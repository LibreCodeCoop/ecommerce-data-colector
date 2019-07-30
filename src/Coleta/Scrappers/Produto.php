<?php
namespace ColetaDados\Scrappers;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\HttpBrowser;
use function GuzzleHttp\json_decode;

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
     * Coleta dados de produtos
     * @param string $url
     * @param HttpBrowser $client
     */
    public function __construct(string $url, HttpBrowser $client)
    {
        $this->url = $url;
        $this->client = $client;
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
        $this->getLastPage($crawler);
        $this->getAllProducts($crawler);
        $this->getScore($crawler);
    }
    private function getStoreKey($crawler)
    {
        $html = $crawler->html();
        preg_match('/script\/(?<storekey>.*)\/yvapi.js/', $html, $matches);
        if (isset($matches['storekey'])) {
            return $matches['storekey'];
        }
        return '';
    }

    private function getLastPage($crawler):int
    {
        $href = $crawler->filter('.pages .collection-pagination-pages .last-page');
        if (!$href->count()) {
            return 0;
        }
        $href = $href->attr('href');
        return $this->lastPage = (int) substr($href, strrpos($href, '/')+1);
    }

    private function getAllProducts(Crawler $crawler)
    {
        $this->products = $crawler->filter('.collection.dept-collection.loadProducts li')->each(function (Crawler $node, $i) {
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
            preg_match("/ '(?<code>\d+)',(?<spy>true|false)/", $href, $matches);
            if (isset($matches['spy']) && $matches['spy'] == 'true') {
                $product['spy'] = true;
            } else {
                $product['spy'] = false;
            }
            $product['code'] = $matches['code'];
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
            $product['stock'] = $this->getStock($product['code']);
            return $product;
        });
    }

    private function getStock(int $productCode):int
    {
        $crawler = $this->getClient()->request(
            'GET',
            $this->url . '/AddCarrinho?CodVar=' . $productCode . '&Qtd=999999999&source=cd&rand='.rand(1, 999)
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
    
    private function getScore($crawler)
    {
        $storeKey = $this->getStoreKey($crawler);
        if (!$storeKey) {
            return;
        }
        $ids = array_column($this->products, 'id');
        $client = $this->getClient();
        $client->request(
            'GET',
            'https://service.yourviews.com.br/review/productShelf' .
            '?storeKey=' . $storeKey .
            '&ids=' . implode(',', $ids)
        );
        $json = $client->getResponse()->getContent();
        $stars = json_decode($json);
        foreach ($this->products as $key => $product) {
            foreach ($stars as $pos => $data) {
                if ($data->productId == $product['id']) {
                    preg_match('/\((?<score>\d+)\)/', $data->data, $matches);
                    $this->products[$key]['score'] = (int) $matches['score'];
                    unset($stars[$pos]);
                    continue 2;
                }
            }
        }
    }

    private function textToFloat(string $price):float
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
}
