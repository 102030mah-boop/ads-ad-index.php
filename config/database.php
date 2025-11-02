<?php
/**
 * ملف الاتصال بقاعدة البيانات
 * Database Connection Configuration
 */

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'court_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    public $conn;

    /**
     * الحصول على اتصال قاعدة البيانات
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "خطأ في الاتصال بقاعدة البيانات: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

/**
 * دالة مساعدة للحصول على اتصال قاعدة البيانات
 */
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}

/**
 * دالة لتنظيف المدخلات
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * دالة للتحقق من صحة رقم القضية
 */
function validateCaseNumber($case_number) {
    // التحقق من أن رقم القضية يحتوي على أرقام وشرطة مائلة فقط
    return preg_match('/^[0-9]+\/[0-9]+$/', $case_number);
}

/**
 * دالة لتنسيق التاريخ
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * دالة لتنسيق الوقت
 */
function formatTime($time, $format = 'H:i') {
    if (empty($time)) return '';
    
    try {
        $timeObj = new DateTime($time);
        return $timeObj->format($format);
    } catch (Exception $e) {
        return $time;
    }

}

// function formatTime($conn, $format = 'H:i') {
//     if (empty($conn)) return '';
    
//     try {
//         $timeObj = new DateTime($conn);
//         return $timeObj->format($format);
//     } catch (Exception $e) {
//         return $conn;
//     }
    
// }
?>

