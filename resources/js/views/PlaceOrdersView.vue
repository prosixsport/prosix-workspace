<template>
  <AppLayout>
    <div class="place-orders-page">
      <PageHeader
        title="Place Orders"
        subtitle="Manage customer orders and production files."
        :user="currentUser"
        :photo="currentUser?.profile_photo_url"
        @profile="openProfile"
      />


    <section class="filters-bar">
      <div class="tabs">
        <button
          v-for="tab in dynamicStatusTabs"
          :key="tab.key"
          type="button"
          :class="{ active: activeStatus === tab.key }"
          @click="activeStatus = tab.key"
        >
          {{ tab.label }}
        </button>
      </div>

      <span
        v-if="unreadCount"
        class="new-badge"
      >
        {{ unreadCount }} new
      </span>
    </section>

    <main class="orders-card">
      <div
        v-if="loading"
        class="table-state"
      >
        <i class="fa-solid fa-spinner fa-spin"></i>
        Loading orders...
      </div>

      <div
        v-else-if="filteredOrders.length === 0"
        class="table-state"
      >
        <i class="fa-solid fa-inbox"></i>
        No place orders found
      </div>

      <div
        v-else
        class="table-wrap"
      >
        <table class="orders-table">
          <thead>
            <tr>
              <th class="check-col">
                <input
                  type="checkbox"
                  :checked="allVisibleSelected"
                  @change="toggleSelectAll"
                />
              </th>

              <th>#</th>
              <th>Thumbnail</th>
              <th>Customer</th>
              <th>Email</th>
              <th>Order #</th>
              <th>Status</th>
              <th>Remark</th>
              <th>Files</th>
              <th>Action</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="order in filteredOrders"
              :key="order.id"
              :class="{ unread: !order.is_read }"
            >
              <td class="check-col">
                <input
                  type="checkbox"
                  :checked="isSelected(order.id)"
                  @change="toggleOrder(order.id)"
                />
              </td>

              <td class="id-cell">
                {{ order.id }}
              </td>

              <td>
                <div class="table-thumb">
                  <img
                    v-if="orderThumbnail(order)"
                    :src="orderThumbnail(order)"
                    :alt="orderDisplayName(order)"
                    @error="hideBrokenImage"
                  />

                  <span v-else>
                    <i class="fa-regular fa-image"></i>
                    No Image
                  </span>
                </div>
              </td>

              <td>
                <div class="customer-cell">
                  <strong>
                    {{ order.full_name || '—' }}
                  </strong>

                  <small>
                    {{ orderDisplayName(order) }}
                  </small>
                </div>
              </td>

              <td>
                <span class="email-cell">
                  {{ order.email || '—' }}
                </span>
              </td>

              <!-- Phone / user number intentionally removed -->

              <td>
                <strong class="order-no">
                  {{
                    order.order_number ||
                    `#${order.id}`
                  }}
                </strong>
              </td>

              <td class="status-cell">
                <div class="status-select-shell">
                  <span
                    class="status-select-dot"
                    :style="{ backgroundColor: statusColor(order.status) }"
                  ></span>

                  <select
                    class="status-select-pro"
                    :value="normalizeStatus(order.status)"
                    :disabled="!canEditStatus || savingOrderId === order.id"
                    :style="statusSelectStyle(order.status)"
                    @change="handleStatusSelect(order, $event)"
                  >
                    <option
                      v-for="status in statusDefinitions"
                      :key="status.id || status.value"
                      :value="status.value"
                    >
                      {{ status.name }}
                    </option>

                    <option
                      v-if="canEditStatus"
                      value="__customize__"
                    >
                      ⚙ Customize Statuses...
                    </option>
                  </select>
                </div>

                <small
                  v-if="savingOrderId === order.id"
                  class="saving-text"
                >
                  Saving...
                </small>
              </td>

              <td class="remark-cell">
                <div v-if="canEditRemark" class="remark-field-pro">
                  <i class="fa-regular fa-note-sticky"></i>

                  <input
                    v-model="order._remark"
                    type="text"
                    placeholder="Write internal remark..."
                    :disabled="savingOrderId === order.id"
                    @input="queueRemarkSave(order)"
                    @blur="saveRemark(order)"
                    @keyup.enter="$event.target.blur()"
                  />

                  <span
                    v-if="order._remarkSaved"
                    class="remark-check"
                    title="Saved"
                  >
                    <i class="fa-solid fa-check"></i>
                  </span>
                </div>

                <div v-else class="remark-readonly-pro">
                  <i class="fa-regular fa-note-sticky"></i>
                  <span>{{ order.remark || 'No remark' }}</span>
                </div>
              </td>

              <td>
                <span
                  v-if="fileCount(order)"
                  class="file-count"
                >
                  {{ fileCount(order) }}
                  {{
                    fileCount(order) === 1
                      ? 'file'
                      : 'files'
                  }}
                </span>

                <span
                  v-else
                  class="no-files"
                >
                  No files
                </span>
              </td>

              <td>
                <button
                  type="button"
                  class="view-btn"
                  @click="selectOrder(order)"
                >
                  <i class="fa-regular fa-eye"></i>
                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>

    <!-- VIEW MODAL -->
    <div
      v-if="selectedOrder"
      class="modal-overlay"
      @click.self="closeOrder"
    >
      <section class="order-modal">
        <header class="view-black-header">
          <div class="view-brand">
            <span class="prosix-mark">P</span>

            <div>
              <strong>PROSIX SPORTS</strong>
              <small>PLACE ORDER DETAILS</small>
            </div>
          </div>

          <div class="view-header-actions">
            <span
              class="header-status"
              :style="statusPillStyle(selectedOrder.status)"
            >
              {{ statusLabel(selectedOrder.status) }}
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

        <div class="thank-you-strip">
          <h4>THANKS FOR CHOOSING US!</h4>
          <p>
            WE REALLY APPRECIATE &amp; VALUE YOUR BUSINESS
          </p>
        </div>

        <div class="order-view-body">
          <section class="order-hero-card">
            <div class="hero-thumbnail">
              <img
                v-if="orderThumbnail(selectedOrder)"
                :src="orderThumbnail(selectedOrder)"
                :alt="orderDisplayName(selectedOrder)"
                @error="hideBrokenImage"
              />

              <div
                v-else
                class="hero-thumbnail-empty"
              >
                <i class="fa-regular fa-image"></i>
                <span>No Image</span>
              </div>
            </div>

            <div class="hero-order-info">
              <span class="small-label">
                Order Name
              </span>

              <h1>
                {{ orderDisplayName(selectedOrder) }}
              </h1>

              <p>
                Order #
                {{
                  selectedOrder.order_number ||
                  selectedOrder.id
                }}
              </p>
            </div>

            <div class="hero-side-info">
              <span>Submitted</span>
              <strong>
                {{ formatDate(selectedOrder.created_at) }}
              </strong>
            </div>
          </section>

          <section class="order-details-grid">
            <article class="detail-field">
              <span>Full Name</span>
              <strong>
                {{ selectedOrder.full_name || '—' }}
              </strong>
            </article>

            <article class="detail-field">
              <span>Email</span>
              <strong>
                {{ selectedOrder.email || '—' }}
              </strong>
            </article>

            <!-- Phone field intentionally removed -->

            <article class="detail-field">
              <span>Order Place Date</span>
              <strong>
                {{ dateValue(selectedOrder.order_date) }}
              </strong>
            </article>

            <article class="detail-field">
              <span>Delivery Date</span>
              <strong>
                {{ dateValue(selectedOrder.delivery_date) }}
              </strong>
            </article>

            <article class="detail-field">
              <span>Sales Rep</span>
              <strong>
                {{ selectedOrder.sales_rep || '—' }}
              </strong>
            </article>

            <article class="detail-field">
              <span>Order #</span>
              <strong>
                {{
                  selectedOrder.order_number ||
                  `#${selectedOrder.id}`
                }}
              </strong>
            </article>
          </section>

          <section class="team-colors-box">
            <span>Team Colors</span>
            <strong>
              {{ selectedOrder.team_colors || '—' }}
            </strong>
          </section>

          <section class="placeorder-controls-card">
            <div class="placeorder-control">
              <label>Status</label>

              <div class="modal-status-select-shell">
                <span
                  class="status-select-dot"
                  :style="{ backgroundColor: statusColor(selectedOrder.status) }"
                ></span>

                <select
                  class="status-select-pro modal-status-select"
                  :value="normalizeStatus(selectedOrder.status)"
                  :disabled="!canEditStatus || savingOrderId === selectedOrder.id"
                  :style="statusSelectStyle(selectedOrder.status)"
                  @change="handleStatusSelect(selectedOrder, $event)"
                >
                  <option
                    v-for="status in statusDefinitions"
                    :key="status.id || status.value"
                    :value="status.value"
                  >
                    {{ status.name }}
                  </option>

                  <option
                    v-if="canEditStatus"
                    value="__customize__"
                  >
                    ⚙ Customize Statuses...
                  </option>
                </select>
              </div>
            </div>

            <div class="placeorder-control remark-control">
              <label>Remark</label>

              <div v-if="canEditRemark" class="modal-remark-wrap">
                <input
                  v-model="selectedOrder._remark"
                  type="text"
                  placeholder="Write internal remark..."
                  :disabled="savingOrderId === selectedOrder.id"
                  @input="queueRemarkSave(selectedOrder)"
                  @blur="saveRemark(selectedOrder)"
                  @keyup.enter="$event.target.blur()"
                />

                <small>
                  {{
                    selectedOrder._remarkSaved
                      ? 'Saved'
                      : 'Auto-save'
                  }}
                </small>
              </div>

              <div v-else class="modal-remark-readonly">
                {{ selectedOrder.remark || 'No remark added.' }}
              </div>
            </div>
          </section>

          <div class="section-divider"></div>

          <section class="file-notes-grid">
            <article
              v-for="group in fileGroups"
              :key="group.key"
              class="document-card"
            >
              <header class="document-card-title">
                <span>
                  <i :class="group.icon"></i>
                  {{ group.title }}
                </span>

                <em>{{ group.files.length }}</em>
              </header>

              <div
                v-if="group.files.length"
                class="document-list"
              >
                <div
                  v-for="(file, index) in group.files"
                  :key="index"
                  class="document-file"
                >
                  <a
                    :href="fileUrl(file, group.folder)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="document-preview"
                  >
                    <img
                      v-if="isImage(file)"
                      :src="fileUrl(file, group.folder)"
                      :alt="fileName(file)"
                      @error="hideBrokenImage"
                    />

                    <span
                      v-else
                      class="document-file-icon"
                    >
                      <i class="fa-solid fa-file"></i>
                      <strong>
                        {{ extension(file) }}
                      </strong>
                    </span>
                  </a>

                  <div class="document-file-info">
                    <strong>
                      {{ fileName(file) }}
                    </strong>

                    <div>
                      <a
                        :href="fileUrl(file, group.folder)"
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        View
                      </a>

                      <a
                        :href="fileUrl(file, group.folder)"
                        download
                      >
                        Download
                      </a>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-else
                class="document-empty"
              >
                <i class="fa-regular fa-folder-open"></i>
                <span>No files uploaded</span>
              </div>
            </article>

            <article class="document-card notes-document-card">
              <header class="document-card-title">
                <span>
                  <i class="fa-solid fa-note-sticky"></i>
                  Notes
                </span>
              </header>

              <div
                v-if="selectedOrder.notes"
                class="notes-box"
                v-html="selectedOrder.notes"
              ></div>

              <div
                v-else
                class="document-empty"
              >
                <i class="fa-regular fa-note-sticky"></i>
                <span>No notes added</span>
              </div>
            </article>
          </section>

          <footer class="order-actions-footer">
            <div class="footer-order-id">
              <span>Selected Order</span>
              <strong>
                {{
                  selectedOrder.order_number ||
                  `#${selectedOrder.id}`
                }}
              </strong>
            </div>

            <div class="footer-buttons">
              <button
                type="button"
                class="download-pdf-btn"
                @click="downloadPdf"
              >
                <i class="fa-regular fa-file-pdf"></i>
                Download PDF
              </button>

              <button
                type="button"
                class="print-order-btn"
                @click="printOrder"
              >
                <i class="fa-solid fa-print"></i>
                Print
              </button>
            </div>
          </footer>
        </div>
      </section>
    </div>

    <!-- STATUS MANAGER -->
    <div
      v-if="statusManagerOpen"
      class="status-manager-overlay"
      @click.self="closeStatusManager"
    >
      <section class="status-manager-modal">
        <header class="status-manager-head">
          <div>
            <span>PLACE ORDER SETTINGS</span>
            <h2>Manage Statuses</h2>
            <p>
              Add a custom status, edit its name or change its color.
            </p>
          </div>

          <button type="button" @click="closeStatusManager">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </header>

        <div class="status-manager-body">
          <div class="new-status-row">
            <div class="status-form-field">
              <label>New Status</label>
              <input
                v-model="newStatusName"
                type="text"
                placeholder="e.g. Artwork Pending"
                @keyup.enter="createStatusDefinition"
              />
            </div>

            <div class="status-form-field color-field">
              <label>Color</label>
              <input
                v-model="newStatusColor"
                type="color"
              />
            </div>

            <button
              type="button"
              class="create-status-btn"
              :disabled="!String(newStatusName || '').trim()"
              @click="createStatusDefinition"
            >
              <i class="fa-solid fa-plus"></i>
              Add
            </button>
          </div>

          <div class="status-definition-list">
            <article
              v-for="status in statusDefinitions"
              :key="status.id || status.value"
              class="status-definition-row"
            >
              <span
                class="status-color-preview"
                :style="{ backgroundColor: status.color }"
              ></span>

              <div class="status-edit-name">
                <label>Name</label>
                <input
                  v-model="status.name"
                  type="text"
                  :disabled="!status.custom"
                />
                <small>{{ status.value }}</small>
              </div>

              <div class="status-edit-color">
                <label>Color</label>
                <input
                  v-model="status.color"
                  type="color"
                />
              </div>

              <div class="status-definition-preview">
                <label>Preview</label>
                <span :style="statusPillStyleFromDefinition(status)">
                  <i :style="{ backgroundColor: status.color }"></i>
                  {{ status.name }}
                </span>
              </div>

              <div class="status-definition-actions">
                <button
                  type="button"
                  class="status-save-btn"
                  @click="saveStatusDefinition(status)"
                >
                  Save
                </button>

                <button
                  v-if="status.custom"
                  type="button"
                  class="status-delete-btn"
                  @click="deleteStatusDefinition(status)"
                >
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </article>
          </div>
        </div>
      </section>
    </div>

    </div>
  </AppLayout>
</template>

<script>
import axios from 'axios'
import AppLayout from '../layouts/AppLayout.vue'
import PageHeader from '../layouts/PageHeader.vue'

export default {
  name: 'PlaceOrdersView',

  components: {
    AppLayout,
    PageHeader
  },

  data() {
    return {
      loading: false,
      orders: [],
      selectedOrder: null,
      selectedIds: [],
      search: '',
      activeStatus: 'all',
      statusDefinitions: [],
      statusManagerOpen: false,
      statusManagerTargetOrder: null,
      newStatusName: '',
      newStatusColor: '#7c3aed',
      savingOrderId: null,
      remarkTimers: {},
      syncTimer: null,
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
    currentUser() {
      try {
        return JSON.parse(localStorage.getItem('user')) || {}
      } catch {
        return {}
      }
    },

    userRole() {
      return String(this.currentUser?.role || '').toLowerCase()
    },

    canEditRemark() {
      return ['super_admin', 'admin'].includes(this.userRole)
    },

    canEditStatus() {
      return ['super_admin', 'admin', 'member', 'designer'].includes(this.userRole)
    },

    allVisibleSelected() {
      return (
        this.filteredOrders.length > 0 &&
        this.filteredOrders.every(order =>
          this.selectedIds.includes(Number(order.id))
        )
      )
    },

    filteredOrders() {
      const query = String(this.search || '').trim().toLowerCase()

      return this.orders.filter(order => {
        const status = String(order.status || 'pending').toLowerCase()
        const statusMatch = this.activeStatus === 'all' || status === this.activeStatus
        const searchMatch = !query || [
          order.order_number,
          order.full_name,
          order.email,
          order.phone,
          this.orderDisplayName(order)
        ].some(value => String(value || '').toLowerCase().includes(query))

        return statusMatch && searchMatch
      })
    },

    unreadCount() {
      return this.orders.filter(order => !order.is_read).length
    },

    dynamicStatusTabs() {
      const tabs = [
        { key: 'all', label: 'All' },
        ...this.statusDefinitions.map(status => ({
          key: status.value,
          label: status.name
        }))
      ]

      const known = new Set(tabs.map(tab => tab.key))

      this.orders.forEach(order => {
        const value = this.normalizeStatus(order.status)

        if (value && !known.has(value)) {
          tabs.push({
            key: value,
            label: this.capitalize(order.status)
          })

          known.add(value)
        }
      })

      return tabs
    },

    fileGroups() {
      return [
        {
          key: 'mockup',
          title: 'Final Mockup Files',
          folder: 'mockup',
          icon: 'fa-solid fa-paperclip',
          files: this.selectedOrder?.mockup_files || []
        },
        {
          key: 'roster',
          title: 'Team Roster Files',
          folder: 'roster',
          icon: 'fa-solid fa-users',
          files: this.selectedOrder?.roster_files || []
        },
        {
          key: 'quote',
          title: 'Quote / Invoice',
          folder: 'quote',
          icon: 'fa-regular fa-file-lines',
          files: this.selectedOrder?.quote_files || []
        }
      ]
    }
  },

  async mounted() {
    document.addEventListener('click', this.closeStatusMenus)

    await Promise.all([
      this.fetchStatusDefinitions(),
      this.fetchOrders()
    ])

    this.syncTimer = window.setInterval(() => {
      this.fetchOrders(true)
      this.fetchStatusDefinitions(true)
    }, 5000)
  },

  beforeUnmount() {
    document.removeEventListener('click', this.closeStatusMenus)

    if (this.syncTimer) {
      window.clearInterval(this.syncTimer)
    }

    Object.values(this.remarkTimers).forEach(timer => {
      window.clearTimeout(timer)
    })

    document.body.style.overflow = ''
  },

  methods: {
    openProfile() {
      if (this.$route?.path === '/profile') return
      this.$router.push('/profile')
    },

    headers() {
      return {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        Accept: 'application/json'
      }
    },

    prepareOrder(order) {
      return {
        ...order,
        _remark: order?.remark ?? '',
        _remarkSaved: false,
        _statusMenu: false,
        _customOpen: false,
        _customStatus: '',
        _customColor: '#7c3aed'
      }
    },

    normalizeStatus(value) {
      return String(value || 'pending')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '-')
    },

    async fetchStatusDefinitions(silent = false) {
      try {
        const response = await axios.get(
          '/api/place-orders/statuses',
          { headers: this.headers() }
        )

        const data = response.data?.data ?? []

        if (Array.isArray(data)) {
          this.statusDefinitions = data.map(status => ({
            ...status,
            value: String(status.value || '').toLowerCase(),
            name: status.name || this.capitalize(status.value),
            color: status.color || '#667085',
            custom: Boolean(status.custom)
          }))
        }
      } catch (error) {
        if (!silent) {
          console.error(
            'Place order statuses fetch error:',
            error
          )
        }
      }
    },

    statusDefinition(value) {
      const normalized = this.normalizeStatus(value)

      return this.statusDefinitions.find(
        status => status.value === normalized
      ) || null
    },

    statusLabel(value) {
      return (
        this.statusDefinition(value)?.name ||
        this.capitalize(value || 'pending')
      )
    },

    statusColor(value) {
      return (
        this.statusDefinition(value)?.color ||
        '#667085'
      )
    },

    hexToRgba(hex, alpha = 0.12) {
      let clean = String(hex || '#667085').replace('#', '')

      if (clean.length === 3) {
        clean = clean.split('').map(char => char + char).join('')
      }

      const num = parseInt(clean.slice(0, 6), 16)
      const r = (num >> 16) & 255
      const g = (num >> 8) & 255
      const b = num & 255

      return `rgba(${r}, ${g}, ${b}, ${alpha})`
    },

    statusPillStyle(value) {
      const color = this.statusColor(value)

      return {
        color,
        borderColor: this.hexToRgba(color, 0.28),
        backgroundColor: this.hexToRgba(color, 0.12)
      }
    },

    statusSelectStyle(value) {
      const color = this.statusColor(value)

      return {
        color,
        borderColor: this.hexToRgba(color, 0.35),
        backgroundColor: this.hexToRgba(color, 0.08)
      }
    },

    statusPillStyleFromDefinition(status) {
      const color = status?.color || '#667085'

      return {
        color,
        borderColor: this.hexToRgba(color, 0.28),
        backgroundColor: this.hexToRgba(color, 0.12)
      }
    },

    toggleStatusMenu(order) {
      if (!this.canEditStatus) return

      const next = !order._statusMenu

      this.orders.forEach(item => {
        item._statusMenu = false
      })

      if (this.selectedOrder && this.selectedOrder !== order) {
        this.selectedOrder._statusMenu = false
      }

      order._statusMenu = next
    },

    closeStatusMenus() {
      this.orders.forEach(order => {
        order._statusMenu = false
      })

      if (this.selectedOrder) {
        this.selectedOrder._statusMenu = false
      }
    },

    async handleStatusSelect(order, event) {
      const value = String(event?.target?.value || '')

      if (value === '__customize__') {
        // Keep the actual order status selected in the field.
        if (event?.target) {
          event.target.value = this.normalizeStatus(order.status)
        }

        this.openStatusManager(order)
        return
      }

      await this.changeOrderStatus(order, value)
    },

    async changeOrderStatus(order, value) {
      if (!this.canEditStatus || !order?.id) return

      this.savingOrderId = Number(order.id)

      try {
        const response = await axios.put(
          `/api/place-orders/${order.id}`,
          { status: value },
          { headers: this.headers() }
        )

        const updated = response.data?.data || {}

        order.status = updated.status ?? value
        order.remark = updated.remark ?? order.remark
        order._remark = order.remark ?? ''
        order._statusMenu = false

        await this.fetchStatusDefinitions(true)
      } catch (error) {
        console.error('Place order status update error:', error)
        alert(
          error?.response?.data?.message ||
          'Status update failed.'
        )
      } finally {
        this.savingOrderId = null
      }
    },

    queueRemarkSave(order) {
      if (!this.canEditRemark) return

      const id = Number(order.id)
      order._remarkSaved = false

      if (this.remarkTimers[id]) {
        clearTimeout(this.remarkTimers[id])
      }

      this.remarkTimers[id] = setTimeout(() => {
        this.saveRemark(order)
      }, 800)
    },

    async saveRemark(order) {
      if (!this.canEditRemark || !order?.id) return

      const id = Number(order.id)

      if (this.remarkTimers[id]) {
        clearTimeout(this.remarkTimers[id])
        delete this.remarkTimers[id]
      }

      const value = String(order._remark ?? '')

      if (value === String(order.remark ?? '')) {
        return
      }

      this.savingOrderId = id

      try {
        const response = await axios.put(
          `/api/place-orders/${order.id}`,
          { remark: value },
          { headers: this.headers() }
        )

        const updated = response.data?.data || {}

        order.remark = updated.remark ?? value
        order._remark = order.remark
        order._remarkSaved = true

        setTimeout(() => {
          order._remarkSaved = false
        }, 1400)
      } catch (error) {
        order._remark = order.remark ?? ''

        console.error('Place order remark update error:', error)

        alert(
          error?.response?.data?.message ||
          'Remark update failed.'
        )
      } finally {
        this.savingOrderId = null
      }
    },

    async saveInlineCustomStatus(order) {
      if (!this.canEditStatus || !order?.id) return

      const name = String(order._customStatus || '').trim()

      if (!name) return

      try {
        const response = await axios.post(
          '/api/place-orders/statuses',
          {
            name,
            color: order._customColor || '#7c3aed'
          },
          { headers: this.headers() }
        )

        const created = response.data?.data

        await this.fetchStatusDefinitions(true)

        if (created?.value) {
          await this.changeOrderStatus(
            order,
            created.value
          )
        }

        order._customStatus = ''
        order._customColor = '#7c3aed'
        order._customOpen = false
        order._statusMenu = false
      } catch (error) {
        alert(
          error?.response?.data?.message ||
          'Could not create custom status.'
        )
      }
    },

    openStatusManager(order = null) {
      if (!this.canEditStatus) return

      this.statusManagerTargetOrder = order || null

      if (order) {
        order._statusMenu = false
      }

      this.statusManagerOpen = true
    },

    closeStatusManager() {
      this.statusManagerOpen = false
      this.statusManagerTargetOrder = null
      this.newStatusName = ''
      this.newStatusColor = '#7c3aed'
    },

    async createStatusDefinition() {
      if (!this.canEditStatus) return

      const name = String(this.newStatusName || '').trim()

      if (!name) return

      try {
        const response = await axios.post(
          '/api/place-orders/statuses',
          {
            name,
            color: this.newStatusColor
          },
          { headers: this.headers() }
        )

        const created = response.data?.data

        await this.fetchStatusDefinitions(true)

        this.newStatusName = ''
        this.newStatusColor = '#7c3aed'

        if (
          created?.value &&
          this.statusManagerTargetOrder
        ) {
          await this.changeOrderStatus(
            this.statusManagerTargetOrder,
            created.value
          )
        }
      } catch (error) {
        alert(
          error?.response?.data?.message ||
          'Could not create status.'
        )
      }
    },

    async saveStatusDefinition(status) {
      if (!this.canEditStatus || !status?.id) return

      try {
        await axios.put(
          `/api/place-orders/statuses/${status.id}`,
          {
            name: status.name,
            color: status.color
          },
          { headers: this.headers() }
        )

        await this.fetchStatusDefinitions(true)
      } catch (error) {
        alert(
          error?.response?.data?.message ||
          'Could not update status.'
        )
      }
    },

    async deleteStatusDefinition(status) {
      if (!this.canEditStatus || !status?.id || !status.custom) {
        return
      }

      if (!window.confirm(`Delete "${status.name}" from the status list?`)) {
        return
      }

      try {
        await axios.delete(
          `/api/place-orders/statuses/${status.id}`,
          { headers: this.headers() }
        )

        await this.fetchStatusDefinitions(true)
      } catch (error) {
        alert(
          error?.response?.data?.message ||
          'Could not delete status.'
        )
      }
    },

    async fetchOrders(silent = false) {
      if (!silent) {
        this.loading = true
      }

      try {
        const response = await axios.get('/api/place-orders', {
          headers: this.headers()
        })

        const data = response.data?.data ?? response.data ?? []
        const incoming = Array.isArray(data) ? data : []

        const currentById = new Map(
          this.orders.map(order => [Number(order.id), order])
        )

        this.orders = incoming.map(raw => {
          const existing = currentById.get(Number(raw.id))
          const prepared = this.prepareOrder(raw)

          if (existing) {
            prepared._statusMenu = existing._statusMenu || false
          }

          return prepared
        })

        if (this.selectedOrder) {
          const refreshed = this.orders.find(
            order => Number(order.id) === Number(this.selectedOrder.id)
          )

          if (refreshed) {
            this.selectedOrder = refreshed
          }
        }
      } catch (error) {
        console.error('Place orders fetch error:', error)
        this.orders = []
      } finally {
        if (!silent) {
          this.loading = false
        }
      }
    },

    async selectOrder(order) {
      if (typeof order._remark === 'undefined') {
        order._remark = order.remark ?? ''
      }

      if (typeof order._statusMenu === 'undefined') {
        order._statusMenu = false
      }

      this.selectedOrder = order
      document.body.style.overflow = 'hidden'

      if (order.is_read) return

      try {
        await axios.post(
          `/api/place-orders/${order.id}/mark-read`,
          {},
          { headers: this.headers() }
        )

        order.is_read = true

        window.dispatchEvent(
          new CustomEvent('place-orders-read-updated')
        )
      } catch (error) {
        console.error(
          'Place order mark-read error:',
          error
        )
      }
    },

    closeOrder() {
      this.selectedOrder = null
      document.body.style.overflow = ''
    },

    isSelected(orderId) {
      return this.selectedIds.includes(
        Number(orderId)
      )
    },

    toggleOrder(orderId) {
      const id = Number(orderId)

      if (this.selectedIds.includes(id)) {
        this.selectedIds =
          this.selectedIds.filter(
            selectedId => selectedId !== id
          )
      } else {
        this.selectedIds = [
          ...this.selectedIds,
          id
        ]
      }
    },

    toggleSelectAll() {
      const visibleIds =
        this.filteredOrders.map(
          order => Number(order.id)
        )

      if (this.allVisibleSelected) {
        this.selectedIds =
          this.selectedIds.filter(
            id => !visibleIds.includes(id)
          )
      } else {
        this.selectedIds = [
          ...new Set([
            ...this.selectedIds,
            ...visibleIds
          ])
        ]
      }
    },

    fileCount(order) {
      return (
        (Array.isArray(order?.mockup_files)
          ? order.mockup_files.length
          : 0) +
        (Array.isArray(order?.roster_files)
          ? order.roster_files.length
          : 0) +
        (Array.isArray(order?.quote_files)
          ? order.quote_files.length
          : 0)
      )
    },

    printSelected() {
      const orders = this.orders.filter(
        order =>
          this.selectedIds.includes(
            Number(order.id)
          )
      )

      if (!orders.length) {
        alert('Select at least one order.')
        return
      }

      const printWindow = window.open(
        '',
        '_blank',
        'width=1200,height=850'
      )

      if (!printWindow) {
        alert(
          'Please allow popups for printing.'
        )
        return
      }

      const rows = orders.map(order => {
        const thumbnail =
          this.orderThumbnail(order)

        return `
          <tr>
            <td>
              ${
                thumbnail
                  ? `
                    <img
                      class="p-thumb"
                      src="${this.escapeHtml(thumbnail)}"
                      alt=""
                    />
                  `
                  : `
                    <div class="p-empty">
                      No Image
                    </div>
                  `
              }
            </td>

            <td>
              <strong>
                ${this.escapeHtml(
                  order.full_name || '—'
                )}
              </strong>

              <small>
                ${this.escapeHtml(
                  this.orderDisplayName(order)
                )}
              </small>
            </td>

            <td>
              ${this.escapeHtml(
                order.email || '—'
              )}
            </td>

            <td>
              <strong>
                ${this.escapeHtml(
                  order.order_number ||
                  `#${order.id}`
                )}
              </strong>
            </td>

            <td>
              ${this.escapeHtml(
                this.capitalize(
                  order.status || 'pending'
                )
              )}
            </td>

            <td>
              ${this.fileCount(order)}
            </td>
          </tr>
        `
      }).join('')

      printWindow.document.write(`
        <!doctype html>
        <html>
          <head>
            <meta charset="UTF-8">

            <title>
              Prosix Selected Place Orders
            </title>

            <style>
              @page {
                size: A4 landscape;
                margin: 9mm;
              }

              * {
                box-sizing: border-box;
              }

              body {
                margin: 0;
                font-family: Arial, sans-serif;
                color: #111;
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
              }

              header {
                padding: 14px 18px;
                background: #050505;
                color: white;
                display: flex;
                align-items: center;
                justify-content: space-between;
              }

              header strong {
                font-size: 18px;
                letter-spacing: .08em;
              }

              header span {
                color: #ccc;
                font-size: 10px;
              }

              table {
                width: 100%;
                margin-top: 12px;
                border-collapse: collapse;
              }

              th,
              td {
                padding: 9px;
                border: 1px solid #ddd;
                text-align: left;
                vertical-align: middle;
                font-size: 10px;
              }

              th {
                background: #202328;
                color: white;
                font-size: 9px;
                text-transform: uppercase;
              }

              td small {
                display: block;
                margin-top: 3px;
                color: #777;
              }

              .p-thumb,
              .p-empty {
                width: 58px;
                height: 48px;
                border-radius: 6px;
                background: #f3f4f6;
              }

              .p-thumb {
                object-fit: contain;
              }

              .p-empty {
                color: #999;
                font-size: 7px;
                display: grid;
                place-items: center;
              }

/* =========================================================
   PLACE ORDERS — STATUS / REMARK SYNC
   ========================================================= */
.status-cell {
  min-width: 165px;
  position: relative;
}

.remark-cell {
  min-width: 190px;
}

.po-status-wrap {
  width: 155px;
  position: relative;
}

.po-status-trigger {
  width: 155px;
  height: 38px;
  padding: 0 11px;
  border: 1px solid transparent;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f3f4f6;
  cursor: pointer;
  font-size: 12px;
  font-weight: 800;
}

.po-status-trigger:disabled {
  cursor: default;
}

.po-status-trigger > span:nth-child(2) {
  min-width: 0;
  flex: 1;
  overflow: hidden;
  text-align: left;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.po-status-trigger i {
  font-size: 9px;
}

.po-status-dot {
  width: 8px;
  height: 8px;
  flex-shrink: 0;
  border-radius: 50%;
}

.po-status-menu {
  width: 245px;
  padding: 7px;
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  z-index: 1200;
  border: 1px solid #e1e5ea;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 18px 42px rgba(15, 23, 42, .16);
}

.po-status-menu-item,
.po-custom-status-open {
  width: 100%;
  min-height: 38px;
  padding: 0 9px;
  border: 0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 9px;
  background: transparent;
  color: #344054;
  cursor: pointer;
  font-size: 11px;
  font-weight: 700;
}

.po-status-menu-item:hover,
.po-custom-status-open:hover {
  background: #f7f8fa;
}

.po-status-menu-item i {
  margin-left: auto;
  font-size: 9px;
}

.po-status-divider {
  height: 1px;
  margin: 6px 3px;
  background: #eceff3;
}

.po-custom-status-open {
  color: #111827;
  font-weight: 850;
}

.remark-line-wrap {
  width: 180px;
  position: relative;
  padding-bottom: 11px;
}

.remark-line-input {
  width: 100%;
  height: 36px;
  padding: 0 3px;
  border: 0;
  border-bottom: 1px solid #cfd5dd;
  outline: 0;
  background: transparent;
  color: #101828;
  font-size: 12px;
}

.remark-line-input:focus {
  border-bottom-color: #111827;
}

.remark-saved {
  position: absolute;
  right: 0;
  bottom: -1px;
  color: #15956b;
  font-size: 8px;
  font-weight: 700;
}

.remark-readonly {
  display: block;
  max-width: 180px;
  overflow: hidden;
  color: #667085;
  font-size: 11px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.placeorder-controls-card {
  margin-top: 14px;
  padding: 15px;
  border: 1px solid #e4e7ec;
  border-radius: 12px;
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr);
  gap: 18px;
  background: #f9fafb;
}

.placeorder-control > label {
  display: block;
  margin-bottom: 7px;
  color: #667085;
  font-size: 10px;
  font-weight: 850;
  text-transform: uppercase;
}

.modal-status-wrap,
.modal-status-trigger {
  width: 100%;
}

.modal-po-status-menu {
  width: 260px;
}

.modal-remark-wrap {
  position: relative;
  padding-bottom: 12px;
}

.modal-remark-wrap input {
  width: 100%;
  height: 38px;
  padding: 0 4px;
  border: 0;
  border-bottom: 1px solid #cfd5dd;
  outline: 0;
  background: transparent;
  color: #101828;
  font-size: 12px;
}

.modal-remark-wrap small {
  position: absolute;
  right: 0;
  bottom: 0;
  color: #98a2b3;
  font-size: 8px;
}

.modal-remark-readonly {
  min-height: 38px;
  padding: 10px 11px;
  border: 1px solid #e4e7ec;
  border-radius: 9px;
  background: #fff;
  color: #475467;
  font-size: 11px;
}

/* Status manager */
.status-manager-overlay {
  padding: 28px;
  position: fixed;
  inset: 0;
  z-index: 5000;
  overflow-y: auto;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  background: rgba(15, 23, 42, .56);
  backdrop-filter: blur(3px);
}

.status-manager-modal {
  width: min(900px, 100%);
  overflow: hidden;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 28px 70px rgba(15, 23, 42, .24);
}

.status-manager-head {
  padding: 20px 22px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  background: #111827;
  color: #fff;
}

.status-manager-head span {
  color: #98a2b3;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: .12em;
}

.status-manager-head h2 {
  margin: 4px 0 0;
  font-size: 20px;
}

.status-manager-head p {
  margin: 6px 0 0;
  color: #c5cbd5;
  font-size: 11px;
}

.status-manager-head > button {
  width: 38px;
  height: 38px;
  border: 0;
  border-radius: 10px;
  background: #242b39;
  color: #fff;
  cursor: pointer;
}

.status-manager-body {
  padding: 18px;
  background: #f7f8fa;
}

.new-status-row {
  padding: 14px;
  border: 1px solid #e4e7ec;
  border-radius: 12px;
  display: flex;
  align-items: flex-end;
  gap: 10px;
  background: #fff;
}

.status-form-field {
  min-width: 0;
  flex: 1;
}

.status-form-field.color-field {
  max-width: 100px;
}

.status-form-field label,
.status-edit-name label,
.status-edit-color label,
.status-definition-preview label {
  display: block;
  margin-bottom: 5px;
  color: #667085;
  font-size: 9px;
  font-weight: 850;
}

.status-form-field input[type="text"],
.status-edit-name input {
  width: 100%;
  height: 39px;
  padding: 0 10px;
  border: 1px solid #d0d5dd;
  border-radius: 9px;
  outline: 0;
  font-size: 12px;
}

.status-form-field input[type="color"],
.status-edit-color input {
  width: 52px;
  height: 39px;
  border: 0;
  background: transparent;
  cursor: pointer;
}

.create-status-btn {
  height: 39px;
  padding: 0 14px;
  border: 0;
  border-radius: 9px;
  background: #111827;
  color: #fff;
  cursor: pointer;
  font-size: 11px;
  font-weight: 850;
}

.create-status-btn:disabled {
  opacity: .45;
}

.status-definition-list {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.status-definition-row {
  padding: 11px;
  border: 1px solid #e4e7ec;
  border-radius: 11px;
  display: grid;
  grid-template-columns: 10px minmax(180px, 1fr) 70px 160px 95px;
  align-items: end;
  gap: 10px;
  background: #fff;
}

.status-color-preview {
  width: 9px;
  height: 42px;
  align-self: center;
  border-radius: 999px;
}

.status-edit-name small {
  display: block;
  margin-top: 3px;
  color: #98a2b3;
  font-size: 8px;
}

.status-definition-preview > span {
  min-height: 36px;
  padding: 0 9px;
  border: 1px solid transparent;
  border-radius: 9px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 10px;
  font-weight: 800;
}

.status-definition-preview i {
  width: 7px;
  height: 7px;
  border-radius: 50%;
}

.status-definition-actions {
  display: flex;
  gap: 5px;
}

.status-save-btn,
.status-delete-btn {
  height: 36px;
  border: 0;
  border-radius: 8px;
  cursor: pointer;
  font-size: 9px;
  font-weight: 850;
}

.status-save-btn {
  padding: 0 11px;
  background: #111827;
  color: #fff;
}

.status-delete-btn {
  width: 36px;
  background: #fee4e2;
  color: #b42318;
}

@media (max-width: 760px) {
  .placeorder-controls-card {
    grid-template-columns: 1fr;
  }

  .new-status-row {
    align-items: stretch;
    flex-direction: column;
  }

  .status-form-field.color-field {
    max-width: none;
  }

  .status-definition-row {
    grid-template-columns: 10px 1fr 70px;
  }

  .status-definition-preview,
  .status-definition-actions {
    grid-column: 2 / -1;
  }
}


/* =========================================================
   APP LAYOUT / TEAMSTORE-LIKE PLACE ORDER CONTROLS
   ========================================================= */

.place-orders-page {
  width: 100%;
  min-width: 0;
  min-height: 100vh;
  background: #f5f6f8;
}

/* AppLayout owns the left sidebar exactly like TeamStore. */
.place-orders-page .page-head,
.place-orders-page .filters-bar,
.place-orders-page .orders-card {
  width: 100%;
  min-width: 0;
}

/* Wider table so Status + Remark never get squeezed. */
.orders-table {
  min-width: 1320px !important;
}

.status-cell {
  min-width: 185px !important;
  overflow: visible !important;
}

.status-control {
  width: 170px;
  position: relative;
}

.status-trigger {
  width: 170px;
  height: 40px;
  padding: 0 12px;
  border: 1px solid transparent;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 9px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 800;
  transition: .18s ease;
}

.status-trigger:hover:not(:disabled) {
  filter: brightness(.98);
}

.status-trigger:disabled {
  cursor: default;
  opacity: .8;
}

.status-trigger-dot,
.option-dot {
  width: 8px;
  height: 8px;
  flex: 0 0 8px;
  border-radius: 50%;
}

.status-trigger-label {
  min-width: 0;
  flex: 1;
  overflow: hidden;
  text-align: left;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.status-trigger > i {
  margin-left: auto;
  font-size: 9px;
}

.status-menu {
  width: 270px;
  padding: 8px;
  position: absolute;
  top: calc(100% + 7px);
  left: 0;
  z-index: 3000;
  border: 1px solid #e2e6eb;
  border-radius: 13px;
  background: #fff;
  box-shadow: 0 18px 45px rgba(15, 23, 42, .17);
}

.status-menu-title {
  padding: 5px 7px 8px;
  color: #98a2b3;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.status-option,
.custom-status-toggle,
.manage-status-link {
  width: 100%;
  min-height: 39px;
  padding: 0 10px;
  border: 0;
  border-radius: 9px;
  display: flex;
  align-items: center;
  gap: 9px;
  background: transparent;
  color: #344054;
  cursor: pointer;
  font-size: 11px;
  font-weight: 750;
  text-align: left;
}

.status-option:hover,
.custom-status-toggle:hover,
.manage-status-link:hover {
  background: #f7f8fa;
}

.status-option.selected {
  background: #f2f4f7;
  color: #101828;
  font-weight: 900;
}

.status-option > i {
  margin-left: auto;
  font-size: 9px;
}

.custom-divider {
  height: 1px;
  margin: 6px 3px;
  background: #eaecf0;
}

.custom-status-box {
  margin: 5px 0 7px;
  padding: 8px;
  border: 1px solid #e4e7ec;
  border-radius: 10px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 38px 54px;
  gap: 6px;
  background: #f9fafb;
}

.custom-status-box input[type="text"] {
  min-width: 0;
  height: 38px;
  padding: 0 9px;
  border: 1px solid #d0d5dd;
  border-radius: 8px;
  outline: 0;
  background: #fff;
  color: #101828;
  font-size: 11px;
}

.custom-status-box input[type="text"]:focus {
  border-color: #111827;
}

.custom-status-color {
  width: 38px !important;
  height: 38px !important;
  padding: 2px !important;
  border: 1px solid #d0d5dd !important;
  border-radius: 8px !important;
  background: #fff !important;
  cursor: pointer;
}

.custom-status-box button {
  height: 38px;
  border: 0;
  border-radius: 8px;
  background: #111827;
  color: #fff;
  cursor: pointer;
  font-size: 10px;
  font-weight: 850;
}

.custom-status-box button:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.manage-status-link {
  margin-top: 3px;
  color: #111827;
  font-weight: 850;
}

.saving-text {
  display: block;
  margin-top: 4px;
  color: #98a2b3;
  font-size: 9px;
}

/* ---------------------------------------------------------
   BIGGER REMARK FIELD
   --------------------------------------------------------- */
.remark-cell {
  min-width: 300px !important;
}

.remark-editor {
  width: 280px;
  position: relative;
  padding-bottom: 13px;
}

.remark-input-wrap {
  width: 100%;
  height: 42px;
  padding: 0 11px;
  border: 1px solid #d9dee6;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 9px;
  background: #fff;
  transition: .18s ease;
}

.remark-input-wrap:focus-within {
  border-color: #111827;
  box-shadow: 0 0 0 3px rgba(17, 24, 39, .05);
}

.remark-input-wrap > i {
  flex-shrink: 0;
  color: #98a2b3;
  font-size: 12px;
}

.remark-input {
  width: 100%;
  min-width: 0;
  height: 38px;
  padding: 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: #101828;
  font-size: 12px;
  font-weight: 550;
}

.remark-input::placeholder {
  color: #98a2b3;
}

.remark-save-state {
  position: absolute;
  right: 3px;
  bottom: 0;
  min-height: 11px;
  color: #98a2b3;
  font-size: 9px;
  font-weight: 700;
}

.remark-save-state.saved {
  color: #15956b;
}

.remark-readonly-box {
  width: 280px;
  min-height: 42px;
  padding: 0 11px;
  border: 1px solid #e4e7ec;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f9fafb;
  color: #667085;
  font-size: 11px;
}

.remark-readonly-box i {
  color: #98a2b3;
}

/* Modal controls should also be easy to type in. */
.placeorder-controls-card {
  grid-template-columns: 260px minmax(0, 1fr) !important;
}

.modal-status-trigger {
  width: 100% !important;
}

.modal-remark-wrap input {
  height: 42px !important;
  padding: 0 11px !important;
  border: 1px solid #d9dee6 !important;
  border-radius: 10px !important;
  background: #fff !important;
  font-size: 12px !important;
}

@media (max-width: 900px) {
  .remark-cell {
    min-width: 260px !important;
  }

  .remark-editor,
  .remark-readonly-box {
    width: 240px;
  }

  .placeorder-controls-card {
    grid-template-columns: 1fr !important;
  }
}



/* =========================================================
   FINAL CLEAN PLACE ORDERS UI
   - no external edit button
   - Customize lives inside status dropdown
   - compact status/remark controls
   - larger readable page typography
   ========================================================= */

/* General page typography */
.place-orders-page .page-head h1 {
  font-size: 25px !important;
  line-height: 1.15 !important;
}

.place-orders-page .page-head > div > span {
  font-size: 12px !important;
}

.place-orders-page .eyebrow {
  font-size: 10px !important;
  font-weight: 900 !important;
  letter-spacing: .11em !important;
}

.place-orders-page .tabs button {
  min-height: 34px !important;
  padding: 0 13px !important;
  font-size: 11px !important;
  font-weight: 800 !important;
}

.place-orders-page .search-box input {
  font-size: 12px !important;
}

.place-orders-page .print-selected-btn,
.place-orders-page .view-btn {
  font-size: 11px !important;
  font-weight: 850 !important;
}

.place-orders-page .orders-table th {
  padding-top: 13px !important;
  padding-bottom: 13px !important;
  font-size: 10px !important;
  font-weight: 950 !important;
  letter-spacing: .04em !important;
}

.place-orders-page .orders-table td {
  padding-top: 11px !important;
  padding-bottom: 11px !important;
  font-size: 12px !important;
}

.place-orders-page .id-cell,
.place-orders-page .order-no,
.place-orders-page .email-cell {
  font-size: 11px !important;
}

.place-orders-page .customer-cell strong {
  font-size: 12px !important;
  font-weight: 850 !important;
}

.place-orders-page .customer-cell small {
  margin-top: 4px !important;
  font-size: 9px !important;
}

.place-orders-page .file-count,
.place-orders-page .no-files {
  font-size: 9px !important;
}

/* Status: compact, clean, single control */
.place-orders-page .status-cell {
  min-width: 165px !important;
}

.place-orders-page .status-select-shell {
  width: 155px !important;
  gap: 0 !important;
}

.place-orders-page .status-select-pro {
  width: 155px !important;
  height: 37px !important;
  padding: 0 29px 0 28px !important;
  border-radius: 9px !important;
  font-size: 11px !important;
  font-weight: 800 !important;
  line-height: 37px !important;
}

.place-orders-page .status-select-dot {
  width: 7px !important;
  height: 7px !important;
  left: 11px !important;
}

.place-orders-page .status-select-pro option {
  font-size: 12px !important;
  font-weight: 650 !important;
  background: #fff !important;
  color: #101828 !important;
}

/* Old pencil/edit control must never appear */
.place-orders-page .manage-status-mini,
.place-orders-page .modal-manage-status {
  display: none !important;
}

/* Remark: slightly smaller than before, still proper input */
.place-orders-page .remark-cell {
  min-width: 270px !important;
}

.place-orders-page .remark-field-pro,
.place-orders-page .remark-readonly-pro {
  width: 255px !important;
  height: 37px !important;
  padding: 0 10px !important;
  border-radius: 9px !important;
  gap: 8px !important;
}

.place-orders-page .remark-field-pro > i,
.place-orders-page .remark-readonly-pro > i {
  font-size: 11px !important;
}

.place-orders-page .remark-field-pro input {
  height: 34px !important;
  font-size: 11px !important;
}

.place-orders-page .remark-readonly-pro {
  font-size: 11px !important;
}

/* Modal status/remark controls */
.place-orders-page .modal-status-select-shell {
  width: 100% !important;
}

.place-orders-page .modal-status-select {
  width: 100% !important;
  height: 39px !important;
  font-size: 11px !important;
}

.place-orders-page .modal-remark-wrap input {
  height: 39px !important;
  font-size: 11px !important;
}

/* Make manager modal crisp and readable */
.status-manager-head h2 {
  font-size: 21px !important;
}

.status-manager-head p {
  font-size: 11px !important;
}

.status-form-field input[type="text"],
.status-edit-name input {
  font-size: 12px !important;
}

.status-definition-row {
  font-size: 11px !important;
}

</style>
          </head>

          <body>
            <header>
              <strong>PROSIX SPORTS</strong>

              <span>
                ${orders.length}
                Selected Place Orders
              </span>
            </header>

            <table>
              <thead>
                <tr>
                  <th>Thumbnail</th>
                  <th>Customer / Order</th>
                  <th>Email</th>
                  <th>Order #</th>
                  <th>Status</th>
                  <th>Files</th>
                </tr>
              </thead>

              <tbody>
                ${rows}
              </tbody>
            </table>
          </body>
        </html>
      `)

      printWindow.document.close()

      this.waitForImages(
        printWindow,
        1800
      ).then(() => {
        printWindow.focus()

        setTimeout(() => {
          printWindow.print()
        }, 100)
      })
    },

    orderDisplayName(order) {
      return (
        order?.order_name ||
        order?.product_name ||
        order?.design_name ||
        order?.model_name ||
        order?.title ||
        order?.name ||
        'Custom Order'
      )
    },

    orderThumbnail(order) {
      const files = Array.isArray(order?.mockup_files) ? order.mockup_files : []
      const firstImage = files.find(file => this.isImage(file))
      return firstImage ? this.fileUrl(firstImage, 'mockup') : ''
    },

    fileName(file) {
      if (typeof file === 'string') {
        return file.split('?')[0].split('/').pop()
      }

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
      const name = this.fileName(file).split('?')[0].toLowerCase()
      return ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.bmp']
        .some(ext => name.endsWith(ext))
    },

    extension(file) {
      const parts = this.fileName(file).split('.')
      return parts.length > 1 ? parts.pop().toUpperCase() : 'FILE'
    },

    hideBrokenImage(event) {
      event.target.style.display = 'none'
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

    dateValue(value) {
      return this.formatDate(value)
    },

    statusClass(status) {
      return `status-${String(status || 'pending').toLowerCase()}`
    },

    downloadPdf() {
      if (!this.selectedOrder?.id) return
      window.open(
        `${this.prosixBaseUrl}/order/download/${this.selectedOrder.id}`,
        '_blank',
        'noopener,noreferrer'
      )
    },

    escapeHtml(value) {
      return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;')
    },

    stripHtml(value) {
      const div = document.createElement('div')
      div.innerHTML = String(value || '')
      return div.textContent || div.innerText || ''
    },

    waitForImages(printWindow, timeout = 2200) {
      return new Promise(resolve => {
        const images = Array.from(printWindow.document.images || [])
        if (!images.length) return resolve()

        let loaded = 0
        let finished = false

        const done = () => {
          loaded += 1
          if (!finished && loaded >= images.length) {
            finished = true
            resolve()
          }
        }

        images.forEach(image => {
          if (image.complete) return done()
          image.addEventListener('load', done, { once: true })
          image.addEventListener('error', done, { once: true })
        })

        setTimeout(() => {
          if (!finished) {
            finished = true
            resolve()
          }
        }, timeout)
      })
    },

    async printOrder() {
      const order = this.selectedOrder
      if (!order) return

      const printWindow = window.open('', '_blank', 'width=1200,height=850')

      if (!printWindow) {
        alert('Please allow popups for printing.')
        return
      }

      const thumbnail = this.orderThumbnail(order)
      const fileSections = this.fileGroups.map(group => {
        const files = group.files || []
        const cards = files.map(file => {
          const url = this.fileUrl(file, group.folder)
          const preview = this.isImage(file)
            ? `<img src="${this.escapeHtml(url)}" alt="" />`
            : `<div class="print-file-icon">${this.escapeHtml(this.extension(file))}</div>`

          return `
            <div class="print-file">
              ${preview}
              <span>${this.escapeHtml(this.fileName(file))}</span>
            </div>
          `
        }).join('')

        return `
          <section class="print-card">
            <h3>${this.escapeHtml(group.title)} <span>${files.length}</span></h3>
            ${cards ? `<div class="print-files">${cards}</div>` : '<p class="empty-text">No files uploaded.</p>'}
          </section>
        `
      }).join('')

      const notes = this.stripHtml(order.notes)

      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset="UTF-8" />
            <title>${this.escapeHtml(order.order_number || `Order ${order.id}`)}</title>
            <style>
              @page { size: A4 landscape; margin: 8mm; }
              * { box-sizing: border-box; }
              body { margin:0; color:#111; background:#fff; font-family:Arial,Helvetica,sans-serif; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
              .black-head { min-height:50px; padding:11px 17px; background:#050505; color:#fff; display:flex; align-items:center; justify-content:space-between; }
              .brand strong,.brand small { display:block; }
              .brand strong { font-size:15px; letter-spacing:.08em; }
              .brand small { margin-top:3px; color:#bbb; font-size:7px; letter-spacing:.14em; }
              .print-status { padding:6px 10px; border-radius:999px; background:#fff; color:#111; font-size:9px; font-weight:800; }
              .thanks { padding:11px; border-bottom:1px solid #ddd; background:#f8f8f8; text-align:center; }
              .thanks strong { display:block; font-size:13px; font-style:italic; }
              .thanks span { display:block; margin-top:3px; color:#888; font-size:6px; letter-spacing:.24em; }
              .content { padding:14px; }
              .hero { min-height:90px; padding:10px; border:1px solid #ddd; border-radius:8px; display:flex; align-items:center; gap:13px; }
              .hero-image,.hero-placeholder { width:100px; height:78px; border:1px solid #ddd; border-radius:7px; background:#f5f5f5; }
              .hero-image { object-fit:contain; }
              .hero-placeholder { color:#999; display:grid; place-items:center; font-size:9px; }
              .hero-info { min-width:0; flex:1; }
              .hero-info small { color:#888; font-size:6px; font-weight:800; text-transform:uppercase; }
              .hero-info h1 { margin:5px 0; font-size:19px; }
              .hero-info p { margin:0; color:#666; font-size:8px; }
              .details-grid { margin-top:12px; display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:7px; }
              .field span,.team-colors span { display:block; margin-bottom:4px; color:#888; font-size:6px; font-weight:800; text-transform:uppercase; }
              .field strong,.team-colors strong { min-height:29px; padding:7px; border:1px solid #d8d8d8; border-radius:5px; background:#f8f8f8; display:flex; align-items:center; overflow-wrap:anywhere; font-size:8px; }
              .team-colors { margin-top:10px; }
              .cards { margin-top:12px; padding-top:12px; border-top:1px solid #ddd; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:9px; }
              .print-card { min-height:120px; padding:9px; border:1px solid #ddd; border-radius:7px; }
              .print-card h3 { margin:0 0 8px; padding-bottom:7px; border-bottom:1px solid #eee; font-size:8px; display:flex; justify-content:space-between; }
              .print-card h3 span { padding:2px 5px; border-radius:999px; background:#111; color:#fff; font-size:6px; }
              .print-files { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:6px; }
              .print-file img,.print-file-icon { width:100%; height:55px; border-radius:5px; background:#f4f4f4; object-fit:contain; }
              .print-file-icon { color:#666; display:grid; place-items:center; font-size:8px; font-weight:800; }
              .print-file span { display:block; margin-top:3px; overflow:hidden; font-size:6px; text-overflow:ellipsis; white-space:nowrap; }
              .notes-print { min-height:90px; padding:8px; border:1px dashed #ccc; border-radius:5px; background:#fafafa; color:#444; font-size:8px; line-height:1.5; white-space:pre-wrap; }
              .empty-text { color:#999; font-size:8px; text-align:center; }
            </style>
          </head>
          <body>
            <header class="black-head">
              <div class="brand"><strong>PROSIX SPORTS</strong><small>PLACE ORDER DETAILS</small></div>
              <span class="print-status">${this.escapeHtml(this.capitalize(order.status || 'pending'))}</span>
            </header>
            <div class="thanks"><strong>THANKS FOR CHOOSING US!</strong><span>WE REALLY APPRECIATE &amp; VALUE YOUR BUSINESS</span></div>
            <div class="content">
              <section class="hero">
                ${thumbnail ? `<img class="hero-image" src="${this.escapeHtml(thumbnail)}" alt="" />` : '<div class="hero-placeholder">No Image</div>'}
                <div class="hero-info">
                  <small>Order Name</small>
                  <h1>${this.escapeHtml(this.orderDisplayName(order))}</h1>
                  <p>Order # ${this.escapeHtml(order.order_number || order.id)}</p>
                </div>
              </section>
              <div class="details-grid">
                <div class="field"><span>Full Name</span><strong>${this.escapeHtml(order.full_name || '—')}</strong></div>
                <div class="field"><span>Email</span><strong>${this.escapeHtml(order.email || '—')}</strong></div>
                <div class="field"><span>Order Place Date</span><strong>${this.escapeHtml(this.dateValue(order.order_date))}</strong></div>
                <div class="field"><span>Delivery Date</span><strong>${this.escapeHtml(this.dateValue(order.delivery_date))}</strong></div>
                <div class="field"><span>Sales Rep</span><strong>${this.escapeHtml(order.sales_rep || '—')}</strong></div>
                <div class="field"><span>Order #</span><strong>${this.escapeHtml(order.order_number || `#${order.id}`)}</strong></div>
              </div>
              <div class="team-colors"><span>Team Colors</span><strong>${this.escapeHtml(order.team_colors || '—')}</strong></div>
              <section class="cards">
                ${fileSections}
                <section class="print-card"><h3>Notes</h3><div class="notes-print">${this.escapeHtml(notes || 'No notes added.')}</div></section>
              </section>
            </div>
          </body>
        </html>
      `)

      printWindow.document.close()
      await this.waitForImages(printWindow, 2200)
      printWindow.focus()
      setTimeout(() => printWindow.print(), 100)
    }
  },

  beforeUnmount() {
    document.body.style.overflow = ''
  }
}
</script>

<style scoped>
* { box-sizing: border-box; }

.place-orders-page {
  min-height: 100vh;
  padding: 28px 32px;
  background: #f4f5f7;
  color: #111827;
}

.page-head {
  margin-bottom: 18px;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
}

.eyebrow {
  margin: 0 0 4px;
  color: #6b7280;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: .14em;
  text-transform: uppercase;
}

.page-head h1 {
  margin: 0;
  font-size: 24px;
}

.page-head > div > span {
  display: block;
  margin-top: 5px;
  color: #6b7280;
  font-size: 11px;
}

.top-actions {
  display: flex;
  align-items: center;
  gap: 9px;
}

.search-box {
  width: 310px;
  height: 40px;
  padding: 0 12px;
  border: 1px solid #d9dde3;
  border-radius: 9px;
  background: white;
  display: flex;
  align-items: center;
  gap: 8px;
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  font-size: 11px;
}

.print-selected-btn {
  height: 40px;
  padding: 0 13px;
  border: 1px solid #111827;
  border-radius: 8px;
  background: #111827;
  color: white;
  font-size: 10px;
  font-weight: 900;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 7px;
}

.print-selected-btn:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.print-selected-btn span {
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  border-radius: 999px;
  background: white;
  color: #111827;
  display: grid;
  place-items: center;
  font-size: 8px;
}

.filters-bar {
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.tabs {
  display: flex;
  gap: 6px;
  overflow-x: auto;
}

.tabs button {
  padding: 7px 11px;
  border: 1px solid #d9dde3;
  border-radius: 999px;
  background: white;
  color: #6b7280;
  font-size: 9px;
  font-weight: 800;
  cursor: pointer;
}

.tabs button.active {
  border-color: #111827;
  background: #111827;
  color: white;
}

.new-badge {
  padding: 6px 9px;
  border-radius: 999px;
  background: #111827;
  color: white;
  font-size: 9px;
  font-weight: 900;
}

.orders-card {
  overflow: hidden;
  border: 1px solid #dfe3e8;
  border-radius: 12px;
  background: white;
  box-shadow: 0 8px 28px rgba(15,23,42,.04);
}

.table-wrap { overflow-x: auto; }

.orders-table {
  width: 100%;
  min-width: 1050px;
  border-collapse: collapse;
}

.orders-table th,
.orders-table td {
  padding: 11px 13px;
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
  vertical-align: middle;
}

.orders-table th {
  background: #202328;
  color: white;
  font-size: 8px;
  font-weight: 900;
  letter-spacing: .04em;
  text-transform: uppercase;
}

.orders-table td { font-size: 10px; }

.orders-table tbody tr:hover { background: #fafafa; }
.orders-table tbody tr.unread { background: #f7fbff; }

.check-col {
  width: 42px;
  text-align: center !important;
}

.check-col input {
  width: 16px;
  height: 16px;
  accent-color: #2563eb;
  cursor: pointer;
}

.id-cell {
  width: 48px;
  color: #4b5563;
  font-weight: 800;
}

.table-thumb {
  width: 62px;
  height: 52px;
  overflow: hidden;
  border: 1px solid #dfe3e8;
  border-radius: 8px;
  background: #f6f7f8;
}

.table-thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.table-thumb span {
  width: 100%;
  height: 100%;
  color: #9ca3af;
  font-size: 7px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
}

.customer-cell strong,
.customer-cell small {
  display: block;
}

.customer-cell strong { font-size: 10px; }

.customer-cell small {
  margin-top: 3px;
  color: #8a9099;
  font-size: 8px;
}

.email-cell {
  display: inline-block;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.order-no {
  font-size: 9px;
  white-space: nowrap;
}

.status-pill {
  display: inline-flex;
  padding: 5px 8px;
  border-radius: 999px;
  font-size: 8px;
  font-weight: 900;
}

.file-count {
  padding: 5px 7px;
  border-radius: 999px;
  background: #111827;
  color: white;
  font-size: 7px;
  font-weight: 900;
}

.no-files {
  color: #9ca3af;
  font-size: 8px;
}

.view-btn {
  height: 32px;
  padding: 0 10px;
  border: 1px solid #111827;
  border-radius: 7px;
  background: #111827;
  color: white;
  font-size: 8px;
  font-weight: 900;
  cursor: pointer;
}

.table-state {
  min-height: 320px;
  color: #6b7280;
  font-size: 11px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* MODAL */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;
  padding: 28px;
  background: rgba(0,0,0,.68);
  backdrop-filter: blur(3px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.order-modal {
  width: min(1180px, 100%);
  max-height: calc(100vh - 56px);
  overflow: hidden;
  border-radius: 14px;
  background: white;
  box-shadow: 0 30px 90px rgba(0,0,0,.32);
}

.view-black-header {
  min-height: 62px;
  padding: 12px 18px;
  background: #050505;
  color: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.view-brand {
  display: flex;
  align-items: center;
  gap: 9px;
}

.prosix-mark {
  width: 34px;
  height: 34px;
  border-radius: 7px;
  background: white;
  color: #050505;
  font-size: 17px;
  font-weight: 1000;
  font-style: italic;
  display: grid;
  place-items: center;
}

.view-brand strong,
.view-brand small { display: block; }

.view-brand strong {
  font-size: 11px;
  letter-spacing: .1em;
}

.view-brand small {
  margin-top: 3px;
  color: #9ca3af;
  font-size: 6px;
  letter-spacing: .16em;
}

.view-header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.header-status {
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 8px;
  font-weight: 900;
}

.modal-close {
  width: 34px;
  height: 34px;
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 8px;
  background: rgba(255,255,255,.08);
  color: white;
  cursor: pointer;
}

.thank-you-strip {
  padding: 11px 18px;
  border-bottom: 1px solid #e5e7eb;
  background: #f7f7f7;
  text-align: center;
}

.thank-you-strip h4 {
  margin: 0;
  font-size: 11px;
  font-style: italic;
  letter-spacing: .08em;
}

.thank-you-strip p {
  margin: 3px 0 0;
  color: #a0a0a0;
  font-size: 5.5px;
  letter-spacing: .32em;
}

.order-view-body {
  max-height: calc(100vh - 145px);
  padding: 18px;
  overflow-y: auto;
}

.order-hero-card {
  min-height: 115px;
  padding: 12px;
  border: 1px solid #dfe2e6;
  border-radius: 10px;
  background: #fafafa;
  display: flex;
  align-items: center;
  gap: 14px;
}

.hero-thumbnail {
  width: 135px;
  height: 95px;
  flex-shrink: 0;
  overflow: hidden;
  border: 1px solid #d7dbe0;
  border-radius: 8px;
  background: white;
}

.hero-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.hero-thumbnail-empty {
  width: 100%;
  height: 100%;
  color: #9ca3af;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  font-size: 8px;
}

.hero-order-info {
  min-width: 0;
  flex: 1;
}

.small-label,
.hero-side-info span {
  color: #8a8f98;
  font-size: 6px;
  font-weight: 900;
  letter-spacing: .1em;
  text-transform: uppercase;
}

.hero-order-info h1 {
  margin: 5px 0;
  font-size: 19px;
}

.hero-order-info p {
  margin: 0;
  color: #6b7280;
  font-size: 8px;
}

.hero-side-info {
  min-width: 105px;
  padding-left: 13px;
  border-left: 1px solid #e0e2e5;
  text-align: right;
}

.hero-side-info strong {
  display: block;
  margin-top: 5px;
  font-size: 10px;
}

.order-details-grid {
  margin-top: 14px;
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 8px;
}

.detail-field span,
.team-colors-box span {
  display: block;
  margin-bottom: 4px;
  color: #7c828c;
  font-size: 6px;
  font-weight: 900;
  text-transform: uppercase;
}

.detail-field strong,
.team-colors-box strong {
  min-height: 36px;
  padding: 8px;
  border: 1px solid #d8dce1;
  border-radius: 6px;
  background: #f7f7f7;
  overflow-wrap: anywhere;
  font-size: 8px;
  display: flex;
  align-items: center;
}

.team-colors-box { margin-top: 12px; }

.section-divider {
  height: 1px;
  margin: 16px 0;
  background: #e5e7eb;
}

.file-notes-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.document-card {
  min-width: 0;
  min-height: 220px;
  padding: 11px;
  border: 1px solid #dcdfe4;
  border-radius: 9px;
  background: white;
}

.document-card-title {
  margin-bottom: 9px;
  padding-bottom: 8px;
  border-bottom: 1px solid #eceef1;
  font-size: 8px;
  font-weight: 900;
  display: flex;
  justify-content: space-between;
}

.document-card-title span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.document-card-title em {
  min-width: 19px;
  height: 19px;
  padding: 0 5px;
  border-radius: 999px;
  background: #111827;
  color: white;
  font-size: 7px;
  font-style: normal;
  display: grid;
  place-items: center;
}

.document-list {
  max-height: 175px;
  overflow-y: auto;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 6px;
}

.document-file {
  min-width: 0;
  overflow: hidden;
  border: 1px solid #e1e4e8;
  border-radius: 6px;
}

.document-preview {
  height: 70px;
  background: #f5f6f8;
  display: block;
}

.document-preview img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.document-file-icon {
  width: 100%;
  height: 100%;
  color: #6b7280;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.document-file-info { padding: 5px; }

.document-file-info > strong {
  display: block;
  overflow: hidden;
  font-size: 6.5px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.document-file-info div {
  margin-top: 4px;
  display: flex;
  gap: 3px;
}

.document-file-info a {
  flex: 1;
  padding: 3px;
  border-radius: 4px;
  background: #f1f2f4;
  color: #111827;
  font-size: 5.5px;
  font-weight: 900;
  text-align: center;
  text-decoration: none;
}

.document-file-info a:last-child {
  background: #111827;
  color: white;
}

.document-empty {
  min-height: 140px;
  color: #9ca3af;
  font-size: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.notes-box {
  min-height: 140px;
  max-height: 175px;
  overflow-y: auto;
  padding: 9px;
  border: 1px dashed #d0d3d8;
  border-radius: 6px;
  background: #fafafa;
  color: #4b5563;
  font-size: 8px;
  line-height: 1.6;
}

.order-actions-footer {
  margin-top: 16px;
  padding-top: 13px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.footer-order-id span,
.footer-order-id strong { display: block; }

.footer-order-id span {
  color: #9ca3af;
  font-size: 6px;
  font-weight: 900;
  text-transform: uppercase;
}

.footer-order-id strong {
  margin-top: 3px;
  font-size: 9px;
}

.footer-buttons {
  display: flex;
  gap: 7px;
}

.download-pdf-btn,
.print-order-btn {
  height: 36px;
  padding: 0 12px;
  border-radius: 7px;
  font-size: 8px;
  font-weight: 900;
  cursor: pointer;
}

.download-pdf-btn {
  border: 1px solid #111827;
  background: white;
  color: #111827;
}

.print-order-btn {
  border: 1px solid #111827;
  background: #111827;
  color: white;
}

.status-pending { background: #fef3c7; color: #92400e; }
.status-processing { background: #dbeafe; color: #1d4ed8; }
.status-completed { background: #dcfce7; color: #166534; }
.status-cancelled,
.status-canceled { background: #fee2e2; color: #991b1b; }

@media (max-width: 1050px) {
  .page-head {
    align-items: stretch;
    flex-direction: column;
  }

  .top-actions,
  .search-box {
    width: 100%;
  }

  .order-details-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .file-notes-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 650px) {
  .place-orders-page { padding: 15px; }
  .top-actions { flex-direction: column; }
  .print-selected-btn { width: 100%; justify-content: center; }

  .modal-overlay { padding: 0; }
  .order-modal {
    max-height: 100vh;
    border-radius: 0;
  }

  .hero-side-info { display: none; }
  .order-hero-card { align-items: flex-start; }

  .order-details-grid {
    grid-template-columns: 1fr 1fr;
  }

  .file-notes-grid { grid-template-columns: 1fr; }

  .order-actions-footer {
    align-items: stretch;
    flex-direction: column;
  }

  .footer-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
  }
}


/* =========================================================
   FINAL ROBUST STATUS + REMARK CONTROLS
   ========================================================= */
.status-cell {
  min-width: 240px !important;
}

.status-select-shell,
.modal-status-select-shell {
  width: 220px;
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-select-dot {
  width: 9px;
  height: 9px;
  position: absolute;
  left: 12px;
  z-index: 2;
  flex: 0 0 9px;
  border-radius: 50%;
  pointer-events: none;
}

.status-select-pro {
  width: 178px;
  height: 42px;
  padding: 0 34px 0 31px;
  border: 1px solid #d0d5dd;
  border-radius: 10px;
  outline: none;
  appearance: auto;
  background-color: #fff;
  color: #101828;
  cursor: pointer;
  font-family: inherit;
  font-size: 12px;
  font-weight: 800;
  line-height: 42px;
  transition: border-color .18s ease, box-shadow .18s ease;
}

.status-select-pro:hover:not(:disabled) {
  border-color: #98a2b3;
}

.status-select-pro:focus {
  border-color: #344054;
  box-shadow: 0 0 0 3px rgba(52, 64, 84, .08);
}

.status-select-pro:disabled {
  cursor: default;
  opacity: .75;
}

.status-select-pro option {
  background: #fff;
  color: #101828;
  font-weight: 600;
}

.manage-status-mini {
  width: 38px;
  height: 38px;
  flex: 0 0 38px;
  border: 1px solid #d0d5dd;
  border-radius: 9px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  color: #344054;
  cursor: pointer;
  font-size: 11px;
  transition: .18s ease;
}

.manage-status-mini:hover {
  border-color: #111827;
  background: #111827;
  color: #fff;
}

.saving-text {
  display: block;
  margin-top: 4px;
  color: #98a2b3;
  font-size: 9px;
}

.remark-cell {
  min-width: 330px !important;
}

.remark-field-pro,
.remark-readonly-pro {
  width: 310px;
  height: 42px;
  padding: 0 11px;
  border: 1px solid #d0d5dd;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 9px;
  background: #fff;
  color: #344054;
  transition: border-color .18s ease, box-shadow .18s ease;
}

.remark-field-pro:focus-within {
  border-color: #344054;
  box-shadow: 0 0 0 3px rgba(52, 64, 84, .08);
}

.remark-field-pro > i,
.remark-readonly-pro > i {
  flex: 0 0 auto;
  color: #98a2b3;
  font-size: 13px;
}

.remark-field-pro input {
  width: 100%;
  min-width: 0;
  height: 38px;
  padding: 0;
  border: 0 !important;
  outline: 0 !important;
  background: transparent !important;
  color: #101828;
  font-family: inherit;
  font-size: 12px;
  font-weight: 500;
}

.remark-field-pro input::placeholder {
  color: #98a2b3;
}

.remark-check {
  width: 22px;
  height: 22px;
  flex: 0 0 22px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #ecfdf3;
  color: #079455;
  font-size: 9px;
}

.remark-readonly-pro {
  overflow: hidden;
  background: #f9fafb;
  color: #667085;
}

.remark-readonly-pro span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.modal-status-select-shell {
  width: 100%;
}

.modal-status-select {
  width: 220px;
}

.modal-manage-status {
  width: auto;
  padding: 0 12px;
  gap: 7px;
  flex-basis: auto;
  font-weight: 800;
}

.placeorder-controls-card .modal-remark-wrap input {
  width: 100%;
  height: 42px !important;
  padding: 0 12px !important;
  border: 1px solid #d0d5dd !important;
  border-radius: 10px !important;
  background: #fff !important;
  color: #101828 !important;
  font-size: 12px !important;
}

@media (max-width: 900px) {
  .remark-cell {
    min-width: 280px !important;
  }

  .remark-field-pro,
  .remark-readonly-pro {
    width: 260px;
  }
}



/* =========================================================
   FIX: MANAGE STATUSES MUST OPEN AS A CENTER MODAL
   ========================================================= */
.status-manager-overlay {
  position: fixed !important;
  inset: 0 !important;
  z-index: 99999 !important;
  width: 100vw !important;
  height: 100vh !important;
  padding: 24px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  overflow-y: auto !important;
  background: rgba(15, 23, 42, 0.62) !important;
  backdrop-filter: blur(3px);
}

.status-manager-modal {
  width: min(760px, calc(100vw - 48px)) !important;
  max-height: calc(100vh - 48px) !important;
  overflow: hidden !important;
  border: 1px solid #e4e7ec !important;
  border-radius: 16px !important;
  background: #fff !important;
  box-shadow: 0 30px 80px rgba(15, 23, 42, .30) !important;
}

.status-manager-head {
  padding: 18px 20px !important;
  display: flex !important;
  align-items: flex-start !important;
  justify-content: space-between !important;
  gap: 18px !important;
  background: #111827 !important;
  color: #fff !important;
}

.status-manager-head > div {
  min-width: 0;
}

.status-manager-head span {
  display: block !important;
  margin-bottom: 4px !important;
  color: #98a2b3 !important;
  font-size: 9px !important;
  font-weight: 900 !important;
  letter-spacing: .11em !important;
}

.status-manager-head h2 {
  margin: 0 !important;
  color: #fff !important;
  font-size: 20px !important;
  font-weight: 850 !important;
}

.status-manager-head p {
  margin: 5px 0 0 !important;
  color: #c5cbd5 !important;
  font-size: 10px !important;
}

.status-manager-head > button {
  width: 36px !important;
  height: 36px !important;
  flex: 0 0 36px !important;
  border: 0 !important;
  border-radius: 9px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  background: rgba(255,255,255,.08) !important;
  color: #fff !important;
  cursor: pointer !important;
}

.status-manager-body {
  max-height: calc(100vh - 145px) !important;
  padding: 14px !important;
  overflow-y: auto !important;
  background: #f7f8fa !important;
}

.new-status-row {
  padding: 10px !important;
  border: 1px solid #e4e7ec !important;
  border-radius: 11px !important;
  display: grid !important;
  grid-template-columns: minmax(0,1fr) 52px 110px !important;
  align-items: end !important;
  gap: 8px !important;
  background: #fff !important;
}

.status-form-field {
  min-width: 0 !important;
}

.status-form-field label,
.status-edit-name label,
.status-edit-color label,
.status-definition-preview label {
  display: block !important;
  margin-bottom: 5px !important;
  color: #667085 !important;
  font-size: 9px !important;
  font-weight: 800 !important;
}

.status-form-field input[type="text"],
.status-edit-name input {
  width: 100% !important;
  height: 38px !important;
  padding: 0 10px !important;
  border: 1px solid #d0d5dd !important;
  border-radius: 8px !important;
  outline: 0 !important;
  background: #fff !important;
  color: #101828 !important;
  font-size: 11px !important;
}

.status-form-field.color-field {
  max-width: none !important;
}

.status-form-field input[type="color"],
.status-edit-color input[type="color"] {
  width: 42px !important;
  height: 38px !important;
  padding: 2px !important;
  border: 1px solid #d0d5dd !important;
  border-radius: 8px !important;
  background: #fff !important;
  cursor: pointer !important;
}

.create-status-btn {
  height: 38px !important;
  padding: 0 13px !important;
  border: 0 !important;
  border-radius: 8px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 6px !important;
  background: #111827 !important;
  color: #fff !important;
  cursor: pointer !important;
  font-size: 10px !important;
  font-weight: 850 !important;
}

.status-definition-list {
  margin-top: 10px !important;
  display: flex !important;
  flex-direction: column !important;
  gap: 7px !important;
}

.status-definition-row {
  padding: 9px !important;
  border: 1px solid #e4e7ec !important;
  border-radius: 10px !important;
  display: grid !important;
  grid-template-columns: 7px minmax(170px,1fr) 52px 145px 90px !important;
  align-items: end !important;
  gap: 8px !important;
  background: #fff !important;
}

.status-color-preview {
  width: 7px !important;
  height: 38px !important;
  align-self: center !important;
  border-radius: 999px !important;
}

.status-edit-name small {
  display: block !important;
  margin-top: 3px !important;
  color: #98a2b3 !important;
  font-size: 8px !important;
}

.status-definition-preview > span {
  min-height: 34px !important;
  padding: 0 9px !important;
  border: 1px solid transparent !important;
  border-radius: 8px !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
  font-size: 9px !important;
  font-weight: 800 !important;
}

.status-definition-preview i {
  width: 7px !important;
  height: 7px !important;
  border-radius: 50% !important;
}

.status-definition-actions {
  display: flex !important;
  align-items: center !important;
  gap: 5px !important;
}

.status-save-btn,
.status-delete-btn {
  height: 34px !important;
  border: 0 !important;
  border-radius: 8px !important;
  cursor: pointer !important;
  font-size: 9px !important;
  font-weight: 850 !important;
}

.status-save-btn {
  padding: 0 11px !important;
  background: #111827 !important;
  color: #fff !important;
}

.status-delete-btn {
  width: 34px !important;
  background: #fee4e2 !important;
  color: #b42318 !important;
}

@media (max-width: 760px) {
  .status-manager-overlay {
    padding: 12px !important;
    align-items: flex-start !important;
  }

  .status-manager-modal {
    width: calc(100vw - 24px) !important;
    max-height: calc(100vh - 24px) !important;
  }

  .new-status-row {
    grid-template-columns: 1fr 52px !important;
  }

  .create-status-btn {
    grid-column: 1 / -1 !important;
  }

  .status-definition-row {
    grid-template-columns: 7px 1fr 52px !important;
  }

  .status-definition-preview,
  .status-definition-actions {
    grid-column: 2 / -1 !important;
  }
}

</style>
