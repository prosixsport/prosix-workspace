<template>
    <div class="register-page">
        <div class="register-card">
            <div class="brand-wrap">
                <img src="/public/assets/images/P LOGO WHITE.png" class="brand-p" alt="Prosix P">
                <div class="brand-line"></div>
                <img src="/public/assets/images/PROSIX SPORTS LOGO PNG WHITE.png" class="brand-prosix" alt="Prosix">
            </div>

            <h3 class="register-title">{{ isInvite ? 'Create Account' : 'Customer Sign Up' }}</h3>
            <p class="register-subtitle">
                {{ isInvite ? 'Accept your Prosix invitation' : 'Request access to Prosix CRM' }}
            </p>

            <div v-if="error" class="alert-box">{{ error }}</div>
            <div v-if="success" class="success-box">{{ success }}</div>

            <div class="field-group">
                <label>Name *</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input v-model.trim="form.name" type="text" placeholder="Your name" autocomplete="name">
                </div>
            </div>

            <div class="field-group">
                <label>Email Address *</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input v-model.trim="form.email" type="email" placeholder="you@example.com" :readonly="isInvite && Boolean($route.query.email)" autocomplete="email">
                </div>
            </div>

            <template v-if="!isInvite">
                <div class="field-group">
                    <label>Phone *</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-phone input-icon"></i>
                        <input v-model.trim="form.phone" type="tel" placeholder="Phone number" autocomplete="tel">
                    </div>
                </div>

                <div class="field-group">
                    <label>Company</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-building input-icon"></i>
                        <input v-model.trim="form.company" type="text" placeholder="Company name" autocomplete="organization">
                    </div>
                </div>

                <div class="field-group">
                    <label>Address *</label>
                    <div class="input-wrap textarea-wrap">
                        <i class="fa-solid fa-location-dot input-icon textarea-icon"></i>
                        <textarea v-model.trim="form.address" placeholder="Complete address" autocomplete="street-address"></textarea>
                    </div>
                </div>
            </template>

            <div class="field-group">
                <label>Password *</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="Minimum 8 characters" autocomplete="new-password">
                    <button type="button" class="password-toggle" @click="showPassword = !showPassword">
                        <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="field-group">
                <label>Confirm Password *</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input v-model="form.password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" placeholder="Confirm password" autocomplete="new-password" @keyup.enter="register">
                    <button type="button" class="password-toggle" @click="showConfirmPassword = !showConfirmPassword">
                        <i :class="showConfirmPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                    </button>
                </div>
            </div>

            <button type="button" class="register-btn" :disabled="loading || Boolean(success)" @click="register">
                <span v-if="loading"><i class="fa-solid fa-spinner fa-spin me-1"></i> Submitting...</span>
                <span v-else>{{ isInvite ? 'Create Account' : 'Submit Request' }} <i class="fa-solid fa-arrow-right ms-1"></i></span>
            </button>

            <p v-if="!isInvite" class="approval-note">
                Your request will be reviewed within 24 hours. You will receive an email after approval.
            </p>

            <div class="login-link">Already have an account? <router-link to="/login">Log in</router-link></div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'Register',

    data() {
        return {
            form: { name: '', email: '', phone: '', company: '', address: '', password: '', password_confirmation: '', invite_token: '' },
            error: null,
            success: null,
            loading: false,
            showPassword: false,
            showConfirmPassword: false
        }
    },

    computed: {
        isInvite() {
            return Boolean(this.$route.query.invite)
        }
    },

    mounted() {
        this.form.invite_token = this.$route.query.invite || ''
        this.form.email = this.$route.query.email || ''
    },

    methods: {
        firstError(error) {
            const errors = error.response?.data?.errors
            return errors ? Object.values(errors).flat()[0] : null
        },

        async register() {
            this.form.email = this.form.email.trim().toLowerCase()
            this.error = null
            this.success = null

            if (!this.form.name || !this.form.email || !this.form.password || !this.form.password_confirmation) {
                this.error = 'Please fill all required fields.'
                return
            }

            if (!this.isInvite && (!this.form.phone || !this.form.address)) {
                this.error = 'Phone and address are required.'
                return
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            if (!emailPattern.test(this.form.email)) {
                this.error = 'Please enter a valid email address.'
                return
            }

            if (this.form.password.length < 8) {
                this.error = 'Password must be at least 8 characters.'
                return
            }

            if (this.form.password !== this.form.password_confirmation) {
                this.error = 'Password and confirm password do not match.'
                return
            }

            this.loading = true

            try {
                const payload = { ...this.form }
                if (!this.isInvite) delete payload.invite_token

                const res = await axios.post('/api/register', payload)
                this.success = res.data.message || 'Request submitted successfully.'

                window.setTimeout(() => {
                    this.$router.push('/login')
                }, 2500)
            } catch (error) {
                this.error = this.firstError(error) || error.response?.data?.message || 'Registration failed.'
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style scoped>
*{box-sizing:border-box}.register-page{min-height:100vh;width:100%;background:radial-gradient(circle at top left,rgba(255,255,255,.08),transparent 28%),linear-gradient(135deg,#050505 0%,#111 48%,#2e2d4d 100%);display:flex;align-items:center;justify-content:center;padding:24px}.register-card{width:460px;max-width:100%;background:rgba(255,255,255,.96);border-radius:22px;padding:34px 34px 28px;box-shadow:0 30px 90px rgba(0,0,0,.45);border:1px solid rgba(255,255,255,.18);margin:20px 0}.brand-wrap{background:#000;border-radius:18px;min-height:92px;display:flex;align-items:center;justify-content:center;gap:24px;padding:18px 22px;margin-bottom:26px}.brand-p{height:48px;width:auto;object-fit:contain}.brand-line{width:2px;height:46px;background:#fff;border-radius:10px;opacity:.9}.brand-prosix{height:34px;width:auto;object-fit:contain}.register-title{margin:0;color:#050505;font-size:26px;font-weight:900;text-align:center}.register-subtitle{margin:7px 0 22px;color:#6b7280;font-size:14px;text-align:center}.alert-box,.success-box{border-radius:10px;padding:10px 12px;font-size:13px;font-weight:600;margin-bottom:16px}.alert-box{background:#fff0f0;border:1px solid #ffb4b4;color:#b42318}.success-box{background:#ecfdf3;border:1px solid #86efac;color:#166534}.field-group{margin-bottom:16px}.field-group label{display:block;margin-bottom:7px;color:#111827;font-size:13px;font-weight:800}.input-wrap{position:relative;display:flex;align-items:center}.input-icon{position:absolute;left:14px;color:#6b7280;font-size:14px}.input-wrap input,.input-wrap textarea{width:100%;border:1.5px solid #d1d5db;border-radius:12px;padding:0 46px 0 42px;color:#111827;font-size:14px;font-weight:600;outline:none;background:#fff;transition:.2s}.input-wrap input{height:48px}.input-wrap textarea{min-height:86px;padding-top:14px;resize:vertical}.textarea-wrap{align-items:flex-start}.textarea-icon{top:16px}.input-wrap input:focus,.input-wrap textarea:focus{border-color:#000;box-shadow:0 0 0 4px rgba(0,0,0,.08)}.input-wrap input:read-only{background:#f3f4f6;cursor:not-allowed}.password-toggle{position:absolute;right:12px;width:30px;height:30px;border:none;background:#f3f4f6;color:#111;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer}.password-toggle:hover{background:#000;color:#fff}.register-btn{width:100%;height:50px;border:none;border-radius:13px;background:#000;color:#fff;font-size:15px;font-weight:900;margin-top:4px;cursor:pointer;transition:.2s}.register-btn:hover:not(:disabled){background:#2e2d4d;transform:translateY(-1px)}.register-btn:disabled{opacity:.65;cursor:not-allowed}.approval-note{margin:12px 0 0;color:#6b7280;font-size:12px;line-height:1.6;text-align:center}.login-link{margin-top:18px;padding-top:16px;border-top:1px solid #e5e7eb;color:#6b7280;font-size:13px;text-align:center}.login-link a{color:#000;font-weight:800;text-decoration:none}.login-link a:hover{text-decoration:underline}@media(max-width:500px){.register-page{padding:14px}.register-card{padding:24px 20px}.brand-wrap{gap:16px}.brand-p{height:42px}.brand-prosix{height:28px}}
</style>
