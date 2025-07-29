from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from routes.peticiones import router as peticiones_router
from routes.clasificacion import router as clasificacion_router

app = FastAPI(
    title="API de Peticiones Ciudadanas",
    description="Clasificador de dependencias basado en texto",
    version="1.0"
)

# Configura CORS para permitir conexión desde el frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Puedes restringir esto a ["http://localhost:5173"] por seguridad
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Incluye rutas
app.include_router(peticiones_router, prefix="/api/peticiones")
app.include_router(clasificacion_router, prefix="/api/clasificacion")

@app.get("/")
def read_root():
    return {"mensaje": "API funcionando correctamente"}
