<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HouseHistory;

class Resident extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function histories()
    {
        return $this->hasMany(HouseHistory::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function houseHistories()
    {
        return $this->hasMany(HouseHistory::class);
    }

    public function activeHouse()
    {
        return $this->hasOne(HouseHistory::class)->whereNull('end_date');
    }
}


