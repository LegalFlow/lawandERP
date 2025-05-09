@extends('layouts.app')

@section('content')
<div class="container">
    <h1>매출 항목 수정</h1>
    <form action="{{ route('income_entries.update', $incomeEntry->id) }}" method="POST">
        @csrf
        @method('PUT') <!-- PUT 메서드를 사용하여 업데이트 요청으로 설정 -->

        <div class="form-group">
            <label for="deposit_date">입금일자</label>
            <input type="date" name="deposit_date" class="form-control" value="{{ $incomeEntry->deposit_date }}" required>
        </div>

        <div class="form-group">
            <label for="depositor_name">입금자명</label>
            <input type="text" name="depositor_name" class="form-control" value="{{ $incomeEntry->depositor_name }}" required>
        </div>

        <div class="form-group">
            <label for="amount">입금액</label>
            <input type="number" name="amount" class="form-control" value="{{ $incomeEntry->amount }}" required>
        </div>

        <div class="form-group">
            <label for="representative_id">담당자</label>
            <select name="representative_id" class="form-control">
                @foreach ($representatives as $representative)
                    <option value="{{ $representative->id }}" {{ $incomeEntry->representative_id == $representative->id ? 'selected' : '' }}>
                        {{ $representative->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="account_type">계정</label>
            <select name="account_type" class="form-control" required>
                <option value="서비스매출" {{ $incomeEntry->account_type == '서비스매출' ? 'selected' : '' }}>서비스매출</option>
                <option value="송인부" {{ $incomeEntry->account_type == '송인부' ? 'selected' : '' }}>송인부</option>
            </select>
        </div>

        <div class="form-group">
            <label for="memo">메모</label>
            <textarea name="memo" class="form-control">{{ $incomeEntry->memo }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary mt-3">수정</button>
    </form>
</div>
@endsection
