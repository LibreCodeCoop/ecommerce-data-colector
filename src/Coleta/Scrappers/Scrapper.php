<?php
namespace ColetaDados\Scrappers;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\BrowserKit\HttpBrowser;

abstract class Scrapper
{
    /**
     * @var HttpBrowser
     */
    public $client;
    /**
     * @var string
     */
    protected $url;
    /**
     * Retorna um client HTTP
     *
     * @return HttpBrowser
     */
    public function getClient()
    {
        if (empty($this->client)) {
            $this->client = new HttpBrowser(HttpClient::create([
                'headers' => [
                    'Host' => parse_url($this->url)['host']
                ]
            ]));
            $this->client->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36');
        }
        return $this->client;
    }
    /**
     * Request to a URL to get the cookie
     */
    protected function setUp()
    {
        $client = $this->getClient();
        $client->request('GET', $this->url . '/login.json/isAuthenticated');
    }
}
