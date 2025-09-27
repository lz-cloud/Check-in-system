// 后台管理功能交互逻辑

/**
 * 管理系统类
 */
class AdminSystem {
    constructor() {
        // 继承主系统功能
        this.mainSystem = window.signSystem || new SignSystem();
        this.init();
    }

    /**
     * 初始化管理系统
     */
    init() {
        // 初始化页面特定功能
        this.initDashboard();
        this.initLinksPage();
        this.initRecordsPage();
        this.initExportPage();
        this.initStudentManagement();
        this.initLinkToggle();
        this.initResetLinks();
        this.initSearch();
    }

    /**
     * 初始化仪表盘页面
     */
    initDashboard() {
        if (!document.getElementById('dashboard')) return;
        
        // 可以添加仪表盘特定的交互逻辑
        // 例如图表初始化、数据刷新等
    }

    /**
     * 初始化链接管理页面
     */
    initLinksPage() {
        if (!document.getElementById('links-page')) return;
        
        // 复制链接功能已在main.js中处理
        // 可以添加其他链接管理特定功能
    }

    /**
     * 初始化签到记录页面
     */
    initRecordsPage() {
        if (!document.getElementById('records-page')) return;
        
        // 日期选择器处理
        const datePicker = document.getElementById('date-filter');
        if (datePicker) {
            datePicker.addEventListener('change', (e) => {
                this.filterRecordsByDate(e.target.value);
            });
        }
        
        // 重新开始签到按钮
        const resetButton = document.getElementById('reset-sign-btn');
        if (resetButton) {
            resetButton.addEventListener('click', async () => {
                const confirmed = await this.mainSystem.confirmDialog(
                    '确定要重新开始签到吗？这将重置今天的所有签到状态，但保留历史数据。',
                    '确认重置'
                );
                
                if (confirmed) {
                    this.resetTodaySign();
                }
            });
        }
    }

    /**
     * 初始化数据导出页面
     */
    initExportPage() {
        if (!document.getElementById('export-page')) return;
        
        // 导出按钮处理
        const exportButton = document.getElementById('export-csv-btn');
        if (exportButton) {
            exportButton.addEventListener('click', () => {
                const dateSelect = document.getElementById('export-date');
                const date = dateSelect ? dateSelect.value : '';
                this.exportCSV(date);
            });
        }
    }

    /**
     * 初始化学生管理功能
     */
    initStudentManagement() {
        const studentTable = document.getElementById('student-table');
        if (!studentTable) return;
        
        // 文件上传区域处理
        const uploadArea = document.getElementById('upload-area');
        if (uploadArea) {
            // 拖拽上传功能
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, this.preventDefaults, false);
            });
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.classList.add('border-primary', 'bg-primary/10');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, () => {
                    uploadArea.classList.remove('border-primary', 'bg-primary/10');
                }, false);
            });
            
            // 处理文件放下事件
            uploadArea.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files.length > 0) {
                    this.handleFileUpload(files[0]);
                }
            }, false);
            
            // 点击上传区域触发文件选择
            uploadArea.addEventListener('click', () => {
                const fileInput = document.getElementById('student-file');
                if (fileInput) {
                    fileInput.click();
                }
            });
            
            // 文件选择变化时处理
            const fileInput = document.getElementById('student-file');
            if (fileInput) {
                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        this.handleFileUpload(e.target.files[0]);
                    }
                });
            }
        }
        
        // 下载专属链接按钮
        const downloadLinksButton = document.getElementById('download-links-btn');
        if (downloadLinksButton) {
            downloadLinksButton.addEventListener('click', async () => {
                try {
                    const response = await this.mainSystem.ajax('index.php?action=download_links', {
                        method: 'POST'
                    });
                    
                    if (response.success && response.download_url) {
                        window.location.href = response.download_url;
                    } else {
                        this.mainSystem.showToast('生成链接文件失败', 'error');
                    }
                } catch (error) {
                    this.mainSystem.showToast('网络错误，请重试', 'error');
                }
            });
        }
    }

    /**
     * 初始化链接启用/禁用开关
     */
    initLinkToggle() {
        const toggleSwitches = document.querySelectorAll('.link-toggle');
        toggleSwitches.forEach(toggle => {
            toggle.addEventListener('change', (e) => {
                const studentId = e.target.getAttribute('data-student-id');
                const isActive = e.target.checked;
                this.toggleLinkStatus(studentId, isActive);
            });
        });
    }

    /**
     * 初始化重置链接功能
     */
    initResetLinks() {
        const resetButtons = document.querySelectorAll('.reset-link-btn');
        resetButtons.forEach(button => {
            button.addEventListener('click', async (e) => {
                const studentId = e.target.getAttribute('data-student-id') || 
                                 e.target.closest('button').getAttribute('data-student-id');
                
                const confirmed = await this.mainSystem.confirmDialog(
                    '确定要重置此学生的专属链接吗？重置后原链接将失效。',
                    '确认重置链接'
                );
                
                if (confirmed) {
                    this.resetStudentLink(studentId);
                }
            });
        });
    }

    /**
     * 初始化搜索功能
     */
    initSearch() {
        const searchInputs = document.querySelectorAll('.search-input');
        searchInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const tableId = e.target.getAttribute('data-table');
                this.filterTable(tableId, searchTerm);
            });
        });
    }

    /**
     * 阻止默认事件
     * @param {Event} e 事件对象
     */
    preventsDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    /**
     * 处理文件上传
     * @param {File} file 上传的文件
     */
    handleFileUpload(file) {
        // 检查文件类型
        if (file.type !== 'application/json') {
            this.mainSystem.showToast('请上传JSON格式的文件', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const fileContent = e.target.result;
                // 验证JSON格式
                JSON.parse(fileContent);
                
                // 创建FormData并上传
                const formData = new FormData();
                formData.append('student_file', file);
                
                // 显示加载状态
                const uploadArea = document.getElementById('upload-area');
                if (uploadArea) {
                    uploadArea.innerHTML = '<div class="text-center py-6"><div class="loading-spinner mx-auto mb-3"></div><p>正在上传...</p></div>';
                }
                
                // 发送请求
                const response = await this.mainSystem.ajax('index.php?action=upload_students', {
                    method: 'POST',
                    body: formData,
                    // 不需要设置Content-Type，FormData会自动设置
                });
                
                if (response.success) {
                    this.mainSystem.showToast('学生名单上传成功', 'success');
                    // 刷新页面或更新表格
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    this.mainSystem.showToast(response.message || '上传失败', 'error');
                    // 恢复上传区域
                    if (uploadArea) {
                        uploadArea.innerHTML = '<div class="text-center py-6"><i class="fas fa-cloud-upload-alt text-4xl mb-3 text-primary"></i><p>点击或拖拽JSON文件到此处上传</p><p class="text-sm text-gray-500 mt-2">支持格式：.json</p></div>';
                    }
                }
            } catch (error) {
                this.mainSystem.showToast('文件格式错误，请检查JSON格式', 'error');
                // 恢复上传区域
                const uploadArea = document.getElementById('upload-area');
                if (uploadArea) {
                    uploadArea.innerHTML = '<div class="text-center py-6"><i class="fas fa-cloud-upload-alt text-4xl mb-3 text-primary"></i><p>点击或拖拽JSON文件到此处上传</p><p class="text-sm text-gray-500 mt-2">支持格式：.json</p></div>';
                }
            }
        };
        
        reader.readAsText(file);
    }

    /**
     * 根据日期筛选签到记录
     * @param {string} date 日期字符串
     */
    filterRecordsByDate(date) {
        // 在实际应用中，这里应该发送AJAX请求获取指定日期的数据
        // 为了演示，我们暂时只刷新页面并附带日期参数
        const url = new URL(window.location.href);
        url.searchParams.set('date', date);
        window.location.href = url.toString();
    }

    /**
     * 重置今天的签到状态
     */
    async resetTodaySign() {
        try {
            const response = await this.mainSystem.ajax('records.php?action=reset_today', {
                method: 'POST'
            });
            
            if (response.success) {
                this.mainSystem.showToast('签到状态已重置', 'success');
                // 刷新页面
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                this.mainSystem.showToast(response.message || '重置失败', 'error');
            }
        } catch (error) {
            this.mainSystem.showToast('网络错误，请重试', 'error');
        }
    }

    /**
     * 导出CSV文件
     * @param {string} date 日期
     */
    exportCSV(date) {
        // 构建导出URL
        const url = new URL('export.php', window.location.origin);
        if (date) {
            url.searchParams.set('date', date);
        }
        
        // 打开新窗口下载
        window.open(url.toString(), '_blank');
    }

    /**
     * 切换链接状态
     * @param {string} studentId 学生ID
     * @param {boolean} isActive 是否激活
     */
    async toggleLinkStatus(studentId, isActive) {
        try {
            const response = await this.mainSystem.ajax('links.php?action=toggle_link', {
                method: 'POST',
                body: new URLSearchParams({
                    student_id: studentId,
                    active: isActive ? 1 : 0
                })
            });
            
            if (response.success) {
                this.mainSystem.showToast(isActive ? '链接已启用' : '链接已禁用', 'success');
            } else {
                this.mainSystem.showToast(response.message || '操作失败', 'error');
                // 恢复原始状态
                const toggle = document.querySelector(`.link-toggle[data-student-id="${studentId}"]`);
                if (toggle) {
                    toggle.checked = !isActive;
                }
            }
        } catch (error) {
            this.mainSystem.showToast('网络错误，请重试', 'error');
            // 恢复原始状态
            const toggle = document.querySelector(`.link-toggle[data-student-id="${studentId}"]`);
            if (toggle) {
                toggle.checked = !isActive;
            }
        }
    }

    /**
     * 重置学生专属链接
     * @param {string} studentId 学生ID
     */
    async resetStudentLink(studentId) {
        try {
            const response = await this.mainSystem.ajax('links.php?action=reset_link', {
                method: 'POST',
                body: new URLSearchParams({
                    student_id: studentId
                })
            });
            
            if (response.success && response.new_link) {
                // 更新链接显示
                const linkElement = document.querySelector(`[data-student-link="${studentId}"]`);
                if (linkElement) {
                    linkElement.textContent = response.new_link;
                    linkElement.setAttribute('data-copy', response.new_link);
                }
                
                this.mainSystem.showToast('链接已重置', 'success');
            } else {
                this.mainSystem.showToast(response.message || '重置失败', 'error');
            }
        } catch (error) {
            this.mainSystem.showToast('网络错误，请重试', 'error');
        }
    }

    /**
     * 筛选表格内容
     * @param {string} tableId 表格ID
     * @param {string} searchTerm 搜索关键词
     */
    filterTable(tableId, searchTerm) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            let found = false;
            
            cells.forEach(cell => {
                if (cell.textContent.toLowerCase().includes(searchTerm)) {
                    found = true;
                }
            });
            
            row.style.display = found ? '' : 'none';
        });
    }
}

// 页面加载完成后初始化管理系统
document.addEventListener('DOMContentLoaded', () => {
    // 检查是否在管理页面
    if (window.location.pathname.includes('/admin/')) {
        window.adminSystem = new AdminSystem();
    }
});