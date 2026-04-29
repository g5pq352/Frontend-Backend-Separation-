<template>
    <header class="site-header">
        <div class="container header-content">
            <div class="logo">
                <NuxtLink to="/" style="text-decoration: none; color: inherit;">
                    <h1>GOODS Reference</h1>
                </NuxtLink>
            </div>
            <nav class="main-nav">
                <!-- <NuxtLink to="/module" class="nav-link">模組</NuxtLink> -->
                <NuxtLink to="https://backedapi.gdlinode.tw/portal-auth/signin" target="_blank" class="nav-link">後台登入</NuxtLink>
            </nav>
            <!-- <div class="user-actions">
                <button class="btn btn-primary" @click="handleLoginClick">登入 / 註冊</button>
            </div> -->
        </div>
    </header>
</template>

<script setup>
// 觸發登入事件
const handleLoginClick = () => {
    // 由於 Header 是全域組件，我們使用 DOM 事件來觸發
    const event = new CustomEvent('open-auth-modal')
    window.dispatchEvent(event)
}
</script>