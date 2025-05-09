import os
from pinecone import Pinecone
import config

def check_pinecone_index():
    """
    Pinecone 인덱스의 상태를 확인합니다.
    """
    print(f"Pinecone 인덱스 확인: {config.PINECONE_INDEX_NAME}")
    print(f"API 키: {config.PINECONE_API_KEY[:5]}...{config.PINECONE_API_KEY[-5:]}")
    
    try:
        # Pinecone 클라이언트 초기화
        pc = Pinecone(api_key=config.PINECONE_API_KEY)
        
        # 인덱스 목록 조회
        indexes = pc.list_indexes()
        print(f"\n사용 가능한 인덱스 목록:")
        if hasattr(indexes, 'names'):
            for idx in indexes.names():
                print(f"- {idx}")
        else:
            print(indexes)
        
        # 특정 인덱스 연결
        try:
            index = pc.Index(config.PINECONE_INDEX_NAME)
            
            # 인덱스 통계 정보 조회
            stats = index.describe_index_stats()
            print(f"\n인덱스 '{config.PINECONE_INDEX_NAME}'의 통계 정보:")
            print(f"벡터 개수: {stats.total_vector_count}")
            print(f"차원: {stats.dimension}")
            
            # 샘플 쿼리로 테스트 (임의의 벡터)
            dim = stats.dimension
            query_results = index.query(
                vector=[0.1] * dim,  # 임의의 벡터
                top_k=1,
                include_metadata=True
            )
            
            print("\n샘플 쿼리 결과:")
            if query_results.matches:
                print(f"매치 수: {len(query_results.matches)}")
                sample_match = query_results.matches[0]
                print(f"ID: {sample_match.id}")
                print(f"점수: {sample_match.score}")
                print(f"메타데이터: {sample_match.metadata}")
            else:
                print("일치하는 벡터가 없습니다.")
                
            # 부인권 관련 키워드 검색 시도
            print("\n메타데이터에서 '부인권' 키워드 검색:")
            try:
                # 키워드 검색은 보통 다른 방식으로 진행하지만, 
                # 여기서는 간단한 메타데이터 필터링으로 확인
                query_results = index.query(
                    vector=[0.1] * dim,  # 임의의 벡터
                    top_k=10,
                    include_metadata=True,
                    filter={
                        "$or": [
                            {"content": {"$contains": "부인권"}},
                            {"article_title": {"$contains": "부인"}}
                        ]
                    }
                )
                
                if query_results.matches:
                    print(f"'부인권' 관련 항목 발견: {len(query_results.matches)}개")
                    for i, match in enumerate(query_results.matches, 1):
                        print(f"\n[{i}]")
                        print(f"ID: {match.id}")
                        print(f"점수: {match.score}")
                        if 'law_name' in match.metadata:
                            print(f"법률: {match.metadata['law_name']}")
                        if 'article_no' in match.metadata:
                            print(f"조문: {match.metadata['article_no']}")
                        if 'article_title' in match.metadata:
                            print(f"제목: {match.metadata['article_title']}")
                        if 'content' in match.metadata:
                            content = match.metadata['content']
                            print(f"내용: {content[:100]}..." if len(content) > 100 else content)
                else:
                    print("메타데이터에서 '부인권' 키워드를 포함하는 항목을 찾을 수 없습니다.")
            except Exception as e:
                print(f"메타데이터 필터링 오류: {e}")
                
        except Exception as e:
            print(f"인덱스 연결 또는 쿼리 오류: {e}")
    
    except Exception as e:
        print(f"Pinecone 연결 오류: {e}")

if __name__ == "__main__":
    print(f"로컬 설정 파일 확인 (config_local.py)...")
    try:
        import config_local
        print("로컬 설정 파일 로드됨.")
    except ImportError:
        print("로컬 설정 파일을 찾을 수 없습니다.")
    
    check_pinecone_index() 