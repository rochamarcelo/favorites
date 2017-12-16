<?php
namespace CakeDC\Favorites\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestCase;
use CakeDC\Favorites\Controller\FavoritesController;

/**
 * CakeDC\Favorites\Controller\FavoritesController Test Case
 */
class FavoritesControllerTest extends IntegrationTestCase
{

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
     * Auto-detect if the HTTP middleware stack should be used.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('Favorites.types', array('like' => 'FavoriteArticles', 'dislike' => 'FavoriteArticles'));
		Configure::write('Favorites.modelCategories', array('FavoriteArticles'));
		
		$this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->useHttpServer(true);
        $this->configApplication(\CakeDC\Favorites\Test\App\Application::class, null);
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd()
    {
        $this->disableErrorHandlerMiddleware();
        $this->configRequest([
            'headers' => [
                'REFERER' => '/articles/index',
            ]
        ]);
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->enableRetainFlashMessages();
        $this->get('/favorites/favorites/add/like/2');
        $this->assertSession('Record was successfully added', 'Flash.flash.0.message');
        $this->assertRedirect('/articles/index');
    }
    
    /**
     * Test add method as json
     *
     * @return void
     */
    public function testAddJson()
    {
        $this->disableErrorHandlerMiddleware();
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'REFERER' => '/articles/index',
            ]
        ]);
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->get('/favorites/favorites/add/like/2');
        $this->assertResponseOk();
        $expected = [
            'message' => 'Record was successfully added',
            'status' => 'success',
            'type' => 'like',
            'foreignKey' => 2
        ];
        $actual = json_decode($this->_response->getBody(), true);
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Test add method as json, accessing twice the same type
     *
     * @return void
     */
    public function testAddDuplicatedFailJson()
    {
        $this->disableErrorHandlerMiddleware();
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'REFERER' => '/articles/index',
            ]
        ]);
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->get('/favorites/favorites/add/like/1');
        $this->assertResponseOk();
        $expected = [
            'message' => 'Record was not added. Already added.',
            'status' => 'error',
            'type' => 'like',
            'foreignKey' => 1
        ];
        $actual = json_decode($this->_response->getBody(), true);
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Test add method as json, has a registry for user_id, foreign_key and model but different type
     *
     * @return void
     */
    public function testAddDifferentType()
    {
        $this->disableErrorHandlerMiddleware();
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'REFERER' => '/articles/index',
            ]
        ]);
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->get('/favorites/favorites/add/dislike/1');
        $this->assertResponseOk();
        $expected = [
            'message' => 'Record was successfully added',
            'status' => 'success',
            'type' => 'dislike',
            'foreignKey' => 1
        ];
        $actual = json_decode($this->_response->getBody(), true);
        $this->assertEquals($expected, $actual);
    }
    
    /**
     * Test add method as json, using wrong type
     *
     * @return void
     */
    public function testAddWrongType()
    {
        $this->disableErrorHandlerMiddleware();
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'REFERER' => '/articles/index',
            ]
        ]);
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->get('/favorites/favorites/add/wrongtype/1');
        $this->assertResponseOk();
        $expected = [
            'message' => 'Invalid object type.',
            'status' => 'error',
            'type' => 'wrongtype',
            'foreignKey' => 1
        ];
        $actual = json_decode($this->_response->getBody(), true);
        $this->assertEquals($expected, $actual);
    }
    
    
    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->disableErrorHandlerMiddleware();
        $this->configRequest([
            'headers' => [
                'REFERER' => '/articles/index',
            ]
        ]);
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->enableRetainFlashMessages();
        $this->get('/favorites/favorites/delete/a62e34eb-084b-4ff8-b8b9-754c581ecab2');
        $this->assertRedirect('/articles/index');
        $this->assertSession('Record removed from list', 'Flash.flash.0.message');
    }
    
    /**
     * Test delete method, record does not exists
     *
     * @return void
     */
    public function testDeleteNotExists()
    {
        $this->disableErrorHandlerMiddleware();
        $this->configRequest([
            'headers' => [
                'REFERER' => '/articles/index',
            ]
        ]);
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->enableRetainFlashMessages();
        $this->get('/favorites/favorites/delete/abce34eb-084b-4ff8-b8b9-754c581ecab2');
        $this->assertRedirect('/articles/index');
        $this->assertSession('Unable to delete favorite, please try again', 'Flash.flash.0.message');
    }
    
    /**
     * Test delete method, record does not exists
     *
     * @return void
     */
    public function testDeleteOtherUsers()
    {
        $this->disableErrorHandlerMiddleware();
        $this->configRequest([
            'headers' => [
                'REFERER' => '/articles/index',
            ]
        ]);
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->enableRetainFlashMessages();
        $this->get('/favorites/favorites/delete/a62e34eb-084b-4ff8-b8b9-754c581ecab2');
        $this->assertRedirect('/articles/index');
        $this->assertSession('Unable to delete favorite, please try again', 'Flash.flash.0.message');
    }

    /**
     * Test short_list method
     *
     * @return void
     */
    public function testShortList()
    {
        $this->disableErrorHandlerMiddleware();
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->get('/favorites/favorites/short-list/like');
        $this->assertResponseOk();
        $entities = $this->viewVariable('favorites');
        $this->assertTrue(count($entities['FavoriteArticles']) === 2);
    }

    /**
     * Test list_all method
     *
     * @return void
     */
    public function testListAll()
    {
        $this->disableErrorHandlerMiddleware();
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->get('/favorites/favorites/list-all/like');
        $this->assertResponseOk();
        $entities = $this->viewVariable('favorites');
        $this->assertTrue(count($entities['FavoriteArticles']) === 2);
    }

    /**
     * Test move method
     *
     * @return void
     */
    public function testMove()
    {
        $this->disableErrorHandlerMiddleware();
        $this->configRequest([
            'headers' => [
                'REFERER' => '/articles/index',
            ]
        ]);
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->get('/favorites/favorites/move/a63e34eb-084b-4ff8-b8b9-754c581ecab2/up');
        $this->assertSession('Favorite positions updated.', 'Flash.flash.0.message');
        $this->assertRedirect('/articles/index');
    }
    
    /**
     * Test move method with a wrong direction
     *
     * @return void
     */
    public function testMoveWrongDirection()
    {
        $this->disableErrorHandlerMiddleware();
        $this->configRequest([
            'headers' => [
                'REFERER' => '/articles/index',
            ]
        ]);
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'testing',
                ]
            ]
        ]);
        $this->get('/favorites/favorites/move/a63e34eb-084b-4ff8-b8b9-754c581ecab2/space');
        $this->assertSession('Invalid direction', 'Flash.flash.0.message');
        $this->assertRedirect('/articles/index');
    }

    /**
     * Test redirect method
     *
     * @return void
     */
    public function testRedirect()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
