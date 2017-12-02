<?php
namespace CakeDC\Favorites\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
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

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
            'className' => 'CakeDC/Favorites.Users'
        ]);
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
            ->maxLength('type', 3)
            ->allowEmpty('type');

        $validator
            ->integer('position')
            ->requirePresence('position', 'create')
            ->notEmpty('position');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }
}
