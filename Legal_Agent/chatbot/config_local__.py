# API Keys
OPENAI_API_KEY = "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"
CLAUDE_API_KEY = "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"
PINECONE_API_KEY = "pcsk_5bVrzE_BbgA8JnoxeSm5xydc7Qn647R19kPSvqp5ofbhkBQZwdNzYh2CxFm8crbowEyMr"
PINECONE_ENVIRONMENT = "aws-us-east-1"
PINECONE_INDEX_NAME = "rag-law-index"

# Model Settings
EMBEDDING_MODEL = "text-embedding-3-large"
LLM_MODEL = "gpt-4.1"
GPT_MODEL = "gpt-4o"

# API Settings
API_HOST = "0.0.0.0"
API_PORT = 8001
DEBUG_MODE = False

# Search Settings
TOP_K_RESULTS = 5

# Database Settings
DB_HOST = "43.200.156.135"  # 실서버 DB 호스트 (필요시 수정)
DB_PORT = 3306
DB_USER = "lawanderp"  # 실서버 DB 사용자 (필요시 수정)
DB_PASSWORD = "2496"   # 실서버 DB 비밀번호 (필요시 수정)
DB_NAME = "law_firm_db"  # 실서버 DB 이름 (필요시 수정)

# 경로 설정
VECTOR_DB_PATH = "./data/vectordb"
CONTENT_DB_PATH = "./data/content.db"
CHAT_DB_PATH = "./data/chat.db"

# Logging
LOG_LEVEL = "INFO"