<?php
namespace ColetaDados\Scrappers;

class Lojas extends Scrapper
{
    /**
     * @var array
     */
    private $lojas;
    /**
     * @var Produto
     */
    private $Produto;
    public function __construct(string $url)
    {
        $this->url = $url;
    }
    public function setLojas(array $lojas)
    {
        $this->lojas = $lojas;
    }
    public function getLojas()
    {
        if (!$this->lojas) {
            $client = $this->getClient();
            $this->setUp();
            $crawler = $client->request('GET', $this->url);
            $html = $crawler->filter('.box-nossasLojas.superlojas')->html();
            preg_match_all('/href="\/lista\/(?P<id>\d+).*?>(?P<loja>.*?)<\/a/', $html, $matches);
            $nomes = array_map(function ($nome) {
                return ['nome' => $nome];
            }, $matches['loja']);
            $this->lojas = array_combine(array_map('intval', $matches['id']), $nomes);
        }
        return $this->lojas;
    }
    public function getProductsFromStore():array
    {
        $this->Produto = new Produto($this->url);
        $this->Produto->client = $this->client;
        if (!is_array($this->lojas) || !count($this->lojas)) {
            return [];
        }
        foreach ($this->lojas as $idLoja => $name) {
            $this->lojas[$idLoja]['produtos'] = $this->Produto->getProductsFromStore($idLoja);
        }
        return $this->lojas;
    }
}
