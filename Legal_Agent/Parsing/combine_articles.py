import pandas as pd
import os
import re
from glob import glob

# 스크립트 경로 가져오기
script_dir = os.path.dirname(os.path.abspath(__file__))

# 입력 및 출력 폴더 경로 설정
input_dir = os.path.join(script_dir, 'text_laws_csv')
output_dir = os.path.join(script_dir, 'vector_laws_csv')

# 출력 폴더가 없으면 생성
if not os.path.exists(output_dir):
    os.makedirs(output_dir)

# 조문 번호 추출 함수 (제1조, 제2조 등에서 숫자 부분만 추출)
def extract_article_number(article_no):
    if not article_no or article_no == '':
        return 0
    
    # 정규 표현식으로 숫자 추출 (제1조, 제1조의2 등 모두 처리)
    match = re.search(r'제(\d+)조(?:의(\d+))?', article_no)
    if match:
        main_num = int(match.group(1))
        sub_num = match.group(2)
        if sub_num:
            # 제1조의2와 같은 형태는 1.2로 정렬되도록 함
            return main_num + float('0.' + sub_num)
        return main_num
    return 0

# 유니코드 숫자(①,②)를 숫자로 매핑하는 함수
circled_number_map = {chr(code): idx for idx, code in enumerate(range(9312, 9332), start=1)}

# 다음 조문 제목이 합쳐지는지 확인하는 함수
def check_mixed_article(content):
    # 조문 제목 형식 (제x조의y(제목) 형태 찾기) - 반드시 줄바꿈 뒤에 오는 경우만 찾도록 수정
    pattern = r'\\n제\\d+조(?:의\\d+)?(?:\\([^)]+\\))?(?:\\s|$)'
    match = re.search(pattern, content)
    if match:
        # 분리 위치 찾기
        split_pos = match.start()
        # 원래 내용과 다음 조문 제목/내용 분리
        return content[:split_pos], content[split_pos:].strip()
    return content, None

# 항 번호 중복 제거 및 포맷 정리 함수
def clean_item_numbers(text):
    # 원문자 번호(①, ②, ③ 등) 정리 - 중복 제거
    text = re.sub(r'(\([①-⑮]\))\s*[①-⑮]', r'\1', text)
    
    # 항 번호가 중복되는 경우 처리 (예: ① ①)
    text = re.sub(r'([①-⑮])\s+([①-⑮])', r'\1', text)
    
    # 호의 이름 중복 제거 (예: 1. 1.)
    text = re.sub(r'(\d+\.)\s*\d+\.', r'\1', text)
    
    # 목의 이름 중복 제거 (예: 가. 가.)
    text = re.sub(r'([가-힣]\.)\s*[가-힣]\.', r'\1', text)
    
    # 항 번호(①) 다음에 오는 호 번호(1.)에서 항 번호 제거
    text = re.sub(r'([①-⑮])\s+(\d+\.)', r'\2', text)
    
    # 호 번호(1.) 다음에 오는 목 번호(가.)에서 호 번호 제거
    text = re.sub(r'(\d+\.)\s+([가-힣]\.)', r'\2', text)
    
    # 두 번째 중복 호 번호 패턴 제거 (예: 1. 1., 2. 2.)
    text = re.sub(r'(\d+)\.\s+\1\.', r'\1.', text)
    
    return text

# 숫자를 원본 표시형태로 복원하는 함수
def restore_original_format(row):
    # 항번호 처리 (①, ②, ... 형태로 복원)
    if pd.notna(row['항번호']) and row['항번호'] != '':
        item_num = int(row['항번호'])
        if 1 <= item_num <= 20:  # 원형 숫자는 1~20까지만 지원
            prefix = chr(9311 + item_num)  # ① = 9312, ② = 9313, ...
        else:
            prefix = f"({item_num})"
    else:
        prefix = ""
    
    # 호번호 처리 (1., 2., ... 형태로 복원)
    if pd.notna(row['호번호']) and row['호번호'] != '':
        if prefix:
            prefix += f" {row['호번호']}."
        else:
            prefix = f"{row['호번호']}."
    
    # 목번호 처리 (가., 나., ... 형태로 복원)
    if pd.notna(row['목번호']) and row['목번호'] != '':
        if prefix:
            prefix += f" {row['목번호']}."
        else:
            prefix = f"{row['목번호']}."
    
    # 내용 추가
    if prefix:
        return f"{prefix} {row['내용']}"
    else:
        return row['내용']

def extract_law_sections(text, law_name):
    # 항과 호, 목의 패턴 정의
    item_pattern = r'([①-⑮])\s*(.*?)(?=(?:[①-⑮]|\n\n|\Z))'
    number_pattern = r'(\d+\.)\s*(.*?)(?=(?:\d+\.|\n\n|\Z))'
    subitem_pattern = r'([가-힣]\.)\s*(.*?)(?=(?:[가-힣]\.|\n\n|\Z))'
    
    # 조문 단위로 분리
    article_pattern = r'제(\d+(?:-\d+)?)조(?:\(([^)]+)\))?((?:(?!제\d+(?:-\d+)조).)*)'
    
    # 조문 추출
    articles = []
    
    article_matches = re.finditer(article_pattern, text, re.DOTALL)
    for match in article_matches:
        article_no = match.group(1)
        article_title = match.group(2) if match.group(2) else ""
        content = match.group(3).strip()
        
        # 조문 내용 정리
        content = clean_item_numbers(content)
        
        articles.append({
            'law_name': law_name,
            'part_no': "",
            'part_title': "",
            'chapter_no': "",
            'chapter_title': "",
            'section_no': "",
            'section_title': "",
            'article_no': article_no,
            'article_title': article_title,
            'content': content
        })
    
    return articles

def process_law_file(file_path):
    print(f"처리 중: {file_path}")
    with open(file_path, 'r', encoding='utf-8') as f:
        text = f.read()
    
    # 법률 이름 추출
    law_name_match = re.search(r'^(.*?)\s*(?:타법개정|일부개정|제정|전부개정)', text)
    law_name = law_name_match.group(1).strip() if law_name_match else "미상"
    
    # 편, 장, 절 정보를 저장할 딕셔너리
    structure_info = {
        'part_no': "",
        'part_title': "",
        'chapter_no': "",
        'chapter_title': "",
        'section_no': "",
        'section_title': ""
    }
    
    # 조문 추출
    sections = extract_law_sections(text, law_name)
    
    # 편, 장, 절 정보 추출
    part_pattern = r'제(\d+)편\s+([^\n]+)'
    chapter_pattern = r'제(\d+)장\s+([^\n]+)'
    section_pattern = r'제(\d+)절\s+([^\n]+)'
    
    # 편 정보 추출
    part_matches = re.finditer(part_pattern, text)
    for part_match in part_matches:
        part_no = part_match.group(1)
        part_title = part_match.group(2).strip()
        
        # 해당 편 이후의 모든 조문에 편 정보 추가
        part_start_pos = part_match.start()
        for section in sections:
            section_text_pos = text.find(f"제{section['article_no']}조")
            if section_text_pos > part_start_pos:
                section['part_no'] = part_no
                section['part_title'] = part_title
    
    # 장 정보 추출
    chapter_matches = re.finditer(chapter_pattern, text)
    for chapter_match in chapter_matches:
        chapter_no = chapter_match.group(1)
        chapter_title = chapter_match.group(2).strip()
        
        # 해당 장 이후의 모든 조문에 장 정보 추가
        chapter_start_pos = chapter_match.start()
        for section in sections:
            section_text_pos = text.find(f"제{section['article_no']}조")
            if section_text_pos > chapter_start_pos:
                section['chapter_no'] = chapter_no
                section['chapter_title'] = chapter_title
    
    # 절 정보 추출
    section_matches = re.finditer(section_pattern, text)
    for section_match in section_matches:
        section_no = section_match.group(1)
        section_title = section_match.group(2).strip()
        
        # 해당 절 이후의 모든 조문에 절 정보 추가
        section_start_pos = section_match.start()
        for section_item in sections:
            section_text_pos = text.find(f"제{section_item['article_no']}조")
            if section_text_pos > section_start_pos:
                section_item['section_no'] = section_no
                section_item['section_title'] = section_title
    
    # 법률 제목과 시행일자 정보 추가
    header_info = {
        'law_name': law_name,
        'part_no': "",
        'part_title': "",
        'chapter_no': "",
        'chapter_title': "",
        'section_no': "",
        'section_title': "",
        'article_no': "",
        'article_title': "",
        'content': text.split('\n\n')[0].strip()
    }
    
    # 최종 결과에 헤더 정보 추가
    sections.insert(0, header_info)
    
    return sections

def combine_articles():
    # 대상 폴더 찾기
    base_dir = os.path.dirname(os.path.abspath(__file__))
    input_pattern = os.path.join(base_dir, "text_laws_csv", "*.csv")
    output_dir = os.path.join(base_dir, "vector_laws_csv")
    
    # 출력 폴더가 없으면 생성
    os.makedirs(output_dir, exist_ok=True)
    
    # 모든 법률 파일 처리
    for file_path in glob(input_pattern):
        try:
            # 파일명에서 접두사 추출 (예: rb_1.csv -> rb_1)
            file_prefix = os.path.splitext(os.path.basename(file_path))[0]
            
            # CSV 파일 불러오기
            df = pd.read_csv(file_path)
            
            # 결측값 처리
            df = df.fillna('')
            
            # 원본 형식으로 내용 변환
            df['formatted_content'] = df.apply(restore_original_format, axis=1)
            
            # 결과 리스트 생성
            result_groups = []
            
            # 법률명이 있고 조문번호가 없는 특별한 첫 항목 처리 (타이틀 행)
            title_rows = df[(df['법률명'] != '') & (df['조문번호'] == '')]
            if not title_rows.empty:
                for _, row in title_rows.iterrows():
                    result_groups.append({
                        '법률명': row['법률명'],
                        '편번호': '',
                        '편제목': '',
                        '장번호': '',
                        '장제목': '',
                        '절번호': '',
                        '절제목': '',
                        '조문번호': '',
                        '조문제목': '',
                        'content': row['내용'],
                        'article_sort_key': 0  # 타이틀은 항상 맨 처음에 오도록
                    })
            
            # 임시 그룹 저장 딕셔너리
            temp_article_contents = {}
            
            # 조문번호가 있는 행들 그룹화
            grouped = df[df['조문번호'] != ''].groupby(['법률명', '편번호', '편제목', '장번호', '장제목', '절번호', '절제목', '조문번호', '조문제목'])
            
            for name, group in grouped:
                # 같은 조문번호를 가진 모든 행의 formatted_content를 리스트로 만들고
                # 줄바꿈으로 연결해 하나의 문자열로 만듦
                contents = []
                for _, row in group.iterrows():
                    if row['formatted_content'].strip():  # 빈 내용이 아닌 경우에만 추가
                        contents.append(row['formatted_content'])
                
                combined_content = '\n'.join(contents)
                
                # 항번호, 호번호, 목번호 정리
                combined_content = clean_item_numbers(combined_content)
                
                # 다음 조문 제목이 합쳐진 경우 분리
                main_content, next_article = check_mixed_article(combined_content)
                
                # 그룹 이름을 딕셔너리로 변환
                group_dict = dict(zip(['법률명', '편번호', '편제목', '장번호', '장제목', '절번호', '절제목', '조문번호', '조문제목'], name))
                
                # 조문 번호 기준 정렬 키 추가
                article_sort_key = extract_article_number(group_dict['조문번호'])
                group_dict['article_sort_key'] = article_sort_key
                group_dict['content'] = main_content
                
                # 다음 조문 제목이 포함된 경우
                if next_article:
                    # 다음 조문에 대한 정보 추출 시도
                    next_article_match = re.search(r'제(\d+)조(?:의(\d+))?\(([^)]+)\)', next_article)
                    if next_article_match:
                        next_article_num = f"제{next_article_match.group(1)}조"
                        if next_article_match.group(2):
                            next_article_num += f"의{next_article_match.group(2)}"
                        next_article_title = next_article_match.group(3)
                        
                        # 임시 저장
                        if next_article_num not in temp_article_contents:
                            temp_article_contents[next_article_num] = {
                                '법률명': group_dict['법률명'],
                                '편번호': group_dict['편번호'],
                                '편제목': group_dict['편제목'],
                                '장번호': group_dict['장번호'],
                                '장제목': group_dict['장제목'],
                                '절번호': group_dict['절번호'],
                                '절제목': group_dict['절제목'],
                                '조문번호': next_article_num,
                                '조문제목': next_article_title,
                                'content': clean_item_numbers(next_article.replace(next_article_match.group(0), "").strip()),
                                'article_sort_key': extract_article_number(next_article_num)
                            }
                
                result_groups.append(group_dict)
            
            # 임시 저장된 분리된 조문들 추가
            for article_num, article_data in temp_article_contents.items():
                # 이미 존재하는 조문인지 확인
                if not any(g['조문번호'] == article_num for g in result_groups):
                    result_groups.append(article_data)
            
            # 조문 번호 기준으로 결과 정렬
            result_groups.sort(key=lambda x: x['article_sort_key'])
            
            # 결과를 데이터프레임으로 변환
            result = pd.DataFrame(result_groups)
            
            # 정렬 키 컬럼 제거
            result = result.drop(columns=['article_sort_key'])
            
            # 컬럼명 변경
            result = result.rename(columns={
                '법률명': 'law_name',
                '편번호': 'part_no',
                '편제목': 'part_title',
                '장번호': 'chapter_no',
                '장제목': 'chapter_title',
                '절번호': 'section_no',
                '절제목': 'section_title',
                '조문번호': 'article_no',
                '조문제목': 'article_title'
            })
            
            # 결과를 CSV 파일로 저장
            output_path = os.path.join(output_dir, f"{file_prefix}_combined.csv")
            result.to_csv(output_path, index=False, encoding='utf-8-sig')
            print(f"✅ 통합 완료: {os.path.abspath(output_path)}")
            
        except Exception as e:
            print(f"⚠️ 파일 처리 중 오류 발생: {file_path}")
            print(f"오류 내용: {e}")

if __name__ == "__main__":
    combine_articles()

print(f"모든 CSV 파일이 처리되었습니다. 결과는 {output_dir} 폴더에 저장되었습니다.") 