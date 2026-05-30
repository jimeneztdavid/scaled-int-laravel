<?php

namespace Jimeneztdavid\ScaledIntLaravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Jimeneztdavid\ScaledIntLaravel\ScaledIntCast;
use ReflectionClass;

final class MakeScaledIntCastCommand extends Command
{
    protected $signature = 'make:scaled-int-cast {model} {attribute} {--scale=100} {--force} {--dry-run}';

    protected $description = 'Add a ScaledInt cast definition to an Eloquent model.';

    protected $help = 'Ensure the database column can store the scaled minor value (integer or bigint).';

    public function handle(Filesystem $files): int
    {
        $path = $this->modelPath($files, $this->argument('model'));

        if ($path === null) {
            $this->error('Unable to locate the model file.');

            return self::FAILURE;
        }

        $contents = $files->get($path);
        $updated = $this->updatedContents($contents, (string) $this->argument('attribute'), (int) $this->option('scale'));

        if ($updated === null) {
            $this->error('The model already has a cast for this attribute. Use --force to replace it.');

            return self::FAILURE;
        }

        if ($updated === $contents) {
            $this->info('The model already contains the requested cast.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line($updated);

            return self::SUCCESS;
        }

        $files->put($path, $updated);
        $this->info('ScaledInt cast added successfully.');

        return self::SUCCESS;
    }

    private function modelPath(Filesystem $files, string $model): ?string
    {
        if (class_exists($model)) {
            $reflection = new ReflectionClass($model);

            return $reflection->getFileName() ?: null;
        }

        $model = str_replace('/', '\\', $model);
        $rootNamespace = app()->getNamespace();
        $relativeModel = str_starts_with($model, $rootNamespace)
            ? substr($model, strlen($rootNamespace))
            : $model;

        $relativeModel = ltrim($relativeModel, '\\');
        $candidates = [
            app_path(str_replace('\\', '/', $relativeModel).'.php'),
            app_path('Models/'.str_replace('\\', '/', $relativeModel).'.php'),
        ];

        foreach ($candidates as $candidate) {
            if ($files->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function updatedContents(string $contents, string $attribute, int $scale): ?string
    {
        if (preg_match('/protected\s+function\s+casts\s*\(\s*\)\s*:\s*array\s*\{\s*return\s*\[(.*?)\];\s*\}/s', $contents)) {
            return $this->updateCastsMethod($contents, $attribute, $scale);
        }

        if (preg_match('/protected\s+\$casts\s*=\s*\[(.*?)\];/s', $contents)) {
            return $this->updateCastsProperty($contents, $attribute, $scale);
        }

        return $this->addCastsMethod($contents, $attribute, $scale);
    }

    private function updateCastsMethod(string $contents, string $attribute, int $scale): ?string
    {
        preg_match('/protected\s+function\s+casts\s*\(\s*\)\s*:\s*array\s*\{\s*return\s*\[(.*?)\];\s*\}/s', $contents, $matches);

        $replacement = $this->replaceArrayBody($matches[0], $matches[1], $attribute, $scale);

        return $replacement === null ? null : str_replace($matches[0], $replacement, $contents);
    }

    private function updateCastsProperty(string $contents, string $attribute, int $scale): ?string
    {
        preg_match('/protected\s+\$casts\s*=\s*\[(.*?)\];/s', $contents, $matches);

        $replacement = $this->replaceArrayBody($matches[0], $matches[1], $attribute, $scale);

        return $replacement === null ? null : str_replace($matches[0], $replacement, $contents);
    }

    private function replaceArrayBody(string $block, string $body, string $attribute, int $scale): ?string
    {
        $castLine = $this->castLine($attribute, $scale);
        $attributePattern = '/([ \t]*)[\'\"]'.preg_quote($attribute, '/').'[\'\"]\s*=>\s*.*?,/';

        if (preg_match($attributePattern, $body)) {
            if (! $this->option('force')) {
                return trim(preg_replace($attributePattern, '$1'.$castLine, $body)) === trim($body) ? $block : null;
            }

            return str_replace($body, preg_replace($attributePattern, '$1'.$castLine, $body, 1), $block);
        }

        $indent = $this->arrayIndent($body);
        $line = $indent.$castLine;
        $newBody = rtrim($body).PHP_EOL.$line.PHP_EOL.str_repeat(' ', max(strlen($indent) - 4, 0));

        return str_replace($body, $newBody, $block);
    }

    private function addCastsMethod(string $contents, string $attribute, int $scale): string
    {
        $method = PHP_EOL.'    protected function casts(): array'.PHP_EOL.'    {'.PHP_EOL.'        return ['.PHP_EOL.'            '.$this->castLine($attribute, $scale).PHP_EOL.'        ];'.PHP_EOL.'    }'.PHP_EOL;
        $position = strrpos($contents, '}');

        if ($position === false) {
            return $contents;
        }

        return substr_replace($contents, $method, $position, 0);
    }

    private function arrayIndent(string $body): string
    {
        if (preg_match('/\n([ \t]+)[^\s]/', $body, $matches)) {
            return $matches[1];
        }

        return '            ';
    }

    private function castLine(string $attribute, int $scale): string
    {
        return "'{$attribute}' => \\".ScaledIntCast::class."::class.':{$scale}',";
    }
}
