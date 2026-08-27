<?php

namespace Scry\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Scry\Cli\ServeCommand;

class CliServeCommandTest extends TestCase
{
    #[DataProvider('loopbackHostProvider')]
    public function test_is_loopback_host(string $host, bool $expected): void
    {
        $this->assertEquals($expected, ServeCommand::isLoopbackHost($host));
    }

    public static function loopbackHostProvider(): array
    {
        return [
            'ipv4 loopback' => ['127.0.0.1', true],
            'localhost name' => ['localhost', true],
            'localhost mixed case' => ['LocalHost', true],
            'ipv6 loopback' => ['::1', true],
            'ipv6 loopback bracketed' => ['[::1]', true],
            'wildcard' => ['0.0.0.0', false],
            'lan address' => ['192.168.1.5', false],
            'public address' => ['8.8.8.8', false],
        ];
    }

    public function test_write_connections_temp_file_is_private_and_contains_the_connections(): void
    {
        $connections = ['default' => ['driver' => 'mysql', 'password' => 'super-secret']];

        $path = ServeCommand::writeConnectionsTempFile($connections);

        try {
            $this->assertFileExists($path);
            $this->assertEquals('0600', substr(sprintf('%o', fileperms($path)), -4));
            $this->assertEquals($connections, json_decode(file_get_contents($path), true));
        } finally {
            @unlink($path);
        }
    }

    public function test_build_open_command_safely_quotes_a_url_with_shell_metacharacters(): void
    {
        $malicious = "http://127.0.0.1:8080/?token=abc'; rm -rf / #";

        $command = ServeCommand::buildOpenCommand($malicious, 'Linux');

        // The whole URL, metacharacters included, must be inert inside a single-quoted shell arg.
        $this->assertStringContainsString(escapeshellarg($malicious), $command);
        $this->assertStringStartsWith('xdg-open ', $command);
    }

    public function test_build_open_command_uses_open_on_darwin_and_start_on_windows(): void
    {
        $this->assertStringStartsWith('open ', ServeCommand::buildOpenCommand('http://127.0.0.1:8080', 'Darwin'));
        $this->assertStringStartsWith('start "" ', ServeCommand::buildOpenCommand('http://127.0.0.1:8080', 'Windows'));
    }
}
