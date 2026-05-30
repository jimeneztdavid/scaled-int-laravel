<?php

namespace Jimeneztdavid\ScaledIntLaravel\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Jimeneztdavid\ScaledIntLaravel\ScaledIntCast;

final class Product extends Model
{
    protected $table = 'products';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => ScaledIntCast::class.':100',
            'weight' => ScaledIntCast::class.':1000',
            'discount' => ScaledIntCast::class.':100',
        ];
    }
}
