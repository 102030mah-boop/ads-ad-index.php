؟_<?php
session_start();
require_once '../config/database.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}
//************* */
function get_litigants($conn){
    try{
  $litigants_query = "SELECT litigant_id
                        FROM litigants
                        ORDER BY litigant_id desc limit 1";
                         $litigants_stmt = $conn->prepare($litigants_query);
    $litigants_stmt->execute();
           return $litigants_stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }catch(PDOException $e){
        $e->getmessage();
        return null;

    }
    

}
function UPDATE_litigantsAndcase($role,$conn,$case_id,$litigant_id){  
        
                    
                    $update_query = "UPDATE case_litigants 
                                    SET role = :role
                                    WHERE litigant_id = :litigant_id and case_id=:case_id";
                    
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':role', $role);
                    $update_stmt->bindParam(':litigant_id', $litigant_id);
                    $update_stmt->bindParam(':case_id', $case_id);
                  
                    
                    if ($update_stmt->execute()) {
                        $message = 'ok';
                    } else {
                        $message = 'no';
                    }
                    return $message;
}
function add_litigantsAndcase($role,$conn,$case_id,$litigant_id){              
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

}}
//**************** */
$message = '';
$error_message = '';
$litigants = [];
$action = isset($_GET["action"]) ? $_GET["action"] : "list";
$litigant_id = isset($_GET["id"]) ? $_GET["id"] : null;

try {
    $conn = getDBConnection();
    
    // معالجة الإجراءات
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                
                case 'add':
                    $full_name = sanitizeInput($_POST['full_name']);
                    $id_number = sanitizeInput($_POST['id_number']);
                    $address = sanitizeInput($_POST['address']);
                      $case_number = $_GET['case_number'];
                       $case_id = $_GET['case_id'];
                    $phone_number = sanitizeInput($_POST['phone_number']);

                    $role = sanitizeInput($_POST['role']);

                    $insert_query = "INSERT INTO litigants (full_name, id_number, address, phone_number,case_id) 
                                    VALUES (:full_name, :id_number, :address, :phone_number, :case_number)";
                    
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bindParam(':full_name', $full_name);
                    $insert_stmt->bindParam(':id_number', $id_number);
                    $insert_stmt->bindParam(':address', $address);
                    $insert_stmt->bindParam(':phone_number', $phone_number);
                      $insert_stmt->bindParam(':case_number', $case_number);
                    
                    if ($insert_stmt->execute()) {
                       
                        $message = 'تم إضافة المتقاضي بنجاح';
                        //*********** */
                        $ssss =get_litigants($conn);
                        $litigant_id1=  htmlspecialchars($ssss['litigant_id']); 
                        add_litigantsAndcase($role,$conn,$case_id,$litigant_id1);
                          //******************* */
                            header("location:search_admin.php?case_number=".$case_number);
                        $action = 'list';
                    } else {
                        $error_message = 'حدث خطأ في إضافة المتقاضي';
                    }
                    
    // تحقق إذا رقم الهوية موجود مسبقاً
    $check_query =$conn->prepare ("SELECT COUNT(*) FROM litigants WHERE id_number = :id_number");
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':id_number', $id_number);
    $check_stmt->execute();
    $exists=$check_stmt->fetchColumn();


    // if ($check_stmt->fetchColumn() > 0) {
    //     $error_message = "<p style='color:red;'>⚠️ رقم الهوية ($id_number) موجود بالفعل.</p>";

            if($exists > 0){
            
                $error_message = "<p style='color:red;'>⚠️ رقم الهوية ($id_number) موجود بالفعل.</p>";
    } else {
        $insert_query = "INSERT INTO litigants (full_name, id_number, address, phone_number) 
                         VALUES (:full_name, :id_number, :address, :phone_number)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bindParam(':full_name', $full_name);
        $insert_stmt->bindParam(':id_number', $id_number);
        $insert_stmt->bindParam(':address', $address);
        $insert_stmt->bindParam(':phone_number', $phone_number);
        if ($insert_stmt->execute()) {
            $message = "<p style='color:green;'>✔ تمت إضافة المتقاضي بنجاح.</p>";
        } else {
            $error_message = "<p style='color:red;'>❌ فشل في إضافة المتقاضي.</p>";
        }
    }
break;
                    
                case 'edit':
                      $case_number = $_GET['case_number'];
                       $case_id = $_GET['case_id'];
                       $role=sanitizeInput($_POST['role']);
                    $litigant_id = sanitizeInput($_POST['litigant_id']);
                    $full_name = sanitizeInput($_POST['full_name']);
                    $id_number = sanitizeInput($_POST['id_number']);
                    $address = sanitizeInput($_POST['address']);
                    $phone_number = sanitizeInput($_POST['phone_number']);
                    
                    $update_query = "UPDATE litigants 
                                    SET full_name = :full_name, id_number = :id_number, 
                                        address = :address, phone_number = :phone_number 
                                    WHERE litigant_id = :litigant_id";
                    
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bindParam(':litigant_id', $litigant_id);
                    $update_stmt->bindParam(':full_name', $full_name);
                    $update_stmt->bindParam(':id_number', $id_number);
                    $update_stmt->bindParam(':address', $address);
                    $update_stmt->bindParam(':phone_number', $phone_number);
                    
                    if ($update_stmt->execute()) {
                       $ms= UPDATE_litigantsAndcase($role,$conn,$case_id,$litigant_id);
                       if($ms=="ok"){
 $message = 'تم تحديث بيانات المتقاضي بنجاح مع تعديل الدور';
                       }else{
                        $message = 'تم تحديث بيانات المتقاضي بنجاح';
                       }
                          header("location:search_admin.php?case_number=".$case_number);
                        $action = 'list';
                    } else {
                        $error_message = 'حدث خطأ في تحديث بيانات المتقاضي';
                    }
                    
    // تحقق من عدم وجود رقم هوية مكرر لمتقاضي آخر
    $check_query = "SELECT COUNT(*) FROM litigants WHERE id_number = :id_number AND litigant_id != :litigant_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindParam(':id_number', $id_number);
    $check_stmt->bindParam(':litigant_id', $litigant_id);
    $check_stmt->execute();

    if ($check_stmt->fetchColumn() > 0) {
        $error_message = "<p style='color:red;'>⚠️ لا يمكن التعديل، رقم الهوية ($id_number) مستخدم من قبل متقاضي آخر.</p>";
    } else {
        $update_query = "UPDATE litigants 
                         SET full_name = :full_name, id_number = :id_number, address = :address, phone_number = :phone_number 
                         WHERE litigant_id = :litigant_id";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':full_name', $full_name);
        $update_stmt->bindParam(':id_number', $id_number);
        $update_stmt->bindParam(':address', $address);
        $update_stmt->bindParam(':phone_number', $phone_number);
        $update_stmt->bindParam(':litigant_id', $litigant_id);
        if ($update_stmt->execute()) {
            $message = "<p style='color:green;'>✔ تم تعديل بيانات المتقاضي بنجاح.</p>";
        } else {
            $error_message = "<p style='color:red;'>❌ فشل في تعديل بيانات المتقاضي.</p>";
        }
    }
break;
                    
                case 'delete':
                    $litigant_id = sanitizeInput($_POST['litigant_id']);
                    
                    $delete_query = "DELETE FROM litigants WHERE litigant_id = :litigant_id";
                    $delete_stmt = $conn->prepare($delete_query);
                    $delete_stmt->bindParam(':litigant_id', $litigant_id);
                    
                    if ($delete_stmt->execute()) {
                        $message = 'تم حذف المتقاضي بنجاح';
                    } else {
                        $error_message = 'حدث خطأ في حذف المتقاضي';
                    }
                    break;
            }
        }
    }
    
    // جلب المتقاضين
    $litigants_query = "SELECT l.*, 
                               COUNT(cl.case_id) as total_cases
                        FROM litigants l 
                        LEFT JOIN case_litigants cl ON l.litigant_id = cl.litigant_id 
                        GROUP BY l.litigant_id 
                        ORDER BY l.full_name ASC";
    
    $litigants_stmt = $conn->prepare($litigants_query);
    $litigants_stmt->execute();
    $litigants = $litigants_stmt->fetchAll();
    
    // جلب بيانات متقاضي واحد للتعديل
    $current_litigant = null;
    if ($action === 'edit' && $litigant_id) {
        $litigant_query = "SELECT * FROM litigants WHERE litigant_id = :litigant_id";
        $litigant_stmt = $conn->prepare($litigant_query);
        $litigant_stmt->bindParam(':litigant_id', $litigant_id);
        $litigant_stmt->execute();
        $current_litigant = $litigant_stmt->fetch();
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
    <title>إدارة المتقاضين - نظام إدارة جلسات المحكمة</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <h1><i class="fas fa-users"></i> إدارة المتقاضين</h1>
            <p>إدارة وتنظيم أطراف القضايا</p>
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
        <!-- Add/Edit Litigant Form -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i>
                </span>
                <?php echo $action === 'add' ? 'إضافة متقاضي جديد' : 'تعديل بيانات المتقاضي'; ?>
            </h2>
            
            <form method="POST" class="litigant-form">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="litigant_id" value="<?php echo htmlspecialchars($litigant_id); ?>">
                <?php endif; ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label for="full_name">الاسم الكامل:</label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               class="form-control" 
                               placeholder="مثال: محمود رفيق"
                               value="<?php echo $current_litigant ? htmlspecialchars($current_litigant['full_name']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_number">رقم الهوية:</label>
                        <input type="text" 
                               id="id_number" 
                               name="id_number" 
                               class="form-control" 
                               placeholder="مثال: 1234567890"
                               pattern="[0-9]{11}"
                               title="يرجى إدخال رقم هوية صحيح (11 أرقام)"
                               value="<?php echo $current_litigant ? htmlspecialchars($current_litigant['id_number']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone_number">رقم الهاتف:</label>
                        <input type="tel" 
                               id="phone_number" 
                               name="phone_number" 
                               class="form-control" 
                               placeholder="مثال: 0501234567"
                               pattern="7[0-9]{8}"
                               title="يرجى إدخال رقم هاتف صحيح (7xxxxxxxx)"
                               value="<?php echo $current_litigant ? htmlspecialchars($current_litigant['phone_number']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">العنوان:</label>
                    <textarea id="address" 
                              name="address" 
                              class="form-control" 
                              rows="3" 
                              placeholder="أدخل العنوان الكامل..."><?php echo $current_litigant ? htmlspecialchars($current_litigant['address']) : ''; ?></textarea>
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

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        <?php echo $action === 'add' ? 'إضافة المتقاضي' : 'حفظ التغييرات'; ?>
                    </button>
                    <a href="manage_litigants.php" class="btn btn-secondary" style="margin-right: 10px;">
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
                        <i class="fas fa-list"></i> قائمة المتقاضين
                    </h3>
                </div>
                <div>
                    <a href="manage_litigants.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> إضافة متقاضي جديد
                    </a>
                </div>
            </div>
        </div>

        <!-- Litigants List -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-users"></i>
                </span>
                المتقاضون المسجلون (<?php echo count($litigants); ?> متقاضي)
            </h2>
            
            <?php 
           
            if (empty($litigants)): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 20px;"></i>
                <h3>لا يوجد متقاضون مسجلون</h3>
                <p>لم يتم تسجيل أي متقاضين في النظام بعد.</p>
                <a href="manage_litigants.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إضافة متقاضي جديد
                </a>
            </div>
            <?php else: ?>
            
            <div class="table-responsive">

               
                <!-- كود المتقاضون-->
            </div>
            
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; text-align: center; min-width: 300px;">
            <h3 style="color: #e74c3c; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle"></i> تأكيد الحذف
            </h3>
            <p id="deleteMessage" style="margin-bottom: 30px;"></p>
            <form id="deleteForm" method="POST" style="display: inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="litigant_id" id="deleteLitigantId">
                <button type="submit" class="btn btn-danger" style="margin-left: 10px;">
                    <i class="fas fa-trash"></i> حذف
                </button>
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> إلغاء
                </button>
            </form>
        </div>
    </div>

    <script>
        function deleteLitigant(id, name) {
            document.getElementById('deleteLitigantId').value = id;
            document.getElementById('deleteMessage').textContent = 'هل أنت متأكد من حذف المتقاضي "' + name + '"؟';
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // إغلاق النافذة عند النقر خارجها
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>

