<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_logs', function (Blueprint $table) {
            $table->id();
            $table->string('from_version');
            $table->string('to_version');
            $table->string('status'); // success, failed, rolled_back
            $table->text('log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_logs');
    }
};
