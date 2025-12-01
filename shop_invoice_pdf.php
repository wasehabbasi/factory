<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require('fpdf/fpdf.php');
include "db/db.php";

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die("Invalid invoice id");
}

// Fetch invoice + shop info
$stmt = $conn->prepare("
    SELECT si.*, sh.name AS shop_name, sh.address AS shop_address, sh.phone_number AS shop_phone_number
    FROM shop_invoices si
    LEFT JOIN shops sh ON si.shop_id = sh.id
    WHERE si.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) die("Invoice not found");

// print_r($invoice['invoice_no']);exit;

// Fetch all invoice items
$stmt = $conn->prepare("
    SELECT ss.qty, ss.total_suits, ss.cutting, ss.rate, ss.total, p.name as product_name
    FROM shop_sales ss
    LEFT JOIN products p ON ss.product_id = p.id
    WHERE invoice_no = ?
");
$stmt->bind_param("s", $invoice['invoice_no']);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();

// Company & Shop Info
$companyName = "Usman Arshad Cloth Center";
$companyContact = "Phone:" . $invoice['shop_phone_number'];
$logo_path = "./uploads/UA-Logo.png";

$shopName = $invoice['shop_name'] ?? '';
$shopAddress = $invoice['shop_address'] ?? '';

// PDF Class
class PDF_A5 extends FPDF {
    private $logo;
    function __construct($orientation='P', $unit='mm', $size='A5', $logoPath=null) {
        parent::__construct($orientation, $unit, $size);
        $this->logo = $logoPath;
    }
}

// ✅ Create PDF
$pdf = new PDF_A5('P', 'mm', 'A5', $logo_path);
$pdf->AddPage();

// --- Header Section ---
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 10, 8, 18);
}
$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 6, $companyName, 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, $companyContact, 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'SHOP INVOICE', 0, 1, 'C');
$pdf->Ln(4);

// --- Invoice Info ---
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(30, 6, 'Invoice No:', 0, 0);
$pdf->Cell(0, 6, $invoice['invoice_no'], 0, 1);
$pdf->Cell(30, 6, 'Date:', 0, 0);
$pdf->Cell(0, 6, $invoice['date'], 0, 1);
$pdf->Cell(30, 6, 'Customer:', 0, 0);
$pdf->Cell(0, 6, $invoice['customer_name'], 0, 1);
$pdf->Cell(30, 6, 'Paandi:', 0, 0);
$pdf->Cell(0, 6, $invoice['paandi_name'], 0, 1);
$pdf->Ln(5);

// --- Table Header ---
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(10, 8, '#', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Product Name', 1, 0, 'C', true);
$pdf->Cell(15, 8, 'Cutting', 1, 0, 'C', true);
$pdf->Cell(15, 8, 'Qty', 1, 0, 'C', true);
// $pdf->Cell(20, 8, 'Rate', 1, 0, 'C', true);
$pdf->Cell(20, 8, 'Total Suit', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Total', 1, 1, 'C', true);

// --- Table Rows ---
$pdf->SetFont('Arial', '', 9);
$counter = 1;
while ($row = $items->fetch_assoc()) {
    $pdf->Cell(10, 7, $counter++, 1, 0, 'C');
    $pdf->Cell(40, 7, $row['product_name'], 1, 0, 'C');
    $pdf->Cell(15, 7, number_format($row['cutting'], 2), 1, 0, 'C'); // 🟢 Cutting column
    $pdf->Cell(15, 7, number_format($row['qty'], 2), 1, 0, 'C');
    // $pdf->Cell(20, 7, number_format($row['rate'], 2), 1, 0, 'C');
    $pdf->Cell(20, 7, number_format($row['total_suits'], 2), 1, 0, 'C');
    $pdf->Cell(25, 7, number_format($row['total'], 2), 1, 1, 'C');
}

// --- Totals ---
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(90, 7, 'Grand Total:', 0, 0, 'R');
$pdf->Cell(25, 7, number_format($invoice['grand_total'], 2), 0, 1, 'R');

$pdf->Ln(10);
$pdf->Cell(0, 7, 'Signature: _______________________', 0, 1, 'R');
$pdf->Cell(0, 5, $shopAddress, 0, 1, 'C');

$filename = 'ShopInvoice_' . preg_replace('/[^A-Za-z0-9_-]/', '', $invoice['invoice_no']) . '.pdf';

// ✅ Output
if (ob_get_length()) ob_clean();
$pdf->Output('I', $filename);
ob_end_flush();
exit;
?>
