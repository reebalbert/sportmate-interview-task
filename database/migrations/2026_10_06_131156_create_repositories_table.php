<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_target_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('github_id')->unique();
            $table->string('name');
            $table->string('full_name');
            $table->text('description')->nullable();
            $table->longText('readme_content')->nullable();
            $table->string('html_url');
            $table->string('language')->nullable();
            $table->unsignedInteger('stargazers_count')->default(0);
            $table->unsignedInteger('open_issues_count')->default(0);
            $table->boolean('archived')->default(false);
            $table->timestamp('github_updated_at')->nullable();
            $table->timestamps();

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['name', 'full_name', 'description', 'readme_content'], 'repositories_search_fulltext');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
