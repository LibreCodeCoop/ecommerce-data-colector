#!/usr/bin/env php
<?php
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
