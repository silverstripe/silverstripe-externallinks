<?php

namespace SilverStripe\ExternalLinks\Tests\Stubs;

use Page;
use SilverStripe\Dev\TestOnly;

class ExternalLinksTestPage extends Page implements TestOnly
{
    private static $table_name = 'ExternalLinksTestPage';

    private static $db = array(
        'ExpectedContent' => 'HTMLText'
    );
}
