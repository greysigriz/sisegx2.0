<template>
  <div class="card-table">
    <h3>🏆 Top 10 Departamentos con Más Seguimientos</h3>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Departamento</th>
          <th>Seguimientos</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(dep, index) in topDepartamentos" :key="dep.nombre">
          <td>{{ index + 1 }}</td>
          <td>{{ dep.nombre }}</td>
          <td>
            <div class="seguimiento-box">
              {{ dep.seguimientos.toLocaleString() }}
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'TopDepartamentos',
  data() {
    return {
      departamentos: [
        { nombre: 'Dirección de Tránsito Municipal', seguimientos: 152 },
        { nombre: 'Dirección de la Policía Turística', seguimientos: 134 },
        { nombre: 'Dirección General de Protección Civil', seguimientos: 120 },
        { nombre: 'Mantenimiento de alumbrado público', seguimientos: 97 },
        { nombre: 'Limpieza y mantenimiento de parques', seguimientos: 86 },
        { nombre: 'Secretaría Municipal de Obras Públicas y Servicios', seguimientos: 74 },
        { nombre: 'Dirección General de Obras Públicas', seguimientos: 65 },
        { nombre: 'Coordinación de Seguridad Escolar', seguimientos: 122 },
        { nombre: 'Dirección de Parques y Jardines', seguimientos: 88 },
        { nombre: 'Departamento de Señalización Vial', seguimientos: 44 },
        { nombre: 'Protección Animal', seguimientos: 32 },
        { nombre: 'Departamento de Bacheo y Pavimentación', seguimientos: 101 }
      ]
    };
  },
  computed: {
    topDepartamentos() {
      return this.departamentos
        .sort((a, b) => b.seguimientos - a.seguimientos)
        .slice(0, 10);
    }
  }
};
</script>

