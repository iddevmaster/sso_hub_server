<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DtmsSyncFailure extends Model
{
    protected $table = 'dtms_sync_failures';

    protected $fillable = ['event_type', 'entity_id', 'error', 'payload', 'resolved_at'];

    protected $casts = ['payload' => 'array', 'resolved_at' => 'datetime'];
}
