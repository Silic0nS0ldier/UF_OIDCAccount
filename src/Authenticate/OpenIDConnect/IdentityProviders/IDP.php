<?php
/**
 * OIDCAccount
 *
 * @link      
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\Authenticate\OpenIDConnect\IdentityProviders;

use Illuminate\Cache\CacheManager;
use Slim\Csrf\Guard;

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
     * @var object JSON Web Keys (JWKs)
     */
    private $jwks;

    /**
     * @var string client_id used to identify service at the identity provider.
     */
    private $clientId;

    /**
     * @var \Slim\Csrf\Guard Session service for session access.
     */
    private $csrf;

    /**
     * @var integer Time in seconds that idp data should be cached.
     */
    private $cacheLength;

    /**
     * Create a new IDP object.
     *
     * @param object $rawIdpObject An identity provider object from the JSON configuration file.
     * @param \Illuminate\Cache\CacheManager Cache service for cache access.
     * @param \Slim\Csrf\Guard Slim CSRF Guard object
     */
    public function __construct($rawIdpObject, $cache, $csrf)
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

        // Validate cache time
        if (is_null($rawIdpObject->cache_expires)) {
            $this->cacheLength = 60 * 24 * 60;
        } else if (!is_int($rawIdpObject->cache_expires)) {
            $invalidException("cache_expires specified, but isn't an integer.");
        } else {
            if ($rawIdpObject->cache_expires <= 0) {
                $invalidException("cache_expires specified, but is 0 or lower.");
            } else {
                $this->cacheLength = 60 * 24 * $rawIdpObject->cache_expires;
            }
        }

        $this->cache = $cache;
        $this->csrf = $csrf;
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
     */
    public function generateLoginUri($redirectUri)
    {
        // Get base url
        $url = $this->getOidcConfig()->authorization_endpoint;
        // Add client_id
        $url .= "?client_id=" . $this->clientId;
        // Add response_type
        $url .= "&response_type=id_token+code";
        // Add redirect_uri
        $url .= "&redirect_uri=" . urlencode($redirectUri);
        // Add response_mode (optional, but we can specify form_post here for better security)
        $url .= "&response_mode=form_post";
        // Add scope for openid
        $url .= "&scope=" + urlencode("openid email");
        // Add nonce safeguard (we use the CSRF token, as it'll only be erased if something weird happens, and is only wiped at the end of the end of the next request.)
        $nonce = $this->csrf->getTokenName() . $this->csrf->getTokenValue();
        $url .= "&nonce=" . urlencode($nonce);

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
    * Returns JSON Web Keys (JWKs) used for decrypting encrypted id_token
    *
    * @return object JWKs object
    */
    public function getJWKs()
    {
        return $this->getOidcConfig()->jwks_uri;
        if (isset($this->jwks)) {
            // Its stored locally, return that
            return $this->jwks;
        } elseif ($this->cache->get("$this->alias-jwks") != null) {
            // Its been cached, store it locally and return that
            $this->jwks = json_decode($this->cache->get("$this->alias-jwks"));
            return $this->jwks;
        } else {
            // We don't have it, but the provider does. Time to fetch some fresh keys.
            // Get data via curl
            $query = curl_init($this->getOidcConfig()->jwks_uri);
            $result = curl_exec($query);
            // Attempt JSON decode, verify details by inspecting base uri for all links
            $jwks = json_decode($result);
            if ($config === null) {
                throw new \RuntimeException("Unable to retrieve JSON Web Keys (JWKs) for $this->alias.");
            }
            // Store the keys in cache
            $this->cache->put("$this->alias-jwks", json_encode($jwks), $this->cacheLength);
            // Store locally
            $this->jwks = $jwks;
            // Return keys
            return $this->jwks;
        }
    }

    /**
     * Generates logout uri for the IDP. (aka end_session_endpoint)
     * Note: IDP may announce logout to connected services.
     */
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
        } elseif ($this->cache->get("$this->alias-idpcconfig") != null) {
            // Next check the cache
            $this->oidcConfig = json_decode($this->cache->get("$this->alias-idpcconfig"));
            return $this->oidcConfig;
        } else {
            // Last resort, query for fresh details.
            // Get data via curl
            $query = curl_init($this->baseUri . ".well-known/openid-configuration");
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
            $this->cache->put("$this->alias-idpcconfig", json_encode($config), $this->cacheLength);

            // Finally return config
            return $this->oidcConfig;
        }
    }
}