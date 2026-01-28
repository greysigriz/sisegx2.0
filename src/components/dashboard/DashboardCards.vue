<template>
  <div class="dashboard-metrics">
    <div class="metrics-container">
      <div class="metrics-grid">
        <!-- Card 1: Reportes Totales -->
        <div class="metric-card">
          <div class="metric-label">Reportes Totales</div>
          <div class="metric-value-row">
            <div class="metric-value">{{ Math.floor(cards[0].displayValue).toLocaleString() }}</div>
            <span :class="['metric-badge', cards[0].trend > 0 ? 'badge-up' : 'badge-down']">
              <svg class="badge-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  :d="cards[0].trend > 0 ? 'M18 15L12 9L6 15' : 'M6 9L12 15L18 9'"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>{{ Math.abs(cards[0].trend) }}%</span>
            </span>
          </div>
        </div>

        <!-- Card 2: Pendientes -->
        <div class="metric-card">
          <div class="metric-label">Pendientes</div>
          <div class="metric-value-row">
            <div class="metric-value">{{ Math.floor(cards[1].displayValue).toLocaleString() }}</div>
            <span :class="['metric-badge', cards[1].trend > 0 ? 'badge-up' : 'badge-down']">
              <svg class="badge-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  :d="cards[1].trend > 0 ? 'M18 15L12 9L6 15' : 'M6 9L12 15L18 9'"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>{{ Math.abs(cards[1].trend) }}%</span>
            </span>
          </div>
        </div>

        <!-- Card 3: Atendidos -->
        <div class="metric-card">
          <div class="metric-label">Atendidos</div>
          <div class="metric-value-row">
            <div class="metric-value">{{ Math.floor(cards[2].displayValue).toLocaleString() }}</div>
            <span :class="['metric-badge', cards[2].trend > 0 ? 'badge-up' : 'badge-down']">
              <svg class="badge-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  :d="cards[2].trend > 0 ? 'M18 15L12 9L6 15' : 'M6 9L12 15L18 9'"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>{{ Math.abs(cards[2].trend) }}%</span>
            </span>
          </div>
        </div>

        <!-- Card 4: En Proceso -->
        <div class="metric-card">
          <div class="metric-label">En Proceso</div>
          <div class="metric-value-row">
            <div class="metric-value">{{ Math.floor(cards[3].displayValue).toLocaleString() }}</div>
            <span :class="['metric-badge', cards[3].trend > 0 ? 'badge-up' : 'badge-down']">
              <svg class="badge-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  :d="cards[3].trend > 0 ? 'M18 15L12 9L6 15' : 'M6 9L12 15L18 9'"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <span>{{ Math.abs(cards[3].trend) }}%</span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import '@/assets/css/cards_dashboard.css'
import { ref, onMounted, onUnmounted } from "vue"

export default {
  name: "DashboardCards",
  setup() {
    const cards = ref([
      { title: 'Reportes Totales', value: 1240, displayValue: 0, trend: 1.8 },
      { title: 'Pendientes', value: 312, displayValue: 0, trend: -2.5 },
      { title: 'Atendidos', value: 872, displayValue: 0, trend: 5.2 },
      { title: 'En Proceso', value: 56, displayValue: 0, trend: 2.2 },
    ])

    const animationIntervals = ref([])

    const animateNumber = (index, targetValue, duration = 1500) => {
      // Defensive: Don't animate if tab is hidden
      if (document.hidden) {
        cards.value[index].displayValue = targetValue
        return
      }

      const frameRate = 1000 / 60
      const totalFrames = Math.round(duration / frameRate)
      let frame = 0
      const increment = targetValue / totalFrames

      const counter = setInterval(() => {
        // Pause animation if tab becomes hidden
        if (document.hidden) {
          clearInterval(counter)
          return
        }

        frame++
        cards.value[index].displayValue += increment
        if (frame >= totalFrames) {
          cards.value[index].displayValue = targetValue
          clearInterval(counter)
        }
      }, frameRate)

      animationIntervals.value.push(counter)
    }

    onMounted(() => {
      cards.value.forEach((c, i) => animateNumber(i, c.value))
    })

    onUnmounted(() => {
      // Cleanup: clear all animation intervals
      animationIntervals.value.forEach(interval => clearInterval(interval))
      animationIntervals.value = []
    })

    return { cards }
  }
}
</script>


