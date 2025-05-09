import csv
import re
import os

# --- 파일 경로 설정 ---
current_dir = os.path.dirname(os.path.abspath(__file__))
toc_file_path = os.path.join(current_dir, '직무편람목차.csv')
text_file_path = os.path.join(current_dir, 'raw_pdf', '회생위원_직무편람.txt')
output_csv_path = os.path.join(current_dir, '회생위원_직무편람_parsed.csv')

# --- 정규 표현식 정의 ---
patterns = {
    '편': re.compile(r'^제(\d+)편\s+(.*)'),
    '장': re.compile(r'^제(\d+)장\s+(.*?)(?:\s+•\s+\d+)?$'),  # 장 제목 뒤에 '• 숫자' 형태의 페이지 표시가 있을 수 있음
    '절': re.compile(r'^제(\d+)절\s+(.*)'),
    '항_숫자': re.compile(r'^(\d+)\.\s+(.*)'),     # 항 정보는 내용으로 포함할 용도로만 사용
    '항_괄호': re.compile(r'^\(([가-힣\d]+)\)\s+(.*)'), 
    '항_한글': re.compile(r'^([가-힣])\.\s+(.*)'), 
    # 머리말/꼬리말/페이지 번호 패턴
    '머리말꼬리말': re.compile(
        r'^\s*•\s*\d+\s*$'  # • 숫자
        r'|^\s*제\d+편\s*$' # 제N편
        r'|^\s*제\d+장\s*$' # 제N장
        r'|^\s*제\d+절\s*$' # 제N절 (단독 라인)
        r'|^\s*제\d+편\s+.*\s+•\s+\d+\s*$' # 제1편 개관 • 3
        r'|^\s*제\d+장\s+.*\s+•\s+\d+\s*$' # 제1장 개인회생제도의 개요 • 5
        r'|^\s*\d+\s+•\s+제\d+편.*$' # 12 • 제1편 개관
        r'|^\s*제\d+장제\d+장\s+.*\s+•\s+\d+\s*$' # 제2장제2장 보전처분과 중지ㆍ금지명령, 포괄적 금지명령 • 31
    ),
    '각주': re.compile(r'^\d+\)\s+'), # 숫자) 로 시작하는 각주
    '빈줄': re.compile(r'^\s*$')
}

# --- 텍스트 정규화 함수 ---
def normalize_text(text):
    """텍스트를 정규화하여 공백과 특수문자를 제거합니다."""
    if not text:
        return ""
    # 공백 제거
    norm_text = re.sub(r'\s+', '', text)
    # 특수문자 제거 - 모든 특수문자 제거
    norm_text = re.sub(r'[^\w\s가-힣]', '', norm_text)
    return norm_text

print("1. 목차 파일 로드 중...")
# --- 목차 데이터 로드 ---
toc_data = []
section_data = []  # 절 단위 데이터 저장

try:
    with open(toc_file_path, 'r', encoding='utf-8-sig') as f:
        reader = csv.DictReader(f)
        toc_fieldnames = reader.fieldnames
        
        # 중복 제거를 위한 집합 (절 기준)
        unique_sections = set()
        
        for row in reader:
            # 제목에서 공백 제거
            for key in ['편제목', '장제목', '절제목', '항제목']:
                if key in row:
                    row[key] = row[key].strip() if row[key] else ''
            
            # 항은 무시하고 절 단위 데이터만 생성
            if row.get('절번호'):  # 절 번호가 있는 경우만 처리
                section_key = f"{row['편번호']}:{row['장번호']}:{row['절번호']}"
                
                # 아직 처리하지 않은 절인 경우에만 추가
                if section_key not in unique_sections:
                    unique_sections.add(section_key)
                    
                    # 정규화된 제목도 저장 (매칭용)
                    section_row = {
                        '편번호': row['편번호'],
                        '편제목': row['편제목'],
                        '장번호': row['장번호'],
                        '장제목': row['장제목'],
                        '절번호': row['절번호'],
                        '절제목': row['절제목'],
                        '정규화_편제목': normalize_text(row['편제목']),
                        '정규화_장제목': normalize_text(row['장제목']),
                        '정규화_절제목': normalize_text(row['절제목']),
                        'content': ""
                    }
                    section_data.append(section_row)
            
            # 원본 데이터도 보존
            toc_data.append(row)
            
except FileNotFoundError:
    print(f"오류: 목차 파일 '{toc_file_path}'을(를) 찾을 수 없습니다.")
    exit()
except Exception as e:
    print(f"오류: 목차 파일 '{toc_file_path}'을(를) 읽는 중 오류 발생: {e}")
    exit()

if not section_data:
    print("오류: 절 데이터가 비어 있습니다.")
    exit()

print(f"2. {len(section_data)}개의 절 항목을 로드했습니다.")
print(f"   편 목록: {', '.join(set(row['편번호'] for row in section_data))}")

# --- 텍스트 처리를 위한 변수 초기화 ---
current_content = []
current_편번호 = ""
current_장번호 = ""
current_절번호 = ""
current_section_index = None  # 현재 처리 중인 절 인덱스

# 이미 내용이 할당된 섹션 인덱스를 추적하기 위한 집합
filled_section_indices = set()

def find_section_index(편번호, 장번호="", 절번호="", 정규화된_제목=None):
    """목차에서 해당 번호에 맞는 절 인덱스 찾기"""
    # 번호가 모두 일치하는 항목 찾기
    matches = []
    for i, row in enumerate(section_data):
        if row['편번호'] == 편번호:
            if not 장번호 or row['장번호'] == 장번호:
                if not 절번호 or row['절번호'] == 절번호:
                    # 아직 내용이 할당되지 않은 섹션만 후보로 고려
                    if i not in filled_section_indices:
                        matches.append(i)
    
    # 번호로 유일하게 매칭된 경우
    if len(matches) == 1:
        return matches[0]
    
    # 여러 매칭이 있거나 매칭이 없는 경우, 정규화된 제목으로 추가 검색
    if 정규화된_제목 and matches:
        for idx in matches:
            section = section_data[idx]
            if 절번호 and normalize_text(section['절제목']) == 정규화된_제목:
                return idx
            elif 장번호 and normalize_text(section['장제목']) == 정규화된_제목:
                return idx
            elif normalize_text(section['편제목']) == 정규화된_제목:
                return idx
    
    # 특수 조건: 제2편 제2장의 경우 제목에 특수문자가 포함되어 있으므로 특별 처리
    if 편번호 == "2" and 장번호 == "2":
        for i, row in enumerate(section_data):
            if row['편번호'] == "2" and row['장번호'] == "2":
                # 보전처분 또는 금지명령이라는 키워드가 포함된 경우 매칭
                if "보전처분" in row['장제목'] or "금지명령" in row['장제목']:
                    if i not in filled_section_indices:
                        return i
    
    # 매칭이 없는 경우, 첫 번째 반환(번호만으로 매칭)
    if matches:
        return matches[0]
    
    # 모든 섹션이 이미 채워져 있다면, 새로운 섹션을 목차에 없는 항목으로 생성
    if not matches and 편번호 and 장번호 and 절번호 and 정규화된_제목:
        # 일치하는 섹션은 이미 모두 채워졌지만, 새로운 섹션 추가가 필요한 경우
        print(f"  알림: 새로운 섹션 생성 - 제{편번호}편 제{장번호}장 제{절번호}절")
        
        # 새 섹션 데이터 생성
        new_section = {
            '편번호': 편번호,
            '편제목': f"제{편번호}편", # 임시 제목
            '장번호': 장번호,
            '장제목': f"제{장번호}장", # 임시 제목
            '절번호': 절번호,
            '절제목': 정규화된_제목,  # 정규화된 제목 사용
            '정규화_편제목': "",
            '정규화_장제목': "",
            '정규화_절제목': 정규화된_제목,
            'content': ""
        }
        
        # 데이터에 추가하고 인덱스 반환
        section_data.append(new_section)
        return len(section_data) - 1
    
    return None

def debug_print_section(index, reason=""):
    """디버깅용: 섹션 정보 출력"""
    if index is not None and 0 <= index < len(section_data):
        s = section_data[index]
        print(f"  >> 섹션[{index}]: 제{s['편번호']}편 제{s['장번호']}장 제{s['절번호']}절 - {s['절제목']} {reason}")

print("3. 텍스트 파일 처리 중...")
# --- 텍스트 파일 처리 ---
try:
    debug_sections = set()  # 디버깅: 찾지 못한 섹션 기록
    
    with open(text_file_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
        
        for line_num, line in enumerate(lines, 1):
            line = line.strip()
            
            # 빈 줄 무시
            if not line or patterns['빈줄'].match(line):
                continue
                
            # 머리말/꼬리말 패턴만 무시 (각주는 무시하지 않음)
            if patterns['머리말꼬리말'].match(line):
                continue
            
            # 특수한 형태의 장 제목 패턴 처리 (예: 제2장제2장 보전처분과 중지ㆍ금지명령, 포괄적 금지명령)
            special_chapter_match = re.match(r'^제(\d+)장제\d+장\s+(.*?)(?:\s+•\s+\d+)?$', line)
            if special_chapter_match:
                # 이전 내용 저장
                if current_section_index is not None and current_content:
                    section_data[current_section_index]['content'] = '\n'.join(current_content).strip()
                    filled_section_indices.add(current_section_index)  # 채워진 섹션으로 표시
                
                # 새 장 시작
                current_장번호 = special_chapter_match.group(1)
                장제목 = special_chapter_match.group(2).strip()
                current_절번호 = ""
                current_content = []
                current_section_index = None  # 절이 나타날 때까지 대기
                
                print(f"  장 매칭 (특수 패턴): 제{current_편번호}편 제{current_장번호}장 - {장제목}")
                continue
                
            # 편, 장, 절 패턴 확인
            match_편 = patterns['편'].match(line)
            match_장 = patterns['장'].match(line)
            match_절 = patterns['절'].match(line)
            
            # 일치하는 패턴에 따라 처리
            if match_편:
                # 이전 내용 저장
                if current_section_index is not None and current_content:
                    section_data[current_section_index]['content'] = '\n'.join(current_content).strip()
                    filled_section_indices.add(current_section_index)  # 채워진 섹션으로 표시
                
                # 새 편 시작
                current_편번호 = match_편.group(1)
                편제목 = match_편.group(2).strip()
                current_장번호 = ""
                current_절번호 = ""
                current_content = []
                current_section_index = None  # 절이 나타날 때까지 대기
                
                print(f"  편 매칭: 제{current_편번호}편 - {편제목}")
                    
            elif match_장:
                # 이전 내용 저장
                if current_section_index is not None and current_content:
                    section_data[current_section_index]['content'] = '\n'.join(current_content).strip()
                    filled_section_indices.add(current_section_index)  # 채워진 섹션으로 표시
                
                # 새 장 시작
                current_장번호 = match_장.group(1)
                장제목 = match_장.group(2).strip()
                current_절번호 = ""
                current_content = []
                current_section_index = None  # 절이 나타날 때까지 대기
                
                print(f"  장 매칭: 제{current_편번호}편 제{current_장번호}장 - {장제목}")
                    
            elif match_절:
                # 이전 내용 저장
                if current_section_index is not None and current_content:
                    section_data[current_section_index]['content'] = '\n'.join(current_content).strip()
                    filled_section_indices.add(current_section_index)  # 채워진 섹션으로 표시
                
                # 새 절 시작
                current_절번호 = match_절.group(1)
                절제목 = match_절.group(2).strip()
                current_content = []
                
                # 목차에서 대응하는 절 찾기 (정규화된 제목 포함)
                current_section_index = find_section_index(
                    current_편번호, 
                    current_장번호, 
                    current_절번호,
                    정규화된_제목=normalize_text(절제목)
                )
                
                if current_section_index is not None:
                    print(f"  절 매칭: 제{current_편번호}편 제{current_장번호}장 제{current_절번호}절 - {절제목} (인덱스 {current_section_index})")
                    debug_print_section(current_section_index, "매칭됨")
                else:
                    key = f"{current_편번호}:{current_장번호}:{current_절번호}"
                    if key not in debug_sections:
                        debug_sections.add(key)
                        print(f"  경고: 제{current_편번호}편 제{current_장번호}장 제{current_절번호}절({절제목})을 목차에서 찾을 수 없습니다.")
                
            else:
                # 현재 절이 설정되었다면 일반 텍스트 추가 (항 포함)
                if current_section_index is not None:
                    current_content.append(line)
        
        # 마지막 내용 저장
        if current_section_index is not None and current_content:
            section_data[current_section_index]['content'] = '\n'.join(current_content).strip()
            filled_section_indices.add(current_section_index)  # 채워진 섹션으로 표시
            
except FileNotFoundError:
    print(f"오류: 텍스트 파일 '{text_file_path}'을(를) 찾을 수 없습니다.")
    exit()
except Exception as e:
    print(f"오류: 텍스트 파일 '{text_file_path}'을(를) 처리하는 중 오류 발생: {e}")
    import traceback
    traceback.print_exc()

print("4. 결과 저장 중...")
# --- 결과 저장 ---
try:
    # 출력 필드 준비 (정규화 필드 제외)
    output_fieldnames = ['편번호', '편제목', '장번호', '장제목', '절번호', '절제목', 'content']
    
    # 내용이 추가된 항목 수 계산
    filled_items = sum(1 for item in section_data if item.get('content'))
    
    output_data = []
    for item in section_data:
        # 정규화 필드 제거
        output_item = {key: item[key] for key in output_fieldnames}
        output_data.append(output_item)
    
    with open(output_csv_path, 'w', newline='', encoding='utf-8-sig') as f:
        writer = csv.DictWriter(f, fieldnames=output_fieldnames)
        writer.writeheader()
        writer.writerows(output_data)
    
    print(f"성공: CSV 파일이 '{output_csv_path}'에 저장되었습니다.")
    print(f"총 {len(section_data)}개 절 항목 중 {filled_items}개 항목에 내용이 추가되었습니다.")
    print(f"신규 생성된 섹션 수: {len(section_data) - len(toc_data)}")
    
except Exception as e:
    print(f"오류: CSV 파일 '{output_csv_path}'을(를) 저장하는 중 오류 발생: {e}")