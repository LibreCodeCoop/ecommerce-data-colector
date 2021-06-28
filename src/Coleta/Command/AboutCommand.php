<?php

namespace ColetaDados\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class AboutCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription('Exibe informações breves sobre o Coleta Dados.')
            ->setHelp(<<<HELP
                <info>php coleta-dados.phar about</info>

                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write(<<<HELP
            <info>Coleta dados em site de e-commerce</info>
            <comment>Coleta de dados de site de e-commerce.</comment>
            Veja https://github.com/librecodecoop/ecommerce-data-colector/ para mais informações.

            HELP
        );
    }
}
