<?php
/**
 * Copyright 2009-2014, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2014, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * favorites Schema
 *
 * @package favorites
 * @subpackage favorites.config.schema
 */
class favoritesSchema extends CakeSchema {
	var $name = 'favorites';

	function before($event = array()) {
		return true;
	}

	function after($event = array()) {
	}

	var $favorites = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'foreign_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
		'model' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 64),
		'type' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 32),
		'position' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 3),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'UNIQUE_FAVORITES' => array('column' => array('foreign_key', 'model', 'type', 'user_id'), 'unique' => 1))
	);
}
