import { createRouter, createWebHistory } from 'vue-router'

const routes = [
    { path: '/', redirect: '/login' },

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
        meta: { requiresAuth: true }
    },

    {
        path: '/members',
        component: () => import('./views/Members.vue'),
        meta: { requiresAuth: true }
    },

    // Factory Orders
    {
        path: '/orders',
        component: () => import('./views/AllOrdersView.vue'),
        meta: { requiresAuth: true }
    },

    // Prosix Website Place Orders
    {
        path: '/place-orders',
        component: () => import('./views/PlaceOrdersView.vue'),
        meta: { requiresAuth: true }
    },

    {
    path: '/teamstore-orders',
    component: () => import('./views/TeamStoreOrdersView.vue'),
    meta: { requiresAuth: true }
},

// {
//     path: '/artwork-requests',
//     component: () => import('./views/ArtworkRequestsView.vue'),
//     meta: { requiresAuth: true }
// },
{
    path: '/artwork-requests',
    component: () => import('./views/ArtworkRequestsView.vue'),
    meta: { requiresAuth: true }
},
    {
        path: '/clients',
        component: () => import('./views/ClientsView.vue'),
        meta: {
            requiresAuth: true,
            superAdmin: true
        }
    },

    {
        path: '/invoices',
        component: () => import('./views/InvoicesView.vue'),
        meta: {
            requiresAuth: true,
            superAdmin: true
        }
    },

    {
        path: '/activity-logs',
        component: () => import('./views/ActivityLogsView.vue'),
        meta: {
            requiresAuth: true,
            superAdmin: true
        }
    },

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
        user = JSON.parse(localStorage.getItem('user'))
    } catch {}

    if (to.meta.requiresAuth && !token) {
        return next('/login')
    }

    if (to.meta.superAdmin && user?.role !== 'super_admin') {
        return next('/dashboard')
    }

    next()
})

export default router
