<?php

/**
 * Provides an interface for checking that a link is valid
 */
interface LinkChecker {
	
	/**
	 * Determine the http status code for a given link
	 *
	 * @param string $href URL to check
	 * @return int HTTP status code, or null if not checkable (not a link)
	 */
	public function checkLink($href);
}
