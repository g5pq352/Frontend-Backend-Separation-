import { API_CONFIG } from "~/config/paths"

export default defineNuxtRouteMiddleware(async (to) => {

    // 避免自己 redirect 自己
    if (to.path === '/auth') return

    if (import.meta.server) return

    try {
        const res = await fetch(`${API_CONFIG.BASE_URL}/check-access`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-API-TOKEN': API_CONFIG.TOKEN
            },
            credentials: 'include'
        })

        const data = await res.json()

        if (!data?.data?.has_access) {
            return navigateTo('/auth')
        }

    } catch (e) {
        console.error(e)
        return navigateTo('/auth')
    }
})
