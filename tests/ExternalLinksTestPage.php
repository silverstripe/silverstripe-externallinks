<?php

namespace SilverStripe\ExternalLinks\Tests;

use SilverStripe\Dev\TestOnly;

class ExternalLinksTestPage extends Page implements TestOnly
{
    private static $db = array(
        'ExpectedContent' => 'HTMLText'
    );
}
