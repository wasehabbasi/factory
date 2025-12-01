<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require('fpdf/fpdf.php');
include "db/db.php";

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die("Invalid invoice ID");
}

// ✅ Fetch Factory Invoice + Factory Info + Product Info
$stmt = $conn->prepare("
    SELECT fi.*, 
           f.name AS factory_name, 
           p.name AS product_name, 
           p.measurement, 
           p.lot_number AS product_lot_number
    FROM factory_invoices fi
    LEFT JOIN factories f ON fi.factory_id = f.id
    LEFT JOIN products p ON fi.product_id = p.id
    WHERE fi.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    die("Invoice not found");
}

// ✅ Company & Invoice Info
$companyName = "Usman Arshad Cloth Center";
$companyContact = "Phone: 041-2624142";
$logo_path = "./uploads/UA-Logo.png";
$date = date('d-m-Y', strtotime($invoice['created_at'] ?? date('Y-m-d')));

$total_amount = $invoice['total_meter'] * $invoice['per_meter_rate'];
$net_amount = $total_amount - $invoice['rejection'] - $invoice['advance_adjusted'];

// ✅ PDF Class
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
$pdf->Cell(0, 8, 'FACTORY INVOICE', 0, 1, 'C');
$pdf->Ln(4);

// --- Invoice Info ---
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(35, 6, 'Invoice ID:', 0, 0);
$pdf->Cell(0, 6, $invoice['id'], 0, 1);
$pdf->Cell(35, 6, 'Date:', 0, 0);
$pdf->Cell(0, 6, $date, 0, 1);
$pdf->Cell(35, 6, 'Factory Name:', 0, 0);
$pdf->Cell(0, 6, $invoice['factory_name'], 0, 1);
$pdf->Cell(35, 6, 'Product Name:', 0, 0);
$pdf->Cell(0, 6, $invoice['product_name'] . " (" . $invoice['measurement'] . ")", 0, 1);
$pdf->Cell(35, 6, 'Lot Number:', 0, 0);
$pdf->Cell(0, 6, $invoice['product_lot_number'], 0, 1);
$pdf->Ln(5);

// --- Table Header ---
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(8, 8, '#', 1, 0, 'C', true);
$pdf->Cell(28, 8, 'Total Meter', 1, 0, 'C', true);
$pdf->Cell(28, 8, 'Rate/Mtr', 1, 0, 'C', true);
$pdf->Cell(28, 8, 'Total Amt', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Rejection', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Advance', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Net Amt', 1, 1, 'C', true);

// --- Table Row ---
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(8, 8, '1', 1, 0, 'C');
$pdf->Cell(28, 8, number_format($invoice['total_meter'], 2), 1, 0, 'C');
$pdf->Cell(28, 8, number_format($invoice['per_meter_rate'], 2), 1, 0, 'C');
$pdf->Cell(28, 8, number_format($total_amount, 2), 1, 0, 'C');
$pdf->Cell(25, 8, number_format($invoice['rejection'], 2), 1, 0, 'C');
$pdf->Cell(25, 8, number_format($invoice['advance_adjusted'], 2), 1, 0, 'C');
$pdf->Cell(25, 8, number_format($net_amount, 2), 1, 1, 'C');


// --- Totals Section ---
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(105, 7, 'Grand Total:', 0, 0, 'R');
$pdf->Cell(30, 7, number_format($net_amount, 2), 0, 1, 'R');

$pdf->Ln(10);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 7, 'Signature: ___________________________', 0, 1, 'R');
$pdf->Ln(4);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 5, 'Thank you for your business!', 0, 1, 'C');

$filename = 'FactoryInvoice_' . $invoice['id'] . '.pdf';

// ✅ Output PDF
if (ob_get_length()) ob_clean();
$pdf->Output('I', $filename);
ob_end_flush();
exit;
?>
