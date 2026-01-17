async function ve(e,a){var t,o,n,s;try{const c=(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.planificacion;if(!c)return[];const l=new URLSearchParams({tipo:"events",viewType:e||"",start:a.startStr||"",end:a.endStr||"",t:Date.now()}),d=await fetch(`${c}?${l.toString()}`);if(!d.ok)return console.error("Error eventos",d.status),[];const p=await d.json();let g=Array.isArray(p)?p:Array.isArray(p==null?void 0:p.events)?p.events:[];const x=((n=document.getElementById("solo-salidas"))==null?void 0:n.checked)||!1,b=((s=document.getElementById("solo-planillas"))==null?void 0:s.checked)||!1,r=g.filter(m=>{var f;return((f=m.extendedProps)==null?void 0:f.tipo)==="resumen-dia"}),u=g.filter(m=>{var f;return((f=m.extendedProps)==null?void 0:f.tipo)!=="resumen-dia"});let i=u;return x&&!b?i=u.filter(m=>{var h;return((h=m.extendedProps)==null?void 0:h.tipo)==="salida"}):b&&!x&&(i=u.filter(m=>{var h;const f=(h=m.extendedProps)==null?void 0:h.tipo;return f==="planilla"||f==="festivo"})),[...i,...r]}catch(c){return console.error("fetch eventos falló:",c),[]}}async function xe(e,a){var c,l;const t=(l=(c=window.AppSalidas)==null?void 0:c.routes)==null?void 0:l.planificacion;if(!t)return[];const o=new URLSearchParams({tipo:"resources",viewType:e,start:a.startStr||"",end:a.endStr||""}),n=await fetch(`${t}?${o.toString()}`,{method:"GET"});if(!n.ok)throw new Error("Error cargando recursos");const s=await n.json();return Array.isArray(s)?s:Array.isArray(s==null?void 0:s.resources)?s.resources:[]}function te(e,a){const t=e.event.extendedProps||{};if(t.tipo!=="festivo"){if(t.tipo==="planilla"){const o=`
      ✅ Fabricados: ${Z(t.fabricadosKg)} kg<br>
      🔄 Fabricando: ${Z(t.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${Z(t.pendientesKg)} kg
    `;tippy(e.el,{content:o,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}t.tipo==="salida"&&t.comentario&&t.comentario.trim()&&tippy(e.el,{content:t.comentario,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}}function Z(e){return e!=null?Number(e).toLocaleString():0}let U=null;function P(){U&&(U.remove(),U=null,document.removeEventListener("click",P),document.removeEventListener("contextmenu",P,!0),document.removeEventListener("scroll",P,!0),window.removeEventListener("resize",P),window.removeEventListener("keydown",de))}function de(e){e.key==="Escape"&&P()}function we(e,a,t){P();const o=document.createElement("div");o.className="fc-contextmenu",Object.assign(o.style,{position:"fixed",top:a+"px",left:e+"px",zIndex:99999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",fontFamily:"system-ui, -apple-system, Segoe UI, Roboto, sans-serif"}),o.innerHTML=t,document.body.appendChild(o),U=o;const n=o.getBoundingClientRect(),s=Math.max(0,n.right-window.innerWidth+8),c=Math.max(0,n.bottom-window.innerHeight+8);return(s||c)&&(o.style.left=Math.max(8,e-s)+"px",o.style.top=Math.max(8,a-c)+"px"),setTimeout(()=>{document.addEventListener("click",P),document.addEventListener("contextmenu",P,!0),document.addEventListener("scroll",P,!0),window.addEventListener("resize",P),window.addEventListener("keydown",de)},0),o}function Se(e,a,{headerHtml:t="",items:o=[]}={}){const n=`
    <div class="ctx-menu-container">
      ${t?`<div class="ctx-menu-header">${t}</div>`:""}
      ${o.map((c,l)=>`
        <button type="button"
          class="ctx-menu-item${c.danger?" ctx-menu-danger":""}"
          data-idx="${l}">
          ${c.icon?`<span class="ctx-menu-icon">${c.icon}</span>`:""}
          <span class="ctx-menu-label">${c.label}</span>
        </button>`).join("")}
    </div>
  `,s=we(e,a,n);return s.querySelectorAll(".ctx-menu-item").forEach(c=>{c.addEventListener("click",async l=>{var g;l.preventDefault(),l.stopPropagation();const d=Number(c.dataset.idx),p=(g=o[d])==null?void 0:g.onClick;P();try{await(p==null?void 0:p())}catch(x){console.error(x)}})}),s}function Ee(e){if(!e||typeof e!="string")return"";const a=e.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);if(a){const t=a[1],o=a[2].padStart(2,"0"),n=a[3].padStart(2,"0");return`${t}-${o}-${n}`}return e}(function(){if(document.getElementById("swal-anims"))return;const a=document.createElement("style");a.id="swal-anims",a.textContent=`
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
  `,document.head.appendChild(a)})();function ke(e){const a=e.extendedProps||{};if(Array.isArray(a.planillas_ids)&&a.planillas_ids.length)return a.planillas_ids;const t=(e.id||"").match(/planilla-(\d+)/);return t?[Number(t[1])]:[]}async function ce(e,a){var t,o;try{P()}catch{}if(!e)return Swal.fire("⚠️","ID de salida inválido.","warning");try{const n=await fetch(`${(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.informacionPaquetesSalida}?salida_id=${e}`,{headers:{Accept:"application/json"}});if(!n.ok)throw new Error("Error al cargar información de la salida");const{salida:s,paquetesAsignados:c,paquetesDisponibles:l,paquetesTodos:d,filtros:p}=await n.json();$e(s,c,l,d||[],p||{obras:[],planillas:[],obrasRelacionadas:[]},a)}catch(n){console.error(n),Swal.fire("❌","Error al cargar la información de la salida","error")}}function $e(e,a,t,o,n,s){window._gestionPaquetesData={salida:e,paquetesAsignados:a,paquetesDisponibles:t,paquetesTodos:o,filtros:n,mostrarTodos:!1};const c=qe(e,a,t,n);Swal.fire({title:`📦 Gestionar Paquetes - Salida ${e.codigo_salida||e.id}`,html:c,width:Math.min(window.innerWidth*.95,1200),showConfirmButton:!0,showCancelButton:!0,confirmButtonText:"💾 Guardar Cambios",cancelButtonText:"Cancelar",focusConfirm:!1,customClass:{popup:"w-full max-w-screen-xl"},didOpen:()=>{me(),Te(),Pe(),setTimeout(()=>{De()},100)},willClose:()=>{q.cleanup&&q.cleanup();const l=document.getElementById("modal-keyboard-indicator");l&&l.remove()},preConfirm:()=>Me()}).then(async l=>{l.isConfirmed&&l.value&&await _e(e.id,l.value,s)})}function qe(e,a,t,o){var p,g;const n=a.reduce((x,b)=>x+(parseFloat(b.peso)||0),0);let s="";e.salida_clientes&&e.salida_clientes.length>0&&(s='<div class="col-span-2"><strong>Obras/Clientes:</strong><br>',e.salida_clientes.forEach(x=>{var i,m,f,h,S;const b=((i=x.obra)==null?void 0:i.obra)||"Obra desconocida",r=(m=x.obra)!=null&&m.cod_obra?`(${x.obra.cod_obra})`:"",u=((f=x.cliente)==null?void 0:f.empresa)||((S=(h=x.obra)==null?void 0:h.cliente)==null?void 0:S.empresa)||"";s+=`<span class="text-xs">• ${b} ${r}`,u&&(s+=` - ${u}`),s+="</span><br>"}),s+="</div>");const c=`
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><strong>Código:</strong> ${e.codigo_salida||"N/A"}</div>
                <div><strong>Código SAGE:</strong> ${e.codigo_sage||"Sin asignar"}</div>
                <div><strong>Fecha salida:</strong> ${new Date(e.fecha_salida).toLocaleString("es-ES")}</div>
                <div><strong>Estado:</strong> ${e.estado||"pendiente"}</div>
                <div><strong>Empresa transporte:</strong> ${((p=e.empresa_transporte)==null?void 0:p.nombre)||"Sin asignar"}</div>
                <div><strong>Camión:</strong> ${((g=e.camion)==null?void 0:g.modelo)||"Sin asignar"}</div>
                ${s}
            </div>
        </div>
    `,l=((o==null?void 0:o.obras)||[]).map(x=>`<option value="${x.id}">${x.cod_obra||""} - ${x.obra||"Sin nombre"}</option>`).join(""),d=((o==null?void 0:o.planillas)||[]).map(x=>`<option value="${x.id}" data-obra-id="${x.obra_id||""}">${x.codigo||"Sin código"}</option>`).join("");return`
        <div class="text-left">
            ${c}

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
                                    ${l}
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
    `}function ee(e){return!e||e.length===0?'<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>':e.map(a=>{var t,o,n,s,c,l,d,p,g,x,b,r,u,i,m,f;return`
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${a.id}"
            data-peso="${a.peso||0}"
            data-obra-id="${((o=(t=a.planilla)==null?void 0:t.obra)==null?void 0:o.id)||""}"
            data-obra="${((s=(n=a.planilla)==null?void 0:n.obra)==null?void 0:s.obra)||""}"
            data-planilla-id="${((c=a.planilla)==null?void 0:c.id)||""}"
            data-planilla="${((l=a.planilla)==null?void 0:l.codigo)||""}"
            data-cliente="${((p=(d=a.planilla)==null?void 0:d.cliente)==null?void 0:p.empresa)||""}"
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
                <div>📄 ${((g=a.planilla)==null?void 0:g.codigo)||a.planilla_id}</div>
                <div>🏗️ ${((b=(x=a.planilla)==null?void 0:x.obra)==null?void 0:b.cod_obra)||""} - ${((u=(r=a.planilla)==null?void 0:r.obra)==null?void 0:u.obra)||"N/A"}</div>
                <div>👤 ${((m=(i=a.planilla)==null?void 0:i.cliente)==null?void 0:m.empresa)||"Sin cliente"}</div>
                ${(f=a.nave)!=null&&f.obra?`<div class="text-blue-600 font-medium">📍 ${a.nave.obra}</div>`:""}
            </div>
        </div>
    `}).join("")}async function Ce(e){var a;try{const t=document.querySelector(`[data-paquete-id="${e}"]`);let o=null;if(t&&t.dataset.paqueteJson)try{o=JSON.parse(t.dataset.paqueteJson.replace(/&#39;/g,"'"))}catch(d){console.warn("No se pudo parsear JSON del paquete",d)}if(!o){const d=await fetch(`/api/paquetes/${e}/elementos`);d.ok&&(o=await d.json())}if(!o){alert("No se pudo obtener información del paquete");return}const n=[];if(o.etiquetas&&o.etiquetas.length>0&&o.etiquetas.forEach(d=>{d.elementos&&d.elementos.length>0&&d.elementos.forEach(p=>{n.push({id:p.id,dimensiones:p.dimensiones,peso:p.peso,longitud:p.longitud,diametro:p.diametro})})}),n.length===0){alert("Este paquete no tiene elementos para mostrar");return}const s=n.map((d,p)=>`
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">Elemento #${d.id}</span>
                    <span class="text-xs text-gray-500">${p+1} de ${n.length}</span>
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
        `).join(""),c=document.getElementById("modal-elementos-paquete-overlay");c&&c.remove();const l=`
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
        `;document.body.insertAdjacentHTML("beforeend",l),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&n.forEach(d=>{d.dimensiones&&window.dibujarFiguraElemento(`elemento-dibujo-${d.id}`,d.dimensiones,null)})},100)}catch(t){console.error("Error al ver elementos del paquete:",t),alert("Error al cargar los elementos del paquete")}}window.verElementosPaqueteSalida=Ce;function Te(e){const a=document.getElementById("filtro-obra-modal"),t=document.getElementById("filtro-planilla-modal"),o=document.getElementById("btn-limpiar-filtros-modal");a&&a.addEventListener("change",()=>{ae(),Q()}),t&&t.addEventListener("change",()=>{Q()}),o&&o.addEventListener("click",()=>{a&&(a.value=""),t&&(t.value=""),ae(),Q()})}function ae(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData;if(!a||!t)return;const o=(e==null?void 0:e.value)||"",n=o?t.paquetesTodos:t.paquetesDisponibles,s=new Map;n.forEach(d=>{var p,g,x;if((p=d.planilla)!=null&&p.id){if(o&&String((g=d.planilla.obra)==null?void 0:g.id)!==o)return;s.has(d.planilla.id)||s.set(d.planilla.id,{id:d.planilla.id,codigo:d.planilla.codigo||"Sin código",obra_id:(x=d.planilla.obra)==null?void 0:x.id})}});const c=Array.from(s.values()).sort((d,p)=>(d.codigo||"").localeCompare(p.codigo||"")),l=a.value;a.innerHTML='<option value="">-- Todas las planillas --</option>',c.forEach(d=>{const p=document.createElement("option");p.value=d.id,p.textContent=d.codigo,a.appendChild(p)}),l&&s.has(parseInt(l))?a.value=l:a.value=""}function Q(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData,o=(e==null?void 0:e.value)||"",n=(a==null?void 0:a.value)||"",s=document.querySelector('[data-zona="disponibles"]');if(!s||!t)return;const c=document.querySelector('[data-zona="asignados"]'),l=new Set;c&&c.querySelectorAll(".paquete-item-salida").forEach(g=>{l.add(parseInt(g.dataset.paqueteId))});let p=(o?t.paquetesTodos:t.paquetesDisponibles).filter(g=>{var x,b,r;return!(l.has(g.id)||o&&String((b=(x=g.planilla)==null?void 0:x.obra)==null?void 0:b.id)!==o||n&&String((r=g.planilla)==null?void 0:r.id)!==n)});s.innerHTML=ee(p),me(),p.length===0&&(s.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">No hay paquetes que coincidan con el filtro</div>')}let q={zonaActiva:"asignados",indiceFocused:-1,cleanup:null};function De(){q.cleanup&&q.cleanup(),q.zonaActiva="asignados",q.indiceFocused=0,z();function e(a){var x;if(!document.querySelector(".swal2-container"))return;const t=a.target.tagName.toLowerCase(),o=t==="select";if((t==="input"||t==="textarea")&&a.key!=="Escape")return;const s=document.querySelector('[data-zona="asignados"]'),c=document.querySelector('[data-zona="disponibles"]');if(!s||!c)return;const l=q.zonaActiva==="asignados"?s:c,d=Array.from(l.querySelectorAll('.paquete-item-salida:not([style*="display: none"])')),p=d.length;let g=!1;if(!o)switch(a.key){case"ArrowDown":p>0&&(q.indiceFocused=(q.indiceFocused+1)%p,z(),g=!0);break;case"ArrowUp":p>0&&(q.indiceFocused=q.indiceFocused<=0?p-1:q.indiceFocused-1,z(),g=!0);break;case"ArrowLeft":case"ArrowRight":q.zonaActiva=q.zonaActiva==="asignados"?"disponibles":"asignados",q.indiceFocused=0,z(),g=!0;break;case"Tab":a.preventDefault(),q.zonaActiva=q.zonaActiva==="asignados"?"disponibles":"asignados",q.indiceFocused=0,z(),g=!0;break;case"Enter":{if(p>0&&q.indiceFocused>=0){const b=d[q.indiceFocused];if(b){Le(b);const r=Array.from(l.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));q.indiceFocused>=r.length&&(q.indiceFocused=Math.max(0,r.length-1)),z(),g=!0}}break}case"Home":q.indiceFocused=0,z(),g=!0;break;case"End":q.indiceFocused=Math.max(0,p-1),z(),g=!0;break}if(g){a.preventDefault(),a.stopPropagation();return}switch(a.key){case"o":case"O":{const b=document.getElementById("filtro-obra-modal");b&&(b.focus(),g=!0);break}case"p":case"P":{const b=document.getElementById("filtro-planilla-modal");b&&(b.focus(),g=!0);break}case"l":case"L":{const b=document.getElementById("btn-limpiar-filtros-modal");b&&(b.click(),(x=document.activeElement)==null||x.blur(),z(),g=!0);break}case"/":case"f":case"F":{const b=document.getElementById("filtro-obra-modal");b&&(b.focus(),g=!0);break}case"Escape":o&&(document.activeElement.blur(),z(),g=!0);break;case"s":case"S":{if(a.ctrlKey||a.metaKey){const b=document.querySelector(".swal2-confirm");b&&(b.click(),g=!0)}break}}g&&(a.preventDefault(),a.stopPropagation())}document.addEventListener("keydown",e,!0),q.cleanup=()=>{document.removeEventListener("keydown",e,!0),ue()}}function z(){ue();const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;q.zonaActiva==="asignados"?(e.classList.add("zona-activa-keyboard"),a.classList.remove("zona-activa-keyboard")):(a.classList.add("zona-activa-keyboard"),e.classList.remove("zona-activa-keyboard"));const t=q.zonaActiva==="asignados"?e:a,o=Array.from(t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));if(o.length>0&&q.indiceFocused>=0){const n=Math.min(q.indiceFocused,o.length-1),s=o[n];s&&(s.classList.add("paquete-focused-keyboard"),s.scrollIntoView({behavior:"smooth",block:"nearest"}))}Ae()}function ue(){document.querySelectorAll(".paquete-focused-keyboard").forEach(e=>{e.classList.remove("paquete-focused-keyboard")}),document.querySelectorAll(".zona-activa-keyboard").forEach(e=>{e.classList.remove("zona-activa-keyboard")})}function Le(e){const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!a||!t)return;const o=e.closest("[data-zona]"),n=o.dataset.zona==="asignados"?t:a,s=n.querySelector(".placeholder-sin-paquetes");if(s&&s.remove(),n.appendChild(e),o.querySelectorAll(".paquete-item-salida").length===0){const l=document.createElement("div");l.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",l.textContent="Sin paquetes",o.appendChild(l)}pe(e),J()}function Ae(){let e=document.getElementById("modal-keyboard-indicator");e||(e=document.createElement("div"),e.id="modal-keyboard-indicator",e.className="fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs",document.body.appendChild(e));const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]'),o=(a==null?void 0:a.querySelectorAll(".paquete-item-salida").length)||0,n=(t==null?void 0:t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0,s=q.zonaActiva==="asignados"?`📦 Asignados (${o})`:`📋 Disponibles (${n})`;e.innerHTML=`
        <div class="flex items-center gap-2 mb-2">
            <span class="${q.zonaActiva==="asignados"?"bg-green-500":"bg-gray-500"} text-white text-xs px-2 py-0.5 rounded">${s}</span>
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
    `,clearTimeout(e._checkTimeout),e._checkTimeout=setTimeout(()=>{document.querySelector(".swal2-container")||e.remove()},500)}function Pe(){if(document.getElementById("modal-keyboard-styles"))return;const e=document.createElement("style");e.id="modal-keyboard-styles",e.textContent=`
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
    `,document.head.appendChild(e)}function pe(e){e.addEventListener("dragstart",a=>{e.style.opacity="0.5",a.dataTransfer.setData("text/plain",e.dataset.paqueteId)}),e.addEventListener("dragend",a=>{e.style.opacity="1"})}function me(){document.querySelectorAll(".paquete-item-salida").forEach(e=>{pe(e)}),document.querySelectorAll(".drop-zone").forEach(e=>{e.addEventListener("dragover",a=>{a.preventDefault();const t=e.dataset.zona;e.style.backgroundColor=t==="asignados"?"#d1fae5":"#e0f2fe"}),e.addEventListener("dragleave",a=>{e.style.backgroundColor=""}),e.addEventListener("drop",a=>{a.preventDefault(),e.style.backgroundColor="";const t=a.dataTransfer.getData("text/plain"),o=document.querySelector(`.paquete-item-salida[data-paquete-id="${t}"]`);if(o){const n=e.querySelector(".placeholder-sin-paquetes");n&&n.remove(),e.appendChild(o),J()}})})}function J(){const e=document.querySelector('[data-zona="asignados"]'),a=e==null?void 0:e.querySelectorAll(".paquete-item-salida");let t=0;a==null||a.forEach(n=>{const s=parseFloat(n.dataset.peso)||0;t+=s});const o=document.getElementById("peso-asignados");o&&(o.textContent=`${t.toFixed(2)} kg`)}function Ie(){const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;const t=e.querySelectorAll(".paquete-item-salida");if(t.length===0)return;t.forEach(n=>{a.appendChild(n)});const o=a.querySelector(".placeholder-sin-paquetes");o&&o.remove(),e.querySelectorAll(".paquete-item-salida").length===0&&(e.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),J()}function Fe(){const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;const t=Array.from(a.querySelectorAll(".paquete-item-salida")).filter(s=>s.style.display!=="none");if(t.length===0)return;const o=e.querySelector(".placeholder-sin-paquetes");o&&o.remove(),t.forEach(s=>{e.appendChild(s)}),a.querySelectorAll(".paquete-item-salida").length===0&&(a.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),J()}window.vaciarSalidaModal=Ie;window.volcarTodosASalidaModal=Fe;function Me(){const e=document.querySelector('[data-zona="asignados"]');return{paquetes_ids:Array.from((e==null?void 0:e.querySelectorAll(".paquete-item-salida"))||[]).map(t=>parseInt(t.dataset.paqueteId))}}async function _e(e,a,t){var o,n,s,c;try{const d=await(await fetch((n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.guardarPaquetesSalida,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf},body:JSON.stringify({salida_id:e,paquetes_ids:a.paquetes_ids})})).json();d.success?(await Swal.fire({icon:"success",title:"✅ Cambios Guardados",text:"Los paquetes de la salida se han actualizado correctamente.",timer:2e3}),t&&(t.refetchEvents(),(c=t.refetchResources)==null||c.call(t))):await Swal.fire("⚠️",d.message||"No se pudieron guardar los cambios","warning")}catch(l){console.error(l),Swal.fire("❌","Error al guardar los paquetes","error")}}async function ze(e,a,t){try{P()}catch{}window.Livewire.dispatch("abrirComentario",{salidaId:e}),window._calendarRef=t}function Ne(e){return e?typeof e=="string"?e.split(",").map(t=>t.trim()).filter(Boolean):Array.from(e).map(t=>typeof t=="object"&&(t==null?void 0:t.id)!=null?t.id:t).map(String).map(t=>t.trim()).filter(Boolean):[]}async function Be(e){var s,c;const a=(c=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:c.informacionPlanillas;if(!a)throw new Error("Ruta 'informacionPlanillas' no configurada");const t=`${a}?ids=${encodeURIComponent(e.join(","))}`,o=await fetch(t,{headers:{Accept:"application/json"}});if(!o.ok){const l=await o.text().catch(()=>"");throw new Error(`GET ${t} -> ${o.status} ${l}`)}const n=await o.json();return Array.isArray(n==null?void 0:n.planillas)?n.planillas:[]}function fe(e){if(!e)return!1;const t=new Date(e+"T00:00:00").getDay();return t===0||t===6}function je(e,a,t,o){const n=document.getElementById("modal-figura-elemento-overlay");n&&n.remove();const s=o.getBoundingClientRect(),c=320,l=240;let d=s.right+10;d+c>window.innerWidth&&(d=s.left-c-10);let p=s.top-l/2+s.height/2;p<10&&(p=10),p+l>window.innerHeight-10&&(p=window.innerHeight-l-10);const g=`
        <div id="modal-figura-elemento-overlay"
             class="fixed bg-white rounded-lg shadow-2xl border border-gray-300"
             style="z-index: 10001; left: ${d}px; top: ${p}px; width: ${c}px;"
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
    `;document.body.insertAdjacentHTML("beforeend",g),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&window.dibujarFiguraElemento(`figura-elemento-container-${e}`,t,null)},50)}function Oe(e){return`
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
          <tbody>${e.map((t,o)=>{var r,u,i;const n=((r=t.obra)==null?void 0:r.codigo)||"",s=((u=t.obra)==null?void 0:u.nombre)||"",c=t.seccion||"";t.descripcion;const l=t.codigo||`Planilla ${t.id}`,d=t.peso_total?parseFloat(t.peso_total).toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})+" kg":"",p=Ee(t.fecha_estimada_entrega),g=t.elementos&&t.elementos.length>0,x=((i=t.elementos)==null?void 0:i.length)||0;let b="";return g&&(b=t.elementos.map((m,f)=>{const h=m.fecha_entrega||"",S=m.peso?parseFloat(m.peso).toFixed(2):"-",k=m.codigo||"-",E=m.dimensiones&&m.dimensiones.trim()!=="",w=E?m.dimensiones.replace(/"/g,"&quot;").replace(/'/g,"&#39;"):"",C=k.replace(/"/g,"&quot;").replace(/'/g,"&#39;");return`
                    <tr class="elemento-row elemento-planilla-${t.id} bg-gray-50 hidden">
                        <td class="px-2 py-1 text-xs text-gray-400 pl-4">
                            <div class="flex items-center gap-1">
                                <input type="checkbox" class="elemento-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-3.5 w-3.5"
                                       data-elemento-id="${m.id}"
                                       data-planilla-id="${t.id}">
                                <span>↳</span>
                                <span class="font-medium text-gray-600">${k}</span>
                                ${E?`
                                <button type="button"
                                        class="ver-figura-elemento text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded p-0.5 transition-colors"
                                        data-elemento-id="${m.id}"
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
                        <td class="px-2 py-1 text-xs text-gray-500">${m.marca||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">Ø${m.diametro||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${m.longitud||"-"} mm</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${m.barras||"-"} uds</td>
                        <td class="px-2 py-1 text-xs text-right text-gray-500">${S} kg</td>
                        <td class="px-2 py-1" colspan="2">
                            <input type="date" class="swal2-input !m-0 !w-auto !text-xs elemento-fecha"
                                   data-elemento-id="${m.id}"
                                   data-planilla-id="${t.id}"
                                   value="${h}">
                        </td>
                    </tr>`}).join("")),`
<tr class="planilla-row hover:bg-blue-50 bg-blue-100 border-t border-blue-200" data-planilla-id="${t.id}" style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${o*18}ms;">
  <td class="px-2 py-2 text-xs font-semibold text-blue-800" colspan="2">
    ${g?`<button type="button" class="toggle-elementos mr-1 text-blue-600 hover:text-blue-800" data-planilla-id="${t.id}">▶</button>`:""}
    📄 ${l}
    ${g?`<span class="ml-1 text-xs text-blue-500 font-normal">(${x} elem.)</span>`:""}
  </td>
  <td class="px-2 py-2 text-xs text-blue-700" colspan="2">
    <span class="font-medium">${n}</span> ${s}
  </td>
  <td class="px-2 py-2 text-xs text-blue-600">${c||"-"}</td>
  <td class="px-2 py-2 text-xs text-right font-semibold text-blue-800">${d}</td>
  <td class="px-2 py-2" colspan="2">
    <div class="flex items-center gap-1">
      <input type="date" class="swal2-input !m-0 !w-auto planilla-fecha !bg-blue-50 !border-blue-300" data-planilla-id="${t.id}" value="${p}">
      ${g?`<button type="button" class="aplicar-fecha-elementos text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded" data-planilla-id="${t.id}" title="Aplicar fecha a todos los elementos">↓</button>`:""}
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
    </div>`}function We(e){const a={};return document.querySelectorAll('input[type="date"][data-planilla-id]').forEach(o=>{const n=parseInt(o.dataset.planillaId),s=o.value,c=e.find(l=>l.id===n);s&&c&&c.peso_total&&(a[s]||(a[s]={peso:0,planillas:0,esFinDeSemana:fe(s)}),a[s].peso+=parseFloat(c.peso_total),a[s].planillas+=1)}),a}function oe(e){const a=We(e),t=document.getElementById("resumen-contenido");if(!t)return;const o=Object.keys(a).sort();if(o.length===0){t.innerHTML='<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';return}const n=o.map(l=>{const d=a[l],p=new Date(l+"T00:00:00").toLocaleDateString("es-ES",{weekday:"short",day:"2-digit",month:"2-digit",year:"numeric"}),g=d.peso.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2}),x=d.esFinDeSemana?"bg-orange-100 border-orange-300 text-orange-800":"bg-green-100 border-green-300 text-green-800",b=d.esFinDeSemana?"🏖️":"📦";return`
            <div class="inline-block m-1 px-2 py-1 rounded border ${x}">
                <span class="font-medium">${b} ${p}</span>
                <br>
                <span class="text-xs">${g} kg (${d.planillas} planilla${d.planillas!==1?"s":""})</span>
            </div>
        `}).join(""),s=o.reduce((l,d)=>l+a[d].peso,0),c=o.reduce((l,d)=>l+a[d].planillas,0);t.innerHTML=`
        <div class="mb-2">${n}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${s.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})} kg 
            (${c} planilla${c!==1?"s":""})
        </div>
    `}async function He(e){var o,n,s;const a=(n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.actualizarFechasPlanillas;if(!a)throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");const t=await fetch(a,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf,Accept:"application/json"},body:JSON.stringify({planillas:e})});if(!t.ok){const c=await t.text().catch(()=>"");throw new Error(`PUT ${a} -> ${t.status} ${c}`)}return t.json().catch(()=>({}))}async function Re(e,a){var t,o;try{const n=Array.from(new Set(Ne(e))).map(Number).filter(Boolean);if(!n.length)return Swal.fire("⚠️","No hay planillas en la agrupación.","warning");const s=await Be(n);if(!s.length)return Swal.fire("⚠️","No se han encontrado planillas.","warning");const l=`
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `+Oe(s),{isConfirmed:d}=await Swal.fire({title:"",html:l,width:Math.min(window.innerWidth*.98,1200),customClass:{popup:"w-full max-w-screen-xl"},showCancelButton:!0,confirmButtonText:"💾 Guardar",cancelButtonText:"Cancelar",focusConfirm:!1,showClass:{popup:"swal-fade-in-zoom"},hideClass:{popup:"swal-fade-out"},didOpen:r=>{var f,h,S,k,E,w,C;Ge(r),G("#swal-drag",!1),setTimeout(()=>{const y=Swal.getHtmlContainer().querySelector('input[type="date"]');y==null||y.focus({preventScroll:!0})},120),Swal.getHtmlContainer().querySelectorAll('input[type="date"]').forEach(y=>{y.addEventListener("change",function(){fe(this.value)?this.classList.add("weekend-date"):this.classList.remove("weekend-date"),oe(s)})});const i=Swal.getHtmlContainer();i.querySelectorAll(".toggle-elementos").forEach(y=>{y.addEventListener("click",D=>{D.stopPropagation();const $=y.dataset.planillaId,L=i.querySelectorAll(`.elemento-planilla-${$}`),M=y.textContent==="▼";L.forEach(I=>{I.classList.toggle("hidden",M)}),y.textContent=M?"▶":"▼"})}),(f=i.querySelector("#expandir-todos"))==null||f.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(y=>y.classList.remove("hidden")),i.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▼")}),(h=i.querySelector("#colapsar-todos"))==null||h.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(y=>y.classList.add("hidden")),i.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▶")});function m(){const D=i.querySelectorAll(".elemento-checkbox:checked").length,$=i.querySelector("#barra-acciones-masivas"),L=i.querySelector("#contador-seleccionados");D>0?($==null||$.classList.remove("hidden"),L&&(L.textContent=D)):$==null||$.classList.add("hidden")}i.querySelectorAll(".elemento-checkbox").forEach(y=>{y.addEventListener("change",m)}),(S=i.querySelector("#seleccionar-todos-elementos"))==null||S.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(y=>y.classList.remove("hidden")),i.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▼"),i.querySelectorAll(".elemento-checkbox").forEach(y=>{y.checked=!0}),m()}),(k=i.querySelector("#seleccionar-sin-fecha"))==null||k.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(y=>y.classList.remove("hidden")),i.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▼"),i.querySelectorAll(".elemento-checkbox").forEach(y=>{y.checked=!1}),i.querySelectorAll(".elemento-checkbox").forEach(y=>{const D=y.dataset.elementoId,$=i.querySelector(`.elemento-fecha[data-elemento-id="${D}"]`);$&&!$.value&&(y.checked=!0)}),m()}),(E=i.querySelector("#deseleccionar-todos"))==null||E.addEventListener("click",()=>{i.querySelectorAll(".elemento-checkbox").forEach(y=>{y.checked=!1}),m()}),(w=i.querySelector("#aplicar-fecha-masiva"))==null||w.addEventListener("click",()=>{var M;const y=(M=i.querySelector("#fecha-masiva"))==null?void 0:M.value;if(!y){alert("Por favor, selecciona una fecha para aplicar");return}i.querySelectorAll(".elemento-checkbox:checked").forEach(I=>{const _=I.dataset.elementoId,j=i.querySelector(`.elemento-fecha[data-elemento-id="${_}"]`);j&&(j.value=y,j.dispatchEvent(new Event("change")))});const $=i.querySelector("#aplicar-fecha-masiva"),L=$.textContent;$.textContent="✓ Aplicado",$.classList.add("bg-green-600"),setTimeout(()=>{$.textContent=L,$.classList.remove("bg-green-600")},1500)}),(C=i.querySelector("#limpiar-fecha-seleccionados"))==null||C.addEventListener("click",()=>{i.querySelectorAll(".elemento-checkbox:checked").forEach(D=>{const $=D.dataset.elementoId,L=i.querySelector(`.elemento-fecha[data-elemento-id="${$}"]`);L&&(L.value="",L.dispatchEvent(new Event("change")))})}),i.querySelectorAll(".aplicar-fecha-elementos").forEach(y=>{y.addEventListener("click",D=>{var M;D.stopPropagation();const $=y.dataset.planillaId,L=(M=i.querySelector(`.planilla-fecha[data-planilla-id="${$}"]`))==null?void 0:M.value;L&&i.querySelectorAll(`.elemento-fecha[data-planilla-id="${$}"]`).forEach(I=>{I.value=L,I.dispatchEvent(new Event("change"))})})}),i.querySelectorAll(".ver-figura-elemento").forEach(y=>{y.addEventListener("mouseenter",D=>{var I,_;const $=y.dataset.elementoId,L=((I=y.dataset.elementoCodigo)==null?void 0:I.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"",M=((_=y.dataset.dimensiones)==null?void 0:_.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"";typeof window.dibujarFiguraElemento=="function"&&je($,L,M,y)}),y.addEventListener("mouseleave",D=>{setTimeout(()=>{const $=document.getElementById("modal-figura-elemento-overlay");$&&!$.matches(":hover")&&$.remove()},100)}),y.addEventListener("click",D=>{D.preventDefault(),D.stopPropagation();const $=y.dataset.elementoId,L=i.querySelector(`.elemento-checkbox[data-elemento-id="${$}"]`);if(L){L.checked=!L.checked;const I=i.querySelectorAll(".elemento-checkbox:checked").length,_=i.querySelector("#barra-acciones-masivas"),j=i.querySelector("#contador-seleccionados");I>0?(_==null||_.classList.remove("hidden"),j&&(j.textContent=I)):_==null||_.classList.add("hidden")}})}),setTimeout(()=>{oe(s)},100)}});if(!d)return;const p=Swal.getHtmlContainer(),g=p.querySelectorAll(".planilla-fecha"),x=Array.from(g).map(r=>{const u=Number(r.getAttribute("data-planilla-id")),i=p.querySelectorAll(`.elemento-fecha[data-planilla-id="${u}"]`),m=Array.from(i).map(f=>({id:Number(f.getAttribute("data-elemento-id")),fecha_entrega:f.value||null}));return{id:u,fecha_estimada_entrega:r.value,elementos:m.length>0?m:void 0}}),b=await He(x);await Swal.fire(b.success?"✅":"⚠️",b.message||(b.success?"Fechas actualizadas":"No se pudieron actualizar"),b.success?"success":"warning"),b.success&&a&&((t=a.refetchEvents)==null||t.call(a),(o=a.refetchResources)==null||o.call(a))}catch(n){console.error("[CambiarFechasEntrega] error:",n),Swal.fire("❌",(n==null?void 0:n.message)||"Ocurrió un error al actualizar las fechas.","error")}}function ne(e,a){e.el.addEventListener("mousedown",P),e.el.addEventListener("contextmenu",t=>{t.preventDefault(),t.stopPropagation();const o=e.event,n=o.extendedProps||{},s=n.tipo||"planilla";let c="";if(s==="salida"){if(n.clientes&&Array.isArray(n.clientes)&&n.clientes.length>0){const p=n.clientes.map(g=>g.nombre).filter(Boolean).join(", ");p&&(c+=`<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${p}</span>`)}n.obras&&Array.isArray(n.obras)&&n.obras.length>0&&(c+='<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ ',c+=n.obras.map(p=>{const g=p.codigo?`(${p.codigo})`:"";return`${p.nombre} ${g}`}).join(", "),c+="</span>")}const l=`
      <div style="padding:10px 12px; font-weight:600;">
        ${o.title??"Evento"}${c}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(o.start).toLocaleString()} — ${new Date(o.end).toLocaleString()}
        </span>
      </div>
    `;let d=[];if(s==="planilla"){const p=ke(o);d=[{label:"Gestionar Salidas y Paquetes",icon:"📦",onClick:()=>window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${p.join(",")}`},{label:"Cambiar fechas de entrega",icon:"🗓️",onClick:()=>Re(p,a)}]}else if(s==="salida"){const p=n.salida_id||o.id;n.empresa_id,n.empresa,d=[{label:"Abrir salida",icon:"🧾",onClick:()=>window.open(`/salidas-ferralla/${p}`,"_blank")},{label:"Gestionar paquetes",icon:"📦",onClick:()=>ce(p,a)},{label:"Agregar comentario",icon:"✍️",onClick:()=>ze(p,n.comentario||"",a)}]}else d=[{label:"Abrir",icon:"🧾",onClick:()=>window.open(n.url||"#","_blank")}];Se(t.clientX,t.clientY,{headerHtml:l,items:d})})}function Ge(e){e.style.transform="none",e.style.position="fixed",e.style.margin="0";const a=e.offsetWidth,t=e.offsetHeight,o=Math.max(0,Math.round((window.innerWidth-a)/2)),n=Math.max(0,Math.round((window.innerHeight-t)/2));e.style.left=`${o}px`,e.style.top=`${n}px`}function G(e=".swal2-title",a=!1){const t=Swal.getPopup(),o=Swal.getHtmlContainer();let n=(e?(o==null?void 0:o.querySelector(e))||(t==null?void 0:t.querySelector(e)):null)||t;if(!t||!n)return;a&&G.__lastPos&&(t.style.left=G.__lastPos.left,t.style.top=G.__lastPos.top,t.style.transform="none"),n.style.cursor="move",n.style.touchAction="none";const s=u=>{var i;return((i=u.closest)==null?void 0:i.call(u,"input, textarea, select, button, a, label, [contenteditable]"))!=null};let c=!1,l=0,d=0,p=0,g=0;const x=u=>{if(!n.contains(u.target)||s(u.target))return;c=!0,document.body.style.userSelect="none";const i=t.getBoundingClientRect();t.style.left=`${i.left}px`,t.style.top=`${i.top}px`,t.style.transform="none",p=parseFloat(t.style.left||i.left),g=parseFloat(t.style.top||i.top),l=u.clientX,d=u.clientY,document.addEventListener("pointermove",b),document.addEventListener("pointerup",r,{once:!0})},b=u=>{if(!c)return;const i=u.clientX-l,m=u.clientY-d;let f=p+i,h=g+m;const S=t.offsetWidth,k=t.offsetHeight,E=-S+40,w=window.innerWidth-40,C=-k+40,y=window.innerHeight-40;f=Math.max(E,Math.min(w,f)),h=Math.max(C,Math.min(y,h)),t.style.left=`${f}px`,t.style.top=`${h}px`},r=()=>{c=!1,document.body.style.userSelect="",a&&(G.__lastPos={left:t.style.left,top:t.style.top}),document.removeEventListener("pointermove",b)};n.addEventListener("pointerdown",x)}document.addEventListener("DOMContentLoaded",function(){window.addEventListener("comentarioGuardado",e=>{const{salidaId:a,comentario:t}=e.detail,o=window._calendarRef;if(o){const n=o.getEventById(`salida-${a}`);n&&(n.setExtendedProp("comentario",t),n._def&&n._def.extendedProps&&(n._def.extendedProps.comentario=t)),typeof Swal<"u"&&Swal.fire({icon:"success",title:"Comentario guardado",text:"El comentario se ha guardado correctamente",timer:2e3,showConfirmButton:!1,toast:!0,position:"top-end"})}})});function se(e){var c,l;if(!e)return;const a=new Date(e),t={year:"numeric",month:"long"};let o=a.toLocaleDateString("es-ES",t);o=o.charAt(0).toUpperCase()+o.slice(1);const n=document.querySelector("#resumen-mensual-fecha");n&&(n.textContent=`(${o})`);const s=(l=(c=window.AppSalidas)==null?void 0:c.routes)==null?void 0:l.totales;s&&fetch(`${s}?fecha=${encodeURIComponent(e)}`).then(d=>d.json()).then(d=>{const p=d.semana||{};O("#resumen-semanal-peso",`📦 ${Y(p.peso)} kg`),O("#resumen-semanal-longitud",`📏 ${Y(p.longitud)} m`),O("#resumen-semanal-diametro",p.diametro!=null&&!isNaN(p.diametro)?`⌀ ${Number(p.diametro).toFixed(2)} mm`:"");const g=d.mes||{};O("#resumen-mensual-peso",`📦 ${Y(g.peso)} kg`),O("#resumen-mensual-longitud",`📏 ${Y(g.longitud)} m`),O("#resumen-mensual-diametro",g.diametro!=null&&!isNaN(g.diametro)?`⌀ ${Number(g.diametro).toFixed(2)} mm`:"")}).catch(d=>console.error("❌ Error al actualizar los totales:",d))}function Y(e){return e!=null?Number(e).toLocaleString():"0"}function O(e,a){const t=document.querySelector(e);t&&(t.textContent=a)}let v=null;function Ve(e,a){const t=()=>e&&e.offsetParent!==null&&e.clientWidth>0&&e.clientHeight>=0;if(t())return a();if("IntersectionObserver"in window){const n=new IntersectionObserver(s=>{s.some(l=>l.isIntersecting)&&(n.disconnect(),a())},{root:null,threshold:.01});n.observe(e);return}if("ResizeObserver"in window){const n=new ResizeObserver(()=>{t()&&(n.disconnect(),a())});n.observe(e);return}const o=setInterval(()=>{t()&&(clearInterval(o),a())},100)}function B(){v&&(requestAnimationFrame(()=>{try{v.updateSize()}catch{}}),setTimeout(()=>{try{v.updateSize()}catch{}},150))}function Ke(e,a){var d,p;ge();const t=document.createElement("div");t.id="custom-drag-ghost",t.className="custom-drag-ghost";const o=e.extendedProps||{},n=o.tipo==="salida"?"🚚":"📋",s=o.cod_obra||o.nombre_obra||((d=e.title)==null?void 0:d.split(`
`)[0])||"Evento",c=o.pesoTotal?`${Number(o.pesoTotal).toLocaleString()} kg`:"",l=((p=a==null?void 0:a.style)==null?void 0:p.backgroundColor)||e.backgroundColor||"#3b82f6";return t.innerHTML=`
        <div class="ghost-content" style="background: ${l};">
            <div class="ghost-header">
                <span class="ghost-icon">${n}</span>
                <span class="ghost-title">${s}</span>
            </div>
            ${c?`<div class="ghost-weight">📦 ${c}</div>`:""}
            <div class="ghost-time">
                <span class="ghost-time-label">Soltar en:</span>
                <span class="ghost-time-value">--:--</span>
            </div>
        </div>
    `,document.body.appendChild(t),t}function re(e,a,t){const o=document.getElementById("custom-drag-ghost");if(o&&(o.style.left=`${e+15}px`,o.style.top=`${a-30}px`,t)){const n=o.querySelector(".ghost-time-value");n&&(n.textContent=t)}}function ge(){const e=document.getElementById("custom-drag-ghost");e&&e.remove()}function ie(e,a){const t=a==null?void 0:a.querySelector(".fc-timegrid-slots");if(!t)return null;const o=t.getBoundingClientRect(),n=e-o.top+t.scrollTop,s=t.scrollHeight||o.height,c=5,l=20,d=l-c,p=n/s*d,g=c*60+p*60,x=Math.round(g/30)*30,b=Math.max(c,Math.min(l-1,Math.floor(x/60))),r=x%60;return`${String(b).padStart(2,"0")}:${String(r).padStart(2,"0")}`}function le(e){const a=document.querySelectorAll(".fc-timegrid-slot, .fc-timegrid-col");e?a.forEach(t=>{t.classList.add("fc-drop-zone-highlight")}):a.forEach(t=>{t.classList.remove("fc-drop-zone-highlight")})}function Ye(){if(!window.FullCalendar)return console.error("FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."),null;v&&v.destroy();const e=["resourceTimeGridDay","resourceTimelineWeek","dayGridMonth"];let a=localStorage.getItem("ultimaVistaCalendario");e.includes(a)||(a="resourceTimeGridDay");const t=localStorage.getItem("fechaCalendario");let o=null;const n=document.getElementById("calendario");if(!n)return console.error("#calendario no encontrado"),null;function s(p){return v?v.getEvents().some(g=>{var r,u;const x=(g.startStr||((r=g.start)==null?void 0:r.toISOString())||"").split("T")[0];return(((u=g.extendedProps)==null?void 0:u.tipo)==="festivo"||typeof g.id=="string"&&g.id.startsWith("festivo-"))&&x===p}):!1}Ve(n,()=>{v=new FullCalendar.Calendar(n,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",navLinks:!0,navLinkDayClick:(r,u)=>{var h;const i=r.getDay(),m=i===0||i===6,f=(h=v==null?void 0:v.view)==null?void 0:h.type;if(m&&(f==="resourceTimelineWeek"||f==="dayGridMonth")){u.preventDefault();let S;f==="dayGridMonth"?S=i===6?"saturday":"sunday":S=r.toISOString().split("T")[0],window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(S)?window.expandedWeekendDays.delete(S):window.expandedWeekendDays.add(S),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),v.render(),setTimeout(()=>{var k;return(k=window.applyWeekendCollapse)==null?void 0:k.call(window)},50);return}v.changeView("resourceTimeGridDay",r)},initialView:a,initialDate:t?new Date(t):void 0,dayMaxEventRows:!1,dayMaxEvents:!1,slotMinTime:"05:00:00",slotMaxTime:"20:00:00",buttonText:{today:"Hoy",resourceTimeGridDay:"Día",resourceTimelineWeek:"Semana",dayGridMonth:"Mes"},progressiveEventRendering:!0,expandRows:!0,height:"auto",events:(r,u,i)=>{var f;const m=r.view&&r.view.type||((f=v==null?void 0:v.view)==null?void 0:f.type)||"resourceTimeGridDay";ve(m,r).then(u).catch(i)},resources:(r,u,i)=>{var f;const m=r.view&&r.view.type||((f=v==null?void 0:v.view)==null?void 0:f.type)||"resourceTimeGridDay";xe(m,r).then(u).catch(i)},headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimeGridDay,resourceTimelineWeek,dayGridMonth"},eventOrderStrict:!0,eventOrder:(r,u)=>{var S,k;const i=((S=r.extendedProps)==null?void 0:S.tipo)==="resumen-dia",m=((k=u.extendedProps)==null?void 0:k.tipo)==="resumen-dia";if(i&&!m)return-1;if(!i&&m)return 1;const f=parseInt(String(r.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0,h=parseInt(String(u.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0;return f-h},datesSet:r=>{try{const u=Ue(r);localStorage.setItem("fechaCalendario",u),localStorage.setItem("ultimaVistaCalendario",r.view.type),l(),setTimeout(()=>se(u),0),clearTimeout(o),o=setTimeout(()=>{v.refetchResources(),v.refetchEvents(),B(),(r.view.type==="resourceTimelineWeek"||r.view.type==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)},0)}catch(u){console.error("Error en datesSet:",u)}},loading:r=>{if(!r&&v){const u=v.view.type;u==="resourceTimeGridDay"&&setTimeout(()=>d(),150),(u==="resourceTimelineWeek"||u==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)}},viewDidMount:r=>{l(),r.view.type==="resourceTimeGridDay"&&setTimeout(()=>d(),100),r.view.type==="dayGridMonth"&&setTimeout(()=>{document.querySelectorAll(".fc-daygrid-event-harness").forEach(u=>{u.querySelector(".evento-resumen-diario")||(u.style.setProperty("width","100%","important"),u.style.setProperty("max-width","100%","important"),u.style.setProperty("position","static","important"),u.style.setProperty("left","unset","important"),u.style.setProperty("right","unset","important"),u.style.setProperty("top","unset","important"),u.style.setProperty("inset","unset","important"),u.style.setProperty("margin","0 0 2px 0","important"))}),document.querySelectorAll(".fc-daygrid-event:not(.evento-resumen-diario)").forEach(u=>{u.style.setProperty("width","100%","important"),u.style.setProperty("max-width","100%","important"),u.style.setProperty("margin","0","important"),u.style.setProperty("position","static","important"),u.style.setProperty("left","unset","important"),u.style.setProperty("right","unset","important"),u.style.setProperty("inset","unset","important")})},50)},eventContent:r=>{var h;const u=r.event.backgroundColor||"#9CA3AF",i=r.event.extendedProps||{},m=(h=v==null?void 0:v.view)==null?void 0:h.type;if(i.tipo==="resumen-dia"){const S=Number(i.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),k=Number(i.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),E=i.diametroMedio?Number(i.diametroMedio).toFixed(1):null;if(m==="resourceTimelineWeek")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${S} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${k} m</div>
                                ${E?`<div class="text-yellow-800">⌀ ${E} mm</div>`:""}
                            </div>
                        `};if(m==="dayGridMonth")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${S} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${k} m</div>
                                ${E?`<div class="text-yellow-800">⌀ ${E} mm</div>`:""}
                            </div>
                        `}}let f=`
        <div style="background-color:${u}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${r.event.title}</div>
    `;if(i.tipo==="planilla"){const S=i.pesoTotal!=null?`📦 ${Number(i.pesoTotal).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2})} kg`:null,k=i.longitudTotal!=null?`📏 ${Number(i.longitudTotal).toLocaleString()} m`:null,E=i.diametroMedio!=null?`⌀ ${Number(i.diametroMedio).toFixed(2)} mm`:null,w=[S,k,E].filter(Boolean);w.length>0&&(f+=`<div class="text-sm text-black font-semibold">${w.join(" | ")}</div>`),i.tieneSalidas&&Array.isArray(i.salidas_codigos)&&i.salidas_codigos.length>0&&(f+=`
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${i.salidas_codigos.join(", ")}
                </span>
            </div>`)}return f+="</div>",{html:f}},eventDidMount:function(r){var f,h,S,k;const u=r.event.extendedProps||{};if(u.tipo==="resumen-dia"){r.el.classList.add("evento-resumen-diario"),r.el.style.cursor="default";return}if(r.view.type==="dayGridMonth"){const E=r.el.closest(".fc-daygrid-event-harness");E&&(E.style.setProperty("width","100%","important"),E.style.setProperty("max-width","100%","important"),E.style.setProperty("min-width","100%","important"),E.style.setProperty("position","static","important"),E.style.setProperty("left","unset","important"),E.style.setProperty("right","unset","important"),E.style.setProperty("top","unset","important"),E.style.setProperty("inset","unset","important"),E.style.setProperty("margin","0 0 2px 0","important"),E.style.setProperty("display","block","important")),r.el.style.setProperty("width","100%","important"),r.el.style.setProperty("max-width","100%","important"),r.el.style.setProperty("min-width","100%","important"),r.el.style.setProperty("margin","0","important"),r.el.style.setProperty("position","static","important"),r.el.style.setProperty("left","unset","important"),r.el.style.setProperty("right","unset","important"),r.el.style.setProperty("inset","unset","important"),r.el.style.setProperty("display","block","important"),r.el.querySelectorAll("*").forEach(w=>{w.style.setProperty("width","100%","important"),w.style.setProperty("max-width","100%","important")})}const i=(((f=document.getElementById("filtro-obra"))==null?void 0:f.value)||"").trim().toLowerCase(),m=(((h=document.getElementById("filtro-nombre-obra"))==null?void 0:h.value)||"").trim().toLowerCase();if(i||m){let E=!1;if(u.tipo==="salida"&&u.obras&&Array.isArray(u.obras))E=u.obras.some(w=>{const C=(w.codigo||"").toString().toLowerCase(),y=(w.nombre||"").toString().toLowerCase();return i&&C.includes(i)||m&&y.includes(m)});else{const w=(((S=r.event.extendedProps)==null?void 0:S.cod_obra)||"").toString().toLowerCase(),C=(((k=r.event.extendedProps)==null?void 0:k.nombre_obra)||r.event.title||"").toString().toLowerCase();E=i&&w.includes(i)||m&&C.includes(m)}if(E){r.el.classList.add("evento-filtrado");const w="#1f2937",C="#111827";r.el.style.setProperty("background-color",w,"important"),r.el.style.setProperty("background",w,"important"),r.el.style.setProperty("border-color",C,"important"),r.el.style.setProperty("color","white","important"),r.el.querySelectorAll("*").forEach(y=>{y.style.setProperty("background-color",w,"important"),y.style.setProperty("background",w,"important"),y.style.setProperty("color","white","important")})}}typeof te=="function"&&te(r),typeof ne=="function"&&ne(r,v)},eventAllow:(r,u)=>{var m;const i=(m=u.extendedProps)==null?void 0:m.tipo;return!(i==="resumen-dia"||i==="festivo")},snapDuration:"00:30:00",eventDragStart:r=>{var f;window._isDragging=!0,window._draggedEvent=r.event,Ke(r.event,r.el),document.body.classList.add("fc-dragging-active");const u=()=>{document.querySelectorAll(".fc-event-mirror").forEach(h=>{h.style.display="none",h.style.opacity="0",h.style.visibility="hidden"}),window._isDragging&&requestAnimationFrame(u)};requestAnimationFrame(u);const i=document.getElementById("calendario");((f=v==null?void 0:v.view)==null?void 0:f.type)==="resourceTimeGridDay"&&le(!0);const m=h=>{if(!window._isDragging)return;const S=ie(h.clientY,i);re(h.clientX,h.clientY,S)};if(document.addEventListener("mousemove",m),window._dragMouseMoveHandler=m,r.jsEvent){const h=ie(r.jsEvent.clientY,i);re(r.jsEvent.clientX,r.jsEvent.clientY,h)}},eventDragStop:r=>{window._isDragging=!1,window._draggedEvent=null,window._dragMouseMoveHandler&&(document.removeEventListener("mousemove",window._dragMouseMoveHandler),window._dragMouseMoveHandler=null),ge(),document.body.classList.remove("fc-dragging-active"),le(!1)},eventDrop:r=>{var S,k,E,w;const u=r.event.extendedProps||{},i=r.event.id,m=(S=r.event.start)==null?void 0:S.toISOString(),f={fecha:m,tipo:u.tipo,planillas_ids:u.planillas_ids||[],elementos_ids:u.elementos_ids||[]},h=(((E=(k=window.AppSalidas)==null?void 0:k.routes)==null?void 0:E.updateItem)||"").replace("__ID__",i);fetch(h,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(w=window.AppSalidas)==null?void 0:w.csrf},body:JSON.stringify(f)}).then(C=>{if(!C.ok)throw new Error("No se pudo actualizar la fecha.");return C.json()}).then(C=>{v.refetchEvents(),v.refetchResources();const D=r.event.start.toISOString().split("T")[0];se(D),B(),C.alerta_retraso&&Swal.fire({icon:"warning",title:"⚠️ Fecha de entrega adelantada",html:`
                                    <div class="text-left">
                                        <p class="mb-2">${C.alerta_retraso.mensaje}</p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fin fabricación:</strong> ${C.alerta_retraso.fin_programado}</p>
                                            <p class="text-sm"><strong>Fecha entrega:</strong> ${C.alerta_retraso.fecha_entrega}</p>
                                        </div>
                                        <p class="mt-3 text-sm text-gray-600">Los elementos no estarán listos para la fecha indicada según la programación actual de máquinas.</p>
                                    </div>
                                `,showCancelButton:!0,confirmButtonText:"🚀 Adelantar fabricación",cancelButtonText:"Entendido",confirmButtonColor:"#10b981",cancelButtonColor:"#f59e0b"}).then($=>{$.isConfirmed&&Xe(u.elementos_ids,m)})}).catch(C=>{console.error("Error:",C),r.revert()})},dateClick:r=>{s(r.dateStr)&&Swal.fire({icon:"info",title:"📅 Día festivo",text:"Los festivos se editan en la planificación de Trabajadores.",confirmButtonText:"Entendido"})},eventMinHeight:30,firstDay:1,slotLabelContent:r=>{var E,w;if(((E=v==null?void 0:v.view)==null?void 0:E.type)!=="resourceTimelineWeek")return null;const i=r.date;if(!i)return null;const m=i.getDay(),f=m===0||m===6,h=i.toISOString().split("T")[0],S={weekday:"short",day:"numeric",month:"short"},k=i.toLocaleDateString("es-ES",S);if(f){const y=!((w=window.expandedWeekendDays)==null?void 0:w.has(h)),D=y?"▶":"▼",$=y?i.toLocaleDateString("es-ES",{weekday:"short"}).substring(0,3):k;return{html:`<div class="weekend-header cursor-pointer select-none hover:bg-gray-200 px-1 rounded"
                                    data-date="${h}"
                                    data-collapsed="${y}"
                                    title="${y?"Clic para expandir":"Clic para colapsar"}">
                                <span class="collapse-icon text-xs mr-1">${D}</span>
                                <span class="weekend-label">${$}</span>
                               </div>`}}return{html:`<span>${k}</span>`}},views:{resourceTimelineWeek:{slotDuration:{days:1}},resourceTimeGridDay:{slotDuration:"01:00:00",slotLabelFormat:{hour:"2-digit",minute:"2-digit",hour12:!1},slotLabelInterval:"01:00:00",allDaySlot:!1}},editable:!0,eventDurationEditable:!1,eventStartEditable:!0,resourceAreaColumns:[{field:"cod_obra",headerContent:"Código"},{field:"title",headerContent:"Obra"},{field:"cliente",headerContent:"Cliente"}],resourceAreaHeaderContent:"Obras",resourceOrder:"orderIndex",resourceLabelContent:r=>({html:`<div class="text-xs font-semibold">
                        <div class="text-blue-600">${r.resource.extendedProps.cod_obra||""}</div>
                        <div class="text-gray-700 truncate">${r.resource.title||""}</div>
                        <div class="text-gray-500 text-[10px] truncate">${r.resource.extendedProps.cliente||""}</div>
                    </div>`}),windowResize:()=>B()}),v.render(),B();const p=localStorage.getItem("expandedWeekendDays");window.expandedWeekendDays=new Set(p?JSON.parse(p):[]),window.weekendDefaultCollapsed=!0;function g(r){const i=new Date(r+"T00:00:00").getDay();return i===0||i===6}function x(){var u,i,m;const r=(u=v==null?void 0:v.view)==null?void 0:u.type;if(r==="resourceTimelineWeek"&&(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(S=>{var E;const k=S.getAttribute("data-date");g(k)&&(((E=window.expandedWeekendDays)==null?void 0:E.has(k))?S.classList.remove("weekend-collapsed"):S.classList.add("weekend-collapsed"))}),document.querySelectorAll(".fc-timeline-lane td[data-date]").forEach(S=>{var E;const k=S.getAttribute("data-date");g(k)&&(((E=window.expandedWeekendDays)==null?void 0:E.has(k))?S.classList.remove("weekend-collapsed"):S.classList.add("weekend-collapsed"))})),r==="dayGridMonth"){const f=(i=window.expandedWeekendDays)==null?void 0:i.has("saturday"),h=(m=window.expandedWeekendDays)==null?void 0:m.has("sunday");console.log("applyWeekendCollapse - satExpanded:",f,"sunExpanded:",h);const S=document.querySelectorAll(".fc-col-header-cell.fc-day-sat"),k=document.querySelectorAll(".fc-col-header-cell.fc-day-sun");console.log("Headers encontrados - sat:",S.length,"sun:",k.length),S.forEach(w=>{f?w.classList.remove("weekend-day-collapsed"):w.classList.add("weekend-day-collapsed"),console.log("Header sat después:",w.classList.contains("weekend-day-collapsed"))}),k.forEach(w=>{h?w.classList.remove("weekend-day-collapsed"):w.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sat").forEach(w=>{f?w.classList.remove("weekend-day-collapsed"):w.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sun").forEach(w=>{h?w.classList.remove("weekend-day-collapsed"):w.classList.add("weekend-day-collapsed")});const E=document.querySelector(".fc-dayGridMonth-view table");if(E){let w=E.querySelector("colgroup");if(!w){w=document.createElement("colgroup");for(let y=0;y<7;y++)w.appendChild(document.createElement("col"));E.insertBefore(w,E.firstChild)}const C=w.querySelectorAll("col");C.length>=7&&(C[5].style.width=f?"":"40px",C[6].style.width=h?"":"40px")}}}function b(r){console.log("toggleWeekendCollapse llamado con key:",r),console.log("expandedWeekendDays antes:",[...window.expandedWeekendDays||[]]),window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(r)?(window.expandedWeekendDays.delete(r),console.log("Colapsando:",r)):(window.expandedWeekendDays.add(r),console.log("Expandiendo:",r)),console.log("expandedWeekendDays después:",[...window.expandedWeekendDays]),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),x()}n.addEventListener("click",r=>{var m;console.log("Click detectado en:",r.target);const u=r.target.closest(".weekend-header");if(u){const f=u.getAttribute("data-date");if(console.log("Click en weekend-header, dateStr:",f),f){r.preventDefault(),r.stopPropagation(),b(f);return}}const i=(m=v==null?void 0:v.view)==null?void 0:m.type;if(console.log("Vista actual:",i),i==="dayGridMonth"){const f=r.target.closest(".fc-col-header-cell.fc-day-sat, .fc-col-header-cell.fc-day-sun");if(console.log("Header cell encontrado:",f),f){r.preventDefault(),r.stopPropagation();const k=f.classList.contains("fc-day-sat")?"saturday":"sunday";console.log("Toggling:",k),b(k);return}const h=r.target.closest(".fc-daygrid-day.fc-day-sat, .fc-daygrid-day.fc-day-sun");if(console.log("Day cell encontrado:",h),h&&!r.target.closest(".fc-event")){r.preventDefault(),r.stopPropagation();const k=h.classList.contains("fc-day-sat")?"saturday":"sunday";console.log("Toggling day:",k),b(k);return}}},!0),setTimeout(()=>x(),100),window.applyWeekendCollapse=x,n.addEventListener("contextmenu",r=>{const u=r.target.closest(".fc-daygrid-day, .fc-timeline-slot, .fc-timegrid-slot, .fc-col-header-cell");if(u){let i=u.getAttribute("data-date");if(!i){const m=r.target.closest("[data-date]");m&&(i=m.getAttribute("data-date"))}if(i&&v){const m=v.view.type;(m==="resourceTimelineWeek"||m==="dayGridMonth")&&(r.preventDefault(),r.stopPropagation(),Swal.fire({title:"📅 Ir a día",text:`¿Quieres ver el día ${i}?`,icon:"question",showCancelButton:!0,confirmButtonText:"Sí, ir al día",cancelButtonText:"Cancelar"}).then(f=>{f.isConfirmed&&(v.changeView("resourceTimeGridDay",i),B())}))}}})}),window.addEventListener("shown.bs.tab",B),window.addEventListener("shown.bs.collapse",B),window.addEventListener("shown.bs.modal",B);function l(){document.querySelectorAll(".resumen-diario-custom").forEach(g=>g.remove())}function d(){if(!v||v.view.type!=="resourceTimeGridDay"){l();return}l();const p=v.getDate(),g=p.getFullYear(),x=String(p.getMonth()+1).padStart(2,"0"),b=String(p.getDate()).padStart(2,"0"),r=`${g}-${x}-${b}`,u=v.getEvents().find(i=>{var m,f;return((m=i.extendedProps)==null?void 0:m.tipo)==="resumen-dia"&&((f=i.extendedProps)==null?void 0:f.fecha)===r});if(u&&u.extendedProps){const i=Number(u.extendedProps.pesoTotal||0).toLocaleString(),m=Number(u.extendedProps.longitudTotal||0).toLocaleString(),f=u.extendedProps.diametroMedio?Number(u.extendedProps.diametroMedio).toFixed(2):null,h=document.createElement("div");h.className="resumen-diario-custom",h.innerHTML=`
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 Peso: ${i} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${m} m</div>
                        ${f?`<div class="text-yellow-800">⌀ Diámetro: ${f} mm</div>`:""}
                    </div>
                </div>
            `,n&&n.parentNode&&n.parentNode.insertBefore(h,n)}}return window.mostrarResumenDiario=d,window.limpiarResumenesCustom=l,v}function Ue(e){if(e.view.type==="dayGridMonth"){const a=new Date(e.start);return a.setDate(a.getDate()+15),a.toISOString().split("T")[0]}if(e.view.type==="resourceTimeGridWeek"||e.view.type==="resourceTimelineWeek"){const a=new Date(e.start),t=Math.floor((e.end-e.start)/(1e3*60*60*24)/2);return a.setDate(a.getDate()+t),a.toISOString().split("T")[0]}return e.startStr.split("T")[0]}function Xe(e,a){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para adelantar"});return}Swal.fire({title:"Analizando opciones...",html:"Calculando la mejor posición para adelantar la fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}}),fetch("/planificacion/simular-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({elementos_ids:e,fecha_entrega:a})}).then(o=>{if(!o.ok)throw new Error("Error en la simulación");return o.json()}).then(o=>{if(!o.necesita_adelanto){Swal.fire({icon:"info",title:"No es necesario adelantar",text:o.mensaje||"Los elementos llegarán a tiempo."});return}let n="";o.ordenes_a_adelantar&&o.ordenes_a_adelantar.length>0&&(n=`
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
                `,o.ordenes_a_adelantar.forEach(l=>{n+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${l.planilla_codigo}</td>
                            <td class="px-2 py-1">${l.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${l.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${l.posicion_nueva}</td>
                        </tr>
                    `}),n+=`
                                </tbody>
                            </table>
                        </div>
                    </div>
                `);let s="";o.colaterales&&o.colaterales.length>0&&(s=`
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
                `,o.colaterales.forEach(l=>{s+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${l.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${l.obra}</td>
                            <td class="px-2 py-1">${l.fecha_entrega}</td>
                        </tr>
                    `}),s+=`
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-orange-600 mt-1">Estas planillas bajarán una posición en la cola de fabricación.</p>
                    </div>
                `);const c=o.fecha_entrega||"---";Swal.fire({icon:"question",title:"🚀 Adelantar fabricación",html:`
                    <div class="text-left">
                        <p class="mb-3">Para cumplir con la fecha de entrega <strong>${c}</strong>, se propone el siguiente cambio:</p>
                        ${n}
                        ${s}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el adelanto?</p>
                    </div>
                `,width:600,showCancelButton:!0,confirmButtonText:"✅ Ejecutar adelanto",cancelButtonText:"Cancelar",confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280"}).then(l=>{l.isConfirmed&&Je(o.ordenes_a_adelantar)})}).catch(o=>{console.error("Error en simulación:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo simular el adelanto. "+o.message})})}function Je(e){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay órdenes para adelantar"});return}Swal.fire({title:"Ejecutando adelanto...",html:"Actualizando posiciones en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const a=e.map(o=>({planilla_id:o.planilla_id,maquina_id:o.maquina_id,posicion_nueva:o.posicion_nueva}));fetch("/planificacion/ejecutar-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({ordenes:a})}).then(o=>{if(!o.ok)throw new Error("Error al ejecutar el adelanto");return o.json()}).then(o=>{o.success?Swal.fire({icon:"success",title:"¡Adelanto ejecutado!",text:o.mensaje||"Las posiciones han sido actualizadas correctamente.",confirmButtonColor:"#10b981"}).then(()=>{v&&(v.refetchEvents(),v.refetchResources())}):Swal.fire({icon:"error",title:"Error",text:o.mensaje||"No se pudo ejecutar el adelanto."})}).catch(o=>{console.error("Error al ejecutar adelanto:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el adelanto. "+o.message})})}function Ze(e,a={}){const{selector:t=null,once:o=!1}=a;let n=!1;const s=()=>{t&&!document.querySelector(t)||o&&n||(n=!0,e())};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",s):s(),document.addEventListener("livewire:navigated",s)}function Qe(e){document.addEventListener("livewire:navigating",e)}function et(e){let t=new Date(e).toLocaleDateString("es-ES",{month:"long",year:"numeric"});return`(${t.charAt(0).toUpperCase()+t.slice(1)})`}function tt(e){const a=new Date(e),t=a.getDay(),o=t===0?-6:1-t,n=new Date(a);n.setDate(a.getDate()+o);const s=new Date(n);s.setDate(n.getDate()+6);const c=new Intl.DateTimeFormat("es-ES",{day:"2-digit",month:"short"}),l=new Intl.DateTimeFormat("es-ES",{year:"numeric"});return`(${c.format(n)} – ${c.format(s)} ${l.format(s)})`}function at(e){var s,c;const a=document.querySelector("#resumen-semanal-fecha"),t=document.querySelector("#resumen-mensual-fecha");a&&(a.textContent=tt(e)),t&&(t.textContent=et(e));const o=(c=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:c.totales;if(!o)return;const n=`${o}?fecha=${encodeURIComponent(e)}`;fetch(n).then(l=>l.json()).then(l=>{const d=l.semana||{},p=l.mes||{};document.querySelector("#resumen-semanal-peso").textContent=`📦 ${Number(d.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-semanal-longitud").textContent=`📏 ${Number(d.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-semanal-diametro").textContent=d.diametro!=null?`⌀ ${Number(d.diametro).toFixed(2)} mm`:"",document.querySelector("#resumen-mensual-peso").textContent=`📦 ${Number(p.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-mensual-longitud").textContent=`📏 ${Number(p.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-mensual-diametro").textContent=p.diametro!=null?`⌀ ${Number(p.diametro).toFixed(2)} mm`:""}).catch(l=>console.error("❌ Totales:",l))}let W;function ot(){var u,i;if(window.calendar)try{window.calendar.destroy()}catch(m){console.warn("Error al destruir calendario anterior:",m)}const e=Ye();W=e,window.calendar=e,e.refetchResources(),e.refetchEvents(),(u=document.getElementById("ver-con-salidas"))==null||u.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()}),(i=document.getElementById("ver-todas"))==null||i.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()});const t=(localStorage.getItem("fechaCalendario")||new Date().toISOString()).split("T")[0];at(t);const o=localStorage.getItem("soloSalidas")==="true",n=localStorage.getItem("soloPlanillas")==="true",s=document.getElementById("solo-salidas"),c=document.getElementById("solo-planillas");s&&(s.checked=o),c&&(c.checked=n);const l=document.getElementById("filtro-obra"),d=document.getElementById("filtro-nombre-obra"),p=document.getElementById("btn-reset-filtros"),g=document.getElementById("btn-limpiar-filtros");p==null||p.addEventListener("click",()=>{l&&(l.value=""),d&&(d.value=""),s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),c&&(c.checked=!1,localStorage.setItem("soloPlanillas","false")),r(),W.refetchEvents()});const b=((m,f=150)=>{let h;return(...S)=>{clearTimeout(h),h=setTimeout(()=>m(...S),f)}})(()=>{W.refetchEvents()},120);l==null||l.addEventListener("input",b),d==null||d.addEventListener("input",b);function r(){const m=s==null?void 0:s.closest(".checkbox-container"),f=c==null?void 0:c.closest(".checkbox-container");m==null||m.classList.remove("active-salidas"),f==null||f.classList.remove("active-planillas"),s!=null&&s.checked&&(m==null||m.classList.add("active-salidas")),c!=null&&c.checked&&(f==null||f.classList.add("active-planillas"))}s==null||s.addEventListener("change",m=>{m.target.checked&&c&&(c.checked=!1,localStorage.setItem("soloPlanillas","false")),localStorage.setItem("soloSalidas",m.target.checked.toString()),r(),W.refetchEvents()}),c==null||c.addEventListener("change",m=>{m.target.checked&&s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),localStorage.setItem("soloPlanillas",m.target.checked.toString()),r(),W.refetchEvents()}),r(),g==null||g.addEventListener("click",()=>{l&&(l.value=""),d&&(d.value=""),W.refetchEvents()})}let F=null,R=null,N="days",T=-1,A=[];function nt(){R&&R();const e=window.calendar;if(!e)return;F=e.getDate(),N="days",T=-1,V();function a(t){const o=t.target.tagName.toLowerCase();if(o==="input"||o==="textarea"||t.target.isContentEditable||document.querySelector(".swal2-container")||!window.calendar||!F)return;let s=!1;if(t.key==="Tab"&&!t.ctrlKey&&!t.metaKey){t.preventDefault(),st();return}if(t.key==="Escape"&&N==="events"){t.preventDefault(),N="days",T=-1,X(),V(),K();return}N==="events"?s=rt(t):s=it(t),s&&(t.preventDefault(),t.stopPropagation())}document.addEventListener("keydown",a,!0),e.on("eventsSet",()=>{N==="events"&&(ye(),H())}),R=()=>{document.removeEventListener("keydown",a,!0),be(),X()}}function st(){N==="days"?(N="events",ye(),A.length>0?(T=0,H()):(N="days",ut())):(N="days",T=-1,X(),V()),K()}function ye(){const e=window.calendar;if(!e){A=[];return}A=e.getEvents().filter(a=>{var o;const t=(o=a.extendedProps)==null?void 0:o.tipo;return t!=="resumen-dia"&&t!=="festivo"}).sort((a,t)=>{const o=a.start||new Date(0),n=t.start||new Date(0);return o<n?-1:o>n?1:(a.title||"").localeCompare(t.title||"")})}function rt(e){if(A.length===0)return!1;let a=!1;switch(e.key){case"ArrowDown":case"ArrowRight":T=(T+1)%A.length,H(),a=!0;break;case"ArrowUp":case"ArrowLeft":T=T<=0?A.length-1:T-1,H(),a=!0;break;case"Home":T=0,H(),a=!0;break;case"End":T=A.length-1,H(),a=!0;break;case"Enter":lt(),a=!0;break;case"e":case"E":dt(),a=!0;break;case"i":case"I":ct(),a=!0;break}return a}function it(e){const a=window.calendar,t=new Date(F);let o=!1;switch(e.key){case"ArrowLeft":t.setDate(t.getDate()-1),o=!0;break;case"ArrowRight":t.setDate(t.getDate()+1),o=!0;break;case"ArrowUp":t.setDate(t.getDate()-7),o=!0;break;case"ArrowDown":t.setDate(t.getDate()+7),o=!0;break;case"Home":t.setDate(1),o=!0;break;case"End":t.setMonth(t.getMonth()+1),t.setDate(0),o=!0;break;case"PageUp":t.setMonth(t.getMonth()-1),o=!0;break;case"PageDown":t.setMonth(t.getMonth()+1),o=!0;break;case"Enter":const n=he(F),s=a.view.type;s==="dayGridMonth"||s==="resourceTimelineWeek"?a.changeView("resourceTimeGridDay",n):a.gotoDate(F),o=!0;break;case"t":case"T":!e.ctrlKey&&!e.metaKey&&(F=new Date,a.today(),V(),o=!0);break}if(o&&e.key!=="Enter"&&e.key!=="t"&&e.key!=="T"){F=t;const n=a.view;(t<n.currentStart||t>=n.currentEnd)&&a.gotoDate(t),V()}return o}function H(){var t;if(X(),T<0||T>=A.length)return;const e=A[T];if(!e)return;const a=document.querySelector(`[data-event-id="${e.id}"]`)||document.querySelector(`.fc-event[data-event="${e.id}"]`);if(a)a.classList.add("keyboard-focused-event"),a.scrollIntoView({behavior:"smooth",block:"nearest"});else{const o=document.querySelectorAll(".fc-event");for(const n of o)if(n.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){n.classList.add("keyboard-focused-event"),n.scrollIntoView({behavior:"smooth",block:"nearest"});break}}e.start&&(F=new Date(e.start)),K()}function X(){document.querySelectorAll(".keyboard-focused-event").forEach(e=>{e.classList.remove("keyboard-focused-event")})}function lt(){if(T<0||T>=A.length)return;const e=A[T];if(!e)return;const a=e.extendedProps||{},t=window.calendar;if(a.tipo==="salida"){const o=a.salida_id||e.id;ce(o,t)}else if(a.tipo==="planilla"){const o=a.planillas_ids||[];o.length>0&&(window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${o.join(",")}`)}}function dt(){var t;if(T<0||T>=A.length)return;const e=A[T];if(!e)return;const a=document.querySelectorAll(".fc-event");for(const o of a)if(o.classList.contains("keyboard-focused-event")||o.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){const n=o.getBoundingClientRect(),s=new MouseEvent("contextmenu",{bubbles:!0,cancelable:!0,clientX:n.left+n.width/2,clientY:n.top+n.height/2});o.dispatchEvent(s);break}}function ct(){if(T<0||T>=A.length)return;const e=A[T];if(!e)return;const a=e.extendedProps||{};let t=`<strong>${e.title}</strong><br><br>`;a.tipo==="salida"?(t+="<b>Tipo:</b> Salida<br>",a.obras&&a.obras.length>0&&(t+=`<b>Obras:</b> ${a.obras.map(o=>o.nombre).join(", ")}<br>`)):a.tipo==="planilla"&&(t+="<b>Tipo:</b> Planilla<br>",a.cod_obra&&(t+=`<b>Código:</b> ${a.cod_obra}<br>`),a.pesoTotal&&(t+=`<b>Peso:</b> ${Number(a.pesoTotal).toLocaleString()} kg<br>`),a.longitudTotal&&(t+=`<b>Longitud:</b> ${Number(a.longitudTotal).toLocaleString()} m<br>`)),e.start&&(t+=`<b>Fecha:</b> ${e.start.toLocaleDateString("es-ES",{weekday:"long",day:"numeric",month:"long",year:"numeric"})}<br>`),Swal.fire({title:"Información del evento",html:t,icon:"info",confirmButtonText:"Cerrar"})}function ut(){const e=document.getElementById("keyboard-nav-indicator");if(e){const a=document.getElementById("keyboard-nav-date");a&&(a.innerHTML='<span class="text-yellow-400">No hay eventos visibles</span>'),clearTimeout(e._hideTimeout),e.style.display="flex",e._hideTimeout=setTimeout(()=>{K()},2e3)}}function he(e){const a=e.getFullYear(),t=String(e.getMonth()+1).padStart(2,"0"),o=String(e.getDate()).padStart(2,"0");return`${a}-${t}-${o}`}function V(){if(be(),!F)return;const e=he(F),a=window.calendar;if(!a)return;const t=a.view.type;let o=null;t==="dayGridMonth"?o=document.querySelector(`.fc-daygrid-day[data-date="${e}"]`):t==="resourceTimelineWeek"?(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(s=>{s.dataset.date&&s.dataset.date.startsWith(e)&&(o=s)}),o||(o=document.querySelector(`.fc-timeline-slot-lane[data-date^="${e}"]`))):t==="resourceTimeGridDay"&&(o=document.querySelector(".fc-col-header-cell")),o&&(o.classList.add("keyboard-focused-day"),o.scrollIntoView({behavior:"smooth",block:"nearest",inline:"nearest"})),K()}function be(){document.querySelectorAll(".keyboard-focused-day").forEach(e=>{e.classList.remove("keyboard-focused-day")})}function K(){let e=document.getElementById("keyboard-nav-indicator");if(e||(e=document.createElement("div"),e.id="keyboard-nav-indicator",e.className="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm",document.body.appendChild(e)),N==="events"){const a=A[T],t=(a==null?void 0:a.title)||"Sin evento",o=`${T+1}/${A.length}`;e.innerHTML=`
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
        `}else{const a=F?F.toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short",year:"numeric"}):"";e.innerHTML=`
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
        `}clearTimeout(e._hideTimeout),e.style.display="block",e._hideTimeout=setTimeout(()=>{e.style.display="none"},4e3)}function pt(){if(document.getElementById("keyboard-nav-styles"))return;const e=document.createElement("style");e.id="keyboard-nav-styles",e.textContent=`
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
    `,document.head.appendChild(e)}Ze(()=>{ot(),pt(),setTimeout(()=>{nt()},500)},{selector:'#calendario[data-calendar-type="salidas"]'});Qe(()=>{if(R&&(R(),R=null),window.calendar)try{window.calendar.destroy(),window.calendar=null}catch(a){console.warn("Error al limpiar calendario de salidas:",a)}const e=document.getElementById("keyboard-nav-indicator");e&&e.remove()});
