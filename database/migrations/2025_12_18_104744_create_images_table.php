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
        Schema::create('images', function (Blueprint $table) {
            $table->id();

            $table->string('image');
            $table->string('title');
            $table->decimal('price', 10, 2)->default(0);
            $table->text('desc')->nullable();

            $table->unsignedBigInteger('category_id')->nullable()->default(0);

            $table->enum('status', ['Approved', 'Pending', 'Rejected'])
                  ->default('Pending');

            $table->string('alt')->nullable();
            $table->unsignedBigInteger('user_id');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
