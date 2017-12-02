<?php

namespace CakeDC\Favorites\Test\App\Model\Table;

use Cake\ORM\Table;

/**
 * FavoriteArticles Model
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class FavoriteArticlesTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('articles');
        $this->setPrimaryKey('id');
        $this->belongsTo('FavoriteUsers', [
            'foreignKey' => 'author_id',
            'className' => FavoriteUsersTable::class
        ]);
        
        $this->addBehavior('CakeDC/Favorites.Favorite');
    }
}
