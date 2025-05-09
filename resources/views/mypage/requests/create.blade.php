@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">신청서 작성</h5>
                    <a href="{{ route('mypage.requests.index') }}" class="btn btn-sm btn-secondary">목록으로</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('mypage.requests.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="request_number" class="form-label">신청서 번호</label>
                            <input type="text" class="form-control" value="자동 생성됩니다" disabled>
                            <small class="text-muted">신청서 번호는 자동으로 생성됩니다.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="request_type" class="form-label">신청종류 <span class="text-danger">*</span></label>
                            <select name="request_type" id="request_type" class="form-select @error('request_type') is-invalid @enderror" required>
                                <option value="">신청종류 선택</option>
                                @foreach($requestTypes as $type)
                                    <option value="{{ $type }}" {{ old('request_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('request_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="date_type" class="form-label">일자선택 <span class="text-danger">*</span></label>
                            <select name="date_type" id="date_type" class="form-select @error('date_type') is-invalid @enderror" required>
                                <option value="선택없음" {{ old('date_type') == '선택없음' ? 'selected' : '' }}>선택없음</option>
                                <option value="기간선택" {{ old('date_type') == '기간선택' ? 'selected' : '' }}>기간선택</option>
                                <option value="특정일선택" {{ old('date_type') == '특정일선택' ? 'selected' : '' }}>특정일선택</option>
                            </select>
                            @error('date_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div id="date_range_fields" class="mb-3 {{ old('date_type') == '기간선택' ? '' : 'd-none' }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">시작일 <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}">
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">종료일 <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}">
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div id="specific_date_field" class="mb-3 {{ old('date_type') == '특정일선택' ? '' : 'd-none' }}">
                            <label for="specific_date" class="form-label">특정일 <span class="text-danger">*</span></label>
                            <input type="date" name="specific_date" id="specific_date" class="form-control @error('specific_date') is-invalid @enderror" value="{{ old('specific_date') }}">
                            @error('specific_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">신청 내용 <span class="text-danger">*</span></label>
                            <textarea name="content" id="content" rows="5" class="form-control @error('content') is-invalid @enderror" required>{{ old('content') }}</textarea>
                            <small class="form-text text-muted">신청과 관련된 상세 내용을 작성해주세요.</small>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="files" class="form-label">파일첨부 (PDF, PNG, JPEG)</label>
                            <input type="file" name="files[]" id="files" class="form-control @error('files.*') is-invalid @enderror" multiple accept=".pdf,.png,.jpg,.jpeg">
                            <div class="form-text">최대 10개 파일, 총 10MB까지 첨부 가능합니다.</div>
                            @error('files.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3 text-center">
                            <button type="submit" class="btn btn-primary">신청서 제출</button>
                            <a href="{{ route('mypage.requests.index') }}" class="btn btn-outline-secondary ms-2">취소</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // 일자 선택 타입에 따라 필드 표시 조정
    document.addEventListener('DOMContentLoaded', function() {
        const dateTypeSelect = document.getElementById('date_type');
        const dateRangeFields = document.getElementById('date_range_fields');
        const specificDateField = document.getElementById('specific_date_field');
        const requestTypeSelect = document.getElementById('request_type');
        const contentTextarea = document.getElementById('content');
        
        // 신청 종류 변경 시 내용 템플릿 설정 및 일자 타입 자동 선택
        requestTypeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            const currentContent = contentTextarea.value.trim();
            let templateContent = '';
            let preferredDateType = null;
            
            // 기존 내용이 있고, 사용자가 직접 수정한 내용인 경우 템플릿을 적용하지 않음
            if (currentContent && !confirm('신청 내용을 템플릿으로 변경하시겠습니까?')) {
                return;
            }
            
            // 신청종류에 따른 내용 템플릿 설정
            switch(selectedType) {
                case '사직서':
                    templateContent = `일자선택을 '특정일'로 변경한 후 일자를 근로자가 원하는 마지막 근무일의 익일로 선택해 주세요.
단, 근로기준법에 의거 신청일로부터 원하는 퇴사일자가 1월 이내인 경우에는 실제 퇴사일자는 변경될 수 있습니다. 
그리고 퇴사사유를 반드시 명시해야 합니다. 다만, 개인적인 사유인 경우에는 '자진퇴사'라고만 적시해도 됩니다.`;
                    preferredDateType = '특정일선택';
                    break;
                case '임신으로 인한 단축근무 신청서':
                    templateContent = `일자선택을 '기간선택'으로 변경하여 시작일과 종료일을 반드시 선택해야 합니다. 
그리고 소명자료(임신확인서, 출산예정일을 확인할 수 있는 서류 등)를 반드시 첨부해야 합니다. 
승인완료 후에는 근무현황의 근무일정에서 근무시간을 직접 수정하면 됩니다.`;
                    preferredDateType = '기간선택';
                    break;
                case '출산휴가 신청서':
                    templateContent = `일자선택을 '기간선택'으로 변경하여 시작일과 종료일을 반드시 선택해야 합니다. 
그리고 소명자료(출산예정일을 확인할 수 있는 서류 등)를 반드시 첨부해야 합니다. 
출산휴가 후 바로 육아휴직을 사용하는 경우에는 육아휴직 신청서도 함께 신청해 주세요.`;
                    preferredDateType = '기간선택';
                    break;
                case '육아휴직 신청서':
                    templateContent = `일자선택을 '기간선택'으로 변경하여 시작일과 종료일을 반드시 선택해야 합니다.
그리고 소명자료(출산(예정)일을 확인할 수 있는 서류 등)를 반드시 첨부해야 합니다.`;
                    preferredDateType = '기간선택';
                    break;
                case '무급휴가 신청서':
                    templateContent = `무급휴가는 일반적으로 받아들여지지 않으므로 무급휴가를 사용해야 하는 이유를 구체적으로 명시하고, 이에 대한 소명자료를 첨부해 주세요.`;
                    break;
                case '무급휴직 신청서':
                    templateContent = `무급휴직은 일반적으로 받아들여지지 않으므로 무급휴가를 사용해야 하는 이유를 구체적으로 명시하고, 이에 대한 소명자료를 첨부해 주세요.`;
                    preferredDateType = '기간선택';
                    break;
                case '재직증명서 신청서':
                    templateContent = `재직증명서의 사용용도는 __________________이고, ____________ 에 제출할 예정입니다.
상기 내용이 사실임을 확인하며, 관련 규정을 준수할 것을 서약합니다.`;
                    break;
                case '병가 신청서':
                    templateContent = `병가관련 기준을 사내행정의 회사내규에서 확인한 후 기간을 선택해 주세요. 
확인할 수 없거나 해석의 여지가 있는 경우에는 임의로 선택해서 제출해 주세요.
변경이 필요한 경우는 반려절차를 통해 수정된 기간을 제시해 드리겠습니다.`;
                    preferredDateType = '기간선택';
                    break;
                case '병가휴직 신청서':
                    templateContent = `병가휴직은 '병가'와는 다르게 무급휴직입니다. 
규정된 병가 외에 요양을 원하는 경우 이에 대한 구체적인 소명자료와 함께 제출해 주세요.`;
                    preferredDateType = '기간선택';
                    break;
                case '연차선사용신청서':
                    templateContent = `이미 연차가 소진되었음에도 불구하고 부득이 연차를 사용해야 하는 이유를 소명자료와 함께 제출해 주세요.
또한 연차 선사용시 그 다음 연차적용기간에서 연차가 공제되며 이로 인해 급여 공제 등의 불이익을 감수한다는 내용을 포함시켜야 합니다.`;
                    break;
                case '직장 내 괴롭힘 신고':
                    templateContent = `가해자를 특정하고, 그 가해자의 행위에 대해 구체적인 소명자료와 함께 제출해 주세요.`;
                    break;
                case '직장 내 성희롱 신고':
                    templateContent = `가해자를 특정하고, 그 가해자의 행위에 대해 구체적인 소명자료와 함께 제출해 주세요.`;
                    break;
                case '업무 재배치 신청서':
                    templateContent = `업무 재배치를 하고싶은 이유에 대해 자세히 소명해 주세요.`;
                    break;
                case '사무소 이동 신청서':
                    templateContent = `사무소를 이동하고 싶은 이유에 대해 자세히 소명해 주세요.`;
                    break;
                case '외부교육 및 세미나 참가 신청 및 확인서':
                    templateContent = `외부교육 및 세미나가 필요한 이유에 대해 소명하고, 참석 후 반드시 이수확인서(또는 증빙할만한 자료)를 제출해 주세요.`;
                    break;
                case '경조사 지원 신청서':
                    templateContent = `사내행정의 회사내규를 확인하여 소명자료와 함께 제출해 주세요.`;
                    break;
                case '예비군 및 민방위 참가 신청서':
                    templateContent = `소명자료와 함께 제출해 주세요. 충성!`;
                    break;
                case '기타':
                    templateContent = `구체적인 내용과 소명자료를 함께 제출해 주세요.`;
                    break;
            }
            
            // 템플릿이 있으면 적용
            if(templateContent) {
                contentTextarea.value = templateContent;
            }
            
            // 선호하는 일자 타입이 있으면 자동 선택
            if(preferredDateType && dateTypeSelect.value !== preferredDateType) {
                dateTypeSelect.value = preferredDateType;
                // 일자 타입 변경 이벤트 트리거
                dateTypeSelect.dispatchEvent(new Event('change'));
            }
        });
        
        dateTypeSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            
            if (selectedValue === '기간선택') {
                dateRangeFields.classList.remove('d-none');
                specificDateField.classList.add('d-none');
                document.getElementById('start_date').setAttribute('required', '');
                document.getElementById('end_date').setAttribute('required', '');
                document.getElementById('specific_date').removeAttribute('required');
            } else if (selectedValue === '특정일선택') {
                dateRangeFields.classList.add('d-none');
                specificDateField.classList.remove('d-none');
                document.getElementById('start_date').removeAttribute('required');
                document.getElementById('end_date').removeAttribute('required');
                document.getElementById('specific_date').setAttribute('required', '');
            } else {
                dateRangeFields.classList.add('d-none');
                specificDateField.classList.add('d-none');
                document.getElementById('start_date').removeAttribute('required');
                document.getElementById('end_date').removeAttribute('required');
                document.getElementById('specific_date').removeAttribute('required');
            }
        });
    });
</script>
@endpush
@endsection 