<?php declare(strict_types=1);

namespace Fschirinzi\TranslationManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

class ValidateTranslations extends Command
{
    private const DEFAULT_LANG_DIRNAME = 'lang';
    private $locales = [];
    private $translations;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'translations:validate {--dir= : Relative path to lang directory (e.g. "/resources/lang")}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Lists all locale files and their missing translations.';

    private $exitCode = 0;

    /** @inheritDoc */
    public function handle(): int
    {
        if ($this->option('dir') === null) {
            $pathToLocates = resource_path(self::DEFAULT_LANG_DIRNAME);
        } elseif (File::isDirectory($this->option('dir'))) {
            $pathToLocates = $this->option('dir');
        } elseif (File::isDirectory(base_path($this->option('dir')))) {
            $pathToLocates = base_path($this->option('dir'));
        } else {
            throw new DirectoryNotFoundException("Specified resource directory {$this->option('dir')} does not exist.");
        }

        $this->translations = collect();
        $localeDirectories = File::directories($pathToLocates);

        $this->loadAllLocales($localeDirectories);

        $this->validateMissingLocales();

        $this->info('Lookup directory: ' . $pathToLocates);

        $itemsThatMissTranslation = $this->translations->where('missingIn');
        if($itemsThatMissTranslation->count()){
            $itemsThatMissTranslation = $itemsThatMissTranslation->map(function($item) {
                return $item
                    ->put('missingIn', join(',', $item->get('missingIn')))
                    ->put('foundIn', join(',', $item->get('foundIn')))
                    ->only(['file', 'key', 'foundIn', 'missingIn']);
            })
                ->sortBy(['key'])->sortBy(['file']) # Reverse sort; see https://github.com/laravel/ideas/issues/11
                ->toArray();

            $this->table(['File', 'Key', 'Found in', 'Missing in'], $itemsThatMissTranslation);
        }

        $this->info('Successfully compared all languages.');

        return $this->exitCode;
    }

    private function loadAllLocales($localeDirectories){
        foreach($localeDirectories as $localeDirectory) {
            $rootLocalePath = dirname($localeDirectory);
            $locale = basename($localeDirectory);
            if(!in_array($locale, $this->locales)){ array_push($this->locales, $locale); }

            $baseLocaleDirectoryPath = $localeDirectory;
            $baseLocaleFiles = $this->getFilenames($baseLocaleDirectoryPath);

            foreach ($baseLocaleFiles as $file) {
                $loadedLocale = File::getRequire("{$baseLocaleDirectoryPath}/{$file}");
                $keys = $this->getAbolsutePathRecursive($loadedLocale);

                foreach($keys as $key){
                    $item = $this->translations
                        ->where('folder', $rootLocalePath)
                        ->where('file', $file)
                        ->where('key', $key)
                        ->first();

                    if($item) {
                        $item->put('foundIn', array_merge($item->get('foundIn'), [$locale]));
                    } else {
                        $this->translations->push(collect([
                            'folder' => $rootLocalePath,
                            'file' => $file,
                            'key' => $key,
                            'foundIn' => [$locale],
                        ]));
                    }
                }
            }

        }

    }

    private function getAbolsutePathRecursive($arr, $parentKey = null){
        $keys = [];

        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $keys = array_merge($keys, $this->getAbolsutePathRecursive($val, $key));
            } else {
                array_push($keys, ($parentKey ? "{$parentKey}." : '' ) . $key);
            }
        }

        return $keys;
    }

    private function validateMissingLocales(){
        $this->translations->each(function($item){
            $item->put('missingIn', array_diff($this->locales, $item->get('foundIn')));
        });

        if($this->translations->where('missingIn')->count()){
            $this->exitCode = 1;
        }
    }

    /**
     * Get filenames of directory
     */
    private function getFilenames(string $directory): array
    {
        $fileNames = [];

        /** @var \Symfony\Component\Finder\SplFileInfo[] $filesInFolder */
        $filesInFolder = File::files($directory);

        foreach ($filesInFolder as $fileInfo) {
            $fileNames[] = $fileInfo->getFilename();
        }

        return $fileNames;
    }
}
