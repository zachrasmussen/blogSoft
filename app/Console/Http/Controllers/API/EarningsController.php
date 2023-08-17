<?php

namespace App\Http\Controllers\API;

use App\Freshbooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Reports
     */
    public function getProfitLoss( $accountId, Request $request )
    {
        $freshbooks = new Freshbooks;

        $dateMin = ( $request->has('date_min') ) ? date('Y-m-d', strtotime('-7 days')) : $request->input('date_min');
        $dateMax = ( $request->has('date_max') ) ? date('Y-m-d') : $request->input('date_max');

        // $payments = $freshbooks->getPayments( $accountId, $dateMin, $dateMax );
        // $expenses = $freshbooks->getExpenses( $accountId, $dateMin, $dateMax );

        $data = $freshbooks->getReport( 'profitloss_entity', $accountId, ['start_date' => $dateMin, 'end_date' => $dateMax] );

        return response()->json([
            'status' => 'OK',
            'data'   => $data
        ]);
    }

    public function getExpenses( $accountId, Request $request )
    {
        $freshbooks = new Freshbooks;

        $categories = $freshbooks->getExpenseCategories( $accountId );
        $expenses = $freshbooks->getExpenses( $accountId );

        return response()->json([
            'status' => 'OK',
            'data'   => [
                'categories' => $categories,
                'expenses' => $expenses
            ]
        ]);
    }

    public function getPaymentsCollected( $accountId, Request $request )
    {
        $freshbooks = new Freshbooks;

        $dateMin = ( $request->has('date_min') ) ? date('Y-m-d', strtotime('-7 days')) : $request->input('date_min');
        $dateMax = ( $request->has('date_max') ) ? date('Y-m-d') : $request->input('date_max');

        $data = $freshbooks->getReport( 'payments_collected', $accountId, ['start_date' => $dateMin, 'end_date' => $dateMax] );

        return response()->json([
            'status' => 'OK',
            'data'   => $data
        ]);
    }

    public function getAccountsAging( $accountId, Request $request )
    {
        $freshbooks = new Freshbooks;

        $dateMin = ( $request->has('date_min') ) ? date('Y-m-d', strtotime('-7 days')) : $request->input('date_min');
        $dateMax = ( $request->has('date_max') ) ? date('Y-m-d') : $request->input('date_max');

        $data = $freshbooks->getReport( 'accounts_aging', $accountId, ['start_date' => $dateMin, 'end_date' => $dateMax] );

        return response()->json([
            'status' => 'OK',
            'data'   => $data
        ]);
    }

    public function getTimeEntries( $businessId, Request $request )
    {
        $freshbooks = new Freshbooks;

        $data = $freshbooks->getTimeEntries( $businessId );

        return response()->json([
            'status' => 'OK',
            'data'   => $data
        ]);
    }
}
