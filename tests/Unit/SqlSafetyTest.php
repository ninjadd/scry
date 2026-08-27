<?php

namespace Scry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Scry\Support\SqlSafety;

class SqlSafetyTest extends TestCase
{
    public function test_quote_identifier_escapes_double_quotes_for_default_driver(): void
    {
        $this->assertEquals('"col""name"', SqlSafety::quoteIdentifier('pgsql', 'col"name'));
        $this->assertEquals('"col""name"', SqlSafety::quoteIdentifier('sqlite', 'col"name'));
    }

    public function test_quote_identifier_escapes_backticks_for_mysql_and_mariadb(): void
    {
        $this->assertEquals('`col``name`', SqlSafety::quoteIdentifier('mysql', 'col`name'));
        $this->assertEquals('`col``name`', SqlSafety::quoteIdentifier('mariadb', 'col`name'));
    }

    public function test_quote_identifier_escapes_brackets_for_sqlsrv(): void
    {
        $this->assertEquals('[col]]name]', SqlSafety::quoteIdentifier('sqlsrv', 'col]name'));
    }

    public function test_quote_identifier_neutralizes_a_breakout_attempt(): void
    {
        // A naive `"{$name}"` wrap would let this identifier close the quote and
        // inject a second statement. Escaped, it must stay a single identifier token.
        $malicious = '"; DROP TABLE users; --';
        $quoted = SqlSafety::quoteIdentifier('pgsql', $malicious);

        $this->assertEquals('"""; DROP TABLE users; --"', $quoted);
    }

    public function test_quote_literal_escapes_single_quotes(): void
    {
        $this->assertEquals("'O''Brien'", SqlSafety::quoteLiteral("O'Brien"));
    }

    public function test_quote_literal_neutralizes_a_breakout_attempt(): void
    {
        $malicious = "'; DROP TABLE users; --";
        $quoted = SqlSafety::quoteLiteral($malicious);

        $this->assertEquals("'''; DROP TABLE users; --'", $quoted);
    }
}
