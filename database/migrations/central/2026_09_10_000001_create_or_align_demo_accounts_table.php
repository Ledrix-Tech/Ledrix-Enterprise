<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        $schema = Schema::connection('central');

        if (! $schema->hasTable('demo_accounts')) {
            $schema->create('demo_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('company')->nullable();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('status', 32)->default('active')->index();
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip', 45)->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->json('meta')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });

            return;
        }

        $schema->table('demo_accounts', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('demo_accounts', 'name')) {
                $table->string('name')->nullable();
            }
            if (! $schema->hasColumn('demo_accounts', 'email')) {
                $table->string('email')->unique();
            }
            if (! $schema->hasColumn('demo_accounts', 'password')) {
                $table->string('password')->nullable();
            }
            if (! $schema->hasColumn('demo_accounts', 'company')) {
                $table->string('company')->nullable();
            }
            if (! $schema->hasColumn('demo_accounts', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
            }
            if (! $schema->hasColumn('demo_accounts', 'status')) {
                $table->string('status', 32)->default('active')->index();
            }
            if (! $schema->hasColumn('demo_accounts', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (! $schema->hasColumn('demo_accounts', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable();
            }
            if (! $schema->hasColumn('demo_accounts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            if (! $schema->hasColumn('demo_accounts', 'meta')) {
                $table->json('meta')->nullable();
            }
            if (! $schema->hasColumn('demo_accounts', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }

    public function down(): void
    {
        // Keep the table — production may already store demo visitors here.
    }
};
