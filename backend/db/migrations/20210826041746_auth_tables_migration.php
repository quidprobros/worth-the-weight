<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AuthTablesMigration extends AbstractMigration
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
        $table = $this->table('users');
        // autoincrementing key autocreated by phinx
        $table
            ->addColumn('email', 'string', [
                'null' => false,
                'length' => 249,
            ])
            ->addColumn('password', 'string', [
                'null' => false,
                'length' => 255,
            ])
            ->addColumn('username', 'string', [
                'null' => true,
                'length' => 100,
            ])
            ->addColumn('status', 'integer', [
                'null' => false,
                'default' => 0,
            ])
            ->addColumn('verified', 'integer', [
                'null' => false,
                'default' => 0,
            ])
            ->addColumn('resettable', 'integer', [
                'null' => false,
                'default' => 1,
            ])
            ->addColumn('roles_mask', 'integer', [
                'null' => false,
                'default' => 0,
            ])
            ->addColumn('registered', 'integer', [
                'null' => false,
            ])
            ->addColumn('last_login', 'integer', [
                'null' => true,
            ])
            ->addColumn('force_logout', 'integer', [
                'null' => false,
                'default' => 0,
            ])
            ->addIndex(['email'], ['unique' => true])
            ->create();
    }
}
