<?php
/**
 * OIDCAccount
 *
 * @link      
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\Model;

use UserFrosting\Sprinkle\Core\Model\UFModel;

/**
 * Represents a permission in the database.
 * 
 * @author Jordan Mele
 * @property int id
 * @property string name A name representing the role.
 * @property string|null description
 * @property string slug A friendly alias for the authorization system.
 * @property string callback
 * @property mixed[]|null paramaters Array for which each index is funneled into callback. Writable indexes have a name key.
 * EG: [ "value1", { "name": "writableValue", "default": "optionalDefault" }]
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class Permission extends UFModel
{
    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "permissions";

    /**
     * @var string Columns that are permitted to be altered.
     */
    protected $fillable = [
        'name',
        'description',
        'slug',
        'callback',
        'parameters'
    ];

    /**
     * @var array Attributes that should be cast to native types.
     */
    protected $casts = [
        'parameters' => 'array'
    ];

    /**
     * @var bool Enable timestamps.
     */
    public $timestamps = true;

    // permissions

    /**
     * Get the users associated with this role.
     */
    public function user()
    {
        return $this->belongsToMany(self::$ci->dbModel->User);
    }

    // static default role(s)
}