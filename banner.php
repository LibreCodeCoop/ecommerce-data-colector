<?php
require_once 'vendor/autoload.php';

use League\CLImate\CLImate;

if(php_sapi_name() !== 'cli'){
    die('Can only be executed via CLI');
}

function dbIsUp() {
    try {
        $dsn = 'pgsql:dbname='.getenv('DB_NAME').';host='.getenv('DB_HOST');
        new PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWD'));
    } catch(Exception $e) {
        return false;
    }
    return true;
}

while(!dbIsUp()) {
    sleep(1);
}

$climate = new CLImate();
$climate->info('####################################################');
$climate->br();
$climate->lightGreen()->out('Bem vindo ao Coleta Dados!');
$climate->br();
$climate->info('####################################################');
