import { auth } from '@/utils/auth'
import hasPermission from '@/utils/hasPermission'
import { getAccessToken } from '@/utils/accessToken'

const isLoggedIn = () => !!getAccessToken()

// The dashboard of the user's default part (users.default_part)
const defaultDashboard = () => {
  if (!isLoggedIn()) return { name: 'login' }

  const userPath = auth()?.user?.default_part

  if (userPath == 'hr') {
    return hasPermission('hr-allow-part')
      ? { name: 'hr-dashboards' }
      : { name: 'not-authorized' }
  } else if (userPath == 'admin') {
    return hasPermission('admin-allow-part')
      ? { name: 'admin-dashboards' }
      : { name: 'not-authorized' }
  }
  return { name: 'not-authorized' }
}

export const redirects = [
  {
    path: "/",
    name: "index",
    redirect: defaultDashboard,
  },
  {
    path: "",
    redirect: defaultDashboard,
  },
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
  {
    path: '/hr',
    name: 'hr',
    redirect: () => {
      if (!isLoggedIn()) return { name: 'login' }

      return hasPermission('hr-allow-part')
        ? { name: 'hr-dashboards' }
        : { name: 'not-authorized' }
    },
  },
]
export const routes = [

]
