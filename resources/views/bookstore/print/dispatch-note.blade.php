{{-- Property handover note — "ንብረት መረካከቢያ ፎርም". Deliberately laid out like the
     paper form the store already uses, so a coordinator recognises it on sight. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Waybill {{ $dispatch->dispatch_number }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111; }
        .head { width: 100%; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 12px; }
        .head td { vertical-align: top; }
        .org { font-size: 15px; font-weight: bold; }
        .org small { display: block; font-weight: normal; font-size: 10px; color: #444; }
        .doc-title { text-align: center; font-size: 13px; font-weight: bold; margin: 10px 0 14px; }
        .meta { width: 100%; margin-bottom: 12px; }
        .meta td { padding: 2px 0; }
        .meta .label { color: #555; width: 26%; }
        .meta .value { font-weight: bold; border-bottom: 1px dotted #999; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.items th, table.items td { border: 1px solid #333; padding: 5px 6px; }
        table.items th { background: #f0f0f0; font-size: 10px; text-align: left; }
        table.items td.num, table.items th.num { text-align: right; }
        .totals td { font-weight: bold; background: #f7f7f7; }
        .qr { text-align: center; }
        .qr img { width: 90px; height: 90px; }
        .qr .caption { font-size: 9px; margin-top: 3px; }
        .signatures { width: 100%; margin-top: 26px; }
        .signatures td { width: 50%; padding-top: 6px; font-size: 10px; }
        .sig-line { border-bottom: 1px solid #333; height: 26px; margin: 4px 30px 3px 0; }
        .note { margin-top: 16px; font-size: 9px; color: #555; }
    </style>
</head>
<body>

<table class="head">
    <tr>
        <td>
            <div class="org">
                Shiloh International Theological Seminary
                <small>ሴሎ ኢንተርናሽናል ቲዎሎጂካል ሴሚናሪ · Hawassa, Ethiopia</small>
            </div>
        </td>
        <td class="qr" style="width: 110px;">
            <img src="{{ $qr['qr'] }}" alt="Waybill QR">
            <div class="caption">{{ $qr['name'] }}</div>
        </td>
    </tr>
</table>

<div class="doc-title">ንብረት መረካከቢያ ፎርም — Property Handover / Dispatch Note</div>

<table class="meta">
    <tr>
        <td class="label">Waybill No. / ቁጥር</td>
        <td class="value">{{ $dispatch->dispatch_number }}</td>
        <td class="label">Date / ቀን</td>
        <td class="value">{{ optional($dispatch->dispatched_at)->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="label">Request No.</td>
        <td class="value">{{ $dispatch->bookRequest?->request_number }}</td>
        <td class="label">Destination / ማእከል</td>
        <td class="value">{{ $dispatch->bookRequest?->destination_name }}</td>
    </tr>
    <tr>
        <td class="label">Receiver / ተረካቢ</td>
        <td class="value">{{ $dispatch->received_by_name ?: '—' }}</td>
        <td class="label">Phone / ሞባይል</td>
        <td class="value">{{ $dispatch->received_by_phone ?: '—' }}</td>
    </tr>
</table>

<table class="items">
    <thead>
    <tr>
        <th style="width: 6%;">ተ.ቁ</th>
        <th style="width: 14%;">Code / ኮድ</th>
        <th>Book / Course — የመጽሃፍ ስም</th>
        <th style="width: 20%;">Shelf location</th>
        <th class="num" style="width: 10%;">Qty / ብዛት</th>
        <th class="num" style="width: 13%;">Unit price</th>
        <th class="num" style="width: 14%;">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($dispatch->items as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->bookTitle?->code }}</td>
            <td>{{ $item->bookTitle?->title }}</td>
            <td>{{ $item->shelfSection?->path }}</td>
            <td class="num">{{ $item->quantity }}</td>
            <td class="num">{{ number_format((float) $item->unit_price, 2) }}</td>
            <td class="num">{{ number_format((float) $item->line_total, 2) }}</td>
        </tr>
    @endforeach
    <tr class="totals">
        <td colspan="4">ድምር — Total</td>
        <td class="num">{{ $dispatch->total_quantity }}</td>
        <td></td>
        <td class="num">{{ number_format((float) $dispatch->total_amount, 2) }}</td>
    </tr>
    </tbody>
</table>

@if ($dispatch->notes)
    <div class="note"><strong>ማስታወሻ / Remark:</strong> {{ $dispatch->notes }}</div>
@endif

<table class="signatures">
    <tr>
        <td>
            <strong>ያስረከበ — Handed over by</strong>
            <div class="sig-line"></div>
            {{ $dispatch->dispatchedBy?->name }} (Store) · Signature &amp; Date
        </td>
        <td>
            <strong>ተረካቢ — Received by</strong>
            <div class="sig-line"></div>
            {{ $dispatch->received_by_name ?: '……………………………' }} · Signature &amp; Date
        </td>
    </tr>
</table>

<div class="note">
    Scan the QR above to confirm receipt online — it records the confirmation against
    waybill {{ $dispatch->dispatch_number }} without a phone call.
</div>

</body>
</html>
