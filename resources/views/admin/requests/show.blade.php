@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">신청서 처리</h5>
                    <a href="{{ route('admin.requests.index') }}" class="btn btn-sm btn-secondary">목록으로</a>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">신청서 번호</label>
                        <input type="text" class="form-control" value="{{ $request->request_number }}" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">신청일</label>
                        <input type="text" class="form-control" value="{{ $request->created_at->format('Y-m-d H:i') }}" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">작성자</label>
                        <input type="text" class="form-control" value="{{ $request->user->name }}" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">신청종류</label>
                        <input type="text" class="form-control" value="{{ $request->request_type }}" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">일자선택</label>
                        <input type="text" class="form-control" value="{{ $request->date_type }}" readonly>
                    </div>
                    
                    @if($request->date_type === '기간선택')
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">시작일</label>
                                <input type="text" class="form-control" value="{{ $request->start_date->format('Y-m-d') }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">종료일</label>
                                <input type="text" class="form-control" value="{{ $request->end_date->format('Y-m-d') }}" readonly>
                            </div>
                        </div>
                    @elseif($request->date_type === '특정일선택')
                        <div class="mb-3">
                            <label class="form-label">특정일</label>
                            <input type="text" class="form-control" value="{{ $request->specific_date->format('Y-m-d') }}" readonly>
                        </div>
                    @endif
                    
                    <div class="mb-3">
                        <label class="form-label">신청 내용</label>
                        <textarea class="form-control" rows="5" readonly>{{ $request->content }}</textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">현재 상태</label>
                        <div>
                            @if($request->status == '승인대기')
                                <span class="badge bg-warning">승인대기</span>
                            @elseif($request->status == '승인완료')
                                <span class="badge bg-success">승인완료</span>
                            @else
                                <span class="badge bg-danger">반려</span>
                            @endif
                        </div>
                    </div>
                    
                    @if($request->processed_at)
                        <div class="mb-3">
                            <label class="form-label">처리일</label>
                            <input type="text" class="form-control" value="{{ $request->processed_at->format('Y-m-d') }}" readonly>
                        </div>
                        
                        @if($request->processor)
                            <div class="mb-3">
                                <label class="form-label">처리자</label>
                                <input type="text" class="form-control" value="{{ $request->processor->name }}" readonly>
                            </div>
                        @endif
                    @endif
                    
                    <div class="mb-3">
                        <label class="form-label">신청자 첨부파일</label>
                        <div class="list-group">
                            @forelse($request->files->where('is_admin_file', false) as $file)
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.requests.download-file', $file->id) }}" class="text-decoration-none">
                                        <i class="bi bi-file-earmark"></i> {{ $file->original_name }}
                                    </a>
                                    <span class="badge bg-secondary rounded-pill">{{ number_format($file->file_size / 1024, 2) }} KB</span>
                                </div>
                            @empty
                                <div class="list-group-item text-muted">첨부파일이 없습니다.</div>
                            @endforelse
                        </div>
                    </div>
                    
                    @if($request->files->where('is_admin_file', true)->count() > 0)
                        <div class="mb-3">
                            <label class="form-label">관리자 첨부파일</label>
                            <div class="list-group">
                                @foreach($request->files->where('is_admin_file', true) as $file)
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <a href="{{ route('admin.requests.download-file', $file->id) }}" class="text-decoration-none">
                                            <i class="bi bi-file-earmark"></i> {{ $file->original_name }}
                                        </a>
                                        <span class="badge bg-secondary rounded-pill">{{ number_format($file->file_size / 1024, 2) }} KB</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    @if($request->admin_comment)
                        <div class="mb-3">
                            <label class="form-label">관리자 코멘트</label>
                            <textarea class="form-control" rows="3" readonly>{{ $request->admin_comment }}</textarea>
                        </div>
                    @endif
                    
                    @if($request->status === '승인대기')
                        <hr class="my-4">
                        
                        <form action="{{ route('admin.requests.process', $request->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="admin_comment" class="form-label">관리자 코멘트</label>
                                <textarea name="admin_comment" id="admin_comment" class="form-control @error('admin_comment') is-invalid @enderror" rows="3">{{ old('admin_comment') }}</textarea>
                                @error('admin_comment')
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
                            
                            <div class="d-flex justify-content-center gap-3">
                                <button type="submit" name="status" value="승인완료" class="btn btn-success">승인</button>
                                <button type="submit" name="status" value="반려" class="btn btn-danger">반려</button>
                                <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary">취소</a>
                            </div>
                        </form>
                    @else
                        <div class="d-flex justify-content-center mt-4">
                            <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary">목록으로</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 