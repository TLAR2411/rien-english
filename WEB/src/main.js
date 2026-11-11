import { createApp } from "vue"
import App from "@/App.vue"
import { registerPlugins } from "@core/utils/plugins"
import { PerfectScrollbarPlugin } from 'vue3-perfect-scrollbar';
import "@core/scss/template/index.scss"
import "@styles/styles.scss"
import 'vue3-perfect-scrollbar/style.css';
import Notifications, { notify } from "@kyvg/vue3-notification"
import { router } from "@/router"
import { useAuthStore } from "@/stores/authStore"
import { getAccessToken } from "@/utils/accessToken"

// Create app
const app = createApp(App)

// Plugins
registerPlugins(app)
app.use(Notifications)
app.use(PerfectScrollbarPlugin);

async function start() {
  if (getAccessToken()) {
    await useAuthStore().bootstrap()
  }

  app.use(router)

  app.mount("#app")
}

start()
