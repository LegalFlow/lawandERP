import os
import PyPDF2
import glob

def convert_pdf_to_text(pdf_path, txt_path):
    """PDF 파일을 텍스트 파일로 변환합니다."""
    try:
        with open(pdf_path, 'rb') as pdf_file:
            # PDF 리더 객체 생성
            pdf_reader = PyPDF2.PdfReader(pdf_file)
            
            # 모든 페이지의 텍스트 추출
            text = ""
            for page_num in range(len(pdf_reader.pages)):
                text += pdf_reader.pages[page_num].extract_text()
            
            # 추출된 텍스트를 파일로 저장
            with open(txt_path, 'w', encoding='utf-8') as txt_file:
                txt_file.write(text)
            
            print(f"변환 완료: {pdf_path} -> {txt_path}")
            return True
    except Exception as e:
        print(f"변환 실패 {pdf_path}: {str(e)}")
        return False

def main():
    # raw_pdf 폴더 경로
    raw_pdf_folder = os.path.join(os.path.dirname(os.path.abspath(__file__)), "raw_pdf")
    
    # raw_pdf 폴더가 존재하는지 확인
    if not os.path.exists(raw_pdf_folder):
        print(f"'{raw_pdf_folder}' 폴더가 존재하지 않습니다. 폴더를 생성합니다.")
        os.makedirs(raw_pdf_folder)
        print(f"'{raw_pdf_folder}' 폴더가 생성되었습니다. PDF 파일을 이 폴더에 넣으세요.")
        return
    
    # raw_pdf 폴더 내의 모든 PDF 파일 찾기
    pdf_files = glob.glob(os.path.join(raw_pdf_folder, "*.pdf"))
    
    if not pdf_files:
        print(f"'{raw_pdf_folder}' 폴더에 PDF 파일이 없습니다.")
        return
    
    # 각 PDF 파일을 텍스트로 변환
    success_count = 0
    for pdf_file in pdf_files:
        # 출력 텍스트 파일 경로 생성
        txt_file = os.path.splitext(pdf_file)[0] + ".txt"
        
        # PDF를 텍스트로 변환
        if convert_pdf_to_text(pdf_file, txt_file):
            success_count += 1
    
    # 결과 출력
    print(f"\n변환 완료: {success_count}/{len(pdf_files)} 파일 변환됨")

if __name__ == "__main__":
    main() 