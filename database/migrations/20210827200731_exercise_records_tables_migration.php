<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ExerciseRecordsTablesMigration extends AbstractMigration
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
        $table = $this->table('exercise_records');
        // autoincrementing key autocreated by phinx
        $table
            ->addColumn('user_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('exercised', 'integer', [
                'null' => false,
                'default' => 0
            ])
            ->addColumn('date', 'date', [
            ])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addForeignKey('user_id', 'users', 'id', [
            ])
            ->create();
    }
}
