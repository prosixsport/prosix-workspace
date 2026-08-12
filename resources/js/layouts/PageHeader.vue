<template>
  <header class="page-header">
    <!-- LEFT SIDE -->
    <div class="page-header__copy">
      <span class="page-header__eyebrow">{{ eyebrow }}</span>

      <div class="page-header__title-row">
        <div class="page-header__mark">
          <i class="fa-solid fa-layer-group"></i>
        </div>

        <div class="page-header__titles">
          <h1>{{ title }}</h1>
          <p v-if="subtitle">{{ subtitle }}</p>
        </div>
      </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="page-header__right">
      <!-- GLOBAL NOTIFICATION BELL -->
      <div class="page-header-notification-slot" @click.stop>
        <button
          type="button"
          class="page-header__notification"
          :class="{ active: notificationOpen }"
          title="Notifications"
          aria-label="Notifications"
          @click.stop="toggleNotifications"
        >
          <i class="fa-solid fa-bell"></i>

          <span
            v-if="totalNotificationCount > 0"
            class="page-header__notification-count"
          >
            {{ totalNotificationCount > 99 ? '99+' : totalNotificationCount }}
          </span>
        </button>

        <!-- NOTIFICATION DROPDOWN -->
        <div
          v-if="notificationOpen"
          class="global-notification-dropdown"
          @click.stop
        >
          <!-- HEADER -->
          <div class="global-notification-head">
            <div>
              <strong>Notifications</strong>
              <small>{{ totalNotificationCount }} unread</small>
            </div>

            <button
              type="button"
              class="notification-close"
              title="Close"
              @click.stop="notificationOpen = false"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- TABS -->
          <div class="notification-tabs">
            <button
              type="button"
              :class="{ active: notificationTab === 'chats' }"
              @click.stop="notificationTab = 'chats'"
            >
              <i class="fa-solid fa-comments"></i>
              <span>Chats</span>

              <strong v-if="totalUnreadChatCount > 0">
                {{ totalUnreadChatCount }}
              </strong>
            </button>

            <button
              type="button"
              :class="{ active: notificationTab === 'orders' }"
              @click.stop="notificationTab = 'orders'"
            >
              <i class="fa-solid fa-folder-plus"></i>
              <span>Orders</span>

              <strong v-if="unreadOrderNotificationCount > 0">
                {{ unreadOrderNotificationCount }}
              </strong>
            </button>
          </div>

          <!-- LOADING -->
          <div
            v-if="notificationLoading"
            class="notification-state"
          >
            <i class="fa-solid fa-spinner fa-spin"></i>
            <span>Loading notifications...</span>
          </div>

          <!-- CHAT TAB -->
          <div
            v-else-if="notificationTab === 'chats'"
            class="global-notification-list"
          >
            <button
              v-for="order in unreadChatOrders"
              :key="'header-chat-' + order.id"
              type="button"
              class="global-notification-item"
              @click="openChatOrder(order)"
            >
              <span class="notification-icon chat-icon">
                <i class="fa-solid fa-comments"></i>
              </span>

              <span class="notification-content">
                <strong>{{ order.name || `Order #${order.id}` }}</strong>

                <small>
                  {{ order.last_message_sender || 'New message' }}

                  <template v-if="order.last_message_text">
                    · {{ shortText(order.last_message_text) }}
                  </template>
                </small>

                <em v-if="order.last_message_at">
                  {{ notificationTime(order.last_message_at) }}
                </em>
              </span>

              <span class="notification-count-pill">
                {{ Number(order.unread_chat_count || 0) }}
              </span>
            </button>

            <div
              v-if="unreadChatOrders.length === 0"
              class="notification-state"
            >
              <i class="fa-regular fa-comment-dots"></i>
              <span>No unread chats</span>
            </div>
          </div>

          <!-- ORDERS TAB -->
          <div
            v-else
            class="global-notification-list"
          >
            <button
              v-for="order in unreadOrderNotifications"
              :key="'header-order-' + order.id"
              type="button"
              class="global-notification-item"
              @click="openNewOrder(order)"
            >
              <span class="notification-icon order-icon">
                <i class="fa-solid fa-folder-plus"></i>
              </span>

              <span class="notification-content">
                <strong>{{ order.name || `Order #${order.id}` }}</strong>

                <small>
                  New order

                  <template v-if="order.po">
                    · {{ order.po }}
                  </template>
                </small>

                <em v-if="order.created_at">
                  {{ notificationTime(order.created_at) }}
                </em>
              </span>

              <span class="notification-unread-dot"></span>
            </button>

            <div
              v-if="unreadOrderNotifications.length === 0"
              class="notification-state"
            >
              <i class="fa-regular fa-folder-open"></i>
              <span>No new order notifications</span>
            </div>
          </div>
        </div>
      </div>

      <!-- PROFILE -->
      <button
        type="button"
        class="page-header__profile"
        title="Open profile"
        @click="goToProfile"
      >
        <span class="page-header__avatar">
          <img
            v-if="photo"
            :src="photo"
            alt="Profile"
          />

          <span v-else>
            {{ initial || fallbackInitial }}
          </span>

          <span class="page-header__online-dot"></span>
        </span>

        <span class="page-header__user-copy">
          <strong>{{ user?.name || 'My Profile' }}</strong>
          <small>{{ roleLabel }}</small>
        </span>

        <span class="page-header__profile-action">
          <i class="fa-solid fa-chevron-down"></i>
        </span>
      </button>
    </div>
  </header>
</template>

<script>
import axios from 'axios'

export default {
  name: 'PageHeader',

  emits: ['profile'],

  props: {
    eyebrow: {
      type: String,
      default: 'WORK MANAGEMENT'
    },

    title: {
      type: String,
      default: 'Workspace'
    },

    subtitle: {
      type: String,
      default: ''
    },

    user: {
      type: Object,
      default: () => ({})
    },

    photo: {
      type: String,
      default: ''
    },

    initial: {
      type: String,
      default: ''
    }
  },

  data() {
    return {
      headerOrders: [],
      notificationOpen: false,
      notificationLoading: false,
      notificationTab: 'chats',
      notificationTimer: null
    }
  },

  computed: {
    fallbackInitial() {
      return String(this.user?.name || 'U')
        .trim()
        .charAt(0)
        .toUpperCase()
    },

    roleLabel() {
      const role = String(this.user?.role || 'User')
        .replace(/_/g, ' ')
        .trim()

      return role.replace(/\b\w/g, char => char.toUpperCase())
    },

    unreadChatOrders() {
      return this.headerOrders
        .filter(order => Number(order.unread_chat_count || 0) > 0)
        .sort((a, b) => {
          const aTime = new Date(a.last_message_at || 0).getTime()
          const bTime = new Date(b.last_message_at || 0).getTime()

          return bTime - aTime
        })
    },

    totalUnreadChatCount() {
      return this.unreadChatOrders.reduce(
        (total, order) => total + Number(order.unread_chat_count || 0),
        0
      )
    },

    unreadOrderNotifications() {
      return this.headerOrders
        .filter(order => !order.user_has_seen)
        .sort((a, b) => {
          const aTime = new Date(a.created_at || 0).getTime()
          const bTime = new Date(b.created_at || 0).getTime()

          return bTime - aTime
        })
    },

    unreadOrderNotificationCount() {
      return this.unreadOrderNotifications.length
    },

    totalNotificationCount() {
      return this.totalUnreadChatCount + this.unreadOrderNotificationCount
    }
  },

  mounted() {
    this.fetchHeaderNotifications(false)

    this.notificationTimer = setInterval(() => {
      this.fetchHeaderNotifications(false)
    }, 30000)

    document.addEventListener(
      'click',
      this.closeNotifications
    )
  },

  beforeUnmount() {
    if (this.notificationTimer) {
      clearInterval(this.notificationTimer)
    }

    document.removeEventListener(
      'click',
      this.closeNotifications
    )
  },

  methods: {
    headers() {
      const token =
        localStorage.getItem('token') ||
        localStorage.getItem('auth_token') ||
        ''

      return {
        Authorization: token ? `Bearer ${token}` : '',
        Accept: 'application/json'
      }
    },

    async fetchHeaderNotifications(showLoading = true) {
      if (showLoading) {
        this.notificationLoading = true
      }

      try {
        const response = await axios.get(
          '/api/orders',
          {
            headers: this.headers()
          }
        )

        const list = Array.isArray(response.data)
          ? response.data
          : (response.data?.data || [])

        this.headerOrders = Array.isArray(list)
          ? list
          : []
      } catch (error) {
        console.error(
          'PageHeader notification orders error:',
          error
        )

        this.headerOrders = []
      } finally {
        this.notificationLoading = false
      }
    },

    async toggleNotifications() {
      this.notificationOpen = !this.notificationOpen

      if (!this.notificationOpen) {
        return
      }

      await this.fetchHeaderNotifications(true)

      if (
        this.totalUnreadChatCount === 0 &&
        this.unreadOrderNotificationCount > 0
      ) {
        this.notificationTab = 'orders'
      } else {
        this.notificationTab = 'chats'
      }
    },

    closeNotifications() {
      this.notificationOpen = false
    },

    openChatOrder(order) {
      this.notificationOpen = false

      this.$router.push({
        path: '/orders',
        query: {
          type: 'factory',
          order_id: order.id,
          open_chat: 1
        }
      })
    },

    openNewOrder(order) {
      this.notificationOpen = false

      this.$router.push({
        path: '/orders',
        query: {
          type: 'factory',
          order_id: order.id
        }
      })
    },

    shortText(value, max = 55) {
      const text = String(value || '').trim()

      if (text.length <= max) {
        return text
      }

      return `${text.slice(0, max)}...`
    },

    notificationTime(value) {
      if (!value) {
        return ''
      }

      const date = new Date(value)

      if (Number.isNaN(date.getTime())) {
        return ''
      }

      const difference = Date.now() - date.getTime()
      const minutes = Math.floor(difference / 60000)

      if (minutes < 1) {
        return 'Just now'
      }

      if (minutes < 60) {
        return `${minutes}m ago`
      }

      const hours = Math.floor(minutes / 60)

      if (hours < 24) {
        return `${hours}h ago`
      }

      const days = Math.floor(hours / 24)

      if (days < 7) {
        return `${days}d ago`
      }

      return date.toLocaleDateString(
        'en-US',
        {
          month: 'short',
          day: 'numeric'
        }
      )
    },

    goToProfile() {
      this.$emit('profile', this.user)
    }
  }
}
</script>

<style scoped>
.page-header {
  width: 100%;
  min-height: 165px;
  padding: 20px 28px;

  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;

  position: relative;
  z-index: 99990;
  overflow: visible !important;

  background: #f4f5f8;
}

.page-header__copy {
  min-width: 0;
}

.page-header__eyebrow {
  display: block;
  margin-bottom: 8px;

  color: #8a919d;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .16em;
  text-transform: uppercase;
}

.page-header__title-row {
  display: flex;
  align-items: center;
  gap: 13px;
}

.page-header__mark {
  width: 42px;
  height: 42px;
  flex: 0 0 42px;

  display: grid;
  place-items: center;

  background: #111827;
  color: #ffffff;
  border-radius: 10px;

  font-size: 15px;
}

.page-header__titles {
  min-width: 0;
}

.page-header__titles h1 {
  margin: 0;

  color: #111827;

  font-size: 22px;
  line-height: 1.15;
  font-weight: 800;

  letter-spacing: -.025em;
}

.page-header__titles p {
  margin: 5px 0 0;

  color: #7a8491;

  font-size: 11px;
  line-height: 1.45;
}

.page-header__right {
  position: relative;
  z-index: 99991;

  display: flex;
  align-items: center;
  justify-content: flex-end;

  gap: 12px;

  flex: 0 0 auto;
}

.page-header-notification-slot {
  position: relative;
  z-index: 99992;
  overflow: visible !important;

  display: flex;
  align-items: center;
  justify-content: center;
}

.page-header__notification {
  position: relative;

  width: 44px;
  height: 44px;
  min-width: 44px;

  display: grid;
  place-items: center;

  padding: 0;
  margin: 0;

  background: #ffffff;
  color: #111827;

  border: 1px solid #d8dde5;
  border-radius: 50%;

  cursor: pointer;

  transition:
    background .16s ease,
    border-color .16s ease,
    box-shadow .16s ease,
    transform .16s ease;
}

.page-header__notification:hover,
.page-header__notification.active {
  background: #ffffff;
  border-color: #aeb7c4;

  box-shadow:
    0 7px 18px
    rgba(15, 23, 42, .10);

  transform: translateY(-1px);
}

.page-header__notification i {
  font-size: 14px;
}

.page-header__notification-count {
  position: absolute;

  top: -7px;
  right: -7px;

  min-width: 21px;
  height: 21px;

  padding: 0 5px;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  background: #ef4444;
  color: #ffffff;

  border: 2px solid #f4f5f8;
  border-radius: 999px;

  font-size: 9px;
  line-height: 1;
  font-weight: 800;
}

/* DROPDOWN */
.global-notification-dropdown {
  position: absolute;

  top: calc(100% + 12px);
  right: 0;

  width: 390px;
  max-width: calc(100vw - 30px);

  background: #ffffff;

  border: 1px solid #e2e8f0;
  border-radius: 14px;

  overflow: hidden;

  box-shadow:
    0 20px 55px
    rgba(15, 23, 42, .16);

  z-index: 2147483000 !important;
  isolation: isolate;
}

.global-notification-head {
  min-height: 62px;
  padding: 12px 12px 12px 16px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;

  border-bottom: 1px solid #edf0f4;
}

.global-notification-head strong {
  display: block;

  color: #111827;

  font-size: 14px;
  font-weight: 800;
}

.global-notification-head small {
  display: block;

  margin-top: 3px;

  color: #8a919d;

  font-size: 10px;
  font-weight: 600;
}

.notification-close {
  width: 30px;
  height: 30px;

  display: grid;
  place-items: center;

  padding: 0;

  background: #f4f5f8;
  color: #667085;

  border: 0;
  border-radius: 8px;

  cursor: pointer;
}

/* TABS */
.notification-tabs {
  display: grid;
  grid-template-columns: 1fr 1fr;

  padding: 8px;

  gap: 7px;

  background: #f8fafc;

  border-bottom: 1px solid #edf0f4;
}

.notification-tabs button {
  min-height: 38px;

  display: flex;
  align-items: center;
  justify-content: center;

  gap: 7px;

  padding: 7px 10px;

  background: transparent;
  color: #667085;

  border: 1px solid transparent;
  border-radius: 9px;

  font-size: 10px;
  font-weight: 800;

  cursor: pointer;
}

.notification-tabs button.active {
  background: #ffffff;
  color: #111827;

  border-color: #dfe4ea;

  box-shadow:
    0 3px 9px
    rgba(15, 23, 42, .06);
}

.notification-tabs button strong {
  min-width: 20px;
  height: 20px;

  padding: 0 5px;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  background: #111827;
  color: #ffffff;

  border-radius: 999px;

  font-size: 9px;
}

.global-notification-list {
  max-height: 390px;

  overflow-y: auto;
}

.global-notification-item {
  width: 100%;

  padding: 12px 14px;

  display: grid;

  grid-template-columns:
    38px
    minmax(0, 1fr)
    auto;

  align-items: start;

  gap: 11px;

  background: #ffffff;

  border: 0;
  border-bottom: 1px solid #f0f2f5;

  text-align: left;

  cursor: pointer;
}

.global-notification-item:hover {
  background: #f8fafc;
}

.notification-icon {
  width: 38px;
  height: 38px;

  display: grid;
  place-items: center;

  border-radius: 11px;

  font-size: 13px;
}

.notification-icon.chat-icon {
  background: #eef2ff;
  color: #4f46e5;
}

.notification-icon.order-icon {
  background: #ecfdf3;
  color: #16a34a;
}

.notification-content {
  min-width: 0;
}

.notification-content strong {
  display: block;

  color: #111827;

  font-size: 11px;
  font-weight: 800;

  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notification-content small {
  display: -webkit-box;

  margin-top: 4px;

  color: #667085;

  font-size: 10px;
  line-height: 1.45;

  overflow: hidden;

  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.notification-content em {
  display: block;

  margin-top: 5px;

  color: #98a2b3;

  font-size: 9px;
  font-style: normal;
  font-weight: 600;
}

.notification-count-pill {
  min-width: 24px;
  height: 24px;

  padding: 0 6px;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  background: #111827;
  color: #ffffff;

  border-radius: 999px;

  font-size: 9px;
  font-weight: 800;
}

.notification-unread-dot {
  width: 8px;
  height: 8px;

  margin-top: 7px;

  background: #22c55e;

  border-radius: 50%;
}

.notification-state {
  min-height: 145px;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 9px;

  padding: 25px;

  color: #98a2b3;

  font-size: 11px;
  font-weight: 600;
}

.notification-state i {
  font-size: 22px;
}

/* PROFILE */
.page-header__profile {
  min-width: 220px;
  max-width: 280px;

  height: 58px;

  padding: 6px 10px 6px 7px;

  display: grid;

  grid-template-columns:
    44px
    minmax(0, 1fr)
    28px;

  align-items: center;

  gap: 10px;

  background: #f8f9fb;

  border: 1px solid #e1e5ea;
  border-radius: 12px;

  text-align: left;

  cursor: pointer;

  transition:
    background .16s ease,
    border-color .16s ease,
    box-shadow .16s ease,
    transform .16s ease;
}

.page-header__profile:hover {
  background: #ffffff;

  border-color: #cbd2db;

  box-shadow:
    0 8px 22px
    rgba(15, 23, 42, .08);

  transform: translateY(-1px);
}

.page-header__avatar {
  position: relative;

  width: 44px;
  height: 44px;

  display: grid;
  place-items: center;

  overflow: visible;

  border-radius: 50%;

  background: #111827;
  color: #ffffff;

  font-size: 13px;
  font-weight: 800;
}

.page-header__avatar img {
  width: 44px;
  height: 44px;

  display: block;

  object-fit: cover;
  object-position: center;

  border-radius: 50%;

  border: 2px solid #ffffff;

  box-shadow:
    0 0 0 1px
    #d8dde5;
}

.page-header__online-dot {
  position: absolute;

  right: 0;
  bottom: 1px;

  width: 10px;
  height: 10px;

  background: #22c55e;

  border: 2px solid #ffffff;
  border-radius: 50%;
}

.page-header__user-copy {
  min-width: 0;

  display: block;
}

.page-header__user-copy strong,
.page-header__user-copy small {
  display: block;

  overflow: hidden;

  white-space: nowrap;

  text-overflow: ellipsis;
}

.page-header__user-copy strong {
  color: #111827;

  font-size: 12px;
  font-weight: 800;
}

.page-header__user-copy small {
  margin-top: 3px;

  color: #8a919d;

  font-size: 9px;
  font-weight: 600;
}

.page-header__profile-action {
  width: 28px;
  height: 28px;

  display: grid;
  place-items: center;

  color: #667085;

  border-radius: 8px;
}

.page-header__profile-action i {
  font-size: 9px;
}

/* DARK MODE */
:global(.theme-dark) .page-header {
  background: #111827;
}

:global(.theme-dark) .page-header__mark {
  background: #ffffff;
  color: #111827;
}

:global(.theme-dark) .page-header__titles h1,
:global(.theme-dark) .page-header__user-copy strong {
  color: #ffffff;
}

:global(.theme-dark) .page-header__eyebrow,
:global(.theme-dark) .page-header__titles p,
:global(.theme-dark) .page-header__user-copy small,
:global(.theme-dark) .page-header__profile-action {
  color: #aeb8c7;
}

:global(.theme-dark) .page-header__profile,
:global(.theme-dark) .page-header__notification {
  background: #182132;
  border-color: #334155;
  color: #ffffff;
}

:global(.theme-dark) .page-header__notification-count {
  border-color: #111827;
}


/* =========================================================
   FORCE NOTIFICATION DROPDOWN ABOVE BOARD / STATUS BAR
   ========================================================= */
.page-header,
.page-header__right,
.page-header-notification-slot {
  overflow: visible !important;
}

.page-header {
  isolation: isolate;
  z-index: 99990 !important;
}

.page-header__right {
  z-index: 99991 !important;
}

.page-header-notification-slot {
  z-index: 99992 !important;
}

.global-notification-dropdown {
  z-index: 2147483000 !important;
}

/* MOBILE */
@media (max-width: 780px) {
  .page-header {
    min-height: auto;
    padding: 16px;
  }

  .page-header__titles h1 {
    font-size: 18px;
  }

  .page-header__titles p {
    display: none;
  }

  .page-header__right {
    gap: 7px;
  }

  .page-header__notification {
    width: 40px;
    height: 40px;
    min-width: 40px;
  }

  .global-notification-dropdown {
    position: fixed;

    top: 75px;
    right: 12px;
    left: 12px;

    width: auto;
    max-width: none;
  }

  .page-header__profile {
    min-width: 54px;
    width: 54px;

    grid-template-columns: 44px;

    padding: 5px;
  }

  .page-header__user-copy,
  .page-header__profile-action {
    display: none;
  }
}
</style>
