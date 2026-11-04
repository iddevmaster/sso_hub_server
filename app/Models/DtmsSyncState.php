<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DtmsSyncState extends Model
{
    protected $table = 'dtms_sync_state';

    protected $fillable = ['cursor', 'last_success_at'];

    protected $casts = ['last_success_at' => 'datetime'];
}
