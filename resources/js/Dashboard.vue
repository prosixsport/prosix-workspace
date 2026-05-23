<template>
    <AppLayout>
        <div class="dash">

            <!-- HERO -->
            <div class="hero">
                <div>
                    <h2>Good morning, {{ user?.name || 'User' }}! 👋</h2>
                    <p>Here is your Prosix orders overview.</p>
                </div>
                <router-link to="/orders" class="hero-btn">
                    <i class="fa-solid fa-clipboard-list"></i>
                    View Orders
                </router-link>
            </div>

            <!-- STATS 2x2 -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-left">
                        <span>Total Orders</span>
                        <strong>{{ stats.totalOrders }}</strong>
                    </div>
                    <div class="stat-ico"><i class="fa-solid fa-box"></i></div>
                </div>

                <div class="stat-card">
                    <div class="stat-left">
                        <span>In Production</span>
                        <strong>{{ stats.inProduction }}</strong>
                    </div>
                    <div class="stat-ico"><i class="fa-solid fa-gears"></i></div>
                </div>

                <div class="stat-card">
                    <div class="stat-left">
                        <span>Completed</span>
                        <strong>{{ stats.completed }}</strong>
                    </div>
                    <div class="stat-ico"><i class="fa-solid fa-circle-check"></i></div>
                </div>

                <div class="stat-card">
                    <div class="stat-left">
                        <span>Shipped</span>
                        <strong>{{ stats.shipped }}</strong>
                    </div>
                    <div class="stat-ico"><i class="fa-solid fa-truck-fast"></i></div>
                </div>
            </div>

            <!-- RECENT ORDERS -->
            <div class="orders-card">

                <div class="orders-head">
                    <p>Latest orders in Prosix</p>
                    <router-link to="/orders" class="view-all-btn">View All</router-link>
                </div>

                <div v-if="loading" class="empty-state">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Loading...</p>
                </div>

                <div v-else-if="recentOrders.length === 0" class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <p>No orders yet.</p>
                </div>

                <div v-else class="order-list">
                    <div
                        v-for="order in recentOrders"
                        :key="order.id"
                        class="order-row"
                        @click="$router.push(`/orders?order_id=${order.id}`)"
                    >
                        <div class="order-av">{{ initial(order.name) }}</div>
                        <div class="order-info">
                            <strong>{{ order.name }}</strong>
                            <small>{{ formatDate(order.created_at) }}</small>
                        </div>
                        <span class="badge" :class="statusClass(order.status)">
                            {{ order.status || 'Pending' }}
                        </span>
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>

<script>
import AppLayout from './layouts/AppLayout.vue'
import axios from 'axios'

export default {
    name: 'Dashboard',
    components: { AppLayout },

    data() {
        return {
            loading: false,
            orders: [],
            stats: { totalOrders: 0, pending: 0, inProduction: 0, completed: 0, shipped: 0 }
        }
    },

    computed: {
        user() {
            try { return JSON.parse(localStorage.getItem('user')) } catch { return null }
        },
        recentOrders() { return this.orders.slice(0, 8) }
    },

    mounted() { this.fetchDashboard() },

    methods: {
        headers() {
            return {
                Authorization: `Bearer ${localStorage.getItem('token')}`,
                Accept: 'application/json'
            }
        },

        async fetchDashboard() {
            this.loading = true
            try {
                const res = await axios.get('/api/orders', { headers: this.headers() })
                const orders = Array.isArray(res.data) ? res.data : (res.data?.data || [])
                this.orders = orders
                this.makeStats(orders)
            } catch (e) {
                console.error(e)
            } finally {
                this.loading = false
            }
        },

        makeStats(orders) {
            this.stats.totalOrders = orders.length
            this.stats.pending = orders.filter(o => {
                const s = this.cs(o.status)
                return s.includes('pending') || s.includes('design')
            }).length
            this.stats.inProduction = orders.filter(o => {
                const s = this.cs(o.status)
                return s.includes('production') || s.includes('progress')
            }).length
            this.stats.completed = orders.filter(o => this.cs(o.status).includes('completed')).length
            this.stats.shipped = orders.filter(o => {
                const s = this.cs(o.status)
                return s.includes('shipped') || s.includes('delivered')
            }).length
        },

        cs(status) { return String(status || '').toLowerCase() },

        statusClass(status) {
            const s = this.cs(status)
            if (s.includes('completed')) return 'b-completed'
            if (s.includes('shipped') || s.includes('delivered')) return 'b-shipped'
            if (s.includes('production') || s.includes('progress') || s.includes('packing')) return 'b-production'
            if (s.includes('design')) return 'b-designing'
            return 'b-pending'
        },

        initial(name) { return name ? name.charAt(0).toUpperCase() : 'O' },

        formatDate(date) {
            if (!date) return ''
            const d = new Date(date)
            if (isNaN(d)) return date
            return d.toLocaleDateString()
        }
    }
}
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box; }

.dash {
    padding: 20px;
    background: #f4f5f8;
    min-height: 100vh;
}

/* ─── HERO ─── */
.hero {
    background: #111;
    color: #fff;
    border-radius: 20px;
    padding: 24px 20px;
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.hero h2 { margin: 0; font-size: 22px; font-weight: 900; line-height: 1.3; }
.hero p  { margin: 6px 0 0; color: #9ca3af; font-size: 13px; }

.hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    color: #000;
    border: none;
    border-radius: 10px;
    padding: 10px 18px;
    font-size: 13px;
    font-weight: 800;
    text-decoration: none;
    align-self: flex-start;
}
.hero-btn:hover { background: #f0f0f0; color: #000; }

/* ─── STATS ─── */
.stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}

.stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 18px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid #e8eaed;
}

.stat-left {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.stat-left span {
    font-size: 12px;
    color: #6b7280;
    font-weight: 700;
}

.stat-left strong {
    font-size: 30px;
    font-weight: 900;
    color: #000;
    line-height: 1;
}

.stat-ico {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #111;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

/* ─── ORDERS CARD ─── */
.orders-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e8eaed;
    overflow: hidden;
}

.orders-head {
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f0f1f3;
}

.orders-head p {
    margin: 0;
    font-size: 13px;
    color: #6b7280;
    font-weight: 600;
}

.view-all-btn {
    background: #111;
    color: #fff;
    border-radius: 8px;
    padding: 7px 14px;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
}
.view-all-btn:hover { background: #333; color: #fff; }

/* ─── ORDER ROW ─── */
.order-list { padding: 8px 0; }

.order-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background 0.15s;
}
.order-row:hover { background: #f9fafb; }
.order-row + .order-row { border-top: 1px solid #f3f4f6; }

.order-av {
    width: 38px;
    height: 38px;
    background: #111;
    color: #fff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 14px;
    flex-shrink: 0;
}

.order-info { flex: 1; min-width: 0; }
.order-info strong {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: #111;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.order-info small {
    display: block;
    font-size: 11px;
    color: #9ca3af;
    margin-top: 2px;
}

/* ─── BADGES ─── */
.badge {
    border-radius: 999px;
    padding: 5px 12px;
    font-size: 11px;
    font-weight: 800;
    text-transform: capitalize;
    white-space: nowrap;
    flex-shrink: 0;
}

.b-pending    { background: #fff; color: #000; border: 1.5px solid #000; }
.b-designing  { background: #f3f4f6; color: #555; border: 1px solid #d1d5db; }
.b-production { background: #111; color: #fff; }
.b-completed  { background: #111; color: #fff; }
.b-shipped    { background: #fff; color: #000; border: 1.5px dashed #000; }

/* ─── EMPTY ─── */
.empty-state {
    padding: 40px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: #9ca3af;
}
.empty-state i { font-size: 28px; color: #000; }

/* ─── DESKTOP ─── */
@media (min-width: 769px) {
    .dash { padding: 28px; }

    .hero {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        padding: 28px 30px;
        border-radius: 22px;
    }

    .hero-btn { align-self: auto; }

    .stats {
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }

    .stat-left strong { font-size: 34px; }
}
</style>
