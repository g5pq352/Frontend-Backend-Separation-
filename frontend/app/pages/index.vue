<script setup>
import { getImageUrl } from '~/config/paths'
import { useHighlight, stripHtml, getSearchExcerpt, containsKeyword } from '~/composables/useHighlight'

// 設定這頁的 SEO
useSeoMeta({
    title: 'GOODS Reference',
    description: 'GOODS Reference'
})

// 使用簡化的 API composable 取得首頁資料
const { data: apiResponse, error, pending } = await useApiGet('/home-data')

// 處理作品資料
const portfolioData = computed(() => {
    if (apiResponse.value?.status === 'success') {
        return apiResponse.value.data.portfolio || []
    }
    return []
})

// 處理篩選器資料
const filtersData = computed(() => {
    if (apiResponse.value?.status === 'success') {
        return apiResponse.value.data.filters || {
            type: [],
            category: [],
            author: [],
            project: [],
            color: [],
            tag: []
        }
    }
    return {
        type: [],
        category: [],
        author: [],
        project: [],
        color: [],
        tag: []
    }
})

// ===== Vue 狀態管理 =====
const searchQuery = ref('')
const currentView = ref('grid') // 'grid' or 'list'
const showAuthModal = ref(false)

// 篩選器狀態
const selectedType = ref(null)
const selectedCategory = ref(null)
const selectedAuthor = ref(null)
const selectedProject = ref(null)
const selectedColor = ref(null)
const selectedTag = ref(null)

// 下拉選單顯示狀態
const showTypeDropdown = ref(false)
const showCategoryDropdown = ref(false)
const showAuthorDropdown = ref(false)
const showProjectDropdown = ref(false)
const showColorDropdown = ref(false)
const showTagDropdown = ref(false)

// 篩選後的作品資料
const filteredportfolio = computed(() => {
    let result = portfolioData.value

    // 全文搜尋關鍵字篩選
    if (searchQuery.value) {
        const query = searchQuery.value.trim()
        result = result.filter(portfolio => {
            // 搜尋標題
            if (containsKeyword(portfolio.d_title, query)) return true

            // 搜尋內容
            if (containsKeyword(portfolio.d_content, query)) return true

            // 搜尋 slug
            if (containsKeyword(portfolio.d_slug, query)) return true

            // 搜尋分類名稱 (從 filtersData 中取得名稱)
            const typeNames = getClassNames(portfolio.d_class2, filtersData.value.type)
            if (containsKeyword(typeNames, query)) return true

            const categoryNames = getClassNames(portfolio.d_class3, filtersData.value.category)
            if (containsKeyword(categoryNames, query)) return true

            const colorNames = getClassNames(portfolio.d_class4, filtersData.value.color)
            if (containsKeyword(colorNames, query)) return true

            const tagNames = getClassNames(portfolio.d_class5, filtersData.value.tag)
            if (containsKeyword(tagNames, query)) return true

            const authorNames = getClassNames(portfolio.d_class6, filtersData.value.author)
            if (containsKeyword(authorNames, query)) return true

            const projectNames = getClassNames(portfolio.d_class7, filtersData.value.project)
            if (containsKeyword(projectNames, query)) return true

            return false
        })
    }

    // 類型篩選
    if (selectedType.value) {
        result = result.filter(portfolio => {
            const types = portfolio.d_class2?.split(',') || []
            return types.includes(String(selectedType.value))
        })
    }

    // 分類篩選
    if (selectedCategory.value) {
        result = result.filter(portfolio => {
            const categories = portfolio.d_class3?.split(',') || []
            return categories.includes(String(selectedCategory.value))
        })
    }

    // 作者篩選
    if (selectedAuthor.value) {
        result = result.filter(portfolio => {
            const authors = portfolio.d_class6?.split(',') || []
            return authors.includes(String(selectedAuthor.value))
        })
    }

    // 專案篩選
    if (selectedProject.value) {
        result = result.filter(portfolio => {
            const projects = portfolio.d_class7?.split(',') || []
            return projects.includes(String(selectedProject.value))
        })
    }

    // 顏色篩選
    if (selectedColor.value) {
        result = result.filter(portfolio => {
            const colors = portfolio.d_class4?.split(',') || []
            return colors.includes(String(selectedColor.value))
        })
    }

    // 標籤篩選
    if (selectedTag.value) {
        result = result.filter(portfolio => {
            const tags = portfolio.d_class5?.split(',') || []
            return tags.includes(String(selectedTag.value))
        })
    }

    return result
})

// 輔助函數：根據 ID 字串取得分類名稱
const getClassNames = (idString, filterList) => {
    if (!idString || !filterList) return ''

    const ids = idString.split(',').map(id => id.trim())
    const names = ids.map(id => {
        const item = filterList.find(f => f.t_id == id)
        return item ? item.t_name : ''
    }).filter(name => name)

    return names.join(' ')
}

// 取得高亮顯示的標題
const getHighlightedTitle = (title) => {
    if (!searchQuery.value) return title
    return useHighlight(title, searchQuery.value)
}

// 取得作品的標籤列表 (顯示類型和專案)
const getPortfolioTags = (portfolio) => {
    const tags = []

    // 取得專案標籤 (d_class7 - project)
    if (portfolio.d_class7) {
        const projectIds = portfolio.d_class7.split(',')
        projectIds.forEach(id => {
            const projectItem = filtersData.value.project.find(p => p.t_id == id.trim())
            if (projectItem) {
                tags.push({
                    name: projectItem.t_name,
                    type: 'project'
                })
            }
        })
    }

    // 取得類型標籤 (d_class2 - type)
    if (portfolio.d_class2) {
        const typeIds = portfolio.d_class2.split(',')
        typeIds.forEach(id => {
            const typeItem = filtersData.value.type.find(t => t.t_id == id.trim())
            if (typeItem) {
                tags.push({
                    name: typeItem.t_name,
                    type: 'type'
                })
            }
        })
    }

    // 取得分類標籤 (d_class3 - category)
    if (portfolio.d_class3) {
        const categoryIds = portfolio.d_class3.split(',')
        categoryIds.forEach(id => {
            const categoryItem = filtersData.value.category.find(t => t.t_id == id.trim())
            if (categoryItem) {
                tags.push({
                    name: categoryItem.t_name,
                    type: 'category'
                })
            }
        })
    }

    // 取得顏色標籤 (d_class4 - color)
    if (portfolio.d_class4) {
        const colorIds = portfolio.d_class4.split(',')
        colorIds.forEach(id => {
            const colorItem = filtersData.value.color.find(t => t.t_id == id.trim())
            if (colorItem) {
                tags.push({
                    name: colorItem.t_name,
                    type: 'color'
                })
            }
        })
    }

    // 取得標籤標籤 (d_class5 - tag)
    if (portfolio.d_class5) {
        const tagIds = portfolio.d_class5.split(',')
        tagIds.forEach(id => {
            const tagItem = filtersData.value.tag.find(t => t.t_id == id.trim())
            if (tagItem) {
                tags.push({
                    name: tagItem.t_name,
                    type: 'tag'
                })
            }
        })
    }
    
    return tags
}

// 取得搜尋摘要
const getPortfolioExcerpt = (portfolio) => {
    if (!searchQuery.value) return stripHtml(portfolio.d_content || '').substring(0, 100) + '...'

    // 優先顯示內容中包含關鍵字的部分
    if (containsKeyword(portfolio.d_content, searchQuery.value)) {
        const excerpt = getSearchExcerpt(portfolio.d_content, searchQuery.value, 50)
        return useHighlight(excerpt, searchQuery.value)
    }

    // 如果內容沒有關鍵字,檢查其他欄位
    if (containsKeyword(portfolio.d_title, searchQuery.value)) {
        return '標題: ' + useHighlight(portfolio.d_title, searchQuery.value)
    }

    // 其他欄位
    const allText = [
        portfolio.d_slug,
        getClassNames(portfolio.d_class2, filtersData.value.type),
        getClassNames(portfolio.d_class3, filtersData.value.category),
        getClassNames(portfolio.d_class4, filtersData.value.color),
        getClassNames(portfolio.d_class5, filtersData.value.tag),
        getClassNames(portfolio.d_class6, filtersData.value.author),
        getClassNames(portfolio.d_class7, filtersData.value.project)
    ].join(' ')

    if (containsKeyword(allText, searchQuery.value)) {
        const excerpt = getSearchExcerpt(allText, searchQuery.value, 50)
        return useHighlight(excerpt, searchQuery.value)
    }

    return stripHtml(portfolio.d_content || '').substring(0, 100) + '...'
}

// 切換視圖
const toggleView = (view) => {
    currentView.value = view
}

// 切換下拉選單
const toggleDropdown = (type) => {
    if (type === 'type') {
        showTypeDropdown.value = !showTypeDropdown.value
        showCategoryDropdown.value = false
        showAuthorDropdown.value = false
        showProjectDropdown.value = false
        showColorDropdown.value = false
        showTagDropdown.value = false
    } else if (type === 'category') {
        showCategoryDropdown.value = !showCategoryDropdown.value
        showTypeDropdown.value = false
        showAuthorDropdown.value = false
        showProjectDropdown.value = false
        showColorDropdown.value = false
        showTagDropdown.value = false
    } else if (type === 'author') {
        showAuthorDropdown.value = !showAuthorDropdown.value
        showTypeDropdown.value = false
        showCategoryDropdown.value = false
        showProjectDropdown.value = false
        showColorDropdown.value = false
        showTagDropdown.value = false
    } else if (type === 'project') {
        showProjectDropdown.value = !showProjectDropdown.value
        showTypeDropdown.value = false
        showCategoryDropdown.value = false
        showAuthorDropdown.value = false
        showColorDropdown.value = false
        showTagDropdown.value = false
    } else if (type === 'color') {
        showColorDropdown.value = !showColorDropdown.value
        showTypeDropdown.value = false
        showCategoryDropdown.value = false
        showAuthorDropdown.value = false
        showProjectDropdown.value = false
        showTagDropdown.value = false
    } else if (type === 'tag') {
        showTagDropdown.value = !showTagDropdown.value
        showTypeDropdown.value = false
        showCategoryDropdown.value = false
        showAuthorDropdown.value = false
        showProjectDropdown.value = false
        showColorDropdown.value = false
    }
}

// 選擇篩選項目
const selectFilter = (type, id) => {
    if (type === 'type') {
        selectedType.value = selectedType.value === id ? null : id
        showTypeDropdown.value = false
    } else if (type === 'category') {
        selectedCategory.value = selectedCategory.value === id ? null : id
        showCategoryDropdown.value = false
    } else if (type === 'author') {
        selectedAuthor.value = selectedAuthor.value === id ? null : id
        showAuthorDropdown.value = false
    } else if (type === 'project') {
        selectedProject.value = selectedProject.value === id ? null : id
        showProjectDropdown.value = false
    } else if (type === 'color') {
        selectedColor.value = selectedColor.value === id ? null : id
        showColorDropdown.value = false
    } else if (type === 'tag') {
        selectedTag.value = selectedTag.value === id ? null : id
        showTagDropdown.value = false
    }
}

// 取得篩選器的顯示名稱
const getFilterLabel = (type) => {
    if (type === 'type' && selectedType.value) {
        const item = filtersData.value.type.find(t => t.t_id == selectedType.value)
        return item ? item.t_name : '類型'
    } else if (type === 'category' && selectedCategory.value) {
        const item = filtersData.value.category.find(t => t.t_id == selectedCategory.value)
        return item ? item.t_name : '分類'
    } else if (type === 'author' && selectedAuthor.value) {
        const item = filtersData.value.author.find(t => t.t_id == selectedAuthor.value)
        return item ? item.t_name : '作者'
    } else if (type === 'project' && selectedProject.value) {
        const item = filtersData.value.project.find(t => t.t_id == selectedProject.value)
        return item ? item.t_name : '專案'
    } else if (type === 'color' && selectedColor.value) {
        const item = filtersData.value.color.find(t => t.t_id == selectedColor.value)
        return item ? item.t_name : '顏色'
    } else if (type === 'tag' && selectedTag.value) {
        const item = filtersData.value.tag.find(t => t.t_id == selectedTag.value)
        return item ? item.t_name : '標籤'
    }
    return type === 'type' ? '類型' : type === 'category' ? '分類' : type === 'author' ? '作者' : type === 'project' ? '專案' : type === 'color' ? '顏色' : '標籤'
}

// 重置篩選器
const resetFilters = () => {
    searchQuery.value = ''
    selectedType.value = null
    selectedCategory.value = null
    selectedAuthor.value = null
    selectedProject.value = null
    selectedColor.value = null
    selectedTag.value = null
    showTypeDropdown.value = false
    showCategoryDropdown.value = false
    showAuthorDropdown.value = false
    showProjectDropdown.value = false
    showColorDropdown.value = false
    showTagDropdown.value = false
}

// 關閉所有下拉選單 (點擊外部時)
const closeAllDropdowns = () => {
    showTypeDropdown.value = false
    showCategoryDropdown.value = false
    showAuthorDropdown.value = false
    showProjectDropdown.value = false
    showColorDropdown.value = false
    showTagDropdown.value = false
}

// ===== 在 onMounted 中使用 jQuery 和 GSAP =====
onMounted(() => {
    // 監聽來自 Header 的登入事件
    const handleAuthModalEvent = () => {
        openAuthModal()
    }
    window.addEventListener('open-auth-modal', handleAuthModalEvent)
    
    // 監聽全域點擊事件以關閉下拉選單
    window.addEventListener('click', closeAllDropdowns)

    // GSAP 動畫
    if (typeof gsap !== 'undefined') {
        gsap.fromTo('.website-card', {
            y: 25,
            opacity: 0,
        }, {
            duration: 0.7,
            y: 0,
            opacity: 1,
            ease: 'power1.in-out',
            stagger: 0.15
        })
    }
    
    // 清理監聽器
    onUnmounted(() => {
        window.removeEventListener('open-auth-modal', handleAuthModalEvent)
        window.removeEventListener('click', closeAllDropdowns)
    })
})
</script>

<template>
    <!-- Filter Bar -->
    <section class="filter-bar">
        <div class="container">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="search-input" v-model="searchQuery" placeholder="搜尋網站...">
            </div>
            <div class="filters-wrapper">
                <!-- 作者篩選 -->
                <div class="filter-group">
                    <button
                        class="filter-btn"
                        :class="{ active: selectedAuthor }"
                        @click.stop="toggleDropdown('author')"
                    >
                        {{ getFilterLabel('author') }} <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown" v-if="showAuthorDropdown" @click.stop>
                        <div
                            v-for="item in filtersData.author"
                            :key="item.t_id"
                            class="filter-item"
                            :class="{ active: selectedAuthor == item.t_id }"
                            @click="selectFilter('author', item.t_id)"
                        >
                            {{ item.t_name }}
                        </div>
                        <div v-if="!filtersData.author?.length" class="filter-item empty">
                            暫無資料
                        </div>
                    </div>
                </div>
                
                <!-- 專案篩選 -->
                <div class="filter-group">
                    <button
                        class="filter-btn"
                        :class="{ active: selectedProject }"
                        @click.stop="toggleDropdown('project')"
                    >
                        {{ getFilterLabel('project') }} <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown" v-if="showProjectDropdown" @click.stop>
                        <div
                            v-for="item in filtersData.project"
                            :key="item.t_id"
                            class="filter-item"
                            :class="{ active: selectedProject == item.t_id }"
                            @click="selectFilter('project', item.t_id)"
                        >
                            {{ item.t_name }}
                        </div>
                        <div v-if="!filtersData.project?.length" class="filter-item empty">
                            暫無資料
                        </div>
                    </div>
                </div>

                <!-- 類型篩選 -->
                <div class="filter-group">
                    <button
                        class="filter-btn"
                        :class="{ active: selectedType }"
                        @click.stop="toggleDropdown('type')"
                    >
                        {{ getFilterLabel('type') }} <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown" v-if="showTypeDropdown" @click.stop>
                        <div
                            v-for="item in filtersData.type"
                            :key="item.t_id"
                            class="filter-item"
                            :class="{ active: selectedType == item.t_id }"
                            @click="selectFilter('type', item.t_id)"
                        >
                            {{ item.t_name }}
                        </div>
                        <div v-if="!filtersData.type?.length" class="filter-item empty">
                            暫無資料
                        </div>
                    </div>
                </div>

                <!-- 分類篩選 -->
                <div class="filter-group">
                    <button
                        class="filter-btn"
                        :class="{ active: selectedCategory }"
                        @click.stop="toggleDropdown('category')"
                    >
                        {{ getFilterLabel('category') }} <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown" v-if="showCategoryDropdown" @click.stop>
                        <div
                            v-for="item in filtersData.category"
                            :key="item.t_id"
                            class="filter-item"
                            :class="{ active: selectedCategory == item.t_id }"
                            @click="selectFilter('category', item.t_id)"
                        >
                            {{ item.t_name }}
                        </div>
                        <div v-if="!filtersData.category?.length" class="filter-item empty">
                            暫無資料
                        </div>
                    </div>
                </div>

                <!-- 顏色篩選 -->
                <div class="filter-group">
                    <button
                        class="filter-btn"
                        :class="{ active: selectedColor }"
                        @click.stop="toggleDropdown('color')"
                    >
                        {{ getFilterLabel('color') }} <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown" v-if="showColorDropdown" @click.stop>
                        <div
                            v-for="item in filtersData.color"
                            :key="item.t_id"
                            class="filter-item"
                            :class="{ active: selectedColor == item.t_id }"
                            @click="selectFilter('color', item.t_id)"
                        >
                            {{ item.t_name }}
                        </div>
                        <div v-if="!filtersData.color?.length" class="filter-item empty">
                            暫無資料
                        </div>
                    </div>
                </div>

                <!-- 標籤篩選 -->
                <div class="filter-group">
                    <button
                        class="filter-btn"
                        :class="{ active: selectedTag }"
                        @click.stop="toggleDropdown('tag')"
                    >
                        {{ getFilterLabel('tag') }} <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="filter-dropdown" v-if="showTagDropdown" @click.stop>
                        <div
                            v-for="item in filtersData.tag"
                            :key="item.t_id"
                            class="filter-item"
                            :class="{ active: selectedTag == item.t_id }"
                            @click="selectFilter('tag', item.t_id)"
                        >
                            {{ item.t_name }}
                        </div>
                        <div v-if="!filtersData.tag?.length" class="filter-item empty">
                            暫無資料
                        </div>
                    </div>
                </div>
            </div>
            <div class="reset-wrapper">
                <button @click="resetFilters" class="btn-text">
                    <i class="fa-solid fa-rotate-right"></i> 重置
                </button>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="status-bar">
                <span v-if="pending">載入中...</span>
                <span v-else-if="error">載入失敗</span>
                <span v-else>{{ filteredportfolio.length }} sites</span>

                <div class="view-options">
                    <button
                        class="view-btn"
                        :class="{ active: currentView === 'grid' }"
                        @click="toggleView('grid')"
                    >
                        <i class="fa-solid fa-border-all"></i>
                    </button>
                    <button
                        class="view-btn"
                        :class="{ active: currentView === 'list' }"
                        @click="toggleView('list')"
                    >
                        <i class="fa-solid fa-list"></i>
                    </button>
                </div>
            </div>

            <!-- 載入中狀態 -->
            <div v-if="pending" class="website-grid">
                <div v-for="i in 6" :key="i" class="website-card">
                    <div class="animate-pulse">
                        <div class="device-frame">
                            <div class="h-48 bg-gray-200 rounded"></div>
                        </div>
                        <div class="card-content">
                            <div class="h-6 bg-gray-200 rounded mb-2"></div>
                            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 錯誤狀態 -->
            <div v-else-if="error" class="text-center py-12">
                <p class="text-red-600 mb-4">載入資料時發生錯誤</p>
                <p class="text-gray-500">{{ error.message || error }}</p>
            </div>

            <!-- 網站卡片列表 -->
            <div
                v-else-if="filteredportfolio.length > 0"
                id="website-grid"
                class="website-grid"
                :class="{ 'list-view': currentView === 'list' }"
            >
                <div
                    v-for="(portfolio, index) in filteredportfolio"
                    :key="portfolio.d_id || index"
                    class="website-card"
                >
                    <NuxtLink :to="`/portfolio/${portfolio.d_slug}`" class="card-link">
                        <div class="card-item">
                            <div class="card-thumbnail">
                                <img
                                    v-if="portfolio.file_link1"
                                    :src="getImageUrl(portfolio.file_link1)"
                                    :alt="portfolio.file_title || portfolio.d_title"
                                    loading="lazy"
                                >
                                <div v-else class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-400">
                                    <i class="fa-regular fa-image text-4xl"></i>
                                </div>
                            </div>
                            <div class="card-info">
                                <h3 class="card-title" v-html="getHighlightedTitle(portfolio.d_title)"></h3>
                                <!-- 搜尋摘要 (僅在有搜尋關鍵字時顯示) -->
                                <p v-if="searchQuery" class="search-excerpt" v-html="getPortfolioExcerpt(portfolio)"></p>
                                <!-- 標籤 (顯示類型和專案) -->
                                <div class="card-tags" v-else-if="getPortfolioTags(portfolio).length > 0">
                                    <span
                                        v-for="tag in getPortfolioTags(portfolio)"
                                        :key="tag.name"
                                        class="tag"
                                        :class="`tag-${tag.type}`"
                                    >
                                        {{ tag.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </NuxtLink>
                </div>
            </div>

            <!-- 無資料狀態 -->
            <div v-else class="text-center py-12">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-500 text-lg">{{ searchQuery ? '找不到符合的資料' : '目前沒有資料' }}</p>
            </div>
        </div>
    </main>
</template>

<style scoped>
/* Filter Group Dropdown Styles */
.filter-group {
    position: relative;
}

.filter-btn {
    background: white;
    border: 1px solid #e5e7eb;
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-btn:hover {
    border-color: #d1d5db;
    background: #f9fafb;
}

.filter-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.filter-btn i {
    font-size: 12px;
    transition: transform 0.2s ease;
}

.filter-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    min-width: 200px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
}

.filter-item {
    padding: 10px 16px;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
    transition: all 0.2s ease;
    border-bottom: 1px solid #f3f4f6;
}

.filter-item:last-child {
    border-bottom: none;
}

.filter-item:hover {
    background: #f9fafb;
    color: #3b82f6;
}

.filter-item.active {
    background: #eff6ff;
    color: #3b82f6;
    font-weight: 500;
}

.filter-item.empty {
    color: #9ca3af;
    cursor: default;
    text-align: center;
}

.filter-item.empty:hover {
    background: white;
    color: #9ca3af;
}

/* Swiper 基本樣式 */
.swiper {
    width: 100%;
    height: 200px;
}

/* 卡片動畫準備 */
.gsap-box {
    opacity: 0;
    transform: translateX(-100px);
}

/* 搜尋高亮樣式 */
:deep(.search-highlight) {
    background: linear-gradient(180deg, rgba(255, 235, 59, 0.4) 0%, rgba(255, 235, 59, 0.7) 100%);
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: 600;
    color: #1a1a1a;
    box-shadow: 0 1px 2px rgba(255, 235, 59, 0.3);
    transition: all 0.2s ease;
}

:deep(.search-highlight:hover) {
    background: linear-gradient(180deg, rgba(255, 235, 59, 0.6) 0%, rgba(255, 235, 59, 0.9) 100%);
    box-shadow: 0 2px 4px rgba(255, 235, 59, 0.5);
}

/* 搜尋摘要樣式 */
.search-excerpt {
    font-size: 14px;
    color: #6b7280;
    line-height: 1.6;
    margin-top: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* 卡片標題高亮時的樣式 */
.card-title {
    transition: color 0.2s ease;
}

.card-title:has(.search-highlight) {
    color: #1f2937;
}

/* 卡片標籤樣式 */
.card-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}

.tag {
    display: inline-block;
    padding: 4px 12px;
    font-size: 12px;
    border-radius: 6px;
    background: #f3f4f6;
    color: #6b7280;
    font-weight: 500;
    transition: all 0.2s ease;
}

/* 類型標籤 (藍色) */
.tag-type {
    background: #dbeafe;
    color: #1e40af;
}

.tag-type:hover {
    background: #bfdbfe;
    color: #1e3a8a;
}

/* 專案標籤 (綠色) */
.tag-project {
    background: #d1fae5;
    color: #065f46;
}

.tag-project:hover {
    background: #a7f3d0;
    color: #064e3b;
}

/* 分類標籤 (靛藍色) */
.tag-category {
    background: #e0e7ff;
    color: #4338ca;
}

.tag-category:hover {
    background: #c7d2fe;
    color: #3730a3;
}

/* 顏色標籤 (琥珀色) */
.tag-color {
    background: #fef3c7;
    color: #b45309;
}

.tag-color:hover {
    background: #fde68a;
    color: #92400e;
}

/* 標籤標籤 (玫瑰色) */
.tag-tag {
    background: #ffe4e6;
    color: #be123c;
}

.tag-tag:hover {
    background: #fecdd3;
    color: #9f1239;
}
</style>
