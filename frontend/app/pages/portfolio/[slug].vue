<script setup>
import { getImageUrl } from '~/config/paths'

// 取得路由參數
const route = useRoute()
const slug = route.params.slug

// 設定 SEO (會在取得資料後更新)
useSeoMeta({
    title: 'GOODS Reference',
    description: 'GOODS Reference'
})

// 使用 API composable 取得作品詳細資料
const { data: apiResponse, error, pending } = await useApiGet(`/portfolio-detail/${slug}`)

// 處理資料
const portfolioDetail = computed(() => {
    if (apiResponse.value?.status === 'success') {
        return apiResponse.value.data.portfolio
    }
    return null
})

const coverImage = computed(() => {
    if (apiResponse.value?.status === 'success') {
        return apiResponse.value.data.cover_image || null
    }
    return null
})

const coverOg = computed(() => {
    if (apiResponse.value?.status === 'success') {
        return apiResponse.value.data.cover_og || null
    }
    return null
})

const images = computed(() => {
    if (apiResponse.value?.status === 'success') {
        return apiResponse.value.data.images || []
    }
    return []
})

const categories = computed(() => {
    if (apiResponse.value?.status === 'success') {
        return apiResponse.value.data.categories || {}
    }
    return {}
})

// 更新 SEO meta - 使用 watchEffect 立即執行
watchEffect(() => {
    const portfolio = portfolioDetail.value
    const cover = coverOg.value?.file_link1 ? coverOg.value : coverImage.value;

    if (portfolio) {
        useSeoMeta({
            title: `${portfolio.d_title} - GOODS Reference`,
            ogTitle: `${portfolio.d_title} - GOODS Reference`,
            description: portfolio.d_content?.replace(/<[^>]*>/g, '').substring(0, 160) || '',
            ogDescription: portfolio.d_content?.replace(/<[^>]*>/g, '').substring(0, 160) || '',
            ogImage: cover?.file_link1 ? getImageUrl(cover.file_link1) : '',
        })
    }
})
</script>

<template>
    <!-- 載入中狀態 -->
    <main v-if="pending" class="main-content detail-page">
        <div class="container">
            <div class="animate-pulse">
                <div class="h-8 bg-gray-200 rounded w-32 mb-6"></div>
                <div class="detail-layout-page">
                    <div class="detail-preview-section">
                        <div class="device-frame-large">
                            <div class="w-full h-96 bg-gray-200 rounded"></div>
                        </div>
                    </div>
                    <div class="detail-info-section">
                        <div class="h-10 bg-gray-200 rounded w-3/4 mb-4"></div>
                        <div class="h-6 bg-gray-200 rounded w-full mb-2"></div>
                        <div class="h-6 bg-gray-200 rounded w-2/3"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 錯誤狀態 -->
    <main v-else-if="error" class="main-content detail-page">
        <div class="container">
            <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded">
                <p class="font-bold text-lg mb-2">載入錯誤</p>
                <p>{{ error.message || error }}</p>
                <NuxtLink to="/" class="mt-4 inline-block text-blue-600 hover:text-blue-800">
                    <i class="fa-solid fa-arrow-left"></i> 返回作品列表
                </NuxtLink>
            </div>
        </div>
    </main>

    <!-- 找不到作品 -->
    <main v-else-if="!portfolioDetail" class="main-content detail-page">
        <div class="container">
            <div class="text-center py-12">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-gray-500 text-xl mb-6">找不到此作品</p>
                <NuxtLink to="/" class="inline-block text-blue-600 hover:text-blue-800">
                    <i class="fa-solid fa-arrow-left"></i> 返回作品列表
                </NuxtLink>
            </div>
        </div>
    </main>

    <!-- 作品詳細內容 -->
    <main v-else class="main-content detail-page">
        <div class="container">
            <!-- 返回列表連結 -->
            <NuxtLink to="/" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> 返回列表
            </NuxtLink>

            <div class="detail-layout-page">
                <!-- 左側：圖片預覽區 -->
                <div class="detail-preview-section">
                    <!-- 封面圖片 -->
                    <div class="device-frame-large">
                        <img
                            v-if="coverImage"
                            :src="getImageUrl(coverImage.file_link1)"
                            :alt="coverImage.file_title || portfolioDetail.d_title"
                            loading="lazy"
                        >
                        <div v-else class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-400">
                            <i class="fa-regular fa-image text-6xl"></i>
                        </div>
                    </div>

                    <!-- 其他圖片 (如果有多張) -->
                    <!-- <div v-if="images.length > 0" class="mt-4 grid grid-cols-3 gap-2">
                        <div
                            v-for="(img, index) in images"
                            :key="index"
                            class="aspect-square rounded overflow-hidden"
                        >
                            <img
                                :src="getImageUrl(img.file_link1)"
                                :alt="img.file_title || portfolioDetail.d_title"
                                class="w-full h-full object-cover"
                                loading="lazy"
                            >
                        </div>
                    </div> -->
                </div>

                <!-- 右側：資訊區 -->
                <div class="detail-info-section">
                    <div class="sticky-info">
                        <!-- 標題 -->
                        <h1>{{ portfolioDetail.d_title }}</h1>

                        <!-- 前往網站按鈕 -->
                        <div class="action-buttons">
                            <a
                                v-if="portfolioDetail.d_data1"
                                :href="portfolioDetail.d_data1"
                                target="_blank"
                                class="btn btn-dark btn-lg"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> 前往網站
                            </a>
                            <!-- <div class="secondary-actions">
                                <button class="btn btn-outline btn-icon"><i class="fa-regular fa-heart"></i></button>
                                <button class="btn btn-outline btn-icon"><i class="fa-regular fa-bookmark"></i></button>
                            </div> -->
                        </div>

                        <div class="detail-meta-grid">
                            <!-- 作者 -->
                            <div v-if="categories.auth?.length" class="meta-group">
                                <label>Auth</label>
                                <div class="tags-list">
                                    <span v-for="(tag, index) in categories.auth" :key="index" class="tag">{{ tag }}</span>
                                </div>
                            </div>

                            <!-- 專案 -->
                            <div v-if="categories.project?.length" class="meta-group">
                                <label>Project</label>
                                <div class="tags-list">
                                    <span v-for="(tag, index) in categories.project" :key="index" class="tag">{{ tag }}</span>
                                </div>
                            </div>

                            <!-- 類型 -->
                            <div v-if="categories.type?.length" class="meta-group">
                                <label>Type</label>
                                <div class="tags-list">
                                    <span v-for="(tag, index) in categories.type" :key="index" class="tag">{{ tag }}</span>
                                </div>
                            </div>

                            <!-- 分類 -->
                            <div v-if="categories.category?.length" class="meta-group">
                                <label>Category</label>
                                <div class="tags-list">
                                    <span v-for="(tag, index) in categories.category" :key="index" class="tag">{{ tag }}</span>
                                </div>
                            </div>

                            <!-- 顏色 -->
                            <div v-if="categories.color?.length" class="meta-group">
                                <label>Color</label>
                                <div class="tags-list">
                                    <span v-for="(tag, index) in categories.color" :key="index" class="tag">{{ tag }}</span>
                                </div>
                            </div>

                            <!-- 標籤 -->
                            <div v-if="categories.tags?.length" class="meta-group">
                                <label>Tags</label>
                                <div class="tags-list">
                                    <span v-for="(tag, index) in categories.tags" :key="index" class="tag">{{ tag }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-content mt-10">
                <!-- 作品內容 -->
                <div v-if="portfolioDetail.d_content" class="meta-group">
                    <label>Content</label>
                    <div class="content-wrapper leading-loose" v-html="portfolioDetail.d_content"></div>
                </div>
            </div>
        </div>
    </main>
</template>

<style scoped>

</style>
