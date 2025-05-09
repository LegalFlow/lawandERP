import os
import csv
import re

def extract_article_number(article_str):
    """조문 번호를 추출하는 함수"""
    # '제1조', '제2조의2', '제3조의3' 등의 패턴을 찾음
    match = re.match(r'^제(\d+)조(?:의(\d+))?$', article_str)
    if match:
        base_num = int(match.group(1))
        sub_num = int(match.group(2)) if match.group(2) else 0
        return (base_num, sub_num)
    return None

def extract_law_name(content_str):
    """법률명을 추출하는 함수"""
    if not content_str:
        return None
    
    # '~법', '~법률' 패턴의 법률명을 추출
    match = re.search(r'(.+?(?:법|법률|규정|특례법))', content_str)
    if match:
        return match.group(1).strip()
    return None

def is_sequential(article_numbers):
    """조문 번호가 순차적인지 확인하는 함수"""
    if not article_numbers:
        return True, []
    
    missing = []
    
    # 모든 (base, sub) 쌍을 정렬하여 순서대로 확인
    sorted_numbers = sorted(article_numbers)
    
    # 각 번호별로 있는지 체크할 딕셔너리
    existing_numbers = {num: True for num in sorted_numbers}
    
    # 첫 번째 조항부터 마지막 조항까지 확인
    first_base = sorted_numbers[0][0]
    last_base = sorted_numbers[-1][0]
    
    # 기본 조항 체크 (제1조, 제2조, ...)
    for base in range(first_base, last_base + 1):
        # 기본 조항이 없는 경우 (sub가 0인 경우)
        if (base, 0) not in existing_numbers:
            # '제2조의1', '제2조의2' 등이 있는지 확인
            sub_exists = any(num[0] == base and num[1] > 0 for num in sorted_numbers)
            
            # 관련 sub 조항도 없다면 누락으로 표시
            if not sub_exists:
                missing.append(f"제{base}조")
        else:
            # 기본 조항이 있는 경우, 해당 조항의 모든 sub 체크
            subs_for_base = [sub for b, sub in sorted_numbers if b == base and sub > 0]
            if subs_for_base:
                max_sub = max(subs_for_base)
                
                # '제N조의2'부터 '제N조의M'까지 확인 (주의: '제N조의1'은 누락되어도 정상)
                for sub in range(2, max_sub + 1):
                    if (base, sub) not in existing_numbers:
                        missing.append(f"제{base}조의{sub}")
    
    return len(missing) == 0, missing

def check_law_articles():
    """모든 CSV 파일의 조문 번호를 체크하고 결과를 출력하는 함수"""
    # 현재 스크립트 파일의 디렉토리 경로
    script_dir = os.path.dirname(os.path.abspath(__file__))
    # vector_laws_csv 폴더 경로
    csv_folder = os.path.join(script_dir, 'vector_laws_csv')
    results = []
    
    # 폴더 내의 모든 CSV 파일 확인
    for filename in os.listdir(csv_folder):
        if filename.endswith('.csv'):
            file_path = os.path.join(csv_folder, filename)
            law_name = None
            article_numbers = []
            
            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    csv_reader = csv.reader(f)
                    
                    # 헤더 건너뛰기
                    next(csv_reader)
                    
                    # 두 번째 줄부터 처리 시작
                    for row in csv_reader:
                        # 법률명이 아직 추출되지 않았으면 추출 시도
                        if not law_name and len(row) > 9 and row[9]:
                            law_name = extract_law_name(row[9])
                        elif not law_name and len(row) > 0:
                            law_name = row[0].split(' ')[0]  # 첫 번째 열에서 공백 앞까지 추출
                        
                        # H열(8번째 열)에서 조문 번호 추출
                        if len(row) > 8 and row[7]:
                            article_no = row[7].strip()
                            extracted = extract_article_number(article_no)
                            if extracted:
                                article_numbers.append(extracted)
                
                # 순차적인지 확인
                if article_numbers:
                    is_normal, missing_articles = is_sequential(article_numbers)
                    
                    if is_normal:
                        first_article = f"제{article_numbers[0][0]}조"
                        last_article = f"제{article_numbers[-1][0]}조"
                        if article_numbers[-1][1] > 0:
                            last_article += f"의{article_numbers[-1][1]}"
                        
                        results.append(f"{law_name} {first_article}부터 {last_article}까지 정상적으로 구성되었습니다.")
                    else:
                        missing_str = ", ".join(missing_articles)
                        results.append(f"{law_name} {missing_str}가 누락되었습니다.")
            except Exception as e:
                results.append(f"{filename} 파일 처리 중 오류 발생: {str(e)}")
    
    return results

def main():
    results = check_law_articles()
    
    # 결과 출력
    for result in results:
        print(result)
    
    # 현재 스크립트 파일의 디렉토리 경로
    script_dir = os.path.dirname(os.path.abspath(__file__))
    result_file = os.path.join(script_dir, 'check_law_results.txt')
    
    # 결과를 파일로 저장
    with open(result_file, 'w', encoding='utf-8') as f:
        for result in results:
            f.write(result + '\n')
    
    print(f"\n결과가 {result_file}에 저장되었습니다.")

if __name__ == "__main__":
    main() 