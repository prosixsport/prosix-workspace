<template>
  <div class="place-orders-page">
    <header class="page-head">
      <div>
        <p class="eyebrow">Prosix CRM</p>
        <h1>Place Orders</h1>
        <span>Manage customer orders and production files.</span>
      </div>

      <div class="top-actions">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input
            v-model="search"
            type="text"
            placeholder="Search orders..."
          />
        </div>

        <button
          type="button"
          class="print-selected-btn"
          :disabled="selectedIds.length === 0"
          @click="printSelected"
        >
          <i class="fa-solid fa-print"></i>
          Print Selected
          <span v-if="selectedIds.length">
            {{ selectedIds.length }}
          </span>
        </button>
      </div>
    </header>

    <section class="filters-bar">
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

              <td>
                <span
                  class="status-pill"
                  :class="statusClass(order.status)"
                >
                  {{
                    capitalize(
                      order.status || 'pending'
                    )
                  }}
                </span>
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
              :class="statusClass(selectedOrder.status)"
            >
              {{
                capitalize(
                  selectedOrder.status || 'pending'
                )
              }}
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
      selectedIds: [],
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

        this.selectedOrder = null
      } catch (error) {
        console.error('Place orders fetch error:', error)
        this.orders = []
      } finally {
        this.loading = false
      }
    },

    async selectOrder(order) {
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
</style>
