<?php
// 公共函数库，提供系统所需的各类工具函数

// 加载配置文件
require_once dirname(__FILE__) . '/config.php';

/**
 * 获取当前系统的基础URL
 * @return string 基础URL地址
 */
function get_current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    
    // 去除admin目录（如果存在）
    $path = str_replace('/admin', '', $path);
    $path = str_replace('/common', '', $path);
    
    return $protocol . $domain . $path;
}

/**
 * 读取系统链接配置文件
 * @return array 配置数组
 */
function get_system_links_config() {
    $config_path = dirname(__FILE__) . '/../config/system_links.json';
    $config = read_json_file($config_path);
    
    // 如果配置文件中的base_url为空，则使用动态获取的URL
    if (empty($config['base_url'])) {
        $config['base_url'] = get_current_url();
    }
    
    return $config;
}

/**
 * 读取JSON数据文件
 * @param string $file_path 文件路径
 * @return array 数据数组，如果文件不存在则返回空数组
 */
function read_json_file($file_path) {
    try {
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $data = json_decode($content, true);
            // 确保返回有效的数组
            return is_array($data) ? $data : [];
        }
        return [];
    } catch (Exception $e) {
        // 记录错误但不中断程序
        error_log('读取JSON文件失败: ' . $e->getMessage());
        return [];
    }
}

/**
 * 写入JSON数据文件
 * @param string $file_path 文件路径
 * @param array $data 要写入的数据
 * @return bool 是否成功
 */
function write_json_file($file_path, $data) {
    try {
        // 确保目录存在
        $dir = dirname($file_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return file_put_contents($file_path, $content) !== false;
    } catch (Exception $e) {
        error_log('写入JSON文件失败: ' . $e->getMessage());
        return false;
    }
}

/**
 * 生成唯一Token
 * @param int $length Token长度
 * @return string 生成的Token
 */
function generate_token($length = 16) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $token;
}

/**
 * 验证用户登录凭证
 * @param string $class_id 班级ID
 * @param string $password 密码
 * @return bool 是否验证成功
 */
function validate_login($class_id, $password) {
    if (isset(PASSWORDS[$class_id]) && PASSWORDS[$class_id]['password'] === $password) {
        return true;
    }
    return false;
}

/**
 * 获取班级名称
 * @param string $class_id 班级ID
 * @return string 班级名称
 */
function get_class_name($class_id) {
    if (isset(PASSWORDS[$class_id])) {
        return PASSWORDS[$class_id]['class_name'];
    }
    return '未知班级';
}

/**
 * 获取学生信息
 * @param string $class_id 班级ID
 * @param int $student_id 学生ID
 * @return array|null 学生信息
 */
function get_student_info($class_id, $student_id) {
    $file_path = ROOT_DIR . '/data/' . $class_id . '_students.json';
    $students = read_json_file($file_path);
    
    foreach ($students as $student) {
        if ($student['id'] == $student_id) {
            return $student;
        }
    }
    
    return null;
}

/**
 * 根据Token获取学生信息
 * @param string $token 学生Token
 * @return array|null 包含班级ID和学生信息的数组
 */
function get_student_by_token($token) {
    foreach (PASSWORDS as $class_id => $class_info) {
        $file_path = ROOT_DIR . '/data/' . $class_id . '_students.json';
        $students = read_json_file($file_path);
        
        foreach ($students as $student) {
            if ($student['token'] === $token && $student['active']) {
                return ['class_id' => $class_id, 'student' => $student];
            }
        }
    }
    
    return null;
}

/**
 * 验证通用链接Token
 * @param string $class_id 班级ID
 * @param string $token 通用Token
 * @return bool 是否验证成功
 */
function validate_general_token($class_id, $token) {
    if (isset(CLASS_TOKENS[$class_id]) && 
        CLASS_TOKENS[$class_id]['general_token'] === $token && 
        CLASS_TOKENS[$class_id]['active']) {
        return true;
    }
    return false;
}

/**
 * 处理签到请求
 * @param string $class_id 班级ID
 * @param int $student_id 学生ID
 * @return array 签到结果
 */
function process_sign_in($class_id, $student_id) {
    $today = date('Y-m-d');
    $file_path = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
    $records = read_json_file($file_path);
    
    // 确保当天的记录存在
    if (!isset($records[$today])) {
        $records[$today] = [];
    }
    
    // 如果已经签到，返回成功但不更新
    if (isset($records[$today][$student_id]) && $records[$today][$student_id]['signed']) {
        return [
            'success' => true,
            'message' => '您已经签到过了',
            'already_signed' => true
        ];
    }
    
    // 更新签到状态
    $records[$today][$student_id] = [
        'signed' => true,
        'timestamp' => time(),
        'ip' => $_SERVER['REMOTE_ADDR']
    ];
    
    // 保存更新后的记录
    if (write_json_file($file_path, $records)) {
        return [
            'success' => true,
            'message' => '签到成功！'
        ];
    } else {
        return [
            'success' => false,
            'message' => '签到失败，请重试'
        ];
    }
}

/**
 * 获取签到统计信息
 * @param string $class_id 班级ID
 * @param string $date 日期，默认为今天
 * @return array 统计信息
 */
function get_sign_statistics($class_id, $date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
    $record_file = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
    
    $students = read_json_file($student_file);
    $records = read_json_file($record_file);
    
    $total_count = count($students);
    $signed_count = 0;
    
    // 统计签到人数
    if (isset($records[$date])) {
        foreach ($records[$date] as $student_id => $record) {
            if ($record['signed']) {
                $signed_count++;
            }
        }
    }
    
    return [
        'total' => $total_count,
        'signed' => $signed_count,
        'rate' => $total_count > 0 ? round(($signed_count / $total_count) * 100, 2) : 0
    ];
}

/**
 * 重置当天签到状态
 * @param string $class_id 班级ID
 * @return bool 是否成功
 */
function reset_today_sign($class_id) {
    $today = date('Y-m-d');
    $file_path = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
    $records = read_json_file($file_path);
    
    // 保存历史数据，但重置今天的签到状态
    $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
    $students = read_json_file($student_file);
    
    $records[$today] = [];
    foreach ($students as $student) {
        $records[$today][$student['id']] = ['signed' => false];
    }
    
    return write_json_file($file_path, $records);
}

/**
 * 生成CSV导出数据
 * @param string $class_id 班级ID
 * @param string $date 日期
 * @return string CSV内容
 */
function generate_csv_export($class_id, $date) {
    $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
    $record_file = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
    
    $students = read_json_file($student_file);
    $records = read_json_file($record_file);
    
    // CSV头部
    $csv = "学号,姓名,签到状态,签到时间,IP地址\n";
    
    // 处理每条记录
    foreach ($students as $student) {
        $student_id = $student['id'];
        $signed = '未签到';
        $timestamp = '';
        $ip = '';
        
        if (isset($records[$date]) && isset($records[$date][$student_id]) && $records[$date][$student_id]['signed']) {
            $signed = '已签到';
            $timestamp = date('Y-m-d H:i:s', $records[$date][$student_id]['timestamp']);
            $ip = $records[$date][$student_id]['ip'];
        }
        
        // 转义CSV特殊字符
        $name = str_replace('"', '""', $student['name']);
        
        $csv .= "$student_id,\"$name\",$signed,\"$timestamp\",$ip\n";
    }
    
    return $csv;
}

/**
 * 超级管理员函数：获取所有班级信息
 * @return array 所有班级信息数组
 */
function get_all_classes() {
    return PASSWORDS;
}

/**
 * 超级管理员函数：添加新班级
 * @param string $class_id 班级ID
 * @param string $class_name 班级名称
 * @param string $password 班级密码
 * @return bool 是否添加成功
 */
function add_class($class_id, $class_name, $password) {
    // 检查班级ID是否已存在
    if (isset(PASSWORDS[$class_id])) {
        return false;
    }
    
    // 读取当前密码文件
    $passwords_file = ROOT_DIR . '/config/passwords.json';
    $passwords = read_json_file($passwords_file);
    
    // 添加新班级
    $passwords[$class_id] = [
        'class_name' => $class_name,
        'password' => $password,
        'role' => 'class'
    ];
    
    // 为新班级创建学生文件和签到记录文件
    $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
    $record_file = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
    
    write_json_file($student_file, []);
    write_json_file($record_file, []);
    
    // 为新班级创建Token配置
    $tokens_file = ROOT_DIR . '/config/class_tokens.json';
    $tokens = read_json_file($tokens_file);
    $tokens[$class_id] = [
        'general_token' => generate_token(),
        'active' => true
    ];
    write_json_file($tokens_file, $tokens);
    
    // 更新全局配置
    return write_json_file($passwords_file, $passwords);
}

/**
 * 超级管理员函数：修改班级信息
 * @param string $class_id 班级ID
 * @param array $new_info 新的班级信息
 * @return bool 是否修改成功
 */
function update_class($class_id, $new_info) {
    // 检查班级ID是否存在
    if (!isset(PASSWORDS[$class_id])) {
        return false;
    }
    
    // 读取当前密码文件
    $passwords_file = ROOT_DIR . '/config/passwords.json';
    $passwords = read_json_file($passwords_file);
    
    // 更新班级信息
    foreach ($new_info as $key => $value) {
        $passwords[$class_id][$key] = $value;
    }
    
    // 更新全局配置
    return write_json_file($passwords_file, $passwords);
}

/**
 * 超级管理员函数：删除班级
 * @param string $class_id 班级ID
 * @return bool 是否删除成功
 */
function delete_class($class_id) {
    // 不允许删除超级管理员账号
    if ($class_id === 'superadmin') {
        return false;
    }
    
    // 检查班级ID是否存在
    if (!isset(PASSWORDS[$class_id])) {
        return false;
    }
    
    // 读取当前密码文件
    $passwords_file = ROOT_DIR . '/config/passwords.json';
    $passwords = read_json_file($passwords_file);
    
    // 删除班级
    unset($passwords[$class_id]);
    
    // 从Token配置中删除
    $tokens_file = ROOT_DIR . '/config/class_tokens.json';
    $tokens = read_json_file($tokens_file);
    if (isset($tokens[$class_id])) {
        unset($tokens[$class_id]);
        write_json_file($tokens_file, $tokens);
    }
    
    // 删除学生文件和签到记录文件
    $student_file = ROOT_DIR . '/data/' . $class_id . '_students.json';
    $record_file = ROOT_DIR . '/data/sign_records_' . $class_id . '.json';
    
    if (file_exists($student_file)) {
        unlink($student_file);
    }
    
    if (file_exists($record_file)) {
        unlink($record_file);
    }
    
    // 更新全局配置
    return write_json_file($passwords_file, $passwords);
}

/**
 * 超级管理员函数：获取班级的所有学生
 * @param string $class_id 班级ID
 * @return array 学生数组
 */
function get_class_students($class_id) {
    $file_path = ROOT_DIR . '/data/' . $class_id . '_students.json';
    return read_json_file($file_path);
}

/**
 * 超级管理员函数：添加学生
 * @param string $class_id 班级ID
 * @param array $student_info 学生信息
 * @return bool 是否添加成功
 */
function add_student($class_id, $student_info) {
    $file_path = ROOT_DIR . '/data/' . $class_id . '_students.json';
    $students = read_json_file($file_path);
    
    // 为新学生生成ID
    $max_id = 0;
    foreach ($students as $student) {
        if ($student['id'] > $max_id) {
            $max_id = $student['id'];
        }
    }
    
    $student_info['id'] = $max_id + 1;
    $student_info['token'] = generate_token();
    $student_info['active'] = true;
    
    // 添加学生
    $students[] = $student_info;
    
    return write_json_file($file_path, $students);
}

/**
 * 超级管理员函数：修改学生信息
 * @param string $class_id 班级ID
 * @param int $student_id 学生ID
 * @param array $new_info 新的学生信息
 * @return bool 是否修改成功
 */
function update_student($class_id, $student_id, $new_info) {
    $file_path = ROOT_DIR . '/data/' . $class_id . '_students.json';
    $students = read_json_file($file_path);
    
    // 查找并更新学生信息
    for ($i = 0; $i < count($students); $i++) {
        if ($students[$i]['id'] == $student_id) {
            foreach ($new_info as $key => $value) {
                $students[$i][$key] = $value;
            }
            break;
        }
    }
    
    return write_json_file($file_path, $students);
}

/**
 * 超级管理员函数：删除学生
 * @param string $class_id 班级ID
 * @param int $student_id 学生ID
 * @return bool 是否删除成功
 */
function delete_student($class_id, $student_id) {
    $file_path = ROOT_DIR . '/data/' . $class_id . '_students.json';
    $students = read_json_file($file_path);
    
    // 过滤掉要删除的学生
    $new_students = array_filter($students, function($student) use ($student_id) {
        return $student['id'] != $student_id;
    });
    
    return write_json_file($file_path, array_values($new_students));
}

/**
 * 显示Toast提示
 * @param string $message 消息内容
 * @param string $type 消息类型(success/error/warning/info)
 */
function show_toast($message, $type = 'info') {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.createElement('div');
            toast.className = 'toast show fixed top-4 right-4 z-50 bg-$type text-white px-4 py-3 rounded-lg shadow-lg transition-all duration-300 opacity-0 transform translate-x-full';
            toast.textContent = '$message';
            document.body.appendChild(toast);
            
            // 显示动画
            setTimeout(() => {
                toast.classList.remove('opacity-0', 'translate-x-full');
            }, 10);
            
            // 自动关闭
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        });
    </script>";
}