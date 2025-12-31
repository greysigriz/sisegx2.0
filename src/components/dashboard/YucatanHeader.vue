<template>
  <div class="yucatan-header">
    <div class="header-content">
      <div class="header-left">
        <div class="escudo">
          <svg viewBox="0 0 100 120" xmlns="http://www.w3.org/2000/svg">
            <!-- Escudo estilizado de Yucatán -->
            <path d="M50 10 L85 30 L85 80 C85 95 70 110 50 110 C30 110 15 95 15 80 L15 30 Z"
                  fill="#006341" stroke="#FFD700" stroke-width="2"/>
            <circle cx="50" cy="55" r="20" fill="#FFD700"/>
            <text x="50" y="62" font-size="16" fill="#006341" text-anchor="middle" font-weight="bold">Y</text>
          </svg>
        </div>
        <div class="titles">
          <h1 class="main-title">Estado de Yucatán</h1>
          <p class="subtitle">Sistema Integral de Seguimiento de Solicitudes</p>
        </div>
      </div>
      <div class="header-right">
        <div class="info-badge">
          <span class="badge-label">Municipios</span>
          <span class="badge-value">106</span>
        </div>
        <div class="info-badge">
          <span class="badge-label">Habitantes</span>
          <span class="badge-value">2.3M</span>
        </div>
      </div>
    </div>
    <div class="header-decoration">
      <div class="decoration-line"></div>
    </div>
  </div>
</template>

<script>
export default {
  name: "YucatanHeader"
}
</script>

<style scoped>
.yucatan-header {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  padding: 2rem 2rem 1rem;
  margin-bottom: 1rem;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  border-left: 6px solid #006341;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.escudo {
  width: 80px;
  height: 96px;
  flex-shrink: 0;
}

.escudo svg {
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.titles {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.main-title {
  font-size: 2rem;
  font-weight: 700;
  color: #006341;
  margin: 0;
  letter-spacing: -0.5px;
}

.subtitle {
  font-size: 1rem;
  color: #64748b;
  margin: 0;
  font-weight: 500;
}

.header-right {
  display: flex;
  gap: 1rem;
}

.info-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: white;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

.badge-label {
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.badge-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #006341;
  margin-top: 0.25rem;
}

.header-decoration {
  margin-top: 1rem;
}

.decoration-line {
  height: 3px;
  background: linear-gradient(90deg, #006341 0%, #FFD700 50%, #006341 100%);
  border-radius: 2px;
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .main-title {
    font-size: 1.5rem;
  }

  .escudo {
    width: 60px;
    height: 72px;
  }
}
</style>
