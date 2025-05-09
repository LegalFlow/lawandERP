import os
import csv
import re
import glob
import chardet

def detect_encoding(file_path):
    """파일의 인코딩을 감지합니다."""
    with open(file_path, 'rb') as f:
        result = chardet.detect(f.read())
    return result['encoding']

def check_article_numbers(csv_file_path):
    try:
        # 파일 인코딩 감지
        encoding = detect_encoding(csv_file_path)
        
        # 파일 열기
        with open(csv_file_path, 'r', encoding=encoding) as file:
            reader = csv.reader(file)
            rows = list(reader)
            
            # 파일명에서 법률 이름 추출
            law_name = os.path.basename(csv_file_path).split('_')[0]
            
            # CSV 파일에서 법률명 가져오기
            if len(rows) > 0 and len(rows[0]) > 0:
                law_full_name = rows[0][0].strip()
                if law_full_name:
                    law_name = law_full_name.split(' ')[0]  # 첫 번째 단어만 사용
            
            # 헤더와 2번째 행을 제외하고 3번째 행부터 시작
            article_numbers = []
            for row in rows[2:]:
                if len(row) > 7:  # H열(인덱스 7)이 존재하는 경우만
                    article_no = row[7].strip()
                    if article_no and article_no != "article_no":  # 빈 값 및 헤더가 아닌 경우만
                        article_numbers.append(article_no)
            
            # 조항 번호 추출 및 정렬
            parsed_numbers = []
            for article in article_numbers:
                # "제n조" 또는 "제n조의 m" 형식 추출
                match = re.search(r'제(\d+)조(?:의 (\d+))?', article)
                if match:
                    main_num = int(match.group(1))
                    sub_num = int(match.group(2)) if match.group(2) else 0
                    parsed_numbers.append((main_num, sub_num))
            
            # 정렬된 값과 원래 값 비교하여 누락 확인
            parsed_numbers.sort()
            
            if not parsed_numbers:
                return f"{law_name} 파일에 유효한 조항이 없습니다."
            
            # 최소값과 최대값 찾기
            min_num = parsed_numbers[0][0]
            max_num = parsed_numbers[-1][0]
            
            # 누락된 조항 확인
            missing_articles = []
            expected_number = min_num
            
            for i, (main_num, sub_num) in enumerate(parsed_numbers):
                if main_num > expected_number:
                    # 누락된 주 번호 추가
                    for missing_main in range(expected_number, main_num):
                        missing_articles.append(f"제{missing_main}조")
                
                # 같은 주 번호의 연속을 확인하여 부 번호 확인
                if i > 0 and parsed_numbers[i-1][0] == main_num:
                    expected_sub = parsed_numbers[i-1][1] + 1
                    if sub_num > expected_sub:
                        for missing_sub in range(expected_sub, sub_num):
                            missing_articles.append(f"제{main_num}조의 {missing_sub}")
                
                expected_number = main_num + 1
                
            if missing_articles:
                # 누락된 조항 모두 표시
                missing_str = ", ".join(missing_articles)
                return f"{law_name} {missing_str}가 누락되었습니다."
            else:
                return f"{law_name} 제{min_num}조부터 제{max_num}조까지 정상적으로 구성되었습니다."
    except Exception as e:
        return f"{os.path.basename(csv_file_path)} 파일 처리 중 오류 발생: {str(e)}"

def main():
    try:
        # 현재 스크립트 실행 경로를 기준으로 vector_laws_csv 폴더 찾기
        current_dir = os.path.dirname(os.path.abspath(__file__))
        
        # Legal_Agent/Parsing/vector_laws_csv 폴더 경로 설정
        base_dir = os.path.dirname(os.path.dirname(current_dir))  # lawandERP 폴더
        csv_folder = os.path.join(base_dir, 'Legal_Agent', 'Parsing', 'vector_laws_csv')
        
        print(f"현재 스크립트 위치: {current_dir}")
        print(f"CSV 폴더 경로: {csv_folder}")
        
        # 폴더가 존재하는지 확인
        if not os.path.exists(csv_folder):
            print(f"오류: {csv_folder} 폴더가 존재하지 않습니다.")
            
            # 대안 경로 시도
            alternative_path = os.path.join(current_dir, 'Legal_Agent', 'Parsing', 'vector_laws_csv')
            print(f"대안 경로 시도: {alternative_path}")
            
            if os.path.exists(alternative_path):
                csv_folder = alternative_path
            else:
                print("vector_laws_csv 폴더를 찾을 수 없습니다.")
                csv_folder = input("vector_laws_csv 폴더의 전체 경로를 입력해주세요: ")
                
                if not os.path.exists(csv_folder):
                    print(f"오류: {csv_folder} 폴더가 존재하지 않습니다.")
                    return
        
        # CSV 파일 찾기
        csv_files = glob.glob(os.path.join(csv_folder, '*.csv'))
        
        if not csv_files:
            print(f"{csv_folder} 폴더에 CSV 파일이 없습니다.")
            return
        
        print(f"총 {len(csv_files)}개의 CSV 파일을 검사합니다...\n")
        
        results = []
        for csv_file in csv_files:
            result = check_article_numbers(csv_file)
            results.append(result)
            print(result)
            
        print("\n검사 완료!")
    except Exception as e:
        print(f"실행 중 오류 발생: {str(e)}")

if __name__ == "__main__":
    main() 