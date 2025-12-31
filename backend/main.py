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
    allow_origins=["http://localhost:5173"],  # Puedes restringir esto a ["http://localhost:5173"] por seguridad
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

# Ruta de login básica
from fastapi import Request
from fastapi.responses import JSONResponse

@app.post("/login")
async def login(request: Request):
    data = await request.json()
    usuario = data.get("usuario")
    password = data.get("password")
    # Ejemplo simple, reemplaza con tu lógica real
    if usuario == "Admin" and password == "Admin":
        return {"success": True, "user": {"usuario": usuario}}
    return JSONResponse({"success": False, "message": "Credenciales inválidas"}, status_code=401)
