import sys
import os

# 현재 스크립트 경로 기준으로 import 경로 설정
current_dir = os.path.dirname(os.path.abspath(__file__))
sys.path.append(os.path.dirname(current_dir))

# 직접 모듈 import (chatbot. 접두사 제거)
from retriever import retrieve_relevant_articles
from llm_client import generate_response, format_articles_for_context

def test_retrieval_and_response():
    """
    벡터 검색과 LLM 응답 생성을 테스트합니다.
    """
    # 테스트 질문
    test_question = "채무자 회생 및 파산에 관한 법률에서 부인권에 대해 어디서 규정하고 있니? 개인회생을 하고 있어."
    
    print(f"질문: {test_question}\n")
    
    # 관련 조문 검색 (더 많은 결과를 검색)
    print("관련 조문 검색 중...")
    relevant_articles = retrieve_relevant_articles(test_question, top_k=10)
    
    # 검색 결과 출력
    print(f"\n검색된 관련 조문 ({len(relevant_articles)}개):")
    for i, article in enumerate(relevant_articles, 1):
        print(f"\n[{i}] {article['law_name']} 제{article['article_no']}조")
        if article.get('article_title'):
            print(f"제목: {article['article_title']}")
        print(f"유사도 점수: {article['score']:.4f}")
        print(f"내용: {article['content'][:100]}...")
    
    # 포맷팅된 조문 확인
    formatted_context = format_articles_for_context(relevant_articles)
    print("\n포맷팅된 조문:")
    print(formatted_context)
    
    # LLM 응답 생성
    print("\nLLM 응답 생성 중...")
    answer = generate_response(test_question, relevant_articles)
    
    # 응답 출력
    print("\n생성된 응답:")
    print(answer)

if __name__ == "__main__":
    test_retrieval_and_response() 