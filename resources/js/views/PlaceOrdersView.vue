<template>
  <div class="place-orders-page">
    <aside class="orders-sidebar">
      <div class="sidebar-head">
        <button type="button" class="back-btn" @click="$router.push('/dashboard')">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <div>
          <h2>Place Orders</h2>
          <p>Orders received from Prosix.com</p>
        </div>
        <span v-if="unreadCount" class="count-badge">{{ unreadCount }} new</span>
      </div>

      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input v-model="search" type="text" placeholder="Search place orders..." />
      </div>

      <div class="tabs">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          type="button"
          :class="{ active: activeStatus === tab.key }"
          @click="activeStatus = tab.key"
        >
          {{ tab.label }}
        </button>
      </div>

      <div v-if="loading" class="sidebar-state">
        <i class="fa-solid fa-spinner fa-spin"></i>
        Loading orders...
      </div>

      <div v-else-if="filteredOrders.length === 0" class="sidebar-state">
        <i class="fa-solid fa-inbox"></i>
        No place orders found
      </div>

      <div v-else class="order-list">
        <button
          v-for="order in filteredOrders"
          :key="order.id"
          type="button"
          class="order-row"
          :class="{
            active: Number(selectedOrder?.id) === Number(order.id),
            unread: !order.is_read
          }"
          @click="selectOrder(order)"
        >
          <span v-if="!order.is_read" class="unread-dot"></span>
          <div class="order-row-body">
            <strong>{{ order.order_number || `Order #${order.id}` }}</strong>
            <span>{{ order.full_name || 'Unknown customer' }}</span>
            <small>
              {{ formatDate(order.created_at) }}
              <em :class="statusClass(order.status)">{{ capitalize(order.status || 'pending') }}</em>
            </small>
          </div>
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </aside>

    <main class="orders-content">
      <div v-if="!selectedOrder" class="empty-detail">
        <i class="fa-solid fa-cart-shopping"></i>
        <h3>Select a Place Order</h3>
        <p>Choose an order from the left side.</p>
      </div>

      <template v-else>
        <header class="detail-header">
          <div>
            <span>Place Order</span>
            <h1>{{ selectedOrder.order_number || `Order #${selectedOrder.id}` }}</h1>
            <p>{{ selectedOrder.full_name || 'Customer' }}</p>
          </div>
          <strong class="status-pill" :class="statusClass(selectedOrder.status)">
            {{ capitalize(selectedOrder.status || 'pending') }}
          </strong>
        </header>

        <section class="info-grid">
          <article class="card">
            <h3><i class="fa-solid fa-user"></i> Customer Information</h3>
            <div class="fields">
              <div><span>Full Name</span><strong>{{ selectedOrder.full_name || '—' }}</strong></div>
              <div><span>Email</span><strong>{{ selectedOrder.email || '—' }}</strong></div>
              <div><span>Phone</span><strong>{{ selectedOrder.phone || '—' }}</strong></div>
              <div><span>Sales Rep</span><strong>{{ selectedOrder.sales_rep || '—' }}</strong></div>
            </div>
          </article>

          <article class="card">
            <h3><i class="fa-solid fa-calendar-days"></i> Order Information</h3>
            <div class="fields">
              <div><span>Order Date</span><strong>{{ selectedOrder.order_date || '—' }}</strong></div>
              <div><span>Delivery Date</span><strong>{{ selectedOrder.delivery_date || '—' }}</strong></div>
              <div><span>Team Colors</span><strong>{{ selectedOrder.team_colors || '—' }}</strong></div>
              <div><span>Submitted</span><strong>{{ formatDate(selectedOrder.created_at) }}</strong></div>
            </div>
          </article>
        </section>

        <article class="card notes-card">
          <h3><i class="fa-solid fa-note-sticky"></i> Notes</h3>
          <div v-if="selectedOrder.notes" class="notes" v-html="selectedOrder.notes"></div>
          <p v-else class="muted">No notes were added.</p>
        </article>

        <section class="file-grid">
          <article v-for="group in fileGroups" :key="group.key" class="card">
            <h3>
              <i :class="group.icon"></i>
              {{ group.title }}
              <span class="file-count">{{ group.files.length }}</span>
            </h3>

            <div v-if="group.files.length" class="files">
              <a
                v-for="(file, index) in group.files"
                :key="index"
                :href="fileUrl(file, group.folder)"
                target="_blank"
                rel="noopener noreferrer"
                class="file-card"
              >
                <img
                  v-if="isImage(file)"
                  :src="fileUrl(file, group.folder)"
                  :alt="fileName(file)"
                />
                <div v-else class="file-icon">
                  <i class="fa-solid fa-file"></i>
                  <span>{{ extension(file) }}</span>
                </div>
                <small>{{ fileName(file) }}</small>
              </a>
            </div>

            <p v-else class="muted">No files uploaded.</p>
          </article>
        </section>
      </template>
    </main>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'PlaceOrdersView',

  data() {
    return {
      loading: false,
      orders: [],
      selectedOrder: null,
      search: '',
      activeStatus: 'all',
      prosixBaseUrl: import.meta.env.VITE_PROSIX_URL || 'https://prosix.com',
      tabs: [
        { key: 'all', label: 'All' },
        { key: 'pending', label: 'Pending' },
        { key: 'processing', label: 'Processing' },
        { key: 'completed', label: 'Completed' },
        { key: 'cancelled', label: 'Cancelled' }
      ]
    }
  },

  computed: {
    filteredOrders() {
      const query = String(this.search || '').trim().toLowerCase()

      return this.orders.filter(order => {
        const status = String(order.status || 'pending').toLowerCase()
        const statusMatch = this.activeStatus === 'all' || status === this.activeStatus
        const searchMatch = !query || [
          order.order_number,
          order.full_name,
          order.email,
          order.phone
        ].some(value => String(value || '').toLowerCase().includes(query))

        return statusMatch && searchMatch
      })
    },

    unreadCount() {
      return this.orders.filter(order => !order.is_read).length
    },

    fileGroups() {
      return [
        {
          key: 'mockup',
          title: 'Mockup Files',
          folder: 'mockup',
          icon: 'fa-solid fa-image',
          files: this.selectedOrder?.mockup_files || []
        },
        {
          key: 'roster',
          title: 'Roster Files',
          folder: 'roster',
          icon: 'fa-solid fa-users',
          files: this.selectedOrder?.roster_files || []
        },
        {
          key: 'quote',
          title: 'Quote Files',
          folder: 'quote',
          icon: 'fa-solid fa-file-invoice-dollar',
          files: this.selectedOrder?.quote_files || []
        }
      ]
    }
  },

  async mounted() {
    await this.fetchOrders()
  },

  methods: {
    headers() {
      return {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        Accept: 'application/json'
      }
    },

    async fetchOrders() {
      this.loading = true

      try {
        const response = await axios.get('/api/place-orders', {
          headers: this.headers()
        })

        const data = response.data?.data ?? response.data ?? []
        this.orders = Array.isArray(data) ? data : []

        if (this.orders.length) {
          await this.selectOrder(this.orders[0])
        }
      } catch (error) {
        console.error('Place orders fetch error:', error)
        this.orders = []
      } finally {
        this.loading = false
      }
    },

    async selectOrder(order) {
      this.selectedOrder = order

      if (order.is_read) return

      try {
        await axios.post(
          `/api/place-orders/${order.id}/mark-read`,
          {},
          { headers: this.headers() }
        )

        order.is_read = true
        window.dispatchEvent(new CustomEvent('place-orders-read-updated'))
      } catch (error) {
        console.error('Place order mark-read error:', error)
      }
    },

    fileName(file) {
      if (typeof file === 'string') return file.split('/').pop()

      return file?.original || file?.original_name || file?.filename || file?.file_name || file?.name || 'File'
    },

    fileUrl(file, folder) {
      const raw = typeof file === 'string'
        ? file
        : file?.url || file?.path || file?.filename || file?.file_name || file?.name || ''

      if (!raw) return '#'
      if (raw.startsWith('http://') || raw.startsWith('https://')) return raw
      if (raw.startsWith('/uploads/')) return `${this.prosixBaseUrl}${raw}`
      if (raw.startsWith('uploads/')) return `${this.prosixBaseUrl}/${raw}`

      return `${this.prosixBaseUrl}/uploads/orders/${folder}/${raw}`
    },

    isImage(file) {
      return ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.bmp']
        .some(ext => this.fileName(file).toLowerCase().endsWith(ext))
    },

    extension(file) {
      const parts = this.fileName(file).split('.')
      return parts.length > 1 ? parts.pop().toUpperCase() : 'FILE'
    },

    capitalize(value) {
      const text = String(value || '')
      return text ? text.charAt(0).toUpperCase() + text.slice(1) : ''
    },

    formatDate(value) {
      if (!value) return '—'
      const date = new Date(value)
      if (Number.isNaN(date.getTime())) return value

      return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      })
    },

    statusClass(status) {
      return `status-${String(status || 'pending').toLowerCase()}`
    }
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }
.place-orders-page { min-height: 100vh; display: grid; grid-template-columns: 360px minmax(0, 1fr); background: #f3f4f6; }
.orders-sidebar { height: 100vh; overflow-y: auto; background: #111827; color: white; border-right: 1px solid #1f2937; }
.sidebar-head { min-height: 92px; padding: 18px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,.1); }
.sidebar-head h2 { margin: 0; font-size: 18px; font-weight: 900; }
.sidebar-head p { margin: 3px 0 0; color: #9ca3af; font-size: 11px; }
.back-btn { width: 36px; height: 36px; border: 1px solid rgba(255,255,255,.15); border-radius: 10px; background: rgba(255,255,255,.08); color: white; cursor: pointer; }
.count-badge { margin-left: auto; padding: 5px 9px; border-radius: 999px; background: white; color: #111827; font-size: 11px; font-weight: 900; }
.search-box { margin: 15px; height: 42px; padding: 0 12px; border: 1px solid rgba(255,255,255,.12); border-radius: 10px; background: rgba(255,255,255,.07); display: flex; align-items: center; gap: 9px; }
.search-box input { width: 100%; border: 0; outline: 0; background: transparent; color: white; }
.tabs { padding: 0 15px 12px; display: flex; gap: 6px; overflow-x: auto; }
.tabs button { flex-shrink: 0; padding: 7px 10px; border: 1px solid rgba(255,255,255,.12); border-radius: 999px; background: transparent; color: #9ca3af; font-size: 11px; font-weight: 800; cursor: pointer; }
.tabs button.active { background: white; color: #111827; }
.sidebar-state { min-height: 180px; color: #9ca3af; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; font-size: 12px; }
.order-list { padding: 0 10px 18px; }
.order-row { position: relative; width: 100%; margin-bottom: 7px; padding: 12px; border: 1px solid transparent; border-radius: 12px; background: transparent; color: white; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 10px; }
.order-row:hover, .order-row.active { border-color: rgba(255,255,255,.12); background: rgba(255,255,255,.08); }
.order-row.unread { background: rgba(255,255,255,.12); }
.unread-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; }
.order-row-body { min-width: 0; flex: 1; }
.order-row-body strong, .order-row-body span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.order-row-body strong { font-size: 13px; }
.order-row-body span { margin-top: 3px; color: #d1d5db; font-size: 11px; }
.order-row-body small { margin-top: 7px; color: #9ca3af; display: flex; align-items: center; gap: 7px; }
.order-row-body em { padding: 3px 7px; border-radius: 999px; font-style: normal; font-weight: 900; }
.orders-content { min-width: 0; padding: 28px; overflow-y: auto; }
.empty-detail { min-height: calc(100vh - 56px); color: #6b7280; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.empty-detail > i { width: 78px; height: 78px; margin-bottom: 15px; border-radius: 22px; background: #111827; color: white; font-size: 28px; display: flex; align-items: center; justify-content: center; }
.empty-detail h3 { margin: 0; color: #111827; }
.detail-header { margin-bottom: 20px; padding: 22px; border-radius: 18px; background: #111827; color: white; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.detail-header span { color: #9ca3af; font-size: 11px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
.detail-header h1 { margin: 5px 0 3px; font-size: 25px; }
.detail-header p { margin: 0; color: #d1d5db; }
.status-pill { padding: 8px 13px; border-radius: 999px; font-size: 12px; }
.info-grid, .file-grid { display: grid; gap: 18px; }
.info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.file-grid { margin-top: 18px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
.card { padding: 19px; border: 1px solid #e5e7eb; border-radius: 16px; background: white; box-shadow: 0 8px 24px rgba(15,23,42,.05); }
.card h3 { margin: 0 0 16px; color: #111827; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.fields span { display: block; margin-bottom: 5px; color: #6b7280; font-size: 10px; font-weight: 900; text-transform: uppercase; }
.fields strong { overflow-wrap: anywhere; color: #111827; font-size: 13px; }
.notes-card { margin-top: 18px; }
.notes { color: #374151; font-size: 13px; line-height: 1.7; }
.file-count { margin-left: auto; min-width: 25px; height: 25px; padding: 0 7px; border-radius: 999px; background: #111827; color: white; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; }
.files { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.file-card { min-width: 0; padding: 8px; border: 1px solid #e5e7eb; border-radius: 10px; color: #111827; text-decoration: none; }
.file-card img, .file-icon { width: 100%; height: 95px; border-radius: 8px; background: #f3f4f6; object-fit: contain; }
.file-icon { color: #6b7280; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; }
.file-card small { display: block; overflow: hidden; margin-top: 7px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.muted { color: #6b7280; font-size: 12px; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-processing { background: #dbeafe; color: #1d4ed8; }
.status-completed { background: #dcfce7; color: #166534; }
.status-cancelled, .status-canceled { background: #fee2e2; color: #991b1b; }
@media (max-width: 1100px) { .place-orders-page { grid-template-columns: 320px minmax(0, 1fr); } .file-grid { grid-template-columns: 1fr; } }
@media (max-width: 800px) { .place-orders-page { display: block; } .orders-sidebar { width: 100%; height: auto; max-height: 55vh; } .orders-content { padding: 16px; } .info-grid { grid-template-columns: 1fr; } }
@media (max-width: 520px) { .detail-header { align-items: flex-start; flex-direction: column; } .fields, .files { grid-template-columns: 1fr; } }
</style>
