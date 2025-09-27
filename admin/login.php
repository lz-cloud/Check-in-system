<?php
// 管理员登录页面

// 加载配置和函数
require_once dirname(__DIR__) . '/common/config.php';
require_once dirname(__DIR__) . '/common/functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

// 如果已登录，重定向到首页
if (is_logged_in()) {
    header('Location: index.php');
    exit();
}

// 处理退出登录
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    // 重定向到登录页面
    header('Location: login.php');
    exit();
}

// 处理登录请求
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = trim($_POST['class_id']);
    $password = $_POST['password'];
    
    // 验证登录凭证
    if (validate_login($class_id, $password)) {
        login($class_id);
        
        // 重定向到之前请求的页面或首页
        $redirect = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : 'index.php';
        header('Location: ' . $redirect);
        exit();
    } else {
        $error = '班级账号或密码错误';
    }
}

// 页面标题
$page_title = '管理员登录';

// 自定义CSS
$custom_css = <<<CSS
    body {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .login-container {
        width: 100%;
        max-width: 400px;
        padding: 20px;
    }
    
    .login-card {
        background: white;
        border-radius: 16px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }
    
    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .login-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #3498db 0%, #2ecc71 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 40px;
    }
    
    .form-group {
        position: relative;
        margin-bottom: 20px;
    }
    
    .form-group label {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        padding: 0 5px;
        transition: all 0.3s ease;
        pointer-events: none;
        color: #666;
        font-size: 14px;
    }
    
    .form-group input:focus + label,
    .form-group input:not(:placeholder-shown) + label {
        top: 0;
        font-size: 12px;
        color: #3498db;
        font-weight: 500;
    }
    
    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 16px;
    }
    
    .form-group input:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        outline: none;
    }
    
    .login-btn {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #3498db 0%, #2ecc71 100%);
        border: none;
        border-radius: 8px;
        color: white;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .login-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
    }
    
    .login-btn:active {
        transform: translateY(0);
    }
    
    .login-btn::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }
    
    .login-btn:hover::after {
        width: 300px;
        height: 300px;
    }
    
    .error-message {
        background: #fee;
        color: #e74c3c;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #fcc;
        display: flex;
        align-items: center;
    }
    
    .error-message i {
        margin-right: 8px;
    }
CSS;

// 引入头部
require_once dirname(__DIR__) . '/common/header.php';
?>
<div class="login-container">
    <div class="login-card fade-in">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2 class="h4 font-bold">家长会签到系统</h2>
            <p class="text-sm text-gray-500">请输入班级账号和密码</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
            <div class="form-group">
                <input type="text" id="class_id" name="class_id" placeholder=" " required>
                <label for="class_id">账号</label>
            </div>
            
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder=" " required>
                <label for="password">密码</label>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt mr-2"></i>登录
            </button>
        </form>
        
        <div class="text-center mt-4 text-sm text-gray-500">
            <p>示例班级账号：class_1a 密码：123456</p>
            <p class="mt-1">示例班级账号：class_2a 密码：abcdef</p>
            <p class="mt-2 text-yellow-600"><i class="fas fa-lock mr-1"></i>超级管理员账号：superadmin 密码：superadmin123</p>
        </div>
    </div>
</div>

<?php
// 引入底部
require_once dirname(__DIR__) . '/common/footer.php';
?>