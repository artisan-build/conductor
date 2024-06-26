<?php

declare(strict_types=1);

namespace Conductor\Services;

use Conductor\Error;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class Package
{
    const LOCALLY = 'local';

    const GLOBALLY = 'global';

    const DEV_KEYWORDS = ['dev', 'testing', 'static analysis'];

    public readonly string $name;

    public readonly array $latest_data;

    /**
     * Create a package instance from a package name.
     */
    public static function fromName(mixed $name): self|Error
    {
        if (! is_string($name)) {
            return new Error('invalid-name', 'Invalid package name.');
        }

        // validate package name formatting
        if (! preg_match('/^[a-z0-9-_.]+\/[a-z0-9-_.]+$/', $name)) {
            return new Error('invalid-name', 'Invalid package name.');
        }

        $data = self::getPackageData($name);

        // check package exists
        if (isset($data['status']) && $data['status'] === 'error') {
            return new Error('not-found', $data['message']);
        }

        // check package has binaries
        if (! isset(self::extractLatestData($data)['bin'])) {
            return new Error('no-binary', 'Package has no binaries.');
        }

        return new self($data);
    }

    public function __construct(public readonly array $data)
    {
        $this->name = $this->data['package']['name'];
        $this->latest_data = $this->extractLatestData($this->data);
    }

    public static function getPackageData(string $name): array
    {
        $response = Http::get("https://packagist.org/packages/{$name}.json");
        $json = $response->json();

        return $json;
    }

    public static function extractLatestData(array $data): array
    {
        $versions = $data['package']['versions'];
        $latest = $versions[array_keys($versions)[1]]; // [0] is `dev-master` or `dev-main`

        return $latest;
    }

    public function binaries(): array|string
    {
        return count($this->latest_data['bin']) === 1
        ? $this->latest_data['bin'][0]
        : $this->latest_data['bin'];
    }

    public function keywords(): Collection
    {
        return collect($this->latest_data['keywords']);
    }

    public function isDev(): bool
    {
        return $this->keywords()->intersect(self::DEV_KEYWORDS)->isNotEmpty();
    }

    public function isInstalledLocally(): bool
    {
        exec("composer show {$this->name} 2>&1", $output, $return_code);

        return $return_code === 0;
    }

    public function isInstalledGlobally(): bool
    {
        exec("composer global show {$this->name} 2>&1", $output, $return_code);

        return $return_code === 0;
    }

    public function installGlobally(): int
    {
        $cmd = "composer global require {$this->name}";
        if ($this->isDev()) {
            $cmd .= ' --dev';
        }
        $result = Process::forever()->tty()->run($cmd);

        return $result->exitCode() ?? 0; // return 0 if exit code is null
    }

    public function uninstallGlobally(): int
    {
        $cmd = "composer global uninstall {$this->name}";
        if ($this->isDev()) {
            $cmd .= ' --dev';
        }
        $result = Process::forever()->tty()->run($cmd);

        return $result->exitCode() ?? 0; // return 0 if exit code is null
    }

    public function run(
        string $binary,
        ?string $command,
        ?array $arguments,
        ?array $options,
        string $context = self::GLOBALLY,
    ): int {
        $arguments = implode(' ', $arguments ?? []);
        $options = collect($options)
            ->prepend('') // this will add `-- ` before the first option
            ->map(fn (string $option): string => "--{$option}")
            ->implode(' ');
        $cmd = str(match ($context) {
            self::GLOBALLY => 'composer global exec',
            self::LOCALLY => 'composer exec',
            default => throw new Exception('invalid context'),
        })->append(" {$binary} {$command} {$arguments} {$options}")->toString();
        $result = Process::forever()->tty()->run($cmd);

        return $result->exitCode() ?? 0; // return 0 if exit code is null
    }

    public function isLatestVersion(): bool
    {
        // return $this->latest_data['version'] === $this->latest_data['version_normalized'];
        throw new Exception('not implemented');
    }
}
