/**
 * API 請求的可重複使用 composable
 * 自動處理 SSR、錯誤處理、Token 認證
 */

import { API_CONFIG } from '~/config/paths'

/**
 * 通用 API 請求函數
 * @param {string} endpoint - API 端點 (例如: '/home-data')
 * @param {object} options - 額外選項
 * @returns {object} { data, error, pending, refresh }
 */
export const useApi = (endpoint, options = {}) => {

    const {
        method = 'GET',
        key = endpoint,
        lazy = false,
        server = true,
        ...otherOptions
    } = options

    const frontendUrl = API_CONFIG.FRONTEND_URL.replace(/\/+$/, '')

    const headers = {
        'X-API-TOKEN': API_CONFIG.TOKEN,
        'Content-Type': 'application/json',
        'Origin': frontendUrl,
        'Referer': frontendUrl + '/',
        ...otherOptions.headers
    }

    return useFetch(`${API_CONFIG.BASE_URL}${endpoint}`, {
        method,
        key,
        headers,
        credentials: 'include',
        server,
        lazy,
        default: () => null,

        // 關鍵：永遠不要把錯誤吃掉
        onResponseError({ response }) {
            console.error(
                `❌ API Error ${method} ${endpoint}:`,
                response.status,
                response._data
            )
        },

        ...otherOptions
    })
}

/**
 * GET 請求的快捷方式
 * @param {string} endpoint - API 端點
 * @param {object} options - 額外選項
 */
export const useApiGet = (endpoint, options = {}) => {
    return useApi(endpoint, { method: 'GET', ...options })
}

/**
 * POST 請求的快捷方式
 * @param {string} endpoint - API 端點
 * @param {object} body - 請求主體
 * @param {object} options - 額外選項
 */
export const useApiPost = (endpoint, body = {}, options = {}) => {
    return useApi(endpoint, {
        method: 'POST',
        body,
        ...options
    })
}

/**
 * PUT 請求的快捷方式
 * @param {string} endpoint - API 端點
 * @param {object} body - 請求主體
 * @param {object} options - 額外選項
 */
export const useApiPut = (endpoint, body = {}, options = {}) => {
    return useApi(endpoint, {
        method: 'PUT',
        body,
        ...options
    })
}

/**
 * DELETE 請求的快捷方式
 * @param {string} endpoint - API 端點
 * @param {object} options - 額外選項
 */
export const useApiDelete = (endpoint, options = {}) => {
    return useApi(endpoint, { method: 'DELETE', ...options })
}
