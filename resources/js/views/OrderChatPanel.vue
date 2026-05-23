<template>
  <div
    class="chat-panel"
    :class="{ 'mobile-fullscreen': isMobile }"
    :style="isMobile ? {} : { width: chatWidth + 'px', minWidth: chatWidth + 'px' }"
    @click="openMessageMenuKey = null"
  >
    <div v-if="!isMobile" class="chat-resize-bar" @mousedown.stop="startChatResize"></div>

    <div class="chat-header">
      <span class="chat-title">Team Inbox</span>
      <div class="chat-header-actions">
        <button class="chat-icon-btn"><i class="fa-solid fa-bell"></i></button>
        <button class="chat-icon-btn"><i class="fa-solid fa-gear"></i></button>
        <button class="chat-icon-btn" @click="$emit('close')">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
    </div>

    <div v-if="isSuperAdmin" class="order-member-add">
      <button class="btn btn-sm btn-dark" @click.stop="showAddMemberBox = !showAddMemberBox">
        + Add Member
      </button>
      <div v-if="showAddMemberBox" class="add-member-box" @click.stop>
        <select v-model="addMemberId" class="form-select form-select-sm">
          <option value="">Select member</option>
          <option v-for="m in filteredAvailableMembers" :key="m.id" :value="m.id">
            {{ m.name }} — {{ m.email }}
          </option>
        </select>
        <button class="btn btn-sm btn-primary mt-2 w-100" @click="addMemberToOrder">
          Add To Order
        </button>
      </div>
    </div>

    <div class="team-members-list">
      <div class="team-members-label member-toggle" @click.stop="showMembersList = !showMembersList">
        <span>Order Members — click to tag</span>
        <i :class="showMembersList ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'"></i>
      </div>
      <div v-if="showMembersList" class="team-members-scroll">
        <div
          v-for="m in teamMembers"
          :key="m.id"
          class="team-member-item"
          :class="{ active: activeChatMember?.id === m.id }"
          @click="startChatWith(m)"
        >
          <div
            class="member-av"
            :style="{ background: m.profile_photo_url ? '#fff' : m.color }"
            title="View profile"
            @click.stop="openProfile(m)"
          >
            <img v-if="m.profile_photo_url" :src="m.profile_photo_url" class="member-av-img" />
            <span v-else>{{ m.initial }}</span>
            <span class="online-dot online"></span>
          </div>
          <div class="member-info" @click.stop="openProfile(m)" title="View profile">
            <div class="member-name">{{ m.name }}</div>
            <div class="member-role">{{ m.role }}</div>
          </div>
          <button v-if="isSuperAdmin" class="member-remove-btn" title="Remove from order" @click.stop="removeMember(m.id)">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div v-if="teamMembers.length === 0" class="member-drop-empty">No members added yet</div>
      </div>
    </div>

    <div class="chat-messages" ref="chatMessages">
      <div v-if="chatMessages.length === 0" class="chat-empty">No messages yet</div>

      <div
        v-for="(msg, i) in orderedMessages"
        :key="messageKey(msg, i)"
        class="chat-msg-group"
        :class="{ mine: isMine(msg) }"
      >
        <div class="chat-msg-meta">
          <div
            class="chat-av-sm clickable-avatar"
            :style="{ background: messagePhoto(msg) ? '#fff' : msg.senderColor }"
            title="View profile"
            @click.stop="openProfileByMessage(msg)"
          >
            <img v-if="messagePhoto(msg)" :src="messagePhoto(msg)" class="member-av-img" />
            <span v-else>{{ displayInitial(msg) }}</span>
          </div>
          <span class="chat-msg-name">{{ displaySender(msg) }}</span>
          <span class="chat-msg-time">{{ msg.time }}</span>
        </div>

        <div class="chat-bubble-wrap">
          <div class="chat-bubble-user">
            <div v-if="isDeletedEveryone(msg)" class="deleted-message">{{ deletedText(msg) }}</div>

            <div v-else-if="isEditing(msg)" class="chat-edit-box">
              <textarea v-model="editingText" class="chat-edit-textarea" @keydown.enter.exact.prevent="saveEditMessage"></textarea>
              <div class="chat-edit-actions">
                <button class="edit-cancel-btn" @click="cancelEditMessage">Cancel</button>
                <button class="edit-save-btn" @click="saveEditMessage">Save</button>
              </div>
            </div>

            <template v-else>
              <div v-if="msg.text" v-html="formatMessage(msg.text)"></div>
              <span v-if="msg.editedAt" class="edited-label">edited</span>
              <div v-if="msg.files && msg.files.length" class="chat-file-list">
                <a
                  v-for="file in msg.files"
                  :key="file.id || file.url || file.name"
                  :href="file.url"
                  target="_blank"
                  :download="file.name"
                  class="chat-file-card"
                >
                  <img v-if="file.isImage" :src="file.url" class="chat-file-img" />
                  <div v-else class="chat-file-doc">
                    <i :class="getFileIcon(file.name)"></i>
                  </div>
                  <div class="chat-file-info">
                    <span class="chat-file-name">{{ file.name }}</span>
                    <span class="chat-file-type">{{ file.type || 'file' }}</span>
                  </div>
                </a>
              </div>
            </template>
          </div>

          <button v-if="canShowMessageActions(msg)" class="chat-action-btn" @click.stop="toggleMessageMenu(msg, i)">
            <i class="fa-solid fa-chevron-down"></i>
          </button>

          <div v-if="openMessageMenuKey === messageKey(msg, i)" class="msg-action-menu" @click.stop>
            <div v-if="canEditMessage(msg)" class="msg-action-item" @click="startEditMessage(msg)">
              <i class="fa-solid fa-pen"></i> Edit
            </div>
            <div class="msg-action-item" @click="openMessageInfo(msg)">
              <i class="fa-solid fa-circle-info"></i> Info
            </div>
            <div class="msg-action-item danger" @click="openDeleteOptions(msg)">
              <i class="fa-solid fa-trash"></i> Delete
            </div>
          </div>
        </div>

        <div v-if="isMine(msg) && !isDeletedEveryone(msg)" class="seen-row" :title="seenTitle(msg)">
          {{ seenText(msg) }}
        </div>
      </div>
    </div>

    <div v-if="showTagDropdown" class="tag-dropdown" @click.stop>
      <div v-for="m in tagFilteredMembers" :key="m.id" class="tag-drop-item" @click="insertTag(m)">
        <div class="member-av-sm" :style="{ background: m.profile_photo_url ? '#fff' : m.color }">
          <img v-if="m.profile_photo_url" :src="m.profile_photo_url" class="member-av-img" />
          <span v-else>{{ m.initial }}</span>
        </div>
        <span>{{ m.name }}</span>
      </div>
    </div>

    <div class="chat-input-area">
      <div class="chat-input-box">
        <textarea
          v-model="newMessage"
          class="chat-input"
          placeholder="Type a message... use @ to tag"
          @keydown.enter.exact.prevent="sendMessage"
          @input="onChatInput"
          rows="1"
          ref="chatInput"
        ></textarea>
      <button class="chat-attach-btn" type="button" title="Attach files" @click="$refs.chatFileInput.click()">
  <i class="fa-solid fa-paperclip"></i>
</button>
<input ref="chatFileInput" type="file" multiple class="hidden-file-input" @change="uploadChatFiles" />

<!-- Voice button -->
<button
  class="chat-attach-btn"
  type="button"
  :class="{ recording: isRecording }"
  :title="isRecording ? 'Stop recording' : 'Record voice message'"
  @click="toggleRecording"
>
  <i :class="isRecording ? 'fa-solid fa-stop' : 'fa-solid fa-microphone'"></i>
</button>

<!-- Recording indicator -->
<div v-if="isRecording" class="recording-indicator">
  <span class="rec-dot"></span> Recording...
</div>

        <button class="chat-send-btn" @click="sendMessage">
          <i class="fa-solid fa-paper-plane"></i>
        </button>
      </div>
      <div class="chat-input-hint">Enter to send · @ to tag · Click member to auto-tag</div>
    </div>

    <!-- DELETE MODAL -->
    <div v-if="deleteTarget" class="delete-modal-overlay" @click.self="deleteTarget = null">
      <div class="delete-modal">
        <div class="delete-modal-title">Delete message?</div>
        <button class="delete-modal-option" @click="deleteForMe">Delete for me</button>
        <button v-if="canDeleteForEveryone(deleteTarget)" class="delete-modal-option danger" @click="deleteForEveryone">Delete for everyone</button>
        <button class="delete-modal-option cancel" @click="deleteTarget = null">Cancel</button>
      </div>
    </div>

    <!-- INFO MODAL -->
    <div v-if="infoTarget" class="delete-modal-overlay" @click.self="infoTarget = null">
      <div class="message-info-modal">
        <div class="delete-modal-title">Message info</div>
        <div class="info-section">
          <div class="info-label-title">Seen by</div>
          <div v-if="seenList(infoTarget).filter(u => Number(u.id) !== Number(currentUser?.id || 0)).length">
            <div v-for="u in seenList(infoTarget).filter(u => Number(u.id) !== Number(currentUser?.id || 0))" :key="u.id" class="seen-info-row">
              <span>{{ u.name }}</span>
              <small>{{ u.readAt ? new Date(u.readAt).toLocaleString() : 'Seen' }}</small>
            </div>
          </div>
          <div v-else class="seen-info-empty">Not seen yet</div>
        </div>
        <button class="delete-modal-option cancel" @click="infoTarget = null">Close</button>
      </div>
    </div>

    <!-- PROFILE MODAL -->
    <div v-if="profileModal" class="profile-modal-overlay" @click.self="closeProfile">
      <div class="profile-modal" @click.stop>
        <button class="profile-close" @click="closeProfile"><i class="fa-solid fa-xmark"></i></button>
        <div class="profile-photo-wrap">
          <img v-if="profileForm.preview" :src="profileForm.preview" class="profile-photo" />
          <div v-else class="profile-photo-empty">{{ profileUser?.name ? profileUser.name.charAt(0).toUpperCase() : '?' }}</div>
        </div>
        <input v-if="isOwnProfile" type="file" accept="image/*" class="form-control form-control-sm mt-2" @change="onProfilePhotoChange" />
        <div class="profile-field">
          <label>Name</label>
          <input v-model="profileForm.name" :readonly="!isOwnProfile" class="form-control form-control-sm" />
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
          <textarea v-model="profileForm.about" :readonly="!isOwnProfile" class="form-control" rows="4" placeholder="Write something about yourself..."></textarea>
        </div>
        <button v-if="isOwnProfile" class="btn btn-primary w-100 mt-3" @click="saveProfile">Save Profile</button>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'OrderChatPanel',

  props: {
    selectedOrder: { type: Object, default: null },
    teamMembers: { type: Array, default: () => [] },
    availableMembers: { type: Array, default: () => [] },
    chatMessages: { type: Array, default: () => [] },
    headers: { type: Function, required: true },
    initial: { type: Function, required: true },
    memberColor: { type: Function, required: true },
    getFileIcon: { type: Function, required: true },
    normalizeOrderFile: { type: Function, required: true }
  },

  emits: ['close', 'refresh-orders', 'refresh-files', 'update-chat-messages', 'add-chat-files-to-card'],

  data() {
    return {
      chatWidth: 340,
      isChatResizing: false,
      showAddMemberBox: false,
      showTagDropdown: false,
      activeChatMember: null,
      addMemberId: '',
      newMessage: '',
      tagQuery: '',
      openMessageMenuKey: null,
      editingMessageId: null,
      editingText: '',
      deleteTarget: null,
      infoTarget: null,
      showMembersList: false,
      profileModal: false,
      profileUser: null,
      profileForm: { name: '', about: '', profile_photo: null, preview: '' },
windowWidth: typeof window !== 'undefined' ? window.innerWidth : 1200,
isRecording: false,
mediaRecorder: null,
audioChunks: []
    }
  },

  computed: {
    currentUser() {
      try { return JSON.parse(localStorage.getItem('user')) || null } catch { return null }
    },
    isSuperAdmin() { return this.currentUser?.role === 'super_admin' },
    isOwnProfile() { return Number(this.profileUser?.id || 0) === Number(this.currentUser?.id || 0) },
    isMobile() { return this.windowWidth < 768 },

    orderedMessages() {
      return [...this.chatMessages].sort((a, b) => {
        const at = Number(a.sortAt || a.createdAtMs || 0)
        const bt = Number(b.sortAt || b.createdAtMs || 0)
        return at - bt
      })
    },

    filteredAvailableMembers() {
      return this.availableMembers.filter(m => !this.teamMembers.some(tm => tm.id === m.id))
    },

    tagFilteredMembers() {
      if (!this.tagQuery) return this.teamMembers
      return this.teamMembers.filter(m => m.name.toLowerCase().includes(this.tagQuery.toLowerCase()))
    }
  },

  mounted() {
    this.scrollChatBottom()
    this.handleResize = () => { this.windowWidth = window.innerWidth }
    window.addEventListener('resize', this.handleResize)
  },

  beforeUnmount() {
    document.removeEventListener('mousemove', this.resizeChat)
    document.removeEventListener('mouseup', this.stopChatResize)
    window.removeEventListener('resize', this.handleResize)
  },

  watch: {
    chatMessages: { deep: true, handler() { this.scrollChatBottom() } }
  },

  methods: {
    async toggleRecording() {
  if (this.isRecording) {
    this.stopRecording()
  } else {
    await this.startRecording()
  }
},

async startRecording() {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    alert('Is mobile browser me microphone support nahi hai. Chrome use karo.')
    return
  }

  if (!window.MediaRecorder) {
    alert('Is mobile browser me voice recording support nahi hai. Chrome use karo.')
    return
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })

    this.audioChunks = []

    let options = {}
    if (MediaRecorder.isTypeSupported('audio/webm')) {
      options = { mimeType: 'audio/webm' }
    } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
      options = { mimeType: 'audio/mp4' }
    }

    this.mediaRecorder = new MediaRecorder(stream, options)

    this.mediaRecorder.ondataavailable = (e) => {
      if (e.data.size > 0) this.audioChunks.push(e.data)
    }

    this.mediaRecorder.onstop = async () => {
      const mimeType = this.mediaRecorder.mimeType || 'audio/webm'
      const ext = mimeType.includes('mp4') ? 'mp4' : 'webm'

      const blob = new Blob(this.audioChunks, { type: mimeType })
      const file = new File([blob], `voice-${Date.now()}.${ext}`, { type: mimeType })

      stream.getTracks().forEach(t => t.stop())
      await this.uploadVoiceMessage(file)
    }

    this.mediaRecorder.start()
    this.isRecording = true
  } catch (e) {
    console.error('MIC ERROR:', e)

    if (e.name === 'NotAllowedError') {
      alert('Mic permission deny hai. Browser settings me Allow karo.')
    } else if (e.name === 'NotFoundError') {
      alert('Microphone device nahi mila.')
    } else {
      alert('Error: ' + e.name)
    }
  }
},

stopRecording() {
  if (this.mediaRecorder && this.isRecording) {
    this.mediaRecorder.stop()
    this.isRecording = false
  }
},

async uploadVoiceMessage(file) {
  if (!this.selectedOrder) return
  const formData = new FormData()
  formData.append('files[]', file)
  try {
    const res = await axios.post(
      `/api/orders/${this.selectedOrder.id}/chat-files`,
      formData,
      { headers: { ...this.headers(), 'Content-Type': 'multipart/form-data' } }
    )
    const savedFilesRaw = res.data?.files || res.data?.data || []
    const me = this.currentUser || {}
    const savedFiles = savedFilesRaw.map(f => ({
      ...this.normalizeOrderFile(f), cardType: 'chat_files',
      senderId: f.user?.id || me.id || null
    }))
    const savedMessages = savedFiles.map(f => {
      const createdDate = f.createdAt ? new Date(f.createdAt) : new Date()
      return {
        localKey: `file-${f.id}`, fileMessageId: f.id,
        senderId: f.senderId || me.id || null,
        sender: f.sender || me.name || 'You',
        senderInitial: this.initial(f.sender || me.name || 'You'),
        senderColor: this.memberColor(f.senderId || me.id || 0),
        senderPhoto: me.profile_photo_url || null,
        time: createdDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        sortAt: createdDate.getTime(), text: '', files: [f]
      }
    })
    this.$emit('update-chat-messages', [...this.chatMessages, ...savedMessages])
    this.scrollChatBottom()
  } catch (e) {
    console.error('uploadVoiceMessage error:', e)
    alert('Voice message upload nahi hui')
  }
},
    async openProfile(member) {
      if (!member?.id) return
      try {
        const res = await axios.get(`/api/users/${member.id}/profile`, { headers: this.headers() })
        this.profileUser = res.data
        this.profileForm = { name: res.data?.name || '', about: res.data?.about || '', profile_photo: null, preview: res.data?.profile_photo_url || '' }
        this.profileModal = true
      } catch (e) { console.error('openProfile error:', e); alert(e.response?.data?.message || 'Profile load nahi hui') }
    },

    openProfileByMessage(msg) {
      if (!msg?.senderId) return
      const member = this.teamMembers.find(m => Number(m.id) === Number(msg.senderId))
      if (member) this.openProfile(member)
      else if (msg.senderId && this.currentUser && Number(msg.senderId) === Number(this.currentUser.id)) this.openProfile(this.currentUser)
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
      if (!this.isOwnProfile) return
      const form = new FormData()
      form.append('name', this.profileForm.name || '')
      form.append('about', this.profileForm.about || '')
      if (this.profileForm.profile_photo) form.append('profile_photo', this.profileForm.profile_photo)
      try {
        const res = await axios.post('/api/me/profile', form, { headers: { ...this.headers(), 'Content-Type': 'multipart/form-data' } })
        const user = res.data?.user
        if (user) localStorage.setItem('user', JSON.stringify(user))
        this.closeProfile()
        this.$emit('refresh-orders')
      } catch (e) { console.error('saveProfile error:', e); alert(e.response?.data?.message || 'Profile save nahi hui') }
    },

    messagePhoto(msg) {
      if (msg?.senderPhoto) return msg.senderPhoto
      const senderId = Number(msg?.senderId || 0)
      if (senderId && Number(this.currentUser?.id || 0) === senderId) return this.currentUser?.profile_photo_url || null
      const member = this.teamMembers.find(m => Number(m.id) === senderId)
      return member?.profile_photo_url || null
    },

    messageKey(msg, index) { return msg.localKey || msg.id || msg.fileMessageId || `msg-${index}` },
    isMine(msg) { if (!this.currentUser?.id) return false; return Number(msg.senderId || 0) === Number(this.currentUser.id) },
    isDeletedEveryone(msg) { return Boolean(msg.deletedEveryoneAt || msg.deleted_everyone_at) },
    deletedText(msg) { return this.isMine(msg) ? 'You deleted this message' : 'This message was deleted' },

    seenList(msg) {
      return msg.seenBy || (msg.reads || []).map(r => ({ id: r.user?.id || r.user_id, name: r.user?.name || 'User', readAt: r.read_at }))
    },

    seenText(msg) {
      if (!this.isMine(msg)) return ''
      const seen = this.seenList(msg).filter(u => Number(u.id) !== Number(this.currentUser?.id || 0))
      if (!seen.length) return 'Sent'
      if (seen.length === 1) return `Seen by ${seen[0].name}`
      return `Seen by ${seen.length} people`
    },

    seenTitle(msg) {
      const seen = this.seenList(msg).filter(u => Number(u.id) !== Number(this.currentUser?.id || 0))
      if (!seen.length) return 'Not seen yet'
      return seen.map(u => `${u.name}${u.readAt ? ' - ' + new Date(u.readAt).toLocaleString() : ''}`).join('\n')
    },

    canShowMessageActions(msg) { return Boolean(msg.id) && !msg.fileMessageId && !this.isDeletedEveryone(msg) },
    canEditMessage(msg) { return this.isMine(msg) && Boolean(msg.id) && Boolean(msg.text) && !this.isDeletedEveryone(msg) },
    canDeleteForEveryone(msg) { return this.isMine(msg) && Boolean(msg.id) && !this.isDeletedEveryone(msg) },
    isEditing(msg) { return this.editingMessageId && Number(this.editingMessageId) === Number(msg.id) },

    displaySender(msg) {
      if (msg.senderId && this.currentUser?.id && Number(msg.senderId) === Number(this.currentUser.id)) return 'You'
      if (!msg.senderId && msg.sender === this.currentUser?.name) return 'You'
      return msg.sender || 'User'
    },

    displayInitial(msg) { const name = this.displaySender(msg); return name ? name.charAt(0).toUpperCase() : '?' },

    toggleMessageMenu(msg, index) {
      const key = this.messageKey(msg, index)
      this.openMessageMenuKey = this.openMessageMenuKey === key ? null : key
    },

    startEditMessage(msg) { this.editingMessageId = msg.id; this.editingText = msg.text || ''; this.openMessageMenuKey = null },
    cancelEditMessage() { this.editingMessageId = null; this.editingText = '' },

    async saveEditMessage() {
      if (!this.editingMessageId || !this.selectedOrder) return
      const text = this.editingText.trim()
      if (!text) return
      try {
        const res = await axios.put(`/api/orders/${this.selectedOrder.id}/messages/${this.editingMessageId}`, { message: text }, { headers: this.headers() })
        const updated = res.data?.message || res.data
        const updatedAt = updated.edited_at || new Date().toISOString()
        const nextMessages = this.chatMessages.map(msg => {
          if (Number(msg.id) !== Number(this.editingMessageId)) return msg
          return { ...msg, text: updated.message, editedAt: updatedAt }
        })
        this.$emit('update-chat-messages', nextMessages)
        this.cancelEditMessage()
      } catch (e) { console.error('saveEditMessage error:', e); alert(e.response?.data?.message || 'Message edit nahi hua') }
    },

    openDeleteOptions(msg) { this.deleteTarget = msg; this.openMessageMenuKey = null },
    openMessageInfo(msg) { this.infoTarget = msg; this.openMessageMenuKey = null },

    async deleteForMe() {
      if (!this.deleteTarget || !this.selectedOrder) return
      const target = this.deleteTarget
      try {
        if (target.id && !target.fileMessageId) await axios.delete(`/api/orders/${this.selectedOrder.id}/messages/${target.id}/for-me`, { headers: this.headers() })
        const nextMessages = this.chatMessages.filter(msg => this.messageKey(msg, 0) !== this.messageKey(target, 0))
        this.$emit('update-chat-messages', nextMessages)
        this.deleteTarget = null
      } catch (e) { console.error('deleteForMe error:', e); alert(e.response?.data?.message || 'Delete for me nahi hua') }
    },

    async deleteForEveryone() {
      if (!this.deleteTarget || !this.selectedOrder) return
      const target = this.deleteTarget
      try {
        const res = await axios.delete(`/api/orders/${this.selectedOrder.id}/messages/${target.id}/everyone`, { headers: this.headers() })
        const updated = res.data?.message || res.data
        const deletedAt = updated.deleted_everyone_at || new Date().toISOString()
        const nextMessages = this.chatMessages.map(msg => {
          if (Number(msg.id) !== Number(target.id)) return msg
          return { ...msg, text: '', files: [], deletedEveryoneAt: deletedAt }
        })
        this.$emit('update-chat-messages', nextMessages)
        this.deleteTarget = null
      } catch (e) { console.error('deleteForEveryone error:', e); alert(e.response?.data?.message || 'Delete for everyone nahi hua') }
    },

    startChatWith(member) {
      this.activeChatMember = member
      this.newMessage = '@' + member.name + ' '
      this.$nextTick(() => this.$refs.chatInput?.focus())
    },

    onChatInput() {
      const text = this.newMessage
      const atIndex = text.lastIndexOf('@')
      if (atIndex !== -1) {
        const query = text.slice(atIndex + 1)
        if (!query.includes(' ') || query.length < 20) { this.tagQuery = query; this.showTagDropdown = true; return }
      }
      this.showTagDropdown = false
    },

    insertTag(member) {
      const atIndex = this.newMessage.lastIndexOf('@')
      this.newMessage = this.newMessage.slice(0, atIndex) + '@' + member.name + ' '
      this.showTagDropdown = false
      this.$nextTick(() => this.$refs.chatInput?.focus())
    },

    formatMessage(text) {
      if (!text) return ''
      return text.replace(/@([A-Za-z ]+)/g, '<span class="tag-highlight">@$1</span>')
    },

    async sendMessage() {
      if (!this.newMessage.trim() || !this.selectedOrder) return
      try {
        const res = await axios.post(`/api/orders/${this.selectedOrder.id}/messages`, { message: this.newMessage }, { headers: this.headers() })
        const msg = res.data?.message || res.data
        const createdDate = msg.created_at ? new Date(msg.created_at) : new Date()
        const newMsg = {
          id: msg.id, localKey: `msg-${msg.id || Date.now()}`,
          senderId: msg.user?.id || this.currentUser?.id || null,
          sender: msg.user?.name || this.currentUser?.name || 'You',
          senderInitial: this.initial(msg.user?.name || this.currentUser?.name || 'You'),
          senderColor: this.memberColor(msg.user?.id || this.currentUser?.id || 0),
          senderPhoto: msg.user?.profile_photo_url || this.currentUser?.profile_photo_url || null,
          time: createdDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
          sortAt: createdDate.getTime(),
          editedAt: null, deletedEveryoneAt: null, text: msg.message, reads: [], seenBy: []
        }
        this.$emit('update-chat-messages', [...this.chatMessages, newMsg])
        this.newMessage = ''
        this.showTagDropdown = false
        this.scrollChatBottom()
      } catch (e) { console.error('sendMessage error:', e); alert(e.response?.data?.message || 'Message send nahi hua') }
    },

    async uploadChatFiles(event) {
      const files = Array.from(event.target.files || [])
      event.target.value = ''
      if (!files.length || !this.selectedOrder) return
      const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      const me = this.currentUser || {}
      const tempFiles = files.map((file, index) => ({
        id: `chat-temp-${Date.now()}-${index}-${Math.random().toString(16).slice(2)}`,
        name: file.name, url: URL.createObjectURL(file),
        isImage: file.type.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(file.name),
        type: file.type || 'file', size: file.size, uploading: true, cardType: 'chat_files', senderId: me.id || null
      }))
      const tempSortAt = Date.now()
      const tempMessages = tempFiles.map((file, index) => ({
        localKey: file.id, fileMessageId: file.id, senderId: me.id || null,
        sender: me.name || 'You', senderInitial: this.initial(me.name || 'You'),
        senderColor: this.memberColor(me.id || 0), senderPhoto: me.profile_photo_url || null,
        time: now, sortAt: tempSortAt + index, text: '', files: [file]
      }))
      const messagesWithTemp = [...this.chatMessages, ...tempMessages]
      this.$emit('update-chat-messages', messagesWithTemp)
      this.scrollChatBottom()
      const formData = new FormData()
      files.forEach(file => { formData.append('files[]', file) })
      try {
        const res = await axios.post(`/api/orders/${this.selectedOrder.id}/chat-files`, formData, { headers: { ...this.headers(), 'Content-Type': 'multipart/form-data' } })
        const savedFilesRaw = res.data?.files || res.data?.data || []
        const savedFiles = savedFilesRaw.map(file => ({
          ...this.normalizeOrderFile(file), cardType: 'chat_files',
          senderId: file.user?.id || me.id || null, senderPhoto: file.user?.profile_photo_url || me.profile_photo_url || null
        }))
        const savedMessages = savedFiles.map((file, index) => {
          const createdDate = file.createdAt ? new Date(file.createdAt) : new Date(tempSortAt + index)
          return {
            localKey: `file-${file.id}`, fileMessageId: file.id,
            senderId: file.senderId || me.id || null, sender: file.sender || me.name || 'You',
            senderInitial: file.senderInitial || this.initial(file.sender || me.name || 'You'),
            senderColor: file.senderColor || this.memberColor(file.senderId || me.id || 0),
            senderPhoto: file.senderPhoto || me.profile_photo_url || null,
            time: createdDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            sortAt: createdDate.getTime(), text: '', files: [file]
          }
        })
        const withoutTemp = messagesWithTemp.filter(msg => !String(msg.fileMessageId || '').startsWith('chat-temp-'))
        this.$emit('update-chat-messages', [...withoutTemp, ...savedMessages])
        this.$emit('add-chat-files-to-card', savedFiles)
        this.scrollChatBottom()
      } catch (e) {
        const withoutTemp = messagesWithTemp.filter(msg => !String(msg.fileMessageId || '').startsWith('chat-temp-'))
        this.$emit('update-chat-messages', withoutTemp)
        console.error('uploadChatFiles error:', e)
        alert(e.response?.data?.message || 'Chat file upload nahi hui')
      }
    },

    async addMemberToOrder() {
      if (!this.addMemberId || !this.selectedOrder) return
      try {
        await axios.post(`/api/orders/${this.selectedOrder.id}/members`, { user_id: this.addMemberId }, { headers: this.headers() })
        this.addMemberId = ''
        this.showAddMemberBox = false
        this.$emit('refresh-orders')
      } catch (e) { console.error('addMemberToOrder error:', e); alert(e.response?.data?.message || 'Member add nahi hua') }
    },

    async removeMember(userId) {
      if (!this.selectedOrder) return
      if (!confirm('Remove this member from order?')) return
      try {
        await axios.delete(`/api/orders/${this.selectedOrder.id}/members/${userId}`, { headers: this.headers() })
        this.$emit('refresh-orders')
      } catch (e) { console.error('removeMember error:', e); alert(e.response?.data?.message || 'Member remove nahi hua') }
    },

    scrollChatBottom() {
      this.$nextTick(() => {
        const el = this.$refs.chatMessages
        if (el) el.scrollTop = el.scrollHeight
      })
    },

    startChatResize() {
      this.isChatResizing = true
      document.addEventListener('mousemove', this.resizeChat)
      document.addEventListener('mouseup', this.stopChatResize)
    },

    resizeChat(e) {
      if (!this.isChatResizing) return
      let newWidth = window.innerWidth - e.clientX
      if (newWidth < 260) newWidth = 260
      if (newWidth > 650) newWidth = 650
      this.chatWidth = newWidth
    },

    stopChatResize() {
      this.isChatResizing = false
      document.removeEventListener('mousemove', this.resizeChat)
      document.removeEventListener('mouseup', this.stopChatResize)
    }
  }
}
</script>

<style scoped>
.chat-attach-btn.recording {
  background: #ff3d71 !important;
  color: #fff !important;
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}

.recording-indicator {
  position: absolute;
  bottom: 70px;
  left: 10px;
  right: 10px;
  background: #ff3d71;
  color: #fff;
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 8px;
  z-index: 100;
}

.rec-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #fff;
  animation: pulse 1s infinite;
}
.chat-panel {
  width: 340px;
  min-width: 260px;
  max-width: 650px;
  background: #fff;
  border-left: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;
}

/* Mobile: full width overlay */
.chat-panel.mobile-fullscreen {
  position: fixed;
  inset: 52px 0 0 0;
  width: 100% !important;
  min-width: unset;
  max-width: unset;
  z-index: 997;
  border-left: none;
}

.chat-resize-bar {
  position: absolute;
  left: 0;
  top: 0;
  width: 6px;
  height: 100%;
  cursor: col-resize;
  z-index: 999;
}

.chat-resize-bar:hover { background: #6161ff; }

.chat-header {
  padding: 10px 12px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}

.chat-title { font-size: 14px; font-weight: 800; color: #172b4d; }

.chat-header-actions { display: flex; align-items: center; gap: 4px; }

.chat-icon-btn {
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  color: #9aa0b8;
  font-size: 13px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.chat-icon-btn:hover { background: #f3f4f6; color: #172b4d; }

.order-member-add { padding: 10px 14px; border-bottom: 1px solid #e5e7eb; position: relative; }
.add-member-box { margin-top: 8px; background: #fff; }

.team-members-list { border-bottom: 1px solid #e5e7eb; flex-shrink: 0; }

.team-members-label {
  padding: 8px 14px 6px;
  font-size: 10px;
  font-weight: 700;
  color: #9aa0b8;
  text-transform: uppercase;
  letter-spacing: 0.6px;
}

.team-members-scroll { max-height: 140px; overflow-y: auto; }

.team-member-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 7px 14px;
  cursor: pointer;
  transition: background 0.12s;
}

.team-member-item:hover { background: #f0f7ff; }
.team-member-item.active { background: #f0f0ff; border-left: 3px solid #6161ff; }

.member-av {
  position: relative;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: hidden;
}

.online-dot { position: absolute; bottom: 0; right: 0; width: 8px; height: 8px; border-radius: 50%; border: 2px solid #fff; }
.online-dot.online { background: #00c875; }

.member-info { flex: 1; overflow: hidden; }
.member-name { font-size: 13px; font-weight: 700; color: #172b4d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.member-role { font-size: 11px; color: #9aa0b8; }

.member-remove-btn {
  width: 22px;
  height: 22px;
  border: none;
  border-radius: 6px;
  background: #fff0f0;
  color: #e2445c;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 11px;
}

.member-remove-btn:hover { background: #e2445c; color: #fff; }
.member-drop-empty { padding: 10px 14px; font-size: 12px; color: #9aa0b8; text-align: center; }

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.chat-empty { text-align: center; color: #9aa0b8; font-size: 13px; padding: 20px 0; }

.chat-msg-group { display: flex; flex-direction: column; gap: 3px; align-items: flex-start; }
.chat-msg-group.mine { align-items: flex-end; }
.chat-msg-group.mine .chat-msg-meta { flex-direction: row-reverse; }
.chat-msg-group.mine .chat-bubble-user { margin-left: 0; margin-right: 32px; border-radius: 10px 0 10px 10px; background: #eef0ff; }

.chat-msg-meta { display: flex; align-items: center; gap: 6px; }

.chat-av-sm, .member-av-sm {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: hidden;
}

.chat-msg-name { font-size: 12px; font-weight: 700; color: #172b4d; }
.chat-msg-time { font-size: 11px; color: #9aa0b8; margin-left: 4px; }

.chat-bubble-user {
  background: #f3f4f6;
  border-radius: 0 10px 10px 10px;
  padding: 8px 11px;
  font-size: 13px;
  color: #172b4d;
  margin-left: 32px;
  line-height: 1.5;
  max-width: 86%;
  word-break: break-word;
}

:deep(.tag-highlight) { background: #e8f0ff; color: #6161ff; border-radius: 4px; padding: 1px 4px; font-weight: 700; }

.tag-dropdown {
  position: absolute;
  bottom: 68px;
  left: 10px;
  right: 10px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  z-index: 9999;
  overflow: hidden;
}

.tag-drop-item { display: flex; align-items: center; gap: 10px; padding: 9px 14px; cursor: pointer; font-size: 13px; color: #172b4d; font-weight: 600; transition: background 0.12s; }
.tag-drop-item:hover { background: #f0f0ff; color: #6161ff; }

.chat-input-area { padding: 8px 10px; border-top: 1px solid #e5e7eb; flex-shrink: 0; }

.chat-input-box { display: flex; align-items: flex-end; gap: 6px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 5px 8px; background: #f8f9fc; }

.chat-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 13px;
  color: #172b4d;
  outline: none;
  resize: none;
  max-height: 80px;
  font-family: inherit;
  line-height: 1.5;
}

.chat-attach-btn, .chat-send-btn {
  width: 28px;
  height: 28px;
  border: none;
  background: #6161ff;
  border-radius: 7px;
  color: #fff;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
  flex-shrink: 0;
}

.chat-send-btn:hover { background: #4f4fd4; }
.chat-attach-btn { background: #eef0ff; color: #6161ff; }
.chat-attach-btn:hover { background: #dfe3ff; }

.chat-input-hint { font-size: 10px; color: #c5cae0; margin-top: 4px; text-align: center; }

.chat-file-list { display: flex; flex-direction: column; gap: 7px; margin-top: 6px; }

.chat-file-card {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 6px;
  color: #172b4d;
}

.chat-file-card:hover { border-color: #6161ff; }

.chat-file-img { width: 52px; height: 52px; object-fit: cover; border-radius: 7px; background: #f3f4f6; border: 1px solid #e5e7eb; }

.chat-file-doc { width: 52px; height: 52px; border-radius: 7px; background: #f3f4f6; border: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; color: #6b7280; font-size: 22px; flex-shrink: 0; }

.chat-file-info { min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.chat-file-name { font-size: 12px; font-weight: 800; color: #172b4d; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.chat-file-type { font-size: 10px; color: #9aa0b8; }

.hidden-file-input { display: none; }

/* MESSAGE ACTIONS */
.chat-bubble-wrap { position: relative; display: flex; align-items: flex-start; gap: 4px; max-width: 90%; }
.chat-msg-group.mine .chat-bubble-wrap { flex-direction: row-reverse; }

.chat-action-btn {
  width: 22px;
  height: 22px;
  border: none;
  background: transparent;
  color: #9aa0b8;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  opacity: 0;
  margin-top: 3px;
  font-size: 11px;
}

.chat-msg-group:hover .chat-action-btn { opacity: 1; }
.chat-action-btn:hover { background: #e5e7eb; color: #172b4d; }

.msg-action-menu {
  position: absolute;
  top: 26px;
  right: 0;
  width: 130px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  box-shadow: 0 12px 30px rgba(0,0,0,0.16);
  z-index: 99999;
  overflow: hidden;
}

.chat-msg-group:not(.mine) .msg-action-menu { left: 0; right: auto; }

.msg-action-item { padding: 9px 12px; font-size: 12px; font-weight: 700; color: #172b4d; cursor: pointer; display: flex; gap: 8px; align-items: center; }
.msg-action-item:hover { background: #f5f6fb; }
.msg-action-item.danger { color: #e2445c; }

.chat-edit-box { display: flex; flex-direction: column; gap: 7px; min-width: 200px; }

.chat-edit-textarea {
  width: 100%;
  min-height: 54px;
  border: 1px solid #d8d5ff;
  border-radius: 8px;
  padding: 7px;
  resize: vertical;
  outline: none;
  font-size: 13px;
  color: #172b4d;
}

.chat-edit-actions { display: flex; justify-content: flex-end; gap: 6px; }

.edit-cancel-btn, .edit-save-btn { border: none; border-radius: 7px; padding: 5px 10px; font-size: 12px; font-weight: 800; cursor: pointer; }
.edit-cancel-btn { background: #f3f4f6; color: #6b7280; }
.edit-save-btn { background: #6161ff; color: #fff; }

.edited-label { font-size: 10px; color: #9aa0b8; margin-left: 6px; font-style: italic; }
.deleted-message { font-size: 12px; font-style: italic; color: #8d93a8; }

.delete-modal-overlay { position: fixed; inset: 0; z-index: 999999; background: rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; padding: 16px; }

.delete-modal { width: 300px; max-width: 92vw; background: #fff; border-radius: 16px; box-shadow: 0 30px 80px rgba(0,0,0,0.25); overflow: hidden; }

.delete-modal-title { padding: 14px 16px; font-size: 15px; font-weight: 900; color: #172b4d; border-bottom: 1px solid #e5e7eb; }

.delete-modal-option { width: 100%; border: none; background: #fff; padding: 12px 16px; text-align: left; font-size: 13px; font-weight: 800; color: #172b4d; cursor: pointer; }
.delete-modal-option:hover { background: #f5f6fb; }
.delete-modal-option.danger { color: #e2445c; }
.delete-modal-option.cancel { color: #6b7280; }

.member-toggle { display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none; }
.member-toggle i { font-size: 10px; color: #9aa0b8; }

.member-av-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.member-info { cursor: pointer; }

.profile-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center; z-index: 999999; padding: 16px; }

.profile-modal { width: 360px; max-width: 100%; background: #fff; border-radius: 18px; padding: 20px; position: relative; box-shadow: 0 30px 90px rgba(0,0,0,.25); }

.profile-close { position: absolute; right: 12px; top: 12px; width: 28px; height: 28px; border: none; background: #f3f4f6; border-radius: 8px; color: #6b7280; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.profile-close:hover { background: #e5e7eb; }

.profile-photo-wrap { display: flex; justify-content: center; margin-top: 10px; }

.profile-photo, .profile-photo-empty { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }

.profile-photo-empty { background: #6161ff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 900; }

.profile-field { margin-top: 10px; }
.profile-field label { font-size: 12px; font-weight: 800; color: #6b7280; margin-bottom: 4px; display: block; }
.profile-field input[readonly], .profile-field textarea[readonly] { background: #f8f9fc; cursor: not-allowed; }

.clickable-avatar { cursor: pointer; }
.clickable-avatar:hover, .member-av:hover, .member-info:hover { box-shadow: 0 0 0 2px rgba(97,97,255,.18); }

.message-info-modal { width: 300px; max-width: 92vw; background: #fff; border-radius: 14px; padding: 14px; box-shadow: 0 16px 45px rgba(0,0,0,.25); }

.info-section { padding: 8px 2px 10px; }
.info-label-title { font-size: 12px; font-weight: 900; color: #6b7280; text-transform: uppercase; margin-bottom: 8px; }

.seen-info-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 0; border-bottom: 1px solid #eef0f6; font-size: 13px; font-weight: 700; color: #111827; }
.seen-info-row small { color: #6b7280; font-weight: 500; font-size: 11px; text-align: right; }

.seen-info-empty { background: #f3f4f6; color: #6b7280; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 700; text-align: center; }

.seen-row { font-size: 10px; color: #9aa0b8; margin-top: 2px; text-align: right; }

/* RESPONSIVE */
@media (max-width: 767px) {
  .chat-panel:not(.mobile-fullscreen) { display: none; }

  .chat-bubble-user { max-width: 92%; }
  .chat-bubble-wrap { max-width: 94%; }

  .chat-input-hint { display: none; }

  .msg-action-menu { position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%); top: auto; right: auto; width: 200px; }
}

@media (max-width: 480px) {
  .chat-file-img { width: 44px; height: 44px; }
  .chat-file-doc { width: 44px; height: 44px; font-size: 18px; }
}

.mt-2 { margin-top: 8px; }
.mt-3 { margin-top: 12px; }
.w-100 { width: 100%; }
</style>
