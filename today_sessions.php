<?php
require_once 'config/database.php';
session_start();
$today_sessions = [];
$error_message = '';

try {
    $conn = getDBConnection();
    
    // الحصول على تاريخ اليوم
    $today = date('Y-m-d');
    
    // البحث عن جلسات اليوم
    $query = "SELECT s.*, c.case_number, c.case_type, c.description
              FROM sessions s 
              JOIN cases c ON s.case_id = c.case_id 
              WHERE s.session_date = :today 
              ORDER BY s.session_time ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    
    $today_sessions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = 'حدث خطأ في تحميل جلسات اليوم. يرجى المحاولة مرة أخرى.';
}

// تجميع الجلسات حسب الوقت
$sessions_by_time = [];
foreach ($today_sessions as $session) {
    $time_key = $session['session_time'];
    if (!isset($sessions_by_time[$time_key])) {
        $sessions_by_time[$time_key] = [];
    }
    $sessions_by_time[$time_key][] = $session;
}
ksort($sessions_by_time);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جلسات اليوم - نظام إدارة جلسات المحكمة</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <h1><i class="fas fa-calendar-day"></i> جلسات اليوم</h1>
            <p>جميع الجلسات المجدولة لتاريخ <?php echo formatDate(isset($today) ? $today : date('Y-m-d'), 'd/m/Y'); ?></p>       </header>

        <!-- Navigation -->
        <nav class="nav fade-in">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i> الرئيسية
            </a>
            <a href="search.php" class="nav-link">
                <i class="fas fa-search"></i> البحث عن قضية
            </a>
            <a href="today_sessions.php" class="nav-link active">
                <i class="fas fa-calendar-day"></i> جلسات اليوم
            </a>
             <?php
   if (isset($_SESSION['admin_logged_in'])) {
  
  
?>
            <!-- <a href="admin/login.php" class="nav-link">
                <i class="fas fa-user-shield"></i> دخول الإدارة
            </a> -->
           <?php 
        } 
        ?>
        </nav>

        <?php if (!empty($error_message)): ?>
        <!-- Error Message -->
        <div class="alert alert-error fade-in">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <!-- Today's Date Info -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-calendar-check"></i>
                </span>
                معلومات اليوم
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="text-align: center; padding: 20px; background: rgba(52, 152, 219, 0.1); border-radius: 12px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #3498db;">
                        <?php echo count($today_sessions); ?>
                    </div>
                    <div style="color: #7f8c8d; margin-top: 5px;">إجمالي الجلسات</div>
                </div>
                <div style="text-align: center; padding: 20px; background: rgba(39, 174, 96, 0.1); border-radius: 12px;">
                    <div style="font-size: 2rem; font-weight: bold; color: #27ae60;">
                        <?php echo date('d'); ?>
                    </div>
                    <div style="color: #7f8c8d; margin-top: 5px;">اليوم</div>
                </div>
                <div style="text-align: center; padding: 20px; background: rgba(243, 156, 18, 0.1); border-radius: 12px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #f39c12;">
                        <?php 
                        $days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
                        echo $days[date('w')]; 
                        ?>
                    </div>
                    <div style="color: #7f8c8d; margin-top: 5px;">يوم الأسبوع</div>
                </div>
                <div style="text-align: center; padding: 20px; background: rgba(155, 89, 182, 0.1); border-radius: 12px;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #9b59b6;">
                        <?php 
                        $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                                  'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                        echo $months[date('n') - 1]; 
                        ?>
                    </div>
                    <div style="color: #7f8c8d; margin-top: 5px;">الشهر</div>
                </div>
            </div>
        </div>

        <?php if (empty($today_sessions)): ?>
        <!-- No Sessions Message -->
        <div class="card fade-in">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-calendar-times" style="font-size: 4rem; color: #bdc3c7; margin-bottom: 20px;"></i>
                <h3 style="color: #7f8c8d; margin-bottom: 15px;">لا توجد جلسات مجدولة لهذا اليوم</h3>
                <p style="color: #95a5a6; margin-bottom: 30px;">
                    لا توجد جلسات محكمة مجدولة لتاريخ اليوم. يمكنك التحقق من الجلسات القادمة أو البحث عن قضية محددة.
                </p>
                <div>
                    <a href="search.php" class="btn btn-primary" style="margin-left: 10px;">
                        <i class="fas fa-search"></i> البحث عن قضية
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> العودة للرئيسية
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>

        <!-- Sessions Timeline -->
        <?php foreach ($sessions_by_time as $time => $sessions): ?>
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-clock"></i>
                </span>
                الساعة <?php echo formatTime($time); ?>
            </h2>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>رقم القضية</th>
                            <th>نوع القضية</th>
                            <th>مكان الجلسة</th>
                            <th>حالة الجلسة</th>
                            <th>ملاحظات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                        <tr>
                            <td>
                                <strong style="color: #667eea;">
                                    <?php echo htmlspecialchars($session['case_number']); ?>
                                </strong>
                            </td>
                            <td>
                                <span style="padding: 5px 10px; background: rgba(102, 126, 234, 0.1); border-radius: 15px; font-size: 12px; color: #667eea;">
                                    <?php echo htmlspecialchars($session['case_type']); ?>
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-map-marker-alt" style="color: #e74c3c;"></i>
                                <?php echo htmlspecialchars($session['session_location']); ?>
                            </td>
                            <td>
                                <?php
                                $status_class = 'status-scheduled';
                                $status_icon = 'fas fa-clock';
                                switch ($session['session_status']) {
                                    case 'منعقدة':
                                        $status_class = 'status-held';
                                        $status_icon = 'fas fa-check-circle';
                                        break;
                                    case 'مؤجلة':
                                        $status_class = 'status-postponed';
                                        $status_icon = 'fas fa-exclamation-triangle';
                                        break;
                                    case 'ملغية':
                                        $status_class = 'status-cancelled';
                                        $status_icon = 'fas fa-times-circle';
                                        break;
                                }
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <i class="<?php echo $status_icon; ?>"></i>
                                    <?php echo htmlspecialchars($session['session_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($session['notes'])): ?>
                                    <span title="<?php echo htmlspecialchars($session['notes']); ?>">
                                        <?php echo htmlspecialchars(substr($session['notes'], 0, 50)) . (strlen($session['notes']) > 50 ? '...' : ''); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #bdc3c7;">لا توجد ملاحظات</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="search.php?case_number=<?php echo urlencode($session['case_number']); ?>" 
                                   class="btn btn-primary" 
                                   style="padding: 8px 15px; font-size: 12px;">
                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Summary Card -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-chart-pie"></i>
                </span>
                ملخص جلسات اليوم
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php
                $status_counts = [];
                foreach ($today_sessions as $session) {
                    $status = $session['session_status'];
                    $status_counts[$status] = (isset($status_counts[$status]) ? $status_counts[$status] : 0) + 1;
                }
                
                $status_colors = [
                    'مجدولة' => ['color' => '#3498db', 'bg' => 'rgba(52, 152, 219, 0.1)', 'icon' => 'fas fa-clock'],
                    'منعقدة' => ['color' => '#27ae60', 'bg' => 'rgba(39, 174, 96, 0.1)', 'icon' => 'fas fa-check-circle'],
                    'مؤجلة' => ['color' => '#f39c12', 'bg' => 'rgba(243, 156, 18, 0.1)', 'icon' => 'fas fa-exclamation-triangle'],
                    'ملغية' => ['color' => '#e74c3c', 'bg' => 'rgba(231, 76, 60, 0.1)', 'icon' => 'fas fa-times-circle']
                ];
                
                foreach ($status_counts as $status => $count):
                    $style = $status_colors[$status] or $status_colors['مجدولة'];
                ?>
                <div style="text-align: center; padding: 20px; background: <?php echo $style['bg']; ?>; border-radius: 12px;">
                    <div style="font-size: 2rem; font-weight: bold; color: <?php echo $style['color']; ?>;">
                        <i class="<?php echo $style['icon']; ?>"></i>
                        <?php echo $count; ?>
                    </div>
                    <div style="color: #7f8c8d; margin-top: 5px;"><?php echo $status; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php endif; ?>

        <!-- Refresh Button -->
        <div class="card fade-in">
            <div style="text-align: center;">
                <button onclick="location.reload()" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> تحديث الجلسات
                </button>
                <p style="margin-top: 15px; color: #7f8c8d; font-size: 14px;">
                    آخر تحديث: <?php echo date('H:i:s'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="text-align: center; padding: 30px; color: rgba(255, 255, 255, 0.8); margin-top: 50px;">
        <p>&copy; 2024 نظام إدارة جلسات المحكمة. جميع الحقوق محفوظة.</p>
    </footer>

    <script>
        // تحديث تلقائي كل 5 دقائق
        setInterval(function() {
            location.reload();
        }, 300000); // 5 دقائق

        // إضافة مؤثرات بصرية للجداول
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.table tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = (index * 0.1) + 's';
                row.classList.add('fade-in');
            });
        });

        // تحسين عرض الملاحظات الطويلة
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', function() {
                this.style.cursor = 'help';
            });
        });
    </script>
</body>
</html>

