<x-app-layout>
    <x-slot name="title">Centro de Ayuda</x-slot>

    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">📘 Centro de Ayuda</h2>
    </x-slot>
    <div class="flex flex-col md:flex-row">

        <div class="md:hidden sticky top-0 z-40 bg-red-50 border-b border-red-200 shadow-sm" x-data="{ open: false }">
            <div class="px-4 py-3 flex items-center justify-between">
                <h3 class="text-base font-bold text-red-800 uppercase tracking-wide">Navegación</h3>
                <button @click="open = !open" class="text-red-800 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Menú desplegable solo visible en móvil -->
            <nav :class="{ 'block': open, 'hidden': !open }" class="px-4 pb-4 space-y-2">
                <a href="#inicio-sesion" @click="open = false"
                    class="block w-full px-3 py-2 rounded-lg hover:bg-red-100 text-red-800 font-medium transition">🔐
                    Inicio de sesión</a>
                <a href="#usuarios" @click="open = false"
                    class="block w-full px-3 py-2 rounded-lg hover:bg-red-100 text-red-800 font-medium transition">👤
                    Usuarios</a>
                <a href="#materiales" @click="open = false"
                    class="block w-full px-3 py-2 rounded-lg hover:bg-red-100 text-red-800 font-medium transition">📦
                    Materiales (Entradas)</a>
                <a href="#movimientos" @click="open = false"
                    class="block w-full px-3 py-2 rounded-lg hover:bg-red-100 text-red-800 font-medium transition">🔄
                    Movimientos de Material</a>
                <a href="#planillas" @click="open = false"
                    class="block w-full px-3 py-2 rounded-lg hover:bg-red-100 text-red-800 font-medium transition">📋
                    Planillas</a>
                <a href="#produccion" @click="open = false"
                    class="block w-full px-3 py-2 rounded-lg hover:bg-red-100 text-red-800 font-medium transition">⚙️
                    Producción</a>
                <a href="#salidas" @click="open = false"
                    class="block w-full px-3 py-2 rounded-lg hover:bg-red-100 text-red-800 font-medium transition">🚚
                    Salidas</a>
            </nav>

        </div>




        <!-- Contenido principal -->
        <main class="flex-1 w-full max-w-5xl mx-auto px-0 md:px-6 py-8 space-y-10 mt-2 md:mt-0">

            <section id="inicio-sesion">
                <h3 class="text-lg font-bold mb-4 text-center">🔐 Inicio de sesión</h3>
                <!-- Solo para oficina / administrativos -->
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                        <li>El <strong>correo</strong> será el que ha proporcionado a administración.</li>
                        <li>El cambio de <strong>contraseña</strong>contraseña no está permitido. Si es necesario por
                            razones
                            operativas o de acceso, deberá solicitarse a administración o utilizar el enlace
                            <strong>“¿Olvidaste
                                tu contraseña?”</strong> para iniciar el proceso de recuperación.
                        </li>
                        <li>El operario solo puede entrar en las partes de la aplicación que necesita para trabajar.
                        </li>
                        <li>Si marcas <strong>“Recuérdame”</strong>, no tendrás que volver a iniciar sesión cada vez,
                            aunque cierres el navegador.</li>
                        <li>Si marcas <strong>“Recordar correo”</strong>, el sistema guardará tu email y solo tendrás
                            que escribir la contraseña la próxima vez.</li>
                        <li>Los permisos dependen del <code>rol</code> asignado: <strong>operario, oficina o
                                admin</strong>.</li>
                    </ul>
                </div>
            </section>

            <section id="usuarios" class="space-y-4">
                <h3 class="text-lg font-bold mb-4 text-center">👤 Usuarios</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="bg-white p-4 border rounded shadow">
                        <h4 class="font-semibold text-red-700 mb-2">👨‍💼 Oficina</h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            <li>Registrar nuevos usuarios mediante el botón <strong>“Registrar Usuario”</strong>.</li>
                            <li>Aplicar filtros por nombre, email, empresa, categoría, turno, etc.</li>
                            <li>Ordenar columnas como nombre, DNI, email, rol, estado.</li>
                            <li>Editar usuarios directamente en la tabla mediante doble clic, puede guardar con enter o
                                botón verde <strong>guardar</strong> en la columna acciones. Con otro doble clic puede
                                cerrar la edición.</li>
                            <li>Ver si un usuario está <strong>en línea</strong> actualmente.</li>
                            <li>Acceder a la ficha completa del usuario con el botón <strong>"ver"</strong> en la
                                columna acciones.</li>
                            <li>Asignar o modificar <strong>turnos</strong> manualmente en el enlace
                                <strong>"ver"</strong> .
                            </li>
                            <li>Generar turnos automáticamente según el tipo (diurno, mañana y nocturno). Si el operario
                                tiene turno de mañana al clicar en el botón turnos asignara turnos de mañana hasta final
                                de año, obviando los días festivos y vacaciones ya asignadas. Si el operario tiene turno
                                de noche asignara la noche. Cuando el operario tiene turno diurno saltará una ventana
                                pidiendo si quiere que empiece asignando la mañana o la tarde.</li>
                            <li>Los cambios de <strong>contraseña y eliminación de usuario</strong> con
                                el botón <strong>"Editar"</strong> en
                                acciones.</li>

                            <li>Ver vacaciones globales de todos los usuarios.</li>
                        </ul>
                    </div>

                    <div class="bg-red-50 p-4 border border-red-200 rounded shadow">
                        <h4 class="font-semibold text-red-700 mb-2">👷 Operarios</h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            <li>El operario no tiene acceso a la gestión de usuarios.</li>
                            <li>Solo ve su propia ficha con datos personales (nombre, categoría, máquina asignada).</li>
                            <li>Pueden consultar sus vacaciones disponibles y su historial de fichajes.</li>
                            <li>Realiza <strong>fichaje de entrada y salida</strong> con validación por geolocalización
                                y obra seleccionada.</li>
                            <li>Visualiza un calendario con sus turnos y ausencias.</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-4 text-sm text-gray-600">
                    <p>El acceso a esta vista y las acciones disponibles dependen del <strong>rol del usuario
                            autenticado</strong> (<code>oficina</code> o <code>operario</code>).</p>
                </div>
            </section>


            <section id="materiales">
                <h3 class="text-lg font-bold mb-4 text-center">📦 Materiales (Entradas)</h3>
                <p>Dar de alta productos, imprimir códigos QR y gestionar entradas al almacén.</p>
            </section>

            <section id="movimientos">
                <h3 class="text-lg font-bold mb-4 text-center">🔄 Movimientos de Material</h3>
                <p>Cómo mover productos entre ubicaciones y registrar cambios en tiempo real.</p>
            </section>

            <section id="planillas">
                <h3 class="text-lg font-bold mb-4 text-center">📋 Planillas</h3>
                <p>Creación de planillas de fabricación, vinculación con obras y fechas de entrega.</p>
            </section>

            <section id="produccion">
                <h3 class="text-lg font-bold mb-4 text-center">⚙️ Producción</h3>
                <p>Control de máquinas, asignación de tareas y seguimiento del proceso productivo.</p>
            </section>

            <section id="salidas">
                <h3 class="text-lg font-bold mb-4 text-center">🚚 Salidas</h3>
                <p>Asignación de paquetes a camiones, albaranes y confirmación de portes.</p>
            </section>
        </main>
    </div>
</x-app-layout>
