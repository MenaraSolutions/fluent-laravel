# fluent-laravel
[![Build Status](https://travis-ci.org/MenaraSolutions/fluent-laravel.svg?branch=master)](https://travis-ci.org/MenaraSolutions/fluent-laravel)
[![Code Climate](https://codeclimate.com/github/MenaraSolutions/fluent-laravel/badges/gpa.svg)](https://codeclimate.com/github/MenaraSolutions/fluent-laravel)
[![Test Coverage](https://codeclimate.com/github/MenaraSolutions/fluent-laravel/badges/coverage.svg)](https://codeclimate.com/github/MenaraSolutions/fluent-laravel/coverage)

Laravel package for Fluent translations (application localisation)

## Localization flow of Laravel

### Key-based language files: {locale}/{group}.php (PHP array format)

Use: ```__('auth.please_wait')```

Drawbacks: after adding a new key to the main locale, the same key has to be manually added to all other locales.

It's easy for locales to go out of sync. Some texts may be present in the language files but not be in use anywhere.

### JSON files: {locale}.json (JSON format)

Use: ```__('Please wait, authorizing')```

Drawbacks: if the same text needs different translations in different contexts, this doesn't work well.

It's hard to say how much of content is translated becase there is no original text dictionary.

## Localization flow of Fluent

## Note

This package **will not** alter any of your files. This package creates an extra folder in `resources/` - `lang-fluent`, all new languagae
files will be stored here.

You can move back to standard translation implementation at any time. Simply comment out our service provider in `config/app.php`.
