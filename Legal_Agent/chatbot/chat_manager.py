import json
import os
import time
import sqlite3
import uuid
from typing import List, Dict, Any, Optional
from datetime import datetime

import config
from utils import logger

# 대화 저장 경로
CHAT_HISTORY_DIR = os.path.join(os.path.dirname(__file__), "chat_history")

# 디렉토리가 없으면 생성
os.makedirs(CHAT_HISTORY_DIR, exist_ok=True)

class ChatManager:
    """
    채팅 히스토리 관리 클래스
    """
    
    def __init__(self, user_id: str):
        """
        초기화
        
        Args:
            user_id: 사용자 ID
        """
        self.user_id = user_id
        self.db_path = config.CHAT_DB_PATH
        self._init_db()
        
    def _init_db(self):
        """데이터베이스 초기화"""
        # 데이터베이스 파일의 디렉토리가 존재하는지 확인하고 없으면 생성
        db_dir = os.path.dirname(self.db_path)
        if db_dir and not os.path.exists(db_dir):
            os.makedirs(db_dir, exist_ok=True)
            logger.info(f"데이터베이스 디렉토리 생성: {db_dir}")
        
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        # 대화 테이블 생성
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS conversations (
            id TEXT PRIMARY KEY,
            user_id TEXT NOT NULL,
            title TEXT NOT NULL,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )
        ''')
        
        # 메시지 테이블 생성
        cursor.execute('''
        CREATE TABLE IF NOT EXISTS messages (
            id TEXT PRIMARY KEY,
            conversation_id TEXT NOT NULL,
            role TEXT NOT NULL,
            content TEXT NOT NULL,
            timestamp TEXT NOT NULL,
            metadata TEXT,
            model TEXT,
            FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE
        )
        ''')
        
        conn.commit()
        conn.close()
        
    def create_conversation(self, title: str = "새 대화") -> str:
        """
        새 대화 생성
        
        Args:
            title: 대화 제목
            
        Returns:
            대화 ID
        """
        conversation_id = str(uuid.uuid4())
        now = datetime.now().isoformat()
        
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        cursor.execute(
            "INSERT INTO conversations (id, user_id, title, created_at, updated_at) VALUES (?, ?, ?, ?, ?)",
            (conversation_id, self.user_id, title, now, now)
        )
        
        conn.commit()
        conn.close()
        
        return conversation_id
    
    def add_message(self, conversation_id: str, role: str, content: str, sources: List[Dict] = None, model: str = None) -> str:
        """
        대화에 메시지 추가
        
        Args:
            conversation_id: 대화 ID
            role: 역할 (user/assistant)
            content: 메시지 내용
            sources: 관련 소스 정보 (선택사항)
            model: 사용된 모델 정보 (선택사항)
            
        Returns:
            메시지 ID
        """
        message_id = str(uuid.uuid4())
        now = datetime.now().isoformat()
        
        # 메타데이터 처리
        metadata = None
        if sources:
            metadata = json.dumps({"sources": sources}, ensure_ascii=False)
        
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        # 대화가 존재하는지 확인
        cursor.execute("SELECT id FROM conversations WHERE id = ?", (conversation_id,))
        if not cursor.fetchone():
            conn.close()
            raise ValueError(f"대화 ID가 존재하지 않습니다: {conversation_id}")
        
        # 메시지 추가
        cursor.execute(
            "INSERT INTO messages (id, conversation_id, role, content, timestamp, metadata, model) VALUES (?, ?, ?, ?, ?, ?, ?)",
            (message_id, conversation_id, role, content, now, metadata, model)
        )
        
        # 대화 업데이트 시간 갱신 및 제목 설정
        cursor.execute(
            "UPDATE conversations SET updated_at = ? WHERE id = ?",
            (now, conversation_id)
        )
        
        # 첫 사용자 메시지인 경우, 제목으로 설정
        cursor.execute(
            "SELECT COUNT(*) FROM messages WHERE conversation_id = ?",
            (conversation_id,)
        )
        
        if cursor.fetchone()[0] == 1 and role == "user":
            # 내용이 너무 길면 자르기
            title = content if len(content) <= 50 else content[:47] + "..."
            cursor.execute(
                "UPDATE conversations SET title = ? WHERE id = ?",
                (title, conversation_id)
            )
        
        conn.commit()
        conn.close()
        
        return message_id
    
    def get_conversation(self, conversation_id: str) -> Optional[Dict]:
        """
        대화 조회
        
        Args:
            conversation_id: 대화 ID
            
        Returns:
            대화 정보 (없으면 None)
        """
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        # 대화 정보 조회
        cursor.execute(
            "SELECT id, user_id, title, created_at, updated_at FROM conversations WHERE id = ? AND user_id = ?",
            (conversation_id, self.user_id)
        )
        
        conversation = cursor.fetchone()
        if not conversation:
            conn.close()
            return None
        
        # 대화 데이터 구성
        conversation_data = {
            "id": conversation[0],
            "user_id": conversation[1],
            "title": conversation[2],
            "created_at": conversation[3],
            "updated_at": conversation[4],
            "messages": []
        }
        
        # 메시지 조회
        cursor.execute(
            "SELECT id, role, content, timestamp, metadata, model FROM messages WHERE conversation_id = ? ORDER BY timestamp",
            (conversation_id,)
        )
        
        for message in cursor.fetchall():
            message_data = {
                "id": message[0],
                "role": message[1],
                "content": message[2],
                "timestamp": message[3],
                "model": message[5]  # 모델 정보 추가
            }
            
            # 메타데이터 처리
            if message[4]:
                metadata = json.loads(message[4])
                if "sources" in metadata:
                    message_data["sources"] = metadata["sources"]
                    
            conversation_data["messages"].append(message_data)
            
        conn.close()
        return conversation_data
    
    def get_all_conversations(self) -> List[Dict]:
        """
        사용자의 모든 대화 목록 조회
        
        Returns:
            대화 목록
        """
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        # 대화 정보 조회
        cursor.execute(
            "SELECT id, title, created_at, updated_at FROM conversations WHERE user_id = ? ORDER BY updated_at DESC",
            (self.user_id,)
        )
        
        conversations = []
        for row in cursor.fetchall():
            conversation_id = row[0]
            
            # 해당 대화의 메시지 조회
            cursor.execute(
                "SELECT role, content, model FROM messages WHERE conversation_id = ? ORDER BY timestamp",
                (conversation_id,)
            )
            
            messages = []
            for message in cursor.fetchall():
                messages.append({
                    "role": message[0],
                    "content": message[1],
                    "model": message[2]  # 모델 정보 추가
                })
            
            # 대화 미리보기 (마지막 메시지)
            preview = ""
            if messages:
                last_message = next((m for m in reversed(messages) if m["role"] == "assistant"), None)
                if last_message:
                    preview = last_message["content"][:50] + "..." if len(last_message["content"]) > 50 else last_message["content"]
            
            conversations.append({
                "id": row[0],
                "title": row[1],
                "created_at": row[2],
                "updated_at": row[3],
                "messages": messages,
                "preview": preview
            })
            
        conn.close()
        return conversations
    
    def delete_conversation(self, conversation_id: str) -> bool:
        """
        대화 삭제
        
        Args:
            conversation_id: 대화 ID
            
        Returns:
            성공 여부
        """
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        
        # 해당 사용자의 대화인지 확인
        cursor.execute(
            "SELECT id FROM conversations WHERE id = ? AND user_id = ?",
            (conversation_id, self.user_id)
        )
        
        if not cursor.fetchone():
            conn.close()
            return False
        
        # 대화 삭제 (CASCADE 설정으로 메시지도 함께 삭제됨)
        cursor.execute("DELETE FROM conversations WHERE id = ?", (conversation_id,))
        conn.commit()
        conn.close()
        
        return True 