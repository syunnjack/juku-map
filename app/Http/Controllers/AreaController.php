<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function show(Request $request, string $area)
    {
        $query = Venue::query()
            ->withAvg('costReports', 'monthly_fee')
            ->where('area', $area);

        if ($request->filled('grade')) {
            $query->whereJsonContains('target_grades', $request->input('grade'));
        }

        if ($request->filled('lesson_style')) {
            $query->where('lesson_style', $request->input('lesson_style'));
        }

        $venues = $query->latest()->get();

        abort_if($venues->isEmpty(), 404);

        return view('venues.area', compact('venues', 'area'));
    }
}
