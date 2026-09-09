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

                                <th>Price List Files</th>

                                <th>Status</th>

                                <th width="180">Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr v-for="client in clients" :key="client.id">

                                <td data-label="Name">{{ client.name }}</td>

                                <td data-label="Email">{{ client.email || '-' }}</td>

                                <td data-label="Phone">{{ client.phone || '-' }}</td>

                                <td data-label="Company">{{ client.company || '-' }}</td>

                                <td data-label="Files">
                                    <div v-if="clientPriceFiles(client).length" class="client-table-files">
                                        <button
                                            v-for="file in clientPriceFiles(client).slice(0, 2)"
                                            :key="file.id"
                                            type="button"
                                            :title="file.name"
                                            @click.stop="openFileViewer(client, file)"
                                        >
                                            <img v-if="isImageFile(file)" :src="priceListFileUrl(file)" :alt="file.name" />
                                            <i v-else :class="fileIcon(file)"></i>
                                        </button>
                                        <button v-if="clientPriceFiles(client).length > 2" type="button" @click.stop="openFileViewer(client, clientPriceFiles(client)[2])">
                                            +{{ clientPriceFiles(client).length - 2 }}
                                        </button>
                                    </div>
                                    <span v-else class="no-files">No files</span>
                                </td>

                                <td data-label="Status"><span class="badge">{{ client.status }}</span></td>

                                <td data-label="Action">

                                    <button class="icon-btn view" title="View complete details" @click="openClientDetails(client)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <button class="icon-btn" @click="openModal(client)">

                                        <i class="fa-solid fa-pen"></i>

                                    </button>

                                    <button

                                        class="icon-btn danger"

                                        :disabled="deletingId === client.id"

                                        @click="deleteClient(client.id)"

                                    >

                                        <i :class="deletingId === client.id ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-trash'"></i>

                                    </button>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

            <div v-if="detailsModal && selectedClient" class="modal-overlay details-overlay" @click.self="closeClientDetails">
                <div class="client-details-modal">
                    <button class="close" type="button" @click="closeClientDetails">×</button>

                    <div class="details-head">
                        <span class="modal-kicker">CUSTOMER PROFILE</span>
                        <h2>{{ selectedClient.name }}</h2>
                        <p>{{ selectedClient.company || selectedClient.team_name || 'Client details' }}</p>
                    </div>

                    <div class="details-grid">
                        <div v-for="item in clientDetailItems(selectedClient)" :key="item.label" class="detail-item" :class="{ wide: item.wide }">
                            <small>{{ item.label }}</small>
                            <strong>{{ item.value || '-' }}</strong>
                        </div>
                    </div>

                    <section class="details-files-section">
                        <div class="details-section-title">
                            <div>
                                <h3>Price List Files</h3>
                                <p>{{ clientPriceFiles(selectedClient).length }} uploaded file(s)</p>
                            </div>
                        </div>

                        <div v-if="clientPriceFiles(selectedClient).length" class="details-file-grid">
                            <article v-for="file in clientPriceFiles(selectedClient)" :key="file.id" class="details-file-card">
                                <button type="button" class="details-file-preview" @click="openFileViewer(selectedClient, file)">
                                    <img v-if="isImageFile(file)" :src="priceListFileUrl(file)" :alt="file.name" />
                                    <i v-else :class="fileIcon(file)"></i>
                                </button>
                                <div class="details-file-copy">
                                    <strong :title="file.name">{{ file.name }}</strong>
                                    <small>{{ formatFileSize(file.size) }}</small>
                                </div>
                                <div class="details-file-actions">
                                    <button type="button" title="View file" @click="openFileViewer(selectedClient, file)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <a :href="priceListFileUrl(file)" :download="file.name" title="Download file">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                </div>
                            </article>
                        </div>

                        <div v-else class="details-no-files">
                            <i class="fa-regular fa-folder-open"></i>
                            No price list files uploaded.
                        </div>
                    </section>

                    <button class="details-edit-button" type="button" @click="editFromDetails">
                        <i class="fa-solid fa-pen"></i> Edit Client
                    </button>
                </div>
            </div>

            <Teleport to="body">
                <div v-if="fileViewerOpen && activePreviewFile" class="client-file-viewer-overlay" @click.self="closeFileViewer">
                    <section class="client-file-viewer" role="dialog" aria-modal="true" aria-label="File preview">
                        <header class="client-file-viewer-head">
                            <div>
                                <strong :title="activePreviewFile.name">{{ activePreviewFile.name }}</strong>
                                <small>{{ fileViewerIndex + 1 }} of {{ fileViewerFiles.length }}</small>
                            </div>
                            <div class="client-file-viewer-actions">
                                <a :href="priceListFileUrl(activePreviewFile)" :download="activePreviewFile.name" title="Download">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                                <button type="button" title="Close" @click="closeFileViewer">×</button>
                            </div>
                        </header>

                        <div class="client-file-viewer-body">
                            <button v-if="fileViewerFiles.length > 1" type="button" class="viewer-arrow viewer-prev" title="Previous file" @click="previousFile">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>

                            <div class="client-file-viewer-stage">
                                <img v-if="isImageFile(activePreviewFile)" :src="priceListFileUrl(activePreviewFile)" :alt="activePreviewFile.name" />
                                <iframe v-else-if="isPdfFile(activePreviewFile)" :src="priceListFileUrl(activePreviewFile)" :title="activePreviewFile.name"></iframe>
                                <div v-else class="viewer-file-fallback">
                                    <i :class="fileIcon(activePreviewFile)"></i>
                                    <strong>{{ activePreviewFile.name }}</strong>
                                    <p>This file cannot be previewed in the browser.</p>
                                    <a :href="priceListFileUrl(activePreviewFile)" :download="activePreviewFile.name">
                                        <i class="fa-solid fa-download"></i> Download File
                                    </a>
                                </div>
                            </div>

                            <button v-if="fileViewerFiles.length > 1" type="button" class="viewer-arrow viewer-next" title="Next file" @click="nextFile">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </section>
                </div>
            </Teleport>

            <div v-if="modal" class="modal-overlay" @click.self="closeModal">

                <div class="client-modal-box">

                    <button class="close" @click="closeModal">×</button>

                    <div class="client-modal-head">

                        <div>

                            <span class="modal-kicker">CUSTOMER MANAGEMENT</span>

                            <h3>{{ editingId ? 'Edit Client' : 'Add Client' }}</h3>

                            <p>Enter customer, team and sales information.</p>

                        </div>

                    </div>

                    <div class="client-form-grid">

                        <section class="client-form-column">

                            <h4>Customer Information</h4>

                            <div class="field-group full-field">

                                <label>Customer Name *</label>

                                <input v-model="form.name" placeholder="Customer name" />

                            </div>

                            <div class="field-group">

                                <label>Email *</label>

                                <input v-model.trim="form.email" type="email" placeholder="client@example.com" autocomplete="email" />

                            </div>

                            <div class="field-group">

                                <label>Phone #</label>

                                <input v-model="form.phone" type="tel" placeholder="Phone number" />

                            </div>

                            <div class="field-group">

                                <label>Company</label>

                                <input v-model="form.company" placeholder="Company name" />

                            </div>

                            <div class="field-group">

                                <label>Source of Contact</label>

                                <select v-model="form.source_of_contact">

                                    <option value="">Select source</option>

                                    <option v-for="source in contactSources" :key="source" :value="source">{{ source }}</option>

                                </select>

                            </div>

                            <div class="field-group">

                                <label>Country</label>

                                <input v-model="form.country" placeholder="Country" />

                            </div>

                            <div class="field-group">

                                <label>City / State</label>

                                <input v-model="form.city_state" placeholder="City or state" />

                            </div>

                            <div class="field-group full-field">

                                <label>Address</label>

                                <textarea v-model="form.address" placeholder="Complete customer address"></textarea>

                            </div>

                        </section>

                        <section class="client-form-column">

                            <h4>Sales & Team Information</h4>

                            <div class="field-group full-field">

                                <label>Price List</label>

                                <textarea v-model="form.price_list" class="material-textarea" placeholder="Write price list, pricing notes, products or any other material..."></textarea>

                                <label class="price-file-upload">

                                    <input

                                        type="file"

                                        multiple

                                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt"

                                        @change="selectPriceListFiles"

                                    />

                                    <i class="fa-solid fa-cloud-arrow-up"></i>

                                    <span>

                                        <strong>Upload Price List Files</strong>

                                        <small>Photos, PDF, Word, Excel, CSV or text — maximum 10 MB each</small>

                                    </span>

                                </label>

                                <div v-if="priceListUploads.length" class="selected-price-files">

                                    <div v-for="(file, index) in priceListUploads" :key="`${file.name}-${index}`" class="price-file-row">

                                        <i class="fa-regular fa-file"></i>

                                        <span>{{ file.name }}</span>

                                        <small>{{ formatFileSize(file.size) }}</small>

                                        <button type="button" title="Remove" @click="removeNewPriceListFile(index)">×</button>

                                    </div>

                                </div>

                                <div v-if="form.price_list_files.length" class="selected-price-files existing-files">

                                    <div v-for="file in form.price_list_files" :key="file.id" class="price-file-row">

                                        <i :class="String(file.mime_type || '').startsWith('image/') ? 'fa-regular fa-image' : 'fa-regular fa-file-pdf'"></i>

                                        <a :href="priceListFileUrl(file)" target="_blank" rel="noopener">{{ file.name }}</a>

                                        <small>{{ formatFileSize(file.size) }}</small>

                                        <button type="button" title="Remove saved file" @click="removeSavedPriceListFile(file.id)">×</button>

                                    </div>

                                </div>

                            </div>

                            <div class="field-group">

                                <label>Lead Status</label>

                                <select v-model="form.lead_status">

                                    <option v-for="status in leadStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>

                                </select>

                            </div>

                            <div class="field-group">

                                <label>Account Status</label>

                                <select v-model="form.status">

                                    <option value="active">Active</option>

                                    <option value="inactive">Inactive</option>

                                </select>

                            </div>

                            <div class="field-group full-field">

                                <label>Team Name</label>

                                <input v-model="form.team_name" placeholder="Team name" />

                            </div>

                            <div class="field-group full-field">

                                <label>Notes</label>

                                <textarea v-model="form.notes" class="notes-textarea" placeholder="Write full notes, requirements, fabric/material details or any customer information..."></textarea>

                            </div>

                        </section>

                    </div>

                    <p class="field-help" v-if="!editingId">

                        The client will set their own password when they log in for the first time.

                    </p>

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

            deletingId: null,

            modal: false,

            detailsModal: false,

            selectedClient: null,

            fileViewerOpen: false,

            fileViewerFiles: [],

            fileViewerIndex: 0,

            editingId: null,

            clients: [],

            priceListUploads: [],

            contactSources: [

                'Facebook',

                'Instagram',

                'Website',

                'WhatsApp',

                'Referral',

                'Email',

                'Phone Call',

                'Walk-in',

                'Other',

            ],

            leadStatuses: [

                { value: 'new', label: 'New Lead' },

                { value: 'contacted', label: 'Contacted' },

                { value: 'qualified', label: 'Qualified' },

                { value: 'proposal', label: 'Proposal Sent' },

                { value: 'negotiation', label: 'Negotiation' },

                { value: 'won', label: 'Won' },

                { value: 'lost', label: 'Lost' },

                { value: 'follow_up', label: 'Follow Up' },

            ],

            form: {

                name: '',

                email: '',

                phone: '',

                company: '',

                source_of_contact: '',

                country: '',

                city_state: '',

                address: '',

                price_list: '',

                price_list_files: [],

                lead_status: 'new',

                team_name: '',

                notes: '',

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

        },

        activePreviewFile() {
            return this.fileViewerFiles[this.fileViewerIndex] || null
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

        clientPriceFiles(client) {
            const files = client?.price_list_files
            if (Array.isArray(files)) return files
            if (typeof files === 'string') {
                try {
                    const parsed = JSON.parse(files)
                    return Array.isArray(parsed) ? parsed : []
                } catch (e) {
                    return []
                }
            }
            return []
        },

        isImageFile(file) {
            const mime = String(file?.mime_type || '').toLowerCase()
            const name = String(file?.name || '').toLowerCase()
            return mime.startsWith('image/') || /\.(jpg|jpeg|png|webp|gif)$/.test(name)
        },

        isPdfFile(file) {
            const mime = String(file?.mime_type || '').toLowerCase()
            const name = String(file?.name || '').toLowerCase()
            return mime === 'application/pdf' || name.endsWith('.pdf')
        },

        openFileViewer(client, file) {
            const files = this.clientPriceFiles(client)
            if (!files.length) return
            const index = files.findIndex(item => String(item.id) === String(file?.id))
            this.fileViewerFiles = files
            this.fileViewerIndex = index >= 0 ? index : 0
            this.fileViewerOpen = true
        },

        closeFileViewer() {
            this.fileViewerOpen = false
            this.fileViewerFiles = []
            this.fileViewerIndex = 0
        },

        previousFile() {
            const total = this.fileViewerFiles.length
            if (total) this.fileViewerIndex = (this.fileViewerIndex - 1 + total) % total
        },

        nextFile() {
            const total = this.fileViewerFiles.length
            if (total) this.fileViewerIndex = (this.fileViewerIndex + 1) % total
        },

        fileIcon(file) {
            const name = String(file?.name || '').toLowerCase()
            if (name.endsWith('.pdf')) return 'fa-solid fa-file-pdf'
            if (/\.(doc|docx)$/.test(name)) return 'fa-solid fa-file-word'
            if (/\.(xls|xlsx|csv)$/.test(name)) return 'fa-solid fa-file-excel'
            return 'fa-solid fa-file-lines'
        },

        openClientDetails(client) {
            this.selectedClient = client
            this.detailsModal = true
        },

        closeClientDetails() {
            this.detailsModal = false
            this.selectedClient = null
        },

        editFromDetails() {
            const client = this.selectedClient
            this.closeClientDetails()
            this.openModal(client)
        },

        clientDetailItems(client) {
            const lead = this.leadStatuses.find(item => item.value === client.lead_status)?.label
            return [
                { label: 'Customer Name', value: client.name },
                { label: 'Email', value: client.email },
                { label: 'Phone #', value: client.phone },
                { label: 'Company', value: client.company },
                { label: 'Source of Contact', value: client.source_of_contact },
                { label: 'Country', value: client.country },
                { label: 'City / State', value: client.city_state },
                { label: 'Team Name', value: client.team_name },
                { label: 'Lead Status', value: lead || client.lead_status },
                { label: 'Account Status', value: client.status },
                { label: 'Address', value: client.address, wide: true },
                { label: 'Price List / Pricing Details', value: client.price_list, wide: true },
                { label: 'Notes', value: client.notes, wide: true },
            ]
        },

        emptyForm() {

            return {

                name: '',

                email: '',

                phone: '',

                company: '',

                source_of_contact: '',

                country: '',

                city_state: '',

                address: '',

                price_list: '',

                price_list_files: [],

                lead_status: 'new',

                team_name: '',

                notes: '',

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

                    phone: client.phone || '',

                    company: client.company || '',

                    source_of_contact: client.source_of_contact || '',

                    country: client.country || '',

                    city_state: client.city_state || '',

                    address: client.address || '',

                    price_list: client.price_list || '',

                    price_list_files: Array.isArray(client.price_list_files) ? [...client.price_list_files] : [],

                    lead_status: client.lead_status || 'new',

                    team_name: client.team_name || '',

                    notes: client.notes || '',

                    status: client.status || 'active',

                }

            } else {

                this.editingId = null

                this.form = this.emptyForm()

            }

            this.modal = true

            this.priceListUploads = []

        },

        closeModal() {

            this.modal = false

            this.priceListUploads = []

        },

        selectPriceListFiles(event) {

            const picked = Array.from(event.target.files || [])

            const allowed = picked.filter(file => file.size <= 10 * 1024 * 1024)

            if (allowed.length !== picked.length) alert('Each file must be 10 MB or smaller')

            this.priceListUploads = [...this.priceListUploads, ...allowed].slice(0, 10)

            event.target.value = ''

        },

        removeNewPriceListFile(index) {

            this.priceListUploads.splice(index, 1)

        },

        removeSavedPriceListFile(id) {

            this.form.price_list_files = this.form.price_list_files.filter(file => String(file.id) !== String(id))

        },

        priceListFileUrl(file) {
            const path = String(file?.path || '').replace(/^\/+/, '')

            if (path) {
                return `${window.location.origin}/storage/${path}`
            }

            const savedUrl = String(file?.url || '')
            if (!savedUrl) return '#'

            try {
                const parsed = new URL(savedUrl, window.location.origin)
                return `${window.location.origin}${parsed.pathname}`
            } catch (e) {
                return savedUrl
            }

        },

        formatFileSize(bytes) {

            const size = Number(bytes || 0)

            if (size < 1024) return `${size} B`

            if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`

            return `${(size / (1024 * 1024)).toFixed(1)} MB`

        },

        async saveClient() {

            this.form.name = this.form.name.trim()

            this.form.email = this.form.email.trim().toLowerCase()

            if (!this.form.name) {

                alert('Client name required')

                return

            }

            if (!this.form.email) {

                alert('Client email required')

                return

            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

            if (!emailPattern.test(this.form.email)) {

                alert('Please enter a valid client email')

                return

            }

            this.saving = true

            try {

                const payload = new FormData()

                Object.entries(this.form).forEach(([key, value]) => {

                    if (key !== 'price_list_files') payload.append(key, value ?? '')

                })

                this.priceListUploads.forEach(file => payload.append('price_list_files[]', file))

                payload.append('keep_price_list_file_ids', JSON.stringify(this.form.price_list_files.map(file => file.id)))

                if (this.editingId) payload.append('_method', 'PUT')

                await axios.post(

                    this.editingId ? `/api/clients/${this.editingId}` : '/api/clients',

                    payload,

                    { headers: this.headers() }

                )

                this.closeModal()

                this.fetchClients()

            } catch (e) {

                const errors = e.response?.data?.errors

                const firstError = errors

                    ? Object.values(errors).flat()[0]

                    : null

                alert(firstError || e.response?.data?.message || 'Client save failed')

            } finally {

                this.saving = false

            }

        },

        async deleteClient(id) {

            if (!confirm('Permanently delete this client and login account?')) return

            this.deletingId = id

            try {

                await axios.delete(`/api/clients/${id}`, { headers: this.headers() })

                this.clients = this.clients.filter(client => client.id !== id)

            } catch (e) {

                alert(e.response?.data?.message || 'Client delete failed')

            } finally {

                this.deletingId = null

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

table { width: 100%; border-collapse: collapse; min-width: 940px; }

th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f1f1f1; font-size: 14px; }

th { background: #fafafa; color: #6b7280; font-size: 12px; text-transform: uppercase; }

.badge { background: #111; color: #fff; border-radius: 999px; padding: 5px 10px; font-size: 11px; font-weight: 800; }

.icon-btn { border: none; background: #f3f4f6; width: 34px; height: 34px; border-radius: 9px; margin-right: 6px; cursor: pointer; }

.icon-btn.danger { background: #fee2e2; color: #b91c1c; }
.icon-btn.view { background: #eef2ff; color: #4338ca; }

.icon-btn:disabled { opacity: .6; cursor: not-allowed; }

.empty { padding: 40px; text-align: center; color: #6b7280; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.55); display: flex; align-items: center; justify-content: center; z-index: 99999; padding: 15px; }

.close { position: absolute; right: 15px; top: 12px; border: none; background: #f3f4f6; width: 34px; height: 34px; border-radius: 10px; font-size: 22px; cursor: pointer; }

.client-modal-box h3 { margin: 3px 0 0; color: #0f172a; font-size: 24px; font-weight: 900; }

label { display: block; margin: 12px 0 6px; font-size: 13px; font-weight: 800; }

input, textarea, select { width: 100%; border: 1.5px solid #d1d5db; border-radius: 10px; padding: 11px 12px; outline: none; }

.field-help { margin: 7px 0 0; color: #6b7280; font-size: 12px; line-height: 1.5; }

textarea { min-height: 80px; resize: vertical; }

.save-btn { width: 100%; margin-top: 16px; min-height: 46px; }

.client-modal-box { display: block; background: #fff; width: min(980px, 96vw); max-height: 92vh; overflow-y: auto; border-radius: 20px; padding: 24px; position: relative; z-index: 100000; box-shadow: 0 28px 80px rgba(15,23,42,.28); }

.client-modal-head { padding: 0 48px 16px 0; border-bottom: 1px solid #e5e7eb; }

.client-modal-head p { margin: 5px 0 0; color: #64748b; font-size: 13px; }

.modal-kicker { color: #6161ff; font-size: 10px; font-weight: 900; letter-spacing: .8px; }

.client-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; margin-top: 18px; }

.client-form-column { min-width: 0; padding: 18px; border: 1px solid #e5e7eb; border-radius: 14px; background: #f8fafc; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-content: start; gap: 0 12px; }

.client-form-column h4 { grid-column: 1 / -1; margin: 0 0 6px; color: #334155; font-size: 12px; font-weight: 900; letter-spacing: .35px; text-transform: uppercase; }

.field-group { min-width: 0; }

.field-group.full-field { grid-column: 1 / -1; }

.field-group label { color: #334155; }

.field-group input, .field-group textarea, .field-group select { background: #fff; }

.field-group input:focus, .field-group textarea:focus, .field-group select:focus { border-color: #6161ff; box-shadow: 0 0 0 3px rgba(97,97,255,.12); }

.material-textarea { min-height: 130px; }

.notes-textarea { min-height: 170px; }

.price-file-upload { margin-top: 10px; min-height: 76px; padding: 12px; border: 1.5px dashed #a5b4fc; border-radius: 11px; background: #fff; color: #4338ca; display: flex; align-items: center; gap: 11px; cursor: pointer; transition: .18s ease; }

.price-file-upload:hover { border-color: #6161ff; background: #f5f5ff; }

.price-file-upload input { display: none; }

.price-file-upload > i { width: 38px; height: 38px; border-radius: 9px; background: #eef2ff; display: grid; place-items: center; font-size: 17px; }

.price-file-upload span { min-width: 0; display: flex; flex-direction: column; gap: 3px; }

.price-file-upload strong { color: #312e81; font-size: 12px; }

.price-file-upload small { color: #64748b; font-size: 10px; line-height: 1.35; }

.selected-price-files { margin-top: 8px; display: grid; gap: 5px; }

.price-file-row { min-width: 0; min-height: 38px; padding: 6px 7px 6px 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; display: grid; grid-template-columns: 18px minmax(0,1fr) auto 27px; align-items: center; gap: 7px; color: #475569; }

.price-file-row span, .price-file-row a { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #1e293b; font-size: 11px; font-weight: 700; text-decoration: none; }

.price-file-row a:hover { color: #4f46e5; text-decoration: underline; }

.price-file-row small { color: #94a3b8; font-size: 9px; white-space: nowrap; }

.price-file-row button { width: 27px; height: 27px; border: 0; border-radius: 7px; background: #fee2e2; color: #dc2626; cursor: pointer; font-size: 16px; }
.client-table-files { display: flex; align-items: center; gap: 5px; }
.client-table-files > a, .client-table-files > button { width: 34px; height: 34px; flex: 0 0 34px; padding: 0; border: 1px solid #dbe3ef; border-radius: 8px; background: #f8fafc; color: #475569; display: grid; place-items: center; overflow: hidden; text-decoration: none; cursor: pointer; font-size: 10px; font-weight: 900; }
.client-table-files img { width: 100%; height: 100%; object-fit: cover; }
.client-table-files i { font-size: 15px; }
.no-files { color: #94a3b8; font-size: 11px; }
.details-overlay { z-index: 120000; }
.client-details-modal { position: relative; width: min(900px, 96vw); max-height: 92vh; overflow-y: auto; padding: 24px; border-radius: 20px; background: #fff; box-shadow: 0 28px 80px rgba(15,23,42,.3); }
.details-head { padding: 0 50px 17px 0; border-bottom: 1px solid #e5e7eb; }
.details-head h2 { margin: 4px 0 0; color: #0f172a; font-size: 25px; }
.details-head p { margin: 5px 0 0; color: #64748b; font-size: 12px; }
.details-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 10px; margin-top: 16px; }
.detail-item { min-width: 0; min-height: 76px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 11px; background: #f8fafc; display: flex; flex-direction: column; gap: 6px; }
.detail-item.wide { grid-column: span 2; }
.detail-item small { color: #64748b; font-size: 9px; font-weight: 900; letter-spacing: .35px; text-transform: uppercase; }
.detail-item strong { color: #1e293b; font-size: 12px; line-height: 1.45; white-space: pre-wrap; overflow-wrap: anywhere; }
.details-files-section { margin-top: 18px; padding-top: 17px; border-top: 1px solid #e5e7eb; }
.details-section-title h3 { margin: 0; color: #0f172a; font-size: 16px; }
.details-section-title p { margin: 4px 0 0; color: #64748b; font-size: 10px; }
.details-file-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 9px; margin-top: 12px; }
.details-file-card { min-width: 0; padding: 8px; border: 1px solid #e2e8f0; border-radius: 11px; background: #fff; display: grid; grid-template-columns: 48px minmax(0,1fr) auto; align-items: center; gap: 9px; }
.details-file-preview { width: 48px; height: 48px; padding: 0; border: 0; border-radius: 8px; background: #f1f5f9; color: #475569; display: grid; place-items: center; overflow: hidden; text-decoration: none; cursor: pointer; }
.details-file-preview img { width: 100%; height: 100%; object-fit: cover; }
.details-file-preview i { font-size: 21px; }
.details-file-copy { min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.details-file-copy strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #1e293b; font-size: 11px; }
.details-file-copy small { color: #94a3b8; font-size: 9px; }
.details-file-actions { display: flex; gap: 5px; }
.details-file-actions a, .details-file-actions button { width: 31px; height: 31px; padding: 0; border: 0; border-radius: 7px; background: #eef2ff; color: #4338ca; display: grid; place-items: center; text-decoration: none; cursor: pointer; }
.details-file-actions a:last-child { background: #ecfdf5; color: #059669; }
.client-file-viewer-overlay { position: fixed; inset: 0; z-index: 250000; padding: 24px; background: rgba(2,6,23,.78); backdrop-filter: blur(5px); display: grid; place-items: center; }
.client-file-viewer { width: min(1180px, 96vw); height: min(850px, 93vh); overflow: hidden; border-radius: 18px; background: #fff; box-shadow: 0 30px 90px rgba(0,0,0,.45); display: flex; flex-direction: column; }
.client-file-viewer-head { min-height: 70px; padding: 12px 16px 12px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
.client-file-viewer-head > div:first-child { min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.client-file-viewer-head strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #0f172a; font-size: 14px; }
.client-file-viewer-head small { color: #64748b; font-size: 10px; font-weight: 800; }
.client-file-viewer-actions { display: flex; align-items: center; gap: 8px; }
.client-file-viewer-actions a, .client-file-viewer-actions button { width: 38px; height: 38px; padding: 0; border: 0; border-radius: 10px; background: #f1f5f9; color: #0f172a; display: grid; place-items: center; text-decoration: none; cursor: pointer; font-size: 20px; }
.client-file-viewer-body { position: relative; min-height: 0; flex: 1; padding: 14px 66px; background: #e9edf3; display: grid; place-items: center; }
.client-file-viewer-stage { position: relative; width: 100%; height: 100%; min-width: 0; min-height: 0; overflow: hidden; border-radius: 12px; background: #fff; display: flex; align-items: center; justify-content: center; }
.client-file-viewer-stage > img { position: absolute !important; inset: 0 !important; width: 100% !important; height: 100% !important; max-width: 100% !important; max-height: 100% !important; margin: auto !important; object-fit: contain !important; object-position: center !important; transform: none !important; display: block !important; }
.client-file-viewer-stage > iframe { width: 100%; height: 100%; border: 0; background: #fff; }
.viewer-arrow { position: absolute; z-index: 2; top: 50%; width: 43px; height: 54px; transform: translateY(-50%); border: 0; border-radius: 12px; background: #fff; color: #0f172a; box-shadow: 0 8px 24px rgba(15,23,42,.2); cursor: pointer; }
.viewer-prev { left: 12px; }
.viewer-next { right: 12px; }
.viewer-file-fallback { padding: 30px; text-align: center; color: #64748b; }
.viewer-file-fallback > i { display: block; margin-bottom: 14px; color: #4f46e5; font-size: 64px; }
.viewer-file-fallback strong { display: block; color: #0f172a; font-size: 15px; overflow-wrap: anywhere; }
.viewer-file-fallback p { margin: 8px 0 18px; font-size: 11px; }
.viewer-file-fallback a { min-height: 40px; padding: 0 16px; border-radius: 9px; background: #111827; color: #fff; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 11px; font-weight: 900; }
.details-no-files { min-height: 90px; margin-top: 12px; border: 1px dashed #cbd5e1; border-radius: 11px; color: #94a3b8; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 7px; font-size: 11px; }
.details-no-files i { font-size: 22px; }
.details-edit-button { width: 100%; min-height: 43px; margin-top: 17px; border: 0; border-radius: 10px; background: #111827; color: #fff; font-weight: 900; cursor: pointer; }

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

    .client-modal-box { width: 100%; max-width: 100%; max-height: none; margin-top: 10px; border-radius: 18px; padding: 18px; }

    .client-form-grid { grid-template-columns: 1fr; gap: 12px; }

    .client-form-column { grid-template-columns: 1fr; padding: 14px; }

    .field-group.full-field { grid-column: auto; }
    .client-details-modal { width: 100%; max-height: none; margin-top: 10px; padding: 18px; border-radius: 18px; }
    .details-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .detail-item.wide { grid-column: 1 / -1; }
    .details-file-grid { grid-template-columns: 1fr; }
    .client-file-viewer-overlay { padding: 8px; }
    .client-file-viewer { width: 100%; height: 94vh; border-radius: 14px; }
    .client-file-viewer-body { padding: 8px 45px; }
    .viewer-arrow { width: 36px; height: 48px; border-radius: 9px; }
    .viewer-prev { left: 5px; }
    .viewer-next { right: 5px; }

}


</style>
