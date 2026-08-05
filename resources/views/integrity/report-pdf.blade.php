{{--
    Academic Integrity report — PDF export via DomPDF (A4 portrait).
    Rendered by Integrity\ExportController@pdf.
    Data: $document (with instructor/student/course loaded), $report, $plagiarismReport (nullable).
--}}
@php
    $heatColor = function ($score) {
        $t = max(0, min(1, $score / 100));
        $r = (int) round(245 + (239 - 245) * $t);
        $g = (int) round(158 + (68 - 158) * $t);
        $b = (int) round(11 + (68 - 11) * $t);
        $alpha = round(0.10 + $t * 0.45, 2);

        return "background-color: rgba({$r}, {$g}, {$b}, {$alpha});";
    };

    $text = (string) $document->extracted_text;
    $scores = collect($report->sentence_scores ?? [])->sortBy('start')->values();
    $segments = [];
    $cursor = 0;

    foreach ($scores as $s) {
        if ($s['start'] > $cursor) {
            $segments[] = ['text' => mb_substr($text, $cursor, $s['start'] - $cursor), 'style' => ''];
        }
        $segments[] = ['text' => mb_substr($text, $s['start'], $s['end'] - $s['start']), 'style' => $heatColor($s['score'])];
        $cursor = max($cursor, $s['end']);
    }
    if ($cursor < mb_strlen($text)) {
        $segments[] = ['text' => mb_substr($text, $cursor), 'style' => ''];
    }

    $verdictLabels = [
        'likely_human' => 'Likely Human-Written',
        'mixed' => 'Mixed Signals',
        'likely_ai' => 'Likely AI-Generated',
        'insufficient_text' => 'Insufficient Text',
    ];
    $signalLabels = [
        'burstiness' => 'Sentence Rhythm (Burstiness)',
        'sentence_length_uniformity' => 'Sentence Length Uniformity',
        'type_token_ratio' => 'Vocabulary Diversity',
        'ngram_repetition' => 'Phrase Repetition',
        'transition_density' => 'Generic Transitions',
        'em_dash_rate' => 'Em-dash Usage',
        'paragraph_uniformity' => 'Paragraph Uniformity',
        'sentence_opener_diversity' => 'Sentence-Opener Diversity',
        'personal_voice_markers' => 'Personal Voice',
        'list_structure_density' => 'Structure / List Density',
        'readability_delta' => 'Readability vs. Baseline',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Integrity Report — {{ $document->title }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        @page { margin: 20px 24px; }
        body { margin: 0; color: #111827; font-size: 9px; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        h2 { font-size: 11px; margin: 16px 0 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; }
        .subtitle { font-size: 8px; color: #6b7280; margin: 0 0 10px; }
        .disclaimer {
            border: 1px solid #d97706; background: #fffbeb; color: #78350f;
            padding: 8px 10px; font-size: 8px; line-height: 1.4; margin-bottom: 12px;
        }
        .score-row { margin-bottom: 4px; }
        .score-big { font-size: 26px; font-weight: bold; }
        .badge {
            display: inline-block; border: 0.5px solid #94a3b8; border-radius: 3px;
            padding: 1px 5px; font-size: 7.5px; text-transform: uppercase; font-weight: bold;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.5px solid #cbd5e1; padding: 3px 5px; font-size: 7.5px; text-align: left; }
        thead th { background: #1e293b; color: #fff; }
        .heatmap-box { border: 0.5px solid #cbd5e1; padding: 8px; line-height: 1.6; font-size: 8px; }
        .meta-table td { border: none; padding: 1px 0; }
        .no-border td { border: none; }
    </style>
</head>
<body>
    <h1>Academic Integrity Report</h1>
    <p class="subtitle">
        SITS Seminary &nbsp;·&nbsp; Generated {{ now()->format('d M Y H:i') }} &nbsp;·&nbsp; Engine v{{ $report->engine_version }}
    </p>

    <div class="disclaimer">
        <strong>Disclaimer:</strong> AI-detection scores are probabilistic indicators, not proof. ESL writing is
        more likely to be falsely flagged. Use this report as a starting point for a conversation, not a
        conclusion. No automated action (grade penalty, misconduct flag) may ever be triggered by a score alone.
    </div>

    <table class="meta-table no-border">
        <tr><td style="width:110px;"><strong>Document</strong></td><td>{{ $document->title }}</td></tr>
        <tr><td><strong>Instructor</strong></td><td>{{ $document->instructor?->name ?? '—' }}</td></tr>
        <tr><td><strong>Student</strong></td><td>{{ $document->student?->name ?? 'Not linked' }}</td></tr>
        <tr><td><strong>Course</strong></td><td>{{ $document->course?->title ?? 'Not linked' }}</td></tr>
        <tr><td><strong>Word count</strong></td><td>{{ $document->word_count }}</td></tr>
        <tr><td><strong>Submitted</strong></td><td>{{ $document->created_at->format('d M Y H:i') }}</td></tr>
    </table>

    <h2>AI Detection Score</h2>
    <div class="score-row">
        <span class="score-big">{{ $report->ai_probability ?? '—' }}{{ $report->ai_probability !== null ? '%' : '' }}</span>
        &nbsp;&nbsp;
        <span class="badge">{{ $verdictLabels[$report->verdict_label?->value] ?? $report->verdict_label?->value }}</span>
        &nbsp;
        <span class="badge">{{ strtoupper($report->confidence?->value ?? 'n/a') }} CONFIDENCE</span>
    </div>

    @if (!empty($report->statistical_signals))
        <h2>Statistical Signals</h2>
        <table>
            <thead>
                <tr><th>Signal</th><th style="width:60px;">Value</th><th style="width:60px;">Z-score</th><th style="width:80px;">Direction</th></tr>
            </thead>
            <tbody>
                @foreach ($report->statistical_signals as $key => $signal)
                    <tr>
                        <td>{{ $signalLabels[$key] ?? $key }}</td>
                        <td>{{ $signal['value'] }}</td>
                        <td>{{ $signal['zscore_vs_baseline'] }}</td>
                        <td>{{ str_replace('_', ' ', $signal['direction']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (!empty($segments))
        <h2>Sentence Heatmap</h2>
        <div class="heatmap-box">
            @foreach ($segments as $segment)
                <span style="{{ $segment['style'] }}">{{ $segment['text'] }}</span>
            @endforeach
        </div>
    @endif

    @if ($plagiarismReport)
        <h2>Plagiarism</h2>
        <table class="meta-table no-border">
            <tr><td style="width:150px;"><strong>Corpus similarity</strong></td><td>{{ $plagiarismReport->overall_similarity }}% (against {{ $plagiarismReport->corpus_size }} prior submission(s))</td></tr>
            <tr><td><strong>Web similarity</strong></td><td>{{ $plagiarismReport->web_similarity !== null ? $plagiarismReport->web_similarity . '%' : 'Not checked' }}</td></tr>
        </table>

        @if (!empty($plagiarismReport->matches))
            <table style="margin-top:6px;">
                <thead>
                    <tr><th>Source</th><th style="width:70px;">Type</th><th style="width:70px;">Match</th></tr>
                </thead>
                <tbody>
                    @foreach ($plagiarismReport->matches as $match)
                        <tr>
                            <td>{{ $match['source_type'] === 'web' ? ($match['source_title'] ?? $match['url'] ?? '—') : ($match['matched_title'] ?? '—') }}</td>
                            <td>{{ $match['source_type'] }}</td>
                            <td>{{ isset($match['similarity_pct']) ? $match['similarity_pct'] . '%' : ($match['match_quality'] ?? '—') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <h2>Review Status</h2>
    <table class="meta-table no-border">
        <tr><td style="width:150px;"><strong>Status</strong></td><td>{{ str_replace('_', ' ', ucfirst($report->review_status?->value ?? 'none')) }}</td></tr>
        @if ($report->reviewed_by)
            <tr><td><strong>Reviewed by</strong></td><td>{{ $report->reviewer?->name ?? '—' }}</td></tr>
            <tr><td><strong>Reviewed at</strong></td><td>{{ $report->reviewed_at?->format('d M Y H:i') }}</td></tr>
        @endif
        @if ($report->student_meeting_date)
            <tr><td><strong>Student meeting date</strong></td><td>{{ $report->student_meeting_date->format('d M Y') }}</td></tr>
        @endif
        @if ($report->review_notes)
            <tr><td><strong>Notes</strong></td><td>{{ $report->review_notes }}</td></tr>
        @endif
    </table>
</body>
</html>
