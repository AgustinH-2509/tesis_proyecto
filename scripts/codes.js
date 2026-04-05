// scripts/codes.js

import { initNuevaDevolucion } from "./nueva_devolucion.js";
import { initDistributorModal } from "./modal_distribuidor.js";
import { initHistorial } from "./historial.js";
import { initControlDevoluciones } from "./control_devoluciones_simple.js";
import { initGestionarPermisos } from "./gestionar_permisos.js";

let sidebar, mainContent, toggleButton;

window.loadContent = function (url) {
    mainContent.innerHTML = "<div class='text-center mt-5'><div class='spinner-border' role='status'><span class='visually-hidden'>Cargando...</span></div></div>";

    // Aquí es donde manejamos la URL con parámetros
    const urlParts = url.split('?');
    const page = urlParts[0];
    const params = urlParts.length > 1 ? '?' + urlParts[1] : '';

    fetch('pages/' + page + params)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(html => {
            mainContent.innerHTML = html;
            // La lógica para inicializar los scripts se mantiene
            if (page === 'nueva_devolucion.php') {
                initNuevaDevolucion();
            } else if (page === 'distribuidores.php') {
                initDistributorModal();
            } else if (page === 'control_devoluciones.php') {
                console.log('✅ Inicializando módulo: Control de Devoluciones (control_devoluciones.php)');
                // Usar setTimeout para asegurar que el DOM esté completamente renderizado
                setTimeout(() => {
                    initControlDevoluciones();
                }, 100);
            } else if (page === 'historial.php') {
                initHistorial();
            } else if (page === 'ver_devolucion.php') {
                // No hay JS adicional para esta página, solo se carga el HTML/PHP
            } else if (page === 'gestionar_permisos.php') {
                initGestionarPermisos();
            }
        })
        .catch(error => {
            mainContent.innerHTML = "<p class='alert alert-danger'>No se pudo cargar el contenido. Inténtalo de nuevo más tarde.</p>";
            console.error('Error:', error);
        });
};

document.addEventListener('DOMContentLoaded', function () {
    sidebar = document.getElementById('sidebar');
    mainContent = document.getElementById('main-content');
    toggleButton = document.getElementById('toggleButton');

    if (toggleButton) {
        toggleButton.addEventListener('click', function () {
            if (sidebar && mainContent) {
                sidebar.classList.toggle('sidebar-collapsed');
                mainContent.classList.toggle('main-content-expanded');
            }
        });
    }

    const navLinks = document.querySelectorAll('.sidebar-content .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            const contentUrl = this.getAttribute('data-content-id');
            if (contentUrl) {
                event.preventDefault();
                window.loadContent(contentUrl);
            }
        });
    });

    mainContent.addEventListener('click', function (event) {
        // Usa `event.target.closest` para encontrar el enlace, incluso si se hace clic en un elemento hijo (como un icono o un span)
        const link = event.target.closest('a[data-content-id]');
        if (link) {
            event.preventDefault();
            const contentUrl = link.getAttribute('data-content-id');
            window.loadContent(contentUrl);
        }
    });

    window.loadContent('inicio.php');

    window.addEventListener('resize', handleSidebarState);
    function handleSidebarState() {
        if (sidebar && mainContent && toggleButton) {
            const isMobile = window.innerWidth <= 768; // 768px es un buen breakpoint para tablets
            if (isMobile) {
                // Si es un dispositivo móvil, colapsa la barra y desactiva el botón de alternancia.
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.add('main-content-expanded');
                toggleButton.disabled = true;
            } else {
                // Si no es un dispositivo móvil, asegúrate de que la barra no esté colapsada y el botón esté habilitado.
                // Esta es la parte que falta en tu código original.
                sidebar.classList.remove('sidebar-collapsed');
                mainContent.classList.remove('main-content-expanded');
                toggleButton.disabled = false;
            }
        }
    }
    window.addEventListener('beforeunload', function () {
        navigator.sendBeacon('administrador/logout_on_close.php');
    });
    handleSidebarState();
});