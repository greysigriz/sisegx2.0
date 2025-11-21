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

// FontAwesome - TODOS los iconos necesarios
import { library } from '@fortawesome/fontawesome-svg-core'
import {
  faUserShield,
  faDatabase,
  faPalette,
  // Alternativos seguros
  faQuestionCircle,     // en lugar de faCircleQuestion
  faClock,              // en lugar de faHourglassHalf
  faPhone,              // en lugar de faHeadset
  faStream,             // en lugar de faLayerGroup
  faLightbulb,          // en lugar de faBrain
  faPaperPlane,
  faCheckCircle,
  faCopy,
  faPlus,
  faPrint,
  faExclamationCircle,
  faWandMagicSparkles,  // en lugar de faMagic
  faRobot,
  faSyncAlt,            // en lugar de faRefresh
  faTimes
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// Motion Plugin
import motionPlugin from './plugins/motion-plugin'

// ✅ Agregar TODOS los iconos a la librería
library.add(
  faUserShield,
  faDatabase,
  faPalette,
  faQuestionCircle,
  faClock,
  faPhone,
  faStream,
  faLightbulb,
  faPaperPlane,
  faCheckCircle,
  faCopy,
  faPlus,
  faPrint,
  faExclamationCircle,
  faWandMagicSparkles,
  faRobot,
  faSyncAlt,
  faTimes
)

const app = createApp(App)

app.component('font-awesome-icon', FontAwesomeIcon)
app.use(createPinia())
app.use(router)
app.use(motionPlugin)

axios.defaults.baseURL = import.meta.env.VITE_API_URL

app.mount('#app')
