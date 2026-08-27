<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('platform_status_components', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name');
            $table->string('status', 32)->default('operational'); // operational|degraded|partial_outage|major_outage|maintenance
            $table->string('description', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_status_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('severity', 32)->default('minor'); // minor|major|critical
            $table->string('status', 32)->default('investigating'); // investigating|identified|monitoring|resolved
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('platform_status_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_status_subscribers');
        Schema::connection('central')->dropIfExists('platform_status_incidents');
        Schema::connection('central')->dropIfExists('platform_status_components');
    }
};
