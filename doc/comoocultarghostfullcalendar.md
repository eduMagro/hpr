# Cómo ocultar el ghost por defecto de FullCalendar

## Problema

Al implementar un ghost personalizado para el drag & drop en FullCalendar, el ghost por defecto del calendario seguía apareciendo superpuesto.

## Investigación

Usando debug con `document.elementsFromPoint()` durante el drag, se descubrió que:

1. **FullCalendar NO usa `.fc-event-mirror`** en la vista mensual (`dayGridMonth`)
2. En su lugar, **mueve el elemento original** del evento con:
   - `position: fixed`
   - Clase `fc-event-dragging`

```javascript
// Elemento encontrado durante el drag:
{
    tag: "A",
    classes: "fc-event fc-event-draggable fc-event-start fc-event-end fc-event-past fc-daygrid-event fc-daygrid-dot-event fc-event-dragging",
    position: "fixed",
    style: "width: 155.531px; position: fixed; ..."
}
```

## Solución

### 1. Ocultar con JavaScript durante el drag

En `eventDragStart`, usar `requestAnimationFrame` para detectar y ocultar continuamente los elementos con `fc-event-dragging` y `position: fixed`:

```javascript
eventDragStart: (info) => {
    window._isDragging = true;

    // Función para ocultar el ghost de FullCalendar
    const hideFullCalendarGhost = () => {
        document.querySelectorAll('.fc-event-dragging').forEach(el => {
            const style = window.getComputedStyle(el);
            if (style.position === 'fixed') {
                el.style.setProperty('opacity', '0', 'important');
                el.style.setProperty('visibility', 'hidden', 'important');
                el.style.setProperty('pointer-events', 'none', 'important');
            }
        });

        if (window._isDragging) {
            requestAnimationFrame(hideFullCalendarGhost);
        }
    };

    requestAnimationFrame(hideFullCalendarGhost);
}
```

### 2. CSS de respaldo (no suficiente por sí solo)

El CSS solo no funciona porque FullCalendar aplica estilos inline con mayor prioridad:

```css
/* Esto NO funciona completamente */
.fc-event-dragging[style*="position: fixed"] {
    opacity: 0 !important;
}
```

### 3. Inyectar estilos globales (opcional)

Para mayor robustez, inyectar estilos al crear el calendario:

```javascript
if (!document.getElementById('fc-mirror-hide-style-global')) {
    const globalStyle = document.createElement('style');
    globalStyle.id = 'fc-mirror-hide-style-global';
    globalStyle.textContent = `
        .fc-event-dragging[style*="position: fixed"],
        a.fc-event.fc-event-dragging {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
    `;
    document.head.appendChild(globalStyle);
}
```

## Por qué otras soluciones NO funcionaron

| Intento | Por qué falló |
|---------|---------------|
| Ocultar `.fc-event-mirror` | FullCalendar no usa esta clase en vista mensual |
| CSS con `!important` | Los estilos inline de FullCalendar tienen prioridad |
| `setDragImage()` transparente | FullCalendar no usa HTML5 Drag API internamente |
| `draggable="false"` | No afecta el sistema de pointer events de FullCalendar |
| `MutationObserver` | El elemento ya existe, solo cambia de posición |

## Clave de la solución

La clave es usar `getComputedStyle()` para detectar elementos con `position: fixed` (no el atributo `style`, sino el estilo computado) y ocultarlos **continuamente** durante el drag con `requestAnimationFrame`.

## Archivos modificados

- `resources/js/modules/calendario-salidas/calendar.js` - Lógica de ocultación
- `resources/css/estilosCalendarioSalidas.css` - Estilos del ghost personalizado

## Versión de FullCalendar

Esta solución fue probada con FullCalendar v6.1.8 (Scheduler).
