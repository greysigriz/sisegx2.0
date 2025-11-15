<template>
    <div class="tabla">
    <TablaDepartamento />
    </div>
</template>

<script>
import TablaDepartamento from '@/components/TablaDepartamento.vue';

export default {
  name: 'TablaDepartamentos',
  components: {
    TablaDepartamento
  }
}

</script>

<style src="@/assets/css/TablaDepartamentos.css"></style>
