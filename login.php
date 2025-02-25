<?php
session_start();
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
    </style>
</head>
<body>
    <div class="login-container flex items-center justify-center">
        <div class="glass-form p-8 w-full max-w-md mx-4">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-blue mb-2">Welcome Back</h1>
                <p class="text-black-200">Sign in to continue your journey</p>
    </div>


            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="email" class="block text-white text-sm font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" required
                        class="form-input w-full px-4 py-3 rounded-lg"
                        placeholder="Enter your email">
                </div>

                <div>
                    <label for="password" class="block text-white text-sm font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" required
                        class="form-input w-full px-4 py-3 rounded-lg"
                        placeholder="Enter your password">
                </div>
                <?php if (!empty($error)) { ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php } ?>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" class="h-4 w-4 rounded border-gray-300">
                        <label for="remember" class="ml-2 block text-sm text-white">Remember me</label>
                    </div>
                    <a href="forgotpassword.php" class="text-sm text-white hover:text-blue-200">Forgot password?</a>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition duration-300">
                    Sign In
                </button>

                <div class="text-center text-white">
                    <p>Don't have an account? <a href="register.php" class="text-blue-300 hover:text-blue-200">Register here</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
