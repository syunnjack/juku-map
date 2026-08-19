<?php

namespace Database\Seeders;

use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class OsmJukuSeeder extends Seeder
{
    /**
     * OpenStreetMap から取り出した学習塾・予備校を取り込む。
     *
     * データは scripts/build-juku-data.py が database/data/juku-osm.json に書き出す。
     * 出典は OpenStreetMap contributors（ODbL 1.0）で、表示側に明記する必要がある。
     * 元データに無い項目（月謝、対象学年、合格実績など）は補わずに空のままにする。
     * 利用者が投稿した教室（source が null）には触れない。
     */
    private const CHUNK = 40;

    public function run(): void
    {
        $path = database_path('data/juku-osm.json');

        if (! File::exists($path)) {
            throw new RuntimeException('database/data/juku-osm.json が見つかりません。');
        }

        $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $schools = $payload['schools'] ?? [];

        if ($schools === []) {
            throw new RuntimeException('教室データが空です。');
        }

        $now = now();
        $written = 0;

        foreach (array_chunk($schools, self::CHUNK) as $chunk) {
            $rows = [];

            foreach ($chunk as $school) {
                $rows[] = [
                    'name' => $school['name'],
                    'area' => $school['area'],
                    'city' => $school['city'],
                    'operator' => $school['operator'],
                    'address' => $school['address'],
                    'phone' => $school['phone'],
                    'website' => $school['website'],
                    'opening_hours' => $school['openingHours'],
                    'lat' => $school['lat'],
                    'lng' => $school['lng'],
                    'source' => 'openstreetmap',
                    'source_ref' => $school['sourceRef'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('venues')->upsert(
                $rows,
                ['source', 'source_ref'],
                [
                    'name', 'area', 'city', 'operator', 'address', 'phone',
                    'website', 'opening_hours', 'lat', 'lng', 'updated_at',
                ]
            );

            $written += count($rows);
        }

        $this->command?->info(number_format($written).'件を取り込みました（掲載中 '
            .number_format(Venue::count()).'件）。');
    }
}
