# Project Context

This repository is a Laravel package that integrates `jimeneztdavid/scaled-int` with Eloquent.

## Package shape

- Package name: `jimeneztdavid/scaled-int-laravel`
- Namespace: `Jimeneztdavid\ScaledIntLaravel`
- Minimum Laravel version: 12
- Laravel 13 is supported through dependency constraints.

## Main behavior

- `ScaledIntCast` stores database values as minor integers.
- Reading an attribute returns a `Jimeneztdavid\ScaledInt\ScaledInt` object.
- Assignment supports `ScaledInt`, major strings, integer minor values, and `null`.
- The package registers `make:scaled-int-cast` through Laravel package auto-discovery.

## Testing

- Testbench is used for package tests.
- The GitHub Actions matrix covers Laravel 12 and Laravel 13 dependency sets.
