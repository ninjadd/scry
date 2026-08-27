<?php

namespace Scry\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ServeCommand extends Command
{
    protected static $defaultName = 'serve';

    protected function configure(): void
    {
        $this
            ->setName('serve')
            ->setDescription('Launch the Scry visual database manager web workbench')
            ->addArgument('target', InputArgument::OPTIONAL, 'Database file path, DSN, or connection string (e.g., ./app.sqlite, postgres://user:pass@localhost:5432/db)')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Port to run the Scry web interface on', '8080')
            ->addOption('host', 'H', InputOption::VALUE_REQUIRED, 'Host interface to bind to', '127.0.0.1')
            ->addOption('driver', 'd', InputOption::VALUE_REQUIRED, 'Database driver (mysql, pgsql, sqlite, sqlsrv, mariadb)')
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'Database name or SQLite file path')
            ->addOption('username', 'u', InputOption::VALUE_REQUIRED, 'Database username')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Database password')
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Path to .env configuration file')
            ->addOption('no-open', null, InputOption::VALUE_NONE, 'Do not automatically open default web browser')
            ->addOption('allow-remote', null, InputOption::VALUE_NONE, 'Allow binding to a non-loopback host interface (required alongside --host for anything other than 127.0.0.1/localhost/::1)');
    }

    /**
     * Determine whether a host string refers to the local loopback interface.
     */
    public static function isLoopbackHost(string $host): bool
    {
        return in_array(strtolower(trim($host)), ['127.0.0.1', 'localhost', '::1', '[::1]'], true);
    }

    /**
     * Write connection configs (which may contain plaintext credentials) to a
     * private temp file rather than a child-process env var, since env vars
     * set on a process are readable by any local process running as the same
     * user (e.g. via /proc/<pid>/environ) for as long as the process lives.
     */
    public static function writeConnectionsTempFile(array $connections): string
    {
        $path = tempnam(sys_get_temp_dir(), 'scry_conn_');
        chmod($path, 0600);
        file_put_contents($path, json_encode($connections));

        return $path;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $host = $input->getOption('host') ?: '127.0.0.1';
        if (!self::isLoopbackHost($host) && !$input->getOption('allow-remote')) {
            $io->error([
                "Refusing to bind to non-loopback host '{$host}'.",
                'Scry has no login system — anyone who can reach this address gets full unauthenticated database access.',
                'Pass --allow-remote to confirm you understand the risk and want to proceed anyway.',
            ]);
            return Command::FAILURE;
        }

        $target = $input->getArgument('target');
        $options = [
            'driver' => $input->getOption('driver'),
            'host' => $input->getOption('host'),
            'port' => $input->getOption('port'),
            'database' => $input->getOption('database'),
            'username' => $input->getOption('username'),
            'password' => $input->getOption('password'),
            'env' => $input->getOption('env'),
        ];

        $connections = ConnectionConfig::resolveConnections($target, $options);
        $defaultConnName = array_key_first($connections);
        $defaultConn = $connections[$defaultConnName] ?? [];
        $driver = $defaultConn['driver'] ?? 'sqlite';
        $dbName = $defaultConn['database'] ?? ':memory:';

        // Find an open port
        $initialPort = (int) ($input->getOption('port') ?: 8080);
        $port = $this->findAvailablePort($host, $initialPort);

        $url = "http://{$host}:{$port}";
        $token = bin2hex(random_bytes(24));
        $authenticatedUrl = "{$url}/?token={$token}";

        // Banner Output
        $output->writeln('');
        $output->writeln('  <fg=cyan;options=bold>┌────────────────────────────────────────────────────────┐</>');
        $output->writeln('  <fg=cyan;options=bold>│</>  <fg=bright-white;options=bold>SCRY DATABASE MANAGER</> <fg=gray>(CLI Standalone Workbench)</>      <fg=cyan;options=bold>│</>');
        $output->writeln('  <fg=cyan;options=bold>└────────────────────────────────────────────────────────┘</>');
        $output->writeln('');
        $output->writeln("  <fg=gray>Driver:</>   <fg=bright-green;options=bold>" . strtoupper($driver) . "</>");
        $output->writeln("  <fg=gray>Target:</>   <fg=bright-yellow>" . ($dbName ?: '(default)') . "</>");
        $output->writeln("  <fg=gray>Workbench:</><fg=bright-cyan;options=bold> {$url}</>");
        $output->writeln("  <fg=gray>Auth Token:</><fg=bright-magenta> {$token}</>");
        $output->writeln('');
        $output->writeln('  <fg=gray>Press</> <fg=yellow>Ctrl+C</> <fg=gray>to stop the server.</>');
        $output->writeln('');

        // Open browser automatically unless --no-open
        if (!$input->getOption('no-open')) {
            $this->openBrowser($authenticatedUrl);
        }

        // Launch PHP Built-in Server
        $serverScript = __DIR__ . '/server.php';
        $phpBinary = PHP_BINARY ?: 'php';

        $connectionsFile = self::writeConnectionsTempFile($connections);

        $env = [
            'SCRY_CONNECTIONS_FILE' => $connectionsFile,
            'SCRY_TARGET' => $target ?? '',
            'SCRY_AUTH_TOKEN' => $token,
        ];

        $command = [$phpBinary, '-S', "{$host}:{$port}", $serverScript];

        $process = new Process($command, getcwd(), $env, null, null);
        $process->setTimeout(null);

        // Handle clean termination
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () use ($process, $output, $connectionsFile) {
                $output->writeln("\n  <fg=yellow>Stopping Scry server...</>");
                $process->stop();
                @unlink($connectionsFile);
                exit(0);
            });
            pcntl_signal(SIGTERM, function () use ($process, $connectionsFile) {
                $process->stop();
                @unlink($connectionsFile);
                exit(0);
            });
        }

        try {
            $process->run(function ($type, $buffer) use ($output) {
                // Optional: filter PHP built-in server output to keep terminal clean
                if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity()) {
                    $output->write($buffer);
                }
            });
        } finally {
            @unlink($connectionsFile);
        }

        return Command::SUCCESS;
    }

    /**
     * Find an available port if the requested port is occupied.
     */
    protected function findAvailablePort(string $host, int $startPort): int
    {
        $port = $startPort;
        $maxPort = $startPort + 50;

        while ($port < $maxPort) {
            $connection = @fsockopen($host, $port, $errno, $errstr, 0.1);
            if (!is_resource($connection)) {
                return $port; // Port is free!
            }
            fclose($connection);
            $port++;
        }

        return $startPort;
    }

    /**
     * Open the default web browser on macOS, Linux, or Windows.
     */
    protected function openBrowser(string $url): void
    {
        @exec(self::buildOpenCommand($url, PHP_OS_FAMILY));
    }

    /**
     * Build the shell command used to open a URL in the system's default browser,
     * safely quoting the URL so it can't be used to inject additional shell commands.
     */
    public static function buildOpenCommand(string $url, string $osFamily): string
    {
        $escaped = escapeshellarg($url);

        return match ($osFamily) {
            'Darwin' => "open {$escaped} > /dev/null 2>&1 &",
            'Windows' => "start \"\" {$escaped}",
            default => "xdg-open {$escaped} > /dev/null 2>&1 &",
        };
    }
}
