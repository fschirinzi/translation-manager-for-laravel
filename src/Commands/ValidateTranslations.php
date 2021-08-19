<?php

declare(strict_types=1);

namespace Fschirinzi\TranslationManager\Commands;

use Fschirinzi\TranslationManager\Support\TranslationManager;
use Illuminate\Console\Command;

class ValidateTranslations extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'translations:validate
                            {--dir= : Relative path to lang directory (e.g. "/resources/lang")}
                            {--s|separator=. : Character to separate nested keys}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Lists all locale files and their missing translations.';

    private $exitCode = 0;

    /** @inheritDoc */
    public function handle(): int
    {
        $tM = new TranslationManager($this->option('dir'));
        $tM->setSeparator($this->option('separator'));
        $tM->load();

        $this->info('Lookup directory: '.$tM->getRootLocalePath());

        $itemsThatMissTranslation = $tM->getTranslations(true);
        if ($itemsThatMissTranslation->count()) {
            $this->exitCode = 1;
            $this->table(
                ['File', 'Key', 'Found in', 'Missing in'],
                $tM->formatItemsForConsoleTable($itemsThatMissTranslation)
            );
        }

        $this->info('Successfully compared all languages.');

        return $this->exitCode;
    }
}
