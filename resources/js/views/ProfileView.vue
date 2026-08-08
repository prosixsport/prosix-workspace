<template>
  <AppLayout>
    <div class="profile-page">
      <div class="profile-shell">

        <!-- TOP HERO -->
        <section class="profile-hero">
          <div class="hero-copy">
            <span class="eyebrow">PROSIX WORK MANAGEMENT</span>
            <h1>My Profile</h1>
            <p>
              Manage your personal information, account details and workspace access.
            </p>
          </div>

          <button
            type="button"
            class="logout-top-btn"
            @click="logout"
          >
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
          </button>
        </section>

        <!-- PROFILE CARD -->
        <section class="profile-main-card">
          <div class="profile-card-top">
            <div class="avatar-wrap">
              <img
                v-if="photoPreview || form.profile_photo_url"
                :src="photoPreview || form.profile_photo_url"
                alt="Profile"
              />

              <span v-else class="avatar-fallback">
                {{ userInitial }}
              </span>

              <label
                v-if="isOwnProfile"
                class="avatar-edit-btn"
                title="Change profile photo"
              >
                <i class="fa-solid fa-camera"></i>

                <input
                  type="file"
                  accept="image/*"
                  @change="onPhotoChange"
                />
              </label>
            </div>

            <div class="profile-identity">
              <div class="identity-line">
                <h2>{{ form.name || 'User' }}</h2>

                <span class="role-badge">
                  {{ roleLabel }}
                </span>
              </div>

              <p>{{ form.email || 'No email available' }}</p>

              <div class="profile-meta">
                <span>
                  <i class="fa-solid fa-shield-halved"></i>
                  {{ roleLabel }}
                </span>

                <span v-if="form.phone">
                  <i class="fa-solid fa-phone"></i>
                  {{ form.phone }}
                </span>
              </div>
            </div>
          </div>

          <!-- INFO GRID -->
          <div class="profile-content-grid">

            <!-- LEFT -->
            <div class="profile-panel">
              <div class="panel-heading">
                <div>
                  <span>PERSONAL INFORMATION</span>
                  <h3>Account Details</h3>
                </div>

                <button
                  v-if="!editing"
                  type="button"
                  class="edit-btn"
                  @click="editing = true"
                >
                  <i class="fa-solid fa-pen"></i>
                  Edit
                </button>
              </div>

              <div class="form-grid">
                <div class="field full">
                  <label>Full Name</label>
                  <input
                    v-model="form.name"
                    type="text"
                    :readonly="!editing"
                    placeholder="Enter full name"
                  />
                </div>

                <div class="field">
                  <label>Email Address</label>
                  <input
                    v-model="form.email"
                    type="email"
                    readonly
                  />
                </div>

                <div class="field">
                  <label>Phone Number</label>
                  <input
                    v-model="form.phone"
                    type="text"
                    :readonly="!editing"
                    placeholder="Add phone number"
                  />
                </div>

                <div class="field">
                  <label>Role</label>
                  <input
                    :value="roleLabel"
                    type="text"
                    readonly
                  />
                </div>

                <div class="field">
                  <label>Account ID</label>
                  <input
                    :value="form.id || '—'"
                    type="text"
                    readonly
                  />
                </div>

                <div class="field full">
                  <label>About</label>
                  <textarea
                    v-model="form.about"
                    rows="5"
                    :readonly="!editing"
                    placeholder="Write something about yourself..."
                  ></textarea>
                </div>
              </div>

              <div
                v-if="editing"
                class="form-actions"
              >
                <button
                  type="button"
                  class="cancel-btn"
                  @click="cancelEdit"
                >
                  Cancel
                </button>

                <button
                  type="button"
                  class="save-btn"
                  :disabled="saving"
                  @click="saveProfile"
                >
                  <i
                    :class="saving
                      ? 'fa-solid fa-spinner fa-spin'
                      : 'fa-solid fa-floppy-disk'"
                  ></i>

                  {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </div>

            <!-- RIGHT -->
            <aside class="profile-side-panel">
              <div class="side-heading">
                <span>WORKSPACE</span>
                <h3>Access Summary</h3>
              </div>

              <div class="access-list">
                <div class="access-item">
                  <span class="access-icon">
                    <i class="fa-solid fa-user-shield"></i>
                  </span>

                  <div>
                    <small>Role</small>
                    <strong>{{ roleLabel }}</strong>
                  </div>
                </div>

                <div class="access-item">
                  <span class="access-icon">
                    <i class="fa-solid fa-circle-check"></i>
                  </span>

                  <div>
                    <small>Account Status</small>
                    <strong>Active</strong>
                  </div>
                </div>

                <div class="access-item">
                  <span class="access-icon">
                    <i class="fa-solid fa-calendar-days"></i>
                  </span>

                  <div>
                    <small>Member Since</small>
                    <strong>{{ memberSince }}</strong>
                  </div>
                </div>
              </div>

              <div class="security-box">
                <span class="security-icon">
                  <i class="fa-solid fa-lock"></i>
                </span>

                <div>
                  <strong>Account Security</strong>
                  <p>
                    Keep your profile information up to date and sign out when using a shared device.
                  </p>
                </div>
              </div>

              <button
                type="button"
                class="side-logout-btn"
                @click="logout"
              >
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout from Account
              </button>
            </aside>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import axios from 'axios'
import AppLayout from '../layouts/AppLayout.vue'

export default {
  name: 'ProfileView',

  components: {
    AppLayout
  },

  data() {
    return {
      editing: false,
      saving: false,
      originalForm: null,
      photoFile: null,
      photoPreview: '',

      form: {
        id: null,
        name: '',
        email: '',
        phone: '',
        about: '',
        role: '',
        profile_photo_url: '',
        created_at: ''
      }
    }
  },

  computed: {
    currentUser() {
      try {
        return JSON.parse(localStorage.getItem('user') || '{}')
      } catch {
        return {}
      }
    },

    isOwnProfile() {
      return true
    },

    userInitial() {
      return String(this.form.name || 'U')
        .trim()
        .charAt(0)
        .toUpperCase()
    },

    roleLabel() {
      const role = String(
        this.form.role ||
        this.currentUser?.role ||
        'user'
      )
        .replace(/_/g, ' ')
        .trim()

      return role.replace(/\b\w/g, char => char.toUpperCase())
    },

    memberSince() {
      if (!this.form.created_at) return '—'

      const date = new Date(this.form.created_at)

      if (Number.isNaN(date.getTime())) return '—'

      return date.toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric'
      })
    }
  },

  mounted() {
    this.loadProfile()
  },

  methods: {
    headers() {
      return {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        Accept: 'application/json'
      }
    },

    async loadProfile() {
      const localUser = this.currentUser || {}

      this.form = {
        id: localUser.id || null,
        name: localUser.name || '',
        email: localUser.email || '',
        phone: localUser.phone || '',
        about: localUser.about || '',
        role: localUser.role || '',
        profile_photo_url: localUser.profile_photo_url || '',
        created_at: localUser.created_at || ''
      }

      try {
        const response = await axios.get(
          '/api/user/profile',
          {
            headers: this.headers()
          }
        )

        const user =
          response.data?.user ||
          response.data?.data ||
          response.data ||
          {}

        this.form = {
          ...this.form,
          ...user
        }

        localStorage.setItem(
          'user',
          JSON.stringify({
            ...localUser,
            ...user
          })
        )
      } catch (error) {
        console.error('Profile load error:', error)
      }

      this.originalForm = { ...this.form }
    },

    onPhotoChange(event) {
      const file = event.target.files?.[0]

      if (!file) return

      this.photoFile = file

      if (this.photoPreview) {
        URL.revokeObjectURL(this.photoPreview)
      }

      this.photoPreview = URL.createObjectURL(file)
      this.editing = true
    },

    cancelEdit() {
      if (this.originalForm) {
        this.form = { ...this.originalForm }
      }

      this.photoFile = null

      if (this.photoPreview) {
        URL.revokeObjectURL(this.photoPreview)
      }

      this.photoPreview = ''
      this.editing = false
    },

    async saveProfile() {
      if (this.saving) return

      this.saving = true

      try {
        const data = new FormData()

        data.append('name', this.form.name || '')
        data.append('phone', this.form.phone || '')
        data.append('about', this.form.about || '')

        if (this.photoFile) {
          data.append('profile_photo', this.photoFile)
        }

        const response = await axios.post(
          '/api/user/profile',
          data,
          {
            headers: {
              ...this.headers(),
              'Content-Type': 'multipart/form-data'
            }
          }
        )

        const user =
          response.data?.user ||
          response.data?.data ||
          response.data ||
          {}

        this.form = {
          ...this.form,
          ...user
        }

        const localUser = this.currentUser || {}

        localStorage.setItem(
          'user',
          JSON.stringify({
            ...localUser,
            ...user
          })
        )

        this.originalForm = { ...this.form }

        this.photoFile = null

        if (this.photoPreview) {
          URL.revokeObjectURL(this.photoPreview)
        }

        this.photoPreview = ''
        this.editing = false
      } catch (error) {
        console.error('Profile save error:', error)

        alert(
          error?.response?.data?.message ||
          'Profile could not be saved.'
        )
      } finally {
        this.saving = false
      }
    },

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
        console.error('Logout error:', error)
      }

      localStorage.removeItem('token')
      localStorage.removeItem('user')

      this.$router.push('/login')
    }
  },

  beforeUnmount() {
    if (this.photoPreview) {
      URL.revokeObjectURL(this.photoPreview)
    }
  }
}
</script>

<style scoped>
.profile-page {
  min-height: 100vh;
  padding: 26px;
  background: #f4f5f8;
  color: #101828;
}

.profile-shell {
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
}

.profile-hero {
  min-height: 118px;
  padding: 24px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  background: #ffffff;
  border: 1px solid #e1e5eb;
  border-radius: 16px;
}

.hero-copy .eyebrow,
.panel-heading span,
.side-heading span {
  color: #8a94a6;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: .15em;
}

.hero-copy h1 {
  margin: 7px 0 5px;
  color: #0f172a;
  font-size: 27px;
  font-weight: 900;
}

.hero-copy p {
  margin: 0;
  color: #667085;
  font-size: 12px;
}

.logout-top-btn,
.side-logout-btn {
  border: 0;
  cursor: pointer;
  font-weight: 800;
}

.logout-top-btn {
  height: 42px;
  padding: 0 17px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #101828;
  color: #fff;
  border-radius: 10px;
}

.profile-main-card {
  margin-top: 18px;
  background: #ffffff;
  border: 1px solid #e1e5eb;
  border-radius: 16px;
  overflow: hidden;
}

.profile-card-top {
  padding: 28px;
  display: flex;
  align-items: center;
  gap: 20px;
  border-bottom: 1px solid #eaecf0;
}

.avatar-wrap {
  position: relative;
  width: 94px;
  height: 94px;
  flex: 0 0 94px;
}

.avatar-wrap img,
.avatar-fallback {
  width: 94px;
  height: 94px;
  border-radius: 50%;
}

.avatar-wrap img {
  object-fit: cover;
  border: 4px solid #fff;
  box-shadow: 0 0 0 1px #dfe3e9;
}

.avatar-fallback {
  display: grid;
  place-items: center;
  background: #101828;
  color: #fff;
  font-size: 30px;
  font-weight: 900;
}

.avatar-edit-btn {
  position: absolute;
  right: -1px;
  bottom: 3px;
  width: 31px;
  height: 31px;
  display: grid;
  place-items: center;
  background: #101828;
  color: #fff;
  border: 3px solid #fff;
  border-radius: 50%;
  cursor: pointer;
}

.avatar-edit-btn input {
  display: none;
}

.profile-identity {
  min-width: 0;
}

.identity-line {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.identity-line h2 {
  margin: 0;
  color: #101828;
  font-size: 22px;
  font-weight: 900;
}

.role-badge {
  padding: 5px 9px;
  background: #eef2f6;
  color: #344054;
  border: 1px solid #dfe4ea;
  border-radius: 999px;
  font-size: 9px;
  font-weight: 800;
}

.profile-identity > p {
  margin: 5px 0 0;
  color: #667085;
  font-size: 11px;
}

.profile-meta {
  margin-top: 11px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.profile-meta span {
  padding: 6px 9px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #f8fafc;
  color: #475467;
  border: 1px solid #eaecf0;
  border-radius: 7px;
  font-size: 9px;
  font-weight: 700;
}

.profile-content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 330px;
}

.profile-panel {
  padding: 28px;
}

.profile-side-panel {
  padding: 28px;
  background: #fafbfc;
  border-left: 1px solid #eaecf0;
}

.panel-heading,
.side-heading {
  margin-bottom: 21px;
}

.panel-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 15px;
}

.panel-heading h3,
.side-heading h3 {
  margin: 6px 0 0;
  color: #101828;
  font-size: 17px;
  font-weight: 900;
}

.edit-btn {
  height: 36px;
  padding: 0 13px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  border: 1px solid #d0d5dd;
  border-radius: 8px;
  background: #fff;
  color: #344054;
  font-size: 10px;
  font-weight: 800;
  cursor: pointer;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 17px;
}

.field.full {
  grid-column: 1 / -1;
}

.field label {
  display: block;
  margin-bottom: 7px;
  color: #475467;
  font-size: 10px;
  font-weight: 800;
}

.field input,
.field textarea {
  width: 100%;
  border: 1px solid #d7dce3;
  border-radius: 9px;
  background: #fff;
  color: #101828;
  outline: none;
  font-family: inherit;
}

.field input {
  height: 42px;
  padding: 0 12px;
}

.field textarea {
  padding: 11px 12px;
  resize: vertical;
}

.field input[readonly],
.field textarea[readonly] {
  background: #f8fafc;
  color: #667085;
}

.field input:focus,
.field textarea:focus {
  border-color: #98a2b3;
  box-shadow: 0 0 0 3px rgba(15, 23, 42, .05);
}

.form-actions {
  margin-top: 20px;
  padding-top: 19px;
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  border-top: 1px solid #eaecf0;
}

.cancel-btn,
.save-btn {
  height: 39px;
  padding: 0 15px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 800;
  cursor: pointer;
}

.cancel-btn {
  border: 1px solid #d0d5dd;
  background: #fff;
  color: #344054;
}

.save-btn {
  border: 0;
  background: #101828;
  color: #fff;
}

.access-list {
  display: grid;
  gap: 10px;
}

.access-item {
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 11px;
  background: #fff;
  border: 1px solid #e4e7ec;
  border-radius: 10px;
}

.access-icon {
  width: 35px;
  height: 35px;
  display: grid;
  place-items: center;
  flex: 0 0 35px;
  background: #101828;
  color: #fff;
  border-radius: 9px;
}

.access-item small,
.access-item strong {
  display: block;
}

.access-item small {
  color: #98a2b3;
  font-size: 8px;
  font-weight: 800;
  text-transform: uppercase;
}

.access-item strong {
  margin-top: 2px;
  color: #101828;
  font-size: 11px;
  font-weight: 800;
}

.security-box {
  margin-top: 18px;
  padding: 14px;
  display: flex;
  gap: 11px;
  border: 1px solid #e4e7ec;
  border-radius: 11px;
  background: #f3f5f8;
}

.security-icon {
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  display: grid;
  place-items: center;
  border-radius: 9px;
  background: #fff;
  color: #101828;
}

.security-box strong {
  color: #101828;
  font-size: 10px;
}

.security-box p {
  margin: 4px 0 0;
  color: #667085;
  font-size: 9px;
  line-height: 1.5;
}

.side-logout-btn {
  width: 100%;
  height: 42px;
  margin-top: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #101828;
  color: #fff;
  border-radius: 9px;
}

@media (max-width: 900px) {
  .profile-content-grid {
    grid-template-columns: 1fr;
  }

  .profile-side-panel {
    border-left: 0;
    border-top: 1px solid #eaecf0;
  }
}

@media (max-width: 650px) {
  .profile-page {
    padding: 14px;
  }

  .profile-hero {
    padding: 18px;
  }

  .logout-top-btn {
    display: none;
  }

  .profile-card-top {
    padding: 20px;
    align-items: flex-start;
  }

  .avatar-wrap,
  .avatar-wrap img,
  .avatar-fallback {
    width: 72px;
    height: 72px;
  }

  .avatar-wrap {
    flex-basis: 72px;
  }

  .identity-line h2 {
    font-size: 18px;
  }

  .profile-panel,
  .profile-side-panel {
    padding: 20px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .field.full {
    grid-column: auto;
  }
}
</style>
