<?php

use XRayLog\XRayLogger;

if (!function_exists('xray_setup')) {
    /**
     * Configure XRayLogger project name globally
     *
     * @param array $config Configuration array with key:
     *                      - project: Project name
     * @return void
     */
    function xray_setup(array $config): void
    {
        static $initialized = false;
        
        if (!$initialized && isset($config['project'])) {
            xray()->setProject($config['project']);
            $initialized = true;
        }
    }
}

if (!function_exists('xray')) {
    /**
     * Get XRayLogger instance and log message
     *
     * @param string|mixed $typeOrPayload Log type (ERROR, INFO, etc.) or payload if single argument
     * @param mixed|null $payload Data to log (optional if first argument is payload)
     * @return XRayLogger
     */
    function xray($typeOrPayload = null, $payload = null): XRayLogger
    {
        static $instance = null;
        
        if ($instance === null) {
            $instance = new XRayLogger('Default Project');
        }

        if ($typeOrPayload !== null) {
            if ($payload === null) {
                // Single argument - treat as payload with INFO level
                $instance->info($typeOrPayload);
            } else {
                // Two arguments - first is type, second is payload
                $method = strtolower($typeOrPayload);
                if (method_exists($instance, $method)) {
                    $instance->$method($payload);
                } else {
                    $instance->log($payload, strtoupper($typeOrPayload));
                }
            }
        }
        
        return $instance;
    }
}
