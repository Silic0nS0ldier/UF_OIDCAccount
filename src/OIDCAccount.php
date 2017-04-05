<?php
/**
 * UF_OIDCAccount (https://github.com/Silic0nS0ldier/UF_OIDCAccount)
 *
 * @link      https://github.com/Silic0nS0ldier/UF_OIDCAccount
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\Core;

use UserFrosting\Sprinkle\Core\Initialize\Sprinkle;
use UserFrosting\Sprinkle\OIDCAccount\ServicesProvider\OIDCAccountServicesProvider;

/**
 * Bootstrapper class for the core sprinkle.
 *
 * @author Jordan Mele
 */
class OIDCAccount extends Sprinkle
{
    /**
     * Trigger Service Provider(s) for this Sprinkle.
     */
    public function init()
    {
        // Execute Services Provider
        OIDCAccountServicesProvider::register($this->ci);
    }
}
