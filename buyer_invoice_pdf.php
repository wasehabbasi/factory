<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('fpdf/fpdf.php');
include "db/db.php";

// ✅ Get buyer invoice by ID
$id = $_GET['id'] ?? 0;
if (!$id) {
    die("Invalid Invoice ID");
}

$sql = "SELECT bi.*, 
               b.name AS buyer_name, 
               b.phone AS buyer_phone, 
               b.address AS buyer_address,
               p.name AS product_name
        FROM buyer_invoices bi
        LEFT JOIN buyers b ON bi.buyer_id = b.id
        LEFT JOIN products p ON bi.product_id = p.id
        WHERE bi.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$invoice = $result->fetch_assoc();

if (!$invoice) {
    die("Invoice not found");
}

// ✅ Company Settings
$company_name = "Usman Arshad Cloth Center";
$ptcl_number = "PTCL: 041-2624142";
$phone_number = "Phone: 03203432326";
$address = "Shop Number P-115 Maki Market, Rail Bazaar Faislabad";
$logo_path = "./uploads/UA-Logo.png"; // <-- Update if needed

// ✅ Safe helper (prevent null warnings)
function safe($val, $default = '-') {
    return $val !== null && $val !== '' ? $val : $default;
}

class PDF_Invoice extends FPDF
{
    function Header()
    {
        global $company_name, $ptcl_number, $phone_number, $logo_path;

        // Logo watermark (optional)
        // if (file_exists($logo_path)) {
        //     $this->Image($logo_path, 40, 60, 80, 0, '', '', true);
        // }

        // Logo top-left
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 10, 10, 20);
        }

        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 5, strtoupper($company_name), 0, 1, 'R');

        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, $ptcl_number, 0, 1, 'R');
        $this->Cell(0, 5, $phone_number, 0, 1, 'R');
        $this->Ln(8);

        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, 'BUYER INVOICE', 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer()
    {
        global $address;
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, $address, 0, 1, 'C');
        $this->Cell(0, 5, 'Thank you for your business!', 0, 0, 'C');
    }
}

// ✅ Create PDF (A5)
$pdf = new PDF_Invoice('P', 'mm', 'A5');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// ✅ Invoice Info
$pdf->Cell(30, 8, 'Invoice No:', 0, 0);
$pdf->Cell(60, 8, safe($invoice['invoice_no']), 0, 1);

$pdf->Cell(30, 8, 'Date:', 0, 0);
$pdf->Cell(60, 8, !empty($invoice['date']) ? date('d M Y', strtotime($invoice['date'])) : '-', 0, 1);

$pdf->Cell(30, 8, 'Customer:', 0, 0);
$pdf->Cell(60, 8, safe($invoice['buyer_name']), 0, 1);

$pdf->Cell(30, 8, 'Lot Number:', 0, 0);
$pdf->Cell(60, 8, safe($invoice['lot_number']), 0, 1);

$pdf->Ln(5);

// ✅ Table Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(20, 8, 'Qty', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Product', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Rate', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Total', 1, 1, 'C', true);

// ✅ Table Data
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(20, 8, safe($invoice['qty']), 1, 0, 'C');
$pdf->Cell(50, 8, safe($invoice['product_name']), 1, 0, 'C');
$pdf->Cell(25, 8, number_format((float)$invoice['rate'], 2), 1, 0, 'C');
$pdf->Cell(30, 8, number_format((float)$invoice['total_amount'], 2), 1, 1, 'C');

// ✅ Payment Summary
$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 8, 'Nag (Cash Received):', 1, 0, 'R', true);
$pdf->Cell(30, 8, number_format($invoice['nag'], 2), 1, 1, 'C', true);


$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 8, 'Total Amount:', 1, 0, 'R', true);
$pdf->Cell(30, 8, number_format((float)$invoice['total_amount'], 2), 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 8, 'Amount Paid:', 1, 0, 'R');
$pdf->Cell(30, 8, number_format((float)$invoice['amount_paid'], 2), 1, 1, 'C');

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 8, 'Balance:', 1, 0, 'R', true);
$pdf->Cell(30, 8, number_format((float)$invoice['balance'], 2), 1, 1, 'C', true);

$pdf->Ln(6);

// ✅ Remarks
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 6, 'Remarks: ' . safe($invoice['remarks']));

// ✅ Generated On
$pdf->Ln(4);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 5, 'Generated on: ' . date('d M Y h:i A'), 0, 1, 'C');

// ✅ Safe Output
if (ob_get_length()) ob_clean();
$pdf->Output('I', 'Buyer_Invoice_' . safe($invoice['invoice_no']) . '.pdf');
ob_end_flush();
exit;
?>
