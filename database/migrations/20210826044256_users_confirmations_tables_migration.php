<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UsersConfirmationsTablesMigration extends AbstractMigration
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
    public function up(): void
    {

        $table = $this->table('users_confirmations');
        // autoincrementing key autocreated by phinx
        $table
            ->addColumn('user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('email', 'string', [
                'null' => false,
                'length' => 249,
            ])
            ->addColumn('selector', 'string', [
                'null' => false,
                'length' => 16
            ])
            ->addColumn('token', 'string', [
                'null' => false,
                'length' => 255
            ])
            ->addColumn('expires', 'integer', [
                'null' => false
            ])
            ->addColumn('shart', 'integer', [
                'null' => false
            ])
            ->addIndex(['email', 'expires'], [
                'name' => 'email_expires'
            ])
            ->addIndex(['user_id'])
            ->addIndex(['selector'], ['unique' => true])
            ->create();
    }
}
