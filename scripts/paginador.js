// scripts/paginador.js

export class TablePaginator {
    constructor(tableId, searchInputId = null, rowsPerPage = 10) {
        this.table = document.getElementById(tableId);
        if (!this.table) return;
        
        this.tbody = this.table.querySelector('tbody');
        if (!this.tbody) return;

        this.rowsPerPage = rowsPerPage;
        this.currentPage = 1;
        this.dataRows = Array.from(this.tbody.querySelectorAll('tr'));
        
        this.searchInput = searchInputId ? document.getElementById(searchInputId) : null;
        if (this.searchInput) {
            this.searchInput.addEventListener('input', () => {
                this.currentPage = 1;
                this.render();
            });
        }

        // Si la tabla original tiene "No se encontraron devoluciones" en su inicio, lo filtraremos
        this.updateRows();
    }

    updateRows() {
        this.dataRows = Array.from(this.tbody.querySelectorAll('tr'));
        // Si hay un solo tr que dice "No hay resultados", no lo paginamos
        if (this.dataRows.length === 1 && this.dataRows[0].textContent.toLowerCase().includes('no ')) {
            this.dataRows[0].style.display = '';
            this.renderControls(0);
            return;
        }
        this.currentPage = 1;
        this.render();
    }

    render() {
        const searchText = this.searchInput ? this.searchInput.value.toLowerCase() : '';
        
        let filtered = this.dataRows;
        
        if (searchText) {
            filtered = this.dataRows.filter(row => {
                return row.textContent.toLowerCase().includes(searchText);
            });
        }

        const totalPages = Math.ceil(filtered.length / this.rowsPerPage) || 1;
        if (this.currentPage > totalPages) this.currentPage = totalPages;
        if (this.currentPage < 1) this.currentPage = 1;

        // Ocultar todas las filas
        this.dataRows.forEach(row => row.style.display = 'none');

        // Mostrar solo las de la página actual
        const start = (this.currentPage - 1) * this.rowsPerPage;
        filtered.slice(start, start + this.rowsPerPage).forEach(row => row.style.display = '');

        this.renderControls(totalPages);
    }

    renderControls(totalPages) {
        let nav = this.table.parentNode.querySelector('.custom-pagination');
        if (!nav) {
            nav = document.createElement('nav');
            nav.className = 'custom-pagination d-flex justify-content-between align-items-center mt-3';
            // Inserta el nav justo después de la tabla
            this.table.parentNode.insertBefore(nav, this.table.nextSibling);
        }

        if (totalPages <= 1) {
            nav.innerHTML = '';
            return;
        }
        
        let html = `<div class="text-muted small">Página ${this.currentPage} de ${totalPages}</div>`;
        html += '<ul class="pagination mb-0">';
        
        html += `<li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="prev">Anterior</a>
                 </li>`;
        
        // Paginación abreviada para muchas páginas
        let startPage = Math.max(1, this.currentPage - 2);
        let endPage = Math.min(totalPages, this.currentPage + 2);
        
        if (startPage > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<li class="page-item ${this.currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                     </li>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
        }
        
        html += `<li class="page-item ${this.currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="next">Siguiente</a>
                 </li>`;
        html += '</ul>';
        
        nav.innerHTML = html;

        nav.querySelectorAll('.page-link').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (btn.parentElement.classList.contains('disabled')) return;
                
                const page = e.target.getAttribute('data-page');
                if (page === 'prev' && this.currentPage > 1) this.currentPage--;
                else if (page === 'next' && this.currentPage < totalPages) this.currentPage++;
                else if (!isNaN(page)) this.currentPage = parseInt(page);
                
                this.render();
            });
        });
    }
}
