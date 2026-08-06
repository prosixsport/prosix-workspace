<template>
    <div class="prosix-layout">

        <!-- MOBILE TOPBAR -->
        <div class="mobile-topbar">
            <div class="mobile-logo">
                <div class="logo-icon">P</div>
                <span>Prosix Sports</span>
            </div>
            <button class="hamburger-btn" @click="sidebarOpen = true">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <!-- OVERLAY -->
        <div
            class="sidebar-overlay"
            :class="{ open: sidebarOpen }"
            @click="sidebarOpen = false"
        ></div>

        <!-- SIDEBAR -->
        <aside
            v-if="$route.path !== '/orders'"
            class="prosix-sidebar"
            :class="{ 'sidebar-open': sidebarOpen }"
        >

            <button class="sidebar-close-btn" @click="sidebarOpen = false">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="sidebar-logo">
                <div class="logo-icon">P</div>
                <div>
                    <h5>Prosix Sports</h5>
                    <small>Work Management</small>
                </div>
            </div>

            <div class="user-card" @click="openProfile">
                <div class="user-avatar" :class="{ 'has-photo': userPhoto }">
                    <img v-if="userPhoto" :src="userPhoto" class="user-avatar-img" alt="Profile" />
                    <span v-else>{{ userInitial }}</span>
                </div>
                <div class="user-info">
                    <div class="user-name">{{ user?.name || 'User' }}</div>
                    <div class="user-role">{{ formatRole(user?.role) }}</div>
                </div>
                <i class="fa-solid fa-pen edit-profile-icon"></i>
            </div>

            <nav class="sidebar-nav">
                <router-link
                    to="/dashboard"
                    class="nav-link-custom"
                    :class="{ active: $route.path === '/dashboard' }"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon"><i class="fa-solid fa-house"></i></span>
                    <span>Home</span>
                </router-link>

                <!-- FACTORY ORDERS -->
                <router-link
                    :to="{ path: '/orders', query: { type: 'factory' } }"
                    class="nav-link-custom"
                    :class="{
                        active:
                            $route.path === '/orders' &&
                            ($route.query.type || 'factory') === 'factory'
                    }"
                    @click="clearOrderBadge(); sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-industry"></i>
                    </span>

                    <span>Factory Orders</span>

                    <span
                        v-if="showOrderBadge"
                        class="order-badge"
                    >
                        {{ orderNotificationCount }}
                    </span>
                </router-link>

                <!-- TEAMSTORE ORDERS -->
               <router-link
    to="/teamstore-orders"
    class="nav-link-custom"
    :class="{
        active: $route.path === '/teamstore-orders'
    }"
    @click="sidebarOpen = false"
>
    <span class="nav-icon">
        <i class="fa-solid fa-store"></i>
    </span>

    <span>TeamStore Orders</span>
</router-link>

                <!-- PLACE ORDERS -->
                <router-link
                    to="/place-orders"
                    class="nav-link-custom"
                    :class="{ active: $route.path === '/place-orders' }"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </span>

                    <span>Place Orders</span>

                    <span
                        v-if="placeOrderNotificationCount > 0"
                        class="order-badge"
                    >
                        {{ placeOrderNotificationCount }}
                    </span>
                </router-link>

                <!-- ARTWORK REQUESTS -->
                <!-- <router-link
                    :to="{ path: '/orders', query: { type: 'artwork_request' } }"
                    class="nav-link-custom"
                    :class="{
                        active:
                            $route.path === '/orders' &&
                            $route.query.type === 'artwork_request'
                    }"
                    @click="clearOrderBadge(); sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-palette"></i>
                    </span>

                    <span>Artwork Requests</span>
                </router-link> -->
                <router-link
to="/artwork-requests"
                    class="nav-link-custom"
                    :class="{
                        active:
                            $route.path === '/orders' &&
                            $route.query.type === 'artwork_request'
                    }"
                    @click="clearOrderBadge(); sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-palette"></i>
                    </span>

                    <span>Artwork Requests</span>
                </router-link>


                <router-link
                    v-if="isSuperAdmin || isAdmin"
                    to="/members"
                    class="nav-link-custom"
                    :class="{ active: $route.path === '/members' }"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                    <span>Members</span>
                </router-link>


                <router-link
    v-if="isSuperAdmin"
    to="/clients"
    class="nav-link-custom"
    :class="{ active: $route.path.startsWith('/clients') }"
    @click="sidebarOpen = false"
>
    <span class="nav-icon"><i class="fa-solid fa-user-tie"></i></span>
    <span>Clients</span>
</router-link>

<router-link
    v-if="isSuperAdmin"
    to="/invoices"
    class="nav-link-custom"
    :class="{ active: $route.path.startsWith('/invoices') }"
    @click="sidebarOpen = false"
>
    <span class="nav-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
    <span>Invoices</span>
</router-link>
<router-link
    v-if="isSuperAdmin"
    to="/activity-logs"
    class="nav-link-custom"
    :class="{ active: $route.path.startsWith('/activity-logs') }"
    @click="sidebarOpen = false"
>
    <span class="nav-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
    <span>Activity Logs</span>
</router-link>

<router-link
    v-if="isSuperAdmin"
    to="/recycle-bin"
    class="nav-link-custom"
    :class="{ active: $route.path.startsWith('/recycle-bin') }"
    @click="sidebarOpen = false"
>
    <span class="nav-icon"><i class="fa-solid fa-recycle"></i></span>
    <span>Recycle Bin</span>
</router-link>

            </nav>

            <div class="sidebar-bottom">
                <button @click="logout" class="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </button>
            </div>

        </aside>

        <!-- MAIN -->
        <main
            class="prosix-main"
            :class="{ 'orders-full-width': $route.path === '/orders' }"
        >
            <slot />
        </main>

        <!-- PROFILE MODAL -->
        <div v-if="profileModal" class="profile-modal-overlay" @click.self="closeProfile">
            <div class="profile-modal">
                <button class="profile-close" @click="closeProfile">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h4>Edit Profile</h4>
                <p class="profile-subtitle">Update your Prosix profile</p>
                <div class="profile-photo-wrap">
                    <img v-if="profileForm.preview" :src="profileForm.preview" class="profile-photo" />
                    <div v-else class="profile-photo-empty">{{ userInitial }}</div>
                </div>
                <input type="file" accept="image/*" class="profile-file" @change="onProfilePhotoChange" />
                <div class="profile-field">
                    <label>Name</label>
                    <input v-model="profileForm.name" type="text" />
                </div>
                <div class="profile-field">
                    <label>Email</label>
                    <input :value="user?.email || ''" readonly />
                </div>
                <div class="profile-field">
                    <label>Role</label>
                    <input :value="formatRole(user?.role)" readonly />
                </div>
                <div class="profile-field">
                    <label>About</label>
                    <textarea v-model="profileForm.about" rows="4" placeholder="Write something about yourself..."></textarea>
                </div>
                <button class="profile-save-btn" @click="saveProfile" :disabled="savingProfile">
                    <span v-if="savingProfile"><i class="fa-solid fa-spinner fa-spin"></i> Saving...</span>
                    <span v-else>Save Profile</span>
                </button>
            </div>
        </div>

    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'AppLayout',

    data() {
        return {
            sidebarOpen: false,
            profileModal: false,
            savingProfile: false,
            orderNotificationCount: 0,
            placeOrderNotificationCount: 0,
            profileForm: {
                name: '',
                about: '',
                profile_photo: null,
                preview: ''
            }
        }
    },

    computed: {
        user() {
            try {
                return JSON.parse(localStorage.getItem('user'))
            } catch { return null }
        },
        userInitial() {
            return this.user?.name?.charAt(0).toUpperCase() || 'U'
        },
        userPhoto() {
            return this.user?.profile_photo_url || null
        },
        isSuperAdmin() {
            return this.user?.role === 'super_admin'
        },
        isAdmin() {
            return this.user?.role === 'admin'
        },
        showOrderBadge() {
            return !this.isSuperAdmin && !this.isAdmin && this.orderNotificationCount > 0
        }
    },

    mounted() {
        this.loadOrderNotificationCount()
        this.loadPlaceOrderNotificationCount()

        window.addEventListener(
            'place-orders-read-updated',
            this.loadPlaceOrderNotificationCount
        )
    },

    watch: {
        '$route.path'(newPath) {
            this.sidebarOpen = false

            if (newPath.startsWith('/orders')) {
                this.clearOrderBadge()
            } else {
                this.loadOrderNotificationCount()
            }

            this.loadPlaceOrderNotificationCount()
        }
    },

    beforeUnmount() {
        window.removeEventListener(
            'place-orders-read-updated',
            this.loadPlaceOrderNotificationCount
        )
    },

    methods: {
        headers() {
            return {
                Authorization: `Bearer ${localStorage.getItem('token')}`,
                Accept: 'application/json'
            }
        },

        async loadOrderNotificationCount() {
            if (this.isSuperAdmin || this.isAdmin) { this.orderNotificationCount = 0; return }
            if (this.$route.path.startsWith('/orders')) { this.orderNotificationCount = 0; return }
            try {
                const res = await axios.get('/api/orders', { headers: this.headers() })
                const orders = Array.isArray(res.data) ? res.data : (res.data?.data || [])
                this.orderNotificationCount = orders.filter(o => !o.user_has_seen).length
            } catch (e) { console.error(e) }
        },

        clearOrderBadge() { this.orderNotificationCount = 0 },

        async loadPlaceOrderNotificationCount() {
            try {
                const response = await axios.get(
                    '/api/place-orders/unread-count',
                    { headers: this.headers() }
                )

                this.placeOrderNotificationCount = Number(
                    response.data?.count || 0
                )
            } catch (error) {
                console.error('Place order notification error:', error)
                this.placeOrderNotificationCount = 0
            }
        },


        formatRole(role) {
            if (!role) return 'Member'
            return role.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
        },

        openProfile() {
            this.profileForm = {
                name: this.user?.name || '',
                about: this.user?.about || '',
                profile_photo: null,
                preview: this.user?.profile_photo_url || ''
            }
            this.profileModal = true
        },

        closeProfile() { this.profileModal = false },

        onProfilePhotoChange(event) {
            const file = event.target.files?.[0]
            if (!file) return
            this.profileForm.profile_photo = file
            this.profileForm.preview = URL.createObjectURL(file)
        },

        async saveProfile() {
            this.savingProfile = true
            const form = new FormData()
            form.append('name', this.profileForm.name || '')
            form.append('about', this.profileForm.about || '')
            if (this.profileForm.profile_photo) {
                form.append('profile_photo', this.profileForm.profile_photo)
            }
            try {
                const res = await axios.post('/api/me/profile', form, {
                    headers: { ...this.headers(), 'Content-Type': 'multipart/form-data' }
                })
                const updatedUser = res.data?.user || res.data
                if (updatedUser) localStorage.setItem('user', JSON.stringify(updatedUser))
                this.closeProfile()
                window.location.reload()
            } catch (e) {
                console.error(e)
                alert(e.response?.data?.message || 'Profile save failed')
            } finally {
                this.savingProfile = false
            }
        },

        async logout() {
            try {
                await axios.post('/api/logout', {}, { headers: this.headers() })
            } catch (e) { console.error(e) }
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            this.$router.push('/login')
        }
    }
}
</script>

<style scoped>
*, *::before, *::after { box-sizing: border-box; }

.prosix-layout {
    min-height: 100vh;
    display: flex;
    background: #f6f7fb;
}

/* ─── SIDEBAR ─── */
.prosix-sidebar {
    width: 250px;
    min-height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: linear-gradient(180deg, #0f1117 0%, #181b25 100%);
    color: #fff;
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(255,255,255,0.08);
    z-index: 1000;
    transition: left 0.25s ease;
}

.sidebar-logo {
    height: 78px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    flex-shrink: 0;
}

.logo-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #fff;
    color: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 900;
    flex-shrink: 0;
}

.sidebar-logo h5 { margin: 0; font-size: 18px; font-weight: 900; color: #fff; }
.sidebar-logo small { color: #9aa0b8; font-size: 11px; }

.user-card {
    margin: 16px;
    padding: 12px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}
.user-card:hover { background: rgba(255,255,255,0.1); }

.user-avatar {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: #fff; color: #000;
    font-size: 15px; font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.user-avatar.has-photo { overflow: hidden; }
.user-avatar-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

.user-info { overflow: hidden; flex: 1; }
.user-name { font-size: 14px; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role { font-size: 11px; color: #9aa0b8; margin-top: 2px; }
.edit-profile-icon { font-size: 12px; color: #9aa0b8; }

.sidebar-nav { padding: 4px 12px; flex: 1; }

.nav-link-custom {
    height: 46px;
    padding: 0 14px;
    margin-bottom: 6px;
    border-radius: 12px;
    color: #b8bfd3;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 11px;
    font-size: 14px;
    font-weight: 700;
    transition: 0.18s ease;
    position: relative;
}
.nav-link-custom:hover { background: rgba(255,255,255,0.08); color: #fff; }
.nav-link-custom.active { background: #fff; color: #000; }
.nav-icon { width: 22px; display: inline-flex; justify-content: center; }

.order-badge {
    margin-left: auto;
    background: #fff; color: #000;
    border-radius: 999px;
    font-size: 11px; font-weight: 900;
    padding: 2px 8px;
}

.sidebar-bottom {
    padding: 16px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.logout-btn {
    width: 100%; height: 44px;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.05);
    color: #fff; border-radius: 12px;
    font-size: 14px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    gap: 8px; cursor: pointer;
}
.logout-btn:hover { background: #fff; color: #000; }

.sidebar-close-btn {
    display: none;
    position: absolute;
    top: 12px; right: 12px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
    width: 32px; height: 32px;
    border-radius: 8px;
    font-size: 14px; cursor: pointer;
    align-items: center; justify-content: center;
    z-index: 10;
}

/* ─── MAIN ─── */
.prosix-main {
    margin-left: 250px;
    flex: 1;
    min-height: 100vh;
    background: #f6f7fb;
    min-width: 0;
}

/* ─── MOBILE TOPBAR ─── */
.mobile-topbar {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 56px;
    background: linear-gradient(180deg, #0f1117 0%, #181b25 100%);
    color: #fff;
    z-index: 999;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.mobile-logo {
    display: flex; align-items: center; gap: 10px;
    font-size: 16px; font-weight: 900;
}

.hamburger-btn {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff;
    width: 38px; height: 38px;
    border-radius: 10px;
    font-size: 16px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}

.sidebar-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 999;
}
.sidebar-overlay.open { display: block; }

/* ─── PROFILE MODAL ─── */
.profile-modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 99999;
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
}

.profile-modal {
    width: 430px; max-width: 100%;
    max-height: 90vh; overflow-y: auto;
    background: #fff; border-radius: 20px;
    padding: 26px; position: relative;
    box-shadow: 0 30px 90px rgba(0,0,0,0.35);
}

.profile-modal h4 { margin: 0; font-size: 24px; font-weight: 900; color: #000; text-align: center; }
.profile-subtitle { margin: 6px 0 18px; color: #6b7280; text-align: center; font-size: 13px; }

.profile-close {
    position: absolute; right: 16px; top: 16px;
    border: none; background: #f3f4f6;
    width: 34px; height: 34px; border-radius: 10px; cursor: pointer;
}

.profile-photo-wrap { display: flex; justify-content: center; margin-bottom: 12px; }

.profile-photo,
.profile-photo-empty {
    width: 92px; height: 92px; border-radius: 50%;
    object-fit: cover; background: #000; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 30px; font-weight: 900;
}

.profile-file { width: 100%; margin-bottom: 16px; }

.profile-field { margin-bottom: 14px; }
.profile-field label { display: block; margin-bottom: 6px; color: #111; font-size: 13px; font-weight: 900; }
.profile-field input,
.profile-field textarea {
    width: 100%; border: 1.5px solid #d1d5db; border-radius: 10px;
    padding: 10px 12px; color: #111; font-size: 14px; font-weight: 600; outline: none;
}
.profile-field input:focus,
.profile-field textarea:focus { border-color: #000; box-shadow: 0 0 0 4px rgba(0,0,0,0.08); }
.profile-field input[readonly] { background: #f3f4f6; }

.profile-save-btn {
    width: 100%; height: 46px; border: none;
    background: #000; color: #fff; border-radius: 12px;
    font-size: 14px; font-weight: 900; cursor: pointer;
}
.profile-save-btn:disabled { opacity: 0.65; }

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
    .mobile-topbar { display: flex; }
    .sidebar-close-btn { display: flex; }

    .prosix-sidebar {
        left: -260px;
        width: 250px;
        z-index: 1001;
    }
    .prosix-sidebar.sidebar-open { left: 0; }

    .prosix-main {
        margin-left: 0;
        width: 100%;
        padding-top: 56px;
    }
}

.prosix-main.orders-full-width {
    margin-left: 0;
    width: 100%;
}

</style>
