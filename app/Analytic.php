<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Analytic extends Model
{
    protected $fillable = [
        'client_id', 'type', 'data','created_at','filter','year'
    ];


    public function client()
    {
        return $this->belongsTo('App\Client');
    }

    public static function getRecord($client_id,$type,$filter){
        return static::where(['client_id' => $client_id, 'type' => $type,'filter'=>$filter,'created_at'=>Carbon::today()])->first();
    }

    public static function getMonthRecord($client_id,$type,$filter){
        return static::where(['client_id' => $client_id, 'type' => $type,'filter'=>$filter,'year'=>date('Y')])->first();
    }
}
