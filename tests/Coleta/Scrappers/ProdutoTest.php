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
        $Product = new Produto('http://test/', $client);
        $this->assertCount(0, $Product->getProductsFromStore(0));
    }
    public function testGetProductsReturn2Products() {
        $client = new HttpBrowser(new MockHttpClient([
            new MockResponse(file_get_contents(__DIR__.'/Fixtures/List-2Products-1Page.html')), // Page 1
            new MockResponse('<div id="AddCarrinhoStatus">carrinho 50 unidade</div>'), // Stock Product 1
            new MockResponse(''), // Stock Product 2
            new MockResponse(file_get_contents(__DIR__.'/Fixtures/Statrs-2Products-1Page.json')), // Stars
        ]));
        $Product = new Produto('http://test/', $client);
        $products = $Product->getProductsFromStore(0);
        $expected = json_decode(file_get_contents(__DIR__.'/Fixtures/List-2Products-1Page.json'), true);
        $this->assertEquals($expected, $products);
    }
}