<?php
// 顶部导航栏文件，用于后台管理页面
// 加载权限验证
require_once dirname(__FILE__) . '/auth.php';

// 获取当前页面
$current_page = basename($_SERVER['SCRIPT_NAME']);

// 获取当前登录的班级信息
$class_id = get_current_class_id();
$class_name = get_class_name($class_id);
?>
<!-- 顶部导航栏 -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-md z-40 sticky-top">
    <div class="container-fluid px-4">
        <!-- 左侧Logo和班级信息 -->
        <div class="d-flex items-center">
            <div class="bg-primary text-white rounded-full w-10 h-10 flex items-center justify-center mr-3">
                <i class="fas fa-graduation-cap text-lg"></i>
            </div>
            <div>
                <h4 class="font-bold text-gray-800 mb-0">家长会签到系统</h4>
                <p class="text-xs text-gray-500"><?php echo $class_name; ?></p>
            </div>
        </div>
        
        <!-- 右侧导航菜单 -->
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav space-x-1">
                <?php if (is_superadmin()): ?>
                <!-- 超级管理员菜单项 -->
                <li class="nav-item">
                    <a href="superadmin.php" class="nav-link px-4 py-2 rounded-lg transition-all duration-200 <?php echo $current_page === 'superadmin.php' ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-crown mr-2"></i>超级管理
                    </a>
                </li>
                <li class="nav-item">
                    <a href="class_manage.php" class="nav-link px-4 py-2 rounded-lg transition-all duration-200 <?php echo $current_page === 'class_manage.php' ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-school mr-2"></i>班级管理
                    </a>
                </li>
                <li class="nav-item">
                    <a href="student_manage.php" class="nav-link px-4 py-2 rounded-lg transition-all duration-200 <?php echo $current_page === 'student_manage.php' ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-user-graduate mr-2"></i>学生管理
                    </a>
                </li>
                <?php else: ?>
                <!-- 普通班级菜单项 -->
                <li class="nav-item">
                    <a href="index.php" class="nav-link px-4 py-2 rounded-lg transition-all duration-200 <?php echo $current_page === 'index.php' ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-home mr-2"></i>首页
                    </a>
                </li>
                <li class="nav-item">
                    <a href="links.php" class="nav-link px-4 py-2 rounded-lg transition-all duration-200 <?php echo $current_page === 'links.php' ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-link mr-2"></i>链接管理
                    </a>
                </li>
                <li class="nav-item">
                    <a href="records.php" class="nav-link px-4 py-2 rounded-lg transition-all duration-200 <?php echo $current_page === 'records.php' ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-chart-bar mr-2"></i>签到记录
                    </a>
                </li>
                <li class="nav-item">
                    <a href="export.php" class="nav-link px-4 py-2 rounded-lg transition-all duration-200 <?php echo $current_page === 'export.php' ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        <i class="fas fa-file-export mr-2"></i>数据导出
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <button id="logout-btn" class="nav-link text-red-600 hover:bg-red-50 px-4 py-2 rounded-lg transition-all duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>退出登录
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- 退出登录确认脚本 -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function() {
                if (confirm('确定要退出登录吗？')) {
                    window.location.href = 'login.php?action=logout';
                }
            });
        }
    });
</script>