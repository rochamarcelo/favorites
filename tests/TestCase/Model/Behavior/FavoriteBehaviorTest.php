<?php
namespace CakeDC\Favorites\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakeDC\Favorites\Model\Behavior\FavoriteBehavior;
use CakeDC\Favorites\Model\Table\FavoritesTable;
use CakeDC\Favorites\Test\App\Model\Table\FavoriteArticlesTable;
use CakeDC\Favorites\Test\App\Model\Table\FavoriteUsersTable;


/**
 * CakeDC\Favorites\Model\Behavior\FavoriteBehavior Test Case
 */
class FavoriteBehaviorTest extends TestCase
{

    /**
     * fixtures
     *
     * @var array
     */
	public $fixtures = [
        'plugin.cake_d_c/favorites.favorites',
        'core.articles',
		'core.authors'
    ];

    /**
     * Test subject
     *
     * @var \CakeDC\Favorites\Model\Table\FavoritesTable
     */
    public $Favorites;
    
    /**
     * Test subject
     *
     * @var \CakeDC\Favorites\Model\Table\FavoriteUsersTable
     */
    public $FavoriteUsers;

    /**
     * @var \CakeDC\Favorites\App\Model\Table\FavoriteArticlesTable
     */
    public $FavoriteArticles;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        
        Configure::write('Favorites.types', array('like' => 'FavoriteArticles', 'dislike' => 'FavoriteArticles'));
		Configure::write('Favorites.modelCategories', array('FavoriteArticles'));

        $config = TableRegistry::exists('Favorites') ? [] : ['className' => FavoritesTable::class];
        $this->Favorites = TableRegistry::get('Favorites', $config);

        $config = TableRegistry::exists('FavoriteArticles') ? [] : ['className' => FavoriteArticlesTable::class];
        $this->FavoriteArticles = TableRegistry::get('FavoriteArticles', $config);
        
        $config = TableRegistry::exists('FavoriteUsers') ? [] : ['className' => FavoriteUsersTable::class];
        $this->FavoriteUsers = TableRegistry::get('FavoriteUsers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Favorites, $this->FavoriteArticles, $this->FavoriteUsers);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $hasManyFavorite = $this->FavoriteArticles->associations()->get('Favorites');
        $this->assertInstanceOf('\Cake\ORM\Association\HasMany', $hasManyFavorite);

        $this->assertEquals('CakeDC/Favorites.Favorites', $hasManyFavorite->className());
        $this->assertEquals(['Favorites.model' => 'FavoriteArticles'], $hasManyFavorite->getConditions());
        
        $belongsTo = $this->FavoriteArticles->Favorites->associations()->get('FavoriteArticles');
        $this->assertInstanceOf('Cake\ORM\Association\BelongsTo', $belongsTo);
        
        $this->assertTrue($this->FavoriteArticles->behaviors()->has('CounterCache'));
        $this->assertEquals(['Favorites' => 'favorite_count'], $this->FavoriteArticles->behaviors()->get('CounterCache')->getConfig());

		$expected = [
			'like' => ['limit' => null, 'model' => 'FavoriteArticles'],
			'dislike' => ['limit' => null, 'model' => 'FavoriteArticles']
		];

		$this->assertEquals($this->FavoriteArticles->behaviors()->get('Favorite')->favoriteTypes, $expected);
    }
    
    /**
     * Test saving of favorites
     *
     * @return void
     */
	public function testSaveFavorite() 
	{
		$result = $this->FavoriteArticles->saveFavorite(1, 'FavoriteArticles', 'default', 1);

		$this->assertNotEmpty($result->id);
		$this->assertEmpty($result->getErrors());

		$this->assertEquals($result['user_id'], 1);
		$this->assertEquals($result['model'], 'FavoriteArticles');
		$this->assertEquals($result['foreign_key'], 1);
		$this->assertEquals($result['position'], 0);
		$oldId = $result['id'];

		//save twice will fail
		try {
			$result = $this->FavoriteArticles->saveFavorite(1, 'FavoriteArticles', 'default', 1);
			$this->fail('No Exception');
		} catch (\Exception $e) {
			$error = $e->getMessage();
			$this->assertEquals($error, 'Already added.');
		}

		$result = $this->Favorites->get($oldId);
		$this->assertEquals($result['user_id'], 1);
		$this->assertEquals($result['model'], 'FavoriteArticles');
		$this->assertEquals($result['foreign_key'], 1);
		$this->assertEquals($oldId, $result['id']);
	}
	
	/**
     * Test saving of favorites with a limit of favorites per user
     *
     * @return void
     */
	public function testSaveFavoriteWithLimit() 
	{
	    $this->FavoriteArticles->behaviors()->get('Favorite')->favoriteTypes = [
			'like' => ['limit' => 2, 'model' => 'FavoriteArticles'],
			'dislike' => ['limit' => null, 'model' => 'FavoriteArticles']
		];

		$result = $this->FavoriteArticles->saveFavorite(1, 'FavoriteArticles', 'like', 1);
		$this->assertInstanceOf('\Cake\Datasource\EntityInterface', $result);
		$result = $this->FavoriteArticles->saveFavorite(1, 'FavoriteArticles', 'like', 2);
		$this->assertInstanceOf('\Cake\Datasource\EntityInterface', $result);

		try {
			$this->FavoriteArticles->saveFavorite(1, 'FavoriteArticles', 'like', 3);
			$this->fail('No exception thrown when saving too many favorites');		
		} catch (\Exception $e) {
			$this->assertEquals($e->getMessage(), 'You cannot add more than 2 items to this list');
		}
		$result = $this->FavoriteArticles->saveFavorite(3, 'FavoriteArticles', 'like', 3);
		$this->assertInstanceOf('\Cake\Datasource\EntityInterface', $result);
	}
	
	/**
     * test that as favorites are added they appended to the end of the stack
     *
     * @return void
     */
	public function testIncrementingFavorites() 
	{
		$result = $this->FavoriteArticles->saveFavorite(1, 'FavoriteArticles', 'default', 1);
		$this->assertEquals($result['position'], 0);

		$result = $this->FavoriteArticles->saveFavorite(1, 'FavoriteArticles', 'default', 2);
		$this->assertEquals($result['position'], 1);

		$result = $this->FavoriteArticles->saveFavorite(1, 'FavoriteArticles', 'default', 12334);
		$this->assertEquals($result['position'], 2);

		$result = $this->FavoriteArticles->saveFavorite(22222, 'FavoriteArticles', 'default', 12334);
		$this->assertEquals($result['position'], 0);
	}
}
