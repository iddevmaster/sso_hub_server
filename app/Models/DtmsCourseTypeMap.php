<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DtmsCourseTypeMap extends Model
{
    protected $table = 'dtms_course_type_map';

    protected $fillable = ['vehicle_type_code', 'is_foreign', 'course_type'];
}
