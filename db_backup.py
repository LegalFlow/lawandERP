#!/usr/bin/env python3
import os
import sys
import subprocess
import datetime
import gzip
import logging
import glob
from pathlib import Path

# 설정
DB_USER = "lawanderp"
DB_PASSWORD = "2496"
DB_NAME = "law_firm_db"
BACKUP_DIR = os.path.expanduser("~/db_backups")
LOG_DIR = os.path.join(BACKUP_DIR, "logs")
BACKUP_RETENTION_DAYS = 7
LOG_RETENTION_DAYS = 7

# 로그 파일 설정
def setup_logger():
    """로거 설정"""
    os.makedirs(LOG_DIR, exist_ok=True)
    current_date = datetime.datetime.now().strftime('%Y%m%d')
    log_file = os.path.join(LOG_DIR, f'backup_{current_date}.log')
    
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s',
        handlers=[
            logging.FileHandler(log_file),
            logging.StreamHandler()
        ]
    )
    return logging.getLogger(__name__)

def cleanup_old_logs():
    """오래된 로그 파일 삭제"""
    try:
        current_time = datetime.datetime.now()
        for log_file in Path(LOG_DIR).glob("backup_*.log"):
            file_age = datetime.datetime.fromtimestamp(log_file.stat().st_mtime)
            if (current_time - file_age).days > LOG_RETENTION_DAYS:
                log_file.unlink()
                logger.info(f"오래된 로그 파일 삭제: {log_file}")
    except Exception as e:
        logger.error(f"로그 파일 정리 중 오류 발생: {e}")

def create_backup_dir():
    """백업 디렉토리 생성"""
    try:
        os.makedirs(BACKUP_DIR, exist_ok=True)
        logger.info(f"백업 디렉토리 확인: {BACKUP_DIR}")
    except Exception as e:
        logger.error(f"백업 디렉토리 생성 실패: {e}")
        sys.exit(1)

def cleanup_old_backups():
    """오래된 백업 파일 삭제"""
    try:
        current_time = datetime.datetime.now()
        for backup_file in Path(BACKUP_DIR).glob("*.sql.gz"):
            file_age = datetime.datetime.fromtimestamp(backup_file.stat().st_mtime)
            if (current_time - file_age).days > BACKUP_RETENTION_DAYS:
                backup_file.unlink()
                logger.info(f"오래된 백업 파일 삭제: {backup_file}")
    except Exception as e:
        logger.error(f"백업 파일 정리 중 오류 발생: {e}")

def create_backup():
    """데이터베이스 백업 생성 및 압축"""
    timestamp = datetime.datetime.now().strftime('%Y%m%d_%H%M%S')
    backup_file = os.path.join(BACKUP_DIR, f"law_firm_db_backup_{timestamp}.sql")
    compressed_file = f"{backup_file}.gz"

    try:
        # mysqldump 실행
        with open(backup_file, 'w') as f:
            subprocess.run([
                'mysqldump',
                '-u', DB_USER,
                f'-p{DB_PASSWORD}',
                DB_NAME
            ], stdout=f, stderr=subprocess.PIPE, check=True)
        
        # 압축
        with open(backup_file, 'rb') as f_in:
            with gzip.open(compressed_file, 'wb') as f_out:
                f_out.writelines(f_in)
        
        # 원본 파일 삭제
        os.remove(backup_file)
        
        logger.info(f"백업 완료: {compressed_file}")
        return True
    except subprocess.CalledProcessError as e:
        logger.error(f"mysqldump 실행 실패: {e.stderr.decode()}")
        return False
    except Exception as e:
        logger.error(f"백업 중 오류 발생: {e}")
        return False

def main():
    """메인 실행 함수"""
    global logger
    logger = setup_logger()
    
    logger.info("데이터베이스 백업 시작")
    
    create_backup_dir()
    cleanup_old_logs()    # 오래된 로그 파일 정리
    cleanup_old_backups() # 오래된 백업 파일 정리
    
    if create_backup():
        logger.info("백업 프로세스 완료")
    else:
        logger.error("백업 프로세스 실패")
        sys.exit(1)

if __name__ == "__main__":
    main() 