<?php

namespace App\Console\Commands;
use App\Company;
use App\Clients\GoogleAnalytics;
use App\Analytic;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

use App\Exceptions\ClientSetupException;
use App\Exceptions\ClientNotFoundException;
use App\Exceptions\ClientAuthenticationException;

use Illuminate\Console\Command;

class GetAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytic:get {company?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get yesterday analytics for all analytic clients.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $start_of_year =  Carbon::parse('first day of January');
        $current_day_of_the_month = Carbon::now();
        $period = CarbonPeriod::create($start_of_year, '1 month', $current_day_of_the_month);
        foreach ($period as $dt) {
            $months[] =  $dt->englishMonth;
        }
        $range = 'yesterday';
        $numberOfDays = 1;
        $label1 = '';
        $label2 = '';
        $saveData =  array();
        $monthName = '';
        $compare = false;
        $custom    = false;
        $startDate = '';
        $endDate   = '';
        $days = false;
        $filters = array('yesterday',7,30,'last_month','monthly');
        $types = array('traffic','stats','source');
        $created_at = Carbon::today();
        $year       = false;

        $split = ( 'yesterday' == $range )
            ? 'ga:dateHour'
            : 'ga:date';

        $companyId = $this->argument('company');

        $companies = Company::all();

        if ( 'all' == $companyId || '' == $companyId ) {
            $companies = Company::all();

            foreach ( $companies as $company ) {
                $this->call( 'analytic:get', ['company' => $company->id] );
            }

            return;
        }

        // Get the company
        $company = Company::findOrFail( $companyId );

        $this->info("Found company {$company->name}");

        // Loop through all the clients and retrieve all data from each
        foreach ( $company->clients as $client ) {

            switch ( $client->type ) {
                case 'analytics' :
                    try{
                        $analytics = new GoogleAnalytics( $client );
                        foreach($types as $type){

                            foreach($filters as $filter){
                                $saveData =  array();
                                $custom = false;
                                if($filter == 'yesterday'){
                                    $range = 'yesterday';
                                    $numberOfDays = 1;
                                    $split = 'ga:dateHour';
                                }

                                if($filter == 7 || $filter == 30){
                                    $range = $filter. 'daysAgo';
                                    $numberOfDays = (int) $filter;
                                    $days = false;

                                    $label1 = 'Current '.$filter.' days';
                                    $label2 = 'Previous '.$filter.' days ';

                                    if($filter == 7){
                                        $days = true;
                                    }

                                    $split = 'ga:date';
                                }

                                if($filter == "last_month") {
                                    $from   = new Carbon('first day of last month');
                                    $from   = $from->toDateString();

                                    $to = new Carbon('last day of last month');
                                    $to = $to->toDateString();

                                    $custom = true;
                                    $startDate = $from;
                                    $endDate   = $to;

                                    $split = 'ga:date';
                                }


                                if($type == 'traffic'){
                                    if($filter == 'yesterday' || $filter == 7 || $filter == 30){
                                        $to   = Carbon::now()->toDateString();
                                        $from = Carbon::now()->subDays( $numberOfDays )->toDateString();

                                        $current = $analytics->getTraffic( $from, $to, $split,$days);

                                        $from = Carbon::now()->subDays( $numberOfDays * 2 )->toDateString();
                                        $to   = Carbon::now()->subDays( $numberOfDays )->toDateString();

                                        $previous = $analytics->getTraffic( $from, $to, $split,$days);
                                    }

                                    if($filter == "last_month") {
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



                                    if($filter == "monthly") {
                                        foreach($months as $month){
                                            $count = $this->checkMonthRecord($client->id,$month,$type);
                                            if($count > 0 || $month == date('F')){
                                                //skip do nothing
                                            }else{
                                                $filter = $month;
                                                $split = 'ga:date';

                                                $custom = true;
                                                $monthName = $month;

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

                                                $saveData['dataLabels'] = $current['labels'];
                                                $saveData['currentLabel'] = ($label1!="")?$label1:strtoupper('Today');
                                                $saveData['previousLabel'] = ($label2!="")?$label2:strtoupper($range);
                                                $saveData['current'] = $current['values'];
                                                $saveData['previous'] = $previous['values'];
                                                $saveData['prevLabels'] = $previous['labels'];
                                                $saveData['customPreviousLabel'] = strtoupper($monthName);
                                                $saveData['labels'] = $current['labels'];
                                                $saveData['datasets'][] = $current['values'];
                                                $saveData['status'] = 'OK';

                                                $response = Analytic::updateOrCreate(
                                                    ['client_id' => $client->id,'type' => $type,'filter'=>$filter],
                                                    ['data' => json_encode( $saveData ),'created_at'=>$created_at,'year'=>date('Y')]
                                                );
                                            }
                                        }
                                    }else{
                                        $saveData['dataLabels'] = $current['labels'];
                                        $saveData['currentLabel'] = ($label1!="")?$label1:strtoupper('Today');
                                        $saveData['previousLabel'] = ($label2!="")?$label2:strtoupper($range);
                                        $saveData['current'] = $current['values'];
                                        $saveData['previous'] = $previous['values'];
                                        $saveData['prevLabels'] = $previous['labels'];
                                        if($split == 'ga:dateHour'){
                                            //For Last Week Same Day
                                            $last_week = Carbon::now()->subDays(7);
                                            $last_week_from   = $last_week->toDateString();
                                            $last_week_to = $last_week->addDay(1)->toDateString();
                                            $label3 = 'Last Week '.$last_week->subDay(1)->format('l');
                                            //Get Last Week same day data
                                            $last_week_current_day = $analytics->getTraffic( $last_week_from, $last_week_to, $split,$days);
                                            $saveData['lastweek']      = $last_week_current_day['values'];
                                            $saveData['lastWeekLabel'] = $label3;
                                        }
                                        $saveData['customPreviousLabel'] = strtoupper($monthName);
                                        $saveData['labels'] = $current['labels'];
                                        $saveData['datasets'][] = $current['values'];
                                        $saveData['status'] = 'OK';
                                    }

                                }

                                if($type == 'stats'){

                                    if ($filter == "monthly") {
                                        foreach ($months as $month) {
                                            $count = $this->checkMonthRecord($client->id,$month,$type);
                                            if ($count > 0 || $month == date('F')) {
                                                //skip do nothing
                                            } else {
                                                $year = true;
                                                $split = 'ga:date';
                                                $filter = $month;
                                                $custom = true;
                                                $monthName = $month;

                                                $from = new Carbon('first day of ' . $monthName);
                                                $prevMonth = $from;
                                                $from = $from->toDateString();

                                                $to = new Carbon('last day of ' . $monthName);
                                                $to = $to->toDateString();

                                                $startDate = $from;
                                                $endDate = $to;

                                                $saveData['status'] = 'OK';
                                                $saveData['visits'] = $analytics->getVisits( $range,$custom,$startDate,$endDate);
                                                $saveData['avgMinutes'] = $analytics->getAvgUserSession( $range,$custom,$startDate,$endDate);
                                                $saveData['pageViews'] = $analytics->getPageViews( $range,$custom,$startDate,$endDate);
                                                $saveData['bounceRate'] = $analytics->getBounceRate( $range,$custom,$startDate,$endDate);
                                                $saveData['pagesPerVisit'] = $analytics->getViewsPerSession( $range,$custom,$startDate,$endDate);
                                                $saveData['newVisits'] = $analytics->getNewUsers( $range,$custom,$startDate,$endDate);

                                                $response = Analytic::updateOrCreate(
                                                    ['client_id' => $client->id,'type' => $type,'filter'=>$filter],
                                                    ['data' => json_encode( $saveData ),'created_at'=>$created_at,'year'=>date('Y')]
                                                );
                                            }
                                        }
                                    }else{
                                        $saveData['status'] = 'OK';
                                        $saveData['visits'] = $analytics->getVisits( $range,$custom,$startDate,$endDate);
                                        $saveData['avgMinutes'] = $analytics->getAvgUserSession( $range,$custom,$startDate,$endDate);
                                        $saveData['pageViews'] = $analytics->getPageViews( $range,$custom,$startDate,$endDate);
                                        $saveData['bounceRate'] = $analytics->getBounceRate( $range,$custom,$startDate,$endDate);
                                        $saveData['pagesPerVisit'] = $analytics->getViewsPerSession( $range,$custom,$startDate,$endDate);
                                        $saveData['newVisits'] = $analytics->getNewUsers( $range,$custom,$startDate,$endDate);
                                    }
                                }

                                if($type == 'source'){

                                    if ($filter == "monthly") {
                                        foreach ($months as $month) {
                                            $count = $this->checkMonthRecord($client->id,$month,$type);
                                            if ($count > 0 || $month == date('F')) {
                                                //skip do nothing
                                            } else {
                                                $year = true;
                                                $split = 'ga:date';
                                                $filter = $month;
                                                $custom = true;
                                                $monthName = $month;

                                                $from = new Carbon('first day of ' . $monthName);
                                                $prevMonth = $from;
                                                $from = $from->toDateString();

                                                $to = new Carbon('last day of ' . $monthName);
                                                $to = $to->toDateString();

                                                $startDate = $from;
                                                $endDate = $to;

                                                $saveData = [
                                                    'status'        => 'OK',
                                                    'mediums'       => $analytics->getSourceMediums( $range,$custom,$startDate,$endDate),
                                                    'countries'     => $analytics->getSourceCountries( $range,$custom,$startDate,$endDate),
                                                    'devices'       => $analytics->getSourceDevices( $range,$custom,$startDate,$endDate),
                                                    'pages'         => $analytics->getSourcePagePaths( $range,$custom,$startDate,$endDate),
                                                ];

                                                $response = Analytic::updateOrCreate(
                                                    ['client_id' => $client->id,'type' => $type,'filter'=>$filter],
                                                    ['data' => json_encode( $saveData ),'created_at'=>$created_at,'year'=>date('Y')]
                                                );
                                            }
                                        }
                                    }else{
                                        $saveData = [
                                            'status'        => 'OK',
                                            'mediums'       => $analytics->getSourceMediums( $range,$custom,$startDate,$endDate),
                                            'countries'     => $analytics->getSourceCountries( $range,$custom,$startDate,$endDate),
                                            'devices'       => $analytics->getSourceDevices( $range,$custom,$startDate,$endDate),
                                            'pages'         => $analytics->getSourcePagePaths( $range,$custom,$startDate,$endDate),
                                        ];
                                    }

                                }
                                if(!$year){
                                    $response = Analytic::updateOrCreate(
                                        ['client_id' => $client->id, 'type' => $type,'filter'=>$filter],
                                        ['data' => json_encode( $saveData ),'created_at'=>$created_at]
                                    );
                                }

                            }
                        }

                        $this->info("Storing analytics for {$client->id}");
                    }
                    catch ( ClientNotFoundException $e ) {
                        $this->info("Client {$client->id} Not Found Exception");
                    }
                    catch ( \Google_Service_Exception $e ) {
                        $this->info("Client {$client->id} Cannot connect to Google Analytics");
                    }
                    catch ( ClientAuthenticationException $e ) {
                        $this->info("Client {$client->id} Client Authentication Exception");
                    }
                    catch ( ClientSetupException $e ) {
                        $this->info("Client {$client->id} Client Setup Exception");
                    }


                    break;

            }

        }

    }

    public function checkMonthRecord($client_id,$monthName,$type){
        if($monthName != "" && $client_id > 0){
            $count = Analytic::where(['client_id'=>$client_id,'filter'=>$monthName,'type'=>$type,'year'=>date('Y')])->count();
            return $count;
        }
    }

}
