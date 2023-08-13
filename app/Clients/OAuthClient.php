<?php

namespace App\Clients;

use App\Client;
use App\Exceptions\ClientNotFoundException;

class OAuthClient
{
    protected $oauth;
    protected $client;

    public function __construct( $client )
    {
        if ( !is_a( $client, Client::class ) ) {
            throw new ClientNotFoundException;
        }
    }

    /**
     * Used by social media clients
     */
    public function storeCount()
    {}
}