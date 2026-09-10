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
                <div class="dashboard-user-tools">
                    <div class="dashboard-notification-wrap" @click.stop>
                        <button
                            type="button"
                            class="dashboard-notification-btn"
                            title="Open notifications"
                            @click="toggleNotifications"
                        >
                            <i class="fa-regular fa-bell"></i>
                            <span
                                v-if="notificationCount > 0"
                                class="dashboard-notification-badge"
                            >
                                {{ notificationCount > 99 ? '99+' : notificationCount }}
                            </span>
                        </button>

                        <div
                            v-if="showNotificationMenu"
                            class="dashboard-notification-menu notification-center-dropdown"
                        >
                            <div class="notification-menu-head notification-center-head">
                                <div>
                                    <strong>Notifications</strong>
                                    <small>{{ totalBellNotificationCount }} unread</small>
                                </div>
                                <button type="button" @click="showNotificationMenu = false">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>

                            <div class="notification-tabs">
                                <button
                                    type="button"
                                    :class="{ active: notificationTab === 'chats' }"
                                    @click="notificationTab = 'chats'"
                                >
                                    <i class="fa-solid fa-comments"></i>
                                    Chats
                                    <span v-if="totalUnreadChatCount > 0">
                                        {{ totalUnreadChatCount }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    :class="{ active: notificationTab === 'orders' }"
                                    @click="notificationTab = 'orders'"
                                >
                                    <i class="fa-solid fa-folder-plus"></i>
                                    Orders
                                    <span v-if="unreadOrderNotificationCount > 0">
                                        {{ unreadOrderNotificationCount }}
                                    </span>
                                </button>
                            </div>

                            <div v-if="notificationsLoading" class="notification-menu-empty">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                                Loading...
                            </div>

                            <div v-else-if="notificationTab === 'chats'" class="notification-list">
                                <button
                                    v-for="order in unreadChatOrders"
                                    :key="'chat-notification-' + order.id"
                                    type="button"
                                    class="notification-order chat-notification-item"
                                    @click="openChatNotification(order)"
                                >
                                    <span class="notification-order-icon chat-notification-icon">
                                        <i class="fa-solid fa-comments"></i>
                                    </span>
                                    <span class="notification-order-copy chat-notification-content">
                                        <strong>{{ order.name }}</strong>
                                        <small>
                                            {{ order.last_message_sender || 'New message' }}
                                            <template v-if="order.last_message_text">
                                                · {{ shortLastMessage(order.last_message_text) }}
                                            </template>
                                        </small>
                                    </span>
                                    <span class="chat-notification-badge">
                                        {{ order.unread_chat_count }}
                                    </span>
                                </button>
                                <div v-if="unreadChatOrders.length === 0" class="notification-menu-empty">
                                    <i class="fa-regular fa-comment-dots"></i>
                                    No unread chats
                                </div>
                            </div>

                            <div v-else class="notification-list">
                                <button
                                    v-for="order in unreadOrderNotifications"
                                    :key="'order-notification-' + order.id"
                                    type="button"
                                    class="notification-order chat-notification-item order-notification-item"
                                    @click="openOrderNotification(order)"
                                >
                                    <span class="notification-order-icon order-notification-icon">
                                        <i class="fa-solid fa-folder-plus"></i>
                                    </span>
                                    <span class="notification-order-copy chat-notification-content">
                                        <strong>{{ order.name }}</strong>
                                        <small>
                                            New order
                                            <template v-if="order.po"> · {{ order.po }}</template>
                                            <template v-if="order.created_at">
                                                · {{ notificationTime(order.created_at) }}
                                            </template>
                                        </small>
                                    </span>
                                    <span class="order-notification-new-dot"></span>
                                </button>
                                <div v-if="unreadOrderNotifications.length === 0" class="notification-menu-empty">
                                    <i class="fa-regular fa-folder-open"></i>
                                    No new order notifications
                                </div>
                            </div>

                            <button
                                v-if="totalBellNotificationCount > 0"
                                type="button"
                                class="notification-view-all"
                                @click="openAllNotifications"
                            >
                                View all orders
                            </button>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="dashboard-profile-btn"
                        title="Open profile"
                        @click="openProfilePage"
                    >
                        <span class="dashboard-profile-avatar">
                            <img
                                v-if="user?.profile_photo_url"
                                :src="user.profile_photo_url"
                                :alt="user?.name || 'User'"
                            />
                            <span v-else>{{ initial(user?.name) }}</span>
                        </span>
                        <span class="dashboard-profile-copy">
                            <strong>{{ user?.name || 'User' }}</strong>
                            <small>{{ formatRole(user?.role) }}</small>
                        </span>
                        <i class="fa-solid fa-chevron-right dashboard-profile-arrow"></i>
                    </button>
                </div>
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
                                <!-- <span
                                    v-if="designerShippedCount(designer) > 0"
                                    class="designer-finished-flag"
                                    :title="designer.name + ' finished shipped orders'"
                                >
                                    <i class="fa-solid fa-flag-checkered"></i>
                                </span> -->
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
                                <span>Shipped / Finished</span>
                                <strong>
                                    {{ designerShippedCount(designer) }}
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
                                    v-if="isShippedRecord(record)"
                                    class="history-status finished"
                                >
                                    <i class="fa-solid fa-flag-checkered"></i>
                                    Finished
                                </span>
                                <span
                                    v-else-if="record.is_working"
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
            notificationCount: 0,
            notifications: [],
            notificationsLoading: false,
            showNotificationMenu: false,
            notificationTab: 'chats',
            notificationTimer: null,
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
        unreadChatOrders() {
            return this.notifications
                .filter(order => Number(order.unread_chat_count || 0) > 0)
                .sort((a, b) => {
                    return new Date(b.last_message_at || 0).getTime() -
                        new Date(a.last_message_at || 0).getTime()
                })
        },
        totalUnreadChatCount() {
            return this.unreadChatOrders.reduce(
                (total, order) => total + Number(order.unread_chat_count || 0),
                0
            )
        },
        unreadOrderNotifications() {
            return this.notifications
                .filter(order => !order.user_has_seen)
                .sort((a, b) => {
                    return new Date(b.created_at || 0).getTime() -
                        new Date(a.created_at || 0).getTime()
                })
        },
        unreadOrderNotificationCount() {
            return this.unreadOrderNotifications.length
        },
        totalBellNotificationCount() {
            return this.totalUnreadChatCount +
                this.unreadOrderNotificationCount
        },
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
        this.loadNotifications()
        this.notificationTimer = window.setInterval(
            () => this.loadNotifications(true),
            5000
        )
        document.addEventListener('click', this.closeNotificationMenu)
    },
    beforeUnmount() {
        if (this.notificationTimer) {
            window.clearInterval(this.notificationTimer)
        }
        document.removeEventListener('click', this.closeNotificationMenu)
    },
    methods: {
        openProfilePage() {
            if (this.$route.path === '/profile') return
            this.$router.push('/profile').catch(() => {})
        },
        toggleNotifications() {
            this.showNotificationMenu = !this.showNotificationMenu
            if (this.showNotificationMenu) {
                this.loadNotifications()
                if (
                    this.totalUnreadChatCount === 0 &&
                    this.unreadOrderNotificationCount > 0
                ) {
                    this.notificationTab = 'orders'
                } else if (
                    this.unreadOrderNotificationCount === 0 &&
                    this.totalUnreadChatCount > 0
                ) {
                    this.notificationTab = 'chats'
                }
            }
        },
        closeNotificationMenu() {
            this.showNotificationMenu = false
        },
        openOrderNotification(order) {
            this.showNotificationMenu = false
            this.$router.push({
                path: '/orders',
                query: {
                    type: 'factory',
                    order_id: order.id
                }
            }).catch(() => {})
        },
        openChatNotification(order) {
            this.showNotificationMenu = false
            this.$router.push({
                path: '/orders',
                query: {
                    type: 'factory',
                    order_id: order.id,
                    open_chat: 1
                }
            }).catch(() => {})
        },
        shortLastMessage(value) {
            const text = String(value || '').trim()
            return text.length > 52
                ? `${text.slice(0, 52)}…`
                : text
        },
        notificationTime(value) {
            if (!value) return ''
            const date = new Date(value)
            if (Number.isNaN(date.getTime())) return ''

            const diff = Date.now() - date.getTime()
            const minute = 60 * 1000
            const hour = 60 * minute
            const day = 24 * hour

            if (diff < minute) return 'Just now'
            if (diff < hour) return `${Math.floor(diff / minute)}m ago`
            if (diff < day) return `${Math.floor(diff / hour)}h ago`
            if (diff < 7 * day) return `${Math.floor(diff / day)}d ago`

            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            })
        },
        openAllNotifications() {
            this.showNotificationMenu = false
            this.$router.push({
                path: '/orders',
                query: { type: 'factory' }
            }).catch(() => {})
        },
        async loadNotifications(silent = false) {
            if (!silent) this.notificationsLoading = true
            try {
                // Use the exact same source as Factory Orders so both bells
                // always show identical order/chat notification counts.
                const response = await axios.get('/api/orders', {
                    headers: this.headers()
                })
                const orders = Array.isArray(response.data)
                    ? response.data
                    : response.data?.data || []
                this.notifications = orders.filter(order => {
                    return !order.user_has_seen ||
                        Number(order.unread_chat_count || 0) > 0
                })

                this.notificationCount = orders.reduce((total, order) => {
                    const newOrder = order.user_has_seen ? 0 : 1
                    const unreadChats = Number(order.unread_chat_count || 0)
                    return total + newOrder + unreadChats
                }, 0)

                if (this.showNotificationMenu) {
                    if (
                        this.totalUnreadChatCount === 0 &&
                        this.unreadOrderNotificationCount > 0
                    ) {
                        this.notificationTab = 'orders'
                    } else if (
                        this.unreadOrderNotificationCount === 0 &&
                        this.totalUnreadChatCount > 0
                    ) {
                        this.notificationTab = 'chats'
                    }
                }
            } catch (error) {
                console.error('Dashboard notification error:', error)
            } finally {
                this.notificationsLoading = false
            }
        },
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
        designerShippedCount(designer) {
            const direct = Number(
                designer?.shipped_orders ??
                designer?.finished_orders ??
                designer?.forwarded_orders ??
                0
            )
            if (direct > 0) return direct
            return (designer?.recent_record || []).filter(
                record => this.isShippedRecord(record)
            ).length
        },
        isShippedRecord(record) {
            return String(
                record?.status ||
                record?.order_status ||
                record?.finish_reason ||
                ''
            ).trim().toLowerCase() === 'shipped'
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
    padding: 14px 22px 22px;
    background: #f4f5f8;
}
.hero {
    margin-bottom: 12px;
    padding: 12px 16px;
    border-radius: 14px;
    color: #111827;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}
.hero-label,
.section-eyebrow {
    display: block;
    margin-bottom: 7px;
    color: #64748b;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.14em;
}
.hero h2 {
    margin: 0;
    font-size: 30px;
    font-weight: 900;
}
.hero p {
    margin: 3px 0 0;
    color: #64748b;
    font-size: 12px;
}
.dashboard-user-tools {
    display: flex;
    align-items: center;
    gap: 10px;
}
.dashboard-notification-btn {
    position: relative;
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    padding: 0;
    border: 1px solid #dce2ea;
    border-radius: 12px;
    background: #f8fafc;
    color: #111827;
    display: grid;
    place-items: center;
    cursor: pointer;
}
.dashboard-notification-btn:hover {
    background: #eef2ff;
    color: #4f46e5;
}
.dashboard-notification-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    border: 2px solid #ffffff;
    border-radius: 999px;
    background: #ef4444;
    color: #ffffff;
    display: grid;
    place-items: center;
    font-size: 9px;
    font-weight: 900;
}
.dashboard-profile-btn {
    min-width: 205px;
    height: 48px;
    padding: 4px 10px 4px 5px;
    border: 1px solid #dce2ea;
    border-radius: 13px;
    background: #f8fafc;
    color: #111827;
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr) 14px;
    align-items: center;
    gap: 9px;
    text-align: left;
    cursor: pointer;
}
.dashboard-profile-btn:hover {
    background: #f1f5f9;
}
.dashboard-profile-avatar {
    width: 38px;
    height: 38px;
    overflow: hidden;
    border: 2px solid #ffffff;
    border-radius: 50%;
    background: #ffffff;
    color: #111827;
    display: grid;
    place-items: center;
    font-size: 13px;
    font-weight: 900;
}
.dashboard-profile-avatar img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}
.dashboard-profile-copy {
    min-width: 0;
}
.dashboard-profile-copy strong,
.dashboard-profile-copy small {
    display: block;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.dashboard-profile-copy strong {
    font-size: 11px;
    font-weight: 900;
}
.dashboard-profile-copy small {
    margin-top: 3px;
    color: #64748b;
    font-size: 8px;
    font-weight: 700;
}
.dashboard-profile-arrow {
    color: #9ca3af;
    font-size: 10px;
}
.dashboard-notification-wrap {
    position: relative;
}
.dashboard-notification-menu {
    position: absolute;
    top: calc(100% + 9px);
    right: 0;
    z-index: 2000;
    width: 330px;
    max-height: 420px;
    overflow-x: hidden;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #ffffff;
    box-shadow: 0 18px 50px rgba(15, 23, 42, .18);
}
.notification-menu-head {
    padding: 12px 14px;
    border-bottom: 1px solid #eef1f5;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.notification-menu-head strong,
.notification-menu-head small {
    display: block;
}
.notification-menu-head strong {
    color: #111827;
    font-size: 13px;
    font-weight: 900;
}
.notification-menu-head small {
    margin-top: 2px;
    color: #64748b;
    font-size: 9px;
}
.notification-menu-head button {
    width: 28px;
    height: 28px;
    border: 0;
    border-radius: 8px;
    background: #f1f5f9;
    color: #475569;
}
.notification-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px;
    padding: 8px;
    border-bottom: 1px solid #e8edf3;
    background: #f8fafc;
}
.notification-tabs button {
    min-height: 36px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 900;
}
.notification-tabs button:hover,
.notification-tabs button.active {
    border-color: #dbe2ea;
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
}
.notification-tabs button > span {
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: #111827;
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
}
.notification-list {
    max-height: 300px;
    overflow-y: auto;
    overscroll-behavior: contain;
}
.notification-menu-empty {
    min-height: 110px;
    padding: 22px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 11px;
}
.notification-order {
    width: 100%;
    padding: 10px 13px;
    border: 0;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr) auto;
    align-items: center;
    gap: 9px;
    text-align: left;
}
.notification-order:hover {
    background: #f8fafc;
}
.notification-order-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: #eef2ff;
    color: #4f46e5;
    display: grid;
    place-items: center;
    font-size: 12px;
}
.notification-order-copy {
    min-width: 0;
}
.notification-order-copy strong,
.notification-order-copy small {
    display: block;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.notification-order-copy strong {
    color: #111827;
    font-size: 10px;
    font-weight: 900;
}
.notification-order-copy small {
    margin-top: 3px;
    color: #64748b;
    font-size: 9px;
}
.chat-notification-badge {
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 999px;
    background: #ef4444;
    color: #ffffff;
    display: grid;
    place-items: center;
    font-size: 9px;
    font-weight: 900;
}
.order-notification-icon {
    background: #ecfdf5;
    color: #059669;
}
.order-notification-new-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, .12);
}
.notification-view-all {
    width: 100%;
    padding: 10px;
    border: 0;
    background: #111827;
    color: #ffffff;
    font-size: 10px;
    font-weight: 900;
}
.stats {
    margin-top: 78px;
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
.designer-finished-flag {
    position: absolute;
    top: -5px;
    right: -7px;
    width: 19px;
    height: 19px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    background: #16a34a;
    color: #ffffff;
    display: grid;
    place-items: center;
    font-size: 8px;
    box-shadow: 0 3px 9px rgba(22,163,74,.28);
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
.history-status.finished {
    display: inline-flex;
    align-items: center;
    gap: 4px;
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
    .dashboard-user-tools {
        width: 100%;
    }
    .dashboard-profile-btn {
        flex: 1 1 auto;
        min-width: 0;
    }
    .dashboard-notification-menu {
        right: auto;
        left: 0;
        width: min(330px, calc(100vw - 24px));
    }
    .stats {
        margin-top: 0;
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
