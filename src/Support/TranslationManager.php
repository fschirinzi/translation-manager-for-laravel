<?php

namespace Fschirinzi\TranslationManager\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

class TranslationManager
{
    private const DEFAULT_LANG_DIRNAME = 'lang';

    private $translations;
    private $rootLocalePath;
    private $locales = [];

    public function __construct($rootLocalePath)
    {
        $this->translations = collect();
        $this->setRootLocalePath($rootLocalePath);
    }

    /**
     * @return string
     */
    public function getRootLocalePath(): string
    {
        return $this->rootLocalePath;
    }

    /**
     * @param string $rootLocalePath
     */
    public function setRootLocalePath(string $rootLocalePath): void
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

    public function getItemsMissingTranslation(): Collection
    {
        return $this->translations
            ->where('missingIn')
            ->map(function ($item) {
                return $item
                    ->put('missingIn', join(',', $item->get('missingIn')))
                    ->put('foundIn', join(',', $item->get('foundIn')))
                    ->only(['file', 'key', 'foundIn', 'missingIn']);
            })
            ->sortBy('key')
            ->sortBy('file'); // Reverse sort; see https://github.com/laravel/ideas/issues/11
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

                    if ($item) {
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
                array_push($keys, ($parentKey ? "{$parentKey}." : '').$key);
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

}