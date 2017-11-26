<?php

namespace SilverStripe\ExternalLinks\Tasks;

use Psr\SimpleCache\CacheInterface;

/**
 * Check links using curl
 */
class CurlLinkChecker implements LinkChecker
{

    /**
     * Return cache
     *
     * @return Zend_Cache_Frontend
     */
    protected function getCache()
    {
        return Injector::inst()->get(CacheInterface::class . '.CurlLinkChecker');
    }

    /**
     * Determine the http status code for a given link
     *
     * @param string $href URL to check
     * @return int HTTP status code, or null if not checkable (not a link)
     */
    public function checkLink($href)
    {
        // Skip non-external links
        if (!preg_match('/^https?[^:]*:\/\//', $href)) {
            return null;
        }

        // Check if we have a cached result
        $cacheKey = md5($href);
        $result = $this->getCache()->get($cacheKey);
        if ($result !== false) {
            return $result;
        }

        // No cached result so just request
        $handle = curl_init($href);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);
        curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        // Cache result
        $this->getCache()->set($httpCode, $cacheKey);
        return $httpCode;
    }
}
