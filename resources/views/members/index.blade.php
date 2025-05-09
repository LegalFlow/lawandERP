@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mt-4 mb-4">구성원</h1>
    <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
        신규등록
    </button>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>No.</th>
                    <th>이름</th>
                    <th>직급</th>
                    <th>업무</th>
                    <th>소속</th>
                    <th>상태</th>
                    <th>8-17</th>
                    <th>9-18</th>
                    <th>10-19</th>
                    <th>9-16</th>
                    <th>휴무</th>
                    <th>재택</th>
                    <th>유연근무</th>
                    <th>차량유지비</th>
                    <th>보육수당</th>
                    <th>연차시작일</th>
                    <th>연차종료일</th>
                    <th>은행</th>
                    <th>계좌번호</th>
                    <th>비고</th>
                    <th>기준금액</th>
                    <th style="width: 100px">관리</th>
                </tr>
            </thead>
            <tbody>
                @foreach($members as $index => $member)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->position }}</td>
                    <td>{{ $member->task }}</td>
                    <td>{{ $member->affiliation }}</td>
                    <td>{{ $member->status }}</td>
                    <td>{{ $member->block_8_17 }}</td>
                    <td>{{ $member->block_9_18 }}</td>
                    <td>{{ $member->block_10_19 }}</td>
                    <td>{{ $member->block_9_16 }}</td>
                    <td>{{ $member->paid_holiday }}</td>
                    <td>{{ $member->house_work }}</td>
                    <td>{{ $member->flexible_working ? '예' : '아니오' }}</td>
                    <td>{{ $member->car_cost }}</td>
                    <td>{{ $member->childcare }}</td>
                    <td>{{ $member->annual_start_period ? $member->annual_start_period->format('Y-m-d') : '' }}</td>
                    <td>{{ $member->annual_end_period ? $member->annual_end_period->format('Y-m-d') : '' }}</td>
                    <td>{{ $member->bank }}</td>
                    <td>{{ $member->account_number }}</td>
                    <td>{{ $member->notes }}</td>
                    <td>{{ number_format($member->standard) }}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button" 
                                    class="btn btn-outline-primary btn-sm" 
                                    onclick="editMember({{ $member->id }})"
                                    title="수정">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form action="{{ route('members.destroy', $member->id) }}" 
                                  method="POST" 
                                  class="d-inline" 
                                  onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="btn btn-outline-danger btn-sm" 
                                        title="삭제">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 신규등록 모달 -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createModalLabel">구성원 신규등록</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createForm" action="{{ route('members.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">이름</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="position" class="form-label">직급</label>
                            <select class="form-control" id="position" name="position" required>
                                <option value="">선택안함</option>
                                <option value="사원">사원</option>
                                <option value="주임">주임</option>
                                <option value="대리">대리</option>
                                <option value="과장">과장</option>
                                <option value="팀장">팀장</option>
                                <option value="실장">실장</option>
                                <option value="변호사">변호사</option>
                                <option value="파트너">파트너</option>
                                <option value="개발자">개발자</option>
                                <option value="대표">대표</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="task" class="form-label">업무</label>
                            <select class="form-control" id="task" name="task" required>
                                <option value="">선택안함</option>
                                <option value="법률컨설팅팀">법률컨설팅팀</option>
                                <option value="사건관리팀">사건관리팀</option>
                                <option value="개발팀">개발팀</option>
                                <option value="지원팀">지원팀</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="affiliation" class="form-label">소속</label>
                            <select class="form-control" id="affiliation" name="affiliation" required>
                                <option value="서울">서울</option>
                                <option value="대전">대전</option>
                                <option value="부산">부산</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">상태</label>
                            <select class="form-control" id="status" name="status">
                                <option value="재직">재직</option>
                                <option value="휴직">휴직</option>
                                <option value="퇴사">퇴사</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="block_8_17" class="form-label">8-17</label>
                            <input type="number" class="form-control" id="block_8_17" name="block_8_17" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="block_9_18" class="form-label">9-18</label>
                            <input type="number" class="form-control" id="block_9_18" name="block_9_18" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="block_10_19" class="form-label">10-19</label>
                            <input type="number" class="form-control" id="block_10_19" name="block_10_19" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="block_9_16" class="form-label">9-16</label>
                            <input type="number" class="form-control" id="block_9_16" name="block_9_16" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="paid_holiday" class="form-label">휴무</label>
                            <input type="number" class="form-control" id="paid_holiday" name="paid_holiday" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="house_work" class="form-label">재택</label>
                            <input type="number" class="form-control" id="house_work" name="house_work" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="flexible_working" class="form-label">유연근무</label>
                            <select class="form-control" id="flexible_working" name="flexible_working">
                                <option value="0">아니오</option>
                                <option value="1">예</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="car_cost" class="form-label">차량유지비</label>
                            <input type="number" class="form-control" id="car_cost" name="car_cost" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="childcare" class="form-label">보육수당</label>
                            <input type="number" class="form-control" id="childcare" name="childcare" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="annual_start_period" class="form-label">연차시작일</label>
                            <input type="date" class="form-control" id="annual_start_period" name="annual_start_period">
                        </div>
                        <div class="mb-3">
                            <label for="annual_end_period" class="form-label">연차종료일</label>
                            <input type="date" class="form-control" id="annual_end_period" name="annual_end_period">
                        </div>
                        <div class="mb-3">
                            <label for="bank" class="form-label">은행</label>
                            <select class="form-control" id="bank" name="bank">
                                <option value="">선택없음</option>
                                <option value="신한">신한</option>
                                <option value="경남">경남</option>
                                <option value="광주">광주</option>
                                <option value="국민">국민</option>
                                <option value="기업">기업</option>
                                <option value="농협">농협</option>
                                <option value="도이치">도이치</option>
                                <option value="부산">부산</option>
                                <option value="산업">산업</option>
                                <option value="상호저축">상호저축</option>
                                <option value="새마을">새마을</option>
                                <option value="수협">수협</option>
                                <option value="신협">신협</option>
                                <option value="씨티">씨티</option>
                                <option value="아이엠뱅크(대구)">아이엠뱅크(대구)</option>
                                <option value="외환">외환</option>
                                <option value="우리">우리</option>
                                <option value="우체국">우체국</option>
                                <option value="전북">전북</option>
                                <option value="제주">제주</option>
                                <option value="지역농축협">지역농축협</option>
                                <option value="토스뱅크">토스뱅크</option>
                                <option value="하나">하나</option>
                                <option value="케이뱅크">케이뱅크</option>
                                <option value="카카오뱅크">카카오뱅크</option>
                                <option value="공상">공상</option>
                                <option value="BNP파리바">BNP파리바</option>
                                <option value="JP모간">JP모간</option>
                                <option value="BOA">BOA</option>
                                <option value="HSBC">HSBC</option>
                                <option value="SC제일은행">SC제일은행</option>
                                <option value="산림조합중앙회">산림조합중앙회</option>
                                <option value="중국건설">중국건설</option>
                                <option value="구조흥">구조흥</option>
                                <option value="구신한">구신한</option>
                                <option value="지방세입">지방세입</option>
                                <option value="국고금">국고금</option>
                                <option value="유안타증권">유안타증권</option>
                                <option value="KB증권">KB증권</option>
                                <option value="미래에셋증권(230)">미래에셋증권(230)</option>
                                <option value="미래에셋증권(238)">미래에셋증권(238)</option>
                                <option value="삼성증권">삼성증권</option>
                                <option value="IBK투자증권">IBK투자증권</option>
                                <option value="한국투자증권">한국투자증권</option>
                                <option value="NH투자증권">NH투자증권</option>
                                <option value="아이엠증권">아이엠증권</option>
                                <option value="현대차증권">현대차증권</option>
                                <option value="에스케이증권">에스케이증권</option>
                                <option value="한화증권">한화증권</option>
                                <option value="하나증권">하나증권</option>
                                <option value="신한투자증권">신한투자증권</option>
                                <option value="메리츠종합금융증권">메리츠종합금융증권</option>
                                <option value="유진투자증권">유진투자증권</option>
                                <option value="신영증권">신영증권</option>
                                <option value="교보증권">교보증권</option>
                                <option value="대신증권">대신증권</option>
                                <option value="동부증권">동부증권</option>
                                <option value="부국증권">부국증권</option>
                                <option value="LS증권">LS증권</option>
                                <option value="솔로몬투자증권">솔로몬투자증권</option>
                                <option value="케이프투자증권">케이프투자증권</option>
                                <option value="키움증권">키움증권</option>
                                <option value="BNK투자증권">BNK투자증권</option>
                                <option value="우리투자증권">우리투자증권</option>
                                <option value="다올투자증권">다올투자증권</option>
                                <option value="카카오페이증권">카카오페이증권</option>
                                <option value="상상인증권">상상인증권</option>
                                <option value="토스증권">토스증권</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="account_number" class="form-label">계좌번호</label>
                            <input type="text" class="form-control" id="account_number" name="account_number">
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">비고</label>
                            <textarea class="form-control" id="notes" name="notes"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="standard" class="form-label">기준금액</label>
                            <input type="number" class="form-control" id="standard" name="standard" value="0" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('createForm').submit()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 수정 모달 -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">구성원 수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">이름</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_position" class="form-label">직급</label>
                            <select class="form-control" id="edit_position" name="position" required>
                                <option value="">선택안함</option>
                                <option value="사원">사원</option>
                                <option value="주임">주임</option>
                                <option value="대리">대리</option>
                                <option value="과장">과장</option>
                                <option value="팀장">팀장</option>
                                <option value="실장">실장</option>
                                <option value="변호사">변호사</option>
                                <option value="파트너">파트너</option>
                                <option value="개발자">개발자</option>
                                <option value="대표">대표</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_task" class="form-label">업무</label>
                            <select class="form-control" id="edit_task" name="task" required>
                                <option value="">선택안함</option>
                                <option value="법률컨설팅팀">법률컨설팅팀</option>
                                <option value="사건관리팀">사건관리팀</option>
                                <option value="개발팀">개발팀</option>
                                <option value="지원팀">지원팀</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_affiliation" class="form-label">소속</label>
                            <select class="form-control" id="edit_affiliation" name="affiliation" required>
                                <option value="서울">서울</option>
                                <option value="대전">대전</option>
                                <option value="부산">부산</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">상태</label>
                            <select class="form-control" id="edit_status" name="status">
                                <option value="재직">재직</option>
                                <option value="휴직">휴직</option>
                                <option value="퇴사">퇴사</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_block_8_17" class="form-label">8-17</label>
                            <input type="number" class="form-control" id="edit_block_8_17" name="block_8_17">
                        </div>
                        <div class="mb-3">
                            <label for="edit_block_9_18" class="form-label">9-18</label>
                            <input type="number" class="form-control" id="edit_block_9_18" name="block_9_18">
                        </div>
                        <div class="mb-3">
                            <label for="edit_block_10_19" class="form-label">10-19</label>
                            <input type="number" class="form-control" id="edit_block_10_19" name="block_10_19">
                        </div>
                        <div class="mb-3">
                            <label for="edit_block_9_16" class="form-label">9-16</label>
                            <input type="number" class="form-control" id="edit_block_9_16" name="block_9_16">
                        </div>
                        <div class="mb-3">
                            <label for="edit_paid_holiday" class="form-label">휴무</label>
                            <input type="number" class="form-control" id="edit_paid_holiday" name="paid_holiday">
                        </div>
                        <div class="mb-3">
                            <label for="edit_house_work" class="form-label">재택</label>
                            <input type="number" class="form-control" id="edit_house_work" name="house_work">
                        </div>
                        <div class="mb-3">
                            <label for="edit_flexible_working" class="form-label">유연근무</label>
                            <select class="form-control" id="edit_flexible_working" name="flexible_working">
                                <option value="0">아니오</option>
                                <option value="1">예</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_car_cost" class="form-label">차량유지비</label>
                            <input type="number" class="form-control" id="edit_car_cost" name="car_cost">
                        </div>
                        <div class="mb-3">
                            <label for="edit_childcare" class="form-label">보육수당</label>
                            <input type="number" class="form-control" id="edit_childcare" name="childcare">
                        </div>
                        <div class="mb-3">
                            <label for="edit_annual_start_period" class="form-label">연차시작일</label>
                            <input type="date" class="form-control" id="edit_annual_start_period" name="annual_start_period">
                        </div>
                        <div class="mb-3">
                            <label for="edit_annual_end_period" class="form-label">연차종료일</label>
                            <input type="date" class="form-control" id="edit_annual_end_period" name="annual_end_period">
                        </div>
                        <div class="mb-3">
                            <label for="edit_bank" class="form-label">은행</label>
                            <select class="form-control" id="edit_bank" name="bank">
                                <option value="">선택없음</option>
                                <option value="신한">신한</option>
                                <option value="경남">경남</option>
                                <option value="광주">광주</option>
                                <option value="국민">국민</option>
                                <option value="기업">기업</option>
                                <option value="농협">농협</option>
                                <option value="도이치">도이치</option>
                                <option value="부산">부산</option>
                                <option value="산업">산업</option>
                                <option value="상호저축">상호저축</option>
                                <option value="새마을">새마을</option>
                                <option value="수협">수협</option>
                                <option value="신협">신협</option>
                                <option value="씨티">씨티</option>
                                <option value="아이엠뱅크(대구)">아이엠뱅크(대구)</option>
                                <option value="외환">외환</option>
                                <option value="우리">우리</option>
                                <option value="우체국">우체국</option>
                                <option value="전북">전북</option>
                                <option value="제주">제주</option>
                                <option value="지역농축협">지역농축협</option>
                                <option value="토스뱅크">토스뱅크</option>
                                <option value="하나">하나</option>
                                <option value="케이뱅크">케이뱅크</option>
                                <option value="카카오뱅크">카카오뱅크</option>
                                <option value="공상">공상</option>
                                <option value="BNP파리바">BNP파리바</option>
                                <option value="JP모간">JP모간</option>
                                <option value="BOA">BOA</option>
                                <option value="HSBC">HSBC</option>
                                <option value="SC제일은행">SC제일은행</option>
                                <option value="산림조합중앙회">산림조합중앙회</option>
                                <option value="중국건설">중국건설</option>
                                <option value="구조흥">구조흥</option>
                                <option value="구신한">구신한</option>
                                <option value="지방세입">지방세입</option>
                                <option value="국고금">국고금</option>
                                <option value="유안타증권">유안타증권</option>
                                <option value="KB증권">KB증권</option>
                                <option value="미래에셋증권(230)">미래에셋증권(230)</option>
                                <option value="미래에셋증권(238)">미래에셋증권(238)</option>
                                <option value="삼성증권">삼성증권</option>
                                <option value="IBK투자증권">IBK투자증권</option>
                                <option value="한국투자증권">한국투자증권</option>
                                <option value="NH투자증권">NH투자증권</option>
                                <option value="아이엠증권">아이엠증권</option>
                                <option value="현대차증권">현대차증권</option>
                                <option value="에스케이증권">에스케이증권</option>
                                <option value="한화증권">한화증권</option>
                                <option value="하나증권">하나증권</option>
                                <option value="신한투자증권">신한투자증권</option>
                                <option value="메리츠종합금융증권">메리츠종합금융증권</option>
                                <option value="유진투자증권">유진투자증권</option>
                                <option value="신영증권">신영증권</option>
                                <option value="교보증권">교보증권</option>
                                <option value="대신증권">대신증권</option>
                                <option value="동부증권">동부증권</option>
                                <option value="부국증권">부국증권</option>
                                <option value="LS증권">LS증권</option>
                                <option value="솔로몬투자증권">솔로몬투자증권</option>
                                <option value="케이프투자증권">케이프투자증권</option>
                                <option value="키움증권">키움증권</option>
                                <option value="BNK투자증권">BNK투자증권</option>
                                <option value="우리투자증권">우리투자증권</option>
                                <option value="다올투자증권">다올투자증권</option>
                                <option value="카카오페이증권">카카오페이증권</option>
                                <option value="상상인증권">상상인증권</option>
                                <option value="토스증권">토스증권</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_account_number" class="form-label">계좌번호</label>
                            <input type="text" class="form-control" id="edit_account_number" name="account_number">
                        </div>
                        <div class="mb-3">
                            <label for="edit_notes" class="form-label">비고</label>
                            <textarea class="form-control" id="edit_notes" name="notes"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_standard" class="form-label">기준금액</label>
                            <input type="number" class="form-control" id="edit_standard" name="standard" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('editForm').submit()">저장</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
    }
    .btn-group-sm i {
        font-size: 1rem;
    }
    .table td {
        vertical-align: middle;
    }
    .btn-group > .btn {
        margin-right: 2px;
    }
    </style>

    <script>
    function editMember(id) {
        fetch(`/members/${id}/edit`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_name').value = data.name;
                document.getElementById('edit_position').value = data.position;
                document.getElementById('edit_task').value = data.task;
                document.getElementById('edit_affiliation').value = data.affiliation;
                document.getElementById('edit_status').value = data.status ?? '';
                document.getElementById('edit_bank').value = data.bank ?? '';
                document.getElementById('edit_account_number').value = data.account_number ?? '';
                document.getElementById('edit_notes').value = data.notes ?? '';
                document.getElementById('edit_block_8_17').value = data.block_8_17 ?? 0;
                document.getElementById('edit_block_9_18').value = data.block_9_18 ?? 0;
                document.getElementById('edit_block_10_19').value = data.block_10_19 ?? 0;
                document.getElementById('edit_block_9_16').value = data.block_9_16 ?? 0;
                document.getElementById('edit_paid_holiday').value = data.paid_holiday ?? 0;
                document.getElementById('edit_house_work').value = data.house_work ?? 0;
                document.getElementById('edit_flexible_working').value = data.flexible_working ? '1' : '0';
                document.getElementById('edit_car_cost').value = data.car_cost ?? 0;
                document.getElementById('edit_childcare').value = data.childcare ?? 0;
                document.getElementById('edit_annual_start_period').value = data.annual_start_period;
                document.getElementById('edit_annual_end_period').value = data.annual_end_period;
                document.getElementById('edit_bank').value = data.bank ?? '';
                document.getElementById('edit_account_number').value = data.account_number ?? '';
                document.getElementById('edit_notes').value = data.notes ?? '';
                document.getElementById('edit_standard').value = data.standard ?? 0;

                const form = document.getElementById('editForm');
                form.action = `/members/${id}`;

                new bootstrap.Modal(document.getElementById('editModal')).show();
            })
            .catch(error => console.error('Error:', error));
    }
    </script>
</div>
@endsection
