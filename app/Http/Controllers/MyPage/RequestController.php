<?php

namespace App\Http\Controllers\MyPage;

use App\Http\Controllers\Controller;
use App\Models\Request;
use App\Models\RequestFile;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PDF;

class RequestController extends Controller
{
    // 신청서 목록 조회
    public function index()
    {
        $requests = Request::where('user_id', Auth::id())
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);

        return view('mypage.requests.index', compact('requests'));
    }

    // 신청서 작성 폼
    public function create()
    {
        // 신청서 종류 목록
        $requestTypes = [
            '사직서',
            '임신으로 인한 단축근무 신청서',
            '출산휴가 신청서',
            '육아휴직 신청서',
            '무급휴가 신청서',
            '무급휴직 신청서',
            '재직증명서 신청서',
            '병가 신청서',
            '병가휴직 신청서',
            '연차선사용신청서',
            '직장 내 괴롭힘 신고',
            '직장 내 성희롱 신고',
            '업무 재배치 신청서',
            '사무소 이동 신청서',
            '외부교육 및 세미나 참가 신청 및 확인서',
            '경조사 지원 신청서',
            '예비군 및 민방위 참가 신청서',
            '기타'
        ];

        return view('mypage.requests.create', compact('requestTypes'));
    }

    // 신청서 저장
    public function store(HttpRequest $request)
    {
        // 유효성 검사
        $validator = Validator::make($request->all(), [
            'request_type' => 'required|string|max:50',
            'date_type' => 'required|in:선택없음,기간선택,특정일선택',
            'start_date' => 'nullable|date|required_if:date_type,기간선택',
            'end_date' => 'nullable|date|required_if:date_type,기간선택|after_or_equal:start_date',
            'specific_date' => 'nullable|date|required_if:date_type,특정일선택',
            'content' => 'required|string',
            'files.*' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:10240', // 10MB 제한
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 신청서 생성
        $newRequest = Request::create([
            'request_number' => Request::generateRequestNumber(),
            'user_id' => Auth::id(),
            'request_type' => $request->request_type,
            'date_type' => $request->date_type,
            'start_date' => $request->date_type === '기간선택' ? $request->start_date : null,
            'end_date' => $request->date_type === '기간선택' ? $request->end_date : null,
            'specific_date' => $request->date_type === '특정일선택' ? $request->specific_date : null,
            'content' => $request->content,
            'status' => '승인대기'
        ]);

        // 파일 업로드
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $totalSize = 0;
            $count = 0;
            
            foreach ($files as $file) {
                $totalSize += $file->getSize();
                $count++;
                
                // 최대 10개 파일, 총 10MB 제한
                if ($count > 10 || $totalSize > 10 * 1024 * 1024) {
                    break;
                }
                
                $originalName = $file->getClientOriginalName();
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('request_pdf', $filename);
                
                RequestFile::create([
                    'request_id' => $newRequest->id,
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'file_size' => $file->getSize(),
                    'is_admin_file' => false
                ]);
            }
        }

        return redirect()->route('mypage.requests.index')
                         ->with('success', '신청서가 성공적으로 제출되었습니다.');
    }

    // 신청서 상세 조회
    public function show(Request $request)
    {
        // 권한 확인 (본인의 신청서만 조회 가능)
        if ($request->user_id !== Auth::id()) {
            abort(403);
        }

        // 신청서 종류 목록
        $requestTypes = [
            '사직서',
            '임신으로 인한 단축근무 신청서',
            '출산휴가 신청서',
            '육아휴직 신청서',
            '무급휴가 신청서',
            '무급휴직 신청서',
            '재직증명서 신청서',
            '병가 신청서',
            '병가휴직 신청서',
            '연차선사용신청서',
            '직장 내 괴롭힘 신고',
            '직장 내 성희롱 신고',
            '업무 재배치 신청서',
            '사무소 이동 신청서',
            '외부교육 및 세미나 참가 신청 및 확인서',
            '경조사 지원 신청서',
            '예비군 및 민방위 참가 신청서',
            '기타'
        ];

        return view('mypage.requests.show', compact('request', 'requestTypes'));
    }

    // 신청서 수정
    public function update(HttpRequest $httpRequest, Request $request)
    {
        // 권한 확인 (본인의 신청서만 수정 가능)
        if ($request->user_id !== Auth::id()) {
            abort(403);
        }

        // 승인대기 상태인 경우만 수정 가능
        if ($request->status !== '승인대기') {
            return back()->with('error', '이미 처리된 신청서는 수정할 수 없습니다.');
        }

        // 유효성 검사
        $validator = Validator::make($httpRequest->all(), [
            'request_type' => 'required|string|max:50',
            'date_type' => 'required|in:선택없음,기간선택,특정일선택',
            'start_date' => 'nullable|date|required_if:date_type,기간선택',
            'end_date' => 'nullable|date|required_if:date_type,기간선택|after_or_equal:start_date',
            'specific_date' => 'nullable|date|required_if:date_type,특정일선택',
            'content' => 'required|string',
            'files.*' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:10240', // 10MB 제한
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 신청서 업데이트
        $request->update([
            'request_type' => $httpRequest->request_type,
            'date_type' => $httpRequest->date_type,
            'start_date' => $httpRequest->date_type === '기간선택' ? $httpRequest->start_date : null,
            'end_date' => $httpRequest->date_type === '기간선택' ? $httpRequest->end_date : null,
            'specific_date' => $httpRequest->date_type === '특정일선택' ? $httpRequest->specific_date : null,
            'content' => $httpRequest->content,
        ]);

        // 파일 업로드
        if ($httpRequest->hasFile('files')) {
            $files = $httpRequest->file('files');
            $existingFiles = $request->files()->where('is_admin_file', false)->count();
            
            $totalSize = 0;
            $count = 0;
            
            foreach ($files as $file) {
                // 최대 10개 파일 제한 확인
                if ($existingFiles + $count >= 10) {
                    break;
                }
                
                $totalSize += $file->getSize();
                $count++;
                
                // 총 10MB 제한 확인
                if ($totalSize > 10 * 1024 * 1024) {
                    break;
                }
                
                $originalName = $file->getClientOriginalName();
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('request_pdf', $filename);
                
                RequestFile::create([
                    'request_id' => $request->id,
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'file_size' => $file->getSize(),
                    'is_admin_file' => false
                ]);
            }
        }

        return redirect()->route('mypage.requests.show', $request)
                         ->with('success', '신청서가 성공적으로 수정되었습니다.');
    }

    // 신청서 삭제
    public function destroy(Request $request)
    {
        // 권한 확인 (본인의 신청서만 삭제 가능)
        if ($request->user_id !== Auth::id()) {
            abort(403);
        }

        // 승인대기 상태인 경우만 삭제 가능
        if ($request->status !== '승인대기') {
            return back()->with('error', '이미 처리된 신청서는 삭제할 수 없습니다.');
        }

        // 파일 삭제
        foreach ($request->files as $file) {
            if (Storage::exists($file->file_path)) {
                Storage::delete($file->file_path);
            }
        }

        // 신청서 삭제
        $request->delete();

        return redirect()->route('mypage.requests.index')
                         ->with('success', '신청서가 성공적으로 삭제되었습니다.');
    }

    // 파일 다운로드
    public function downloadFile(RequestFile $file)
    {
        // 권한 확인 (본인의 신청서 파일만 다운로드 가능)
        if ($file->request->user_id !== Auth::id()) {
            abort(403);
        }

        if (!Storage::exists($file->file_path)) {
            abort(404, '파일을 찾을 수 없습니다.');
        }

        return Storage::download($file->file_path, $file->original_name);
    }

    // 재직증명서 PDF 다운로드
    public function downloadCertificate(Request $request, HttpRequest $httpRequest)
    {
        // 권한 확인 (본인의 신청서만 다운로드 가능)
        if ($request->user_id !== Auth::id()) {
            abort(403);
        }

        // 승인완료 상태인 경우만 다운로드 가능
        if ($request->status !== '승인완료' || $request->request_type !== '재직증명서 신청서') {
            return back()->with('error', '승인완료된 재직증명서만 다운로드할 수 있습니다.');
        }

        $user = $request->user;
        $member = $user->member;
        
        if (!$member) {
            return back()->with('error', '직원 정보를 찾을 수 없습니다.');
        }
        
        // 주민등록번호 마스킹 여부
        $maskResidentId = $httpRequest->has('mask_resident_id');
        
        // 직인 날인 여부
        $useStamp = $httpRequest->has('use_stamp');
        
        // 휴대폰 번호 포맷팅
        $phoneNumber = $user->phone_number;
        if (strlen($phoneNumber) === 11) {
            $phoneNumber = substr($phoneNumber, 0, 3) . '-' . substr($phoneNumber, 3, 4) . '-' . substr($phoneNumber, 7);
        }
        
        // 주민등록번호 포맷팅 및 마스킹
        $residentIdFront = $user->resident_id_front;
        $residentIdBack = $user->resident_id_back;
        
        if ($maskResidentId && $residentIdBack) {
            $maskedResidentIdBack = substr($residentIdBack, 0, 1) . str_repeat('*', strlen($residentIdBack) - 1);
            $residentId = $residentIdFront . '-' . $maskedResidentIdBack;
        } else {
            $residentId = $residentIdFront . '-' . $residentIdBack;
        }
        
        // 회사 정보 설정
        $companyInfo = [
            '회사명' => '',
            '사업자번호' => '',
            '주소' => '',
        ];
        
        switch ($member->affiliation) {
            case '서울':
                $companyInfo['회사명'] = '법무법인 로앤';
                $companyInfo['사업자번호'] = '783-86-00865';
                $companyInfo['주소'] = '서울특별시 강남구 논현로87길 25, HB타워 3층';
                $affiliation = '법무법인 로앤 서울 사무소';
                break;
            case '대전':
                $companyInfo['회사명'] = '법무법인 로앤 대전 분사무소';
                $companyInfo['사업자번호'] = '372-85-00799';
                $companyInfo['주소'] = '대전광역시 서구 둔산중로 78번길 26 민석타워 14층';
                $affiliation = '법무법인 로앤 대전 사무소';
                break;
            case '부산':
                $companyInfo['회사명'] = '법무법인 로앤 부산 분사무소';
                $companyInfo['사업자번호'] = '608-85-37517';
                $companyInfo['주소'] = '부산광역시 연제구 법원로 38 로펌빌딩 401호';
                $affiliation = '법무법인 로앤 부산 사무소';
                break;
            default:
                $affiliation = '법무법인 로앤 ' . $member->affiliation . ' 사무소';
                $companyInfo['회사명'] = '법무법인 로앤';
                $companyInfo['사업자번호'] = '783-86-00865';
                $companyInfo['주소'] = '서울특별시 강남구 논현로87길 25, HB타워 3층';
        }
        
        $data = [
            'user' => $user,
            'member' => $member,
            'request' => $request,
            'phoneNumber' => $phoneNumber,
            'residentId' => $residentId,
            'affiliation' => $affiliation,
            'companyInfo' => $companyInfo,
            'useStamp' => $useStamp,
        ];
        
        $pdf = PDF::loadView('mypage.requests.certificate', $data);
        
        return $pdf->download('재직증명서_' . $user->name . '_' . now()->format('Ymd') . '.pdf');
    }
} 