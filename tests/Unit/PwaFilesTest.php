<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PwaFilesTest extends TestCase
{
    private function publicPath(string $file): string
    {
        return dirname(__DIR__, 2).'/public/'.$file;
    }

    public function test_app_is_not_installable(): void
    {
        // Installing as an app is switched off: no web app manifest is published.
        $this->assertFileDoesNotExist($this->publicPath('manifest.webmanifest'));
        $this->assertStringNotContainsString('manifest', file_get_contents($this->publicPath('sw.js')));
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
