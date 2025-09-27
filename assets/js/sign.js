// 签到页面交互逻辑

/**
 * 签到页面系统类
 */
class SignPageSystem {
    constructor() {
        // 继承主系统功能
        this.mainSystem = window.signSystem || new SignSystem();
        this.init();
    }

    /**
     * 初始化签到页面
     */
    init() {
        // 检查是否在签到页面
        if (!document.getElementById('sign-page')) return;
        
        // 存储选中的学生ID
        this.selectedStudentId = null;
        
        // 初始化专属链接模式
        this.initExclusiveMode();
        
        // 初始化通用链接模式
        this.initGeneralMode();
        
        // 初始化签到按钮事件
        this.initSignButton();
        
        // 初始化学生选择功能
        this.initStudentSelection();
    }

    /**
     * 初始化专属链接模式
     */
    initExclusiveMode() {
        const exclusiveMode = document.getElementById('exclusive-mode');
        if (!exclusiveMode) return;
        
        // 可以添加专属模式特定的初始化逻辑
        // 例如检查学生是否已签到
        this.checkIfSigned();
    }

    /**
     * 初始化通用链接模式
     */
    initGeneralMode() {
        const generalMode = document.getElementById('general-mode');
        if (!generalMode) return;
        
        // 加载未签到学生列表
        this.loadUnsignedStudents();
    }

    /**
     * 初始化签到按钮
     */
    initSignButton() {
        const signButton = document.getElementById('sign-btn');
        if (!signButton) return;
        
        signButton.addEventListener('click', async () => {
            await this.handleSignIn();
        });
    }

    /**
     * 初始化学生选择功能（通用模式）
     */
    initStudentSelection() {
        const generalMode = document.getElementById('general-mode');
        if (!generalMode) return;
        
        const studentsList = document.getElementById('students-list');
        const selectionHint = document.getElementById('selection-hint');
        const selectedStudentInfo = document.getElementById('selected-student-info');
        const selectedStudentName = document.getElementById('selected-student-name');
        const clearSelection = document.getElementById('clear-selection');
        const signButton = document.getElementById('sign-btn');
        
        // 学生项点击事件
        if (studentsList) {
            studentsList.addEventListener('click', (e) => {
                const studentItem = e.target.closest('.student-item');
                if (!studentItem) return;
                
                // 检查是否已签到
                if (studentItem.querySelector('.status-success')) {
                    this.mainSystem.showToast('该学生已经签到', 'info');
                    return;
                }
                
                // 移除其他学生的选中状态
                document.querySelectorAll('.student-item').forEach(item => {
                    item.classList.remove('selected', 'border-primary', 'bg-primary/5');
                });
                
                // 添加选中状态
                studentItem.classList.add('selected', 'border-primary', 'bg-primary/5');
                
                // 获取学生信息
                const studentId = studentItem.dataset.studentId;
                const studentName = studentItem.dataset.studentName;
                
                // 存储选中的学生
                this.selectedStudentId = studentId;
                
                // 更新UI
                if (selectionHint) selectionHint.classList.add('hidden');
                if (selectedStudentInfo) selectedStudentInfo.classList.remove('hidden');
                if (selectedStudentName) selectedStudentName.textContent = studentName;
                if (signButton) signButton.disabled = false;
            });
        }
        
        // 清除选择按钮事件
        if (clearSelection) {
            clearSelection.addEventListener('click', () => {
                // 移除所有学生的选中状态
                document.querySelectorAll('.student-item').forEach(item => {
                    item.classList.remove('selected', 'border-primary', 'bg-primary/5');
                });
                
                // 重置选中的学生
                this.selectedStudentId = null;
                
                // 更新UI
                if (selectionHint) selectionHint.classList.remove('hidden');
                if (selectedStudentInfo) selectedStudentInfo.classList.add('hidden');
                if (selectedStudentName) selectedStudentName.textContent = '';
                if (signButton) signButton.disabled = true;
            });
        }
        
        // 初始化搜索功能
        this.initSearch();
    }

    /**
     * 初始化搜索功能
     */
    initSearch() {
        const searchInput = document.getElementById('student-search');
        if (!searchInput) return;
        
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            const studentItems = document.querySelectorAll('.student-item');
            
            let hasResults = false;
            
            studentItems.forEach(item => {
                const studentName = item.dataset.studentName.toLowerCase();
                const isVisible = studentName.includes(searchTerm);
                
                item.style.display = isVisible ? '' : 'none';
                
                if (isVisible) {
                    hasResults = true;
                }
            });
            
            // 显示/隐藏无结果提示
            const noResults = document.getElementById('no-search-results');
            if (!noResults && !hasResults) {
                const emptyState = document.createElement('div');
                emptyState.id = 'no-search-results';
                emptyState.className = 'text-center py-6';
                emptyState.innerHTML = `
                    <i class="fas fa-search text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">未找到匹配的学生</p>
                `;
                
                const studentsList = document.getElementById('students-list');
                if (studentsList) {
                    studentsList.appendChild(emptyState);
                }
            } else if (noResults && hasResults) {
                noResults.remove();
            }
        });
    }

    /**
     * 初始化学生选择下拉框
     */
    initStudentSelect() {
        const studentSelect = document.getElementById('student-select');
        if (!studentSelect) return;
        
        // 添加搜索过滤功能
        studentSelect.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const options = studentSelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') return; // 跳过提示选项
                
                const optionText = option.textContent.toLowerCase();
                option.style.display = optionText.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    /**
     * 检查学生是否已签到
     */
    async checkIfSigned() {
        try {
            // 获取URL中的token参数
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            
            if (!token) return;
            
            // 发送请求检查签到状态
            const response = await this.mainSystem.ajax('ajax_sign.php?action=check_status', {
                method: 'POST',
                body: new URLSearchParams({ token })
            });
            
            if (response.success && response.signed) {
                // 已签到，更新UI
                this.updateSignedUI(response);
            }
        } catch (error) {
            console.error('检查签到状态失败:', error);
            // 不显示错误，避免影响用户体验
        }
    }

    /**
     * 加载未签到学生列表
     */
    async loadUnsignedStudents() {
        try {
            // 获取URL中的class和token参数
            const urlParams = new URLSearchParams(window.location.search);
            const classId = urlParams.get('class');
            const token = urlParams.get('token');
            
            if (!classId || !token) return;
            
            // 显示加载状态
            const studentSelect = document.getElementById('student-select');
            if (studentSelect) {
                studentSelect.innerHTML = '<option value="">加载中...</option>';
            }
            
            // 发送请求加载未签到学生
            const response = await this.mainSystem.ajax('ajax_sign.php?action=get_unsigned_students', {
                method: 'POST',
                body: new URLSearchParams({
                    class_id: classId,
                    token: token
                })
            });
            
            if (response.success && response.students) {
                // 更新学生选择下拉框
                if (studentSelect) {
                    if (response.students.length === 0) {
                        studentSelect.innerHTML = '<option value="">所有学生已签到</option>';
                        studentSelect.disabled = true;
                        // 禁用签到按钮
                        const signButton = document.getElementById('sign-btn');
                        if (signButton) {
                            signButton.disabled = true;
                            signButton.textContent = '所有学生已签到';
                        }
                    } else {
                        studentSelect.innerHTML = '<option value="">请选择学生...</option>';
                        response.students.forEach(student => {
                            const option = document.createElement('option');
                            option.value = student.id;
                            option.textContent = student.name;
                            studentSelect.appendChild(option);
                        });
                    }
                }
            } else {
                // 显示错误信息
                if (studentSelect) {
                    studentSelect.innerHTML = '<option value="">加载学生列表失败</option>';
                }
                this.mainSystem.showToast(response.message || '加载学生列表失败', 'error');
            }
        } catch (error) {
            console.error('加载学生列表失败:', error);
            const studentSelect = document.getElementById('student-select');
            if (studentSelect) {
                studentSelect.innerHTML = '<option value="">网络错误，请重试</option>';
            }
        }
    }

    /**
     * 处理签到请求
     */
    async handleSignIn() {
        try {
            const signButton = document.getElementById('sign-btn');
            if (!signButton) return;
            
            // 获取URL参数
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            
            // 验证令牌存在
            if (!token) {
                this.mainSystem.showToast('签到令牌不能为空', 'error');
                return;
            }
            
            let params = { token: token }; // 始终包含token
            let studentName = '';
            
            // 判断签到模式
            if (document.getElementById('exclusive-mode')) {
                // 专属链接模式
                // token已包含在params中
                const studentNameElement = document.querySelector('.sign-form h2');
                if (studentNameElement) {
                    studentName = studentNameElement.textContent;
                }
            } else if (document.getElementById('general-mode')) {
                // 通用链接模式
                const classId = urlParams.get('class');
                const studentId = this.selectedStudentId;
                
                if (!studentId) {
                    this.mainSystem.showToast('请选择学生', 'warning');
                    return;
                }
                
                if (!classId) {
                    this.mainSystem.showToast('班级信息缺失', 'error');
                    return;
                }
                
                params = {
                    class_id: classId,
                    token: token, // 明确包含token
                    student_id: studentId
                };
                
                // 获取选中的学生名称
                const selectedStudentName = document.getElementById('selected-student-name');
                if (selectedStudentName) {
                    studentName = selectedStudentName.textContent;
                }
            }
            
            // 确保token参数始终存在
            if (!params.token || params.token.trim() === '') {
                this.mainSystem.showToast('签到令牌不能为空', 'error');
                return;
            }
            
            // 显示确认对话框
            if (studentName) {
                const confirmModal = document.getElementById('sign-confirm-modal');
                const confirmStudentName = document.getElementById('confirm-student-name');
                const confirmSignBtn = document.getElementById('confirm-sign-btn');
                
                if (confirmModal && confirmStudentName && confirmSignBtn) {
                    // 设置学生名称
                    confirmStudentName.textContent = studentName;
                    
                    // 显示对话框
                    $(confirmModal).modal('show'); // 使用Bootstrap的modal方法
                    
                    // 创建确认签到的Promise
                    return new Promise((resolve) => {
                        // 移除之前的事件监听器
                        const newConfirmSignBtn = confirmSignBtn.cloneNode(true);
                        confirmSignBtn.parentNode.replaceChild(newConfirmSignBtn, confirmSignBtn);
                        
                        // 添加新的事件监听器
                        newConfirmSignBtn.addEventListener('click', async () => {
                            // 隐藏对话框
                            $(confirmModal).modal('hide');
                            
                            // 显示加载状态
                            this.mainSystem.showLoading(signButton);
                            
                            try {
                                // 发送签到请求
                                const response = await this.mainSystem.ajax('ajax_sign.php', {
                                    method: 'POST',
                                    body: new URLSearchParams(params)
                                });
                                
                                // 隐藏加载状态
                                this.mainSystem.hideLoading(signButton);
                                
                                if (response.success) {
                                    // 签到成功
                                    if (response.already_signed) {
                                        this.mainSystem.showToast('您已经签到过了', 'info');
                                        this.updateSignedUI(response);
                                    } else {
                                        this.mainSystem.showToast('签到成功！', 'success');
                                        this.showFireworksAnimation();
                                        this.updateSignedUI(response);
                                    }
                                } else {
                                    // 签到失败
                                    this.mainSystem.showToast(response.message || '签到失败，请重试', 'error');
                                }
                            } catch (error) {
                                console.error('签到请求失败:', error);
                                this.mainSystem.hideLoading(signButton);
                                this.mainSystem.showToast('网络错误，请重试', 'error');
                            }
                            
                            resolve();
                        });
                    });
                }
            }
            
            // 如果没有确认对话框，直接进行签到
            // 显示加载状态
            this.mainSystem.showLoading(signButton);
            
            // 发送签到请求
            const response = await this.mainSystem.ajax('ajax_sign.php', {
                method: 'POST',
                body: new URLSearchParams(params)
            });
            
            // 隐藏加载状态
            this.mainSystem.hideLoading(signButton);
            
            if (response.success) {
                // 签到成功
                if (response.already_signed) {
                    this.mainSystem.showToast('您已经签到过了', 'info');
                    this.updateSignedUI(response);
                } else {
                    this.mainSystem.showToast('签到成功！', 'success');
                    this.showFireworksAnimation();
                    this.updateSignedUI(response);
                }
            } else {
                // 签到失败
                this.mainSystem.showToast(response.message || '签到失败，请重试', 'error');
            }
        } catch (error) {
            console.error('签到请求失败:', error);
            const signButton = document.getElementById('sign-btn');
            if (signButton) {
                this.mainSystem.hideLoading(signButton);
            }
            this.mainSystem.showToast('网络错误，请重试', 'error');
        }
    }

    /**
     * 更新已签到的UI显示
     * @param {object} response 签到响应数据
     */
    updateSignedUI(response) {
        // 专属链接模式的UI更新
        const exclusiveMode = document.getElementById('exclusive-mode');
        if (exclusiveMode) {
            const signButton = document.getElementById('sign-btn');
            if (signButton) {
                signButton.innerHTML = '<i class="fas fa-check-circle mr-2"></i>已签到';
                signButton.classList.remove('btn-primary', 'pulse-animation');
                signButton.classList.add('btn-secondary');
                signButton.disabled = true;
            }
            
            // 显示签到时间
            if (response.timestamp) {
                const signTimeElement = document.querySelector('.sign-success .text-sm');
                if (signTimeElement) {
                    const formattedTime = this.mainSystem.formatDateTime(response.timestamp);
                    signTimeElement.innerHTML = `<p>签到时间：${formattedTime}</p>`;
                    signTimeElement.style.display = 'block';
                }
            }
        }
        
        // 通用链接模式的UI更新
        const generalMode = document.getElementById('general-mode');
        if (generalMode) {
            const signButton = document.getElementById('sign-btn');
            const selectionHint = document.getElementById('selection-hint');
            const selectedStudentInfo = document.getElementById('selected-student-info');
            const selectedStudentName = document.getElementById('selected-student-name');
            
            if (response.student_id) {
                // 查找并更新已签到的学生项
                const studentItem = document.querySelector(`.student-item[data-student-id="${response.student_id}"]`);
                if (studentItem) {
                    const statusBadge = studentItem.querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.className = 'status-badge status-success';
                        statusBadge.textContent = '已签到';
                    }
                    
                    // 添加已签到的视觉效果
                    studentItem.classList.add('signed-item');
                    studentItem.style.cursor = 'default';
                    
                    // 移除点击事件
                    studentItem.onclick = function(e) { e.stopPropagation(); };
                }
                
                // 重置选择状态
                this.selectedStudentId = null;
                
                // 更新UI
                if (selectionHint) selectionHint.classList.remove('hidden');
                if (selectedStudentInfo) selectedStudentInfo.classList.add('hidden');
                if (selectedStudentName) selectedStudentName.textContent = '';
                if (signButton) signButton.disabled = true;
                
                // 检查是否所有学生都已签到
                const unsignedStudents = document.querySelectorAll('.student-item:not(.signed-item)');
                if (unsignedStudents.length === 0) {
                    // 显示所有学生已签到的提示
                    const studentsList = document.getElementById('students-list');
                    if (studentsList) {
                        studentsList.innerHTML = `
                            <div class="text-center py-6">
                                <div class="success-icon bg-success text-white rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-check-circle text-4xl"></i>
                                </div>
                                <h3 class="font-bold text-success mb-2">所有学生已签到！</h3>
                                <p class="text-gray-600">感谢您完成本次家长会签到</p>
                            </div>
                        `;
                    }
                    
                    if (signButton) {
                        signButton.innerHTML = '<i class="fas fa-check-circle mr-2"></i>所有学生已签到';
                        signButton.disabled = true;
                    }
                }
            }
        }
    }

    /**
     * 显示烟花动画
     */
    showFireworksAnimation() {
        // 创建烟花容器
        let container = document.getElementById('fireworks-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'fireworks-container';
            document.body.appendChild(container);
        }
        
        // 清空容器
        container.innerHTML = '';
        
        // 添加CSS类
        container.className = 'fireworks-container';
        
        // 创建更多的烟花效果
        for (let i = 0; i < 20; i++) {
            setTimeout(() => {
                this.createFirework(container);
            }, i * 150);
        }
        
        // 8秒后移除动画容器
        setTimeout(() => {
            if (container) {
                container.remove();
            }
        }, 8000);
    }

    /**
     * 创建单个烟花效果
     * @param {HTMLElement} container 容器元素
     */
    createFirework(container) {
        const firework = document.createElement('div');
        
        // 随机位置
        const x = Math.random() * 100;
        const y = Math.random() * 40 + 30; // 限制在屏幕中间和上半部分
        
        // 随机颜色 - 使用渐变颜色方案
        const colorSchemes = [
            ['#ff6b6b', '#ee5a52'],
            ['#4ecdc4', '#45b7aa'],
            ['#45b7d1', '#34a8c4'],
            ['#96ceb4', '#86c2a3'],
            ['#feca57', '#fdcc5c'],
            ['#ff9ff3', '#ff8ce0'],
            ['#54a0ff', '#4895ff'],
            ['#667eea', '#764ba2']
        ];
        
        const colorScheme = colorSchemes[Math.floor(Math.random() * colorSchemes.length)];
        const startColor = colorScheme[0];
        const endColor = colorScheme[1];
        
        // 设置烟花样式
        firework.style.cssText = `
            position: absolute;
            left: ${x}%;
            top: ${y}%;
            width: 8px;
            height: 8px;
            background: linear-gradient(135deg, ${startColor}, ${endColor});
            border-radius: 50%;
            transform: scale(0);
            animation: firework 1s ease-out forwards;
            box-shadow: 0 0 15px ${startColor};
            z-index: 1000;
        `;
        
        container.appendChild(firework);
        
        // 创建爆炸效果
        setTimeout(() => {
            this.createExplosion(container, x, y, startColor, endColor);
            firework.remove();
        }, 300);
    }

    /**
     * 创建爆炸效果
     * @param {HTMLElement} container 容器元素
     * @param {number} x X坐标百分比
     * @param {number} y Y坐标百分比
     * @param {string} startColor 起始颜色
     * @param {string} endColor 结束颜色
     */
    createExplosion(container, x, y, startColor, endColor) {
        // 随机爆炸粒子数量
        const particleCount = Math.floor(Math.random() * 30) + 20;
        
        // 创建多个爆炸粒子
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            
            // 随机方向和距离
            const angle = Math.random() * Math.PI * 2;
            const distance = Math.random() * 70 + 30;
            
            // 计算终点位置
            const endX = x + Math.cos(angle) * distance;
            const endY = y + Math.sin(angle) * distance;
            
            // 随机粒子大小
            const size = Math.random() * 4 + 2;
            
            // 随机透明度变化
            const opacityStart = Math.random() * 0.5 + 0.5;
            
            // 随机动画持续时间
            const duration = Math.random() * 0.7 + 0.8;
            
            // 设置粒子样式
            particle.style.cssText = `
                position: absolute;
                left: ${x}%;
                top: ${y}%;
                width: ${size}px;
                height: ${size}px;
                background: linear-gradient(135deg, ${startColor}, ${endColor});
                border-radius: 50%;
                box-shadow: 0 0 10px ${startColor};
                opacity: ${opacityStart};
                animation: explosion ${duration}s ease-out forwards;
                z-index: 1000;
            `;
            
            // 创建动画关键帧
            const animKeyframes = `
                @keyframes explosion {
                    0% {
                        transform: translate(0, 0) scale(1);
                        opacity: ${opacityStart};
                    }
                    50% {
                        opacity: ${opacityStart * 0.8};
                    }
                    100% {
                        transform: translate(${endX - x}px, ${endY - y}px) scale(0.3);
                        opacity: 0;
                    }
                }
            `;
            
            // 创建样式元素
            const style = document.createElement('style');
            style.textContent = animKeyframes;
            particle.appendChild(style);
            
            container.appendChild(particle);
            
            // 动画结束后移除粒子
            setTimeout(() => {
                if (particle && particle.parentNode === container) {
                    particle.remove();
                }
            }, duration * 1000);
        }
        
        // 添加中心火花效果
        this.createCoreSpark(container, x, y, startColor);
    }

    /**
     * 创建中心火花效果
     * @param {HTMLElement} container 容器元素
     * @param {number} x X坐标百分比
     * @param {number} y Y坐标百分比
     * @param {string} color 颜色
     */
    createCoreSpark(container, x, y, color) {
        const core = document.createElement('div');
        
        core.style.cssText = `
            position: absolute;
            left: ${x}%;
            top: ${y}%;
            width: 20px;
            height: 20px;
            background: radial-gradient(circle, ${color}, transparent 70%);
            transform: translate(-50%, -50%);
            animation: coreSpark 0.5s ease-out forwards;
            z-index: 1001;
        `;
        
        // 创建动画关键帧
        const animKeyframes = `
            @keyframes coreSpark {
                0% {
                    transform: translate(-50%, -50%) scale(0);
                    opacity: 1;
                }
                50% {
                    transform: translate(-50%, -50%) scale(1.5);
                    opacity: 0.8;
                }
                100% {
                    transform: translate(-50%, -50%) scale(2);
                    opacity: 0;
                }
            }
        `;
        
        // 创建样式元素
        const style = document.createElement('style');
        style.textContent = animKeyframes;
        core.appendChild(style);
        
        container.appendChild(core);
        
        // 动画结束后移除核心效果
        setTimeout(() => {
            if (core && core.parentNode === container) {
                core.remove();
            }
        }, 500);
    }
}

// 页面加载完成后初始化签到系统
document.addEventListener('DOMContentLoaded', () => {
    // 检查是否在签到页面
    if (window.location.pathname.includes('sign.php')) {
        window.signPageSystem = new SignPageSystem();
    }
});