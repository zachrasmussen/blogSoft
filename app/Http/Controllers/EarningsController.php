<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Freshbooks;

class EarningsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function reports( Request $request )
    {
        $freshbooks = new Freshbooks;

        if ( !$freshbooks->isIntegrated() ) {
            return redirect()->route('freshbooks');
        }

        $user = $freshbooks->getUser();

        $businesses = collect( $user->business_memberships );

        $activeBusiness = ( $request->has('account') )
            ? $businesses->first(function($value, $key) { return $value->business->account_id === $request->input('account'); })
            : $businesses->first();

        $expenses = $freshbooks->getExpenses( $activeBusiness->business->account_id );
        $payments = $freshbooks->getPayments( $activeBusiness->business->account_id );

        $transactions = $payments->merge( $expenses )->sortByDesc('date');

        return view('earnings.reports', [
            'user'           => $user,
            'businesses'     => $businesses,
            'activeBusiness' => $activeBusiness,
            'payments'       => $payments,
            'transactions'   => $transactions
        ]);
    }

    public function timeTracking( Request $request )
    {
        $freshbooks = new Freshbooks;

        if ( !$freshbooks->isIntegrated() ) {
            return redirect()->route('freshbooks');
        }

        $user = $freshbooks->getUser();

        $businesses = collect( $user->business_memberships );

        $activeBusiness = ( $request->has('account') )
            ? $businesses->first(function($value, $key) { return $value->business->account_id === $request->input('account'); })
            : $businesses->first();

        return view('earnings.timetracking', [
            'user' => $user,
            'businesses' => $businesses,
            'activeBusiness' => $activeBusiness,
        ]);
    }
}
