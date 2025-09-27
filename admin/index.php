<?php
// 仪表盘主页

// 加载配置和函数
require_once dirname(__DIR__) . '/common/config.php';
require_once dirname(__DIR__) . '/common/functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

// 检查权限
check_auth();

// 获取当前登录的班级信息
$class_id = get_current_class_id();
$class_name = get_class_name($class_id);

// 处理上传学生名单
if (isset($_GET['action']) && $_GET['action'] === 'upload_students' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['student_file']) && $_FILES['student_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['student_file']['tmp_name'];
        $file_name = $_FILES['student_file']['name'];
        
        // 验证文件类型
        $file_info = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $file_info->file($file_tmp);
        
        if ($mime_type === 'application/json') {
            try {
                // 读取文件内容
                $content = file_get_contents($file_tmp);
                $students = json_decode($content, true);
                
                // 验证JSON格式
                if (!is_array($students)) {
                    throw new Exception('文件格式错误');
                }
                
                // 为每个学生生成唯一token（如果没有）
                foreach ($students as &$student) {
                    if (!isset($student['token']) || empty($student['token'])) {
                        $student['token'] = generate_token();
                    }
                    // 确保有id和name字段
                    if (!isset($student['id']) || !isset($student['name'])) {
                        throw new Exception('学生数据缺少必要字段');
                    }
                    // 确保有active字段
                    if (!isset($student['active'])) {
                        $student['active'] = true;
                    }
                }
                
                // 保存学生数据
                $file_path = ROOT_DIR . '/data/' . $class_id . '_students.json';
                if (write_json_file($file_path, $students)) {
                    // 响应JSON
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => '学生名单上传成功']);
                    exit;
                } else {
                    throw new Exception('保存文件失败');
                }
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => '请上传JSON格式的文件']);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '文件上传失败']);
        exit;
    }
}

// 处理下载专属链接
        if (isset($_GET['action']) && $_GET['action'] === 'download_links' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // 读取学生数据
                $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
                $students = read_json_file($student_file);
                
                if (empty($students)) {
                    throw new Exception('没有学生数据');
                }
                
                // 创建HTML内容
                $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$class_name} 家长会签到链接</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .student-card { margin-bottom: 15px; }
        .qr-code { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-5">{$class_name} 家长会签到链接</h1>
        <div class="row">
HTML;
                
                // 添加学生卡片
                foreach ($students as $student) {
                    if ($student['active']) {
                        // 生成签到链接 - 使用系统链接配置
                        $system_config = get_system_links_config();
                        $base_url = $system_config['base_url'];
                        $sign_page = $system_config['sign_page'];
                        $qr_code_api = $system_config['qr_code_api'];
                        
                        // 「系统链接」「后缀链接」格式
                        $sign_url = $base_url . $sign_page . '?token=' . $student['token'];
                        // 使用配置的API生成二维码
                        $qr_code_url = $qr_code_api . urlencode($sign_url);
                        
                        $html .= <<<HTML
            <div class="col-md-4 mb-4">
                <div class="card student-card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">{$student['name']}</h5>
                        <div class="qr-code text-center">
                            <img src="{$qr_code_url}" alt="签到二维码" class="img-fluid">
                        </div>
                        <p class="card-text text-sm text-muted">签到链接：</p>
                        <p class="card-text break-all">{$sign_url}</p>
                    </div>
                </div>
            </div>
HTML;
                    }
                }
        
        // 完成HTML内容
        $html .= <<<HTML
        </div>
    </div>
</body>
</html>
HTML;
        
        // 保存为临时文件
        $temp_dir = ROOT_DIR . '/temp';
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        $temp_file = $temp_dir . '/sign_links_' . $class_id . '_' . time() . '.html';
        file_put_contents($temp_file, $html);
        
        // 返回下载链接
        header('Content-Type: application/json');
        $download_url = '../temp/' . basename($temp_file);
        echo json_encode(['success' => true, 'download_url' => $download_url]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// 获取统计信息
$statistics = get_sign_statistics($class_id);

// 读取学生数据
$student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
$students = read_json_file($student_file);

// 页面标题
$page_title = '仪表盘';

// 引入头部
require_once dirname(__DIR__) . '/common/header.php';
// 引入侧边栏
require_once dirname(__DIR__) . '/common/sidebar.php';
?>
<!-- 主内容区域 -->
<div class="main-content container-fluid p-4">
    <div class="dashboard" id="dashboard">
        <!-- 页面标题 -->
        <div class="mb-4">
            <h1 class="h3 font-bold">欢迎回来，<?php echo $class_name; ?></h1>
            <p class="text-gray-500">今天是 <?php echo date('Y年m月d日'); ?>，以下是班级签到统计</p>
        </div>
        
        <!-- 统计卡片 -->
        <div class="stats-container mb-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">班级名称</p>
                            <h3 class="h4 font-bold"><?php echo $class_name; ?></h3>
                        </div>
                        <div class="bg-primary/10 text-primary p-3 rounded-lg">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">总人数</p>
                            <h3 class="h4 font-bold"><?php echo $statistics['total']; ?></h3>
                        </div>
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-lg">
                            <i class="fas fa-user-friends text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">已签到</p>
                            <h3 class="h4 font-bold"><?php echo $statistics['signed']; ?></h3>
                        </div>
                        <div class="bg-success/10 text-success p-3 rounded-lg">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">签到进度</p>
                            <h3 class="h4 font-bold"><?php echo $statistics['rate']; ?>%</h3>
                        </div>
                        <div class="bg-warning/10 text-warning p-3 rounded-lg">
                            <i class="fas fa-chart-pie text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 签到进度条 -->
        <div class="card mb-6">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="font-medium">今日签到进度</span>
                    <span class="text-success font-medium"><?php echo $statistics['signed']; ?>/<?php echo $statistics['total']; ?></span>
                </div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $statistics['rate']; ?>%" aria-valuenow="<?php echo $statistics['rate']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
        
        <!-- 学生管理区域 -->
        <div class="card mb-6">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 font-bold m-0">学生名单管理</h2>
                <div class="d-flex space-x-2">
                    <div class="relative">
                        <input type="text" class="search-input form-control form-control-sm" placeholder="搜索学生..." data-table="student-table">
                        <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                    </div>
                    <button id="download-links-btn" class="btn btn-primary btn-sm">
                        <i class="fas fa-download mr-1"></i>下载链接
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- 文件上传区域 -->
                <div id="upload-area" class="border-2 border-dashed border-gray-300 rounded-lg mb-4 cursor-pointer">
                    <div class="text-center py-6">
                        <i class="fas fa-cloud-upload-alt text-4xl mb-3 text-primary"></i>
                        <p>点击或拖拽JSON文件到此处上传</p>
                        <p class="text-sm text-gray-500 mt-2">支持格式：.json</p>
                        <input type="file" id="student-file" class="d-none" accept=".json">
                    </div>
                </div>
                
                <!-- 学生列表表格 -->
                <div class="table-responsive">
                    <table class="table table-hover" id="student-table">
                        <thead>
                            <tr>
                                <th scope="col">学号</th>
                                <th scope="col">姓名</th>
                                <th scope="col">专属Token</th>
                                <th scope="col">状态</th>
                                <th scope="col">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['id']; ?></td>
                                        <td><?php echo $student['name']; ?></td>
                                        <td class="text-truncate" style="max-width: 150px;" title="<?php echo $student['token']; ?>">
                                            <?php echo $student['token']; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $student['active'] ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo $student['active'] ? '启用' : '禁用'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                // 使用系统链接配置生成完整的签到链接
                                                $system_config = get_system_links_config();
                                                $base_url = $system_config['base_url'];
                                                $sign_page = $system_config['sign_page'];
                                                
                                                // 「系统链接」「后缀链接」格式
                                                $full_sign_url = $base_url . $sign_page . '?token=' . $student['token'];
                                            ?>
                                            <button class="btn btn-primary btn-sm" data-copy="<?php echo $full_sign_url; ?>">
                                                <i class="fas fa-copy mr-1"></i>复制链接
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500">暂无学生数据，请上传学生名单</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 引入底部
require_once dirname(__DIR__) . '/common/footer.php';
?>