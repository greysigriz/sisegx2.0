<!--C:\xampp\htdocs\SISE\src\views\Login.vue-->
<template>
    <div class="login-page">
        <!-- Orbes decorativos flotantes -->
        <div class="login-orbs">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
        </div>

        <div class="login-container" :class="{ 'fade-in': mounted }">
            <!-- Patrón de puntos decorativo -->
            <div class="dot-pattern"></div>

            <div class="login-form-content">
                <div class="logo-glow-wrapper stagger-1" :class="{ 'visible': mounted }">
                    <img src="@/assets/Tramitia.jpg" alt="TramitIA">
                </div>

                <h2 class="login-title stagger-2" :class="{ 'visible': mounted }">
                    Bienvenido de nuevo
                </h2>
                <p class="login-subtitle stagger-2" :class="{ 'visible': mounted }">
                    Ingresa tus credenciales para continuar
                </p>

                <form @submit.prevent="login" :class="{ 'shake': shakeForm }">
                    <div class="input-group stagger-3" :class="{ 'visible': mounted }">
                        <label for="usuario">Usuario</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <input
                                type="text"
                                id="usuario"
                                v-model="usuario"
                                placeholder="Ingresa tu usuario"
                                required
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div class="input-group stagger-4" :class="{ 'visible': mounted }">
                        <label for="password">Contraseña</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input
                                :type="showPassword ? 'text' : 'password'"
                                id="password"
                                v-model="password"
                                placeholder="Ingresa tu contraseña"
                                required
                                autocomplete="current-password"
                            >
                            <button
                                type="button"
                                class="toggle-password"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                                tabindex="-1"
                            >
                                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-options stagger-5" :class="{ 'visible': mounted }">
                        <label class="remember-me">
                            <input type="checkbox" v-model="rememberMe">
                            Recordar sesión
                        </label>
                    </div>

                    <transition name="error-slide">
                        <div v-if="errorMessage" class="error-message">
                            <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                            {{ errorMessage }}
                        </div>
                    </transition>

                    <div class="stagger-6" :class="{ 'visible': mounted }">
                        <button type="submit" :disabled="loading" class="btn-submit">
                            <span v-if="loading" class="btn-spinner"></span>
                            <span v-if="!loading">Iniciar Sesión</span>
                            <span v-else>Verificando...</span>
                            <svg v-if="!loading" class="btn-arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { useRouter } from 'vue-router'
import authService from '@/services/auth'

export default {
    name: 'LoginView',
    setup() {
        const router = useRouter()
        return { router }
    },
    data() {
        return {
            usuario: "",
            password: "",
            errorMessage: "",
            loading: false,
            showPassword: false,
            rememberMe: false,
            shakeForm: false,
            mounted: false
        }
    },
    mounted() {
        requestAnimationFrame(() => {
            this.mounted = true
        })
    },
    methods: {
        triggerShake() {
            this.shakeForm = true
            setTimeout(() => { this.shakeForm = false }, 600)
        },
        async login() {
            this.errorMessage = ""
            this.loading = true

            try {
                const result = await authService.login(this.usuario, this.password)
                if (result.success) {
                    this.router.push('/bienvenido')
                } else {
                    this.errorMessage = result.message || "Error durante el inicio de sesión"
                    this.triggerShake()
                }
            } catch (error) {
                if (error.response) {
                    this.errorMessage = error.response.data.message || "Credenciales inválidas"
                } else {
                    this.errorMessage = "Error de conexión al servidor"
                }
                this.triggerShake()
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style src="@/assets/css/login.css"></style>
