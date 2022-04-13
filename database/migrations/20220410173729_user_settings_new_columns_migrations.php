<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UserSettingsNewColumnsMigrations extends AbstractMigration
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
        $this->table("users_settings")
             ->addColumn("weight_unit_id", "integer", [
                 'signed' => false,
                 'null' => true,
             ])
             ->addColumn("height_unit_id", "integer", [
                 'signed' => false,
                 'null' => true,
             ])
             ->addForeignKey('weight_unit_id', 'measurement_unit_type', 'id', [
             ])
             ->addForeignKey('height_unit_id', 'measurement_unit_type', 'id', [
             ])
             ->update();
    }
}
