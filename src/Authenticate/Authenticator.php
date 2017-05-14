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
use Slim\Container;
use Jose\Loader;

/**
 * Handles authentication tasks.
 *
 * @author Jordan Mele
 * @todo Everything. This isn't event used atm.
 */
class Authenticator
{
    /**
     * @var object
     */
    private $dbModels;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var mixed[]
     */
    private $config;

    //IDPList

    private function validateIdToken($idToken, $issuer, $subject)
    {
        //ensure issuer and subject match
        
    }

    //method to handle response from first login
    //method to verify id_token
    //method to get data
}