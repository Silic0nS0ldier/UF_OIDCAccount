<?php
/**
 * OIDCAccount
 *
 * @link      
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\Authenticate\IdentityProviders;

use Illuminate\Cache\CacheManager;
use UserFrosting\Session\Session;

/**
 * Represents an identity provider, and provides methods useful for integration with service.
 * 
 * @author Jordan Mele
 */
class IDP
{
    /**
     * @var string Name of the identity provider.
     */
    private $name;

    /**
     * @var string Short PHP friendly alias for the identity provider.
     */
    private $alias;

    /**
     * @var string Address of OpenID Connect configuration file.
     */
    private $baseUri;

    /**
     * @var string[] Specified API base uri's for use with identity provider, each on a named index.
     */
    private $apiUri;

    /**
     * @var string Path to an optional icon that represents the identity provider.
     */
    private $iconPath;

    /**
     * @var \Illuminate\Cache\CacheManager Cache service for cache access.
     */
     private $cache;

    /**
     * @var object OpenID Connect configuration
     */
    private $oidcConfig;

    /**
     * @var string client_id used to identify service at the identity provider.
     */
    private $clientId;

    /**
     * @var \UserFrosting\Session\Session Session service for session access.
     */
    private $session;

    /**
     * Create a new IDP object.
     *
     * @param object $rawIdpObject An identity provider object from the JSON configuration file.
     * @param \Illuminate\Cache\CacheManager Cache service for cache access.
     */
    public function __construct($rawIdpObject, $cache, $session)
    {
        $invalidException = function($error) use ($rawIdpObject) {
            throw new \InvalidArguementException("Failed to parse identity provider details." . PHP_EOL . "Error: " . $error . PHP_EOL . "Provided data: " . json_encode($rawIdpObject));
        };

        // Validate name
        if (!is_string($rawIdpObject->name)) {
            $invalidException("Invalid name.");
        } else {
            $this->name = $rawIdpObject->name;
        }

        // Validate short name
        if (!is_string($rawIdpObject->alias)) {
            $invalidException("Alias is required, and must be a string.");
        } else {
            $this->alias = $rawIdpObject->alias;
        }

        // Validate icon
        /* @todo Should really check file exists too. */
        if (!is_string($rawIdpObject->icon)) {
            $invalidException("Icon path is required, and must be a string.");
        } else {
            if (preg_match("(\/?[a-z_\-\s0-9\.]+)+\.([a-z_]+)$", $rawIdpObject->icon) !== 1) {
                $invalidException("Icon path is not a valid Unix path.");
            } else {
                $this->iconPath = $rawIdpObject->icon;
            }
        }

        // Check existance of uri key
        if (!is_object($rawIdpObject->uri)) {
            $invalidException("Couldn't find uris.");
        } else {
            // Validate base uri
            if (filter_var($rawIdpObject->uri->base, FILTER_VALIDATE_URL) === false) {
                $invalidException("Invalid base uri.");
            } else {
                $this->baseUri = $rawIdpObject->uri->base;
            }

            // Validate any api uris, if set.
            if (isset($rawIdpObject->uri->api)) {
                $this->apiUris = [];
                foreach ($rawIdpObject->uri->api as $apiUri) {
                    if (filter_var($apiUri->uri, FILTER_VALIDATE_URL) === false && !is_string($apiUri->name)) {
                        $invalidException("Invalid api uri.");
                    } else {
                        $this->apiUri[$apiUri->name] = $apiUri->uri;
                    }
                }
            }
        }

        // Validate client_id
        if (!is_string($rawIdpObject->client_id)) {
            $invalidException("client_id is required and must be a string.");
        } else {
            $this->clientId = $rawIdpObject->client_id;
        }

        $this->cache = $cache;
        $this->session = $session;
    }

    /**
     * Returns identity provider name.
     *
     * @return string Name of identity provider.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns identity provider alias.
     *
     * @return string Alias for identity provider.
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Generates the login URI for the identity provider.
     *
     * @param string URI to redirect to after successful login, or irrecoverable failure.
     * @return string URI to facilitate login via identity provider.
     *
     * @todo Is CSRF protection something that will need to be implemented with this?
     */
    public function generateLoginUri($redirectUri)
    {
        // Get base url
        $url = $this->getOidcConfig()->authorization_endpoint;
        // Add client_id
        $url .= "?client_id=" . $this->clientId;
        // Add response_type
        $url .= "&response_type=id_token";
        // Add redirect_uri
        $url .= "&redirect_uri=" . urlencode($redirectUri);
        // Add response_mode (optional, but we can specify form_post here for better security)
        $url .= "&response_mode=form_post";
        // Add scope for openid
        $url .= "&scope=openid";
        // Add nonce safeguard
        // Generate key
        $guid = $this->generateGUID();
        // Store in session
        $this->session->set('oidc_nonce', $guid);
        // Attach to url
        $url .= "&nonce=" . urlencode($guid);

        return $url;
    }

    /**
     * Returns token_endpoint URI.
     * Needed to establish OAuth2 session that can facilitate retrival of data.
     *
     * @return string OAuth2 token_endpoint URI
     */
    public function getTokenEndpoint()
    {
        return $this->getOidcConfig()->token_endpoint;
    }

    /**
    * Returns jwks_uri URI.
    * URI returns json containing signtures that can be used to verify the signing userinfo_endpoint
    *
    * @return string jwks_uri URI
    */
    public function getJwksUri()
    {
        return $this->getOidcConfig()->jwks_uri;
    }

    //get logout url (aka end_session_endpoint)
    //identity provider will indicate to all connected services that the specified user has logged out.
    public function generateLogoutUri($redirectUri)
    {
        // Get base url
        $url = $this->getOidcConfig()->end_session_endpoint;
        // Add post_logout_redirect_uri
        $url .= "?post_logout_redirect_uri=" . urlencode($redirectUri);

        return $url;
    }

    /**
     * Returns URI that can be used check session with identity provider on client side.
     *
     * @return string Session check URI
     */
    public function getSessionCheckUri()
    {
        return $this->getOidcConfig()->check_session_iframe;
    }

    /**
     * Returns URI that can be used to request information about the authenticated user.
     *
     * @return string User info URI
     */
    public function getUserInfoUri()
    {
        return $this->getOidcConfig()->userinfo_endpoint;
    }

    
    /**
     * Returns uri for requested api.
     *
     * @param string $apiName Name of requested api uri.
     * @return string Api uri.
     *
     * @throws \OutOfBoundsException Thrown when specified api can't be found.
     */
    public function getApiUri($apiName)
    {
        if (!array_key_exists($apiName, $this->apiUri)) {
            throw new \OutOfBoundsException("The api '$apiName' couldn't be found, uri can't be returned.");
        } else {
            return $this->apiUri[$apiName];
        }
    }

    /**
     * Returns path to icon, ready to be feed into locator service.
     *
     * @return string Icon path for use with locator service.
     *
     * @example {{ assets.url(idpObject.getIconPath()) }}
     */
    public function getIconPath()
    {
        return "assets://" . $this->iconPath;
    }

    /**
     * Gets OpenID Connect configuration, updating with fresh details if necessary.
     */
    private function getOidcConfig()
    {
        // First check if configuration has already been fetched.
        if (isset($this->oidcConfig)) {
            return $this->oidcConfig;
        } elseif ($this->cache->get("idpcconfig-$this->alias") != null) {
            // Next check the cache
            $this->oidcConfig = json_decode($this->cache->get("idpcconfig-$this->alias-date"));
            return $this->oidcConfig;
        } else {
            // Last resort, query for fresh details.
            // Get data via curl
            $query = curl_init($this->baseUri);
            $result = curl_exec($query);
            // Attempt JSON decode, verify details by inspecting base uri for all links
            $config = json_decode($result);
            if ($config === null) {
                throw new \RuntimeException("Configuration data appears to be corrupted for identity provider $this->alias. Aborting.");
            }
            // Verify details by inspecting base uri for all links (aka the attack vector for man-in-the-middle attacks)
            $hostUri = parse_url($this->baseUri, PHP_URL_HOST);
            $validateBaseUri = function($testUri) use ($hostUri) {
                // Extract base uri
                $hostTestUri = parse_url($testUri, PHP_URL_HOST);
                // Compare with hostUri
                if ($hostUri == $hostTestUri) {
                    return;
                } else {
                    throw new \RuntimeException("Evidence of manipulation to identity provider configuration file. Host URI should be consistent, but was not. For $testUri the expected host should have been $hostUri but was $hostTestUri");
                }
            };
            // Test authorization_endpoint
            $validateBaseUri($config->authorization_endpoint);
            // Test token_endpoint
            $validateBaseUri($config->token_endpoint);
            // Test jwks_uri
            $validateBaseUri($config->jwks_uri);
            // Test end_session_endpoint
            $validateBaseUri($config->end_session_endpoint);
            // Test check_session_iframe
            $validateBaseUri($config->check_session_iframe);
            // Test userinfo_endpoint
            $validateBaseUri($config->userinfo_endpoint);

            // Store for later (faster) use.
            // Locally
            $this->oidcConfig = $config;
            
            // Cache (stored for 60 days)
            $this->cache->put("idpcconfig-$this->alias-date", json_encode($config), 60 * 24 * 60);

            // Finally return config
            return $this->oidcConfig;
        }
    }

    /**
	 * Generate v4 GUID
	 * 
	 * Version 4 GUIDs are pseudo-random. This is a non-issue as further validation is provided via CSRF checks.
     * @source https://gist.githubusercontent.com/dahnielson/508447/raw/abc0cd5d7daa0484187bb3ff6d984d2aef94930a/UUID.php
	 */
	public static function generateGUID()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

		// 32 bits for "time_low"
		mt_rand(0, 0xffff), mt_rand(0, 0xffff),

		// 16 bits for "time_mid"
		mt_rand(0, 0xffff),

		// 16 bits for "time_hi_and_version",
		// four most significant bits holds version number 4
		mt_rand(0, 0x0fff) | 0x4000,

		// 16 bits, 8 bits for "clk_seq_hi_res",
		// 8 bits for "clk_seq_low",
		// two most significant bits holds zero and one for variant DCE1.1
		mt_rand(0, 0x3fff) | 0x8000,

		// 48 bits for "node"
		mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
}