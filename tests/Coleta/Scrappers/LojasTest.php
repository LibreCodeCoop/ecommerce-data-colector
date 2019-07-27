<?php

use PHPUnit\Framework\TestCase;
use ColetaDados\Scrappers\Lojas;

class LojasTest extends TestCase
{
    public function testGetClientReturnGoutteClient()
    {
        $loja = new Lojas();
        $this->assertInstanceOf(Goutte\Client::class, $loja->getClient() );
    }

    public function testSetNewClientReturnNotGoutteClient()
    {
        $loja = new Lojas();
        $loja->client = new GuzzleHttp\Client();
        $this->assertInstanceOf(GuzzleHttp\Client::class, $loja->getClient());
    }
}