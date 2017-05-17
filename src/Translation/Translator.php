<?php

namespace MenaraSolutions\FluentLaravel\Translation;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;

class Translator extends \Illuminate\Translation\Translator implements TranslatorContract {
    /**
     * @var TranslatorContract
     */
    protected $original;

    /**
     * @param TranslatorContract $translator
     * @return $this
     */
    public function setOriginalTranslator(TranslatorContract $translator)
    {
        $this->original = $translator;

        return $this;
    }

    /**
     * @return TranslatorContract
     */
    public function getOriginalTranslator()
    {
        return $this->original;
    }

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array|null
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        list($namespace, $group, $item) = $this->parseKey($key);

        $locales = $fallback ? $this->localeArray($locale)
            : [$locale ?: $this->locale];

        foreach ($locales as $locale) {
            if (! is_null($line = $this->getLine(
                $namespace, $group, $locale, $item, $replace
            ))) {
                break;
            }
        }

        if (isset($line)) {
            return $line;
        }

        return $this->getOriginalTranslator()->get($key, $replace, $locale, $fallback);
    }

    /**
     * Get the translation for a given key from the JSON translation files.
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string  $locale
     * @return string
     */
    public function getFromJson($key, array $replace = [], $locale = null)
    {
        $locale = $locale ?: $this->locale;

        // For JSON translations, there is only one file per locale, so we will simply load
        // that file and then we will be ready to check the array for the key. These are
        // only one level deep so we do not need to do any fancy searching through it.
        $this->load('*', '*', $locale);

        $line = isset($this->loaded['*']['*'][$locale][$key])
            ? $this->loaded['*']['*'][$locale][$key] : null;

        // If we can't find a translation for the JSON key, we will attempt to translate it
        // using the typical translation file. This way developers can always just use a
        // helper such as __ instead of having to pick between trans or __ with views.

        if (! isset($line)) {
            $fallback = $this->get($key, $replace, $locale);

            if ($fallback !== $key) {
                return $fallback;
            }

            return $this->getOriginalTranslator()->getFromJson($key, $replace, $locale);
        }

        return $this->makeReplacements($line ?: $key, $replace);
    }
}