<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            // 対象学年：['小学生','中学生','高校生','大学受験・浪人生'] などの JSON 配列
            $table->json('target_grades')->nullable()->after('area');
            $table->string('lesson_style')->nullable()->after('target_grades'); // 個別/集団/オンライン
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn(['target_grades', 'lesson_style']);
        });
    }
};
