{{-- Sheet of QR asset-tag stickers. Sized for a 3-across A4 label sheet, which
     is what the store already buys; the grid is plain floats because dompdf's
     flex/grid support is not to be relied on. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 10mm 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        .sheet-head { border-bottom: 1.5px solid #111; padding-bottom: 6px; margin-bottom: 10px; }
        .sheet-head .org { font-size: 13px; font-weight: bold; }
        .sheet-head .sub { font-size: 9px; color: #555; margin-top: 2px; }
        table.labels { width: 100%; border-collapse: separate; border-spacing: 4mm 4mm; }
        table.labels td {
            width: 33.33%; border: 1px dashed #999; padding: 5px 4px;
            text-align: center; vertical-align: top; height: 32mm;
        }
        .qr img { width: 22mm; height: 22mm; }
        .code { font-size: 9.5px; font-weight: bold; letter-spacing: 0.3px; margin-top: 2px; }
        .name { font-size: 8.5px; margin-top: 1px; }
        .sub { font-size: 7.5px; color: #555; }
        .meta { font-size: 7px; color: #777; margin-top: 1px; }
        .empty { border: none !important; }
        .foot { margin-top: 12px; font-size: 7.5px; color: #666; }
    </style>
</head>
<body>

<div class="sheet-head">
    <div class="org">Shiloh International Theological Seminary</div>
    <div class="sub">{{ $title }} · {{ count($labels) }} label{{ count($labels) === 1 ? '' : 's' }} · printed {{ now()->format('d/m/Y H:i') }}</div>
</div>

<table class="labels">
    @foreach (array_chunk($labels, 3) as $row)
        <tr>
            @foreach ($row as $label)
                <td>
                    <div class="qr"><img src="{{ $label['qr'] }}" alt="{{ $label['code'] }}"></div>
                    <div class="code">{{ $label['code'] }}</div>
                    <div class="name">{{ \Illuminate\Support\Str::limit($label['name'], 34) }}</div>
                    @if ($label['sub'])
                        <div class="sub">{{ \Illuminate\Support\Str::limit($label['sub'], 34) }}</div>
                    @endif
                    @if ($label['meta'])
                        <div class="meta">{{ \Illuminate\Support\Str::limit($label['meta'], 40) }}</div>
                    @endif
                </td>
            @endforeach
            {{-- Pad the last row so the dashed cells stay a uniform width. --}}
            @for ($i = count($row); $i < 3; $i++)
                <td class="empty"></td>
            @endfor
        </tr>
    @endforeach
</table>

<div class="foot">
    Property of Shiloh International Theological Seminary. Do not remove this label.
    Scan or type the code into Store → Stocktake to look the item up.
</div>

</body>
</html>
