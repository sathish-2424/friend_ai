<?php
session_start();

// Database configuration (customize these values)
$host = 'localhost';
$dbname = 'friend_ai';
$username = 'root';
$password = '';

// Initialize variables
$error_message = '';
$success_message = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // For development - remove in production
    $error_message = "Database connection failed: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Handle Login
    if (isset($_POST['login'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $error_message = "Please fill in all fields.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Redirect to chat page
                    header('Location: chat.php');
                    exit;
                } else {
                    $error_message = "Invalid email or password.";
                }
            } catch(PDOException $e) {
                $error_message = "Login failed. Please try again.";
            }
        }
    }
    
    // Handle Signup
    if (isset($_POST['signup'])) {
        $username = trim($_POST['username']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm-password'];
        
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error_message = "Please fill in all fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error_message = "An account with this email already exists.";
                } else {
                    // Check if username already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error_message = "Username already taken. Please choose another.";
                    } else {
                        // Create new user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$username, $email, $hashed_password]);
                        
                        $success_message = "Account created successfully! Please log in.";
                    }
                }
            } catch(PDOException $e) {
                $error_message = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Signup - FRIEND AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Add a subtle background pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle at top right, rgba(121, 68, 154, 0.1), transparent),
                              radial-gradient(circle at bottom left, rgba(49, 46, 129, 0.1), transparent);
            z-index: -1;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
    </style>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">

    <div class="relative w-full max-w-md mx-auto p-4">
        
        <!-- Display error or success messages -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <div id="login-form" class="bg-white p-8 rounded-2xl shadow-lg w-full transition-transform duration-500">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-2">Welcome Back!</h2>
            <p class="text-center text-gray-500 mb-8">Please enter your details to sign in.</p>

            <form action="" method="POST">
                <input type="hidden" name="login" value="1">
                
                <div class="mb-4">
                    <label for="login-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="login-email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="you@example.com" required>
                </div>

                <div class="mb-6">
                    <div class="flex justify-between items-baseline">
                        <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <a href="forgot-password.php" class="text-sm text-indigo-600 hover:text-indigo-500">Forgot password?</a>
                    </div>
                    <input type="password" id="login-password" name="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="••••••••" required>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-300">
                    Sign In
                </button>
            </form>

            <p class="text-center text-gray-500 mt-8">
                Don't have an account? <a href="#" id="show-signup" class="font-medium text-indigo-600 hover:text-indigo-500">Sign up</a>
            </p>
        </div>

        <!-- Signup Form -->
        <div id="signup-form" class="bg-white p-8 rounded-2xl shadow-lg w-full absolute top-0 left-0 transition-transform duration-500 transform scale-95 opacity-0 -z-10">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-2">Create Account</h2>
            <p class="text-center text-gray-500 mb-8">Let's get you started with a new account.</p>

            <form action="" method="POST">
                <input type="hidden" name="signup" value="1">
                
                <div class="mb-4">
                    <label for="signup-username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" id="signup-username" name="username" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="yourusername" required>
                </div>
                
                <div class="mb-4">
                    <label for="signup-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="signup-email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="you@example.com" required>
                </div>

                <div class="mb-4">
                    <label for="signup-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="signup-password" name="password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="••••••••" required>
                </div>
                
                <div class="mb-6">
                    <label for="signup-confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" id="signup-confirm-password" name="confirm-password" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="••••••••" required>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-300">
                    Create Account
                </button>
            </form>

            <p class="text-center text-gray-500 mt-8">
                Already have an account? <a href="#" id="show-login" class="font-medium text-indigo-600 hover:text-indigo-500">Sign in</a>
            </p>
        </div>
    </div>

    <script>
        // --- DOM Elements ---
        const loginForm = document.getElementById('login-form');
        const signupForm = document.getElementById('signup-form');
        const showSignupBtn = document.getElementById('show-signup');
        const showLoginBtn = document.getElementById('show-login');

        /**
         * Toggles the visibility of the login and signup forms with animations.
         * @param {HTMLElement} formToShow - The form element to display.
         * @param {HTMLElement} formToHide - The form element to hide.
         */
        function toggleForms(formToShow, formToHide) {
            // Hide the form that is currently visible
            formToHide.classList.add('opacity-0', 'scale-95', '-z-10');
            formToHide.classList.remove('opacity-100', 'scale-100', 'z-10');
            
            // Show the new form
            setTimeout(() => {
                formToShow.classList.remove('opacity-0', 'scale-95', '-z-10');
                formToShow.classList.add('opacity-100', 'scale-100', 'z-10');
            }, 300); // Delay should match the transition duration
        }

        // --- Event Listeners ---

        // Event listener to switch from login to the signup form
        showSignupBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent the default anchor link behavior
            toggleForms(signupForm, loginForm);
        });

        // Event listener to switch from signup back to the login form
        showLoginBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent the default anchor link behavior
            toggleForms(loginForm, signupForm);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>

</body>
</html>