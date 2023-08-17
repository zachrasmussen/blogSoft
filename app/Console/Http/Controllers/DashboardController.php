<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Clients\GoogleAnalytics;
use App\Analytic;

use App\Exceptions\ClientNotFoundException;
use App\Exceptions\ClientAuthenticationException;

class DashboardController extends Controller
{
    /**
     * Create new instance
     * 
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->middleware('auth');
    }

    /**
     * render the dashboard
     * 
     * @return \Illuminate\Http\Response
     */
    public function index( Request $request )
    {
        $company = $request->user()->activeCompany();
        $start_of_the_month =Carbon::now()->startOfMonth();
        $current_day_of_the_month = Carbon::now();
        $daysDiff = $start_of_the_month->diffInDays($current_day_of_the_month);
        $months =  array();
        $start_of_year =  Carbon::parse('first day of January');
        $custom    = false;
        $startDate = '';
        $endDate   = '';
        $period = CarbonPeriod::create($start_of_year, '1 month', $current_day_of_the_month);
        $monthName = '';
        $compare = false;
        $days = false;
        $label1 = '';
        $label2 = '';
        $analyticsData = '';
        $saveData =  array();
        $last_week = Carbon::now()->subDays(7);
        $last_week_from   = $last_week->toDateString();
        $last_week_to = $last_week->addDay(1)->toDateString();
        $label3 = 'Last Week '.$last_week->subDay(1)->format('l');

        foreach ($period as $dt) {
            $months[] =  $dt->englishMonth;
        }


        if ( null == $company ) {
            return redirect()->route('settings.setup');
        }

        $client = $company->getClient('analytics');
        if($client != null && isset($client->id)){
            //get analytics saved data from database
            $analyticsData =  Analytic::where(['client_id'=>$client->id,'type'=>'yesterday'])->whereDate('created_at', Carbon::today())->first();
        }
        try {

            $analytics = new GoogleAnalytics( $client );

            if ( $request->has('range') ) {
                $range = $request->input('range') . 'daysAgo';
                $numberOfDays = (int) $request->input('range');
                //$compare = true;
            }
            elseif ( $request->has('range') && $request->input('range') == "month_to_date") {
                $range = $request->input('range') . 'daysAgo';
                $numberOfDays = (int) $request->input('range');
                $compare = true;
            }else{
                $range = 'yesterday';
                $numberOfDays = 1;
            }

            $split = ( 'yesterday' == $range )
                ? 'ga:dateHour'
                : 'ga:date';

            if($request->has('range') && $request->input('range') == "7"){
                $label1 = 'Current 7 days';
                $label2 = 'Previous 7 days ';
                $days = true;
            }

            if($request->has('range') && $request->input('range') == "30"){
                $label1 = 'Current 30 days';
                $label2 = 'Previous 30 days ';
            }

            if(!$compare){
                $to   = Carbon::now()->toDateString();
                $from = Carbon::now()->subDays( $numberOfDays )->toDateString();

                $current = $analytics->getTraffic( $from, $to, $split,$days);

                $from = Carbon::now()->subDays( $numberOfDays * 2 )->toDateString();
                $to   = Carbon::now()->subDays( $numberOfDays )->toDateString();

                $previous = $analytics->getTraffic( $from, $to, $split,$days);

                $last_week_current_day = $analytics->getTraffic( $last_week_from, $last_week_to, $split,$days);
            }

            /*if($request->has('range') && $request->input('range') == "7"){
                $to   = Carbon::now()->toDateString();
                $from = Carbon::now()->subDays( $numberOfDays )->toDateString();

            }*/

            if($request->has('range') && $request->input('range') == "last_month") {
                $from   = new Carbon('first day of last month');
                $forPrevmonthStart = $from;
                $from   = $from->toDateString();

                $to = new Carbon('last day of last month');
                $to = $to->toDateString();

                $custom = true;
                $startDate = $from;
                $endDate   = $to;

                //$compare = true;
                $current = $analytics->getTraffic( $from, $to, $split );

                $from = $forPrevmonthStart->copy()->subMonth();
                $from   = $from->toDateString();

                $to   = $forPrevmonthStart->copy()->subMonth()->endOfMonth();
                $to = $to->toDateString();

                $previous = $analytics->getTraffic( $from, $to, $split,$days);

                $label1 = 'Current Last Month';
                $label2 = 'Previous Last Month ';


            }


            if($request->has('month')){
                $split = 'ga:date';

                $custom = true;
                $monthName = $request->input('month');

                $from = new Carbon('first day of '.$monthName);
                $prevMonth = $from;
                $from = $from->toDateString();

                $to = new Carbon('last day of '.$monthName);
                $to = $to->toDateString();

                $startDate = $from;
                $endDate   = $to;

                $current = $analytics->getTraffic( $from, $to, $split );

                $from = $prevMonth->copy()->subMonth();
                $from   = $from->toDateString();

                $to   = $prevMonth->copy()->subMonth()->endOfMonth();
                $to = $to->toDateString();

                $prevMonth = $prevMonth->copy()->subMonth()->format('F');

                $previous = $analytics->getTraffic( $from, $to, $split,$days);

                $label1 = $monthName;
                $label2 = $prevMonth;

                //$compare = true;


            }

            /*if( $request->has('range') && $request->input('range') == "month_to_date"){
                $from = Carbon::now()->subDays(30);
                $from = $from->toDateString();
                $startDate = $from;
                $to = $current_day_of_the_month->toDateString();
                $endDate   = $to;
                $split = 'ga:date';
                $custom = true;
                $compare = true;
            }*/


            /*if($request->has('to') && $request->has('from')){
                $startDate = $request->input('from');
                $from = $startDate;

                $endDate   = $request->input('to');
                $to = $endDate;

                $split = 'ga:date';
                $custom = true;
                $monthName = "Custom";
                $compare = true;
                if(strtotime($from) > strtotime($to)){
                    return view('dashboard')
                        ->withErrors(['danger'=>'Please select valid date range!']);
                }
            }*/

            if($request->has('to') && $request->has('from')){
                $split = 'ga:dateHour';
                $custom = true;
                $monthName = "Custom";

                $startDate = $request->input('from');
                $from = $startDate;

                $endDate   = $request->input('to');
                $to = $endDate;

                if(strtotime($from) > strtotime($to)){
                    return view('dashboard')
                        ->withErrors(['danger'=>'Please select valid date range!']);
                }

                $from  = Carbon::createFromFormat('Y-m-d', $startDate);
                $to    = $from->copy()->subDays( 1 )->toDateString();
                $from = $startDate;

                //$label1 = $to.' | '.$from;
                $label1 = $startDate;
                $current = $analytics->getTraffic( $to, $from, $split,$days);


                $to    = Carbon::createFromFormat('Y-m-d', $endDate);
                $from   = $to->subDays( 1 )->toDateString();
                $to = $endDate;

                $label2 = $endDate;
                //$label2 = $from.' | '.$to;
                $previous = $analytics->getTraffic( $from, $to, $split,$days);

            }


            /*if($compare){
                $current = $analytics->getSessions( $from, $to, $split );
                $previous = $analytics->getViews( $from, $to, $split );
                $label1 = 'Visitors';
                $label2 = 'PageViews';
            }*/

            //dd($current,$previous);
            //$range = '7daysAgo';

            //$socialStats = $analytics->getSourceSocials( $range,$custom,$startDate,$endDate);

            /*if($range == 'yesterday'){
                if($analyticsData == null){
                    $saveData['traffic'] = array(
                        'dataLabels'    => $current['labels'],
                        'currentLabel'  => ($label1!="")?$label1:strtoupper('Today'),
                        'previousLabel' => ($label2!="")?$label2:strtoupper($range),
                        'current'       => $current['values'],
                        'previous'      => $previous['values'],
                        'prevLabels'     => $previous['labels'],
                        'customPreviousLabel' => strtoupper($monthName),

                        'visits'        => $analytics->getVisits( $range,$custom,$startDate,$endDate),
                        //'avgMinutes'    => $analytics->getAvgTimeOnPage( $range,$custom,$startDate,$endDate),
                        'avgMinutes'    => $analytics->getAvgUserSession( $range,$custom,$startDate,$endDate),
                        'pageViews'     => $analytics->getPageViews( $range,$custom,$startDate,$endDate),
                        'bounceRate'    => $analytics->getBounceRate( $range,$custom,$startDate,$endDate),
                        'pagesPerVisit' => $analytics->getViewsPerSession( $range,$custom,$startDate,$endDate),
                        'newVisits'     => $analytics->getNewUsers( $range,$custom,$startDate,$endDate)
                    );

                    $saveData['sources'] = array(
                        'mediums'       => $analytics->getSourceMediums( $range,$custom,$startDate,$endDate),
                        'countries'     => $analytics->getSourceCountries( $range,$custom,$startDate,$endDate),
                        'devices'       => $analytics->getSourceDevices( $range,$custom,$startDate,$endDate),
                        'pages'         => $analytics->getSourcePagePaths( $range,$custom,$startDate,$endDate)
                    );

                    Analytic::updateOrCreate(
                        ['client_id' => $client->id, 'type' => 'yesterday','created_at'=>Carbon::today()],
                        ['data' => json_encode( $saveData )]
                    );

                    return view( 'dashboard', [
                        'traffic' => $saveData['traffic'],
                        'sources' => $saveData['sources'],

                        'social' => $this->getSocialData(),
                        'month_to_date' => $daysDiff,
                        'months'   => $months,
                        'custom'   => $custom,
                    ] );
                }else{
                    $analyticsData = $analyticsData->toArray();
                    $json = json_decode($analyticsData['data'],TRUE);

                    return view( 'dashboard', [
                        'traffic' => $json['traffic'],
                        'sources' => $json['sources'],

                        'social' => $this->getSocialData(),
                        'month_to_date' => $daysDiff,
                        'months'   => $months,
                        'custom'   => $custom,
                    ] );
                }
            }else{*/
                return view( 'dashboard', [
                    'traffic' => [
                        'dataLabels'    => $current['labels'],
                        'currentLabel'  => ($label1!="")?$label1:strtoupper('Today'),
                        'previousLabel' => ($label2!="")?$label2:strtoupper($range),
                        'lastWeekLabel' => $label3,
                        'current'       => $current['values'],
                        'previous'      => $previous['values'],
                        'prevLabels'     => $previous['labels'],
                        'lastWeek'       => $last_week_current_day['values'],
                        'customPreviousLabel' => strtoupper($monthName),

                        'visits'        => $analytics->getVisits( $range,$custom,$startDate,$endDate),
                        //'avgMinutes'    => $analytics->getAvgTimeOnPage( $range,$custom,$startDate,$endDate),
                        'avgMinutes'    => $analytics->getAvgUserSession( $range,$custom,$startDate,$endDate),
                        'pageViews'     => $analytics->getPageViews( $range,$custom,$startDate,$endDate),
                        'bounceRate'    => $analytics->getBounceRate( $range,$custom,$startDate,$endDate),
                        'pagesPerVisit' => $analytics->getViewsPerSession( $range,$custom,$startDate,$endDate),
                        'newVisits'     => $analytics->getNewUsers( $range,$custom,$startDate,$endDate)
                    ],

                    'sources' => [
                        'mediums'       => $analytics->getSourceMediums( $range,$custom,$startDate,$endDate),
                        'countries'     => $analytics->getSourceCountries( $range,$custom,$startDate,$endDate),
                        'devices'       => $analytics->getSourceDevices( $range,$custom,$startDate,$endDate),
                        'pages'         => $analytics->getSourcePagePaths( $range,$custom,$startDate,$endDate)
                    ],

                    'social' => $this->getSocialData(),
                    'month_to_date' => $daysDiff,
                    'months'   => $months,
                    'custom'   => $custom,
                ] );
            //}

        }
        catch ( ClientNotFoundException $e ) {
            return view('settings.analytics');
        }
        catch ( \Google_Service_Exception $e ) {
            //return redirect()->route('analytics.change.account');
            return view('settings.analytics')
                ->withErrors(['danger'=>'Cannot connect to Google Analytics, please refresh your authorization with Google.']);
        }
        catch ( ClientAuthenticationException $e ) {
            return view('settings.analytics')
                ->withErrors(['danger'=>'Cannot connect to Google Analytics, please refresh your authorization with Google.']);
        }
    }

    public function getSocialData()
    {
        // TODO: pull active social data
        return [1, 2, 3, 4, 5];
    }

    public function dashboardWithAjax(Request $request){
        $company = $request->user()->activeCompany();
        $current_day_of_the_month = Carbon::now();
        $months =  array();
        $start_of_year =  Carbon::parse('first day of January');
        $period = CarbonPeriod::create($start_of_year, '1 month', $current_day_of_the_month);


        foreach ($period as $dt) {
            $months[] =  $dt->englishMonth;
        }

        if ( null == $company ) {
            return redirect()->route('settings.setup');
        }

        $client = $company->getClient('analytics');

        if($client == null){
            return redirect()->route('settings.setup');
        }

        return view( 'ajax', [
            'social' => $this->getSocialData(),
            'months'   => $months,
        ] );
    }

    public function getTrafficData( Request $request ){
        $company = $request->user()->activeCompany();

        $monthName = '';
        $compare = false;
        $days = false;
        $label1 = '';
        $label2 = '';
        $month = $request->input('month');
        $range = $request->input('range');
        $months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        //For Last Week Same Day
        $last_week = Carbon::now()->subDays(7);
        $last_week_from   = $last_week->toDateString();
        $last_week_to = $last_week->addDay(1)->toDateString();
        $label3 = 'Last Week '.$last_week->subDay(1)->format('l');

        $type = 'traffic';
        $filter = 'yesterday';
        $traffic = [
            'labels' => [],
            'current' => [],
            'dataLabels'    => [],
            'currentLabel'  => 'Today',
            'previousLabel' => '',
            'current'       => [],
            'previous'      => [],
            'prevLabels'     => [],
            'customPreviousLabel' => strtoupper($monthName),

        ];
        $client = $company->getClient('analytics');

        if($client != null && isset($client->id)){
            $filter = $this->setFilter($range,$month);
            if(in_array($filter,$months)){
                $data = Analytic::getMonthRecord($client->id,$type,$filter);
            }else{
                $data = Analytic::getRecord($client->id,$type,$filter);
            }

            if($data != null){
                $created_at = $data['updated_at']->diffForHumans();
                $data = json_decode($data['data'],true);
                $data['created_at'] = $created_at;
                return response()->json( $data );
            }

            try {

                $analytics = new GoogleAnalytics( $client );

                if ( $request->has('range') ) {
                    $range = $request->input('range') . 'daysAgo';
                    $numberOfDays = (int) $request->input('range');
                }
                elseif ( $request->has('range') && $request->input('range') == "month_to_date") {
                    $range = $request->input('range') . 'daysAgo';
                    $numberOfDays = (int) $request->input('range');
                    $compare = true;
                }

                if ( $request->has('range') && $request->input('range') == null && $request->input('month') == null){
                    $range = 'yesterday';
                    $numberOfDays = 1;
                }


                $split = ( 'yesterday' == $range )
                    ? 'ga:dateHour'
                    : 'ga:date';


                if($request->has('range') && $request->input('range') == "7"){
                    $filter = '7';
                    $label1 = 'Current 7 days';
                    $label2 = 'Previous 7 days ';
                    $days = true;
                }

                if($request->has('range') && $request->input('range') == "30"){
                    $filter = '30';
                    $label1 = 'Current 30 days';
                    $label2 = 'Previous 30 days ';
                }

                if(!$compare){
                    $to   = Carbon::now()->toDateString();
                    $from = Carbon::now()->subDays( $numberOfDays )->toDateString();

                    $current = $analytics->getTraffic( $from, $to, $split,$days);

                    $from = Carbon::now()->subDays( $numberOfDays * 2 )->toDateString();
                    $to   = Carbon::now()->subDays( $numberOfDays )->toDateString();

                    $previous = $analytics->getTraffic( $from, $to, $split,$days);
                }



                if($request->has('range') && $request->input('range') == "last_month") {
                    $filter = 'last_month';
                    $from   = new Carbon('first day of last month');
                    $forPrevmonthStart = $from;
                    $from   = $from->toDateString();

                    $to = new Carbon('last day of last month');
                    $to = $to->toDateString();

                    $custom = true;
                    $startDate = $from;
                    $endDate   = $to;

                    //$compare = true;
                    $current = $analytics->getTraffic( $from, $to, $split );

                    $from = $forPrevmonthStart->copy()->subMonth();
                    $from   = $from->toDateString();

                    $to   = $forPrevmonthStart->copy()->subMonth()->endOfMonth();
                    $to = $to->toDateString();

                    $previous = $analytics->getTraffic( $from, $to, $split,$days);

                    $label1 = 'Current Last Month';
                    $label2 = 'Previous Last Month ';


                }


                if($request->has('month') && $request->input('month') != null && $request->input('range') == null){
                    $filter = $request->input('month');
                    $split = 'ga:date';

                    $custom = true;
                    $monthName = $request->input('month');

                    $from = new Carbon('first day of '.$monthName);
                    $prevMonth = $from;
                    $from = $from->toDateString();

                    $to = new Carbon('last day of '.$monthName);
                    $to = $to->toDateString();

                    $startDate = $from;
                    $endDate   = $to;

                    $current = $analytics->getTraffic( $from, $to, $split );

                    $from = $prevMonth->copy()->subMonth();
                    $from   = $from->toDateString();

                    $to   = $prevMonth->copy()->subMonth()->endOfMonth();
                    $to = $to->toDateString();

                    $prevMonth = $prevMonth->copy()->subMonth()->format('F');

                    $previous = $analytics->getTraffic( $from, $to, $split,$days);

                    $label1 = $monthName;
                    $label2 = $prevMonth;


                }


                if($request->has('to') && $request->has('from')){
                    $split = 'ga:dateHour';
                    $custom = true;
                    $monthName = "Custom";

                    $startDate = $request->input('from');
                    $from = $startDate;

                    $endDate   = $request->input('to');
                    $to = $endDate;

                    if(strtotime($from) > strtotime($to)){
                        return view('dashboard')
                            ->withErrors(['danger'=>'Please select valid date range!']);
                    }

                    $from  = Carbon::createFromFormat('Y-m-d', $startDate);
                    $to    = $from->copy()->subDays( 1 )->toDateString();
                    $from = $startDate;

                    //$label1 = $to.' | '.$from;
                    $label1 = $startDate;
                    $current = $analytics->getTraffic( $to, $from, $split,$days);


                    $to    = Carbon::createFromFormat('Y-m-d', $endDate);
                    $from   = $to->subDays( 1 )->toDateString();
                    $to = $endDate;

                    $label2 = $endDate;
                    //$label2 = $from.' | '.$to;
                    $previous = $analytics->getTraffic( $from, $to, $split,$days);

                }
                $created_at = Carbon::today();
                $traffic['dataLabels'] = $current['labels'];
                $traffic['currentLabel'] = ($label1!="")?$label1:strtoupper('Today');
                $traffic['previousLabel'] = ($label2!="")?$label2:strtoupper($range);
                $traffic['current'] = $current['values'];
                $traffic['previous'] = $previous['values'];
                $traffic['prevLabels'] = $previous['labels'];
                if($split == 'ga:dateHour'){
                    $last_week_current_day = $analytics->getTraffic( $last_week_from, $last_week_to, $split,$days);
                    $traffic['lastweek']      = $last_week_current_day['values'];
                    $traffic['lastWeekLabel'] = $label3;
                }
                $traffic['customPreviousLabel'] = strtoupper($monthName);
                $traffic['labels'] = $current['labels'];
                $traffic['datasets'][] = $current['values'];
                $traffic['created_at'] = '0 minute ago';
                $traffic['status'] = 'OK';

                if(in_array($filter,$months)) {
                    $response = Analytic::updateOrCreate(
                        ['client_id' => $client->id, 'type' => $type, 'filter' => $filter],
                        ['data' => json_encode($traffic), 'created_at' => $created_at,'year'=>date('Y')]
                    );
                }else{
                    $response = Analytic::updateOrCreate(
                        ['client_id' => $client->id, 'type' => $type,'filter'=>$filter],
                        ['data' => json_encode( $traffic ),'created_at'=>$created_at]
                    );
                }


               return response()->json( $traffic );

            }
            catch ( ClientNotFoundException $e ) {
                $traffic['status'] = 'false';
                return response()->json( $traffic );
            }
            catch ( \Google_Service_Exception $e ) {
                $traffic['status'] = 'false';
                return response()->json( $traffic );
            }
            catch ( ClientAuthenticationException $e ) {
                $traffic['status'] = 'false';
                return response()->json( $traffic );
            }
        }

    }
    
    public function getStatsData(Request $request){
        $company = $request->user()->activeCompany();
        $custom    = false;
        $startDate = '';
        $endDate   = '';
        $type = 'stats';
        $filter = 'yesterday';
        $month = $request->input('month');
        $range = $request->input('range');
        $months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

        $stats = [
            'visits' => '',
            'avgMinutes' => '',
            'pageViews'      => '',
            'bounceRate'     => '',
            'pagesPerVisit' => '',
            'newVisits' => '',
        ];

        $client = $company->getClient('analytics');
        if($client != null && isset($client->id)){

            $filter = $this->setFilter($range,$month);
            if(in_array($filter,$months)){
                $data = Analytic::getMonthRecord($client->id,$type,$filter);
            }else{
                $data = Analytic::getRecord($client->id,$type,$filter);
            }

            if($data != null){
                $created_at = $data['updated_at']->diffForHumans();
                $data = json_decode($data['data'],true);
                $data['created_at'] = $created_at;
                return response()->json( $data );
            }

            try {

                $analytics = new GoogleAnalytics( $client );

                if ( $request->has('range') ) {
                    $range = $request->input('range') . 'daysAgo';
                    $numberOfDays = (int) $request->input('range');
                }
                elseif ( $request->has('range') && $request->input('range') == "month_to_date") {
                    $range = $request->input('range') . 'daysAgo';
                    $numberOfDays = (int) $request->input('range');
                    $compare = true;
                }

                if ( $request->has('range') && $request->input('range') == null && $request->input('month') == null){
                    $range = 'yesterday';
                    $numberOfDays = 1;
                }


                $split = ( 'yesterday' == $range )
                    ? 'ga:dateHour'
                    : 'ga:date';

                if($request->has('range') && $request->input('range') == "7"){
                    $label1 = 'Current 7 days';
                    $label2 = 'Previous 7 days ';
                    $days = true;
                    $filter= '7';
                }

                if($request->has('range') && $request->input('range') == "30"){
                    $label1 = 'Current 30 days';
                    $label2 = 'Previous 30 days ';
                    $filter= '30';
                }


                if($request->has('range') && $request->input('range') == "last_month") {
                    $filter= 'last_month';
                    $from   = new Carbon('first day of last month');
                    $forPrevmonthStart = $from;
                    $from   = $from->toDateString();

                    $to = new Carbon('last day of last month');
                    $to = $to->toDateString();

                    $custom = true;
                    $startDate = $from;
                    $endDate   = $to;

                }


                if($request->has('month') && $request->input('month') != null && $request->input('range') == null){
                    $split = 'ga:date';
                    $filter= $request->input('month');
                    $custom = true;
                    $monthName = $request->input('month');

                    $from = new Carbon('first day of '.$monthName);
                    $prevMonth = $from;
                    $from = $from->toDateString();

                    $to = new Carbon('last day of '.$monthName);
                    $to = $to->toDateString();

                    $startDate = $from;
                    $endDate   = $to;

                }


                if($request->has('to') && $request->has('from')){
                    $split = 'ga:dateHour';
                    $custom = true;
                    $monthName = "Custom";

                    $startDate = $request->input('from');
                    $from = $startDate;

                    $endDate   = $request->input('to');
                    $to = $endDate;

                    if(strtotime($from) > strtotime($to)){
                        return view('dashboard')
                            ->withErrors(['danger'=>'Please select valid date range!']);
                    }

                    $from  = Carbon::createFromFormat('Y-m-d', $startDate);
                    $to    = $from->copy()->subDays( 1 )->toDateString();
                    $from = $startDate;



                    $to    = Carbon::createFromFormat('Y-m-d', $endDate);
                    $from   = $to->subDays( 1 )->toDateString();
                    $to = $endDate;


                }
                $created_at = Carbon::today();
                $stats['status'] = 'OK';
                $stats['visits'] = $analytics->getVisits( $range,$custom,$startDate,$endDate);
                $stats['avgMinutes'] = $analytics->getAvgUserSession( $range,$custom,$startDate,$endDate);
                $stats['pageViews'] = $analytics->getPageViews( $range,$custom,$startDate,$endDate);
                $stats['bounceRate'] = $analytics->getBounceRate( $range,$custom,$startDate,$endDate);
                $stats['pagesPerVisit'] = $analytics->getViewsPerSession( $range,$custom,$startDate,$endDate);
                $stats['newVisits'] = $analytics->getNewUsers( $range,$custom,$startDate,$endDate);
                $stats['created_at'] = '0 minute ago';

                if(in_array($filter,$months)) {
                    $response = Analytic::updateOrCreate(
                        ['client_id' => $client->id, 'type' => $type, 'filter' => $filter],
                        ['data' => json_encode($stats), 'created_at' => $created_at,'year'=>date('Y')]
                    );
                }else{
                    $response = Analytic::updateOrCreate(
                        ['client_id' => $client->id, 'type' => $type,'filter'=>$filter],
                        ['data' => json_encode( $stats ),'created_at'=>$created_at]
                    );
                }

                return response()->json( $stats );

            }
            catch ( ClientNotFoundException $e ) {
                $stats['status'] = 'false';
                return response()->json( $stats );
            }
            catch ( \Google_Service_Exception $e ) {
                $stats['status'] = 'false';
                return response()->json( $stats );
            }
            catch ( ClientAuthenticationException $e ) {
                $stats['status'] = 'false';
                return response()->json( $stats );
            }
        }
    }

    public function getSourceData(Request $request){
        $company = $request->user()->activeCompany();
        $custom    = false;
        $startDate = '';
        $endDate   = '';
        $month = $request->input('month');
        $range = $request->input('range');

        $type = 'source';
        $filter = 'yesterday';

        $months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

        $sources = [
            'mediums'       => [],
            'countries'     => [],
            'devices'       => [],
            'pages'         => []
        ];

        $client = $company->getClient('analytics');
        if($client != null && isset($client->id)){

            $filter = $this->setFilter($range,$month);
            if(in_array($filter,$months)){
                $data = Analytic::getMonthRecord($client->id,$type,$filter);
            }else{
                $data = Analytic::getRecord($client->id,$type,$filter);
            }

            if($data != null){
                $created_at = $data['updated_at']->diffForHumans();
                $data = json_decode($data['data'],true);
                $data['created_at'] = $created_at;
                return response()->json( $data );
            }

            try {

                $analytics = new GoogleAnalytics( $client );

                if ( $request->has('range') ) {
                    $range = $request->input('range') . 'daysAgo';
                    $numberOfDays = (int) $request->input('range');
                }
                elseif ( $request->has('range') && $request->input('range') == "month_to_date") {
                    $range = $request->input('range') . 'daysAgo';
                    $numberOfDays = (int) $request->input('range');
                    $compare = true;
                }

                if ( $request->has('range') && $request->input('range') == null && $request->input('month') == null){
                    $range = 'yesterday';
                    $numberOfDays = 1;
                }


                $split = ( 'yesterday' == $range )
                    ? 'ga:dateHour'
                    : 'ga:date';

                if($request->has('range') && $request->input('range') == "7"){
                    $label1 = 'Current 7 days';
                    $label2 = 'Previous 7 days ';
                    $days = true;
                    $filter = $request->input('range');
                }

                if($request->has('range') && $request->input('range') == "30"){
                    $label1 = 'Current 30 days';
                    $label2 = 'Previous 30 days ';
                    $filter = $request->input('range');
                }


                if($request->has('range') && $request->input('range') == "last_month") {
                    $filter = $request->input('range');
                    $from   = new Carbon('first day of last month');
                    $forPrevmonthStart = $from;
                    $from   = $from->toDateString();

                    $to = new Carbon('last day of last month');
                    $to = $to->toDateString();

                    $custom = true;
                    $startDate = $from;
                    $endDate   = $to;

                }


                if($request->has('month') && $request->input('month') != null && $request->input('range') == null){
                    $split = 'ga:date';
                    $filter = $request->input('month');
                    $custom = true;
                    $monthName = $request->input('month');

                    $from = new Carbon('first day of '.$monthName);
                    $prevMonth = $from;
                    $from = $from->toDateString();

                    $to = new Carbon('last day of '.$monthName);
                    $to = $to->toDateString();

                    $startDate = $from;
                    $endDate   = $to;

                }


                if($request->has('to') && $request->has('from')){
                    $split = 'ga:dateHour';
                    $custom = true;
                    $monthName = "Custom";

                    $startDate = $request->input('from');
                    $from = $startDate;

                    $endDate   = $request->input('to');
                    $to = $endDate;

                    if(strtotime($from) > strtotime($to)){
                        return view('dashboard')
                            ->withErrors(['danger'=>'Please select valid date range!']);
                    }

                    $from  = Carbon::createFromFormat('Y-m-d', $startDate);
                    $to    = $from->copy()->subDays( 1 )->toDateString();
                    $from = $startDate;



                    $to    = Carbon::createFromFormat('Y-m-d', $endDate);
                    $from   = $to->subDays( 1 )->toDateString();
                    $to = $endDate;


                }
                $created_at =  Carbon::today();
                //date("Y-m-d | h:i:sa")
                $sources = [
                    'status'        => 'OK',
                    'mediums'       => $analytics->getSourceMediums( $range,$custom,$startDate,$endDate),
                    'countries'     => $analytics->getSourceCountries( $range,$custom,$startDate,$endDate),
                    'devices'       => $analytics->getSourceDevices( $range,$custom,$startDate,$endDate),
                    'pages'         => $analytics->getSourcePagePaths( $range,$custom,$startDate,$endDate),
                    'created_at'    => '0 minute ago'
                ];
                if(in_array($filter,$months)) {
                    $response = Analytic::updateOrCreate(
                        ['client_id' => $client->id, 'type' => $type, 'filter' => $filter],
                        ['data' => json_encode($sources), 'created_at' => $created_at,'year'=>date('Y')]
                    );
                }else{
                    $response = Analytic::updateOrCreate(
                        ['client_id' => $client->id, 'type' => $type,'filter'=>$filter],
                        ['data' => json_encode( $sources ),'created_at'=>$created_at]
                    );
                }

                return response()->json( $sources );

            }
            catch ( ClientNotFoundException $e ) {
                $sources['status'] = 'false';
                return response()->json( $sources );
            }
            catch ( \Google_Service_Exception $e ) {
                $sources['status'] = 'false';
                return response()->json( $sources );
            }
            catch ( ClientAuthenticationException $e ) {
                $sources['status'] = 'false';
                return response()->json( $sources );
            }
        }
    }

    public function setFilter($range,$month){
        switch (true) {
            case ($range == '' && $month == ''):
                return 'yesterday';
            break;
            case ($range !='' && $month == ''):
                return $range;
            break;
            case ($range =='' && $month != ''):
                return $month;
            break;
        }
    }

    public function deleteSaveAnalytics(Request $request){
        $response = [
            'status' => 'false',
        ];
        $company = $request->user()->activeCompany();

        $client = $company->getClient('analytics');

        if($client != null && isset($client->id)){
            $data = Analytic::where(['client_id'=>$client->id,'year' => 0])->get();
            if($data != null){
                Analytic::where(['client_id'=>$client->id,'year' => 0])->delete();
                $response = [
                    'status' => 'true',
                ];

                return response()->json( $response );
            }
        }

        return response()->json( $response );
    }
}
