<template>
  <header class="page-header">
    <div class="page-header__copy">
      <span class="page-header__eyebrow">{{ eyebrow }}</span>

      <div class="page-header__title-row">
        <div class="page-header__mark">
          <i class="fa-solid fa-layer-group"></i>
        </div>

        <div class="page-header__titles">
          <h1>{{ title }}</h1>
          <p v-if="subtitle">{{ subtitle }}</p>
        </div>
      </div>
    </div>

    <button
      type="button"
      class="page-header__profile"
      title="Open profile"
      @click="goToProfile"
    >
      <span class="page-header__avatar">
        <img
          v-if="photo"
          :src="photo"
          alt="Profile"
        />

        <span v-else>
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

  props: {
    eyebrow: {
      type: String,
      default: 'WORK MANAGEMENT'
    },

    title: {
      type: String,
      default: 'Workspace'
    },

    subtitle: {
      type: String,
      default: ''
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

  methods: {
    goToProfile() {
      if (this.$route?.path === '/profile') return

      this.$router.push('/profile')
    }
  },

  computed: {
    fallbackInitial() {
      return String(this.user?.name || 'U')
        .trim()
        .charAt(0)
        .toUpperCase()
    },

    roleLabel() {
      const role = String(this.user?.role || 'User')
        .replace(/_/g, ' ')
        .trim()

      return role.replace(/\b\w/g, char => char.toUpperCase())
    }
  }
}
</script>

<style scoped>
.page-header {
  width: 100%;
  min-height: 104px;
  padding: 20px 28px;

  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;

  background: #ffffff;
  border: 1px solid #e2e5eb;
  border-radius: 14px;
  box-shadow: 0 4px 18px rgba(15, 23, 42, .045);
}

.page-header__copy {
  min-width: 0;
}

.page-header__eyebrow {
  display: block;
  margin-bottom: 8px;

  color: #8a919d;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .16em;
  text-transform: uppercase;
}

.page-header__title-row {
  display: flex;
  align-items: center;
  gap: 13px;
}

.page-header__mark {
  width: 42px;
  height: 42px;
  flex: 0 0 42px;

  display: grid;
  place-items: center;

  background: #111827;
  color: #ffffff;
  border-radius: 10px;
  font-size: 15px;
}

.page-header__titles {
  min-width: 0;
}

.page-header__titles h1 {
  margin: 0;
  color: #111827;
  font-size: 22px;
  line-height: 1.15;
  font-weight: 800;
  letter-spacing: -.025em;
}

.page-header__titles p {
  margin: 5px 0 0;
  color: #7a8491;
  font-size: 11px;
  line-height: 1.45;
}

.page-header__profile {
  min-width: 220px;
  max-width: 280px;
  height: 58px;
  padding: 6px 10px 6px 7px;

  display: grid;
  grid-template-columns: 44px minmax(0, 1fr) 28px;
  align-items: center;
  gap: 10px;

  background: #f8f9fb;
  border: 1px solid #e1e5ea;
  border-radius: 12px;

  text-align: left;
  cursor: pointer;
  transition:
    background .16s ease,
    border-color .16s ease,
    box-shadow .16s ease,
    transform .16s ease;
}

.page-header__profile:hover {
  background: #ffffff;
  border-color: #cbd2db;
  box-shadow: 0 8px 22px rgba(15, 23, 42, .08);
  transform: translateY(-1px);
}

.page-header__avatar {
  position: relative;
  width: 44px;
  height: 44px;

  display: grid;
  place-items: center;

  overflow: visible;
  border-radius: 50%;
  background: #111827;
  color: #ffffff;

  font-size: 13px;
  font-weight: 800;
}

.page-header__avatar img {
  width: 44px;
  height: 44px;
  display: block;

  object-fit: cover;
  object-position: center;
  border-radius: 50%;
  border: 2px solid #ffffff;
  box-shadow: 0 0 0 1px #d8dde5;
}

.page-header__online-dot {
  position: absolute;
  right: 0;
  bottom: 1px;

  width: 10px;
  height: 10px;

  background: #22c55e;
  border: 2px solid #ffffff;
  border-radius: 50%;
}

.page-header__user-copy {
  min-width: 0;
  display: block;
}

.page-header__user-copy strong,
.page-header__user-copy small {
  display: block;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.page-header__user-copy strong {
  color: #111827;
  font-size: 12px;
  font-weight: 800;
}

.page-header__user-copy small {
  margin-top: 3px;
  color: #8a919d;
  font-size: 9px;
  font-weight: 600;
}

.page-header__profile-action {
  width: 28px;
  height: 28px;

  display: grid;
  place-items: center;

  color: #667085;
  border-radius: 8px;
}

.page-header__profile-action i {
  font-size: 9px;
}

/* Dark mode when parent page uses .theme-dark */
:global(.theme-dark) .page-header {
  background: #111827;
  border-color: #2f3b4d;
}

:global(.theme-dark) .page-header__mark {
  background: #ffffff;
  color: #111827;
}

:global(.theme-dark) .page-header__titles h1,
:global(.theme-dark) .page-header__user-copy strong {
  color: #ffffff;
}

:global(.theme-dark) .page-header__eyebrow,
:global(.theme-dark) .page-header__titles p,
:global(.theme-dark) .page-header__user-copy small,
:global(.theme-dark) .page-header__profile-action {
  color: #aeb8c7;
}

:global(.theme-dark) .page-header__profile {
  background: #182132;
  border-color: #334155;
}

@media (max-width: 780px) {
  .page-header {
    padding: 16px;
    min-height: auto;
  }

  .page-header__titles h1 {
    font-size: 18px;
  }

  .page-header__titles p {
    display: none;
  }

  .page-header__profile {
    min-width: 54px;
    width: 54px;
    grid-template-columns: 44px;
    padding: 5px;
  }

  .page-header__user-copy,
  .page-header__profile-action {
    display: none;
  }
}
</style>
