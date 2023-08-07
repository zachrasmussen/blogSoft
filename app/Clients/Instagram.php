<?php

namespace App\Clients;

use Carbon\Carbon;
use MetzWeb\Instagram\Instagram as OAuth;

class Instagram extends OAuthClient
{
    public function __construct( $client )
    {
        parent::__construct( $client );
        
        $this->client = $client;
        $this->oauth = new OAuth([
            'apiKey' => config( 'services.instagram.client_id' ),
            'apiSecret' => config( 'services.instagram.client_secret' ),
            'apiCallback' => config( 'services.instagram.redirect' ),
        ]);

        $this->oauth->setAccessToken( $this->client->access_token );
    }

    public function storeCount($yesterday = false)
    {
        $num = 0;

        if($yesterday){
            $date = date( 'Y-m-d',strtotime("-1 days"));
            $count = $this->client->getCount( $date );
        }else{
            $date = date( 'Y-m-d' );
            $count = $this->client->getCount( $date );
        }

        $user = $this->oauth->getUser();

        if ( null == $count ) {
            $this->client->counts()->create([
                'date' => $date,
                'count' => $user->data->counts->followed_by
            ]);
        } else {
            $count->update(['count' => $user->data->counts->followed_by]);
        }

        $num = $user->data->counts->followed_by;

        return $num;
    }

    public function getPostCounts($posturl)
    {
        $counts = [];

        if($posturl != null){
            $api = file_get_contents("http://api.instagram.com/oembed?url=$posturl");
            $apiObj = json_decode($api, true);
            $media_id = isset($apiObj['media_id'])?$apiObj['media_id']:'';
            $author_name = isset($apiObj['author_name'])?$apiObj['author_name']:'';

            if($media_id != '' && $author_name == $this->client->username){
                $user = $this->oauth->getMedia($media_id);
                if($user->meta->code == 200){
                    $counts['like']  = (isset($user->data->likes->count))?$user->data->likes->count:0;
                    $counts['comments']  = (isset($user->data->comments->count))?$user->data->comments->count:0;

                    return $counts;
                }
            }

        }
        return false;
    }
}