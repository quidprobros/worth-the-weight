<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class PointsRecordsTablesMigration extends AbstractMigration
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

        $table = $this->table('points_records');
        // autoincrementing key autocreated by phinx
        $table
            // this is a foreign key!
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('food_id', 'integer', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('quantity', 'float', [
                'null' => false,
                'default' => 0.0
            ])
            ->addColumn('points', 'float', [ // 2
                'null' => false,
                'default' => 0.0
            ])
            ->addColumn('date', 'date')
            ->addColumn('time', 'time')
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            // ->addForeignKey('user_id', 'users', 'id', [
            // ])
            //   ->addForeignKey('food_id', 'food_records', 'id', [
            // ])
            ->create();


    }
}
