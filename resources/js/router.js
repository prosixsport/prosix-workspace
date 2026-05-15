import { createRouter, createWebHistory } from 'vue-router'

const routes = [
    { path: '/', redirect: '/login' },
    { path: '/login', component: () => import('./auth/Login.vue') },
    { path: '/register', component: () => import('./auth/Register.vue') },
    { path: '/dashboard', component: () => import('./Dashboard.vue'), meta: { requiresAuth: true } },
    { path: '/members', component: () => import('./views/Members.vue'), meta: { requiresAuth: true } },
    { path: '/orders', component: () => import('./views/AllOrdersView.vue'), meta: { requiresAuth: true } }, // ✅ ADD THIS
    { 
    path: '/orders', 
    component: () => import('./views/AllOrdersView.vue'), 
    meta: { requiresAuth: true } 
},
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')
    if (to.meta.requiresAuth && !token) {
        next('/login')
    } else {
        next()
    }
})

export default router
