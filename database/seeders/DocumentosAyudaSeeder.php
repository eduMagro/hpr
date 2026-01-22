<?php

namespace Database\Seeders;

use App\Models\DocumentoAyuda;
use Illuminate\Database\Seeder;

class DocumentosAyudaSeeder extends Seeder
{
    /**
     * Seed the documentos_ayuda table with initial knowledge base.
     * Los embeddings se generarán después con el comando o desde el panel admin.
     */
    public function run(): void
    {
        $documentos = [
            // ═══════════════════════════════════════════════════════════════
            // FICHAJES
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'fichajes',
                'titulo' => 'Cómo fichar entrada',
                'contenido' => 'Para fichar entrada (solo operarios):
1. Haz clic en tu nombre en la esquina superior derecha
2. Pulsa el botón VERDE "Fichar Entrada"
3. Acepta los permisos de ubicación GPS cuando el navegador lo solicite
4. Espera a que aparezca el modal de confirmación
5. Haz clic en "Sí, fichar"

Requisitos:
- Debes estar dentro de la zona GPS configurada para tu obra
- El sistema detecta automáticamente tu turno según la hora
- Si fichas fuera de horario recibirás un aviso',
                'keywords' => 'fichar, entrada, fichaje, gps, ubicación, turno, operario',
                'orden' => 1,
            ],
            [
                'categoria' => 'fichajes',
                'titulo' => 'Cómo fichar salida',
                'contenido' => 'Para fichar salida:
1. Haz clic en tu nombre en la esquina superior derecha
2. Pulsa el botón ROJO "Fichar Salida"
3. Confirma la acción en el modal

El sistema registra automáticamente la hora de salida.',
                'keywords' => 'fichar, salida, fichaje, terminar',
                'orden' => 2,
            ],
            [
                'categoria' => 'fichajes',
                'titulo' => 'Ver mis fichajes',
                'contenido' => 'Para ver tus registros de fichajes:
1. Ve a Recursos Humanos desde el menú principal
2. Haz clic en "Registros Entrada/Salida"
3. Verás una tabla con todos tus fichajes
4. Puedes filtrar por fecha usando los controles superiores',
                'keywords' => 'ver, fichajes, historial, registros, entrada, salida',
                'orden' => 3,
            ],

            // ═══════════════════════════════════════════════════════════════
            // VACACIONES
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'vacaciones',
                'titulo' => 'Cómo solicitar vacaciones',
                'contenido' => 'Para solicitar vacaciones (operarios):
1. Haz clic en tu nombre (esquina superior derecha) → "Mi Perfil"
2. Verás un calendario con tus turnos asignados
3. Sistema de selección "clic-clic":

PRIMER CLIC: Haz clic en el día de inicio - se resaltará en azul

SEGUNDO CLIC:
- Si haces clic en el MISMO día = solicitas solo ese día
- Si haces clic en un DÍA DIFERENTE = creas un rango completo
- Mientras mueves el ratón verás el resaltado visual del rango

4. Aparecerá el modal "Solicitar vacaciones" con las fechas
5. Haz clic en "Enviar solicitud"
6. Tu solicitud quedará como "pendiente" hasta que RRHH la apruebe

Tip: Presiona ESC para cancelar la selección antes del segundo clic',
                'keywords' => 'vacaciones, solicitar, días, festivos, permiso, descanso',
                'orden' => 1,
            ],
            [
                'categoria' => 'vacaciones',
                'titulo' => 'Gestión de vacaciones (RRHH)',
                'contenido' => 'Para gestionar vacaciones desde RRHH:
1. Ve a Recursos Humanos → Vacaciones
2. Verás calendarios organizados por departamento (Maquinistas, Ferrallas, Oficina)
3. Las solicitudes pendientes aparecen destacadas
4. Haz clic en una solicitud para ver detalles
5. Puedes Aprobar o Denegar la solicitud
6. Personal de oficina puede asignar vacaciones directamente',
                'keywords' => 'vacaciones, aprobar, denegar, gestionar, rrhh, calendario',
                'orden' => 2,
            ],

            // ═══════════════════════════════════════════════════════════════
            // NÓMINAS
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'nominas',
                'titulo' => 'Cómo descargar mi nómina',
                'contenido' => 'Para recibir tu nómina por email:
1. Haz clic en tu nombre (esquina superior derecha)
2. Baja hasta la sección "Mis Nóminas"
3. Selecciona el mes y año que quieres recibir
4. Haz clic en "Descargar Nómina"
5. El sistema ENVIARÁ la nómina a tu correo electrónico
6. Revisa tu email - recibirás un PDF adjunto

IMPORTANTE:
- Las nóminas deben estar generadas previamente por RRHH
- Debes tener un email configurado en tu perfil
- El PDF se envía por email, NO se descarga directamente
- Si no recibes el email, revisa tu carpeta de spam',
                'keywords' => 'nómina, nomina, sueldo, descargar, email, pdf, salario',
                'orden' => 1,
            ],

            // ═══════════════════════════════════════════════════════════════
            // CONTRASEÑAS
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'contrasenas',
                'titulo' => 'Recuperar contraseña olvidada',
                'contenido' => 'Si olvidaste tu contraseña:
1. Ve a la página de login
2. Haz clic en "¿Olvidaste tu contraseña?"
3. Introduce tu correo electrónico
4. Revisa tu email y sigue el enlace de recuperación
5. Establece una nueva contraseña

Si no recibes el email, revisa la carpeta de spam.',
                'keywords' => 'contraseña, password, olvidé, recuperar, restablecer, clave',
                'orden' => 1,
            ],
            [
                'categoria' => 'contrasenas',
                'titulo' => 'Cambiar contraseña',
                'contenido' => 'Para cambiar tu contraseña si la recuerdas:
- Contacta con administración o tu supervisor
- Ellos pueden cambiarla desde el panel de usuarios

Nota: Por seguridad, no puedes cambiarla tú mismo desde el perfil.',
                'keywords' => 'contraseña, cambiar, modificar, clave',
                'orden' => 2,
            ],

            // ═══════════════════════════════════════════════════════════════
            // PEDIDOS
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'pedidos',
                'titulo' => 'Recepcionar pedido - Paso 1: Activar línea',
                'contenido' => 'PASO 1 para recepcionar un pedido - Activar la línea:

ACCESO: Ir a Logística → Pedidos

Pulsar "Activar línea" (botón amarillo) en la línea a recepcionar.

⚠️ IMPORTANTE: Solo se pueden activar líneas cuando la nave es HPR.

FORMAS DE ACTIVAR UNA LÍNEA:

🔹 Opción A - Automatizada:
1. Escanear el albarán
2. El sistema activa automáticamente la línea correcta

🔹 Opción B - Manual:
1. Leer el albarán
2. Determinar tipo de entrega:
   - Directa: cliente = HPR (fabricante indiferente)
   - Indirecta: cliente = distribuidor (actúa como intermediario)
3. Aplicar filtros en este orden:
   - Distribuidor o fabricante (según tipo de entrega)
   - Nave donde se recepciona
   - Tipo de producto (según albarán)
   - Estado: Pendiente o Parcial
   - Fecha: seleccionar la más cercana a la actual

RESULTADO: Ambas opciones generan un movimiento. El gruista lo ve en su zona de trabajo para realizar la recepción.',
                'keywords' => 'pedido, recepcionar, activar, línea, entrada, material, albarán, escanear, directa, indirecta, distribuidor, fabricante, HPR',
                'orden' => 1,
            ],
            [
                'categoria' => 'pedidos',
                'titulo' => 'Recepcionar pedido - Paso 2: Máquina GRÚA',
                'contenido' => 'PASO 2 para recepcionar un pedido - Ir a máquina GRÚA:
1. Ve a Producción → Máquinas
2. Selecciona una máquina tipo GRÚA
3. En la sección "Movimientos Pendientes" verás la entrada que activaste
4. Haz clic en el botón "Entrada" (naranja)

Esto te llevará al wizard de recepción (Paso 3).',
                'keywords' => 'pedido, grúa, grua, máquina, movimientos, entrada',
                'orden' => 2,
            ],
            [
                'categoria' => 'pedidos',
                'titulo' => 'Recepcionar pedido - Paso 3: Wizard de recepción',
                'contenido' => 'PASO 3 para recepcionar un pedido - El wizard te guía paso a paso:

1. Cantidad de paquetes: ¿1 o 2 paquetes?
2. Fabricante: Selecciona el fabricante (si aplica)
3. Código del paquete: Escanea o escribe el código (debe empezar por MP)
4. Número de colada: Introduce el número de colada
5. Número de paquete: Introduce el número del paquete
6. Si son 2 paquetes, repite pasos 3-5 para el segundo
7. Peso total (kg): Introduce el peso en kilogramos
8. Ubicación: Selecciona Sector → Ubicación (o marca checkbox para escanear)
9. Revisar y confirmar todos los datos
10. El sistema registra la entrada

Cuando termines TODO, haz clic en "Cerrar Albarán"

Tip: Los datos se guardan automáticamente si sales, puedes continuar después.',
                'keywords' => 'pedido, recepcionar, wizard, paquete, colada, peso, ubicación',
                'orden' => 3,
            ],

            // ═══════════════════════════════════════════════════════════════
            // PLANILLAS
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'planillas',
                'titulo' => 'Cómo importar una planilla',
                'contenido' => 'Para importar una planilla (Excel o BVBS):
1. Ve a Producción → Planillas
2. Haz clic en "Importar Planilla"
3. Selecciona el archivo desde tu ordenador:
   - Excel: Columnas requeridas: Posicion, Nombre, Ø, L, NºBarras, kg/ud
   - BVBS: Formato estándar de la industria
4. Completa el formulario:
   - Cliente (obligatorio)
   - Obra (obligatorio)
   - Fecha de aprobación (el sistema calcula entrega = aprobación + 7 días)
5. Haz clic en "Importar"
6. El sistema procesa en background con barra de progreso

IMPORTANTE: La importación puede tardar varios minutos si el archivo es grande.',
                'keywords' => 'planilla, importar, excel, bvbs, subir, cargar',
                'orden' => 1,
            ],
            // ═══════════════════════════════════════════════════════════════
            // PLANIFICACIÓN DE MÁQUINAS
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'planificacion',
                'titulo' => 'Vista de Producción Máquinas - Introducción',
                'contenido' => 'La vista Producción → Máquinas es el centro de planificación de la fábrica.

ACCESO: Producción → Máquinas

QUÉ MUESTRA:
- Calendario Timeline con todas las máquinas (una fila por máquina)
- Planillas como bloques de colores con barra de progreso
- Panel de filtros colapsable
- Botones de acciones masivas: Optimizar, Balancear, Priorizar, Retrasos, Resumen, Historial

ACCIONES PRINCIPALES:
- Arrastrar planillas entre máquinas (drag & drop)
- Click en planilla → ver elementos en panel lateral
- Optimizar planillas con retraso automáticamente
- Balancear carga de trabajo entre máquinas
- Priorizar obras urgentes
- Ver y resolver retrasos
- Deshacer cualquier cambio (reversible)

CADA FILA DE MÁQUINA TIENE:
- Nombre de la máquina
- Botón 🔴 para cambiar estado (Activa/Averiada/Mantenimiento/Pausa)
- Botón 🔀 para redistribuir su cola de trabajo

⚠️ IMPORTANTE: Todas las operaciones quedan registradas en el historial y pueden deshacerse.',
                'keywords' => 'planificación, máquinas, calendario, producción, vista, timeline',
                'orden' => 1,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Mover planilla a otra máquina (drag & drop)',
                'contenido' => 'Para mover una planilla de una máquina a otra:

PASOS:
1. Ve a Producción → Máquinas
2. Localiza la planilla en el calendario
3. Haz clic y MANTÉN PULSADO sobre el bloque de la planilla
4. Aparecerá un indicador circular con número de posición
5. Arrastra hacia la fila de otra máquina
6. El número de posición se actualiza mientras arrastras
7. Suelta en la máquina destino

VALIDACIÓN DE COMPATIBILIDAD:
El sistema verifica que los elementos sean compatibles con la máquina destino (diámetro mínimo/máximo).

- Si TODOS son compatibles → Se mueven automáticamente
- Si ALGUNOS son incompatibles → Pregunta de confirmación:
  * Muestra cantidad compatible vs incompatible
  * Si confirmas: mueve solo los compatibles
  * Los incompatibles quedan en máquina original
- Si NINGUNO es compatible → Error, no se puede mover

CONSECUENCIAS:
- Se actualiza la posición en la cola de la nueva máquina
- Se recalculan fechas de fin programado
- Se genera log reversible (puedes deshacer)
- El calendario se recarga automáticamente',
                'keywords' => 'mover, planilla, máquina, arrastrar, drag, drop, cambiar, reasignar',
                'orden' => 2,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Optimizar planillas con retraso',
                'contenido' => 'El botón OPTIMIZAR analiza planillas retrasadas y sugiere moverlas a máquinas con menor carga.

ACCESO: Producción → Máquinas → Botón "Optimizar Planillas" (púrpura)

PASOS:
1. Haz clic en "Optimizar Planillas"
2. El modal muestra:
   - Estadísticas: planillas con retraso, elementos a mover
   - Tabla con sugerencias para cada elemento:
     * Código elemento y planilla
     * Diámetro y peso
     * Máquina actual
     * Fecha entrega (verde) vs Fin programado (rojo si hay retraso)
     * Select con máquinas compatibles (preseleccionada la sugerida)
3. OPCIONAL: Cambia la máquina destino en el select si prefieres otra
4. Haz clic en "Aplicar Optimización"
5. Confirmación de éxito con resultados

CONSECUENCIAS:
- Los elementos se mueven a las máquinas seleccionadas
- Se recalculan posiciones y fechas
- Se genera log reversible
- Botón "Deshacer" se habilita

⚠️ Puedes cambiar la máquina sugerida antes de aplicar si conoces mejor la situación real de la fábrica.',
                'keywords' => 'optimizar, retraso, planilla, mover, sugerencia, automático',
                'orden' => 3,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Balancear carga entre máquinas',
                'contenido' => 'El botón BALANCEAR redistribuye el trabajo para equilibrar la carga entre máquinas.

ACCESO: Producción → Máquinas → Botón "Balancear Carga" (verde)

PASOS:
1. Haz clic en "Balancear Carga"
2. El modal muestra:
   - Estadísticas: elementos, peso total (kg), longitud total (m)
   - Gráfico circular con carga original por máquina
   - Tabla de elementos con checkbox para seleccionar cuáles incluir:
     * Vista previa de figura del elemento
     * Código, planilla, diámetro, peso
     * Máquina actual → Máquina sugerida
3. Marca/desmarca checkboxes según necesites
4. El gráfico inferior se actualiza en tiempo real
5. Haz clic en "Aplicar Balanceo"

CONSECUENCIAS:
- Solo se mueven elementos con checkbox marcado
- Se redistribuye carga equilibradamente
- Se genera log reversible
- Calendario se recarga

💡 TIP: Usa los checkboxes para excluir elementos que no quieres mover (ej: ya están a punto de fabricarse).',
                'keywords' => 'balancear, carga, equilibrar, distribuir, peso, máquinas',
                'orden' => 4,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Priorizar obra urgente',
                'contenido' => 'El botón PRIORIZAR permite establecer qué obras se fabrican primero.

ACCESO: Producción → Máquinas → Botón "Priorizar Obra" (naranja)

PASOS:
1. Haz clic en "Priorizar Obra"
2. El modal muestra 5 niveles de prioridad (1 = máxima)
3. Cada nivel tiene un selector con todas las obras disponibles:
   - Formato: "FECHA_ENTREGA (N planillas) [Códigos]"
   - Agrupadas por cliente
4. Selecciona la obra más urgente en Prioridad 1
5. Opcionalmente, asigna obras a prioridades 2-5
6. Botón "Limpiar" para resetear un nivel
7. Haz clic en "Aplicar Priorización"

CONSECUENCIAS:
- Las planillas de Prioridad 1 se mueven al INICIO de cada máquina
- Las de Prioridad 2 se ponen después, y así sucesivamente
- Se recalculan todas las posiciones
- Las máquinas empiezan a fabricar las obras priorizadas primero
- Se genera log reversible

⚠️ IMPORTANTE: Esto reorganiza TODAS las colas de TODAS las máquinas.',
                'keywords' => 'priorizar, obra, urgente, primero, ordenar, cola',
                'orden' => 5,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Ver y resolver retrasos',
                'contenido' => 'El botón RETRASOS muestra todas las planillas que van con retraso.

ACCESO: Producción → Máquinas → Botón "Retrasos" (rojo)

QUÉ MUESTRA:
- Contador total de planillas con retraso
- Tarjetas por cada planilla retrasada con:
  * Código de planilla (clickeable)
  * Badge rojo con días de retraso
  * Cliente y obra
  * Fecha entrega vs Fin programado
  * Máquinas donde está asignada (con posición en cola)
  * Número de elementos

ACCIONES DISPONIBLES:
- Click en código → abre la planilla
- Botón "Simular incluir sábado":
  * Permite marcar sábados específicos como hábiles
  * Recalcula retrasos en tiempo real
  * NO modifica la base de datos (solo simulación)

CÓMO RESOLVER RETRASOS:
1. Identifica las planillas más críticas
2. Usa "Optimizar" para moverlas automáticamente
3. O arrastra manualmente a máquinas con menos carga
4. O usa "Priorizar" para ponerlas primero en cola',
                'keywords' => 'retraso, retrasos, fecha, entrega, programado, resolver',
                'orden' => 6,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Redistribuir cola de una máquina',
                'contenido' => 'El botón 🔀 en cada máquina permite redistribuir su trabajo a otras máquinas.

ACCESO: Producción → Máquinas → Botón 🔀 en la fila de la máquina

CUÁNDO USAR:
- La máquina tiene avería
- La máquina está sobrecargada
- Necesitas vaciar esa máquina temporalmente

PASOS:
1. Haz clic en 🔀 de la máquina a vaciar
2. El modal muestra opciones:
   - Checkbox "Solo planillas revisadas"
   - Campo "Límite de elementos" (opcional)
3. Elige tipo de redistribución:
   - "Primeros 50": solo los primeros 50 elementos
   - "Con límite": hasta el número especificado
   - "Todos": redistribuye todo lo pendiente
4. Modal de confirmación muestra:
   - Cantidad de elementos a mover
   - Máquinas destino y cantidad por cada una
5. Confirma con "Sí, redistribuir"
6. Modal de resultados con detalle completo

CONSECUENCIAS:
- Elementos se reparten según compatibilidad de diámetros
- Sistema elige máquinas con menor carga
- Se recalculan posiciones en todas las máquinas afectadas
- Se genera log reversible

⚠️ Los elementos incompatibles con otras máquinas permanecen en la original.',
                'keywords' => 'redistribuir, cola, vaciar, máquina, avería, repartir',
                'orden' => 7,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Deshacer cambios en planificación',
                'contenido' => 'El botón DESHACER revierte la última operación realizada.

ACCESO: Producción → Máquinas → Botón "Deshacer" (se habilita tras una acción)

OPERACIONES REVERSIBLES:
- Optimizar planillas
- Balancear carga
- Priorizar obra
- Mover planilla (drag & drop)
- Redistribuir cola

PASOS:
1. Realiza una operación (Optimizar, Balancear, etc.)
2. El botón "Deshacer" cambia de gris a coloreado
3. Haz clic en "Deshacer"
4. Modal muestra qué se va a revertir:
   - Tipo de operación
   - Descripción de cambios
   - Fecha y hora original
5. Confirma con "Sí, deshacer"
6. Se restaura el estado anterior
7. Calendario se recarga

HISTORIAL COMPLETO:
- Botón "Historial" muestra TODAS las operaciones
- Puedes revertir operaciones antiguas desde ahí
- Cada log muestra: acción, usuario, descripción, fecha
- Botón "Ver detalles" expande el JSON de cambios

⚠️ Al deshacer, la acción anterior (si existe) se vuelve disponible para deshacer.',
                'keywords' => 'deshacer, revertir, historial, log, cambios, restaurar',
                'orden' => 8,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Cambiar estado de máquina',
                'contenido' => 'Cada máquina puede tener 4 estados que afectan la planificación.

ACCESO: Producción → Máquinas → Botón 🔴 en la fila de la máquina

ESTADOS DISPONIBLES:
🟢 ACTIVA: Funcionando normal, recibe y fabrica elementos
🔴 AVERIADA: No funciona, no recibe nuevos elementos automáticamente
🛠️ MANTENIMIENTO: En revisión, temporalmente fuera de servicio
⏸️ PAUSA: Parada temporal, no fabrica pero mantiene su cola

PASOS:
1. Haz clic en 🔴 de la máquina
2. Modal muestra 4 botones grandes con los estados
3. Haz clic en el nuevo estado
4. El cambio se aplica inmediatamente

CONSECUENCIAS:
- AVERIADA: El sistema NO asigna automáticamente nuevos elementos a esta máquina
- MANTENIMIENTO: Similar a averiada, indica revisión planificada
- PAUSA: Mantiene su cola pero no avanza en fabricación
- Se genera log del cambio de estado
- Puede afectar cálculos de fin programado

💡 TIP: Si una máquina se avería, cámbiala a "Averiada" y luego usa 🔀 para redistribuir su cola.',
                'keywords' => 'estado, máquina, avería, mantenimiento, pausa, activa',
                'orden' => 9,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Filtrar planillas en el calendario',
                'contenido' => 'El panel de filtros permite encontrar planillas específicas en el calendario.

ACCESO: Producción → Máquinas → Panel "Filtros de planillas" (colapsable)

FILTROS DISPONIBLES:
- Cliente (texto)
- Código de Cliente (texto)
- Obra (texto)
- Código de Obra (texto)
- Código de Planilla (texto)
- Fecha de Entrega (selector de fecha)
- Estado: Todos, Pendiente, Fabricando, Completada

PASOS:
1. Haz clic en "Filtros de planillas" para expandir
2. Rellena los campos que necesites
3. Los filtros se aplican automáticamente (con pequeño delay)
4. Las planillas que coinciden se RESALTAN en el calendario
5. Badge amarillo "Filtros Activos" indica filtros aplicados
6. Botón "Restablecer filtros" limpia todos los campos

FILTRO POR TURNOS:
- Sección "⏰ Turnos Activos" al final del panel
- Puedes activar/desactivar turnos específicos
- Los turnos inactivos no generan eventos en el calendario

⚠️ Los filtros usan AND lógico: la planilla debe coincidir con TODOS los criterios.',
                'keywords' => 'filtrar, buscar, planilla, calendario, cliente, obra',
                'orden' => 10,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Ver elementos de una planilla',
                'contenido' => 'Al hacer clic en una planilla se abre el panel lateral con sus elementos.

ACCESO: Producción → Máquinas → Clic en cualquier planilla del calendario

QUÉ MUESTRA EL PANEL:
- Header con código de planilla
- Botón "Marcar como revisada" (marca toda la planilla)
- Select para filtrar por máquina
- Lista de elementos con:
  * Código del elemento
  * Diámetro (Ø) y peso
  * Estado (pendiente, fabricando, completado)
  * Icono para ver figura (si existe)

ACCIONES:
1. Click en elemento → marcar como revisado individualmente
2. Click en icono de figura → modal con dibujo del elemento
3. Botón X → cerrar panel

CONSECUENCIAS DE MARCAR COMO REVISADA:
- La planilla/elemento cambia a estado "revisada = true"
- Afecta contadores en el Resumen
- Algunas operaciones (redistribuir) pueden filtrar solo revisadas
- El elemento cambia de color en el panel

💡 TIP: Revisa las planillas antes de que entren en producción para validar que todo esté correcto.',
                'keywords' => 'elementos, planilla, panel, revisar, ver, detalle, figura',
                'orden' => 11,
            ],
            [
                'categoria' => 'planificacion',
                'titulo' => 'Ver resumen de planificación',
                'contenido' => 'El botón RESUMEN muestra estadísticas globales de la planificación.

ACCESO: Producción → Máquinas → Botón "Resumen" (azul)

QUÉ MUESTRA:
4 tarjetas de estadísticas:
- 🟢 Planillas revisadas
- ⚪ Planillas no revisadas
- 📊 Total planillas
- 🔴 Planillas con retraso

SECCIÓN "CLIENTES CON RETRASO":
- Si no hay retrasos: mensaje verde "Sin retrasos"
- Si hay retrasos: estructura expandible:
  * Cliente → Obra → Fecha Entrega → Planillas
  * Para cada planilla: código, máquinas asignadas, días de retraso

USO:
- Vista rápida del estado general de la fábrica
- Identificar clientes/obras con problemas
- Solo lectura, no modifica nada
- Los datos se actualizan cada vez que abres el modal

💡 TIP: Revisa el resumen al inicio del día para saber qué priorizar.',
                'keywords' => 'resumen, estadísticas, estado, planillas, clientes, retraso',
                'orden' => 12,
            ],

            // ═══════════════════════════════════════════════════════════════
            // PRODUCCIÓN
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'produccion',
                'titulo' => 'Cómo fabricar elementos',
                'contenido' => 'Para fabricar (operarios):
1. Ve a Producción → Máquinas
2. Selecciona tu máquina (verás las planillas asignadas)
3. Haz clic en la planilla que vas a fabricar
4. Verás todos los elementos/etiquetas de esa planilla
5. Haz clic en el elemento que vas a fabricar

Durante la fabricación:
- Puedes ver los parámetros del elemento (Ø, longitud, kg, etc.)
- Marca las etiquetas como "en proceso" o "completadas"
- Añade observaciones si es necesario',
                'keywords' => 'fabricar, producir, elemento, etiqueta, máquina, operario',
                'orden' => 1,
            ],
            [
                'categoria' => 'produccion',
                'titulo' => 'Cómo crear un paquete',
                'contenido' => 'Para crear un paquete después de fabricar:
1. Cuando termines varias etiquetas, haz clic en "Crear Paquete"
2. Selecciona las etiquetas que van en el paquete (pueden ser múltiples)
3. El sistema genera automáticamente:
   - Un código único para el paquete
   - Un código QR imprimible
4. Haz clic en "Imprimir Etiqueta" y pégala en el paquete físico
5. Asigna una ubicación en el mapa de la nave

El código QR sirve para rastrear el paquete en salidas y stock.',
                'keywords' => 'paquete, crear, etiqueta, qr, código, imprimir',
                'orden' => 2,
            ],

            // ═══════════════════════════════════════════════════════════════
            // SALIDAS
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'salidas',
                'titulo' => 'Crear salida planificada (porte)',
                'contenido' => 'Para crear una salida planificada:
1. Ve a Planificación → Portes
2. Haz clic en el calendario en la fecha deseada
3. Rellena el formulario:
   - Obra de destino
   - Fecha y hora de salida
   - Transportista (opcional)
4. Haz clic en "Crear Porte"

El porte aparecerá en el calendario y estará listo para preparar.',
                'keywords' => 'salida, porte, planificar, calendario, transportista',
                'orden' => 1,
            ],
            [
                'categoria' => 'salidas',
                'titulo' => 'Preparar salida directa',
                'contenido' => 'Para preparar una salida directa:
1. Ve a Logística → Salidas
2. Haz clic en "Nueva Salida"
3. Selecciona la obra y los paquetes a enviar
4. Durante la carga del camión:
   - Escanea los códigos QR de cada paquete
   - O selecciónalos manualmente de la lista
5. Cuando todo esté cargado, haz clic en "Confirmar Salida"
6. El sistema genera automáticamente el albarán
7. Haz clic en "Imprimir Albarán" para el transportista

Tip: Usa el móvil para escanear QR durante la carga - es más rápido

IMPORTANTE: Los paquetes salen del stock automáticamente al confirmar.',
                'keywords' => 'salida, directa, paquete, escanear, qr, albarán, camión',
                'orden' => 2,
            ],

            // ═══════════════════════════════════════════════════════════════
            // STOCK
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'stock',
                'titulo' => 'Consultar stock de productos',
                'contenido' => 'Para consultar stock de productos base:
1. Ve a Logística → Productos o Almacén → Productos
2. Verás una tabla con todos los productos y su stock actual
3. Usa los filtros para buscar:
   - Por diámetro (Ø8, Ø10, Ø12, etc.)
   - Por tipo (corrugado, liso, malla, etc.)
   - Por ubicación o nave
4. La columna "Stock" muestra las unidades/kg disponibles',
                'keywords' => 'stock, productos, consultar, disponible, inventario, diámetro',
                'orden' => 1,
            ],
            [
                'categoria' => 'stock',
                'titulo' => 'Ver ubicaciones y mapa de nave',
                'contenido' => 'Para ver las ubicaciones en la nave:
1. Ve a Logística → Ubicaciones o Almacén → Ubicaciones
2. Verás un mapa de la nave con todas las ubicaciones
3. Haz clic en una ubicación para ver qué material contiene
4. Puedes filtrar por nave si tienes varias',
                'keywords' => 'ubicaciones, mapa, nave, sector, almacén',
                'orden' => 2,
            ],
            [
                'categoria' => 'stock',
                'titulo' => 'Ver paquetes fabricados',
                'contenido' => 'Para ver el stock de paquetes fabricados:
1. Ve a Producción → Paquetes o Stock → Paquetes
2. Verás todos los paquetes fabricados y su ubicación
3. Puedes filtrar por:
   - Planilla
   - Obra
   - Estado (en stock, en tránsito, entregado)

Tip: Usa el buscador rápido en la esquina superior para encontrar un producto específico.',
                'keywords' => 'paquetes, fabricados, stock, buscar, filtrar',
                'orden' => 3,
            ],

            // ═══════════════════════════════════════════════════════════════
            // USUARIOS
            // ═══════════════════════════════════════════════════════════════
            [
                'categoria' => 'usuarios',
                'titulo' => 'Crear nuevo usuario',
                'contenido' => 'Para crear un nuevo usuario (solo administradores):
1. Ve a Recursos Humanos desde el menú principal
2. Haz clic en "Registrar Usuario"
3. Completa el formulario de registro:
   - Nombre completo
   - Email (será su usuario de acceso)
   - Contraseña y confirmación
   - Rol: Operario, Oficina, o Admin
   - Departamento
   - Categoría laboral
   - Turno (si es operario)
   - Máquina asignada (si es operario de producción)
4. Haz clic en "Crear Usuario"',
                'keywords' => 'usuario, crear, registrar, nuevo, empleado, alta',
                'orden' => 1,
            ],
            [
                'categoria' => 'usuarios',
                'titulo' => 'Ver y editar usuarios',
                'contenido' => 'Para ver y editar usuarios existentes:
1. Ve a Recursos Humanos → Usuarios
2. Verás una tabla con todos los usuarios
3. Puedes:
   - Editar inline: Haz doble clic en una celda
   - Ver detalles: Haz clic en el botón "Ver"
   - Filtrar/buscar: Usa los filtros superiores

Solo usuarios con rol Admin pueden crear o editar usuarios.',
                'keywords' => 'usuario, editar, ver, modificar, tabla, buscar',
                'orden' => 2,
            ],
        ];

        foreach ($documentos as $doc) {
            DocumentoAyuda::updateOrCreate(
                ['titulo' => $doc['titulo']],
                array_merge($doc, ['activo' => true])
            );
        }

        $this->command->info('Se crearon/actualizaron ' . count($documentos) . ' documentos de ayuda.');
        $this->command->warn('Recuerda regenerar los embeddings desde el panel admin o ejecutando el comando.');
    }
}
