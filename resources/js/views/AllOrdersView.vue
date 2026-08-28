<template>
  <AppLayout>
  <div
    class="factory-board-page"
    :class="`theme-${boardTheme}`"
    @click="handlePageBackgroundClick"
  >
    <!-- REUSABLE PAGE HEADER -->
 <PageHeader
  title="Factory Order Management"
  subtitle="Track production, manage orders and keep your workflow organized."
  :user="currentUser"
  :photo="currentUser?.profile_photo_url"
  @profile="openProfile"
>
  <template #notifications>
      <div
        v-if="isSuperAdmin"
        class="header-board-admin-tools"
        @click.stop
      >
        <span class="header-owner-toggle-label">SELECT OWNER</span>
        <button
          type="button"
          class="header-owner-toggle"
          :class="{ active: boardSettings.auto_assign_all_owners }"
          :title="boardSettings.auto_assign_all_owners ? 'Automatic owner assignment is ON' : 'Automatic owner assignment is OFF'"
          @click.stop="toggleAutoAssignAllOwners"
        >
          <span></span>
        </button>

        <button
          type="button"
          class="header-column-settings-btn"
          title="Manage board columns"
          @click.stop="boardSettingsModal = true"
        >
          <i class="fa-solid fa-sliders"></i>
        </button>
      </div>

      <Teleport to="body">
        <div
          v-if="showChatNotificationMenu"
          class="chat-notification-dropdown notification-center-dropdown factory-notification-portal"
          @click.stop
        >
        <div class="chat-notification-head notification-center-head">
          <div>
            <strong>Notifications</strong>
            <small>{{ totalBellNotificationCount }} unread</small>
          </div>
        </div>

        <div class="notification-tabs">
          <button
            type="button"
            :class="{ active: notificationTab === 'chats' }"
            @click.stop="notificationTab = 'chats'"
          >
            <i class="fa-solid fa-comments"></i>
            Chats
            <span v-if="totalUnreadChatCount > 0">
              {{ totalUnreadChatCount }}
            </span>
          </button>

          <button
            type="button"
            :class="{ active: notificationTab === 'orders' }"
            @click.stop="notificationTab = 'orders'"
          >
            <i class="fa-solid fa-folder-plus"></i>
            Orders
            <span v-if="unreadOrderNotificationCount > 0">
              {{ unreadOrderNotificationCount }}
            </span>
          </button>
        </div>

        <div v-if="notificationTab === 'chats'" class="notification-list">
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
            <i class="fa-regular fa-comment-dots"></i>
            No unread chats
          </div>
        </div>

        <div v-else class="notification-list">
          <button
            v-for="order in unreadOrderNotifications"
            :key="'order-notification-' + order.id"
            type="button"
            class="chat-notification-item order-notification-item"
            @click="openOrderFromNotification(order)"
          >
            <span class="chat-notification-icon order-notification-icon">
              <i class="fa-solid fa-folder-plus"></i>
            </span>

            <span class="chat-notification-content">
              <strong>{{ order.name }}</strong>
              <small>
                New order
                <template v-if="order.po">
                  · {{ order.po }}
                </template>
                <template v-if="order.created_at">
                  · {{ notificationTime(order.created_at) }}
                </template>
              </small>
            </span>

            <span class="order-notification-new-dot"></span>
          </button>

          <div
            v-if="unreadOrderNotifications.length === 0"
            class="chat-notification-empty"
          >
            <i class="fa-regular fa-folder-open"></i>
            No new order notifications
          </div>
        </div>
        </div>
      </Teleport>
  </template>
</PageHeader>

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
        <span class="summary-home-icon prosix-summary-icon">
          <img
            src="/public/assets/images/P LOGO BLACK.png"
            alt="Prosix"
            class="prosix-summary-logo"
          />
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
            v-if="canManageWorkflow"
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
          v-if="canManageWorkflow"
          type="button"
          class="workflow-add-button"
          title="Add another order section"
          @click="addWorkflowGroup"
        >
          <i class="fa-solid fa-plus"></i>
        </button>
      </div>

      <div class="board-toolbar-actions">
        <button
          v-if="!isClient"
          type="button"
          class="client-filter-trigger"
          @click.stop="openClientFilter"
        >
          <i class="fa-solid fa-user-group"></i>
          <span>Client Filter</span>
          <span v-if="selectedClient" class="client-filter-count">1</span>
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
          borderLeftColor: activeBoardGroup.color,
          order: boardSectionOrder(activeGroup, 0)
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
        :style="{ order: boardSectionOrder(activeGroup, 1) }"
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
        :style="{ order: boardSectionOrder(activeGroup, 2) }"
      >
        <div class="board-table-head" :style="boardGridStyle">
          <div class="board-col board-col-check resizable-head-cell" style="order:-1">
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

          <div class="board-col board-col-name resizable-head-cell" :style="boardColumnOrderStyle('name')">
            ORDER NAME
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('name', $event)"
            ></span>
          </div>

          <div v-if="isBoardColumnVisible('status')" class="board-col board-col-status resizable-head-cell" :style="boardColumnOrderStyle('status')">
            STATUS
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('status', $event)"
            ></span>
          </div>

          <div
            v-for="column in activeCustomColumns"
            :key="'custom-head-' + column.id"
            class="board-col board-col-custom resizable-head-cell"
            :style="boardColumnOrderStyle(`custom_${column.id}`)"
          >
            {{ column.name }}
          </div>

          <div v-if="isBoardColumnVisible('owner')" class="board-col board-col-owner resizable-head-cell" :style="boardColumnOrderStyle('owner')">
            OWNER
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('owner', $event)"
            ></span>
          </div>

          <div v-if="isBoardColumnVisible('files')" class="board-col board-col-files resizable-head-cell" :style="boardColumnOrderStyle('files')">
            FILES
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('files', $event)"
            ></span>
          </div>

          <div v-if="isBoardColumnVisible('packing')" class="board-col board-col-packing resizable-head-cell" :style="boardColumnOrderStyle('packing')">
            PACKING DETAIL
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('packing', $event)"
            ></span>
          </div>

          <div v-if="isBoardColumnVisible('notes')" class="board-col board-col-notes resizable-head-cell" :style="boardColumnOrderStyle('notes')">
            NOTES
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('notes', $event)"
            ></span>
          </div>

          <div v-if="isBoardColumnVisible('chat')" class="board-col board-col-chat resizable-head-cell" :style="boardColumnOrderStyle('chat')">
            CHAT
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('chat', $event)"
            ></span>
          </div>

          <div v-if="isBoardColumnVisible('payment')" class="board-col board-col-payment resizable-head-cell" :style="boardColumnOrderStyle('payment')">
            PAYMENT
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('payment', $event)"
            ></span>
          </div>

          <div v-if="isBoardColumnVisible('address')" class="board-col board-col-address resizable-head-cell" :style="boardColumnOrderStyle('address')">
            ADDRESS
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('address', $event)"
            ></span>
          </div>

          <div v-if="isBoardColumnVisible('track')" class="board-col board-col-track resizable-head-cell" :style="boardColumnOrderStyle('track')">
            TRK#
            <span
              class="column-resizer"
              title="Drag to resize"
              @mousedown.stop.prevent="startColumnResize('track', $event)"
            ></span>
          </div>

          <div class="board-col board-col-info" style="order:9999">
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

              <template v-if="isClient">
                <select class="inline-fixed-select" disabled title="Status is fixed for client orders">
                  <option>Pending</option>
                </select>

                <select v-model="inlinePriorityOptionId" class="inline-priority-select">
                  <option value="">Select Priority</option>
                  <option
                    v-for="option in priorityOptions"
                    :key="option.id"
                    :value="String(option.id)"
                  >
                    {{ option.label }}
                  </option>
                </select>

                <select class="inline-fixed-select" disabled title="Payment is fixed for client orders">
                  <option>Not Yet</option>
                </select>
              </template>

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


        <div v-if="loadingOrders && orders.length === 0" class="board-empty-state prosix-loading-state">
          <div class="prosix-loader-logo-wrap">
            <img src="/public/assets/images/P LOGO BLACK.png" alt="Prosix" class="prosix-loader-logo" />
          </div>
          <span>Loading orders...</span>
        </div>

        <div v-else-if="!loadingOrders && filteredOrders.length === 0" class="board-empty-state">
          No orders found in this section.
        </div>

        <div
          v-for="order in filteredOrders"
          :key="order.id"
          class="board-table-row"
          :class="{
            unread: !order.user_has_seen,
            opened: order.user_has_seen,
            selected: selectedOrders.includes(order.id),
            'last-opened-order': Number(lastOpenedOrderId) === Number(order.id)
          }"
          :style="boardGridStyle"
          @click.stop="openBoardOrder(order)"
        >
          <div class="board-col board-col-check" style="order:-1">
            <input
              type="checkbox"
              :value="order.id"
              v-model="selectedOrders"
              @click.stop
            />
          </div>

          <div class="board-col board-col-name" :style="boardColumnOrderStyle('name')">
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

              </button>

              <div class="order-working-actions">
                <!-- SHIPPED: SHOW THE PERSON WHO FINISHED THIS ORDER -->
                <div
                  v-if="isShippedOrder(order) && finishedDesigner(order)"
                  class="finished-user-avatar-only"
                  :title="finishedDesigner(order).name + ' finished this order'"
                  @click.stop
                >
                  <img
                    v-if="finishedDesigner(order).profile_photo_url"
                    :src="finishedDesigner(order).profile_photo_url"
                    :alt="finishedDesigner(order).name"
                  />
                  <span v-else class="row-working-avatar-fallback">
                    {{ initial(finishedDesigner(order).name) }}
                  </span>
                  <span class="finished-flag-badge" title="Finished">
                    <i class="fa-solid fa-flag-checkered"></i>
                  </span>
                </div>

                <!-- START WORK: SHOW FOR EVERY ORDER STATUS -->
                <button
                  v-else-if="!workingDesigner(order)"
                  type="button"
                  class="start-working-btn"
                  title="Start Work"
                  aria-label="Start Work"
                  @click.stop="startWorking(order)"
                >
                  <i class="fa-solid fa-play"></i>
                </button>

                <!-- AFTER START: ONLY SUPER ADMIN CAN STOP -->
                <button
                  v-else-if="
                    workingDesigner(order) &&
                    isSuperAdmin
                  "
                  type="button"
                  class="working-user-avatar-only working-user-avatar-stop"
                  :title="'Stop work for ' + workingDesigner(order).name"
                  aria-label="Stop Work"
                  @click.stop="stopWorking(order)"
                >
                  <img
                    v-if="workingDesigner(order).profile_photo_url"
                    :src="workingDesigner(order).profile_photo_url"
                    :alt="workingDesigner(order).name"
                  />

                  <span
                    v-else
                    class="row-working-avatar-fallback"
                  >
                    {{ initial(workingDesigner(order).name) }}
                  </span>

                  <span class="working-stop-badge">
                    <i class="fa-solid fa-stop"></i>
                  </span>
                </button>

                <!-- OTHER USERS: IMAGE ONLY -->
                <div
                  v-else-if="workingDesigner(order)"
                  class="working-user-avatar-only working-user-avatar-readonly"
                  :title="workingDesigner(order).name + ' is working'"
                  @click.stop
                >
                  <img
                    v-if="workingDesigner(order).profile_photo_url"
                    :src="workingDesigner(order).profile_photo_url"
                    :alt="workingDesigner(order).name"
                  />

                  <span
                    v-else
                    class="row-working-avatar-fallback"
                  >
                    {{ initial(workingDesigner(order).name) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div v-if="isBoardColumnVisible('status')" class="board-col board-col-status row-status-cell" :style="boardColumnOrderStyle('status')" @click.stop>
            <div class="status-ref-wrap">
              <button
                type="button"
                class="status-ref-trigger"
                :disabled="!canEditWorkflowFields"
                :class="{ open: rowStatusMenuId === Number(order.id) }"
                :style="{
                  '--status-fill': order.statusColor || '#e5e7eb',
                  '--status-text': readableTextColor(order.statusColor || '#e5e7eb')
                }"
                @click.stop="canEditWorkflowFields && toggleRowStatusMenu(order, $event)"
              >
<span class="status-ref-label">
                  {{ order.status }}
                </span>

                <i
                  v-if="canEditWorkflowFields"
                  class="fa-solid fa-chevron-down status-ref-chevron"
                  :class="{ rotate: rowStatusMenuId === Number(order.id) }"
                ></i>
              </button>
            </div>
          </div>

          <div
            v-for="column in activeCustomColumns"
            :key="'custom-cell-' + order.id + '-' + column.id"
            class="board-col board-col-custom row-custom-cell"
            :style="boardColumnOrderStyle(`custom_${column.id}`)"
            @mouseenter="showCopyableHoverText(getOrderCustomText(order, column), $event)"
            @mouseleave="scheduleCopyableHoverHide"
            @click.stop
          >
            <!-- DROPDOWN: full colored label like STATUS -->
            <button
              v-if="(column.type || 'dropdown') === 'dropdown'"
              type="button"
              class="custom-field-trigger custom-field-trigger-filled"
              :class="{ empty: !getOrderCustomOption(order, column) }"
              :style="customFieldButtonStyle(order, column)"
              :disabled="!canEditCustomField(column)"
              @click.stop="canEditCustomField(column) && openCustomFieldMenu(order, column, $event)"
            >
              <span>{{ getOrderCustomOption(order, column)?.label || 'Select' }}</span>
              <i class="fa-solid fa-chevron-down"></i>
            </button>

            <!-- TEXT -->
            <input
              v-else-if="column.type === 'text'"
              class="custom-field-text-input"
              type="text"
              :value="getOrderCustomText(order, column)"
              placeholder="Write..."
              :readonly="!canEditCustomField(column)"
              @click.stop
              @keydown.enter.prevent="$event.target.blur()"
              @blur="saveOrderCustomText(order, column, $event.target.value)"
            />

            <!-- NOTES -->
            <textarea
              v-else
              class="custom-field-notes-input"
              rows="1"
              :value="getOrderCustomText(order, column)"
              placeholder="Write notes..."
              :readonly="!canEditCustomField(column)"
              @click.stop
              @keydown.enter.exact.prevent="$event.target.blur()"
              @keydown.shift.enter.stop
              @blur="saveOrderCustomText(order, column, $event.target.value)"
            ></textarea>

            <div
              v-if="column.type !== 'dropdown' && getOrderCustomText(order, column)"
              class="copyable-cell-tooltip"
              @click.stop
            >
              {{ getOrderCustomText(order, column) }}
            </div>
          </div>

          <div v-if="isBoardColumnVisible('owner')" class="board-col board-col-owner" :style="boardColumnOrderStyle('owner')">
            <div class="board-avatar-stack owner-compact-stack">
              <button
                v-if="loggedInOrderOwner(order)"
                type="button"
                class="board-avatar owner-current-avatar"
                :title="loggedInOrderOwner(order).name"
                @click.stop="openProfile(loggedInOrderOwner(order))"
              >
                <img
                  v-if="loggedInOrderOwner(order).profile_photo_url"
                  :src="loggedInOrderOwner(order).profile_photo_url"
                  :alt="loggedInOrderOwner(order).name"
                />
                <span v-else>
                  {{ initial(loggedInOrderOwner(order).name) }}
                </span>
              </button>

              <button
                v-if="otherOrderOwnersCount(order) > 0"
                type="button"
                class="board-avatar board-avatar-more"
                :title="otherOrderOwnersNames(order)"
                @click.stop="openSingleOrderMembers(order)"
              >
                +{{ otherOrderOwnersCount(order) }}
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
            v-if="isBoardColumnVisible('files')"
            class="board-col board-col-files board-files-drop-zone"
            :style="boardColumnOrderStyle('files')"
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
              <div
                v-if="uploadProgressByOrder[Number(order.id)] !== undefined"
                class="inline-upload-progress"
              >
                <i class="fa-solid fa-spinner fa-spin"></i>
                {{ uploadProgressByOrder[Number(order.id)] }}%
              </div>
              <!-- ONLY 3 THUMBNAILS IN OUTER BOARD -->
              <div
                v-for="file in rowFiles(order).slice(0, 3)"
                :key="file.id || file.url || file.name"
                class="board-row-file-thumb-wrap"
                :title="file.name"
                @click.stop="openRowFile(order, file)"
              >
                <div class="board-row-file-thumb">
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
                </div>

                <button
                  v-if="canDeleteFile(file) && !file.uploading"
                  type="button"
                  class="board-row-file-remove"
                  title="Delete file"
                  @click.stop="removeRowFile(order, file)"
                >
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>

              <!-- ALL EXTRA FILES COME IN COUNT -->
              <button
                v-if="rowFiles(order).length > 3"
                type="button"
                class="board-more-files board-more-files-button"
                :title="`${rowFiles(order).length - 3} more files - click to view all`"
                @click.stop="openRowViewAll(order)"
              >
                +{{ rowFiles(order).length - 3 }}
              </button>

              <!-- ADD BUTTON ALWAYS VISIBLE FOR USERS WHO CAN UPLOAD -->
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

          <div v-if="isBoardColumnVisible('packing')" class="board-col board-col-packing" :style="boardColumnOrderStyle('packing')" @mouseenter="showCopyableHoverText(packingDetailText(order), $event)" @mouseleave="scheduleCopyableHoverHide">
            <input
              class="packing-clean-input"
              :value="packingDetailText(order)"
              type="text"
              placeholder="Add packing detail"
              @click.stop
              @keydown.enter.prevent="savePackingInline(order, $event)"
              @blur="savePackingInline(order, $event, true)"
            />
            <div
              v-if="packingDetailText(order)"
              class="copyable-cell-tooltip"
              @click.stop
            >
              {{ packingDetailText(order) }}
            </div>
          </div>

          <!-- NOTES: EDIT DIRECTLY FROM OUTER ORDER ROW -->
          <div v-if="isBoardColumnVisible('notes')" class="board-col board-col-notes" :style="boardColumnOrderStyle('notes')" @mouseenter="showCopyableHoverText(orderNotesText(order), $event)" @mouseleave="scheduleCopyableHoverHide">
            <textarea
              class="board-notes-inline-input"
              :value="inlineTextDraftValue(order, 'notes')"
              rows="1"
              placeholder="Write notes..."
              :readonly="!canEditNotesForOrder(order)"
              @focus="beginInlineTextEditing(order, 'notes')"
              @input="updateInlineTextDraft(order, 'notes', $event.target.value)"
              @click.stop
              @keydown.enter.exact.prevent="saveNotesInline(order, $event)"
              @keydown.shift.enter.stop
              @blur="saveNotesInline(order, $event, true)"
            ></textarea>
            <div
              v-if="orderNotesText(order)"
              class="copyable-cell-tooltip"
              @click.stop
            >
              {{ orderNotesText(order) }}
            </div>
          </div>

          <div v-if="isBoardColumnVisible('chat')" class="board-col board-col-chat" :style="boardColumnOrderStyle('chat')">
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

          <div v-if="isBoardColumnVisible('payment')" class="board-col board-col-payment" :style="boardColumnOrderStyle('payment')">
            <select v-if="isClient" class="board-inline-cell-input payment-input-inline" disabled>
              <option>{{ order.payment || 'Not Yet' }}</option>
            </select>
            <input
              v-else
              class="board-inline-cell-input payment-input-inline"
              :value="order.payment || 'Not Yet'"
              type="text"
              title="Click and edit payment"
              @click.stop
              @focus="beginTextEditing"
              @keydown.enter.prevent="$event.target.blur()"
              @blur="saveTextInline(order, 'payment', $event)"
            />
          </div>

          <div v-if="isBoardColumnVisible('address')" class="board-col board-col-address" :style="boardColumnOrderStyle('address')" @mouseenter="showCopyableHoverText(order.shippingAddress, $event)" @mouseleave="scheduleCopyableHoverHide">
            <input
              class="board-inline-cell-input"
              :value="inlineTextDraftValue(order, 'shipping_address')"
              type="text"
              placeholder="Add address"
              @focus="beginInlineTextEditing(order, 'shipping_address')"
              @input="updateInlineTextDraft(order, 'shipping_address', $event.target.value)"
              @click.stop
              @keydown.enter.prevent="$event.target.blur()"
              @blur="saveTextInline(order, 'shipping_address', $event)"
            />
            <div
              v-if="order.shippingAddress"
              class="copyable-cell-tooltip"
              @click.stop
            >
              {{ order.shippingAddress }}
            </div>
          </div>

          <div v-if="isBoardColumnVisible('track')" class="board-col board-col-track" :style="boardColumnOrderStyle('track')" @mouseenter="showCopyableHoverText(trackingSummary(order.trk), $event)" @mouseleave="scheduleCopyableHoverHide">
            <input
              class="board-inline-cell-input"
              :value="trackingSummary(order.trk)"
              type="text"
              placeholder="Tracking #"
              @click.stop
              @change="saveDirectInlineField(order, 'trk', $event.target.value)"
            />
            <div
              v-if="trackingSummary(order.trk) && trackingSummary(order.trk) !== 'N/A'"
              class="copyable-cell-tooltip"
              @click.stop
            >
              {{ trackingSummary(order.trk) }}
            </div>
          </div>

          <div class="board-col board-col-info" style="order:9999">
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


      <!-- OTHER SECTIONS: KEEP THEIR OWN NATURAL POSITION -->
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
            borderLeftColor: group.color,
            order: boardSectionOrder(group.key, 0)
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
    <!-- SUPER ADMIN BOARD SETTINGS -->
    <div
      v-if="boardSettingsModal && isSuperAdmin"
      class="board-settings-overlay"
      @click.self="boardSettingsModal = false"
    >
      <div class="board-settings-modal" @click.stop>
        <div class="board-settings-head">
          <div>
            <h3>Board Settings</h3>
            <p>Hide/show columns and create Dropdown, Text or Notes columns.</p>
          </div>
          <button type="button" @click="boardSettingsModal = false">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="board-settings-section">
          <h4>Column Positions</h4>
          <p class="column-position-help">Use arrows to place any standard or custom column wherever you want.</p>
          <div class="board-column-position-list">
            <div
              v-for="(column, index) in orderedBoardColumnItems"
              :key="'position-' + column.key"
              class="board-column-position-row"
            >
              <span class="board-column-position-number">{{ index + 1 }}</span>
              <strong>{{ column.label }}</strong>
              <small>{{ column.custom ? 'Custom' : 'Standard' }}</small>
              <button
                type="button"
                title="Move left"
                :disabled="index === 0 || boardSettingsSaving"
                @click="moveBoardColumn(column.key, -1)"
              >
                <i class="fa-solid fa-arrow-left"></i>
              </button>
              <button
                type="button"
                title="Move right"
                :disabled="index === orderedBoardColumnItems.length - 1 || boardSettingsSaving"
                @click="moveBoardColumn(column.key, 1)"
              >
                <i class="fa-solid fa-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="board-settings-section">
          <h4>Standard Columns</h4>
          <div class="board-column-toggle-grid">
            <label v-for="column in manageableStandardColumns" :key="column.key">
              <input
                type="checkbox"
                :checked="isBoardColumnVisible(column.key)"
                @change="toggleBoardColumnVisibility(column.key)"
              />
              <span>{{ column.label }}</span>
            </label>
          </div>
        </div>

        <div class="board-settings-section">
          <div class="board-settings-section-title">
            <h4>Custom Columns</h4>
          </div>

          <div class="custom-column-create custom-column-create-with-type">
            <input
              v-model="newCustomColumnName"
              type="text"
              placeholder="Column name e.g. PRIORITY / DESIGN NOTE"
              @keydown.enter.prevent="createCustomColumn"
            />

            <select
              v-model="newCustomColumnType"
              class="custom-column-type-select"
              title="Choose field type"
            >
              <option value="dropdown">Dropdown Labels</option>
              <option value="text">Single Line Text</option>
              <option value="notes">Multi Line Notes</option>
            </select>

            <button type="button" :disabled="!newCustomColumnName.trim()" @click="createCustomColumn">
              <i class="fa-solid fa-plus"></i> Add Column
            </button>
          </div>

          <div class="custom-field-type-help">
            <span><strong>Dropdown:</strong> Status/Priority type colored labels</span>
            <span><strong>Text:</strong> PO type short manual text</span>
            <span><strong>Notes:</strong> Multi-line written notes</span>
          </div>

          <div v-if="!customColumns.length" class="custom-column-empty">
            No custom columns yet.
          </div>

          <div
            v-for="column in orderedCustomColumns"
            :key="'manage-column-' + column.id"
            class="custom-column-manager"
            :data-custom-column-id="column.id"
          >
            <div class="custom-column-manager-head">
              <div class="custom-column-title-block">
                <strong>{{ column.name }}</strong>

                <select
                  :value="column.type || 'dropdown'"
                  class="custom-column-inline-type"
                  @change="changeCustomColumnType(column, $event.target.value)"
                >
                  <option value="dropdown">Dropdown</option>
                  <option value="text">Text</option>
                  <option value="notes">Notes</option>
                </select>
              </div>

              <div>
                <button type="button" title="Rename" @click="renameCustomColumn(column)">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <button type="button" class="danger" title="Delete" @click="deleteCustomColumn(column)">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </div>

            <template v-if="(column.type || 'dropdown') === 'dropdown'">
            <div class="custom-option-list">
              <div
                v-for="option in column.options || []"
                :key="'manage-option-' + option.id"
                class="custom-option-manage-row"
              >
                <span class="custom-option-color" :style="{ background: option.color || '#6161ff' }"></span>
                <span>{{ option.label }}</span>
                <button type="button" title="Edit" @click="editCustomColumnOption(column, option)">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <button type="button" class="danger" title="Delete" @click="deleteCustomColumnOption(column, option)">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </div>

            <div class="custom-option-inline-create">
              <input
                :value="customOptionDraft(column.id).label"
                type="text"
                placeholder="Write label e.g. Urgent"
                @input="setCustomOptionDraft(column.id, 'label', $event.target.value)"
                @keydown.enter.prevent="createCustomColumnOptionInline(column)"
              />

              <label class="custom-option-inline-color" title="Choose label color">
                <span
                  :style="{ background: customOptionDraft(column.id).color }"
                ></span>
                <input
                  :value="customOptionDraft(column.id).color"
                  type="color"
                  @input="setCustomOptionDraft(column.id, 'color', $event.target.value)"
                />
              </label>

              <button
                type="button"
                class="custom-option-add-btn"
                :disabled="!customOptionDraft(column.id).label.trim()"
                @click="createCustomColumnOptionInline(column)"
              >
                <i class="fa-solid fa-plus"></i>
                Add Label
              </button>
            </div>
            </template>

            <div
              v-else
              class="custom-non-dropdown-help"
            >
              <i :class="(column.type || 'text') === 'notes' ? 'fa-regular fa-note-sticky' : 'fa-solid fa-font'"></i>
              <span>
                {{ (column.type || 'text') === 'notes'
                  ? 'This column accepts multi-line written notes.'
                  : 'This column accepts short manual text.' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CUSTOM FIELD DROPDOWN -->
    <div
      v-if="customFieldMenu.order && customFieldMenu.column"
      class="custom-field-fixed-dropdown"
      :style="customFieldMenuStyle"
      @click.stop
    >
      <div class="custom-field-dropdown-head">
        <strong>{{ customFieldMenu.column.name }}</strong>

        <button
          type="button"
          @click="closeCustomFieldMenu"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- NOT SET -->
      <button
        type="button"
        class="custom-field-option-row custom-field-clear-row"
        @click="saveOrderCustomValue(
          customFieldMenu.order,
          customFieldMenu.column,
          null
        )"
      >
        <span class="custom-option-color clear"></span>
        <span>Not Set</span>
        <span></span>
      </button>

      <!-- OPTIONS -->
      <div
        v-for="option in customFieldMenu.column.options || []"
        :key="'custom-select-' + option.id"
        class="custom-field-option-manage-select-row"
      >
        <!--
          Click the small color dot:
          native color picker opens immediately.
        -->
        <label
          v-if="isSuperAdmin"
          class="custom-field-color-picker"
          title="Change label color"
          @click.stop
        >
          <span
            class="custom-option-color"
            :style="{
              background: option.color || '#fdab3d'
            }"
          ></span>

          <input
            type="color"
            :value="option.color || '#fdab3d'"
            @input.stop
            @change.stop="
              changeCustomOptionColor(
                customFieldMenu.column,
                option,
                $event.target.value
              )
            "
          />
        </label>

        <span
          v-else
          class="custom-option-color"
          :style="{
            background: option.color || '#fdab3d'
          }"
        ></span>

        <!-- NORMAL LABEL -->
        <button
          v-if="Number(customFieldEditingOptionId) !== Number(option.id)"
          type="button"
          class="custom-field-option-main"
          @click="
            saveOrderCustomValue(
              customFieldMenu.order,
              customFieldMenu.column,
              option
            )
          "
        >
          <span>{{ option.label }}</span>

          <i
            v-if="
              Number(
                getOrderCustomOption(
                  customFieldMenu.order,
                  customFieldMenu.column
                )?.id
              ) === Number(option.id)
            "
            class="fa-solid fa-check"
          ></i>
        </button>

        <!-- INLINE EDIT -->
        <div
          v-else
          class="custom-field-inline-edit"
        >
          <input
            v-model="customFieldEditingLabel"
            type="text"
            maxlength="120"
            autofocus
            @keydown.enter.prevent="
              saveCustomOptionInlineEdit(
                customFieldMenu.column,
                option
              )
            "
            @keydown.esc.prevent="
              cancelCustomOptionInlineEdit
            "
          />

          <button
            type="button"
            class="save"
            title="Save"
            @click="
              saveCustomOptionInlineEdit(
                customFieldMenu.column,
                option
              )
            "
          >
            <i class="fa-solid fa-check"></i>
          </button>

          <button
            type="button"
            title="Cancel"
            @click="cancelCustomOptionInlineEdit"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <!-- SUPER ADMIN ACTIONS -->
        <div
          v-if="isSuperAdmin"
          class="custom-field-option-actions"
        >
          <button
            type="button"
            title="Edit label"
            @click.stop="
              startCustomOptionInlineEdit(option)
            "
          >
            <i class="fa-solid fa-pen"></i>
          </button>

          <button
            type="button"
            class="danger"
            title="Delete label"
            @click.stop="
              deleteCustomColumnOption(
                customFieldMenu.column,
                option
              )
            "
          >
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
      </div>

      <!-- QUICK ADD DIRECTLY IN DROPDOWN -->
      <div
        v-if="isSuperAdmin"
        class="custom-field-quick-create"
      >
        <input
          v-model="customFieldQuickLabel"
          type="text"
          maxlength="120"
          placeholder="Add new label..."
          @keydown.enter.prevent="
            addCustomOptionFromDropdown(
              customFieldMenu.column
            )
          "
        />

        <button
          type="button"
          :disabled="!customFieldQuickLabel.trim()"
          @click="
            addCustomOptionFromDropdown(
              customFieldMenu.column
            )
          "
        >
          <i class="fa-solid fa-plus"></i>
          Add
        </button>
      </div>

      <div
        v-if="isSuperAdmin"
        class="custom-field-quick-hint"
      >
        <i class="fa-solid fa-circle-info"></i>
        New label gets a default color. Click its color dot to change it.
      </div>
    </div>

    <!-- ROW STATUS DROPDOWN -->
    <div
      v-if="canEditWorkflowFields && rowStatusMenuId && rowStatusMenuOrder"
      class="status-fixed-dropdown monday-status-menu"
      :style="rowStatusMenuStyle"
      @click.stop
    >
      <div class="monday-status-menu-head">
        <div>
          <strong>Change Status</strong>
          <small>{{ rowStatusMenuOrder?.name }}</small>
        </div>

        <button
          type="button"
          class="monday-status-close"
          title="Close"
          @click.stop="closeRowStatusMenu"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="monday-status-options">
        <div
          v-for="status in workflowStatusOptions"
          :key="'status-fixed-' + status.label"
          class="monday-status-row"
          :class="{
            active: status.label === rowStatusMenuOrder.status,
            editing: rowStatusEditingLabel === status.label
          }"
        >
          <!-- NORMAL VIEW -->
          <template v-if="rowStatusEditingLabel !== status.label">
            <button
              type="button"
              class="monday-status-select"
              @click.stop="selectRowStatus(rowStatusMenuOrder, status)"
            >
              <span
                class="monday-status-dot"
                :style="{ background: status.color }"
              ></span>

              <span class="monday-status-name">
                {{ status.label }}
              </span>

              <i
                v-if="status.label === rowStatusMenuOrder.status"
                class="fa-solid fa-check monday-status-check"
              ></i>
            </button>

            <div
              v-if="canManageStatusOption(status)"
              class="monday-status-actions"
            >
              <button
                type="button"
                title="Edit status"
                @click.stop="startRowStatusEdit(status)"
              >
                <i class="fa-solid fa-pen"></i>
              </button>

              <button
                type="button"
                class="danger"
                title="Delete status"
                @click.stop="deleteRowCustomStatus(status)"
              >
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </template>

          <!-- INLINE EDIT -->
          <div
            v-else
            class="monday-status-edit-row"
          >
            <label
              class="monday-status-color-button"
              title="Change color"
            >
              <span :style="{ background: rowStatusEditColor }"></span>

              <input
                v-model="rowStatusEditColor"
                type="color"
              />
            </label>

            <input
              v-model="rowStatusEditName"
              type="text"
              class="monday-status-edit-input"
              placeholder="Status name"
              @keydown.enter.prevent="saveRowStatusEdit(status)"
              @keydown.esc.prevent="cancelRowStatusEdit"
            />

            <button
              type="button"
              class="monday-status-save-edit"
              title="Save"
              @click.stop="saveRowStatusEdit(status)"
            >
              <i class="fa-solid fa-check"></i>
            </button>

            <button
              type="button"
              class="monday-status-cancel-edit"
              title="Cancel"
              @click.stop="cancelRowStatusEdit"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- ADD CUSTOM STATUS -->
      <div v-if="canManageWorkflow" class="monday-status-add">
        <div class="monday-status-add-title">
          <span class="monday-status-add-icon">
            <i class="fa-solid fa-plus"></i>
          </span>

          <div>
            <strong>Add Custom Status</strong>
            <small>Create your own label and color</small>
          </div>
        </div>

        <div class="monday-status-add-form">
          <label
            class="monday-status-add-color"
            title="Choose color"
          >
            <span :style="{ background: customStatusColor }"></span>

            <input
              v-model="customStatusColor"
              type="color"
            />
          </label>

          <input
            v-model="customStatusLabel"
            type="text"
            class="monday-status-add-input"
            placeholder="e.g. Waiting Approval"
            @keydown.enter.prevent="addCustomRowStatus(rowStatusMenuOrder)"
          />

          <button
            type="button"
            class="monday-status-add-button"
            :disabled="!customStatusLabel.trim()"
            @click.stop="addCustomRowStatus(rowStatusMenuOrder)"
          >
            Add
          </button>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL -->
    <div v-if="selectedOrder && detailOpen" class="board-detail-overlay" @click.self.stop="closeBoardDetail">
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

        <!-- SEARCH ANY ORDER FROM INSIDE DETAIL PAGE -->
        <div class="detail-order-search" @click.stop>
          <i class="fa-solid fa-magnifying-glass detail-order-search-icon"></i>

          <input
            v-model="detailSearchOrder"
            type="text"
            placeholder="Search another order..."
            autocomplete="off"
            @focus="detailSearchOpen = true"
            @input="detailSearchOpen = true"
            @keydown.enter.prevent="openFirstDetailSearchResult"
            @keydown.esc.prevent="detailSearchOpen = false"
          />

          <button
            v-if="detailSearchOrder"
            type="button"
            class="detail-order-search-clear"
            title="Clear search"
            @click.stop="clearDetailOrderSearch"
          >
            <i class="fa-solid fa-xmark"></i>
          </button>

          <div
            v-if="detailSearchOpen && detailSearchOrder.trim()"
            class="detail-order-search-dropdown"
          >
            <button
              v-for="order in detailSearchResults"
              :key="'detail-search-' + order.id"
              type="button"
              class="detail-order-search-item"
              :class="{ active: Number(order.id) === Number(selectedOrder?.id) }"
              @click.stop="openDetailSearchOrder(order)"
            >
              <span class="detail-search-folder">
                <i class="fa-solid fa-folder"></i>
              </span>

              <span class="detail-search-copy">
                <strong>{{ order.name }}</strong>
                <small>
                  {{ order.po || 'No PO' }}
                  <template v-if="order.status"> · {{ order.status }}</template>
                </small>
              </span>

              <i class="fa-solid fa-arrow-right detail-search-arrow"></i>
            </button>

            <div
              v-if="detailSearchResults.length === 0"
              class="detail-order-search-empty"
            >
              <i class="fa-regular fa-folder-open"></i>
              No matching order found
            </div>
          </div>
        </div>

        <!-- WHO STARTED THIS ORDER -->
        <div
          v-if="workingDesigner(selectedOrder)"
          class="detail-working-user"
          :title="workingDesigner(selectedOrder).name + ' is working on this order'"
        >
          <span class="detail-working-avatar">
            <img
              v-if="workingDesigner(selectedOrder).profile_photo_url"
              :src="workingDesigner(selectedOrder).profile_photo_url"
              :alt="workingDesigner(selectedOrder).name"
            />
            <span v-else>
              {{ initial(workingDesigner(selectedOrder).name) }}
            </span>

            <span class="detail-working-live-dot"></span>
          </span>

          <span class="detail-working-copy">
            <small>Working</small>
            <strong>{{ workingDesigner(selectedOrder).name }}</strong>
          </span>

          <button
            v-if="isSuperAdmin"
            type="button"
            class="detail-stop-work-button"
            title="Stop Work"
            @click.stop="stopWorking(selectedOrder)"
          >
            <i class="fa-solid fa-stop"></i>
          </button>
        </div>

        <button
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
  <span class="info-label">Shipping Address :</span>

  <span
  class="info-value"
  @click="showShippingAddressMenu = !showShippingAddressMenu"
>
{{ shortShippingAddress(selectedOrder?.shippingAddress) }}
    <i class="fa-solid fa-pen"></i>
  </span>

<div
  v-if="showShippingAddressMenu"
  class="tracking-dropdown"
>
    <textarea
      v-model="shippingAddressEdit"
      class="payment-input"
      rows="4"
      @focus="beginTextEditing"
      @keydown.enter.exact.prevent="saveShippingAddress"
      @blur="saveShippingAddress"
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
<span class="status-badge" :style="{ background: selectedOrder.statusColor }" @click="canEditWorkflowFields && (showStatusMenu = !showStatusMenu)">
                  {{ selectedOrder.status }}
              <i class="fa-solid fa-chevron-down" style="font-size:9px;margin-left:4px"></i>
            </span>
<div v-if="showStatusMenu && canEditWorkflowFields" class="status-dropdown">
                  <div v-for="s in workflowStatusOptions" :key="s.label" class="status-drop-item" @click="changeStatus(s)">
  <input
    type="color"
    class="status-dot status-color-picker"
    :value="s.color"
    @click.stop
    @input.stop="changeStatusOptionColor(s, $event.target.value)"
  />

  <span class="status-name">{{ s.label }}</span>

  <span class="status-group-tag">→ {{ s.groupLabel }}</span>

  <button v-if="canManageWorkflow && s.custom" class="status-action-btn" @click.stop="editCustomStatus(s)">
    <i class="fa-solid fa-pen"></i>
  </button>

  <button v-if="canManageWorkflow && s.custom" class="status-action-btn danger" @click.stop="deleteCustomStatus(s)">
    <i class="fa-solid fa-trash"></i>
  </button>
</div>
              <div v-if="canManageWorkflow" class="custom-status-box">
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
                  <span class="payment-chip payment-chip-paid">{{ isClient ? 'Not Yet' : (selectedOrder.payment || 'Not Yet') }}</span>
              <span class="payment-chip payment-chip-received">R ${{ selectedOrder.paymentReceived || 0 }}</span>
              <span class="payment-chip payment-chip-balance">B ${{ selectedOrder.paymentBalance || 0 }}</span>
              <i class="fa-solid fa-chevron-down" style="font-size:9px;margin-left:4px"></i>
            </span>
           <div v-if="showPaymentMenu && !isClient" class="payment-dropdown payment-dropdown-wide">
                  <div class="payment-dropdown-header">Payment Details</div>
              <div class="payment-read-row paid-row">
                <span>Paid</span><strong>{{ isClient ? 'Not Yet' : (selectedOrder.payment || 'Not Yet') }}</strong>
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
  @focus="beginTextEditing"
  @keydown.enter.exact.prevent="saveNote(card)"
  @blur="saveNote(card)"
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
                    <div v-for="(file, fi) in card.files" :key="file.id || file.url || fi" class="file-thumb">
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
                  <div v-if="uploadProgressByCard[card.type] !== undefined" class="card-upload-progress">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Uploading {{ uploadProgressByCard[card.type] }}%
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
      </div>


    <!-- FILES VIEW ALL MODAL -->
    <div
      v-if="viewAllCard"
      class="files-modal-overlay"
      @click.self="viewAllCard = null"
    >
      <div class="files-modal">
        <div class="files-modal-header">
          <div>
            <h3>{{ viewAllCard.title || 'Files' }}</h3>
            <p>{{ (viewAllCard.files || []).length }} file(s)</p>
          </div>

          <div class="files-modal-header-actions">
            <button
              v-if="viewAllCard.files && viewAllCard.files.length"
              type="button"
              class="files-modal-download-all"
              @click="downloadAllFiles(viewAllCard)"
            >
              <i class="fa-solid fa-download"></i>
              Download All
            </button>

            <button
              type="button"
              class="files-modal-close"
              title="Close"
              @click="viewAllCard = null"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>

        <div v-if="viewAllCard.files && viewAllCard.files.length" class="files-modal-grid">
          <div
            v-for="(file, index) in viewAllCard.files"
            :key="file.id || file.url || index"
            class="files-modal-item"
          >
            <button
              type="button"
              class="files-modal-preview"
              title="Click to view"
              @click="openPreviewFile(file)"
            >
              <img
                v-if="file.isImage && !file.imageError"
                :src="file.url"
                :alt="file.name"
                @error="file.imageError = true"
              />
              <span v-else class="files-modal-icon">
                <i :class="getFileIcon(file.name)"></i>
              </span>
            </button>

            <div class="files-modal-item-info">
              <strong :title="file.name">{{ file.name }}</strong>
              <small>{{ formatFileSize(file.size) }}</small>
            </div>

            <div class="files-modal-item-actions">
              <button type="button" title="View" @click="openPreviewFile(file)">
                <i class="fa-solid fa-eye"></i>
              </button>
              <button type="button" title="Download" @click="downloadSingleFile(file)">
                <i class="fa-solid fa-download"></i>
              </button>
              <button
                v-if="canDeleteFile(file) && !file.uploading"
                type="button"
                class="danger"
                title="Delete"
                @click="removeFile(viewAllCard, index)"
              >
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
          </div>
        </div>

        <div v-else class="files-modal-empty">
          <i class="fa-regular fa-folder-open"></i>
          <span>No files available</span>
        </div>
      </div>
    </div>

    <!-- SINGLE FILE PREVIEW MODAL -->
    <div
      v-if="previewFile"
      class="image-preview-overlay"
      @click.self="previewFile = null"
    >
      <div class="image-preview-modal universal-preview-modal">
        <div class="preview-modal-topbar">
          <strong :title="previewFile.name">{{ previewFile.name }}</strong>
          <div>
            <button type="button" title="Download" @click="downloadSingleFile(previewFile)">
              <i class="fa-solid fa-download"></i>
            </button>
            <button type="button" title="Close" @click="previewFile = null">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>

        <div class="preview-modal-body">
          <img
            v-if="previewFile.isImage"
            :src="previewFile.url"
            :alt="previewFile.name"
            class="image-preview-full"
          />

          <iframe
            v-else-if="canEmbedPreview(previewFile)"
            :src="previewEmbedUrl(previewFile)"
            class="file-preview-frame"
            frameborder="0"
          ></iframe>

          <div v-else class="file-preview-doc">
            <i :class="getFileIcon(previewFile.name)"></i>
            <strong>{{ previewFile.name }}</strong>
            <span>This file cannot be previewed directly in the browser.</span>
            <button type="button" class="download-all-btn" @click="openFileNewTab(previewFile)">
              <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Open File
            </button>
          </div>
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
            <button type="button" class="member-preview-remove" title="Remove member" @click="removeBulkMember(member.id)">
              <i class="fa-solid fa-xmark"></i>
            </button>
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
            <button type="button" class="member-preview-remove" title="Remove client" @click="removeBulkClient(client.id)">
              <i class="fa-solid fa-xmark"></i>
            </button>
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

    <!-- Fixed layer: never clipped by table overflow; text is selectable/copyable. -->
    <div
      v-if="copyableHoverTooltip.text"
      class="global-copyable-tooltip"
      :style="copyableHoverTooltipStyle"
      @mouseenter="cancelCopyableHoverHide"
      @mouseleave="hideCopyableHoverText"
      @click.stop
    >
      {{ copyableHoverTooltip.text }}
    </div>

    <Teleport to="body">
      <Transition name="client-filter-slide">
        <div
          v-if="clientFilterOpen"
          class="client-filter-overlay"
          @click.self="closeClientFilter"
        >
          <aside class="client-filter-drawer" @click.stop>
            <header class="client-filter-header">
              <div>
                <h2>Client Filter</h2>
                <p>Select a client to view their orders.</p>
              </div>
              <button type="button" class="client-filter-close" @click="closeClientFilter">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </header>

            <div class="client-filter-search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input
                v-model.trim="clientSearch"
                type="search"
                placeholder="Search clients..."
                autofocus
                @input="clientFilterListOpen = true"
              />

              <button
                type="button"
                class="client-filter-dropdown-toggle"
                :class="{ open: clientFilterListOpen }"
                title="Show all clients"
                @click="clientFilterListOpen = !clientFilterListOpen"
              >
                <i class="fa-solid fa-chevron-down"></i>
              </button>
            </div>

            <div v-if="clientFilterListOpen" class="client-filter-list">
              <button
                type="button"
                class="client-filter-item"
                :class="{ active: !selectedClient }"
                @click="selectClientFilter('')"
              >
                <span class="client-filter-avatar"><i class="fa-solid fa-users"></i></span>
                <span><strong>All Clients</strong><small>Show every order</small></span>
                <i v-if="!selectedClient" class="fa-solid fa-check"></i>
              </button>

              <button
                v-for="client in filteredClientOptions"
                :key="client.id"
                type="button"
                class="client-filter-item"
                :class="{ active: Number(selectedClient) === Number(client.id) }"
                @click="selectClientFilter(client.id)"
              >
                <span class="client-filter-avatar">{{ initial(client.name || client.email) }}</span>
                <span><strong>{{ client.name || 'Unnamed client' }}</strong><small>{{ client.email || client.company || '' }}</small></span>
                <i v-if="Number(selectedClient) === Number(client.id)" class="fa-solid fa-check"></i>
              </button>

              <div v-if="filteredClientOptions.length === 0" class="client-filter-empty">No clients found.</div>
            </div>
          </aside>
        </div>
      </Transition>
    </Teleport>
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
      clientFilterOpen: false,
      clientFilterListOpen: false,
      textEditing: false,
      noteSaving: false,
      inlineTextDrafts: {},
      inlineTextDirty: {},
      inlineTextSaving: {},
      lastOpenedOrderId: Number(localStorage.getItem('factory_last_opened_order_id') || 0),
      uploadProgressByOrder: {},
      uploadProgressByCard: {},
      copyableHoverTooltip: {
        text: '',
        top: 0,
        left: 0,
        width: 320
      },
      copyableHoverHideTimer: null,
      boardSettingsModal: false,
      boardSettingsSaving: false,
      columnOrderSaveTimer: null,
      columnOrderDirty: false,
      boardSettings: {
        auto_assign_all_owners: false,
        hidden_columns: [],
        column_order: []
      },
      customColumns: [],
      boardCustomValuesByOrder: {},
      newCustomColumnName: '',
      newCustomColumnType: 'dropdown',
      customOptionDrafts: {},
      customFieldMenu: {
        order: null,
        column: null,
        top: 0,
        left: 0,
        width: 260
      },

      // Inline management inside custom dropdown
      customFieldQuickLabel: '',
      customFieldEditingOptionId: null,
      customFieldEditingLabel: '',

      dragActiveCardType: null,
      dragCounter: 0,
      detailOpen: false,
      inlineAddOpen: false,
      inlineOrderName: '',
      inlinePriorityOptionId: '',
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
        name: 380,
        status: 170,
        owner: 120,
        files: 190,
        packing: 130,
        notes: 190,
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
      notificationTab: 'chats',
      activeSectionCollapsed: false,
      boardTheme: 'light',
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

      // Row status manager
      rowStatusEditingLabel: null,
      rowStatusEditName: '',
      rowStatusEditColor: '#6161ff',

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
      sharedBoardTimer: null,
      orderSyncTimer: null,
      chatSyncTimer: null,
      noteClockTimer: null,
      nowTick: Date.now(),
      clientNoteSavedAtByOrder: JSON.parse(
        localStorage.getItem('client_note_saved_at_by_order') || '{}'
      ),
      notifications: [],
      notificationCount: 0,
      lastNotificationId: null,
      searchOrder: '',
      clientSearch: '',
      detailSearchOrder: '',
      detailSearchOpen: false,
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
      payment: 'Not Yet',
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
        { label: 'Delivered', color: '#00c875', group: 'delivered', groupLabel: 'Delivered' }
      ]
    }
  },

  computed: {
    copyableHoverTooltipStyle() {
      return {
        position: 'fixed',
        top: `${this.copyableHoverTooltip.top}px`,
        left: `${this.copyableHoverTooltip.left}px`,
        width: `${this.copyableHoverTooltip.width}px`,
        zIndex: 2147483647
      }
    },
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

    workflowStatusOptions() {
      const defaultLabels = {
        in_production: 'In Production',
        completed: 'Completed',
        shipped: 'Shipped',
        delivered: 'Delivered'
      }

      return this.boardGroups.map(group => {
        const expectedLabel =
          this.defaultBoardGroupOverrides?.[group.key]?.label ||
          defaultLabels[group.key] ||
          group.label

        const existing = this.statusOptions.find(status =>
          String(status.label || '').trim().toLowerCase() ===
            String(expectedLabel || '').trim().toLowerCase()
        ) || this.statusOptions.find(status =>
          status.group === group.key && status.custom === true
        )

        return this.normalizeStatusDefinition(existing || {
          label: expectedLabel,
          color: group.color,
          group: group.key,
          groupLabel: group.label,
          custom: group.custom === true
        })
      })
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

  if (this.isClient) {
    const note = this.selectedOrder.cards?.find(card => card.type === 'notes')
    const hasNote = Boolean(String(note?.noteText || '').trim())
    return !hasNote && !this.clientNoteSavedAtByOrder[Number(this.selectedOrder.id)]
  }

  return this.hasFullOrderAccess
    || this.currentUser?.can_create_orders === true
},
canUploadFiles() {
  return this.hasFullOrderAccess
    || this.isClient
    || this.currentUser?.can_create_orders === true
},

canEditOrderNotes() {
  return this.hasFullOrderAccess
    || this.currentUser?.can_create_orders === true
},

canManageWorkflow() {
  if (this.isClient) return false

  return this.isSuperAdmin
    || this.isAdmin
    || this.currentUser?.can_create_orders === true
},
canEditWorkflowFields() {
  return this.canManageWorkflow
},
    currentUser() {
      try { return JSON.parse(localStorage.getItem('user')) || null } catch { return null }
    },
    isSuperAdmin() { return this.currentUser?.role === 'super_admin' },
    isAdmin() { return this.currentUser?.role === 'admin' },
    hasFullOrderAccess() { return this.isSuperAdmin || this.isAdmin },
   canCreateOrder() {
  return this.hasFullOrderAccess
    || this.isClient
    || this.currentUser?.can_create_orders === true
},

    userInitial() {
      const raw = localStorage.getItem('user')
      if (!raw) return 'A'
      try { const user = JSON.parse(raw); return user?.name ? user.name.charAt(0).toUpperCase() : 'A' } catch { return 'A' }
    },
    userPhoto() { return this.currentUser?.profile_photo_url || null },

    accessibleOrders() {
      if (!this.isClient) return this.orders

      const userId = Number(this.currentUser?.id || 0)
      const email = String(this.currentUser?.email || '').trim().toLowerCase()

      return this.orders.filter(order =>
        (order.clients || []).some(client =>
          Number(client.user_id || 0) === userId ||
          String(client.email || '').trim().toLowerCase() === email
        )
      )
    },

    filteredClientOptions() {
      const query = String(this.clientSearch || '').trim().toLowerCase()
      return [...this.availableClients]
        .filter(client => !query || [client.name, client.email, client.company]
          .filter(Boolean)
          .join(' ')
          .toLowerCase()
          .includes(query))
        .sort((a, b) => String(a.name || a.email || '').localeCompare(String(b.name || b.email || '')))
    },

    detailSearchResults() {
      const query = String(this.detailSearchOrder || '')
        .trim()
        .toLowerCase()

      if (!query) return []

      return this.orders
        .filter(order => {
          const searchable = [
            order.name,
            order.po,
            order.status,
            order.shippingAddress,
            order.trk
          ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()

          return searchable.includes(query)
        })
        .sort((a, b) =>
          String(a.name || '').localeCompare(String(b.name || ''), 'en', {
            sensitivity: 'base',
            numeric: true
          })
        )
        .slice(0, 8)
    },

filteredOrders() {
  return this.accessibleOrders
    .filter(o => {
      const groupMatch =
        this.activeGroup === 'all' ||
        o.group === this.activeGroup ||
        (
          this.activeGroup === 'delivered' &&
          String(o.status || '').toLowerCase() === 'delivered'
        )

      return groupMatch && this.orderMatchesCurrentSearch(o)
    })
    .sort((a, b) => {
      // Chat activity never changes row position. Newest created order stays first.
      const aCreated = new Date(a.created_at || 0).getTime()
      const bCreated = new Date(b.created_at || 0).getTime()

      if (aCreated !== bCreated) return bCreated - aCreated
      return Number(b.id || 0) - Number(a.id || 0)
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

    unreadOrderNotifications() {
      return this.orders
        .filter(order => !order.user_has_seen)
        .sort((a, b) => {
          const aTime = new Date(a.created_at || 0).getTime()
          const bTime = new Date(b.created_at || 0).getTime()
          return bTime - aTime
        })
    },

    unreadOrderNotificationCount() {
      return this.unreadOrderNotifications.length
    },

    totalBellNotificationCount() {
      return this.totalUnreadChatCount + this.unreadOrderNotificationCount
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
        width: `${Number(pos.width || 320)}px`,
        zIndex: 2147483647
      }
    },

    manageableStandardColumns() {
      return [
        { key: 'status', label: 'Status' },
        { key: 'owner', label: 'Owner' },
        { key: 'files', label: 'Files' },
        { key: 'packing', label: 'Packing Detail' },
        { key: 'notes', label: 'Notes' },
        { key: 'chat', label: 'Chat' },
        { key: 'payment', label: 'Payment' },
        { key: 'address', label: 'Address' },
        { key: 'track', label: 'Tracking' }
      ]
    },

    activeCustomColumns() {
      return this.orderedCustomColumns
        .filter(column => column && column.is_active !== false)
    },

    priorityColumn() {
      return this.activeCustomColumns.find(column => this.isPriorityColumn(column)) || null
    },

    priorityOptions() {
      return Array.isArray(this.priorityColumn?.options)
        ? this.priorityColumn.options
        : []
    },

    orderedCustomColumns() {
      return [...(this.customColumns || [])]
        .filter(Boolean)
        .sort((a, b) => {
          const positionDifference = Number(a.position || 0) - Number(b.position || 0)
          return positionDifference || Number(a.id || 0) - Number(b.id || 0)
        })
    },

    allBoardColumnItems() {
      return [
        { key: 'name', label: 'Order Name', custom: false },
        ...this.manageableStandardColumns.map(item => ({ ...item, custom: false })),
        ...this.orderedCustomColumns.map(column => ({
          key: `custom_${column.id}`,
          label: column.name,
          custom: true
        }))
      ]
    },

    orderedBoardColumnItems() {
      const items = this.allBoardColumnItems
      const itemMap = new Map(items.map(item => [item.key, item]))
      const savedOrder = Array.isArray(this.boardSettings.column_order)
        ? this.boardSettings.column_order
        : []

      const ordered = savedOrder
        .filter(key => itemMap.has(key))
        .map(key => itemMap.get(key))

      items.forEach(item => {
        if (!ordered.some(existing => existing.key === item.key)) ordered.push(item)
      })

      return ordered
    },

    customFieldMenuStyle() {
      const menu = this.customFieldMenu || {}
      return {
        position: 'fixed',
        top: `${Number(menu.top || 0)}px`,
        left: `${Number(menu.left || 0)}px`,
        width: `${Number(menu.width || 220)}px`,
        zIndex: 2147483647
      }
    },

    boardGridStyle() {
      const w = this.columnWidths

      const activeCustomKeys = new Set(
        this.activeCustomColumns.map(column => `custom_${column.id}`)
      )

      const orderedDataKeys = this.orderedBoardColumnItems
        .map(item => item.key)
        .filter(key => {
          if (key === 'name') return true
          if (key.startsWith('custom_')) return activeCustomKeys.has(key)
          return this.isBoardColumnVisible(key)
        })

      const keys = ['check', ...orderedDataKeys, 'info']

      activeCustomKeys.forEach(key => {
        if (!w[key]) w[key] = 145
      })

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
  this.loadSavedStatusOptions()
  this.loadBoardGroups()
  this.loadDefaultBoardGroupOverrides()
  await this.fetchBoardConfiguration()

  await Promise.all([
    this.fetchOrders(),
    this.fetchMembers(),
    this.fetchClients()
  ])
  if ('Notification' in window) {
    Notification.requestPermission()
  }

  await this.fetchNotifications(false)

  // Cross-user sync fallback. Server remains the single source of truth.
  this.sharedBoardTimer = window.setInterval(
    () => this.fetchBoardConfiguration({ silent: true }),
    3000
  )
  this.orderSyncTimer = window.setInterval(
    () => {
      if (!this.textEditing) {
        this.fetchOrders({ silent: true, loadFiles: false })
      }
    },
    4000
  )
  this.chatSyncTimer = window.setInterval(() => {
    if (this.showChat && this.selectedOrder?.id) {
      this.fetchMessages(this.selectedOrder.id, { silent: true })
    }
  }, 1500)
  this.noteClockTimer = window.setInterval(() => {
    this.nowTick = Date.now()
  }, 15000)


  const orderId = this.$route.query.order_id
  const openChat = this.$route.query.open_chat

   if (orderId) {
  const foundOrder = this.orders.find(o => Number(o.id) === Number(orderId))

  if (foundOrder) {
    this.activeGroup = foundOrder.group
    await this.openBoardOrder(foundOrder)

    if (openChat == 1) {
      this.showChat = true
      await this.markChatRead()
    }
  }
} else {
  /*
   * Do NOT silently select the first order.
   * This prevents the detail form from jumping back to row #1.
   */
  this.selectedOrder = null
  this.detailOpen = false
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

  clearInterval(this.sharedBoardTimer)
  clearInterval(this.orderSyncTimer)
  clearInterval(this.chatSyncTimer)
  clearInterval(this.noteClockTimer)
  clearTimeout(this.columnOrderSaveTimer)
  document.body.style.overflow = ''
},



 methods: {
    showCopyableHoverText(rawText, event) {
      const text = String(rawText || '').trim()

      if (!text || text.toLowerCase() === 'n/a') {
        this.hideCopyableHoverText()
        return
      }

      this.cancelCopyableHoverHide()

      const rect = event?.currentTarget?.getBoundingClientRect?.()
      if (!rect) return

      const width = Math.min(360, Math.max(240, rect.width + 80))
      const padding = 12
      let left = rect.left
      let top = rect.bottom + 6

      if (left + width > window.innerWidth - padding) {
        left = window.innerWidth - width - padding
      }

      if (left < padding) left = padding

      // Near the bottom, place the popup above the row.
      if (top + 190 > window.innerHeight) {
        top = Math.max(padding, rect.top - 190)
      }

      this.copyableHoverTooltip = { text, top, left, width }
    },

    scheduleCopyableHoverHide() {
      this.cancelCopyableHoverHide()
      this.copyableHoverHideTimer = setTimeout(
        () => this.hideCopyableHoverText(),
        280
      )
    },

    cancelCopyableHoverHide() {
      if (this.copyableHoverHideTimer) {
        clearTimeout(this.copyableHoverHideTimer)
        this.copyableHoverHideTimer = null
      }
    },

    hideCopyableHoverText() {
      this.cancelCopyableHoverHide()
      this.copyableHoverTooltip = { text: '', top: 0, left: 0, width: 320 }
    },

    isBoardColumnVisible(key) {
      return !(this.boardSettings.hidden_columns || []).includes(key)
    },

    async fetchBoardConfiguration() {
      if (this.boardSettingsSaving) return

      try {
        const res = await axios.get('/api/factory-board/config', {
          headers: this.headers()
        })

        const data = res.data?.data || res.data || {}
        const serverColumnOrder = Array.isArray(data.settings?.column_order)
          ? data.settings.column_order
          : []
        const localColumnOrder = JSON.parse(
          localStorage.getItem('factory_board_column_order') || '[]'
        )
        const effectiveColumnOrder =
          this.isSuperAdmin && Array.isArray(localColumnOrder) && localColumnOrder.length
            ? localColumnOrder
            : serverColumnOrder

        this.boardSettings = {
          auto_assign_all_owners: Boolean(data.settings?.auto_assign_all_owners),
          hidden_columns: Array.isArray(data.settings?.hidden_columns)
            ? data.settings.hidden_columns
            : [],
          column_order: effectiveColumnOrder
        }

        if (Array.isArray(data.settings?.status_options)) {
          this.statusOptions = data.settings.status_options.map(
            status => this.normalizeStatusDefinition(status)
          )
        }
        if (Array.isArray(data.settings?.custom_groups)) {
          this.customBoardGroups = data.settings.custom_groups
        }
        if (data.settings?.default_group_overrides) {
          this.defaultBoardGroupOverrides = data.settings.default_group_overrides
        }
        this.customColumns = Array.isArray(data.custom_columns)
          ? data.custom_columns
          : []

        const rawValues = data.custom_values || {}
        this.boardCustomValuesByOrder = Object.keys(rawValues).reduce((map, orderId) => {
          map[Number(orderId)] = Array.isArray(rawValues[orderId])
            ? rawValues[orderId]
            : []
          return map
        }, {})
      } catch (error) {
        console.error('fetchBoardConfiguration error:', error)
      }
    },

    async toggleAutoAssignAllOwners() {
      if (!this.isSuperAdmin || this.boardSettingsSaving) return
      this.boardSettingsSaving = true
      const next = !this.boardSettings.auto_assign_all_owners

      try {
        const res = await axios.put(
          '/api/factory-board/settings',
          {
            auto_assign_all_owners: next,
            hidden_columns: this.boardSettings.hidden_columns || [],
            column_order: this.boardSettings.column_order || []
          },
          { headers: this.headers() }
        )
        this.boardSettings.auto_assign_all_owners = Boolean(
          res.data?.settings?.auto_assign_all_owners ??
          res.data?.data?.auto_assign_all_owners ??
          next
        )
      } catch (error) {
        console.error('toggleAutoAssignAllOwners error:', error)
        alert(error.response?.data?.message || 'Owner auto-select setting could not be saved.')
      } finally {
        this.boardSettingsSaving = false
      }
    },

    async toggleBoardColumnVisibility(key) {
      if (!this.isSuperAdmin) return

      const hidden = new Set(this.boardSettings.hidden_columns || [])
      if (hidden.has(key)) hidden.delete(key)
      else hidden.add(key)

      try {
        const res = await axios.put(
          '/api/factory-board/settings',
          {
            auto_assign_all_owners: Boolean(
              this.boardSettings.auto_assign_all_owners
            ),
            hidden_columns: [...hidden],
            column_order: this.boardSettings.column_order || []
          },
          { headers: this.headers() }
        )
        const savedHidden =
          res.data?.settings?.hidden_columns ??
          res.data?.data?.hidden_columns

        this.boardSettings.hidden_columns = Array.isArray(savedHidden)
          ? savedHidden
          : [...hidden]
      } catch (error) {
        console.error('toggleBoardColumnVisibility error:', error)
        alert(error.response?.data?.message || 'Column setting could not be saved.')
      }
    },

    boardColumnOrderStyle(key) {
      const index = this.orderedBoardColumnItems.findIndex(item => item.key === key)
      return { order: index < 0 ? 0 : index + 1 }
    },

    moveBoardColumn(key, direction) {
      if (!this.isSuperAdmin) return

      const order = this.orderedBoardColumnItems.map(item => item.key)
      const currentIndex = order.indexOf(key)
      const targetIndex = currentIndex + Number(direction)

      if (currentIndex < 0 || targetIndex < 0 || targetIndex >= order.length) return

      const [movedKey] = order.splice(currentIndex, 1)
      order.splice(targetIndex, 0, movedKey)

      this.boardSettings.column_order = order
      this.columnOrderDirty = true
      localStorage.setItem(
        'factory_board_column_order',
        JSON.stringify(order)
      )

      clearTimeout(this.columnOrderSaveTimer)
      this.columnOrderSaveTimer = window.setTimeout(
        () => this.saveBoardColumnOrder(),
        500
      )
    },

    async saveBoardColumnOrder() {
      if (!this.isSuperAdmin || !this.columnOrderDirty) return

      const order = [...(this.boardSettings.column_order || [])]
      this.boardSettingsSaving = true

      try {
        const res = await axios.put(
          '/api/factory-board/settings',
          {
            auto_assign_all_owners: Boolean(this.boardSettings.auto_assign_all_owners),
            hidden_columns: this.boardSettings.hidden_columns || [],
            column_order: order
          },
          { headers: this.headers() }
        )

        const savedOrder =
          res.data?.settings?.column_order ??
          res.data?.data?.column_order

        if (
          Array.isArray(savedOrder) &&
          savedOrder.length &&
          JSON.stringify(savedOrder) !== JSON.stringify(order)
        ) {
          console.warn('Server returned a different column order; keeping Super Admin order.', savedOrder)
        }

        this.boardSettings.column_order = order
        localStorage.setItem(
          'factory_board_column_order',
          JSON.stringify(order)
        )
        this.columnOrderDirty = false
      } catch (error) {
        console.error('moveBoardColumn error:', error)
        alert(error.response?.data?.message || 'Column position is fixed on this browser, but the server could not save it.')
      } finally {
        this.boardSettingsSaving = false
      }
    },

    async createCustomColumn() {
      if (!this.isSuperAdmin) return

      const name = String(this.newCustomColumnName || '').trim().toUpperCase()
      const type = String(this.newCustomColumnType || 'dropdown')

      if (!name) return

      try {
        await axios.post(
          '/api/factory-board/custom-columns',
          { name, type },
          { headers: this.headers() }
        )

        this.newCustomColumnName = ''
        this.newCustomColumnType = 'dropdown'

        await this.fetchBoardConfiguration()
      } catch (error) {
        console.error('createCustomColumn error:', error)
        alert(error.response?.data?.message || 'Custom column could not be created.')
      }
    },

    async changeCustomColumnType(column, type) {
      if (!this.isSuperAdmin || !column) return

      if (
        !confirm(
          `Change "${column.name}" to ${String(type).toUpperCase()} field?`
        )
      ) {
        return
      }

      try {
        await axios.put(
          `/api/factory-board/custom-columns/${column.id}`,
          {
            name: column.name,
            type
          },
          {
            headers: this.headers()
          }
        )

        await this.fetchBoardConfiguration()
      } catch (error) {
        alert(
          error.response?.data?.message ||
          'Column type could not be changed.'
        )
      }
    },

    customColumnIndex(column) {
      return this.orderedCustomColumns.findIndex(
        item => Number(item.id) === Number(column?.id)
      )
    },

    async moveCustomColumn(column, direction) {
      if (!this.isSuperAdmin || !column || this.boardSettingsSaving) return

      const columns = this.orderedCustomColumns
      const currentIndex = this.customColumnIndex(column)
      const targetIndex = currentIndex + Number(direction)

      if (currentIndex < 0 || targetIndex < 0 || targetIndex >= columns.length) return

      const reordered = [...columns]
      const [movedColumn] = reordered.splice(currentIndex, 1)
      reordered.splice(targetIndex, 0, movedColumn)

      const previousColumns = [...this.customColumns]
      this.customColumns = reordered.map((item, index) => ({
        ...item,
        position: index + 1
      }))
      this.boardSettingsSaving = true

      try {
        await Promise.all(
          this.customColumns.map(item =>
            axios.put(
              `/api/factory-board/custom-columns/${item.id}`,
              {
                name: item.name,
                type: item.type || 'dropdown',
                position: item.position
              },
              { headers: this.headers() }
            )
          )
        )

        await this.fetchBoardConfiguration()
      } catch (error) {
        this.customColumns = previousColumns
        console.error('moveCustomColumn error:', error)
        alert(error.response?.data?.message || 'Column position could not be saved.')
      } finally {
        this.boardSettingsSaving = false
      }
    },

    openCustomColumnSettings(column) {
      this.closeCustomFieldMenu()
      this.boardSettingsModal = true

      this.$nextTick(() => {
        const target = document.querySelector(
          `[data-custom-column-id="${column?.id}"]`
        )

        target?.scrollIntoView({
          block: 'center',
          behavior: 'smooth'
        })
      })
    },

    async renameCustomColumn(column) {
      if (!this.isSuperAdmin || !column) return
      const name = window.prompt('New column name', column.name)
      if (!name || !name.trim()) return

      try {
        await axios.put(`/api/factory-board/custom-columns/${column.id}`, {
          name: name.trim().toUpperCase()
        }, { headers: this.headers() })
        await this.fetchBoardConfiguration()
      } catch (error) {
        alert(error.response?.data?.message || 'Column could not be renamed.')
      }
    },

    async deleteCustomColumn(column) {
      if (!this.isSuperAdmin || !column) return
      if (!confirm(`Delete "${column.name}" column?`)) return

      try {
        await axios.delete(`/api/factory-board/custom-columns/${column.id}`, { headers: this.headers() })
        await this.fetchBoardConfiguration()
      } catch (error) {
        alert(error.response?.data?.message || 'Column could not be deleted.')
      }
    },

    customDefaultOptionColor(column) {
      const palette = [
        '#fdab3d',
        '#00c875',
        '#579bfc',
        '#e2445c',
        '#a25ddc',
        '#00c2ff',
        '#ff642e',
        '#037f4c'
      ]

      const count =
        Array.isArray(column?.options)
          ? column.options.length
          : 0

      return palette[count % palette.length]
    },

    customOptionDraft(columnId) {
      const id = Number(columnId)

      if (!this.customOptionDrafts[id]) {
        const column =
          this.customColumns.find(
            item => Number(item.id) === id
          )

        this.customOptionDrafts[id] = {
          label: '',
          color: this.customDefaultOptionColor(column)
        }
      }

      return this.customOptionDrafts[id]
    },

    setCustomOptionDraft(columnId, key, value) {
      const id = Number(columnId)
      const current = this.customOptionDraft(id)

      this.customOptionDrafts[id] = {
        ...current,
        [key]: value
      }
    },

    async createCustomColumnOptionInline(column) {
      if (!this.isSuperAdmin || !column) return

      const draft = this.customOptionDraft(column.id)
      const label = String(draft.label || '').trim()
      const color = String(draft.color || '#fdab3d').trim() || '#fdab3d'

      if (!label) return

      try {
        await axios.post(
          `/api/factory-board/custom-columns/${column.id}/options`,
          {
            label,
            color
          },
          {
            headers: this.headers()
          }
        )

        await this.fetchBoardConfiguration()

        const refreshedColumn =
          this.customColumns.find(
            item => Number(item.id) === Number(column.id)
          )

        this.customOptionDrafts[Number(column.id)] = {
          label: '',
          color: this.customDefaultOptionColor(refreshedColumn)
        }

        if (this.customFieldMenu.column?.id === column.id) {
          this.customFieldMenu.column =
            this.customColumns.find(
              item => Number(item.id) === Number(column.id)
            ) || column
        }
      } catch (error) {
        console.error('createCustomColumnOptionInline error:', error)
        alert(
          error.response?.data?.message ||
          'Dropdown label could not be added.'
        )
      }
    },

    async addCustomColumnOption(column) {
      if (!this.isSuperAdmin || !column) return
      const label = window.prompt(`Add option in ${column.name}`)
      if (!label || !label.trim()) return
      const color = window.prompt('Color hex (example #ff3b30)', '#fdab3d') || '#fdab3d'

      try {
        await axios.post(`/api/factory-board/custom-columns/${column.id}/options`, {
          label: label.trim(),
          color
        }, { headers: this.headers() })
        await this.fetchBoardConfiguration()
        if (this.customFieldMenu.column?.id === column.id) {
          this.customFieldMenu.column = this.customColumns.find(c => Number(c.id) === Number(column.id)) || column
        }
      } catch (error) {
        alert(error.response?.data?.message || 'Option could not be added.')
      }
    },

    startCustomOptionInlineEdit(option) {
      if (!this.isSuperAdmin || !option) return

      this.customFieldEditingOptionId =
        Number(option.id)

      this.customFieldEditingLabel =
        String(option.label || '')
    },

    cancelCustomOptionInlineEdit() {
      this.customFieldEditingOptionId = null
      this.customFieldEditingLabel = ''
    },

    async saveCustomOptionInlineEdit(column, option) {
      if (
        !this.isSuperAdmin ||
        !column ||
        !option
      ) {
        return
      }

      const label =
        String(
          this.customFieldEditingLabel || ''
        ).trim()

      if (!label) {
        return
      }

      try {
        await axios.put(
          `/api/factory-board/custom-options/${option.id}`,
          {
            label,
            color:
              option.color ||
              '#fdab3d'
          },
          {
            headers:
              this.headers()
          }
        )

        this.cancelCustomOptionInlineEdit()

        await this.fetchBoardConfiguration()

        this.refreshOpenCustomFieldColumn(
          column.id
        )
      } catch (error) {
        alert(
          error.response?.data?.message ||
          'Label could not be edited.'
        )
      }
    },

    async changeCustomOptionColor(
      column,
      option,
      color
    ) {
      if (
        !this.isSuperAdmin ||
        !column ||
        !option ||
        !color
      ) {
        return
      }

      /*
       * Change UI immediately so picker feels instant.
       */
      option.color = color

      try {
        await axios.put(
          `/api/factory-board/custom-options/${option.id}`,
          {
            label:
              option.label,

            color
          },
          {
            headers:
              this.headers()
          }
        )

        await this.fetchBoardConfiguration()

        this.refreshOpenCustomFieldColumn(
          column.id
        )
      } catch (error) {
        alert(
          error.response?.data?.message ||
          'Color could not be changed.'
        )

        await this.fetchBoardConfiguration()

        this.refreshOpenCustomFieldColumn(
          column.id
        )
      }
    },

    refreshOpenCustomFieldColumn(columnId) {
      if (
        !this.customFieldMenu.column ||
        Number(this.customFieldMenu.column.id) !==
          Number(columnId)
      ) {
        return
      }

      const fresh =
        this.customColumns.find(
          column =>
            Number(column.id) ===
            Number(columnId)
        )

      if (fresh) {
        this.customFieldMenu.column =
          fresh
      }
    },

    async addCustomOptionFromDropdown(column) {
      if (
        !this.isSuperAdmin ||
        !column
      ) {
        return
      }

      const label =
        String(
          this.customFieldQuickLabel || ''
        ).trim()

      if (!label) {
        return
      }

      const color =
        this.customDefaultOptionColor(
          column
        )

      try {
        await axios.post(
          `/api/factory-board/custom-columns/${column.id}/options`,
          {
            label,
            color
          },
          {
            headers:
              this.headers()
          }
        )

        this.customFieldQuickLabel = ''

        await this.fetchBoardConfiguration()

        this.refreshOpenCustomFieldColumn(
          column.id
        )
      } catch (error) {
        alert(
          error.response?.data?.message ||
          'Label could not be added.'
        )
      }
    },

    async deleteCustomColumnOption(column, option) {
      if (!this.isSuperAdmin || !option) return
      if (!confirm(`Delete "${option.label}" option?`)) return

      try {
        await axios.delete(`/api/factory-board/custom-options/${option.id}`, { headers: this.headers() })
        await this.fetchBoardConfiguration()
        this.refreshOpenCustomFieldColumn(column?.id)
      } catch (error) {
        alert(error.response?.data?.message || 'Option could not be deleted.')
      }
    },

    getOrderCustomValue(order, column) {
      if (!order || !column) return null

      const values =
        Array.isArray(order.custom_values)
          ? order.custom_values
          : []

      return values.find(
        item =>
          Number(item.column_id) ===
          Number(column.id)
      ) || null
    },

    getOrderCustomText(order, column) {
      const item =
        this.getOrderCustomValue(
          order,
          column
        )

      return String(
        item?.value ?? ''
      )
    },

    async saveOrderCustomText(order, column, rawValue) {
      if (
        !this.canEditCustomField(column) ||
        !order ||
        !column
      ) {
        return
      }

      const value =
        String(rawValue ?? '')

      const oldValue =
        this.getOrderCustomText(
          order,
          column
        )

      if (value === oldValue) {
        return
      }

      try {
        const res =
          await axios.put(
            `/api/orders/${order.id}/custom-values/${column.id}`,
            {
              value
            },
            {
              headers:
                this.headers()
            }
          )

        const saved =
          res.data?.value || null

        const values =
          Array.isArray(order.custom_values)
            ? [...order.custom_values]
            : []

        const index =
          values.findIndex(
            item =>
              Number(item.column_id) ===
              Number(column.id)
          )

        if (!saved) {
          if (index !== -1) {
            values.splice(index, 1)
          }
        } else if (index === -1) {
          values.push(saved)
        } else {
          values.splice(
            index,
            1,
            saved
          )
        }

        order.custom_values =
          values

        if (
          this.selectedOrder &&
          Number(this.selectedOrder.id) ===
          Number(order.id)
        ) {
          this.selectedOrder.custom_values =
            values
        }
      } catch (error) {
        console.error(
          'saveOrderCustomText error:',
          error
        )

        alert(
          error.response?.data?.message ||
          'Custom value could not be saved.'
        )
      }
    },

    getOrderCustomOption(order, column) {
      if (!order || !column) return null
      const values = Array.isArray(order.custom_values) ? order.custom_values : []
      const value = values.find(item => Number(item.column_id) === Number(column.id))
      if (!value) return null

      if (value.option) return value.option
      const optionId = value.option_id || value.value_option_id
      return (column.options || []).find(option => Number(option.id) === Number(optionId)) || null
    },

    customFieldButtonStyle(order, column) {
      const option =
        this.getOrderCustomOption(
          order,
          column
        )

      if (!option) {
        return {}
      }

      const color =
        option.color ||
        '#6161ff'

      return {
        background: color,
        color: this.readableTextColor(color),
        borderColor: color
      }
    },

    isPriorityColumn(column) {
      return String(column?.name || column?.slug || '')
        .trim()
        .toLowerCase() === 'priority'
    },

    canEditCustomField(column) {
      return this.canEditWorkflowFields || (this.isClient && this.isPriorityColumn(column))
    },

    openCustomFieldMenu(order, column, event) {
      const rect = event.currentTarget.getBoundingClientRect()
      const width = Math.max(270, rect.width)
      const left = Math.min(rect.left, window.innerWidth - width - 12)
      const estimatedHeight = Math.min(
        430,
        120 + ((column.options || []).length * 46)
      )

      const top =
        rect.bottom + estimatedHeight > window.innerHeight
          ? Math.max(12, rect.top - estimatedHeight - 6)
          : rect.bottom + 6

      this.customFieldQuickLabel = ''
      this.cancelCustomOptionInlineEdit()

      this.customFieldMenu = {
        order,
        column,
        top,
        left,
        width
      }
    },

    closeCustomFieldMenu() {
      this.customFieldQuickLabel = ''
      this.cancelCustomOptionInlineEdit()

      this.customFieldMenu = {
        order: null,
        column: null,
        top: 0,
        left: 0,
        width: 260
      }
    },

    async saveOrderCustomValue(order, column, option) {
      if (!this.canEditCustomField(column) || !order || !column) return

      try {
        const res = await axios.put(
          `/api/orders/${order.id}/custom-values/${column.id}`,
          { option_id: option?.id || null },
          { headers: this.headers() }
        )

        const saved = res.data?.value || null
        const values = Array.isArray(order.custom_values) ? [...order.custom_values] : []
        const index = values.findIndex(item => Number(item.column_id) === Number(column.id))

        if (!saved) {
          if (index !== -1) values.splice(index, 1)
        } else if (index === -1) {
          values.push(saved)
        } else {
          values.splice(index, 1, saved)
        }

        order.custom_values = values
        if (this.selectedOrder && Number(this.selectedOrder.id) === Number(order.id)) {
          this.selectedOrder.custom_values = values
        }
        this.closeCustomFieldMenu()
      } catch (error) {
        console.error('saveOrderCustomValue error:', error)
        alert(error.response?.data?.message || 'Custom value could not be saved.')
      }
    },

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

    loggedInOrderOwner(order) {
      const owners = Array.isArray(order?.owners)
        ? order.owners
        : []

      const currentId = Number(this.currentUser?.id)

      const assignedCurrentUser = owners.find(
        owner => Number(owner.id) === currentId
      )

      if (assignedCurrentUser) {
        return assignedCurrentUser
      }

      if (this.currentUser?.id) {
        return {
          id: this.currentUser.id,
          name: this.currentUser.name || 'User',
          role: this.currentUser.role,
          profile_photo_url:
            this.currentUser.profile_photo_url || null
        }
      }

      return null
    },

    otherOrderOwners(order) {
      const owners = Array.isArray(order?.owners)
        ? order.owners
        : []

      const currentId = Number(this.currentUser?.id)

      return owners.filter(
        owner => Number(owner.id) !== currentId
      )
    },

    otherOrderOwnersCount(order) {
      return this.otherOrderOwners(order).length
    },

    otherOrderOwnersNames(order) {
      return this.otherOrderOwners(order)
        .map(owner => owner.name)
        .filter(Boolean)
        .join(', ')
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

      const selected = this.orders.filter(order =>
        this.selectedOrders.map(Number).includes(Number(order.id))
      )
      const commonIds = selected.length
        ? (selected[0].clients || []).map(client => Number(client.id)).filter(id =>
            selected.every(order => (order.clients || []).some(client => Number(client.id) === id))
          )
        : []
      this.bulkSelectedClients = this.availableClients.filter(client => commonIds.includes(Number(client.id)))
      this.bulkClientsModal = true
    },

    removeBulkClient(clientId) {
      this.bulkSelectedClients = this.bulkSelectedClients.filter(
        client => Number(client.id) !== Number(clientId)
      )
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

        // Mark ONLY this exact order as started on this browser/user.
        this.markOrderWorkStarted(order.id)

        order.work_started = true
        order.working_by =
          response.data?.working_by ||
          response.data?.user ||
          this.currentUser

        // Any order that starts work belongs in In Production / Working Now.
        if (!this.isInProductionOrder(order)) {
          const productionStatus = this.statusOptions.find(
            item => String(item.label || '').trim().toLowerCase() === 'in production'
          )

          await axios.put(
            `/api/orders/${order.id}`,
            {
              status: 'In Production',
              status_color: productionStatus?.color || '#6161ff'
            },
            { headers: this.headers() }
          )

          order.status = 'In Production'
          order.statusColor = productionStatus?.color || '#6161ff'
          order.group = 'in_production'
        }

        await this.fetchOrders()
      } catch (error) {
        console.error('Start working error:', error)

        const message = String(
          error.response?.data?.message ||
          ''
        )

        /*
         * Another user already started it:
         * no browser alert. Refresh the board and show that user's avatar.
         */
        if (
          error.response?.status === 409 ||
          message.toLowerCase().includes('already working') ||
          message.toLowerCase().includes('already claimed')
        ) {
          await this.fetchOrders()
          return
        }

        alert(
          message ||
          'Working status could not be started.'
        )
      }
    },

    async stopWorking(order) {
      if (!order?.id) return

      /*
       * STRICT:
       * Sirf exact super_admin role Stop Work kar sakta hai.
       * admin/member/can_create_orders access is permission mein count nahi hoga.
       */
      if (
        String(this.currentUser?.role || '')
          .trim()
          .toLowerCase() !== 'super_admin'
      ) {
        alert('Only Super Admin can stop this work.')
        return
      }

      const workerName =
        this.workingDesigner(order)?.name || 'this user'

      const confirmed = window.confirm(
        `Stop work on "${order.name}" for ${workerName}?`
      )

      if (!confirmed) return

      try {
        await axios.post(
          `/api/orders/${order.id}/release`,
          {},
          {
            headers: this.headers()
          }
        )

        this.clearOrderWorkStarted(order.id)
        order.work_started = false
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

    isInProductionOrder(order) {
      const status = String(order?.status || '')
        .trim()
        .toLowerCase()
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ')

      return status === 'in production'
    },

    getStartedWorkOrderIds() {
      try {
        const raw = localStorage.getItem('factory_started_work_order_ids')
        const ids = raw ? JSON.parse(raw) : []
        return Array.isArray(ids) ? ids.map(Number) : []
      } catch (error) {
        return []
      }
    },

    markOrderWorkStarted(orderId) {
      const id = Number(orderId)
      if (!id) return

      const ids = this.getStartedWorkOrderIds()
      if (!ids.includes(id)) ids.push(id)

      localStorage.setItem(
        'factory_started_work_order_ids',
        JSON.stringify(ids)
      )
    },

    clearOrderWorkStarted(orderId) {
      const id = Number(orderId)
      const ids = this.getStartedWorkOrderIds().filter(item => item !== id)

      localStorage.setItem(
        'factory_started_work_order_ids',
        JSON.stringify(ids)
      )
    },

    getFinishedWorkMap() {
      try {
        const value = JSON.parse(
          localStorage.getItem('factory_finished_work_by_order') || '{}'
        )
        return value && typeof value === 'object' ? value : {}
      } catch (error) {
        return {}
      }
    },

    markOrderFinished(orderId, user) {
      if (!orderId || !user) return

      const map = this.getFinishedWorkMap()
      map[Number(orderId)] = {
        id: user.id || null,
        name: user.name || 'User',
        profile_photo_url: user.profile_photo_url || null,
        finished_at: new Date().toISOString()
      }

      localStorage.setItem(
        'factory_finished_work_by_order',
        JSON.stringify(map)
      )
    },

    isShippedOrder(order) {
      return String(order?.status || '').trim().toLowerCase() === 'shipped'
    },

    finishedDesigner(order) {
      if (!order?.id) return null

      return (
        order.finished_by ||
        order.shipped_by ||
        order.completed_by ||
        this.getFinishedWorkMap()[Number(order.id)] ||
        null
      )
    },

    async finishWorkForShippedOrder(order, worker = null) {
      if (!order?.id) return

      const finisher = worker || this.workingDesigner(order)
      if (!finisher) return

      try {
        const response = await axios.post(
          `/api/orders/${order.id}/release`,
          {
            finish_reason: 'shipped',
            finished_status: 'Shipped'
          },
          { headers: this.headers() }
        )

        const savedFinisher =
          response.data?.finished_by ||
          response.data?.working_by ||
          response.data?.user ||
          finisher

        this.markOrderFinished(order.id, savedFinisher)
        this.clearOrderWorkStarted(order.id)
        order.finished_by = savedFinisher
        order.work_started = false
        order.is_working = false
        order.work_session_active = false
        order.working_started_at = null
        order.working_by = null
      } catch (error) {
        console.error('Finish shipped order work error:', error)
        alert(
          error.response?.data?.message ||
          'Order shipped ho gaya, lekin working session finish nahi hua.'
        )
      }
    },

    hasOrderWorkStarted(order) {
      if (!order?.id) return false

      // Explicit backend flags are supported when available.
      const backendStarted =
        order.work_started === true ||
        order.is_working === true ||
        order.work_session_active === true ||
        Boolean(order.working_started_at)

      if (backendStarted) return true

      // IMPORTANT: working_by alone is NOT enough, because some API responses
      // may populate it when status changes to In Production.
      return this.getStartedWorkOrderIds().includes(Number(order.id))
    },

    workingDesigner(order) {
      if (!order) return null

      /*
       * IMPORTANT:
       * Agar backend working_by bhej raha hai to iska matlab order
       * kisi user ne already claim/start kiya hua hai.
       *
       * Is se doosre user ko Play button dobara nazar nahi aayega;
       * uski jagah actual worker ki profile image nazar aayegi.
       */
      if (
        order?.working_by &&
        (
          order.working_by.id ||
          order.working_by.name ||
          order.working_by.profile_photo_url
        )
      ) {
        return order.working_by
      }

      if (!this.hasOrderWorkStarted(order)) return null

      return this.currentUser || null
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

        this.markOrderWorkStarted(order.id)
        order.work_started = true
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

        this.clearOrderWorkStarted(order.id)
        order.work_started = false
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
      if (!this.canManageWorkflow || !group?.key || !color) return

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
      if (!this.canManageWorkflow) return
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

      this.saveAllStatusOptions()
    },

    deleteWorkflowGroup(group) {
      if (!this.canManageWorkflow) return
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

      this.openPreviewFile(normalized)
    },

    async openRowViewAll(order) {
      await this.selectOrder(order)
      this.viewAllCard = {
        title: `${order.name || 'Order'} - All Files`,
        type: 'all_order_files',
        files: this.rowFiles(this.selectedOrder || order)
      }
    },

    async removeRowFile(order, file) {
      if (!this.canDeleteFile(file) || !file?.id) return
      if (!confirm(`Delete ${file.name || 'this file'}?`)) return

      try {
        await axios.delete(`/api/order-files/${file.id}`, { headers: this.headers() })

        ;(order.cards || []).forEach(card => {
          card.files = (card.files || []).filter(item => Number(item.id) !== Number(file.id))
        })

        if (this.selectedOrder && Number(this.selectedOrder.id) === Number(order.id)) {
          ;(this.selectedOrder.cards || []).forEach(card => {
            card.files = (card.files || []).filter(item => Number(item.id) !== Number(file.id))
          })
        }
      } catch (e) {
        console.error('removeRowFile error:', e)
        alert(e.response?.data?.message || 'File delete nahi hui')
      }
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

      const status = this.workflowStatusOptions.find(
        item =>
          String(item.label || '').trim().toLowerCase() ===
          String(label || '').trim().toLowerCase()
      )

      if (!status) return

      const activeWorker = this.workingDesigner(order)
      const isShipped =
        String(status.label).trim().toLowerCase() === 'shipped'
      const shippedBy = isShipped ? this.currentUser : null

      try {
        await axios.put(
          `/api/orders/${order.id}`,
          {
            status: status.label,
            status_color: status.color,
            ...(isShipped && shippedBy
              ? {
                  shipped_by_user_id: shippedBy.id,
                  shipped_at: new Date().toISOString()
                }
              : {})
          },
          {
            headers: this.headers()
          }
        )

        order.status = status.label
        order.statusColor = status.color
        const targetGroup = status.group || this.statusToGroup(status.label)
        order.group = targetGroup

        if (isShipped && shippedBy) {
          this.markOrderFinished(order.id, shippedBy)
          order.finished_by = shippedBy

          if (activeWorker) {
            await this.finishWorkForShippedOrder(order, shippedBy)
          }
        }

        if (
          this.selectedOrder &&
          Number(this.selectedOrder.id) === Number(order.id)
        ) {
          this.selectedOrder.status = status.label
          this.selectedOrder.statusColor = status.color
          this.selectedOrder.group = order.group
        }

        // Force the active tab list/counts to react immediately.
        this.orders = [...this.orders]
        await this.fetchOrders({ silent: true, loadFiles: false })
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
  border: 1px solid ##aeb0b3 !important;
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



/* =========================================================
   FINAL OWNER COLUMN
   Logged-in user only + remaining count + add button
========================================================= */

.factory-board-page .board-col-owner {
  min-width: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: flex-start !important;
  padding: 0 8px !important;
  overflow: hidden !important;
}

.factory-board-page .owner-compact-stack {
  width: 100% !important;
  min-width: 0 !important;
  height: 100% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: flex-start !important;
  gap: 0 !important;
  padding: 0 !important;
  margin: 0 !important;
  overflow: visible !important;
}

.factory-board-page .owner-compact-stack .owner-current-avatar {
  position: relative !important;
  z-index: 3 !important;
  width: 30px !important;
  height: 30px !important;
  min-width: 30px !important;
  max-width: 30px !important;
  padding: 0 !important;
  margin: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border: 2px solid #ffffff !important;
  border-radius: 50% !important;
  background: #ffffff !important;
  overflow: hidden !important;
  cursor: pointer !important;
}

.factory-board-page .owner-compact-stack .owner-current-avatar img {
  width: 100% !important;
  height: 100% !important;
  display: block !important;
  object-fit: cover !important;
  border-radius: 50% !important;
}

.factory-board-page .owner-compact-stack .owner-current-avatar > span {
  width: 100% !important;
  height: 100% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  background: #0f172a !important;
  color: #ffffff !important;
  font-size: 10px !important;
  font-weight: 800 !important;
}

.factory-board-page .owner-compact-stack .board-avatar-more {
  position: relative !important;
  z-index: 4 !important;
  width: 24px !important;
  height: 24px !important;
  min-width: 24px !important;
  max-width: 24px !important;
  margin-left: -7px !important;
  padding: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border: 2px solid #ffffff !important;
  border-radius: 50% !important;
  background: #273142 !important;
  color: #ffffff !important;
  font-size: 8px !important;
  font-weight: 800 !important;
  line-height: 1 !important;
  cursor: pointer !important;
}

.factory-board-page .owner-compact-stack .board-avatar-add {
  width: 30px !important;
  height: 30px !important;
  min-width: 30px !important;
  max-width: 30px !important;
  margin-left: 7px !important;
  padding: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border: 1px dashed #94a3b8 !important;
  border-radius: 50% !important;
  background: transparent !important;
  color: #64748b !important;
  font-size: 10px !important;
  cursor: pointer !important;
}

.factory-board-page .owner-compact-stack .board-avatar-add:hover {
  background: #f8fafc !important;
  border-color: #64748b !important;
  color: #111827 !important;
}

.factory-board-page.theme-dark .owner-compact-stack .owner-current-avatar {
  border-color: #111827 !important;
}

.factory-board-page.theme-dark .owner-compact-stack .board-avatar-more {
  border-color: #111827 !important;
}

.factory-board-page.theme-dark .owner-compact-stack .board-avatar-add:hover {
  background: #1f2937 !important;
  color: #ffffff !important;
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
    boardSectionOrder(groupKey, part = 0) {
      if (groupKey === 'all') {
        return Number(part)
      }

      const index = this.boardGroups.findIndex(
        group => group.key === groupKey
      )

      const safeIndex = index >= 0 ? index : 0

      return ((safeIndex + 1) * 10) + Number(part)
    },


    countForGroup(groupKey) {
      const matchingOrders = this.accessibleOrders.filter(order =>
        this.orderMatchesCurrentSearch(order)
      )

      if (groupKey === 'all') {
        return matchingOrders.length
      }

      if (groupKey === 'delivered') {
        return matchingOrders.filter(
          order =>
            String(order.status || '').toLowerCase() ===
            'delivered'
        ).length
      }

      return matchingOrders.filter(
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
      if (!this.canManageWorkflow) return
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

    toggleNotificationMenu() {
      this.showChatNotificationMenu = !this.showChatNotificationMenu

      if (!this.showChatNotificationMenu) return

      if (
        this.totalUnreadChatCount === 0 &&
        this.unreadOrderNotificationCount > 0
      ) {
        this.notificationTab = 'orders'
      } else if (
        this.unreadOrderNotificationCount === 0 &&
        this.totalUnreadChatCount > 0
      ) {
        this.notificationTab = 'chats'
      }
    },

    clearDetailOrderSearch() {
      this.detailSearchOrder = ''
      this.detailSearchOpen = false
    },

    async openFirstDetailSearchResult() {
      const first = this.detailSearchResults[0]
      if (!first) return
      await this.openDetailSearchOrder(first)
    },

    async openDetailSearchOrder(order) {
      if (!order) return

      this.detailSearchOpen = false
      this.detailSearchOrder = ''
      this.activeGroup = order.group || this.activeGroup
      this.activeSectionCollapsed = false

      // Keep the selected order in the URL without leaving this page.
      if (Number(this.$route.query.order_id) !== Number(order.id)) {
        this.$router.replace({
          path: this.$route.path,
          query: {
            ...this.$route.query,
            order_id: order.id,
            open_chat: undefined
          }
        }).catch(() => {})
      }

      await this.openBoardOrder(order)
    },

    async openBoardOrder(order) {
      if (!order) return

      this.lastOpenedOrderId = Number(order.id)
      localStorage.setItem('factory_last_opened_order_id', String(order.id))

      /*
       * Keep the currently opened order in the URL.
       * If this page re-renders/remounts because of notifications,
       * route updates, HMR, etc., the SAME order is restored instead
       * of falling back to the first order.
       */
      if (
        Number(this.$route.query.order_id || 0) !==
        Number(order.id)
      ) {
        this.$router.replace({
          path: this.$route.path,
          query: {
            ...this.$route.query,
            order_id: order.id,
            open_chat: undefined
          }
        }).catch(() => {})
      }

      // Show detail immediately, then fetch heavier data in background.
      this.selectedOrder = order
      this.detailOpen = true
      this.closeAllMenus()

      await this.$nextTick()

      this.selectOrder(order).catch(error => {
        console.error('openBoardOrder load error:', error)
      })
    },

    async openOrderFromNotification(order) {
      this.showChatNotificationMenu = false
      this.activeGroup = order.group || this.activeGroup
      this.activeSectionCollapsed = false

      await this.openBoardOrder(order)
    },

    notificationTime(value) {
      if (!value) return ''

      const date = new Date(value)
      if (Number.isNaN(date.getTime())) return ''

      const diff = Date.now() - date.getTime()
      const minute = 60 * 1000
      const hour = 60 * minute
      const day = 24 * hour

      if (diff < minute) return 'Just now'
      if (diff < hour) return `${Math.floor(diff / minute)}m ago`
      if (diff < day) return `${Math.floor(diff / hour)}h ago`
      if (diff < 7 * day) return `${Math.floor(diff / day)}d ago`

      return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
      })
    },

    closeBoardDetail() {
      this.detailOpen = false
      this.showChat = false
      this.closeAllMenus()

      /*
       * User intentionally closed the form, so remove persisted order ID.
       */
      if (this.$route.query.order_id) {
        const query = {
          ...this.$route.query
        }

        delete query.order_id
        delete query.open_chat

        this.$router.replace({
          path: this.$route.path,
          query
        }).catch(() => {})
      }
    },

    async openBoardChat(order) {
      if (!order) return

      this.selectedOrder = order
      this.detailOpen = true
      this.showChat = true
      this.closeAllMenus()

      await this.$nextTick()

      this.selectOrder(order)
        .then(async () => {
          if (this.markChatRead) {
            await this.markChatRead()
          }
        })
        .catch(error => {
          console.error('openBoardChat load error:', error)
        })
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
      if (!this.canEditWorkflowFields || !order?.id) return

      const id = Number(order.id)

      if (this.rowStatusMenuId === id) {
        this.rowStatusMenuId = null
        this.rowStatusMenuOrder = null
        return
      }

      const button = event?.currentTarget
      const rect = button?.getBoundingClientRect?.()

      if (!rect) return

      const width = 320
      const gap = 8
      const padding = 12

      let left = rect.left
      let top = rect.bottom + gap

      const estimatedHeight = 470

      if (left + width > window.innerWidth - padding) {
        left = window.innerWidth - width - padding
      }

      if (left < padding) {
        left = padding
      }

      if (top + estimatedHeight > window.innerHeight - padding) {
        top = Math.max(
          padding,
          rect.top - estimatedHeight - gap
        )
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
      if (!this.canEditWorkflowFields || !order || !status?.label) return

      this.rowStatusMenuId = null
      this.rowStatusMenuOrder = null

      await this.inlineChangeStatus(
        order,
        status.label
      )
    },

    startRowStatusEdit(status) {
      if (!this.canManageWorkflow || !status?.label) return

      this.rowStatusEditingLabel = status.label
      this.rowStatusEditName = status.label
      this.rowStatusEditColor = status.color || '#6161ff'
    },

    cancelRowStatusEdit() {
      this.rowStatusEditingLabel = null
      this.rowStatusEditName = ''
      this.rowStatusEditColor = '#6161ff'
    },

    async saveRowStatusEdit(status) {
      if (!this.canManageWorkflow || !status?.label) return

      const oldLabel = String(status.label || '').trim()
      const newLabel = String(this.rowStatusEditName || '').trim()
      const newColor = this.rowStatusEditColor || status.color || '#6161ff'

      if (!oldLabel || !newLabel) return

      const duplicate = this.statusOptions.some(
        item =>
          item !== status &&
          String(item.label || '').trim().toLowerCase() ===
          newLabel.toLowerCase()
      )

      if (duplicate) {
        alert('A status with this name already exists.')
        return
      }

      const affectedOrders = this.orders.filter(
        order =>
          String(order.status || '').trim().toLowerCase() ===
          oldLabel.toLowerCase()
      )

      status.label = newLabel
      status.color = newColor

      // Save ALL statuses, including the built-in/default ones.
      this.saveAllStatusOptions()

      // Keep already-loaded orders in sync and persist them.
      affectedOrders.forEach(order => {
        order.status = newLabel
        order.statusColor = newColor
      })

      await Promise.allSettled(
        affectedOrders.map(order =>
          axios.put(
            `/api/orders/${order.id}`,
            {
              status: newLabel,
              status_color: newColor
            },
            { headers: this.headers() }
          )
        )
      )

      if (
        this.rowStatusMenuOrder &&
        String(this.rowStatusMenuOrder.status || '').trim().toLowerCase() ===
          oldLabel.toLowerCase()
      ) {
        this.rowStatusMenuOrder.status = newLabel
        this.rowStatusMenuOrder.statusColor = newColor
      }

      if (
        this.selectedOrder &&
        String(this.selectedOrder.status || '').trim().toLowerCase() ===
          oldLabel.toLowerCase()
      ) {
        this.selectedOrder.status = newLabel
        this.selectedOrder.statusColor = newColor
      }

      this.cancelRowStatusEdit()
    },

    async deleteRowCustomStatus(status) {
      if (!this.canManageWorkflow || !status?.label) return

      const label = String(status.label || '').trim()

      if (
        !confirm(
          `Delete "${label}" status?\n\nOrders already using it will keep their current status until you change them.`
        )
      ) {
        return
      }

      this.statusOptions = this.statusOptions.filter(
        item => item !== status
      )

      // Persist deletion for built-in and custom statuses.
      this.saveAllStatusOptions()

      if (this.rowStatusEditingLabel === label) {
        this.cancelRowStatusEdit()
      }
    },

    async addCustomRowStatus(order) {
      if (!this.canManageWorkflow || !order) return

      const label = String(this.customStatusLabel || '').trim()
      if (!label) return

      const existing = this.statusOptions.find(
        item =>
          String(item.label || '').toLowerCase() ===
          label.toLowerCase()
      )

      const customGroupKey = label
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_|_$/g, '')

      if (!existing && !this.boardGroups.some(group => group.key === customGroupKey)) {
        this.customBoardGroups.push({
          key: customGroupKey,
          label: label.toUpperCase(),
          color: this.customStatusColor || '#6161ff',
          icon: 'fa-solid fa-house',
          custom: true
        })
        this.persistWorkflowGroups()
      }

      const status = existing || {
        label,
        color: this.customStatusColor || '#6161ff',
        group: customGroupKey,
        groupLabel: label,
        custom: true
      }

      if (!existing) {
        this.saveCustomStatusOption(status)
      }

      this.customStatusLabel = ''
      this.customStatusColor = '#6161ff'

      await this.inlineChangeStatus(order, status.label)

      this.rowStatusMenuId = null
      this.rowStatusMenuOrder = null
    },

    async changeRowCustomStatusColor(status, color) {
      if (!status || !color) return

      status.color = color
      status.custom = true
      this.saveCustomStatusOption(status)

      const affectedOrders = this.orders.filter(
        order =>
          String(order.status || '').toLowerCase() ===
          String(status.label || '').toLowerCase()
      )

      for (const order of affectedOrders) {
        order.statusColor = color
      }

      if (
        this.rowStatusMenuOrder &&
        String(this.rowStatusMenuOrder.status || '').toLowerCase() ===
        String(status.label || '').toLowerCase()
      ) {
        try {
          await axios.put(
            `/api/orders/${this.rowStatusMenuOrder.id}`,
            {
              status: status.label,
              status_color: color
            },
            { headers: this.headers() }
          )

          this.rowStatusMenuOrder.statusColor = color
        } catch (error) {
          console.error('changeRowCustomStatusColor error:', error)
        }
      }
    },

    async openStatusForRow(order) {
      await this.selectOrder(order)
      this.detailOpen = true
      this.showStatusMenu = true
    },

    orderNotesText(order) {
      const noteCard = (order?.cards || []).find(
        card =>
          card?.type === 'notes' ||
          card?.title === 'Notes'
      )

      return String(
        noteCard?.noteText ??
        order?.notes ??
        ''
      ).trim()
    },

    async saveNotesInline(order, event, fromBlur = false) {
      const input = event?.target
      const draftKey = this.inlineTextDraftKey(order, 'notes')

      if (
        !input ||
        !order?.id ||
        !this.canEditNotesForOrder(order)
      ) {
        this.endTextEditing()
        return
      }

      if (this.inlineTextSaving[draftKey]) return

      const value = String(this.inlineTextDraftValue(order, 'notes') || '').trim()
      const oldValue = this.orderNotesText(order)

      /*
       * Enter ke baad blur duplicate request na bheje.
       */
      if (fromBlur && value === oldValue) {
        this.clearInlineTextDraft(draftKey)
        this.endTextEditing()
        return
      }

      this.inlineTextSaving = { ...this.inlineTextSaving, [draftKey]: true }

      try {
        await axios.put(
          `/api/orders/${order.id}`,
          {
            notes: value === '' ? null : value
          },
          {
            headers: this.headers()
          }
        )

        order.notes = value

        let noteCard = (order.cards || []).find(
          card =>
            card?.type === 'notes' ||
            card?.title === 'Notes'
        )

        if (noteCard) {
          noteCard.noteText = value
        }

        /*
         * Agar same order detail panel mein open ho,
         * Notes card ko bhi instantly sync rakho.
         */
        if (
          this.selectedOrder &&
          Number(this.selectedOrder.id) === Number(order.id)
        ) {
          this.selectedOrder.notes = value

          const selectedNoteCard =
            (this.selectedOrder.cards || []).find(
              card =>
                card?.type === 'notes' ||
                card?.title === 'Notes'
            )

          if (selectedNoteCard) {
            selectedNoteCard.noteText = value
            selectedNoteCard.saved = true

            setTimeout(() => {
              selectedNoteCard.saved = false
            }, 1200)
          }
        }

        input.value = value
        this.clearInlineTextDraft(draftKey)

        if (this.isClient) {
          this.clientNoteSavedAtByOrder = {
            ...this.clientNoteSavedAtByOrder,
            [Number(order.id)]: Date.now()
          }
          localStorage.setItem('client_note_saved_at_by_order', JSON.stringify(this.clientNoteSavedAtByOrder))
        }

        if (!fromBlur) {
          input.blur()
        }
      } catch (error) {
        console.error(
          'Inline notes save error:',
          error
        )

        alert(
          error.response?.data?.message ||
          'Notes save nahi huay.'
        )
      } finally {
        const saving = { ...this.inlineTextSaving }
        delete saving[draftKey]
        this.inlineTextSaving = saving
        this.endTextEditing()
      }
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
      this.inlinePriorityOptionId = ''

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
      this.inlinePriorityOptionId = ''
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

      const activeStatus = this.isClient
        ? this.statusOptions.find(item => item.label === 'Pending')
        : (
          this.statusOptions.find(item => item.group === this.activeGroup) ||
          this.statusOptions.find(item => item.label === 'Pending')
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
            payment: 'Not Yet',
            trk: 'N/A',
            notes: '',
            shipping_address: '',
            member_ids: this.boardSettings.auto_assign_all_owners
              ? this.availableMembers.map(member => member.id)
              : [],
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
          this.isClient &&
          createdId &&
          this.priorityColumn?.id &&
          this.inlinePriorityOptionId
        ) {
          await axios.put(
            `/api/orders/${createdId}/custom-values/${this.priorityColumn.id}`,
            { option_id: Number(this.inlinePriorityOptionId) },
            { headers: this.headers() }
          )
        }

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
  if (!this.selectedOrder) {
    this.endTextEditing()
    return
  }

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
  } finally {
    this.endTextEditing()
  }
},


shortShippingAddress(address) {
  if (!address) return 'N/A'

  const words = String(address).trim().split(/\s+/)

  if (words.length <= 4) return address

  return words.slice(0, 4).join(' ') + '...'
},


    editCustomStatus(status) {
  if (!this.canManageWorkflow) return
  if (!this.isSuperAdmin) return
const newName = prompt('Enter new status name', status.label)
  if (!newName || !newName.trim()) return

  status.label = newName.trim()

  localStorage.setItem(
    'custom_order_statuses',
    JSON.stringify(this.statusOptions.filter(s => s.custom))
  )
  this.saveAllStatusOptions()
},

deleteCustomStatus(status) {
  if (!this.canManageWorkflow) return
  if (!this.isSuperAdmin) return
  if (!confirm('Are you sure you want to delete this custom status?')) return

  this.statusOptions = this.statusOptions.filter(s => s.label !== status.label)

  localStorage.setItem(
    'custom_order_statuses',
    JSON.stringify(this.statusOptions.filter(s => s.custom))
  )
  this.saveAllStatusOptions()
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
  const selected = this.orders.filter(order =>
    this.selectedOrders.map(Number).includes(Number(order.id))
  )
  const commonIds = selected.length
    ? (selected[0].owners || []).map(member => Number(member.id)).filter(id =>
        selected.every(order => (order.owners || []).some(member => Number(member.id) === id))
      )
    : []
  this.bulkSelectedMembers = this.availableMembers.filter(member => commonIds.includes(Number(member.id)))
  this.bulkMembersModal = true
},

removeBulkMember(memberId) {
  this.bulkSelectedMembers = this.bulkSelectedMembers.filter(
    member => Number(member.id) !== Number(memberId)
  )
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

    orderMatchesCurrentSearch(order) {
      const orderText = String(this.searchOrder || '').trim().toLowerCase()

      const orderMatch = !orderText || [
        order.name,
        order.po,
        order.status,
        order.shippingAddress,
        order.trk
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
        .includes(orderText)

      const selectedClientMatch =
        !this.selectedClient ||
        (order.clients || []).some(
          client => Number(client.id) === Number(this.selectedClient)
        )

      return orderMatch && selectedClientMatch
    },

    // Mobile: select order and close left panel
    selectOrderAndClose(order) {
      this.openBoardOrder(order)
      this.mobileLeftOpen = false
    },

    canManageStatusOption(status) {
      return this.canManageWorkflow && Boolean(status?.label)
    },

    loadSavedStatusOptions() {
      try {
        /*
         * New storage contains the complete status list:
         * built-in/default + custom statuses.
         */
        const savedAll = JSON.parse(
          localStorage.getItem('factory_order_status_options') || '[]'
        )

        if (Array.isArray(savedAll) && savedAll.length) {
          this.statusOptions = savedAll
            .filter(item => item?.label)
            .map(item => this.normalizeStatusDefinition(item))

          return
        }

        /*
         * Backward compatibility:
         * old custom statuses are merged into the current default list.
         */
        const legacyCustom = JSON.parse(
          localStorage.getItem('custom_order_statuses') || '[]'
        )

        if (Array.isArray(legacyCustom)) {
          legacyCustom.forEach(item => {
            if (
              item?.label &&
              !this.statusOptions.some(
                status =>
                  String(status.label || '').trim().toLowerCase() ===
                  String(item.label || '').trim().toLowerCase()
              )
            ) {
              this.statusOptions.push({
                label: item.label,
                color: item.color || '#6161ff',
                group: item.group || 'in_production',
                groupLabel: item.groupLabel || 'In Production',
                custom: true
              })
            }
          })
        }

        this.saveAllStatusOptions()
      } catch (error) {
        console.error('loadSavedStatusOptions error:', error)
      }
    },

    async saveAllStatusOptions() {
      const clean = (this.statusOptions || [])
        .filter(item => item?.label)
        .map(item => ({
          label: String(item.label || '').trim(),
          color: item.color || '#6161ff',
          group: item.group || 'in_production',
          groupLabel: item.groupLabel || 'In Production',
          custom: item.custom === true
        }))

      localStorage.setItem(
        'factory_order_status_options',
        JSON.stringify(clean)
      )

      /*
       * Keep old custom storage too so any older code still works.
       */
      localStorage.setItem(
        'custom_order_statuses',
        JSON.stringify(
          clean
            .filter(item => item.custom)
            .map(item => ({
              ...item,
              custom: true
            }))
        )
      )

      if (this.isSuperAdmin) {
        try {
          await axios.put('/api/factory-board/settings', {
            auto_assign_all_owners: Boolean(this.boardSettings.auto_assign_all_owners),
            hidden_columns: this.boardSettings.hidden_columns || [],
            column_order: this.boardSettings.column_order || [],
            status_options: clean,
            custom_groups: this.customBoardGroups || [],
            default_group_overrides: this.defaultBoardGroupOverrides || {}
          }, { headers: this.headers() })
        } catch (error) {
          console.error('Shared status settings could not be saved:', error)
        }
      }
    },

    saveCustomStatusOption(status) {
      if (!this.isSuperAdmin || !status?.label) return

      const existsIndex = this.statusOptions.findIndex(
        item =>
          String(item.label || '').trim().toLowerCase() ===
          String(status.label || '').trim().toLowerCase()
      )

      const normalized = {
        label: String(status.label || '').trim(),
        color: status.color || '#6161ff',
        group: status.group || 'in_production',
        groupLabel: status.groupLabel || 'In Production',
        custom: true
      }

      if (existsIndex === -1) {
        this.statusOptions.push(normalized)
      } else {
        this.statusOptions.splice(
          existsIndex,
          1,
          {
            ...this.statusOptions[existsIndex],
            ...normalized
          }
        )
      }

      this.saveAllStatusOptions()
    },

    async changeStatusOptionColor(status, color) {
      if (!this.canManageWorkflow || !status || !color) return

      status.color = color

      /*
       * Save color even for built-in statuses such as:
       * Pending, Designing, In Production, Completed, Shipped, Delivered.
       */
      this.saveAllStatusOptions()

      const affectedOrders = this.orders.filter(
        order =>
          String(order.status || '').trim().toLowerCase() ===
          String(status.label || '').trim().toLowerCase()
      )

      affectedOrders.forEach(order => {
        order.statusColor = color
      })

      if (
        this.rowStatusMenuOrder &&
        String(this.rowStatusMenuOrder.status || '').trim().toLowerCase() ===
          String(status.label || '').trim().toLowerCase()
      ) {
        this.rowStatusMenuOrder.statusColor = color
      }

      if (
        this.selectedOrder &&
        String(this.selectedOrder.status || '').trim().toLowerCase() ===
          String(status.label || '').trim().toLowerCase()
      ) {
        this.selectedOrder.statusColor = color
      }

      /*
       * Persist the new color to orders already using this status.
       */
      await Promise.allSettled(
        affectedOrders.map(order =>
          axios.put(
            `/api/orders/${order.id}`,
            {
              status: order.status,
              status_color: color
            },
            {
              headers: this.headers()
            }
          )
        )
      )
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

  this.previewFile = {
    ...file,
    isImage:
      file.isImage ??
      /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(file.name || '')
  }
},

    fileExtension(file) {
      return String(file?.name || '')
        .split('.')
        .pop()
        .toLowerCase()
    },

    absoluteFileUrl(file) {
      if (!file?.url) return ''
      return file.url.startsWith('http')
        ? file.url
        : window.location.origin + file.url
    },

    canEmbedPreview(file) {
      const ext = this.fileExtension(file)
      return [
        'pdf', 'txt', 'csv',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'
      ].includes(ext)
    },

    previewEmbedUrl(file) {
      const ext = this.fileExtension(file)
      const fullUrl = this.absoluteFileUrl(file)

      if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(ext)) {
        return `https://docs.google.com/viewer?url=${encodeURIComponent(fullUrl)}&embedded=true`
      }

      return file?.url || ''
    },

    openFileNewTab(file) {
      if (!file?.url) return
      window.open(this.absoluteFileUrl(file), '_blank', 'noopener,noreferrer')
    },

    async downloadSingleFile(file) {
      if (!file?.url) return

      try {
        const response = await fetch(file.url)
        if (!response.ok) throw new Error('Download request failed')

        const blob = await response.blob()
        const blobUrl = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = blobUrl
        a.download = this.safeFileName(file.name || 'file')
        document.body.appendChild(a)
        a.click()
        a.remove()
        URL.revokeObjectURL(blobUrl)
      } catch (e) {
        console.error('downloadSingleFile error:', e)

        const a = document.createElement('a')
        a.href = file.url
        a.download = this.safeFileName(file.name || 'file')
        a.target = '_blank'
        a.rel = 'noopener'
        document.body.appendChild(a)
        a.click()
        a.remove()
      }
    },

    formatFileSize(bytes) {
      const size = Number(bytes || 0)
      if (!size) return ''
      if (size < 1024) return `${size} B`
      if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`
      return `${(size / (1024 * 1024)).toFixed(1)} MB`
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
      // PageHeader profile click does not pass a user; open the normal profile page.
      if (!user?.id) {
        this.$router.push('/profile')
        return
      }
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

    async fetchOrders({ silent = false, loadFiles = true } = {}) {
      if (!silent) this.loadingOrders = true
      try {
        const previousOrders = new Map(
          this.orders.map(order => [Number(order.id), order])
        )

        if (this.selectedOrder?.id) {
          previousOrders.set(Number(this.selectedOrder.id), this.selectedOrder)
        }

        const res = await axios.get('/api/orders', { headers: this.headers() })
        const list = Array.isArray(res.data) ? res.data : (res.data?.data || [])
        this.orders = list.map(rawOrder => {
          const freshOrder = this.formatOrder(rawOrder)

          if (!loadFiles) {
            const previousOrder = previousOrders.get(Number(freshOrder.id))

            if (previousOrder) {
              freshOrder.invoiceFiles = previousOrder.invoiceFiles || []
              freshOrder.cards = (freshOrder.cards || []).map(card => {
                const oldCard = (previousOrder.cards || []).find(
                  item => item.type === card.type
                )

                return card.type === 'notes'
                  ? card
                  : { ...card, files: oldCard?.files || [] }
              })
            }
          }

          return freshOrder
        })

        // Load file thumbnails for every board row as well.
        // This keeps the 3 thumbnail previews visible after a full page refresh.
        if (loadFiles) await this.loadBoardOrderFiles()

        /*
         * Preserve the SAME selected order after any refresh.
         * Never replace it with this.orders[0].
         */
        const selectedId =
          Number(
            this.selectedOrder?.id ||
            this.$route.query.order_id ||
            0
          )

        if (selectedId) {
          const fresh = this.orders.find(
            order => Number(order.id) === selectedId
          )

          if (fresh) {
            this.selectedOrder = fresh
          }
        }
      } catch (e) {
        if (!silent) console.error('fetchOrders error:', e)
      } finally {
        if (!silent) this.loadingOrders = false
      }
    },

    async loadBoardOrderFiles() {
      if (!Array.isArray(this.orders) || !this.orders.length) return

      await Promise.all(
        this.orders.map(async order => {
          try {
            const res = await axios.get(`/api/orders/${order.id}/files`, {
              headers: this.headers()
            })

            const files = this.filesFromResponse(res.data)

            const normalizedFiles = files.map(file => ({
              ...this.normalizeOrderFile(file),
              cardType: this.normalizeCardType(file.card_type || file.cardType)
            }))

            order.invoiceFiles = normalizedFiles.filter(
              file => file.cardType === 'invoice_files'
            )

            ;(order.cards || []).forEach(card => {
              if (card.type === 'notes') return

              if (card.type === 'order_files') {
                card.files = normalizedFiles.filter(file =>
                  file.cardType === 'order_files' ||
                  file.cardType === 'chat_files'
                )
              } else {
                card.files = normalizedFiles.filter(
                  file => file.cardType === card.type
                )
              }
            })
          } catch (error) {
            console.error(`Board files load error for order ${order.id}:`, error)
          }
        })
      )
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
        notesUpdatedAt: order.notes_updated_at || null,
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
        payment: order.payment || 'Not Yet',
        paymentReceived: order.payment_received || 0,
        paymentBalance: order.payment_balance || 0,
        members,
        clients: order.clients || [],
        custom_values: order.custom_values || this.boardCustomValuesByOrder[Number(order.id)] || [],
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
        work_started:
          order.work_started === true ||
          order.is_working === true ||
          order.work_session_active === true ||
          Boolean(order.working_started_at) ||
          this.getStartedWorkOrderIds().includes(Number(order.id)),
        is_working: order.is_working === true,
        work_session_active: order.work_session_active === true,
        working_started_at: order.working_started_at || null,
        finished_by:
          order.finished_by ||
          order.shipped_by ||
          order.completed_by ||
          this.getFinishedWorkMap()[Number(order.id)] ||
          null,
        finished_at: order.finished_at || order.shipped_at || null,

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
      const normalized = String(status || '').trim().toLowerCase()

      if (normalized === 'completed') return 'completed'
      if (normalized === 'shipped') return 'shipped'
      if (normalized === 'delivered') return 'delivered'
      if (
        normalized === 'pending' ||
        normalized === 'designing' ||
        normalized === 'in production'
      ) return 'in_production'

      const found = this.workflowStatusOptions.find(
        item =>
          String(item.label || '').trim().toLowerCase() === normalized
      )

      if (found?.group) {
        return found.group
      }

      return 'in_production'
    },

    normalizeStatusDefinition(status) {
      const label = String(status?.label || '').trim()
      const normalized = label.toLowerCase()
      const canonicalGroups = {
        pending: ['in_production', 'In Production'],
        designing: ['in_production', 'In Production'],
        'in production': ['in_production', 'In Production'],
        completed: ['completed', 'Completed'],
        shipped: ['shipped', 'Shipped'],
        delivered: ['delivered', 'Delivered']
      }
      const canonical = canonicalGroups[normalized]

      return {
        ...status,
        label,
        color: status?.color || '#6161ff',
        group: canonical?.[0] || status?.group || 'in_production',
        groupLabel: canonical?.[1] || status?.groupLabel || 'In Production',
        custom: canonical ? false : status?.custom === true
      }
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
  payment: 'Not Yet',
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
        payment: order.payment || 'Not Yet', trk: order.trk === 'N/A' ? '' : (order.trk || 'N/A')
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
          trk: order.trk || 'N/A', payment: order.payment || 'Not Yet', notes: this.getOrderNote(order)
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
        member_ids: (!this.editingOrderId && this.boardSettings.auto_assign_all_owners)
          ? this.availableMembers.map(m => m.id)
          : this.newOrder.selectedMembers.map(m => m.id),
        client_ids: this.newOrder.selectedClients.map(c => c.id),
shipping_address: this.newOrder.shippingAddress,
        ship_date: this.newOrder.shipDate || null, status: this.newOrder.status,
        status_color: status?.color || '#fdab3d', trk: this.newOrder.trk || 'N/A',
        payment: this.isClient ? 'Not Yet' : (this.newOrder.payment || 'Not Yet')
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
      if (!this.canManageWorkflow || !this.selectedOrder) return
      const label = (this.customStatusLabel || '').trim()
      if (!label) return
      const custom = { label, color: this.customStatusColor || '#6161ff', group: 'in_production', groupLabel: 'In Production', custom: true }
      this.saveCustomStatusOption(custom)
      await this.changeStatus(custom)
      this.customStatusLabel = ''
      this.customStatusColor = '#6161ff'
    },

    async changeStatus(s) {
      if (!this.canEditWorkflowFields || !this.selectedOrder) return
      const activeWorker = this.workingDesigner(this.selectedOrder)
      const isShipped =
        String(s.label || '').trim().toLowerCase() === 'shipped'
      const shippedBy = isShipped ? this.currentUser : null
      try {
        await axios.put(`/api/orders/${this.selectedOrder.id}`, {
          status: s.label,
          status_color: s.color || '#6161ff',
          ...(isShipped && shippedBy
            ? {
                shipped_by_user_id: shippedBy.id,
                shipped_at: new Date().toISOString()
              }
            : {})
        }, { headers: this.headers() })
        this.selectedOrder.status = s.label
        this.selectedOrder.statusColor = s.color || '#6161ff'
        const targetGroup = s.group || this.statusToGroup(s.label)
        this.selectedOrder.group = targetGroup
        if (isShipped && shippedBy) {
          this.markOrderFinished(this.selectedOrder.id, shippedBy)
          this.selectedOrder.finished_by = shippedBy

          if (activeWorker) {
            await this.finishWorkForShippedOrder(this.selectedOrder, shippedBy)
          }
        }
        const idx = this.orders.findIndex(o => o.id === this.selectedOrder.id)
        if (idx !== -1) this.orders[idx] = { ...this.selectedOrder }
        this.orders = [...this.orders]
        this.showStatusMenu = false
        await this.fetchOrders({ silent: true, loadFiles: false })
      } catch (e) { console.error('changeStatus error:', e) }
    },

  async updateShipDate(event) {
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
  if (!this.selectedOrder || !this.canEditNotes || this.noteSaving) {
    this.endTextEditing()
    return
  }

  if (!String(card?.noteText || '').trim()) {
    this.endTextEditing()
    return
  }

  this.noteSaving = true

  try {
    await axios.put(`/api/orders/${this.selectedOrder.id}`, {
      notes: card.noteText
    }, { headers: this.headers() })

    if (this.isClient) {
      const orderId = Number(this.selectedOrder.id)
      if (!this.clientNoteSavedAtByOrder[orderId]) {
        this.clientNoteSavedAtByOrder = {
          ...this.clientNoteSavedAtByOrder,
          [orderId]: Date.now()
        }
        localStorage.setItem(
          'client_note_saved_at_by_order',
          JSON.stringify(this.clientNoteSavedAtByOrder)
        )
      }
      this.nowTick = Date.now()
    }

    card.saved = true
    setTimeout(() => { card.saved = false }, 2500)
  } catch (e) {
    console.error('saveNote error:', e)
    alert(e.response?.data?.message || 'Note save nahi hua')
  } finally {
    this.noteSaving = false
    this.endTextEditing()
  }
    },

    normalizeOrderFile(file) {
      const mime = file.mime_type || file.type || ''
      const name = file.original_name || file.name || 'File'
      let url = file.url || ''
      if (!url && file.file_path) url = `/storage/${file.file_path}`
      if (!url && file.path) url = `/storage/${String(file.path).replace(/^\//, '')}`
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

    filesFromResponse(payload) {
      if (Array.isArray(payload)) return payload
      if (Array.isArray(payload?.files)) return payload.files
      if (Array.isArray(payload?.data)) return payload.data
      if (Array.isArray(payload?.data?.files)) return payload.data.files
      return []
    },

    normalizeCardType(value) {
      const type = String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, '_')

      const aliases = {
        file: 'order_files',
        files: 'order_files',
        order_file: 'order_files',
        logo: 'logos',
        roster: 'team_roster',
        finished_product: 'finished_products',
        approved_mockups: 'approved_mockup'
      }

      return aliases[type] || type
    },

    async fetchOrderFiles(orderId) {
      if (!this.selectedOrder) return
      try {
        const res = await axios.get(`/api/orders/${orderId}/files`, { headers: this.headers() })
        const files = this.filesFromResponse(res.data)
        const normalizedFiles = files.map(file => ({
          ...this.normalizeOrderFile(file),
          cardType: this.normalizeCardType(file.card_type || file.cardType)
        }))
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
      const uploadingOrderId = Number(this.selectedOrder.id)
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
      this.uploadProgressByOrder = { ...this.uploadProgressByOrder, [uploadingOrderId]: 0 }
      this.uploadProgressByCard = { ...this.uploadProgressByCard, [cardType]: 0 }
      try {
        const res = await axios.post(`/api/orders/${uploadingOrderId}/files`, formData, {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}`, Accept: 'application/json' },
          onUploadProgress: progressEvent => {
            const total = Number(progressEvent.total || 0)
            const percent = total > 0 ? Math.min(100, Math.round((progressEvent.loaded * 100) / total)) : 0
            this.uploadProgressByOrder = { ...this.uploadProgressByOrder, [uploadingOrderId]: percent }
            this.uploadProgressByCard = { ...this.uploadProgressByCard, [cardType]: percent }
          }
        })
        const savedFilesRaw = res.data?.files || []
        const savedFiles = savedFilesRaw.map(file => ({ ...this.normalizeOrderFile(file), cardType }))
        const withoutTemp = (card.files || []).filter(file => !file.uploading)
        card.files = this.mergeFiles(withoutTemp, savedFiles)
      } catch (e) {
        card.files = (card.files || []).filter(file => !file.uploading)
        throw e
      } finally {
        const orderProgress = { ...this.uploadProgressByOrder }
        const cardProgress = { ...this.uploadProgressByCard }
        delete orderProgress[uploadingOrderId]
        delete cardProgress[cardType]
        this.uploadProgressByOrder = orderProgress
        this.uploadProgressByCard = cardProgress
      }
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
        notes: 150,
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
      this.detailSearchOpen = false
      this.showShippingAddressMenu = false
      this.customFieldMenu = { order: null, column: null, top: 0, left: 0, width: 260 }
    },

    beginTextEditing() {
      this.textEditing = true
    },

    inlineTextDraftKey(order, field) {
      return `${Number(order?.id || 0)}:${field}`
    },

    inlineTextServerValue(order, field) {
      if (field === 'notes') return this.orderNotesText(order)
      if (field === 'shipping_address') return String(order?.shippingAddress || '')
      return String(order?.[field] || '')
    },

    inlineTextDraftValue(order, field) {
      const key = this.inlineTextDraftKey(order, field)
      return Object.prototype.hasOwnProperty.call(this.inlineTextDrafts, key)
        ? this.inlineTextDrafts[key]
        : this.inlineTextServerValue(order, field)
    },

    beginInlineTextEditing(order, field) {
      const key = this.inlineTextDraftKey(order, field)
      if (!Object.prototype.hasOwnProperty.call(this.inlineTextDrafts, key)) {
        this.inlineTextDrafts = {
          ...this.inlineTextDrafts,
          [key]: this.inlineTextServerValue(order, field)
        }
      }
      this.beginTextEditing()
    },

    updateInlineTextDraft(order, field, value) {
      const key = this.inlineTextDraftKey(order, field)
      this.inlineTextDrafts = { ...this.inlineTextDrafts, [key]: value }
      this.inlineTextDirty = { ...this.inlineTextDirty, [key]: true }
    },

    clearInlineTextDraft(key) {
      const drafts = { ...this.inlineTextDrafts }
      const dirty = { ...this.inlineTextDirty }
      delete drafts[key]
      delete dirty[key]
      this.inlineTextDrafts = drafts
      this.inlineTextDirty = dirty
    },

    endTextEditing() {
      this.textEditing = false
    },

    canEditNotesForOrder(order) {
      if (!order?.id) return false
      if (!this.isClient) return this.canEditOrderNotes
      return !this.orderNotesText(order) && !this.clientNoteSavedAtByOrder[Number(order.id)]
    },

    async saveTextInline(order, field, event) {
      const input = event?.target
      const draftKey = this.inlineTextDraftKey(order, field)
      if (!input || !order?.id) {
        this.endTextEditing()
        return
      }

      if (this.inlineTextSaving[draftKey]) return

      const value = String(this.inlineTextDraftValue(order, field) || '').trim()
      const allowed = this.canEditWorkflowFields || (this.isClient && field === 'shipping_address')
      if (!allowed) {
        this.endTextEditing()
        return
      }

      if (value === this.inlineTextServerValue(order, field).trim()) {
        this.clearInlineTextDraft(draftKey)
        this.endTextEditing()
        return
      }

      this.inlineTextSaving = { ...this.inlineTextSaving, [draftKey]: true }

      try {
        await axios.put(
          `/api/orders/${order.id}`,
          { [field]: value === '' ? null : value },
          { headers: this.headers() }
        )
        if (field === 'shipping_address') order.shippingAddress = value
        else order[field] = value
        this.clearInlineTextDraft(draftKey)
      } catch (error) {
        console.error('Inline text save error:', error)
        alert(error.response?.data?.message || 'Value could not be saved.')
      } finally {
        const saving = { ...this.inlineTextSaving }
        delete saving[draftKey]
        this.inlineTextSaving = saving
        this.endTextEditing()
      }
    },

    handlePageBackgroundClick(event) {
      this.closeAllMenus()
      // Detail page clicks must never clear the remembered board row.
      if (this.detailOpen) return
      if (!event?.target?.closest?.('.board-table-row')) {
        this.lastOpenedOrderId = 0
        localStorage.removeItem('factory_last_opened_order_id')
      }
    },

    closeClientFilter() {
      this.clientFilterOpen = false
      this.clientFilterListOpen = false
      this.clientSearch = ''
    },

    openClientFilter() {
      this.clientSearch = ''
      this.clientFilterListOpen = false
      this.clientFilterOpen = true
    },

    selectClientFilter(clientId) {
      this.selectedClient = clientId
      this.closeClientFilter()
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

.payment-chip-paid { background: #dcfce7; color: #15803d; }
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
  border-radius: 100%;
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

/* PROSIX ORDER LOADER */
.prosix-loading-state {
  flex-direction: column;
  gap: 10px;
}

.prosix-loader-logo-wrap {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: prosixLoaderPulse 1.15s ease-in-out infinite;
}

.prosix-loader-logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.prosix-loading-state span {
  color: #9ca3af;
  font-size: 12px;
  font-weight: 700;
}

@keyframes prosixLoaderPulse {
  0%, 100% { transform: scale(.86); opacity: .45; }
  50% { transform: scale(1.08); opacity: 1; }
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
  background: #dcfce7;
  color: #15803d;
  border-color: #86efac;
  text-align: center;
  font-weight: 800;
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

.board-file-count-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, .72);
  color: #fff;
  font-size: 10px;
  font-weight: 900;
  line-height: 1;
  pointer-events: none;
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
  width: min(780px, 100%);
  max-height: 90vh;
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
  max-height: 280px;
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
  border: 1px solid #aeb0b3 !important;
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



/* =========================================================
   FINAL STATUS CELL STYLE
   Full name + square box + full status color
   ========================================================= */

.factory-board-page .board-col-status {
  overflow: visible !important;
}

.factory-board-page .status-ref-wrap {
  width: 100% !important;
  max-width: none !important;
  padding: 0 6px !important;
}

.factory-board-page .status-ref-trigger,
.factory-board-page .status-ref-trigger.open {
  width: 100% !important;
  min-width: 150px !important;
  max-width: none !important;
  height: 38px !important;

  padding: 0 11px !important;

  display: grid !important;
  grid-template-columns: 8px minmax(0, 1fr) 16px !important;
  align-items: center !important;
  gap: 8px !important;

  background: var(--status-fill) !important;
  background-color: var(--status-fill) !important;

  border: 1px solid var(--status-fill) !important;
  border-radius: 0 !important;

  color: var(--status-text) !important;

  box-shadow: none !important;
  overflow: visible !important;
}

.factory-board-page .status-ref-label {
  min-width: 0 !important;

  overflow: visible !important;
  text-overflow: clip !important;
  white-space: nowrap !important;

  color: var(--status-text) !important;

  font-size: 11px !important;
  font-weight: 800 !important;
  line-height: 1 !important;

  text-align: left !important;
}

.factory-board-page .status-ref-chevron {
  color: var(--status-text) !important;
  flex: 0 0 auto !important;
}

.factory-board-page .status-ref-dot {
  width: 7px !important;
  height: 7px !important;
  min-width: 7px !important;

  background: var(--status-text) !important;
  opacity: .75 !important;

  border-radius: 50% !important;
}

/* Keep the status cell centered */
.factory-board-page .row-status-cell {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

/* Do not let dark-mode rules replace the selected status color */
.factory-board-page.theme-dark .status-ref-trigger,
.factory-board-page.theme-dark .status-ref-trigger.open {
  background: var(--status-fill) !important;
  background-color: var(--status-fill) !important;
  border-color: var(--status-fill) !important;
  color: var(--status-text) !important;
}

.factory-board-page.theme-dark .status-ref-label,
.factory-board-page.theme-dark .status-ref-chevron {
  color: var(--status-text) !important;
}



/* =========================================================
   FINAL STATUS / PIPELINE ALIGNMENT FIX
   - no status dot
   - status text centered
   - status box never spills outside its column
   - vertical pipelines touch row top + bottom
   ========================================================= */

/* Every row cell clips its own content so resize never pushes
   status/owner controls outside the assigned column. */
.factory-board-page .board-table-row > .board-col {
  position: relative !important;
  min-width: 0 !important;
  overflow: hidden !important;
}

/* Status cell specifically */
.factory-board-page .board-table-row .row-status-cell {
  width: 100% !important;
  min-width: 0 !important;

  padding: 0 8px !important;

  display: flex !important;
  align-items: center !important;
  justify-content: center !important;

  overflow: hidden !important;
}

/* Wrapper must follow resized column width */
.factory-board-page .board-table-row .status-ref-wrap {
  width: 100% !important;
  min-width: 0 !important;
  max-width: 100% !important;

  padding: 0 !important;
  margin: 0 !important;

  overflow: hidden !important;
}

/* Full colored status box */
.factory-board-page .board-table-row .status-ref-trigger,
.factory-board-page .board-table-row .status-ref-trigger.open {
  width: 100% !important;
  min-width: 0 !important;
  max-width: 100% !important;
  height: 38px !important;

  margin: 0 !important;
  padding: 0 10px !important;

  display: grid !important;
  grid-template-columns: minmax(0, 1fr) 28px !important;
  align-items: stretch !important;

  background: var(--status-fill) !important;
  background-color: var(--status-fill) !important;

  border: 1px solid var(--status-fill) !important;
  border-radius: 0 !important;

  color: var(--status-text) !important;

  box-shadow: none !important;
  overflow: hidden !important;
}

/* Remove any old dot generated by CSS/template */
.factory-board-page .board-table-row .status-ref-dot {
  display: none !important;
}

/* Exact centered status name */
.factory-board-page .board-table-row .status-ref-label {
  min-width: 0 !important;
  width: 100% !important;

  display: flex !important;
  align-items: center !important;
  justify-content: center !important;

  padding: 0 4px !important;

  color: var(--status-text) !important;

  font-size: 11px !important;
  font-weight: 800 !important;
  line-height: 1 !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;

  text-align: center !important;
}

/* Arrow gets its own clean right section */
.factory-board-page .board-table-row .status-ref-chevron {
  width: 28px !important;
  height: 100% !important;

  margin: 0 !important;

  display: flex !important;
  align-items: center !important;
  justify-content: center !important;

  color: var(--status-text) !important;

  border-left: 1px solid color-mix(in srgb, var(--status-text), transparent 45%) !important;
}

/* =========================================================
   ROW VERTICAL PIPELINES — TOP TO BOTTOM ATTACHED
   ========================================================= */

/* Remove older short pseudo pipeline */
.factory-board-page .board-table-row > .board-col::before,
.factory-board-page .board-table-row > .board-col::after {
  content: none !important;
}

/* One full-height divider on the RIGHT of every column */
.factory-board-page .board-table-row > .board-col:not(:last-child) {
  border-right: 1px solid #111827 !important;
}

/* Do not add extra left/right margin around dividers */
.factory-board-page .board-table-row {
  column-gap: 0 !important;
}

/* Header pipelines also touch top and bottom */
.factory-board-page .board-table-head > .board-col {
  position: relative !important;
  min-width: 0 !important;
}

.factory-board-page .board-table-head > .board-col::before,
.factory-board-page .board-table-head > .board-col::after {
  content: none !important;
}

.factory-board-page .board-table-head > .board-col:not(:last-child) {
  border-right: 1px solid rgba(255,255,255,.78) !important;
}

/* The actual resize handle still stays draggable, but visually
   becomes the same full-height pipeline. */
.factory-board-page .column-resizer {
  position: absolute !important;
  top: 0 !important;
  right: -4px !important;
  bottom: 0 !important;

  width: 8px !important;
  height: 100% !important;

  cursor: col-resize !important;
  z-index: 20 !important;

  background: transparent !important;
}

.factory-board-page .column-resizer::before {
  content: "" !important;

  position: absolute !important;
  top: 0 !important;
  bottom: 0 !important;
  left: 50% !important;

  width: 1px !important;
  height: 100% !important;

  transform: translateX(-50%) !important;

  background: rgba(255,255,255,.78) !important;
}

/* When resizing, controls must remain inside their own cells */
.factory-board-page .board-table-row,
.factory-board-page .board-table-head {
  overflow: visible !important;
}

/* Keep owner/avatar content contained after status column resize */
.factory-board-page .board-col-owner {
  min-width: 0 !important;
  overflow: hidden !important;
}

.factory-board-page .board-avatar-stack {
  max-width: 100% !important;
  min-width: 0 !important;
  overflow: hidden !important;
}

/* Dark theme */
.factory-board-page.theme-dark .board-table-row > .board-col:not(:last-child) {
  border-right-color: rgba(255,255,255,.45) !important;
}


/* =========================================================
   FINAL COMPACT ROW + FULL STATUS FILL
   Added 2026-08-08
========================================================= */
.factory-board-page .board-table-row {
  min-height: 48px !important;
  height: 48px !important;
}

.factory-board-page .board-table-row > .board-col {
  min-height: 48px !important;
  height: 48px !important;
  padding-top: 0 !important;
  padding-bottom: 0 !important;
  align-items: center !important;
}

.factory-board-page .board-table-row .board-col-status.row-status-cell {
  height: 48px !important;
  min-height: 48px !important;
  padding: 0 !important;
  margin: 0 !important;
  overflow: hidden !important;
}

.factory-board-page .board-table-row .row-status-cell .status-ref-wrap {
  width: 100% !important;
  height: 100% !important;
  min-height: 48px !important;
  padding: 0 !important;
  margin: 0 !important;
}

.factory-board-page .board-table-row .row-status-cell .status-ref-trigger {
  width: 100% !important;
  height: 100% !important;
  min-height: 48px !important;
  margin: 0 !important;
  padding: 0 12px !important;
  border: 0 !important;
  border-radius: 0 !important;
  outline: 0 !important;
  box-shadow: none !important;
  background: var(--status-fill) !important;
  color: var(--status-text) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  gap: 8px !important;
}

.factory-board-page .board-table-row .row-status-cell .status-ref-label {
  flex: 1 1 auto !important;
  min-width: 0 !important;
  text-align: center !important;
  font-size: 12px !important;
  font-weight: 700 !important;
  line-height: 1 !important;
  color: inherit !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

.factory-board-page .board-table-row .row-status-cell .status-ref-chevron {
  flex: 0 0 auto !important;
  width: auto !important;
  min-width: 0 !important;
  height: auto !important;
  margin: 0 !important;
  padding: 0 !important;
  border: 0 !important;
  border-left: 0 !important;
  border-right: 0 !important;
  box-shadow: none !important;
  font-size: 10px !important;
  color: inherit !important;
}

.factory-board-page .board-table-row .row-status-cell .status-ref-trigger::before,
.factory-board-page .board-table-row .row-status-cell .status-ref-trigger::after,
.factory-board-page .board-table-row .row-status-cell .status-ref-chevron::before,
.factory-board-page .board-table-row .row-status-cell .status-ref-chevron::after {
  border-left: 0 !important;
  border-right: 0 !important;
}

.factory-board-page .board-table-row .row-status-cell .status-ref-chevron.rotate {
  transform: rotate(180deg) !important;
}
/* =========================================================
   ORDER NAME CELL - CLEAN COMPACT LAYOUT
========================================================= */

.board-table-row {
  min-height: 64px !important;
  height: 64px !important;
}

/* Name cell */
.board-col-name {
  position: relative !important;

  display: flex !important;
  align-items: center !important;

  min-width: 0 !important;
  height: 64px !important;

  padding: 6px 12px !important;

  overflow: hidden !important;
}

/* Main wrapper */
.board-col-name .inline-cell-wrap {
  width: 100% !important;
  min-width: 0 !important;

  display: flex !important;
  flex-direction: column !important;
  align-items: flex-start !important;
  justify-content: center !important;

  gap: 2px !important;
}

/* Order name button */
.board-col-name .name-value {
  width: 100% !important;
  min-width: 0 !important;

  display: flex !important;
  flex-direction: column !important;
  align-items: flex-start !important;

  padding: 0 !important;
  margin: 0 !important;

  background: transparent !important;
  border: 0 !important;

  text-align: left !important;
}

/* ORDER NAME bigger */
.board-col-name .name-value > strong {
  display: flex !important;
  align-items: center !important;
  gap: 6px !important;

  max-width: 100% !important;

  margin: 0 !important;

  color: #111827 !important;

  font-size: 13px !important;
  font-weight: 800 !important;
  line-height: 1.2 !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

/* small order icon */
.board-col-name .name-value > strong::before {
  content: "\f07b";

  font-family: "Font Awesome 6 Free";
  font-weight: 900;

  color: #94a3b8;

  font-size: 10px;

  flex: 0 0 auto;
}

/* PO NUMBER */
.board-col-name .name-value > small {
  display: flex !important;
  align-items: center !important;
  gap: 5px !important;

  margin-top: 2px !important;

  color: #94a3b8 !important;

  font-size: 9px !important;
  font-weight: 500 !important;
  line-height: 1 !important;

  white-space: nowrap !important;
}

/* PO icon */
.board-col-name .name-value > small::before {
  content: "\f02b";

  font-family: "Font Awesome 6 Free";
  font-weight: 900;

  color: #b0b8c4;

  font-size: 8px;
}

/* Start Order area */
.board-col-name .order-working-actions {
  margin-top: 4px !important;

  display: flex !important;
  align-items: center !important;
}

/* Start Order */
.board-col-name .start-working-btn {
  height: 19px !important;

  padding: 0 9px !important;

  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;

  gap: 4px !important;

  border: 0 !important;
  border-radius: 999px !important;

  background: #dcfce7 !important;
  color: #087443 !important;

  font-size: 8px !important;
  font-weight: 800 !important;
  line-height: 1 !important;

  white-space: nowrap !important;
}

/* Better play icon */
.board-col-name .start-working-btn i {
  font-size: 6px !important;
}

/* Started badge */
.board-col-name .working-locked-label {
  height: 19px !important;

  padding: 0 8px !important;

  display: inline-flex !important;
  align-items: center !important;

  gap: 4px !important;

  border-radius: 999px !important;

  background: #edf2f7 !important;
  color: #475569 !important;

  font-size: 8px !important;
  font-weight: 700 !important;
}

/* unread/new dot */
.board-col-name .board-new-dot {
  position: absolute !important;

  left: 5px !important;
  top: 10px !important;

  width: 6px !important;
  height: 6px !important;

  border-radius: 50% !important;

  background: #94a3b8 !important;
}

/* Leave small space when new dot exists */
.board-col-name:has(.board-new-dot) {
  padding-left: 18px !important;
}

/* Other cells remain vertically centered */
.board-table-row > .board-col:not(.board-col-name) {
  min-height: 64px !important;
  height: 64px !important;

  display: flex !important;
  align-items: center !important;
}

/* don't clip name content vertically */
.board-col-name,
.board-col-name .inline-cell-wrap,
.board-col-name .name-value {
  overflow-y: visible !important;
}


/* =========================================================
   FINAL WORKING USER POSITION
   User appears in the SAME right-side slot as Start Order
========================================================= */

.factory-board-page .board-col-name {
  position: relative !important;
  min-width: 0 !important;
  height: 64px !important;
  min-height: 64px !important;

  padding: 6px 180px 6px 14px !important;

  display: flex !important;
  align-items: center !important;

  overflow: hidden !important;
}

.factory-board-page .board-col-name .inline-cell-wrap {
  width: 100% !important;
  min-width: 0 !important;
  height: 100% !important;

  display: flex !important;
  align-items: center !important;

  margin: 0 !important;
  padding: 0 !important;

  overflow: hidden !important;
}

.factory-board-page .board-col-name .name-value {
  width: 100% !important;
  min-width: 0 !important;

  display: flex !important;
  flex-direction: column !important;
  align-items: flex-start !important;
  justify-content: center !important;

  padding: 0 !important;
  margin: 0 !important;

  border: 0 !important;
  background: transparent !important;

  overflow: hidden !important;
}

.factory-board-page .board-col-name .name-value > strong {
  width: 100% !important;
  min-width: 0 !important;

  margin: 0 !important;

  color: #111827 !important;

  font-size: 13px !important;
  font-weight: 800 !important;
  line-height: 1.2 !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

.factory-board-page .board-col-name .name-value > small {
  width: 100% !important;
  min-width: 0 !important;

  margin-top: 3px !important;

  color: #94a3b8 !important;

  font-size: 9px !important;
  font-weight: 500 !important;
  line-height: 1.1 !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

/* Old pill must never appear under the name */
.factory-board-page .working-designer-pill {
  display: none !important;
}

/* Same right-side slot */
.factory-board-page .board-col-name .order-working-actions {
  position: absolute !important;

  right: 8px !important;
  top: 50% !important;

  transform: translateY(-50%) !important;

  margin: 0 !important;
  padding: 0 !important;

  display: flex !important;
  align-items: center !important;
  justify-content: flex-end !important;

  z-index: 5 !important;
}

/* Start icon */
.factory-board-page .board-col-name .start-working-btn {
  position: static !important;
  transform: none !important;

  width: 30px !important;
  height: 30px !important;
  min-width: 30px !important;

  padding: 0 !important;
  margin: 0 !important;

  display: grid !important;
  place-items: center !important;

  border: 1px solid #bbf7d0 !important;
  border-radius: 50% !important;

  background: #ecfdf3 !important;
  color: #059669 !important;

  cursor: pointer !important;
  box-shadow: none !important;
}

.factory-board-page .board-col-name .start-working-btn i {
  margin: 0 !important;
  padding: 0 !important;
  color: #059669 !important;
  font-size: 9px !important;
}

/* Working user */
.factory-board-page .board-col-name .row-working-user {
  height: 30px !important;
  max-width: 166px !important;

  padding: 3px 8px 3px 5px !important;

  display: flex !important;
  align-items: center !important;
  gap: 5px !important;

  border: 1px solid #a7f3d0 !important;
  border-radius: 15px !important;

  background: #ecfdf5 !important;

  white-space: nowrap !important;
  overflow: hidden !important;
}

.factory-board-page .row-working-live-dot {
  width: 6px !important;
  height: 6px !important;
  min-width: 6px !important;

  border-radius: 50% !important;
  background: #22c55e !important;

  flex: 0 0 auto !important;
}

.factory-board-page .row-working-user img,
.factory-board-page .row-working-avatar-fallback {
  width: 21px !important;
  height: 21px !important;
  min-width: 21px !important;

  border-radius: 50% !important;
  object-fit: cover !important;

  display: grid !important;
  place-items: center !important;

  background: #111827 !important;
  color: #fff !important;

  font-size: 8px !important;
  font-weight: 800 !important;

  flex: 0 0 auto !important;
}

.factory-board-page .row-working-user strong {
  min-width: 0 !important;
  max-width: 84px !important;

  color: #047857 !important;

  font-size: 10px !important;
  font-weight: 800 !important;

  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
}

.factory-board-page .row-working-user small {
  color: #16a34a !important;
  font-size: 7px !important;
  font-weight: 600 !important;
  flex: 0 0 auto !important;
}

/* Hide old Started badge if old CSS/template survives anywhere */
.factory-board-page .working-locked-label {
  display: none !important;
}



/* =========================================================
   FINAL COMPACT ORDER ROW
   - order name + PO stay inside same fixed row
   - Start icon stays on right
   - after click, icon is replaced by working user in SAME place
   - row height never grows
========================================================= */

.factory-board-page .board-table-row {
  height: 54px !important;
  min-height: 54px !important;
  max-height: 54px !important;
}

/* all cells same fixed height and vertically centered */
.factory-board-page .board-table-row > .board-col {
  height: 54px !important;
  min-height: 54px !important;
  max-height: 54px !important;

  display: flex !important;
  align-items: center !important;

  padding-top: 0 !important;
  padding-bottom: 0 !important;

  overflow: hidden !important;
}

/* =========================
   ORDER NAME CELL
========================= */
.factory-board-page .board-col-name {
  position: relative !important;

  padding: 5px 48px 5px 14px !important;

  display: flex !important;
  align-items: center !important;

  overflow: hidden !important;
}

.factory-board-page .board-col-name .inline-cell-wrap {
  width: 100% !important;
  min-width: 0 !important;
  height: 44px !important;

  display: flex !important;
  align-items: center !important;

  padding: 0 !important;
  margin: 0 !important;

  overflow: hidden !important;
}

.factory-board-page .board-col-name .name-value {
  width: 100% !important;
  min-width: 0 !important;
  height: 44px !important;

  display: flex !important;
  flex-direction: column !important;
  align-items: flex-start !important;
  justify-content: center !important;

  gap: 2px !important;

  padding: 0 !important;
  margin: 0 !important;

  border: 0 !important;
  background: transparent !important;

  text-align: left !important;

  overflow: hidden !important;
}

/* order name */
.factory-board-page .board-col-name .name-value > strong {
  width: 100% !important;
  min-width: 0 !important;

  display: block !important;

  margin: 0 !important;
  padding: 0 !important;

  color: #111827 !important;

  font-size: 12px !important;
  font-weight: 800 !important;
  line-height: 15px !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

/* PO under name */
.factory-board-page .board-col-name .name-value > small {
  width: 100% !important;
  min-width: 0 !important;

  display: block !important;

  margin: 0 !important;
  padding: 0 !important;

  color: #94a3b8 !important;

  font-size: 8px !important;
  font-weight: 500 !important;
  line-height: 12px !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

/* do not create extra icons/lines before name or PO */
.factory-board-page .board-col-name .name-value > strong::before,
.factory-board-page .board-col-name .name-value > small::before {
  content: none !important;
  display: none !important;
}

/* unread dot stays left and does not disturb layout */
.factory-board-page .board-col-name .board-new-dot {
  position: absolute !important;
  left: 6px !important;
  top: 10px !important;

  width: 5px !important;
  height: 5px !important;
  min-width: 5px !important;

  border-radius: 50% !important;

  z-index: 2 !important;
}

/* =========================
   RIGHT SLOT
========================= */
.factory-board-page .board-col-name .order-working-actions {
  position: absolute !important;

  right: 7px !important;
  top: 50% !important;

  transform: translateY(-50%) !important;

  width: 34px !important;
  min-width: 34px !important;
  height: 34px !important;

  display: flex !important;
  align-items: center !important;
  justify-content: center !important;

  margin: 0 !important;
  padding: 0 !important;

  overflow: visible !important;

  z-index: 6 !important;
}

/* =========================
   START ICON BEFORE CLICK
========================= */
.factory-board-page .board-col-name .start-working-btn {
  position: static !important;
  inset: auto !important;
  transform: none !important;

  width: 28px !important;
  height: 28px !important;
  min-width: 28px !important;
  max-width: 28px !important;

  padding: 0 !important;
  margin: 0 !important;

  display: grid !important;
  place-items: center !important;

  border: 1px solid #bbf7d0 !important;
  border-radius: 50% !important;

  background: #ecfdf3 !important;
  color: #059669 !important;

  box-shadow: none !important;
}

.factory-board-page .board-col-name .start-working-btn i {
  margin: 0 !important;
  padding: 0 !important;

  color: #059669 !important;
  font-size: 8px !important;
}

/* =========================
   AFTER CLICK: SAME SLOT
========================= */
.factory-board-page .board-col-name .row-working-user {
  width: 128px !important;
  max-width: 128px !important;
  height: 28px !important;
  min-height: 28px !important;

  padding: 2px 7px 2px 5px !important;
  margin: 0 !important;

  display: flex !important;
  align-items: center !important;
  gap: 4px !important;

  border: 1px solid #a7f3d0 !important;
  border-radius: 14px !important;

  background: #ecfdf5 !important;

  white-space: nowrap !important;
  overflow: hidden !important;
}

.factory-board-page .row-working-live-dot {
  width: 5px !important;
  height: 5px !important;
  min-width: 5px !important;

  border-radius: 50% !important;
  background: #22c55e !important;

  flex: 0 0 auto !important;
}

.factory-board-page .row-working-user img,
.factory-board-page .row-working-avatar-fallback {
  width: 20px !important;
  height: 20px !important;
  min-width: 20px !important;

  border-radius: 50% !important;
  object-fit: cover !important;

  flex: 0 0 auto !important;
}

.factory-board-page .row-working-user strong {
  min-width: 0 !important;
  max-width: 67px !important;

  color: #047857 !important;

  font-size: 9px !important;
  font-weight: 800 !important;
  line-height: 1 !important;

  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
}

.factory-board-page .row-working-user small {
  color: #16a34a !important;

  font-size: 6px !important;
  font-weight: 700 !important;
  line-height: 1 !important;

  flex: 0 0 auto !important;
}

/* old under-name working pill / started badge never display */
.factory-board-page .working-designer-pill,
.factory-board-page .working-locked-label {
  display: none !important;
}

/* keep status and all other controls centered in same 54px row */
.factory-board-page .row-status-cell,
.factory-board-page .board-col-owner,
.factory-board-page .board-col-files,
.factory-board-page .board-col-packing,
.factory-board-page .board-col-chat,
.factory-board-page .board-col-payment,
.factory-board-page .board-col-address,
.factory-board-page .board-col-track,
.factory-board-page .board-col-info {
  height: 54px !important;
  min-height: 54px !important;
  max-height: 54px !important;

  display: flex !important;
  align-items: center !important;
}

/* status still fills its column height cleanly */
.factory-board-page .row-status-cell .status-ref-wrap,
.factory-board-page .row-status-cell .status-ref-trigger {
  height: 54px !important;
  min-height: 54px !important;
  max-height: 54px !important;
}

/* packing stays compact */
.factory-board-page .board-col-packing .packing-clean-input {
  height: 28px !important;
  min-height: 28px !important;
  line-height: 28px !important;
}
/* =========================================
   STATUS COLUMN PERFECT ALIGNMENT
========================================= */

/* status cell ke apne extra borders hatao */
.factory-board-page .row-status-cell {
  padding: 0 !important;
  margin: 0 !important;

  border-left: 0 !important;
  border-right: 0 !important;

  display: flex !important;
  align-items: stretch !important;
  justify-content: stretch !important;

  overflow: hidden !important;
}

/* wrapper full cell */
.factory-board-page .row-status-cell .status-ref-wrap {
  width: 100% !important;
  height: 100% !important;

  padding: 0 !important;
  margin: 0 !important;

  display: flex !important;
  align-items: stretch !important;
}

/* orange/colored box full equal width */
.factory-board-page .row-status-cell .status-ref-trigger {
  width: 100% !important;
  height: 100% !important;

  min-width: 0 !important;
  max-width: 100% !important;

  padding: 0 12px !important;
  margin: 0 !important;

  border: 0 !important;
  border-radius: 0 !important;

  display: flex !important;
  align-items: center !important;
  justify-content: center !important;

  box-sizing: border-box !important;
}

/* actual column divider only */
.factory-board-page .board-table-row > .board-col-status {
  border-left: 1px solid #111827 !important;
  border-right: 1px solid #111827 !important;
}

/* avoid duplicate line from neighbor columns */
.factory-board-page .board-table-row > .board-col-name {
  border-right: 0 !important;
}

.factory-board-page .board-table-row > .board-col-owner {
  border-left: 0 !important;
}

/* =========================================
   PACKING DETAIL - SAME SIZE AS TRACKING
========================================= */

.factory-board-page .board-col-packing {
  padding: 0 8px !important;
}

.factory-board-page .packing-clean-input {
  width: 100% !important;
  height: 28px !important;
  min-height: 28px !important;

  padding: 0 6px !important;
  margin: 0 !important;

  border: 0 !important;
  border-radius: 0 !important;

  background: transparent !important;
  color: #111827 !important;

  font-size: 9px !important;
  font-weight: 500 !important;
  line-height: 28px !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;

  outline: none !important;
  box-shadow: none !important;
}

.factory-board-page .packing-clean-input::placeholder {
  color: #98a2b3 !important;

  font-size: 9px !important;
  font-weight: 500 !important;
}

/* focus same clean tracking style */
.factory-board-page .packing-clean-input:focus {
  background: #ffffff !important;
  border: 1px solid #d0d5dd !important;
  border-radius: 4px !important;
}


/* =========================================================
   FINAL STATUS COLUMN FIX
   Equal black pipelines + full cell color + custom footer
========================================================= */

/* ROW STATUS CELL: exactly one line on each side */
.factory-board-page .board-table-row > .board-col-name {
  border-right: 0 !important;
}

.factory-board-page .board-table-row > .board-col-status.row-status-cell {
  position: relative !important;

  height: 54px !important;
  min-height: 54px !important;
  max-height: 54px !important;

  padding: 0 !important;
  margin: 0 !important;

  border-left: 1px solid #111827 !important;
  border-right: 1px solid #111827 !important;

  display: flex !important;
  align-items: stretch !important;
  justify-content: stretch !important;

  overflow: hidden !important;
  box-sizing: border-box !important;
}

.factory-board-page .board-table-row > .board-col-owner {
  border-left: 0 !important;
}

/* Kill old pseudo pipelines only in status cell */
.factory-board-page .board-table-row > .board-col-status::before,
.factory-board-page .board-table-row > .board-col-status::after {
  content: none !important;
  display: none !important;
}

/* Wrapper fills the exact space between both black lines */
.factory-board-page .row-status-cell .status-ref-wrap {
  width: 100% !important;
  min-width: 0 !important;
  max-width: 100% !important;

  height: 100% !important;
  min-height: 100% !important;
  max-height: 100% !important;

  padding: 0 !important;
  margin: 0 !important;

  display: flex !important;
  align-items: stretch !important;

  overflow: hidden !important;
}

/* Colored status fills ALL available cell area */
.factory-board-page .row-status-cell .status-ref-trigger,
.factory-board-page .row-status-cell .status-ref-trigger.open {
  width: 100% !important;
  min-width: 0 !important;
  max-width: 100% !important;

  height: 100% !important;
  min-height: 100% !important;
  max-height: 100% !important;

  margin: 0 !important;
  padding: 0 12px !important;

  display: grid !important;
  grid-template-columns: minmax(0, 1fr) 18px !important;
  align-items: center !important;
  gap: 6px !important;

  background: var(--status-fill) !important;
  background-color: var(--status-fill) !important;

  color: var(--status-text) !important;

  border: 0 !important;
  border-radius: 0 !important;

  outline: 0 !important;
  box-shadow: none !important;

  box-sizing: border-box !important;
  overflow: hidden !important;
}

/* centered full status name */
.factory-board-page .row-status-cell .status-ref-label {
  width: 100% !important;
  min-width: 0 !important;

  display: block !important;

  color: var(--status-text) !important;

  font-size: 10px !important;
  font-weight: 800 !important;
  line-height: 1 !important;

  text-align: center !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

/* Arrow has NO inner divider line */
.factory-board-page .row-status-cell .status-ref-chevron {
  width: 18px !important;
  min-width: 18px !important;
  height: auto !important;

  margin: 0 !important;
  padding: 0 !important;

  display: grid !important;
  place-items: center !important;

  color: var(--status-text) !important;

  border: 0 !important;
  border-left: 0 !important;
  border-right: 0 !important;

  font-size: 9px !important;
}

/* Header status/owner divider alignment */
.factory-board-page .board-table-head > .board-col-status {
  border-left: 1px solid rgba(255,255,255,.85) !important;
  border-right: 1px solid rgba(255,255,255,.85) !important;
  box-sizing: border-box !important;
}

.factory-board-page .board-table-head > .board-col-name {
  border-right: 0 !important;
}

.factory-board-page .board-table-head > .board-col-owner {
  border-left: 0 !important;
}

/* =========================================================
   ROW STATUS DROPDOWN
========================================================= */

.factory-board-page .status-fixed-dropdown {
  padding: 7px !important;

  background: #ffffff !important;

  border: 1px solid #d8dee8 !important;
  border-radius: 12px !important;

  box-shadow:
    0 18px 45px rgba(15,23,42,.16),
    0 4px 12px rgba(15,23,42,.08) !important;

  overflow: visible !important;
}

.factory-board-page .status-fixed-option-row {
  width: 100% !important;

  display: flex !important;
  align-items: center !important;

  border-radius: 7px !important;

  overflow: hidden !important;
}

.factory-board-page .status-fixed-option-row:hover,
.factory-board-page .status-fixed-option-row.active {
  background: #f4f6f8 !important;
}

.factory-board-page .status-fixed-option-row .status-fixed-option {
  flex: 1 1 auto !important;
  min-width: 0 !important;

  border-radius: 0 !important;
  background: transparent !important;
}

.factory-board-page .status-fixed-color-edit {
  position: relative !important;

  width: 32px !important;
  height: 32px !important;
  min-width: 32px !important;

  display: grid !important;
  place-items: center !important;

  color: #667085 !important;

  cursor: pointer !important;
}

.factory-board-page .status-fixed-color-edit i {
  font-size: 10px !important;
}

.factory-board-page .status-fixed-color-edit input {
  position: absolute !important;
  inset: 0 !important;

  width: 100% !important;
  height: 100% !important;

  opacity: 0 !important;
  cursor: pointer !important;
}

/* =========================================================
   ADD CUSTOM STATUS AT END OF DROPDOWN
========================================================= */

.factory-board-page .status-fixed-custom-add {
  margin-top: 7px !important;
  padding-top: 8px !important;

  border-top: 1px solid #e7ebf0 !important;
}

.factory-board-page .status-fixed-custom-title {
  margin-bottom: 7px !important;

  display: flex !important;
  align-items: center !important;
  gap: 6px !important;

  color: #344054 !important;

  font-size: 9px !important;
  font-weight: 800 !important;
}

.factory-board-page .status-fixed-custom-fields {
  display: grid !important;
  grid-template-columns: minmax(0, 1fr) 32px 42px !important;
  align-items: center !important;
  gap: 5px !important;
}

.factory-board-page .status-fixed-custom-input {
  width: 100% !important;
  height: 31px !important;

  padding: 0 8px !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 6px !important;

  background: #ffffff !important;
  color: #101828 !important;

  outline: none !important;

  font-size: 9px !important;
  font-weight: 600 !important;
}

.factory-board-page .status-fixed-custom-input:focus {
  border-color: #98a2b3 !important;
  box-shadow: 0 0 0 3px rgba(15,23,42,.05) !important;
}

.factory-board-page .status-fixed-custom-color {
  width: 32px !important;
  height: 31px !important;

  display: block !important;

  overflow: hidden !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 6px !important;

  background: #ffffff !important;
}

.factory-board-page .status-fixed-custom-color input {
  width: 42px !important;
  height: 41px !important;

  margin: -5px !important;
  padding: 0 !important;

  border: 0 !important;
  cursor: pointer !important;
}

.factory-board-page .status-fixed-custom-button {
  height: 31px !important;

  padding: 0 8px !important;

  border: 0 !important;
  border-radius: 6px !important;

  background: #101828 !important;
  color: #ffffff !important;

  font-size: 8px !important;
  font-weight: 800 !important;

  cursor: pointer !important;
}

.factory-board-page .status-fixed-custom-button:disabled {
  opacity: .4 !important;
  cursor: not-allowed !important;
}

/* Dark mode */
.factory-board-page.theme-dark .status-fixed-dropdown {
  background: #111827 !important;
  border-color: #334155 !important;
}

.factory-board-page.theme-dark .status-fixed-option-row:hover,
.factory-board-page.theme-dark .status-fixed-option-row.active {
  background: #1f2937 !important;
}

.factory-board-page.theme-dark .status-fixed-custom-add {
  border-top-color: #334155 !important;
}

.factory-board-page.theme-dark .status-fixed-custom-title {
  color: #f8fafc !important;
}

.factory-board-page.theme-dark .status-fixed-custom-input,
.factory-board-page.theme-dark .status-fixed-custom-color {
  background: #0f172a !important;
  border-color: #475569 !important;
  color: #f8fafc !important;
}



/* =========================================================
   MONDAY-STYLE ROW STATUS MANAGER
   Keep this block at the VERY END
========================================================= */

.factory-board-page .monday-status-menu {
  width: 320px !important;
  min-width: 320px !important;
  max-width: min(320px, calc(100vw - 24px)) !important;

  padding: 0 !important;

  overflow: hidden !important;

  background: #ffffff !important;

  border: 1px solid #d9dee7 !important;
  border-radius: 12px !important;

  box-shadow:
    0 20px 55px rgba(15, 23, 42, .18),
    0 4px 14px rgba(15, 23, 42, .08) !important;
}

/* header */
.factory-board-page .monday-status-menu-head {
  min-height: 56px !important;

  padding: 10px 12px !important;

  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  gap: 10px !important;

  background: #ffffff !important;

  border-bottom: 1px solid #edf0f4 !important;
}

.factory-board-page .monday-status-menu-head > div {
  min-width: 0 !important;

  display: flex !important;
  flex-direction: column !important;
  gap: 2px !important;
}

.factory-board-page .monday-status-menu-head strong {
  color: #101828 !important;

  font-size: 12px !important;
  font-weight: 800 !important;
}

.factory-board-page .monday-status-menu-head small {
  max-width: 235px !important;

  color: #98a2b3 !important;

  font-size: 8px !important;
  font-weight: 600 !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

.factory-board-page .monday-status-close {
  width: 29px !important;
  height: 29px !important;
  min-width: 29px !important;

  padding: 0 !important;

  display: grid !important;
  place-items: center !important;

  border: 0 !important;
  border-radius: 7px !important;

  background: transparent !important;
  color: #667085 !important;

  cursor: pointer !important;
}

.factory-board-page .monday-status-close:hover {
  background: #f2f4f7 !important;
  color: #101828 !important;
}

/* list */
.factory-board-page .monday-status-options {
  max-height: 310px !important;

  padding: 6px !important;

  overflow-y: auto !important;
  overflow-x: hidden !important;
}

.factory-board-page .monday-status-row {
  position: relative !important;

  width: 100% !important;
  min-height: 38px !important;

  display: flex !important;
  align-items: center !important;

  border-radius: 7px !important;

  transition: background .15s ease !important;
}

.factory-board-page .monday-status-row:hover,
.factory-board-page .monday-status-row.active {
  background: #f5f6f8 !important;
}

.factory-board-page .monday-status-row.active {
  box-shadow: inset 3px 0 0 #101828 !important;
}

/* selectable status */
.factory-board-page .monday-status-select {
  flex: 1 1 auto !important;
  min-width: 0 !important;
  height: 38px !important;

  padding: 0 8px !important;

  display: grid !important;
  grid-template-columns: 9px minmax(0, 1fr) 18px !important;
  align-items: center !important;
  gap: 8px !important;

  border: 0 !important;
  border-radius: 7px !important;

  background: transparent !important;
  color: #475467 !important;

  text-align: left !important;
  cursor: pointer !important;
}

.factory-board-page .monday-status-dot {
  width: 8px !important;
  height: 8px !important;
  min-width: 8px !important;

  border-radius: 50% !important;
}

.factory-board-page .monday-status-name {
  min-width: 0 !important;

  color: #475467 !important;

  font-size: 10px !important;
  font-weight: 600 !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

.factory-board-page .monday-status-check {
  color: #101828 !important;
  font-size: 9px !important;
}

/* edit/delete actions */
.factory-board-page .monday-status-actions {
  width: 58px !important;
  min-width: 58px !important;

  padding-right: 4px !important;

  display: flex !important;
  align-items: center !important;
  justify-content: flex-end !important;
  gap: 2px !important;

  opacity: 0 !important;
  transform: translateX(4px) !important;

  transition:
    opacity .15s ease,
    transform .15s ease !important;
}

.factory-board-page .monday-status-row:hover .monday-status-actions {
  opacity: 1 !important;
  transform: translateX(0) !important;
}

.factory-board-page .monday-status-actions button {
  width: 26px !important;
  height: 26px !important;

  padding: 0 !important;

  display: grid !important;
  place-items: center !important;

  border: 0 !important;
  border-radius: 6px !important;

  background: transparent !important;
  color: #667085 !important;

  cursor: pointer !important;
}

.factory-board-page .monday-status-actions button:hover {
  background: #e9edf2 !important;
  color: #101828 !important;
}

.factory-board-page .monday-status-actions button.danger:hover {
  background: #fff0f0 !important;
  color: #d92d20 !important;
}

/* inline edit */
.factory-board-page .monday-status-row.editing {
  padding: 5px !important;
  background: #f7f8fa !important;
}

.factory-board-page .monday-status-edit-row {
  width: 100% !important;

  display: grid !important;
  grid-template-columns: 30px minmax(0, 1fr) 28px 28px !important;
  align-items: center !important;
  gap: 5px !important;
}

.factory-board-page .monday-status-color-button {
  position: relative !important;

  width: 30px !important;
  height: 30px !important;

  display: grid !important;
  place-items: center !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 7px !important;

  background: #ffffff !important;

  overflow: hidden !important;
  cursor: pointer !important;
}

.factory-board-page .monday-status-color-button > span {
  width: 16px !important;
  height: 16px !important;

  border-radius: 5px !important;
}

.factory-board-page .monday-status-color-button input {
  position: absolute !important;
  inset: 0 !important;

  width: 100% !important;
  height: 100% !important;

  opacity: 0 !important;
  cursor: pointer !important;
}

.factory-board-page .monday-status-edit-input {
  width: 100% !important;
  min-width: 0 !important;
  height: 30px !important;

  padding: 0 8px !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 7px !important;

  background: #ffffff !important;
  color: #101828 !important;

  outline: none !important;

  font-size: 9px !important;
  font-weight: 600 !important;
}

.factory-board-page .monday-status-edit-input:focus {
  border-color: #8b95a7 !important;
  box-shadow: 0 0 0 3px rgba(15, 23, 42, .05) !important;
}

.factory-board-page .monday-status-save-edit,
.factory-board-page .monday-status-cancel-edit {
  width: 28px !important;
  height: 28px !important;

  padding: 0 !important;

  display: grid !important;
  place-items: center !important;

  border: 0 !important;
  border-radius: 6px !important;

  cursor: pointer !important;
}

.factory-board-page .monday-status-save-edit {
  background: #101828 !important;
  color: #ffffff !important;
}

.factory-board-page .monday-status-cancel-edit {
  background: #eaecf0 !important;
  color: #475467 !important;
}

/* add status footer */
.factory-board-page .monday-status-add {
  padding: 10px !important;

  background: #fafbfc !important;

  border-top: 1px solid #e7ebf0 !important;
}

.factory-board-page .monday-status-add-title {
  margin-bottom: 8px !important;

  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
}

.factory-board-page .monday-status-add-icon {
  width: 27px !important;
  height: 27px !important;

  display: grid !important;
  place-items: center !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 7px !important;

  background: #ffffff !important;
  color: #344054 !important;

  font-size: 9px !important;
}

.factory-board-page .monday-status-add-title > div {
  min-width: 0 !important;

  display: flex !important;
  flex-direction: column !important;
  gap: 1px !important;
}

.factory-board-page .monday-status-add-title strong {
  color: #344054 !important;

  font-size: 9px !important;
  font-weight: 800 !important;
}

.factory-board-page .monday-status-add-title small {
  color: #98a2b3 !important;

  font-size: 7px !important;
  font-weight: 500 !important;
}

.factory-board-page .monday-status-add-form {
  display: grid !important;
  grid-template-columns: 34px minmax(0, 1fr) 48px !important;
  align-items: center !important;
  gap: 6px !important;
}

.factory-board-page .monday-status-add-color {
  position: relative !important;

  width: 34px !important;
  height: 32px !important;

  display: grid !important;
  place-items: center !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 7px !important;

  background: #ffffff !important;

  overflow: hidden !important;
  cursor: pointer !important;
}

.factory-board-page .monday-status-add-color > span {
  width: 18px !important;
  height: 18px !important;

  border-radius: 5px !important;
}

.factory-board-page .monday-status-add-color input {
  position: absolute !important;
  inset: 0 !important;

  width: 100% !important;
  height: 100% !important;

  opacity: 0 !important;
  cursor: pointer !important;
}

.factory-board-page .monday-status-add-input {
  width: 100% !important;
  min-width: 0 !important;
  height: 32px !important;

  padding: 0 8px !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 7px !important;

  background: #ffffff !important;
  color: #101828 !important;

  outline: none !important;

  font-size: 9px !important;
  font-weight: 600 !important;
}

.factory-board-page .monday-status-add-input:focus {
  border-color: #8b95a7 !important;
  box-shadow: 0 0 0 3px rgba(15, 23, 42, .05) !important;
}

.factory-board-page .monday-status-add-button {
  height: 32px !important;

  padding: 0 10px !important;

  border: 0 !important;
  border-radius: 7px !important;

  background: #101828 !important;
  color: #ffffff !important;

  font-size: 8px !important;
  font-weight: 800 !important;

  cursor: pointer !important;
}

.factory-board-page .monday-status-add-button:disabled {
  opacity: .35 !important;
  cursor: not-allowed !important;
}

/* Dark */
.factory-board-page.theme-dark .monday-status-menu,
.factory-board-page.theme-dark .monday-status-menu-head {
  background: #111827 !important;
  border-color: #334155 !important;
}

.factory-board-page.theme-dark .monday-status-menu-head strong,
.factory-board-page.theme-dark .monday-status-name {
  color: #f8fafc !important;
}

.factory-board-page.theme-dark .monday-status-row:hover,
.factory-board-page.theme-dark .monday-status-row.active,
.factory-board-page.theme-dark .monday-status-row.editing {
  background: #1f2937 !important;
}

.factory-board-page.theme-dark .monday-status-add {
  background: #0f172a !important;
  border-color: #334155 !important;
}

.factory-board-page.theme-dark .monday-status-add-title strong {
  color: #f8fafc !important;
}

.factory-board-page.theme-dark .monday-status-edit-input,
.factory-board-page.theme-dark .monday-status-add-input,
.factory-board-page.theme-dark .monday-status-color-button,
.factory-board-page.theme-dark .monday-status-add-color {
  background: #111827 !important;
  border-color: #475569 !important;
  color: #f8fafc !important;
}

</style>


<style>
/* =========================================================
   GLOBAL STATUS DROPDOWN FIX
   This block is intentionally NOT scoped.
   The status popup uses position:fixed and had old rules
   overriding the newer scoped styles.
========================================================= */

.monday-status-menu.status-fixed-dropdown {
  position: fixed !important;
  z-index: 2147483647 !important;

  width: 320px !important;
  min-width: 320px !important;
  max-width: min(320px, calc(100vw - 24px)) !important;

  max-height: none !important;
  padding: 0 !important;

  overflow: hidden !important;

  background: #ffffff !important;
  border: 1px solid #d9dee7 !important;
  border-radius: 12px !important;

  box-shadow:
    0 22px 60px rgba(15, 23, 42, .18),
    0 5px 16px rgba(15, 23, 42, .08) !important;

  color: #101828 !important;

  font-family: inherit !important;
}

/* HEADER */
.monday-status-menu .monday-status-menu-head {
  min-height: 55px !important;
  padding: 10px 12px !important;

  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  gap: 10px !important;

  background: #ffffff !important;
  border-bottom: 1px solid #edf0f4 !important;
}

.monday-status-menu .monday-status-menu-head > div {
  min-width: 0 !important;

  display: flex !important;
  flex-direction: column !important;
  gap: 2px !important;
}

.monday-status-menu .monday-status-menu-head strong {
  color: #101828 !important;
  font-size: 12px !important;
  font-weight: 800 !important;
  line-height: 1.2 !important;
}

.monday-status-menu .monday-status-menu-head small {
  display: block !important;
  max-width: 235px !important;

  color: #98a2b3 !important;
  font-size: 8px !important;
  font-weight: 600 !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

.monday-status-menu .monday-status-close {
  width: 29px !important;
  height: 29px !important;
  min-width: 29px !important;

  padding: 0 !important;

  display: grid !important;
  place-items: center !important;

  border: 0 !important;
  border-radius: 7px !important;

  background: transparent !important;
  color: #667085 !important;

  cursor: pointer !important;
}

.monday-status-menu .monday-status-close:hover {
  background: #f2f4f7 !important;
  color: #101828 !important;
}

/* OPTIONS AREA ONLY SCROLLS */
.monday-status-menu .monday-status-options {
  max-height: 285px !important;

  padding: 6px !important;

  overflow-y: auto !important;
  overflow-x: hidden !important;

  background: #ffffff !important;
}

.monday-status-menu .monday-status-row {
  position: relative !important;

  width: 100% !important;
  min-height: 38px !important;

  display: flex !important;
  align-items: center !important;

  margin: 0 !important;
  padding: 0 !important;

  border: 0 !important;
  border-radius: 7px !important;

  background: transparent !important;
}

.monday-status-menu .monday-status-row + .monday-status-row {
  margin-top: 2px !important;
}

.monday-status-menu .monday-status-row:hover,
.monday-status-menu .monday-status-row.active {
  background: #f4f5f7 !important;
}

.monday-status-menu .monday-status-row.active {
  box-shadow: inset 3px 0 0 #111827 !important;
}

/* STATUS SELECT BUTTON */
.monday-status-menu .monday-status-select {
  flex: 1 1 auto !important;
  min-width: 0 !important;
  height: 38px !important;

  padding: 0 8px !important;
  margin: 0 !important;

  display: grid !important;
  grid-template-columns: 9px minmax(0, 1fr) 18px !important;
  align-items: center !important;
  gap: 8px !important;

  border: 0 !important;
  border-radius: 7px !important;

  background: transparent !important;
  color: #475467 !important;

  text-align: left !important;
  cursor: pointer !important;
}

.monday-status-menu .monday-status-dot {
  width: 8px !important;
  height: 8px !important;
  min-width: 8px !important;

  display: block !important;
  border-radius: 50% !important;
}

.monday-status-menu .monday-status-name {
  min-width: 0 !important;

  display: block !important;

  color: #475467 !important;
  font-size: 10px !important;
  font-weight: 600 !important;
  line-height: 1 !important;

  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
}

.monday-status-menu .monday-status-check {
  color: #101828 !important;
  font-size: 9px !important;
}

/* EDIT + DELETE ONLY FOR CUSTOM STATUS */
.monday-status-menu .monday-status-actions {
  width: 58px !important;
  min-width: 58px !important;

  padding-right: 4px !important;

  display: flex !important;
  align-items: center !important;
  justify-content: flex-end !important;
  gap: 2px !important;

  opacity: 0 !important;
  visibility: hidden !important;

  transition: opacity .15s ease !important;
}

.monday-status-menu .monday-status-row:hover .monday-status-actions {
  opacity: 1 !important;
  visibility: visible !important;
}

.monday-status-menu .monday-status-actions button {
  width: 26px !important;
  height: 26px !important;
  min-width: 26px !important;

  padding: 0 !important;
  margin: 0 !important;

  display: grid !important;
  place-items: center !important;

  border: 0 !important;
  border-radius: 6px !important;

  background: transparent !important;
  color: #667085 !important;

  cursor: pointer !important;
}

.monday-status-menu .monday-status-actions button:hover {
  background: #e9edf2 !important;
  color: #101828 !important;
}

.monday-status-menu .monday-status-actions button.danger:hover {
  background: #fff0f0 !important;
  color: #d92d20 !important;
}

/* INLINE EDIT MODE */
.monday-status-menu .monday-status-row.editing {
  padding: 5px !important;
  background: #f7f8fa !important;
}

.monday-status-menu .monday-status-edit-row {
  width: 100% !important;

  display: grid !important;
  grid-template-columns: 30px minmax(0, 1fr) 28px 28px !important;
  align-items: center !important;
  gap: 5px !important;
}

.monday-status-menu .monday-status-color-button {
  position: relative !important;

  width: 30px !important;
  height: 30px !important;

  display: grid !important;
  place-items: center !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 7px !important;

  background: #ffffff !important;

  overflow: hidden !important;
  cursor: pointer !important;
}

.monday-status-menu .monday-status-color-button > span {
  width: 16px !important;
  height: 16px !important;
  display: block !important;
  border-radius: 5px !important;
}

.monday-status-menu .monday-status-color-button input {
  position: absolute !important;
  inset: 0 !important;

  width: 100% !important;
  height: 100% !important;

  opacity: 0 !important;
  cursor: pointer !important;
}

.monday-status-menu .monday-status-edit-input {
  width: 100% !important;
  min-width: 0 !important;
  height: 30px !important;

  padding: 0 8px !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 7px !important;

  background: #ffffff !important;
  color: #101828 !important;

  outline: none !important;

  font-size: 9px !important;
  font-weight: 600 !important;
}

.monday-status-menu .monday-status-edit-input:focus {
  border-color: #98a2b3 !important;
  box-shadow: 0 0 0 3px rgba(15, 23, 42, .05) !important;
}

.monday-status-menu .monday-status-save-edit,
.monday-status-menu .monday-status-cancel-edit {
  width: 28px !important;
  height: 28px !important;

  padding: 0 !important;

  display: grid !important;
  place-items: center !important;

  border: 0 !important;
  border-radius: 6px !important;

  cursor: pointer !important;
}

.monday-status-menu .monday-status-save-edit {
  background: #101828 !important;
  color: #ffffff !important;
}

.monday-status-menu .monday-status-cancel-edit {
  background: #eaecf0 !important;
  color: #475467 !important;
}

/* ADD CUSTOM STATUS FOOTER */
.monday-status-menu .monday-status-add {
  padding: 10px !important;

  background: #fafbfc !important;
  border-top: 1px solid #e7ebf0 !important;
}

.monday-status-menu .monday-status-add-title {
  margin-bottom: 8px !important;

  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
}

.monday-status-menu .monday-status-add-icon {
  width: 27px !important;
  height: 27px !important;
  min-width: 27px !important;

  display: grid !important;
  place-items: center !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 7px !important;

  background: #ffffff !important;
  color: #344054 !important;

  font-size: 9px !important;
}

.monday-status-menu .monday-status-add-title > div {
  min-width: 0 !important;

  display: flex !important;
  flex-direction: column !important;
  gap: 1px !important;
}

.monday-status-menu .monday-status-add-title strong {
  color: #344054 !important;
  font-size: 9px !important;
  font-weight: 800 !important;
}

.monday-status-menu .monday-status-add-title small {
  color: #98a2b3 !important;
  font-size: 7px !important;
  font-weight: 500 !important;
}

.monday-status-menu .monday-status-add-form {
  display: grid !important;
  grid-template-columns: 34px minmax(0, 1fr) 48px !important;
  align-items: center !important;
  gap: 6px !important;
}

.monday-status-menu .monday-status-add-color {
  position: relative !important;

  width: 34px !important;
  height: 32px !important;

  display: grid !important;
  place-items: center !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 7px !important;

  background: #ffffff !important;

  overflow: hidden !important;
  cursor: pointer !important;
}

.monday-status-menu .monday-status-add-color > span {
  width: 18px !important;
  height: 18px !important;

  display: block !important;
  border-radius: 5px !important;
}

.monday-status-menu .monday-status-add-color input {
  position: absolute !important;
  inset: 0 !important;

  width: 100% !important;
  height: 100% !important;

  opacity: 0 !important;
  cursor: pointer !important;
}

.monday-status-menu .monday-status-add-input {
  width: 100% !important;
  min-width: 0 !important;
  height: 32px !important;

  padding: 0 8px !important;

  border: 1px solid #d0d5dd !important;
  border-radius: 7px !important;

  background: #ffffff !important;
  color: #101828 !important;

  outline: none !important;

  font-size: 9px !important;
  font-weight: 600 !important;
}

.monday-status-menu .monday-status-add-input:focus {
  border-color: #98a2b3 !important;
  box-shadow: 0 0 0 3px rgba(15, 23, 42, .05) !important;
}

.monday-status-menu .monday-status-add-button {
  height: 32px !important;

  padding: 0 10px !important;

  border: 0 !important;
  border-radius: 7px !important;

  background: #101828 !important;
  color: #ffffff !important;

  font-size: 8px !important;
  font-weight: 800 !important;

  cursor: pointer !important;
}

.monday-status-menu .monday-status-add-button:disabled {
  opacity: .35 !important;
  cursor: not-allowed !important;
}

/* DARK MODE */
.theme-dark .monday-status-menu.status-fixed-dropdown,
.theme-dark .monday-status-menu .monday-status-menu-head,
.theme-dark .monday-status-menu .monday-status-options {
  background: #111827 !important;
  border-color: #334155 !important;
}

.theme-dark .monday-status-menu .monday-status-menu-head strong,
.theme-dark .monday-status-menu .monday-status-name {
  color: #f8fafc !important;
}

.theme-dark .monday-status-menu .monday-status-row:hover,
.theme-dark .monday-status-menu .monday-status-row.active,
.theme-dark .monday-status-menu .monday-status-row.editing {
  background: #1f2937 !important;
}

.theme-dark .monday-status-menu .monday-status-add {
  background: #0f172a !important;
  border-color: #334155 !important;
}

.theme-dark .monday-status-menu .monday-status-add-title strong {
  color: #f8fafc !important;
}

.theme-dark .monday-status-menu .monday-status-edit-input,
.theme-dark .monday-status-menu .monday-status-add-input,
.theme-dark .monday-status-menu .monday-status-color-button,
.theme-dark .monday-status-menu .monday-status-add-color {
  background: #111827 !important;
  border-color: #475569 !important;
  color: #f8fafc !important;
}


/* ================= FILES: 5 THUMBS + DELETE + VIEW ALL ================= */
.board-row-file-thumb-wrap {
  position: relative;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  cursor: pointer;
}

.board-row-file-thumb-wrap .board-row-file-thumb {
  width: 100%;
  height: 100%;
  margin: 0;
}

.board-row-file-remove {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 18px;
  height: 18px;
  border: 0;
  border-radius: 50%;
  background: #ef4444;
  color: #fff;
  display: grid;
  place-items: center;
  font-size: 10px;
  cursor: pointer;
  z-index: 4;
  opacity: 0;
  transform: scale(.85);
  transition: .15s ease;
}

.board-row-file-thumb-wrap:hover .board-row-file-remove {
  opacity: 1;
  transform: scale(1);
}

.board-more-files {
  border: 0;
  background: transparent;
  cursor: pointer;
}

.detail-more-files {
  min-width: 42px;
  height: 42px;
  border: 1px dashed #94a3b8;
  border-radius: 8px;
  background: #f8fafc;
  color: #172b4d;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.files-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000000;
  background: rgba(15, 23, 42, .72);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  backdrop-filter: blur(3px);
}

.files-modal {
  width: min(1050px, 96vw);
  max-height: 90vh;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 28px 90px rgba(0,0,0,.35);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.files-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 18px;
  border-bottom: 1px solid #e5e7eb;
}

.files-modal-header h3 { margin: 0; color: #0f172a; font-size: 18px; }
.files-modal-header p { margin: 3px 0 0; color: #64748b; font-size: 12px; }
.files-modal-header-actions { display: flex; align-items: center; gap: 8px; }

.files-modal-download-all,
.files-modal-close {
  border: 0;
  border-radius: 9px;
  height: 36px;
  padding: 0 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  cursor: pointer;
  font-weight: 800;
}

.files-modal-download-all { background: #172b4d; color: #fff; }
.files-modal-close { width: 36px; padding: 0; background: #f1f5f9; color: #0f172a; }

.files-modal-grid {
  padding: 18px;
  overflow: auto;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 14px;
}

.files-modal-item {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}

.files-modal-preview {
  width: 100%;
  height: 135px;
  border: 0;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  overflow: hidden;
}

.files-modal-preview img { width: 100%; height: 100%; object-fit: contain; }
.files-modal-icon i { font-size: 46px; color: #64748b; }
.files-modal-item-info { padding: 10px 11px 7px; min-width: 0; }
.files-modal-item-info strong { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #0f172a; font-size: 12px; }
.files-modal-item-info small { color: #94a3b8; font-size: 10px; }
.files-modal-item-actions { display: flex; gap: 6px; padding: 0 10px 10px; }
.files-modal-item-actions button { width: 32px; height: 30px; border: 1px solid #e2e8f0; border-radius: 7px; background: #fff; color: #334155; cursor: pointer; }
.files-modal-item-actions button:hover { background: #f1f5f9; }
.files-modal-item-actions button.danger { color: #ef4444; }
.files-modal-empty { min-height: 260px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; color: #94a3b8; }
.files-modal-empty i { font-size: 42px; }

.universal-preview-modal {
  width: min(1100px, 96vw);
  height: min(800px, 92vh);
  padding: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.preview-modal-topbar {
  min-height: 52px;
  padding: 10px 12px 10px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.preview-modal-topbar strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #0f172a; }
.preview-modal-topbar > div { display: flex; gap: 7px; }
.preview-modal-topbar button { width: 34px; height: 34px; border: 0; border-radius: 8px; background: #f1f5f9; color: #0f172a; cursor: pointer; }
.preview-modal-body { flex: 1; min-height: 0; display: flex; align-items: center; justify-content: center; background: #eef2f7; overflow: auto; }
.file-preview-frame { width: 100%; height: 100%; background: #fff; }

@media (max-width: 700px) {
  .files-modal-overlay { padding: 10px; }
  .files-modal-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 10px; gap: 10px; }
  .files-modal-download-all span { display: none; }
  .universal-preview-modal { width: 98vw; height: 90vh; }
}


/* =========================================================
   OUTER BOARD FILES: 4 THUMBNAILS + EXTRA COUNT + ADD BUTTON
   Detail page / View All is NOT limited.
   ========================================================= */
.board-col-files .board-row-files {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 3px;
  width: 100%;
  min-width: 0;
  overflow: visible;
  flex-wrap: nowrap;
}

.board-col-files .board-row-file-thumb-wrap {
  position: relative;
  width: 28px !important;
  height: 32px !important;
  min-width: 28px !important;
  flex: 0 0 28px !important;
}

.board-col-files .board-row-file-thumb-wrap .board-row-file-thumb {
  width: 28px !important;
  height: 32px !important;
  min-width: 28px !important;
  padding: 0 !important;
  border: 1px solid #d8dee9;
  border-radius: 5px;
  background: #fff;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.board-col-files .board-row-file-thumb img {
  width: 100% !important;
  height: 100% !important;
  display: block;
  object-fit: cover;
}

.board-col-files .board-row-file-thumb i {
  font-size: 13px !important;
}

.board-col-files .board-more-files-button {
  width: 28px !important;
  height: 32px !important;
  min-width: 28px !important;
  flex: 0 0 28px !important;
  margin: 0 !important;
  padding: 0 !important;
  border: 1px dashed #9aa8bc;
  border-radius: 5px;
  background: #f8fafc;
  color: #172b4d;
  font-size: 10px;
  line-height: 1;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.board-col-files .board-more-files-button:hover {
  background: #eef2ff;
  border-color: #64748b;
}

.board-col-files .board-row-file-add {
  width: 30px !important;
  height: 32px !important;
  min-width: 30px !important;
  flex: 0 0 30px !important;
  margin: 0 !important;
  padding: 0 !important;
  border: 1px dashed #aab6c7;
  border-radius: 5px;
  background: #fff;
  color: #172b4d;
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.board-col-files .board-row-file-add i {
  font-size: 14px !important;
}

.board-col-files .board-row-file-remove {
  width: 14px !important;
  height: 14px !important;
  min-width: 14px !important;
  right: -3px !important;
  top: -4px !important;
  font-size: 8px !important;
}


/* =========================================================
   CHAT + ORDER NOTIFICATION CENTER
========================================================= */

.notification-center-dropdown {
  overflow: hidden !important;
  max-height: 460px !important;
}

.notification-center-head {
  position: relative !important;
}

.notification-center-head > div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  gap: 12px;
}

.notification-center-head small {
  color: #64748b;
  font-size: 10px;
  font-weight: 800;
  white-space: nowrap;
}

.notification-tabs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
  padding: 8px;
  border-bottom: 1px solid #e8edf3;
  background: #f8fafc;
}

.notification-tabs button {
  min-height: 36px;
  border: 1px solid transparent;
  border-radius: 8px;
  background: transparent;
  color: #64748b;
  font-size: 11px;
  font-weight: 900;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.notification-tabs button:hover {
  background: #ffffff;
  color: #0f172a;
}

.notification-tabs button.active {
  background: #ffffff;
  border-color: #dbe2ea;
  color: #0f172a;
  box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
}

.notification-tabs button > span {
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  background: #111827;
  color: #ffffff;
  font-size: 9px;
  font-weight: 900;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.notification-list {
  max-height: 340px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.order-notification-item {
  position: relative;
}

.order-notification-icon {
  background: #ecfdf5 !important;
  color: #059669 !important;
}

.order-notification-new-dot {
  width: 8px;
  height: 8px;
  flex: 0 0 8px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, .12);
}

.theme-dark .notification-center-head,
.theme-dark .notification-tabs {
  background: #111827 !important;
  border-color: #334155 !important;
}

.theme-dark .notification-center-head small {
  color: #cbd5e1 !important;
}

.theme-dark .notification-tabs button {
  color: #cbd5e1 !important;
}

.theme-dark .notification-tabs button:hover,
.theme-dark .notification-tabs button.active {
  background: #1e293b !important;
  color: #ffffff !important;
  border-color: #475569 !important;
}

.theme-dark .notification-tabs button > span {
  background: #ffffff !important;
  color: #111827 !important;
}

@media (max-width: 767px) {
  .notification-center-dropdown {
    max-height: calc(100vh - 95px) !important;
  }

  .notification-list {
    max-height: calc(100vh - 210px);
  }
}



/* =========================================================
   FINAL FIX — KEEP APP SIDEBAR VISIBLE IN ORDER DETAIL
   =========================================================
   AppLayout desktop sidebar width = 250px.
   Detail overlay starts AFTER the sidebar instead of covering it.
========================================================= */

.board-detail-overlay {
  position: fixed !important;
  top: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  left: 250px !important;

  width: auto !important;
  height: 100vh !important;

  margin: 0 !important;
  padding: 14px !important;

  z-index: 900 !important;

  background: rgba(15, 23, 42, 0.50) !important;

  display: flex !important;
  align-items: stretch !important;
  justify-content: stretch !important;

  overflow: hidden !important;
  box-sizing: border-box !important;
}

/* Full available area on the RIGHT of sidebar */
.board-detail-overlay .board-detail-panel,
.board-detail-overlay .clean-detail-panel {
  position: relative !important;

  top: auto !important;
  right: auto !important;
  bottom: auto !important;
  left: auto !important;

  width: 100% !important;
  max-width: none !important;

  height: calc(100vh - 28px) !important;
  max-height: calc(100vh - 28px) !important;

  margin: 0 !important;

  border-radius: 16px !important;

  overflow-y: auto !important;
  overflow-x: hidden !important;

  overscroll-behavior: contain;

  box-sizing: border-box !important;
}

/* Keep detail header/topbar inside the right-side panel */
.board-detail-overlay .clean-detail-header {
  width: 100% !important;
  flex: 0 0 auto;
}

.board-detail-overlay .detail-topbar-wrapper {
  width: 100% !important;
}

/* Avoid accidental width spilling */
.board-detail-overlay .orders-right,
.board-detail-overlay .detail-body,
.board-detail-overlay .cards-area,
.board-detail-overlay .cards-grid {
  max-width: 100% !important;
  box-sizing: border-box !important;
}

/* =========================================================
   TABLET / MOBILE
   App sidebar becomes drawer, so detail can use full screen.
========================================================= */

@media (max-width: 991px) {
  .board-detail-overlay {
    left: 0 !important;
    width: 100% !important;
    padding: 8px !important;
  }

  .board-detail-overlay .board-detail-panel,
  .board-detail-overlay .clean-detail-panel {
    width: 100% !important;
    height: calc(100vh - 16px) !important;
    max-height: calc(100vh - 16px) !important;
    border-radius: 12px !important;
  }
}

@media (max-width: 576px) {
  .board-detail-overlay {
    padding: 0 !important;
  }

  .board-detail-overlay .board-detail-panel,
  .board-detail-overlay .clean-detail-panel {
    width: 100% !important;
    height: 100vh !important;
    max-height: 100vh !important;
    border-radius: 0 !important;
  }
}



/* =========================================================
   FINAL BOARD UX FIX
   Requested:
   - background #f4f5f8
   - sections stay in their own original position
   - only one section open, others collapsed
   - gap after final order row
========================================================= */

.factory-board-page,
.factory-board {
  background: #f4f5f8 !important;
}

.factory-board-page {
  min-height: 100vh !important;
}

.factory-board {
  display: flex !important;
  flex-direction: column !important;
  align-items: stretch !important;
  gap: 0 !important;
}

/* Allow each collapsed status button to be ordered independently. */
.collapsed-status-bars {
  display: contents !important;
}

.collapsed-status-bar {
  width: 100% !important;
  flex: 0 0 auto !important;
  margin-top: 10px !important;
  margin-bottom: 0 !important;
}

.board-section-heading.collapsible-active-heading {
  flex: 0 0 auto !important;
  margin-top: 10px !important;
  margin-bottom: 0 !important;
}

.board-table-shell {
  flex: 0 0 auto !important;
  margin-bottom: 14px !important;
  padding-bottom: 0 !important;
}

.board-bulk-toolbar {
  flex: 0 0 auto !important;
  margin-top: 6px !important;
  margin-bottom: 6px !important;
}

@media (max-width: 991px) {
  .collapsed-status-bar,
  .board-section-heading.collapsible-active-heading {
    margin-top: 8px !important;
  }

  .board-table-shell {
    margin-bottom: 10px !important;
  }
}



/* =========================================================
   FINAL VISUAL UPDATE
   - 50px breathing space after an open order section
   - exposed gap/background = #f4f5f8
   - Prosix P logo replaces Home icon in All Orders card
========================================================= */

/* Whole Factory Orders canvas */
.factory-board-page,
.factory-board {
  background: #f4f5f8 !important;
}

/*
 * Give the open section 50px of breathing room before the
 * next collapsed workflow section. Because this is margin,
 * the parent #f4f5f8 background is visible in that space.
 */
.board-table-shell {
  margin-bottom: 50px !important;
}

/* Do not add another large gap on top of the following bar. */
.collapsed-status-bar {
  margin-top: 0 !important;
}

/*
 * If the selected/open section has no orders, the same
 * 50px separation should still remain before the next section.
 */
.board-empty-state {
  background: #f4f5f8;
}

/* Prosix icon inside the All Orders / To Open summary card */
.prosix-summary-icon {
  overflow: hidden;
  padding: 4px !important;
  background: #ffffff !important;
}

.prosix-summary-logo {
  width: 80%;
  height: 80%;
  max-width: 28px;
  max-height: 28px;
  display: block;
  object-fit: contain;
}

/* Keep the icon clean in dark mode as well */
.theme-dark .prosix-summary-icon {
  background: #ffffff !important;
}

/* Mobile: keep a slightly smaller but still clear section gap */
@media (max-width: 991px) {
  .board-table-shell {
    margin-bottom: 32px !important;
  }
}



/* =========================================================
   FINAL ACCORDION POSITION FIX
   Every section opens in ITS OWN ORIGINAL POSITION.
   Closed sections remain where they belong.
========================================================= */

/*
  IMPORTANT:
  collapsed-status-bars is only a wrapper.
  display: contents makes each collapsed button participate
  directly in .factory-board flex ordering, so the existing
  boardSectionOrder(...) values actually work.
*/
.collapsed-status-bars {
  display: contents !important;
}

/* Closed sections keep the requested 50px spacing */
.collapsed-status-bar {
  margin-top: 0 !important;
  margin-bottom: 50px !important;
}

/* Open section/table also leaves the same spacing below it */
.board-table-shell {
  margin-bottom: 50px !important;
}

/* Active heading and its table stay together in the same slot */
.board-section-heading.collapsible-active-heading {
  margin-top: 0 !important;
}

/* Requested gray canvas in all gaps */
.factory-board-page,
.factory-board {
  background: #f4f5f8 !important;
}
/* =========================================================
   FINAL ORDER ROW GAP COLOR FIX
   KEEP THIS AT VERY END OF <style>
   ========================================================= */

/* Table ke andar rows ke beech exposed area */
.factory-board-page.theme-light .board-table-shell {
  background: #f4f5f8 !important;
}

/* Header black hi rahe */
.factory-board-page.theme-light .board-table-head {
  background: #000000 !important;
}

/* Individual order row white */
.factory-board-page.theme-light .board-table-row {
  background: #ffffff !important;

  /* gap between orders */
  margin-top: 0 !important;
  margin-bottom: 8px !important;

  border-top: 0 !important;
  border-bottom: 0 !important;
}

/* Row ke andar cells white */
.factory-board-page.theme-light .board-table-row > .board-col {
  background: #ffffff !important;
}

/* IMPORTANT:
   kisi old red border / shadow ko completely remove karo
*/
.factory-board-page.theme-light .board-table-row,
.factory-board-page.theme-light .board-table-row.opened,
.factory-board-page.theme-light .board-table-row.unread,
.factory-board-page.theme-light .board-table-row.selected {
  border-top-color: transparent !important;
  border-bottom-color: transparent !important;
  box-shadow: none !important;
}

/* Do rows ke darmiyan exact requested color */
.factory-board-page.theme-light
.board-table-row + .board-table-row {
  margin-top: 0 !important;
}

/* Inline add row */
.factory-board-page.theme-light .board-inline-add-row {
  background: #ffffff !important;
}

/* Empty area */
.factory-board-page.theme-light .board-empty-state {
  background: #f4f5f8 !important;
}

/* Whole board background */
.factory-board-page.theme-light,
.factory-board-page.theme-light .factory-board {
  background: #f4f5f8 !important;
}


/* =========================================================
   FINAL DETAIL PAGE UPDATE
   - order detail outer/padding background = #f4f5f8
   - detail canvas = #f4f5f8
   - board notification bell hidden while detailOpen via v-show
========================================================= */

.factory-board-page.theme-light .board-detail-overlay,
.board-detail-overlay {
  background: #f4f5f8 !important;
  background-color: #f4f5f8 !important;
}

/* Main order detail panel/canvas */
.board-detail-overlay .board-detail-panel,
.board-detail-overlay .clean-detail-panel,
.board-detail-overlay .orders-right,
.board-detail-overlay .detail-body,
.board-detail-overlay .cards-area {
  background: #f4f5f8 !important;
  background-color: #f4f5f8 !important;
}

/* Keep actual cards white on gray canvas */
.board-detail-overlay .order-card,
.board-detail-overlay .detail-topbar-wrapper,
.board-detail-overlay .detail-topbar,
.board-detail-overlay .card-preview-area,
.board-detail-overlay .card-footer-inner {
  background-color: #ffffff !important;
}

/* Detail header stays black */
.board-detail-overlay .clean-detail-header,
.board-detail-overlay .detail-header {
  background: #000000 !important;
  background-image: none !important;
}



/* =========================================================
   SAME BACKGROUND AS P LOGO AREA - CLEAN FINAL OVERRIDE
   Exact color: #f4f5f8
========================================================= */

.factory-board-page,
.factory-board-page.theme-light,
.factory-board-page.theme-light .factory-board,
.factory-board,
.board-detail-overlay,
.board-detail-overlay .board-detail-panel,
.board-detail-overlay .clean-detail-panel,
.board-detail-overlay .orders-right,
.board-detail-overlay .detail-body,
.board-detail-overlay .cards-area {
  background: #f4f5f8 !important;
  background-color: #f4f5f8 !important;
}

/* Remove any outer detail shadow/border that makes the two grays look different */
.board-detail-overlay .board-detail-panel,
.board-detail-overlay .clean-detail-panel,
.board-detail-overlay .orders-right {
  box-shadow: none !important;
  border-color: transparent !important;
}

/* Actual content cards remain clean white */
.board-detail-overlay .order-card,
.board-detail-overlay .detail-topbar-wrapper,
.board-detail-overlay .detail-topbar,
.board-detail-overlay .card-preview-area,
.board-detail-overlay .card-footer-inner {
  background: #ffffff !important;
  background-color: #ffffff !important;
}

/* Header remains black */
.board-detail-overlay .clean-detail-header,
.board-detail-overlay .detail-header {
  background: #000000 !important;
  background-color: #000000 !important;
  background-image: none !important;
}



/* =========================================================
   SUPER ADMIN ONLY - STOP WORK BUTTON
   (Background/colors above are left untouched)
========================================================= */

.row-working-control {
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
  max-width: 100% !important;
}

.superadmin-stop-working-btn {
  width: 24px !important;
  min-width: 24px !important;
  height: 24px !important;
  padding: 0 !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  border: 1px solid #111827 !important;
  border-radius: 50% !important;
  background: #111827 !important;
  color: #ffffff !important;
  cursor: pointer !important;
  flex-shrink: 0 !important;
  font-size: 8px !important;
}

.superadmin-stop-working-btn:hover {
  background: #ffffff !important;
  color: #111827 !important;
}



/* In Production: after Start Work show only working user's avatar */
.working-user-avatar-only {
  width: 34px;
  height: 34px;
  min-width: 34px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  overflow: hidden;
  background: #f3f4f6;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: default;
  box-shadow: 0 0 0 2px #fff, 0 0 0 3px rgba(0, 0, 0, 0.08);
}

.working-user-avatar-only img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.working-user-avatar-only .row-working-avatar-fallback {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  line-height: 1;
}

/* Shipped order: finisher profile with a checkered finish flag */
.finished-user-avatar-only {
  position: relative;
  width: 34px;
  height: 34px;
  min-width: 34px;
  padding: 0;
  border: 2px solid #16a34a;
  border-radius: 50%;
  overflow: visible;
  background: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 0 2px #fff, 0 0 0 3px rgba(22, 163, 74, .22);
}

.finished-user-avatar-only > img,
.finished-user-avatar-only > .row-working-avatar-fallback {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  display: grid;
  place-items: center;
  overflow: hidden;
}

.finished-flag-badge {
  position: absolute;
  right: -7px;
  bottom: -5px;
  width: 19px;
  height: 19px;
  border: 2px solid #ffffff;
  border-radius: 50%;
  background: #16a34a;
  color: #ffffff;
  display: grid;
  place-items: center;
  font-size: 8px;
  box-shadow: 0 2px 7px rgba(22, 163, 74, .32);
}



/* =========================================================
   FINAL ORDER NAME + WORKER AVATAR FIX
   Keep order name visible right up to the worker avatar.
   No large empty/white reserved area.
   ========================================================= */
.factory-board-page .board-table-row > .board-col-name {
  padding-left: 14px !important;
  padding-right: 48px !important;
}

.factory-board-page .board-col-name .inline-cell-wrap,
.factory-board-page .board-col-name .name-value {
  width: 100% !important;
  max-width: 100% !important;
  min-width: 0 !important;
}

.factory-board-page .board-col-name .name-value > strong,
.factory-board-page .board-col-name .name-value > small {
  width: 100% !important;
  max-width: 100% !important;
  min-width: 0 !important;
}

.factory-board-page .board-col-name .order-working-actions {
  right: 7px !important;
  width: 34px !important;
  min-width: 34px !important;
  max-width: 34px !important;
  height: 34px !important;
  overflow: visible !important;
}

.factory-board-page .board-col-name .working-user-avatar-only {
  width: 30px !important;
  height: 30px !important;
  min-width: 30px !important;
  max-width: 30px !important;
  padding: 0 !important;
  margin: 0 !important;
  border-radius: 50% !important;
  overflow: hidden !important;
  flex: 0 0 30px !important;
}

.factory-board-page .board-col-name .working-user-avatar-only img {
  width: 30px !important;
  height: 30px !important;
  min-width: 30px !important;
  object-fit: cover !important;
  border-radius: 50% !important;
}

.factory-board-page .board-col-name .working-user-avatar-only .row-working-avatar-fallback {
  width: 30px !important;
  height: 30px !important;
  min-width: 30px !important;
  border-radius: 50% !important;
}

.factory-board-page .board-col-name .start-working-btn {
  width: 28px !important;
  height: 28px !important;
  min-width: 28px !important;
  max-width: 28px !important;
  margin: 0 !important;
}



/* =========================================================
   PAGE HEADER NOTIFICATION DROPDOWN
   ========================================================= */
.factory-board-page .page-header-notification-slot .chat-notification-dropdown {
  position: absolute !important;
  top: calc(100% + 10px) !important;
  right: 0 !important;
  left: auto !important;
  width: min(390px, calc(100vw - 32px)) !important;
  z-index: 99999 !important;
  margin: 0 !important;
}

@media (max-width: 780px) {
  .factory-board-page .page-header-notification-slot .chat-notification-dropdown {
    right: -58px !important;
    width: min(360px, calc(100vw - 24px)) !important;
  }
}



/* =========================================================
   FINAL STATUS MANAGER + GREEN PAYMENT UPDATE
   ========================================================= */

/* Edit/Delete controls are available for default and custom statuses. */
.monday-status-actions {
  display: flex !important;
  align-items: center !important;
  gap: 4px !important;
  flex: 0 0 auto !important;
}

/* Payment labels/pills are green instead of yellow. */
.factory-board-page .payment-input-inline {
  background: #dcfce7 !important;
  color: #15803d !important;
  border-color: #86efac !important;
}

.factory-board-page .payment-input-inline:hover,
.factory-board-page .payment-input-inline:focus {
  background: #ecfdf5 !important;
  color: #166534 !important;
  border-color: #4ade80 !important;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, .12) !important;
}

.factory-board-page .payment-chip-paid {
  background: #dcfce7 !important;
  color: #15803d !important;
}



/* =========================================================
   FINAL OUTER ROW NOTES + DETAIL TOOLBAR FIX
   ========================================================= */

/* NOTES column */
.board-col-notes {
  min-width: 0 !important;
}

.board-notes-inline-input {
  width: 100%;
  min-width: 0;
  height: 34px;

  padding: 0 10px;

  background: transparent;
  color: #111827;

  border: 1px solid transparent;
  border-radius: 7px;

  outline: none;

  font-size: 10px;
  font-weight: 600;

  white-space: pre-wrap;
  overflow: auto;
  resize: none;
  font-family: inherit;

  transition:
    background .15s ease,
    border-color .15s ease,
    box-shadow .15s ease;
}

.board-notes-inline-input:not(:read-only):hover {
  background: #f8fafc;
  border-color: #dbe2ea;
}

.board-notes-inline-input:not(:read-only):focus {
  background: #ffffff;
  border-color: #94a3b8;
  box-shadow: 0 0 0 3px rgba(148, 163, 184, .12);
}

.board-notes-inline-input:read-only {
  cursor: default;
  color: #667085;
}

/*
 * IMPORTANT:
 * Global PageHeader is 165px high.
 * Detail overlay now starts UNDER it, so:
 * Back to Orders + Order Name + Chat + Pipeline
 * are never hidden behind the header.
 */
.board-detail-overlay {
  top: 165px !important;
  bottom: 0 !important;
  height: calc(100vh - 165px) !important;
  min-height: 0 !important;
  z-index: 900 !important;
}

.board-detail-overlay .board-detail-panel,
.board-detail-overlay .clean-detail-panel {
  height: calc(100vh - 193px) !important;
  max-height: calc(100vh - 193px) !important;
}

/* Keep the order toolbar visible at the top while detail scrolls. */
.board-detail-overlay .clean-detail-header,
.board-detail-overlay .detail-header {
  display: flex !important;
  position: sticky !important;
  top: 0 !important;
  z-index: 5000 !important;

  width: 100% !important;
  flex-wrap: wrap !important;

  background: #000000 !important;
}

/* Order name + chat should never disappear */
.board-detail-overlay .clean-detail-order-name,
.board-detail-overlay .clean-detail-chat-button,
.board-detail-overlay .board-detail-back {
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
}

/* Pipeline remains visible in toolbar */
.board-detail-overlay .clean-detail-header .detail-pipeline-strip {
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
}

/* SUPER ADMIN STOP */
.working-user-avatar-stop {
  position: relative;
  cursor: pointer !important;
}

.working-stop-badge {
  position: absolute;
  right: -4px;
  bottom: -3px;

  width: 15px;
  height: 15px;

  display: grid;
  place-items: center;

  background: #ef4444;
  color: #ffffff;

  border: 2px solid #ffffff;
  border-radius: 50%;

  font-size: 6px;
  pointer-events: none;
}

.working-user-avatar-readonly {
  cursor: default !important;
}

/* Mobile/tablet: global header is shorter */
@media (max-width: 780px) {
  .board-detail-overlay {
    top: 74px !important;
    left: 0 !important;
    height: calc(100vh - 74px) !important;
  }

  .board-detail-overlay .board-detail-panel,
  .board-detail-overlay .clean-detail-panel {
    height: calc(100vh - 94px) !important;
    max-height: calc(100vh - 94px) !important;
  }

  .board-detail-overlay .clean-detail-header,
  .board-detail-overlay .detail-header {
    flex-wrap: wrap !important;
  }
}



/* =========================================================
   FINAL CLEAN DETAIL TOOLBAR + ACTIVE WORKER PROFILE
   ========================================================= */

/*
 * Header layout:
 * Back | Order | Working User | Chat
 * Pipeline gets its own clean full-width row.
 */
.board-detail-overlay .clean-detail-header {
  min-height: 132px !important;
  padding: 14px 16px !important;

  display: grid !important;
  grid-template-columns:
    auto
    minmax(190px, 1fr)
    minmax(170px, auto)
    auto !important;
  grid-template-rows: 50px auto !important;

  align-items: center !important;
  gap: 10px !important;

  background: #050505 !important;
  border-radius: 0 !important;
  border-bottom: 1px solid #202020 !important;

  overflow: visible !important;
}

/* Back button is now part of normal grid; no absolute positioning. */
.board-detail-overlay .clean-detail-header .board-detail-back {
  position: static !important;
  top: auto !important;
  left: auto !important;

  height: 40px !important;
  padding: 0 13px !important;

  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 7px !important;

  background: #111111 !important;
  color: #ffffff !important;

  border: 1px solid #343434 !important;
  border-radius: 9px !important;

  white-space: nowrap !important;
}

/* Order card */
.board-detail-overlay .clean-detail-order-name {
  width: 100% !important;
  min-width: 0 !important;
  height: 46px !important;

  padding: 0 12px !important;

  background: #111111 !important;
  border: 1px solid #343434 !important;
  border-radius: 9px !important;
}

.board-detail-overlay .clean-detail-order-name > i {
  color: #ffffff !important;
}

.board-detail-overlay .clean-detail-order-name small {
  color: #9ca3af !important;
}

.board-detail-overlay .clean-detail-order-name strong {
  color: #ffffff !important;
}

/* Active worker */
.detail-working-user {
  min-width: 170px;
  max-width: 250px;
  height: 46px;

  padding: 5px 7px;

  display: grid;
  grid-template-columns: 34px minmax(0, 1fr) auto;
  align-items: center;
  gap: 8px;

  background: #111111;
  border: 1px solid #343434;
  border-radius: 9px;

  color: #ffffff;
}

.detail-working-avatar {
  position: relative;

  width: 34px;
  height: 34px;

  display: grid;
  place-items: center;

  overflow: visible;

  background: #232323;
  color: #ffffff;

  border-radius: 50%;

  font-size: 10px;
  font-weight: 900;
}

.detail-working-avatar img {
  width: 34px;
  height: 34px;

  display: block;

  object-fit: cover;
  border-radius: 50%;

  border: 1px solid #ffffff;
}

.detail-working-live-dot {
  position: absolute;
  right: -1px;
  bottom: 0;

  width: 9px;
  height: 9px;

  background: #22c55e;

  border: 2px solid #111111;
  border-radius: 50%;
}

.detail-working-copy {
  min-width: 0;
}

.detail-working-copy small,
.detail-working-copy strong {
  display: block;

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.detail-working-copy small {
  color: #9ca3af;

  font-size: 7px;
  line-height: 1;
  font-weight: 800;

  text-transform: uppercase;
}

.detail-working-copy strong {
  margin-top: 4px;

  color: #ffffff;

  font-size: 10px;
  line-height: 1.1;
  font-weight: 900;
}

.detail-stop-work-button {
  width: 28px;
  height: 28px;

  padding: 0;

  display: grid;
  place-items: center;

  background: #fee2e2;
  color: #dc2626;

  border: 1px solid #fecaca;
  border-radius: 7px;

  cursor: pointer;
}

.detail-stop-work-button i {
  font-size: 8px;
}

/* Chat */
.board-detail-overlay .clean-detail-chat-button {
  position: static !important;

  height: 40px !important;
  min-height: 40px !important;

  padding: 0 13px !important;

  background: #111111 !important;
  color: #ffffff !important;

  border: 1px solid #343434 !important;
  border-radius: 9px !important;

  white-space: nowrap !important;
}

.board-detail-overlay .clean-detail-chat-button:hover,
.board-detail-overlay .clean-detail-chat-button.active {
  background: #ffffff !important;
  color: #111111 !important;
}

/* Pipeline = clean second row */
.board-detail-overlay .clean-detail-header .detail-pipeline-strip {
  grid-column: 1 / -1 !important;
  grid-row: 2 !important;

  width: 100% !important;
  min-width: 0 !important;
  min-height: 42px !important;

  margin: 0 !important;
  padding: 5px !important;

  display: flex !important;
  align-items: center !important;
  gap: 5px !important;

  overflow-x: auto !important;
  overflow-y: hidden !important;

  background: #0d0d0d !important;

  border: 1px solid #272727 !important;
  border-radius: 9px !important;

  scrollbar-width: thin;
}

.board-detail-overlay .detail-pipeline-label {
  flex: 0 0 auto !important;

  padding: 0 8px !important;

  color: #8f98a5 !important;

  font-size: 8px !important;
  font-weight: 900 !important;

  text-transform: uppercase !important;
}

.board-detail-overlay .detail-pipeline-step {
  flex: 0 0 auto !important;

  min-height: 30px !important;
  padding: 0 10px !important;

  border-radius: 7px !important;

  font-size: 8px !important;
  font-weight: 900 !important;

  white-space: nowrap !important;
}

/* =========================================================
   CLEAN INFO STRIP
   No overlap / no text on top of another field.
   ========================================================= */

.board-detail-overlay .detail-topbar-wrapper {
  position: relative !important;
  top: auto !important;
  z-index: 3000 !important;

  width: 100% !important;

  padding: 8px 10px !important;

  background: #f4f5f8 !important;

  overflow-x: auto !important;
  overflow-y: visible !important;
}

.board-detail-overlay .detail-topbar {
  width: 100% !important;
  min-width: 1120px !important;
  max-width: none !important;

  min-height: 58px !important;

  display: flex !important;
  flex-wrap: nowrap !important;
  align-items: stretch !important;

  background: #ffffff !important;

  border: 1px solid #dfe3e8 !important;
  border-radius: 10px !important;

  overflow: visible !important;
}

.board-detail-overlay .detail-info-item {
  position: relative !important;

  min-width: 145px !important;
  min-height: 56px !important;

  padding: 8px 12px !important;

  display: flex !important;
  flex-direction: column !important;
  align-items: flex-start !important;
  justify-content: center !important;
  gap: 4px !important;

  background: #ffffff !important;

  border-right: 1px solid #e5e7eb !important;

  overflow: visible !important;
}

.board-detail-overlay .detail-info-item:last-child {
  border-right: 0 !important;
}

.board-detail-overlay .detail-info-item .info-label {
  display: block !important;

  margin: 0 !important;

  color: #98a2b3 !important;

  font-size: 7px !important;
  line-height: 1 !important;
  font-weight: 900 !important;

  text-transform: uppercase !important;
  white-space: nowrap !important;
}

.board-detail-overlay .detail-info-item .info-value,
.board-detail-overlay .detail-info-item .status-badge,
.board-detail-overlay .detail-info-item .trk-badge,
.board-detail-overlay .detail-info-item .payment-badge {
  position: relative !important;
  inset: auto !important;

  margin: 0 !important;

  max-width: 100% !important;

  line-height: 1.2 !important;
}

/* Specific widths so content never crashes into neighbors. */
.board-detail-overlay .detail-info-item:nth-child(1) {
  min-width: 175px !important;
}

.board-detail-overlay .detail-info-item:nth-child(2) {
  min-width: 145px !important;
}

.board-detail-overlay .detail-info-item:nth-child(3) {
  min-width: 190px !important;
}

.board-detail-overlay .detail-info-item:nth-child(4) {
  min-width: 145px !important;
}

.board-detail-overlay .invoice-info-item {
  min-width: 150px !important;
}

.board-detail-overlay .tracking-info-item {
  min-width: 205px !important;
}

/* Payment gets enough room for all 3 chips. */
.board-detail-overlay .detail-info-item:has(.payment-summary-badge) {
  min-width: 265px !important;
}

.board-detail-overlay .payment-summary-badge {
  width: auto !important;

  display: flex !important;
  align-items: center !important;
  gap: 5px !important;

  flex-wrap: nowrap !important;
}

.board-detail-overlay .payment-chip {
  flex: 0 0 auto !important;
  white-space: nowrap !important;
}

/* Dropdowns always sit above info strip. */
.board-detail-overlay .date-dropdown,
.board-detail-overlay .status-dropdown,
.board-detail-overlay .tracking-dropdown,
.board-detail-overlay .payment-dropdown {
  z-index: 20000 !important;
}

/* Detail body starts cleanly after info strip. */
.board-detail-overlay .detail-body {
  padding-top: 12px !important;
}

/* ---------------------------------------------------------
   TABLE ROW: another worker already claimed it
   -> show worker avatar; don't show Play again.
   --------------------------------------------------------- */
.order-working-actions .working-user-avatar-only img {
  object-fit: cover !important;
}

/* Responsive */
@media (max-width: 980px) {
  .board-detail-overlay .clean-detail-header {
    grid-template-columns:
      auto
      minmax(160px, 1fr)
      auto !important;
    grid-template-rows: 46px 46px auto !important;
  }

  .detail-working-user {
    grid-column: 2 / 3 !important;
    grid-row: 2 !important;

    max-width: none !important;
  }

  .board-detail-overlay .clean-detail-chat-button {
    grid-column: 3 !important;
    grid-row: 1 !important;
  }

  .board-detail-overlay .clean-detail-header .detail-pipeline-strip {
    grid-row: 3 !important;
  }
}

@media (max-width: 650px) {
  .board-detail-overlay .clean-detail-header {
    display: flex !important;
    flex-wrap: wrap !important;
  }

  .board-detail-overlay .clean-detail-header .board-detail-back,
  .board-detail-overlay .clean-detail-chat-button {
    flex: 0 0 auto !important;
  }

  .board-detail-overlay .clean-detail-order-name,
  .detail-working-user {
    flex: 1 1 180px !important;
  }

  .board-detail-overlay .clean-detail-header .detail-pipeline-strip {
    flex: 1 0 100% !important;
  }
}



/* =========================================================
   FINAL CLEANUP: NO PIPELINE + STICKY ACTIVE SECTION
   ========================================================= */

/* Detail toolbar: Back | Order | Working User | Chat */
.board-detail-overlay .clean-detail-header {
  min-height: 74px !important;
  height: auto !important;
  padding: 12px 14px !important;

  display: grid !important;
  grid-template-columns:
    auto
    minmax(220px, 1fr)
    minmax(170px, auto)
    auto !important;
  grid-template-rows: 48px !important;

  align-items: center !important;
  gap: 10px !important;

  background: #050505 !important;
  overflow: visible !important;
}

/* Pipeline removed from detail view */
.board-detail-overlay .detail-pipeline-strip,
.board-detail-overlay .detail-pipeline-label,
.board-detail-overlay .detail-pipeline-step {
  display: none !important;
}

/* Clean info bar directly below toolbar */
.board-detail-overlay .detail-topbar-wrapper {
  position: relative !important;
  top: auto !important;
  margin-top: 0 !important;
  padding: 8px 10px !important;
  transform: none !important;

  width: 100% !important;

  background: #f4f5f8 !important;

  overflow-x: auto !important;
  overflow-y: visible !important;

  z-index: 2500 !important;
}

.board-detail-overlay .detail-topbar,
.board-detail-overlay .table-border {
  position: relative !important;
  top: auto !important;
  margin-top: 0 !important;
  transform: none !important;
}

/* =========================================================
   FACTORY LIST STICKY HEADERS
   ========================================================= */

/* IN PRODUCTION / active status title stays visible while page scrolls */
.factory-board .board-section-heading.collapsible-active-heading {
  position: sticky !important;
  top: 0 !important;
  z-index: 8500 !important;

  background: #ffffff !important;

  box-shadow:
    0 2px 10px rgba(15, 23, 42, .08) !important;
}

/* Black table header stays immediately under IN PRODUCTION title */
.factory-board .board-table-head {
  position: sticky !important;
  top: 58px !important;
  z-index: 8400 !important;

  background: #000000 !important;
}

/* Do not clip sticky children */
.factory-board,
.factory-board .board-table-shell {
  overflow: visible !important;
}

/* Rows remain behind sticky headers */
.factory-board .board-table-row {
  position: relative;
  z-index: 1;
}

@media (max-width: 780px) {
  .board-detail-overlay .clean-detail-header {
    display: flex !important;
    flex-wrap: wrap !important;
    min-height: auto !important;
  }

  .board-detail-overlay .clean-detail-order-name,
  .detail-working-user {
    flex: 1 1 180px !important;
  }

  .factory-board .board-section-heading.collapsible-active-heading {
    top: 0 !important;
  }

  .factory-board .board-table-head {
    top: 54px !important;
  }
}



/* =========================================================
   FINAL STICKY ORDER + DETAIL Z-INDEX FIX
   ========================================================= */

/*
  DESKTOP SCROLL ORDER:
  1. Workflow / reminder toolbar
  2. Active section heading
  3. Black table columns
  4. Order rows scroll underneath
*/
.factory-board-page .board-toolbar {
  position: sticky !important;
  top: 0 !important;
  z-index: 12000 !important;

  min-height: 62px !important;

  background: #f4f5f8 !important;

  padding-top: 6px !important;
  padding-bottom: 6px !important;

  border-bottom: 1px solid #e5e7eb !important;

  box-shadow:
    0 4px 12px rgba(15, 23, 42, .06) !important;
}

/* IN PRODUCTION / active section heading */
.factory-board-page .factory-board
.board-section-heading.collapsible-active-heading {
  position: sticky !important;

  top: 62px !important;

  z-index: 11000 !important;

  background: #ffffff !important;

  box-shadow:
    0 2px 8px rgba(15, 23, 42, .05) !important;
}

/* BLACK TABLE HEADER */
.factory-board-page .factory-board .board-table-head {
  position: sticky !important;

  top: 120px !important;

  z-index: 10000 !important;

  background: #000000 !important;

  box-shadow:
    0 2px 8px rgba(0, 0, 0, .14) !important;
}

/* Rows always remain below sticky areas */
.factory-board-page .board-table-row {
  position: relative !important;
  z-index: 1 !important;
}

/* Parents must not clip sticky elements */
.factory-board-page .factory-board,
.factory-board-page .board-table-shell {
  overflow: visible !important;
}

/* =========================================================
   ORDER DETAIL MUST ALWAYS BE ABOVE THE FACTORY LIST
   ========================================================= */

.factory-board-page .board-detail-overlay {
  z-index: 50000 !important;
}

/* Detail panel itself */
.factory-board-page .board-detail-panel,
.factory-board-page .clean-detail-panel {
  position: relative !important;
  z-index: 50001 !important;

  overflow: auto !important;
}

/*
  Detail top black toolbar stays fixed inside the open order.
  Everything in the order scrolls underneath it.
*/
.factory-board-page .board-detail-overlay .clean-detail-header,
.factory-board-page .board-detail-overlay .detail-header {
  position: sticky !important;

  top: 0 !important;

  z-index: 52000 !important;

  background: #050505 !important;
}

/*
  Info bar sits below detail toolbar.
  Do NOT let it jump above toolbar.
*/
.factory-board-page .board-detail-overlay .detail-topbar-wrapper {
  position: relative !important;

  top: auto !important;

  z-index: 51000 !important;

  margin-top: 0 !important;

  overflow: visible !important;

  background: #f4f5f8 !important;
}

.factory-board-page .board-detail-overlay .detail-topbar {
  position: relative !important;

  top: auto !important;

  z-index: 51001 !important;

  overflow: visible !important;
}

/* =========================================================
   ALL DETAIL DROPDOWNS OPEN ABOVE CONTENT
   ========================================================= */

.factory-board-page .date-dropdown,
.factory-board-page .status-dropdown,
.factory-board-page .tracking-dropdown,
.factory-board-page .payment-dropdown,
.factory-board-page .tracking-dropdown-wide,
.factory-board-page .payment-dropdown-wide {
  position: absolute !important;

  z-index: 90000 !important;
}

/* Their parent cells must not clip dropdowns */
.factory-board-page .detail-info-item,
.factory-board-page .tracking-info-item,
.factory-board-page .invoice-info-item {
  overflow: visible !important;
}

/* Row Status dropdown always above sticky board headers */
.factory-board-page .status-fixed-dropdown,
.factory-board-page .monday-status-menu {
  z-index: 95000 !important;
}

/* Notification/profile dropdowns still remain highest */
.factory-board-page .chat-notification-dropdown,
.factory-board-page .notification-center-dropdown {
  z-index: 100000 !important;
}

/* =========================================================
   WHEN DETAIL IS OPEN:
   LIST STICKY HEADERS MUST NEVER APPEAR OVER IT
   ========================================================= */

/*
  Modern Chrome supports :has().
  This removes sticky stacking competition while detail is open.
*/
.factory-board-page:has(.board-detail-overlay)
.board-toolbar,

.factory-board-page:has(.board-detail-overlay)
.factory-board
.board-section-heading.collapsible-active-heading,

.factory-board-page:has(.board-detail-overlay)
.factory-board
.board-table-head {
  z-index: 10 !important;
}

/* Detail remains well above them */
.factory-board-page:has(.board-detail-overlay)
.board-detail-overlay {
  z-index: 50000 !important;
}

/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 780px) {
  .factory-board-page .board-toolbar {
    top: 0 !important;
    min-height: 56px !important;
  }

  .factory-board-page .factory-board
  .board-section-heading.collapsible-active-heading {
    top: 56px !important;
  }

  .factory-board-page .factory-board .board-table-head {
    top: 108px !important;
  }

  .factory-board-page .board-detail-overlay {
    z-index: 50000 !important;
  }

  .factory-board-page .board-detail-overlay .clean-detail-header,
  .factory-board-page .board-detail-overlay .detail-header {
    top: 0 !important;
  }
}



/* =========================================================
   FINAL: ONLY ORDER ROWS SCROLL
   HEADER / TOOLBAR / SECTION TITLE STAY IN PLACE
   ========================================================= */

/*
 * Stop the whole Factory Orders page from scrolling.
 * The order table shell gets its own internal vertical scroll.
 */
.factory-board-page {
  height: 100vh !important;
  max-height: 100vh !important;

  display: flex !important;
  flex-direction: column !important;

  overflow: hidden !important;
}

/* Shared PageHeader stays in normal flow and NEVER scrolls away. */
.factory-board-page > .page-header,
.factory-board-page .page-header {
  position: relative !important;
  top: auto !important;

  flex: 0 0 auto !important;

  z-index: 30000 !important;

  transform: none !important;
}

/* Top workflow / reminder toolbar stays exactly where it is. */
.factory-board-page .board-toolbar {
  position: relative !important;
  top: auto !important;

  flex: 0 0 auto !important;

  z-index: 20000 !important;

  margin: 0 !important;

  background: #f4f5f8 !important;

  box-shadow: none !important;
}

/*
 * Factory board occupies the remaining screen height.
 * It does NOT scroll as a whole.
 */
.factory-board-page .factory-board {
  flex: 1 1 auto !important;
  min-height: 0 !important;

  display: flex !important;
  flex-direction: column !important;

  overflow: hidden !important;
}

/* IN PRODUCTION title remains fixed in the board flow. */
.factory-board-page .factory-board
.board-section-heading.collapsible-active-heading {
  position: relative !important;
  top: auto !important;

  flex: 0 0 auto !important;

  z-index: 15000 !important;

  margin: 0 !important;

  background: #ffffff !important;

  box-shadow: none !important;
}

/* Bulk toolbar, when visible, also stays fixed. */
.factory-board-page .board-bulk-toolbar {
  position: relative !important;
  top: auto !important;

  flex: 0 0 auto !important;

  z-index: 14500 !important;
}

/*
 * THIS is the only vertically scrollable area.
 * It contains the black header + order rows.
 */
.factory-board-page .board-table-shell {
  flex: 1 1 auto !important;
  min-height: 0 !important;

  position: relative !important;

  overflow-x: auto !important;
  overflow-y: auto !important;

  overscroll-behavior: contain !important;

  scrollbar-gutter: stable;
}

/*
 * Black column heading stays at the top INSIDE the order scroll area.
 * Only rows pass underneath it.
 */
.factory-board-page .board-table-shell .board-table-head {
  position: sticky !important;
  top: 0 !important;

  z-index: 14000 !important;

  background: #000000 !important;

  box-shadow:
    0 2px 8px rgba(0, 0, 0, .12) !important;
}

/* Inline Add row stays below black table header. */
.factory-board-page .board-inline-add-row {
  position: relative !important;
  z-index: 5 !important;
}

/* Only order rows move. */
.factory-board-page .board-table-row {
  position: relative !important;
  z-index: 1 !important;
}

/* Collapsed status sections stay in their place, outside row scrolling. */
.factory-board-page .collapsed-status-bars {
  flex: 0 0 auto !important;

  position: relative !important;

  z-index: 12000 !important;

  background: #f4f5f8 !important;
}

/* =========================================================
   IMPORTANT: REMOVE OLD PAGE STICKY OFFSETS
   ========================================================= */

.factory-board-page .board-toolbar,
.factory-board-page .factory-board
.board-section-heading.collapsible-active-heading,
.factory-board-page .factory-board .board-table-head {
  transform: none !important;
}

/* =========================================================
   ORDER DETAIL
   ========================================================= */

/*
 * Detail remains above the board.
 * Opening an order will not make the list headers jump over it.
 */
.factory-board-page .board-detail-overlay {
  z-index: 60000 !important;
}

.factory-board-page .board-detail-overlay .board-detail-panel,
.factory-board-page .board-detail-overlay .clean-detail-panel {
  z-index: 60001 !important;
}

.factory-board-page .board-detail-overlay .clean-detail-header,
.factory-board-page .board-detail-overlay .detail-header {
  z-index: 62000 !important;
}

/* Detail dropdowns always above the detail content. */
.factory-board-page .date-dropdown,
.factory-board-page .status-dropdown,
.factory-board-page .tracking-dropdown,
.factory-board-page .payment-dropdown,
.factory-board-page .tracking-dropdown-wide,
.factory-board-page .payment-dropdown-wide {
  z-index: 90000 !important;
}

/* Row status popup remains above table/header. */
.factory-board-page .status-fixed-dropdown,
.factory-board-page .monday-status-menu {
  z-index: 95000 !important;
}

/* Notification dropdown remains highest. */
.factory-board-page .chat-notification-dropdown,
.factory-board-page .notification-center-dropdown {
  z-index: 100000 !important;
}

/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 780px) {
  .factory-board-page {
    height: 100dvh !important;
    max-height: 100dvh !important;
  }

  .factory-board-page .board-table-shell {
    -webkit-overflow-scrolling: touch;
  }
}



/* =========================================================
   FULL HEIGHT ORDER LIST
   Collapsed sections live INSIDE the scrolling order area.
   ========================================================= */

/* Board table takes all remaining vertical space. */
.factory-board-page .board-table-shell {
  flex: 1 1 0 !important;
  min-height: 0 !important;
  height: auto !important;
  max-height: none !important;

  overflow-y: auto !important;
  overflow-x: auto !important;
}

/*
 * Completed / Shipped / Delivered / custom collapsed bars
 * now appear AFTER the order rows inside the same scroll area.
 * They do NOT reduce the visible height of the orders.
 */
.factory-board-page .board-table-shell .collapsed-status-bars {
  position: relative !important;

  width: 100% !important;

  display: flex !important;
  flex-direction: column !important;

  flex: none !important;

  margin-top: 18px !important;
  padding: 0 0 18px !important;

  background: #f4f5f8 !important;

  z-index: 2 !important;
}

/* Undo old rule that reserved fixed-page space for collapsed bars. */
.factory-board-page > .factory-board > .collapsed-status-bars {
  display: none !important;
}

/* Rows use full available viewport below the fixed headers. */
.factory-board-page .factory-board {
  flex: 1 1 0 !important;
  min-height: 0 !important;
}

/* Black table heading stays at the top of the internal scroll area. */
.factory-board-page .board-table-shell .board-table-head {
  position: sticky !important;
  top: 0 !important;
  z-index: 14000 !important;
}

/* Keep scrollbar at full available height. */
.factory-board-page .board-table-shell {
  scrollbar-gutter: stable;
}



/* =========================================================
   FINAL SECTION POSITION FIX
   OPEN EACH SECTION WHERE IT BELONGS
   ========================================================= */

/*
 * collapsed-status-bars is only a wrapper.
 * display: contents means every collapsed section button becomes
 * a direct flex-order participant inside .factory-board.
 *
 * Existing boardSectionOrder(group.key, ...) values then keep:
 * In Production -> its place
 * Completed     -> its place
 * Shipped       -> its place
 * Delivered     -> its place
 * Custom groups -> their place
 *
 * So opening a section no longer forces it to the top.
 */
.factory-board-page .factory-board > .collapsed-status-bars {
  display: contents !important;

  height: auto !important;
  min-height: 0 !important;
  max-height: none !important;

  margin: 0 !important;
  padding: 0 !important;

  overflow: visible !important;
}

/* Each closed section participates in the existing boardSectionOrder(). */
.factory-board-page .factory-board > .collapsed-status-bars > .collapsed-status-bar {
  display: flex !important;
  width: 100% !important;
  flex: 0 0 auto !important;
}

/*
 * The active heading/table already use:
 * boardSectionOrder(activeGroup, 0/1/2)
 * so they now open exactly where that group belongs.
 */
.factory-board-page .board-section-heading.collapsible-active-heading,
.factory-board-page .board-bulk-toolbar,
.factory-board-page .board-table-shell {
  position: relative !important;
}

/*
 * IMPORTANT:
 * Override previous rules that hid direct-child collapsed sections.
 */
.factory-board-page > .factory-board > .collapsed-status-bars,
.factory-board-page .factory-board > .collapsed-status-bars {
  display: contents !important;
}



/* =========================================================
   FINAL: ORDERS FULL SCREEN HEIGHT + SECTIONS AFTER IT
   ========================================================= */

/*
 * Do NOT let the collapsed sections squeeze the active order list.
 * The active list gets a full viewport-height block.
 * Other sections stay after it, in their own natural order.
 */
.factory-board-page .factory-board {
  display: flex !important;
  flex-direction: column !important;

  overflow: visible !important;
}

/* Active section heading keeps its normal height. */
.factory-board-page .board-section-heading.collapsible-active-heading {
  flex: 0 0 auto !important;
}

/*
 * Main active order table gets the whole remaining screen height.
 * This is the important change.
 */
.factory-board-page .board-table-shell {
  flex: 0 0 calc(100vh - 370px) !important;

  min-height: calc(100vh - 370px) !important;
  height: calc(100vh - 370px) !important;
  max-height: calc(100vh - 370px) !important;

  overflow-y: auto !important;
  overflow-x: auto !important;

  margin-bottom: 18px !important;

  scrollbar-gutter: stable;
}

/* Black table header stays at top while rows scroll. */
.factory-board-page .board-table-shell .board-table-head {
  position: sticky !important;
  top: 0 !important;

  z-index: 14000 !important;

  background: #000000 !important;
}

/*
 * Other sections remain AFTER the full-height order area.
 * They no longer reduce the visible order height.
 */
.factory-board-page .factory-board > .collapsed-status-bars {
  display: contents !important;
}

.factory-board-page
.factory-board
> .collapsed-status-bars
> .collapsed-status-bar {
  flex: 0 0 auto !important;
  width: 100% !important;
}

/*
 * Preserve existing boardSectionOrder(...) positioning.
 * Opening a lower section still opens where that section belongs.
 */
.factory-board-page .board-section-heading.collapsible-active-heading,
.factory-board-page .board-bulk-toolbar,
.factory-board-page .board-table-shell,
.factory-board-page .collapsed-status-bar {
  position: relative !important;
}

/* Laptop / smaller desktop */
@media (max-height: 850px) and (min-width: 781px) {
  .factory-board-page .board-table-shell {
    flex-basis: calc(100vh - 350px) !important;
    min-height: calc(100vh - 350px) !important;
    height: calc(100vh - 350px) !important;
    max-height: calc(100vh - 350px) !important;
  }
}

/* Mobile */
@media (max-width: 780px) {
  .factory-board-page .board-table-shell {
    flex-basis: calc(100dvh - 255px) !important;
    min-height: calc(100dvh - 255px) !important;
    height: calc(100dvh - 255px) !important;
    max-height: calc(100dvh - 255px) !important;

    -webkit-overflow-scrolling: touch;
  }
}



/* =========================================================
   TRUE FINAL LAYOUT
   ORDERS FILL SCREEN; OTHER SECTIONS ONLY AFTER LAST ORDER
   ========================================================= */

/* Keep the application viewport fixed. */
.factory-board-page {
  height: 100vh !important;
  max-height: 100vh !important;

  display: flex !important;
  flex-direction: column !important;

  overflow: hidden !important;
}

/* Header and workflow tabs never participate in the board scroll. */
.factory-board-page .page-header,
.factory-board-page .board-toolbar {
  flex: 0 0 auto !important;
  position: relative !important;
  top: auto !important;
}

/*
 * The BOARD is the one scrolling region.
 * It fills every pixel left below PageHeader + workflow toolbar.
 */
.factory-board-page .factory-board {
  flex: 1 1 0 !important;
  min-height: 0 !important;
  height: auto !important;

  display: flex !important;
  flex-direction: column !important;

  overflow-y: auto !important;
  overflow-x: hidden !important;

  overscroll-behavior: contain !important;
  scrollbar-gutter: stable;
}

/* Active section title remains visible while its orders scroll. */
.factory-board-page
.factory-board
.board-section-heading.collapsible-active-heading {
  position: sticky !important;
  top: 0 !important;

  flex: 0 0 auto !important;

  z-index: 16000 !important;
  background: #ffffff !important;
}

/* Bulk actions sit below the active section title when present. */
.factory-board-page .board-bulk-toolbar {
  position: sticky !important;
  top: 58px !important;
  z-index: 15500 !important;
  flex: 0 0 auto !important;
}

/*
 * IMPORTANT:
 * Do NOT give the order table its own short scroll box.
 * It becomes normal content in .factory-board and is guaranteed
 * to occupy at least the rest of the visible screen.
 */
.factory-board-page .board-table-shell {
  flex: 0 0 auto !important;

  width: 100% !important;

  min-height: calc(100vh - 360px) !important;
  height: auto !important;
  max-height: none !important;

  overflow: visible !important;

  margin: 0 0 18px !important;
  padding: 0 !important;
}

/* Black columns remain visible underneath the active section heading. */
.factory-board-page .board-table-shell .board-table-head {
  position: sticky !important;
  top: 58px !important;

  z-index: 15000 !important;

  background: #000000 !important;
}

/* If bulk toolbar exists, black heading gets a little more offset. */
.factory-board-page:has(.board-bulk-toolbar)
.board-table-shell .board-table-head {
  top: 108px !important;
}

/* Rows are normal document rows, so they continue right to screen bottom. */
.factory-board-page .board-table-row {
  position: relative !important;
  z-index: 1 !important;
}

/*
 * Closed sections remain direct children and keep boardSectionOrder().
 * Because the active table is no longer height-limited by them,
 * they appear ONLY after the final order when you scroll down.
 */
.factory-board-page .factory-board > .collapsed-status-bars {
  display: contents !important;
}

.factory-board-page
.factory-board
> .collapsed-status-bars
> .collapsed-status-bar {
  flex: 0 0 auto !important;
  width: 100% !important;
}

/* Remove all previous fixed heights from the order shell. */
.factory-board-page .board-table-shell {
  flex-basis: auto !important;
}

/* Keep detail view above board when an order is opened. */
.factory-board-page .board-detail-overlay {
  z-index: 60000 !important;
}

/* Mobile */
@media (max-width: 780px) {
  .factory-board-page {
    height: 100dvh !important;
    max-height: 100dvh !important;
  }

  .factory-board-page .board-table-shell {
    min-height: calc(100dvh - 265px) !important;
  }

  .factory-board-page .board-table-shell .board-table-head {
    top: 54px !important;
  }
}



/* =========================================================
   DETAIL HEADER ORDER SEARCH
   Search any order while the order detail page is open.
========================================================= */
.detail-order-search {
  position: relative;
  width: clamp(220px, 24vw, 360px);
  min-width: 220px;
  flex: 0 1 360px;
  z-index: 70020;
}

.detail-order-search > input {
  width: 100%;
  height: 40px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: rgba(255, 255, 255, 0.09);
  color: #fff;
  border-radius: 8px;
  padding: 0 38px 0 38px;
  outline: none;
  font-size: 13px;
  font-weight: 600;
  transition: border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.detail-order-search > input::placeholder {
  color: rgba(255, 255, 255, 0.62);
}

.detail-order-search > input:focus {
  background: #fff;
  color: #111827;
  border-color: #fff;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.12);
}

.detail-order-search > input:focus::placeholder {
  color: #94a3b8;
}

.detail-order-search-icon {
  position: absolute;
  left: 13px;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255, 255, 255, 0.72);
  font-size: 13px;
  pointer-events: none;
  z-index: 2;
}

.detail-order-search:focus-within .detail-order-search-icon {
  color: #64748b;
}

.detail-order-search-clear {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  width: 27px;
  height: 27px;
  border: 0;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  display: grid;
  place-items: center;
  cursor: pointer;
  z-index: 3;
}

.detail-order-search:focus-within .detail-order-search-clear {
  background: #eef2f7;
  color: #475569;
}

.detail-order-search-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: min(430px, 86vw);
  max-height: 390px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.22);
  padding: 6px;
  z-index: 70030;
}

.detail-order-search-item {
  width: 100%;
  border: 0;
  background: transparent;
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
  padding: 10px;
  border-radius: 8px;
  cursor: pointer;
  color: #0f172a;
}

.detail-order-search-item:hover,
.detail-order-search-item.active {
  background: #f1f5f9;
}

.detail-search-folder {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  border-radius: 7px;
  background: #111827;
  color: #fff;
  display: grid;
  place-items: center;
}

.detail-search-copy {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.detail-search-copy strong {
  color: #0f172a;
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.detail-search-copy small {
  color: #64748b;
  font-size: 11px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.detail-search-arrow {
  color: #94a3b8;
  font-size: 11px;
}

.detail-order-search-empty {
  min-height: 90px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 8px;
  color: #94a3b8;
  font-size: 12px;
}

@media (max-width: 980px) {
  .detail-order-search {
    width: min(100%, 330px);
    min-width: 190px;
  }
}

@media (max-width: 700px) {
  .detail-order-search {
    order: 10;
    flex: 1 0 100%;
    width: 100%;
    max-width: none;
  }

  .detail-order-search-dropdown {
    width: 100%;
  }
}

/* =========================================================
   FINAL SECTION BAR BORDER COLOR
   Black lines -> soft gray
   KEEP THIS AT VERY END OF <style>
   ========================================================= */

/* CLOSED / COLLAPSED STATUS BARS */
.factory-board-page .factory-board .collapsed-status-bar {
  background: #ffffff !important;

  border-top: 1px solid #d1d5db !important;
  border-bottom: 1px solid #d1d5db !important;
  border-right: 0 !important;

  /* status color wali left line same rahe */
  border-left: 5px solid var(--group-color) !important;

  border-radius: 0 !important;
  box-shadow: none !important;

  min-height: 58px !important;
  width: 100% !important;

  padding-left: 38px !important;

  display: flex !important;
  align-items: center !important;
  justify-content: flex-start !important;
}


/* CLOSED BAR HOVER */
.factory-board-page .factory-board .collapsed-status-bar:hover {
  background: #fafafa !important;
}


/* ACTIVE / OPEN SECTION */
.factory-board-page
.factory-board
.board-section-heading.collapsible-active-heading {
  background: #ffffff !important;

  border-top: 1px solid #d1d5db !important;
  border-bottom: 1px solid #d1d5db !important;
  border-right: 0 !important;

  border-left: 5px solid var(--active-section-color) !important;

  border-radius: 0 !important;
  box-shadow: none !important;

  min-height: 58px !important;
}


/* ACTIVE SECTION HOVER */
.factory-board-page
.factory-board
.board-section-heading.collapsible-active-heading:hover {
  background: #fafafa !important;
}


/* REMOVE ANY OLD BLACK BORDER OVERRIDES */
.factory-board-page .collapsed-status-bars {
  border-top: 0 !important;
}


/* Keep title colors */
.factory-board-page .collapsed-status-bar strong {
  color: var(--group-color) !important;
}


/* Chevron same group color */
.factory-board-page .collapsed-status-bar .collapsed-status-icon {
  color: var(--group-color) !important;
}


/* Active section title / arrow same active color */
.factory-board-page
.collapsible-active-heading h1,
.factory-board-page
.collapsible-active-heading .section-collapse-icon {
  color: var(--active-section-color) !important;
}


/* OPTIONAL:
   If lines should be slightly lighter, use #e5e7eb instead of #d1d5db
*/






/* =========================================================
   BOARD SUPER ADMIN SETTINGS + CUSTOM DROPDOWN COLUMNS
   ========================================================= */
.header-board-admin-tools {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-right: 10px;
  white-space: nowrap;
}
.header-owner-toggle-label {
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .6px;
  color: #6b7280;
}
.header-owner-toggle {
  width: 38px;
  height: 20px;
  padding: 2px;
  border: 0;
  border-radius: 999px;
  background: #d1d5db;
  cursor: pointer;
  transition: .2s ease;
}
.header-owner-toggle span {
  display: block;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,.22);
  transition: transform .2s ease;
}
.header-owner-toggle.active { background: #00c875; }
.header-owner-toggle.active span { transform: translateX(18px); }
.header-column-settings-btn {
  width: 30px;
  height: 30px;
  display: inline-grid;
  place-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #111827;
  cursor: pointer;
}
.board-settings-overlay {
  position: fixed; inset: 0; z-index: 2100000000;
  background: rgba(15,23,42,.45);
  display: flex; align-items: center; justify-content: center;
  padding: 24px;
}
.board-settings-modal {
  width: min(760px, 96vw); max-height: 88vh; overflow: auto;
  background: #fff; border-radius: 16px; box-shadow: 0 25px 70px rgba(0,0,0,.28);
}
.board-settings-head {
  display:flex; align-items:flex-start; justify-content:space-between; gap:16px;
  padding:20px 22px; border-bottom:1px solid #e5e7eb;
}
.board-settings-head h3 { margin:0; font-size:20px; color:#111827; }
.board-settings-head p { margin:5px 0 0; font-size:12px; color:#6b7280; }
.board-settings-head > button, .custom-column-manager-head button, .custom-option-manage-row button {
  border:0; background:#f3f4f6; border-radius:7px; width:30px; height:30px; cursor:pointer;
}
.board-settings-section { padding:20px 22px; border-bottom:1px solid #eef0f3; }
.board-settings-section h4 { margin:0 0 12px; font-size:13px; color:#111827; text-transform:uppercase; letter-spacing:.5px; }
.board-column-toggle-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
.board-column-toggle-grid label { display:flex; align-items:center; gap:8px; padding:10px; border:1px solid #e5e7eb; border-radius:9px; font-size:12px; font-weight:700; color:#374151; }
.column-position-help { margin:-6px 0 12px; color:#64748b; font-size:11px; }
.board-column-position-list { display:grid; gap:7px; }
.board-column-position-row { display:grid; grid-template-columns:28px minmax(0,1fr) 68px 32px 32px; gap:7px; align-items:center; padding:8px 9px; border:1px solid #e5e7eb; border-radius:9px; background:#f8fafc; }
.board-column-position-number { width:24px; height:24px; display:grid; place-items:center; border-radius:7px; background:#111827; color:#fff; font-size:10px; font-weight:800; }
.board-column-position-row strong { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px; color:#111827; }
.board-column-position-row small { color:#94a3b8; font-size:9px; text-transform:uppercase; font-weight:800; }
.board-column-position-row button { width:30px; height:30px; border:0; border-radius:7px; background:#e5e7eb; color:#111827; cursor:pointer; }
.board-column-position-row button:disabled { opacity:.35; cursor:not-allowed; }
.custom-column-create { display:flex; gap:8px; margin-bottom:14px; }
.custom-column-create-with-type {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 190px auto;
}
.custom-column-type-select,
.custom-column-inline-type {
  border: 1px solid #d1d5db;
  border-radius: 9px;
  background: #fff;
  color: #111827;
  font-weight: 700;
  outline: none;
}
.custom-column-type-select {
  min-height: 40px;
  padding: 0 10px;
}
.custom-field-type-help {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
  margin: -4px 0 14px;
  font-size: 11px;
  color: #64748b;
}
.custom-field-type-help span {
  padding: 8px 10px;
  background: #f8fafc;
  border-radius: 8px;
}
.custom-column-title-block {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}
.custom-column-inline-type {
  padding: 5px 8px;
  font-size: 10px;
}
.custom-non-dropdown-help {
  margin-top: 10px;
  padding: 11px;
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px dashed #cbd5e1;
  border-radius: 9px;
  color: #64748b;
  font-size: 11px;
}
.custom-column-create input { flex:1; min-width:0; border:1px solid #d1d5db; border-radius:9px; padding:10px 12px; }
.custom-column-create button, .custom-option-add-btn, .custom-field-quick-add button { border:0; border-radius:9px; background:#111827; color:#fff; padding:9px 12px; font-weight:700; cursor:pointer; }
.custom-column-manager { border:1px solid #e5e7eb; border-radius:12px; padding:12px; margin-top:10px; }
.custom-column-manager-head { display:flex; justify-content:space-between; align-items:center; gap:10px; }
.custom-column-manager-head > div { display:flex; gap:6px; }
.custom-column-manager-head button:disabled { opacity:.35; cursor:not-allowed; }
.custom-column-manager-head .danger, .custom-option-manage-row .danger { color:#dc2626; }
.custom-option-list { margin-top:10px; display:grid; gap:6px; }
.custom-option-manage-row { display:grid; grid-template-columns:16px 1fr 30px 30px; gap:7px; align-items:center; background:#f8fafc; padding:7px 8px; border-radius:8px; font-size:12px; }
.custom-option-color { width:12px; height:12px; border-radius:50%; display:inline-block; }
.custom-option-color.clear { border:1px dashed #9ca3af; background:transparent; }
.custom-option-add-btn { margin-top:10px; background:#f3f4f6; color:#111827; }
.custom-option-inline-create {
  margin-top: 10px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 42px auto;
  gap: 8px;
  align-items: center;
}
.custom-option-inline-create > input[type="text"] {
  min-width: 0;
  height: 38px;
  padding: 0 11px;
  border: 1px solid #d1d5db;
  border-radius: 9px;
  outline: none;
}
.custom-option-inline-create > input[type="text"]:focus {
  border-color: #94a3b8;
  box-shadow: 0 0 0 3px rgba(148,163,184,.12);
}
.custom-option-inline-color {
  width: 42px;
  height: 38px;
  position: relative;
  display: grid;
  place-items: center;
  border: 1px solid #d1d5db;
  border-radius: 9px;
  cursor: pointer;
  overflow: hidden;
}
.custom-option-inline-color span {
  width: 22px;
  height: 22px;
  border-radius: 6px;
}
.custom-option-inline-color input[type="color"] {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}
.custom-option-inline-create .custom-option-add-btn {
  margin-top: 0;
  height: 38px;
  white-space: nowrap;
}
.custom-option-inline-create .custom-option-add-btn:disabled {
  opacity: .45;
  cursor: not-allowed;
}
.custom-column-empty { padding:18px; text-align:center; color:#9ca3af; font-size:12px; }
.board-col-custom { min-width:0; }
.custom-field-trigger { width:100%; min-width:0; height:100%; min-height:46px; border:0; border-radius:0; padding:0 10px; background:#fff; color:#374151; display:flex; align-items:center; justify-content:space-between; gap:6px; font-size:11px; font-weight:800; cursor:pointer; transition:.15s ease; }
.custom-field-trigger span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.custom-field-trigger-filled:not(.empty) { box-shadow: inset 0 0 0 1px rgba(0,0,0,.05); }
.custom-field-trigger.empty { color:#9ca3af; font-weight:600; background:#fff; border:1px solid #e5e7eb; margin:5px; width:calc(100% - 10px); min-height:34px; height:34px; border-radius:7px; }
.custom-field-text-input,
.custom-field-notes-input {
  width: 100%;
  min-width: 0;
  min-height: 46px;
  border: 0;
  outline: 0;
  background: transparent;
  color: #111827;
  padding: 8px 9px;
  font: inherit;
  font-size: 11px;
}
.custom-field-notes-input {
  resize: none;
  white-space: pre-wrap;
  line-height: 1.35;
}
.custom-field-text-input:focus,
.custom-field-notes-input:focus {
  background: #fff;
  box-shadow: inset 0 0 0 2px #cbd5e1;
}
.custom-field-fixed-dropdown { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 18px 50px rgba(0,0,0,.2); overflow:hidden; max-height:360px; overflow-y:auto; }
.custom-field-dropdown-head { position:sticky; top:0; z-index:2; display:flex; align-items:center; justify-content:space-between; padding:11px 12px; background:#f8fafc; border-bottom:1px solid #e5e7eb; }
.custom-field-dropdown-head button { border:0; background:transparent; cursor:pointer; }
.custom-field-option-row { width:100%; border:0; border-bottom:1px solid #f3f4f6; background:#fff; padding:10px 12px; display:grid; grid-template-columns:16px 1fr 18px; gap:8px; align-items:center; text-align:left; cursor:pointer; font-size:12px; }
.custom-field-option-row:hover { background:#f8fafc; }

.custom-field-option-manage-select-row {
  min-height: 44px;
  display: grid;
  grid-template-columns: 26px minmax(0, 1fr) auto;
  gap: 6px;
  align-items: center;
  padding: 5px 7px;
  background: #fff;
  border-bottom: 1px solid #f1f5f9;
}

.custom-field-option-manage-select-row:hover {
  background: #f8fafc;
}

.custom-field-color-picker {
  width: 26px;
  height: 30px;
  display: grid;
  place-items: center;
  position: relative;
  cursor: pointer;
  border-radius: 6px;
}

.custom-field-color-picker:hover {
  background: #eef2f7;
}

.custom-field-color-picker .custom-option-color {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  box-shadow:
    0 0 0 2px #fff,
    0 0 0 3px #d1d5db;
}

.custom-field-color-picker input[type="color"] {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.custom-field-option-main {
  min-width: 0;
  min-height: 34px;
  border: 0;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  text-align: left;
  padding: 0 5px;
  color: #111827;
  font-size: 12px;
  cursor: pointer;
}

.custom-field-option-main > span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.custom-field-option-actions {
  display: flex;
  align-items: center;
  gap: 3px;
}

.custom-field-option-actions button {
  width: 29px;
  height: 29px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #475569;
  cursor: pointer;
}

.custom-field-option-actions button:hover {
  background: #e2e8f0;
  color: #111827;
}

.custom-field-option-actions button.danger {
  color: #dc2626;
}

.custom-field-option-actions button.danger:hover {
  background: #fee2e2;
}

.custom-field-inline-edit {
  min-width: 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 30px 30px;
  gap: 4px;
  align-items: center;
}

.custom-field-inline-edit input {
  min-width: 0;
  height: 32px;
  border: 1px solid #94a3b8;
  border-radius: 6px;
  padding: 0 8px;
  outline: none;
  font-size: 12px;
}

.custom-field-inline-edit input:focus {
  border-color: #111827;
}

.custom-field-inline-edit button {
  width: 30px;
  height: 30px;
  border: 0;
  border-radius: 6px;
  cursor: pointer;
  background: #e2e8f0;
}

.custom-field-inline-edit button.save {
  background: #111827;
  color: #fff;
}

.custom-field-quick-create {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 7px;
  padding: 9px;
  background: #f8fafc;
  border-top: 1px solid #e5e7eb;
}

.custom-field-quick-create input {
  min-width: 0;
  height: 34px;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  padding: 0 9px;
  outline: none;
  font-size: 12px;
}

.custom-field-quick-create input:focus {
  border-color: #111827;
}

.custom-field-quick-create button {
  height: 34px;
  border: 0;
  border-radius: 7px;
  padding: 0 11px;
  background: #111827;
  color: #fff;
  font-weight: 700;
  cursor: pointer;
}

.custom-field-quick-create button:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.custom-field-quick-hint {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 7px 9px 9px;
  background: #f8fafc;
  color: #64748b;
  font-size: 10px;
  line-height: 1.35;
}
.custom-field-quick-add { padding:10px; background:#f8fafc; }
.custom-field-quick-add button { width:100%; }
@media (max-width: 780px) {
  .board-column-toggle-grid { grid-template-columns:1fr 1fr; }
  .header-owner-toggle-label { display:none; }
  .custom-column-create-with-type {
    grid-template-columns: 1fr;
  }
  .custom-field-type-help {
    grid-template-columns: 1fr;
  }
}

/* =========================================================
   FINAL HEADER + FULL-CELL TEXT FIELDS + COPYABLE TOOLTIP
   ========================================================= */

/* Every heading stays on one exact horizontal level. */
.factory-board-page .board-table-head {
  min-height: 68px !important;
  height: 68px !important;
  align-items: stretch !important;
}

.factory-board-page .board-table-head > .board-col {
  height: 68px !important;
  min-height: 68px !important;
  margin: 0 !important;
  padding: 0 12px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  align-self: stretch !important;
  color: #ffffff !important;
  font-size: 13px !important;
  font-weight: 900 !important;
  line-height: 1 !important;
  letter-spacing: .25px !important;
  text-transform: uppercase !important;
  white-space: nowrap !important;
}

.factory-board-page .board-table-head > .board-col-name {
  justify-content: flex-start !important;
}

.factory-board-page .board-table-head > .board-col-check,
.factory-board-page .board-table-head > .board-col-info {
  padding: 0 !important;
}

/* Text is written directly inside the complete column box. */
.factory-board-page .row-custom-cell,
.factory-board-page .board-col-packing,
.factory-board-page .board-col-notes,
.factory-board-page .board-col-address,
.factory-board-page .board-col-track {
  position: relative !important;
  overflow: visible !important;
  padding: 0 !important;
}

.factory-board-page .custom-field-text-input,
.factory-board-page .custom-field-notes-input,
.factory-board-page .packing-clean-input,
.factory-board-page .board-notes-inline-input,
.factory-board-page .board-col-address > .board-inline-cell-input,
.factory-board-page .board-col-track > .board-inline-cell-input {
  width: 100% !important;
  height: 100% !important;
  min-height: 54px !important;
  margin: 0 !important;
  padding: 10px 14px !important;
  border: 0 !important;
  border-radius: 0 !important;
  outline: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
  color: #111827 !important;
  font-size: 12px !important;
  font-weight: 700 !important;
  line-height: 1.35 !important;
}

.factory-board-page .custom-field-notes-input,
.factory-board-page .board-notes-inline-input {
  resize: none !important;
  overflow: auto !important;
}

.factory-board-page .custom-field-text-input:focus,
.factory-board-page .custom-field-notes-input:focus,
.factory-board-page .packing-clean-input:focus,
.factory-board-page .board-notes-inline-input:focus,
.factory-board-page .board-col-address > .board-inline-cell-input:focus,
.factory-board-page .board-col-track > .board-inline-cell-input:focus {
  border: 0 !important;
  outline: 0 !important;
  box-shadow: inset 0 0 0 2px #2563eb !important;
  background: #ffffff !important;
}

.factory-board-page .custom-field-text-input::placeholder,
.factory-board-page .custom-field-notes-input::placeholder,
.factory-board-page .packing-clean-input::placeholder,
.factory-board-page .board-notes-inline-input::placeholder,
.factory-board-page .board-inline-cell-input::placeholder {
  color: #94a3b8 !important;
  font-weight: 600 !important;
}

/* Full text on hover; user can select and copy it. */
.copyable-cell-tooltip {
  position: absolute;
  left: 8px;
  top: calc(100% - 3px);
  z-index: 2147483000;
  display: none;
  width: max-content;
  min-width: 150px;
  max-width: 340px;
  max-height: 180px;
  overflow: auto;
  padding: 10px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #111827;
  color: #ffffff;
  box-shadow: 0 12px 30px rgba(15, 23, 42, .28);
  font-size: 12px;
  font-weight: 700;
  line-height: 1.45;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  text-align: left;
  cursor: text;
  user-select: text;
  -webkit-user-select: text;
  pointer-events: auto;
}

.factory-board-page .row-custom-cell:hover > .copyable-cell-tooltip,
.factory-board-page .board-col-packing:hover > .copyable-cell-tooltip,
.factory-board-page .board-col-notes:hover > .copyable-cell-tooltip,
.factory-board-page .board-col-address:hover > .copyable-cell-tooltip,
.factory-board-page .board-col-track:hover > .copyable-cell-tooltip,
.copyable-cell-tooltip:hover {
  display: block;
}

.factory-board-page.theme-dark .copyable-cell-tooltip {
  border-color: #475569;
  background: #f8fafc;
  color: #0f172a;
}

/* The old in-cell popup can be clipped by table overflow; use fixed popup. */
.copyable-cell-tooltip {
  display: none !important;
}

.global-copyable-tooltip {
  max-height: 190px;
  overflow: auto;
  padding: 13px 15px;
  border: 1px solid #3f4652;
  border-radius: 7px;
  background: #303238;
  color: #ffffff;
  box-shadow: 0 14px 34px rgba(15, 23, 42, .32);
  font-size: 13px;
  font-weight: 600;
  line-height: 1.48;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  text-align: left;
  cursor: text;
  user-select: text;
  -webkit-user-select: text;
  pointer-events: auto;
}

.global-copyable-tooltip::before {
  content: '';
  position: absolute;
  top: -6px;
  left: 18px;
  width: 11px;
  height: 11px;
  border-left: 1px solid #3f4652;
  border-top: 1px solid #3f4652;
  background: #303238;
  transform: rotate(45deg);
}

/* Teleported notification layer: always above the open order detail page. */
.factory-notification-portal {
  position: fixed !important;
  top: 78px !important;
  right: 24px !important;
  left: auto !important;
  z-index: 2147483647 !important;
  width: min(390px, calc(100vw - 32px)) !important;
  max-height: min(460px, calc(100vh - 100px)) !important;
  margin: 0 !important;
  overflow: hidden !important;
}

.factory-notification-portal .notification-list {
  max-height: min(340px, calc(100vh - 220px)) !important;
  overflow-y: auto !important;
}

@media (max-width: 780px) {
  .factory-notification-portal {
    top: 66px !important;
    right: 12px !important;
    width: calc(100vw - 24px) !important;
  }
}

.status-ref-trigger:disabled {
  cursor: default !important;
  opacity: 1 !important;
}

/* Requested client filter, upload feedback and readable PO styling. */
.client-filter-trigger {
  min-height: 40px;
  padding: 0 16px;
  border: 2px solid #0f172a;
  border-radius: 999px;
  background: #fff;
  color: #0f172a;
  display: inline-flex;
  align-items: center;
  gap: 9px;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
}

.client-filter-count {
  min-width: 20px;
  height: 20px;
  border-radius: 999px;
  background: #6161ff;
  color: #fff;
  display: grid;
  place-items: center;
  font-size: 11px;
}

.client-filter-overlay {
  position: fixed;
  inset: 0;
  z-index: 2147483647;
  background: rgba(15, 23, 42, .36);
}

.client-filter-drawer {
  position: absolute;
  top: 0;
  right: 0;
  width: min(430px, 94vw);
  height: 100%;
  padding: 24px;
  background: #fff;
  box-shadow: -22px 0 50px rgba(15, 23, 42, .22);
  display: flex;
  flex-direction: column;
}

.client-filter-header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
.client-filter-header h2 { margin:0; color:#0f172a; font-size:23px; }
.client-filter-header p { margin:5px 0 0; color:#64748b; font-size:13px; }
.client-filter-close { width:38px; height:38px; border:0; border-radius:10px; background:#f1f5f9; cursor:pointer; }
.client-filter-search { position:relative; margin:22px 0 14px; }
.client-filter-search i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#64748b; }
.client-filter-search input { width:100%; height:46px; padding:0 14px 0 42px; border:1px solid #cbd5e1; border-radius:12px; outline:none; }
.client-filter-search input:focus { border-color:#6161ff; box-shadow:0 0 0 3px rgba(97,97,255,.13); }
.client-filter-list { overflow-y:auto; min-height:0; display:flex; flex-direction:column; gap:8px; padding-right:3px; }
.client-filter-item { width:100%; padding:11px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; display:grid; grid-template-columns:40px 1fr auto; align-items:center; gap:11px; text-align:left; cursor:pointer; }
.client-filter-item:hover,.client-filter-item.active { border-color:#6161ff; background:#f5f5ff; }
.client-filter-item > span:nth-child(2) { min-width:0; display:flex; flex-direction:column; }
.client-filter-item strong,.client-filter-item small { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.client-filter-item small { margin-top:3px; color:#64748b; }
.client-filter-avatar { width:40px; height:40px; border-radius:50%; background:#111827; color:#fff; display:grid !important; place-items:center; font-weight:900; }
.client-filter-empty { padding:35px 10px; text-align:center; color:#64748b; }
.client-filter-slide-enter-active,.client-filter-slide-leave-active { transition:opacity .22s ease; }
.client-filter-slide-enter-active .client-filter-drawer,.client-filter-slide-leave-active .client-filter-drawer { transition:transform .22s ease; }
.client-filter-slide-enter-from,.client-filter-slide-leave-to { opacity:0; }
.client-filter-slide-enter-from .client-filter-drawer,.client-filter-slide-leave-to .client-filter-drawer { transform:translateX(100%); }

.inline-upload-progress,.card-upload-progress { display:inline-flex; align-items:center; gap:6px; color:#4f46e5; font-size:11px; font-weight:900; white-space:nowrap; }
.card-upload-progress { position:absolute; left:50%; bottom:12px; transform:translateX(-50%); padding:7px 11px; border-radius:999px; background:#fff; box-shadow:0 5px 18px rgba(15,23,42,.16); }
.factory-board-page .board-col-name .name-value > small { font-size:11px !important; line-height:1.35 !important; font-weight:700 !important; }
.board-col-payment select:disabled { opacity:1; color:#111827; cursor:not-allowed; background:#f8fafc; }

/* Smooth inline editing: drafts stay visible until the API confirms the save. */
.factory-board-page .board-notes-inline-input,
.factory-board-page .board-col-address .board-inline-cell-input {
  transition: min-height .18s ease, box-shadow .18s ease, background .18s ease;
}

.factory-board-page .board-notes-inline-input:focus {
  min-height: 72px !important;
  padding: 9px 10px !important;
  background: #fff !important;
  box-shadow: inset 0 0 0 2px #2563eb !important;
  resize: vertical !important;
  overflow-y: auto !important;
}

.factory-board-page .board-col-address .board-inline-cell-input:focus {
  background: #fff !important;
  box-shadow: inset 0 0 0 2px #2563eb !important;
}

/* Last order opened by this browser. A normal page-side click clears it. */
.factory-board-page .board-table-row.last-opened-order {
  position: relative;
  z-index: 2;
  border-radius: 8px;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, .9), 0 10px 26px rgba(37, 99, 235, .24) !important;
}

.factory-board-page .board-table-row.last-opened-order::after {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: 10px;
  border: 3px solid #3157ff;
  box-shadow: 0 0 0 1px rgba(49, 87, 255, .16);
  pointer-events: none;
}

.client-filter-search input { padding-right: 48px !important; }
.client-filter-dropdown-toggle {
  position: absolute;
  top: 50%;
  right: 7px;
  width: 34px;
  height: 34px;
  transform: translateY(-50%);
  border: 0;
  border-radius: 9px;
  background: #eef2ff;
  color: #3730a3;
  display: grid;
  place-items: center;
  cursor: pointer;
}
.client-filter-dropdown-toggle i { position: static !important; transform: none !important; transition: transform .18s ease; }
.client-filter-dropdown-toggle.open i { transform: rotate(180deg) !important; }

/* Guaranteed visible last-opened marker across the whole grid row. */
.factory-board-page .board-table-row.last-opened-order {
  outline: 3px solid #3157ff !important;
  outline-offset: -2px !important;
  box-shadow: inset 5px 0 0 #3157ff, 0 0 0 2px rgba(49,87,255,.22), 0 8px 24px rgba(49,87,255,.22) !important;
}

.factory-board-page .board-table-row.last-opened-order > .board-col:first-child {
  border-left: 4px solid #3157ff !important;
}

.factory-board-page .board-table-row.last-opened-order > .board-col:last-child {
  border-right: 4px solid #3157ff !important;
}

.factory-board-page .custom-field-trigger:disabled {
  cursor: default;
  opacity: 1;
}

.board-inline-add-main .inline-fixed-select,
.board-inline-add-main .inline-priority-select {
  width: 145px;
  height: 40px;
  padding: 0 34px 0 11px;
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  background: #fff;
  color: #0f172a;
  font-size: 12px;
  font-weight: 800;
  outline: none;
}

.board-inline-add-main .inline-priority-select:focus {
  border-color: #3157ff;
  box-shadow: 0 0 0 3px rgba(49,87,255,.14);
}

.board-inline-add-main .inline-fixed-select:disabled {
  opacity: 1;
  color: #475569;
  background: #f1f5f9;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .board-inline-add-main {
    flex-wrap: wrap;
    padding-block: 10px;
  }
}

/* PO is secondary but must remain clearly readable. */
.factory-board-page .board-col-name .name-value > small {
  margin-top: 4px !important;
  color: #64748b !important;
  font-size: 12px !important;
  line-height: 1.35 !important;
  font-weight: 800 !important;
  letter-spacing: .01em !important;
}

/* Larger, cleaner assignment dialog with explicit removable selections. */
.member-select-modal {
  width: min(760px, calc(100vw - 32px)) !important;
  max-height: min(760px, calc(100vh - 42px)) !important;
  border-radius: 18px !important;
  overflow: hidden !important;
  box-shadow: 0 28px 80px rgba(15, 23, 42, .28) !important;
}

.member-multiselect .multiselect__content-wrapper {
  max-height: 260px !important;
}

.member-selected-preview {
  max-height: 170px !important;
  overflow-y: auto !important;
  align-content: flex-start;
}

.member-preview-chip {
  position: relative;
  padding-right: 34px !important;
}

.member-preview-remove {
  position: absolute;
  top: 50%;
  right: 7px;
  width: 24px;
  height: 24px;
  transform: translateY(-50%);
  border: 0;
  border-radius: 7px;
  background: #fee2e2;
  color: #dc2626;
  display: grid;
  place-items: center;
  cursor: pointer;
}

.member-preview-remove:hover { background:#dc2626; color:#fff; }

.client-payment-only {
  min-width: 125px;
  height: 34px;
  padding: 0 30px 0 11px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #f8fafc;
  color: #0f172a;
  font-size: 12px;
  font-weight: 800;
  opacity: 1;
}
</style>
