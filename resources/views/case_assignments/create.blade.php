@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">사건배당 신규등록</h5>
                </div>

                <div class="card-body">
                    <form id="createForm" method="POST" action="{{ route('case-assignments.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">사건번호</label>
                                    <input type="text" class="form-control" name="case_idx" 
                                           value="{{ $newCaseIdx }}" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="case_type">사건분야 <span class="text-danger">*</span></label>
                                    <select class="form-select" name="case_type" id="case_type" required>
                                        <option value="1" selected>개인회생</option>
                                        <option value="2">개인파산</option>
                                        <option value="3">기타</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">고객명 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="client_name" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">지역</label>
                                    <input type="text" class="form-control" name="living_place">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">상담자</label>
                                    <input type="text" class="form-control" name="consultant">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="case_state">진행현황 <span class="text-danger">*</span></label>
                                    <select class="form-select" name="case_state" id="case_state" required>
                                        <option value="">선택하세요</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">담당자</label>
                                    <input type="text" class="form-control" name="case_manager" value="담당없음">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">비고</label>
                                    <textarea class="form-control" name="notes" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">등록</button>
                            <a href="{{ route('case-assignments.index') }}" class="btn btn-secondary">취소</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
// 상태 데이터 정의
const REVIVAL_STATES = {
    5: '상담대기',
    10: '상담완료',
    11: '재상담필요',
    15: '계약',
    20: '서류준비',
    21: '부채증명서 발급중',
    22: '부채증명서 발급완료',
    25: '신청서 작성 진행중',
    30: '신청서 제출',
    35: '금지명령',
    40: '보정기간',
    45: '개시결정',
    50: '채권자 집회기일',
    55: '인가결정'
};

const BANKRUPTCY_STATES = {
    5: '상담대기',
    10: '상담완료',
    11: '재상담필요',
    15: '계약',
    20: '서류준비',
    21: '부채증명서 발급중',
    22: '부채증명서 발급완료',
    25: '신청서 작성 진행중',
    30: '신청서 제출',
    40: '보정기간',
    100: '파산선고',
    105: '의견청취기일',
    110: '재산환가 및 배당',
    115: '파산폐지',
    120: '면책결정',
    125: '면책불허가'
};

$(document).ready(function() {
    // 진행현황 옵션 설정 함수
    function setStateOptions(caseTypeValue) {
        console.log('선택된 사건분야 값:', caseTypeValue);
        const stateSelect = document.getElementById('case_state');
        
        // case_type 값에 따른 상태값 설정
        const states = caseTypeValue === '2' ? BANKRUPTCY_STATES : REVIVAL_STATES;
        
        // 기존 옵션 초기화
        stateSelect.innerHTML = '<option value="">선택하세요</option>';
        
        // 새로운 옵션 추가 (value는 숫자 값 유지)
        Object.entries(states).forEach(([value, label]) => {
            const option = new Option(label, value);
            stateSelect.appendChild(option);
        });
    }

    // 초기 진행현황 설정
    const initialCaseType = $('#case_type').val();
    setStateOptions(initialCaseType);

    // 사건분야 변경 시 진행현황 업데이트
    $('#case_type').on('change', function() {
        setStateOptions(this.value);
    });

    // 폼 제출 시
    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!confirm('사건을 등록하시겠습니까?')) {
            return;
        }

        const formData = new FormData(this);
        
        // case_type과 case_state가 숫자 값으로 전송되는지 확인
        console.log('전송 데이터:', {
            case_type: formData.get('case_type'),
            case_state: formData.get('case_state')
        });

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    window.location.href = '{{ route("case-assignments.index") }}';
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                alert('오류가 발생했습니다.');
                console.error(xhr);
            }
        });
    });
});
</script>
@endsection 