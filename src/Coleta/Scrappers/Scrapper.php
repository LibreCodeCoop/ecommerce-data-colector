<?php
namespace ColetaDados\Scrappers;

use Goutte\Client;

abstract class Scrapper
{
    /**
     * @var Client
     */
    public $client;
    /**
     * Retorna um client HTTP
     *
     * @return Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client([['verify' => false]]);
        }
        return $this->client;
    }
}
