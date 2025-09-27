<?php
// 数据导出页面

// 加载配置和函数
require_once dirname(__DIR__) . '/common/config.php';
require_once dirname(__DIR__) . '/common/functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

// 检查权限
check_auth();

// 获取当前登录的班级信息
$class_id = get_current_class_id();
$class_name = get_class_name($class_id);

// 获取指定日期，默认为今天
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 处理导出请求
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    try {
        // 读取学生数据
        $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
        $students = read_json_file($student_file);
        
        if (empty($students)) {
            throw new Exception('暂无学生数据');
        }
        
        // 读取签到记录
        $records_file = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
        $records = read_json_file($records_file);
        
        // 准备导出数据
        $export_data = array();
        
        // 添加表头
        $export_data[] = array('序号', '学生姓名', '签到状态', '签到时间', 'IP地址');
        
        // 添加学生签到数据
        $index = 1;
        foreach ($students as $student) {
            $student_id = $student['id'];
            $name = $student['name'];
            
            // 签到状态相关信息
            $signed = isset($records[$date][$student_id]) && $records[$date][$student_id]['signed'] ? '已签到' : '未签到';
            $time = isset($records[$date][$student_id]['timestamp']) && $records[$date][$student_id]['signed'] ? 
                    date('Y-m-d H:i:s', $records[$date][$student_id]['timestamp']) : '-';
            $ip = isset($records[$date][$student_id]['ip']) && $records[$date][$student_id]['signed'] ? 
                  $records[$date][$student_id]['ip'] : '-';
            
            $export_data[] = array($index++, $name, $signed, $time, $ip);
        }
        
        // 生成CSV内容
        $csv_content = '';
        foreach ($export_data as $row) {
            // 处理CSV中的特殊字符（引号）
            $csv_row = array();
            foreach ($row as $cell) {
                // 如果单元格包含逗号、引号或换行符，则用引号包围
                if (strpos($cell, ',') !== false || strpos($cell, '"') !== false || strpos($cell, '\n') !== false) {
                    // 替换引号为双引号
                    $cell = str_replace('"', '""', $cell);
                    $csv_row[] = '"' . $cell . '"';
                } else {
                    $csv_row[] = $cell;
                }
            }
            $csv_content .= implode(',', $csv_row) . "\n";
        }
        
        // 添加BOM以支持中文
        $csv_content = chr(0xEF) . chr(0xBB) . chr(0xBF) . $csv_content;
        
        // 设置HTTP头
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $class_name . '_' . $date . '_签到记录.csv"');
        header('Cache-Control: max-age=0');
        
        // 输出CSV内容
        echo $csv_content;
        exit;
    } catch (Exception $e) {
        // 导出失败，跳回导出页面并显示错误
        header('Location: export.php?date=' . $date . '&error=' . urlencode($e->getMessage()));
        exit;
    }
}

// 读取学生数据
$student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
$students = read_json_file($student_file);

// 读取签到记录数据
$records_file = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
$records = read_json_file($records_file);

// 排序日期列表（从新到旧）
$date_list = array_keys($records);
rsort($date_list);

// 页面标题
$page_title = '数据导出';

// 引入头部
require_once dirname(__DIR__) . '/common/header.php';
// 引入侧边栏
require_once dirname(__DIR__) . '/common/sidebar.php';
?>
<!-- 主内容区域 -->
<div class="main-content container-fluid p-4">
    <div class="export-page" id="export-page">
        <!-- 页面标题 -->
        <div class="mb-4">
            <h1 class="h3 font-bold">数据导出</h1>
            <p class="text-gray-500">导出班级签到记录数据</p>
        </div>
        
        <!-- 导出配置卡片 -->
        <div class="card mb-6">
            <div class="card-body">
                <form id="export-form" action="export.php" method="get">
                    <input type="hidden" name="action" value="download">
                    
                    <div class="mb-4">
                        <label for="export-date" class="form-label font-medium">选择导出日期</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                            <input type="date" id="export-date" name="date" class="form-control" 
                                   value="<?php echo $date; ?>" 
                                   min="<?php echo !empty($date_list) ? $date_list[count($date_list)-1] : ''; ?>" 
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <small class="form-text text-muted mt-1">
                            选择要导出的签到记录日期，默认为今天
                        </small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label font-medium">导出格式</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="format" id="format-csv" value="csv" checked>
                            <label class="form-check-label" for="format-csv">
                                <i class="fas fa-file-csv mr-2 text-green-500"></i>CSV文件（Excel兼容）
                            </label>
                        </div>
                        <small class="form-text text-muted mt-1">
                            导出的CSV文件包含学生姓名、签到状态、签到时间和IP地址等信息
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-file-export mr-2"></i>导出数据
                    </button>
                </form>
            </div>
        </div>
        
        <!-- 导出数据预览 -->
        <div class="card">
            <div class="card-header">
                <h2 class="h5 font-bold m-0">
                    <i class="fas fa-table mr-2"></i>
                    <?php echo date('Y年m月d日', strtotime($date)); ?> 数据预览
                </h2>
            </div>
            <div class="card-body">
                <!-- 搜索框 -->
                <div class="mb-4">
                    <div class="relative">
                        <input type="text" class="search-input form-control" placeholder="搜索学生..." data-table="preview-table">
                        <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- 预览表格 -->
                <div class="table-responsive">
                    <table class="table table-striped" id="preview-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>学生姓名</th>
                                <th>签到状态</th>
                                <th>签到时间</th>
                                <th>IP地址</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($students)): ?>
                                <?php $index = 1; ?>
                                <?php foreach ($students as $student): ?>
                                    <?php 
                                        $student_id = $student['id'];
                                        $signed = isset($records[$date][$student_id]) && $records[$date][$student_id]['signed'];
                                        $timestamp = isset($records[$date][$student_id]['timestamp']) ? $records[$date][$student_id]['timestamp'] : 0;
                                        $ip = isset($records[$date][$student_id]['ip']) ? $records[$date][$student_id]['ip'] : '';
                                    ?>
                                    <tr>
                                        <td><?php echo $index++; ?></td>
                                        <td><?php echo $student['name']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $signed ? 'status-success' : 'status-pending'; ?>">
                                                <?php echo $signed ? '已签到' : '未签到'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $signed && $timestamp ? date('H:i:s', $timestamp) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php echo $signed && $ip ? $ip : '-'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-6">
                                        <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                                        <p class="text-gray-500">暂无学生数据，请先到首页上传学生名单</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 页面特定JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 表单提交处理
        const exportForm = document.getElementById('export-form');
        if (exportForm) {
            exportForm.addEventListener('submit', function() {
                // 显示加载状态
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalContent = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>导出中...';
                submitBtn.disabled = true;
                
                // 防止多次提交
                this.addEventListener('submit', function(e) {
                    e.preventDefault();
                }, { once: true });
            });
        }
        
        // 显示错误信息
        <?php if (isset($_GET['error'])): ?>
            signSystem.showToast('<?php echo htmlspecialchars($_GET['error']); ?>', 'error');
        <?php endif; ?>
    });
</script>

<?php
// 引入底部
require_once dirname(__DIR__) . '/common/footer.php';
?>