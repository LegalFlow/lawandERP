@echo off
chcp 65001 > nul
echo DB 백업 동기화를 시작합니다...
wsl -d Ubuntu -e python3 /mnt/c/Users/bmh31/OneDrive/lawandERP/scripts/sync_db_backup.py
echo 작업이 완료되었습니다.
pause 