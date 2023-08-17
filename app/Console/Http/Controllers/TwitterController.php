<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Socialite;
use App\User;
use App\Client;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['refreshToken']);
    }
    
    public function index( Request $request )
    {
        $company = $request->user()->activeCompany();
        $client = $company->getClient('twitter');

        return view('settings.twitter', ['client' => $client]);
    }

    public function redirect()
    {
        return Socialite::driver('twitter')->redirect();
    }

    public function getToken( Request $request )
    {
        $twitter = Socialite::driver('twitter')->user();

        $company = $request->user()->activeCompany();
        $client = $company->getClient('twitter');

        if ( null == $client ) {
            $client = new Client;
        }

        $client->company_id    = $company->id;
        $client->type          = 'twitter';
        $client->username      = $twitter->id;
        $client->access_token  = $twitter->token;
        $client->refresh_token = null;
        $client->expiry        = null;

        $client->save();

        $client->data()->createMany([
            [
                'name' => 'secret',
                'value' => $twitter->tokenSecret
            ],
            [
                'name' => 'avatar',
                'value' => $twitter->avatar
            ],
            [
                'name' => 'nickname',
                'value' => $twitter->nickname
            ]
        ]);

        return redirect()->route('social-media')->with('success', 'Success! We\'re tracking your Twitter now, come back in a few days to see your activity!');
    }

    public function refreshToken(Request $request){
        $users = User::where(['status'=>'active'])->get();
        if($users != null && sizeof($users) > 0){
            foreach($users as $user){
                $company = $user->activeCompany();
                $client = $company->getClient('twitter');
                if($client != null){

                }
            }
        }
    }
}
