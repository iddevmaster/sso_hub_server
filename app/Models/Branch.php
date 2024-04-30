<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'agn',
        'address',
        'contact',
    ];

    public function getAgn() {
        return $this->belongsTo(Agency::class, 'agn');
    }
}
