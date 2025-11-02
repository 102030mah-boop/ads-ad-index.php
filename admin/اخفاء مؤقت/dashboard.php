<?php
session_start();
require_once '../config/database.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$statistics = [];
$recent_sessions = [];

try {
    $conn = getDBConnection();
    
    // جمع الإحصائيات
    $stats_queries = [
        'total_cases' => "SELECT COUNT(*) as count FROM cases",
        'total_sessions' => "SELECT COUNT(*) as count FROM sessions",
        'today_sessions' => "SELECT COUNT(*) as count FROM sessions WHERE session_date = CURDATE()",
        'upcoming_sessions' => "SELECT COUNT(*) as count FROM sessions WHERE session_date > CURDATE()",
        'held_sessions' => "SELECT COUNT(*) as count FROM sessions WHERE session_status = 'منعقدة'",
        'postponed_sessions' => "SELECT COUNT(*) as count FROM sessions WHERE session_status = 'مؤجلة'"
    ];
    
    foreach ($stats_queries as $key => $query) {
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();
        $statistics[$key] = $result['count'];
    }
    
    // الجلسات الأخيرة
    $recent_query = "SELECT s.*, c.case_number, c.case_type 
                    FROM sessions s 
                    JOIN cases c ON s.case_id = c.case_id 
                    ORDER BY s.created_at DESC 
                    LIMIT 10";
    
    $recent_stmt = $conn->prepare($recent_query);
    $recent_stmt->execute();
    $recent_sessions = $recent_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = 'حدث خطأ في تحميل البيانات.';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - نظام إدارة جلسات المحكمة</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <h1><i class="fas fa-tachometer-alt"></i> لوحة التحكم الإدارية</h1>
            <p>مرحباً <?php echo htmlspecialchars($_SESSION['admin_name']); ?> - إدارة نظام جلسات المحكمة</p>
        </header>

        <!-- Admin Navigation -->
        <nav class="nav fade-in">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
            </a>
            <a href="manage_sessions.php" class="nav-link">
                <i class="fas fa-calendar-alt"></i> إدارة الجلسات
            </a>
            <a href="manage_cases.php" class="nav-link">
                <i class="fas fa-folder-open"></i> إدارة القضايا
            </a>
            <a href="../index.php" class="nav-link">
                <i class="fas fa-eye"></i> عرض الموقع
            </a>
            <a href="logout.php" class="nav-link" style="background: #e74c3c; color: white;">
                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
            </a>
        </nav>

        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="card fade-in">
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 3rem; color: #3498db; margin-bottom: 10px;">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; color: #3498db;">
                        <?php echo isset($statistics["total_cases"]) ? $statistics["total_cases"] : 0; ?>
                    </div>
                    <div style="color: #7f8c8d;">إجمالي القضايا</div>
                </div>
            </div>

            <div class="card fade-in">
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 3rem; color: #27ae60; margin-bottom: 10px;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; color: #27ae60;">
                        <?php echo $statistics['total_sessions'] ; ?>
                    </div>
                    <div style="color: #7f8c8d;">إجمالي الجلسات</div>
                </div>
            </div>

            <div class="card fade-in">
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 3rem; color: #f39c12; margin-bottom: 10px;">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; color: #f39c12;">
                        <?php echo $statistics['today_sessions'] ; ?>
                    </div>
                    <div style="color: #7f8c8d;">جلسات اليوم</div>
                </div>
            </div>

            <div class="card fade-in">
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 3rem; color: #9b59b6; margin-bottom: 10px;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div style="font-size: 2rem; font-weight: bold; color: #9b59b6;">
                        <?php echo $statistics['upcoming_sessions'] ; ?>
                    </div>
                    <div style="color: #7f8c8d;">الجلسات القادمة</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-bolt"></i>
                </span>
                إجراءات سريعة
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                <a href="manage_sessions.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إضافة جلسة جديدة
                </a>
                <a href="manage_cases.php?action=add" class="btn btn-success">
                    <i class="fas fa-folder-plus"></i> إضافة قضية جديدة
                </a>
                <a href="manage_sessions.php?filter=today" class="btn btn-secondary">
                    <i class="fas fa-calendar-day"></i> جلسات اليوم
                </a>
                <a href="manage_sessions.php?filter=postponed" class="btn" style="background: #f39c12; color: white;">
                    <i class="fas fa-exclamation-triangle"></i> الجلسات المؤجلة
                </a>
            </div>
        </div>

        <!-- Session Status Overview -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-chart-pie"></i>
                </span>
                نظرة عامة على حالات الجلسات
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="text-align: center; padding: 20px; background: rgba(39, 174, 96, 0.1); border-radius: 12px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #27ae60;">
                        <?php echo $statistics['held_sessions'] ; ?>
                    </div>
                    <div style="color: #7f8c8d; margin-top: 5px;">جلسات منعقدة</div>
                </div>
                <div style="text-align: center; padding: 20px; background: rgba(243, 156, 18, 0.1); border-radius: 12px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #f39c12;">
                        <?php echo $statistics['postponed_sessions'] ; ?>
                    </div>
                    <div style="color: #7f8c8d; margin-top: 5px;">جلسات مؤجلة</div>
                </div>
                <div style="text-align: center; padding: 20px; background: rgba(52, 152, 219, 0.1); border-radius: 12px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #3498db;">
                        <?php echo ($statistics['total_sessions'] ) - ($statistics['held_sessions'] ) - ($statistics['postponed_sessions'] ); ?>
                    </div>
                    <div style="color: #7f8c8d; margin-top: 5px;">جلسات مجدولة</div>
                </div>
            </div>
        </div>

        <!-- Recent Sessions -->
        <?php if (!empty($recent_sessions)): ?>
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-history"></i>
                </span>
                الجلسات الأخيرة
            </h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>رقم القضية</th>
                            <th>نوع القضية</th>
                            <th>تاريخ الجلسة</th>
                            <th>وقت الجلسة</th>
                            <th>المكان</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recent_sessions, 0, 5) as $session): ?>
                        <tr>
                            <td>
                                <strong style="color: #667eea;">
                                    <?php echo htmlspecialchars($session['case_number']); ?>
                                </strong>
                            </td>
                            <td><?php echo htmlspecialchars($session['case_type']); ?></td>
                            <td><?php echo formatDate($session['session_date'], 'd/m/Y'); ?></td>
                            <td><?php echo formatTime($session['session_time']); ?></td>
                            <td><?php echo htmlspecialchars($session['session_location']); ?></td>
                            <td>
                                <?php
                                $status_class = 'status-scheduled';
                                switch ($session['session_status']) {
                                    case 'منعقدة':
                                        $status_class = 'status-held';
                                        break;
                                    case 'مؤجلة':
                                        $status_class = 'status-postponed';
                                        break;
                                    case 'ملغية':
                                        $status_class = 'status-cancelled';
                                        break;
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($session['session_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="manage_sessions.php?action=edit&id=<?php echo $session['session_id']; ?>" 
                                   class="btn btn-primary" 
                                   style="padding: 5px 10px; font-size: 12px;">
                                    <i class="fas fa-edit"></i> تعديل
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="manage_sessions.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i> عرض جميع الجلسات
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- System Information -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-info-circle"></i>
                </span>
                معلومات النظام
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <div>
                    <strong>المستخدم الحالي:</strong>
                    <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                </div>
                <div>
                    <strong>وقت تسجيل الدخول:</strong>
                    <?php echo date('H:i:s'); ?>
                </div>
                <div>
                    <strong>تاريخ اليوم:</strong>
                    <?php echo date('d/m/Y'); ?>
                </div>
                <div>
                    <strong>إصدار النظام:</strong>
                    v1.0.0
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="text-align: center; padding: 30px; color: rgba(255, 255, 255, 0.8); margin-top: 50px;">
        <p>&copy; 2024 نظام إدارة جلسات المحكمة. جميع الحقوق محفوظة.</p>
    </footer>

    <script>
        // تحديث تلقائي للإحصائيات كل 5 دقائق
        setInterval(function() {
            location.reload();
        }, 300000);

        // إضافة تأثيرات بصرية
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
            });
        });
    </script>
</body>
</html>

