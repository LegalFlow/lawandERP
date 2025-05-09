<div class="modal-dialog modal-xl" style="max-width: 80%;">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">업무 리스트 가져오기</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            @if($taskLists->isEmpty())
                <div class="alert alert-info">
                    가져올 수 있는 업무 리스트가 없습니다. 업무 리스트 페이지에서 업무를 추가해주세요.
                </div>
            @else
                <form id="import-tasks-form">
                    <input type="hidden" name="work_log_id" value="{{ $workLog->id }}">
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="select-all-tasks">
                            <label class="form-check-label" for="select-all-tasks">
                                전체 선택
                            </label>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 5%"></th>
                                    <th style="width: 10%">계획일자</th>
                                    <th style="width: 10%">카테고리</th>
                                    <th style="width: 15%">하위카테고리</th>
                                    <th style="width: 40%">업무내용</th>
                                    <th style="width: 10%">상태</th>
                                    <th style="width: 10%">완료로 표시</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($taskLists as $task)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input task-checkbox" type="checkbox" name="task_list_ids[]" value="{{ $task->id }}" id="task-{{ $task->id }}">
                                        </div>
                                    </td>
                                    <td>{{ $task->plan_date->format('Y-m-d') }}</td>
                                    <td>{{ $task->category_type }}</td>
                                    <td>{{ $task->category_detail }}</td>
                                    <td>{{ $task->description }}</td>
                                    <td>
                                        @if($task->status == '진행예정')
                                            <span class="badge" style="background-color: #e9ecef; color: #212529;">{{ $task->status }}</span>
                                        @elseif($task->status == '진행중')
                                            <span class="badge" style="background-color: #fff3cd; color: #212529;">{{ $task->status }}</span>
                                        @else
                                            <span class="badge" style="background-color: #f8f9fa; color: #212529;">{{ $task->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input complete-checkbox" type="checkbox" name="mark_as_completed[{{ $task->id }}]" value="1" id="complete-{{ $task->id }}" checked>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
            @if(!$taskLists->isEmpty())
                <button type="button" class="btn btn-primary" id="import-selected-tasks-btn">가져오기</button>
            @endif
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // 전체 선택 체크박스
        $('#select-all-tasks').change(function() {
            $('.task-checkbox').prop('checked', $(this).prop('checked'));
        });
        
        // 개별 체크박스 변경 시 전체 선택 체크박스 상태 업데이트
        $('.task-checkbox').change(function() {
            if ($('.task-checkbox:checked').length === $('.task-checkbox').length) {
                $('#select-all-tasks').prop('checked', true);
            } else {
                $('#select-all-tasks').prop('checked', false);
            }
        });
        
        // 가져오기 버튼 클릭 이벤트
        $('#import-selected-tasks-btn').click(function() {
            // 선택된 항목이 있는지 확인
            if ($('.task-checkbox:checked').length === 0) {
                alert('가져올 업무를 선택해주세요.');
                return;
            }
            
            // 폼 데이터 수집
            const formData = $('#import-tasks-form').serialize();
            
            // 저장 중 알림 표시
            window.savingToast.show();
            
            // AJAX 요청
            $.ajax({
                url: "{{ route('work-logs.import-tasks') }}",
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    window.savingToast.hide();
                    
                    if (response.success) {
                        // 성공 메시지 표시
                        $('#saveToast .toast-body').html('<i class="bi bi-check-circle me-2"></i> ' + response.message);
                        window.saveToast.show();
                        
                        // 모달 닫기
                        $('#importTasksModal').modal('hide');
                        
                        // 페이지 새로고침
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                },
                error: function() {
                    window.savingToast.hide();
                    window.errorToast.show();
                }
            });
        });
    });
</script> 