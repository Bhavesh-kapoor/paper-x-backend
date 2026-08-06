@php
    use App\Support\AmountInWords;

    $seller  = config('company');
    $bill    = $invoice['bill_to'] ?? [];

    // Zupply brand logo, embedded as base64 so dompdf renders it without file access.
    $logoPath = public_path('assets/img/zupply-logo.png');
    $logoSrc  = is_file($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $dated   = $invoice['paid_at'] ? \Carbon\Carbon::parse($invoice['paid_at'])->format('d-M-Y') : '—';
    $taxable = (float) ($invoice['base_amount_inr'] ?? 0);
    $gst     = (float) ($invoice['gst_amount_inr'] ?? 0);
    $gstRate = (float) ($invoice['gst_percent'] ?? 0);
    $total   = (float) ($invoice['total_inr'] ?? 0);
    $cgst    = round($gst / 2, 2);
    $sgst    = round($gst - $cgst, 2);
    $halfRate = $gstRate / 2;
    $roundOff = round($total - ($taxable + $gst), 2);

    $buyerName    = $bill['company_name'] ?: ($bill['name'] ?? '');
    $buyerAddr    = collect([$bill['city'] ?? null, $bill['state'] ?? null])->filter()->implode(', ');
    $buyerState   = $bill['state'] ?? '';
    $buyerGstin   = $bill['gstin'] ?? '';

    $qty = $invoice['credits'] !== null ? number_format($invoice['credits']) : '1';
    $n2  = fn ($v) => number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #000; padding: 18px; }
  table { border-collapse: collapse; width: 100%; }
  .outer { border: 1px solid #000; }
  td { vertical-align: top; padding: 3px 5px; }
  .b-r { border-right: 1px solid #000; }
  .b-b { border-bottom: 1px solid #000; }
  .b-t { border-top: 1px solid #000; }
  .title { text-align: center; font-size: 13px; font-weight: bold; padding: 5px; }
  .lbl { color: #333; font-size: 8px; }
  .val { font-weight: bold; }
  .seller-name { font-weight: bold; font-size: 11px; }
  .small { font-size: 8px; line-height: 1.5; }
  .right { text-align: right; }
  .center { text-align: center; }
  .muted { color: #444; }
  .items th { border: 1px solid #000; background: #f2f2f2; font-size: 8px; padding: 4px; text-transform: uppercase; }
  .items td { border-left: 1px solid #000; border-right: 1px solid #000; padding: 4px 5px; }
  .strong { font-weight: bold; }
  .metarow td { font-size: 8px; height: 16px; }
</style>
</head>
<body>
  <div class="title">Tax Invoice</div>

  <table class="outer">
    <!-- Seller + meta grid -->
    <tr>
      <td class="b-r b-b" style="width:52%;">
        @if($logoSrc)<img src="{{ $logoSrc }}" alt="Zupply" style="height:36px; margin-bottom:3px;"><br>@endif
        <div class="seller-name" style="font-size:13px;">{{ $seller['name'] }}</div>
        <div class="small muted" style="margin-bottom:2px;">A unit of {{ $seller['legal_name'] }}</div>
        <div class="small">
          @foreach($seller['address_lines'] as $line){{ $line }}<br>@endforeach
          @if($seller['udyam']){{ $seller['udyam'] }}<br>@endif
          @if($seller['cin'])CIN- {{ $seller['cin'] }}<br>@endif
          @if($seller['gstin'])GSTIN/UIN: {{ $seller['gstin'] }}<br>@endif
          State Name : {{ $seller['state'] }}, Code : {{ $seller['state_code'] }}
        </div>
      </td>
      <td class="b-b" style="width:48%; padding:0;">
        <table>
          <tr class="metarow">
            <td class="b-r b-b" style="width:50%;"><span class="lbl">Invoice No.</span><br><span class="val">{{ $invoice['invoice_no'] }}</span></td>
            <td class="b-b"><span class="lbl">Dated</span><br><span class="val">{{ $dated }}</span></td>
          </tr>
          <tr class="metarow">
            <td class="b-r b-b"><span class="lbl">Reference No. &amp; Date</span><br><span class="val">{{ $invoice['receipt'] ?? '—' }}</span></td>
            <td class="b-b"><span class="lbl">Mode/Terms of Payment</span><br><span class="val">Razorpay (Prepaid)</span></td>
          </tr>
          <tr class="metarow">
            <td class="b-r b-b"><span class="lbl">Buyer's Order No.</span></td>
            <td class="b-b"><span class="lbl">Dated</span></td>
          </tr>
          <tr class="metarow">
            <td class="b-r"><span class="lbl">Dispatched through</span></td>
            <td><span class="lbl">Destination</span></td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Consignee -->
    <tr>
      <td class="b-r b-b" colspan="2" style="padding:0;">
        <table>
          <tr>
            <td class="b-r" style="width:52%;">
              <span class="lbl">Consignee (Ship to)</span><br>
              <span class="val">{{ $buyerName }}</span><br>
              <span class="small">{{ $buyerAddr ?: '—' }}</span><br>
              <span class="small">@if($buyerGstin)GSTIN/UIN : {{ $buyerGstin }}<br>@endif State Name : {{ $buyerState ?: '—' }}</span>
            </td>
            <td>
              <span class="lbl">Buyer (Bill to)</span><br>
              <span class="val">{{ $buyerName }}</span><br>
              <span class="small">{{ $buyerAddr ?: '—' }}</span><br>
              <span class="small">@if($buyerGstin)GSTIN/UIN : {{ $buyerGstin }}<br>@endif State Name : {{ $buyerState ?: '—' }}</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Items -->
  <table class="items" style="border:1px solid #000; border-top:0;">
    <tr>
      <th style="width:5%;">Sl<br>No.</th>
      <th style="width:43%;">Description of Goods</th>
      <th style="width:12%;">HSN/SAC</th>
      <th style="width:10%;" class="right">Quantity</th>
      <th style="width:12%;" class="right">Rate</th>
      <th style="width:5%;">per</th>
      <th style="width:13%;" class="right">Amount</th>
    </tr>
    <tr>
      <td class="center">1</td>
      <td class="strong">{{ $invoice['title'] }}</td>
      <td class="center">{{ $seller['sac_code'] }}</td>
      <td class="right">{{ $qty }}</td>
      <td class="right">{{ $n2($taxable) }}</td>
      <td class="center"></td>
      <td class="right">{{ $n2($taxable) }}</td>
    </tr>
    @if($gst > 0)
    <tr>
      <td></td>
      <td class="right muted">OUTPUT CGST @ {{ rtrim(rtrim(number_format($halfRate,2),'0'),'.') }}%</td>
      <td></td><td></td><td></td>
      <td class="center muted">{{ rtrim(rtrim(number_format($halfRate,2),'0'),'.') }}%</td>
      <td class="right">{{ $n2($cgst) }}</td>
    </tr>
    <tr>
      <td></td>
      <td class="right muted">OUTPUT SGST @ {{ rtrim(rtrim(number_format($halfRate,2),'0'),'.') }}%</td>
      <td></td><td></td><td></td>
      <td class="center muted">{{ rtrim(rtrim(number_format($halfRate,2),'0'),'.') }}%</td>
      <td class="right">{{ $n2($sgst) }}</td>
    </tr>
    @endif
    @if(abs($roundOff) >= 0.01)
    <tr>
      <td></td><td class="right muted">Round Off</td><td></td><td></td><td></td><td></td>
      <td class="right">{{ $n2($roundOff) }}</td>
    </tr>
    @endif
    <tr>
      <td class="b-t"></td>
      <td class="b-t right strong">Total</td>
      <td class="b-t"></td><td class="b-t"></td><td class="b-t"></td><td class="b-t"></td>
      <td class="b-t right strong">₹ {{ $n2($total) }}</td>
    </tr>
  </table>

  <!-- Amount in words -->
  <table class="outer" style="border-top:0;">
    <tr>
      <td class="b-b">
        <span class="lbl">Amount Chargeable (in words)</span>
        <span class="right" style="float:right;">E. &amp; O.E</span><br>
        <span class="val">{{ AmountInWords::inr($total) }}</span>
      </td>
    </tr>
  </table>

  <!-- Tax summary -->
  <table class="items" style="border:1px solid #000; border-top:0;">
    <tr>
      <th rowspan="2" style="width:34%;">Taxable<br>Value</th>
      <th colspan="2">CGST</th>
      <th colspan="2">SGST/UTGST</th>
      <th rowspan="2" class="right" style="width:18%;">Total<br>Tax Amount</th>
    </tr>
    <tr>
      <th class="center">Rate</th><th class="right">Amount</th>
      <th class="center">Rate</th><th class="right">Amount</th>
    </tr>
    <tr>
      <td class="right">{{ $n2($taxable) }}</td>
      <td class="center">{{ rtrim(rtrim(number_format($halfRate,2),'0'),'.') }}%</td>
      <td class="right">{{ $n2($cgst) }}</td>
      <td class="center">{{ rtrim(rtrim(number_format($halfRate,2),'0'),'.') }}%</td>
      <td class="right">{{ $n2($sgst) }}</td>
      <td class="right">{{ $n2($gst) }}</td>
    </tr>
    <tr>
      <td class="right strong b-t">{{ $n2($taxable) }}</td>
      <td class="b-t"></td>
      <td class="right strong b-t">{{ $n2($cgst) }}</td>
      <td class="b-t"></td>
      <td class="right strong b-t">{{ $n2($sgst) }}</td>
      <td class="right strong b-t">{{ $n2($gst) }}</td>
    </tr>
  </table>

  <table class="outer" style="border-top:0;">
    <tr>
      <td class="b-b"><span class="lbl">Tax Amount (in words) :</span> <span class="val">{{ AmountInWords::inr($gst) }}</span></td>
    </tr>
    <tr>
      <td class="b-b" style="padding:0;">
        <table>
          <tr>
            <td class="b-r" style="width:60%;">
              <span class="lbl">Company's PAN :</span> <span class="val">{{ $seller['pan'] }}</span>
              <div class="small" style="margin-top:6px;">
                <span class="strong">Declaration</span><br>
                We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.
              </div>
            </td>
            <td>
              <span class="lbl">Company's Bank Details</span>
              <div class="small">
                Bank Name : {{ $seller['bank']['name'] ?: '—' }}<br>
                A/c No. : {{ $seller['bank']['account'] ?: '—' }}<br>
                Branch &amp; IFS Code : {{ $seller['bank']['branch_ifsc'] ?: '—' }}
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="padding:0;">
        <table>
          <tr>
            <td class="b-r" style="width:60%; height:52px;">
              <span class="small">Customer's Seal and Signature</span>
            </td>
            <td class="right">
              <span class="small">for {{ $seller['legal_name'] }}</span>
              <div class="small" style="margin-top:34px;">Authorised Signatory</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <div class="center small" style="margin-top:6px;">This is a Computer Generated Invoice</div>
</body>
</html>
