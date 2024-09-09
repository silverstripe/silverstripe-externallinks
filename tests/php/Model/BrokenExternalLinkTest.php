<?php

namespace SilverStripe\ExternalLinks\Tests\Model;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ExternalLinks\Model\BrokenExternalLink;
use PHPUnit\Framework\Attributes\DataProvider;

class BrokenExternalLinkTest extends SapphireTest
{
    #[DataProvider('httpCodeProvider')]
    public function testGetHTTPCodeDescription(int $httpCode, string $expected)
    {
        $link = new BrokenExternalLink();
        $link->HTTPCode = $httpCode;
        $this->assertSame($expected, $link->getHTTPCodeDescription());
    }
    
    public static function httpCodeProvider(): array
    {
        return [
            [200, '200 (OK)'],
            [302, '302 (Found)'],
            [404, '404 (Not Found)'],
            [500, '500 (Internal Server Error)'],
            [789, '789 (Unknown Response Code)'],
        ];
    }

    public static function permissionProvider(): array
    {
        return [
            ['admin', 'ADMIN'],
            ['content-author', 'CMS_ACCESS_CMSMain'],
            ['asset-admin', 'CMS_ACCESS_AssetAdmin'],
        ];
    }

    #[DataProvider('permissionProvider')]
    public function testCanViewReport(string $user, string $permission)
    {
        $this->logOut();
        $this->logInWithPermission($permission);

        $link = new BrokenExternalLink();
        
        if ($user === 'asset-admin') {
            $this->assertFalse($link->canView());
        } else {
            $this->assertTrue($link->canView());
        }
    }
}
