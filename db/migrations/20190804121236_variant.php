<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

class Variant extends AbstractMigration
{
    public function change()
    {
        $this->table('variant', ['id' => false, 'primary_key' => ['sku']])
            ->addColumn('title', 'string', ['limit' => 255])
            ->addColumn('codigo', 'integer')
            ->addColumn('sku', 'string', ['length' => 30])
            ->addColumn('produto_sku', 'string', ['length' => 30])
            ->addColumn('id', 'integer', ['null' => true])
            ->addColumn('comprar', 'boolean')
            ->addColumn('stock', 'integer')
            ->addColumn('image', 'string', ['limit' => 255])
            ->addColumn('price', Literal::from('NUMERIC(10, 2)'))
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->AddIndex(['sku'], ['unique' => true])
            ->addForeignKey('produto_sku', 'produto', 'sku', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
            ->create();
    }
}
