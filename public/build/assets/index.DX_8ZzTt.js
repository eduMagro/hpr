async function ge(e,a){var t,o,n,s;try{const d=(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.planificacion;if(!d)return[];const p=new URLSearchParams({tipo:"events",viewType:e||"",start:a.startStr||"",end:a.endStr||"",t:Date.now()}),c=await fetch(`${d}?${p.toString()}`);if(!c.ok)return console.error("Error eventos",c.status),[];const m=await c.json();let g=Array.isArray(m)?m:Array.isArray(m==null?void 0:m.events)?m.events:[];const w=((n=document.getElementById("solo-salidas"))==null?void 0:n.checked)||!1,h=((s=document.getElementById("solo-planillas"))==null?void 0:s.checked)||!1,r=g.filter(u=>{var f;return((f=u.extendedProps)==null?void 0:f.tipo)==="resumen-dia"}),l=g.filter(u=>{var f;return((f=u.extendedProps)==null?void 0:f.tipo)!=="resumen-dia"});let i=l;return w&&!h?i=l.filter(u=>{var v;return((v=u.extendedProps)==null?void 0:v.tipo)==="salida"}):h&&!w&&(i=l.filter(u=>{var v;const f=(v=u.extendedProps)==null?void 0:v.tipo;return f==="planilla"||f==="festivo"})),[...i,...r]}catch(d){return console.error("fetch eventos falló:",d),[]}}async function ye(e,a){var d,p;const t=(p=(d=window.AppSalidas)==null?void 0:d.routes)==null?void 0:p.planificacion;if(!t)return[];const o=new URLSearchParams({tipo:"resources",viewType:e,start:a.startStr||"",end:a.endStr||""}),n=await fetch(`${t}?${o.toString()}`,{method:"GET"});if(!n.ok)throw new Error("Error cargando recursos");const s=await n.json();return Array.isArray(s)?s:Array.isArray(s==null?void 0:s.resources)?s.resources:[]}function te(e,a){const t=e.event.extendedProps||{};if(t.tipo!=="festivo"){if(t.tipo==="planilla"){const o=`
      ✅ Fabricados: ${Z(t.fabricadosKg)} kg<br>
      🔄 Fabricando: ${Z(t.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${Z(t.pendientesKg)} kg
    `;tippy(e.el,{content:o,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}t.tipo==="salida"&&t.comentario&&t.comentario.trim()&&tippy(e.el,{content:t.comentario,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}}function Z(e){return e!=null?Number(e).toLocaleString():0}let Y=null;function P(){Y&&(Y.remove(),Y=null,document.removeEventListener("click",P),document.removeEventListener("contextmenu",P,!0),document.removeEventListener("scroll",P,!0),window.removeEventListener("resize",P),window.removeEventListener("keydown",re))}function re(e){e.key==="Escape"&&P()}function he(e,a,t){P();const o=document.createElement("div");o.className="fc-contextmenu",Object.assign(o.style,{position:"fixed",top:a+"px",left:e+"px",zIndex:99999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",fontFamily:"system-ui, -apple-system, Segoe UI, Roboto, sans-serif"}),o.innerHTML=t,document.body.appendChild(o),Y=o;const n=o.getBoundingClientRect(),s=Math.max(0,n.right-window.innerWidth+8),d=Math.max(0,n.bottom-window.innerHeight+8);return(s||d)&&(o.style.left=Math.max(8,e-s)+"px",o.style.top=Math.max(8,a-d)+"px"),setTimeout(()=>{document.addEventListener("click",P),document.addEventListener("contextmenu",P,!0),document.addEventListener("scroll",P,!0),window.addEventListener("resize",P),window.addEventListener("keydown",re)},0),o}function be(e,a,{headerHtml:t="",items:o=[]}={}){const n=`
    <div class="ctx-menu-container">
      ${t?`<div class="ctx-menu-header">${t}</div>`:""}
      ${o.map((d,p)=>`
        <button type="button"
          class="ctx-menu-item${d.danger?" ctx-menu-danger":""}"
          data-idx="${p}">
          ${d.icon?`<span class="ctx-menu-icon">${d.icon}</span>`:""}
          <span class="ctx-menu-label">${d.label}</span>
        </button>`).join("")}
    </div>
  `,s=he(e,a,n);return s.querySelectorAll(".ctx-menu-item").forEach(d=>{d.addEventListener("click",async p=>{var g;p.preventDefault(),p.stopPropagation();const c=Number(d.dataset.idx),m=(g=o[c])==null?void 0:g.onClick;P();try{await(m==null?void 0:m())}catch(w){console.error(w)}})}),s}function ve(e){if(!e||typeof e!="string")return"";const a=e.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);if(a){const t=a[1],o=a[2].padStart(2,"0"),n=a[3].padStart(2,"0");return`${t}-${o}-${n}`}return e}(function(){if(document.getElementById("swal-anims"))return;const a=document.createElement("style");a.id="swal-anims",a.textContent=`
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
  `,document.head.appendChild(a)})();function xe(e){const a=e.extendedProps||{};if(Array.isArray(a.planillas_ids)&&a.planillas_ids.length)return a.planillas_ids;const t=(e.id||"").match(/planilla-(\d+)/);return t?[Number(t[1])]:[]}async function ie(e,a){var t,o;try{P()}catch{}if(!e)return Swal.fire("⚠️","ID de salida inválido.","warning");try{const n=await fetch(`${(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.informacionPaquetesSalida}?salida_id=${e}`,{headers:{Accept:"application/json"}});if(!n.ok)throw new Error("Error al cargar información de la salida");const{salida:s,paquetesAsignados:d,paquetesDisponibles:p,paquetesTodos:c,filtros:m}=await n.json();we(s,d,p,c||[],m||{obras:[],planillas:[],obrasRelacionadas:[]},a)}catch(n){console.error(n),Swal.fire("❌","Error al cargar la información de la salida","error")}}function we(e,a,t,o,n,s){window._gestionPaquetesData={salida:e,paquetesAsignados:a,paquetesDisponibles:t,paquetesTodos:o,filtros:n,mostrarTodos:!1};const d=Se(e,a,t,n);Swal.fire({title:`📦 Gestionar Paquetes - Salida ${e.codigo_salida||e.id}`,html:d,width:Math.min(window.innerWidth*.95,1200),showConfirmButton:!0,showCancelButton:!0,confirmButtonText:"💾 Guardar Cambios",cancelButtonText:"Cancelar",focusConfirm:!1,customClass:{popup:"w-full max-w-screen-xl"},didOpen:()=>{ce(),Ee(),De(),setTimeout(()=>{$e()},100)},willClose:()=>{q.cleanup&&q.cleanup();const p=document.getElementById("modal-keyboard-indicator");p&&p.remove()},preConfirm:()=>Ae()}).then(async p=>{p.isConfirmed&&p.value&&await Pe(e.id,p.value,s)})}function Se(e,a,t,o){var m,g;const n=a.reduce((w,h)=>w+(parseFloat(h.peso)||0),0);let s="";e.salida_clientes&&e.salida_clientes.length>0&&(s='<div class="col-span-2"><strong>Obras/Clientes:</strong><br>',e.salida_clientes.forEach(w=>{var i,u,f,v,k;const h=((i=w.obra)==null?void 0:i.obra)||"Obra desconocida",r=(u=w.obra)!=null&&u.cod_obra?`(${w.obra.cod_obra})`:"",l=((f=w.cliente)==null?void 0:f.empresa)||((k=(v=w.obra)==null?void 0:v.cliente)==null?void 0:k.empresa)||"";s+=`<span class="text-xs">• ${h} ${r}`,l&&(s+=` - ${l}`),s+="</span><br>"}),s+="</div>");const d=`
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><strong>Código:</strong> ${e.codigo_salida||"N/A"}</div>
                <div><strong>Código SAGE:</strong> ${e.codigo_sage||"Sin asignar"}</div>
                <div><strong>Fecha salida:</strong> ${new Date(e.fecha_salida).toLocaleString("es-ES")}</div>
                <div><strong>Estado:</strong> ${e.estado||"pendiente"}</div>
                <div><strong>Empresa transporte:</strong> ${((m=e.empresa_transporte)==null?void 0:m.nombre)||"Sin asignar"}</div>
                <div><strong>Camión:</strong> ${((g=e.camion)==null?void 0:g.modelo)||"Sin asignar"}</div>
                ${s}
            </div>
        </div>
    `,p=((o==null?void 0:o.obras)||[]).map(w=>`<option value="${w.id}">${w.cod_obra||""} - ${w.obra||"Sin nombre"}</option>`).join(""),c=((o==null?void 0:o.planillas)||[]).map(w=>`<option value="${w.id}" data-obra-id="${w.obra_id||""}">${w.codigo||"Sin código"}</option>`).join("");return`
        <div class="text-left">
            ${d}

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
                                    ${p}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">📄 Filtrar por Planilla</label>
                                <select id="filtro-planilla-modal" class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">-- Todas las planillas --</option>
                                    ${c}
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
    `}function ee(e){return!e||e.length===0?'<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>':e.map(a=>{var t,o,n,s,d,p,c,m,g,w,h,r,l,i,u,f;return`
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${a.id}"
            data-peso="${a.peso||0}"
            data-obra-id="${((o=(t=a.planilla)==null?void 0:t.obra)==null?void 0:o.id)||""}"
            data-obra="${((s=(n=a.planilla)==null?void 0:n.obra)==null?void 0:s.obra)||""}"
            data-planilla-id="${((d=a.planilla)==null?void 0:d.id)||""}"
            data-planilla="${((p=a.planilla)==null?void 0:p.codigo)||""}"
            data-cliente="${((m=(c=a.planilla)==null?void 0:c.cliente)==null?void 0:m.empresa)||""}"
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
                <div>🏗️ ${((h=(w=a.planilla)==null?void 0:w.obra)==null?void 0:h.cod_obra)||""} - ${((l=(r=a.planilla)==null?void 0:r.obra)==null?void 0:l.obra)||"N/A"}</div>
                <div>👤 ${((u=(i=a.planilla)==null?void 0:i.cliente)==null?void 0:u.empresa)||"Sin cliente"}</div>
                ${(f=a.nave)!=null&&f.obra?`<div class="text-blue-600 font-medium">📍 ${a.nave.obra}</div>`:""}
            </div>
        </div>
    `}).join("")}async function ke(e){var a;try{const t=document.querySelector(`[data-paquete-id="${e}"]`);let o=null;if(t&&t.dataset.paqueteJson)try{o=JSON.parse(t.dataset.paqueteJson.replace(/&#39;/g,"'"))}catch(c){console.warn("No se pudo parsear JSON del paquete",c)}if(!o){const c=await fetch(`/api/paquetes/${e}/elementos`);c.ok&&(o=await c.json())}if(!o){alert("No se pudo obtener información del paquete");return}const n=[];if(o.etiquetas&&o.etiquetas.length>0&&o.etiquetas.forEach(c=>{c.elementos&&c.elementos.length>0&&c.elementos.forEach(m=>{n.push({id:m.id,dimensiones:m.dimensiones,peso:m.peso,longitud:m.longitud,diametro:m.diametro})})}),n.length===0){alert("Este paquete no tiene elementos para mostrar");return}const s=n.map((c,m)=>`
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">Elemento #${c.id}</span>
                    <span class="text-xs text-gray-500">${m+1} de ${n.length}</span>
                </div>
                <div class="mt-2 text-sm text-gray-600 grid grid-cols-2 gap-2">
                    ${c.diametro?`<div><strong>Ø:</strong> ${c.diametro} mm</div>`:""}
                    ${c.longitud?`<div><strong>Long:</strong> ${c.longitud} mm</div>`:""}
                    ${c.peso?`<div><strong>Peso:</strong> ${parseFloat(c.peso).toFixed(2)} kg</div>`:""}
                </div>
                ${c.dimensiones?`
                    <div class="mt-2 p-2 bg-white border rounded">
                        <div id="elemento-dibujo-${c.id}" class="w-full h-32"></div>
                    </div>
                `:""}
            </div>
        `).join(""),d=document.getElementById("modal-elementos-paquete-overlay");d&&d.remove();const p=`
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
        `;document.body.insertAdjacentHTML("beforeend",p),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&n.forEach(c=>{c.dimensiones&&window.dibujarFiguraElemento(`elemento-dibujo-${c.id}`,c.dimensiones,null)})},100)}catch(t){console.error("Error al ver elementos del paquete:",t),alert("Error al cargar los elementos del paquete")}}window.verElementosPaqueteSalida=ke;function Ee(e){const a=document.getElementById("filtro-obra-modal"),t=document.getElementById("filtro-planilla-modal"),o=document.getElementById("btn-limpiar-filtros-modal");a&&a.addEventListener("change",()=>{ae(),Q()}),t&&t.addEventListener("change",()=>{Q()}),o&&o.addEventListener("click",()=>{a&&(a.value=""),t&&(t.value=""),ae(),Q()})}function ae(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData;if(!a||!t)return;const o=(e==null?void 0:e.value)||"",n=o?t.paquetesTodos:t.paquetesDisponibles,s=new Map;n.forEach(c=>{var m,g,w;if((m=c.planilla)!=null&&m.id){if(o&&String((g=c.planilla.obra)==null?void 0:g.id)!==o)return;s.has(c.planilla.id)||s.set(c.planilla.id,{id:c.planilla.id,codigo:c.planilla.codigo||"Sin código",obra_id:(w=c.planilla.obra)==null?void 0:w.id})}});const d=Array.from(s.values()).sort((c,m)=>(c.codigo||"").localeCompare(m.codigo||"")),p=a.value;a.innerHTML='<option value="">-- Todas las planillas --</option>',d.forEach(c=>{const m=document.createElement("option");m.value=c.id,m.textContent=c.codigo,a.appendChild(m)}),p&&s.has(parseInt(p))?a.value=p:a.value=""}function Q(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData,o=(e==null?void 0:e.value)||"",n=(a==null?void 0:a.value)||"",s=document.querySelector('[data-zona="disponibles"]');if(!s||!t)return;const d=document.querySelector('[data-zona="asignados"]'),p=new Set;d&&d.querySelectorAll(".paquete-item-salida").forEach(g=>{p.add(parseInt(g.dataset.paqueteId))});let m=(o?t.paquetesTodos:t.paquetesDisponibles).filter(g=>{var w,h,r;return!(p.has(g.id)||o&&String((h=(w=g.planilla)==null?void 0:w.obra)==null?void 0:h.id)!==o||n&&String((r=g.planilla)==null?void 0:r.id)!==n)});s.innerHTML=ee(m),ce(),m.length===0&&(s.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">No hay paquetes que coincidan con el filtro</div>')}let q={zonaActiva:"asignados",indiceFocused:-1,cleanup:null};function $e(){q.cleanup&&q.cleanup(),q.zonaActiva="asignados",q.indiceFocused=0,_();function e(a){var w;if(!document.querySelector(".swal2-container"))return;const t=a.target.tagName.toLowerCase(),o=t==="select";if((t==="input"||t==="textarea")&&a.key!=="Escape")return;const s=document.querySelector('[data-zona="asignados"]'),d=document.querySelector('[data-zona="disponibles"]');if(!s||!d)return;const p=q.zonaActiva==="asignados"?s:d,c=Array.from(p.querySelectorAll('.paquete-item-salida:not([style*="display: none"])')),m=c.length;let g=!1;if(!o)switch(a.key){case"ArrowDown":m>0&&(q.indiceFocused=(q.indiceFocused+1)%m,_(),g=!0);break;case"ArrowUp":m>0&&(q.indiceFocused=q.indiceFocused<=0?m-1:q.indiceFocused-1,_(),g=!0);break;case"ArrowLeft":case"ArrowRight":q.zonaActiva=q.zonaActiva==="asignados"?"disponibles":"asignados",q.indiceFocused=0,_(),g=!0;break;case"Tab":a.preventDefault(),q.zonaActiva=q.zonaActiva==="asignados"?"disponibles":"asignados",q.indiceFocused=0,_(),g=!0;break;case"Enter":{if(m>0&&q.indiceFocused>=0){const h=c[q.indiceFocused];if(h){qe(h);const r=Array.from(p.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));q.indiceFocused>=r.length&&(q.indiceFocused=Math.max(0,r.length-1)),_(),g=!0}}break}case"Home":q.indiceFocused=0,_(),g=!0;break;case"End":q.indiceFocused=Math.max(0,m-1),_(),g=!0;break}if(g){a.preventDefault(),a.stopPropagation();return}switch(a.key){case"o":case"O":{const h=document.getElementById("filtro-obra-modal");h&&(h.focus(),g=!0);break}case"p":case"P":{const h=document.getElementById("filtro-planilla-modal");h&&(h.focus(),g=!0);break}case"l":case"L":{const h=document.getElementById("btn-limpiar-filtros-modal");h&&(h.click(),(w=document.activeElement)==null||w.blur(),_(),g=!0);break}case"/":case"f":case"F":{const h=document.getElementById("filtro-obra-modal");h&&(h.focus(),g=!0);break}case"Escape":o&&(document.activeElement.blur(),_(),g=!0);break;case"s":case"S":{if(a.ctrlKey||a.metaKey){const h=document.querySelector(".swal2-confirm");h&&(h.click(),g=!0)}break}}g&&(a.preventDefault(),a.stopPropagation())}document.addEventListener("keydown",e,!0),q.cleanup=()=>{document.removeEventListener("keydown",e,!0),le()}}function _(){le();const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;q.zonaActiva==="asignados"?(e.classList.add("zona-activa-keyboard"),a.classList.remove("zona-activa-keyboard")):(a.classList.add("zona-activa-keyboard"),e.classList.remove("zona-activa-keyboard"));const t=q.zonaActiva==="asignados"?e:a,o=Array.from(t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));if(o.length>0&&q.indiceFocused>=0){const n=Math.min(q.indiceFocused,o.length-1),s=o[n];s&&(s.classList.add("paquete-focused-keyboard"),s.scrollIntoView({behavior:"smooth",block:"nearest"}))}Ce()}function le(){document.querySelectorAll(".paquete-focused-keyboard").forEach(e=>{e.classList.remove("paquete-focused-keyboard")}),document.querySelectorAll(".zona-activa-keyboard").forEach(e=>{e.classList.remove("zona-activa-keyboard")})}function qe(e){const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!a||!t)return;const o=e.closest("[data-zona]"),n=o.dataset.zona==="asignados"?t:a,s=n.querySelector(".placeholder-sin-paquetes");if(s&&s.remove(),n.appendChild(e),o.querySelectorAll(".paquete-item-salida").length===0){const p=document.createElement("div");p.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",p.textContent="Sin paquetes",o.appendChild(p)}de(e),X()}function Ce(){let e=document.getElementById("modal-keyboard-indicator");e||(e=document.createElement("div"),e.id="modal-keyboard-indicator",e.className="fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs",document.body.appendChild(e));const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]'),o=(a==null?void 0:a.querySelectorAll(".paquete-item-salida").length)||0,n=(t==null?void 0:t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0,s=q.zonaActiva==="asignados"?`📦 Asignados (${o})`:`📋 Disponibles (${n})`;e.innerHTML=`
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
    `,clearTimeout(e._checkTimeout),e._checkTimeout=setTimeout(()=>{document.querySelector(".swal2-container")||e.remove()},500)}function De(){if(document.getElementById("modal-keyboard-styles"))return;const e=document.createElement("style");e.id="modal-keyboard-styles",e.textContent=`
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
    `,document.head.appendChild(e)}function de(e){e.addEventListener("dragstart",a=>{e.style.opacity="0.5",a.dataTransfer.setData("text/plain",e.dataset.paqueteId)}),e.addEventListener("dragend",a=>{e.style.opacity="1"})}function ce(){document.querySelectorAll(".paquete-item-salida").forEach(e=>{de(e)}),document.querySelectorAll(".drop-zone").forEach(e=>{e.addEventListener("dragover",a=>{a.preventDefault();const t=e.dataset.zona;e.style.backgroundColor=t==="asignados"?"#d1fae5":"#e0f2fe"}),e.addEventListener("dragleave",a=>{e.style.backgroundColor=""}),e.addEventListener("drop",a=>{a.preventDefault(),e.style.backgroundColor="";const t=a.dataTransfer.getData("text/plain"),o=document.querySelector(`.paquete-item-salida[data-paquete-id="${t}"]`);if(o){const n=e.querySelector(".placeholder-sin-paquetes");n&&n.remove(),e.appendChild(o),X()}})})}function X(){const e=document.querySelector('[data-zona="asignados"]'),a=e==null?void 0:e.querySelectorAll(".paquete-item-salida");let t=0;a==null||a.forEach(n=>{const s=parseFloat(n.dataset.peso)||0;t+=s});const o=document.getElementById("peso-asignados");o&&(o.textContent=`${t.toFixed(2)} kg`)}function Te(){const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;const t=e.querySelectorAll(".paquete-item-salida");if(t.length===0)return;t.forEach(n=>{a.appendChild(n)});const o=a.querySelector(".placeholder-sin-paquetes");o&&o.remove(),e.querySelectorAll(".paquete-item-salida").length===0&&(e.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),X()}function Le(){const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;const t=Array.from(a.querySelectorAll(".paquete-item-salida")).filter(s=>s.style.display!=="none");if(t.length===0)return;const o=e.querySelector(".placeholder-sin-paquetes");o&&o.remove(),t.forEach(s=>{e.appendChild(s)}),a.querySelectorAll(".paquete-item-salida").length===0&&(a.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),X()}window.vaciarSalidaModal=Te;window.volcarTodosASalidaModal=Le;function Ae(){const e=document.querySelector('[data-zona="asignados"]');return{paquetes_ids:Array.from((e==null?void 0:e.querySelectorAll(".paquete-item-salida"))||[]).map(t=>parseInt(t.dataset.paqueteId))}}async function Pe(e,a,t){var o,n,s,d;try{const c=await(await fetch((n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.guardarPaquetesSalida,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf},body:JSON.stringify({salida_id:e,paquetes_ids:a.paquetes_ids})})).json();c.success?(await Swal.fire({icon:"success",title:"✅ Cambios Guardados",text:"Los paquetes de la salida se han actualizado correctamente.",timer:2e3}),t&&(t.refetchEvents(),(d=t.refetchResources)==null||d.call(t))):await Swal.fire("⚠️",c.message||"No se pudieron guardar los cambios","warning")}catch(p){console.error(p),Swal.fire("❌","Error al guardar los paquetes","error")}}async function Ie(e,a,t){try{P()}catch{}window.Livewire.dispatch("abrirComentario",{salidaId:e}),window._calendarRef=t}function Fe(e){return e?typeof e=="string"?e.split(",").map(t=>t.trim()).filter(Boolean):Array.from(e).map(t=>typeof t=="object"&&(t==null?void 0:t.id)!=null?t.id:t).map(String).map(t=>t.trim()).filter(Boolean):[]}async function Me(e){var s,d;const a=(d=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:d.informacionPlanillas;if(!a)throw new Error("Ruta 'informacionPlanillas' no configurada");const t=`${a}?ids=${encodeURIComponent(e.join(","))}`,o=await fetch(t,{headers:{Accept:"application/json"}});if(!o.ok){const p=await o.text().catch(()=>"");throw new Error(`GET ${t} -> ${o.status} ${p}`)}const n=await o.json();return Array.isArray(n==null?void 0:n.planillas)?n.planillas:[]}function ue(e){if(!e)return!1;const t=new Date(e+"T00:00:00").getDay();return t===0||t===6}function ze(e,a,t,o){const n=document.getElementById("modal-figura-elemento-overlay");n&&n.remove();const s=o.getBoundingClientRect(),d=320,p=240;let c=s.right+10;c+d>window.innerWidth&&(c=s.left-d-10);let m=s.top-p/2+s.height/2;m<10&&(m=10),m+p>window.innerHeight-10&&(m=window.innerHeight-p-10);const g=`
        <div id="modal-figura-elemento-overlay"
             class="fixed bg-white rounded-lg shadow-2xl border border-gray-300"
             style="z-index: 10001; left: ${c}px; top: ${m}px; width: ${d}px;"
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
    `;document.body.insertAdjacentHTML("beforeend",g),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&window.dibujarFiguraElemento(`figura-elemento-container-${e}`,t,null)},50)}function _e(e){return`
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
          <tbody>${e.map((t,o)=>{var r,l,i;const n=((r=t.obra)==null?void 0:r.codigo)||"",s=((l=t.obra)==null?void 0:l.nombre)||"",d=t.seccion||"";t.descripcion;const p=t.codigo||`Planilla ${t.id}`,c=t.peso_total?parseFloat(t.peso_total).toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})+" kg":"",m=ve(t.fecha_estimada_entrega),g=t.elementos&&t.elementos.length>0,w=((i=t.elementos)==null?void 0:i.length)||0;let h="";return g&&(h=t.elementos.map((u,f)=>{const v=u.fecha_entrega||"",k=u.peso?parseFloat(u.peso).toFixed(2):"-",E=u.codigo||"-",S=u.dimensiones&&u.dimensiones.trim()!=="",b=S?u.dimensiones.replace(/"/g,"&quot;").replace(/'/g,"&#39;"):"",C=E.replace(/"/g,"&quot;").replace(/'/g,"&#39;");return`
                    <tr class="elemento-row elemento-planilla-${t.id} bg-gray-50 hidden">
                        <td class="px-2 py-1 text-xs text-gray-400 pl-4">
                            <div class="flex items-center gap-1">
                                <input type="checkbox" class="elemento-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-3.5 w-3.5"
                                       data-elemento-id="${u.id}"
                                       data-planilla-id="${t.id}">
                                <span>↳</span>
                                <span class="font-medium text-gray-600">${E}</span>
                                ${S?`
                                <button type="button"
                                        class="ver-figura-elemento text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded p-0.5 transition-colors"
                                        data-elemento-id="${u.id}"
                                        data-elemento-codigo="${C}"
                                        data-dimensiones="${b}"
                                        title="Click para seleccionar, hover para ver figura">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                `:""}
                            </div>
                        </td>
                        <td class="px-2 py-1 text-xs text-gray-500">${u.marca||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">Ø${u.diametro||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${u.longitud||"-"} mm</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${u.barras||"-"} uds</td>
                        <td class="px-2 py-1 text-xs text-right text-gray-500">${k} kg</td>
                        <td class="px-2 py-1" colspan="2">
                            <input type="date" class="swal2-input !m-0 !w-auto !text-xs elemento-fecha"
                                   data-elemento-id="${u.id}"
                                   data-planilla-id="${t.id}"
                                   value="${v}">
                        </td>
                    </tr>`}).join("")),`
<tr class="planilla-row hover:bg-blue-50 bg-blue-100 border-t border-blue-200" data-planilla-id="${t.id}" style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${o*18}ms;">
  <td class="px-2 py-2 text-xs font-semibold text-blue-800" colspan="2">
    ${g?`<button type="button" class="toggle-elementos mr-1 text-blue-600 hover:text-blue-800" data-planilla-id="${t.id}">▶</button>`:""}
    📄 ${p}
    ${g?`<span class="ml-1 text-xs text-blue-500 font-normal">(${w} elem.)</span>`:""}
  </td>
  <td class="px-2 py-2 text-xs text-blue-700" colspan="2">
    <span class="font-medium">${n}</span> ${s}
  </td>
  <td class="px-2 py-2 text-xs text-blue-600">${d||"-"}</td>
  <td class="px-2 py-2 text-xs text-right font-semibold text-blue-800">${c}</td>
  <td class="px-2 py-2" colspan="2">
    <div class="flex items-center gap-1">
      <input type="date" class="swal2-input !m-0 !w-auto planilla-fecha !bg-blue-50 !border-blue-300" data-planilla-id="${t.id}" value="${m}">
      ${g?`<button type="button" class="aplicar-fecha-elementos text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded" data-planilla-id="${t.id}" title="Aplicar fecha a todos los elementos">↓</button>`:""}
    </div>
  </td>
</tr>
${h}`}).join("")}</tbody>
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
    </div>`}function Ne(e){const a={};return document.querySelectorAll('input[type="date"][data-planilla-id]').forEach(o=>{const n=parseInt(o.dataset.planillaId),s=o.value,d=e.find(p=>p.id===n);s&&d&&d.peso_total&&(a[s]||(a[s]={peso:0,planillas:0,esFinDeSemana:ue(s)}),a[s].peso+=parseFloat(d.peso_total),a[s].planillas+=1)}),a}function oe(e){const a=Ne(e),t=document.getElementById("resumen-contenido");if(!t)return;const o=Object.keys(a).sort();if(o.length===0){t.innerHTML='<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';return}const n=o.map(p=>{const c=a[p],m=new Date(p+"T00:00:00").toLocaleDateString("es-ES",{weekday:"short",day:"2-digit",month:"2-digit",year:"numeric"}),g=c.peso.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2}),w=c.esFinDeSemana?"bg-orange-100 border-orange-300 text-orange-800":"bg-green-100 border-green-300 text-green-800",h=c.esFinDeSemana?"🏖️":"📦";return`
            <div class="inline-block m-1 px-2 py-1 rounded border ${w}">
                <span class="font-medium">${h} ${m}</span>
                <br>
                <span class="text-xs">${g} kg (${c.planillas} planilla${c.planillas!==1?"s":""})</span>
            </div>
        `}).join(""),s=o.reduce((p,c)=>p+a[c].peso,0),d=o.reduce((p,c)=>p+a[c].planillas,0);t.innerHTML=`
        <div class="mb-2">${n}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${s.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})} kg 
            (${d} planilla${d!==1?"s":""})
        </div>
    `}async function Be(e){var o,n,s;const a=(n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.actualizarFechasPlanillas;if(!a)throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");const t=await fetch(a,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf,Accept:"application/json"},body:JSON.stringify({planillas:e})});if(!t.ok){const d=await t.text().catch(()=>"");throw new Error(`PUT ${a} -> ${t.status} ${d}`)}return t.json().catch(()=>({}))}async function je(e,a){var t,o;try{const n=Array.from(new Set(Fe(e))).map(Number).filter(Boolean);if(!n.length)return Swal.fire("⚠️","No hay planillas en la agrupación.","warning");const s=await Me(n);if(!s.length)return Swal.fire("⚠️","No se han encontrado planillas.","warning");const p=`
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `+_e(s),{isConfirmed:c}=await Swal.fire({title:"",html:p,width:Math.min(window.innerWidth*.98,1200),customClass:{popup:"w-full max-w-screen-xl"},showCancelButton:!0,confirmButtonText:"💾 Guardar",cancelButtonText:"Cancelar",focusConfirm:!1,showClass:{popup:"swal-fade-in-zoom"},hideClass:{popup:"swal-fade-out"},didOpen:r=>{var f,v,k,E,S,b,C;Oe(r),G("#swal-drag",!1),setTimeout(()=>{const y=Swal.getHtmlContainer().querySelector('input[type="date"]');y==null||y.focus({preventScroll:!0})},120),Swal.getHtmlContainer().querySelectorAll('input[type="date"]').forEach(y=>{y.addEventListener("change",function(){ue(this.value)?this.classList.add("weekend-date"):this.classList.remove("weekend-date"),oe(s)})});const i=Swal.getHtmlContainer();i.querySelectorAll(".toggle-elementos").forEach(y=>{y.addEventListener("click",T=>{T.stopPropagation();const $=y.dataset.planillaId,L=i.querySelectorAll(`.elemento-planilla-${$}`),M=y.textContent==="▼";L.forEach(I=>{I.classList.toggle("hidden",M)}),y.textContent=M?"▶":"▼"})}),(f=i.querySelector("#expandir-todos"))==null||f.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(y=>y.classList.remove("hidden")),i.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▼")}),(v=i.querySelector("#colapsar-todos"))==null||v.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(y=>y.classList.add("hidden")),i.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▶")});function u(){const T=i.querySelectorAll(".elemento-checkbox:checked").length,$=i.querySelector("#barra-acciones-masivas"),L=i.querySelector("#contador-seleccionados");T>0?($==null||$.classList.remove("hidden"),L&&(L.textContent=T)):$==null||$.classList.add("hidden")}i.querySelectorAll(".elemento-checkbox").forEach(y=>{y.addEventListener("change",u)}),(k=i.querySelector("#seleccionar-todos-elementos"))==null||k.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(y=>y.classList.remove("hidden")),i.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▼"),i.querySelectorAll(".elemento-checkbox").forEach(y=>{y.checked=!0}),u()}),(E=i.querySelector("#seleccionar-sin-fecha"))==null||E.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(y=>y.classList.remove("hidden")),i.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▼"),i.querySelectorAll(".elemento-checkbox").forEach(y=>{y.checked=!1}),i.querySelectorAll(".elemento-checkbox").forEach(y=>{const T=y.dataset.elementoId,$=i.querySelector(`.elemento-fecha[data-elemento-id="${T}"]`);$&&!$.value&&(y.checked=!0)}),u()}),(S=i.querySelector("#deseleccionar-todos"))==null||S.addEventListener("click",()=>{i.querySelectorAll(".elemento-checkbox").forEach(y=>{y.checked=!1}),u()}),(b=i.querySelector("#aplicar-fecha-masiva"))==null||b.addEventListener("click",()=>{var M;const y=(M=i.querySelector("#fecha-masiva"))==null?void 0:M.value;if(!y){alert("Por favor, selecciona una fecha para aplicar");return}i.querySelectorAll(".elemento-checkbox:checked").forEach(I=>{const z=I.dataset.elementoId,j=i.querySelector(`.elemento-fecha[data-elemento-id="${z}"]`);j&&(j.value=y,j.dispatchEvent(new Event("change")))});const $=i.querySelector("#aplicar-fecha-masiva"),L=$.textContent;$.textContent="✓ Aplicado",$.classList.add("bg-green-600"),setTimeout(()=>{$.textContent=L,$.classList.remove("bg-green-600")},1500)}),(C=i.querySelector("#limpiar-fecha-seleccionados"))==null||C.addEventListener("click",()=>{i.querySelectorAll(".elemento-checkbox:checked").forEach(T=>{const $=T.dataset.elementoId,L=i.querySelector(`.elemento-fecha[data-elemento-id="${$}"]`);L&&(L.value="",L.dispatchEvent(new Event("change")))})}),i.querySelectorAll(".aplicar-fecha-elementos").forEach(y=>{y.addEventListener("click",T=>{var M;T.stopPropagation();const $=y.dataset.planillaId,L=(M=i.querySelector(`.planilla-fecha[data-planilla-id="${$}"]`))==null?void 0:M.value;L&&i.querySelectorAll(`.elemento-fecha[data-planilla-id="${$}"]`).forEach(I=>{I.value=L,I.dispatchEvent(new Event("change"))})})}),i.querySelectorAll(".ver-figura-elemento").forEach(y=>{y.addEventListener("mouseenter",T=>{var I,z;const $=y.dataset.elementoId,L=((I=y.dataset.elementoCodigo)==null?void 0:I.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"",M=((z=y.dataset.dimensiones)==null?void 0:z.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"";typeof window.dibujarFiguraElemento=="function"&&ze($,L,M,y)}),y.addEventListener("mouseleave",T=>{setTimeout(()=>{const $=document.getElementById("modal-figura-elemento-overlay");$&&!$.matches(":hover")&&$.remove()},100)}),y.addEventListener("click",T=>{T.preventDefault(),T.stopPropagation();const $=y.dataset.elementoId,L=i.querySelector(`.elemento-checkbox[data-elemento-id="${$}"]`);if(L){L.checked=!L.checked;const I=i.querySelectorAll(".elemento-checkbox:checked").length,z=i.querySelector("#barra-acciones-masivas"),j=i.querySelector("#contador-seleccionados");I>0?(z==null||z.classList.remove("hidden"),j&&(j.textContent=I)):z==null||z.classList.add("hidden")}})}),setTimeout(()=>{oe(s)},100)}});if(!c)return;const m=Swal.getHtmlContainer(),g=m.querySelectorAll(".planilla-fecha"),w=Array.from(g).map(r=>{const l=Number(r.getAttribute("data-planilla-id")),i=m.querySelectorAll(`.elemento-fecha[data-planilla-id="${l}"]`),u=Array.from(i).map(f=>({id:Number(f.getAttribute("data-elemento-id")),fecha_entrega:f.value||null}));return{id:l,fecha_estimada_entrega:r.value,elementos:u.length>0?u:void 0}}),h=await Be(w);await Swal.fire(h.success?"✅":"⚠️",h.message||(h.success?"Fechas actualizadas":"No se pudieron actualizar"),h.success?"success":"warning"),h.success&&a&&((t=a.refetchEvents)==null||t.call(a),(o=a.refetchResources)==null||o.call(a))}catch(n){console.error("[CambiarFechasEntrega] error:",n),Swal.fire("❌",(n==null?void 0:n.message)||"Ocurrió un error al actualizar las fechas.","error")}}function ne(e,a){e.el.addEventListener("mousedown",P),e.el.addEventListener("contextmenu",t=>{t.preventDefault(),t.stopPropagation();const o=e.event,n=o.extendedProps||{},s=n.tipo||"planilla";let d="";if(s==="salida"){if(n.clientes&&Array.isArray(n.clientes)&&n.clientes.length>0){const m=n.clientes.map(g=>g.nombre).filter(Boolean).join(", ");m&&(d+=`<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${m}</span>`)}n.obras&&Array.isArray(n.obras)&&n.obras.length>0&&(d+='<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ ',d+=n.obras.map(m=>{const g=m.codigo?`(${m.codigo})`:"";return`${m.nombre} ${g}`}).join(", "),d+="</span>")}const p=`
      <div style="padding:10px 12px; font-weight:600;">
        ${o.title??"Evento"}${d}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(o.start).toLocaleString()} — ${new Date(o.end).toLocaleString()}
        </span>
      </div>
    `;let c=[];if(s==="planilla"){const m=xe(o);c=[{label:"Gestionar Salidas y Paquetes",icon:"📦",onClick:()=>window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${m.join(",")}`},{label:"Cambiar fechas de entrega",icon:"🗓️",onClick:()=>je(m,a)}]}else if(s==="salida"){const m=n.salida_id||o.id;n.empresa_id,n.empresa,c=[{label:"Abrir salida",icon:"🧾",onClick:()=>window.open(`/salidas-ferralla/${m}`,"_blank")},{label:"Gestionar paquetes",icon:"📦",onClick:()=>ie(m,a)},{label:"Agregar comentario",icon:"✍️",onClick:()=>Ie(m,n.comentario||"",a)}]}else c=[{label:"Abrir",icon:"🧾",onClick:()=>window.open(n.url||"#","_blank")}];be(t.clientX,t.clientY,{headerHtml:p,items:c})})}function Oe(e){e.style.transform="none",e.style.position="fixed",e.style.margin="0";const a=e.offsetWidth,t=e.offsetHeight,o=Math.max(0,Math.round((window.innerWidth-a)/2)),n=Math.max(0,Math.round((window.innerHeight-t)/2));e.style.left=`${o}px`,e.style.top=`${n}px`}function G(e=".swal2-title",a=!1){const t=Swal.getPopup(),o=Swal.getHtmlContainer();let n=(e?(o==null?void 0:o.querySelector(e))||(t==null?void 0:t.querySelector(e)):null)||t;if(!t||!n)return;a&&G.__lastPos&&(t.style.left=G.__lastPos.left,t.style.top=G.__lastPos.top,t.style.transform="none"),n.style.cursor="move",n.style.touchAction="none";const s=l=>{var i;return((i=l.closest)==null?void 0:i.call(l,"input, textarea, select, button, a, label, [contenteditable]"))!=null};let d=!1,p=0,c=0,m=0,g=0;const w=l=>{if(!n.contains(l.target)||s(l.target))return;d=!0,document.body.style.userSelect="none";const i=t.getBoundingClientRect();t.style.left=`${i.left}px`,t.style.top=`${i.top}px`,t.style.transform="none",m=parseFloat(t.style.left||i.left),g=parseFloat(t.style.top||i.top),p=l.clientX,c=l.clientY,document.addEventListener("pointermove",h),document.addEventListener("pointerup",r,{once:!0})},h=l=>{if(!d)return;const i=l.clientX-p,u=l.clientY-c;let f=m+i,v=g+u;const k=t.offsetWidth,E=t.offsetHeight,S=-k+40,b=window.innerWidth-40,C=-E+40,y=window.innerHeight-40;f=Math.max(S,Math.min(b,f)),v=Math.max(C,Math.min(y,v)),t.style.left=`${f}px`,t.style.top=`${v}px`},r=()=>{d=!1,document.body.style.userSelect="",a&&(G.__lastPos={left:t.style.left,top:t.style.top}),document.removeEventListener("pointermove",h)};n.addEventListener("pointerdown",w)}document.addEventListener("DOMContentLoaded",function(){window.addEventListener("comentarioGuardado",e=>{const{salidaId:a,comentario:t}=e.detail,o=window._calendarRef;if(o){const n=o.getEventById(`salida-${a}`);n&&(n.setExtendedProp("comentario",t),n._def&&n._def.extendedProps&&(n._def.extendedProps.comentario=t)),typeof Swal<"u"&&Swal.fire({icon:"success",title:"Comentario guardado",text:"El comentario se ha guardado correctamente",timer:2e3,showConfirmButton:!1,toast:!0,position:"top-end"})}})});function se(e){var d,p;if(!e)return;const a=new Date(e),t={year:"numeric",month:"long"};let o=a.toLocaleDateString("es-ES",t);o=o.charAt(0).toUpperCase()+o.slice(1);const n=document.querySelector("#resumen-mensual-fecha");n&&(n.textContent=`(${o})`);const s=(p=(d=window.AppSalidas)==null?void 0:d.routes)==null?void 0:p.totales;s&&fetch(`${s}?fecha=${encodeURIComponent(e)}`).then(c=>c.json()).then(c=>{const m=c.semana||{};O("#resumen-semanal-peso",`📦 ${U(m.peso)} kg`),O("#resumen-semanal-longitud",`📏 ${U(m.longitud)} m`),O("#resumen-semanal-diametro",m.diametro!=null&&!isNaN(m.diametro)?`⌀ ${Number(m.diametro).toFixed(2)} mm`:"");const g=c.mes||{};O("#resumen-mensual-peso",`📦 ${U(g.peso)} kg`),O("#resumen-mensual-longitud",`📏 ${U(g.longitud)} m`),O("#resumen-mensual-diametro",g.diametro!=null&&!isNaN(g.diametro)?`⌀ ${Number(g.diametro).toFixed(2)} mm`:"")}).catch(c=>console.error("❌ Error al actualizar los totales:",c))}function U(e){return e!=null?Number(e).toLocaleString():"0"}function O(e,a){const t=document.querySelector(e);t&&(t.textContent=a)}let x=null;function We(e,a){const t=()=>e&&e.offsetParent!==null&&e.clientWidth>0&&e.clientHeight>=0;if(t())return a();if("IntersectionObserver"in window){const n=new IntersectionObserver(s=>{s.some(p=>p.isIntersecting)&&(n.disconnect(),a())},{root:null,threshold:.01});n.observe(e);return}if("ResizeObserver"in window){const n=new ResizeObserver(()=>{t()&&(n.disconnect(),a())});n.observe(e);return}const o=setInterval(()=>{t()&&(clearInterval(o),a())},100)}function B(){x&&(requestAnimationFrame(()=>{try{x.updateSize()}catch{}}),setTimeout(()=>{try{x.updateSize()}catch{}},150))}function Re(){if(!window.FullCalendar)return console.error("FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."),null;x&&x.destroy();const e=["resourceTimeGridDay","resourceTimelineWeek","dayGridMonth"];let a=localStorage.getItem("ultimaVistaCalendario");e.includes(a)||(a="resourceTimeGridDay");const t=localStorage.getItem("fechaCalendario");let o=null;const n=document.getElementById("calendario");if(!n)return console.error("#calendario no encontrado"),null;function s(m){return x?x.getEvents().some(g=>{var r,l;const w=(g.startStr||((r=g.start)==null?void 0:r.toISOString())||"").split("T")[0];return(((l=g.extendedProps)==null?void 0:l.tipo)==="festivo"||typeof g.id=="string"&&g.id.startsWith("festivo-"))&&w===m}):!1}We(n,()=>{x=new FullCalendar.Calendar(n,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",navLinks:!0,navLinkDayClick:(r,l)=>{var v;const i=r.getDay(),u=i===0||i===6,f=(v=x==null?void 0:x.view)==null?void 0:v.type;if(u&&(f==="resourceTimelineWeek"||f==="dayGridMonth")){l.preventDefault();let k;f==="dayGridMonth"?k=i===6?"saturday":"sunday":k=r.toISOString().split("T")[0],window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(k)?window.expandedWeekendDays.delete(k):window.expandedWeekendDays.add(k),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),x.render(),setTimeout(()=>{var E;return(E=window.applyWeekendCollapse)==null?void 0:E.call(window)},50);return}x.changeView("resourceTimeGridDay",r)},initialView:a,initialDate:t?new Date(t):void 0,dayMaxEventRows:!1,dayMaxEvents:!1,slotMinTime:"05:00:00",slotMaxTime:"20:00:00",buttonText:{today:"Hoy",resourceTimeGridDay:"Día",resourceTimelineWeek:"Semana",dayGridMonth:"Mes"},progressiveEventRendering:!0,expandRows:!0,height:"auto",events:(r,l,i)=>{var f;const u=r.view&&r.view.type||((f=x==null?void 0:x.view)==null?void 0:f.type)||"resourceTimeGridDay";ge(u,r).then(l).catch(i)},resources:(r,l,i)=>{var f;const u=r.view&&r.view.type||((f=x==null?void 0:x.view)==null?void 0:f.type)||"resourceTimeGridDay";ye(u,r).then(l).catch(i)},headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimeGridDay,resourceTimelineWeek,dayGridMonth"},eventOrderStrict:!0,eventOrder:(r,l)=>{var k,E;const i=((k=r.extendedProps)==null?void 0:k.tipo)==="resumen-dia",u=((E=l.extendedProps)==null?void 0:E.tipo)==="resumen-dia";if(i&&!u)return-1;if(!i&&u)return 1;const f=parseInt(String(r.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0,v=parseInt(String(l.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0;return f-v},datesSet:r=>{try{const l=He(r);localStorage.setItem("fechaCalendario",l),localStorage.setItem("ultimaVistaCalendario",r.view.type),p(),setTimeout(()=>se(l),0),clearTimeout(o),o=setTimeout(()=>{x.refetchResources(),x.refetchEvents(),B(),(r.view.type==="resourceTimelineWeek"||r.view.type==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)},0)}catch(l){console.error("Error en datesSet:",l)}},loading:r=>{if(!r&&x){const l=x.view.type;l==="resourceTimeGridDay"&&setTimeout(()=>c(),150),(l==="resourceTimelineWeek"||l==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)}},viewDidMount:r=>{p(),r.view.type==="resourceTimeGridDay"&&setTimeout(()=>c(),100),r.view.type==="dayGridMonth"&&setTimeout(()=>{document.querySelectorAll(".fc-daygrid-event-harness").forEach(l=>{l.querySelector(".evento-resumen-diario")||(l.style.setProperty("width","100%","important"),l.style.setProperty("max-width","100%","important"),l.style.setProperty("position","static","important"),l.style.setProperty("left","unset","important"),l.style.setProperty("right","unset","important"),l.style.setProperty("top","unset","important"),l.style.setProperty("inset","unset","important"),l.style.setProperty("margin","0 0 2px 0","important"))}),document.querySelectorAll(".fc-daygrid-event:not(.evento-resumen-diario)").forEach(l=>{l.style.setProperty("width","100%","important"),l.style.setProperty("max-width","100%","important"),l.style.setProperty("margin","0","important"),l.style.setProperty("position","static","important"),l.style.setProperty("left","unset","important"),l.style.setProperty("right","unset","important"),l.style.setProperty("inset","unset","important")})},50)},eventContent:r=>{var v;const l=r.event.backgroundColor||"#9CA3AF",i=r.event.extendedProps||{},u=(v=x==null?void 0:x.view)==null?void 0:v.type;if(i.tipo==="resumen-dia"){const k=Number(i.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),E=Number(i.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),S=i.diametroMedio?Number(i.diametroMedio).toFixed(1):null;if(u==="resourceTimelineWeek")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${k} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${E} m</div>
                                ${S?`<div class="text-yellow-800">⌀ ${S} mm</div>`:""}
                            </div>
                        `};if(u==="dayGridMonth")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${k} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${E} m</div>
                                ${S?`<div class="text-yellow-800">⌀ ${S} mm</div>`:""}
                            </div>
                        `}}let f=`
        <div style="background-color:${l}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${r.event.title}</div>
    `;if(i.tipo==="planilla"){const k=i.pesoTotal!=null?`📦 ${Number(i.pesoTotal).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2})} kg`:null,E=i.longitudTotal!=null?`📏 ${Number(i.longitudTotal).toLocaleString()} m`:null,S=i.diametroMedio!=null?`⌀ ${Number(i.diametroMedio).toFixed(2)} mm`:null,b=[k,E,S].filter(Boolean);b.length>0&&(f+=`<div class="text-sm text-black font-semibold">${b.join(" | ")}</div>`),i.tieneSalidas&&Array.isArray(i.salidas_codigos)&&i.salidas_codigos.length>0&&(f+=`
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${i.salidas_codigos.join(", ")}
                </span>
            </div>`)}return f+="</div>",{html:f}},eventDidMount:function(r){var f,v,k,E;const l=r.event.extendedProps||{};if(l.tipo==="resumen-dia"){r.el.classList.add("evento-resumen-diario"),r.el.style.cursor="default";return}if(r.view.type==="dayGridMonth"){const S=r.el.closest(".fc-daygrid-event-harness");S&&(S.style.setProperty("width","100%","important"),S.style.setProperty("max-width","100%","important"),S.style.setProperty("min-width","100%","important"),S.style.setProperty("position","static","important"),S.style.setProperty("left","unset","important"),S.style.setProperty("right","unset","important"),S.style.setProperty("top","unset","important"),S.style.setProperty("inset","unset","important"),S.style.setProperty("margin","0 0 2px 0","important"),S.style.setProperty("display","block","important")),r.el.style.setProperty("width","100%","important"),r.el.style.setProperty("max-width","100%","important"),r.el.style.setProperty("min-width","100%","important"),r.el.style.setProperty("margin","0","important"),r.el.style.setProperty("position","static","important"),r.el.style.setProperty("left","unset","important"),r.el.style.setProperty("right","unset","important"),r.el.style.setProperty("inset","unset","important"),r.el.style.setProperty("display","block","important"),r.el.querySelectorAll("*").forEach(b=>{b.style.setProperty("width","100%","important"),b.style.setProperty("max-width","100%","important")})}const i=(((f=document.getElementById("filtro-obra"))==null?void 0:f.value)||"").trim().toLowerCase(),u=(((v=document.getElementById("filtro-nombre-obra"))==null?void 0:v.value)||"").trim().toLowerCase();if(i||u){let S=!1;if(l.tipo==="salida"&&l.obras&&Array.isArray(l.obras))S=l.obras.some(b=>{const C=(b.codigo||"").toString().toLowerCase(),y=(b.nombre||"").toString().toLowerCase();return i&&C.includes(i)||u&&y.includes(u)});else{const b=(((k=r.event.extendedProps)==null?void 0:k.cod_obra)||"").toString().toLowerCase(),C=(((E=r.event.extendedProps)==null?void 0:E.nombre_obra)||r.event.title||"").toString().toLowerCase();S=i&&b.includes(i)||u&&C.includes(u)}if(S){r.el.classList.add("evento-filtrado");const b="#1f2937",C="#111827";r.el.style.setProperty("background-color",b,"important"),r.el.style.setProperty("background",b,"important"),r.el.style.setProperty("border-color",C,"important"),r.el.style.setProperty("color","white","important"),r.el.querySelectorAll("*").forEach(y=>{y.style.setProperty("background-color",b,"important"),y.style.setProperty("background",b,"important"),y.style.setProperty("color","white","important")})}}typeof te=="function"&&te(r),typeof ne=="function"&&ne(r,x)},eventAllow:(r,l)=>{var u;const i=(u=l.extendedProps)==null?void 0:u.tipo;return!(i==="resumen-dia"||i==="festivo")},eventDragStart:()=>{const r=()=>{document.querySelectorAll(".fc-event-dragging").forEach(l=>{l.style.width="150px",l.style.maxWidth="150px",l.style.minWidth="150px",l.style.height="80px",l.style.maxHeight="80px",l.style.overflow="hidden"}),window._isDragging&&requestAnimationFrame(r)};window._isDragging=!0,requestAnimationFrame(r)},eventDragStop:()=>{window._isDragging=!1},eventDrop:r=>{var k,E,S,b;const l=r.event.extendedProps||{},i=r.event.id,u=(k=r.event.start)==null?void 0:k.toISOString(),f={fecha:u,tipo:l.tipo,planillas_ids:l.planillas_ids||[],elementos_ids:l.elementos_ids||[]},v=(((S=(E=window.AppSalidas)==null?void 0:E.routes)==null?void 0:S.updateItem)||"").replace("__ID__",i);fetch(v,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(b=window.AppSalidas)==null?void 0:b.csrf},body:JSON.stringify(f)}).then(C=>{if(!C.ok)throw new Error("No se pudo actualizar la fecha.");return C.json()}).then(C=>{x.refetchEvents(),x.refetchResources();const T=r.event.start.toISOString().split("T")[0];se(T),B(),C.alerta_retraso&&Swal.fire({icon:"warning",title:"⚠️ Fecha de entrega adelantada",html:`
                                    <div class="text-left">
                                        <p class="mb-2">${C.alerta_retraso.mensaje}</p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fin fabricación:</strong> ${C.alerta_retraso.fin_programado}</p>
                                            <p class="text-sm"><strong>Fecha entrega:</strong> ${C.alerta_retraso.fecha_entrega}</p>
                                        </div>
                                        <p class="mt-3 text-sm text-gray-600">Los elementos no estarán listos para la fecha indicada según la programación actual de máquinas.</p>
                                    </div>
                                `,showCancelButton:!0,confirmButtonText:"🚀 Adelantar fabricación",cancelButtonText:"Entendido",confirmButtonColor:"#10b981",cancelButtonColor:"#f59e0b"}).then($=>{$.isConfirmed&&Ge(l.elementos_ids,u)})}).catch(C=>{console.error("Error:",C),r.revert()})},dateClick:r=>{s(r.dateStr)&&Swal.fire({icon:"info",title:"📅 Día festivo",text:"Los festivos se editan en la planificación de Trabajadores.",confirmButtonText:"Entendido"})},eventMinHeight:30,firstDay:1,slotLabelContent:r=>{var S,b;if(((S=x==null?void 0:x.view)==null?void 0:S.type)!=="resourceTimelineWeek")return null;const i=r.date;if(!i)return null;const u=i.getDay(),f=u===0||u===6,v=i.toISOString().split("T")[0],k={weekday:"short",day:"numeric",month:"short"},E=i.toLocaleDateString("es-ES",k);if(f){const y=!((b=window.expandedWeekendDays)==null?void 0:b.has(v)),T=y?"▶":"▼",$=y?i.toLocaleDateString("es-ES",{weekday:"short"}).substring(0,3):E;return{html:`<div class="weekend-header cursor-pointer select-none hover:bg-gray-200 px-1 rounded"
                                    data-date="${v}"
                                    data-collapsed="${y}"
                                    title="${y?"Clic para expandir":"Clic para colapsar"}">
                                <span class="collapse-icon text-xs mr-1">${T}</span>
                                <span class="weekend-label">${$}</span>
                               </div>`}}return{html:`<span>${E}</span>`}},views:{resourceTimelineWeek:{slotDuration:{days:1}},resourceTimeGridDay:{slotDuration:"01:00:00",slotLabelFormat:{hour:"2-digit",minute:"2-digit",hour12:!1},slotLabelInterval:"01:00:00",allDaySlot:!1}},editable:!0,eventDurationEditable:!1,eventStartEditable:!0,resourceAreaColumns:[{field:"cod_obra",headerContent:"Código"},{field:"title",headerContent:"Obra"},{field:"cliente",headerContent:"Cliente"}],resourceAreaHeaderContent:"Obras",resourceOrder:"orderIndex",resourceLabelContent:r=>({html:`<div class="text-xs font-semibold">
                        <div class="text-blue-600">${r.resource.extendedProps.cod_obra||""}</div>
                        <div class="text-gray-700 truncate">${r.resource.title||""}</div>
                        <div class="text-gray-500 text-[10px] truncate">${r.resource.extendedProps.cliente||""}</div>
                    </div>`}),windowResize:()=>B()}),x.render(),B();const m=localStorage.getItem("expandedWeekendDays");window.expandedWeekendDays=new Set(m?JSON.parse(m):[]),window.weekendDefaultCollapsed=!0;function g(r){const i=new Date(r+"T00:00:00").getDay();return i===0||i===6}function w(){var l,i,u;const r=(l=x==null?void 0:x.view)==null?void 0:l.type;if(r==="resourceTimelineWeek"&&(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(k=>{var S;const E=k.getAttribute("data-date");g(E)&&(((S=window.expandedWeekendDays)==null?void 0:S.has(E))?k.classList.remove("weekend-collapsed"):k.classList.add("weekend-collapsed"))}),document.querySelectorAll(".fc-timeline-lane td[data-date]").forEach(k=>{var S;const E=k.getAttribute("data-date");g(E)&&(((S=window.expandedWeekendDays)==null?void 0:S.has(E))?k.classList.remove("weekend-collapsed"):k.classList.add("weekend-collapsed"))})),r==="dayGridMonth"){const f=(i=window.expandedWeekendDays)==null?void 0:i.has("saturday"),v=(u=window.expandedWeekendDays)==null?void 0:u.has("sunday");console.log("applyWeekendCollapse - satExpanded:",f,"sunExpanded:",v);const k=document.querySelectorAll(".fc-col-header-cell.fc-day-sat"),E=document.querySelectorAll(".fc-col-header-cell.fc-day-sun");console.log("Headers encontrados - sat:",k.length,"sun:",E.length),k.forEach(b=>{f?b.classList.remove("weekend-day-collapsed"):b.classList.add("weekend-day-collapsed"),console.log("Header sat después:",b.classList.contains("weekend-day-collapsed"))}),E.forEach(b=>{v?b.classList.remove("weekend-day-collapsed"):b.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sat").forEach(b=>{f?b.classList.remove("weekend-day-collapsed"):b.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sun").forEach(b=>{v?b.classList.remove("weekend-day-collapsed"):b.classList.add("weekend-day-collapsed")});const S=document.querySelector(".fc-dayGridMonth-view table");if(S){let b=S.querySelector("colgroup");if(!b){b=document.createElement("colgroup");for(let y=0;y<7;y++)b.appendChild(document.createElement("col"));S.insertBefore(b,S.firstChild)}const C=b.querySelectorAll("col");C.length>=7&&(C[5].style.width=f?"":"40px",C[6].style.width=v?"":"40px")}}}function h(r){console.log("toggleWeekendCollapse llamado con key:",r),console.log("expandedWeekendDays antes:",[...window.expandedWeekendDays||[]]),window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(r)?(window.expandedWeekendDays.delete(r),console.log("Colapsando:",r)):(window.expandedWeekendDays.add(r),console.log("Expandiendo:",r)),console.log("expandedWeekendDays después:",[...window.expandedWeekendDays]),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),w()}n.addEventListener("click",r=>{var u;console.log("Click detectado en:",r.target);const l=r.target.closest(".weekend-header");if(l){const f=l.getAttribute("data-date");if(console.log("Click en weekend-header, dateStr:",f),f){r.preventDefault(),r.stopPropagation(),h(f);return}}const i=(u=x==null?void 0:x.view)==null?void 0:u.type;if(console.log("Vista actual:",i),i==="dayGridMonth"){const f=r.target.closest(".fc-col-header-cell.fc-day-sat, .fc-col-header-cell.fc-day-sun");if(console.log("Header cell encontrado:",f),f){r.preventDefault(),r.stopPropagation();const E=f.classList.contains("fc-day-sat")?"saturday":"sunday";console.log("Toggling:",E),h(E);return}const v=r.target.closest(".fc-daygrid-day.fc-day-sat, .fc-daygrid-day.fc-day-sun");if(console.log("Day cell encontrado:",v),v&&!r.target.closest(".fc-event")){r.preventDefault(),r.stopPropagation();const E=v.classList.contains("fc-day-sat")?"saturday":"sunday";console.log("Toggling day:",E),h(E);return}}},!0),setTimeout(()=>w(),100),window.applyWeekendCollapse=w,n.addEventListener("contextmenu",r=>{const l=r.target.closest(".fc-daygrid-day, .fc-timeline-slot, .fc-timegrid-slot, .fc-col-header-cell");if(l){let i=l.getAttribute("data-date");if(!i){const u=r.target.closest("[data-date]");u&&(i=u.getAttribute("data-date"))}if(i&&x){const u=x.view.type;(u==="resourceTimelineWeek"||u==="dayGridMonth")&&(r.preventDefault(),r.stopPropagation(),Swal.fire({title:"📅 Ir a día",text:`¿Quieres ver el día ${i}?`,icon:"question",showCancelButton:!0,confirmButtonText:"Sí, ir al día",cancelButtonText:"Cancelar"}).then(f=>{f.isConfirmed&&(x.changeView("resourceTimeGridDay",i),B())}))}}})}),window.addEventListener("shown.bs.tab",B),window.addEventListener("shown.bs.collapse",B),window.addEventListener("shown.bs.modal",B);function p(){document.querySelectorAll(".resumen-diario-custom").forEach(g=>g.remove())}function c(){if(!x||x.view.type!=="resourceTimeGridDay"){p();return}p();const m=x.getDate(),g=m.getFullYear(),w=String(m.getMonth()+1).padStart(2,"0"),h=String(m.getDate()).padStart(2,"0"),r=`${g}-${w}-${h}`,l=x.getEvents().find(i=>{var u,f;return((u=i.extendedProps)==null?void 0:u.tipo)==="resumen-dia"&&((f=i.extendedProps)==null?void 0:f.fecha)===r});if(l&&l.extendedProps){const i=Number(l.extendedProps.pesoTotal||0).toLocaleString(),u=Number(l.extendedProps.longitudTotal||0).toLocaleString(),f=l.extendedProps.diametroMedio?Number(l.extendedProps.diametroMedio).toFixed(2):null,v=document.createElement("div");v.className="resumen-diario-custom",v.innerHTML=`
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 Peso: ${i} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${u} m</div>
                        ${f?`<div class="text-yellow-800">⌀ Diámetro: ${f} mm</div>`:""}
                    </div>
                </div>
            `,n&&n.parentNode&&n.parentNode.insertBefore(v,n)}}return window.mostrarResumenDiario=c,window.limpiarResumenesCustom=p,x}function He(e){if(e.view.type==="dayGridMonth"){const a=new Date(e.start);return a.setDate(a.getDate()+15),a.toISOString().split("T")[0]}if(e.view.type==="resourceTimeGridWeek"||e.view.type==="resourceTimelineWeek"){const a=new Date(e.start),t=Math.floor((e.end-e.start)/(1e3*60*60*24)/2);return a.setDate(a.getDate()+t),a.toISOString().split("T")[0]}return e.startStr.split("T")[0]}function Ge(e,a){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para adelantar"});return}Swal.fire({title:"Analizando opciones...",html:"Calculando la mejor posición para adelantar la fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}}),fetch("/planificacion/simular-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({elementos_ids:e,fecha_entrega:a})}).then(o=>{if(!o.ok)throw new Error("Error en la simulación");return o.json()}).then(o=>{if(!o.necesita_adelanto){Swal.fire({icon:"info",title:"No es necesario adelantar",text:o.mensaje||"Los elementos llegarán a tiempo."});return}let n="";o.ordenes_a_adelantar&&o.ordenes_a_adelantar.length>0&&(n=`
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
                `,o.ordenes_a_adelantar.forEach(p=>{n+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${p.planilla_codigo}</td>
                            <td class="px-2 py-1">${p.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${p.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${p.posicion_nueva}</td>
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
                `,o.colaterales.forEach(p=>{s+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${p.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${p.obra}</td>
                            <td class="px-2 py-1">${p.fecha_entrega}</td>
                        </tr>
                    `}),s+=`
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-orange-600 mt-1">Estas planillas bajarán una posición en la cola de fabricación.</p>
                    </div>
                `);const d=o.fecha_entrega||"---";Swal.fire({icon:"question",title:"🚀 Adelantar fabricación",html:`
                    <div class="text-left">
                        <p class="mb-3">Para cumplir con la fecha de entrega <strong>${d}</strong>, se propone el siguiente cambio:</p>
                        ${n}
                        ${s}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el adelanto?</p>
                    </div>
                `,width:600,showCancelButton:!0,confirmButtonText:"✅ Ejecutar adelanto",cancelButtonText:"Cancelar",confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280"}).then(p=>{p.isConfirmed&&Ve(o.ordenes_a_adelantar)})}).catch(o=>{console.error("Error en simulación:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo simular el adelanto. "+o.message})})}function Ve(e){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay órdenes para adelantar"});return}Swal.fire({title:"Ejecutando adelanto...",html:"Actualizando posiciones en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const a=e.map(o=>({planilla_id:o.planilla_id,maquina_id:o.maquina_id,posicion_nueva:o.posicion_nueva}));fetch("/planificacion/ejecutar-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({ordenes:a})}).then(o=>{if(!o.ok)throw new Error("Error al ejecutar el adelanto");return o.json()}).then(o=>{o.success?Swal.fire({icon:"success",title:"¡Adelanto ejecutado!",text:o.mensaje||"Las posiciones han sido actualizadas correctamente.",confirmButtonColor:"#10b981"}).then(()=>{x&&(x.refetchEvents(),x.refetchResources())}):Swal.fire({icon:"error",title:"Error",text:o.mensaje||"No se pudo ejecutar el adelanto."})}).catch(o=>{console.error("Error al ejecutar adelanto:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el adelanto. "+o.message})})}function Ke(e,a={}){const{selector:t=null,once:o=!1}=a;let n=!1;const s=()=>{t&&!document.querySelector(t)||o&&n||(n=!0,e())};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",s):s(),document.addEventListener("livewire:navigated",s)}function Ue(e){document.addEventListener("livewire:navigating",e)}function Ye(e){let t=new Date(e).toLocaleDateString("es-ES",{month:"long",year:"numeric"});return`(${t.charAt(0).toUpperCase()+t.slice(1)})`}function Je(e){const a=new Date(e),t=a.getDay(),o=t===0?-6:1-t,n=new Date(a);n.setDate(a.getDate()+o);const s=new Date(n);s.setDate(n.getDate()+6);const d=new Intl.DateTimeFormat("es-ES",{day:"2-digit",month:"short"}),p=new Intl.DateTimeFormat("es-ES",{year:"numeric"});return`(${d.format(n)} – ${d.format(s)} ${p.format(s)})`}function Xe(e){const a=document.querySelector("#resumen-semanal-fecha"),t=document.querySelector("#resumen-mensual-fecha");a&&(a.textContent=Je(e)),t&&(t.textContent=Ye(e));const o=`${window.AppSalidas.routes.totales}?fecha=${encodeURIComponent(e)}`;fetch(o).then(n=>n.json()).then(n=>{const s=n.semana||{},d=n.mes||{};document.querySelector("#resumen-semanal-peso").textContent=`📦 ${Number(s.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-semanal-longitud").textContent=`📏 ${Number(s.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-semanal-diametro").textContent=s.diametro!=null?`⌀ ${Number(s.diametro).toFixed(2)} mm`:"",document.querySelector("#resumen-mensual-peso").textContent=`📦 ${Number(d.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-mensual-longitud").textContent=`📏 ${Number(d.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-mensual-diametro").textContent=d.diametro!=null?`⌀ ${Number(d.diametro).toFixed(2)} mm`:""}).catch(n=>console.error("❌ Totales:",n))}let W;function Ze(){var l,i;if(window.calendar)try{window.calendar.destroy()}catch(u){console.warn("Error al destruir calendario anterior:",u)}const e=Re();W=e,window.calendar=e,e.refetchResources(),e.refetchEvents(),(l=document.getElementById("ver-con-salidas"))==null||l.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()}),(i=document.getElementById("ver-todas"))==null||i.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()});const t=(localStorage.getItem("fechaCalendario")||new Date().toISOString()).split("T")[0];Xe(t);const o=localStorage.getItem("soloSalidas")==="true",n=localStorage.getItem("soloPlanillas")==="true",s=document.getElementById("solo-salidas"),d=document.getElementById("solo-planillas");s&&(s.checked=o),d&&(d.checked=n);const p=document.getElementById("filtro-obra"),c=document.getElementById("filtro-nombre-obra"),m=document.getElementById("btn-reset-filtros"),g=document.getElementById("btn-limpiar-filtros");m==null||m.addEventListener("click",()=>{p&&(p.value=""),c&&(c.value=""),s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),d&&(d.checked=!1,localStorage.setItem("soloPlanillas","false")),r(),W.refetchEvents()});const h=((u,f=150)=>{let v;return(...k)=>{clearTimeout(v),v=setTimeout(()=>u(...k),f)}})(()=>{W.refetchEvents()},120);p==null||p.addEventListener("input",h),c==null||c.addEventListener("input",h);function r(){const u=s==null?void 0:s.closest(".checkbox-container"),f=d==null?void 0:d.closest(".checkbox-container");u==null||u.classList.remove("active-salidas"),f==null||f.classList.remove("active-planillas"),s!=null&&s.checked&&(u==null||u.classList.add("active-salidas")),d!=null&&d.checked&&(f==null||f.classList.add("active-planillas"))}s==null||s.addEventListener("change",u=>{u.target.checked&&d&&(d.checked=!1,localStorage.setItem("soloPlanillas","false")),localStorage.setItem("soloSalidas",u.target.checked.toString()),r(),W.refetchEvents()}),d==null||d.addEventListener("change",u=>{u.target.checked&&s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),localStorage.setItem("soloPlanillas",u.target.checked.toString()),r(),W.refetchEvents()}),r(),g==null||g.addEventListener("click",()=>{p&&(p.value=""),c&&(c.value=""),W.refetchEvents()})}let F=null,H=null,N="days",D=-1,A=[];function Qe(){H&&H();const e=window.calendar;if(!e)return;F=e.getDate(),N="days",D=-1,V();function a(t){const o=t.target.tagName.toLowerCase();if(o==="input"||o==="textarea"||t.target.isContentEditable||document.querySelector(".swal2-container")||!window.calendar||!F)return;let s=!1;if(t.key==="Tab"&&!t.ctrlKey&&!t.metaKey){t.preventDefault(),et();return}if(t.key==="Escape"&&N==="events"){t.preventDefault(),N="days",D=-1,J(),V(),K();return}N==="events"?s=tt(t):s=at(t),s&&(t.preventDefault(),t.stopPropagation())}document.addEventListener("keydown",a,!0),e.on("eventsSet",()=>{N==="events"&&(pe(),R())}),H=()=>{document.removeEventListener("keydown",a,!0),fe(),J()}}function et(){N==="days"?(N="events",pe(),A.length>0?(D=0,R()):(N="days",rt())):(N="days",D=-1,J(),V()),K()}function pe(){const e=window.calendar;if(!e){A=[];return}A=e.getEvents().filter(a=>{var o;const t=(o=a.extendedProps)==null?void 0:o.tipo;return t!=="resumen-dia"&&t!=="festivo"}).sort((a,t)=>{const o=a.start||new Date(0),n=t.start||new Date(0);return o<n?-1:o>n?1:(a.title||"").localeCompare(t.title||"")})}function tt(e){if(A.length===0)return!1;let a=!1;switch(e.key){case"ArrowDown":case"ArrowRight":D=(D+1)%A.length,R(),a=!0;break;case"ArrowUp":case"ArrowLeft":D=D<=0?A.length-1:D-1,R(),a=!0;break;case"Home":D=0,R(),a=!0;break;case"End":D=A.length-1,R(),a=!0;break;case"Enter":ot(),a=!0;break;case"e":case"E":nt(),a=!0;break;case"i":case"I":st(),a=!0;break}return a}function at(e){const a=window.calendar,t=new Date(F);let o=!1;switch(e.key){case"ArrowLeft":t.setDate(t.getDate()-1),o=!0;break;case"ArrowRight":t.setDate(t.getDate()+1),o=!0;break;case"ArrowUp":t.setDate(t.getDate()-7),o=!0;break;case"ArrowDown":t.setDate(t.getDate()+7),o=!0;break;case"Home":t.setDate(1),o=!0;break;case"End":t.setMonth(t.getMonth()+1),t.setDate(0),o=!0;break;case"PageUp":t.setMonth(t.getMonth()-1),o=!0;break;case"PageDown":t.setMonth(t.getMonth()+1),o=!0;break;case"Enter":const n=me(F),s=a.view.type;s==="dayGridMonth"||s==="resourceTimelineWeek"?a.changeView("resourceTimeGridDay",n):a.gotoDate(F),o=!0;break;case"t":case"T":!e.ctrlKey&&!e.metaKey&&(F=new Date,a.today(),V(),o=!0);break}if(o&&e.key!=="Enter"&&e.key!=="t"&&e.key!=="T"){F=t;const n=a.view;(t<n.currentStart||t>=n.currentEnd)&&a.gotoDate(t),V()}return o}function R(){var t;if(J(),D<0||D>=A.length)return;const e=A[D];if(!e)return;const a=document.querySelector(`[data-event-id="${e.id}"]`)||document.querySelector(`.fc-event[data-event="${e.id}"]`);if(a)a.classList.add("keyboard-focused-event"),a.scrollIntoView({behavior:"smooth",block:"nearest"});else{const o=document.querySelectorAll(".fc-event");for(const n of o)if(n.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){n.classList.add("keyboard-focused-event"),n.scrollIntoView({behavior:"smooth",block:"nearest"});break}}e.start&&(F=new Date(e.start)),K()}function J(){document.querySelectorAll(".keyboard-focused-event").forEach(e=>{e.classList.remove("keyboard-focused-event")})}function ot(){if(D<0||D>=A.length)return;const e=A[D];if(!e)return;const a=e.extendedProps||{},t=window.calendar;if(a.tipo==="salida"){const o=a.salida_id||e.id;ie(o,t)}else if(a.tipo==="planilla"){const o=a.planillas_ids||[];o.length>0&&(window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${o.join(",")}`)}}function nt(){var t;if(D<0||D>=A.length)return;const e=A[D];if(!e)return;const a=document.querySelectorAll(".fc-event");for(const o of a)if(o.classList.contains("keyboard-focused-event")||o.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){const n=o.getBoundingClientRect(),s=new MouseEvent("contextmenu",{bubbles:!0,cancelable:!0,clientX:n.left+n.width/2,clientY:n.top+n.height/2});o.dispatchEvent(s);break}}function st(){if(D<0||D>=A.length)return;const e=A[D];if(!e)return;const a=e.extendedProps||{};let t=`<strong>${e.title}</strong><br><br>`;a.tipo==="salida"?(t+="<b>Tipo:</b> Salida<br>",a.obras&&a.obras.length>0&&(t+=`<b>Obras:</b> ${a.obras.map(o=>o.nombre).join(", ")}<br>`)):a.tipo==="planilla"&&(t+="<b>Tipo:</b> Planilla<br>",a.cod_obra&&(t+=`<b>Código:</b> ${a.cod_obra}<br>`),a.pesoTotal&&(t+=`<b>Peso:</b> ${Number(a.pesoTotal).toLocaleString()} kg<br>`),a.longitudTotal&&(t+=`<b>Longitud:</b> ${Number(a.longitudTotal).toLocaleString()} m<br>`)),e.start&&(t+=`<b>Fecha:</b> ${e.start.toLocaleDateString("es-ES",{weekday:"long",day:"numeric",month:"long",year:"numeric"})}<br>`),Swal.fire({title:"Información del evento",html:t,icon:"info",confirmButtonText:"Cerrar"})}function rt(){const e=document.getElementById("keyboard-nav-indicator");if(e){const a=document.getElementById("keyboard-nav-date");a&&(a.innerHTML='<span class="text-yellow-400">No hay eventos visibles</span>'),clearTimeout(e._hideTimeout),e.style.display="flex",e._hideTimeout=setTimeout(()=>{K()},2e3)}}function me(e){const a=e.getFullYear(),t=String(e.getMonth()+1).padStart(2,"0"),o=String(e.getDate()).padStart(2,"0");return`${a}-${t}-${o}`}function V(){if(fe(),!F)return;const e=me(F),a=window.calendar;if(!a)return;const t=a.view.type;let o=null;t==="dayGridMonth"?o=document.querySelector(`.fc-daygrid-day[data-date="${e}"]`):t==="resourceTimelineWeek"?(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(s=>{s.dataset.date&&s.dataset.date.startsWith(e)&&(o=s)}),o||(o=document.querySelector(`.fc-timeline-slot-lane[data-date^="${e}"]`))):t==="resourceTimeGridDay"&&(o=document.querySelector(".fc-col-header-cell")),o&&(o.classList.add("keyboard-focused-day"),o.scrollIntoView({behavior:"smooth",block:"nearest",inline:"nearest"})),K()}function fe(){document.querySelectorAll(".keyboard-focused-day").forEach(e=>{e.classList.remove("keyboard-focused-day")})}function K(){let e=document.getElementById("keyboard-nav-indicator");if(e||(e=document.createElement("div"),e.id="keyboard-nav-indicator",e.className="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm",document.body.appendChild(e)),N==="events"){const a=A[D],t=(a==null?void 0:a.title)||"Sin evento",o=`${D+1}/${A.length}`;e.innerHTML=`
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
        `}clearTimeout(e._hideTimeout),e.style.display="block",e._hideTimeout=setTimeout(()=>{e.style.display="none"},4e3)}function it(){if(document.getElementById("keyboard-nav-styles"))return;const e=document.createElement("style");e.id="keyboard-nav-styles",e.textContent=`
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
    `,document.head.appendChild(e)}Ke(()=>{Ze(),it(),setTimeout(()=>{Qe()},500)},{selector:'#calendario[data-calendar-type="salidas"]'});Ue(()=>{if(H&&(H(),H=null),window.calendar)try{window.calendar.destroy(),window.calendar=null}catch(a){console.warn("Error al limpiar calendario de salidas:",a)}const e=document.getElementById("keyboard-nav-indicator");e&&e.remove()});
