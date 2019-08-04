<?php
namespace ColetaDados\Scrappers;

use Symfony\Component\DomCrawler\Crawler;
use Cocur\Slugify\Slugify;

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
                $product['original-price'] = $this->textToFloat($originalPrice->text());
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
    public static function getCodigoFromUrl(string $url):int
    {
        preg_match('/produto\/(?<codigo>\d+)\//', $url, $match);
        return (int) $match['codigo'];
    }
    public function getProdutoFromMobile(string $url)
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
        $restrict = $crawler->filter('.product-promo-restrict');
        if (count($restrict)) {
            $product['restrict'] = trim($restrict->text());
        }
        $product['descricao-curta'] = $crawler->filter('.product-shortDescription')->text();
        $product['descricao-curta'] = trim(preg_replace('!\s+!', ' ', $product['descricao-curta']));
        $product['price'] = $crawler->filter('.product-price-price')->text();
        $product['price'] = $this->textToFloat($product['price']);
        $this->getProdutoDescriptions($crawler, $product);
        $originalPrice = $crawler->filter('.produt-oldPrice-price');
        if ($originalPrice->count()) {
            $product['original-price'] = $this->textToFloat($originalPrice->text());
            $discount = $crawler->filter('.product-discountPercent');
            $product['discount'] = trim($discount->text());
        }
        $splitPrice = $crawler->filter('.product-splitPrice-quantity');
        if ($splitPrice->count()) {
            $product['split-price-quantity'] = (int) $splitPrice->html();
            $product['split-price-price'] = $this->textToFloat(
                $crawler->filter('.product-splitPrice-price')->html()
            );
        }
        return $product;
    }
    private function getVariants(Crawler $crawler, array &$product)
    {
        $variants = $crawler->filter('.product-variants-scroll tr')->each(function (Crawler $node) {
            $return = [];
            $a = $node->filter('[data-sku]');
            $return['sku'] = (int) $a->attr('data-sku');
            $return['codigo'] = (int) $a->attr('data-variantid');
            $return['comprar'] = $a->text() == 'Comprar';
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
                if ($product['codigo'] == $variant['codigo']) {
                    if (!in_array($variant['image'], $product['imagens'])) {
                        $product['imagens'][] = $variant['image'];
                    }
                    $product['stock'] = $variant['stock'];
                }
                $product['variants'][$variant['sku']] = $variant;
            }
        } else {
            $a = $crawler->filter('.product-variants a[data-sku]');
            $product['comprar'] = $a->text() == 'Comprar';
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
            $product[$this->Slugify->slugify($data['name'])] = trim($text);
        }
    }
}
