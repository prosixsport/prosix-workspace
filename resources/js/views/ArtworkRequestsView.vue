<template>
  <AppLayout>
  <div
    class="factory-board-page"
    :class="`theme-${boardTheme}`"
    @click="closeAllMenus"
  >
    <!-- REUSABLE PAGE HEADER -->
 <PageHeader
  title="Factory Order Management"
  subtitle="Track production, manage orders and keep your workflow organized."
  :user="currentUser"
  :photo="currentUser?.profile_photo_url"
  @profile="openProfile"
/>

    <!-- STATUS NAVIGATION -->
    <section class="board-toolbar">
      <button
        type="button"
        class="summary-home-card"
        :class="{ active: activeGroup === 'all' }"
        @click="
          activeGroup = 'all';
          activeSectionCollapsed = false
        "
      >
        <span class="summary-home-icon">
          <i class="fa-solid fa-house"></i>
        </span>

        <span>
          <small>To Open</small>
          <strong>{{ unreadOrdersCount }}</strong>
        </span>
      </button>

      <div class="workflow-tabs">
        <div
          v-for="group in boardGroups"
          :key="group.key"
          class="workflow-tab-wrap"
        >
          <button
            type="button"
            class="workflow-tab"
            :class="{ active: activeGroup === group.key }"
            :style="{
              '--group-color': group.color,
              background: softColor(group.color, 0.12),
              color: group.color
            }"
            @click="
              activeGroup = group.key;
              activeSectionCollapsed = false
            "
          >
            <span class="workflow-tab-label">{{ group.label }}</span>

            <span
              class="workflow-total-box"
              :style="{
                background: softColor(group.color, 0.18),
                color: group.color
              }"
            >
              {{ countForGroup(group.key) }}
            </span>
          </button>

          <div
            class="workflow-custom-actions"
            @click.stop
          >
            <label
              class="workflow-color-action"
              title="Change section color"
            >
              <i class="fa-solid fa-palette"></i>

              <input
                type="color"
                :value="group.color"
                @input="changeWorkflowGroupColor(group, $event.target.value)"
              />
            </label>

            <button
              type="button"
              title="Edit section name"
              @click="editWorkflowGroup(group)"
            >
              <i class="fa-solid fa-pen"></i>
            </button>

            <button
              type="button"
              class="danger"
              title="Delete section"
              @click="deleteWorkflowGroup(group)"
            >
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>

        <button
          v-if="canCreateOrder"
          type="button"
          class="workflow-add-button"
          title="Add another order section"
          @click="addWorkflowGroup"
        >
          <i class="fa-solid fa-plus"></i>
        </button>
      </div>

      <div class="board-toolbar-actions">
        <!-- ALL CHAT NOTIFICATIONS -->
        <div class="chat-notification-wrap" @click.stop>
          <button
            type="button"
            class="chat-notification-button"
            title="Unread chats"
            @click="showChatNotificationMenu = !showChatNotificationMenu"
          >
            <i class="fa-solid fa-bell"></i>
            <span v-if="totalUnreadChatCount > 0" class="chat-notification-count">
              {{ totalUnreadChatCount }}
            </span>
          </button>

          <div
            v-if="showChatNotificationMenu"
            class="chat-notification-dropdown"
          >
            <div class="chat-notification-head">
              <strong>Unread Chats</strong>
              <span>{{ totalUnreadChatCount }} total</span>
            </div>

            <button
              v-for="order in unreadChatOrders"
              :key="'chat-notification-' + order.id"
              type="button"
              class="chat-notification-item"
              @click="openChatFromNotification(order)"
            >
              <span class="chat-notification-icon">
                <i class="fa-solid fa-comments"></i>
              </span>

              <span class="chat-notification-content">
                <strong>{{ order.name }}</strong>
                <small>
                  {{ order.last_message_sender || 'New message' }}
                  <template v-if="order.last_message_text">
                    · {{ shortLastMessage(order.last_message_text) }}
                  </template>
                </small>
              </span>

              <span class="chat-notification-badge">
                {{ order.unread_chat_count }}
              </span>
            </button>

            <div
              v-if="unreadChatOrders.length === 0"
              class="chat-notification-empty"
            >
              <i class="fa-regular fa-bell-slash"></i>
              No unread chats
            </div>
          </div>
        </div>

        <button
          type="button"
          class="theme-toggle-button"
          :title="boardTheme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'"
          @click.stop="toggleBoardTheme"
        >
          <i
            :class="boardTheme === 'light'
              ? 'fa-solid fa-moon'
              : 'fa-solid fa-sun'"
          ></i>

          <span>
            {{ boardTheme === 'light' ? 'Dark' : 'Light' }}
          </span>
        </button>

        <div class="board-search">
          <i class="fa-solid fa-magnifying-glass"></i>
        <input
          v-model="searchOrder"
          type="text"
          placeholder="Search orders..."
          @click.stop
        />
        </div>
      </div>
    </section>

    <!-- BOARD -->
    <main class="factory-board">
      <section
        class="board-section-heading collapsible-active-heading"
        :class="{ collapsed: activeSectionCollapsed }"
        :style="{
          '--active-section-color': activeBoardGroup.color,
          background: '#ffffff',
          color: activeBoardGroup.color,
          borderLeftColor: activeBoardGroup.color
        }"
        @click="activeSectionCollapsed = !activeSectionCollapsed"
      >
        <div>
          <span class="section-chevron-slot">
            <i
              class="fa-solid section-collapse-icon"
              :class="
                activeSectionCollapsed
                  ? 'fa-chevron-right'
                  : 'fa-chevron-down'
              "
            ></i>
          </span>

          <div class="active-section-title-wrap">
            <h1>{{ activeBoardGroup.label }}</h1>

            <span class="active-section-meta">
              {{ unreadOrdersCount }} TO OPEN
              · {{ filteredOrders.length }} TOTAL
            </span>
          </div>
        </div>

        <div class="board-heading-actions" @click.stop>
          <button
            v-if="canCreateOrder"
            type="button"
            class="board-top-add-button"
            @click="startInlineOrder"
          >
            <i class="fa-solid fa-plus"></i>
            Add Order
          </button>

          <button
            type="button"
            class="board-print-button"
            :style="{ color: readableTextColor(activeBoardGroup.color) }"
            title="Print"
            @click="printVisibleOrders"
          >
            <i class="fa-solid fa-print"></i>
            <small>PRINT</small>
          </button>
        </div>
      </section>


      <section
        v-if="selectedOrders.length > 0"
        class="board-bulk-toolbar"
        @click.stop
      >
        <div class="bulk-selected-count">
          <strong>{{ selectedOrders.length }}</strong>
          <span>Selected</span>
        </div>

        <button
          v-if="hasFullOrderAccess || currentUser?.can_create_orders === true"
          type="button"
          @click="openBulkMembersModal"
        >
          <i class="fa-solid fa-users"></i>
          Add Members
        </button>

        <button
          v-if="hasFullOrderAccess || currentUser?.can_create_orders === true"
          type="button"
          @click="openBulkClientsModal"
        >
          <i class="fa-solid fa-user-tie"></i>
          Add Client
        </button>

        <button
          v-if="hasFullOrderAccess || currentUser?.can_create_orders === true"
          type="button"
          @click="bulkDuplicateOrders"
        >
          <i class="fa-solid fa-copy"></i>
          Duplicate
        </button>

        <button
          v-if="hasFullOrderAccess || currentUser?.can_create_orders === true"
          type="button"
          class="danger"
          @click="bulkDeleteOrders"
        >
          <i class="fa-solid fa-trash"></i>
          Delete
        </button>

        <button
          type="button"
          class="clear"
          @click="clearBulkSelection"
        >
          <i class="fa-solid fa-xmark"></i>
          Clear
        </button>
      </section>

      <section
        v-show="!activeSectionCollapsed"
        class="board-table-shell"
      >
        <div class="board-table-head" :style="boardGridStyle">
          <div class="board-col board-col-check resizable-head-cell">
            <input
              type="checkbox"
              v-model="selectAll"
              @change="toggleSelectAll"
              @click.stop
            />
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('check', $event)"
            ></span>
          </div>

          <div class="board-col board-col-name resizable-head-cell">
            ORDER NAME
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('name', $event)"
            ></span>
          </div>

          <div class="board-col board-col-status resizable-head-cell">
            STATUS
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('status', $event)"
            ></span>
          </div>

          <div class="board-col board-col-owner resizable-head-cell">
            OWNER
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('owner', $event)"
            ></span>
          </div>

          <div class="board-col board-col-files resizable-head-cell">
            FILES
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('files', $event)"
            ></span>
          </div>

          <div class="board-col board-col-packing resizable-head-cell">
            PACKING DETAIL
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('packing', $event)"
            ></span>
          </div>

          <div class="board-col board-col-chat resizable-head-cell">
            CHAT
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('chat', $event)"
            ></span>
          </div>

          <div class="board-col board-col-payment resizable-head-cell">
            PAYMENT
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('payment', $event)"
            ></span>
          </div>

          <div class="board-col board-col-address resizable-head-cell">
            ADDRESS
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('address', $event)"
            ></span>
          </div>

          <div class="board-col board-col-track resizable-head-cell">
            TRK#
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('track', $event)"
            ></span>
          </div>

          <div class="board-col board-col-info">
            <i class="fa-regular fa-circle-question"></i>
          </div>
        </div>

        <!-- INLINE ADD ORDER -->
        <div
          v-if="canCreateOrder && inlineAddOpen"
          class="board-inline-add-row board-inline-add-top"
          :style="boardGridStyle"
        >
          <div class="board-col board-col-check"></div>

          <div class="board-col board-inline-add-main">
            <template v-if="inlineAddOpen">
              <input
                ref="inlineOrderInput"
                v-model="inlineOrderName"
                type="text"
                placeholder="WRITE ORDER NAME AND PRESS ENTER"
                @keyup.enter="createInlineOrder"
                @keyup.esc="cancelInlineOrder"
              />

              <button
                type="button"
                class="inline-save-button"
                :disabled="inlineOrderSaving || !inlineOrderName.trim()"
                @click="createInlineOrder"
              >
                <i
                  :class="inlineOrderSaving
                    ? 'fa-solid fa-spinner fa-spin'
                    : 'fa-solid fa-check'"
                ></i>
              </button>

              <button
                type="button"
                class="inline-cancel-button"
                @click="cancelInlineOrder"
              >
                <i class="fa-solid fa-xmark"></i>
              </button>
            </template>
          </div>
        </div>


        <div v-if="loadingOrders" class="board-empty-state">
          <i class="fa-solid fa-spinner fa-spin"></i>
          Loading orders...
        </div>

        <div v-else-if="filteredOrders.length === 0" class="board-empty-state">
          No orders found in this section.
        </div>

        <div
          v-for="order in filteredOrders"
          :key="order.id"
          class="board-table-row"
          :class="{
            unread: !order.user_has_seen,
            opened: order.user_has_seen,
            selected: selectedOrders.includes(order.id)
          }"
          :style="boardGridStyle"
          @click="openBoardOrder(order)"
        >
          <div class="board-col board-col-check">
            <input
              type="checkbox"
              :value="order.id"
              v-model="selectedOrders"
              @click.stop
            />
          </div>

          <div class="board-col board-col-name">
            <span v-if="!order.user_has_seen" class="board-new-dot"></span>

            <div class="inline-cell-wrap">
              <input
                v-if="isEditingCell(order.id, 'name')"
                v-model="inlineEditValue"
                class="board-inline-cell-input"
                type="text"
                @click.stop
                @keyup.enter="saveInlineCell(order, 'name')"
                @keyup.esc="cancelInlineCell"
                @blur="saveInlineCell(order, 'name')"
              />

              <button
                v-else
                type="button"
                class="inline-value-button name-value"
                title="Click to edit order name"
                @click.stop="startInlineCell(order, 'name', order.name)"
              >
                <strong>{{ order.name }}</strong>
                <small>{{ order.po || 'N/A' }}</small>

                <span
                  v-if="workingDesigner(order)"
                  class="working-designer-pill"
                  :title="workingDesigner(order).name + ' is working on this order'"
                >
                  <span class="working-live-dot"></span>

                  <img
                    v-if="workingDesigner(order).profile_photo_url"
                    :src="workingDesigner(order).profile_photo_url"
                    alt=""
                  />

                  <strong>
                    {{ workingDesigner(order).name }}
                  </strong>

                  <small>working</small>
                </span>
              </button>

              <div class="order-working-actions">
                <button
                  v-if="!workingDesigner(order)"
                  type="button"
                  class="start-working-btn"
                  @click.stop="startWorking(order)"
                >
                  <i class="fa-solid fa-play"></i>
                  Start Order
                </button>

                <span
                  v-else
                  class="working-locked-label"
                  :title="workingDesigner(order)?.name + ' started this order'"
                >
                  <i class="fa-solid fa-lock"></i>
                  Started
                </span>
              </div>
            </div>
          </div>

          <div class="board-col board-col-status row-status-cell" @click.stop>
            <div class="status-ref-wrap">
              <button
                type="button"
                class="status-ref-trigger"
                :class="{ open: rowStatusMenuId === Number(order.id) }"
                @click.stop="toggleRowStatusMenu(order, $event)"
              >
                <span
                  class="status-ref-dot"
                  :style="{ background: order.statusColor }"
                ></span>

                <span class="status-ref-label">
                  {{ order.status }}
                </span>

                <i
                  class="fa-solid fa-chevron-down status-ref-chevron"
                  :class="{ rotate: rowStatusMenuId === Number(order.id) }"
                ></i>
              </button>
            </div>
          </div>

          <div class="board-col board-col-owner">
            <div class="board-avatar-stack">
              <button
                v-for="owner in visibleOrderOwners(order)"
                :key="owner.id"
                type="button"
                class="board-avatar"
                :style="{
                  background:
                    owner.profile_photo_url
                      ? '#ffffff'
                      : owner.color
                }"
                :title="`${owner.name} · ${formatOwnerRole(owner.role)}`"
                @click.stop="openProfile(owner)"
              >
                <img
                  v-if="owner.profile_photo_url"
                  :src="owner.profile_photo_url"
                  :alt="owner.name"
                />

                <span v-else>
                  {{ owner.initial }}
                </span>
              </button>

              <button
                v-if="hiddenOrderOwnersCount(order) > 0"
                type="button"
                class="board-avatar board-avatar-more"
                :title="hiddenOrderOwnersNames(order)"
                @click.stop="openSingleOrderMembers(order)"
              >
                +{{ hiddenOrderOwnersCount(order) }}
              </button>

              <button
                v-if="
                  hasFullOrderAccess ||
                  currentUser?.can_create_orders === true
                "
                type="button"
                class="board-avatar board-avatar-add"
                title="Add or manage members"
                @click.stop="openSingleOrderMembers(order)"
              >
                <i class="fa-solid fa-plus"></i>
              </button>
            </div>
          </div>

          <div
            class="board-col board-col-files board-files-drop-zone"
            :class="{
              'row-file-drag-active':
                rowFileDragOrderId === Number(order.id)
            }"
            @dragenter.prevent.stop="onRowFileDragEnter(order)"
            @dragover.prevent.stop="onRowFileDragOver($event, order)"
            @dragleave.prevent.stop="onRowFileDragLeave($event, order)"
            @drop.prevent.stop="onRowFileDrop($event, order)"
          >
            <div
              v-if="rowFileDragOrderId === Number(order.id)"
              class="row-file-drop-label"
            >
              Drop files
            </div>

            <div class="board-row-files">
              <button
                v-for="file in rowFiles(order).slice(0, 4)"
                :key="file.id || file.url || file.name"
                type="button"
                class="board-row-file-thumb"
                :title="file.name"
                @click.stop="openRowFile(order, file)"
              >
                <img
                  v-if="file.isImage && !file.imageError"
                  :src="file.url"
                  :alt="file.name"
                  @error="file.imageError = true"
                />

                <i
                  v-else
                  :class="getFileIcon(file.name)"
                ></i>
              </button>

              <span
                v-if="rowFiles(order).length > 4"
                class="board-more-files"
              >
                +{{ rowFiles(order).length - 4 }}
              </span>

              <button
                v-if="canUploadFiles"
                type="button"
                class="board-row-file-add"
                title="Add files"
                @click.stop="triggerRowFileUpload(order)"
              >
                <i class="fa-solid fa-plus"></i>
              </button>
            </div>
          </div>

          <div class="board-col board-col-packing">
            <input
              class="packing-clean-input"
              :value="packingDetailText(order)"
              type="text"
              placeholder="Add packing detail"
              :title="packingDetailText(order) || 'Add packing detail'"
              @click.stop
              @keydown.enter.prevent="savePackingInline(order, $event)"
              @blur="savePackingInline(order, $event, true)"
            />
          </div>

          <div class="board-col board-col-chat">
            <button
              type="button"
              class="board-chat-button"
              @click.stop="openBoardChat(order)"
            >
              <i class="fa-solid fa-comments"></i>

              <span v-if="Number(order.unread_chat_count || 0) > 0">
                {{ order.unread_chat_count }}
              </span>
            </button>
          </div>

          <div class="board-col board-col-payment">
            <input
              class="board-inline-cell-input payment-input-inline"
              :value="order.payment || '0 % Paid'"
              type="text"
              title="Click and edit payment"
              @click.stop
              @change="saveDirectInlineField(order, 'payment', $event.target.value)"
            />
          </div>

          <div class="board-col board-col-address">
            <input
              class="board-inline-cell-input"
              :value="order.shippingAddress || ''"
              type="text"
              placeholder="Add address"
              title="Click and edit address"
              @click.stop
              @change="saveDirectInlineField(order, 'shipping_address', $event.target.value)"
            />
          </div>

          <div class="board-col board-col-track">
            <input
              class="board-inline-cell-input"
              :value="trackingSummary(order.trk)"
              type="text"
              placeholder="Tracking #"
              title="Click and edit tracking"
              @click.stop
              @change="saveDirectInlineField(order, 'trk', $event.target.value)"
            />
          </div>

          <div class="board-col board-col-info">
            <button
              type="button"
              title="Order details"
              @click.stop="openOrderInfo(order)"
            >
              <i class="fa-regular fa-circle-question"></i>
            </button>
          </div>
        </div>

      </section>

      <!-- COLLAPSED STATUS BARS -->
      <section class="collapsed-status-bars">
        <button
          v-for="group in boardGroups.filter(group => group.key !== activeGroup)"
          :key="group.key"
          type="button"
          class="collapsed-status-bar"
          :style="{
            '--group-color': group.color,
            background: '#ffffff',
            color: group.color,
            borderLeftColor: group.color
          }"
          @click="
            activeGroup = group.key;
            activeSectionCollapsed = false
          "
        >
          <span class="collapsed-status-left">
            <span class="section-chevron-slot">
              <i class="fa-solid fa-chevron-right collapsed-status-icon"></i>
            </span>
            <strong>{{ group.label }}</strong>
          </span>
        </button>
      </section>
    </main>
    <!-- ROW STATUS DROPDOWN -->
    <div
      v-if="rowStatusMenuId && rowStatusMenuOrder"
      class="status-fixed-dropdown"
      :style="rowStatusMenuStyle"
      @click.stop
    >
      <button
        v-for="status in statusOptions"
        :key="'status-fixed-' + status.label"
        type="button"
        class="status-fixed-option"
        :class="{ active: status.label === rowStatusMenuOrder.status }"
        @click.stop="selectRowStatus(rowStatusMenuOrder, status)"
      >
        <span
          class="status-fixed-dot"
          :style="{ background: status.color }"
        ></span>

        <span class="status-fixed-label">
          {{ status.label }}
        </span>

        <i
          v-if="status.label === rowStatusMenuOrder.status"
          class="fa-solid fa-check status-fixed-check"
        ></i>
      </button>
    </div>


    <!-- RIGHT PANEL -->
    <div v-if="selectedOrder && detailOpen" class="board-detail-overlay" @click.self="closeBoardDetail">
      <div class="orders-right board-detail-panel clean-detail-panel">

      <!-- HEADER -->
      <div class="detail-header clean-detail-header">
        <button
          type="button"
          class="board-detail-back"
          title="Back to orders"
          @click.stop="closeBoardDetail"
        >
          <i class="fa-solid fa-arrow-left"></i>
          <span>Back to Orders</span>
        </button>

        <div
          v-if="selectedOrder"
          class="clean-detail-order-name"
        >
          <i class="fa-solid fa-folder-open"></i>

          <div>
            <small>Order</small>
            <strong>{{ selectedOrder.name }}</strong>
          </div>
        </div>

        <button
          v-if="!isClient"
          type="button"
          class="clean-detail-chat-button"
          :class="{ active: showChat }"
          @click.stop="toggleChat"
        >
          <i class="fa-solid fa-comments"></i>
          <span>Chat</span>

          <strong v-if="unreadChatCount > 0">
            {{ unreadChatCount }}
          </strong>
        </button>

        <div
          v-if="selectedOrder"
          class="detail-pipeline-strip"
        >
          <span class="detail-pipeline-label">
            Pipeline
          </span>

          <button
            v-for="group in boardGroups"
            :key="group.key"
            type="button"
            class="detail-pipeline-step"
            :class="{
              active:
                selectedOrder.group === group.key ||
                (
                  group.key === 'delivered' &&
                  String(
                    selectedOrder.status || ''
                  ).toLowerCase() === 'delivered'
                )
            }"
            :style="{
              '--pipeline-color': group.color,
              background:
                (
                  selectedOrder.group === group.key ||
                  (
                    group.key === 'delivered' &&
                    String(
                      selectedOrder.status || ''
                    ).toLowerCase() === 'delivered'
                  )
                )
                  ? group.color
                  : 'rgba(255,255,255,.08)'
            }"
            @click.stop="moveSelectedOrderToPipeline(group)"
          >
            <span
              :style="{ background: group.color }"
            ></span>

            {{ group.label }}
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
<span class="info-value date-clickable" @click="!isClient && (showDatePicker = !showDatePicker)">
                  {{ selectedOrder.shipDate }}
              <i class="fa-solid fa-calendar-days" style="font-size:11px;margin-left:4px;color:black"></i>
            </span>
<div v-if="showDatePicker && !isClient" class="date-dropdown">
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
  <span class="info-label">Shipping Address :</span>

  <span
  class="info-value"
  @click="!isClient && (showShippingAddressMenu = !showShippingAddressMenu)"
>
{{ shortShippingAddress(selectedOrder?.shippingAddress) }}
    <i class="fa-solid fa-pen"></i>
  </span>

<div
  v-if="showShippingAddressMenu && !isClient"
  class="tracking-dropdown"
>
    <textarea
      v-model="shippingAddressEdit"
      class="payment-input"
      rows="4"
    ></textarea>

    <button
      class="payment-save-btn"
      @click="saveShippingAddress"
    >
      Save Address
    </button>
  </div>
</div>
          <div class="detail-info-item" style="position:relative" @click.stop>
            <span class="info-label">Status :</span>
<span class="status-badge" :style="{ background: selectedOrder.statusColor }" @click="!isClient && (showStatusMenu = !showStatusMenu)">
                  {{ selectedOrder.status }}
              <i class="fa-solid fa-chevron-down" style="font-size:9px;margin-left:4px"></i>
            </span>
<div v-if="showStatusMenu && !isClient" class="status-dropdown">
                  <div v-for="s in statusOptions" :key="s.label" class="status-drop-item" @click="changeStatus(s)">
  <input
    type="color"
    class="status-dot status-color-picker"
    :value="s.color"
    @click.stop
    @input.stop="changeStatusOptionColor(s, $event.target.value)"
  />

  <span class="status-name">{{ s.label }}</span>

  <span class="status-group-tag">→ {{ s.groupLabel }}</span>

  <button v-if="s.custom" class="status-action-btn" @click.stop="editCustomStatus(s)">
    <i class="fa-solid fa-pen"></i>
  </button>

  <button v-if="s.custom" class="status-action-btn danger" @click.stop="deleteCustomStatus(s)">
    <i class="fa-solid fa-trash"></i>
  </button>
</div>
              <div class="custom-status-box">
                <input v-model="customStatusLabel" class="custom-status-input" placeholder="Write custom status..." @keydown.enter.prevent="applyCustomStatus" />
                <input v-model="customStatusColor" type="color" class="custom-status-color" title="Choose label color" />
                <button class="custom-status-btn" @click="applyCustomStatus">Add</button>
              </div>
            </div>
          </div>
          <div v-if="hasFullOrderAccess" class="detail-info-item invoice-info-item" style="position:relative" @click.stop>
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

  <div class="trk-slider-box">
    <button class="trk-arrow" @click="prevTracking" :disabled="trackingList.length <= 1">
      <i class="fa-solid fa-chevron-left"></i>
    </button>

    <span class="trk-badge trk-clickable" @click="!isClient && openTrackingMenu()">
      <img v-if="trackingLogo(activeTracking.company)" :src="trackingLogo(activeTracking.company)" class="trk-logo" />
      {{ activeTracking.number || 'N/A' }}
      <small v-if="trackingList.length > 1">({{ activeTrackingIndex + 1 }}/{{ trackingList.length }})</small>
      <i class="fa-solid fa-pen" style="font-size:9px;margin-left:6px"></i>
    </span>

    <button class="trk-arrow" @click="nextTracking" :disabled="trackingList.length <= 1">
      <i class="fa-solid fa-chevron-right"></i>
    </button>
  </div>

  <div v-if="showTrackingMenu && !isClient" class="tracking-dropdown tracking-dropdown-wide">
    <div class="tracking-dropdown-header">All Tracking Details</div>

    <div
      v-for="(item, index) in trackingEditList"
      :key="index"
      class="tracking-multi-row"
    >
      <div class="payment-field">
        <label class="payment-label">Tracking Number {{ index + 1 }}</label>
        <input
          v-model="item.number"
          class="payment-input"
          placeholder="e.g. 123456789"
          @keydown.enter.prevent="addTrackingRow"
        />
      </div>

      <div class="payment-field">
        <label class="payment-label">Company Website</label>
        <input
          v-model="item.company"
          class="payment-input"
          placeholder="e.g. www.dhl.com or www.ups.com"
          @keydown.enter.prevent="addTrackingRow"
        />
      </div>

      <div v-if="trackingLogo(item.company)" class="tracking-preview-row">
        <img :src="trackingLogo(item.company)" class="tracking-preview-logo" />
        <span>{{ trackingCompanyName(item.company) }}</span>
      </div>

  <button
  v-if="trackingEditList.length > 1"
  class="tracking-close-btn"
  @click="removeTrackingRow(index)"
>
  <i class="fa-solid fa-xmark"></i>
</button>
    </div>

 <button class="tracking-add-btn" @click="addTrackingRow">
  <i class="fa-solid fa-plus"></i>
  <span>Add Tracking</span>
</button>

    <button class="payment-save-btn" @click="saveTracking">
      <i class="fa-solid fa-floppy-disk me-1"></i>Save All Tracking
    </button>
  </div>
</div>
          <div class="detail-info-item" style="position:relative" @click.stop>
            <span class="info-label">Payment :</span>
            <span class="payment-badge payment-summary-badge" @click="!isClient && (showPaymentMenu = !showPaymentMenu)">
                  <span class="payment-chip payment-chip-paid">{{ selectedOrder.payment || '0 % Paid' }}</span>
              <span class="payment-chip payment-chip-received">R ${{ selectedOrder.paymentReceived || 0 }}</span>
              <span class="payment-chip payment-chip-balance">B ${{ selectedOrder.paymentBalance || 0 }}</span>
              <i class="fa-solid fa-chevron-down" style="font-size:9px;margin-left:4px"></i>
            </span>
           <div v-if="showPaymentMenu && !isClient" class="payment-dropdown payment-dropdown-wide">
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
<div v-if="canEditWorkflowFields" class="payment-admin-editor">
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
                <div
                  class="card-preview-area"
                  :class="{ 'drag-drop-active': dragActiveCardType === card.type }"
                  @dragenter.prevent.stop="onDragEnter(card)"
                  @dragover.prevent.stop="onDragOver($event, card)"
                  @dragleave.prevent.stop="onDragLeave($event, card)"
                  @drop.prevent.stop="onDrop($event, card)"
                >
                  <div
                    v-if="dragActiveCardType === card.type"
                    class="drag-drop-overlay"
                  >
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <strong>Drop files here</strong>
                    <span>{{ card.title }}</span>
                  </div>
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
    v-if="showChat && !isClient"
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
      </div>




    <!-- PROFILE SETTINGS MODAL -->
    <div
      v-if="profileModal"
      class="profile-settings-overlay"
      @click.self="closeProfile"
    >
      <div class="profile-settings-modal">
        <button
          type="button"
          class="profile-settings-close"
          @click="closeProfile"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="profile-settings-heading">
          <h3>Profile Settings</h3>
          <p>Update your photo and personal details.</p>
        </div>

        <div class="profile-settings-photo-section">
          <label class="profile-photo-circle">
            <img
              v-if="profileForm.preview"
              :src="profileForm.preview"
              alt="Profile"
            />

            <span v-else>
              {{ initial(profileForm.name || profileUser?.name || 'U') }}
            </span>

            <input
              v-if="isOwnProfile(profileUser)"
              type="file"
              accept="image/*"
              @change="onProfilePhotoChange"
            />

            <i
              v-if="isOwnProfile(profileUser)"
              class="fa-solid fa-camera"
            ></i>
          </label>

          <small v-if="isOwnProfile(profileUser)">
            Click photo to change
          </small>
        </div>

        <div class="profile-settings-field">
          <label>Name</label>

          <input
            v-model="profileForm.name"
            type="text"
            :readonly="!isOwnProfile(profileUser)"
          />
        </div>

        <div class="profile-settings-field">
          <label>About</label>

          <textarea
            v-model="profileForm.about"
            rows="4"
            :readonly="!isOwnProfile(profileUser)"
            placeholder="Write something about yourself..."
          ></textarea>
        </div>

        <div class="profile-settings-actions">
          <button
            type="button"
            class="profile-settings-cancel"
            @click="closeProfile"
          >
            Cancel
          </button>

          <button
            v-if="isOwnProfile(profileUser)"
            type="button"
            class="profile-settings-save"
            @click="saveProfile"
          >
            <i class="fa-solid fa-floppy-disk"></i>
            Save Changes
          </button>
        </div>
      </div>
    </div>

    <!-- MEMBER SELECTION MODAL -->
    <div
      v-if="bulkMembersModal"
      class="member-select-overlay"
      @click.self="closeBulkMembersModal"
    >
      <div class="member-select-modal">
        <div class="member-select-header">
          <div>
            <h3>Add Members</h3>
            <p>
              Apply members to
              {{ selectedOrders.length }}
              selected order(s)
            </p>
          </div>

          <button
            type="button"
            @click="closeBulkMembersModal"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="member-select-toolbar">
          <button
            type="button"
            @click="selectAllAvailableMembers"
          >
            <i class="fa-solid fa-users"></i>
            {{
              bulkSelectedMembers.length === availableMembers.length &&
              availableMembers.length
                ? 'Clear All'
                : 'Select All Members'
            }}
          </button>

          <span>
            {{ bulkSelectedMembers.length }}
            selected
          </span>
        </div>

        <Multiselect
          v-model="bulkSelectedMembers"
          :options="availableMembers"
          :multiple="true"
          :close-on-select="false"
          :clear-on-select="false"
          :preserve-search="true"
          label="name"
          track-by="id"
          placeholder="Search and select members"
          class="member-multiselect"
        >
          <template #option="{ option }">
            <div class="member-option-row">
              <div class="member-option-avatar">
                <img
                  v-if="option.profile_photo_url"
                  :src="option.profile_photo_url"
                  alt=""
                />

                <span v-else>
                  {{ initial(option.name) }}
                </span>
              </div>

              <div>
                <strong>{{ option.name }}</strong>
                <small>{{ option.email || option.role }}</small>
              </div>
            </div>
          </template>

          <template #tag="{ option, remove }">
            <span class="member-selected-tag">
              {{ option.name }}

              <button
                type="button"
                @click.stop="remove(option)"
              >
                ×
              </button>
            </span>
          </template>
        </Multiselect>

        <div class="member-selected-preview">
          <div
            v-for="member in bulkSelectedMembers"
            :key="member.id"
            class="member-preview-chip"
          >
            <img
              v-if="member.profile_photo_url"
              :src="member.profile_photo_url"
              alt=""
            />

            <span v-else>
              {{ initial(member.name) }}
            </span>

            <strong>{{ member.name }}</strong>
          </div>
        </div>

        <div class="member-select-footer">
          <button
            type="button"
            class="member-cancel-button"
            @click="closeBulkMembersModal"
          >
            Cancel
          </button>

          <button
            type="button"
            class="member-save-button"
            :disabled="bulkSaving"
            @click="bulkUpdateMembers"
          >
            <i
              :class="
                bulkSaving
                  ? 'fa-solid fa-spinner fa-spin'
                  : 'fa-solid fa-check'
              "
            ></i>

            {{
              bulkSaving
                ? 'Saving...'
                : 'Apply Members'
            }}
          </button>
        </div>
      </div>
    </div>


    <!-- CLIENT SELECTION MODAL -->
    <div
      v-if="bulkClientsModal"
      class="member-select-overlay"
      @click.self="closeBulkClientsModal"
    >
      <div class="member-select-modal">
        <div class="member-select-header">
          <div>
            <h3>Add Clients</h3>
            <p>
              Apply clients to
              {{ selectedOrders.length }}
              selected order(s)
            </p>
          </div>

          <button
            type="button"
            @click="closeBulkClientsModal"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="member-select-toolbar">
          <button
            type="button"
            @click="selectAllAvailableClients"
          >
            <i class="fa-solid fa-user-tie"></i>
            {{
              bulkSelectedClients.length === availableClients.length &&
              availableClients.length
                ? 'Clear All'
                : 'Select All Clients'
            }}
          </button>

          <span>
            {{ bulkSelectedClients.length }}
            selected
          </span>
        </div>

        <Multiselect
          v-model="bulkSelectedClients"
          :options="availableClients"
          :multiple="true"
          :close-on-select="false"
          :clear-on-select="false"
          :preserve-search="true"
          label="name"
          track-by="id"
          placeholder="Search and select clients"
          class="member-multiselect"
        >
          <template #option="{ option }">
            <div class="member-option-row">
              <div class="member-option-avatar client-avatar">
                <i class="fa-solid fa-user-tie"></i>
              </div>

              <div>
                <strong>{{ option.name }}</strong>
                <small>
                  {{ option.company || option.email || 'Client' }}
                </small>
              </div>
            </div>
          </template>

          <template #tag="{ option, remove }">
            <span class="member-selected-tag">
              {{ option.name }}

              <button
                type="button"
                @click.stop="remove(option)"
              >
                ×
              </button>
            </span>
          </template>
        </Multiselect>

        <div class="member-selected-preview">
          <div
            v-for="client in bulkSelectedClients"
            :key="client.id"
            class="member-preview-chip"
          >
            <span class="client-preview-icon">
              <i class="fa-solid fa-user-tie"></i>
            </span>

            <strong>{{ client.name }}</strong>
          </div>
        </div>

        <div class="member-select-footer">
          <button
            type="button"
            class="member-cancel-button"
            @click="closeBulkClientsModal"
          >
            Cancel
          </button>

          <button
            type="button"
            class="member-save-button"
            :disabled="bulkClientSaving"
            @click="bulkUpdateClients"
          >
            <i
              :class="
                bulkClientSaving
                  ? 'fa-solid fa-spinner fa-spin'
                  : 'fa-solid fa-check'
              "
            ></i>

            {{
              bulkClientSaving
                ? 'Saving...'
                : 'Apply Clients'
            }}
          </button>
        </div>
      </div>
    </div>

    <!-- VIEWED BY MODAL -->
    <div
      v-if="orderInfoModal"
      class="viewed-modal-overlay"
      @click.self="closeOrderInfo"
    >
      <div class="viewed-modal">
        <div class="viewed-modal-header">
          <div>
            <h3>Order View History</h3>
            <p>{{ infoOrder?.name || 'Order' }}</p>
          </div>

          <button
            type="button"
            @click="closeOrderInfo"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div
          v-if="orderReadInfo.length === 0"
          class="viewed-empty"
        >
          No member has opened this order yet.
        </div>

        <div
          v-for="read in orderReadInfo"
          :key="read.id || read.user_id"
          class="viewed-person-row"
        >
          <div class="viewed-avatar">
            <img
              v-if="read.user?.profile_photo_url || read.profile_photo_url"
              :src="read.user?.profile_photo_url || read.profile_photo_url"
              alt=""
            />

            <span v-else>
              {{ initial(read.user?.name || read.name || 'U') }}
            </span>
          </div>

          <div class="viewed-person-info">
            <strong>
              {{ read.user?.name || read.name || 'Member' }}
            </strong>

            <small>
              Opened: {{
                formatReadDate(
                  read.last_viewed_at ||
                  read.read_at ||
                  read.created_at
                )
              }}
            </small>

            <small v-if="read.views_count">
              Views: {{ read.views_count }}
            </small>
          </div>

          <span
            v-if="
              infoOrder &&
              workingDesigner(infoOrder) &&
              Number(
                workingDesigner(infoOrder)?.id
              ) === Number(
                read.user_id ||
                read.user?.id
              )
            "
            class="currently-working-badge"
          >
            Working now
          </span>
        </div>
      </div>
    </div>

  </div>
  </AppLayout>
</template>

<script>
import axios from 'axios'
import JSZip from 'jszip'
import Multiselect from 'vue-multiselect'
import OrderChatPanel from './OrderChatPanel.vue'
import AppLayout from '../layouts/AppLayout.vue'
import PageHeader from '../layouts/PageHeader.vue'
import 'vue-multiselect/dist/vue-multiselect.min.css'

export default {
  name: 'AllOrdersView',
  components: { Multiselect, OrderChatPanel, AppLayout, PageHeader },
  data() {
      return {
      showShippingAddressMenu: false,
      dragActiveCardType: null,
      dragCounter: 0,
      detailOpen: false,
      inlineAddOpen: false,
      inlineOrderName: '',
      inlineOrderSaving: false,
      inlineEditingCell: null,
      inlineEditValue: '',
      packingEditOrderId: null,
      packingEditValue: '',
      packingSavingOrderId: null,
      rowStatusMenuId: null,
      rowStatusMenuOrder: null,
      rowStatusMenuPosition: {
        top: 0,
        left: 0,
        width: 190
      },

      columnWidths: {
        check: 42,
        name: 430,
        status: 120,
        owner: 120,
        files: 190,
        packing: 130,
        chat: 85,
        payment: 120,
        address: 240,
        track: 110,
        info: 42
      },
      columnResizeState: null,

      rowFileDragOrderId: null,
      rowFileDragDepth: 0,
      customBoardGroups: [],
      defaultBoardGroupOverrides: {},
      newlyCreatedOrderIds: JSON.parse(
        localStorage.getItem('factory_pinned_new_order_ids') || '[]'
      ).map(Number),
      showChatNotificationMenu: false,
      activeSectionCollapsed: false,
      boardTheme: localStorage.getItem('artwork_board_theme') || 'light',
      persistentSeenOrderIds: JSON.parse(
        localStorage.getItem('artwork_seen_order_ids') || '[]'
      ).map(Number),
      bulkClientsModal: false,
      bulkSelectedClients: [],
      bulkClientSaving: false,
      shippingAddressEdit: '',
      sidebarLightMode: false,
      leftWidth: 370,
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
      availableClients: [],
      clientModal: false,
      clientSaving: false,
      clientForm: {
      name: '',
      email: '',
      phone: '',
      company: '',
      address: '',
      status: 'active'},
      teamMembers: [],
      chatMessages: [],
      unreadChatCount: 0,
      unreadTimer: null,
      notificationTimer: null,
      notifications: [],
      notificationCount: 0,
      lastNotificationId: null,
      searchOrder: '',
      selectedClient: '',
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
      trackingEditList: [{ number: '', company: '' }],
activeTrackingIndex: 0,
      paymentEdit: { percent: '', received: '', balance: '' },
      newOrder: {
      name: '',
      po: '',
      selectedMembers: [],
      selectedClients: [],
      shippingAddress: '',
      shipDate: '',
      status: 'Pending',
      payment: '0 % Paid',
      trk: 'N/A'
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
    boardGroups() {
      const statusColorByLabel = (label, fallback) => {
        const found = this.statusOptions.find(
          status =>
            String(status.label || '').toLowerCase() ===
            String(label).toLowerCase()
        )

        return found?.color || fallback
      }

      const baseDefaults = [
        {
          key: 'in_production',
          label: 'IN PRODUCTION',
          color: statusColorByLabel('In Production', '#6161ff'),
          icon: 'fa-solid fa-house'
        },
        {
          key: 'completed',
          label: 'COMPLETED',
          color: statusColorByLabel('Completed', '#00c875'),
          icon: 'fa-solid fa-house'
        },
        {
          key: 'shipped',
          label: 'SHIPPED',
          color: statusColorByLabel('Shipped', '#fdab3d'),
          icon: 'fa-solid fa-house'
        },
        {
          key: 'delivered',
          label: 'DELIVERED',
          color: statusColorByLabel('Delivered', '#00c875'),
          icon: 'fa-solid fa-house'
        }
      ]

      const defaults = baseDefaults
        .map(group => {
          const override =
            this.defaultBoardGroupOverrides?.[group.key] || {}

          return {
            ...group,
            ...override,
            key: group.key,
            custom: false
          }
        })
        .filter(group => !group.hidden)

      return [...defaults, ...this.customBoardGroups]
    },

    activeBoardGroup() {
      if (this.activeGroup === 'all') {
        return {
          key: 'all',
          label: 'ALL ORDERS',
          color: '#4a90e2'
        }
      }

      return this.boardGroups.find(
        group => group.key === this.activeGroup
      ) || this.boardGroups[0]
    },

    trackingList() {
  return this.parseTrackingList(this.selectedOrder?.trk)
},

activeTracking() {
  return this.trackingList[this.activeTrackingIndex] || { number: '', company: '' }
},
    isClient() {
  return this.currentUser?.role === 'client'
},
 canEditNotes() {
  if (!this.selectedOrder || !this.currentUser) {
    return false
  }

  return this.hasFullOrderAccess
    || this.currentUser?.can_create_orders === true
},
canUploadFiles() {
  return this.hasFullOrderAccess
    || this.currentUser?.can_create_orders === true
},
canEditWorkflowFields() {
  const role = this.currentUser?.role

  return role === 'super_admin'
    || role === 'admin'
    || role === 'member'
},
    currentUser() {
      try { return JSON.parse(localStorage.getItem('user')) || null } catch { return null }
    },
    isSuperAdmin() { return this.currentUser?.role === 'super_admin' },
    isAdmin() { return this.currentUser?.role === 'admin' },
    hasFullOrderAccess() { return this.isSuperAdmin || this.isAdmin },
   canCreateOrder() {
  return this.hasFullOrderAccess
    || this.currentUser?.can_create_orders === true
},

    userInitial() {
      const raw = localStorage.getItem('user')
      if (!raw) return 'A'
      try { const user = JSON.parse(raw); return user?.name ? user.name.charAt(0).toUpperCase() : 'A' } catch { return 'A' }
    },
    userPhoto() { return this.currentUser?.profile_photo_url || null },
filteredOrders() {
  return this.orders
    .filter(o => {
      const groupMatch =
        this.activeGroup === 'all' ||
        o.group === this.activeGroup ||
        (
          this.activeGroup === 'delivered' &&
          String(o.status || '').toLowerCase() === 'delivered'
        )

      const searchText = String(this.searchOrder || '')
        .trim()
        .toLowerCase()

      const searchable = [
        o.name,
        o.po,
        o.status,
        o.shippingAddress,
        o.trk
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()

      const searchMatch =
        !searchText || searchable.includes(searchText)

      const clientMatch =
        !this.selectedClient ||
        (o.clients || []).some(
          c => Number(c.id) === Number(this.selectedClient)
        )

      return groupMatch && searchMatch && clientMatch
    })
    .sort((a, b) => {
      /*
       * 1) Jis order mein unread chat aaye woh sab se upar.
       *    Chat view hote hi unread count 0 ho jata hai aur row apni normal
       *    pinned/A-Z position par wapas chali jati hai.
       */
      const aUnread = Number(a.unread_chat_count || 0) > 0
      const bUnread = Number(b.unread_chat_count || 0) > 0

      if (aUnread !== bUnread) {
        return aUnread ? -1 : 1
      }

      if (aUnread && bUnread) {
        const aTime = new Date(a.last_message_at || 0).getTime()
        const bTime = new Date(b.last_message_at || 0).getTime()

        if (aTime !== bTime) {
          return bTime - aTime
        }
      }

      /*
       * 2) Is browser se newly-created orders refresh ke baad bhi top par.
       *    IDs localStorage mein persist hoti hain.
       */
      const aNewIndex = this.newlyCreatedOrderIds.indexOf(Number(a.id))
      const bNewIndex = this.newlyCreatedOrderIds.indexOf(Number(b.id))
      const aIsNew = aNewIndex !== -1
      const bIsNew = bNewIndex !== -1

      if (aIsNew !== bIsNew) {
        return aIsNew ? -1 : 1
      }

      if (aIsNew && bIsNew && aNewIndex !== bNewIndex) {
        return aNewIndex - bNewIndex
      }

      /*
       * 3) Baqi orders A-Z.
       */
      return String(a.name || '').localeCompare(
        String(b.name || ''),
        'en',
        {
          sensitivity: 'base',
          numeric: true,
          ignorePunctuation: true
        }
      )
    })
},
    unreadChatOrders() {
      return this.orders
        .filter(order => Number(order.unread_chat_count || 0) > 0)
        .sort((a, b) => {
          const aTime = new Date(a.last_message_at || 0).getTime()
          const bTime = new Date(b.last_message_at || 0).getTime()
          return bTime - aTime
        })
    },
    totalUnreadChatCount() {
      return this.unreadChatOrders.reduce(
        (total, order) => total + Number(order.unread_chat_count || 0),
        0
      )
    },
    unreadOrdersCount() { return this.orders.filter(o => !o.user_has_seen).length },

    rowStatusOrder() {
      if (!this.rowStatusMenuId) return null

      return this.orders.find(
        order => Number(order.id) === Number(this.rowStatusMenuId)
      ) || null
    },

    rowStatusMenuStyle() {
      const pos = this.rowStatusMenuPosition || {}

      return {
        position: 'fixed',
        top: `${Number(pos.top || 0)}px`,
        left: `${Number(pos.left || 0)}px`,
        width: `${Number(pos.width || 230)}px`,
        zIndex: 2147483647
      }
    },

    boardGridStyle() {
      const w = this.columnWidths

      const keys = [
        'check',
        'name',
        'status',
        'owner',
        'files',
        'packing',
        'chat',
        'payment',
        'address',
        'track',
        'info'
      ]

      const total = keys.reduce(
        (sum, key) => sum + Number(w[key] || 0),
        0
      ) || 1

      /*
       * Percent-based grid:
       * table always remains exactly 100% wide.
       * Divider move = one column grows, next one shrinks.
       * No blank area, no horizontal layout break.
       */
      const columns = keys
        .map(key => {
          const percent =
            (Number(w[key] || 0) / total) * 100

          return `${percent.toFixed(5)}%`
        })
        .join(' ')

      return {
        '--board-grid-columns': columns,
        gridTemplateColumns: columns
      }
    },
    desktopLeftStyle() {
       // Only apply dynamic width on desktop (>= 768px)
      if (typeof window !== 'undefined' && window.innerWidth >= 768) {
        return { width: this.leftWidth + 'px' }
      }
      return {}
    }
  },


watch: {
  detailOpen(value) {
    document.body.style.overflow = value
      ? 'hidden'
      : ''
  },

  orderInfoModal(value) {
    if (value) {
      document.body.style.overflow = 'hidden'
    } else if (!this.detailOpen && !this.bulkMembersModal && !this.bulkClientsModal) {
      document.body.style.overflow = ''
    }
  },

  bulkMembersModal(value) {
    if (value) {
      document.body.style.overflow = 'hidden'
    } else if (!this.detailOpen && !this.orderInfoModal && !this.bulkClientsModal) {
      document.body.style.overflow = ''
    }
  },

  bulkClientsModal(value) {
    if (value) {
      document.body.style.overflow = 'hidden'
    } else if (!this.detailOpen && !this.orderInfoModal && !this.bulkMembersModal) {
      document.body.style.overflow = ''
    }
  }
},

async mounted() {
  this.loadCustomStatuses()
  this.loadBoardGroups()
  this.loadDefaultBoardGroupOverrides()
  await this.fetchOrders()
  await this.fetchMembers()
await this.fetchClients()
  if ('Notification' in window) {
    Notification.requestPermission()
  }

  await this.fetchNotifications(false)


  const orderId = this.$route.query.order_id
  const openChat = this.$route.query.open_chat

   if (orderId) {
  const foundOrder = this.orders.find(o => Number(o.id) === Number(orderId))

  if (foundOrder) {
    this.activeGroup = foundOrder.group
    await this.selectOrder(foundOrder)

    if (openChat == 1) {
      this.showChat = true
      await this.markChatRead()
    }
  }
} else if (this.filteredOrders.length) {
  this.selectedOrder = this.filteredOrders[0]
}
  },

beforeUnmount()  {
  document.removeEventListener('mousemove', this.resizeSidebar)
  document.removeEventListener('mouseup', this.stopResize)
  document.removeEventListener('mousemove', this.resizeBoardColumn)
  document.removeEventListener('mouseup', this.stopColumnResize)

  if (this.unreadTimer) {
    clearInterval(this.unreadTimer)
  }

  if (this.notificationTimer) {
    clearInterval(this.notificationTimer)
  }
},








  beforeUnmount() {
    document.body.style.overflow = ''
  },



 methods: {
    normalizeOwnerRole(role) {
      return String(role || 'member')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '_')
    },

    formatOwnerRole(role) {
      const normalized = this.normalizeOwnerRole(role)

      if (normalized === 'super_admin') {
        return 'Super Admin'
      }

      if (normalized === 'admin') {
        return 'Admin'
      }

      return 'Member'
    },

    allAvailableMembersAssigned(order) {
      const assignedIds = new Set(
        (order?.owners || []).map(
          owner => Number(owner.id)
        )
      )

      const availableIds = (
        this.availableMembers || []
      )
        .map(member => Number(member.id))
        .filter(Boolean)

      return (
        availableIds.length > 0 &&
        availableIds.every(id => assignedIds.has(id))
      )
    },

    visibleOrderOwners(order) {
      const owners = [...(order?.owners || [])]

      /*
       * Jab tamam available members order mein assigned hon:
       * 1 Super Admin, 1 Admin aur alphabetically pehle
       * 2 normal Members show honge.
       */
      if (this.allAvailableMembersAssigned(order)) {
        const byName = (first, second) =>
          String(first.name || '').localeCompare(
            String(second.name || ''),
            'en',
            {
              sensitivity: 'base',
              numeric: true
            }
          )

        const superAdmin = owners
          .filter(
            owner =>
              this.normalizeOwnerRole(owner.role) ===
              'super_admin'
          )
          .sort(byName)
          .slice(0, 1)

        const admin = owners
          .filter(
            owner =>
              this.normalizeOwnerRole(owner.role) ===
              'admin'
          )
          .sort(byName)
          .slice(0, 1)

        const members = owners
          .filter(owner => {
            const role =
              this.normalizeOwnerRole(owner.role)

            return (
              role !== 'super_admin' &&
              role !== 'admin'
            )
          })
          .sort(byName)
          .slice(0, 2)

        return [
          ...superAdmin,
          ...admin,
          ...members
        ]
      }

      /*
       * Normal case:
       * Sirf profile image wale assigned members show honge.
       * Bina image wale members + count ke andar rahenge.
       */
      return owners
        .filter(owner => Boolean(owner.profile_photo_url))
        .sort((first, second) =>
          String(first.name || '').localeCompare(
            String(second.name || ''),
            'en',
            {
              sensitivity: 'base',
              numeric: true
            }
          )
        )
        .slice(0, 4)
    },

    hiddenOrderOwners(order) {
      const visibleIds = new Set(
        this.visibleOrderOwners(order).map(
          owner => Number(owner.id)
        )
      )

      return (order?.owners || []).filter(
        owner => !visibleIds.has(Number(owner.id))
      )
    },

    hiddenOrderOwnersCount(order) {
      return this.hiddenOrderOwners(order).length
    },

    hiddenOrderOwnersNames(order) {
      const hidden = this.hiddenOrderOwners(order)

      if (!hidden.length) {
        return ''
      }

      return hidden
        .map(owner => owner.name)
        .filter(Boolean)
        .sort((first, second) =>
          String(first).localeCompare(
            String(second),
            'en',
            {
              sensitivity: 'base',
              numeric: true
            }
          )
        )
        .join(', ')
    },

    toggleBoardTheme() {
      this.boardTheme =
        this.boardTheme === 'light'
          ? 'dark'
          : 'light'

      localStorage.setItem(
        'artwork_board_theme',
        this.boardTheme
      )
    },

    softColor(color, alpha = 0.08) {
      const value = String(color || '')
        .replace('#', '')
        .trim()

      if (!/^[0-9a-f]{6}$/i.test(value)) {
        return `rgba(100, 116, 139, ${alpha})`
      }

      const red = parseInt(value.slice(0, 2), 16)
      const green = parseInt(value.slice(2, 4), 16)
      const blue = parseInt(value.slice(4, 6), 16)

      return `rgba(${red}, ${green}, ${blue}, ${alpha})`
    },

    readableTextColor(color) {
      const value = String(color || '')
        .replace('#', '')
        .trim()

      if (!/^[0-9a-f]{6}$/i.test(value)) {
        return '#111827'
      }

      const red = parseInt(value.slice(0, 2), 16)
      const green = parseInt(value.slice(2, 4), 16)
      const blue = parseInt(value.slice(4, 6), 16)

      const luminance =
        (0.299 * red) +
        (0.587 * green) +
        (0.114 * blue)

      return luminance > 150
        ? '#111827'
        : '#ffffff'
    },

    openBulkClientsModal() {
      if (!this.selectedOrders.length) {
        return
      }

      this.bulkSelectedClients = []
      this.bulkClientsModal = true
    },

    closeBulkClientsModal() {
      this.bulkClientsModal = false
      this.bulkSelectedClients = []
      this.bulkClientSaving = false
    },

    selectAllAvailableClients() {
      if (
        this.bulkSelectedClients.length ===
          this.availableClients.length &&
        this.availableClients.length
      ) {
        this.bulkSelectedClients = []
        return
      }

      this.bulkSelectedClients = [
        ...this.availableClients
      ]
    },

    async bulkUpdateClients() {
      if (!this.selectedOrders.length) return

      this.bulkClientSaving = true

      try {
        const clientIds =
          this.bulkSelectedClients.map(
            client => Number(client.id)
          )

        await Promise.all(
          this.selectedOrders.map(orderId =>
            axios.put(
              `/api/orders/${orderId}`,
              {
                client_ids: clientIds
              },
              {
                headers: this.headers()
              }
            )
          )
        )

        await this.fetchOrders()
        this.closeBulkClientsModal()
      } catch (error) {
        console.error(
          'Bulk clients update error:',
          error
        )

        alert(
          error.response?.data?.message ||
          'Clients could not be added.'
        )
      } finally {
        this.bulkClientSaving = false
      }
    },

    async startWorking(order) {
      if (!order?.id) return

      try {
        const response = await axios.post(
          `/api/orders/${order.id}/claim`,
          {},
          {
            headers: this.headers()
          }
        )

        order.working_by =
          response.data?.working_by ||
          response.data?.user ||
          this.currentUser

        await this.fetchOrders()
      } catch (error) {
        console.error('Start working error:', error)

        alert(
          error.response?.data?.message ||
          'Working status could not be started.'
        )
      }
    },

    async stopWorking(order) {
      if (!order?.id) return

      try {
        await axios.post(
          `/api/orders/${order.id}/release`,
          {},
          {
            headers: this.headers()
          }
        )

        order.working_by = null
        await this.fetchOrders()
      } catch (error) {
        console.error('Stop working error:', error)

        alert(
          error.response?.data?.message ||
          'Working status could not be stopped.'
        )
      }
    },

    workingDesigner(order) {
      return order?.working_by || null
    },

    async claimOrder(order) {
      if (!order?.id || !this.currentUser) return

      try {
        const response = await axios.post(
          `/api/orders/${order.id}/claim`,
          {},
          {
            headers: this.headers()
          }
        )

        order.working_by =
          response.data?.working_by ||
          response.data?.user ||
          this.currentUser
      } catch (error) {
        console.error('Claim order error:', error)

        alert(
          error.response?.data?.message ||
          'Working status could not be started.'
        )
      }
    },

    async releaseOrder(order) {
      if (!order?.id) return

      try {
        await axios.post(
          `/api/orders/${order.id}/release`,
          {},
          {
            headers: this.headers()
          }
        )

        order.working_by = null
      } catch (error) {
        console.error('Release order error:', error)

        alert(
          error.response?.data?.message ||
          'Working status could not be stopped.'
        )
      }
    },

    onRowFileDragEnter(order) {
      if (!this.canUploadFiles) return

      this.rowFileDragDepth += 1
      this.rowFileDragOrderId = Number(order.id)
    },

    onRowFileDragOver(event, order) {
      if (!this.canUploadFiles) return

      if (event?.dataTransfer) {
        event.dataTransfer.dropEffect = 'copy'
      }

      this.rowFileDragOrderId = Number(order.id)
    },

    onRowFileDragLeave(event, order) {
      if (!this.canUploadFiles) return

      const current = event?.currentTarget
      const related = event?.relatedTarget

      if (
        current &&
        related &&
        current.contains(related)
      ) {
        return
      }

      this.rowFileDragDepth = Math.max(
        0,
        this.rowFileDragDepth - 1
      )

      if (this.rowFileDragDepth === 0) {
        this.rowFileDragOrderId = null
      }
    },

    async onRowFileDrop(event, order) {
      this.rowFileDragDepth = 0
      this.rowFileDragOrderId = null

      if (!this.canUploadFiles) return

      const files = Array.from(
        event?.dataTransfer?.files || []
      )

      if (!files.length) return

      await this.uploadRowFiles(order, files)
    },

    async uploadRowFiles(order, files) {
      try {
        await this.selectOrder(order)

        const targetCard =
          this.selectedOrder?.cards?.find(
            card => card.type === 'files'
          ) ||
          this.selectedOrder?.cards?.find(
            card => card.type !== 'notes'
          )

        if (!targetCard) {
          alert('No file section found in this order.')
          return
        }

        await this.uploadFilesToOrder(
          files,
          targetCard.type
        )

        await this.fetchOrderFiles()

        const listOrder = this.orders.find(
          item =>
            Number(item.id) === Number(order.id)
        )

        if (listOrder && this.selectedOrder) {
          listOrder.cards =
            this.selectedOrder.cards
        }
      } catch (error) {
        console.error(
          'Row drag-drop upload error:',
          error
        )

        alert(
          error.response?.data?.message ||
          'Files could not be uploaded.'
        )
      }
    },

    async moveSelectedOrderToPipeline(group) {
      if (
        !this.selectedOrder ||
        !this.canCreateOrder
      ) {
        return
      }

      const status =
        this.statusOptions.find(
          item => item.group === group.key
        ) || {
          label: group.label,
          color: group.color
        }

      await this.inlineChangeStatus(
        this.selectedOrder,
        status.label
      )
    },

    persistWorkflowGroups() {
      localStorage.setItem(
        'custom_factory_order_groups',
        JSON.stringify(this.customBoardGroups)
      )
    },

    loadDefaultBoardGroupOverrides() {
      try {
        const saved = JSON.parse(
          localStorage.getItem(
            'default_factory_order_group_overrides'
          ) || '{}'
        )

        this.defaultBoardGroupOverrides =
          saved && typeof saved === 'object'
            ? saved
            : {}
      } catch (error) {
        console.error(
          'Default board group overrides load error:',
          error
        )
        this.defaultBoardGroupOverrides = {}
      }
    },

    persistDefaultBoardGroupOverrides() {
      localStorage.setItem(
        'default_factory_order_group_overrides',
        JSON.stringify(this.defaultBoardGroupOverrides)
      )
    },

    defaultStatusLabelForGroup(groupKey) {
      const map = {
        in_production: 'In Production',
        completed: 'Completed',
        shipped: 'Shipped',
        delivered: 'Delivered'
      }

      return map[groupKey] || null
    },

    changeWorkflowGroupColor(group, color) {
      if (!group?.key || !color) return

      if (group.custom) {
        const target = this.customBoardGroups.find(
          item => item.key === group.key
        )

        if (!target) return

        target.color = color
        this.persistWorkflowGroups()

        const relatedStatuses = this.statusOptions.filter(
          status => status.group === group.key
        )

        relatedStatuses.forEach(status => {
          status.color = color
        })

        localStorage.setItem(
          'custom_order_statuses',
          JSON.stringify(
            this.statusOptions.filter(status => status.custom)
          )
        )

        return
      }

      /*
       * Default sections:
       * color override save hota hai, aur usi named main status
       * ka color bhi update hota hai. Is se SHIPPED bar/status
       * mismatch nahi hota.
       */
      this.defaultBoardGroupOverrides = {
        ...this.defaultBoardGroupOverrides,
        [group.key]: {
          ...(this.defaultBoardGroupOverrides?.[group.key] || {}),
          color
        }
      }

      this.persistDefaultBoardGroupOverrides()

      const statusLabel =
        this.defaultStatusLabelForGroup(group.key)

      if (statusLabel) {
        const status = this.statusOptions.find(
          item =>
            String(item.label || '').toLowerCase() ===
            String(statusLabel).toLowerCase()
        )

        if (status) {
          status.color = color
        }
      }
    },

    editWorkflowGroup(group) {
      const entered = window.prompt(
        'Edit section name',
        group.label
      )

      if (!entered || !entered.trim()) {
        return
      }

      const newLabel = entered.trim().toUpperCase()

      if (!group.custom) {
        /*
         * Default section ka display name change hoga.
         * Internal key/status relation same rahegi, is liye orders
         * aur pipeline break nahi hongi.
         */
        this.defaultBoardGroupOverrides = {
          ...this.defaultBoardGroupOverrides,
          [group.key]: {
            ...(this.defaultBoardGroupOverrides?.[group.key] || {}),
            label: newLabel
          }
        }

        this.persistDefaultBoardGroupOverrides()
        return
      }

      const target = this.customBoardGroups.find(
        item => item.key === group.key
      )

      if (!target) return

      const oldLabel = target.label
      target.label = newLabel
      this.persistWorkflowGroups()

      this.statusOptions
        .filter(status => status.group === group.key)
        .forEach(status => {
          status.groupLabel = newLabel

          if (status.label === oldLabel) {
            status.label = newLabel
          }
        })

      localStorage.setItem(
        'custom_order_statuses',
        JSON.stringify(
          this.statusOptions.filter(status => status.custom)
        )
      )
    },

    deleteWorkflowGroup(group) {
      const count = this.countForGroup(group.key)

      if (count > 0) {
        alert(
          `This section has ${count} order(s). Move those orders to another section before deleting it.`
        )
        return
      }

      const confirmed = window.confirm(
        `Delete "${group.label}" section?`
      )

      if (!confirmed) return

      if (!group.custom) {
        this.defaultBoardGroupOverrides = {
          ...this.defaultBoardGroupOverrides,
          [group.key]: {
            ...(this.defaultBoardGroupOverrides?.[group.key] || {}),
            hidden: true
          }
        }

        this.persistDefaultBoardGroupOverrides()

        if (this.activeGroup === group.key) {
          this.activeGroup = this.boardGroups[0]?.key || 'all'
        }

        return
      }

      this.customBoardGroups =
        this.customBoardGroups.filter(
          item => item.key !== group.key
        )

      this.statusOptions =
        this.statusOptions.filter(
          status => status.group !== group.key
        )

      this.persistWorkflowGroups()

      localStorage.setItem(
        'custom_order_statuses',
        JSON.stringify(
          this.statusOptions.filter(status => status.custom)
        )
      )

      if (this.activeGroup === group.key) {
        this.activeGroup = this.boardGroups[0]?.key || 'all'
      }
    },

    rowFiles(order) {
      return (order.cards || [])
        .filter(card => card.type !== 'notes')
        .flatMap(card =>
          (card.files || []).map(file => ({
            ...file,
            sourceCardType: card.type,
            sourceCardTitle: card.title
          }))
        )
    },

    async openRowFile(order, file) {
      await this.selectOrder(order)

      const normalized = {
        ...file,
        isImage:
          file.isImage ??
          /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(
            file.name || ''
          )
      }

      this.previewFile = normalized
    },

    triggerRowFileUpload(order) {
      if (!this.canUploadFiles) return

      const input = document.createElement('input')
      input.type = 'file'
      input.multiple = true
      input.accept = '*/*'

      input.addEventListener(
        'change',
        async event => {
          const files = Array.from(
            event.target.files || []
          )

          if (!files.length) return

          await this.uploadRowFiles(
            order,
            files
          )
        },
        { once: true }
      )

      input.click()
    },

    isEditingCell(orderId, field) {
      return (
        this.inlineEditingCell?.orderId === Number(orderId) &&
        this.inlineEditingCell?.field === field
      )
    },

    startInlineCell(order, field, value) {
      if (!this.canCreateOrder) return

      this.inlineEditingCell = {
        orderId: Number(order.id),
        field
      }

      this.inlineEditValue = String(value || '')

      this.$nextTick(() => {
        const active = document.activeElement
        if (active?.select) active.select()
      })
    },

    cancelInlineCell() {
      this.inlineEditingCell = null
      this.inlineEditValue = ''
    },

    async saveInlineCell(order, field) {
      if (!this.isEditingCell(order.id, field)) return

      const value = String(this.inlineEditValue || '').trim()

      if (field === 'name' && !value) {
        return
      }

      try {
        await axios.put(
          `/api/orders/${order.id}`,
          {
            [field]: value
          },
          {
            headers: this.headers()
          }
        )

        if (field === 'name') {
          order.name = value.toUpperCase()
        } else {
          order[field] = value
        }

        this.cancelInlineCell()
      } catch (error) {
        console.error('Inline cell save error:', error)

        alert(
          error.response?.data?.message ||
          'Value could not be saved.'
        )
      }
    },

    async savePackingInline(order, event, fromBlur = false) {
      const input = event?.target
      if (!input || !order?.id) return

      const value = String(input.value || '').trim()
      const oldValue = this.packingDetailText(order)

      // Blur after Enter should not make a duplicate request.
      if (fromBlur && value === oldValue) {
        return
      }

      const saved = await this.saveDirectInlineField(
        order,
        'packing_detail',
        value
      )

      if (!saved) {
        input.value = oldValue
        return
      }

      order.packing_detail = value
      order.packingDetail = value
      input.value = value

      if (!fromBlur) {
        input.blur()
      }
    },

    async saveDirectInlineField(order, field, value) {
      if (!this.canEditWorkflowFields) return false

      try {
        await axios.put(
          `/api/orders/${order.id}`,
          {
            [field]: value
          },
          {
            headers: this.headers()
          }
        )

        if (field === 'shipping_address') {
          order.shippingAddress = value
        } else if (field === 'packing_detail') {
          order.packingDetail = value
          order.packing_detail = value
        } else {
          order[field] = value
        }

        if (
          this.selectedOrder &&
          Number(this.selectedOrder.id) === Number(order.id)
        ) {
          if (field === 'shipping_address') {
            this.selectedOrder.shippingAddress = value
          } else if (field === 'packing_detail') {
            this.selectedOrder.packingDetail = value
            this.selectedOrder.packing_detail = value
          } else {
            this.selectedOrder[field] = value
          }
        }

        return true
      } catch (error) {
        console.error('Direct inline save error:', error)

        alert(
          error.response?.data?.message ||
          'Value could not be saved.'
        )

        return false
      }
    },

    async inlineChangeStatus(order, label) {
      if (!this.canEditWorkflowFields) return

      const status = this.statusOptions.find(
        item => item.label === label
      )

      if (!status) return

      try {
        await axios.put(
          `/api/orders/${order.id}`,
          {
            status: status.label,
            status_color: status.color
          },
          {
            headers: this.headers()
          }
        )

        order.status = status.label
        order.statusColor = status.color
        order.group = this.statusToGroup(status.label)

        if (
          this.selectedOrder &&
          Number(this.selectedOrder.id) === Number(order.id)
        ) {
          this.selectedOrder.status = status.label
          this.selectedOrder.statusColor = status.color
          this.selectedOrder.group = order.group
        }
      } catch (error) {
        console.error('Inline status error:', error)

        alert(
          error.response?.data?.message ||
          'Status could not be updated.'
        )
      }
    },

    openSingleOrderMembers(order) {
      this.selectedOrders = [order.id]

      const ownerIds = (order.owners || []).map(
        owner => Number(owner.id)
      )

      this.bulkSelectedMembers =
        this.availableMembers.filter(
          member =>
            ownerIds.includes(Number(member.id))
        )

      this.bulkMembersModal = true
    },

    openBulkClientsPrompt() {
      if (!this.selectedOrders.length) return

      const available = this.availableClients
        .map(client => `${client.id}: ${client.name}`)
        .join('\n')

      const entered = window.prompt(
        `Enter client ID.\n\nAvailable clients:\n${available}`
      )

      if (!entered) return

      const clientId = Number(entered)

      if (!clientId) {
        alert('Invalid client ID.')
        return
      }

      this.bulkAssignClient(clientId)
    },

    async bulkAssignClient(clientId) {
      try {
        const selectedClient = this.availableClients.find(
          client => Number(client.id) === Number(clientId)
        )

        if (!selectedClient) {
          alert('Client not found.')
          return
        }

        await Promise.all(
          this.selectedOrders.map(async orderId => {
            const order = this.orders.find(
              item => Number(item.id) === Number(orderId)
            )

            const currentClientIds = (order?.clients || []).map(
              client => Number(client.id)
            )

            if (!currentClientIds.includes(Number(clientId))) {
              currentClientIds.push(Number(clientId))
            }

            await axios.put(
              `/api/orders/${orderId}`,
              {
                client_ids: currentClientIds
              },
              {
                headers: this.headers()
              }
            )
          })
        )

        await this.fetchOrders()
      } catch (error) {
        console.error('Bulk client assign error:', error)

        alert(
          error.response?.data?.message ||
          'Client could not be assigned.'
        )
      }
    },

    printVisibleOrders() {
      /*
       * Checkbox selection ho to sirf selected orders.
       * Selection na ho to current visible/filter orders.
       */
      if (this.selectedOrders.length > 0) {
        this.printSelectedOrders()
        return
      }

      this.printOrders(this.filteredOrders)
    },

    printSelectedOrders() {
      const selectedIds = this.selectedOrders.map(Number)

      const orders = this.orders.filter(
        order => selectedIds.includes(Number(order.id))
      )

      this.printOrders(orders)
    },

    printOrderCreator(order) {
      /*
       * Preferred: actual order creator.
       * Purane API response mein creator na ho to:
       * created_by member ya pehla assigned owner fallback hoga.
       */
      const directCreator =
        order?.creator ||
        order?.created_by_user ||
        order?.createdBy ||
        null

      if (directCreator) {
        return {
          id: directCreator.id || directCreator.user_id || null,
          name:
            directCreator.name ||
            directCreator.full_name ||
            'Order Creator',
          profile_photo_url:
            directCreator.profile_photo_url ||
            directCreator.photo_url ||
            directCreator.avatar ||
            null
        }
      }

      const members = order?.owners || order?.members || []

      const creatorMember = members.find(member => {
        return (
          member?.pivot?.role === 'creator' ||
          member?.role === 'creator' ||
          Number(member?.id) === Number(order?.created_by)
        )
      })

      const fallback = creatorMember || members[0] || null

      return {
        id: fallback?.id || null,
        name: fallback?.name || 'Unassigned',
        profile_photo_url:
          fallback?.profile_photo_url ||
          fallback?.photo_url ||
          null
      }
    },

    absolutePrintImageUrl(url) {
      const value = String(url || '').trim()

      if (!value) {
        return ''
      }

      if (
        value.startsWith('http://') ||
        value.startsWith('https://') ||
        value.startsWith('data:')
      ) {
        return value
      }

      if (value.startsWith('/')) {
        return `${window.location.origin}${value}`
      }

      return `${window.location.origin}/${value}`
    },

    splitPrintOrders(orders, pageSize = 30) {
      const pages = []

      for (let index = 0; index < orders.length; index += pageSize) {
        pages.push(orders.slice(index, index + pageSize))
      }

      return pages
    },

    waitForPrintImages(printWindow, timeout = 2500) {
      return new Promise(resolve => {
        const images = Array.from(
          printWindow.document.images || []
        )

        if (!images.length) {
          resolve()
          return
        }

        let finished = 0
        let resolved = false

        const done = () => {
          finished += 1

          if (!resolved && finished >= images.length) {
            resolved = true
            resolve()
          }
        }

        images.forEach(image => {
          if (image.complete) {
            done()
            return
          }

          image.addEventListener('load', done, { once: true })
          image.addEventListener('error', done, { once: true })
        })

        setTimeout(() => {
          if (!resolved) {
            resolved = true
            resolve()
          }
        }, timeout)
      })
    },

    async printOrders(orders) {
      if (!orders.length) {
        alert('No orders available for print.')
        return
      }

      /*
       * Window foran open hoti hai taake browser popup block na kare.
       * Heavy report/summary/images remove kiye gaye hain for fast load.
       */
      const printWindow = window.open(
        '',
        '_blank',
        'width=1200,height=850'
      )

      if (!printWindow) {
        alert('Please allow popups for printing.')
        return
      }

      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset="UTF-8" />
            <title>Preparing Prosix Orders...</title>
            <style>
              body {
                margin: 0;
                min-height: 100vh;
                font-family: Arial, Helvetica, sans-serif;
                display: grid;
                place-items: center;
                background: #ffffff;
                color: #111827;
              }

              .loading-print {
                text-align: center;
              }

              .loading-print strong,
              .loading-print span {
                display: block;
              }

              .loading-print span {
                margin-top: 8px;
                color: #6b7280;
                font-size: 12px;
              }


/* ===========================
   CHAT NOTIFICATION BELL
   =========================== */
.chat-notification-wrap {
  position: relative;
  flex: 0 0 auto;
}

.chat-notification-button {
  position: relative;
  width: 40px;
  height: 36px;
  padding: 0;
  border: 1px solid #0f172a;
  border-radius: 18px;
  background: #ffffff;
  color: #0f172a;
  cursor: pointer;
  display: grid;
  place-items: center;
  font-size: 14px;
  transition: transform .15s ease, background .15s ease;
}

.chat-notification-button:hover {
  transform: translateY(-1px);
  background: #f8fafc;
}

.chat-notification-count {
  position: absolute;
  top: -7px;
  right: -7px;
  min-width: 19px;
  height: 19px;
  padding: 0 5px;
  border: 2px solid #ffffff;
  border-radius: 999px;
  background: #ef4444;
  color: #ffffff;
  font-size: 9px;
  font-weight: 900;
  line-height: 15px;
  text-align: center;
}

.chat-notification-dropdown {
  position: absolute;
  top: calc(100% + 9px);
  right: 0;
  z-index: 10000;
  width: 340px;
  max-height: 390px;
  overflow-y: auto;
  border: 1px solid #dbe2ea;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 18px 45px rgba(15, 23, 42, .16);
}

.chat-notification-head {
  position: sticky;
  top: 0;
  z-index: 2;
  padding: 11px 13px;
  border-bottom: 1px solid #e8edf3;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chat-notification-head strong {
  color: #0f172a;
  font-size: 12px;
  font-weight: 900;
}

.chat-notification-head span {
  color: #64748b;
  font-size: 10px;
  font-weight: 700;
}

.chat-notification-item {
  width: 100%;
  padding: 10px 12px;
  border: 0;
  border-bottom: 1px solid #eef2f7;
  background: #ffffff;
  color: #0f172a;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
}

.chat-notification-item:hover {
  background: #f8fafc;
}

.chat-notification-icon {
  flex: 0 0 34px;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #0f172a;
  color: #ffffff;
  display: grid;
  place-items: center;
  font-size: 12px;
}

.chat-notification-content {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.chat-notification-content strong {
  overflow: hidden;
  color: #0f172a;
  font-size: 11px;
  font-weight: 900;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chat-notification-content small {
  overflow: hidden;
  color: #64748b;
  font-size: 9px;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chat-notification-badge {
  flex: 0 0 auto;
  min-width: 23px;
  height: 23px;
  padding: 0 6px;
  border-radius: 999px;
  background: #ef4444;
  color: #ffffff;
  display: grid;
  place-items: center;
  font-size: 9px;
  font-weight: 900;
}

.chat-notification-empty {
  min-height: 90px;
  padding: 18px;
  color: #64748b;
  display: grid;
  place-items: center;
  gap: 7px;
  font-size: 11px;
  font-weight: 700;
}

.workflow-total-box {
  font-size: 12px;
  font-weight: 900;
}

/* Section bar must use the same color as its selected status */
.collapsed-status-bar {
  background: var(--group-color) !important;
  color: #111827;
}

/* Dark mode notification support */
.theme-dark .chat-notification-button,
.theme-dark .chat-notification-dropdown,
.theme-dark .chat-notification-head,
.theme-dark .chat-notification-item {
  background: #111827;
  color: #f8fafc;
  border-color: #334155;
}

.theme-dark .chat-notification-item:hover {
  background: #1e293b;
}

.theme-dark .chat-notification-head strong,
.theme-dark .chat-notification-content strong {
  color: #f8fafc;
}

.theme-dark .chat-notification-head span,
.theme-dark .chat-notification-content small,
.theme-dark .chat-notification-empty {
  color: #cbd5e1;
}

@media (max-width: 767px) {
  .chat-notification-dropdown {
    position: fixed;
    top: 58px;
    right: 10px;
    left: 10px;
    width: auto;
  }
}



/* =========================================================
   FINAL PIPELINE UI FIXES
   ========================================================= */

/* Toolbar right side must have enough room for Bell + Theme + Search */
.board-toolbar {
  grid-template-columns: 122px minmax(0, 1fr) auto !important;
}

/* Active/open section must use the exact pipeline/status color */
.collapsible-active-heading {
  margin-bottom: 0;
  padding-left: 14px;
  padding-right: 14px;
  border-top: 0 !important;
  border-radius: 3px 3px 0 0;
  transition: background .15s ease, color .15s ease;
}

.collapsible-active-heading h1,
.collapsible-active-heading span,
.collapsible-active-heading .section-collapse-icon {
  color: inherit !important;
}

.collapsible-active-heading .board-top-add-button {
  border: 1px solid currentColor;
}

/* Hover tools for every pipeline section */
.workflow-tab-wrap .workflow-custom-actions {
  min-width: max-content;
}

/* Keep the tab's own status color visible */
.workflow-total-box {
  background: var(--group-color);
  font-size: 12px;
  font-weight: 900;
}

/* Better active indication without replacing the section color */
.workflow-tab.active {
  box-shadow:
    0 0 0 3px color-mix(
      in srgb,
      var(--group-color),
      transparent 68%
    ) !important;
}

/* Bell sits cleanly with search controls */
.chat-notification-wrap {
  z-index: 500;
}

.chat-notification-button {
  flex: 0 0 38px;
  width: 38px;
  height: 36px;
}

.chat-notification-dropdown {
  z-index: 999999 !important;
}

/* Dropdown order name / last chat line */
.chat-notification-content strong,
.chat-notification-content small {
  max-width: 220px;
}

/* The colored collapsed bars always use their group color */
.collapsed-status-bar {
  background: var(--group-color) !important;
}

/* Bell count remains visible even with many notifications */
.chat-notification-count {
  min-width: 20px;
  max-width: 34px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 1150px) {
  .board-toolbar {
    grid-template-columns: 122px minmax(0, 1fr) !important;
  }

  .board-toolbar-actions {
    grid-column: 1 / -1;
    justify-content: flex-end;
  }
}



/* =========================================================
   PACKING DETAIL INLINE / HOVER EDITOR
   ========================================================= */
.packing-detail-cell {
  overflow: visible !important;
  position: relative;
  z-index: 8;
}

.packing-detail-wrap {
  position: relative;
  width: 100%;
  display: flex;
  justify-content: center;
}

.packing-detail-preview {
  max-width: 112px;
  min-width: 72px;
  height: 28px;
  padding: 0 8px;
  border: 1px solid transparent;
  border-radius: 6px;
  background: transparent;
  color: #334155;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  font-size: 9px;
  font-weight: 700;
  white-space: nowrap;
  transition: .15s ease;
}

.packing-detail-preview:hover {
  border-color: #d7dee8;
  background: #ffffff;
  box-shadow: 0 4px 12px rgba(15, 23, 42, .08);
}

.packing-detail-preview i {
  opacity: 0;
  font-size: 8px;
  transition: .15s ease;
}

.packing-detail-preview:hover i {
  opacity: 1;
}

.packing-empty-text {
  color: #94a3b8;
  font-weight: 600;
}

.packing-detail-tooltip {
  position: absolute;
  left: 50%;
  bottom: calc(100% + 8px);
  transform: translateX(-50%);
  z-index: 10050;
  width: max-content;
  max-width: 300px;
  padding: 8px 10px;
  border-radius: 7px;
  background: #111827;
  color: #ffffff;
  box-shadow: 0 10px 28px rgba(15, 23, 42, .2);
  font-size: 10px;
  font-weight: 600;
  line-height: 1.45;
  white-space: normal;
  word-break: break-word;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: .12s ease;
}

.packing-detail-tooltip::after {
  content: "";
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #111827;
}

.packing-detail-wrap:hover .packing-detail-tooltip {
  opacity: 1;
  visibility: visible;
}

.packing-detail-popover {
  position: absolute;
  top: calc(100% + 7px);
  left: 50%;
  transform: translateX(-50%);
  z-index: 10060;
  width: 290px;
  padding: 10px;
  border: 1px solid #dbe2ea;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 18px 42px rgba(15, 23, 42, .18);
}

.packing-popover-head {
  margin-bottom: 7px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.packing-popover-head strong {
  color: #111827;
  font-size: 11px;
  font-weight: 900;
}

.packing-popover-close {
  width: 23px;
  height: 23px;
  padding: 0;
  border: 0;
  border-radius: 5px;
  background: #f1f5f9;
  color: #475569;
  cursor: pointer;
  display: grid;
  place-items: center;
}

.packing-detail-input {
  width: 100%;
  min-height: 78px;
  padding: 8px 9px;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  outline: none;
  resize: vertical;
  color: #111827;
  background: #ffffff;
  font-size: 11px;
  line-height: 1.4;
}

.packing-detail-input:focus {
  border-color: #64748b;
  box-shadow: 0 0 0 3px rgba(100, 116, 139, .12);
}

.packing-popover-footer {
  margin-top: 7px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.packing-popover-footer small {
  color: #94a3b8;
  font-size: 9px;
  font-weight: 600;
}

.packing-save-btn {
  height: 28px;
  padding: 0 10px;
  border: 0;
  border-radius: 6px;
  background: #111827;
  color: #ffffff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 9px;
  font-weight: 800;
}

.packing-save-btn:disabled {
  opacity: .6;
  cursor: wait;
}

.theme-dark .packing-detail-preview {
  color: #e5e7eb;
}

.theme-dark .packing-detail-preview:hover,
.theme-dark .packing-detail-popover {
  border-color: #334155;
  background: #111827;
}

.theme-dark .packing-popover-head strong {
  color: #f8fafc;
}

.theme-dark .packing-detail-input {
  border-color: #475569;
  background: #0f172a;
  color: #f8fafc;
}

/* AppLayout sidebar ke andar board full available width use kare */
.factory-board-page {
  width: 100%;
  min-width: 0;
}



/* =========================================================
   ONLY REQUESTED CHANGE:
   section color = TEXT color, not full bar background
   ========================================================= */

.workflow-tab {
  background: #ffffff !important;
  color: var(--group-color) !important;
  border-color: #dfe3e8 !important;
  border-left: 4px solid var(--group-color) !important;
}

.workflow-tab-label {
  color: var(--group-color) !important;
}

.workflow-total-box {
  background: transparent !important;
  color: var(--group-color) !important;
  border-left: 1px solid #e5e7eb !important;
}

.workflow-tab.active {
  background: #ffffff !important;
  color: var(--group-color) !important;
  box-shadow: 0 0 0 2px rgba(17, 24, 39, .08) !important;
}

/* Current/open section */
.collapsible-active-heading {
  background: #ffffff !important;
  color: var(--active-section-color) !important;
  border-left: 5px solid var(--active-section-color) !important;
}

.collapsible-active-heading h1,
.collapsible-active-heading .section-collapse-icon {
  color: var(--active-section-color) !important;
}

/* Other section bars */
.collapsed-status-bar {
  background: #ffffff !important;
  color: var(--group-color) !important;
  border: 1px solid #e5e7eb !important;
  border-left: 5px solid var(--group-color) !important;
}

.collapsed-status-bar:hover {
  background: #f8f9fb !important;
}

.collapsed-status-bar strong {
  color: var(--group-color) !important;
}

.collapsed-status-bar span {
  color: var(--group-color) !important;
}



/* =========================================================
   FINAL CLEAN PIPELINE SPACING / COLORS
   ========================================================= */

/* Top tabs keep their own colors, but only as a light tint */
.workflow-tab {
  background: color-mix(
    in srgb,
    var(--group-color) 12%,
    #ffffff
  ) !important;
  color: var(--group-color) !important;
  border: 1px solid color-mix(
    in srgb,
    var(--group-color) 28%,
    #e5e7eb
  ) !important;
  border-left: 4px solid var(--group-color) !important;
  border-radius: 6px !important;
  box-shadow: none !important;
}

.workflow-tab:hover {
  background: color-mix(
    in srgb,
    var(--group-color) 18%,
    #ffffff
  ) !important;
}

.workflow-total-box {
  background: color-mix(
    in srgb,
    var(--group-color) 17%,
    #ffffff
  ) !important;
  color: var(--group-color) !important;
  border-left: 1px solid color-mix(
    in srgb,
    var(--group-color) 24%,
    #e5e7eb
  ) !important;
}

.workflow-tab.active {
  background: color-mix(
    in srgb,
    var(--group-color) 16%,
    #ffffff
  ) !important;
  box-shadow: 0 0 0 2px color-mix(
    in srgb,
    var(--group-color) 22%,
    transparent
  ) !important;
}

/* Current open category */
.collapsible-active-heading {
  background: #ffffff !important;
  color: var(--active-section-color) !important;
  border-left: 4px solid var(--active-section-color) !important;
}

.collapsible-active-heading .section-collapse-icon {
  color: var(--active-section-color) !important;
  margin-right: 10px !important;
}

/* Other categories: clean text rows with icon, no right total */
.collapsed-status-bars {
  display: flex !important;
  flex-direction: column !important;
  gap: 50px !important;
  padding-top: 22px !important;
  border-top: 1px dashed #e3e7ec !important;
}

.collapsed-status-bar {
  min-height: 24px !important;
  padding: 0 0 0 10px !important;
  border: 0 !important;
  border-left: 0 !important;
  border-radius: 0 !important;
  background: transparent !important;
  color: var(--group-color) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: flex-start !important;
  box-shadow: none !important;
}

.collapsed-status-bar:hover {
  background: transparent !important;
}

.collapsed-status-left {
  display: inline-flex !important;
  align-items: center !important;
  gap: 10px !important;
}

.collapsed-status-icon {
  width: 12px;
  color: var(--group-color) !important;
  font-size: 11px !important;
  transition: transform .15s ease;
}

.collapsed-status-bar:hover .collapsed-status-icon {
  transform: translateX(2px);
}

.collapsed-status-bar strong {
  color: var(--group-color) !important;
  font-size: 16px !important;
  font-weight: 800 !important;
  font-style: italic !important;
}

/* Any old right-side total span is hidden defensively */
.collapsed-status-bar > span:not(.collapsed-status-left) {
  display: none !important;
}



/* =========================================================
   SPACING + CHEVRON ALIGNMENT ONLY
   ========================================================= */

/* More space between each category/section */
.collapsed-status-bars {
  gap: 65px !important;
  padding-top: 30px !important;
}

/* Keep every collapsed category on exactly the same left line */
.collapsed-status-bar {
  padding-left: 27px !important;
  margin: 0 !important;
}

.collapsed-status-left {
  display: grid !important;
  grid-template-columns: 18px auto !important;
  align-items: center !important;
  column-gap: 10px !important;
}

.collapsed-status-icon {
  width: 18px !important;
  min-width: 18px !important;
  margin: 0 !important;
  padding: 0 !important;
  text-align: center !important;
}

.collapsed-status-bar strong {
  margin: 0 !important;
}

/* Active/open heading icon uses the exact same icon column */
.collapsible-active-heading {
  padding-left: 27px !important;
}

.collapsible-active-heading > div:first-child {
  display: grid !important;
  grid-template-columns: 18px auto auto !important;
  align-items: center !important;
  column-gap: 10px !important;
}

.collapsible-active-heading .section-collapse-icon {
  width: 18px !important;
  min-width: 18px !important;
  margin: 0 !important;
  padding: 0 !important;
  text-align: center !important;
}

/* 20px vertical space between individual order rows */
.board-table-body {
  background: #ffffff !important;
}

.board-table-row {
  margin-bottom: 20px !important;
  border-bottom: 0 !important;
  box-shadow: 0 1px 0 #e5e7eb !important;
}

.board-table-row:last-child {
  margin-bottom: 0 !important;
}



/* =========================================================
   EXCEL-LIKE RESIZABLE BOARD COLUMNS
   ========================================================= */

/* white background + black grid lines */
.board-table-shell {
  background: #ffffff !important;
  border: 1px solid #111827 !important;
  overflow-x: auto !important;
  overflow-y: visible !important;
}

.board-table-head {
  background: #ffffff !important;
  color: #111827 !important;
  border-bottom: 1px solid #111827 !important;
}

.board-table-row,
.board-inline-add-row {
  background: #ffffff !important;
}

/* Dynamic widths come from :style="boardGridStyle" */
.board-table-head,
.board-table-row,
.board-inline-add-row {
  min-width: max-content !important;
  width: max-content !important;
  grid-template-columns: unset;
}

/* Full vertical pipelines on every column boundary */
.board-table-head .board-col,
.board-table-row .board-col,
.board-inline-add-row .board-col {
  position: relative !important;
  min-width: 0 !important;
  border-right: 1px solid #111827 !important;
  background: #ffffff !important;
}

.board-table-head .board-col:last-child,
.board-table-row .board-col:last-child,
.board-inline-add-row .board-col:last-child {
  border-right: 0 !important;
}

/* Remove old short gray separator pseudo-lines */
.board-table-row .board-col::after,
.board-table-head .board-col::after,
.board-inline-add-row .board-col::after,
.board-table-row .board-col::before,
.board-table-head .board-col::before,
.board-inline-add-row .board-col::before {
  display: none !important;
  content: none !important;
}

/* Header resize handle */
.resizable-head-cell {
  position: relative !important;
  overflow: visible !important;
}

.column-resizer {
  position: absolute;
  top: 0;
  right: -4px;
  z-index: 120;
  width: 8px;
  height: 100%;
  cursor: col-resize;
  touch-action: none;
}

.column-resizer::before {
  content: "";
  position: absolute;
  top: 0;
  bottom: 0;
  left: 3px;
  width: 1px;
  background: #111827;
}

.column-resizer:hover::before,
body.board-column-resizing .column-resizer::before {
  width: 2px;
  left: 2px;
  background: #111827;
}

/* While dragging: Excel-like horizontal resize cursor */
body.board-column-resizing,
body.board-column-resizing * {
  cursor: col-resize !important;
  user-select: none !important;
}

/* Keep header labels centered / name left aligned */
.board-table-head .board-col {
  justify-content: center !important;
  font-weight: 700 !important;
}

.board-table-head .board-col-name {
  justify-content: flex-start !important;
}

/* Keep cells clean while width changes */
.board-col {
  overflow: hidden;
}

.board-col-name,
.board-col-address,
.board-col-track,
.board-col-packing {
  min-width: 0 !important;
}

.board-col-address input,
.board-col-track input,
.board-col-packing .packing-detail-wrap,
.board-col-payment input {
  max-width: 100% !important;
}

/* Keep row spacing requested earlier */
.board-table-row {
  margin-bottom: 20px !important;
  border-bottom: 1px solid #d1d5db !important;
}

.board-table-row:last-child {
  margin-bottom: 0 !important;
}



/* =========================================================
   CHEVRON ALIGNMENT FIX ONLY
   Open + collapsed section icons use same fixed column
   ========================================================= */

/* Collapsed rows start from exactly the same left position */
.collapsed-status-bar {
  padding-left: 27px !important;
}

/* Fixed icon column + fixed gap before title */
.collapsed-status-left {
  display: grid !important;
  grid-template-columns: 18px auto !important;
  align-items: center !important;
  column-gap: 14px !important;
  width: max-content !important;
}

.collapsed-status-icon {
  display: block !important;
  width: 18px !important;
  min-width: 18px !important;
  margin: 0 !important;
  padding: 0 !important;
  text-align: center !important;
  justify-self: center !important;
  color: #64748b !important;
}

/* Prevent > icon from touching category text */
.collapsed-status-bar strong {
  display: block !important;
  margin: 0 !important;
  padding: 0 !important;
}

/* Open section uses exactly the same 18px icon column + 14px gap */
.collapsible-active-heading {
  padding-left: 27px !important;
}

.collapsible-active-heading > div:first-child {
  display: grid !important;
  grid-template-columns: 18px auto auto !important;
  align-items: center !important;
  column-gap: 14px !important;
}

.collapsible-active-heading .section-collapse-icon {
  display: block !important;
  width: 18px !important;
  min-width: 18px !important;
  margin: 0 !important;
  padding: 0 !important;
  text-align: center !important;
  justify-self: center !important;
  color: #64748b !important;
}



/* =========================================================
   FINAL FIX — VERTICAL PIPELINES + SECTION TARTEEB
   ========================================================= */

/* ---------- SECTION CHEVRONS: ONE EXACT VERTICAL LINE ---------- */
.collapsible-active-heading {
  padding-left: 28px !important;
}

.collapsible-active-heading > div:first-child {
  display: flex !important;
  align-items: center !important;
  gap: 0 !important;
}

.section-chevron-slot {
  flex: 0 0 28px !important;
  width: 28px !important;
  min-width: 28px !important;
  height: 24px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  margin: 0 12px 0 0 !important;
  padding: 0 !important;
}

.section-chevron-slot i {
  width: 12px !important;
  min-width: 12px !important;
  margin: 0 !important;
  padding: 0 !important;
  text-align: center !important;
  line-height: 1 !important;
}

/* Open heading title starts at same X as every closed title */
.collapsible-active-heading h1 {
  margin: 0 !important;
  padding: 0 !important;
}

.collapsed-status-bars {
  padding-left: 28px !important;
}

.collapsed-status-bar {
  padding: 0 !important;
  margin: 0 !important;
}

.collapsed-status-left {
  display: flex !important;
  align-items: center !important;
  gap: 0 !important;
  width: max-content !important;
}

.collapsed-status-left .section-chevron-slot {
  flex: 0 0 28px !important;
  width: 28px !important;
  min-width: 28px !important;
  margin-right: 12px !important;
}

.collapsed-status-bar strong {
  margin: 0 !important;
  padding: 0 !important;
}

/* ---------- REAL PIPELINES: HEADER + EVERY ORDER ROW ---------- */

/* Outer table frame */
.board-table-shell {
  position: relative !important;
  background: #ffffff !important;
  border: 1px solid #111827 !important;
  border-radius: 0 !important;
  overflow-x: auto !important;
  overflow-y: visible !important;
}

/* Header and rows share EXACT same dynamic CSS grid */
.board-table-head,
.board-table-row,
.board-inline-add-row {
  display: grid !important;
  width: max-content !important;
  min-width: 100% !important;
  align-items: stretch !important;
  column-gap: 0 !important;
}

/* White header like requested */
.board-table-head {
  background: #ffffff !important;
  color: #111827 !important;
  border-bottom: 1px solid #111827 !important;
}

/* White rows */
.board-table-row,
.board-inline-add-row {
  background: #ffffff !important;
}

/* IMPORTANT:
   Every cell itself owns the vertical pipeline.
   This makes line continue for the full cell height. */
.board-table-head > .board-col,
.board-table-row > .board-col,
.board-inline-add-row > .board-col {
  position: relative !important;
  height: 100% !important;
  min-height: 100% !important;
  border-right: 1px solid #111827 !important;
  box-sizing: border-box !important;
}

/* Last info column does not need a right line because outer frame exists */
.board-table-head > .board-col:last-child,
.board-table-row > .board-col:last-child,
.board-inline-add-row > .board-col:last-child {
  border-right: 0 !important;
}

/* Kill any old pseudo separators so there is only ONE clean pipeline */
.board-table-head > .board-col::before,
.board-table-head > .board-col::after,
.board-table-row > .board-col::before,
.board-table-row > .board-col::after,
.board-inline-add-row > .board-col::before,
.board-inline-add-row > .board-col::after {
  content: none !important;
  display: none !important;
}

/* Horizontal row separation */
.board-table-row {
  border-bottom: 1px solid #d7dce2 !important;
}

/* Keep requested order spacing without breaking vertical column geometry */
.board-table-row + .board-table-row {
  margin-top: 20px !important;
}

/* Resize grab area sits ON TOP of the black pipeline */
.resizable-head-cell {
  overflow: visible !important;
}

.column-resizer {
  position: absolute !important;
  top: 0 !important;
  right: -5px !important;
  z-index: 999 !important;
  width: 10px !important;
  height: 100% !important;
  cursor: col-resize !important;
  background: transparent !important;
}

.column-resizer::before {
  content: "" !important;
  display: block !important;
  position: absolute !important;
  top: 0 !important;
  bottom: 0 !important;
  left: 4px !important;
  width: 1px !important;
  background: #111827 !important;
}

.column-resizer:hover::before,
body.board-column-resizing .column-resizer::before {
  width: 3px !important;
  left: 3px !important;
  background: #111827 !important;
}

/* Make sure fields do not paint over the pipeline */
.board-col-status,
.board-col-owner,
.board-col-files,
.board-col-packing,
.board-col-chat,
.board-col-payment,
.board-col-address,
.board-col-track {
  overflow: visible !important;
}

.board-col-name {
  overflow: hidden !important;
}

/* Keep input controls inside their own columns */
.board-col input,
.board-col select,
.board-col textarea,
.board-col button {
  max-width: 100%;
  box-sizing: border-box;
}

</style>
          </head>
          <body>
            <div class="loading-print">
              <strong>Preparing order sheet...</strong>
              <span>Please wait</span>
            </div>
          </body>
        </html>
      `)

      printWindow.document.close()

      const generatedAt = new Date().toLocaleString()
      const pages = this.splitPrintOrders(orders, 30)

      const pageMarkup = pages
        .map((pageOrders, pageIndex) => {
          const leftOrders = pageOrders.slice(0, 15)
          const rightOrders = pageOrders.slice(15, 30)

          const makeColumn = (columnOrders, startIndex) => {
            const orderRows = columnOrders
              .map((order, localIndex) => {
                const creator = this.printOrderCreator(order)
                const photoUrl = this.absolutePrintImageUrl(
                  creator.profile_photo_url
                )

                const creatorPhoto = photoUrl
                  ? `
                      <img
                        src="${this.escapePrint(photoUrl)}"
                        alt=""
                        loading="eager"
                        decoding="sync"
                        onerror="
                          this.style.display='none';
                          this.nextElementSibling.style.display='grid';
                        "
                      />
                      <span
                        class="creator-fallback"
                        style="display:none"
                      >
                        ${this.escapePrint(
                          this.initial(creator.name || 'U')
                        )}
                      </span>
                    `
                  : `
                      <span class="creator-fallback">
                        ${this.escapePrint(
                          this.initial(creator.name || 'U')
                        )}
                      </span>
                    `

                return `
                  <article class="print-order-card">
                    <span class="order-number">
                      ${startIndex + localIndex + 1}
                    </span>

                    <div class="order-main">
                      <strong class="order-name">
                        ${this.escapePrint(order.name || 'Unnamed Order')}
                      </strong>

                      <span
                        class="order-status"
                        style="
                          --status-color:${this.escapePrint(
                            order.statusColor || '#e5e7eb'
                          )};
                          --status-text:${this.escapePrint(
                            this.readableTextColor(
                              order.statusColor || '#e5e7eb'
                            )
                          )};
                        "
                      >
                        ${this.escapePrint(order.status || 'Pending')}
                      </span>
                    </div>

                    <div class="creator">
                      <span class="creator-photo">
                        ${creatorPhoto}
                      </span>

                      <span class="creator-name">
                        <small>Created By</small>
                        <strong>
                          ${this.escapePrint(creator.name || 'Unassigned')}
                        </strong>
                      </span>
                    </div>
                  </article>
                `
              })
              .join('')

            const emptyRows = Math.max(0, 15 - columnOrders.length)

            const placeholders = Array.from(
              { length: emptyRows },
              () => '<div class="empty-print-row"></div>'
            ).join('')

            return `
              <div class="print-column">
                ${orderRows}
                ${placeholders}
              </div>
            `
          }

          return `
            <section class="print-page">
              <header class="print-header">
                <div class="print-brand">
                  <strong>PROSIX SPORTS</strong>
                  <span>Factory Order Production Sheet</span>
                </div>

                <div class="print-meta">
                  <strong>
                    ${this.escapePrint(this.activeBoardGroup.label)}
                  </strong>
                  <span>
                    Page ${pageIndex + 1} of ${pages.length}
                  </span>
                  <span>
                    Generated: ${this.escapePrint(generatedAt)}
                  </span>
                </div>
              </header>

              <div class="print-columns">
                ${makeColumn(
                  leftOrders,
                  pageIndex * 30
                )}

                ${makeColumn(
                  rightOrders,
                  pageIndex * 30 + 15
                )}
              </div>

              <footer class="print-footer">
                <span>Prosix Sports — Internal Use</span>
                <strong>
                  ${pageOrders.length} orders on this page
                </strong>
              </footer>
            </section>
          `
        })
        .join('')

      printWindow.document.open()
      printWindow.document.write(`
        <!DOCTYPE html>
        <html>
          <head>
            <meta charset="UTF-8" />

            <title>Prosix Factory Orders</title>

            <style>
              @page {
                size: A4 landscape;
                margin: 6mm;
              }

              * {
                box-sizing: border-box;
              }

              html,
              body {
                margin: 0;
                padding: 0;
                background: #ffffff;
                color: #111827;
                font-family: Arial, Helvetica, sans-serif;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
              }

              .print-page {
                width: 100%;
                min-height: 190mm;
                padding: 0;
                display: flex;
                flex-direction: column;
                page-break-after: always;
                break-after: page;
              }

              .print-page:last-child {
                page-break-after: auto;
                break-after: auto;
              }

              .print-header {
                height: 14mm;
                padding: 0 1mm 2mm;
                border-bottom: 1.5px solid #111827;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
              }

              .print-brand strong,
              .print-brand span,
              .print-meta strong,
              .print-meta span {
                display: block;
              }

              .print-brand strong {
                font-size: 15px;
                letter-spacing: .06em;
              }

              .print-brand span {
                margin-top: 2px;
                color: #6b7280;
                font-size: 7px;
              }

              .print-meta {
                text-align: right;
              }

              .print-meta strong {
                font-size: 8px;
                text-transform: uppercase;
              }

              .print-meta span {
                margin-top: 1px;
                color: #6b7280;
                font-size: 6px;
              }

              .print-columns {
                flex: 1;
                padding-top: 3mm;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 4mm;
              }

              .print-column {
                min-width: 0;
                display: grid;
                grid-template-rows: repeat(15, minmax(0, 1fr));
                gap: 1.1mm;
              }

              .print-order-card,
              .empty-print-row {
                min-height: 9.2mm;
                border: 1px solid #d4d8df;
                border-radius: 2mm;
              }

              .print-order-card {
                padding: 1.2mm 1.8mm;
                background: #ffffff;
                display: grid;
                grid-template-columns: 6mm minmax(0, 1fr) 39mm;
                align-items: center;
                gap: 2mm;
                break-inside: avoid;
                page-break-inside: avoid;
              }

              .empty-print-row {
                border-color: transparent;
              }

              .order-number {
                width: 5mm;
                height: 5mm;
                border-radius: 50%;
                background: #111827;
                color: #ffffff;
                font-size: 6px;
                font-weight: 800;
                display: grid;
                place-items: center;
              }

              .order-main {
                min-width: 0;
                display: flex;
                align-items: center;
                gap: 2mm;
              }

              .order-name {
                min-width: 0;
                flex: 1;
                overflow: hidden;
                font-size: 7.4px;
                line-height: 1.1;
                text-overflow: ellipsis;
                white-space: nowrap;
              }

              .order-status {
                flex-shrink: 0;
                max-width: 25mm;
                padding: 1.2mm 2mm;
                border-radius: 999px;
                background: var(--status-color);
                color: var(--status-text);
                overflow: hidden;
                font-size: 5.8px;
                font-weight: 900;
                line-height: 1;
                text-overflow: ellipsis;
                white-space: nowrap;
              }

              .creator {
                min-width: 0;
                padding-left: 2mm;
                border-left: 1px solid #e5e7eb;
                display: flex;
                align-items: center;
                gap: 1.5mm;
              }

              .creator-photo,
              .creator-photo img,
              .creator-fallback {
                width: 6.5mm;
                height: 6.5mm;
                flex-shrink: 0;
                border-radius: 50%;
              }

              .creator-photo {
                overflow: hidden;
                background: #eef0f3;
              }

              .creator-photo img {
                display: block;
                object-fit: cover;
              }

              .creator-fallback {
                background: #111827;
                color: #ffffff;
                font-size: 6px;
                font-weight: 900;
                place-items: center;
              }

              .creator-name {
                min-width: 0;
              }

              .creator-name small,
              .creator-name strong {
                display: block;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
              }

              .creator-name small {
                color: #9ca3af;
                font-size: 4.8px;
                font-weight: 800;
                text-transform: uppercase;
              }

              .creator-name strong {
                margin-top: .7mm;
                font-size: 6.3px;
              }

              .print-footer {
                height: 8mm;
                padding: 2mm 1mm 0;
                border-top: 1px solid #d4d8df;
                color: #6b7280;
                font-size: 6px;
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
              }

              .print-footer strong {
                color: #111827;
              }

              @media screen {
                body {
                  padding: 12px;
                  background: #e5e7eb;
                }

                .print-page {
                  max-width: 297mm;
                  min-height: 210mm;
                  margin: 0 auto 14px;
                  padding: 6mm;
                  background: #ffffff;
                  box-shadow: 0 8px 25px rgba(0, 0, 0, .12);
                }
              }

              @media print {
                body {
                  background: #ffffff;
                }

                .print-page {
                  height: 198mm;
                  min-height: 198mm;
                  overflow: hidden;
                }
              }
            </style>
          </head>

          <body>
            ${pageMarkup}
          </body>
        </html>
      `)

      printWindow.document.close()

      /*
       * Browser ko document render aur creator photos load karne do.
       * Hard delay ki jagah actual image load events ka wait hota hai.
       */
      await this.waitForPrintImages(printWindow, 2500)

      printWindow.focus()

      requestAnimationFrame(() => {
        setTimeout(() => {
          printWindow.print()
        }, 120)
      })
    },

    escapePrint(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
    },

    countForGroup(groupKey) {
      if (groupKey === 'all') {
        return this.orders.length
      }

      if (groupKey === 'delivered') {
        return this.orders.filter(
          order =>
            String(order.status || '').toLowerCase() ===
            'delivered'
        ).length
      }

      return this.orders.filter(
        order => order.group === groupKey
      ).length
    },

    loadBoardGroups() {
      try {
        const saved = JSON.parse(
          localStorage.getItem(
            'custom_factory_order_groups'
          ) || '[]'
        )

        this.customBoardGroups = Array.isArray(saved)
          ? saved.map(group => ({
              ...group,
              custom: true
            }))
          : []
      } catch (error) {
        console.error('Board groups load error:', error)
        this.customBoardGroups = []
      }
    },

    addWorkflowGroup() {
      const label = window.prompt(
        'New order section name'
      )

      if (!label || !label.trim()) {
        return
      }

      const cleanLabel = label.trim().toUpperCase()

      const key = cleanLabel
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_|_$/g, '')

      if (
        this.boardGroups.some(
          group => group.key === key
        )
      ) {
        alert('This section already exists.')
        return
      }

      const palette = [
        '#8b5cf6',
        '#ec4899',
        '#06b6d4',
        '#14b8a6',
        '#f97316'
      ]

      const group = {
        key,
        label: cleanLabel,
        color:
          palette[
            this.customBoardGroups.length %
            palette.length
          ],
        icon: 'fa-solid fa-house',
        custom: true
      }

      this.customBoardGroups.push(group)

      localStorage.setItem(
        'custom_factory_order_groups',
        JSON.stringify(this.customBoardGroups)
      )

      const statusOption = {
        label: cleanLabel,
        color: group.color,
        group: key,
        groupLabel: cleanLabel,
        custom: true
      }

      this.statusOptions.push(statusOption)
      this.saveCustomStatusOption(statusOption)

      this.activeGroup = key
    },

    async openBoardOrder(order) {
      await this.selectOrder(order)
      this.detailOpen = true
    },

    closeBoardDetail() {
      this.detailOpen = false
      this.showChat = false
      this.closeAllMenus()
    },

    async openBoardChat(order) {
      await this.selectOrder(order)
      this.detailOpen = true
      this.showChat = true

      if (this.markChatRead) {
        await this.markChatRead()
      }
    },

    async openChatFromNotification(order) {
      this.showChatNotificationMenu = false
      this.activeGroup = order.group || this.activeGroup
      this.activeSectionCollapsed = false

      await this.openBoardChat(order)

      /*
       * Read hone ke baad bell dropdown aur board row dono
       * foran refresh ho jayein.
       */
      await this.fetchOrders()
    },

    async openOrderCard(order, card) {
      await this.selectOrder(order)
      this.detailOpen = true

      const selectedCard =
        this.selectedOrder?.cards?.find(
          item => item.type === card.type
        )

      if (selectedCard) {
        this.openViewAll(selectedCard)
      }
    },

    toggleRowStatusMenu(order, event) {
      if (!order?.id) return

      const id = Number(order.id)

      if (this.rowStatusMenuId === id) {
        this.rowStatusMenuId = null
        this.rowStatusMenuOrder = null
        return
      }

      const button = event?.currentTarget
      const rect = button?.getBoundingClientRect?.()

      if (!rect) return

      const width = 230
      const gap = 7
      const padding = 10

      let left = rect.left
      let top = rect.bottom + gap

      if (left + width > window.innerWidth - padding) {
        left = window.innerWidth - width - padding
      }

      if (left < padding) {
        left = padding
      }

      this.rowStatusMenuPosition = {
        top,
        left,
        width
      }

      this.rowStatusMenuOrder = order
      this.rowStatusMenuId = id
    },

    async selectRowStatus(order, status) {
      if (!order || !status?.label) return

      this.rowStatusMenuId = null
      this.rowStatusMenuOrder = null

      await this.inlineChangeStatus(
        order,
        status.label
      )
    },

    async openStatusForRow(order) {
      await this.selectOrder(order)
      this.detailOpen = true
      this.showStatusMenu = true
    },

    packingDetailText(order) {
      return String(
        order?.packing_detail ??
        order?.packingDetail ??
        ''
      ).trim()
    },

    shortPackingDetail(value) {
      const words = String(value || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)

      if (!words.length) return '—'

      if (words.length <= 2) {
        return words.join(' ')
      }

      return words.slice(0, 2).join(' ') + '...'
    },

    openPackingEditor(order) {
      this.packingEditOrderId = Number(order.id)
      this.packingEditValue = this.packingDetailText(order)

      this.$nextTick(() => {
        const textarea = document.querySelector(
          '.packing-detail-popover .packing-detail-input'
        )

        if (textarea) {
          textarea.focus()
          textarea.setSelectionRange(
            textarea.value.length,
            textarea.value.length
          )
        }
      })
    },

    closePackingEditor() {
      this.packingEditOrderId = null
      this.packingEditValue = ''
      this.packingSavingOrderId = null
    },

    async savePackingDetail(order) {
      if (!order?.id) return

      this.packingSavingOrderId = Number(order.id)

      const value = String(this.packingEditValue || '').trim()

      try {
        await axios.put(
          `/api/orders/${order.id}`,
          { notes: value },
          { headers: this.headers() }
        )

        const noteCard = order.cards?.find(
          card => card.type === 'notes'
        )

        if (noteCard) {
          noteCard.noteText = value
          noteCard.saved = true

          setTimeout(() => {
            noteCard.saved = false
          }, 1400)
        }

        /*
         * Detail panel mein same order open ho to uska notes card bhi
         * instantly sync rahe.
         */
        if (
          this.selectedOrder &&
          Number(this.selectedOrder.id) === Number(order.id)
        ) {
          const selectedNoteCard =
            this.selectedOrder.cards?.find(
              card => card.type === 'notes'
            )

          if (selectedNoteCard) {
            selectedNoteCard.noteText = value
          }
        }

        this.closePackingEditor()
      } catch (error) {
        console.error('Packing detail save error:', error)

        alert(
          error.response?.data?.message ||
          'Packing detail save nahi hua.'
        )
      } finally {
        this.packingSavingOrderId = null
      }
    },

    trackingSummary(value) {
      const list = this.parseTrackingList(value)
      const item = list?.[0]

      return item?.number || 'N/A'
    },

    startInlineOrder() {
      this.inlineAddOpen = true
      this.inlineOrderName = ''

      this.$nextTick(() => {
        const input = this.$refs.inlineOrderInput

        if (input) {
          input.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
          })

          input.focus()
        }
      })
    },

    cancelInlineOrder() {
      this.inlineAddOpen = false
      this.inlineOrderName = ''
      this.inlineOrderSaving = false
    },

    async createInlineOrder() {
      const name = this.inlineOrderName
        .trim()
        .toUpperCase()

      if (
        !name ||
        this.inlineOrderSaving ||
        !this.canCreateOrder
      ) {
        return
      }

      this.inlineOrderSaving = true

      const activeStatus =
        this.statusOptions.find(
          item => item.group === this.activeGroup
        ) ||
        this.statusOptions.find(
          item => item.label === 'Pending'
        )

      try {
        const response = await axios.post(
          '/api/orders',
          {
            name,
            po: this.generatePoNumber(),
            ship_date: null,
            status:
              activeStatus?.label || 'Pending',
            status_color:
              activeStatus?.color || '#fdab3d',
            payment: '0 % Paid',
            trk: 'N/A',
            notes: '',
            shipping_address: '',
            member_ids: [],
            client_ids: []
          },
          {
            headers: this.headers()
          }
        )

        const createdId =
          response.data?.order?.id ||
          response.data?.id

        if (
          createdId &&
          !this.newlyCreatedOrderIds.includes(
            Number(createdId)
          )
        ) {
          this.newlyCreatedOrderIds.unshift(
            Number(createdId)
          )

          localStorage.setItem(
            'factory_pinned_new_order_ids',
            JSON.stringify(this.newlyCreatedOrderIds)
          )
        }

        this.cancelInlineOrder()
        await this.fetchOrders()

        /*
         * Order create hone ke baad row list mein hi rahegi.
         * Detail panel automatically open nahi hoga.
         */
        this.selectedOrder = null
        this.detailOpen = false
      } catch (error) {
        console.error(
          'Inline order create error:',
          error
        )

        alert(
          error.response?.data?.message ||
          'Order could not be created.'
        )
      } finally {
        this.inlineOrderSaving = false
      }
    },

shortLastMessage(text) {
  if (!text) return ''
  return text.length > 28 ? text.substring(0, 28) + '...' : text
},


 async saveShippingAddress() {
  if (!this.selectedOrder) return

  try {
    await axios.put(
      `/api/orders/${this.selectedOrder.id}`,
      {
        shipping_address: this.shippingAddressEdit
      },
      {
        headers: this.headers()
      }
    )

    this.selectedOrder.shippingAddress =
      this.shippingAddressEdit

    const idx = this.orders.findIndex(
      o => Number(o.id) === Number(this.selectedOrder.id)
    )

    if (idx !== -1) {
      this.orders[idx].shippingAddress =
        this.shippingAddressEdit
    }

    this.showShippingAddressMenu = false

  } catch (e) {
    console.error(e)
    alert('Shipping address not saved ')
  }
},


shortShippingAddress(address) {
  if (!address) return 'N/A'

  const words = String(address).trim().split(/\s+/)

  if (words.length <= 4) return address

  return words.slice(0, 4).join(' ') + '...'
},


    editCustomStatus(status) {
const newName = prompt('Enter new status name', status.label)
  if (!newName || !newName.trim()) return

  status.label = newName.trim()

  localStorage.setItem(
    'custom_order_statuses',
    JSON.stringify(this.statusOptions.filter(s => s.custom))
  )
},

deleteCustomStatus(status) {
  if (!confirm('Are you sure you want to delete this custom status?')) return

  this.statusOptions = this.statusOptions.filter(s => s.label !== status.label)

  localStorage.setItem(
    'custom_order_statuses',
    JSON.stringify(this.statusOptions.filter(s => s.custom))
  )
},
  canDeleteFile(file) {
  // Client sirf view/download karega
  if (this.isClient) return false;

  return this.hasFullOrderAccess || Number(file?.senderId) === Number(this.currentUser?.id);
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

selectAllAvailableMembers() {
  if (
    this.bulkSelectedMembers.length ===
      this.availableMembers.length &&
    this.availableMembers.length
  ) {
    this.bulkSelectedMembers = []
    return
  }

  this.bulkSelectedMembers = [
    ...this.availableMembers
  ]
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
alert(e.response?.data?.message || 'Members were not updated')
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
alert(e.response?.data?.message || 'Orders were not duplicated')
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
alert(e.response?.data?.message || 'Orders were not deleted')
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
      this.openBoardOrder(order)
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
      if (!this.hasFullOrderAccess || !this.selectedOrder) return
      this.$refs.invoiceInput?.click()
    },

    async onInvoiceFileChange(event) {
      const files = Array.from(event.target.files || [])
      event.target.value = ''
      if (!files.length || !this.hasFullOrderAccess || !this.selectedOrder) return
      const formData = new FormData()
      formData.append('card_type', 'invoice_files')
      files.forEach(file => formData.append('files[]', file))
      try {
        await axios.post(`/api/orders/${this.selectedOrder.id}/files`, formData, { headers: { ...this.headers(), 'Content-Type': 'multipart/form-data' } })
        await this.fetchOrderFiles(this.selectedOrder.id)
        alert('Invoice uploaded successfully')
      } catch (e) { console.error('invoice upload error:', e); alert(e.response?.data?.message || 'Invoice was not uploaded') }
    },

 parseTracking(value) {
  const raw = (value || '').trim()
  const urlMatch = raw.match(/(https?:\/\/)?(www\.)?[a-z0-9.-]+\.[a-z]{2,}(\/\S*)?/i)
  const company = urlMatch ? urlMatch[0] : ''
  const number = company ? raw.replace(company, '').trim() : raw
  return { number, company }
},

parseTrackingList(value) {
  if (!value || value === 'N/A') return [{ number: '', company: '' }]

  try {
    const parsed = JSON.parse(value)

    if (Array.isArray(parsed)) {
      const clean = parsed
        .map(t => ({
          number: t.number || '',
          company: t.company || ''
        }))
        .filter(t => t.number || t.company)

      return clean.length ? clean : [{ number: '', company: '' }]
    }
  } catch (e) {}

  return [this.parseTracking(value)]
},

buildTrackingValue(list) {
  const clean = (list || [])
    .map(t => ({
      number: (t.number || '').trim(),
      company: (t.company || '').trim()
    }))
    .filter(t => t.number || t.company)

  return clean.length ? JSON.stringify(clean) : 'N/A'
},

openTrackingMenu() {
  if (!this.selectedOrder) return

  this.trackingEditList = this.parseTrackingList(this.selectedOrder.trk)
  this.showTrackingMenu = !this.showTrackingMenu
},

addTrackingRow() {
  this.trackingEditList.push({ number: '', company: '' })
},

removeTrackingRow(index) {
  this.trackingEditList.splice(index, 1)

  if (!this.trackingEditList.length) {
    this.trackingEditList.push({ number: '', company: '' })
  }
},

nextTracking() {
  if (this.trackingList.length <= 1) return

  this.activeTrackingIndex =
    (this.activeTrackingIndex + 1) % this.trackingList.length
},

prevTracking() {
  if (this.trackingList.length <= 1) return

  this.activeTrackingIndex =
    (this.activeTrackingIndex - 1 + this.trackingList.length) % this.trackingList.length
},

async saveTracking() {
  if (!this.selectedOrder) return

  const trk = this.buildTrackingValue(this.trackingEditList)

  try {
    await axios.put(
      `/api/orders/${this.selectedOrder.id}`,
      { trk },
      { headers: this.headers() }
    )

    this.selectedOrder.trk = trk

    const idx = this.orders.findIndex(
      o => Number(o.id) === Number(this.selectedOrder.id)
    )

    if (idx !== -1) {
      this.orders[idx].trk = trk
    }

    this.activeTrackingIndex = 0
    this.showTrackingMenu = false
  } catch (e) {
    console.error('saveTracking error:', e)
    alert(e.response?.data?.message || 'Tracking was not saved')
  }
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
      } catch (e) { console.error('openProfile error:', e); alert(e.response?.data?.message || 'Profile could not be loaded') }
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

        if (user) {
          localStorage.setItem(
            'user',
            JSON.stringify(user)
          )
        }

        this.closeProfile()
        await this.fetchMembers()
        await this.fetchOrders()
      } catch (e) { console.error('saveProfile error:', e); alert(e.response?.data?.message || 'Profile could not be saved') }
    },

  async toggleChat() {
  this.showChat = !this.showChat

  if (this.showChat && this.selectedOrder) {
    await this.markChatRead()
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
    await axios.post(
      `/api/orders/${this.selectedOrder.id}/messages/mark-read`,
      {},
      { headers: this.headers() }
    )

    this.unreadChatCount = 0

    const orderId = this.selectedOrder.id

    const idx = this.orders.findIndex(
      o => Number(o.id) === Number(orderId)
    )

    if (idx !== -1) {
      this.orders[idx].unread_chat_count = 0
    }

    if (this.selectedOrder) {
      this.selectedOrder.unread_chat_count = 0
    }

    await this.fetchMessages(orderId)
  } catch (e) {
    console.error('markChatRead error:', e)
  }
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
async fetchClients() {
  try {
    const res = await axios.get('/api/clients', { headers: this.headers() })

    console.log('CLIENTS DATA:', res.data)

    this.availableClients = Array.isArray(res.data)
      ? res.data
      : (res.data?.data || [])

    console.log('availableClients:', this.availableClients)

  } catch (e) {
    console.error('fetchClients error:', e)
  }
},
    formatOrder(order) {
      const members = order.members || []
      const status = order.status || 'Pending'
      return {
        id: order.id,
        created_at: order.created_at || null,
        user_has_seen:
          Boolean(order.user_has_seen) ||
          this.persistentSeenOrderIds.includes(Number(order.id)),
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
        clients: order.clients || [],
        shippingAddress: order.shipping_address || '',
        packing_detail: order.packing_detail || '',
        packingDetail: order.packing_detail || '',
        unread_chat_count: Number(order.unread_chat_count || 0),
        last_message_at: order.last_message_at || null,
        last_message_text: order.last_message_text || '',
        last_message_sender: order.last_message_sender || '',
        last_message_time: order.last_message_time || '',

        /*
         * Current working designer backend ki
         * order_work_sessions table se aata hai.
         */
        working_by: order.working_by || null,

        /*
         * Order creator backend se creator relation ke naam se aaye.
         * Alternate field names bhi support kiye gaye hain.
         */
        creator:
          order.creator ||
          order.created_by_user ||
          order.createdBy ||
          null,

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
      const found = this.statusOptions.find(
        item => item.label === status
      )

      if (found?.group) {
        return found.group
      }

      if (status === 'Completed') return 'completed'
      if (status === 'Shipped') return 'shipped'
      if (status === 'Delivered') return 'delivered'

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
this.trackingEditList = this.parseTrackingList(order.trk)
this.activeTrackingIndex = 0
      this.shippingAddressEdit = order.shippingAddress || ''
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

        if (
          !this.persistentSeenOrderIds.includes(
            Number(order.id)
          )
        ) {
          this.persistentSeenOrderIds.push(
            Number(order.id)
          )

          localStorage.setItem(
            'artwork_seen_order_ids',
            JSON.stringify(this.persistentSeenOrderIds)
          )
        }

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
    const res = await axios.get(`/api/orders/${orderId}/messages`, {
      headers: this.headers()
    })

    const list = Array.isArray(res.data) ? res.data : (res.data?.data || [])

    this.chatMessages = list.map(msg => {
      const createdDate = msg.created_at ? new Date(msg.created_at) : new Date()

      const files = (msg.files || []).map(f => ({
        ...this.normalizeOrderFile(f),
        cardType: f.card_type || f.cardType || 'chat_files',
        senderId: msg.user?.id || msg.user_id || f.user?.id || f.user_id || null,
        sender: msg.user?.name || f.user?.name || 'Shared file',
        senderPhoto: msg.user?.profile_photo_url || f.user?.profile_photo_url || null
      }))

      return {
        id: msg.id,
        localKey: msg.file_message_id ? `file-${msg.file_message_id}` : `msg-${msg.id}`,
        fileMessageId: msg.file_message_id || null,

        senderId: msg.user?.id || msg.user_id || null,
        sender: msg.user?.name || 'User',
        senderInitial: this.initial(msg.user?.name || 'User'),
        senderColor: this.memberColor(msg.user?.id || msg.user_id || 0),
        senderPhoto: msg.user?.profile_photo_url || null,

        time: createdDate.toLocaleTimeString([], {
          hour: '2-digit',
          minute: '2-digit'
        }),

        sortAt: createdDate.getTime(),
        editedAt: msg.edited_at || null,
        deletedEveryoneAt: msg.deleted_everyone_at || null,

        reads: msg.reads || [],
        seenBy: (msg.reads || []).map(r => ({
          id: r.user?.id || r.user_id,
          name: r.user?.name || r.name || 'User',
          readAt: r.read_at
        })),

        text: msg.deleted_everyone_at ? '' : (msg.message || ''),
        files: files,

        reply_to_id: msg.reply_to_id || null,
        reply_to: msg.reply_to || null
      }
    })
  } catch (e) {
    console.error('fetchMessages error:', e)
    this.chatMessages = []
  }
},

    addNewOrder() {
  if (!this.canCreateOrder) return

  this.editingOrderId = null
      this.openOrderMenuId = null
this.newOrder = {
  name: '',
  po: this.generatePoNumber(),
  selectedMembers: [],
  selectedClients: [],
  shippingAddress: '',
  shipDate: '',
  status: 'Pending',
  payment: '0 % Paid',
  trk: 'N/A'
}
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
        selectedClients: (order.clients || []).map(client => this.availableClients.find(c => Number(c.id) === Number(client.id)) || client),
shippingAddress: order.shippingAddress || '',
        shipDate: order.shipDateRaw || '', status: order.status || 'Pending',
        payment: order.payment || '0 % Paid', trk: order.trk === 'N/A' ? '' : (order.trk || 'N/A')
      }
      this.showAddModal = true
      this.$nextTick(() => this.$refs.orderNameInput?.focus())
    },

    getOrderNote(order) { return order.cards?.find(c => c.title === 'Notes')?.noteText || '' },

    async duplicateOrder(order) {
if (!this.hasFullOrderAccess && this.currentUser?.can_create_orders !== true) return
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
      } catch (e) { console.error('duplicateOrder error:', e); alert(e.response?.data?.message || 'Order could not be duplicated') }
    },

    async deleteOrder(order) {
if (!this.hasFullOrderAccess && this.currentUser?.can_create_orders !== true) return
      if (!confirm('Delete this order?')) return
      try {
        await axios.delete(`/api/orders/${order.id}`, { headers:  this.headers() })
        this.orders = this.orders.filter(o => o.id !== order.id)
        if (this.selectedOrder?.id === order.id) {
          this.selectedOrder = this.orders[0] || null
          if (this.selectedOrder) await this.selectOrder(this.selectedOrder)
        }
        this.openOrderMenuId = null
      } catch (e) { console.error('deleteOrder error:', e); alert(e.response?.data?.message || 'Order could not be deleted') }
    },

    async confirmAddOrder() {
  if (!this.canCreateOrder && !this.editingOrderId) return
  if (!this.newOrder.name.trim() || this.savingOrder) return
      this.savingOrder = true
      const status = this.statusOptions.find(s => s.label === this.newOrder.status)
      const payload = {
        name: this.newOrder.name, po: this.newOrder.po,
        member_ids: this.newOrder.selectedMembers.map(m => m.id),
        client_ids: this.newOrder.selectedClients.map(c => c.id),
shipping_address: this.newOrder.shippingAddress,
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
      } catch (e) { console.error('confirmAddOrder error:', e); alert(e.response?.data?.message || 'Order could not be saved') }
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
  if (this.isClient) return

  if (!this.selectedOrder) return

  const raw = event.target.value
  if (!raw) return

  try {
    await axios.put(
      `/api/orders/${this.selectedOrder.id}`,
      { ship_date: raw },
      { headers: this.headers() }
    )

    this.selectedOrder.shipDateRaw = raw
    this.selectedOrder.shipDate = this.formatDate(raw)
    this.showDatePicker = false

  } catch (e) {
    console.error('updateShipDate error:', e)
  }
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

    onDragEnter(card) {
      if (!this.canUploadFiles || !card || card.type === 'notes') return

      this.dragCounter += 1
      this.dragActiveCardType = card.type
    },

    onDragOver(event, card) {
      if (!this.canUploadFiles || !card || card.type === 'notes') return

      if (event?.dataTransfer) {
        event.dataTransfer.dropEffect = 'copy'
      }

      this.dragActiveCardType = card.type
    },

    onDragLeave(event, card) {
      if (!card) return

      this.dragCounter = Math.max(0, this.dragCounter - 1)

      const currentTarget = event?.currentTarget
      const relatedTarget = event?.relatedTarget

      /*
       * Child element ke andar move karne par overlay hide nahi hoga.
       */
      if (
        currentTarget &&
        relatedTarget &&
        currentTarget.contains(relatedTarget)
      ) {
        return
      }

      if (this.dragCounter === 0) {
        this.dragActiveCardType = null
      }
    },

    async onDrop(event, card) {
      this.dragCounter = 0
      this.dragActiveCardType = null

      if (!this.canUploadFiles || !card || card.type === 'notes') {
        return
      }

      const droppedItems = Array.from(
        event?.dataTransfer?.items || []
      )

      /*
       * Browser folder ko direct upload nahi kar sakta.
       * Folder ke andar files select/drag karni hongi.
       */
      const containsFolder = droppedItems.some(item => {
        const entry = item.webkitGetAsEntry?.()
        return entry?.isDirectory === true
      })

      if (containsFolder) {
        alert('Please folder ke andar ki files drag karein. Complete folder upload supported nahi hai.')
        return
      }

      const files = Array.from(
        event?.dataTransfer?.files || []
      ).filter(file => file && file.size >= 0)

      if (!files.length) {
        return
      }

      try {
        await this.uploadFilesToOrder(files, card.type)
      } catch (e) {
        console.error('onDrop error:', e)
        alert(
          e.response?.data?.message ||
          'File upload nahi hui'
        )
      }
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
      if (!this.hasFullOrderAccess || !this.selectedOrder?.invoiceFiles?.length) return
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

    minimumColumnWidth(key) {
      const minimums = {
        check: 36,
        name: 180,
        status: 95,
        owner: 85,
        files: 90,
        packing: 105,
        chat: 65,
        payment: 90,
        address: 130,
        track: 85,
        info: 36
      }

      return minimums[key] || 70
    },

    nextColumnKey(key) {
      const order = [
        'check',
        'name',
        'status',
        'owner',
        'files',
        'packing',
        'chat',
        'payment',
        'address',
        'track',
        'info'
      ]

      const index = order.indexOf(key)

      if (index === -1 || index >= order.length - 1) {
        return null
      }

      return order[index + 1]
    },

    startColumnResize(key, event) {
      if (!key || !event) return

      const nextKey = this.nextColumnKey(key)

      if (!nextKey) return

      this.columnResizeState = {
        key,
        nextKey,
        startX: event.clientX,
        startWidth: Number(this.columnWidths[key] || 100),
        nextStartWidth: Number(this.columnWidths[nextKey] || 100)
      }

      document.body.classList.add('board-column-resizing')
      document.addEventListener('mousemove', this.resizeBoardColumn)
      document.addEventListener('mouseup', this.stopColumnResize)
    },

    resizeBoardColumn(event) {
      if (!this.columnResizeState) return

      const {
        key,
        nextKey,
        startX,
        startWidth,
        nextStartWidth
      } = this.columnResizeState

      const requestedDelta = event.clientX - startX

      const currentMin = this.minimumColumnWidth(key)
      const nextMin = this.minimumColumnWidth(nextKey)

      /*
       * Divider move karne par:
       * left/current column barhta hai to right/next column utna hi chhota hota hai.
       * Is liye total table width SAME rehti hai aur blank space nahi banti.
       */
      const maxPositiveDelta = nextStartWidth - nextMin
      const maxNegativeDelta = -(startWidth - currentMin)

      const delta = Math.max(
        maxNegativeDelta,
        Math.min(requestedDelta, maxPositiveDelta)
      )

      this.columnWidths[key] = Math.round(startWidth + delta)
      this.columnWidths[nextKey] = Math.round(nextStartWidth - delta)
    },

    stopColumnResize() {
      if (!this.columnResizeState) return

      /*
       * Width intentionally save nahi hoti.
       * Page refresh ke baad default column widths wapas aayengi.
       */
      this.columnResizeState = null

      document.body.classList.remove('board-column-resizing')
      document.removeEventListener('mousemove', this.resizeBoardColumn)
      document.removeEventListener('mouseup', this.stopColumnResize)
    },

    closeAllMenus() {
      this.rowStatusMenuId = null
      this.rowStatusMenuOrder = null
      this.showStatusMenu = false
      this.showPaymentMenu = false
      this.showTrackingMenu = false
      this.showDatePicker = false
      this.showChatNotificationMenu = false
      this.packingEditOrderId = null
      this.packingEditValue = ''
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

  if (Notification.permission !== 'granted') {
    Notification.requestPermission()
    return
  }

  const title = notification.title || 'New Notification'
  const body = notification.message || 'New update received'

  const n = new Notification(title, {
    body: body,
    icon: '/assets/images/P LOGO WHITE.png',
    tag: 'order-' + notification.order_id
  })

  n.onclick = () => {
    window.focus()

    if (notification.order_id) {
      this.$router.push({
        path: '/orders',
        query: { order_id: notification.order_id, open_chat: 1 }
      })
    }

    n.close()
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
.order-chat-count {
  margin-left: 6px;
  background: #ff3b30;
  color: #fff !important;
  font-size: 10px;
  font-weight: 900;
  padding: 3px 7px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

.order-chat-count i {
  font-size: 10px;
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
.order-title-wrap {
  width: 100%;
  min-width: 0;
}

.order-title-line {
  display: flex;
  align-items: center;
  gap: 6px;
}

.order-last-chat {
  margin-top: 3px;
  font-size: 10px;
  font-weight: 700;
  color: #9ca3af;
  display: flex;
  align-items: center;
  gap: 4px;
  max-width: 180px;
  white-space: nowrap;
  overflow: hidden;
}

.order-last-chat span {
  overflow: hidden;
  text-overflow: ellipsis;
}

.order-last-chat small {
  color: #22c55e;
  font-size: 9px;
  margin-left: auto;
}

.order-chat-count {
  background: #22c55e;
  color: #fff !important;
  font-size: 10px;
  font-weight: 900;
  min-width: 18px;
  height: 18px;
  border-radius: 999px;
  display: inline-flex;
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

.order-dots-btn {
  opacity: 1;
}
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
  min-width: 0;
    height:100vh;
    overflow:hidden;
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
  right:100px;
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
.detail-body{
    display:flex;
    flex:1;
    min-height:0;
    overflow:hidden;
}

/* CARDS */
.cards-area{
    flex:1;
    min-width:0;
    overflow:auto;
}

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
  padding: 9px 18px;
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


.trk-slider-box {
  display: flex;
  align-items: center;
  gap: 6px;
}

.trk-arrow {
  border: 0;
  background: #f3f4f6;
  color: #111827;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  font-size: 10px;
  cursor: pointer;
}

.trk-arrow:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.tracking-dropdown-wide {
  min-width: 340px;
  max-height: 480px;
  overflow-y: auto;
}

.tracking-multi-row{
    position:relative;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:16px;
    margin-bottom:14px;
    background:#fff;
}

.tracking-close-btn{
    position:absolute;
    right:12px;
    top:10px;
    width:26px;
    height:26px;
    border:none;
    background:transparent;
    color:#ef4444;
    font-size:18px;
    cursor:pointer;
}

.tracking-close-btn:hover{
    transform:scale(1.1);
}

.tracking-add-btn{
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

.tracking-add-btn:hover{
    background:#000;
}

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
.list-head,
.list-row{
  grid-template-columns: 32px 1fr 140px 38px !important;
}

.owner-status-images{
  display:flex !important;
  align-items:center !important;
  justify-content:flex-start !important;
  gap:7px !important;
  overflow:visible !important;
    padding-left:10px !important;
}

.owner-status-badge{
  display:inline-flex !important;
  align-items:center !important;
  justify-content:center !important;
  min-width:75px !important;
  max-width:90px !important;
  color:#fff !important;
  font-size:9px !important;
  font-weight:900 !important;
  padding:4px 7px !important;
  border-radius:999px !important;
  white-space:nowrap !important;
  overflow:hidden !important;
  text-overflow:ellipsis !important;
  flex-shrink:0 !important;
}

.owner-status-images .avatar-stack{
  display:flex !important;
  align-items:center !important;
  gap:2px !important;
  flex-shrink:0 !important;
}
.status-name {
  flex: 1;
}

.status-action-btn {
  border: none;
  background: #eef2ff;
  color: #4f46e5;
  width: 24px;
  height: 24px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 11px;
}

.status-action-btn.danger {
  background: #fff0f0;
  color: #e2445c;
}
.shipping-info-item {
  max-width: 260px;
}

.shipping-value {
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sidebar-mode-btn {
  border: none;
  background: rgba(255,255,255,0.12);
  color: #fff;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
  margin-left: 8px;
}

.orders-left.sidebar-light {
  background: #ffffff !important;
  color: #000 !important;
  border-right: 1px solid #e5e7eb !important;
}

.orders-left.sidebar-light .orders-left-header,
.orders-left.sidebar-light .orders-title {
  color: #000 !important;
}

.orders-left.sidebar-light .sidebar-mode-btn {
  background: #111 !important;
  color: #fff !important;
}

.orders-left.sidebar-light .orders-tab {
  color: #555 !important;
}

.orders-left.sidebar-light .orders-tab.active {
  color: #000 !important;
  border-bottom-color: #6161ff !important;
}

.orders-left.sidebar-light .list-head {
  color: #555 !important;
  border-bottom: 1px solid #ddd !important;
}

.orders-left.sidebar-light .list-row {
  background: #fff !important;
  border-bottom: 1px solid #e5e7eb !important;
}

.orders-left.sidebar-light .list-row:hover {
  background: #f5f5f5 !important;
}

.orders-left.sidebar-light .col-task,
.orders-left.sidebar-light .col-task span {
  color: #000 !important;
  font-weight: 900 !important;
}

.orders-left.sidebar-light .order-search-box {
  background: #f3f4f6 !important;
}

.orders-left.sidebar-light .order-search-box i,
.orders-left.sidebar-light .order-search-box input {
  color: #000 !important;
}

.orders-left.sidebar-light .add-row {
  color: #000 !important;
  border-top: 1px solid #ddd !important;
}

.orders-left.sidebar-light .av {
  border-color: #fff !important;
}

.orders-left.sidebar-light .av-count {
  background: #444 !important;
  color: #fff !important;
}
.add-filter-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
}

.client-filter-select {
  flex: 1;
  height: 30px;
  border-radius: 6px;
  border: 1px solid #444;
  background: #111827;
  color: #fff;
  font-size: 12px;
  padding: 0 6px;
}
.orders-left.sidebar-light .order-dots-btn {
  opacity: 1;
  background: #f3f4f6 !important;
  color: #000 !important;
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

/* ================= DRAG AND DROP FILE UPLOAD ================= */

.card-preview-area,
.view-all-body {
  position: relative;
}

.card-preview-area.drag-drop-active,
.view-all-body.drag-drop-active {
  outline: 2px dashed #111827;
  outline-offset: -6px;
  background: rgba(17, 24, 39, 0.06);
}

.drag-drop-overlay {
  position: absolute;
  inset: 6px;
  z-index: 30;
  border: 2px dashed #111827;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.94);
  color: #111827;
  pointer-events: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  text-align: center;
  backdrop-filter: blur(3px);
}

.drag-drop-overlay i {
  font-size: 28px;
}

.drag-drop-overlay strong {
  font-size: 14px;
  font-weight: 800;
}

.drag-drop-overlay span {
  max-width: 90%;
  overflow: hidden;
  color: #6b7280;
  font-size: 12px;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drag-drop-overlay-modal {
  inset: 12px;
  min-height: 180px;
}




*,
*::before,
*::after {
  box-sizing: border-box;
}

.factory-board-page {
  min-height: 100vh;
  background: #ffffff;
  color: #141821;
  font-family: Arial, Helvetica, sans-serif;
}

/* HEADER */
.board-brand-header {
  min-height: 180px;
  padding: 25px 42px 18px;
  display: grid;
  grid-template-columns: 180px 1fr 180px;
  align-items: center;
  gap: 20px;
  background: #f4f5f8;
}

.board-brand-mark {
  position: relative;
  width: 145px;
  height: 120px;
  display: flex;
  align-items: center;
}

.board-brand-mark img {
  max-width: 145px;
  max-height: 120px;
  object-fit: contain;
}

.fallback-p {
  position: absolute;
  inset: 0;
  z-index: -1;
  font-size: 94px;
  font-weight: 1000;
  font-style: italic;
  line-height: 1;
}

.board-header-center {
  color: #a4a7ad;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: .18em;
  text-align: center;
}

.board-profile-button {
  justify-self: end;
  position: relative;
  width: 62px;
  height: 62px;
  border: 0;
  border-radius: 50%;
  background: #0f131c;
  color: #ffffff;
  cursor: pointer;
  display: grid;
  place-items: center;
  font-size: 20px;
  font-weight: 900;
}

.board-profile-photo {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.board-profile-button > i {
  position: absolute;
  left: -5px;
  bottom: -3px;
  width: 25px;
  height: 25px;
  border: 3px solid #ffffff;
  border-radius: 50%;
  background: #0f131c;
  color: #ffffff;
  display: grid;
  place-items: center;
  font-size: 10px;
}

/* TOOLBAR */
.board-toolbar {
  padding: 0 42px 22px;
  display: grid;
  grid-template-columns: 122px minmax(0, 1fr) 235px;
  align-items: center;
  gap: 10px;
}

.summary-home-card,
.workflow-tab {
  border: 0;
  cursor: pointer;
}

.summary-home-card {
  height: 40px;
  padding: 0 8px;
  border-radius: 3px;
  background: #494d54;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 8px;
}

.summary-home-card.active {
  box-shadow: 0 0 0 3px rgba(74, 144, 226, .2);
}

.summary-home-icon {
  width: 32px;
  height: 32px;
  border-radius: 2px;
  background: #4a90e2;
  display: grid;
  place-items: center;
}

.summary-home-card small,
.summary-home-card strong {
  display: block;
}

.summary-home-card small {
  font-size: 8px;
  text-transform: uppercase;
}

.summary-home-card strong {
  font-size: 14px;
}

.workflow-tabs {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  overflow-x: auto;
  padding: 3px;
}

.workflow-tab {
  position: relative;
  flex: 0 0 122px;
  height: 40px;
  padding: 0 42px 0 12px;
  border-radius: 3px;
  background: #4b4f56;
  color: #ffffff;
  text-align: left;
  overflow: hidden;
}

.workflow-tab.active {
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--group-color), transparent 70%);
}

.workflow-tab-label {
  display: block;
  overflow: hidden;
  font-size: 8px;
  font-weight: 900;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.workflow-tab strong {
  font-size: 13px;
}

.workflow-total-box {
  position: absolute;
  top: 0;
  right: 0;
  width: 34px;
  height: 40px;
  background: var(--group-color);
  display: grid;
  place-items: center;
}

.workflow-add-button {
  flex: 0 0 40px;
  width: 40px;
  height: 40px;
  border: 1px dashed #a7abb2;
  border-radius: 3px;
  background: #ffffff;
  color: #4b4f56;
  cursor: pointer;
}

.board-search {
  height: 36px;
  padding: 0 13px;
  border: 2px solid #111827;
  border-radius: 999px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.board-search input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  font-size: 12px;
}

/* BOARD */
.factory-board {
  padding: 0 42px 45px;
}

.board-section-heading {
  min-height: 60px;
  padding: 12px 8px 8px;
  border-top: 2px dashed #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.board-section-heading > div {
  display: flex;
  align-items: flex-end;
  gap: 18px;
}

.board-section-heading h1 {
  margin: 0;
  font-size: 21px;
  font-weight: 1000;
  font-style: italic;
  letter-spacing: .03em;
}

.board-section-heading span {
  margin-bottom: 3px;
  font-size: 9px;
  font-weight: 900;
}

.board-print-button {
  border: 0;
  background: transparent;
  color: #111827;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.board-print-button i {
  font-size: 26px;
}

.board-print-button small {
  font-size: 8px;
  font-weight: 900;
}

.board-table-shell {
  border: 1px solid #dfe3e8;
  background: #ffffff;
  overflow-x: auto;
}

.board-table-head,
.board-table-row,
.board-inline-add-row {
  min-width: 1210px;
  display: grid;
  grid-template-columns:
    45px
    minmax(185px, 1.6fr)
    135px
    145px
    150px
    145px
    90px
    125px
    minmax(150px, 1fr)
    110px
    42px;
  align-items: center;
}

.board-table-head {
  min-height: 42px;
  background: #4a90e2;
  color: #111827;
  font-size: 11px;
  letter-spacing: .03em;
}

.board-table-row {
  position: relative;
  min-height: 88px;
  border-bottom: 1px solid #e6e8eb;
  cursor: pointer;
  transition: background .16s ease;
}

.board-table-row:hover {
  background: #f7fbff;
}

.board-table-row.unread {
  box-shadow: inset 4px 0 #4a90e2;
}

.board-table-row.selected {
  background: #f0f7ff;
}

.board-col {
  min-width: 0;
  padding: 8px 10px;
}

.board-col-check {
  display: grid;
  place-items: center;
}

.board-col-check input {
  width: 22px;
  height: 22px;
  accent-color: #4a90e2;
}

.board-col-name {
  display: flex;
  align-items: center;
  gap: 8px;
}

.board-col-name strong {
  display: block;
  overflow: hidden;
  font-size: 13px;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.board-col-name small {
  display: block;
  margin-top: 4px;
  color: #9ca3af;
  font-size: 9px;
}

.board-new-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #4a90e2;
  flex-shrink: 0;
}

.board-status-pill,
.board-payment-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 82px;
  min-height: 24px;
  padding: 4px 12px;
  border: 1px solid rgba(0, 0, 0, .25);
  border-radius: 5px;
  color: #111827;
  font-size: 10px;
  font-weight: 800;
}

.board-status-pill {
  cursor: pointer;
}

.board-payment-pill {
  border-radius: 999px;
  background: #ffeb58;
}

.board-avatar-stack {
  display: flex;
  align-items: center;
}

.board-avatar {
  width: 29px;
  height: 29px;
  margin-left: -5px;
  border: 2px solid #ffffff;
  border-radius: 50%;
  color: #ffffff;
  cursor: pointer;
  display: grid;
  place-items: center;
  overflow: hidden;
  font-size: 10px;
  font-weight: 900;
}

.board-avatar:first-child {
  margin-left: 0;
}

.board-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.board-avatar-more {
  background: #343844;
  cursor: default;
}

.board-file-icons {
  display: flex;
  align-items: center;
  gap: 4px;
}

.board-file-icons button {
  position: relative;
  width: 25px;
  height: 29px;
  border: 0;
  background: transparent;
  color: #252a31;
  cursor: pointer;
  font-size: 17px;
}

.board-file-icons button span {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 14px;
  height: 14px;
  padding: 0 3px;
  border-radius: 999px;
  background: #4a90e2;
  color: #ffffff;
  font-size: 8px;
  display: grid;
  place-items: center;
}

.board-packing-text {
  color: #6b7280;
  font-size: 10px;
  font-weight: 700;
}

.board-chat-button,
.board-col-info button {
  border: 0;
  background: transparent;
  cursor: pointer;
}

.board-chat-button {
  position: relative;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #111827;
  color: #ffffff;
}

.board-chat-button span {
  position: absolute;
  top: -5px;
  right: -7px;
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  border: 2px solid #ffffff;
  border-radius: 999px;
  background: #4a5568;
  font-size: 8px;
  display: grid;
  place-items: center;
}

.board-col-address,
.board-col-track {
  overflow: hidden;
  color: #4b5563;
  font-size: 10px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.board-col-info button {
  color: #5f6368;
  font-size: 17px;
}

.board-empty-state {
  min-width: 1210px;
  min-height: 150px;
  color: #9ca3af;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 13px;
}

/* INLINE ADD */
.board-inline-add-row {
  min-height: 72px;
}

.board-inline-add-main {
  grid-column: 2 / -1;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.board-add-order-button {
  border: 0;
  background: transparent;
  color: #c4c7cc;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
}

.board-inline-add-main input {
  width: min(480px, 70vw);
  height: 40px;
  padding: 0 13px;
  border: 1px solid #4a90e2;
  border-radius: 3px;
  outline: 0;
  font-size: 12px;
  font-weight: 800;
}

.inline-save-button,
.inline-cancel-button {
  width: 38px;
  height: 38px;
  border: 0;
  border-radius: 4px;
  cursor: pointer;
}

.inline-save-button {
  background: #4a90e2;
  color: #ffffff;
}

.inline-cancel-button {
  background: #eef0f3;
  color: #111827;
}

/* COLLAPSED STATUS BARS */
.collapsed-status-bars {
  padding-top: 14px;
  border-top: 2px dashed #e5e7eb;
  display: grid;
  gap: 10px;
}

.collapsed-status-bar {
  min-height: 38px;
  padding: 0 30px;
  border: 0;
  background: var(--group-color);
  color: #10131a;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.collapsed-status-bar strong,
.collapsed-status-bar span {
  font-weight: 1000;
  font-style: italic;
}

.collapsed-status-bar strong {
  font-size: 18px;
}

.collapsed-status-bar span {
  font-size: 15px;
}

/* DETAIL OVERLAY */
.board-detail-overlay {
  position: fixed;
  inset: 0;
  z-index: 5000;
  padding: 22px;
  background: rgba(9, 12, 18, .72);
  display: flex;
  align-items: stretch;
  justify-content: flex-end;
}

.board-detail-panel {
  position: relative;
  width: min(1380px, calc(100vw - 44px));
  height: calc(100vh - 44px);
  overflow: auto;
  border-radius: 8px;
  background: #f6f7fb;
  box-shadow: 0 30px 90px rgba(0, 0, 0, .4);
}

.board-detail-close {
  position: absolute;
  top: 14px;
  right: 14px;
  z-index: 20;
  width: 35px;
  height: 35px;
  border: 1px solid rgba(255, 255, 255, .35);
  border-radius: 7px;
  background: rgba(0, 0, 0, .35);
  color: #ffffff;
  cursor: pointer;
}

@media (max-width: 1000px) {
  .board-brand-header {
    min-height: 120px;
    grid-template-columns: 120px 1fr 80px;
    padding: 18px;
  }

  .board-brand-mark {
    width: 100px;
    height: 80px;
  }

  .board-brand-mark img {
    max-width: 100px;
    max-height: 80px;
  }

  .board-toolbar {
    padding: 0 18px 18px;
    grid-template-columns: 110px 1fr;
  }

  .board-search {
    grid-column: 1 / -1;
  }

  .factory-board {
    padding: 0 18px 30px;
  }
}

@media (max-width: 700px) {
  .board-brand-header {
    grid-template-columns: 80px 1fr 55px;
  }

  .board-header-center {
    font-size: 8px;
  }

  .board-profile-button {
    width: 48px;
    height: 48px;
  }

  .board-toolbar {
    display: block;
  }

  .summary-home-card {
    width: 100%;
    margin-bottom: 8px;
  }

  .workflow-tabs {
    margin-bottom: 10px;
  }

  .factory-board {
    padding-inline: 10px;
  }

  .board-section-heading > div {
    align-items: flex-start;
    flex-direction: column;
    gap: 2px;
  }

  .collapsed-status-bar {
    padding: 0 14px;
  }

  .collapsed-status-bar strong {
    font-size: 14px;
  }

  .collapsed-status-bar span {
    font-size: 11px;
  }

  .board-detail-overlay {
    padding: 0;
  }

  .board-detail-panel {
    width: 100vw;
    height: 100vh;
    border-radius: 0;
  }
}



/* BULK TOOLBAR */
.board-bulk-toolbar {
  position: sticky;
  top: 8px;
  z-index: 50;
  min-height: 54px;
  margin-bottom: 10px;
  padding: 8px 12px;
  border: 1px solid #d8dde5;
  border-radius: 8px;
  background: #111827;
  color: #ffffff;
  box-shadow: 0 10px 25px rgba(15, 23, 42, .18);
  display: flex;
  align-items: center;
  gap: 8px;
  overflow-x: auto;
}

.bulk-selected-count {
  min-width: 78px;
  padding-right: 10px;
  border-right: 1px solid rgba(255,255,255,.2);
  display: flex;
  align-items: center;
  gap: 6px;
}

.bulk-selected-count strong {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #ffffff;
  color: #111827;
  display: grid;
  place-items: center;
}

.bulk-selected-count span {
  font-size: 11px;
  font-weight: 800;
}

.board-bulk-toolbar > button {
  min-height: 34px;
  padding: 0 11px;
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 6px;
  background: rgba(255,255,255,.08);
  color: #ffffff;
  cursor: pointer;
  font-size: 10px;
  font-weight: 800;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.board-bulk-toolbar > button:hover {
  background: #ffffff;
  color: #111827;
}

.board-bulk-toolbar > button.danger {
  background: #b91c1c;
}

.board-bulk-toolbar > button.clear {
  margin-left: auto;
}

/* INLINE CELLS */
.inline-cell-wrap {
  min-width: 0;
  width: 100%;
}

.inline-value-button {
  width: 100%;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  text-align: left;
  cursor: text;
}

.inline-value-button strong,
.inline-value-button small {
  pointer-events: none;
}

.board-inline-cell-input,
.board-inline-select {
  width: 100%;
  min-width: 0;
  height: 32px;
  padding: 0 7px;
  border: 1px solid transparent;
  border-radius: 4px;
  background: transparent;
  color: #111827;
  outline: none;
  font-family: inherit;
  font-size: 10px;
  font-weight: 700;
}

.board-inline-cell-input:hover,
.board-inline-cell-input:focus,
.board-inline-select:hover,
.board-inline-select:focus {
  border-color: #4a90e2;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(74, 144, 226, .1);
}

.board-inline-select.board-status-pill {
  min-width: 100px;
  cursor: pointer;
  appearance: auto;
}

.payment-input-inline {
  min-width: 90px;
  border-radius: 999px;
  background: #ffeb58;
  text-align: center;
}

.board-avatar-add {
  border: 1px dashed #aab0ba;
  background: #ffffff !important;
  color: #111827;
}


/* CUSTOM WORKFLOW CONTROLS */
.workflow-tab-wrap {
  position: relative;
  flex: 0 0 122px;
}

.workflow-tab-wrap .workflow-tab {
  width: 100%;
}

.workflow-custom-actions {
  position: absolute;
  top: -29px;
  left: 0;
  z-index: 40;
  padding: 3px;
  border: 1px solid #d8dde5;
  border-radius: 6px;
  background: #ffffff;
  box-shadow: 0 7px 18px rgba(15, 23, 42, .14);
  opacity: 0;
  visibility: hidden;
  display: flex;
  align-items: center;
  gap: 3px;
  transition: .15s ease;
}

.workflow-tab-wrap:hover .workflow-custom-actions {
  opacity: 1;
  visibility: visible;
}

.workflow-custom-actions button,
.workflow-color-action {
  position: relative;
  width: 25px;
  height: 25px;
  padding: 0;
  border: 0;
  border-radius: 4px;
  background: #eef1f5;
  color: #111827;
  cursor: pointer;
  display: grid;
  place-items: center;
  font-size: 10px;
}

.workflow-custom-actions button:hover,
.workflow-color-action:hover {
  background: #111827;
  color: #ffffff;
}

.workflow-custom-actions button.danger:hover {
  background: #b91c1c;
}

.workflow-color-action input {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

/* ROW FILE THUMBNAILS */
.board-row-files {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 5px;
}

.board-row-file-thumb,
.board-row-file-add {
  position: relative;
  flex: 0 0 31px;
  width: 31px;
  height: 31px;
  padding: 0;
  border: 1px solid #d9dee6;
  border-radius: 5px;
  background: #ffffff;
  color: #111827;
  cursor: pointer;
  overflow: hidden;
  display: grid;
  place-items: center;
}

.board-row-file-thumb:hover,
.board-row-file-add:hover {
  border-color: #4a90e2;
  box-shadow: 0 0 0 3px rgba(74, 144, 226, .12);
}

.board-row-file-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.board-row-file-thumb i {
  font-size: 15px;
}

.board-row-file-add {
  border-style: dashed;
}

.board-more-files {
  min-width: 25px;
  height: 25px;
  padding: 0 5px;
  border-radius: 999px;
  background: #111827;
  color: #ffffff;
  font-size: 8px;
  font-weight: 900;
  display: grid;
  place-items: center;
}

/* DETAIL PANEL: VIEW ONLY, CHAT STILL WORKS */
.view-only-detail .date-clickable,
.view-only-detail .status-badge,
.view-only-detail .payment-summary-badge,
.view-only-detail .trk-clickable,
.view-only-detail .info-value .fa-pen {
  pointer-events: none;
}

.view-only-detail .invoice-info-item,
.view-only-detail .notes-save-btn,
.view-only-detail .card-add-btn,
.view-only-detail .upload-small-btn,
.view-only-detail .upload-btn-big,
.view-only-detail .file-remove-btn,
.view-only-detail .remove-btn {
  display: none !important;
}

.view-only-detail .notes-textarea {
  pointer-events: none;
  background: #ffffff;
}

.view-only-detail .detail-header {
  border-radius: 8px 8px 0 0;
}

.view-only-detail .cards-grid {
  gap: 14px;
}

.view-only-detail .order-card {
  border: 1px solid #e4e7ec;
  box-shadow: 0 6px 18px rgba(15, 23, 42, .05);
}


/* COMPACT BOARD COLUMNS */
.board-table-head,
.board-table-row,
.board-inline-add-row {
  grid-template-columns:
    42px
    minmax(230px, 1.8fr)
    118px
    120px
    190px
    112px
    78px
    112px
    minmax(150px, 1fr)
    105px
    38px;
}

.board-col {
  padding-inline: 7px;
}

.board-col-name {
  overflow: hidden;
}

.board-inline-cell-input {
  text-overflow: ellipsis;
}

/* ROW FILE DRAG DROP */
.board-files-drop-zone {
  position: relative;
  min-height: 62px;
  display: flex;
  align-items: center;
}

.board-files-drop-zone.row-file-drag-active {
  outline: 2px dashed #4a90e2;
  outline-offset: -4px;
  background: #eff6ff;
}

.row-file-drop-label {
  position: absolute;
  inset: 3px;
  z-index: 20;
  border: 1px dashed #4a90e2;
  border-radius: 5px;
  background: rgba(239, 246, 255, .94);
  color: #1d4ed8;
  pointer-events: none;
  font-size: 10px;
  font-weight: 900;
  display: grid;
  place-items: center;
}

/* WORKING DESIGNER */
.working-designer-pill {
  width: max-content;
  max-width: 170px;
  margin-top: 6px;
  padding: 3px 7px 3px 4px;
  border: 1px solid #bbf7d0;
  border-radius: 999px;
  background: #f0fdf4;
  color: #166534;
  display: flex;
  align-items: center;
  gap: 4px;
}

.working-designer-pill img {
  width: 17px;
  height: 17px;
  border-radius: 50%;
  object-fit: cover;
}

.working-designer-pill strong {
  overflow: hidden;
  max-width: 85px;
  font-size: 8px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.working-designer-pill small {
  margin: 0;
  color: #16a34a;
  font-size: 7px;
}

.working-live-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, .15);
}

/* DETAIL BACK BUTTON */
.board-detail-back {
  position: absolute;
  top: 14px;
  left: 14px;
  z-index: 25;
  min-height: 35px;
  padding: 0 12px;
  border: 1px solid rgba(255,255,255,.3);
  border-radius: 7px;
  background: rgba(0,0,0,.35);
  color: #ffffff;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 10px;
  font-weight: 800;
}

/* DETAIL PIPELINE */
.detail-pipeline-strip {
  width: 100%;
  padding: 8px 15px;
  border-top: 1px solid rgba(255,255,255,.16);
  background: rgba(0,0,0,.12);
  display: flex;
  align-items: center;
  gap: 7px;
  overflow-x: auto;
}

.detail-pipeline-label {
  color: #d1d5db;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
}

.detail-pipeline-step {
  flex: 0 0 auto;
  min-height: 27px;
  padding: 0 9px;
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 999px;
  background: rgba(255,255,255,.08);
  color: #ffffff;
  cursor: pointer;
  font-size: 8px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.detail-pipeline-step > span {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--pipeline-color);
}

.detail-pipeline-step.active {
  background: var(--pipeline-color);
  color: #111827;
}


/* TOP ACTIONS */
.board-heading-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.board-top-add-button {
  min-height: 38px;
  padding: 0 16px;
  border: 0;
  border-radius: 6px;
  background: #111827;
  color: #ffffff;
  cursor: pointer;
  font-size: 11px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

/* CLEAR COLUMN BOXES */
.board-table-head .board-col,
.board-table-row .board-col {
  min-height: 100%;
  border-right: 1px solid #d9dee7;
  display: flex;
  align-items: center;
}

.board-table-head .board-col:last-child,
.board-table-row .board-col:last-child {
  border-right: 0;
}

.board-col-status,
.board-col-owner,
.board-col-files,
.board-col-packing,
.board-col-chat,
.board-col-payment,
.board-col-address,
.board-col-track,
.board-col-info {
  justify-content: center;
}

.board-col-name {
  align-items: flex-start !important;
}

/* WORKING ACTIONS */
.order-working-actions {
  margin-top: 7px;
}

.start-working-btn,
.stop-working-btn {
  min-height: 25px;
  padding: 0 8px;
  border: 0;
  border-radius: 999px;
  cursor: pointer;
  font-size: 8px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.start-working-btn {
  background: #dcfce7;
  color: #166534;
}

.stop-working-btn {
  background: #fee2e2;
  color: #991b1b;
}

.busy-working-label {
  min-height: 25px;
  padding: 0 8px;
  border-radius: 999px;
  background: #fff7ed;
  color: #9a3412;
  font-size: 8px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

/* VIEW HISTORY MODAL */
.viewed-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;
  padding: 20px;
  background: rgba(15, 23, 42, .68);
  display: flex;
  align-items: center;
  justify-content: center;
}

.viewed-modal {
  width: min(520px, 100%);
  max-height: 80vh;
  overflow-y: auto;
  border-radius: 14px;
  background: #ffffff;
  box-shadow: 0 30px 80px rgba(0,0,0,.3);
}

.viewed-modal-header {
  position: sticky;
  top: 0;
  z-index: 3;
  padding: 18px;
  border-bottom: 1px solid #e5e7eb;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.viewed-modal-header h3 {
  margin: 0;
  font-size: 18px;
}

.viewed-modal-header p {
  margin: 4px 0 0;
  color: #6b7280;
  font-size: 11px;
}

.viewed-modal-header button {
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 8px;
  background: #f3f4f6;
  cursor: pointer;
}

.viewed-person-row {
  padding: 13px 18px;
  border-bottom: 1px solid #eef0f3;
  display: flex;
  align-items: center;
  gap: 11px;
}

.viewed-avatar {
  flex: 0 0 38px;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: #111827;
  color: #ffffff;
  overflow: hidden;
  display: grid;
  place-items: center;
  font-size: 12px;
  font-weight: 900;
}

.viewed-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.viewed-person-info {
  flex: 1;
}

.viewed-person-info strong,
.viewed-person-info small {
  display: block;
}

.viewed-person-info strong {
  font-size: 12px;
}

.viewed-person-info small {
  margin-top: 3px;
  color: #6b7280;
  font-size: 9px;
}

.currently-working-badge {
  padding: 5px 8px;
  border-radius: 999px;
  background: #dcfce7;
  color: #166534;
  font-size: 8px;
  font-weight: 900;
}

.viewed-empty {
  padding: 40px 20px;
  color: #6b7280;
  text-align: center;
  font-size: 12px;
}

/* CLEAN DETAIL HEADER */
.board-detail-back {
  top: 10px;
  left: 10px;
}

.board-detail-close {
  top: 10px;
  right: 10px;
}

.detail-header {
  padding-top: 48px;
}

.detail-pipeline-strip {
  margin-top: 8px;
}


/* TOP INLINE ADD ROW */
.board-inline-add-top {
  min-height: 64px;
  border-bottom: 1px solid #e5e7eb;
  background: #f8fbff;
}

.board-inline-add-top .board-inline-add-main {
  padding-left: 10px;
}

.board-inline-add-top input {
  width: min(520px, 72vw);
  height: 40px;
  background: #ffffff;
}

/* SHORT GRAY PIPELINE SEPARATORS */
.board-table-row .board-col {
  position: relative;
  border-right: 0 !important;
}

.board-table-row .board-col:not(:last-child)::after {
  content: "";
  position: absolute;
  top: 50%;
  right: 0;
  width: 1px;
  height: 42px;
  background: #c9ced6;
  transform: translateY(-50%);
}

.board-table-head .board-col {
  border-right: 0 !important;
}

.board-table-row .board-col::before,
.board-table-head .board-col::before {
  display: none !important;
  content: none !important;
}

.board-table-row .board-col-status::after,
.board-table-row .board-col-owner::after,
.board-table-row .board-col-files::after,
.board-table-row .board-col-packing::after,
.board-table-row .board-col-chat::after,
.board-table-row .board-col-payment::after,
.board-table-row .board-col-address::after,
.board-table-row .board-col-track::after {
  background: #bfc5ce;
}

/* MEMBER SELECT MODAL */
.member-select-overlay {
  position: fixed;
  inset: 0;
  z-index: 100000;
  padding: 20px;
  background: rgba(15, 23, 42, .7);
  display: flex;
  align-items: center;
  justify-content: center;
}

.member-select-modal {
  width: min(620px, 100%);
  max-height: 86vh;
  overflow-y: auto;
  border-radius: 16px;
  background: #ffffff;
  box-shadow: 0 30px 90px rgba(0, 0, 0, .35);
}

.member-select-header {
  position: sticky;
  top: 0;
  z-index: 5;
  padding: 18px 20px;
  border-bottom: 1px solid #e5e7eb;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.member-select-header h3 {
  margin: 0;
  color: #111827;
  font-size: 19px;
}

.member-select-header p {
  margin: 4px 0 0;
  color: #6b7280;
  font-size: 11px;
}

.member-select-header > button {
  width: 35px;
  height: 35px;
  border: 0;
  border-radius: 8px;
  background: #f3f4f6;
  cursor: pointer;
}

.member-select-toolbar {
  padding: 14px 20px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.member-select-toolbar button {
  min-height: 34px;
  padding: 0 12px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  background: #ffffff;
  color: #111827;
  cursor: pointer;
  font-size: 10px;
  font-weight: 800;
}

.member-select-toolbar span {
  color: #6b7280;
  font-size: 10px;
  font-weight: 800;
}

.member-multiselect {
  margin: 0 20px;
}

.member-option-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.member-option-avatar,
.member-preview-chip > img,
.member-preview-chip > span {
  width: 31px;
  height: 31px;
  border-radius: 50%;
  background: #111827;
  color: #ffffff;
  object-fit: cover;
  display: grid;
  place-items: center;
  font-size: 9px;
  font-weight: 900;
}

.member-option-row strong,
.member-option-row small {
  display: block;
}

.member-option-row strong {
  color: #111827;
  font-size: 11px;
}

.member-option-row small {
  margin-top: 2px;
  color: #6b7280;
  font-size: 9px;
}

.member-selected-tag {
  margin: 2px;
  padding: 4px 7px;
  border-radius: 999px;
  background: #111827;
  color: #ffffff;
  font-size: 9px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.member-selected-tag button {
  padding: 0;
  border: 0;
  background: transparent;
  color: #ffffff;
  cursor: pointer;
}

.member-selected-preview {
  max-height: 190px;
  margin: 14px 20px;
  overflow-y: auto;
  display: grid;
  grid-template-columns: repeat(
    auto-fill,
    minmax(150px, 1fr)
  );
  gap: 8px;
}

.member-preview-chip {
  min-width: 0;
  padding: 7px;
  border: 1px solid #e5e7eb;
  border-radius: 9px;
  display: flex;
  align-items: center;
  gap: 7px;
}

.member-preview-chip strong {
  overflow: hidden;
  color: #111827;
  font-size: 9px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.member-select-footer {
  position: sticky;
  bottom: 0;
  padding: 14px 20px;
  border-top: 1px solid #e5e7eb;
  background: #ffffff;
  display: flex;
  justify-content: flex-end;
  gap: 9px;
}

.member-cancel-button,
.member-save-button {
  min-height: 38px;
  padding: 0 15px;
  border-radius: 7px;
  cursor: pointer;
  font-size: 10px;
  font-weight: 900;
}

.member-cancel-button {
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #111827;
}

.member-save-button {
  border: 0;
  background: #111827;
  color: #ffffff;
}


/* PIPELINE HOVER CONTROLS FIX */
.workflow-tabs {
  overflow: visible !important;
  padding-top: 34px !important;
  margin-top: -34px;
}

.workflow-tab-wrap {
  overflow: visible;
}

.workflow-custom-actions {
  top: -32px !important;
  left: 2px !important;
  z-index: 200 !important;
}

.workflow-tab-wrap:hover {
  z-index: 210;
}

/* CLIENT MODAL */
.client-avatar,
.client-preview-icon {
  background: #eef2ff !important;
  color: #3730a3 !important;
}

.client-preview-icon {
  flex: 0 0 31px;
  width: 31px;
  height: 31px;
  border-radius: 50%;
  display: grid;
  place-items: center;
}

/* CLEAN MODERN DETAIL VIEW */
.board-detail-overlay {
  padding: 18px !important;
  background: rgba(15, 23, 42, .74) !important;
  backdrop-filter: blur(6px);
}

.board-detail-panel {
  width: min(1460px, calc(100vw - 36px)) !important;
  height: calc(100vh - 36px) !important;
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 18px !important;
  background: #f5f7fb !important;
  overflow: auto;
}

.view-only-detail .detail-header {
  position: sticky;
  top: 0;
  z-index: 100;
  min-height: 112px;
  padding: 48px 20px 12px !important;
  border-radius: 18px 18px 0 0 !important;
  background:
    linear-gradient(135deg, #191b34 0%, #302f5d 100%) !important;
  box-shadow: 0 8px 28px rgba(20, 22, 45, .22);
}

.board-detail-back {
  top: 12px !important;
  left: 14px !important;
  min-height: 34px;
  background: rgba(255,255,255,.10) !important;
  border-color: rgba(255,255,255,.22) !important;
}

.board-detail-close {
  top: 12px !important;
  right: 14px !important;
}

.current-order-title {
  min-width: 0;
  max-width: 360px;
  height: 38px;
  padding: 0 12px;
  border: 1px solid rgba(255,255,255,.16);
  border-radius: 9px;
  background: rgba(255,255,255,.09);
  display: inline-flex !important;
  align-items: center;
  gap: 8px;
}

.current-order-title span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.detail-pipeline-strip {
  margin: 12px 0 0 !important;
  padding: 9px 10px !important;
  border: 1px solid rgba(255,255,255,.14) !important;
  border-radius: 10px;
  background: rgba(0,0,0,.16) !important;
}

.detail-pipeline-step {
  min-height: 30px !important;
  padding: 0 11px !important;
  border-radius: 8px !important;
}

.detail-topbar-wrapper {
  position: sticky;
  top: 112px;
  z-index: 80;
  padding: 10px 14px 0;
  background: #f5f7fb;
}

.detail-topbar {
  border: 1px solid #e0e5ed !important;
  border-radius: 11px;
  background: #ffffff;
  box-shadow: 0 5px 18px rgba(15, 23, 42, .05);
}

.detail-info-item {
  min-height: 48px;
  padding: 8px 12px !important;
  border-right: 1px solid #e9edf2 !important;
}

.detail-body {
  padding: 14px !important;
  background: #f5f7fb;
}

.cards-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  gap: 14px !important;
}

.order-card {
  overflow: hidden;
  border: 1px solid #e2e7ee !important;
  border-radius: 14px !important;
  background: #ffffff !important;
  box-shadow: 0 8px 24px rgba(15, 23, 42, .06) !important;
}

.card-preview-area {
  min-height: 190px !important;
  background:
    linear-gradient(180deg, #fafbfc 0%, #f2f5f8 100%) !important;
}

.card-files-preview {
  padding: 14px;
  display: grid !important;
  grid-template-columns: repeat(
    auto-fill,
    minmax(110px, 1fr)
  );
  gap: 10px;
}

.file-thumb {
  min-height: 100px;
  border: 1px solid #e2e7ee;
  border-radius: 10px;
  background: #ffffff;
  overflow: hidden;
}

.file-img {
  width: 100% !important;
  height: 100px !important;
  object-fit: cover !important;
  cursor: zoom-in !important;
}

.card-footer-inner {
  min-height: 48px;
  padding: 8px 11px !important;
  border-top: 1px solid #e7ebf0;
  background: #ffffff;
}

.card-title {
  font-size: 11px !important;
  font-weight: 900 !important;
}

.card-view-btn {
  min-height: 30px;
  padding: 0 9px;
  border: 1px solid #d8dee8 !important;
  border-radius: 7px !important;
  background: #ffffff !important;
}

.card-view-btn:hover {
  border-color: #111827 !important;
  background: #111827 !important;
  color: #ffffff !important;
}

.card-notes-area {
  min-height: 260px;
  background: #ffffff;
}

.notes-header {
  min-height: 44px;
  padding: 0 13px !important;
  background: #111827 !important;
  color: #ffffff !important;
}

.notes-header .text-dark {
  color: #ffffff !important;
}

.notes-textarea {
  min-height: 165px !important;
  padding: 14px !important;
  border: 0 !important;
  resize: vertical;
}

.notes-footer {
  min-height: 46px;
  padding: 8px 12px !important;
  border-top: 1px solid #e7ebf0;
  background: #f8fafc !important;
}

@media (max-width: 900px) {
  .cards-grid {
    grid-template-columns: 1fr !important;
  }

  .board-detail-overlay {
    padding: 0 !important;
  }

  .board-detail-panel {
    width: 100vw !important;
    height: 100vh !important;
    border-radius: 0 !important;
  }
}


/* OPENED / UNOPENED ORDERS */
.board-table-row.unread {
  background: #fffdf5 !important;
  box-shadow:
    inset 5px 0 #f59e0b,
    inset 0 0 0 1px rgba(245, 158, 11, .10);
}

.board-table-row.opened {
  background: #f4fbf7 !important;
  box-shadow:
    inset 5px 0 #22c55e,
    inset 0 0 0 1px rgba(34, 197, 94, .08);
}

.board-table-row.unread:hover {
  background: #fff8e6 !important;
}

.board-table-row.opened:hover {
  background: #eaf8ef !important;
}

.board-new-dot {
  background: #f59e0b !important;
}

.board-table-row.opened .board-new-dot {
  display: none;
}

/* CORRECT STATUS COLORS */
.board-inline-select.board-status-pill {
  border: 1px solid rgba(17, 24, 39, .18);
  font-weight: 900;
}

.detail-pipeline-step.active {
  border-color: var(--pipeline-color) !important;
  background: var(--pipeline-color) !important;
  color: #111827 !important;
  box-shadow:
    0 0 0 3px color-mix(
      in srgb,
      var(--pipeline-color),
      transparent 72%
    );
}

/* CLEAN DETAIL HEADER */
.clean-detail-panel {
  background: #f4f6fa !important;
}

.clean-detail-header {
  position: sticky;
  top: 0;
  z-index: 110;
  min-height: 118px;
  padding: 16px 18px 14px 170px !important;
  border-radius: 18px 18px 0 0 !important;
  background:
    linear-gradient(
      135deg,
      #171a31 0%,
      #292852 100%
    ) !important;
  display: grid;
  grid-template-columns:
    minmax(220px, 1fr)
    auto;
  align-items: center;
  gap: 12px;
}

.clean-detail-header .board-detail-back {
  top: 17px !important;
  left: 17px !important;
}

.clean-detail-order-name {
  min-width: 0;
  height: 48px;
  padding: 0 14px;
  border: 1px solid rgba(255,255,255,.16);
  border-radius: 11px;
  background: rgba(255,255,255,.08);
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 10px;
}

.clean-detail-order-name > i {
  font-size: 17px;
}

.clean-detail-order-name > div {
  min-width: 0;
}

.clean-detail-order-name small,
.clean-detail-order-name strong {
  display: block;
}

.clean-detail-order-name small {
  color: #b9bdd0;
  font-size: 8px;
  font-weight: 800;
  text-transform: uppercase;
}

.clean-detail-order-name strong {
  overflow: hidden;
  margin-top: 2px;
  font-size: 13px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.clean-detail-chat-button {
  position: relative;
  min-height: 42px;
  padding: 0 14px;
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 10px;
  background: rgba(255,255,255,.09);
  color: #ffffff;
  cursor: pointer;
  font-size: 10px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.clean-detail-chat-button.active,
.clean-detail-chat-button:hover {
  background: #ffffff;
  color: #171a31;
}

.clean-detail-chat-button strong {
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  border-radius: 999px;
  background: #ef4444;
  color: #ffffff;
  font-size: 8px;
  display: grid;
  place-items: center;
}

.clean-detail-header .detail-pipeline-strip {
  grid-column: 1 / -1;
  margin: 0 !important;
  padding: 8px !important;
  border: 1px solid rgba(255,255,255,.13);
  border-radius: 10px;
  background: rgba(0,0,0,.14) !important;
}

/* REMOVE OLD DETAIL HEADER ELEMENTS */
.clean-detail-panel .header-left-p,
.clean-detail-panel .header-center-logo,
.clean-detail-panel .header-right-icons,
.clean-detail-panel .board-detail-close,
.clean-detail-panel .user-avatar-top,
.clean-detail-panel .current-order-title {
  display: none !important;
}

/* NOTES / FILES PERMISSION LOOK */
.clean-detail-panel .notes-textarea[readonly] {
  background: #f8fafc !important;
  color: #6b7280;
  cursor: not-allowed;
}

.clean-detail-panel .card-add-btn {
  display: inline-flex;
}

.clean-detail-panel .notes-save-btn {
  display: inline-flex;
}

/* DETAIL BODY */
.clean-detail-panel .detail-topbar-wrapper {
  top: 118px !important;
}

.clean-detail-panel .detail-body {
  padding: 16px !important;
}

.clean-detail-panel .order-card {
  border-radius: 14px !important;
  box-shadow:
    0 8px 22px rgba(15, 23, 42, .06) !important;
}

/* SMALL PERMISSION NOTE */
.permission-readonly-note {
  color: #9ca3af;
  font-size: 9px;
}

@media (max-width: 800px) {
  .clean-detail-header {
    padding: 58px 12px 12px !important;
    grid-template-columns: 1fr auto;
  }

  .clean-detail-order-name {
    min-width: 0;
  }
}


/* FINAL OPENED / UNOPENED COLORS */
.board-table-row.unread {
  background: #f3f4f6 !important;
  box-shadow: inset 4px 0 #9ca3af !important;
}

.board-table-row.unread:hover {
  background: #e9edf2 !important;
}

.board-table-row.opened {
  background: #ffffff !important;
  box-shadow: inset 4px 0 #22c55e !important;
}

.board-table-row.opened:hover {
  background: #f8fafc !important;
}

.board-table-row.unread .board-new-dot {
  background: #9ca3af !important;
}

.board-table-row.opened .board-new-dot {
  display: none !important;
}

/* DETAIL SCROLL: ONLY PANEL MOVES, PAGE BEHIND STAYS FIXED */
.board-detail-overlay {
  overflow: hidden !important;
}

.board-detail-panel {
  overflow-y: auto !important;
  overscroll-behavior: contain;
}

/* REMOVE DETAIL PIPELINE AREA */
.clean-detail-header .detail-pipeline-strip,
.detail-pipeline-strip {
  display: none !important;
}

/* CLEAN HEADER AFTER PIPELINE REMOVAL */
.clean-detail-header {
  min-height: 78px !important;
  grid-template-columns: minmax(220px, 1fr) auto !important;
  padding-bottom: 14px !important;
}

.clean-detail-panel .detail-topbar-wrapper {
  top: 78px !important;
}

/* NATIVE STATUS OPTIONS */
.board-inline-select option {
  font-weight: 800;
  padding: 8px;
}


/* COLLAPSIBLE ACTIVE SECTION */
.collapsible-active-heading {
  min-height: 52px;
  padding: 8px 10px;
  cursor: pointer;
  user-select: none;
  transition: background .18s ease;
}

.collapsible-active-heading:hover {
  background: #f8fafc;
}

.collapsible-active-heading > div:first-child {
  align-items: center;
}

.section-collapse-icon {
  width: 18px;
  color: #64748b;
  font-size: 12px;
}

.collapsible-active-heading.collapsed {
  margin-bottom: 10px;
  border: 1px solid #d9dee7;
  border-radius: 7px;
  background: #f8fafc;
}

.collapsible-active-heading.collapsed h1 {
  font-size: 17px;
}

/* COMPACT TABLE TO REDUCE SCROLLING */
.board-table-row {
  min-height: 72px !important;
}

.board-table-head {
  min-height: 38px !important;
}

.board-col {
  padding-top: 5px !important;
  padding-bottom: 5px !important;
}

.board-col-name strong {
  font-size: 11px !important;
}

.board-col-name small {
  margin-top: 2px !important;
}

.order-working-actions {
  margin-top: 4px !important;
}

.start-working-btn,
.stop-working-btn,
.busy-working-label {
  min-height: 21px !important;
  font-size: 7px !important;
}

.collapsed-status-bars {
  gap: 7px !important;
}

.collapsed-status-bar {
  min-height: 34px !important;
}

/* ACTIVE SECTION STAYS VISIBLE */
.board-section-heading {
  position: sticky;
  top: 0;
  z-index: 90;
  background: #ffffff;
}


/* EXTRA COMPACT DETAIL VIEW */
.clean-detail-panel .detail-header,
.clean-detail-header {
  min-height: 66px !important;
  padding-top: 10px !important;
  padding-bottom: 10px !important;
}

.clean-detail-panel .detail-topbar-wrapper {
  top: 66px !important;
  padding-top: 7px !important;
}

.clean-detail-panel .detail-topbar {
  min-height: 42px !important;
}

.clean-detail-panel .detail-info-item {
  min-height: 42px !important;
  padding: 6px 9px !important;
}

.clean-detail-panel .detail-body {
  padding: 10px !important;
}

.clean-detail-panel .cards-grid {
  gap: 9px !important;
}

.clean-detail-panel .order-card {
  min-height: 0 !important;
  border-radius: 10px !important;
}

.clean-detail-panel .card-preview-area {
  min-height: 135px !important;
  height: 135px !important;
}

.clean-detail-panel .card-footer-inner {
  min-height: 38px !important;
  padding: 5px 8px !important;
}

.clean-detail-panel .card-title {
  font-size: 9px !important;
}

.clean-detail-panel .card-view-btn {
  min-height: 25px !important;
  padding: 0 7px !important;
  font-size: 8px !important;
}

.clean-detail-panel .card-notes-area {
  min-height: 180px !important;
}

.clean-detail-panel .notes-header {
  min-height: 36px !important;
  padding: 0 10px !important;
}

.clean-detail-panel .notes-textarea {
  min-height: 108px !important;
  padding: 10px !important;
}

.clean-detail-panel .notes-footer {
  min-height: 36px !important;
  padding: 5px 9px !important;
}

.clean-detail-panel .file-img {
  height: 78px !important;
}

.clean-detail-panel .file-thumb {
  min-height: 78px !important;
}

.clean-detail-panel .card-files-preview {
  padding: 9px !important;
  grid-template-columns:
    repeat(auto-fill, minmax(85px, 1fr)) !important;
  gap: 7px !important;
}

@media (min-width: 1100px) {
  .clean-detail-panel .cards-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr)) !important;
  }
}


/* THEME TOOLBAR */
.board-toolbar-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.theme-toggle-button {
  height: 36px;
  padding: 0 12px;
  border: 1px solid #111827;
  border-radius: 999px;
  background: #ffffff;
  color: #111827;
  cursor: pointer;
  font-size: 10px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

/* REMOVE LONG LEFT STATUS LINE */
.board-table-row,
.board-table-row.unread,
.board-table-row.opened,
.board-table-row.selected {
  box-shadow: none !important;
}

.board-table-row::before,
.board-table-row::after {
  display: none !important;
  content: none !important;
}

/* LIGHT THEME */
.theme-light {
  background: #ffffff;
  color: #111827;
}

/* DARK THEME */
.theme-dark {
  background: #0f172a;
  color: #f8fafc;
}

.theme-dark .board-brand-header,
.theme-dark .board-toolbar,
.theme-dark .factory-board,
.theme-dark .board-section-heading,
.theme-dark .board-table-shell,
.theme-dark .board-inline-add-top {
  background: #0f172a !important;
  color: #f8fafc !important;
}

.theme-dark .board-section-heading:hover,
.theme-dark .collapsible-active-heading.collapsed {
  background: #111827 !important;
}

.theme-dark .board-table-head {
  background: #1d4ed8 !important;
  color: #ffffff !important;
}

.theme-dark .board-table-row,
.theme-dark .board-table-row.opened,
.theme-dark .board-table-row.unread {
  background: #111827 !important;
  color: #f8fafc !important;
  border-bottom-color: #334155 !important;
}

.theme-dark .board-table-row:hover,
.theme-dark .board-table-row.opened:hover,
.theme-dark .board-table-row.unread:hover {
  background: #1e293b !important;
}

.theme-dark .board-inline-cell-input,
.theme-dark .board-inline-select,
.theme-dark .board-search input {
  color: #f8fafc !important;
}

.theme-dark .board-inline-cell-input:hover,
.theme-dark .board-inline-cell-input:focus,
.theme-dark .board-inline-select:hover,
.theme-dark .board-inline-select:focus {
  background: #1e293b !important;
  border-color: #60a5fa !important;
}

.theme-dark .board-search {
  border-color: #e2e8f0;
  color: #f8fafc;
}

.theme-dark .theme-toggle-button {
  border-color: #e2e8f0;
  background: #111827;
  color: #f8fafc;
}

.theme-dark .collapsed-status-bars {
  border-top-color: #334155 !important;
}

.theme-dark .board-col-address,
.theme-dark .board-col-track,
.theme-dark .design-meta,
.theme-dark .board-col-name small,
.theme-dark .subtitle {
  color: #cbd5e1 !important;
}

.theme-dark .board-row-file-thumb,
.theme-dark .board-row-file-add,
.theme-dark .board-avatar-add {
  border-color: #475569;
  background: #1e293b !important;
  color: #f8fafc;
}

.theme-dark .board-table-row .board-col:not(:last-child)::after {
  background: #475569 !important;
}


/* DARK MODE HEADER FIXES */
.theme-dark .board-brand-mark img {
  filter: brightness(0) invert(1);
}

.theme-dark .board-print-button {
  color: #ffffff !important;
}

.theme-dark .board-print-button i,
.theme-dark .board-print-button small {
  color: #ffffff !important;
}

.theme-dark .board-top-add-button {
  border: 1px solid #ffffff !important;
  background: transparent !important;
  color: #ffffff !important;
}

.theme-dark .board-top-add-button:hover {
  background: #ffffff !important;
  color: #111827 !important;
}

/* PROFILE BUTTON: TRUE FIXED CIRCLE */
.board-profile-button {
  overflow: visible !important;
}

.board-profile-button > img.board-profile-photo {
  position: absolute;
  inset: 0;
  width: 100% !important;
  height: 100% !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  object-position: center !important;
  display: block;
}

.board-profile-button > span:not(.board-profile-photo) {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  display: grid;
  place-items: center;
}

.board-profile-button > i {
  z-index: 4;
}

/* PROFILE SETTINGS */
.profile-settings-overlay {
  position: fixed;
  inset: 0;
  z-index: 120000;
  padding: 20px;
  background: rgba(15, 23, 42, .72);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
}

.profile-settings-modal {
  position: relative;
  width: min(430px, 100%);
  padding: 24px;
  border-radius: 18px;
  background: #ffffff;
  color: #111827;
  box-shadow: 0 35px 100px rgba(0, 0, 0, .35);
}

.profile-settings-close {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 9px;
  background: #f3f4f6;
  color: #111827;
  cursor: pointer;
}

.profile-settings-heading h3 {
  margin: 0;
  font-size: 20px;
}

.profile-settings-heading p {
  margin: 5px 0 0;
  color: #6b7280;
  font-size: 11px;
}

.profile-settings-photo-section {
  margin: 22px 0;
  text-align: center;
}

.profile-photo-circle {
  position: relative;
  width: 116px;
  height: 116px;
  margin: 0 auto;
  border: 4px solid #ffffff;
  border-radius: 50%;
  background: #111827;
  box-shadow:
    0 0 0 2px #d1d5db,
    0 12px 30px rgba(15, 23, 42, .18);
  overflow: hidden;
  cursor: pointer;
  display: grid;
  place-items: center;
}

.profile-photo-circle img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  object-position: center;
}

.profile-photo-circle > span {
  color: #ffffff;
  font-size: 32px;
  font-weight: 900;
}

.profile-photo-circle input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  z-index: 3;
}

.profile-photo-circle > i {
  position: absolute;
  right: 5px;
  bottom: 5px;
  z-index: 4;
  width: 30px;
  height: 30px;
  border: 3px solid #ffffff;
  border-radius: 50%;
  background: #111827;
  color: #ffffff;
  display: grid;
  place-items: center;
  font-size: 11px;
  pointer-events: none;
}

.profile-settings-photo-section small {
  display: block;
  margin-top: 10px;
  color: #6b7280;
  font-size: 10px;
}

.profile-settings-field {
  margin-top: 14px;
}

.profile-settings-field label {
  display: block;
  margin-bottom: 6px;
  color: #374151;
  font-size: 11px;
  font-weight: 800;
}

.profile-settings-field input,
.profile-settings-field textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 9px;
  background: #ffffff;
  color: #111827;
  outline: 0;
  font-family: inherit;
  font-size: 12px;
}

.profile-settings-field input:focus,
.profile-settings-field textarea:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}

.profile-settings-field input[readonly],
.profile-settings-field textarea[readonly] {
  background: #f8fafc;
  color: #64748b;
}

.profile-settings-actions {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
  gap: 9px;
}

.profile-settings-cancel,
.profile-settings-save {
  min-height: 39px;
  padding: 0 15px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 10px;
  font-weight: 900;
}

.profile-settings-cancel {
  border: 1px solid #d1d5db;
  background: #ffffff;
  color: #111827;
}

.profile-settings-save {
  border: 0;
  background: #111827;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

/* PROFILE MODAL DARK MODE */
.theme-dark .profile-settings-modal {
  background: #111827;
  color: #f8fafc;
}

.theme-dark .profile-settings-heading p,
.theme-dark .profile-settings-photo-section small,
.theme-dark .profile-settings-field label {
  color: #cbd5e1;
}

.theme-dark .profile-settings-field input,
.theme-dark .profile-settings-field textarea {
  border-color: #475569;
  background: #1e293b;
  color: #f8fafc;
}

.theme-dark .profile-settings-close {
  background: #1e293b;
  color: #ffffff;
}

.theme-dark .profile-settings-cancel {
  border-color: #475569;
  background: #1e293b;
  color: #ffffff;
}

.theme-dark .profile-settings-save {
  background: #ffffff;
  color: #111827;
}

/* PERMANENT VIEWED STATE */
.board-table-row.opened {
  background: #ffffff !important;
}

.theme-dark .board-table-row.opened {
  background: #111827 !important;
}


/* FINAL OWNER AVATAR DISPLAY */
.board-avatar-stack {
  min-width: 0;
  flex-wrap: nowrap;
}

.board-avatar {
  flex: 0 0 29px;
}

.board-avatar img {
  width: 100% !important;
  height: 100% !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  object-position: center !important;
}

.board-avatar-more {
  border: 2px solid #ffffff;
  background: #2f3542 !important;
  color: #ffffff !important;
  cursor: pointer !important;
}

.board-avatar-more:hover {
  transform: translateY(-1px);
  background: #111827 !important;
}

.board-avatar-add {
  margin-left: 3px !important;
}


/* WORK SESSION FINAL CLEANUP */
.working-designer-pill {
  max-width: 190px;
  min-height: 27px;
  padding: 3px 8px 3px 4px;
  border: 1px solid #bbf7d0;
  border-radius: 999px;
  background: #f0fdf4;
  color: #166534;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.working-designer-pill img {
  width: 21px !important;
  height: 21px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
}

.working-designer-pill strong {
  overflow: hidden;
  max-width: 90px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.working-live-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, .13);
}

.board-avatar-stack {
  gap: 2px !important;
}

.board-avatar-more,
.board-avatar-add {
  margin-left: 3px !important;
}
/* OWNER AVATARS — NEVER OVERFLOW */
.board-col-owner {
  min-width: 0;
  overflow: hidden;
}

.board-avatar-stack {
  width: 100%;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;

  display: flex;
  align-items: center;
  justify-content: flex-start;
  flex-wrap: nowrap;
  gap: 0;
}

.board-avatar {
  flex: 0 0 26px;
  width: 26px;
  height: 26px;

  margin-left: -5px;
  border: 2px solid #ffffff;
  border-radius: 50%;

  overflow: hidden;
  position: relative;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  font-size: 8px;
  font-weight: 800;
}

.board-avatar:first-child {
  margin-left: 0;
}

.board-avatar img {
  width: 100% !important;
  height: 100% !important;

  border-radius: 50% !important;
  object-fit: cover !important;
  object-position: center !important;
}

.board-avatar-more {
  flex: 0 0 28px;
  width: 28px;
  height: 28px;

  margin-left: -4px !important;

  background: #252b38 !important;
  color: #ffffff !important;

  border: 2px solid #ffffff;
  font-size: 8px;
}

.board-avatar-add {
  flex: 0 0 28px;
  width: 28px;
  height: 28px;

  margin-left: 4px !important;

  border: 1px dashed #94a3b8;
  background: #ffffff;
  color: #111827;
}

/* Show maximum 4 visible circles cleanly */
.board-avatar-stack .board-avatar:nth-child(n + 6) {
  display: none;
}

/* Dark mode */
.theme-dark .board-avatar {
  border-color: #111827;
}

.theme-dark .board-avatar-more {
  border-color: #111827;
}

.theme-dark .board-avatar-add {
  border-color: #64748b;
  background: #111827;
  color: #ffffff;
}


/* =========================================================
   ABSOLUTE FINAL OVERRIDE
   This block is intentionally LAST so old CSS cannot override it.
   ========================================================= */

/* Use the live draggable widths for header + every order row */
.board-table-head,
.board-table-row,
.board-inline-add-row {
  display: grid !important;
  grid-template-columns: var(--board-grid-columns) !important;
  width: max-content !important;
  min-width: 100% !important;
  column-gap: 0 !important;
  align-items: stretch !important;
}

/* White table/background */
.board-table-shell,
.board-table-head,
.board-table-row,
.board-inline-add-row,
.board-table-head > .board-col,
.board-table-row > .board-col,
.board-inline-add-row > .board-col {
  background: #ffffff !important;
}

/* REAL full-height vertical pipelines */
.board-table-head > .board-col,
.board-table-row > .board-col,
.board-inline-add-row > .board-col {
  position: relative !important;
  box-sizing: border-box !important;
  min-width: 0 !important;
  height: 100% !important;
  border-right: 1px solid #111111 !important;
}

/* outer right edge handled by shell */
.board-table-head > .board-col:last-child,
.board-table-row > .board-col:last-child,
.board-inline-add-row > .board-col:last-child {
  border-right: 0 !important;
}

/* Remove every old short/grey fake separator */
.board-table-head > .board-col::before,
.board-table-head > .board-col::after,
.board-table-row > .board-col::before,
.board-table-row > .board-col::after,
.board-inline-add-row > .board-col::before,
.board-inline-add-row > .board-col::after {
  content: none !important;
  display: none !important;
}

/* Header line */
.board-table-head {
  border-bottom: 1px solid #111111 !important;
}

/* Excel-style draggable separator exactly on the pipeline */
.resizable-head-cell {
  position: relative !important;
  overflow: visible !important;
}

.column-resizer {
  position: absolute !important;
  top: 0 !important;
  right: -5px !important;
  width: 10px !important;
  height: 100% !important;
  z-index: 99999 !important;
  cursor: col-resize !important;
  background: transparent !important;
}

.column-resizer::before {
  content: "" !important;
  display: block !important;
  position: absolute !important;
  top: 0 !important;
  bottom: 0 !important;
  left: 4px !important;
  width: 1px !important;
  background: #111111 !important;
}

.column-resizer:hover::before,
body.board-column-resizing .column-resizer::before {
  left: 3px !important;
  width: 3px !important;
  background: #000000 !important;
}

/* Keep content inside its resized column */
.board-col {
  min-width: 0 !important;
  box-sizing: border-box !important;
}

.board-col input,
.board-col select,
.board-col textarea,
.board-col button {
  max-width: 100% !important;
  box-sizing: border-box !important;
}

/* Keep the chevrons in one exact vertical column */
.collapsible-active-heading {
  padding-left: 28px !important;
}

.collapsible-active-heading > div:first-child,
.collapsed-status-left {
  display: flex !important;
  align-items: center !important;
  gap: 0 !important;
}

.section-chevron-slot {
  flex: 0 0 28px !important;
  width: 28px !important;
  min-width: 28px !important;
  margin: 0 12px 0 0 !important;
  padding: 0 !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.section-chevron-slot i {
  width: 12px !important;
  min-width: 12px !important;
  margin: 0 !important;
  padding: 0 !important;
  text-align: center !important;
}

.collapsed-status-bars {
  padding-left: 28px !important;
}

.collapsed-status-bar {
  padding-left: 0 !important;
}

/* Cursor while dragging */
body.board-column-resizing,
body.board-column-resizing * {
  cursor: col-resize !important;
  user-select: none !important;
}



/* =========================================================
   TEMPORARY BALANCED COLUMN RESIZE
   - divider movement never changes total table width
   - neighboring column absorbs the width
   - refresh restores defaults
   ========================================================= */

.board-table-shell {
  width: 100% !important;
}

/* Grid width is the sum of current column widths.
   Since two adjacent widths are changed inversely, it remains stable. */
.board-table-head,
.board-table-row,
.board-inline-add-row {
  grid-template-columns: var(--board-grid-columns) !important;
}

/* Prevent any extra ghost/blank column after INFO */
.board-table-head::after,
.board-table-row::after,
.board-inline-add-row::after {
  content: none !important;
  display: none !important;
}

/* Last visible column should simply end at the table edge */
.board-col-info {
  min-width: 0 !important;
}

/* Drag interaction feedback */
.column-resizer:hover::before,
body.board-column-resizing .column-resizer::before {
  background: #000000 !important;
}

/* TABLE HEADER — BLACK */
.board-table-head {
  background: #000000 !important;
  color: #ffffff !important;
}

/* Header text + icons white */
.board-table-head .board-col,
.board-table-head span,
.board-table-head strong,
.board-table-head i {
  color: #ffffff !important;
}

/* Header ki pipelines white */
.board-table-head > .board-col:not(:last-child)::after,
.board-table-head .column-resizer::before {
  background: #ffffff !important;
}

/* =========================================================
   CLEAN LAYOUT FIX — only requested visual corrections
   ========================================================= */

/* ---------- ACTIVE SECTION HEADER ---------- */
.collapsible-active-heading {
  min-height: 58px !important;
  padding: 9px 18px 9px 28px !important;
  background: #ffffff !important;
  border: 1px solid #d7dde5 !important;
  border-left: 4px solid var(--active-section-color) !important;
  border-radius: 7px !important;
  box-shadow: none !important;
}

.collapsible-active-heading > div:first-child {
  display: flex !important;
  align-items: center !important;
  gap: 0 !important;
  min-width: 0 !important;
}

.active-section-title-wrap {
  display: flex !important;
  flex-direction: column !important;
  align-items: flex-start !important;
  justify-content: center !important;
  gap: 3px !important;
  min-width: 0 !important;
}

.active-section-title-wrap h1 {
  margin: 0 !important;
  padding: 0 !important;
  line-height: 1.05 !important;
}

.active-section-meta {
  display: block !important;
  margin: 0 !important;
  padding: 0 !important;
  color: #667085 !important;
  font-size: 9px !important;
  font-weight: 700 !important;
  line-height: 1.2 !important;
  white-space: nowrap !important;
}

/* ---------- COLLAPSED CATEGORIES: clean, NO background bar ---------- */
.collapsed-status-bars {
  padding-top: 22px !important;
  padding-left: 28px !important;
  gap: 34px !important;
  border-top: 1px dashed #dfe3e8 !important;
}

.collapsed-status-bar {
  min-height: 26px !important;
  padding: 0 !important;
  margin: 0 !important;
  border: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
  justify-content: flex-start !important;
}

.collapsed-status-bar:hover {
  background: transparent !important;
}

.collapsed-status-bar strong {
  background: transparent !important;
  color: var(--group-color) !important;
  font-size: 15px !important;
  font-weight: 800 !important;
  font-style: italic !important;
}

/* Specifically prevent any green/delivered pill/bar background */
.collapsed-status-bar,
.collapsed-status-bar strong,
.collapsed-status-left {
  background-image: none !important;
}

/* ---------- CHEVRON ALIGNMENT ---------- */
.section-chevron-slot {
  flex: 0 0 28px !important;
  width: 28px !important;
  min-width: 28px !important;
  margin: 0 12px 0 0 !important;
}

.collapsed-status-left {
  display: flex !important;
  align-items: center !important;
  gap: 0 !important;
}

/* ---------- TABLE LAYOUT ---------- */
/* Keep the table width stable and avoid the ugly extra empty area */
.board-table-shell {
  width: 100% !important;
  max-width: 100% !important;
  background: #ffffff !important;
  border: 1px solid #d8dde5 !important;
  overflow-x: auto !important;
  overflow-y: visible !important;
  scrollbar-gutter: stable !important;
}

.board-table-head,
.board-table-row,
.board-inline-add-row {
  grid-template-columns: var(--board-grid-columns) !important;
  width: 100% !important;
  min-width: 0 !important;
  max-width: 100% !important;
  column-gap: 0 !important;
  align-items: stretch !important;
}

/* Header stays clean */
.board-table-head {
  background: #ffffff !important;
  color: #111827 !important;
  border-bottom: 1px solid #d8dde5 !important;
}

/* Rows */
.board-table-row,
.board-inline-add-row {
  background: #ffffff !important;
}

/*
 * Short vertical pipeline:
 * line remains in the same column boundary,
 * but does NOT touch the top/bottom of the cell.
 */
.board-table-head > .board-col,
.board-table-row > .board-col,
.board-inline-add-row > .board-col {
  position: relative !important;
  border-right: 0 !important;
  background: transparent !important;
  min-width: 0 !important;
  box-sizing: border-box !important;
}

/* one inset separator per column */
.board-table-head > .board-col:not(:last-child)::after,
.board-table-row > .board-col:not(:last-child)::after,
.board-inline-add-row > .board-col:not(:last-child)::after {
  content: "" !important;
  display: block !important;
  position: absolute !important;
  top: 8px !important;
  bottom: 8px !important;
  right: 0 !important;
  width: 1px !important;
  background: #111111 !important;
  pointer-events: none !important;
}

/* Header pipeline a little shorter */
.board-table-head > .board-col:not(:last-child)::after {
  top: 6px !important;
  bottom: 6px !important;
}

/* Keep old pseudo lines from duplicating on LEFT */
.board-table-head > .board-col::before,
.board-table-row > .board-col::before,
.board-inline-add-row > .board-col::before {
  content: none !important;
  display: none !important;
}

/* Resize handle stays exactly on the same boundary */
.column-resizer {
  position: absolute !important;
  top: 6px !important;
  bottom: 6px !important;
  right: -5px !important;
  width: 10px !important;
  height: auto !important;
  z-index: 99999 !important;
  cursor: col-resize !important;
  background: transparent !important;
}

.column-resizer::before {
  content: "" !important;
  display: block !important;
  position: absolute !important;
  top: 0 !important;
  bottom: 0 !important;
  left: 4px !important;
  width: 1px !important;
  background: #111111 !important;
}

.column-resizer:hover::before,
body.board-column-resizing .column-resizer::before {
  left: 3px !important;
  width: 3px !important;
  background: #000000 !important;
}

/* Prevent excessive row whitespace */
.board-table-row {
  margin: 0 !important;
  min-height: 66px !important;
  border-bottom: 1px solid #e5e7eb !important;
  box-shadow: none !important;
}

.board-table-row + .board-table-row {
  margin-top: 0 !important;
}

/* ---------- PRINT ICON ---------- */
.board-print-button {
  color: #111827 !important;
}

.board-print-button i,
.board-print-button small {
  color: #111827 !important;
}

/* Dark mode => printer white */
.theme-dark .board-print-button,
.theme-dark .board-print-button i,
.theme-dark .board-print-button small {
  color: #ffffff !important;
}

/* ---------- DARK MODE CLEANUP ---------- */
.theme-dark .collapsible-active-heading {
  background: #111827 !important;
  border-color: #334155 !important;
}

.theme-dark .active-section-meta {
  color: #cbd5e1 !important;
}

.theme-dark .collapsed-status-bar,
.theme-dark .collapsed-status-left,
.theme-dark .collapsed-status-bar strong {
  background: transparent !important;
}

.theme-dark .board-table-shell,
.theme-dark .board-table-head,
.theme-dark .board-table-row,
.theme-dark .board-inline-add-row {
  background: #111827 !important;
}

.theme-dark .board-table-head > .board-col:not(:last-child)::after,
.theme-dark .board-table-row > .board-col:not(:last-child)::after,
.theme-dark .board-inline-add-row > .board-col:not(:last-child)::after,
.theme-dark .column-resizer::before {
  background: #ffffff !important;
}



/* =========================================================
   FINAL ROW SPACING FIX
   - clean space between each order
   - pipelines stay inside each row
   - resize does not break layout
   ========================================================= */

/* Table shell stays stable */
.board-table-shell {
  width: 100% !important;
  max-width: 100% !important;
  overflow-x: auto !important;
  overflow-y: visible !important;
  background: #ffffff !important;
  border: 0 !important;
}

/* Header stays attached, rows use the same live column widths */
.board-table-head,
.board-table-row,
.board-inline-add-row {
  display: grid !important;
  grid-template-columns: var(--board-grid-columns) !important;
  width: 100% !important;
  min-width: 0 !important;
  max-width: 100% !important;
  column-gap: 0 !important;
  box-sizing: border-box !important;
}

/* Clean header */
.board-table-head {
  min-height: 40px !important;
  margin: 0 0 8px 0 !important;
  background: #4a90e2 !important;
  color: #111827 !important;
  border: 1px solid #d9e0e7 !important;
  border-radius: 3px !important;
}

/* Every order becomes its own clean row/card */
.board-table-row {
  min-height: 72px !important;
  margin: 0 0 8px 0 !important;
  padding: 0 !important;
  background: #f7f8fa !important;
  border: 1px solid #dfe4ea !important;
  border-radius: 3px !important;
  box-shadow: none !important;
  overflow: visible !important;
}

/* last row does not need extra bottom gap */
.board-table-row:last-child {
  margin-bottom: 0 !important;
}

/* alternate row background only slightly different */
.board-table-row:nth-of-type(even) {
  background: #ffffff !important;
}

/* keep all cells aligned vertically */
.board-table-head > .board-col,
.board-table-row > .board-col,
.board-inline-add-row > .board-col {
  position: relative !important;
  min-width: 0 !important;
  height: 100% !important;
  box-sizing: border-box !important;
  border-right: 0 !important;
  background: transparent !important;
}

/* pipelines remain short and INSIDE each row/header */
.board-table-head > .board-col:not(:last-child)::after,
.board-table-row > .board-col:not(:last-child)::after,
.board-inline-add-row > .board-col:not(:last-child)::after {
  content: "" !important;
  display: block !important;
  position: absolute !important;
  right: 0 !important;
  top: 10px !important;
  bottom: 10px !important;
  width: 1px !important;
  background: #111111 !important;
  pointer-events: none !important;
}

/* header separator is a little tighter */
.board-table-head > .board-col:not(:last-child)::after {
  top: 7px !important;
  bottom: 7px !important;
}

/* no duplicated old lines */
.board-table-head > .board-col::before,
.board-table-row > .board-col::before,
.board-inline-add-row > .board-col::before {
  content: none !important;
  display: none !important;
}

/* resize handle stays on the exact separator */
.column-resizer {
  position: absolute !important;
  top: 7px !important;
  bottom: 7px !important;
  right: -5px !important;
  width: 10px !important;
  height: auto !important;
  z-index: 99999 !important;
  cursor: col-resize !important;
  background: transparent !important;
}

.column-resizer::before {
  content: "" !important;
  display: block !important;
  position: absolute !important;
  left: 4px !important;
  top: 0 !important;
  bottom: 0 !important;
  width: 1px !important;
  background: #111111 !important;
}

/* keep content inside resized columns */
.board-col,
.board-col * {
  box-sizing: border-box;
}

.board-col input,
.board-col select,
.board-col textarea,
.board-col button {
  max-width: 100% !important;
}

/* stop long content from forcing column width */
.board-col-name,
.board-col-address,
.board-col-track,
.board-col-packing,
.board-col-payment {
  overflow: hidden !important;
}

.board-col-name strong,
.board-col-name small,
.board-col-address input,
.board-col-track input,
.board-col-packing .packing-detail-preview,
.board-col-payment input {
  min-width: 0 !important;
  text-overflow: ellipsis !important;
}

/* dark mode keeps same clean row spacing */
.theme-dark .board-table-shell {
  background: #0f172a !important;
}

.theme-dark .board-table-row {
  background: #111827 !important;
  border-color: #334155 !important;
}

.theme-dark .board-table-row:nth-of-type(even) {
  background: #0f172a !important;
}

.theme-dark .board-table-head > .board-col:not(:last-child)::after,
.theme-dark .board-table-row > .board-col:not(:last-child)::after,
.theme-dark .board-inline-add-row > .board-col:not(:last-child)::after,
.theme-dark .column-resizer::before {
  background: #ffffff !important;
}



/* =========================================================
   TRUE RESPONSIVE GRID + CLEAN PIPELINES
   ========================================================= */

/* Never create horizontal empty/overflow space */
.board-table-shell {
  width: 100% !important;
  max-width: 100% !important;
  overflow-x: hidden !important;
  overflow-y: visible !important;
  background: #ffffff !important;
}

/* Header and every row use the SAME percentage grid */
.board-table-head,
.board-table-row,
.board-inline-add-row {
  display: grid !important;
  grid-template-columns: var(--board-grid-columns) !important;
  width: 100% !important;
  min-width: 100% !important;
  max-width: 100% !important;
  column-gap: 0 !important;
  align-items: stretch !important;
  box-sizing: border-box !important;
}

/* Clean order spacing */
.board-table-head {
  min-height: 40px !important;
  margin: 0 0 8px 0 !important;
}

.board-table-row {
  min-height: 72px !important;
  margin: 0 0 8px 0 !important;
  padding: 0 !important;
  border: 1px solid #dfe4ea !important;
  border-radius: 3px !important;
  box-shadow: none !important;
  overflow: visible !important;
}

.board-table-row:last-child {
  margin-bottom: 0 !important;
}

/* Every cell stays inside its assigned column */
.board-table-head > .board-col,
.board-table-row > .board-col,
.board-inline-add-row > .board-col {
  position: relative !important;
  min-width: 0 !important;
  width: auto !important;
  max-width: none !important;
  height: 100% !important;
  box-sizing: border-box !important;
  border-right: 0 !important;
  overflow: hidden !important;
}

/* Columns that need popovers/buttons can still show vertically */
.board-col-packing,
.board-col-status,
.board-col-owner,
.board-col-files,
.board-col-chat {
  overflow: visible !important;
}

/* PIPELINES:
   same exact column boundaries in header and every order,
   shorter from top/bottom for clean look. */
.board-table-head > .board-col:not(:last-child)::after,
.board-table-row > .board-col:not(:last-child)::after,
.board-inline-add-row > .board-col:not(:last-child)::after {
  content: "" !important;
  display: block !important;
  position: absolute !important;
  right: 0 !important;
  top: 11px !important;
  bottom: 11px !important;
  width: 1px !important;
  background: #111827 !important;
  pointer-events: none !important;
  z-index: 3 !important;
}

.board-table-head > .board-col:not(:last-child)::after {
  top: 7px !important;
  bottom: 7px !important;
}

/* Kill old duplicate separators */
.board-table-head > .board-col::before,
.board-table-row > .board-col::before,
.board-inline-add-row > .board-col::before {
  content: none !important;
  display: none !important;
}

/* Resize handle exactly follows that same boundary */
.column-resizer {
  position: absolute !important;
  top: 7px !important;
  bottom: 7px !important;
  right: -5px !important;
  width: 10px !important;
  height: auto !important;
  z-index: 99999 !important;
  cursor: col-resize !important;
  background: transparent !important;
}

.column-resizer::before {
  content: "" !important;
  display: block !important;
  position: absolute !important;
  top: 0 !important;
  bottom: 0 !important;
  left: 4px !important;
  width: 1px !important;
  background: #111827 !important;
}

.column-resizer:hover::before,
body.board-column-resizing .column-resizer::before {
  left: 3px !important;
  width: 3px !important;
  background: #000000 !important;
}

/* Long content must NOT push columns wider */
.board-col-name .inline-cell-wrap,
.board-col-name .inline-value-button,
.board-col-address,
.board-col-track,
.board-col-payment,
.board-col-packing .packing-detail-wrap {
  min-width: 0 !important;
  max-width: 100% !important;
}

.board-col-name strong,
.board-col-name small,
.board-col-address input,
.board-col-track input,
.board-col-payment input,
.packing-detail-preview {
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
}

/* Keep content centered/clean after resize */
.board-col-status,
.board-col-owner,
.board-col-files,
.board-col-chat,
.board-col-payment,
.board-col-packing,
.board-col-track,
.board-col-info {
  display: flex !important;
  align-items: center !important;
}

.board-col-status,
.board-col-files,
.board-col-chat,
.board-col-payment,
.board-col-packing,
.board-col-track,
.board-col-info {
  justify-content: center !important;
}

.board-col-owner {
  justify-content: flex-start !important;
}

/* Dark mode pipelines */
.theme-dark .board-table-head > .board-col:not(:last-child)::after,
.theme-dark .board-table-row > .board-col:not(:last-child)::after,
.theme-dark .board-inline-add-row > .board-col:not(:last-child)::after,
.theme-dark .column-resizer::before {
  background: #ffffff !important;
}



/* =========================================================
   PIPELINE CENTER FIX ONLY
   Keep everything else unchanged.
   Vertical separators are centered inside header/order rows.
   ========================================================= */

/* Remove previous full/inset separator positioning */
.board-table-head > .board-col:not(:last-child)::after,
.board-table-row > .board-col:not(:last-child)::after,
.board-inline-add-row > .board-col:not(:last-child)::after {
  content: "" !important;
  position: absolute !important;
  right: 0 !important;
  top: 50% !important;
  bottom: auto !important;
  transform: translateY(-50%) !important;
  width: 1px !important;
  height: 32px !important;
  background: #111827 !important;
  pointer-events: none !important;
  z-index: 3 !important;
}

/* Header separator slightly shorter */
.board-table-head > .board-col:not(:last-child)::after {
  height: 25px !important;
}

/* Resize handle remains on same boundary, also vertically centered */
.column-resizer {
  top: 50% !important;
  bottom: auto !important;
  transform: translateY(-50%) !important;
  height: 32px !important;
}

.board-table-head .column-resizer {
  height: 25px !important;
}

.column-resizer::before {
  top: 0 !important;
  bottom: auto !important;
  height: 100% !important;
}

/* Dark mode keeps separator visible */
.theme-dark .board-table-head > .board-col:not(:last-child)::after,
.theme-dark .board-table-row > .board-col:not(:last-child)::after,
.theme-dark .board-inline-add-row > .board-col:not(:last-child)::after,
.theme-dark .column-resizer::before {
  background: #ffffff !important;
}



/* =========================================================
   SECTION BAR CLEANUP ONLY
   - active bar slightly lower
   - white background + black border
   - colored left strip on every section
   ========================================================= */

/* Active/open section bar */
.collapsible-active-heading {
  margin-top: 12px !important;
  margin-bottom: 12px !important;
  background: #ffffff !important;
  border: 1px solid #111827 !important;
  border-left: 5px solid var(--active-section-color) !important;
  border-radius: 6px !important;
  box-shadow: none !important;
}

/* Keep active title clean */
.collapsible-active-heading h1 {
  color: var(--active-section-color) !important;
}

/* Other/collapsed sections */
.collapsed-status-bars {
  gap: 34px !important;
  padding-top: 12px !important;
}

.collapsed-status-bar {
  position: relative !important;
  min-height: 42px !important;
  padding: 0 14px 0 28px !important;
  background: #ffffff !important;
  border: 1px solid #111827 !important;
  border-left: 5px solid var(--group-color) !important;
  border-radius: 6px !important;
  box-shadow: none !important;
  display: flex !important;
  align-items: center !important;
  justify-content: flex-start !important;
}

/* No colored full background */
.collapsed-status-bar:hover {
  background: #f8fafc !important;
}

.collapsed-status-left {
  background: transparent !important;
}

/* Keep each title in its own section color */
.collapsed-status-bar strong {
  color: var(--group-color) !important;
  background: transparent !important;
}

/* Chevron stays aligned */
.collapsed-status-bar .section-chevron-slot {
  margin-right: 12px !important;
}

/* Dark mode */
.theme-dark .collapsible-active-heading,
.theme-dark .collapsed-status-bar {
  background: #111827 !important;
  border-color: #ffffff !important;
}

.theme-dark .collapsed-status-bar:hover {
  background: #172033 !important;
}



/* =========================================================
   UNIFORM SECTION BARS — FINAL
   All open/closed bars same size and same clean style.
   ========================================================= */

/* OPEN / ACTIVE BAR */
.collapsible-active-heading {
  width: 100% !important;
  height: 58px !important;
  min-height: 58px !important;
  max-height: 58px !important;
  margin: 12px 0 !important;
  padding: 8px 16px 8px 28px !important;

  background: #ffffff !important;
  border: 1px solid #111111 !important;
  border-left: 5px solid #111111 !important;
  border-radius: 6px !important;
  box-shadow: none !important;
  box-sizing: border-box !important;
}

/* CLOSED BARS CONTAINER */
.collapsed-status-bars {
  width: 100% !important;
  padding: 0 !important;
  margin: 0 !important;
  gap: 12px !important;
}

/* CLOSED BAR — EXACT SAME SIZE AS OPEN BAR */
.collapsed-status-bar {
  width: 100% !important;
  height: 58px !important;
  min-height: 58px !important;
  max-height: 58px !important;
  margin: 0 !important;
  padding: 8px 16px 8px 28px !important;

  background: #ffffff !important;
  border: 1px solid #111111 !important;
  border-left: 5px solid #111111 !important;
  border-radius: 6px !important;
  box-shadow: none !important;
  box-sizing: border-box !important;

  display: flex !important;
  align-items: center !important;
  justify-content: flex-start !important;
}

/* ALL SECTION TEXT BLACK */
.collapsible-active-heading h1,
.collapsible-active-heading .active-section-meta,
.collapsed-status-bar strong {
  color: #111111 !important;
}

/* active title/meta alignment */
.active-section-title-wrap {
  justify-content: center !important;
}

.active-section-meta {
  margin-top: 2px !important;
  color: #111111 !important;
}

/* ALL CHEVRONS BLACK */
.collapsible-active-heading .section-chevron-slot,
.collapsed-status-bar .section-chevron-slot,
.collapsible-active-heading .section-chevron-slot i,
.collapsed-status-bar .section-chevron-slot i {
  color: #111111 !important;
}

/* remove colored hover/background */
.collapsed-status-bar:hover,
.collapsible-active-heading:hover {
  background: #ffffff !important;
}

/* Make sure no group color leaks into text/background */
.collapsed-status-left,
.collapsed-status-bar strong {
  background: transparent !important;
}

/* DARK MODE: requested bars still clean; white surface / black content */
.theme-dark .collapsible-active-heading,
.theme-dark .collapsed-status-bar {
  background: #ffffff !important;
  border-color: #111111 !important;
  border-left-color: #111111 !important;
}

.theme-dark .collapsible-active-heading h1,
.theme-dark .collapsible-active-heading .active-section-meta,
.theme-dark .collapsed-status-bar strong,
.theme-dark .collapsible-active-heading .section-chevron-slot,
.theme-dark .collapsed-status-bar .section-chevron-slot,
.theme-dark .collapsible-active-heading .section-chevron-slot i,
.theme-dark .collapsed-status-bar .section-chevron-slot i {
  color: #111111 !important;
}



/* =========================================================
   PROFESSIONAL WORKFLOW TABS + SECTION COLOR SYNC
   ========================================================= */

/* ---------- TOP WORKFLOW TABS ---------- */
.workflow-tabs {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  flex-wrap: wrap !important;
}

.workflow-tab-wrap {
  flex: 0 0 auto !important;
}

.workflow-tab {
  min-width: 122px !important;
  height: 42px !important;
  padding: 0 !important;

  display: grid !important;
  grid-template-columns: 1fr 42px !important;
  align-items: stretch !important;

  background: #ffffff !important;
  border: 1px solid #d9dee7 !important;
  border-left: 4px solid var(--group-color) !important;
  border-radius: 7px !important;

  color: var(--group-color) !important;
  box-shadow: 0 1px 2px rgba(15, 23, 42, .04) !important;
  overflow: hidden !important;
  transition: .16s ease !important;
}

.workflow-tab:hover {
  transform: translateY(-1px) !important;
  border-color: #cbd5e1 !important;
  box-shadow: 0 5px 14px rgba(15, 23, 42, .08) !important;
  background: #ffffff !important;
}

.workflow-tab.active {
  border-color: var(--group-color) !important;
  box-shadow:
    0 0 0 2px color-mix(in srgb, var(--group-color) 18%, transparent),
    0 5px 14px rgba(15, 23, 42, .08) !important;
  background: #ffffff !important;
}

.workflow-tab-label {
  display: flex !important;
  align-items: center !important;
  padding: 0 11px !important;

  color: var(--group-color) !important;
  background: #ffffff !important;

  font-size: 9px !important;
  font-weight: 900 !important;
  letter-spacing: .025em !important;
  white-space: nowrap !important;
}

.workflow-total-box {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;

  background: color-mix(
    in srgb,
    var(--group-color) 12%,
    #ffffff
  ) !important;

  color: var(--group-color) !important;
  border-left: 1px solid color-mix(
    in srgb,
    var(--group-color) 25%,
    #e5e7eb
  ) !important;

  font-size: 14px !important;
  font-weight: 900 !important;
}

/* Plus/add-section button matches the tabs */
.workflow-add-button {
  width: 42px !important;
  height: 42px !important;
  border: 1px dashed #94a3b8 !important;
  border-radius: 7px !important;
  background: #ffffff !important;
  color: #0f172a !important;
}

.workflow-add-button:hover {
  background: #f8fafc !important;
  border-color: #475569 !important;
}

/* ---------- SECTION BARS ---------- */
/* Open section: white card, but text + left strip use its exact section color */
.collapsible-active-heading {
  background: #ffffff !important;
  border: 1px solid #111827 !important;
  border-left: 5px solid var(--active-section-color) !important;
  box-shadow: none !important;
}

.collapsible-active-heading h1 {
  color: var(--active-section-color) !important;
}

.collapsible-active-heading .active-section-meta {
  color: var(--active-section-color) !important;
  opacity: .78 !important;
}

.collapsible-active-heading .section-chevron-slot,
.collapsible-active-heading .section-chevron-slot i {
  color: var(--active-section-color) !important;
}

/* Closed sections: exact group color on text + left strip */
.collapsed-status-bar {
  background: #ffffff !important;
  border: 1px solid #111827 !important;
  border-left: 5px solid var(--group-color) !important;
  box-shadow: none !important;
}

.collapsed-status-bar:hover {
  background: #fbfcfd !important;
}

.collapsed-status-bar strong,
.collapsed-status-bar .section-chevron-slot,
.collapsed-status-bar .section-chevron-slot i,
.collapsed-status-bar .collapsed-status-icon {
  color: var(--group-color) !important;
}

/* Make sure no old black override wins */
.collapsed-status-left,
.collapsed-status-bar strong {
  background: transparent !important;
}

/* ---------- DARK MODE ---------- */
.theme-dark .workflow-tab,
.theme-dark .workflow-tab-label,
.theme-dark .workflow-add-button {
  background: #111827 !important;
}

.theme-dark .workflow-tab {
  border-color: #334155 !important;
  border-left-color: var(--group-color) !important;
}

.theme-dark .workflow-total-box {
  background: color-mix(
    in srgb,
    var(--group-color) 18%,
    #111827
  ) !important;
}

.theme-dark .collapsible-active-heading,
.theme-dark .collapsed-status-bar {
  background: #111827 !important;
  border-color: #ffffff !important;
}

.theme-dark .collapsible-active-heading {
  border-left-color: var(--active-section-color) !important;
}

.theme-dark .collapsed-status-bar {
  border-left-color: var(--group-color) !important;
}





/* ===== Status dropdown reference style ===== */
.board-table-shell,
.board-table-row,
.row-status-cell,
.status-ref-wrap {
  overflow: visible !important;
}

.board-table-row {
  position: relative !important;
  z-index: 1 !important;
}

.board-table-row:has(.status-ref-menu) {
  z-index: 5000 !important;
}

.row-status-cell {
  position: relative !important;
  z-index: 30 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.status-ref-wrap {
  position: relative !important;
  width: 160px !important;
  max-width: 100% !important;
}

.status-ref-trigger {
  width: 160px !important;
  max-width: 100% !important;
  height: 38px !important;
  padding: 0 13px !important;

  display: grid !important;
  grid-template-columns: 8px minmax(0, 1fr) 18px !important;
  align-items: center !important;
  gap: 10px !important;

  background: #e9e9e9 !important;
  border: 1px solid #b8b8b8 !important;
  border-radius: 10px !important;

  color: #2f3540 !important;
  cursor: pointer !important;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.65) !important;
}

.status-ref-trigger.open {
  background: #eeeeee !important;
}

.status-ref-dot {
  display: block !important;
  width: 8px !important;
  height: 8px !important;
  min-width: 8px !important;
  border-radius: 50% !important;
}

.status-ref-label,
.status-ref-option-label {
  min-width: 0 !important;
  overflow: hidden !important;
  white-space: nowrap !important;
  text-overflow: ellipsis !important;
}

.status-ref-label {
  color: #31343a !important;
  font-size: 12px !important;
  font-weight: 500 !important;
  text-align: left !important;
}

.status-ref-chevron {
  color: #111827 !important;
  font-size: 13px !important;
  transition: transform .15s ease !important;
}

.status-ref-chevron.rotate {
  transform: rotate(180deg) !important;
}

.status-ref-menu {
  position: absolute !important;
  top: calc(100% + 7px) !important;
  left: 0 !important;
  z-index: 2147483647 !important;

  width: 240px !important;
  max-height: 310px !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;

  padding: 9px !important;
  background: #ffffff !important;
  border: 1px solid #e0e3e7 !important;
  border-radius: 12px !important;

  box-shadow:
    0 18px 42px rgba(15,23,42,.14),
    0 3px 10px rgba(15,23,42,.05) !important;
}

.status-ref-option {
  width: 100% !important;
  min-height: 40px !important;
  padding: 0 10px !important;

  display: grid !important;
  grid-template-columns: 8px minmax(0,1fr) 14px !important;
  align-items: center !important;
  gap: 10px !important;

  border: 0 !important;
  border-radius: 8px !important;
  background: transparent !important;

  color: #666b73 !important;
  text-align: left !important;
  cursor: pointer !important;
}

.status-ref-option:hover,
.status-ref-option.active {
  background: #f5f6f8 !important;
}

.status-ref-option-label {
  color: #666b73 !important;
  font-size: 12px !important;
  font-weight: 500 !important;
}

.status-ref-check {
  color: #111827 !important;
  font-size: 10px !important;
}

/* Keep packing centered and clean */
.board-col-packing {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.packing-clean-input,
.packing-inline-input {
  margin: 0 !important;
  align-self: center !important;
}

/* Dark mode */
.theme-dark .status-ref-trigger {
  background: #1f2937 !important;
  border-color: #475569 !important;
}

.theme-dark .status-ref-label,
.theme-dark .status-ref-chevron {
  color: #f8fafc !important;
}

.theme-dark .status-ref-menu {
  background: #111827 !important;
  border-color: #334155 !important;
}

.theme-dark .status-ref-option:hover,
.theme-dark .status-ref-option.active {
  background: #1f2937 !important;
}

.theme-dark .status-ref-option-label,
.theme-dark .status-ref-check {
  color: #f8fafc !important;
}


/* =========================================================
   STATUS DROPDOWN OUTSIDE ROW / TABLE
   ========================================================= */

.status-ref-wrap {
  position: relative !important;
  width: 160px !important;
  max-width: 100% !important;
}

.status-ref-trigger {
  width: 160px !important;
  max-width: 100% !important;
  height: 38px !important;
  padding: 0 13px !important;
  display: grid !important;
  grid-template-columns: 8px minmax(0,1fr) 18px !important;
  align-items: center !important;
  gap: 10px !important;
  background: #e9e9e9 !important;
  border: 1px solid #b8b8b8 !important;
  border-radius: 10px !important;
  color: #2f3540 !important;
  cursor: pointer !important;
}

.status-ref-trigger.open {
  background: #eeeeee !important;
  border-color: #9aa1aa !important;
}

.status-ref-dot {
  display: block !important;
  width: 8px !important;
  height: 8px !important;
  min-width: 8px !important;
  border-radius: 50% !important;
}

.status-ref-label {
  min-width: 0 !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
  text-align: left !important;
  color: #31343a !important;
  font-size: 12px !important;
  font-weight: 500 !important;
}

.status-ref-chevron {
  color: #111827 !important;
  font-size: 13px !important;
  transition: transform .15s ease !important;
}

.status-ref-chevron.rotate {
  transform: rotate(180deg) !important;
}

/* IMPORTANT: menu is teleported to body, so it can never push rows down */
.status-ref-menu-portal {
  position: fixed !important;
  z-index: 2147483647 !important;
  max-height: 310px !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  padding: 9px !important;
  margin: 0 !important;

  background: #ffffff !important;
  border: 1px solid #e0e3e7 !important;
  border-radius: 12px !important;

  box-shadow:
    0 18px 42px rgba(15,23,42,.16),
    0 4px 12px rgba(15,23,42,.06) !important;
}

.status-ref-menu-portal .status-ref-option {
  width: 100% !important;
  min-height: 40px !important;
  padding: 0 10px !important;

  display: grid !important;
  grid-template-columns: 8px minmax(0,1fr) 14px !important;
  align-items: center !important;
  gap: 10px !important;

  border: 0 !important;
  border-radius: 8px !important;
  background: transparent !important;
  color: #666b73 !important;
  text-align: left !important;
  cursor: pointer !important;
}

.status-ref-menu-portal .status-ref-option:hover,
.status-ref-menu-portal .status-ref-option.active {
  background: #f5f6f8 !important;
}

.status-ref-option-label {
  min-width: 0 !important;
  overflow: hidden !important;
  white-space: nowrap !important;
  text-overflow: ellipsis !important;
  color: #666b73 !important;
  font-size: 12px !important;
  font-weight: 500 !important;
}

.status-ref-check {
  color: #111827 !important;
  font-size: 10px !important;
}

/* smooth open/close */
.status-ref-menu-enter-active,
.status-ref-menu-leave-active {
  transition: opacity .14s ease, transform .14s ease !important;
  transform-origin: top left !important;
}

.status-ref-menu-enter-from,
.status-ref-menu-leave-to {
  opacity: 0 !important;
  transform: translateY(-4px) scale(.985) !important;
}

.theme-dark .status-ref-menu-portal {
  background: #111827 !important;
  border-color: #334155 !important;
}

.theme-dark .status-ref-menu-portal .status-ref-option:hover,
.theme-dark .status-ref-menu-portal .status-ref-option.active {
  background: #1f2937 !important;
}

.theme-dark .status-ref-option-label,
.theme-dark .status-ref-check {
  color: #f8fafc !important;
}



/* START-ONLY ORDER STATE */
.working-locked-label {
  display: inline-flex !important;
  align-items: center !important;
  gap: 5px !important;
  padding: 4px 9px !important;
  border-radius: 999px !important;
  background: #eef2f7 !important;
  color: #475467 !important;
  font-size: 9px !important;
  font-weight: 700 !important;
  white-space: nowrap !important;
}

.working-locked-label i {
  font-size: 8px !important;
}



/* =========================================================
   WORKING STATUS DROPDOWN — FINAL
   ========================================================= */
.status-fixed-dropdown {
  position: fixed !important;
  z-index: 2147483647 !important;
  display: block !important;

  padding: 8px !important;
  max-height: 310px !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;

  background: #ffffff !important;
  border: 1px solid #d9dee5 !important;
  border-radius: 12px !important;

  box-shadow:
    0 18px 42px rgba(15,23,42,.18),
    0 4px 12px rgba(15,23,42,.07) !important;
}

.status-fixed-option {
  width: 100% !important;
  min-height: 38px !important;
  padding: 0 10px !important;

  display: grid !important;
  grid-template-columns: 8px minmax(0,1fr) 14px !important;
  align-items: center !important;
  gap: 10px !important;

  border: 0 !important;
  border-radius: 8px !important;
  background: transparent !important;

  color: #5f6670 !important;
  text-align: left !important;
  cursor: pointer !important;
}

.status-fixed-option:hover,
.status-fixed-option.active {
  background: #f3f4f6 !important;
}

.status-fixed-dot {
  width: 8px !important;
  height: 8px !important;
  min-width: 8px !important;
  border-radius: 50% !important;
}

.status-fixed-label {
  color: #5f6670 !important;
  font-size: 12px !important;
  font-weight: 500 !important;
}

.status-fixed-check {
  color: #111827 !important;
  font-size: 10px !important;
}

.theme-dark .status-fixed-dropdown {
  background: #111827 !important;
  border-color: #334155 !important;
}

.theme-dark .status-fixed-option:hover,
.theme-dark .status-fixed-option.active {
  background: #1f2937 !important;
}

.theme-dark .status-fixed-label,
.theme-dark .status-fixed-check {
  color: #ffffff !important;
}
/* MAIN TABLE HEADER BAR */
.board-table-header,
.board-grid-header {
    background: #000 !important;
    color: #fff !important;
}

/* Header ke tamam text white */
.board-table-header *,
.board-grid-header * {
    color: #fff !important;
}

/* Header ki pipeline white/light */
.board-table-header .board-col,
.board-grid-header .board-col {
    border-right-color: rgba(255, 255, 255, 0.65) !important;
}
/* ===== MAIN TABLE HEADER BLACK ONLY ===== */

.factory-board-page .board-table-head {
  background: #000000 !important;
  background-color: #000000 !important;
  color: #ffffff !important;
  border-color: #000000 !important;
}

/* All header cells black + text white */
.factory-board-page .board-table-head > .board-col {
  background: #000000 !important;
  background-color: #000000 !important;
  color: #ffffff !important;
}

/* Header text + icons white */
.factory-board-page .board-table-head > .board-col *,
.factory-board-page .board-table-head i {
  color: #ffffff !important;
}

/* Vertical lines white */
.factory-board-page
.board-table-head
> .board-col:not(:last-child)::after {
  background: #ffffff !important;
}

/* Resize lines white */
.factory-board-page .board-table-head .column-resizer::before {
  background: #ffffff !important;
}
/* ===== PAGE BACKGROUND ===== */
.factory-board-page {
  background: #f4f5f8 !important;
  min-height: 100vh;
}


/* =========================================================
   REUSABLE HEADER PAGE SPACING
   ========================================================= */
.factory-board-page {
  background: #f4f5f8 !important;
}

.factory-board-page .board-toolbar {
  padding-top: 20px !important;
}

/* Old header is no longer used; keep it harmless if referenced elsewhere */
.factory-board-page .board-brand-header {
  display: none !important;
}


/* =========================================================
   CHAT NOTIFICATION DROPDOWN - REAL PAGE CSS
   ========================================================= */

.chat-notification-wrap {
  position: relative !important;
  flex: 0 0 auto !important;
  z-index: 1000 !important;
}

.chat-notification-button {
  position: relative !important;
  width: 40px !important;
  height: 38px !important;
  padding: 0 !important;

  display: grid !important;
  place-items: center !important;

  border: 1px solid #0f172a !important;
  border-radius: 999px !important;

  background: #ffffff !important;
  color: #0f172a !important;

  cursor: pointer !important;
}

.chat-notification-count {
  position: absolute !important;
  top: -6px !important;
  right: -7px !important;

  min-width: 19px !important;
  height: 19px !important;
  padding: 0 5px !important;

  display: grid !important;
  place-items: center !important;

  background: #ef4444 !important;
  color: #ffffff !important;

  border: 2px solid #ffffff !important;
  border-radius: 999px !important;

  font-size: 9px !important;
  font-weight: 900 !important;
  line-height: 1 !important;
}

.chat-notification-dropdown {
  position: absolute !important;

  top: calc(100% + 10px) !important;
  right: 0 !important;
  left: auto !important;

  z-index: 2147483647 !important;

  width: 360px !important;
  min-width: 360px !important;
  max-width: min(360px, calc(100vw - 24px)) !important;
  max-height: 420px !important;

  overflow-y: auto !important;
  overflow-x: hidden !important;

  background: #ffffff !important;

  border: 1px solid #dbe2ea !important;
  border-radius: 14px !important;

  box-shadow:
    0 22px 55px rgba(15, 23, 42, .18),
    0 4px 12px rgba(15, 23, 42, .06) !important;
}

.chat-notification-head {
  position: sticky !important;
  top: 0 !important;
  z-index: 2 !important;

  width: 100% !important;
  padding: 13px 14px !important;

  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  gap: 12px !important;

  background: #ffffff !important;

  border-bottom: 1px solid #e8edf3 !important;
}

.chat-notification-head strong {
  color: #0f172a !important;
  font-size: 13px !important;
  font-weight: 900 !important;
  white-space: nowrap !important;
}

.chat-notification-head span {
  color: #64748b !important;
  font-size: 10px !important;
  font-weight: 700 !important;
  white-space: nowrap !important;
}

.chat-notification-item {
  width: 100% !important;
  min-width: 0 !important;

  padding: 11px 12px !important;

  display: grid !important;
  grid-template-columns: 36px minmax(0, 1fr) 26px !important;
  align-items: center !important;
  gap: 10px !important;

  border: 0 !important;
  border-bottom: 1px solid #eef2f7 !important;

  background: #ffffff !important;
  color: #0f172a !important;

  cursor: pointer !important;
  text-align: left !important;
}

.chat-notification-item:last-of-type {
  border-bottom: 0 !important;
}

.chat-notification-item:hover {
  background: #f8fafc !important;
}

.chat-notification-icon {
  width: 36px !important;
  height: 36px !important;
  min-width: 36px !important;

  display: grid !important;
  place-items: center !important;

  border-radius: 50% !important;

  background: #0f172a !important;
  color: #ffffff !important;

  font-size: 12px !important;
}

.chat-notification-content {
  min-width: 0 !important;
  width: 100% !important;

  display: flex !important;
  flex-direction: column !important;
  gap: 3px !important;
}

.chat-notification-content strong {
  display: block !important;

  min-width: 0 !important;
  max-width: none !important;

  overflow: hidden !important;

  color: #0f172a !important;

  font-size: 11px !important;
  font-weight: 900 !important;

  white-space: nowrap !important;
  text-overflow: ellipsis !important;
}

.chat-notification-content small {
  display: block !important;

  min-width: 0 !important;
  max-width: none !important;

  overflow: hidden !important;

  color: #64748b !important;

  font-size: 9px !important;
  font-weight: 600 !important;

  white-space: nowrap !important;
  text-overflow: ellipsis !important;
}

.chat-notification-badge {
  width: 24px !important;
  height: 24px !important;
  min-width: 24px !important;

  display: grid !important;
  place-items: center !important;

  padding: 0 !important;

  border-radius: 999px !important;

  background: #ef4444 !important;
  color: #ffffff !important;

  font-size: 9px !important;
  font-weight: 900 !important;
}

.chat-notification-empty {
  min-height: 110px !important;

  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 8px !important;

  padding: 20px !important;

  color: #64748b !important;

  font-size: 11px !important;
  font-weight: 700 !important;
}

/* Keep toolbar controls aligned */
.board-toolbar-actions {
  display: flex !important;
  align-items: center !important;
  justify-content: flex-end !important;
  gap: 10px !important;
  overflow: visible !important;
}

.board-toolbar {
  overflow: visible !important;
}

/* DARK */
.theme-dark .chat-notification-button,
.theme-dark .chat-notification-dropdown,
.theme-dark .chat-notification-head,
.theme-dark .chat-notification-item {
  background: #111827 !important;
  color: #f8fafc !important;
  border-color: #334155 !important;
}

.theme-dark .chat-notification-item:hover {
  background: #1e293b !important;
}

.theme-dark .chat-notification-head strong,
.theme-dark .chat-notification-content strong {
  color: #f8fafc !important;
}

.theme-dark .chat-notification-head span,
.theme-dark .chat-notification-content small,
.theme-dark .chat-notification-empty {
  color: #cbd5e1 !important;
}

/* MOBILE */
@media (max-width: 767px) {
  .chat-notification-dropdown {
    position: fixed !important;

    top: 72px !important;
    right: 12px !important;
    left: 12px !important;

    width: auto !important;
    min-width: 0 !important;
    max-width: none !important;

    max-height: calc(100vh - 90px) !important;
  }
}

</style>
