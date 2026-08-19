<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 教室の出典を持てるようにする。
     *
     * 掲載データには、利用者が投稿したものと、OpenStreetMap から取り込んだ
     * 学習塾・予備校がある。どちらなのか分からないと、出典の表示も更新もできない。
     */
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('city', 40)->nullable()->after('area');
            $table->string('operator', 100)->nullable()->after('city');
            $table->string('website')->nullable()->after('phone');
            $table->string('opening_hours')->nullable()->after('website');
            $table->string('source', 30)->nullable()->after('opening_hours');
            $table->string('source_ref', 60)->nullable()->after('source');

            $table->unique(['source', 'source_ref']);
            $table->index('area');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropUnique(['source', 'source_ref']);
            $table->dropIndex(['area']);
            $table->dropColumn(['city', 'operator', 'website', 'opening_hours', 'source', 'source_ref']);
        });
    }
};
