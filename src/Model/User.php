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
 * Represents a User object as stored in the database.
 * 
 * @author Jordan Mele
 * @property int id
 * @property string email
 * @property string name
 * @property string identity_provider Identity provider this user has connected with.
 * @property string identity_provider_user_id Id for this user on the used identity provider service.
 * @property string locale Users specified language. Note that i18n system currently not in place.
 * @property boolean flag_enabled Indicates if account is enabled. If false, user is logged out.
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class User extends UFModel
{
    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "users";

    /**
     * @var string Columns that are permitted to be altered.
     */
    protected $fillable = [
        'email',
        'name',
        'identity_provider',
        'identity_provider_user_id',
        'locale',
        'enabled',
        'email_verified'
    ];

    /**
     * @var array Attributes that should be cast to native types.
     */
    protected $casts = [
        'enabled' => 'boolean',
        'email_verified' => 'boolean'
    ];

    /**
     * @var bool Enable timestamps.
     */
    public $timestamps = true;

    /**
     * Generate the users full name.
     * Laravel magic methods will map this to `$this->full_name`
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Get the activities associated with this user.
     * @return QueryBuilder
     */
    public function activities()
    {
        return $this->hasMany(self::$ci->dbModel->Activity);
    }

    /**
     * Get the roles associated with this user.
     * @return QueryBuilder
     */
    public function roles()
    {
        return $this->belongsToMany(self::$ci->dbModel->Role);
    }

    /**
     * Get the permissions associated with this user.
     * @return QueryBuilder
     */
    public function permissions()
    {
        return self::$ci->dbModel->Permission::leftJoin('permission_roles', 'permissions.id', '=', 'permission_roles.permission_id')
                                             ->leftJoin('roles', 'permission_roles.role_id', '=', 'roles.id')
                                             ->leftJoin('role_users', 'role.id', '=', 'role_users.role_id')
                                             ->where('role_users.user_id', $this->id);
    }
}