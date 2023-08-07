<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

use App\Events\FileDeleted;

class PartnerDoc extends Model
{
    /**
     * Allow for fields to be mass-fillable
     */
    protected $fillable = [
        'partner_id', 'name', 'path'
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'deleted' => FileDeleted::class,
    ];

    /**
     * Get file url
     */
    public function getFileUrl()
    {
        return Storage::url( $this->path );
    }
}
