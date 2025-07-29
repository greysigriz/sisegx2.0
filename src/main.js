// ✅ Importar configuración de Axios ANTES que cualquier otra cosa
import './services/axios-config'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'

// ✅ Estilos de Swiper: deben ir antes de app.mount()
import 'swiper/css'
import 'swiper/css/effect-coverflow'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

// FontAwesome
import { library } from '@fortawesome/fontawesome-svg-core'
import { faUserShield, faDatabase, faPalette } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// Motion Plugin
import motionPlugin from './plugins/motion-plugin'

library.add(faUserShield, faDatabase, faPalette)

const app = createApp(App)

app.component('font-awesome-icon', FontAwesomeIcon)
app.use(createPinia())
app.use(router)
app.use(motionPlugin)

axios.defaults.baseURL = import.meta.env.VITE_API_URL // Puedes dejar esto o moverlo a axios-config.js si ya lo configuraste ahí

app.mount('#app')
