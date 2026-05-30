<?php

namespace Jimeneztdavid\ScaledIntLaravel\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Jimeneztdavid\ScaledIntLaravel\Tests\TestCase;

final class MakeScaledIntCastCommandTest extends TestCase
{
    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
        $this->files->ensureDirectoryExists(app_path('Models'));
    }

    public function test_it_adds_a_cast_to_a_casts_method(): void
    {
        $path = app_path('Models/Product.php');
        $this->files->put($path, <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Product extends Model
{
    protected function casts(): array
    {
        return [
            'name' => 'string',
        ];
    }
}
PHP);

        $this->artisan('make:scaled-int-cast', [
            'model' => 'Product',
            'attribute' => 'price',
            '--scale' => 100,
        ])->assertExitCode(0);

        $contents = $this->files->get($path);

        $this->assertStringContainsString("'price' => \\Jimeneztdavid\\ScaledIntLaravel\\ScaledIntCast::class.':100',", $contents);
    }

    public function test_it_adds_a_cast_to_a_casts_property(): void
    {
        $path = app_path('Models/ProductWithProperty.php');
        $this->files->put($path, <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ProductWithProperty extends Model
{
    protected $casts = [
        'name' => 'string',
    ];
}
PHP);

        $this->artisan('make:scaled-int-cast', [
            'model' => 'ProductWithProperty',
            'attribute' => 'price',
            '--scale' => 100,
        ])->assertExitCode(0);

        $contents = $this->files->get($path);

        $this->assertStringContainsString("'price' => \\Jimeneztdavid\\ScaledIntLaravel\\ScaledIntCast::class.':100',", $contents);
    }

    public function test_it_does_not_write_files_during_a_dry_run(): void
    {
        $path = app_path('Models/DryRunProduct.php');
        $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class DryRunProduct extends Model
{
}
PHP;

        $this->files->put($path, $original);

        $this->artisan('make:scaled-int-cast', [
            'model' => 'DryRunProduct',
            'attribute' => 'price',
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame($original, $this->files->get($path));
    }

    public function test_it_does_not_replace_an_existing_cast_without_force(): void
    {
        $path = app_path('Models/ExistingCastProduct.php');
        $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ExistingCastProduct extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'integer',
        ];
    }
}
PHP;

        $this->files->put($path, $original);

        $this->artisan('make:scaled-int-cast', [
            'model' => 'ExistingCastProduct',
            'attribute' => 'price',
        ])->assertExitCode(1);

        $this->assertSame($original, $this->files->get($path));
    }

    public function test_it_replaces_an_existing_cast_with_force(): void
    {
        $path = app_path('Models/ForcedCastProduct.php');
        $this->files->put($path, <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ForcedCastProduct extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'integer',
        ];
    }
}
PHP);

        $this->artisan('make:scaled-int-cast', [
            'model' => 'ForcedCastProduct',
            'attribute' => 'price',
            '--scale' => 100,
            '--force' => true,
        ])->assertExitCode(0);

        $contents = $this->files->get($path);

        $this->assertStringContainsString("'price' => \\Jimeneztdavid\\ScaledIntLaravel\\ScaledIntCast::class.':100',", $contents);
        $this->assertStringNotContainsString("'price' => 'integer',", $contents);
    }
}
