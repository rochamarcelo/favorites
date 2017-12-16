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
namespace CakeDC\Favorites\Model;

use App\Model\AppModel;
use Cake\Core\Configure;



/**
 * Favorite Model
 *
 * @package favorites
 * @subpackage favorites.models
 */
class Favorite extends AppModel {

/**
 * Categories for list options. Restricts types of favorites fetched when making lists.
 *
 * @var array
 */
	protected $_listCategories = array(
		'Book'
	);

/**
 * Additional Find types to be used with find($type);
 *
 * @var array
 */
	public $findMethods = array(
		'favorite' => true
	);

/**
 * Constructor
 *
 * @param mixed $id Model ID
 * @param string $table Table name
 * @param string $ds Datasource
 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);
		$categories = Configure::read('Favorites.modelCategories');
		if (!empty($categories)) {
			$this->_listCategories = (array)$categories;
		}
	}




/**
 * Returns the search data
 *
 * @param string
 * @param array
 * @param array
 * @return
 */
	protected function _findFavorite($state, $query, $results = array()) {
		if ($state == 'before') {
			$this->Behaviors->attach('Containable', array('autoFields' => false));
			$results = $query;
			$options = array();
			if (!empty($query['options'])) {
				$options = $query['options'];
			}
			if (!empty($query['favoriteType'])) {
				$options['type'] = $query['favoriteType'];
			}
			$userId = $query['userId'];
			$default = array(
			'conditions' => array(
				$this->alias . '.user_id' => $userId,
				$this->alias . '.type' => $options['type'],
				$this->alias . '.model' => $this->_getSupported('types', $options)),
			'order' => $this->alias . '.position ASC',
			'contain' => $this->_getSupported('contain', $options));
			$results = Set::merge($default, $query);
			if (isset($query['operation']) && $query['operation'] == 'count') {
				$results['fields'] = array('count(*)');
			}
			return $results;
		} elseif ($state == 'after') {
			if (isset($query['operation']) && $query['operation'] == 'count') {
				if (isset($query['group']) && is_array($query['group']) && !empty($query['group'])) {
					return count($results);
				}
				return $results[0][0]['count(*)'];
			}
			return $results;
		}
	}

/**
 * Customized paginateCount method
 *
 * @param array
 * @param integer
 * @param array
 * @return
 */
	public function paginateCount($conditions = array(), $recursive = 0, $extra = array()) {
		$parameters = compact('conditions');
		if ($recursive != $this->recursive) {
			$parameters['recursive'] = $recursive;
		}
		if (isset($extra['type']) && isset($this->findMethods[$extra['type']])) {
			$extra['operation'] = 'count';
			return $this->find($extra['type'], array_merge($parameters, $extra));
		} else {
			return $this->find('count', array_merge($parameters, $extra));
		}
	}

/**
 * Get Supported
 *
 *  - types - list of models associated
 *  - contain - list of models and association we may get
 *
 * @param $type
 * @param $options
 * @return unknown_type
 */
	protected function _getSupported($type, $options = array()) {
		$assocs = array_keys($this->belongsTo);
		$allTypes = $this->_listCategories;
		if (!empty($options['types'])) {
			$allTypes = array_merge($allTypes, $options['types']);
			$assocs = array_merge($assocs, $options['types']);
		}
		$types = array();
		$contain = array();
		foreach ($assocs as $assoc) {
			if (isset($allTypes[$assoc])) {
				$types[$assoc] = $allTypes[$assoc];
				$contain[$assoc] = $allTypes[$assoc];
			} elseif (in_array($assoc, $allTypes) && is_numeric(array_search($assoc, $allTypes))) {
				$types[$assoc] = $assoc;
				$contain[] = $assoc;
			}
		}
		if ($type == 'types') {
			return array_keys($types);
		} else {
			return $contain;
		}
	}

/**
 * Delete with calling model callbacks
 *
 * @param $type
 * @param $options
 * @return boolean
 */
	public function deleteRecord($id) {
		$record = $this->read(null, $id);
		if (empty($record)) {
			return true;
		}
		$record = $record[$this->alias];
		$model = $record['model'];
		$Model = ClassRegistry::init($model);
		$result = $this->delete($id);
		if ($result) {
			if (method_exists($Model, 'afterDeleteFavorite')) {
				$result = $Model->afterDeleteFavorite(array('id' => $record['foreign_key'], 'userId' => $record['user_id'], 'model' => $model, 'type' => $record['type']));
			}
			return $result;
		}
		return $result;
	}

}

