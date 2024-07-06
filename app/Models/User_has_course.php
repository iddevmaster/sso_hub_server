<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User_has_course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id'
    ];

    public function getUser() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getCourse() {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
