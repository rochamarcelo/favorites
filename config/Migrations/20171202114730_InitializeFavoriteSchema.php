<?php
use Migrations\AbstractMigration;

class InitializeFavoriteSchema extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('favorites', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'uuid')
            ->addColumn('user_id', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('foreign_key', 'string', [
                'default' => null,
                'limit' => 36,
                'null' => false,
            ])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('type', 'string', [
                'default' => 0,
                'limit' => 3,
                'null' => true,
            ])
            ->addColumn('position', 'integer', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex([
                'foreign_key', 'model', 'type', 'user_id',
            ], [
                'name' => 'UNIQUE_BY_USER',
                'unique' => true,
            ])
            ->create();
    }
}
