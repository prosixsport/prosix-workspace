<template>
    <AppLayout>
        <div class="page">
            <PageHeader title="Try Login Customers" subtitle="Review customer signup requests." :user="currentUser" />
            <div class="card">
                <div v-if="loading" class="empty">Loading...</div>
                <div v-else-if="requests.length === 0" class="empty">No signup requests.</div>
                <div v-else class="table-wrap">
                    <table>
                        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Company</th><th>Address</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="item in requests" :key="item.id">
                                <td>{{ item.name }}</td><td>{{ item.email }}</td><td>{{ item.phone }}</td>
                                <td>{{ item.company || '-' }}</td><td>{{ item.address }}</td>
                                <td><span class="badge">{{ item.account_status }}</span></td>
                                <td>
                                    <button v-if="item.account_status === 'pending'" class="approve" @click="decide(item.id, 'approve')">Approve</button>
                                    <button v-if="item.account_status !== 'rejected'" class="reject" @click="decide(item.id, 'reject')">Reject</button>
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
    components: { AppLayout, PageHeader },
    data: () => ({ loading: false, requests: [] }),
    computed: {
        currentUser() {
            try { return JSON.parse(localStorage.getItem('user')) || {} } catch { return {} }
        }
    },
    mounted() { this.load() },
    methods: {
        headers() { return { Authorization: `Bearer ${localStorage.getItem('token')}`, Accept: 'application/json' } },
        async load() {
            this.loading = true
            try {
                const res = await axios.get('/api/client-requests', { headers: this.headers() })
                this.requests = res.data
            } catch (e) { alert(e.response?.data?.message || 'Requests load failed') }
            finally { this.loading = false }
        },
        async decide(id, action) {
            if (!confirm(`Are you sure you want to ${action} this customer?`)) return
            try {
                await axios.patch(`/api/client-requests/${id}/${action}`, {}, { headers: this.headers() })
                await this.load()
            } catch (e) { alert(e.response?.data?.message || 'Action failed') }
        }
    }
}
</script>

<style scoped>
.page{padding:24px}.card{background:#fff;border-radius:14px;padding:18px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse}th,td{padding:12px;border-bottom:1px solid #eee;text-align:left}.empty{text-align:center;padding:30px;color:#777}.badge{padding:5px 9px;border-radius:20px;background:#eee}.approve,.reject{border:0;border-radius:7px;padding:8px 11px;margin:2px;color:#fff;cursor:pointer}.approve{background:#16803a}.reject{background:#c62828}
</style>
