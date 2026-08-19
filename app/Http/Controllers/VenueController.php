<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Support\ContentModeration;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /** 1ページに載せる教室の数。 */
    private const PER_PAGE = 60;

    public function index(Request $request)
    {
        // 旧URL（/?area=東京都）は都道府県ページへ送る。
        if ($request->filled('area')) {
            $slug = Venue::slugForArea((string) $request->input('area'));

            if ($slug !== null) {
                return redirect()->route('areas.show', ['area' => $slug], 301);
            }
        }

        $venues = $this->filtered($request)->latest()->paginate(self::PER_PAGE)->withQueryString();

        return view('venues.index', [
            'venues' => $venues,
            'areaCounts' => $this->areaCounts(),
            'area' => null,
            'areaSlug' => null,
            'total' => Venue::count(),
        ]);
    }

    /** 対象学年・授業形式での絞り込み。どちらも利用者の投稿がある教室にだけ入っている。 */
    private function filtered(Request $request)
    {
        return Venue::query()
            ->withAvg('costReports', 'monthly_fee')
            ->when($request->filled('grade'), fn ($query) => $query->whereJsonContains('target_grades', $request->input('grade')))
            ->when($request->filled('lesson_style'), fn ($query) => $query->where('lesson_style', $request->input('lesson_style')));
    }

    /** 都道府県ごとの掲載件数（多い順）。 */
    private function areaCounts()
    {
        return Venue::query()
            ->selectRaw('area, COUNT(*) as total')
            ->whereNotNull('area')
            ->groupBy('area')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'area' => $row->area,
                'slug' => Venue::slugForArea($row->area),
                'total' => (int) $row->total,
            ])
            ->filter(fn (array $row) => $row['slug'] !== null)
            ->values();
    }

    public function create()
    {
        return view('venues.create');
    }

    public function store(Request $request)
    {
        if (! empty($request->input('website'))) {
            return redirect()->route('venues.thanks');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'area' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'target_grades' => 'nullable|array',
            'target_grades.*' => 'string|in:' . implode(',', Venue::GRADE_OPTIONS),
            'lesson_style' => 'nullable|string|in:' . implode(',', Venue::LESSON_STYLE_OPTIONS),
        ]);

        if (ContentModeration::containsNgWord($validated['name'] . ' ' . ($validated['description'] ?? ''))) {
            return back()->withErrors(['name' => '投稿内容に使用できない文字列が含まれています。'])->withInput();
        }

        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("venue-create:{$ipHash}", 30)) {
            return back()->withErrors(['name' => '投稿間隔が短すぎます。しばらく待ってから再度お試しください。'])->withInput();
        }

        Venue::create($validated);

        return redirect()->route('venues.thanks');
    }

    public function show(Venue $venue)
    {
        $venue->load(['reviews' => fn ($q) => $q->latest()]);
        $venue->load(['costReports' => fn ($q) => $q->latest()]);

        $isWatching = session('line_user_local_id')
            ? $venue->favorites()->where('line_user_id', session('line_user_local_id'))->exists()
            : false;

        $hasRequestedDocument = session('line_user_local_id')
            ? $venue->documentRequests()->where('line_user_id', session('line_user_local_id'))->exists()
            : false;

        $averageMonthlyFee = $venue->costReports->isNotEmpty()
            ? (int) round($venue->costReports->avg('monthly_fee'))
            : null;

        return view('venues.show', compact('venue', 'isWatching', 'hasRequestedDocument', 'averageMonthlyFee'));
    }

    public function like(Request $request, Venue $venue)
    {
        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("like:{$venue->id}:{$ipHash}", 60)) {
            return response()->json(['error' => 'いいね！は少し時間を空けてから再度お試しください。'], 429);
        }

        $venue->increment('likes_count');
        $venue->refresh();

        return response()->json(['likes_count' => $venue->likes_count]);
    }

    public function sitemap()
    {
        // 名前と座標しか無い教室は、同名のページが何十枚も並ぶだけなので
        // サイトマップには載せない（一覧からはたどれる）。
        $venues = Venue::select('id', 'area', 'updated_at', 'address', 'phone', 'website',
            'opening_hours', 'operator', 'description', 'target_grades', 'lesson_style')
            ->get()
            ->filter(fn (Venue $venue) => $venue->is_detailed)
            ->values();
        $areas = Venue::whereNotNull('area')->distinct()->pluck('area');

        $urls = collect();


        // トップページ
        $urls->push('<url><loc>' . url('/') . '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>');

        // エリア別ページ（URLはローマ字。日本語のままだと %E6%9D%B1... になる）
        foreach ($areas as $area) {
            $slug = Venue::slugForArea($area);

            if ($slug === null) {
                continue;
            }

            $urls->push('<url><loc>' . route('areas.show', $slug) . '</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>');
        }

        // 個別塾ページ
        foreach ($venues as $venue) {
            $urls->push('<url><loc>' . url("/venues/{$venue->id}") . '</loc><lastmod>' . $venue->updated_at->toDateString() . '</lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>');
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
