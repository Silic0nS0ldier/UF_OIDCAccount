<?php
/**
 * UF_OIDCAccount (https://github.com/Silic0nS0ldier/UF_OIDCAccount)
 *
 * @link      https://github.com/Silic0nS0ldier/UF_OIDCAccount
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\ServicesProvider;

/**
 * Registers services for the OIDCAccount sprinkle.
 *
 * @author Jordan Mele (https://github.com/Silic0nS0ldier)
 */
class OIDCAccountServicesProvider
{
    /**
     * Register OIDCAccount's services.
     *
     * @param Container $container A DI container implementing ArrayAccess and container-interop.
     */
    public static function register($container)
    {

        /**
         * Map database models to 'dbModel' on container.
         * This is used instead of classMapper to aid in readability.
         *
         * Mappings added: Version, User, Role, Permission, Activity
         */
        $container['dbModel'] = (object) [
            'Version'           => $container->classMapper->getClassMapping('version'),
            'User'              => 'UserFrosting\Sprinkle\OIDCAccount\Model\User',
            'Activity'          => 'UserFrosting\Sprinkle\OIDCAccount\Model\Activity',
            'Role'              => 'UserFrosting\Sprinkle\OIDCAccount\Model\Role',
            'Permission'        => 'UserFrosting\Sprinkle\OIDCAccount\Model\Permission'
        ];
    }
}
