<template>
  <AppLayout>
    <div class="artwork-page">
      <!-- TOP -->
      <div class="page-head">
        <div>
          <p class="eyebrow">PROSIX WEBSITE</p>
          <h1>Artwork Requests</h1>
          <span>Artwork requests received from Prosix.com</span>
        </div>

        <div class="page-head-actions">
          <button
            type="button"
            class="btn btn-dark"
            :disabled="selectedIds.length === 0"
            @click="downloadSelectedFiles"
          >
            <i class="fa-solid fa-download"></i>
            Download Selected Files
          </button>

          <button
            type="button"
            class="btn btn-outline"
            :disabled="selectedIds.length === 0"
            @click="printSelected"
          >
            <i class="fa-solid fa-print"></i>
            Print Selected
          </button>

          <button
            type="button"
            class="btn btn-outline"
            @click="fetchOrders()"
          >
            <i class="fa-solid fa-rotate-right"></i>
            Refresh
          </button>
        </div>
      </div>

      <!-- TOOLBAR -->
      <div class="toolbar">
        <div class="search-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input
            v-model="search"
            type="text"
            placeholder="Search name, email, team or product..."
          />
        </div>

        <div class="toolbar-right">
          <div class="filter-tabs">
            <button
              v-for="tab in tabs"
              :key="tab.key"
              type="button"
              :class="{ active: activeStatus === tab.key }"
              @click="activeStatus = tab.key"
            >
              {{ tab.label }}
              <span>{{ statusCount(tab.key) }}</span>
            </button>
          </div>

          <span v-if="unreadCount > 0" class="new-badge">
            {{ unreadCount }} new
          </span>
        </div>
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <div v-if="loading" class="loading-state">
          <i class="fa-solid fa-spinner fa-spin"></i>
          <span>Loading artwork requests...</span>
        </div>

        <template v-else>
          <div class="table-scroll">
            <table class="artwork-table">
              <thead>
                <tr>
                  <th class="check-col">
                    <input
                      type="checkbox"
                      :checked="allVisibleSelected"
                      :indeterminate.prop="someVisibleSelected"
                      @change="toggleSelectAllVisible"
                    />
                  </th>
                  <th class="id-col">#</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Team</th>
                  <th>Products</th>
                  <th class="qty-col">Qty</th>
                  <th>Images</th>
                  <th>Date</th>
                  <th class="actions-col">Actions</th>
                </tr>
              </thead>

              <tbody v-if="filteredOrders.length">
                <tr
                  v-for="order in filteredOrders"
                  :key="order.id"
                  :class="{ unread: !order.is_read }"
                >
                  <td class="check-col">
                    <input
                      type="checkbox"
                      :value="Number(order.id)"
                      v-model="selectedIds"
                    />
                  </td>

                  <td class="id-col">
                    <span v-if="!order.is_read" class="unread-dot"></span>
                    {{ order.id }}
                  </td>

                  <td>
                    <strong class="main-text">
                      {{ order.full_name || '—' }}
                    </strong>
                  </td>

                  <td>
                    <span class="email-text">
                      {{ order.email || '—' }}
                    </span>
                  </td>

                  <td>
                    <span class="team-text">
                      {{ order.team_name || '—' }}
                    </span>
                  </td>

                  <td>
                    <div class="product-chips">
                      <span
                        v-for="product in normalizedProducts(order)"
                        :key="product"
                        class="product-chip"
                      >
                        {{ product }}
                      </span>
                      <span
                        v-if="normalizedProducts(order).length === 0"
                        class="muted"
                      >
                        —
                      </span>
                    </div>
                  </td>

                  <td class="qty-col">
                    {{ order.quantity ?? '—' }}
                  </td>

                  <td>
                    <div class="images-cell">
                      <span class="image-count">
                        {{ artworkFiles(order).length }} image(s)
                      </span>

                      <img
                        v-if="firstImage(order)"
                        :src="firstImage(order)"
                        :alt="order.full_name || 'Artwork'"
                        @error="hideBrokenImage"
                      />
                    </div>
                  </td>

                  <td>
                    <span class="date-text">
                      {{ formatDate(order.created_at) }}
                    </span>
                  </td>

                  <td class="actions-col">
                    <button
                      type="button"
                      class="view-btn"
                      @click="openRequest(order)"
                    >
                      <i class="fa-regular fa-eye"></i>
                      <span>View</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="filteredOrders.length === 0"
            class="empty-state"
          >
            <i class="fa-regular fa-folder-open"></i>
            <strong>No artwork requests found</strong>
            <span>Try another search or status filter.</span>
          </div>
        </template>

        <div
          v-if="!loading && filteredOrders.length"
          class="table-footer"
        >
          <span>
            Showing {{ filteredOrders.length }} of {{ orders.length }} requests
          </span>

          <span v-if="selectedIds.length">
            {{ selectedIds.length }} selected
          </span>
        </div>
      </div>

      <!-- DETAIL MODAL -->
      <div
        v-if="detailOpen && selectedOrder"
        class="modal-backdrop"
        @click.self="closeDetail"
      >
        <div class="detail-modal">
          <div class="modal-top">
            <div class="modal-brand">
              <div class="brand-mark">P</div>
              <div>
                <strong>PROSIX SPORTS</strong>
                <span>Artwork Request #{{ selectedOrder.id }}</span>
              </div>
            </div>

            <div class="modal-top-actions">
              <button
                type="button"
                class="modal-icon-btn"
                title="Print"
                @click="printOne(selectedOrder)"
              >
                <i class="fa-solid fa-print"></i>
              </button>

              <button
                type="button"
                class="modal-icon-btn"
                title="Download all files"
                @click="downloadOrderFiles(selectedOrder)"
              >
                <i class="fa-solid fa-download"></i>
              </button>

              <button
                type="button"
                class="modal-close"
                @click="closeDetail"
              >
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          </div>

          <div class="modal-scroll">
            <div class="detail-title">
              <div>
                <span>Artwork Request</span>
                <h2>{{ selectedOrder.full_name || 'Customer' }}</h2>
                <p>{{ selectedOrder.email || '—' }}</p>
              </div>

              <span
                class="status-pill"
                :class="statusClass(selectedOrder.status)"
              >
                {{ capitalize(selectedOrder.status || 'pending') }}
              </span>
            </div>

            <div class="detail-table">
              <div class="detail-row">
                <span>Full Name</span>
                <strong>{{ selectedOrder.full_name || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Email</span>
                <strong>{{ selectedOrder.email || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Phone</span>
                <strong>{{ selectedOrder.phone || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Instagram</span>
                <strong>{{ selectedOrder.instagram || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Address</span>
                <strong>{{ selectedOrder.address || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Team / Org</span>
                <strong>{{ selectedOrder.team_name || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Role</span>
                <strong>{{ selectedOrder.role || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Order Quantity</span>
                <strong>{{ selectedOrder.quantity ?? '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Team Color</span>
                <strong>{{ selectedOrder.team_color || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Home / Away</span>
                <strong>{{ selectedOrder.home_away || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Design Style</span>
                <strong>{{ selectedOrder.design_style || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Material</span>
                <strong>{{ selectedOrder.material || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Product(s)</span>
                <div class="detail-products">
                  <span
                    v-for="product in normalizedProducts(selectedOrder)"
                    :key="product"
                  >
                    {{ product }}
                  </span>
                  <strong
                    v-if="normalizedProducts(selectedOrder).length === 0"
                  >
                    —
                  </strong>
                </div>
              </div>

              <div class="detail-row">
                <span>Additional / Mockup Details</span>
                <strong>{{ selectedOrder.additional || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Source</span>
                <strong>{{ selectedOrder.source || '—' }}</strong>
              </div>

              <div class="detail-row">
                <span>Submitted On</span>
                <strong>{{ formatDate(selectedOrder.created_at) }}</strong>
              </div>
            </div>

            <section class="reference-section">
              <div class="reference-head">
                <div>
                  <h3>Uploaded Reference Images / Files</h3>
                  <span>
                    {{ artworkFiles(selectedOrder).length }} file(s)
                  </span>
                </div>

                <button
                  v-if="artworkFiles(selectedOrder).length"
                  type="button"
                  class="small-download-btn"
                  @click="downloadOrderFiles(selectedOrder)"
                >
                  <i class="fa-solid fa-download"></i>
                  Download All
                </button>
              </div>

              <div
                v-if="artworkFiles(selectedOrder).length"
                class="reference-grid"
              >
                <article
                  v-for="(file, index) in artworkFiles(selectedOrder)"
                  :key="file.url || file.filename || index"
                  class="reference-card"
                >
                  <button
                    type="button"
                    class="preview-area"
                    @click="openFile(file)"
                  >
                    <img
                      v-if="isImage(file)"
                      :src="fileUrl(file)"
                      :alt="fileName(file)"
                      @error="hideBrokenImage"
                    />

                    <div v-else class="large-file-icon">
                      <i :class="fileIcon(file)"></i>
                      <span>{{ extension(file) }}</span>
                    </div>
                  </button>

                  <div class="file-meta">
                    <strong>{{ fileName(file) }}</strong>

                    <div class="file-actions">
                      <button
                        type="button"
                        title="View"
                        @click="openFile(file)"
                      >
                        <i class="fa-regular fa-eye"></i>
                      </button>

                      <button
                        type="button"
                        title="Download"
                        @click="downloadFile(file)"
                      >
                        <i class="fa-solid fa-download"></i>
                      </button>
                    </div>
                  </div>
                </article>
              </div>

              <div v-else class="no-files">
                <i class="fa-regular fa-image"></i>
                No reference files uploaded.
              </div>
            </section>
          </div>

          <div class="modal-bottom">
            <button
              type="button"
              class="btn btn-outline"
              @click="closeDetail"
            >
              Close
            </button>
          </div>
        </div>
      </div>

      <!-- IMAGE PREVIEW -->
      <div
        v-if="previewFile"
        class="preview-backdrop"
        @click.self="previewFile = null"
      >
        <div class="preview-modal">
          <div class="preview-head">
            <strong>{{ fileName(previewFile) }}</strong>

            <div>
              <button
                type="button"
                @click="downloadFile(previewFile)"
              >
                <i class="fa-solid fa-download"></i>
                Download
              </button>

              <button
                type="button"
                class="preview-close"
                @click="previewFile = null"
              >
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          </div>

          <div class="preview-body">
            <img
              v-if="isImage(previewFile)"
              :src="fileUrl(previewFile)"
              :alt="fileName(previewFile)"
            />

            <iframe
              v-else-if="isPdf(previewFile)"
              :src="fileUrl(previewFile)"
            ></iframe>

            <div v-else class="unsupported-preview">
              <i :class="fileIcon(previewFile)"></i>
              <h3>{{ fileName(previewFile) }}</h3>
              <p>Preview is not available for this file type.</p>
              <button
                type="button"
                class="btn btn-dark"
                @click="downloadFile(previewFile)"
              >
                Download File
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import axios from 'axios'
import AppLayout from '../layouts/AppLayout.vue'

export default {
  name: 'ArtworkRequestsView',

  components: {
    AppLayout
  },

  data() {
    return {
      loading: false,
      orders: [],
      selectedIds: [],
      selectedOrder: null,
      detailOpen: false,
      previewFile: null,
      search: '',
      activeStatus: 'all',
      syncTimer: null,
      prosixBaseUrl:
        import.meta.env.VITE_PROSIX_URL ||
        'https://prosix.com',

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
      const query = String(this.search || '')
        .trim()
        .toLowerCase()

      return this.orders.filter(order => {
        const status = String(
          order.status || 'pending'
        ).toLowerCase()

        const statusMatch =
          this.activeStatus === 'all' ||
          status === this.activeStatus

        const haystack = [
          order.id,
          order.full_name,
          order.email,
          order.phone,
          order.instagram,
          order.address,
          order.team_name,
          order.role,
          order.quantity,
          order.team_color,
          order.home_away,
          order.design_style,
          order.material,
          order.additional,
          order.source,
          ...this.normalizedProducts(order)
        ]

        const searchMatch =
          !query ||
          haystack.some(value =>
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

    visibleIds() {
      return this.filteredOrders.map(
        order => Number(order.id)
      )
    },

    allVisibleSelected() {
      return (
        this.visibleIds.length > 0 &&
        this.visibleIds.every(id =>
          this.selectedIds.includes(id)
        )
      )
    },

    someVisibleSelected() {
      const selectedVisible =
        this.visibleIds.filter(id =>
          this.selectedIds.includes(id)
        ).length

      return (
        selectedVisible > 0 &&
        selectedVisible < this.visibleIds.length
      )
    }
  },

  async mounted() {
    await this.fetchOrders()

    this.syncTimer = window.setInterval(() => {
      this.fetchOrders(true)
    }, 5000)
  },

  beforeUnmount() {
    if (this.syncTimer) {
      window.clearInterval(this.syncTimer)
    }

    document.body.style.overflow = ''
  },

  methods: {
    headers() {
      return {
        Authorization:
          `Bearer ${localStorage.getItem('token')}`,
        Accept: 'application/json'
      }
    },

    async fetchOrders(silent = false) {
      if (!silent) {
        this.loading = true
      }

      try {
        const response = await axios.get(
          '/api/artwork-requests',
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

        if (this.selectedOrder) {
          const fresh = this.orders.find(
            order =>
              Number(order.id) ===
              Number(this.selectedOrder.id)
          )

          if (fresh) {
            this.selectedOrder = fresh
          }
        }

        this.selectedIds =
          this.selectedIds.filter(id =>
            this.orders.some(
              order => Number(order.id) === Number(id)
            )
          )
      } catch (error) {
        console.error(
          'Artwork requests fetch error:',
          error
        )

        if (!silent) {
          this.orders = []
        }
      } finally {
        if (!silent) {
          this.loading = false
        }
      }
    },

    async openRequest(order) {
      this.selectedOrder = order
      this.detailOpen = true
      document.body.style.overflow = 'hidden'

      if (order.is_read) {
        return
      }

      try {
        await axios.post(
          `/api/artwork-requests/${order.id}/mark-read`,
          {},
          {
            headers: this.headers()
          }
        )

        order.is_read = true

        window.dispatchEvent(
          new CustomEvent(
            'artwork-requests-read-updated'
          )
        )
      } catch (error) {
        console.error(
          'Artwork request mark-read error:',
          error
        )
      }
    },

    closeDetail() {
      this.detailOpen = false
      this.selectedOrder = null
      this.previewFile = null
      document.body.style.overflow = ''
    },

    toggleSelectAllVisible(event) {
      if (event.target.checked) {
        this.selectedIds = [
          ...new Set([
            ...this.selectedIds,
            ...this.visibleIds
          ])
        ]

        return
      }

      const visibleSet =
        new Set(this.visibleIds)

      this.selectedIds =
        this.selectedIds.filter(
          id => !visibleSet.has(id)
        )
    },

    statusCount(status) {
      if (status === 'all') {
        return this.orders.length
      }

      return this.orders.filter(
        order =>
          String(
            order.status || 'pending'
          ).toLowerCase() === status
      ).length
    },

    normalizedProducts(order) {
      const value = order?.products

      if (Array.isArray(value)) {
        return value
          .map(item => {
            if (typeof item === 'string') {
              return item
            }

            return (
              item?.name ||
              item?.title ||
              item?.product_name ||
              ''
            )
          })
          .filter(Boolean)
      }

      if (typeof value === 'string') {
        try {
          const parsed = JSON.parse(value)

          if (Array.isArray(parsed)) {
            return parsed
              .map(item =>
                typeof item === 'string'
                  ? item
                  : item?.name ||
                    item?.title ||
                    item?.product_name ||
                    ''
              )
              .filter(Boolean)
          }
        } catch {
          return value
            .split(',')
            .map(item => item.trim())
            .filter(Boolean)
        }
      }

      return []
    },

    artworkFiles(order) {
      const files =
        order?.artwork_files ??
        order?.mockup_files ??
        order?.artwork_file ??
        []

      if (Array.isArray(files)) {
        return files
      }

      if (typeof files === 'string') {
        try {
          const parsed = JSON.parse(files)
          return Array.isArray(parsed)
            ? parsed
            : []
        } catch {
          return files
            ? [files]
            : []
        }
      }

      return []
    },

    fileName(file) {
      if (typeof file === 'string') {
        return file.split('/').pop()
      }

      return (
        file?.original ||
        file?.original_name ||
        file?.filename ||
        file?.file_name ||
        file?.name ||
        'File'
      )
    },

    fileUrl(file) {
      const raw =
        typeof file === 'string'
          ? file
          : file?.url ||
            file?.path ||
            file?.filename ||
            file?.file_name ||
            file?.name ||
            ''

      if (!raw) {
        return '#'
      }

      if (
        raw.startsWith('http://') ||
        raw.startsWith('https://')
      ) {
        return raw
      }

      if (raw.startsWith('/uploads/')) {
        return `${this.prosixBaseUrl}${raw}`
      }

      if (raw.startsWith('uploads/')) {
        return `${this.prosixBaseUrl}/${raw}`
      }

      return (
        `${this.prosixBaseUrl}` +
        `/uploads/artwork/${raw}`
      )
    },

    isImage(file) {
      const name =
        this.fileName(file).toLowerCase()

      return [
        '.jpg',
        '.jpeg',
        '.png',
        '.gif',
        '.webp',
        '.svg',
        '.bmp'
      ].some(ext => name.endsWith(ext))
    },

    isPdf(file) {
      return this.fileName(file)
        .toLowerCase()
        .endsWith('.pdf')
    },

    extension(file) {
      const parts =
        this.fileName(file).split('.')

      return parts.length > 1
        ? parts.pop().toUpperCase()
        : 'FILE'
    },

    fileIcon(file) {
      const ext =
        this.extension(file).toLowerCase()

      if (ext === 'pdf') {
        return 'fa-solid fa-file-pdf'
      }

      if (['doc', 'docx'].includes(ext)) {
        return 'fa-solid fa-file-word'
      }

      if (['xls', 'xlsx', 'csv'].includes(ext)) {
        return 'fa-solid fa-file-excel'
      }

      if (['zip', 'rar', '7z'].includes(ext)) {
        return 'fa-solid fa-file-zipper'
      }

      return 'fa-solid fa-file'
    },

    firstImage(order) {
      const file =
        this.artworkFiles(order)
          .find(item => this.isImage(item))

      return file
        ? this.fileUrl(file)
        : ''
    },

    hideBrokenImage(event) {
      event.target.style.display = 'none'
    },

    openFile(file) {
      if (
        this.isImage(file) ||
        this.isPdf(file)
      ) {
        this.previewFile = file
        return
      }

      window.open(
        this.fileUrl(file),
        '_blank',
        'noopener'
      )
    },

    downloadFile(file) {
      const url = this.fileUrl(file)

      if (!url || url === '#') {
        return
      }

      const anchor =
        document.createElement('a')

      anchor.href = url
      anchor.target = '_blank'
      anchor.rel = 'noopener'
      anchor.download = this.fileName(file)

      document.body.appendChild(anchor)
      anchor.click()
      anchor.remove()
    },

    async downloadOrderFiles(order) {
      const files =
        this.artworkFiles(order)

      if (!files.length) {
        alert('No files available for this request.')
        return
      }

      for (let index = 0; index < files.length; index++) {
        this.downloadFile(files[index])

        await new Promise(resolve =>
          window.setTimeout(resolve, 350)
        )
      }
    },

    async downloadSelectedFiles() {
      const selected =
        this.orders.filter(order =>
          this.selectedIds.includes(
            Number(order.id)
          )
        )

      const files = selected.flatMap(
        order => this.artworkFiles(order)
      )

      if (!files.length) {
        alert(
          'Selected requests do not have any files.'
        )
        return
      }

      for (let index = 0; index < files.length; index++) {
        this.downloadFile(files[index])

        await new Promise(resolve =>
          window.setTimeout(resolve, 350)
        )
      }
    },

    printOne(order) {
      this.openPrintWindow([order])
    },

    printSelected() {
      const selected =
        this.orders.filter(order =>
          this.selectedIds.includes(
            Number(order.id)
          )
        )

      if (!selected.length) {
        return
      }

      this.openPrintWindow(selected)
    },

    openPrintWindow(requests) {
      const popup = window.open(
        '',
        '_blank',
        'width=1100,height=800'
      )

      if (!popup) {
        alert(
          'Please allow popups to print artwork requests.'
        )
        return
      }

      const rows = requests.map(order => {
        const products =
          this.normalizedProducts(order)
            .join(', ') || '—'

        const files =
          this.artworkFiles(order)

        const fileHtml = files.length
          ? files.map(file => {
              if (this.isImage(file)) {
                return `
                  <div class="print-file">
                    <img src="${this.escapeHtml(this.fileUrl(file))}" />
                    <small>${this.escapeHtml(this.fileName(file))}</small>
                  </div>
                `
              }

              return `
                <div class="print-file print-file-name">
                  ${this.escapeHtml(this.fileName(file))}
                </div>
              `
            }).join('')
          : '<div class="muted">No uploaded files.</div>'

        return `
          <section class="request">
            <div class="request-head">
              <div>
                <small>ARTWORK REQUEST</small>
                <h1>#${this.escapeHtml(String(order.id))} — ${this.escapeHtml(order.full_name || 'Customer')}</h1>
                <p>${this.escapeHtml(order.email || '—')}</p>
              </div>
              <strong>${this.escapeHtml(this.capitalize(order.status || 'pending'))}</strong>
            </div>

            <table>
              ${this.printRow('Full Name', order.full_name)}
              ${this.printRow('Email', order.email)}
              ${this.printRow('Phone', order.phone)}
              ${this.printRow('Instagram', order.instagram)}
              ${this.printRow('Address', order.address)}
              ${this.printRow('Team / Org', order.team_name)}
              ${this.printRow('Role', order.role)}
              ${this.printRow('Order Quantity', order.quantity)}
              ${this.printRow('Team Color', order.team_color)}
              ${this.printRow('Home / Away', order.home_away)}
              ${this.printRow('Design Style', order.design_style)}
              ${this.printRow('Material', order.material)}
              ${this.printRow('Product(s)', products)}
              ${this.printRow('Additional', order.additional)}
              ${this.printRow('Source', order.source)}
              ${this.printRow('Submitted On', this.formatDate(order.created_at))}
            </table>

            <h3>Uploaded Reference Images / Files</h3>
            <div class="files">${fileHtml}</div>
          </section>
        `
      }).join('')

      popup.document.open()
      popup.document.write(`
        <!doctype html>
        <html>
        <head>
          <title>Prosix Artwork Requests</title>
          <style>
            *{box-sizing:border-box}
            body{
              margin:0;
              padding:24px;
              color:#111827;
              font-family:Arial,sans-serif;
              background:#fff;
            }
            .brand{
              margin-bottom:22px;
              padding-bottom:14px;
              border-bottom:3px solid #111827;
              display:flex;
              align-items:center;
              justify-content:space-between;
            }
            .brand h2{margin:0;font-size:22px}
            .brand span{font-size:12px;color:#64748b}
            .request{
              margin-bottom:34px;
              page-break-after:always;
            }
            .request:last-child{page-break-after:auto}
            .request-head{
              padding:16px 18px;
              background:#111827;
              color:#fff;
              display:flex;
              justify-content:space-between;
              align-items:flex-start;
              gap:20px;
            }
            .request-head small{font-weight:700;letter-spacing:.12em}
            .request-head h1{margin:5px 0;font-size:20px}
            .request-head p{margin:0;color:#d1d5db}
            table{
              width:100%;
              border-collapse:collapse;
              margin-top:14px;
            }
            td{
              padding:9px 10px;
              border:1px solid #e5e7eb;
              vertical-align:top;
              font-size:12px;
            }
            td:first-child{
              width:220px;
              background:#f8fafc;
              font-weight:700;
            }
            h3{font-size:13px;margin:18px 0 9px}
            .files{
              display:flex;
              flex-wrap:wrap;
              gap:10px;
            }
            .print-file{
              width:150px;
              border:1px solid #e5e7eb;
              padding:7px;
            }
            .print-file img{
              width:100%;
              height:110px;
              object-fit:contain;
              display:block;
            }
            .print-file small{
              margin-top:5px;
              display:block;
              word-break:break-word;
            }
            .print-file-name{
              min-height:80px;
              display:flex;
              align-items:center;
              justify-content:center;
              text-align:center;
            }
            .muted{color:#64748b;font-size:12px}
            @media print{
              body{padding:0}
            }
          </style>
        </head>
        <body>
          <div class="brand">
            <h2>PROSIX SPORTS</h2>
            <span>Artwork Requests</span>
          </div>
          ${rows}
          <script>
            window.addEventListener('load', function () {
              setTimeout(function () {
                window.print()
              }, 500)
            })
          <\/script>
        </body>
        </html>
      `)
      popup.document.close()
    },

    printRow(label, value) {
      return `
        <tr>
          <td>${this.escapeHtml(label)}</td>
          <td>${this.escapeHtml(
            value === null ||
            value === undefined ||
            value === ''
              ? '—'
              : String(value)
          )}</td>
        </tr>
      `
    },

    escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
    },

    capitalize(value) {
      const text =
        String(value || '')

      return text
        ? text.charAt(0).toUpperCase() +
          text.slice(1)
        : ''
    },

    formatDate(value) {
      if (!value) {
        return '—'
      }

      const date =
        new Date(value)

      if (
        Number.isNaN(
          date.getTime()
        )
      ) {
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

    statusClass(status) {
      return `status-${String(
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

.artwork-page {
  width: 100%;
  min-width: 0;
  padding: 24px;
  color: #101828;
  background: #f6f7fb;
}

/* HEADER */
.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 18px;
}

.page-head h1 {
  margin: 2px 0 4px;
  font-size: 25px;
  font-weight: 900;
  letter-spacing: -.03em;
}

.page-head > div > span {
  color: #667085;
  font-size: 12px;
}

.eyebrow {
  margin: 0;
  color: #667085;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: .14em;
}

.page-head-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.btn {
  min-height: 39px;
  padding: 0 13px;
  border-radius: 7px;
  font-size: 11px;
  font-weight: 850;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
}

.btn:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.btn-dark {
  border: 1px solid #101828;
  background: #101828;
  color: #fff;
}

.btn-outline {
  border: 1px solid #d0d5dd;
  background: #fff;
  color: #101828;
}

/* TOOLBAR */
.toolbar {
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
}

.search-wrap {
  width: min(420px, 100%);
  height: 42px;
  padding: 0 13px;
  border: 1px solid #d0d5dd;
  border-radius: 8px;
  background: #fff;
  display: flex;
  align-items: center;
  gap: 9px;
}

.search-wrap i {
  color: #98a2b3;
}

.search-wrap input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #101828;
  font-size: 12px;
}

.toolbar-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

.filter-tabs {
  display: flex;
  align-items: center;
  gap: 5px;
}

.filter-tabs button {
  min-height: 34px;
  padding: 0 10px;
  border: 1px solid #e4e7ec;
  border-radius: 7px;
  background: #fff;
  color: #667085;
  font-size: 10px;
  font-weight: 850;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.filter-tabs button span {
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  background: #f2f4f7;
  color: #344054;
  font-size: 9px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.filter-tabs button.active {
  border-color: #101828;
  background: #101828;
  color: #fff;
}

.filter-tabs button.active span {
  background: #fff;
  color: #101828;
}

.new-badge {
  padding: 6px 9px;
  border-radius: 999px;
  background: #ecfdf3;
  color: #027a48;
  font-size: 10px;
  font-weight: 900;
}

/* TABLE */
.table-card {
  border: 1px solid #d0d5dd;
  background: #fff;
  overflow: hidden;
}

.table-scroll {
  width: 100%;
  overflow-x: auto;
}

.artwork-table {
  width: 100%;
  min-width: 1180px;
  border-collapse: collapse;
}

.artwork-table th {
  height: 48px;
  padding: 0 10px;
  border-right: 1px solid #475467;
  background: #181d24;
  color: #fff;
  text-align: left;
  font-size: 10px;
  font-weight: 850;
  white-space: nowrap;
}

.artwork-table td {
  padding: 12px 10px;
  border-right: 1px solid #eaecf0;
  border-bottom: 1px solid #d0d5dd;
  vertical-align: top;
  font-size: 11px;
}

.artwork-table tr.unread td {
  background: #fffdf7;
}

.artwork-table tr:hover td {
  background: #f9fafb;
}

.check-col {
  width: 42px;
  text-align: center !important;
}

.id-col {
  width: 55px;
  white-space: nowrap;
}

.qty-col {
  width: 65px;
  text-align: center !important;
}

.actions-col {
  width: 90px;
  text-align: center !important;
}

.main-text {
  color: #101828;
  font-size: 12px;
  font-weight: 850;
}

.email-text,
.team-text,
.date-text {
  color: #344054;
  line-height: 1.45;
}

.unread-dot {
  width: 6px;
  height: 6px;
  margin-right: 5px;
  border-radius: 50%;
  background: #12b76a;
  display: inline-block;
}

.product-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.product-chip {
  padding: 5px 7px;
  border-radius: 5px;
  background: #181d24;
  color: #fff;
  font-size: 9px;
  font-weight: 850;
  white-space: nowrap;
}

.images-cell {
  min-width: 92px;
}

.image-count {
  margin-bottom: 6px;
  padding: 4px 7px;
  border-radius: 6px;
  background: #181d24;
  color: #fff;
  font-size: 9px;
  font-weight: 850;
  display: inline-flex;
}

.images-cell img {
  width: 52px;
  height: 45px;
  border: 1px solid #eaecf0;
  border-radius: 5px;
  object-fit: contain;
  background: #fff;
  display: block;
}

.view-btn {
  min-width: 56px;
  padding: 8px 7px;
  border: 0;
  border-radius: 5px;
  background: #181d24;
  color: #fff;
  cursor: pointer;
  font-size: 10px;
  font-weight: 800;
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.view-btn i {
  font-size: 14px;
}

.table-footer {
  min-height: 44px;
  padding: 0 13px;
  border-top: 1px solid #eaecf0;
  color: #667085;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.loading-state,
.empty-state {
  min-height: 270px;
  color: #98a2b3;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 9px;
}

.empty-state i {
  font-size: 28px;
}

.empty-state strong {
  color: #344054;
}

.empty-state span {
  font-size: 11px;
}

.muted {
  color: #98a2b3;
}

/* DETAIL MODAL */
.modal-backdrop,
.preview-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9999;
  padding: 24px;
  background: rgba(16, 24, 40, .72);
  display: flex;
  align-items: center;
  justify-content: center;
}

.detail-modal {
  width: min(900px, 96vw);
  max-height: 92vh;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 24px 80px rgba(0, 0, 0, .3);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-top {
  min-height: 72px;
  padding: 0 20px;
  border-bottom: 1px solid #eaecf0;
  background: #101828;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-brand {
  display: flex;
  align-items: center;
  gap: 11px;
}

.brand-mark {
  width: 42px;
  height: 42px;
  border-radius: 8px;
  background: #fff;
  color: #101828;
  font-size: 26px;
  font-style: italic;
  font-weight: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-brand strong {
  display: block;
  font-size: 13px;
}

.modal-brand span {
  margin-top: 2px;
  color: #98a2b3;
  font-size: 10px;
  display: block;
}

.modal-top-actions {
  display: flex;
  gap: 6px;
}

.modal-icon-btn,
.modal-close {
  width: 36px;
  height: 36px;
  border: 1px solid #344054;
  border-radius: 7px;
  background: transparent;
  color: #fff;
  cursor: pointer;
}

.modal-close {
  border-color: #f04438;
  background: #f04438;
}

.modal-scroll {
  min-height: 0;
  padding: 18px;
  overflow-y: auto;
}

.detail-title {
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid #eaecf0;
  border-radius: 8px;
  background: #f9fafb;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.detail-title > div > span {
  color: #667085;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: .12em;
}

.detail-title h2 {
  margin: 4px 0 2px;
  font-size: 19px;
}

.detail-title p {
  margin: 0;
  color: #667085;
  font-size: 11px;
}

.status-pill {
  padding: 7px 10px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 900;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-processing {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-completed {
  background: #dcfce7;
  color: #166534;
}

.status-cancelled,
.status-canceled {
  background: #fee2e2;
  color: #991b1b;
}

.detail-table {
  border: 1px solid #eaecf0;
  border-radius: 8px;
  overflow: hidden;
}

.detail-row {
  min-height: 42px;
  border-bottom: 1px solid #eaecf0;
  display: grid;
  grid-template-columns: 230px minmax(0, 1fr);
}

.detail-row:last-child {
  border-bottom: 0;
}

.detail-row > span {
  padding: 11px 13px;
  background: #f9fafb;
  color: #475467;
  font-size: 10px;
  font-weight: 850;
}

.detail-row > strong,
.detail-row > .detail-products {
  padding: 11px 13px;
  color: #101828;
  font-size: 11px;
  font-weight: 700;
  overflow-wrap: anywhere;
}

.detail-products {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.detail-products span {
  padding: 4px 7px;
  border-radius: 5px;
  background: #181d24;
  color: #fff;
  font-size: 9px;
  font-weight: 850;
}

.reference-section {
  margin-top: 18px;
}

.reference-head {
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.reference-head h3 {
  margin: 0;
  font-size: 13px;
}

.reference-head span {
  margin-top: 2px;
  color: #667085;
  font-size: 10px;
  display: block;
}

.small-download-btn {
  min-height: 34px;
  padding: 0 10px;
  border: 1px solid #101828;
  border-radius: 6px;
  background: #101828;
  color: #fff;
  font-size: 10px;
  font-weight: 850;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.reference-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.reference-card {
  min-width: 0;
  border: 1px solid #eaecf0;
  border-radius: 8px;
  background: #fff;
  overflow: hidden;
}

.preview-area {
  width: 100%;
  height: 130px;
  padding: 8px;
  border: 0;
  background: #f9fafb;
  cursor: pointer;
}

.preview-area img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.large-file-icon {
  width: 100%;
  height: 100%;
  color: #667085;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.large-file-icon i {
  font-size: 26px;
}

.file-meta {
  padding: 8px;
  display: flex;
  align-items: center;
  gap: 7px;
}

.file-meta strong {
  min-width: 0;
  flex: 1;
  overflow: hidden;
  color: #344054;
  font-size: 9px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-actions {
  display: flex;
  gap: 4px;
}

.file-actions button {
  width: 27px;
  height: 27px;
  border: 1px solid #d0d5dd;
  border-radius: 5px;
  background: #fff;
  color: #344054;
  cursor: pointer;
}

.no-files {
  min-height: 110px;
  border: 1px dashed #d0d5dd;
  border-radius: 8px;
  color: #98a2b3;
  font-size: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
}

.modal-bottom {
  min-height: 58px;
  padding: 0 18px;
  border-top: 1px solid #eaecf0;
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

/* PREVIEW */
.preview-modal {
  width: min(1100px, 96vw);
  height: min(820px, 92vh);
  border-radius: 10px;
  background: #fff;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.preview-head {
  min-height: 58px;
  padding: 0 15px;
  border-bottom: 1px solid #eaecf0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.preview-head > strong {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.preview-head > div {
  display: flex;
  gap: 6px;
}

.preview-head button {
  min-height: 34px;
  padding: 0 10px;
  border: 1px solid #101828;
  border-radius: 6px;
  background: #101828;
  color: #fff;
  cursor: pointer;
}

.preview-head .preview-close {
  width: 34px;
  padding: 0;
  border-color: #f04438;
  background: #f04438;
}

.preview-body {
  min-height: 0;
  flex: 1;
  padding: 15px;
  background: #f2f4f7;
}

.preview-body img,
.preview-body iframe {
  width: 100%;
  height: 100%;
  border: 0;
  object-fit: contain;
  background: #fff;
}

.unsupported-preview {
  width: 100%;
  height: 100%;
  color: #667085;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.unsupported-preview > i {
  font-size: 52px;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
  .page-head,
  .toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .page-head-actions,
  .toolbar-right {
    justify-content: flex-start;
  }

  .reference-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .artwork-page {
    padding: 14px;
  }

  .page-head-actions,
  .toolbar-right,
  .filter-tabs {
    width: 100%;
    overflow-x: auto;
    flex-wrap: nowrap;
  }

  .page-head-actions .btn {
    flex-shrink: 0;
  }

  .detail-modal {
    width: 100%;
    max-height: 96vh;
  }

  .modal-backdrop,
  .preview-backdrop {
    padding: 8px;
  }

  .detail-row {
    grid-template-columns: 1fr;
  }

  .detail-row > span {
    padding-bottom: 4px;
  }

  .detail-row > strong,
  .detail-row > .detail-products {
    padding-top: 4px;
  }

  .reference-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 480px) {
  .reference-grid {
    grid-template-columns: 1fr;
  }

  .detail-title {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
