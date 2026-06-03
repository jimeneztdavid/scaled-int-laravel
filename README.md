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

## Why this package exists in practice

### Float math is not reliable for exact values

```php
var_dump(0.1 + 0.2); // float(0.30000000000000004)
var_dump((0.1 + 0.2) === 0.3); // bool(false)
```

That is fine for approximate scientific calculations, but not for money, discounts, taxes, or any value that must round exactly.

### Storing money as a float creates long-term problems

If you store a price as a float, you are asking the database and PHP to preserve decimal precision that binary floats do not represent cleanly.

Common Laravel workaround:

```php
public function setPriceAttribute($value): void
{
    $this->attributes['price'] = (int) round($value * 100);
}

public function getPriceAttribute($value): float
{
    return $value / 100;
}
```

This works, but it means every model repeats the same logic. It also pushes rounding decisions into many places instead of one.

### Comparisons with floats can be misleading

```php
var_dump(0.3 === (0.1 + 0.2)); // bool(false)
```

With scaled integers, the comparison is exact because the stored value is an integer.

```php
use Jimeneztdavid\ScaledInt\ScaledInt;

$a = ScaledInt::fromMajor('0.10');
$b = ScaledInt::fromMajor('0.20');

echo $a->add($b); // 0.30
```

### The same problem appears when saving and loading values

Many projects end up doing this manually:

```php
$product->price = 10.25;
$product->save();

// save as 1025
// read back as 10.25
```

That means multiplying by 100 on write, dividing by 100 on read, and keeping the column as an integer. `ScaledIntCast` does that for you in one place.

```php
use Jimeneztdavid\ScaledIntLaravel\ScaledIntCast;

protected function casts(): array
{
    return [
        'price' => ScaledIntCast::class.':100',
    ];
}
```

### What you get with ScaledInt

- exact integer-based math
- consistent rounding
- easier comparisons
- less custom accessor/mutator code
- clearer intent in your models and services

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
