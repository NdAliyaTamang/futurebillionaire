<?php
// Enable full error reporting for debugging during development
// IMPORTANT: Disable in production by setting display_errors to 0
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start session to manage user login state and persist authentication
// Sessions store user data across multiple page requests
session_start();

// Include required files for database connection and authentication functions
// __DIR__ ensures correct path resolution regardless of server configuration
require_once __DIR__ . '/../includes/db.php';           // Database connection setup
require_once __DIR__ . '/../includes/auth_model.php';   // Authentication logic and user verification

// Initialize variables for error message and username persistence
$error = '';      // Stores authentication error messages for user feedback
$username = '';   // Preserves username input after failed login attempts (UX improvement)

// Check if form was submitted via POST method
// POST method is used for form submissions that modify server state (login)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve form data with security considerations:
    // trim() removes leading/trailing whitespace that could cause validation issues
    // Null coalescing operator (??) provides default values to prevent undefined index warnings
    $username = trim($_POST['username'] ?? '');   // Username input (trimmed for consistency)
    $password = $_POST['password'] ?? '';         // Password input (not trimmed to preserve possible spaces)
    $role = $_POST['role'] ?? 'Staff';            // Role selection with 'Staff' as default

    // Validate required fields before processing authentication
    // Early validation prevents unnecessary database queries
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password"; // User-friendly error message
    } else {
        // Verify user credentials against database using authentication model
        // This function should handle password hashing/verification securely
        $user = verifyUser($username, $password, $role);
        
        // Check if authentication was successful (returns user data array)
        if ($user) {
            // If authentication successful, set session variables for user state management
            // Session variables persist until browser closes or session times out
            $_SESSION['userID'] = $user['UserID'];          // Unique user identifier for database operations
            $_SESSION['username'] = $user['Username'];      // Display name for UI personalization
            $_SESSION['role'] = $user['Role'];              // Role for permission-based access control
            $_SESSION['LAST_ACTIVITY'] = time();            // Track session activity for timeout management
            
            // IMPORTANT SECURITY: Regenerate session ID to prevent session fixation attacks
            // This should be added: session_regenerate_id(true);
            
            // Redirect user to appropriate dashboard based on role
            // Using absolute path would be better: header("Location: /path/to/staff_list.php")
            header("Location: staff_list.php");
            exit(); // CRITICAL: Always exit after header redirect to prevent further code execution
        } else {
            // Authentication failed - set generic error message for security
            // Generic message prevents user enumeration attacks (don't reveal which field was wrong)
            $error = "Invalid username, password, or role";
            
            // SECURITY NOTE: Consider implementing login attempt tracking to prevent brute force attacks
            // Example: Increment failed attempt counter, implement CAPTCHA after X failures
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Future Billionaire Academy</title>
    <!-- Bootstrap CSS for responsive styling and pre-built components -->
    <!-- Using CDN for simplicity; consider self-hosting for production -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Main background gradient for visual appeal and brand consistency */
        body {
            background: linear-gradient(135deg, #182848, #4b6cb7);
            min-height: 100vh;                 /* Full viewport height */
            display: flex;                     /* Flexbox for centering */
            align-items: center;               /* Vertical centering */
            justify-content: center;           /* Horizontal centering */
            font-family: "Poppins", sans-serif;/* Modern font family */
            margin: 0;                         /* Remove default margin */
            padding: 20px;                     /* Add padding for mobile devices */
        }
        /* Glass morphism effect for login container - modern UI design */
        .login-container {
            background: rgba(255, 255, 255, 0.1); /* Semi-transparent white background */
            backdrop-filter: blur(10px);          /* Frosted glass effect (may not work in all browsers) */
            border-radius: 15px;                  /* Rounded corners for modern look */
            padding: 40px;                        /* Internal spacing */
            width: 100%;                          /* Responsive width */
            max-width: 400px;                     /* Maximum width for readability */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); /* Depth shadow for visual hierarchy */
            color: white;                         /* Text color for contrast on dark background */
            border: 1px solid rgba(255, 255, 255, 0.2); /* Subtle border for definition */
        }
        /* Header styling with accent color for brand recognition */
        .login-header {
            text-align: center;                   /* Center align header content */
            margin-bottom: 30px;                  /* Space below header */
        }
        .login-header h2 {
            color: #f1c40f;                       /* Brand accent color (yellow/gold) */
            font-weight: 600;                     /* Semi-bold for emphasis */
        }
        /* Custom form control styling matching the glass morphism theme */
        .form-control {
            background: rgba(255, 255, 255, 0.1); /* Transparent background */
            border: 1px solid rgba(255, 255, 255, 0.3); /* Light border */
            color: white;                         /* White text for contrast */
            border-radius: 8px;                   /* Slightly rounded corners */
            padding: 12px;                        /* Comfortable input padding */
            transition: all 0.3s ease;            /* Smooth transition for interactive states */
        }
        /* Focus state for form inputs - visual feedback for accessibility */
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15); /* Slightly brighter on focus */
            border-color: #f1c40f;                /* Brand color border on focus */
            color: white;                         /* Maintain text contrast */
            box-shadow: 0 0 0 0.2rem rgba(241, 196, 15, 0.25); /* Bootstrap-like focus ring */
            outline: none;                        /* Remove default browser outline */
        }
        /* Placeholder text color for better visibility */
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);      /* Semi-transparent white */
        }
        /* Login button with gradient and hover effects for clear call-to-action */
        .btn-login {
            background: linear-gradient(135deg, #f1c40f, #f39c12); /* Gradient matching brand */
            border: none;                          /* Remove default border */
            color: #182848;                        /* Dark text for contrast on light button */
            font-weight: 600;                      /* Bold text for emphasis */
            padding: 12px;                         /* Comfortable click area */
            border-radius: 8px;                    /* Rounded corners matching inputs */
            width: 100%;                           /* Full width of container */
            margin-top: 10px;                      /* Space above button */
            cursor: pointer;                       /* Pointer cursor for interactivity */
            transition: all 0.3s ease;             /* Smooth hover transition */
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #f39c12, #e67e22); /* Darker gradient on hover */
            color: #182848;                        /* Maintain text color */
            transform: translateY(-2px);           /* Subtle lift effect on hover */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); /* Enhanced shadow on hover */
        }
        /* Alert box styling for error messages */
        .alert {
            border-radius: 8px;                    /* Rounded corners matching design system */
            border: none;                          /* Remove default border */
            margin-bottom: 20px;                   /* Space below alert */
        }
        /* Improve accessibility for focus states */
        .btn-login:focus {
            outline: 2px solid #f1c40f;            /* Visible focus indicator for keyboard users */
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <!-- Main login container with glass morphism effect -->
    <div class="login-container">
        <div class="login-header">
            <h2>Future Billionaire Academy</h2>
            <p class="mb-0">Staff Portal Login</p>
        </div>

        <!-- Display error message if authentication fails -->
        <!-- Using htmlspecialchars() to prevent XSS attacks from user input -->
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Login form - POST method for security (parameters not in URL) -->
        <!-- Empty action attribute posts to same page (self-processing form) -->
        <form method="POST" action="">
            <!-- Username input field -->
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username); ?>" 
                       placeholder="Enter your username" required
                       autocomplete="username"> <!-- Helps password managers -->
            </div>

            <!-- Password input field -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Enter your password" required
                       autocomplete="current-password"> <!-- Helps password managers -->
            </div>

            <!-- Role selection dropdown -->
            <!-- Note: In production, role should be determined by database, not user selection -->
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="Staff" <?php echo ($_POST['role'] ?? 'Staff') === 'Staff' ? 'selected' : ''; ?>>Staff</option>
                    <option value="Admin" <?php echo ($_POST['role'] ?? '') === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
                <!-- SECURITY NOTE: Allowing user to select role is unusual - typically role is determined by database -->
                <!-- Consider removing this dropdown and determining role from user record -->
            </div>

            <!-- Submit button for form submission -->
            <button type="submit" class="btn btn-login" aria-label="Login to staff portal">Login</button>
        </form>

        <!-- Additional information for users -->
        <div class="text-center mt-3">
            <small class="text-muted">Use your assigned credentials to access the system</small>
            <!-- Consider adding password reset link if feature exists -->
            <!-- <br><a href="forgot_password.php" style="color: #f1c40f;">Forgot Password?</a> -->
        </div>
    </div>
    
    <!-- Optional: Add JavaScript for enhanced UX -->
    <!-- <script>
        // Example: Focus on username field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script> -->
</body>
</html>