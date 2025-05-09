import pymysql
from typing import Optional, List, Dict, Tuple
import config
from utils import logger

# DB 연결 풀 (간단한 구현, 실제 환경에서는 더 견고한 풀 사용 권장)
connection = None

def get_db_connection():
    """
    데이터베이스 연결을 가져오거나 생성합니다.
    """
    global connection
    if connection and connection.open:
        try:
            connection.ping(reconnect=True) # 연결 유효성 검사
            return connection
        except pymysql.err.OperationalError:
            logger.warning("DB 연결이 끊어졌습니다. 재연결 시도...")
            connection = None # 연결 강제 재생성

    try:
        connection = pymysql.connect(
            host=config.DB_HOST,
            port=config.DB_PORT,
            user=config.DB_USER,
            password=config.DB_PASSWORD,
            database=config.DB_NAME,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        logger.info(f"DB 연결 성공: {config.DB_HOST}:{config.DB_PORT}/{config.DB_NAME}")
        return connection
    except pymysql.err.OperationalError as e:
        logger.error(f"DB 연결 실패: {e}")
        raise

def get_article_content(law_name: str, article_no: str) -> Optional[str]:
    """
    rb_laws 테이블에서 특정 조문의 content를 조회합니다.
    
    Args:
        law_name: 법률명
        article_no: 조문 번호
        
    Returns:
        조문 내용 (content) 또는 None
    """
    conn = get_db_connection()
    if not conn:
        return None
        
    try:
        with conn.cursor() as cursor:
            sql = "SELECT content FROM rb_laws WHERE law_name = %s AND article_no = %s LIMIT 1"
            cursor.execute(sql, (law_name, article_no))
            result = cursor.fetchone()
            
            if result:
                logger.debug(f"RDB 조회 성공: {law_name} {article_no}")
                return result['content']
            else:
                logger.warning(f"RDB에서 조문을 찾을 수 없음: {law_name} {article_no}")
                return None
                
    except pymysql.err.Error as e:
        logger.error(f"DB 쿼리 오류 ({law_name} {article_no}): {e}")
        # 오류 발생 시 연결을 다시 시도하도록 None 반환 및 연결 초기화
        global connection
        connection = None 
        return None

def get_multiple_article_contents(ids: List[Tuple[str, str]]) -> Dict[Tuple[str, str], str]:
    """
    여러 조문의 content를 한 번의 쿼리로 조회합니다.
    
    Args:
        ids: (law_name, article_no) 튜플의 리스트
        
    Returns:
        {(law_name, article_no): content} 딕셔너리
    """
    conn = get_db_connection()
    if not conn or not ids:
        return {}

    contents_map = {}
    placeholders = ', '.join(['(%s, %s)'] * len(ids))
    flat_params = [item for sublist in ids for item in sublist]

    sql = f"""SELECT law_name, article_no, content 
             FROM rb_laws 
             WHERE (law_name, article_no) IN ({placeholders})"""
             
    try:
        with conn.cursor() as cursor:
            cursor.execute(sql, flat_params)
            results = cursor.fetchall()
            
            for row in results:
                key = (row['law_name'], row['article_no'])
                contents_map[key] = row['content']
            
            logger.info(f"RDB 다중 조회: {len(ids)}개 요청, {len(results)}개 찾음")
            return contents_map
            
    except pymysql.err.Error as e:
        logger.error(f"DB 다중 쿼리 오류: {e}")
        global connection
        connection = None
        return {}

# 앱 종료 시 연결 닫기 (FastAPI 등에서는 shutdown 이벤트 핸들러 사용)
def close_db_connection():
    global connection
    if connection and connection.open:
        connection.close()
        logger.info("DB 연결 종료")
    connection = None 