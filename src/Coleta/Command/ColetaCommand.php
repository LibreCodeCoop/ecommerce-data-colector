<?php

namespace ColetaDados\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Question\ChoiceQuestion;
use ColetaDados\Scrappers\Lojas;
use ColetaDados\Scrappers\Departamentos;
use Symfony\Component\Console\Helper\ProgressBar;
use Cocur\Slugify\Slugify;

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
     * @var Lojas
     */
    private $lojas;
    /**
     * @var string
     */
    public $url;
    protected function configure()
    {
        $this
            ->setName('coleta')
            ->setDescription('Coleta dados em site de e-commerce.')
            ->setDefinition([
                new InputOption('url', 'u', InputOption::VALUE_REQUIRED, 'Base URL do site'),
                new InputOption('lojas', 'l', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Lista de lojas'),
                new InputOption('departamentos', 'd', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Lista de departamentos')
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
            $this->url = (string)$this->input->getOption('url');
            if ($input->getOption('lojas')) {
                $this->getLojas((array)$input->getOption('lojas'));
                $this->getLoja()->getProductsFromStore();
            } elseif ($input->getOption('departamentos')) {
                $this->getDepartamentos((array)$input->getOption('departamentos'));
            } else {
                throw new \Exception(
                    '<error>Necessário informar a forma de coleta de dados que deseja realizar</error>'
                );
            }
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

    public function getLoja()
    {
        if (!$this->lojas) {
            $this->lojas = new Lojas($this->url);
        }
        return $this->lojas;
    }
    private function getDepartamentos(array $departamentos)
    {
        $lista = new Departamentos($this->url);
        $lista->setDepartamentos(array_intersect_key($lista->getDepartmentSitemapFromSitemapIndex(), array_flip($departamentos)));
        $departments = $lista->getDepartmentSitemapFromSitemapIndex();
        $progressBar = new ProgressBar($this->output, count($departments));
        $progressBar->start();
        $slug = new Slugify();
        foreach ($departments as $department) {
            $department = $lista->processSitemap($department);
            $filename =
                'data-'.$department['codigo'].'-'.
                $slug->slugify($department['nome']).'-'.
                date('YmdHis').'.json';
            file_put_contents(
                $filename,
                json_encode($department, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)
            );
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->output->writeln('');
    }

    private function getLojas(array $lojas)
    {
        $selected = [];
        $lista = $this->getLoja()->getLojas();
        if (!$lojas) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Informe as lojas desejadas, ENTER para selecionar todas, CTRL+C para cancelar',
                array_map(
                    function ($loja) {
                        return str_replace(' ', '-', $loja['nome']);
                    },
                    $lista
                ),
                implode(',', array_keys($lista))
            );
            $question->setMultiselect(true);
            $positionsResponses = $helper->ask($this->input, $this->output, $question);
            $realPositions = array_intersect(
                array_map(
                    function ($loja) {
                        return str_replace(' ', '-', $loja['nome']);
                    },
                    $lista
                ),
                $positionsResponses
            );
            $selected = array_filter($lista, function ($key) use ($realPositions) {
                return isset($realPositions[$key]);
            }, ARRAY_FILTER_USE_KEY);
        } else {
            foreach ($lojas as $idLoja) {
                if (!isset($lista[$idLoja])) {
                    $this->output->writeln('Loja inválida');
                    $this->getLojas([]);
                    return;
                }
                $selected[$idLoja] = $lista[$idLoja];
            }
        }
        $this->getLoja()->setLojas($selected);
        return $selected;
    }
}
