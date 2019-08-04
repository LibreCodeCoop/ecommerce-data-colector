<?php

use ColetaDados\Scrappers\Lojas;
use Tests\TestCase;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class LojasTest extends TestCase
{
    public function testGetClientReturnGoutteClient()
    {
        $loja = new Lojas('http://teste');
        $this->assertInstanceOf(HttpBrowser::class, $loja->getClient() );
    }

    public function testSetNewClientReturnNotGoutteClient()
    {
        $loja = new Lojas('http://teste');
        $loja->client = new HttpBrowser();
        $this->assertInstanceOf(HttpBrowser::class, $loja->getClient());
    }

    public function testGetTwoProdutcsReturnWithProducts()
    {
        $loja = new Lojas('http://teste');
        $html = file_get_contents(__DIR__.'/Fixtures/Web/mock.html');
        $loja->client = new HttpBrowser(new MockHttpClient([
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]]),// setUp   getCookies
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]]),// getLoja homeSite
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]]),// getPage Pagina 1
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]]),// getStock
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]]),// getStock
            new MockResponse(file_get_contents(__DIR__.'/Fixtures/Web/Stars-2Products-1Page.json')),//Score
        ]));
        $loja->getLojas();
        $produtos = $loja->getProductsFromStore();
        $this->assertArrayHasKey(22, $produtos);
        $this->assertArrayHasKey('produtos', $produtos[22]);
    }

    public function testSetAndGetLojas()
    {
        $loja = new Lojas('http://teste');
        $lojas = [22 => ['nome' => 'LojaTeste']];
        $loja->setLojas($lojas);
        $this->assertEquals($lojas, $loja->getLojas());
    }

    public function testGetProductsForZeroStores()
    {
        $loja = new Lojas('http://teste');
        $html = file_get_contents(__DIR__.'/Fixtures/Web/mock.html');
        $loja->client = new HttpBrowser(new MockHttpClient([
            new MockResponse($html, ['response_headers' => ['Set-cookie' => 1]])
        ]));
        $this->assertCount(0, $loja->getProductsFromStore());
    }
}