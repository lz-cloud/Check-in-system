<?php
// 链接管理页面

// 加载配置和函数
require_once dirname(__DIR__) . '/common/config.php';
require_once dirname(__DIR__) . '/common/functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

// 检查权限
check_auth();

// 获取当前登录的班级信息
$class_id = get_current_class_id();
$class_name = get_class_name($class_id);

// 处理切换链接状态
if (isset($_GET['action']) && $_GET['action'] === 'toggle_link' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $active = $_POST['active'] === '1';
    
    try {
        // 读取学生数据
        $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
        $students = read_json_file($student_file);
        
        // 查找并更新学生状态
        $found = false;
        foreach ($students as &$student) {
            if ($student['id'] == $student_id) {
                $student['active'] = $active;
                $found = true;
                break;
            }
        }
        
        if ($found) {
            // 保存更新后的学生数据
            if (write_json_file($student_file, $students)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } else {
                throw new Exception('保存失败');
            }
        } else {
            throw new Exception('学生不存在');
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// 处理重置链接
if (isset($_GET['action']) && $_GET['action'] === 'reset_link' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    
    try {
        // 读取学生数据
        $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
        $students = read_json_file($student_file);
        
        // 查找并重置学生链接
        $found = false;
        $new_token = '';
        foreach ($students as &$student) {
            if ($student['id'] == $student_id) {
                $new_token = generate_token();
                $student['token'] = $new_token;
                $found = true;
                break;
            }
        }
        
        if ($found) {
            // 保存更新后的学生数据
            if (write_json_file($student_file, $students)) {
                // 生成新的签到链接
                $new_link = 'sign.php?token=' . $new_token;
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'new_link' => $new_link]);
                exit;
            } else {
                throw new Exception('保存失败');
            }
        } else {
            throw new Exception('学生不存在');
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// 处理切换通用链接状态
if (isset($_GET['action']) && $_GET['action'] === 'toggle_general_link' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $active = $_POST['active'] === '1';
    
    try {
        // 读取班级token配置
        $tokens_file = ROOT_DIR . '/config/class_tokens.json';
        $tokens = read_json_file($tokens_file);
        
        // 更新通用链接状态
        if (isset($tokens[$class_id])) {
            $tokens[$class_id]['active'] = $active;
            
            // 保存更新后的配置
            if (write_json_file($tokens_file, $tokens)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            } else {
                throw new Exception('保存失败');
            }
        } else {
            throw new Exception('班级配置不存在');
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// 获取通用链接信息
$general_token = isset(CLASS_TOKENS[$class_id]) ? CLASS_TOKENS[$class_id]['general_token'] : '';
$general_active = isset(CLASS_TOKENS[$class_id]) ? CLASS_TOKENS[$class_id]['active'] : false;
// 使用系统链接配置
$system_config = get_system_links_config();
$base_url = $system_config['base_url'];
$sign_page = $system_config['sign_page'];
$qr_code_api = $system_config['qr_code_api'];

// 「系统链接」「后缀链接」格式
$general_link = $base_url . $sign_page . '?class=' . $class_id . '&token=' . $general_token;

// 生成二维码（使用配置的API）
$qr_code_url = $qr_code_api . urlencode($general_link);

// 读取学生数据
$student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
$students = read_json_file($student_file);

// 页面标题
$page_title = '链接管理';

// 引入头部
require_once dirname(__DIR__) . '/common/header.php';
// 引入侧边栏
require_once dirname(__DIR__) . '/common/sidebar.php';
?>
<!-- 主内容区域 -->
<div class="main-content container-fluid p-4">
    <div class="links-page" id="links-page">
        <!-- 页面标题 -->
        <div class="mb-4">
            <h1 class="h3 font-bold">链接管理</h1>
            <p class="text-gray-500">管理班级的专属签到链接和通用签到链接</p>
        </div>
        
        <!-- 通用链接区域 -->
        <div class="card mb-6">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 font-bold m-0">通用签到链接</h2>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="general-link-toggle" <?php echo $general_active ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="general-link-toggle">
                        <?php echo $general_active ? '启用' : '禁用'; ?>
                    </label>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <!-- 二维码 -->
                        <div class="mb-4">
                            <img src="<?php echo $qr_code_url; ?>" alt="签到二维码" class="img-fluid rounded-lg shadow-sm">
                        </div>
                        <p class="text-sm text-gray-500">扫描二维码进行签到</p>
                    </div>
                    <div class="col-md-8">
                        <!-- 链接信息 -->
                        <div class="mb-4">
                            <label class="form-label font-medium mb-1">通用签到链接</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?php echo $general_link; ?>" readonly>
                                <button class="btn btn-primary" data-copy="<?php echo $general_link; ?>">
                                    <i class="fas fa-copy mr-1"></i>复制
                                </button>
                            </div>
                        </div>
                        
                        <!-- 链接说明 -->
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        通用链接可用于班级所有家长签到，访问后需要选择学生姓名。该链接长期有效，可分享到班级群或打印张贴。
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 专属链接区域 -->
        <div class="card">
            <div class="card-header">
                <h2 class="h5 font-bold m-0">专属签到链接</h2>
            </div>
            <div class="card-body">
                <!-- 搜索框 -->
                <div class="mb-4">
                    <div class="relative">
                        <input type="text" class="search-input form-control" placeholder="搜索学生..." data-table="students-grid">
                        <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- 学生链接卡片网格 -->
                <div class="card-grid" id="students-grid">
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between items-start mb-3">
                                        <div>
                                            <h5 class="card-title font-bold"><?php echo $student['name']; ?></h5>
                                            <p class="text-sm text-gray-500">学号：<?php echo $student['id']; ?></p>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input link-toggle" type="checkbox" 
                                                data-student-id="<?php echo $student['id']; ?>" 
                                                <?php echo $student['active'] ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                    
                                    <!-- 签到链接 -->
                                    <div class="mb-3">
                                        <label class="text-sm text-gray-500 block mb-1">签到链接</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-sm" 
                                                value="sign.php?token=<?php echo $student['token']; ?>" 
                                                data-student-link="<?php echo $student['id']; ?>"
                                                readonly>
                                            <button class="btn btn-primary btn-sm" 
                                                data-copy="sign.php?token=<?php echo $student['token']; ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- 操作按钮 -->
                                    <div class="d-flex justify-content-end">
                                        <button class="reset-link-btn btn btn-secondary btn-sm" 
                                            data-student-id="<?php echo $student['id']; ?>">
                                            <i class="fas fa-sync-alt mr-1"></i>重置链接
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-8">
                                <i class="fas fa-user-friends text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">暂无学生数据，请先到首页上传学生名单</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 页面特定JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 通用链接开关处理
        const generalToggle = document.getElementById('general-link-toggle');
        if (generalToggle) {
            generalToggle.addEventListener('change', async function() {
                const isActive = this.checked;
                
                try {
                    const response = await signSystem.ajax('links.php?action=toggle_general_link', {
                        method: 'POST',
                        body: new URLSearchParams({
                            active: isActive ? 1 : 0
                        })
                    });
                    
                    if (response.success) {
                        signSystem.showToast(isActive ? '通用链接已启用' : '通用链接已禁用', 'success');
                        this.nextElementSibling.textContent = isActive ? '启用' : '禁用';
                    } else {
                        signSystem.showToast(response.message || '操作失败', 'error');
                        this.checked = !isActive;
                        this.nextElementSibling.textContent = !isActive ? '启用' : '禁用';
                    }
                } catch (error) {
                    signSystem.showToast('网络错误，请重试', 'error');
                    this.checked = !isActive;
                    this.nextElementSibling.textContent = !isActive ? '启用' : '禁用';
                }
            });
        }
    });
</script>

<?php
// 引入底部
require_once dirname(__DIR__) . '/common/footer.php';
?>