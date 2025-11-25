# 🧪 Testing Checklist - Vista Producción/Máquinas

## 📊 Comparación de Archivos

```bash
Original:  233 KB (4,587 líneas)
Nuevo:      49 KB (739 líneas)
Reducción:  79% en tamaño de archivo
```

## ✅ Tests de Funcionalidad

### 1. Carga Inicial
- [ ] El calendario se renderiza sin errores en consola
- [ ] Los datos de máquinas se cargan correctamente
- [ ] Las planillas aparecen en el calendario
- [ ] Los turnos se muestran con colores correctos
- [ ] El panel de filtros está colapsado inicialmente

### 2. Filtros
- [ ] Filtro por Cliente funciona
- [ ] Filtro por Código Cliente funciona
- [ ] Filtro por Obra funciona
- [ ] Filtro por Código Obra funciona
- [ ] Filtro por Código Planilla funciona
- [ ] Filtro por Fecha Entrega funciona
- [ ] Filtro por Estado funciona
- [ ] Botón "Restablecer filtros" limpia todos los filtros
- [ ] Badge de filtros activos muestra el número correcto
- [ ] Eventos se opacan/resaltan según filtros

### 3. Calendario
- [ ] Vista 3 días funciona
- [ ] Vista 7 días funciona
- [ ] Vista 14 días funciona
- [ ] Botones prev/next funcionan
- [ ] Botón "today" funciona
- [ ] Header se mantiene sticky al hacer scroll
- [ ] Líneas de inicio de turno se muestran

### 4. Drag & Drop
- [ ] Planillas se pueden arrastrar
- [ ] Indicador de posición aparece al arrastrar
- [ ] Se puede mover planilla en la misma máquina
- [ ] Se puede mover planilla a otra máquina
- [ ] Modal de confirmación aparece al soltar
- [ ] Reordenamiento se guarda correctamente
- [ ] Calendario se actualiza después de reordenar
- [ ] Si se cancela, la planilla vuelve a su posición

### 5. Tooltips
- [ ] Tooltip aparece al pasar mouse sobre evento
- [ ] Tooltip muestra información correcta (obra, estado, duración, etc.)
- [ ] Tooltip indica si planilla está revisada o no
- [ ] Tooltip desaparece al quitar el mouse
- [ ] Tooltips se ocultan mientras se arrastra

### 6. Panel Lateral de Elementos
- [ ] Panel se abre al hacer clic en una planilla
- [ ] Lista de elementos se carga correctamente
- [ ] Información de elementos es correcta (código, longitud, peso, diámetro)
- [ ] Panel se cierra con el botón X
- [ ] Panel se cierra al hacer clic en el overlay
- [ ] Botón "Marcar como revisada" funciona

### 7. Modales
#### Modal Cambiar Estado
- [ ] Modal se abre al hacer clic en botón "Estado"
- [ ] Nombre de máquina se muestra correctamente
- [ ] Botones de estados funcionan (activa, averiada, mantenimiento, pausa)
- [ ] Estado se actualiza en el servidor
- [ ] Calendario se actualiza después del cambio
- [ ] Modal se cierra después de cambiar estado

#### Modal Redistribuir Cola
- [ ] Modal se abre al hacer clic en botón "Redistribuir"
- [ ] Opciones "primeros elementos" y "todos" funcionan
- [ ] Redistribución se ejecuta correctamente
- [ ] Calendario se actualiza después de redistribuir
- [ ] Modal de resultados muestra información correcta

### 8. Gestión de Turnos
- [ ] Turnos activos se muestran en verde
- [ ] Turnos inactivos se muestran en gris
- [ ] Toggle de turno cambia el estado
- [ ] Líneas separadoras aparecen en horas de inicio de turno
- [ ] Cambio de turno se guarda en el servidor

### 9. Modo Pantalla Completa
- [ ] Botón "Expandir" activa pantalla completa
- [ ] Sidebar se oculta en pantalla completa
- [ ] Header se oculta en pantalla completa
- [ ] Breadcrumbs se ocultan en pantalla completa
- [ ] Botón cambia a "Contraer"
- [ ] Tecla ESC sale de pantalla completa
- [ ] Todo se restaura al salir de pantalla completa

### 10. Botones de Acción
- [ ] Botón "Optimizar Planillas" muestra modal
- [ ] Botón "Balancear Carga" muestra modal
- [ ] (Funcionalidades pendientes de implementar están marcadas como "en desarrollo")

### 11. Responsive
- [ ] Vista funciona en desktop (1920x1080)
- [ ] Vista funciona en laptop (1366x768)
- [ ] Vista funciona en tablet (768x1024)
- [ ] Botones se adaptan a pantalla pequeña

### 12. Performance
- [ ] Página carga en menos de 3 segundos
- [ ] No hay errores en consola
- [ ] No hay warnings críticos
- [ ] Drag & drop es fluido (sin lag)
- [ ] Cambio de vistas es instantáneo
- [ ] Filtros responden rápido (debounce funciona)

### 13. Compatibilidad de Navegadores
- [ ] Chrome/Edge (última versión)
- [ ] Firefox (última versión)
- [ ] Safari (última versión)

## 🐛 Errores Comunes a Verificar

### Consola del Navegador
```javascript
// NO debería aparecer:
❌ Cannot read property 'X' of undefined
❌ FullCalendar is not defined
❌ Uncaught ReferenceError
❌ Failed to fetch

// Puede aparecer (avisos de Vite en dev):
⚠️ [vite] connecting...
⚠️ [vite] connected
```

### Network Tab
```
✅ Status 200 para todos los assets
✅ maquinas.HASH.css se carga
✅ index.HASH.js se carga
✅ No 404 en scripts
```

## 📝 Notas de Testing

### Entorno de Desarrollo
```bash
# Iniciar servidor Vite
npm run dev

# Acceder a:
http://localhost/manager/produccion/maquinas
```

### Entorno de Producción
```bash
# Compilar assets
npm run build

# Verificar que se generaron:
public/build/manifest.json
public/build/assets/maquinas.*.css
public/build/assets/index.*.js
```

## 🆘 Troubleshooting

### Si algo no funciona:

1. **Verificar consola de errores**
   ```javascript
   // Abrir DevTools (F12)
   // Ver pestaña Console
   ```

2. **Verificar que assets están compilados**
   ```bash
   ls -la public/build/assets/maquinas.*
   ls -la public/build/assets/index.*
   ```

3. **Limpiar caché**
   ```bash
   # Navegador: Ctrl+Shift+R (hard reload)
   # Laravel: php artisan cache:clear
   # Vite: rm -rf node_modules/.vite
   ```

4. **Recompilar**
   ```bash
   npm run build
   ```

5. **Restaurar backup si es necesario**
   ```bash
   cp resources/views/produccion/maquinas.blade.php.backup resources/views/produccion/maquinas.blade.php
   ```

## ✅ Criterios de Aceptación

La refactorización es exitosa si:
- ✅ Todas las funcionalidades del checklist funcionan
- ✅ No hay errores en consola
- ✅ Performance es igual o mejor que antes
- ✅ Bundle size es menor que antes
- ✅ HMR funciona en desarrollo

## 📊 Métricas Esperadas

### Lighthouse Score (objetivo)
- Performance: > 90
- Accessibility: > 90
- Best Practices: > 90
- SEO: > 90

### Bundle Analysis
```bash
# Ver tamaño de bundles
npm run build -- --mode production

# Resultado esperado:
maquinas.css:     ~5 KB (gzip: ~1.6 KB)
index.js:        ~24 KB (gzip: ~7.8 KB)
fullcalendar:   ~382 KB (gzip: ~109 KB)
```

---

**Testing completado por**: _________________
**Fecha**: _________________
**Estado**: [ ] ✅ APROBADO  [ ] ❌ REQUIERE AJUSTES
**Comentarios**: ___________________________________________
