<?php
namespace CakeDC\Favorites\Model\Entity;

use Cake\ORM\Entity;

/**
 * Favorite Entity
 *
 * @property string $id
 * @property string $user_id
 * @property string $foreign_key
 * @property string $model
 * @property string $type
 * @property int $position
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \CakeDC\Favorites\Model\Entity\User $user
 */
class Favorite extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'user_id' => true,
        'foreign_key' => true,
        'model' => true,
        'type' => true,
        'position' => true,
        'created' => true,
        'modified' => true,
        'user' => true
    ];
}
