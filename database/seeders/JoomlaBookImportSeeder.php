<?php

namespace Database\Seeders;

use App\Models\Library;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * JoomlaBookImportSeeder
 *
 * Automatically imports books from Joomla's Alexandria Book Library (josn9_abbook)
 * database tables directly into the SITS Laravel libraries table.
 */
class JoomlaBookImportSeeder extends Seeder
{
    public function run(): void
    {
        $imported = 0;
        $skipped  = 0;

        try {
            // Check connection
            DB::connection('joomla')->getPdo();
            
            $prefix = config('database.connections.joomla.prefix', 'josn9_');
            
            $rows = DB::connection('joomla')->select("
                SELECT b.id, b.title, b.description, b.image as banner, b.file, c.title as category_name, b.state
                FROM {$prefix}abbook b
                LEFT JOIN {$prefix}categories c ON b.catid = c.id
            ");

            foreach ($rows as $row) {
                // Skip if a book with the same title already exists in Laravel
                if (Library::where('title', $row->title)->exists()) {
                    $skipped++;
                    continue;
                }

                // If description contains download links or cover image references, clean them up or adapt
                // The Joomla paths for images are typically like "images/books/hol.jpg"
                $bannerClean = $row->banner ? explode('#', $row->banner)[0] : null;
                $bannerPath  = $bannerClean ? mb_substr(str_starts_with($bannerClean, '/') ? $bannerClean : '/' . $bannerClean, 0, 255, 'UTF-8') : null;
                
                $filePath = $row->file ? mb_substr(str_starts_with($row->file, '/') ? $row->file : '/images/books/' . $row->file, 0, 255, 'UTF-8') : null;

                Library::create([
                    'title'       => mb_substr($row->title, 0, 50, 'UTF-8'), // SITS title column has length 50
                    'description' => $row->description,
                    'banner'      => $bannerPath,
                    'file'        => mb_substr($row->file, 0, 255, 'UTF-8'),
                    'link'        => $filePath, // Point direct download link to file path
                    'category'    => $row->category_name ?? 'General',
                    'status'      => intval($row->state) === 1,
                    'visibility'  => intval($row->state) === 1,
                ]);

                $imported++;
            }

            $this->command->info("Library Import: {$imported} books imported, {$skipped} skipped.");

        } catch (\Exception $e) {
            $this->command->error('Could not connect to Joomla database for Book Import: ' . $e->getMessage());
        }
    }
}
