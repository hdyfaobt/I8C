<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Factuur {{ $order->invoiceNumber() }}</title>
    {{-- Standalone page (no app layout / navigation) — this is meant to be
         printed or saved as a PDF, so the site's nav/sidebar would only be
         noise here. --}}
    <style>
        /* border-box everywhere so padding never pushes elements wider than
           their declared width — the main cause of things drifting/looking
           misaligned once printed. */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
            max-width: 1000px;
            width: 100%;
            margin: 40px auto;
            padding: 0 1cm;
        }
        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            border-bottom: 3px solid #1f2937;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 22px;
            margin: 0 0 4px 0;
        }
        .parties {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 32px;
            margin-bottom: 24px;
        }
        .parties > div {
            flex: 1 1 200px;
            min-width: 200px;
        }
        .parties h2 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            margin: 0 0 6px 0;
        }
        .meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 8px 16px;
            font-size: 13px;
            background: #f9fafb;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 14px;
        }
        th, td {
            text-align: left;
            padding: 8px 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
        }
        .text-right { text-align: right; }
        .totals {
            width: 260px;
            margin-left: auto;
            font-size: 14px;
        }
        .totals div {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }
        .totals .grand-total {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #1f2937;
            padding-top: 8px;
            margin-top: 4px;
        }
        .payment-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 16px;
            margin-top: 24px;
            font-size: 14px;
        }
        .payment-box div { margin-bottom: 6px; }
        .payment-box strong { display: inline-block; min-width: 170px; }
        .paid-stamp {
            display: inline-block;
            margin-top: 12px;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 13px;
        }
        .paid-stamp.paid {
            background: #d1fae5;
            color: #065f46;
        }
        .paid-stamp.unpaid {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Print button — visible on screen only, never on the printed page.
           display:inline-flex + a fixed icon font-size keeps the printer
           icon and the text on one single line — without this, the emoji
           was rendering oversized (like a little logo) and wrapping onto
           its own line above the text instead of sitting next to it. */
        .print-actions {
            margin-bottom: 24px;
        }
        .print-actions button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            padding: 10px 20px;
            background: #4338ca;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            line-height: 1;
            cursor: pointer;
        }
        .print-actions button .icon {
            font-size: 16px;
            line-height: 1;
        }
        @media print {
            .print-actions { display: none; }
            /* NOTE: "margin: 0 auto" here, not just "margin: 0" — a plain
               "0" wipes out the horizontal auto-centering too, which is
               what made the printed page look shifted/off to one side.
               Keeping "auto" on the sides preserves centering while still
               dropping the extra top/bottom whitespace for print. */
            body { margin: 0 auto; }
        }
    </style>
</head>
<body>

    {{-- Screen-only print trigger — icon and text in one clean, aligned button --}}
    <div class="print-actions">
        <button onclick="window.print()">
            <span class="icon">🖨</span>
            <span>Print / opslaan als PDF</span>
        </button>
    </div>

    <div class="header">
        <div>
            <h1>FACTUUR</h1>
            <div>{{ $order->invoiceNumber() }}</div>
        </div>
        <div class="paid-stamp {{ $order->paid ? 'paid' : 'unpaid' }}">
            {{ $order->paid ? '✓ BETAALD' : 'NOG NIET BETAALD' }}
        </div>
    </div>

    <div class="parties">
        {{-- Seller — this is a fictional demo company, feel free to adjust
             name/address/BTW number in this view to match a real business. --}}
        <div>
            <h2>Verkoper</h2>
            <div><strong>{{ config('app.name') }}</strong></div>
            <div>Fictiestraat 1</div>
            <div>1000 Brussel, België</div>
            <div>BTW: BE 0123.456.789</div>
        </div>
        {{-- Buyer — pulled straight from the customer record on the order --}}
        <div>
            <h2>Koper</h2>
            <div><strong>{{ $order->customer->name }}</strong></div>
            <div>Klantnummer: {{ $order->customer->customerNumber() }}</div>
            @if ($order->customer->company)
                <div>{{ $order->customer->company }}</div>
            @endif
            @if ($order->customer->address)
                <div>{{ $order->customer->address }}</div>
            @endif
            @if ($order->customer->email)
                <div>{{ $order->customer->email }}</div>
            @endif
        </div>
    </div>

    <div class="meta">
        <div><strong>Factuurdatum:</strong> {{ $order->created_at->format('d/m/Y') }}</div>
        <div><strong>Bestelling:</strong> #{{ $order->id }}</div>
        <div><strong>Aantal artikelen:</strong> {{ $order->totalArticleCount() }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-right">Aantal</th>
                <th class="text-right">Prijs</th>
                <th class="text-right">Subtotaal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="text-right">€ {{ number_format($item->lineTotal(), 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="grand-total">
            <span>Totaal te betalen</span>
            <span>€ {{ number_format($order->totalPrice(), 2, ',', '.') }}</span>
        </div>
    </div>

    <div class="payment-box">
        @if ($order->paid)
            <div><strong>Betaalmethode:</strong> {{ $order->payment_method === 'cash' ? 'Cash' : 'Overschrijving/Bancontact' }}</div>
        @endif
        <div><strong>Rekeningnummer (IBAN):</strong> BE35 3631 4989 3837</div>
        <div><strong>Betalingsreferentie:</strong> {{ $order->paymentReference() }}</div>
        <div style="color:#6b7280; font-size: 12px;">
            Gelieve steeds de betalingsreferentie te vermelden bij uw overschrijving.
        </div>
    </div>

</body>
</html>
