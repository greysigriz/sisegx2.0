<template>
  <!-- Estado de carga -->
  <div v-if="loading" class="gallery-loading">
    <div class="loading-spinner"></div>
    <p>Cargando imágenes...</p>
  </div>

  <!-- Estado de error -->
  <div v-else-if="error" class="gallery-error">
    <div class="error-icon">
      <font-awesome-icon icon="fa-solid fa-exclamation-triangle" />
    </div>
    <div class="error-text">
      <h4>Error al cargar imágenes</h4>
      <p>{{ error }}</p>
    </div>
  </div>

  <!-- Galería con imágenes -->
  <div v-else-if="finalImages && finalImages.length > 0" class="image-gallery">
    <!-- Vista en grid -->
    <div v-if="layout === 'grid'" class="gallery-grid" :class="`grid-${columns}`">
      <div
        v-for="(image, index) in finalImages"
        :key="image.id"
        class="gallery-item"
        @click="openModal(index)"
      >
        <img
          :src="image.url_acceso"
          :alt="image.filename_original"
          class="gallery-image"
          @error="onImageError"
        />
        <div class="image-overlay">
          <font-awesome-icon icon="fa-solid fa-expand" />
        </div>
        <div v-if="showInfo" class="image-info">
          <div class="image-name">{{ truncateName(image.filename_original) }}</div>
          <div class="image-meta">
            <span class="file-size">{{ formatFileSize(image.file_size) }}</span>
            <span v-if="image.width && image.height" class="dimensions">
              {{ image.width }}×{{ image.height }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Vista en lista -->
    <div v-else-if="layout === 'list'" class="gallery-list">
      <div
        v-for="(image, index) in finalImages"
        :key="image.id"
        class="list-item"
        @click="openModal(index)"
      >
        <img
          :src="image.url_acceso"
          :alt="image.filename_original"
          class="list-image"
          @error="onImageError"
        />
        <div class="list-info">
          <div class="image-name">{{ image.filename_original }}</div>
          <div class="image-meta">
            <span class="file-size">{{ formatFileSize(image.file_size) }}</span>
            <span v-if="image.width && image.height" class="dimensions">
              {{ image.width }} × {{ image.height }}
            </span>
            <span class="upload-date">{{ formatDate(image.fecha_subida) }}</span>
            <span v-if="image.usuario_nombre" class="uploaded-by">
              por {{ image.usuario_nombre }}
            </span>
          </div>
        </div>
        <div class="list-actions">
          <button
            @click.stop="downloadImage(image)"
            class="action-btn download"
            title="Descargar imagen"
          >
            <font-awesome-icon icon="fa-solid fa-download" />
          </button>
          <button
            v-if="allowDelete"
            @click.stop="deleteImage(image, index)"
            class="action-btn delete"
            title="Eliminar imagen"
          >
            <font-awesome-icon icon="fa-solid fa-trash" />
          </button>
        </div>
      </div>
    </div>

    <!-- Vista en carrusel -->
    <div v-else-if="layout === 'carousel'" class="gallery-carousel">
      <div class="carousel-container">
        <div class="carousel-track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
          <div
            v-for="(image, index) in images"
            :key="image.id"
            class="carousel-slide"
            @click="openModal(index)"
          >
            <img
              :src="image.url_acceso"
              :alt="image.filename_original"
              class="carousel-image"
              @error="onImageError"
            />
            <div v-if="showInfo" class="carousel-info">
              <div class="image-name">{{ image.filename_original }}</div>
              <div class="image-meta">{{ formatFileSize(image.file_size) }}</div>
            </div>
          </div>
        </div>

        <!-- Controles del carrusel -->
        <button
          v-if="finalImages.length > 1"
          @click="prevSlide"
          class="carousel-btn prev"
          :disabled="currentSlide === 0"
        >
          <font-awesome-icon icon="fa-solid fa-chevron-left" />
        </button>
        <button
          v-if="finalImages.length > 1"
          @click="nextSlide"
          class="carousel-btn next"
          :disabled="currentSlide === finalImages.length - 1"
        >
          <font-awesome-icon icon="fa-solid fa-chevron-right" />
        </button>
      </div>

      <!-- Indicadores -->
      <div v-if="finalImages.length > 1" class="carousel-indicators">
        <button
          v-for="(image, index) in finalImages"
          :key="`indicator-${index}`"
          @click="goToSlide(index)"
          class="indicator"
          :class="{ active: currentSlide === index }"
        ></button>
      </div>
    </div>

    <!-- Modal de imagen ampliada -->
    <div v-if="modalVisible" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <!-- Header del modal -->
        <div class="modal-header">
          <div class="modal-title">
            <h3>{{ currentImage?.filename_original }}</h3>
            <div class="modal-counter">{{ modalIndex + 1 }} de {{ finalImages.length }}</div>
          </div>
          <button @click="closeModal" class="close-btn">
            <font-awesome-icon icon="fa-solid fa-times" />
          </button>
        </div>

        <!-- Contenido del modal -->
        <div class="modal-body">
          <div class="modal-image-container">
            <img
              :src="currentImage?.url_acceso"
              :alt="currentImage?.filename_original"
              class="modal-image"
              @error="onImageError"
            />

            <!-- Navegación en modal -->
            <button
              v-if="images.length > 1"
              @click="prevModalImage"
              class="modal-nav-btn prev"
              :disabled="modalIndex === 0"
            >
              <font-awesome-icon icon="fa-solid fa-chevron-left" />
            </button>
            <button
              v-if="images.length > 1"
              @click="nextModalImage"
              class="modal-nav-btn next"
              :disabled="modalIndex === images.length - 1"
            >
              <font-awesome-icon icon="fa-solid fa-chevron-right" />
            </button>
          </div>
        </div>

        <!-- Footer del modal -->
        <div class="modal-footer">
          <div class="image-details">
            <div class="detail-group">
              <span class="label">Tamaño:</span>
              <span class="value">{{ formatFileSize(currentImage?.file_size) }}</span>
            </div>
            <div v-if="currentImage?.width && currentImage?.height" class="detail-group">
              <span class="label">Dimensiones:</span>
              <span class="value">{{ currentImage.width }} × {{ currentImage.height }}</span>
            </div>
            <div class="detail-group">
              <span class="label">Tipo:</span>
              <span class="value">{{ currentImage?.mime_type }}</span>
            </div>
            <div class="detail-group">
              <span class="label">Subida:</span>
              <span class="value">{{ formatDate(currentImage?.fecha_subida) }}</span>
            </div>
            <div v-if="currentImage?.usuario_nombre" class="detail-group">
              <span class="label">Por:</span>
              <span class="value">{{ currentImage.usuario_nombre }}</span>
            </div>
          </div>
          <div class="modal-actions">
            <button
              @click="downloadImage(currentImage)"
              class="btn-download"
              title="Descargar imagen"
            >
              <font-awesome-icon icon="fa-solid fa-download" />
              Descargar
            </button>
            <button
              v-if="allowDelete"
              @click="deleteImage(currentImage, modalIndex)"
              class="btn-delete"
              title="Eliminar imagen"
            >
              <font-awesome-icon icon="fa-solid fa-trash" />
              Eliminar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Estado vacío -->
  <div v-else class="gallery-empty">
    <div class="empty-icon">
      <i class="fas fa-images"></i>
    </div>
    <div class="empty-text">
      <h4>No hay imágenes</h4>
      <p>{{ emptyMessage || 'No se han subido imágenes aún' }}</p>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import axios from 'axios'

export default {
  name: 'ImageGallery',
  props: {
    images: {
      type: Array,
      default: () => []
    },
    // ✅ NUEVO: Props para carga automática
    entidadTipo: {
      type: String,
      default: null
    },
    entidadId: {
      type: [Number, String],
      default: null
    },
    readonly: {
      type: Boolean,
      default: false
    },
    showUpload: {
      type: Boolean,
      default: true
    },
    compact: {
      type: Boolean,
      default: false
    },
    layout: {
      type: String,
      default: 'grid', // 'grid', 'list', 'carousel'
      validator: (value) => ['grid', 'list', 'carousel'].includes(value)
    },
    columns: {
      type: Number,
      default: 3,
      validator: (value) => value >= 1 && value <= 6
    },
    showInfo: {
      type: Boolean,
      default: true
    },
    allowDelete: {
      type: Boolean,
      default: false
    },
    emptyMessage: {
      type: String,
      default: ''
    }
  },
  emits: ['delete-image', 'download-image'],
  setup(props, { emit }) {
    // Estado del modal
    const modalVisible = ref(false)
    const modalIndex = ref(0)

    // Estado del carrusel
    const currentSlide = ref(0)

    // ✅ NUEVO: Estado para carga automática
    const loadedImages = ref([])
    const loading = ref(false)
    const error = ref(null)

    // ✅ NUEVO: Computed para imágenes finales
    const finalImages = computed(() => {
      // Si se pasan imágenes como props, usar esas
      if (props.images && props.images.length > 0) {
        return props.images
      }
      // Si no, usar las imágenes cargadas automáticamente
      return loadedImages.value
    })

    // Computed
    const currentImage = computed(() => {
      return finalImages.value[modalIndex.value] || null
    })

    // ✅ NUEVA: Función para cargar imágenes automáticamente con control mínimo
    const cargarImagenes = async () => {
      if (!props.entidadTipo || !props.entidadId) {
        return
      }

      try {
        loading.value = true
        error.value = null

        const response = await axios.get('imagenes.php', {
          params: {
            entidad_tipo: props.entidadTipo,
            entidad_id: props.entidadId
          },
          // No usar AbortController por ahora para debugging
          timeout: 10000
        })

        console.log('🔍 Respuesta de la API de imágenes:', response.data)

        if (response.data.success) {
          loadedImages.value = response.data.imagenes || []
          console.log(`✅ Cargadas ${loadedImages.value.length} imágenes para ${props.entidadTipo} #${props.entidadId}`)
        } else {
          throw new Error(response.data.message || 'Error al cargar imágenes')
        }
      } catch (err) {
        console.error('❌ Error cargando imágenes:', err)
        error.value = err.message || 'Error al cargar imágenes'
        loadedImages.value = []
      } finally {
        loading.value = false
      }
    }

    // Watchers
    watch(() => finalImages.value.length, (newLength) => {
      if (modalIndex.value >= newLength) {
        modalIndex.value = Math.max(0, newLength - 1)
      }
      if (currentSlide.value >= newLength) {
        currentSlide.value = Math.max(0, newLength - 1)
      }
    })

    // ✅ NUEVO: Watcher directo sin debounce para evitar duplicados
    watch(
      () => [props.entidadTipo, props.entidadId],
      () => {
        if (props.entidadTipo && props.entidadId) {
          cargarImagenes()
        }
      },
      { immediate: true }
    )

    // Métodos del modal
    const openModal = (index) => {
      modalIndex.value = index
      modalVisible.value = true
      document.body.style.overflow = 'hidden' // Prevenir scroll del body
    }

    const closeModal = () => {
      modalVisible.value = false
      document.body.style.overflow = 'auto'
    }

    const nextModalImage = () => {
      if (modalIndex.value < finalImages.value.length - 1) {
        modalIndex.value++
      }
    }

    const prevModalImage = () => {
      if (modalIndex.value > 0) {
        modalIndex.value--
      }
    }

    // Métodos del carrusel
    const nextSlide = () => {
      if (currentSlide.value < finalImages.value.length - 1) {
        currentSlide.value++
      }
    }

    const prevSlide = () => {
      if (currentSlide.value > 0) {
        currentSlide.value--
      }
    }

    const goToSlide = (index) => {
      currentSlide.value = index
    }

    // Métodos de utilidad
    const formatFileSize = (bytes) => {
      if (!bytes) return 'N/A'
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(1024))
      return Math.round((bytes / Math.pow(1024, i)) * 100) / 100 + ' ' + sizes[i]
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const truncateName = (name, maxLength = 20) => {
      if (!name) return 'Sin nombre'
      if (name.length <= maxLength) return name
      const ext = name.split('.').pop()
      const nameWithoutExt = name.substring(0, name.lastIndexOf('.'))
      const truncated = nameWithoutExt.substring(0, maxLength - ext.length - 3)
      return `${truncated}...${ext}`
    }

    // Métodos de acción
    const downloadImage = async (image) => {
      if (!image || !image.url_acceso) return

      const url = image.url_acceso
      const nombreArchivo = image.filename_original || 'imagen.jpg'

      console.log('📥 Intentando descargar imagen:', { url, nombreArchivo })

      try {
        // Extraer la ruta relativa del archivo (eliminar barras iniciales también)
        let urlRelativa = url.replace(/^https?:\/\/[^/]+/, '').replace(/^\/SISEE/, '')
        urlRelativa = urlRelativa.replace(/^\//, '') // Quitar barra inicial si existe

        console.log('📁 Ruta relativa extraída:', urlRelativa)

        // Descargar usando el endpoint PHP (axios usa baseURL automáticamente)
        // withCredentials: false para evitar conflicto con CORS wildcard
        // skipAuthToken: true para no enviar header de autenticación
        const response = await axios.get('descargar-imagen.php', {
          params: {
            archivo: urlRelativa,
            nombre: nombreArchivo
          },
          responseType: 'blob',
          timeout: 30000,
          withCredentials: false,  // No enviar cookies para evitar error CORS
          skipAuthToken: true      // No enviar header X-Auth-Token
        })

        console.log('📦 Respuesta recibida, creando blob...')

        // Crear blob URL
        const blob = response.data
        const blobUrl = window.URL.createObjectURL(blob)

        // Crear enlace temporal y forzar descarga
        const link = document.createElement('a')
        link.href = blobUrl
        link.download = nombreArchivo
        link.style.display = 'none'

        // Agregar al DOM, hacer clic y remover
        document.body.appendChild(link)
        link.click()

        // Limpiar después de un momento
        setTimeout(() => {
          if (link.parentNode) {
            document.body.removeChild(link)
          }
          window.URL.revokeObjectURL(blobUrl)
        }, 100)

        console.log('✅ Imagen descargada correctamente')
        emit('download-image', image)

      } catch (error) {
        console.error('❌ Error al descargar imagen:', error)
        console.error('Detalles del error:', error.response?.data)
        console.error('URL solicitada:', error.config?.url)

        // Mostrar mensaje más específico
        const mensaje = error.response?.data?.message || error.message || 'Error desconocido'
        alert(`Error al descargar: ${mensaje}`)
      }
    }

    const deleteImage = (image, index) => {
      if (!confirm('¿Está seguro de que desea eliminar esta imagen?')) return

      emit('delete-image', { image, index })

      // Ajustar modal si está abierto
      if (modalVisible.value) {
        if (props.images.length <= 1) {
          closeModal()
        } else if (modalIndex.value >= props.images.length - 1) {
          modalIndex.value = props.images.length - 2
        }
      }

      // Ajustar carrusel
      if (currentSlide.value >= props.images.length - 1) {
        currentSlide.value = Math.max(0, props.images.length - 2)
      }
    }

    const onImageError = (e) => {
      console.error('Error loading image:', e.target.src)

      // Evitar bucle infinito de errores
      if (e.target.hasAttribute('data-error-handled')) {
        return
      }

      // Marcar que ya se manejó el error
      e.target.setAttribute('data-error-handled', 'true')

      // Usar un SVG placeholder simple
      const placeholderSvg = `data:image/svg+xml;base64,${btoa(`
        <svg width="300" height="200" xmlns="http://www.w3.org/2000/svg">
          <rect width="100%" height="100%" fill="#f0f0f0"/>
          <text x="50%" y="50%" text-anchor="middle" dy="0.3em" font-family="Arial, sans-serif" font-size="16" fill="#999">
            Imagen no disponible
          </text>
        </svg>
      `)}`

      e.target.src = placeholderSvg
    }

    // Event listeners para navegación con teclado
    const handleKeydown = (e) => {
      if (!modalVisible.value) return

      switch (e.key) {
        case 'Escape':
          closeModal()
          break
        case 'ArrowLeft':
          prevModalImage()
          break
        case 'ArrowRight':
          nextModalImage()
          break
      }
    }

    // Setup de event listeners
    document.addEventListener('keydown', handleKeydown)

    // Cleanup al desmontar el componente
    onBeforeUnmount(() => {
      document.removeEventListener('keydown', handleKeydown)
    })

    return {
      // Estado
      modalVisible,
      modalIndex,
      currentSlide,
      loading,
      error,
      loadedImages,

      // Computed
      currentImage,
      finalImages,

      // Métodos
      openModal,
      closeModal,
      nextModalImage,
      prevModalImage,
      nextSlide,
      prevSlide,
      goToSlide,
      formatFileSize,
      formatDate,
      truncateName,
      downloadImage,
      deleteImage,
      onImageError
    }
  }
}
</script>

<style scoped>
.image-gallery {
  width: 100%;
}

/* Grid Layout */
.gallery-grid {
  display: grid;
  gap: 15px;
  margin-bottom: 1rem;
}

.grid-1 { grid-template-columns: 1fr; }
.grid-2 { grid-template-columns: repeat(2, 1fr); }
.grid-3 { grid-template-columns: repeat(3, 1fr); }
.grid-4 { grid-template-columns: repeat(4, 1fr); }
.grid-5 { grid-template-columns: repeat(5, 1fr); }
.grid-6 { grid-template-columns: repeat(6, 1fr); }

.gallery-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  background: white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.gallery-item:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.gallery-image {
  width: 100%;
  height: 200px;
  object-fit: cover;
  display: block;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
  color: white;
  font-size: 2rem;
}

.gallery-item:hover .image-overlay {
  opacity: 1;
}

.image-info {
  padding: 12px;
  background: white;
}

.image-name {
  font-weight: 600;
  color: #333;
  margin-bottom: 4px;
  font-size: 0.9rem;
}

.image-meta {
  display: flex;
  gap: 8px;
  font-size: 0.8rem;
  color: #6c757d;
}

/* List Layout */
.gallery-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.list-item {
  display: flex;
  align-items: center;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.3s ease, box-shadow 0.3s ease;
  background: white;
}

.list-item:hover {
  background: #f8f9fa;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.list-image {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
  margin-right: 12px;
}

.list-info {
  flex: 1;
}

.list-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 8px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.3s ease;
}

.action-btn.download {
  background: #007bff;
  color: white;
}

.action-btn.download:hover {
  background: #0056b3;
}

.action-btn.delete {
  background: #dc3545;
  color: white;
}

.action-btn.delete:hover {
  background: #c82333;
}

/* Carousel Layout */
.gallery-carousel {
  position: relative;
}

.carousel-container {
  position: relative;
  overflow: hidden;
  border-radius: 8px;
  background: #f8f9fa;
}

.carousel-track {
  display: flex;
  transition: transform 0.5s ease;
}

.carousel-slide {
  flex: 0 0 100%;
  position: relative;
  cursor: pointer;
}

.carousel-image {
  width: 100%;
  height: 300px;
  object-fit: cover;
  display: block;
}

.carousel-info {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(transparent, rgba(0,0,0,0.7));
  color: white;
  padding: 20px;
}

.carousel-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.5);
  color: white;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.2rem;
  transition: background 0.3s ease;
}

.carousel-btn:hover:not(:disabled) {
  background: rgba(0,0,0,0.7);
}

.carousel-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.carousel-btn.prev {
  left: 15px;
}

.carousel-btn.next {
  right: 15px;
}

.carousel-indicators {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 15px;
}

.indicator {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: none;
  background: #ddd;
  cursor: pointer;
  transition: background 0.3s ease;
}

.indicator.active {
  background: #007bff;
}

.indicator:hover {
  background: #6c757d;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 90vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #dee2e6;
  background: #f8f9fa;
}

.modal-title {
  flex: 1;
}

.modal-title h3 {
  margin: 0 0 5px 0;
  font-size: 1.2rem;
  color: #333;
}

.modal-counter {
  font-size: 0.9rem;
  color: #6c757d;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6c757d;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: background 0.3s ease;
}

.close-btn:hover {
  background: rgba(0,0,0,0.1);
  color: #dc3545;
}

.modal-body {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: auto;
  position: relative;
}

.modal-image-container {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.modal-image {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
}

.modal-nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.5);
  color: white;
  border: none;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.5rem;
  transition: background 0.3s ease;
}

.modal-nav-btn:hover:not(:disabled) {
  background: rgba(0,0,0,0.7);
}

.modal-nav-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.modal-nav-btn.prev {
  left: 20px;
}

.modal-nav-btn.next {
  right: 20px;
}

.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-top: 1px solid #dee2e6;
  background: #f8f9fa;
  flex-wrap: wrap;
  gap: 15px;
}

.image-details {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  flex: 1;
}

.detail-group {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #6c757d;
}

.value {
  font-size: 0.9rem;
  color: #333;
}

.modal-actions {
  display: flex;
  gap: 10px;
}

.btn-download, .btn-delete {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background 0.3s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-download {
  background: #007bff;
  color: white;
}

.btn-download:hover {
  background: #0056b3;
}

.btn-delete {
  background: #dc3545;
  color: white;
}

.btn-delete:hover {
  background: #c82333;
}

/* Estado vacío */
.gallery-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #6c757d;
  text-align: center;
}

.empty-icon {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1rem;
}

.empty-text h4 {
  margin: 0 0 0.5rem 0;
  color: #6c757d;
}

.empty-text p {
  margin: 0;
  font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
  .gallery-grid {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 10px;
  }

  .gallery-image {
    height: 150px;
  }

  .list-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .list-image {
    width: 100%;
    height: 120px;
    margin-right: 0;
  }

  .list-actions {
    width: 100%;
    justify-content: center;
  }

  .carousel-image {
    height: 250px;
  }

  .modal-content {
    max-width: 95vw;
    max-height: 95vh;
  }

  .modal-footer {
    flex-direction: column;
    align-items: flex-start;
  }

  .image-details {
    width: 100%;
  }

  .modal-actions {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .gallery-grid {
    grid-template-columns: 1fr !important;
  }

  .detail-group {
    flex: 1;
    min-width: 45%;
  }
}

/* ✅ Estilos para estado de carga */
.gallery-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
  color: #6c757d;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* ✅ Estilos para estado de error */
.gallery-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
  color: #dc3545;
  text-align: center;
}

.error-icon {
  font-size: 2rem;
  margin-bottom: 1rem;
  color: #dc3545;
}

.error-text h4 {
  margin-bottom: 0.5rem;
  color: #dc3545;
}

.error-text p {
  color: #6c757d;
  font-size: 0.9rem;
}
</style>
