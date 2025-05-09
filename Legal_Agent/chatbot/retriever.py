import os
import re
import openai  # OpenAI 클래스 대신 모듈 자체를 임포트
from pinecone import Pinecone
from typing import List, Dict, Any, Tuple
import logging

# 설정 모듈 가져오기
import config
from utils import logger # utils.py 에서 logger 임포트
from db_client import get_multiple_article_contents # RDB 조회 함수 임포트

# OpenAI API 키 설정 (이전 버전 방식)
openai.api_key = config.OPENAI_API_KEY

# Pinecone 초기화 - 버전 호환성 처리
try:
    # 새로운 버전 방식 시도
    pc = Pinecone(api_key=config.PINECONE_API_KEY)
    index = pc.Index(config.PINECONE_INDEX_NAME)
    pinecone_version = "new"
    logger.info("Pinecone 새 버전 API 초기화 성공")
except (ImportError, AttributeError):
    try:
        # 이전 버전 방식 시도
        import pinecone
        pinecone.init(api_key=config.PINECONE_API_KEY)
        index = pinecone.Index(config.PINECONE_INDEX_NAME)
        pinecone_version = "old"
        logger.info("Pinecone 이전 버전 API 초기화 성공")
    except Exception as e:
        logger.error(f"Pinecone 초기화 실패: {e}")
        raise

def get_embedding(text: str) -> List[float]:
    """
    텍스트의 임베딩 벡터를 생성합니다.
    
    Args:
        text: 임베딩할 텍스트
        
    Returns:
        생성된 임베딩 벡터
    """
    try:
        # 이전 버전 API 방식으로 임베딩 생성
        response = openai.Embedding.create(
            input=[text],
            model=config.EMBEDDING_MODEL
        )
        return response['data'][0]['embedding']
    except Exception as e:
        logger.error(f"임베딩 생성 오류: {e}", exc_info=True)
        raise

def preprocess_query(query: str) -> str:
    """
    검색 쿼리를 전처리합니다.
    
    Args:
        query: 원본 쿼리
        
    Returns:
        전처리된 쿼리
    """
    # 불필요한 단어 제거
    query = re.sub(r'어디서|무엇인가요?|알려줘|가르쳐줘|알고 싶어', '', query)
    
    # 특수 키워드 강화
    legal_terms = ['부인권', '회생', '파산', '채무자', '개인회생']
    for term in legal_terms:
        if term in query:
            # 중요 키워드를 2번 반복하여 가중치 부여
            query = f"{query} {term} {term}"
    
    logger.info(f"전처리된 쿼리: {query.strip()}")
    return query.strip()

def extract_keywords(query: str) -> List[str]:
    """
    쿼리에서 중요 키워드를 추출합니다.
    
    Args:
        query: 사용자 질문
        
    Returns:
        키워드 목록
    """
    # 법률 관련 중요 키워드
    keywords = []
    
    # 법률 용어 패턴 (회생법, 파산법 등)
    law_pattern = r'(채무자\s*회생|파산|개인\s*회생|회생\s*절차|파산\s*절차)'
    law_matches = re.findall(law_pattern, query)
    keywords.extend(law_matches)
    
    # 특정 법률 조문 관련 패턴
    article_pattern = r'(부인권|관리인|파산\s*관재인|채권자|면책)'
    article_matches = re.findall(article_pattern, query)
    keywords.extend(article_matches)
    
    # 중복 제거 및 공백 정리
    cleaned_keywords = [k.strip() for k in keywords]
    final_keywords = list(set(cleaned_keywords))
    logger.info(f"추출된 키워드: {final_keywords}")
    return final_keywords

def rank_articles(articles: List[Dict[str, Any]], keywords: List[str]) -> List[Dict[str, Any]]:
    """
    키워드 일치도에 따라 조문 순위를 조정합니다.
    RDB에서 가져온 실제 content를 기준으로 랭킹합니다.
    
    Args:
        articles: RDB에서 content를 포함하여 구성된 조문 목록
        keywords: 추출된 키워드
        
    Returns:
        순위가 조정된 조문 목록
    """
    if not keywords or not articles:
        return articles
        
    logger.info(f"키워드 랭킹 시작: {len(articles)}개 조문, 키워드: {keywords}")
    ranked_articles = []
    for article in articles:
        # RDB에서 가져온 실제 content 확인
        content = article.get('content', '').lower()
        if not content:
            logger.warning(f"ID {article.get('id', 'N/A')}: 키워드 랭킹을 위한 content 누락. 건너뜁니다.")
            article['score'] = article['original_score'] # 키워드 점수 없이 원래 점수 사용
            ranked_articles.append(article)
            continue
            
        keyword_score = 0
        title = article.get('article_title', '').lower()
        
        matched_keywords_in_article = []
        for keyword in keywords:
            keyword_lower = keyword.lower()
            keyword_present = False
            # 제목에 키워드가 있으면 높은 가중치
            if keyword_lower in title:
                keyword_score += 0.2
                keyword_present = True
            
            # 내용에 키워드가 있으면 가중치 추가
            if keyword_lower in content:
                keyword_score += 0.1
                keyword_present = True
            
            if keyword_present:
                matched_keywords_in_article.append(keyword)
                
        # 최종 점수 = 원래 유사도 점수 + 키워드 점수
        new_score = article['original_score'] + keyword_score # 원래 Pinecone 점수 기준
        article['keyword_score'] = keyword_score
        article['score'] = new_score
        article['matched_keywords'] = matched_keywords_in_article # 매칭된 키워드 기록
        ranked_articles.append(article)
        
        if keyword_score > 0:
             logger.debug(f"  - ID {article.get('id', 'N/A')}: 키워드 점수 {keyword_score:.2f} 추가 (매칭: {matched_keywords_in_article}), 최종 점수: {new_score:.4f}")
        
    # 점수에 따라 내림차순 정렬
    sorted_articles = sorted(ranked_articles, key=lambda x: x['score'], reverse=True)
    logger.info(f"키워드 랭킹 완료. 상위 결과 점수: {sorted_articles[0]['score']:.4f} (원래 점수: {sorted_articles[0]['original_score']:.4f})" if sorted_articles else "결과 없음")
    return sorted_articles

def retrieve_relevant_articles(query: str, top_k: int = 3) -> List[Dict[str, Any]]:
    """
    질문과 가장 관련성이 높은 법률 조문을 검색합니다.
    
    Args:
        query: 사용자 질문
        top_k: 반환할 조문 수
        
    Returns:
        관련 법률 조문 목록 (RDB에서 content 포함)
    """
    logger.info(f"원본 쿼리: {query}")
    # 쿼리 전처리
    processed_query = preprocess_query(query)
    
    # 키워드 추출
    keywords = extract_keywords(query)
    
    try:
        # 질문의 임베딩 생성
        query_embedding = get_embedding(processed_query)
        logger.info(f"쿼리 임베딩 생성 완료 (차원: {len(query_embedding)})")
    except Exception as e:
        logger.error(f"쿼리 임베딩 생성 실패: {e}")
        return []
        
    # Pinecone에서 벡터 검색 (ID와 메타데이터만 필요)
    num_candidates = top_k * 3 # 더 많은 결과를 가져와서 후처리 (최소 10개는 가져오도록)
    if num_candidates < 10:
        num_candidates = 10
        
    logger.info(f"Pinecone 벡터 검색 시작 (top_k={num_candidates})")
    try:
        # Pinecone 버전에 따른 쿼리 결과 처리
        search_results = index.query(
            vector=query_embedding,
            top_k=num_candidates,
            include_metadata=True
        )
        
        # 버전에 따른 결과 처리
        if pinecone_version == "new":
            matches = search_results.matches
            matches_count = len(matches)
        else:
            matches = search_results['matches']
            matches_count = len(matches)
            
        logger.info(f"Pinecone 검색 완료: {matches_count}개 결과 받음")
    except Exception as e:
        logger.error(f"Pinecone 검색 오류: {e}", exc_info=True)
        return []
        
    # 결과에서 law_name, article_no 추출 및 RDB 조회 준비
    articles_to_fetch = []
    pinecone_results_map = {}
    logger.debug("--- Pinecone Raw Results (ID + Meta) ---")
    
    for i, match in enumerate(matches):
        # 버전에 따른 결과 처리
        if pinecone_version == "new":
            metadata = match.metadata
            score = match.score
            vector_id = match.id
        else:
            metadata = match['metadata']
            score = match['score']
            vector_id = match['id']
            
        logger.debug(f"  [{i+1}] ID: {vector_id}, Score: {score:.4f}, Meta: {metadata}")
        
        # 필수 ID 필드 확인
        law_name = metadata.get('law_name')
        article_no = metadata.get('article_no')
        
        if law_name and article_no:
            db_key = (law_name, article_no)
            articles_to_fetch.append(db_key)
            # 중복된 (law_name, article_no)가 있을 경우 높은 점수 유지
            if db_key not in pinecone_results_map or score > pinecone_results_map[db_key]['score']:
                pinecone_results_map[db_key] = {
                    'id': vector_id,
                    'score': score,
                    'metadata': metadata # 원본 메타데이터 저장
                }
        else:
            logger.warning(f"  [{i+1}] ID: {vector_id} RDB 조회를 위한 필수 메타데이터 누락: law_name 또는 article_no")
            
    logger.debug("--- End Pinecone Raw Results ---")
    
    if not articles_to_fetch:
        logger.warning("RDB에서 조회할 유효한 조문 ID가 없습니다.")
        return []
        
    # RDB에서 실제 content 조회 (중복 제거된 ID 목록 사용)
    unique_ids_to_fetch = list(pinecone_results_map.keys())
    rdb_contents = get_multiple_article_contents(unique_ids_to_fetch)
    
    # Pinecone 결과와 RDB content 결합
    combined_articles = []
    for db_key, content in rdb_contents.items():
        if db_key in pinecone_results_map:
            pinecone_data = pinecone_results_map[db_key]
            metadata = pinecone_data['metadata']
            combined_articles.append({
                'id': pinecone_data['id'],
                'law_name': db_key[0],
                'article_no': db_key[1],
                'content': content, # RDB에서 가져온 실제 content
                'original_score': pinecone_data['score'], # Pinecone 유사도 점수
                'score': pinecone_data['score'], # 초기 점수는 Pinecone 점수
                'part_title': metadata.get('part_title', ''),
                'chapter_title': metadata.get('chapter_title', ''),
                'section_title': metadata.get('section_title', ''),
                'article_title': metadata.get('article_title', '')
            })
        else:
             logger.error(f"로직 오류: RDB 결과 키 {db_key}가 Pinecone 맵에 없습니다.") # 발생하면 안 됨
             
    logger.info(f"RDB 조회 후 결합된 조문 수: {len(combined_articles)}")

    if not combined_articles:
        logger.warning("RDB 조회 결과가 비어있거나 Pinecone 결과와 결합할 수 없습니다.")
        return []
        
    # 키워드 기반으로 순위 조정 (RDB content 기준)
    ranked_articles = rank_articles(combined_articles, keywords)
    
    # 상위 결과만 반환
    final_articles = ranked_articles[:top_k]
    logger.info(f"최종 반환 조문 수: {len(final_articles)}")
    
    # content 필드를 제외하고 LLM에 전달할 데이터만 남길 경우
    # final_articles_for_llm = [{k: v for k, v in article.items() if k != 'content'} for article in final_articles]
    # logger.info(f"LLM 전달용 최종 조문 수: {len(final_articles_for_llm)}")
    
    return final_articles 