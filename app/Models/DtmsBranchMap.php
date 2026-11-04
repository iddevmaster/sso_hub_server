<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DtmsBranchMap extends Model
{
    protected $table = 'dtms_branch_map';

    protected $fillable = ['dtms_id', 'dtms_code', 'dtms_name', 'brn_id', 'is_active'];
}
