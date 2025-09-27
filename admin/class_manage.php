<?php
// 班级管理页面

// 加载配置和函数
require_once dirname(__DIR__) . '/common/config.php';
require_once dirname(__DIR__) . '/common/functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

// 检查是否为超级管理员
if (!is_superadmin()) {
    header('Location: index.php');
    exit;
}

// 处理班级操作
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add':
            // 添加班级处理
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $class_id = $_POST['class_id'];
                $class_name = $_POST['class_name'];
                $password = $_POST['password'];
                
                if (add_class($class_id, $class_name, $password)) {
                    show_toast('班级添加成功', 'success');
                } else {
                    show_toast('班级添加失败，班级ID可能已存在', 'error');
                }
                header('Location: class_manage.php');
                exit;
            }
            break;
        
        case 'edit':
            // 修改班级处理
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id'])) {
                $class_id = $_POST['class_id'];
                $new_info = array_filter($_POST, function($key) {
                    return $key !== 'class_id' && $key !== 'action';
                }, ARRAY_FILTER_USE_KEY);
                
                if (update_class($class_id, $new_info)) {
                    show_toast('班级信息更新成功', 'success');
                } else {
                    show_toast('班级信息更新失败', 'error');
                }
                header('Location: class_manage.php');
                exit;
            }
            break;
        
        case 'delete':
            // 删除班级处理
            if (isset($_GET['class_id'])) {
                $class_id = $_GET['class_id'];
                
                if (delete_class($class_id)) {
                    show_toast('班级删除成功', 'success');
                } else {
                    show_toast('班级删除失败', 'error');
                }
                header('Location: class_manage.php');
                exit;
            }
            break;
        
        case 'get_class_info':
            // 获取班级信息（用于编辑表单）
            if (isset($_GET['class_id'])) {
                $class_id = $_GET['class_id'];
                $all_classes = get_all_classes();
                
                if (isset($all_classes[$class_id])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'class' => $all_classes[$class_id]]);
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => '班级不存在']);
                }
                exit;
            }
            break;
    }
}

// 获取所有班级信息
$all_classes = get_all_classes();

// 页面标题
$page_title = '班级管理';

// 引入头部
require_once dirname(__DIR__) . '/common/header.php';
// 引入侧边栏
require_once dirname(__DIR__) . '/common/sidebar.php';
?>
<!-- 主内容区域 -->
<div class="main-content container-fluid p-4">
    <div class="class-management" id="class-management">
        <!-- 页面标题 -->
        <div class="mb-4">
            <h1 class="h3 font-bold flex items-center">
                <i class="fas fa-school text-blue-600 mr-2"></i>
                班级管理
            </h1>
            <p class="text-gray-500">添加、修改和删除班级信息</p>
        </div>
        
        <!-- 添加班级按钮 -->
        <div class="mb-6">
            <button id="add-class-btn" class="bg-primary text-white px-4 py-2 rounded-lg shadow hover:bg-primary/90 transition-colors duration-200">
                <i class="fas fa-plus mr-2"></i>添加班级
            </button>
        </div>
        
        <!-- 班级列表 -->
        <div class="card mb-6">
            <div class="card-body">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">班级ID</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">班级名称</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($all_classes as $class_id => $class_info): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $class_id; ?>
                                        <?php if ($class_id === 'superadmin'): ?>
                                            <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                系统管理员
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $class_info['class_name']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $class_info['role'] === 'superadmin' ? '超级管理员' : '普通班级'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($class_id !== 'superadmin'): ?>
                                            <button class="edit-class-btn text-blue-600 hover:text-blue-800 mr-3" data-class-id="<?php echo $class_id; ?>">
                                                <i class="fas fa-edit mr-1"></i>编辑
                                            </button>
                                            <button class="delete-class-btn text-red-600 hover:text-red-800" data-class-id="<?php echo $class_id; ?>">
                                                <i class="fas fa-trash mr-1"></i>删除
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400">系统账号不可修改</span>
                                        <?php endif; ?>
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

<!-- 添加班级模态框 -->
<div id="add-class-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">添加班级</h3>
            <button id="close-add-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="add-class-form" method="POST" action="class_manage.php?action=add">
            <div class="space-y-4">
                <div>
                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">班级ID</label>
                    <input type="text" id="class_id" name="class_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                </div>
                <div>
                    <label for="class_name" class="block text-sm font-medium text-gray-700 mb-1">班级名称</label>
                    <input type="text" id="class_name" name="class_name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">班级密码</label>
                    <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
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

<!-- 编辑班级模态框 -->
<div id="edit-class-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">编辑班级</h3>
            <button id="close-edit-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="edit-class-form" method="POST" action="class_manage.php?action=edit">
            <input type="hidden" id="edit-class-id" name="class_id">
            <div class="space-y-4">
                <div>
                    <label for="edit-class-name" class="block text-sm font-medium text-gray-700 mb-1">班级名称</label>
                    <input type="text" id="edit-class-name" name="class_name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" required>
                </div>
                <div>
                    <label for="edit-password" class="block text-sm font-medium text-gray-700 mb-1">班级密码（留空不修改）</label>
                    <input type="password" id="edit-password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 添加班级模态框控制
        const addClassBtn = document.getElementById('add-class-btn');
        const addClassModal = document.getElementById('add-class-modal');
        const closeAddModal = document.getElementById('close-add-modal');
        const cancelAdd = document.getElementById('cancel-add');
        
        addClassBtn.addEventListener('click', function() {
            addClassModal.classList.remove('hidden');
        });
        
        function closeAddModalFunc() {
            addClassModal.classList.add('hidden');
            document.getElementById('add-class-form').reset();
        }
        
        closeAddModal.addEventListener('click', closeAddModalFunc);
        cancelAdd.addEventListener('click', closeAddModalFunc);
        
        // 编辑班级模态框控制
        const editClassBtns = document.querySelectorAll('.edit-class-btn');
        const editClassModal = document.getElementById('edit-class-modal');
        const closeEditModal = document.getElementById('close-edit-modal');
        const cancelEdit = document.getElementById('cancel-edit');
        
        editClassBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const classId = this.getAttribute('data-class-id');
                
                // 获取班级信息
                fetch('class_manage.php?action=get_class_info&class_id=' + classId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('edit-class-id').value = classId;
                            document.getElementById('edit-class-name').value = data.class.class_name;
                            document.getElementById('edit-password').value = ''; // 密码留空
                            editClassModal.classList.remove('hidden');
                        } else {
                            alert(data.message || '获取班级信息失败');
                        }
                    })
                    .catch(error => {
                        alert('获取班级信息失败');
                        console.error(error);
                    });
            });
        });
        
        function closeEditModalFunc() {
            editClassModal.classList.add('hidden');
        }
        
        closeEditModal.addEventListener('click', closeEditModalFunc);
        cancelEdit.addEventListener('click', closeEditModalFunc);
        
        // 删除班级确认
        const deleteClassBtns = document.querySelectorAll('.delete-class-btn');
        
        deleteClassBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const classId = this.getAttribute('data-class-id');
                
                if (confirm('确定要删除这个班级吗？这将删除班级的所有学生数据和签到记录，此操作不可恢复！')) {
                    window.location.href = 'class_manage.php?action=delete&class_id=' + classId;
                }
            });
        });
        
        // 点击模态框外部关闭
        window.addEventListener('click', function(event) {
            if (event.target === addClassModal) {
                closeAddModalFunc();
            }
            if (event.target === editClassModal) {
                closeEditModalFunc();
            }
        });
    });
</script>

<?php
// 引入底部
require_once dirname(__DIR__) . '/common/footer.php';
?>