<?php

namespace SilverStripe\ExternalLinks\Tasks;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;

/**
 * Check links using curl
 */
class CurlLinkChecker implements LinkChecker
{
    use Configurable;

    /**
     * If we want to follow redirects a 301 http code for example
     * Set via YAML file
     *
     * @config
     * @var boolean
     */
    private static $follow_location = false;

    /**
     * If we want to bypass the cache
     * Set via YAML file
     *
     * @config
     * @var boolean
     */
    private static $bypass_cache = false;

    /**
     * Set default user agent as config
     * Override via YAML file
     * 
     * * @config
     * @var string
     */
    private static $user_agent = '';

    /**
     * Allow to pass custom header to be in CURL request
     * 
     * * @config
     * @var array
     */

     private static $headers = [];

    /**
     * Return cache
     *
     * @return CacheInterface
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

        $cacheKey = md5($href);
        if (!$this->config()->get('bypass_cache')) {
            // Check if we have a cached result
            $result = $this->getCache()->get($cacheKey, false);
            if ($result !== false) {
                return $result;
            }
        }

        // No cached result so just request
        $handle = curl_init($href);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);
        if ($this->config()->get('follow_location')) {
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        }

        // Add user agent
        $userAgent = trim($this->config()->get('user_agent'));
        if ($userAgent) {
            curl_setopt($handle, CURLOPT_USERAGENT , $userAgent);
        }

        // Other headers
        if ($headers = $this->config()->get('headers')) {
            if (is_array($headers)) {
                 curl_setopt($handle, CURLOPT_HTTPHEADER , $headers);
            } else {
                 curl_setopt($handle, CURLOPT_HTTPHEADER , array($headers));
            }
        }
        
        // Retrieve http code
        curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if (!$this->config()->get('bypass_cache')) {
            // Cache result
            $this->getCache()->set($cacheKey, $httpCode);
        }

        return $httpCode;
    }
}
