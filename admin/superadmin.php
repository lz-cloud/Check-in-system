<?php
// 超级管理员仪表盘

// 加载配置和函数
require_once dirname(__DIR__) . '/common/config.php';
require_once dirname(__DIR__) . '/common/functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

// 检查是否为超级管理员
if (!is_superadmin()) {
    header('Location: index.php');
    exit;
}

// 获取所有班级信息
$all_classes = get_all_classes();
$class_count = count($all_classes);

// 获取总学生数
$total_students = 0;
foreach ($all_classes as $class_id => $class_info) {
    if ($class_id !== 'superadmin') {
        $students = get_class_students($class_id);
        $total_students += count($students);
    }
}

// 页面标题
$page_title = '超级管理员仪表盘';

// 引入头部
require_once dirname(__DIR__) . '/common/header.php';
// 引入侧边栏
require_once dirname(__DIR__) . '/common/sidebar.php';
?>
<!-- 主内容区域 -->
<div class="main-content container-fluid p-4">
    <div class="dashboard" id="dashboard">
        <!-- 页面标题 -->
        <div class="mb-6">
            <h1 class="h3 font-bold flex items-center">
                <i class="fas fa-crown text-yellow-500 mr-2"></i>
                超级管理员控制面板
            </h1>
            <p class="text-gray-500">欢迎使用系统管理功能</p>
        </div>
        
        <!-- 超级管理员统计卡片 -->
        <div class="stats-container mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- 班级数量卡片 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between items-center">
                            <div>
                                <p class="text-sm text-gray-500">总班级数</p>
                                <h3 class="h4 font-bold"><?php echo $class_count; ?></h3>
                            </div>
                            <div class="bg-blue-100 text-blue-600 p-3 rounded-lg">
                                <i class="fas fa-school text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 学生总数卡片 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between items-center">
                            <div>
                                <p class="text-sm text-gray-500">总学生数</p>
                                <h3 class="h4 font-bold"><?php echo $total_students; ?></h3>
                            </div>
                            <div class="bg-green-100 text-green-600 p-3 rounded-lg">
                                <i class="fas fa-user-graduate text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 系统状态卡片 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between items-center">
                            <div>
                                <p class="text-sm text-gray-500">系统状态</p>
                                <h3 class="h4 font-bold text-green-600">正常运行</h3>
                            </div>
                            <div class="bg-purple-100 text-purple-600 p-3 rounded-lg">
                                <i class="fas fa-server text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 管理功能卡片 -->
        <div class="management-cards mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- 班级管理卡片 -->
                <div class="card hover:shadow-lg transition-shadow duration-300">
                    <div class="card-body">
                        <div class="d-flex justify-content-between items-start">
                            <div>
                                <h4 class="font-bold text-lg text-gray-800">班级管理</h4>
                                <p class="text-gray-500 mt-1">添加、修改、删除班级信息</p>
                                <a href="class_manage.php" class="mt-3 inline-flex items-center text-primary hover:text-primary/80 transition-colors duration-200">
                                    前往管理 <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                            <div class="bg-blue-100 p-4 rounded-lg">
                                <i class="fas fa-building text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 学生管理卡片 -->
                <div class="card hover:shadow-lg transition-shadow duration-300">
                    <div class="card-body">
                        <div class="d-flex justify-content-between items-start">
                            <div>
                                <h4 class="font-bold text-lg text-gray-800">学生管理</h4>
                                <p class="text-gray-500 mt-1">管理各班级的学生数据</p>
                                <a href="student_manage.php" class="mt-3 inline-flex items-center text-primary hover:text-primary/80 transition-colors duration-200">
                                    前往管理 <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                            <div class="bg-green-100 p-4 rounded-lg">
                                <i class="fas fa-users text-green-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 最近活动日志 -->
        <div class="activity-log mb-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="font-bold text-gray-800">系统概览</h4>
                </div>
                <div class="card-body">
                    <div class="space-y-4">
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <h5 class="font-semibold text-blue-800">系统版本</h5>
                            <p class="text-gray-700">家长会签到系统 v1.0</p>
                        </div>
                        <div class="p-4 bg-green-50 rounded-lg">
                            <h5 class="font-semibold text-green-800">数据存储</h5>
                            <p class="text-gray-700">JSON文件存储 - 轻量级数据管理</p>
                        </div>
                        <div class="p-4 bg-purple-50 rounded-lg">
                            <h5 class="font-semibold text-purple-800">安全提示</h5>
                            <p class="text-gray-700">请定期备份config和data目录下的所有文件</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 引入底部
require_once dirname(__DIR__) . '/common/footer.php';
?>