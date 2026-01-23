/**
 * 文字高亮顯示 Composable
 * 用於在搜尋結果中高亮顯示關鍵字
 */

/**
 * 移除 HTML 標籤
 * @param {string} html - HTML 字串
 * @returns {string} 純文字
 */
export const stripHtml = (html) => {
    if (!html) return ''
    return html.replace(/<[^>]*>/g, '')
}

/**
 * 高亮顯示關鍵字
 * @param {string} text - 原始文字
 * @param {string} keyword - 搜尋關鍵字
 * @returns {string} 包含 HTML 標記的文字
 */
export const useHighlight = (text, keyword) => {
    if (!text || !keyword) return text

    // 清理關鍵字
    const cleanKeyword = keyword.trim()
    if (!cleanKeyword) return text

    // 使用正則表達式進行大小寫不敏感的替換
    const regex = new RegExp(`(${escapeRegExp(cleanKeyword)})`, 'gi')
    return text.replace(regex, '<mark class="search-highlight">$1</mark>')
}

/**
 * 轉義正則表達式特殊字符
 * @param {string} string - 需要轉義的字串
 * @returns {string} 轉義後的字串
 */
const escapeRegExp = (string) => {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

/**
 * 取得搜尋結果摘要
 * @param {string} text - 完整文字
 * @param {string} keyword - 搜尋關鍵字
 * @param {number} contextLength - 關鍵字前後保留的字元數
 * @returns {string} 摘要文字
 */
export const getSearchExcerpt = (text, keyword, contextLength = 60) => {
    if (!text || !keyword) return ''

    // 移除 HTML 標籤
    const plainText = stripHtml(text)

    // 找到關鍵字的位置
    const lowerText = plainText.toLowerCase()
    const lowerKeyword = keyword.toLowerCase().trim()
    const index = lowerText.indexOf(lowerKeyword)

    // 如果找不到關鍵字,返回開頭的文字
    if (index === -1) {
        return plainText.substring(0, contextLength * 2) + (plainText.length > contextLength * 2 ? '...' : '')
    }

    // 計算摘要的起始和結束位置
    const start = Math.max(0, index - contextLength)
    const end = Math.min(plainText.length, index + keyword.length + contextLength)

    // 組合摘要
    let excerpt = plainText.substring(start, end)

    // 添加省略號
    if (start > 0) excerpt = '...' + excerpt
    if (end < plainText.length) excerpt = excerpt + '...'

    return excerpt
}

/**
 * 檢查文字是否包含關鍵字
 * @param {string} text - 要檢查的文字
 * @param {string} keyword - 搜尋關鍵字
 * @returns {boolean} 是否包含關鍵字
 */
export const containsKeyword = (text, keyword) => {
    if (!text || !keyword) return false

    const plainText = stripHtml(text)
    return plainText.toLowerCase().includes(keyword.toLowerCase().trim())
}

/**
 * 計算關鍵字在文字中出現的次數
 * @param {string} text - 要檢查的文字
 * @param {string} keyword - 搜尋關鍵字
 * @returns {number} 出現次數
 */
export const countKeyword = (text, keyword) => {
    if (!text || !keyword) return 0

    const plainText = stripHtml(text)
    const regex = new RegExp(escapeRegExp(keyword.trim()), 'gi')
    const matches = plainText.match(regex)

    return matches ? matches.length : 0
}
