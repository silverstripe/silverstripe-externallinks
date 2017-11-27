<?php

namespace SilverStripe\ExternalLinks\Tests;

use SilverStripe\Dev\TestOnly;
use Page;

class ExternalLinksTestPage extends Page implements TestOnly
{
    private static $table_name = 'ExternalLinksTestPage';

    private static $db = array(
        'ExpectedContent' => 'HTMLText'
    );
}
