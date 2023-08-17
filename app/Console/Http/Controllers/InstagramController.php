<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Socialite;
use MetzWeb\Instagram\Instagram;
use App\Client;

class InstagramController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index( Request $request )
    {
        $company = $request->user()->activeCompany();
        $client = $company->getClient('instagram');

        return view('settings.instagram', ['client' => $client]);
    }

    /**
     * Redirect to Facebook to get Instagram
     */
    public function redirect( Request $request )
    {
        $instagram = new Instagram([
            'apiKey'      => config( 'services.instagram.client_id' ),
            'apiSecret'   => config( 'services.instagram.client_secret' ),
            'apiCallback' => config( 'services.instagram.redirect' ),
        ]);

        return redirect()->away( $instagram->getLoginUrl() );
    }

    public function getToken( Request $request )
    {
        if ( $request->has('code') ) {
            $instagram = new Instagram([
                'apiKey'      => config( 'services.instagram.client_id' ),
                'apiSecret'   => config( 'services.instagram.client_secret' ),
                'apiCallback' => config( 'services.instagram.redirect' ),
            ]);

            $data = $instagram->getOAuthToken( $request->input('code') );

            $company = $request->user()->activeCompany();
            $client = $company->getClient('instagram');

            if ( null == $client ) {
                $client = new Client;
            }

            $client->company_id    = $company->id;
            $client->type          = 'instagram';
            $client->username      = $data->user->username;
            $client->access_token  = $data->access_token;
            $client->refresh_token = null;
            $client->expiry        = null;

            $client->save();

            return redirect()->route('social-media')->with('success', 'Success! We\'re tracking your Instagram now, come back in a few days to see your activity!');
        }

        return redirect()->route('instagram.redirect');
    }
}
