<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Website;
use App\SocialAccount;

class SocialMediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getAccounts( Website $website, Request $request )
    {
        return $website->accounts;
    }

    public function addAccount( Website $website, Request $request )
    {
        $request->validate([
            'type'     => 'required',
            'username' => 'required'
        ]);

        $account = new SocialAccount;

        $account->website_id = $website->id;
        $account->type = $request->input('type');
        $account->username = $request->input('username');

        $account->save();

        return response()->json([
            'status' => 'OK',
            'message' => 'Account added.',
            'data' => $account
        ]);
    }

    public function updateAccount( Website $website, Request $request )
    {
        $request->validate([
            'id' => 'required|exists:social_accounts,id',
            'type' => 'required',
            'username' => 'required'
        ]);

        $account = SocialAccount::firstOrFail( $request->id );

        $account->website_id = $website->id;
        $account->type = $request->input('type');
        $account->username = $request->input('username');

        $account->save();

        return response()->json([
            'status' => 'OK',
            'message' => 'Account updated.'
        ]);
    }

    public function deleteAccount( Website $website, Request $request )
    {
        SocialAccount::destroy( $request->input('id') );

        return response()->json([
            'status' => 'OK',
            'message' => 'Account removed.'
        ]);
    }
}
