<?php

declare(strict_types=1);

namespace Grav\Plugin\Api\Tests\Unit\Services;

use Grav\Common\Config\Config;
use Grav\Common\Grav;
use Grav\Plugin\Api\Services\PreferencesResolver;
use Grav\Plugin\Api\Tests\Unit\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PreferencesResolver::class)]
class PreferencesResolverSiteDefaultsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/grav_api_prefs_' . uniqid();
        mkdir($this->root . '/user/config', 0775, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->root . '/user/config/admin-next.yaml');
        @rmdir($this->root . '/user/config');
        @rmdir($this->root . '/user');
        @rmdir($this->root);
        Grav::resetInstance();
    }

    #[Test]
    public function saving_site_defaults_merges_instead_of_replacing(): void
    {
        $resolver = $this->resolver();

        $resolver->saveSitePreferences(['accentHue' => 120, 'fontFamily' => 'inter']);
        $resolver->saveSitePreferences(['fontSize' => 'large']);

        $site = $resolver->sitePreferences();
        self::assertSame(120, $site['accentHue']);
        self::assertSame('inter', $site['fontFamily']);
        self::assertSame('large', $site['fontSize']);
    }

    #[Test]
    public function null_clears_a_saved_site_default(): void
    {
        $resolver = $this->resolver();

        $resolver->saveSitePreferences(['accentHue' => 120, 'fontFamily' => 'inter']);
        $resolver->saveSitePreferences(['accentHue' => null]);

        $site = $resolver->sitePreferences();
        self::assertSame(271, $site['accentHue']);
        self::assertSame('inter', $site['fontFamily']);
    }

    #[Test]
    public function branding_urls_follow_the_user_stream(): void
    {
        self::assertSame('/user/media/admin-next/logo.svg', $this->resolver('user')->brandingMediaUrl('logo.svg'));
        self::assertSame('/user/sites/blog/media/admin-next/logo.svg', $this->resolver('user/sites/blog')->brandingMediaUrl('../logo.svg'));
        // A user folder outside the webroot has no public URL; keep the default.
        self::assertSame('/user/media/admin-next/logo.svg', $this->resolver('/srv/grav-user')->brandingMediaUrl('logo.svg'));
        self::assertSame('', $this->resolver()->brandingMediaUrl(''));
    }

    private function resolver(string $userRelative = 'user'): PreferencesResolver
    {
        $root = $this->root;
        $locator = new class ($root, $userRelative) {
            public function __construct(private readonly string $root, private readonly string $userRelative)
            {
            }

            public function findResource(string $uri, bool $absolute = true, bool $create = false): string|false
            {
                return match ($uri) {
                    'user://' => $absolute ? $this->root . '/user' : $this->userRelative,
                    'user://config' => $this->root . '/user/config',
                    default => false,
                };
            }
        };

        $grav = TestHelper::createMockGrav(['locator' => $locator, 'config' => new Config([])]);

        return new PreferencesResolver($grav);
    }
}
