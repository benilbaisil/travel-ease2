<?php
// Turn off output buffering
ob_clean();

// Ensure no whitespace before opening PHP tag and after closing PHP tag
session_start();
require_once 'config.php';
require_once 'TCPDF/tcpdf.php';

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Travel Ease');
$pdf->SetTitle('Booking Confirmation');

// Set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Travel Ease', 'Booking Confirmation');

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Get booking ID from URL parameter and validate it
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : null;

if ($booking_id > 0) {  // Make sure we have a valid booking ID
    try {
        // Use the existing database connection
        $sql = "SELECT b.id, u.name as customer_name, b.travel_date, 
                       p.destination, p.package_name, b.total_amount, b.created_at as booking_date
                FROM bookings b
                JOIN users u ON b.user_id = u.user_id
                JOIN travel_packages p ON b.package_id = p.id
                WHERE b.id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $bookingDetails = $result->fetch_assoc();

        if ($bookingDetails) {
            // Add a page
            $pdf->AddPage();

            // Set border style
            $pdf->SetLineStyle(array('width' => 0.5, 'color' => array(0, 0, 0)));
            $pdf->Rect(5, 5, $pdf->getPageWidth() - 10, $pdf->getPageHeight() - 10);

            // Set font
            $pdf->SetFont('helvetica', '', 14);

            // Format the amount in Indian currency format (e.g., 1,00,000.00)
            $amount = $bookingDetails['total_amount'];
            $decimal = '';
            
            // Handle decimal points if they exist
            if (strpos($amount, '.') !== false) {
                list($amount, $decimal) = explode('.', $amount);
                $decimal = '.' . $decimal;
            }
            
            // Format the whole number part according to Indian numbering system
            $lastThree = substr($amount, -3);
            $otherNumbers = substr($amount, 0, -3);
            $lastThree = $otherNumbers != '' ? ',' . $lastThree : $lastThree;
            $formatted_amount = $otherNumbers != '' ? preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $otherNumbers) . $lastThree : $lastThree;
            $formatted_amount .= $decimal;

            $html = '<style>
                        h1 { color: #2E86C1; font-size: 36px; text-align: center; margin-bottom: 30px; }
                        p { font-size: 16px; line-height: 1.6; margin-bottom: 20px; }
                        .highlight { background-color: #FFC300; font-weight: bold; padding: 5px; }
                        .amount { 
                            color: #2E86C1; 
                            font-size: 20px; 
                            font-weight: bold;
                            padding: 10px;
                            background-color: #f8f9fa;
                            border-radius: 5px;
                        }
                        .rupee-symbol {
                            font-family: DejaVu Sans;
                            font-weight: bold;
                        }
                     </style>';
            $html .= '<h1>Booking Confirmation</h1>';
            $html .= '<p class="highlight">Booking Reference: ' . htmlspecialchars($bookingDetails['id']) . '</p>';
            $html .= '<p>Dear ' . htmlspecialchars($bookingDetails['customer_name']) . ',</p>';
            $html .= '<p>We are pleased to confirm your booking with Travel Ease. Below are the details of your booking:</p>';
            $html .= '<p><strong>Booking Date:</strong> ' . htmlspecialchars(date('F d, Y', strtotime($bookingDetails['booking_date']))) . '</p>';
            $html .= '<p><strong>Travel Date:</strong> ' . htmlspecialchars(date('F d, Y', strtotime($bookingDetails['travel_date']))) . '</p>';
            $html .= '<p><strong>Destination:</strong> ' . htmlspecialchars($bookingDetails['destination']) . '</p>';
            $html .= '<p><strong>Package Type:</strong> ' . htmlspecialchars($bookingDetails['package_name']) . '</p>';
            $html .= '<p><strong>Total Amount:</strong> <span class="amount"><span class="rupee-symbol">₹</span> ' . htmlspecialchars($formatted_amount) . '/-</span></p>';
            $html .= '<p>Thank you for choosing Travel Ease. We wish you a pleasant journey!</p>';

            // Write HTML content to PDF
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $pdf->AddPage();
            $pdf->Cell(0, 10, 'Booking not found for ID: ' . $booking_id, 0, 1, 'C');
        }
    } catch(Exception $e) {
        $pdf->AddPage();
        $pdf->Cell(0, 10, 'Database error: ' . $e->getMessage(), 0, 1, 'C');
    }

    // Close database connection
    $conn->close();
} else {
    $pdf->AddPage();
    $pdf->Cell(0, 10, 'Invalid or no booking ID provided. Please provide a valid booking ID.', 0, 1, 'C');
}

// Before outputting the PDF, clear any output buffers
ob_end_clean();

// Close and output PDF document
$pdf->Output('booking_confirmation.pdf', 'D');
// Make sure there's no whitespace or content after the PHP closing tag