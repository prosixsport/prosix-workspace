<template>
  <AppLayout>
    <div class="page-wrap">
      <h2>Recycle Bin</h2>

      <div v-if="loading" class="empty">Loading...</div>

      <div v-else-if="orders.length === 0" class="empty">
        No deleted orders found.
      </div>

      <div v-else class="table-card">
        <table>
          <thead>
            <tr>
              <th>Order</th>
              <th>PO</th>
              <th>Deleted By</th>
              <th>Deleted At</th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="order in orders" :key="order.id">
              <td>{{ order.name }}</td>
              <td>{{ order.po || 'N/A' }}</td>
              <td>{{ order.deleted_by || 'Unknown' }}</td>
              <td>{{ formatDate(order.deleted_at) }}</td>
              <td>
                <button class="btn restore" @click="restoreOrder(order)">Restore</button>
                <button class="btn danger" @click="forceDelete(order)">Permanent Delete</button>
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
  name: 'RecycleBinView',
  components: { AppLayout },

  data() {
    return {
      loading: false,
      orders: []
    }
  },

  mounted() {
    this.fetchRecycleBin()
  },

  methods: {
    headers() {
      return {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        Accept: 'application/json'
      }
    },

    async fetchRecycleBin() {
      this.loading = true

      try {
        const res = await axios.get('/api/orders/recycle-bin', {
          headers: this.headers()
        })

        this.orders = Array.isArray(res.data) ? res.data : (res.data?.data || [])
      } catch (e) {
        console.error(e)
        alert('Recycle bin load nahi hui')
      } finally {
        this.loading = false
      }
    },

    async restoreOrder(order) {
      try {
        await axios.post(`/api/orders/${order.id}/restore`, {}, {
          headers: this.headers()
        })

        await this.fetchRecycleBin()
      } catch (e) {
        console.error(e)
        alert('Order restore nahi hua')
      }
    },

    async forceDelete(order) {
      if (!confirm('Permanent delete karna hai? Ye wapas nahi ayega.')) return

      try {
        await axios.delete(`/api/orders/${order.id}/force-delete`, {
          headers: this.headers()
        })

        await this.fetchRecycleBin()
      } catch (e) {
        console.error(e)
        alert('Permanent delete nahi hua')
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
.page-wrap { padding: 28px; }
h2 { font-size: 28px; font-weight: 900; margin-bottom: 20px; }
.table-card { background: #fff; border-radius: 16px; padding: 18px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 14px; border-bottom: 1px solid #eee; text-align: left; }
th { font-size: 13px; color: #555; text-transform: uppercase; }
.btn { border: none; padding: 8px 12px; border-radius: 10px; font-weight: 800; margin-right: 8px; cursor: pointer; }
.restore { background: #00c875; color: #fff; }
.danger { background: #ef4444; color: #fff; }
.empty { background: #fff; padding: 30px; border-radius: 16px; font-weight: 800; }
</style>
