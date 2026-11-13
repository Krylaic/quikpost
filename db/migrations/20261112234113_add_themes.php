<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddThemes extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('themes', ['collation' => 'utf8mb4_general_ci']);
        $table->addColumn('css', 'text')
              ->addColumn('title', 'string', ['limit' => 100])
              ->addColumn('description', 'string', ['limit' => 1000, 'null' => true])
              ->addColumn('image', 'string', ['limit' => 255, 'default' => '/assets/img/default.png'])
              ->addColumn('creation_date', 'datetime')
              ->addColumn('updated_date', 'datetime', ['null' => true])
              ->addColumn('author', 'integer')
              ->addColumn('installs', 'integer', ['default' => 0])
              ->create();

        $users = $this->table('users');
        $users->addColumn('active_theme', 'integer', ['null' => true, 'default' => null])
              ->update();
    }
}
