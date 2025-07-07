<?php

namespace IGE\ChannelLister\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'channel-lister:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel-lister:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Channel Lister resources';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->comment('Publishing Channel Lister Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'channel-lister-provider']);

        $this->comment('Publishing Channel Lister Assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'channel-lister-assets']);

        $this->comment('Publishing Channel Lister Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'channel-lister-config']);

        $this->comment('Publishing Channel Lister Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'channel-lister-migrations']);

        $this->registerChannelListerServiceProvider();

        $this->info('Channel Lister scaffolding installed successfully.');
    }

    /**
     * Register the Channel Lister service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerChannelListerServiceProvider()
    {
        if ($this->addProviderToBootstrapFile()) {
            return;
        }

        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if ($appConfig === false) {
            return;
        }

        if (Str::contains($appConfig, $namespace.'\\Providers\\ChannelListerServiceProvider::class')) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        /**
         * Add the namespaced ChannelListerServiceProvider to the app.php config file.
         */
        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol,
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol."        {$namespace}\Providers\ChannelListerServiceProvider::class,".$eol,
            $appConfig
        ));

        if (! file_exists(app_path('Providers/ChannelListerServiceProvider.php'))) {
            return;
        }

        $contents = file_get_contents(app_path('Providers/ChannelListerServiceProvider.php'));

        if ($contents === false) {
            return;
        }

        file_put_contents(app_path('Providers/ChannelListerServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            $contents
        ));
    }

    protected function addProviderToBootstrapFile(): bool
    {
        if (method_exists(ServiceProvider::class, 'addProviderToBootstrapFile')) {
            return false;
        }

        return ServiceProvider::addProviderToBootstrapFile(
            \App\Providers\ChannelListerServiceProvider::class // @phpstan-ignore-line
        );
    }
}
