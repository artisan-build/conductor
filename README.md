<p align="center">
    <img title="Conductor" height="400" src="https://raw.githubusercontent.com/artisan-build/conductor/main/art/composer_conductor.webp" alt="Conductor Logo" />
</p>

<p align="center">
  <a href="https://github.com/artisan-build/conductor/actions"><img src="https://github.com/artisan-build/conductor/actions/workflows/tests.yml/badge.svg" alt="Build Status" /></a>
  <a href="https://packagist.org/packages/artisan-build/conductor"><img src="https://img.shields.io/packagist/dt/artisan-build/conductor.svg" alt="Total Downloads" /></a>
  <a href="https://packagist.org/packages/artisan-build/conductor"><img src="https://img.shields.io/packagist/v/artisan-build/conductor.svg?label=stable" alt="Latest Stable Version" /></a>
  <a href="https://packagist.org/packages/artisan-build/conductor"><img src="https://img.shields.io/packagist/l/artisan-build/conductor.svg" alt="License" /></a>
</p>

Conductor was created by [Len Woodward](https://github.com/ProjektGopher) and [Ed Grosvenor](https://github.com/edgrosvenor).

Conductor is a way to run Composer binaries without cluttering up your global path, or having to install packages locally. It is **unofficial** and not yet affiliated with Composer.

- Built with [Laravel-Zero](https://laravel-zero.com).

------

## Documentation

For full documentation, visit [artisan.build/conductor](https://artisan.build/conductor).

### Installation

Conductor should *only* be installed globally (like Composer).

```sh
composer global require artisan-build/conductor
```

To verify that conductor has been added to your global `$PATH`, run

```sh
conductor
```

### Usage

```sh
conductor {PACKAGE_NAME} {COMMAND} {ARGUMENTS}
```

example:
```sh
conductor laravel/installer new my-project
```

This command will validate that the package has a configured binary, install the latest version globally, run the command with the provided arguments, then uninstall the package.

## Support the development
**Do you like this project? Support it by sponsoring**

- Len Woodward: [Sponsor](https://github.com/sponsors/ProjektGopher)

Artisan Build is a small Laravel development agency, founded by Len Woodward and Ed Grosvenor. We specialize in building SaaS products in an equity partnership capacity.

## License

Conductor is an open-source software licensed under the MIT license.
