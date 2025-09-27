<?php
// 智能签到入口页面

// 加载配置和函数
require_once dirname(__FILE__) . '/common/config.php';
require_once dirname(__FILE__) . '/common/functions.php';

// 智能路由系统 - 自动识别链接类型
$mode = 'error'; // 默认错误模式
$student_info = null;
$class_info = null;
$students_list = null;
$has_signed = false;
$current_date = date('Y-m-d');

// 检查专属链接模式
if (isset($_GET['token']) && !isset($_GET['class'])) {
    $token = $_GET['token'];
    
    // 查找匹配的学生
    foreach (PASSWORDS as $class_id => $class_data) {
        $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
        if (file_exists($student_file)) {
            $students = read_json_file($student_file);
            foreach ($students as $student) {
                if ($student['token'] === $token && $student['active']) {
                    $student_info = $student;
                    $class_info = $class_data;
                    $mode = 'exclusive';
                    break 2;
                }
            }
        }
    }
    
    // 检查签到状态
    if ($mode === 'exclusive') {
        $records_file = ROOT_DIR . '/data/sign_records_' . array_search($class_info, PASSWORDS) . '.json';
        if (file_exists($records_file)) {
            $records = read_json_file($records_file);
            $student_id = $student_info['id'];
            $has_signed = isset($records[$current_date][$student_id]) && $records[$current_date][$student_id]['signed'];
        }
    }
}
// 检查通用链接模式
elseif (isset($_GET['class']) && isset($_GET['token'])) {
    $class_id = $_GET['class'];
    $token = $_GET['token'];
    
    // 验证班级和通用token
    if (isset(CLASS_TOKENS[$class_id]) && CLASS_TOKENS[$class_id]['general_token'] === $token && CLASS_TOKENS[$class_id]['active']) {
        $class_info = CLASS_TOKENS[$class_id];
        
        // 读取该班级的学生列表
        $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
        if (file_exists($student_file)) {
            $students = read_json_file($student_file);
            
            // 读取签到记录
            $records_file = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
            $records = file_exists($records_file) ? read_json_file($records_file) : array();
            
            // 过滤出未签到的学生
            $students_list = array();
            foreach ($students as $student) {
                if ($student['active']) {
                    $student_id = $student['id'];
                    $is_signed = isset($records[$current_date][$student_id]) && $records[$current_date][$student_id]['signed'];
                    
                    $students_list[] = array(
                        'id' => $student_id,
                        'name' => $student['name'],
                        'signed' => $is_signed
                    );
                }
            }
            
            $mode = 'general';
        }
    }
}

// 页面标题
$page_title = '家长会签到';

// 添加签到页面专用CSS
$custom_css = '/assets/css/sign-styles.css';

// 引入头部
require_once dirname(__FILE__) . '/common/header.php';
?>

<!-- 主内容区域 -->
<div class="container-fluid p-4">
    <div class="sign-page" id="sign-page">
        <!-- 专属链接模式 -->
        <?php if ($mode === 'exclusive'): ?>
            <div class="exclusive-mode">
                <!-- 顶部欢迎横幅 -->
                <div class="welcome-banner bg-primary text-white rounded-lg p-6 mb-6 text-center">
                    <h1 class="h2 font-bold mb-2">欢迎参加家长会</h1>
                    <p class="text-white-800">
                        <?php echo $class_info['class_name']; ?> - <?php echo $student_info['name']; ?>的家长
                    </p>
                </div>
                
                <!-- 签到卡片 -->
                <div class="card max-w-md mx-auto">
                    <div class="card-body text-center">
                        <?php if ($has_signed): ?>
                            <!-- 已签到状态 -->
                            <div class="sign-success">
                                <div class="success-icon bg-success text-white rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-check-circle text-4xl"></i>
                                </div>
                                <h2 class="h3 font-bold text-success mb-2">签到成功！</h2>
                                <p class="text-gray-600 mb-4">感谢您参加本次家长会</p>
                                <div class="text-sm text-gray-500">
                                    <p>签到时间：
                                        <?php 
                                            $records_file = ROOT_DIR . '/data/sign_records_' . array_search($class_info, PASSWORDS) . '.json';
                                            $records = file_exists($records_file) ? read_json_file($records_file) : array();
                                            $student_id = $student_info['id'];
                                            $sign_time = isset($records[$current_date][$student_id]['timestamp']) ? 
                                                        date('Y年m月d日 H:i:s', $records[$current_date][$student_id]['timestamp']) : '';
                                            echo $sign_time;
                                        ?>
                                    </p>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- 未签到状态 -->
                            <div class="sign-form">
                                <div class="student-avatar bg-gray-100 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-user-graduate text-4xl text-primary"></i>
                                </div>
                                <h2 class="h3 font-bold mb-2"><?php echo $student_info['name']; ?></h2>
                                <p class="text-gray-600 mb-6"><?php echo $class_info['class_name']; ?></p>
                                <button id="sign-btn" class="btn btn-primary btn-lg pulse-animation">
                                    <i class="fas fa-check-circle mr-2"></i>点击签到
                                </button>
                                <p class="text-xs text-gray-500 mt-4">
                                    点击按钮即表示您已到达会场，签到信息将实时同步至班主任
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 页脚信息 -->
                <div class="text-center mt-8 text-sm text-gray-500">
                    <p>© <?php echo date('Y'); ?> 家长会签到系统</p>
                </div>
            </div>
        
        <!-- 通用链接模式 -->
        <?php elseif ($mode === 'general'): ?>
            <div class="general-mode">
                <!-- 顶部标题 -->
                <div class="text-center mb-6">
                    <h1 class="h2 font-bold">家长会签到</h1>
                    <p class="text-gray-600">请选择您的孩子</p>
                </div>
                
                <!-- 班级信息卡片 -->
                <div class="card mb-6">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-full p-3 mr-4">
                                <i class="fas fa-school text-xl"></i>
                            </div>
                            <div>
                                <h2 class="h5 font-bold mb-1"><?php echo $class_info['class_name']; ?></h2>
                                <p class="text-sm text-gray-500">选择学生并完成签到</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 学生选择卡片 -->
                <div class="card">
                    <div class="card-body">
                        <!-- 搜索框 -->
                        <div class="mb-6">
                            <div class="relative">
                                <input type="text" id="student-search" class="form-control" placeholder="搜索学生...">
                                <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- 选择提示 -->
                        <div id="selection-hint" class="text-center py-6 fade-in-up">
                            <i class="fas fa-hand-pointer text-4xl text-primary/60 mb-3"></i>
                            <p class="text-gray-500">请从下方列表选择您的孩子</p>
                        </div>
                        
                        <!-- 已选学生信息 -->
                        <div id="selected-student-info" class="hidden mb-4 p-4 bg-primary/5 rounded-lg border border-primary/10 fade-in-up">
                            <div class="d-flex justify-content-between items-center">
                                <div class="d-flex items-center">
                                    <div class="bg-primary/10 rounded-full w-12 h-12 flex items-center justify-center mr-3">
                                        <i class="fas fa-user-graduate text-2xl text-primary"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-primary font-medium">已选择</p>
                                        <p id="selected-student-name" class="font-bold text-lg"></p>
                                    </div>
                                </div>
                                <button id="clear-selection" class="text-primary hover:text-primary/70 transition-colors">
                                    <i class="fas fa-times-circle text-xl"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- 学生列表 -->
                        <div id="students-list" class="students-list">
                            <?php if (!empty($students_list)): ?>
                                <?php foreach ($students_list as $student): ?>
                                    <div class="student-item card mb-3 p-3 cursor-pointer transition-all hover:shadow-md" 
                                         data-student-id="<?php echo $student['id']; ?>" 
                                         data-student-name="<?php echo $student['name']; ?>">
                                        <div class="d-flex justify-content-between items-center">
                                            <div class="d-flex items-center">
                                                <div class="bg-gray-100 rounded-full w-10 h-10 flex items-center justify-center mr-3">
                                                    <i class="fas fa-user text-gray-500"></i>
                                                </div>
                                                <span class="font-medium"><?php echo $student['name']; ?></span>
                                            </div>
                                            <span class="status-badge <?php echo $student['signed'] ? 'status-success' : 'status-pending'; ?>">
                                                <?php echo $student['signed'] ? '已签到' : '未签到'; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-6">
                                    <i class="fas fa-user-friends text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">暂无学生数据</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 签到按钮 -->
                        <div class="mt-6 text-center">
                            <button id="sign-btn" class="btn btn-primary btn-lg pulse-animation disabled:opacity-50 disabled:pointer-events-none" disabled>
                                <i class="fas fa-check-circle mr-2"></i>确认签到
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 页脚信息 -->
                <div class="text-center mt-8 text-sm text-gray-500">
                    <p>© <?php echo date('Y'); ?> 家长会签到系统</p>
                </div>
            </div>
        
        <!-- 错误页面 -->
        <?php else: ?>
            <div class="error-mode">
                <div class="card max-w-md mx-auto">
                    <div class="card-body text-center">
                        <div class="error-icon bg-danger text-white rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-exclamation-circle text-4xl"></i>
                        </div>
                        <h2 class="h3 font-bold text-danger mb-2">链接错误</h2>
                        <p class="text-gray-600 mb-6">签到链接无效或已过期，请联系班主任获取正确的签到链接。</p>
                        <a href="admin/login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt mr-2"></i>管理员登录
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 签到确认对话框 -->
<div class="modal" id="sign-confirm-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-check-circle text-primary mr-2"></i>确认签到</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="student-avatar bg-primary text-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-2">
                        <i class="fas fa-user-graduate text-3xl"></i>
                    </div>
                    <p id="confirm-student-name" class="font-bold text-lg"></p>
                </div>
                <p class="text-center">您确定要为以上学生完成签到吗？</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="confirm-sign-btn">确认签到</button>
            </div>
        </div>
    </div>
</div>

<!-- 烟花容器 -->
<div id="fireworks-container"></div>

<?php
// 引入底部
require_once dirname(__FILE__) . '/common/footer.php';
?>