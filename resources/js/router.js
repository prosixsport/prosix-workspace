import { createRouter, createWebHistory } from 'vue-router'

const routes = [
    /*
    |--------------------------------------------------------------------------
    | Default Route
    |--------------------------------------------------------------------------
    */

    {
        path: '/',
        redirect: '/login'
    },

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('./Dashboard.vue'),
        meta: {
            requiresAuth: true
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Members
    |--------------------------------------------------------------------------
    */

    {
        path: '/members',
        name: 'members',
        component: () => import('./views/Members.vue'),
        meta: {
            requiresAuth: true
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Factory Orders
    |--------------------------------------------------------------------------
    */

    {
        path: '/orders',
        name: 'factory-orders',
        component: () => import('./views/AllOrdersView.vue'),
        meta: {
            requiresAuth: true,
            section: 'factory'
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Place Orders
    |--------------------------------------------------------------------------
    */

    {
        path: '/place-orders',
        name: 'place-orders',
        component: () => import('./views/PlaceOrdersView.vue'),
        meta: {
            requiresAuth: true
        }
    },

    /*
    |--------------------------------------------------------------------------
    | TeamStore Orders
    |--------------------------------------------------------------------------
    */

    {
        path: '/teamstore-orders',
        name: 'teamstore-orders',
        component: () => import('./views/TeamStoreOrdersView.vue'),
        meta: {
            requiresAuth: true
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Artwork Requests
    |--------------------------------------------------------------------------
    */

    {
        path: '/artwork-requests',
        name: 'artwork-requests',
        component: () => import('./views/ArtworkRequestsView.vue'),
        meta: {
            requiresAuth: true,
            section: 'artwork'
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    {
        path: '/profile',
        name: 'profile',
        component: () => import('./views/ProfileView.vue'),
        meta: {
            requiresAuth: true
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */

    {
        path: '/clients',
        name: 'clients',
        component: () => import('./views/ClientsView.vue'),
        meta: {
            requiresAuth: true,
            superAdmin: true
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Try Login Customers
    |--------------------------------------------------------------------------
    */

    {
        path: '/clients/requests',
        name: 'client-requests',
        component: () => import('./views/TryLoginCustomersView.vue'),
        meta: {
            requiresAuth: true,
            superAdmin: true
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Invoices
    |--------------------------------------------------------------------------
    */

    {
        path: '/invoices',
        name: 'invoices',
        component: () => import('./views/InvoicesView.vue'),
        meta: {
            requiresAuth: true,
            superAdmin: true
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Activity Logs
    |--------------------------------------------------------------------------
    */

    {
        path: '/activity-logs',
        name: 'activity-logs',
        component: () => import('./views/ActivityLogsView.vue'),
        meta: {
            requiresAuth: true,
            superAdmin: true
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Recycle Bin
    |--------------------------------------------------------------------------
    */

    {
        path: '/recycle-bin',
        name: 'recycle-bin',
        component: () => import('./views/RecycleBinView.vue'),
        meta: {
            requiresAuth: true,
            superAdmin: true
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Unknown Routes
    |--------------------------------------------------------------------------
    */

    {
        path: '/:pathMatch(.*)*',
        redirect: '/dashboard'
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return {
            top: 0,
            left: 0
        }
    }
})

/*
|--------------------------------------------------------------------------
| Navigation Guard
|--------------------------------------------------------------------------
*/

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

    /*
    |--------------------------------------------------------------------------
    | Login Required
    |--------------------------------------------------------------------------
    */

    if (to.meta.requiresAuth && !token) {
        return next({
            path: '/login',
            query: {
                redirect: to.fullPath
            }
        })
    }

    /*
    |--------------------------------------------------------------------------
    | Invalid User Data
    |--------------------------------------------------------------------------
    */

    if (to.meta.requiresAuth && token && !user) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')

        return next('/login')
    }

    /*
    |--------------------------------------------------------------------------
    | Super Admin Only Pages
    |--------------------------------------------------------------------------
    */

    if (
        to.meta.superAdmin &&
        user?.role !== 'super_admin'
    ) {
        return next('/dashboard')
    }

    /*
    |--------------------------------------------------------------------------
    | Authenticated User Cannot Open Login/Register
    |--------------------------------------------------------------------------
    */

    if (
        token &&
        (
            to.name === 'login' ||
            to.name === 'register'
        )
    ) {
        return next('/dashboard')
    }

    return next()
})

export default router
