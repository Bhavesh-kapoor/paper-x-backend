<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1F2937; padding: 36px; }
  .header { width: 100%; border-bottom: 3px solid #1D4ED8; padding-bottom: 16px; margin-bottom: 24px; }
  .brand { font-size: 26px; font-weight: bold; color: #1D4ED8; }
  .company-details { font-size: 10px; color: #6B7280; margin-top: 6px; line-height: 1.5; }
  .invoice-meta { text-align: right; }
  .invoice-title { font-size: 20px; font-weight: bold; color: #111827; }
  .paid-badge { display: inline-block; background: #DCFCE7; color: #15803D; font-weight: bold; font-size: 10px; padding: 3px 10px; border-radius: 10px; margin-top: 6px; letter-spacing: 1px; }
  .meta-line { font-size: 11px; color: #6B7280; margin-top: 4px; }
  table.layout { width: 100%; border-collapse: collapse; }
  .section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #9CA3AF; font-weight: bold; margin-bottom: 6px; }
  .bill-box { margin-bottom: 24px; }
  .bill-name { font-size: 13px; font-weight: bold; color: #111827; }
  .bill-line { font-size: 11px; color: #4B5563; line-height: 1.6; }
  table.items { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
  table.items th { background: #F3F4F6; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #6B7280; padding: 9px 12px; }
  table.items th.right, table.items td.right { text-align: right; }
  table.items td { padding: 11px 12px; border-bottom: 1px solid #E5E7EB; font-size: 12px; }
  table.totals { width: 46%; margin-left: 54%; border-collapse: collapse; margin-top: 8px; }
  table.totals td { padding: 6px 12px; font-size: 12px; }
  table.totals td.label { color: #6B7280; }
  table.totals td.value { text-align: right; color: #111827; }
  table.totals tr.grand td { border-top: 2px solid #1D4ED8; font-weight: bold; font-size: 14px; padding-top: 10px; }
  .payment-box { margin-top: 28px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; padding: 14px 16px; }
  .payment-line { font-size: 11px; color: #4B5563; line-height: 1.8; }
  .payment-line span { color: #111827; font-weight: bold; }
  .footer { margin-top: 40px; border-top: 1px solid #E5E7EB; padding-top: 12px; font-size: 9px; color: #9CA3AF; text-align: center; line-height: 1.6; }
</style>
</head>
<body>

  <table class="layout header">
    <tr>
      <td>
        <div class="brand">{{ $invoice['seller']['name'] }}</div>
        <div class="company-details">
          {{ $invoice['seller']['legal_name'] }}<br>
          {{ $invoice['seller']['address'] }}<br>
          @if(!empty($invoice['seller']['gstin'])) GSTIN: {{ $invoice['seller']['gstin'] }}<br> @endif
          {{ $invoice['seller']['email'] }} @if(!empty($invoice['seller']['phone'])) · {{ $invoice['seller']['phone'] }} @endif
        </div>
      </td>
      <td class="invoice-meta">
        <div class="invoice-title">TAX INVOICE</div>
        <div class="paid-badge">PAID</div>
        <div class="meta-line">Invoice No: <strong>{{ $invoice['invoice_no'] }}</strong></div>
        <div class="meta-line">Date: {{ $invoice['paid_at'] ? \Carbon\Carbon::parse($invoice['paid_at'])->format('d M Y, h:i A') : '—' }}</div>
      </td>
    </tr>
  </table>

  <div class="bill-box">
    <div class="section-title">Billed To</div>
    <div class="bill-name">{{ $invoice['bill_to']['company_name'] ?: $invoice['bill_to']['name'] }}</div>
    @if($invoice['bill_to']['company_name'] && $invoice['bill_to']['name'])
      <div class="bill-line">{{ $invoice['bill_to']['name'] }}</div>
    @endif
    @if($invoice['bill_to']['city'] || $invoice['bill_to']['state'])
      <div class="bill-line">{{ collect([$invoice['bill_to']['city'], $invoice['bill_to']['state']])->filter()->implode(', ') }}</div>
    @endif
    @if(!empty($invoice['bill_to']['gstin']))
      <div class="bill-line">GSTIN: {{ $invoice['bill_to']['gstin'] }}</div>
    @endif
  </div>

  <table class="items">
    <tr>
      <th style="width: 55%;">Description</th>
      <th class="right">Credits</th>
      <th class="right">Amount (₹)</th>
    </tr>
    <tr>
      <td>{{ $invoice['title'] }}</td>
      <td class="right">{{ $invoice['credits'] !== null ? number_format($invoice['credits']) : '—' }}</td>
      <td class="right">{{ number_format($invoice['base_amount_inr'], 2) }}</td>
    </tr>
  </table>

  <table class="totals">
    <tr>
      <td class="label">Subtotal</td>
      <td class="value">₹{{ number_format($invoice['base_amount_inr'], 2) }}</td>
    </tr>
    @if($invoice['gst_amount_inr'] > 0)
    <tr>
      <td class="label">GST ({{ rtrim(rtrim(number_format($invoice['gst_percent'], 2), '0'), '.') }}%)</td>
      <td class="value">₹{{ number_format($invoice['gst_amount_inr'], 2) }}</td>
    </tr>
    @endif
    <tr class="grand">
      <td class="label">Total Paid</td>
      <td class="value">₹{{ number_format($invoice['total_inr'], 2) }}</td>
    </tr>
  </table>

  <div class="payment-box">
    <div class="section-title">Payment Details</div>
    <div class="payment-line">Payment Method: <span>Razorpay</span></div>
    @if(!empty($invoice['razorpay_payment_id']))
      <div class="payment-line">Payment ID: <span>{{ $invoice['razorpay_payment_id'] }}</span></div>
    @endif
    <div class="payment-line">Receipt: <span>{{ $invoice['receipt'] }}</span></div>
  </div>

  <div class="footer">
    This is a computer-generated invoice and does not require a signature.<br>
    For any queries, contact {{ $invoice['seller']['email'] }}.
  </div>

</body>
</html>
