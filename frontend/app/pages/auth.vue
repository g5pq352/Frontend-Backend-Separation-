<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { API_CONFIG } from '~/config/paths'

const router = useRouter()

// 狀態
const password = ref('')
const isLoading = ref(true)
const isVerifying = ref(false)
const errorMessage = ref('')
const accessInfo = ref(null)

// 檢查存取權限
const checkAccess = async () => {
    try {
        isLoading.value = true

        const response = await fetch(`${API_CONFIG.BASE_URL}/check-access`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-API-TOKEN': API_CONFIG.TOKEN
            },
            credentials: 'include'
        })

        const data = await response.json()

        if (data.status === 'success') {
            accessInfo.value = data.data

            // 如果已經有存取權限,直接導向首頁
            if (data.data.has_access) {
                router.push('/')
            }
        }
    } catch (error) {
        console.error('檢查存取權限失敗:', error)
        errorMessage.value = '無法連接伺服器,請稍後再試'
    } finally {
        isLoading.value = false
    }
}

// 驗證密碼
const verifyPassword = async () => {
    if (!password.value) {
        errorMessage.value = '請輸入密碼'
        return
    }

    try {
        isVerifying.value = true
        errorMessage.value = ''

        const response = await fetch(`${API_CONFIG.BASE_URL}/verify-password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-TOKEN': API_CONFIG.TOKEN
            },
            credentials: 'include',
            body: JSON.stringify({
                password: password.value
            })
        })

        const data = await response.json()

        if (data.status === 'success') {
            // 密碼正確,導向首頁
            router.push('/')
        } else {
            // 密碼錯誤
            errorMessage.value = '密碼錯誤,請重新輸入'
            password.value = ''
        }
    } catch (error) {
        console.error('密碼驗證失敗:', error)
        errorMessage.value = '驗證失敗,請稍後再試'
    } finally {
        isVerifying.value = false
    }
}

// 在組件掛載時檢查存取權限
onMounted(() => {
    checkAccess()
})
</script>

<template>
    <div class="auth-page">
        <div class="container">
            <!-- 載入中 -->
            <div v-if="isLoading" class="auth-container">
                <div class="loading-box">
                    <div class="loading-spinner"></div>
                    <p class="loading-text">正在檢查存取權限...</p>
                </div>
            </div>

            <!-- 密碼輸入表單 -->
            <div v-else class="auth-container">
                <div class="auth-box">
                    <div class="auth-header">
                        <h1 class="auth-title">GoodsSite</h1>
                        <p class="auth-subtitle">此網站需要密碼驗證</p>
                    </div>

                    <div class="auth-body">
                        <div class="info-box" v-if="accessInfo">
                            <p class="info-label">您的 IP 位址</p>
                            <p class="info-value">{{ accessInfo.client_ip }}</p>
                        </div>

                        <form @submit.prevent="verifyPassword" class="auth-form">
                            <div class="form-group">
                                <label for="password">請輸入密碼</label>
                                <input
                                    type="password"
                                    id="password"
                                    v-model="password"
                                    placeholder="Password"
                                    :disabled="isVerifying"
                                    autocomplete="off"
                                    autofocus
                                    class="form-input"
                                >
                            </div>

                            <div v-if="errorMessage" class="error-message">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span>{{ errorMessage }}</span>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-primary btn-block"
                                :disabled="isVerifying || !password"
                            >
                                <span v-if="isVerifying">
                                    <i class="fa-solid fa-spinner fa-spin"></i> 驗證中...
                                </span>
                                <span v-else>
                                    驗證並進入
                                </span>
                            </button>
                        </form>

                        <div class="auth-hint">
                            <i class="fa-solid fa-info-circle"></i>
                            公司內部 IP 無需密碼即可訪問
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.auth-page {
    min-height: 100vh;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

.auth-container {
    max-width: 480px;
    margin: 0 auto;
}

.auth-box {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    animation: fadeInUp 0.4s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.auth-header {
    padding: 40px 40px 32px;
    text-align: center;
    border-bottom: 1px solid #f3f4f6;
}

.auth-title {
    font-size: 32px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 8px;
}

.auth-subtitle {
    font-size: 16px;
    color: #6b7280;
    margin: 0;
}

.auth-body {
    padding: 32px 40px 40px;
}

.info-box {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
    text-align: center;
}

.info-label {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 4px;
}

.info-value {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    font-family: 'Courier New', monospace;
    margin: 0;
}

.auth-form {
    margin-bottom: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    font-size: 15px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: white;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:disabled {
    background-color: #f9fafb;
    cursor: not-allowed;
    opacity: 0.6;
}

.error-message {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
}

.error-message i {
    font-size: 16px;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 500;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
}

.btn-primary:active:not(:disabled) {
    background: #1d4ed8;
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-block {
    width: 100%;
}

.auth-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 13px;
    color: #9ca3af;
    padding-top: 4px;
}

.auth-hint i {
    font-size: 14px;
}

.loading-box {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 60px 40px;
    text-align: center;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e5e7eb;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.loading-text {
    color: #6b7280;
    font-size: 15px;
    margin: 0;
}

/* 響應式設計 */
@media (max-width: 640px) {
    .auth-page {
        padding: 20px;
    }

    .auth-header {
        padding: 32px 24px 24px;
    }

    .auth-title {
        font-size: 28px;
    }

    .auth-subtitle {
        font-size: 14px;
    }

    .auth-body {
        padding: 24px;
    }

    .loading-box {
        padding: 48px 24px;
    }
}
</style>
