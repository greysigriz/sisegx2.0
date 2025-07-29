<template>
    <div class="carrusel-temas">
    <h2>Tutorial de cómo usar el sistema</h2>

    <div class="scroll-container">
      <div
        class="tarjeta"
        v-for="(item, index) in temas"
        :key="index"
      >
        <iframe
          :src="item.video"
          frameborder="0"
          allow="autoplay; encrypted-media"
          allowfullscreen
        ></iframe>
        <h3>{{ item.titulo }}</h3>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CarruselTemas',
data() {
    return {
        temas: [
        { video: 'https://www.youtube.com/embed/zKoawSDsm68' },
        { video: 'https://www.youtube.com/embed/XP6HEikQAyA' },
        { video: 'https://www.youtube.com/embed/J_BdLnnzfOg' }
        ]
    }
}

}
</script>

<style scoped>
.carrusel-temas {
  max-width: 93.3%;
  margin: 3rem auto;
  padding: -5rem;
  background: #ffffff;
  border-radius: 10px; 
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.2);
  overflow: hidden;
  position: relative;
  scroll-snap-align: center;
  transition: transform 0.3s ease;
}

.carrusel-temas:hover {
  transform: scale(1.03);
  background: rgb(255, 255, 255);
}

.scroll-container {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding: 1rem 0;
  scroll-snap-type: x mandatory;
  scrollbar-width: thin;
  padding-inline: 2rem;
  margin-top: 0rem;
  justify-content: center;
}

.carrusel-temas h2 {
  text-align: center;
  color: rgb(0, 0, 0);
  font-size: 1.5rem;
  margin-top: 1rem;
}

.tarjeta {
  flex: 0 0 auto;
  width: 400px;
  height: 250px;
  border-radius: 12px;
  background: rgb(0, 0, 0);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1);
  position: relative;
  scroll-snap-align: center;
  overflow: hidden;
}

.tarjeta:hover {
  transform: scale(1.05);
  transition: transform 0.3s ease;
}

.tarjeta iframe {
  width: 100%;
  height: 100%;
  border: none;
}

.tarjeta h3 {
  position: absolute;
  bottom: 10px;
  left: 0;
  right: 0;
  font-size: 1.1rem;
  color: white;
  text-shadow: 0 2px 4px rgba(0,0,0,0.7);
}
</style>