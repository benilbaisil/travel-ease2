<?php
session_start();
require_once 'config.php';

// Check if user is staff
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    header('Location: login.php');
    exit();
}

// Handle enquiry response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enquiry_id'])) {
    $enquiry_id = filter_input(INPUT_POST, 'enquiry_id', FILTER_SANITIZE_NUMBER_INT);
    $response = filter_input(INPUT_POST, 'response', FILTER_SANITIZE_STRING);
    $staff_id = $_SESSION['user_id'];

    $sql = "UPDATE enquiries SET 
            status = 'Responded',
            response = ?,
            responded_at = NOW(),
            responded_by = ?
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $response, $staff_id, $enquiry_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Response sent successfully!";
    } else {
        $_SESSION['error'] = "Failed to send response.";
    }
    
    header('Location: view_enquiries.php');
    exit();
}

// Fetch enquiries
$sql = "SELECT e.*, u.name as staff_name 
        FROM enquiries e 
        LEFT JOIN users u ON e.responded_by = u.user_id 
        ORDER BY e.created_at DESC";
$result = $conn->query($sql);
$enquiries = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Enquiries - Staff Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Customer Enquiries</h1>
            <a href="staff_dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($enquiries as $enquiry): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">#<?php echo $enquiry['id']; ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($enquiry['name']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($enquiry['email']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($enquiry['subject']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $enquiry['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($enquiry['status'] === 'Responded' ? 'bg-green-100 text-green-800' : 
                                            'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo $enquiry['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4"><?php echo date('M d, Y H:i', strtotime($enquiry['created_at'])); ?></td>
                                <td class="px-6 py-4">
                                    <button onclick="viewEnquiry(<?php echo htmlspecialchars(json_encode($enquiry)); ?>)" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Enquiry Modal -->
    <div id="enquiryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Enquiry Details</h3>
                <div id="enquiryDetails" class="space-y-4"></div>
                <div class="mt-4">
                    <button onclick="closeEnquiryModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewEnquiry(enquiry) {
            const modal = document.getElementById('enquiryModal');
            const details = document.getElementById('enquiryDetails');
            
            details.innerHTML = `
                <div class="space-y-2">
                    <p><strong>From:</strong> ${enquiry.name}</p>
                    <p><strong>Email:</strong> ${enquiry.email}</p>
                    <p><strong>Subject:</strong> ${enquiry.subject}</p>
                    <p><strong>Message:</strong></p>
                    <p class="bg-gray-50 p-3 rounded">${enquiry.message}</p>
                    ${enquiry.response ? `
                        <div class="mt-4">
                            <p><strong>Response:</strong></p>
                            <p class="bg-blue-50 p-3 rounded">${enquiry.response}</p>
                            <p class="text-sm text-gray-500">Responded by: ${enquiry.staff_name}</p>
                        </div>
                    ` : ''}
                </div>
                ${enquiry.status === 'Pending' ? `
                    <form action="view_enquiries.php" method="POST" class="mt-4">
                        <input type="hidden" name="enquiry_id" value="${enquiry.id}">
                        <textarea name="response" class="w-full p-2 border rounded" rows="4" required 
                                placeholder="Write your response..."></textarea>
                        <button type="submit" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Send Response
                        </button>
                    </form>
                ` : ''}
            `;
            
            modal.classList.remove('hidden');
        }

        function closeEnquiryModal() {
            document.getElementById('enquiryModal').classList.add('hidden');
        }
    </script>
</body>
</html>