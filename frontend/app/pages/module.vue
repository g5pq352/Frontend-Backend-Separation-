<script setup>
import { ref, computed, watchEffect, watch } from 'vue'
import { getImageUrl } from '~/config/paths'

// 取得路由參數
const route = useRoute()
const slug = route.params.slug

// 設定 SEO (會在取得資料後更新)
useSeoMeta({
    title: 'GOODS Reference',
    description: 'GOODS Reference'
})

// 使用 API composable 取得模組資料（一次性載入所有資料）
const { data: apiResponse, error, pending } = await useApiGet('/module-list')

// 處理資料
const moduleCategory = computed(() => {
    if (apiResponse.value?.status === 'success') {
        return apiResponse.value.data.moduleCategory
    }
    return []
})

const allModules = computed(() => {
    if (apiResponse.value?.status === 'success') {
        return apiResponse.value.data.module || []
    }
    return []
})

// 選中的分類
const selectedCategory = ref('')

// 根據選中的分類過濾模組列表（前端篩選）
const filteredModules = computed(() => {
    if (!selectedCategory.value) {
        return allModules.value
    }
    return allModules.value.filter(module => {
        return module.d_class2 == selectedCategory.value || 
               module.d_class3 == selectedCategory.value ||
               module.d_class4 == selectedCategory.value ||
               module.d_class5 == selectedCategory.value ||
               module.d_class6 == selectedCategory.value ||
               module.d_class7 == selectedCategory.value
    })
})

// 側邊欄收合狀態
const isSidebarOpen = ref(true)

// 切換側邊欄
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value
}

// moduleContent 中的項目
const contentItems = ref([])

// 拖曳狀態
const draggedItem = ref(null)
const draggedFromContent = ref(false)
const draggedIndex = ref(null) // 新增：記錄拖曳項目的原始索引
const dragOverIndex = ref(null)

// 從側邊欄開始拖曳
const handleDragStart = (event, module) => {
    draggedItem.value = module
    draggedFromContent.value = false
    draggedIndex.value = null
    event.dataTransfer.effectAllowed = 'copy'
    event.dataTransfer.setData('text/html', event.target.innerHTML)
    event.target.style.opacity = '0.5'
}

// 從 content 區域開始拖曳
const handleContentDragStart = (event, index) => {
    draggedItem.value = contentItems.value[index]
    draggedFromContent.value = true
    draggedIndex.value = index // 記錄原始索引
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/html', event.target.innerHTML)
    event.target.style.opacity = '0.5'
}

// 拖曳結束
const handleDragEnd = (event) => {
    event.target.style.opacity = '1'
    dragOverIndex.value = null
}

// 拖曳經過 content 區域 - 根據來源顯示不同提示
const handleContentDragOver = (event, index) => {
    event.preventDefault()
    event.dataTransfer.dropEffect = draggedFromContent.value ? 'move' : 'copy'
    
    if (draggedFromContent.value) {
        // 內部拖曳：顯示交換目標
        dragOverIndex.value = index
    } else {
        // 從側邊欄拖曳：偵測滑鼠位置決定插入點
        const rect = event.currentTarget.getBoundingClientRect()
        const mouseY = event.clientY
        const elementMiddle = rect.top + rect.height / 2
        
        // 如果滑鼠在元素上半部，插入到前面；下半部則插入到後面
        dragOverIndex.value = mouseY < elementMiddle ? index : index + 1
    }
}

// 拖曳離開
const handleContentDragLeave = () => {
    dragOverIndex.value = null
}

// 放置到 content 區域
const handleContentDrop = (event, targetIndex) => {
    event.preventDefault()
    
    if (!draggedItem.value) return
    
    if (draggedFromContent.value) {
        // 從 content 內部交換位置
        const dragIndex = draggedIndex.value
        if (dragIndex !== null && dragIndex !== targetIndex) {
            const items = [...contentItems.value]
            // 直接交換兩個項目的位置
            const temp = items[dragIndex]
            items[dragIndex] = items[targetIndex]
            items[targetIndex] = temp
            contentItems.value = items
        }
    } else {
        // 從側邊欄插入：根據滑鼠位置決定插入點
        const rect = event.currentTarget.getBoundingClientRect()
        const mouseY = event.clientY
        const elementMiddle = rect.top + rect.height / 2
        const insertIndex = mouseY < elementMiddle ? targetIndex : targetIndex + 1
        
        const items = [...contentItems.value]
        items.splice(insertIndex, 0, { ...draggedItem.value })
        contentItems.value = items
    }
    
    draggedItem.value = null
    draggedFromContent.value = false
    draggedIndex.value = null
    dragOverIndex.value = null
}

// 放置到 content 容器（空白區域）
const handleContentContainerDrop = (event) => {
    event.preventDefault()
    
    if (!draggedItem.value) return
    
    if (!draggedFromContent.value) {
        // 從側邊欄新增到 content 末尾（允許重複加入）
        contentItems.value.push({ ...draggedItem.value })
    }
    
    draggedItem.value = null
    draggedFromContent.value = false
    dragOverIndex.value = null
}

// 允許放置
const handleContentContainerDragOver = (event) => {
    event.preventDefault()
    event.dataTransfer.dropEffect = draggedFromContent.value ? 'move' : 'copy'
}

// 移除項目
const removeItem = (index) => {
    contentItems.value.splice(index, 1)
}

// 更新 SEO meta - 使用 watchEffect 立即執行
watchEffect(() => {
    if (allModules.value && allModules.value.length > 0) {
        const firstModule = allModules.value[0]
        useSeoMeta({
            title: `${firstModule.d_title} - GOODS Reference`,
            description: firstModule.d_content?.replace(/<[^>]*>/g, '').substring(0, 160) || '',
        })
    }
})
</script>

<template>
    <main class="flex">
        <!-- 左側邊欄 -->
        <div :class="[
            'fixed top-0 left-0 h-screen bg-white overflow-y-auto z-50 border-r border-gray-200 transition-transform duration-300 ease-in-out',
            isSidebarOpen ? 'translate-x-0 w-[400px]' : '-translate-x-full w-[400px]'
        ]">
            <div>
                <!-- 標題列與關閉按鈕 -->
                <div class="sticky top-0 bg-white flex items-center justify-between mb-5 p-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800">模組列表</h2>
                    <button 
                        @click="toggleSidebar"
                        class="p-2 hover:bg-gray-100 rounded-full transition-colors duration-200 group"
                        title="關閉側邊欄"
                    >
                        <svg class="w-5 h-5 text-gray-600 group-hover:text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="p-4">
                    <!-- 分類下拉選單 -->
                    <div class="mb-5">
                        <select v-model="selectedCategory" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">全部分類</option>
                            <option 
                                v-for="category in moduleCategory" 
                                :key="category.t_id" 
                                :value="category.t_id"
                            >
                                {{ category.t_name }}
                            </option>
                        </select>
                    </div>
                    
                    <!-- 模組列表 -->
                    <div class="space-y-4">
                        <div 
                            v-for="module in filteredModules" 
                            :key="module.d_id"
                            draggable="true"
                            @dragstart="handleDragStart($event, module)"
                            @dragend="handleDragEnd"
                            class="bg-white border border-gray-200 rounded-lg overflow-hidden cursor-move hover:shadow-lg transition-shadow duration-200"
                        >
                            <h3 class="text-center font-semibold py-2 px-3 bg-gray-50 border-b border-gray-200">
                                {{ module.d_title }}
                            </h3>
                            <div class="p-2">
                                <img 
                                    :src="getImageUrl(module.file_link1)" 
                                    :alt="module.file_title || module.d_title" 
                                    loading="lazy"
                                    class="w-full h-auto rounded"
                                >
                            </div>
                        </div>
                        
                        <!-- 無資料提示 -->
                        <div v-if="filteredModules.length === 0" class="text-center text-gray-500 py-8">
                            <p>此分類暫無資料</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 開啟側邊欄按鈕 (當側邊欄關閉時顯示) -->
        <button
            v-if="!isSidebarOpen"
            @click="toggleSidebar"
            class="fixed top-2 left-4 z-[60] bg-blue-500 text-white p-3 rounded-full shadow-lg hover:bg-blue-600 transition-all duration-200 hover:scale-110"
            title="開啟側邊欄"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        
        <!-- 右側內容區域 -->
        <div 
            :class="[
                'w-full min-h-screen transition-all duration-300 ease-in-out',
                isSidebarOpen ? 'ml-[400px]' : 'ml-0'
            ]"
        >
            <div 
                id="moduleContent" 
                class="min-h-screen bg-gray-50"
                @drop="handleContentContainerDrop"
                @dragover="handleContentContainerDragOver"
            >
                <!-- 內容項目 -->
                <div v-if="contentItems.length > 0">
                    <div 
                        v-for="(item, index) in contentItems" 
                        :key="`content-${item.d_id}-${index}`"
                        draggable="true"
                        @dragstart="handleContentDragStart($event, index)"
                        @dragend="handleDragEnd"
                        @dragover="handleContentDragOver($event, index)"
                        @dragleave="handleContentDragLeave"
                        @drop="handleContentDrop($event, index)"
                        :class="[
                            'bg-white overflow-hidden cursor-move transition-all duration-200 relative',
                            // 從側邊欄拖曳：顯示插入線
                            !draggedFromContent && dragOverIndex === index ? 'border-t-4 border-blue-500' : '',
                            !draggedFromContent && dragOverIndex === index + 1 ? 'border-b-4 border-blue-500' : '',
                            // 內部拖曳：顯示交換目標
                            draggedFromContent && dragOverIndex === index ? 'ring-4 ring-green-500' : ''
                        ]"
                    >
                        <div class="relative group">
                            <!-- 刪除按鈕 -->
                            <button 
                                @click="removeItem(index)"
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 hover:bg-red-600 z-10"
                            >
                                ×
                            </button>
                            
                            <img 
                                :src="getImageUrl(item.file_link1)" 
                                :alt="item.file_title || item.d_title" 
                                loading="lazy"
                                class="w-full h-auto"
                            >
                        </div>
                    </div>
                </div>
                
                <!-- 空狀態提示 -->
                <div v-else class="flex items-center justify-center h-96 border-2 border-dashed border-gray-300 rounded-lg">
                    <div class="text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="text-lg font-medium">將左側項目拖曳到此處</p>
                        <p class="text-sm mt-2">您可以拖曳項目到此區域，並重新排序</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>

<style scoped>
/* 拖曳時的視覺效果 */
[draggable="true"] {
    user-select: none;
}

/* 滾動條樣式 */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>