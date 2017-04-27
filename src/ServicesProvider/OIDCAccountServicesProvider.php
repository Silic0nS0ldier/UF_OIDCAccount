<?php
/**
 * UF_OIDCAccount (https://github.com/Silic0nS0ldier/UF_OIDCAccount)
 *
 * @link      https://github.com/Silic0nS0ldier/UF_OIDCAccount
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\ServicesProvider;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use UserFrosting\Sprinkle\Core\Log\MixedFormatter;
use UserFrosting\Sprinkle\OIDCAccount\Twig\OIDCAccountExtension;

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
         * Mappings added: Version, User, Activity, Role, Permission
         */
        $container['dbModel'] = (object) [
            'Version'           => $container->classMapper->getClassMapping('version'),
            'User'              => 'UserFrosting\Sprinkle\OIDCAccount\Model\User',
            'Activity'          => 'UserFrosting\Sprinkle\OIDCAccount\Model\Activity',
            'Role'              => 'UserFrosting\Sprinkle\OIDCAccount\Model\Role',
            'Permission'        => 'UserFrosting\Sprinkle\OIDCAccount\Model\Permission'
        ];

        //also class mapper for back-compat

        /**
         * Auth logging with Monolog.
         *
         * Extend this service to push additional handlers onto the 'auth' log stack.
         */
        $container['authLogger'] = function ($c) {
            $logger = new Logger('auth');

            $logFile = $c->get('locator')->findResource('log://auth.log', true, true);

            $handler = new StreamHandler($logFile);

            $formatter = new MixedFormatter(null, null, true);

            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);

            return $logger;
        };

        // stub user for testing
        $container['currentUser'] = function ($c) {
            return new $c->dbModel->User([
                "id" => 1,
                "email" => "user@uowmail.edu.au",
                "first_name" => "Foo",
                "last_name" => "Bar",
            ]);
        };

        /**
         * Extends the 'view' service with the AccountExtension for Twig.
         *
         * Adds account-specific functions, globals, filters, etc to Twig, and the path to templates for the user theme.
         */
        $container->extend('view', function ($view, $c) {
            $twig = $view->getEnvironment();
            $extension = new OIDCAccountExtension($c);
            $twig->addExtension($extension);

            return $view;
        });
    }
}
