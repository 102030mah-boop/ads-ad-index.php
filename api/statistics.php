<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/database.php';

$response = [
    'success' => false,
    'total_cases' => 0,
    'today_sessions' => 0,
    'upcoming_sessions' => 0
];

try {
    $conn = getDBConnection();
    
    // إجمالي القضايا
    $cases_query = "SELECT COUNT(*) as total FROM cases";
    $cases_stmt = $conn->prepare($cases_query);
    $cases_stmt->execute();
    $cases_result = $cases_stmt->fetch();
    $response["total_cases"] = isset($cases_result["total"]) ? $cases_result["total"] : 0;
    
    // جلسات اليوم
    $today = date('Y-m-d');
    $today_query = "SELECT COUNT(*) as total FROM sessions WHERE session_date = :today";
    $today_stmt = $conn->prepare($today_query);
    $today_stmt->bindParam(':today', $today);
    $today_stmt->execute();
    $today_result = $today_stmt->fetch();
    $response["today_sessions"] = isset($today_result["total"]) ? $today_result["total"] : 0;
    
    // الجلسات القادمة (بعد اليوم)
    $upcoming_query = "SELECT COUNT(*) as total FROM sessions WHERE session_date > :today";
    $upcoming_stmt = $conn->prepare($upcoming_query);
    $upcoming_stmt->bindParam(':today', $today);
    $upcoming_stmt->execute();
    $upcoming_result = $upcoming_stmt->fetch();
    $response["upcoming_sessions"] = isset($upcoming_result["total"]) ? $upcoming_result["total"] : 0;
    
    $response['success'] = true;
    
} catch (PDOException $e) {
    $response['error'] = 'خطأ في قاعدة البيانات';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

