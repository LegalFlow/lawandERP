from openai import OpenAI
from typing import List, Dict, Any
import anthropic  # Claude API를 위한 라이브러리 추가
import requests.adapters
import urllib3.util.timeout

# 설정 모듈 가져오기
import config

# 타임아웃 설정 변수 정의 (초 단위)
REQUEST_TIMEOUT = config.DEFAULT_TIMEOUT  # config.py에서 정의한 타임아웃 사용

# OpenAI 클라이언트 초기화
client = OpenAI(api_key=config.OPENAI_API_KEY, timeout=REQUEST_TIMEOUT)

# Claude 클라이언트 초기화
claude_client = anthropic.Anthropic(api_key=config.CLAUDE_API_KEY, timeout=REQUEST_TIMEOUT)

def format_articles_for_context(articles: List[Dict[str, Any]]) -> str:
    """
    검색된 법률 조문을 LLM 프롬프트에 사용할 형식으로 가공합니다.
    
    Args:
        articles: 검색된 법률 조문 목록
        
    Returns:
        포맷팅된 조문 문자열
    """
    if not articles:
        return "관련 법률 조문을 찾을 수 없습니다."
    
    formatted_text = "### 관련 법률 조문\n\n"
    
    for i, article in enumerate(articles, 1):
        formatted_text += f"[{i}] {article['law_name']} 제{article['article_no']}조"
        
        if article['article_title']:
            formatted_text += f" ({article['article_title']})"
        
        formatted_text += "\n"
        
        # 계층 구조 정보 추가 (있는 경우)
        hierarchy = []
        if article.get('part_title'):
            hierarchy.append(f"[편] {article['part_title']}")
        if article.get('chapter_title'):
            hierarchy.append(f"[장] {article['chapter_title']}")
        if article.get('section_title'):
            hierarchy.append(f"[절] {article['section_title']}")
            
        if hierarchy:
            formatted_text += f"위치: {' > '.join(hierarchy)}\n"
        
        # 조문 내용
        formatted_text += f"내용: {article['content']}\n\n"
    
    return formatted_text

def generate_response(question: str, articles: List[Dict[str, Any]], history: List[Dict] = None, model: str = None) -> str:
    """
    사용자 질문과 관련 조문을 기반으로 LLM을 사용하여 답변을 생성합니다.
    
    Args:
        question: 사용자 질문
        articles: 관련 법률 조문 목록
        history: 이전 대화 기록 (선택사항)
        model: 사용할 모델 (기본값: config에 지정된 모델)
        
    Returns:
        생성된 답변
    """
    # 관련 조문 포맷팅
    context = format_articles_for_context(articles)
    
    # 시스템 프롬프트 구성
    system_prompt = """
    당신은 대한민국 채무자 회생 및 파산에 관한 법률 전문 AI 비서입니다. 사용자의 법률 질문에 대해 정확하고 객관적인 답변을 제공해야 합니다.

    다음 지침을 따라주세요:
    0. 채무자 회생 및 파산에 관한 법률을 최우선으로 검토하고, 회생위원 실무편람이나 파산관재인 직무편람을 참고한 후 민법 등 기타 법률을 참고하여 답변하세요.
    1. 사용자의 질문이 모호하거나 추가 정보가 필요한 경우엔 먼저 질문한 뒤, 답변을 구성하세요.
    2. 법률 조문의 내용을 직접 인용할 때는 명확히 출처를 표시하세요. (예: 채무자 회생 및 파산에 관한 법률 제580조에 따르면...)
    3. 법률 조문을 근거로 이야기를 하면 해당 법률 조문을 반드시 표시해 주세요.
    4. 응답은 구조화되고 시각적으로 명확하게 구성하세요.
    5. 내용을 단계별로 구분하고, 각 섹션에 적절한 제목을 사용하세요. 
    6. 이모지를 적절히 사용하여 가독성을 높이세요 (✅, 🔹, ⚖️, 📝 등).
    7. 복잡한 정보는 표 형식으로 제공하세요.
    8. 중요한 정보는 굵은 글씨나 표시를 통해 강조하세요.
    9. 비교가 필요한 내용은 비교표를 만들어 명확히 차이점을 보여주세요.

    답변 형식:
    - 친절하고 전문적인 법률 전문가처럼 응답하세요.
    - 먼저 간결한 요약 답변을 제시한 후 세부 내용을 구조화하여 설명하세요.
    - 마지막에는 실무적 함의나 추가 조언을 덧붙이세요.
    - 필요하다면 사례나 예시를 들어 설명하세요.
    """
    
    # 모델 설정
    use_model = model or config.LLM_MODEL
    
    # 모델이 claude로 시작하면 Claude API 사용, 그렇지 않으면 OpenAI API 사용
    if use_model.startswith("claude"):
        try:
            # 최신 버전 API 시도 (messages.create)
            messages = [
                {"role": "user", "content": f"질문: {question}\n\n{context}"}
            ]
            
            # 이전 대화 기록 추가 (있는 경우)
            if history:
                formatted_history = []
                for msg in history:
                    formatted_history.append({"role": msg["role"], "content": msg["content"]})
                
                # 마지막 사용자 메시지를 context와 함께 업데이트
                if formatted_history and formatted_history[-1]["role"] == "user":
                    formatted_history.pop()  # 마지막 사용자 메시지 제거
                    
                messages = formatted_history + messages
            
            # 최신 버전 API 호출 - 타임아웃 적용
            response = claude_client.messages.create(
                model=use_model,
                system=system_prompt,
                messages=messages,
                max_tokens=15000,
                temperature=0.2,
                timeout=REQUEST_TIMEOUT,  # 타임아웃 설정 추가
            )
            
            return response.content[0].text
        except (AttributeError, TypeError) as e:
            print(f"최신 Claude API 호출 실패, 이전 버전 API 시도: {e}")
            try:
                # 이전 버전 API 시도 (completions.create)
                prompt = f"\n\nHuman: {question}\n\n{context}\n\nAssistant:"
                
                # 이전 대화 기록 추가 (있는 경우)
                if history:
                    conversation_history = ""
                    for msg in history:
                        role_prefix = "Human: " if msg["role"] == "user" else "Assistant: "
                        conversation_history += f"\n\n{role_prefix}{msg['content']}"
                    
                    prompt = f"{conversation_history}\n\nHuman: {question}\n\n{context}\n\nAssistant:"
                
                # 이전 버전 API 호출 - 타임아웃 적용
                response = claude_client.completions.create(
                    prompt=f"{system_prompt}\n{prompt}",
                    model=use_model,
                    max_tokens_to_sample=15000,
                    temperature=0.2,
                    stop_sequences=["\n\nHuman:"],
                    timeout=REQUEST_TIMEOUT,  # 타임아웃 설정 추가
                )
                
                return response.completion
            except Exception as e2:
                print(f"이전 Claude API도 실패, OpenAI로 폴백: {e2}")
                use_model = config.GPT_MODEL
                # 아래의 OpenAI 코드로 계속 진행
    
    # OpenAI API 사용
    # 메시지 구성
    messages = [
        {"role": "system", "content": system_prompt},
    ]
    
    # 이전 대화 기록 추가 (있는 경우)
    if history:
        for msg in history:
            messages.append({"role": msg["role"], "content": msg["content"]})
    
    # 현재 질문 및 컨텍스트 추가
    messages.append({"role": "user", "content": f"질문: {question}\n\n{context}"})
    
    # 답변 생성 - 타임아웃 적용 (클라이언트 초기화 시 이미 설정됨)
    response = client.chat.completions.create(
        model=use_model,
        messages=messages,
        temperature=0.2,
        max_tokens=15000,
    )
    
    return response.choices[0].message.content

def generate_general_response(question: str, history: List[Dict] = None, model: str = None) -> str:
    """
    GENERAL 모드: RAG 없이 직접 LLM을 사용하여 답변을 생성합니다.
    
    Args:
        question: 사용자 질문
        history: 이전 대화 기록 (선택사항)
        model: 사용할 모델 (기본값: config에 지정된 모델)
        
    Returns:
        생성된 답변
    """
    # 시스템 프롬프트 구성
    system_prompt = """
    당신은 '개인회생 및 개인파산' 사건을 전문으로 하는 법무법인 소속의 법률 전문가입니다. 당신의 대화 상대는 해당 법무법인의 변호사나 사무직원으로, 사건 해결을 위한 실질적인 솔루션을 찾고 있습니다.

    답변 시 다음 원칙을 준수하세요:

    1. 한국 법률에 기반하여 실무적이고 구체적인 해결책을 제시하세요 (특히 '채무자회생 및 파산에 관한 법률', '민법', '민사집행법', '형법' 참조).

    2. 채무자 입장에서 개인회생과 개인파산을 어떻게 성공적으로 해결할 수 있는지 적극적인 액션 플랜을 제안하세요. 구체적인 단계와 실행 방법을 안내하세요.

    3. 실제 사건에서 쟁점이 될 수 있는 부분과 이를 극복하기 위한 전략적 접근법을 제시하세요.

    4. 가능한 경우 조문 번호와 법률 이름을 명시하여 법적 근거를 명확히 하세요.

    5. 모호하거나 해석의 여지가 있는 부분이 있더라도 "상담이 필요합니다"라는 표현은 사용하지 마세요. 대신 법률 전문가로서 가장 가능성 높은 해석과 전략적 방향을 제시하세요.

    6. 소송 전략, 서류 준비, 법정 대응, 채무자 상담 방법 등 실무자가 사건을 진행하는 데 직접적으로 활용할 수 있는 조언을 제공하세요.

    답변은 구체적이고, 실행 가능하며, 실무에 즉시 적용할 수 있는 내용으로 구성하세요. 이론적 설명보다는 실질적인 해결 방안에 중점을 두세요.
    """
    
    # 모델 설정
    use_model = model or config.LLM_MODEL
    
    # 모델이 claude로 시작하면 Claude API 사용, 그렇지 않으면 OpenAI API 사용
    if use_model.startswith("claude"):
        try:
            # 최신 버전 API 시도 (messages.create)
            messages = []
            
            # 이전 대화 기록 추가 (있는 경우)
            if history:
                for msg in history:
                    messages.append({"role": msg["role"], "content": msg["content"]})
            
            # 현재 질문 추가
            messages.append({"role": "user", "content": question})
            
            # 최신 버전 API 호출 - 타임아웃 적용
            response = claude_client.messages.create(
                model=use_model,
                system=system_prompt,
                messages=messages,
                max_tokens=15000,
                temperature=0.2,
                timeout=REQUEST_TIMEOUT,  # 타임아웃 설정 추가
            )
            
            return response.content[0].text
        except (AttributeError, TypeError) as e:
            print(f"최신 Claude API 호출 실패, 이전 버전 API 시도: {e}")
            try:
                # 이전 버전 API 시도 (completions.create)
                prompt = f"\n\nHuman: {question}\n\nAssistant:"
                
                # 이전 대화 기록 추가 (있는 경우)
                if history:
                    conversation_history = ""
                    for msg in history:
                        role_prefix = "Human: " if msg["role"] == "user" else "Assistant: "
                        conversation_history += f"\n\n{role_prefix}{msg['content']}"
                    
                    prompt = f"{conversation_history}\n\nHuman: {question}\n\nAssistant:"
                
                # 이전 버전 API 호출 - 타임아웃 적용
                response = claude_client.completions.create(
                    prompt=f"{system_prompt}\n{prompt}",
                    model=use_model,
                    max_tokens_to_sample=15000,
                    temperature=0.2,
                    stop_sequences=["\n\nHuman:"],
                    timeout=REQUEST_TIMEOUT,  # 타임아웃 설정 추가
                )
                
                return response.completion
            except Exception as e2:
                print(f"이전 Claude API도 실패, OpenAI로 폴백: {e2}")
                use_model = config.GPT_MODEL
                # 아래의 OpenAI 코드로 계속 진행
    
    # OpenAI API 사용
    # 메시지 구성
    messages = [
        {"role": "system", "content": system_prompt},
    ]
    
    # 이전 대화 기록 추가 (있는 경우)
    if history:
        for msg in history:
            messages.append({"role": msg["role"], "content": msg["content"]})
    
    # 현재 질문 추가
    messages.append({"role": "user", "content": question})
    
    # 답변 생성 - 타임아웃 적용 (클라이언트 초기화 시 이미 설정됨)
    response = client.chat.completions.create(
        model=use_model,
        messages=messages,
        temperature=0.2,
        max_tokens=15000,
    )
    
    return response.choices[0].message.content 