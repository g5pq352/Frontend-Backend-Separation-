/**
 * API 和資源路徑配置
 * 統一管理所有路徑設定
 */

// 判斷環境
const isDev = process.env.NODE_ENV === 'development'

// API 設定
export const API_CONFIG = {
    // API 基礎路徑
    BASE_URL: isDev
        ? 'http://localhost/template-ver5/api'
        : 'https://backedapi.gdlinode.tw/api',

    // API Token
    TOKEN: '7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d',

    // 前端網址 (用於 Referer)
    FRONTEND_URL: isDev
        ? 'http://localhost:3000'
        : 'https://template.server-goods-design.com'
}

// 資源路徑設定
export const RESOURCE_CONFIG = {
    // 上傳檔案基礎路徑
    UPLOAD_BASE: isDev
        ? 'http://localhost/template-ver5'
        : 'https://backedapi.gdlinode.tw',

    // 圖片路徑前綴
    IMAGE_PREFIX: ''
}

/**
 * 取得完整的圖片 URL
 * @param {string} path - 圖片相對路徑
 * @returns {string} 完整的圖片 URL
 */
export const getImageUrl = (path) => {
    if (!path) return ''

    // 如果已經是完整 URL，直接返回
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path
    }

    // 移除路徑開頭的斜線（如果有的話）
    const cleanPath = path.startsWith('/') ? path.slice(1) : path

    // 組合完整路徑
    return `${RESOURCE_CONFIG.UPLOAD_BASE}/${cleanPath}`
}

/**
 * 取得完整的 API URL
 * @param {string} endpoint - API 端點
 * @returns {string} 完整的 API URL
 */
export const getApiUrl = (endpoint) => {
    return `${API_CONFIG.BASE_URL}${endpoint}`
}
