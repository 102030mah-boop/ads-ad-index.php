


<!-- <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?> -->
    <!-- <div class="alert alert-success text-center">تم رفع الملف بنجاح ✅</div> -->
<!-- <?php endif; ?> -->



<?php
// استدعاء ملف قاعدة البيانات (عدّل المسار حسب مجلدك)
require_once '../config/database.php';

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

// فحص الاتصال
if (!$conn) {
    die("❌ فشل الاتصال بقاعدة البيانات");
}

// قراءة البحث (إن وجد)
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// تجهيز الاستعلام
if ($search != '') {
    $query = $conn->prepare(" SELECT case_id, case_number,created_at, case_type, filing_date
        FROM cases
        WHERE case_number LIKE ? OR case_type LIKE ?
        ORDER BY case_id DESC ");
        
    $query->execute(["%$search%", "%$search%"]);
} else {
    $query = $conn->prepare(" SELECT case_id,created_at, case_number, case_type, filing_date
        FROM cases
        ORDER BY case_id DESC ");
    $query->execute();
}

// جلب النتائج
$cases = $query->fetchAll(PDO::FETCH_ASSOC);
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
            <p>مرحباً - إدارة نظام جلسات المحكمة</p>
        </header>

        <!-- Admin Navigation -->
        <nav class="nav fade-in">
            <a href="dashboard.php" class="nav-link ">
                <i class="fas fa-tachometer-alt"></i> لوحة التحكم
            </a>
            <a href="manage_sessions.php" class="nav-link">
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



<div class="container">
    <h2 class="text-center mb-4">📁 أرشيف القضايا</h2>

    <!-- مربع البحث -->
    <form method="get" class="search-bar mb-3 text-center">
        <input type="text" name="q" class="form-control" placeholder="ابحث عن قضية..." 
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary mt-2">🔍 بحث</button>
    </form>

    <!--رفع ملف القضية-->
    <form action="upload_pdf.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="case_id" value="<?php echo $cases['case_id']; ?>">
                        <input type="file" name="pdf_file" accept="application/pdf" required>
                        <button type="submit" class="btn btn-primary btn-sm">📎 رفع</button>
                    </form>

                    <?php if (!empty($cases['pdf_file'])): ?>
                        <a href="uploads/<?php echo $cases['pdf_file']; ?>" target="_blank" class="btn btn-success btn-sm">📄 عرض الملف</a>
                    <?php endif; ?>


    <!-- جدول عرض القضايا -->
    <div class="card">
        <div class="card-body">
            <?php if (count($cases) > 0): ?>
                <table class="table table-striped table-hover">
                    <thead class="table-primary">
                        <div class="one">
                        <tr>
                            <th>الرقم التسلسلي</th>
                            <th>رقم القضية</th>
                             <th>تاريخ التسجيل</th>
                            <th>نوع القضية</th>
                             <th>التاريخ</th>
                           
                        </tr>
                       



                            </div>
                    </thead>
                         <div class="card fade-in">
            <h2>
                <span class="card-icon">
                    <i class="fas fa-folder-open"></i>
                </span>
                القضايا المسجلة (<?php echo count($cases); ?> قضية)
            </h2>
            
                    <tbody>
                        <?php foreach ($cases as $index => $case): ?>

                            <tr>
                                <td><?= $index + 1 ?></td>
                                
                                <td><?= htmlspecialchars($case['case_number']) ?></td>
                                <td><?= htmlspecialchars($case['created_at']) ?></td>
                                
                            

                                <td><?= htmlspecialchars($case['case_type']) ?></td>
                                <td><?= htmlspecialchars($case['filing_date']) ?></td>
                                
                            </tr>
                            
                        <?php endforeach; ?>
                        
                    </tbody>
                </table>
              
            <?php else: ?>
                <div class="alert alert-warning text-center">لا توجد قضايا في الأرشيف.</div>
                
            <?php endif; ?>
        </div>
    </div>
</div>


</body>
</html>