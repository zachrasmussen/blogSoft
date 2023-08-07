<?php

namespace App\Clients;

use Google_Client;
use Google_Service_YouTube;

use App\ClientData;
use App\Exceptions\ClientNotFoundException;
use App\Exceptions\ClientAuthenticationException;

class YouTube extends OAuthClient
{
    public function __construct( $client )
    {
        parent::__construct( $client );

        $this->client = $client;

        // set up oauth client
        $this->oauth = new Google_Client;
        $this->oauth->setClientId( config( 'services.youtube.client_id' ) );
        $this->oauth->setClientSecret( config( 'services.youtube.client_secret' ) );
        $this->oauth->setRedirectUri( config( 'services.youtube.redirect' ) );
        $this->oauth->setAccessType( 'offline' );

        // set auth token
        $token = $this->client->getData( 'token' );

        $token = json_decode( $token, true );
        $this->oauth->setAccessToken( $token );

        // attempt to refresh token if it's expired
        if ( $this->oauth->isAccessTokenExpired() ) {
            $token = $this->oauth->fetchAccessTokenWithRefreshToken( $this->client->refresh_token );

            if ( isset( $token['error'] ) ) {
                throw new ClientAuthenticationException( $token['error_description'] );
            }

            $this->client->access_token = $token['access_token'];
            $this->client->expiry       = $token['created'] + $token['expires_in'];
            $this->client->save();

            $data = ClientData::firstOrNew(
                ['client_id' => $this->client->id, 'name' => 'token'],
                ['value' => json_encode( $token )]
            );

            $data->save();
        }

        // Then set up our service
        $this->service = new Google_Service_YouTube( $this->oauth );
    }

    public function getChannels()
    {
        $listResponse = $this->service->channels->listChannels('localizations', ['mine' => true, 'hl' => 'en']);

        return $listResponse->getItems();
    }

    public function getChannel()
    {
        $channelId = $this->client->getData('channel');

        $channels = $this->service->channels->listChannels('localizations,statistics', ['id' => $channelId, 'hl' => 'en']);

        if ( empty( $channels ) ) {
            return false;
        }

        return $channels[0];
    }

    public function storeCount($yesterday = false)
    {
        $num = 0;

        $channel = $this->getChannel();
        if ( false == $channel ) {
            return 0;
        }

        $stats = $channel->getStatistics();
        $num = $stats->getSubscriberCount();

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
        try{
            // Call the API's videos.list method to retrieve the video resource.
            $response = $this->service->videos->listVideos("statistics",
                array('id' => $postId));

            $googleService = current($response->items);
            if($googleService instanceof \Google_Service_YouTube_Video) {
                return $googleService->getStatistics();
            }
        } catch (\Google_Service_Exception $e) {
            /*return sprintf('<p>A service error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));*/
            return false;
        } catch (\Google_Exception $e) {
            /*return sprintf('<p>An client error occurred: <code>%s</code></p>',
                htmlspecialchars($e->getMessage()));*/
            return false;
        }
    }

    public function getChannelSubscribers($channelID){
        try{
            $Youtube_Api_Key = env('YOUTUBEAPI_KEY');
            if(!empty($Youtube_Api_Key))
            {
                $client = new \Google_Client();
                $client->setApplicationName('Legendize');
                $client->setDeveloperKey($Youtube_Api_Key);
                $service = new \Google_Service_YouTube($client);

                $queryParams = [
                    'id' => $channelID,
                    //'mine' => true,
                    'maxResults' => 50,
                ];

                $response = $service->channels->listChannels('statistics', $queryParams);
                if(!empty($response) && isset($response->items[0]["statistics"]))
                {
                    return $response->items[0]["statistics"]['subscriberCount'];
                }
            }
        }catch (\Google_Service_Exception $e){
            return false;
        }catch (\Google_Exception $e){
            return false;
        }catch (ClientAuthenticationException $e){
            return false;
        }catch (ClientNotFoundException $e){
            return false;
        }


    }
}