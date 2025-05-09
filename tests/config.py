import os
from dotenv import load_dotenv

# .env 파일이 있으면 로드
load_dotenv()

# API 서버 설정
API_HOST = os.getenv("API_HOST", "0.0.0.0")
API_PORT = int(os.getenv("API_PORT", "8001"))

# OpenAI API 설정
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY", "")

# Claude API 설정
CLAUDE_API_KEY = os.getenv("CLAUDE_API_KEY", "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")

# 기본 LLM 모델 설정 (기본값은 Claude로 설정)
LLM_MODEL = os.getenv("LLM_MODEL", "claude-3-5-sonnet-20241022")
GPT_MODEL = os.getenv("GPT_MODEL", "gpt-4o")  # GPT 모델 추가

# RAG 설정
EMBEDDINGS_MODEL = os.getenv("EMBEDDINGS_MODEL", "text-embedding-3-large")
EMBEDDING_DIM = 1536  # text-embedding-3-large 모델의 차원
TOP_K_RESULTS = int(os.getenv("TOP_K_RESULTS", "5"))  # 상위 K개 결과 검색

# 벡터 DB 설정
VECTOR_DB_PATH = os.getenv("VECTOR_DB_PATH", "./data/vectordb")

# 콘텐츠 DB 설정
CONTENT_DB_PATH = os.getenv("CONTENT_DB_PATH", "./data/content.db")

# 채팅 기록 DB 설정
CHAT_DB_PATH = os.getenv("CHAT_DB_PATH", "./data/chat.db")

# 로깅 설정
LOG_LEVEL = os.getenv("LOG_LEVEL", "INFO")
LOG_FILE = os.getenv("LOG_FILE", "legal_agent.log")

# 디버그 모드 (개발용)
DEBUG_MODE = os.getenv("DEBUG_MODE", "True").lower() in ["true", "1", "yes"]

# 가용 모델 목록
AVAILABLE_MODELS = {
    "claude-3-5-sonnet-20241022": "Claude 3.5 Sonnet",
    "gpt-4o": "GPT-4o"
}

# 기본 설정 정의
# API 키 및 기본 설정
PINECONE_API_KEY = os.getenv("PINECONE_API_KEY", "")
PINECONE_ENVIRONMENT = os.getenv("PINECONE_ENVIRONMENT", "")
PINECONE_INDEX_NAME = os.getenv("PINECONE_INDEX_NAME", "")

# 모델 설정
EMBEDDING_MODEL = os.getenv("EMBEDDING_MODEL", "text-embedding-3-large")

# API 설정
DEBUG_MODE = os.getenv("DEBUG_MODE", "True").lower() in ("true", "1", "t")

# 검색 설정
TOP_K_RESULTS = int(os.getenv("TOP_K_RESULTS", "3"))

# 데이터베이스 연결 정보 (RDB)
DB_HOST = os.getenv("DB_HOST", "localhost")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_USER = os.getenv("DB_USER", "root")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")
DB_NAME = os.getenv("DB_NAME", "lawanderp")

# 경로 설정
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CHAT_HISTORY_DIR = os.path.join(BASE_DIR, "chat_history")
LOG_DIR = os.path.join(BASE_DIR, "logs")

# 디렉토리가 없으면 생성
os.makedirs(CHAT_HISTORY_DIR, exist_ok=True)
os.makedirs(LOG_DIR, exist_ok=True)

# config_local.py에서 로컬 설정 로드 (있는 경우)
try:
    from config_local import *
    print("로컬 설정을 성공적으로 로드했습니다.")
except ImportError:
    print("로컬 설정 파일(config_local.py)을 찾을 수 없습니다. 기본 설정을 사용합니다.") 