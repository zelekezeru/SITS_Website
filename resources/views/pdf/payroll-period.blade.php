{{--
    Payroll period sheet — PDF replica of the on-screen summary / Excel export.
    Rendered by Finance\PayrollController@exportPdf via DomPDF (A4 landscape).
    Data: $period, $rows, $totals, $columns (all from App\Support\PayrollSheetPresenter).
--}}
@php
    /** Money cell: 2dp with thousands separator; a dash for zero to keep the wide sheet readable. */
    $money = fn ($v) => (float) $v == 0.0 ? '–' : number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payroll — {{ $period->name }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        @page { margin: 14px 12px; }
        body { margin: 0; color: #111827; }
        .title { font-size: 13px; font-weight: bold; margin: 0 0 2px; }
        .subtitle { font-size: 8px; color: #6b7280; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 0.5px solid #cbd5e1; padding: 2px 3px; font-size: 6.2px; overflow: hidden; }
        thead th {
            background: #1e3a8a; color: #fff; font-weight: bold;
            text-align: center; vertical-align: middle;
        }
        tbody td { vertical-align: middle; }
        .idx { text-align: center; width: 16px; }
        .name { text-align: left; width: 88px; white-space: normal; }
        .grade, .campus { text-align: left; width: 34px; }
        .days { text-align: center; width: 24px; }
        .num { text-align: right; white-space: nowrap; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        tfoot td {
            background: #e2e8f0; font-weight: bold; border-top: 1px solid #1e3a8a;
        }
        tfoot .lbl { text-align: right; }
    </style>
</head>
<body>
    <p class="title">SITS Seminary — Payroll: {{ $period->name }}</p>
    <p class="subtitle">
        {{ count($rows) }} employee{{ count($rows) === 1 ? '' : 's' }}
        &nbsp;·&nbsp; Generated {{ now()->format('d M Y H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th class="idx">#</th>
                <th class="name">Employee</th>
                <th class="grade">Grade</th>
                <th class="campus">Campus</th>
                <th class="days">Days</th>
                @foreach ($columns as $key => $label)
                    <th class="num">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="idx">{{ $row['no'] }}</td>
                    <td class="name">{{ $row['name'] }}</td>
                    <td class="grade">{{ $row['grade'] ?: '—' }}</td>
                    <td class="campus">{{ $row['campus'] ?: '—' }}</td>
                    <td class="days">{{ rtrim(rtrim(number_format((float) $row['working_days'], 1), '0'), '.') }}</td>
                    @foreach ($columns as $key => $label)
                        <td class="num">{{ $money($row[$key] ?? 0) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="idx" colspan="{{ 5 + count($columns) }}" style="text-align:center; padding:12px;">
                        No payslips for this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if (count($rows))
            <tfoot>
                <tr>
                    <td class="lbl" colspan="5">TOTAL ({{ count($rows) }})</td>
                    @foreach ($columns as $key => $label)
                        <td class="num">{{ $money($totals[$key] ?? 0) }}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
