// Script para generar GeoJSON con todos los municipios de Yucatán
// Posiciones aproximadas basadas en la geografía real del estado

const municipios = [
  { nombre: "Abalá", lat: 20.98, lng: -89.77 },
  { nombre: "Acanceh", lat: 20.81, lng: -89.45 },
  { nombre: "Akil", lat: 20.37, lng: -89.32 },
  { nombre: "Baca", lat: 21.05, lng: -89.57 },
  { nombre: "Bokobá", lat: 20.96, lng: -89.15 },
  { nombre: "Buctzotz", lat: 21.28, lng: -88.19 },
  { nombre: "Cacalchén", lat: 20.90, lng: -89.80 },
  { nombre: "Calotmul", lat: 21.16, lng: -88.16 },
  { nombre: "Cansahcab", lat: 21.18, lng: -89.49 },
  { nombre: "Cantamayec", lat: 20.42, lng: -89.25 },
  { nombre: "Celestún", lat: 20.86, lng: -90.40 },
  { nombre: "Cenotillo", lat: 21.05, lng: -88.84 },
  { nombre: "Chacsinkín", lat: 21.05, lng: -88.45 },
  { nombre: "Chankom", lat: 20.74, lng: -88.43 },
  { nombre: "Chapab", lat: 19.92, lng: -89.18 },
  { nombre: "Chemax", lat: 20.66, lng: -87.94 },
  { nombre: "Chichimilá", lat: 20.63, lng: -88.37 },
  { nombre: "Chicxulub Pueblo", lat: 21.10, lng: -89.48 },
  { nombre: "Chikindzonot", lat: 20.62, lng: -88.60 },
  { nombre: "Chocholá", lat: 20.90, lng: -89.93 },
  { nombre: "Chumayel", lat: 20.46, lng: -89.34 },
  { nombre: "Conkal", lat: 21.08, lng: -89.52 },
  { nombre: "Cuncunul", lat: 20.74, lng: -88.01 },
  { nombre: "Cuzamá", lat: 20.77, lng: -89.37 },
  { nombre: "Dzán", lat: 20.25, lng: -89.58 },
  { nombre: "Dzemul", lat: 21.18, lng: -89.66 },
  { nombre: "Dzidzantún", lat: 21.27, lng: -89.30 },
  { nombre: "Dzilam de Bravo", lat: 21.40, lng: -88.88 },
  { nombre: "Dzilam González", lat: 21.32, lng: -88.88 },
  { nombre: "Dzitás", lat: 20.82, lng: -88.82 },
  { nombre: "Dzoncauich", lat: 21.28, lng: -89.41 },
  { nombre: "Espita", lat: 21.01, lng: -88.30 },
  { nombre: "Halachó", lat: 20.48, lng: -90.08 },
  { nombre: "Hocabá", lat: 20.78, lng: -89.14 },
  { nombre: "Hoctún", lat: 20.86, lng: -89.21 },
  { nombre: "Homún", lat: 20.74, lng: -89.28 },
  { nombre: "Huhí", lat: 20.76, lng: -89.38 },
  { nombre: "Hunucmá", lat: 21.02, lng: -89.88 },
  { nombre: "Ixil", lat: 20.95, lng: -89.45 },
  { nombre: "Izamal", lat: 20.93, lng: -89.02 },
  { nombre: "Kanasín", lat: 20.93, lng: -89.55 },
  { nombre: "Kantunil", lat: 20.53, lng: -88.51 },
  { nombre: "Kaua", lat: 20.85, lng: -88.21 },
  { nombre: "Kinchil", lat: 20.93, lng: -90.05 },
  { nombre: "Kopomá", lat: 20.97, lng: -89.99 },
  { nombre: "Mama", lat: 20.44, lng: -89.48 },
  { nombre: "Maní", lat: 20.39, lng: -89.41 },
  { nombre: "Maxcanú", lat: 20.59, lng: -90.17 },
  { nombre: "Mayapán", lat: 20.50, lng: -89.46 },
  { nombre: "Mérida", lat: 20.97, lng: -89.62 },
  { nombre: "Mococháh", lat: 21.11, lng: -89.48 },
  { nombre: "Motul", lat: 21.09, lng: -89.29 },
  { nombre: "Muna", lat: 20.28, lng: -89.72 },
  { nombre: "Muxupip", lat: 20.27, lng: -89.53 },
  { nombre: "Opichén", lat: 20.39, lng: -89.86 },
  { nombre: "Oxkutzcab", lat: 20.30, lng: -89.42 },
  { nombre: "Panabá", lat: 21.30, lng: -88.27 },
  { nombre: "Peto", lat: 20.13, lng: -88.92 },
  { nombre: "Progreso", lat: 21.28, lng: -89.67 },
  { nombre: "Quintana Roo", lat: 19.97, lng: -88.71 },
  { nombre: "Río Lagartos", lat: 21.60, lng: -88.16 },
  { nombre: "Sacalum", lat: 20.51, lng: -90.08 },
  { nombre: "Samahil", lat: 20.89, lng: -89.80 },
  { nombre: "San Felipe", lat: 21.55, lng: -88.18 },
  { nombre: "Sanahcat", lat: 21.04, lng: -88.46 },
  { nombre: "Santa Elena", lat: 20.25, lng: -89.60 },
  { nombre: "Seyé", lat: 20.85, lng: -89.42 },
  { nombre: "Sinanché", lat: 21.23, lng: -89.79 },
  { nombre: "Sotuta", lat: 20.63, lng: -89.04 },
  { nombre: "Sucilá", lat: 21.23, lng: -88.35 },
  { nombre: "Sudzal", lat: 21.01, lng: -88.57 },
  { nombre: "Suma", lat: 20.15, lng: -89.30 },
  { nombre: "Tahdziú", lat: 19.99, lng: -89.11 },
  { nombre: "Tahmek", lat: 20.82, lng: -89.29 },
  { nombre: "Teabo", lat: 20.42, lng: -89.29 },
  { nombre: "Tecoh", lat: 20.74, lng: -89.50 },
  { nombre: "Tekal de Venegas", lat: 21.23, lng: -88.97 },
  { nombre: "Tekantó", lat: 21.13, lng: -89.26 },
  { nombre: "Tekax", lat: 20.20, lng: -89.29 },
  { nombre: "Tekit", lat: 20.50, lng: -89.38 },
  { nombre: "Tekom", lat: 20.68, lng: -88.26 },
  { nombre: "Telchac Pueblo", lat: 21.24, lng: -89.31 },
  { nombre: "Telchac Puerto", lat: 21.34, lng: -89.26 },
  { nombre: "Temax", lat: 21.18, lng: -88.95 },
  { nombre: "Temozón", lat: 21.23, lng: -88.98 },
  { nombre: "Tepakán", lat: 20.32, lng: -90.26 },
  { nombre: "Tetiz", lat: 20.96, lng: -89.99 },
  { nombre: "Teya", lat: 20.95, lng: -89.34 },
  { nombre: "Ticul", lat: 20.40, lng: -89.53 },
  { nombre: "Timucuy", lat: 21.14, lng: -89.96 },
  { nombre: "Tinum", lat: 20.70, lng: -88.55 },
  { nombre: "Tixcacalcupul", lat: 20.85, lng: -88.02 },
  { nombre: "Tixkokob", lat: 21.01, lng: -89.40 },
  { nombre: "Tixmehuac", lat: 21.10, lng: -88.46 },
  { nombre: "Tixpéhual", lat: 21.01, lng: -89.43 },
  { nombre: "Tizimín", lat: 21.14, lng: -88.17 },
  { nombre: "Tunkas", lat: 21.06, lng: -88.68 },
  { nombre: "Tzucacab", lat: 20.06, lng: -89.04 },
  { nombre: "Uayma", lat: 20.66, lng: -88.76 },
  { nombre: "Ucú", lat: 21.05, lng: -89.72 },
  { nombre: "Umán", lat: 20.89, lng: -89.75 },
  { nombre: "Valladolid", lat: 20.69, lng: -88.20 },
  { nombre: "Xocchel", lat: 20.88, lng: -89.93 },
  { nombre: "Yaxcabá", lat: 20.55, lng: -88.78 },
  { nombre: "Yaxkukul", lat: 21.09, lng: -89.60 },
  { nombre: "Yobaín", lat: 21.18, lng: -89.25 }
];

// Función para crear un polígono rectangular alrededor de un punto central
function crearPoligono(lat, lng, tamano = 0.12) {
  const half = tamano / 2;
  return [
    [
      [lng - half, lat - half],
      [lng + half, lat - half],
      [lng + half, lat + half],
      [lng - half, lat + half],
      [lng - half, lat - half]
    ]
  ];
}

// Generar GeoJSON
const geojson = {
  type: "FeatureCollection",
  features: municipios.map(mun => ({
    type: "Feature",
    properties: {
      nombre: mun.nombre
    },
    geometry: {
      type: "Polygon",
      coordinates: crearPoligono(mun.lat, mun.lng)
    }
  }))
};

// Exportar como JSON
console.log(JSON.stringify(geojson, null, 2));
