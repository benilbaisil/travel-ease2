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
    // Add error variable to store errors
    $error = "";
    
    // Validate that all required fields are present
    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password'])) {
        $error = "All fields are required";
    } else {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_role = 'Client';

        // Check if email already exists
        $check_email = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            // Insert new user using prepared statement
            $sql = "INSERT INTO users (name, email, password, user_role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $password, $user_role);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit();
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
    
    // If there's an error, display it
    if (!empty($error)) {
        echo "<script>alert('$error');</script>";
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .login-container {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                url('img/flat-lay-travel-essentials-with-copy-space.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            width: 100%;
        }
        
        .glass-form {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(15px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        .form-input {
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 300;
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.25);
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        }

        .error-message {
            color: #ff6b6b;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
            font-weight: 500;
        }

        .form-input.error {
            border-color: #ff6b6b;
            background: rgba(255, 107, 107, 0.1);
        }

        button[type="submit"] {
            background: linear-gradient(45deg, #3b82f6, #2563eb);
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        button[type="submit"]:hover {
            background: linear-gradient(45deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-container flex items-center justify-center py-8">
        <div class="glass-form p-8 w-full max-w-md mx-4">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">Join TravelEase</h1>
                <p class="text-blue-100 text-lg">Start your journey with us</p>
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
                    <p class="text-gray-200">Already have an account? 
                        <a href="login.php" class="text-blue-300 hover:text-blue-200 font-medium hover:underline transition duration-300">
                            Sign in here
                        </a>
                    </p>
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