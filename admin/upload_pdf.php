
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
</head>
<body>
    
</body>
</html>


<!-- 
<?php
require_once '../config/database.php';
if (isset($_FILES['pdf_file']) && isset($_POST['case_id'])) {
    $case_id = $_POST['case_id'];
    $file_name = basename($_FILES['pdf_file']['name']);
    $target_dir = "uploads/";
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_file)) {
        // تحديث اسم الملف داخل قاعدة البيانات
       require_once '../config/database.php';
        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("UPDATE cases SET pdf_file = :file WHERE case_id = :case_id");
        $stmt->bindParam(':file', $file_name);
        $stmt->bindParam(':case_id', $case_id);
        $stmt->execute();

        echo "<script>alert('تم رفع الملف بنجاح'); window.history.back();</script>";
    } else {
        echo "<script>alert('فشل رفع الملف'); window.history.back();</script>";
    }
}
?> -->
<?php
require_once '../config/database.php';

if (isset($_FILES['pdf_file']) && isset($_POST['case_id'])) {

    $case_id = $_POST['case_id'];
    $file_name = time() . "_" . basename($_FILES['pdf_file']['name']); // ✅ اسم فريد
    $target_dir = "uploads/";
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_file)) {

        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("UPDATE cases SET pdf_file = :file WHERE case_id = :case_id");
        $stmt->bindParam(':file', $file_name);
        $stmt->bindParam(':case_id', $case_id);
        $stmt->execute();

        // ✅ توجيه بعد الرفع
        header("Location: archive_cases.php?upload=success");
        exit;

    } else {
        header("Location: archive_cases.php?upload=failed");
        exit;
    }
} else {
    header("Location: archive_cases.php?upload=error");
    exit;
}
?>