<?php

namespace App\Clients;

use Facebook\Facebook as OAuth;

class Facebook extends OAuthClient
{
    public function __construct( $client )
    {
        parent::__construct( $client );

        $this->client = $client;
        $this->oauth = new OAuth( config( 'services.facebook' ) );
    }

    public function storeCount( $datePreset = 'last_30d')
    {
        $num = 0;

        $response = $this->oauth->get(
            "/{$this->client->username}/insights/page_fans/day?date_preset={$datePreset}",
            $this->client->access_token
        );

        $data = $response->getDecodedBody();
        foreach ( $data['data'] as $period ) {
            if ( 'day' !== $period['period'] ) {
                continue;
            }

            foreach ( $period['values'] as $day ) {
                $timestamp = strtotime( $day['end_time'] );
                $date = date( 'Y-m-d', $timestamp );

                $count = $this->client->getCount( $date );

                if ( null == $count ) {
                    $this->client->counts()->create([
                        'date' => $date,
                        'count' => $day['value']
                    ]);
                } else {
                    $count->update(['count' => $day['value']]);
                }

                $num = $day['value'];
            }
        }

        return $num;
    }

    public function getPostCounts( $postId )
    {
        $response = $this->oauth->get(
            "/{$this->client->username}_{$postId}/insights/post_activity_by_action_type",
            $this->client->access_token
        );

       /* $response = $this->oauth->get(
            "/{$this->client->username}_{$postId}/insights/post_reactions_by_type_total",
            $this->client->access_token
        );*/

        $data = $response->getDecodedBody();
        foreach ( $data['data'] as $period ) {
            foreach ( $period['values'] as $counts ) {
                return $counts['value'];
            }
        }

        return false;
    }
}