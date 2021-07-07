<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MyNewMigration extends AbstractMigration
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
        // create the table
        $table = $this->table('exercise_records');
        // autoincrementing key autocreated by phinx
        $table
            // this is a foreign key!
            ->addColumn('user_id', 'integer', [
                'null' => false
            ])
            ->addColumn('exercised', 'integer', [
                'null' => false, 'default' => 0
            ])
            ->addColumn('created_at', 'integer')
            ->addColumn('updated_at', 'integer')
            ->create();
    }
}
