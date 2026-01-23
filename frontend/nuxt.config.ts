// frontend/nuxt.config.ts
export default defineNuxtConfig({
  // 1. 啟用 Tailwind 模組
  modules: ['@nuxtjs/tailwindcss'],

  // 2. 指定您的 SCSS 入口 (雖然 Tailwind 模組會自動掃描,但如果您有自定義 SCSS 建議明確列出)
  css: ['~/assets/scss/style.scss'],

  // 3. 確保 PostCSS 設定正確 (通常模組會自動處理,但為了保險可加上)
  postcss: {
    plugins: {
      tailwindcss: {},
      autoprefixer: {},
    },
  },

  // 4. 全域引入 JavaScript 檔案
  app: {
    head: {
      script: [
        {
          src: '/js/app.js',
          type: 'text/javascript'
        },
        // jQuery (必須先載入)
        {
          src: '/js/jquery-3.7.1.min.js',
          type: 'text/javascript'
        },
        // GSAP
        {
          src: '/js/gsap.min.js',
          type: 'text/javascript'
        },
        // Swiper
        {
          src: '/js/swiper-bundle.min.js',
          type: 'text/javascript'
        }
      ],
      link: [
        // Google Fonts 預連接
        {
          rel: 'preconnect',
          href: 'https://fonts.googleapis.com'
        },
        {
          rel: 'preconnect',
          href: 'https://fonts.gstatic.com',
          crossorigin: 'anonymous'
        },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@300;400;500;700&display=swap'
        },
        {
          rel: 'stylesheet',
          href: 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
        },
        // Swiper CSS
        {
          rel: 'stylesheet',
          href: '/css/swiper-bundle.min.css'
        }
      ]
    }
  },

  // 5. 路由設定 - 忽略不存在的資源路徑
  routeRules: {
    '/js/**': { ssr: false },
    '/css/**': { ssr: false }
  },

  // 6. Vite 設定 - 忽略 source map 檔案
  vite: {
    server: {
      fs: {
        strict: false
      }
    }
  }
})
