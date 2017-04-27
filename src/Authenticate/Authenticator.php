<?php
/**
 * OIDCAccount
 *
 * @link      
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\Authenticate;

use UserFrosting\Session\Session;

/**
 * Handles authentication tasks.
 *
 * @author Jordan Mele
 */
class Authenticator
{
    /**
     * @var object
     */
    protected $dbModels;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var mixed[]
     */
    protected $config;

    //method to handle response from first login
    //method to verifiy id_token
    //method to get data
}