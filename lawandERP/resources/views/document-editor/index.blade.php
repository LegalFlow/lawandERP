            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
        
        // 탭 전환 함수
        function switchTab(tabId) {
            // 모든 탭 콘텐츠를 숨김
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            // 모든 탭 버튼에서 active 클래스 제거
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.classList.contains('btn-primary')) {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                    btn.style.color = '#0d6efd';
                }
            });
            
            // 선택된 탭 콘텐츠 표시
            const selectedPane = document.getElementById(tabId);
            if (selectedPane) {
                selectedPane.classList.add('show', 'active');
            }
            
            // 선택된 탭 버튼 활성화
            const selectedBtn = document.querySelector(`[data-tab="${tabId}"]`);
            if (selectedBtn) {
                selectedBtn.classList.add('active');
                selectedBtn.classList.remove('btn-outline-primary');
                selectedBtn.classList.add('btn-primary');
                selectedBtn.style.color = 'white';
            }
        } 