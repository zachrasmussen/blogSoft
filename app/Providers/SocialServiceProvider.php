<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Auth;
use Facebook\Facebook;
use Abraham\TwitterOAuth\TwitterOAuth;
use DirkGroenen\Pinterest\Pinterest;

class SocialServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        'Instagram',
        'Facebook',
        TwitterOAuth::class,
        Pinterest::class
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Instagram OAuth Client Provider
         */
        $this->app->singleton( 'Instagram', function() {
            return new Facebook( config( 'services.instagram' ) );
        } );

        /**
         * Facebook OAuth Client Provider
         */
        $this->app->singleton( 'Facebook', function() {
            return new Facebook( config( 'services.facebook' ) );
        } );

        /**
         * Twitter OAuth Client Provider
         */
        $this->app->singleton( TwitterOAuth::class, function() {
            return new TwitterOAuth(
                config('services.twitter.client_id'),
                config('services.twitter.client_secret')
            );
        } );

        // Check if there's a user and if we can authenticate.
        $this->app->resolving( TwitterOAuth::class, function( $twitter ) {
            $user = Auth::user();

            if ( $user ) {
                $company = $user->activeCompany();
                $client = $company->getClient('twitter');
                if ( $client ) {
                    $accessToken = $client->access_token;
                    $accessSecret = $client->getData('name', 'secret');

                    $twitter->setOauthToken( $accessToken, $accessSecret );

                    $verify = $twitter->get('account/verify_credentials');

                    if ( isset( $verify->errors ) ) {
                        throw new \Exception( $verify->errors[0]->message );
                    }
                }
            }

            return $twitter;
        } );

        /**
         * Pinterest OAuth Client Provider
         */
        $this->app->singleton( Pinterest::class, function($app) {
            return new Pinterest(
                config('services.pinterest.client_id'),
                config('services.pinterest.client_secret')
            );
        } );

        // Check if there's a user and if we can authenticate.
        $this->app->resolving( Pinterest::class, function( Pinterest $pinterest ) {
            $user = Auth::user();

            if ( $user ) {
                $company = $user->activeCompany();
                $client = $company->getClient('pinterest');
                if ( $client ) {
                    $pinterest->auth->setOAuthToken( $client->access_token );
                }
            }

            return $pinterest;
        } );
    }
}
