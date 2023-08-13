<?php

namespace App\Clients;

use DirkGroenen\Pinterest\Pinterest as OAuth;
use DirkGroenen\Pinterest\Exceptions\PinterestException;

class Pinterest extends OAuthClient
{
    public function __construct( $client )
    {
        parent::__construct( $client );

        $this->client = $client;
        $this->oauth = new OAuth(
            config('services.pinterest.client_id'),
            config('services.pinterest.client_secret')
        );

        $this->oauth->auth->setOAuthToken( $this->client->access_token );
    }

    public function storeCount($yesterday = false)
    {
        $num = 0;

        try {
            if($yesterday){
                $today = date( 'Y-m-d',strtotime("-1 days"));
            }else{
                $today = date( 'Y-m-d' );
            }

            // calc follow count
            $me = $this->oauth->users->me(['fields' => 'counts']);

            $num = $me->counts['followers'];

            // find db entry if exists
            $count = $this->client->getCount( $today );

            if ( null == $count ) {
                $count = $this->client->counts()->create([
                    'date' => $today,
                    'count' => $num
                ]);
            } else {
                $count->update(['count' => $num]);
            }
        } catch ( PinterestException $e ) {
            return false;
        }

        return $num;
    }

    public function getPostCounts( $postId )
    {
        try {
            $postData = $this->oauth->pins->get( $postId,['fields' => 'created_at,link,creator,board,note,color,counts,media,attribution,image,metadata']);

            if ( $postData ) {
                return $postData->counts;
            }
        } catch ( PinterestException $e ) {
            return false;
        }

        return false;
    }

    public function getCountsByAccessToken($postId){

        if($postId != null){
            $url = "https://api.pinterest.com/v1/pins/".$postId."/?access_token=".$this->client->access_token."&fields=id%2Clink%2Cnote%2Curl%2Ccounts";

            $jsonData = json_decode(file_get_contents($url,false));
            if($jsonData === false){
                return false;
            }
            if(isset($jsonData->data)){
                return $jsonData->data->counts;
            }

            return false;

        }


        return false;
    }

    public function getPost( $postId )
    {
        try {
            $postData = $this->oauth->pins->get( $postId );

            if ( $postData ) {
                return true;
            }
        } catch ( PinterestException $e ) {
            return false;
        }

        return false;
    }
}