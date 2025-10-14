<?php
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/BillingController.php';
require_once __DIR__ . '/../controllers/SystemController.php';

$billId = $_GET['id'] ?? null;
if (!$billId) {
    http_response_code(400);
    echo "<h3>Bill ID is required</h3>";
    exit;
}

$controller = new BillingController();
$bill = $controller->getById($billId);
if (!$bill) {
    http_response_code(404);
    echo "<h3>Billing record not found</h3>";
    exit;
}

// Simple helper to format currency
function fmt($v) {
    return number_format((float)$v, 2);
}

$systemController = new SystemController();
$system = $systemController->getSystemDetails();
$logoPath = '';
if (!empty($system['logo'])) {
  $logoPath = '/uploads/company/' . $system['logo'];
}

?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Invoice - <?= htmlspecialchars($bill['bill_id']) ?></title>
  <style>
    :root{ --accent:#0d6efd; --muted:#69707a; --paper:#fff; }
    html,body{height:100%;}
    body { font-family: Inter, 'Segoe UI', Roboto, -apple-system, 'Helvetica Neue', Arial; color: #222; margin: 0; background: #f4f6f9; padding: 28px; }
    .invoice { max-width: 820px; margin: 0 auto; background: var(--paper); border-radius:8px; box-shadow:0 6px 18px rgba(15,23,42,0.08); padding: 28px; }
    .header { display:flex; justify-content:space-between; align-items:center; gap:16px; }
    .company { font-size: 22px; font-weight: 800; color: #0b4ed9; }
    .muted { color:var(--muted); }
    .brand { display:flex; align-items:center; gap:12px; }
    .logo { width:72px; height:72px; object-fit:contain; border-radius:6px; background:#fff; padding:6px; box-shadow:0 2px 6px rgba(15,23,42,0.04); }
    table { width:100%; border-collapse: collapse; margin-top: 18px; background:transparent; }
    td, th { padding: 10px 8px; vertical-align: middle; }
    .line-item td { border-bottom: 1px solid #f0f0f0; }
    .text-right { text-align: right; }
    .total-row td { border-top: 2px solid #e6e9ef; font-weight:700; }
    .notes { margin-top: 20px; color: #333; font-size: 0.95rem; }
    .meta { text-align:right; }
    .small { font-size:0.86rem; color:var(--muted); }
    .no-print { margin-top:20px; }
    @media print { body { background:#fff; padding:0; } .no-print{display:none;} .invoice{box-shadow:none;border-radius:0;padding:0;} }
  </style>
</head>
<body>
  <div class="invoice">
    <div class="header">
      <div class="brand">
        <?php if ($logoPath && file_exists(__DIR__ . '/..' . $logoPath)): ?>
          <img src="<?= $logoPath ?>" alt="Logo" class="logo" />
        <?php endif; ?>
        <div>
          <div class="company"><?= htmlspecialchars($system['name'] ?: 'Clinic Management') ?></div>
          <?php if (!empty($system['address']) || !empty($system['phone']) || !empty($system['email'])): ?>
            <div class="small muted"><?php
              $parts = [];
              if (!empty($system['address'])) $parts[] = $system['address'];
              if (!empty($system['phone'])) $parts[] = 'Tel: ' . $system['phone'];
              if (!empty($system['email'])) $parts[] = $system['email'];
              echo htmlspecialchars(implode(' | ', $parts));
            ?></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="text-right meta">
        <div><strong>Bill #</strong> <?= htmlspecialchars($bill['bill_id']) ?></div>
        <div class="muted"><?= htmlspecialchars($bill['bill_date']) ?></div>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;margin-top:12px;">
      <div>
        <strong>Patient</strong><br>
        <?= htmlspecialchars($bill['patient_name']) ?><br>
        <small class="muted"><?= htmlspecialchars(($bill['patient_phone'] ?? '') . (($bill['patient_email'] ?? '') ? ' | ' . $bill['patient_email'] : '')) ?></small>
      </div>
      <div class="text-right">
        <strong>Doctor</strong><br>
        <?= htmlspecialchars($bill['doctor_name'] ?? '') ?><br>
        <small class="muted">Appointment: <?= htmlspecialchars($bill['appointment_date'] ?? 'N/A') ?></small>
      </div>
    </div>

    <table>
      <tbody>
        <tr>
          <td>Consultation</td>
          <td class="text-right">₱<?= fmt($bill['consultation_fee'] ?? 0) ?></td>
        </tr>
        <tr>
          <td>Lab Charges</td>
          <td class="text-right">₱<?= fmt($bill['lab_charges'] ?? 0) ?></td>
        </tr>
        <tr>
          <td>Medication</td>
          <td class="text-right">₱<?= fmt($bill['medication_charges'] ?? 0) ?></td>
        </tr>
        <tr>
          <td>Other Charges</td>
          <td class="text-right">₱<?= fmt($bill['other_charges'] ?? 0) ?></td>
        </tr>
        <tr>
          <td>Discount</td>
          <td class="text-right">₱<?= fmt($bill['discount_amount'] ?? 0) ?></td>
        </tr>
        <tr>
          <td>Tax</td>
          <td class="text-right">₱<?= fmt($bill['tax_amount'] ?? 0) ?></td>
        </tr>
        <tr class="total-row">
          <td>Total</td>
          <td class="text-right">₱<?= fmt($bill['total_amount'] ?? 0) ?></td>
        </tr>
        <tr>
          <td>Paid</td>
          <td class="text-right">₱<?= fmt($bill['paid_amount'] ?? 0) ?></td>
        </tr>
        <tr>
          <td class="total-row">Balance</td>
          <td class="text-right total-row">₱<?= fmt($bill['balance_amount'] ?? 0) ?></td>
        </tr>
      </tbody>
    </table>

    <div class="notes">
      <strong>Notes</strong>
      <div><?= nl2br(htmlspecialchars($bill['notes'] ?? '-')) ?></div>
    </div>

    <div style="margin-top:24px;" class="no-print">
      <button onclick="window.print();" class="btn btn-primary">Print</button>
      <button onclick="window.close();" class="btn">Close</button>
    </div>
  </div>
</body>
</html>
