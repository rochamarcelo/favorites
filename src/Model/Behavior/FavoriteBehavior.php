<?php
/**
 * Copyright 2009-2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Favorites\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Exception;

/**
 * Favorite behavior
 */
class FavoriteBehavior extends Behavior
{
    public $favoriteTypes = [];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'favoriteClass' => 'CakeDC/Favorites.Favorites',
		'favoriteAlias' => 'Favorites',
		'foreignKey' => 'foreign_key',
		'counterCache' => 'favorite_count',
    ];
    
    /**
     * Init the base property favoriteTypes with config data.
     * 
     * @return void
     */
    protected function _initFavoriteTypes()
    {
        $types = (array)Configure::read('Favorites.types');
		$this->favoriteTypes = [];
		foreach ($types as $key => $type) {
			$this->favoriteTypes[$key] = is_array($type) ? $type : array('model' => $type);
			if (empty($this->favoriteTypes[$key]['limit'])) {
				$this->favoriteTypes[$key]['limit'] = null;
			}
		}
    }

    /**
     * Constructor hook method.
     *
     * Implement this method to avoid having to overwrite
     * the constructor and call parent.
     *
     * @param array $config The configuration settings provided to this behavior.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $favoriteClass = $this->getConfig('favoriteClass');
		$favoriteAlias = $this->getConfig('favoriteAlias');
		$alias = $this->getTable()->getAlias();
		
		$this->getTable()->hasMany('Favorites', [
		    'className' => $favoriteClass,
			'foreignKey' => $this->getConfig('foreignKey'),
			'conditions' => [$favoriteAlias . '.model' => $alias],
			'fields' => '',
			'dependent' => true,
	    ])->belongsTo($alias, [
        	'foreignKey' => $this->getConfig('foreignKey'),
	    ]);

	    if (!$this->getTable()->behaviors()->has('CounterCache')) {
	        $this->getTable()->addBehavior('CounterCache');
	    }

	    $this->getTable()->behaviors()->get('CounterCache')->setConfig(
	        $favoriteAlias,
	        $this->getConfig('counterCache')
	    );
	    
        $this->_initFavoriteTypes();
    }
    
    /**
     * Save a favorite for a user - Checks for existing identical favorite first
     *
     * @param mixed $userId Id of the user.
     * @param string $modelName Name of model
     * @param string $type favorite type
     * @param mixed $foreignKey foreignKey
     * @throws Exception When it is impossible to save the favorite
     * @return boolean success of save Returns true if the favorite record already exists.
     */
	public function saveFavorite($userId, $modelName, $type, $foreignKey) 
	{
	    $table = $this->getTable();
	    $entity = $table->Favorites->newEntity([
            'user_id' => $userId,
		    'model' => $modelName,
		    'type' => $type,
		    'foreign_key' => $foreignKey,
		    'position' => 1
        ]);
		if (method_exists($table, 'beforeSaveFavorite')) {
			$result = $table->beforeSaveFavorite($entity);
			if (!$result) {
				throw new Exception(__d('favorites', 'Operation is not allowed'));
			}
		}
		$data = [
            'user_id' => $userId,
		    'model' => $modelName,
		    'type' => $type,
		    'foreign_key' => $foreignKey
        ];

		if ($table->Favorites->exists($data)) {
			throw new Exception(__d('favorites', 'Already added.'));
		}

		if (array_key_exists($type, $this->favoriteTypes) && !is_null($this->favoriteTypes[$type]['limit'])) {
			$currentCount = $table->Favorites->find()->where([
				$table->Favorites->aliasField('user_id') => $userId,
				$table->Favorites->aliasField('type') => $type
			])->count();
			if ($currentCount >= $this->favoriteTypes[$type]['limit']) {
				throw new Exception(
				    sprintf(
					    __d('favorites', 'You cannot add more than %s items to this list'),
					    $this->favoriteTypes[$type]['limit']
				    )
			    );
			}
		}

        $entity = $table->Favorites->newEntity($data);
        $entity->position = $this->_getNextPosition($entity);

		$result = $table->Favorites->save($entity);
		
		if ($result && method_exists($table, 'afterSaveFavorite')) {
			$result = $table->afterSaveFavorite($result);
		}

		return $result;
	}
	
	/**
     * Get the next value for the order field based on the user and model
     *
     * @param \Cake\Datasource\EntityInterface $entity
     * @return int
     */
	protected function _getNextPosition(\Cake\Datasource\EntityInterface $entity) {
		$position = 0;
		$table = $this->getTable();
		
		$maxPosition = $table->Favorites->find()->where([
		    $table->Favorites->aliasField('user_id') => $entity->user_id,
		    $table->Favorites->aliasField('model') => $entity->model,
			$table->Favorites->aliasField('type') => $entity->type
		])->order([
		    $table->Favorites->aliasField('position') => 'DESC'
	    ])->first();

	    if (isset($maxPosition['position'])) {
	        $position = $maxPosition['position'] + 1;
	    }

		return $position;
	}
}
