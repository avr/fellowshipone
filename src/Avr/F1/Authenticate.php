<?php namespace Avr\F1;

use Cache;
use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;
use Guzzle\Http\Exception\ClientErrorResponseException;

/**
 * Helper Class for the FellowshipOne.com API in Laravel
 * Using some methods adapted from Tracy Mazelin:
 * @link https://github.com/tracymazelin/laravel-fellowshipone
 */

class Authenticate {

    public $settings;

    /**
     * contruct fellowship one class with settings array that contains
     * @param unknown_type $settings
     */
    public function __construct($settings)
    {
        $this->settings = (object) $settings;
        return $this->login($settings['username'], $settings['password']);
    }

    /**
     * 2nd Party credentials based authentication
     * @param string $username
     * @param string $password
     * @return boolean
     */
    public function login($username, $password)
    {
        $token = $this->getAccessToken();

        if (!$token) {
            $tokens = $this->obtainAccessToken($username, $password);
            $this->saveAccessToken($tokens);
        }

        $this->accessToken = $token;

        return true;
    }

    /**
     * obtain credentials based access token from API
     * @param string $username
     * @param string $password
     * @return array
     */
    protected function obtainAccessToken($username, $password)
    {
        try {
            $client = new Client($this->settings->baseUrl);

            $oauth  = new OauthPlugin(array(
                'consumer_key'  => $this->settings->key,
                'consumer_secret' => $this->settings->secret,
            ));

            $client->addSubscriber($oauth);

            // Create the URL
            $credentials = urlencode(base64_encode("{$username} {$password}"));
            $url = $this->settings->accessTokenUrl .'?ec='.$credentials;

            // Request the tokens
            $response = $client->post($url)->send();

            $tokens = [
                'oauth_token' => $response->getHeader('oauth_token')->raw()[0],
                'oauth_token_secret' => $response->getHeader('oauth_token_secret')->raw()[0],
            ];

            return $tokens;

        } catch (ClientErrorResponseException $e) {
            die($e->getMessage());
        }
    }

    /**
     * Retrieve access token from cache
     * @param string $username
     * @return array|NULL
     */
    protected function getAccessToken()
    {
        if (Cache::has('f1tokens')) {
             $tokens = Cache::get('f1tokens');
             return $tokens;
        }
        return null;
    }

    /**
     * Store access token to cache
     * @param array $token
     */
    protected function saveAccessToken($tokens)
    {
        Cache::forever('f1tokens', $tokens);
    }

}