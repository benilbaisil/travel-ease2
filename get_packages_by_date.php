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
$startDate = isset($_GET['start']) ? $_GET['start'] : null;
$endDate = isset($_GET['end']) ? $_GET['end'] : null;

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

// Fetch package data for the selected date range
$query = "
    SELECT 
        tp.id,
        tp.package_name,
        tp.description,
        tp.price,
        tp.duration,
        COUNT(b.id) as booking_count,
        SUM(CASE WHEN b.booking_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN b.booking_status = 'Confirmed' THEN tp.price ELSE 0 END) as total_revenue
    FROM travel_packages tp
    LEFT JOIN bookings b ON tp.id = b.package_id
    AND DATE(b.created_at) BETWEEN ? AND ?
    GROUP BY tp.id
    ORDER BY booking_count DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

// Initialize statistics
$totalPackages = 0;
$totalBookings = 0;
$totalRevenue = 0;
$totalConfirmedBookings = 0;
$packages = [];

while ($row = $result->fetch_assoc()) {
    $packages[] = $row;
    $totalPackages++;
    $totalBookings += $row['booking_count'];
    $totalRevenue += $row['total_revenue'];
    $totalConfirmedBookings += $row['confirmed_bookings'];
}

// Calculate average price
$avgPrice = $totalPackages > 0 ? $totalRevenue / $totalConfirmedBookings : 0;

// Create PDF using TCPDF
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'Travel Ease - Package Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
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
$pdf->SetTitle('Package Report');

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

// Package Summary
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Package Summary', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 12);

$summaryData = [
    ['Total Packages:', $totalPackages],
    ['Total Bookings:', $totalBookings],
    ['Confirmed Bookings:', $totalConfirmedBookings],
    ['Total Revenue:', 'Rs.' . formatNumber($totalRevenue)],
    ['Average Price:', 'Rs.' . formatNumber($avgPrice)]
];

foreach ($summaryData as $row) {
    $pdf->Cell(100, 8, $row[0], 0, 0);
    $pdf->Cell(0, 8, $row[1], 0, 1);
}

$pdf->Ln(5);

// Package Details
if (!empty($packages)) {
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Package Details', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);

    // Table header
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    $header = ['Package Name', 'Duration', 'Price', 'Bookings', 'Confirmed', 'Revenue'];
    $w = [50, 25, 25, 25, 25, 30];
    
    foreach ($header as $i => $col) {
        $pdf->Cell($w[$i], 7, $col, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table data
    $pdf->SetFont('helvetica', '', 9);
    $fill = false;
    foreach ($packages as $package) {
        $pdf->Cell($w[0], 6, $package['package_name'], 1, 0, 'L', $fill);
        $pdf->Cell($w[1], 6, $package['duration'], 1, 0, 'C', $fill);
        $pdf->Cell($w[2], 6, 'Rs.' . formatNumber($package['price']), 1, 0, 'R', $fill);
        $pdf->Cell($w[3], 6, $package['booking_count'], 1, 0, 'C', $fill);
        $pdf->Cell($w[4], 6, $package['confirmed_bookings'], 1, 0, 'C', $fill);
        $pdf->Cell($w[5], 6, 'Rs.' . formatNumber($package['total_revenue']), 1, 0, 'R', $fill);
        $pdf->Ln();
        
        $fill = !$fill;
    }
} else {
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'No packages found for the selected date range', 0, 1, 'L');
}

// Before PDF output
if (ob_get_length()) ob_clean();

// Close and output PDF document
$filename = 'travel-ease-package-report-' . $startDate . '-to-' . $endDate . '.pdf';
$pdf->Output($filename, 'D');

// Clean up
ob_end_flush();
$conn->close();

// Number formatting function
function formatNumber($number, $decimals = 2) {
    $parts = explode('.', (string)$number);
    $whole = $parts[0];
    $decimal = isset($parts[1]) ? $parts[1] : '';
    
    // Add commas to whole number part
    $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole);
    
    // If decimals are needed, add them back
    if ($decimals > 0) {
        $decimal = str_pad($decimal, $decimals, '0');
        return $whole . '.' . substr($decimal, 0, $decimals);
    }
    
    return $whole;
}
?> 