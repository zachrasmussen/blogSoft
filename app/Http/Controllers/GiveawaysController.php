<?php

namespace App\Http\Controllers;

use App\Giveaway;
use Illuminate\Http\Request;

class GiveawaysController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $giveaways = Giveaway::where('user_id', \Auth::id())->get();

        return view('giveaways.index', ['giveaways' => $giveaways]);
    }

    /**
     * Store giveaways
     */
    public function storeGiveaway( Request $request )
    {
        // name, color, owner_id
        $giveaway = ( $request->input('id') )
            ? Giveaway::firstOrFail( $request->input('id') )
            : new Giveaway;

        $giveaway->title = $request->input('title');
        $giveaway->sponsor_company = $request->input('sponsor_company');
        $giveaway->sponsor_company_name = $request->input('sponsor_company_name');
        $giveaway->sponsor_company_email = $request->input('sponsor_company_email');
        $giveaway->beginner_date = $request->input('beginner_date');
        $giveaway->end_date = $request->input('end_date');
        $giveaway->collaborator = $request->input('collaborator');
        $giveaway->winner_name = $request->input('winner_name');
        $giveaway->winner_email = $request->input('winner_email');
        $giveaway->winner_address = $request->input('winner_address');
        $giveaway->winner_city_state_zip = $request->input('winner_city_state_zip');
        $giveaway->prize_amount = $request->input('prize_amount');
        $giveaway->prize_details = $request->input('prize_details');
        $giveaway->shipping_sent_date = $request->input('shipping_sent_date');
        $giveaway->shipping_tracking_num = $request->input('shipping_tracking_num');
        $giveaway->shipping_tracking_url = $request->input('shipping_tracking_url');
        $giveaway->note = $request->input('note');
        $giveaway->user_id = \Auth::id();

        $giveaway->save();

        return redirect()->route( 'giveaways', [$giveaway] );
    }
}
