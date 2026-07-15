<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostReport extends Model
{
    protected $fillable = [
        'venue_id',
        'grade_level',
        'course_type',
        'monthly_fee',
        'annual_other_fees',
        'comment',
        'nickname',
        'ip_hash',
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
