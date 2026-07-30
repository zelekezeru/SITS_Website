{{--
    Individual payslip — PDF.
    Rendered by Admin\FinanceCrudController@payslipPdf and Portal\EmployeeController
    via DomPDF (A4 portrait). Data: $payslip with employee, payrollPeriod and lines.

    Earnings and deductions come from the payslip lines, so whatever the run
    itemised is what prints — including "Unpaid Absence (N day(s))". The absence
    row is called out separately below the totals because it is withheld from
    taxed pay, which is the part staff most often query.
--}}
@php
    $money = fn ($v) => number_format((float) $v, 2);

    $earnings = $payslip->lines->where('type', 'earning');
    $deductions = $payslip->lines->where('type', 'deduction');

    $absenceDeduction = (float) ($payslip->absence_deduction ?? 0);
    $absentDays = $payslip->absent_days;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payslip — {{ $payslip->employee?->full_name_en }} — {{ $payslip->payrollPeriod?->name }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        @page { margin: 28px 30px; }
        body { margin: 0; color: #111827; font-size: 10px; }

        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 14px; }
        .org { font-size: 15px; font-weight: bold; margin: 0; color: #1e3a8a; }
        .doc { font-size: 10px; color: #6b7280; margin: 2px 0 0; }

        .meta { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .meta td { padding: 3px 0; font-size: 9.5px; vertical-align: top; }
        .meta .k { color: #6b7280; width: 88px; }
        .meta .v { font-weight: bold; }

        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.grid th, table.grid td { border: 0.5px solid #cbd5e1; padding: 4px 6px; font-size: 9.5px; }
        table.grid thead th { background: #1e3a8a; color: #fff; text-align: left; font-size: 9px; }
        table.grid td.num { text-align: right; white-space: nowrap; }
        table.grid tbody tr:nth-child(even) td { background: #f8fafc; }
        table.grid tfoot td { background: #e2e8f0; font-weight: bold; }

        .cols { width: 100%; border-collapse: separate; border-spacing: 10px 0; }
        .cols > tbody > tr > td { vertical-align: top; width: 50%; }

        .net {
            margin-top: 6px; border: 1px solid #1e3a8a; background: #eff6ff;
            padding: 8px 10px;
        }
        .net .lbl { font-size: 10px; color: #1e3a8a; font-weight: bold; }
        .net .amt { font-size: 15px; font-weight: bold; text-align: right; }

        .note {
            margin-top: 10px; border-left: 3px solid #b91c1c; background: #fef2f2;
            padding: 6px 9px; font-size: 9px; color: #7f1d1d;
        }
        .foot { margin-top: 20px; font-size: 8px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <p class="org">SITS Seminary</p>
        <p class="doc">Payslip — {{ $payslip->payrollPeriod?->name }}</p>
    </div>

    <table class="meta">
        <tr>
            <td class="k">Employee</td>
            <td class="v">{{ $payslip->employee?->full_name_en ?? '—' }}</td>
            <td class="k">Staff No.</td>
            <td class="v">{{ $payslip->employee?->staff_no ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Position</td>
            <td class="v">{{ $payslip->employee?->position?->title_en ?? '—' }}</td>
            <td class="k">Department</td>
            <td class="v">{{ $payslip->employee?->department?->name_en ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Grade</td>
            <td class="v">{{ $payslip->grade ?: '—' }}</td>
            <td class="k">Working days</td>
            <td class="v">
                {{ (float) $payslip->working_days }}
                @if ($absentDays)
                    <span style="color:#b91c1c;">({{ $absentDays }} absent)</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="cols">
        <tbody>
        <tr>
            <td>
                <table class="grid">
                    <thead><tr><th>Earnings</th><th style="text-align:right;">ETB</th></tr></thead>
                    <tbody>
                        @forelse ($earnings as $line)
                            <tr>
                                <td>{{ $line->label }}</td>
                                <td class="num">{{ $money($line->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" style="color:#9ca3af;">No earnings recorded.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Gross</td>
                            <td class="num">{{ $money($payslip->gross) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
            <td>
                <table class="grid">
                    <thead><tr><th>Deductions</th><th style="text-align:right;">ETB</th></tr></thead>
                    <tbody>
                        @forelse ($deductions as $line)
                            <tr>
                                <td>{{ $line->label }}</td>
                                <td class="num">{{ $money($line->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" style="color:#9ca3af;">No deductions recorded.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total deductions</td>
                            <td class="num">{{ $money($payslip->total_deductions) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </td>
        </tr>
        </tbody>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th>Tax &amp; statutory summary</th>
                <th style="text-align:right;">ETB</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Taxable income</td><td class="num">{{ $money($payslip->taxable_income) }}</td></tr>
            <tr><td>Personal income tax (PIT)</td><td class="num">{{ $money($payslip->income_tax) }}</td></tr>
            @if ((float) $payslip->employee_pension)
                <tr><td>Pension — employee</td><td class="num">{{ $money($payslip->employee_pension) }}</td></tr>
            @endif
            @if ((float) $payslip->employer_pension)
                <tr><td>Pension — employer</td><td class="num">{{ $money($payslip->employer_pension) }}</td></tr>
            @endif
            @if ((float) $payslip->provident_fund_employee)
                <tr><td>Provident fund — employee</td><td class="num">{{ $money($payslip->provident_fund_employee) }}</td></tr>
            @endif
            @if ((float) $payslip->provident_fund_employer)
                <tr><td>Provident fund — employer</td><td class="num">{{ $money($payslip->provident_fund_employer) }}</td></tr>
            @endif
            @if ($absenceDeduction > 0)
                <tr>
                    <td style="color:#b91c1c;">Unpaid absence ({{ $absentDays }} day(s)) — withheld after tax</td>
                    <td class="num" style="color:#b91c1c;">{{ $money($absenceDeduction) }}</td>
                </tr>
            @endif
            @if ((float) $payslip->loan_deduction)
                <tr><td>Loan repayment</td><td class="num">{{ $money($payslip->loan_deduction) }}</td></tr>
            @endif
            @if ((float) $payslip->medical_allowance)
                <tr>
                    <td style="color:#047857;">Medical allowance reimbursement (non-taxable)</td>
                    <td class="num" style="color:#047857;">+{{ $money($payslip->medical_allowance) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="net">
        <tr>
            <td class="lbl">NET PAY</td>
            <td class="amt">{{ $money($payslip->net_pay) }} ETB</td>
        </tr>
    </table>

    @if ($absenceDeduction > 0)
        <div class="note">
            <strong>Unpaid absence:</strong> {{ $absentDays }} unexcused day(s) at
            {{ $money($absenceDeduction / max($absentDays, 1)) }} per day.
            Income tax was calculated on your full earnings before this was withheld, so the
            deduction is taken from taxed pay. Approved attendance permissions for this period
            have already been excluded.
        </div>
    @endif

    <p class="foot">
        Generated {{ now()->format('d M Y H:i') }} · Status: {{ ucfirst($payslip->status?->value ?? 'draft') }}
        · This is a computer-generated document.
    </p>
</body>
</html>
