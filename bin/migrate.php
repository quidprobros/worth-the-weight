<?php
namespace App;

use App\DB_DSN;
use Illuminate\Database\Capsule\Manager as Capsule;
use Phinx\Migration\AbstractMigration;

const FILE_ROOT = __DIR__ . "/..";

require_once FILE_ROOT . "/vendor/autoload.php";

Config::init();


class Migration extends AbstractMigration {
    /** @var \Illuminate\Database\Capsule\Manager $capsule */
    public $capsule;
    /** @var \Illuminate\Database\Schema\Builder $capsule */
    public $schema;

    public function init()
    {
        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            "driver" => App\DB_DRIVER,
            "database" => App\DB_DATABASE,
        ]);

        $this->capsule->bootEloquent();
        $this->capsule->setAsGlobal();
        $this->schema = $this->capsule->schema();
    }

    public function up()
    {
        $this->schema->create('widgets', function (Illuminate\Database\Schema\Blueprint $table){
            // Auto-increment id
            $table->increments('id');
            $table->integer('serial_number');
            $table->string('name');
            // Required for Eloquent's created_at and updated_at columns
            $table->timestamps();
        });
    }
}
