<?php

namespace Coyotito\LaravelSettings\Console\Commands;

use Coyotito\LaravelSettings\SettingsManager;
use Coyotito\LaravelSettings\SettingsManifest;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CacheSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:settings { --force : Overwrite any existing cached settings file }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache the registered settings for faster access';

    public function __construct(protected Filesystem $files, protected SettingsManager $manager)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $manifest = $this->makeManifest();

        if ($manifest->present()) {
            if ($this->option('force') !== true) {
                $this->components->error('Settings cache already exists! Use the --force option to overwrite it.');

                return self::SUCCESS;
            }

            $this->files->delete($this->getCachePath());

            $this->components->alert('Existing settings cache cleared.');
        }

        $this->generateCache($manifest);

        return self::SUCCESS;
    }

    protected function generateCache(SettingsManifest $manifest): void
    {
        $manifest->generate($this->manager->registry->settings);

        $this->components->info('Settings cached successfully!');
    }

    protected function makeManifest(): SettingsManifest
    {
        return new SettingsManifest($this->files, $this->getCachePath());
    }

    protected function getCachePath(): string
    {
        return base_path('bootstrap/cache/settings.php');
    }
}
