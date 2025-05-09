from fastapi import FastAPI, HTTPException, Depends, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
import logging
import time
import uuid
import uvicorn
from contextlib import asynccontextmanager

# 내부 모듈 임포트
import config
from retriever import retrieve_relevant_articles
from llm_client import generate_response, generate_general_response, REQUEST_TIMEOUT
from chat_manager import ChatManager
from utils import sanitize_user_input, format_response_for_frontend, logger

# API 타임아웃 설정
API_TIMEOUT = REQUEST_TIMEOUT + 30  # LLM 요청 타임아웃 + 30초 여유

# FastAPI 리퍼 설정 (타임아웃 조정)
@asynccontextmanager
async def lifespan(app: FastAPI):
    # 서버 시작 시 실행
    # lifespan 컨텍스트 매니저를 통해 앱 시작/종료 시 실행할 코드 정의
    logger.info(f"서버 시작: {config.API_HOST}:{config.API_PORT}, 타임아웃: {API_TIMEOUT}초")
    
    # 기본 타임아웃 설정 로깅
    logger.info(f"LLM API 타임아웃: {REQUEST_TIMEOUT}초")
    logger.info(f"FastAPI 서버 타임아웃: {API_TIMEOUT}초")
    
    yield
    
    # 서버 종료 시 실행
    logger.info("서버 종료")

app = FastAPI(title="법무법인 ERP 법률 AI 챗봇 API", lifespan=lifespan)

# CORS 설정
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # 실제 배포시 프론트엔드 도메인으로 제한
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# 데이터 모델
class ChatRequest(BaseModel):
    question: str
    conversation_id: Optional[str] = None
    user_id: str
    mode: Optional[str] = "GENERAL"  # 모드 필드 추가 (기본값: GENERAL)
    model: Optional[str] = "claude-3-5-sonnet"  # 모델 필드 추가 (기본값: Claude)

class ChatResponse(BaseModel):
    answer: str
    sources: List[Dict[str, Any]]
    conversation_id: str
    model: str  # 사용된 모델 정보 반환

class ConversationListRequest(BaseModel):
    user_id: str

class ConversationDeleteRequest(BaseModel):
    user_id: str
    conversation_id: str

# 사용 가능한 모델 정보를 반환하는 API 엔드포인트 추가
@app.get("/api/models")
async def get_available_models():
    return {"models": config.AVAILABLE_MODELS}

# 임시 사용자 인증 (실제로는 ERP 시스템의 인증 사용)
def get_current_user(request: Request):
    # 여기서는 간단히 처리하지만, 실제로는 기존 ERP 인증 시스템과 연동
    auth_header = request.headers.get("Authorization")
    if not auth_header:
        # 개발 환경에서는 더미 사용자 허용
        if config.DEBUG_MODE:
            return {"user_id": "test_user"}
        else:
            raise HTTPException(status_code=401, detail="인증되지 않은 접근")
    
    # 실제 환경에서는 토큰 검증 등의 로직 추가
    return {"user_id": "authenticated_user"}

# 에러 로깅 미들웨어
@app.middleware("http")
async def log_exceptions(request: Request, call_next):
    try:
        return await call_next(request)
    except Exception as e:
        logger.error(f"요청 처리 중 오류 발생: {e}", exc_info=True)
        return JSONResponse(
            status_code=500,
            content={"detail": "서버 내부 오류가 발생했습니다."}
        )

# API 엔드포인트
@app.post("/api/chat", response_model=ChatResponse)
async def chat(request: ChatRequest):
    start_time = time.time()
    
    try:
        # 사용자 입력 정제
        question = sanitize_user_input(request.question)
        
        # 채팅 관리자 초기화
        chat_manager = ChatManager(request.user_id)
        
        # 대화 ID가 없으면 새로 생성
        conversation_id = request.conversation_id
        if not conversation_id:
            conversation_id = chat_manager.create_conversation()
            
        # 사용자 질문 저장
        chat_manager.add_message(conversation_id, "user", question)
        
        # 요청으로부터 모델 추출 (기본값은 Claude)
        model = request.model if request.model in config.AVAILABLE_MODELS else config.LLM_MODEL
        
        # 모드에 따라 다른 처리 적용
        sources = []
        if request.mode == "채무자회생법":
            # 기존 RAG 방식 사용
            relevant_articles = retrieve_relevant_articles(question, config.TOP_K_RESULTS)
            sources = relevant_articles
        else:
            # GENERAL 모드: RAG 없이 직접 LLM 사용
            relevant_articles = []
        
        # 이전 대화 기록 가져오기 (최근 5개 메시지)
        conversation = chat_manager.get_conversation(conversation_id)
        history = []
        if conversation and len(conversation["messages"]) > 0:
            messages = conversation["messages"][-5:]  # 최근 5개 메시지만 사용
            for msg in messages:
                if msg["role"] in ["user", "assistant"]:
                    history.append({"role": msg["role"], "content": msg["content"]})
        
        # 응답 생성 (모드와 모델에 따라 다른 함수 호출)
        if request.mode == "채무자회생법":
            # RAG 기반 응답 생성 (모델 전달)
            answer = generate_response(question, relevant_articles, history, model)
        else:
            # GENERAL 모드: RAG 없이 직접 LLM 응답 생성 (모델 전달)
            answer = generate_general_response(question, history, model)
        
        # 응답 저장 (모델 정보 추가)
        chat_manager.add_message(conversation_id, "assistant", answer, sources=sources, model=model)
        
        # 응답 포맷팅 및 반환
        formatted_sources = []
        if sources:
            formatted_sources = [
                {
                    "law_name": article["law_name"],
                    "article_no": article["article_no"],
                    "article_title": article.get("article_title", ""),
                    "content": article["content"][:200] + "..." if len(article["content"]) > 200 else article["content"],
                    "score": article.get("score", 0)
                }
                for article in sources
            ]
        
        elapsed_time = time.time() - start_time
        logger.info(f"처리 시간: {elapsed_time:.2f}초, 사용 모델: {model}")
        
        return ChatResponse(answer=answer, sources=formatted_sources, conversation_id=conversation_id, model=model)
    
    except Exception as e:
        logger.error(f"채팅 응답 생성 오류: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/conversations")
@app.get("/api/conversations")
async def get_conversations(request: Request, user_id: str = None):
    try:
        # GET 요청 처리
        if request.method == "GET":
            if not user_id:
                raise HTTPException(status_code=400, detail="user_id 파라미터가 필요합니다")
        # POST 요청 처리
        else:
            body = await request.json()
            user_id = body.get("user_id")
            if not user_id:
                raise HTTPException(status_code=400, detail="user_id 필드가 필요합니다")
                
        chat_manager = ChatManager(user_id)
        conversations = chat_manager.get_all_conversations()
        
        # 가공된 대화 목록 반환 (메시지 내용은 제외)
        result = []
        for conv in conversations:
            result.append({
                "id": conv["id"],
                "title": conv["title"],
                "created_at": conv["created_at"],
                "updated_at": conv["updated_at"],
                "message_count": len(conv["messages"])
            })
        
        return {"conversations": result}
    except Exception as e:
        logger.error(f"대화 목록 조회 오류: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/api/conversations/{conversation_id}")
async def get_conversation_detail(conversation_id: str, user_id: str):
    try:
        chat_manager = ChatManager(user_id)
        conversation = chat_manager.get_conversation(conversation_id)
        
        if not conversation:
            raise HTTPException(status_code=404, detail="대화를 찾을 수 없습니다")
            
        return conversation
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"대화 상세 조회 오류: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))

@app.delete("/api/conversations/{conversation_id}")
async def delete_conversation(request: ConversationDeleteRequest):
    try:
        chat_manager = ChatManager(request.user_id)
        success = chat_manager.delete_conversation(request.conversation_id)
        
        if not success:
            raise HTTPException(status_code=404, detail="대화를 찾을 수 없습니다")
            
        return {"success": True}
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"대화 삭제 오류: {e}", exc_info=True)
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/health")
async def health_check():
    return {"status": "healthy"}

if __name__ == "__main__":
    logger.info(f"서버 시작: {config.API_HOST}:{config.API_PORT}, 타임아웃: {API_TIMEOUT}초")
    # uvicorn 서버 실행 시 타임아웃 설정 추가
    uvicorn.run(
        "app:app", 
        host=config.API_HOST, 
        port=config.API_PORT, 
        reload=config.DEBUG_MODE,
        timeout_keep_alive=API_TIMEOUT
    )
