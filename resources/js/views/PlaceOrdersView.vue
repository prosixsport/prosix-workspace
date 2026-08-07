<template>
  <div class="place-orders-page">
    <aside class="orders-sidebar">
      <div class="sidebar-head">
        <button type="button" class="back-btn" @click="$router.push('/dashboard')">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <div class="sidebar-title">
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

          <span class="sidebar-thumb">
            <img
              v-if="orderThumbnail(order)"
              :src="orderThumbnail(order)"
              :alt="orderDisplayName(order)"
              @error="hideBrokenImage"
            />
            <i v-else class="fa-regular fa-image"></i>
          </span>

          <div class="order-row-body">
            <strong>{{ order.order_number || `Order #${order.id}` }}</strong>
            <span>{{ orderDisplayName(order) }}</span>
            <small>{{ order.full_name || 'Unknown customer' }}</small>
            <small class="order-row-meta">
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
        <span class="empty-icon"><i class="fa-solid fa-cart-shopping"></i></span>
        <h3>Select a Place Order</h3>
        <p>Choose an order from the left side.</p>
      </div>

      <section v-else class="order-view-shell">
        <header class="view-black-header">
          <div class="view-brand">
            <span class="prosix-mark">P</span>
            <div>
              <strong>PROSIX SPORTS</strong>
              <small>PLACE ORDER DETAILS</small>
            </div>
          </div>

          <div class="view-header-actions">
            <span class="header-status" :class="statusClass(selectedOrder.status)">
              {{ capitalize(selectedOrder.status || 'pending') }}
            </span>
            <button type="button" class="header-icon-btn" title="Print order" @click="printOrder">
              <i class="fa-solid fa-print"></i>
            </button>
          </div>
        </header>

        <div class="thank-you-strip">
          <h4>THANKS FOR CHOOSING US!</h4>
          <p>WE REALLY APPRECIATE &amp; VALUE YOUR BUSINESS</p>
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
              <div v-else class="hero-thumbnail-empty">
                <i class="fa-regular fa-image"></i>
                <span>No Image</span>
              </div>
            </div>

            <div class="hero-order-info">
              <span class="small-label">Order Name</span>
              <h1>{{ orderDisplayName(selectedOrder) }}</h1>
              <p>Order # {{ selectedOrder.order_number || selectedOrder.id }}</p>
            </div>

            <div class="hero-side-info">
              <span>Submitted</span>
              <strong>{{ formatDate(selectedOrder.created_at) }}</strong>
            </div>
          </section>

          <section class="order-details-grid">
            <article class="detail-field"><span>Full Name</span><strong>{{ selectedOrder.full_name || '—' }}</strong></article>
            <article class="detail-field"><span>Email</span><strong>{{ selectedOrder.email || '—' }}</strong></article>
            <article class="detail-field"><span>Phone</span><strong>{{ selectedOrder.phone || '—' }}</strong></article>
            <article class="detail-field"><span>Order Place Date</span><strong>{{ dateValue(selectedOrder.order_date) }}</strong></article>
            <article class="detail-field"><span>Delivery Date</span><strong>{{ dateValue(selectedOrder.delivery_date) }}</strong></article>
            <article class="detail-field"><span>Sales Rep</span><strong>{{ selectedOrder.sales_rep || '—' }}</strong></article>
            <article class="detail-field"><span>Order #</span><strong class="order-number-value">{{ selectedOrder.order_number || `#${selectedOrder.id}` }}</strong></article>
          </section>

          <section class="team-colors-box">
            <span>Team Colors</span>
            <strong>{{ selectedOrder.team_colors || '—' }}</strong>
          </section>

          <div class="section-divider"></div>

          <section class="file-notes-grid">
            <article v-for="group in fileGroups" :key="group.key" class="document-card">
              <header class="document-card-title">
                <span><i :class="group.icon"></i>{{ group.title }}</span>
                <em>{{ group.files.length }}</em>
              </header>

              <div v-if="group.files.length" class="document-list">
                <div v-for="(file, index) in group.files" :key="index" class="document-file">
                  <a :href="fileUrl(file, group.folder)" target="_blank" rel="noopener noreferrer" class="document-preview">
                    <img
                      v-if="isImage(file)"
                      :src="fileUrl(file, group.folder)"
                      :alt="fileName(file)"
                      @error="hideBrokenImage"
                    />
                    <span v-else class="document-file-icon">
                      <i class="fa-solid fa-file"></i>
                      <strong>{{ extension(file) }}</strong>
                    </span>
                  </a>

                  <div class="document-file-info">
                    <strong>{{ fileName(file) }}</strong>
                    <div>
                      <a :href="fileUrl(file, group.folder)" target="_blank" rel="noopener noreferrer">View</a>
                      <a :href="fileUrl(file, group.folder)" download>Download</a>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="document-empty">
                <i class="fa-regular fa-folder-open"></i>
                <span>No files uploaded</span>
              </div>
            </article>

            <article class="document-card notes-document-card">
              <header class="document-card-title">
                <span><i class="fa-solid fa-note-sticky"></i>Notes</span>
              </header>
              <div v-if="selectedOrder.notes" class="notes-box" v-html="selectedOrder.notes"></div>
              <div v-else class="document-empty">
                <i class="fa-regular fa-note-sticky"></i>
                <span>No notes added</span>
              </div>
            </article>
          </section>

          <footer class="order-actions-footer">
            <div class="footer-order-id">
              <span>Selected Order</span>
              <strong>{{ selectedOrder.order_number || `#${selectedOrder.id}` }}</strong>
            </div>

            <div class="footer-buttons">
              <button type="button" class="download-pdf-btn" @click="downloadPdf">
                <i class="fa-regular fa-file-pdf"></i>
                Download PDF
              </button>
              <button type="button" class="print-order-btn" @click="printOrder">
                <i class="fa-solid fa-print"></i>
                Print
              </button>
            </div>
          </footer>
        </div>
      </section>
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
              .details-grid { margin-top:12px; display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:7px; }
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
                <div class="field"><span>Phone</span><strong>${this.escapeHtml(order.phone || '—')}</strong></div>
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
  }
}
</script>

<style scoped>
*{box-sizing:border-box}.place-orders-page{min-height:100vh;display:grid;grid-template-columns:330px minmax(0,1fr);background:#eceef1;color:#111827}.orders-sidebar{height:100vh;overflow-y:auto;background:#111827;color:#fff;border-right:1px solid #202938}.sidebar-head{min-height:88px;padding:17px 15px;border-bottom:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:11px}.sidebar-title{min-width:0;flex:1}.sidebar-head h2{margin:0;font-size:17px;font-weight:900}.sidebar-head p{margin:3px 0 0;color:#9ca3af;font-size:10px}.back-btn{width:35px;height:35px;flex-shrink:0;border:1px solid rgba(255,255,255,.15);border-radius:9px;background:rgba(255,255,255,.08);color:#fff;cursor:pointer}.count-badge{padding:5px 8px;border-radius:999px;background:#fff;color:#111827;font-size:9px;font-weight:900}.search-box{margin:13px;height:40px;padding:0 11px;border:1px solid rgba(255,255,255,.12);border-radius:9px;background:rgba(255,255,255,.07);display:flex;align-items:center;gap:8px}.search-box input{width:100%;border:0;outline:0;background:transparent;color:#fff;font-size:11px}.tabs{padding:0 13px 11px;display:flex;gap:5px;overflow-x:auto}.tabs button{flex-shrink:0;padding:6px 9px;border:1px solid rgba(255,255,255,.12);border-radius:999px;background:transparent;color:#9ca3af;font-size:9px;font-weight:800;cursor:pointer}.tabs button.active{background:#fff;color:#111827}.sidebar-state{min-height:180px;color:#9ca3af;font-size:11px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px}.order-list{padding:0 8px 18px}.order-row{position:relative;width:100%;margin-bottom:5px;padding:9px;border:1px solid transparent;border-radius:10px;background:transparent;color:#fff;text-align:left;cursor:pointer;display:flex;align-items:center;gap:8px}.order-row:hover,.order-row.active{border-color:rgba(255,255,255,.13);background:rgba(255,255,255,.08)}.order-row.unread{background:rgba(255,255,255,.11)}.unread-dot{position:absolute;top:8px;left:5px;width:6px;height:6px;border-radius:50%;background:#22c55e}.sidebar-thumb{width:43px;height:43px;flex-shrink:0;border-radius:8px;overflow:hidden;background:rgba(255,255,255,.08);color:#9ca3af;display:grid;place-items:center}.sidebar-thumb img{width:100%;height:100%;object-fit:cover}.order-row-body{min-width:0;flex:1}.order-row-body strong,.order-row-body span,.order-row-body small{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.order-row-body strong{display:block;font-size:11px}.order-row-body>span{display:block;margin-top:2px;color:#d1d5db;font-size:9px}.order-row-body>small{display:block;margin-top:2px;color:#9ca3af;font-size:8px}.order-row-meta{display:flex!important;align-items:center;gap:5px}.order-row-meta em{padding:2px 5px;border-radius:999px;font-size:7px;font-style:normal;font-weight:900}.orders-content{min-width:0;height:100vh;padding:20px;overflow-y:auto}.empty-detail{min-height:calc(100vh - 40px);color:#6b7280;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center}.empty-icon{width:70px;height:70px;margin-bottom:13px;border-radius:20px;background:#111827;color:#fff;font-size:25px;display:grid;place-items:center}.empty-detail h3{margin:0;color:#111827;font-size:22px}.empty-detail p{margin:5px 0 0;font-size:11px}.order-view-shell{width:min(1250px,100%);margin:0 auto;overflow:hidden;border:1px solid #dfe2e6;border-radius:14px;background:#fff;box-shadow:0 16px 45px rgba(15,23,42,.08)}.view-black-header{min-height:65px;padding:13px 19px;background:#050505;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:15px}.view-brand{display:flex;align-items:center;gap:10px}.prosix-mark{width:35px;height:35px;border-radius:8px;background:#fff;color:#050505;font-size:17px;font-weight:1000;font-style:italic;display:grid;place-items:center}.view-brand strong,.view-brand small{display:block}.view-brand strong{font-size:12px;letter-spacing:.1em}.view-brand small{margin-top:3px;color:#9ca3af;font-size:7px;letter-spacing:.17em}.view-header-actions{display:flex;align-items:center;gap:8px}.header-status{padding:7px 11px;border-radius:999px;font-size:9px;font-weight:900}.header-icon-btn{width:35px;height:35px;border:1px solid rgba(255,255,255,.2);border-radius:8px;background:rgba(255,255,255,.08);color:#fff;cursor:pointer}.thank-you-strip{padding:13px 20px;border-bottom:1px solid #e5e7eb;background:#f7f7f7;text-align:center}.thank-you-strip h4{margin:0;font-size:12px;font-style:italic;letter-spacing:.08em}.thank-you-strip p{margin:4px 0 0;color:#a0a0a0;font-size:6px;letter-spacing:.35em}.order-view-body{padding:20px}.order-hero-card{min-height:125px;padding:13px;border:1px solid #dfe2e6;border-radius:11px;background:#fafafa;display:flex;align-items:center;gap:16px}.hero-thumbnail{width:145px;height:105px;flex-shrink:0;overflow:hidden;border:1px solid #d7dbe0;border-radius:9px;background:#fff}.hero-thumbnail img{width:100%;height:100%;object-fit:contain}.hero-thumbnail-empty{width:100%;height:100%;color:#9ca3af;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;font-size:9px}.hero-thumbnail-empty i{font-size:20px}.hero-order-info{min-width:0;flex:1}.small-label,.hero-side-info span{color:#8a8f98;font-size:7px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}.hero-order-info h1{margin:5px 0;overflow-wrap:anywhere;font-size:21px}.hero-order-info p{margin:0;color:#6b7280;font-size:9px}.hero-side-info{min-width:110px;padding-left:14px;border-left:1px solid #e0e2e5;text-align:right}.hero-side-info strong{display:block;margin-top:5px;font-size:11px}.order-details-grid{margin-top:16px;display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:9px}.detail-field{min-width:0}.detail-field span,.team-colors-box span{display:block;margin-bottom:5px;color:#7c828c;font-size:7px;font-weight:900;letter-spacing:.06em;text-transform:uppercase}.detail-field strong,.team-colors-box strong{min-height:38px;padding:8px 9px;border:1px solid #d8dce1;border-radius:6px;background:#f7f7f7;color:#20242a;overflow-wrap:anywhere;font-size:9px;display:flex;align-items:center}.order-number-value{font-weight:1000!important}.team-colors-box{margin-top:13px}.section-divider{height:1px;margin:18px 0;background:#e5e7eb}.file-notes-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.document-card{min-width:0;min-height:235px;padding:12px;border:1px solid #dcdfe4;border-radius:10px;background:#fff}.document-card-title{margin-bottom:10px;padding-bottom:9px;border-bottom:1px solid #eceef1;color:#111827;font-size:9px;font-weight:900;display:flex;align-items:center;justify-content:space-between}.document-card-title span{display:flex;align-items:center;gap:6px}.document-card-title em{min-width:20px;height:20px;padding:0 5px;border-radius:999px;background:#111827;color:#fff;font-size:8px;font-style:normal;display:grid;place-items:center}.document-list{max-height:190px;overflow-y:auto;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}.document-file{min-width:0;overflow:hidden;border:1px solid #e1e4e8;border-radius:7px;background:#fff}.document-preview{height:75px;background:#f5f6f8;text-decoration:none;display:block}.document-preview img{width:100%;height:100%;object-fit:contain}.document-file-icon{width:100%;height:100%;color:#6b7280;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px}.document-file-icon i{font-size:18px}.document-file-icon strong{font-size:7px}.document-file-info{padding:5px}.document-file-info>strong{display:block;overflow:hidden;color:#374151;font-size:7px;text-overflow:ellipsis;white-space:nowrap}.document-file-info div{margin-top:5px;display:flex;gap:4px}.document-file-info a{flex:1;padding:4px 3px;border-radius:4px;background:#f1f2f4;color:#111827;font-size:6px;font-weight:900;text-align:center;text-decoration:none}.document-file-info a:last-child{background:#111827;color:#fff}.document-empty{min-height:150px;color:#9ca3af;font-size:9px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px}.document-empty i{font-size:20px}.notes-document-card{display:flex;flex-direction:column}.notes-box{flex:1;min-height:150px;max-height:190px;overflow-y:auto;padding:10px;border:1px dashed #d0d3d8;border-radius:6px;background:#fafafa;color:#4b5563;font-size:9px;line-height:1.7;overflow-wrap:anywhere}.order-actions-footer{margin-top:18px;padding-top:14px;border-top:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;gap:12px}.footer-order-id span,.footer-order-id strong{display:block}.footer-order-id span{color:#9ca3af;font-size:7px;font-weight:900;text-transform:uppercase}.footer-order-id strong{margin-top:3px;font-size:10px}.footer-buttons{display:flex;gap:8px}.download-pdf-btn,.print-order-btn{height:38px;padding:0 14px;border-radius:7px;font-size:9px;font-weight:900;cursor:pointer;display:flex;align-items:center;gap:6px}.download-pdf-btn{border:1px solid #111827;background:#fff;color:#111827}.print-order-btn{border:1px solid #111827;background:#111827;color:#fff}.status-pending{background:#fef3c7;color:#92400e}.status-processing{background:#dbeafe;color:#1d4ed8}.status-completed{background:#dcfce7;color:#166534}.status-cancelled,.status-canceled{background:#fee2e2;color:#991b1b}@media(max-width:1200px){.place-orders-page{grid-template-columns:300px minmax(0,1fr)}.order-details-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.file-notes-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:800px){.place-orders-page{display:block}.orders-sidebar{width:100%;height:auto;max-height:45vh}.orders-content{height:auto;padding:12px}.order-hero-card{align-items:flex-start}.hero-side-info{display:none}.order-details-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.file-notes-grid{grid-template-columns:1fr}}@media(max-width:520px){.order-view-body{padding:12px}.order-hero-card{flex-direction:column}.hero-thumbnail{width:100%;height:180px}.order-details-grid{grid-template-columns:1fr}.order-actions-footer{align-items:stretch;flex-direction:column}.footer-buttons{display:grid;grid-template-columns:1fr 1fr}.download-pdf-btn,.print-order-btn{justify-content:center}}
</style>
