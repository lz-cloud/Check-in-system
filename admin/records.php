<?php
// 签到记录页面

// 加载配置和函数
require_once dirname(__DIR__) . '/common/config.php';
require_once dirname(__DIR__) . '/common/functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

// 检查权限
check_auth();

// 获取当前登录的班级信息
$class_id = get_current_class_id();
$class_name = get_class_name($class_id);

// 获取当前日期或指定日期
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$today = date('Y-m-d');

// 处理重新开始签到
if (isset($_GET['action']) && $_GET['action'] === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 读取签到记录文件
        $records_file = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
        $records = read_json_file($records_file);
        
        // 读取学生数据
        $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
        $students = read_json_file($student_file);
        
        // 重置当前日期的签到记录
        $records[$today] = array();
        foreach ($students as $student) {
            $records[$today][$student['id']] = array('signed' => false);
        }
        
        // 保存更新后的记录
        if (write_json_file($records_file, $records)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            throw new Exception('重置失败');
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// 读取学生数据
$student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
$students = read_json_file($student_file);

// 读取签到记录数据
$records_file = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
$records = read_json_file($records_file);

// 计算总人数、已签到人数和未签到人数
$total_count = count($students);
$signed_count = 0;
$unsigned_count = $total_count;

// 初始化指定日期的签到记录
if (!isset($records[$date])) {
    $records[$date] = array();
    foreach ($students as $student) {
        $records[$date][$student['id']] = array('signed' => false);
    }
}

// 计算签到统计
foreach ($students as $student) {
    $student_id = $student['id'];
    if (isset($records[$date][$student_id]) && $records[$date][$student_id]['signed']) {
        $signed_count++;
        $unsigned_count--;
    }
}

// 计算签到率
$sign_rate = $total_count > 0 ? round(($signed_count / $total_count) * 100, 1) : 0;

// 排序日期列表（从新到旧）
$date_list = array_keys($records);
rsort($date_list);

// 页面标题
$page_title = '签到记录';

// 引入头部
require_once dirname(__DIR__) . '/common/header.php';
// 引入侧边栏
require_once dirname(__DIR__) . '/common/sidebar.php';
?>
<!-- 主内容区域 -->
<div class="main-content container-fluid p-4">
    <div class="records-page" id="records-page">
        <!-- 页面标题 -->
        <div class="mb-4">
            <h1 class="h3 font-bold">签到记录</h1>
            <p class="text-gray-500">查看和管理班级签到数据</p>
        </div>
        
        <!-- 统计卡片 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">总人数</p>
                            <h3 class="h2 font-bold mt-1"><?php echo $total_count; ?></h3>
                        </div>
                        <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                            <i class="fas fa-user-friends text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">已签到</p>
                            <h3 class="h2 font-bold mt-1 text-green-500"><?php echo $signed_count; ?></h3>
                        </div>
                        <div class="bg-green-100 text-green-600 p-3 rounded-full">
                            <i class="fas fa-check-circle text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">未签到</p>
                            <h3 class="h2 font-bold mt-1 text-red-500"><?php echo $unsigned_count; ?></h3>
                        </div>
                        <div class="bg-red-100 text-red-600 p-3 rounded-full">
                            <i class="fas fa-times-circle text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 签到进度 -->
        <div class="card mb-6">
            <div class="card-body">
                <div class="d-flex justify-content-between items-center mb-2">
                    <h5 class="font-bold m-0">签到进度</h5>
                    <span class="text-sm font-medium"><?php echo $sign_rate; ?>%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: <?php echo $sign_rate; ?>%" 
                         aria-valuenow="<?php echo $sign_rate; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 日期选择和操作 -->
        <div class="card mb-6">
            <div class="card-body">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <!-- 日期选择器 -->
                    <div class="input-group" style="max-width: 300px;">
                        <label class="input-group-text" for="date-filter">
                            <i class="fas fa-calendar-alt mr-2"></i>选择日期
                        </label>
                        <input type="date" class="form-control" id="date-filter" 
                               value="<?php echo $date; ?>" 
                               min="<?php echo !empty($date_list) ? $date_list[count($date_list)-1] : ''; ?>" 
                               max="<?php echo $today; ?>">
                    </div>
                    
                    <!-- 操作按钮 -->
                    <div class="d-flex gap-3">
                        <!-- 重新开始签到按钮 -->
                        <?php if ($date === $today): ?>
                            <button id="reset-sign-btn" class="btn btn-warning">
                                <i class="fas fa-redo-alt mr-2"></i>重新开始签到
                            </button>
                        <?php endif; ?>
                        
                        <!-- 导出按钮 -->
                        <a href="export.php?date=<?php echo $date; ?>" class="btn btn-primary">
                            <i class="fas fa-file-export mr-2"></i>导出数据
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 签到记录表格 -->
        <div class="card">
            <div class="card-header">
                <h2 class="h5 font-bold m-0">
                    <i class="fas fa-list-ul mr-2"></i>
                    <?php echo date('Y年m月d日', strtotime($date)); ?> 签到记录
                </h2>
            </div>
            <div class="card-body">
                <!-- 搜索框 -->
                <div class="mb-4">
                    <div class="relative">
                        <input type="text" class="search-input form-control" placeholder="搜索学生..." data-table="records-table">
                        <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- 表格 -->
                <div class="table-responsive">
                    <table class="table table-striped" id="records-table">
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

<!-- 确认对话框 -->
<div class="modal" id="reset-confirm-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-warning mr-2"></i>确认重新开始签到</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>确定要重新开始签到吗？此操作将重置今天的签到记录，但会保留历史数据。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-warning" id="confirm-reset-btn">确认重置</button>
            </div>
        </div>
    </div>
</div>

<!-- 页面特定JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 日期选择器切换
        const dateFilter = document.getElementById('date-filter');
        if (dateFilter) {
            dateFilter.addEventListener('change', function() {
                window.location.href = 'records.php?date=' + this.value;
            });
        }
        
        // 重新开始签到按钮
        const resetBtn = document.getElementById('reset-sign-btn');
        const resetModal = document.getElementById('reset-confirm-modal');
        const confirmResetBtn = document.getElementById('confirm-reset-btn');
        
        if (resetBtn && resetModal) {
            resetBtn.addEventListener('click', function() {
                $(resetModal).modal('show');
            });
        }
        
        if (confirmResetBtn) {
            confirmResetBtn.addEventListener('click', async function() {
                try {
                    $(confirmResetBtn).html('<i class="fas fa-spinner fa-spin mr-2"></i>重置中...');
                    $(confirmResetBtn).prop('disabled', true);
                    
                    const response = await signSystem.ajax('records.php?action=reset', {
                        method: 'POST'
                    });
                    
                    if (response.success) {
                        signSystem.showToast('签到已重置', 'success');
                        $(resetModal).modal('hide');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        signSystem.showToast(response.message || '重置失败', 'error');
                    }
                } catch (error) {
                    signSystem.showToast('网络错误，请重试', 'error');
                } finally {
                    $(confirmResetBtn).html('确认重置');
                    $(confirmResetBtn).prop('disabled', false);
                }
            });
        }
    });
</script>

<?php
// 引入底部
require_once dirname(__DIR__) . '/common/footer.php';
?>