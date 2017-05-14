<?php
/**
 * OIDCAccount
 *
 * @link      
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\Authenticate\OpenIDConnect;

use Slim\Container;

/**
 * Wrapper for all OpenIDConnect authentication operations.
 * 
 * @author Jordan Mele
 */
class OpenIDConnect
{
    /*
    - get idps
    - signin
        - returns verified token
    - 
    */
    /**
     * Processes id_token provided by Identity Provider.
     *
     * @param string Raw id_token to be processed
     * @param string Short name for the identity provider
     * @return object
     *
     * @throws \Exception Its possible for id_token's to be invalid
     *
     * @todo Everything. This is just a stub that grabs the id_token, and decodes the base64 encoding, and returns it.
     */
    public static function processIdToken($rawIdToken, $idp)
    {
        return json_decode(base64_decode(explode('.', $rawIdToken)[1]));
    }
}