<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MeasurementUnitMigration extends AbstractMigration
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
        $table = $this->table("measurement_unit_type")
                      ->addColumn("measurement_parameter", "text", [
                          "null" => false,
                      ])
                      ->addColumn("unit_name", "text", [
                          "null" => false
                      ])
                      ->addColumn("unit_name_singular", "text", [
                          "null" => false,
                      ])
                      ->addColumn("unit_abbreviation", "text", [
                          "null" => false
                      ])
                      ->addTimeStamps()
                      ->create();

        $data = [
            [
                "measurement_parameter" => "height",
                "unit_name" => "inches",
                "unit_name_singular" => "inch",
                "unit_abbreviation" => "in"
            ],
            [
                "measurement_parameter" => "height",
                "unit_name" => "centimeters",
                "unit_name_singular" => "centimeter",
                "unit_abbreviation" => "cm"
            ],
            [
                "measurement_parameter" => "weight",
                "unit_name" => "pounds",
                "unit_name_singular" => "pound",
                "unit_abbreviation" => "lb"
            ],
            [
                "measurement_parameter" => "weight",
                "unit_name" => "kilograms",
                "unit_name_singular" => "kilogram",
                "unit_abbreviation" => "kg"
            ]


        ];

        $this->table("measurement_unit_type")->insert($data)->saveData();

    }
}
