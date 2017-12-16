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
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test short_list method
     *
     * @return void
     */
    public function testShortList()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test list_all method
     *
     * @return void
     */
    public function testListAll()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test move method
     *
     * @return void
     */
    public function testMove()
    {
        $this->markTestIncomplete('Not implemented yet.');
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
