<?php

namespace CakeDC\Favorites\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use CakeDC\Favorites\Test\App\Model\Table\FavoriteArticlesTable;
use CakeDC\Favorites\Test\App\Model\Table\FavoriteUsersTable;

/**
 * Favorites Controller
 */
class FavoritesController extends AppController 
{

	/**
	 * Models to load
	 *
	 * @var array
	 */
	public $uses = array('Favorites.Favorite');

	/**
	 * Allowed Types of things to be favorited
	 * Maps types to models so you don't have to expose model names if you don't want to.
	 *
	 * @var array
	 */
	public $favoriteTypes = array();
	
	/**
	 * @var string 
	 */
	public $model;


	/**
     * Called before the controller action.
     * We use it to:
     *  Deny access to all actions.
     *  Define the property favoriteTypes based on config 'Favorites.types
     *
     * @param \Cake\Event\Event $event An Event instance
     * @return \Cake\Http\Response|null
     * @link https://book.cakephp.org/3.0/en/controllers.html#request-life-cycle-callbacks
     */
    public function beforeFilter(Event $event)
    {
		parent::beforeFilter($event);
		$this->Auth->deny($this->Auth->allowedActions);
		$types = Configure::read('Favorites.types');
		if (!empty($types)) {
			$this->favoriteTypes = array();
			// Keep only key / values (type / model)
			foreach ((array) $types as $key => $type) {
				if (is_string($type)) {
					$this->favoriteTypes[$key] = $type;
				} elseif (is_array($type) && array_key_exists('model', $type)) {
					$this->favoriteTypes[$key] = $type['model'];
				}
			}
		}

		$config = TableRegistry::exists('FavoriteArticles') ? [] : ['className' => FavoriteArticlesTable::class];
        TableRegistry::get('FavoriteArticles', $config);
        
        $config = TableRegistry::exists('FavoriteUsers') ? [] : ['className' => FavoriteUsersTable::class];
        TableRegistry::get('FavoriteUsers', $config);
	}

	/**
	 * Create a new favorite for the specific type.
	 *
	 * @param string $type
	 * @param string $foreignKey
	 * @return \Cake\Http\Response|null
	 */
	public function add($type = null, $foreignKey = null) 
	{
		$status = 'error';
		if (!isset($this->favoriteTypes[$type])) {
			$message = __d('favorites', 'Invalid object type.');
		} else {
			$Subject = $this->loadModel($this->favoriteTypes[$type]);
			$this->model = $type;
			if (!$Subject->exists(['id' => $foreignKey])) {
				$message = __d('favorites', 'Invalid identifier');
			} else {
				try {
					$result = $Subject->saveFavorite($this->Auth->user('id'), $Subject->getAlias(), $type, $foreignKey);
					if ($result) {
						$status = 'success';
						$message = __d('favorites', 'Record was successfully added');
					} else {
						$message = __d('favorites', 'Record was not added.');
					}
				} catch (\Exception $e) {
					$message = __d('favorites', 'Record was not added.') . ' ' . $e->getMessage();
				}
			}
		}
		$this->set(compact('status', 'message', 'type', 'foreignKey'));
		if ($this->request->is('json')) {
			return $this->render();
		} else {
			return $this->redirect($this->referer());
		}
	}

	/**
	 * Delete a favorite by Id
	 *
	 * @param mixed $id Id of favorite to delete.
	 * @return \Cake\Http\Response
	 */
	public function delete($id = null) 
	{
		$favorite = $this->Favorites->findById($id)->find('owned', [
			'user_id' => $this->Auth->user('id')
		])->first();

		$message = __d('favorites', 'Unable to delete favorite, please try again');
		if ($favorite && $this->Favorites->delete($favorite)) {
			$message = __d('favorites', 'Record removed from list');
			$this->Flash->success($message);
		} else {
			$this->Flash->error($message);
		}

		return $this->redirect($this->referer(), -999);
	}

	/**
	 * Get a list of favorites for a User by type.
	 *
	 * @param string $type
	 * @return void
	 */
	public function shortList($type = null) 
	{
		$type = Inflector::underscore($type);
		if (!isset($this->favoriteTypes[$type])) {
			$this->Flash->error(__d('favorites', 'Invalid object type.'));
			return;
		}
		$userId = $this->Auth->user('id');
		$favorites = $this->Favorites->getByType($userId, compact('type'));
		$this->set(compact('favorites', 'type'));
		$this->render('list');
	}

	/**
	 * Get all favorites for a specific user and $type
	 *
	 * @param string $type Type of favorites to get
	 * @return void
	 */
	public function listAll($type = null) 
	{
		$type = strtolower($type);
		if (!isset($this->favoriteTypes[$type])) {
			$this->Flash->error(__d('favorites', 'Invalid object type.'));
			return;
		}
		$userId = $this->Auth->user('id');
		$favorites = $this->Favorites->getByType($userId, array('limit' => 100, 'type' => $type));
		$this->set(compact('favorites', 'type'));
		$this->render('list');
	}

	/**
	 * Move a favorite up or down a position.
	 *
	 * @param mixed $id Id of favorite to move.
	 * @param string $direction direction to move (only up and down are accepted)
	 * @return \Cake\Http\Response|null
	 */
	public function move($id = null, $direction = 'up') 
	{
		$status = 'error';
		$direction = strtolower($direction);
		$favorite = $this->Favorites->findById($id)->find('owned', [
			'user_id' => $this->Auth->user('id')
		])->first();
		
		if (!$favorite) {
			$message = __d('favorites', 'Record not found.');
		} elseif ($direction !== 'up' && $direction !== 'down') {
			$message = __d('favorites', 'Invalid direction');
		} elseif ($this->Favorites->move($id, $direction)) {
			$status = 'success';
			$message = __d('favorites', 'Favorite positions updated.');
		} else {
			$message = __d('favorites', 'Unable to change favorite position, please try again');
		}

		$this->set(compact('status', 'message'));
		return $this->redirect($this->referer());
	}

	/**
	 * Overload Redirect.  Many actions are invoked via Xhr, most of these
	 * require a list of current favorites to be returned.
	 *
	 * @param string|array $url A string or array-based URL pointing to another location within the app,
     *     or an absolute URL
     * @param int $status HTTP status code (eg: 301)
     * @return \Cake\Http\Response|null
	 */
	public function redirect($url, $code = null) 
	{
		if ($code == -999) {
			return parent::redirect($url, null);
		}

		if (!empty($this->viewVars['authMessage']) && $this->request->is('json')) {
			$this->RequestHandler->renderAs($this, 'json');
			$this->set('message', $this->viewVars['authMessage']);
			$this->set('status', 'error');
			return $this->render('add');
		}
		
		if ($this->request->is('ajax') || $this->request->is('json')) {
			return $this->short_list($this->model);
		} elseif (isset($this->viewVars['status']) && isset($this->viewVars['message'])) {
			$this->Flash->error($this->viewVars['message'], 'default', array(), $this->viewVars['status']);
		} elseif (!empty($this->viewVars['authMessage'])) {
			$this->Flash->error($this->viewVars['authMessage']);
		}

		return parent::redirect($url, $code);
	}
}
