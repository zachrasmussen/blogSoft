<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Carbon\CarbonInterval;

use Auth;
use Socialite;
use App\Client;
use App\SocialCount;
use App\Clients\Instagram;
use App\Clients\Facebook;
use App\Clients\Twitter;
use App\Clients\Pinterest;
use App\Clients\YouTube;

class SocialMediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('company');
    }

    /**
     * Render social analytics
     */
    public function index( Request $request, $range = 'week' )
    {
        $user    = $request->user();
        $company = $user->activeCompany();
        $clients = $company->getClient('all');

        $instagram  = $this->getData( 'instagram', $company );
        $facebook   = $this->getData( 'facebook_page', $company );
        $twitter    = $this->getData( 'twitter', $company );
        $pinterest  = $this->getData( 'pinterest', $company );
        $youtube    = $this->getData( 'youtube', $company );

        if(!empty($clients)){
            foreach($clients as $client){
                $getCount = SocialCount::where('client_id',$client->id)->count();
                if($getCount == 0){
                    switch($client->type){
                        case 'youtube':
                            $getter = new YouTube( $client );
                            $getter->storeCount(true);
                            break;

                        case 'pinterest':
                            $getter = new Pinterest( $client );
                            $getter->storeCount(true);
                            break;

                        case 'facebook_page':
                            $getter = new Facebook( $client );
                            $getter->storeCount();
                            break;

                        case 'instagram':
                            $getter = new Instagram( $client );
                            $getter->storeCount(true);
                            break;

                        case 'twitter':
                            $getter = new Twitter( $client );
                            $getter->storeCount(true);
                            break;
                    }

                }

            }
        }
        $totals = $this->calcTotals( $instagram, $facebook, $twitter, $pinterest, $youtube );

        return view( 'social-media.index', [
            'company'   => $company,
            'range'     => $range,

            'totals'    => $totals,
            'instagram' => $instagram,
            'facebook'  => $facebook,
            'twitter'   => $twitter,
            'pinterest' => $pinterest,
            'youtube'   => $youtube,
        ] );
    }

    /**
     * Get Data for social media account
     */
    public function getData( $type, $company )
    {
        $client = $company->getClient($type);

        if ( null == $client ) {
            return false;
        }

        $start = Carbon::yesterday()->subDay();
        $end = Carbon::yesterday();
        $yesterday = $client->getCountsFor($start->format('Y-m-d'), $end->format('Y-m-d'));

        $start = Carbon::yesterday()->subWeek();
        $end = Carbon::yesterday();
        $week = $client->getCountsFor($start->format('Y-m-d'), $end->format('Y-m-d'));

        $start = Carbon::yesterday()->subMonth();
        $end = Carbon::yesterday();
        $month = $client->getCountsFor($start->format('Y-m-d'), $end->format('Y-m-d'));

        // Structure
        $data = [
            'yesterday' => [
                'difference' => ( $yesterday->count() > 0 ) ? ( $yesterday->last() - $yesterday->first() ) : 0,
                'count'      => $yesterday->last(),
            ],
            'week' => [
                'difference' => ( $week->count() > 0 ) ? ceil( $week->last() - $week->first() ) : 0,
                'percentage' => ( $week->count() > 0 ) ? $this->getPercentDifference( $week->last(), $week->first() ) : 0,
                'perDay'     => ( $week->count() > 0 ) ? $this->getPerDayDifference( $week->last(), $week->first() ) : 0,
            ],
            'month' => [
                'difference' => ( $month->count() > 0 ) ? ceil( $month->last() - $month->first() ) : 0,
                'percentage' => ( $month->count() > 0 ) ? $this->getPercentDifference( $month->last(), $month->first() ) : 0,
                'perDay'     => ( $month->count() > 0 ) ? $this->getPerDayDifference( $month->last(), $month->first() ) : 0,
            ],
        ];

        return $data;
    }

    /**
     * Calc the per day difference between the start and end counts
     */
    private function getPerDayDifference( $start, $end )
    {
        if ( $start === 0 || $end === 0 ) {
            return 0;
        }

        return (int) ceil( (( $start - $end ) / $end) );
    }

    /**
     * Calc the percent difference between the start and end counts
     */
    private function getPercentDifference( $start, $end )
    {
        if ( $start === 0 || $end === 0 ) {
            return 0;
        }

        return (int) ceil( (( $start - $end ) / $end) * 100 );
    }

    /**
     * Calc the averages of all the data
     */
    public function calcTotals( $instagram, $facebook, $twitter, $pinterest, $youtube )
    {
        $reach    = 0;
        $weekAvg  = 0;
        $monthAvg = 0;

        if ( $instagram ) {
            $reach    += $instagram['yesterday']['difference'];
            $weekAvg  += $instagram['week']['perDay'];
            $monthAvg += $instagram['month']['perDay'];
        }

        if ( $facebook ) {
            $reach    += $facebook['yesterday']['difference'];
            $weekAvg  += $facebook['week']['perDay'];
            $monthAvg += $facebook['month']['perDay'];
        }

        if ( $twitter ) {
            $reach    += $twitter['yesterday']['difference'];
            $weekAvg  += $twitter['week']['perDay'];
            $monthAvg += $twitter['month']['perDay'];
        }

        if ( $pinterest ) {
            $reach    += $pinterest['yesterday']['difference'];
            $weekAvg  += $pinterest['week']['perDay'];
            $monthAvg += $pinterest['month']['perDay'];
        }

        if ( $youtube ) {
            $reach    += $youtube['yesterday']['difference'];
            $weekAvg  += $youtube['week']['perDay'];
            $monthAvg += $youtube['month']['perDay'];
        }

        return [
            'reach' => (int) $reach,
            'week'  => (int) $weekAvg,
            'month' => (int) $monthAvg,
        ];
    }

    public function chartData( Request $request )
    {
        $graphData = array();

        $company = $request->user()->activeCompany();
        $timeframe = ( $request->has('timeframe') )
            ? $request->input('timeframe')
            : 'day';

        switch ( $timeframe ) {
            case 'month' : 
                $interval = CarbonInterval::month();
                break;

            case 'week' :
                $interval = CarbonInterval::week();
                break;

            case 'day' :
                $interval = CarbonInterval::day();
                break;
        }

        $data = [
            'labels' => [],
            'datasets' => []
        ];

        $setLabels = false;

        foreach( $company->clients as $client ) {
            $graphLabels   = array();

            if ( $client->type == 'facebook_user' || $client->type == 'analytics' ) {
                continue;
            }

            $group = [
                'label' => $client->getLabel(),
                'borderColor' => $client->getColor(),
                'data' => []
            ];

            $start = Carbon::today();

            for ( $i = 0; $i < 6; $i++ ) {
                $day = $start->sub( $interval );

                $count = $client->getCount( $day->format('Y-m-d') );

                //$group['data'][] = ($count) ? $count->count : null;
                //$group['data'][] = ($count) ? $count->count : 0;
                $graphLabels[] = ($count) ? $count->count : 0;

                if ( false === $setLabels ) {
                    //$data['labels'][] = $day->format('m-d-Y');
                    $graphData[]  = $day->format('m-d-Y');
                }
            }
            $group['data'] = array_reverse($graphLabels);
            $data['labels'] = array_reverse($graphData);
            $setLabels = true;
            $data['datasets'][] = $group;
        }
        return response()->json( $data );
    }

    public function accounts( Request $request )
    {
        $company = $request->user()->activeCompany();

        return response()->json( $company->clients );
    }
}
