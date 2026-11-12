import hasPermission from '@/utils/hasPermission'
import { getAccessToken } from '@/utils/accessToken'

const isLoggedIn = () => !!getAccessToken()

// "/" is the public landing page (src/pages/index.vue)
export const redirects = [
  {
    path: '/admin',
    name: 'admin',
    redirect: () => {
      if (!isLoggedIn()) return { name: 'login' }

      return hasPermission('admin-allow-part')
        ? { name: 'admin-dashboards' }
        : { name: 'not-authorized' }
    },
  },
]
export const routes = [

]
