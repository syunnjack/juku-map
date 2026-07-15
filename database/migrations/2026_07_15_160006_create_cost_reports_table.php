<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->string('grade_level', 20)->nullable();
            $table->string('course_type', 20)->nullable();
            $table->unsignedInteger('monthly_fee');
            $table->unsignedInteger('annual_other_fees')->nullable();
            $table->text('comment')->nullable();
            $table->string('nickname', 30)->default('匿名');
            $table->string('ip_hash', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_reports');
    }
};
