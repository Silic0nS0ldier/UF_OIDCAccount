<?php
/**
 * 
 *
 * @link      
 * @copyright 
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\Twig;

use Interop\Container\ContainerInterface;

/**
 * Extends Twig functionality for the OIDCAccount sprinkle.
 *
 * @author 
 */
class OIDCAccountExtension extends \Twig_Extension
{
    // ContainerInterface
    protected $c;

    public function __construct(ContainerInterface $c)
    {
        $this->c = $c;
    }

    public function getName()
    {
        return 'userfrosting/OIDCAccount';
    }

    public function getFunctions()
    {
        return [];
    }

    public function getGlobals()
    {
        return [
            'current_user'   => $this->c->currentUser,
            'oidc_links'     => $this->c->oidcLinks
        ];
    }
}
