<template>
  <div class="teamstore-orders-page">
    <aside class="orders-sidebar">
      <div class="sidebar-head">
        <button
          type="button"
          class="back-btn"
          @click="$router.push('/dashboard')"
        >
          <i class="fa-solid fa-arrow-left"></i>
        </button>

        <div>
          <h2>TeamStore Orders</h2>
          <p>Orders received from Prosix TeamStore</p>
        </div>

        <span
          v-if="unreadCount"
          class="count-badge"
        >
          {{ unreadCount }} new
        </span>
      </div>

      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>

        <input
          v-model="search"
          type="text"
          placeholder="Search TeamStore orders..."
        />
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

      <div
        v-if="loading"
        class="sidebar-state"
      >
        <i class="fa-solid fa-spinner fa-spin"></i>
        Loading orders...
      </div>

      <div
        v-else-if="filteredOrders.length === 0"
        class="sidebar-state"
      >
        <i class="fa-solid fa-inbox"></i>
        No TeamStore orders found
      </div>

      <div
        v-else
        class="order-list"
      >
        <button
          v-for="order in filteredOrders"
          :key="order.id"
          type="button"
          class="order-row"
          :class="{
            active:
              Number(selectedOrder?.id) ===
              Number(order.id),

            unread: !order.is_read
          }"
          @click="selectOrder(order)"
        >
          <span
            v-if="!order.is_read"
            class="unread-dot"
          ></span>

          <div class="order-row-body">
            <strong>
              {{ order.order_number || `Order #${order.id}` }}
            </strong>

            <span>
              {{ order.customer_name || 'Unknown customer' }}
            </span>

            <small>
              {{ formatDate(order.created_at) }}

              <em :class="statusClass(order.status)">
                {{ capitalize(order.status || 'new') }}
              </em>
            </small>
          </div>

          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </aside>

    <main class="orders-content">
      <div
        v-if="!selectedOrder"
        class="empty-detail"
      >
        <i class="fa-solid fa-store"></i>
        <h3>Select a TeamStore Order</h3>
        <p>Choose an order from the left side.</p>
      </div>

      <template v-else>
        <header class="detail-header">
          <div>
            <span>TeamStore Order</span>

            <h1>
              {{
                selectedOrder.order_number ||
                `Order #${selectedOrder.id}`
              }}
            </h1>

            <p>
              {{ selectedOrder.customer_name || 'Customer' }}
            </p>
          </div>

          <div class="header-statuses">
            <strong
              class="status-pill"
              :class="statusClass(selectedOrder.status)"
            >
              {{ capitalize(selectedOrder.status || 'new') }}
            </strong>

            <strong
              class="payment-pill"
              :class="paymentStatusClass(
                selectedOrder.payment_status
              )"
            >
              {{
                capitalize(
                  selectedOrder.payment_status || 'pending'
                )
              }}
            </strong>
          </div>
        </header>

        <section class="summary-grid">
          <article class="summary-card">
            <span>Total Amount</span>
            <strong>
              {{
                formatCurrency(
                  selectedOrder.total,
                  selectedOrder.currency
                )
              }}
            </strong>
          </article>

          <article class="summary-card">
            <span>Payment Method</span>
            <strong>
              {{
                capitalize(
                  selectedOrder.payment_method || '—'
                )
              }}
            </strong>
          </article>

          <article class="summary-card">
            <span>Delivery</span>
            <strong>
              {{ selectedOrder.delivery_days || '—' }}
            </strong>
          </article>

          <article class="summary-card">
            <span>Items</span>
            <strong>
              {{ normalizedItems.length }}
            </strong>
          </article>
        </section>

        <section class="info-grid">
          <article class="card">
            <h3>
              <i class="fa-solid fa-user"></i>
              Customer Information
            </h3>

            <div class="fields">
              <div>
                <span>Full Name</span>
                <strong>
                  {{ selectedOrder.customer_name || '—' }}
                </strong>
              </div>

              <div>
                <span>Email</span>
                <strong>
                  {{ selectedOrder.email || '—' }}
                </strong>
              </div>

              <div>
                <span>Phone</span>
                <strong>
                  {{ selectedOrder.phone || '—' }}
                </strong>
              </div>

              <div>
                <span>Submitted</span>
                <strong>
                  {{ formatDate(selectedOrder.created_at) }}
                </strong>
              </div>
            </div>
          </article>

          <article class="card">
            <h3>
              <i class="fa-solid fa-location-dot"></i>
              Shipping Information
            </h3>

            <div class="fields">
              <div>
                <span>Address</span>
                <strong>
                  {{ selectedOrder.shipping_address || '—' }}
                </strong>
              </div>

              <div>
                <span>City</span>
                <strong>
                  {{ selectedOrder.shipping_city || '—' }}
                </strong>
              </div>

              <div>
                <span>Province</span>
                <strong>
                  {{ selectedOrder.shipping_province || '—' }}
                </strong>
              </div>

              <div>
                <span>Postal Code</span>
                <strong>
                  {{
                    selectedOrder.shipping_postal_code || '—'
                  }}
                </strong>
              </div>
            </div>
          </article>
        </section>

        <section class="info-grid second-row">
          <article class="card">
            <h3>
              <i class="fa-solid fa-truck-fast"></i>
              Tracking Information
            </h3>

            <div class="fields">
              <div>
                <span>Courier</span>
                <strong>
                  {{ selectedOrder.courier_name || '—' }}
                </strong>
              </div>

              <div>
                <span>Tracking Number</span>
                <strong>
                  {{ selectedOrder.tracking_number || '—' }}
                </strong>
              </div>
            </div>
          </article>

          <article class="card">
            <h3>
              <i class="fa-solid fa-credit-card"></i>
              Payment Information
            </h3>

            <div class="fields">
              <div>
                <span>Payment Status</span>
                <strong>
                  {{
                    capitalize(
                      selectedOrder.payment_status || 'pending'
                    )
                  }}
                </strong>
              </div>

              <div>
                <span>Payment Method</span>
                <strong>
                  {{
                    capitalize(
                      selectedOrder.payment_method || '—'
                    )
                  }}
                </strong>
              </div>
            </div>
          </article>
        </section>

        <article class="card items-card">
          <h3>
            <i class="fa-solid fa-box-open"></i>
            Order Items

            <span class="item-count">
              {{ normalizedItems.length }}
            </span>
          </h3>

          <div
            v-if="normalizedItems.length"
            class="items-table-wrap"
          >
            <table class="items-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Size</th>
                  <th>Qty</th>
                  <th>Price</th>
                  <th>Total</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="(item, index) in normalizedItems"
                  :key="item.id || index"
                >
                  <td>
                    <div class="product-cell">
                      <img
                        v-if="itemImage(item)"
                        :src="itemImage(item)"
                        :alt="item.name || 'Product'"
                        @error="hideBrokenImage"
                      />

                      <span v-else class="product-placeholder">
                        <i class="fa-solid fa-shirt"></i>
                      </span>

                      <div>
                        <strong>
                          {{ item.name || 'Product' }}
                        </strong>

                        <small v-if="item.color">
                          {{ item.color }}
                        </small>
                      </div>
                    </div>
                  </td>

                  <td>{{ item.size || '—' }}</td>
                  <td>{{ item.quantity || 1 }}</td>

                  <td>
                    {{
                      formatCurrency(
                        item.price,
                        selectedOrder.currency
                      )
                    }}
                  </td>

                  <td>
                    {{
                      formatCurrency(
                        itemTotal(item),
                        selectedOrder.currency
                      )
                    }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <p v-else class="muted">
            No order items found.
          </p>
        </article>

        <article class="card notes-card">
          <h3>
            <i class="fa-solid fa-note-sticky"></i>
            Admin Notes
          </h3>

          <div
            v-if="selectedOrder.admin_notes"
            class="notes"
          >
            {{ selectedOrder.admin_notes }}
          </div>

          <p v-else class="muted">
            No admin notes were added.
          </p>
        </article>
      </template>
    </main>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'TeamStoreOrdersView',

  data() {
    return {
      loading: false,
      orders: [],
      selectedOrder: null,
      search: '',
      activeStatus: 'all',

      tabs: [
        { key: 'all', label: 'All' },
        { key: 'new', label: 'New' },
        { key: 'confirmed', label: 'Confirmed' },
        { key: 'production', label: 'Production' },
        { key: 'shipped', label: 'Shipped' },
        { key: 'delivered', label: 'Delivered' },
        { key: 'cancelled', label: 'Cancelled' }
      ]
    }
  },

  computed: {
    filteredOrders() {
      const query = String(this.search || '')
        .trim()
        .toLowerCase()

      return this.orders.filter(order => {
        const status = String(
          order.status || 'new'
        ).toLowerCase()

        const statusMatch =
          this.activeStatus === 'all' ||
          status === this.activeStatus

        const searchMatch =
          !query ||
          [
            order.order_number,
            order.customer_name,
            order.email,
            order.phone,
            order.tracking_number
          ].some(value =>
            String(value || '')
              .toLowerCase()
              .includes(query)
          )

        return statusMatch && searchMatch
      })
    },

    unreadCount() {
      return this.orders.filter(
        order => !order.is_read
      ).length
    },

    normalizedItems() {
      const items = this.selectedOrder?.items

      if (Array.isArray(items)) {
        return items
      }

      if (typeof items === 'string') {
        try {
          const parsed = JSON.parse(items)
          return Array.isArray(parsed) ? parsed : []
        } catch {
          return []
        }
      }

      return []
    }
  },

  async mounted() {
    await this.fetchOrders()
  },

  methods: {
    headers() {
      return {
        Authorization:
          `Bearer ${localStorage.getItem('token')}`,

        Accept: 'application/json'
      }
    },

    async fetchOrders() {
      this.loading = true

      try {
        const response = await axios.get(
          '/api/teamstore-orders',
          {
            headers: this.headers()
          }
        )

        const data =
          response.data?.data ??
          response.data ??
          []

        this.orders = Array.isArray(data)
          ? data
          : []

        if (this.orders.length) {
          await this.selectOrder(this.orders[0])
        }
      } catch (error) {
        console.error(
          'TeamStore orders fetch error:',
          error
        )

        this.orders = []
      } finally {
        this.loading = false
      }
    },

    async selectOrder(order) {
      this.selectedOrder = order

      if (order.is_read) {
        return
      }

      try {
        await axios.post(
          `/api/teamstore-orders/${order.id}/mark-read`,
          {},
          {
            headers: this.headers()
          }
        )

        order.is_read = true

        window.dispatchEvent(
          new CustomEvent(
            'teamstore-orders-read-updated'
          )
        )
      } catch (error) {
        console.error(
          'TeamStore mark-read error:',
          error
        )
      }
    },

    itemTotal(item) {
      return (
        Number(item?.price || 0) *
        Number(item?.quantity || 1)
      )
    },

    itemImage(item) {
      const raw =
        item?.image ||
        item?.thumbnail ||
        item?.image_url ||
        item?.thumbnail_url ||
        ''

      if (!raw) {
        return ''
      }

      if (
        raw.startsWith('http://') ||
        raw.startsWith('https://')
      ) {
        return raw
      }

      const base =
        import.meta.env.VITE_PROSIX_URL ||
        'https://prosix.com'

      return raw.startsWith('/')
        ? `${base}${raw}`
        : `${base}/${raw}`
    },

    hideBrokenImage(event) {
      event.target.style.display = 'none'
    },

    capitalize(value) {
      const text = String(value || '')

      return text
        ? text.charAt(0).toUpperCase() +
            text.slice(1).replaceAll('_', ' ')
        : ''
    },

    formatDate(value) {
      if (!value) {
        return '—'
      }

      const date = new Date(value)

      if (Number.isNaN(date.getTime())) {
        return value
      }

      return date.toLocaleDateString(
        'en-US',
        {
          day: '2-digit',
          month: 'short',
          year: 'numeric'
        }
      )
    },

    formatCurrency(value, currency = 'USD') {
      const amount = Number(value || 0)

      try {
        return new Intl.NumberFormat(
          'en-US',
          {
            style: 'currency',
            currency:
              String(currency || 'USD')
                .toUpperCase()
          }
        ).format(amount)
      } catch {
        return `$${amount.toFixed(2)}`
      }
    },

    statusClass(status) {
      return `status-${String(
        status || 'new'
      ).toLowerCase()}`
    },

    paymentStatusClass(status) {
      return `payment-${String(
        status || 'pending'
      ).toLowerCase()}`
    }
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.teamstore-orders-page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 350px minmax(0, 1fr);
  background: #f3f4f6;
}

.orders-sidebar {
  height: 100vh;
  overflow-y: auto;
  background: #111827;
  color: #ffffff;
  border-right: 1px solid #1f2937;
}

.sidebar-head {
  min-height: 92px;
  padding: 18px;
  border-bottom: 1px solid rgba(255,255,255,.1);
  display: flex;
  align-items: center;
  gap: 12px;
}

.sidebar-head h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 900;
}

.sidebar-head p {
  margin: 3px 0 0;
  color: #9ca3af;
  font-size: 11px;
}

.back-btn {
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  border: 1px solid rgba(255,255,255,.15);
  border-radius: 10px;
  background: rgba(255,255,255,.08);
  color: #ffffff;
  cursor: pointer;
}

.count-badge {
  margin-left: auto;
  padding: 5px 9px;
  border-radius: 999px;
  background: #ffffff;
  color: #111827;
  font-size: 11px;
  font-weight: 900;
}

.search-box {
  height: 42px;
  margin: 15px;
  padding: 0 12px;
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 10px;
  background: rgba(255,255,255,.07);
  display: flex;
  align-items: center;
  gap: 9px;
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #ffffff;
}

.tabs {
  padding: 0 15px 12px;
  display: flex;
  gap: 6px;
  overflow-x: auto;
}

.tabs button {
  flex-shrink: 0;
  padding: 7px 10px;
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 999px;
  background: transparent;
  color: #9ca3af;
  font-size: 10px;
  font-weight: 800;
  cursor: pointer;
}

.tabs button.active {
  background: #ffffff;
  color: #111827;
}

.sidebar-state {
  min-height: 180px;
  color: #9ca3af;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 12px;
}

.order-list {
  padding: 0 10px 18px;
}

.order-row {
  position: relative;
  width: 100%;
  margin-bottom: 7px;
  padding: 12px;
  border: 1px solid transparent;
  border-radius: 12px;
  background: transparent;
  color: #ffffff;
  text-align: left;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
}

.order-row:hover,
.order-row.active {
  border-color: rgba(255,255,255,.12);
  background: rgba(255,255,255,.08);
}

.order-row.unread {
  background: rgba(255,255,255,.12);
}

.unread-dot {
  width: 8px;
  height: 8px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #22c55e;
}

.order-row-body {
  min-width: 0;
  flex: 1;
}

.order-row-body strong,
.order-row-body span {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.order-row-body strong {
  font-size: 13px;
}

.order-row-body span {
  margin-top: 3px;
  color: #d1d5db;
  font-size: 11px;
}

.order-row-body small {
  margin-top: 7px;
  color: #9ca3af;
  display: flex;
  align-items: center;
  gap: 7px;
}

.order-row-body em {
  padding: 3px 7px;
  border-radius: 999px;
  font-style: normal;
  font-weight: 900;
}

.orders-content {
  min-width: 0;
  height: 100vh;
  padding: 24px;
  overflow-y: auto;
}

.empty-detail {
  min-height: calc(100vh - 48px);
  color: #6b7280;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.empty-detail > i {
  width: 78px;
  height: 78px;
  margin-bottom: 15px;
  border-radius: 22px;
  background: #111827;
  color: #ffffff;
  font-size: 28px;
  display: grid;
  place-items: center;
}

.empty-detail h3 {
  margin: 0;
  color: #111827;
}

.detail-header {
  margin-bottom: 16px;
  padding: 22px;
  border-radius: 18px;
  background: #111827;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.detail-header span {
  color: #9ca3af;
  font-size: 11px;
  font-weight: 900;
  letter-spacing: .12em;
  text-transform: uppercase;
}

.detail-header h1 {
  margin: 5px 0 3px;
  font-size: 25px;
}

.detail-header p {
  margin: 0;
  color: #d1d5db;
}

.header-statuses {
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-pill,
.payment-pill {
  padding: 8px 13px;
  border-radius: 999px;
  font-size: 11px;
}

.summary-grid {
  margin-bottom: 16px;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.summary-card {
  padding: 15px;
  border: 1px solid #e5e7eb;
  border-radius: 13px;
  background: #ffffff;
}

.summary-card span {
  display: block;
  color: #6b7280;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
}

.summary-card strong {
  display: block;
  margin-top: 7px;
  color: #111827;
  font-size: 17px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.second-row {
  margin-top: 16px;
}

.card {
  padding: 18px;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #ffffff;
  box-shadow: 0 8px 24px rgba(15,23,42,.04);
}

.card h3 {
  margin: 0 0 16px;
  color: #111827;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.fields span {
  display: block;
  margin-bottom: 5px;
  color: #6b7280;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
}

.fields strong {
  overflow-wrap: anywhere;
  color: #111827;
  font-size: 13px;
}

.items-card,
.notes-card {
  margin-top: 16px;
}

.item-count {
  margin-left: auto;
  min-width: 25px;
  height: 25px;
  padding: 0 7px;
  border-radius: 999px;
  background: #111827;
  color: #ffffff;
  font-size: 11px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.items-table-wrap {
  overflow-x: auto;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
}

.items-table th,
.items-table td {
  padding: 10px;
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
  font-size: 11px;
}

.items-table th {
  color: #6b7280;
  font-size: 9px;
  text-transform: uppercase;
}

.product-cell {
  min-width: 190px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.product-cell img,
.product-placeholder {
  width: 45px;
  height: 45px;
  flex-shrink: 0;
  border-radius: 9px;
  background: #f3f4f6;
  object-fit: contain;
}

.product-placeholder {
  color: #9ca3af;
  display: grid;
  place-items: center;
}

.product-cell strong,
.product-cell small {
  display: block;
}

.product-cell small {
  margin-top: 3px;
  color: #6b7280;
  font-size: 9px;
}

.notes {
  color: #374151;
  font-size: 13px;
  line-height: 1.7;
  white-space: pre-wrap;
}

.muted {
  color: #6b7280;
  font-size: 12px;
}

.status-new {
  background: #e5e7eb;
  color: #374151;
}

.status-confirmed {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-production {
  background: #fef3c7;
  color: #92400e;
}

.status-shipped {
  background: #ede9fe;
  color: #6d28d9;
}

.status-delivered {
  background: #dcfce7;
  color: #166534;
}

.status-cancelled,
.status-canceled {
  background: #fee2e2;
  color: #991b1b;
}

.payment-paid {
  background: #dcfce7;
  color: #166534;
}

.payment-pending {
  background: #fef3c7;
  color: #92400e;
}

.payment-failed,
.payment-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

@media (max-width: 1100px) {
  .teamstore-orders-page {
    grid-template-columns: 310px minmax(0, 1fr);
  }

  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 800px) {
  .teamstore-orders-page {
    display: block;
  }

  .orders-sidebar {
    width: 100%;
    height: auto;
    max-height: 55vh;
  }

  .orders-content {
    height: auto;
    padding: 16px;
  }

  .info-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 520px) {
  .detail-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .header-statuses {
    flex-wrap: wrap;
  }

  .summary-grid,
  .fields {
    grid-template-columns: 1fr;
  }
}
</style>
