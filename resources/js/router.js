import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },

  {
    path: '/login',
    name: 'login',
    component: () => import('./auth/Login.vue')
  },

  {
    path: '/register',
    name: 'register',
    component: () => import('./auth/Register.vue')
  },

  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('./Dashboard.vue'),
    meta: {
      requiresAuth: true
    }
  },

  {
    path: '/members',
    name: 'members',
    component: () => import('./views/Members.vue'),
    meta: {
      requiresAuth: true
    }
  },

  // ==========================================
  // FACTORY ORDERS
  // ==========================================
  {
    path: '/orders',
    name: 'factory-orders',
    component: () => import('./views/AllOrdersView.vue'),
    meta: {
      requiresAuth: true,
      section: 'factory'
    }
  },

  // ==========================================
  // PLACE ORDERS
  // ==========================================
  {
    path: '/place-orders',
    name: 'place-orders',
    component: () => import('./views/PlaceOrdersView.vue'),
    meta: {
      requiresAuth: true
    }
  },

  // ==========================================
  // TEAMSTORE ORDERS
  // ==========================================
  {
    path: '/teamstore-orders',
    name: 'teamstore-orders',
    component: () => import('./views/TeamStoreOrdersView.vue'),
    meta: {
      requiresAuth: true
    }
  },

  // ==========================================
  // ARTWORK REQUESTS - COMPLETELY SEPARATE
  // ==========================================
  {
    path: '/artwork-requests',
    name: 'artwork-requests',
    component: () => import('./views/ArtworkRequestsView.vue'),
    meta: {
      requiresAuth: true,
      section: 'artwork'
    }
  },

  // ==========================================
  // PROFILE
  // ==========================================
  {
    path: '/profile',
    name: 'profile',
    component: () => import('./views/ProfileView.vue'),
    meta: {
      requiresAuth: true
    }
  },

  // ==========================================
  // CLIENTS
  // ==========================================
  {
    path: '/clients',
    name: 'clients',
    component: () => import('./views/ClientsView.vue'),
    meta: {
      requiresAuth: true,
      superAdmin: true
    }
  },

  // ==========================================
  // INVOICES
  // ==========================================
  {
    path: '/invoices',
    name: 'invoices',
    component: () => import('./views/InvoicesView.vue'),
    meta: {
      requiresAuth: true,
      superAdmin: true
    }
  },

  // ==========================================
  // ACTIVITY LOGS
  // ==========================================
  {
    path: '/activity-logs',
    name: 'activity-logs',
    component: () => import('./views/ActivityLogsView.vue'),
    meta: {
      requiresAuth: true,
      superAdmin: true
    }
  },

  // ==========================================
  // RECYCLE BIN
  // ==========================================
  {
    path: '/recycle-bin',
    name: 'recycle-bin',
    component: () => import('./views/RecycleBinView.vue'),
    meta: {
      requiresAuth: true,
      superAdmin: true
    }
  },

  // UNKNOWN ROUTES
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard'
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

  // Login required
  if (to.meta.requiresAuth && !token) {
    return next('/login')
  }

  // Super Admin only pages
  if (
    to.meta.superAdmin &&
    user?.role !== 'super_admin'
  ) {
    return next('/dashboard')
  }

  // Logged in user should not see login/register again
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
