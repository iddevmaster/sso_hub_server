<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Customer extends Model implements Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable, HasRoles;

    protected $primaryKey = 'citizen_id';
    protected $keyType = 'str';

    protected $casts = [
        'citizen_id' => 'string', // Add this line
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'citizen_id',
        'password',
        'full_name',
        'gender',
        'address',
        'province',
        'dob',
        'phone',
        'course',
        'life_time',
        'staff',
        'brn'
    ];

    public function getBrn() {
        return $this->belongsTo(Branch::class, 'brn');
    }

    public function getBrnAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getAuthIdentifierName()
    {
        return 'citizen_id';
    }

    public function getAuthIdentifier()
    {
        return $this->citizen_id;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return null; // Or implement logic to retrieve the remember token if used
    }

    public function setRememberToken($value)
    {
        // Implement logic to store the remember token if needed
    }

    public function canRemember()
    {
        return true; // Or implement logic to determine if remember me is allowed
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    public function getAuthPasswordAttribute()
    {
        return $this->password;
    }

}
