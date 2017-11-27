<?php
namespace SilverStripe\ExternalLinks\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ExternalLinks\Tasks\LinkChecker;

class PretendLinkChecker implements LinkChecker, TestOnly
{
    public function checkLink($href)
    {
        switch ($href) {
            case 'http://www.working.com':
                return 200;
            case 'http://www.broken.com':
                return 403;
            case 'http://www.nodomain.com':
                return 0;
            case '/internal/link':
            case '[sitetree_link,id=9999]':
            case 'home':
            case 'broken-internal':
            case '[sitetree_link,id=1]':
                return null;
            case 'http://www.broken.com/url/thing':
            default:
                return 404;
        }
    }
}
