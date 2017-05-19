# fluent-laravel
[![Build Status](https://travis-ci.org/MenaraSolutions/fluent-laravel.svg?branch=master)](https://travis-ci.org/MenaraSolutions/fluent-laravel)
[![Code Climate](https://codeclimate.com/github/MenaraSolutions/fluent-laravel/badges/gpa.svg)](https://codeclimate.com/github/MenaraSolutions/fluent-laravel)
[![Test Coverage](https://codeclimate.com/github/MenaraSolutions/fluent-laravel/badges/coverage.svg)](https://codeclimate.com/github/MenaraSolutions/fluent-laravel/coverage)

Laravel package for Fluent translations (application localisation)

## Localization flow of Laravel

Laravel offers two options for localization: key-based texts and plain JSON texts.

### Key-based language files: {locale}/{group}.php (PHP array format)

Use: ```__('auth.please_wait')```

Drawbacks: after adding a new key to the main locale, the same key has to be manually added to all other locales.

It's easy for locales to go out of sync. Some texts may be present in the language files but not be in use anywhere.

### JSON files: {locale}.json (JSON format)

Use: ```__('Please wait, authorizing')```

Drawbacks: if the same text needs different translations in different contexts, this doesn't work well.

It's hard to say how much of content is translated because there is no original text dictionary.

## Localization flow of Fluent

Fluent offers you to store and manage your translations in the "cloud" which makes it trivial to outsource translations
to translation market places or colleagues. Even if you translate everything yourself, Fluent interface will be far
more convenient for this task (eg. you will see clearly current status, get hints from MTs, find unused texts, etc).

## Installation

Use Composer to install this package:

```bash
$ composer require menarasolutions/fluent-laravel
```

Add our service provider to ```config/app.php```:

```php
 'providers' => [
 /// ...
     MenaraSolutions\FluentLaravel\Providers\LaravelServiceProvider::class,
 /// ...
 ],
```

Create a config file (`config/fluent.php`):

```bash
$ php artisan vendor:publish
```

Add your Fluent API key and application ID in this config file and you are ready to run!

## Uploading original texts

You need to submit your original, untranslated texts to Fluent from time to time so that you or your translators
can start working on translations. To do that run `scan` command:

```bash
$ php artisan fluent:scan -v
```

This command will scan your `src` and `resources/views` folders and look for all invocations of `__()`, `trans()` and `@lang()`.
Moreover, you will be prompted whether you want to upload your existing translations from `resources/lang` folder to Fluent.

## Updating language files

Run the following command anytime during development or during your CI/CD pipeline:

```bash
$ php artisan fluent:fetch
```

This command will look for any new translations on Fluent and download them to `resources/lang-fluent` folder.

## Note

This package **will not** alter any of your files. This package creates an extra folder in `resources/` - `lang-fluent`, all new languagae
files will be stored here.

You can move back to standard translation implementation at any time. Simply comment out our service provider in `config/app.php`.
