from services.clasificador import ClasificadorDependencias

clasificador = ClasificadorDependencias("dependencias_cancun.json")
resultado = clasificador.clasificar("hay un bache en mi calle")
print(resultado)
