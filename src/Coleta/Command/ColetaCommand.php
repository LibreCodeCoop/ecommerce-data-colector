<?php

namespace ColetaDados\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Goutte\Client;

class ColetaCommand extends Command
{
    use LockableTrait;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var Client
     */
    public $client;
    protected function configure()
    {
        $this
            ->setName('coleta')
            ->setDescription('Coleta dados em site de e-commerce.')
            ->setDefinition([
                new InputOption('url', 'u', InputOption::VALUE_REQUIRED, 'Base URL do site'),
                new InputOption('lojas', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Lista de lojas')
            ])
            ->setHelp(<<<HELP
                O comando <info>coleta</info> realiza a coletat de dados.
                
                Maiores informações:
                    https://github.com/LyseonTech/ecommerce-data-colector
                HELP
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lock()) {
            $output->writeln('Este comando já está em execução em outro processo.');
            return 0;
        }
        try {
            $url = $input->getOption('url');
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \Exception(
                    '<error>URL inválida</error>'
                );
            }
            $this->input = $input;
            $this->output = $output;
            $this->getLojas((array)$input->getOption('lojas'));
            $this->release();
        } catch (\Exception $e) {
            $this->release();
            $output->writeln(
                $e->getMessage()."\n".
                "Execute o comando que segue para mais informações:\n".
                '  consulta --help'
            );
            return 0;
        }
    }

    /**
     * Retorna um client Goutte
     *
     * @return Client
     */
    private function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client([['verify' => false]]);
        }
        return $this->client;
    }

    private function getLojas(array $lojas)
    {
        $crawler = $this->getClient()->request('GET', (string)$this->input->getOption('url'));
        $html = $crawler->filter('.box-nossasLojas.superlojas')->html();
        preg_match_all('/href="\/lista\/(?P<id>\d+).*?>(?P<loja>.*?)<\/a/', $html, $matches);
        if (!$lojas) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Informe as lojas desejadas, ENTER para selecionar todas, CTRL+C para cancelar',
                array_map(
                    function ($loja) {
                        return str_replace(' ', '-', $loja);
                    },
                    $matches['loja']
                ),
                implode(',', array_keys($matches['id']))
            );
            $question->setMultiselect(true);
            $positionsResponses = $helper->ask($this->input, $this->output, $question);
            $realPositions = array_intersect(
                array_map(
                    function ($loja) {
                        return str_replace(' ', '-', $loja);
                    },
                    $matches['loja']
                ),
                $positionsResponses
            );
            $lojas = array_intersect_key($matches['id'], $realPositions);
        } else {
            foreach ($lojas as $loja) {
                if (!isset($matches['id'][$loja])) {
                    $this->output->writeln('Loja inválida');
                    $this->getLojas([]);
                    break;
                }
            }
        }
        return $lojas;
    }
}
