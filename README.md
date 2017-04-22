# fluent-laravel
Laravel package for Fluent translations (application localisation)

## Localization flow of Laravel

Key-based language files: {locale}/{group}.php (PHP array format)
Use: __('auth.please_wait')
Drawbacks: after adding a new key to the main locale, the same key has to be manually added to all other locales.
It's easy for locales to go out of sync. Some texts may be present in the language files but not be in use anywhere.

JSON files: {locale}.json (JSON format)
Use: __('Please wait, authorizing')
Drawbacks: if the same text needs different translations in different contexts, this doesn't work well.
It's hard to say how much of content is translated becase there is no original text dictionary.
