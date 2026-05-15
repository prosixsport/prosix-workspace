<template>
    <AppLayout>
        <div class="p-4">

            <!-- Header -->
            <div class="mb-4">
                <h4 class="fw-bold mb-1">Good morning, {{ user?.name }}! 👋</h4>
                <p class="text-muted">Here's what's happening today.</p>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">Total Boards</div>
                                    <div class="fw-bold fs-3">{{ stats.totalBoards }}</div>
                                </div>
                                <div style="font-size:32px;">📋</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">Total Tasks</div>
                                    <div class="fw-bold fs-3">{{ stats.totalTasks }}</div>
                                </div>
                                <div style="font-size:32px;">✅</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">In Progress</div>
                                    <div class="fw-bold fs-3">{{ stats.inProgress }}</div>
                                </div>
                                <div style="font-size:32px;">⏳</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">Completed</div>
                                    <div class="fw-bold fs-3">{{ stats.completed }}</div>
                                </div>
                                <div style="font-size:32px;">🎉</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Boards -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Recent Boards</h6>
                    <router-link to="/boards" class="btn btn-sm text-white" style="background:#ff3d57;">
                        View All
                    </router-link>
                </div>
                <div class="card-body p-0">
                    <div v-if="recentBoards.length === 0" class="text-center py-5 text-muted">
                        <div style="font-size:40px;">📋</div>
                        <p class="mt-2">No boards yet!</p>
                        <router-link to="/boards" class="btn btn-sm text-white" style="background:#ff3d57;">
                            + New Board
                        </router-link>
                    </div>
                    <table v-else class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">Board Name</th>
                                <th>Tasks</th>
                                <th>Created</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="board in recentBoards" :key="board.id">
                                <td class="px-4 fw-semibold">{{ board.name }}</td>
                                <td>
                                    <span class="badge bg-primary">{{ board.items_count }} tasks</span>
                                </td>
                                <td class="text-muted small">{{ formatDate(board.created_at) }}</td>
                                <td>
                                    <router-link :to="`/boards/${board.id}`" class="btn btn-sm btn-outline-secondary">
                                        Open
                                    </router-link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- My Tasks -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">My Tasks</h6>
                </div>
                <div class="card-body p-0">
                    <div v-if="myTasks.length === 0" class="text-center py-4 text-muted">
                        No tasks assigned to you yet!
                    </div>
                    <table v-else class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">Task</th>
                                <th>Board</th>
                                <th>Status</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="task in myTasks" :key="task.id">
                                <td class="px-4">{{ task.title }}</td>
                                <td class="text-muted small">{{ task.board?.name }}</td>
                                <td>
                                    <span class="badge text-white" :style="{ background: statusColor(task.status) }">
                                        {{ task.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ task.due_date || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script>
import AppLayout from './layouts/AppLayout.vue'
import axios from 'axios'

export default {
    name: 'Dashboard',
    components: { AppLayout },
    data() {
        return {
            stats: {
                totalBoards: 0,
                totalTasks:  0,
                inProgress:  0,
                completed:   0,
            },
            recentBoards: [],
            myTasks: [],
        }
    },
    computed: {
        user() {
            try {
                return JSON.parse(localStorage.getItem('user'))
            } catch (e) {
                return null
            }
        }
    },
    mounted() {
        this.fetchDashboard()
    },
    methods: {
        headers() {
            return { Authorization: `Bearer ${localStorage.getItem('token')}` }
        },

      async fetchDashboard() {
    try {
        const token = localStorage.getItem('token')
        if (!token) return

        const res = await axios.get('/api/dashboard', {
            headers: { Authorization: `Bearer ${token}` }
        })
        this.stats        = res.data.stats
        this.recentBoards = res.data.recent_boards
        this.myTasks      = res.data.my_tasks
    } catch (e) {
        console.error(e)
    }
},

        statusColor(status) {
            const colors = {
                'not_started':   '#c4c4c4',
                'working_on_it': '#fdab3d',
                'stuck':         '#e2445c',
                'done':          '#00c875',
            }
            return colors[status] || '#c4c4c4'
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString()
        }
    }
}
</script>
