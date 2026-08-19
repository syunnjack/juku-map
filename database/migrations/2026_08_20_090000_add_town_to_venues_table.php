<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 町名を持てるようにする。
     *
     * OpenStreetMap 側は86%の教室が名前と座標しか持っておらず、
     * 「KUMON（三重県）」という同じ見出しのページが289枚並んでいた。
     * 国土地理院の逆ジオコーディングで市区町村と町名を補い、
     * どこの教室なのかが分かるようにする。
     */
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->string('town', 60)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn('town');
        });
    }
};
