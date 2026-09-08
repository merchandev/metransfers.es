# 🗺️ Estructura Completa de Páginas de MeTransfers

> Documento histórico, no inventario del sitio publicado. La creación automática descrita abajo está parcialmente desactivada y varios slugs no existen en WordPress. Consultar la [auditoría del 8 de septiembre de 2026](docs/page-structure-audit-2026-09-08.md) y el [inventario real](docs/page-structure-inventory-2026-09-08.md) antes de crear páginas o cambiar redirecciones.

A continuación tienes el mapa completo de todas las páginas que tu tema instala y gestiona automáticamente. Todas ellas han sido actualizadas para incluir los prefijos de SEO enfocados en captación de origen (Barcelona).

### 1. 💼 Páginas de Servicios Principales
Estas son las páginas pilar de tu modelo de negocio. Todas utilizan el `template-servicio.php` que diseñamos.

| Título de la Página | URL (Slug) | Plantilla |
| :--- | :--- | :--- |
| MeTransfers Barcelona - Traslados al Aeropuerto Barcelona | `/traslados-aeropuerto/` | `template-servicio.php` |
| MeTransfers Barcelona - Traslados al Puerto de Barcelona | `/traslados-puerto/` | `template-servicio.php` |
| MeTransfers Barcelona - Chófer Privado por Horas | `/chofer-por-horas/` | `template-servicio.php` |
| MeTransfers Barcelona - Corporativo y Eventos | `/corporativo-y-eventos/` | `template-servicio.php` |
| MeTransfers Barcelona - Tours Privados | `/tours-privados/` | `template-servicio.php` |
| MeTransfers Barcelona - Grupos | `/grupos/` | `template-servicio.php` |

---

### 2. 📍 Hub de Destinos y Rutas
Páginas creadas masivamente para abarcar todas las combinaciones geográficas y posicionar en long-tail. Todas las páginas "hijas" utilizan el `template-destino.php`.

* **MeTransfers Barcelona - Destinos de traslados privados desde Barcelona** (`/destinos/`) - *Página Padre / Hub*
  * MeTransfers Barcelona - Traslado privado a **Almeria** desde Barcelona (`/destinos/almeria/`)
  * MeTransfers Barcelona - Traslado privado a **Andorra** desde Barcelona (`/destinos/andorra/`)
  * MeTransfers Barcelona - Traslado privado a **Baqueira Beret** desde Barcelona (`/destinos/baqueira-beret/`)
  * *(... y el resto de los 38 destinos configurados inicialmente)*

---

### 3. 🎯 Páginas Estratégicas de SEO (Aterrizaje Manuales)
Páginas 100% hechas a medida con plantillas personalizadas exclusivas para atacar términos de búsqueda con volumen exacto (Taxis/Transfers).

| Título de la Página | URL (Slug) | Plantilla Exclusiva |
| :--- | :--- | :--- |
| MeTransfers Barcelona - Taxis Privado Barcelona | `/taxis-privado-barcelona/` | `page-taxis-privado-barcelona.php` |
| MeTransfers Barcelona - Taxis Barcelona a Port Aventura | `/taxis-barcelona-port-aventura/` | `page-taxis-barcelona-port-aventura.php` |
| MeTransfers Barcelona - Taxis Barcelona a Salou | `/taxis-barcelona-salou/` | `page-taxis-barcelona-salou.php` |
| MeTransfers Barcelona - Taxis Barcelona a Costa Brava | `/taxis-barcelona-costa-brava/` | `page-taxis-barcelona-costa-brava.php` |
| MeTransfers Barcelona - Taxis Barcelona a Girona | `/taxis-barcelona-girona/` | `page-taxis-barcelona-girona.php` |

---

### 3.1. 🚀 Nuevas Páginas Estratégicas Dinámicas (Taxis y Traslados)
*Generadas automáticamente desde el catálogo usando la plantilla unificada `page-seo-dynamic.php`.*

**Páginas orientadas a "Taxis":**
* MeTransfers Barcelona - Taxis Barcelona a Andorra (`/taxis-barcelona-andorra/`)
* MeTransfers Barcelona - Taxis Barcelona a Taüll (`/taxis-barcelona-taull/`)
* MeTransfers Barcelona - Taxis Barcelona a Vielha (`/taxis-barcelona-vielha/`)
* MeTransfers Barcelona - Taxis Barcelona a Tossa de Mar (`/taxis-barcelona-tossa-de-mar/`)
* MeTransfers Barcelona - Taxis Barcelona a Cadaqués (`/taxis-barcelona-cadaques/`)
* MeTransfers Barcelona - Taxis Barcelona a Besalú (`/taxis-barcelona-besalu/`)
* MeTransfers Barcelona - Taxis Barcelona a Bagur (`/taxis-barcelona-bagur/`)
* MeTransfers Barcelona - Taxis Barcelona a Delta del Ebro (`/taxis-barcelona-delta-del-ebro/`)
* MeTransfers Barcelona - Taxis Barcelona a Peñíscola (`/taxis-barcelona-peniscola/`)
* MeTransfers Barcelona - Taxis Barcelona a Morella (`/taxis-barcelona-morella/`)
* MeTransfers Barcelona - Taxis Barcelona a Altea (`/taxis-barcelona-altea/`)
* MeTransfers Barcelona - Taxis Barcelona a Valderrobres (`/taxis-barcelona-valderrobres/`)
* MeTransfers Barcelona - Taxis Barcelona a Alquézar (`/taxis-barcelona-alquezar/`)
* MeTransfers Barcelona - Taxis Barcelona a Colliure (`/taxis-barcelona-colliure/`)
* MeTransfers Barcelona - Taxis Barcelona a Carcasona (`/taxis-barcelona-carcasona/`)

**Páginas orientadas a "Traslados privados":**
* MeTransfers Barcelona - Traslado privado a Andorra desde Barcelona (`/traslados-barcelona-andorra/`)
* MeTransfers Barcelona - Traslado privado a Taüll desde Barcelona (`/traslados-barcelona-taull/`)
* MeTransfers Barcelona - Traslado privado a Vielha desde Barcelona (`/traslados-barcelona-vielha/`)
* MeTransfers Barcelona - Traslado privado a Tossa de Mar desde Barcelona (`/traslados-barcelona-tossa-de-mar/`)
* MeTransfers Barcelona - Traslado privado a Cadaqués desde Barcelona (`/traslados-barcelona-cadaques/`)
* MeTransfers Barcelona - Traslado privado a Besalú desde Barcelona (`/traslados-barcelona-besalu/`)
* MeTransfers Barcelona - Traslado privado a Bagur desde Barcelona (`/traslados-barcelona-bagur/`)
* MeTransfers Barcelona - Traslado privado a Delta del Ebro desde Barcelona (`/traslados-barcelona-delta-del-ebro/`)
* MeTransfers Barcelona - Traslado privado a Peñíscola desde Barcelona (`/traslados-barcelona-peniscola/`)
* MeTransfers Barcelona - Traslado privado a Morella desde Barcelona (`/traslados-barcelona-morella/`)
* MeTransfers Barcelona - Traslado privado a Altea desde Barcelona (`/traslados-barcelona-altea/`)
* MeTransfers Barcelona - Traslado privado a Valderrobres desde Barcelona (`/traslados-barcelona-valderrobres/`)
* MeTransfers Barcelona - Traslado privado a Alquézar desde Barcelona (`/traslados-barcelona-alquezar/`)
* MeTransfers Barcelona - Traslado privado a Colliure desde Barcelona (`/traslados-barcelona-colliure/`)
* MeTransfers Barcelona - Traslado privado a Carcasona desde Barcelona (`/traslados-barcelona-carcasona/`)

---

### 4. 📸 Catálogo de Tours
Experiencias empaquetadas.

| Título de la Página | URL (Slug) |
| :--- | :--- |
| MeTransfers Barcelona - Tour en Barcelona | `/tour-barcelona/` |
| MeTransfers Barcelona - Tour a Montserrat | `/tour-a-montserrat/` |
| MeTransfers Barcelona - Tour Costa Brava | `/tour-costa-brava/` |
| MeTransfers Barcelona - Tour a Girona | `/tour-a-girona/` |

---

### 5. ⚖️ Páginas Legales, Soporte y Proceso de Reserva
Páginas administrativas y flujos de usuario (algunas de ellas gestionadas por WooCommerce o tu motor de reservas).

* **MeTransfers Barcelona - Políticas de privacidad** (`/privacidad/`)
* **MeTransfers Barcelona - Términos y Condiciones regulan la contratación** (`/terminos-y-condiciones/`)
* **MeTransfers Barcelona - Política de Cookies** (`/cookies/`)
* **MeTransfers Barcelona - Preguntas Frecuentes** (`/faq/`)
* **MeTransfers Barcelona - Contacto** (`/contacto/`)
* **MeTransfers Barcelona - Reservaciones** (`/reservaciones/`)
