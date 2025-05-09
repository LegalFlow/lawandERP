<?php

namespace App\Http\Controllers;

use App\Models\Workhour;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WorkStatusController extends Controller
{
    public function index(Request $request)
    {
        // 날짜 기본값 설정 (오늘)
        $selectedDate = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        // 필터 기본값 설정
        $selectedAffiliation = $request->input('affiliation', '전체');
        $selectedTask = $request->input('task', '전체');
        $selectedStatus = $request->input('status', '전체');

        // CSV 파일에서 내선번호 데이터 읽기
        $phoneNumbers = [];
        $csvPath = resource_path('views/work-status/phone.csv');
        if (file_exists($csvPath)) {
            $handle = fopen($csvPath, 'r');
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                if (isset($data[0]) && isset($data[1])) {
                    $phoneNumbers[trim($data[0])] = trim($data[1]);
                }
            }
            fclose($handle);
        }

        // 데이터 조회
        $query = Workhour::query()
            ->where('work_date', $selectedDate)
            ->when($selectedAffiliation !== '전체', function ($query) use ($selectedAffiliation) {
                return $query->where('affiliation', $selectedAffiliation);
            })
            ->when($selectedTask !== '전체', function ($query) use ($selectedTask) {
                return $query->where('task', $selectedTask);
            })
            ->when($selectedStatus !== '전체', function ($query) use ($selectedStatus) {
                return $query->where('status', $selectedStatus);
            });

        // 정렬 순서: 소속 > 업무 > 이름
        $query->orderByRaw("FIELD(affiliation, '서울', '대전', '부산')")
              ->orderByRaw("FIELD(task, '법률컨설팅팀', '사건관리팀', '지원팀', '개발팀')")
              ->orderBy('member');

        $workData = $query->get();

        // 필터 옵션 데이터
        $affiliations = ['전체', '서울', '대전', '부산'];
        $tasks = ['전체', '법률컨설팅팀', '사건관리팀', '지원팀', '개발팀'];
        $statuses = ['전체', '근무', '재택', '휴무', '연차', '오전반차', '오후반차', '공휴일'];

        return view('work-status.index', compact(
            'workData',
            'selectedDate',
            'selectedAffiliation',
            'selectedTask',
            'selectedStatus',
            'affiliations',
            'tasks',
            'statuses',
            'phoneNumbers'
        ));
    }
}