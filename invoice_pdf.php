<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'db/db.php';
require('fpdf/fpdf.php');

// 🧾 Get Invoice ID
if (!isset($_GET['id'])) {
    die("Invalid request");
}
$id = intval($_GET['id']);

// Get Invoice Data
$stmt = $conn->prepare("
    SELECT vi.*, v.name AS vendor_name, v.address AS vendor_address
    FROM vendor_invoices vi
    LEFT JOIN vendors v ON vi.vendor_id = v.id
    WHERE vi.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    die("Invoice not found");
}
$invoice = $res->fetch_assoc();
$stmt->close();

$settings = [
    'logo_url' => 'https://factory.origami99.com/uploads/UA-Logo.png', // <-- update this if needed
];

// Company details
$companyName = "Usman Arshad Cloth Center";
$companyAddress = "Shop Number P-115 Maki Market, Rail Bazaar Faislabad";
$companyContact = "Phone: 041-2624142 | Abdul Manan: 03203432326";


// Custom PDF Class (with watermark)
class PDF_Watermark extends FPDF {
    public $watermarkImage = '';

    function Header() {
        // nothing in header
    }

    function Footer() {
        // nothing in footer
    }

    // Draw watermark image in background
    function AddWatermark($file) {
        if (file_exists($file) || filter_var($file, FILTER_VALIDATE_URL)) {
            $this->SetTextColor(200, 200, 200);
            $this->Image($file, 10, 8, 18);
            $this->SetTextColor(0, 0, 0);
        }
    }
}

// Create PDF A5
$pdf = new PDF_Watermark('P', 'mm', 'A5');
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();

// Add dynamic logo watermark
if (!empty($settings['logo_url'])) {
    $pdf->AddWatermark($settings['logo_url']);
}

// Header
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 8, $companyName, 0, 1, 'C');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, $companyAddress, 0, 1, 'C');
$pdf->Cell(0, 5, $companyContact, 0, 1, 'C');

$pdf->Ln(4);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.4);
$pdf->Line(10, $pdf->GetY(), 138, $pdf->GetY());
$pdf->Ln(6);

// Title
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'VENDOR INVOICE', 0, 1, 'C');
$pdf->Ln(2);

// Invoice info
$pdf->SetFont('Arial', '', 10);
$leftX = 12;
$lineH = 6;

$pdf->SetX($leftX);
$pdf->Cell(40, $lineH, "Vendor:", 0, 0);
$pdf->SetX($leftX + 30);
$pdf->Cell(0, $lineH, $invoice['vendor_name'] ?: '-', 0, 1);

$pdf->SetX($leftX);
$pdf->Cell(40, $lineH, "Vendor Address:", 0, 0);
$pdf->SetX($leftX + 30);
$pdf->MultiCell(0, $lineH, $invoice['vendor_address'] ?: '-', 0, 1);

$pdf->SetX($leftX);
$pdf->Cell(40, $lineH, "Lot Number:", 0, 0);
$pdf->SetX($leftX + 30);
$pdf->Cell(0, $lineH, $invoice['lot_number'] ?: '-', 0, 1);

$pdf->SetX($leftX);
$pdf->Cell(40, $lineH, "Payment Date:", 0, 0);
$pdf->SetX($leftX + 30);
$pdf->Cell(0, $lineH, $invoice['payment_date'] ? date('d M Y', strtotime($invoice['payment_date'])) : '-', 0, 1);

$pdf->Ln(4);

// Amount box
$pdf->SetXY($leftX, $pdf->GetY());
$pdf->SetFont('Arial', '', 10);

$issue_meter = floatval($invoice['issue_meter'] ?? 0);
$rejection = floatval($invoice['rejection'] ?? 0);
$safi_meter = $issue_meter - $rejection;

// Issue Meter
$pdf->Cell(60, $lineH, "Issue Meter", 1, 0, 'C');
$pdf->Cell(38, $lineH, number_format($issue_meter, 2), 1, 1, 'R');

// Rejection
$pdf->SetX($leftX);
$pdf->Cell(60, $lineH, "Rejection", 1, 0, 'C');
$pdf->Cell(38, $lineH, number_format($rejection, 2), 1, 1, 'R');

// Safi Meter
$pdf->SetX($leftX);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(60, $lineH, "Safi Meter", 1, 0, 'C');
$pdf->Cell(38, $lineH, number_format($safi_meter, 2), 1, 1, 'R');

// 🔹 Total Amount (Set X again)
$pdf->SetX($leftX);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, $lineH, "Total Amount", 1, 0, 'C');
$pdf->Cell(38, $lineH, number_format($invoice['total_amount'], 2), 1, 1, 'R');

// Amount Paid
$pdf->SetX($leftX);
$pdf->Cell(60, $lineH, "Amount Paid", 1, 0, 'C');
$pdf->Cell(38, $lineH, number_format($invoice['amount_paid'], 2), 1, 1, 'R');

// Balance
$balance = $invoice['total_amount'] - $invoice['amount_paid'];
$pdf->SetX($leftX);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(60, $lineH + 2, "Balance", 1, 0, 'C');
$pdf->Cell(38, $lineH + 2, number_format($balance, 2), 1, 1, 'R');

$pdf->Ln(6);

// Remarks
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, "Remarks:", 0, 1);
$pdf->SetFont('Arial', '', 9);
$remarks = $invoice['remarks'] ?: '-';
$pdf->MultiCell(0, 5, $remarks, 0, 1);

$pdf->Ln(8);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 5, 'Generated on: ' . date('d M Y h:i A'), 0, 1, 'C');

// Output file
$filename = 'Invoice_' . preg_replace('/[^A-Za-z0-9_\-]/', '', ($invoice['lot_number'] ?: $invoice['id'])) . '.pdf';
$pdf->Output('I', $filename);
exit;
?>
