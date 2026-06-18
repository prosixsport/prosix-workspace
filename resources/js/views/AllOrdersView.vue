
<template>
  <div class="orders-layout" :class="{ 'left-open': mobileLeftOpen, 'chat-open': showChat }" @click="closeAllMenus">
    <!-- MOBILE TOP BAR -->
    <div class="mobile-topbar">
      <button class="mobile-menu-btn" @click.stop="mobileLeftOpen = !mobileLeftOpen">
        <i class="fa-solid fa-bars"></i>
      </button>
      <span class="mobile-order-name">{{ selectedOrder ? selectedOrder.name : 'Orders' }}</span>
      <button v-if="selectedOrder" class="mobile-chat-btn" @click.stop="toggleChat" :class="{ active: showChat }">
        <i class="fa-solid fa-comments"></i>
        <span v-if="unreadChatCount > 0" class="chat-badge">{{ unreadChatCount }}</span>
      </button>
    </div>

    <!-- OVERLAY for mobile left panel -->
    <div class="mobile-overlay" v-if="mobileLeftOpen" @click="mobileLeftOpen = false"></div>
<div class="resize-bar"
     :style="{ left: leftWidth + 'px' }"
     @mousedown.stop="startResize">
  <div class="resize-handle">
    <i class="fa-solid fa-angles-right"></i>
  </div>
</div>

    <!-- LEFT PANEL -->
    <div class="orders-left" :style="desktopLeftStyle">

      <div class="orders-left-header">
        <button class="back-btn" type="button" title="Back to dashboard" @click.stop="$router.push('/dashboard')">
          <i class="fa-solid fa-arrow-left"></i>
        </button>
        <span class="orders-title">All Orders</span>
        <span v-if="unreadOrdersCount > 0" class="order-notify-pill">{{ unreadOrdersCount }} new</span>
      </div>

      <div class="orders-tabs">
        <button
          v-for="group in groups"
          :key="group.key"
          class="orders-tab"
          :class="{ active: activeGroup === group.key }"
          @click="activeGroup = group.key"
        >{{ group.label }}</button>
      </div>

      <div class="list-head">
<div class="col-chk">
  <input
    type="checkbox"
    v-model="selectAll"
    @change="toggleSelectAll"
    @click.stop
  />
</div>
        <div class="col-task">Task</div>
        <div class="col-owner">OWNER</div>
        <div class="col-actions"></div>
      </div>
<div v-if="isSuperAdmin && selectedOrders.length > 1" class="bulk-actions" @click.stop>
      <strong>{{ selectedOrders.length }}</strong>

  <button class="bulk-btn" @click="openBulkMembersModal" :disabled="bulkActionLoading">
    <i class="fa-solid fa-users"></i> Edit Members
  </button>

  <button class="bulk-btn" @click="bulkDuplicateOrders" :disabled="bulkActionLoading">
    <i class="fa-solid fa-copy"></i> Duplicate
  </button>

  <button class="bulk-btn danger" @click="bulkDeleteOrders" :disabled="bulkActionLoading">
    <i class="fa-solid fa-trash"></i> Delete
  </button>
</div>
<div class="order-search-box">
  <i class="fa-solid fa-magnifying-glass"></i>
  <input
    v-model="searchOrder"
    type="text"
    placeholder="Search order..."
    @click.stop
  />
</div>

<div v-if="canCreateOrder" class="add-row" @click="addNewOrder">
            <i class="fa-solid fa-plus me-1"></i> Add New Order
      </div>


      <div v-if="loadingOrders" class="orders-loading">Loading orders...</div>
      <div v-else-if="filteredOrders.length === 0" class="orders-empty-list">No orders found</div>

      <div
        v-for="order in filteredOrders"
        :key="order.id"
        class="list-row"
        :class="{
          active: selectedOrder?.id === order.id,
          unread: !order.user_has_seen,
          seen: order.user_has_seen
        }"
        @click="selectOrderAndClose(order)"
      >
<div class="col-chk">
  <input
    type="checkbox"
    :value="order.id"
    v-model="selectedOrders"
    @click.stop
  />
</div>
        <div class="col-task">
          <span v-if="!order.user_has_seen" class="unread-dot"></span>
          <i v-if="order.hasChildren" class="fa-solid fa-chevron-right row-arrow"></i>
          <span>{{ order.name }}</span>
        </div>
        <div class="col-owner">
          <div class="avatar-stack">
            <div
v-for="(av, i) in order.owners.slice(0, 4)"
              :key="i"
              class="av"
              :class="{ 'has-photo': av.profile_photo_url }"
              :style="{ background: av.profile_photo_url ? '#fff' : av.color }"
              :title="av.name"
              @click.stop="openProfile(av)"
            >
              <img v-if="av.profile_photo_url" :src="av.profile_photo_url" class="avatar-img" />
              <span v-else>{{ av.initial }}</span>
            </div>
<div v-if="order.owners.length > 4" class="av av-count">+{{ order.owners.length - 4 }}</div>
          </div>
        </div>
        <div class="order-actions-wrap" @click.stop>
          <button class="order-dots-btn" @click="toggleOrderMenu(order.id)">
            <i class="fa-solid fa-ellipsis"></i>
          </button>
          <div v-if="openOrderMenuId === order.id" class="order-menu">
            <div class="order-menu-item" @click="openOrderInfo(order)">
              <i class="fa-solid fa-circle-info"></i> Info
            </div>
            <template v-if="isSuperAdmin">
              <div class="order-menu-item" @click="openEditOrder(order)"><i class="fa-solid fa-pen"></i> Edit</div>
              <div class="order-menu-item" @click="duplicateOrder(order)"><i class="fa-solid fa-copy"></i> Duplicate</div>
              <div class="order-menu-item danger" @click="deleteOrder(order)"><i class="fa-solid fa-trash"></i> Delete</div>
            </template>
          </div>
        </div>
      </div>


    </div>

    <!-- RIGHT PANEL -->
    <div class="orders-right" v-if="selectedOrder">

      <!-- HEADER -->
      <div class="detail-header">
        <div class="header-left-p">
          <img src="/public/assets/images/P LOGO WHITE.png" alt="Prosix P" class="left-p-logo" />
        </div>
        <div class="header-center-logo">
          <img src="/public/assets/images/PROSIX SPORTS LOGO PNG WHITE.png" alt="Prosix" class="prosix-main-logo" />
        </div>
        <div class="header-right-icons">
          <button class="header-icon-btn" @click.stop="toggleChat" :class="{ active: showChat }">
            <i class="fa-solid fa-comments"></i>
            <span v-if="unreadChatCount > 0" class="chat-badge">{{ unreadChatCount }}</span>
          </button>
          <button class="user-avatar-top" type="button" title="Open profile" @click.stop="openProfile(currentUser)">
            <img v-if="userPhoto" :src="userPhoto" class="avatar-img" />
            <span v-else>{{ userInitial }}</span>
          </button>
        </div>





      </div>

 <!-- INFO BAR -->
<div class="detail-topbar-wrapper">
      <div class="detail-topbar table-border" style="display:flex; flex-wrap:nowrap; width:max-content; min-width:100%;">
          <div class="detail-info-item">
            <span class="info-label">P.o #</span>
            <span class="info-value">{{ selectedOrder.po }}</span>
          </div>
          <div class="detail-info-item" style="position:relative" @click.stop>
            <span class="info-label">Ship Date</span>
            <span class="info-value date-clickable" @click="showDatePicker = !showDatePicker">
              {{ selectedOrder.shipDate }}
              <i class="fa-solid fa-calendar-days" style="font-size:11px;margin-left:4px;color:black"></i>
            </span>
         <div v-if="showDatePicker" class="date-dropdown">
  <div class="date-dropdown-header">Select Ship Date</div>

  <input
    type="date"
    class="date-input"
    :value="selectedOrder.shipDateRaw"
    @change="updateShipDate($event)"
  />

  <button class="date-clear-btn" @click="showDatePicker = false">
    Close
  </button>
</div>
          </div>
          <div class="detail-info-item" style="position:relative" @click.stop>
            <span class="info-label">Status :</span>
            <span class="status-badge" :style="{ background: selectedOrder.statusColor }" @click="showStatusMenu = !showStatusMenu">
              {{ selectedOrder.status }}
              <i class="fa-solid fa-chevron-down" style="font-size:9px;margin-left:4px"></i>
            </span>
            <div v-if="showStatusMenu" class="status-dropdown">
              <div v-for="s in statusOptions" :key="s.label" class="status-drop-item" @click="changeStatus(s)">
                <input type="color" class="status-dot status-color-picker" :value="s.color" title="Click to change label color" @click.stop @input.stop="changeStatusOptionColor(s, $event.target.value)" />
                {{ s.label }}
                <span class="status-group-tag">→ {{ s.groupLabel }}</span>
              </div>
              <div class="custom-status-box">
                <input v-model="customStatusLabel" class="custom-status-input" placeholder="Write custom status..." @keydown.enter.prevent="applyCustomStatus" />
                <input v-model="customStatusColor" type="color" class="custom-status-color" title="Choose label color" />
                <button class="custom-status-btn" @click="applyCustomStatus">Add</button>
              </div>
            </div>
          </div>
          <div v-if="isSuperAdmin" class="detail-info-item invoice-info-item" style="position:relative" @click.stop>
            <button class="invoice-btn" @click="triggerInvoiceUpload">
              <i class="fa-solid fa-file-invoice me-1"></i>Add Invoice
            </button>
            <button v-if="selectedOrder.invoiceFiles && selectedOrder.invoiceFiles.length" class="invoice-view-btn" @click="openInvoiceFiles">
              <i class="fa-solid fa-eye me-1"></i>View ({{ selectedOrder.invoiceFiles.length }})
            </button>
            <input ref="invoiceInput" type="file" accept="image/*,.pdf" class="hidden-file-input" @change="onInvoiceFileChange" />
          </div>
          <div class="detail-info-item tracking-info-item" style="position:relative" @click.stop>
            <span class="info-label">trk# :</span>
            <span class="trk-badge trk-clickable" @click="openTrackingMenu">
              <img v-if="trackingLogo(selectedOrder.trk)" :src="trackingLogo(selectedOrder.trk)" class="trk-logo" />
              {{ selectedOrder.trk || 'N/A' }}
              <i class="fa-solid fa-pen" style="font-size:9px;margin-left:6px"></i>
            </span>
            <div v-if="showTrackingMenu" class="tracking-dropdown">
              <div class="tracking-dropdown-header">Tracking Details</div>
              <div class="payment-field">
                <label class="payment-label">Tracking Number</label>
                <input v-model="trackingEdit.number" class="payment-input" placeholder="e.g. 123456789" />
              </div>
              <div class="payment-field">
                <label class="payment-label">Company Website</label>
                <input v-model="trackingEdit.company" class="payment-input" placeholder="e.g. www.dhl.com or www.ups.com" />
              </div>
              <div v-if="trackingLogo(trackingEdit.company)" class="tracking-preview-row">
                <img :src="trackingLogo(trackingEdit.company)" class="tracking-preview-logo" />
                <span>{{ trackingCompanyName(trackingEdit.company) }}</span>
              </div>
              <button class="payment-save-btn" @click="saveTracking">
                <i class="fa-solid fa-floppy-disk me-1"></i>Save Tracking
              </button>
            </div>
          </div>
          <div class="detail-info-item" style="position:relative" @click.stop>
            <span class="info-label">Payment :</span>
            <span class="payment-badge payment-summary-badge" @click="showPaymentMenu = !showPaymentMenu">
              <span class="payment-chip payment-chip-paid">{{ selectedOrder.payment || '0 % Paid' }}</span>
              <span class="payment-chip payment-chip-received">R ${{ selectedOrder.paymentReceived || 0 }}</span>
              <span class="payment-chip payment-chip-balance">B ${{ selectedOrder.paymentBalance || 0 }}</span>
              <i class="fa-solid fa-chevron-down" style="font-size:9px;margin-left:4px"></i>
            </span>
            <div v-if="showPaymentMenu" class="payment-dropdown payment-dropdown-wide">
              <div class="payment-dropdown-header">Payment Details</div>
              <div class="payment-read-row paid-row">
                <span>Paid</span><strong>{{ selectedOrder.payment || '0 % Paid' }}</strong>
              </div>
              <div class="payment-read-row received-row">
                <span>Received</span><strong>${{ selectedOrder.paymentReceived || 0 }}</strong>
              </div>
              <div class="payment-read-row balance-row">
                <span>Balance</span><strong>${{ selectedOrder.paymentBalance || 0 }}</strong>
              </div>
              <div v-if="isSuperAdmin" class="payment-admin-editor">
                <div class="payment-field">
                  <label class="payment-label">Paid %</label>
                  <div class="payment-percent-row">
                    <input v-model="paymentEdit.percent" type="number" min="0" max="100" class="payment-input" placeholder="e.g. 40" />
                    <span class="percent-sign">%</span>
                  </div>
                </div>
                <div class="payment-field">
                  <label class="payment-label"><i class="fa-solid fa-circle-check" style="color:#00c875;margin-right:4px"></i>Received ($)</label>
                  <input v-model="paymentEdit.received" type="number" min="0" class="payment-input" placeholder="e.g. 1500" />
                </div>
                <div class="payment-field">
                  <label class="payment-label"><i class="fa-solid fa-clock" style="color:#fdab3d;margin-right:4px"></i>Balance ($)</label>
                  <input v-model="paymentEdit.balance" type="number" min="0" class="payment-input" placeholder="e.g. 2500" />
                </div>
                <button class="payment-save-btn" @click="savePayment">
                  <i class="fa-solid fa-floppy-disk me-1"></i>Save Payment
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-if="selectedOrder" class="current-order-title">
  <i class="fa-solid fa-folder-open"></i>
  <span>{{ selectedOrder.name }}</span>
</div>
      </div>

      <!-- BODY -->
      <div class="detail-body">
        <!-- CARDS -->
        <div class="cards-area">
          <div class="cards-grid">
            <div v-for="card in selectedOrder.cards" :key="card.title" class="order-card">

              <div v-if="card.title === 'Notes'" class="card-notes-area">
                <div class="notes-header">
                  <i class="fa-solid fa-pen-to-square notes-icon"></i>
                  <span class="text-dark">Notes</span>
                </div>
              <textarea
  v-model="card.noteText"
  class="notes-textarea text-dark"
  placeholder="Type your notes here..."
  :readonly="!canEditNotes"
></textarea>

<div class="notes-footer">
  <span class="notes-count text-dark">{{ card.noteText ? card.noteText.length : 0 }} chars</span>
  <span v-if="card.saved" class="notes-saved-msg">✅ Saved!</span>

  <button v-if="canEditNotes" class="notes-save-btn" @click="saveNote(card)">
    <i class="fa-solid fa-floppy-disk me-1"></i>Save
  </button>
</div>
              </div>

              <template v-else>
                <div class="card-preview-area" @dragover.prevent @drop="onDrop($event, card)">
                  <div v-if="card.files && card.files.length" class="card-files-preview">
                    <div v-for="(file, fi) in card.files" :key="fi" class="file-thumb">
                      <img v-if="file.isImage && !file.imageError" :src="file.url" class="file-img" @click.stop="openPreviewFile(file)" @error="file.imageError = true" />
                   <div v-else class="file-icon-box" @click.stop="openPreviewFile(file)" style="cursor:pointer;">
  <i :class="getFileIcon(file.name)" class="file-type-icon"></i>
  <span class="file-name-small">{{ file.name }}</span>
</div>
                      <span v-if="file.uploading" class="uploading-label">Uploading...</span>
                <button v-if="canDeleteFile(file) && !file.uploading" class="file-remove-btn" @click.stop="removeFile(card, fi)">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>
                  <div v-else class="card-empty-preview">
                    <img v-if="card.thumbnail" :src="card.thumbnail" class="card-thumbnail-img" />
                    <i v-else :class="card.icon" class="card-bg-icon"></i>
                  </div>
                </div>
                <div class="card-footer-inner">
                  <div class="card-footer-left">
<button v-if="canUploadFiles && card.type !== 'notes'" class="card-add-btn" @click="triggerUpload(card)">
                          <i class="fa-solid fa-plus"></i><i class="fa-regular fa-file"></i>
                    </button>


                    <input type="file" multiple :ref="'fileInput_' + card.title" class="hidden-file-input" @change="onFileChange($event, card)" />
                  </div>
                  <span class="card-title">{{ card.title }}</span>
                  <button v-if="card.files && card.files.length" class="card-view-btn" @click="downloadAllFiles(card)">
                    <i class="fa-solid fa-download"></i><span>Download All</span>
                  </button>
                  <button class="card-view-btn" @click="openViewAll(card)">
                    <i class="fa-solid fa-expand"></i><span>View All</span>
                  </button>
                </div>
              </template>

            </div>
          </div>
        </div>

        <!-- CHAT PANEL -->
        <OrderChatPanel
          v-if="showChat"
          :selected-order="selectedOrder"
          :team-members="teamMembers"
          :available-members="availableMembers"
          :chat-messages="chatMessages"
          :headers="headers"
          :initial="initial"
          :member-color="memberColor"
          :get-file-icon="getFileIcon"
          :normalize-order-file="normalizeOrderFile"
          @close="showChat = false"
          @update-chat-messages="chatMessages = $event"
          @refresh-files="fetchOrderFiles"
          @refresh-orders="refreshSelectedOrder"
          @add-chat-files-to-card="addChatFilesToCard"
        />
      </div>
    </div>

    <!-- ADD ORDER MODAL -->
    <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
      <div class="add-order-modal">
        <div class="view-all-header">
          <h5>{{ editingOrderId ? 'Edit Order' : 'Add New Order' }}</h5>
          <button class="modal-close" @click="closeOrderModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="add-order-body">
          <div class="field-group">
            <label>Order Name <span class="req">*</span></label>
            <input v-model="newOrder.name" class="field-input" placeholder="e.g. DUNBAR FOOTBALL UNIFORMS PO" @keyup.enter="confirmAddOrder" @input="newOrder.name = $event.target.value.toUpperCase()" ref="orderNameInput" />
          </div>
          <div class="field-row">
            <div class="field-group">
              <label>P.O #</label>
              <input v-model="newOrder.po" class="field-input" placeholder="e.g. 002030" />
            </div>
            <div class="field-group">
              <label>Ship Date</label>
              <input v-model="newOrder.shipDate" type="date" class="field-input" />
            </div>
          </div>
          <div class="field-row">
            <div class="field-group">
              <label>Status</label>
              <select v-model="newOrder.status" class="field-input">
                <option value="Pending">Pending</option>
                <option value="Designing">Designing</option>
                <option value="In Production">In Production</option>
                <option value="Completed">Completed</option>
                <option value="Shipped">Shipped</option>
                <option value="Delivered">Delivered</option>
              </select>
            </div>
            <div class="field-group">
              <label>Payment %</label>
              <input v-model="newOrder.payment" class="field-input" placeholder="e.g. 50 % Paid" />
            </div>
          </div>
          <div class="field-group">
            <label>TRK #</label>
            <input v-model="newOrder.trk" class="field-input" placeholder="e.g. 03316566200" />
          </div>
          <div class="field-group">
            <label>Add Members</label>
            <div class="member-select-actions">
              <button type="button" class="select-all-members-btn" @click="selectAllMembers"><i class="fa-solid fa-check-double me-1"></i>Select All</button>
              <button type="button" class="select-all-members-btn clear" @click="clearSelectedMembers">Clear</button>
            </div>
            <Multiselect v-model="newOrder.selectedMembers" :options="availableMembers" :multiple="true" placeholder="Select members" label="name" track-by="id" />
          </div>
        </div>
        <div class="add-order-footer">
          <button class="btn-cancel" @click="closeOrderModal">Cancel</button>
          <button class="btn-create" @click="confirmAddOrder" :disabled="savingOrder || !newOrder.name.trim()">
            <span v-if="savingOrder"><i class="fa-solid fa-spinner fa-spin me-1"></i>Saving...</span>
            <span v-else><i class="fa-solid fa-plus me-1"></i>{{ editingOrderId ? 'Update Order' : 'Create Order' }}</span>
          </button>
        </div>
      </div>
    </div>
<!-- BULK MEMBERS MODAL -->
<div v-if="bulkMembersModal" class="modal-overlay" @click.self="closeBulkMembersModal">
  <div class="add-order-modal">
    <div class="view-all-header">
      <h5>Edit Members For {{ selectedOrders.length }} Orders</h5>
      <button class="modal-close" @click="closeBulkMembersModal">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="add-order-body">
      <div class="field-group">
        <label>Select Members</label>

        <div class="member-select-actions">
          <button type="button" class="select-all-members-btn" @click="bulkSelectedMembers = [...availableMembers]">
            <i class="fa-solid fa-check-double me-1"></i>Select All
          </button>
          <button type="button" class="select-all-members-btn clear" @click="bulkSelectedMembers = []">
            Clear
          </button>
        </div>

        <Multiselect
          v-model="bulkSelectedMembers"
          :options="availableMembers"
          :multiple="true"
          placeholder="Select members"
          label="name"
          track-by="id"
        />

        <small style="color:#6b7280;font-weight:700;margin-top:6px;">
          Save karne par selected members selected orders me update ho jayen gy.
        </small>
      </div>
    </div>

    <div class="add-order-footer">
      <button class="btn-cancel" @click="closeBulkMembersModal">Cancel</button>

      <button class="btn-create" @click="bulkUpdateMembers" :disabled="bulkSaving">
        <span v-if="bulkSaving">
          <i class="fa-solid fa-spinner fa-spin me-1"></i>Saving...
        </span>
        <span v-else>
          <i class="fa-solid fa-floppy-disk me-1"></i>Save Members
        </span>
      </button>
    </div>
  </div>
</div>
    <!-- PROFILE MODAL -->
    <div v-if="profileModal" class="modal-overlay" @click.self="closeProfile">
      <div class="profile-modal" @click.stop>
        <button class="profile-close" @click="closeProfile"><i class="fa-solid fa-xmark"></i></button>
        <div class="profile-photo-wrap">
          <img v-if="profileForm.preview" :src="profileForm.preview" class="profile-photo" />
          <div v-else class="profile-photo-empty">{{ profileUser?.name ? profileUser.name.charAt(0).toUpperCase() : '?' }}</div>
        </div>
        <input v-if="isOwnProfile(profileUser)" type="file" accept="image/*" class="form-control form-control-sm mt-2" @change="onProfilePhotoChange" />
        <div class="profile-field">
          <label>Name</label>
          <input v-model="profileForm.name" :readonly="!isOwnProfile(profileUser)" class="form-control form-control-sm" />
        </div>
        <div class="profile-field">
          <label>Email</label>
          <input :value="profileUser?.email || ''" readonly class="form-control form-control-sm" />
        </div>
        <div class="profile-field">
          <label>Role</label>
          <input :value="profileUser?.role || ''" readonly class="form-control form-control-sm" />
        </div>
        <div class="profile-field">
          <label>About</label>
          <textarea v-model="profileForm.about" :readonly="!isOwnProfile(profileUser)" class="form-control" rows="4" placeholder="Write something about yourself..."></textarea>
        </div>
        <button v-if="isOwnProfile(profileUser)" class="btn btn-primary w-100 mt-3" @click="saveProfile">Save Profile</button>
      </div>
    </div>

    <!-- VIEW ALL MODAL -->
    <div v-if="viewAllCard" class="modal-overlay" @click.self="viewAllCard = null">
      <div class="view-all-modal">
        <div class="view-all-header">
          <h5>{{ viewAllCard.title }}</h5>
          <div class="view-all-head-actions">
            <button v-if="viewAllCard.files && viewAllCard.files.length" class="download-all-btn" @click="downloadAllFiles(viewAllCard)">
              <i class="fa-solid fa-file-zipper me-1"></i> Download All
            </button>
<button v-if="canUploadFiles && viewAllCard.type !== 'notes'" class="upload-small-btn" @click="triggerUpload(viewAllCard)">
                  <i class="fa-solid fa-upload me-1"></i> Upload
            </button>
<input v-if="canUploadFiles && viewAllCard.type !== 'notes'" type="file" multiple :ref="'fileInput_modal_' + viewAllCard.title" class="hidden-file-input" @change="onFileChange($event, viewAllCard)" />
            <button class="modal-close" @click="viewAllCard = null"><i class="fa-solid fa-xmark"></i></button>
          </div>
        </div>
        <div class="view-all-body">
          <div v-if="viewAllCard.files && viewAllCard.files.length" class="view-all-grid">
            <div v-for="(file, fi) in viewAllCard.files" :key="fi" class="view-file-item">
              <img v-if="file.isImage && !file.imageError" :src="file.url" class="view-file-img clickable-file-preview" @click="openPreviewFile(file)" @error="file.imageError = true" />
              <div v-else class="view-file-doc clickable-file-preview" @click="openPreviewFile(file)">
                <i :class="getFileIcon(file.name)" class="view-file-icon"></i>
              </div>
              <span class="view-file-name">{{ file.name }}</span>
              <div class="view-file-actions">
                <a :href="file.url" :download="file.name" class="vf-btn download-btn"><i class="fa-solid fa-download"></i></a>
             <button v-if="canDeleteFile(file)"
class="vf-btn remove-btn"
@click="removeFile(viewAllCard, fi)"><i class="fa-solid fa-trash"></i></button>
              </div>
            </div>
          </div>
          <div v-else class="view-all-empty">
            <i class="fa-solid fa-inbox" style="font-size:40px;opacity:0.3"></i>
            <p>No files uploaded yet</p>
<button v-if="canUploadFiles && viewAllCard.type !== 'notes'" class="upload-btn-big" @click="triggerUpload(viewAllCard)">
                  <i class="fa-solid fa-upload me-2"></i>Upload Files
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ORDER INFO MODAL -->
    <div v-if="orderInfoModal" class="modal-overlay" @click.self="closeOrderInfo">
      <div class="order-info-modal" @click.stop>
        <div class="view-all-header">
          <h5>Order Info</h5>
          <button class="modal-close" @click="closeOrderInfo"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="order-info-body">
          <div class="order-info-title">
            <strong>{{ infoOrder?.name || 'Order' }}</strong>
            <span>{{ infoOrder?.po || 'N/A' }}</span>
          </div>
          <div class="read-info-section">
            <h6>Seen by</h6>
            <div v-if="orderReadInfo.length" class="read-info-list">
              <div v-for="read in orderReadInfo" :key="read.user_id || read.email || read.name" class="read-info-row">
                <div class="read-user">
                  <div class="read-user-avatar">{{ initial(read.name || read.email || 'U') }}</div>
                  <div>
                    <strong>{{ read.name || 'User' }}</strong>
                    <small>{{ read.email || '' }}</small>
                  </div>
                </div>
                <span class="read-time">{{ formatReadDate(read.read_at) }}</span>
              </div>
            </div>
            <div v-else class="read-info-empty">No one has seen this order yet.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- IMAGE / FILE PREVIEW MODAL -->
    <div v-if="previewFile" class="image-preview-overlay" @click.self="previewFile = null">
      <div class="image-preview-modal">
        <button class="image-preview-close" @click="previewFile = null"><i class="fa-solid fa-xmark"></i></button>
        <img v-if="previewFile.isImage" :src="previewFile.url" class="image-preview-full" :alt="previewFile.name" />
       <div v-else class="file-preview-doc">
  <i :class="getFileIcon(previewFile.name)"></i>
  <strong>{{ previewFile.name }}</strong>
  <a :href="previewFile.url" target="_blank" class="download-all-btn mt-3">
    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Open File
  </a>
  <a :href="previewFile.url" :download="previewFile.name" class="download-all-btn mt-3" style="background:#374151">
    <i class="fa-solid fa-download me-1"></i>Download
  </a>
</div>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios'
import JSZip from 'jszip'
import Multiselect from 'vue-multiselect'
import OrderChatPanel from './OrderChatPanel.vue'
import 'vue-multiselect/dist/vue-multiselect.min.css'

export default {
  name: 'AllOrdersView',
  components: { Multiselect, OrderChatPanel },
  data() {
    return {
      leftWidth: 320,
      isResizing: false,
      mobileLeftOpen: false,
      loadingOrders: false,
      savingOrder: false,
      openOrderMenuId: null,
      editingOrderId: null,
      activeGroup: 'in_production',
      selectedOrder: null,
      showChat: false,
      viewAllCard: null,
      showAddModal: false,
      showStatusMenu: false,
      showPaymentMenu: false,
      showTrackingMenu: false,
      showDatePicker: false,
      previewFile: null,
      customStatusLabel: '',
      customStatusColor: '#6161ff',
      orders: [],
      availableMembers: [],
      teamMembers: [],
      chatMessages: [],
      unreadChatCount: 0,
      unreadTimer: null,

notificationTimer: null,
notifications: [],
notificationCount: 0,
lastNotificationId: null,
    searchOrder: '',

      selectedOrders: [],
      selectAll: false,
      bulkMembersModal: false,
      bulkSelectedMembers: [],
      bulkSaving: false,
      bulkActionLoading: false,
      orderInfoModal: false,
      infoOrder: null,
      orderReadInfo: [],
      profileModal: false,
      profileUser: null,
      profileForm: { name: '', about: '', profile_photo: null, preview: '' },

      trackingEdit: { number: '', company: '' },
      paymentEdit: { percent: '', received: '', balance: '' },

      newOrder: {
        name: '', po: '', selectedMembers: [], member_ids: [],
        shipDate: '', status: 'Pending', payment: '0 % Paid', trk: 'N/A'
      },

      groups: [
        { key: 'in_production', label: 'In Production' },
        { key: 'completed', label: 'Completed' },
        { key: 'shipped', label: 'Shipped' }
      ],

      statusOptions: [
        { label: 'Pending', color: '#fdab3d', group: 'in_production', groupLabel: 'In Production' },
        { label: 'Designing', color: '#00c875', group: 'in_production', groupLabel: 'In Production' },
        { label: 'In Production', color: '#6161ff', group: 'in_production', groupLabel: 'In Production' },
        { label: 'Completed', color: '#00c875', group: 'completed', groupLabel: 'Completed' },
        { label: 'Shipped', color: '#fdab3d', group: 'shipped', groupLabel: 'Shipped' },
        { label: 'Delivered', color: '#00c875', group: 'shipped', groupLabel: 'Shipped' }
      ]
    }
  },

  computed: {
 canEditNotes() {
  if (!this.selectedOrder || !this.currentUser) return false

  if (this.currentUser.role === 'super_admin') return true

  return this.currentUser.can_create_orders === true
},
  canUploadFiles() {
  return this.currentUser?.role === 'super_admin'
    || this.currentUser?.can_create_orders === true
},
    currentUser() {
      try { return JSON.parse(localStorage.getItem('user')) || null } catch { return null }
    },
    isSuperAdmin() { return this.currentUser?.role === 'super_admin' },
     canCreateOrder() {
    return this.currentUser?.role === 'super_admin' || this.currentUser?.can_create_orders === true
  },
    userInitial() {
      const raw = localStorage.getItem('user')
      if (!raw) return 'A'
      try { const user = JSON.parse(raw); return user?.name ? user.name.charAt(0).toUpperCase() : 'A' } catch { return 'A' }
    },
    userPhoto() { return this.currentUser?.profile_photo_url || null },
filteredOrders() {
  return this.orders.filter(o => {
    const groupMatch = o.group === this.activeGroup

    const searchMatch =
      !this.searchOrder ||
      o.name.toLowerCase().includes(
        this.searchOrder.toLowerCase()
      )

    return groupMatch && searchMatch
  })
},
    unreadOrdersCount() { return this.orders.filter(o => !o.user_has_seen).length },
    desktopLeftStyle() {
      // Only apply dynamic width on desktop (>= 768px)
      if (typeof window !== 'undefined' && window.innerWidth >= 768) {
        return { width: this.leftWidth + 'px' }
      }
      return {}
    }
  },

async mounted() {
  this.loadCustomStatuses()
  await this.fetchOrders()
  await this.fetchMembers()

  if ('Notification' in window) {
    Notification.requestPermission()
  }

  await this.fetchNotifications(false)

  this.notificationTimer = setInterval(() => {
    this.fetchNotifications(true)
  }, 5000)

  const orderId = this.$route.query.order_id

    if (orderId) {
      const foundOrder = this.orders.find(o => Number(o.id) === Number(orderId))
      if (foundOrder) {
        this.activeGroup = foundOrder.group
        await this.selectOrder(foundOrder)
      }
    } else if (this.filteredOrders.length) {
      await this.selectOrder(this.filteredOrders[0])
    }

    this.unreadTimer = setInterval(() => { this.fetchUnreadCount() }, 5000)
  },

beforeUnmount() {
  document.removeEventListener('mousemove', this.resizeSidebar)
  document.removeEventListener('mouseup', this.stopResize)

  if (this.unreadTimer) {
    clearInterval(this.unreadTimer)
  }

  if (this.notificationTimer) {
    clearInterval(this.notificationTimer)
  }
},

 methods: {
  canDeleteFile(file) {
    return this.isSuperAdmin || Number(file?.senderId) === Number(this.currentUser?.id)
  },

  toggleSelectAll() {
  this.selectedOrders = this.selectAll
    ? this.filteredOrders.map(o => o.id)
    : []
},

clearBulkSelection() {
  this.selectedOrders = []
  this.selectAll = false
},

openBulkMembersModal() {
  if (!this.selectedOrders.length) return
  this.bulkSelectedMembers = []
  this.bulkMembersModal = true
},

closeBulkMembersModal() {
  this.bulkMembersModal = false
  this.bulkSelectedMembers = []
  this.bulkSaving = false
},

async bulkUpdateMembers() {
  if (!this.selectedOrders.length) return

  this.bulkSaving = true

  try {
    await axios.post('/api/orders/bulk-members', {
      order_ids: this.selectedOrders,
      member_ids: this.bulkSelectedMembers.map(m => m.id)
    }, { headers: this.headers() })

    this.closeBulkMembersModal()
    this.clearBulkSelection()
    await this.fetchOrders()

    if (this.selectedOrder) {
      const fresh = this.orders.find(o => Number(o.id) === Number(this.selectedOrder.id))
      if (fresh) await this.selectOrder(fresh)
    }
  } catch (e) {
    console.error('bulkUpdateMembers error:', e)
    alert(e.response?.data?.message || 'Members update nahi huay')
  } finally {
    this.bulkSaving = false
  }
},

async bulkDuplicateOrders() {
  if (!this.selectedOrders.length || this.bulkActionLoading) return

  this.bulkActionLoading = true

  try {
    await axios.post('/api/orders/bulk-duplicate', {
      order_ids: this.selectedOrders
    }, { headers: this.headers() })

    this.clearBulkSelection()
    await this.fetchOrders()
  } catch (e) {
    console.error('bulkDuplicateOrders error:', e)
    alert(e.response?.data?.message || 'Orders duplicate nahi huay')
  } finally {
    this.bulkActionLoading = false
  }
},

async bulkDeleteOrders() {
  if (!this.selectedOrders.length || this.bulkActionLoading) return
  if (!confirm('Selected orders delete karne hain?')) return

  this.bulkActionLoading = true

  try {
    await axios.post('/api/orders/bulk-delete', {
      order_ids: this.selectedOrders
    }, { headers: this.headers() })

    const deletedIds = [...this.selectedOrders].map(id => Number(id))
    this.clearBulkSelection()
    await this.fetchOrders()

    if (this.selectedOrder && deletedIds.includes(Number(this.selectedOrder.id))) {
      this.selectedOrder = this.filteredOrders[0] || this.orders[0] || null
      if (this.selectedOrder) await this.selectOrder(this.selectedOrder)
    }
  } catch (e) {
    console.error('bulkDeleteOrders error:', e)
    alert(e.response?.data?.message || 'Orders delete nahi huay')
  } finally {
    this.bulkActionLoading = false
  }
},
    generatePoNumber() {
      const d = new Date()
      const y = d.getFullYear()
      const m = String(d.getMonth() + 1).padStart(2, '0')
      const day = String(d.getDate()).padStart(2, '0')
      const time = String(d.getTime()).slice(-5)
      return `PO=${y}-${m}-${day}-${time}`
    },
    headers() {
      return { Authorization: `Bearer ${localStorage.getItem('token')}`, Accept: 'application/json' }
    },

    // Mobile: select order and close left panel
    selectOrderAndClose(order) {
      this.selectOrder(order)
      this.mobileLeftOpen = false
    },

    loadCustomStatuses() {
      try {
        const saved = JSON.parse(localStorage.getItem('custom_order_statuses') || '[]')
        saved.forEach(item => {
          if (item?.label && !this.statusOptions.some(s => s.label === item.label)) {
            this.statusOptions.push({ label: item.label, color: item.color || '#6161ff', group: item.group || 'in_production', groupLabel: item.groupLabel || 'In Production', custom: true })
          }
        })
      } catch (e) { console.error('loadCustomStatuses error:', e) }
    },

    saveCustomStatusOption(status) {
      if (!status?.label) return
      const existsIndex = this.statusOptions.findIndex(s => s.label === status.label)
      if (existsIndex === -1) this.statusOptions.push(status)
      else this.statusOptions.splice(existsIndex, 1, { ...this.statusOptions[existsIndex], ...status })
      const customOnly = this.statusOptions.filter(s => s.custom).map(s => ({ label: s.label, color: s.color, group: s.group || 'in_production', groupLabel: s.groupLabel || 'In Production', custom: true }))
      localStorage.setItem('custom_order_statuses', JSON.stringify(customOnly))
    },

    async changeStatusOptionColor(status, color) {
      if (!status) return
      status.color = color
      if (status.custom) this.saveCustomStatusOption(status)
      if (this.selectedOrder && this.selectedOrder.status === status.label) await this.changeStatus(status)
    },

    selectAllMembers() { this.newOrder.selectedMembers = [...this.availableMembers] },
    clearSelectedMembers() { this.newOrder.selectedMembers = [] },

    triggerInvoiceUpload() {
      if (!this.isSuperAdmin || !this.selectedOrder) return
      this.$refs.invoiceInput?.click()
    },

    async onInvoiceFileChange(event) {
      const files = Array.from(event.target.files || [])
      event.target.value = ''
      if (!files.length || !this.isSuperAdmin || !this.selectedOrder) return
      const formData = new FormData()
      formData.append('card_type', 'invoice_files')
      files.forEach(file => formData.append('files[]', file))
      try {
        await axios.post(`/api/orders/${this.selectedOrder.id}/files`, formData, { headers: { ...this.headers(), 'Content-Type': 'multipart/form-data' } })
        await this.fetchOrderFiles(this.selectedOrder.id)
        alert('Invoice uploaded successfully')
      } catch (e) { console.error('invoice upload error:', e); alert(e.response?.data?.message || 'Invoice upload nahi hui') }
    },

    parseTracking(value) {
      const raw = (value || '').trim()
      const urlMatch = raw.match(/(https?:\/\/)?(www\.)?[a-z0-9.-]+\.[a-z]{2,}(\/\S*)?/i)
      const company = urlMatch ? urlMatch[0] : ''
      const number = company ? raw.replace(company, '').trim() : raw
      return { number, company }
    },

    normalizeTrackingCompany(value) {
      if (!value) return ''
      let clean = String(value).trim().toLowerCase()
      clean = clean.replace(/^https?:\/\//, '').replace(/^www\./, '').split('/')[0]
      if (clean.includes('dhl')) return 'dhl.com'
      if (clean.includes('ups')) return 'ups.com'
      if (clean.includes('fedex')) return 'fedex.com'
      if (clean.includes('usps')) return 'usps.com'
      return clean.includes('.') ? clean : ''
    },

    trackingLogo(value) {
      const domain = this.normalizeTrackingCompany(value)
      if (!domain) return ''
      return `https://www.google.com/s2/favicons?domain=${domain}&sz=64`
    },

    trackingCompanyName(value) {
      const domain = this.normalizeTrackingCompany(value)
      if (!domain) return ''
      if (domain.includes('dhl')) return 'DHL'
      if (domain.includes('ups')) return 'UPS'
      if (domain.includes('fedex')) return 'FedEx'
      if (domain.includes('usps')) return 'USPS'
      return domain
    },

    openTrackingMenu() {
      if (!this.selectedOrder) return
      this.trackingEdit = this.parseTracking(this.selectedOrder.trk)
      this.showTrackingMenu = !this.showTrackingMenu
    },

    async saveTracking() {
      if (!this.selectedOrder) return
      const number = (this.trackingEdit.number || '').trim()
      const company = (this.trackingEdit.company || '').trim()
      const trk = [number, company].filter(Boolean).join(' ') || 'N/A'
      try {
        await axios.put(`/api/orders/${this.selectedOrder.id}`, { trk }, { headers: this.headers() })
        this.selectedOrder.trk = trk
        const idx = this.orders.findIndex(o => Number(o.id) === Number(this.selectedOrder.id))
        if (idx !== -1) this.orders[idx].trk = trk
        this.showTrackingMenu = false
      } catch (e) { console.error('saveTracking error:', e); alert(e.response?.data?.message || 'Tracking save nahi hua') }
    },

openPreviewFile(file) {
  if (!file?.url) return
  const ext = (file.name || '').split('.').pop().toLowerCase()
  const officeExts = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']
  const browserExts = ['pdf', 'csv', 'txt']

  if (officeExts.includes(ext)) {
    // Google Docs Viewer se open karo — download nahi hoga
    const fullUrl = file.url.startsWith('http')
      ? file.url
      : window.location.origin + file.url
    const viewerUrl = `https://docs.google.com/viewer?url=${encodeURIComponent(fullUrl)}&embedded=false`
    window.open(viewerUrl, '_blank')
    return
  }

  if (browserExts.includes(ext)) {
    window.open(file.url, '_blank')
    return
  }

  this.previewFile = file
},
    safeFileName(name) { return String(name || 'file').replace(/[^a-z0-9_.-]/gi, '_') },
    zipBaseName() {
      const d = new Date()
      const month = d.toLocaleString('en-US', { month: 'long' }).toLowerCase()
      const day = String(d.getDate()).padStart(2, '0')
      const year = d.getFullYear()
      const po = this.safeFileName(this.selectedOrder?.po || this.generatePoNumber())
      return `${month}-${day}-${year}-${po}`
    },

    async downloadAllFiles(card) {
      const files = (card?.files || []).filter(file => file?.url)
      if (!files.length) return
      try {
        const zip = new JSZip()
        for (const file of files) {
          const response = await fetch(file.url)
          const blob = await response.blob()
          zip.file(this.safeFileName(file.name), blob)
        }
        const content = await zip.generateAsync({ type: 'blob' })
        const zipUrl = URL.createObjectURL(content)
        const a = document.createElement('a')
        a.href = zipUrl
        a.download = `${this.zipBaseName()}.zip`
        document.body.appendChild(a)
        a.click()
        a.remove()
        URL.revokeObjectURL(zipUrl)
      } catch (e) { console.error('downloadAllFiles error:', e); alert('Download all ZIP nahi ban saka') }
    },

    isOwnProfile(user) { return Number(user?.id || 0) === Number(this.currentUser?.id || 0) },

    async openProfile(user) {
      if (!user?.id) return
      try {
        const res = await axios.get(`/api/users/${user.id}/profile`, { headers: this.headers() })
        this.profileUser = res.data
        this.profileForm = { name: res.data?.name || '', about: res.data?.about || '', profile_photo: null, preview: res.data?.profile_photo_url || '' }
        this.profileModal = true
      } catch (e) { console.error('openProfile error:', e); alert(e.response?.data?.message || 'Profile load nahi hui') }
    },

    closeProfile() {
      this.profileModal = false
      this.profileUser = null
      this.profileForm = { name: '', about: '', profile_photo: null, preview: '' }
    },

    onProfilePhotoChange(event) {
      const file = event.target.files?.[0]
      if (!file) return
      this.profileForm.profile_photo = file
      this.profileForm.preview = URL.createObjectURL(file)
    },

    async saveProfile() {
      if (!this.isOwnProfile(this.profileUser)) return
      const form = new FormData()
      form.append('name', this.profileForm.name || '')
      form.append('about', this.profileForm.about || '')
      if (this.profileForm.profile_photo) form.append('profile_photo', this.profileForm.profile_photo)
      try {
        const res = await axios.post('/api/me/profile', form, { headers: { ...this.headers(), 'Content-Type': 'multipart/form-data' } })
        const user = res.data?.user
        if (user) localStorage.setItem('user', JSON.stringify(user))
        this.closeProfile()
        await this.fetchMembers()
        await this.fetchOrders()
      } catch (e) { console.error('saveProfile error:', e); alert(e.response?.data?.message || 'Profile save nahi hui') }
    },

    async toggleChat() {
      this.showChat = !this.showChat
      if (this.showChat && this.selectedOrder) {
        this.unreadChatCount = 0
        try { await axios.post(`/api/orders/${this.selectedOrder.id}/messages/mark-read`, {}, { headers: this.headers() }) } catch (e) { console.error('mark read error:', e) }
      } else {
        await this.fetchUnreadCount()
      }
    },

    async fetchUnreadCount() {
      if (!this.selectedOrder) { this.unreadChatCount = 0; return }
      if (this.showChat) { this.unreadChatCount = 0; return }
      try {
        const res = await axios.get(`/api/orders/${this.selectedOrder.id}/messages/unread-count`, { headers: this.headers() })
        this.unreadChatCount = Number(res.data?.count || 0)
      } catch (e) { console.error('fetchUnreadCount error:', e) }
    },

    async markChatRead() {
      if (!this.selectedOrder) return
      try {
        await axios.post(`/api/orders/${this.selectedOrder.id}/messages/mark-read`, {}, { headers: this.headers() })
        this.unreadChatCount = 0
        await this.fetchMessages(this.selectedOrder.id)
      } catch (e) { console.error('markChatRead error:', e) }
    },

    async fetchOrders() {
      this.loadingOrders = true
      try {
        const res = await axios.get('/api/orders', { headers: this.headers() })
        const list = Array.isArray(res.data) ? res.data : (res.data?.data || [])
        this.orders = list.map(order => this.formatOrder(order))
        if (this.selectedOrder) {
          const fresh = this.orders.find(o => Number(o.id) === Number(this.selectedOrder.id))
          if (fresh) this.selectedOrder = fresh
        }
      } catch (e) { console.error('fetchOrders error:', e) } finally { this.loadingOrders = false }
    },

    async fetchMembers() {
      try {
        const res = await axios.get('/api/members', { headers: this.headers() })
        this.availableMembers = Array.isArray(res.data) ? res.data : (res.data?.data || [])
      } catch (e) { console.error('fetchMembers error:', e) }
    },

    formatOrder(order) {
      const members = order.members || []
      const status = order.status || 'Pending'
      return {
        id: order.id,
        user_has_seen: Boolean(order.user_has_seen),
        read_at: order.read_at || null,
        read_info: order.read_info || [],
        group: this.statusToGroup(status),
        name: order.name,
        hasChildren: false,
        po: order.po || 'N/A',
        shipDate: order.ship_date ? this.formatDate(order.ship_date) : 'TBD',
        shipDateRaw: order.ship_date || '',
        status,
        statusColor: order.status_color || this.statusColor(status),
        trk: order.trk || 'N/A',
        payment: order.payment || '0 % Paid',
        paymentReceived: order.payment_received || 0,
        paymentBalance: order.payment_balance || 0,
        members,
        invoiceFiles: [],
        owners: members.map(m => ({
          id: m.id, name: m.name, email: m.email,
          initial: this.initial(m.name), color: this.memberColor(m.id),
          role: m.pivot?.role || m.role || 'member',
          profile_photo_url: m.profile_photo_url || null, about: m.about || ''
        })),
        cards: [
          { title: 'Approved Mockup', type: 'approved_mockup', icon: 'fa-solid fa-shirt', files: [], thumbnail: '' },
          { title: 'Logos', type: 'logos', icon: 'fa-solid fa-image', files: [], thumbnail: '' },
          { title: 'Team Roster', type: 'roster', icon: 'fa-solid fa-users', files: [], thumbnail: '' },
          { title: 'Finished Products', type: 'finished_products', icon: 'fa-solid fa-box', files: [], thumbnail: '' },
          { title: 'Files', type: 'order_files', icon: 'fa-solid fa-folder-open', files: [], thumbnail: '' },
          { title: 'Notes', type: 'notes', icon: 'fa-solid fa-file-word', files: [], thumbnail: '', noteText: order.notes || '', saved: false }
        ]
      }
    },

    statusToGroup(status) {
      if (status === 'Completed') return 'completed'
      if (status === 'Shipped' || status === 'Delivered') return 'shipped'
      return 'in_production'
    },

    statusColor(status) {
      const found = this.statusOptions.find(s => s.label === status)
      return found ? found.color : '#fdab3d'
    },

    initial(name) { return name ? name.charAt(0).toUpperCase() : '?' },
    memberColor(id) {
      const colors = ['#6161ff', '#ff3d71', '#00c875', '#fdab3d', '#00c2ff']
      return colors[Number(id || 0) % colors.length]
    },

    formatDate(date) {
      if (!date) return 'TBD'
      const d = new Date(date)
      if (Number.isNaN(d.getTime())) return date
      return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
    },

    async selectOrder(order) {
      this.selectedOrder = order
      this.closeAllMenus()
      await this.markOrderRead(order)
      this.paymentEdit = {
        percent: order.payment ? order.payment.replace(' % Paid', '').replace('%', '').trim() : '',
        received: order.paymentReceived || '',
        balance: order.paymentBalance || ''
      }
      this.trackingEdit = this.parseTracking(order.trk)
      this.teamMembers = (order.members || []).map(m => ({
        id: m.id, name: m.name, email: m.email,
        initial: this.initial(m.name), color: this.memberColor(m.id),
        role: m.pivot?.role || m.role || 'member',
        profile_photo_url: m.profile_photo_url || null, about: m.about || '',
        online: true, lastSeen: 'Now'
      }))
      await this.fetchMessages(order.id)
      await this.fetchOrderFiles(order.id)
      if (this.showChat) await this.markChatRead()
      else await this.fetchUnreadCount()
    },

    async markOrderRead(order) {
      if (!order?.id || order.user_has_seen) return
      try {
        const res = await axios.post(`/api/orders/${order.id}/mark-read`, {}, { headers: this.headers() })
        order.user_has_seen = true
        order.read_at = res.data?.read_at || new Date().toISOString()
        const idx = this.orders.findIndex(o => Number(o.id) === Number(order.id))
        if (idx !== -1) { this.orders[idx].user_has_seen = true; this.orders[idx].read_at = order.read_at }
      } catch (e) { console.error('markOrderRead error:', e) }
    },

    async openOrderInfo(order) {
      if (!order?.id) return
      this.infoOrder = order
      this.orderReadInfo = order.read_info || []
      this.openOrderMenuId = null
      this.orderInfoModal = true
      try {
        const res = await axios.get(`/api/orders/${order.id}/read-info`, { headers: this.headers() })
        this.orderReadInfo = res.data?.reads || []
      } catch (e) { console.error('openOrderInfo error:', e) }
    },

    closeOrderInfo() { this.orderInfoModal = false; this.infoOrder = null; this.orderReadInfo = [] },

    formatReadDate(date) {
      if (!date) return 'Not seen yet'
      const d = new Date(date)
      if (Number.isNaN(d.getTime())) return date
      return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    },

    async fetchMessages(orderId) {
      try {
        const res = await axios.get(`/api/orders/${orderId}/messages`, { headers: this.headers() })
        const list = Array.isArray(res.data) ? res.data : (res.data?.data || [])
        this.chatMessages = list.map(msg => {
          const createdDate = msg.created_at ? new Date(msg.created_at) : new Date()
          return {
            id: msg.id, localKey: `msg-${msg.id}`,
            senderId: msg.user?.id || null,
            sender: msg.user?.name || 'User',
            senderInitial: this.initial(msg.user?.name),
            senderColor: this.memberColor(msg.user?.id),
            senderPhoto: msg.user?.profile_photo_url || null,
            time: createdDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            sortAt: createdDate.getTime(),
            editedAt: msg.edited_at || null,
            deletedEveryoneAt: msg.deleted_everyone_at || null,
            reads: msg.reads || [],
            seenBy: (msg.reads || []).map(r => ({ id: r.user?.id || r.user_id, name: r.user?.name || 'User', readAt: r.read_at })),
            text: msg.deleted_everyone_at ? '' : msg.message
          }
        })
      } catch (e) { console.error('fetchMessages error:', e); this.chatMessages = [] }
    },

    addNewOrder() {
  if (!this.canCreateOrder) return

  this.editingOrderId = null
      this.openOrderMenuId = null
      this.newOrder = { name: '', po: this.generatePoNumber(), selectedMembers: [], shipDate: '', status: 'Pending', payment: '0 % Paid', trk: 'N/A' }
      this.showAddModal = true
      this.$nextTick(() => { this.$refs.orderNameInput?.focus() })
    },

    closeOrderModal() { this.showAddModal = false; this.editingOrderId = null; this.savingOrder = false },
    toggleOrderMenu(id) { this.openOrderMenuId = this.openOrderMenuId === id ? null : id },

    openEditOrder(order) {
      this.editingOrderId = order.id
      this.openOrderMenuId = null
      this.newOrder = {
        name: order.name || '', po: order.po === 'N/A' ? '' : (order.po || ''),
        selectedMembers: (order.members || []).map(member => this.availableMembers.find(m => Number(m.id) === Number(member.id)) || member),
        shipDate: order.shipDateRaw || '', status: order.status || 'Pending',
        payment: order.payment || '0 % Paid', trk: order.trk === 'N/A' ? '' : (order.trk || 'N/A')
      }
      this.showAddModal = true
      this.$nextTick(() => this.$refs.orderNameInput?.focus())
    },

    getOrderNote(order) { return order.cards?.find(c => c.title === 'Notes')?.noteText || '' },

    async duplicateOrder(order) {
      if (!this.isSuperAdmin) return
      const status = this.statusOptions.find(s => s.label === order.status)
      try {
        const res = await axios.post('/api/orders', {
          name: `${order.name} Copy`, po: order.po === 'N/A' ? '' : order.po,
          member_ids: (order.members || []).map(m => m.id), ship_date: order.shipDateRaw || null,
          status: order.status, status_color: status?.color || order.statusColor || '#fdab3d',
          trk: order.trk || 'N/A', payment: order.payment || '0 % Paid', notes: this.getOrderNote(order)
        }, { headers: this.headers() })
        const rawOrder = res.data?.order || res.data?.data || res.data
        const newOrder = this.formatOrder(rawOrder)
        this.orders.unshift(newOrder)
        this.activeGroup = newOrder.group
        await this.selectOrder(newOrder)
        this.openOrderMenuId = null
      } catch (e) { console.error('duplicateOrder error:', e); alert(e.response?.data?.message || 'Order duplicate nahi hua') }
    },

    async deleteOrder(order) {
      if (!this.isSuperAdmin) return
      if (!confirm('Delete this order?')) return
      try {
        await axios.delete(`/api/orders/${order.id}`, { headers: this.headers() })
        this.orders = this.orders.filter(o => o.id !== order.id)
        if (this.selectedOrder?.id === order.id) {
          this.selectedOrder = this.orders[0] || null
          if (this.selectedOrder) await this.selectOrder(this.selectedOrder)
        }
        this.openOrderMenuId = null
      } catch (e) { console.error('deleteOrder error:', e); alert(e.response?.data?.message || 'Order delete nahi hua') }
    },

    async confirmAddOrder() {
  if (!this.canCreateOrder && !this.editingOrderId) return
  if (!this.newOrder.name.trim() || this.savingOrder) return
      this.savingOrder = true
      const status = this.statusOptions.find(s => s.label === this.newOrder.status)
      const payload = {
        name: this.newOrder.name, po: this.newOrder.po,
        member_ids: this.newOrder.selectedMembers.map(m => m.id),
        ship_date: this.newOrder.shipDate || null, status: this.newOrder.status,
        status_color: status?.color || '#fdab3d', trk: this.newOrder.trk || 'N/A',
        payment: this.newOrder.payment || '0 % Paid'
      }

      try {
        let res
        if (this.editingOrderId) res = await axios.put(`/api/orders/${this.editingOrderId}`, payload, { headers: this.headers() })
        else res = await axios.post('/api/orders', payload, { headers: this.headers() })
        const rawOrder = res.data?.order || res.data?.data || res.data
        const order = this.formatOrder(rawOrder)
        const idx = this.orders.findIndex(o => o.id === order.id)
        if (idx !== -1) this.orders.splice(idx, 1, order)
        else this.orders.unshift(order)
        this.activeGroup = order.group
        await this.fetchOrders()
        const freshOrder = this.orders.find(o => Number(o.id) === Number(order.id)) || order
        await this.selectOrder(freshOrder)
        this.closeOrderModal()
      } catch (e) { console.error('confirmAddOrder error:', e); alert(e.response?.data?.message || 'Order save nahi hua') }
      finally { this.savingOrder = false }
    },

    async applyCustomStatus() {
      if (!this.selectedOrder) return
      const label = (this.customStatusLabel || '').trim()
      if (!label) return
      const custom = { label, color: this.customStatusColor || '#6161ff', group: 'in_production', groupLabel: 'In Production', custom: true }
      this.saveCustomStatusOption(custom)
      await this.changeStatus(custom)
      this.customStatusLabel = ''
      this.customStatusColor = '#6161ff'
    },

    async changeStatus(s) {
      if (!this.selectedOrder) return
      try {
        await axios.put(`/api/orders/${this.selectedOrder.id}`, { status: s.label, status_color: s.color || '#6161ff' }, { headers: this.headers() })
        this.selectedOrder.status = s.label
        this.selectedOrder.statusColor = s.color || '#6161ff'
        this.selectedOrder.group = s.group || 'in_production'
        this.activeGroup = s.group || 'in_production'
        const idx = this.orders.findIndex(o => o.id === this.selectedOrder.id)
        if (idx !== -1) this.orders[idx] = { ...this.selectedOrder }
        this.showStatusMenu = false
      } catch (e) { console.error('changeStatus error:', e) }
    },

    async updateShipDate(event) {
      if (!this.selectedOrder) return
      const raw = event.target.value
      if (!raw) return
      try {
        await axios.put(`/api/orders/${this.selectedOrder.id}`, { ship_date: raw }, { headers: this.headers() })
        this.selectedOrder.shipDateRaw = raw
        this.selectedOrder.shipDate = this.formatDate(raw)
        this.showDatePicker = false
      } catch (e) { console.error('updateShipDate error:', e) }
    },

    async savePayment() {
      if (!this.selectedOrder) return
      const payment = `${this.paymentEdit.percent || 0} % Paid`
      try {
        await axios.put(`/api/orders/${this.selectedOrder.id}`, { payment, payment_received: this.paymentEdit.received || 0, payment_balance: this.paymentEdit.balance || 0 }, { headers: this.headers() })
        this.selectedOrder.payment = payment
        this.selectedOrder.paymentReceived = this.paymentEdit.received || 0
        this.selectedOrder.paymentBalance = this.paymentEdit.balance || 0
        this.showPaymentMenu = false
      } catch (e) { console.error('savePayment error:', e) }
    },

   async saveNote(card) {
  if (!this.selectedOrder || !this.canEditNotes) return

  try {
    await axios.put(`/api/orders/${this.selectedOrder.id}`, {
      notes: card.noteText
    }, { headers: this.headers() })

    card.saved = true
    setTimeout(() => { card.saved = false }, 2500)
  } catch (e) {
    console.error('saveNote error:', e)
    alert(e.response?.data?.message || 'Note save nahi hua')
  }
},

    normalizeOrderFile(file) {
      const mime = file.mime_type || file.type || ''
      const name = file.original_name || file.name || 'File'
      let url = file.url || ''
      if (!url && file.file_path) url = `/storage/${file.file_path}`
      return {
        id: file.id, name, url: encodeURI(url),
        isImage: mime.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(name),
        type: mime, size: file.size || 0, createdAt: file.created_at || null,
        sender: file.user?.name || 'Shared file', senderId: file.user?.id || file.user_id || null,
        senderInitial: this.initial(file.user?.name || 'F'),
        senderColor: this.memberColor(file.user?.id || file.user_id || 0),
        senderPhoto: file.user?.profile_photo_url || null, saved: true, imageError: false
      }
    },

    async fetchOrderFiles(orderId) {
      if (!this.selectedOrder) return
      try {
        const res = await axios.get(`/api/orders/${orderId}/files`, { headers: this.headers() })
        const files = Array.isArray(res.data) ? res.data : (res.data?.data || [])
        const normalizedFiles = files.map(file => ({ ...this.normalizeOrderFile(file), cardType: file.card_type }))
        this.selectedOrder.invoiceFiles = normalizedFiles.filter(file => file.cardType === 'invoice_files')
        this.selectedOrder.cards.forEach(card => {
          if (card.type === 'notes') return
          if (card.type === 'order_files') card.files = normalizedFiles.filter(file => file.cardType === 'order_files' || file.cardType === 'chat_files')
          else card.files = normalizedFiles.filter(file => file.cardType === card.type)
        })
        this.syncChatFileMessages(normalizedFiles.filter(file => file.cardType === 'chat_files'))
      } catch (e) { console.error('fetchOrderFiles error:', e) }
    },

    mergeFiles(oldFiles = [], newFiles = []) {
      const map = new Map()
      oldFiles.forEach(file => { const key = file.id || file.url || `${file.name}-${file.size}`; map.set(String(key), file) })
      newFiles.forEach(file => { const key = file.id || file.url || `${file.name}-${file.size}`; map.set(String(key), file) })
      return Array.from(map.values())
    },

    syncChatFileMessages(chatFiles) {
      const normalMessages = this.chatMessages.filter(msg => !msg.fileMessageId)
      const fileMessages = chatFiles.map(file => {
        const createdDate = file.createdAt ? new Date(file.createdAt) : new Date()
        return {
          localKey: `file-${file.id}`, fileMessageId: file.id,
          senderId: file.senderId || file.user_id || null, sender: file.sender || 'Shared file',
          senderInitial: file.senderInitial || this.initial(file.sender || 'F'),
          senderColor: file.senderColor || this.memberColor(file.senderId || file.user_id || 0),
          senderPhoto: file.senderPhoto || null,
          time: createdDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
          sortAt: createdDate.getTime(), text: '', files: [file]
        }
      })
      this.chatMessages = [...normalMessages, ...fileMessages]
    },

    triggerUpload(card) {
if (!this.canUploadFiles || !card || card.type === 'notes') return
      const refKey = this.viewAllCard ? 'fileInput_modal_' + card.title : 'fileInput_' + card.title
      const input = this.$refs[refKey]
      if (input) { const el = Array.isArray(input) ? input[0] : input; el.click() }
    },
    async uploadFilesToOrder(files, cardType) {
      if (!files.length || !this.selectedOrder) return
      const card = this.selectedOrder.cards.find(c => c.type === cardType)
      if (!card) return
      const tempFiles = files.map((file, index) => ({
        id: `temp-${Date.now()}-${index}-${Math.random().toString(16).slice(2)}`,
        name: file.name, url: URL.createObjectURL(file),
        isImage: file.type.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(file.name),
        type: file.type || 'file', size: file.size, uploading: true, cardType
      }))
      card.files = this.mergeFiles(card.files || [], tempFiles)
      const formData = new FormData()
      formData.append('card_type', cardType)
      files.forEach(file => { formData.append('files[]', file) })
      try {
        const res = await axios.post(`/api/orders/${this.selectedOrder.id}/files`, formData, { headers: { Authorization: `Bearer ${localStorage.getItem('token')}`, Accept: 'application/json' } })
        const savedFilesRaw = res.data?.files || []
        const savedFiles = savedFilesRaw.map(file => ({ ...this.normalizeOrderFile(file), cardType }))
        const withoutTemp = (card.files || []).filter(file => !file.uploading)
        card.files = this.mergeFiles(withoutTemp, savedFiles)
      } catch (e) { card.files = (card.files || []).filter(file => !file.uploading); throw e }
    },

async onFileChange(event, card) {
  const files = Array.from(event.target.files || [])
  event.target.value = ''

  if (!files.length) return

  if (!this.canUploadFiles || !card || card.type === 'notes') return

  try {
    await this.uploadFilesToOrder(files, card.type)
  }
      catch (e) { console.error('onFileChange error:', e); alert(e.response?.data?.message || 'File upload nahi hui') }
    },

    async onDrop(event, card) {
      const files = Array.from(event.dataTransfer.files || [])
if (!files.length || !this.canUploadFiles || !card || card.type === 'notes') return
      try { await this.uploadFilesToOrder(files, card.type) }
      catch (e) { console.error('onDrop error:', e); alert(e.response?.data?.message || 'File upload nahi hui') }
    },

  async removeFile(card, index) {
  const file = card.files[index]

  if (!this.canDeleteFile(file)) return

  if (!file?.id) return

  if (!confirm('Delete this file?')) return

  try {
    await axios.delete(`/api/order-files/${file.id}`, { headers: this.headers() })
    card.files.splice(index, 1)
  } catch (e) {
    console.error('removeFile error:', e)
    alert(e.response?.data?.message || 'File delete nahi hui')
  }
},

    openViewAll(card) { this.viewAllCard = card },

    openInvoiceFiles() {
      if (!this.isSuperAdmin || !this.selectedOrder?.invoiceFiles?.length) return
      this.viewAllCard = { title: 'Invoices', type: 'invoice_files', files: this.selectedOrder.invoiceFiles }
    },

    async refreshSelectedOrder() {
      await this.fetchOrders()
      const fresh = this.orders.find(o => o.id === this.selectedOrder?.id)
      if (fresh) await this.selectOrder(fresh)
    },

    addChatFilesToCard(savedFiles) {
      const filesCard = this.selectedOrder?.cards?.find(card => card.type === 'order_files')
      if (filesCard) {
        const convertedFiles = savedFiles.map(file => ({ ...file, cardType: 'order_files' }))
        filesCard.files = this.mergeFiles(filesCard.files || [], convertedFiles)
      }
    },

    getFileIcon(name) {
      const ext = name.split('.').pop().toLowerCase()
      if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) return 'fa-solid fa-image'
      if (ext === 'pdf') return 'fa-solid fa-file-pdf'
      if (['doc', 'docx'].includes(ext)) return 'fa-solid fa-file-word'
      if (['xls', 'xlsx'].includes(ext)) return 'fa-solid fa-file-excel'
      if (['ppt', 'pptx'].includes(ext)) return 'fa-solid fa-file-powerpoint'
      if (['zip', 'rar'].includes(ext)) return 'fa-solid fa-file-zipper'
      return 'fa-solid fa-file'
    },

    closeAllMenus() {
      this.showStatusMenu = false
      this.showPaymentMenu = false
      this.showTrackingMenu = false
      this.showDatePicker = false
      this.openOrderMenuId = null
    },

    startResize() {
      this.isResizing = true
      document.addEventListener('mousemove', this.resizeSidebar)
      document.addEventListener('mouseup', this.stopResize)
    },

    resizeSidebar(e) {
      if (!this.isResizing) return
      let newWidth = e.clientX
      if (newWidth < 240) newWidth = 240
      if (newWidth > 650) newWidth = 650
      this.leftWidth = newWidth
    },

   stopResize() {
  this.isResizing = false
  document.removeEventListener('mousemove', this.resizeSidebar)
  document.removeEventListener('mouseup', this.stopResize)
},

async fetchNotifications(showPopup = false) {
  try {
    const res = await axios.get('/api/notifications', {
      headers: this.headers()
    })

    const list = Array.isArray(res.data) ? res.data : []

    this.notifications = list
    this.notificationCount = list.filter(n => !n.is_read).length

    const latest = list[0]

    if (
      showPopup &&
      latest &&
      this.lastNotificationId &&
      Number(latest.id) !== Number(this.lastNotificationId)
    ) {
      this.showDesktopNotification(latest)
    }

    if (latest) {
      this.lastNotificationId = latest.id
    }

  } catch (e) {
    console.error('Notification Error:', e)
  }
},

showDesktopNotification(notification) {
  if (!('Notification' in window)) return

  if (Notification.permission === 'granted') {
    new Notification(notification.title || 'Order Notification', {
      body: notification.message || 'New order update received'
    })
  }
}








  }



}
</script>

<style scoped>
.detail-topbar-wrapper {
  overflow: visible !important;
  position: relative;
  z-index: 9999;
  background: #fff;
  width: 100%;
}

.detail-topbar,
.detail-info-item {
  overflow: visible !important;
}

.status-dropdown,
.tracking-dropdown,
.payment-dropdown,
.date-dropdown {
  z-index: 99999 !important;
}
.date-dropdown {
  width: 260px !important;
}

.date-clear-btn {
  width: 100% !important;
  margin-top: 10px;
  display: block;
}
.detail-body,
.cards-area,
.cards-grid {
  overflow: visible !important;
}

/* ===========================
   MOBILE TOPBAR
   =========================== */

.mobile-topbar {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 52px;
  background: #2d294d;
  z-index: 1000;
  align-items: center;
  padding: 0 14px;
  gap: 10px;
}

.mobile-menu-btn,
.mobile-chat-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 9px;
  background: rgba(255,255,255,0.12);
  color: #fff;
  font-size: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  position: relative;
}

.mobile-chat-btn.active { background: #6161ff; }
.mobile-order-name {
  flex: 1;
  color: #fff;
  font-size: 13px;
  font-weight: 800;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.mobile-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  z-index: 998;
}

.order-search-box{
  margin:10px;
  padding:10px;
  border-radius:8px;
  background:#2a2d44;
  display:flex;
  align-items:center;
  gap:8px;
}

.order-search-box i{
  color:#fff;
}

.order-search-box input{
  width:100%;
  border:none;
  outline:none;
  background:transparent;
  color:#fff;
}

/*===========================
  LAYOUT
  ===========================*/

.orders-layout {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: #1a1d2e;
}

/* LEFT PANEL */
.orders-left{
  position: relative;
  width: 320px;
  min-width: 240px;
  max-width: 650px;
  background: #1a1d2e;
  display: flex;
  flex-direction: column;
  border-right: 1px solid rgba(255,255,255,0.08);
  overflow-y: auto;
  overflow-x: hidden !important;
  flex-shrink: 0;
}
.orders-left{
  scrollbar-width: none !important;
  -ms-overflow-style: none !important;
}

.orders-left::-webkit-scrollbar{
  display: none !important;
}
.resize-bar{
  position:fixed;
  top:50%;
  transform:translate(-20%,-50%);
  z-index:99999;
  width:28px;
  height:80px;
  cursor:col-resize;
}

.resize-handle{
  width:22px;
  height:90px;
  background:#000000;
  border-radius:0 14px 14px 0;
  display:flex;
  align-items:center;
  justify-content:center;
}

.resize-handle i{
  color:#fff;
}


.orders-left-header {
  padding: 16px 16px 8px;
  font-size: 15px;
  font-weight: 800;
  color: #fff;
  display: flex;
  align-items: center;
}

.back-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  background: rgba(255,255,255,0.08);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 8px;
  cursor: pointer;
  transition: all .15s;
  flex-shrink: 0;
}

.back-btn:hover { background: #fff; color: #000; }
.orders-title { flex: 1; }

.order-notify-pill {
  background: #fff;
  color: #000;
  border-radius: 999px;
  padding: 4px 8px;
  font-size: 10px;
  font-weight: 900;
  margin-left: auto;
}

.orders-tabs {
  display: flex;
  padding: 0 10px;
  gap: 2px;
  margin-bottom: 6px;
  flex-wrap: wrap;
}

.orders-tab {
  padding: 7px 10px;
  border: none;
  background: transparent;
  color: #8d93a8;
  font-size: 12px;
  font-weight: 600;
  border-radius: 6px 6px 0 0;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.15s;
  white-space: nowrap;
}

.orders-tab.active { color: #fff; border-bottom-color: #6161ff; }
.orders-tab:hover { color: #c5cae0; }

.list-head {
  display: grid;
grid-template-columns: 32px 1fr 118px 38px;
  padding: 6px 0;
  font-size: 11px;
  font-weight: 700;
  color: #5c6180;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.list-head > div { padding: 0 6px; }

.list-row {
  display: grid;
grid-template-columns: 32px 1fr 118px 38px;
  align-items: center;
  min-height: 44px;
  cursor: pointer;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  transition: background 0.12s;
}

.list-row:hover { background: rgba(255,255,255,0.04); }
.list-row.active{
  background:#ffffff !important;
  border-left:5px solid #6161ff !important;
}

.list-row.active.seen,
.list-row.active.unread{
  background:#ffffff !important;
}

.list-row.active .col-task,
.list-row.active .col-task span,
.list-row.active .row-arrow{
  color:#000 !important;
  font-weight:900 !important;
}

.list-row.active .order-dots-btn{
  opacity:1;
  color:#000;
}
.list-row.unread { background: rgba(255,255,255,0.075); border-left: 4px solid #fff; }
.list-row.seen {
  background: rgba(255,255,255,0.02);
  opacity: 1;
}

.list-row.seen .col-task span:not(.unread-dot),
.list-row.unread .col-task span:not(.unread-dot),
.list-row .col-task span:not(.unread-dot) {
  color: #ffffff;
  font-weight: 900;
}
.list-row.unread .col-task span:not(.unread-dot) { color: #fff; font-weight: 900; }
.list-row.seen .col-task span {
  color: #c5cae0;
  font-weight: 800;
}
.list-row.seen:hover { opacity: 1; }
.list-row .col-task span:not(.unread-dot) {
  font-weight: 800;
}
.unread-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #fff;
  flex-shrink: 0;
  box-shadow: 0 0 0 3px rgba(255,255,255,.08);
}

.list-row > div { padding: 6px; }
.col-chk { display: flex; align-items: center; justify-content: center; }

.col-task {
  font-size: 12px;
  font-weight: 600;
  color: #c5cae0;
  display: flex;
  align-items: center;
  gap: 6px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.row-arrow { font-size: 10px; color: #5c6180; flex-shrink: 0; }

.avatar-stack { display: flex; gap: 2px; }

.av {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px solid #1a1d2e;
}

.av-count { background: #3a3d50; font-size: 9px; }

.order-actions-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.order-dots-btn {
  opacity: 0;
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 8px;
  background: rgba(255,255,255,0.08);
  color: #c5cae0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.list-row:hover .order-dots-btn,
.order-dots-btn:hover { opacity: 1; background: rgba(255,255,255,0.14); }

.order-menu {
  position: absolute;
  right: 6px;
  top: 32px;
  width: 155px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  box-shadow: 0 14px 35px rgba(0,0,0,0.18);
  z-index: 99999;
  overflow: hidden;
}

.order-menu-item {
  padding: 10px 12px;
  font-size: 13px;
  font-weight: 600;
  color: #172b4d;
  display: flex;
  gap: 8px;
  align-items: center;
  cursor: pointer;
}

.order-menu-item:hover { background: #f5f6fb; }
.order-menu-item.danger { color: #e2445c; }

.add-row {
  padding: 12px 16px;
  font-size: 13px;
  font-weight: 600;
  color: #6161ff;
  cursor: pointer;
  border-top: 1px solid rgba(255,255,255,0.06);
  margin-top: 4px;
  transition: all 0.15s;
  display: flex;
  align-items: center;
}

.add-row:hover { background: rgba(97,97,255,0.1); color: #fff; }
.orders-loading, .orders-empty-list { padding: 18px 16px; color: #8d93a8; font-size: 13px; }

/* ===========================
   RIGHT PANEL
   =========================== */
.orders-right {
  flex: 1;
  background: #f6f7fb;
  display: flex;
  flex-direction: column;
  overflow: visible;
  min-width: 0;
}

.detail-header {
  position: relative;
  height: 62px;
  background: #2d294d;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-left-p { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); }
.left-p-logo { width: 40px; height: auto; object-fit: contain; }

.header-center-logo { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); }
.prosix-main-logo { width: 150px; height: auto; object-fit: contain; }

.header-right-icons {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  gap: 10px;
}
.current-order-title{
  position:absolute;
  right:19px;
  top:50%;
  transform:translateY(-50%);

  background:#fff;
  color:#000;

  border:1px solid #d8d5ff;
  border-radius:8px;

  padding:8px 14px;

  font-size:13px;
  font-weight:900;

  display:flex;
  align-items:center;
  gap:8px;

  max-width:450px;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;

  z-index:10;
}

.current-order-title i{
  color:#6161ff;
}
.header-icon-btn {
  position: relative;
  width: 36px;
  height: 36px;
  border: 1.5px solid #e5e7eb;
  background: #fff;
  border-radius: 9px;
  color: #6b7280;
  font-size: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
}

.header-icon-btn:hover, .header-icon-btn.active { background: #6161ff; border-color: #6161ff; color: #fff; }

.chat-badge {
  position: absolute;
  top: -7px;
  right: -7px;
  min-width: 18px;
  height: 18px;
  background: #ff3d71;
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  border-radius: 50%;
  padding: 0 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid #fff;
}

.user-avatar-top {
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 50%;
  background: #ff3d71;
  color: #fff;
  font-size: 13px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  overflow: hidden;
  padding: 0;
}

/* INFO BAR WRAPPER — horizontal scroll on small screens */
.detail-topbar-wrapper {
  overflow-x: auto;
  flex-shrink: 0;
  -webkit-overflow-scrolling: touch;
  padding: 10px 16px;
}

.detail-topbar {
  display: flex;
  align-items: center;
  background: #fff;
  flex-wrap: nowrap;
}

.table-border {
  border: 1px solid #d8d5ff;
  border-radius: 6px;
  overflow: visible;
  width: max-content;
  min-width: 100%;
  display: flex;
  flex-wrap: nowrap;
}

.detail-info-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-right: 1px solid #d8d5ff;
  background: #fff;
  min-height: 40px;
  white-space: nowrap;
  flex-shrink: 0;
}

.detail-info-item:last-child { border-right: none; }
.detail-info-item:hover { background: #f5f2ff; }

.info-label { font-size: 11px; font-weight: 700; color: #6b7280; white-space: nowrap; }
.info-value { font-size: 12px; font-weight: 800; color: #172b4d; }
.date-clickable { cursor: pointer; transition: color 0.15s; }
.date-clickable:hover { color: #383838; }

.status-badge {
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  border-radius: 6px;
  padding: 4px 10px;
  cursor: pointer;
  user-select: none;
  transition: opacity 0.15s;
  white-space: nowrap;
}

.status-badge:hover { opacity: 0.85; }

.status-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  min-width: 210px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 12px 32px rgba(0,0,0,0.15);
  z-index: 99999;
  overflow: hidden;
}

.status-drop-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  color: #172b4d;
  transition: background 0.12s;
}

.status-drop-item:hover { background: #f8f9fc; }
.status-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.status-group-tag { margin-left: auto; font-size: 11px; color: #9aa0b8; font-weight: 500; }

.date-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 220px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 12px 32px rgba(0,0,0,0.15);
  z-index: 99999;
  padding: 14px;
}

.date-dropdown-header { font-size: 13px; font-weight: 700; color: #172b4d; margin-bottom: 10px; }

.date-input {
  width: 100%;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  padding: 7px 10px;
  font-size: 13px;
  color: #172b4d;
  outline: none;
  margin-bottom: 8px;
}

.date-input:focus { border-color: #000; }

.date-clear-btn {
  width: 100%;
  background: #f3f4f6;
  border: none;
  border-radius: 8px;
  padding: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
}

.date-clear-btn:hover { background: #e5e7eb; }

.trk-badge { background: #e8f3ff; color: #0073ea; font-size: 11px; font-weight: 700; border-radius: 6px; padding: 4px 10px; white-space: nowrap; }

.payment-badge {
  background: #e8fff3;
  color: #00c875;
  font-size: 11px;
  font-weight: 700;
  border-radius: 6px;
  padding: 4px 10px;
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
  transition: opacity 0.15s;
}

.payment-badge:hover { opacity: 0.85; }

.payment-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 250px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  box-shadow: 0 12px 40px rgba(0,0,0,0.15);
  z-index: 99999;
  overflow: hidden;
}

.payment-dropdown-header { padding: 12px 16px 8px; font-size: 14px; font-weight: 800; color: #172b4d; border-bottom: 1px solid #f0f1f3; }

.payment-field { padding: 8px 16px 0; }

.payment-label {
  font-size: 12px;
  font-weight: 700;
  color: #6b7280;
  display: flex;
  align-items: center;
  margin-bottom: 5px;
}

.payment-percent-row { display: flex; align-items: center; gap: 6px; }

.payment-input {
  width: 100%;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  padding: 7px 10px;
  font-size: 13px;
  color: #172b4d;
  outline: none;
  transition: border-color 0.15s;
}

.payment-input:focus { border-color: #6161ff; }
.percent-sign { font-size: 14px; font-weight: 700; color: #6b7280; flex-shrink: 0; }

.payment-save-btn {
  display: block;
  width: calc(100% - 32px);
  margin: 10px 16px 12px;
  background: #6161ff;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s;
}

.payment-save-btn:hover { background: #4f4fd4; }

/* BODY */
.detail-body {
  display: flex;
  flex: 1;
  overflow: hidden;
}

/* CARDS */
.cards-area { flex: 1; overflow-y: auto; padding: 12px; }

.cards-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

.order-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  display: flex;
  flex-direction: column;
}

.card-preview-area {
  min-height: 120px;
  max-height: 120px;
  background: #f8f9fc;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.card-empty-preview { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; }
.card-bg-icon { font-size: 34px; color: #d1d5db; }
.card-thumbnail-img { width: 20%; height: 120px; object-fit: cover; }

.card-notes-area {
  display: flex;
  flex-direction: column;
  min-height: 185px;
  background: #fffef0;
}

.notes-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: #cacaca;
  border-bottom: 1px solid #cacaca;
  font-size: 13px;
  font-weight: 700;
  color: #92400e;
}

.notes-icon { color: #000; }

.notes-textarea {
  flex: 1;
  border: none;
  outline: none;
  resize: none;
  padding: 10px 12px;
  font-size: 13px;
  color: #374151;
  background: #fff;
  line-height: 1.6;
  min-height: 90px;
  font-family: inherit;
}

.notes-textarea::placeholder { color: #d1d5db; }

.notes-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 10px;
  border-top: 1px solid #cacaca;
  background: #cacaca;
}

.notes-count { font-size: 11px; color: #9ca3af; }
.notes-saved-msg { font-size: 12px; color: #00c875; font-weight: 700; }

.notes-save-btn {
  background: #000;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.notes-save-btn:hover { background: #3f3f3f; }

.card-files-preview { display: flex; flex-wrap: wrap; gap: 6px; padding: 8px; align-items: flex-start; width: 100%; }

.file-thumb {
  position: relative;
  width: 72px;
  height: 72px;
  border-radius: 8px;
  overflow: hidden;
  background: #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 4px;
  border: 1px solid #d6dae5;
}

.file-img { width: 100%; height: 100%; object-fit: cover; background: #f3f4f6; }

.file-icon-box { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 6px; }
.file-type-icon { font-size: 24px; color: #6b7280; }
.file-name-small { font-size: 9px; color: #6b7280; text-align: center; word-break: break-all; max-width: 66px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.file-remove-btn {
  position: absolute;
  top: 3px;
  right: 3px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: rgba(0,0,0,0.5);
  border: none;
  color: #fff;
  font-size: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.15s;
}

.file-thumb:hover .file-remove-btn { opacity: 1; }

.card-footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 10px;
  border-top: 1px solid #f0f1f3;
  background: #fff;
  gap: 4px;
  flex-wrap: wrap;
}

.card-footer-left { display: flex; align-items: center; gap: 4px; }

.card-add-btn {
  border: none;
  background: transparent;
  color: #9aa0b8;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 3px;
}

.card-add-btn:hover { color: #6161ff; }
.hidden-file-input { display: none; }

.card-title { font-size: 12px; font-weight: 700; color: #172b4d; }

.card-view-btn {
  display: flex;
  align-items: center;
  gap: 3px;
  border: 1px solid #e5e7eb;
  background: #f8f9fc;
  color: #6b7280;
  font-size: 10px;
  font-weight: 600;
  border-radius: 6px;
  padding: 3px 7px;
  cursor: pointer;
  transition: all 0.15s;
}

.card-view-btn:hover { background: #6161ff; border-color: #6161ff; color: #fff; }

/* MODALS */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999999;
  padding: 16px;
}

.add-order-modal, .view-all-modal {
  background: #fff;
  width: 520px;
  max-width: 100%;
  border-radius: 18px;
  overflow: visible;
  box-shadow: 0 40px 100px rgba(0,0,0,0.25);
  display: flex;
  flex-direction: column;
}

.view-all-modal { width: 720px; max-width: 100%; max-height: 85vh; }

.view-all-header { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; gap: 8px; }
.view-all-header h5 { margin: 0; font-weight: 800; color: #172b4d; }

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
}

.modal-close:hover { background: #e5e7eb; }

.add-order-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; max-height: 70vh; overflow-y: auto; }

.field-group { display: flex; flex-direction: column; gap: 5px; }
.field-group label { font-size: 13px; font-weight: 700; color: #374151; }
.req { color: #ff3d71; }

.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.field-input {
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 11px;
  font-size: 13px;
  color: #172b4d;
  outline: none;
  transition: border-color 0.15s;
  font-family: inherit;
  background: #fff;
}

.field-input:focus { border-color: #6161ff; }

.add-order-footer { display: flex; gap: 10px; justify-content: flex-end; padding: 14px 20px; border-top: 1px solid #e5e7eb; background: #f9fafb; }

.btn-cancel {
  border: 1px solid #e5e7eb;
  background: #fff;
  color: #6b7280;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.btn-cancel:hover { background: #f3f4f6; }

.btn-create {
  background: #6161ff;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 18px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-create:hover:not(:disabled) { background: #4f4fd4; }
.btn-create:disabled { opacity: 0.5; cursor: not-allowed; }

.view-all-body { padding: 16px; overflow-y: auto; }

.view-all-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 12px; }

.view-file-item { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px; background: #fff; }

.view-file-img { width: 100%; height: 110px; object-fit: cover; border-radius: 8px; background: #f3f4f6; }

.view-file-doc { width: 100%; height: 110px; display: flex; align-items: center; justify-content: center; background: #f3f4f6; border-radius: 8px; }
.view-file-icon { font-size: 34px; color: #6b7280; }
.view-file-name { display: block; margin-top: 7px; font-size: 11px; font-weight: 600; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.view-file-actions { display: flex; justify-content: flex-end; gap: 6px; margin-top: 7px; }

.vf-btn { width: 28px; height: 28px; border-radius: 7px; border: none; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; text-decoration: none; }
.download-btn { background: #e8f3ff; color: #0073ea; }
.remove-btn { background: #fff0f0; color: #e2445c; }

.view-all-empty { text-align: center; padding: 36px 0; color: #9aa0b8; }

.upload-btn-big {
  background: #6161ff;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 14px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

/* MULTISELECT */
:deep(.multiselect) { min-height: 38px; }
:deep(.multiselect__tags) { border: 1.5px solid #e5e7eb; border-radius: 8px; min-height: 38px; padding: 6px 40px 0 8px; }
:deep(.multiselect__content-wrapper) { z-index: 9999999; }
:deep(.multiselect__tag) { background: #6161ff; }
:deep(.multiselect__tag-icon::after) { color: #fff; }

.notes-textarea[readonly] { background: #f7f7f7; cursor: not-allowed; }

.view-all-head-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

.upload-small-btn {
  border: none;
  background: #6161ff;
  color: #fff;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.upload-small-btn:hover { background: #4f4fd4; }

.uploading-label {
  position: absolute;
  left: 4px;
  right: 4px;
  bottom: 4px;
  background: rgba(0,0,0,0.68);
  color: #fff;
  font-size: 9px;
  font-weight: 700;
  text-align: center;
  border-radius: 5px;
  padding: 2px 3px;
  z-index: 3;
}

.avatar-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: block; }
.av.has-photo { overflow: hidden; border: 2px solid #fff; }

.profile-modal {
  width: 360px;
  max-width: 100%;
  background: #fff;
  border-radius: 18px;
  padding: 20px;
  position: relative;
  box-shadow: 0 30px 90px rgba(0,0,0,.25);
}

.profile-close {
  position: absolute;
  right: 12px;
  top: 12px;
  width: 28px;
  height: 28px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.profile-close:hover { background: #e5e7eb; }

.profile-photo-wrap { display: flex; justify-content: center; margin-top: 10px; }

.profile-photo, .profile-photo-empty { width: 84px; height: 84px; border-radius: 50%; object-fit: cover; }

.profile-photo-empty {
  background: #6161ff;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  font-weight: 900;
}

.profile-field { margin-top: 10px; }
.profile-field label { font-size: 12px; font-weight: 800; color: #6b7280; margin-bottom: 4px; display: block; }
.profile-field input[readonly], .profile-field textarea[readonly] { background: #f8f9fc; cursor: not-allowed; }

.payment-dropdown-wide { width: 270px; }

.payment-read-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 14px;
  border-radius: 10px;
  margin: 6px 10px;
  font-size: 12px;
  color: #6b7280;
}

.payment-read-row strong { color: #172b4d; font-size: 13px; }
.payment-read-row.paid-row { background: #eef2ff; }
.payment-read-row.received-row { background: #e8fff3; }
.payment-read-row.balance-row { background: #fff4e5; }

.payment-admin-editor { border-top: 1px solid #e5e7eb; margin-top: 4px; padding-top: 2px; }

.trk-clickable { display: inline-flex; align-items: center; gap: 6px; cursor: pointer; }
.trk-logo { width: 16px; height: 16px; border-radius: 4px; background: #fff; object-fit: contain; }

.tracking-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 260px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  box-shadow: 0 12px 40px rgba(0,0,0,0.15);
  z-index: 99999;
  overflow: hidden;
}

.tracking-dropdown-header { padding: 12px 16px 8px; font-size: 14px; font-weight: 800; color: #172b4d; border-bottom: 1px solid #f0f1f3; }

.tracking-preview-row { display: flex; align-items: center; gap: 8px; margin: 8px 14px 0; padding: 7px 10px; background: #f8f9fc; border-radius: 8px; font-size: 12px; font-weight: 800; color: #172b4d; }
.tracking-preview-logo { width: 22px; height: 22px; object-fit: contain; border-radius: 5px; }

.download-all-btn {
  border: none;
  background: #172b4d;
  color: #fff;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.download-all-btn:hover { background: #0f1d35; color: #fff; }
.clickable-file-preview { cursor: pointer; }

.image-preview-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.72);
  z-index: 999999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.image-preview-modal {
  position: relative;
  max-width: 96vw;
  max-height: 92vh;
  background: #fff;
  border-radius: 14px;
  padding: 12px;
  box-shadow: 0 30px 90px rgba(0,0,0,.35);
}

.image-preview-close {
  position: absolute;
  right: -12px;
  top: -12px;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: #fff;
  color: #172b4d;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 8px 22px rgba(0,0,0,.25);
}

.image-preview-full { max-width: 88vw; max-height: 84vh; object-fit: contain; border-radius: 10px; display: block; }

.file-preview-doc {
  width: 320px;
  max-width: 84vw;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #172b4d;
  text-align: center;
}

.file-preview-doc i { font-size: 52px; color: #6161ff; }
.mt-3 { margin-top: 12px; }

.custom-status-box {
  padding: 10px;
  border-top: 1px solid #eef0f6;
  display: flex;
  align-items: center;
  gap: 7px;
  background: #fafbff;
}

.custom-status-input {
  flex: 1;
  min-width: 100px;
  border: 1px solid #d9ddeb;
  border-radius: 8px;
  padding: 7px 8px;
  font-size: 12px;
  outline: none;
}

.custom-status-input:focus { border-color: #6161ff; }

.custom-status-color {
  width: 30px;
  height: 30px;
  border: none;
  border-radius: 50%;
  padding: 0;
  background: transparent;
  cursor: pointer;
}

.custom-status-color::-webkit-color-swatch-wrapper { padding: 0; }
.custom-status-color::-webkit-color-swatch { border: none; border-radius: 50%; }

.custom-status-btn {
  border: none;
  background: #6161ff;
  color: #fff;
  border-radius: 8px;
  padding: 7px 10px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.payment-summary-badge { display: inline-flex; align-items: center; gap: 4px; background: transparent !important; padding: 0 !important; }

.payment-chip {
  display: inline-flex;
  align-items: center;
  padding: 4px 7px;
  border-radius: 7px;
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
}

.payment-chip-paid { background: #e9f3ff; color: #0073ea; }
.payment-chip-received { background: #e8fff3; color: #00a86b; }
.payment-chip-balance { background: #fff4e5; color: #d97706; }

.invoice-info-item { padding: 6px 10px !important; gap: 6px; }

.invoice-btn {
  border: none;
  background: #111827;
  color: #fff;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  white-space: nowrap;
}

.invoice-btn:hover { background: #374151; }

.invoice-view-btn {
  border: none;
  background: #eef2ff;
  color: #4f46e5;
  border-radius: 8px;
  padding: 5px 8px;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  white-space: nowrap;
}

.invoice-view-btn:hover { background: #e0e7ff; }

.status-color-picker { padding: 0; border: none; outline: none; cursor: pointer; appearance: none; -webkit-appearance: none; background: transparent; }
.status-color-picker::-webkit-color-swatch-wrapper { padding: 0; }
.status-color-picker::-webkit-color-swatch { border: none; border-radius: 50%; }

.member-select-actions { display: flex; gap: 8px; margin-bottom: 8px; }

.select-all-members-btn {
  border: none;
  background: #6161ff;
  color: #fff;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.select-all-members-btn.clear { background: #f1f2f6; color: #4b5563; }

.order-info-modal { width: 460px; max-width: 100%; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 30px 90px rgba(0,0,0,.35); }

.order-info-body { padding: 16px; }

.order-info-title { display: flex; flex-direction: column; gap: 4px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 12px; background: #f9fafb; margin-bottom: 12px; }
.order-info-title strong { color: #111; font-size: 14px; }
.order-info-title span { color: #6b7280; font-size: 12px; font-weight: 700; }

.read-info-section h6 { margin: 0 0 8px; font-size: 14px; font-weight: 900; color: #111; }
.read-info-list { display: flex; flex-direction: column; gap: 8px; max-height: 300px; overflow: auto; }

.read-info-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; border: 1px solid #eef0f4; border-radius: 12px; padding: 9px; flex-wrap: wrap; }

.read-user { display: flex; align-items: center; gap: 10px; min-width: 0; }

.read-user-avatar { width: 32px; height: 32px; border-radius: 50%; background: #000; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 900; flex-shrink: 0; }

.read-user strong { display: block; color: #111; font-size: 13px; font-weight: 900; }
.read-user small { display: block; color: #6b7280; font-size: 11px; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.read-time { color: #111; background: #f3f4f6; border-radius: 999px; padding: 4px 8px; font-size: 11px; font-weight: 800; white-space: nowrap; }

.read-info-empty { color: #6b7280; font-size: 13px; padding: 16px; text-align: center; border: 1px dashed #d1d5db; border-radius: 12px; }
.bulk-actions {
  display: flex;
  align-items: center;
  gap: 7px;
  background: #1f2235;
  padding: 8px 10px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  flex-wrap: nowrap;
}

.bulk-actions strong {
  color: #fff;
  font-size: 11px;
  font-weight: 900;
  white-space: nowrap;
}

.bulk-btn {
  border: none;
  background: #6161ff;
  color: #fff;
  border-radius: 7px;
  padding: 6px 8px;
  font-size: 10px;
  font-weight: 900;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

.bulk-btn.danger {
  background: #e2445c;
}

.bulk-btn:disabled {
  opacity: .6;
  cursor: not-allowed;
}
/* ===========================
   RESPONSIVE — TABLET (768px)
   =========================== */
@media (max-width: 900px) {
  .cards-grid { grid-template-columns: 1fr 1fr; }
  .prosix-main-logo { width: 120px; }
}

/* ===========================
   RESPONSIVE — MOBILE (< 768px)
   =========================== */
@media (max-width: 767px) {

  /* Show mobile topbar, hide desktop header in right panel */
  .mobile-topbar { display: flex; }
  .mobile-overlay { display: block; }

  /* Hide desktop header from right panel on mobile */
  .detail-header { display: none; }

  .orders-layout {
    flex-direction: column;
    position: relative;
    padding-top: 52px; /* topbar height */
  }

  /* Left panel: slide in from left as drawer */
  .orders-left {
    position: fixed;
    top: 52px;
    left: 0;
    bottom: 0;
    width: 82vw !important;
    max-width: 320px;
    z-index: 999;
    transform: translateX(-100%);
    transition: transform 0.25s ease;
    box-shadow: 4px 0 30px rgba(0,0,0,0.25);
  }

  .orders-layout.left-open .orders-left {
    transform: translateX(0);
  }

  /* Resize bar hidden on mobile */
  .resize-bar { display: none; }

  /* Right panel: full width */
 /* Right panel: full width */
.orders-right {
  width: 100%;
  flex: 1;
  height: calc(100vh - 52px);
  overflow-x: hidden;
  overflow-y: auto;
}

  /* Info bar: scroll horizontally */
/* Info bar: scroll horizontally */
.detail-topbar-wrapper {
  padding: 0 !important;
  overflow-x: hidden !important;
  overflow-y: visible !important;
}
.detail-topbar {
  flex-wrap: wrap !important;
  width: 100% !important;
  min-width: 100% !important;
  border-radius: 0 !important;
}

.detail-info-item {
  flex-shrink: 0 !important;
  white-space: nowrap !important;
  flex: 1 1 45% !important;
  border-right: 1px solid #d8d5ff !important;
  border-bottom: 1px solid #d8d5ff !important;
  min-width: 0 !important;
  justify-content: center !important;
}
  /* Cards: 1 column on very small, 2 on medium */
  .cards-grid { grid-template-columns: 1fr; gap: 8px; }
  .cards-area { padding: 8px; }

  /* Chat panel: full screen overlay on mobile */
  .orders-layout.chat-open .orders-right .detail-body {
    flex-direction: column;
  }

  /* Field row: 1 column on mobile */
  .field-row { grid-template-columns: 1fr; }

  /* Modals: full width on mobile */
  .add-order-modal, .view-all-modal, .order-info-modal { border-radius: 14px 14px 0 0; align-self: flex-end; max-height: 90vh; }
  .modal-overlay { align-items: flex-end; padding: 0; }

  .profile-modal { border-radius: 14px; }

  /* Payment chips: smaller */
  .payment-chip { font-size: 10px; padding: 3px 6px; }

  /* Status dropdown: fix position on mobile */
  .status-dropdown, .date-dropdown, .tracking-dropdown { position: fixed; top: auto; left: 10px; right: 10px; width: auto; bottom: 10px; }

  .payment-dropdown { position: fixed; bottom: 10px; left: 10px; right: 10px; width: auto; top: auto; }

  .order-dots-btn { opacity: 1; }

  /* View all grid: 2 cols */
  .view-all-grid { grid-template-columns: repeat(2, 1fr); }

  .me-1 { margin-right: 4px; }
}

/* Small mobile */
@media (max-width: 480px) {
  .cards-grid { grid-template-columns: 1fr; }
  .payment-chip-received, .payment-chip-balance { display: none; }
  .left-p-logo { width: 28px; }
}

.me-1 { margin-right: 4px; }
</style>
