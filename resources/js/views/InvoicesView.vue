<template>
    <AppLayout>
        <div class="page">
            <div class="head">
                <div>
                    <h2>Invoices</h2>
                    <p>Create, edit and manage invoices.</p>
                </div>

                <button @click="openModal()" class="add-btn">
                    <i class="fa-solid fa-plus"></i> Create Invoice
                </button>
            </div>

            <!-- MONTH WISE INVOICE CARDS -->
            <div v-if="monthlyCards.length" class="month-cards">
                <div v-for="m in monthlyCards" :key="m.key" class="month-card">
                    <div class="month-top">
                        <span>{{ m.label }}</span>
                        <i class="fa-solid fa-dollar-sign"></i>
                    </div>

                    <strong>Rs {{ money(m.total) }}</strong>
                    <small>{{ m.count }} invoices</small>
                </div>
            </div>

            <div class="card">
                <div v-if="loading" class="empty">Loading...</div>
                <div v-else-if="invoices.length === 0" class="empty">No invoices yet.</div>

                <div v-else class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Client</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="invoice in invoices" :key="invoice.id">
                                <td data-label="Invoice">
                                    <strong>{{ invoice.invoice_no }}</strong>
                                    <small>{{ invoice.title || 'Invoice' }}</small>
                                </td>
                                <td data-label="Client">{{ invoice.client?.name || '-' }}</td>
                                <td data-label="Total">Rs {{ money(invoice.total) }}</td>
                                <td data-label="Status"><span class="badge">{{ invoice.status }}</span></td>
                                <td data-label="Due Date">{{ cleanDate(invoice.due_date) }}</td>
                                <td data-label="Action">
                                    <div class="actions">
                                        <button class="action-btn edit" @click="editInvoice(invoice)" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button class="action-btn view" @click="openView(invoice)" title="View">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="action-btn delete" @click="deleteInvoice(invoice.id)" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CREATE / EDIT MODAL -->
            <div v-if="modal" class="modal-overlay" @click.self="closeModal">
                <div class="invoice-modal-box large">
                    <button class="close" @click="closeModal">×</button>

                    <h3>{{ editingInvoiceId ? 'Edit Invoice' : 'Create Invoice' }}</h3>

                    <label>Client *</label>
                    <select v-model="form.client_id">
                        <option value="">Select client</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">
                            {{ client.name }} - {{ client.email || 'No Email' }}
                        </option>
                    </select>

                    <label>Title</label>
                    <input v-model="form.title" placeholder="Invoice title" />

                    <div class="items-head">
                        <h4>Items</h4>
                        <button type="button" @click="addItem" class="mini-btn">+ Add Item</button>
                    </div>

                    <div class="item-label-row">
                        <label>Description</label>
                        <label>Quantity</label>
                        <label>Price</label>
                        <label></label>
                    </div>

                    <div v-for="(item, index) in form.items" :key="index" class="item-row">
                        <input v-model="item.description" placeholder="Item Description" />
                        <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" />
                        <input v-model.number="item.price" type="number" min="0" placeholder="Price" />
                        <button type="button" @click="removeItem(index)" class="remove-btn">×</button>
                    </div>

                    <div class="two">
                        <div>
                            <label>Tax</label>
                            <input v-model.number="form.tax" type="number" min="0" />
                        </div>
                        <div>
                            <label>Discount</label>
                            <input v-model.number="form.discount" type="number" min="0" />
                        </div>
                    </div>

                    <label>Due Date</label>
                    <input v-model="form.due_date" type="date" />

                    <label>Notes</label>
                    <textarea v-model="form.notes" placeholder="Notes"></textarea>

                    <div class="payment-options">
                        <h4>Payment & Attachment</h4>

                        <div class="toggle-row">
                            <span>Card Payment</span>
                            <label class="switch">
                                <input type="checkbox" v-model="form.card_payment_active" />
                                <span class="slider">
                                    <span class="switch-text allowed">Allowed</span>
                                    <span class="switch-text unallowed">Unallowed</span>
                                </span>
                            </label>
                        </div>

                        <div class="toggle-row">
                            <span>Bank Account</span>
                            <label class="switch">
                                <input type="checkbox" v-model="form.bank_account_allowed" />
                                <span class="slider">
                                    <span class="switch-text allowed">Allowed</span>
                                    <span class="switch-text unallowed">Unallowed</span>
                                </span>
                            </label>
                        </div>

                        <div v-if="form.bank_account_allowed" class="bank-box">
                            <div class="bank-head">
                                <h4>Allowed Bank Accounts</h4>
                                <button type="button" class="mini-btn" @click="addBank">+ Add Bank</button>
                            </div>

                            <div v-for="(bank, index) in form.bank_accounts" :key="index" class="bank-row">
                                <div class="bank-logo-box">
                                    <img
                                        v-if="bank.logo"
                                        :src="bank.logo"
                                        class="bank-logo-img"
                                        alt="Bank Logo"
                                    />
                                    <span v-else>{{ bankInitial(bank.bank_name) }}</span>
                                </div>

                                <div class="bank-search-wrap">
                                    <input
                                        v-model="bank.bank_name"
                                        placeholder="Type Bank Name"
                                        @focus="bank.showDropdown = true"
                                        @input="bank.showDropdown = true"
                                    />

                                    <div
                                        v-if="bank.showDropdown && filteredBanks(bank.bank_name).length"
                                        class="bank-dropdown"
                                    >
                                        <div
                                            v-for="option in filteredBanks(bank.bank_name)"
                                            :key="option.name"
                                            class="bank-option"
                                            @click="selectBank(index, option)"
                                        >
                                            <img :src="option.logo" class="bank-option-logo" />
                                            <span>{{ option.name }}</span>
                                        </div>
                                    </div>
                                </div>

                                <input v-model="bank.account_title" placeholder="Account Title" />
                                <input v-model="bank.account_number" placeholder="Account Number" />
                                <input v-model="bank.iban" placeholder="IBAN" />
                                <button type="button" class="remove-btn" @click="removeBank(index)">×</button>
                            </div>

                            <p v-if="form.bank_accounts.length === 0" class="bank-empty">
                                No bank added yet.
                            </p>
                        </div>

                        <label>Attachment File / Image</label>
                        <input type="file" @change="onInvoiceAttachmentChange" />

                        <small v-if="editingInvoiceId">
    Leave the file field empty to keep the current attachment.

</small>
                    </div>

                    <div class="total-box">
                        <span>Subtotal: Rs {{ money(subtotal) }}</span>
                        <strong>Total: Rs {{ money(total) }}</strong>
                    </div>

                    <button class="save-btn" @click="saveInvoice" :disabled="saving">
                        {{ saving ? 'Saving...' : (editingInvoiceId ? 'Update Invoice' : 'Create Invoice & Send Email') }}
                    </button>
                </div>
            </div>

            <!-- VIEW MODAL -->
            <div v-if="viewModal" class="modal-overlay" @click.self="closeView">
                <div class="preview-modal">
                    <button class="close" @click="closeView">×</button>

                    <div v-if="selectedInvoice" class="invoice-paper">
                        <div class="invoice-top">
                            <div class="brand-logo">P</div>
                            <div class="invoice-title">
                                <h1>Invoice</h1>
                                <p>{{ selectedInvoice.invoice_no }}</p>
                                <p>{{ cleanDate(selectedInvoice.created_at) }}</p>
                            </div>
                        </div>

                        <div class="bill-row">
                            <div>
                                <strong>Billed to:</strong>
                                <p>{{ selectedInvoice.client?.name || '-' }}</p>
                                <p>{{ selectedInvoice.client?.email || '-' }}</p>
                                <p>{{ selectedInvoice.client?.phone || '-' }}</p>
                                <p>{{ selectedInvoice.client?.address || '-' }}</p>
                            </div>

                            <div class="invoice-meta">
                                <p><strong>Invoice No.</strong> {{ selectedInvoice.invoice_no }}</p>
                                <p><strong>Due Date:</strong> {{ cleanDate(selectedInvoice.due_date) }}</p>
                                <p><strong>Status:</strong> {{ selectedInvoice.status }}</p>
                            </div>
                        </div>

                        <table class="invoice-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="center">Quantity</th>
                                    <th class="right">Unit Price</th>
                                    <th class="right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in selectedInvoice.items" :key="i">
                                    <td>{{ item.description }}</td>
                                    <td class="center">{{ item.quantity }}</td>
                                    <td class="right">Rs {{ money(item.price) }}</td>
                                    <td class="right">Rs {{ money(item.quantity * item.price) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="summary">
                            <p><span>Subtotal</span> <strong>Rs {{ money(selectedInvoice.subtotal) }}</strong></p>
                            <p><span>Tax</span> <strong>Rs {{ money(selectedInvoice.tax) }}</strong></p>
                            <p><span>Discount</span> <strong>Rs {{ money(selectedInvoice.discount) }}</strong></p>
                            <div class="grand-total">
                                <span>Total</span>
                                <strong>Rs {{ money(selectedInvoice.total) }}</strong>
                            </div>
                        </div>

                        <div
                            v-if="selectedInvoice.card_payment_active || selectedInvoice.bank_account_allowed || selectedInvoice.invoice_attachment_url"
                            class="preview-payment-box"
                        >
                            <strong>Payment & Attachment</strong>

                            <p v-if="selectedInvoice.card_payment_active">Card Payment: Allowed</p>
                            <a
                                v-if="selectedInvoice.card_payment_active && selectedInvoice.stripe_payment_url"
                                :href="selectedInvoice.stripe_payment_url"
                                target="_blank"
                                class="pay-card-btn"
                            >
                                Pay by Card
                            </a>

                            <div v-if="selectedInvoice.bank_accounts && selectedInvoice.bank_accounts.length" class="view-bank-list">
                                <strong>Allowed Banks</strong>

                                <div v-for="(bank, index) in selectedInvoice.bank_accounts" :key="index" class="view-bank-card">
                                    <div class="view-bank-head">
                                        <img
                                            v-if="bank.logo"
                                            :src="bank.logo"
                                            class="view-bank-logo"
                                            alt="Bank Logo"
                                        />
                                        <span v-else class="view-bank-fallback">{{ bankInitial(bank.bank_name) }}</span>
                                        <strong>{{ bank.bank_name || 'Bank' }}</strong>
                                    </div>
                                    <p>Title: {{ bank.account_title || '-' }}</p>
                                    <p>Account: {{ bank.account_number || '-' }}</p>
                                    <p>IBAN: {{ bank.iban || '-' }}</p>
                                </div>
                            </div>

                            <a
                                v-if="selectedInvoice.invoice_attachment_url"
                                :href="selectedInvoice.invoice_attachment_url"
                                target="_blank"
                                class="attachment-btn"
                            >
                                View Attachment
                            </a>
                        </div>

                        <div class="thanks">Thank You!</div>

                        <div class="payment-info">
                            <div>
                                <strong>Payment Information</strong>
                                <p v-if="selectedInvoice.card_payment_active">Card payment is allowed.</p>
                                <p v-if="selectedInvoice.bank_account_allowed">Bank account payment is allowed.</p>
                                <p v-if="!selectedInvoice.card_payment_active && !selectedInvoice.bank_account_allowed">
                                    Payment details will be added here.
                                </p>
                            </div>

                            <div class="signature">
                                <strong>Prosixflow</strong>
                                <p>Work Management</p>
                            </div>
                        </div>

                        <p v-if="selectedInvoice.notes" class="notes">
                            <strong>Notes:</strong> {{ selectedInvoice.notes }}
                        </p>
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
    name: 'InvoicesView',
    components: { AppLayout },

    data() {
        return {
            loading: false,
            saving: false,
            modal: false,
            viewModal: false,
            selectedInvoice: null,
            editingInvoiceId: null,
            invoices: [],
            clients: [],

            banksList: [
                { name: 'Meezan Bank', domain: 'meezanbank.com' },
                { name: 'HBL', domain: 'hbl.com' },
                { name: 'UBL', domain: 'ubldigital.com' },
                { name: 'MCB Bank', domain: 'mcb.com.pk' },
                { name: 'Bank Alfalah', domain: 'bankalfalah.com' },
                { name: 'Allied Bank', domain: 'abl.com' },
                { name: 'Askari Bank', domain: 'askaribank.com' },
                { name: 'Faysal Bank', domain: 'faysalbank.com' },
                { name: 'Bank Al Habib', domain: 'bankalhabib.com' },
                { name: 'JS Bank', domain: 'jsbl.com' },
                { name: 'Dubai Islamic Bank', domain: 'dibpak.com' },
                { name: 'Standard Chartered', domain: 'sc.com' },
                { name: 'Soneri Bank', domain: 'soneribank.com' },
                { name: 'Sindh Bank', domain: 'sindhbank.com.pk' },
                { name: 'Summit Bank', domain: 'summitbank.com.pk' },
                { name: 'NRSP Bank', domain: 'nrspbank.com' },
                { name: 'Easypaisa', domain: 'easypaisa.com.pk' },
                { name: 'JazzCash', domain: 'jazzcash.com.pk' },
                { name: 'NayaPay', domain: 'nayapay.com' },
                { name: 'SadaPay', domain: 'sadapay.pk' },
            ],

            form: this.emptyFormData(),
        }
    },

    computed: {
        subtotal() {
            return this.form.items.reduce((sum, item) => {
                return sum + Number(item.quantity || 0) * Number(item.price || 0)
            }, 0)
        },

        total() {
            return this.subtotal + Number(this.form.tax || 0) - Number(this.form.discount || 0)
        },

        monthlyCards() {
            const groups = {}

            this.invoices.forEach(inv => {
                const d = new Date(inv.created_at || inv.due_date)
                if (isNaN(d)) return

                const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`

                if (!groups[key]) {
                    groups[key] = {
                        key,
                        label: d.toLocaleString('en-US', {
                            month: 'short',
                            year: 'numeric',
                        }),
                        total: 0,
                        count: 0,
                    }
                }

                groups[key].total += Number(inv.total || 0)
                groups[key].count += 1
            })

            return Object.values(groups).sort((a, b) => b.key.localeCompare(a.key))
        },
    },

    mounted() {
        this.fetchInvoices()
        this.fetchClients()
    },

    methods: {
        emptyFormData() {
            return {
                client_id: '',
                title: '',
                items: [{ description: '', quantity: 1, price: 0 }],
                tax: 0,
                discount: 0,
                due_date: '',
                notes: '',
                card_payment_active: false,
                bank_account_allowed: false,
                bank_accounts: [],
                invoice_attachment: null,
            }
        },

        headers() {
            return {
                Authorization: `Bearer ${localStorage.getItem('token')}`,
                Accept: 'application/json',
            }
        },

        money(value) {
            return Number(value || 0).toLocaleString()
        },

        cleanDate(date) {
            if (!date) return '-'
            const d = new Date(date)
            if (isNaN(d)) return date
            return d.toLocaleDateString()
        },

        bankLogo(domain) {
            return `https://www.google.com/s2/favicons?domain=${domain}&sz=128`
        },

        bankInitial(bankName) {
            return String(bankName || 'B').charAt(0).toUpperCase()
        },

        filteredBanks(search) {
            const q = String(search || '').toLowerCase()

            return this.banksList
                .filter(bank => bank.name.toLowerCase().includes(q))
                .map(bank => ({
                    ...bank,
                    logo: this.bankLogo(bank.domain),
                }))
        },

        selectBank(index, bank) {
            this.form.bank_accounts[index].bank_name = bank.name
            this.form.bank_accounts[index].logo = bank.logo
            this.form.bank_accounts[index].domain = bank.domain
            this.form.bank_accounts[index].showDropdown = false
        },

        onInvoiceAttachmentChange(e) {
            this.form.invoice_attachment = e.target.files[0] || null
        },

        addBank() {
            this.form.bank_accounts.push({
                bank_name: '',
                account_title: '',
                account_number: '',
                iban: '',
                logo: '',
                domain: '',
                showDropdown: false,
            })
        },

        removeBank(index) {
            this.form.bank_accounts.splice(index, 1)
        },

        makeFormData() {
            const fd = new FormData()

            fd.append('client_id', this.form.client_id)
            fd.append('title', this.form.title || '')
            fd.append('items', JSON.stringify(this.form.items))
            fd.append('tax', this.form.tax || 0)
            fd.append('discount', this.form.discount || 0)
            fd.append('due_date', this.form.due_date || '')
            fd.append('notes', this.form.notes || '')
            fd.append('card_payment_active', this.form.card_payment_active ? 1 : 0)
            fd.append('bank_account_allowed', this.form.bank_account_allowed ? 1 : 0)
            fd.append('bank_accounts', JSON.stringify(this.form.bank_accounts || []))

            if (this.form.invoice_attachment) {
                fd.append('invoice_attachment', this.form.invoice_attachment)
            }

            return fd
        },

        async fetchInvoices() {
            this.loading = true
            try {
                const res = await axios.get('/api/invoices', { headers: this.headers() })
                this.invoices = Array.isArray(res.data) ? res.data : []
            } catch (e) {
                alert(e.response?.data?.message || 'Invoices load failed')
            } finally {
                this.loading = false
            }
        },

        async fetchClients() {
            try {
                const res = await axios.get('/api/clients', { headers: this.headers() })
                this.clients = Array.isArray(res.data) ? res.data : []
            } catch (e) {
                console.error(e)
            }
        },

        openModal() {
            this.editingInvoiceId = null
            this.form = this.emptyFormData()
            this.modal = true
        },

        editInvoice(invoice) {
            this.editingInvoiceId = invoice.id

            const banks = invoice.bank_accounts ? JSON.parse(JSON.stringify(invoice.bank_accounts)) : []

            this.form = {
                client_id: invoice.client_id,
                title: invoice.title || '',
                items: JSON.parse(JSON.stringify(invoice.items || [])),
                tax: Number(invoice.tax || 0),
                discount: Number(invoice.discount || 0),
                due_date: invoice.due_date ? String(invoice.due_date).substring(0, 10) : '',
                notes: invoice.notes || '',
                card_payment_active: !!invoice.card_payment_active,
                bank_account_allowed: !!invoice.bank_account_allowed,
                bank_accounts: banks.map(bank => ({
                    bank_name: bank.bank_name || '',
                    account_title: bank.account_title || '',
                    account_number: bank.account_number || '',
                    iban: bank.iban || '',
                    logo: bank.logo || '',
                    domain: bank.domain || '',
                    showDropdown: false,
                })),
                invoice_attachment: null,
            }

            if (!this.form.items.length) {
                this.form.items = [{ description: '', quantity: 1, price: 0 }]
            }

            this.modal = true
        },

        closeModal() {
            this.editingInvoiceId = null
            this.modal = false
        },

        openView(invoice) {
            this.selectedInvoice = invoice
            this.viewModal = true
        },

        closeView() {
            this.selectedInvoice = null
            this.viewModal = false
        },

        addItem() {
            this.form.items.push({ description: '', quantity: 1, price: 0 })
        },

        removeItem(index) {
            if (this.form.items.length === 1) return
            this.form.items.splice(index, 1)
        },

        async saveInvoice() {
            if (!this.form.client_id) {
alert('Please select a client')
                return
            }

            const hasEmpty = this.form.items.some(item => !item.description || !item.quantity)
            if (hasEmpty) {
alert('Item description and quantity are required')
                return
            }

            this.saving = true

            try {
                const fd = this.makeFormData()

                if (this.editingInvoiceId) {
                    await axios.post(`/api/invoices/${this.editingInvoiceId}?_method=PUT`, fd, {
                        headers: {
                            ...this.headers(),
                            'Content-Type': 'multipart/form-data',
                        },
                    })
                } else {
                    await axios.post('/api/invoices', fd, {
                        headers: {
                            ...this.headers(),
                            'Content-Type': 'multipart/form-data',
                        },
                    })
                }
                                              
                this.closeModal()
                this.fetchInvoices()
            } catch (e) {
                alert(e.response?.data?.message || 'Invoice save failed')
            } finally {
                this.saving = false
            }
        },

        async deleteInvoice(id) {
            if (!confirm('Delete this invoice?')) return

            try {
                await axios.delete(`/api/invoices/${id}`, { headers: this.headers() })
                this.fetchInvoices()
            } catch (e) {
                alert(e.response?.data?.message || 'Invoice delete failed')
            }
        },
    },
}
</script>

<style scoped>
.page { padding:24px; background:#f4f5f8; min-height:100vh; }
.head { display:flex; justify-content:space-between; gap:15px; align-items:center; margin-bottom:18px; }
.head h2 { margin:0; font-size:28px; font-weight:900; color:#111; }
.head p { margin:4px 0 0; color:#6b7280; }
.add-btn,.save-btn { border:none; background:#111; color:#fff; border-radius:12px; padding:12px 18px; font-weight:900; cursor:pointer; }
.card { background:#fff; border:1px solid #e5e7eb; border-radius:18px; overflow:hidden; }
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; min-width:760px; }
th,td { padding:14px 16px; text-align:left; border-bottom:1px solid #f1f1f1; font-size:14px; }
td small { display:block; color:#9ca3af; margin-top:3px; }
th { background:#fafafa; color:#6b7280; font-size:12px; text-transform:uppercase; }
.badge { background:#111; color:#fff; border-radius:999px; padding:5px 10px; font-size:11px; font-weight:800; }
.empty { padding:40px; text-align:center; color:#6b7280; }
.actions { display:flex; justify-content:flex-end; gap:8px; }

/* MONTH WISE CARDS */
.month-cards {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    padding-bottom: 14px;
    margin-bottom: 16px;
    scrollbar-width: thin;
}

.month-card {
    min-width: 170px;
    background: #fff;
    border: 1.5px solid #2563eb;
    border-radius: 14px;
    padding: 14px;
    box-shadow: 0 12px 28px rgba(0,0,0,.08);
    flex-shrink: 0;
}

.month-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #4b5563;
    font-size: 14px;
}

.month-top i {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2563eb;
}

.month-card strong {
    display: block;
    margin-top: 10px;
    font-size: 24px;
    color: #111;
}

.month-card small {
    display: block;
    margin-top: 4px;
    color: #9ca3af;
}

.text-end { text-align:right; }
.action-btn { width:34px; height:34px; border:none; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-size:13px; }
.action-btn.edit { background:#dcfce7; color:#15803d; }
.action-btn.view { background:#e0f2fe; color:#0369a1; }
.action-btn.delete { background:#fee2e2; color:#b91c1c; }
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.55); display:flex; align-items:center; justify-content:center; z-index:99999; padding:15px; }
.invoice-modal-box { background:#fff; width:460px; max-width:100%; border-radius:20px; padding:24px; position:relative; max-height:92vh; overflow-y:auto; z-index:100000; }
.invoice-modal-box.large { width:900px; }
.preview-modal { background:#fff; width:720px; max-width:96%; max-height:94vh; overflow-y:auto; border-radius:4px; padding:0; position:relative; }
.close { position:absolute; right:15px; top:12px; border:none; background:#f3f4f6; width:34px; height:34px; border-radius:10px; font-size:22px; cursor:pointer; z-index:5; }
label { display:block; margin:12px 0 6px; font-size:13px; font-weight:800; }
input,textarea,select { width:100%; border:1.5px solid #d1d5db; border-radius:10px; padding:11px 12px; outline:none; }
textarea { min-height:80px; resize:vertical; }
.items-head { display:flex; justify-content:space-between; align-items:center; margin-top:18px; }
.items-head h4 { margin:0; }
.mini-btn { border:none; background:#111; color:#fff; border-radius:9px; padding:8px 12px; font-weight:800; cursor:pointer; }
.item-label-row,.item-row { display:grid; grid-template-columns:1fr 90px 120px 40px; gap:10px; }
.item-label-row { margin-top:12px; }
.item-label-row label { margin:0; font-size:12px; font-weight:900; color:#111; }
.item-row { margin-top:8px; }
.item-row input:nth-child(2) { text-align:center; }
.item-row input:nth-child(3) { text-align:right; }
.remove-btn { border:none; background:#fee2e2; color:#b91c1c; border-radius:9px; font-size:22px; cursor:pointer; }
.two { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.payment-options { background:#f8fafc; border:1px solid #e5e7eb; border-radius:14px; padding:14px; margin-top:14px; }
.payment-options h4 { margin:0 0 12px; font-size:15px; font-weight:900; }
.toggle-row { display:flex; justify-content:space-between; align-items:center; gap:15px; margin:12px 0; font-size:13px; font-weight:900; }
.switch { position:relative; width:135px; height:40px; margin:0; flex-shrink:0; }
.switch input { display:none; }
.slider { position:absolute; inset:0; cursor:pointer; background:#d1d5db; border-radius:999px; transition:.25s ease; }
.slider::before { content:""; position:absolute; width:34px; height:34px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.25s ease; box-shadow:0 2px 8px rgba(0,0,0,.25); z-index:2; }
.switch input:checked + .slider { background:#22c55e; }
.switch input:checked + .slider::before { transform:translateX(95px); }
.switch-text { position:absolute; top:50%; transform:translateY(-50%); font-size:11px; font-weight:900; color:#fff; z-index:1; text-transform:uppercase; }
.switch-text.allowed { left:12px; display:none; }
.switch-text.unallowed { right:10px; color:#374151; display:block; }
.switch input:checked + .slider .allowed { display:block; }
.switch input:checked + .slider .unallowed { display:none; }
.bank-box { margin-top:14px; padding:14px; background:#fff; border:1px dashed #d1d5db; border-radius:14px; }
.bank-head { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; }
.bank-head h4 { margin:0; font-size:14px; font-weight:900; }
.bank-row { display:grid; grid-template-columns:48px 1.2fr 1fr 1fr 1fr 38px; gap:8px; margin-top:10px; align-items:center; }
.bank-logo-box { width:42px; height:42px; border-radius:12px; background:#f3f4f6; border:1px solid #e5e7eb; display:flex; align-items:center; justify-content:center; font-weight:900; overflow:hidden; }
.bank-logo-img { width:100%; height:100%; object-fit:contain; padding:5px; }
.bank-search-wrap { position:relative; }
.bank-dropdown { position:absolute; top:46px; left:0; right:0; background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 12px 30px rgba(0,0,0,.12); z-index:999999; max-height:220px; overflow-y:auto; }
.bank-option { display:flex; align-items:center; gap:10px; padding:10px 12px; cursor:pointer; font-size:13px; font-weight:800; }
.bank-option:hover { background:#f3f4f6; }
.bank-option-logo { width:26px; height:26px; object-fit:contain; }
.bank-empty { color:#6b7280; font-size:13px; margin:8px 0 0; }
.total-box { background:#f8fafc; border:1px solid #e5e7eb; border-radius:14px; padding:14px; margin-top:14px; display:flex; justify-content:space-between; gap:10px; }
.total-box strong { font-size:18px; }
.save-btn { width:100%; margin-top:18px; }
.invoice-paper { background:#fff; color:#111; padding:54px; font-family:Arial,sans-serif; }
.invoice-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:54px; }
.brand-logo { width:52px; height:52px; background:#111; color:#fff; border-radius:15px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:24px; }
.invoice-title { text-align:right; }
.invoice-title h1 { margin:0 0 22px; font-size:44px; font-family:Georgia,serif; font-weight:500; }
.invoice-title p { margin:3px 0; color:#555; font-size:13px; }
.bill-row { display:flex; justify-content:space-between; gap:30px; margin-bottom:34px; }
.bill-row p { margin:4px 0; font-size:13px; color:#333; }
.invoice-meta { text-align:right; min-width:230px; }
.invoice-table { min-width:100%; width:100%; border-collapse:collapse; margin-top:8px; }
.invoice-table th { background:#fff; color:#111; border-bottom:2px solid #111; padding:12px 0; font-size:13px; }
.invoice-table td { border-bottom:1px solid #999; padding:14px 0; font-size:13px; }
.center { text-align:center; }
.right { text-align:right; }
.summary { width:270px; margin-left:auto; margin-top:22px; }
.summary p { display:flex; justify-content:space-between; margin:9px 0; font-size:13px; }
.grand-total { margin-top:10px; background:#111; color:#fff; padding:14px 18px; display:flex; justify-content:space-between; font-size:20px; font-weight:900; }
.preview-payment-box { margin-top:25px; background:#f8fafc; border:1px solid #e5e7eb; padding:14px; border-radius:12px; font-size:13px; }
.view-bank-list { margin-top:14px; }
.view-bank-card { margin-top:10px; padding:10px; border-radius:10px; background:#fff; border:1px solid #e5e7eb; }
.view-bank-card p { margin:4px 0; }
.view-bank-head { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.view-bank-logo,.view-bank-fallback { width:34px; height:34px; border-radius:10px; background:#f3f4f6; border:1px solid #e5e7eb; object-fit:contain; padding:5px; display:flex; align-items:center; justify-content:center; font-weight:900; }
.pay-card-btn,.attachment-btn { display:inline-block; margin-top:8px; margin-right:8px; padding:11px 16px; border-radius:10px; text-decoration:none; font-weight:900; }
.pay-card-btn { background:#111; color:#fff !important; }
.attachment-btn { background:#e0f2fe; color:#0369a1 !important; }
.thanks { margin-top:62px; font-family:cursive; font-size:42px; color:#222; transform:rotate(-6deg); }
.payment-info { margin-top:28px; display:flex; justify-content:space-between; gap:20px; }
.payment-info p { margin:4px 0; font-size:12px; color:#333; }
.signature { text-align:right; font-family:Georgia,serif; font-size:18px; }
.notes { margin-top:20px; font-size:13px; }

@media (max-width:900px) {
    .bank-row { grid-template-columns:48px 1fr 1fr; }
    .bank-row .remove-btn { grid-column:1 / -1; width:100%; }
}

@media (max-width:700px) {
    .bank-row { grid-template-columns:1fr; }
    .bank-logo-box { width:48px; height:48px; }
    .bank-head { flex-direction:column; align-items:stretch; }
}

@media (max-width:600px) {
    .page { padding:16px; }
    .head { flex-direction:column; align-items:stretch; }
    .add-btn { width:100%; }
    .item-label-row { display:none; }
    .item-row { grid-template-columns:1fr; gap:8px; }
    .item-row input:nth-child(2), .item-row input:nth-child(3) { text-align:left; }
    .two { grid-template-columns:1fr; }
    .total-box { flex-direction:column; }
    .toggle-row { align-items:flex-start; flex-direction:column; }
    .invoice-paper { padding:28px; }
    .invoice-top,.bill-row,.payment-info { flex-direction:column; }
    .invoice-title,.invoice-meta,.signature { text-align:left; }
    .summary { width:100%; }
    .invoice-title h1 { font-size:36px; }
    .month-card { min-width:145px; }
    .month-card strong { font-size:20px; }
}

/* MOBILE RESPONSIVE TABLE CARDS */
@media (max-width: 768px) {
    .page { padding: 14px; }
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
    td[data-label="Invoice"] { display: block; }
    td[data-label="Invoice"]::before { display: block; margin-bottom: 6px; }
    td[data-label="Action"] { justify-content: flex-end; padding-top: 14px; border-top: 1px solid #f1f1f1; margin-top: 6px; }
    td[data-label="Action"]::before { display: none; }
    .actions { width: 100%; justify-content: flex-end; }
    .action-btn { width: 40px; height: 40px; }
    .modal-overlay { align-items: flex-start; overflow-y: auto; padding: 12px; }
    .invoice-modal-box, .invoice-modal-box.large, .preview-modal { width: 100%; max-width: 100%; margin-top: 10px; border-radius: 18px; }
}

</style>
