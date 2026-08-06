<template>
    <AppLayout>
        <div class="dash">

            <!-- HERO -->
            <div class="hero">
                <div>
                    <span class="hero-label">PROSIX WORKSPACE</span>
                    <h2>{{ user?.name || 'User' }}!</h2>
                    <p>
                        Here is your orders and designer performance overview.
                    </p>
                </div>

                <router-link
                    to="/orders?type=factory"
                    class="hero-btn"
                >
                    <i class="fa-solid fa-clipboard-list"></i>
                    View Factory Orders
                </router-link>
            </div>

            <!-- STATS -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-left">
                        <span>Total Orders</span>
                        <strong>{{ stats.totalOrders }}</strong>
                    </div>

                    <div class="stat-ico">
                        <i class="fa-solid fa-box"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-left">
                        <span>In Production</span>
                        <strong>{{ stats.inProduction }}</strong>
                    </div>

                    <div class="stat-ico">
                        <i class="fa-solid fa-gears"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-left">
                        <span>Completed</span>
                        <strong>{{ stats.completed }}</strong>
                    </div>

                    <div class="stat-ico">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-left">
                        <span>Shipped</span>
                        <strong>{{ stats.shipped }}</strong>
                    </div>

                    <div class="stat-ico">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                </div>
            </div>

            <!-- DESIGNER PERFORMANCE -->
            <section class="performance-card">
                <div class="section-head">
                    <div>
                        <span class="section-eyebrow">
                            TEAM PERFORMANCE
                        </span>

                        <h3>Designer Record</h3>

                        <p>
                            Working, completed and total time record for every designer.
                        </p>
                    </div>

                    <div class="performance-search">
                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            v-model.trim="designerSearch"
                            type="search"
                            placeholder="Search designer..."
                        />
                    </div>
                </div>

                <div
                    v-if="loading"
                    class="empty-state"
                >
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Loading designer record...</p>
                </div>

                <div
                    v-else-if="filteredDesigners.length === 0"
                    class="empty-state"
                >
                    <i class="fa-solid fa-users"></i>
                    <p>No designer record found.</p>
                </div>

                <div
                    v-else
                    class="designer-grid"
                >
                    <article
                        v-for="designer in filteredDesigners"
                        :key="designer.id"
                        class="designer-card"
                        :class="{
                            working:
                                designer.currently_working > 0
                        }"
                    >
                        <div class="designer-top">
                            <div class="designer-photo">
                                <img
                                    v-if="designer.profile_photo_url"
                                    :src="designer.profile_photo_url"
                                    :alt="designer.name"
                                />

                                <span v-else>
                                    {{ initial(designer.name) }}
                                </span>

                                <i
                                    v-if="designer.currently_working > 0"
                                    class="working-dot"
                                ></i>
                            </div>

                            <div class="designer-identity">
                                <strong>{{ designer.name }}</strong>

                                <small>
                                    {{ formatRole(designer.role) }}
                                </small>
                            </div>

                            <button
                                type="button"
                                class="record-toggle"
                                :class="{
                                    active:
                                        expandedDesignerId ===
                                        designer.id
                                }"
                                @click="toggleDesigner(designer.id)"
                            >
                                <i
                                    class="fa-solid"
                                    :class="
                                        expandedDesignerId ===
                                        designer.id
                                            ? 'fa-chevron-up'
                                            : 'fa-chevron-down'
                                    "
                                ></i>
                            </button>
                        </div>

                        <div class="designer-metrics">
                            <div>
                                <span>Working Now</span>
                                <strong>
                                    {{ designer.currently_working }}
                                </strong>
                            </div>

                            <div>
                                <span>Worked Orders</span>
                                <strong>
                                    {{ designer.total_worked_orders }}
                                </strong>
                            </div>

                            <div>
                                <span>Completed</span>
                                <strong>
                                    {{ designer.completed_orders }}
                                </strong>
                            </div>

                            <div>
                                <span>Forwarded</span>
                                <strong>
                                    {{ designer.forwarded_orders }}
                                </strong>
                            </div>
                        </div>

                        <div class="designer-time">
                            <div>
                                <span>Total Time</span>
                                <strong>
                                    {{ formatMinutes(designer.total_minutes) }}
                                </strong>
                            </div>

                            <div>
                                <span>Average / Session</span>
                                <strong>
                                    {{ formatMinutes(designer.average_minutes) }}
                                </strong>
                            </div>
                        </div>

                        <div
                            v-if="
                                designer.currently_working_orders?.length
                            "
                            class="working-orders"
                        >
                            <span class="working-label">
                                <i class="fa-solid fa-circle"></i>
                                Working now
                            </span>

                            <button
                                v-for="order in designer.currently_working_orders"
                                :key="order.order_id"
                                type="button"
                                @click="openOrder(order.order_id)"
                            >
                                {{ order.order_name }}
                            </button>
                        </div>

                        <div
                            v-if="
                                expandedDesignerId === designer.id
                            "
                            class="designer-history"
                        >
                            <div class="history-head">
                                <strong>Recent Work Record</strong>
                                <span>
                                    {{ designer.recent_record?.length || 0 }}
                                    sessions
                                </span>
                            </div>

                            <div
                                v-if="!designer.recent_record?.length"
                                class="history-empty"
                            >
                                No working sessions yet.
                            </div>

                            <button
                                v-for="record in designer.recent_record"
                                v-else
                                :key="record.id"
                                type="button"
                                class="history-row"
                                @click="openOrder(record.order_id)"
                            >
                                <div>
                                    <strong>
                                        {{ record.order_name }}
                                    </strong>

                                    <small>
                                        {{ formatDateTime(record.started_at) }}
                                    </small>
                                </div>

                                <span
                                    v-if="record.is_working"
                                    class="history-status live"
                                >
                                    Working
                                </span>

                                <span
                                    v-else
                                    class="history-status"
                                >
                                    {{ formatMinutes(record.minutes) }}
                                </span>
                            </button>
                        </div>
                    </article>
                </div>
            </section>

            <!-- RECENT ORDERS -->
            <div class="orders-card">
                <div class="orders-head">
                    <div>
                        <h3>Recent Orders</h3>
                        <p>Latest factory orders in Prosix.</p>
                    </div>

                    <router-link
                        to="/orders?type=factory"
                        class="view-all-btn"
                    >
                        View All
                    </router-link>
                </div>

                <div
                    v-if="loading"
                    class="empty-state"
                >
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Loading...</p>
                </div>

                <div
                    v-else-if="recentOrders.length === 0"
                    class="empty-state"
                >
                    <i class="fa-solid fa-inbox"></i>
                    <p>No orders yet.</p>
                </div>

                <div
                    v-else
                    class="order-list"
                >
                    <div
                        v-for="order in recentOrders"
                        :key="order.id"
                        class="order-row"
                        @click="openOrder(order.id)"
                    >
                        <div class="order-av">
                            {{ initial(order.name) }}
                        </div>

                        <div class="order-info">
                            <strong>{{ order.name }}</strong>

                            <small>
                                {{ order.po || formatDate(order.created_at) }}
                            </small>
                        </div>

                        <span
                            class="badge"
                            :style="{
                                background:
                                    order.status_color || '#111827',
                                color:
                                    readableTextColor(
                                        order.status_color || '#111827'
                                    )
                            }"
                        >
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

    components: {
        AppLayout
    },

    data() {
        return {
            loading: false,
            designerSearch: '',
            expandedDesignerId: null,
            recentOrders: [],
            designers: [],

            stats: {
                totalOrders: 0,
                pending: 0,
                inProduction: 0,
                completed: 0,
                shipped: 0,
                delivered: 0
            }
        }
    },

    computed: {
        user() {
            try {
                return JSON.parse(
                    localStorage.getItem('user')
                )
            } catch {
                return null
            }
        },

        filteredDesigners() {
            const search = this.designerSearch
                .toLowerCase()
                .trim()

            if (!search) {
                return this.designers
            }

            return this.designers.filter(designer => {
                return [
                    designer.name,
                    designer.email,
                    this.formatRole(designer.role)
                ]
                    .join(' ')
                    .toLowerCase()
                    .includes(search)
            })
        }
    },

    mounted() {
        this.fetchDashboard()
    },

    methods: {
        headers() {
            return {
                Authorization:
                    `Bearer ${localStorage.getItem('token')}`,
                Accept: 'application/json'
            }
        },

        async fetchDashboard() {
            this.loading = true

            try {
                const response = await axios.get(
                    '/api/dashboard',
                    {
                        headers: this.headers()
                    }
                )

                this.stats = {
                    ...this.stats,
                    ...(response.data?.stats || {})
                }

                this.recentOrders =
                    response.data?.recent_orders || []

                this.designers =
                    response.data?.designer_performance || []

            } catch (error) {
                console.error(
                    'Dashboard load error:',
                    error
                )
            } finally {
                this.loading = false
            }
        },

        toggleDesigner(id) {
            this.expandedDesignerId =
                this.expandedDesignerId === id
                    ? null
                    : id
        },

        openOrder(orderId) {
            if (!orderId) return

            this.$router.push({
                path: '/orders',
                query: {
                    type: 'factory',
                    order_id: orderId
                }
            })
        },

        initial(name) {
            return String(name || 'U')
                .charAt(0)
                .toUpperCase()
        },

        formatRole(role) {
            return String(role || 'member')
                .replaceAll('_', ' ')
                .replace(
                    /\b\w/g,
                    letter => letter.toUpperCase()
                )
        },

        formatMinutes(minutes) {
            const total = Number(minutes || 0)

            if (total <= 0) {
                return '0m'
            }

            const hours = Math.floor(total / 60)
            const remaining = total % 60

            if (!hours) {
                return `${remaining}m`
            }

            if (!remaining) {
                return `${hours}h`
            }

            return `${hours}h ${remaining}m`
        },

        formatDate(date) {
            if (!date) return '—'

            const parsed = new Date(date)

            if (Number.isNaN(parsed.getTime())) {
                return String(date)
            }

            return parsed.toLocaleDateString(
                'en-US',
                {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                }
            )
        },

        formatDateTime(date) {
            if (!date) return '—'

            const parsed = new Date(date)

            if (Number.isNaN(parsed.getTime())) {
                return String(date)
            }

            return parsed.toLocaleString(
                'en-US',
                {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }
            )
        },

        readableTextColor(color) {
            const value = String(color || '')
                .replace('#', '')

            if (!/^[0-9a-f]{6}$/i.test(value)) {
                return '#ffffff'
            }

            const red = parseInt(
                value.slice(0, 2),
                16
            )

            const green = parseInt(
                value.slice(2, 4),
                16
            )

            const blue = parseInt(
                value.slice(4, 6),
                16
            )

            const luminance =
                (0.299 * red) +
                (0.587 * green) +
                (0.114 * blue)

            return luminance > 155
                ? '#111827'
                : '#ffffff'
        }
    }
}
</script>

<style scoped>
*,
*::before,
*::after {
    box-sizing: border-box;
}

.dash {
    min-height: 100vh;
    padding: 22px;
    background: #f4f5f8;
}

.hero {
    margin-bottom: 16px;
    padding: 26px;
    border-radius: 20px;
    background:
        linear-gradient(
            135deg,
            #10121a 0%,
            #202534 100%
        );
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

.hero-label,
.section-eyebrow {
    display: block;
    margin-bottom: 7px;
    color: #9ca3af;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 0.14em;
}

.hero h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 900;
}

.hero p {
    margin: 6px 0 0;
    color: #b6bdcc;
    font-size: 12px;
}

.hero-btn {
    min-height: 41px;
    padding: 0 16px;
    border-radius: 10px;
    background: #ffffff;
    color: #111827;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    font-weight: 900;
}

.stats {
    margin-bottom: 16px;
    display: grid;
    grid-template-columns:
        repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.stat-card {
    min-height: 108px;
    padding: 17px;
    border: 1px solid #e4e7ec;
    border-radius: 15px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stat-left span {
    display: block;
    color: #6b7280;
    font-size: 10px;
    font-weight: 800;
}

.stat-left strong {
    display: block;
    margin-top: 8px;
    color: #111827;
    font-size: 31px;
    font-weight: 900;
    line-height: 1;
}

.stat-ico {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #111827;
    color: #ffffff;
    display: grid;
    place-items: center;
}

.performance-card,
.orders-card {
    margin-bottom: 16px;
    border: 1px solid #e4e7ec;
    border-radius: 16px;
    background: #ffffff;
    overflow: hidden;
}

.section-head,
.orders-head {
    padding: 17px 18px;
    border-bottom: 1px solid #eceef2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.section-head h3,
.orders-head h3 {
    margin: 0;
    color: #111827;
    font-size: 17px;
    font-weight: 900;
}

.section-head p,
.orders-head p {
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 10px;
}

.performance-search {
    width: 230px;
    height: 38px;
    padding: 0 12px;
    border: 1px solid #d9dee7;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.performance-search i {
    color: #9ca3af;
    font-size: 11px;
}

.performance-search input {
    width: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    font-size: 11px;
}

.designer-grid {
    padding: 14px;
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.designer-card {
    min-width: 0;
    padding: 14px;
    border: 1px solid #e2e6ed;
    border-radius: 14px;
    background: #fbfcfd;
    transition: 0.18s ease;
}

.designer-card:hover {
    border-color: #c8ced8;
    box-shadow: 0 10px 28px rgba(15,23,42,0.07);
}

.designer-card.working {
    border-color: #86efac;
    background: #f3fff7;
}

.designer-top {
    display: flex;
    align-items: center;
    gap: 10px;
}

.designer-photo {
    position: relative;
    width: 43px;
    height: 43px;
    flex: 0 0 43px;
    overflow: visible;
}

.designer-photo img,
.designer-photo > span {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    display: grid;
    place-items: center;
    background: #111827;
    color: #ffffff;
    font-size: 13px;
    font-weight: 900;
}

.working-dot {
    position: absolute;
    right: 0;
    bottom: 1px;
    width: 11px;
    height: 11px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    background: #22c55e;
}

.designer-identity {
    min-width: 0;
    flex: 1;
}

.designer-identity strong {
    display: block;
    overflow: hidden;
    color: #111827;
    font-size: 12px;
    font-weight: 900;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.designer-identity small {
    display: block;
    margin-top: 3px;
    color: #6b7280;
    font-size: 9px;
}

.record-toggle {
    width: 31px;
    height: 31px;
    border: 1px solid #d9dee7;
    border-radius: 8px;
    background: #ffffff;
    color: #111827;
    cursor: pointer;
}

.record-toggle.active {
    background: #111827;
    color: #ffffff;
}

.designer-metrics {
    margin-top: 13px;
    display: grid;
    grid-template-columns:
        repeat(4, minmax(0, 1fr));
    border: 1px solid #e7eaf0;
    border-radius: 10px;
    background: #ffffff;
    overflow: hidden;
}

.designer-metrics > div {
    min-width: 0;
    padding: 9px 7px;
    border-right: 1px solid #eceef2;
    text-align: center;
}

.designer-metrics > div:last-child {
    border-right: 0;
}

.designer-metrics span,
.designer-time span {
    display: block;
    color: #8a93a3;
    font-size: 7px;
    font-weight: 800;
    text-transform: uppercase;
}

.designer-metrics strong {
    display: block;
    margin-top: 5px;
    color: #111827;
    font-size: 16px;
    font-weight: 900;
}

.designer-time {
    margin-top: 10px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.designer-time > div {
    padding: 9px 10px;
    border-radius: 9px;
    background: #eef1f5;
}

.designer-time strong {
    display: block;
    margin-top: 4px;
    color: #111827;
    font-size: 11px;
}

.working-orders {
    margin-top: 10px;
    padding: 9px;
    border-radius: 9px;
    background: #dcfce7;
}

.working-label {
    display: block;
    margin-bottom: 6px;
    color: #166534;
    font-size: 8px;
    font-weight: 900;
    text-transform: uppercase;
}

.working-label i {
    margin-right: 4px;
    font-size: 6px;
}

.working-orders button {
    margin: 2px 4px 2px 0;
    padding: 5px 8px;
    border: 0;
    border-radius: 999px;
    background: #ffffff;
    color: #166534;
    cursor: pointer;
    font-size: 8px;
    font-weight: 900;
}

.designer-history {
    margin-top: 12px;
    border-top: 1px solid #e4e7ec;
    padding-top: 11px;
}

.history-head {
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.history-head strong {
    font-size: 10px;
}

.history-head span {
    color: #8a93a3;
    font-size: 8px;
}

.history-row {
    width: 100%;
    padding: 8px 2px;
    border: 0;
    border-bottom: 1px solid #eceef2;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    text-align: left;
}

.history-row:last-child {
    border-bottom: 0;
}

.history-row div {
    min-width: 0;
}

.history-row strong {
    display: block;
    overflow: hidden;
    font-size: 9px;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.history-row small {
    display: block;
    margin-top: 2px;
    color: #8a93a3;
    font-size: 7px;
}

.history-status {
    flex-shrink: 0;
    padding: 4px 7px;
    border-radius: 999px;
    background: #eef1f5;
    color: #374151;
    font-size: 7px;
    font-weight: 900;
}

.history-status.live {
    background: #dcfce7;
    color: #166534;
}

.history-empty {
    padding: 12px;
    color: #9ca3af;
    text-align: center;
    font-size: 9px;
}

.view-all-btn {
    padding: 7px 13px;
    border-radius: 8px;
    background: #111827;
    color: #ffffff;
    text-decoration: none;
    font-size: 9px;
    font-weight: 900;
}

.order-list {
    padding: 5px 0;
}

.order-row {
    padding: 11px 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 11px;
    transition: background 0.15s ease;
}

.order-row + .order-row {
    border-top: 1px solid #f0f2f5;
}

.order-row:hover {
    background: #f8fafc;
}

.order-av {
    width: 35px;
    height: 35px;
    flex: 0 0 35px;
    border-radius: 9px;
    background: #111827;
    color: #ffffff;
    display: grid;
    place-items: center;
    font-size: 11px;
    font-weight: 900;
}

.order-info {
    min-width: 0;
    flex: 1;
}

.order-info strong {
    display: block;
    overflow: hidden;
    color: #111827;
    font-size: 11px;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.order-info small {
    display: block;
    margin-top: 3px;
    color: #9ca3af;
    font-size: 8px;
}

.badge {
    flex-shrink: 0;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 8px;
    font-weight: 900;
}

.empty-state {
    padding: 35px 20px;
    color: #9ca3af;
    text-align: center;
}

.empty-state i {
    font-size: 23px;
}

.empty-state p {
    margin: 8px 0 0;
    font-size: 10px;
}

@media (max-width: 1050px) {
    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .designer-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 680px) {
    .dash {
        padding: 12px;
    }

    .hero,
    .section-head,
    .orders-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .stats {
        grid-template-columns: 1fr 1fr;
    }

    .performance-search {
        width: 100%;
    }

    .designer-metrics {
        grid-template-columns: 1fr 1fr;
    }

    .designer-metrics > div:nth-child(2) {
        border-right: 0;
    }

    .designer-metrics > div:nth-child(-n + 2) {
        border-bottom: 1px solid #eceef2;
    }
}
</style>
