<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

class SeedAllDatabasesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scry:seed-all {--fresh : Run migrate:fresh on all connections before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate and seed dummy data across all 5 supported database engines (pgsql, mysql, mariadb, sqlite, sqlsrv)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $connections = ['pgsql', 'mysql', 'mariadb', 'sqlite', 'sqlsrv'];
        $fresh = $this->option('fresh');

        $this->info('Starting multi-database migration and seeding for Scry...');

        foreach ($connections as $conn) {
            if (!Config::has("database.connections.{$conn}")) {
                $this->warn("Skipping [{$conn}]: Connection configuration not found.");
                continue;
            }

            $this->line('');
            $this->info("--------------------------------------------------");
            $this->info("Processing Database Connection: [{$conn}]");
            $this->info("--------------------------------------------------");

            try {
                // Ensure SQLite file exists if connection is sqlite
                if ($conn === 'sqlite') {
                    $sqlitePath = config('database.connections.sqlite.database');
                    if ($sqlitePath && !file_exists($sqlitePath)) {
                        $dir = dirname($sqlitePath);
                        if (!file_exists($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        touch($sqlitePath);
                        $this->info("Created SQLite database file at {$sqlitePath}");
                    }
                }

                // Ensure SQL Server database exists if connection is sqlsrv
                if ($conn === 'sqlsrv') {
                    try {
                        config(['database.connections.sqlsrv_temp' => array_merge(
                            config('database.connections.sqlsrv'),
                            ['database' => 'master']
                        )]);
                        $dbName = config('database.connections.sqlsrv.database', 'scry_sql_db');
                        DB::connection('sqlsrv_temp')->statement("IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = N'{$dbName}') CREATE DATABASE [{$dbName}];");
                        $this->info("Ensured SQL Server database [{$dbName}] exists.");
                    } catch (\Throwable $e) {
                        $this->warn("SQL Server database creation check: " . $e->getMessage());
                    }
                }

                // Test connection
                DB::connection($conn)->getPdo();

                if ($fresh) {
                    $this->comment("Running migrate:fresh on [{$conn}]...");
                    Artisan::call('migrate:fresh', [
                        '--database' => $conn,
                        '--force' => true,
                    ]);
                    $this->line(trim(Artisan::output()));
                } else {
                    $this->comment("Running migrate on [{$conn}]...");
                    Artisan::call('migrate', [
                        '--database' => $conn,
                        '--force' => true,
                    ]);
                    $this->line(trim(Artisan::output()));
                }

                $this->comment("Seeding dummy data on [{$conn}]...");
                Artisan::call('db:seed', [
                    '--database' => $conn,
                    '--force' => true,
                ]);
                $this->line(trim(Artisan::output()));

                $this->info("Successfully migrated & seeded [{$conn}].");
            } catch (Throwable $e) {
                $this->error("Failed to migrate/seed connection [{$conn}]: " . $e->getMessage());
            }
        }

        $this->info('');
        $this->info('All database connections have been processed successfully!');
        return Command::SUCCESS;
    }
}
