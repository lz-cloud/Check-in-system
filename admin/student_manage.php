<?php
// 学生管理页面

// 加载配置和函数
require_once dirname(__DIR__) . '/common/config.php';
require_once dirname(__DIR__) . '/common/functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

// 检查是否为超级管理员
if (!is_superadmin()) {
    header('Location: index.php');
    exit;
}

// 获取所有班级信息（排除超级管理员）
$all_classes = get_all_classes();
$class_options = array_filter($all_classes, function($class_id) {
    return $class_id !== 'superadmin';
}, ARRAY_FILTER_USE_KEY);

// 默认选择第一个班级
$selected_class_id = array_key_first($class_options);

// 如果有班级选择参数，则使用该参数
if (isset($_GET['class_id']) && isset($class_options[$_GET['class_id']])) {
    $selected_class_id = $_GET['class_id'];
}

// 处理学生操作
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add':
            // 添加学生处理
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id'])) {
                $class_id = $_POST['class_id'];
                $student_info = [
                    'name' => $_POST['name']
                ];
                
                if (add_student($class_id, $student_info)) {
                    show_toast('学生添加成功', 'success');
                } else {
                    show_toast('学生添加失败', 'error');
                }
                header('Location: student_manage.php?class_id=' . $class_id);
                exit;
            }
            break;
        
        case 'edit':
            // 修改学生处理
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id']) && isset($_POST['student_id'])) {
                $class_id = $_POST['class_id'];
                $student_id = $_POST['student_id'];
                $new_info = [
                    'name' => $_POST['name']
                ];
                
                if (update_student($class_id, $student_id, $new_info)) {
                    show_toast('学生信息更新成功', 'success');
                } else {
                    show_toast('学生信息更新失败', 'error');
                }
                header('Location: student_manage.php?class_id=' . $class_id);
                exit;
            }
            break;
        
        case 'delete':
            // 删除学生处理
            if (isset($_GET['class_id']) && isset($_GET['student_id'])) {
                $class_id = $_GET['class_id'];
                $student_id = $_GET['student_id'];
                
                if (delete_student($class_id, $student_id)) {
                    show_toast('学生删除成功', 'success');
                } else {
                    show_toast('学生删除失败', 'error');
                }
                header('Location: student_manage.php?class_id=' . $class_id);
                exit;
            }
            break;
        
        case 'get_student_info':
            // 获取学生信息（用于编辑表单）
            if (isset($_GET['class_id']) && isset($_GET['student_id'])) {
                $class_id = $_GET['class_id'];
                $student_id = $_GET['student_id'];
                $students = get_class_students($class_id);
                
                $student_info = null;
                foreach ($students as $student) {
                    if ($student['id'] == $student_id) {
                        $student_info = $student;
                        break;
                    }
                }
                
                if ($student_info) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'student' => $student_info]);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => '学生不存在']);
                }
                exit;
            }
            break;
        
        case 'refresh':
            // 刷新学生列表（AJAX请求）
            if (isset($_GET['class_id'])) {
                $class_id = $_GET['class_id'];
                $students = get_class_students($class_id);
                
                // 生成学生列表HTML
                ob_start();
                foreach ($students as $student) {
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $student['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $student['name']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <code class="bg-gray-100 px-2 py-1 rounded text-xs break-all"><?php echo $student['token']; ?></code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($student['active']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    激活
                                </span>
                            <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    禁用
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button class="edit-student-btn text-blue-600 hover:text-blue-800 mr-3" data-class-id="<?php echo $class_id; ?>" data-student-id="<?php echo $student['id']; ?>">
                                <i class="fas fa-edit mr-1"></i>编辑
                            </button>
                            <button class="delete-student-btn text-red-600 hover:text-red-800" data-class-id="<?php echo $class_id; ?>" data-student-id="<?php echo $student['id']; ?>">
                                <i class="fas fa-trash mr-1"></i>删除
                            </button>
                        </td>
                    </tr>
                    <?php
                }
                $student_list_html = ob_get_clean();
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'student_list_html' => $student_list_html,
                    'student_count' => count($students)
                ]);
                exit;
            }
            break;
            
        case 'batch_upload':
            // 批量上传学生处理
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id']) && isset($_FILES['students_file'])) {
                $class_id = $_POST['class_id'];
                $file_tmp = $_FILES['students_file']['tmp_name'];
                $file_name = $_FILES['students_file']['name'];
                
                // 验证文件类型
                $file_info = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $file_info->file($file_tmp);
                
                if ($mime_type === 'application/json') {
                    try {
                        // 读取文件内容
                        $content = file_get_contents($file_tmp);
                        $students_data = json_decode($content, true);
                        
                        // 验证JSON格式
                        if (!is_array($students_data)) {
                            throw new Exception('文件格式错误');
                        }
                        
                        // 读取现有学生数据
                        $file_path = ROOT_DIR . '/data/' . $class_id . '_students.json';
                        $students = read_json_file($file_path);
                        
                        // 获取最大ID
                        $max_id = 0;
                        foreach ($students as $student) {
                            if ($student['id'] > $max_id) {
                                $max_id = $student['id'];
                            }
                        }
                        
                        // 添加新学生
                        $added_count = 0;
                        foreach ($students_data as $student_data) {
                            // 验证学生数据
                            if (isset($student_data['name']) && !empty(trim($student_data['name']))) {
                                $max_id++;
                                $student_info = [
                                    'id' => $max_id,
                                    'name' => trim($student_data['name']),
                                    'token' => generate_token(),
                                    'active' => true
                                ];
                                $students[] = $student_info;
                                $added_count++;
                            }
                        }
                        
                        // 保存学生数据
                        if (write_json_file($file_path, $students)) {
                            show_toast('成功添加 ' . $added_count . ' 名学生', 'success');
                        } else {
                            throw new Exception('保存文件失败');
                        }
                    } catch (Exception $e) {
                        show_toast($e->getMessage(), 'error');
                    }
                } else {
                    show_toast('请上传JSON格式的文件', 'error');
                }
                header('Location: student_manage.php?class_id=' . $class_id);
                exit;
            }
            break;
    }
}

// 获取选中班级的学生
$students = get_class_students($selected_class_id);

// 页面标题
$page_title = '学生管理';

// 引入头部
require_once dirname(__DIR__) . '/common/header.php';
// 引入侧边栏
require_once dirname(__DIR__) . '/common/sidebar.php';
?>
<!-- 主内容区域 -->
<div class="main-content container-fluid p-4">
    <div class="student-management" id="student-management">
        <!-- 页面标题 -->
        <div class="mb-4">
            <h1 class="h3 font-bold flex items-center">
                <i class="fas fa-user-graduate text-green-600 mr-2"></i>
                学生管理
            </h1>
            <p class="text-gray-500">管理各班级的学生信息</p>
        </div>
        
        <!-- 班级选择和添加按钮 -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
            <div class="w-full md:w-auto">
                <label for="class-select" class="block text-sm font-medium text-gray-700 mb-1">选择班级</label>
                <div class="relative">
                    <select id="class-select" class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <?php foreach ($class_options as $class_id => $class_info): ?>
                            <option value="<?php echo $class_id; ?>" <?php echo $class_id === $selected_class_id ? 'selected' : ''; ?>>
                                <?php echo $class_info['class_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex space-x-3">
                <button id="add-student-btn" class="bg-primary text-white px-4 py-2 rounded-lg shadow hover:bg-primary/90 transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>添加学生
                </button>
                <button id="batch-upload-btn" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition-colors duration-200">
                    <i class="fas fa-upload mr-2"></i>批量上传
                </button>
            </div>
        </div>
        
        <!-- 学生列表 -->
        <div class="card mb-6">
            <div class="card-body">
                <div class="mb-4 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <?php echo $class_options[$selected_class_id]['class_name']; ?> 的学生列表
                    </h3>
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                        共 <?php echo count($students); ?> 名学生
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">学生ID</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">学生姓名</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="student-list">
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $student['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $student['name']; ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <code class="bg-gray-100 px-2 py-1 rounded text-xs break-all"><?php echo $student['token']; ?></code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($student['active']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                激活
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                禁用
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="edit-student-btn text-blue-600 hover:text-blue-800 mr-3" data-class-id="<?php echo $selected_class_id; ?>" data-student-id="<?php echo $student['id']; ?>">
                                            <i class="fas fa-edit mr-1"></i>编辑
                                        </button>
                                        <button class="delete-student-btn text-red-600 hover:text-red-800" data-class-id="<?php echo $selected_class_id; ?>" data-student-id="<?php echo $student['id']; ?>">
                                            <i class="fas fa-trash mr-1"></i>删除
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 添加学生模态框 -->
<div id="add-student-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">添加学生</h3>
            <button id="close-add-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="add-student-form" method="POST" action="student_manage.php?action=add">
            <input type="hidden" id="add-class-id" name="class_id" value="<?php echo $selected_class_id; ?>">
            <div class="space-y-4">
                <div>
                    <label for="add-student-name" class="block text-sm font-medium text-gray-700 mb-1">学生姓名</label>
                    <input type="text" id="add-student-name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="cancel-add" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    取消
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    添加
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 编辑学生模态框 -->
<div id="edit-student-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">编辑学生</h3>
            <button id="close-edit-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="edit-student-form" method="POST" action="student_manage.php?action=edit">
            <input type="hidden" id="edit-class-id" name="class_id">
            <input type="hidden" id="edit-student-id" name="student_id">
            <div class="space-y-4">
                <div>
                    <label for="edit-student-name" class="block text-sm font-medium text-gray-700 mb-1">学生姓名</label>
                    <input type="text" id="edit-student-name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="cancel-edit" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    取消
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    保存
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 批量上传学生模态框 -->
<div id="batch-upload-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">批量上传学生</h3>
            <button id="close-batch-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="batch-upload-form" method="POST" action="student_manage.php?action=batch_upload" enctype="multipart/form-data">
            <input type="hidden" id="batch-class-id" name="class_id" value="<?php echo $selected_class_id; ?>">
            <div class="space-y-4">
                <div>
                    <label for="students-file" class="block text-sm font-medium text-gray-700 mb-1">选择JSON文件</label>
                    <input type="file" id="students-file" name="students_file" accept=".json" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                    <p class="text-xs text-gray-500 mt-1">请上传包含学生信息的JSON文件，格式如: [{"name":"张三"}, {"name":"李四"}]</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" id="cancel-batch" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    取消
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    上传
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 添加学生模态框控制
        const addStudentBtn = document.getElementById('add-student-btn');
        const addStudentModal = document.getElementById('add-student-modal');
        const closeAddModal = document.getElementById('close-add-modal');
        const cancelAdd = document.getElementById('cancel-add');
        const classSelect = document.getElementById('class-select');
        const addClassId = document.getElementById('add-class-id');
        
        addStudentBtn.addEventListener('click', function() {
            addClassId.value = classSelect.value;
            addStudentModal.classList.remove('hidden');
        });
        
        function closeAddModalFunc() {
            addStudentModal.classList.add('hidden');
            document.getElementById('add-student-form').reset();
        }
        
        closeAddModal.addEventListener('click', closeAddModalFunc);
        cancelAdd.addEventListener('click', closeAddModalFunc);
        
        // 编辑学生模态框控制
        const editStudentBtns = document.querySelectorAll('.edit-student-btn');
        const editStudentModal = document.getElementById('edit-student-modal');
        const closeEditModal = document.getElementById('close-edit-modal');
        const cancelEdit = document.getElementById('cancel-edit');
        
        editStudentBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const classId = this.getAttribute('data-class-id');
                const studentId = this.getAttribute('data-student-id');
                
                // 获取学生信息
                fetch('student_manage.php?action=get_student_info&class_id=' + classId + '&student_id=' + studentId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('edit-class-id').value = classId;
                            document.getElementById('edit-student-id').value = studentId;
                            document.getElementById('edit-student-name').value = data.student.name;
                            editStudentModal.classList.remove('hidden');
                        } else {
                            alert(data.message || '获取学生信息失败');
                        }
                    })
                    .catch(error => {
                        alert('获取学生信息失败');
                        console.error(error);
                    });
            });
        });
        
        function closeEditModalFunc() {
            editStudentModal.classList.add('hidden');
        }
        
        closeEditModal.addEventListener('click', closeEditModalFunc);
        cancelEdit.addEventListener('click', closeEditModalFunc);
        
        // 批量上传学生模态框控制
        const batchUploadBtn = document.getElementById('batch-upload-btn');
        const batchUploadModal = document.getElementById('batch-upload-modal');
        const closeBatchModal = document.getElementById('close-batch-modal');
        const cancelBatch = document.getElementById('cancel-batch');
        const batchClassId = document.getElementById('batch-class-id');
        
        batchUploadBtn.addEventListener('click', function() {
            batchClassId.value = classSelect.value;
            batchUploadModal.classList.remove('hidden');
        });
        
        function closeBatchModalFunc() {
            batchUploadModal.classList.add('hidden');
            document.getElementById('batch-upload-form').reset();
        }
        
        closeBatchModal.addEventListener('click', closeBatchModalFunc);
        cancelBatch.addEventListener('click', closeBatchModalFunc);
        
        // 删除学生确认
        const deleteStudentBtns = document.querySelectorAll('.delete-student-btn');
        
        deleteStudentBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const classId = this.getAttribute('data-class-id');
                const studentId = this.getAttribute('data-student-id');
                
                if (confirm('确定要删除这个学生吗？此操作不可恢复！')) {
                    window.location.href = 'student_manage.php?action=delete&class_id=' + classId + '&student_id=' + studentId;
                }
            });
        });
        
        // 班级选择变化时刷新学生列表
        classSelect.addEventListener('change', function() {
            const selectedClassId = this.value;
            window.location.href = 'student_manage.php?class_id=' + selectedClassId;
        });
        
        // 点击模态框外部关闭
        window.addEventListener('click', function(event) {
            if (event.target === addStudentModal) {
                closeAddModalFunc();
            }
            if (event.target === editStudentModal) {
                closeEditModalFunc();
            }
            if (event.target === batchUploadModal) {
                closeBatchModalFunc();
            }
        });
    });
</script>

<?php
// 引入底部
require_once dirname(__DIR__) . '/common/footer.php';
?>