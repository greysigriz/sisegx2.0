import { ref, reactive, onMounted, onBeforeUnmount } from 'vue'

export default function usePetitionPage() {
  const formData = reactive({
    nombre: '',
    telefono: '',
    direccion: '',
    localidad: '',
    nivel_importancia: '',
    descripcion: '',
    red_social: '',
    dependencia_sugerida: ''
  })

  const errors = reactive({})
  const successMessage = ref('')
  const errorMessage = ref('')
  const generatedFolio = ref('')
  const userData = ref(null)
  const divisionNombre = ref('')
  const isLoading = ref(false)
  const isSearching = ref(false)
  const sugerencias = ref([])
  const showSugerencias = ref(false)
  const selectedDependencia = ref(null)
  const debounceTimer = ref(null)
  const searchStats = ref(null)

  const formFields = [
    { id: 'nombre', label: 'Nombre del solicitante', type: 'text', required: true, placeholder: 'Ingrese el nombre completo del solicitante', delay: 500 },
    { id: 'telefono', label: 'Teléfono del solicitante', type: 'tel', required: true, placeholder: 'Ej. 555-123-4567', delay: 600 },
    { id: 'direccion', label: 'Dirección', type: 'text', required: true, placeholder: 'Ingrese su dirección completa', delay: 700 },
    { id: 'localidad', label: 'Localidad', type: 'text', required: true, placeholder: 'Ingrese su localidad', delay: 800 },
    {
      id: 'nivel_importancia',
      label: 'Nivel de Importancia',
      type: 'select',
      required: true,
      delay: 900,
      options: [
        { value: '', text: 'Seleccione un nivel' },
        { value: '1', text: '🔴 Crítico (1) - Requiere atención inmediata' },
        { value: '2', text: '🟠 Alto (2) - Problema urgente' },
        { value: '3', text: '🟡 Medio (3) - Problema importante' },
        { value: '4', text: '🟢 Bajo (4) - Problema menor' },
        { value: '5', text: '🔵 Muy Bajo (5) - Consulta o sugerencia' }
      ],
      helpText: 'Seleccione el nivel que mejor describa la urgencia de su petición'
    },
    { id: 'descripcion', label: 'Descripción del problema', type: 'textarea', required: true, placeholder: 'Describa detalladamente su petición o problema...', delay: 1000 },
    { id: 'red_social', label: 'Red social del solicitante', type: 'text', required: false, placeholder: 'Ej. @usuario', delay: 1100, helpText: 'Opcional: Proporcione su usuario de redes sociales para seguimiento' }
  ]

  const infoCards = [/* ... las tarjetas como las tenías ... */]

  // Aquí pegas TODO lo que estaba dentro del `setup()` de tu script original, tal cual

  // return:
  return {
    formData, errors, successMessage, errorMessage, generatedFolio,
    userData, divisionNombre, isLoading, isSearching, sugerencias,
    showSugerencias, selectedDependencia, searchStats, formFields, infoCards,
    updateFormField, selectDependencia, selectAndApplyDependencia, clearSelectedDependencia,
    closeSugerencias, getSugerenciaLabel, getScoreClass, submitForm, resetForm
  }
}
