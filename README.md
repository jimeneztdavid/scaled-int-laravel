# ScaledInt Laravel

Laravel integration for [`jimeneztdavid/scaled-int`](https://packagist.org/packages/jimeneztdavid/scaled-int).

## Installation

```bash
composer require jimeneztdavid/scaled-int-laravel
```

## Usage

```php
use Jimeneztdavid\ScaledIntLaravel\ScaledIntCast;

protected function casts(): array
{
    return [
        'price' => ScaledIntCast::class.':100',
    ];
}
```

The cast stores the minor integer in the database and returns a `ScaledInt` instance when reading the attribute.

```php
$product->price = '10.25';
$product->save();

echo $product->price; // 10.25
```

Accepted assignment values:

- `Jimeneztdavid\ScaledInt\ScaledInt`
- major numeric strings, such as `'10.25'`
- integer minor values
- `null`

## Artisan command

```bash
php artisan make:scaled-int-cast Product price --scale=100
```

Options:

- `--scale=100`
- `--force`
- `--dry-run`

## Configuration

```bash
php artisan vendor:publish --tag=scaled-int-config
```
