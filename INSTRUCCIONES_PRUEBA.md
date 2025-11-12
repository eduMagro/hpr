# Instrucciones para Probar el Asistente Virtual

## ✅ Todo está instalado y configurado

El sistema del Asistente Virtual está completamente funcional. Sigue estos pasos para probarlo:

---

## Paso 1: Iniciar el servidor (si no está corriendo)

```bash
cd C:\xampp\htdocs\manager
php artisan serve
```

O simplemente accede vía XAMPP: `http://localhost/manager`

---

## Paso 2: Acceder al Asistente

### Opción A: Desde el Dashboard
1. Abre tu navegador
2. Ve a: `http://localhost/manager` (o tu URL configurada)
3. Inicia sesión con tu usuario
4. En el dashboard verás un nuevo icono: **"Asistente Virtual"**
5. Haz clic en él

### Opción B: URL Directa
- Ve directamente a: `http://localhost/manager/asistente`

---

## Paso 3: Usar el Asistente

### 3.1 Crear una conversación
1. Haz clic en el botón azul **"Nueva conversación"** (esquina superior izquierda)
2. Se creará una conversación nueva y verás el área de chat

### 3.2 Hacer tu primera pregunta
Escribe en el campo de texto inferior (ejemplos):

```
¿Qué salidas tengo programadas para hoy?
```
```
Muéstrame los pedidos pendientes
```
```
¿Cuántos elementos hay en producción?
```
```
Lista los últimos 10 usuarios
```
```
¿Qué máquinas están disponibles?
```

### 3.3 Ver la respuesta
- El asistente analizará tu pregunta
- Generará una consulta SQL automáticamente
- Ejecutará la consulta de forma segura
- Te mostrará los resultados formateados

---

## Ejemplos de Consultas

### Consulta 1: Salidas de hoy
```
Usuario: ¿Qué salidas tengo programadas para hoy?

Asistente: He encontrado 5 salidas programadas para hoy:
1. Salida #1234 - Cliente: ABC - Estado: Pendiente
2. Salida #1235 - Cliente: XYZ - Estado: En preparación
...
```

### Consulta 2: Usuarios activos
```
Usuario: ¿Qué usuarios están activos?

Asistente: Hay 15 usuarios activos:
- Juan Pérez (Operario)
- María García (Oficina)
- Pedro López (Admin)
...
```

### Consulta 3: Pedidos pendientes
```
Usuario: Muéstrame los pedidos pendientes

Asistente: Hay 8 pedidos pendientes:
| ID | Cliente | Fecha | Estado |
|----|---------|-------|--------|
| 101| ABC     |10/11  |Pendiente|
...
```

---

## Funcionalidades del Chat

### ✅ Conversaciones múltiples
- Puedes crear varias conversaciones
- Cada conversación mantiene su propio historial
- Haz clic en una conversación del sidebar para cambiar

### ✅ Historial contextual
- El asistente recuerda las últimas preguntas
- Puedes hacer preguntas de seguimiento
- Ejemplo:
  ```
  Tú: "Muéstrame los pedidos"
  Asistente: [lista de pedidos]
  Tú: "¿Cuántos son del cliente ABC?"
  Asistente: [filtra por ABC]
  ```

### ✅ Formateo rico
- **Negrita**: `**texto**`
- *Cursiva*: `*texto*`
- `Código`: \`código\`
- Listas automáticas
- Tablas markdown

### ✅ Eliminar conversaciones
- Haz clic en el icono de papelera (esquina superior derecha)
- Confirma la eliminación

---

## Verificar que funciona

### Test 1: Consulta simple
```
Pregunta: "Lista todos los usuarios"
Esperado: Lista de usuarios de la tabla 'users'
```

### Test 2: Filtro por fecha
```
Pregunta: "¿Qué salidas hay hoy?"
Esperado: Salidas con fecha de hoy (DATE(fecha) = CURDATE())
```

### Test 3: Conteo
```
Pregunta: "¿Cuántos pedidos hay?"
Esperado: Número total de pedidos
```

### Test 4: JOIN
```
Pregunta: "Muéstrame los pedidos con sus clientes"
Esperado: Pedidos con información de clientes (JOIN)
```

---

## Troubleshooting

### Error: "La página no carga"
**Solución:**
```bash
cd C:\xampp\htdocs\manager
php artisan optimize:clear
```

### Error: "No aparece el asistente en el dashboard"
**Solución:**
```bash
php artisan db:seed --class=AsistenteVirtualSeeder
php artisan optimize:clear
```

### Error: "API Key not configured"
**Verificar que en `.env` exista:**
```env
OPENAI_API_KEY=sk-proj-YOUR_OPENAI_API_KEY_HERE
```

**Luego:**
```bash
php artisan config:clear
```

### Error: "No hay respuesta del asistente"
**Revisar logs:**
```bash
# Windows
type storage\logs\laravel.log | findstr /C:"AsistenteVirtual"

# O abrir manualmente:
# storage/logs/laravel.log
```

### Error: "Console log: 404 Not Found"
**Verificar rutas:**
```bash
php artisan route:list --path=asistente
```

---

## Ver la Auditoría

Para ver todas las consultas ejecutadas, puedes revisar la tabla:

```sql
-- Últimas 10 consultas
SELECT
    u.name as usuario,
    ccs.consulta_natural,
    ccs.consulta_sql,
    ccs.filas_afectadas,
    ccs.exitosa,
    ccs.created_at
FROM chat_consultas_sql ccs
JOIN users u ON ccs.user_id = u.id
ORDER BY ccs.created_at DESC
LIMIT 10;
```

---

## Preguntas de Ejemplo para Probar

### Básicas:
1. "Lista todos los usuarios"
2. "Muéstrame los clientes"
3. "¿Cuántas máquinas hay?"
4. "¿Qué alertas hay?"

### Con filtros:
5. "Muéstrame los pedidos de esta semana"
6. "¿Qué salidas hay pendientes?"
7. "Lista los elementos en producción"
8. "¿Qué usuarios son operarios?"

### Con fechas:
9. "¿Qué movimientos hay hoy?"
10. "Muéstrame las entradas de ayer"
11. "¿Qué planillas se hicieron esta semana?"
12. "Lista las salidas de este mes"

### Conversacionales:
13. "Hola, ¿qué puedes hacer?"
14. "Gracias"
15. "¿Cómo puedo ver los pedidos?"

---

## Próximos Pasos

Una vez que funcione correctamente, puedes:

1. **Personalizar el icono:**
   - Reemplaza: `public/imagenes/iconos/asistente.png`
   - Con un icono de 128x128 o 256x256 PNG

2. **Agregar más tablas permitidas:**
   - Edita: `app/Services/AsistenteVirtualService.php`
   - Línea ~20: constante `TABLAS_PERMITIDAS`

3. **Modificar sugerencias:**
   - Edita: `app/Http/Controllers/AsistenteVirtualController.php`
   - Método: `obtenerSugerencias()`

4. **Personalizar el prompt de IA:**
   - Edita: `app/Services/AsistenteVirtualService.php`
   - Método: `analizarIntencion()` línea ~100

---

## Comandos Útiles

```bash
# Limpiar todo el caché
php artisan optimize:clear

# Ver rutas del asistente
php artisan route:list --path=asistente

# Ver logs en tiempo real
php artisan pail

# Re-ejecutar migraciones (¡CUIDADO! borra datos)
php artisan migrate:fresh
php artisan db:seed --class=AsistenteVirtualSeeder

# Solo agregar la sección al dashboard (si falta)
php artisan db:seed --class=AsistenteVirtualSeeder
```

---

## Soporte

Si tienes problemas:

1. **Revisa los logs:** `storage/logs/laravel.log`
2. **Verifica las tablas:** Que existan `chat_conversaciones`, `chat_mensajes`, `chat_consultas_sql`
3. **Comprueba la API Key:** En `.env` debe estar `OPENAI_API_KEY`
4. **Limpia caché:** `php artisan optimize:clear`

---

## 🎉 ¡Listo para usar!

El Asistente Virtual está completamente funcional. Solo accede a `/asistente` y comienza a hacer preguntas.

**Disfruta de tu nuevo asistente con IA!** 🚀
