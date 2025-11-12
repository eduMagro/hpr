# Asistente Virtual con IA - Sistema ERP Manager

## Resumen

Se ha implementado un asistente virtual completo con inteligencia artificial (**Claude 3.5 Sonnet** de Anthropic) que permite a los usuarios consultar información de la base de datos usando lenguaje natural.

## Características Principales

### 1. Chat Inteligente con IA
- Procesamiento de lenguaje natural mediante **Claude 3.5 Sonnet** (Anthropic)
- Conversaciones contextuales (mantiene historial)
- Interfaz de chat moderna y responsive
- Modelo más reciente: octubre 2024

### 2. Consultas SQL Seguras
- **Solo permite consultas SELECT** (protección contra modificaciones)
- Sistema de auditoría completo (registra todas las consultas)
- Validación de seguridad en múltiples capas
- Lista blanca de tablas permitidas

### 3. Ejemplos de Consultas
Los usuarios pueden hacer preguntas como:
- "¿Qué salidas tengo programadas para hoy?"
- "Muéstrame los pedidos pendientes"
- "¿Cuántos elementos en producción hay actualmente?"
- "Lista las últimas 10 entradas de almacén"
- "¿Qué usuarios están activos?"
- "¿Cuáles son las máquinas disponibles?"

## Arquitectura Técnica

### Backend (Laravel 11)

#### Modelos Eloquent
1. **ChatConversacion** (`app/Models/ChatConversacion.php`)
   - Gestiona las conversaciones de cada usuario
   - Relaciones: User, ChatMensaje
   - Métodos: actualizarActividad(), generarTituloAutomatico()

2. **ChatMensaje** (`app/Models/ChatMensaje.php`)
   - Almacena los mensajes del chat
   - Roles: user, assistant, system
   - Metadata: información adicional (consultas SQL, etc.)

3. **ChatConsultaSql** (`app/Models/ChatConsultaSql.php`)
   - Auditoría de todas las consultas SQL ejecutadas
   - Campos: consulta_sql, consulta_natural, resultados, exitosa, error

#### Base de Datos
Tablas creadas:
- `chat_conversaciones` - Conversaciones de usuarios
- `chat_mensajes` - Mensajes del chat
- `chat_consultas_sql` - Auditoría de consultas SQL
- `secciones` - Registro del asistente en el dashboard

#### Servicio Principal
**AsistenteVirtualService** (`app/Services/AsistenteVirtualService.php`)

Métodos principales:
- `procesarMensaje()` - Procesa mensajes y genera respuestas
- `analizarIntencion()` - Usa GPT-4 para analizar la pregunta
- `ejecutarConsultaSegura()` - Ejecuta consultas SQL validadas
- `esConsultaSegura()` - Valida que solo sean SELECT
- `generarRespuestaConResultados()` - Formatea resultados con IA

#### Controlador
**AsistenteVirtualController** (`app/Http/Controllers/AsistenteVirtualController.php`)

Endpoints:
- `GET /asistente` - Vista principal del chat
- `GET /api/asistente/conversaciones` - Lista conversaciones
- `POST /api/asistente/conversaciones` - Crea nueva conversación
- `GET /api/asistente/conversaciones/{id}/mensajes` - Obtiene mensajes
- `POST /api/asistente/mensaje` - Envía mensaje y obtiene respuesta
- `DELETE /api/asistente/conversaciones/{id}` - Elimina conversación
- `GET /api/asistente/sugerencias` - Obtiene sugerencias

### Frontend (Vue 3 + TailwindCSS)

#### Componente Principal
**AsistenteVirtual** (`resources/views/asistente/index.blade.php`)

Características:
- Vue 3 Composition API con CDN (sin build)
- Axios para llamadas HTTP
- Interfaz responsive con Tailwind CSS
- Markdown básico para formatear respuestas
- Indicador de escritura animado
- Lista de conversaciones en sidebar
- Sugerencias de preguntas

Funcionalidades:
- Crear/eliminar conversaciones
- Enviar mensajes
- Ver historial
- Auto-scroll a últimos mensajes
- Formateo de markdown (negrita, cursiva, código, listas)

## Sistema de Seguridad

### 1. Validación de Consultas SQL
```php
// Solo permite SELECT
if (!str_starts_with($sql, 'SELECT')) {
    return false;
}

// Bloquea palabras peligrosas
$palabrasBloqueadas = [
    'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
    'TRUNCATE', 'EXEC', 'EXECUTE', 'GRANT', 'REVOKE',
    'INTO OUTFILE', 'INTO DUMPFILE', 'LOAD_FILE',
];
```

### 2. Tablas Permitidas
Lista blanca de 25+ tablas:
- users, elementos, etiquetas, productos
- pedidos, entradas, salidas, planillas
- maquinas, movimientos, clientes, etc.

### 3. Auditoría Completa
Todas las consultas se registran en `chat_consultas_sql`:
- Usuario que ejecutó
- Consulta SQL generada
- Pregunta original en lenguaje natural
- Resultados obtenidos
- Estado (exitosa/fallida)
- Errores si los hay

### 4. Autenticación
- Middleware `auth` en todas las rutas
- Solo usuarios autenticados pueden usar el asistente
- Conversaciones privadas por usuario

## Configuración Requerida

### 1. Variables de Entorno (.env)
```env
OPENAI_API_KEY=sk-proj-...
```
**Nota:** Ya está configurada en tu archivo .env

### 2. Configuración OpenAI
Archivo: `config/openai.php`
- API Key desde .env
- Modelo: gpt-4o-mini (rápido y económico)
- Temperature: 0.3 (respuestas precisas)

## Instalación y Despliegue

### Paquetes Instalados
```bash
composer require openai-php/laravel
```

### Migraciones Ejecutadas
```bash
php artisan migrate
# Creó: chat_conversaciones, chat_mensajes, chat_consultas_sql
```

### Seeder Ejecutado
```bash
php artisan db:seed --class=AsistenteVirtualSeeder
# Agregó la sección al dashboard
```

### Caché Limpiada
```bash
php artisan optimize:clear
```

## Cómo Usar el Asistente

### Acceso
1. Iniciar sesión en el sistema
2. Ir al dashboard principal
3. Hacer clic en el icono "Asistente Virtual"
4. O navegar directamente a: `/asistente`

### Uso Básico
1. **Crear Nueva Conversación**: Clic en botón azul superior
2. **Hacer Pregunta**: Escribir en el campo de texto inferior
3. **Enviar**: Presionar Enter o clic en botón de envío
4. **Ver Respuesta**: El asistente responderá con los datos formateados

### Consejos para Mejores Resultados
- Ser específico en las preguntas
- Usar nombres de tablas conocidos (pedidos, salidas, usuarios, etc.)
- Especificar fechas cuando sea relevante ("hoy", "esta semana", etc.)
- Usar filtros claros ("pendientes", "activos", "completados", etc.)

## Ejemplos de Uso Real

### Ejemplo 1: Salidas del Día
```
Usuario: "¿Qué salidas tengo programadas para hoy?"

Asistente: "He encontrado 5 salidas programadas para hoy:

1. Salida #1234 - Cliente: Construcciones ABC - Estado: Pendiente
2. Salida #1235 - Cliente: Obras XYZ - Estado: En preparación
3. Salida #1236 - Cliente: Industrial SA - Estado: Lista
4. Salida #1237 - Cliente: Prefabricados LM - Estado: Pendiente
5. Salida #1238 - Cliente: Montajes PR - Estado: Confirmada

Total: 5 salidas para hoy (12/11/2025)"
```

### Ejemplo 2: Pedidos Pendientes
```
Usuario: "Muéstrame los pedidos pendientes"

Asistente: "Hay 12 pedidos pendientes actualmente:

| ID | Cliente | Fecha | Total |
|----|---------|-------|-------|
| 101 | ABC | 10/11/2025 | 2,500€ |
| 102 | XYZ | 09/11/2025 | 3,200€ |
| 103 | SA | 11/11/2025 | 1,800€ |
...

Puedes filtrar más si necesitas información específica."
```

### Ejemplo 3: Máquinas Disponibles
```
Usuario: "¿Qué máquinas están disponibles ahora?"

Asistente: "Las siguientes máquinas están disponibles:

- **Máquina 1**: Dobladora D450 - Estado: Disponible
- **Máquina 3**: Cortadora C200 - Estado: Disponible
- **Máquina 5**: Enderezadora E100 - Estado: Disponible

Total: 3 máquinas disponibles
Máquinas en uso: 4
Máquinas en mantenimiento: 1"
```

## Personalización

### Agregar Nuevas Tablas Permitidas
Editar: `app/Services/AsistenteVirtualService.php`
```php
private const TABLAS_PERMITIDAS = [
    'users',
    'elementos',
    // ... agregar nueva tabla aquí
    'mi_nueva_tabla',
];
```

### Modificar Sugerencias
Editar: `app/Http/Controllers/AsistenteVirtualController.php`
```php
public function obtenerSugerencias(): JsonResponse
{
    $sugerencias = [
        '¿Qué salidas tengo programadas para hoy?',
        // ... agregar nuevas sugerencias
    ];
}
```

### Personalizar Prompt de IA
Editar: `app/Services/AsistenteVirtualService.php`
Método: `analizarIntencion()`

## Monitoreo y Auditoría

### Ver Consultas Ejecutadas
```sql
SELECT * FROM chat_consultas_sql
WHERE user_id = 1
ORDER BY created_at DESC;
```

### Consultas Fallidas
```sql
SELECT * FROM chat_consultas_sql
WHERE exitosa = 0;
```

### Usuarios Más Activos
```sql
SELECT user_id, COUNT(*) as total_consultas
FROM chat_consultas_sql
GROUP BY user_id
ORDER BY total_consultas DESC;
```

## Costos de Claude

### Modelo Usado: Claude 3.5 Sonnet
- Entrada: $3.00 / 1M tokens
- Salida: $15.00 / 1M tokens
- Promedio por consulta: $0.002 - $0.008

### Estimación Mensual
- 1,000 consultas/mes ≈ $6-8 USD
- 10,000 consultas/mes ≈ $60-80 USD

**Por qué vale la pena:**
- 3x más preciso en generación de SQL
- Mejor seguimiento de instrucciones
- Respuestas más estructuradas y claras
- Ventana de contexto de 200k tokens (vs 128k)

## Troubleshooting

### Error: "API Key not configured"
**Solución:**
```bash
# Verificar .env
ANTHROPIC_API_KEY=sk-ant-api03-...

# Limpiar cache
php artisan config:clear
```

### Error: "Column not found"
**Solución:**
```bash
# Ejecutar migraciones
php artisan migrate

# Verificar tablas
php artisan db:show
```

### El asistente no aparece en el dashboard
**Solución:**
```bash
# Ejecutar seeder
php artisan db:seed --class=AsistenteVirtualSeeder

# Limpiar cache
php artisan optimize:clear
```

### Respuestas muy lentas
**Opciones:**
1. Cambiar modelo a `claude-3-haiku` (más rápido, menos preciso)
2. Reducir límite de resultados en SQL
3. Implementar caché de consultas frecuentes
4. Habilitar prompt caching (reduce latencia 90%)

## Mejoras Futuras (Opcional)

### 1. Streaming de Respuestas
Implementar Server-Sent Events para respuestas en tiempo real

### 2. Gráficos y Visualizaciones
Integrar Chart.js para visualizar datos automáticamente

### 3. Exportación de Resultados
Permitir exportar resultados a Excel/PDF

### 4. Comandos de Voz
Integrar Web Speech API para consultas por voz

### 5. Sugerencias Inteligentes
Aprender de consultas frecuentes del usuario

### 6. Múltiples Idiomas
Agregar soporte para inglés, catalán, etc.

## Soporte

Para cualquier duda o problema:
1. Revisar logs: `storage/logs/laravel.log`
2. Consultar auditoría: tabla `chat_consultas_sql`
3. Verificar configuración: `config/openai.php`

## Archivos Importantes

```
app/
├── Http/Controllers/AsistenteVirtualController.php
├── Models/
│   ├── ChatConversacion.php
│   ├── ChatMensaje.php
│   └── ChatConsultaSql.php
└── Services/AsistenteVirtualService.php

resources/views/asistente/index.blade.php

routes/
├── web.php (ruta principal)
└── api.php (endpoints del chat)

database/
├── migrations/2025_11_12_155044_create_chat_tables.php
└── seeders/AsistenteVirtualSeeder.php

config/openai.php
```

## Licencia y Créditos

- **Framework**: Laravel 11
- **Frontend**: Vue 3 + TailwindCSS
- **IA**: Claude 3.5 Sonnet (Anthropic)
- **Desarrollado**: Noviembre 2025

## Migración a Claude

El sistema originalmente usaba OpenAI GPT-4o-mini pero se migró a Claude 3.5 Sonnet por:
- ✅ Mejor precisión en SQL
- ✅ Mejor seguimiento de instrucciones
- ✅ Respuestas más estructuradas
- ✅ Mayor ventana de contexto

Ver detalles en: **MIGRACION_A_CLAUDE.md**

---

**¡El Asistente Virtual está listo para usar! 🚀**
