<?php

namespace MenaraSolutions\FluentLaravel\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use MenaraSolutions\FluentLaravel\Translation\Translator;
use Symfony\Component\Console\Output\OutputInterface;

class Scan extends Command
{
    const APP_TYPE_LARAVEL = 2;
    const APP_TYPE_TRANSLATION = 1;
    const LANG_FILE_HEADER = "<?php\n\nreturn ";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluent:scan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan Blade templates for missing JSON/key translations';

    /**
     * @var Client
     */
    protected $client;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->client = app('fluent.api_client');
    }

    /**
     * @param string $path
     * @return Collection
     */
    protected function scanFolder($path)
    {
        $this->output->writeln("Scanning {$path}", OutputInterface::VERBOSITY_VERBOSE);
        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
        $output = [];

        foreach ($objects as $name => $object) {
            if (is_dir($name)) continue;
            if (! preg_match('/\.php$/', $name)) continue;

            $regex1 = '(?:trans|__|@lang)\(\s*\'((?:[^\']|\\\\.)*)\'\s*\)';
            $regex2 = '(?:trans|__|@lang)\(\s*"((?:[^"]|\\\\.)*)"\s*\)';
            preg_match_all("/{$regex1}|{$regex2}/", file_get_contents($name), $matches);
            $matches = array_merge(array_filter($matches[1]), array_filter($matches[2]));

            if (! empty($matches)) {
                $this->output->writeln('- ' . $name . ': ' . count($matches), OutputInterface::VERBOSITY_VERBOSE);
                $output = array_merge($output, collect($matches)->map(function($text) { return stripslashes($text); })->toArray());
            }

            unset($matches);
        }

        return collect($output);
    }

    /**
     * @return Collection
     * @throws \Exception
     */
    protected function getLanguages()
    {
        $response = $this->client->get('/api/v1/apps');
        $apps = collect(json_decode((string)$response->getBody(), true)['items']);

        $original = $apps->first(function($item) { return $item['uuid'] == config('fluent.uuid') && $item['type'] == self::APP_TYPE_LARAVEL; });
        if (! $original) throw new \Exception('Couldn\'t find your app in API response. Wrong API token maybe?');

        $languages = $apps->filter(function($item) { return $item['type'] == self::APP_TYPE_TRANSLATION && $item['origin']['uuid'] == config('fluent.uuid'); });

        return $languages;
    }

    /**
     * @return \Illuminate\Translation\Translator
     */
    private function getLaravelTranslator()
    {
        $translator = app('translator');
        if ($translator instanceof Translator) return $translator->getOriginalTranslator();

        return $translator;
    }

    /**
     * @param Collection $texts
     * @param string $locale
     * @return Collection
     */
    protected function findUntranslated($texts, $locale = null)
    {
        $locale = $locale ?: config('app.locale');
        $defaultTranslator = $this->getLaravelTranslator();

        return $texts->reject(function($text) use ($defaultTranslator, $locale) {
            if ($locale == config('app.locale')) {
                if (strpos($text, ' ') !== false || strpos($text, '.') === false) return true;

                return $text != $defaultTranslator->getFromJson($text, [], $locale);
            }

            return $defaultTranslator->getFromJson($text, []) != $defaultTranslator->getFromJson($text, [], $locale);
        });
    }

    /**
     * @param Collection $texts
     * @param null $locale
     * @return Collection
     */
    protected function findTranslated($texts, $locale = null)
    {
        return $texts->diff($this->findUntranslated($texts, $locale));
    }

    /**
     * @param Collection $keys
     * @param string $locale
     * @return Collection
     */
    protected function getTranslated($keys, $locale = null)
    {
        $defaultTranslator = $this->getLaravelTranslator();

        return $this->findTranslated($keys, $locale)->map(function($key) use ($defaultTranslator, $locale) {
            return [
                'text' => $defaultTranslator->getFromJson($key, [], $locale),
                'source_context' => $key
            ];
        });
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $texts = $this->scanFolder(realpath(resource_path('views')))
            ->merge($this->scanFolder(realpath(base_path('app'))));
        $this->info("Discovered {$texts->count()} texts in templates and in source code");

        $untranslated = $this->findUntranslated($texts);
        $this->info("Discovered {$untranslated->count()} potentially missing texts in the original locale");

        $extraApps = $this->getLanguages();

        if ($extraApps->isEmpty()) {
            $this->info('Your Fluent app doesn\'t have any extra languages configured yet');
            return;
        }

        $this->info("Submitting original texts...");

        $response = $this->client->post('/api/v1/apps/' . config('fluent.uuid') . '/texts/batch', [
            'json' => $this->getTranslated($texts)
        ]);

        $confirmed = collect(json_decode((string)$response->getBody(), true)['items']);

        if ($this->confirm('Do you want to upload your existing translations?')) {
            $this->info("Submitting existing translations...");

            $extraApps->each(function($app) use ($texts, $confirmed) {
                $count = $this->findUntranslated($texts, $app['language'])->count();
                if ($count > 0) $this->warn("Discovered {$count} missing translations for locale " . $app['language']);

                $this->client->post('/api/v1/apps/' . $app['uuid'] . '/texts/batch', [
                    'json' => $this->getTranslated($texts, $app['language'])->map(function($text) use ($confirmed) {
                        $original = $confirmed->first(function($originalText) use ($text) { return $text['source_context'] == $originalText['source_context']; });

                        return array_merge($text, [
                            'origin_id' => $original ? $original['id'] : null
                        ]);
                    })
                ]);
            });
        }

        $this->info("Checking for new translations...");

        $translator = $this->getLaravelTranslator();

        $extraApps->each(function($app) use ($texts, $confirmed, $translator) {
            $response = $this->client->get('/api/v1/apps/' . $app['uuid'] . '/texts');
            $texts = collect(json_decode((string)$response->getBody(), true)['items']);

            $path = app('path.resources') . DIRECTORY_SEPARATOR . 'lang-fluent';
            if (! file_exists($path)) mkdir($path);

            $path .= DIRECTORY_SEPARATOR . $app['language'];
            if (! file_exists($path)) mkdir($path);

            $normalized = $texts->reduce(function($result, $text) use ($translator) {
                if ($text['source_context'] == '*' || $text['source_context'] == $text['origin']['text']) {
                    $group = '*';
                    $item = $text['origin']['text'];
                } else {
                    list($namespace, $group, $item) = $translator->parseKey($text['source_context']);
                }

                if (! isset($result[$group])) $result[$group] = [];
                $result[$group][$item] = $text['text'];

                return $result;
            }, []);

            collect($normalized)->each(function($group, $key) use ($path, $app) {
                if ($key == '*') {
                    file_put_contents(dirname($path) . DIRECTORY_SEPARATOR . $app['language'] . '.json', json_encode($group, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    return;
                }

                file_put_contents($path . DIRECTORY_SEPARATOR . $key . '.php', self::LANG_FILE_HEADER . var_export($group, true) . ';');
            });
        });
    }
}
