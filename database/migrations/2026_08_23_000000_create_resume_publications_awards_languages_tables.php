<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('publisher')->nullable();
            $table->date('publication_date')->nullable();
            $table->string('url', 2048)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('award_honors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('issuer')->nullable();
            $table->date('awarded_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('proficiency', 50)->default('Intermediate');
            $table->timestamps();
        });

        foreach ([
            'resume_publication' => 'publication_id',
            'resume_award_honor' => 'award_honor_id',
            'resume_language' => 'language_id',
        ] as $tableName => $foreignKey) {
            Schema::create($tableName, function (Blueprint $table) use ($foreignKey) {
                $table->id();
                $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
                $table->foreignId($foreignKey)->constrained()->cascadeOnDelete();
                $table->unsignedInteger('order')->default(0);
                $table->timestamps();
                $table->unique(['resume_id', $foreignKey]);
            });
        }

        // Preserve languages previously stored as a JSON list in Additional Information.
        if (Schema::hasTable('additional_information')) {
            DB::table('additional_information')
                ->whereNotNull('languages')
                ->orderBy('id')
                ->each(function ($additionalInfo) {
                    $languages = json_decode($additionalInfo->languages, true);

                    if (! is_array($languages)) {
                        return;
                    }

                    foreach (array_values(array_unique(array_filter($languages))) as $order => $name) {
                        $languageId = DB::table('languages')->insertGetId([
                            'user_id' => $additionalInfo->user_id,
                            'name' => $name,
                            'proficiency' => 'Intermediate',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        if ($additionalInfo->resume_id) {
                            DB::table('resume_language')->insert([
                                'resume_id' => $additionalInfo->resume_id,
                                'language_id' => $languageId,
                                'order' => $order,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_language');
        Schema::dropIfExists('resume_award_honor');
        Schema::dropIfExists('resume_publication');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('award_honors');
        Schema::dropIfExists('publications');
    }
};

