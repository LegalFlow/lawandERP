<div class="task-item {{ $isRoot ? 'root-task' : 'subtask' }} mb-3" data-id="{{ $task->id }}">
    @if($isRoot)
    <div class="card {{ $task->duration_minutes && $task->duration_minutes > 0 ? 'bg-completed-task' : '' }}">
        <div class="card-body">
            <div class="d-flex align-items-center mb-2">
                @if($task->children->count() > 0)
                <button class="btn btn-sm toggle-subtasks me-1" data-expanded="false">
                    <i class="bi bi-chevron-down"></i>
                </button>
                @else
                <span class="me-1" style="width: 31px;"></span>
                @endif
                <span class="task-number me-2">{{ $loop->iteration }}.</span>
                <div class="form-group mb-0 me-2">
                    <select class="form-control editable-field" data-field="category_type">
                        <option value="">카테고리 선택</option>
                        @foreach(array_keys($categories ?? []) as $category)
                            <option value="{{ $category }}" {{ $task->category_type == $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0 me-2">
                    <select class="form-control editable-field" data-field="category_detail" {{ empty($task->category_type) ? 'disabled' : '' }}>
                        <option value="">세부 카테고리 선택</option>
                        @if(!empty($task->category_type) && isset($categories[$task->category_type]))
                            @foreach($categories[$task->category_type] as $detail)
                                <option value="{{ $detail }}" {{ $task->category_detail == $detail ? 'selected' : '' }}>{{ $detail }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="form-group mb-0 me-2">
                    <input type="text" class="form-control time-input editable-field" data-field="start_time" value="{{ $task->start_time ? \Carbon\Carbon::parse($task->start_time)->format('H:i') : '' }}" placeholder="시작시각" maxlength="4">
                </div>
                <div class="form-group mb-0 me-2">
                    <input type="text" class="form-control time-input editable-field" data-field="end_time" value="{{ $task->end_time ? \Carbon\Carbon::parse($task->end_time)->format('H:i') : '' }}" placeholder="종료시각" maxlength="4">
                </div>
                <div class="form-group mb-0 flex-grow-1 me-2">
                    <input type="text" class="form-control editable-field" data-field="description" value="{{ $task->description }}" placeholder="태스크 설명">
                </div>
                <button class="btn btn-outline-action btn-outline-add add-existing-subtask me-2">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <button class="btn btn-outline-action btn-outline-delete delete-existing-task">
                    <i class="bi bi-dash-lg"></i>
                </button>
            </div>
            <div class="subtasks-container ps-4" style="display: none;">
                @foreach($task->children as $subtask)
                    @include('work_logs.partials.task', ['task' => $subtask, 'isRoot' => false])
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="d-flex align-items-center">
        <span class="ms-2">ㄴ</span>
        <div class="form-group mb-0 flex-grow-1 ms-2 me-2">
            <input type="text" class="form-control editable-field" data-field="description" value="{{ $task->description }}" placeholder="하위 태스크 설명">
        </div>
        <button class="btn btn-outline-action btn-outline-delete delete-existing-task">
            <i class="bi bi-dash-lg"></i>
        </button>
    </div>
    @endif
</div> 