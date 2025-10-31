/**
 * 活动编辑器核心模块
 */
class ActivityEditor {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.activityId = this.container.dataset.activityId;
        this.canvas = document.getElementById('canvas-area');
        this.propertiesPanel = document.getElementById('properties-panel');
        this.components = [];
        this.selectedComponent = null;
        this.isDirty = false;
        
        // 撤销/重做历史
        this.history = [];
        this.historyIndex = -1;
        this.maxHistorySize = 50;
        
        // 设备预览模式
        this.deviceMode = 'desktop';
        
        this.init();
    }
    
    init() {
        this.setupDragAndDrop();
        this.setupEventListeners();
        this.loadExistingComponents();
        this.setupAutoSave();
        this.setupPropertyBinding();
        this.setupUndoRedo();
        this.setupKeyboardShortcuts();
        this.setupDeviceSwitcher();
        this.setupGridAlignment();
    }
    
    setupDragAndDrop() {
        // 组件拖拽
        document.querySelectorAll('.component-item').forEach(item => {
            item.addEventListener('dragstart', (e) => this.handleDragStart(e));
            item.addEventListener('dragend', (e) => this.handleDragEnd(e));
        });
        
        // 画布接收
        this.canvas.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.canvas.addEventListener('drop', (e) => this.handleDrop(e));
        this.canvas.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        
        // 组件排序
        this.setupSortable();
    }
    
    setupSortable() {
        if (typeof Sortable !== 'undefined') {
            new Sortable(this.canvas, {
                animation: 150,
                handle: '.component-handle',
                ghostClass: 'sortable-ghost',
                onEnd: (evt) => {
                    this.reorderComponents(evt.oldIndex, evt.newIndex);
                }
            });
        }
    }
    
    handleDragStart(e) {
        e.dataTransfer.effectAllowed = 'copy';
        const item = e.currentTarget;
        e.dataTransfer.setData('componentType', item.dataset.componentType);
        e.dataTransfer.setData('componentConfig', item.dataset.componentConfig);
        
        // 添加拖拽效果
        item.classList.add('dragging');
    }
    
    handleDragEnd(e) {
        e.currentTarget.classList.remove('dragging');
    }
    
    handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'copy';
        this.canvas.classList.add('drag-over');
        
        // 显示放置指示器
        this.showDropIndicator(e);
        
        return false;
    }
    
    handleDragLeave(e) {
        if (e.target === this.canvas) {
            this.canvas.classList.remove('drag-over');
            this.hideDropIndicator();
        }
    }
    
    handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        this.canvas.classList.remove('drag-over');
        this.hideDropIndicator();
        
        const componentType = e.dataTransfer.getData('componentType');
        const componentConfig = JSON.parse(e.dataTransfer.getData('componentConfig'));
        
        // 计算放置位置
        const position = this.calculateDropPosition(e);
        
        // 添加组件
        this.addComponent(componentType, componentConfig, position);
        
        return false;
    }
    
    showDropIndicator(e) {
        // 实现放置指示器逻辑
        const indicator = document.getElementById('drop-indicator') || this.createDropIndicator();
        const rect = this.canvas.getBoundingClientRect();
        const y = e.clientY - rect.top;
        
        indicator.style.display = 'block';
        indicator.style.top = y + 'px';
    }
    
    hideDropIndicator() {
        const indicator = document.getElementById('drop-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }
    
    createDropIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'drop-indicator';
        indicator.className = 'drop-indicator';
        indicator.style.cssText = 'position: absolute; left: 0; right: 0; height: 2px; background: #007bff; display: none;';
        this.canvas.appendChild(indicator);
        return indicator;
    }
    
    calculateDropPosition(e) {
        const components = this.canvas.querySelectorAll('.canvas-component');
        const y = e.clientY;
        
        let position = components.length;
        
        components.forEach((component, index) => {
            const rect = component.getBoundingClientRect();
            if (y < rect.top + rect.height / 2) {
                position = Math.min(position, index);
            }
        });
        
        return position;
    }
    
    addComponent(type, config, position = null) {
        // 移除占位符
        const placeholder = this.canvas.querySelector('.canvas-placeholder');
        if (placeholder) {
            placeholder.remove();
        }
        
        const component = {
            id: 'component-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
            type: type,
            config: config.defaultConfig || {},
            position: position !== null ? position : this.components.length,
            visible: true
        };
        
        // 插入到指定位置
        if (position !== null) {
            this.components.splice(position, 0, component);
            // 重新计算位置
            this.components.forEach((comp, index) => {
                comp.position = index;
            });
        } else {
            this.components.push(component);
        }
        
        // 创建DOM元素
        const element = this.createComponentElement(component);
        
        // 插入到正确位置
        if (position !== null && position < this.canvas.children.length) {
            this.canvas.insertBefore(element, this.canvas.children[position]);
        } else {
            this.canvas.appendChild(element);
        }
        
        // 自动选中新添加的组件
        this.selectComponent(component);
        
        // 标记为已修改
        this.setDirty(true);
        
        // 触发组件添加事件
        this.triggerEvent('component:added', { component });
    }
    
    createComponentElement(component) {
        const div = document.createElement('div');
        div.className = 'canvas-component';
        div.dataset.componentId = component.id;
        div.dataset.componentType = component.type;
        
        // 拖拽手柄
        const handle = document.createElement('div');
        handle.className = 'component-handle';
        handle.innerHTML = '<i class="fa fa-grip-vertical"></i>';
        
        // 工具栏
        const toolbar = document.createElement('div');
        toolbar.className = 'component-toolbar';
        toolbar.innerHTML = `
            <button class="btn btn-sm btn-info" title="编辑" onclick="activityEditor.editComponent('${component.id}')">
                <i class="fa fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-warning" title="复制" onclick="activityEditor.duplicateComponent('${component.id}')">
                <i class="fa fa-copy"></i>
            </button>
            <button class="btn btn-sm btn-secondary" title="${component.visible ? '隐藏' : '显示'}" 
                    onclick="activityEditor.toggleComponentVisibility('${component.id}')">
                <i class="fa fa-eye${component.visible ? '' : '-slash'}"></i>
            </button>
            <button class="btn btn-sm btn-danger" title="删除" onclick="activityEditor.removeComponent('${component.id}')">
                <i class="fa fa-trash"></i>
            </button>
        `;
        
        div.appendChild(handle);
        div.appendChild(toolbar);
        
        // 组件预览
        const preview = document.createElement('div');
        preview.className = 'component-preview';
        preview.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> 加载中...</div>';
        div.appendChild(preview);
        
        // 加载预览
        this.loadComponentPreview(component, preview);
        
        // 点击选中
        div.addEventListener('click', (e) => {
            if (!e.target.closest('.component-toolbar')) {
                this.selectComponent(component);
            }
        });
        
        return div;
    }
    
    async loadComponentPreview(component, previewElement) {
        try {
            const response = await fetch('/admin/activity/editor/preview-component', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    type: component.type,
                    config: component.config
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                previewElement.innerHTML = data.html;
                // 执行预览中的脚本
                this.executeScripts(previewElement);
            } else {
                previewElement.innerHTML = `
                    <div class="alert alert-danger">
                        预览失败: ${data.error || '未知错误'}
                    </div>
                `;
            }
        } catch (error) {
            previewElement.innerHTML = `
                <div class="alert alert-danger">
                    预览加载失败: ${error.message}
                </div>
            `;
        }
    }
    
    executeScripts(element) {
        const scripts = element.querySelectorAll('script');
        scripts.forEach(script => {
            const newScript = document.createElement('script');
            newScript.textContent = script.textContent;
            script.parentNode.replaceChild(newScript, script);
        });
    }
    
    selectComponent(component) {
        // 移除之前的选中状态
        document.querySelectorAll('.canvas-component').forEach(el => {
            el.classList.remove('selected');
        });
        
        // 添加选中状态
        const element = document.querySelector(`[data-component-id="${component.id}"]`);
        if (element) {
            element.classList.add('selected');
        }
        
        this.selectedComponent = component;
        this.showProperties(component);
        
        // 触发选中事件
        this.triggerEvent('component:selected', { component });
    }
    
    showProperties(component) {
        // 根据组件类型加载属性面板
        this.loadPropertyPanel(component);
    }
    
    async loadPropertyPanel(component) {
        // 获取组件schema
        const schema = await this.getComponentSchema(component.type);
        
        // 生成属性表单
        const form = this.generatePropertyForm(schema, component.config);
        
        this.propertiesPanel.innerHTML = `
            <h6>${component.type} 属性设置</h6>
            <div class="property-form">
                ${form}
            </div>
        `;
        
        // 绑定表单事件
        this.bindPropertyEvents(component);
    }
    
    generatePropertyForm(schema, config) {
        let html = '';
        
        for (const [key, field] of Object.entries(schema)) {
            const value = config[key] !== undefined ? config[key] : field.default || '';
            
            html += `
                <div class="property-field mb-3">
                    <label for="prop-${key}" class="form-label">${field.label || key}</label>
                    ${this.generateFieldInput(key, field, value)}
                </div>
            `;
        }
        
        return html;
    }
    
    generateFieldInput(key, field, value) {
        const type = field.type || 'string';
        const editor = field.editor || type;
        
        switch (editor) {
            case 'richtext':
                return `<textarea id="prop-${key}" name="${key}" class="form-control" rows="4">${value}</textarea>`;
                
            case 'color':
                return `<input type="color" id="prop-${key}" name="${key}" class="form-control form-control-color" value="${value}">`;
                
            case 'image':
                return `
                    <div class="input-group">
                        <input type="text" id="prop-${key}" name="${key}" class="form-control" value="${value}">
                        <button class="btn btn-outline-secondary" type="button" onclick="activityEditor.selectImage('${key}')">
                            <i class="fa fa-image"></i>
                        </button>
                    </div>
                `;
                
            case 'boolean':
                return `
                    <div class="form-check form-switch">
                        <input type="checkbox" id="prop-${key}" name="${key}" class="form-check-input" ${value ? 'checked' : ''}>
                    </div>
                `;
                
            case 'select':
                const options = field.options || [];
                return `
                    <select id="prop-${key}" name="${key}" class="form-select">
                        ${options.map(opt => `<option value="${opt}" ${value === opt ? 'selected' : ''}>${opt}</option>`).join('')}
                    </select>
                `;
                
            default:
                return `<input type="text" id="prop-${key}" name="${key}" class="form-control" value="${value}">`;
        }
    }
    
    bindPropertyEvents(component) {
        const inputs = this.propertiesPanel.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // 实时更新
            input.addEventListener('input', debounce(() => {
                this.updateComponentProperty(component, input.name, this.getInputValue(input));
            }, 300));
            
            // 立即更新某些属性
            if (input.type === 'checkbox' || input.type === 'select-one') {
                input.addEventListener('change', () => {
                    this.updateComponentProperty(component, input.name, this.getInputValue(input));
                });
            }
        });
    }
    
    getInputValue(input) {
        if (input.type === 'checkbox') {
            return input.checked;
        }
        return input.value;
    }
    
    updateComponentProperty(component, property, value) {
        // 更新组件配置
        component.config[property] = value;
        
        // 重新渲染预览
        const element = document.querySelector(`[data-component-id="${component.id}"]`);
        if (element) {
            const preview = element.querySelector('.component-preview');
            this.loadComponentPreview(component, preview);
        }
        
        // 标记为已修改
        this.setDirty(true);
        
        // 触发更新事件
        this.triggerEvent('component:updated', { component, property, value });
    }
    
    editComponent(componentId) {
        const component = this.components.find(c => c.id === componentId);
        if (component) {
            this.selectComponent(component);
        }
    }
    
    duplicateComponent(componentId) {
        const component = this.components.find(c => c.id === componentId);
        if (component) {
            const newComponent = {
                ...component,
                id: 'component-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                config: { ...component.config },
                position: component.position + 1
            };
            
            // 更新后续组件位置
            this.components.forEach(c => {
                if (c.position > component.position) {
                    c.position++;
                }
            });
            
            // 添加新组件
            this.components.push(newComponent);
            this.components.sort((a, b) => a.position - b.position);
            
            // 创建DOM元素
            const element = this.createComponentElement(newComponent);
            const originalElement = document.querySelector(`[data-component-id="${componentId}"]`);
            originalElement.parentNode.insertBefore(element, originalElement.nextSibling);
            
            // 选中新组件
            this.selectComponent(newComponent);
            
            this.setDirty(true);
        }
    }
    
    toggleComponentVisibility(componentId) {
        const component = this.components.find(c => c.id === componentId);
        if (component) {
            component.visible = !component.visible;
            
            // 更新按钮图标
            const element = document.querySelector(`[data-component-id="${componentId}"]`);
            const button = element.querySelector('.component-toolbar button[title*="显示"], .component-toolbar button[title*="隐藏"]');
            const icon = button.querySelector('i');
            
            if (component.visible) {
                button.title = '隐藏';
                icon.className = 'fa fa-eye';
                element.classList.remove('component-hidden');
            } else {
                button.title = '显示';
                icon.className = 'fa fa-eye-slash';
                element.classList.add('component-hidden');
            }
            
            this.setDirty(true);
        }
    }
    
    removeComponent(componentId) {
        if (confirm('确定要删除这个组件吗？')) {
            // 从数组中移除
            const index = this.components.findIndex(c => c.id === componentId);
            if (index > -1) {
                this.components.splice(index, 1);
                
                // 重新计算位置
                this.components.forEach((comp, idx) => {
                    comp.position = idx;
                });
                
                // 从DOM中移除
                const element = document.querySelector(`[data-component-id="${componentId}"]`);
                if (element) {
                    element.remove();
                }
                
                // 如果没有组件了，显示占位符
                if (this.components.length === 0) {
                    this.showPlaceholder();
                }
                
                // 清空属性面板
                if (this.selectedComponent && this.selectedComponent.id === componentId) {
                    this.selectedComponent = null;
                    this.propertiesPanel.innerHTML = `
                        <div class="text-muted text-center py-3">
                            选择一个组件以编辑属性
                        </div>
                    `;
                }
                
                this.setDirty(true);
            }
        }
    }
    
    reorderComponents(oldIndex, newIndex) {
        if (oldIndex === newIndex) return;
        
        const component = this.components[oldIndex];
        this.components.splice(oldIndex, 1);
        this.components.splice(newIndex, 0, component);
        
        // 重新计算位置
        this.components.forEach((comp, index) => {
            comp.position = index;
        });
        
        this.setDirty(true);
    }
    
    showPlaceholder() {
        this.canvas.innerHTML = `
            <div class="canvas-placeholder text-center text-muted py-5">
                将组件拖拽到这里
            </div>
        `;
    }
    
    async loadExistingComponents() {
        try {
            const response = await fetch(`/admin/activity/${this.activityId}/editor/components`);
            const data = await response.json();
            
            if (data && data.length > 0) {
                // 移除占位符
                const placeholder = this.canvas.querySelector('.canvas-placeholder');
                if (placeholder) {
                    placeholder.remove();
                }
                
                data.forEach(componentData => {
                    const component = {
                        id: 'component-' + componentData.id,
                        type: componentData.type,
                        config: componentData.config,
                        position: componentData.position,
                        visible: componentData.visible
                    };
                    
                    this.components.push(component);
                    const element = this.createComponentElement(component);
                    this.canvas.appendChild(element);
                });
            }
        } catch (error) {
            console.error('Failed to load components:', error);
        }
    }
    
    async saveComponents() {
        try {
            const response = await fetch(`/admin/activity/${this.activityId}/editor/components`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(this.components)
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.setDirty(false);
                this.showNotification('保存成功！', 'success');
            } else {
                this.showNotification('保存失败：' + (data.error || '未知错误'), 'danger');
            }
        } catch (error) {
            this.showNotification('保存失败：' + error.message, 'danger');
        }
    }
    
    setupAutoSave() {
        // 每30秒自动保存
        setInterval(() => {
            if (this.isDirty) {
                this.saveComponents();
            }
        }, 30000);
        
        // 离开页面时提示保存
        window.addEventListener('beforeunload', (e) => {
            if (this.isDirty) {
                e.preventDefault();
                e.returnValue = '您有未保存的更改，确定要离开吗？';
            }
        });
    }
    
    setupPropertyBinding() {
        // 实现双向数据绑定
        Object.defineProperty(this, 'selectedComponentConfig', {
            get() {
                return this.selectedComponent ? this.selectedComponent.config : null;
            },
            set(value) {
                if (this.selectedComponent) {
                    this.selectedComponent.config = value;
                    this.updateComponentPreview(this.selectedComponent);
                }
            }
        });
    }
    
    updateComponentPreview(component) {
        const element = document.querySelector(`[data-component-id="${component.id}"]`);
        if (element) {
            const preview = element.querySelector('.component-preview');
            this.loadComponentPreview(component, preview);
        }
    }
    
    setDirty(dirty) {
        this.isDirty = dirty;
        
        // 更新保存按钮状态
        const saveButton = document.getElementById('btn-save');
        if (saveButton) {
            if (dirty) {
                saveButton.classList.add('btn-warning');
                saveButton.innerHTML = '<i class="fa fa-save"></i> 保存*';
            } else {
                saveButton.classList.remove('btn-warning');
                saveButton.innerHTML = '<i class="fa fa-save"></i> 保存';
            }
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
    
    triggerEvent(eventName, detail) {
        const event = new CustomEvent(eventName, { detail });
        this.container.dispatchEvent(event);
    }
    
    setupEventListeners() {
        // 保存按钮
        document.getElementById('btn-save')?.addEventListener('click', () => {
            this.saveComponents();
        });
        
        // 预览按钮
        document.getElementById('btn-preview')?.addEventListener('click', () => {
            window.open(`/activity/preview/${this.activityId}`, '_blank');
        });
        
        // 发布按钮
        document.getElementById('btn-publish')?.addEventListener('click', async () => {
            if (confirm('确定要发布这个活动吗？')) {
                try {
                    const response = await fetch(`/admin/activity/${this.activityId}/editor/publish`, {
                        method: 'POST'
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showNotification('发布成功！', 'success');
                    } else {
                        this.showNotification('发布失败：' + (data.error || '未知错误'), 'danger');
                    }
                } catch (error) {
                    this.showNotification('发布失败：' + error.message, 'danger');
                }
            }
        });
    }
    
    async getComponentSchema(type) {
        // 从缓存或服务器获取组件schema
        // 这里简化处理，实际应该从服务器获取
        return {
            content: { type: 'string', label: '内容', editor: 'richtext' },
            alignment: { type: 'string', label: '对齐', editor: 'select', options: ['left', 'center', 'right'] },
            fontSize: { type: 'string', label: '字体大小', default: '14px' },
            color: { type: 'string', label: '文字颜色', editor: 'color', default: '#333333' },
            backgroundColor: { type: 'string', label: '背景颜色', editor: 'color', default: 'transparent' },
            padding: { type: 'string', label: '内边距', default: '10px' },
            className: { type: 'string', label: '自定义样式类' }
        };
    }
    
    selectImage(fieldKey) {
        // 打开图片选择器
        // 这里可以集成文件管理器或媒体库
        const url = prompt('请输入图片URL:');
        if (url) {
            const input = document.getElementById(`prop-${fieldKey}`);
            if (input) {
                input.value = url;
                input.dispatchEvent(new Event('input'));
            }
        }
    }
    
    // 撤销/重做功能
    setupUndoRedo() {
        // 保存初始状态
        this.saveHistory();
        
        // 工具栏按钮
        const undoBtn = document.getElementById('undo-btn');
        const redoBtn = document.getElementById('redo-btn');
        
        if (undoBtn) {
            undoBtn.addEventListener('click', () => this.undo());
        }
        if (redoBtn) {
            redoBtn.addEventListener('click', () => this.redo());
        }
        
        // 监听组件变化
        this.canvas.addEventListener('component-changed', () => {
            this.saveHistory();
        });
    }
    
    saveHistory() {
        // 删除当前索引之后的历史
        this.history = this.history.slice(0, this.historyIndex + 1);
        
        // 保存当前状态
        const state = {
            components: JSON.parse(JSON.stringify(this.components)),
            timestamp: Date.now()
        };
        
        this.history.push(state);
        
        // 限制历史记录大小
        if (this.history.length > this.maxHistorySize) {
            this.history.shift();
        } else {
            this.historyIndex++;
        }
        
        this.updateUndoRedoButtons();
    }
    
    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.restoreState(this.history[this.historyIndex]);
            this.updateUndoRedoButtons();
        }
    }
    
    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.restoreState(this.history[this.historyIndex]);
            this.updateUndoRedoButtons();
        }
    }
    
    restoreState(state) {
        this.components = JSON.parse(JSON.stringify(state.components));
        this.renderAllComponents();
        this.setDirty(true);
    }
    
    updateUndoRedoButtons() {
        const undoBtn = document.getElementById('undo-btn');
        const redoBtn = document.getElementById('redo-btn');
        
        if (undoBtn) {
            undoBtn.disabled = this.historyIndex <= 0;
        }
        if (redoBtn) {
            redoBtn.disabled = this.historyIndex >= this.history.length - 1;
        }
    }
    
    renderAllComponents() {
        // 清空画布
        this.canvas.innerHTML = '';
        
        // 重新渲染所有组件
        this.components.forEach(component => {
            const element = this.createComponentElement(component);
            this.canvas.appendChild(element);
        });
        
        if (this.components.length === 0) {
            this.showPlaceholder();
        }
    }
    
    // 快捷键支持
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + Z: 撤销
            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                this.undo();
            }
            
            // Ctrl/Cmd + Shift + Z 或 Ctrl/Cmd + Y: 重做
            if (((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'z') || 
                ((e.ctrlKey || e.metaKey) && e.key === 'y')) {
                e.preventDefault();
                this.redo();
            }
            
            // Ctrl/Cmd + S: 保存
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                this.saveComponents();
            }
            
            // Delete: 删除选中组件
            if (e.key === 'Delete' && this.selectedComponent) {
                e.preventDefault();
                this.removeComponent(this.selectedComponent.id);
            }
            
            // Ctrl/Cmd + D: 复制选中组件
            if ((e.ctrlKey || e.metaKey) && e.key === 'd' && this.selectedComponent) {
                e.preventDefault();
                this.duplicateComponent(this.selectedComponent.id);
            }
            
            // Ctrl/Cmd + C: 复制
            if ((e.ctrlKey || e.metaKey) && e.key === 'c' && this.selectedComponent) {
                e.preventDefault();
                this.copyComponent();
            }
            
            // Ctrl/Cmd + V: 粘贴
            if ((e.ctrlKey || e.metaKey) && e.key === 'v') {
                e.preventDefault();
                this.pasteComponent();
            }
        });
    }
    
    copyComponent() {
        if (this.selectedComponent) {
            this.clipboard = JSON.parse(JSON.stringify(this.selectedComponent));
            this.showNotification('组件已复制', 'info');
        }
    }
    
    pasteComponent() {
        if (this.clipboard) {
            const newComponent = {
                ...this.clipboard,
                id: 'component-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                position: this.components.length
            };
            
            this.components.push(newComponent);
            const element = this.createComponentElement(newComponent);
            this.canvas.appendChild(element);
            
            this.selectComponent(newComponent);
            this.saveHistory();
            this.setDirty(true);
            
            this.showNotification('组件已粘贴', 'success');
        }
    }
    
    // 响应式设备切换
    setupDeviceSwitcher() {
        const deviceButtons = document.querySelectorAll('[data-device]');
        
        deviceButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const device = btn.dataset.device;
                this.switchDevice(device);
                
                // 更新按钮状态
                deviceButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    }
    
    switchDevice(device) {
        this.deviceMode = device;
        const canvasWrapper = this.canvas.parentElement;
        
        // 移除所有设备类
        canvasWrapper.classList.remove('device-desktop', 'device-tablet', 'device-mobile');
        
        // 添加新设备类
        canvasWrapper.classList.add(`device-${device}`);
        
        // 设置画布宽度
        switch(device) {
            case 'mobile':
                canvasWrapper.style.maxWidth = '375px';
                break;
            case 'tablet':
                canvasWrapper.style.maxWidth = '768px';
                break;
            case 'desktop':
            default:
                canvasWrapper.style.maxWidth = '100%';
                break;
        }
        
        this.showNotification(`切换到${this.getDeviceName(device)}视图`, 'info');
    }
    
    getDeviceName(device) {
        const names = {
            'desktop': 'PC',
            'tablet': '平板',
            'mobile': '手机'
        };
        return names[device] || device;
    }
    
    // 网格对齐
    setupGridAlignment() {
        this.gridSize = 10;
        this.snapToGrid = false;
        
        const gridToggle = document.getElementById('grid-toggle');
        if (gridToggle) {
            gridToggle.addEventListener('change', (e) => {
                this.snapToGrid = e.target.checked;
                this.canvas.classList.toggle('show-grid', this.snapToGrid);
            });
        }
    }
    
    snapPosition(position) {
        if (!this.snapToGrid) return position;
        
        return Math.round(position / this.gridSize) * this.gridSize;
    }
}

// 防抖函数
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 全局实例
let activityEditor;

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    const editorContainer = document.getElementById('activity-editor');
    if (editorContainer) {
        activityEditor = new ActivityEditor('activity-editor');
    }
});