<?php

namespace Conductor\Commands;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
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

    protected string $package;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // check if conductor is latest version...

        if (! $this->arguments()['package']) {
            $this->call(SummaryCommand::class);

            return;
        }

        $this->package = $this->arguments()['package'];

        $binary = $this->getBinaryName();

        if ($this->packageIsInstalledLocally()) {
            $this->info('package is already installed locally. running...');
            $cmd = "vendor/bin/{$binary} {$this->arguments()['thing']} {$this->arguments()['argument']}";
            $result = Process::forever()->tty()->run($cmd);

            return $result->exitCode();
        }

        if ($this->packageIsInstalledGlobally()) {
            $this->info('package is already installed globally. running...');
            $cmd = "{$binary} {$this->arguments()['thing']} {$this->arguments()['argument']}";
            $result = Process::forever()->tty()->run($cmd);

            return $result->exitCode();
        }

        $this->info('update globally installed package?..');
        $this->info('keep package installed after running?..');
        $this->info('installing package globally...');
        $this->info('running binary...');
        $this->info('uninstalling package globally...');
        $this->info('Command ran successfully!');
    }

    protected function getBinaryName(): string
    {
        $this->info('checking package for binary...');
        $response = Http::get("https://packagist.org/packages/{$this->package}.json");
        $json = $response->json();
        $versions = $json['package']['versions'];
        $latest = $versions[array_keys($versions)[1]]; // [0] is `dev-master` or `dev-main`
        $binary = $latest['bin'][0];
        // $this->info('Binary name: ' . $binary);

        return explode('/', $binary)[1];
    }

    protected function packageIsInstalledLocally(): bool
    {
        $this->info('checking for locally installed package...');
        exec("composer show {$this->arguments()['package']} 2>&1", $output, $return_code);

        return $return_code === 0;
    }

    protected function packageIsInstalledGlobally(): bool
    {
        $this->info('checking for globally installed package...');
        exec("composer global show {$this->arguments()['package']} 2>&1", $output, $return_code);

        return $return_code === 0;
    }
}
