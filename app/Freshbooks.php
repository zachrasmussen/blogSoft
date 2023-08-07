<?php

namespace App;

use Auth;
use App\Setting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Freshbooks
{
    protected $uri = 'https://api.freshbooks.com';
    protected $headers = [
        'api-version' => 'alpha',
        'content-type' => 'application/json'
    ];

    protected $client;
    protected $retries = 1;

    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;

    protected $body;
    protected $accessToken;
    protected $refreshToken;
    protected $tokenExpiry;

    public function __construct()
    {
        $this->clientId     = config('freshbooks.client_id');
        $this->clientSecret = config('freshbooks.client_secret');
        $this->redirectUri  = config('freshbooks.redirect_uri');

        $setting = Setting::where(['user_id' => Auth::id(), 'name' => 'freshbooks_access_token'])->first();

        if ( $setting && $setting->value ) {
            $this->setAccessToken( json_decode( $setting->value ) );
        }

        $this->client = new Client([
            'base_uri' => $this->uri,
            'headers' => $this->headers
        ]);
    }

    public function request( $method, $endpoint, $body = [], $headers = [] )
    {
        if ( $this->isTokenExpired() ) {
            $this->refreshAccessToken();
        }

        $request = [];

        if ( !empty( $headers ) ) {
            $request['headers'] = $headers;
        }

        if ( !empty( $body ) ) {
            if ( $method === 'GET' ) {
                $request['query'] = $body;
            } else {
                $request['json'] = $body;
            }
        }

        try {
            $response = $this->client->request(
                $method,
                $endpoint,
                $request
            );
        } catch( ClientException $e ) {
            $response = $e->getResponse();
            $responseBody = json_decode( $response->getBody() );

            if ( isset( $responseBody->error ) && $responseBody->error === 'unauthenticated' && $this->retries > 0 ) {
                $this->refreshAccessToken();

                $this->retries--;
                $response = $this->request( $method, $endpoint, $body, $headers );
            } else {
                report( $e );
            }
        }

        return $response;
    }

    public function isTokenExpired()
    {
        return $this->tokenExpiry < time();
    }

    public function isIntegrated()
    {
        return $this->accessToken ? true : false;
    }

    public function getAuthorizationUrl()
    {
        return "https://my.freshbooks.com/service/auth/oauth/authorize?client_id=$this->clientId&response_type=code&redirect_uri=$this->redirectUri";
    }

    public function authenticate( $authCode )
    {
        try {
            $response = $this->client->request(
                'POST',
                '/auth/oauth/token',
                [
                    'json' => [
                        'grant_type'    => 'authorization_code',
                        'client_id'     => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'redirect_uri'  => $this->redirectUri,
                        'code'          => $authCode
                    ]
                ]
            );
        } catch ( ClientException $e ) {
            report( $e );

            $response = $e->getResponse();
        }

        $body = json_decode( (string) $response->getBody() );
        
        if ( $response->getStatusCode() === 200 ) {
            $this->setAccessToken( $body, true );
        }
    }

    public function refreshAccessToken()
    {
        try {
            $response = $this->client->request(
                'POST',
                '/auth/oauth/token',
                [
                    'json' => [
                        'grant_type'    => 'refresh_token',
                        'client_id'     => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'redirect_uri'  => $this->redirectUri,
                        'refresh_token' => $this->refreshToken
                    ]
                ]
            );
        } catch ( ClientException $e ) {
            report( $e );

            $response = $e->getResponse();
        }

        $body = json_decode( (string) $response->getBody() );

        if ( $response->getStatusCode() === 200 ) {
            $this->setAccessToken( $body, true );
        } else {
            $errorType = $body->error;
            $errorMessage = $body->error_description;
        }
    }

    public function setAccessToken( $token, $store = false )
    {
        $this->accessToken  = $token->access_token;
        $this->refreshToken = $token->refresh_token;
        $this->tokenExpiry  = $token->created_at + $token->expires_in;

        $this->headers['Authorization'] = 'Bearer ' . $this->accessToken;

        if ( $store ) {
            $setting = Setting::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'name' => 'freshbooks_access_token'
                ],
                [
                    'value' => json_encode( $token )
                ]
            );
        }
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getUser()
    {
        $response = $this->request(
            'GET',
            '/auth/api/v1/users/me'
        );

        if ( $response->getStatusCode() === 200 ) {
            $body = json_decode( $response->getBody() );
            return $body->response;
        }

        return false;
    }

    public function getPayments( $accountId, $dateMin = false, $dateMax = false )
    {
        $body = [];

        if ( $dateMin && $dateMax ) {
            $body = [
                'date_min' => $dateMin,
                'date_max' => $dateMax
            ];
        }

        $response = $this->request(
            'GET',
            "/accounting/account/$accountId/payments/payments",
            $body
        );

        if ( $response->getStatusCode() === 200 ) {
            $body = json_decode( $response->getBody() );

            $payments = $body->response->result->payments;

            if ( $body->response->result->pages > 1 ) {
                for ( $i = 2; $i < $body->response->result->pages; $i++ ) {
                    $response = $this->request(
                        'GET',
                        "/accounting/account/$accountId/payments/payments",
                        ['page' => $i]
                    );

                    if ( $response->getStatusCode() === 200 ) {
                        $newBody = json_decode( $response->getBody() );

                        $payments = array_merge( $payments, $newBody->response->result->payments );
                    }
                }
            }

            return collect( $payments );
        }

        return false;
    }

    public function getExpenseCategories( $accountId )
    {
        $response = $this->request(
            'GET',
            "/accounting/account/$accountId/expenses/categories"
        );

        if ( $response->getStatusCode() === 200 ) {
            $body = json_decode( $response->getBody() );
            
            $categories = $body->response->result->categories;

            if ( $body->response->result->pages > 1 ) {
                for ( $i = 2; $i < $body->response->result->pages; $i++ ) {
                    $response = $this->request(
                        'GET',
                        "/accounting/account/$accountId/expenses/categories",
                        ['page' => $i]
                    );

                    if ( $response->getStatusCode() === 200 ) {
                        $newBody = json_decode( $response->getBody() );

                        $categories = array_merge( $categories, $newBody->response->result->categories );
                    }
                }
            }
            
            return collect( $categories );
        }

        return false;
    }

    public function getExpenses( $accountId, $dateMin = false, $dateMax = false )
    {
        $body = [];

        if ( $dateMin && $dateMax ) {
            $body = [
                'date_min' => $dateMin,
                'date_max' => $dateMax
            ];
        }

        $response = $this->request(
            'GET',
            "/accounting/account/$accountId/expenses/expenses",
            $body
        );

        if ( $response->getStatusCode() === 200 ) {
            $body = json_decode( $response->getBody() );

            $expenses = $body->response->result->expenses;

            if ( $body->response->result->pages > 1 ) {
                for ( $i = 2; $i < $body->response->result->pages; $i++ ) {
                    $response = $this->request(
                        'GET',
                        "/accounting/account/$accountId/expenses/categories",
                        ['page' => $i]
                    );

                    if ( $response->getStatusCode() === 200 ) {
                        $newBody = json_decode( $response->getBody() );

                        $expenses = array_merge( $expenses, $newBody->response->result->expenses );
                    }
                }
            }

            return collect( $expenses );
        }

        return false;
    }

    public function getReport( $report, $accountId, $args )
    {
        $response = $this->request(
            'GET',
            "/accounting/account/$accountId/reports/accounting/$report",
            $args
        );

        if ( $response->getStatusCode() === 200 ) {
            $body = json_decode( $response->getBody() );
            return $body->response->result->profitloss;
        }

        return false;
    }

    public function getTimeEntries( $businessId )
    {
        $response = $this->request(
            'GET',
            "/timetracking/business/$businessId/time_entries"
        );

        if ( $response->getStatusCode() === 200 ) {
            $body = json_decode( $response->getBody() );

            $entries = (array) $body->time_entries;

            if ( $body->meta->pages > 1 ) {
                for ( $i = 2; $i < $body->meta->pages; $i++ ) {
                    $response = $this->request(
                        'GET',
                        "/timetracking/business/$businessId/time_entries",
                        ['page' => $i]
                    );

                    if ( $response->getStatusCode() === 200 ) {
                        $newBody = json_decode( $response->getBody() );

                        $entries = array_merge( $entries, $newBody->time_entries );
                    }
                }
            }

            return $entries;
        }

        return [];
    }
}