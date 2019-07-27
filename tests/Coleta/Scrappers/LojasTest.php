<?php

use ColetaDados\Scrappers\Lojas;
use Tests\TestCase;

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