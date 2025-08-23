<?php
require_once '../config/config.php';

if (is_logged_in()) {
    redirect('../index.php');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // In a real application, you would send an email with a reset link
            $message = 'If an account with that email exists, we have sent you a password reset link.';
        } else {
            // Don't reveal if email exists or not for security
            $message = 'If an account with that email exists, we have sent you a password reset link.';
        }
        
        $stmt->close();
        $db->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-key fa-3x text-success mb-3"></i>
                            <h2 class="text-success fw-bold">Forgot Password?</h2>
                            <p class="text-muted">Enter your email to reset your password</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($message): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 mb-3">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-2">Remember your password? <a href="login.php" class="text-success">Sign in here</a></p>
                            <p class="mb-0">Don't have an account? <a href="register.php" class="text-success">Sign up here</a></p>
                        </div>

                        <hr class="my-4">
                        
                        <div class="text-center">
                            <a href="../index.php" class="btn btn-outline-success">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
