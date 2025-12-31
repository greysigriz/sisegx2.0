<template>
  <div class="dashboard-wrapper">
    <div class="dashboard">
      <div
        v-for="(card, index) in cards"
        :key="index"
        class="card"
        @mousemove="handleMouseMove($event, index)"
        @mouseleave="resetTransform(index)"
        :style="{ transform: card.transform }"
      >
        <div class="card-title">{{ card.title }}</div>
        <div class="card-number">
          {{ Math.floor(card.displayValue).toLocaleString() }}
        </div>
        <div class="card-subtext">{{ card.subtext }}</div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from "vue"

export default {
  name: "DashboardCards",
  setup() {
    const cards = ref([
      { title: 'Reportes Totales', value: 1240, displayValue: 0, subtext: 'Actualizado hoy', transform: '' },
      { title: 'Pendientes', value: 312, displayValue: 0, subtext: 'Requieren seguimiento', transform: '' },
      { title: 'Atendidos', value: 872, displayValue: 0, subtext: 'Última semana', transform: '' },
      { title: 'En Proceso', value: 56, displayValue: 0, subtext: 'Actualmente en curso', transform: '' },
    ])

    const animateNumber = (index, targetValue, duration = 1500) => {
      const frameRate = 1000 / 60
      const totalFrames = Math.round(duration / frameRate)
      let frame = 0
      const increment = targetValue / totalFrames

      const counter = setInterval(() => {
        frame++
        cards.value[index].displayValue += increment
        if (frame >= totalFrames) {
          cards.value[index].displayValue = targetValue
          clearInterval(counter)
        }
      }, frameRate)
    }

    const handleMouseMove = (e, index) => {
      const card = e.currentTarget
      const rect = card.getBoundingClientRect()
      const x = e.clientX - rect.left
      const y = e.clientY - rect.top
      const rotateX = ((y / rect.height) - 0.5) * -10
      const rotateY = ((x / rect.width) - 0.5) * 10
      cards.value[index].transform = `perspective(600px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`
    }

    const resetTransform = (index) => {
      cards.value[index].transform = 'perspective(600px) rotateX(0deg) rotateY(0deg)'
    }

    onMounted(() => {
      cards.value.forEach((c, i) => animateNumber(i, c.value))
    })

    return { cards, handleMouseMove, resetTransform }
  }
}
</script>
