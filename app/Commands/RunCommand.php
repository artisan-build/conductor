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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // TODO
        // check if conductor is latest version...

        if (! $this->arguments()['package']) {
            $this->call(SummaryCommand::class);

            return;
        }

        $package = $this->arguments()['package'];
        $package_data = $this->getLatestPackageData($package);
        $keywords = $package_data['keywords'];
        $binary = $this->getBinaryName($package_data);
        $command = $this->arguments()['thing'];
        $arguments = $this->arguments()['argument'];

        if ($this->packageIsInstalledLocally($package)) {
            $this->info('package is already installed locally. running...');
            $cmd = "vendor/bin/{$binary} {$command} {$arguments}";
            $result = Process::forever()->tty()->run($cmd);

            return $result->exitCode();
        }

        if ($this->packageIsInstalledGlobally($package)) {
            $this->info('package is already installed globally. running...');
            $cmd = "{$binary} {$command} {$arguments}";
            $result = Process::forever()->tty()->run($cmd);

            return $result->exitCode();
        }

        // TODO
        $this->info('update globally installed package?..');

        $should_keep_package_installed = $this->confirm(
            question: 'keep package installed after running?..',
            default: true,
        );

        /**
         * Install package globally.
         * 
         * If the packages `keywords` property contains `dev`, `testing`, or `static analysis`,
         * then add on `--dev` to the composer command. Save this to a variable for use in cleanup.
         */
        $this->info('installing package globally...');
        $cmd = "composer global require {$package}";
        if (collect(['dev', 'testing', 'static analysis'])->intersect($keywords)->isNotEmpty()) {
            $cmd .= ' --dev';
        }
        $result = Process::forever()->tty()->run($cmd);
        if ($result->exitCode() !== 0) {
            $this->error('failed to install package globally.');
            return $result->exitCode();
        }

        /**
         * Run binary.
         */
        $this->info('running binary...');
        $cmd = "{$binary} {$command} {$arguments}";
        $result = Process::forever()->tty()->run($cmd);
        // Save the return code for later.
        $return = $result->exitCode();

        /**
         * If the user wants to keep the package installed, then return the return code.
         */
        if ($should_keep_package_installed === true) {
            return $return;
        }

        /**
         * Uninstall package globally.
         */
        $this->info('uninstalling package globally...');
        $cmd = "composer global uninstall {$package}";
        if (collect(['dev', 'testing', 'static analysis'])->intersect($keywords)->isNotEmpty()) {
            $cmd .= ' --dev';
        }
        $result = Process::forever()->tty()->run($cmd);
        if ($result->exitCode() !== 0) {
            $this->error('failed to uninstall package globally.');
            return $result->exitCode();
        }

        // use the return code from earlier.
        return $return;
    }

    protected function getLatestPackageData(string $package): array
    {
        $response = Http::get("https://packagist.org/packages/{$package}.json");
        $json = $response->json();
        $versions = $json['package']['versions'];
        $latest = $versions[array_keys($versions)[1]]; // [0] is `dev-master` or `dev-main`

        return $latest;
    }

    protected function getBinaryName(array $latest_package_data): string
    {
        $this->info('checking package for binary...');
        $binary = $latest_package_data['bin'][0];
        $bin = explode('/', $binary)[1];

        return $bin;
    }

    protected function packageIsInstalledLocally(string $package): bool
    {
        $this->info('checking for locally installed package...');
        exec("composer show {$package} 2>&1", $output, $return_code);

        return $return_code === 0;
    }

    protected function packageIsInstalledGlobally(string $package): bool
    {
        $this->info('checking for globally installed package...');
        exec("composer global show {$package} 2>&1", $output, $return_code);

        return $return_code === 0;
    }
}
