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
 * Represents a role in the database.
 * 
 * @author Jordan Mele
 * @property int id
 * @property string name A name representing the role.
 * @property string slug A friendly alias for the authorization system.
 * @property string description
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class Role extends UFModel
{
    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "roles";

    /**
     * @var string Columns that are permitted to be altered.
     */
    protected $fillable = [
        'name',
        'slug',
        'description'
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