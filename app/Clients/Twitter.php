<?php

namespace App\Clients;

use Abraham\TwitterOAuth\TwitterOAuth;

use App\Exceptions\ClientAuthenticationException;

class Twitter extends OAuthClient
{
    public function __construct( $client )
    {
        parent::__construct( $client );

        $this->client = $client;
        $this->oauth = new TwitterOAuth(
            config('services.twitter.client_id'),
            config('services.twitter.client_secret')
        );

        $accessToken = $client->access_token;
        $accessSecret = $client->getData('secret');

        $this->oauth->setOauthToken( $accessToken, (string) $accessSecret );

        $verify = $this->oauth->get('account/verify_credentials');

        if ( isset( $verify->errors ) ) {
            throw new ClientAuthenticationException( $verify->errors[0]->message );
        }
    }

    public function storeCount($yesterday = false)
    {
        $userData = $this->oauth->get( 'users/show', ['user_id' => $this->client->username] );
        $num = $userData->followers_count;

        if($yesterday){
            $today = date( 'Y-m-d',strtotime("-1 days"));
            $count = $this->client->getCount( $today );
        }else{
            $today = date( 'Y-m-d' );
            $count = $this->client->getCount( $today );
        }

        if ( null == $count ) {
            $count = $this->client->counts()->create([
                'date'  => $today,
                'count' => $num
            ]);
        } else {
            $count->update(['count' => $num]);
        }

        return $num;
    }

    public function getPostCounts( $postId )
    {
        $postData = $this->oauth->get(
            'statuses/show',
            ['id' => $postId]
        );

        $counts = [];

        if ( $postData->favorite_count ) {
            $counts['favorite'] = (int) $postData->favorite_count;
        }

        if ( $postData->retweet_count ) {
            $counts['retweets'] = (int) $postData->retweet_count;
        }

        if ( !empty( $counts ) ) {
            return $counts;
        }

        return false;
    }

    public function checkTweet($postId){
        $postData = $this->oauth->get(
            'statuses/show',
            ['id' => $postId]
        );

        if(isset($postData->id) && $postData->id > 0){
            return true;
        }

        return false;
    }
}