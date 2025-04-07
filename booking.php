<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['name'])) {
    header("Location: login.php");
    exit();
}

// Get package_id from URL
$package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;

// Fetch package details from database
$sql = "SELECT * FROM travel_packages WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();
$package = $result->fetch_assoc();

// If package not found, set error and redirect
if (!$package) {
    $_SESSION['booking_error'] = 'Invalid package selected. Please choose a valid package.';
    header("Location: userpackages.php");
    exit();
}

// Add this debug code after the package assignment
echo "<!-- Debug: Selected package = " . htmlspecialchars($package['package_name']) . " -->";

// Package information array (in real application, this would come from a database)
$packages = [
    'kerala-adventure' => [
        'name' => 'Kerala Adventure Package',
        'price' => 15000,
        'duration' => '5 Days, 4 Nights'
    ],
    'karnataka-heritage' => [
        'name' => 'Karnataka Heritage Tour',
        'price' => 12000,
        'duration' => '4 Days, 3 Nights'
    ],
    'rajasthan-royal' => [
        'name' => 'Royal Rajasthan Tour',
        'price' => 20000,
        'duration' => '6 Days, 5 Nights'
    ]
];

// Optional: Print available packages
echo "<!-- Debug: Available packages = " . implode(', ', array_keys($packages)) . " -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Package - TravelEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-container {
            background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)),
                        url('/travel%20ease/img/travel-concept-with-landmarks.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .form-card {
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .form-card:hover {
            transform: translateY(-5px);
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-group input, .input-group select {
            transition: border 0.3s ease, box-shadow 0.3s ease;
        }
        
        .input-group input:focus, .input-group select:focus {
            border-color: #10B981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }

        .input-group input.border-red-500 {
            border-color: #EF4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .input-group input.border-green-500 {
            border-color: #10B981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        
        .package-info {
            border-left: 4px solid #10B981;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .submit-btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .feature-icon {
            color: #10B981;
            margin-right: 0.5rem;
        }
    </style>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get form elements
            const form = document.getElementById('bookingForm');
            const travelDateInput = document.querySelector('input[name="travel_date"]');
            const phoneInput = document.querySelector('input[name="phone"]');

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            travelDateInput.setAttribute('min', today);

            // Phone number validation
            phoneInput.addEventListener('input', function(e) {
                const phoneNumber = e.target.value.replace(/\D/g, ''); // Remove non-digits
                // Check if number starts with 0 or is not 10 digits
                const isValid = /^[6-9][0-9]{9}$/.test(phoneNumber);
                
                if (!isValid) {
                    phoneInput.classList.add('border-red-500');
                    phoneInput.classList.remove('border-green-500');
                    if (phoneNumber.startsWith('0')) {
                        showError(phoneInput, 'Phone number cannot start with 0');
                    } else if (phoneNumber.length !== 10) {
                        showError(phoneInput, 'Please enter a valid 10-digit phone number');
                    } else {
                        showError(phoneInput, 'Phone number must start with 6-9');
                    }
                } else {
                    phoneInput.classList.remove('border-red-500');
                    phoneInput.classList.add('border-green-500');
                    hideError(phoneInput);
                }
            });

            // Travel date validation
            travelDateInput.addEventListener('change', function(e) {
                const selectedDate = new Date(e.target.value);
                const currentDate = new Date();
                
                if (selectedDate < currentDate) {
                    travelDateInput.classList.add('border-red-500');
                    travelDateInput.classList.remove('border-green-500');
                    showError(travelDateInput, 'Please select a future date');
                } else {
                    travelDateInput.classList.remove('border-red-500');
                    travelDateInput.classList.add('border-green-500');
                    hideError(travelDateInput);
                }
            });

            // Modify form submission for Razorpay
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                
                // Existing validation code
                let isValid = true;
                // ... existing validation checks ...

                if (!isValid) {
                    return false;
                }

                // Calculate total amount (price * number of guests)
                const guests = document.querySelector('select[name="guests"]').value;
                const amount = <?php echo $package['price']; ?> * guests;

                // Razorpay payment options
                var options = {
                    key: "rzp_test_wDXUXVf6PM0T4D",
                    amount: amount * 100, // Amount in paise
                    currency: "INR",
                    name: "TravelEase",
                    description: "<?php echo htmlspecialchars($package['package_name']); ?>",
                    handler: function (response) {
                        // On successful payment
                        document.querySelector('#payment_id').value = response.razorpay_payment_id;
                        
                        // Show loading state
                        const submitButton = form.querySelector('button[type="submit"]');
                        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                        submitButton.disabled = true;

                        // Submit form with payment ID
                        const formData = new FormData(form);
                        fetch('process_booking.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            // Clone the response so we can use it twice if needed
                            const responseClone = response.clone();
                            
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            
                            // Try to parse as JSON, but fallback to text if it fails
                            return response.json()
                                .catch(() => {
                                    return responseClone.text().then(text => {
                                        throw new Error('Invalid JSON response: ' + text);
                                    });
                                });
                        })
                        .then(data => {
                            if (data.success) {
                                // Show success message
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Booking Successful!',
                                    text: 'Your booking has been confirmed. Booking ID: ' + data.booking_id,
                                    confirmButtonText: 'View Booking Details',
                                    confirmButtonColor: '#10B981'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'booking_details.php?id=' + data.booking_id;
                                    }
                                });
                            } else {
                                throw new Error(data.message || 'Booking failed');
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: error.message || 'Something went wrong!',
                                confirmButtonColor: '#EF4444'
                            });
                        })
                        .finally(() => {
                            submitButton.innerHTML = '<i class="fas fa-lock mr-2"></i>Pay ₹<?php echo number_format($package["price"]); ?>';
                            submitButton.disabled = false;
                        });
                    },
                    // Add modal close handler
                    modal: {
                        ondismiss: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Payment Failed',
                                text: 'Your payment was cancelled or failed. Please try again.',
                                confirmButtonColor: '#EF4444'
                            });
                        }
                    },
                    prefill: {
                        name: "<?php echo htmlspecialchars($_SESSION['name']); ?>",
                        contact: document.querySelector('input[name="phone"]').value
                    },
                    theme: {
                        color: "#10B981"
                    }
                };

                // Initialize Razorpay
                var rzp = new Razorpay(options);
                rzp.open();
            });

            // Helper functions
            function showError(element, message) {
                // Remove existing error message if any
                hideError(element);
                
                // Create and insert error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'text-red-500 text-sm mt-1 error-message';
                errorDiv.textContent = message;
                element.parentNode.appendChild(errorDiv);
            }

            function hideError(element) {
                const existingError = element.parentNode.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }
            }

            // Add this new code for dynamic amount calculation
            const guestsSelect = document.querySelector('select[name="guests"]');
            const submitButton = document.querySelector('.submit-btn');
            const basePrice = <?php echo $package['price']; ?>;
            
            // Function to format currency in Indian format
            function formatIndianCurrency(amount) {
                return new Intl.NumberFormat('en-IN', {
                    style: 'currency',
                    currency: 'INR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(amount).replace('INR', '₹');
            }

            // Function to update total amount
            function updateTotalAmount() {
                const guests = parseInt(guestsSelect.value);
                const totalAmount = basePrice * guests;
                submitButton.innerHTML = `<i class="fas fa-lock mr-2"></i>Pay ${formatIndianCurrency(totalAmount)}`;
                
                // Add or update the total amount display
                let amountDisplay = document.getElementById('totalAmountDisplay');
                if (!amountDisplay) {
                    amountDisplay = document.createElement('div');
                    amountDisplay.id = 'totalAmountDisplay';
                    amountDisplay.className = 'text-center mt-4 text-gray-600';
                    submitButton.parentNode.insertBefore(amountDisplay, submitButton);
                }
                amountDisplay.innerHTML = `
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Price per person: ${formatIndianCurrency(basePrice)}</p>
                        <p class="text-sm text-gray-600">Number of guests: ${guests}</p>
                        <p class="text-lg font-bold text-green-600 mt-2">Total Amount: ${formatIndianCurrency(totalAmount)}</p>
                    </div>
                `;

                // Update Razorpay amount
                options.amount = totalAmount * 100;
            }

            // Add event listener for guests select
            guestsSelect.addEventListener('change', updateTotalAmount);

            // Initial calculation
            updateTotalAmount();

            // Update the existing Razorpay options
            var options = {
                // ... existing Razorpay options ...
                amount: basePrice * parseInt(guestsSelect.value) * 100, // Update initial amount
                // ... rest of the options ...
            };
        });
    </script>

    <!-- Add SweetAlert2 for better alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="text-2xl font-bold text-green-600">TravelEase</div>
                <div class="hidden md:flex space-x-8">
                    <a href="home.php" class="text-gray-700 hover:text-blue-600">Home</a>
                    <a href="userpackages.php" class="text-gray-700 hover:text-blue-600">Packages</a>
                    <a href="#" class="text-gray-700 hover:text-blue-600">About</a>
                    <a href="#" class="text-gray-700 hover:text-blue-600">Contact</a>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-800 mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Booking Form Section -->
    <div class="booking-container min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4">
            <div class="form-card bg-white rounded-xl shadow-xl p-8">
                <h1 class="text-4xl font-bold text-center mb-2 text-gray-800">Book Your Dream Vacation</h1>
                <p class="text-center text-gray-600 mb-8">Complete your booking in just a few simple steps</p>
                
                <div class="package-info mb-8 p-6 bg-gray-50 rounded-lg">
                    <h2 class="text-2xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-suitcase-rolling feature-icon"></i>
                        <?php echo htmlspecialchars($package['package_name']); ?>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center">
                            <i class="fas fa-clock feature-icon"></i>
                            <span class="text-gray-600"><?php echo htmlspecialchars($package['duration']); ?> Days</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-tag feature-icon"></i>
                            <span class="text-2xl font-bold text-green-600">₹<?php echo number_format($package['price']); ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt feature-icon"></i>
                            <span class="text-gray-600"><?php echo htmlspecialchars($package['destination']); ?></span>
                        </div>
                    </div>
                </div>

                <form id="bookingForm" action="process_booking.php" method="POST" class="space-y-8" enctype="multipart/form-data">
                    <input type="hidden" name="package_id" value="<?php echo htmlspecialchars($package_id); ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="input-group">
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="far fa-calendar-alt feature-icon"></i>Travel Date
                            </label>
                            <input type="date" name="travel_date" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none">
                        </div>
                        
                        <div class="input-group">
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-users feature-icon"></i>Number of Guests
                            </label>
                            <select name="guests" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none transition-all duration-300">
                                <?php for($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-phone feature-icon"></i>Phone Number
                            </label>
                            <input type="tel" name="phone" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none"
                                placeholder="Enter your phone number">
                        </div>
                    </div>

                    <!-- Add hidden field for payment ID -->
                    <input type="hidden" name="payment_id" id="payment_id">

                    <div class="mt-8">
                        <button type="submit" class="submit-btn w-full bg-green-600 text-white py-4 px-6 rounded-lg hover:bg-green-700 font-semibold text-lg transition-all duration-300">
                            <i class="fas fa-lock mr-2"></i>Pay ₹<?php echo number_format($package['price']); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center">
                <p>© 2024 TravelEase. All rights reserved.</p>
                <p class="mt-2">For support, contact: support@travelease.com</p>
            </div>
        </div>
    </footer>
</body>
</html> 