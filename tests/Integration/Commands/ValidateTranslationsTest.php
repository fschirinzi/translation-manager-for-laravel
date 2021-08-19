<?php

declare(strict_types=1);

namespace Fschirinzi\TranslationManager\Tests\Integration\Commands;

use Fschirinzi\TranslationManager\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

final class ValidateTranslationsTest extends TestCase
{
    /** @test */
    public function it_does_not_report_about_synchronized_files()
    {
        $this->withoutMockingConsoleOutput();

        $dir = __DIR__ . '/../../Templates/sync_lang_files';
        $exitCode = $this->artisan('translations:validate', ['--dir' => $dir]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Successfully compared all languages.', trim($output));
    }

    /** @test */
    public function it_reports_about_missing_translation_keys()
    {
        $this->withoutMockingConsoleOutput();

        $dir = __DIR__ . '/../../Templates/unsync_lang_files';
        $exitCode = $this->artisan('translations:validate', ['--dir' => $dir]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('| a.php | OK            | en       | be         |', $output);
        $this->assertStringContainsString('| a.php | group.nested  | be       | en         |', $output);
        $this->assertStringContainsString('| a.php | group.nested2 | be       | en         |', $output);
    }

    /** @test */
    public function it_does_not_report_about_missing_translation_keys_by_locale_country_combination()
    {
        $this->withoutMockingConsoleOutput();

        $dir = __DIR__ . '/../../Templates/locale-country/sync_lang_files';
        $exitCode = $this->artisan('translations:validate', ['--dir' => $dir]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Successfully compared all languages.', trim($output));
    }

    /** @test */
    public function it_reports_about_missing_translation_keys_by_locale_country_combination()
    {
        $this->withoutMockingConsoleOutput();

        $dir = __DIR__ . '/../../Templates/locale-country/unsync_lang_files';
        $exitCode = $this->artisan('translations:validate', ['--dir' => $dir]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('| a.php | only_in_de      | de       | de-CH,en,fr-CH |', $output);
        $this->assertStringContainsString('| a.php | only_in_de-CH   | de-CH    | de,en,fr-CH    |', $output);
        $this->assertStringContainsString('| a.php | only_in_en      | en       | de,de-CH,fr-CH |', $output);
        $this->assertStringContainsString('| a.php | only_in_fr-CH   | fr-CH    | de,de-CH,en    |', $output);
        $this->assertStringContainsString('| b.php | File_only_in_de | de       | de-CH,en,fr-CH |', $output);
    }

    /** @test */
    public function it_does_not_report_about_missing_translation_from_children_directories()
    {
        $this->withoutMockingConsoleOutput();

        $dir = __DIR__ . '/../../Templates/sync_sub_dirs';
        $exitCode = $this->artisan('translations:validate', ['--dir' => $dir]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Successfully compared all languages.', trim($output));
    }

    /** @test */
    public function it_reports_about_missing_translation_keys_from_children_directories()
    {
        $this->withoutMockingConsoleOutput();

        $DS = \DIRECTORY_SEPARATOR;

        $dir = __DIR__ . '/../../Templates/unsync_sub_dirs';
        $exitCode = $this->artisan('translations:validate', ['--dir' => $dir]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('| a.php                                | Yes              | en       | be         |', $output);
        $this->assertStringContainsString("| sub-dir{$DS}sub-sub-dir{$DS}sub-sub-file.php | sub-sub-file-key | en       | be         |", $output);
    }
}
