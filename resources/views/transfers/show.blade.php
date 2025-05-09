@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"></h3>
                    <div class="card-tools">
                        @if($transfer->payment_status !== '납부완료')
                            <button type="button" class="btn btn-primary" onclick="enableEdit()">수정</button>
                        @endif
                        <button type="button" class="btn btn-primary" onclick="enableFileUpload()">파일첨부</button>
                        <button type="button" class="btn btn-danger" onclick="deleteTransfer()">삭제</button>
                        <a href="{{ route('transfers.index') }}" class="btn btn-secondary">목록</a>
                    </div>
                </div>
                <div class="card-body">
                    <form id="transferForm" method="POST" action="{{ route('transfers.update', $transfer->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <!-- 상태 정보 -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="status-box">
                                    <label class="mb-2">승인여부</label>
                                    <select class="form-control form-control-lg" name="approval_status" disabled>
                                        <option value="승인대기" {{ $transfer->approval_status === '승인대기' ? 'selected' : '' }}>
                                            승인대기
                                        </option>
                                        <option value="승인완료" {{ $transfer->approval_status === '승인완료' ? 'selected' : '' }}>
                                            승인완료
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="status-box">
                                    <label class="mb-2">납부여부</label>
                                    <select class="form-control form-control-lg" name="payment_status" {{ $transfer->payment_status === '납부완료' ? 'disabled' : '' }}>
                                        <option value="납부대기" {{ $transfer->payment_status === '납부대기' ? 'selected' : '' }}>
                                            납부대기
                                        </option>
                                        <option value="납부완료" {{ $transfer->payment_status === '납부완료' ? 'selected' : '' }}>
                                            납부완료
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="status-box">
                                    <label class="mb-2">납부완료일</label>
                                    <input type="date" class="form-control form-control-lg" name="payment_completed_at" 
                                           value="{{ $transfer->payment_completed_at ? \Carbon\Carbon::parse($transfer->payment_completed_at)->format('Y-m-d') : '' }}" 
                                           disabled>
                                </div>
                            </div>
                        </div>

                        <!-- 기본 정보 -->
                        <div class="row mb-4">
                            <div class="col">
                                <div class="form-group mb-3">
                                    <label class="mb-2">납부유형</label>
                                    <select class="form-control" name="payment_type" disabled>
                                        <option value="계좌이체" {{ $transfer->payment_type === '계좌이체' ? 'selected' : '' }}>계좌이체</option>
                                        <option value="서면납부" {{ $transfer->payment_type === '서면납부' ? 'selected' : '' }}>서면납부</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group mb-3">
                                    <label class="mb-2">납부대상</label>
                                    <select class="form-control" name="payment_target" disabled>
                                        @foreach(['신건접수', '민사예납', '송달료환급', '환불', '과오납', '즉시항고', '추완항고', '금지명령', '중지명령', '소송구조', '집행정지', '압류중지', '기타'] as $target)
                                            <option value="{{ $target }}" {{ $transfer->payment_target === $target ? 'selected' : '' }}>{{ $target }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group mb-3">
                                    <label class="mb-2">고객명</label>
                                    <input type="text" class="form-control" name="client_name" value="{{ $transfer->client_name }}" disabled>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group mb-3">
                                    <label class="mb-2">관할법원</label>
                                    <input type="text" class="form-control" name="court_name" value="{{ $transfer->court_name }}" disabled>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group mb-3">
                                    <label class="mb-2">사건번호</label>
                                    <input type="text" class="form-control" name="case_number" value="{{ $transfer->case_number }}" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col">
                                <div class="form-group mb-3">
                                    <label class="mb-2">계약자</label>
                                    <select class="form-control" name="consultant" disabled>
                                        <option value="">선택하세요</option>
                                        @foreach($members as $member)
                                            <option value="{{ $member->name }}" {{ $transfer->consultant === $member->name ? 'selected' : '' }}>
                                                {{ $member->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group mb-3">
                                    <label class="mb-2">담당자</label>
                                    <select class="form-control" name="manager" disabled>
                                        <option value="">선택하세요</option>
                                        @foreach($members as $member)
                                            <option value="{{ $member->name }}" {{ $transfer->manager === $member->name ? 'selected' : '' }}>
                                                {{ $member->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group mb-3">
                                    <label class="mb-2">은행</label>
                                    <select class="form-control" name="bank" disabled>
                                        <option value="신한" {{ $transfer->bank === '신한' ? 'selected' : '' }}>신한</option>
                                        <option value="경남" {{ $transfer->bank === '경남' ? 'selected' : '' }}>경남</option>
                                        <option value="광주" {{ $transfer->bank === '광주' ? 'selected' : '' }}>광주</option>
                                        <option value="국민" {{ $transfer->bank === '국민' ? 'selected' : '' }}>국민</option>
                                        <option value="기업" {{ $transfer->bank === '기업' ? 'selected' : '' }}>기업</option>
                                        <option value="농협" {{ $transfer->bank === '농협' ? 'selected' : '' }}>농협</option>
                                        <option value="도이치" {{ $transfer->bank === '도이치' ? 'selected' : '' }}>도이치</option>
                                        <option value="부산" {{ $transfer->bank === '부산' ? 'selected' : '' }}>부산</option>
                                        <option value="산업" {{ $transfer->bank === '산업' ? 'selected' : '' }}>산업</option>
                                        <option value="상호저축" {{ $transfer->bank === '상호저축' ? 'selected' : '' }}>상호저축</option>
                                        <option value="새마을" {{ $transfer->bank === '새마을' ? 'selected' : '' }}>새마을</option>
                                        <option value="수협" {{ $transfer->bank === '수협' ? 'selected' : '' }}>수협</option>
                                        <option value="신협" {{ $transfer->bank === '신협' ? 'selected' : '' }}>신협</option>
                                        <option value="씨티" {{ $transfer->bank === '씨티' ? 'selected' : '' }}>씨티</option>
                                        <option value="아이엠뱅크(대구)" {{ $transfer->bank === '아이엠뱅크(대구)' ? 'selected' : '' }}>아이엠뱅크(대구)</option>
                                        <option value="외환" {{ $transfer->bank === '외환' ? 'selected' : '' }}>외환</option>
                                        <option value="우리" {{ $transfer->bank === '우리' ? 'selected' : '' }}>우리</option>
                                        <option value="우체국" {{ $transfer->bank === '우체국' ? 'selected' : '' }}>우체국</option>
                                        <option value="전북" {{ $transfer->bank === '전북' ? 'selected' : '' }}>전북</option>
                                        <option value="제주" {{ $transfer->bank === '제주' ? 'selected' : '' }}>제주</option>
                                        <option value="지역농축협" {{ $transfer->bank === '지역농축협' ? 'selected' : '' }}>지역농축협</option>
                                        <option value="토스뱅크" {{ $transfer->bank === '토스뱅크' ? 'selected' : '' }}>토스뱅크</option>
                                        <option value="하나" {{ $transfer->bank === '하나' ? 'selected' : '' }}>하나</option>
                                        <option value="케이뱅크" {{ $transfer->bank === '케이뱅크' ? 'selected' : '' }}>케이뱅크</option>
                                        <option value="카카오뱅크" {{ $transfer->bank === '카카오뱅크' ? 'selected' : '' }}>카카오뱅크</option>
                                        <option value="공상" {{ $transfer->bank === '공상' ? 'selected' : '' }}>공상</option>
                                        <option value="BNP파리바" {{ $transfer->bank === 'BNP파리바' ? 'selected' : '' }}>BNP파리바</option>
                                        <option value="JP모간" {{ $transfer->bank === 'JP모간' ? 'selected' : '' }}>JP모간</option>
                                        <option value="BOA" {{ $transfer->bank === 'BOA' ? 'selected' : '' }}>BOA</option>
                                        <option value="HSBC" {{ $transfer->bank === 'HSBC' ? 'selected' : '' }}>HSBC</option>
                                        <option value="SC제일은행" {{ $transfer->bank === 'SC제일은행' ? 'selected' : '' }}>SC제일은행</option>
                                        <option value="산림조합중앙회" {{ $transfer->bank === '산림조합중앙회' ? 'selected' : '' }}>산림조합중앙회</option>
                                        <option value="중국건설" {{ $transfer->bank === '중국건설' ? 'selected' : '' }}>중국건설</option>
                                        <option value="구조흥" {{ $transfer->bank === '구조흥' ? 'selected' : '' }}>구조흥</option>
                                        <option value="구신한" {{ $transfer->bank === '구신한' ? 'selected' : '' }}>구신한</option>
                                        <option value="지방세입" {{ $transfer->bank === '지방세입' ? 'selected' : '' }}>지방세입</option>
                                        <option value="국고금" {{ $transfer->bank === '국고금' ? 'selected' : '' }}>국고금</option>
                                        <option value="유안타증권" {{ $transfer->bank === '유안타증권' ? 'selected' : '' }}>유안타증권</option>
                                        <option value="KB증권" {{ $transfer->bank === 'KB증권' ? 'selected' : '' }}>KB증권</option>
                                        <option value="미래에셋증권(230)" {{ $transfer->bank === '미래에셋증권(230)' ? 'selected' : '' }}>미래에셋증권(230)</option>
                                        <option value="미래에셋증권(238)" {{ $transfer->bank === '미래에셋증권(238)' ? 'selected' : '' }}>미래에셋증권(238)</option>
                                        <option value="삼성증권" {{ $transfer->bank === '삼성증권' ? 'selected' : '' }}>삼성증권</option>
                                        <option value="IBK투자증권" {{ $transfer->bank === 'IBK투자증권' ? 'selected' : '' }}>IBK투자증권</option>
                                        <option value="한국투자증권" {{ $transfer->bank === '한국투자증권' ? 'selected' : '' }}>한국투자증권</option>
                                        <option value="NH투자증권" {{ $transfer->bank === 'NH투자증권' ? 'selected' : '' }}>NH투자증권</option>
                                        <option value="아이엠증권" {{ $transfer->bank === '아이엠증권' ? 'selected' : '' }}>아이엠증권</option>
                                        <option value="현대차증권" {{ $transfer->bank === '현대차증권' ? 'selected' : '' }}>현대차증권</option>
                                        <option value="에스케이증권" {{ $transfer->bank === '에스케이증권' ? 'selected' : '' }}>에스케이증권</option>
                                        <option value="한화증권" {{ $transfer->bank === '한화증권' ? 'selected' : '' }}>한화증권</option>
                                        <option value="하나증권" {{ $transfer->bank === '하나증권' ? 'selected' : '' }}>하나증권</option>
                                        <option value="신한투자증권" {{ $transfer->bank === '신한투자증권' ? 'selected' : '' }}>신한투자증권</option>
                                        <option value="메리츠종합금융증권" {{ $transfer->bank === '메리츠종합금융증권' ? 'selected' : '' }}>메리츠종합금융증권</option>
                                        <option value="유진투자증권" {{ $transfer->bank === '유진투자증권' ? 'selected' : '' }}>유진투자증권</option>
                                        <option value="신영증권" {{ $transfer->bank === '신영증권' ? 'selected' : '' }}>신영증권</option>
                                        <option value="교보증권" {{ $transfer->bank === '교보증권' ? 'selected' : '' }}>교보증권</option>
                                        <option value="대신증권" {{ $transfer->bank === '대신증권' ? 'selected' : '' }}>대신증권</option>
                                        <option value="동부증권" {{ $transfer->bank === '동부증권' ? 'selected' : '' }}>동부증권</option>
                                        <option value="부국증권" {{ $transfer->bank === '부국증권' ? 'selected' : '' }}>부국증권</option>
                                        <option value="LS증권" {{ $transfer->bank === 'LS증권' ? 'selected' : '' }}>LS증권</option>
                                        <option value="솔로몬투자증권" {{ $transfer->bank === '솔로몬투자증권' ? 'selected' : '' }}>솔로몬투자증권</option>
                                        <option value="케이프투자증권" {{ $transfer->bank === '케이프투자증권' ? 'selected' : '' }}>케이프투자증권</option>
                                        <option value="키움증권" {{ $transfer->bank === '키움증권' ? 'selected' : '' }}>키움증권</option>
                                        <option value="BNK투자증권" {{ $transfer->bank === 'BNK투자증권' ? 'selected' : '' }}>BNK투자증권</option>
                                        <option value="우리투자증권" {{ $transfer->bank === '우리투자증권' ? 'selected' : '' }}>우리투자증권</option>
                                        <option value="다올투자증권" {{ $transfer->bank === '다올투자증권' ? 'selected' : '' }}>다올투자증권</option>
                                        <option value="카카오페이증권" {{ $transfer->bank === '카카오페이증권' ? 'selected' : '' }}>카카오페이증권</option>
                                        <option value="상상인증권" {{ $transfer->bank === '상상인증권' ? 'selected' : '' }}>상상인증권</option>
                                        <option value="토스증권" {{ $transfer->bank === '토스증권' ? 'selected' : '' }}>토스증권</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group mb-3">
                                    <label class="mb-2">계좌번호</label>
                                    <input type="text" class="form-control" name="virtual_account" value="{{ $transfer->virtual_account }}" disabled>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group mb-3">
                                    <label class="mb-2">납부금액</label>
                                    <input type="text" class="form-control" name="payment_amount" value="{{ number_format($transfer->payment_amount) }}" disabled>
                                </div>
                            </div>
                        </div>

                        <!-- 메모 -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="form-group mb-3">
                                    <label class="mb-2">메모</label>
                                    <textarea class="form-control" name="memo" rows="4" disabled>{{ $transfer->memo }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- 첨부파일 -->
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="mb-2">첨부파일 (최대 10개까지 가능)</label>
                                    <div class="custom-file-upload mb-2" style="display: none;">
                                        <input type="file" 
                                               class="form-control" 
                                               name="files[]" 
                                               id="fileInput" 
                                               multiple 
                                               accept=".pdf">
                                        <small class="text-muted ml-2">선택된 파일: <span id="selectedFileCount">0</span>개</small>
                                    </div>
                                    <div id="selectedFiles" class="mb-2 text-muted"></div>
                                    <div class="file-list p-3 border rounded">
                                        @foreach($transfer->files()->where('del_flag', 0)->get() as $file)
                                            <div class="file-item mb-2">
                                                <a href="{{ route('transfers.files.download', $file->id) }}" class="mr-2">
                                                    <i class="fas fa-file-pdf"></i> {{ $file->original_name }}
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteFile({{ $file->id }})">삭제</button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" style="display: none;">저장</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.status-box {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 5px;
    border: 1px solid #dee2e6;
}

.status-box select, .status-box input {
    font-weight: bold;
}

.status-box select[value="승인대기"], 
.status-box select[value="납부대기"] {
    color: #ffc107;
}

.status-box select[value="승인완료"], 
.status-box select[value="납부완료"] {
    color: #28a745;
}

.form-group label {
    font-weight: bold;
    color: #495057;
}

.file-list {
    background-color: #f8f9fa;
}

.custom-file-upload {
    display: inline-block;
}

#selectedFiles {
    max-height: 100px;
    overflow-y: auto;
}
</style>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function enableEdit() {
    // 기존 필드들 활성화
    $('input, select, textarea').not('[name="files[]"]').prop('disabled', false);
    
    // 납부완료 상태일 경우 납부여부 필드는 비활성화 유지
    if ('{{ $transfer->payment_status }}' === '납부완료') {
        $('select[name="payment_status"]').prop('disabled', true);
    }
    
    $('#transferForm button[type="submit"]').show();
}

function enableFileUpload() {
    // 파일 업로드 관련 필드만 활성화
    $('.custom-file-upload').show();
    $('#transferForm button[type="submit"]').show();
}

// 폼 제출 이벤트 처리
$('#transferForm').on('submit', function(e) {
    e.preventDefault();
    
    let formData = new FormData(this);
    
    // 파일 업로드 모드인 경우에만 기존 값들을 FormData에 추가
    if ($('.custom-file-upload').is(':visible')) {
        formData.set('payment_status', '{{ $transfer->payment_status }}');
        formData.set('payment_type', '{{ $transfer->payment_type }}');
        formData.set('payment_target', '{{ $transfer->payment_target }}');
        formData.set('client_name', '{{ $transfer->client_name }}');
        formData.set('court_name', '{{ $transfer->court_name }}');
        formData.set('case_number', '{{ $transfer->case_number }}');
        formData.set('consultant', '{{ $transfer->consultant }}');
        formData.set('manager', '{{ $transfer->manager }}');
        formData.set('bank', '{{ $transfer->bank }}');
        formData.set('virtual_account', '{{ $transfer->virtual_account }}');
        formData.set('payment_amount', '{{ $transfer->payment_amount }}');
        formData.set('memo', {!! json_encode($transfer->memo) !!});
        formData.set('approval_status', '{{ $transfer->approval_status }}');
        formData.set('payment_completed_at', '{{ $transfer->payment_completed_at }}');
    } else {
        // 일반 수정 모드일 때는 payment_amount의 콤마만 제거
        let paymentAmount = formData.get('payment_amount');
        if (paymentAmount) {
            formData.set('payment_amount', paymentAmount.replace(/,/g, ''));
        }
    }
    
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
            console.log('Success:', response);
            alert('저장되었습니다.');
            location.reload();
        },
        error: function(xhr, status, error) {
            console.log('Error:', xhr.responseText);
            let errorMessage = '저장 중 오류가 발생했습니다.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            alert(errorMessage);
        }
    });
});

// 파일 선택 시 이벤트 처리
document.getElementById('fileInput').addEventListener('change', function(e) {
    const files = e.target.files;
    const fileCount = files.length;
    
    if (fileCount > 10) {
        alert('최대 10개까지만 업로드 가능합니다.');
        this.value = '';
        return;
    }
    
    document.getElementById('selectedFileCount').textContent = fileCount;
    
    const selectedFilesDiv = document.getElementById('selectedFiles');
    selectedFilesDiv.innerHTML = '';
    
    Array.from(files).forEach(file => {
        selectedFilesDiv.innerHTML += `<div><i class="fas fa-file-pdf"></i> ${file.name}</div>`;
    });
});

// 파일 삭제 함수
function deleteFile(fileId) {
    if (confirm('파일을 삭제하시겠습니까?')) {
        $.ajax({
            url: `/transfers/files/${fileId}`,
            type: 'POST',
            data: {
                _method: 'DELETE',
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $(`button[onclick="deleteFile(${fileId})"]`).closest('.file-item').remove();
                } else {
                    alert('파일 삭제에 실패했습니다.');
                }
            },
            error: function(xhr) {
                let errorMessage = '파일 삭제 중 오류가 발생했습니다.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                alert(errorMessage);
            }
        });
    }
}

// 이체요청 삭제 함수
function deleteTransfer() {
    if (confirm('정말로 이 이체요청을 삭제하시겠습니까?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("transfers.destroy", $transfer->id) }}';
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = document.querySelector('meta[name="csrf-token"]').content;
        
        form.appendChild(methodInput);
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// 금액 입력 필드의 콤마 처리
document.querySelector('input[name="payment_amount"]').addEventListener('change', function() {
    this.value = this.value.replace(/,/g, '');
});
</script>
@endpush
@endsection

