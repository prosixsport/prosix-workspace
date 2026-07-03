<template>
  <AppLayout>
    <div class="page-wrap">
      <div class="page-head">
        <div>
          <h2>Activity Logs</h2>
          <p>All user create, update, delete and restore record</p>
        </div>
      </div>

      <div class="filter-card">
        <div class="field">
          <label>User</label>
          <select v-model="filters.user">
            <option value="">All Users</option>
            <option v-for="u in users" :key="u" :value="u">{{ u }}</option>
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

      <div v-if="loading" class="empty">Loading...</div>

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
            <tr v-for="(log, index) in filteredLogs" :key="log.id">
              <td>{{ index + 1 }}</td>
              <td>
                <strong>{{ log.user?.name || 'Unknown' }}</strong>
                <small>{{ log.user?.email || '' }}</small>
              </td>
              <td>{{ log.order?.name || 'Deleted Order' }}</td>
              <td>
                <span class="badge" :class="log.action">
                  {{ formatAction(log.action) }}
                </span>
              </td>
              <td>{{ log.description || '-' }}</td>
              <td>{{ formatDate(log.created_at) }}</td>
              <td>
                <button class="delete-btn" @click="deleteLog(log)">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import axios from 'axios'
import AppLayout from '../layouts/AppLayout.vue'

export default {
  name: 'ActivityLogsView',
  components: { AppLayout },

  data() {
    return {
      loading: false,
      logs: [],
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
    users() {
      return [...new Set(this.logs.map(l => l.user?.name).filter(Boolean))]
    }
  },

  mounted() {
    this.fetchLogs()
  },

  methods: {
    headers() {
      return {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        Accept: 'application/json'
      }
    },

    async fetchLogs() {
      this.loading = true

      try {
        const res = await axios.get('/api/order-activities', {
          headers: this.headers()
        })

        this.logs = Array.isArray(res.data) ? res.data : (res.data?.data || [])
        this.filteredLogs = this.logs
      } catch (e) {
        console.error(e)
        alert('Activity logs load nahi huay')
      } finally {
        this.loading = false
      }
    },

    applyFilters() {
      this.filteredLogs = this.logs.filter(log => {
        const userMatch =
          !this.filters.user || log.user?.name === this.filters.user

        const actionMatch =
          !this.filters.action || log.action === this.filters.action

        const logDate = log.created_at ? new Date(log.created_at) : null

        const fromMatch =
          !this.filters.from || (logDate && logDate >= new Date(this.filters.from))

        const toMatch =
          !this.filters.to || (logDate && logDate <= new Date(this.filters.to + ' 23:59:59'))

        return userMatch && actionMatch && fromMatch && toMatch
      })
    },

    clearFilters() {
      this.filters = {
        user: '',
        action: '',
        from: '',
        to: ''
      }

      this.filteredLogs = this.logs
    },

    async deleteLog(log) {
      if (!confirm('confirm this activity log delete?')) return

      try {
        await axios.delete(`/api/order-activities/${log.id}`, {
          headers: this.headers()
        })

        await this.fetchLogs()
      } catch (e) {
        console.error(e)
        alert('Activity log delete nahi hua')
      }
    },

    formatAction(action) {
      if (!action) return '-'
      return action.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
    },

    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleString()
    }
  }
}
</script>

<style scoped>
.page-wrap {
  padding: 32px;
}

.page-head h2 {
  font-size: 28px;
  font-weight: 900;
  margin: 0;
}

.page-head p {
  color: #6b7280;
  margin-top: 6px;
  font-weight: 600;
}

.filter-card {
  background: #fff;
  border-radius: 16px;
  padding: 18px;
  margin: 24px 0;
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr 48px 48px;
  gap: 14px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.06);
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
  padding: 18px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
  overflow-x: auto;
}

table {
  width: 100%;
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
}

td small {
  display: block;
  color: #6b7280;
  margin-top: 3px;
}

.badge {
  color: #fff;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 900;
  background: #111827;
}

.badge.created { background: #16a34a; }
.badge.updated { background: #f59e0b; }
.badge.deleted { background: #ef4444; }
.badge.restored { background: #2563eb; }
.badge.permanent_deleted { background: #7f1d1d; }

.delete-btn {
  border: none;
  background: #ef4444;
  color: #fff;
  width: 34px;
  height: 34px;
  border-radius: 10px;
  cursor: pointer;
}

.empty {
  background: #fff;
  padding: 30px;
  border-radius: 16px;
  font-weight: 800;
}

@media (max-width: 900px) {
  .filter-card {
    grid-template-columns: 1fr;
  }

  .search-btn,
  .clear-btn {
    margin-top: 0;
  }
}
</style>
