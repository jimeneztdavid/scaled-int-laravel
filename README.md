# ScaledInt Laravel

Laravel integration for [`jimeneztdavid/scaled-int`](https://packagist.org/packages/jimeneztdavid/scaled-int).

## Why this package exists

`ScaledInt` solves a common problem in money and decimal-based attributes: databases store exact integers, while applications often need values such as `10.25`, `2.750`, or similar scaled numbers.

This package bridges that gap for Laravel. It lets you work with readable major values in your models while storing the minor integer representation in the database. That keeps arithmetic exact, avoids floating-point errors, and keeps Eloquent attributes simple to use.

The package also provides an Artisan command to add the cast to a model automatically.

## Installation

```bash
composer require jimeneztdavid/scaled-int-laravel
```

## Usage

### Model cast

```php
use Jimeneztdavid\ScaledIntLaravel\ScaledIntCast;

protected function casts(): array
{
    return [
        'price' => ScaledIntCast::class.':100',
    ];
}
```

### What the cast does

- stores the minor integer in the database
- returns a `Jimeneztdavid\ScaledInt\ScaledInt` instance when reading the attribute
- accepts `ScaledInt`, major numeric strings, integer minor values, and `null`

### Example with data

```php
$product = new Product();
$product->price = '10.25';
$product->save();

// database value: 1025
echo $product->price; // 10.25
```

### ScaledInt methods

The `jimeneztdavid/scaled-int` package provides a value object with safe decimal arithmetic.

#### Creating values

```php
use Jimeneztdavid\ScaledInt\ScaledInt;

$price = ScaledInt::fromMajor('10.25');
$weight = ScaledInt::fromMajor('2.750', 1000);
$minor = ScaledInt::fromMinor(1025);

echo $price; // 10.25
echo $weight; // 2.750
echo $minor; // 10.25
```

#### Reading values

- `minor()` returns the stored integer value
- `scale()` returns the scale used by the value
- `toMajorString()` returns the human-readable decimal string
- casting the object to string returns the same major value

```php
echo $price->minor(); // 1025
echo $price->scale(); // 100
echo $price->toMajorString(); // 10.25
echo $price; // 10.25
```

#### Arithmetic

```php
$price = ScaledInt::fromMajor('100.00');
$tax = ScaledInt::fromMajor('19.00');
$discount = ScaledInt::fromMajor('15.00');

$price->add($tax); // 119.00
$price->subtract($discount); // 85.00
$price->multiplyByInt(2); // 200.00
$price->divideByIntExact(2); // 50.00
$price->divideByInt(3, \Jimeneztdavid\ScaledInt\RoundingMode::HALF_UP); // 33.33
$price->percentOf(19, \Jimeneztdavid\ScaledInt\RoundingMode::HALF_UP); // 19.00
```

#### Comparisons

```php
$price = ScaledInt::fromMajor('10.25');
$other = ScaledInt::fromMajor('10.30');
$min = ScaledInt::fromMajor('10.00');
$max = ScaledInt::fromMajor('20.00');

$price->compareTo($other); // -1
$other->compareTo($price); // 1
$price->min($other); // 10.25
$price->max($other); // 10.30
$price->between($min, $max); // true
```

### Accepted assignment values

- `Jimeneztdavid\ScaledInt\ScaledInt` instance, for example `ScaledInt::fromMajor('10.25')`
- major numeric strings, such as `'10.25'`, stored as `1025`
- integer minor values, such as `1025`, returned as `10.25`
- `null`, stored as `null`

### Supported usage patterns

```php
$product->price = '10.25'; // stored as 1025
$product->price = \Jimeneztdavid\ScaledInt\ScaledInt::fromMajor('10.25', 100); // stored as 1025
$product->price = 1025; // read back as 10.25
$product->price = null; // stored as null
```

## Artisan command

```bash
php artisan make:scaled-int-cast Product price --scale=100
```

Options:

- `--scale=100`
- `--force`
- `--dry-run`

The command supports both existing `casts(): array` methods and legacy `$casts` properties.

## Configuration

```bash
php artisan vendor:publish --tag=scaled-int-config
```

Configuration options:

- `default_scale`: fallback scale used when no scale is passed to `ScaledIntCast`
- `default_cast`: default cast class for package configuration
