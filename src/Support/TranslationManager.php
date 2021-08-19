<?php

namespace Fschirinzi\TranslationManager\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

class TranslationManager
{
    private const DEFAULT_LANG_DIRNAME = 'lang';
    private const DEFAULT_KEY_SEPARATOR = '__.__';

    private $separator;
    private $translations;
    private $rootLocalePath;
    private $locales = [];

    public function __construct($rootLocalePath)
    {
        $this->translations = collect();
        $this->setSeparator('');
        $this->setRootLocalePath($rootLocalePath);
    }

    /**
     * @return Collection
     */
    public function getTranslations(bool $onlyMissingTranslation): Collection
    {
        return $this->translations
            ->when($onlyMissingTranslation, function ($q) {
                return $q->where('missingIn');
            });
    }

    /**
     * @return string
     */
    public function getSeparator(): string
    {
        return $this->separator;
    }

    /**
     * @param string $separator
     */
    public function setSeparator(string $separator): void
    {
        $this->separator = $separator == ''
            ? self::DEFAULT_KEY_SEPARATOR
            : $separator;
    }

    /**
     * @return string
     */
    public function getRootLocalePath(): string
    {
        return $this->rootLocalePath;
    }

    /**
     * @param string|null $rootLocalePath
     */
    public function setRootLocalePath(?string $rootLocalePath = ''): void
    {
        if ($rootLocalePath == '') {
            $this->rootLocalePath = resource_path(self::DEFAULT_LANG_DIRNAME);

            return;
        }

        if (File::isDirectory($rootLocalePath)) {
            $this->rootLocalePath = $rootLocalePath;

            return;
        }

        if (File::isDirectory(base_path($rootLocalePath))) {
            $this->rootLocalePath = base_path($rootLocalePath);

            return;
        }

        throw new DirectoryNotFoundException("Specified resource directory {$rootLocalePath} does not exist.");
    }

    public function load()
    {
        $this->parseDirectories(File::directories($this->getRootLocalePath()));
        $this->validateMissingLocales();
    }

    public function formatItemsForConsoleTable(Collection $items)
    {
        return $items->map(function ($item) {
            return $item
                ->put('missingIn', join(',', $item->get('missingIn')))
                ->put('foundIn', join(',', $item->get('foundIn')))
                ->only(['file', 'key', 'foundIn', 'missingIn']);
        })->toArray();
    }

    public function getItemsForExport(): Collection
    {
        return $this->getTranslations(false)
            ->map(function ($item) {
                foreach ($item->get('translations') as $key => $translation) {
                    $item->put("translation_{$key}", $translation);
                }
                $item->forget('folder');
                $item->forget('translations');
                $item->forget('foundIn');
                $item->forget('missingIn');

                return $item;
            });
    }

    public function createExportFile($outputPath)
    {
        $items = $this->getItemsForExport();
        $headers = $items->map->keys()->flatten()->unique()->toArray();

        $fp = fopen($outputPath, 'w');

        fputcsv($fp, $headers);
        foreach ($items as $row) {
            $formattedRow = collect();
            foreach ($headers as $header) {
                $formattedRow->put($header, $row->get($header, ''));
            }
            fputcsv($fp, $formattedRow->toArray());
        }

        fclose($fp);
    }

    public function parseDirectories($localeDirectories)
    {
        foreach ($localeDirectories as $localeDirectory) {
            $rootLocalePath = dirname($localeDirectory);
            $locale = basename($localeDirectory);
            if (! in_array($locale, $this->locales)) {
                array_push($this->locales, $locale);
            }

            $baseLocaleDirectoryPath = $localeDirectory;
            $baseLocaleFiles = $this->getFilenames($baseLocaleDirectoryPath);

            foreach ($baseLocaleFiles as $file) {
                $loadedLocale = File::getRequire("{$baseLocaleDirectoryPath}/{$file}");
                $keys = $this->getAbolsutePathRecursive($loadedLocale);

                foreach ($keys as $key) {
                    $item = $this->translations
                        ->where('folder', $rootLocalePath)
                        ->where('file', $file)
                        ->where('key', $key)
                        ->first();

                    $pathAsArray = explode($this->getSeparator(), $key);
                    $translation = $this->getNestedItems($loadedLocale, $pathAsArray);

                    if ($item) {
                        $item->put('foundIn', array_merge($item->get('foundIn'), [$locale]));
                        $item->get('translations')->put($locale, $translation);
                    } else {
                        $this->translations->push(collect([
                            'folder' => $rootLocalePath,
                            'file' => $file,
                            'key' => $key,
                            'foundIn' => [$locale],
                            'translations' => collect([$locale => $translation]),
                        ]));
                    }
                }
            }
        }

        $this->translations
            ->sortBy('key')
            ->sortBy('file'); // Reverse sort; see https://github.com/laravel/ideas/issues/11
    }

    private function validateMissingLocales()
    {
        $this->translations->each(function ($item) {
            $item->put('missingIn', array_diff($this->locales, $item->get('foundIn')));
        });
    }

    private function getAbolsutePathRecursive($arr, $parentKey = null)
    {
        $keys = [];

        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $keys = array_merge($keys, $this->getAbolsutePathRecursive($val, $key));
            } else {
                array_push($keys, ($parentKey ? "{$parentKey}{$this->getSeparator()}" : '').$key);
            }
        }

        return $keys;
    }

    /**
     * Get filenames of directory.
     */
    private function getFilenames(string $directory): array
    {
        $fileNames = [];

        /** @var \Symfony\Component\Finder\SplFileInfo[] $filesInFolder */
        $filesInFolder = File::allFiles($directory);

        foreach ($filesInFolder as $fileInfo) {
            $fileNames[] = $fileInfo->getRelativePathname();
        }

        return $fileNames;
    }

    private function getNestedItems($input, $levels = [])
    {
        $output = $input;

        foreach ($levels as $level) {
            if (! is_array($output) || ! array_key_exists($level, $output)) {
                return null;
            }
            $output = $output[$level];
        }

        return $output;
    }
}
