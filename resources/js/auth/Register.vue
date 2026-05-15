<template>
    <div class="min-vh-100 d-flex align-items-center justify-content-center" style="background:#f6f7fb;">
        <div class="text-center" style="width:420px;">
            <div class="mb-4">
                <h2 class="fw-bold" style="color:#ff3d57;">monday</h2>
                <p class="text-muted">Create your account</p>
            </div>
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Name</label>
                        <input v-model="form.name" type="text" class="form-control form-control-lg" placeholder="Your name">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Email</label>
                        <input v-model="form.email" type="email" class="form-control form-control-lg" placeholder="you@example.com" :readonly="!!$route.query.email">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Password</label>
                        <input v-model="form.password" type="password" class="form-control form-control-lg" placeholder="••••••••" autocomplete="new-password">
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Confirm Password</label>
                        <input v-model="form.password_confirmation" type="password" class="form-control form-control-lg" placeholder="••••••••" autocomplete="new-password">
                    </div>
                    <button @click="register" class="btn btn-lg w-100 text-white fw-bold" :disabled="loading"
                        style="background:#ff3d57; border:none;">
                        {{ loading ? 'Creating...' : 'Create Account' }}
                    </button>
                    <hr class="my-3">
                    <p class="mb-0 text-muted">
                        Already have an account?
                        <router-link to="/login" class="fw-semibold text-decoration-none" style="color:#ff3d57;">Log in</router-link>
                    </p>
                </div>
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
        // ✅ URL se email auto set karo
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
            loading: false
        }
    },
    methods: {
       async register() {
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
