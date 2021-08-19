<?php

declare(strict_types=1);

namespace Fschirinzi\TranslationManager\Tests\Integration\Commands;

use Fschirinzi\TranslationManager\Tests\TestCase;

final class ExportTranslationsTest extends TestCase
{
    /** @test */
    public function it_does_export_all_translations()
    {
        $this->withoutMockingConsoleOutput();

        $dir = __DIR__.'/../../Templates/locale-country/unsync_lang_files';
        $tmpOutputDir = '/tmp';
        $tmpOutputFilepath = $tmpOutputDir.'/locale-country_unsync_lang_files.csv';
        $exitCode = $this->artisan(
            'translations:export', ['--dir' => $dir, '-o' => $tmpOutputFilepath]
        );

        $original = __DIR__.'/../../Templates/Exports/locale-country_unsync_lang_files.csv';
        $this->assertEquals(file_get_contents($original), file_get_contents($tmpOutputFilepath));
        $this->assertSame(0, $exitCode);
    }
}
