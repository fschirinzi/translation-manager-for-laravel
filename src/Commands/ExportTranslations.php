<?php

declare(strict_types=1);

namespace Fschirinzi\TranslationManager\Commands;

use Fschirinzi\TranslationManager\Support\TranslationManager;
use Illuminate\Console\Command;

class ExportTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:export
                            {--dir= : Relative path to lang directory (e.g. "/resources/lang")}
                            {--o|output= : Output file path.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export translations to csv.';

    private $exitCode = 0;

    /** @inheritDoc */
    public function handle(): int
    {
        $tM = new TranslationManager($this->option('dir'));
        $tM->load();

        $this->info('Lookup directory: '.$tM->getRootLocalePath());
        $this->info('Export directory: '.$this->option('output'));

        $tM->createExportFile($this->option('output'));

        return $this->exitCode;
    }
}
