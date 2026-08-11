<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    public const GRADE_OPTIONS = [
        '小学生',
        '中学生（中1〜中3）',
        '高校生（高1〜高3）',
        '大学受験・浪人生',
    ];

    public const LESSON_STYLE_OPTIONS = [
        '個別指導',
        '集団授業',
        'オンライン',
        '自立学習',
    ];

    protected $fillable = [
        'name',
        'description',
        'area',
        'address',
        'phone',
        'lat',
        'lng',
        'likes_count',
        'target_grades',
        'lesson_style',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'target_grades' => 'array',
        ];
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function documentRequests()
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function costReports()
    {
        return $this->hasMany(CostReport::class);
    }
}
