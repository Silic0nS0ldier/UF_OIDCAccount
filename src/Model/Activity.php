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
 * Represents a users activity in the database.
 * 
 * @author Jordan Mele
 * @property int id
 * @property int user_id If of the user this activity belongs to.
 * @property string ip_address IP address of user when activity was logged.
 * @property string type An identifier that represents the class of activities this activity belongs to. Primarily used as search aid.
 * @property timestamp occurred_at Time event occured at.
 * @property string description
 */
class Activity extends UFModel
{
    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "activities";

    /**
     * @var string Columns that are permitted to be altered.
     */
    protected $fillable = [
        'ip_address',
        'user_id',
        'type',
        'occurred_at'
    ];

    /**
     * @var array Attributes that should be cast to native types.
     */
    protected $casts = [
        'created_at' => 'timestamp'
    ];

    /**
     * Get the user associated with this activity.
     */
    public function user()
    {
        return $this->hasOne(self::$ci->dbModel->User);
    }
}