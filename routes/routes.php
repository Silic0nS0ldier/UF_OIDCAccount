<?php
/**
 * OIDCAccount
 *
 * @link      
 * @copyright 
 * @license   
 */

//oauth/oidc callback for login

$app->post('/login', 'UserFrosting\Sprinkle\OIDCAccount\Controller\OIDCAccountController:loginRedirect')
    ->setName('login');

$app->get('/logout', 'UserFrosting\Sprinkle\OIDCAccount\Controller\OIDCAccountController:logoutRedirect')
    ->setName('logout');