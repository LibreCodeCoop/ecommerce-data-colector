<?php

namespace ColetaDados\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\LockableTrait;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

class ColetaCommand extends Command
{
    use LockableTrait;
    /**
     * @var OutputInterface
     */
    private $output;
    protected function configure()
    {
        $this
            ->setName('coleta')
            ->setDescription('Coleta dados em site de e-commerce.')
            ->setDefinition([
                new InputOption('url', 'u', InputOption::VALUE_REQUIRED, 'Base URL do site')
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
        } catch (\Exception $e) {
            $output->writeln(
                $e->getMessage()."\n".
                "Execute o comando que segue para mais informações:\n".
                '  consulta --help'
            );
        }
    }
}
