<?php
namespace CakeDC\Favorites\Test\TestCase\Model\Table;

use CakeDC\Favorites\Model\Table\FavoritesTable;
use CakeDC\Favorites\Test\App\Model\Table\FavoriteArticlesTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

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
    public $FavoriteUsers;

    /**
     * @var \CakeDC\Favorites\App\Model\Table\FavoriteArticlesTable
     */
    public $FavoriteArticles;
    
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.cake_d_c/favorites.favorites',
        'core.articles',
		'core.users'
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
        $this->FavoriteArticles = TableRegistry::get('FavoriteArticles', $config);
        
        $config = TableRegistry::exists('FavoriteUsers') ? [] : ['className' => FavoriteUsersTable::class];
        $this->FavoriteUsers = TableRegistry::get('FavoriteArticles', $config);
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
        
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
