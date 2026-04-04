<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 *  Table to store jobs. Jobs name were used already by Laravel jobs table, so why we used work_jobs table name.
 */
#[Fillable(['title', 'salary_start', 'salary_end', 'company', 'description', 'contacts', 'location', 'technologies'])]
class WorkJob extends Model
{
    protected function casts(): array
    {
        return [
            'salary_start' => 'decimal:2',
            'salary_end' => 'decimal:2',
            'technologies' => 'array',
        ];
    }
}
