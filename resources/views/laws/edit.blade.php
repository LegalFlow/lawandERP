@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4"></h1>
        <div>
            <a href="{{ route('laws.show', $law) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>돌아가기
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('laws.update', $law) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="title" class="form-label">제목 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" 
                               value="{{ old('title', $law->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="registration_date" class="form-label">등록일 <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('registration_date') is-invalid @enderror" 
                               id="registration_date" name="registration_date" 
                               value="{{ old('registration_date', $law->registration_date->format('Y-m-d')) }}" required>
                        @error('registration_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="enforcement_date" class="form-label">시행일 <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('enforcement_date') is-invalid @enderror" 
                               id="enforcement_date" name="enforcement_date" 
                               value="{{ old('enforcement_date', $law->enforcement_date->format('Y-m-d')) }}" required>
                        @error('enforcement_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="abolition_date" class="form-label">폐기일</label>
                        <input type="date" class="form-control @error('abolition_date') is-invalid @enderror" 
                               id="abolition_date" name="abolition_date" 
                               value="{{ old('abolition_date', $law->abolition_date ? $law->abolition_date->format('Y-m-d') : '') }}">
                        @error('abolition_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">시행여부 <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" name="status" required>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" 
                                    {{ old('status', $law->status) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="content" class="form-label">내용 <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('content') is-invalid @enderror" 
                                  id="content" name="content" rows="10" required>{{ old('content', $law->content) }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>저장
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-label {
    font-weight: 500;
}
.text-danger {
    font-weight: bold;
}
</style>
@endsection