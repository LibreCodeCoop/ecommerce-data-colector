<?php
use League\CLImate\CLImate;

require_once 'vendor/autoload.php';

if(php_sapi_name() !== 'cli'){
    die('Can only be executed via CLI');
}

$climate = new CLImate();
$climate->info('####################################################');
$climate->br();
$climate->lightGreen()->out('Bem vindo ao Coleta Dados!');
$climate->br();
$climate->info('####################################################');
