<div class="task-container mb-3" data-temp-id="${tempId}">
    <!-- 숨겨진 필드들 -->
    <input type="hidden" class="auto-save" data-field="plan_date" value="${today}">
    <input type="hidden" class="auto-save" data-field="deadline" value="">
    <input type="hidden" class="auto-save" data-field="category_type" value="${defaultCategory}">
    <input type="hidden" class="auto-save" data-field="category_detail" value="${defaultCategoryDetail}">
    <input type="hidden" class="auto-save" data-field="completion_date" value="">
    <input type="hidden" class="auto-save" data-field="memo" value="">
    <input type="hidden" class="auto-save" data-field="status" value="진행예정">
    
    <div class="main-task">
        <div class="main-task-info">
            <span class="task-number">-.</span>
            <input type="text" class="task-input auto-save" data-field="description" placeholder="새 업무 입력..." required>
        </div>
        <div class="button-group">
            <button type="button" class="btn btn-add add-subtask-btn" data-temp-id="${tempId}">+</button>
            <button type="button" class="btn btn-remove delete-task" data-temp-id="${tempId}">-</button>
        </div>
    </div>
    
    <div class="subtasks-container" style="display: none;">
        <!-- 하위업무는 추가 후 표시됩니다 -->
    </div>
    
    <div class="subtask new-subtask-form" style="display: none;">
        <div class="subtask-info">
            <span class="subtask-prefix">ㄴ</span>
            <input type="text" class="task-input new-subtask-input" placeholder="새 하위업무 입력...">
        </div>
        <div class="button-group">
            <button class="btn btn-add create-subtask" data-temp-id="${tempId}">+</button>
        </div>
    </div>
</div> 