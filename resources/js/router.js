import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },

  {
    path: '/login',
    component: () => import('./auth/Login.vue')
  },

  {
    path: '/register',
    component: () => import('./auth/Register.vue')
  },

  {
    path: '/dashboard',
    component: () => import('./Dashboard.vue'),
    meta: {
      requiresAuth: true
    }
  },

  {
    path: '/members',
    component: () => import('./views/Members.vue'),
    meta: {
      requiresAuth: true
    }
  },

  // =========================
  // FACTORY ORDERS
  // =========================
  {
    path: '/orders',
    component: () => import('./views/AllOrdersView.vue'),
    meta: {
      requiresAuth: true
    }
  },

  // =========================
  // PLACE ORDERS
  // =========================
  {
    path: '/place-orders',
    component: () => import('./views/PlaceOrdersView.vue'),
    meta: {
      requiresAuth: true
    }
  },

  // =========================
  // TEAMSTORE ORDERS
  // =========================
  {
    path: '/teamstore-orders',
    component: () => import('./views/TeamStoreOrdersView.vue'),
    meta: {
      requiresAuth: true
    }
  },

  // =========================
  // ARTWORK REQUESTS
  // =========================
  {
    path: '/artwork-requests',
    component: () => import('./views/ArtworkRequestsView.vue'),
    meta: {
      requiresAuth: true
    }
  },

  // =========================
  // PROFILE
  // =========================
  {
    path: '/profile',
    name: 'profile',
    component: () => import('./views/ProfileView.vue'),
    meta: {
      requiresAuth: true
    }
  },

  // =========================
  // CLIENTS
  // =========================
  {
    path: '/clients',
    component: () => import('./views/ClientsView.vue'),
    meta: {
      requiresAuth: true,
      superAdmin: true
    }
  },

  // =========================
  // INVOICES
  // =========================
  {
    path: '/invoices',
    component: () => import('./views/InvoicesView.vue'),
    meta: {
      requiresAuth: true,
      superAdmin: true
    }
  },

  // =========================
  // ACTIVITY LOGS
  // =========================
  {
    path: '/activity-logs',
    component: () => import('./views/ActivityLogsView.vue'),
    meta: {
      requiresAuth: true,
      superAdmin: true
    }
  },

  // =========================
  // RECYCLE BIN
  // =========================
  {
    path: '/recycle-bin',
    component: () => import('./views/RecycleBinView.vue'),
    meta: {
      requiresAuth: true,
      superAdmin: true
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')

  let user = null

  try {
    user = JSON.parse(
      localStorage.getItem('user') || 'null'
    )
  } catch {
    user = null
  }

  // Not logged in
  if (to.meta.requiresAuth && !token) {
    return next('/login')
  }

  // Super admin only pages
  if (
    to.meta.superAdmin &&
    user?.role !== 'super_admin'
  ) {
    return next('/dashboard')
  }

  // Logged-in user should not return to login/register
  if (
    token &&
    (
      to.path === '/login' ||
      to.path === '/register'
    )
  ) {
    return next('/dashboard')
  }

  next()
})

export default router
