<?php
require_once '../config/database.php';

session_start();

$search_result = null;
$error_message = '';
$case_number = '';

if (isset($_GET['case_number']) && !empty($_GET['case_number'])) {
    $case_number = sanitizeInput($_GET['case_number']);
    
    if (validateCaseNumber($case_number)) {
        try {
            $conn = getDBConnection();
            
            // البحث عن القضية
            $case_query = "SELECT c.*, 
                                  COUNT(s.session_id) as total_sessions,
                                  MAX(s.session_date) as last_session_date
                           FROM cases c 
                           LEFT JOIN sessions s ON c.case_id = s.case_id 
                           WHERE c.case_number = :case_number 
                           GROUP BY c.case_id";
            
            $case_stmt = $conn->prepare($case_query);
            $case_stmt->bindParam(':case_number', $case_number);
            $case_stmt->execute();
            
            $case_data = $case_stmt->fetch();
            
            if ($case_data) {
                // البحث عن جلسات القضية
                $sessions_query = "SELECT s.*, c.case_number 
                                  FROM sessions s 
                                  JOIN cases c ON s.case_id = c.case_id 
                                  WHERE c.case_number = :case_number 
                                  ORDER BY s.session_date DESC, s.session_time DESC";
                
                $sessions_stmt = $conn->prepare($sessions_query);
                $sessions_stmt->bindParam(':case_number', $case_number);
                $sessions_stmt->execute();
                
                $sessions_data = $sessions_stmt->fetchAll();
                
                // البحث عن المتقاضين
                $litigants_query = "SELECT l.*, cl.role 
                                   FROM litigants l 
                                   JOIN case_litigants cl ON l.litigant_id = cl.litigant_id 
                                   JOIN cases c ON cl.case_id = c.case_id 
                                   WHERE c.case_number = :case_number";
                
                $litigants_stmt = $conn->prepare($litigants_query);
                $litigants_stmt->bindParam(':case_number', $case_number);
                $litigants_stmt->execute();
                
                $litigants_data = $litigants_stmt->fetchAll();
                
                $search_result = [
                    'case' => $case_data,
                    'sessions' => $sessions_data,
                    'litigants' => $litigants_data
                ];
            } else {
                $error_message = 'لم يتم العثور على قضية بهذا الرقم';
            }
        } catch (PDOException $e) {
            $error_message = 'حدث خطأ في البحث. يرجى المحاولة مرة أخرى.';
        }
    } else {
        $error_message = 'رقم القضية غير صحيح. يرجى إدخال الرقم بالصيغة الصحيحة (مثال: 1001/2024)';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البحث عن قضية - نظام إدارة جلسات المحكمة</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <h1><i class="fas fa-search"></i> البحث عن قضية</h1>
            <p>ابحث عن قضيتك باستخدام رقم القضية</p>
        </header>

        <!-- Navigation -->
        <nav class="nav fade-in">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i> الرئيسية
            </a>
          
            <a href="today_sessions.php" class="nav-link">
                <i class="fas fa-calendar-day"></i> جلسات اليوم
            </a>
               <?php
   if (isset($_SESSION['admin_logged_in'])) {
  
  
?>
            <a href="login.php" class="nav-link">
                <i class="fas fa-user-shield"></i> دخول الإدارة
            </a>
           <?php 
        } 
        ?>
          
        </nav>

        <!-- Search Form -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-search"></i>
                </span>
                البحث عن قضية
            </h2>
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="case_number">رقم القضية:</label>
                    <input type="text" 
                           id="case_number" 
                           name="case_number" 
                           class="form-control" 
                           placeholder="مثال: 1001/2024"
                           value="<?php echo htmlspecialchars($case_number); ?>"
                           pattern="[0-9]+/[0-9]+"
                           title="يرجى إدخال رقم القضية بالصيغة الصحيحة (مثال: 1001/2024)"
                           required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> بحث
                </button>
                <?php if (!empty($case_number)): ?>
                <a href="search.php" class="btn btn-secondary" style="margin-right: 10px;">
                    <i class="fas fa-times"></i> مسح البحث
                </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($error_message)): ?>
        <!-- Error Message -->
        <div class="alert alert-error fade-in">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($search_result): ?>
        <!-- Case Information -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-file-alt"></i>
                </span>
                معلومات القضية
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <div>
                    <strong>الرقم الالي للقضية :</strong>
                    <span style="color: #667eea; font-weight: bold;"><?php echo htmlspecialchars($search_result['case']['case_id']); ?></span>
                </div>
                 <div>
                    <strong>الرقم اليدوي للقضية :</strong>
                    <span style="color: #667eea; font-weight: bold;"><?php echo htmlspecialchars($search_result['case']['case_number']); ?></span>
                </div>
                <div>
                    <strong>نوع القضية:</strong>
                    <?php echo htmlspecialchars($search_result['case']['case_type']); ?>
                </div>
                <div>
                    <strong>تاريخ الرفع:</strong>
                    <?php echo formatDate($search_result['case']['filing_date'], 'd/m/Y'); ?>
                </div>
                <div>
                    <strong>عدد الجلسات:</strong>
                    <?php echo $search_result['case']['total_sessions']; ?>
                </div>
            </div>
            
            <?php if (!empty($search_result['case']['description'])): ?>
            <div style="margin-top: 20px;">
                <strong>وصف القضية:</strong>
                <p style="margin-top: 10px; padding: 15px; background: rgba(102, 126, 234, 0.05); border-radius: 8px; line-height: 1.6;">
                    <?php echo htmlspecialchars($search_result['case']['description']); ?>
                </p>
            </div>
               <a href="manage_litigants.php?case_number=<?php 
              
              $casenumber=htmlspecialchars($search_result['case']['case_number']);
              echo htmlspecialchars($casenumber); ?>&case_id=<?php 
              
              $caseid=htmlspecialchars($search_result['case']['case_id']);
              echo htmlspecialchars($caseid); ?>&action=add" 
                                       class="btn btn-success" 
                                       style="padding: 15px 20px; font-size: 12px;"
                                       target="_blank">
                                        <i class="fas fa-eye"></i> اضافة اعضاء

                                        
                                    </a>
            <?php endif; ?>
        </div>
 
        <!-- Litigants Information -->
        <?php if (!empty($search_result['litigants'])): ?>
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-users"></i>
                </span>
                أطراف القضية
               
            </h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الاسم الكامل</th>
                            <th>رقم الهوية</th>
                            <th>الدور</th>
                            <th>رقم الهاتف</th>
                            <th>الاجراءات </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_result['litigants'] as $litigant): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($litigant['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($litigant['id_number']); ?></td>
                            <td>
                                <span class="status-badge status-scheduled">
                                    <?php echo htmlspecialchars($litigant['role']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(isset($litigant['phone_number']) ? $litigant['phone_number'] : 'غير محدد'); ?></td>
                           <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="manage_litigants.php?action=edit&id=<?php echo $litigant['litigant_id']; ?>&case_number=<?php echo $casenumber;?>&case_id=<?php echo $caseid;?>"
                                       class="btn btn-primary" 
                                       style="padding: 5px 10px; font-size: 12px;">
                                        <i class="fas fa-edit"></i> تعديل
                                    </a>
                                    <button onclick="deleteLitigant(<?php echo $litigant['litigant_id']; ?>, '<?php echo htmlspecialchars($litigant['full_name']); ?>')" 
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
        </div>
         <!-- Sessions Information -->
        <?php endif; ?>

        <!-- Sessions Information -->
        <?php if (!empty($search_result['sessions'])): ?>
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                جلسات القضية
            </h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>تاريخ الجلسة</th>
                            <th>وقت الجلسة</th>
                            <th>مكان الجلسة</th>
                            <th>حالة الجلسة</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_result['sessions'] as $session): ?>
                        <tr>
                            <td>
                                <strong><?php echo formatDate($session['session_date'], 'd/m/Y'); ?></strong>
                                <br>
                                <small style="color: #7f8c8d;">
                                    <?php 
                                    $date = new DateTime($session['session_date']);
                                    echo $date->format('l'); 
                                    ?>
                                </small>
                            </td>
                            <td>
                                <i class="fas fa-clock"></i>
                                <?php echo formatTime($session['session_time']); ?>
                            </td>
                            <td>
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($session['session_location']); ?>
                            </td>
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
                                <?php echo htmlspecialchars($session['notes'] or 'لا توجد ملاحظات'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>

        <!-- Help Card -->
        <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-question-circle"></i>
                </span>
                مساعدة
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="padding: 15px; background: rgba(52, 152, 219, 0.05); border-radius: 8px;">
                    <h4 style="color: #3498db; margin-bottom: 10px;">
                        <i class="fas fa-info-circle"></i> تنسيق رقم القضية
                    </h4>
                    <p style="color: #7f8c8d; line-height: 1.6;">
                        يجب إدخال رقم القضية بالصيغة التالية: رقم/سنة (مثال: 1001/2024)
                    </p>
                </div>
                <div style="padding: 15px; background: rgba(39, 174, 96, 0.05); border-radius: 8px;">
                    <h4 style="color: #27ae60; margin-bottom: 10px;">
                        <i class="fas fa-calendar-check"></i> حالات الجلسات
                    </h4>
                    <p style="color: #7f8c8d; line-height: 1.6;">
                        مجدولة: لم تنعقد بعد | منعقدة: تمت | مؤجلة: تم تأجيلها | ملغية: تم إلغاؤها
                    </p>
                </div>
            </div>
        </div>
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
    <!-- Footer -->
    <footer style="text-align: center; padding: 30px; color: rgba(255, 255, 255, 0.8); margin-top: 50px;">
        <p>&copy; 2024 نظام إدارة جلسات المحكمة. جميع الحقوق محفوظة.</p>
    </footer>

    <script>
        // تحسين تجربة المستخدم
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري البحث...';
            submitBtn.disabled = true;
            
            // إعادة تفعيل الزر في حالة عدم تحميل الصفحة
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });
    </script>

</body>
</html>

