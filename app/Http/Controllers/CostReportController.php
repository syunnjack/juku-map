<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use App\Support\ContentModeration;
use Illuminate\Http\Request;

class CostReportController extends Controller
{
    public function store(Request $request, Venue $venue)
    {
        if (! empty($request->input('website'))) {
            return back()->with('success', '投稿を受け付けました。');
        }

        $validated = $request->validate([
            'grade_level' => 'nullable|string|max:20',
            'course_type' => 'nullable|string|max:20',
            'monthly_fee' => 'required|integer|min:0|max:1000000',
            'annual_other_fees' => 'nullable|integer|min:0|max:5000000',
            'comment' => 'nullable|string|max:1000',
            'nickname' => 'nullable|string|max:30',
        ]);

        if (! empty($validated['comment']) && ContentModeration::containsNgWord($validated['comment'])) {
            return back()->withErrors(['comment' => '投稿内容に使用できない文字列が含まれています。'])->withInput();
        }

        $ipHash = ContentModeration::clientIpHash($request);
        if (ContentModeration::isTooSoon("cost-report:{$venue->id}:{$ipHash}", 30)) {
            return back()->withErrors(['monthly_fee' => '投稿間隔が短すぎます。しばらく待ってから再度お試しください。'])->withInput();
        }

        $venue->costReports()->create([
            'grade_level' => $validated['grade_level'] ?? null,
            'course_type' => $validated['course_type'] ?? null,
            'monthly_fee' => $validated['monthly_fee'],
            'annual_other_fees' => $validated['annual_other_fees'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'nickname' => ($validated['nickname'] ?? '') !== '' ? $validated['nickname'] : '匿名',
            'ip_hash' => $ipHash,
        ]);

        return back()->with('success', '月謝・費用の口コミを投稿しました。ありがとうございます。');
    }
}
