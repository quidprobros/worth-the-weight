<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UsersSettingsMigration extends AbstractMigration
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
        $this->table("users_settings")
            // this is a foreign key!
             ->addColumn('user_id', 'integer', [
                 'null' => false,
                 'signed' => false,
             ])
             ->addColumn('plan_selection', 'text', [
             ])
             ->addColumn('created_at', 'datetime')
             ->addColumn('updated_at', 'datetime')
             ->addForeignKey('user_id', 'users', 'id')
             ->create();
    }
}
