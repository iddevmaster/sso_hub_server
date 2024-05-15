<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'brn',
        'agn',
        'role',
        'icon',
        'staff',
        'gender',
        'address',
        'province',
        'dob',
        'phone',
        'course',
        'life_time',
    ];

    public function getCourseAttribute($value)
    {
        return json_decode($value, true);
    }
    public function getBrn() {
        return $this->belongsTo(Branch::class, 'brn');
    }
    public function getAgn() {
        return $this->belongsTo(Agency::class, 'agn');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getAuthPasswordAttribute()
    {
        return $this->password;
    }
}
