import re
import csv
import os

# 정규표현식 패턴 정의
patterns = {
    '편': re.compile(r'^제(\d+)편\s*(.*)'),
    '장': re.compile(r'^제(\d+)장\s*(.*)'),
    '절': re.compile(r'^제(\d+)절\s*(.*)'),
    '조문': re.compile(r'^제(\d+)조(?:의(\d+))?\((.*?)\)'),
    '조문2': re.compile(r'^제(\d+)조의(\d+)\((.*?)\)'),  # 제19조의2 같은 형식 별도 매칭
    '삭제조문': re.compile(r'^제(\d+)조(?:의(\d+))?\s+삭제\s+<(\d{4}\.\d{1,2}\.\d{1,2})>'),  # 한 줄 삭제 패턴
    '항': re.compile(r'^[\s]*[\u2460-\u2473]'),  # ①~⑳
    '호': re.compile(r'^[\s]*\d+\.'),
    '목': re.compile(r'^[\s]*[가-하]\.'),
    # 추가: 두 줄 삭제 패턴의 첫 줄 (조문 번호만)
    '조문번호만': re.compile(r'^제(\d+)조(?:의(\d+))?$'), 
    # 추가: 두 줄 삭제 패턴의 두 번째 줄 (삭제 정보만)
    '삭제정보만': re.compile(r'^삭제\s+<(\d{4}\.\d{1,2}\.\d{1,2})>') 
}

# 유니코드 숫자(①,②)를 숫자로 매핑하는 함수
circled_number_map = {chr(code): idx for idx, code in enumerate(range(9312, 9332), start=1)}

def get_circled_number(text):
    for char in text.strip():
        if char in circled_number_map:
            return circled_number_map[char]
    return None

# 조문 번호가 자연스럽게 이어지는지 확인하는 함수
def is_next_expected_article(current_num, current_sub_num, next_num, next_sub_num):
    if current_num is None:
        return True
    
    try:
        current_num = int(current_num)
        next_num = int(next_num)
    except (ValueError, TypeError):
         return False # 숫자로 변환할 수 없으면 예상 조문이 아님

    current_sub_num_int = None
    if current_sub_num is not None:
        try:
            current_sub_num_int = int(current_sub_num)
        except (ValueError, TypeError):
            pass # 변환 실패 시 None 유지
            
    next_sub_num_int = None
    if next_sub_num is not None:
        try:
            next_sub_num_int = int(next_sub_num)
        except (ValueError, TypeError):
            pass
            
    # 같은 조문 번호, 부속 번호 증가 (N -> N의1, N의X -> N의X+1)
    if current_num == next_num:
        if current_sub_num_int is None and next_sub_num_int is not None:
            return next_sub_num_int >= 1 # N -> N의1 이상
        elif current_sub_num_int is not None and next_sub_num_int is not None:
            return next_sub_num_int == current_sub_num_int + 1 # N의X -> N의X+1
        return False
    
    # 다음 조문 번호 증가 (N -> N+1)
    if next_num == current_num + 1:
        # N+1 이거나 N+1의1 이어야 함
        return next_sub_num_int is None or next_sub_num_int == 1
    
    return False

# 현재 context를 results에 추가하는 함수
def save_current_context(results, current, law_name, current_id):
    should_save = False
    # 내용이 있거나, 삭제 조문인 경우 저장
    if current['내용']:
        should_save = True
    # 조문 제목만 있는 경우도 저장 (항/호/목 없이 조문만 있는 경우 위함)
    elif current['조문번호'] and current['조문제목'] is not None and not current['항번호'] and not current['호번호'] and not current['목번호']:
         should_save = True
         
    if should_save:
        results.append({
            'id': current_id,
            '법률명': law_name,
            '편번호': current['편번호'],
            '편제목': current['편제목'],
            '장번호': current['장번호'],
            '장제목': current['장제목'],
            '절번호': current['절번호'],
            '절제목': current['절제목'],
            '조문번호': current['조문번호'],
            '조문제목': current['조문제목'],
            '항번호': current['항번호'],
            '호번호': current['호번호'],
            '목번호': current['목번호'],
            '내용': current['내용'].strip() if current['내용'] else ''
        })
        return current_id + 1
    return current_id

# 스크립트 경로 가져오기
script_dir = os.path.dirname(os.path.abspath(__file__))

# 입력 및 출력 폴더 경로 설정
raw_laws_dir = os.path.join(script_dir, 'raw_laws')
text_laws_csv_dir = os.path.join(script_dir, 'text_laws_csv')

# 출력 폴더가 없으면 생성
if not os.path.exists(text_laws_csv_dir):
    os.makedirs(text_laws_csv_dir)

# raw_laws 폴더의 모든 .txt 파일 처리
for filename in os.listdir(raw_laws_dir): 
    if filename.endswith('.txt'):
        input_file = os.path.join(raw_laws_dir, filename)
        output_csv = os.path.join(text_laws_csv_dir, filename.replace('.txt', '.csv'))

        # 파일 첫 줄에서 법률 이름 추출
        with open(input_file, 'r', encoding='utf-8') as f:
            first_line = f.readline().strip()
            # 파일명에서 법률 이름 추출 (첫 줄이 비어있을 경우 대비)
            if not first_line:
                law_name = os.path.splitext(filename)[0]
            else:
                law_name = first_line
            
            # 첫 줄을 포함한 전체 파일 다시 읽기
            f.seek(0)
            lines = f.readlines()

        results = []
        current_id = 1
        current_편번호 = None
        current_편제목 = None
        current_장번호 = None
        current_장제목 = None
        current_절번호 = None
        current_절제목 = None
        
        # 조문 번호 추적을 위한 변수
        last_article_num = None
        last_article_sub_num = None
        
        # 현재 처리 중인 정보
        current = {
            '편번호': None, '편제목': None, '장번호': None, '장제목': None, 
            '절번호': None, '절제목': None, '조문번호': None, '조문제목': None, 
            '항번호': None, '호번호': None, '목번호': None, '내용': ''
        }
        
        # 상위 구조 정보를 임시 저장 (항/호/목 상속용)
        temp_context = current.copy()

        i = 0
        while i < len(lines):
            line = lines[i].strip()
            if not line:
                i += 1
                continue

            processed = False # 현재 라인이 처리되었는지 여부

            # --- 패턴 매칭 순서 중요! --- 
            
            # 1. 두 줄 형식 삭제 조문 처리 (가장 먼저 확인)
            m_num = patterns['조문번호만'].match(line)
            if m_num and (i + 1 < len(lines)):
                next_line = lines[i+1].strip()
                m_del = patterns['삭제정보만'].match(next_line)
                if m_del:
                    current_id = save_current_context(results, current, law_name, current_id)
                    article_num = m_num.group(1)
                    article_sub_num = m_num.group(2)
                    deletion_date = m_del.group(1)
                    
                    if is_next_expected_article(last_article_num, last_article_sub_num, article_num, article_sub_num):
                        last_article_num = article_num
                        last_article_sub_num = article_sub_num
                        조문번호 = f'제{article_num}조' + (f'의{article_sub_num}' if article_sub_num else '')
                        
                        results.append({
                            'id': current_id,
                            '법률명': law_name,
                            '편번호': current_편번호,
                            '편제목': current_편제목,
                            '장번호': current_장번호,
                            '장제목': current_장제목,
                            '절번호': current_절번호,
                            '절제목': current_절제목,
                            '조문번호': 조문번호,
                            '조문제목': '',
                            '항번호': None,
                            '호번호': None,
                            '목번호': None,
                            '내용': f'삭제 <{deletion_date}>'
                        })
                        current_id += 1
                        
                        # current 및 임시 context 초기화 (다음 조문 준비)
                        temp_context = {
                             '편번호': current_편번호, '편제목': current_편제목, 
                             '장번호': current_장번호, '장제목': current_장제목, 
                             '절번호': current_절번호, '절제목': current_절제목, 
                             '조문번호': None, '조문제목': None, 
                             '항번호': None, '호번호': None, '목번호': None, '내용': ''
                        }
                        current = temp_context.copy()
                        processed = True
                        i += 2 # 두 줄 처리했으므로 2 증가
                        continue
                    else:
                         # 예상 조문 아니면 일반 내용으로 (이런 경우가 있을까?)
                         current['내용'] += (' ' + line) if current['내용'] else line
                         processed = True
                         # 다음 라인(삭제 정보)은 무시? 아니면 내용 추가? -> 일단 내용 추가
                         current['내용'] += (' ' + next_line) if current['내용'] else next_line
                         i += 2 
                         continue
            
            # 2. 편/장/절 처리
            m = patterns['편'].match(line)
            if not processed and m:
                current_id = save_current_context(results, current, law_name, current_id)
                current_편번호 = f'제{m.group(1)}편'
                current_편제목 = m.group(2).strip()
                current_장번호, current_장제목, current_절번호, current_절제목 = None, None, None, None
                last_article_num, last_article_sub_num = None, None
                temp_context = {'편번호': current_편번호, '편제목': current_편제목, '장번호': None, '장제목': None, '절번호': None, '절제목': None, '조문번호': None, '조문제목': None, '항번호': None, '호번호': None, '목번호': None, '내용': ''}
                current = temp_context.copy()
                processed = True
                i += 1
                continue

            m = patterns['장'].match(line)
            if not processed and m:
                current_id = save_current_context(results, current, law_name, current_id)
                current_장번호 = f'제{m.group(1)}장'
                current_장제목 = m.group(2).strip()
                current_절번호, current_절제목 = None, None
                temp_context = {
                    '편번호': current_편번호, '편제목': current_편제목, 
                    '장번호': current_장번호, '장제목': current_장제목, 
                    '절번호': None, '절제목': None, '조문번호': None, '조문제목': None, 
                    '항번호': None, '호번호': None, '목번호': None, '내용': ''
                }
                current = temp_context.copy()
                processed = True
                i += 1
                continue

            m = patterns['절'].match(line)
            if not processed and m:
                current_id = save_current_context(results, current, law_name, current_id)
                current_절번호 = f'제{m.group(1)}절'
                current_절제목 = m.group(2).strip()
                temp_context = {
                    '편번호': current_편번호, '편제목': current_편제목, 
                    '장번호': current_장번호, '장제목': current_장제목, 
                    '절번호': current_절번호, '절제목': current_절제목, 
                    '조문번호': None, '조문제목': None, 
                    '항번호': None, '호번호': None, '목번호': None, '내용': ''
                }
                current = temp_context.copy()
                processed = True
                i += 1
                continue
            
            # 3. 한 줄 형식 삭제 조문
            m = patterns['삭제조문'].match(line)
            if not processed and m:
                current_id = save_current_context(results, current, law_name, current_id)
                article_num = m.group(1)
                article_sub_num = m.group(2)
                deletion_date = m.group(3)
                
                if is_next_expected_article(last_article_num, last_article_sub_num, article_num, article_sub_num):
                    last_article_num = article_num
                    last_article_sub_num = article_sub_num
                    조문번호 = f'제{article_num}조' + (f'의{article_sub_num}' if article_sub_num else '')
                    
                    results.append({
                        'id': current_id,
                        '법률명': law_name,
                        '편번호': current_편번호,
                        '편제목': current_편제목,
                        '장번호': current_장번호,
                        '장제목': current_장제목,
                        '절번호': current_절번호,
                        '절제목': current_절제목,
                        '조문번호': 조문번호,
                        '조문제목': '',
                        '항번호': None,
                        '호번호': None,
                        '목번호': None,
                        '내용': f'삭제 <{deletion_date}>'
                    })
                    current_id += 1
                    
                    # current 및 임시 context 초기화
                    temp_context = {
                         '편번호': current_편번호, '편제목': current_편제목, 
                         '장번호': current_장번호, '장제목': current_장제목, 
                         '절번호': current_절번호, '절제목': current_절제목, 
                         '조문번호': None, '조문제목': None, 
                         '항번호': None, '호번호': None, '목번호': None, '내용': ''
                    }
                    current = temp_context.copy()
                    processed = True
                else:
                    current['내용'] += (' ' + line) if current['내용'] else line
                    processed = True 
                
                i += 1
                continue
                
            # 4. 조문 (제X조의Y(제목) 또는 제X조(제목))
            m = patterns['조문2'].match(line) or patterns['조문'].match(line)
            if not processed and m:
                current_id = save_current_context(results, current, law_name, current_id)
                article_num = m.group(1)
                # 조문2 와 조문 패턴의 그룹 인덱스가 다름
                if '의' in m.re.pattern: # 조문2 패턴
                    article_sub_num = m.group(2)
                    article_title = m.group(3).strip()
                else: # 조문 패턴
                    article_sub_num = m.group(2)
                    article_title = m.group(3).strip() if m.group(3) else ''
                
                if is_next_expected_article(last_article_num, last_article_sub_num, article_num, article_sub_num):
                    last_article_num = article_num
                    last_article_sub_num = article_sub_num
                    조문번호 = f'제{article_num}조' + (f'의{article_sub_num}' if article_sub_num else '')
                    
                    # 새 조문 context 설정 및 임시 저장
                    temp_context = {
                        '편번호': current_편번호,
                        '편제목': current_편제목,
                        '장번호': current_장번호,
                        '장제목': current_장제목,
                        '절번호': current_절번호,
                        '절제목': current_절제목,
                        '조문번호': 조문번호,
                        '조문제목': article_title,
                        '항번호': None,
                        '호번호': None,
                        '목번호': None,
                        '내용': '' 
                    }
                    current = temp_context.copy()
                    processed = True
                else:
                    current['내용'] += (' ' + line) if current['내용'] else line
                    processed = True
                
                i += 1
                continue

            # 5. 항/호/목
            m = patterns['항'].match(line)
            if not processed and m:
                current_id = save_current_context(results, current, law_name, current_id)
                hang_num = get_circled_number(line)
                # 현재 context 복사 후 항 정보 업데이트
                current = temp_context.copy() 
                current['항번호'] = hang_num
                current['호번호'] = None
                current['목번호'] = None
                current['내용'] = line 
                processed = True
                i += 1
                continue

            m = patterns['호'].match(line)
            if not processed and m:
                current_id = save_current_context(results, current, law_name, current_id)
                ho_num = line.split('.')[0].strip()
                current = temp_context.copy()
                current['항번호'] = current['항번호'] # 이전 항 번호 유지
                current['호번호'] = ho_num
                current['목번호'] = None
                current['내용'] = line 
                processed = True
                i += 1
                continue

            m = patterns['목'].match(line)
            if not processed and m:
                current_id = save_current_context(results, current, law_name, current_id)
                mok_num = line.split('.')[0].strip()
                current = temp_context.copy()
                current['항번호'] = current['항번호'] # 이전 항 번호 유지
                current['호번호'] = current['호번호'] # 이전 호 번호 유지
                current['목번호'] = mok_num
                current['내용'] = line
                processed = True
                i += 1
                continue

            # 6. 어떤 패턴에도 해당하지 않으면 현재 context의 내용으로 추가
            if not processed:
                 # current에 유효한 상위 구조 정보가 있을 때만 내용 추가
                 if current['조문번호'] or current['항번호'] or current['호번호'] or current['목번호']:
                      current['내용'] += (' ' + line) if current['내용'] else line
                 # else: 상위 구조 정보 없이 떠다니는 텍스트는 무시 (예: 장 제목 다음 줄 공백 등)
            
            i += 1

        # 마지막 남은 내용 처리
        current_id = save_current_context(results, current, law_name, current_id)

        # --- 후처리: 누락된 상위 정보 채우기 --- 
        final_results = []
        last_valid_context = {}
        for row in results:
            # 현재 행 정보로 last_valid_context 업데이트 (None이 아닌 값만)
            for key in ['편번호', '편제목', '장번호', '장제목', '절번호', '절제목', '조문번호', '조문제목']:
                if row[key] is not None:
                    last_valid_context[key] = row[key]
            
            # 현재 행의 비어있는 상위 정보를 last_valid_context에서 채움
            for key in ['편번호', '편제목', '장번호', '장제목', '절번호', '절제목']:
                 if row[key] is None and key in last_valid_context:
                     row[key] = last_valid_context[key]
                     # 제목 쌍 맞추기 (번호 채울 때 제목도 같이)
                     if key.endswith('번호') and row[key[:-2]+'제목'] is None and key[:-2]+'제목' in last_valid_context:
                         row[key[:-2]+'제목'] = last_valid_context[key[:-2]+'제목']
                         
            # 항/호/목인데 조문 정보가 없는 경우 채우기
            if (row['항번호'] is not None or row['호번호'] is not None or row['목번호'] is not None) and row['조문번호'] is None:
                 if '조문번호' in last_valid_context:
                     row['조문번호'] = last_valid_context['조문번호']
                 if '조문제목' in last_valid_context:
                     row['조문제목'] = last_valid_context['조문제목']
            
            # 유효한 행만 추가 (내용이 있거나, 조문 제목 행)
            if row['내용'] or (row['조문번호'] and row['조문제목'] is not None):
                 final_results.append(row)

        # id 재정렬
        for idx, row in enumerate(final_results):
            row['id'] = idx + 1
            
        with open(output_csv, 'w', newline='', encoding='utf-8-sig') as csvfile:
            fieldnames = ['id', '법률명', '편번호', '편제목', '장번호', '장제목', '절번호', '절제목', '조문번호', '조문제목', '항번호', '호번호', '목번호', '내용']
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)

            writer.writeheader()
            for row in final_results:
                writer.writerow(row)

        print(f'✅ 변환 완료: {output_csv}')
