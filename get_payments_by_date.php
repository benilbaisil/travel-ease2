<?php
ob_start(); // Start output buffering
require_once 'config.php';
require_once 'TCPDF/tcpdf.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    ob_end_clean(); // Clean output buffer
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Get date range parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

if (!$startDate || !$endDate) {
    ob_end_clean(); // Clean output buffer
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Start and end dates are required']);
    exit;
}

// Validate date format
if (!strtotime($startDate) || !strtotime($endDate)) {
    ob_end_clean(); // Clean output buffer
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

// We don't need to create a new connection since it's already done in config.php
// Remove any whitespace or output before PDF generation
if (ob_get_length()) ob_clean();

// Fetch payment data for the selected date range
$query = "
    SELECT 
        p.id as payment_id,
        p.amount,
        p.mode as payment_method,
        p.status as payment_status,
        p.transaction_id,
        p.created_at as payment_date,
        b.id as booking_id,
        b.booking_status,
        b.travel_date,
        u.name as user_name,
        u.email as user_email,
        tp.package_name,
        tp.price as package_price
    FROM payment p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.user_id
    JOIN travel_packages tp ON b.package_id = tp.id
    WHERE DATE(p.created_at) BETWEEN ? AND ?
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

// Initialize statistics
$totalPayments = 0;
$totalAmount = 0;
$successfulPayments = 0;
$failedPayments = 0;
$uniqueUsers = [];
$paymentMethods = [];
$payments = [];

while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
    $totalPayments++;
    $totalAmount += $row['amount'];
    
    if (strtolower($row['payment_status']) === 'success') {
        $successfulPayments++;
    } elseif (strtolower($row['payment_status']) === 'failed') {
        $failedPayments++;
    }
    
    $uniqueUsers[$row['user_email']] = true;
    $paymentMethods[$row['payment_method']] = ($paymentMethods[$row['payment_method']] ?? 0) + 1;
}

// Create PDF using TCPDF
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'Travel Ease - Payment Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Travel Ease System');
$pdf->SetTitle('Payment Report');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// Set margins
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add date range
$pdf->Cell(0, 10, 'Period: ' . date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate)), 0, 1, 'C');
$pdf->Ln(5);

// Payment Summary
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Payment Summary', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

$summaryData = [
    ['Total Payments:', $totalPayments],
    ['Total Amount:', '₹' . number_format($totalAmount, 2)],
    ['Successful Payments:', $successfulPayments],
    ['Failed Payments:', $failedPayments],
    ['Unique Users:', count($uniqueUsers)]
];

foreach ($summaryData as $row) {
    $pdf->Cell(100, 8, $row[0], 0, 0);
    $pdf->Cell(0, 8, $row[1], 0, 1);
}

$pdf->Ln(5);

// Payment Methods Distribution
if (!empty($paymentMethods)) {
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Payment Methods', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);

    foreach ($paymentMethods as $method => $count) {
        $percentage = ($count / $totalPayments) * 100;
        $pdf->Cell(100, 8, $method . ':', 0, 0);
        $pdf->Cell(0, 8, $count . ' (' . number_format($percentage, 1) . '%)', 0, 1);
    }

    $pdf->Ln(5);
}

// Detailed Payment Records
if (!empty($payments)) {
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Payment Details', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    // Table header
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    $header = ['Date', 'Transaction ID', 'Customer', 'Package', 'Amount', 'Method', 'Status'];
    $w = [30, 35, 40, 35, 25, 25, 25];
    
    foreach ($header as $i => $col) {
        $pdf->Cell($w[$i], 7, $col, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table data
    $pdf->SetFont('helvetica', '', 9);
    $fill = false;
    foreach ($payments as $payment) {
        $paymentDate = new DateTime($payment['payment_date']);
        
        $pdf->Cell($w[0], 6, $paymentDate->format('d M Y, H:i'), 1, 0, 'L', $fill);
        $pdf->Cell($w[1], 6, $payment['transaction_id'], 1, 0, 'L', $fill);
        $pdf->Cell($w[2], 6, $payment['user_name'], 1, 0, 'L', $fill);
        $pdf->Cell($w[3], 6, $payment['package_name'], 1, 0, 'L', $fill);
        $pdf->Cell($w[4], 6, '₹' . number_format($payment['amount'], 2), 1, 0, 'R', $fill);
        $pdf->Cell($w[5], 6, $payment['payment_method'], 1, 0, 'L', $fill);
        $pdf->Cell($w[6], 6, $payment['payment_status'], 1, 0, 'L', $fill);
        $pdf->Ln();
        
        $fill = !$fill;
    }
} else {
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'No payments found for the selected date range', 0, 1, 'L');
}

// Before PDF output
if (ob_get_length()) ob_clean();

// Close and output PDF document
$filename = 'travel-ease-payment-report-' . $startDate . '-to-' . $endDate . '.pdf';
$pdf->Output($filename, 'D');

// Clean up
ob_end_flush();
$conn->close();
?> 