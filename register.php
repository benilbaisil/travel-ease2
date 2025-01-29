<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "travel_booking";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $user_role = 'Client'; // Default role for new registrations

    // Check if email already exists
    $check_email = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);

    if ($result->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        // Insert new user
        $sql = "INSERT INTO users (name, email, password, phone_number, user_role) 
                VALUES ('$name', '$email', '$password', '$phone', '$user_role')";

        if ($conn->query($sql) === TRUE) {
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php"); // Redirect to login page
            exit();
        } else {
            $error = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravelEase - Register</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        .login-container {
            background-image: url('/travel ease/img/beautiful-shot-mountains-cloudy-sky-from-inside-plane-windows.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            width: 100%;
        }
        
        .glass-form {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.8);
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.2);
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
        }

        .error-message {
            color: #ff4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
        }

        .form-input.error {
            border-color: #ff4444;
        }
    </style>
</head>
<body>
    <div class="login-container flex items-center justify-center py-8">
        <div class="glass-form p-8 w-full max-w-md mx-4">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Join TravelEase</h1>
                <p class="text-gray-200">Start your journey with us</p>
            </div>

        

            <form method="POST" action="" class="space-y-4" id="registrationForm" novalidate>
                <div>
                    <label for="name" class="block text-white text-sm font-medium mb-2">Full Name</label>
                    <input type="text" id="name" name="name" required
                        class="form-input w-full px-4 py-3 rounded-lg"
                        placeholder="Enter your full name">
                    <span class="error-message" id="nameError"></span>
                </div>

                <div>
                    <label for="email" class="block text-white text-sm font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" required
                        class="form-input w-full px-4 py-3 rounded-lg"
                        placeholder="Enter your email">
                    <span class="error-message" id="emailError"></span>
                </div>

                <div>
                    <label for="password" class="block text-white text-sm font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" required
                        class="form-input w-full px-4 py-3 rounded-lg"
                        placeholder="Create a password">
                    <span class="error-message" id="passwordError"></span>
                </div>

                <div>
                    <label for="confirm_password" class="block text-white text-sm font-medium mb-2">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                        class="form-input w-full px-4 py-3 rounded-lg"
                        placeholder="Confirm your password">
                    <span class="error-message" id="confirmPasswordError"></span>
                </div>

      

           

                <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition duration-300 mt-6">
                    Create Account
                </button>

                <div class="text-center text-white mt-4">
                    <p>Already have an account? <a href="login.php" class="text-blue-300 hover:text-blue-200">Sign in here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const inputs = {
                name: {
                    element: document.getElementById('name'),
                    error: document.getElementById('nameError'),
                    validate: (value) => {
                        if (value.length < 3) return 'Name must be at least 3 characters long';
                        if (!/^[a-zA-Z\s]*$/.test(value)) return 'Name can only contain letters and spaces';
                        return '';
                    }
                },
                email: {
                    element: document.getElementById('email'),
                    error: document.getElementById('emailError'),
                    validate: (value) => {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) return 'Please enter a valid email address';
                        return '';
                    }
                },
                password: {
                    element: document.getElementById('password'),
                    error: document.getElementById('passwordError'),
                    validate: (value) => {
                        if (value.length < 8) return 'Password must be at least 8 characters long';
                        if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
                            return 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
                        }
                        return '';
                    }
                },
                confirm_password: {
                    element: document.getElementById('confirm_password'),
                    error: document.getElementById('confirmPasswordError'),
                    validate: (value) => {
                        const password = document.getElementById('password').value;
                        if (value !== password) return 'Passwords do not match';
                        return '';
                    }
                },
                
            };

            // Add validation listeners to each input
            Object.keys(inputs).forEach(inputName => {
                const input = inputs[inputName];
                
                input.element.addEventListener('input', function() {
                    validateField(inputName);
                });

                input.element.addEventListener('blur', function() {
                    validateField(inputName);
                });
            });

            function validateField(fieldName) {
                const input = inputs[fieldName];
                const error = input.validate(input.element.value.trim());
                
                if (error) {
                    input.element.classList.add('error');
                    input.error.style.display = 'block';
                    input.error.textContent = error;
                    return false;
                } else {
                    input.element.classList.remove('error');
                    input.error.style.display = 'none';
                    input.error.textContent = '';
                    return true;
                }
            }

            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                Object.keys(inputs).forEach(inputName => {
                    if (!validateField(inputName)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html> 