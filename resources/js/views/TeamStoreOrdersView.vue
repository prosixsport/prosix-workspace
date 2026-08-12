<template>
  <AppLayout>
    <div class="teamstore-page">
    <main class="main-content">
      <PageHeader
        title="TeamStore Orders"
        subtitle="Category-wise orders received from TeamStore"
        :user="currentUser"
        :photo="currentUser?.profile_photo_url"
        @profile="openProfile"
      />

      <section class="workspace">
        <!-- =========================
             CATEGORIES
        ========================== -->
        <section class="card category-section">
          <div class="section-heading">
            <div>
              <h2>Order Categories</h2>
              <p>
                Select a category to view its orders and status summary.
              </p>
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
              @click="selectCategory(category.key)"
            >
              <span class="category-icon">
                <img
                  v-if="category.image"
                  :src="category.image"
                  :alt="category.label"
                  @error="hideBrokenImage"
                />

                <i v-else :class="category.icon"></i>
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

        <!-- =========================
             STATUS SUMMARY
        ========================== -->
        <section class="status-section">
          <button
            v-for="tab in statusTabsWithCustom"
            :key="tab.key"
            type="button"
            class="status-summary"
            :class="{ active: activeStatus === tab.key }"
            @click="activeStatus = tab.key"
          >
            <span
              class="summary-dot"
              :style="statusDotStyle(tab.key)"
            ></span>

            <span>{{ tab.label }}</span>

            <strong>{{ statusCount(tab.key) }}</strong>
          </button>
        </section>

        <!-- =========================
             ORDER TABLE
        ========================== -->
        <section class="card orders-panel">
          <div class="panel-header">
            <div>
              <h2>{{ activeCategoryLabel }}</h2>
              <p>
                {{ filteredOrders.length }}
                {{ filteredOrders.length === 1 ? 'order' : 'orders' }}
              </p>
            </div>

            <div class="bulk-actions">
              <span v-if="selectedIds.length" class="selected-label">
                {{ selectedIds.length }} selected
              </span>

              <button
                type="button"
                class="secondary-button"
                @click="toggleVisibleSelection"
              >
                <i class="fa-regular fa-square-check"></i>
                {{ allVisibleSelected ? 'Clear Visible' : 'Select Visible' }}
              </button>

              <button
                type="button"
                class="primary-button"
                :disabled="selectedIds.length === 0"
                @click="printSelectedOrders"
              >
                <i class="fa-solid fa-print"></i>
                Print Selected
              </button>
            </div>
          </div>

          <div v-if="loading" class="empty-state">
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

          <div v-else class="table-wrap">
            <table class="orders-table">
              <thead>
                <tr>
                  <th class="check-column">
                    <input
                      type="checkbox"
                      :checked="allVisibleSelected"
                      @change="toggleVisibleSelection"
                    />
                  </th>

                  <th>Order</th>
                  <th>Category</th>
                  <th>Items</th>
                  <th>Customer</th>
                  <th>Contact</th>
                  <th>Status</th>
                  <th>Remark</th>
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
                  <td class="check-column">
                    <input
                      type="checkbox"
                      :checked="isSelected(order.id)"
                      @change="toggleOrder(order.id)"
                    />
                  </td>

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
                        <small>ID #{{ order.id }}</small>
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

                      <em v-if="orderCategories(order).length > 2">
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

                        <i v-else class="fa-solid fa-shirt"></i>
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

                  <!-- =========================
                       PROFESSIONAL STATUS
                  ========================== -->
                  <td class="status-column">
                    <div class="status-control">
                      <button
                        type="button"
                        class="status-trigger"
                        :class="statusClass(order.status)"
                        :style="statusPillStyle(order.status)"
                        :disabled="isSaving(order.id)"
                        @click.stop="toggleStatusMenu(order)"
                      >
                        <span class="status-trigger-dot"></span>

                        <span class="status-trigger-label">
                          {{ statusLabel(order.status || 'new') }}
                        </span>

                        <i class="fa-solid fa-chevron-down"></i>
                      </button>

                      <div
                        v-if="order._status_menu"
                        class="status-menu"
                        @click.stop
                      >
                        <div class="status-menu-title">
                          Change Status
                        </div>

                        <button
                          v-for="option in statusOptions"
                          :key="option.value"
                          type="button"
                          class="status-option"
                          :class="{
                            selected:
                              String(order.status || '').toLowerCase() ===
                              option.value
                          }"
                          @click="selectStandardStatus(order, option.value)"
                        >
                          <span
                            class="option-dot"
                            :style="{ backgroundColor: option.color }"
                          ></span>

                          {{ option.label }}

                          <i
                            v-if="
                              String(order.status || '').toLowerCase() ===
                              option.value
                            "
                            class="fa-solid fa-check"
                          ></i>
                        </button>

                        <div class="custom-divider"></div>

                        <button
                          type="button"
                          class="custom-status-toggle"
                          @click="order._custom_open = !order._custom_open"
                        >
                          <i class="fa-solid fa-pen"></i>
                          Custom Status
                        </button>

                        <div
                          v-if="order._custom_open"
                          class="custom-status-box"
                        >
                          <input
                            v-model="order._custom_status"
                            type="text"
                            placeholder="e.g. Printing, Hold, Packing"
                            @keyup.enter="saveCustomStatus(order)"
                          />

                          <button
                            type="button"
                            :disabled="
                              isSaving(order.id) ||
                              !String(order._custom_status || '').trim()
                            "
                            @click="saveCustomStatus(order)"
                          >
                            Save
                          </button>
                        </div>
                      </div>

                      <small
                        v-if="isSaving(order.id)"
                        class="saving-text"
                      >
                        Saving...
                      </small>
                    </div>
                  </td>

                  <!-- =========================
                       REMARK — LINE AUTO SAVE
                  ========================== -->
                  <td class="remark-column">
                    <div class="line-editor">
                      <input
                        v-model="order._remark"
                        type="text"
                        class="line-input"
                        placeholder="Write remark..."
                        :disabled="isSaving(order.id)"
                        @input="queueRemarkSave(order)"
                        @blur="saveRemarkNow(order)"
                        @keyup.enter="$event.target.blur()"
                      />

                      <span
                        class="autosave-state"
                        :class="{ saved: order._remark_saved }"
                      >
                        {{
                          isSaving(order.id)
                            ? 'Saving...'
                            : order._remark_saved
                              ? 'Saved'
                              : ''
                        }}
                      </span>
                    </div>
                  </td>

                  <td>
                    <div class="shipping-cell">
                      <strong>{{ order.shipping_city || '—' }}</strong>
                      <small>{{ order.shipping_province || '' }}</small>
                    </div>
                  </td>

                  <!-- =========================
                       TRACKING — WIDER FIELD
                  ========================== -->
                  <td class="tracking-column">
                    <div class="tracking-editor">
                      <div class="tracking-input-wrap">
                        <i class="fa-solid fa-barcode"></i>

                        <input
                          v-model="order._tracking_number"
                          type="text"
                          placeholder="Enter tracking number"
                          :disabled="isSaving(order.id)"
                          @input="queueTrackingSave(order)"
                          @blur="saveTrackingNow(order)"
                          @keyup.enter="$event.target.blur()"
                        />
                      </div>

                      <div class="tracking-meta">
                        <small>
                          {{ order.courier_name || 'No courier selected' }}
                        </small>

                        <span
                          v-if="
                            order._tracking_saved &&
                            !isSaving(order.id)
                          "
                        >
                          Saved
                        </span>
                      </div>
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
      </section>
    </main>

    <!-- =========================
         VIEW ORDER MODAL
    ========================== -->
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
              :style="statusPillStyle(selectedOrder.status)"
            >
              {{ statusLabel(selectedOrder.status || 'new') }}
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

            <article class="detail-card full-card">
              <h3>
                <i class="fa-solid fa-sliders"></i>
                Production Controls
              </h3>

              <div class="modal-control-grid">
                <div class="modal-control">
                  <label>Status</label>

                  <div class="status-control modal-status-control">
                    <button
                      type="button"
                      class="status-trigger modal-status-trigger"
                      :class="statusClass(selectedOrder.status)"
                      :style="statusPillStyle(selectedOrder.status)"
                      @click.stop="toggleStatusMenu(selectedOrder)"
                    >
                      <span class="status-trigger-dot"></span>
                      {{ statusLabel(selectedOrder.status || 'new') }}
                      <i class="fa-solid fa-chevron-down"></i>
                    </button>

                    <div
                      v-if="selectedOrder._status_menu"
                      class="status-menu modal-status-menu"
                      @click.stop
                    >
                      <button
                        v-for="option in statusOptions"
                        :key="option.value"
                        type="button"
                        class="status-option"
                        @click="
                          selectStandardStatus(
                            selectedOrder,
                            option.value
                          )
                        "
                      >
                        <span
                          class="option-dot"
                          :style="{ backgroundColor: option.color }"
                        ></span>
                        {{ option.label }}
                      </button>

                      <div class="custom-divider"></div>

                      <div class="custom-status-box always-open">
                        <input
                          v-model="selectedOrder._custom_status"
                          type="text"
                          placeholder="Custom status..."
                          @keyup.enter="saveCustomStatus(selectedOrder)"
                        />

                        <button
                          type="button"
                          @click="saveCustomStatus(selectedOrder)"
                        >
                          Save
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="modal-control">
                  <label>Tracking Number</label>

                  <div class="modal-field-input">
                    <i class="fa-solid fa-barcode"></i>

                    <input
                      v-model="selectedOrder._tracking_number"
                      type="text"
                      placeholder="Enter tracking number"
                      @input="queueTrackingSave(selectedOrder)"
                      @blur="saveTrackingNow(selectedOrder)"
                      @keyup.enter="$event.target.blur()"
                    />
                  </div>
                </div>

                <div class="modal-control full-row">
                  <label>Remark</label>

                  <div class="modal-line-editor">
                    <input
                      v-model="selectedOrder._remark"
                      type="text"
                      placeholder="Type internal remark..."
                      @input="queueRemarkSave(selectedOrder)"
                      @blur="saveRemarkNow(selectedOrder)"
                      @keyup.enter="$event.target.blur()"
                    />

                    <small>
                      {{
                        isSaving(selectedOrder.id)
                          ? 'Saving...'
                          : selectedOrder._remark_saved
                            ? 'Saved'
                            : 'Auto-save'
                      }}
                    </small>
                  </div>
                </div>
              </div>
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

                  <i v-else class="fa-solid fa-shirt"></i>
                </div>

                <div class="modal-item-info">
                  <span>{{ itemCategory(item) }}</span>
                  <h4>{{ itemName(item) }}</h4>

                  <div class="item-meta">
                    <em>
                      Size:
                      <strong>
                        {{ item.size || item.selected_size || '—' }}
                      </strong>
                    </em>

                    <em>
                      Qty:
                      <strong>
                        {{ item.quantity || item.qty || 1 }}
                      </strong>
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

            <div v-else class="no-items">
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

      <!-- =========================
           STATUS MANAGER
      ========================== -->
      <div
        v-if="statusManagerOpen"
        class="status-manager-overlay"
        @click.self="closeStatusManager"
      >
        <section class="status-manager-modal">
          <header class="status-manager-header">
            <div>
              <span class="manager-eyebrow">TEAMSTORE SETTINGS</span>
              <h2>Manage Order Statuses</h2>
              <p>
                Add custom statuses, edit their display names and choose colors.
              </p>
            </div>

            <button
              type="button"
              class="manager-close"
              @click="closeStatusManager"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </header>

          <div class="status-manager-body">
            <section class="add-status-card">
              <div class="manager-section-title">
                <div>
                  <h3>Add New Status</h3>
                  <p>
                    New status will stay in the dropdown on this browser.
                  </p>
                </div>
              </div>

              <div class="add-status-form">
                <div class="manager-field manager-field-grow">
                  <label>Status Name</label>
                  <input
                    v-model="newStatusName"
                    type="text"
                    placeholder="e.g. Printing, QC, Packing"
                    @keyup.enter="addCustomStatusDefinition"
                  />
                </div>

                <div class="manager-field color-field">
                  <label>Color</label>

                  <div class="color-picker-box">
                    <input
                      v-model="newStatusColor"
                      type="color"
                    />
                    <span>{{ newStatusColor }}</span>
                  </div>
                </div>

                <button
                  type="button"
                  class="add-status-button"
                  :disabled="!String(newStatusName || '').trim()"
                  @click="addCustomStatusDefinition"
                >
                  <i class="fa-solid fa-plus"></i>
                  Add Status
                </button>
              </div>
            </section>

            <section class="status-list-card">
              <div class="manager-section-title">
                <div>
                  <h3>Available Statuses</h3>
                  <p>
                    Edit name or color, then click Save.
                  </p>
                </div>

                <span class="status-total">
                  {{ statusOptions.length }} statuses
                </span>
              </div>

              <div class="status-manager-list">
                <article
                  v-for="option in statusOptions"
                  :key="option.value"
                  class="status-manager-row"
                >
                  <span
                    class="manager-color-preview"
                    :style="{ backgroundColor: option.color }"
                  ></span>

                  <div class="manager-status-main">
                    <label>Display Name</label>
                    <input
                      v-model="option.label"
                      type="text"
                    />
                    <small>
                      Value: {{ option.value }}
                    </small>
                  </div>

                  <div class="manager-color-control">
                    <label>Color</label>

                    <input
                      v-model="option.color"
                      type="color"
                    />
                  </div>

                  <div class="manager-preview">
                    <label>Preview</label>

                    <span
                      class="manager-preview-pill"
                      :style="statusPillStyleFromOption(option)"
                    >
                      <i
                        :style="{ backgroundColor: option.color }"
                      ></i>
                      {{ option.label }}
                    </span>
                  </div>

                  <div class="manager-row-actions">
                    <button
                      type="button"
                      class="save-status-setting"
                      @click="saveStatusDefinitions"
                    >
                      <i class="fa-solid fa-check"></i>
                      Save
                    </button>

                    <button
                      v-if="option.custom"
                      type="button"
                      class="delete-status-setting"
                      @click="deleteCustomStatusDefinition(option)"
                    >
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </div>
                </article>
              </div>
            </section>

            <div class="manager-footer-note">
              <i class="fa-solid fa-circle-info"></i>
              Status name/color settings are saved in this browser. The selected
              order status itself still saves to Prosix through your existing API.
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
  name: 'TeamStoreOrdersView',

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
      activeCategory: 'all',
      activeStatus: 'all',
      savingOrderIds: [],
      remarkSaveTimers: {},
      trackingSaveTimers: {},
      mobileSidebarOpen: false,

      statusManagerOpen: false,
      newStatusName: '',
      newStatusColor: '#7c3aed',
      customStatuses: [],

      standardStatusOptions: [
        { value: 'new', label: 'New', color: '#8b5cf6', custom: false },
        { value: 'confirmed', label: 'Confirmed', color: '#4f7df3', custom: false },
        { value: 'production', label: 'Production', color: '#f59e0b', custom: false },
        { value: 'shipped', label: 'Shipped', color: '#0ea5e9', custom: false },
        { value: 'delivered', label: 'Delivered', color: '#10b981', custom: false },
        { value: 'cancelled', label: 'Cancelled', color: '#ef4444', custom: false }
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

    currentUserName() {
      return (
        this.currentUser.name ||
        this.currentUser.full_name ||
        'Prosix User'
      )
    },

    currentUserRole() {
      return this.formatLabel(
        this.currentUser.role || 'member'
      )
    },

    currentUserAvatar() {
      return (
        this.currentUser.profile_photo_url ||
        this.currentUser.avatar ||
        ''
      )
    },

    currentUserInitial() {
      return String(this.currentUserName)
        .trim()
        .charAt(0)
        .toUpperCase()
    },

    isSuperAdmin() {
      return this.currentUser?.role === 'super_admin'
    },

    unreadCount() {
      return this.orders.filter(order => !order.is_read).length
    },

    allCategories() {
      const map = new Map()

      this.orders.forEach(order => {
        this.normalizedItems(order).forEach(item => {
          const category = this.itemCategoryData(item)

          const key = category.id
            ? `category-${category.id}`
            : this.slug(category.name)

          if (!map.has(key)) {
            map.set(key, {
              key,
              id: category.id,
              label: category.name,
              image:
                category.image ||
                this.itemImage(item),
              icon:
                this.categoryIcon(category.name),
              orderIds: new Set()
            })
          }

          map.get(key).orderIds.add(order.id)
        })
      })

      return Array.from(map.values())
        .map(category => ({
          ...category,
          count: category.orderIds.size
        }))
        .filter(
          category =>
            category.label !== 'Other' ||
            category.count > 0
        )
        .sort((a, b) =>
          a.label.localeCompare(b.label)
        )
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
          category =>
            category.key === this.activeCategory
        )?.label || 'All Categories'
      )
    },

    statusOptions() {
      return [
        ...this.standardStatusOptions,
        ...this.customStatuses
      ]
    },

    statusTabsWithCustom() {
      const tabs = [
        {
          key: 'all',
          label: 'All',
          color: '#667085'
        },
        ...this.statusOptions.map(option => ({
          key: option.value,
          label: option.label,
          color: option.color
        }))
      ]

      const known = new Set(
        tabs.map(tab =>
          String(tab.key).toLowerCase()
        )
      )

      const unknownFromOrders = this.orders
        .map(order =>
          String(order.status || '').trim()
        )
        .filter(Boolean)
        .filter(status =>
          !known.has(status.toLowerCase())
        )
        .filter(
          (status, index, array) =>
            array.findIndex(
              value =>
                value.toLowerCase() ===
                status.toLowerCase()
            ) === index
        )
        .map(status => ({
          key: status.toLowerCase(),
          label: this.formatLabel(status),
          color: '#667085'
        }))

      return [...tabs, ...unknownFromOrders]
    },

    selectedOrders() {
      return this.orders.filter(order =>
        this.selectedIds.includes(
          Number(order.id)
        )
      )
    },

    allVisibleSelected() {
      return (
        this.filteredOrders.length > 0 &&
        this.filteredOrders.every(order =>
          this.selectedIds.includes(
            Number(order.id)
          )
        )
      )
    },

    filteredOrders() {
      const query = String(this.search || '')
        .trim()
        .toLowerCase()

      return this.orders.filter(order => {
        const status =
          String(order.status || 'new')
            .toLowerCase()

        const statusMatch =
          this.activeStatus === 'all' ||
          status === this.activeStatus

        const categories =
          this.orderCategories(order)

        const categoryMatch =
          this.activeCategory === 'all' ||
          this.normalizedItems(order).some(
            item => {
              const category =
                this.itemCategoryData(item)

              const key = category.id
                ? `category-${category.id}`
                : this.slug(category.name)

              return key === this.activeCategory
            }
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
            order.remark,
            ...categories
          ].some(value =>
            String(value || '')
              .toLowerCase()
              .includes(query)
          )

        return (
          statusMatch &&
          categoryMatch &&
          searchMatch
        )
      })
    }
  },

  async mounted() {
    this.loadStatusDefinitions()

    document.addEventListener(
      'click',
      this.closeAllStatusMenus
    )

    await this.fetchOrders()
  },

  beforeUnmount() {
    document.removeEventListener(
      'click',
      this.closeAllStatusMenus
    )

    document.body.style.overflow = ''

    Object.values(
      this.remarkSaveTimers
    ).forEach(timer => clearTimeout(timer))

    Object.values(
      this.trackingSaveTimers
    ).forEach(timer => clearTimeout(timer))
  },

  methods: {
    openProfile() {
      this.$router.push('/profile')
    },

    headers() {
      return {
        Authorization:
          `Bearer ${localStorage.getItem('token')}`,
        Accept: 'application/json'
      }
    },

    loadStatusDefinitions() {
      try {
        const raw = localStorage.getItem(
          'teamstore_status_definitions_v1'
        )

        if (!raw) {
          return
        }

        const saved = JSON.parse(raw)

        if (Array.isArray(saved?.standard)) {
          this.standardStatusOptions =
            this.standardStatusOptions.map(defaultOption => {
              const stored = saved.standard.find(
                item => item.value === defaultOption.value
              )

              return stored
                ? {
                    ...defaultOption,
                    label:
                      String(stored.label || defaultOption.label).trim() ||
                      defaultOption.label,
                    color:
                      stored.color ||
                      defaultOption.color
                  }
                : defaultOption
            })
        }

        if (Array.isArray(saved?.custom)) {
          this.customStatuses = saved.custom
            .filter(item => item?.value && item?.label)
            .map(item => ({
              value: String(item.value),
              label: String(item.label),
              color: item.color || '#667085',
              custom: true
            }))
        }
      } catch (error) {
        console.warn(
          'Could not load TeamStore status definitions:',
          error
        )
      }
    },

    saveStatusDefinitions() {
      this.standardStatusOptions =
        this.standardStatusOptions.map(option => ({
          ...option,
          label:
            String(option.label || '').trim() ||
            this.formatLabel(option.value),
          color: option.color || '#667085',
          custom: false
        }))

      this.customStatuses =
        this.customStatuses.map(option => ({
          ...option,
          label:
            String(option.label || '').trim() ||
            this.formatLabel(option.value),
          color: option.color || '#667085',
          custom: true
        }))

      localStorage.setItem(
        'teamstore_status_definitions_v1',
        JSON.stringify({
          standard: this.standardStatusOptions,
          custom: this.customStatuses
        })
      )
    },

    addCustomStatusDefinition() {
      const label =
        String(this.newStatusName || '').trim()

      if (!label) {
        return
      }

      let value = this.slug(label)

      if (!value) {
        return
      }

      const existingValues = new Set(
        this.statusOptions.map(option =>
          String(option.value).toLowerCase()
        )
      )

      if (existingValues.has(value.toLowerCase())) {
        alert('This status already exists.')
        return
      }

      this.customStatuses.push({
        value,
        label,
        color: this.newStatusColor || '#7c3aed',
        custom: true
      })

      this.newStatusName = ''
      this.newStatusColor = '#7c3aed'

      this.saveStatusDefinitions()
    },

    deleteCustomStatusDefinition(option) {
      if (!option?.custom) {
        return
      }

      const used = this.orders.some(
        order =>
          String(order.status || '').toLowerCase() ===
          String(option.value || '').toLowerCase()
      )

      if (used) {
        const confirmed = window.confirm(
          `"${option.label}" is currently used by one or more orders. ` +
          'Delete it from the dropdown anyway? Existing orders will keep their current status.'
        )

        if (!confirmed) {
          return
        }
      }

      this.customStatuses =
        this.customStatuses.filter(
          item => item.value !== option.value
        )

      this.saveStatusDefinitions()
    },

    closeStatusManager() {
      this.saveStatusDefinitions()
      this.statusManagerOpen = false
    },

    statusOption(status) {
      const value =
        String(status || 'new').toLowerCase()

      return this.statusOptions.find(
        option =>
          String(option.value).toLowerCase() === value
      ) || null
    },

    statusLabel(status) {
      return (
        this.statusOption(status)?.label ||
        this.formatLabel(status || 'new')
      )
    },

    hexToRgba(hex, alpha = 0.12) {
      const clean =
        String(hex || '#667085')
          .replace('#', '')
          .trim()

      const normalized =
        clean.length === 3
          ? clean
              .split('')
              .map(char => char + char)
              .join('')
          : clean.padEnd(6, '0').slice(0, 6)

      const number =
        Number.parseInt(normalized, 16)

      const r = (number >> 16) & 255
      const g = (number >> 8) & 255
      const b = number & 255

      return `rgba(${r}, ${g}, ${b}, ${alpha})`
    },

    statusPillStyle(status) {
      const color =
        this.statusOption(status)?.color ||
        '#667085'

      return {
        color,
        backgroundColor:
          this.hexToRgba(color, 0.13),
        borderColor:
          this.hexToRgba(color, 0.24)
      }
    },

    statusPillStyleFromOption(option) {
      const color =
        option?.color || '#667085'

      return {
        color,
        backgroundColor:
          this.hexToRgba(color, 0.13),
        borderColor:
          this.hexToRgba(color, 0.24)
      }
    },

    statusDotStyle(status) {
      return {
        backgroundColor:
          this.statusOption(status)?.color ||
          (
            String(status).toLowerCase() === 'all'
              ? '#667085'
              : '#667085'
          )
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

        this.orders = (
          Array.isArray(data) ? data : []
        ).map(order =>
          this.prepareOrder(order)
        )
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

    prepareOrder(order) {
      const prepared = {
        ...order,
        _remark: order?.remark ?? '',
        _remark_saved: false,
        _tracking_number:
          order?.tracking_number ?? '',
        _tracking_saved: false,
        _custom_status:
          this.isStandardStatus(order?.status)
            ? ''
            : (order?.status || ''),
        _status_menu: false,
        _custom_open: false
      }

      return prepared
    },

    selectCategory(key) {
      this.activeCategory = key
      this.activeStatus = 'all'
    },

    isStandardStatus(status) {
      const value =
        String(status || '')
          .trim()
          .toLowerCase()

      return this.standardStatusOptions.some(
        option => option.value === value
      )
    },

    toggleStatusMenu(order) {
      const nextState = !order._status_menu

      this.orders.forEach(item => {
        item._status_menu = false
      })

      if (
        this.selectedOrder &&
        Number(this.selectedOrder.id) !==
          Number(order.id)
      ) {
        this.selectedOrder._status_menu = false
      }

      order._status_menu = nextState
    },

    closeAllStatusMenus() {
      this.orders.forEach(order => {
        order._status_menu = false
      })

      if (this.selectedOrder) {
        this.selectedOrder._status_menu = false
      }
    },

    async selectStandardStatus(
      order,
      value
    ) {
      order._status_menu = false
      order._custom_open = false
      order._custom_status = ''

      await this.updateRemoteOrder(
        order,
        { status: value }
      )
    },

    async saveCustomStatus(order) {
      const value =
        String(
          order._custom_status || ''
        ).trim()

      if (!value) {
        return
      }

      const success =
        await this.updateRemoteOrder(
          order,
          { status: value }
        )

      if (success) {
        order._status_menu = false
        order._custom_open = false
      }
    },

    isSaving(orderId) {
      return this.savingOrderIds.includes(
        Number(orderId)
      )
    },

    setSaving(orderId, state) {
      const id = Number(orderId)

      if (state) {
        if (
          !this.savingOrderIds.includes(id)
        ) {
          this.savingOrderIds = [
            ...this.savingOrderIds,
            id
          ]
        }

        return
      }

      this.savingOrderIds =
        this.savingOrderIds.filter(
          savingId =>
            savingId !== id
        )
    },

    queueRemarkSave(order) {
      const id = Number(order.id)

      order._remark_saved = false

      if (
        this.remarkSaveTimers[id]
      ) {
        clearTimeout(
          this.remarkSaveTimers[id]
        )
      }

      this.remarkSaveTimers[id] =
        setTimeout(() => {
          this.saveRemarkNow(order)
        }, 800)
    },

    async saveRemarkNow(order) {
      const id = Number(order.id)

      if (
        this.remarkSaveTimers[id]
      ) {
        clearTimeout(
          this.remarkSaveTimers[id]
        )

        delete this.remarkSaveTimers[id]
      }

      const nextValue =
        String(order._remark ?? '')

      if (
        nextValue ===
        String(order.remark ?? '')
      ) {
        return
      }

      const success =
        await this.updateRemoteOrder(
          order,
          { remark: nextValue }
        )

      if (success) {
        order._remark_saved = true

        setTimeout(() => {
          order._remark_saved = false
        }, 1400)
      }
    },

    queueTrackingSave(order) {
      const id = Number(order.id)

      order._tracking_saved = false

      if (
        this.trackingSaveTimers[id]
      ) {
        clearTimeout(
          this.trackingSaveTimers[id]
        )
      }

      this.trackingSaveTimers[id] =
        setTimeout(() => {
          this.saveTrackingNow(order)
        }, 800)
    },

    async saveTrackingNow(order) {
      const id = Number(order.id)

      if (
        this.trackingSaveTimers[id]
      ) {
        clearTimeout(
          this.trackingSaveTimers[id]
        )

        delete this.trackingSaveTimers[id]
      }

      const nextValue =
        String(
          order._tracking_number ?? ''
        ).trim()

      if (
        nextValue ===
        String(order.tracking_number ?? '')
      ) {
        return
      }

      const success =
        await this.updateRemoteOrder(
          order,
          { tracking_number: nextValue }
        )

      if (success) {
        order._tracking_saved = true

        setTimeout(() => {
          order._tracking_saved = false
        }, 1400)
      }
    },

    async updateRemoteOrder(
      order,
      payload
    ) {
      if (
        !order?.id ||
        this.isSaving(order.id)
      ) {
        return false
      }

      this.setSaving(order.id, true)

      try {
        const response = await axios.put(
          `/api/teamstore-orders/${order.id}`,
          payload,
          {
            headers: this.headers()
          }
        )

        const updated =
          response.data?.data || {}

        if (
          Object.prototype.hasOwnProperty.call(
            updated,
            'status'
          )
        ) {
          order.status =
            updated.status

          order._custom_status =
            this.isStandardStatus(
              updated.status
            )
              ? ''
              : (updated.status || '')
        }

        if (
          Object.prototype.hasOwnProperty.call(
            updated,
            'remark'
          )
        ) {
          order.remark =
            updated.remark ?? ''

          order._remark =
            updated.remark ?? ''
        }

        if (
          Object.prototype.hasOwnProperty.call(
            updated,
            'tracking_number'
          )
        ) {
          order.tracking_number =
            updated.tracking_number ?? ''

          order._tracking_number =
            updated.tracking_number ?? ''
        }

        return true
      } catch (error) {
        console.error(
          'TeamStore order update error:',
          error
        )

        order._remark =
          order.remark ?? ''

        order._tracking_number =
          order.tracking_number ?? ''

        alert(
          error?.response?.data?.message ||
          'Order update failed.'
        )

        return false
      } finally {
        this.setSaving(
          order.id,
          false
        )
      }
    },

    isSelected(orderId) {
      return this.selectedIds.includes(
        Number(orderId)
      )
    },

    toggleOrder(orderId) {
      const id = Number(orderId)

      if (
        this.selectedIds.includes(id)
      ) {
        this.selectedIds =
          this.selectedIds.filter(
            selectedId =>
              selectedId !== id
          )
      } else {
        this.selectedIds = [
          ...this.selectedIds,
          id
        ]
      }
    },

    toggleVisibleSelection() {
      const visibleIds =
        this.filteredOrders.map(
          order => Number(order.id)
        )

      if (this.allVisibleSelected) {
        this.selectedIds =
          this.selectedIds.filter(
            id =>
              !visibleIds.includes(id)
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

    closeOrder() {
      if (this.selectedOrder) {
        this.selectedOrder._status_menu = false
      }

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
          const parsed =
            JSON.parse(items)

          return Array.isArray(parsed)
            ? parsed
            : []
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

    itemCategoryData(item) {
      const nestedCategory =
        item?.category &&
        typeof item.category === 'object'
          ? item.category
          : null

      const id =
        item?.category_id ||
        nestedCategory?.id ||
        item?.product_category_id ||
        null

      const name =
        item?.category_name ||
        nestedCategory?.name ||
        item?.product_category ||
        item?.store_name ||
        item?.team_name ||
        item?.collection_name ||
        'Other'

      const image =
        item?.category_icon_image ||
        nestedCategory?.icon_image ||
        item?.category_image ||
        ''

      return {
        id: id ? Number(id) : null,
        name: String(name || 'Other'),
        image
      }
    },

    itemCategory(item) {
      return this.itemCategoryData(
        item
      ).name
    },

    orderCategories(order) {
      const categories =
        this.normalizedItems(order)
          .map(item =>
            this.itemCategory(item)
          )
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
      const normalizedStatus =
        String(status || 'all')
          .toLowerCase()

      return this.orders.filter(order => {
        const categoryMatch =
          this.activeCategory === 'all' ||
          this.normalizedItems(order).some(
            item => {
              const category =
                this.itemCategoryData(item)

              const key = category.id
                ? `category-${category.id}`
                : this.slug(category.name)

              return (
                key ===
                this.activeCategory
              )
            }
          )

        const statusMatch =
          normalizedStatus === 'all' ||
          String(order.status || 'new')
            .toLowerCase() ===
            normalizedStatus

        return (
          categoryMatch &&
          statusMatch
        )
      }).length
    },

    statusClass(status) {
      return `status-${this.slug(
        status || 'new'
      )}`
    },

    statusDotClass(status) {
      const slug =
        this.slug(status || 'new')

      return `dot-${slug}`
    },

    slug(value) {
      return String(value || 'other')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
    },

    categoryIcon(name) {
      const value =
        String(name || '')
          .toLowerCase()

      if (
        value.includes('shirt') ||
        value.includes('jersey')
      ) {
        return 'fa-solid fa-shirt'
      }

      if (value.includes('bag')) {
        return 'fa-solid fa-bag-shopping'
      }

      if (
        value.includes('cap') ||
        value.includes('hat')
      ) {
        return 'fa-solid fa-hat-cowboy'
      }

      if (
        value.includes('jacket') ||
        value.includes('outerwear')
      ) {
        return 'fa-solid fa-vest'
      }

      if (
        value.includes('football')
      ) {
        return 'fa-solid fa-football'
      }

      return 'fa-solid fa-box'
    },

    formatLabel(value) {
      return String(value || '')
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, char =>
          char.toUpperCase()
        )
    },

    formatDate(value) {
      if (!value) {
        return '—'
      }

      const date = new Date(value)

      if (
        Number.isNaN(date.getTime())
      ) {
        return '—'
      }

      return date.toLocaleDateString(
        'en-US',
        {
          month: 'short',
          day: '2-digit',
          year: 'numeric'
        }
      )
    },

    formatDateTime(value) {
      if (!value) {
        return '—'
      }

      const date = new Date(value)

      if (
        Number.isNaN(date.getTime())
      ) {
        return '—'
      }

      return date.toLocaleString(
        'en-US',
        {
          month: 'short',
          day: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        }
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

    printSelectedOrders() {
      const orders = this.selectedOrders

      if (!orders.length) {
        return
      }

      const rows = orders.map(order => {
        const items =
          this.normalizedItems(order)
            .map(item => {
              const image =
                this.itemImage(item)

              return `
                <div class="item">
                  ${
                    image
                      ? `<img src="${this.escapeHtml(image)}" alt="">`
                      : '<div class="placeholder">No Image</div>'
                  }

                  <div>
                    <strong>${this.escapeHtml(this.itemName(item))}</strong>
                    <span>Category: ${this.escapeHtml(this.itemCategory(item))}</span>
                    <span>Size: ${this.escapeHtml(item.size || item.selected_size || '—')}</span>
                    <span>Qty: ${this.escapeHtml(item.quantity || item.qty || 1)}</span>
                  </div>
                </div>
              `
            })
            .join('')

        return `
          <section class="order">
            <header>
              <div>
                <small>ORDER</small>
                <h2>${this.escapeHtml(order.order_number || `#${order.id}`)}</h2>
              </div>

              <span>${this.escapeHtml(this.statusLabel(order.status || 'new'))}</span>
            </header>

            <div class="details">
              <p><b>Customer:</b> ${this.escapeHtml(order.customer_name || '—')}</p>
              <p><b>Phone:</b> ${this.escapeHtml(order.phone || '—')}</p>
              <p><b>Email:</b> ${this.escapeHtml(order.email || '—')}</p>
              <p><b>City:</b> ${this.escapeHtml(order.shipping_city || '—')}</p>
              <p><b>Tracking:</b> ${this.escapeHtml(order.tracking_number || '—')}</p>
              <p><b>Remark:</b> ${this.escapeHtml(order.remark || '—')}</p>
            </div>

            <div class="items">
              ${items || '<p>No item details found.</p>'}
            </div>
          </section>
        `
      }).join('')

      const printWindow =
        window.open(
          '',
          '_blank',
          'width=1100,height=800'
        )

      if (!printWindow) {
        alert(
          'Please allow popups to print.'
        )

        return
      }

      printWindow.document.write(`
        <!doctype html>
        <html>
          <head>
            <title>TeamStore Orders</title>

            <style>
              * { box-sizing: border-box; }

              body {
                margin: 0;
                padding: 24px;
                color: #111;
                font-family: Arial, sans-serif;
                background: #fff;
              }

              .brand {
                margin-bottom: 20px;
                padding-bottom: 12px;
                border-bottom: 2px solid #111;
                text-align: center;
              }

              .brand h1 {
                margin: 0;
                font-size: 23px;
              }

              .brand p {
                margin: 4px 0 0;
                color: #666;
              }

              .order {
                margin-bottom: 18px;
                border: 1px solid #ddd;
                border-radius: 10px;
                overflow: hidden;
                page-break-inside: avoid;
              }

              .order header {
                padding: 12px 14px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #111827;
                color: white;
              }

              .order header h2 {
                margin: 3px 0 0;
                font-size: 17px;
              }

              .order header > span {
                padding: 5px 9px;
                border-radius: 999px;
                background: white;
                color: #111;
                font-size: 10px;
                font-weight: 700;
              }

              .details {
                padding: 12px 14px;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 6px 16px;
              }

              .details p {
                margin: 0;
                font-size: 11px;
              }

              .items {
                padding: 0 14px 14px;
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 9px;
              }

              .item {
                padding: 8px;
                display: flex;
                gap: 9px;
                border: 1px solid #ddd;
                border-radius: 8px;
              }

              .item img,
              .placeholder {
                width: 56px;
                height: 56px;
                border-radius: 6px;
                object-fit: contain;
                background: #f3f4f6;
              }

              .placeholder {
                display: grid;
                place-items: center;
                color: #777;
                font-size: 8px;
              }

              .item strong,
              .item span {
                display: block;
              }

              .item strong {
                margin-bottom: 4px;
                font-size: 11px;
              }

              .item span {
                margin-top: 2px;
                color: #555;
                font-size: 9px;
              }

              @media print {
                body { padding: 0; }
              }

/* ==========================================================
   APP LAYOUT INTEGRATION + READABLE PROFESSIONAL TYPOGRAPHY
   ========================================================== */

.teamstore-page {
  width: 100%;
  min-width: 0;
  min-height: 100vh;
  background: #f5f6f8;
  color: #101828;
  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

.teamstore-page .main-content {
  width: 100%;
  min-width: 0;
}

/* The sidebar belongs to AppLayout now. */
.teamstore-page .mobile-menu {
  display: none !important;
}

/* Bigger readable typography */
.topbar h1 {
  font-size: 24px !important;
}

.eyebrow {
  font-size: 10px !important;
}

.page-subtitle {
  font-size: 12px !important;
}

.section-heading h2,
.panel-header h2,
.items-heading h3 {
  font-size: 16px !important;
}

.section-heading p,
.panel-header p,
.items-heading p {
  font-size: 11px !important;
}

.category-info strong {
  font-size: 12px !important;
}

.category-info small {
  font-size: 10px !important;
}

.status-summary {
  min-width: 118px !important;
  height: 40px !important;
  font-size: 12px !important;
}

.status-summary strong {
  font-size: 11px !important;
}

.orders-table th {
  padding: 12px 10px !important;
  font-size: 10px !important;
}

.orders-table td {
  padding: 12px 10px !important;
  font-size: 12px !important;
}

.order-number-cell strong,
.customer-cell strong,
.shipping-cell strong {
  font-size: 12px !important;
}

.order-number-cell small,
.customer-cell small,
.shipping-cell small {
  font-size: 9px !important;
}

.category-tags span {
  font-size: 9px !important;
}

.plain-text,
.date-cell {
  font-size: 11px !important;
}

.status-control {
  width: 165px !important;
}

.status-trigger {
  width: 165px !important;
  height: 38px !important;
  padding: 0 12px !important;
  border-width: 1px !important;
  border-style: solid !important;
  font-size: 11px !important;
}

.status-menu {
  width: 245px !important;
}

.status-menu-title {
  font-size: 9px !important;
}

.status-option,
.custom-status-toggle {
  min-height: 37px !important;
  font-size: 11px !important;
}

.custom-status-box input {
  height: 38px !important;
  font-size: 11px !important;
}

.custom-status-box button {
  height: 35px !important;
  font-size: 10px !important;
}

.remark-column {
  width: 210px !important;
}

.line-editor {
  width: 195px !important;
}

.line-input {
  height: 37px !important;
  font-size: 12px !important;
}

.tracking-column {
  width: 245px !important;
}

.tracking-editor {
  width: 225px !important;
}

.tracking-input-wrap {
  height: 40px !important;
}

.tracking-input-wrap input {
  font-size: 12px !important;
}

.tracking-meta small,
.tracking-meta span {
  font-size: 9px !important;
}

.view-button,
.secondary-button,
.primary-button,
.refresh-button {
  font-size: 11px !important;
}

.view-button {
  height: 36px !important;
}

.search-box {
  width: 330px !important;
  height: 42px !important;
}

.search-box input {
  font-size: 12px !important;
}

.new-count {
  height: 38px !important;
  font-size: 11px !important;
}

/* Manage Status button */
.manage-status-button {
  height: 42px;
  padding: 0 14px;
  border: 1px solid #dfe3e8;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #fff;
  color: #111827;
  cursor: pointer;
  font-size: 11px;
  font-weight: 850;
  white-space: nowrap;
  transition: .18s ease;
}

.manage-status-button:hover {
  border-color: #111827;
  background: #f9fafb;
}

/* ==========================================================
   STATUS MANAGER
   ========================================================== */
.status-manager-overlay {
  position: fixed;
  inset: 0;
  z-index: 5000;
  padding: 28px;
  overflow-y: auto;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  background: rgba(15, 23, 42, .56);
  backdrop-filter: blur(3px);
}

.status-manager-modal {
  width: min(940px, 100%);
  overflow: hidden;
  border: 1px solid #e4e7ec;
  border-radius: 18px;
  background: #fff;
  box-shadow:
    0 28px 70px
    rgba(15, 23, 42, .24);
}

.status-manager-header {
  padding: 22px 24px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  background: #111827;
  color: #fff;
}

.manager-eyebrow {
  display: block;
  margin-bottom: 5px;
  color: #98a2b3;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: .12em;
}

.status-manager-header h2 {
  margin: 0;
  font-size: 21px;
}

.status-manager-header p {
  margin: 6px 0 0;
  color: #c4cad4;
  font-size: 11px;
}

.manager-close {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  border: 0;
  border-radius: 10px;
  display: grid;
  place-items: center;
  background: #242c3a;
  color: #fff;
  cursor: pointer;
}

.status-manager-body {
  padding: 20px;
  background: #f7f8fa;
}

.add-status-card,
.status-list-card {
  border: 1px solid #e4e7ec;
  border-radius: 14px;
  background: #fff;
}

.add-status-card {
  padding: 16px;
}

.status-list-card {
  margin-top: 14px;
  padding: 16px;
}

.manager-section-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.manager-section-title h3 {
  margin: 0;
  color: #101828;
  font-size: 14px;
}

.manager-section-title p {
  margin: 4px 0 0;
  color: #98a2b3;
  font-size: 10px;
}

.status-total {
  padding: 6px 9px;
  border-radius: 999px;
  background: #f2f4f7;
  color: #475467;
  font-size: 9px;
  font-weight: 850;
}

.add-status-form {
  margin-top: 14px;
  display: flex;
  align-items: flex-end;
  gap: 10px;
}

.manager-field {
  min-width: 0;
}

.manager-field-grow {
  flex: 1;
}

.manager-field label,
.manager-status-main label,
.manager-color-control label,
.manager-preview label {
  display: block;
  margin-bottom: 6px;
  color: #667085;
  font-size: 9px;
  font-weight: 800;
}

.manager-field input[type="text"],
.manager-status-main input {
  width: 100%;
  height: 40px;
  padding: 0 11px;
  border: 1px solid #d0d5dd;
  border-radius: 9px;
  outline: 0;
  color: #101828;
  font-size: 12px;
}

.manager-field input[type="text"]:focus,
.manager-status-main input:focus {
  border-color: #111827;
}

.color-field {
  width: 145px;
}

.color-picker-box {
  height: 40px;
  padding: 0 9px;
  border: 1px solid #d0d5dd;
  border-radius: 9px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-picker-box input {
  width: 27px;
  height: 27px;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
}

.color-picker-box span {
  color: #667085;
  font-size: 10px;
}

.add-status-button {
  height: 40px;
  padding: 0 14px;
  border: 0;
  border-radius: 9px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: #111827;
  color: #fff;
  cursor: pointer;
  font-size: 11px;
  font-weight: 850;
}

.add-status-button:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.status-manager-list {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.status-manager-row {
  padding: 11px;
  border: 1px solid #eaecf0;
  border-radius: 11px;
  display: grid;
  grid-template-columns:
    12px minmax(180px, 1fr) 70px 170px 95px;
  align-items: end;
  gap: 11px;
  background: #fff;
}

.manager-color-preview {
  width: 10px;
  height: 42px;
  border-radius: 999px;
  align-self: center;
}

.manager-status-main small {
  display: block;
  margin-top: 4px;
  color: #98a2b3;
  font-size: 8px;
}

.manager-color-control input {
  width: 48px;
  height: 40px;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: pointer;
}

.manager-preview-pill {
  min-height: 36px;
  padding: 0 10px;
  border: 1px solid transparent;
  border-radius: 9px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 10px;
  font-weight: 850;
}

.manager-preview-pill i {
  width: 7px;
  height: 7px;
  border-radius: 50%;
}

.manager-row-actions {
  display: flex;
  align-items: center;
  gap: 5px;
}

.save-status-setting,
.delete-status-setting {
  height: 36px;
  border: 0;
  border-radius: 8px;
  cursor: pointer;
  font-size: 9px;
  font-weight: 850;
}

.save-status-setting {
  padding: 0 10px;
  background: #111827;
  color: #fff;
}

.delete-status-setting {
  width: 36px;
  background: #fee4e2;
  color: #b42318;
}

.manager-footer-note {
  margin-top: 12px;
  padding: 10px 12px;
  border: 1px solid #e4e7ec;
  border-radius: 10px;
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background: #fff;
  color: #667085;
  font-size: 9px;
  line-height: 1.5;
}

@media (max-width: 900px) {
  .topbar-actions {
    flex-wrap: wrap;
  }

  .search-box {
    width: min(100%, 330px) !important;
  }

  .add-status-form {
    align-items: stretch;
    flex-direction: column;
  }

  .color-field {
    width: 100%;
  }

  .status-manager-row {
    grid-template-columns: 10px 1fr 60px;
  }

  .manager-preview,
  .manager-row-actions {
    grid-column: 2 / -1;
  }
}

</style>
          </head>

          <body>
            <div class="brand">
              <h1>PROSIX SPORTS</h1>
              <p>TeamStore Orders Production Sheet</p>
            </div>

            ${rows}
          </body>
        </html>
      `)

      printWindow.document.close()
      printWindow.focus()

      setTimeout(() => {
        printWindow.print()
      }, 400)
    }
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.teamstore-shell {
  min-height: 100vh;
  display: flex;
  background: #f5f6f8;
  color: #101828;
  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

/* =========================
   SIDEBAR
========================= */
.sidebar {
  width: 240px;
  min-width: 240px;
  min-height: 100vh;
  position: sticky;
  top: 0;
  align-self: flex-start;
  z-index: 50;
  background:
    linear-gradient(
      180deg,
      #11141d 0%,
      #0d1017 100%
    );
  color: #fff;
  padding: 14px 12px 20px;
}

.brand {
  height: 46px;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 4px;
}

.brand-mark {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  display: grid;
  place-items: center;
  background: #fff;
  color: #10131b;
  font-size: 14px;
  font-weight: 950;
  font-style: italic;
}

.brand strong,
.brand span {
  display: block;
}

.brand strong {
  font-size: 12px;
  font-weight: 850;
}

.brand span {
  margin-top: 1px;
  color: #7f8796;
  font-size: 7px;
  font-weight: 600;
}

.sidebar-close {
  display: none;
  margin-left: auto;
  width: 30px;
  height: 30px;
  border: 0;
  border-radius: 8px;
  background: #202532;
  color: #fff;
}

.profile-card {
  margin-top: 10px;
  min-height: 65px;
  padding: 10px;
  border: 1px solid #2b303d;
  border-radius: 13px;
  display: flex;
  align-items: center;
  gap: 9px;
  background: #181c25;
}

.profile-avatar {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  overflow: hidden;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: #2c3240;
  color: #fff;
  font-size: 13px;
  font-weight: 900;
}

.profile-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.profile-info {
  min-width: 0;
  flex: 1;
}

.profile-info strong,
.profile-info small {
  display: block;
}

.profile-info strong {
  overflow: hidden;
  color: #fff;
  font-size: 10px;
  font-weight: 850;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-info small {
  margin-top: 3px;
  color: #8f97a7;
  font-size: 8px;
}

.profile-edit-icon {
  color: #7d8595;
  font-size: 9px;
}

.sidebar-nav {
  margin-top: 15px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-link {
  min-height: 39px;
  padding: 0 10px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #aeb5c3;
  text-decoration: none;
  font-size: 9px;
  font-weight: 750;
  transition: .18s ease;
}

.nav-link i {
  width: 15px;
  text-align: center;
  color: #8992a3;
}

.nav-link:hover {
  background: #171c25;
  color: #fff;
}

.nav-link.active {
  background: #1b202a;
  color: #fff;
}

.nav-link.active i {
  color: #fff;
}

.nav-count {
  margin-left: auto;
  min-width: 23px;
  height: 23px;
  padding: 0 6px;
  border-radius: 999px;
  display: grid;
  place-items: center;
  background: #fff;
  color: #111827;
  font-size: 8px;
  font-weight: 900;
}

/* =========================
   MAIN
========================= */
.main-content {
  min-width: 0;
  flex: 1;
}

.topbar {
  min-height: 82px;
  padding: 16px 24px;
  border-bottom: 1px solid #e6e8ec;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  background: #fff;
}

.topbar-left,
.topbar-actions,
.header-left {
  display: flex;
  align-items: center;
}

.topbar-left {
  gap: 12px;
}

.back-button,
.mobile-menu {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  border: 1px solid #dfe3e8;
  border-radius: 10px;
  display: grid;
  place-items: center;
  background: #fff;
  color: #111827;
  cursor: pointer;
}

.mobile-menu {
  display: none;
}

.eyebrow {
  margin: 0 0 3px;
  color: #516fff;
  font-size: 7px;
  font-weight: 950;
  letter-spacing: .16em;
}

.topbar h1 {
  margin: 0;
  color: #101828;
  font-size: 18px;
  line-height: 1.1;
}

.page-subtitle {
  display: block;
  margin-top: 4px;
  color: #7a8393;
  font-size: 9px;
}

.topbar-actions {
  gap: 10px;
}

.search-box {
  width: 285px;
  height: 38px;
  padding: 0 11px;
  border: 1px solid #dfe3e8;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fff;
}

.search-box i {
  color: #7c8492;
  font-size: 10px;
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #111827;
  font-size: 10px;
}

.new-count {
  height: 34px;
  padding: 0 12px;
  border-radius: 999px;
  display: flex;
  align-items: center;
  background: #111827;
  color: #fff;
  font-size: 9px;
  font-weight: 900;
}

.workspace {
  padding: 18px 22px 34px;
}

.card {
  border: 1px solid #e5e8ed;
  border-radius: 14px;
  background: #fff;
  box-shadow:
    0 7px 20px
    rgba(16, 24, 40, .035);
}

/* =========================
   CATEGORIES
========================= */
.category-section {
  padding: 15px;
}

.section-heading,
.panel-header,
.items-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
}

.section-heading h2,
.panel-header h2,
.items-heading h3 {
  margin: 0;
  color: #101828;
  font-size: 12px;
}

.section-heading p,
.panel-header p,
.items-heading p {
  margin: 3px 0 0;
  color: #8a93a3;
  font-size: 8px;
}

.refresh-button,
.secondary-button,
.primary-button,
.view-button {
  border: 0;
  cursor: pointer;
  font-weight: 800;
}

.refresh-button {
  height: 34px;
  padding: 0 11px;
  border: 1px solid #dfe3e8;
  border-radius: 9px;
  display: flex;
  align-items: center;
  gap: 6px;
  background: #fff;
  color: #111827;
  font-size: 9px;
}

.category-grid {
  margin-top: 13px;
  display: grid;
  grid-template-columns:
    repeat(auto-fill, minmax(165px, 1fr));
  gap: 9px;
}

.category-card {
  min-height: 68px;
  padding: 9px 10px;
  border: 1px solid #e0e4ea;
  border-radius: 11px;
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fff;
  text-align: left;
  cursor: pointer;
  transition: .18s ease;
}

.category-card:hover {
  border-color: #c8ced8;
  transform: translateY(-1px);
}

.category-card.active {
  border-color: #111827;
  background: #111827;
  color: #fff;
}

.category-icon {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  overflow: hidden;
  border: 1px solid #e3e7ec;
  border-radius: 9px;
  display: grid;
  place-items: center;
  background: #f8fafc;
  color: #111827;
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
  font-size: 9px;
  font-weight: 850;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-info small {
  margin-top: 4px;
  color: #8a93a3;
  font-size: 7px;
}

.category-card.active
.category-info small {
  color: #b7becb;
}

.card-arrow {
  color: #9ca3af;
  font-size: 8px;
}

.category-card.active
.card-arrow {
  color: #fff;
}

/* =========================
   STATUS SUMMARY
========================= */
.status-section {
  margin: 11px 0;
  display: flex;
  gap: 7px;
  overflow-x: auto;
  scrollbar-width: thin;
}

.status-summary {
  min-width: 105px;
  height: 36px;
  padding: 0 10px;
  border: 1px solid #e0e4ea;
  border-radius: 9px;
  display: flex;
  align-items: center;
  gap: 7px;
  background: #fff;
  color: #667085;
  cursor: pointer;
  font-size: 9px;
  white-space: nowrap;
}

.status-summary strong {
  margin-left: auto;
  color: #111827;
  font-size: 8px;
}

.status-summary.active {
  border-color: #111827;
  box-shadow:
    inset 0 0 0 1px #111827;
  color: #111827;
}

.summary-dot,
.option-dot,
.status-trigger-dot {
  width: 7px;
  height: 7px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #94a3b8;
}

.dot-new { background: #8b5cf6; }
.dot-confirmed { background: #4f7df3; }
.dot-production { background: #f59e0b; }
.dot-shipped { background: #38a6e6; }
.dot-delivered { background: #34b98b; }
.dot-cancelled { background: #ef5350; }

/* =========================
   TABLE
========================= */
.orders-panel {
  overflow: visible;
}

.panel-header {
  padding: 14px 15px;
  border-bottom: 1px solid #e8ebef;
}

.bulk-actions {
  display: flex;
  align-items: center;
  gap: 7px;
}

.selected-label {
  color: #667085;
  font-size: 8px;
  font-weight: 800;
}

.secondary-button,
.primary-button {
  height: 32px;
  padding: 0 11px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 8px;
}

.secondary-button {
  border: 1px solid #dfe3e8;
  background: #fff;
  color: #111827;
}

.primary-button {
  background: #111827;
  color: #fff;
}

.primary-button:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.table-wrap {
  width: 100%;
  overflow-x: auto;
  overflow-y: visible;
}

.orders-table {
  width: 100%;
  min-width: 1450px;
  border-collapse: collapse;
}

.orders-table th {
  padding: 10px 9px;
  background: #f8f9fb;
  color: #7c8492;
  text-align: left;
  font-size: 7px;
  font-weight: 900;
  letter-spacing: .05em;
  text-transform: uppercase;
  white-space: nowrap;
}

.orders-table td {
  padding: 10px 9px;
  border-top: 1px solid #edf0f3;
  color: #111827;
  font-size: 9px;
  vertical-align: middle;
}

.orders-table tbody tr {
  background: #fff;
  transition: background .15s ease;
}

.orders-table tbody tr:hover {
  background: #fafbfc;
}

.orders-table tbody tr.unread {
  background: #f4f7ff;
}

.check-column {
  width: 36px;
  text-align: center !important;
}

.order-number-cell {
  min-width: 120px;
  display: flex;
  align-items: center;
  gap: 7px;
}

.order-number-cell strong,
.customer-cell strong,
.shipping-cell strong {
  display: block;
  font-size: 9px;
  font-weight: 850;
}

.order-number-cell small,
.customer-cell small,
.shipping-cell small {
  display: block;
  margin-top: 3px;
  color: #98a1b0;
  font-size: 7px;
}

.unread-dot {
  width: 6px;
  height: 6px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #4f7df3;
}

.category-tags {
  min-width: 115px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.category-tags span {
  max-width: 105px;
  padding: 4px 7px;
  overflow: hidden;
  border-radius: 999px;
  background: #eef2ff;
  color: #4056c9;
  font-size: 7px;
  font-weight: 850;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-tags em {
  color: #667085;
  font-size: 7px;
  font-style: normal;
}

.item-thumbnails {
  min-width: 115px;
  display: flex;
  align-items: center;
}

.item-thumb {
  width: 30px;
  height: 30px;
  margin-left: -4px;
  overflow: hidden;
  border: 2px solid #fff;
  border-radius: 7px;
  display: grid;
  place-items: center;
  background: #f4f5f7;
  color: #777;
}

.item-thumb:first-child {
  margin-left: 0;
}

.item-thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.more-items {
  width: 28px;
  height: 28px;
  margin-left: 2px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: #111827;
  color: #fff;
  font-size: 7px;
  font-weight: 900;
}

.customer-cell {
  min-width: 150px;
}

.plain-text,
.date-cell {
  color: #475467;
  font-size: 8px;
}

/* =========================
   PROFESSIONAL STATUS MENU
========================= */
.status-column {
  position: relative;
}

.status-control {
  position: relative;
  width: 145px;
}

.status-trigger {
  width: 145px;
  height: 32px;
  padding: 0 10px;
  border: 1px solid transparent;
  border-radius: 9px;
  display: flex;
  align-items: center;
  gap: 7px;
  background: #f3f4f6;
  color: #344054;
  cursor: pointer;
  font-size: 8px;
  font-weight: 850;
}

.status-trigger-label {
  min-width: 0;
  flex: 1;
  overflow: hidden;
  text-align: left;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.status-trigger i {
  font-size: 7px;
}

.status-new {
  background: #f4edff;
  color: #7e22ce;
}

.status-confirmed {
  background: #e7efff;
  color: #315ed4;
}

.status-production {
  background: #fff4da;
  color: #a26406;
}

.status-shipped {
  background: #e4f5fd;
  color: #0877a8;
}

.status-delivered {
  background: #e3f8ef;
  color: #14795a;
}

.status-cancelled {
  background: #feeceb;
  color: #b42318;
}

.status-new
.status-trigger-dot { background: #8b5cf6; }

.status-confirmed
.status-trigger-dot { background: #4f7df3; }

.status-production
.status-trigger-dot { background: #f59e0b; }

.status-shipped
.status-trigger-dot { background: #38a6e6; }

.status-delivered
.status-trigger-dot { background: #34b98b; }

.status-cancelled
.status-trigger-dot { background: #ef5350; }

.status-menu {
  width: 220px;
  padding: 7px;
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  z-index: 1000;
  border: 1px solid #e0e4ea;
  border-radius: 11px;
  background: #fff;
  box-shadow:
    0 16px 38px
    rgba(16, 24, 40, .14);
}

.status-menu-title {
  padding: 5px 7px 7px;
  color: #8a93a3;
  font-size: 7px;
  font-weight: 900;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.status-option,
.custom-status-toggle {
  width: 100%;
  min-height: 31px;
  padding: 0 8px;
  border: 0;
  border-radius: 7px;
  display: flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  color: #344054;
  cursor: pointer;
  font-size: 9px;
  font-weight: 700;
  text-align: left;
}

.status-option:hover,
.custom-status-toggle:hover {
  background: #f7f8fa;
}

.status-option.selected {
  background: #f2f4f7;
  color: #101828;
}

.status-option i {
  margin-left: auto;
  color: #111827;
  font-size: 8px;
}

.custom-divider {
  height: 1px;
  margin: 6px 3px;
  background: #eef0f3;
}

.custom-status-toggle {
  color: #111827;
  font-weight: 800;
}

.custom-status-box {
  margin-top: 6px;
  padding: 7px;
  border-radius: 8px;
  background: #f7f8fa;
}

.custom-status-box input {
  width: 100%;
  height: 32px;
  padding: 0 9px;
  border: 1px solid #d8dde5;
  border-radius: 7px;
  outline: 0;
  background: #fff;
  color: #111827;
  font-size: 9px;
}

.custom-status-box button {
  width: 100%;
  height: 30px;
  margin-top: 6px;
  border: 0;
  border-radius: 7px;
  background: #111827;
  color: #fff;
  cursor: pointer;
  font-size: 8px;
  font-weight: 900;
}

.custom-status-box button:disabled {
  opacity: .45;
}

.saving-text {
  display: block;
  margin-top: 3px;
  color: #98a1b0;
  font-size: 7px;
}

/* =========================
   REMARK + TRACKING
========================= */
.remark-column {
  width: 180px;
}

.line-editor {
  width: 170px;
  position: relative;
  padding-bottom: 9px;
}

.line-input {
  width: 100%;
  height: 31px;
  padding: 0 3px;
  border: 0;
  border-bottom: 1px solid #ccd2da;
  outline: 0;
  background: transparent;
  color: #111827;
  font-size: 9px;
}

.line-input:focus {
  border-bottom-color: #111827;
}

.line-input::placeholder {
  color: #a2a9b4;
}

.autosave-state {
  position: absolute;
  right: 2px;
  bottom: -1px;
  color: #98a1b0;
  font-size: 6px;
}

.autosave-state.saved {
  color: #15956b;
}

.tracking-column {
  width: 200px;
}

.tracking-editor {
  width: 185px;
}

.tracking-input-wrap {
  height: 34px;
  padding: 0 9px;
  border: 1px solid #d9dee6;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 7px;
  background: #fff;
}

.tracking-input-wrap:focus-within {
  border-color: #111827;
}

.tracking-input-wrap i {
  color: #7f8898;
  font-size: 9px;
}

.tracking-input-wrap input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #111827;
  font-size: 9px;
}

.tracking-input-wrap input::placeholder {
  color: #a0a8b5;
}

.tracking-meta {
  min-height: 12px;
  margin-top: 3px;
  display: flex;
  justify-content: space-between;
}

.tracking-meta small {
  color: #9aa2af;
  font-size: 6px;
}

.tracking-meta span {
  color: #15956b;
  font-size: 6px;
  font-weight: 800;
}

.view-button {
  height: 30px;
  padding: 0 10px;
  border: 1px solid #dfe3e8;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: #fff;
  color: #111827;
  font-size: 8px;
}

.view-button:hover {
  border-color: #111827;
}

/* =========================
   EMPTY
========================= */
.empty-state {
  min-height: 280px;
  display: grid;
  place-items: center;
  align-content: center;
  color: #8a93a3;
  text-align: center;
}

.empty-state i {
  margin-bottom: 10px;
  font-size: 24px;
}

.empty-state h3 {
  margin: 0;
  color: #344054;
  font-size: 13px;
}

.empty-state p {
  margin: 5px 0 0;
  font-size: 9px;
}

/* =========================
   MODAL
========================= */
.modal-overlay {
  padding: 30px;
  position: fixed;
  inset: 0;
  z-index: 3000;
  overflow-y: auto;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  background: rgba(15, 23, 42, .55);
  backdrop-filter: blur(3px);
}

.order-modal {
  width: min(1040px, 100%);
  overflow: visible;
  border-radius: 16px;
  background: #fff;
  box-shadow:
    0 24px 60px
    rgba(15, 23, 42, .22);
}

.modal-header {
  min-height: 88px;
  padding: 18px 20px;
  border-radius: 16px 16px 0 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #111827;
  color: #fff;
}

.modal-header p {
  margin: 0 0 3px;
  color: #9ba4b4;
  font-size: 7px;
  font-weight: 900;
  letter-spacing: .1em;
  text-transform: uppercase;
}

.modal-header h2 {
  margin: 0;
  font-size: 17px;
}

.modal-header span {
  display: block;
  margin-top: 4px;
  color: #c6ccd6;
  font-size: 8px;
}

.modal-header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-badge.large {
  padding: 7px 10px;
  border-radius: 999px;
  font-size: 8px;
  font-weight: 900;
}

.modal-close {
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 9px;
  display: grid;
  place-items: center;
  background: #222938;
  color: #fff;
  cursor: pointer;
}

.modal-body {
  padding: 18px;
}

.detail-grid {
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.detail-card,
.items-section,
.notes-card {
  border: 1px solid #e5e8ed;
  border-radius: 12px;
  background: #fff;
}

.detail-card {
  padding: 14px;
}

.detail-card.full-card {
  grid-column: 1 / -1;
}

.detail-card h3,
.items-section h3,
.notes-card h3 {
  margin: 0 0 12px;
  display: flex;
  align-items: center;
  gap: 6px;
  color: #101828;
  font-size: 10px;
}

.detail-card dl {
  margin: 0;
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
  gap: 10px 14px;
}

.detail-card dl .full-row {
  grid-column: 1 / -1;
}

.detail-card dt {
  margin-bottom: 3px;
  color: #8b94a3;
  font-size: 7px;
  font-weight: 850;
  text-transform: uppercase;
}

.detail-card dd {
  margin: 0;
  color: #344054;
  font-size: 9px;
  font-weight: 650;
}

.modal-control-grid {
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.modal-control.full-row {
  grid-column: 1 / -1;
}

.modal-control > label {
  display: block;
  margin-bottom: 6px;
  color: #7f8898;
  font-size: 7px;
  font-weight: 900;
  text-transform: uppercase;
}

.modal-status-control {
  width: 100%;
}

.modal-status-trigger {
  width: 100%;
}

.modal-status-menu {
  width: 260px;
}

.always-open {
  display: block;
}

.modal-field-input {
  height: 38px;
  padding: 0 10px;
  border: 1px solid #d9dee6;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.modal-field-input i {
  color: #7f8898;
  font-size: 10px;
}

.modal-field-input input {
  width: 100%;
  border: 0;
  outline: 0;
  font-size: 10px;
}

.modal-line-editor {
  position: relative;
  padding-bottom: 13px;
}

.modal-line-editor input {
  width: 100%;
  height: 38px;
  padding: 0 3px;
  border: 0;
  border-bottom: 1px solid #ccd2da;
  outline: 0;
  color: #111827;
  font-size: 10px;
}

.modal-line-editor input:focus {
  border-bottom-color: #111827;
}

.modal-line-editor small {
  position: absolute;
  right: 0;
  bottom: 0;
  color: #98a1b0;
  font-size: 7px;
}

.items-section {
  margin-top: 12px;
  padding: 14px;
}

.modal-items-grid {
  margin-top: 10px;
  display: grid;
  grid-template-columns:
    repeat(2, minmax(0, 1fr));
  gap: 9px;
}

.modal-item-card {
  padding: 9px;
  border: 1px solid #e5e8ed;
  border-radius: 9px;
  display: flex;
  gap: 9px;
}

.modal-item-image {
  width: 58px;
  height: 58px;
  flex-shrink: 0;
  overflow: hidden;
  border-radius: 8px;
  display: grid;
  place-items: center;
  background: #f5f6f8;
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
  color: #5871db;
  font-size: 7px;
  font-weight: 850;
}

.modal-item-info h4 {
  margin: 3px 0 6px;
  font-size: 10px;
}

.item-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 10px;
}

.item-meta em {
  color: #8a93a3;
  font-size: 7px;
  font-style: normal;
}

.item-meta strong {
  color: #344054;
}

.no-items {
  padding: 30px;
  color: #8a93a3;
  text-align: center;
  font-size: 9px;
}

.notes-card {
  margin-top: 12px;
  padding: 14px;
}

.notes-card p {
  margin: 0;
  color: #475467;
  font-size: 9px;
  line-height: 1.6;
}

/* =========================
   RESPONSIVE
========================= */
.sidebar-backdrop {
  display: none;
}

@media (max-width: 1100px) {
  .sidebar {
    width: 215px;
    min-width: 215px;
  }

  .search-box {
    width: 230px;
  }
}

@media (max-width: 900px) {
  .sidebar {
    width: 240px;
    min-width: 240px;
    position: fixed;
    left: -260px;
    transition: left .2s ease;
  }

  .sidebar.open {
    left: 0;
  }

  .sidebar-close {
    display: grid;
    place-items: center;
  }

  .sidebar-backdrop {
    position: fixed;
    inset: 0;
    z-index: 40;
    display: block;
    background: rgba(15, 23, 42, .45);
  }

  .mobile-menu {
    display: grid;
  }

  .topbar {
    padding: 14px;
  }

  .workspace {
    padding: 14px;
  }

  .search-box {
    width: 200px;
  }
}

@media (max-width: 680px) {
  .topbar {
    align-items: flex-start;
    flex-direction: column;
  }

  .topbar-actions {
    width: 100%;
  }

  .search-box {
    width: 100%;
    flex: 1;
  }

  .category-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }

  .panel-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .detail-grid,
  .modal-control-grid,
  .modal-items-grid {
    grid-template-columns: 1fr;
  }

  .modal-control.full-row,
  .detail-card.full-card {
    grid-column: auto;
  }

  .modal-overlay {
    padding: 10px;
  }
}
</style>
