<?php

namespace Scry\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scry:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Scry Database Manager resources and assets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->comment('Publishing Scry Configuration...');
        $this->call('vendor:publish', ['--tag' => 'scry-config']);

        $this->comment('Publishing Scry Assets...');
        $this->call('vendor:publish', ['--tag' => 'scry-assets', '--force' => true]);

        $this->info('Scry Database Manager installed successfully!');
        $this->line('');
        $this->line('You can now access Scry at: <comment>' . url(config('scry.path', 'scry')) . '</comment>');

        return 0;
    }
}
