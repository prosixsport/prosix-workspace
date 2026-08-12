<template>
  <AppLayout>
    <div class="page-wrap">

      <!-- GLOBAL HEADER -->
      <PageHeader
        title="Recycle Bin"
        subtitle="Restore deleted orders or permanently remove them"
        :user="currentUser"
        :photo="currentUser?.profile_photo_url"
        @profile="openProfile"
      />

      <!-- PAGE CONTENT -->
      <div class="recycle-content">

        <!-- LOADING -->
        <div
          v-if="loading"
          class="empty"
        >
          <i class="fa-solid fa-spinner fa-spin"></i>
          Loading...
        </div>

        <!-- EMPTY -->
        <div
          v-else-if="orders.length === 0"
          class="empty"
        >
          <i class="fa-regular fa-trash-can"></i>
          No deleted orders found.
        </div>

        <!-- TABLE -->
        <div
          v-else
          class="table-card"
        >
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
              <tr
                v-for="order in orders"
                :key="order.id"
              >
                <!-- ORDER -->
                <td>
                  <div class="order-cell">
                    <span class="order-icon">
                      <i class="fa-solid fa-box"></i>
                    </span>

                    <div>
                      <strong>
                        {{ order.name || 'Unnamed Order' }}
                      </strong>

                      <small>
                        Order #{{ order.id }}
                      </small>
                    </div>
                  </div>
                </td>

                <!-- PO -->
                <td>
                  <span class="po-text">
                    {{ order.po || 'N/A' }}
                  </span>
                </td>

                <!-- DELETED BY -->
                <td>
                  <div class="deleted-user">
                    <span class="deleted-user-avatar">
                      {{
                        initial(
                          deletedByName(order)
                        )
                      }}
                    </span>

                    <span>
                      {{ deletedByName(order) }}
                    </span>
                  </div>
                </td>

                <!-- DELETED DATE -->
                <td>
                  <span class="date-text">
                    {{ formatDate(order.deleted_at) }}
                  </span>
                </td>

                <!-- ACTIONS -->
                <td>
                  <div class="action-buttons">

                    <button
                      type="button"
                      class="btn restore"
                      :disabled="processingId === order.id"
                      @click="restoreOrder(order)"
                    >
                      <i
                        :class="
                          processingId === order.id &&
                          processingAction === 'restore'
                            ? 'fa-solid fa-spinner fa-spin'
                            : 'fa-solid fa-rotate-left'
                        "
                      ></i>

                      Restore
                    </button>

                    <button
                      type="button"
                      class="btn danger"
                      :disabled="processingId === order.id"
                      @click="forceDelete(order)"
                    >
                      <i
                        :class="
                          processingId === order.id &&
                          processingAction === 'delete'
                            ? 'fa-solid fa-spinner fa-spin'
                            : 'fa-solid fa-trash'
                        "
                      ></i>

                      Permanent Delete
                    </button>

                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script>
import axios from 'axios'
import AppLayout from '../layouts/AppLayout.vue'
import PageHeader from '../layouts/PageHeader.vue'

export default {
  name: 'RecycleBinView',

  components: {
    AppLayout,
    PageHeader
  },

  data() {
    return {
      loading: false,

      orders: [],

      processingId: null,
      processingAction: ''
    }
  },

  computed: {
    /*
     * SAME LOGGED-IN USER
     * jo Factory Orders / Members / Clients
     * waghera ke header mein show hota hai.
     */
    currentUser() {
      try {
        return (
          JSON.parse(
            localStorage.getItem('user')
          ) || {}
        )
      } catch (error) {
        return {}
      }
    }
  },

  mounted() {
    this.fetchRecycleBin()
  },

  methods: {
    /*
     * AUTH HEADERS
     */
    headers() {
      const token =
        localStorage.getItem('token') ||
        localStorage.getItem('auth_token') ||
        ''

      return {
        Authorization:
          token
            ? `Bearer ${token}`
            : '',

        Accept: 'application/json'
      }
    },

    /*
     * OPEN PROFILE
     */
    openProfile() {
      if (
        this.$route?.path === '/profile'
      ) {
        return
      }

      this.$router.push('/profile')
    },

    /*
     * FETCH DELETED ORDERS
     */
    async fetchRecycleBin() {
      this.loading = true

      try {
        const response =
          await axios.get(
            '/api/orders/recycle-bin',
            {
              headers: this.headers()
            }
          )

        const data =
          Array.isArray(response.data)
            ? response.data
            : (
                response.data?.data ||
                []
              )

        this.orders =
          Array.isArray(data)
            ? data
            : []

      } catch (error) {
        console.error(
          'Recycle bin fetch error:',
          error
        )

        alert(
          error.response?.data?.message ||
          'Recycle bin load nahi hui'
        )

        this.orders = []

      } finally {
        this.loading = false
      }
    },

    /*
     * RESTORE ORDER
     */
    async restoreOrder(order) {
      if (!order?.id) {
        return
      }

      const confirmed =
        window.confirm(
          `Restore "${order.name || 'this order'}"?`
        )

      if (!confirmed) {
        return
      }

      this.processingId = order.id
      this.processingAction = 'restore'

      try {
        await axios.post(
          `/api/orders/${order.id}/restore`,
          {},
          {
            headers: this.headers()
          }
        )

        /*
         * Remove immediately from UI
         */
        this.orders =
          this.orders.filter(
            item =>
              Number(item.id) !==
              Number(order.id)
          )

      } catch (error) {
        console.error(
          'Order restore error:',
          error
        )

        alert(
          error.response?.data?.message ||
          'Order restore nahi hua'
        )

      } finally {
        this.processingId = null
        this.processingAction = ''
      }
    },

    /*
     * PERMANENT DELETE
     */
    async forceDelete(order) {
      if (!order?.id) {
        return
      }

      const confirmed =
        window.confirm(
          `Are you sure you want to permanently delete "${order.name || 'this order'}"?\n\nThis action cannot be undone.`
        )

      if (!confirmed) {
        return
      }

      this.processingId = order.id
      this.processingAction = 'delete'

      try {
        await axios.delete(
          `/api/orders/${order.id}/force-delete`,
          {
            headers: this.headers()
          }
        )

        /*
         * Remove immediately from UI
         */
        this.orders =
          this.orders.filter(
            item =>
              Number(item.id) !==
              Number(order.id)
          )

      } catch (error) {
        console.error(
          'Permanent delete error:',
          error
        )

        alert(
          error.response?.data?.message ||
          'Permanent delete nahi hua'
        )

      } finally {
        this.processingId = null
        this.processingAction = ''
      }
    },

    /*
     * DELETED USER NAME
     *
     * Backend agar deleted_by object
     * ya string kisi bhi form mein bheje
     * dono handle ho jayenge.
     */
    deletedByName(order) {
      if (!order) {
        return 'Unknown'
      }

      if (
        typeof order.deleted_by ===
        'string'
      ) {
        return (
          order.deleted_by ||
          'Unknown'
        )
      }

      if (
        order.deleted_by &&
        typeof order.deleted_by ===
        'object'
      ) {
        return (
          order.deleted_by.name ||
          'Unknown'
        )
      }

      if (order.deletedBy?.name) {
        return order.deletedBy.name
      }

      if (order.deleted_by_user?.name) {
        return order.deleted_by_user.name
      }

      return 'Unknown'
    },

    /*
     * USER INITIAL
     */
    initial(name) {
      return String(name || 'U')
        .trim()
        .charAt(0)
        .toUpperCase()
    },

    /*
     * FORMAT DATE
     */
    formatDate(date) {
      if (!date) {
        return 'N/A'
      }

      const parsed =
        new Date(date)

      if (
        Number.isNaN(
          parsed.getTime()
        )
      ) {
        return 'N/A'
      }

      return parsed.toLocaleString()
    }
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

/* =========================
   PAGE
========================= */

.page-wrap {
  min-height: 100vh;

  padding:
    0 32px 32px;

  background: #f4f5f8;
}

.recycle-content {
  padding-top: 18px;
}

/* =========================
   TABLE CARD
========================= */

.table-card {
  background: #ffffff;

  border:
    1px solid
    #e6e9ee;

  border-radius: 14px;

  box-shadow:
    0 8px 25px
    rgba(15, 23, 42, .06);

  overflow-x: auto;
}

table {
  width: 100%;

  min-width: 900px;

  border-collapse: collapse;
}

/* =========================
   TABLE HEADER
========================= */

thead {
  background: #111827;
}

th {
  padding: 14px 16px;

  color: #ffffff;

  border-bottom:
    1px solid
    #273244;

  text-align: left;

  font-size: 10px;
  font-weight: 800;

  letter-spacing: .05em;

  text-transform: uppercase;
}

/* =========================
   TABLE BODY
========================= */

td {
  padding: 14px 16px;

  border-bottom:
    1px solid
    #edf0f4;

  color: #344054;

  text-align: left;

  vertical-align: middle;

  font-size: 12px;
}

tbody tr {
  transition:
    background .15s ease;
}

tbody tr:hover {
  background: #f8fafc;
}

tbody tr:last-child td {
  border-bottom: 0;
}

/* =========================
   ORDER
========================= */

.order-cell {
  min-width: 190px;

  display: flex;
  align-items: center;

  gap: 11px;
}

.order-icon {
  width: 38px;
  height: 38px;

  flex: 0 0 38px;

  display: grid;
  place-items: center;

  background: #f1f5f9;
  color: #475569;

  border-radius: 10px;

  font-size: 12px;
}

.order-cell strong {
  display: block;

  max-width: 220px;

  overflow: hidden;

  color: #111827;

  font-size: 12px;
  font-weight: 800;

  white-space: nowrap;

  text-overflow: ellipsis;
}

.order-cell small {
  display: block;

  margin-top: 3px;

  color: #98a2b3;

  font-size: 9px;
  font-weight: 600;
}

/* =========================
   PO
========================= */

.po-text {
  display: inline-flex;

  padding: 5px 9px;

  background: #f4f5f8;

  border-radius: 7px;

  color: #475467;

  font-size: 10px;
  font-weight: 800;
}

/* =========================
   DELETED USER
========================= */

.deleted-user {
  min-width: 150px;

  display: flex;
  align-items: center;

  gap: 8px;

  color: #344054;

  font-size: 11px;
  font-weight: 700;
}

.deleted-user-avatar {
  width: 30px;
  height: 30px;

  flex: 0 0 30px;

  display: grid;
  place-items: center;

  background: #111827;
  color: #ffffff;

  border-radius: 50%;

  font-size: 10px;
  font-weight: 900;
}

/* =========================
   DATE
========================= */

.date-text {
  color: #667085;

  font-size: 10px;
  font-weight: 600;

  white-space: nowrap;
}

/* =========================
   ACTION BUTTONS
========================= */

.action-buttons {
  display: flex;
  align-items: center;

  gap: 7px;

  white-space: nowrap;
}

.btn {
  min-height: 34px;

  padding: 7px 11px;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  gap: 6px;

  border: none;

  border-radius: 8px;

  font-size: 9px;
  font-weight: 800;

  cursor: pointer;

  transition:
    transform .15s ease,
    opacity .15s ease,
    box-shadow .15s ease;
}

.btn:hover:not(:disabled) {
  transform:
    translateY(-1px);
}

.btn:disabled {
  opacity: .55;

  cursor: not-allowed;
}

/* RESTORE */

.restore {
  background: #111827;

  color: #ffffff;
}

.restore:hover:not(:disabled) {
  box-shadow:
    0 6px 14px
    rgba(17, 24, 39, .15);
}

/* DELETE */

.danger {
  background: #fff1f2;

  color: #dc2626;

  border:
    1px solid
    #fecdd3;
}

.danger:hover:not(:disabled) {
  background: #fee2e2;
}

/* =========================
   EMPTY / LOADING
========================= */

.empty {
  min-height: 180px;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 10px;

  padding: 30px;

  background: #ffffff;

  border:
    1px solid
    #e6e9ee;

  border-radius: 14px;

  color: #667085;

  font-size: 12px;
  font-weight: 800;

  box-shadow:
    0 8px 25px
    rgba(15, 23, 42, .05);
}

.empty i {
  color: #98a2b3;

  font-size: 22px;
}

/* =========================
   DARK MODE
========================= */

:global(.theme-dark) .page-wrap {
  background: #111827;
}

:global(.theme-dark) .table-card,
:global(.theme-dark) .empty {
  background: #182132;

  border-color: #334155;
}

:global(.theme-dark) td {
  color: #cbd5e1;

  border-color: #334155;
}

:global(.theme-dark) tbody tr:hover {
  background: #1e293b;
}

:global(.theme-dark) .order-cell strong {
  color: #ffffff;
}

:global(.theme-dark) .order-icon,
:global(.theme-dark) .po-text {
  background: #263244;

  color: #cbd5e1;
}

/* =========================
   MOBILE
========================= */

@media (max-width: 900px) {
  .page-wrap {
    padding:
      0 16px 20px;
  }

  .recycle-content {
    padding-top: 14px;
  }
}

@media (max-width: 600px) {
  .page-wrap {
    padding:
      0 12px 16px;
  }

  .table-card {
    border-radius: 10px;
  }
}
</style>
