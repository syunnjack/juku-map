<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    /** 1ページに載せる教室の数。 */
    private const PER_PAGE = 60;

    /**
     * 都道府県ページ（/areas/tokyo）。
     *
     * 以前は日本語の都道府県名をそのままURLに入れていたため、
     * リンクが %E6%9D%B1... と読めない形になっていた。ローマ字に変え、
     * 古い形のURLは新しいURLへ転送する。
     */
    public function show(Request $request, string $area)
    {
        $prefecture = Venue::areaForSlug($area);

        if ($prefecture === null) {
            // 日本語の都道府県名で来た古いURL
            $slug = Venue::slugForArea($area);

            if ($slug !== null) {
                return redirect()->route('areas.show', ['area' => $slug], 301);
            }

            abort(404);
        }

        $venues = Venue::query()
            ->withAvg('costReports', 'monthly_fee')
            ->where('area', $prefecture)
            ->when($request->filled('grade'), fn ($query) => $query->whereJsonContains('target_grades', $request->input('grade')))
            ->when($request->filled('lesson_style'), fn ($query) => $query->where('lesson_style', $request->input('lesson_style')))
            ->orderBy('city')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // 中身の無いページを作らない。
        if ($venues->total() === 0) {
            abort(404);
        }

        return view('venues.index', [
            'venues' => $venues,
            'areaCounts' => $this->areaCounts(),
            'area' => $prefecture,
            'areaSlug' => $area,
            'total' => $venues->total(),
        ]);
    }

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
}
