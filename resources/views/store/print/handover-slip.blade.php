{{-- Asset handover slip — the paper half of a custody record. Signed by both
     parties and scanned back in against the assignment, which is what turns
     "IT says he has it" into something an auditor accepts. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Handover — {{ $assignment->unit->asset_tag }}</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111; }
        .head { width: 100%; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 12px; }
        .head td { vertical-align: top; }
        .org { font-size: 15px; font-weight: bold; }
        .org small { display: block; font-weight: normal; font-size: 10px; color: #444; }
        .doc-title { text-align: center; font-size: 13px; font-weight: bold; margin: 10px 0 14px; }
        .meta { width: 100%; margin-bottom: 12px; }
        .meta td { padding: 3px 0; }
        .meta .label { color: #555; width: 24%; }
        .meta .value { font-weight: bold; border-bottom: 1px dotted #999; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.items th, table.items td { border: 1px solid #333; padding: 6px; }
        table.items th { background: #f0f0f0; font-size: 10px; text-align: left; }
        .qr { text-align: center; }
        .qr img { width: 88px; height: 88px; }
        .qr .caption { font-size: 8.5px; margin-top: 3px; font-weight: bold; }
        .terms { margin-top: 14px; font-size: 9px; color: #333; line-height: 1.5; }
        .terms li { margin-bottom: 2px; }
        .signatures { width: 100%; margin-top: 24px; }
        .signatures td { width: 50%; padding-top: 6px; font-size: 10px; vertical-align: top; }
        .sig-line { border-bottom: 1px solid #333; height: 26px; margin: 4px 30px 3px 0; }
        .note { margin-top: 16px; font-size: 8.5px; color: #555; }
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
        <td class="qr" style="width: 108px;">
            <img src="{{ $label['qr'] }}" alt="{{ $label['code'] }}">
            <div class="caption">{{ $label['code'] }}</div>
        </td>
    </tr>
</table>

<div class="doc-title">
    {{ $assignment->returned_at ? 'ንብረት መመለሻ ቅጽ — Asset Return Note' : 'ንብረት ማስረከቢያ ቅጽ — Asset Handover Note' }}
</div>

<table class="meta">
    <tr>
        <td class="label">Custodian / ተረካቢ</td>
        <td class="value">{{ $assignment->employee?->full_name_en ?? '—' }}</td>
        <td class="label">Staff No.</td>
        <td class="value">{{ $assignment->employee?->staff_no ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Department</td>
        <td class="value">{{ $assignment->employee?->department?->name_en ?? '—' }}</td>
        <td class="label">Issued on / ቀን</td>
        <td class="value">{{ optional($assignment->issued_at)->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="label">Due back</td>
        <td class="value">{{ $assignment->due_at ? $assignment->due_at->format('d/m/Y') : 'Indefinite' }}</td>
        <td class="label">Returned on</td>
        <td class="value">{{ $assignment->returned_at ? $assignment->returned_at->format('d/m/Y') : '—' }}</td>
    </tr>
    <tr>
        <td class="label">Purpose</td>
        <td class="value" colspan="3">{{ $assignment->purpose ?: '—' }}</td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width: 26%;">Asset Tag</th>
            <th>Description</th>
            <th style="width: 20%;">Serial No.</th>
            <th style="width: 14%;">Condition Out</th>
            <th style="width: 14%;">Condition In</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>{{ $assignment->unit->asset_tag }}</strong></td>
            <td>
                {{ $assignment->unit->item?->name_en }}
                @if ($assignment->unit->item?->brand || $assignment->unit->item?->model)
                    <br><span style="font-size: 9px; color: #555;">
                        {{ trim(($assignment->unit->item->brand ?? '').' '.($assignment->unit->item->model ?? '')) }}
                    </span>
                @endif
            </td>
            <td>{{ $assignment->unit->serial_number ?: '—' }}</td>
            <td>{{ $assignment->condition_out?->label() ?? '—' }}</td>
            <td>{{ $assignment->condition_in?->label() ?? '—' }}</td>
        </tr>
    </tbody>
</table>

<div class="terms">
    <strong>Conditions of custody</strong>
    <ol>
        <li>The asset above remains the property of the Seminary at all times.</li>
        <li>The custodian is responsible for its safekeeping and reasonable care.</li>
        <li>Loss, theft or damage must be reported to the Store immediately.</li>
        <li>The asset must be returned on or before the due date, or on separation from the Seminary, whichever is earlier.</li>
        <li>Unreturned assets are recorded against clearance and may be recovered from final settlement.</li>
    </ol>
</div>

<table class="signatures">
    <tr>
        <td>
            <div>Issued by (Store) / አስረካቢ</div>
            <div class="sig-line"></div>
            <div>{{ $assignment->issuedBy?->name ?? '' }}</div>
            <div style="color:#666;">Name &amp; signature · Date</div>
        </td>
        <td>
            <div>Received by (Custodian) / ተረካቢ</div>
            <div class="sig-line"></div>
            <div>{{ $assignment->employee?->full_name_en ?? '' }}</div>
            <div style="color:#666;">Name &amp; signature · Date</div>
        </td>
    </tr>
    @if ($assignment->returned_at)
        <tr>
            <td colspan="2" style="padding-top: 18px;">
                <div>Returned to store, received by / ተመላሽ ተቀባይ</div>
                <div class="sig-line" style="margin-right: 55%;"></div>
                <div>{{ $assignment->receivedBackBy?->name ?? '' }}</div>
            </td>
        </tr>
    @endif
</table>

<div class="note">
    Reference: custody record #{{ $assignment->id }} · asset {{ $assignment->unit->asset_tag }} ·
    generated {{ now()->format('d/m/Y H:i') }} from the SITS Store module.
</div>

</body>
</html>
