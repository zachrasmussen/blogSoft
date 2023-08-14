<?php

namespace App\Console\Commands;

use App\Company;
use App\Clients\Instagram;
use App\Clients\Facebook;
use App\Clients\Twitter;
use App\Clients\Pinterest;
use App\Clients\YouTube;
use App\Analytic;

use Illuminate\Console\Command;

class GetSocialCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'social:counts {company?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get social counts for all clients.';

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
        Analytic::where(['filter'=>'monthly'])->delete();
        $companyId = $this->argument('company');

        // if we're trying to all companies, let's recursively call all companies;
        if ( 'all' == $companyId || '' == $companyId ) {
            $companies = Company::all();

            foreach ( $companies as $company ) {
                $this->call( 'social:counts', ['company' => $company->id] );
            }

            return;
        }

        // Get the company
        $company = Company::findOrFail( $companyId );

        $this->info("Found company {$company->name}");

        // Loop through all the clients and retreive all data from each
        foreach ( $company->clients as $client ) {
            $getter = false;

            switch ( $client->type ) {
                case 'facebook_page' :
                    $getter = new Facebook( $client );
                    break;

                case 'instagram' :
                    $getter = new Instagram( $client );
                    break;

                case 'twitter' :
                    $getter = new Twitter( $client );
                    break;

                case 'pinterest' :
                    $getter = new Pinterest( $client );
                    break;

                case 'youtube' :
                    $getter = new YouTube( $client );
                    break;
            }

            if ( false !== $getter ) {
                $count = $getter->storeCount();
                $this->info("Storing count ($count) for {$client->type}");
            }
        }
    }
}
