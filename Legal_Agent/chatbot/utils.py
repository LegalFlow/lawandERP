import os
import logging
from typing import Dict, Any
from openai import OpenAI

# 설정 모듈 가져오기
import config

# 로깅 설정
logging.basicConfig(
    level=getattr(logging, config.LOG_LEVEL),
    format="%(asctime)s [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler(os.path.join(config.LOG_DIR, "app.log")),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# OpenAI 클라이언트 초기화
client = OpenAI(api_key=config.OPENAI_API_KEY)

def get_embedding(text: str) -> list:
    """
    텍스트의 임베딩 벡터를 생성합니다.
    
    Args:
        text: 임베딩할 텍스트
        
    Returns:
        생성된 임베딩 벡터
    """
    try:
        response = client.embeddings.create(
            input=[text],
            model=config.EMBEDDING_MODEL
        )
        return response.data[0].embedding
    except Exception as e:
        logger.error(f"임베딩 생성 오류: {e}")
        raise

def format_response_for_frontend(answer: str, sources: list) -> Dict[str, Any]:
    """
    프론트엔드에 표시할 응답을 포맷팅합니다.
    
    Args:
        answer: LLM 응답
        sources: 참조 법률 조문
    
    Returns:
        포맷팅된 응답
    """
    return {
        "answer": answer,
        "sources": [
            {
                "law_name": source["law_name"],
                "article_no": source["article_no"],
                "article_title": source.get("article_title", ""),
                "content": source["content"][:200] + "..." if len(source["content"]) > 200 else source["content"],
                "score": source.get("score", 0)
            }
            for source in sources
        ]
    }

def sanitize_user_input(text: str) -> str:
    """
    사용자 입력을 정제합니다.
    
    Args:
        text: 사용자 입력
    
    Returns:
        정제된 텍스트
    """
    # HTML 태그 등의 위험한 요소 제거
    # 여기서는 간단히 처리하지만 실제로는 더 세밀한 검증 필요
    text = text.strip()
    return text 