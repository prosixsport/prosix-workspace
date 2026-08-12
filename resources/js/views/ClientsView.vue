<template>
    <AppLayout>
        <div class="page">
            <PageHeader
                title="Clients"
                subtitle="Add and manage clients."
                :user="currentUser"
                :photo="currentUser?.profile_photo_url"
                @profile="openProfile"
            />

            <div class="clients-header-actions">
                <button @click="openModal()" class="add-btn">
                    <i class="fa-solid fa-plus"></i> Add Client
                </button>
            </div>

            <div class="card">
                <div v-if="loading" class="empty">Loading...</div>
                <div v-else-if="clients.length === 0" class="empty">No clients yet.</div>

                <div v-else class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th width="120">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="client in clients" :key="client.id">
                                <td data-label="Name">{{ client.name }}</td>
                                <td data-label="Email">{{ client.email || '-' }}</td>
                                <td data-label="Phone">{{ client.phone || '-' }}</td>
                                <td data-label="Company">{{ client.company || '-' }}</td>
                                <td data-label="Status"><span class="badge">{{ client.status }}</span></td>
                                <td data-label="Action">
                                    <button class="icon-btn" @click="openModal(client)">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="icon-btn danger" @click="deleteClient(client.id)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="modal" class="modal-overlay" @click.self="closeModal">
                <div class="client-modal-box">
                    <button class="close" @click="closeModal">×</button>
                    <h3>{{ editingId ? 'Edit Client' : 'Add Client' }}</h3>

                    <label>Name *</label>
                    <input v-model="form.name" placeholder="Client name" />

                    <label>Email *</label>
                    <input v-model="form.email" placeholder="Email" />

                    <label v-if="!editingId">Password *</label>
                    <input
                        v-if="!editingId"
                        v-model="form.password"
                        type="password"
                        placeholder="Client password"
                    />

                    <label>Phone</label>
                    <input v-model="form.phone" placeholder="Phone" />

                    <label>Company</label>
                    <input v-model="form.company" placeholder="Company" />

                    <label>Address</label>
                    <textarea v-model="form.address" placeholder="Address"></textarea>

                    <label>Status</label>
                    <select v-model="form.status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <button class="save-btn" @click="saveClient" :disabled="saving">
                        {{ saving ? 'Saving...' : 'Save Client' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import AppLayout from '../layouts/AppLayout.vue'
import PageHeader from '../layouts/PageHeader.vue'
import axios from 'axios'

export default {
    name: 'ClientsView',
    components: { AppLayout, PageHeader },

    data() {
        return {
            loading: false,
            saving: false,
            modal: false,
            editingId: null,
            clients: [],
            form: {
                name: '',
                email: '',
                password: '',
                phone: '',
                company: '',
                address: '',
                status: 'active',
            },
        }
    },

    computed: {
        currentUser() {
            try {
                return JSON.parse(localStorage.getItem('user')) || {}
            } catch (e) {
                return {}
            }
        }
    },

    mounted() {
        this.fetchClients()
    },

    methods: {
        openProfile() {
            this.$router.push('/profile')
        },

        headers() {
            return {
                Authorization: `Bearer ${localStorage.getItem('token')}`,
                Accept: 'application/json',
            }
        },

        emptyForm() {
            return {
                name: '',
                email: '',
                password: '',
                phone: '',
                company: '',
                address: '',
                status: 'active',
            }
        },

        async fetchClients() {
            this.loading = true
            try {
                const res = await axios.get('/api/clients', { headers: this.headers() })
                this.clients = Array.isArray(res.data) ? res.data : []
            } catch (e) {
                alert(e.response?.data?.message || 'Clients load failed')
            } finally {
                this.loading = false
            }
        },

        openModal(client = null) {
            if (client) {
                this.editingId = client.id
                this.form = {
                    name: client.name || '',
                    email: client.email || '',
                    password: '',
                    phone: client.phone || '',
                    company: client.company || '',
                    address: client.address || '',
                    status: client.status || 'active',
                }
            } else {
                this.editingId = null
                this.form = this.emptyForm()
            }

            this.modal = true
        },

        closeModal() {
            this.modal = false
        },

        async saveClient() {
            if (!this.form.name) {
                alert('Client name required')
                return
            }

            if (!this.form.email) {
                alert('Client email required')
                return
            }

            if (!this.editingId && !this.form.password) {
                alert('Client password required')
                return
            }

            this.saving = true

            try {
                if (this.editingId) {
                    const payload = { ...this.form }
                    delete payload.password

                    await axios.put(`/api/clients/${this.editingId}`, payload, {
                        headers: this.headers()
                    })
                } else {
                    await axios.post('/api/clients', this.form, {
                        headers: this.headers()
                    })
                }

                this.closeModal()
                this.fetchClients()
            } catch (e) {
                alert(e.response?.data?.message || 'Client save failed')
            } finally {
                this.saving = false
            }
        },

        async deleteClient(id) {
            if (!confirm('Delete this client?')) return

            try {
                await axios.delete(`/api/clients/${id}`, { headers: this.headers() })
                this.fetchClients()
            } catch (e) {
                alert(e.response?.data?.message || 'Client delete failed')
            }
        },
    },
}
</script>

<style scoped>
.page { padding: 0 24px 24px; background: #f4f5f8; min-height: 100vh; }
.clients-header-actions { display: flex; justify-content: flex-end; align-items: center; margin: 14px 0 18px; }
.head { display: flex; justify-content: space-between; gap: 15px; align-items: center; margin-bottom: 18px; }
.head h2 { margin: 0; font-size: 28px; font-weight: 900; color: #111; }
.head p { margin: 4px 0 0; color: #6b7280; }
.add-btn, .save-btn {border: none; background: #111; color: #fff; border-radius: 12px;padding: 12px 18px; font-weight: 900; cursor: pointer;}
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; overflow: hidden; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 760px; }
th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f1f1f1; font-size: 14px; }
th { background: #fafafa; color: #6b7280; font-size: 12px; text-transform: uppercase; }
.badge { background: #111; color: #fff; border-radius: 999px; padding: 5px 10px; font-size: 11px; font-weight: 800; }
.icon-btn { border: none; background: #f3f4f6; width: 34px; height: 34px; border-radius: 9px; margin-right: 6px; cursor: pointer; }
.icon-btn.danger { background: #fee2e2; color: #b91c1c; }
.empty { padding: 40px; text-align: center; color: #6b7280; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); display: flex; align-items: center; justify-content: center; z-index: 99999; padding: 15px; }
.close { position: absolute; right: 15px; top: 12px; border: none; background: #f3f4f6; width: 34px; height: 34px; border-radius: 10px; font-size: 22px; cursor: pointer; }
.client-modal-box h3 { margin: 0 0 18px; font-weight: 900; }
label { display: block; margin: 12px 0 6px; font-size: 13px; font-weight: 800; }
input, textarea, select { width: 100%; border: 1.5px solid #d1d5db; border-radius: 10px; padding: 11px 12px; outline: none; }
textarea { min-height: 80px; resize: vertical; }
.save-btn { width: 100%; margin-top: 18px; }
.client-modal-box {display: block; background: #fff; width: 460px; max-width: 100%; border-radius: 20px; padding: 24px; position: relative; z-index: 100000;}
@media (max-width: 600px) {
    .page { padding: 0 16px 16px; }
    .head { flex-direction: column; align-items: stretch; }
    .add-btn { width: 100%; }
}
@media (max-width: 768px) {
    .page { padding: 0 14px 14px; }
    .head { flex-direction: column; align-items: stretch; gap: 12px; }
    .head h2 { font-size: 24px; }
    .add-btn { width: 100%; display: flex; justify-content: center; align-items: center; gap: 8px; }
    .card { background: transparent; border: none; border-radius: 0; overflow: visible; }
    .table-wrap { overflow: visible; }
    table, thead, tbody, tr, th, td { display: block; width: 100%; min-width: 0; }
    table { min-width: 0; }
    thead { display: none; }
    tr { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 14px; margin-bottom: 12px; box-shadow: 0 8px 22px rgba(0,0,0,.04); }
    td { border-bottom: none; padding: 9px 0; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; font-size: 13px; word-break: break-word; }
    td::before { content: attr(data-label); font-weight: 900; color: #6b7280; flex-shrink: 0; min-width: 84px; }
    td[data-label="Action"] { justify-content: flex-end; padding-top: 14px; border-top: 1px solid #f1f1f1; margin-top: 6px; }
    td[data-label="Action"]::before { display: none; }
    .icon-btn { width: 40px; height: 40px; }
    .modal-overlay { align-items: flex-start; overflow-y: auto; padding: 12px; }
    .client-modal-box { width: 100%; max-width: 100%; margin-top: 10px; border-radius: 18px; padding: 18px; }
}
</style>
