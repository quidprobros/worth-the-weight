<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UserGoalsMigration extends AbstractMigration
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
        $table = $this->table('user_goals');
        // autoincrementing key autocreated by phinx
        $table
            ->addColumn('personal_title', 'text')
            ->addColumn('user_id', 'integer', [
                'null' => false,
            ])
            // this is the limit for a given day
            ->addColumn('daily_points', 'float', [
                'default' => 0.0
            ])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->create();

    }
}
