<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UsersRememberedTablesMigration extends AbstractMigration
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
        $table = $this->table('users_remembered');
        // autoincrementing key autocreated by phinx
        $table
            ->addColumn('user', 'integer', [
                'null' => false
            ])
            ->addColumn('selector', 'string', [
                'null' => false,
                'length' => 24,
            ])
            ->addColumn('token', 'string', [
                'null' => false,
                'length' => 255,
            ])
            ->addColumn('expires', 'integer', [
                'null' => false
            ])
            ->addIndex(['user'])
            ->addIndex(['selector'], ['unique' => true])
            ->create();
    }
}
