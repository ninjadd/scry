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
            ->addOption('no-open', null, InputOption::VALUE_NONE, 'Do not automatically open default web browser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

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
        $host = $input->getOption('host') ?: '127.0.0.1';
        $initialPort = (int) ($input->getOption('port') ?: 8080);
        $port = $this->findAvailablePort($host, $initialPort);

        $url = "http://{$host}:{$port}";

        // Banner Output
        $output->writeln('');
        $output->writeln('  <fg=cyan;options=bold>┌────────────────────────────────────────────────────────┐</>');
        $output->writeln('  <fg=cyan;options=bold>│</>  <fg=bright-white;options=bold>SCRY DATABASE MANAGER</> <fg=gray>(CLI Standalone Workbench)</>      <fg=cyan;options=bold>│</>');
        $output->writeln('  <fg=cyan;options=bold>└────────────────────────────────────────────────────────┘</>');
        $output->writeln('');
        $output->writeln("  <fg=gray>Driver:</>   <fg=bright-green;options=bold>" . strtoupper($driver) . "</>");
        $output->writeln("  <fg=gray>Target:</>   <fg=bright-yellow>" . ($dbName ?: '(default)') . "</>");
        $output->writeln("  <fg=gray>Workbench:</><fg=bright-cyan;options=bold> {$url}</>");
        $output->writeln('');
        $output->writeln('  <fg=gray>Press</> <fg=yellow>Ctrl+C</> <fg=gray>to stop the server.</>');
        $output->writeln('');

        // Open browser automatically unless --no-open
        if (!$input->getOption('no-open')) {
            $this->openBrowser($url);
        }

        // Launch PHP Built-in Server
        $serverScript = __DIR__ . '/server.php';
        $phpBinary = PHP_BINARY ?: 'php';

        $env = [
            'SCRY_CONNECTIONS_JSON' => json_encode($connections),
            'SCRY_TARGET' => $target ?? '',
        ];

        $command = [$phpBinary, '-S', "{$host}:{$port}", $serverScript];

        $process = new Process($command, getcwd(), $env, null, null);
        $process->setTimeout(null);

        // Handle clean termination
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () use ($process, $output) {
                $output->writeln("\n  <fg=yellow>Stopping Scry server...</>");
                $process->stop();
                exit(0);
            });
            pcntl_signal(SIGTERM, function () use ($process, $output) {
                $process->stop();
                exit(0);
            });
        }

        $process->run(function ($type, $buffer) use ($output) {
            // Optional: filter PHP built-in server output to keep terminal clean
            if (OutputInterface::VERBOSITY_VERY_VERBOSE <= $output->getVerbosity()) {
                $output->write($buffer);
            }
        });

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
        $os = PHP_OS_FAMILY;

        $command = match ($os) {
            'Darwin' => "open '{$url}' > /dev/null 2>&1 &",
            'Windows' => "start \"\" \"{$url}\"",
            default => "xdg-open '{$url}' > /dev/null 2>&1 &",
        };

        @exec($command);
    }
}
