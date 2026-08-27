<template>
    <AppLayout>
        <div class="page">
            <PageHeader
                title="Try Login Customers"
                subtitle="Review and manage customer signup requests."
                :user="currentUser"
                :photo="currentUser?.profile_photo_url"
                @profile="openProfile"
            />

            <div class="card">
                <div v-if="loading" class="empty-state">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Loading requests...</span>
                </div>

                <div v-else-if="requests.length === 0" class="empty-state">
                    <i class="fa-solid fa-user-clock"></i>
                    <span>No signup requests.</span>
                </div>

                <div v-else class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Company</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="item in requests" :key="item.id">
                                <td data-label="Name">{{ item.name || '-' }}</td>
                                <td data-label="Email">{{ item.email || '-' }}</td>
                                <td data-label="Phone">{{ item.phone || '-' }}</td>
                                <td data-label="Company">{{ item.company || '-' }}</td>
                                <td data-label="Address">{{ item.address || '-' }}</td>

                                <td data-label="Status">
                                    <span class="status-badge" :class="statusClass(item.account_status)">
                                        {{ formatStatus(item.account_status) }}
                                    </span>
                                </td>

                                <td data-label="Actions">
                                    <div class="actions">
                                        <button
                                            v-if="item.account_status !== 'active'"
                                            type="button"
                                            class="action-button approve-button"
                                            :disabled="isProcessing(item.id)"
                                            @click="changeStatus(item, 'approve')"
                                        >
                                            <i :class="buttonIcon(item.id, 'approve', 'fa-solid fa-check')"></i>
                                            Approve
                                        </button>

                                        <button
                                            v-if="item.account_status !== 'rejected'"
                                            type="button"
                                            class="action-button reject-button"
                                            :disabled="isProcessing(item.id)"
                                            @click="changeStatus(item, 'reject')"
                                        >
                                            <i :class="buttonIcon(item.id, 'reject', 'fa-solid fa-ban')"></i>
                                            Reject
                                        </button>

                                        <button
                                            type="button"
                                            class="action-button delete-button"
                                            :disabled="isProcessing(item.id)"
                                            @click="deleteCustomer(item)"
                                        >
                                            <i :class="buttonIcon(item.id, 'delete', 'fa-solid fa-trash')"></i>
                                            Delete
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
    name: 'TryLoginCustomersView',

    components: {
        AppLayout,
        PageHeader
    },

    data() {
        return {
            loading: false,
            requests: [],
            processingId: null,
            processingAction: null
        }
    },

    computed: {
        currentUser() {
            try {
                return JSON.parse(localStorage.getItem('user')) || {}
            } catch {
                return {}
            }
        }
    },

    mounted() {
        this.loadRequests()
    },

    methods: {
        openProfile() {
            this.$router.push('/profile')
        },

        headers() {
            return {
                Authorization: `Bearer ${localStorage.getItem('token')}`,
                Accept: 'application/json'
            }
        },

        firstError(error) {
            const errors = error.response?.data?.errors
            return errors ? Object.values(errors).flat()[0] : null
        },

        async loadRequests() {
            this.loading = true

            try {
                const response = await axios.get('/api/client-requests', {
                    headers: this.headers()
                })

                this.requests = Array.isArray(response.data)
                    ? response.data
                    : response.data?.data || []
            } catch (error) {
                alert(this.firstError(error) || error.response?.data?.message || 'Requests load failed.')
            } finally {
                this.loading = false
            }
        },

        isProcessing(id) {
            return this.processingId === id
        },

        buttonIcon(id, action, normalIcon) {
            if (this.processingId === id && this.processingAction === action) {
                return 'fa-solid fa-spinner fa-spin'
            }

            return normalIcon
        },

        formatStatus(status) {
            if (!status) return 'Unknown'
            return status.charAt(0).toUpperCase() + status.slice(1)
        },

        statusClass(status) {
            return {
                pending: status === 'pending',
                active: status === 'active',
                rejected: status === 'rejected'
            }
        },

        async changeStatus(item, action) {
            const label = action === 'approve' ? 'approve' : 'reject'

            if (!confirm(`Are you sure you want to ${label} ${item.name}?`)) {
                return
            }

            this.processingId = item.id
            this.processingAction = action

            try {
                const response = await axios.patch(
                    `/api/client-requests/${item.id}/${action}`,
                    {},
                    { headers: this.headers() }
                )

                item.account_status = action === 'approve' ? 'active' : 'rejected'
                item.status = item.account_status

                alert(response.data?.message || `Customer ${label}d successfully.`)
            } catch (error) {
                alert(this.firstError(error) || error.response?.data?.message || 'Action failed.')
            } finally {
                this.processingId = null
                this.processingAction = null
            }
        },

        async deleteCustomer(item) {
            if (!confirm(`Permanently delete ${item.name} and their login account?`)) {
                return
            }

            this.processingId = item.id
            this.processingAction = 'delete'

            try {
                await axios.delete(`/api/clients/${item.id}`, {
                    headers: this.headers()
                })

                this.requests = this.requests.filter(request => request.id !== item.id)
            } catch (error) {
                alert(this.firstError(error) || error.response?.data?.message || 'Customer delete failed.')
            } finally {
                this.processingId = null
                this.processingAction = null
            }
        }
    }
}
</script>

<style scoped>
.page { min-height: 100vh; padding: 0 24px 24px; background: #f4f5f8; }
.card { overflow: hidden; background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; }
.table-wrap { overflow-x: auto; }
table { width: 100%; min-width: 1080px; border-collapse: collapse; }
th, td { padding: 14px 16px; border-bottom: 1px solid #f1f1f1; text-align: left; font-size: 13px; }
th { background: #fafafa; color: #6b7280; font-size: 11px; font-weight: 900; text-transform: uppercase; }
.empty-state { min-height: 180px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: #6b7280; }
.empty-state i { font-size: 24px; }
.status-badge { display: inline-flex; padding: 6px 10px; border-radius: 999px; font-size: 11px; font-weight: 900; }
.status-badge.pending { background: #fef3c7; color: #92400e; }
.status-badge.active { background: #dcfce7; color: #166534; }
.status-badge.rejected { background: #fee2e2; color: #991b1b; }
.actions { display: flex; flex-wrap: wrap; gap: 6px; }
.action-button { min-height: 34px; display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border: 0; border-radius: 8px; color: #fff; font-size: 11px; font-weight: 800; cursor: pointer; }
.action-button:disabled { opacity: .6; cursor: not-allowed; }
.approve-button { background: #16803a; }
.reject-button { background: #c62828; }
.delete-button { background: #111827; }

@media (max-width: 768px) {
    .page { padding: 0 14px 14px; }
    .card { overflow: visible; background: transparent; border: 0; }
    .table-wrap { overflow: visible; }
    table, tbody, tr, td { display: block; width: 100%; min-width: 0; }
    thead { display: none; }
    tr { margin-bottom: 12px; padding: 14px; background: #fff; border: 1px solid #e5e7eb; border-radius: 15px; }
    td { display: flex; justify-content: space-between; gap: 14px; padding: 9px 0; border: 0; word-break: break-word; }
    td::before { content: attr(data-label); min-width: 80px; color: #6b7280; font-weight: 900; }
    td[data-label="Actions"] { margin-top: 7px; padding-top: 13px; border-top: 1px solid #eee; }
    td[data-label="Actions"]::before { display: none; }
    .actions { width: 100%; }
    .action-button { flex: 1; justify-content: center; }
}
</style>
