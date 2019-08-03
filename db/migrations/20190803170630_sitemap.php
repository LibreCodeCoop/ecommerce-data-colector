<?php

use Phinx\Migration\AbstractMigration;

class Sitemap extends AbstractMigration
{
    public function change()
    {
        $this->table('sitemap', ['id' => false, 'primary_key' => ['codigo']])
            ->addColumn('sitemap', 'string', ['limit' => 500])
            ->addColumn('url', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('nome', 'string', ['limit' => 50])
            ->addColumn('codigo', 'integer')
            ->AddIndex(['codigo'], ['unique' => true])
            ->create();
    }
}
