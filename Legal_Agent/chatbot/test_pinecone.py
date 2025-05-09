# 테스트 스크립트
import os
import sys
import pinecone
from pinecone import Pinecone

# 설정 가져오기 
import config

print(f"Python 버전: {sys.version}")
print(f"Pinecone 패키지 버전: {pinecone.__version__}")

print("\n테스트 1: 새 버전 방식 시도")
try:
    pc = Pinecone(api_key=config.PINECONE_API_KEY)
    print("  - Pinecone 클래스 초기화 성공")
    index = pc.Index(config.PINECONE_INDEX_NAME)
    print(f"  - 인덱스 접근 성공: {config.PINECONE_INDEX_NAME}")
    print("신버전 방식이 작동합니다!")
except Exception as e:
    print(f"  - 오류 발생: {type(e).__name__}: {e}")
    
print("\n테스트 2: 구 버전 방식 시도")
try:
    pinecone.init(api_key=config.PINECONE_API_KEY, environment=config.PINECONE_ENVIRONMENT)
    print("  - pinecone.init 성공")
    index = pinecone.Index(config.PINECONE_INDEX_NAME)
    print(f"  - 인덱스 접근 성공: {config.PINECONE_INDEX_NAME}")
    print("구버전 방식이 작동합니다!")
except Exception as e:
    print(f"  - 오류 발생: {type(e).__name__}: {e}")