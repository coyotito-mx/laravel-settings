<?php

namespace Coyotito\LaravelSettings\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ClearCacheSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:settings:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the settings cache';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->files->exists($this->getCachePath())) {
            $this->files->delete($this->getCachePath());

            $this->components->success('Settings cache cleared successfully.');
        } else {
            $this->components->warn('No settings cache found to clear.');
        }

        return self::SUCCESS;
    }

    protected function getCachePath(): string
    {
        return base_path('bootstrap/cache/settings.php');
    }
}
