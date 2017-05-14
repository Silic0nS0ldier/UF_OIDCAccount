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

    //method to validate id_token, assume HS256 is the encryption used, plus private/public key pair
    private function decodeIdToken($encodedIdToken, $keys)
    {
        //extract header (typ, alg)
        //determine required decoding

        //extract payload

        //extract signature
        //verify
    }

    private function validateIdToken($idToken, $issuer, $subject)
    {
        //ensure issuer and subject match
        
    }

    //method to handle response from first login
    //method to verify id_token
    //method to get data
}