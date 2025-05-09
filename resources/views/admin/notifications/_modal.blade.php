<!-- 통지 작성/수정 모달 -->
<div id="notificationModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">통지 작성</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="notificationForm" method="POST" action="{{ route('admin.notifications.store') }}">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    
                    <div class="mb-3">
                        <label class="form-label">통지유형</label>
                        <select name="type" class="form-select" required>
                            @foreach(\App\Models\Notification::getAvailableTypes() as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">제목</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">내용</label>
                        <textarea name="content" rows="5" class="form-control" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">피통지자</label>
                        <select name="notified_user_id" class="form-select" required>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">경유자</label>
                        <select name="via_user_id" class="form-select">
                            <option value="">선택 안함</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="response_required" id="responseRequired" 
                                   class="form-check-input" onchange="toggleDeadlineInput(this)">
                            <label class="form-check-label" for="responseRequired">
                                답변 필요
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="deadlineInput" style="display: none;">
                        <label class="form-label">답변기한 (일)</label>
                        <input type="number" name="response_deadline" class="form-control" min="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="submit" form="notificationForm" class="btn btn-primary">저장</button>
            </div>
        </div>
    </div>
</div>

<script>
function openNotificationModal(notificationId = null) {
    const modal = new bootstrap.Modal(document.getElementById('notificationModal'));
    const form = document.getElementById('notificationForm');
    const modalTitle = document.getElementById('modalTitle');

    // 폼 초기화
    form.reset();
    
    if (notificationId) {
        modalTitle.textContent = '통지 수정';
        form.action = `/admin/notifications/${notificationId}`;
        form.querySelector('input[name="_method"]').value = 'PUT';
        
        // AJAX로 기존 데이터 가져오기
        fetch(`/admin/notifications/${notificationId}/edit`)
            .then(response => response.json())
            .then(data => {
                form.querySelector('select[name="type"]').value = data.type;
                form.querySelector('input[name="title"]').value = data.title;
                form.querySelector('textarea[name="content"]').value = data.content;
                form.querySelector('select[name="notified_user_id"]').value = data.notified_user_id;
                form.querySelector('select[name="via_user_id"]').value = data.via_user_id || '';
                form.querySelector('input[name="response_required"]').checked = data.response_required;
                form.querySelector('input[name="response_deadline"]').value = data.response_deadline;
                toggleDeadlineInput(form.querySelector('input[name="response_required"]'));
            });
    } else {
        modalTitle.textContent = '통지 작성';
        form.action = '{{ route('admin.notifications.store') }}';
        form.querySelector('input[name="_method"]').value = 'POST';
    }

    modal.show();
}

function toggleDeadlineInput(checkbox) {
    const deadlineInput = document.getElementById('deadlineInput');
    deadlineInput.style.display = checkbox.checked ? 'block' : 'none';
    if (!checkbox.checked) {
        document.querySelector('input[name="response_deadline"]').value = '';
    }
}
</script>