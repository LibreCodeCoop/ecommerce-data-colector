<?php

use Phinx\Migration\AbstractMigration;

class SitemapProduto extends AbstractMigration
{
    public function change()
    {
        $this->table('sitemap_produto', ['id' => false, 'primary_key' => ['produto_sku', 'sitemap_codigo']])
            ->addColumn('produto_sku', 'string', ['length' => 30])
            ->addColumn('sitemap_codigo', 'integer')
            ->AddIndex(['produto_sku', 'sitemap_codigo'], ['unique' => true])
            ->addForeignKey('produto_sku', 'produto', 'sku', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
            ->addForeignKey('sitemap_codigo', 'sitemap', 'codigo', ['delete'=> 'CASCADE', 'update'=> 'CASCADE'])
            ->create();
    }
}
