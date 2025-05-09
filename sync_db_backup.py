#!/usr/bin/env python3
import os
import sys
import subprocess
import datetime
import logging
import gzip
import shutil
from pathlib import Path

# 설정
AWS_HOST = "ec2-user@43.200.156.135"
PEM_FILE = os.path.expanduser("~/newLawAndERP.pem")
REMOTE_BACKUP_DIR = "/home/ec2-user/db_backups"
LOCAL_BACKUP_DIR = "/mnt/c/Users/bmh31/OneDrive/law_firm_db_backup"
LOG_DIR = os.path.join(LOCAL_BACKUP_DIR, "logs")
RETENTION_DAYS = 7

# 데이터베이스 설정
DB_USER = "lawanderp"
DB_PASSWORD = "2496"
DB_NAME = "law_firm_db"

# 로그 설정
def setup_logger():
    """로거 설정"""
    os.makedirs(LOG_DIR, exist_ok=True)
    current_date = datetime.datetime.now().strftime('%Y%m%d')
    log_file = os.path.join(LOG_DIR, f'sync_{current_date}.log')
    
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s',
        handlers=[
            logging.FileHandler(log_file),
            logging.StreamHandler()
        ]
    )
    return logging.getLogger(__name__)

def create_directories():
    """필요한 디렉토리 생성"""
    os.makedirs(LOCAL_BACKUP_DIR, exist_ok=True)
    os.makedirs(LOG_DIR, exist_ok=True)

def cleanup_old_files(directory, pattern, days):
    """오래된 파일 삭제"""
    try:
        current_time = datetime.datetime.now()
        for old_file in Path(directory).glob(pattern):
            file_age = datetime.datetime.fromtimestamp(old_file.stat().st_mtime)
            if (current_time - file_age).days > days:
                old_file.unlink()
                logger.info(f"오래된 파일 삭제: {old_file}")
    except Exception as e:
        logger.error(f"파일 정리 중 오류 발생: {e}")

def get_latest_backup():
    """AWS 인스턴스에서 최신 백업 파일 가져오기"""
    try:
        # 최신 백업 파일 찾기
        cmd = f'ssh -i "{PEM_FILE}" {AWS_HOST} "ls -t {REMOTE_BACKUP_DIR}/*.sql.gz | head -1"'
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
        if result.returncode != 0:
            raise Exception(f"원격 파일 검색 실패: {result.stderr}")
        
        latest_backup = result.stdout.strip()
        if not latest_backup:
            raise Exception("백업 파일을 찾을 수 없습니다")
        
        # 파일 복사
        backup_filename = os.path.basename(latest_backup)
        local_path = os.path.join(LOCAL_BACKUP_DIR, backup_filename)
        
        cmd = f'scp -i "{PEM_FILE}" {AWS_HOST}:{latest_backup} "{local_path}"'
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
        if result.returncode != 0:
            raise Exception(f"파일 복사 실패: {result.stderr}")
        
        logger.info(f"백업 파일 다운로드 완료: {backup_filename}")
        return local_path
    except Exception as e:
        logger.error(f"백업 파일 가져오기 실패: {e}")
        return None

def modify_sql_file(sql_file):
    """SQL 파일에서 NO_AUTO_CREATE_USER 모드 제거"""
    try:
        with open(sql_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # NO_AUTO_CREATE_USER 모드 제거
        modified_content = content.replace("NO_AUTO_CREATE_USER,", "").replace(",NO_AUTO_CREATE_USER", "").replace("NO_AUTO_CREATE_USER", "")
        
        with open(sql_file, 'w', encoding='utf-8') as f:
            f.write(modified_content)
        
        logger.info("SQL 파일 수정 완료")
        return True
    except Exception as e:
        logger.error(f"SQL 파일 수정 실패: {e}")
        return False

def restore_backup(backup_file):
    """백업 파일을 로컬 데이터베이스에 복원"""
    try:
        # 압축 해제
        temp_sql = backup_file.replace('.gz', '')
        with gzip.open(backup_file, 'rb') as f_in:
            with open(temp_sql, 'wb') as f_out:
                shutil.copyfileobj(f_in, f_out)
        
        # SQL 파일 수정
        if not modify_sql_file(temp_sql):
            raise Exception("SQL 파일 수정 실패")
        
        # 데이터베이스 복원
        cmd = f'mysql -u {DB_USER} -p{DB_PASSWORD} {DB_NAME} < "{temp_sql}"'
        result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
        
        # 임시 파일 삭제
        os.remove(temp_sql)
        
        if result.returncode != 0:
            raise Exception(f"데이터베이스 복원 실패: {result.stderr}")
        
        logger.info("데이터베이스 복원 완료")
        return True
    except Exception as e:
        logger.error(f"데이터베이스 복원 실패: {e}")
        return False

def main():
    """메인 실행 함수"""
    global logger
    logger = setup_logger()
    
    logger.info("백업 동기화 시작")
    
    # 디렉토리 생성
    create_directories()
    
    # 오래된 파일 정리
    cleanup_old_files(LOCAL_BACKUP_DIR, "*.sql.gz", RETENTION_DAYS)
    cleanup_old_files(LOG_DIR, "sync_*.log", RETENTION_DAYS)
    
    # 최신 백업 가져오기
    backup_file = get_latest_backup()
    if not backup_file:
        logger.error("백업 파일을 가져올 수 없습니다")
        sys.exit(1)
    
    # 데이터베이스 복원
    if restore_backup(backup_file):
        logger.info("백업 동기화 프로세스 완료")
    else:
        logger.error("백업 동기화 프로세스 실패")
        sys.exit(1)

if __name__ == "__main__":
    main() 