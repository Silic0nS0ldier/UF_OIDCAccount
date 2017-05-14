<?php
/**
 * OIDCAccount
 *
 * @link      
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\Authenticate\OpenIDConnect\IdentityProviders;

use UserFrosting\Sprinkle\OIDCAccount\Authenticate\OpenIDConnect\IdentityProviders\IDP;
use Slim\Container;

/**
 * Contains a list of IDPs which in turn provide configuration information and the appropraite helper methods.
 *
 * @author Jordan Mele
 */
class IDPList
{
    /**
     * @var Container Service Provider container for app.
     */
    private $c;

    /**
     * @var IDP[] Associative array of IDPs.
     */
    private $idps;

    /**
     *
     */
    public function __construct($c, $rawIdpList)
    {
        $this->c = $c;

        if (!is_array($rawIdpList)) {
            throw new \InvalidArguementException("List of identity providers must be an array.");
        } else {
            $this->idps = [];
            foreach ($rawIdpList as $rawIdp) {
                $idp = new IDP($rawIdp, $this->c->cache, $this->c->session);
                $this->idps[$idp->short_name] = $idp;
            }
        }
    }

    /**
     *
     */
    public function getIDP($short_name)
    {
        if (array_key_exists($short_name, $this->idps)) {
            return $this->idps[$short_name];
        } else {
            throw new \OutOfBoundsException();
        }
    }

    /**
     *
     */
    public function getIDPs()
    {
        return $this->idps;
    }
}