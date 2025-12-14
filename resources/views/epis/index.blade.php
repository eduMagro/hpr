<x-app-layout>
    <x-slot name="title">EPIs</x-slot>

    <div class="py-6 px-4" x-data="episPage()" x-init="init()">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">EPIs por trabajador</h1>
                    <p class="text-sm text-gray-600 mt-1">Agenda de usuarios con EPIs en posesión (sin detallar en la lista).</p>
                </div>

                <div class="flex gap-2 items-end">
                    <div class="relative w-full sm:w-96">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Buscar trabajador</label>
                        <input type="text"
                            x-model="query"
                            @input="onQueryChange()"
                            @keydown.escape="closeSuggestions()"
                            @focus="openSuggestions()"
                            placeholder="Nombre, apellidos, DNI, móvil…"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />

                        <!-- Helper / sugerencias -->
                        <div x-cloak x-show="suggestionsOpen"
                            class="absolute z-50 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                            <div class="max-h-80 overflow-y-auto">
                                <template x-if="suggestions.length === 0">
                                    <div class="p-4 text-sm text-gray-600">Sin coincidencias.</div>
                                </template>
                                <template x-for="u in suggestions" :key="u.id">
                                    <button type="button" class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-center gap-3"
                                        @click="selectUser(u)">
                                        <div class="w-9 h-9 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                            <template x-if="u.ruta_imagen">
                                                <img :src="u.ruta_imagen" :alt="`Foto de ${u.nombre_completo}`" class="w-full h-full object-cover" />
                                            </template>
                                            <template x-if="!u.ruta_imagen">
                                                <span class="text-gray-600 font-semibold" x-text="u.nombre_completo?.slice(0,1)?.toUpperCase()"></span>
                                            </template>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900 truncate" x-text="u.nombre_completo"></p>
                                            <p class="text-xs text-gray-600 truncate">
                                                <span x-text="u.dni || 'DNI N/D'"></span>
                                                <span class="mx-1">·</span>
                                                <span x-text="u.movil_personal || 'Móvil N/D'"></span>
                                            </p>
                                        </div>
                                        <span class="text-xs font-medium text-blue-700 bg-blue-50 rounded-full px-2 py-0.5"
                                            x-text="`${u.epis_en_posesion} EPIs`"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col">
                        <span class="block text-xs font-medium text-transparent mb-1 select-none">.</span>
                        <button type="button"
                            class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap"
                            @click="openCatalog()">
                            Gestionar EPIs
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Usuarios con EPIs</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1" x-text="stats.usuariosConEpis"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Más EPIs en posesión</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1 truncate" x-text="stats.topNombre || '—'"></p>
                    <p class="text-sm text-gray-600" x-text="stats.topCantidad ? `${stats.topCantidad} EPIs` : ''"></p>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Catálogo</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-1" x-text="catalogoCount"></p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900">Agenda</h2>
                </div>

                <template x-if="loadingUsers">
                    <div class="p-6 text-gray-700">Cargando…</div>
                </template>

                <template x-if="!loadingUsers && agendaUsers.length === 0">
                    <div class="p-6 text-gray-700">No hay usuarios con EPIs en posesión.</div>
                </template>

                <template x-if="!loadingUsers && agendaUsers.length > 0">
                    <div class="divide-y divide-gray-100">
                        <template x-for="u in agendaUsers" :key="u.id">
                            <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        <template x-if="u.ruta_imagen">
                                            <img :src="u.ruta_imagen" :alt="`Foto de ${u.nombre_completo}`" class="w-full h-full object-cover" />
                                        </template>
                                        <template x-if="!u.ruta_imagen">
                                            <span class="text-gray-600 font-semibold" x-text="u.nombre_completo?.slice(0,1)?.toUpperCase()"></span>
                                        </template>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <p class="font-semibold text-gray-900 truncate" x-text="u.nombre_completo"></p>
                                            <span class="inline-flex items-center rounded-full bg-blue-50 text-blue-700 px-2 py-0.5 text-xs font-medium"
                                                x-text="`${u.epis_en_posesion} EPIs`"></span>
                                        </div>
                                        <div class="text-sm text-gray-600 flex flex-col sm:flex-row sm:gap-4 mt-1">
                                            <span x-text="`DNI: ${u.dni || 'N/D'}`"></span>
                                            <span class="truncate" x-text="`Email: ${u.email || 'N/D'}`"></span>
                                            <span x-text="`Móvil: ${u.movil_personal || 'N/D'}`"></span>
                                        </div>
                                    </div>
                                </div>

                                <button type="button"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800"
                                    @click="openUser(u)">
                                    EPIs
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <!-- Modal -->
        <div x-cloak x-show="modalOpen" x-transition.opacity
            class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>

            <div class="relative w-full sm:max-w-5xl bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden max-h-[92vh] sm:max-h-[85vh] flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-wide text-gray-500">EPIs</p>
                        <p class="font-semibold text-gray-900 truncate" x-text="modalTitle"></p>
                    </div>

                    <button type="button" class="p-2 rounded-lg hover:bg-gray-100" @click="closeModal()" aria-label="Cerrar">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 pt-4">
                    <div class="flex gap-2 border-b border-gray-100">
                        <button type="button"
                            class="px-4 py-2 text-sm font-medium rounded-t-lg"
                            :class="modalTab === 'usuario' ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
                            @click="switchModalTab('usuario')"
                            :disabled="!selectedUser">
                            Usuario
                        </button>
                        <button type="button"
                            class="px-4 py-2 text-sm font-medium rounded-t-lg"
                            :class="modalTab === 'catalogo' ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
                            @click="switchModalTab('catalogo')">
                            Catálogo
                        </button>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto space-y-6">
                    <!-- Tab usuario -->
                    <template x-if="modalTab === 'usuario'">
                        <div>
                            <template x-if="!selectedUser">
                                <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-gray-700">
                                    Selecciona un usuario para ver/gestionar sus EPIs.
                                </div>
                            </template>

                            <template x-if="selectedUser">
                                <div class="space-y-6">
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                        <div class="lg:col-span-2 bg-gray-50 border border-gray-200 rounded-xl p-4">
                                            <p class="text-xs uppercase tracking-wide text-gray-500">Asignar EPI</p>
                                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-4 gap-3">
                                                <div class="sm:col-span-2">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">EPI</label>
                                                    <select x-model.number="assignForm.epi_id"
                                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                                        <option value="" disabled>Selecciona un EPI…</option>
                                                        <template x-for="e in epis.filter(x => x.activo)" :key="e.id">
                                                            <option :value="e.id" x-text="e.codigo ? `${e.nombre} (${e.codigo})` : e.nombre"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Cantidad</label>
                                                    <input type="number" min="1" x-model.number="assignForm.cantidad"
                                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Notas</label>
                                                    <input type="text" x-model="assignForm.notas" placeholder="Opcional"
                                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                </div>
                                                <div class="sm:col-span-4 flex justify-end">
                                                    <button type="button"
                                                        class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                                                        :disabled="!assignForm.epi_id || assignForm.cantidad < 1 || saving"
                                                        @click="assignEpi()">
                                                        Asignar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                                            <p class="text-xs uppercase tracking-wide text-gray-500">Resumen</p>
                                            <p class="text-lg font-semibold text-gray-900 mt-1" x-text="selectedUser.nombre_completo"></p>
                                            <p class="text-sm text-gray-600 mt-1" x-text="`DNI: ${selectedUser.dni || 'N/D'}`"></p>
                                            <p class="text-sm text-gray-600" x-text="`Email: ${selectedUser.email || 'N/D'}`"></p>
                                            <p class="text-sm text-gray-600" x-text="`Móvil: ${selectedUser.movil_personal || 'N/D'}`"></p>
                                            <p class="text-sm text-gray-600 mt-3">
                                                En posesión: <span class="font-semibold" x-text="userTotalEnPosesion"></span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                                        <div class="px-6 py-4 border-b border-gray-100">
                                            <h3 class="font-semibold text-gray-900">EPIs en posesión</h3>
                                        </div>

                                        <template x-if="loadingAsignaciones">
                                            <div class="p-6 text-gray-700">Cargando…</div>
                                        </template>

                                        <template x-if="!loadingAsignaciones && asignacionesEnPosesion.length === 0">
                                            <div class="p-6 text-gray-700">Este usuario no tiene EPIs en posesión.</div>
                                        </template>

                                        <template x-if="!loadingAsignaciones && asignacionesEnPosesion.length > 0">
                                            <div class="divide-y divide-gray-100">
                                                <template x-for="a in asignacionesEnPosesion" :key="a.id">
                                                    <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                                        <div class="flex items-center gap-4 min-w-0">
                                                            <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                                <template x-if="a.epi?.imagen_url">
                                                                    <img :src="a.epi.imagen_url" :alt="`Imagen de ${a.epi.nombre}`" class="w-full h-full object-cover" />
                                                                </template>
                                                                <template x-if="!a.epi?.imagen_url">
                                                                    <span class="text-gray-500 text-xs">Sin img</span>
                                                                </template>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <p class="font-semibold text-gray-900 truncate">
                                                                    <span x-text="a.epi?.nombre || 'EPI'"></span>
                                                                    <template x-if="a.epi?.codigo">
                                                                        <span class="text-sm text-gray-600" x-text="` (${a.epi.codigo})`"></span>
                                                                    </template>
                                                                </p>
                                                                <p class="text-sm text-gray-600">
                                                                    Cantidad: <span class="font-semibold" x-text="a.cantidad"></span>
                                                                </p>
                                                                <template x-if="a.notas">
                                                                    <p class="text-sm text-gray-600 truncate" x-text="`Notas: ${a.notas}`"></p>
                                                                </template>
                                                            </div>
                                                        </div>

                                                        <button type="button"
                                                            class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800 disabled:opacity-50"
                                                            :disabled="saving"
                                                            @click="markReturned(a)">
                                                            Marcar devuelto
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Tab catálogo -->
                    <template x-if="modalTab === 'catalogo'">
                        <div class="space-y-6">
                            <div class="bg-white border border-gray-200 rounded-xl p-4">
                                <p class="text-xs uppercase tracking-wide text-gray-500">Crear EPI</p>
                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-6 gap-3">
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Nombre</label>
                                        <input type="text" x-model="epiCreate.nombre"
                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Código</label>
                                        <input type="text" x-model="epiCreate.codigo" placeholder="Opcional"
                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Categoría</label>
                                        <input type="text" x-model="epiCreate.categoria" placeholder="Opcional"
                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Activo</label>
                                        <label class="inline-flex items-center gap-2 mt-2">
                                            <input type="checkbox" x-model="epiCreate.activo" class="rounded border-gray-300" />
                                            <span class="text-sm text-gray-700">Sí</span>
                                        </label>
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Descripción</label>
                                        <input type="text" x-model="epiCreate.descripcion" placeholder="Opcional"
                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Imagen</label>
                                        <input type="file" accept="image/*" @change="epiCreateFile = $event.target.files?.[0] || null" class="w-full text-sm" />
                                        <p class="text-xs text-gray-500 mt-1">Se guarda en `storage/app/public/epis`.</p>
                                    </div>
                                    <div class="sm:col-span-6 flex justify-end">
                                        <button type="button"
                                            class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                                            :disabled="!epiCreate.nombre || saving"
                                            @click="createEpi()">
                                            Crear
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100">
                                    <h3 class="font-semibold text-gray-900">EPIs en base de datos</h3>
                                </div>

                                <template x-if="loadingEpis">
                                    <div class="p-6 text-gray-700">Cargando…</div>
                                </template>

                                <template x-if="!loadingEpis && epis.length === 0">
                                    <div class="p-6 text-gray-700">No hay EPIs creados.</div>
                                </template>

                                <template x-if="!loadingEpis && epis.length > 0">
                                    <div class="divide-y divide-gray-100">
                                        <template x-for="e in epis" :key="e.id">
                                            <div class="p-4 sm:p-6" x-data="{ editOpen: false, form: { ...e } }">
                                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                                    <div class="flex items-center gap-4 min-w-0">
                                                        <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                            <template x-if="e.imagen_url">
                                                                <img :src="e.imagen_url" :alt="`Imagen de ${e.nombre}`" class="w-full h-full object-cover" />
                                                            </template>
                                                            <template x-if="!e.imagen_url">
                                                                <span class="text-gray-500 text-xs">Sin img</span>
                                                            </template>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="font-semibold text-gray-900 truncate">
                                                                <span x-text="e.nombre"></span>
                                                                <template x-if="!e.activo">
                                                                    <span class="ml-2 text-xs font-medium text-gray-600">(inactivo)</span>
                                                                </template>
                                                            </p>
                                                            <p class="text-sm text-gray-600">
                                                                <template x-if="e.codigo"><span x-text="`Código: ${e.codigo}`"></span></template>
                                                                <template x-if="e.codigo && e.categoria"><span> · </span></template>
                                                                <template x-if="e.categoria"><span x-text="`Categoría: ${e.categoria}`"></span></template>
                                                            </p>
                                                            <template x-if="e.descripcion">
                                                                <p class="text-sm text-gray-600 mt-1" x-text="e.descripcion"></p>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <div class="flex gap-2">
                                                        <button type="button"
                                                            class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-900 text-sm font-medium"
                                                            @click="editOpen = !editOpen">
                                                            Editar
                                                        </button>
                                                        <button type="button"
                                                            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 text-sm font-medium disabled:opacity-50"
                                                            :disabled="saving"
                                                            @click="$root.deleteEpi(e)">
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                </div>

                                                <div x-cloak x-show="editOpen" class="mt-4 bg-gray-50 border border-gray-200 rounded-xl p-4">
                                                    <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
                                                        <div class="sm:col-span-2">
                                                            <label class="block text-xs font-medium text-gray-700 mb-1">Nombre</label>
                                                            <input type="text" x-model="form.nombre"
                                                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-700 mb-1">Código</label>
                                                            <input type="text" x-model="form.codigo"
                                                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                        </div>
                                                        <div class="sm:col-span-2">
                                                            <label class="block text-xs font-medium text-gray-700 mb-1">Categoría</label>
                                                            <input type="text" x-model="form.categoria"
                                                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-700 mb-1">Activo</label>
                                                            <label class="inline-flex items-center gap-2 mt-2">
                                                                <input type="checkbox" x-model="form.activo" class="rounded border-gray-300" />
                                                                <span class="text-sm text-gray-700">Sí</span>
                                                            </label>
                                                        </div>
                                                        <div class="sm:col-span-4">
                                                            <label class="block text-xs font-medium text-gray-700 mb-1">Descripción</label>
                                                            <input type="text" x-model="form.descripcion"
                                                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" />
                                                        </div>
                                                        <div class="sm:col-span-2">
                                                            <label class="block text-xs font-medium text-gray-700 mb-1">Nueva imagen</label>
                                                            <input type="file" accept="image/*" @change="form._file = $event.target.files?.[0] || null" class="w-full text-sm" />
                                                        </div>
                                                        <div class="sm:col-span-6 flex justify-end">
                                                            <button type="button"
                                                                class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50"
                                                                :disabled="!form.nombre || saving"
                                                                @click="$root.updateEpi(e.id, form)">
                                                                Guardar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <script data-navigate-once>
        function episPage() {
            return {
                query: '',
                suggestionsOpen: false,
                suggestions: [],
                allUsers: [],
                agendaUsers: [],
                loadingUsers: true,

                modalOpen: false,
                modalTab: 'usuario', // usuario | catalogo
                selectedUser: null,
                asignacionesEnPosesion: [],
                userTotalEnPosesion: 0,
                loadingAsignaciones: false,

                epis: [],
                loadingEpis: false,
                catalogoCount: 0,

                saving: false,

                stats: {
                    usuariosConEpis: 0,
                    topNombre: null,
                    topCantidad: null,
                },

                assignForm: { epi_id: '', cantidad: 1, notas: '' },
                epiCreate: { nombre: '', codigo: '', categoria: '', descripcion: '', activo: true },
                epiCreateFile: null,

                get modalTitle() {
                    if (this.modalTab === 'catalogo') return 'Catálogo';
                    return this.selectedUser ? this.selectedUser.nombre_completo : 'Usuario';
                },

                async init() {
                    await this.refreshUsers();
                    await this.refreshEpis();
                },

                csrf() {
                    return document.querySelector('meta[name="csrf-token"]')?.content || '';
                },

                async api(url, options = {}) {
                    const headers = options.headers ? { ...options.headers } : {};
                    if (!headers['X-CSRF-TOKEN']) headers['X-CSRF-TOKEN'] = this.csrf();
                    if (!headers['Accept']) headers['Accept'] = 'application/json';
                    return fetch(url, { credentials: 'same-origin', ...options, headers });
                },

                normalize(str) {
                    return (str || '')
                        .toString()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLowerCase()
                        .replace(/\s+/g, ' ')
                        .trim();
                },

                digits(str) {
                    return (str || '').toString().replace(/\D+/g, '');
                },

                buildHaystack(u) {
                    const fullName = `${u.name || ''} ${u.primer_apellido || ''} ${u.segundo_apellido || ''}`.replace(/\s+/g, ' ').trim();
                    u._full_name = fullName;
                    const parts = [
                        fullName,
                        u.email || '',
                        u.dni || '',
                        u.movil_personal || '',
                        this.digits(u.movil_personal || ''),
                    ].join(' | ');
                    u._hay = this.normalize(parts);
                    u._hay_digits = this.digits(parts);
                },

                matches(u, q) {
                    const query = this.normalize(q);
                    if (!query) return true;
                    const tokens = query.split(' ').filter(Boolean);
                    const digitsQuery = this.digits(q);

                    const allTokensMatch = tokens.every(t => u._hay.includes(t));
                    if (allTokensMatch) return true;

                    if (digitsQuery.length >= 2) {
                        return u._hay_digits.includes(digitsQuery);
                    }

                    return false;
                },

                async refreshUsers() {
                    this.loadingUsers = true;
                    try {
                        const res = await this.api(@js(route('epis.api.users')));
                        const data = await res.json();
                        const users = (data.users || []);
                        users.forEach(u => this.buildHaystack(u));
                        this.allUsers = users;
                        this.agendaUsers = users.filter(u => u.tiene_epis);

                        this.stats.usuariosConEpis = (data.stats?.usuarios_con_epis) ?? this.agendaUsers.length;
                        this.stats.topNombre = data.stats?.top?.user?.nombre_completo || null;
                        this.stats.topCantidad = data.stats?.top?.cantidad || null;
                    } finally {
                        this.loadingUsers = false;
                        this.updateSuggestions();
                    }
                },

                async refreshEpis() {
                    this.loadingEpis = true;
                    try {
                        const res = await this.api(@js(route('epis.api.epis')));
                        const data = await res.json();
                        this.epis = data.epis || [];
                        this.catalogoCount = this.epis.length;
                    } finally {
                        this.loadingEpis = false;
                    }
                },

                openSuggestions() {
                    this.suggestionsOpen = true;
                    this.updateSuggestions();
                },

                closeSuggestions() {
                    this.suggestionsOpen = false;
                },

                onQueryChange() {
                    this.openSuggestions();
                    this.updateSuggestions();
                },

                updateSuggestions() {
                    const q = this.query;
                    const filtered = this.allUsers.filter(u => this.matches(u, q));
                    this.suggestions = filtered.slice(0, 10);
                },

                async selectUser(u) {
                    this.query = u._full_name || u.nombre_completo || '';
                    this.closeSuggestions();
                    await this.openUser(u);
                },

                async openUser(u) {
                    this.selectedUser = u;
                    this.modalOpen = true;
                    this.modalTab = 'usuario';
                    this.assignForm = { epi_id: '', cantidad: 1, notas: '' };
                    await this.refreshAsignaciones();
                },

                async openCatalog() {
                    this.modalOpen = true;
                    this.modalTab = 'catalogo';
                    await this.refreshEpis();
                },

                async switchModalTab(tab) {
                    this.modalTab = tab;
                    if (tab === 'catalogo') await this.refreshEpis();
                    if (tab === 'usuario' && this.selectedUser) await this.refreshAsignaciones();
                },

                closeModal() {
                    this.modalOpen = false;
                },

                async refreshAsignaciones() {
                    if (!this.selectedUser) return;
                    this.loadingAsignaciones = true;
                    try {
                        const url = @js(url('/epis/api/users/__ID__/asignaciones')).replace('__ID__', this.selectedUser.id);
                        const res = await this.api(url);
                        const data = await res.json();
                        this.asignacionesEnPosesion = data.en_posesion || [];
                        this.userTotalEnPosesion = data.total_en_posesion || 0;
                    } finally {
                        this.loadingAsignaciones = false;
                    }
                },

                async assignEpi() {
                    if (!this.selectedUser) return;
                    this.saving = true;
                    try {
                        const url = @js(url('/epis/usuarios/__ID__/asignaciones')).replace('__ID__', this.selectedUser.id);
                        const res = await this.api(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                epi_id: this.assignForm.epi_id,
                                cantidad: this.assignForm.cantidad,
                                notas: this.assignForm.notas || null,
                            }),
                        });
                        if (!res.ok) throw new Error('No se pudo asignar.');
                        this.assignForm = { epi_id: '', cantidad: 1, notas: '' };
                        await this.refreshAsignaciones();
                        await this.refreshUsers();
                    } finally {
                        this.saving = false;
                    }
                },

                async markReturned(a) {
                    if (!this.selectedUser) return;
                    this.saving = true;
                    try {
                        const url = @js(url('/epis/usuarios/__UID__/asignaciones/__AID__/devolver'))
                            .replace('__UID__', this.selectedUser.id)
                            .replace('__AID__', a.id);
                        const res = await this.api(url, { method: 'PATCH' });
                        if (!res.ok) throw new Error('No se pudo devolver.');
                        await this.refreshAsignaciones();
                        await this.refreshUsers();
                    } finally {
                        this.saving = false;
                    }
                },

                async createEpi() {
                    this.saving = true;
                    try {
                        const fd = new FormData();
                        fd.append('nombre', this.epiCreate.nombre);
                        if (this.epiCreate.codigo) fd.append('codigo', this.epiCreate.codigo);
                        if (this.epiCreate.categoria) fd.append('categoria', this.epiCreate.categoria);
                        if (this.epiCreate.descripcion) fd.append('descripcion', this.epiCreate.descripcion);
                        fd.append('activo', this.epiCreate.activo ? '1' : '0');
                        if (this.epiCreateFile) fd.append('imagen', this.epiCreateFile);

                        const res = await this.api(@js(route('epis.catalogo.store')), { method: 'POST', body: fd });
                        if (!res.ok) throw new Error('No se pudo crear.');
                        this.epiCreate = { nombre: '', codigo: '', categoria: '', descripcion: '', activo: true };
                        this.epiCreateFile = null;
                        await this.refreshEpis();
                    } finally {
                        this.saving = false;
                    }
                },

                async updateEpi(id, form) {
                    this.saving = true;
                    try {
                        const fd = new FormData();
                        fd.append('_method', 'PUT');
                        fd.append('nombre', form.nombre || '');
                        fd.append('codigo', form.codigo || '');
                        fd.append('categoria', form.categoria || '');
                        fd.append('descripcion', form.descripcion || '');
                        fd.append('activo', form.activo ? '1' : '0');
                        if (form._file) fd.append('imagen', form._file);

                        const url = @js(url('/epis/catalogo/__ID__')).replace('__ID__', id);
                        const res = await this.api(url, { method: 'POST', body: fd });
                        if (!res.ok) throw new Error('No se pudo actualizar.');
                        await this.refreshEpis();
                    } finally {
                        this.saving = false;
                    }
                },

                async deleteEpi(e) {
                    const ok = confirm('¿Seguro que quieres eliminar este EPI? Si tiene asignaciones, se desactivará.');
                    if (!ok) return;
                    this.saving = true;
                    try {
                        const url = @js(url('/epis/catalogo/__ID__')).replace('__ID__', e.id);
                        const fd = new FormData();
                        fd.append('_method', 'DELETE');
                        const res = await this.api(url, { method: 'POST', body: fd });
                        if (!res.ok) throw new Error('No se pudo eliminar.');
                        await this.refreshEpis();
                    } finally {
                        this.saving = false;
                    }
                },
            }
        }
    </script>
</x-app-layout>
