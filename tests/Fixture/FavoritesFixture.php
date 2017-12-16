<?php
namespace CakeDC\Favorites\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * FavoritesFixture
 *
 */
class FavoritesFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'user_id' => ['type' => 'string', 'length' => 36, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'foreign_key' => ['type' => 'string', 'length' => 36, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'model' => ['type' => 'string', 'length' => 64, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'type' => ['type' => 'string', 'length' => 32, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'position' => ['type' => 'integer', 'length' => 3, 'unsigned' => false, 'null' => true, 'default' => '0', 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'UNIQUE_BY_USER' => ['type' => 'unique', 'columns' => ['foreign_key', 'model', 'type', 'user_id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 'a62e34eb-084b-4ff8-b8b9-754c581ecab2',
            'user_id' => '2',
            'foreign_key' => '1',
            'model' => 'FavoriteArticles',
            'type' => 'like',
            'position' => 2,
            'created' => '2017-12-02 20:15:03',
            'modified' => '2017-12-02 20:15:03'
        ],
        [
            'id' => 'a63e34eb-084b-4ff8-b8b9-754c581ecab2',
            'user_id' => '2',
            'foreign_key' => '3',
            'model' => 'FavoriteArticles',
            'type' => 'like',
            'position' => 1,
            'created' => '2017-12-02 20:17:03',
            'modified' => '2017-12-02 20:17:03'
        ],
    ];
}
