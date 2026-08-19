<?php

namespace Tests\Feature;

use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeVenue(string $area, string $name): Venue
    {
        return Venue::create([
            'name' => $name,
            'area' => $area,
            'city' => '渋谷区',
            'lat' => 35.68,
            'lng' => 139.76,
            'source' => 'openstreetmap',
            'source_ref' => 'node/'.random_int(1, 99999999),
        ]);
    }

    public function test_都道府県ページが掲載件数と教室を表示する(): void
    {
        $this->makeVenue('東京都', 'テスト進学教室');

        $this->get('/areas/tokyo')
            ->assertOk()
            ->assertSee('テスト進学教室')
            ->assertSee('東京都の学習塾・予備校');
    }

    public function test_掲載の無い都道府県ページと知らないURLは404になる(): void
    {
        $this->makeVenue('東京都', 'テスト進学教室');

        $this->get('/areas/okinawa')->assertNotFound();
        $this->get('/areas/nowhere')->assertNotFound();
    }

    public function test_日本語のエリアURLはローマ字のURLへ転送される(): void
    {
        $this->makeVenue('東京都', 'テスト進学教室');

        $this->get('/areas/'.urlencode('東京都'))
            ->assertRedirect(route('areas.show', 'tokyo'));
    }

    public function test_旧エリア検索も都道府県ページへ転送される(): void
    {
        $this->makeVenue('東京都', 'テスト進学教室');

        $this->get('/?area='.urlencode('東京都'))
            ->assertRedirect(route('areas.show', 'tokyo'));
    }

    public function test_2ページ目は自分自身を正規URLとして申告する(): void
    {
        foreach (range(1, 61) as $i) {
            $this->makeVenue('東京都', "テスト進学教室{$i}");
        }

        $this->get('/areas/tokyo?page=2')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('areas.show', 'tokyo').'?page=2">', false);
    }

    public function test_絞り込みページはnoindexにする(): void
    {
        $this->makeVenue('東京都', 'テスト進学教室');

        $this->get('/')->assertOk()->assertDontSee('name="robots"', false);
        $this->get('/?lesson_style='.urlencode('個別指導'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_サイトマップに都道府県ページがローマ字で載る(): void
    {
        $this->makeVenue('東京都', 'テスト進学教室');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('areas.show', 'tokyo'), false);
    }

    public function test_教室ページに出典が載る(): void
    {
        $venue = $this->makeVenue('東京都', 'テスト進学教室');

        $this->get("/venues/{$venue->id}")
            ->assertOk()
            ->assertSee('OpenStreetMap', false);
    }
}
