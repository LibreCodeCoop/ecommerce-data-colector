<?php

use Tests\TestCase;
use ColetaDados\Scrappers\Produto;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ProdutoTest extends TestCase
{
    public function testGetProductsInEnptyList() {
        $client = new HttpBrowser(new MockHttpClient([
            new MockResponse('html'), // Page 1
        ]));
        $Product = new Produto('http://test/');
        $Product->client = $client;
        $this->assertCount(0, $Product->getProductsFromStore(0));
    }
    public function testGetProductsReturn2Products() {
        $client = new HttpBrowser(new MockHttpClient([
            new MockResponse(file_get_contents(__DIR__.'/Fixtures/Web/List-2Products-1Page.html')), // Page 1
            new MockResponse('<div id="AddCarrinhoStatus">carrinho 50 unidade</div>'), // Stock Product 1
            new MockResponse(''), // Stock Product 2
            new MockResponse(file_get_contents(__DIR__.'/Fixtures/Web/Stars-2Products-1Page.json')), // Stars
        ]));
        $Product = new Produto('http://teste');
        $Product->client = $client;
        $products = $Product->getProductsFromStore(0);
        $this->assertJsonStringEqualsJsonFile(__DIR__.'/Fixtures/Web/List-2Products-1Page.json', json_encode($products));
    }
}