<template>
  <div class="image-upload-container">
    <!-- Área de drag & drop -->
    <div
      class="upload-area"
      :class="{
        'dragover': isDragOver,
        'has-images': images.length > 0,
        'disabled': isUploading || images.length >= maxImages
      }"
      @dragover.prevent="handleDragOver"
      @dragleave.prevent="handleDragLeave"
      @drop.prevent="handleDrop"
      @click="triggerFileInput"
    >
      <input
        ref="fileInput"
        type="file"
        multiple
        accept="image/*"
        @change="handleFileSelect"
        style="display: none"
      />

      <div v-if="images.length === 0" class="upload-placeholder">
        <div class="upload-icon">
          <font-awesome-icon
            :icon="isUploading ? 'fa-solid fa-spinner' : 'fa-solid fa-cloud-upload-alt'"
            :class="{ 'fa-spin': isUploading }"
          />
        </div>
        <div class="upload-text">
          <h4>{{ title || 'Subir Imágenes' }}</h4>
          <p v-if="!isUploading">
            Arrastra las imágenes aquí o <span class="link-text">haz clic para seleccionar</span>
          </p>
          <p v-else class="uploading-text">
            <font-awesome-icon icon="fa-solid fa-spinner" class="fa-spin" />
            Subiendo imágenes...
          </p>
          <div class="upload-info">
            <small>
              Máximo {{ maxImages }} imágenes • {{ maxSizeMB }}MB por imagen<br>
              Formatos: JPG, PNG, WebP, GIF, BMP
            </small>
          </div>
        </div>
      </div>

      <!-- Vista previa de imágenes -->
      <div v-if="images.length > 0" class="images-preview">
        <div class="images-grid">
          <div
            v-for="(image, index) in images"
            :key="image.id || `preview-${index}`"
            class="image-preview-item"
          >
            <!-- Imagen -->
            <div class="image-container" @click="openImageModal(image)">
              <img
                :src="getImageSrc(image)"
                :alt="image.filename_original || image.name"
                class="preview-image"
                @load="onImageLoad"
                @error="onImageError"
              />
              <div class="image-overlay">
                <font-awesome-icon icon="fa-solid fa-search-plus" />
              </div>
            </div>

            <!-- Información de la imagen -->
            <div class="image-info">
              <div class="image-name">
                {{ truncateFileName(image.filename_original || image.name) }}
              </div>
              <div class="image-size">
                {{ formatFileSize(image.file_size || image.size) }}
              </div>
              <div v-if="image.width && image.height" class="image-dimensions">
                {{ image.width }} × {{ image.height }}
              </div>
            </div>

            <!-- Estado de subida -->
            <div v-if="image.uploading" class="upload-progress">
              <div class="progress-bar">
                <div class="progress-fill" :style="{ width: `${image.progress || 0}%` }"></div>
              </div>
              <small>Subiendo...</small>
            </div>

            <!-- Botón eliminar -->
            <button
              v-if="!image.uploading"
              @click.stop="removeImage(index, image)"
              class="remove-btn"
              :title="image.id ? 'Eliminar imagen del servidor' : 'Quitar imagen'"
            >
              <font-awesome-icon icon="fa-solid fa-times" />
            </button>
          </div>

          <!-- Botón agregar más si no se alcanzó el límite -->
          <div
            v-if="images.length < maxImages && !isUploading"
            class="add-more-btn"
            @click="triggerFileInput"
          >
            <font-awesome-icon icon="fa-solid fa-plus" />
            <span>Agregar más</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Errores -->
    <div v-if="errors.length > 0" class="upload-errors">
      <div class="error-header">
        <font-awesome-icon icon="fa-solid fa-exclamation-triangle" />
        Errores al subir imágenes:
      </div>
      <ul>
        <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
      </ul>
      <button @click="clearErrors" class="clear-errors-btn">
        <font-awesome-icon icon="fa-solid fa-times" />
        Limpiar
      </button>
    </div>

    <!-- Modal de imagen ampliada -->
    <div v-if="modalImage" class="image-modal-overlay" @click.self="closeImageModal">
      <div class="image-modal">
        <div class="modal-header">
          <h3>{{ modalImage.filename_original || modalImage.name }}</h3>
          <button @click="closeImageModal" class="close-modal-btn">
            <font-awesome-icon icon="fa-solid fa-times" />
          </button>
        </div>
        <div class="modal-body">
          <img :src="getImageSrc(modalImage)" :alt="modalImage.filename_original || modalImage.name" />
        </div>
        <div class="modal-footer">
          <div class="image-details">
            <span>{{ formatFileSize(modalImage.file_size || modalImage.size) }}</span>
            <span v-if="modalImage.width && modalImage.height">
              {{ modalImage.width }} × {{ modalImage.height }}
            </span>
            <span>{{ modalImage.mime_type || 'Imagen' }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'

export default {
  name: 'ImageUpload',
  props: {
    title: {
      type: String,
      default: 'Subir Imágenes'
    },
    maxImages: {
      type: Number,
      default: 3
    },
    maxSizeMB: {
      type: Number,
      default: 10
    },
    entidadTipo: {
      type: String,
      required: true,
      validator: (value) => ['peticion', 'historial_cambio'].includes(value)
    },
    entidadId: {
      type: Number,
      default: 0
    },
    initialImages: {
      type: Array,
      default: () => []
    },
    autoUpload: {
      type: Boolean,
      default: true
    }
  },
  emits: ['images-changed', 'upload-complete', 'upload-error'],
  setup(props, { emit }) {
    const fileInput = ref(null)
    const images = ref([...props.initialImages])
    const isDragOver = ref(false)
    const isUploading = ref(false)
    const errors = ref([])
    const modalImage = ref(null)

    const API_URL = `${import.meta.env.VITE_API_URL || '/api'}/imagenes.php`
    const maxSizeBytes = computed(() => props.maxSizeMB * 1024 * 1024)

    // Métodos de validación
    const validateFile = (file) => {
      const errors = []

      // Validar tamaño
      if (file.size > maxSizeBytes.value) {
        errors.push(`${file.name}: Excede el tamaño máximo de ${props.maxSizeMB}MB`)
      }

      // Validar tipo
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'image/bmp']
      if (!allowedTypes.includes(file.type)) {
        errors.push(`${file.name}: Formato no permitido. Use JPG, PNG, WebP, GIF o BMP`)
      }

      return errors
    }

    // Drag & Drop handlers
    const handleDragOver = (e) => {
      e.preventDefault()
      isDragOver.value = true
    }

    const handleDragLeave = (e) => {
      e.preventDefault()
      isDragOver.value = false
    }

    const handleDrop = (e) => {
      e.preventDefault()
      isDragOver.value = false

      const files = Array.from(e.dataTransfer.files)
      processFiles(files)
    }

    // File input handlers
    const triggerFileInput = () => {
      if (!isUploading.value && images.value.length < props.maxImages) {
        fileInput.value?.click()
      }
    }

    const handleFileSelect = (e) => {
      const files = Array.from(e.target.files)
      processFiles(files)
      e.target.value = '' // Limpiar input
    }

    // Procesamiento de archivos
    const processFiles = (files) => {
      if (isUploading.value) return

      // Validar límite total
      const remainingSlots = props.maxImages - images.value.length
      if (remainingSlots <= 0) {
        errors.value.push('Ya se alcanzó el máximo de imágenes permitidas')
        return
      }

      // Tomar solo los archivos que caben
      const filesToProcess = files.slice(0, remainingSlots)

      // Validar cada archivo
      const validFiles = []
      filesToProcess.forEach(file => {
        const fileErrors = validateFile(file)
        if (fileErrors.length > 0) {
          errors.value.push(...fileErrors)
        } else {
          validFiles.push(file)
        }
      })

      if (validFiles.length > 0) {
        addFilesToPreview(validFiles)

        if (props.autoUpload && props.entidadId > 0) {
          uploadImages(validFiles)
        }
      }
    }

    // Agregar archivos a vista previa
    const addFilesToPreview = (files) => {
      files.forEach(file => {
        const preview = {
          file,
          name: file.name,
          size: file.size,
          type: file.type,
          uploading: props.autoUpload,
          progress: 0,
          preview: URL.createObjectURL(file)
        }
        images.value.push(preview)
      })

      emit('images-changed', images.value)
    }

    // Upload de imágenes
    const uploadImages = async (files) => {
      if (!props.entidadId || props.entidadId <= 0) {
        errors.value.push('ID de entidad no válido para subir imágenes')
        return
      }

      isUploading.value = true

      const formData = new FormData()
      formData.append('entidad_tipo', props.entidadTipo)
      formData.append('entidad_id', props.entidadId.toString())

      files.forEach(file => {
        formData.append('imagenes[]', file)
      })

      try {
        const response = await fetch(API_URL, {
          method: 'POST',
          body: formData
        })

        const result = await response.json()

        if (result.success) {
          // Actualizar imágenes subidas con datos del servidor
          result.imagenes.forEach((serverImage, index) => {
            const previewIndex = images.value.findIndex(img =>
              img.file && img.file.name === files[index]?.name && img.uploading
            )

            if (previewIndex >= 0) {
              images.value[previewIndex] = {
                ...serverImage,
                uploading: false,
                progress: 100
              }
            }
          })

          if (result.errores && result.errores.length > 0) {
            errors.value.push(...result.errores)
          }

          emit('upload-complete', result.imagenes)
        } else {
          throw new Error(result.message || 'Error al subir imágenes')
        }

      } catch (error) {
        console.error('Error uploading images:', error)
        errors.value.push(`Error al subir imágenes: ${error.message}`)

        // Marcar archivos con error
        files.forEach(file => {
          const previewIndex = images.value.findIndex(img =>
            img.file && img.file.name === file.name && img.uploading
          )
          if (previewIndex >= 0) {
            images.value[previewIndex].uploading = false
            images.value[previewIndex].error = true
          }
        })

        emit('upload-error', error)
      } finally {
        isUploading.value = false
        emit('images-changed', images.value)
      }
    }

    // Remover imagen
    const removeImage = async (index, image) => {
      if (image.id && image.id > 0) {
        // Eliminar del servidor
        try {
          const response = await fetch(API_URL, {
            method: 'DELETE',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ imagen_id: image.id })
          })

          const result = await response.json()
          if (!result.success) {
            throw new Error(result.message || 'Error al eliminar imagen')
          }
        } catch (error) {
          console.error('Error deleting image:', error)
          errors.value.push(`Error al eliminar imagen: ${error.message}`)
          return
        }
      }

      // Limpiar URL de preview si existe
      if (image.preview) {
        URL.revokeObjectURL(image.preview)
      }

      images.value.splice(index, 1)
      emit('images-changed', images.value)
    }

    // Utilidades
    const getImageSrc = (image) => {
      return image.url_acceso || image.preview || '/placeholder-image.png'
    }

    const formatFileSize = (bytes) => {
      if (!bytes) return 'N/A'
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(1024))
      return Math.round((bytes / Math.pow(1024, i)) * 100) / 100 + ' ' + sizes[i]
    }

    const truncateFileName = (name, maxLength = 20) => {
      if (!name) return 'Sin nombre'
      if (name.length <= maxLength) return name
      const ext = name.split('.').pop()
      const nameWithoutExt = name.substring(0, name.lastIndexOf('.'))
      const truncated = nameWithoutExt.substring(0, maxLength - ext.length - 3)
      return `${truncated}...${ext}`
    }

    // Modal de imagen
    const openImageModal = (image) => {
      modalImage.value = image
    }

    const closeImageModal = () => {
      modalImage.value = null
    }

    // Limpiar errores
    const clearErrors = () => {
      errors.value = []
    }

    // Handlers de imagen
    const onImageLoad = () => {
      // Imagen cargada correctamente
    }

    const onImageError = (e) => {
      console.error('Error loading image:', e.target.src)
      e.target.src = '/placeholder-image.png' // Imagen por defecto
    }

    // Métodos públicos
    const getImages = () => images.value
    const clearImages = () => {
      images.value.forEach(img => {
        if (img.preview) URL.revokeObjectURL(img.preview)
      })
      images.value = []
      emit('images-changed', images.value)
    }

    return {
      // Refs
      fileInput,
      images,
      isDragOver,
      isUploading,
      errors,
      modalImage,

      // Computed
      maxSizeBytes,

      // Métodos
      handleDragOver,
      handleDragLeave,
      handleDrop,
      triggerFileInput,
      handleFileSelect,
      removeImage,
      getImageSrc,
      formatFileSize,
      truncateFileName,
      openImageModal,
      closeImageModal,
      clearErrors,
      onImageLoad,
      onImageError,

      // Métodos públicos
      getImages,
      clearImages,
      uploadImages: (files) => uploadImages(files)
    }
  }
}
</script>

<style scoped>
.image-upload-container {
  width: 100%;
}

.upload-area {
  border: 2px dashed #ddd;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #fafafa;
}

.upload-area:hover:not(.disabled) {
  border-color: #007bff;
  background: #f0f8ff;
}

.upload-area.dragover {
  border-color: #007bff;
  background: #e6f3ff;
  transform: scale(1.02);
}

.upload-area.disabled {
  cursor: not-allowed;
  opacity: 0.6;
  background: #f5f5f5;
}

.upload-placeholder {
  padding: 40px 20px;
}

.upload-icon {
  font-size: 3rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

.upload-text h4 {
  margin: 0 0 0.5rem 0;
  color: #333;
  font-weight: 600;
}

.upload-text p {
  margin: 0 0 1rem 0;
  color: #6c757d;
}

.link-text {
  color: #007bff;
  text-decoration: underline;
}

.uploading-text {
  color: #007bff;
}

.upload-info {
  margin-top: 1rem;
}

.upload-info small {
  color: #6c757d;
}

.images-preview {
  margin-top: 1rem;
}

.images-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 15px;
}

.image-preview-item {
  position: relative;
  border: 1px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
  background: white;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.image-preview-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.image-container {
  position: relative;
  cursor: pointer;
  overflow: hidden;
}

.preview-image {
  width: 100%;
  height: 120px;
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
  font-size: 1.5rem;
}

.image-container:hover .image-overlay {
  opacity: 1;
}

.image-info {
  padding: 8px;
  font-size: 0.8rem;
}

.image-name {
  font-weight: 600;
  color: #333;
  margin-bottom: 2px;
}

.image-size, .image-dimensions {
  color: #6c757d;
  font-size: 0.75rem;
}

.upload-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(255,255,255,0.95);
  padding: 8px;
}

.progress-bar {
  height: 4px;
  background: #e9ecef;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 4px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #007bff, #0056b3);
  transition: width 0.3s ease;
}

.remove-btn {
  position: absolute;
  top: 5px;
  right: 5px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: none;
  background: rgba(220, 53, 69, 0.9);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  transition: background 0.2s ease;
}

.remove-btn:hover {
  background: rgba(220, 53, 69, 1);
}

.add-more-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 150px;
  border: 2px dashed #ccc;
  border-radius: 8px;
  cursor: pointer;
  color: #6c757d;
  transition: all 0.3s ease;
}

.add-more-btn:hover {
  border-color: #007bff;
  color: #007bff;
  background: #f8f9fa;
}

.upload-errors {
  margin-top: 1rem;
  padding: 12px;
  background: #f8d7da;
  border: 1px solid #f5c6cb;
  border-radius: 4px;
  color: #721c24;
}

.error-header {
  font-weight: 600;
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.upload-errors ul {
  margin: 8px 0;
  padding-left: 20px;
}

.clear-errors-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 4px 8px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8rem;
  margin-top: 8px;
}

.clear-errors-btn:hover {
  background: #c82333;
}

/* Modal de imagen */
.image-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
}

.image-modal {
  background: white;
  border-radius: 8px;
  max-width: 90vw;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #dee2e6;
  background: #f8f9fa;
}

.modal-header h3 {
  margin: 0;
  flex: 1;
  font-size: 1.1rem;
  color: #333;
}

.close-modal-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6c757d;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-modal-btn:hover {
  color: #dc3545;
}

.modal-body {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: auto;
  padding: 20px;
}

.modal-body img {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
}

.modal-footer {
  padding: 15px 20px;
  border-top: 1px solid #dee2e6;
  background: #f8f9fa;
}

.image-details {
  display: flex;
  gap: 15px;
  font-size: 0.9rem;
  color: #6c757d;
}

@media (max-width: 768px) {
  .images-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
  }

  .image-preview-item {
    font-size: 0.7rem;
  }

  .preview-image {
    height: 100px;
  }

  .upload-placeholder {
    padding: 20px 10px;
  }

  .image-modal {
    max-width: 95vw;
    max-height: 95vh;
  }

  .image-details {
    flex-direction: column;
    gap: 5px;
  }
}
</style>
