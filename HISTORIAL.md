# Historial consolidado de MeTransfers

Este documento resume la evolución completa del proyecto y explica cómo se preservó su historial Git. La fuente técnica definitiva de cada cambio continúa siendo `git log --all` y el contenido de cada commit.

## Alcance del historial

El 1 de septiembre de 2026 se consolidaron en `merchandev/metransfers.es` los historiales de:

| Proyecto original | Alcance | Estado |
|---|---|---|
| `tema-metransfers` | Tema WordPress, diseño, contenido, SEO e internacionalización | Integrado completamente |
| `plugin-de-reservas-metrasnfers` | Booking, vehículos, Hotel QR e importación/exportación | Integrado completamente |
| `Traductor-MT` | Traductor SEO, caché, selector y traducción de HTML | Integrado completamente |
| `metransfers.es` | Plataforma modular, hardening, pagos, calidad y producción | Repositorio principal |

La consolidación preservó autores, fechas, mensajes, relaciones padre e identificadores SHA. Los archivos antiguos no reemplazaron el árbol moderno: se utilizaron merges de historia con estrategia `ours` cuando el código ya había sido importado y evolucionado en la plataforma principal.

Commits de unión:

- `483d5c1`: conecta el repositorio original del motor de reservas.
- `f130a22`: hace alcanzables desde `main` todas las ramas históricas del principal.
- `1c2a6bc`: conecta el repositorio original del traductor.

La etiqueta histórica `v1.0.0` también fue preservada. Git cuenta commits únicos por SHA; un commit compartido por varias ramas se contabiliza una sola vez.

## Cronología funcional

### Julio de 2026 — Tema, estabilidad y SEO

- Nace el tema MeTransfers y se completa el primer saneamiento de seguridad y estabilidad.
- Se corrigen mojibake, BOM, cabeceras enviadas, mixed content y carga duplicada de recursos.
- Se introducen Schema JSON-LD, breadcrumbs, mejoras SEO y el sistema visual premium.
- Se construye la internacionalización nativa del tema y el selector de idiomas.
- Se crean páginas, rutas, servicios, tours, contenido legal y estructuras editoriales.
- Se añaden controles administrativos y herramientas de reparación/migración.

Commits representativos: `f960ba2`, `5715d03`, `f43b6f5`, `c4a59b2`, `655a74c`.

### Julio de 2026 — Traductor MT

- Evolución desde GCT Translator SEO Edition v4 hasta GCT Translator v5.
- Traducciones persistidas como contenido real, caché y rutas por idioma.
- Traducción de HTML estático, actualización de `lang` y manejo detallado de errores AJAX.
- Separación temporal como plugin y posterior integración en la plataforma.

Commits representativos: `2007305`, `31d6f0e`, `13031fe`, `7126e83`, `f1259db`.

### Julio–agosto de 2026 — Reservas y hoteles

- Se consolida el motor de reservas con buscador, vehículos, tarifas y checkout.
- Se integran Hotel QR, tokens, formularios hoteleros y administración de vehículos.
- Se incorpora importación/exportación de hoteles manteniendo sus tokens QR.
- Se refactorizan estilos, JavaScript, vistas y compatibilidad operativa.

Commits representativos: `319f77b`, `38447a8`, `f6f3f67`, `aed2842`.

### Agosto de 2026 — Plataforma modular

- Los módulos legacy se importan en `app/Legacy` y pasan a arrancar desde una aplicación PSR-4.
- Se centralizan CPT, administración, configuración, pricing, pagos y assets.
- Se mantiene compatibilidad con las tablas y contratos existentes.
- Se unifican reservas, flota, hoteles, traducciones y SEO en un tema desplegable.

Commits representativos: `ef04a30`, `da9b584`, `c8aeec5`, `ef480d9`, `572bbab`, `c555e33`.

### Agosto de 2026 — Diez fases de endurecimiento

1. Entradas públicas, nonces, permisos, rate limiting y tratamiento de secretos.
2. Outbox durable para trabajos posteriores al pago.
3. Borradores idempotentes de reserva.
4. Vehículos y precios cotizados por el servidor.
5. Dinero almacenado y comparado en céntimos enteros.
6. Recibos reconstruidos desde estado autoritativo.
7. Administración con mínimo privilegio y auditoría.
8. Migraciones discretas, bloqueadas y reanudables.
9. Router, caché, SEO e internacionalización modularizados.
10. Plataforma reproducible de PHPUnit, PHPStan, WPCS, ESLint, Playwright y WordPress real.

Commits principales: `03c2987`, `60cd038`, `199c2a7`, `f89b691`, `4cb8d71`, `5ec43d1`, `2941110`, `bc3df53`, `8d0d8ba`, `8556083`.

El informe detallado está en [docs/integration/RELEASE-CANDIDATE-10-10-CHANGES.md](docs/integration/RELEASE-CANDIDATE-10-10-CHANGES.md).

### Agosto de 2026 — Producción, diseño y regresiones

- Integración completa de las diez fases y reparación del formulario de reservas.
- Correcciones de Maps, codificación, estilos de vehículos y contraste del booking.
- Auditoría SEO, rediseño de páginas y sincronización de la versión V2.
- Correcciones específicas de rutas y carga de PortAventura.
- Cierre de PHPStan y PHPCS para el árbol integrado.

Commits representativos: `6d1ad71`, `be10884`, `90f5b19`, `92cf414`, `d047faa`, `1d280c8`.

### Septiembre de 2026 — Historia única

- Unión de todos los historiales y ramas en `metransfers.es/main` sin duplicar SHA.

## Cómo consultar cada cambio

```bash
# Cronología completa, incluyendo merges históricos.
git log --graph --decorate --date=short --pretty=format:'%ad %h %an %s' main

# Todos los commits y ramas conservados.
git log --all --graph --decorate --oneline

# Cambios de un commit concreto.
git show <sha>

# Commits por autor.
git shortlog -sne main

# Total de commits únicos alcanzables desde producción.
git rev-list --count main
```

## Documentación histórica relacionada

- [Informe de integración 10/10](docs/integration/RELEASE-CANDIDATE-10-10-CHANGES.md)
- [Inventario del tema](docs/integration/theme-inventory.md)
- [Inventario del plugin](docs/integration/plugin-inventory.md)
- [Contrato de base de datos](docs/integration/db-contract.md)
- [Contrato de API pública](docs/integration/public-api-contract.md)
- [Compatibilidad de staging](docs/integration/staging-compatibility-report.md)

## Política de conservación

- No reescribir `main` ni utilizar `push --force` para alterar el historial consolidado.
- No hacer squash o rebase de los commits históricos ya conectados.
- Conservar autores, fechas, mensajes y tags originales.
- Añadir cambios nuevos mediante commits verificables y los controles de calidad del repositorio.
- Nunca duplicar commits sólo para aumentar el contador mostrado por GitHub.
