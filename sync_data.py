import pandas as pd
import mysql.connector
from datetime import datetime
import os
import json

# 로그 추가
def log_message(message):
    print(f"{datetime.now()}: {message}")

# fee JSON 파싱 함수
def extract_fee_details(fee_json_str):
    try:
        if pd.isna(fee_json_str):
            return None, None, None, None
        fee_data = json.loads(fee_json_str)
        base = fee_data.get('base', {})
        return (
            base.get('lawyer_fee', 0),
            base.get('total_const_delivery', 0),
            base.get('stamp_fee', 0),
            base.get('total_debt_cert_cost', 0)
        )
    except:
        return 0, 0, 0, 0

# contract_date 처리를 위한 함수
def convert_date(date_val):
    return None if pd.isna(date_val) else date_val

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
    'host': '172.31.20.7',
    'user': 'lawanderp',
    'password': '2496',
    'database': 'law_firm_db'
}

def export_to_csv():
    try:
        log_message("Starting data export to CSV")

        # CONTENT DB 연결
        content_conn = mysql.connector.connect(**rds_base_config, database='CONTENT')
        content_cursor = content_conn.cursor()
        log_message("Connected to CONTENT database")

        # ACCOUNT DB 연결
        account_conn = mysql.connector.connect(**rds_base_config, database='ACCOUNT')
        account_cursor = account_conn.cursor()
        log_message("Connected to ACCOUNT database")

        # TblCase 데이터 추출 (del_flag 포함)
        content_cursor.execute("""
            SELECT idx, case_state, Member_idx, Office_idx, case_type, 
                   court_name, case_number, del_flag
            FROM TblCase
            WHERE Office_idx = 56
        """)
        case_data = pd.DataFrame(content_cursor.fetchall(),
                               columns=['idx', 'case_state', 'Member_idx', 'Office_idx', 
                                      'case_type', 'court_name', 'case_number', 'del_flag'])
        case_data.to_csv(f'{CSV_DIR}/case.csv', index=False)
        log_message(f"Exported {len(case_data)} records from TblCase")

        # TblCSClient 데이터 추출
        content_cursor.execute("""
            SELECT Case_idx, create_dt, name, living_place, phone
            FROM TblCSClient
        """)
        client_data = pd.DataFrame(content_cursor.fetchall(),
                                 columns=['Case_idx', 'create_dt', 'name', 'living_place', 'phone'])
        client_data.to_csv(f'{CSV_DIR}/client.csv', index=False)
        log_message(f"Exported {len(client_data)} records from TblCSClient")

        # TblMember 데이터 추출
        account_cursor.execute("""
            SELECT idx, name
            FROM TblMember
        """)
        member_data = pd.DataFrame(account_cursor.fetchall(),
                                 columns=['idx', 'name'])
        member_data.to_csv(f'{CSV_DIR}/member.csv', index=False)
        log_message(f"Exported {len(member_data)} records from TblMember")

        # TblContract 데이터 추출
        content_cursor.execute("""
            SELECT Case_idx, contract_date, fee
            FROM TblContract
        """)
        contract_data = pd.DataFrame(content_cursor.fetchall(),
                                   columns=['Case_idx', 'contract_date', 'fee'])
        contract_data.to_csv(f'{CSV_DIR}/contract.csv', index=False)
        log_message(f"Exported {len(contract_data)} records from TblContract")

        content_cursor.close()
        content_conn.close()
        account_cursor.close()
        account_conn.close()
        log_message("Completed data export to CSV")
        return True
    except Exception as e:
        log_message(f"Error exporting data: {str(e)}")
        return False

def process_and_import():
    try:
        log_message("Starting data processing and import")

        # CSV 파일 읽기
        log_message("Reading CSV files")
        cases = pd.read_csv(f'{CSV_DIR}/case.csv')
        clients = pd.read_csv(f'{CSV_DIR}/client.csv')
        members = pd.read_csv(f'{CSV_DIR}/member.csv')
        contracts = pd.read_csv(f'{CSV_DIR}/contract.csv')

        log_message(f"Read {len(cases)} cases, {len(clients)} clients, {len(members)} members, {len(contracts)} contracts")

        # Contract fee 데이터 처리
        contracts['fee_data'] = contracts['fee'].apply(extract_fee_details)
        contracts['lawyer_fee'] = contracts['fee_data'].apply(lambda x: x[0] if x else 0)
        contracts['total_const_delivery'] = contracts['fee_data'].apply(lambda x: x[1] if x else 0)
        contracts['stamp_fee'] = contracts['fee_data'].apply(lambda x: x[2] if x else 0)
        contracts['total_debt_cert_cost'] = contracts['fee_data'].apply(lambda x: x[3] if x else 0)

        # 데이터 조인
        log_message("Performing data joins")
        result = cases.merge(
            clients,
            left_on='idx',
            right_on='Case_idx'
        ).merge(
            members,
            left_on='Member_idx',
            right_on='idx',
            how='left'
        ).merge(
            contracts,
            left_on='idx_x',
            right_on='Case_idx',
            how='left'
        )

        log_message(f"Joined data has {len(result)} records")

        # NULL 값 처리를 포함한 데이터 변환
        final_data = pd.DataFrame({
            'idx_TblCase': result['idx_x'].fillna(0).astype(int),
            'create_dt': result['create_dt'],
            'name': result['name_x'].fillna('Unknown'),
            'living_place': result['living_place'].fillna('Unknown'),
            'phone': result['phone'].apply(convert_nan_to_none),
            'Member': result['name_y'].fillna('Unknown'),
            'case_state': result['case_state'].fillna(0).astype(int),
            'court_name': result['court_name'].apply(convert_nan_to_none),
            'case_number': result['case_number'].apply(convert_nan_to_none),
            'case_type': result['case_type'].fillna(0).astype(int),
            'contract_date': result['contract_date'],
            'lawyer_fee': result['lawyer_fee'].fillna(0).astype(float),
            'total_const_delivery': result['total_const_delivery'].fillna(0).astype(float),
            'stamp_fee': result['stamp_fee'].fillna(0).astype(float),
            'total_debt_cert_cost': result['total_debt_cert_cost'].fillna(0).astype(float),
            'note': [None] * len(result),
            'div_case': [0] * len(result),
            'del_flag': result['del_flag'].fillna(0).astype(int)
        })

        # MariaDB에 데이터 입력
        log_message("Connecting to target database")
        conn = mysql.connector.connect(**target_config)
        cursor = conn.cursor()

        # 새 데이터 입력
        log_message("Starting data upsert")
        for _, row in final_data.iterrows():
            try:
                cursor.execute("""
                    INSERT INTO target_table (
                        idx_TblCase, create_dt, name, living_place, phone,
                        Member, case_state, court_name, case_number, case_type, 
                        contract_date, lawyer_fee, total_const_delivery, 
                        stamp_fee, total_debt_cert_cost, note, div_case, del_flag
                    ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE
                        create_dt = VALUES(create_dt),
                        name = VALUES(name),
                        living_place = VALUES(living_place),
                        phone = VALUES(phone),
                        Member = VALUES(Member),
                        case_state = VALUES(case_state),
                        court_name = VALUES(court_name),
                        case_number = VALUES(case_number),
                        case_type = VALUES(case_type),
                        contract_date = VALUES(contract_date),
                        lawyer_fee = VALUES(lawyer_fee),
                        total_const_delivery = VALUES(total_const_delivery),
                        stamp_fee = VALUES(stamp_fee),
                        total_debt_cert_cost = VALUES(total_debt_cert_cost),
                        del_flag = VALUES(del_flag)
                """, (
                    int(row['idx_TblCase']),
                    row['create_dt'],
                    str(row['name']),
                    str(row['living_place']),
                    row['phone'],
                    str(row['Member']),
                    int(row['case_state']),
                    row['court_name'],
                    row['case_number'],
                    int(row['case_type']),
                    convert_date(row['contract_date']),
                    float(row['lawyer_fee']),
                    float(row['total_const_delivery']),
                    float(row['stamp_fee']),
                    float(row['total_debt_cert_cost']),
                    None,
                    0,
                    int(row['del_flag'])
                ))
            except Exception as e:
                log_message(f"Error upserting row: {str(row)}")
                log_message(f"Error details: {str(e)}")
                continue

        # case_assignments 테이블의 기존 데이터 업데이트
        log_message("Updating existing case_assignments records")
        cursor.execute("""
            UPDATE case_assignments ca
            INNER JOIN target_table tt ON ca.case_idx = tt.idx_TblCase
            SET ca.court_name = tt.court_name,
                ca.case_number = tt.case_number
        """)

        conn.commit()
        log_message(f"Processed {len(final_data)} records")

        cursor.close()
        conn.close()
        log_message("Completed data import and update")
        return True
    except Exception as e:
        log_message(f"Error processing and importing data: {str(e)}")
        return False

if __name__ == "__main__":
    log_message("Starting sync process")
    if export_to_csv():
        log_message("Export successful, starting import")
        process_and_import()
    log_message("Sync process completed")