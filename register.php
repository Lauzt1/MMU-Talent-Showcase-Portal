<?php
// register.php - Improved version with security fixes
include('admin/config.php');

$message = "";
$toastClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic input handling
    $username = $_POST['username'];
    $student_id = $_POST['student_id'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $admincode = trim($_POST['admincode']);

    $role = 'user';

        // Check admin code if provided
        if (!empty($admincode)) {
            $stmt = $pdo->prepare("SELECT * FROM admincodes WHERE code = :code");
            $stmt->execute([':code' => $admincode]);

            if ($stmt->rowCount() > 0) {
                $role = 'admin';
            } else {
                $message = "Invalid admin code";
                $toastClass = "#dc3545";
            }
        }

        if (empty($message)) {
            // Check if email already exists
            $checkEmailStmt = $pdo->prepare("SELECT email FROM userdata WHERE email = ?");
            $checkEmailStmt->execute([$email]);

            if ($checkEmailStmt->rowCount() > 0) {
                $message = "Email ID already exists";
                $toastClass = "#007bff";
            } else {
                try {
                    // Store password as plain text
                    $stmt = $pdo->prepare("INSERT INTO userdata (username, student_id, email, password, role) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $student_id, $email, $password, $role]);   

                    $message = "Account created successfully" . ($role === 'admin' ? ' with admin privileges' : '');
                    $toastClass = "#28a745";
                } catch (PDOException $e) {
                    $message = "Error: " . $e->getMessage();
                    $toastClass = "#dc3545";
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
    <link rel="stylesheet" href="styles/register.css">
    <title>Registration</title>
</head>
<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">
        <h2 class="portal-title">MMU Talent Showcase Portal</h2>

        <?php if ($message): ?>
            <div class="toast align-items-center text-white border-0" 
                 role="alert" aria-live="assertive" aria-atomic="true"
                 style="background-color: <?php echo $toastClass; ?>;">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $message; ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                            data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="post" class="form-control mt-5 p-4"
              style="height:auto; width:380px; box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
            
            <div class="row text-center">
                <i class="fa fa-user-circle-o fa-3x mt-1 mb-2" style="color: green;"></i>
                <h5 class="p-4" style="font-weight: 700;">Create Your Account</h5>
            </div>
            
            <div class="mb-2">
                <label for="username"><i class="fa fa-user"></i> User Name</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            
            <div class="mb-2">
                <label for="student_id"><i class="fa fa-id-card"></i> Student ID</label>
                <input type="text" name="student_id" id="student_id" class="form-control" required>
            </div>

            <div class="mb-2 mt-2">
                <label for="email"><i class="fa fa-envelope"></i> Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            
            <div class="mb-2 mt-2">
                <label for="password"><i class="fa fa-lock"></i> Password</label>
                <input type="text" name="password" id="password" class="form-control" required>
            </div>

            <div class="mb-2 mt-2">
                <label for="admincode"><i class="fa fa-lock"></i> Admin Code (optional)</label>
                <input type="text" name="admincode" id="admincode" class="form-control">
            </div>

            <div class="mb-2 mt-3">
                <button type="submit" class="btn btn-success bg-success" style="font-weight: 600;">
                    Create Account
                </button>
            </div>
            
            <div class="mb-2 mt-4">
                <p class="text-center" style="font-weight: 600; color: navy;">
                    I have an Account <a href="./login.php" style="text-decoration: none;">Login</a>
                </p>
            </div>
        </form>
    </div>
    
    <script>
        let toastElList = [].slice.call(document.querySelectorAll('.toast'))
        let toastList = toastElList.map(function (toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 3000 });
        });
        toastList.forEach(toast => toast.show());
    </script>
</body>
</html>
