<template>
  <AppLayout>
    <div class="page-wrap">
      <h2>Activity Logs</h2>

      <div v-if="loading" class="empty">Loading...</div>

      <div v-else-if="logs.length === 0" class="empty">
        No activity logs found.
      </div>

      <div v-else class="table-card">
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>User</th>
              <th>Order</th>
              <th>Action</th>
              <th>Description</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td>{{ formatDate(log.created_at) }}</td>
              <td>{{ log.user?.name || 'Unknown' }}</td>
              <td>{{ log.order?.name || 'Deleted Order' }}</td>
              <td>
                <span class="badge">{{ log.action }}</span>
              </td>
              <td>{{ log.description || '-' }}</td>
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
      logs: []
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
      } catch (e) {
        console.error(e)
        alert('Activity logs load nahi huay')
      } finally {
        this.loading = false
      }
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
  padding: 28px;
}

h2 {
  font-size: 28px;
  font-weight: 900;
  margin-bottom: 20px;
}

.table-card {
  background: #fff;
  border-radius: 16px;
  padding: 18px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 14px;
  border-bottom: 1px solid #eee;
  text-align: left;
}

th {
  font-size: 13px;
  color: #555;
  text-transform: uppercase;
}

.badge {
  background: #111827;
  color: #fff;
  padding: 5px 9px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
}

.empty {
  background: #fff;
  padding: 30px;
  border-radius: 16px;
  font-weight: 800;
}
</style>
