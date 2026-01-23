<script setup>
// 不再在 app.vue 中進行權限檢查
// 權限檢查已移至 auth.vue 頁面處理
</script>

<template>
    <LayoutHeader />
    <main>
        <NuxtPage />
    </main>
    <LayoutFooter />
</template>
