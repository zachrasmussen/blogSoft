<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Website;
use App\SocialAccount;

class BlogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index( Request $request )
    {
        $website = ( Auth::id() )
            ? Website::findOrFail( Auth::id() )
            : Auth::user()->websites()->first();

        $facebook   = $this->facebookLikes( $website );
        $twitter    = $this->twitterFollowers( $website );
        $googleplus = $this->googlePlusFollowers( $website );
        $youtube    = $this->youtubeSubscribers( $website );

        return view( 'blog.index', [
            'website'    => $website,
            'facebook'   => $facebook,
            'twitter'    => $twitter,
            'googleplus' => $googleplus,
            'youtube'    => $youtube
        ] );
    }

    public function storeWebsite( Request $request )
    {
        $data = $request->validate([
            'id'   => 'nullable|exists:websites,id',
            'name' => 'required|max:60',
            'url'  => 'required|url'
        ]);

        $website = ( $request->input('id') )
            ? Website::findOrFail( $request->input('id') )
            : new Website;

        $website->name    = $request->input('name');
        $website->url     = $request->input('url');
        $website->user_id = $request->user()->id;

        $website->save();

        return back();
    }

    public function storeAccount( Request $request )
    {
        $data = $request->validate([
            'website_id' => 'required|exists:websites,id',
            'username'   => 'required|max:255',
            'type'       => 'required'
        ]);

        $account = ( $request->input('id') )
            ? Client::findOrFail( $request->input('id') )
            : new Client;

        $account->website_id = $request->input('website_id');
        $account->username   = $request->input('username');
        $account->type       = $request->input('type');

        $account->save();
    }

    public function facebookLikes( $website )
    {
        return false;
    }

    public function twitterFollowers( $website )
    {
        return false;
    }

    public function googlePlusFollowers( $website )
    {
        return false;
    }

    public function youtubeSubscribers( $website )
    {
        return false;
    }
}
