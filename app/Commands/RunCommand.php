<?php

namespace Conductor\Commands;

use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use NunoMaduro\LaravelConsoleSummary\SummaryCommand;

class RunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run {package?} {thing?} {argument?} {--option=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and run a composer package.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->arguments()['package']) {
            $this->call(SummaryCommand::class);
            return;
        }

        $this->info('checking package for binary...');
        $this->getBinaryName();

        $this->info('checking for globally installed package...');
        $this->info('update globally installed package?..');
        $this->info('keep package installed after running?..');
        $this->info('installing package globally...');
        $this->info('running binary...');
        $this->info('uninstalling package globally...');
        $this->info('Command ran successfully!');
    }

    protected function getBinaryName()
    {
        $package = $this->arguments()['package'];
        $response = Http::get("https://packagist.org/packages/{$package}.json");
        $json = $response->json();
        $versions = $json['package']['versions'];
        $latest = $versions[array_keys($versions)[1]]; // [0] is `dev-master` or `dev-main`
        $binary = $latest['bin'][0];
        $this->info('Binary name: ' . $binary);
    }
}
