<template>
    <div class="prosix-layout">

        <!-- MOBILE TOP BAR -->
        <div class="mobile-topbar">
            <div class="mobile-logo">
                <img
                    src="/public/assets/images/P LOGO BLACK.png"
                    alt="Prosix"
                    class="mobile-prosix-logo"
                    @error="$event.target.style.display = 'none'"
                />
            </div>

            <button
                type="button"
                class="hamburger-btn"
                @click="sidebarOpen = true"
            >
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>


        <!-- MOBILE OVERLAY -->
        <div
            class="sidebar-overlay"
            :class="{ open: sidebarOpen }"
            @click="sidebarOpen = false"
        ></div>


        <!-- SIDEBAR -->
      <aside
    class="prosix-sidebar"
    :class="{ 'sidebar-open': sidebarOpen }"
>

            <!-- MOBILE CLOSE -->
            <button
                type="button"
                class="sidebar-close-btn"
                @click="sidebarOpen = false"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>


            <!-- =========================
                 WHITE LOGO AREA
            ========================== -->
            <div class="sidebar-brand">
                <img
                    src="/public/assets/images/P LOGO BLACK.png"
                    alt="Prosix"
                    class="sidebar-prosix-logo"
                    @error="$event.target.style.display = 'none'"
                />
            </div>


            <!-- =========================
                 NAVIGATION
            ========================== -->
            <nav class="sidebar-nav">

                <!-- HOME -->
                <router-link
                    to="/dashboard"
                    class="nav-link-custom"
                    :class="{
                        active: $route.path === '/dashboard'
                    }"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-house"></i>
                    </span>

                    <span class="nav-text">
                        Dashboard
                    </span>
                </router-link>


                <!-- FACTORY ORDERS -->
                <router-link
                    :to="{
                        path: '/orders',
                        query: {
                            type: 'factory'
                        }
                    }"
                    class="nav-link-custom"
                    :class="{
                        active:
                            $route.path === '/orders' &&
                            ($route.query.type || 'factory') === 'factory'
                    }"
                    @click="
                        clearOrderBadge();
                        sidebarOpen = false;
                    "
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-industry"></i>
                    </span>

                    <span class="nav-text">
                        Factory Orders
                    </span>

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

                    <span class="nav-text">
                        TeamStore Orders
                    </span>

                    <span
                        v-if="teamStoreNotificationCount > 0"
                        class="order-badge"
                    >
                        {{ teamStoreNotificationCount }}
                    </span>
                </router-link>


                <!-- PLACE ORDERS -->
                <router-link
                    to="/place-orders"
                    class="nav-link-custom"
                    :class="{
                        active: $route.path === '/place-orders'
                    }"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </span>

                    <span class="nav-text">
                        Place Orders
                    </span>

                    <span
                        v-if="placeOrderNotificationCount > 0"
                        class="order-badge"
                    >
                        {{ placeOrderNotificationCount }}
                    </span>
                </router-link>


                <!-- ARTWORK REQUESTS -->
                <router-link
                    to="/artwork-requests"
                    class="nav-link-custom"
                    :class="{
                        active: $route.path === '/artwork-requests'
                    }"
                    @click="
                        clearOrderBadge();
                        sidebarOpen = false;
                    "
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-palette"></i>
                    </span>

                    <span class="nav-text">
                        Artwork Requests
                    </span>
                </router-link>


                <!-- MEMBERS -->
                <router-link
                    v-if="isSuperAdmin || isAdmin"
                    to="/members"
                    class="nav-link-custom"
                    :class="{
                        active: $route.path === '/members'
                    }"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-users"></i>
                    </span>

                    <span class="nav-text">
                        Members
                    </span>
                </router-link>


                <!-- CLIENTS -->
                <router-link
                    v-if="isSuperAdmin"
                    to="/clients"
                    class="nav-link-custom"
                    :class="{
                        active: $route.path.startsWith('/clients')
                    }"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-user-tie"></i>
                    </span>

                    <span class="nav-text">
                        Clients
                    </span>
                </router-link>


                <!-- INVOICES -->
                <router-link
                    v-if="isSuperAdmin"
                    to="/invoices"
                    class="nav-link-custom"
                    :class="{
                        active: $route.path.startsWith('/invoices')
                    }"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </span>

                    <span class="nav-text">
                        Invoices
                    </span>
                </router-link>


                <!-- ACTIVITY LOGS -->
                <router-link
                    v-if="isSuperAdmin"
                    to="/activity-logs"
                    class="nav-link-custom"
                    :class="{
                        active: $route.path.startsWith('/activity-logs')
                    }"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </span>

                    <span class="nav-text">
                        Activity Logs
                    </span>
                </router-link>


                <!-- RECYCLE BIN -->
                <router-link
                    v-if="isSuperAdmin"
                    to="/recycle-bin"
                    class="nav-link-custom"
                    :class="{
                        active: $route.path.startsWith('/recycle-bin')
                    }"
                    @click="sidebarOpen = false"
                >
                    <span class="nav-icon">
                        <i class="fa-solid fa-recycle"></i>
                    </span>

                    <span class="nav-text">
                        Recycle Bin
                    </span>
                </router-link>

            </nav>


            <!-- =========================
                 LOGOUT
            ========================== -->
            <div class="sidebar-bottom">
                <button
                    type="button"
                    class="logout-btn"
                    @click="logout"
                >
                    <i class="fa-solid fa-right-from-bracket"></i>

                    <span>
                        Logout
                    </span>
                </button>
            </div>

        </aside>


        <!-- =========================
             MAIN CONTENT
        ========================== -->
       <main class="prosix-main">
    <slot />
</main>

    </div>
</template>


<script>
import axios from 'axios'

export default {

    name: 'AppLayout',


    data() {
        return {

            sidebarOpen: false,

            orderNotificationCount: 0,

            placeOrderNotificationCount: 0,

            teamStoreNotificationCount: 0
        }
    },


    computed: {

        // LOGGED IN USER
        user() {
            try {

                return JSON.parse(
                    localStorage.getItem('user')
                )

            } catch (error) {

                return null
            }
        },


        // SUPER ADMIN
        isSuperAdmin() {
            return this.user?.role === 'super_admin'
        },


        // ADMIN
        isAdmin() {
            return this.user?.role === 'admin'
        },


        // FACTORY ORDER BADGE
        showOrderBadge() {

            return (
                !this.isSuperAdmin &&
                !this.isAdmin &&
                this.orderNotificationCount > 0
            )
        }
    },


    mounted() {

        this.loadOrderNotificationCount()

        this.loadPlaceOrderNotificationCount()

        this.loadTeamStoreNotificationCount()


        window.addEventListener(
            'place-orders-read-updated',
            this.loadPlaceOrderNotificationCount
        )


        window.addEventListener(
            'teamstore-orders-read-updated',
            this.loadTeamStoreNotificationCount
        )
    },


    beforeUnmount() {

        window.removeEventListener(
            'place-orders-read-updated',
            this.loadPlaceOrderNotificationCount
        )


        window.removeEventListener(
            'teamstore-orders-read-updated',
            this.loadTeamStoreNotificationCount
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

            this.loadTeamStoreNotificationCount()
        }
    },


    methods: {

        // =========================
        // API HEADERS
        // =========================
        headers() {

            return {

                Authorization:
                    `Bearer ${localStorage.getItem('token')}`,

                Accept:
                    'application/json'
            }
        },


        // =========================
        // FACTORY ORDERS COUNT
        // =========================
        async loadOrderNotificationCount() {

            if (this.isSuperAdmin || this.isAdmin) {

                this.orderNotificationCount = 0

                return
            }


            if (this.$route.path.startsWith('/orders')) {

                this.orderNotificationCount = 0

                return
            }


            try {

                const response = await axios.get(
                    '/api/orders',
                    {
                        headers: this.headers()
                    }
                )


                const orders =
                    Array.isArray(response.data)
                        ? response.data
                        : response.data?.data || []


                this.orderNotificationCount =
                    orders.filter(
                        order => !order.user_has_seen
                    ).length

            } catch (error) {

                console.error(
                    'Factory order notification error:',
                    error
                )

                this.orderNotificationCount = 0
            }
        },


        // =========================
        // CLEAR FACTORY BADGE
        // =========================
        clearOrderBadge() {

            this.orderNotificationCount = 0
        },


        // =========================
        // PLACE ORDERS COUNT
        // =========================
        async loadPlaceOrderNotificationCount() {

            try {

                const response = await axios.get(
                    '/api/place-orders/unread-count',
                    {
                        headers: this.headers()
                    }
                )


                this.placeOrderNotificationCount =
                    Number(
                        response.data?.count || 0
                    )

            } catch (error) {

                console.error(
                    'Place order notification error:',
                    error
                )


                this.placeOrderNotificationCount = 0
            }
        },


        // =========================
        // TEAMSTORE COUNT
        // =========================
        async loadTeamStoreNotificationCount() {

            try {

                const response = await axios.get(
                    '/api/teamstore-orders/unread-count',
                    {
                        headers: this.headers()
                    }
                )


                this.teamStoreNotificationCount =
                    Number(
                        response.data?.count || 0
                    )

            } catch (error) {

                console.error(
                    'TeamStore notification error:',
                    error
                )


                this.teamStoreNotificationCount = 0
            }
        },


        // =========================
        // LOGOUT
        // =========================
        async logout() {

            try {

                await axios.post(
                    '/api/logout',
                    {},
                    {
                        headers: this.headers()
                    }
                )

            } catch (error) {

                console.error(
                    'Logout error:',
                    error
                )
            }


            localStorage.removeItem('token')

            localStorage.removeItem('user')


            this.$router.push('/login')
        }
    }
}
</script>


<style scoped>

/* ==================================================
   RESET
\\================================================== */

\*,
\*::before,
\*::after {
    box-sizing: border-box;
}


.prosix-layout {
    min-height: 100vh;

    display: flex;

    background: #f6f7fb;
}



/* ==================================================
   SIDEBAR
\\================================================== */

.prosix-sidebar {
    width: 250px;

    height: 100vh;

    position: fixed;

    top: 0;
    left: 0;

    display: flex;

    flex-direction: column;

    background: linear-gradient(
        180deg,
        #0f1117 0%,
        #171a24 100%
    );

    color: #ffffff;

    border: 0 !important;

    border-right: 0 !important;

    outline: 0 !important;

    box-shadow: none !important;

    overflow: hidden;

    z-index: 1000;

    transition: left 0.25s ease;
}



/* ==================================================
   TOP WHITE LOGO SECTION
\\================================================== */

.sidebar-brand {
    width: 100%;

    height: 190px;

    min-height: 190px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f4f5f8 !important;

    border: none !important;

    border-top: none !important;

    border-right: none !important;

    border-bottom: none !important;

    border-left: none !important;

    outline: none !important;

    box-shadow: none !important;

    margin: 0;

    padding: 20px;

    position: relative;

    overflow: hidden;
}


.sidebar-brand::before,
.sidebar-brand::after {
    display: none !important;

    content: none !important;

    border: none !important;

    outline: none !important;

    box-shadow: none !important;
}



/* ==================================================
   REAL PROSIX LOGO
\\================================================== */

.sidebar-prosix-logo {
    display: block;

    width: 135px;

    height: 135px;

    max-width: 80%;

    max-height: 90%;

    object-fit: contain;

    object-position: center;

    margin: 0 auto;

    padding: 0;

    background: transparent;

    border: none !important;

    outline: none !important;

    box-shadow: none !important;
}



/* ==================================================
   NAVIGATION
\\================================================== */

.sidebar-nav {
    flex: 1;

    padding: 28px 12px 15px;

    background: transparent;

    overflow-y: auto;

    overflow-x: hidden;

    border: 0;

    outline: 0;

    scrollbar-width: thin;

    scrollbar-color:
        rgba(255, 255, 255, 0.15)
        transparent;
}


.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}


.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}


.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);

    border-radius: 20px;
}



/* ==================================================
   NAV ITEM
\\================================================== */

.nav-link-custom {
    width: 100%;

    min-height: 45px;

    margin-bottom: 7px;

    padding: 0 14px;

    display: flex;

    align-items: center;

    gap: 11px;

    position: relative;

    border: 0;

    border-radius: 4px;

    outline: none;

    color: #b8bfd3;

    text-decoration: none;

    font-size: 14px;

    font-weight: 700;

    transition:
        \*background\* 0.18s ease,
        color 0.18s ease;
}


.nav-link-custom:hover {
    background: rgba(255, 255, 255, 0.07);

    color: #ffffff;
}


.nav-link-custom.active {
    background: #f7f6f1;

    color: #1a1a1a;
}



/* ==================================================
   NAV ICON
\\================================================== */

.nav-icon {
    width: 21px;

    min-width: 21px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    font-size: 13px;
}


.nav-link-custom.active .nav-icon {
    color: #1a1a1a;
}



/* ==================================================
   NAV TEXT
\\================================================== */

.nav-text {
    min-width: 0;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}



/* ==================================================
   ORDER BADGES
\\================================================== */

.order-badge {
    margin-left: auto;

    min-width: 27px;

    height: 22px;

    padding: 0 7px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

    border: 0;

    border-radius: 50px;

    background: #ffffff;

    color: #000000;

    font-size: 11px;

    font-weight: 900;
}


.nav-link-custom.active .order-badge {
    background: #111111;

    color: #ffffff;
}



/* ==================================================
   SIDEBAR BOTTOM
\\================================================== */

.sidebar-bottom {
    flex-shrink: 0;

    padding: 16px;

    border: 0;

    border-top:
        1px solid rgba(255, 255, 255, 0.08);
}



/* ==================================================
   LOGOUT BUTTON
\\================================================== */

.logout-btn {
    width: 100%;

    height: 46px;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    border:
        1px solid rgba(255, 255, 255, 0.13);

    border-radius: 10px;

    background:
        rgba(255, 255, 255, 0.05);

    color: #ffffff;

    font-size: 14px;

    font-weight: 800;

    cursor: pointer;

    transition:
        \*background\* 0.2s ease,
        color 0.2s ease,
        border-color 0.2s ease;
}


.logout-btn:hover {
    background: #ffffff;

    border-color: #ffffff;

    color: #000000;
}



/* ==================================================
   MOBILE CLOSE BUTTON
\\================================================== */

.sidebar-close-btn {
    display: none;

    position: absolute;

    top: 12px;

    right: 12px;

    width: 34px;

    height: 34px;

    align-items: center;

    justify-content: center;

    border: 0;

    border-radius: 8px;

    background:
        rgba(255, 255, 255, 0.9);

    color: #111111;

    font-size: 15px;

    cursor: pointer;

    z-index: 10;
}



/* ==================================================
   MAIN CONTENT
\\================================================== */

.prosix-main {
    flex: 1;

    min-width: 0;

    min-height: 100vh;

    margin-left: 250px;

    background: #f6f7fb;

    border: 0 !important;

    border-left: 0 !important;

    outline: 0 !important;

    box-shadow: none !important;
}



/* ==================================================
   ORDERS FULL WIDTH
\\================================================== */

.prosix-main.orders-full-width {
    width: 100%;

    margin-left: 0;

    border: 0 !important;

    box-shadow: none !important;
}



/* ==================================================
   MOBILE TOP BAR
\\================================================== */

.mobile-topbar {
    display: none;

    position: fixed;

    top: 0;

    left: 0;

    right: 0;

    height: 58px;

    padding: 0 14px;

    align-items: center;

    justify-content: space-between;

    background: #ffffff;

    border: 0 !important;

    border-bottom: 0 !important;

    outline: 0 !important;

    box-shadow: none !important;

    color: #111111;

    z-index: 999;
}



/* ==================================================
   MOBILE LOGO
\\================================================== */

.mobile-logo {
    height: 58px;

    display: flex;

    align-items: center;

    justify-content: center;
}


.mobile-prosix-logo {
    display: block;

    width: 44px;

    height: 44px;

    object-fit: contain;

    object-position: center;

    background: transparent;

    border: none !important;

    outline: none !important;

    box-shadow: none !important;
}



/* ==================================================
   HAMBURGER
\\================================================== */

.hamburger-btn {
    width: 38px;

    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    border: 1px solid #e5e7eb;

    border-radius: 9px;

    background: #111111;

    color: #ffffff;

    font-size: 16px;

    cursor: pointer;
}



/* ==================================================
   OVERLAY
\\================================================== */

.sidebar-overlay {
    display: none;

    position: fixed;

    inset: 0;

    background:
        rgba(0, 0, 0, 0.58);

    z-index: 999;
}


.sidebar-overlay.open {
    display: block;
}



/* ==================================================
   FORCE REMOVE LOGO AREA LINES
\\================================================== */

.prosix-sidebar,
.sidebar-brand,
.sidebar-brand::before,
.sidebar-brand::after,
.sidebar-prosix-logo,
.prosix-main {
    box-shadow: none !important;
}


.prosix-sidebar {
    border-right: none !important;
}


.sidebar-brand {
    border: none !important;
}


.prosix-main {
    border-left: none !important;
}



/* ==================================================
   TABLET / MOBILE
\\================================================== */

@media (max-width: 768px) {

    .mobile-topbar {
        display: flex;
    }


    .sidebar-close-btn {
        display: flex;
    }


    .prosix-sidebar {
        width: 250px;

        left: -260px;

        border: 0 !important;

        box-shadow: none !important;

        z-index: 1001;
    }


    .prosix-sidebar.sidebar-open {
        left: 0;
    }


    .sidebar-brand {
        height: 165px;

        min-height: 165px;

        border: 0 !important;

        padding: 18px;
    }


    .sidebar-prosix-logo {
        width: 125px;

        height: 125px;
    }


    .sidebar-nav {
        padding-top: 20px;
    }


    .prosix-main {
        width: 100%;

        margin-left: 0;

        padding-top: 58px;

        border: 0 !important;
    }


    .prosix-main.orders-full-width {
        width: 100%;

        margin-left: 0;
    }
}



/* ==================================================
   SMALL MOBILE
\\================================================== */

@media (max-width: 480px) {

    .prosix-sidebar {
        width: 235px;

        left: -245px;
    }


    .sidebar-brand {
        height: 150px;

        min-height: 150px;

        padding: 16px;
    }


    .sidebar-prosix-logo {
        width: 110px;

        height: 110px;
    }


    .sidebar-nav {
        padding: 18px 10px 12px;
    }


    .nav-link-custom {
        min-height: 43px;

        padding: 0 12px;

        font-size: 13px;
    }
}



/* ==================================================
   STYLISH PROSIX SIDEBAR - FINAL OVERRIDE
\================================================== */

/* Keep layout background consistent */
.prosix-layout,
.prosix-main {
    background: #f4f5f8 !important;
}

/* Main sidebar */
.prosix-sidebar {
    width: 250px;

    background:
        linear-gradient(
            180deg,
            #0b0e15 0%,
            #10141d 50%,
            #151a24 100%
        ) !important;

    overflow: hidden !important;
}

/* Logo area */
.sidebar-brand {
    position: relative !important;

    height: 190px !important;
    min-height: 190px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    padding: 18px 20px 32px !important;

    background: #f4f5f8 !important;

    overflow: visible !important;

    z-index: 5 !important;
}

/* Angled / cut shape under logo */
.sidebar-brand::after {
    content: "" !important;

    display: block !important;

    position: absolute !important;

    left: 0 !important;
    bottom: -34px !important;

    width: 168px !important;
    height: 44px !important;

    background: #f4f5f8 !important;

    clip-path: polygon(
        0 0,
        100% 0,
        82% 100%,
        0 100%
    ) !important;

    z-index: 6 !important;

    pointer-events: none !important;
}

/* Extra small angled accent */
.sidebar-brand::before {
    content: "" !important;

    display: block !important;

    position: absolute !important;

    left: 142px !important;
    bottom: -34px !important;

    width: 48px !important;
    height: 44px !important;

    background: #f4f5f8 !important;

    clip-path: polygon(
        0 0,
        100% 0,
        35% 100%,
        0 100%
    ) !important;

    z-index: 6 !important;

    pointer-events: none !important;
}

/* Logo */
.sidebar-prosix-logo {
    width: 128px !important;
    height: 128px !important;

    max-width: 82% !important;

    object-fit: contain !important;

    transform: translateY(-4px);

    position: relative !important;
    z-index: 8 !important;
}

/* Navigation area starts lower so angled logo cut has breathing room */
.sidebar-nav {
    position: relative !important;

    padding:
        55px
        12px
        15px !important;

    background: transparent !important;
}

/* Soft dark transition under the logo */
.sidebar-nav::before {
    content: "" !important;

    position: absolute !important;

    top: 0 !important;
    left: 0 !important;
    right: 0 !important;

    height: 72px !important;

    background:
        linear-gradient(
            180deg,
            rgba(7, 10, 16, 0.95) 0%,
            rgba(11, 14, 21, 0.25) 65%,
            rgba(11, 14, 21, 0) 100%
        ) !important;

    pointer-events: none !important;

    z-index: 0 !important;
}

.sidebar-nav > \* {
    position: relative;
    z-index: 1;
}

/* Menu items */
.nav-link-custom {
    min-height: 46px !important;

    margin-bottom: 6px !important;

    padding:
        0
        14px !important;

    gap: 11px !important;

    border-radius: 9px !important;

    color: #b7bfd0 !important;

    font-size: 13px !important;
    font-weight: 700 !important;

    transition:
        background 0.18s ease,
        color 0.18s ease,
        transform 0.18s ease,
        box-shadow 0.18s ease !important;
}

/* Hover */
.nav-link-custom:hover {
    background:
        rgba(
            255,
            255,
            255,
            0.07
        ) !important;

    color: #ffffff !important;

    transform: translateX(3px);
}

/* Active menu */
.nav-link-custom.active {
    background: #f7f6f1 !important;

    color: #10131a !important;

    border-radius: 9px !important;

    box-shadow:
        0 5px 14px
        rgba(
            0,
            0,
            0,
            0.18
        ) !important;

    transform: none !important;
}

/* Active left indicator */
.nav-link-custom.active::before {
    content: "" !important;

    position: absolute !important;

    left: 0 !important;
    top: 50% !important;

    transform:
        translateY(-50%) !important;

    width: 3px !important;
    height: 24px !important;

    background: #111111 !important;

    border-radius:
        0
        4px
        4px
        0 !important;
}

/* Icons */
.nav-icon {
    width: 22px !important;
    min-width: 22px !important;

    font-size: 13px !important;

    color: #aeb8cd !important;
}

.nav-link-custom:hover .nav-icon {
    color: #ffffff !important;
}

.nav-link-custom.active .nav-icon {
    color: #10131a !important;
}

/* Notification badges */
.order-badge {
    min-width: 26px !important;
    height: 22px !important;

    padding: 0 7px !important;

    background: #ffffff !important;

    color: #111111 !important;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.18
        ) !important;

    font-size: 10px !important;
    font-weight: 900 !important;

    box-shadow:
        0 2px 6px
        rgba(
            0,
            0,
            0,
            0.16
        );
}

.nav-link-custom.active .order-badge {
    background: #111111 !important;
    color: #ffffff !important;
}

/* Bottom logout section */
.sidebar-bottom {
    padding:
        14px
        14px
        16px !important;

    background:
        linear-gradient(
            180deg,
            rgba(21, 26, 36, 0) 0%,
            rgba(21, 26, 36, 0.95) 45%,
            #151a24 100%
        ) !important;
}

/* Logout */
.logout-btn {
    height: 44px !important;

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.12
        ) !important;

    border-radius: 10px !important;

    background:
        rgba(
            255,
            255,
            255,
            0.045
        ) !important;

    color: #ffffff !important;
}

.logout-btn:hover {
    background: #ffffff !important;

    color: #111111 !important;

    border-color: #ffffff !important;
}

/* Tablet */
@media (max-width: 768px) {

    .sidebar-brand {
        height: 165px !important;
        min-height: 165px !important;

        padding:
            16px
            18px
            28px !important;
    }

    .sidebar-brand::after {
        bottom: -28px !important;

        width: 150px !important;
        height: 38px !important;
    }

    .sidebar-brand::before {
        left: 127px !important;
        bottom: -28px !important;

        width: 42px !important;
        height: 38px !important;
    }

    .sidebar-prosix-logo {
        width: 112px !important;
        height: 112px !important;
    }

    .sidebar-nav {
        padding-top: 48px !important;
    }
}

/* Small mobile */
@media (max-width: 480px) {

    .sidebar-brand {
        height: 150px !important;
        min-height: 150px !important;
    }

    .sidebar-brand::after {
        width: 135px !important;
    }

    .sidebar-brand::before {
        left: 113px !important;
    }

    .sidebar-prosix-logo {
        width: 100px !important;
        height: 100px !important;
    }
}
.sidebar-brand::before {
  display: none !important;
  content: none !important;
}
/* P LOGO BIGGER */
.sidebar-prosix-logo {
    width: 150px !important;
    height: 150px !important;
    max-width: 80% !important;
}
</style>
