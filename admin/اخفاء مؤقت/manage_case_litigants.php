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
$case_id = isset($_GET["case_id"]) ? $_GET["case_id"] : null;
$case_info = null;
$case_litigants = [];
$available_litigants = [];

if (!$case_id) {
    header('Location: manage_cases.php');
    exit();
}

try {
    $conn = getDBConnection();
    
    // معالجة الإجراءات
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_litigant':
                    $litigant_id = sanitizeInput($_POST['litigant_id']);
                    $role = sanitizeInput($_POST['role']);
                    
                    // التحقق من عدم وجود المتقاضي في القضية مسبقاً
                    $check_query = "SELECT COUNT(*) FROM case_litigants WHERE case_id = :case_id AND litigant_id = :litigant_id";
                    $check_stmt = $conn->prepare($check_query);
                    $check_stmt->bindParam(':case_id', $case_id);
                    $check_stmt->bindParam(':litigant_id', $litigant_id);
                    $check_stmt->execute();
                    
                    if ($check_stmt->fetchColumn() > 0) {
                        $error_message = 'هذا المتقاضي مضاف بالفعل إلى القضية';
                    } else {
                        $insert_query = "INSERT INTO case_litigants (case_id, litigant_id, role) 
                                        VALUES (:case_id, :litigant_id, :role)";
                        
                        $insert_stmt = $conn->prepare($insert_query);
                        $insert_stmt->bindParam(':case_id', $case_id);
                        $insert_stmt->bindParam(':litigant_id', $litigant_id);
                        $insert_stmt->bindParam(':role', $role);
                        
                        if ($insert_stmt->execute()) {
                            $message = 'تم إضافة المتقاضي إلى القضية بنجاح';
                        } else {
                            $error_message = 'حدث خطأ في إضافة المتقاضي إلى القضية';
                        }
                    }
                    break;
                    
                case 'remove_litigant':
                    $litigant_id = sanitizeInput($_POST['litigant_id']);
                    
                    $delete_query = "DELETE FROM case_litigants WHERE case_id = :case_id AND litigant_id = :litigant_id";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bindParam(':case_id', $case_id);
                    $delete_stmt->bindParam(':litigant_id', $litigant_id);
                    
                    if ($delete_stmt->execute()) {
                        $message = 'تم إزالة المتقاضي من القضية بنجاح';
                    } else {
                        $error_message = 'حدث خطأ في إزالة المتقاضي من القضية';
                    }
                    break;
                    
                case 'update_role':
                    $litigant_id = sanitizeInput($_POST['litigant_id']);
                    $role = sanitizeInput($_POST['role']);
                    
                    $update_query = "UPDATE case_litigants SET role = :role WHERE case_id = :case_id AND litigant_id = :litigant_id";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':role', $role);
                    $update_stmt->bindParam(':case_id', $case_id);
                    $update_stmt->bindParam(':litigant_id', $litigant_id);
                    
                    if ($update_stmt->execute()) {
                        $message = 'تم تحديث دور المتقاضي بنجاح';
                    } else {
                        $error_message = 'حدث خطأ في تحديث دور المتقاضي';
                    }
                    break;
            }
        }
    }
    
    // جلب معلومات القضية
    $case_query = "SELECT * FROM cases WHERE case_id = :case_id";
    $case_stmt = $conn->prepare($case_query);
    $case_stmt->bindParam(':case_id', $case_id);
    $case_stmt->execute();
    $case_info = $case_stmt->fetch();
    
    if (!$case_info) {
        header('Location: manage_cases.php');
        exit();
    }
    
    // جلب المتقاضين المرتبطين بالقضية
    $case_litigants_query = "SELECT l.*, cl.role 
                            FROM litigants l 
                            JOIN case_litigants cl ON l.litigant_id = cl.litigant_id 
                            WHERE cl.case_id = :case_id 
                            ORDER BY l.full_name";
    
    $case_litigants_stmt = $conn->prepare($case_litigants_query);
    $case_litigants_stmt->bindParam(':case_id', $case_id);
    $case_litigants_stmt->execute();
    $case_litigants = $case_litigants_stmt->fetchAll();
    
    // جلب المتقاضين المتاحين للإضافة (غير مرتبطين بالقضية)
    $available_query = "SELECT * FROM litigants 
                       WHERE litigant_id NOT IN (
                           SELECT litigant_id FROM case_litigants WHERE case_id = :case_id
                       ) 
                       ORDER BY full_name";
    
    $available_stmt = $conn->prepare($available_query);
    $available_stmt->bindParam(':case_id', $case_id);
    $available_stmt->execute();
    $available_litigants = $available_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = 'حدث خطأ في النظام: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة أطراف القضية - نظام إدارة جلسات المحكمة</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <h1><i class="fas fa-user-friends"></i> إدارة أطراف القضية</h1>
            <p>إدارة المتقاضين في القضية رقم <?php echo htmlspecialchars($case_info['case_number']); ?></p>
        </header>

        <!-- Admin Navigation -->
        <nav class="nav fade-in">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
            </a>
            <a href="manage_sessions.php" class="nav-link">
                <i class="fas fa-calendar-alt"></i> إدارة الجلسات
            </a>
            <a href="manage_cases.php" class="nav-link">
                <i class="fas fa-folder-open"></i> إدارة القضايا
            </a>
            <a href="manage_litigants.php" class="nav-link">
                <i class="fas fa-users"></i> إدارة المتقاضين
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

        <!-- Case Info -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-folder-open"></i>
                </span>
                معلومات القضية
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <strong>رقم القضية:</strong> <?php echo htmlspecialchars($case_info['case_number']); ?>
                </div>
                <div>
                    <strong>نوع القضية:</strong> <?php echo htmlspecialchars($case_info['case_type']); ?>
                </div>
                <div>
                    <strong>تاريخ الرفع:</strong> <?php echo formatDate($case_info['filing_date'], 'd/m/Y'); ?>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <strong>وصف القضية:</strong> <?php echo htmlspecialchars($case_info['description']); ?>
            </div>
            <div style="margin-top: 20px;">
                <a href="manage_cases.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> العودة إلى قائمة القضايا
                </a>
            </div>
        </div>

        <!-- Add Litigant -->
        <?php if (!empty($available_litigants)): ?>
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-plus"></i>
                </span>
                إضافة متقاضي إلى القضية
            </h2>
            
            <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <input type="hidden" name="action" value="add_litigant">
                
                <div class="form-group" style="margin: 0;">
                    <label for="litigant_id">اختر المتقاضي:</label>
                    <select id="litigant_id" name="litigant_id" class="form-control" required>
                        <option value="">اختر المتقاضي</option>
                        <?php foreach ($available_litigants as $litigant): ?>
                        <option value="<?php echo $litigant['litigant_id']; ?>">
                            <?php echo htmlspecialchars($litigant['full_name'] . ' - ' . $litigant['id_number']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <label for="role">الدور في القضية:</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">اختر الدور</option>
                        <option value="مدعي">مدعي</option>
                        <option value="مدعى عليه">مدعى عليه</option>
                        <option value="متهم">متهم</option>
                        <option value="مجني عليه">مجني عليه</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إضافة
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Current Litigants -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-user-friends"></i>
                </span>
                أطراف القضية الحالية (<?php echo count($case_litigants); ?> متقاضي)
            </h2>
            
            <?php if (empty($case_litigants)): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <i class="fas fa-user-friends" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <h3>لا يوجد أطراف مضافة للقضية</h3>
                <p>لم يتم إضافة أي متقاضين لهذه القضية بعد.</p>
                <?php if (!empty($available_litigants)): ?>
                <p>يمكنك إضافة متقاضين من القائمة أعلاه.</p>
                <?php else: ?>
                <a href="manage_litigants.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إضافة متقاضي جديد أولاً
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الاسم الكامل</th>
                            <th>رقم الهوية</th>
                            <th>رقم الهاتف</th>
                            <th>الدور في القضية</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($case_litigants as $litigant): ?>
                        <tr>
                            <td>
                                <strong style="color: #667eea;">
                                    <?php echo htmlspecialchars($litigant['full_name']); ?>
                                </strong>
                            </td>
                            <td><?php echo htmlspecialchars($litigant['id_number']); ?></td>
                            <td><?php echo htmlspecialchars(isset($litigant['phone_number']) ? $litigant['phone_number'] : 'غير محدد'); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="litigant_id" value="<?php echo $litigant['litigant_id']; ?>">
                                    <select name="role" class="form-control" style="display: inline-block; width: auto; padding: 3px 8px; font-size: 12px;" onchange="this.form.submit()">
                                        <option value="مدعي" <?php echo $litigant['role'] === 'مدعي' ? 'selected' : ''; ?>>مدعي</option>
                                        <option value="مدعى عليه" <?php echo $litigant['role'] === 'مدعى عليه' ? 'selected' : ''; ?>>مدعى عليه</option>
                                        <option value="متهم" <?php echo $litigant['role'] === 'متهم' ? 'selected' : ''; ?>>متهم</option>
                                        <option value="مجني عليه" <?php echo $litigant['role'] === 'مجني عليه' ? 'selected' : ''; ?>>مجني عليه</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="manage_litigants.php?action=edit&id=<?php echo $litigant['litigant_id']; ?>" 
                                       class="btn btn-primary" 
                                       style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-edit"></i> تعديل
                                    </a>
                                    <button onclick="removeLitigant(<?php echo $litigant['litigant_id']; ?>, '<?php echo htmlspecialchars($litigant['full_name']); ?>')" 
                                            class="btn btn-danger" 
                                            style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-times"></i> إزالة
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
    </div>

    <!-- Remove Confirmation Modal -->
    <div id="removeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; text-align: center; min-width: 300px;">
            <h3 style="color: #e74c3c; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> تأكيد الإزالة
            </h3>
            <p id="removeMessage" style="margin-bottom: 30px;"></p>
            <form id="removeForm" method="POST" style="display: inline;">
                <input type="hidden" name="action" value="remove_litigant">
                <input type="hidden" name="litigant_id" id="removeLitigantId">
                <button type="submit" class="btn btn-danger" style="margin-left: 10px;">
                    <i class="fas fa-times"></i> إزالة
                </button>
                <button type="button" onclick="closeRemoveModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> إلغاء
                </button>
            </form>
        </div>
    </div>

    <script>
        function removeLitigant(id, name) {
            document.getElementById('removeLitigantId').value = id;
            document.getElementById('removeMessage').textContent = 'هل أنت متأكد من إزالة "' + name + '" من هذه القضية؟';
            document.getElementById('removeModal').style.display = 'block';
        }

        function closeRemoveModal() {
            document.getElementById('removeModal').style.display = 'none';
        }

        // إغلاق النافذة عند النقر خارجها
        document.getElementById('removeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRemoveModal();
            }
        });
    </script>
</body>
</html>

