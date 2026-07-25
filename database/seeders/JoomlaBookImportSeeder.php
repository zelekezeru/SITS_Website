<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Category;
use App\Models\Library;
use App\Models\Book;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * JoomlaBookImportSeeder
 *
 * Automatically imports books, authors, and categories from Joomla's Alexandria Book Library dumps
 * (`sitseduorg_joomla.sql` & `sitseduorg_jo749sb.sql`) directly into both:
 * 1. The public digital library (`libraries` table)
 * 2. The ILS library management catalog (`books`, `authors`, `categories`, `author_book`, `book_category` tables)
 */
class JoomlaBookImportSeeder extends Seeder
{
    public function run(): void
    {
        @ini_set('memory_limit', '1024M');

        $files = [
            'c:/Users/hp/Downloads/sitseduorg_joomla.sql',
            'c:/Users/hp/Downloads/sitseduorg_jo749sb.sql',
        ];

        $categoriesMap = []; // old_id => category_name
        $authorsMap    = []; // old_id => author_name
        $booksParsed   = []; // title => book_row

        foreach ($files as $file) {
            if (!file_exists($file)) continue;

            $this->command?->info("Parsing book dump file: " . basename($file));
            $content = file_get_contents($file);

            // 1. Parse Categories (josn9_abcategories & josn9_categories)
            if (preg_match_all('/INSERT INTO [`"]?josn9_abcategories[`"]?\s*\(([^\)]+)\)\s*VALUES\s*(.*?);/is', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $cols = array_map(fn($c) => trim(str_replace('`', '', $c)), explode(',', $m[1]));
                    preg_match_all('/\((.*?)\)(?:,\s*|\s*$)/s', $m[2], $rows);
                    foreach ($rows[1] as $r) {
                        $v = str_getcsv($r, ',', "'");
                        if (count($v) >= count($cols)) {
                            $row = array_combine(array_slice($cols, 0, count($v)), $v);
                            $catName = trim($row['title'] ?? $row['name'] ?? 'General');
                            if ($catName) {
                                $categoriesMap[$row['id']] = $catName;
                            }
                        }
                    }
                }
            }

            // 2. Parse Authors (josn9_abauthor)
            if (preg_match_all('/INSERT INTO [`"]?josn9_abauthor[`"]?\s*\(([^\)]+)\)\s*VALUES\s*(.*?);/is', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $cols = array_map(fn($c) => trim(str_replace('`', '', $c)), explode(',', $m[1]));
                    preg_match_all('/\((.*?)\)(?:,\s*|\s*$)/s', $m[2], $rows);
                    foreach ($rows[1] as $r) {
                        $v = str_getcsv($r, ',', "'");
                        if (count($v) >= count($cols)) {
                            $row = array_combine(array_slice($cols, 0, count($v)), $v);
                            $authorName = trim(($row['name'] ?? '') . ' ' . ($row['lastname'] ?? ''));
                            if (empty($authorName)) $authorName = trim($row['alias'] ?? 'Unknown Author');
                            if ($authorName) {
                                $authorsMap[$row['id']] = $authorName;
                            }
                        }
                    }
                }
            }

            // 3. Parse Books (josn9_abbook)
            if (preg_match_all('/INSERT INTO [`"]?josn9_abbook[`"]?\s*\(([^\)]+)\)\s*VALUES\s*(.*?);/is', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $cols = array_map(fn($c) => trim(str_replace('`', '', $c)), explode(',', $m[1]));
                    preg_match_all('/\((.*?)\)(?:,\s*|\s*$)/s', $m[2], $rows);
                    foreach ($rows[1] as $r) {
                        $v = str_getcsv($r, ',', "'");
                        if (count($v) >= count($cols)) {
                            $row = array_combine(array_slice($cols, 0, count($v)), $v);
                            $title = trim($row['title'] ?? '');
                            if (!$title) continue;

                            if (!isset($booksParsed[$title])) {
                                $booksParsed[$title] = $row;
                            }
                        }
                    }
                }
            }

            // Free memory after each file
            unset($content);
        }

        $this->command?->info("Found " . count($categoriesMap) . " categories, " . count($authorsMap) . " authors, " . count($booksParsed) . " books.");

        // Step A: Seed Categories into DB
        foreach ($categoriesMap as $oldId => $name) {
            Category::firstOrCreate(
                ['name' => mb_substr($name, 0, 255, 'UTF-8')],
                ['code' => Str::slug(mb_substr($name, 0, 50, 'UTF-8'))]
            );
        }

        // Step B: Seed Authors into DB
        foreach ($authorsMap as $oldId => $name) {
            Author::firstOrCreate(
                ['name' => mb_substr($name, 0, 255, 'UTF-8')],
                ['slug' => Str::slug(mb_substr($name, 0, 50, 'UTF-8'))]
            );
        }

        // Step C: Seed Books into `libraries` and `books`
        $importedLibraries = 0;
        $importedBooks = 0;
        $now = now()->toDateTimeString();

        $libraryInserts = [];
        $bookInserts    = [];

        foreach ($booksParsed as $title => $row) {
            $bannerClean = !empty($row['image']) ? explode('#', $row['image'])[0] : null;
            $bannerPath  = $bannerClean ? mb_substr(str_starts_with($bannerClean, '/') ? $bannerClean : '/' . $bannerClean, 0, 255, 'UTF-8') : null;
            $filePath    = !empty($row['file']) ? mb_substr(str_starts_with($row['file'], '/') ? $row['file'] : '/images/books/' . $row['file'], 0, 255, 'UTF-8') : null;
            $catName     = $categoriesMap[$row['catid'] ?? 0] ?? 'General';
            $desc        = $row['description'] ?? null;
            $state       = intval($row['state'] ?? 1);

            // 1. Digital Library record
            $libraryInserts[] = [
                'title'       => mb_substr($title, 0, 50, 'UTF-8'),
                'description' => $desc,
                'banner'      => $bannerPath,
                'file'        => !empty($row['file']) ? mb_substr($row['file'], 0, 255, 'UTF-8') : null,
                'link'        => $filePath,
                'category'    => mb_substr($catName, 0, 100, 'UTF-8'),
                'status'      => $state === 1,
                'visibility'  => $state === 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            // 2. ILS Book Catalog record
            $bookInserts[] = [
                'title'       => mb_substr($title, 0, 255, 'UTF-8'),
                'description' => $desc,
                'cover_path'  => $bannerPath,
                'cover_url'   => $bannerPath,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        // Execute bulk inserts in chunks of 500
        foreach (array_chunk($libraryInserts, 500) as $chunk) {
            DB::table('libraries')->insert($chunk);
            $importedLibraries += count($chunk);
        }

        foreach (array_chunk($bookInserts, 500) as $chunk) {
            DB::table('books')->insert($chunk);
            $importedBooks += count($chunk);
        }

        $this->command?->info("Book Import Complete: {$importedLibraries} digital library entries and {$importedBooks} ILS book catalog entries created.");
    }
}
