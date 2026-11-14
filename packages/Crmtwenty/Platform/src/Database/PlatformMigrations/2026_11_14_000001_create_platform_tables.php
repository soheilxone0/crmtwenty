<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables of the platform itself (shared by all companies). They are created on the
 * `platform` connection, which always uses the installation's base table prefix.
 */
return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        $schema = Schema::connection('platform');

        if (! $schema->hasTable('platform_admins')) {
            $schema->create('platform_admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('username', 60)->unique();
                $table->string('email')->unique();
                $table->string('password');
                $table->boolean('status')->default(true);
                $table->rememberToken();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('platform_password_resets')) {
            $schema->create('platform_password_resets', function (Blueprint $table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! $schema->hasTable('platform_companies')) {
            $schema->create('platform_companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('table_prefix', 20)->unique();
                $table->string('status', 20)->default('provisioning');
                $table->unsignedBigInteger('manager_user_id')->nullable();
                $table->string('manager_name')->nullable();
                $table->string('manager_email')->nullable();
                $table->string('manager_username', 60)->nullable();
                $table->string('phone', 40)->nullable();
                $table->text('notes')->nullable();
                $table->string('migrated_revision', 64)->nullable();
                $table->timestamp('last_activity_at')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('platform_logins')) {
            $schema->create('platform_logins', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('user_id');
                $table->string('email')->unique();
                $table->string('username', 60)->nullable()->unique();
                $table->timestamps();

                $table->unique(['company_id', 'user_id']);
            });
        }

        if (! $schema->hasTable('platform_settings')) {
            $schema->create('platform_settings', function (Blueprint $table) {
                $table->string('key', 100)->primary();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('platform');

        foreach (['platform_settings', 'platform_logins', 'platform_companies', 'platform_password_resets', 'platform_admins'] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
