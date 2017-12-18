<?php
namespace CakeDC\Favorites\Model\Table;

use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

/**
 * Favorites Model
 *
 * @property \CakeDC\Favorites\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \CakeDC\Favorites\Model\Entity\Favorite get($primaryKey, $options = [])
 * @method \CakeDC\Favorites\Model\Entity\Favorite newEntity($data = null, array $options = [])
 * @method \CakeDC\Favorites\Model\Entity\Favorite[] newEntities(array $data, array $options = [])
 * @method \CakeDC\Favorites\Model\Entity\Favorite|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \CakeDC\Favorites\Model\Entity\Favorite patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \CakeDC\Favorites\Model\Entity\Favorite[] patchEntities($entities, array $data, array $options = [])
 * @method \CakeDC\Favorites\Model\Entity\Favorite findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FavoritesTable extends Table
{
    /**
     * Categories for list options. Restricts types of favorites fetched when making lists.
     *
     * @var array
     */
	protected $_listCategories = array(
		'Book'
	);
	

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('favorites');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        
        $categories = Configure::read('Favorites.modelCategories');
		if (!empty($categories)) {
			$this->_listCategories = (array)$categories;
		}
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->uuid('id')
            ->allowEmpty('id', 'create');

        $validator
            ->scalar('foreign_key')
            ->maxLength('foreign_key', 36)
            ->requirePresence('foreign_key', 'create')
            ->notEmpty('foreign_key');

        $validator
            ->scalar('model')
            ->maxLength('model', 64)
            ->requirePresence('model', 'create')
            ->notEmpty('model');

        $validator
            ->scalar('type')
            ->maxLength('type', 32)
            ->requirePresence('type', 'create')
            ->notEmpty('type');

        $validator
            ->integer('position')
            ->allowEmpty('position');

        return $validator;
    }
    
    /**
     * Move a favorite in the direction indicated. Will update the position record for all other favorites
     *
     * @param mixed $id Id of favorite to move.
     * @param string $direction Direction to move 'up' or 'down'.
     * @return mixed boolean on error and entity onSuccess
     */
	public function move($id, $direction = 'up') 
	{
		$subject = $this->get($id);
		if ($direction == 'up') {
			$modifier = '+1';
			$targetValue = $subject['position'] - 1;
			$subject['position'] -= 1;
		} elseif ($direction == 'down') {
			$modifier = '-1';
			$targetValue = $subject['position'] + 1;
			$subject['position'] += 1;
		}
		if ($subject['position'] < 0) {
			$subject['position'] = 0;
		}

		$this->updateAll(
			[
			    new QueryExpression("position = position {$modifier}")
			],
			[
				$this->aliasField('position') => $targetValue,
				$this->aliasField('user_id') => $subject['user_id'],
				$this->aliasField('model') => $subject['model']
			]
		);

		return $this->save($subject);
	}
	
	/**
     * Helper method for getByType and getFavoriteLists
     *
     * @return array
     */
	public function getFavorites($userId, $options) 
	{
		$models = $this->_getSupported('types', $options);
		$conditions = [
			$this->aliasField('user_id') => $userId,
			$this->alias() . '.type IN' => (array)$options['type'],
		];
		if ($models) {
		    $conditions[$this->getAlias() . '.model IN'] =  $models;
		}
		return $this->find('all',[
			'contain' => $this->_getSupported('contain', $options)
		])->where($conditions)->orderAsc(
		    $this->aliasField('position')
	    )->all()->toArray();
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
	protected function _getSupported($type, $options = array()) 
	{
		$assocs = collection($this->associations()->type('BelongsTo'))->map(function ($item) {
		    return $item->getAlias();
	    })->toArray();

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
     * Returns all the favorites a given User has added
     *
     * @param string $id User id
     * @param array $options query options to be passed to the find method
     * @return array Favorite list with the favorites keys
     * Each key will have a value like the following:
     * array(
     * 'favorite-id1' => 'foreign-key1',
     * 'favorite-id2' => 'foreign-key2')
     * @access public
     */
	public function getAllFavorites($id = null, $options = array()) 
	{
		$keys = array_keys(Configure::read('Favorites.types'));
		$result = array_fill_keys($keys, []);
		if (!is_null($id)) {
			$list = $this->getFavorites($id, ['type' => $keys] + $options);
			$list = Hash::combine($list, '{n}.id', '{n}.foreign_key', '{n}.type');
			$result = array_merge($result, $list);
		}
		return $result;
	}
	
	/**
     * Get all the favorites for a user by type
     * Works similar to getFavoriteLists() but returns full associated model,
     * Making display more flexible.
     *
     * @param mixed $userId Id of user
     * @param array $options Options to use for getting favorites
     * @return array Array of favorites for the user keyed by type.
     */
	public function getByType($userId, $options = array()) 
	{
		$_defaults = ['limit' => 16, 'type' => 'default'];
		$options = array_merge($_defaults, $options);
		$limit = $options['limit'];

		$favorites = $this->getFavorites($userId, $options);
		$out = $categoryCounts = array();
		foreach ($favorites as $item) {
			$category = $item['model'];
			if (!isset($out[$category])) {
				$out[$category] = array();
				$categoryCounts[$category] = 0;
			}
			if ($categoryCounts[$category] >= $limit) {
				continue;
			}
			$out[$category][] = $item;
			$categoryCounts[$category]++;
		}
		return $out;
	}
	
	/**
     * Get The total number of favorites per category for a User.
     *
     * @param uuid $userId of User
     * @param array $options Options to use for the method.
     * @return array Array of counts keyed by model type
     * @todo add types suport here
     */
	public function typeCounts($userId, $options = array()) 
	{
		$options['types'] = $this->_getSupported('types', $options);
		
		$query = $this->find('all', array(
			'conditions' => array(
				$this->getAlias() . '.model IN' => $options['types'],
				$this->aliasField('user_id') => $userId,
			),
			'group' => array($this->aliasField('model')),
			'recursive' => -1
		));
		$query = $query->select([
		    'count' => $query->func()->count('id'),
		    $this->aliasField('model')
	    ]);
		
		$counts = $query->all()->toArray();
		$out = array_combine($options['types'], array_fill(0, count($options['types']), 0));
		foreach ($counts as $count) {
			$type = $count['model'];
			$number = $count['count'];
			$out[$type] = $number;
		}
		return $out;
	}
	
	/**
     * Check if the current item in on favorites
     *
     * @param $modelName Name of model that $foreignKey belongs to.
     * @param string $type favorite type
     * @param $foreignKey Id of the record to check.
     * @param $userId Id of the user you are looking.
     * @return boolean
     */
	public function isFavorited($modelName, $type, $foreignKey, $userId) 
	{
		$result = $this->getFavoriteId($modelName, $type, $foreignKey, $userId);
		return ($result !== false);
	}
	
	/**
     * Get the id of the object matching the current item in favorites.
     *
     * @param $modelName Name of model that $foreignKey belongs to.
     * @param string $type favorite type
     * @param $foreignKey Id of the record to check.
     * @param $userId Id of the user you are looking.
     * @return mixed The id if the element was favorited, false otherwise
     */
	public function getFavoriteId($modelName, $type, $foreignKey, $userId) 
	{
		$favoriteId = false;

		$record = $this->find()->where([
			$this->aliasField('model') => $modelName,
			$this->aliasField('foreign_key') => $foreignKey,
			$this->aliasField('user_id') => $userId,
			$this->aliasField('type') => $type
		])->first();

		if (!empty($record)) {
			$favoriteId = $record[$this->getPrimaryKey()];
		}

		return $favoriteId;
	}/**
     * Get favorite list for a logged in user.
     *
     * @param mixed $userId Id of the user you want to make lists for.
     * @param int $limit Number of list items to get in each category (defaults to 100).
     * @return void
     */
	public function getFavoriteLists($type, $userId, $limit = 100) 
	{
	    $types = collection($this->associations()->type('BelongsTo'))->map(function ($item) {
		    return $item->getAlias();
	    })->toArray();
	    $types = array_unique($types);
	
		$listItems = $this->getFavorites($userId, ['type' => $type, 'types' => $types]);
		$list = array();
		$categoryCounts = array();
		foreach ($listItems as $item) {
			$category = $item['model'];
			if (!isset($list[$category])) {
				$list[$category] = array();
				$categoryCounts[$category] = 0;
			}
			if (!isset($this->{$category})) {
				continue;
			}
			if ($categoryCounts[$category] >= $limit) {
				continue;
			}
			$method = '__get' . $category . 'Item';
			if (method_exists($this, $method)) {
				$list[$category][] = $this->{$method}($item[$category]);
			} else {
				$idField = $this->{$category}->getPrimaryKey();
				$titleField = $this->{$category}->getDisplayField();
				$field = Inflector::underscore(
				    Inflector::singularize($this->{$category}->getAlias())
				);

				$list[$category][] = [
				    'id' => $item->get($field)[$idField], 
				    'title' => $item->get($field)[$titleField]
				];
			}
			$categoryCounts[$category]++;
		}
		return $list;
	}
	
	/**
	 * Set up a query to find only registry associated to the user (user_id)
	 *
	 * @param Query $query to be setup
	 * @param array $options containing 'user_id'
	 * @return Query
	 */
	public function findOwned(Query $query, array $options)
	{
		return $query->where([
			$this->aliasField('user_id') => $options['user_id']
		]);
	}
}
