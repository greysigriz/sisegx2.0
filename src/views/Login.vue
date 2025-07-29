<!--C:\xampp\htdocs\SISE\src\views\Login.vue-->
<template>
    <div class="login-page">
        <div class="login-container">
            <img src="@/assets/Tramitia.jpg" alt="TramitIA">
            <h2>Iniciar Sesión</h2>

            <form @submit.prevent="login">
                <div class="input-group">
                    <label for="usuario">Usuario</label>
                    <input
                        type="text"
                        id="usuario"
                        v-model="usuario"
                        placeholder="Ingresa tu usuario"
                        required
                    >
                </div>

                <div class="input-group">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        v-model="password"
                        placeholder="Ingresa tu contraseña"
                        required
                    >
                </div>

                <div v-if="errorMessage" class="error-message">
                    {{ errorMessage }}
                </div>

                <div v-if="loading" class="loading-indicator">
                    Verificando credenciales...
                </div>

                <button type="submit" :disabled="loading">Iniciar Sesión</button>
            </form>
        </div>
    </div>
</template>

<script>
import { useRouter } from 'vue-router'
import authService from '@/services/auth' // ✅ Cambio agregado

export default {
    setup() {
        const router = useRouter()
        return { router }
    },
    data() {
        return {
            usuario: "",
            password: "",
            errorMessage: "",
            loading: false
        }
    },
    methods: {
        async login() {
            this.errorMessage = ""
            this.loading = true

            try {
                const result = await authService.login(this.usuario, this.password) // ✅ Uso de authService
                if (result.success) {
                    this.router.push('/bienvenido')
                } else {
                    this.errorMessage = result.message || "Error durante el inicio de sesión"
                }
            } catch (error) {
                if (error.response) {
                    this.errorMessage = error.response.data.message || "Credenciales inválidas"
                } else {
                    this.errorMessage = "Error de conexión al servidor"
                }
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style src="@/assets/css/login.css"></style>
