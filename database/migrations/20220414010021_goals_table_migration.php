<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class GoalsTableMigration extends AbstractMigration
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
        $this->table("goals")
             ->addColumn("goal_key", "text")
             ->addColumn("goal_short_name", "text")
             ->addColumn("goal_short_description", "text")
             ->addColumn("goal_description", "text")
             ->addTimeStamps()
             ->create();

        $data = [
            [
                "goal_key" => "point_limit_goal",
                "goal_short_name" => "point limit goal",
                "goal_short_description" => "this is the daily points limit",
                "goal_description" => '...'
            ],
            [
                "goal_key" => "body_weight_goal",
                "goal_short_name" => "body weight goal",
                "goal_short_description" => "this is a body weight goal",
                "goal_description" => '...'
            ],
        ];

        $this->table("goals")->insert($data)->saveData();

    }
}
