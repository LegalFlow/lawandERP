<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class LegalChatController extends Controller
{
    protected $apiBaseUrl;
    
    public function __construct()
    {
        $this->apiBaseUrl = env('LEGAL_CHATBOT_API_URL', 'http://localhost:8001');
    }
    
    /**
     * 법률 챗봇 인덱스 페이지를 표시합니다.
     */
    public function index()
    {
        return view('legal-chat.index');
    }
    
    /**
     * 사용 가능한 모델 목록을 가져옵니다.
     */
    public function getModels()
    {
        try {
            $response = Http::get($this->apiBaseUrl . '/api/models');
            
            if ($response->failed()) {
                \Log::error('모델 목록 API 응답 실패: ' . $response->body());
                return response()->json(['error' => '모델 목록을 가져오는 중 오류가 발생했습니다.'], 500);
            }
            
            return response()->json($response->json());
        } catch (\Exception $e) {
            \Log::error('모델 목록 요청 오류: ' . $e->getMessage());
            return response()->json(['error' => '모델 목록을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * API 서버에서 대화 목록을 가져옵니다.
     */
    public function getConversations(Request $request)
    {
        try {
            // 사용자 ID를 문자열로 변환하여 전송
            $userId = (string)Auth::id();
            // GET 요청을 통해 user_id를 쿼리 스트링으로 전송
            $response = Http::get($this->apiBaseUrl . '/api/conversations', [
                'user_id' => $userId,
            ]);
            
            if ($response->failed()) {
                \Log::error('대화 목록 API 응답 실패: ' . $response->body());
                return response()->json(['error' => '대화 목록을 가져오는 중 오류가 발생했습니다.', 'details' => $response->body()], 500);
            }
            
            return response()->json($response->json());
        } catch (\Exception $e) {
            \Log::error('대화 목록 요청 오류: ' . $e->getMessage());
            return response()->json(['error' => '대화 목록을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * API 서버에서 특정 대화의 상세 내용을 가져옵니다.
     */
    public function getConversation($id)
    {
        try {
            // 사용자 ID를 문자열로 변환하여 전송
            $userId = (string)Auth::id();
            $response = Http::get($this->apiBaseUrl . '/api/conversations/' . $id, [
                'user_id' => $userId,
            ]);
            
            if ($response->failed()) {
                \Log::error('대화 상세 API 응답 실패: ' . $response->body());
                return response()->json(['error' => '대화 내용을 가져오는 중 오류가 발생했습니다.', 'details' => $response->body()], 500);
            }
            
            return response()->json($response->json());
        } catch (\Exception $e) {
            \Log::error('대화 상세 요청 오류: ' . $e->getMessage());
            return response()->json(['error' => '대화 내용을 가져오는 중 오류가 발생했습니다: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * 사용자 질문을 API 서버로 전송하고 응답을 받아옵니다.
     */
    public function sendMessage(Request $request)
    {
        // GET 메소드로 접근한 경우 안내 메시지 표시
        if ($request->isMethod('get')) {
            return response()->json([
                'error' => '이 엔드포인트는 POST 메소드만 지원합니다. Ajax/Fetch에서 method: "POST"를 사용해 주세요.',
                'success' => false
            ], 405);
        }
        
        try {
            // 사용자 ID를 문자열로 변환하여 전송
            $userId = (string)Auth::id();
            $response = Http::post($this->apiBaseUrl . '/api/chat', [
                'question' => $request->input('question'),
                'conversation_id' => $request->input('conversation_id'),
                'user_id' => $userId,
                'mode' => $request->input('mode', 'GENERAL'),
                'model' => $request->input('model', 'gpt-4.1'),
            ]);
            
            if ($response->failed()) {
                \Log::error('채팅 API 응답 실패: ' . $response->body());
                return response()->json(['error' => '메시지 전송 중 오류가 발생했습니다.', 'details' => $response->body()], 500);
            }
            
            return response()->json($response->json());
        } catch (\Exception $e) {
            \Log::error('메시지 전송 오류: ' . $e->getMessage());
            return response()->json(['error' => '메시지 전송 중 오류가 발생했습니다: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * 스트리밍 방식으로 메시지를 처리합니다.
     */
    public function streamMessage(Request $request)
    {
        if ($request->isMethod('get')) {
            return response()->json([
                'error' => '이 엔드포인트는 POST 메소드만 지원합니다.',
                'success' => false
            ], 405);
        }
        
        try {
            // 사용자 ID를 문자열로 변환하여 전송
            $userId = (string)Auth::id();
            
            // 클라이언트에게 SSE 형식으로 응답
            return response()->stream(function() use ($request, $userId) {
                // API 요청 준비
                $apiResponse = Http::timeout(90)->post($this->apiBaseUrl . '/api/chat', [
                    'question' => $request->input('question'),
                    'conversation_id' => $request->input('conversation_id'),
                    'user_id' => $userId,
                    'mode' => $request->input('mode', 'GENERAL'),
                    'model' => $request->input('model', 'gpt-4.1'),
                ]);
                
                if ($apiResponse->successful()) {
                    $data = $apiResponse->json();
                    $answer = $data['answer'] ?? '';
                    
                    // 응답을 300자 정도씩 청크로 나누어 전송
                    $chunkSize = 300;
                    $chunks = str_split($answer, $chunkSize);
                    
                    echo "event: start\n";
                    echo "data: " . json_encode(['status' => 'start']) . "\n\n";
                    flush();
                    
                    foreach ($chunks as $index => $chunk) {
                        echo "event: chunk\n";
                        echo "data: " . json_encode([
                            'text' => $chunk,
                            'index' => $index,
                            'isLast' => ($index == count($chunks) - 1)
                        ]) . "\n\n";
                        flush();
                        
                        // 각 청크 사이에 약간의 지연 추가 (선택 사항)
                        usleep(50000); // 50ms
                    }
                    
                    echo "event: end\n";
                    echo "data: " . json_encode([
                        'status' => 'completed',
                        'sources' => $data['sources'] ?? [],
                        'conversation_id' => $data['conversation_id'] ?? null,
                        'model' => $data['model'] ?? ''
                    ]) . "\n\n";
                    flush();
                } else {
                    // 오류가 발생한 경우
                    \Log::error('API 응답 실패: ' . $apiResponse->body());
                    echo "event: error\n";
                    echo "data: " . json_encode([
                        'error' => '메시지 처리 중 오류가 발생했습니다',
                        'details' => $apiResponse->body()
                    ]) . "\n\n";
                    flush();
                }
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no', // Nginx 버퍼링 비활성화
            ]);
        } catch (\Exception $e) {
            \Log::error('스트리밍 메시지 오류: ' . $e->getMessage());
            return response()->json(['error' => '메시지 처리 중 오류가 발생했습니다: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * 대화를 삭제합니다.
     */
    public function deleteConversation(Request $request, $id)
    {
        try {
            // 사용자 ID를 문자열로 변환하여 전송
            $userId = (string)Auth::id();
            // DELETE 요청의 경우 body를 전송하는 방식이 다릅니다
            $response = Http::delete($this->apiBaseUrl . '/api/conversations/' . $id, [
                'user_id' => $userId,
                'conversation_id' => $id,
            ]);
            
            if ($response->failed()) {
                \Log::error('대화 삭제 API 응답 실패: ' . $response->body());
                return response()->json(['error' => '대화 삭제 중 오류가 발생했습니다.', 'details' => $response->body()], 500);
            }
            
            return response()->json($response->json());
        } catch (\Exception $e) {
            \Log::error('대화 삭제 오류: ' . $e->getMessage());
            return response()->json(['error' => '대화 삭제 중 오류가 발생했습니다: ' . $e->getMessage()], 500);
        }
    }
} 