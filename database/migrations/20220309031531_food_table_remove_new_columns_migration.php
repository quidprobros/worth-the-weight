<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FoodTableRemoveNewColumnsMigration extends AbstractMigration
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
        $table = $this->table('food_records')
                      ->removeColumn("green_plan_points")
                      ->removeColumn("purple_plan_points")
                      ->renameColumn("blue_plan_points", "points")
                      ->addColumn("plan_id", 'integer', [
                          'null' => false,
                          'default' => 2
                      ])
                      ->addForeignKey('plan_id', 'plans', 'id')
                      ->update();
    }
}
