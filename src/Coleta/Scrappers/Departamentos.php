<?php
namespace ColetaDados\Scrappers;

use ColetaDados\Db\DbTrait;

class Departamentos extends Scrapper
{
    /**
     * @var array
     */
    private $departamentos;
    /**
     * @var Produto
     */
    private $produto;
    /**
     * @var array
     */
    private $sitemapUrl;
    use DbTrait;
    public function __construct(string $url, $db)
    {
        $this->url = $url;
        $this->produto = new Produto($this->url);
        $this->setDb($db);
        $this->produto->setDb($db);
    }
    public function processSitemapIndex()
    {
        $this->getDepartmentSitemapFromSitemapIndex();
        foreach ($this->departamentos as $departamento) {
            $this->processSitemap($departamento);
        };
        return $this->departamentos;
    }
    public function setDepartamentos(array $departamentos)
    {
        $this->departamentos = $departamentos;
    }
    public function getDepartmentSitemapFromSitemapIndex()
    {
        if (!$this->departamentos) {
            $this->produto->client = $this->getClient();
            $this->produto->score->client = $this->getClient();
            $crawler = $this->getClient()->request('GET', $this->url . '/sitemap.xml');
            $this->departamentos = $crawler->filter('loc')->reduce(function ($node) {
                return strpos($node->text(), 'depto');
            })->each(function ($node) {
                $return = [];
                $return['sitemap'] = $node->text();
                preg_match('/-depto-(?<nome>.*)-(?<codigo>\d+).xml/', $return['sitemap'], $match);
                $return['codigo'] = (int) $match['codigo'];
                $return['nome'] = str_replace('-', ' ', $match['nome']);
                return $return;
            });
            $this->departamentos = array_combine(
                array_column($this->departamentos, 'codigo'),
                $this->departamentos
            );
            $this->insert($this->departamentos, 'sitemap', 'codigo');
        }
        return $this->departamentos;
    }
    public function processSitemap(array $departamento)
    {
        $urls = $this->getSitemapUrl($departamento);
        foreach ($urls as $url) {
            $path = explode('/', parse_url($url, PHP_URL_PATH));
            switch ($path[1]) {
                case 'departamento':
                    $this->departamentos[$path[2]]['url'] = $url;
                    $this->update('sitemap', ['url = ?'], ['codigo = ?'], [$url, $path[2]]);
                    break;
                case 'produto':
                    $codigoProduto = $path[2];
                    if (isset($this->departamentos[$departamento['codigo']]['produtos'][$codigoProduto])) {
                        break;
                    }
                    $produto = [];
                    try {
                        $produto = $this->produto->getProdutoFromMobile($url, $departamento['codigo']);
                    } catch (\Exception $e) {
                        $produto['error'] = $e->getMessage();
                    }
                    $this->setProduto($departamento['codigo'], $produto);
                    break;
            }
        }
        $this->setScore($departamento['codigo']);
        return $this->departamentos[$departamento['codigo']];
    }
    private function setProduto(int $codigo, array $produto)
    {
        $this->produto->save($produto);
        $this->departamentos[$codigo]['produtos'][$produto['codigo']] = $produto;
    }
    public function setScore(int $codigoDepartamento)
    {
        $idProducts = array_keys($this->departamentos[$codigoDepartamento]['produtos']);
        $scores = $this->produto->score->scores($idProducts);
        if ($scores) {
            foreach ($scores as $idProduct => $score) {
                $this->departamentos[$codigoDepartamento]['produtos'][$idProduct]['score'] = $score;
            }
        }
    }
    public function getSitemapUrl(array $data)
    {
        if (!$this->sitemapUrl) {
            $this->client->request('GET', $data['sitemap']);
            $list = [];
            preg_match_all('/<xhtml:link.*href="(?<url>.*)"/', $this->client->getResponse()->getContent(), $list);
            if ($list) {
                return $this->sitemapUrl = $list['url'];
            }
        }
        return $this->sitemapUrl;
    }
}
