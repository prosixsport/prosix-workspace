<template>
  <AppLayout>
    <div class="page-wrap">
      <!-- GLOBAL HEADER -->
      <PageHeader
        title="Activity Logs"
        subtitle="All user create, update, delete, restore and start work records"
        :user="currentUser"
        :photo="currentUser?.profile_photo_url"
        @profile="openProfile"
      />

      <div class="activity-content">
        <!-- FILTERS -->
        <div class="filter-card">
          <div class="field">
            <label>User</label>
            <select v-model="filters.user">
              <option value="">All Users</option>
              <option v-for="u in users" :key="u" :value="u">
                {{ u }}
              </option>
            </select>
          </div>

          <div class="field">
            <label>Action</label>
            <select v-model="filters.action">
              <option value="">All</option>
              <option value="created">Created</option>
              <option value="updated">Updated</option>
              <option value="deleted">Deleted</option>
              <option value="restored">Restored</option>
              <option value="permanent_deleted">Permanent Deleted</option>
              <option value="started_work">Started Work</option>
            </select>
          </div>

          <div class="field">
            <label>Date From</label>
            <input v-model="filters.from" type="date" />
          </div>

          <div class="field">
            <label>Date To</label>
            <input v-model="filters.to" type="date" />
          </div>

          <button class="search-btn" @click="applyFilters">
            <i class="fa-solid fa-magnifying-glass"></i>
          </button>

          <button class="clear-btn" @click="clearFilters">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div v-if="loading" class="empty">
          Loading...
        </div>

        <div v-else-if="filteredLogs.length === 0" class="empty">
          No activity logs found.
        </div>

        <div v-else class="table-card">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>User</th>
                <th>Order</th>
                <th>Action</th>
                <th>Description</th>
                <th>Time</th>
                <th>Delete</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(log, index) in filteredLogs"
                :key="log._rowKey || log.id"
              >
                <td>{{ index + 1 }}</td>

                <td>
                  <div class="user-cell">
                    <span class="user-avatar">
                      <img
                        v-if="log.user?.profile_photo_url"
                        :src="log.user.profile_photo_url"
                        :alt="log.user?.name || 'User'"
                      />

                      <span v-else>
                        {{ initial(log.user?.name || 'U') }}
                      </span>
                    </span>

                    <span class="user-copy">
                      <strong>
                        {{ log.user?.name || 'Unknown' }}
                      </strong>

                      <small>
                        {{ log.user?.email || '' }}
                      </small>
                    </span>
                  </div>
                </td>

                <td>
                  <strong class="order-name">
                    {{
                      log.order?.name ||
                      log.order_name ||
                      'Deleted Order'
                    }}
                  </strong>

                  <small v-if="log.order?.po || log.order_po">
                    {{
                      log.order?.po ||
                      log.order_po
                    }}
                  </small>
                </td>

                <td>
                  <span
                    class="badge"
                    :class="normalizeAction(log.action)"
                  >
                    <i
                      v-if="normalizeAction(log.action) === 'started_work'"
                      class="fa-solid fa-play"
                    ></i>

                    {{ formatAction(log.action) }}
                  </span>
                </td>

                <td>
                  {{ log.description || '-' }}
                </td>

                <td>
                  {{ formatDate(log.created_at) }}
                </td>

                <td>
                  <button
                    v-if="!log._virtual"
                    class="delete-btn"
                    @click="deleteLog(log)"
                    title="Delete activity"
                  >
                    <i class="fa-solid fa-trash"></i>
                  </button>

                  <span
                    v-else
                    class="live-work-label"
                    title="Current active work"
                  >
                    LIVE
                  </span>
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
import axios from 'axios'
import AppLayout from '../layouts/AppLayout.vue'
import PageHeader from '../layouts/PageHeader.vue'

export default {
  name: 'ActivityLogsView',

  components: {
    AppLayout,
    PageHeader
  },

  data() {
    return {
      loading: false,
      logs: [],
      activeWorkLogs: [],
      filteredLogs: [],

      filters: {
        user: '',
        action: '',
        from: '',
        to: ''
      }
    }
  },

  computed: {
    currentUser() {
      try {
        return JSON.parse(localStorage.getItem('user')) || {}
      } catch (e) {
        return {}
      }
    },

    allLogs() {
      const normalLogs = Array.isArray(this.logs)
        ? this.logs.map(log => ({
            ...log,
            _rowKey: `activity-${log.id}`,
            _virtual: false
          }))
        : []

      const existingStartOrderIds = new Set(
        normalLogs
          .filter(log =>
            ['started_work', 'start_work', 'claimed', 'working_started']
              .includes(this.normalizeAction(log.action))
          )
          .map(log => Number(log.order_id || log.order?.id))
          .filter(Boolean)
      )

      const virtualLogs = this.activeWorkLogs.filter(log => {
        const orderId = Number(log.order_id || log.order?.id)
        return !existingStartOrderIds.has(orderId)
      })

      return [...virtualLogs, ...normalLogs].sort((a, b) => {
        const aTime = new Date(a.created_at || 0).getTime()
        const bTime = new Date(b.created_at || 0).getTime()

        return bTime - aTime
      })
    },

    users() {
      return [
        ...new Set(
          this.allLogs
            .map(log => log.user?.name)
            .filter(Boolean)
        )
      ].sort((a, b) => a.localeCompare(b))
    }
  },

  async mounted() {
    await this.fetchAllData()
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

    openProfile() {
      this.$router.push('/profile')
    },

    initial(name) {
      return String(name || 'U')
        .trim()
        .charAt(0)
        .toUpperCase()
    },

    async fetchAllData() {
      this.loading = true

      try {
        await Promise.all([
          this.fetchLogs(false),
          this.fetchActiveWorkingOrders(false)
        ])

        this.applyFilters()
      } finally {
        this.loading = false
      }
    },

    async fetchLogs(showLoading = true) {
      if (showLoading) {
        this.loading = true
      }

      try {
        const res = await axios.get(
          '/api/order-activities',
          {
            headers: this.headers()
          }
        )

        this.logs = Array.isArray(res.data)
          ? res.data
          : (res.data?.data || [])
      } catch (e) {
        console.error('Activity logs fetch error:', e)

        if (showLoading) {
          alert('Activity logs load nahi huay')
        }

        this.logs = []
      } finally {
        if (showLoading) {
          this.loading = false
        }
      }
    },

    async fetchActiveWorkingOrders(showLoading = true) {
      if (showLoading) {
        this.loading = true
      }

      try {
        const res = await axios.get(
          '/api/orders',
          {
            headers: this.headers()
          }
        )

        const orders = Array.isArray(res.data)
          ? res.data
          : (res.data?.data || [])

        this.activeWorkLogs = orders
          .filter(order => {
            const workingUser = order?.working_by

            const started =
              order?.work_started === true ||
              order?.is_working === true ||
              order?.work_session_active === true ||
              Boolean(order?.working_started_at)

            return Boolean(workingUser) && started
          })
          .map(order => ({
            id: `working-${order.id}`,
            _rowKey: `working-${order.id}`,
            _virtual: true,

            order_id: order.id,

            user: {
              id: order.working_by?.id,
              name: order.working_by?.name || 'Unknown',
              email: order.working_by?.email || '',
              profile_photo_url:
                order.working_by?.profile_photo_url || ''
            },

            order: {
              id: order.id,
              name: order.name || `Order #${order.id}`,
              po: order.po || ''
            },

            action: 'started_work',

            description:
              `${order.working_by?.name || 'User'} started work on ${order.name || `Order #${order.id}`}`,

            created_at:
              order.working_started_at ||
              order.work_started_at ||
              order.updated_at ||
              new Date().toISOString()
          }))
      } catch (e) {
        console.error('Active working orders fetch error:', e)
        this.activeWorkLogs = []
      } finally {
        if (showLoading) {
          this.loading = false
        }
      }
    },

    applyFilters() {
      this.filteredLogs = this.allLogs.filter(log => {
        const userMatch =
          !this.filters.user ||
          log.user?.name === this.filters.user

        const actionMatch =
          !this.filters.action ||
          this.normalizeAction(log.action) === this.filters.action

        const logDate =
          log.created_at
            ? new Date(log.created_at)
            : null

        const fromMatch =
          !this.filters.from ||
          (
            logDate &&
            logDate >= new Date(this.filters.from)
          )

        const toMatch =
          !this.filters.to ||
          (
            logDate &&
            logDate <= new Date(
              this.filters.to + ' 23:59:59'
            )
          )

        return (
          userMatch &&
          actionMatch &&
          fromMatch &&
          toMatch
        )
      })
    },

    clearFilters() {
      this.filters = {
        user: '',
        action: '',
        from: '',
        to: ''
      }

      this.filteredLogs = [...this.allLogs]
    },

    async deleteLog(log) {
      if (!log?.id || log._virtual) return

      if (
        !confirm(
          'Confirm this activity log delete?'
        )
      ) {
        return
      }

      try {
        await axios.delete(
          `/api/order-activities/${log.id}`,
          {
            headers: this.headers()
          }
        )

        await this.fetchAllData()
      } catch (e) {
        console.error(e)
        alert('Activity log delete nahi hua')
      }
    },

    normalizeAction(action) {
      const value = String(action || '')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '_')
        .replace(/-/g, '_')

      if (
        [
          'start_work',
          'started',
          'started_working',
          'working_started',
          'claimed',
          'claim'
        ].includes(value)
      ) {
        return 'started_work'
      }

      return value
    },

    formatAction(action) {
      const normalized =
        this.normalizeAction(action)

      if (!normalized) {
        return '-'
      }

      if (normalized === 'started_work') {
        return 'Started Work'
      }

      if (normalized === 'permanent_deleted') {
        return 'Permanent Deleted'
      }

      return normalized
        .replace(/_/g, ' ')
        .replace(
          /\b\w/g,
          c => c.toUpperCase()
        )
    },

    formatDate(date) {
      if (!date) return 'N/A'

      const parsed = new Date(date)

      if (Number.isNaN(parsed.getTime())) {
        return 'N/A'
      }

      return parsed.toLocaleString()
    }
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.page-wrap {
  min-height: 100vh;
  padding: 0 32px 32px;
  background: #f4f5f8;
}

.activity-content {
  padding-top: 18px;
}

.filter-card {
  background: #fff;
  border-radius: 16px;
  padding: 18px;
  margin: 0 0 24px;

  display: grid;
  grid-template-columns:
    1fr 1fr 1fr 1fr 48px 48px;

  gap: 14px;

  box-shadow:
    0 8px 25px
    rgba(0, 0, 0, 0.06);
}

.field label {
  display: block;

  font-size: 13px;
  font-weight: 900;

  margin-bottom: 8px;
}

.field select,
.field input {
  width: 100%;
  height: 42px;

  border: 1px solid #d1d5db;
  border-radius: 10px;

  padding: 0 12px;

  background: #fff;

  font-weight: 700;
}

.search-btn,
.clear-btn {
  height: 42px;
  margin-top: 25px;

  border: none;
  border-radius: 10px;

  cursor: pointer;

  font-size: 16px;
}

.search-btn {
  background: #111827;
  color: #fff;
}

.clear-btn {
  background: #fff;

  border: 1px solid #9ca3af;

  color: #111827;
}

.table-card {
  background: #fff;

  border-radius: 16px;

  box-shadow:
    0 8px 25px
    rgba(0, 0, 0, 0.08);

  overflow-x: auto;
}

table {
  width: 100%;
  min-width: 1050px;

  border-collapse: collapse;
}

th {
  background: #1f2937;
  color: #fff;

  padding: 14px;

  text-align: left;

  font-size: 13px;
}

td {
  padding: 14px;

  border-bottom: 1px solid #eee;

  text-align: left;

  vertical-align: middle;

  font-size: 13px;
}

td small {
  display: block;

  color: #6b7280;

  margin-top: 3px;
}

.user-cell {
  min-width: 185px;

  display: flex;
  align-items: center;

  gap: 10px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  min-width: 36px;

  display: grid;
  place-items: center;

  overflow: hidden;

  border-radius: 50%;

  background: #111827;
  color: #fff;

  font-size: 12px;
  font-weight: 900;
}

.user-avatar img {
  width: 100%;
  height: 100%;

  display: block;

  object-fit: cover;
}

.user-copy {
  min-width: 0;
}

.user-copy strong,
.user-copy small {
  display: block;

  overflow: hidden;

  white-space: nowrap;

  text-overflow: ellipsis;
}

.order-name {
  display: block;

  max-width: 190px;

  overflow: hidden;

  white-space: nowrap;

  text-overflow: ellipsis;
}

.badge {
  min-height: 28px;

  padding: 6px 10px;

  display: inline-flex;
  align-items: center;

  gap: 6px;

  border-radius: 999px;

  color: #fff;

  font-size: 11px;
  font-weight: 900;

  background: #111827;

  white-space: nowrap;
}

.badge.created {
  background: #16a34a;
}

.badge.updated {
  background: #f59e0b;
}

.badge.deleted {
  background: #ef4444;
}

.badge.restored {
  background: #2563eb;
}

.badge.permanent_deleted {
  background: #7f1d1d;
}

.badge.started_work {
  background: #111827;
}

.delete-btn {
  border: none;

  background: #ef4444;
  color: #fff;

  width: 34px;
  height: 34px;

  border-radius: 10px;

  cursor: pointer;
}

.live-work-label {
  min-width: 40px;
  height: 24px;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  background: #dcfce7;
  color: #15803d;

  border-radius: 999px;

  font-size: 9px;
  font-weight: 900;
}

.empty {
  background: #fff;

  padding: 30px;

  border-radius: 16px;

  font-weight: 800;
}

@media (max-width: 900px) {
  .page-wrap {
    padding: 0 16px 20px;
  }

  .filter-card {
    grid-template-columns: 1fr;
  }
           
  .search-btn,
  .clear-btn {
    margin-top: 0;
  }
}
</style>
