<template>
    <div class="register-page">
        <div class="register-card">

            <div class="brand-wrap">
                <img
                    src="/public/assets/images/P LOGO WHITE.png"
                    class="brand-p"
                    alt="Prosix P"
                >

                <div class="brand-line"></div>

                <img
                    src="/public/assets/images/PROSIX SPORTS LOGO PNG WHITE.png"
                    class="brand-prosix"
                    alt="Prosix"
                >
            </div>

            <h3 class="register-title">Create Account</h3>
            <p class="register-subtitle">Accept your Prosix invitation</p>

            <div v-if="error" class="alert-box">
                {{ error }}
            </div>

            <div class="field-group">
                <label>Name</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-user input-icon"></i>
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Your name"
                        @keyup.enter="register"
                    >
                </div>
            </div>

            <div class="field-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="you@example.com"
                        :readonly="!!$route.query.email"
                        @keyup.enter="register"
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
                        autocomplete="new-password"
                        @keyup.enter="register"
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        @click="showPassword = !showPassword"
                    >
                        <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="field-group">
                <label>Confirm Password</label>
                <div class="input-wrap">
                    <i class="fa-solid fa-lock input-icon"></i>

                    <input
                        v-model="form.password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        placeholder="Confirm password"
                        autocomplete="new-password"
                        @keyup.enter="register"
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        @click="showConfirmPassword = !showConfirmPassword"
                    >
                        <i :class="showConfirmPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                    </button>
                </div>
            </div>

            <button
                @click="register"
                class="register-btn"
                :disabled="loading"
            >
                <span v-if="loading">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i>
                    Creating...
                </span>
                <span v-else>
                    Create Account
                    <i class="fa-solid fa-arrow-right ms-1"></i>
                </span>
            </button>

            <div class="login-link">
                Already have an account?
                <router-link to="/login">Log in</router-link>
            </div>

        </div>
    </div>
</template>

<script>
import axios from 'axios'

export default {
    name: 'Register',

    mounted() {
        if (!this.$route.query.invite) {
            this.$router.push('/login')
        }

        if (this.$route.query.email) {
            this.form.email = this.$route.query.email
        }
    },

    data() {
        return {
            form: {
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
                invite_token: this.$route.query.invite || ''
            },
            error: null,
            loading: false,
            showPassword: false,
            showConfirmPassword: false
        }
    },

    methods: {
        async register() {
            if (!this.form.name || !this.form.email || !this.form.password || !this.form.password_confirmation) {
                this.error = 'Please fill all fields.'
                return
            }

            if (this.form.password !== this.form.password_confirmation) {
                this.error = 'Password and confirm password do not match.'
                return
            }

            this.loading = true
            this.error = null

            try {
                await axios.post('/api/register', this.form)
                this.$router.push('/login')
            } catch (e) {
                this.error = e.response?.data?.message || 'Registration failed'
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style scoped>
.register-page{
    min-height:100vh;
    width:100%;
    background:
        radial-gradient(circle at top left, rgba(255,255,255,0.08), transparent 28%),
        linear-gradient(135deg, #050505 0%, #111111 48%, #2e2d4d 100%);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
}

.register-card{
    width:440px;
    max-width:100%;
    background:rgba(255,255,255,0.96);
    border-radius:22px;
    padding:34px 34px 28px;
    box-shadow:0 30px 90px rgba(0,0,0,0.45);
    border:1px solid rgba(255,255,255,0.18);
}

.brand-wrap{
    background:#000;
    border-radius:18px;
    min-height:92px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:24px;
    padding:18px 22px;
    margin-bottom:26px;
}

.brand-p{
    height:48px;
    width:auto;
    object-fit:contain;
}

.brand-line{
    width:2px;
    height:46px;
    background:#fff;
    border-radius:10px;
    opacity:0.9;
}

.brand-prosix{
    height:34px;
    width:auto;
    object-fit:contain;
}

.register-title{
    margin:0;
    color:#050505;
    font-size:26px;
    font-weight:900;
    text-align:center;
}

.register-subtitle{
    margin:7px 0 22px;
    color:#6b7280;
    font-size:14px;
    text-align:center;
}

.alert-box{
    background:#fff0f0;
    border:1px solid #ffb4b4;
    color:#b42318;
    border-radius:10px;
    padding:10px 12px;
    font-size:13px;
    font-weight:600;
    margin-bottom:16px;
}

.field-group{
    margin-bottom:16px;
}

.field-group label{
    display:block;
    margin-bottom:7px;
    color:#111827;
    font-size:13px;
    font-weight:800;
}

.input-wrap{
    position:relative;
    display:flex;
    align-items:center;
}

.input-icon{
    position:absolute;
    left:14px;
    color:#6b7280;
    font-size:14px;
}

.input-wrap input{
    width:100%;
    height:48px;
    border:1.5px solid #d1d5db;
    border-radius:12px;
    padding:0 46px 0 42px;
    color:#111827;
    font-size:14px;
    font-weight:600;
    outline:none;
    background:#fff;
    transition:0.2s;
}

.input-wrap input:focus{
    border-color:#000;
    box-shadow:0 0 0 4px rgba(0,0,0,0.08);
}

.input-wrap input:read-only{
    background:#f3f4f6;
    cursor:not-allowed;
}

.password-toggle{
    position:absolute;
    right:12px;
    width:30px;
    height:30px;
    border:none;
    background:#f3f4f6;
    color:#111;
    border-radius:8px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
}

.password-toggle:hover{
    background:#000;
    color:#fff;
}

.register-btn{
    width:100%;
    height:50px;
    border:none;
    border-radius:13px;
    background:#000;
    color:#fff;
    font-size:15px;
    font-weight:900;
    margin-top:4px;
    cursor:pointer;
    transition:0.2s;
}

.register-btn:hover:not(:disabled){
    background:#2e2d4d;
    transform:translateY(-1px);
}

.register-btn:disabled{
    opacity:0.65;
    cursor:not-allowed;
}

.login-link{
    margin-top:18px;
    padding-top:16px;
    border-top:1px solid #e5e7eb;
    color:#6b7280;
    font-size:13px;
    text-align:center;
}

.login-link a{
    color:#000;
    font-weight:800;
    text-decoration:none;
}
</style>
