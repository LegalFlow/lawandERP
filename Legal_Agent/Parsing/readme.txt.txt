<법조문 데이터 처리>

parse_to_csv.py : 법률 txt파일을 최하위단위로 csv파일로 구조화. 
combine_articles.py : 최하위단위의 csv파일을 벡터화하기 위해 조문단위로 묶어 csv파일로 구조화 
check_law.py : 누락된 조문이 있는지 체크.
insert_to_db.py : 구조화된 법률 csv파일을 로컬db와 lawanderp의 실제 서버 rb_laws 테이블(텍스트 테이블)에 저장. 
embed_to_pinecone.py : vector_laws_csv 폴더안에 있는 모든 csv 파일을 pinecone(rag-law-index) 에 벡터화하여 업로드

<회생위원 직무편람 처리>
