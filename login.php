<?php
session_start();

// If already logged in, redirect to appropriate page
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'Admin' || $_SESSION['user_role'] == 'Staff') {
        header("Location: admin_dashboard.php"); // Redirect to dashboard instead of packages
    } else {
        header("Location: index.php");
    }
    exit();
}

require_once 'db_connect.php';

$servername = "localhost";
$username = "root"; // Change if needed
$password = ""; // Change if needed
$database = "travel_booking"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);
$error="";

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // First check if user is active (if active column exists)
        try {
            if (isset($user['active']) && $user['active'] == 0) {
                $error = "Your account has been deactivated. Please contact the administrator.";
            } else if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['name'] = $user['name'];
                echo $user["user_role"];
                
                // Redirect based on user type
                if($user['user_role']=="Admin"){
                    header("Location:admin_dashboard.php");
                }
                elseif($user['user_role'] == 'Staff') { 
                    header("Location: staff_dashboard.php");
                } else {
                    header("Location: home.php");
                }
                exit();
            } else {
                $error="Invalid password";
            }
        } catch (Exception $e) {
            // If active column doesn't exist, proceed with normal login
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['name'] = $user['name'];
                echo $user["user_role"];
                
                // Redirect based on user type
                if($user['user_role']=="Admin"){
                    header("Location:admin_dashboard.php");
                }
                elseif($user['user_role'] == 'Staff') {
                    header("Location: staff_dashboard.php");
                } else {
                    header("Location: home.php");
                }
                exit();
            } else {
                $error="Invalid password";
            }
        }
    } else {
        $error= "User not found";
    }
    
    $stmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravelEase - Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .login-container {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                            url('img/travel-suitcase-preparations-packing.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            width: 100%;
        }
        
        .glass-form {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        .form-input {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            transition: all 0.3s ease;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.15);
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        .submit-btn {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            padding: 1rem;
            border-radius: 0.5rem;
            color: #fee2e2;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="login-container flex items-center justify-center p-4">
        <div class="glass-form p-8 w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-3">Welcome Back</h1>
                <p class="text-gray-200 text-lg">Sign in to continue your journey</p>
            </div>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="email" class="block text-white text-sm font-medium mb-2">Email Address</label>
                    <input type="email" id="email" name="email" required
                        class="form-input w-full px-4 py-3 rounded-lg text-lg"
                        placeholder="Enter your email">
                </div>

                <div>
                    <label for="password" class="block text-white text-sm font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" required
                        class="form-input w-full px-4 py-3 rounded-lg text-lg"
                        placeholder="Enter your password">
                </div>

                <?php if (!empty($error)) { ?>
                    <div class="error-message">
                        <?php echo $error; ?>
                    </div>
                <?php } ?>

                <div class="flex items-center justify-end">
                    <a href="forgotpassword.php" class="text-sm text-white hover:text-blue-200 transition duration-300">Forgot password?</a>
                </div>

                <button type="submit"
                    class="submit-btn w-full text-white py-4 px-4 rounded-lg text-lg font-semibold">
                    Sign In
                </button>

                <div class="text-center text-white mt-6">
                    <p class="text-gray-200">Don't have an account? 
                        <a href="register.php" class="text-blue-300 hover:text-blue-200 font-medium transition duration-300">Register here</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
