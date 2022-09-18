<?php

use League\Csv\Reader;

use Phinx\Seed\AbstractSeed;

class FoodRecordsSeeder extends AbstractSeed
{
    private $csv_data = [];

    public function init()
    {
        //load the CSV document from a file path
        // $csv = Reader::createFromPath(FILE_ROOT . "/data/food data.csv", 'r');
        // $csv->setHeaderOffset(0);
        // $records = $csv->getRecords();

        // foreach ($records as $record) {
        //     $this->csv_data[] = $record;
        // }

        // $food_records = $this->table("food_records");
        // $food_records->insert($this->csv_data)
        //              ->saveData(); 
    }

    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run()
    {

    }
}
