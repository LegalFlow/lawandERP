import pandas as pd
import mysql.connector
from datetime import datetime
import os
import json

# 로그 추가
def log_message(message):
    print(f"{datetime.now()}: {message}")

# NULL 값 처리를 위한 헬퍼 함수
def convert_nan_to_none(value):
    return None if pd.isna(value) else str(value)

# CSV 저장 디렉토리 생성
CSV_DIR = '/home/ec2-user/csv_data'
if not os.path.exists(CSV_DIR):
    os.makedirs(CSV_DIR)
    log_message(f"Created directory: {CSV_DIR}")

# RDS 공통 설정
rds_base_config = {
    'host': 'release-loworker.cyohxeybwmoy.ap-northeast-2.rds.amazonaws.com',
    'user': 'legalflow',
    'password': 'AwsDBLF0817'
}

# 타겟 DB 연결 설정
target_config = {
    'host': '43.200.156.135',
    'user': 'lawanderp',
    'password': '2496',
    'database': 'law_firm_db'
}

# 추가 테이블 데이터를 CSV로 내보내는 함수
def export_additional_tables_to_csv():
    try:
        log_message("Starting additional data export to CSV")

        # CONTENT DB 연결
        content_conn = mysql.connector.connect(**rds_base_config, database='CONTENT')
        content_cursor = content_conn.cursor()
        log_message("Connected to CONTENT database")

        # ACCOUNT DB 연결
        account_conn = mysql.connector.connect(**rds_base_config, database='ACCOUNT')
        account_cursor = account_conn.cursor()
        log_message("Connected to ACCOUNT database")

        # TblLawyerFee 데이터 추출
        content_cursor.execute("""
            SELECT case_idx, bank, memo
            FROM TblLawyerFee
        """)
        lawyer_fee_data = pd.DataFrame(content_cursor.fetchall(),
                                     columns=['case_idx', 'bank', 'memo'])
        lawyer_fee_data.to_csv(f'{CSV_DIR}/lawyer_fee.csv', index=False)
        log_message(f"Exported {len(lawyer_fee_data)} records from TblLawyerFee")

        # TblLawyerFeeDetail 데이터 추출
        content_cursor.execute("""
            SELECT idx, case_idx, detail, alarm_dt
            FROM TblLawyerFeeDetail
        """)
        lawyer_fee_detail_data = pd.DataFrame(content_cursor.fetchall(),
                                           columns=['idx', 'case_idx', 'detail', 'alarm_dt'])
        lawyer_fee_detail_data.to_csv(f'{CSV_DIR}/lawyer_fee_detail.csv', index=False)
        log_message(f"Exported {len(lawyer_fee_detail_data)} records from TblLawyerFeeDetail")

        # TblCaseDebtDoc 데이터 추출
        content_cursor.execute("""
            SELECT idx, case_idx, memo, docs
            FROM TblCaseDebtDoc
        """)
        case_debt_doc_data = pd.DataFrame(content_cursor.fetchall(),
                                        columns=['idx', 'case_idx', 'memo', 'docs'])
        case_debt_doc_data.to_csv(f'{CSV_DIR}/case_debt_doc.csv', index=False)
        log_message(f"Exported {len(case_debt_doc_data)} records from TblCaseDebtDoc")

        # TblCaseApplyDocs 데이터 추출
        content_cursor.execute("""
            SELECT idx, case_idx, memo, details
            FROM TblCaseApplyDocs
        """)
        case_apply_docs_data = pd.DataFrame(content_cursor.fetchall(),
                                          columns=['idx', 'case_idx', 'memo', 'details'])
        case_apply_docs_data.to_csv(f'{CSV_DIR}/case_apply_docs.csv', index=False)
        log_message(f"Exported {len(case_apply_docs_data)} records from TblCaseApplyDocs")

        # TblMember 데이터 추출
        account_cursor.execute("""
            SELECT idx, type, member_id, password, name, position, email, phone, 
                   birthday, gender, stamp_url, create_dt, update_dt, Office_idx, 
                   info_recv_agree, tmp_uuid, picture_url, stamp_use_company
            FROM TblMember
        """)
        member_data = pd.DataFrame(account_cursor.fetchall(),
                                 columns=['idx', 'type', 'member_id', 'password', 'name', 
                                        'position', 'email', 'phone', 'birthday', 'gender', 
                                        'stamp_url', 'create_dt', 'update_dt', 'Office_idx', 
                                        'info_recv_agree', 'tmp_uuid', 'picture_url', 
                                        'stamp_use_company'])
        member_data.to_csv(f'{CSV_DIR}/member_full.csv', index=False)
        log_message(f"Exported {len(member_data)} records from TblMember")

        # TblMembership 데이터 추출
        account_cursor.execute("""
            SELECT idx, Office_idx, brc_membership, brc_advanced_s_dt, 
                   brc_advanced_e_dt, cdm_membership, cdm_advanced_s_dt, 
                   cdm_advanced_e_dt
            FROM TblMembership
        """)
        membership_data = pd.DataFrame(account_cursor.fetchall(),
                                     columns=['idx', 'Office_idx', 'brc_membership', 
                                            'brc_advanced_s_dt', 'brc_advanced_e_dt', 
                                            'cdm_membership', 'cdm_advanced_s_dt', 
                                            'cdm_advanced_e_dt'])
        membership_data.to_csv(f'{CSV_DIR}/membership.csv', index=False)
        log_message(f"Exported {len(membership_data)} records from TblMembership")

        content_cursor.close()
        content_conn.close()
        account_cursor.close()
        account_conn.close()
        log_message("Completed additional data export to CSV")
        return True
    except Exception as e:
        log_message(f"Error exporting additional data: {str(e)}")
        return False

# 추가 테이블 데이터를 처리하고 대상 DB로 가져오는 함수
def process_and_import_additional_tables():
    try:
        log_message("Starting additional data processing and import")

        # CSV 파일 읽기
        log_message("Reading additional CSV files")
        lawyer_fee = pd.read_csv(f'{CSV_DIR}/lawyer_fee.csv')
        lawyer_fee_detail = pd.read_csv(f'{CSV_DIR}/lawyer_fee_detail.csv')
        case_debt_doc = pd.read_csv(f'{CSV_DIR}/case_debt_doc.csv')
        case_apply_docs = pd.read_csv(f'{CSV_DIR}/case_apply_docs.csv')
        member_full = pd.read_csv(f'{CSV_DIR}/member_full.csv')
        membership = pd.read_csv(f'{CSV_DIR}/membership.csv')

        log_message(f"Read {len(lawyer_fee)} lawyer fees, {len(lawyer_fee_detail)} lawyer fee details, "
                  f"{len(case_debt_doc)} case debt docs, {len(case_apply_docs)} case apply docs, "
                  f"{len(member_full)} members, {len(membership)} memberships")

        # MariaDB에 데이터 입력
        log_message("Connecting to target database")
        conn = mysql.connector.connect(**target_config)
        cursor = conn.cursor()

        # TblLawyerFee 테이블 동기화
        log_message("Synchronizing TblLawyerFee data")
        # 현재 대상 DB의 데이터 가져오기
        cursor.execute("SELECT case_idx FROM TblLawyerFee")
        existing_case_idx = set([row[0] for row in cursor.fetchall()])
        # 소스 데이터의 case_idx 세트
        source_case_idx = set(lawyer_fee['case_idx'].tolist())
        # 삭제해야 할 데이터 찾기
        to_delete = existing_case_idx - source_case_idx
        
        # 삭제 작업 수행
        for case_idx in to_delete:
            try:
                cursor.execute("DELETE FROM TblLawyerFee WHERE case_idx = %s", (case_idx,))
                log_message(f"Deleted case_idx {case_idx} from TblLawyerFee")
            except Exception as e:
                log_message(f"Error deleting from TblLawyerFee: {str(e)}")
                
        # 새 데이터 추가 및 기존 데이터 업데이트
        for _, row in lawyer_fee.iterrows():
            try:
                cursor.execute("""
                    INSERT INTO TblLawyerFee (case_idx, bank, memo)
                    VALUES (%s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                        bank = VALUES(bank),
                        memo = VALUES(memo)
                """, (
                    int(row['case_idx']),
                    str(row['bank']),
                    str(row['memo'])
                ))
            except Exception as e:
                log_message(f"Error upserting TblLawyerFee row: {str(e)}")
                continue

        # TblLawyerFeeDetail 테이블 동기화
        log_message("Synchronizing TblLawyerFeeDetail data")
        # 현재 대상 DB의 데이터 가져오기
        cursor.execute("SELECT idx FROM TblLawyerFeeDetail")
        existing_idx = set([row[0] for row in cursor.fetchall()])
        # 소스 데이터의 idx 세트
        source_idx = set(lawyer_fee_detail['idx'].tolist())
        # 삭제해야 할 데이터 찾기
        to_delete = existing_idx - source_idx
        
        # 삭제 작업 수행
        for idx in to_delete:
            try:
                cursor.execute("DELETE FROM TblLawyerFeeDetail WHERE idx = %s", (idx,))
                log_message(f"Deleted idx {idx} from TblLawyerFeeDetail")
            except Exception as e:
                log_message(f"Error deleting from TblLawyerFeeDetail: {str(e)}")
        
        # 배치 처리로 새 데이터 추가 및 기존 데이터 업데이트
        batch_size = 1000  # 한 번에 처리할 레코드 수
        total_batches = len(lawyer_fee_detail) // batch_size + 1
        
        for i in range(total_batches):
            start_idx = i * batch_size
            end_idx = min((i + 1) * batch_size, len(lawyer_fee_detail))
            batch = lawyer_fee_detail.iloc[start_idx:end_idx]
            
            if batch.empty:
                continue
                
            for _, row in batch.iterrows():
                try:
                    cursor.execute("""
                        INSERT INTO TblLawyerFeeDetail (idx, case_idx, detail, alarm_dt)
                        VALUES (%s, %s, %s, %s)
                        ON DUPLICATE KEY UPDATE
                            case_idx = VALUES(case_idx),
                            detail = VALUES(detail),
                            alarm_dt = VALUES(alarm_dt)
                    """, (
                        int(row['idx']),
                        int(row['case_idx']),
                        row['detail'],
                        convert_nan_to_none(row['alarm_dt'])
                    ))
                except Exception as e:
                    log_message(f"Error upserting TblLawyerFeeDetail row: {str(e)}")
                    continue
            
            conn.commit()
            log_message(f"Processed TblLawyerFeeDetail batch {i+1}/{total_batches}")

        # TblCaseDebtDoc 테이블 동기화
        log_message("Synchronizing TblCaseDebtDoc data")
        # 현재 대상 DB의 데이터 가져오기
        cursor.execute("SELECT idx FROM TblCaseDebtDoc")
        existing_idx = set([row[0] for row in cursor.fetchall()])
        # 소스 데이터의 idx 세트
        source_idx = set(case_debt_doc['idx'].tolist())
        # 삭제해야 할 데이터 찾기
        to_delete = existing_idx - source_idx
        
        # 삭제 작업 수행
        for idx in to_delete:
            try:
                cursor.execute("DELETE FROM TblCaseDebtDoc WHERE idx = %s", (idx,))
                log_message(f"Deleted idx {idx} from TblCaseDebtDoc")
            except Exception as e:
                log_message(f"Error deleting from TblCaseDebtDoc: {str(e)}")
        
        # 새 데이터 추가 및 기존 데이터 업데이트
        for _, row in case_debt_doc.iterrows():
            try:
                cursor.execute("""
                    INSERT INTO TblCaseDebtDoc (idx, case_idx, memo, docs)
                    VALUES (%s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                        case_idx = VALUES(case_idx),
                        memo = VALUES(memo),
                        docs = VALUES(docs)
                """, (
                    int(row['idx']),
                    int(row['case_idx']),
                    convert_nan_to_none(row['memo']),
                    row['docs']
                ))
            except Exception as e:
                log_message(f"Error upserting TblCaseDebtDoc row: {str(e)}")
                continue

        # TblCaseApplyDocs 테이블 동기화
        log_message("Synchronizing TblCaseApplyDocs data")
        # 현재 대상 DB의 데이터 가져오기
        cursor.execute("SELECT idx FROM TblCaseApplyDocs")
        existing_idx = set([row[0] for row in cursor.fetchall()])
        # 소스 데이터의 idx 세트
        source_idx = set(case_apply_docs['idx'].tolist())
        # 삭제해야 할 데이터 찾기
        to_delete = existing_idx - source_idx
        
        # 삭제 작업 수행
        for idx in to_delete:
            try:
                cursor.execute("DELETE FROM TblCaseApplyDocs WHERE idx = %s", (idx,))
                log_message(f"Deleted idx {idx} from TblCaseApplyDocs")
            except Exception as e:
                log_message(f"Error deleting from TblCaseApplyDocs: {str(e)}")
        
        # 새 데이터 추가 및 기존 데이터 업데이트
        for _, row in case_apply_docs.iterrows():
            try:
                cursor.execute("""
                    INSERT INTO TblCaseApplyDocs (idx, case_idx, memo, details)
                    VALUES (%s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                        case_idx = VALUES(case_idx),
                        memo = VALUES(memo),
                        details = VALUES(details)
                """, (
                    int(row['idx']),
                    int(row['case_idx']),
                    convert_nan_to_none(row['memo']),
                    row['details']
                ))
            except Exception as e:
                log_message(f"Error upserting TblCaseApplyDocs row: {str(e)}")
                continue

        # TblMember 테이블 동기화
        log_message("Synchronizing TblMember data")
        # 현재 대상 DB의 데이터 가져오기
        cursor.execute("SELECT idx FROM TblMember")
        existing_idx = set([row[0] for row in cursor.fetchall()])
        # 소스 데이터의 idx 세트
        source_idx = set(member_full['idx'].tolist())
        # 삭제해야 할 데이터 찾기
        to_delete = existing_idx - source_idx
        
        # 삭제 작업 수행
        for idx in to_delete:
            try:
                cursor.execute("DELETE FROM TblMember WHERE idx = %s", (idx,))
                log_message(f"Deleted idx {idx} from TblMember")
            except Exception as e:
                log_message(f"Error deleting from TblMember: {str(e)}")
        
        # 새 데이터 추가 및 기존 데이터 업데이트
        for _, row in member_full.iterrows():
            try:
                cursor.execute("""
                    INSERT INTO TblMember (
                        idx, type, member_id, password, name, position, email, phone, 
                        birthday, gender, stamp_url, create_dt, update_dt, Office_idx, 
                        info_recv_agree, tmp_uuid, picture_url, stamp_use_company
                    ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                        type = VALUES(type),
                        member_id = VALUES(member_id),
                        password = VALUES(password),
                        name = VALUES(name),
                        position = VALUES(position),
                        email = VALUES(email),
                        phone = VALUES(phone),
                        birthday = VALUES(birthday),
                        gender = VALUES(gender),
                        stamp_url = VALUES(stamp_url),
                        create_dt = VALUES(create_dt),
                        update_dt = VALUES(update_dt),
                        Office_idx = VALUES(Office_idx),
                        info_recv_agree = VALUES(info_recv_agree),
                        tmp_uuid = VALUES(tmp_uuid),
                        picture_url = VALUES(picture_url),
                        stamp_use_company = VALUES(stamp_use_company)
                """, (
                    int(row['idx']),
                    int(row['type']),
                    str(row['member_id']),
                    str(row['password']),
                    str(row['name']),
                    convert_nan_to_none(row['position']),
                    str(row['email']),
                    str(row['phone']),
                    str(row['birthday']),
                    int(row['gender']),
                    convert_nan_to_none(row['stamp_url']),
                    row['create_dt'],
                    row['update_dt'],
                    int(row['Office_idx']),
                    int(row['info_recv_agree']),
                    convert_nan_to_none(row['tmp_uuid']),
                    convert_nan_to_none(row['picture_url']),
                    int(row['stamp_use_company'])
                ))
            except Exception as e:
                log_message(f"Error upserting TblMember row: {str(e)}")
                continue

        # TblMembership 테이블 동기화
        log_message("Synchronizing TblMembership data")
        # 현재 대상 DB의 데이터 가져오기
        cursor.execute("SELECT idx FROM TblMembership")
        existing_idx = set([row[0] for row in cursor.fetchall()])
        # 소스 데이터의 idx 세트
        source_idx = set(membership['idx'].tolist())
        # 삭제해야 할 데이터 찾기
        to_delete = existing_idx - source_idx
        
        # 삭제 작업 수행
        for idx in to_delete:
            try:
                cursor.execute("DELETE FROM TblMembership WHERE idx = %s", (idx,))
                log_message(f"Deleted idx {idx} from TblMembership")
            except Exception as e:
                log_message(f"Error deleting from TblMembership: {str(e)}")
        
        # 새 데이터 추가 및 기존 데이터 업데이트
        for _, row in membership.iterrows():
            try:
                cursor.execute("""
                    INSERT INTO TblMembership (
                        idx, Office_idx, brc_membership, brc_advanced_s_dt, 
                        brc_advanced_e_dt, cdm_membership, cdm_advanced_s_dt, 
                        cdm_advanced_e_dt
                    ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                        Office_idx = VALUES(Office_idx),
                        brc_membership = VALUES(brc_membership),
                        brc_advanced_s_dt = VALUES(brc_advanced_s_dt),
                        brc_advanced_e_dt = VALUES(brc_advanced_e_dt),
                        cdm_membership = VALUES(cdm_membership),
                        cdm_advanced_s_dt = VALUES(cdm_advanced_s_dt),
                        cdm_advanced_e_dt = VALUES(cdm_advanced_e_dt)
                """, (
                    int(row['idx']),
                    int(row['Office_idx']) if pd.notna(row['Office_idx']) else None,
                    int(row['brc_membership']),
                    row['brc_advanced_s_dt'],
                    row['brc_advanced_e_dt'],
                    int(row['cdm_membership']),
                    row['cdm_advanced_s_dt'],
                    row['cdm_advanced_e_dt']
                ))
            except Exception as e:
                log_message(f"Error upserting TblMembership row: {str(e)}")
                continue

        conn.commit()
        log_message("Processed all additional tables")

        cursor.close()
        conn.close()
        log_message("Completed additional data import and update")
        return True
    except Exception as e:
        log_message(f"Error processing and importing additional data: {str(e)}")
        return False

if __name__ == "__main__":
    log_message("Starting additional tables sync process")
    success = export_additional_tables_to_csv()
    if success:
        log_message("Additional export successful, starting additional import")
        process_and_import_additional_tables()
    log_message("Additional tables sync process completed") 