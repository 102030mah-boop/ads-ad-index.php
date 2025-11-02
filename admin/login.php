<?php
session_start();
require_once '../config/database.php';

$error_message = '';
$success_message = '';

// التحقق من تسجيل الدخول المسبق
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput(isset($_POST["username"]) ? $_POST["username"] : "");
    $password = isset($_POST["password"]) ? $_POST["password"] : "";
    
    if (empty($username) || empty($password)) {
        $error_message = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        try {
            $conn = getDBConnection();
            
            $query = "SELECT admin_id, username, password_hash, full_name, is_active 
                     FROM admin_users 
                     WHERE username = :username AND is_active = 1";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $admin = $stmt->fetch();
            
            if ($admin && $password == $admin['password_hash']) {
                // تسجيل دخول ناجح
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'اسم المستخدم أو كلمة المرور غير صحيحة'.$password.' '.$admin['password_hash'];
                
            }
        } catch (PDOException $e) {
            $error_message = 'حدث خطأ في النظام. يرجى المحاولة مرة أخرى.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول الإدارة - نظام إدارة جلسات المحكمة</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <h1><i class="fas fa-user-shield"></i> تسجيل دخول الإدارة</h1>
            <p>لوحة التحكم الإدارية لنظام إدارة جلسات المحكمة</p>
        </header>

        <!-- Navigation -->
        <nav class="nav fade-in">
            <a href="../index.php" class="nav-link">
                <i class="fas fa-home"></i> الرئيسية
            </a>
            <a href="../search.php" class="nav-link">
                <i class="fas fa-search"></i> البحث عن قضية
            </a>
            <a href="../today_sessions.php" class="nav-link">
                <i class="fas fa-calendar-day"></i> جلسات اليوم
            </a>
            <a href="login.php" class="nav-link active">
                <i class="fas fa-user-shield"></i> دخول الإدارة
            </a>
        </nav>

        <!-- Login Form -->
        <div class="card fade-in" style="max-width: 500px; margin: 0 auto;">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-sign-in-alt"></i>
                </span>
                تسجيل الدخول
            </h2>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"> اسم المستخدم:</i>
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           placeholder="أدخل اسم المستخدم"
                          
                           required>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> كلمة المرور:
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="أدخل كلمة المرور"
                           required>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                    </button>
                </div>
            </form>
        </div>

        <!-- Demo Credentials -->
      

        <!-- Security Notice -->
      

    <!-- Footer -->
    <footer style="text-align: center; padding: 30px; color: rgba(255, 255, 255, 0.8); margin-top: 50px;">
        <p>&copy; 2025 نظام إدارة جلسات المحكمة. جميع الحقوق محفوظة.</p>
    </footer>

    <script>
        // تحسين تجربة المستخدم
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري تسجيل الدخول...';
            submitBtn.disabled = true;
            
            // إعادة تفعيل الزر في حالة عدم تحميل الصفحة
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // التركيز على حقل اسم المستخدم عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // إظهار/إخفاء كلمة المرور
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
        }
    </script>
</body>
</html>

