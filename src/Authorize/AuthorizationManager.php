<?php
/**
 * OIDCAccount
 *
 * @link      
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\Authorize;

use Interop\Container\ContainerInterface;
use UserFrosting\Sprinkle\OIDCAccount\Authorize\ParamaterProcessor;

/**
 * AuthorizationManager class.
 *
 * Manages a collection of access condition callbacks, and uses them to perform access control checks on user objects.
 * @author Jordan Mele
 */
class AuthorizationManager
{
    /**
     * @var ContainerInterface The global container object, which holds all your services.
     */
    private $ci;

    /**
     * @var callable[] An array of callbacks that accept some parameters and evaluate to true or false.
     */
    private $callbacks = [];

    /**
     * Create a new AuthorizationManager object.
     *
     * @param ContainerInterface $ci The global container object, which holds all your services.
     */
    public function __construct(ContainerInterface $ci, $callbacks = [])
    {
        $this->ci = $ci;
        $this->callbacks = $callbacks;
    }

    /**
     * Register an authorization callback, which can then be used in permission conditions.
     *
     * To add additional callbacks, simply extend the `authorizer` service in your Sprinkle's service provider.
     * @param string $name
     * @param callable $callback
     */
    public function addCallback($name, $callback)
    {
        $this->callbacks[$name] = $callback;
    }

    /**
     * Get all authorization callbacks.
     *
     * @return callable[]
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    /**
     * Checks whether or not a user has access on a particular permission slug.
     *
     * Determine if this user has access to the given $hook under the given $params.
     * @param User $user The user object to check against.
     * @param string $slug The slug for permissions that will be compared against, if the specified user has them.
     * @param array|null $params[] An optional array of field names => values, specifying any additional data to provide the authorization module when determining whether or not this user has access.
     * @return boolean True if the user has access, false otherwise.
     */
    public function checkAccess($user, $slug, $params = [])
    {
        $debug = $this->ci->config['debug.auth'];

        if ($debug) {
            $trace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3), 1);
            $this->ci->authLogger->debug("Authorization check requested at: ", $trace);
            $this->ci->authLogger->debug("Checking authorization for user {$user->id} ('{$user->user_name}') on permission '$slug'...");
        }

        if ($this->ci->authenticator->guest()) {
            if ($debug) {
                $this->ci->authLogger->debug("User is not logged in.  Access denied.");
            }
            return false;
        }

        // The master (root) account has access to everything.
        // Need to use loose comparison for now, because some DBs return `id` as a string.

        if ($user->id == $this->ci->config['reserved_user_ids.master']) {
            if ($debug) {
                $this->ci->authLogger->debug("User is the master (root) user.  Access granted.");
            }
            return true;
        }

        // Find all permissions that apply to this user (via roles), and check if any evaluate to true.
        $permissions = $user->permissions()->where('slug', $slug)->get();

        if (!count($permissions)) {
            if ($debug) {
                $this->ci->authLogger->debug("No matching permissions found.  Access denied.");
            }
            return false;
        }

        if ($debug) {
            $this->ci->authLogger->debug("Found matching permission(s): \n" . print_r($permissions->toArray(), true));
        }

        // Iterate over each permission, and attempt to process.
        foreach ($permissions as $permission) {
            if ($debug) {
                $logger->debug("Attempting permission '$permission->name'", $permission);
            }

            // Get processed paramaters
            $data = null;
            try {
                $data = ParamaterProcessor::process($permission->paramaters, $params, $this->ci);
            } catch (\Exception $e) {
                if ($debug) {
                    $this->ci->authLogger->debug("Paramater processor couldn't generate paramaters. This could mean there is an issue, or that this isn't the permission being queried (if there are permissions with the same slug).", $e);
                }
            }

            // Attempt permission, if data isn't null and callback exists.
            if ($data !== null && array_key_exists($this->callbacks, $permission->slug)) {
                if ($debug) {
                    $logger->debug("Attempting callback.");
                }
                if ($this->callbacks[$permission->slug](...$data)) {
                    return true;
                }
            }
        }

        if ($debug) {
            $this->ci->authLogger->debug("User failed to pass any of the matched permissions. Access denied.");
        }

        return false;
    }
}
