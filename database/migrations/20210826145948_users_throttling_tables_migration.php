<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UsersThrottlingTablesMigration extends AbstractMigration
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
        $table = $this->table(
            'users_throttling',
            [
                'id' => false,
                'primary_key' => ['bucket']
            ]
        );
        // autoincrementing key autocreated by phinx
        $table
            ->addColumn('bucket', 'integer', [
                'null' => false,
                'length' => 44,
            ])
            ->addColumn('tokens', 'float', [
                'null' => false
            ])
            ->addColumn('replenished_at', 'integer', [
                'null' => false
            ])
            ->addColumn('expires_at', 'integer', [
                'null' => false
            ])
            ->addIndex(['expires_at'])
            ->create();
    }
}
