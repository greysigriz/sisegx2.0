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
export default {
  name: 'DashboardCards',
  data() {
    return {
      cards: [
        { title: 'Reportes Totales', value: 1240, displayValue: 0, subtext: 'Actualizado hoy', transform: '' },
        { title: 'Pendientes', value: 312, displayValue: 0, subtext: 'Requieren seguimiento', transform: '' },
        { title: 'Atendidos', value: 872, displayValue: 0, subtext: 'Última semana', transform: '' },
        { title: 'En Proceso', value: 56, displayValue: 0, subtext: 'Actualmente en curso', transform: '' },
      ],
    };
  },
  mounted() {
    this.cards.forEach((card, index) => {
      this.animateNumber(index, card.value, 1500);
    });
  },
  methods: {
    animateNumber(index, targetValue, duration) {
      const frameRate = 1000 / 60;
      const totalFrames = Math.round(duration / frameRate);
      let frame = 0;
      const increment = targetValue / totalFrames;

      const counter = setInterval(() => {
        frame++;
        this.cards[index].displayValue += increment;
        if (frame >= totalFrames) {
          this.cards[index].displayValue = targetValue;
          clearInterval(counter);
        }
      }, frameRate);
    },
    handleMouseMove(e, index) {
      const card = e.currentTarget;
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const rotateX = ((y / rect.height) - 0.5) * -10;
      const rotateY = ((x / rect.width) - 0.5) * 10;
      this.cards[index].transform = `perspective(600px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    },
    resetTransform(index) {
      this.cards[index].transform = 'perspective(600px) rotateX(0deg) rotateY(0deg)';
    },
  },
};
</script>

<style scoped>
.dashboard-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 24px;
  /* min-height: 100vh; */
  background-color: #eef3f7;
}

.dashboard {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: center;
  max-width: 1200px;
  width: 100%;
}

.card {
  background-color: #0074D9;
  color: white;
  padding: 20px;
  border-radius: 12px;
  min-width: 200px;
  flex: 1 1 260px;
  min-height: 160px;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.25);
  cursor: pointer;
  user-select: none;
  transition: transform 0.2s ease, background-color 0.3s ease;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  backface-visibility: hidden;
  transform-style: preserve-3d;
}

.card:hover {
  background-color: #005fa3;
}

.card-title {
  font-size: 18px;
  font-weight: bold;
  margin-bottom: 8px;
}

.card-number {
  font-size: 36px;
  font-weight: 700;
  margin-bottom: 6px;
}

.card-subtext {
  font-size: 14px;
  opacity: 0.85;
}
</style>
