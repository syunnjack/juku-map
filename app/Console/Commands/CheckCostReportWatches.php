<?php

namespace App\Console\Commands;

use App\Models\CostReport;
use App\Models\Favorite;
use App\Support\LineMessaging;
use Illuminate\Console\Command;

class CheckCostReportWatches extends Command
{
    protected $signature = 'costs:check-watches';

    protected $description = 'ウォッチ登録された塾・教室に新しい月謝・費用の口コミが投稿されていないか確認し、LINEで通知する';

    public function handle(): int
    {
        $favorites = Favorite::with('lineUser')->get();

        foreach ($favorites as $favorite) {
            if (! $favorite->lineUser) {
                continue;
            }

            $since = $favorite->last_checked_report_id ?? 0;
            $newReports = CostReport::where('venue_id', $favorite->venue_id)
                ->where('id', '>', $since)
                ->get();

            if ($newReports->isEmpty()) {
                continue;
            }

            $latest = $newReports->sortByDesc('id')->first();
            $favorite->loadMissing('venue');
            LineMessaging::push(
                $favorite->lineUser->line_user_id,
                "「{$favorite->venue->name}」の新しい月謝・費用の口コミが投稿されました: 月謝" . number_format($latest->monthly_fee) . '円'
                . ($latest->course_type ? "（{$latest->course_type}）" : '')
            );

            // last_checked_report_idは検知カーソル。idは常に厳密単調増加のため、
            // created_at(秒精度)を使った場合に起こりうる同一秒内の複数投稿の取りこぼしが起きない。
            $favorite->update(['last_checked_report_id' => $newReports->max('id')]);
        }

        return self::SUCCESS;
    }
}
