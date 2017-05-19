<?php

namespace MenaraSolutions\FluentLaravel\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use MenaraSolutions\FluentLaravel\Translation\Translator;
use Symfony\Component\Console\Output\OutputInterface;

class Fetch extends Command
{
    const APP_TYPE_LARAVEL = 2;
    const APP_TYPE_TRANSLATION = 1;
    const LANG_FILE_HEADER = "<?php\n\nreturn ";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluent:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch new translations from Fluent API';

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
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $extraApps = $this->getLanguages();

        if ($extraApps->isEmpty()) {
            $this->info('Your Fluent app doesn\'t have any extra languages configured yet');
            return;
        }

        $this->info("Checking for new translations...");
        $translator = $this->getLaravelTranslator();

        $extraApps->each(function($app) use ($translator) {
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