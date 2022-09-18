<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class PlansMigration extends AbstractMigration
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
        $this->table("plans")
             ->addColumn("plan_key", "text")
             ->addColumn("plan_short_name", "text")
             ->addColumn("plan_short_description", "text")
             ->addColumn("plan_description", "text")
             ->addTimeStamps()
             ->create();

        $data = [
            [
                "plan_key" => "green_plan",
                "plan_short_name" => "green plan",
                "plan_short_description" => "this is the green plan",
                "plan_description" => '...'
            ],
            [
                "plan_key" => "blue_plan",
                "plan_short_name" => "blue plan",
                "plan_short_description" => "this is the blue plan",
                "plan_description" => '...'
            ],
            [
                "plan_key" => "purple_plan",
                "plan_short_name" => "purple plan",
                "plan_short_description" => "this is the purple plan",
                "plan_description" => '...'
            ]
        ];

        $this->table("plans")->insert($data)->saveData();

    }
}
