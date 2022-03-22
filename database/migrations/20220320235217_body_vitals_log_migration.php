<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BodyVitalsLogMigration extends AbstractMigration
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
        $table = $this->table('body_vitals_log')
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('body_weight_id', 'integer')
            ->addTimeStamps()
            ->addForeignKey('body_weight_id', 'body_weight', 'id', [
            ])
            ->create();

    }
}
