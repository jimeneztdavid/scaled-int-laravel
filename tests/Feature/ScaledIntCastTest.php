<?php

namespace Jimeneztdavid\ScaledIntLaravel\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Jimeneztdavid\ScaledInt\ScaledInt;
use Jimeneztdavid\ScaledIntLaravel\Tests\Fixtures\Product;
use Jimeneztdavid\ScaledIntLaravel\Tests\TestCase;

final class ScaledIntCastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function ($table): void {
            $table->id();
            $table->bigInteger('price')->nullable();
            $table->bigInteger('weight')->nullable();
            $table->bigInteger('discount')->nullable();
        });
    }

    public function test_it_stores_major_strings_as_minor_integers(): void
    {
        $product = Product::create(['price' => '10.25']);

        $this->assertSame(1025, DB::table('products')->where('id', $product->id)->value('price'));
        $this->assertSame('10.25', (string) $product->refresh()->price);
    }

    public function test_it_stores_scaled_int_instances_as_minor_integers(): void
    {
        $product = Product::create([
            'weight' => ScaledInt::fromMajor('2.750', 1000),
        ]);

        $this->assertSame(2750, DB::table('products')->where('id', $product->id)->value('weight'));
        $this->assertSame('2.750', (string) $product->refresh()->weight);
    }

    public function test_it_preserves_null_values(): void
    {
        $product = Product::create(['discount' => null]);

        $this->assertNull(DB::table('products')->where('id', $product->id)->value('discount'));
        $this->assertNull($product->refresh()->discount);
    }

    public function test_it_accepts_integer_minor_values(): void
    {
        $product = Product::create(['price' => 1234]);

        $this->assertSame(1234, DB::table('products')->where('id', $product->id)->value('price'));
        $this->assertSame('12.34', (string) $product->refresh()->price);
    }

    public function test_it_rejects_invalid_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Product::create(['price' => 'invalid']);
    }
}
