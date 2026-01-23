/**
 * 權限檢查 Composable
 * 用於檢查用戶是否有網站存取權限
 */

import { API_CONFIG } from '~/config/paths'

export const useAccessControl = () => {
    const router = useRouter()
    const hasAccess = ref(false)
    const isChecking = ref(true)
    const accessInfo = ref(null)

    /**
     * 檢查存取權限
     */
    const checkAccess = async () => {
        try {
            isChecking.value = true

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
                hasAccess.value = data.data.has_access

                // 如果沒有存取權限且不在 auth 頁面,導向 auth 頁面
                if (!data.data.has_access && router.currentRoute.value.path !== '/auth') {
                    router.push('/auth')
                }

                return data.data.has_access
            }

            return false
        } catch (error) {
            console.error('檢查存取權限失敗:', error)
            return false
        } finally {
            isChecking.value = false
        }
    }

    /**
     * 驗證密碼
     */
    const verifyPassword = async (password) => {
        try {
            const response = await fetch(`${API_CONFIG.BASE_URL}/verify-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-TOKEN': API_CONFIG.TOKEN
                },
                credentials: 'include',
                body: JSON.stringify({ password })
            })

            const data = await response.json()

            if (data.status === 'success') {
                hasAccess.value = true
                return { success: true }
            } else {
                return { success: false, message: '密碼錯誤' }
            }
        } catch (error) {
            console.error('密碼驗證失敗:', error)
            return { success: false, message: '驗證失敗,請稍後再試' }
        }
    }

    /**
     * 登出
     */
    const logout = async () => {
        try {
            await fetch(`${API_CONFIG.BASE_URL}/logout`, {
                method: 'POST',
                headers: {
                    'X-API-TOKEN': API_CONFIG.TOKEN
                },
                credentials: 'include'
            })

            hasAccess.value = false
            router.push('/auth')
        } catch (error) {
            console.error('登出失敗:', error)
        }
    }

    return {
        hasAccess,
        isChecking,
        accessInfo,
        checkAccess,
        verifyPassword,
        logout
    }
}
