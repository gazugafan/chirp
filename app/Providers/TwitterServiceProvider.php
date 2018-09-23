<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Library\Twitter;

class TwitterServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
		$this->app->singleton(Twitter::class, function ($app) {
			\Codebird\Codebird::setConsumerKey(env('TWITTER_CONSUMER_KEY'), env('TWITTER_CONSUMER_SECRET'));
			$cb = \Codebird\Codebird::getInstance();
			$cb->setToken(env('TWITTER_ACCESS_TOKEN'), env('TWITTER_ACCESS_SECRET'));
			return new Twitter($cb);
		});
    }
}
