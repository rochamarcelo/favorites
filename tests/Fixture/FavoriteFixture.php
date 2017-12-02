<?php
/**
 * Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Favorites\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;


/**
 * Favorite Fixture
 *
 * @package favorites
 * @subpackage favorites.tests.fixtures
 */
class FavoriteFixture extends TestFixture {

/**
 * Table name
 *
 * @var string $useTable
 */
	public $table = 'favorites';

/**
 * Fields definition
 *
 * @var array $fields
 */
	public $fields = array(
		'id' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 36],
		'user_id' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 36],
		'foreign_key' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 36],
		'model' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 64],
		'type' => ['type' => 'string', 'null' => false, 'default' => null, 'length' => 32],
		'position' => ['type' => 'integer', 'null' => true, 'default' => '0', 'length' => 3],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']], 'UNIQUE_FAVORITES' => ['type' => 'unique', 'columns' => ['foreign_key', 'model', 'type', 'user_id']]]
	);

/**
 * record set
 *
 * @var array $records
 */
	public $records = array();
}
