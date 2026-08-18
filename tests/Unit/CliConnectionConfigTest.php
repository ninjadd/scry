<?php

namespace Scry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Scry\Cli\ConnectionConfig;

class CliConnectionConfigTest extends TestCase
{
    public function test_it_parses_postgres_dsn(): void
    {
        $dsn = 'postgres://admin:secret123@db.example.com:5433/prod_database?sslmode=require';
        $config = ConnectionConfig::fromDsn($dsn);

        $this->assertEquals('pgsql', $config['driver']);
        $this->assertEquals('db.example.com', $config['host']);
        $this->assertEquals(5433, $config['port']);
        $this->assertEquals('prod_database', $config['database']);
        $this->assertEquals('admin', $config['username']);
        $this->assertEquals('secret123', $config['password']);
        $this->assertEquals('require', $config['sslmode']);
    }

    public function test_it_parses_mysql_dsn(): void
    {
        $dsn = 'mysql://root:mypass@127.0.0.1:3306/ecommerce_db';
        $config = ConnectionConfig::fromDsn($dsn);

        $this->assertEquals('mysql', $config['driver']);
        $this->assertEquals('127.0.0.1', $config['host']);
        $this->assertEquals(3306, $config['port']);
        $this->assertEquals('ecommerce_db', $config['database']);
        $this->assertEquals('root', $config['username']);
        $this->assertEquals('mypass', $config['password']);
    }

    public function test_it_parses_mariadb_dsn(): void
    {
        $dsn = 'mariadb://admin:pass@127.0.0.1:3307/maria_store';
        $config = ConnectionConfig::fromDsn($dsn);

        $this->assertEquals('mariadb', $config['driver']);
        $this->assertEquals('127.0.0.1', $config['host']);
        $this->assertEquals(3307, $config['port']);
        $this->assertEquals('maria_store', $config['database']);
    }

    public function test_it_parses_sqlsrv_dsn(): void
    {
        $dsn = 'sqlsrv://sa:SecretPass!@localhost:1433/enterprise_db';
        $config = ConnectionConfig::fromDsn($dsn);

        $this->assertEquals('sqlsrv', $config['driver']);
        $this->assertEquals('localhost', $config['host']);
        $this->assertEquals(1433, $config['port']);
        $this->assertEquals('enterprise_db', $config['database']);
        $this->assertEquals('sa', $config['username']);
    }

    public function test_it_parses_sqlite_path(): void
    {
        $config = ConnectionConfig::fromSqlitePath(':memory:');
        $this->assertEquals('sqlite', $config['driver']);
        $this->assertEquals(':memory:', $config['database']);

        $config2 = ConnectionConfig::fromSqlitePath('database.sqlite');
        $this->assertEquals('sqlite', $config2['driver']);
        $this->assertStringContainsString('database.sqlite', $config2['database']);
    }

    public function test_it_parses_flags(): void
    {
        $options = [
            'driver' => 'pgsql',
            'host' => '10.0.0.5',
            'port' => 5432,
            'database' => 'analytics',
            'username' => 'analyst',
            'password' => 'pass123',
        ];

        $config = ConnectionConfig::fromFlags($options);
        $this->assertEquals('pgsql', $config['driver']);
        $this->assertEquals('10.0.0.5', $config['host']);
        $this->assertEquals(5432, $config['port']);
        $this->assertEquals('analytics', $config['database']);
        $this->assertEquals('analyst', $config['username']);
        $this->assertEquals('pass123', $config['password']);
    }

    public function test_it_identifies_dsn_and_sqlite(): void
    {
        $this->assertTrue(ConnectionConfig::isDsn('mysql://root@127.0.0.1/db'));
        $this->assertTrue(ConnectionConfig::isDsn('postgres://user:pass@localhost:5432/app'));
        $this->assertTrue(ConnectionConfig::isDsn('sqlite:///tmp/test.db'));
        $this->assertFalse(ConnectionConfig::isDsn('./test.sqlite'));

        $this->assertTrue(ConnectionConfig::isSqliteFile(':memory:'));
        $this->assertTrue(ConnectionConfig::isSqliteFile('app.sqlite'));
        $this->assertTrue(ConnectionConfig::isSqliteFile('data.db'));
        $this->assertFalse(ConnectionConfig::isSqliteFile('mysql://root@127.0.0.1/db'));
    }

    public function test_it_parses_env_file(): void
    {
        $tempEnv = tempnam(sys_get_temp_dir(), 'scry_env_');
        file_put_contents($tempEnv, "DB_CONNECTION=pgsql\nDB_HOST=127.0.0.1\nDB_PORT=5432\nDB_DATABASE=testing_db\nDB_USERNAME=pguser\nDB_PASSWORD=secret\n");

        $connections = ConnectionConfig::fromEnvFile($tempEnv);
        unlink($tempEnv);

        $this->assertArrayHasKey('default', $connections);
        $this->assertEquals('pgsql', $connections['default']['driver']);
        $this->assertEquals('testing_db', $connections['default']['database']);
        $this->assertEquals('pguser', $connections['default']['username']);
        $this->assertEquals('secret', $connections['default']['password']);
    }
}
