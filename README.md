# Translation Manager For Laravel

<a href="https://github.com/fschirinzi/translation-manager-for-laravel/actions">
    <img src="https://github.com/fschirinzi/translation-manager-for-laravel/workflows/Tests/badge.svg" alt="Tests">
</a>
<a href="https://github.styleci.io/repos/364001001">
    <img src="https://github.styleci.io/repos/364001001/shield?style=flat" alt="Code Style">
</a>
<a href="https://packagist.org/packages/fschirinzi/translation-manager-for-laravel">
    <img src="https://img.shields.io/packagist/v/fschirinzi/translation-manager-for-laravel" alt="Latest Stable Version">
</a>
<a href="https://packagist.org/packages/fschirinzi/translation-manager-for-laravel">
    <img src="https://img.shields.io/packagist/dt/fschirinzi/translation-manager-for-laravel" alt="Total Downloads">
</a>

Easy to use package that helps you with the translation of your Laravel application locally.

<p align="center"><img style="max-width: 500px;" src="https://francesco.schirinzi.me/assets/translation_manager_for_laravel.jpeg" alt="Output example"></p>

### Features
:white_check_mark: Check all locales <br>
:white_check_mark: Check nested translations <br>
:white_check_mark: Check nested directories <br>
:white_check_mark: Display where translations are found <br>
:white_check_mark: Display where translations are missing <br>
:white_check_mark: Export all translations to CSV for easy sharing with your translator <br>

## Installation
```sh
composer require fschirinzi/translation-manager-for-laravel --dev
```

## Usage
### Validation
Use default Laravel's path to lang files:
```sh
php artisan translations:validate
```

You can specify a relative or absolute path to `lang` directory location:
```sh
php artisan translations:validate --dir=/other/dir/with/my-custom-languages
```
### Export
```sh
php artisan translations:export -o /tmp/my-translations.csv
```

## Maintainers

- [Francesco](https://github.com/fschirinzi)

## License

`Translation Manager For Laravel` is open-sourced software licensed under [the MIT license](LICENSE.md).
