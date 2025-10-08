<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('teams', function (Blueprint $table) {
      $table->id();
      $table->foreignId('team_head_id')->nullable()->constrained('users')->onDelete('set null');
      $table->string('name', 191);
      $table->string('code', 50)->unique();
      $table->string('notes', 500)->nullable();
      $table->boolean('is_chat_enabled')->default(true);
      $table->boolean('is_task_enabled')->default(false);
      $table->enum('status', ['active', 'inactive'])->default('active');

      $table->foreignId('created_by_id')->nullable()->constrained('users')->onDelete('set null');
      $table->foreignId('updated_by_id')->nullable()->constrained('users')->onDelete('set null');
      $table->string('tenant_id', 191)->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('teams');
  }
};
