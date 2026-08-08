<template>
  <header class="page-header">
    <!-- LEFT -->
    <div class="page-header__content">
      <div class="page-header__eyebrow">
        PROSIX WORK MANAGEMENT
      </div>

      <div class="page-header__title-row">
        <div class="page-header__mark">
          <i class="fa-solid fa-layer-group"></i>
        </div>

        <div class="page-header__titles">
          <h1>{{ title }}</h1>

          <p v-if="subtitle">
            {{ subtitle }}
          </p>
        </div>
      </div>
    </div>

    <!-- RIGHT PROFILE -->
    <button
      type="button"
      class="page-header__profile"
      title="Open profile"
      @click="$emit('profile')"
    >
      <span class="page-header__avatar">
        <img
          v-if="photo"
          :src="photo"
          alt="Profile"
        />

        <span v-else class="avatar-fallback">
          {{ initial || fallbackInitial }}
        </span>

        <span class="page-header__online-dot"></span>
      </span>

      <span class="page-header__user-copy">
        <strong>{{ user?.name || 'My Profile' }}</strong>
        <small>{{ roleLabel }}</small>
      </span>

      <span class="page-header__profile-action">
        <i class="fa-solid fa-chevron-down"></i>
      </span>
    </button>
  </header>
</template>

<script>
export default {
  name: 'PageHeader',

  emits: ['profile'],

  props: {
    title: {
      type: String,
      default: 'Factory Order Management'
    },

    subtitle: {
      type: String,
      default: 'Track production, manage orders and keep your workflow organized.'
    },

    user: {
      type: Object,
      default: () => ({})
    },

    photo: {
      type: String,
      default: ''
    },

    initial: {
      type: String,
      default: ''
    }
  },

  computed: {
    fallbackInitial() {
      return (
        this.user?.name?.trim()?.charAt(0)?.toUpperCase() || 'U'
      )
    },

    roleLabel() {
      const role = this.user?.role || ''

      const roles = {
        super_admin: 'Super Admin',
        admin: 'Admin',
        member: 'Member',
        client: 'Client'
      }

      return roles[role] || 'User'
    }
  }
}
</script>

<style scoped>
/* ==========================================
   PAGE HEADER
========================================== */

.page-header {
  width: 100%;
  height: 165px;

  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 25px;

  padding: 18px 28px;

  background: #f4f5f8;


  box-shadow: none;

  font-family: inherit;
}


/* ==========================================
   LEFT CONTENT
========================================== */

.page-header__content {
  min-width: 0;

  display: flex;
  flex-direction: column;

  justify-content: center;
}


/* small top heading */

.page-header__eyebrow {
  margin-bottom: 8px;

  color: #8a94a6;

  font-size: 11px;
  line-height: 1;

  font-weight: 900;

  letter-spacing: 2px;

  text-transform: uppercase;
}


/* title row */

.page-header__title-row {
  display: flex;
  align-items: center;

  gap: 13px;

  min-width: 0;
}


/* black icon */

.page-header__mark {
  width: 42px;
  height: 42px;

  flex: 0 0 42px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 10px;

  background: #0d1729;

  color: #ffffff;

  font-size: 16px;
}


/* title content */

.page-header__titles {
  min-width: 0;
}


.page-header__titles h1 {
  margin: 0;

  color: #0c1528;

  font-size: 24px;
  line-height: 1.15;

  font-weight: 900;

  letter-spacing: -0.4px;
}


.page-header__titles p {
  margin: 5px 0 0;

  color: #778196;

  font-size: 12px;
  line-height: 1.3;

  font-weight: 500;
}


/* ==========================================
   PROFILE CARD
========================================== */

.page-header__profile {
  width: 220px;
  height: 60px;

  flex: 0 0 220px;

  display: flex;
  align-items: center;

  gap: 10px;

  padding: 7px 12px;

  border: 1px solid #d8dde6;

  border-radius: 13px;

  background: #f4f5f8;

  color: #0b1426;

  cursor: pointer;

  text-align: left;

  transition:
    border-color 0.18s ease,
    background 0.18s ease;
}


.page-header__profile:hover {
  border-color: #aeb7c5;
  background: #ffffff;
}


/* ==========================================
   AVATAR
========================================== */

.page-header__avatar {
  position: relative;

  width: 44px;
  height: 44px;

  flex: 0 0 44px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: #e7eaf0;

  overflow: visible;
}


.page-header__avatar img {
  width: 44px;
  height: 44px;

  display: block;

  border-radius: 50%;

  object-fit: cover;

  border: 1px solid #d6dae2;
}


.avatar-fallback {
  width: 44px;
  height: 44px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: #111827;

  color: #ffffff;

  font-size: 15px;

  font-weight: 900;
}


/* online */

.page-header__online-dot {
  position: absolute;

  right: -1px;
  bottom: 2px;

  width: 9px;
  height: 9px;

  border: 2px solid #f4f5f8;

  border-radius: 50%;

  background: #22c55e;
}


/* ==========================================
   USER TEXT
========================================== */

.page-header__user-copy {
  min-width: 0;

  flex: 1;

  display: flex;
  flex-direction: column;

  gap: 3px;
}


.page-header__user-copy strong {
  display: block;

  overflow: hidden;

  color: #0b1426;

  font-size: 13px;

  font-weight: 900;

  white-space: nowrap;
  text-overflow: ellipsis;
}


.page-header__user-copy small {
  display: block;

  overflow: hidden;

  color: #7a8496;

  font-size: 10px;

  font-weight: 500;

  white-space: nowrap;
  text-overflow: ellipsis;
}


/* arrow */

.page-header__profile-action {
  width: 22px;
  height: 22px;

  flex: 0 0 22px;

  display: flex;
  align-items: center;
  justify-content: center;

  color: #647084;

  font-size: 10px;
}


/* ==========================================
   TABLET
========================================== */

@media (max-width: 900px) {

  .page-header {
    height: 95px;

    padding: 15px 18px;
  }

  .page-header__eyebrow {
    font-size: 9px;
  }

  .page-header__titles h1 {
    font-size: 20px;
  }

  .page-header__titles p {
    font-size: 11px;
  }

  .page-header__profile {
    width: 190px;
    flex-basis: 190px;
  }
}


/* ==========================================
   MOBILE
========================================== */

@media (max-width: 650px) {

  .page-header {
    height: auto;

    min-height: 90px;

    padding: 14px;

    gap: 12px;

    border-radius: 10px;
  }

  .page-header__eyebrow {
    display: none;
  }

  .page-header__mark {
    width: 38px;
    height: 38px;

    flex-basis: 38px;
  }

  .page-header__titles h1 {
    font-size: 17px;
  }

  .page-header__titles p {
    display: none;
  }

  .page-header__profile {
    width: 52px;
    height: 52px;

    flex: 0 0 52px;

    padding: 4px;

    justify-content: center;

    border: none;

    background: transparent;
  }

  .page-header__user-copy,
  .page-header__profile-action {
    display: none;
  }
}
</style>
