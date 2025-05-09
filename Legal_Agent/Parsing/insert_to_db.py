import os
import csv
import pymysql
from pymysql import Error

def insert_csv_to_db(csv_file_path, local_config, server_config):
    local_cursor = None
    server_cursor = None
    local_connection = None
    server_connection = None
    
    try:
        # 로컬 DB 연결 - pymysql 사용
        local_connection = pymysql.connect(
            host=local_config['host'],
            port=local_config['port'],
            user=local_config['user'],
            password=local_config['password'],
            database=local_config['database'],
            charset='utf8mb4',
            cursorclass=pymysql.cursors.Cursor
        )
        
        # 서버 DB 연결 - pymysql 사용
        server_connection = pymysql.connect(
            host=server_config['host'],
            port=server_config['port'],
            user=server_config['user'],
            password=server_config['password'],
            database=server_config['database'],
            charset='utf8mb4',
            cursorclass=pymysql.cursors.Cursor
        )
        
        local_cursor = local_connection.cursor()
        server_cursor = server_connection.cursor()
        
        # CSV 파일 읽기
        with open(csv_file_path, 'r', encoding='utf-8-sig') as csvfile:
            csv_reader = csv.DictReader(csvfile)
            
            # 처리된 행 수와 업데이트된 행 수 카운터
            inserted_count = 0
            updated_count = 0
            
            # 각 행을 DB에 삽입
            for row in csv_reader:
                # CSV의 값들을 DB 컬럼에 맞게 변환 (새 필드명으로 수정)
                clause_no = None
                item_no = None
                
                # CSV 필드가 존재하는지 확인
                if 'clause_no' in row and row['clause_no'] and row['clause_no'].isdigit():
                    clause_no = int(row['clause_no'])
                if 'item_no' in row and row['item_no'] and row['item_no'].isdigit():
                    item_no = int(row['item_no'])
                
                law_name = row['law_name']
                part_no = row.get('part_no', '')
                part_title = row.get('part_title', '')
                chapter_no = row.get('chapter_no', '')
                chapter_title = row.get('chapter_title', '')
                section_no = row.get('section_no', '')
                section_title = row.get('section_title', '')
                article_no = row.get('article_no', '')
                article_title = row.get('article_title', '')
                subitem_no = row.get('subitem_no', '')
                content = row.get('content', '')
                
                # UPSERT 쿼리 - 중복되는 경우 업데이트
                query = """
                INSERT INTO rb_laws
                (law_name, part_no, part_title, chapter_no, chapter_title, section_no, section_title, 
                article_no, article_title, clause_no, item_no, subitem_no, content)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                part_no = VALUES(part_no),
                part_title = VALUES(part_title),
                chapter_no = VALUES(chapter_no),
                chapter_title = VALUES(chapter_title),
                section_no = VALUES(section_no),
                section_title = VALUES(section_title),
                article_title = VALUES(article_title),
                clause_no = VALUES(clause_no),
                item_no = VALUES(item_no),
                subitem_no = VALUES(subitem_no),
                content = VALUES(content),
                updated_at = CURRENT_TIMESTAMP
                """
                
                values = (
                    law_name,        # law_name
                    part_no,         # part_no
                    part_title,      # part_title
                    chapter_no,      # chapter_no
                    chapter_title,   # chapter_title
                    section_no,      # section_no
                    section_title,   # section_title
                    article_no,      # article_no
                    article_title,   # article_title
                    clause_no,       # clause_no
                    item_no,         # item_no
                    subitem_no,      # subitem_no
                    content          # content
                )
                
                # 먼저 중복 확인 쿼리
                check_query = "SELECT id FROM rb_laws WHERE law_name = %s AND article_no = %s"
                check_values = (law_name, article_no)
                
                # 로컬 DB 처리
                local_cursor.execute(check_query, check_values)
                local_result = local_cursor.fetchone()
                
                if local_result:
                    # 이미 존재하는 경우 UPDATE
                    update_query = """
                    UPDATE rb_laws SET
                    part_no = %s,
                    part_title = %s,
                    chapter_no = %s,
                    chapter_title = %s,
                    section_no = %s,
                    section_title = %s,
                    article_title = %s,
                    clause_no = %s,
                    item_no = %s,
                    subitem_no = %s,
                    content = %s,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE law_name = %s AND article_no = %s
                    """
                    update_values = (
                        part_no, part_title, chapter_no, chapter_title, 
                        section_no, section_title, article_title, 
                        clause_no, item_no, subitem_no, content,
                        law_name, article_no
                    )
                    local_cursor.execute(update_query, update_values)
                    updated_count += 1
                else:
                    # 새로운 레코드 삽입
                    insert_query = """
                    INSERT INTO rb_laws
                    (law_name, part_no, part_title, chapter_no, chapter_title, section_no, section_title, 
                    article_no, article_title, clause_no, item_no, subitem_no, content)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    """
                    local_cursor.execute(insert_query, values)
                    inserted_count += 1
                
                # 서버 DB 처리
                server_cursor.execute(check_query, check_values)
                server_result = server_cursor.fetchone()
                
                if server_result:
                    # 이미 존재하는 경우 UPDATE
                    update_query = """
                    UPDATE rb_laws SET
                    part_no = %s,
                    part_title = %s,
                    chapter_no = %s,
                    chapter_title = %s,
                    section_no = %s,
                    section_title = %s,
                    article_title = %s,
                    clause_no = %s,
                    item_no = %s,
                    subitem_no = %s,
                    content = %s,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE law_name = %s AND article_no = %s
                    """
                    update_values = (
                        part_no, part_title, chapter_no, chapter_title, 
                        section_no, section_title, article_title, 
                        clause_no, item_no, subitem_no, content,
                        law_name, article_no
                    )
                    server_cursor.execute(update_query, update_values)
                else:
                    # 새로운 레코드 삽입
                    insert_query = """
                    INSERT INTO rb_laws
                    (law_name, part_no, part_title, chapter_no, chapter_title, section_no, section_title, 
                    article_no, article_title, clause_no, item_no, subitem_no, content)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    """
                    server_cursor.execute(insert_query, values)
            
            # 변경사항 커밋
            local_connection.commit()
            server_connection.commit()
            print(f"✅ '{os.path.basename(csv_file_path)}' 파일 처리 완료:")
            print(f"   - 새로 삽입된 레코드: {inserted_count}개")
            print(f"   - 업데이트된 레코드: {updated_count}개")

    except Exception as e:
        print(f"오류 발생: {e}")
    
    finally:
        # 연결 종료
        if local_cursor:
            local_cursor.close()
        if server_cursor:
            server_cursor.close()
        if local_connection:
            local_connection.close()
        if server_connection:
            server_connection.close()
            
        print("DB 연결이 종료되었습니다.")

def main():
    # 로컬 DB 설정
    local_db_config = {
        'host': '127.0.0.1',      # 로컬 DB 호스트
        'port': 3306,             # 로컬 DB 포트
        'database': 'law_firm_db', # 로컬 DB 이름
        'user': 'lawanderp',      # 로컬 DB 사용자명
        'password': '2496'        # 로컬 DB 비밀번호
    }
    
    # 서버 DB 설정
    server_db_config = {
        'host': '43.200.156.135', # 서버 DB 호스트
        'port': 3306,             # 서버 DB 포트
        'database': 'law_firm_db', # 서버 DB 이름
        'user': 'lawanderp',      # 서버 DB 사용자명
        'password': '2496'        # 서버 DB 비밀번호
    }
    
    # 스크립트 경로 가져오기
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    # vector_laws_csv 폴더 경로
    csv_dir = os.path.join(script_dir, 'vector_laws_csv')
    
    # 해당 폴더가 존재하는지 확인
    if not os.path.exists(csv_dir):
        print(f"오류: '{csv_dir}' 폴더가 존재하지 않습니다.")
        return
    
    # vector_laws_csv 폴더의 모든 .csv 파일 처리
    csv_files = [f for f in os.listdir(csv_dir) if f.endswith('.csv')]
    
    if not csv_files:
        print("처리할 CSV 파일이 없습니다.")
        return
    
    print(f"\n{len(csv_files)}개의 CSV 파일을 찾았습니다:")
    for i, file in enumerate(csv_files, 1):
        print(f"{i}. {file}")
    
    print("\n모든 CSV 파일을 DB에 삽입합니다...")
    for filename in csv_files:
        csv_file_path = os.path.join(csv_dir, filename)
        insert_csv_to_db(csv_file_path, local_db_config, server_db_config)

if __name__ == "__main__":
    main() 