<?php

namespace Jimeneztdavid\ScaledIntLaravel;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Jimeneztdavid\ScaledInt\ScaledInt;
use Throwable;

final class ScaledIntCast implements CastsAttributes
{
    public function __construct(
        private ?int $scale = null,
    ) {
        $this->scale ??= (int) config('scaled-int.default_scale', 100);

        if ($this->scale < 1) {
            throw new InvalidArgumentException('Scale must be greater than zero.');
        }
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?ScaledInt
    {
        if ($value === null) {
            return null;
        }

        return ScaledInt::fromMinor((int) $value, $this->scale);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ScaledInt) {
            return $value->minor();
        }

        if (is_string($value)) {
            return $this->minorFromMajor($key, $value);
        }

        if (is_int($value)) {
            return $value;
        }

        throw new InvalidArgumentException("Invalid value for {$key}.");
    }

    private function minorFromMajor(string $key, string $value): int
    {
        try {
            return ScaledInt::fromMajor($value, $this->scale)->minor();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException("Invalid value for {$key}.", previous: $exception);
        }
    }
}
