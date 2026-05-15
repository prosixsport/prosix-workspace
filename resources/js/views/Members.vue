<template>
    <AppLayout>
        <div class="p-4">

            <!-- Toast -->
            <div v-if="toast.show" class="toast-msg" :class="toast.type">
                <i :class="toast.type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                {{ toast.text }}
            </div>

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Members</h4>
                <button @click="openInviteModal" class="btn text-white fw-semibold" style="background:#ff3d57;">
                    + Invite Member
                </button>
            </div>

            <!-- Members Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">Member</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="members.length === 0">
                                <td colspan="6" class="text-center py-5 text-muted">No members yet</td>
                            </tr>
                            <tr v-for="member in members" :key="member.id">
                                <td class="px-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold"
                                            style="width:36px;height:36px;background:#ff3d57;font-size:14px;">
                                            {{ member.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="fw-semibold">{{ member.name }}</span>
                                    </div>
                                </td>
                                <td class="text-muted">{{ member.email }}</td>
                                <td>
                                    <span class="badge" :class="roleBadge(member.role)">
                                        {{ member.role }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" :class="member.is_active ? 'bg-success' : 'bg-danger'">
                                        {{ member.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ formatDate(member.created_at) }}</td>
                                <td>
                                    <!-- super_admin ka koi action nahi -->
                                    <template v-if="member.role === 'super_admin'">
                                        <span class="text-muted small">—</span>
                                    </template>
                                    <template v-else>
                                        <button @click="toggleStatus(member)" class="btn btn-sm btn-outline-secondary me-1">
                                            {{ member.is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button @click="deleteMember(member.id)" class="btn btn-sm btn-outline-danger">
                                            Delete
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invite Modal -->
            <div v-if="showInviteModal" class="modal-overlay" @click.self="closeInviteModal">
                <div class="card border-0 shadow-lg" style="width:450px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Invite Member</h5>
                            <button @click="closeInviteModal" class="btn-close"></button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Name</label>
                            <input v-model="inviteForm.name" type="text" class="form-control" placeholder="Member name" />
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input v-model="inviteForm.email" type="email" class="form-control" placeholder="member@example.com" />
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Role</label>
                            <select v-model="inviteForm.role" class="form-select">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button @click="closeInviteModal" class="btn btn-outline-secondary">Cancel</button>
                            <button
                                @click="inviteMember"
                                class="btn text-white d-flex align-items-center gap-2"
                                style="background:#ff3d57;"
                                :disabled="loading"
                            >
                                <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                                <span>{{ loading ? 'Inviting...' : 'Send Invite' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script>
import AppLayout from '../layouts/AppLayout.vue'
import axios from 'axios'

export default {
    name: 'Members',
    components: { AppLayout },
    data() {
        return {
            members: [],
            showInviteModal: false,
            loading: false,
            inviteForm: { name: '', email: '', role: 'member' },
            toast: { show: false, text: '', type: 'success' }
        }
    },
    mounted() {
        this.fetchMembers()
    },
    methods: {
        headers() {
            return { Authorization: `Bearer ${localStorage.getItem('token')}` }
        },

        showToast(text, type = 'success') {
            this.toast = { show: true, text, type }
            setTimeout(() => { this.toast.show = false }, 3000)
        },

        openInviteModal() {
            this.inviteForm = { name: '', email: '', role: 'member' }
            this.showInviteModal = true
        },

        closeInviteModal() {
            this.showInviteModal = false
        },

        async fetchMembers() {
            try {
                const res = await axios.get('/api/members', { headers: this.headers() })
                this.members = Array.isArray(res.data) ? res.data : (res.data?.data || [])
            } catch (e) {
                console.error(e)
                this.showToast('Members load nahi hue', 'error')
            }
        },

        async inviteMember() {
            if (!this.inviteForm.name.trim() || !this.inviteForm.email.trim()) {
                this.showToast('Name aur Email zaroori hain', 'error')
                return
            }

            this.loading = true
            try {
                const res = await axios.post('/api/members/invite', this.inviteForm, { headers: this.headers() })

                // Naya member list mein add karo
                if (res.data.user) {
                    this.members.push(res.data.user)
                }

                this.showToast('Member successfully invite ho gaya! ✅')
                this.closeInviteModal()
            } catch (e) {
                console.error(e)
                this.showToast(e.response?.data?.message || 'Invite nahi hua', 'error')
            } finally {
                this.loading = false
            }
        },

        async toggleStatus(member) {
            try {
                const res = await axios.post(`/api/members/${member.id}/toggle`, {}, { headers: this.headers() })
                member.is_active = res.data.is_active
                this.showToast(`Member ${res.data.is_active ? 'activate' : 'deactivate'} ho gaya`)
            } catch (e) {
                console.error(e)
                this.showToast('Status change nahi hua', 'error')
            }
        },

        async deleteMember(id) {
            if (!confirm('Delete this member?')) return
            try {
                await axios.delete(`/api/members/${id}`, { headers: this.headers() })
                this.members = this.members.filter(m => m.id !== id)
                this.showToast('Member delete ho gaya')
            } catch (e) {
                console.error(e)
                this.showToast('Delete nahi hua', 'error')
            }
        },

        roleBadge(role) {
            const badges = {
                'super_admin': 'bg-danger',
                'admin': 'bg-warning text-dark',
                'member': 'bg-primary',
            }
            return badges[role] || 'bg-secondary'
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString()
        }
    }
}
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

/* Toast */
.toast-msg {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 99999;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    animation: slideIn 0.3s ease;
}

.toast-msg.success {
    background: #00c875;
    color: #fff;
}

.toast-msg.error {
    background: #ff3d57;
    color: #fff;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>