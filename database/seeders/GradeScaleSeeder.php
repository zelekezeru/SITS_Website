<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeScaleSeeder extends Seeder
{
    /** Default five-band grade scale (data-driven). Score 0–100. */
    public function run(): void
    {
        DB::table('grade_scales')->updateOrInsert(
            ['name' => 'SITS Standard 5-band'],
            ['name' => 'SITS Standard 5-band', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
        );

        $scaleId = DB::table('grade_scales')->where('name', 'SITS Standard 5-band')->value('id');

        $bands = [
            ['label_en' => 'Excellent',         'label_am' => 'በጣም ጥሩ',       'min_score' => 90, 'max_score' => 100,  'triggers_increment' => true,  'increment_pct' => 8, 'sort_order' => 1],
            ['label_en' => 'Very Good',         'label_am' => 'ጥሩ',           'min_score' => 75, 'max_score' => 89.99,'triggers_increment' => true,  'increment_pct' => 4, 'sort_order' => 2],
            ['label_en' => 'Satisfactory',      'label_am' => 'አጥጋቢ',         'min_score' => 60, 'max_score' => 74.99,'triggers_increment' => false, 'increment_pct' => 0, 'sort_order' => 3],
            ['label_en' => 'Needs Improvement', 'label_am' => 'መሻሻል ይፈልጋል',  'min_score' => 50, 'max_score' => 59.99,'triggers_increment' => false, 'increment_pct' => 0, 'sort_order' => 4],
            ['label_en' => 'Unsatisfactory',    'label_am' => 'አጥጋቢ ያልሆነ',    'min_score' => 0,  'max_score' => 49.99,'triggers_increment' => false, 'increment_pct' => 0, 'sort_order' => 5],
        ];

        foreach ($bands as $b) {
            DB::table('grade_bands')->updateOrInsert(
                ['grade_scale_id' => $scaleId, 'label_en' => $b['label_en']],
                array_merge($b, ['grade_scale_id' => $scaleId, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
