<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;

use App\Company;
use App\System;
use App\SystemComponent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Force SSL
        URL::forceScheme('http');

        // Blade
        Blade::component('partials.alert', 'alert');
        Blade::component('partials.modal', 'modal');

        // After a new website is created
        Company::created(function($company) {

            // set up predefined groups.
            $predefined = [
                "A" => "Administrative",
                "W" => "Website",
                "S" => "Social Media",
                "E" => "Email",
                "B" => "Brands",
            ];

            // Predefined values are assigned to id's: 1, 2, 3, 4, 5 which are reserved and not used for this purpose
            $index = 1;
            foreach ( $predefined as $prefix => $name ) {
                $system = System::create([
                    'company_id' => $company->id,
                    'name'       => $name,
                    'prefix'     => $prefix,
                    'order'      => $index,
                    'lock'       => true,
                ]);
                
                if ( 1 == $index ) {
                    SystemComponent::create([
                        'system_id' => $system->id,
                        'name'    => 'How to Create a System',
                        'url'     => '#',
                        'type_id' => 1,
                        'num'     => 1
                    ]);
                }
                
                $index++;
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
