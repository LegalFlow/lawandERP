@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">회원정보 수정</div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">이름</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                   name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" autofocus>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">이메일</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                                   name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <!-- 주민등록번호 -->
                        <div class="mb-3">
                            <label class="form-label">주민등록번호</label>
                            <div class="d-flex">
                                <input id="resident_id_front" type="text" class="form-control @error('resident_id_front') is-invalid @enderror" 
                                       name="resident_id_front" value="{{ old('resident_id_front', $user->resident_id_front) }}" required maxlength="6" 
                                       placeholder="앞 6자리" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <span class="mx-2 align-self-center">-</span>
                                <input id="resident_id_back" type="password" class="form-control @error('resident_id_back') is-invalid @enderror" 
                                       name="resident_id_back" value="{{ old('resident_id_back', $user->resident_id_back) }}" required maxlength="7" 
                                       placeholder="뒤 7자리" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            </div>
                            @error('resident_id_front')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            @error('resident_id_back')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <!-- 은행 및 계좌정보 -->
                        <div class="mb-3">
                            <label for="bank" class="form-label">은행명</label>
                            <select id="bank" class="form-control @error('bank') is-invalid @enderror" name="bank" required>
                                <option value="">은행 선택</option>
                                <option value="신한" {{ old('bank', $user->bank) == '신한' ? 'selected' : '' }}>신한</option>
                                <option value="경남" {{ old('bank', $user->bank) == '경남' ? 'selected' : '' }}>경남</option>
                                <option value="광주" {{ old('bank', $user->bank) == '광주' ? 'selected' : '' }}>광주</option>
                                <option value="국민" {{ old('bank', $user->bank) == '국민' ? 'selected' : '' }}>국민</option>
                                <option value="기업" {{ old('bank', $user->bank) == '기업' ? 'selected' : '' }}>기업</option>
                                <option value="농협" {{ old('bank', $user->bank) == '농협' ? 'selected' : '' }}>농협</option>
                                <option value="도이치" {{ old('bank', $user->bank) == '도이치' ? 'selected' : '' }}>도이치</option>
                                <option value="부산" {{ old('bank', $user->bank) == '부산' ? 'selected' : '' }}>부산</option>
                                <option value="산업" {{ old('bank', $user->bank) == '산업' ? 'selected' : '' }}>산업</option>
                                <option value="상호저축" {{ old('bank', $user->bank) == '상호저축' ? 'selected' : '' }}>상호저축</option>
                                <option value="새마을" {{ old('bank', $user->bank) == '새마을' ? 'selected' : '' }}>새마을</option>
                                <option value="수협" {{ old('bank', $user->bank) == '수협' ? 'selected' : '' }}>수협</option>
                                <option value="신협" {{ old('bank', $user->bank) == '신협' ? 'selected' : '' }}>신협</option>
                                <option value="씨티" {{ old('bank', $user->bank) == '씨티' ? 'selected' : '' }}>씨티</option>
                                <option value="아이엠뱅크(대구)" {{ old('bank', $user->bank) == '아이엠뱅크(대구)' ? 'selected' : '' }}>아이엠뱅크(대구)</option>
                                <option value="외환" {{ old('bank', $user->bank) == '외환' ? 'selected' : '' }}>외환</option>
                                <option value="우리" {{ old('bank', $user->bank) == '우리' ? 'selected' : '' }}>우리</option>
                                <option value="우체국" {{ old('bank', $user->bank) == '우체국' ? 'selected' : '' }}>우체국</option>
                                <option value="전북" {{ old('bank', $user->bank) == '전북' ? 'selected' : '' }}>전북</option>
                                <option value="제주" {{ old('bank', $user->bank) == '제주' ? 'selected' : '' }}>제주</option>
                                <option value="지역농축협" {{ old('bank', $user->bank) == '지역농축협' ? 'selected' : '' }}>지역농축협</option>
                                <option value="토스뱅크" {{ old('bank', $user->bank) == '토스뱅크' ? 'selected' : '' }}>토스뱅크</option>
                                <option value="하나" {{ old('bank', $user->bank) == '하나' ? 'selected' : '' }}>하나</option>
                                <option value="케이뱅크" {{ old('bank', $user->bank) == '케이뱅크' ? 'selected' : '' }}>케이뱅크</option>
                                <option value="카카오뱅크" {{ old('bank', $user->bank) == '카카오뱅크' ? 'selected' : '' }}>카카오뱅크</option>
                                <option value="공상" {{ old('bank', $user->bank) == '공상' ? 'selected' : '' }}>공상</option>
                                <option value="BNP파리바" {{ old('bank', $user->bank) == 'BNP파리바' ? 'selected' : '' }}>BNP파리바</option>
                                <option value="JP모간" {{ old('bank', $user->bank) == 'JP모간' ? 'selected' : '' }}>JP모간</option>
                                <option value="BOA" {{ old('bank', $user->bank) == 'BOA' ? 'selected' : '' }}>BOA</option>
                                <option value="HSBC" {{ old('bank', $user->bank) == 'HSBC' ? 'selected' : '' }}>HSBC</option>
                                <option value="SC제일은행" {{ old('bank', $user->bank) == 'SC제일은행' ? 'selected' : '' }}>SC제일은행</option>
                                <option value="산림조합중앙회" {{ old('bank', $user->bank) == '산림조합중앙회' ? 'selected' : '' }}>산림조합중앙회</option>
                                <option value="중국건설" {{ old('bank', $user->bank) == '중국건설' ? 'selected' : '' }}>중국건설</option>
                                <option value="구조흥" {{ old('bank', $user->bank) == '구조흥' ? 'selected' : '' }}>구조흥</option>
                                <option value="구신한" {{ old('bank', $user->bank) == '구신한' ? 'selected' : '' }}>구신한</option>
                                <option value="지방세입" {{ old('bank', $user->bank) == '지방세입' ? 'selected' : '' }}>지방세입</option>
                                <option value="국고금" {{ old('bank', $user->bank) == '국고금' ? 'selected' : '' }}>국고금</option>
                                <option value="유안타증권" {{ old('bank', $user->bank) == '유안타증권' ? 'selected' : '' }}>유안타증권</option>
                                <option value="KB증권" {{ old('bank', $user->bank) == 'KB증권' ? 'selected' : '' }}>KB증권</option>
                                <option value="미래에셋증권(230)" {{ old('bank', $user->bank) == '미래에셋증권(230)' ? 'selected' : '' }}>미래에셋증권(230)</option>
                                <option value="미래에셋증권(238)" {{ old('bank', $user->bank) == '미래에셋증권(238)' ? 'selected' : '' }}>미래에셋증권(238)</option>
                                <option value="삼성증권" {{ old('bank', $user->bank) == '삼성증권' ? 'selected' : '' }}>삼성증권</option>
                                <option value="IBK투자증권" {{ old('bank', $user->bank) == 'IBK투자증권' ? 'selected' : '' }}>IBK투자증권</option>
                                <option value="한국투자증권" {{ old('bank', $user->bank) == '한국투자증권' ? 'selected' : '' }}>한국투자증권</option>
                                <option value="NH투자증권" {{ old('bank', $user->bank) == 'NH투자증권' ? 'selected' : '' }}>NH투자증권</option>
                                <option value="아이엠증권" {{ old('bank', $user->bank) == '아이엠증권' ? 'selected' : '' }}>아이엠증권</option>
                                <option value="현대차증권" {{ old('bank', $user->bank) == '현대차증권' ? 'selected' : '' }}>현대차증권</option>
                                <option value="에스케이증권" {{ old('bank', $user->bank) == '에스케이증권' ? 'selected' : '' }}>에스케이증권</option>
                                <option value="한화증권" {{ old('bank', $user->bank) == '한화증권' ? 'selected' : '' }}>한화증권</option>
                                <option value="하나증권" {{ old('bank', $user->bank) == '하나증권' ? 'selected' : '' }}>하나증권</option>
                                <option value="신한투자증권" {{ old('bank', $user->bank) == '신한투자증권' ? 'selected' : '' }}>신한투자증권</option>
                                <option value="메리츠종합금융증권" {{ old('bank', $user->bank) == '메리츠종합금융증권' ? 'selected' : '' }}>메리츠종합금융증권</option>
                                <option value="유진투자증권" {{ old('bank', $user->bank) == '유진투자증권' ? 'selected' : '' }}>유진투자증권</option>
                                <option value="신영증권" {{ old('bank', $user->bank) == '신영증권' ? 'selected' : '' }}>신영증권</option>
                                <option value="교보증권" {{ old('bank', $user->bank) == '교보증권' ? 'selected' : '' }}>교보증권</option>
                                <option value="대신증권" {{ old('bank', $user->bank) == '대신증권' ? 'selected' : '' }}>대신증권</option>
                                <option value="동부증권" {{ old('bank', $user->bank) == '동부증권' ? 'selected' : '' }}>동부증권</option>
                                <option value="부국증권" {{ old('bank', $user->bank) == '부국증권' ? 'selected' : '' }}>부국증권</option>
                                <option value="LS증권" {{ old('bank', $user->bank) == 'LS증권' ? 'selected' : '' }}>LS증권</option>
                                <option value="솔로몬투자증권" {{ old('bank', $user->bank) == '솔로몬투자증권' ? 'selected' : '' }}>솔로몬투자증권</option>
                                <option value="케이프투자증권" {{ old('bank', $user->bank) == '케이프투자증권' ? 'selected' : '' }}>케이프투자증권</option>
                                <option value="키움증권" {{ old('bank', $user->bank) == '키움증권' ? 'selected' : '' }}>키움증권</option>
                                <option value="BNK투자증권" {{ old('bank', $user->bank) == 'BNK투자증권' ? 'selected' : '' }}>BNK투자증권</option>
                                <option value="우리투자증권" {{ old('bank', $user->bank) == '우리투자증권' ? 'selected' : '' }}>우리투자증권</option>
                                <option value="다올투자증권" {{ old('bank', $user->bank) == '다올투자증권' ? 'selected' : '' }}>다올투자증권</option>
                                <option value="카카오페이증권" {{ old('bank', $user->bank) == '카카오페이증권' ? 'selected' : '' }}>카카오페이증권</option>
                                <option value="상상인증권" {{ old('bank', $user->bank) == '상상인증권' ? 'selected' : '' }}>상상인증권</option>
                                <option value="토스증권" {{ old('bank', $user->bank) == '토스증권' ? 'selected' : '' }}>토스증권</option>
                            </select>
                            @error('bank')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="account_number" class="form-label">계좌번호</label>
                            <input id="account_number" type="text" class="form-control @error('account_number') is-invalid @enderror" 
                                   name="account_number" value="{{ old('account_number', $user->account_number) }}" required 
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            @error('account_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <!-- 휴대전화번호 -->
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">휴대전화번호</label>
                            <input id="phone_number" type="text" class="form-control @error('phone_number') is-invalid @enderror" 
                                   name="phone_number" value="{{ old('phone_number', $user->phone_number) }}" required placeholder="01012345678" 
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            @error('phone_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <!-- 주소 -->
                        <div class="mb-3">
                            <label for="postal_code" class="form-label">우편번호</label>
                            <div class="input-group">
                                <input id="postal_code" type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                                       name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" required readonly>
                                <button type="button" class="btn btn-secondary" onclick="searchAddress()">주소 검색</button>
                            </div>
                            @error('postal_code')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="address_main" class="form-label">기본주소</label>
                            <input id="address_main" type="text" class="form-control @error('address_main') is-invalid @enderror" 
                                   name="address_main" value="{{ old('address_main', $user->address_main) }}" required readonly>
                            @error('address_main')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="address_detail" class="form-label">상세주소</label>
                            <input id="address_detail" type="text" class="form-control @error('address_detail') is-invalid @enderror" 
                                   name="address_detail" value="{{ old('address_detail', $user->address_detail) }}" required>
                            @error('address_detail')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <!-- 입사일자 -->
                        <div class="mb-3">
                            <label for="join_date" class="form-label">입사일자</label>
                            <input id="join_date" type="date" class="form-control @error('join_date') is-invalid @enderror" 
                                   name="join_date" value="{{ old('join_date', $user->join_date ? $user->join_date->format('Y-m-d') : '') }}" required>
                            @error('join_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <!-- 기존 첨부 파일 목록 -->
                        <div class="mb-3">
                            <label class="form-label">첨부된 파일</label>
                            @if($user->documents->isEmpty())
                                <p class="text-muted">첨부된 파일이 없습니다.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>파일명</th>
                                                <th>파일 유형</th>
                                                <th>파일 크기</th>
                                                <th>업로드 일시</th>
                                                <th>관리</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($user->documents as $document)
                                            <tr>
                                                <td>{{ $document->original_filename }}</td>
                                                <td>{{ strtoupper($document->file_type) }}</td>
                                                <td>{{ number_format($document->file_size / 1024, 2) }} KB</td>
                                                <td>{{ $document->created_at->format('Y-m-d H:i') }}</td>
                                                <td>
                                                    <form action="{{ route('profile.document.delete', $document) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('정말로 이 파일을 삭제하시겠습니까?')">
                                                            삭제
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <!-- 파일첨부 -->
                        <div class="mb-3">
                            <label for="documents" class="form-label">추가 서류 첨부 (주민등록등본, 신분증, 통장사본, 졸업증명서, 성적증명서 등)</label>
                            <input id="documents" type="file" class="form-control @error('documents') is-invalid @enderror @error('documents.*') is-invalid @enderror" 
                                   name="documents[]" multiple accept=".pdf,.png,.jpg,.jpeg">
                            <div class="form-text">PDF, PNG, JPEG 파일만 가능합니다. 최대 10개 파일, 각 파일 10MB 이하.</div>
                            @error('documents')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            @error('documents.*')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <hr>

                        <!-- 비밀번호 변경 섹션 -->
                        <h5 class="mb-3">비밀번호 변경 (변경을 원하는 경우에만 입력)</h5>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">현재 비밀번호</label>
                            <input id="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                   name="current_password" autocomplete="current-password">
                            @error('current_password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">새 비밀번호</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                                   name="password" autocomplete="new-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password-confirm" class="form-label">새 비밀번호 확인</label>
                            <input id="password-confirm" type="password" class="form-control" 
                                   name="password_confirmation" autocomplete="new-password">
                        </div>

                        <div class="mb-0">
                            <button type="submit" class="btn btn-primary">
                                정보 수정
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
    function searchAddress() {
        new daum.Postcode({
            oncomplete: function(data) {
                document.getElementById('postal_code').value = data.zonecode;
                document.getElementById('address_main').value = data.address;
                document.getElementById('address_detail').focus();
            }
        }).open();
    }

    // 파일 업로드 제한 (10개, 각 10MB)
    document.getElementById('documents').addEventListener('change', function() {
        const maxFiles = 10;
        const maxSize = 10 * 1024 * 1024; // 10MB
        const files = this.files;
        
        if (files.length > maxFiles) {
            alert('최대 ' + maxFiles + '개의 파일만 업로드할 수 있습니다.');
            this.value = '';
            return;
        }
        
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > maxSize) {
                alert('파일 크기는 10MB를 초과할 수 없습니다.');
                this.value = '';
                return;
            }
        }
    });
</script>
@endpush
@endsection 