<?php

namespace ggio\TrendingEntries\traits;

use craft\helpers\App;

/**
 * Amp Resource trait
 * Adds methods to check amp origin request and add cors headers to response.
 *
 * @author Gonzalo García Arce <info@gongarce.io>
 * @copyright Gonzalo García Arce
 * @license MIT
 *
 * @since     1.1.0
 */
trait AmpResourceRequests
{
    const AMP_CDN = '.cdn.ampproject.org';

    /**
     * Validates that the request follows amp protocol.
     */
    protected function isAmpRequestValid(string $publisherDomain): bool
    {
        $headers = \Craft::$app->getRequest()->getHeaders();
        $origin = $headers->get('Origin');
        $myDomain = rtrim(App::parseEnv('@web'), '/');

        // 1. Validate origin header
        if ($origin) {
            $isGoogleProxy = ($origin === $publisherDomain.self::AMP_CDN);
            $isMyDomain = ($origin === $myDomain);

            return $isGoogleProxy || $isMyDomain;
        }

        // 2. Missing origin validate amp same origin header
        return $headers->get('AMP-Same-Origin') === 'true';
    }

    /**
     * Apply AMP CORS specification response headers.
     */
    protected function setAmpResponseHeaders(): void
    {
        $headers = \Craft::$app->getRequest()->getHeaders();
        $responseHeaders = \Craft::$app->getResponse()->getHeaders();
        $myDomain = rtrim(App::parseEnv('@web'), '/');

        $origin = $headers->get('Origin');
        $allowOrigin = $origin ?: $myDomain;

        $responseHeaders->set('Access-Control-Allow-Origin', $allowOrigin);
        $responseHeaders->set('AMP-Access-Control-Allow-Source-Origin', $myDomain);
        $responseHeaders->set('Access-Control-Expose-Headers', 'AMP-Access-Control-Allow-Source-Origin');
        $responseHeaders->set('Access-Control-Allow-Credentials', 'true');
    }
}
