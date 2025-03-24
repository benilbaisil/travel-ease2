<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    header('Location: login.php');
    exit();
}

// Handle marking enquiry as responded
if (isset($_POST['respond_to_enquiry'])) {
    $enquiry_id = $_POST['enquiry_id'];
    $response = $_POST['response'];
    $staff_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE enquiries SET status = 'Responded', response = ?, responded_at = NOW(), responded_by = ? WHERE id = ?");
    $stmt->bind_param("sii", $response, $staff_id, $enquiry_id);
    $stmt->execute();
}

// Fetch all enquiries with responder details
$sql = "SELECT e.*, 
        CASE 
            WHEN e.responded_by IS NOT NULL THEN u.name 
            ELSE NULL 
        END as responder_name
        FROM enquiries e 
        LEFT JOIN users u ON e.responded_by = u.user_id 
        ORDER BY e.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Enquiries - Staff Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Customer Enquiries</h1>
            <a href="staff_dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex gap-4">
            <button onclick="filterEnquiries('all')" class="filter-btn bg-blue-500 text-white px-4 py-2 rounded">
                All
            </button>
            <button onclick="filterEnquiries('pending')" class="filter-btn bg-yellow-500 text-white px-4 py-2 rounded">
                Pending
            </button>
            <button onclick="filterEnquiries('responded')" class="filter-btn bg-green-500 text-white px-4 py-2 rounded">
                Responded
            </button>
        </div>

        <!-- Enquiries Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="enquiry-row" data-status="<?php echo strtolower($row['status']); ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo htmlspecialchars($row['name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php echo htmlspecialchars($row['email']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($row['subject']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $row['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="viewEnquiry(<?php echo htmlspecialchars(json_encode($row)); ?>)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <?php if($row['status'] === 'Pending'): ?>
                            <button onclick="showResponseForm(<?php echo $row['id']; ?>)" 
                                    class="text-green-600 hover:text-green-900">
                                <i class="fas fa-reply"></i> Respond
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Enquiry Modal -->
    <div id="viewEnquiryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl p-6 m-4 max-w-2xl w-full">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Enquiry Details</h2>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="enquiryDetails" class="space-y-4">
                <!-- Details will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl p-6 m-4 max-w-2xl w-full">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Respond to Enquiry</h2>
                <button onclick="closeResponseModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="responseForm" method="POST" class="space-y-4">
                <input type="hidden" name="enquiry_id" id="enquiry_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Response</label>
                    <textarea name="response" id="response" rows="4" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeResponseModal()" 
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" name="respond_to_enquiry"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Send Response
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterEnquiries(status) {
            const rows = document.querySelectorAll('.enquiry-row');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Update active filter button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('bg-opacity-75');
            });
            event.target.classList.add('bg-opacity-75');
        }

        function viewEnquiry(enquiry) {
            const modal = document.getElementById('viewEnquiryModal');
            const details = document.getElementById('enquiryDetails');
            
            const responseInfo = enquiry.response ? `
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">Response</h3>
                    <p class="text-blue-900">${enquiry.response}</p>
                    <div class="mt-2 text-sm text-blue-700">
                        Responded by: ${enquiry.responder_name || 'N/A'}<br>
                        Date: ${enquiry.responded_at ? new Date(enquiry.responded_at).toLocaleString() : 'N/A'}
                    </div>
                </div>
            ` : '';

            details.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">From</p>
                        <p class="font-semibold">${enquiry.name}</p>
                        <p class="text-blue-600">${enquiry.email}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Date</p>
                        <p class="font-semibold">${new Date(enquiry.created_at).toLocaleString()}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-600">Subject</p>
                    <p class="font-semibold">${enquiry.subject}</p>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-600">Message</p>
                    <p class="mt-2 text-gray-700">${enquiry.message}</p>
                </div>
                ${responseInfo}
            `;
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeViewModal() {
            const modal = document.getElementById('viewEnquiryModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function showResponseForm(enquiryId) {
            const modal = document.getElementById('responseModal');
            document.getElementById('enquiry_id').value = enquiryId;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeResponseModal() {
            const modal = document.getElementById('responseModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('responseForm').reset();
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const viewModal = document.getElementById('viewEnquiryModal');
            const responseModal = document.getElementById('responseModal');
            if (event.target === viewModal) {
                closeViewModal();
            }
            if (event.target === responseModal) {
                closeResponseModal();
            }
        }

        // Form submission handling
        document.getElementById('responseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('process_enquiry_response.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Response sent successfully',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to send response');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
            });
        });
    </script>
</body>
</html> 