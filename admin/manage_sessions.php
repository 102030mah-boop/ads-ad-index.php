<?php
session_start();
require_once '../config/database.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$message = '';
$error_message = '';
$sessions = [];
$cases = [];
$filter = isset($_GET["filter"]) ? $_GET["filter"] : "all";
$action = isset($_GET["action"]) ? $_GET["action"] : "list";
$session_id = isset($_GET["id"]) ? $_GET["id"] : null;

try {
    $conn = getDBConnection();
    
    // معالجة الإجراءات
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $case_id = sanitizeInput($_POST['case_id']);
                    $session_date = sanitizeInput($_POST['session_date']);
                    $session_time = sanitizeInput($_POST['session_time']);
                    $session_location = sanitizeInput($_POST['session_location']);
                    $session_status = sanitizeInput($_POST['session_status']);
                    $notes = sanitizeInput($_POST['notes']);
                    
                    $insert_query = "INSERT INTO sessions (case_id, session_date, session_location,session_time, session_status, notes) 
                                    VALUES (:case_id, :session_date, :session_location,:session_time, :session_status, :notes)";
                    
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bindParam(':case_id', $case_id);
                    $insert_stmt->bindParam(':session_date', $session_date);
                    $insert_stmt->bindParam(':session_time', $session_time);
                    $insert_stmt->bindParam(':session_location', $session_location);
                    $insert_stmt->bindParam(':session_status', $session_status);
                    $insert_stmt->bindParam(':notes', $notes);
                    
                    if ($insert_stmt->execute()) {
                        $message = 'تم إضافة الجلسة بنجاح';
                        $action = 'list';
                    } else {
                        $error_message = 'حدث خطأ في إضافة الجلسة';
                    }
                    break;
                    
                case 'edit':
                    $session_id = sanitizeInput($_POST['session_id']);
                    $case_id = sanitizeInput($_POST['case_id']);
                    $session_date = sanitizeInput($_POST['session_date']);
                    $session_time = sanitizeInput($_POST['session_time']);
                    $session_location = sanitizeInput($_POST['session_location']);
                    $session_status = sanitizeInput($_POST['session_status']);
                    $notes = sanitizeInput($_POST['notes']);
                    
                    $update_query = "UPDATE sessions 
                                    SET case_id = :case_id, session_date = :session_date, session_time = :session_time,
                                        session_location = :session_location, session_status = :session_status, notes = :notes 
                                    WHERE session_id = :session_id";
                    
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':session_id', $session_id);
                    $update_stmt->bindParam(':case_id', $case_id);
                    $update_stmt->bindParam(':session_date', $session_date);
                    $update_stmt->bindParam(':session_time', $session_time);
                    $update_stmt->bindParam(':session_location', $session_location);
                    $update_stmt->bindParam(':session_status', $session_status);
                    $update_stmt->bindParam(':notes', $notes);
                    
                    if ($update_stmt->execute()) {
                        $message = 'تم تحديث الجلسة بنجاح';
                        $action = 'list';
                    } else {
                        $error_message = 'حدث خطأ في تحديث الجلسة';
                    }
                    break;
                    
                case 'delete':
                    $session_id = sanitizeInput($_POST['session_id']);
                    
                    $delete_query = "DELETE FROM sessions WHERE session_id = :session_id";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bindParam(':session_id', $session_id);
                    
                    if ($delete_stmt->execute()) {
                        $message = 'تم حذف الجلسة بنجاح';
                    } else {
                        $error_message = 'حدث خطأ في حذف الجلسة';
                    }
                    break;
            }
        }
    }
    
    // جلب القضايا للقوائم المنسدلة
    $cases_query = "SELECT case_id, case_number, case_type FROM cases ORDER BY case_number";
    $cases_stmt = $conn->prepare($cases_query);
    $cases_stmt->execute();
    $cases = $cases_stmt->fetchAll();
    
    // جلب الجلسات حسب الفلتر
    $sessions_query = "SELECT s.*, c.case_number, c.case_type 
                      FROM sessions s 
                      JOIN cases c ON s.case_id = c.case_id";
    
    switch ($filter) {
        case 'today':
            $sessions_query .= " WHERE s.session_date = CURDATE()";
            break;
        case 'upcoming':
            $sessions_query .= " WHERE s.session_date > CURDATE()";
            break;
        case 'postponed':
            $sessions_query .= " WHERE s.session_status = 'مؤجلة'";
            break;
        case 'held':
            $sessions_query .= " WHERE s.session_status = 'منعقدة'";
            break;
    }
    
    $sessions_query .= " ORDER BY s.session_date DESC, s.session_time DESC";
    
    $sessions_stmt = $conn->prepare($sessions_query);
    $sessions_stmt->execute();
    $sessions = $sessions_stmt->fetchAll();
    
    // جلب بيانات جلسة واحدة للتعديل
    $current_session = null;
    if ($action === 'edit' && $session_id) {
        $session_query = "SELECT * FROM sessions WHERE session_id = :session_id";
        $session_stmt = $conn->prepare($session_query);
        $session_stmt->bindParam(':session_id', $session_id);
        $session_stmt->execute();
        $current_session = $session_stmt->fetch();
    }
    
} catch (PDOException $e) {
    $error_message = 'حدث خطأ في النظام: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الجلسات - نظام إدارة جلسات المحكمة</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <h1><i class="fas fa-calendar-alt"></i> إدارة الجلسات</h1>
            <p>إدارة وترحيل جلسات المحكمة</p>
        </header>

        <!-- Admin Navigation -->
        <nav class="nav fade-in">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
            </a>
            <a href="manage_sessions.php" class="nav-link active">
                <i class="fas fa-calendar-alt"></i> إدارة الجلسات
            </a>
            <a href="manage_cases.php" class="nav-link">
                <i class="fas fa-folder-open"></i> إدارة القضايا
            </a>

             <a href="archive_cases.php" class="nav-link">
                <i class="fas fa-folder-open"></i>📁 الارشيف 
            </a>
            
            <a href="../index.php" class="nav-link">
                <i class="fas fa-eye"></i> عرض الموقع
            </a>
            <a href="logout.php" class="nav-link" style="background: #e74c3c; color: white;">
                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
            </a>
        </nav>

        <?php if (!empty($message)): ?>
        <div class="alert alert-success fade-in">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
        <div class="alert alert-error fade-in">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add/Edit Session Form -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i>
                </span>
                <?php echo $action === 'add' ? 'إضافة جلسة جديدة' : 'تعديل الجلسة'; ?>
            </h2>
            
            <form method="POST" class="session-form">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session_id); ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label for="case_id">القضية:</label>
                        <select id="case_id" name="case_id" class="form-control" required>
                            <option value="">اختر القضية</option>
                            <?php foreach ($cases as $case): ?>
                            <option value="<?php echo $case['case_id']; ?>" 
                                    <?php echo ($current_session && $current_session['case_id'] == $case['case_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($case['case_number'] . ' - ' . $case['case_type']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_date">تاريخ الجلسة:</label>
                        <input type="date" 
                               id="session_date" 
                               name="session_date" 
                               class="form-control" 
                               value="<?php echo $current_session ? $current_session['session_date'] : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_time">وقت الجلسة:</label>
                        <input type="time" 
                               id="session_time" 
                               name="session_time" 
                               class="form-control" 
                               value="<?php echo $current_session ? $current_session['session_time'] : ''; ?>"
                               required>
                    </div>
                    
                                                    
                            <?php
                                                        // تأكد أن المتغير $current_session موجود قبل استخدامه
                            If (!isset($current_session) || !is_array($current_session)) {
                                $current_session = [
                                    'session_location' => '',
                                    'session_status' => '',
                                    'session_time' => '' ,
                                     'notes' => ''
                                ];
                            }
                            ?>


                    <div class="form-group">
                        <label for="session_location">مكان الجلسة:</label>
                        <input type="text" 
                               id="session_location" 
                               name="session_location" 
                               class="form-control" 
                               placeholder="مثال: قاعة المحكمة رقم 1"

                               <?php echo $current_session['session_location'];?>
                              <?php echo $current_session['session_status']; ?>
                               <?php echo $current_session['session_time'];?>
                               
                               value="<?php echo $current_session ? htmlspecialchars($current_session['session_location']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_status">حالة الجلسة:</label>
                        <select id="session_status" name="session_status" class="form-control" required>
                            <option value="مجدولة" <?php echo ($current_session && $current_session['session_status'] === 'مجدولة') ? 'selected' : ''; ?>>مجدولة</option>
                            <option value="منعقدة" <?php echo ($current_session && $current_session['session_status'] === 'منعقدة') ? 'selected' : ''; ?>>منعقدة</option>
                            <option value="مؤجلة" <?php echo ($current_session && $current_session['session_status'] === 'مؤجلة') ? 'selected' : ''; ?>>مؤجلة</option>
                            <option value="ملغية" <?php echo ($current_session && $current_session['session_status'] === 'ملغية') ? 'selected' : ''; ?>>ملغية</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">ملاحظات:</label>
                    <textarea id="notes" 
                              name="notes" 
                              class="form-control" 
                              rows="3" 
                              placeholder="أدخل أي ملاحظات إضافية..."><?php echo $current_session ? htmlspecialchars($current_session['notes']) : ''; ?></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $action === 'add' ? 'إضافة الجلسة' : 'حفظ التغييرات'; ?>
                    </button>
                    <a href="manage_sessions.php" class="btn btn-secondary" style="margin-right: 10px;">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
        
        <?php else: ?>
        
        <!-- Filter and Actions -->
        <div class="card fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h3 style="margin: 0; color: #2c3e50;">
                        <i class="fas fa-filter"></i> تصفية الجلسات
                    </h3>
                </div>
                <div>
                    <a href="manage_sessions.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> إضافة جلسة جديدة
                    </a>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap;">
                <a href="manage_sessions.php?filter=all" 
                   class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-list"></i> جميع الجلسات
                </a>
                <a href="manage_sessions.php?filter=today" 
                   class="btn <?php echo $filter === 'today' ? 'btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-calendar-day"></i> جلسات اليوم
                </a>
                <a href="manage_sessions.php?filter=upcoming" 
                   class="btn <?php echo $filter === 'upcoming' ? 'btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-clock"></i> الجلسات القادمة
                </a>
                <a href="manage_sessions.php?filter=postponed" 
                   class="btn <?php echo $filter === 'postponed' ? 'btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-exclamation-triangle"></i> الجلسات المؤجلة
                </a>
                <a href="manage_sessions.php?filter=held" 
                   class="btn <?php echo $filter === 'held' ? 'btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-check-circle"></i> الجلسات المنعقدة
                </a>
                 <a href="manage_sessions.php?filter=held" 
                   class="btn <?php echo $filter === 'held' ? 'btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-check-circle"></i> الجلسات المجدولة
                </a>
            </div>
        </div>

        <!-- Sessions List -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-list"></i>
                </span>
                قائمة الجلسات (<?php echo count($sessions); ?> جلسة)
            </h2>
            
            <?php if (empty($sessions)): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <h3>لا توجد جلسات</h3>
                <p>لا توجد جلسات تطابق الفلتر المحدد.</p>
            </div>
            <?php else: ?>
            
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
                        <?php foreach ($sessions as $session): ?>
                        <tr>
                            <td>
                                <strong style="color: #667eea;">
                                    <?php echo htmlspecialchars($session['case_number']); ?>
                                </strong>
                            </td>
                            <td><?php echo htmlspecialchars($session['case_type']); ?></td>
                            <td><?php echo formatDate($session['session_date'], 'd/m/Y'); ?></td>
                            <td><?php echo htmlspecialchars($session['session_location']); ?></td>
                            <td><?php echo htmlspecialchars($session['session_time']); ?></td>
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
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="manage_sessions.php?action=edit&id=<?php echo $session['session_id']; ?>" 
                                       class="btn btn-primary" 
                                       style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-edit"></i> تعديل
                                    </a>
                                    <button onclick="deleteSession(<?php echo $session['session_id']; ?>)" 
                                            class="btn btn-danger" 
                                            style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-trash"></i> حذف
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; max-width: 400px; width: 90%;">
            <h3 style="color: #e74c3c; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> تأكيد الحذف
            </h3>
            <p style="margin-bottom: 30px; color: #7f8c8d;">
                هل أنت متأكد من حذف هذه الجلسة؟ لا يمكن التراجع عن هذا الإجراء.
            </p>
            <div style="text-align: center;">
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="session_id" id="deleteSessionId">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> حذف
                    </button>
                </form>
                <button onclick="closeDeleteModal()" class="btn btn-secondary" style="margin-right: 10px;">
                    <i class="fas fa-times"></i> إلغاء
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="text-align: center; padding: 30px; color: rgba(255, 255, 255, 0.8); margin-top: 50px;">
        <p>&copy; 2025 نظام إدارة جلسات المحكمة. جميع الحقوق محفوظة.</p>
    </footer>

    <script>
        function deleteSession(sessionId) {
            document.getElementById('deleteSessionId').value = sessionId;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // إغلاق النافذة المنبثقة عند النقر خارجها
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // تحسين تجربة المستخدم للنماذج
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...';
                        submitBtn.disabled = true;
                        
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 5000);
                    }
                });
            });
        });
    </script>
</body>
</html>

