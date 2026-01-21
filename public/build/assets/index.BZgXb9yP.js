let O=null,X=null,G=null;async function oe(e,t){var n,s;const a=(s=(n=window.AppSalidas)==null?void 0:n.routes)==null?void 0:s.planificacion;if(!a)return{events:[],resources:[],totales:null};const o=`${e}|${t.startStr}|${t.endStr}`;return X===o&&O?O:(G&&X===o||(X=o,G=(async()=>{try{const i=new URLSearchParams({tipo:"all",viewType:e||"",start:t.startStr||"",end:t.endStr||""}),d=await fetch(`${a}?${i.toString()}`);if(!d.ok)throw new Error(`HTTP ${d.status}`);const r=await d.json();return O={events:r.events||[],resources:r.resources||[],totales:r.totales||null},O}catch(i){return console.error("fetch all data falló:",i),O=null,{events:[],resources:[],totales:null}}finally{G=null}})()),G)}function M(){O=null,X=null}function $e(e){var i,d;const t=((i=document.getElementById("solo-salidas"))==null?void 0:i.checked)||!1,a=((d=document.getElementById("solo-planillas"))==null?void 0:d.checked)||!1,o=e.filter(r=>{var u;return((u=r.extendedProps)==null?void 0:u.tipo)==="resumen-dia"}),n=e.filter(r=>{var u;return((u=r.extendedProps)==null?void 0:u.tipo)!=="resumen-dia"});let s=n;return t&&!a?s=n.filter(r=>{var u;return((u=r.extendedProps)==null?void 0:u.tipo)==="salida"}):a&&!t&&(s=n.filter(r=>{var m;const u=(m=r.extendedProps)==null?void 0:m.tipo;return u==="planilla"||u==="festivo"})),[...s,...o]}async function qe(e,t){const a=await oe(e,t);return $e(a.events)}async function Le(e,t){return(await oe(e,t)).resources}async function _e(e,t){const a=await oe(e,t);if(!a.totales)return;const{semana:o,mes:n}=a.totales,s=v=>v!=null?Number(v).toLocaleString():"0",i=document.querySelector("#resumen-semanal-peso"),d=document.querySelector("#resumen-semanal-longitud"),r=document.querySelector("#resumen-semanal-diametro");i&&(i.textContent=`📦 ${s(o==null?void 0:o.peso)} kg`),d&&(d.textContent=`📏 ${s(o==null?void 0:o.longitud)} m`),r&&(r.textContent=o!=null&&o.diametro?`⌀ ${Number(o.diametro).toFixed(2)} mm`:"");const u=document.querySelector("#resumen-mensual-peso"),m=document.querySelector("#resumen-mensual-longitud"),g=document.querySelector("#resumen-mensual-diametro");if(u&&(u.textContent=`📦 ${s(n==null?void 0:n.peso)} kg`),m&&(m.textContent=`📏 ${s(n==null?void 0:n.longitud)} m`),g&&(g.textContent=n!=null&&n.diametro?`⌀ ${Number(n.diametro).toFixed(2)} mm`:""),t.startStr){const v=new Date(t.startStr),c={year:"numeric",month:"long"};let p=v.toLocaleDateString("es-ES",c);p=p.charAt(0).toUpperCase()+p.slice(1);const l=document.querySelector("#resumen-mensual-fecha");l&&(l.textContent=`(${p})`)}}function se(e,t){const a=e.event.extendedProps||{};if(a.tipo!=="festivo"){if(a.tipo==="planilla"){const o=`
      ✅ Fabricados: ${Z(a.fabricadosKg)} kg<br>
      🔄 Fabricando: ${Z(a.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${Z(a.pendientesKg)} kg
    `;tippy(e.el,{content:o,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}a.tipo==="salida"&&a.comentario&&a.comentario.trim()&&tippy(e.el,{content:a.comentario,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}}function Z(e){return e!=null?Number(e).toLocaleString():0}let K=null;function D(){K&&(K.remove(),K=null,document.removeEventListener("click",D),document.removeEventListener("contextmenu",D,!0),document.removeEventListener("scroll",D,!0),window.removeEventListener("resize",D),window.removeEventListener("keydown",fe))}function fe(e){e.key==="Escape"&&D()}function Ae(e,t,a){D();const o=document.createElement("div");o.className="fc-contextmenu",Object.assign(o.style,{position:"fixed",top:t+"px",left:e+"px",zIndex:99999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",fontFamily:"system-ui, -apple-system, Segoe UI, Roboto, sans-serif"}),o.innerHTML=a,document.body.appendChild(o),K=o;const n=o.getBoundingClientRect(),s=Math.max(0,n.right-window.innerWidth+8),i=Math.max(0,n.bottom-window.innerHeight+8);return(s||i)&&(o.style.left=Math.max(8,e-s)+"px",o.style.top=Math.max(8,t-i)+"px"),setTimeout(()=>{document.addEventListener("click",D),document.addEventListener("contextmenu",D,!0),document.addEventListener("scroll",D,!0),window.addEventListener("resize",D),window.addEventListener("keydown",fe)},0),o}function Te(e,t,{headerHtml:a="",items:o=[]}={}){const n=`
    <div class="ctx-menu-container">
      ${a?`<div class="ctx-menu-header">${a}</div>`:""}
      ${o.map((i,d)=>`
        <button type="button"
          class="ctx-menu-item${i.danger?" ctx-menu-danger":""}"
          data-idx="${d}">
          ${i.icon?`<span class="ctx-menu-icon">${i.icon}</span>`:""}
          <span class="ctx-menu-label">${i.label}</span>
        </button>`).join("")}
    </div>
  `,s=Ae(e,t,n);return s.querySelectorAll(".ctx-menu-item").forEach(i=>{i.addEventListener("click",async d=>{var m;d.preventDefault(),d.stopPropagation();const r=Number(i.dataset.idx),u=(m=o[r])==null?void 0:m.onClick;D();try{await(u==null?void 0:u())}catch(g){console.error(g)}})}),s}function De(e){if(!e||typeof e!="string")return"";const t=e.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);if(t){const a=t[1],o=t[2].padStart(2,"0"),n=t[3].padStart(2,"0");return`${a}-${o}-${n}`}return e}(function(){if(document.getElementById("swal-anims"))return;const t=document.createElement("style");t.id="swal-anims",t.textContent=`
  /* Animación solo con scale; el centrado lo hacemos con left/top */
  @keyframes swalFadeInZoom {
    0%   { opacity: 0; transform: scale(.95); }
    100% { opacity: 1; transform: scale(1); }
  }
  @keyframes swalFadeOut {
    0%   { opacity: 1; transform: scale(1); }
    100% { opacity: 0; transform: scale(.98); }
  }
  .swal-fade-in-zoom { animation: swalFadeInZoom .18s ease-out both; }
  .swal-fade-out     { animation: swalFadeOut   .12s ease-in  both; }

  /* IMPORTANTE: escalar desde el centro para que no “camine” */
  .swal2-popup { 
    will-change: transform, opacity; 
    backface-visibility: hidden; 
    transform-origin: center center;
  }

  @keyframes swalRowIn { to { opacity: 1; transform: none; } }
  
  /* Estilos para fines de semana en input type="date" */
  input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
  }
  
  /* Estilo personalizado para inputs de fecha en fines de semana */
  .weekend-date {
    background-color: rgba(239, 68, 68, 0.1) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
    color: #dc2626 !important;
  }
  
  .weekend-date:focus {
    background-color: rgba(239, 68, 68, 0.15) !important;
    border-color: rgba(239, 68, 68, 0.5) !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
  }
  
  /* Estilos para celdas de fin de semana en el calendario */
  .fc-day-sat,
  .fc-day-sun {
    background-color: rgba(239, 68, 68, 0.05) !important;
  }
  
  /* Estilos para el encabezado de días de fin de semana */
  .fc-col-header-cell.fc-day-sat,
  .fc-col-header-cell.fc-day-sun {
    background-color: rgba(239, 68, 68, 0.1) !important;
    color: #dc2626 !important;
  }
  
  /* Para vista de mes - celdas de fin de semana */
  .fc-daygrid-day.fc-day-sat,
  .fc-daygrid-day.fc-day-sun {
    background-color: rgba(239, 68, 68, 0.05) !important;
  }
  
  /* Para vista de semana - columnas de fin de semana */
  .fc-timegrid-col.fc-day-sat,
  .fc-timegrid-col.fc-day-sun {
    background-color: rgba(239, 68, 68, 0.05) !important;
  }
  
  /* Números de día en fin de semana */
  .fc-daygrid-day.fc-day-sat .fc-daygrid-day-number,
  .fc-daygrid-day.fc-day-sun .fc-daygrid-day-number {
    color: #dc2626 !important;
    font-weight: 600 !important;
  }
  `,document.head.appendChild(t)})();function ge(e){const t=e.extendedProps||{};if(Array.isArray(t.planillas_ids)&&t.planillas_ids.length)return t.planillas_ids;const a=(e.id||"").match(/planilla-(\d+)/);return a?[Number(a[1])]:[]}window.eventosSeleccionados=window.eventosSeleccionados||[];function re(e,t){var n;const a=e.id,o=window.eventosSeleccionados.findIndex(s=>s.id===a);o>-1?(window.eventosSeleccionados.splice(o,1),t.classList.remove("evento-seleccionado")):(((n=e.extendedProps)==null?void 0:n.tipo)||"planilla")==="planilla"&&(window.eventosSeleccionados.push({id:a,event:e,el:t}),t.classList.add("evento-seleccionado")),ye()}function ne(){window.eventosSeleccionados.forEach(e=>{e.el&&e.el.classList.remove("evento-seleccionado")}),window.eventosSeleccionados=[],ye()}function ye(){let e=document.getElementById("contador-seleccion");window.eventosSeleccionados.length>0?(e||(e=document.createElement("div"),e.id="contador-seleccion",e.className="fixed bottom-4 right-4 z-50 bg-purple-600 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-3",document.body.appendChild(e)),e.innerHTML=`
            <span>${window.eventosSeleccionados.length} agrupación(es) seleccionada(s)</span>
            <button onclick="window.limpiarSeleccionEventos()" class="bg-purple-700 hover:bg-purple-800 px-2 py-1 rounded text-sm">✕ Limpiar</button>
            <button onclick="window.automatizarSalidasSeleccion()" class="bg-green-500 hover:bg-green-600 px-3 py-1 rounded text-sm font-medium">🚀 Automatizar</button>
        `,e.style.display="flex"):e&&(e.style.display="none")}window.limpiarSeleccionEventos=ne;window.automatizarSalidasSeleccion=()=>he(null);async function he(e){var o,n,s,i;try{D()}catch{}if(window.eventosSeleccionados.length===0)return Swal.fire("⚠️","No hay agrupaciones seleccionadas.","warning");const t=window.eventosSeleccionados.map(d=>{var m,g;const r=d.event,u=r.extendedProps||{};return{obra_id:u.obra_id||u.resourceId,fecha:((m=r.startStr)==null?void 0:m.split("T")[0])||((g=r.start)==null?void 0:g.toISOString().split("T")[0]),planillas_ids:ge(r),obra_nombre:u.obra||u.resourceTitle||"Sin obra"}});if((await Swal.fire({title:"¿Automatizar salidas?",html:`
            <p class="mb-3">Se crearán salidas automáticas para ${t.length} agrupación(es):</p>
            <div class="text-left max-h-48 overflow-y-auto text-sm">
                ${t.map(d=>`
                    <div class="p-2 mb-1 bg-gray-100 rounded">
                        <strong>${d.obra_nombre}</strong><br>
                        <span class="text-gray-600">Fecha: ${d.fecha} | ${d.planillas_ids.length} planilla(s)</span>
                    </div>
                `).join("")}
            </div>
            <p class="mt-3 text-sm text-gray-600">Los paquetes existentes se asociarán y los nuevos se asociarán automáticamente. Límite: 28 tn por salida.</p>
        `,icon:"question",showCancelButton:!0,confirmButtonColor:"#16a34a",cancelButtonColor:"#6b7280",confirmButtonText:"Sí, automatizar",cancelButtonText:"Cancelar"})).isConfirmed){Swal.fire({title:"Procesando...",html:"Creando salidas automáticas...",allowOutsideClick:!1,didOpen:()=>Swal.showLoading()});try{const r=await(await fetch((n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.automatizarSalidas,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":((s=window.AppSalidas)==null?void 0:s.csrf)||((i=document.querySelector('meta[name="csrf-token"]'))==null?void 0:i.content),Accept:"application/json"},body:JSON.stringify({agrupaciones:t})})).json();if(r.requiere_decision&&r.conflictos){await Pe(t,r.conflictos,e);return}r.success?(ne(),await Swal.fire({icon:"success",title:"¡Automatización completada!",html:`
                    <p>${r.message}</p>
                    <div class="mt-3 text-sm">
                        <p><strong>${r.total_salidas}</strong> salida(s) creada(s)</p>
                        <p><strong>${r.total_paquetes}</strong> paquete(s) asociado(s)</p>
                    </div>
                `,confirmButtonColor:"#16a34a"}),e?e.refetchEvents():window.calendar&&window.calendar.refetchEvents()):Swal.fire({icon:"error",title:"Error",text:r.message||"No se pudieron crear las salidas."})}catch(d){console.error("Error en automatizarSalidas:",d),Swal.fire({icon:"error",title:"Error de conexión",text:"No se pudo conectar con el servidor."})}}}async function Pe(e,t,a){var d,r,u,m;const o=t.map(g=>`
        <tr>
            <td class="border px-2 py-1">${g.paquete_codigo}</td>
            <td class="border px-2 py-1">${g.planilla_codigo}</td>
            <td class="border px-2 py-1">${g.salida_actual}</td>
        </tr>
    `).join(""),n=await Swal.fire({title:"Paquetes ya asignados",html:`
            <p class="mb-3">Los siguientes paquetes ya están asignados a otras salidas:</p>
            <div class="max-h-48 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-1">Paquete</th>
                            <th class="border px-2 py-1">Planilla</th>
                            <th class="border px-2 py-1">Salida actual</th>
                        </tr>
                    </thead>
                    <tbody>${o}</tbody>
                </table>
            </div>
            <p class="mt-3 text-sm">¿Qué deseas hacer?</p>
        `,icon:"warning",showCancelButton:!0,showDenyButton:!0,confirmButtonText:"Cambiar a nueva salida",denyButtonText:"Crear sin estos paquetes",cancelButtonText:"Cancelar",confirmButtonColor:"#f59e0b",denyButtonColor:"#6b7280"});if(n.isDismissed)return;const s=n.isConfirmed?"cambiar":"omitir",i=t.map(g=>g.paquete_id);Swal.fire({title:"Procesando...",html:"Creando salidas automáticas...",allowOutsideClick:!1,didOpen:()=>Swal.showLoading()});try{const v=await(await fetch((r=(d=window.AppSalidas)==null?void 0:d.routes)==null?void 0:r.automatizarSalidas,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":((u=window.AppSalidas)==null?void 0:u.csrf)||((m=document.querySelector('meta[name="csrf-token"]'))==null?void 0:m.content),Accept:"application/json"},body:JSON.stringify({agrupaciones:e,accion_conflicto:s,paquetes_conflicto_ids:i})})).json();v.success?(ne(),await Swal.fire({icon:"success",title:"¡Automatización completada!",html:`<p>${v.message}</p>`,confirmButtonColor:"#16a34a"}),a?a.refetchEvents():window.calendar&&window.calendar.refetchEvents()):Swal.fire({icon:"error",title:"Error",text:v.message||"No se pudieron crear las salidas."})}catch(g){console.error("Error en automatizarSalidas:",g),Swal.fire({icon:"error",title:"Error de conexión",text:"No se pudo conectar con el servidor."})}}async function Ie(e,t){var a,o;try{D()}catch{}if(!e)return Swal.fire("⚠️","ID de salida inválido.","warning");try{const n=await fetch(`${(o=(a=window.AppSalidas)==null?void 0:a.routes)==null?void 0:o.informacionPaquetesSalida}?salida_id=${e}`,{headers:{Accept:"application/json"}});if(!n.ok)throw new Error("Error al cargar información de la salida");const{salida:s,paquetesAsignados:i,paquetesDisponibles:d,paquetesTodos:r,filtros:u}=await n.json();ze(s,i,d,r||[],u||{obras:[],planillas:[],obrasRelacionadas:[]},t)}catch(n){console.error(n),Swal.fire("❌","Error al cargar la información de la salida","error")}}function ze(e,t,a,o,n,s){window._gestionPaquetesData={salida:e,paquetesAsignados:t,paquetesDisponibles:a,paquetesTodos:o,filtros:n,mostrarTodos:!1};const i=Fe(e,t,a,n);Swal.fire({title:`📦 Gestionar Paquetes - Salida ${e.codigo_salida||e.id}`,html:i,width:Math.min(window.innerWidth*.95,1200),showConfirmButton:!0,showCancelButton:!0,confirmButtonText:"💾 Guardar Cambios",cancelButtonText:"Cancelar",focusConfirm:!1,customClass:{popup:"w-full max-w-screen-xl"},didOpen:()=>{we(),Be(),Re(),setTimeout(()=>{Oe()},100)},willClose:()=>{_.cleanup&&_.cleanup();const d=document.getElementById("modal-keyboard-indicator");d&&d.remove()},preConfirm:()=>Ge()}).then(async d=>{d.isConfirmed&&d.value&&await Ve(e.id,d.value,s)})}function Fe(e,t,a,o){var u,m;const n=t.reduce((g,v)=>g+(parseFloat(v.peso)||0),0);let s="";e.salida_clientes&&e.salida_clientes.length>0&&(s='<div class="col-span-2"><strong>Obras/Clientes:</strong><br>',e.salida_clientes.forEach(g=>{var l,f,b,E,h;const v=((l=g.obra)==null?void 0:l.obra)||"Obra desconocida",c=(f=g.obra)!=null&&f.cod_obra?`(${g.obra.cod_obra})`:"",p=((b=g.cliente)==null?void 0:b.empresa)||((h=(E=g.obra)==null?void 0:E.cliente)==null?void 0:h.empresa)||"";s+=`<span class="text-xs">• ${v} ${c}`,p&&(s+=` - ${p}`),s+="</span><br>"}),s+="</div>");const i=`
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><strong>Código:</strong> ${e.codigo_salida||"N/A"}</div>
                <div><strong>Código SAGE:</strong> ${e.codigo_sage||"Sin asignar"}</div>
                <div><strong>Fecha salida:</strong> ${new Date(e.fecha_salida).toLocaleString("es-ES")}</div>
                <div><strong>Estado:</strong> ${e.estado||"pendiente"}</div>
                <div><strong>Empresa transporte:</strong> ${((u=e.empresa_transporte)==null?void 0:u.nombre)||"Sin asignar"}</div>
                <div><strong>Camión:</strong> ${((m=e.camion)==null?void 0:m.modelo)||"Sin asignar"}</div>
                ${s}
            </div>
        </div>
    `,d=((o==null?void 0:o.obras)||[]).map(g=>`<option value="${g.id}">${g.cod_obra||""} - ${g.obra||"Sin nombre"}</option>`).join(""),r=((o==null?void 0:o.planillas)||[]).map(g=>`<option value="${g.id}" data-obra-id="${g.obra_id||""}">${g.codigo||"Sin código"}</option>`).join("");return`
        <div class="text-left">
            ${i}

            <p class="text-sm text-gray-600 mb-4">
                Arrastra paquetes entre las zonas para asignarlos o quitarlos de esta salida.
            </p>

            <div class="grid grid-cols-2 gap-4">
                <!-- Paquetes asignados a esta salida -->
                <div class="bg-green-50 border-2 border-green-200 rounded-lg p-3">
                    <div class="font-semibold text-green-900 mb-2 flex items-center justify-between">
                        <span>📦 Paquetes en esta salida</span>
                        <div class="flex items-center gap-2">
                            <span class="text-xs bg-green-200 px-2 py-1 rounded" id="peso-asignados">${n.toFixed(2)} kg</span>
                            <button type="button" onclick="window.vaciarSalidaModal()"
                                class="text-xs px-2 py-1 bg-orange-500 hover:bg-orange-600 text-white rounded transition-colors"
                                title="Vaciar salida (devolver todos a disponibles)">
                                🔄 Vaciar
                            </button>
                        </div>
                    </div>
                    <div
                        class="paquetes-zona-salida drop-zone overflow-y-auto"
                        data-zona="asignados"
                        style="min-height: 350px; max-height: 450px; border: 2px dashed #10b981; border-radius: 8px; padding: 8px;"
                    >
                        ${ee(t)}
                    </div>
                </div>

                <!-- Paquetes disponibles -->
                <div class="bg-gray-50 border-2 border-gray-300 rounded-lg p-3">
                    <div class="font-semibold text-gray-900 mb-2">
                        <span>📋 Paquetes Disponibles</span>
                    </div>

                    <!-- Filtros -->
                    <div class="space-y-2 mb-3">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">🏗️ Filtrar por Obra</label>
                                <select id="filtro-obra-modal" class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- Todas las obras --</option>
                                    ${d}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">📄 Filtrar por Planilla</label>
                                <select id="filtro-planilla-modal" class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- Todas las planillas --</option>
                                    ${r}
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" id="btn-limpiar-filtros-modal"
                                    class="flex-1 text-xs px-2 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                                🔄 Limpiar Filtros
                            </button>
                            <button type="button" onclick="window.volcarTodosASalidaModal()"
                                    class="flex-1 text-xs px-2 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-md transition-colors font-medium">
                                📥 Volcar todos
                            </button>
                        </div>
                    </div>

                    <div
                        class="paquetes-zona-salida drop-zone overflow-y-auto"
                        data-zona="disponibles"
                        style="min-height: 250px; max-height: 350px; border: 2px dashed #6b7280; border-radius: 8px; padding: 8px;"
                    >
                        ${ee(a)}
                    </div>
                </div>
            </div>
        </div>
    `}function ee(e){return!e||e.length===0?'<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>':e.map(t=>{var a,o,n,s,i,d,r,u,m,g,v,c,p,l,f,b;return`
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${t.id}"
            data-peso="${t.peso||0}"
            data-obra-id="${((o=(a=t.planilla)==null?void 0:a.obra)==null?void 0:o.id)||""}"
            data-obra="${((s=(n=t.planilla)==null?void 0:n.obra)==null?void 0:s.obra)||""}"
            data-planilla-id="${((i=t.planilla)==null?void 0:i.id)||""}"
            data-planilla="${((d=t.planilla)==null?void 0:d.codigo)||""}"
            data-cliente="${((u=(r=t.planilla)==null?void 0:r.cliente)==null?void 0:u.empresa)||""}"
            data-paquete-json='${JSON.stringify(t).replace(/'/g,"&#39;")}'
        >
            <div class="flex items-center justify-between text-xs">
                <span class="font-medium">📦 ${t.codigo||"Paquete #"+t.id}</span>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        onclick="event.stopPropagation(); window.verElementosPaqueteSalida(${t.id})"
                        class="text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded p-1 transition-colors"
                        title="Ver elementos del paquete"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                    <span class="text-gray-600">${parseFloat(t.peso||0).toFixed(2)} kg</span>
                </div>
            </div>
            <div class="text-xs text-gray-500 mt-1">
                <div>📄 ${((m=t.planilla)==null?void 0:m.codigo)||t.planilla_id}</div>
                <div>🏗️ ${((v=(g=t.planilla)==null?void 0:g.obra)==null?void 0:v.cod_obra)||""} - ${((p=(c=t.planilla)==null?void 0:c.obra)==null?void 0:p.obra)||"N/A"}</div>
                <div>👤 ${((f=(l=t.planilla)==null?void 0:l.cliente)==null?void 0:f.empresa)||"Sin cliente"}</div>
                ${(b=t.nave)!=null&&b.obra?`<div class="text-blue-600 font-medium">📍 ${t.nave.obra}</div>`:""}
            </div>
        </div>
    `}).join("")}async function Me(e){var t;try{const a=document.querySelector(`[data-paquete-id="${e}"]`);let o=null;if(a&&a.dataset.paqueteJson)try{o=JSON.parse(a.dataset.paqueteJson.replace(/&#39;/g,"'"))}catch(r){console.warn("No se pudo parsear JSON del paquete",r)}if(!o){const r=await fetch(`/api/paquetes/${e}/elementos`);r.ok&&(o=await r.json())}if(!o){alert("No se pudo obtener información del paquete");return}const n=[];if(o.etiquetas&&o.etiquetas.length>0&&o.etiquetas.forEach(r=>{r.elementos&&r.elementos.length>0&&r.elementos.forEach(u=>{n.push({id:u.id,dimensiones:u.dimensiones,peso:u.peso,longitud:u.longitud,diametro:u.diametro})})}),n.length===0){alert("Este paquete no tiene elementos para mostrar");return}const s=n.map((r,u)=>`
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">Elemento #${r.id}</span>
                    <span class="text-xs text-gray-500">${u+1} de ${n.length}</span>
                </div>
                <div class="mt-2 text-sm text-gray-600 grid grid-cols-2 gap-2">
                    ${r.diametro?`<div><strong>Ø:</strong> ${r.diametro} mm</div>`:""}
                    ${r.longitud?`<div><strong>Long:</strong> ${r.longitud} mm</div>`:""}
                    ${r.peso?`<div><strong>Peso:</strong> ${parseFloat(r.peso).toFixed(2)} kg</div>`:""}
                </div>
                ${r.dimensiones?`
                    <div class="mt-2 p-2 bg-white border rounded">
                        <div id="elemento-dibujo-${r.id}" class="w-full h-32"></div>
                    </div>
                `:""}
            </div>
        `).join(""),i=document.getElementById("modal-elementos-paquete-overlay");i&&i.remove();const d=`
            <div id="modal-elementos-paquete-overlay"
                 class="fixed inset-0 flex items-center justify-center p-4"
                 style="z-index: 10000; background: rgba(0,0,0,0.5);"
                 onclick="if(event.target === this) this.remove()">
                <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-between p-4 border-b bg-blue-600 text-white rounded-t-lg">
                        <h3 class="text-lg font-semibold">👁️ Elementos del Paquete #${e}</h3>
                        <button onclick="document.getElementById('modal-elementos-paquete-overlay').remove()"
                                class="text-white hover:bg-blue-700 rounded p-1 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-4 overflow-y-auto flex-1">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <div class="text-sm">
                                <strong>Planilla:</strong> ${((t=o.planilla)==null?void 0:t.codigo)||"N/A"}<br>
                                <strong>Peso total:</strong> ${parseFloat(o.peso||0).toFixed(2)} kg<br>
                                <strong>Total elementos:</strong> ${n.length}
                            </div>
                        </div>
                        ${s}
                    </div>
                    <div class="p-4 border-t bg-gray-50 rounded-b-lg">
                        <button onclick="document.getElementById('modal-elementos-paquete-overlay').remove()"
                                class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        `;document.body.insertAdjacentHTML("beforeend",d),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&n.forEach(r=>{r.dimensiones&&window.dibujarFiguraElemento(`elemento-dibujo-${r.id}`,r.dimensiones,null)})},100)}catch(a){console.error("Error al ver elementos del paquete:",a),alert("Error al cargar los elementos del paquete")}}window.verElementosPaqueteSalida=Me;function Be(e){const t=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),o=document.getElementById("btn-limpiar-filtros-modal");t&&t.addEventListener("change",()=>{ie(),Q()}),a&&a.addEventListener("change",()=>{Q()}),o&&o.addEventListener("click",()=>{t&&(t.value=""),a&&(a.value=""),ie(),Q()})}function ie(){const e=document.getElementById("filtro-obra-modal"),t=document.getElementById("filtro-planilla-modal"),a=window._gestionPaquetesData;if(!t||!a)return;const o=(e==null?void 0:e.value)||"",n=o?a.paquetesTodos:a.paquetesDisponibles,s=new Map;n.forEach(r=>{var u,m,g;if((u=r.planilla)!=null&&u.id){if(o&&String((m=r.planilla.obra)==null?void 0:m.id)!==o)return;s.has(r.planilla.id)||s.set(r.planilla.id,{id:r.planilla.id,codigo:r.planilla.codigo||"Sin código",obra_id:(g=r.planilla.obra)==null?void 0:g.id})}});const i=Array.from(s.values()).sort((r,u)=>(r.codigo||"").localeCompare(u.codigo||"")),d=t.value;t.innerHTML='<option value="">-- Todas las planillas --</option>',i.forEach(r=>{const u=document.createElement("option");u.value=r.id,u.textContent=r.codigo,t.appendChild(u)}),d&&s.has(parseInt(d))?t.value=d:t.value=""}function Q(){const e=document.getElementById("filtro-obra-modal"),t=document.getElementById("filtro-planilla-modal"),a=window._gestionPaquetesData,o=(e==null?void 0:e.value)||"",n=(t==null?void 0:t.value)||"",s=document.querySelector('[data-zona="disponibles"]');if(!s||!a)return;const i=document.querySelector('[data-zona="asignados"]'),d=new Set;i&&i.querySelectorAll(".paquete-item-salida").forEach(m=>{d.add(parseInt(m.dataset.paqueteId))});let u=(o?a.paquetesTodos:a.paquetesDisponibles).filter(m=>{var g,v,c;return!(d.has(m.id)||o&&String((v=(g=m.planilla)==null?void 0:g.obra)==null?void 0:v.id)!==o||n&&String((c=m.planilla)==null?void 0:c.id)!==n)});s.innerHTML=ee(u),we(),u.length===0&&(s.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">No hay paquetes que coincidan con el filtro</div>')}let _={zonaActiva:"asignados",indiceFocused:-1,cleanup:null};function Oe(){_.cleanup&&_.cleanup(),_.zonaActiva="asignados",_.indiceFocused=0,z();function e(t){var g;if(!document.querySelector(".swal2-container"))return;const a=t.target.tagName.toLowerCase(),o=a==="select";if((a==="input"||a==="textarea")&&t.key!=="Escape")return;const s=document.querySelector('[data-zona="asignados"]'),i=document.querySelector('[data-zona="disponibles"]');if(!s||!i)return;const d=_.zonaActiva==="asignados"?s:i,r=Array.from(d.querySelectorAll('.paquete-item-salida:not([style*="display: none"])')),u=r.length;let m=!1;if(!o)switch(t.key){case"ArrowDown":u>0&&(_.indiceFocused=(_.indiceFocused+1)%u,z(),m=!0);break;case"ArrowUp":u>0&&(_.indiceFocused=_.indiceFocused<=0?u-1:_.indiceFocused-1,z(),m=!0);break;case"ArrowLeft":case"ArrowRight":_.zonaActiva=_.zonaActiva==="asignados"?"disponibles":"asignados",_.indiceFocused=0,z(),m=!0;break;case"Tab":t.preventDefault(),_.zonaActiva=_.zonaActiva==="asignados"?"disponibles":"asignados",_.indiceFocused=0,z(),m=!0;break;case"Enter":{if(u>0&&_.indiceFocused>=0){const v=r[_.indiceFocused];if(v){je(v);const c=Array.from(d.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));_.indiceFocused>=c.length&&(_.indiceFocused=Math.max(0,c.length-1)),z(),m=!0}}break}case"Home":_.indiceFocused=0,z(),m=!0;break;case"End":_.indiceFocused=Math.max(0,u-1),z(),m=!0;break}if(m){t.preventDefault(),t.stopPropagation();return}switch(t.key){case"o":case"O":{const v=document.getElementById("filtro-obra-modal");v&&(v.focus(),m=!0);break}case"p":case"P":{const v=document.getElementById("filtro-planilla-modal");v&&(v.focus(),m=!0);break}case"l":case"L":{const v=document.getElementById("btn-limpiar-filtros-modal");v&&(v.click(),(g=document.activeElement)==null||g.blur(),z(),m=!0);break}case"/":case"f":case"F":{const v=document.getElementById("filtro-obra-modal");v&&(v.focus(),m=!0);break}case"Escape":o&&(document.activeElement.blur(),z(),m=!0);break;case"s":case"S":{if(t.ctrlKey||t.metaKey){const v=document.querySelector(".swal2-confirm");v&&(v.click(),m=!0)}break}}m&&(t.preventDefault(),t.stopPropagation())}document.addEventListener("keydown",e,!0),_.cleanup=()=>{document.removeEventListener("keydown",e,!0),ve()}}function z(){ve();const e=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!e||!t)return;_.zonaActiva==="asignados"?(e.classList.add("zona-activa-keyboard"),t.classList.remove("zona-activa-keyboard")):(t.classList.add("zona-activa-keyboard"),e.classList.remove("zona-activa-keyboard"));const a=_.zonaActiva==="asignados"?e:t,o=Array.from(a.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));if(o.length>0&&_.indiceFocused>=0){const n=Math.min(_.indiceFocused,o.length-1),s=o[n];s&&(s.classList.add("paquete-focused-keyboard"),s.scrollIntoView({behavior:"smooth",block:"nearest"}))}Ne()}function ve(){document.querySelectorAll(".paquete-focused-keyboard").forEach(e=>{e.classList.remove("paquete-focused-keyboard")}),document.querySelectorAll(".zona-activa-keyboard").forEach(e=>{e.classList.remove("zona-activa-keyboard")})}function je(e){const t=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!t||!a)return;const o=e.closest("[data-zona]"),n=o.dataset.zona==="asignados"?a:t,s=n.querySelector(".placeholder-sin-paquetes");if(s&&s.remove(),n.appendChild(e),o.querySelectorAll(".paquete-item-salida").length===0){const d=document.createElement("div");d.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",d.textContent="Sin paquetes",o.appendChild(d)}be(e),Y()}function Ne(){let e=document.getElementById("modal-keyboard-indicator");e||(e=document.createElement("div"),e.id="modal-keyboard-indicator",e.className="fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs",document.body.appendChild(e));const t=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]'),o=(t==null?void 0:t.querySelectorAll(".paquete-item-salida").length)||0,n=(a==null?void 0:a.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0,s=_.zonaActiva==="asignados"?`📦 Asignados (${o})`:`📋 Disponibles (${n})`;e.innerHTML=`
        <div class="flex items-center gap-2 mb-2">
            <span class="${_.zonaActiva==="asignados"?"bg-green-500":"bg-gray-500"} text-white text-xs px-2 py-0.5 rounded">${s}</span>
        </div>
        <div class="text-gray-400 space-y-1">
            <div class="flex gap-3">
                <span>↑↓ Navegar</span>
                <span>←→ Zona</span>
                <span>Enter Mover</span>
            </div>
            <div class="flex gap-3 border-t border-gray-700 pt-1 mt-1">
                <span>O Obra</span>
                <span>P Planilla</span>
                <span>L Limpiar</span>
            </div>
            <div class="flex gap-3">
                <span>T Todos</span>
                <span>Esc Salir filtro</span>
                <span>Ctrl+S Guardar</span>
            </div>
        </div>
    `,clearTimeout(e._checkTimeout),e._checkTimeout=setTimeout(()=>{document.querySelector(".swal2-container")||e.remove()},500)}function Re(){if(document.getElementById("modal-keyboard-styles"))return;const e=document.createElement("style");e.id="modal-keyboard-styles",e.textContent=`
        .paquete-focused-keyboard {
            outline: 3px solid #3b82f6 !important;
            outline-offset: 2px;
            background-color: #eff6ff !important;
            transform: scale(1.02);
            z-index: 10;
            position: relative;
        }

        .paquete-focused-keyboard::before {
            content: '►';
            position: absolute;
            left: -16px;
            top: 50%;
            transform: translateY(-50%);
            color: #3b82f6;
            font-size: 12px;
        }

        .zona-activa-keyboard {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
        }

        [data-zona="asignados"].zona-activa-keyboard {
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.3) !important;
        }

        [data-zona="disponibles"].zona-activa-keyboard {
            box-shadow: 0 0 0 3px rgba(107, 114, 128, 0.3) !important;
        }
    `,document.head.appendChild(e)}function be(e){e.addEventListener("dragstart",t=>{e.style.opacity="0.5",t.dataTransfer.setData("text/plain",e.dataset.paqueteId)}),e.addEventListener("dragend",t=>{e.style.opacity="1"})}function we(){document.querySelectorAll(".paquete-item-salida").forEach(e=>{be(e)}),document.querySelectorAll(".drop-zone").forEach(e=>{e.addEventListener("dragover",t=>{t.preventDefault();const a=e.dataset.zona;e.style.backgroundColor=a==="asignados"?"#d1fae5":"#e0f2fe"}),e.addEventListener("dragleave",t=>{e.style.backgroundColor=""}),e.addEventListener("drop",t=>{t.preventDefault(),e.style.backgroundColor="";const a=t.dataTransfer.getData("text/plain"),o=document.querySelector(`.paquete-item-salida[data-paquete-id="${a}"]`);if(o){const n=e.querySelector(".placeholder-sin-paquetes");n&&n.remove(),e.appendChild(o),Y()}})})}function Y(){const e=document.querySelector('[data-zona="asignados"]'),t=e==null?void 0:e.querySelectorAll(".paquete-item-salida");let a=0;t==null||t.forEach(n=>{const s=parseFloat(n.dataset.peso)||0;a+=s});const o=document.getElementById("peso-asignados");o&&(o.textContent=`${a.toFixed(2)} kg`)}function He(){const e=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!e||!t)return;const a=e.querySelectorAll(".paquete-item-salida");if(a.length===0)return;a.forEach(n=>{t.appendChild(n)});const o=t.querySelector(".placeholder-sin-paquetes");o&&o.remove(),e.querySelectorAll(".paquete-item-salida").length===0&&(e.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),Y()}function We(){const e=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!e||!t)return;const a=Array.from(t.querySelectorAll(".paquete-item-salida")).filter(s=>s.style.display!=="none");if(a.length===0)return;const o=e.querySelector(".placeholder-sin-paquetes");o&&o.remove(),a.forEach(s=>{e.appendChild(s)}),t.querySelectorAll(".paquete-item-salida").length===0&&(t.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),Y()}window.vaciarSalidaModal=He;window.volcarTodosASalidaModal=We;function Ge(){const e=document.querySelector('[data-zona="asignados"]');return{paquetes_ids:Array.from((e==null?void 0:e.querySelectorAll(".paquete-item-salida"))||[]).map(a=>parseInt(a.dataset.paqueteId))}}async function Ve(e,t,a){var o,n,s,i;try{const r=await(await fetch((n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.guardarPaquetesSalida,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf},body:JSON.stringify({salida_id:e,paquetes_ids:t.paquetes_ids})})).json();r.success?(await Swal.fire({icon:"success",title:"✅ Cambios Guardados",text:"Los paquetes de la salida se han actualizado correctamente.",timer:2e3}),a&&(a.refetchEvents(),(i=a.refetchResources)==null||i.call(a))):await Swal.fire("⚠️",r.message||"No se pudieron guardar los cambios","warning")}catch(d){console.error(d),Swal.fire("❌","Error al guardar los paquetes","error")}}async function Xe(e,t,a){try{D()}catch{}window.Livewire.dispatch("abrirComentario",{salidaId:e}),window._calendarRef=a}function Ke(e){return e?typeof e=="string"?e.split(",").map(a=>a.trim()).filter(Boolean):Array.from(e).map(a=>typeof a=="object"&&(a==null?void 0:a.id)!=null?a.id:a).map(String).map(a=>a.trim()).filter(Boolean):[]}async function Ye(e){var s,i;const t=(i=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:i.informacionPlanillas;if(!t)throw new Error("Ruta 'informacionPlanillas' no configurada");const a=`${t}?ids=${encodeURIComponent(e.join(","))}`,o=await fetch(a,{headers:{Accept:"application/json"}});if(!o.ok){const d=await o.text().catch(()=>"");throw new Error(`GET ${a} -> ${o.status} ${d}`)}const n=await o.json();return Array.isArray(n==null?void 0:n.planillas)?n.planillas:[]}function xe(e){if(!e)return!1;const a=new Date(e+"T00:00:00").getDay();return a===0||a===6}function Je(e,t,a,o){const n=document.getElementById("modal-figura-elemento-overlay");n&&n.remove();const s=o.getBoundingClientRect(),i=320,d=240;let r=s.right+10;r+i>window.innerWidth&&(r=s.left-i-10);let u=s.top-d/2+s.height/2;u<10&&(u=10),u+d>window.innerHeight-10&&(u=window.innerHeight-d-10);const m=`
        <div id="modal-figura-elemento-overlay"
             class="fixed bg-white rounded-lg shadow-2xl border border-gray-300"
             style="z-index: 10001; left: ${r}px; top: ${u}px; width: ${i}px;"
             onmouseleave="this.remove()">
            <div class="flex items-center justify-between px-3 py-2 border-b bg-gray-100 rounded-t-lg">
                <h3 class="text-xs font-semibold text-gray-700">${t||"Elemento"}</h3>
            </div>
            <div class="p-2">
                <div id="figura-elemento-container-${e}" class="w-full h-36 bg-gray-50 rounded"></div>
                <div class="mt-2 px-1 py-1 bg-gray-100 rounded text-xs text-gray-600 font-mono break-all">
                    ${a||"Sin dimensiones"}
                </div>
            </div>
        </div>
    `;document.body.insertAdjacentHTML("beforeend",m),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&window.dibujarFiguraElemento(`figura-elemento-container-${e}`,a,null)},50)}function Ue(e){return`
    <div class="text-left">
      <div class="text-sm text-gray-600 mb-2">
        Edita la <strong>fecha estimada de entrega</strong> de planillas y elementos.
        <span class="text-blue-600">▶</span> = expandir elementos, <span class="text-purple-600">☑</span> = seleccionar para asignar fecha masiva
      </div>

      <!-- Barra de acciones masivas para elementos -->
      <div id="barra-acciones-masivas" class="mb-3 p-3 bg-purple-50 border border-purple-200 rounded-lg hidden">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-purple-800">
              <span id="contador-seleccionados">0</span> elementos seleccionados
            </span>
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm text-purple-700">Asignar fecha:</label>
            <input type="date" id="fecha-masiva" class="swal2-input !m-0 !w-auto !text-sm !bg-white !border-purple-300">
            <button type="button" id="aplicar-fecha-masiva" class="text-sm bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded font-medium transition-colors">
              Aplicar a seleccionados
            </button>
          </div>
          <div class="flex items-center gap-2 ml-auto">
            <button type="button" id="limpiar-fecha-seleccionados" class="text-xs bg-gray-500 hover:bg-gray-600 text-white px-2 py-1 rounded" title="Quitar fecha de los seleccionados">
              Limpiar fecha
            </button>
            <button type="button" id="deseleccionar-todos" class="text-xs bg-gray-400 hover:bg-gray-500 text-white px-2 py-1 rounded">
              Deseleccionar
            </button>
          </div>
        </div>
      </div>

      <!-- Sumatorio dinámico por fechas -->
      <div id="sumatorio-fechas" class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="text-sm font-medium text-blue-800 mb-2">📊 Resumen por fecha:</div>
        <div id="resumen-contenido" class="text-xs text-blue-700">
          Cambia las fechas para ver el resumen...
        </div>
      </div>

      <div class="overflow-auto" style="max-height:50vh;border:1px solid #e5e7eb;border-radius:6px;">
        <table class="min-w-full text-sm">
        <thead class="sticky top-0 bg-white z-10">
          <tr>
            <th class="px-2 py-1 text-left">ID / Código</th>
            <th class="px-2 py-1 text-left">Marca</th>
            <th class="px-2 py-1 text-left">Ø</th>
            <th class="px-2 py-1 text-left">Longitud</th>
            <th class="px-2 py-1 text-left">Barras</th>
            <th class="px-2 py-1 text-left">Peso</th>
            <th class="px-2 py-1 text-left" colspan="2">Fecha Entrega</th>
          </tr>
        </thead>
          <tbody>${e.map((a,o)=>{var c,p,l;const n=((c=a.obra)==null?void 0:c.codigo)||"",s=((p=a.obra)==null?void 0:p.nombre)||"",i=a.seccion||"";a.descripcion;const d=a.codigo||`Planilla ${a.id}`,r=a.peso_total?parseFloat(a.peso_total).toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})+" kg":"",u=De(a.fecha_estimada_entrega),m=a.elementos&&a.elementos.length>0,g=((l=a.elementos)==null?void 0:l.length)||0;let v="";return m&&(v=a.elementos.map((f,b)=>{const E=f.fecha_entrega||"",h=f.peso?parseFloat(f.peso).toFixed(2):"-",S=f.codigo||"-",$=f.dimensiones&&f.dimensiones.trim()!=="",w=$?f.dimensiones.replace(/"/g,"&quot;").replace(/'/g,"&#39;"):"",C=S.replace(/"/g,"&quot;").replace(/'/g,"&#39;");return`
                    <tr class="elemento-row elemento-planilla-${a.id} bg-gray-50 hidden">
                        <td class="px-2 py-1 text-xs text-gray-400 pl-4">
                            <div class="flex items-center gap-1">
                                <input type="checkbox" class="elemento-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-3.5 w-3.5"
                                       data-elemento-id="${f.id}"
                                       data-planilla-id="${a.id}">
                                <span>↳</span>
                                <span class="font-medium text-gray-600">${S}</span>
                                ${$?`
                                <button type="button"
                                        class="ver-figura-elemento text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded p-0.5 transition-colors"
                                        data-elemento-id="${f.id}"
                                        data-elemento-codigo="${C}"
                                        data-dimensiones="${w}"
                                        title="Click para seleccionar, hover para ver figura">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                `:""}
                            </div>
                        </td>
                        <td class="px-2 py-1 text-xs text-gray-500">${f.marca||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">Ø${f.diametro||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${f.longitud||"-"} mm</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${f.barras||"-"} uds</td>
                        <td class="px-2 py-1 text-xs text-right text-gray-500">${h} kg</td>
                        <td class="px-2 py-1" colspan="2">
                            <input type="date" class="swal2-input !m-0 !w-auto !text-xs elemento-fecha"
                                   data-elemento-id="${f.id}"
                                   data-planilla-id="${a.id}"
                                   value="${E}">
                        </td>
                    </tr>`}).join("")),`
<tr class="planilla-row hover:bg-blue-50 bg-blue-100 border-t border-blue-200" data-planilla-id="${a.id}" style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${o*18}ms;">
  <td class="px-2 py-2 text-xs font-semibold text-blue-800" colspan="2">
    ${m?`<button type="button" class="toggle-elementos mr-1 text-blue-600 hover:text-blue-800" data-planilla-id="${a.id}">▶</button>`:""}
    📄 ${d}
    ${m?`<span class="ml-1 text-xs text-blue-500 font-normal">(${g} elem.)</span>`:""}
  </td>
  <td class="px-2 py-2 text-xs text-blue-700" colspan="2">
    <span class="font-medium">${n}</span> ${s}
  </td>
  <td class="px-2 py-2 text-xs text-blue-600">${i||"-"}</td>
  <td class="px-2 py-2 text-xs text-right font-semibold text-blue-800">${r}</td>
  <td class="px-2 py-2" colspan="2">
    <div class="flex items-center gap-1">
      <input type="date" class="swal2-input !m-0 !w-auto planilla-fecha !bg-blue-50 !border-blue-300" data-planilla-id="${a.id}" value="${u}">
      ${m?`<button type="button" class="aplicar-fecha-elementos text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded" data-planilla-id="${a.id}" title="Aplicar fecha a todos los elementos">↓</button>`:""}
    </div>
  </td>
</tr>
${v}`}).join("")}</tbody>
        </table>
      </div>

      <div class="mt-2 flex flex-wrap gap-2">
        <button type="button" id="expandir-todos" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded">
          📂 Expandir todos
        </button>
        <button type="button" id="colapsar-todos" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded">
          📁 Colapsar todos
        </button>
        <span class="border-l border-gray-300 mx-1"></span>
        <button type="button" id="seleccionar-todos-elementos" class="text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 px-3 py-1 rounded">
          ☑ Seleccionar todos los elementos
        </button>
        <button type="button" id="seleccionar-sin-fecha" class="text-xs bg-orange-100 hover:bg-orange-200 text-orange-700 px-3 py-1 rounded">
          ☑ Seleccionar sin fecha
        </button>
      </div>
    </div>`}function Ze(e){const t={};return document.querySelectorAll('input[type="date"][data-planilla-id]').forEach(o=>{const n=parseInt(o.dataset.planillaId),s=o.value,i=e.find(d=>d.id===n);s&&i&&i.peso_total&&(t[s]||(t[s]={peso:0,planillas:0,esFinDeSemana:xe(s)}),t[s].peso+=parseFloat(i.peso_total),t[s].planillas+=1)}),t}function le(e){const t=Ze(e),a=document.getElementById("resumen-contenido");if(!a)return;const o=Object.keys(t).sort();if(o.length===0){a.innerHTML='<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';return}const n=o.map(d=>{const r=t[d],u=new Date(d+"T00:00:00").toLocaleDateString("es-ES",{weekday:"short",day:"2-digit",month:"2-digit",year:"numeric"}),m=r.peso.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2}),g=r.esFinDeSemana?"bg-orange-100 border-orange-300 text-orange-800":"bg-green-100 border-green-300 text-green-800",v=r.esFinDeSemana?"🏖️":"📦";return`
            <div class="inline-block m-1 px-2 py-1 rounded border ${g}">
                <span class="font-medium">${v} ${u}</span>
                <br>
                <span class="text-xs">${m} kg (${r.planillas} planilla${r.planillas!==1?"s":""})</span>
            </div>
        `}).join(""),s=o.reduce((d,r)=>d+t[r].peso,0),i=o.reduce((d,r)=>d+t[r].planillas,0);a.innerHTML=`
        <div class="mb-2">${n}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${s.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})} kg 
            (${i} planilla${i!==1?"s":""})
        </div>
    `}async function Qe(e){var o,n,s;const t=(n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.actualizarFechasPlanillas;if(!t)throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");const a=await fetch(t,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf,Accept:"application/json"},body:JSON.stringify({planillas:e})});if(!a.ok){const i=await a.text().catch(()=>"");throw new Error(`PUT ${t} -> ${a.status} ${i}`)}return a.json().catch(()=>({}))}async function et(e,t){var a,o;try{const n=Array.from(new Set(Ke(e))).map(Number).filter(Boolean);if(!n.length)return Swal.fire("⚠️","No hay planillas en la agrupación.","warning");const s=await Ye(n);if(!s.length)return Swal.fire("⚠️","No se han encontrado planillas.","warning");const d=`
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `+Ue(s),{isConfirmed:r}=await Swal.fire({title:"",html:d,width:Math.min(window.innerWidth*.98,1200),customClass:{popup:"w-full max-w-screen-xl"},showCancelButton:!0,confirmButtonText:"💾 Guardar",cancelButtonText:"Cancelar",focusConfirm:!1,showClass:{popup:"swal-fade-in-zoom"},hideClass:{popup:"swal-fade-out"},didOpen:c=>{var b,E,h,S,$,w,C;tt(c),V("#swal-drag",!1),setTimeout(()=>{const y=Swal.getHtmlContainer().querySelector('input[type="date"]');y==null||y.focus({preventScroll:!0})},120),Swal.getHtmlContainer().querySelectorAll('input[type="date"]').forEach(y=>{y.addEventListener("change",function(){xe(this.value)?this.classList.add("weekend-date"):this.classList.remove("weekend-date"),le(s)})});const l=Swal.getHtmlContainer();l.querySelectorAll(".toggle-elementos").forEach(y=>{y.addEventListener("click",q=>{q.stopPropagation();const k=y.dataset.planillaId,L=l.querySelectorAll(`.elemento-planilla-${k}`),A=y.textContent==="▼";L.forEach(T=>{T.classList.toggle("hidden",A)}),y.textContent=A?"▶":"▼"})}),(b=l.querySelector("#expandir-todos"))==null||b.addEventListener("click",()=>{l.querySelectorAll(".elemento-row").forEach(y=>y.classList.remove("hidden")),l.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▼")}),(E=l.querySelector("#colapsar-todos"))==null||E.addEventListener("click",()=>{l.querySelectorAll(".elemento-row").forEach(y=>y.classList.add("hidden")),l.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▶")});function f(){const q=l.querySelectorAll(".elemento-checkbox:checked").length,k=l.querySelector("#barra-acciones-masivas"),L=l.querySelector("#contador-seleccionados");q>0?(k==null||k.classList.remove("hidden"),L&&(L.textContent=q)):k==null||k.classList.add("hidden")}l.querySelectorAll(".elemento-checkbox").forEach(y=>{y.addEventListener("change",f)}),(h=l.querySelector("#seleccionar-todos-elementos"))==null||h.addEventListener("click",()=>{l.querySelectorAll(".elemento-row").forEach(y=>y.classList.remove("hidden")),l.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▼"),l.querySelectorAll(".elemento-checkbox").forEach(y=>{y.checked=!0}),f()}),(S=l.querySelector("#seleccionar-sin-fecha"))==null||S.addEventListener("click",()=>{l.querySelectorAll(".elemento-row").forEach(y=>y.classList.remove("hidden")),l.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▼"),l.querySelectorAll(".elemento-checkbox").forEach(y=>{y.checked=!1}),l.querySelectorAll(".elemento-checkbox").forEach(y=>{const q=y.dataset.elementoId,k=l.querySelector(`.elemento-fecha[data-elemento-id="${q}"]`);k&&!k.value&&(y.checked=!0)}),f()}),($=l.querySelector("#deseleccionar-todos"))==null||$.addEventListener("click",()=>{l.querySelectorAll(".elemento-checkbox").forEach(y=>{y.checked=!1}),f()}),(w=l.querySelector("#aplicar-fecha-masiva"))==null||w.addEventListener("click",()=>{var A;const y=(A=l.querySelector("#fecha-masiva"))==null?void 0:A.value;if(!y){alert("Por favor, selecciona una fecha para aplicar");return}l.querySelectorAll(".elemento-checkbox:checked").forEach(T=>{const P=T.dataset.elementoId,I=l.querySelector(`.elemento-fecha[data-elemento-id="${P}"]`);I&&(I.value=y,I.dispatchEvent(new Event("change")))});const k=l.querySelector("#aplicar-fecha-masiva"),L=k.textContent;k.textContent="✓ Aplicado",k.classList.add("bg-green-600"),setTimeout(()=>{k.textContent=L,k.classList.remove("bg-green-600")},1500)}),(C=l.querySelector("#limpiar-fecha-seleccionados"))==null||C.addEventListener("click",()=>{l.querySelectorAll(".elemento-checkbox:checked").forEach(q=>{const k=q.dataset.elementoId,L=l.querySelector(`.elemento-fecha[data-elemento-id="${k}"]`);L&&(L.value="",L.dispatchEvent(new Event("change")))})}),l.querySelectorAll(".aplicar-fecha-elementos").forEach(y=>{y.addEventListener("click",q=>{var A;q.stopPropagation();const k=y.dataset.planillaId,L=(A=l.querySelector(`.planilla-fecha[data-planilla-id="${k}"]`))==null?void 0:A.value;L&&l.querySelectorAll(`.elemento-fecha[data-planilla-id="${k}"]`).forEach(T=>{T.value=L,T.dispatchEvent(new Event("change"))})})}),l.querySelectorAll(".ver-figura-elemento").forEach(y=>{y.addEventListener("mouseenter",q=>{var T,P;const k=y.dataset.elementoId,L=((T=y.dataset.elementoCodigo)==null?void 0:T.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"",A=((P=y.dataset.dimensiones)==null?void 0:P.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"";typeof window.dibujarFiguraElemento=="function"&&Je(k,L,A,y)}),y.addEventListener("mouseleave",q=>{setTimeout(()=>{const k=document.getElementById("modal-figura-elemento-overlay");k&&!k.matches(":hover")&&k.remove()},100)}),y.addEventListener("click",q=>{q.preventDefault(),q.stopPropagation();const k=y.dataset.elementoId,L=l.querySelector(`.elemento-checkbox[data-elemento-id="${k}"]`);if(L){L.checked=!L.checked;const T=l.querySelectorAll(".elemento-checkbox:checked").length,P=l.querySelector("#barra-acciones-masivas"),I=l.querySelector("#contador-seleccionados");T>0?(P==null||P.classList.remove("hidden"),I&&(I.textContent=T)):P==null||P.classList.add("hidden")}})}),setTimeout(()=>{le(s)},100)}});if(!r)return;const u=Swal.getHtmlContainer(),m=u.querySelectorAll(".planilla-fecha"),g=Array.from(m).map(c=>{const p=Number(c.getAttribute("data-planilla-id")),l=u.querySelectorAll(`.elemento-fecha[data-planilla-id="${p}"]`),f=Array.from(l).map(b=>({id:Number(b.getAttribute("data-elemento-id")),fecha_entrega:b.value||null}));return{id:p,fecha_estimada_entrega:c.value,elementos:f.length>0?f:void 0}}),v=await Qe(g);await Swal.fire(v.success?"✅":"⚠️",v.message||(v.success?"Fechas actualizadas":"No se pudieron actualizar"),v.success?"success":"warning"),v.success&&t&&((a=t.refetchEvents)==null||a.call(t),(o=t.refetchResources)==null||o.call(t))}catch(n){console.error("[CambiarFechasEntrega] error:",n),Swal.fire("❌",(n==null?void 0:n.message)||"Ocurrió un error al actualizar las fechas.","error")}}function ce(e,t){e.el.addEventListener("mousedown",D),e.el.addEventListener("contextmenu",a=>{var u;a.preventDefault(),a.stopPropagation();const o=e.event,n=o.extendedProps||{},s=n.tipo||"planilla";let i="";if(s==="salida"){if(n.clientes&&Array.isArray(n.clientes)&&n.clientes.length>0){const m=n.clientes.map(g=>g.nombre).filter(Boolean).join(", ");m&&(i+=`<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${m}</span>`)}n.obras&&Array.isArray(n.obras)&&n.obras.length>0&&(i+='<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ ',i+=n.obras.map(m=>{const g=m.codigo?`(${m.codigo})`:"";return`${m.nombre} ${g}`}).join(", "),i+="</span>")}const d=`
      <div style="padding:10px 12px; font-weight:600;">
        ${o.title??"Evento"}${i}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(o.start).toLocaleString()} — ${new Date(o.end).toLocaleString()}
        </span>
      </div>
    `;let r=[];if(s==="planilla"){const m=ge(o),g=(u=window.eventosSeleccionados)==null?void 0:u.some(v=>v.id===o.id);r=[{label:g?"Deseleccionar":"Seleccionar para automatizar",icon:g?"☐":"☑️",onClick:()=>{re(o,e.el),D()}},{label:"Automatizar salidas",icon:"🚀",onClick:()=>{g||re(o,e.el),he(t)}},{label:"Gestionar Salidas y Paquetes",icon:"📦",onClick:()=>window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${m.join(",")}`},{label:"Cambiar fechas de entrega",icon:"🗓️",onClick:()=>et(m,t)}]}else if(s==="salida"){const m=n.salida_id||o.id;n.empresa_id,n.empresa,r=[{label:"Abrir salida",icon:"🧾",onClick:()=>window.open(`/salidas-ferralla/${m}`,"_blank")},{label:"Gestionar paquetes",icon:"📦",onClick:()=>Ie(m,t)},{label:"Agregar comentario",icon:"✍️",onClick:()=>Xe(m,n.comentario||"",t)}]}else r=[{label:"Abrir",icon:"🧾",onClick:()=>window.open(n.url||"#","_blank")}];Te(a.clientX,a.clientY,{headerHtml:d,items:r})})}function tt(e){e.style.transform="none",e.style.position="fixed",e.style.margin="0";const t=e.offsetWidth,a=e.offsetHeight,o=Math.max(0,Math.round((window.innerWidth-t)/2)),n=Math.max(0,Math.round((window.innerHeight-a)/2));e.style.left=`${o}px`,e.style.top=`${n}px`}function V(e=".swal2-title",t=!1){const a=Swal.getPopup(),o=Swal.getHtmlContainer();let n=(e?(o==null?void 0:o.querySelector(e))||(a==null?void 0:a.querySelector(e)):null)||a;if(!a||!n)return;t&&V.__lastPos&&(a.style.left=V.__lastPos.left,a.style.top=V.__lastPos.top,a.style.transform="none"),n.style.cursor="move",n.style.touchAction="none";const s=p=>{var l;return((l=p.closest)==null?void 0:l.call(p,"input, textarea, select, button, a, label, [contenteditable]"))!=null};let i=!1,d=0,r=0,u=0,m=0;const g=p=>{if(!n.contains(p.target)||s(p.target))return;i=!0,document.body.style.userSelect="none";const l=a.getBoundingClientRect();a.style.left=`${l.left}px`,a.style.top=`${l.top}px`,a.style.transform="none",u=parseFloat(a.style.left||l.left),m=parseFloat(a.style.top||l.top),d=p.clientX,r=p.clientY,document.addEventListener("pointermove",v),document.addEventListener("pointerup",c,{once:!0})},v=p=>{if(!i)return;const l=p.clientX-d,f=p.clientY-r;let b=u+l,E=m+f;const h=a.offsetWidth,S=a.offsetHeight,$=-h+40,w=window.innerWidth-40,C=-S+40,y=window.innerHeight-40;b=Math.max($,Math.min(w,b)),E=Math.max(C,Math.min(y,E)),a.style.left=`${b}px`,a.style.top=`${E}px`},c=()=>{i=!1,document.body.style.userSelect="",t&&(V.__lastPos={left:a.style.left,top:a.style.top}),document.removeEventListener("pointermove",v)};n.addEventListener("pointerdown",g)}document.addEventListener("DOMContentLoaded",function(){window.addEventListener("comentarioGuardado",e=>{const{salidaId:t,comentario:a}=e.detail,o=window._calendarRef;if(o){const n=o.getEventById(`salida-${t}`);n&&(n.setExtendedProp("comentario",a),n._def&&n._def.extendedProps&&(n._def.extendedProps.comentario=a)),typeof Swal<"u"&&Swal.fire({icon:"success",title:"Comentario guardado",text:"El comentario se ha guardado correctamente",timer:2e3,showConfirmButton:!1,toast:!0,position:"top-end"})}})});let x=null;function at(e){setTimeout(()=>{const t=document.querySelector(".fc-resource-timeline .fc-datagrid");if(!t||t.querySelector(".fc-resource-area-resizer"))return;const a=document.createElement("div");a.className="fc-resource-area-resizer",a.title="Arrastrar para redimensionar",t.appendChild(a);let o=!1,n=0,s=0;const i=localStorage.getItem("fc-resource-area-width");i&&(t.style.width=i,e.updateSize()),a.addEventListener("mousedown",d=>{o=!0,n=d.clientX,s=t.offsetWidth,a.classList.add("dragging"),document.body.classList.add("resizing-resource-area"),d.preventDefault()}),document.addEventListener("mousemove",d=>{if(!o)return;const r=d.clientX-n,u=Math.max(100,Math.min(500,s+r));t.style.width=u+"px"}),document.addEventListener("mouseup",()=>{o&&(o=!1,a.classList.remove("dragging"),document.body.classList.remove("resizing-resource-area"),localStorage.setItem("fc-resource-area-width",t.style.width),e.updateSize())})},100)}function ot(e,t){const a=()=>e&&e.offsetParent!==null&&e.clientWidth>0&&e.clientHeight>=0;if(a())return t();if("IntersectionObserver"in window){const n=new IntersectionObserver(s=>{s.some(d=>d.isIntersecting)&&(n.disconnect(),t())},{root:null,threshold:.01});n.observe(e);return}if("ResizeObserver"in window){const n=new ResizeObserver(()=>{a()&&(n.disconnect(),t())});n.observe(e);return}const o=setInterval(()=>{a()&&(clearInterval(o),t())},100)}function F(){x&&(requestAnimationFrame(()=>{try{x.updateSize()}catch{}}),setTimeout(()=>{try{x.updateSize()}catch{}},150))}function nt(){let e=document.getElementById("transparent-drag-image");return e||(e=document.createElement("img"),e.id="transparent-drag-image",e.src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7",e.style.cssText="position: fixed; left: -9999px; top: -9999px; width: 1px; height: 1px; opacity: 0;",document.body.appendChild(e)),e}function st(e,t){var p,l;te();const a=document.createElement("div");a.id="custom-drag-ghost",a.className="custom-drag-ghost";const o=e.extendedProps||{},n=o.tipo==="salida",s=n?"🚚":"📋",i=n?"Salida":"Planilla",d=o.cod_obra||"",r=o.nombre_obra||((p=e.title)==null?void 0:p.split(`
`)[0])||"",u=o.cliente||"",m=o.pesoTotal?Number(o.pesoTotal).toLocaleString("es-ES",{maximumFractionDigits:0}):"",g=o.longitudTotal?Number(o.longitudTotal).toLocaleString("es-ES",{maximumFractionDigits:0}):"",v=o.diametroMedio?Number(o.diametroMedio).toFixed(1):"",c=((l=t==null?void 0:t.style)==null?void 0:l.backgroundColor)||e.backgroundColor||"#6366f1";return a.innerHTML=`
        <div class="ghost-card" style="--ghost-color: ${c};">
            <!-- Tipo -->
            <div class="ghost-type-badge ${n?"badge-salida":"badge-planilla"}">
                <span>${s}</span>
                <span>${i}</span>
            </div>

            <!-- Info principal -->
            <div class="ghost-main">
                ${d?`<div class="ghost-code">${d}</div>`:""}
                ${r?`<div class="ghost-name">${r}</div>`:""}
                ${u?`<div class="ghost-client">👤 ${u}</div>`:""}
            </div>

            <!-- Métricas -->
            ${m||g||v?`
            <div class="ghost-metrics">
                ${m?`<span class="ghost-metric">📦 ${m} kg</span>`:""}
                ${g?`<span class="ghost-metric">📏 ${g} m</span>`:""}
                ${v?`<span class="ghost-metric">⌀ ${v} mm</span>`:""}
            </div>
            `:""}

            <!-- Destino del drop -->
            <div class="ghost-destination">
                <span class="ghost-dest-date">--</span>
            </div>
        </div>
    `,document.body.appendChild(a),a}function de(e,t,a,o){const n=document.getElementById("custom-drag-ghost");if(n){if(n.style.left=`${e+20}px`,n.style.top=`${t-20}px`,a){const s=n.querySelector(".ghost-dest-time");s&&(s.textContent=a)}if(o){const s=n.querySelector(".ghost-dest-date");if(s){const i=new Date(o+"T00:00:00"),d={weekday:"short",day:"numeric",month:"short"};s.textContent=i.toLocaleDateString("es-ES",d)}}}}function te(){const e=document.getElementById("custom-drag-ghost");e&&e.remove()}function ue(e,t){const a=t==null?void 0:t.querySelector(".fc-timegrid-slots");if(!a)return null;const o=a.getBoundingClientRect(),n=e-o.top+a.scrollTop,s=a.scrollHeight||o.height,i=5,d=20,r=d-i,u=n/s*r,m=i*60+u*60,g=Math.round(m/30)*30,v=Math.max(i,Math.min(d-1,Math.floor(g/60))),c=g%60;return`${String(v).padStart(2,"0")}:${String(c).padStart(2,"0")}`}function pe(e){const t=document.querySelectorAll(".fc-timegrid-slot, .fc-timegrid-col");e?t.forEach(a=>{a.classList.add("fc-drop-zone-highlight")}):t.forEach(a=>{a.classList.remove("fc-drop-zone-highlight")})}function rt(){if(!window.FullCalendar)return console.error("FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."),null;if(!document.getElementById("fc-mirror-hide-style-global")){const u=document.createElement("style");u.id="fc-mirror-hide-style-global",u.textContent=`
            /* Ocultar el elemento que FullCalendar mueve con position:fixed durante el drag */
            .fc-event-dragging[style*="position: fixed"],
            .fc-event-dragging[style*="position:fixed"],
            .fc-event.fc-event-dragging[style*="fixed"],
            a.fc-event.fc-event-dragging {
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }

            /* Ocultar completamente el mirror de FullCalendar */
            .fc-event-mirror,
            .fc .fc-event-mirror,
            .fc-timegrid-event.fc-event-mirror,
            .fc-daygrid-event.fc-event-mirror,
            .fc-timeline-event.fc-event-mirror,
            .fc-timegrid-event-harness.fc-event-mirror,
            .fc-daygrid-event-harness .fc-event-mirror,
            [class*="fc-event-mirror"],
            .fc-event.fc-event-mirror {
                display: none !important;
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
            }
        `,document.head.appendChild(u)}x&&x.destroy();const e=["resourceTimeGridDay","resourceTimelineWeek","dayGridMonth"];let t=localStorage.getItem("ultimaVistaCalendario");e.includes(t)||(t="resourceTimeGridDay");const a=localStorage.getItem("fechaCalendario");let o=null;const n=document.getElementById("calendario");if(!n)return console.error("#calendario no encontrado"),null;function s(u){return x?x.getEvents().some(m=>{var c,p;const g=(m.startStr||((c=m.start)==null?void 0:c.toISOString())||"").split("T")[0];return(((p=m.extendedProps)==null?void 0:p.tipo)==="festivo"||typeof m.id=="string"&&m.id.startsWith("festivo-"))&&g===u}):!1}ot(n,()=>{x=new FullCalendar.Calendar(n,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",navLinks:!0,navLinkDayClick:(c,p)=>{var E;const l=c.getDay(),f=l===0||l===6,b=(E=x==null?void 0:x.view)==null?void 0:E.type;if(f&&(b==="resourceTimelineWeek"||b==="dayGridMonth")){p.preventDefault();let h;b==="dayGridMonth"?h=l===6?"saturday":"sunday":h=c.toISOString().split("T")[0],window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(h)?window.expandedWeekendDays.delete(h):window.expandedWeekendDays.add(h),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),x.render(),setTimeout(()=>{var S;return(S=window.applyWeekendCollapse)==null?void 0:S.call(window)},50);return}x.changeView("resourceTimeGridDay",c)},initialView:t,initialDate:a?new Date(a):void 0,dayMaxEventRows:!1,dayMaxEvents:!1,slotMinTime:"05:00:00",slotMaxTime:"20:00:00",buttonText:{today:"Hoy",resourceTimeGridDay:"Día",resourceTimelineWeek:"Semana",dayGridMonth:"Mes"},progressiveEventRendering:!0,expandRows:!0,height:"auto",events:(c,p,l)=>{var b;const f=c.view&&c.view.type||((b=x==null?void 0:x.view)==null?void 0:b.type)||"resourceTimeGridDay";qe(f,c).then(p).catch(l)},resources:(c,p,l)=>{var b;const f=c.view&&c.view.type||((b=x==null?void 0:x.view)==null?void 0:b.type)||"resourceTimeGridDay";Le(f,c).then(p).catch(l)},headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimeGridDay,resourceTimelineWeek,dayGridMonth"},eventOrderStrict:!0,eventOrder:(c,p)=>{var h,S;const l=((h=c.extendedProps)==null?void 0:h.tipo)==="resumen-dia",f=((S=p.extendedProps)==null?void 0:S.tipo)==="resumen-dia";if(l&&!f)return-1;if(!l&&f)return 1;const b=parseInt(String(c.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0,E=parseInt(String(p.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0;return b-E},datesSet:c=>{try{const p=it(c);localStorage.setItem("fechaCalendario",p),localStorage.setItem("ultimaVistaCalendario",c.view.type),d(),clearTimeout(o),o=setTimeout(async()=>{M(),x.refetchResources(),x.refetchEvents(),await _e(c.view.type,{startStr:c.startStr,endStr:c.endStr}),F(),(c.view.type==="resourceTimelineWeek"||c.view.type==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)},0)}catch(p){console.error("Error en datesSet:",p)}},loading:c=>{const p=document.getElementById("calendario-loading"),l=document.getElementById("loading-text");if(p&&(c?(p.classList.remove("hidden"),l&&(l.textContent="Cargando eventos...")):p.classList.add("hidden")),!c&&x){const f=x.view.type;f==="resourceTimeGridDay"&&setTimeout(()=>r(),150),(f==="resourceTimelineWeek"||f==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)}},viewDidMount:c=>{d(),c.view.type==="resourceTimeGridDay"&&setTimeout(()=>r(),100),c.view.type==="dayGridMonth"&&setTimeout(()=>{document.querySelectorAll(".fc-daygrid-event-harness").forEach(p=>{p.querySelector(".evento-resumen-diario")||(p.style.setProperty("width","100%","important"),p.style.setProperty("max-width","100%","important"),p.style.setProperty("position","static","important"),p.style.setProperty("left","unset","important"),p.style.setProperty("right","unset","important"),p.style.setProperty("top","unset","important"),p.style.setProperty("inset","unset","important"),p.style.setProperty("margin","0 0 2px 0","important"))}),document.querySelectorAll(".fc-daygrid-event:not(.evento-resumen-diario)").forEach(p=>{p.style.setProperty("width","100%","important"),p.style.setProperty("max-width","100%","important"),p.style.setProperty("margin","0","important"),p.style.setProperty("position","static","important"),p.style.setProperty("left","unset","important"),p.style.setProperty("right","unset","important"),p.style.setProperty("inset","unset","important")})},50)},eventContent:c=>{var E;const p=c.event.backgroundColor||"#9CA3AF",l=c.event.extendedProps||{},f=(E=x==null?void 0:x.view)==null?void 0:E.type;if(l.tipo==="resumen-dia"){const h=Number(l.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),S=Number(l.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),$=l.diametroMedio?Number(l.diametroMedio).toFixed(1):null;if(f==="resourceTimelineWeek")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${h} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${S} m</div>
                                ${$?`<div class="text-yellow-800">⌀ ${$} mm</div>`:""}
                            </div>
                        `};if(f==="dayGridMonth")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${h} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${S} m</div>
                                ${$?`<div class="text-yellow-800">⌀ ${$} mm</div>`:""}
                            </div>
                        `}}let b=`
        <div style="background-color:${p}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${c.event.title}</div>
    `;if(l.tipo==="planilla"){const h=l.pesoTotal!=null?`📦 ${Number(l.pesoTotal).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2})} kg`:null,S=l.longitudTotal!=null?`📏 ${Number(l.longitudTotal).toLocaleString()} m`:null,$=l.diametroMedio!=null?`⌀ ${Number(l.diametroMedio).toFixed(2)} mm`:null,w=[h,S,$].filter(Boolean);w.length>0&&(b+=`<div class="text-sm text-black font-semibold">${w.join(" | ")}</div>`),l.tieneSalidas&&Array.isArray(l.salidas_codigos)&&l.salidas_codigos.length>0&&(b+=`
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${l.salidas_codigos.join(", ")}
                </span>
            </div>`)}return b+="</div>",{html:b}},eventDidMount:function(c){var S,$,w,C,y;const p=c.event.extendedProps||{};if(c.el.setAttribute("draggable","false"),c.el.ondragstart=q=>(q.preventDefault(),!1),p.tipo==="resumen-dia"){c.el.classList.add("evento-resumen-diario"),c.el.style.cursor="default";return}if(c.view.type==="dayGridMonth"){const q=c.el.closest(".fc-daygrid-event-harness");q&&q.classList.add("evento-fullwidth"),c.el.classList.add("evento-fullwidth-event")}const l=(((S=document.getElementById("filtro-obra"))==null?void 0:S.value)||"").trim().toLowerCase(),f=((($=document.getElementById("filtro-nombre-obra"))==null?void 0:$.value)||"").trim().toLowerCase(),b=(((w=document.getElementById("filtro-cod-cliente"))==null?void 0:w.value)||"").trim().toLowerCase(),E=(((C=document.getElementById("filtro-cliente"))==null?void 0:C.value)||"").trim().toLowerCase(),h=(((y=document.getElementById("filtro-cod-planilla"))==null?void 0:y.value)||"").trim().toLowerCase();if(l||f||b||E||h){let q=!1;if(p.tipo==="salida"&&p.obras&&Array.isArray(p.obras)){if(q=p.obras.some(k=>{const L=(k.codigo||"").toString().toLowerCase(),A=(k.nombre||"").toString().toLowerCase(),T=(k.cod_cliente||"").toString().toLowerCase(),P=(k.cliente||"").toString().toLowerCase(),I=!l||L.includes(l),J=!f||A.includes(f),U=!b||T.includes(b),W=!E||P.includes(E);return I&&J&&U&&W}),h&&p.planillas_codigos&&Array.isArray(p.planillas_codigos)){const k=p.planillas_codigos.some(L=>(L||"").toString().toLowerCase().includes(h));q=q&&k}}else{const k=(p.cod_obra||"").toString().toLowerCase(),L=(p.nombre_obra||c.event.title||"").toString().toLowerCase(),A=(p.cod_cliente||"").toString().toLowerCase(),T=(p.cliente||"").toString().toLowerCase(),P=!l||k.includes(l),I=!f||L.includes(f),J=!b||A.includes(b),U=!E||T.includes(E);let W=!0;h&&(p.planillas_codigos&&Array.isArray(p.planillas_codigos)?W=p.planillas_codigos.some(Ce=>(Ce||"").toString().toLowerCase().includes(h)):W=(c.event.title||"").toLowerCase().includes(h)),q=P&&I&&J&&U&&W}q?(c.el.classList.add("evento-filtrado"),c.el.classList.remove("evento-atenuado")):(c.el.classList.add("evento-atenuado"),c.el.classList.remove("evento-filtrado"))}else c.el.classList.remove("evento-filtrado"),c.el.classList.remove("evento-atenuado");typeof se=="function"&&se(c),typeof ce=="function"&&ce(c,x)},eventAllow:(c,p)=>{var f;const l=(f=p.extendedProps)==null?void 0:f.tipo;return!(l==="resumen-dia"||l==="festivo")},snapDuration:"00:30:00",eventDragStart:c=>{var $;window._isDragging=!0,window._draggedEvent=c.event,st(c.event,c.el),document.body.classList.add("fc-dragging-active");const p=nt(),l=w=>{w.dataTransfer&&window._isDragging&&w.dataTransfer.setDragImage(p,0,0)};document.addEventListener("dragstart",l,!0),window._nativeDragStartHandler=l;const f=document.getElementById("calendario");(($=x==null?void 0:x.view)==null?void 0:$.type)==="resourceTimeGridDay"&&pe(!0);const b=(w,C)=>{const y=document.elementsFromPoint(w,C);for(const q of y){const k=q.closest(".fc-daygrid-day");if(k)return k.getAttribute("data-date");const L=q.closest("[data-date]");if(L)return L.getAttribute("data-date")}return null};let E=!1;const h=w=>{!window._isDragging||E||(E=!0,requestAnimationFrame(()=>{if(E=!1,!window._isDragging)return;const C=ue(w.clientY,f),y=b(w.clientX,w.clientY);de(w.clientX,w.clientY,C,y)}))};if(document.addEventListener("mousemove",h,{passive:!0}),window._dragMouseMoveHandler=h,c.jsEvent){const w=ue(c.jsEvent.clientY,f),C=b(c.jsEvent.clientX,c.jsEvent.clientY);de(c.jsEvent.clientX,c.jsEvent.clientY,w,C)}window._dragOriginalStart=c.event.start,window._dragOriginalEnd=c.event.end,window._dragEventId=c.event.id;const S=w=>{if(window._isDragging){w.preventDefault(),w.stopPropagation(),w.stopImmediatePropagation(),window._cancelDrag=!0,te();const C=new PointerEvent("pointerup",{bubbles:!0,cancelable:!0,clientX:w.clientX,clientY:w.clientY});document.dispatchEvent(C)}};document.addEventListener("contextmenu",S,{capture:!0}),window._dragContextMenuHandler=S},eventDragStop:c=>{window._isDragging=!1,window._draggedEvent=null,window._nativeDragStartHandler&&(document.removeEventListener("dragstart",window._nativeDragStartHandler,!0),window._nativeDragStartHandler=null),window._dragMouseMoveHandler&&(document.removeEventListener("mousemove",window._dragMouseMoveHandler),window._dragMouseMoveHandler=null),window._dragContextMenuHandler&&(document.removeEventListener("contextmenu",window._dragContextMenuHandler,{capture:!0}),window._dragContextMenuHandler=null),window._dragOriginalStart=null,window._dragOriginalEnd=null,window._dragEventId=null,te(),document.body.classList.remove("fc-dragging-active"),pe(!1)},eventDrop:c=>{var h,S,$,w;if(window._cancelDrag){window._cancelDrag=!1,c.revert(),window._dragOriginalStart&&(c.event.setStart(window._dragOriginalStart),window._dragOriginalEnd&&c.event.setEnd(window._dragOriginalEnd));return}const p=c.event.extendedProps||{},l=c.event.id,f=(h=c.event.start)==null?void 0:h.toISOString(),b={fecha:f,tipo:p.tipo,planillas_ids:p.planillas_ids||[],elementos_ids:p.elementos_ids||[]},E=((($=(S=window.AppSalidas)==null?void 0:S.routes)==null?void 0:$.updateItem)||"").replace("__ID__",l);Swal.fire({title:"Actualizando fecha...",html:"Verificando programación de fabricación",allowOutsideClick:!1,allowEscapeKey:!1,showConfirmButton:!1,didOpen:()=>{Swal.showLoading()}}),fetch(E,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(w=window.AppSalidas)==null?void 0:w.csrf},body:JSON.stringify(b)}).then(C=>{if(!C.ok)throw new Error("No se pudo actualizar la fecha.");return C.json()}).then(async C=>{if(Swal.close(),M(),x.refetchEvents(),x.refetchResources(),F(),C.alerta_retraso){const y=C.alerta_retraso.es_elementos_con_fecha_propia||!1,q=y?"elementos":"planilla";Swal.fire({icon:"warning",title:"⚠️ Fecha de entrega adelantada",html:`
                                    <div class="text-left">
                                        <p class="mb-2">${C.alerta_retraso.mensaje}</p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fin fabricación:</strong> ${C.alerta_retraso.fin_programado}</p>
                                            <p class="text-sm"><strong>Fecha entrega:</strong> ${C.alerta_retraso.fecha_entrega}</p>
                                        </div>
                                        <p class="mt-3 text-sm text-gray-600">Los ${q==="elementos"?"elementos":"elementos de la planilla"} no estarán listos para la fecha indicada.</p>
                                    </div>
                                `,showCancelButton:!0,confirmButtonText:"🚀 Adelantar fabricación",cancelButtonText:"Entendido",confirmButtonColor:"#10b981",cancelButtonColor:"#f59e0b"}).then(k=>{k.isConfirmed&&lt(C.alerta_retraso.elementos_ids||p.elementos_ids,f,y)})}if(C.opcion_posponer){const y=C.opcion_posponer.es_elementos_con_fecha_propia||!1,q=C.opcion_posponer.ordenes_afectadas||[],k=y?"Elementos con fecha propia":"Planilla";let L="";q.length>0&&(L=`
                                    <div class="max-h-40 overflow-y-auto mt-3">
                                        <table class="w-full text-sm border">
                                            <thead class="bg-blue-100">
                                                <tr>
                                                    <th class="px-2 py-1 text-left">Planilla</th>
                                                    <th class="px-2 py-1 text-left">Máquina</th>
                                                    <th class="px-2 py-1 text-center">Posición</th>
                                                    ${y?'<th class="px-2 py-1 text-center">Elementos</th>':""}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${q.map(A=>`
                                                    <tr class="border-t">
                                                        <td class="px-2 py-1">${A.planilla_codigo}</td>
                                                        <td class="px-2 py-1">${A.maquina_nombre}</td>
                                                        <td class="px-2 py-1 text-center">${A.posicion_actual} / ${A.total_posiciones}</td>
                                                        ${y?`<td class="px-2 py-1 text-center">${A.elementos_count||"-"}</td>`:""}
                                                    </tr>
                                                `).join("")}
                                            </tbody>
                                        </table>
                                    </div>
                                `),Swal.fire({icon:"question",title:`📅 ${k} - Fecha pospuesta`,html:`
                                    <div class="text-left">
                                        <p class="mb-2">${C.opcion_posponer.mensaje}</p>
                                        <div class="bg-blue-50 border border-blue-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fecha anterior:</strong> ${C.opcion_posponer.fecha_anterior}</p>
                                            <p class="text-sm"><strong>Nueva fecha:</strong> ${C.opcion_posponer.fecha_nueva}</p>
                                        </div>
                                        ${L}
                                        <p class="mt-3 text-sm text-gray-600">Al retrasar la fabricación, otras planillas más urgentes podrán avanzar en la cola.</p>
                                    </div>
                                `,showCancelButton:!0,confirmButtonText:"⏬ Retrasar fabricación",cancelButtonText:"No, mantener posición",confirmButtonColor:"#3b82f6",cancelButtonColor:"#6b7280"}).then(A=>{A.isConfirmed&&dt(C.opcion_posponer.elementos_ids,y,f)})}}).catch(C=>{Swal.close(),console.error("Error:",C),Swal.fire({icon:"error",title:"Error",text:"No se pudo actualizar la fecha.",timer:3e3}),c.revert()})},dateClick:c=>{s(c.dateStr)&&Swal.fire({icon:"info",title:"📅 Día festivo",text:"Los festivos se editan en la planificación de Trabajadores.",confirmButtonText:"Entendido"})},eventMinHeight:30,firstDay:1,slotLabelContent:c=>{var $,w;if((($=x==null?void 0:x.view)==null?void 0:$.type)!=="resourceTimelineWeek")return null;const l=c.date;if(!l)return null;const f=l.getDay(),b=f===0||f===6,E=l.toISOString().split("T")[0],h={weekday:"short",day:"numeric",month:"short"},S=l.toLocaleDateString("es-ES",h);if(b){const y=!((w=window.expandedWeekendDays)==null?void 0:w.has(E)),q=y?"▶":"▼",k=y?l.toLocaleDateString("es-ES",{weekday:"short"}).substring(0,3):S;return{html:`<div class="weekend-header cursor-pointer select-none hover:bg-gray-200 px-1 rounded"
                                    data-date="${E}"
                                    data-collapsed="${y}"
                                    title="${y?"Clic para expandir":"Clic para colapsar"}">
                                <span class="collapse-icon text-xs mr-1">${q}</span>
                                <span class="weekend-label">${k}</span>
                               </div>`}}return{html:`<span>${S}</span>`}},views:{resourceTimelineWeek:{slotDuration:{days:1}},resourceTimeGridDay:{slotDuration:"01:00:00",slotLabelFormat:{hour:"2-digit",minute:"2-digit",hour12:!1},slotLabelInterval:"01:00:00",allDaySlot:!1}},editable:!0,eventDurationEditable:!1,eventStartEditable:!0,resourceAreaWidth:"15%",resourceAreaHeaderContent:"Obras",resourceOrder:"orderIndex",resourceLabelContent:c=>({html:`<div class="text-xs font-semibold">
                        <div class="text-blue-600">${c.resource.extendedProps.cod_obra||""}</div>
                        <div class="text-gray-700 truncate">${c.resource.title||""}</div>
                        <div class="text-gray-500 text-[10px] truncate">${c.resource.extendedProps.cliente||""}</div>
                    </div>`}),windowResize:()=>F()}),x.render(),F(),at(x),setTimeout(()=>{const c=document.getElementById("calendario-loading");c&&!c.classList.contains("opacity-0")&&(c.classList.add("opacity-0","pointer-events-none"),c.classList.remove("opacity-100"))},500);const u=localStorage.getItem("expandedWeekendDays");window.expandedWeekendDays=new Set(u?JSON.parse(u):[]),window.weekendDefaultCollapsed=!0;function m(c){const l=new Date(c+"T00:00:00").getDay();return l===0||l===6}function g(){var p,l,f;const c=(p=x==null?void 0:x.view)==null?void 0:p.type;if(c==="resourceTimelineWeek"&&(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(h=>{var $;const S=h.getAttribute("data-date");m(S)&&((($=window.expandedWeekendDays)==null?void 0:$.has(S))?h.classList.remove("weekend-collapsed"):h.classList.add("weekend-collapsed"))}),document.querySelectorAll(".fc-timeline-lane td[data-date]").forEach(h=>{var $;const S=h.getAttribute("data-date");m(S)&&((($=window.expandedWeekendDays)==null?void 0:$.has(S))?h.classList.remove("weekend-collapsed"):h.classList.add("weekend-collapsed"))})),c==="dayGridMonth"){const b=(l=window.expandedWeekendDays)==null?void 0:l.has("saturday"),E=(f=window.expandedWeekendDays)==null?void 0:f.has("sunday"),h=document.querySelectorAll(".fc-col-header-cell.fc-day-sat"),S=document.querySelectorAll(".fc-col-header-cell.fc-day-sun");h.forEach(w=>{b?w.classList.remove("weekend-day-collapsed"):w.classList.add("weekend-day-collapsed")}),S.forEach(w=>{E?w.classList.remove("weekend-day-collapsed"):w.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sat").forEach(w=>{b?w.classList.remove("weekend-day-collapsed"):w.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sun").forEach(w=>{E?w.classList.remove("weekend-day-collapsed"):w.classList.add("weekend-day-collapsed")});const $=document.querySelector(".fc-dayGridMonth-view table");if($){let w=$.querySelector("colgroup");if(!w){w=document.createElement("colgroup");for(let y=0;y<7;y++)w.appendChild(document.createElement("col"));$.insertBefore(w,$.firstChild)}const C=w.querySelectorAll("col");C.length>=7&&(C[5].style.width=b?"":"40px",C[6].style.width=E?"":"40px")}}}function v(c){window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(c)?window.expandedWeekendDays.delete(c):window.expandedWeekendDays.add(c),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),g()}n.addEventListener("click",c=>{var f;const p=c.target.closest(".weekend-header");if(p){const b=p.getAttribute("data-date");if(b){c.preventDefault(),c.stopPropagation(),v(b);return}}if(((f=x==null?void 0:x.view)==null?void 0:f.type)==="dayGridMonth"){const b=c.target.closest(".fc-col-header-cell.fc-day-sat, .fc-col-header-cell.fc-day-sun");if(b){c.preventDefault(),c.stopPropagation();const S=b.classList.contains("fc-day-sat")?"saturday":"sunday";v(S);return}const E=c.target.closest(".fc-daygrid-day.fc-day-sat, .fc-daygrid-day.fc-day-sun");if(E&&!c.target.closest(".fc-event")){c.preventDefault(),c.stopPropagation();const S=E.classList.contains("fc-day-sat")?"saturday":"sunday";v(S);return}}},!0),setTimeout(()=>g(),100),window.applyWeekendCollapse=g,n.addEventListener("contextmenu",c=>{if(window._isDragging||window._cancelDrag)return;const p=c.target.closest(".fc-daygrid-day, .fc-timeline-slot, .fc-timegrid-slot, .fc-col-header-cell");if(p){let l=p.getAttribute("data-date");if(!l){const f=c.target.closest("[data-date]");f&&(l=f.getAttribute("data-date"))}if(l&&x){const f=x.view.type;(f==="resourceTimelineWeek"||f==="dayGridMonth")&&(c.preventDefault(),c.stopPropagation(),Swal.fire({title:"📅 Ir a día",text:`¿Quieres ver el día ${l}?`,icon:"question",showCancelButton:!0,confirmButtonText:"Sí, ir al día",cancelButtonText:"Cancelar"}).then(b=>{b.isConfirmed&&(x.changeView("resourceTimeGridDay",l),F())}))}}})}),window.addEventListener("shown.bs.tab",F),window.addEventListener("shown.bs.collapse",F),window.addEventListener("shown.bs.modal",F);function d(){document.querySelectorAll(".resumen-diario-custom").forEach(m=>m.remove())}function r(){if(!x||x.view.type!=="resourceTimeGridDay"){d();return}d();const u=x.getDate(),m=u.getFullYear(),g=String(u.getMonth()+1).padStart(2,"0"),v=String(u.getDate()).padStart(2,"0"),c=`${m}-${g}-${v}`,p=x.getEvents().find(l=>{var f,b;return((f=l.extendedProps)==null?void 0:f.tipo)==="resumen-dia"&&((b=l.extendedProps)==null?void 0:b.fecha)===c});if(p&&p.extendedProps){const l=Number(p.extendedProps.pesoTotal||0).toLocaleString(),f=Number(p.extendedProps.longitudTotal||0).toLocaleString(),b=p.extendedProps.diametroMedio?Number(p.extendedProps.diametroMedio).toFixed(2):null,E=document.createElement("div");E.className="resumen-diario-custom",E.innerHTML=`
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 Peso: ${l} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${f} m</div>
                        ${b?`<div class="text-yellow-800">⌀ Diámetro: ${b} mm</div>`:""}
                    </div>
                </div>
            `,n&&n.parentNode&&n.parentNode.insertBefore(E,n)}}return window.mostrarResumenDiario=r,window.limpiarResumenesCustom=d,x}function it(e){if(e.view.type==="dayGridMonth"){const t=new Date(e.start);return t.setDate(t.getDate()+15),t.toISOString().split("T")[0]}if(e.view.type==="resourceTimeGridWeek"||e.view.type==="resourceTimelineWeek"){const t=new Date(e.start),a=Math.floor((e.end-e.start)/(1e3*60*60*24)/2);return t.setDate(t.getDate()+a),t.toISOString().split("T")[0]}return e.startStr.split("T")[0]}function lt(e,t,a=!1){var s;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para adelantar"});return}if(a){ct(e,t);return}Swal.fire({title:"Analizando opciones...",html:"Calculando la mejor posición para adelantar la fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const o=new AbortController,n=setTimeout(()=>o.abort(),6e4);fetch("/planificacion/simular-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf},body:JSON.stringify({elementos_ids:e,fecha_entrega:t}),signal:o.signal}).then(i=>{if(clearTimeout(n),!i.ok)throw new Error("Error en la simulación");return i.json()}).then(i=>{if(!i.necesita_adelanto){const m=(i.mensaje||"Los elementos llegarán a tiempo.").replace(/\n/g,"<br>").replace(/•/g,'<span class="text-amber-600">•</span>');i.razones&&i.razones.length>0&&i.razones.some(c=>c.fin_minimo)?Swal.fire({icon:"warning",title:"No se puede entregar a tiempo",html:`
                            <div class="text-left text-sm mb-4">${m}</div>
                            <div class="text-left text-sm font-semibold text-amber-700 border-t pt-3">
                                ¿Deseas adelantar a primera posición de todas formas?
                            </div>
                        `,width:650,showCancelButton:!0,confirmButtonText:"Sí, adelantar a 1ª posición",cancelButtonText:"Cancelar",confirmButtonColor:"#f59e0b",cancelButtonColor:"#6b7280"}).then(c=>{if(c.isConfirmed){const p=[];i.razones.filter(l=>l.fin_minimo).forEach(l=>{l.planillas_ids&&l.planillas_ids.length>0?l.planillas_ids.forEach(f=>{p.push({planilla_id:f,maquina_id:l.maquina_id,posicion_nueva:1})}):l.planilla_id&&p.push({planilla_id:l.planilla_id,maquina_id:l.maquina_id,posicion_nueva:1})}),p.length>0?(console.log("Órdenes a adelantar:",p),me(p)):(console.warn("No se encontraron órdenes para adelantar",i.razones),Swal.fire({icon:"warning",title:"Sin órdenes",text:"No se encontraron órdenes para adelantar."}))}}):Swal.fire({icon:"info",title:"No es necesario adelantar",html:`<div class="text-left text-sm">${m}</div>`,width:600});return}let d="";i.ordenes_a_adelantar&&i.ordenes_a_adelantar.length>0&&(d=`
                    <div class="mb-4">
                        <h4 class="font-semibold text-green-700 mb-2">📋 Planillas a adelantar:</h4>
                        <div class="max-h-40 overflow-y-auto">
                            <table class="w-full text-sm border">
                                <thead class="bg-green-100">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Planilla</th>
                                        <th class="px-2 py-1 text-left">Máquina</th>
                                        <th class="px-2 py-1 text-center">Pos. Actual</th>
                                        <th class="px-2 py-1 text-center">Nueva Pos.</th>
                                    </tr>
                                </thead>
                                <tbody>
                `,i.ordenes_a_adelantar.forEach(m=>{d+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${m.planilla_codigo}</td>
                            <td class="px-2 py-1">${m.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${m.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${m.posicion_nueva}</td>
                        </tr>
                    `}),d+=`
                                </tbody>
                            </table>
                        </div>
                    </div>
                `);let r="";i.colaterales&&i.colaterales.length>0&&(r=`
                    <div class="mb-4">
                        <h4 class="font-semibold text-orange-700 mb-2">⚠️ Planillas que se retrasarán:</h4>
                        <div class="max-h-32 overflow-y-auto bg-orange-50 border border-orange-200 rounded p-2">
                            <table class="w-full text-sm">
                                <thead class="bg-orange-100">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Planilla</th>
                                        <th class="px-2 py-1 text-left">Obra</th>
                                        <th class="px-2 py-1 text-left">F. Entrega</th>
                                    </tr>
                                </thead>
                                <tbody>
                `,i.colaterales.forEach(m=>{r+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${m.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${m.obra}</td>
                            <td class="px-2 py-1">${m.fecha_entrega}</td>
                        </tr>
                    `}),r+=`
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-orange-600 mt-1">Estas planillas bajarán una posición en la cola de fabricación.</p>
                    </div>
                `);const u=i.fecha_entrega||"---";Swal.fire({icon:"question",title:"🚀 Adelantar fabricación",html:`
                    <div class="text-left">
                        <p class="mb-3">Para cumplir con la fecha de entrega <strong>${u}</strong>, se propone el siguiente cambio:</p>
                        ${d}
                        ${r}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el adelanto?</p>
                    </div>
                `,width:600,showCancelButton:!0,confirmButtonText:"✅ Ejecutar adelanto",cancelButtonText:"Cancelar",confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280"}).then(m=>{m.isConfirmed&&me(i.ordenes_a_adelantar)})}).catch(i=>{clearTimeout(n),console.error("Error en simulación:",i);const d=i.name==="AbortError";Swal.fire({icon:"error",title:d?"Tiempo agotado":"Error",text:d?"El cálculo está tardando demasiado. La operación fue cancelada.":"No se pudo simular el adelanto. "+i.message})})}function me(e){var a;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay órdenes para adelantar"});return}Swal.fire({title:"Ejecutando adelanto...",html:"Actualizando posiciones en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const t=e.map(o=>({planilla_id:o.planilla_id,maquina_id:o.maquina_id,posicion_nueva:o.posicion_nueva}));console.log("Enviando órdenes al servidor:",JSON.stringify({ordenes:t},null,2)),fetch("/planificacion/ejecutar-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(a=window.AppSalidas)==null?void 0:a.csrf},body:JSON.stringify({ordenes:t})}).then(o=>{if(!o.ok)throw new Error("Error al ejecutar el adelanto");return o.json()}).then(o=>{if(console.log("Respuesta del servidor:",o),o.success){const n=o.resultados||[],s=n.filter(r=>r.success),i=n.filter(r=>!r.success);let d=o.mensaje||"Las posiciones han sido actualizadas.";s.length>0&&(d+=`<br><br><strong>${s.length} orden(es) movidas correctamente.</strong>`),i.length>0&&(d+=`<br><span class="text-amber-600">${i.length} orden(es) no pudieron moverse:</span>`,d+="<ul class='text-left text-sm mt-2'>",i.forEach(r=>{d+=`<li>• Planilla ${r.planilla_id}: ${r.mensaje}</li>`}),d+="</ul>"),Swal.fire({icon:s.length>0?"success":"warning",title:s.length>0?"¡Adelanto ejecutado!":"Problemas al adelantar",html:d,confirmButtonColor:"#10b981"}).then(()=>{x&&(M(),x.refetchEvents(),x.refetchResources())})}else Swal.fire({icon:"error",title:"Error",text:o.mensaje||"No se pudo ejecutar el adelanto."})}).catch(o=>{console.error("Error al ejecutar adelanto:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el adelanto. "+o.message})})}function ct(e,t){var a;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para adelantar"});return}Swal.fire({title:"Ejecutando adelanto...",html:"Separando elementos y actualizando posiciones en la cola",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}}),fetch("/planificacion/ejecutar-adelanto-elementos",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(a=window.AppSalidas)==null?void 0:a.csrf},body:JSON.stringify({elementos_ids:e,nueva_fecha_entrega:t})}).then(o=>{if(!o.ok)throw new Error("Error al ejecutar el adelanto");return o.json()}).then(o=>{if(o.success){const n=o.resultados||[],s=n.filter(r=>r.success),i=n.filter(r=>!r.success);let d=o.mensaje||"Las posiciones han sido actualizadas.";s.length>0&&(d+=`<br><br><strong>${s.length} orden(es) de elementos adelantadas.</strong>`),i.length>0&&(d+=`<br><span class="text-amber-600">${i.length} orden(es) no pudieron moverse.</span>`),Swal.fire({icon:s.length>0?"success":"warning",title:s.length>0?"¡Adelanto ejecutado!":"Problemas al adelantar",html:d,confirmButtonColor:"#10b981"}).then(()=>{x&&(M(),x.refetchEvents(),x.refetchResources())})}else Swal.fire({icon:"error",title:"Error",text:o.mensaje||"No se pudo ejecutar el adelanto."})}).catch(o=>{console.error("Error al ejecutar adelanto de elementos:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el adelanto. "+o.message})})}function dt(e,t=!1,a=null){var o;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para retrasar"});return}Swal.fire({title:"Analizando...",html:"Calculando el impacto del retraso en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}}),fetch("/planificacion/simular-retraso",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(o=window.AppSalidas)==null?void 0:o.csrf},body:JSON.stringify({elementos_ids:e,es_elementos_con_fecha_propia:t})}).then(n=>{if(!n.ok)throw new Error("Error en la simulación");return n.json()}).then(n=>{if(!n.puede_retrasar){Swal.fire({icon:"info",title:"No se puede retrasar",text:n.mensaje||"Las planillas ya están al final de la cola."});return}let s="";n.ordenes_a_retrasar&&n.ordenes_a_retrasar.length>0&&(s=`
                    <div class="mb-4">
                        <h4 class="font-semibold text-blue-700 mb-2">📋 Planillas a retrasar:</h4>
                        <div class="max-h-40 overflow-y-auto">
                            <table class="w-full text-sm border">
                                <thead class="bg-blue-100">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Planilla</th>
                                        <th class="px-2 py-1 text-left">Máquina</th>
                                        <th class="px-2 py-1 text-center">Pos. Actual</th>
                                        <th class="px-2 py-1 text-center">Nueva Pos.</th>
                                    </tr>
                                </thead>
                                <tbody>
                `,n.ordenes_a_retrasar.forEach(r=>{s+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${r.planilla_codigo}</td>
                            <td class="px-2 py-1">${r.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${r.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-blue-600">${r.posicion_nueva}</td>
                        </tr>
                    `}),s+=`
                                </tbody>
                            </table>
                        </div>
                    </div>
                `);let i="";n.beneficiados&&n.beneficiados.length>0&&(i=`
                    <div class="mb-4">
                        <h4 class="font-semibold text-green-700 mb-2">✅ Planillas que avanzarán:</h4>
                        <div class="max-h-32 overflow-y-auto bg-green-50 border border-green-200 rounded p-2">
                            <table class="w-full text-sm">
                                <thead class="bg-green-100">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Planilla</th>
                                        <th class="px-2 py-1 text-left">Obra</th>
                                        <th class="px-2 py-1 text-center">Nueva Pos.</th>
                                    </tr>
                                </thead>
                                <tbody>
                `,n.beneficiados.slice(0,10).forEach(r=>{i+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${r.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${r.obra}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${r.posicion_nueva}</td>
                        </tr>
                    `}),i+=`
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-green-600 mt-1">Estas planillas subirán una posición en la cola.</p>
                    </div>
                `);const d=n.es_elementos_con_fecha_propia?"⏬ Retrasar fabricación (Elementos)":"⏬ Retrasar fabricación";Swal.fire({icon:"question",title:d,html:`
                    <div class="text-left">
                        <p class="mb-3">${n.mensaje}</p>
                        ${s}
                        ${i}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el retraso?</p>
                    </div>
                `,width:600,showCancelButton:!0,confirmButtonText:"✅ Ejecutar retraso",cancelButtonText:"Cancelar",confirmButtonColor:"#3b82f6",cancelButtonColor:"#6b7280"}).then(r=>{r.isConfirmed&&ut(e,t,a)})}).catch(n=>{console.error("Error en simulación de retraso:",n),Swal.fire({icon:"error",title:"Error",text:"No se pudo simular el retraso. "+n.message})})}function ut(e,t=!1,a=null){var n;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para retrasar"});return}Swal.fire({title:"Ejecutando retraso...",html:"Actualizando posiciones en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const o={elementos_ids:e,es_elementos_con_fecha_propia:t};t&&a&&(o.nueva_fecha_entrega=a),fetch("/planificacion/ejecutar-retraso",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(n=window.AppSalidas)==null?void 0:n.csrf},body:JSON.stringify(o)}).then(s=>{if(!s.ok)throw new Error("Error al ejecutar el retraso");return s.json()}).then(s=>{if(s.success){const i=s.resultados||[],d=i.filter(m=>m.success),r=i.filter(m=>!m.success);let u=s.mensaje||"Las posiciones han sido actualizadas.";d.length>0&&(u+=`<br><br><strong>${d.length} planilla(s) movidas al final de la cola.</strong>`),r.length>0&&(u+=`<br><span class="text-amber-600">${r.length} orden(es) no pudieron moverse:</span>`,u+="<ul class='text-left text-sm mt-2'>",r.forEach(m=>{u+=`<li>• Planilla ${m.planilla_id}: ${m.mensaje}</li>`}),u+="</ul>"),Swal.fire({icon:d.length>0?"success":"warning",title:d.length>0?"¡Retraso ejecutado!":"Problemas al retrasar",html:u,confirmButtonColor:"#3b82f6"}).then(()=>{x&&(M(),x.refetchEvents(),x.refetchResources())})}else Swal.fire({icon:"error",title:"Error",text:s.mensaje||"No se pudo ejecutar el retraso."})}).catch(s=>{console.error("Error al ejecutar retraso:",s),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el retraso. "+s.message})})}function pt(e,t={}){const{selector:a=null,once:o=!1}=t;let n=!1;const s=()=>{a&&!document.querySelector(a)||o&&n||(n=!0,e())};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",s):s(),document.addEventListener("livewire:navigated",s)}function mt(e){document.addEventListener("livewire:navigating",e)}function ft(e){let a=new Date(e).toLocaleDateString("es-ES",{month:"long",year:"numeric"});return`(${a.charAt(0).toUpperCase()+a.slice(1)})`}function gt(e){const t=new Date(e),a=t.getDay(),o=a===0?-6:1-a,n=new Date(t);n.setDate(t.getDate()+o);const s=new Date(n);s.setDate(n.getDate()+6);const i=new Intl.DateTimeFormat("es-ES",{day:"2-digit",month:"short"}),d=new Intl.DateTimeFormat("es-ES",{year:"numeric"});return`(${i.format(n)} – ${i.format(s)} ${d.format(s)})`}function yt(e){var s,i;const t=document.querySelector("#resumen-semanal-fecha"),a=document.querySelector("#resumen-mensual-fecha");t&&(t.textContent=gt(e)),a&&(a.textContent=ft(e));const o=(i=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:i.totales;if(!o)return;const n=`${o}?fecha=${encodeURIComponent(e)}`;fetch(n).then(d=>d.json()).then(d=>{const r=d.semana||{},u=d.mes||{};document.querySelector("#resumen-semanal-peso").textContent=`📦 ${Number(r.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-semanal-longitud").textContent=`📏 ${Number(r.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-semanal-diametro").textContent=r.diametro!=null?`⌀ ${Number(r.diametro).toFixed(2)} mm`:"",document.querySelector("#resumen-mensual-peso").textContent=`📦 ${Number(u.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-mensual-longitud").textContent=`📏 ${Number(u.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-mensual-diametro").textContent=u.diametro!=null?`⌀ ${Number(u.diametro).toFixed(2)} mm`:""}).catch(d=>console.error("❌ Totales:",d))}let B;function ht(){var b,E;if(window.calendar)try{window.calendar.destroy()}catch(h){console.warn("Error al destruir calendario anterior:",h)}const e=rt();B=e,window.calendar=e,e.refetchResources(),e.refetchEvents(),(b=document.getElementById("ver-con-salidas"))==null||b.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()}),(E=document.getElementById("ver-todas"))==null||E.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()});const a=(localStorage.getItem("fechaCalendario")||new Date().toISOString()).split("T")[0];yt(a);const o=localStorage.getItem("soloSalidas")==="true",n=localStorage.getItem("soloPlanillas")==="true",s=document.getElementById("solo-salidas"),i=document.getElementById("solo-planillas");s&&(s.checked=o),i&&(i.checked=n);const d=document.getElementById("filtro-obra"),r=document.getElementById("filtro-nombre-obra"),u=document.getElementById("filtro-cod-cliente"),m=document.getElementById("filtro-cliente"),g=document.getElementById("filtro-cod-planilla"),v=document.getElementById("btn-reset-filtros"),c=document.getElementById("btn-limpiar-filtros");v==null||v.addEventListener("click",()=>{d&&(d.value=""),r&&(r.value=""),u&&(u.value=""),m&&(m.value=""),g&&(g.value=""),s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),i&&(i.checked=!1,localStorage.setItem("soloPlanillas","false")),f(),document.querySelectorAll(".fc-event.evento-filtrado, .fc-event.evento-atenuado").forEach(h=>{h.classList.remove("evento-filtrado","evento-atenuado")}),M(),B.refetchEvents()});const l=((h,S=150)=>{let $;return(...w)=>{clearTimeout($),$=setTimeout(()=>h(...w),S)}})(()=>{B.refetchEvents()},120);d==null||d.addEventListener("input",l),r==null||r.addEventListener("input",l),u==null||u.addEventListener("input",l),m==null||m.addEventListener("input",l),g==null||g.addEventListener("input",l);function f(){const h=s==null?void 0:s.closest(".checkbox-container"),S=i==null?void 0:i.closest(".checkbox-container");h==null||h.classList.remove("active-salidas"),S==null||S.classList.remove("active-planillas"),s!=null&&s.checked&&(h==null||h.classList.add("active-salidas")),i!=null&&i.checked&&(S==null||S.classList.add("active-planillas"))}s==null||s.addEventListener("change",h=>{h.target.checked&&i&&(i.checked=!1,localStorage.setItem("soloPlanillas","false")),localStorage.setItem("soloSalidas",h.target.checked.toString()),f(),B.refetchEvents()}),i==null||i.addEventListener("change",h=>{h.target.checked&&s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),localStorage.setItem("soloPlanillas",h.target.checked.toString()),f(),B.refetchEvents()}),f(),c==null||c.addEventListener("click",()=>{d&&(d.value=""),r&&(r.value=""),u&&(u.value=""),m&&(m.value=""),g&&(g.value=""),document.querySelectorAll(".fc-event.evento-filtrado, .fc-event.evento-atenuado").forEach(h=>{h.classList.remove("evento-filtrado","evento-atenuado")}),M(),B.refetchEvents()})}let R=null,j=null,ae="days",N=-1,H=[];function vt(){j&&j();const e=window.calendar;if(!e)return;R=e.getDate(),ae="days",N=-1,St();function t(a){var i;const o=a.target.tagName.toLowerCase();if(o==="input"||o==="textarea"||a.target.isContentEditable||document.querySelector(".swal2-container"))return;const n=window.calendar;if(!n)return;let s=!1;switch(a.key){case"ArrowLeft":n.prev(),s=!0;break;case"ArrowRight":n.next(),s=!0;break;case"t":case"T":n.today(),s=!0;break;case"Escape":window.isFullScreen&&((i=window.toggleFullScreen)==null||i.call(window),s=!0);break}s&&(a.preventDefault(),a.stopPropagation())}document.addEventListener("keydown",t,!0),e.on("eventsSet",()=>{ae==="events"&&(bt(),wt())}),j=()=>{document.removeEventListener("keydown",t,!0),Ee(),Se()}}function bt(){const e=window.calendar;if(!e){H=[];return}H=e.getEvents().filter(t=>{var o;const a=(o=t.extendedProps)==null?void 0:o.tipo;return a!=="resumen-dia"&&a!=="festivo"}).sort((t,a)=>{const o=t.start||new Date(0),n=a.start||new Date(0);return o<n?-1:o>n?1:(t.title||"").localeCompare(a.title||"")})}function wt(){var a;if(Se(),N<0||N>=H.length)return;const e=H[N];if(!e)return;const t=document.querySelector(`[data-event-id="${e.id}"]`)||document.querySelector(`.fc-event[data-event="${e.id}"]`);if(t)t.classList.add("keyboard-focused-event"),t.scrollIntoView({behavior:"smooth",block:"nearest"});else{const o=document.querySelectorAll(".fc-event");for(const n of o)if(n.textContent.includes((a=e.title)==null?void 0:a.substring(0,20))){n.classList.add("keyboard-focused-event"),n.scrollIntoView({behavior:"smooth",block:"nearest"});break}}e.start&&(R=new Date(e.start)),ke()}function Se(){document.querySelectorAll(".keyboard-focused-event").forEach(e=>{e.classList.remove("keyboard-focused-event")})}function xt(e){const t=e.getFullYear(),a=String(e.getMonth()+1).padStart(2,"0"),o=String(e.getDate()).padStart(2,"0");return`${t}-${a}-${o}`}function St(){if(Ee(),!R)return;const e=xt(R),t=window.calendar;if(!t)return;const a=t.view.type;let o=null;a==="dayGridMonth"?o=document.querySelector(`.fc-daygrid-day[data-date="${e}"]`):a==="resourceTimelineWeek"?(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(s=>{s.dataset.date&&s.dataset.date.startsWith(e)&&(o=s)}),o||(o=document.querySelector(`.fc-timeline-slot-lane[data-date^="${e}"]`))):a==="resourceTimeGridDay"&&(o=document.querySelector(".fc-col-header-cell")),o&&(o.classList.add("keyboard-focused-day"),o.scrollIntoView({behavior:"smooth",block:"nearest",inline:"nearest"})),ke()}function Ee(){document.querySelectorAll(".keyboard-focused-day").forEach(e=>{e.classList.remove("keyboard-focused-day")})}function ke(){let e=document.getElementById("keyboard-nav-indicator");if(e||(e=document.createElement("div"),e.id="keyboard-nav-indicator",e.className="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm",document.body.appendChild(e)),ae==="events"){const t=H[N],a=(t==null?void 0:t.title)||"Sin evento",o=`${N+1}/${H.length}`;e.innerHTML=`
            <div class="flex items-center gap-2">
                <span class="bg-green-500 text-white text-xs px-2 py-0.5 rounded">EVENTOS</span>
                <span class="font-medium truncate max-w-[200px]">${a}</span>
                <span class="text-gray-400">${o}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1 flex gap-3">
                <span>↑↓ Navegar</span>
                <span>Enter Abrir</span>
                <span>E Menú</span>
                <span>I Info</span>
                <span>Tab/Esc Días</span>
            </div>
        `}else{const t=R?R.toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short",year:"numeric"}):"";e.innerHTML=`
            <div class="flex items-center gap-2">
                <span class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded">DÍAS</span>
                <span class="opacity-75">📅</span>
                <span id="keyboard-nav-date">${t}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1 flex gap-3">
                <span>← → ↑ ↓</span>
                <span>Enter Vista día</span>
                <span>T Hoy</span>
                <span>Tab Eventos</span>
            </div>
        `}clearTimeout(e._hideTimeout),e.style.display="block",e._hideTimeout=setTimeout(()=>{e.style.display="none"},4e3)}function Et(){if(document.getElementById("keyboard-nav-styles"))return;const e=document.createElement("style");e.id="keyboard-nav-styles",e.textContent=`
        /* Foco en días */
        .keyboard-focused-day {
            outline: 3px solid #3b82f6 !important;
            outline-offset: -3px;
            background-color: rgba(59, 130, 246, 0.15) !important;
            position: relative;
            z-index: 5;
        }

        .keyboard-focused-day::after {
            content: '';
            position: absolute;
            inset: 0;
            border: 2px solid #3b82f6;
            pointer-events: none;
            animation: pulse-focus 1.5s ease-in-out infinite;
        }

        @keyframes pulse-focus {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Para vista timeline */
        .fc-timeline-slot.keyboard-focused-day,
        .fc-timeline-slot-lane.keyboard-focused-day {
            background-color: rgba(59, 130, 246, 0.2) !important;
        }

        /* Foco en eventos */
        .keyboard-focused-event {
            outline: 3px solid #22c55e !important;
            outline-offset: 2px;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.3), 0 4px 12px rgba(0, 0, 0, 0.3) !important;
            transform: scale(1.02);
            z-index: 100 !important;
            position: relative;
            transition: all 0.15s ease;
        }

        .keyboard-focused-event::before {
            content: '►';
            position: absolute;
            left: -20px;
            top: 50%;
            transform: translateY(-50%);
            color: #22c55e;
            font-size: 14px;
            animation: bounce-arrow 0.6s ease-in-out infinite;
        }

        @keyframes bounce-arrow {
            0%, 100% { transform: translateY(-50%) translateX(0); }
            50% { transform: translateY(-50%) translateX(3px); }
        }

        #keyboard-nav-indicator {
            transition: opacity 0.3s ease;
        }
    `,document.head.appendChild(e)}pt(()=>{ht(),Et(),setTimeout(()=>{vt()},500)},{selector:'#calendario[data-calendar-type="salidas"]'});mt(()=>{if(j&&(j(),j=null),window.calendar)try{window.calendar.destroy(),window.calendar=null}catch(t){console.warn("Error al limpiar calendario de salidas:",t)}const e=document.getElementById("keyboard-nav-indicator");e&&e.remove()});
