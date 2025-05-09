<div class="task-container mb-3" data-id="{{ $task->id }}" data-status="{{ $task->status }}">
    <!-- 숨겨진 필드들 -->
    <input type="hidden" class="editable-field" data-field="plan_date" value="{{ $task->plan_date->format('Y-m-d') }}">
    <input type="hidden" class="editable-field" data-field="deadline" value="{{ $task->deadline ? $task->deadline->format('Y-m-d') : '' }}">
    <input type="hidden" class="editable-field" data-field="category_type" value="{{ $task->category_type }}">
    <input type="hidden" class="editable-field" data-field="category_detail" value="{{ $task->category_detail }}">
    <input type="hidden" class="editable-field" data-field="completion_date" value="{{ $task->completion_date ? $task->completion_date->format('Y-m-d') : '' }}">
    <input type="hidden" class="editable-field" data-field="memo" value="{{ $task->memo }}">
    <input type="hidden" class="editable-field" data-field="status" value="{{ $task->status }}">
    
    <div class="main-task">
        <div class="main-task-info">
            @if(count($task->subtasks) > 0)
                <span class="collapse-icon" data-task-id="{{ $task->id }}">▼</span>
            @endif
            <span class="task-number">{{ $loop->iteration }}.</span>
            <input type="text" class="task-input editable-field" data-field="description" value="{{ $task->description }}" data-task-id="{{ $task->id }}">
        </div>
        <div class="button-group">
            <button type="button" class="btn btn-add add-subtask-btn" data-task-id="{{ $task->id }}">+</button>
            <button type="button" class="btn btn-remove delete-task" data-id="{{ $task->id }}">-</button>
        </div>
    </div>

    @if ($task->subtasks->count() > 0)
        <div class="subtasks-container mt-2" data-task-id="{{ $task->id }}">
            @foreach ($task->subtasks as $subtask)
                <div class="subtask">
                    <div class="subtask-info">
                        <span class="subtask-prefix">└</span>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input subtask-checkbox" type="checkbox" value="{{ $subtask->id }}"
                                {{ $subtask->is_completed ? 'checked' : '' }}>
                        </div>
                        <div class="subtask-description {{ $subtask->is_completed ? 'completed-task' : '' }}">
                            <input type="text" class="subtask-description-input"
                                data-subtask-id="{{ $subtask->id }}" value="{{ $subtask->description }}"
                                {{ $subtask->is_completed ? 'disabled' : '' }}>
                        </div>
                    </div>
                    <div class="subtask-actions">
                        <button class="btn btn-sm text-danger delete-subtask-btn" data-subtask-id="{{ $subtask->id }}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="subtasks-container mt-2" style="display: none;" data-task-id="{{ $task->id }}"></div>
    @endif

    <div class="add-subtask-container mt-2" style="display: none;" data-task-id="{{ $task->id }}">
        <form class="add-subtask-form" data-task-id="{{ $task->id }}">
            <div class="input-group new-subtask-form" data-task-id="{{ $task->id }}">
                <span class="input-group-text subtask-prefix">└</span>
                <input type="text" class="form-control new-subtask-input" placeholder="새 하위업무 추가">
                <button class="btn btn-success save-subtask-btn" type="submit">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-secondary cancel-subtask-btn" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </form>
    </div>
</div>
