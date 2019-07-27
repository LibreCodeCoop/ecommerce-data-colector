<?php
namespace ColetaDados\Scrappers;

class Lojas extends Scrapper
{
    /**
     * @var array
     */
    private $lojas;
    public function getLojas(string $url)
    {
        if (!$this->lojas) {
            $crawler = $this->getClient()->request('GET', $url);
            $html = $crawler->filter('.box-nossasLojas.superlojas')->html();
            preg_match_all('/href="\/lista\/(?P<id>\d+).*?>(?P<loja>.*?)<\/a/', $html, $matches);
            $nomes = array_map(function($nome) {
                return ['nome' => $nome];
            }, $matches['loja']);
            $this->lojas = array_combine($matches['id'], $nomes);
        }
        return $this->lojas;
    }
}
