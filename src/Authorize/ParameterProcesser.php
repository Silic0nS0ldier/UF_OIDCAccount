<?php
/**
 * OIDCAccount
 *
 * @link      
 * @copyright Copyright (c) 2017 Jordan Mele
 * @license   
 */
namespace UserFrosting\Sprinkle\OIDCAccount\Authorize;

/**
 * ParameterProcesser class.
 *
 * Processes parameters for permission callbacks.
 * @author Jordan Mele
 */
class ParameterProcesser
{
    /**
     * Processes parameters for permission callbacks.
     *
     * @param string $jsonTemplate Template to process against, in JSON form.
     * @param mixed[] $data Associative array containing data to be injected into output, as defined by the template.
     * @return mixed[]
     */
    public static function process($jsonTemplate, $data, $ci)
    {
        $debug = $ci->config['debug.auth'];
        $logger = $ci->authLogger;

        if ($debug) {
            $logger->debug("Started processing parameter template.");
            $logger->debug("Template:", $jsonTemplate);
            $logger->debug("Data:", $data);
        }

        $data = [];

        // If provided template is null, return empty array.
        if (is_null($jsonTemplate)) {
            if ($debug) {
                $logger->debug("Template is null, returning empty array.");
            }
            return [];
        } else {
            // Attempt to decode JSON now. No need to test if string, as result will be null on failure.
            $template = json_decode($jsonTemplate);
            // Stop is result is not an array.
            if (!is_array($template)) {
                if ($debug) {
                    $logger->debug("Template isn't a JSON encoded array. Aborting.", $template);
                }
                // Throw exception to report issue.
                throw new \InvalidArgumentException("Template provided isn't a JSON encoded array.");
            }
            $result = [];
            foreach ($template as $paramater) {
                if (is_object($paramater) && property_exists($paramater, "_name")) {
                    if (array_key_exists($paramater->_name, $data)) {
                        $result[] = $data[$paramater->_name];
                    } else if (property_exists($paramater, "default")) {
                        $result[] = $paramater->default;
                    } else {
                        if ($debug) {
                            $logger->debug("Template has field that has no default value and no provided value. Unable to continue.");
                        }
                        // Throw exception to report issue.
                        throw new \LogicException("The mandatory template paramater ($paramater->_name) was not set.");
                    }
                } else {
                    $result[] = $paramater;
                }
            }
            // Hand back fully processed paramaters.
            return $results;
        }
    }
}