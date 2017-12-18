<?php
namespace CakeDC\Favorites\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakeDC\Favorites\Model\Table\FavoritesTable;
use CakeDC\Favorites\Test\App\Model\Table\FavoriteArticlesTable;
use CakeDC\Favorites\Test\App\Model\Table\FavoriteUsersTable;

/**
 * CakeDC\Favorites\Model\Table\FavoritesTable Test Case
 */
class FavoritesTableTest extends TestCase
{

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
    public $Users;

    /**
     * @var \CakeDC\Favorites\App\Model\Table\FavoriteArticlesTable
     */
    public $Articles;
    
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.cake_d_c/favorites.favorites',
        'core.articles',
		'core.authors'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Favorites') ? [] : ['className' => FavoritesTable::class];
        $this->Favorites = TableRegistry::get('Favorites', $config);

        $config = TableRegistry::exists('FavoriteArticles') ? [] : ['className' => FavoriteArticlesTable::class];
        $this->Articles = TableRegistry::get('FavoriteArticles', $config);
        
        $config = TableRegistry::exists('FavoriteUsers') ? [] : ['className' => FavoriteUsersTable::class];
        $this->Users = TableRegistry::get('FavoriteUsers', $config);
        
        Configure::write('Favorites.modelCategories', array('FavoriteArticles'));
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Favorites);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        Configure::write('Favorites.modelCategories', array('FavoriteArticles'));
        $hasManyFavorite = $this->Articles->associations()->get('Favorites');
        $this->assertInstanceOf('\Cake\ORM\Association\HasMany', $hasManyFavorite);

        $this->assertEquals('CakeDC/Favorites.Favorites', $hasManyFavorite->className());
        $this->assertEquals(['Favorites.model' => 'FavoriteArticles'], $hasManyFavorite->getConditions());
        
        $belongsTo = $this->Articles->Favorites->associations()->get('FavoriteArticles');
        $this->assertInstanceOf('Cake\ORM\Association\BelongsTo', $belongsTo);
        
        $this->assertTrue($this->Articles->behaviors()->has('CounterCache'));
        $this->assertEquals(['Favorites' => 'favorite_count'], $this->Articles->behaviors()->get('CounterCache')->getConfig());
    }

    /**
     * Test moving favorites.
     *
     * @return void
     */
	public function testMove() 
	{
		$entity1 = $this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 1);
		$entity2 = $this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 2);
		$entity3 = $this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 3);
		$entity4 = $this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 4);

		$this->Articles->Favorites->move($entity4['id'], 'up');
		$new = $this->Favorites->get($entity4['id']);

		$this->assertEquals($new['position'], $entity4['position'] - 1);
		$this->Articles->Favorites->move($entity4['id'], 'down');
		$new = $this->Favorites->get($entity4['id']);
		$this->assertEquals($new['position'], $entity4['position']);
    
        //Entity 1
		$this->Articles->Favorites->move($entity1['id'], 'up');
		$new = $this->Favorites->get($entity1['id']);
		$this->assertEquals($new['position'], $entity1['position']);

		$this->Articles->Favorites->move($entity1['id'], 'down');
		$new = $this->Favorites->get($entity1['id']);
		$this->assertEquals($new['position'], $entity1['position'] + 1);
        
        //Entity 2
		$new = $this->Favorites->get($entity2['id']);
		$this->assertEquals($new['position'], 0);

		$positions = $this->Favorites->find('all', array(
			'order' => 'Favorites.position ASC',
			'conditions' => array('Favorites.user_id' => 1)
		))->all()->extract('position')->toArray();
		$this->assertEquals($positions, array(0,1,2,3));
	}
	
	/**
     * test get favorites on Favorite model
     *
     * @return void
     */
	public function testGetFavorites() 
	{
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 1);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 2);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 3);

		$result = $this->Articles->Favorites->getFavorites(1, ['types' => ['FavoriteArticles'], 'type' => 'default']);
		$this->assertEquals(count($result), 3);
		$this->assertTrue(isset($result[0]['id']));
		$this->assertTrue(isset($result[1]['id']));
		$this->assertTrue(isset($result[2]['id']));
	}
	
	/**
     * test get all favorites on Favorite model
     *
     * @return void
     */
	public function testGetAllFavorites() 
	{
		Configure::write('Favorites.types', array('type' => 'Type', 'anothertype' => 'AnotherType'));
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'type', 1);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'type', 2);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'anothertype', 3);
		$result = $this->Favorites->getAllFavorites(1);
		$this->assertEquals(count($result), 2);
		$this->assertEquals(count($result['type']), 2);
		$this->assertEquals(count($result['anothertype']), 1);
		$this->assertEquals(array(1, 2), array_values($result['type']));
		$this->assertEquals(array(3), array_values($result['anothertype']));
	}
	
	/**
     * test get favorites with extra types (models and associations)
     *
     * @return void
     */
	public function testGetFavoritesWithExtraTypes() 
	{
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 1);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 2);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 3);

		$result = $this->Articles->Favorites->getFavorites(1, array('types' => array('FavoriteArticles'), 'type' => 'default'));
		$this->assertEquals(count($result), 3);
		$this->assertTrue(isset($result[0]['id']));
		$this->assertFalse(isset($result[0]['favorite_user']));
		$this->assertTrue(isset($result[1]['id']));
		$this->assertFalse(isset($result[1]['favorite_user']));
		$this->assertTrue(isset($result[2]['id']));
		$this->assertFalse(isset($result[2]['favorite_user']));

		$result = $this->Articles->Favorites->getFavorites(1, [
			'types' => ['FavoriteArticles' => 'FavoriteUsers'],
			'type' => 'default'
		]);
		$this->assertEquals(count($result), 3);

		$this->assertTrue(isset($result[0]['id']));
		$this->assertTrue(isset($result[0]['favorite_article']));
		$this->assertTrue(isset($result[0]['favorite_article']['favorite_user']));
		$this->assertTrue(isset($result[1]['id']));
		$this->assertTrue(isset($result[1]['favorite_article']));
		$this->assertTrue(isset($result[1]['favorite_article']['favorite_user']));
		$this->assertTrue(isset($result[2]['id']));
		$this->assertTrue(isset($result[2]['favorite_article']));
		$this->assertTrue(isset($result[2]['favorite_article']['favorite_user']));
	}
	
	/**
     * Test that getByType behaves as expected
     *
     * @return void
     */
	public function testGetByType() 
	{
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 1);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 2);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 3);
		$result = $this->Articles->Favorites->getByType(1, array('types' => array('FavoriteArticles'), 'type' => 'default'));

		$this->assertEquals(count($result['FavoriteArticles']), 3);
	}
	
	/**
     * undocumented function
     *
     * @return void
     */
	public function testTypeCounts() 
	{
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 1);
		$this->Articles->saveFavorite(1, 'FavoriteOthers', 'default', 2);
		$this->Articles->saveFavorite(1, 'FavoriteOthers', 'default', 3);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 3);

		$this->Articles->Favorites->hasMany('FavoriteOthers', ['className' => 'FavoriteArticles']);
		$this->Articles->Favorites->hasMany('OtherModels', ['className' => 'FavoriteArticles']);
		
		$results = $this->Articles->Favorites->typeCounts(1, ['types' => ['FavoriteArticles', 'FavoriteOthers']]);

		$this->assertEquals($results['FavoriteArticles'], 2);
		$this->assertEquals($results['FavoriteOthers'], 2);

		$results = $this->Articles->Favorites->typeCounts(1, ['types' => ['OtherModels', 'FavoriteArticles', 'FavoriteOthers']]);

		$this->assertEquals($results['FavoriteArticles'], 2);
		$this->assertEquals($results['FavoriteOthers'], 2);
		$this->assertEquals($results['OtherModels'], 0);
	}
	
	/**
     * Test getFavoriteId method
     *
     * @return void
     */
	public function testGetFavoriteId() 
	{
		$result = $this->Articles->saveFavorite(1, 'FavoriteArticle', 'default', 1);
		$this->assertNotNull($result['id']);
		$result = $this->Articles->Favorites->getFavoriteId('FavoriteArticle', 'default', 1, 1);

		$this->assertFalse(empty($result));
		$this->assertTrue(is_scalar($result));

		$result = $this->Articles->Favorites->getFavoriteId('FavoriteArticle', 'default', 1, 2);
		$this->assertFalse($result, 'User with no favorites, is being shown as having one. %s');
	}
	
	/**
     * test checking if a user has favorited something.
     *
     * @return void
     */
	public function testIsFavorited() 
	{
		$result = $this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 1);
		$this->assertNotNull($result['id']);
		$result = $this->Articles->Favorites->isFavorited('FavoriteArticles', 'default', 1, 1);
		$this->assertTrue($result, 'Return is wrong, should be true. %s');

		$result = $this->Articles->Favorites->isFavorited('FavoriteArticle', 'default', 1, 2);
		$this->assertFalse($result, 'User with no favorites, is being shown as having one. %s');
	}
	
	/**
     * testGetFavoriteLists
     *
     * @return void
     */
	public function testGetFavoriteLists() 
	{
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 1);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 2);
		$this->Articles->saveFavorite(1, 'FavoriteArticles', 'default', 3);
		$result = $this->Articles->Favorites->getFavoriteLists('default', 1);
		$expected = [
			'FavoriteArticles' => [
		        ['id' => '1', 'title' => 'First Article'],
		        ['id' => '2', 'title' => 'Second Article'],
		        ['id' => '3', 'title' => 'Third Article']
            ]
        ];
		$this->assertEquals($result, $expected);
	}
	
	/**
	 * Test findOwned method
	 * 
	 * @return void
	 */
	public function testFindOwned()
	{
		$actual = $this->Articles->Favorites->find('owned', [
			'user_id' => 2
		])->find('list')->toArray();
		$expected = [
			'a62e34eb-084b-4ff8-b8b9-754c581ecab2' => 'a62e34eb-084b-4ff8-b8b9-754c581ecab2',
			'a63e34eb-084b-4ff8-b8b9-754c581ecab2' => 'a63e34eb-084b-4ff8-b8b9-754c581ecab2'
		];
		$this->assertEquals($expected, $actual);
	}
	
	/**
	 * Test findOwned method, user does not has favorites
	 * 
	 * @return void
	 */
	public function testFindOwnedEmpty()
	{
		$actual = $this->Articles->Favorites->find('owned', [
			'user_id' => 1
		])->find('list')->toArray();
		$expected = [];
		$this->assertEquals($expected, $actual);
	}
}
