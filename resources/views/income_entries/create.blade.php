@extends('layouts.app')

@section('content')
<div class="container">
    <h1>매출 항목 등록</h1>
    <form action="{{ route('income_entries.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="deposit_date">입금일자</label>
            <input type="date" name="deposit_date" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="depositor_name">입금자명</label>
            <input type="text" name="depositor_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="amount">입금액</label>
            <input type="text" name="amount" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="representative_id">담당자</label>
            <select name="representative_id" class="form-control" required>
                @foreach($representatives as $rep)
                    <option value="{{ $rep->id }}">{{ $rep->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="account_type">계정</label>
            <select name="account_type" class="form-control" required>
                <option value="서비스매출">서비스매출</option>
                <option value="송인부">송인부</option>
            </select>
        </div>
        <div class="form-group">
            <label for="memo">메모</label>
            <textarea name="memo" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-primary mt-3">저장</button>
    </form>
</div>
@endsection

<script>
document.querySelector('input[name="amount"]').addEventListener('input', function() {
    this.value = this.value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
});
</script>
