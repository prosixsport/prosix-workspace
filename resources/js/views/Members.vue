<template>
    <AppLayout>
        <div class="members-page">

            <!-- Toast -->
            <div v-if="toast.show" class="toast-msg" :class="toast.type">
                <i :class="toast.type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                {{ toast.text }}
            </div>

            <!-- Header -->
            <div class="members-header">
                <div>
                    <h4>Members</h4>
                    <p>Manage Prosix team members and roles</p>
                </div>

                <button @click="openInviteModal" class="primary-btn">
                    <i class="fa-solid fa-user-plus"></i>
                    Invite Member
                </button>
            </div>

            <!-- Members Table -->
            <div class="members-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-if="members.length === 0">
                                <td colspan="6" class="empty-text">
                                    No members yet
                                </td>
                            </tr>

                            <tr v-for="member in members" :key="member.id">
                                <td>
                                    <div class="member-info">
                                      <div class="member-avatar" :class="{ 'has-photo': member.profile_photo_url }">
    <img
        v-if="member.profile_photo_url"
        :src="member.profile_photo_url"
        class="member-avatar-img"
        alt="Profile"
    />
    <span v-else>
        {{ member.name.charAt(0).toUpperCase() }}
    </span>
</div>
                                        <span>{{ member.name }}</span>
                                    </div>
                                </td>

                                <td class="text-muted">{{ member.email }}</td>

                                <td>
                                    <span class="role-badge" :class="roleBadge(member.role)">
                                        {{ formatRole(member.role) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="status-badge" :class="member.is_active ? 'active' : 'inactive'">
                                        {{ member.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="text-muted small">
                                    {{ formatDate(member.created_at) }}
                                </td>

                                <td class="text-end">
                                    <template v-if="member.role === 'super_admin'">
                                        <span class="text-muted small">—</span>
                                    </template>

                                  <template v-else>
    <button
        @click="toggleOrderCreatePermission(member)"
        class="permission-btn me-1"
        :class="member.can_create_orders ? 'allowed' : 'blocked'"
    >
        {{ member.can_create_orders ? 'Can Add Orders' : 'No Order Access' }}
    </button>

    <button @click="toggleStatus(member)" class="outline-btn me-1">

                                            {{ member.is_active ? 'Deactivate' : 'Activate' }}
                                        </button>

                                        <button @click="deleteMember(member.id)" class="danger-btn">
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
                <div class="invite-modal">
                    <div class="modal-header-custom">
                        <div>
                            <h5>Invite Member</h5>
                            <p>Send Prosix workspace invitation</p>
                        </div>

                        <button @click="closeInviteModal" class="close-btn">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="modal-body-custom">
                        <div class="field-group">
                            <label>Name</label>
                            <input
                                v-model="inviteForm.name"
                                type="text"
                                placeholder="Member name"
                            />
                        </div>

                        <div class="field-group">
                            <label>Email</label>
                            <input
                                v-model="inviteForm.email"
                                type="email"
                                placeholder="member@example.com"
                            />
                        </div>

                        <div class="field-group">
                            <label>Role</label>
                            <select v-model="inviteForm.role">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer-custom">
                        <button @click="closeInviteModal" class="cancel-btn">
                            Cancel
                        </button>

                        <button
                            @click="inviteMember"
                            class="primary-btn"
                            :disabled="loading"
                        >
                            <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                            <span>{{ loading ? 'Inviting...' : 'Send Invite' }}</span>
                        </button>
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
           inviteForm: {
    name: '',
    email: '',
    role: 'member',
    is_active: true
},
            toast: {
                show: false,
                text: '',
                type: 'success'
            }
        }
    },

    mounted() {
        this.fetchMembers()
    },

    methods: {
        headers() {
            return {
                Authorization: `Bearer ${localStorage.getItem('token')}`
            }
        },

        showToast(text, type = 'success') {
            this.toast = { show: true, text, type }

            setTimeout(() => {
                this.toast.show = false
            }, 3000)
        },

        openInviteModal() {
            this.inviteForm = {
                name: '',
                email: '',
                role: 'member'
            }

            this.showInviteModal = true
        },

    closeInviteModal() {
    if (this.loading) return
    this.showInviteModal = false
    this.inviteForm = { name: '', email: '', role: 'member' }
},

     async fetchMembers() {
    try {
        const res = await axios.get('/api/members', {
            headers: this.headers()
        })

        this.members = Array.isArray(res.data)
            ? res.data
            : (res.data?.data || [])
    } catch (e) {
        console.error(e)
        this.showToast('Failed to load members', 'error')
    }
},

async inviteMember() {
    if (!this.inviteForm.name.trim() || !this.inviteForm.email.trim()) {
        this.showToast('Name and email are required', 'error')
        return
    }

    this.loading = true

    try {
        const res = await axios.post('/api/members/invite', {
            ...this.inviteForm,
            is_active: true
        }, {
            headers: this.headers()
        })

        if (res.data.user) {
            this.members.push({ ...res.data.user, is_active: true })
        }

        this.showToast('Member invited successfully')
        this.showInviteModal = false
    } catch (e) {
        console.error(e)
        this.showToast(e.response?.data?.message || 'Failed to send invite', 'error')
    } finally {
        this.loading = false
    }
},
async toggleOrderCreatePermission(member) {
    try {
        const res = await axios.post(`/api/members/${member.id}/order-create-permission`, {}, {
            headers: this.headers()
        })

        member.can_create_orders = res.data.user.can_create_orders

        this.showToast(
            member.can_create_orders
                ? 'Order create access enabled'
                : 'Order create access disabled'
        )
    } catch (e) {
        console.error(e)
this.showToast(e.response?.data?.message || 'Permission update failed', 'error')
    }
},
async toggleStatus(member) {
    try {
        const res = await axios.post(`/api/members/${member.id}/toggle`, {}, {
            headers: this.headers()
        })

        member.is_active = res.data.is_active

        this.showToast(`Member ${res.data.is_active ? 'activated' : 'deactivated'} successfully`)
    } catch (e) {
        console.error(e)
        this.showToast('Failed to update member status', 'error')
    }
},

async deleteMember(id) {
    if (!confirm('Delete this member?')) return

    try {
        await axios.delete(`/api/members/${id}`, {
            headers: this.headers()
        })

        this.members = this.members.filter(m => m.id !== id)
        this.showToast('Member deleted successfully')
    } catch (e) {
        console.error(e)
        this.showToast('Failed to delete member', 'error')
    }
},

        roleBadge(role) {
            const badges = {
                super_admin: 'super-admin',
                admin: 'admin',
                member: 'member'
            }

            return badges[role] || 'member'
        },

        formatRole(role) {
            if (!role) return 'Member'

            return role
                .replace('_', ' ')
                .replace(/\b\w/g, c => c.toUpperCase())
        },

        formatDate(date) {
            if (!date) return '—'
            return new Date(date).toLocaleDateString()
        }
    }
}
</script>

<style scoped>
.members-page {
    padding: 28px;
    background: #f6f7fb;
    min-height: 100vh;
}

/* Header */
.members-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.members-header h4 {
    margin: 0;
    color: #000;
    font-size: 24px;
    font-weight: 900;
}

.members-header p {
    margin: 4px 0 0;
    color: #6b7280;
    font-size: 14px;
}

/* Buttons */
.primary-btn {
    min-height: 42px;
    border: none;
    background: #000;
    color: #fff;
    border-radius: 10px;
    padding: 0 18px;
    font-size: 14px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: 0.2s;
}

.primary-btn:hover:not(:disabled) {
    background: #222;
    transform: translateY(-1px);
}

.primary-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.outline-btn {
    height: 34px;
    border: 1px solid #000;
    background: #fff;
    color: #000;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 700;
}

.outline-btn:hover { background: #000; color: #fff; }

.danger-btn {
    height: 34px;
    border: 1px solid #dc2626;
    background: #fff;
    color: #dc2626;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 700;
}
.permission-btn {
    height: 34px;
    border: 1px solid #111;
    background: #fff;
    color: #111;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 800;
}

.permission-btn.allowed {
    background: #16a34a;
    border-color: #16a34a;
    color: #fff;
}

.permission-btn.blocked {
    background: #fff;
    border-color: #111;
    color: #111;
}

.danger-btn:hover { background: #dc2626; color: #fff; }

.cancel-btn {
    height: 42px;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #374151;
    border-radius: 10px;
    padding: 0 18px;
    font-size: 14px;
    font-weight: 700;
}

.cancel-btn:hover { background: #f3f4f6; }

/* Card */
.members-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 16px 40px rgba(0,0,0,0.06);
    overflow: hidden;
}

.table thead th {
    background: #000;
    color: #fff;
    border: none;
    padding: 15px 18px;
    font-size: 13px;
    font-weight: 800;
    white-space: nowrap;
}

.table tbody td {
    padding: 16px 18px;
    border-color: #eef0f4;
    font-size: 14px;
}

.empty-text {
    text-align: center;
    padding: 50px !important;
    color: #6b7280;
}

/* Member */
.member-info {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 800;
    color: #111;
}

.member-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #000;
    color: #fff;
    font-size: 14px;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.member-avatar.has-photo {
    background: #fff;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.member-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

/* Badges */
.role-badge,
.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    border-radius: 999px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 900;
    text-transform: capitalize;
}

.role-badge.super-admin { background: #000; color: #fff; }
.role-badge.admin { background: #fff; color: #000; border: 1px solid #000; }
.role-badge.member { background: #f3f4f6; color: #111; border: 1px solid #d1d5db; }
.status-badge.active { background: #000; color: #fff; }
.status-badge.inactive { background: #fff; color: #000; border: 1px solid #000; }

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 16px;
}

.invite-modal {
    width: 450px;
    max-width: 100%;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 35px 100px rgba(0,0,0,0.35);
    overflow: hidden;
}

.modal-header-custom {
    background: #000;
    color: #fff;
    padding: 22px 24px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
}

.modal-header-custom h5 { margin: 0; font-size: 20px; font-weight: 900; }
.modal-header-custom p { margin: 4px 0 0; color: #d1d5db; font-size: 13px; }

.close-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: rgba(255,255,255,0.12);
    color: #fff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-btn:hover { background: #fff; color: #000; }

.modal-body-custom { padding: 24px; }

.field-group { margin-bottom: 16px; }

.field-group label {
    display: block;
    margin-bottom: 7px;
    color: #111;
    font-size: 13px;
    font-weight: 900;
}

.field-group input,
.field-group select {
    width: 100%;
    height: 46px;
    border: 1.5px solid #d1d5db;
    border-radius: 10px;
    padding: 0 13px;
    color: #111;
    font-size: 14px;
    font-weight: 600;
    outline: none;
    background: #fff;
}

.field-group input:focus,
.field-group select:focus {
    border-color: #000;
    box-shadow: 0 0 0 4px rgba(0,0,0,0.08);
}

.modal-footer-custom {
    padding: 16px 24px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Toast */
.toast-msg {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 99999;
    padding: 13px 20px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.18);
    animation: slideIn 0.3s ease;
}

.toast-msg.success { background: #000; color: #fff; }
.toast-msg.error { background: #fff; color: #000; border: 1px solid #000; }

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===========================
   MOBILE RESPONSIVE
   =========================== */
@media (max-width: 768px) {
    .members-page { padding: 16px; }

    .members-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .members-header h4 { font-size: 20px; }
    .primary-btn { width: 100%; }

/* Hide table and show cards */
    .members-card { background: transparent; border: none; box-shadow: none; overflow: visible; }

    .table-responsive { overflow: visible; }

    /* thead hide */
    .table thead { display: none; }

    /* tbody rows ko cards banao */
    .table tbody { display: flex; flex-direction: column; gap: 12px; }

    .table tbody tr {
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        padding: 16px;
        gap: 10px;
    }

    /* Empty row */
    .table tbody tr td[colspan] {
        text-align: center;
        padding: 30px !important;
        color: #6b7280;
        background: #fff;
        border-radius: 14px;
    }

    /* Har td ko row banao */
    .table tbody td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 !important;
        border: none !important;
        font-size: 13px;
    }

    /* Member info top par full width */
    .table tbody td:first-child {
        border-bottom: 1px solid #f0f1f3 !important;
        padding-bottom: 12px !important;
        margin-bottom: 2px;
        justify-content: flex-start;
    }

    .member-info { font-size: 14px; }

    /* Label add karo har td ke liye data-label se */
    .table tbody td:nth-child(2)::before { content: 'Email:'; font-weight: 800; color: #6b7280; font-size: 11px; }
    .table tbody td:nth-child(3)::before { content: 'Role:'; font-weight: 800; color: #6b7280; font-size: 11px; }
    .table tbody td:nth-child(4)::before { content: 'Status:'; font-weight: 800; color: #6b7280; font-size: 11px; }
    .table tbody td:nth-child(5)::before { content: 'Joined:'; font-weight: 800; color: #6b7280; font-size: 11px; }
    .table tbody td:nth-child(6)::before { content: 'Actions:'; font-weight: 800; color: #6b7280; font-size: 11px; }

    /* Actions buttons */
    .table tbody td:last-child { margin-top: 4px; }
    .outline-btn, .danger-btn { height: 32px; font-size: 11px; padding: 0 10px; }

    /* Modal mobile */
    .modal-overlay { align-items: flex-end; padding: 0; }
    .invite-modal { border-radius: 18px 18px 0 0; width: 100%; max-width: 100%; }
    .modal-body-custom { padding: 20px 16px; }
    .modal-footer-custom { padding: 14px 16px; }
    .cancel-btn, .primary-btn { flex: 1; }

    /* Toast mobile */
    .toast-msg { right: 12px; left: 12px; top: 12px; }
}
</style>
