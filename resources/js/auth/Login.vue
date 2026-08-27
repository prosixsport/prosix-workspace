<template>
    <div class="login-page">
        <div class="login-card">
            <div class="brand-wrap">
                <img src="/public/assets/images/P LOGO WHITE.png" class="brand-p" alt="Prosix P">
                <div class="brand-line"></div>
                <img src="/public/assets/images/PROSIX SPORTS LOGO PNG WHITE.png" class="brand-prosix" alt="Prosix">
            </div>

            <h3 class="login-title">Welcome Back</h3>
            <p class="login-subtitle">Login to manage your Prosix orders</p>

            <div v-if="error" class="alert-box">{{ error }}</div>

            <div class="field-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input
                        v-model.trim="form.email"
                        type="email"
                        placeholder="crm@prosix.com"
                        autocomplete="email"
                        @keyup.enter="login"
                    >
                </div>
            </div>

            <div class="field-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="Enter password"
                        autocomplete="current-password"
                        @keyup.enter="login"
                    >
                    <button type="button" class="password-toggle" @click="showPassword = !showPassword">
                        <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                    </button>
                </div>
            </div>

            <button type="button" class="login-btn" :disabled="loading" @click="login">
                <span v-if="loading"><i class="fa-solid fa-spinner fa-spin me-1"></i> Logging in...</span>
                <span v-else>Log In <i class="fa-solid fa-arrow-right ms-1"></i></span>
            </button>

            <div class="login-hint">
                Don’t have an account?
                <router-link to="/register">Sign up</router-link>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'Login',

    data() {
        return {
            form: { email: '', password: '' },
            error: null,
            loading: false,
            showPassword: false
        }
    },

    methods: {
        firstError(error) {
            const errors = error.response?.data?.errors
            return errors ? Object.values(errors).flat()[0] : null
        },

        async login() {
            this.form.email = this.form.email.trim().toLowerCase()

            if (!this.form.email || !this.form.password) {
                this.error = 'Email aur password required hain.'
                return
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            if (!emailPattern.test(this.form.email)) {
                this.error = 'Please enter a valid email address.'
                return
            }

            this.loading = true
            this.error = null

            try {
                const res = await axios.post('/api/login', this.form)
                localStorage.setItem('token', res.data.token)
                localStorage.setItem('user', JSON.stringify(res.data.user))
                await this.$router.push('/dashboard')
            } catch (error) {
                this.error = this.firstError(error) || error.response?.data?.message || 'Login failed.'
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style scoped>
*{box-sizing:border-box}.login-page{min-height:100vh;width:100%;background:radial-gradient(circle at top left,rgba(255,255,255,.08),transparent 28%),linear-gradient(135deg,#050505 0%,#111 48%,#2e2d4d 100%);display:flex;align-items:center;justify-content:center;padding:24px}.login-card{width:430px;max-width:100%;background:rgba(255,255,255,.96);border-radius:22px;padding:34px 34px 28px;box-shadow:0 30px 90px rgba(0,0,0,.45);border:1px solid rgba(255,255,255,.18)}.brand-wrap{background:#000;border-radius:18px;min-height:92px;display:flex;align-items:center;justify-content:center;gap:24px;padding:18px 22px;margin-bottom:26px}.brand-p{height:48px;width:auto;object-fit:contain}.brand-line{width:2px;height:46px;background:#fff;border-radius:10px;opacity:.9}.brand-prosix{height:34px;width:auto;object-fit:contain}.login-title{margin:0;color:#050505;font-size:26px;font-weight:900;text-align:center;letter-spacing:-.5px}.login-subtitle{margin:7px 0 22px;color:#6b7280;font-size:14px;text-align:center}.alert-box{background:#fff0f0;border:1px solid #ffb4b4;color:#b42318;border-radius:10px;padding:10px 12px;font-size:13px;font-weight:600;margin-bottom:16px}.field-group{margin-bottom:16px}.field-group label{display:block;margin-bottom:7px;color:#111827;font-size:13px;font-weight:800}.input-wrap{position:relative;display:flex;align-items:center}.input-icon{position:absolute;left:14px;color:#6b7280;font-size:14px}.input-wrap input{width:100%;height:48px;border:1.5px solid #d1d5db;border-radius:12px;padding:0 46px 0 42px;color:#111827;font-size:14px;font-weight:600;outline:none;background:#fff;transition:.2s}.input-wrap input:focus{border-color:#000;box-shadow:0 0 0 4px rgba(0,0,0,.08)}.password-toggle{position:absolute;right:12px;width:30px;height:30px;border:none;background:#f3f4f6;color:#111;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer}.password-toggle:hover{background:#000;color:#fff}.login-btn{width:100%;height:50px;border:none;border-radius:13px;background:#000;color:#fff;font-size:15px;font-weight:900;margin-top:4px;cursor:pointer;transition:.2s}.login-btn:hover:not(:disabled){background:#2e2d4d;transform:translateY(-1px)}.login-btn:disabled{opacity:.65;cursor:not-allowed}.login-hint{margin-top:18px;padding-top:16px;border-top:1px solid #e5e7eb;color:#6b7280;font-size:13px;line-height:1.7;text-align:center}.login-hint a{color:#000;font-weight:900;text-decoration:none}.login-hint a:hover{text-decoration:underline}@media(max-width:500px){.login-page{padding:14px}.login-card{padding:24px 20px}.brand-wrap{gap:16px}.brand-p{height:42px}.brand-prosix{height:28px}}
</style>
