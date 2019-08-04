<?php
namespace ColetaDados\Scrappers;

use Symfony\Component\DomCrawler\Crawler;

class Score extends Scrapper
{
    /**
     * @var string
     */
    private $storeKey = '';
    public function scores(array $idProducts):array
    {
        $client = $this->getClient();
        $client->request(
            'GET',
            'https://service.yourviews.com.br/review/productShelf' .
            '?storeKey=' . $this->storeKey .
            '&ids=' . implode(',', $idProducts),
            [],
            [],
            ['HTTP_ACCEPT'=>'text/html']
        );
        $json = $client->getResponse()->getContent();
        $stars = json_decode($json, true);
        $return = [];
        if ($stars) {
            foreach ($stars as $product) {
                preg_match('/\((?<score>\d+)\)/', $product['data'], $matches);
                $return[$product['productId']] = (int) $matches['score'];
            }
        }
        return $return;
    }
    public function getStoreKey(Crawler $crawler):string
    {
        if (!$this->storeKey && $crawler) {
            $html = $crawler->html();
            preg_match('/script\/(?<storekey>.*)\/yvapi.js/', $html, $matches);
            if (isset($matches['storekey'])) {
                return $this->storeKey = $matches['storekey'];
            }
        }
        return $this->storeKey;
    }
}
