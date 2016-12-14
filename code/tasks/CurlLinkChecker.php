<?php

/**
 * Check links using curl
 */
class CurlLinkChecker implements LinkChecker {

	/**
	 * Return cache
	 *
	 * @return Zend_Cache_Frontend
	 */
	protected function getCache() {
		return SS_Cache::factory(
			__CLASS__,
			'Output',
			array('automatic_serialization' => true)
		);
	}

	/**
	 * Determine the http status code for a given link
	 *
	 * @param string $href URL to check
	 * @return int HTTP status code, or null if not checkable (not a link)
	 */
	public function checkLink($href) {
		// Skip non-external links
		if(!preg_match('/^https?[^:]*:\/\//', $href)) return null;

		if (!Config::inst()->get('CurlLinkChecker', 'BypassCache')) {
			// Check if we have a cached result
			$cacheKey = md5($href);
			$result = $this->getCache()->load($cacheKey);
			if($result !== false) return $result;
		}

		// No cached result so just request
		$handle = curl_init($href);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
		// do we want to follow any redirect locations eg http to https
		if (Config::inst()->get('CurlLinkChecker', 'FollowLocation')) {
			curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);
		}
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($handle, CURLOPT_TIMEOUT, 10);
		curl_exec($handle);
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);

		if (!Config::inst()->get('CurlLinkChecker', 'BypassCache')) {
			// Cache result
			$this->getCache()->save($httpCode, $cacheKey);
		}
		return $httpCode;
	}
}
