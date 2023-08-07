<?php

namespace App\Clients;

use Carbon\Carbon;

use Google_Client;
use Google_Service_Analytics;

use App\ClientData;
use App\Exceptions\ClientAuthenticationException;
use App\Exceptions\ClientSetupException;

class GoogleAnalytics extends OAuthClient
{
    private $service;
    private $profile;

    public function __construct( $client )
    {
        parent::__construct( $client );

        $this->client = $client;

        // set up oauth client
        $this->oauth = new Google_Client;
        $this->oauth->setClientId( config( 'services.google.client_id' ) );
        $this->oauth->setClientSecret( config( 'services.google.client_secret' ) );
        $this->oauth->setRedirectUri( config( 'services.google.redirect' ) );
        $this->oauth->setAccessType( 'offline' );

        // set auth token
        $token = $this->client->getData( 'token' );
        $token = json_decode( $token, true );
        $this->oauth->setAccessToken( $token );

        // attempt to refresh token if it's expired
        if ( $this->oauth->isAccessTokenExpired() ) {
            try {
                $token = $this->oauth->fetchAccessTokenWithRefreshToken( $this->client->refresh_token );
            }
            catch ( \LogicException $e ) {
                // If this happens, there's either something wrong with the refresh token or it's missing.
                // We will need to revoke the token we have and have the user get a new refresh token from Google.
                $this->oauth->revokeToken();
                throw new ClientAuthenticationException( 'Error connecting to Google, please reauthorize your account.' );
            }

            if ( isset( $token['error'] ) ) {
                throw new ClientAuthenticationException( $token['error_description'] );
            }

            $this->client->access_token = $token['access_token'];
            $this->client->expiry       = $token['created'] + $token['expires_in'];
            $this->client->save();

            $data = ClientData::firstOrNew(
                ['client_id' => $this->client->id, 'name' => 'token'],
                ['value' => json_encode( $token )]
            );

            $data->save();
        }

        // Then set up our service
        $this->service = new Google_Service_Analytics( $this->oauth );
    }

    /**
     * Get accounts from this user
     */
    public function getAccounts()
    {
        $accounts = $this->service->management_accounts->listManagementAccounts();
        return $accounts->getItems();
    }

    /**
     * Get properties from this account
     */
    public function getProperties( $account )
    {
        $properties = $this->service->management_webproperties->listManagementWebproperties( $account );

        return $properties->getItems();
    }

    /**
     * Get profiles (views) from this account and web property
     */
    public function getProfiles( $account, $property )
    {
        $profiles = $this->service->management_profiles->listManagementProfiles( $account, $property );

        return $profiles->getItems();
    }

    /**
     * Get available options for user to track data
     */
    public function getOptions()
    {
        $data = [];

        $accounts = $this->getAccounts();

        if ( !empty( $accounts ) ) {
            foreach ( $accounts as $account ) {
                $acctData = [
                    'id' => $account->getId(),
                    'name' => $account->getName(),
                    'profiles' => []
                ];

                $profiles = $this->getProfiles( $account->getId(), '~all' );

                if ( !empty( $profiles ) ) {
                    foreach ( $profiles as $profile ) {
                        $acctData['profiles'][] = [
                            'id' => $profile->getId(),
                            'name' => $profile->getName(),
                            'type' => $profile->getType(),
                        ];
                    }
                } else {
                    // skip this account if no profiles are found
                    continue;
                }

                $data[] = $acctData;
            }
        }

        return $data;
    }

    /**
     * Get Profile ID
     */
    public function getProfileId()
    {
        if ( null == $this->profile ) {
            $profile = $this->client->getData('profile');
            // if there's no profile yet, throw an exception
            if ( null == $profile ) {
                throw new ClientSetupException;
            }

            $this->profile = $profile->value;
        }

        return $this->profile;
    }

    /**
     * Get Data
     */
    private function getDataPoint( $type, $range, $params = [] )
    {
        $data = collect([]);

        // $analytics = new \Google_Service_AnalyticsReporting( $this->oauth );

        // $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        // $dateRange->setStartDate( $range );
        // $dateRange->setEndDate( "today" );

        // $metrics = new \Google_Service_AnalyticsReporting_Metric();
        // $metrics->setExpression( $type );

        // $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        // $request->setViewId( $this->getProfileId() );
        // $request->setDateRanges( $dateRange );
        // $request->setMetrics([ $metrics ]);

        // $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        // $body->setReportRequests([ $request ]);

        // $rows = $analytics->reports->batchGet( $body );

        // for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {}

        $results = $this->service->data_ga->get(
            'ga:' . $this->getProfileId(),
            $range,
            'today',
            $type,
            $params
        );

        $rows = $results->getRows();

        if ( $rows != null && count( $rows ) > 0 ) {
            // Get the entry for the first entry in the first row.
            for( $i = 0; $i < sizeof($rows); $i++ ) {
                $data[] = $rows[$i][0];
            }
        }

        return ceil( $data->avg() );
    }

    /**
     * Get Data with Start Date and End Date
     */
    private function getDataPointCustom( $type, $startDate,$endDate, $params = [] )
    {
        $data = collect([]);

        // $analytics = new \Google_Service_AnalyticsReporting( $this->oauth );

        // $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        // $dateRange->setStartDate( $range );
        // $dateRange->setEndDate( "today" );

        // $metrics = new \Google_Service_AnalyticsReporting_Metric();
        // $metrics->setExpression( $type );

        // $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        // $request->setViewId( $this->getProfileId() );
        // $request->setDateRanges( $dateRange );
        // $request->setMetrics([ $metrics ]);

        // $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        // $body->setReportRequests([ $request ]);

        // $rows = $analytics->reports->batchGet( $body );

        // for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {}

        $results = $this->service->data_ga->get(
            'ga:' . $this->getProfileId(),
            $startDate,
            $endDate,
            $type,
            $params
        );

        $rows = $results->getRows();
        if ( $rows != null && count( $rows ) > 0 ) {
            // Get the entry for the first entry in the first row.
            for( $i = 0; $i < sizeof($rows); $i++ ) {
                $data[] = $rows[$i][0];
            }
        }

        return ceil( $data->avg() );
    }

    /**
     * Get traffic for a certain amount of time
     */
    public function getTraffic( $from, $to, $split, $days = false)
    {
        $hoursListing = $this->hoursRange();
        $hoursData = [];

        $data = [
            'labels' => [],
            'values' => []
        ];

        $params = [
            'dimensions' => $split,
        ];

        $results = $this->service->data_ga->get(
            'ga:' . $this->getProfileId(),
            $from,
            $to,
            'ga:sessions',
            $params
        );

        $rows = $results->getRows();

        if($rows != null){
            foreach ( $rows as $row ) {
                if ( 'ga:date' == $split ) {
                    $date = Carbon::createFromFormat( 'Ymd', $row[0] );
                    if($days){
                        $data['labels'][] = $date->format('l');
                    }else{
                        $data['labels'][] = $date->format('d');
                    }

                }
                else {
                    $date = Carbon::createFromFormat( 'YmdH', $row[0] );

                    $data['labels'][] = $date->format('h A');
                }

                $data['values'][] = (int) $row[1];
            }

            /*if ( $rows != null && count( $rows ) > 0 ) {
                for( $i = 0; $i < sizeof($rows); $i++ ) {

                }
            }*/

            if ( $data != null && count( $data ) > 0 ) {
                for( $i = 0; $i < sizeof($hoursListing); $i++ ) {
                    if('ga:dateHour' == $split){
                        if(in_array($hoursListing[$i],$data['labels'])){
                            $index = array_search($hoursListing[$i], $data['labels']);
                            $hoursData['labels'][] = $hoursListing[$i];
                            $hoursData['values'][] = $data['values'][$index];
                        }else{
                            $hoursData['labels'][] = $hoursListing[$i];
                            $hoursData['values'][] = 0;
                        }
                    }
                }

                if($hoursData != null && count($hoursData) > 0){
                    $data = $hoursData;
                }
            }
        }

        return $data;
    }

    /**
     * Get current visits
     */
    public function getVisits( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        if($custom){
            return $this->getDataPointCustom( 'ga:sessions',$startDate,$endDate);
        }else{
            return $this->getDataPoint( 'ga:sessions', $range );
        }

    }

    /**
     * Get avg on page time
     */
    public function getAvgTimeOnPage( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        if($custom){
            return $this->getDataPointCustom( 'ga:avgTimeOnPage',$startDate,$endDate);
        }else{
            return $this->getDataPoint( 'ga:avgTimeOnPage', $range );
        }
    }

    /** Get avg time for session*/
    public function getAvgUserSession( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        if($custom){
            $totalSessions = $this->getDataPointCustom( 'ga:sessions',$startDate,$endDate);
            if($totalSessions > 0){
                $avg = $this->getDataPointCustom( 'ga:sessionDuration',$startDate,$endDate)/$totalSessions;
                $avg = ceil($avg /60);
            }else{
                $avg = $this->getDataPointCustom( 'ga:sessionDuration',$startDate,$endDate);
                $avg = ceil($avg /60);
            }

        }else{
            $totalSessions = $this->getDataPoint( 'ga:sessions',$range);
            if($totalSessions > 0){
                $avg = $this->getDataPoint( 'ga:sessionDuration', $range )/$totalSessions;
                $avg = ceil($avg /60);
            }else{
                $avg = $this->getDataPoint( 'ga:sessionDuration', $range );
                $avg = ceil($avg /60);
            }

        }

        return $avg;
    }

    /**
     * Get page views
     */
    public function getPageViews( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        if($custom){
            return $this->getDataPointCustom( 'ga:pageviews',$startDate,$endDate);
        }else{
            return $this->getDataPoint( 'ga:pageviews', $range );
        }
    }

    /**
     * Get Unique views
     */
    public function getUniqueViews( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        if($custom){
            return $this->getDataPointCustom( 'ga:uniquePageviews',$startDate,$endDate);
        }else{
            return $this->getDataPoint( 'ga:uniquePageviews', $range );
        }
    }

    /** Get New Users **/
    public function getNewUsers( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        if($custom){
            return $this->getDataPointCustom( 'ga:newUsers',$startDate,$endDate);
        }else{
            return $this->getDataPoint( 'ga:newUsers', $range );
        }
    }

    /**
     * Get Views per Session
     */
    public function getViewsPerSession( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        if($custom){
            return $this->getDataPointCustom( 'ga:pageviewsPerSession',$startDate,$endDate);
        }else{
            return $this->getDataPoint( 'ga:pageviewsPerSession', $range );
        }
    }

    /**
     * Get bounce rate
     */
    public function getBounceRate( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        if($custom){
            return $this->getDataPointCustom( 'ga:bounceRate',$startDate,$endDate);
        }else{
            return $this->getDataPoint( 'ga:bounceRate', $range );
        }
    }

    /**
     * Get source mediums
     */
    public function getSourceMediums( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        $data = [
            'labels' => [],
            'values' => [],
        ];

        $params = [
            'dimensions' => 'ga:medium'
        ];
        if($custom){
            $results = $this->service->data_ga->get(
                'ga:' . $this->getProfileId(),
                $startDate,
                $endDate,
                'ga:sessions,ga:pageviews,ga:pageviewsPerSession',
                $params
            );
        }else{
            $results = $this->service->data_ga->get(
                'ga:' . $this->getProfileId(),
                $range,
                'today',
                'ga:sessions,ga:pageviews,ga:pageviewsPerSession',
                $params
            );
        }

        $rows = $results->getRows();

        if($rows != null) {
            foreach ($results->getRows() as $row) {
                switch ($row[0]) {
                    case 'organic' :
                        $data['labels'][] = 'Organic';
                        break;

                    case 'company_profile' :
                        $data['labels'][] = 'Company Profile';
                        break;

                    case 'cpc' :
                        $data['labels'][] = 'Paid Search';
                        break;

                    case 'referral' :
                        $data['labels'][] = 'Referral';
                        break;

                    case '(none)' :
                        $data['labels'][] = 'Direct';
                        break;
                }

                $data['values'][] = (int)$row[1];
            }

            $data['data'] = $rows;
        }
        return $data;
    }

    /**
     * Get source countries
     */
    public function getSourceCountries( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        $params = [
            'dimensions' => 'ga:country',
            'sort' => '-ga:sessions,ga:country',
            'max-results' => '5'
        ];
        if($custom){
            $results = $this->service->data_ga->get(
                'ga:' . $this->getProfileId(),
                $startDate,
                $endDate,
                'ga:sessions',
                $params
            );
        }else{
            $results = $this->service->data_ga->get(
                'ga:' . $this->getProfileId(),
                $range,
                'today',
                'ga:sessions',
                $params
            );
        }


        return $results->getRows();
    }

    /**
     * Get page paths
     */
    public function getSourcePagePaths( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        $params = [
            'dimensions' => 'ga:pagePath',
            'sort' => '-ga:sessions,ga:pagePath',
            'max-results' => '5'
        ];
        if($custom){
            $results = $this->service->data_ga->get(
                'ga:' . $this->getProfileId(),
                $startDate,
                $endDate,
                'ga:sessions',
                $params
            );
        }else{
            $results = $this->service->data_ga->get(
                'ga:' . $this->getProfileId(),
                $range,
                'today',
                'ga:sessions',
                $params
            );
        }


        return $results->getRows();
    }

    /**
     * Get source devices
     */
    public function getSourceDevices( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        $data = [
            'labels' => [],
            'values' => [],
        ];

        $params = [
            'dimensions' => 'ga:deviceCategory'
        ];
        if($custom){
            $results = $this->service->data_ga->get(
                'ga:' . $this->getProfileId(),
                $startDate,
                $endDate,
                'ga:sessions,ga:pageviews,ga:pageviewsPerSession',
                $params
            );
        }else{
            $results = $this->service->data_ga->get(
                'ga:' . $this->getProfileId(),
                $range,
                'today',
                'ga:sessions,ga:pageviews,ga:pageviewsPerSession',
                $params
            );
        }

        $rows = $results->getRows();

        if($rows != null) {
            foreach ($results->getRows() as $row) {
                switch ($row[0]) {
                    case 'desktop' :
                        $data['labels'][] = 'Desktop';
                        break;

                    case 'tablet' :
                        $data['labels'][] = 'Tablet';
                        break;

                    case 'mobile' :
                        $data['labels'][] = 'Mobile';
                        break;

                    case '(none)' :
                        $data['labels'][] = 'Direct';
                        break;
                }

                $data['values'][] = (int)$row[1];
            }
            $data['data'] = $rows;
        }
        return $data;
    }

    /**
     * Get source socials
     */
    public function getSourceSocials( $range = '7daysAgo',$custom,$startDate,$endDate)
    {
        $data = [
            'labels' => [],
            'values' => [],
        ];

        $params = [
            'dimensions' => 'ga:socialNetwork'
        ];
        if($custom){
            $results = $this->service->data_ga->get(
                'ga:' . $this->getProfileId(),
                $startDate,
                $endDate,
                'ga:sessions,ga:pageviews,ga:pageviewsPerSession',
                $params
            );
        }else{
            $results = $this->service->data_ga->get(
                'ga:' . $this->getProfileId(),
                $range,
                'today',
                'ga:sessions,ga:pageviews,ga:pageviewsPerSession',
                $params
            );
        }

        $rows = $results->getRows();

        if($rows != null) {
            foreach ($results->getRows() as $row) {
                switch ($row[0]) {
                    case 'Google+' :
                        $data['labels'][] = 'Google+';
                        break;

                    case 'Bing' :
                        $data['labels'][] = 'Bing';
                        break;

                    case 'Pinterest' :
                        $data['labels'][] = 'Pinterest';
                        break;

                    case 'Facebook' :
                        $data['labels'][] = 'Facebook';
                        break;

                    case 'Twitter' :
                        $data['labels'][] = 'Twitter';
                        break;

                    case '(not set)' :
                        $data['labels'][] = 'Direct';
                        break;
                }

                $data['values'][] = (int)$row[1];
            }
        }
        return $data;
    }

    public function getSessions( $from, $to, $split )
    {
        $data = [
            'labels' => [],
            'values' => []
        ];

        $params = [
            'dimensions' => $split,
        ];

        $results = $this->service->data_ga->get(
            'ga:' . $this->getProfileId(),
            $from,
            $to,
            'ga:sessions',
            $params
        );

        $rows = $results->getRows();

        if($rows != null){
            foreach ( $rows as $row ) {
                if ( 'ga:date' == $split ) {
                    $date = Carbon::createFromFormat( 'Ymd', $row[0] );

                    $data['labels'][] = $date->format('M d');
                } else {
                    $date = Carbon::createFromFormat( 'YmdH', $row[0] );

                    $data['labels'][] = $date->format('h A');
                }

                $data['values'][] = (int) $row[1];
            }
        }


        return $data;
    }

    public function getViews( $from, $to, $split )
    {
        $data = [
            'labels' => [],
            'values' => []
        ];

        $params = [
            'dimensions' => $split,
        ];

        $results = $this->service->data_ga->get(
            'ga:' . $this->getProfileId(),
            $from,
            $to,
            'ga:pageviews',
            $params
        );

        $rows = $results->getRows();

        if($rows != null){
            foreach ( $rows as $row ) {
                if ( 'ga:date' == $split ) {
                    $date = Carbon::createFromFormat( 'Ymd', $row[0] );

                    $data['labels'][] = $date->format('M d');
                } else {
                    $date = Carbon::createFromFormat( 'YmdH', $row[0] );

                    $data['labels'][] = $date->format('h A');
                }

                $data['values'][] = (int) $row[1];
            }
        }


        return $data;
    }

    /**Hours Listing**/
    function hoursRange( $lower = 0, $upper = 86400, $step = 3600, $format = '' ) {
        $hours = array();

        if ( empty( $format ) ) {
            $format = 'h A';
        }

        foreach ( range( $lower, $upper, $step ) as $increment ) {
            $increment = gmdate( 'H:i', $increment );

            list( $hour, $minutes ) = explode( ':', $increment );

            $date = new \DateTime( $hour . ':' . $minutes );

            $hours[] = $date->format( $format );
        }

        return $hours;
    }

}