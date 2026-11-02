<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PwaFilesTest extends TestCase
{
    private function publicPath(string $file): string
    {
        return dirname(__DIR__, 2).'/public/'.$file;
    }

    public function test_manifest_has_required_install_fields(): void
    {
        $manifest = json_decode(file_get_contents($this->publicPath('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);
        $sizes = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists($this->publicPath(ltrim($icon['src'], '/')));
        }
    }

    public function test_service_worker_only_caches_public_static_files(): void
    {
        $sw = file_get_contents($this->publicPath('sw.js'));

        $this->assertStringContainsString("request.method !== 'GET'", $sw);
        $this->assertStringContainsString("startsWith('/build/')", $sw);
        $this->assertFileExists($this->publicPath('offline.html'));

        foreach (['/admin', '/checkout', '/cart', '/orders', '/account', '/delivery'] as $private) {
            $this->assertStringNotContainsString("'{$private}", $sw);
        }
    }
}
