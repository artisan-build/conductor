<?php

declare(strict_types=1);

namespace Conductor\Commands;

use Closure;
use Conductor\Error;
use Conductor\Services\Package;
use LaravelZero\Framework\Commands\Command;
use NunoMaduro\LaravelConsoleSummary\SummaryCommand;

use function Laravel\Prompts\select;

class RunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run {package?} {thing?} {arguments?*} {--bin=} {--fake} {--option=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and run a composer package.';

    /**
     * Whether or not to fake the actual execution of the command.
     */
    public readonly bool $fake;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('fake')) {
            $this->info('fake = true');
            $this->fake = true;
        }

        // TODO
        // check if conductor is latest version...

        if (! $this->argument('package')) {
            $this->call(SummaryCommand::class);

            return;
        }

        $package = Package::fromName($this->argument('package'));
        if ($package instanceof Error) {
            $this->error($package->message);

            return self::FAILURE;
        }

        $binary = $this->selectBinary($package);
        $command = $this->argument('thing');
        $arguments = $this->argument('arguments');
        $options = $this->option('option');

        if ($package->isInstalledLocally()) {
            $this->info('package is already installed locally. running...');

            return $package->run($binary, $command, $arguments, $options, Package::LOCALLY);
        }

        if ($package->isInstalledGlobally()) {
            $this->info('package is already installed globally. running...');

            return $package->run($binary, $command, $arguments, $options, Package::GLOBALLY);
        }

        // TODO
        // $this->info('update globally installed package?..');

        $should_keep_package_installed = $this->confirm(
            question: 'keep package installed after running?..',
            default: true,
        );

        /**
         * Install package globally.
         */
        $this->info('installing package globally...');
        $result = $package->installGlobally();
        if ($result !== 0) {
            $this->error('failed to install package globally.');
            return $result;
        }

        /**
         * Run binary and save the return code for later.
         */
        $this->info('running binary...');
        $return = $package->run($binary, $command, $arguments, $options, Package::GLOBALLY);

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
        $result = $package->uninstallGlobally();
        if ($result !== 0) {
            $this->error('failed to uninstall package globally.');
            return $result;
        }

        // use the return code from earlier.
        return $return;
    }

    public function selectBinary(Package $package): string
    {
        if (
            ($binary = $this->option('bin')) &&
            collect($package->binaries())->contains($binary)
        ) {
            return $this->option('bin');
        }

        if (is_string($binary)) {
            $this->warn("binary `{$binary}` not found in package.");
        }

        $binary = is_string($package->binaries())
            ? $package->binaries()
            : select('select binary to run', $package->binaries());

        return last(explode('/', $binary));
    }

    protected function if_faking(Closure $then, Closure $else): mixed
    {
        if ($this->fake) {
            return $then();
        }

        return $else();
    }
}
