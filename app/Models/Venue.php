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

    /** 都道府県ページのURLに使うローマ字。 */
    public const AREA_SLUGS = [
        '北海道' => 'hokkaido', '青森県' => 'aomori', '岩手県' => 'iwate', '宮城県' => 'miyagi',
        '秋田県' => 'akita', '山形県' => 'yamagata', '福島県' => 'fukushima', '茨城県' => 'ibaraki',
        '栃木県' => 'tochigi', '群馬県' => 'gunma', '埼玉県' => 'saitama', '千葉県' => 'chiba',
        '東京都' => 'tokyo', '神奈川県' => 'kanagawa', '新潟県' => 'niigata', '富山県' => 'toyama',
        '石川県' => 'ishikawa', '福井県' => 'fukui', '山梨県' => 'yamanashi', '長野県' => 'nagano',
        '岐阜県' => 'gifu', '静岡県' => 'shizuoka', '愛知県' => 'aichi', '三重県' => 'mie',
        '滋賀県' => 'shiga', '京都府' => 'kyoto', '大阪府' => 'osaka', '兵庫県' => 'hyogo',
        '奈良県' => 'nara', '和歌山県' => 'wakayama', '鳥取県' => 'tottori', '島根県' => 'shimane',
        '岡山県' => 'okayama', '広島県' => 'hiroshima', '山口県' => 'yamaguchi', '徳島県' => 'tokushima',
        '香川県' => 'kagawa', '愛媛県' => 'ehime', '高知県' => 'kochi', '福岡県' => 'fukuoka',
        '佐賀県' => 'saga', '長崎県' => 'nagasaki', '熊本県' => 'kumamoto', '大分県' => 'oita',
        '宮崎県' => 'miyazaki', '鹿児島県' => 'kagoshima', '沖縄県' => 'okinawa',
    ];

    public static function slugForArea(?string $area): ?string
    {
        return $area === null ? null : (self::AREA_SLUGS[$area] ?? null);
    }

    public static function areaForSlug(string $slug): ?string
    {
        return array_search($slug, self::AREA_SLUGS, true) ?: null;
    }

    /** 「三重県四日市市 富田栄町」のような、場所を表す短い文字列。 */
    public function getPlaceLabelAttribute(): string
    {
        $parts = array_filter([
            $this->area,
            $this->city && $this->city !== $this->area ? $this->city : null,
        ]);

        $label = implode('', $parts);

        return $this->town ? trim($label.' '.$this->town) : $label;
    }

    /**
     * 検索結果に出す価値があるページか。
     *
     * 名前と座標しか無い教室は、同名のページが何十枚も並ぶだけになる。
     * 連絡先などの手がかりが1つでもあるものを対象にする
     * （一覧からはどちらもたどれる）。
     */
    public function getIsDetailedAttribute(): bool
    {
        return (bool) ($this->address || $this->phone || $this->website
            || $this->opening_hours || $this->operator || $this->description
            || $this->target_grades || $this->lesson_style);
    }

    public function getAreaSlugAttribute(): ?string
    {
        return self::slugForArea($this->area);
    }

    /** OpenStreetMap から取り込んだ教室か（＝利用者の投稿ではないか）。 */
    public function getIsFromOsmAttribute(): bool
    {
        return $this->source === 'openstreetmap';
    }

    protected $fillable = [
        'name',
        'description',
        'area',
        'city',
        'town',
        'operator',
        'website',
        'opening_hours',
        'source',
        'source_ref',
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
