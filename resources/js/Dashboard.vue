<template>
    <AppLayout>
        <div class="dashboard-page">

            <!-- Header -->
            <div class="dashboard-hero">
                <div>
                    <h2>Good morning, {{ user?.name || 'User' }}! </h2>
                    <p>Here is your Prosix orders overview.</p>
                </div>

                <router-link to="/orders" class="hero-btn">
                    <i class="fa-solid fa-clipboard-list"></i>
                    View Orders
                </router-link>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">

                <div class="stat-card">
                    <div>
                        <p>Total Orders</p>
                        <h3>{{ stats.totalOrders }}</h3>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-box"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div>
                        <p>In Production</p>
                        <h3>{{ stats.inProduction }}</h3>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-gears"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div>
                        <p>Completed</p>
                        <h3>{{ stats.completed }}</h3>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div>
                        <p>Shipped</p>
                        <h3>{{ stats.shipped }}</h3>
                    </div>
                    <div class="stat-icon">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                </div>

            </div>

            <!-- Recent Orders -->
            <div class="dashboard-card">
                <div class="card-head">
                    <div>
                        <h5>Recent Orders</h5>
                        <p>Latest orders added in Prosix.</p>
                    </div>

                    <router-link to="/orders" class="small-dark-btn">
                        View All
                    </router-link>
                </div>

                <div v-if="loading" class="empty-box">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Loading orders...</p>
                </div>

                <div v-else-if="recentOrders.length === 0" class="empty-box">
                    <i class="fa-solid fa-inbox"></i>
                    <p>No orders found yet.</p>
                    <router-link to="/orders" class="small-dark-btn">
                        + New Order
                    </router-link>
                </div>

                <div v-else class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>P.O #</th>
                                <th>Status</th>
                                <th>Ship Date</th>
                                <th>Payment</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="order in recentOrders" :key="order.id">
                                <td>
                                    <div class="order-name">
                                        <div class="order-avatar">
                                            {{ initial(order.name) }}
                                        </div>
                                        <div>
                                            <strong>{{ order.name }}</strong>
                                            <small>{{ formatDate(order.created_at) }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td class="text-muted">
                                    {{ order.po || '—' }}
                                </td>

                                <td>
                                    <span class="status-badge" :class="statusClass(order.status)">
                                        {{ order.status || 'Pending' }}
                                    </span>
                                </td>

                                <td class="text-muted">
                                    {{ order.shipDate || order.ship_date || '—' }}
                                </td>

                                <td class="text-muted">
                                    {{ order.payment || '0 % Paid' }}
                                </td>

                                <td class="text-end">
                                   <router-link :to="`/orders?order_id=${order.id}`" class="open-btn">
    Open
</router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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

    components: {
        AppLayout
    },

    data() {
        return {
            loading: false,

            orders: [],

            stats: {
                totalOrders: 0,
                pending: 0,
                inProduction: 0,
                completed: 0,
                shipped: 0
            }
        }
    },

    computed: {
        user() {
            try {
                return JSON.parse(localStorage.getItem('user'))
            } catch (e) {
                return null
            }
        },

        recentOrders() {
            return this.orders.slice(0, 6)
        }
    },

    mounted() {
        this.fetchDashboard()
    },

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
                const res = await axios.get('/api/orders', {
                    headers: this.headers()
                })

                const orders = Array.isArray(res.data)
                    ? res.data
                    : (res.data?.data || [])

                this.orders = orders
                this.makeStats(orders)

            } catch (e) {
                console.error('Dashboard load error:', e)
            } finally {
                this.loading = false
            }
        },

        makeStats(orders) {
            this.stats.totalOrders = orders.length

            this.stats.pending = orders.filter(o => {
                const s = this.cleanStatus(o.status)
                return s.includes('pending') || s.includes('design')
            }).length

            this.stats.inProduction = orders.filter(o => {
                const s = this.cleanStatus(o.status)
                return s.includes('production') || s.includes('progress')
            }).length

            this.stats.completed = orders.filter(o => {
                const s = this.cleanStatus(o.status)
                return s.includes('completed')
            }).length

            this.stats.shipped = orders.filter(o => {
                const s = this.cleanStatus(o.status)
                return s.includes('shipped') || s.includes('delivered')
            }).length
        },

        cleanStatus(status) {
            return String(status || '').toLowerCase()
        },

        statusClass(status) {
            const s = this.cleanStatus(status)

            if (s.includes('completed')) return 'completed'
            if (s.includes('shipped') || s.includes('delivered')) return 'shipped'
            if (s.includes('production') || s.includes('progress')) return 'production'
            if (s.includes('design')) return 'designing'

            return 'pending'
        },

        initial(name) {
            return name ? name.charAt(0).toUpperCase() : 'O'
        },

        openOrder(order) {
            if (!order?.id) return

            this.$router.push({
                path: '/orders',
                query: {
                    order_id: order.id
                }
            })
        },

        formatShipDate(order) {
            return order?.shipDate
                || order?.ship_date
                || order?.ship_date_raw
                || '—'
        },

        formatDate(date) {
            if (!date) return '—'

            const d = new Date(date)

            if (Number.isNaN(d.getTime())) return date

            return d.toLocaleDateString()
        }
    }
}
</script>

<style scoped>
.dashboard-page {
    min-height: 100vh;
    padding: 28px;
    background: #f6f7fb;
}

/* Hero */
.dashboard-hero {
    background: #000;
    color: #fff;
    border-radius: 22px;
    padding: 28px 30px;
    margin-bottom: 24px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    box-shadow: 0 20px 50px rgba(0,0,0,0.18);
}

.dashboard-hero h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 900;
}

.dashboard-hero p {
    margin: 6px 0 0;
    color: #d1d5db;
    font-size: 14px;
}

.hero-btn,
.small-dark-btn,
.open-btn {
    background: #000;
    color: #fff;
    border: 1px solid #000;
    text-decoration: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 800;
    padding: 10px 16px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.hero-btn {
    background: #fff;
    color: #000;
    border-color: #fff;
}

.hero-btn:hover {
    background: #f3f4f6;
    color: #000;
}

.small-dark-btn:hover,
.open-btn:hover {
    background: #222;
    color: #fff;
}

/* Stats */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    padding: 20px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    box-shadow: 0 12px 30px rgba(0,0,0,0.06);
}

.stat-card p {
    margin: 0 0 8px;
    color: #6b7280;
    font-size: 13px;
    font-weight: 800;
}

.stat-card h3 {
    margin: 0;
    color: #000;
    font-size: 32px;
    font-weight: 900;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: #000;
    color: #fff;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 20px;
}

/* Card */
.dashboard-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 14px 35px rgba(0,0,0,0.06);
}

.card-head {
    padding: 20px 22px;
    border-bottom: 1px solid #eef0f4;

    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-head h5 {
    margin: 0;
    font-size: 17px;
    font-weight: 900;
    color: #000;
}

.card-head p {
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 13px;
}

/* Table */
.table thead th {
    background: #fafafa;
    color: #111;
    font-size: 12px;
    font-weight: 900;
    padding: 14px 18px;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}

.table tbody td {
    padding: 16px 18px;
    border-bottom: 1px solid #f1f2f4;
    font-size: 14px;
}

.order-name {
    display: flex;
    align-items: center;
    gap: 11px;
}

.order-avatar {
    width: 38px;
    height: 38px;
    background: #000;
    color: #fff;
    border-radius: 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: 900;
}

.order-name strong {
    display: block;
    color: #111;
    font-size: 14px;
}

.order-name small {
    display: block;
    color: #6b7280;
    margin-top: 2px;
}

/* Status */
.status-badge {
    border-radius: 999px;
    padding: 5px 11px;
    font-size: 11px;
    font-weight: 900;
    text-transform: capitalize;
}

.status-badge.pending {
    background: #fff;
    color: #000;
    border: 1px solid #000;
}

.status-badge.designing {
    background: #f3f4f6;
    color: #000;
    border: 1px solid #d1d5db;
}

.status-badge.production {
    background: #000;
    color: #fff;
}

.status-badge.completed {
    background: #111;
    color: #fff;
}

.status-badge.shipped {
    background: #fff;
    color: #000;
    border: 1px dashed #000;
}

/* Empty */
.empty-box {
    min-height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    color: #6b7280;
    gap: 10px;
}

.empty-box i {
    font-size: 34px;
    color: #000;
}

/* Records */
.records-grid {
    padding: 22px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.record-box {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 18px;
}

.record-box span {
    display: block;
    color: #6b7280;
    font-size: 13px;
    font-weight: 800;
    margin-bottom: 8px;
}

.record-box strong {
    color: #000;
    font-size: 26px;
    font-weight: 900;
}

/* Responsive */
@media (max-width: 992px) {
    .stats-grid,
    .records-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .dashboard-hero {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
}

@media (max-width: 576px) {
    .dashboard-page {
        padding: 18px;
    }

    .stats-grid,
    .records-grid {
        grid-template-columns: 1fr;
    }

    .hero-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
