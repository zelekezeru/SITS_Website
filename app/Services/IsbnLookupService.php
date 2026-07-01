<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class IsbnLookupService
{
    /**
     * Look up book metadata from Open Library by ISBN.
     *
     * @return array{title:?string,subtitle:?string,authors:?array,publisher:?string,published_year:?int,page_count:?int,subject:?string,cover_url:?string,description:?string}|null
     */
    public function lookup(string $isbn): ?array
    {
        $isbn = preg_replace('/[^0-9Xx]/', '', $isbn);

        if (strlen($isbn) !== 10 && strlen($isbn) !== 13) {
            return null;
        }

        // Cache results for 24 hours to avoid repeated API calls
        return Cache::remember("isbn_lookup_{$isbn}", 86400, function () use ($isbn) {
            return $this->fetchFromOpenLibrary($isbn);
        });
    }

    protected function fetchFromOpenLibrary(string $isbn): ?array
    {
        // Try the ISBN endpoint first
        $response = Http::timeout(10)->get("https://openlibrary.org/isbn/{$isbn}.json");

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        // Resolve authors (they're stored as references in OL)
        $authors = [];
        foreach ($data['authors'] ?? [] as $authorRef) {
            $authorKey = $authorRef['key'] ?? null;
            if ($authorKey) {
                $authorResp = Http::timeout(5)->get("https://openlibrary.org{$authorKey}.json");
                if ($authorResp->successful()) {
                    $authors[] = $authorResp->json()['name'] ?? null;
                }
            }
        }
        $authors = array_filter($authors);

        // Get work-level data for richer description and subjects
        $workKey = $data['works'][0]['key'] ?? null;
        $workData = [];
        if ($workKey) {
            $workResp = Http::timeout(5)->get("https://openlibrary.org{$workKey}.json");
            if ($workResp->successful()) {
                $workData = $workResp->json();
            }
        }

        // Build description from work or edition
        $description = null;
        $descRaw = $workData['description'] ?? $data['description'] ?? null;
        if (is_array($descRaw)) {
            $description = $descRaw['value'] ?? null;
        } elseif (is_string($descRaw)) {
            $description = $descRaw;
        }

        // Subjects from work
        $subjects = $workData['subjects'] ?? [];
        $subjectStr = implode(', ', array_slice($subjects, 0, 5));

        // Cover
        $coverId = $data['covers'][0] ?? null;
        $coverUrl = $coverId ? "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg" : null;

        // Published year
        $publishDate = $data['publish_date'] ?? '';
        preg_match('/(\d{4})/', $publishDate, $yearMatch);
        $year = $yearMatch[1] ?? null;

        // Publisher
        $publishers = $data['publishers'] ?? [];
        $publisher = $publishers[0] ?? null;

        return [
            'title'          => $data['title'] ?? null,
            'subtitle'       => $data['subtitle'] ?? null,
            'authors'        => $authors,
            'publisher'      => $publisher,
            'published_year' => $year ? (int) $year : null,
            'page_count'     => $data['number_of_pages'] ?? null,
            'subject'        => $subjectStr ?: null,
            'cover_url'      => $coverUrl,
            'description'    => $description,
        ];
    }
}
