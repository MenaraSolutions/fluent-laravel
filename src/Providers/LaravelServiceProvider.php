<?php

namespace MenaraSolutions\FluentLaravel {
    use Illuminate\Support\ServiceProvider;

    class LaravelServiceProvider extends ServiceProvider
    {
        /**
         * Bootstrap any application services.
         *
         * @return void
         */
        public function boot()
        {
        }

        /**
         * Register any application services.
         *
         * @return void
         */
        public function register()
        {
            $this->app->singleton('fluent.api_client', function ($app) {
                return new Client([
                    'base_uri' => $app['config']->get('fluent.api_url'),
                    'headers'  => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $app['config']->get('fluent.api_token')
                    ]
                ]);
            });

            $this->app->extend('translator', function ($old, $app) {
                $loader = new FileLoader($app['files'], $app['path.resources'] . DIRECTORY_SEPARATOR . 'lang-fluent');
                $locale = $app['config']['app.locale'];

                $trans = (new Translator($loader, $locale))->setOriginalTranslator($old);
                $trans->setFallback($app['config']['app.fallback_locale']);

                return $trans;
            });
        }
    }
}

