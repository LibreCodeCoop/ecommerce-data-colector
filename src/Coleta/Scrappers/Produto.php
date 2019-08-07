<?php
namespace ColetaDados\Scrappers;

use Symfony\Component\DomCrawler\Crawler;
use Cocur\Slugify\Slugify;
use ColetaDados\Db\DbTrait;

class Produto extends Scrapper
{
    /**
     * @var array
     */
    private $products;
    /**
     * @var int
     */
    private $lastPage;
    /**
     * @var Slugify
     */
    private $Slugify;
    /**
     * @var Score
     */
    public $score;
    use DbTrait;
    /**
     * Coleta dados de produtos
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->url = $url;
        $this->Slugify = new Slugify();
        $this->score = new Score();
    }

    public function getProductsFromStore(int $idLoja):array
    {
        $this->products = [];
        $page = 1;
        do {
            $this->getPage($idLoja, $page);
            $page++;
        } while ($page < $this->lastPage);
        return $this->products;
    }

    private function getPage(int $idLoja, int $page)
    {
        $crawler = $this->getClient()->request(
            'GET',
            $this->url . '/lista/' . $idLoja . '/-/pag/' . $page
        );
        $this->score->getStoreKey($crawler);
        $this->getLastPage($crawler);
        $this->getAllProductsFromWeb($crawler);
    }

    private function getLastPage(Crawler $crawler):int
    {
        $href = $crawler->filter('.pages .collection-pagination-pages .last-page');
        if (!$href->count()) {
            return 0;
        }
        $href = $href->attr('href');
        return $this->lastPage = (int) substr($href, strrpos($href, '/')+1);
    }

    private function getAllProductsFromWeb(Crawler $crawler)
    {
        $this->products = $crawler->filter('.collection.dept-collection.loadProducts li')->each(function (Crawler $node) {
            $product = [];
            $product['id'] = $node->attr('data-productid');
            $product['sku'] = $node->filterXPath('//*[@data-sku]')->attr('data-sku');
            $img = $node->filterXPath('//*/img[@data-src]');
            $product['img'] = $img->attr('data-src');
            $product['img'] = substr($product['img'], 0, strpos($product['img'], '?'));
            $product['name'] = trim($img->attr('alt'));
            $href = $node->filter('.collection-product-price a');
            $product['href'] = $href->attr('href');
            $product['price'] = $this->textToFloat($href->text());
            $href = $node->filter('.collection-product-buy-bt')->attr('href');
            preg_match("/ '(?<codigo>\d+)',(?<spy>true|false)/", $href, $matches);
            if (isset($matches['spy']) && $matches['spy'] == 'true') {
                $product['spy'] = true;
            } else {
                $product['spy'] = false;
            }
            $product['codigo'] = (int) $matches['codigo'];
            $smallDescription = $node->filter('.collection-product-description a');
            if ($smallDescription->count()) {
                $product['included'] = $smallDescription->text();
            }
            $originalPrice = $node->filterXPath('//*/del');
            if ($originalPrice->count()) {
                $product['original_price'] = $this->textToFloat($originalPrice->text());
                $product['discount'] = trim($node->filter('.collection-product-discountPercent')->text());
            }
            $score = $node->filter('.yv-bootstrap');
            if ($score->count()) {
                $product['score'] = (int) preg_replace('/[^\d]/', '', $score->text());
            }
            $product['stock'] = $this->getStock($product['codigo']);
            return $product;
        });
    }

    private function getStock(int $productCode):int
    {
        $crawler = $this->getClient()->request(
            'GET',
            $this->url . '/AddCarrinho?CodVar=' . $productCode . '&qtd=999999999'
        );
        $response = $crawler->filter('#AddCarrinhoStatus');
        if ($response->count()) {
            $response = $response->text();
            preg_match('/carrinho (?<stock>\d+) unidade/', $response, $matches);
            if (isset($matches['stock'])) {
                return (int) $matches['stock'];
            }
        }
        return 0;
    }

    private static function textToFloat(string $price):float
    {
        $float = 0.0;
        preg_match('/(\d+(.|,))+(\d)+/', $price, $matches);
        if (isset($matches[0])) {
            $float = str_replace('.', '', $matches[0]);
            $float = str_replace(',', '.', $float);
            $float = (float) $float;
        }
        return $float;
    }
    public static function getCodigoFromUrl(string $url):string
    {
        $path = explode('/', parse_url($url, PHP_URL_PATH));
        return $path[2];
    }
    public function getProdutoFromMobile(string $url, $departamento)
    {
        $product = [];
        $crawler = $this->getClient()->request('GET', $url);
        $this->score->getStoreKey($crawler);
        $product['url'] = $url;
        $product['sku'] = $this->getCodigoFromUrl($url);
        $name = $crawler->filter('.product-name');
        $product['codigo'] = (int) $name->attr('data-code');
        $product['id'] = (int) $name->attr('data-id');
        $product['titulo'] = $crawler->filter('meta[property="og:title"]')->attr('content');
        $product['marca'] = trim(substr(
            $product['titulo'],
            strrpos($product['titulo'], ' - ') + 3,
        ));
        $product['titulo'] = str_replace(' - ' . $product['marca'], '', $product['titulo']);
        $product['imagens'] = $crawler
            ->filter('.product-image-big img, .product-image-small img')
            ->each(function ($node) {
                return strtok($node->attr('src'), '?');
            });
        $this->getVariants($crawler, $product);
        $this->getPromos($crawler, $product);
        $product['descricao_curta'] = $crawler->filter('.product-shortDescription')->text();
        $product['descricao_curta'] = trim(preg_replace('!\s+!', ' ', $product['descricao_curta']));
        $product['price'] = $crawler->filter('.product-price-price')->text();
        $product['price'] = $this->textToFloat($product['price']);
        $this->getProdutoDescriptions($crawler, $product);
        $originalPrice = $crawler->filter('.produt-oldPrice-price');
        if ($originalPrice->count()) {
            $product['original_price'] = $this->textToFloat($originalPrice->text());
            $discount = $crawler->filter('.product-discountPercent');
            $product['discount'] = trim($discount->text());
        }
        $splitPrice = $crawler->filter('.product-splitPrice-quantity');
        if ($splitPrice->count()) {
            $product['split_price_quantity'] = (int) $splitPrice->html();
            $product['split_price_price'] = $this->textToFloat(
                $crawler->filter('.product-splitPrice-price')->html()
            );
        }
        $bestPrice = $crawler->filter('.product-bestPrice strong');
        if (count($bestPrice)) {
            $product['best_price'] = $this->textToFloat($bestPrice->text());
        }
        $product['departamento'] = $departamento;
        return $product;
    }
    private static function getPromos(Crawler $crawler, array &$product)
    {
        $promos = $crawler->filter('.product-promo')->each(function (Crawler $node) {
            $return = [];
            $return['type'] = $node->attr('class');
            $return['type'] = trim(str_replace('product-promo', '', $return['type']), ' -');
            $return['text'] = $node->text();
            return $return;
        });
        if ($promos) {
            $product['promos'] = $promos;
        }
    }
    private function getVariants(Crawler $crawler, array &$product)
    {
        $variants = $crawler->filter('.product-variants-scroll tr')->each(function (Crawler $node) {
            $return = [];
            $a = $node->filter('[data-sku]');
            $return['sku'] = $a->attr('data-sku');
            $return['codigo'] = (int) $a->attr('data-variantid');
            $return['comprar'] = $a->text() == 'Comprar'?1:0;
            $return['title'] = trim($node->filter('.product-variant-title')->text());
            $return['title'] = str_replace(' ' . $return['sku'], '', $return['title']);
            $return['price'] = $node->filter('[data-Price]')->attr('data-price');
            $return['price'] = $this->textToFloat($return['price']);
            $return['image'] = strtok($node->filter('[data-variantImage]')->attr('data-variantimage'), '?');
            if ($return['comprar']) {
                $return['stock'] = $this->getStock($return['codigo']);
            } else {
                $return['stock'] = 0;
            }
            return $return;
        });
        if ($variants) {
            foreach ($variants as $variant) {
                $product['variants'][$variant['sku']] = $variant;
            }
        } else {
            $a = $crawler->filter('.product-variants a[data-sku]');
            $product['comprar'] = $a->text() == 'Comprar'?1:0;
            if ($product['comprar']) {
                $product['stock'] = $this->getStock($product['codigo']);
            } else {
                $product['stock'] = 0;
            }
        }
    }
    private function getProdutoDescriptions(Crawler $crawler, array &$product)
    {
        $metadata = $crawler->filter('.product-descriptions-item')->each(function ($node) {
            return [
                'id'   => $node->attr('data-description'),
                'name' => $node->attr('data-name')
            ];
        });
        foreach ($metadata as $data) {
            $text = $crawler->filter('div[data-description-content='.$data['id'].']')->html();
            $text = preg_replace('!\s+!', ' ', $text);
            $product['descriptions'][$this->Slugify->slugify($data['name'], '_')] = trim($text);
        }
    }
    public function save(array $produto)
    {
        $data = $produto;
        if (isset($data['variants'])) {
            $variants = $data['variants'];
            unset($data['variants']);
        }
        $data['metadata'] = $data;
        unset(
            $data['imagens'],
            $data['descriptions'],
            $data['split_price_quantity'],
            $data['split_price_price'],
            $data['original_price'],
            $data['discount'],
            $data['price'],
            $data['stock'],
            $data['promos'],
            $data['descricao_curta'],
            $data['metadata']['url'],
            $data['metadata']['titulo'],
            $data['metadata']['marca'],
            $data['metadata']['codigo'],
            $data['metadata']['sku'],
            $data['metadata']['id'],
            $data['metadata']['departamento']
        );
        $data['metadata'] = json_encode($data['metadata']);
        $this->insert($data, 'produto', 'sku');
        if (isset($variants)) {
            foreach ($variants as &$variant) {
                $variant['produto_sku'] = $data['sku'];
            }
            $this->insert($variants, 'variant', 'sku');
        }
    }
}
