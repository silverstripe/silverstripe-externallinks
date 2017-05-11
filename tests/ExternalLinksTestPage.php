<?php

class ExternalLinksTestPage extends Page implements TestOnly
{
    private static $db = array(
        'ExpectedContent' => 'HTMLText'
    );
}
