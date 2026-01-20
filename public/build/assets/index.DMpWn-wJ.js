let O=null,Y=null,G=null;async function oe(e,a){var n,s;const t=(s=(n=window.AppSalidas)==null?void 0:n.routes)==null?void 0:s.planificacion;if(!t)return{events:[],resources:[],totales:null};const o=`${e}|${a.startStr}|${a.endStr}`;return Y===o&&O?O:(G&&Y===o||(Y=o,G=(async()=>{try{const l=new URLSearchParams({tipo:"all",viewType:e||"",start:a.startStr||"",end:a.endStr||""}),c=await fetch(`${t}?${l.toString()}`);if(!c.ok)throw new Error(`HTTP ${c.status}`);const d=await c.json();return O={events:d.events||[],resources:d.resources||[],totales:d.totales||null},O}catch(l){return console.error("fetch all data falló:",l),O=null,{events:[],resources:[],totales:null}}finally{G=null}})()),G)}function z(){O=null,Y=null}function we(e){var l,c;const a=((l=document.getElementById("solo-salidas"))==null?void 0:l.checked)||!1,t=((c=document.getElementById("solo-planillas"))==null?void 0:c.checked)||!1,o=e.filter(d=>{var u;return((u=d.extendedProps)==null?void 0:u.tipo)==="resumen-dia"}),n=e.filter(d=>{var u;return((u=d.extendedProps)==null?void 0:u.tipo)!=="resumen-dia"});let s=n;return a&&!t?s=n.filter(d=>{var u;return((u=d.extendedProps)==null?void 0:u.tipo)==="salida"}):t&&!a&&(s=n.filter(d=>{var m;const u=(m=d.extendedProps)==null?void 0:m.tipo;return u==="planilla"||u==="festivo"})),[...s,...o]}async function Se(e,a){const t=await oe(e,a);return we(t.events)}async function Ee(e,a){return(await oe(e,a)).resources}async function ke(e,a){const t=await oe(e,a);if(!t.totales)return;const{semana:o,mes:n}=t.totales,s=b=>b!=null?Number(b).toLocaleString():"0",l=document.querySelector("#resumen-semanal-peso"),c=document.querySelector("#resumen-semanal-longitud"),d=document.querySelector("#resumen-semanal-diametro");l&&(l.textContent=`📦 ${s(o==null?void 0:o.peso)} kg`),c&&(c.textContent=`📏 ${s(o==null?void 0:o.longitud)} m`),d&&(d.textContent=o!=null&&o.diametro?`⌀ ${Number(o.diametro).toFixed(2)} mm`:"");const u=document.querySelector("#resumen-mensual-peso"),m=document.querySelector("#resumen-mensual-longitud"),v=document.querySelector("#resumen-mensual-diametro");if(u&&(u.textContent=`📦 ${s(n==null?void 0:n.peso)} kg`),m&&(m.textContent=`📏 ${s(n==null?void 0:n.longitud)} m`),v&&(v.textContent=n!=null&&n.diametro?`⌀ ${Number(n.diametro).toFixed(2)} mm`:""),a.startStr){const b=new Date(a.startStr),i={year:"numeric",month:"long"};let p=b.toLocaleDateString("es-ES",i);p=p.charAt(0).toUpperCase()+p.slice(1);const r=document.querySelector("#resumen-mensual-fecha");r&&(r.textContent=`(${p})`)}}function ne(e,a){const t=e.event.extendedProps||{};if(t.tipo!=="festivo"){if(t.tipo==="planilla"){const o=`
      ✅ Fabricados: ${Z(t.fabricadosKg)} kg<br>
      🔄 Fabricando: ${Z(t.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${Z(t.pendientesKg)} kg
    `;tippy(e.el,{content:o,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}t.tipo==="salida"&&t.comentario&&t.comentario.trim()&&tippy(e.el,{content:t.comentario,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}}function Z(e){return e!=null?Number(e).toLocaleString():0}let K=null;function P(){K&&(K.remove(),K=null,document.removeEventListener("click",P),document.removeEventListener("contextmenu",P,!0),document.removeEventListener("scroll",P,!0),window.removeEventListener("resize",P),window.removeEventListener("keydown",pe))}function pe(e){e.key==="Escape"&&P()}function $e(e,a,t){P();const o=document.createElement("div");o.className="fc-contextmenu",Object.assign(o.style,{position:"fixed",top:a+"px",left:e+"px",zIndex:99999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",fontFamily:"system-ui, -apple-system, Segoe UI, Roboto, sans-serif"}),o.innerHTML=t,document.body.appendChild(o),K=o;const n=o.getBoundingClientRect(),s=Math.max(0,n.right-window.innerWidth+8),l=Math.max(0,n.bottom-window.innerHeight+8);return(s||l)&&(o.style.left=Math.max(8,e-s)+"px",o.style.top=Math.max(8,a-l)+"px"),setTimeout(()=>{document.addEventListener("click",P),document.addEventListener("contextmenu",P,!0),document.addEventListener("scroll",P,!0),window.addEventListener("resize",P),window.addEventListener("keydown",pe)},0),o}function Ce(e,a,{headerHtml:t="",items:o=[]}={}){const n=`
    <div class="ctx-menu-container">
      ${t?`<div class="ctx-menu-header">${t}</div>`:""}
      ${o.map((l,c)=>`
        <button type="button"
          class="ctx-menu-item${l.danger?" ctx-menu-danger":""}"
          data-idx="${c}">
          ${l.icon?`<span class="ctx-menu-icon">${l.icon}</span>`:""}
          <span class="ctx-menu-label">${l.label}</span>
        </button>`).join("")}
    </div>
  `,s=$e(e,a,n);return s.querySelectorAll(".ctx-menu-item").forEach(l=>{l.addEventListener("click",async c=>{var m;c.preventDefault(),c.stopPropagation();const d=Number(l.dataset.idx),u=(m=o[d])==null?void 0:m.onClick;P();try{await(u==null?void 0:u())}catch(v){console.error(v)}})}),s}function qe(e){if(!e||typeof e!="string")return"";const a=e.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);if(a){const t=a[1],o=a[2].padStart(2,"0"),n=a[3].padStart(2,"0");return`${t}-${o}-${n}`}return e}(function(){if(document.getElementById("swal-anims"))return;const a=document.createElement("style");a.id="swal-anims",a.textContent=`
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
  `,document.head.appendChild(a)})();function Le(e){const a=e.extendedProps||{};if(Array.isArray(a.planillas_ids)&&a.planillas_ids.length)return a.planillas_ids;const t=(e.id||"").match(/planilla-(\d+)/);return t?[Number(t[1])]:[]}async function Ae(e,a){var t,o;try{P()}catch{}if(!e)return Swal.fire("⚠️","ID de salida inválido.","warning");try{const n=await fetch(`${(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.informacionPaquetesSalida}?salida_id=${e}`,{headers:{Accept:"application/json"}});if(!n.ok)throw new Error("Error al cargar información de la salida");const{salida:s,paquetesAsignados:l,paquetesDisponibles:c,paquetesTodos:d,filtros:u}=await n.json();_e(s,l,c,d||[],u||{obras:[],planillas:[],obrasRelacionadas:[]},a)}catch(n){console.error(n),Swal.fire("❌","Error al cargar la información de la salida","error")}}function _e(e,a,t,o,n,s){window._gestionPaquetesData={salida:e,paquetesAsignados:a,paquetesDisponibles:t,paquetesTodos:o,filtros:n,mostrarTodos:!1};const l=Te(e,a,t,n);Swal.fire({title:`📦 Gestionar Paquetes - Salida ${e.codigo_salida||e.id}`,html:l,width:Math.min(window.innerWidth*.95,1200),showConfirmButton:!0,showCancelButton:!0,confirmButtonText:"💾 Guardar Cambios",cancelButtonText:"Cancelar",focusConfirm:!1,customClass:{popup:"w-full max-w-screen-xl"},didOpen:()=>{ge(),Pe(),ze(),setTimeout(()=>{Ie()},100)},willClose:()=>{A.cleanup&&A.cleanup();const c=document.getElementById("modal-keyboard-indicator");c&&c.remove()},preConfirm:()=>je()}).then(async c=>{c.isConfirmed&&c.value&&await Ne(e.id,c.value,s)})}function Te(e,a,t,o){var u,m;const n=a.reduce((v,b)=>v+(parseFloat(b.peso)||0),0);let s="";e.salida_clientes&&e.salida_clientes.length>0&&(s='<div class="col-span-2"><strong>Obras/Clientes:</strong><br>',e.salida_clientes.forEach(v=>{var r,f,h,E,y;const b=((r=v.obra)==null?void 0:r.obra)||"Obra desconocida",i=(f=v.obra)!=null&&f.cod_obra?`(${v.obra.cod_obra})`:"",p=((h=v.cliente)==null?void 0:h.empresa)||((y=(E=v.obra)==null?void 0:E.cliente)==null?void 0:y.empresa)||"";s+=`<span class="text-xs">• ${b} ${i}`,p&&(s+=` - ${p}`),s+="</span><br>"}),s+="</div>");const l=`
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
    `,c=((o==null?void 0:o.obras)||[]).map(v=>`<option value="${v.id}">${v.cod_obra||""} - ${v.obra||"Sin nombre"}</option>`).join(""),d=((o==null?void 0:o.planillas)||[]).map(v=>`<option value="${v.id}" data-obra-id="${v.obra_id||""}">${v.codigo||"Sin código"}</option>`).join("");return`
        <div class="text-left">
            ${l}

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
                        ${ee(a)}
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
                                    ${c}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">📄 Filtrar por Planilla</label>
                                <select id="filtro-planilla-modal" class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- Todas las planillas --</option>
                                    ${d}
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
                        ${ee(t)}
                    </div>
                </div>
            </div>
        </div>
    `}function ee(e){return!e||e.length===0?'<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>':e.map(a=>{var t,o,n,s,l,c,d,u,m,v,b,i,p,r,f,h;return`
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${a.id}"
            data-peso="${a.peso||0}"
            data-obra-id="${((o=(t=a.planilla)==null?void 0:t.obra)==null?void 0:o.id)||""}"
            data-obra="${((s=(n=a.planilla)==null?void 0:n.obra)==null?void 0:s.obra)||""}"
            data-planilla-id="${((l=a.planilla)==null?void 0:l.id)||""}"
            data-planilla="${((c=a.planilla)==null?void 0:c.codigo)||""}"
            data-cliente="${((u=(d=a.planilla)==null?void 0:d.cliente)==null?void 0:u.empresa)||""}"
            data-paquete-json='${JSON.stringify(a).replace(/'/g,"&#39;")}'
        >
            <div class="flex items-center justify-between text-xs">
                <span class="font-medium">📦 ${a.codigo||"Paquete #"+a.id}</span>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        onclick="event.stopPropagation(); window.verElementosPaqueteSalida(${a.id})"
                        class="text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded p-1 transition-colors"
                        title="Ver elementos del paquete"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                    <span class="text-gray-600">${parseFloat(a.peso||0).toFixed(2)} kg</span>
                </div>
            </div>
            <div class="text-xs text-gray-500 mt-1">
                <div>📄 ${((m=a.planilla)==null?void 0:m.codigo)||a.planilla_id}</div>
                <div>🏗️ ${((b=(v=a.planilla)==null?void 0:v.obra)==null?void 0:b.cod_obra)||""} - ${((p=(i=a.planilla)==null?void 0:i.obra)==null?void 0:p.obra)||"N/A"}</div>
                <div>👤 ${((f=(r=a.planilla)==null?void 0:r.cliente)==null?void 0:f.empresa)||"Sin cliente"}</div>
                ${(h=a.nave)!=null&&h.obra?`<div class="text-blue-600 font-medium">📍 ${a.nave.obra}</div>`:""}
            </div>
        </div>
    `}).join("")}async function De(e){var a;try{const t=document.querySelector(`[data-paquete-id="${e}"]`);let o=null;if(t&&t.dataset.paqueteJson)try{o=JSON.parse(t.dataset.paqueteJson.replace(/&#39;/g,"'"))}catch(d){console.warn("No se pudo parsear JSON del paquete",d)}if(!o){const d=await fetch(`/api/paquetes/${e}/elementos`);d.ok&&(o=await d.json())}if(!o){alert("No se pudo obtener información del paquete");return}const n=[];if(o.etiquetas&&o.etiquetas.length>0&&o.etiquetas.forEach(d=>{d.elementos&&d.elementos.length>0&&d.elementos.forEach(u=>{n.push({id:u.id,dimensiones:u.dimensiones,peso:u.peso,longitud:u.longitud,diametro:u.diametro})})}),n.length===0){alert("Este paquete no tiene elementos para mostrar");return}const s=n.map((d,u)=>`
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">Elemento #${d.id}</span>
                    <span class="text-xs text-gray-500">${u+1} de ${n.length}</span>
                </div>
                <div class="mt-2 text-sm text-gray-600 grid grid-cols-2 gap-2">
                    ${d.diametro?`<div><strong>Ø:</strong> ${d.diametro} mm</div>`:""}
                    ${d.longitud?`<div><strong>Long:</strong> ${d.longitud} mm</div>`:""}
                    ${d.peso?`<div><strong>Peso:</strong> ${parseFloat(d.peso).toFixed(2)} kg</div>`:""}
                </div>
                ${d.dimensiones?`
                    <div class="mt-2 p-2 bg-white border rounded">
                        <div id="elemento-dibujo-${d.id}" class="w-full h-32"></div>
                    </div>
                `:""}
            </div>
        `).join(""),l=document.getElementById("modal-elementos-paquete-overlay");l&&l.remove();const c=`
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
                                <strong>Planilla:</strong> ${((a=o.planilla)==null?void 0:a.codigo)||"N/A"}<br>
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
        `;document.body.insertAdjacentHTML("beforeend",c),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&n.forEach(d=>{d.dimensiones&&window.dibujarFiguraElemento(`elemento-dibujo-${d.id}`,d.dimensiones,null)})},100)}catch(t){console.error("Error al ver elementos del paquete:",t),alert("Error al cargar los elementos del paquete")}}window.verElementosPaqueteSalida=De;function Pe(e){const a=document.getElementById("filtro-obra-modal"),t=document.getElementById("filtro-planilla-modal"),o=document.getElementById("btn-limpiar-filtros-modal");a&&a.addEventListener("change",()=>{se(),Q()}),t&&t.addEventListener("change",()=>{Q()}),o&&o.addEventListener("click",()=>{a&&(a.value=""),t&&(t.value=""),se(),Q()})}function se(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData;if(!a||!t)return;const o=(e==null?void 0:e.value)||"",n=o?t.paquetesTodos:t.paquetesDisponibles,s=new Map;n.forEach(d=>{var u,m,v;if((u=d.planilla)!=null&&u.id){if(o&&String((m=d.planilla.obra)==null?void 0:m.id)!==o)return;s.has(d.planilla.id)||s.set(d.planilla.id,{id:d.planilla.id,codigo:d.planilla.codigo||"Sin código",obra_id:(v=d.planilla.obra)==null?void 0:v.id})}});const l=Array.from(s.values()).sort((d,u)=>(d.codigo||"").localeCompare(u.codigo||"")),c=a.value;a.innerHTML='<option value="">-- Todas las planillas --</option>',l.forEach(d=>{const u=document.createElement("option");u.value=d.id,u.textContent=d.codigo,a.appendChild(u)}),c&&s.has(parseInt(c))?a.value=c:a.value=""}function Q(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData,o=(e==null?void 0:e.value)||"",n=(a==null?void 0:a.value)||"",s=document.querySelector('[data-zona="disponibles"]');if(!s||!t)return;const l=document.querySelector('[data-zona="asignados"]'),c=new Set;l&&l.querySelectorAll(".paquete-item-salida").forEach(m=>{c.add(parseInt(m.dataset.paqueteId))});let u=(o?t.paquetesTodos:t.paquetesDisponibles).filter(m=>{var v,b,i;return!(c.has(m.id)||o&&String((b=(v=m.planilla)==null?void 0:v.obra)==null?void 0:b.id)!==o||n&&String((i=m.planilla)==null?void 0:i.id)!==n)});s.innerHTML=ee(u),ge(),u.length===0&&(s.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">No hay paquetes que coincidan con el filtro</div>')}let A={zonaActiva:"asignados",indiceFocused:-1,cleanup:null};function Ie(){A.cleanup&&A.cleanup(),A.zonaActiva="asignados",A.indiceFocused=0,F();function e(a){var v;if(!document.querySelector(".swal2-container"))return;const t=a.target.tagName.toLowerCase(),o=t==="select";if((t==="input"||t==="textarea")&&a.key!=="Escape")return;const s=document.querySelector('[data-zona="asignados"]'),l=document.querySelector('[data-zona="disponibles"]');if(!s||!l)return;const c=A.zonaActiva==="asignados"?s:l,d=Array.from(c.querySelectorAll('.paquete-item-salida:not([style*="display: none"])')),u=d.length;let m=!1;if(!o)switch(a.key){case"ArrowDown":u>0&&(A.indiceFocused=(A.indiceFocused+1)%u,F(),m=!0);break;case"ArrowUp":u>0&&(A.indiceFocused=A.indiceFocused<=0?u-1:A.indiceFocused-1,F(),m=!0);break;case"ArrowLeft":case"ArrowRight":A.zonaActiva=A.zonaActiva==="asignados"?"disponibles":"asignados",A.indiceFocused=0,F(),m=!0;break;case"Tab":a.preventDefault(),A.zonaActiva=A.zonaActiva==="asignados"?"disponibles":"asignados",A.indiceFocused=0,F(),m=!0;break;case"Enter":{if(u>0&&A.indiceFocused>=0){const b=d[A.indiceFocused];if(b){Fe(b);const i=Array.from(c.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));A.indiceFocused>=i.length&&(A.indiceFocused=Math.max(0,i.length-1)),F(),m=!0}}break}case"Home":A.indiceFocused=0,F(),m=!0;break;case"End":A.indiceFocused=Math.max(0,u-1),F(),m=!0;break}if(m){a.preventDefault(),a.stopPropagation();return}switch(a.key){case"o":case"O":{const b=document.getElementById("filtro-obra-modal");b&&(b.focus(),m=!0);break}case"p":case"P":{const b=document.getElementById("filtro-planilla-modal");b&&(b.focus(),m=!0);break}case"l":case"L":{const b=document.getElementById("btn-limpiar-filtros-modal");b&&(b.click(),(v=document.activeElement)==null||v.blur(),F(),m=!0);break}case"/":case"f":case"F":{const b=document.getElementById("filtro-obra-modal");b&&(b.focus(),m=!0);break}case"Escape":o&&(document.activeElement.blur(),F(),m=!0);break;case"s":case"S":{if(a.ctrlKey||a.metaKey){const b=document.querySelector(".swal2-confirm");b&&(b.click(),m=!0)}break}}m&&(a.preventDefault(),a.stopPropagation())}document.addEventListener("keydown",e,!0),A.cleanup=()=>{document.removeEventListener("keydown",e,!0),me()}}function F(){me();const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;A.zonaActiva==="asignados"?(e.classList.add("zona-activa-keyboard"),a.classList.remove("zona-activa-keyboard")):(a.classList.add("zona-activa-keyboard"),e.classList.remove("zona-activa-keyboard"));const t=A.zonaActiva==="asignados"?e:a,o=Array.from(t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));if(o.length>0&&A.indiceFocused>=0){const n=Math.min(A.indiceFocused,o.length-1),s=o[n];s&&(s.classList.add("paquete-focused-keyboard"),s.scrollIntoView({behavior:"smooth",block:"nearest"}))}Me()}function me(){document.querySelectorAll(".paquete-focused-keyboard").forEach(e=>{e.classList.remove("paquete-focused-keyboard")}),document.querySelectorAll(".zona-activa-keyboard").forEach(e=>{e.classList.remove("zona-activa-keyboard")})}function Fe(e){const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!a||!t)return;const o=e.closest("[data-zona]"),n=o.dataset.zona==="asignados"?t:a,s=n.querySelector(".placeholder-sin-paquetes");if(s&&s.remove(),n.appendChild(e),o.querySelectorAll(".paquete-item-salida").length===0){const c=document.createElement("div");c.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",c.textContent="Sin paquetes",o.appendChild(c)}fe(e),X()}function Me(){let e=document.getElementById("modal-keyboard-indicator");e||(e=document.createElement("div"),e.id="modal-keyboard-indicator",e.className="fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs",document.body.appendChild(e));const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]'),o=(a==null?void 0:a.querySelectorAll(".paquete-item-salida").length)||0,n=(t==null?void 0:t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0,s=A.zonaActiva==="asignados"?`📦 Asignados (${o})`:`📋 Disponibles (${n})`;e.innerHTML=`
        <div class="flex items-center gap-2 mb-2">
            <span class="${A.zonaActiva==="asignados"?"bg-green-500":"bg-gray-500"} text-white text-xs px-2 py-0.5 rounded">${s}</span>
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
    `,clearTimeout(e._checkTimeout),e._checkTimeout=setTimeout(()=>{document.querySelector(".swal2-container")||e.remove()},500)}function ze(){if(document.getElementById("modal-keyboard-styles"))return;const e=document.createElement("style");e.id="modal-keyboard-styles",e.textContent=`
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
    `,document.head.appendChild(e)}function fe(e){e.addEventListener("dragstart",a=>{e.style.opacity="0.5",a.dataTransfer.setData("text/plain",e.dataset.paqueteId)}),e.addEventListener("dragend",a=>{e.style.opacity="1"})}function ge(){document.querySelectorAll(".paquete-item-salida").forEach(e=>{fe(e)}),document.querySelectorAll(".drop-zone").forEach(e=>{e.addEventListener("dragover",a=>{a.preventDefault();const t=e.dataset.zona;e.style.backgroundColor=t==="asignados"?"#d1fae5":"#e0f2fe"}),e.addEventListener("dragleave",a=>{e.style.backgroundColor=""}),e.addEventListener("drop",a=>{a.preventDefault(),e.style.backgroundColor="";const t=a.dataTransfer.getData("text/plain"),o=document.querySelector(`.paquete-item-salida[data-paquete-id="${t}"]`);if(o){const n=e.querySelector(".placeholder-sin-paquetes");n&&n.remove(),e.appendChild(o),X()}})})}function X(){const e=document.querySelector('[data-zona="asignados"]'),a=e==null?void 0:e.querySelectorAll(".paquete-item-salida");let t=0;a==null||a.forEach(n=>{const s=parseFloat(n.dataset.peso)||0;t+=s});const o=document.getElementById("peso-asignados");o&&(o.textContent=`${t.toFixed(2)} kg`)}function Be(){const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;const t=e.querySelectorAll(".paquete-item-salida");if(t.length===0)return;t.forEach(n=>{a.appendChild(n)});const o=a.querySelector(".placeholder-sin-paquetes");o&&o.remove(),e.querySelectorAll(".paquete-item-salida").length===0&&(e.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),X()}function Oe(){const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;const t=Array.from(a.querySelectorAll(".paquete-item-salida")).filter(s=>s.style.display!=="none");if(t.length===0)return;const o=e.querySelector(".placeholder-sin-paquetes");o&&o.remove(),t.forEach(s=>{e.appendChild(s)}),a.querySelectorAll(".paquete-item-salida").length===0&&(a.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),X()}window.vaciarSalidaModal=Be;window.volcarTodosASalidaModal=Oe;function je(){const e=document.querySelector('[data-zona="asignados"]');return{paquetes_ids:Array.from((e==null?void 0:e.querySelectorAll(".paquete-item-salida"))||[]).map(t=>parseInt(t.dataset.paqueteId))}}async function Ne(e,a,t){var o,n,s,l;try{const d=await(await fetch((n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.guardarPaquetesSalida,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf},body:JSON.stringify({salida_id:e,paquetes_ids:a.paquetes_ids})})).json();d.success?(await Swal.fire({icon:"success",title:"✅ Cambios Guardados",text:"Los paquetes de la salida se han actualizado correctamente.",timer:2e3}),t&&(t.refetchEvents(),(l=t.refetchResources)==null||l.call(t))):await Swal.fire("⚠️",d.message||"No se pudieron guardar los cambios","warning")}catch(c){console.error(c),Swal.fire("❌","Error al guardar los paquetes","error")}}async function Re(e,a,t){try{P()}catch{}window.Livewire.dispatch("abrirComentario",{salidaId:e}),window._calendarRef=t}function He(e){return e?typeof e=="string"?e.split(",").map(t=>t.trim()).filter(Boolean):Array.from(e).map(t=>typeof t=="object"&&(t==null?void 0:t.id)!=null?t.id:t).map(String).map(t=>t.trim()).filter(Boolean):[]}async function We(e){var s,l;const a=(l=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:l.informacionPlanillas;if(!a)throw new Error("Ruta 'informacionPlanillas' no configurada");const t=`${a}?ids=${encodeURIComponent(e.join(","))}`,o=await fetch(t,{headers:{Accept:"application/json"}});if(!o.ok){const c=await o.text().catch(()=>"");throw new Error(`GET ${t} -> ${o.status} ${c}`)}const n=await o.json();return Array.isArray(n==null?void 0:n.planillas)?n.planillas:[]}function ye(e){if(!e)return!1;const t=new Date(e+"T00:00:00").getDay();return t===0||t===6}function Ge(e,a,t,o){const n=document.getElementById("modal-figura-elemento-overlay");n&&n.remove();const s=o.getBoundingClientRect(),l=320,c=240;let d=s.right+10;d+l>window.innerWidth&&(d=s.left-l-10);let u=s.top-c/2+s.height/2;u<10&&(u=10),u+c>window.innerHeight-10&&(u=window.innerHeight-c-10);const m=`
        <div id="modal-figura-elemento-overlay"
             class="fixed bg-white rounded-lg shadow-2xl border border-gray-300"
             style="z-index: 10001; left: ${d}px; top: ${u}px; width: ${l}px;"
             onmouseleave="this.remove()">
            <div class="flex items-center justify-between px-3 py-2 border-b bg-gray-100 rounded-t-lg">
                <h3 class="text-xs font-semibold text-gray-700">${a||"Elemento"}</h3>
            </div>
            <div class="p-2">
                <div id="figura-elemento-container-${e}" class="w-full h-36 bg-gray-50 rounded"></div>
                <div class="mt-2 px-1 py-1 bg-gray-100 rounded text-xs text-gray-600 font-mono break-all">
                    ${t||"Sin dimensiones"}
                </div>
            </div>
        </div>
    `;document.body.insertAdjacentHTML("beforeend",m),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&window.dibujarFiguraElemento(`figura-elemento-container-${e}`,t,null)},50)}function Ve(e){return`
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
          <tbody>${e.map((t,o)=>{var i,p,r;const n=((i=t.obra)==null?void 0:i.codigo)||"",s=((p=t.obra)==null?void 0:p.nombre)||"",l=t.seccion||"";t.descripcion;const c=t.codigo||`Planilla ${t.id}`,d=t.peso_total?parseFloat(t.peso_total).toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})+" kg":"",u=qe(t.fecha_estimada_entrega),m=t.elementos&&t.elementos.length>0,v=((r=t.elementos)==null?void 0:r.length)||0;let b="";return m&&(b=t.elementos.map((f,h)=>{const E=f.fecha_entrega||"",y=f.peso?parseFloat(f.peso).toFixed(2):"-",S=f.codigo||"-",C=f.dimensiones&&f.dimensiones.trim()!=="",x=C?f.dimensiones.replace(/"/g,"&quot;").replace(/'/g,"&#39;"):"",$=S.replace(/"/g,"&quot;").replace(/'/g,"&#39;");return`
                    <tr class="elemento-row elemento-planilla-${t.id} bg-gray-50 hidden">
                        <td class="px-2 py-1 text-xs text-gray-400 pl-4">
                            <div class="flex items-center gap-1">
                                <input type="checkbox" class="elemento-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-3.5 w-3.5"
                                       data-elemento-id="${f.id}"
                                       data-planilla-id="${t.id}">
                                <span>↳</span>
                                <span class="font-medium text-gray-600">${S}</span>
                                ${C?`
                                <button type="button"
                                        class="ver-figura-elemento text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded p-0.5 transition-colors"
                                        data-elemento-id="${f.id}"
                                        data-elemento-codigo="${$}"
                                        data-dimensiones="${x}"
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
                        <td class="px-2 py-1 text-xs text-right text-gray-500">${y} kg</td>
                        <td class="px-2 py-1" colspan="2">
                            <input type="date" class="swal2-input !m-0 !w-auto !text-xs elemento-fecha"
                                   data-elemento-id="${f.id}"
                                   data-planilla-id="${t.id}"
                                   value="${E}">
                        </td>
                    </tr>`}).join("")),`
<tr class="planilla-row hover:bg-blue-50 bg-blue-100 border-t border-blue-200" data-planilla-id="${t.id}" style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${o*18}ms;">
  <td class="px-2 py-2 text-xs font-semibold text-blue-800" colspan="2">
    ${m?`<button type="button" class="toggle-elementos mr-1 text-blue-600 hover:text-blue-800" data-planilla-id="${t.id}">▶</button>`:""}
    📄 ${c}
    ${m?`<span class="ml-1 text-xs text-blue-500 font-normal">(${v} elem.)</span>`:""}
  </td>
  <td class="px-2 py-2 text-xs text-blue-700" colspan="2">
    <span class="font-medium">${n}</span> ${s}
  </td>
  <td class="px-2 py-2 text-xs text-blue-600">${l||"-"}</td>
  <td class="px-2 py-2 text-xs text-right font-semibold text-blue-800">${d}</td>
  <td class="px-2 py-2" colspan="2">
    <div class="flex items-center gap-1">
      <input type="date" class="swal2-input !m-0 !w-auto planilla-fecha !bg-blue-50 !border-blue-300" data-planilla-id="${t.id}" value="${u}">
      ${m?`<button type="button" class="aplicar-fecha-elementos text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded" data-planilla-id="${t.id}" title="Aplicar fecha a todos los elementos">↓</button>`:""}
    </div>
  </td>
</tr>
${b}`}).join("")}</tbody>
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
    </div>`}function Ye(e){const a={};return document.querySelectorAll('input[type="date"][data-planilla-id]').forEach(o=>{const n=parseInt(o.dataset.planillaId),s=o.value,l=e.find(c=>c.id===n);s&&l&&l.peso_total&&(a[s]||(a[s]={peso:0,planillas:0,esFinDeSemana:ye(s)}),a[s].peso+=parseFloat(l.peso_total),a[s].planillas+=1)}),a}function re(e){const a=Ye(e),t=document.getElementById("resumen-contenido");if(!t)return;const o=Object.keys(a).sort();if(o.length===0){t.innerHTML='<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';return}const n=o.map(c=>{const d=a[c],u=new Date(c+"T00:00:00").toLocaleDateString("es-ES",{weekday:"short",day:"2-digit",month:"2-digit",year:"numeric"}),m=d.peso.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2}),v=d.esFinDeSemana?"bg-orange-100 border-orange-300 text-orange-800":"bg-green-100 border-green-300 text-green-800",b=d.esFinDeSemana?"🏖️":"📦";return`
            <div class="inline-block m-1 px-2 py-1 rounded border ${v}">
                <span class="font-medium">${b} ${u}</span>
                <br>
                <span class="text-xs">${m} kg (${d.planillas} planilla${d.planillas!==1?"s":""})</span>
            </div>
        `}).join(""),s=o.reduce((c,d)=>c+a[d].peso,0),l=o.reduce((c,d)=>c+a[d].planillas,0);t.innerHTML=`
        <div class="mb-2">${n}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${s.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})} kg 
            (${l} planilla${l!==1?"s":""})
        </div>
    `}async function Ke(e){var o,n,s;const a=(n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.actualizarFechasPlanillas;if(!a)throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");const t=await fetch(a,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf,Accept:"application/json"},body:JSON.stringify({planillas:e})});if(!t.ok){const l=await t.text().catch(()=>"");throw new Error(`PUT ${a} -> ${t.status} ${l}`)}return t.json().catch(()=>({}))}async function Xe(e,a){var t,o;try{const n=Array.from(new Set(He(e))).map(Number).filter(Boolean);if(!n.length)return Swal.fire("⚠️","No hay planillas en la agrupación.","warning");const s=await We(n);if(!s.length)return Swal.fire("⚠️","No se han encontrado planillas.","warning");const c=`
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `+Ve(s),{isConfirmed:d}=await Swal.fire({title:"",html:c,width:Math.min(window.innerWidth*.98,1200),customClass:{popup:"w-full max-w-screen-xl"},showCancelButton:!0,confirmButtonText:"💾 Guardar",cancelButtonText:"Cancelar",focusConfirm:!1,showClass:{popup:"swal-fade-in-zoom"},hideClass:{popup:"swal-fade-out"},didOpen:i=>{var h,E,y,S,C,x,$;Je(i),V("#swal-drag",!1),setTimeout(()=>{const g=Swal.getHtmlContainer().querySelector('input[type="date"]');g==null||g.focus({preventScroll:!0})},120),Swal.getHtmlContainer().querySelectorAll('input[type="date"]').forEach(g=>{g.addEventListener("change",function(){ye(this.value)?this.classList.add("weekend-date"):this.classList.remove("weekend-date"),re(s)})});const r=Swal.getHtmlContainer();r.querySelectorAll(".toggle-elementos").forEach(g=>{g.addEventListener("click",q=>{q.stopPropagation();const k=g.dataset.planillaId,L=r.querySelectorAll(`.elemento-planilla-${k}`),_=g.textContent==="▼";L.forEach(T=>{T.classList.toggle("hidden",_)}),g.textContent=_?"▶":"▼"})}),(h=r.querySelector("#expandir-todos"))==null||h.addEventListener("click",()=>{r.querySelectorAll(".elemento-row").forEach(g=>g.classList.remove("hidden")),r.querySelectorAll(".toggle-elementos").forEach(g=>g.textContent="▼")}),(E=r.querySelector("#colapsar-todos"))==null||E.addEventListener("click",()=>{r.querySelectorAll(".elemento-row").forEach(g=>g.classList.add("hidden")),r.querySelectorAll(".toggle-elementos").forEach(g=>g.textContent="▶")});function f(){const q=r.querySelectorAll(".elemento-checkbox:checked").length,k=r.querySelector("#barra-acciones-masivas"),L=r.querySelector("#contador-seleccionados");q>0?(k==null||k.classList.remove("hidden"),L&&(L.textContent=q)):k==null||k.classList.add("hidden")}r.querySelectorAll(".elemento-checkbox").forEach(g=>{g.addEventListener("change",f)}),(y=r.querySelector("#seleccionar-todos-elementos"))==null||y.addEventListener("click",()=>{r.querySelectorAll(".elemento-row").forEach(g=>g.classList.remove("hidden")),r.querySelectorAll(".toggle-elementos").forEach(g=>g.textContent="▼"),r.querySelectorAll(".elemento-checkbox").forEach(g=>{g.checked=!0}),f()}),(S=r.querySelector("#seleccionar-sin-fecha"))==null||S.addEventListener("click",()=>{r.querySelectorAll(".elemento-row").forEach(g=>g.classList.remove("hidden")),r.querySelectorAll(".toggle-elementos").forEach(g=>g.textContent="▼"),r.querySelectorAll(".elemento-checkbox").forEach(g=>{g.checked=!1}),r.querySelectorAll(".elemento-checkbox").forEach(g=>{const q=g.dataset.elementoId,k=r.querySelector(`.elemento-fecha[data-elemento-id="${q}"]`);k&&!k.value&&(g.checked=!0)}),f()}),(C=r.querySelector("#deseleccionar-todos"))==null||C.addEventListener("click",()=>{r.querySelectorAll(".elemento-checkbox").forEach(g=>{g.checked=!1}),f()}),(x=r.querySelector("#aplicar-fecha-masiva"))==null||x.addEventListener("click",()=>{var _;const g=(_=r.querySelector("#fecha-masiva"))==null?void 0:_.value;if(!g){alert("Por favor, selecciona una fecha para aplicar");return}r.querySelectorAll(".elemento-checkbox:checked").forEach(T=>{const D=T.dataset.elementoId,I=r.querySelector(`.elemento-fecha[data-elemento-id="${D}"]`);I&&(I.value=g,I.dispatchEvent(new Event("change")))});const k=r.querySelector("#aplicar-fecha-masiva"),L=k.textContent;k.textContent="✓ Aplicado",k.classList.add("bg-green-600"),setTimeout(()=>{k.textContent=L,k.classList.remove("bg-green-600")},1500)}),($=r.querySelector("#limpiar-fecha-seleccionados"))==null||$.addEventListener("click",()=>{r.querySelectorAll(".elemento-checkbox:checked").forEach(q=>{const k=q.dataset.elementoId,L=r.querySelector(`.elemento-fecha[data-elemento-id="${k}"]`);L&&(L.value="",L.dispatchEvent(new Event("change")))})}),r.querySelectorAll(".aplicar-fecha-elementos").forEach(g=>{g.addEventListener("click",q=>{var _;q.stopPropagation();const k=g.dataset.planillaId,L=(_=r.querySelector(`.planilla-fecha[data-planilla-id="${k}"]`))==null?void 0:_.value;L&&r.querySelectorAll(`.elemento-fecha[data-planilla-id="${k}"]`).forEach(T=>{T.value=L,T.dispatchEvent(new Event("change"))})})}),r.querySelectorAll(".ver-figura-elemento").forEach(g=>{g.addEventListener("mouseenter",q=>{var T,D;const k=g.dataset.elementoId,L=((T=g.dataset.elementoCodigo)==null?void 0:T.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"",_=((D=g.dataset.dimensiones)==null?void 0:D.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"";typeof window.dibujarFiguraElemento=="function"&&Ge(k,L,_,g)}),g.addEventListener("mouseleave",q=>{setTimeout(()=>{const k=document.getElementById("modal-figura-elemento-overlay");k&&!k.matches(":hover")&&k.remove()},100)}),g.addEventListener("click",q=>{q.preventDefault(),q.stopPropagation();const k=g.dataset.elementoId,L=r.querySelector(`.elemento-checkbox[data-elemento-id="${k}"]`);if(L){L.checked=!L.checked;const T=r.querySelectorAll(".elemento-checkbox:checked").length,D=r.querySelector("#barra-acciones-masivas"),I=r.querySelector("#contador-seleccionados");T>0?(D==null||D.classList.remove("hidden"),I&&(I.textContent=T)):D==null||D.classList.add("hidden")}})}),setTimeout(()=>{re(s)},100)}});if(!d)return;const u=Swal.getHtmlContainer(),m=u.querySelectorAll(".planilla-fecha"),v=Array.from(m).map(i=>{const p=Number(i.getAttribute("data-planilla-id")),r=u.querySelectorAll(`.elemento-fecha[data-planilla-id="${p}"]`),f=Array.from(r).map(h=>({id:Number(h.getAttribute("data-elemento-id")),fecha_entrega:h.value||null}));return{id:p,fecha_estimada_entrega:i.value,elementos:f.length>0?f:void 0}}),b=await Ke(v);await Swal.fire(b.success?"✅":"⚠️",b.message||(b.success?"Fechas actualizadas":"No se pudieron actualizar"),b.success?"success":"warning"),b.success&&a&&((t=a.refetchEvents)==null||t.call(a),(o=a.refetchResources)==null||o.call(a))}catch(n){console.error("[CambiarFechasEntrega] error:",n),Swal.fire("❌",(n==null?void 0:n.message)||"Ocurrió un error al actualizar las fechas.","error")}}function ie(e,a){e.el.addEventListener("mousedown",P),e.el.addEventListener("contextmenu",t=>{t.preventDefault(),t.stopPropagation();const o=e.event,n=o.extendedProps||{},s=n.tipo||"planilla";let l="";if(s==="salida"){if(n.clientes&&Array.isArray(n.clientes)&&n.clientes.length>0){const u=n.clientes.map(m=>m.nombre).filter(Boolean).join(", ");u&&(l+=`<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${u}</span>`)}n.obras&&Array.isArray(n.obras)&&n.obras.length>0&&(l+='<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ ',l+=n.obras.map(u=>{const m=u.codigo?`(${u.codigo})`:"";return`${u.nombre} ${m}`}).join(", "),l+="</span>")}const c=`
      <div style="padding:10px 12px; font-weight:600;">
        ${o.title??"Evento"}${l}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(o.start).toLocaleString()} — ${new Date(o.end).toLocaleString()}
        </span>
      </div>
    `;let d=[];if(s==="planilla"){const u=Le(o);d=[{label:"Gestionar Salidas y Paquetes",icon:"📦",onClick:()=>window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${u.join(",")}`},{label:"Cambiar fechas de entrega",icon:"🗓️",onClick:()=>Xe(u,a)}]}else if(s==="salida"){const u=n.salida_id||o.id;n.empresa_id,n.empresa,d=[{label:"Abrir salida",icon:"🧾",onClick:()=>window.open(`/salidas-ferralla/${u}`,"_blank")},{label:"Gestionar paquetes",icon:"📦",onClick:()=>Ae(u,a)},{label:"Agregar comentario",icon:"✍️",onClick:()=>Re(u,n.comentario||"",a)}]}else d=[{label:"Abrir",icon:"🧾",onClick:()=>window.open(n.url||"#","_blank")}];Ce(t.clientX,t.clientY,{headerHtml:c,items:d})})}function Je(e){e.style.transform="none",e.style.position="fixed",e.style.margin="0";const a=e.offsetWidth,t=e.offsetHeight,o=Math.max(0,Math.round((window.innerWidth-a)/2)),n=Math.max(0,Math.round((window.innerHeight-t)/2));e.style.left=`${o}px`,e.style.top=`${n}px`}function V(e=".swal2-title",a=!1){const t=Swal.getPopup(),o=Swal.getHtmlContainer();let n=(e?(o==null?void 0:o.querySelector(e))||(t==null?void 0:t.querySelector(e)):null)||t;if(!t||!n)return;a&&V.__lastPos&&(t.style.left=V.__lastPos.left,t.style.top=V.__lastPos.top,t.style.transform="none"),n.style.cursor="move",n.style.touchAction="none";const s=p=>{var r;return((r=p.closest)==null?void 0:r.call(p,"input, textarea, select, button, a, label, [contenteditable]"))!=null};let l=!1,c=0,d=0,u=0,m=0;const v=p=>{if(!n.contains(p.target)||s(p.target))return;l=!0,document.body.style.userSelect="none";const r=t.getBoundingClientRect();t.style.left=`${r.left}px`,t.style.top=`${r.top}px`,t.style.transform="none",u=parseFloat(t.style.left||r.left),m=parseFloat(t.style.top||r.top),c=p.clientX,d=p.clientY,document.addEventListener("pointermove",b),document.addEventListener("pointerup",i,{once:!0})},b=p=>{if(!l)return;const r=p.clientX-c,f=p.clientY-d;let h=u+r,E=m+f;const y=t.offsetWidth,S=t.offsetHeight,C=-y+40,x=window.innerWidth-40,$=-S+40,g=window.innerHeight-40;h=Math.max(C,Math.min(x,h)),E=Math.max($,Math.min(g,E)),t.style.left=`${h}px`,t.style.top=`${E}px`},i=()=>{l=!1,document.body.style.userSelect="",a&&(V.__lastPos={left:t.style.left,top:t.style.top}),document.removeEventListener("pointermove",b)};n.addEventListener("pointerdown",v)}document.addEventListener("DOMContentLoaded",function(){window.addEventListener("comentarioGuardado",e=>{const{salidaId:a,comentario:t}=e.detail,o=window._calendarRef;if(o){const n=o.getEventById(`salida-${a}`);n&&(n.setExtendedProp("comentario",t),n._def&&n._def.extendedProps&&(n._def.extendedProps.comentario=t)),typeof Swal<"u"&&Swal.fire({icon:"success",title:"Comentario guardado",text:"El comentario se ha guardado correctamente",timer:2e3,showConfirmButton:!1,toast:!0,position:"top-end"})}})});let w=null;function Ue(e,a){const t=()=>e&&e.offsetParent!==null&&e.clientWidth>0&&e.clientHeight>=0;if(t())return a();if("IntersectionObserver"in window){const n=new IntersectionObserver(s=>{s.some(c=>c.isIntersecting)&&(n.disconnect(),a())},{root:null,threshold:.01});n.observe(e);return}if("ResizeObserver"in window){const n=new ResizeObserver(()=>{t()&&(n.disconnect(),a())});n.observe(e);return}const o=setInterval(()=>{t()&&(clearInterval(o),a())},100)}function M(){w&&(requestAnimationFrame(()=>{try{w.updateSize()}catch{}}),setTimeout(()=>{try{w.updateSize()}catch{}},150))}function Ze(){let e=document.getElementById("transparent-drag-image");return e||(e=document.createElement("img"),e.id="transparent-drag-image",e.src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7",e.style.cssText="position: fixed; left: -9999px; top: -9999px; width: 1px; height: 1px; opacity: 0;",document.body.appendChild(e)),e}function Qe(e,a){var p,r;te();const t=document.createElement("div");t.id="custom-drag-ghost",t.className="custom-drag-ghost";const o=e.extendedProps||{},n=o.tipo==="salida",s=n?"🚚":"📋",l=n?"Salida":"Planilla",c=o.cod_obra||"",d=o.nombre_obra||((p=e.title)==null?void 0:p.split(`
`)[0])||"",u=o.cliente||"",m=o.pesoTotal?Number(o.pesoTotal).toLocaleString("es-ES",{maximumFractionDigits:0}):"",v=o.longitudTotal?Number(o.longitudTotal).toLocaleString("es-ES",{maximumFractionDigits:0}):"",b=o.diametroMedio?Number(o.diametroMedio).toFixed(1):"",i=((r=a==null?void 0:a.style)==null?void 0:r.backgroundColor)||e.backgroundColor||"#6366f1";return t.innerHTML=`
        <div class="ghost-card" style="--ghost-color: ${i};">
            <!-- Tipo -->
            <div class="ghost-type-badge ${n?"badge-salida":"badge-planilla"}">
                <span>${s}</span>
                <span>${l}</span>
            </div>

            <!-- Info principal -->
            <div class="ghost-main">
                ${c?`<div class="ghost-code">${c}</div>`:""}
                ${d?`<div class="ghost-name">${d}</div>`:""}
                ${u?`<div class="ghost-client">👤 ${u}</div>`:""}
            </div>

            <!-- Métricas -->
            ${m||v||b?`
            <div class="ghost-metrics">
                ${m?`<span class="ghost-metric">📦 ${m} kg</span>`:""}
                ${v?`<span class="ghost-metric">📏 ${v} m</span>`:""}
                ${b?`<span class="ghost-metric">⌀ ${b} mm</span>`:""}
            </div>
            `:""}

            <!-- Destino del drop -->
            <div class="ghost-destination">
                <span class="ghost-dest-date">--</span>
            </div>
        </div>
    `,document.body.appendChild(t),t}function le(e,a,t,o){const n=document.getElementById("custom-drag-ghost");if(n){if(n.style.left=`${e+20}px`,n.style.top=`${a-20}px`,t){const s=n.querySelector(".ghost-dest-time");s&&(s.textContent=t)}if(o){const s=n.querySelector(".ghost-dest-date");if(s){const l=new Date(o+"T00:00:00"),c={weekday:"short",day:"numeric",month:"short"};s.textContent=l.toLocaleDateString("es-ES",c)}}}}function te(){const e=document.getElementById("custom-drag-ghost");e&&e.remove()}function de(e,a){const t=a==null?void 0:a.querySelector(".fc-timegrid-slots");if(!t)return null;const o=t.getBoundingClientRect(),n=e-o.top+t.scrollTop,s=t.scrollHeight||o.height,l=5,c=20,d=c-l,u=n/s*d,m=l*60+u*60,v=Math.round(m/30)*30,b=Math.max(l,Math.min(c-1,Math.floor(v/60))),i=v%60;return`${String(b).padStart(2,"0")}:${String(i).padStart(2,"0")}`}function ce(e){const a=document.querySelectorAll(".fc-timegrid-slot, .fc-timegrid-col");e?a.forEach(t=>{t.classList.add("fc-drop-zone-highlight")}):a.forEach(t=>{t.classList.remove("fc-drop-zone-highlight")})}function et(){if(!window.FullCalendar)return console.error("FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."),null;if(!document.getElementById("fc-mirror-hide-style-global")){const u=document.createElement("style");u.id="fc-mirror-hide-style-global",u.textContent=`
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
        `,document.head.appendChild(u)}w&&w.destroy();const e=["resourceTimeGridDay","resourceTimelineWeek","dayGridMonth"];let a=localStorage.getItem("ultimaVistaCalendario");e.includes(a)||(a="resourceTimeGridDay");const t=localStorage.getItem("fechaCalendario");let o=null;const n=document.getElementById("calendario");if(!n)return console.error("#calendario no encontrado"),null;function s(u){return w?w.getEvents().some(m=>{var i,p;const v=(m.startStr||((i=m.start)==null?void 0:i.toISOString())||"").split("T")[0];return(((p=m.extendedProps)==null?void 0:p.tipo)==="festivo"||typeof m.id=="string"&&m.id.startsWith("festivo-"))&&v===u}):!1}Ue(n,()=>{w=new FullCalendar.Calendar(n,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",navLinks:!0,navLinkDayClick:(i,p)=>{var E;const r=i.getDay(),f=r===0||r===6,h=(E=w==null?void 0:w.view)==null?void 0:E.type;if(f&&(h==="resourceTimelineWeek"||h==="dayGridMonth")){p.preventDefault();let y;h==="dayGridMonth"?y=r===6?"saturday":"sunday":y=i.toISOString().split("T")[0],window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(y)?window.expandedWeekendDays.delete(y):window.expandedWeekendDays.add(y),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),w.render(),setTimeout(()=>{var S;return(S=window.applyWeekendCollapse)==null?void 0:S.call(window)},50);return}w.changeView("resourceTimeGridDay",i)},initialView:a,initialDate:t?new Date(t):void 0,dayMaxEventRows:!1,dayMaxEvents:!1,slotMinTime:"05:00:00",slotMaxTime:"20:00:00",buttonText:{today:"Hoy",resourceTimeGridDay:"Día",resourceTimelineWeek:"Semana",dayGridMonth:"Mes"},progressiveEventRendering:!0,expandRows:!0,height:"auto",events:(i,p,r)=>{var h;const f=i.view&&i.view.type||((h=w==null?void 0:w.view)==null?void 0:h.type)||"resourceTimeGridDay";Se(f,i).then(p).catch(r)},resources:(i,p,r)=>{var h;const f=i.view&&i.view.type||((h=w==null?void 0:w.view)==null?void 0:h.type)||"resourceTimeGridDay";Ee(f,i).then(p).catch(r)},headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimeGridDay,resourceTimelineWeek,dayGridMonth"},eventOrderStrict:!0,eventOrder:(i,p)=>{var y,S;const r=((y=i.extendedProps)==null?void 0:y.tipo)==="resumen-dia",f=((S=p.extendedProps)==null?void 0:S.tipo)==="resumen-dia";if(r&&!f)return-1;if(!r&&f)return 1;const h=parseInt(String(i.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0,E=parseInt(String(p.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0;return h-E},datesSet:i=>{try{const p=tt(i);localStorage.setItem("fechaCalendario",p),localStorage.setItem("ultimaVistaCalendario",i.view.type),c(),clearTimeout(o),o=setTimeout(async()=>{z(),w.refetchResources(),w.refetchEvents(),await ke(i.view.type,{startStr:i.startStr,endStr:i.endStr}),M(),(i.view.type==="resourceTimelineWeek"||i.view.type==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)},0)}catch(p){console.error("Error en datesSet:",p)}},loading:i=>{const p=document.getElementById("calendario-loading"),r=document.getElementById("loading-text");if(p&&(i?(p.classList.remove("hidden"),r&&(r.textContent="Cargando eventos...")):p.classList.add("hidden")),!i&&w){const f=w.view.type;f==="resourceTimeGridDay"&&setTimeout(()=>d(),150),(f==="resourceTimelineWeek"||f==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)}},viewDidMount:i=>{c(),i.view.type==="resourceTimeGridDay"&&setTimeout(()=>d(),100),i.view.type==="dayGridMonth"&&setTimeout(()=>{document.querySelectorAll(".fc-daygrid-event-harness").forEach(p=>{p.querySelector(".evento-resumen-diario")||(p.style.setProperty("width","100%","important"),p.style.setProperty("max-width","100%","important"),p.style.setProperty("position","static","important"),p.style.setProperty("left","unset","important"),p.style.setProperty("right","unset","important"),p.style.setProperty("top","unset","important"),p.style.setProperty("inset","unset","important"),p.style.setProperty("margin","0 0 2px 0","important"))}),document.querySelectorAll(".fc-daygrid-event:not(.evento-resumen-diario)").forEach(p=>{p.style.setProperty("width","100%","important"),p.style.setProperty("max-width","100%","important"),p.style.setProperty("margin","0","important"),p.style.setProperty("position","static","important"),p.style.setProperty("left","unset","important"),p.style.setProperty("right","unset","important"),p.style.setProperty("inset","unset","important")})},50)},eventContent:i=>{var E;const p=i.event.backgroundColor||"#9CA3AF",r=i.event.extendedProps||{},f=(E=w==null?void 0:w.view)==null?void 0:E.type;if(r.tipo==="resumen-dia"){const y=Number(r.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),S=Number(r.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),C=r.diametroMedio?Number(r.diametroMedio).toFixed(1):null;if(f==="resourceTimelineWeek")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${y} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${S} m</div>
                                ${C?`<div class="text-yellow-800">⌀ ${C} mm</div>`:""}
                            </div>
                        `};if(f==="dayGridMonth")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${y} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${S} m</div>
                                ${C?`<div class="text-yellow-800">⌀ ${C} mm</div>`:""}
                            </div>
                        `}}let h=`
        <div style="background-color:${p}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${i.event.title}</div>
    `;if(r.tipo==="planilla"){const y=r.pesoTotal!=null?`📦 ${Number(r.pesoTotal).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2})} kg`:null,S=r.longitudTotal!=null?`📏 ${Number(r.longitudTotal).toLocaleString()} m`:null,C=r.diametroMedio!=null?`⌀ ${Number(r.diametroMedio).toFixed(2)} mm`:null,x=[y,S,C].filter(Boolean);x.length>0&&(h+=`<div class="text-sm text-black font-semibold">${x.join(" | ")}</div>`),r.tieneSalidas&&Array.isArray(r.salidas_codigos)&&r.salidas_codigos.length>0&&(h+=`
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${r.salidas_codigos.join(", ")}
                </span>
            </div>`)}return h+="</div>",{html:h}},eventDidMount:function(i){var S,C,x,$,g;const p=i.event.extendedProps||{};if(i.el.setAttribute("draggable","false"),i.el.ondragstart=q=>(q.preventDefault(),!1),p.tipo==="resumen-dia"){i.el.classList.add("evento-resumen-diario"),i.el.style.cursor="default";return}if(i.view.type==="dayGridMonth"){const q=i.el.closest(".fc-daygrid-event-harness");q&&q.classList.add("evento-fullwidth"),i.el.classList.add("evento-fullwidth-event")}const r=(((S=document.getElementById("filtro-obra"))==null?void 0:S.value)||"").trim().toLowerCase(),f=(((C=document.getElementById("filtro-nombre-obra"))==null?void 0:C.value)||"").trim().toLowerCase(),h=(((x=document.getElementById("filtro-cod-cliente"))==null?void 0:x.value)||"").trim().toLowerCase(),E=((($=document.getElementById("filtro-cliente"))==null?void 0:$.value)||"").trim().toLowerCase(),y=(((g=document.getElementById("filtro-cod-planilla"))==null?void 0:g.value)||"").trim().toLowerCase();if(r||f||h||E||y){let q=!1;if(p.tipo==="salida"&&p.obras&&Array.isArray(p.obras)){if(q=p.obras.some(k=>{const L=(k.codigo||"").toString().toLowerCase(),_=(k.nombre||"").toString().toLowerCase(),T=(k.cod_cliente||"").toString().toLowerCase(),D=(k.cliente||"").toString().toLowerCase(),I=!r||L.includes(r),J=!f||_.includes(f),U=!h||T.includes(h),W=!E||D.includes(E);return I&&J&&U&&W}),y&&p.planillas_codigos&&Array.isArray(p.planillas_codigos)){const k=p.planillas_codigos.some(L=>(L||"").toString().toLowerCase().includes(y));q=q&&k}}else{const k=(p.cod_obra||"").toString().toLowerCase(),L=(p.nombre_obra||i.event.title||"").toString().toLowerCase(),_=(p.cod_cliente||"").toString().toLowerCase(),T=(p.cliente||"").toString().toLowerCase(),D=!r||k.includes(r),I=!f||L.includes(f),J=!h||_.includes(h),U=!E||T.includes(E);let W=!0;y&&(p.planillas_codigos&&Array.isArray(p.planillas_codigos)?W=p.planillas_codigos.some(xe=>(xe||"").toString().toLowerCase().includes(y)):W=(i.event.title||"").toLowerCase().includes(y)),q=D&&I&&J&&U&&W}q?(i.el.classList.add("evento-filtrado"),i.el.classList.remove("evento-atenuado")):(i.el.classList.add("evento-atenuado"),i.el.classList.remove("evento-filtrado"))}else i.el.classList.remove("evento-filtrado"),i.el.classList.remove("evento-atenuado");typeof ne=="function"&&ne(i),typeof ie=="function"&&ie(i,w)},eventAllow:(i,p)=>{var f;const r=(f=p.extendedProps)==null?void 0:f.tipo;return!(r==="resumen-dia"||r==="festivo")},snapDuration:"00:30:00",eventDragStart:i=>{var C;window._isDragging=!0,window._draggedEvent=i.event,Qe(i.event,i.el),document.body.classList.add("fc-dragging-active");const p=Ze(),r=x=>{x.dataTransfer&&window._isDragging&&x.dataTransfer.setDragImage(p,0,0)};document.addEventListener("dragstart",r,!0),window._nativeDragStartHandler=r;const f=document.getElementById("calendario");((C=w==null?void 0:w.view)==null?void 0:C.type)==="resourceTimeGridDay"&&ce(!0);const h=(x,$)=>{const g=document.elementsFromPoint(x,$);for(const q of g){const k=q.closest(".fc-daygrid-day");if(k)return k.getAttribute("data-date");const L=q.closest("[data-date]");if(L)return L.getAttribute("data-date")}return null};let E=!1;const y=x=>{!window._isDragging||E||(E=!0,requestAnimationFrame(()=>{if(E=!1,!window._isDragging)return;const $=de(x.clientY,f),g=h(x.clientX,x.clientY);le(x.clientX,x.clientY,$,g)}))};if(document.addEventListener("mousemove",y,{passive:!0}),window._dragMouseMoveHandler=y,i.jsEvent){const x=de(i.jsEvent.clientY,f),$=h(i.jsEvent.clientX,i.jsEvent.clientY);le(i.jsEvent.clientX,i.jsEvent.clientY,x,$)}window._dragOriginalStart=i.event.start,window._dragOriginalEnd=i.event.end,window._dragEventId=i.event.id;const S=x=>{if(window._isDragging){x.preventDefault(),x.stopPropagation(),x.stopImmediatePropagation(),window._cancelDrag=!0,te();const $=new PointerEvent("pointerup",{bubbles:!0,cancelable:!0,clientX:x.clientX,clientY:x.clientY});document.dispatchEvent($)}};document.addEventListener("contextmenu",S,{capture:!0}),window._dragContextMenuHandler=S},eventDragStop:i=>{window._isDragging=!1,window._draggedEvent=null,window._nativeDragStartHandler&&(document.removeEventListener("dragstart",window._nativeDragStartHandler,!0),window._nativeDragStartHandler=null),window._dragMouseMoveHandler&&(document.removeEventListener("mousemove",window._dragMouseMoveHandler),window._dragMouseMoveHandler=null),window._dragContextMenuHandler&&(document.removeEventListener("contextmenu",window._dragContextMenuHandler,{capture:!0}),window._dragContextMenuHandler=null),window._dragOriginalStart=null,window._dragOriginalEnd=null,window._dragEventId=null,te(),document.body.classList.remove("fc-dragging-active"),ce(!1)},eventDrop:i=>{var y,S,C,x;if(window._cancelDrag){window._cancelDrag=!1,i.revert(),window._dragOriginalStart&&(i.event.setStart(window._dragOriginalStart),window._dragOriginalEnd&&i.event.setEnd(window._dragOriginalEnd));return}const p=i.event.extendedProps||{},r=i.event.id,f=(y=i.event.start)==null?void 0:y.toISOString(),h={fecha:f,tipo:p.tipo,planillas_ids:p.planillas_ids||[],elementos_ids:p.elementos_ids||[]},E=(((C=(S=window.AppSalidas)==null?void 0:S.routes)==null?void 0:C.updateItem)||"").replace("__ID__",r);Swal.fire({title:"Actualizando fecha...",html:"Verificando programación de fabricación",allowOutsideClick:!1,allowEscapeKey:!1,showConfirmButton:!1,didOpen:()=>{Swal.showLoading()}}),fetch(E,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(x=window.AppSalidas)==null?void 0:x.csrf},body:JSON.stringify(h)}).then($=>{if(!$.ok)throw new Error("No se pudo actualizar la fecha.");return $.json()}).then(async $=>{if(Swal.close(),z(),w.refetchEvents(),w.refetchResources(),M(),$.alerta_retraso){const g=$.alerta_retraso.es_elementos_con_fecha_propia||!1,q=g?"elementos":"planilla";Swal.fire({icon:"warning",title:"⚠️ Fecha de entrega adelantada",html:`
                                    <div class="text-left">
                                        <p class="mb-2">${$.alerta_retraso.mensaje}</p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fin fabricación:</strong> ${$.alerta_retraso.fin_programado}</p>
                                            <p class="text-sm"><strong>Fecha entrega:</strong> ${$.alerta_retraso.fecha_entrega}</p>
                                        </div>
                                        <p class="mt-3 text-sm text-gray-600">Los ${q==="elementos"?"elementos":"elementos de la planilla"} no estarán listos para la fecha indicada.</p>
                                    </div>
                                `,showCancelButton:!0,confirmButtonText:"🚀 Adelantar fabricación",cancelButtonText:"Entendido",confirmButtonColor:"#10b981",cancelButtonColor:"#f59e0b"}).then(k=>{k.isConfirmed&&at($.alerta_retraso.elementos_ids||p.elementos_ids,f,g)})}if($.opcion_posponer){const g=$.opcion_posponer.es_elementos_con_fecha_propia||!1,q=$.opcion_posponer.ordenes_afectadas||[],k=g?"Elementos con fecha propia":"Planilla";let L="";q.length>0&&(L=`
                                    <div class="max-h-40 overflow-y-auto mt-3">
                                        <table class="w-full text-sm border">
                                            <thead class="bg-blue-100">
                                                <tr>
                                                    <th class="px-2 py-1 text-left">Planilla</th>
                                                    <th class="px-2 py-1 text-left">Máquina</th>
                                                    <th class="px-2 py-1 text-center">Posición</th>
                                                    ${g?'<th class="px-2 py-1 text-center">Elementos</th>':""}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${q.map(_=>`
                                                    <tr class="border-t">
                                                        <td class="px-2 py-1">${_.planilla_codigo}</td>
                                                        <td class="px-2 py-1">${_.maquina_nombre}</td>
                                                        <td class="px-2 py-1 text-center">${_.posicion_actual} / ${_.total_posiciones}</td>
                                                        ${g?`<td class="px-2 py-1 text-center">${_.elementos_count||"-"}</td>`:""}
                                                    </tr>
                                                `).join("")}
                                            </tbody>
                                        </table>
                                    </div>
                                `),Swal.fire({icon:"question",title:`📅 ${k} - Fecha pospuesta`,html:`
                                    <div class="text-left">
                                        <p class="mb-2">${$.opcion_posponer.mensaje}</p>
                                        <div class="bg-blue-50 border border-blue-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fecha anterior:</strong> ${$.opcion_posponer.fecha_anterior}</p>
                                            <p class="text-sm"><strong>Nueva fecha:</strong> ${$.opcion_posponer.fecha_nueva}</p>
                                        </div>
                                        ${L}
                                        <p class="mt-3 text-sm text-gray-600">Al retrasar la fabricación, otras planillas más urgentes podrán avanzar en la cola.</p>
                                    </div>
                                `,showCancelButton:!0,confirmButtonText:"⏬ Retrasar fabricación",cancelButtonText:"No, mantener posición",confirmButtonColor:"#3b82f6",cancelButtonColor:"#6b7280"}).then(_=>{_.isConfirmed&&nt($.opcion_posponer.elementos_ids,g,f)})}}).catch($=>{Swal.close(),console.error("Error:",$),Swal.fire({icon:"error",title:"Error",text:"No se pudo actualizar la fecha.",timer:3e3}),i.revert()})},dateClick:i=>{s(i.dateStr)&&Swal.fire({icon:"info",title:"📅 Día festivo",text:"Los festivos se editan en la planificación de Trabajadores.",confirmButtonText:"Entendido"})},eventMinHeight:30,firstDay:1,slotLabelContent:i=>{var C,x;if(((C=w==null?void 0:w.view)==null?void 0:C.type)!=="resourceTimelineWeek")return null;const r=i.date;if(!r)return null;const f=r.getDay(),h=f===0||f===6,E=r.toISOString().split("T")[0],y={weekday:"short",day:"numeric",month:"short"},S=r.toLocaleDateString("es-ES",y);if(h){const g=!((x=window.expandedWeekendDays)==null?void 0:x.has(E)),q=g?"▶":"▼",k=g?r.toLocaleDateString("es-ES",{weekday:"short"}).substring(0,3):S;return{html:`<div class="weekend-header cursor-pointer select-none hover:bg-gray-200 px-1 rounded"
                                    data-date="${E}"
                                    data-collapsed="${g}"
                                    title="${g?"Clic para expandir":"Clic para colapsar"}">
                                <span class="collapse-icon text-xs mr-1">${q}</span>
                                <span class="weekend-label">${k}</span>
                               </div>`}}return{html:`<span>${S}</span>`}},views:{resourceTimelineWeek:{slotDuration:{days:1}},resourceTimeGridDay:{slotDuration:"01:00:00",slotLabelFormat:{hour:"2-digit",minute:"2-digit",hour12:!1},slotLabelInterval:"01:00:00",allDaySlot:!1}},editable:!0,eventDurationEditable:!1,eventStartEditable:!0,resourceAreaColumns:[{field:"cod_obra",headerContent:"Código"},{field:"title",headerContent:"Obra"},{field:"cliente",headerContent:"Cliente"}],resourceAreaHeaderContent:"Obras",resourceOrder:"orderIndex",resourceLabelContent:i=>({html:`<div class="text-xs font-semibold">
                        <div class="text-blue-600">${i.resource.extendedProps.cod_obra||""}</div>
                        <div class="text-gray-700 truncate">${i.resource.title||""}</div>
                        <div class="text-gray-500 text-[10px] truncate">${i.resource.extendedProps.cliente||""}</div>
                    </div>`}),windowResize:()=>M()}),w.render(),M(),setTimeout(()=>{const i=document.getElementById("calendario-loading");i&&!i.classList.contains("opacity-0")&&(i.classList.add("opacity-0","pointer-events-none"),i.classList.remove("opacity-100"))},500);const u=localStorage.getItem("expandedWeekendDays");window.expandedWeekendDays=new Set(u?JSON.parse(u):[]),window.weekendDefaultCollapsed=!0;function m(i){const r=new Date(i+"T00:00:00").getDay();return r===0||r===6}function v(){var p,r,f;const i=(p=w==null?void 0:w.view)==null?void 0:p.type;if(i==="resourceTimelineWeek"&&(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(y=>{var C;const S=y.getAttribute("data-date");m(S)&&(((C=window.expandedWeekendDays)==null?void 0:C.has(S))?y.classList.remove("weekend-collapsed"):y.classList.add("weekend-collapsed"))}),document.querySelectorAll(".fc-timeline-lane td[data-date]").forEach(y=>{var C;const S=y.getAttribute("data-date");m(S)&&(((C=window.expandedWeekendDays)==null?void 0:C.has(S))?y.classList.remove("weekend-collapsed"):y.classList.add("weekend-collapsed"))})),i==="dayGridMonth"){const h=(r=window.expandedWeekendDays)==null?void 0:r.has("saturday"),E=(f=window.expandedWeekendDays)==null?void 0:f.has("sunday"),y=document.querySelectorAll(".fc-col-header-cell.fc-day-sat"),S=document.querySelectorAll(".fc-col-header-cell.fc-day-sun");y.forEach(x=>{h?x.classList.remove("weekend-day-collapsed"):x.classList.add("weekend-day-collapsed")}),S.forEach(x=>{E?x.classList.remove("weekend-day-collapsed"):x.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sat").forEach(x=>{h?x.classList.remove("weekend-day-collapsed"):x.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sun").forEach(x=>{E?x.classList.remove("weekend-day-collapsed"):x.classList.add("weekend-day-collapsed")});const C=document.querySelector(".fc-dayGridMonth-view table");if(C){let x=C.querySelector("colgroup");if(!x){x=document.createElement("colgroup");for(let g=0;g<7;g++)x.appendChild(document.createElement("col"));C.insertBefore(x,C.firstChild)}const $=x.querySelectorAll("col");$.length>=7&&($[5].style.width=h?"":"40px",$[6].style.width=E?"":"40px")}}}function b(i){window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(i)?window.expandedWeekendDays.delete(i):window.expandedWeekendDays.add(i),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),v()}n.addEventListener("click",i=>{var f;const p=i.target.closest(".weekend-header");if(p){const h=p.getAttribute("data-date");if(h){i.preventDefault(),i.stopPropagation(),b(h);return}}if(((f=w==null?void 0:w.view)==null?void 0:f.type)==="dayGridMonth"){const h=i.target.closest(".fc-col-header-cell.fc-day-sat, .fc-col-header-cell.fc-day-sun");if(h){i.preventDefault(),i.stopPropagation();const S=h.classList.contains("fc-day-sat")?"saturday":"sunday";b(S);return}const E=i.target.closest(".fc-daygrid-day.fc-day-sat, .fc-daygrid-day.fc-day-sun");if(E&&!i.target.closest(".fc-event")){i.preventDefault(),i.stopPropagation();const S=E.classList.contains("fc-day-sat")?"saturday":"sunday";b(S);return}}},!0),setTimeout(()=>v(),100),window.applyWeekendCollapse=v,n.addEventListener("contextmenu",i=>{if(window._isDragging||window._cancelDrag)return;const p=i.target.closest(".fc-daygrid-day, .fc-timeline-slot, .fc-timegrid-slot, .fc-col-header-cell");if(p){let r=p.getAttribute("data-date");if(!r){const f=i.target.closest("[data-date]");f&&(r=f.getAttribute("data-date"))}if(r&&w){const f=w.view.type;(f==="resourceTimelineWeek"||f==="dayGridMonth")&&(i.preventDefault(),i.stopPropagation(),Swal.fire({title:"📅 Ir a día",text:`¿Quieres ver el día ${r}?`,icon:"question",showCancelButton:!0,confirmButtonText:"Sí, ir al día",cancelButtonText:"Cancelar"}).then(h=>{h.isConfirmed&&(w.changeView("resourceTimeGridDay",r),M())}))}}})}),window.addEventListener("shown.bs.tab",M),window.addEventListener("shown.bs.collapse",M),window.addEventListener("shown.bs.modal",M);function c(){document.querySelectorAll(".resumen-diario-custom").forEach(m=>m.remove())}function d(){if(!w||w.view.type!=="resourceTimeGridDay"){c();return}c();const u=w.getDate(),m=u.getFullYear(),v=String(u.getMonth()+1).padStart(2,"0"),b=String(u.getDate()).padStart(2,"0"),i=`${m}-${v}-${b}`,p=w.getEvents().find(r=>{var f,h;return((f=r.extendedProps)==null?void 0:f.tipo)==="resumen-dia"&&((h=r.extendedProps)==null?void 0:h.fecha)===i});if(p&&p.extendedProps){const r=Number(p.extendedProps.pesoTotal||0).toLocaleString(),f=Number(p.extendedProps.longitudTotal||0).toLocaleString(),h=p.extendedProps.diametroMedio?Number(p.extendedProps.diametroMedio).toFixed(2):null,E=document.createElement("div");E.className="resumen-diario-custom",E.innerHTML=`
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 Peso: ${r} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${f} m</div>
                        ${h?`<div class="text-yellow-800">⌀ Diámetro: ${h} mm</div>`:""}
                    </div>
                </div>
            `,n&&n.parentNode&&n.parentNode.insertBefore(E,n)}}return window.mostrarResumenDiario=d,window.limpiarResumenesCustom=c,w}function tt(e){if(e.view.type==="dayGridMonth"){const a=new Date(e.start);return a.setDate(a.getDate()+15),a.toISOString().split("T")[0]}if(e.view.type==="resourceTimeGridWeek"||e.view.type==="resourceTimelineWeek"){const a=new Date(e.start),t=Math.floor((e.end-e.start)/(1e3*60*60*24)/2);return a.setDate(a.getDate()+t),a.toISOString().split("T")[0]}return e.startStr.split("T")[0]}function at(e,a,t=!1){var s;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para adelantar"});return}if(t){ot(e,a);return}Swal.fire({title:"Analizando opciones...",html:"Calculando la mejor posición para adelantar la fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const o=new AbortController,n=setTimeout(()=>o.abort(),6e4);fetch("/planificacion/simular-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf},body:JSON.stringify({elementos_ids:e,fecha_entrega:a}),signal:o.signal}).then(l=>{if(clearTimeout(n),!l.ok)throw new Error("Error en la simulación");return l.json()}).then(l=>{if(!l.necesita_adelanto){const m=(l.mensaje||"Los elementos llegarán a tiempo.").replace(/\n/g,"<br>").replace(/•/g,'<span class="text-amber-600">•</span>');l.razones&&l.razones.length>0&&l.razones.some(i=>i.fin_minimo)?Swal.fire({icon:"warning",title:"No se puede entregar a tiempo",html:`
                            <div class="text-left text-sm mb-4">${m}</div>
                            <div class="text-left text-sm font-semibold text-amber-700 border-t pt-3">
                                ¿Deseas adelantar a primera posición de todas formas?
                            </div>
                        `,width:650,showCancelButton:!0,confirmButtonText:"Sí, adelantar a 1ª posición",cancelButtonText:"Cancelar",confirmButtonColor:"#f59e0b",cancelButtonColor:"#6b7280"}).then(i=>{if(i.isConfirmed){const p=[];l.razones.filter(r=>r.fin_minimo).forEach(r=>{r.planillas_ids&&r.planillas_ids.length>0?r.planillas_ids.forEach(f=>{p.push({planilla_id:f,maquina_id:r.maquina_id,posicion_nueva:1})}):r.planilla_id&&p.push({planilla_id:r.planilla_id,maquina_id:r.maquina_id,posicion_nueva:1})}),p.length>0?(console.log("Órdenes a adelantar:",p),ue(p)):(console.warn("No se encontraron órdenes para adelantar",l.razones),Swal.fire({icon:"warning",title:"Sin órdenes",text:"No se encontraron órdenes para adelantar."}))}}):Swal.fire({icon:"info",title:"No es necesario adelantar",html:`<div class="text-left text-sm">${m}</div>`,width:600});return}let c="";l.ordenes_a_adelantar&&l.ordenes_a_adelantar.length>0&&(c=`
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
                `,l.ordenes_a_adelantar.forEach(m=>{c+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${m.planilla_codigo}</td>
                            <td class="px-2 py-1">${m.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${m.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${m.posicion_nueva}</td>
                        </tr>
                    `}),c+=`
                                </tbody>
                            </table>
                        </div>
                    </div>
                `);let d="";l.colaterales&&l.colaterales.length>0&&(d=`
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
                `,l.colaterales.forEach(m=>{d+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${m.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${m.obra}</td>
                            <td class="px-2 py-1">${m.fecha_entrega}</td>
                        </tr>
                    `}),d+=`
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-orange-600 mt-1">Estas planillas bajarán una posición en la cola de fabricación.</p>
                    </div>
                `);const u=l.fecha_entrega||"---";Swal.fire({icon:"question",title:"🚀 Adelantar fabricación",html:`
                    <div class="text-left">
                        <p class="mb-3">Para cumplir con la fecha de entrega <strong>${u}</strong>, se propone el siguiente cambio:</p>
                        ${c}
                        ${d}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el adelanto?</p>
                    </div>
                `,width:600,showCancelButton:!0,confirmButtonText:"✅ Ejecutar adelanto",cancelButtonText:"Cancelar",confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280"}).then(m=>{m.isConfirmed&&ue(l.ordenes_a_adelantar)})}).catch(l=>{clearTimeout(n),console.error("Error en simulación:",l);const c=l.name==="AbortError";Swal.fire({icon:"error",title:c?"Tiempo agotado":"Error",text:c?"El cálculo está tardando demasiado. La operación fue cancelada.":"No se pudo simular el adelanto. "+l.message})})}function ue(e){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay órdenes para adelantar"});return}Swal.fire({title:"Ejecutando adelanto...",html:"Actualizando posiciones en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const a=e.map(o=>({planilla_id:o.planilla_id,maquina_id:o.maquina_id,posicion_nueva:o.posicion_nueva}));console.log("Enviando órdenes al servidor:",JSON.stringify({ordenes:a},null,2)),fetch("/planificacion/ejecutar-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({ordenes:a})}).then(o=>{if(!o.ok)throw new Error("Error al ejecutar el adelanto");return o.json()}).then(o=>{if(console.log("Respuesta del servidor:",o),o.success){const n=o.resultados||[],s=n.filter(d=>d.success),l=n.filter(d=>!d.success);let c=o.mensaje||"Las posiciones han sido actualizadas.";s.length>0&&(c+=`<br><br><strong>${s.length} orden(es) movidas correctamente.</strong>`),l.length>0&&(c+=`<br><span class="text-amber-600">${l.length} orden(es) no pudieron moverse:</span>`,c+="<ul class='text-left text-sm mt-2'>",l.forEach(d=>{c+=`<li>• Planilla ${d.planilla_id}: ${d.mensaje}</li>`}),c+="</ul>"),Swal.fire({icon:s.length>0?"success":"warning",title:s.length>0?"¡Adelanto ejecutado!":"Problemas al adelantar",html:c,confirmButtonColor:"#10b981"}).then(()=>{w&&(z(),w.refetchEvents(),w.refetchResources())})}else Swal.fire({icon:"error",title:"Error",text:o.mensaje||"No se pudo ejecutar el adelanto."})}).catch(o=>{console.error("Error al ejecutar adelanto:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el adelanto. "+o.message})})}function ot(e,a){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para adelantar"});return}Swal.fire({title:"Ejecutando adelanto...",html:"Separando elementos y actualizando posiciones en la cola",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}}),fetch("/planificacion/ejecutar-adelanto-elementos",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({elementos_ids:e,nueva_fecha_entrega:a})}).then(o=>{if(!o.ok)throw new Error("Error al ejecutar el adelanto");return o.json()}).then(o=>{if(o.success){const n=o.resultados||[],s=n.filter(d=>d.success),l=n.filter(d=>!d.success);let c=o.mensaje||"Las posiciones han sido actualizadas.";s.length>0&&(c+=`<br><br><strong>${s.length} orden(es) de elementos adelantadas.</strong>`),l.length>0&&(c+=`<br><span class="text-amber-600">${l.length} orden(es) no pudieron moverse.</span>`),Swal.fire({icon:s.length>0?"success":"warning",title:s.length>0?"¡Adelanto ejecutado!":"Problemas al adelantar",html:c,confirmButtonColor:"#10b981"}).then(()=>{w&&(z(),w.refetchEvents(),w.refetchResources())})}else Swal.fire({icon:"error",title:"Error",text:o.mensaje||"No se pudo ejecutar el adelanto."})}).catch(o=>{console.error("Error al ejecutar adelanto de elementos:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el adelanto. "+o.message})})}function nt(e,a=!1,t=null){var o;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para retrasar"});return}Swal.fire({title:"Analizando...",html:"Calculando el impacto del retraso en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}}),fetch("/planificacion/simular-retraso",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(o=window.AppSalidas)==null?void 0:o.csrf},body:JSON.stringify({elementos_ids:e,es_elementos_con_fecha_propia:a})}).then(n=>{if(!n.ok)throw new Error("Error en la simulación");return n.json()}).then(n=>{if(!n.puede_retrasar){Swal.fire({icon:"info",title:"No se puede retrasar",text:n.mensaje||"Las planillas ya están al final de la cola."});return}let s="";n.ordenes_a_retrasar&&n.ordenes_a_retrasar.length>0&&(s=`
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
                `,n.ordenes_a_retrasar.forEach(d=>{s+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${d.planilla_codigo}</td>
                            <td class="px-2 py-1">${d.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${d.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-blue-600">${d.posicion_nueva}</td>
                        </tr>
                    `}),s+=`
                                </tbody>
                            </table>
                        </div>
                    </div>
                `);let l="";n.beneficiados&&n.beneficiados.length>0&&(l=`
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
                `,n.beneficiados.slice(0,10).forEach(d=>{l+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${d.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${d.obra}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${d.posicion_nueva}</td>
                        </tr>
                    `}),l+=`
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-green-600 mt-1">Estas planillas subirán una posición en la cola.</p>
                    </div>
                `);const c=n.es_elementos_con_fecha_propia?"⏬ Retrasar fabricación (Elementos)":"⏬ Retrasar fabricación";Swal.fire({icon:"question",title:c,html:`
                    <div class="text-left">
                        <p class="mb-3">${n.mensaje}</p>
                        ${s}
                        ${l}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el retraso?</p>
                    </div>
                `,width:600,showCancelButton:!0,confirmButtonText:"✅ Ejecutar retraso",cancelButtonText:"Cancelar",confirmButtonColor:"#3b82f6",cancelButtonColor:"#6b7280"}).then(d=>{d.isConfirmed&&st(e,a,t)})}).catch(n=>{console.error("Error en simulación de retraso:",n),Swal.fire({icon:"error",title:"Error",text:"No se pudo simular el retraso. "+n.message})})}function st(e,a=!1,t=null){var n;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para retrasar"});return}Swal.fire({title:"Ejecutando retraso...",html:"Actualizando posiciones en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const o={elementos_ids:e,es_elementos_con_fecha_propia:a};a&&t&&(o.nueva_fecha_entrega=t),fetch("/planificacion/ejecutar-retraso",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(n=window.AppSalidas)==null?void 0:n.csrf},body:JSON.stringify(o)}).then(s=>{if(!s.ok)throw new Error("Error al ejecutar el retraso");return s.json()}).then(s=>{if(s.success){const l=s.resultados||[],c=l.filter(m=>m.success),d=l.filter(m=>!m.success);let u=s.mensaje||"Las posiciones han sido actualizadas.";c.length>0&&(u+=`<br><br><strong>${c.length} planilla(s) movidas al final de la cola.</strong>`),d.length>0&&(u+=`<br><span class="text-amber-600">${d.length} orden(es) no pudieron moverse:</span>`,u+="<ul class='text-left text-sm mt-2'>",d.forEach(m=>{u+=`<li>• Planilla ${m.planilla_id}: ${m.mensaje}</li>`}),u+="</ul>"),Swal.fire({icon:c.length>0?"success":"warning",title:c.length>0?"¡Retraso ejecutado!":"Problemas al retrasar",html:u,confirmButtonColor:"#3b82f6"}).then(()=>{w&&(z(),w.refetchEvents(),w.refetchResources())})}else Swal.fire({icon:"error",title:"Error",text:s.mensaje||"No se pudo ejecutar el retraso."})}).catch(s=>{console.error("Error al ejecutar retraso:",s),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el retraso. "+s.message})})}function rt(e,a={}){const{selector:t=null,once:o=!1}=a;let n=!1;const s=()=>{t&&!document.querySelector(t)||o&&n||(n=!0,e())};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",s):s(),document.addEventListener("livewire:navigated",s)}function it(e){document.addEventListener("livewire:navigating",e)}function lt(e){let t=new Date(e).toLocaleDateString("es-ES",{month:"long",year:"numeric"});return`(${t.charAt(0).toUpperCase()+t.slice(1)})`}function dt(e){const a=new Date(e),t=a.getDay(),o=t===0?-6:1-t,n=new Date(a);n.setDate(a.getDate()+o);const s=new Date(n);s.setDate(n.getDate()+6);const l=new Intl.DateTimeFormat("es-ES",{day:"2-digit",month:"short"}),c=new Intl.DateTimeFormat("es-ES",{year:"numeric"});return`(${l.format(n)} – ${l.format(s)} ${c.format(s)})`}function ct(e){var s,l;const a=document.querySelector("#resumen-semanal-fecha"),t=document.querySelector("#resumen-mensual-fecha");a&&(a.textContent=dt(e)),t&&(t.textContent=lt(e));const o=(l=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:l.totales;if(!o)return;const n=`${o}?fecha=${encodeURIComponent(e)}`;fetch(n).then(c=>c.json()).then(c=>{const d=c.semana||{},u=c.mes||{};document.querySelector("#resumen-semanal-peso").textContent=`📦 ${Number(d.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-semanal-longitud").textContent=`📏 ${Number(d.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-semanal-diametro").textContent=d.diametro!=null?`⌀ ${Number(d.diametro).toFixed(2)} mm`:"",document.querySelector("#resumen-mensual-peso").textContent=`📦 ${Number(u.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-mensual-longitud").textContent=`📏 ${Number(u.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-mensual-diametro").textContent=u.diametro!=null?`⌀ ${Number(u.diametro).toFixed(2)} mm`:""}).catch(c=>console.error("❌ Totales:",c))}let B;function ut(){var h,E;if(window.calendar)try{window.calendar.destroy()}catch(y){console.warn("Error al destruir calendario anterior:",y)}const e=et();B=e,window.calendar=e,e.refetchResources(),e.refetchEvents(),(h=document.getElementById("ver-con-salidas"))==null||h.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()}),(E=document.getElementById("ver-todas"))==null||E.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()});const t=(localStorage.getItem("fechaCalendario")||new Date().toISOString()).split("T")[0];ct(t);const o=localStorage.getItem("soloSalidas")==="true",n=localStorage.getItem("soloPlanillas")==="true",s=document.getElementById("solo-salidas"),l=document.getElementById("solo-planillas");s&&(s.checked=o),l&&(l.checked=n);const c=document.getElementById("filtro-obra"),d=document.getElementById("filtro-nombre-obra"),u=document.getElementById("filtro-cod-cliente"),m=document.getElementById("filtro-cliente"),v=document.getElementById("filtro-cod-planilla"),b=document.getElementById("btn-reset-filtros"),i=document.getElementById("btn-limpiar-filtros");b==null||b.addEventListener("click",()=>{c&&(c.value=""),d&&(d.value=""),u&&(u.value=""),m&&(m.value=""),v&&(v.value=""),s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),l&&(l.checked=!1,localStorage.setItem("soloPlanillas","false")),f(),document.querySelectorAll(".fc-event.evento-filtrado, .fc-event.evento-atenuado").forEach(y=>{y.classList.remove("evento-filtrado","evento-atenuado")}),z(),B.refetchEvents()});const r=((y,S=150)=>{let C;return(...x)=>{clearTimeout(C),C=setTimeout(()=>y(...x),S)}})(()=>{B.refetchEvents()},120);c==null||c.addEventListener("input",r),d==null||d.addEventListener("input",r),u==null||u.addEventListener("input",r),m==null||m.addEventListener("input",r),v==null||v.addEventListener("input",r);function f(){const y=s==null?void 0:s.closest(".checkbox-container"),S=l==null?void 0:l.closest(".checkbox-container");y==null||y.classList.remove("active-salidas"),S==null||S.classList.remove("active-planillas"),s!=null&&s.checked&&(y==null||y.classList.add("active-salidas")),l!=null&&l.checked&&(S==null||S.classList.add("active-planillas"))}s==null||s.addEventListener("change",y=>{y.target.checked&&l&&(l.checked=!1,localStorage.setItem("soloPlanillas","false")),localStorage.setItem("soloSalidas",y.target.checked.toString()),f(),B.refetchEvents()}),l==null||l.addEventListener("change",y=>{y.target.checked&&s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),localStorage.setItem("soloPlanillas",y.target.checked.toString()),f(),B.refetchEvents()}),f(),i==null||i.addEventListener("click",()=>{c&&(c.value=""),d&&(d.value=""),u&&(u.value=""),m&&(m.value=""),v&&(v.value=""),document.querySelectorAll(".fc-event.evento-filtrado, .fc-event.evento-atenuado").forEach(y=>{y.classList.remove("evento-filtrado","evento-atenuado")}),z(),B.refetchEvents()})}let R=null,j=null,ae="days",N=-1,H=[];function pt(){j&&j();const e=window.calendar;if(!e)return;R=e.getDate(),ae="days",N=-1,yt();function a(t){var l;const o=t.target.tagName.toLowerCase();if(o==="input"||o==="textarea"||t.target.isContentEditable||document.querySelector(".swal2-container"))return;const n=window.calendar;if(!n)return;let s=!1;switch(t.key){case"ArrowLeft":n.prev(),s=!0;break;case"ArrowRight":n.next(),s=!0;break;case"t":case"T":n.today(),s=!0;break;case"Escape":window.isFullScreen&&((l=window.toggleFullScreen)==null||l.call(window),s=!0);break}s&&(t.preventDefault(),t.stopPropagation())}document.addEventListener("keydown",a,!0),e.on("eventsSet",()=>{ae==="events"&&(mt(),ft())}),j=()=>{document.removeEventListener("keydown",a,!0),ve(),he()}}function mt(){const e=window.calendar;if(!e){H=[];return}H=e.getEvents().filter(a=>{var o;const t=(o=a.extendedProps)==null?void 0:o.tipo;return t!=="resumen-dia"&&t!=="festivo"}).sort((a,t)=>{const o=a.start||new Date(0),n=t.start||new Date(0);return o<n?-1:o>n?1:(a.title||"").localeCompare(t.title||"")})}function ft(){var t;if(he(),N<0||N>=H.length)return;const e=H[N];if(!e)return;const a=document.querySelector(`[data-event-id="${e.id}"]`)||document.querySelector(`.fc-event[data-event="${e.id}"]`);if(a)a.classList.add("keyboard-focused-event"),a.scrollIntoView({behavior:"smooth",block:"nearest"});else{const o=document.querySelectorAll(".fc-event");for(const n of o)if(n.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){n.classList.add("keyboard-focused-event"),n.scrollIntoView({behavior:"smooth",block:"nearest"});break}}e.start&&(R=new Date(e.start)),be()}function he(){document.querySelectorAll(".keyboard-focused-event").forEach(e=>{e.classList.remove("keyboard-focused-event")})}function gt(e){const a=e.getFullYear(),t=String(e.getMonth()+1).padStart(2,"0"),o=String(e.getDate()).padStart(2,"0");return`${a}-${t}-${o}`}function yt(){if(ve(),!R)return;const e=gt(R),a=window.calendar;if(!a)return;const t=a.view.type;let o=null;t==="dayGridMonth"?o=document.querySelector(`.fc-daygrid-day[data-date="${e}"]`):t==="resourceTimelineWeek"?(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(s=>{s.dataset.date&&s.dataset.date.startsWith(e)&&(o=s)}),o||(o=document.querySelector(`.fc-timeline-slot-lane[data-date^="${e}"]`))):t==="resourceTimeGridDay"&&(o=document.querySelector(".fc-col-header-cell")),o&&(o.classList.add("keyboard-focused-day"),o.scrollIntoView({behavior:"smooth",block:"nearest",inline:"nearest"})),be()}function ve(){document.querySelectorAll(".keyboard-focused-day").forEach(e=>{e.classList.remove("keyboard-focused-day")})}function be(){let e=document.getElementById("keyboard-nav-indicator");if(e||(e=document.createElement("div"),e.id="keyboard-nav-indicator",e.className="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm",document.body.appendChild(e)),ae==="events"){const a=H[N],t=(a==null?void 0:a.title)||"Sin evento",o=`${N+1}/${H.length}`;e.innerHTML=`
            <div class="flex items-center gap-2">
                <span class="bg-green-500 text-white text-xs px-2 py-0.5 rounded">EVENTOS</span>
                <span class="font-medium truncate max-w-[200px]">${t}</span>
                <span class="text-gray-400">${o}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1 flex gap-3">
                <span>↑↓ Navegar</span>
                <span>Enter Abrir</span>
                <span>E Menú</span>
                <span>I Info</span>
                <span>Tab/Esc Días</span>
            </div>
        `}else{const a=R?R.toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short",year:"numeric"}):"";e.innerHTML=`
            <div class="flex items-center gap-2">
                <span class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded">DÍAS</span>
                <span class="opacity-75">📅</span>
                <span id="keyboard-nav-date">${a}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1 flex gap-3">
                <span>← → ↑ ↓</span>
                <span>Enter Vista día</span>
                <span>T Hoy</span>
                <span>Tab Eventos</span>
            </div>
        `}clearTimeout(e._hideTimeout),e.style.display="block",e._hideTimeout=setTimeout(()=>{e.style.display="none"},4e3)}function ht(){if(document.getElementById("keyboard-nav-styles"))return;const e=document.createElement("style");e.id="keyboard-nav-styles",e.textContent=`
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
    `,document.head.appendChild(e)}rt(()=>{ut(),ht(),setTimeout(()=>{pt()},500)},{selector:'#calendario[data-calendar-type="salidas"]'});it(()=>{if(j&&(j(),j=null),window.calendar)try{window.calendar.destroy(),window.calendar=null}catch(a){console.warn("Error al limpiar calendario de salidas:",a)}const e=document.getElementById("keyboard-nav-indicator");e&&e.remove()});
