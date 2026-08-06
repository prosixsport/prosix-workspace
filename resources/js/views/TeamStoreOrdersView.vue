<template>
  <div class="teamstore-page">
    <header class="page-header">
      <div class="header-left">
        <button
          type="button"
          class="back-button"
          @click="$router.push('/dashboard')"
        >
          <i class="fa-solid fa-arrow-left"></i>
        </button>

        <div>
          <p class="eyebrow">Prosix TeamStore</p>
          <h1>TeamStore Orders</h1>
          <span>
            Category-wise orders received from TeamStore
          </span>
        </div>
      </div>

      <div class="header-actions">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>

          <input
            v-model="search"
            type="text"
            placeholder="Search order, customer or category..."
          />
        </div>

        <span
          v-if="unreadCount > 0"
          class="new-count"
        >
          {{ unreadCount }} new
        </span>
      </div>
    </header>

    <main class="page-content">
      <section class="category-section">
        <div class="section-heading">
          <div>
            <h2>Order Categories</h2>
            <p>Select a category to view related orders.</p>
          </div>

          <button
            type="button"
            class="refresh-button"
            :disabled="loading"
            @click="fetchOrders"
          >
            <i
              class="fa-solid fa-rotate"
              :class="{ 'fa-spin': loading }"
            ></i>
            Refresh
          </button>
        </div>

        <div class="category-grid">
          <button
            v-for="category in categoryCards"
            :key="category.key"
            type="button"
            class="category-card"
            :class="{ active: activeCategory === category.key }"
            @click="activeCategory = category.key"
          >
            <span class="category-icon">
              <img
                v-if="category.image"
                :src="category.image"
                :alt="category.label"
                @error="hideBrokenImage"
              />

              <i
                v-else
                :class="category.icon"
              ></i>
            </span>

            <span class="category-info">
              <strong>{{ category.label }}</strong>
              <small>
                {{ category.count }}
                {{ category.count === 1 ? 'order' : 'orders' }}
              </small>
            </span>

            <i class="fa-solid fa-chevron-right card-arrow"></i>
          </button>
        </div>
      </section>

      <section class="filters-row">
        <div class="status-tabs">
          <button
            v-for="tab in statusTabs"
            :key="tab.key"
            type="button"
            :class="{ active: activeStatus === tab.key }"
            @click="activeStatus = tab.key"
          >
            <span
              class="status-dot"
              :class="`dot-${tab.key}`"
            ></span>

            {{ tab.label }}

            <strong>{{ statusCount(tab.key) }}</strong>
          </button>
        </div>
      </section>

      <section class="orders-panel">
        <div class="panel-header">
          <div>
            <h2>
              {{ activeCategoryLabel }}
            </h2>

            <p>
              {{ filteredOrders.length }}
              {{ filteredOrders.length === 1 ? 'order' : 'orders' }}
            </p>
          </div>
        </div>

        <div
          v-if="loading"
          class="empty-state"
        >
          <i class="fa-solid fa-spinner fa-spin"></i>
          <h3>Loading TeamStore orders...</h3>
        </div>

        <div
          v-else-if="filteredOrders.length === 0"
          class="empty-state"
        >
          <i class="fa-solid fa-box-open"></i>
          <h3>No orders found</h3>
          <p>Try another category, status or search.</p>
        </div>

        <div
          v-else
          class="table-wrap"
        >
          <table class="orders-table">
            <thead>
              <tr>
                <th>Order</th>
                <th>Category</th>
                <th>Items</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Shipping</th>
                <th>Tracking</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="order in filteredOrders"
                :key="order.id"
                :class="{ unread: !order.is_read }"
              >
                <td>
                  <div class="order-number-cell">
                    <span
                      v-if="!order.is_read"
                      class="unread-dot"
                    ></span>

                    <div>
                      <strong>
                        {{ order.order_number || `#${order.id}` }}
                      </strong>

                      <small>
                        ID #{{ order.id }}
                      </small>
                    </div>
                  </div>
                </td>

                <td>
                  <div class="category-tags">
                    <span
                      v-for="category in orderCategories(order).slice(0, 2)"
                      :key="category"
                    >
                      {{ category }}
                    </span>

                    <em
                      v-if="orderCategories(order).length > 2"
                    >
                      +{{ orderCategories(order).length - 2 }}
                    </em>
                  </div>
                </td>

                <td>
                  <div class="item-thumbnails">
                    <span
                      v-for="(item, index) in normalizedItems(order).slice(0, 3)"
                      :key="item.id || index"
                      class="item-thumb"
                    >
                      <img
                        v-if="itemImage(item)"
                        :src="itemImage(item)"
                        :alt="itemName(item)"
                        @error="hideBrokenImage"
                      />

                      <i
                        v-else
                        class="fa-solid fa-shirt"
                      ></i>
                    </span>

                    <span
                      v-if="normalizedItems(order).length > 3"
                      class="more-items"
                    >
                      +{{ normalizedItems(order).length - 3 }}
                    </span>
                  </div>
                </td>

                <td>
                  <div class="customer-cell">
                    <strong>
                      {{ order.customer_name || 'Unknown customer' }}
                    </strong>

                    <small>{{ order.email || 'No email' }}</small>
                  </div>
                </td>

                <td>
                  <span class="plain-text">
                    {{ order.phone || '—' }}
                  </span>
                </td>

                <td>
                  <span
                    class="status-badge"
                    :class="statusClass(order.status)"
                  >
                    {{ formatLabel(order.status || 'new') }}
                  </span>
                </td>

                <td>
                  <div class="shipping-cell">
                    <strong>
                      {{ order.shipping_city || '—' }}
                    </strong>

                    <small>
                      {{ order.shipping_province || '' }}
                    </small>
                  </div>
                </td>

                <td>
                  <div class="tracking-cell">
                    <strong>
                      {{ order.courier_name || '—' }}
                    </strong>

                    <small>
                      {{ order.tracking_number || 'No tracking' }}
                    </small>
                  </div>
                </td>

                <td>
                  <span class="date-cell">
                    {{ formatDate(order.created_at) }}
                  </span>
                </td>

                <td>
                  <button
                    type="button"
                    class="view-button"
                    @click="openOrder(order)"
                  >
                    <i class="fa-regular fa-eye"></i>
                    View
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <div
      v-if="selectedOrder"
      class="modal-overlay"
      @click.self="closeOrder"
    >
      <section class="order-modal">
        <header class="modal-header">
          <div>
            <p>TeamStore Order</p>

            <h2>
              {{
                selectedOrder.order_number ||
                `Order #${selectedOrder.id}`
              }}
            </h2>

            <span>
              {{ selectedOrder.customer_name || 'Customer' }}
            </span>
          </div>

          <div class="modal-header-actions">
            <span
              class="status-badge large"
              :class="statusClass(selectedOrder.status)"
            >
              {{ formatLabel(selectedOrder.status || 'new') }}
            </span>

            <button
              type="button"
              class="modal-close"
              @click="closeOrder"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </header>

        <div class="modal-body">
          <section class="detail-grid">
            <article class="detail-card">
              <h3>
                <i class="fa-solid fa-user"></i>
                Customer Information
              </h3>

              <dl>
                <div>
                  <dt>Full Name</dt>
                  <dd>{{ selectedOrder.customer_name || '—' }}</dd>
                </div>

                <div>
                  <dt>Email</dt>
                  <dd>{{ selectedOrder.email || '—' }}</dd>
                </div>

                <div>
                  <dt>Phone</dt>
                  <dd>{{ selectedOrder.phone || '—' }}</dd>
                </div>

                <div>
                  <dt>Order Date</dt>
                  <dd>{{ formatDateTime(selectedOrder.created_at) }}</dd>
                </div>
              </dl>
            </article>

            <article class="detail-card">
              <h3>
                <i class="fa-solid fa-location-dot"></i>
                Shipping Information
              </h3>

              <dl>
                <div class="full-row">
                  <dt>Address</dt>
                  <dd>{{ selectedOrder.shipping_address || '—' }}</dd>
                </div>

                <div>
                  <dt>City</dt>
                  <dd>{{ selectedOrder.shipping_city || '—' }}</dd>
                </div>

                <div>
                  <dt>Province</dt>
                  <dd>{{ selectedOrder.shipping_province || '—' }}</dd>
                </div>

                <div>
                  <dt>Postal Code</dt>
                  <dd>{{ selectedOrder.shipping_postal_code || '—' }}</dd>
                </div>

                <div>
                  <dt>Delivery</dt>
                  <dd>{{ selectedOrder.delivery_days || '—' }}</dd>
                </div>
              </dl>
            </article>

            <article class="detail-card">
              <h3>
                <i class="fa-solid fa-truck-fast"></i>
                Tracking Information
              </h3>

              <dl>
                <div>
                  <dt>Courier</dt>
                  <dd>{{ selectedOrder.courier_name || '—' }}</dd>
                </div>

                <div>
                  <dt>Tracking Number</dt>
                  <dd>{{ selectedOrder.tracking_number || '—' }}</dd>
                </div>

                <div>
                  <dt>Dispatch Date</dt>
                  <dd>{{ formatDate(selectedOrder.dispatch_date) }}</dd>
                </div>

                <div>
                  <dt>Delivered Date</dt>
                  <dd>{{ formatDate(selectedOrder.delivered_date) }}</dd>
                </div>
              </dl>
            </article>

            <article class="detail-card">
              <h3>
                <i class="fa-solid fa-circle-info"></i>
                Order Information
              </h3>

              <dl>
                <div>
                  <dt>Status</dt>
                  <dd>{{ formatLabel(selectedOrder.status || 'new') }}</dd>
                </div>

                <div>
                  <dt>Payment Status</dt>
                  <dd>
                    {{
                      formatLabel(
                        selectedOrder.payment_status || 'pending'
                      )
                    }}
                  </dd>
                </div>

                <div class="full-row">
                  <dt>Categories</dt>
                  <dd>
                    {{ orderCategories(selectedOrder).join(', ') }}
                  </dd>
                </div>
              </dl>
            </article>
          </section>

          <section class="items-section">
            <div class="items-heading">
              <div>
                <h3>Order Items</h3>
                <p>
                  {{ normalizedItems(selectedOrder).length }}
                  items in this order
                </p>
              </div>
            </div>

            <div
              v-if="normalizedItems(selectedOrder).length"
              class="modal-items-grid"
            >
              <article
                v-for="(item, index) in normalizedItems(selectedOrder)"
                :key="item.id || index"
                class="modal-item-card"
              >
                <div class="modal-item-image">
                  <img
                    v-if="itemImage(item)"
                    :src="itemImage(item)"
                    :alt="itemName(item)"
                    @error="hideBrokenImage"
                  />

                  <i
                    v-else
                    class="fa-solid fa-shirt"
                  ></i>
                </div>

                <div class="modal-item-info">
                  <span>{{ itemCategory(item) }}</span>

                  <h4>{{ itemName(item) }}</h4>

                  <div class="item-meta">
                    <em>
                      Size:
                      <strong>{{ item.size || item.selected_size || '—' }}</strong>
                    </em>

                    <em>
                      Qty:
                      <strong>{{ item.quantity || item.qty || 1 }}</strong>
                    </em>

                    <em v-if="item.color || item.selected_color">
                      Color:
                      <strong>
                        {{ item.color || item.selected_color }}
                      </strong>
                    </em>
                  </div>
                </div>
              </article>
            </div>

            <div
              v-else
              class="no-items"
            >
              No item details found.
            </div>
          </section>

          <section
            v-if="selectedOrder.admin_notes"
            class="notes-card"
          >
            <h3>
              <i class="fa-solid fa-note-sticky"></i>
              Admin Notes
            </h3>

            <p>{{ selectedOrder.admin_notes }}</p>
          </section>
        </div>
      </section>
    </div>
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
      activeCategory: 'all',
      activeStatus: 'all',

      statusTabs: [
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
    unreadCount() {
      return this.orders.filter(order => !order.is_read).length
    },

    allCategories() {
      const map = new Map()

      this.orders.forEach(order => {
        this.normalizedItems(order).forEach(item => {
          const name = this.itemCategory(item)

          if (!map.has(name)) {
            map.set(name, {
              key: this.slug(name),
              label: name,
              image: this.itemImage(item),
              icon: this.categoryIcon(name),
              orderIds: new Set()
            })
          }

          map.get(name).orderIds.add(order.id)
        })
      })

      return Array.from(map.values())
        .map(category => ({
          ...category,
          count: category.orderIds.size
        }))
        .sort((a, b) => a.label.localeCompare(b.label))
    },

    categoryCards() {
      return [
        {
          key: 'all',
          label: 'All Categories',
          count: this.orders.length,
          image: '',
          icon: 'fa-solid fa-border-all'
        },
        ...this.allCategories
      ]
    },

    activeCategoryLabel() {
      return (
        this.categoryCards.find(
          category => category.key === this.activeCategory
        )?.label || 'All Categories'
      )
    },

    filteredOrders() {
      const query = String(this.search || '').trim().toLowerCase()

      return this.orders.filter(order => {
        const status = String(order.status || 'new').toLowerCase()

        const statusMatch =
          this.activeStatus === 'all' ||
          status === this.activeStatus

        const categories = this.orderCategories(order)

        const categoryMatch =
          this.activeCategory === 'all' ||
          categories.some(
            category => this.slug(category) === this.activeCategory
          )

        const searchMatch =
          !query ||
          [
            order.id,
            order.order_number,
            order.customer_name,
            order.email,
            order.phone,
            order.shipping_city,
            order.courier_name,
            order.tracking_number,
            ...categories
          ].some(value =>
            String(value || '')
              .toLowerCase()
              .includes(query)
          )

        return statusMatch && categoryMatch && searchMatch
      })
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

        this.orders = Array.isArray(data) ? data : []
      } catch (error) {
        console.error('TeamStore orders fetch error:', error)
        this.orders = []
      } finally {
        this.loading = false
      }
    },

    async openOrder(order) {
      this.selectedOrder = order
      document.body.style.overflow = 'hidden'

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
          new CustomEvent('teamstore-orders-read-updated')
        )
      } catch (error) {
        console.error('TeamStore mark-read error:', error)
      }
    },

    closeOrder() {
      this.selectedOrder = null
      document.body.style.overflow = ''
    },

    normalizedItems(order) {
      const items = order?.items

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
    },

    itemName(item) {
      return (
        item?.name ||
        item?.product_name ||
        item?.title ||
        item?.model_name ||
        'TeamStore Product'
      )
    },

    itemCategory(item) {
      return (
        item?.category_name ||
        item?.category ||
        item?.product_category ||
        item?.store_name ||
        item?.team_name ||
        item?.collection_name ||
        'Other'
      )
    },

    orderCategories(order) {
      const categories = this.normalizedItems(order)
        .map(item => this.itemCategory(item))
        .filter(Boolean)

      return [...new Set(categories)]
    },

    itemImage(item) {
      const raw =
        item?.image ||
        item?.thumbnail ||
        item?.image_url ||
        item?.thumbnail_url ||
        item?.product_image ||
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

    statusCount(status) {
      if (status === 'all') {
        return this.orders.length
      }

      return this.orders.filter(
        order =>
          String(order.status || 'new').toLowerCase() === status
      ).length
    },

    slug(value) {
      return String(value || 'other')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
    },

    categoryIcon(name) {
      const value = String(name || '').toLowerCase()

      if (value.includes('shirt') || value.includes('jersey')) {
        return 'fa-solid fa-shirt'
      }

      if (value.includes('bag')) {
        return 'fa-solid fa-bag-shopping'
      }

      if (value.includes('cap') || value.includes('hat')) {
        return 'fa-solid fa-hat-cowboy'
      }

      if (value.includes('jacket') || value.includes('outerwear')) {
        return 'fa-solid fa-vest'
      }

      if (value.includes('football')) {
        return 'fa-solid fa-football'
      }

      return 'fa-solid fa-box'
    },

    statusClass(status) {
      return `status-${String(status || 'new').toLowerCase()}`
    },

    formatLabel(value) {
      return String(value || '')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
    },

    formatDate(value) {
      if (!value) {
        return '—'
      }

      const date = new Date(value)

      if (Number.isNaN(date.getTime())) {
        return value
      }

      return date.toLocaleDateString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      })
    },

    formatDateTime(value) {
      if (!value) {
        return '—'
      }

      const date = new Date(value)

      if (Number.isNaN(date.getTime())) {
        return value
      }

      return date.toLocaleString('en-US', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
  },

  beforeUnmount() {
    document.body.style.overflow = ''
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.teamstore-page {
  min-height: 100vh;
  background: #f5f6f8;
  color: #111827;
}

.page-header {
  min-height: 106px;
  padding: 22px 32px;
  border-bottom: 1px solid #e5e7eb;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.header-left,
.header-actions {
  display: flex;
  align-items: center;
  gap: 14px;
}

.back-button {
  width: 42px;
  height: 42px;
  border: 1px solid #d9dde5;
  border-radius: 12px;
  background: #ffffff;
  color: #111827;
  cursor: pointer;
}

.eyebrow {
  margin: 0 0 3px;
  color: #2563eb;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: .12em;
  text-transform: uppercase;
}

.page-header h1 {
  margin: 0;
  font-size: 24px;
}

.page-header span {
  color: #6b7280;
  font-size: 12px;
}

.search-box {
  width: 330px;
  height: 42px;
  padding: 0 13px;
  border: 1px solid #d9dde5;
  border-radius: 12px;
  background: #ffffff;
  display: flex;
  align-items: center;
  gap: 9px;
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
}

.new-count {
  padding: 8px 12px;
  border-radius: 999px;
  background: #111827;
  color: #ffffff !important;
  font-weight: 900;
}

.page-content {
  padding: 24px 32px 36px;
}

.category-section,
.orders-panel {
  border: 1px solid #e2e5ea;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 8px 30px rgba(15, 23, 42, .04);
}

.category-section {
  padding: 20px;
}

.section-heading,
.panel-header,
.items-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.section-heading h2,
.panel-header h2,
.items-heading h3 {
  margin: 0;
  font-size: 17px;
}

.section-heading p,
.panel-header p,
.items-heading p {
  margin: 4px 0 0;
  color: #6b7280;
  font-size: 11px;
}

.refresh-button {
  height: 36px;
  padding: 0 13px;
  border: 1px solid #d9dde5;
  border-radius: 10px;
  background: #ffffff;
  color: #111827;
  font-weight: 800;
  cursor: pointer;
}

.category-grid {
  margin-top: 18px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(195px, 1fr));
  gap: 12px;
}

.category-card {
  min-height: 82px;
  padding: 12px;
  border: 1px solid #e2e5ea;
  border-radius: 14px;
  background: #ffffff;
  color: #111827;
  text-align: left;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 11px;
  transition: .18s ease;
}

.category-card:hover {
  transform: translateY(-2px);
  border-color: #9ca3af;
}

.category-card.active {
  border-color: #111827;
  background: #111827;
  color: #ffffff;
}

.category-icon {
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #f8fafc;
  color: #111827;
  display: grid;
  place-items: center;
  overflow: hidden;
}

.category-icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.category-info {
  min-width: 0;
  flex: 1;
}

.category-info strong,
.category-info small {
  display: block;
}

.category-info strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 13px;
}

.category-info small {
  margin-top: 4px;
  color: #6b7280;
  font-size: 10px;
}

.category-card.active .category-info small {
  color: #d1d5db;
}

.card-arrow {
  color: #9ca3af;
  font-size: 11px;
}

.filters-row {
  margin: 16px 0;
}

.status-tabs {
  display: flex;
  gap: 8px;
  overflow-x: auto;
}

.status-tabs button {
  min-width: 120px;
  height: 42px;
  padding: 0 13px;
  border: 1px solid #e2e5ea;
  border-radius: 11px;
  background: #ffffff;
  color: #4b5563;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 7px;
}

.status-tabs button.active {
  border-color: #111827;
  color: #111827;
  box-shadow: 0 0 0 2px rgba(17, 24, 39, .06);
}

.status-tabs strong {
  margin-left: auto;
  color: #111827;
  font-size: 11px;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #9ca3af;
}

.dot-new {
  background: #8b5cf6;
}

.dot-confirmed {
  background: #3b82f6;
}

.dot-production {
  background: #f59e0b;
}

.dot-shipped {
  background: #0ea5e9;
}

.dot-delivered {
  background: #10b981;
}

.dot-cancelled {
  background: #ef4444;
}

.panel-header {
  padding: 17px 20px;
  border-bottom: 1px solid #e5e7eb;
}

.table-wrap {
  overflow-x: auto;
}

.orders-table {
  width: 100%;
  min-width: 1320px;
  border-collapse: collapse;
}

.orders-table th,
.orders-table td {
  padding: 13px 14px;
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
  vertical-align: middle;
}

.orders-table th {
  background: #f8fafc;
  color: #6b7280;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: .05em;
  text-transform: uppercase;
}

.orders-table td {
  font-size: 11px;
}

.orders-table tbody tr:hover {
  background: #fafafa;
}

.orders-table tbody tr.unread {
  background: #f2f7ff;
}

.order-number-cell {
  display: flex;
  align-items: center;
  gap: 9px;
}

.unread-dot {
  width: 8px;
  height: 8px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #2563eb;
}

.order-number-cell strong,
.customer-cell strong,
.shipping-cell strong,
.tracking-cell strong {
  display: block;
  font-size: 11px;
}

.order-number-cell small,
.customer-cell small,
.shipping-cell small,
.tracking-cell small {
  display: block;
  margin-top: 3px;
  color: #9ca3af;
  font-size: 9px;
}

.category-tags {
  display: flex;
  align-items: center;
  gap: 5px;
  flex-wrap: wrap;
}

.category-tags span,
.category-tags em {
  padding: 4px 7px;
  border-radius: 999px;
  background: #eef2ff;
  color: #3730a3;
  font-size: 9px;
  font-style: normal;
  font-weight: 800;
}

.item-thumbnails {
  display: flex;
  align-items: center;
}

.item-thumb,
.more-items {
  width: 36px;
  height: 36px;
  margin-right: -6px;
  border: 2px solid #ffffff;
  border-radius: 9px;
  background: #f3f4f6;
  color: #6b7280;
  display: grid;
  place-items: center;
  overflow: hidden;
}

.item-thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.more-items {
  border-radius: 50%;
  background: #111827;
  color: #ffffff;
  font-size: 9px;
  font-weight: 900;
}

.status-badge {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 9px;
  font-weight: 900;
}

.status-badge.large {
  padding: 8px 12px;
  font-size: 10px;
}

.status-new {
  background: #ede9fe;
  color: #6d28d9;
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
  background: #e0f2fe;
  color: #0369a1;
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

.plain-text,
.date-cell {
  color: #4b5563;
}

.view-button {
  height: 34px;
  padding: 0 12px;
  border: 1px solid #d9dde5;
  border-radius: 9px;
  background: #ffffff;
  color: #111827;
  font-weight: 800;
  cursor: pointer;
}

.view-button:hover {
  background: #111827;
  color: #ffffff;
}

.empty-state {
  min-height: 300px;
  padding: 30px;
  color: #6b7280;
  text-align: center;
  display: grid;
  place-items: center;
  align-content: center;
  gap: 8px;
}

.empty-state > i {
  font-size: 28px;
}

.empty-state h3,
.empty-state p {
  margin: 0;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;
  padding: 22px;
  background: rgba(15, 23, 42, .72);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.order-modal {
  width: min(1180px, 100%);
  max-height: calc(100vh - 44px);
  border-radius: 20px;
  background: #f8fafc;
  overflow: hidden;
  box-shadow: 0 30px 90px rgba(0, 0, 0, .35);
}

.modal-header {
  padding: 19px 22px;
  background: #111827;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.modal-header p {
  margin: 0;
  color: #9ca3af;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: .12em;
  text-transform: uppercase;
}

.modal-header h2 {
  margin: 4px 0;
  font-size: 22px;
}

.modal-header span {
  color: #d1d5db;
  font-size: 11px;
}

.modal-header-actions {
  display: flex;
  align-items: center;
  gap: 9px;
}

.modal-close {
  width: 38px;
  height: 38px;
  border: 1px solid rgba(255, 255, 255, .18);
  border-radius: 10px;
  background: rgba(255, 255, 255, .08);
  color: #ffffff;
  cursor: pointer;
}

.modal-body {
  max-height: calc(100vh - 140px);
  padding: 18px;
  overflow-y: auto;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 13px;
}

.detail-card,
.items-section,
.notes-card {
  border: 1px solid #e2e5ea;
  border-radius: 15px;
  background: #ffffff;
}

.detail-card {
  padding: 16px;
}

.detail-card h3,
.notes-card h3 {
  margin: 0 0 14px;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 7px;
}

.detail-card dl {
  margin: 0;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.detail-card .full-row {
  grid-column: 1 / -1;
}

.detail-card dt {
  margin-bottom: 4px;
  color: #6b7280;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
}

.detail-card dd {
  margin: 0;
  overflow-wrap: anywhere;
  color: #111827;
  font-size: 11px;
  font-weight: 700;
}

.items-section {
  margin-top: 13px;
  padding: 16px;
}

.modal-items-grid {
  margin-top: 14px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 11px;
}

.modal-item-card {
  min-width: 0;
  padding: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 11px;
}

.modal-item-image {
  width: 70px;
  height: 70px;
  flex-shrink: 0;
  border-radius: 11px;
  background: #f3f4f6;
  color: #9ca3af;
  display: grid;
  place-items: center;
  overflow: hidden;
}

.modal-item-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.modal-item-info {
  min-width: 0;
}

.modal-item-info > span {
  color: #2563eb;
  font-size: 8px;
  font-weight: 900;
  text-transform: uppercase;
}

.modal-item-info h4 {
  margin: 4px 0 7px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 12px;
}

.item-meta {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.item-meta em {
  color: #6b7280;
  font-size: 9px;
  font-style: normal;
}

.item-meta strong {
  color: #111827;
}

.notes-card {
  margin-top: 13px;
  padding: 16px;
}

.notes-card p {
  margin: 0;
  color: #4b5563;
  font-size: 11px;
  line-height: 1.7;
  white-space: pre-wrap;
}

.no-items {
  margin-top: 12px;
  padding: 24px;
  border-radius: 12px;
  background: #f8fafc;
  color: #6b7280;
  text-align: center;
  font-size: 11px;
}

@media (max-width: 900px) {
  .page-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .header-actions,
  .search-box {
    width: 100%;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .page-header,
  .page-content {
    padding-left: 15px;
    padding-right: 15px;
  }

  .header-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .category-grid {
    grid-template-columns: 1fr;
  }

  .modal-overlay {
    padding: 0;
  }

  .order-modal {
    max-height: 100vh;
    border-radius: 0;
  }

  .modal-header {
    align-items: flex-start;
  }

  .modal-header-actions {
    align-items: flex-end;
    flex-direction: column-reverse;
  }

  .detail-card dl {
    grid-template-columns: 1fr;
  }

  .detail-card .full-row {
    grid-column: auto;
  }
}
</style>
