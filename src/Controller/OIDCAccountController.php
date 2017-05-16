<?php
/**
 * OIDCAccount
 *
 * @link      
 * @copyright 
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\NotFoundException;
use UserFrosting\Sprinkle\Core\Controller\SimpleController;

/**
 * Handles 
 * @author Jordan Mele
 */
class OIDCAccountController extends SimpleController {

    /**
     * Recieves response from Identity Provider upon user login.
     *
     * Request type: POST
     *
     * @todo This is a stub for the sake of demonstration.
     */
    public function loginRedirect($request, $response, $args)
    {
        // Get id_token
        $id_token = \UserFrosting\Sprinkle\OIDCAccount\Authenticate\OpenIDConnect\OpenIDConnect::processIdToken($request->getParsedBody()['id_token'], '');
        // See if user already exists
        $user = $this->ci->dbModel->User::where('oidc_issuer', $id_token->iss)->where('oidc_subject', $id_token->sub)->first();
        if ($user == null) {
            // user doesn't exist, add            
            if (!isset($id_token->email)) {
                // hacky fix for dodgy email claims
                $id_token->email = $id_token->upn;
            }
            $user = new $this->ci->dbModel->User([
                'email'             => $id_token->email,
                'name'              => $id_token->name,
                'oidc_issuer'       => $id_token->iss,
                'oidc_subject'      => $id_token->sub,
                'enabled'           => true
            ]);
            $user->save();
        }
        //set current user in session
        $this->ci->session['user_id'] = $user->id;
        //redirect to index
        return $response->withRedirect($this->ci->router->pathFor('index'), 302);
    }

    // definitly a stub...
    public function logoutRedirect($request, $response, $args)
    {
        // Completely destroy the session
        $this->ci->session->destroy();

        // Restart the session service
        $this->ci->session->start();

        //redirect to idp logout link
        return $response->withRedirect($this->ci->oidcLinks->logout, 302);
    }
}