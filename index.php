<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة جلسات المحكمة</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header fade-in">
            <h1><i class="fas fa-gavel"></i> نظام إدارة جلسات المحكمة</h1>
            <p>منصة إلكترونية لمتابعة القضايا والجلسات القضائية</p>
        </header>

        <!-- Navigation -->
        <nav class="nav fade-in">
            <a href="index.php" class="nav-link active">
                <i class="fas fa-home"></i> الرئيسية
            </a>
            <a href="search.php" class="nav-link">
                <i class="fas fa-search"></i> البحث عن قضية
            </a>
            <a href="today_sessions.php" class="nav-link">
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

        <!-- Main Content -->
        <div class="row">
            <!-- Search Card -->
            <div class="card fade-in">
                <h2>
                    <span class="card-icon">
                        <i class="fas fa-search"></i>
                    </span>
                    البحث عن قضية
                </h2>
                <p style="margin-bottom: 20px; color: #7f8c8d;">
                    ابحث عن قضيتك باستخدام رقم القضية لمعرفة مواعيد الجلسات والتفاصيل
                </p>
                <form action="search.php" method="GET" class="search-form">
                    <div class="form-group">
                        <label for="case_number">رقم القضية:</label>
                        <input type="text" 
                               id="case_number" 
                               name="case_number" 
                               class="form-control" 
                               placeholder="مثال: 1001/2024"
                               pattern="[0-9]+/[0-9]+"
                               title="يرجى إدخال رقم القضية بالصيغة الصحيحة (مثال: 1001/2024)"
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> بحث
                    </button>
                </form>
            </div>

            <!-- Today's Sessions Card -->
            <div class="card fade-in">
                <h2>
                    <span class="card-icon">
                        <i class="fas fa-calendar-day"></i>
                    </span>
                    جلسات اليوم
                </h2>
                <p style="margin-bottom: 20px; color: #7f8c8d;">
                    اطلع على جميع الجلسات المجدولة لليوم الحالي
                </p>
                <div style="text-align: center;">
                    <a href="today_sessions.php" class="btn btn-success">
                        <i class="fas fa-eye"></i> عرض جلسات اليوم
                    </a>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card fade-in">
                <h2>
                    <span class="card-icon">
                        <i class="fas fa-chart-bar"></i>
                    </span>
                    إحصائيات سريعة
                </h2>
                <div id="statistics">
                    <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                        <div style="text-align: center; padding: 20px; background: rgba(52, 152, 219, 0.1); border-radius: 12px; flex: 1; min-width: 150px;">
                            <div style="font-size: 2rem; font-weight: bold; color: #3498db;" id="total-cases">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                            <div style="color: #7f8c8d; margin-top: 5px;">إجمالي القضايا</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: rgba(39, 174, 96, 0.1); border-radius: 12px; flex: 1; min-width: 150px;">
                            <div style="font-size: 2rem; font-weight: bold; color: #27ae60;" id="today-sessions">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                            <div style="color: #7f8c8d; margin-top: 5px;">جلسات اليوم</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: rgba(243, 156, 18, 0.1); border-radius: 12px; flex: 1; min-width: 150px;">
                            <div style="font-size: 2rem; font-weight: bold; color: #f39c12;" id="upcoming-sessions">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                            <div style="color: #7f8c8d; margin-top: 5px;">الجلسات القادمة</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instructions Card -->
            <div class="card fade-in">
                <h2>
                    <span class="card-icon">
                        <i class="fas fa-info-circle"></i>
                    </span>
                    كيفية الاستخدام
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="padding: 20px; background: rgba(102, 126, 234, 0.05); border-radius: 12px; border-right: 4px solid #667eea;">
                        <h3 style="color: #667eea; margin-bottom: 10px;">
                            <i class="fas fa-search"></i> البحث عن قضية
                        </h3>
                        <p style="color: #7f8c8d; line-height: 1.6;">
                            أدخل رقم القضية في حقل البحث للحصول على معلومات القضية ومواعيد جلساتها
                        </p>
                    </div>
                    <div style="padding: 20px; background: rgba(39, 174, 96, 0.05); border-radius: 12px; border-right: 4px solid #27ae60;">
                        <h3 style="color: #27ae60; margin-bottom: 10px;">
                            <i class="fas fa-calendar-day"></i> جلسات اليوم
                        </h3>
                        <p style="color: #7f8c8d; line-height: 1.6;">
                            اطلع على جميع الجلسات المجدولة لليوم الحالي مع تفاصيل الوقت والمكان
                        </p>
                    </div>
                    <div style="padding: 20px; background: rgba(231, 76, 60, 0.05); border-radius: 12px; border-right: 4px solid #e74c3c;">
                        <h3 style="color: #e74c3c; margin-bottom: 10px;">
                            <i class="fas fa-user-shield"></i> للمسؤولين
                        </h3>
                        <p style="color: #7f8c8d; line-height: 1.6;">
                            يمكن للمسؤولين تسجيل الدخول لإدارة الجلسات وترحيلها حسب الحاجة
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="text-align: center; padding: 30px; color: rgba(255, 255, 255, 0.8); margin-top: 50px;">
        <p>&copy; 2024 نظام إدارة جلسات المحكمة. جميع الحقوق محفوظة.</p>
    </footer>

    <script>
        // تحميل الإحصائيات
        document.addEventListener('DOMContentLoaded', function() {
            loadStatistics();
        });

        function loadStatistics() {
            fetch('api/statistics.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('total-cases').textContent = data.total_cases;
                        document.getElementById('today-sessions').textContent = data.today_sessions;
                        document.getElementById('upcoming-sessions').textContent = data.upcoming_sessions;
                    } else {
                        document.getElementById('total-cases').textContent = '0';
                        document.getElementById('today-sessions').textContent = '0';
                        document.getElementById('upcoming-sessions').textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('خطأ في تحميل الإحصائيات:', error);
                    document.getElementById('total-cases').textContent = '0';
                    document.getElementById('today-sessions').textContent = '0';
                    document.getElementById('upcoming-sessions').textContent = '0';
                });
        }

        // تحسين تجربة المستخدم
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري البحث...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>

