<?php namespace Avr\F1;

use Illuminate\Support\ServiceProvider;

class F1ServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
	public function boot()
	{
        $this->package('avr/f1', 'avr/f1');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app['f1'] = $this->app->share(function($app)
        {
            $settings = $app['config']['avr/f1::config'];

            // Get access tokens from F1
            $authenticate = new Authenticate($settings);

            // Create the HTTP Client
            $client = new \Guzzle\Http\Client($app['config']['avr/f1::config.baseUrl']);

            // Authenticate with F1
            $oauth  = new \Guzzle\Plugin\Oauth\OauthPlugin(array(
                'consumer_key'    => $app['config']['avr/f1::config.key'],
                'consumer_secret' => $app['config']['avr/f1::config.secret'],
                'token'           => $authenticate->accessToken['oauth_token'],
                'token_secret'    => $authenticate->accessToken['oauth_token_secret'],
            ));

            // Return authenticated HTTP client
            return $client->addSubscriber($oauth);

        });

        // Create the F1 alias
        $this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('F1', 'Avr\F1\Facade\F1');
        });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('f1');
	}

}