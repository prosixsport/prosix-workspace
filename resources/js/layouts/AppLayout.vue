<template>
    <div class="prosix-layout">

        <!-- Sidebar -->
        <aside class="prosix-sidebar">

            <!-- Logo -->
            <div class="sidebar-logo">
                <div class="logo-icon">P</div>
                <div>
                    <h5>Prosixflow</h5>
                    <small>Work Management</small>
                </div>
            </div>

            <!-- User Info -->
            <div class="user-card">
                <div class="user-avatar">
                    {{ userInitial }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ user?.name || 'User' }}</div>
                    <div class="user-role">{{ formatRole(user?.role) }}</div>
                </div>
            </div>

            <!-- Nav Links -->
            <nav class="sidebar-nav">

                <router-link
                    to="/dashboard"
                    class="nav-link-custom"
                    :class="{ active: $route.path === '/dashboard' }"
                >
                    <span class="nav-icon"><i class="fa-solid fa-house"></i></span>
                    <span>Home</span>
                </router-link>

             
<router-link
    to="/orders"
    class="nav-link-custom"
    :class="{ active: $route.path.startsWith('/orders') }"
>
    <span class="nav-icon"><i class="fa-solid fa-clipboard-list"></i></span>
    <span>All Orders</span>
</router-link>
                <router-link
                    v-if="isSuperAdmin || isAdmin"
                    to="/members"
                    class="nav-link-custom"
                    :class="{ active: $route.path === '/members' }"
                >
                    <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                    <span>Members</span>
                </router-link>

               
            </nav>

            <!-- Workspace Section with + Button -->
            <div class="workspace-section">
                <div class="workspace-header" @click="showBoardList = !showBoardList">
                    <span class="workspace-label">Workspace</span>
                    <div class="workspace-actions">
                        <button class="ws-icon-btn" title="Search" @click.stop>
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <button
                            class="ws-icon-btn ws-plus-btn"
                            @click.stop="toggleAddMenu($event)"
                            title="Add new"
                        >
                            <i class="fa-solid fa-plus"></i>
                        </button>
                        <i
                            class="fa-solid workspace-chevron"
                            :class="showBoardList ? 'fa-chevron-up' : 'fa-chevron-down'"
                        ></i>
                    </div>
                </div>

                <!-- Add New Dropdown (monday.com style) -->
                <div v-if="showAddMenu" class="add-menu" @click.stop>
                    <div class="add-menu-header">Add new</div>

                    <button class="add-menu-item" @click="openBoardSubmenu($event)">
                        <span class="add-menu-icon board-icon">
                            <i class="fa-regular fa-square"></i>
                        </span>
                        <span>Board</span>
                        <i class="fa-solid fa-chevron-right add-menu-arrow"></i>
                    </button>

                    <button class="add-menu-item" @click="showAddMenu = false">
                        <span class="add-menu-icon doc-icon">
                            <i class="fa-regular fa-file-lines"></i>
                        </span>
                        <span>Doc</span>
                        <i class="fa-solid fa-chevron-right add-menu-arrow"></i>
                    </button>

                    <button class="add-menu-item" @click="showAddMenu = false">
                        <span class="add-menu-icon dashboard-icon">
                            <i class="fa-solid fa-chart-bar"></i>
                        </span>
                        <span>Dashboard</span>
                    </button>

                    <button class="add-menu-item" @click="showAddMenu = false">
                        <span class="add-menu-icon form-icon">
                            <i class="fa-solid fa-list-check"></i>
                        </span>
                        <span>Form</span>
                        <i class="fa-solid fa-chevron-right add-menu-arrow"></i>
                    </button>

                    <button class="add-menu-item" @click="showAddMenu = false">
                        <span class="add-menu-icon folder-icon">
                            <i class="fa-solid fa-folder"></i>
                        </span>
                        <span>Folder</span>
                    </button>

                    <div class="add-menu-divider"></div>

                    <button class="add-menu-item" @click="showAddMenu = false">
                        <span class="add-menu-icon import-icon">
                            <i class="fa-solid fa-file-import"></i>
                        </span>
                        <span>Import data</span>
                        <i class="fa-solid fa-chevron-right add-menu-arrow"></i>
                    </button>

                    <!-- Board Submenu (appears to right) -->
                    <div v-if="showBoardSubmenu" class="board-submenu" @click.stop>
                        <button class="submenu-item" @click="openCreateBoardModal">
                            <i class="fa-regular fa-square submenu-icon"></i>
                            New Board
                        </button>
                        <button class="submenu-item" @click="showAddMenu = false">
                            <i class="fa-solid fa-layer-group submenu-icon"></i>
                            New multi-level board
                        </button>
                        <button class="submenu-item" @click="showAddMenu = false">
                            <i class="fa-solid fa-wand-magic-sparkles submenu-icon"></i>
                            Start with template
                        </button>
                    </div>
                </div>

                <!-- My workspace boards list -->
                <transition name="board-list">
                    <div class="ws-board-list" v-show="showBoardList">
                        <div
                            v-for="board in recentBoards"
                            :key="board.id"
                            class="ws-board-item"
                            :class="{ active: $route.path === `/boards/${board.id}` }"
                            @click="goToBoard(board.id)"
                        >
                            <i class="fa-regular fa-square ws-board-icon"></i>
                            <span class="ws-board-name">{{ board.name }}</span>

                            <!-- Three dots button shown on hover -->
                            <button
                                class="ws-board-dots"
                                @click.stop="toggleBoardMenu(board, $event)"
                                title="Options"
                            >
                                <i class="fa-solid fa-ellipsis"></i>
                            </button>
                        </div>
                    </div>
                </transition>

                <!-- Board context menu -->
                <div
                    v-if="activeBoardMenu"
                    class="board-ctx-menu"
                    @click.stop
                >
                    <button class="ctx-item" @click="openBoardInNewTab(activeBoardMenu)">
                        <i class="fa-solid fa-arrow-up-right-from-square ctx-icon"></i>
                        Open in new tab
                    </button>
                    <div class="ctx-divider"></div>
                    <button class="ctx-item" @click="startRenameBoard(activeBoardMenu)">
                        <i class="fa-solid fa-pen ctx-icon"></i>
                        Rename
                    </button>
                    <button class="ctx-item" @click="duplicateBoard(activeBoardMenu)">
                        <i class="fa-regular fa-copy ctx-icon"></i>
                        Duplicate
                    </button>
                    <div class="ctx-divider"></div>
                    <button class="ctx-item ctx-danger" @click="deleteBoard(activeBoardMenu)">
                        <i class="fa-solid fa-trash ctx-icon"></i>
                        Delete
                    </button>
                </div>

                <!-- Rename inline modal -->
                <div v-if="renamingBoard" class="rename-overlay" @click.self="renamingBoard = null">
                    <div class="rename-box">
                        <p class="rename-label">Rename Board</p>
                        <input
                            v-model="renameValue"
                            class="rename-input"
                            @keyup.enter="confirmRename"
                            ref="renameInput"
                        >
                        <div class="rename-actions">
                            <button class="rename-cancel" @click="renamingBoard = null">Cancel</button>
                            <button class="rename-confirm" @click="confirmRename">Save</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom -->
            <div class="sidebar-bottom">
                <button @click="logout" class="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </button>
            </div>

        </aside>

        <!-- Main Content -->
        <main class="prosix-main">
            <slot />
        </main>

        <!-- Create Board Modal -->
        <div v-if="showCreateModal" class="modal-overlay" @click.self="closeCreateModal">
            <div class="create-modal">
                <div class="create-modal-header">
                    <h5 class="fw-bold mb-0">Create New Board</h5>
                    <button @click="closeCreateModal" class="modal-close-btn">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="create-modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Board Name <span class="text-danger">*</span></label>
                        <input
                            v-model="newBoard.name"
                            type="text"
                            class="form-control"
                            placeholder="e.g. Marketing Campaign"
                            @keyup.enter="createBoard"
                            ref="boardNameInput"
                        >
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description <span class="text-muted small">(optional)</span></label>
                        <textarea
                            v-model="newBoard.description"
                            class="form-control"
                            rows="3"
                            placeholder="What is this board for?"
                        ></textarea>
                    </div>

                    <!-- Board Type Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Board type</label>
                        <div class="board-type-grid">
                            <div
                                class="board-type-card"
                                :class="{ selected: newBoard.type === 'main' }"
                                @click="newBoard.type = 'main'"
                            >
                                <i class="fa-solid fa-table-cells-large board-type-icon"></i>
                                <span>Main Table</span>
                            </div>
                            <div
                                class="board-type-card"
                                :class="{ selected: newBoard.type === 'shareable' }"
                                @click="newBoard.type = 'shareable'"
                            >
                                <i class="fa-solid fa-share-nodes board-type-icon"></i>
                                <span>Shareable</span>
                            </div>
                            <div
                                class="board-type-card"
                                :class="{ selected: newBoard.type === 'private' }"
                                @click="newBoard.type = 'private'"
                            >
                                <i class="fa-solid fa-lock board-type-icon"></i>
                                <span>Private</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="create-modal-footer">
                    <button @click="closeCreateModal" class="btn btn-outline-secondary">
                        Cancel
                    </button>
                    <button
                        @click="createBoard"
                        class="btn btn-primary-custom"
                        :disabled="!newBoard.name.trim() || creating"
                    >
                        <span v-if="creating">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Creating...
                        </span>
                        <span v-else>Create Board</span>
                    </button>
                </div>
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
            showAddMenu: false,
            showBoardSubmenu: false,
            showCreateModal: false,
            showBoardList: true,
            creating: false,
            recentBoards: [],
            newBoard: {
                name: '',
                description: '',
                type: 'main',
            },
            activeBoardMenu: null,
            renamingBoard: null,
            renameValue: '',
        }
    },

    computed: {
        user() {
            try {
                const user = localStorage.getItem('user')
                return user ? JSON.parse(user) : null
            } catch (e) {
                return null
            }
        },

        userInitial() {
            return this.user?.name?.charAt(0).toUpperCase() || 'U'
        },

        isSuperAdmin() {
            return this.user?.role === 'super_admin'
        },

        isAdmin() {
            return this.user?.role === 'admin'
        }
    },

    mounted() {
        this.fetchRecentBoards()
        document.addEventListener('click', this.closeAllMenus)
    },

    beforeUnmount() {
        document.removeEventListener('click', this.closeAllMenus)
    },

    methods: {
        formatRole(role) {
            if (!role) return 'Member'
            return role.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
        },

        toggleAddMenu(event) {
            this.showAddMenu = !this.showAddMenu
            if (!this.showAddMenu) {
                this.showBoardSubmenu = false
            } else {
                this.$nextTick(() => {
                    const btn = event.target.closest('.ws-plus-btn')
                    if (btn) {
                        const rect = btn.getBoundingClientRect()
                        const menu = this.$el.querySelector('.add-menu')
                        if (menu) {
                            menu.style.top = rect.top + 'px'
                        }
                    }
                })
            }
        },

        openBoardSubmenu(event) {
            this.showBoardSubmenu = !this.showBoardSubmenu
            if (this.showBoardSubmenu) {
                this.$nextTick(() => {
                    const btn = event.target.closest('.add-menu-item')
                    if (btn) {
                        const rect = btn.getBoundingClientRect()
                        const submenu = this.$el.querySelector('.board-submenu')
                        if (submenu) {
                            submenu.style.top = rect.top + 'px'
                        }
                    }
                })
            }
        },

        openCreateBoardModal() {
            this.showAddMenu = false
            this.showBoardSubmenu = false
            this.showCreateModal = true
            this.$nextTick(() => {
                this.$refs.boardNameInput?.focus()
            })
        },

        closeCreateModal() {
            this.showCreateModal = false
            this.newBoard = { name: '', description: '', type: 'main' }
        },

        closeAllMenus() {
            this.showAddMenu = false
            this.showBoardSubmenu = false
            this.activeBoardMenu = null
        },

        toggleBoardMenu(board, event) {
            if (this.activeBoardMenu?.id === board.id) {
                this.activeBoardMenu = null
                return
            }
            this.activeBoardMenu = board
            this.$nextTick(() => {
                const menu = this.$el.querySelector('.board-ctx-menu')
                if (menu) {
                    const rect = event.target.closest('.ws-board-item').getBoundingClientRect()
                    menu.style.top = rect.bottom + 4 + 'px'
                    menu.style.left = '260px'
                }
            })
        },

        openBoardInNewTab(board) {
            window.open(`/boards/${board.id}`, '_blank')
            this.activeBoardMenu = null
        },

        startRenameBoard(board) {
            this.renamingBoard = board
            this.renameValue = board.name
            this.activeBoardMenu = null
            this.$nextTick(() => {
                this.$refs.renameInput?.focus()
                this.$refs.renameInput?.select()
            })
        },

        async confirmRename() {
            if (!this.renameValue.trim() || !this.renamingBoard) return
            try {
                await axios.put(`/api/boards/${this.renamingBoard.id}`, {
                    name: this.renameValue
                }, { headers: this.headers() })

                const board = this.recentBoards.find(b => b.id === this.renamingBoard.id)
                if (board) board.name = this.renameValue

                this.$emit('board-renamed', { id: this.renamingBoard.id, name: this.renameValue })
            } catch (e) {
                console.error(e)
            } finally {
                this.renamingBoard = null
            }
        },

        async duplicateBoard(board) {
            this.activeBoardMenu = null

            const tempBoard = { ...board, id: 'temp_' + Date.now(), name: board.name + ' (Copy)' }
            this.recentBoards.unshift(tempBoard)
            if (this.recentBoards.length > 5) this.recentBoards.pop()
            this.$emit('board-created', tempBoard)

            try {
                const res = await axios.post('/api/boards', {
                    name: board.name + ' (Copy)',
                    description: board.description || '',
                }, { headers: this.headers() })

                const idx = this.recentBoards.findIndex(b => b.id === tempBoard.id)
                if (idx !== -1) this.recentBoards.splice(idx, 1, res.data)
                this.$emit('board-duplicated', { tempId: tempBoard.id, board: res.data })

            } catch (e) {
                this.recentBoards = this.recentBoards.filter(b => b.id !== tempBoard.id)
                console.error(e)
            }
        },

        async deleteBoard(board) {
            this.activeBoardMenu = null
            if (!confirm(`Delete "${board.name}"?`)) return
            try {
                await axios.delete(`/api/boards/${board.id}`, { headers: this.headers() })
                this.recentBoards = this.recentBoards.filter(b => b.id !== board.id)
                this.$emit('board-deleted', board.id)

                if (this.$route.path === `/boards/${board.id}`) {
                    this.$router.push('/boards')
                }
            } catch (e) {
                console.error(e)
            }
        },

        goToBoard(id) {
            this.$router.push(`/boards/${id}`)
        },

        headers() {
            return { Authorization: `Bearer ${localStorage.getItem('token')}` }
        },

     async fetchRecentBoards() {
    try {
        const res = await axios.get('/api/boards', { headers: this.headers() })
        const boards = Array.isArray(res.data) ? res.data : (res.data?.data || [])
        this.recentBoards = boards.slice(0, 5)
    } catch (e) {
        console.error(e)
    }
},


     async createBoard() {
    if (!this.newBoard.name.trim() || this.creating) return

    this.creating = true
    const boardData = { ...this.newBoard }
    this.closeCreateModal()

    try {
        const res = await axios.post('/api/boards', {
            name: boardData.name,
            description: boardData.description,
        }, { headers: this.headers() })

        console.log('CREATE RESPONSE:', res.data)  // <-- yeh lagao

        const newBoard = res.data?.data || res.data

        console.log('NEW BOARD:', newBoard)  // <-- aur yeh bhi
        console.log('NEW BOARD ID:', newBoard?.id)  // <-- aur yeh

        this.recentBoards.unshift(newBoard)
        if (this.recentBoards.length > 5) this.recentBoards.pop()

        this.showBoardList = true
        this.$emit('board-created', newBoard)
        this.$router.push(`/boards/${newBoard.id}`)

    } catch (e) {
        console.error('Board create error:', e)
        await this.fetchRecentBoards()
    } finally {
        this.creating = false
    }
},

        async logout() {
            try {
                await axios.post('/api/logout', {}, {
                    headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
                })
            } catch (e) {}

            localStorage.removeItem('token')
            localStorage.removeItem('user')
            this.$router.push('/login')
        }
    }
}
</script>

<style scoped>
.prosix-layout {
    min-height: 100vh;
    display: flex;
    background: #f6f7fb;
}

/* ── Sidebar ── */
.prosix-sidebar {
    width: 250px;
    min-height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: linear-gradient(180deg, #181b25 0%, #11131b 100%);
    color: #fff;
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(255,255,255,0.08);
    box-shadow: 8px 0 24px rgba(0,0,0,0.12);
    z-index: 1000;
    overflow: visible;
}

/* Logo */
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
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6161ff, #00c2ff);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 18px;
    color: #fff;
    box-shadow: 0 8px 18px rgba(97,97,255,0.35);
}

.sidebar-logo h5 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -0.4px;
}

.sidebar-logo small {
    color: #8d93a8;
    font-size: 11px;
}

/* User Card */
.user-card {
    margin: 16px;
    padding: 12px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

.user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #ff3d71;
    color: #fff;
    font-size: 14px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.user-info { overflow: hidden; }

.user-name {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    font-size: 11px;
    color: #9aa0b8;
    margin-top: 2px;
}

/* Nav */
.sidebar-nav {
    padding: 4px 12px;
    flex-shrink: 0;
}

.nav-link-custom {
    height: 44px;
    padding: 0 14px;
    margin-bottom: 6px;
    border-radius: 12px;
    color: #aeb4c8;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 11px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.18s ease;
    position: relative;
}

.nav-link-custom:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
    transform: translateX(3px);
}

.nav-link-custom.active {
    background: #ffffff;
    color: #181b25;
    box-shadow: 0 8px 20px rgba(0,0,0,0.18);
}

.nav-link-custom.active::before {
    content: "";
    position: absolute;
    left: -8px;
    width: 4px;
    height: 24px;
    border-radius: 20px;
    background: #6161ff;
}

.nav-icon {
    width: 22px;
    display: inline-flex;
    justify-content: center;
}

/* ── Workspace Section ── */
.workspace-section {
    margin: 0 12px;
    flex: 1;
    position: relative;
}

.workspace-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 4px;
    margin-bottom: 4px;
    cursor: pointer;
    border-radius: 8px;
    transition: background 0.15s;
    user-select: none;
}

.workspace-header:hover {
    background: rgba(255,255,255,0.05);
}

.workspace-label {
    font-size: 11px;
    font-weight: 700;
    color: #5c6180;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.workspace-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}

.workspace-chevron {
    font-size: 10px;
    color: #5c6180;
    margin-left: 2px;
    transition: color 0.15s;
}

.workspace-header:hover .workspace-chevron {
    color: #9aa0b8;
}

.ws-icon-btn {
    width: 26px;
    height: 26px;
    border: none;
    background: transparent;
    color: #6b7280;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transition: all 0.15s;
}

.ws-icon-btn:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

.ws-plus-btn {
    background: rgba(255,255,255,0.08);
    color: #c5cae0;
    font-weight: 700;
}

.ws-plus-btn:hover {
    background: #ffffff;
    color: #181b25;
}

/* ── Board List Transition ── */
.board-list-enter-active,
.board-list-leave-active {
    transition: all 0.2s ease;
    overflow: hidden;
}

.board-list-enter-from,
.board-list-leave-to {
    opacity: 0;
    max-height: 0;
}

.board-list-enter-to,
.board-list-leave-from {
    opacity: 1;
    max-height: 300px;
}

/* ── Add Menu Dropdown ── */
.add-menu {
    position: fixed;
    left: 260px;
    top: auto;
    width: 240px;
    background: #1e2130;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 10px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    padding: 8px;
    z-index: 99999;
}

.add-menu-header {
    font-size: 11px;
    font-weight: 700;
    color: #5c6180;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    padding: 6px 10px;
    margin-bottom: 4px;
}

.add-menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 10px;
    border: none;
    background: transparent;
    color: #c5cae0;
    border-radius: 7px;
    font-size: 14px;
    font-weight: 500;
    text-align: left;
    transition: all 0.15s;
    position: relative;
}

.add-menu-item:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.add-menu-icon {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}

.board-icon     { background: rgba(97, 97, 255, 0.2); color: #8888ff; }
.doc-icon       { background: rgba(0, 194, 255, 0.15); color: #00c2ff; }
.dashboard-icon { background: rgba(0, 200, 117, 0.15); color: #00c875; }
.form-icon      { background: rgba(255, 171, 61, 0.15); color: #fdab3d; }
.folder-icon    { background: rgba(255, 61, 113, 0.15); color: #ff3d71; }
.import-icon    { background: rgba(160, 160, 160, 0.15); color: #9aa0b8; }

.add-menu-arrow {
    margin-left: auto;
    font-size: 11px;
    color: #5c6180;
}

.add-menu-divider {
    height: 1px;
    background: rgba(255,255,255,0.07);
    margin: 6px 0;
}

/* Board Submenu */
.board-submenu {
    position: fixed;
    left: 508px;
    top: auto;
    width: 220px;
    background: #1e2130;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 10px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    padding: 8px;
    z-index: 100000;
}

.submenu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 12px;
    border: none;
    background: transparent;
    color: #c5cae0;
    border-radius: 7px;
    font-size: 14px;
    font-weight: 500;
    text-align: left;
    transition: all 0.15s;
}

.submenu-item:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.submenu-icon {
    font-size: 14px;
    width: 18px;
    text-align: center;
    color: #6b7280;
}

/* ── Workspace Board List ── */
.ws-board-list {
    margin-top: 4px;
}

.ws-board-item {
    display: flex;
    align-items: center;
    gap: 9px;
    height: 34px;
    padding: 0 6px 0 10px;
    border-radius: 8px;
    cursor: pointer;
    color: #8d93a8;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.15s;
    margin-bottom: 2px;
    position: relative;
}

.ws-board-item:hover {
    background: rgba(255,255,255,0.06);
    color: #c5cae0;
}

.ws-board-item.active {
    background: rgba(97, 97, 255, 0.15);
    color: #a0a4ff;
}

.ws-board-icon {
    font-size: 12px;
    opacity: 0.6;
    flex-shrink: 0;
}

.ws-board-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

/* Three-dot button on board item */
.ws-board-dots {
    display: none;
    border: none;
    background: rgba(255,255,255,0.08);
    color: #c5cae0;
    width: 24px;
    height: 24px;
    border-radius: 5px;
    font-size: 12px;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-left: auto;
    transition: background 0.15s;
}

.ws-board-item:hover .ws-board-dots {
    display: flex;
}

.ws-board-dots:hover {
    background: rgba(255,255,255,0.18);
}

/* Board context menu */
.board-ctx-menu {
    position: fixed;
    width: 210px;
    background: #252836;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.55);
    padding: 6px;
    z-index: 99999;
}

.ctx-item {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 12px;
    border: none;
    background: transparent;
    color: #c5cae0;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 500;
    text-align: left;
    transition: all 0.13s;
}

.ctx-item:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.ctx-danger { color: #ff6b81; }
.ctx-danger:hover { background: rgba(255,61,87,0.12); color: #ff3d57; }

.ctx-icon {
    font-size: 13px;
    width: 16px;
    text-align: center;
    opacity: 0.7;
}

.ctx-divider {
    height: 1px;
    background: rgba(255,255,255,0.07);
    margin: 4px 0;
}

/* Rename inline box */
.rename-overlay {
    position: fixed;
    inset: 0;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.4);
}

.rename-box {
    background: #1e2130;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    padding: 20px;
    width: 320px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

.rename-label {
    font-size: 14px;
    font-weight: 700;
    color: #c5cae0;
    margin-bottom: 10px;
}

.rename-input {
    width: 100%;
    background: rgba(255,255,255,0.06);
    border: 1.5px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    color: #fff;
    padding: 9px 12px;
    font-size: 14px;
    outline: none;
    margin-bottom: 14px;
}

.rename-input:focus {
    border-color: #0073ea;
}

.rename-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.rename-cancel {
    border: 1px solid rgba(255,255,255,0.15);
    background: transparent;
    color: #8d93a8;
    border-radius: 7px;
    padding: 7px 16px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.13s;
}

.rename-cancel:hover { background: rgba(255,255,255,0.06); color: #fff; }

.rename-confirm {
    border: none;
    background: #0073ea;
    color: #fff;
    border-radius: 7px;
    padding: 7px 16px;
    font-size: 13px;
    font-weight: 700;
    transition: all 0.13s;
}

.rename-confirm:hover { background: #0060c7; }

/* ── Bottom ── */
.sidebar-bottom {
    padding: 16px;
    border-top: 1px solid rgba(255,255,255,0.08);
    flex-shrink: 0;
}

.logout-btn {
    width: 100%;
    height: 42px;
    border: 1px solid rgba(255,255,255,0.12);
    background: rgba(255,255,255,0.05);
    color: #d7d9e5;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.18s ease;
}

.logout-btn:hover {
    background: #fff;
    border-color: #000;
    color: #000;
}

/* ── Main ── */
.prosix-main {
    margin-left: 250px;
    flex: 1;
    min-height: 100vh;
    background: #f6f7fb;
}

/* ── Create Board Modal ── */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(10, 12, 20, 0.65);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 99999;
    backdrop-filter: blur(4px);
}

.create-modal {
    background: #fff;
    width: 500px;
    border-radius: 16px;
    box-shadow: 0 40px 100px rgba(0,0,0,0.25);
    overflow: hidden;
}

.create-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 1px solid #e9ecef;
}

.create-modal-header h5 {
    font-size: 17px;
    color: #172b4d;
}

.modal-close-btn {
    border: none;
    background: #f3f4f6;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}

.modal-close-btn:hover {
    background: #e5e7eb;
    color: #111;
}

.create-modal-body {
    padding: 20px 24px;
}

.create-modal-body .form-control {
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 14px;
    transition: border-color 0.15s;
}

.create-modal-body .form-control:focus {
    border-color: #0073ea;
    box-shadow: 0 0 0 3px rgba(0,115,234,0.12);
}

.board-type-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.board-type-card {
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.board-type-card:hover {
    border-color: #0073ea;
    color: #0073ea;
    background: #f0f7ff;
}

.board-type-card.selected {
    border-color: #0073ea;
    background: #e8f3ff;
    color: #0073ea;
}

.board-type-icon {
    font-size: 20px;
}

.create-modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 16px 24px;
    border-top: 1px solid #e9ecef;
    background: #f9fafb;
}

.btn-primary-custom {
    background: #0073ea;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 9px 20px;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.15s;
}

.btn-primary-custom:hover:not(:disabled) {
    background: #0060c7;
}

.btn-primary-custom:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
    .prosix-sidebar { width: 220px; }
    .prosix-main { margin-left: 220px; }
    .sidebar-logo h5 { font-size: 16px; }
    .create-modal { width: 95vw; }
}
</style>
