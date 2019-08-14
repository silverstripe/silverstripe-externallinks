<?php

namespace SilverStripe\ExternalLinks\Tests\Model;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ExternalLinks\Model\BrokenExternalLink;

class BrokenExternalLinkTest extends SapphireTest
{
    /**
     * @param int $httpCode
     * @param string $expected
     * @dataProvider httpCodeProvider
     */
    public function testGetHTTPCodeDescription($httpCode, $expected)
    {
        $link = new BrokenExternalLink();
        $link->HTTPCode = $httpCode;
        $this->assertSame($expected, $link->getHTTPCodeDescription());
    }

    /**
     * @return array[]
     */
    public function httpCodeProvider()
    {
        return [
            [200, '200 (OK)'],
            [302, '302 (Found)'],
            [404, '404 (Not Found)'],
            [500, '500 (Internal Server Error)'],
            [789, '789 (Unknown Response Code)'],
        ];
    }
}
