<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\User;
use App\Client;
use DirkGroenen\Pinterest\Pinterest;

class PinterestController extends Controller
{
    protected $pinterest;

    public function __construct()
    {
        $this->middleware('auth')->except(['refreshToken']);
        $this->middleware('company')->except(['refreshToken']);

        $this->pinterest = new Pinterest(
            config('services.pinterest.client_id'),
            config('services.pinterest.client_secret')
        );

        $user = Auth::user();

        if ( $user ) {
            $company = $user->activeCompany();
            $client = $company->getClient('pinterest');
            if ( $client ) {
                $pinterest->auth->setOAuthToken( $client->access_token );
            }
        }
    }

    public function index( Request $request )
    {
        $company = $request->user()->activeCompany();
        $client = $company->getClient('pinterest');

        return view('settings.pinterest', ['client' => $client]);
    }

    public function redirect()
    {
        $url = $this->pinterest->auth->getLoginUrl( route('pinterest.getToken'), ['read_public'] );

        return redirect()->away( $url );
    }

    public function getToken( Request $request )
    {
        try {
            if ( $request->has('code') ) {
                $token = $this->pinterest->auth->getOAuthToken( $request->input('code') );

                $this->pinterest->auth->setOAuthToken( $token->access_token );

                $me = $this->pinterest->users->me(['fields' => 'counts']);
    
                $user = $request->user();
                $company = $user->activeCompany();
                $client = $company->getClient('pinterest');
    
                if ( null == $client ) {
                    $client = new Client;
                }
        
                $client->company_id    = $company->id;
                $client->type          = 'pinterest';
                $client->username      = $company->name;
                $client->access_token  = $token->access_token;
                $client->refresh_token = null;
                $client->expiry        = null;
        
                $client->save();
    
                return redirect()->route('social-media')->with('success', 'Success! We\'re tracking your Pinterest now, come back in a few days to see your activity!');
            } else {
                return redirect()->route('pinterest');
            }

        } catch ( \Exception $e ) {
            return redirect()->route('pinterest')->withErrors( $e->getMessage() );
        }
    }

    public function refreshToken(Request $request){
        $users = User::where(['status'=>'active'])->get();
        if($users != null && sizeof($users) > 0){
            foreach($users as $user){
                $company = $user->activeCompany();
                $client = $company->getClient('pinterest');
                if($client != null){

                }
            }
        }
    }
}
