<script>
import axios from '@/services/axios-config'


export default {
    name: "HeaderFuncionalidad",

    props: {
    selectedMunicipality: {
        type: String,
        default: "Todos"
    },
    notificationCount: {
        type: Number,
        default: 0
    }
    },

emits: ["update:selectedMunicipality", "search", "notifications"],

data() {
    return {
        searchQuery: "",
        municipalities: [],
        loadingMunicipalities: true,
        showSuggestions: false,
        highlightedIndex: -1
        };
    },

    computed: {
        filteredMunicipalities() {
            const q = (this.searchQuery || '').toLowerCase().trim();
            if (!q) return this.municipalities; // Mostrar todos cuando no hay búsqueda
            return this.municipalities.filter(m => m.toLowerCase().includes(q));
        }
    },

async mounted() {
    await this.fetchMunicipalities();
},

methods: {
        onInputFocus() {
            this.showSuggestions = true;
        },

        toggleSuggestions(event) {
            // Prevenir que el blur se ejecute
            event?.preventDefault();
            event?.stopPropagation();
            this.showSuggestions = !this.showSuggestions;
            if (this.showSuggestions) {
                this.highlightedIndex = -1;
            }
        },

        onInputBlur() {
            // small delay to allow click event on suggestion
            setTimeout(() => {
                this.showSuggestions = false;
                this.highlightedIndex = -1;
            }, 150);
        },

        handleKeyDown(e) {
            const len = this.filteredMunicipalities.length;
            if (!len) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.highlightedIndex = (this.highlightedIndex + 1) % len;
                this.showSuggestions = true;
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.highlightedIndex = (this.highlightedIndex - 1 + len) % len;
                this.showSuggestions = true;
            } else if (e.key === 'Enter') {
                if (this.showSuggestions && this.highlightedIndex >= 0) {
                    e.preventDefault();
                    this.selectSuggestion(this.filteredMunicipalities[this.highlightedIndex]);
                } else {
                    // normal search submit
                    this.$emit('search', this.searchQuery);
                }
            } else if (e.key === 'Escape') {
                this.showSuggestions = false;
                this.highlightedIndex = -1;
            }
        },

    onMunicipalityChange(event) {
        const value = event.target.value;
        // Sincronizar el campo de búsqueda con la selección del dropdown
        this.searchQuery = value === 'Todos' ? '' : value;
        this.$emit("update:selectedMunicipality", value);
        this.$emit("search", value);
    },
    onSearch() {
        // Al escribir, buscar coincidencia exacta para actualizar el selector
        const match = this.municipalities.find(
            m => m.toLowerCase() === this.searchQuery.toLowerCase().trim()
        );
        if (match) {
            this.$emit('update:selectedMunicipality', match);
        } else if (!this.searchQuery.trim()) {
            this.$emit('update:selectedMunicipality', 'Todos');
        }
        this.$emit("search", this.searchQuery);
    },
    selectSuggestion(name) {
        if (name === 'Todos') {
            this.searchQuery = '';
            this.$emit('update:selectedMunicipality', 'Todos');
            this.$emit('search', '');
        } else {
            this.searchQuery = name;
            this.$emit('update:selectedMunicipality', name);
            this.$emit('search', name);
        }
        this.showSuggestions = false;
        this.highlightedIndex = -1;
    },
    async fetchMunicipalities() {
        this.loadingMunicipalities = true;
        try {
        const resp = await axios.get('division.php');
        if (resp?.data?.success && Array.isArray(resp.data.divisions)) {
        this.municipalities = resp.data.divisions.map(d => d.Municipio).filter(Boolean);
        } else {
            console.warn('Respuesta inesperada al obtener divisiones', resp.data);
            this.municipalities = [];
        }
        } catch (err) {
        console.error('Error cargando municipios:', err);
        this.municipalities = [];
        } finally {
        this.loadingMunicipalities = false;
        }
    }
}
};
</script>
