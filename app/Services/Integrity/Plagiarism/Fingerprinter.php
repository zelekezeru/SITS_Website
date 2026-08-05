<?php

namespace App\Services\Integrity\Plagiarism;

use App\Models\CorpusFingerprint;
use App\Models\IntegrityDocument;

/**
 * Breaks a document into overlapping 8-word shingles and hashes each one
 * for corpus matching.
 *
 * Position indices are aligned to the ORIGINAL whitespace-tokenized text
 * (words()), not a separately-normalized string — normalizing per-token
 * (rather than normalizing the whole blob before splitting) guarantees the
 * word count never drifts, so a stored `position` always safely maps back
 * to `words()[position]` for excerpt reconstruction later.
 */
class Fingerprinter
{
    public const SHINGLE_SIZE = 8;

    /**
     * @return list<string>
     */
    public function words(string $text): array
    {
        return preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * @param  list<string>  $words
     * @return list<string>
     */
    public function shingles(array $words): array
    {
        $normalized = array_map([$this, 'normalizeToken'], $words);
        $count = count($normalized);

        $shingles = [];
        for ($i = 0; $i <= $count - self::SHINGLE_SIZE; $i++) {
            $shingles[] = implode(' ', array_slice($normalized, $i, self::SHINGLE_SIZE));
        }

        return $shingles;
    }

    protected function normalizeToken(string $word): string
    {
        return mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $word) ?? '');
    }

    /**
     * Combine two independent CRC32s (forward + reversed shingle) into one
     * hash. Each is masked to 31 bits before combining — a full 64-bit pack
     * via `<< 32` would make the result negative (PHP ints are signed) on
     * roughly half of all inputs, corrupting storage into an unsigned
     * bigint column. 62 bits of combined hash space is still effectively
     * collision-free for this corpus size.
     */
    public function hash(string $shingle): int
    {
        $forward = crc32($shingle) & 0x7FFFFFFF;
        $backward = crc32(strrev($shingle)) & 0x7FFFFFFF;

        return ($forward << 31) | $backward;
    }

    /**
     * Replace this document's fingerprints (idempotent — safe to re-run on
     * reanalysis) and return how many shingles were stored.
     */
    public function fingerprint(IntegrityDocument $document): int
    {
        $words = $this->words((string) $document->extracted_text);
        $shingles = $this->shingles($words);

        CorpusFingerprint::where('integrity_document_id', $document->id)->delete();

        $now = now();
        $rows = [];
        foreach ($shingles as $position => $shingle) {
            $rows[] = [
                'integrity_document_id' => $document->id,
                'shingle_hash' => $this->hash($shingle),
                'position' => $position,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            CorpusFingerprint::insert($chunk);
        }

        return count($rows);
    }
}
