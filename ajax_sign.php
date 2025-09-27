<?php
// 统一签到处理文件

// 确保这是AJAX请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '不支持的请求方法']);
    exit;
}

// 加载配置和函数
require_once dirname(__FILE__) . '/common/config.php';
require_once dirname(__FILE__) . '/common/functions.php';

// 设置响应头为JSON格式
header('Content-Type: application/json');

// 获取请求参数
$student_id = isset($_POST['student_id']) ? $_POST['student_id'] : null;
$token = isset($_POST['token']) ? $_POST['token'] : null;
$class_id = isset($_POST['class_id']) ? $_POST['class_id'] : null;

// 获取客户端IP地址
function get_client_ip() {
    $ip = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // 处理多个IP地址的情况
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }
    return $ip;
}

$client_ip = get_client_ip();
$current_date = date('Y-m-d');
$current_time = time();

// 验证参数
if (empty($token)) {
    echo json_encode(['success' => false, 'message' => '签到令牌不能为空']);
    exit;
}

// 处理专属链接模式签到
if (empty($class_id) && !empty($token)) {
    // 查找匹配的学生和班级
    $found_student = null;
    $found_class_id = null;
    $found_class_name = null;
    
    foreach (PASSWORDS as $class_id_key => $class_data) {
        $student_file = ROOT_DIR . '/data/' . $class_id_key . '_students.json';
        if (file_exists($student_file)) {
            $students = read_json_file($student_file);
            foreach ($students as $student) {
                if ($student['token'] === $token && $student['active']) {
                    $found_student = $student;
                    $found_class_id = $class_id_key;
                    $found_class_name = $class_data['class_name'];
                    break 2;
                }
            }
        }
    }
    
    // 验证学生信息
    if (!$found_student) {
        echo json_encode(['success' => false, 'message' => '无效的签到令牌']);
        exit;
    }
    
    // 检查是否已经签到
    $records_file = ROOT_DIR . '/data/sign_records_' . $found_class_id . '.json';
    $records = file_exists($records_file) ? read_json_file($records_file) : array();
    
    if (!isset($records[$current_date])) {
        $records[$current_date] = array();
    }
    
    $student_id = $found_student['id'];
    if (isset($records[$current_date][$student_id]) && $records[$current_date][$student_id]['signed']) {
        echo json_encode(['success' => false, 'message' => '您已经完成签到']);
        exit;
    }
    
    // 执行签到
    $records[$current_date][$student_id] = array(
        'signed' => true,
        'timestamp' => $current_time,
        'ip' => $client_ip
    );
    
    // 保存签到记录
    if (write_json_file($records_file, $records)) {
        echo json_encode([
            'success' => true,
            'message' => '签到成功',
            'token' => $token, // 确保返回token
            'data' => array(
                'student_id' => $student_id,
                'student_name' => $found_student['name'],
                'class_id' => $found_class_id,
                'class_name' => $found_class_name,
                'sign_time' => date('Y-m-d H:i:s', $current_time)
            )
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '签到失败，请重试', 'token' => $token]);
    }
}
// 处理通用链接模式签到
elseif (!empty($class_id) && !empty($student_id) && !empty($token)) {
    // 验证班级和通用token
    if (!isset(CLASS_TOKENS[$class_id]) || CLASS_TOKENS[$class_id]['general_token'] !== $token || !CLASS_TOKENS[$class_id]['active']) {
        echo json_encode(['success' => false, 'message' => '无效的班级令牌']);
        exit;
    }
    
    // 验证学生是否存在于该班级
    $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
    if (!file_exists($student_file)) {
        echo json_encode(['success' => false, 'message' => '班级数据不存在']);
        exit;
    }
    
    $students = read_json_file($student_file);
    $found_student = null;
    
    foreach ($students as $student) {
        if ($student['id'] == $student_id && $student['active']) {
            $found_student = $student;
            break;
        }
    }
    
    if (!$found_student) {
        echo json_encode(['success' => false, 'message' => '学生不存在']);
        exit;
    }
    
    // 检查是否已经签到
    $records_file = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
    $records = file_exists($records_file) ? read_json_file($records_file) : array();
    
    if (!isset($records[$current_date])) {
        $records[$current_date] = array();
    }
    
    if (isset($records[$current_date][$student_id]) && $records[$current_date][$student_id]['signed']) {
        echo json_encode(['success' => false, 'message' => '该学生已经完成签到']);
        exit;
    }
    
    // 执行签到
    $records[$current_date][$student_id] = array(
        'signed' => true,
        'timestamp' => $current_time,
        'ip' => $client_ip
    );
    
    // 保存签到记录
    if (write_json_file($records_file, $records)) {
        echo json_encode([
            'success' => true,
            'message' => '签到成功',
            'token' => $token, // 确保返回token
            'data' => array(
                'student_id' => $student_id,
                'student_name' => $found_student['name'],
                'class_id' => $class_id,
                'class_name' => CLASS_TOKENS[$class_id]['class_name'],
                'sign_time' => date('Y-m-d H:i:s', $current_time)
            )
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '签到失败，请重试', 'token' => $token]);
    }
}
else {
    // 参数不完整
    echo json_encode(['success' => false, 'message' => '参数不完整', 'token' => $token]);
    exit;
}
?>