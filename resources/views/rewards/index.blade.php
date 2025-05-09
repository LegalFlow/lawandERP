@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">보상 및 제재 관리</h3>
                    @if(auth()->user()->is_admin)
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#rewardModal">
                            새로운 보상/제재 등록
                        </button>
                    </div>
                    @endif
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 100px; white-space: nowrap;">등록일자</th>
                                    <th style="width: 120px; white-space: nowrap;">구성원</th>
                                    <th style="width: 100px; white-space: nowrap;">보상/제재</th>
                                    <th>내용</th>
                                    <th style="width: 100px; white-space: nowrap;">등록일자</th>
                                    <th style="width: 60px; text-align: center;">메모</th>
                                    @if(auth()->user()->is_admin)
                                    <th style="width: 120px; text-align: center;">관리</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rewards as $reward)
                                <tr @if($reward->is_auto_generated) class="table-warning" @endif>
                                    <td style="white-space: nowrap;">{{ $reward->created_at->format('Y-m-d') }}</td>
                                    <td style="white-space: nowrap;">{{ $reward->member->name }}</td>
                                    <td style="white-space: nowrap;">{{ $reward->reward_type }}</td>
                                    <td>{{ $reward->content }}</td>
                                    <td style="white-space: nowrap;">{{ $reward->usable_date }}</td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary memo-btn"
                                                onclick="showMemo('{{ $reward->memo }}')"
                                                title="메모 보기">
                                            <i class="fas fa-sticky-note {{ $reward->memo ? 'text-warning' : '' }}"></i>
                                        </button>
                                    </td>
                                    @if(auth()->user()->is_admin)
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-warning"
                                                onclick="editReward({{ $reward->id }})">수정</button>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger"
                                                onclick="deleteReward({{ $reward->id }})">삭제</button>
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">등록된 보상/제재가 없습니다.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    {{ $rewards->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 보상/제재 등록/수정 모달 -->
<div class="modal fade" id="rewardModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">보상/제재 등록</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="rewardForm" method="POST">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>구성원</label>
                        <select name="member_id" class="form-control" required>
                            <option value="">선택하세요</option>
                            @foreach($members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>보상/제재 유형</label>
                        <select name="reward_type" class="form-control" required>
                            <option value="">선택하세요</option>
                            @foreach(App\Models\Reward::getRewardTypes() as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>내용</label>
                        <textarea name="content" class="form-control" required rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>등록일자</label>
                        <input type="date" name="usable_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>메모</label>
                        <textarea name="memo" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 메모 보기 모달 -->
<div class="modal fade" id="memoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">메모 내용</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="memoContent"></p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // 전역 함수로 선언
    window.deleteReward = function(id) {
        if (confirm('정말 삭제하시겠습니까?')) {
            const token = document.querySelector('meta[name="csrf-token"]').content;
            
            fetch(`/rewards/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Delete Error:', error);
                alert('삭제 중 오류가 발생했습니다.');
            });
        }
    }

    $(document).ready(function() {
        // 모달 닫기 버튼 이벤트
        $('.modal .close, .modal .btn-secondary').on('click', function() {
            $(this).closest('.modal').modal('hide');
        });

        // 모달 트리거 버튼 이벤트
        $(document).on('click', '[data-toggle="modal"]', function() {
            var targetModal = $(this).data('target');
            $(targetModal).modal('show');
        });

        // ESC 키로 모달 닫기
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC key
                $('.modal').modal('hide');
            }
        });

        // 모달 외부 클릭시 닫기
        $('.modal').on('click', function(e) {
            if ($(e.target).hasClass('modal')) {
                $(this).modal('hide');
            }
        });

        // 메모 표시
        function showMemo(memo) {
            $('#memoContent').text(memo);
            $('#memoModal').modal('show');
        }

        // 수정 모달 표시
        function editReward(id) {
            $.get(`/rewards/${id}/edit`, function(data) {
                $('#rewardForm').attr('action', `/rewards/${id}`);
                $('#rewardForm input[name="_method"]').val('PUT');
                $('#rewardForm select[name="member_id"]').val(data.member_id);
                $('#rewardForm select[name="reward_type"]').val(data.reward_type);
                $('#rewardForm textarea[name="content"]').val(data.content);
                $('#rewardForm input[name="usable_date"]').val(data.usable_date);
                $('#rewardForm textarea[name="memo"]').val(data.memo);
                $('.modal-title').text('보상/제재 수정');
                $('#rewardModal').modal('show');
            });
        }

        // 신규 등록 모달 초기화
        $('#rewardModal').on('hidden.bs.modal', function() {
            $('#rewardForm').attr('action', '/rewards');
            $('#rewardForm input[name="_method"]').val('POST');
            $('#rewardForm')[0].reset();
            $('.modal-title').text('보상/제재 등록');
        });

        // 전역 함수로 등록
        window.showMemo = showMemo;
        window.editReward = editReward;
        window.deleteReward = deleteReward;
    });
</script>
@endpush
