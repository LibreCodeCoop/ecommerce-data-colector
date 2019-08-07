<?php

use Phinx\Migration\AbstractMigration;

class Produto extends AbstractMigration
{
    public function change()
    {
        $this->table('produto', ['id' => false, 'primary_key' => ['sku']])
            ->addColumn('url', 'string', ['limit' => 500])
            ->addColumn('titulo', 'string', ['limit' => 255])
            ->addColumn('marca', 'string', ['limit' => 100])
            ->addColumn('comprar', 'boolean', ['default' => true])
            ->addColumn('codigo', 'integer')
            ->addColumn('sku', 'string', ['length' => 30])
            ->addColumn('id', 'integer')
            ->addColumn('departamento', 'integer')
            ->addColumn('metadata', 'json')
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->AddIndex(['sku'], ['unique' => true])
            ->addForeignKey('departamento', 'sitemap', 'codigo', ['delete'=> 'NO_ACTION', 'update'=> 'NO_ACTION'])
            ->create();
    }
}
