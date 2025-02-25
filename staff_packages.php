<?php
session_start();

// Check if user is logged in and is staff
if (!isset($_SESSION['is_staff']) || $_SESSION['is_staff'] != 1) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "travel_ease");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all packages
$query = "SELECT * FROM packages ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Management - TravelEase Staff</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .package-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="text-2xl font-bold text-green-600">TravelEase Staff Portal</div>
                <div class="flex items-center space-x-4">
                    <a href="staff_bookings.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-calendar-check"></i> Bookings
                    </a>
                    <a href="staff_packages.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-suitcase"></i> Packages
                    </a>
                    <span class="text-gray-800">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">Package Management</h1>
                <button onclick="openAddPackageModal()" 
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-300">
                    <i class="fas fa-plus mr-2"></i>Add New Package
                </button>
            </div>
        </div>
    </div>

    <!-- Packages Grid -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while($package = $result->fetch_assoc()): ?>
            <div class="package-card bg-white rounded-lg shadow-md overflow-hidden">
                <img src="<?php echo htmlspecialchars($package['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($package['name']); ?>"
                     class="w-full h-48 object-cover">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">
                                <?php echo htmlspecialchars($package['name']); ?>
                            </h3>
                            <p class="text-gray-600">
                                <?php echo htmlspecialchars($package['duration']); ?>
                            </p>
                        </div>
                        <span class="text-2xl font-bold text-green-600">
                            ₹<?php echo number_format($package['price']); ?>
                        </span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <?php 
                        $features = json_decode($package['features'], true);
                        foreach($features as $feature): 
                        ?>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            <?php echo htmlspecialchars($feature); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex justify-between items-center mt-4">
                        <span class="text-sm text-gray-500">
                            Status: 
                            <span class="<?php echo $package['active'] ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $package['active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </span>
                        <div class="space-x-2">
                            <button onclick="editPackage(<?php echo $package['id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="togglePackageStatus(<?php echo $package['id']; ?>)" 
                                    class="<?php echo $package['active'] ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800'; ?>">
                                <i class="fas <?php echo $package['active'] ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                            </button>
                            <button onclick="deletePackage(<?php echo $package['id']; ?>)" 
                                    class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Add/Edit Package Modal -->
    <div id="packageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800" id="modalTitle">Add New Package</h2>
                    <button onclick="closePackageModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="packageForm" class="space-y-4">
                    <input type="hidden" id="packageId" name="id">
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Package Name</label>
                        <input type="text" id="packageName" name="name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Duration</label>
                            <input type="text" id="packageDuration" name="duration" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                                   placeholder="e.g., 5 Days, 4 Nights">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Price</label>
                            <input type="number" id="packagePrice" name="price" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Image URL</label>
                        <input type="url" id="packageImage" name="image_url" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Features (one per line)</label>
                        <textarea id="packageFeatures" name="features" required rows="4"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closePackageModal()"
                                class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Save Package
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddPackageModal() {
            document.getElementById('modalTitle').textContent = 'Add New Package';
            document.getElementById('packageForm').reset();
            document.getElementById('packageId').value = '';
            document.getElementById('packageModal').classList.remove('hidden');
            document.getElementById('packageModal').classList.add('flex');
        }

        function closePackageModal() {
            document.getElementById('packageModal').classList.add('hidden');
            document.getElementById('packageModal').classList.remove('flex');
        }

        function editPackage(id) {
            // Redirect to the edit page with the package ID
            window.location.href = 'staffpackageedit.php?id=' + id;
        }

        function togglePackageStatus(id) {
            if(confirm('Are you sure you want to change this package\'s status?')) {
                // Add AJAX call to toggle status
            }
        }

        function deletePackage(id) {
            if(confirm('Are you sure you want to delete this package?')) {
                // Add AJAX call to delete package
            }
        }

        document.getElementById('packageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Add AJAX call to save package
            closePackageModal();
        });

        // Close modal when clicking outside
        document.getElementById('packageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePackageModal();
            }
        });
    </script>
</body>
</html> 