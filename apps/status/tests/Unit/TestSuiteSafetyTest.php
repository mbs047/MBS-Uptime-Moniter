<?php

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class TestSuiteSafetyTest extends TestCase
{
    public function test_app_tests_do_not_import_laravels_raw_refresh_database_trait(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('tests'))
        );

        $testFiles = collect(iterator_to_array($iterator))
            ->filter(fn (SplFileInfo $file): bool => $file->isFile() && $file->getExtension() === 'php')
            ->map(fn (SplFileInfo $file): string => $file->getPathname())
            ->reject(fn (string $path): bool => str_ends_with($path, '/tests/Concerns/RefreshDedicatedDatabase.php'))
            ->values();

        $rawRefreshImports = $testFiles
            ->filter(function (string $path): bool {
                $contents = file_get_contents($path);

                return is_string($contents)
                    && preg_match('/^use Illuminate\\\\Foundation\\\\Testing\\\\RefreshDatabase;$/m', $contents) === 1;
            })
            ->map(fn (string $path): string => str_replace(base_path().'/', '', $path))
            ->values()
            ->all();

        $this->assertSame(
            [],
            $rawRefreshImports,
            'App tests must use Tests\\Concerns\\RefreshDedicatedDatabase instead of Laravel\'s raw RefreshDatabase trait.'
        );
    }
}
