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
            $this->lojas = array_combine($matches['id'], $nomes);
        }
        return $this->lojas;
    }
    public function getProductsFromStore(array $lojas = []):array
    {
        $this->Produto = new Produto($this->url, $this->client);
        if (!$lojas) {
            $lojas = $this->lojas;
        }
        if (!is_array($lojas) || !count($lojas)) {
            return [];
        }
        foreach ($lojas as $idLoja => $loja) {
            $this->lojas[$idLoja]['produtos'] = $this->Produto->getProductsFromStore($idLoja);
        }
        return $this->lojas;
    }
    public function getAllData()
    {
        $this->getLojas();
        $this->getProductsFromStore();
        return $this->lojas;
    }
}
