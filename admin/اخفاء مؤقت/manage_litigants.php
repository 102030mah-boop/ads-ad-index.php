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
$cases = [];
$action = isset($_GET["action"]) ? $_GET["action"] : "list";
$case_id = isset($_GET["id"]) ? $_GET["id"] : null;

try {
    $conn = getDBConnection();
    
    // معالجة الإجراءات
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                     
                    $case_number = sanitizeInput($_POST['case_number']);
                    $case_type = sanitizeInput($_POST['case_type']);
                    $filing_date = sanitizeInput($_POST['filing_date']);
                    $description = sanitizeInput($_POST['description']);
                    
                    
                    $insert_query = "INSERT INTO cases (case_number, case_type, filing_date, description) 
                                    VALUES (:case_number, :case_type, :filing_date, :description)";
                    
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bindParam(':case_number', $case_number);
                    $insert_stmt->bindParam(':case_type', $case_type);
                    $insert_stmt->bindParam(':filing_date', $filing_date);
                    $insert_stmt->bindParam(':description', $description);
                    $prosecutor = sanitizeInput($_POST['prosecutor']);
                    $defendant = sanitizeInput($_POST['defendant']);

                 
                                    
                    

                    if ($insert_stmt->execute()) {
                        $message = 'تم إضافة القضية بنجاح';
                        $action = 'list';
                    } else {
                        $error_message = 'حدث خطأ في إضافة القضية';
                    }
                    break;
                    
                case 'edit':
                    $case_id = sanitizeInput($_POST['case_id']);
                    $case_number = sanitizeInput($_POST['case_number']);
                    $case_type = sanitizeInput($_POST['case_type']);
                    $filing_date = sanitizeInput($_POST['filing_date']);
                    $description = sanitizeInput($_POST['description']);
                    
                    $update_query = "UPDATE cases 
                                    SET case_number = :case_number, case_type = :case_type, 
                                        filing_date = :filing_date, description = :description 
                                    WHERE case_id = :case_id";
                    
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':case_id', $case_id);
                    $update_stmt->bindParam(':case_number', $case_number);
                    $update_stmt->bindParam(':case_type', $case_type);
                    $update_stmt->bindParam(':filing_date', $filing_date);
                    $update_stmt->bindParam(':description', $description);
                    
                    if ($update_stmt->execute()) {
                        $message = 'تم تحديث القضية بنجاح';
                        $action = 'list';
                    } else {
                        $error_message = 'حدث خطأ في تحديث القضية';
                    }
                    break;
                    
                case 'delete':
                    $case_id = sanitizeInput($_POST['case_id']);
                    
                    $delete_query = "DELETE FROM cases WHERE case_id = :case_id";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bindParam(':case_id', $case_id);
                    
                    if ($delete_stmt->execute()) {
                        $message = 'تم حذف القضية بنجاح';
                    } else {
                        $error_message = 'حدث خطأ في حذف القضية';
                    }
                    break;
            }
        }
    }
    
    // جلب القضايا
    $cases_query = "SELECT c.*, 
                           COUNT(s.session_id) as total_sessions,
                           MAX(s.session_date) as last_session_date
                    FROM cases c 
                    LEFT JOIN sessions s ON c.case_id = s.case_id 
                    GROUP BY c.case_id 
                    ORDER BY c.case_number DESC";
    
    $cases_stmt = $conn->prepare($cases_query);
    $cases_stmt->execute();
    $cases = $cases_stmt->fetchAll();
    
    // جلب بيانات قضية واحدة للتعديل
    $current_case = null;
    if ($action === 'edit' && $case_id) {
        $case_query = "SELECT * FROM cases WHERE case_id = :case_id";
        $case_stmt = $conn->prepare($case_query);
        $case_stmt->bindParam(':case_id', $case_id);
        $case_stmt->execute();
        $current_case = $case_stmt->fetch();
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
    <title>إدارة القضايا - نظام إدارة جلسات المحكمة</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <h1><i class="fas fa-folder-open"></i> إدارة القضايا</h1>
            <p>إدارة وتنظيم قضايا المحكمة</p>
        </header>

        <!-- Admin Navigation -->
        <nav class="nav fade-in">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
            </a>
            <a href="manage_sessions.php" class="nav-link">
                <i class="fas fa-calendar-alt"></i> إدارة الجلسات
            </a>
            <a href="manage_cases.php" class="nav-link active">
                <i class="fas fa-folder-open"></i> إدارة القضايا
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
        <!-- Add/Edit Case Form -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i>
                </span>
                <?php echo $action === 'add' ? 'إضافة قضية جديدة' : 'تعديل القضية'; ?>
            </h2>
            
            <form method="POST" class="case-form">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="case_id" value="<?php echo htmlspecialchars($case_id); ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label for="case_number">رقم القضية:</label>
                        <input type="text" 
                               id="case_number" 
                               name="case_number" 
                               class="form-control" 
                               placeholder="مثال: 1001/1447"
                               pattern="[0-9]+/[0-9]+"
                               title="يرجى إدخال رقم القضية بالصيغة الصحيحة (مثال: 1001/2024)"
                               value="<?php echo $current_case ? htmlspecialchars($current_case['case_number']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="case_type">نوع القضية:</label>
                        <select id="case_type" name="case_type" class="form-control" required>
                            <option value="">اختر نوع القضية</option>
                            <option value="قضية مدنية" <?php echo ($current_case && $current_case['case_type'] === 'قضية مدنية') ? 'selected' : ''; ?>>قضية مدنية</option>
                            <option value="قضية جنائية" <?php echo ($current_case && $current_case['case_type'] === 'قضية جنائية') ? 'selected' : ''; ?>>قضية جنائية</option>
                            <option value="قضية تجارية" <?php echo ($current_case && $current_case['case_type'] === 'قضية تجارية') ? 'selected' : ''; ?>>قضية تجارية</option>
                            <option value="قضية عمالية" <?php echo ($current_case && $current_case['case_type'] === 'قضية عمالية') ? 'selected' : ''; ?>>قضية عمالية</option>
                            <option value="قضية أسرية" <?php echo ($current_case && $current_case['case_type'] === 'قضية أسرية') ? 'selected' : ''; ?>>قضية أسرية</option>
                            <option value="قضية إدارية" <?php echo ($current_case && $current_case['case_type'] === 'قضية إدارية') ? 'selected' : ''; ?>>قضية إدارية</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="filing_date">تاريخ رفع القضية:</label>
                        <input type="date" 
                               id="filing_date" 
                               name="filing_date" 
                               class="form-control" 
                               value="<?php echo $current_case ? $current_case['filing_date'] : ''; ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">وصف القضية:</label>
                    <textarea id="description" 
                              name="description" 
                              class="form-control" 
                              rows="4" 
                              placeholder="أدخل وصفاً موجزاً للقضية..."><?php echo $current_case ? htmlspecialchars($current_case['description']) : ''; ?></textarea>
                </div>
              
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $action === 'add' ? 'إضافة القضية' : 'حفظ التغييرات'; ?>
                    </button>
                    <a href="manage_cases.php" class="btn btn-secondary" style="margin-right: 10px;">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
        
        <?php else: ?>
        
        <!-- Actions -->
        <div class="card fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h3 style="margin: 0; color: #2c3e50;">
                        <i class="fas fa-list"></i> قائمة القضايا
                    </h3>
                </div>
                <div>
                    <a href="manage_cases.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> إضافة قضية جديدة
                    </a>
                </div>
            </div>
        </div>

        <!-- Cases List -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-folder-open"></i>
                </span>
                القضايا المسجلة (<?php echo count($cases); ?> قضية)
            </h2>
            
            <?php if (empty($cases)): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <h3>لا توجد قضايا مسجلة</h3>
                <p>لم يتم تسجيل أي قضايا في النظام بعد.</p>
                <a href="manage_cases.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إضافة قضية جديدة
                </a>
            </div>
            <?php else: ?>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>رقم القضية</th>
                            <th>نوع القضية</th>
                            <th>تاريخ الرفع</th>
                            <th>عدد الجلسات</th>
                            <th>آخر جلسة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $case): ?>
                        <tr>
                            <td>
                                <strong style="color: #667eea;">
                                    <?php echo htmlspecialchars($case['case_number']); ?>
                                </strong>
                            </td>
                            <td>
                                <span style="padding: 5px 10px; background: rgba(102, 126, 234, 0.1); border-radius: 15px; font-size: 12px; color: #667eea;">
                                    <?php echo htmlspecialchars($case['case_type']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($case['filing_date'], 'd/m/Y'); ?></td>
                            <td>
                                <span style="background: rgba(39, 174, 96, 0.1); color: #27ae60; padding: 3px 8px; border-radius: 10px; font-size: 12px;">
                                    <?php echo $case['total_sessions']; ?> جلسة
                                </span>
                            </td>
                            <td>
                                <?php if ($case['last_session_date']): ?>
                                    <?php echo formatDate($case['last_session_date'], 'd/m/Y'); ?>
                                <?php else: ?>
                                    <span style="color: #bdc3c7;">لا توجد جلسات</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="manage_cases.php?action=edit&id=<?php echo $case['case_id']; ?>" 
                                       class="btn btn-primary" 
                                       style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-edit"></i> تعديل
                                    </a>
                                    <a href="../search.php?case_number=<?php echo urlencode($case['case_number']); ?>" 
                                       class="btn btn-success" 
                                       style="padding: 5px 10px; font-size: 12px;"
                                       target="_blank">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
                                    <button onclick="deleteCase(<?php echo $case['case_id']; ?>)" 
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
                هل أنت متأكد من حذف هذه القضية؟ سيتم حذف جميع الجلسات المرتبطة بها أيضاً. لا يمكن التراجع عن هذا الإجراء.
            </p>
            <div style="text-align: center;">
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="case_id" id="deleteCaseId">
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
        function deleteCase(caseId) {
            document.getElementById('deleteCaseId').value = caseId;
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

