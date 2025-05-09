#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
PDF 파일에서 텍스트를 추출하는 스크립트
사용법: python pdf_to_text.py <pdf_file_path>

특수 조건:
2. 머릿말 제외 ('개인정보유출주의...')
"""

import sys
import os
import re
import PyPDF2
from io import StringIO

def extract_text_from_pdf(pdf_path):
    """
    PDF 파일에서 텍스트를 추출합니다.
    특수 조건에 맞게 텍스트를 필터링합니다.
    """
    try:
        # PDF 파일 열기
        with open(pdf_path, 'rb') as file:
            try:
                pdf_reader = PyPDF2.PdfReader(file)  # PdfFileReader 대신 PdfReader 사용 (최신 버전)
                
                # 모든 페이지의 텍스트 추출
                all_text = ""
                for page_num in range(len(pdf_reader.pages)):  # numPages 대신 len(pdf_reader.pages) 사용
                    page = pdf_reader.pages[page_num]  # getPage() 대신 pages[] 사용
                    try:
                        # 유효하지 않은 문자 처리
                        page_text = page.extract_text()  # extractText() 대신 extract_text() 사용
                        if page_text:
                            # 유효한 문자만 필터링
                            page_text = "".join(char for char in page_text if ord(char) < 0x10000)
                            all_text += page_text + "\n"
                    except UnicodeDecodeError:
                        print(f"페이지 {page_num+1}에서 유니코드 디코딩 오류 발생")
                
                # 텍스트 처리
                return process_text(all_text)
            except Exception as e:
                return f"PDF 파싱 오류: {str(e)}"
    except Exception as e:
        return f"파일 오류: {str(e)}"

def process_text(text):
    """
    추출된 텍스트를 특수 조건에 맞게 처리합니다.
    1. '채무자는' 또는 '신청인은'부터 시작하여 날짜 형식 앞까지 추출
    2. 머릿말 제외 ('개인정보유출주의...')
    """
    try:
        # 유니코드 오류 방지를 위한 텍스트 정제
        text = ''.join(char for char in text if ord(char) < 0x10000)
        
        # 2. 머릿말 제외 ('개인정보유출주의...')
        text = re.sub(r'개인정보유출주의.*?[\r\n]', '', text, flags=re.DOTALL | re.MULTILINE)
        
        # 1. 본문 추출: '채무자는' 또는 '신청인은'부터 날짜 형식 앞까지
        # 날짜 패턴 (YYYY. MM. DD. 형식)
        date_pattern = r'\d{4}\.\s*\d{1,2}\.\s*\d{1,2}\.'
        
        # 시작 위치 찾기 ('채무자는' 또는 '신청인은')
        start_pos = -1
        if '채무자는' in text:
            start_pos = text.find('채무자는')
        elif '신청인은' in text:
            start_pos = text.find('신청인은')
        
        # 종료 위치 찾기 (마지막 날짜 형식 앞)
        end_pos = -1
        date_matches = list(re.finditer(date_pattern, text))
        if date_matches:
            last_date_match = date_matches[-1]
            end_pos = last_date_match.start()
        
        # 시작과 종료 위치가 유효한 경우에만 추출
        if start_pos != -1 and end_pos != -1 and start_pos < end_pos:
            return text[start_pos:end_pos].strip()
        
        # 시작 위치만 있는 경우
        if start_pos != -1:
            return text[start_pos:].strip()
        
        # 조건에 맞지 않으면 전체 텍스트 반환
        return text.strip()
    except Exception as e:
        return f"텍스트 처리 중 오류 발생: {str(e)}"

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("사용법: python pdf_to_text.py <pdf_file_path>")
        sys.exit(1)
    
    pdf_path = sys.argv[1]
    if not os.path.exists(pdf_path):
        print(f"파일을 찾을 수 없음: {pdf_path}")
        sys.exit(1)
    
    extracted_text = extract_text_from_pdf(pdf_path)
    
    # 인코딩 이슈를 방지하기 위해 UTF-8로 명시적 인코딩
    print(extracted_text) 