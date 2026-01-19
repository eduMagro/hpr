let B=null,G=null,W=null;async function ee(e,a){var o,s;const t=(s=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:s.planificacion;if(!t)return{events:[],resources:[],totales:null};const n=`${e}|${a.startStr}|${a.endStr}`;return G===n&&B?B:(W&&G===n||(G=n,W=(async()=>{try{const c=new URLSearchParams({tipo:"all",viewType:e||"",start:a.startStr||"",end:a.endStr||""}),p=await fetch(`${t}?${c.toString()}`);if(!p.ok)throw new Error(`HTTP ${p.status}`);const d=await p.json();return B={events:d.events||[],resources:d.resources||[],totales:d.totales||null},B}catch(c){return console.error("fetch all data falló:",c),B=null,{events:[],resources:[],totales:null}}finally{W=null}})()),W)}function J(){B=null,G=null}function he(e){var c,p;const a=((c=document.getElementById("solo-salidas"))==null?void 0:c.checked)||!1,t=((p=document.getElementById("solo-planillas"))==null?void 0:p.checked)||!1,n=e.filter(d=>{var l;return((l=d.extendedProps)==null?void 0:l.tipo)==="resumen-dia"}),o=e.filter(d=>{var l;return((l=d.extendedProps)==null?void 0:l.tipo)!=="resumen-dia"});let s=o;return a&&!t?s=o.filter(d=>{var l;return((l=d.extendedProps)==null?void 0:l.tipo)==="salida"}):t&&!a&&(s=o.filter(d=>{var f;const l=(f=d.extendedProps)==null?void 0:f.tipo;return l==="planilla"||l==="festivo"})),[...s,...n]}async function ve(e,a){const t=await ee(e,a);return he(t.events)}async function be(e,a){return(await ee(e,a)).resources}async function we(e,a){const t=await ee(e,a);if(!t.totales)return;const{semana:n,mes:o}=t.totales,s=h=>h!=null?Number(h).toLocaleString():"0",c=document.querySelector("#resumen-semanal-peso"),p=document.querySelector("#resumen-semanal-longitud"),d=document.querySelector("#resumen-semanal-diametro");c&&(c.textContent=`📦 ${s(n==null?void 0:n.peso)} kg`),p&&(p.textContent=`📏 ${s(n==null?void 0:n.longitud)} m`),d&&(d.textContent=n!=null&&n.diametro?`⌀ ${Number(n.diametro).toFixed(2)} mm`:"");const l=document.querySelector("#resumen-mensual-peso"),f=document.querySelector("#resumen-mensual-longitud"),b=document.querySelector("#resumen-mensual-diametro");if(l&&(l.textContent=`📦 ${s(o==null?void 0:o.peso)} kg`),f&&(f.textContent=`📏 ${s(o==null?void 0:o.longitud)} m`),b&&(b.textContent=o!=null&&o.diametro?`⌀ ${Number(o.diametro).toFixed(2)} mm`:""),a.startStr){const h=new Date(a.startStr),r={year:"numeric",month:"long"};let u=h.toLocaleDateString("es-ES",r);u=u.charAt(0).toUpperCase()+u.slice(1);const i=document.querySelector("#resumen-mensual-fecha");i&&(i.textContent=`(${u})`)}}function te(e,a){const t=e.event.extendedProps||{};if(t.tipo!=="festivo"){if(t.tipo==="planilla"){const n=`
      ✅ Fabricados: ${K(t.fabricadosKg)} kg<br>
      🔄 Fabricando: ${K(t.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${K(t.pendientesKg)} kg
    `;tippy(e.el,{content:n,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}t.tipo==="salida"&&t.comentario&&t.comentario.trim()&&tippy(e.el,{content:t.comentario,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}}function K(e){return e!=null?Number(e).toLocaleString():0}let V=null;function D(){V&&(V.remove(),V=null,document.removeEventListener("click",D),document.removeEventListener("contextmenu",D,!0),document.removeEventListener("scroll",D,!0),window.removeEventListener("resize",D),window.removeEventListener("keydown",de))}function de(e){e.key==="Escape"&&D()}function xe(e,a,t){D();const n=document.createElement("div");n.className="fc-contextmenu",Object.assign(n.style,{position:"fixed",top:a+"px",left:e+"px",zIndex:99999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",fontFamily:"system-ui, -apple-system, Segoe UI, Roboto, sans-serif"}),n.innerHTML=t,document.body.appendChild(n),V=n;const o=n.getBoundingClientRect(),s=Math.max(0,o.right-window.innerWidth+8),c=Math.max(0,o.bottom-window.innerHeight+8);return(s||c)&&(n.style.left=Math.max(8,e-s)+"px",n.style.top=Math.max(8,a-c)+"px"),setTimeout(()=>{document.addEventListener("click",D),document.addEventListener("contextmenu",D,!0),document.addEventListener("scroll",D,!0),window.addEventListener("resize",D),window.addEventListener("keydown",de)},0),n}function Se(e,a,{headerHtml:t="",items:n=[]}={}){const o=`
    <div class="ctx-menu-container">
      ${t?`<div class="ctx-menu-header">${t}</div>`:""}
      ${n.map((c,p)=>`
        <button type="button"
          class="ctx-menu-item${c.danger?" ctx-menu-danger":""}"
          data-idx="${p}">
          ${c.icon?`<span class="ctx-menu-icon">${c.icon}</span>`:""}
          <span class="ctx-menu-label">${c.label}</span>
        </button>`).join("")}
    </div>
  `,s=xe(e,a,o);return s.querySelectorAll(".ctx-menu-item").forEach(c=>{c.addEventListener("click",async p=>{var f;p.preventDefault(),p.stopPropagation();const d=Number(c.dataset.idx),l=(f=n[d])==null?void 0:f.onClick;D();try{await(l==null?void 0:l())}catch(b){console.error(b)}})}),s}function Ee(e){if(!e||typeof e!="string")return"";const a=e.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);if(a){const t=a[1],n=a[2].padStart(2,"0"),o=a[3].padStart(2,"0");return`${t}-${n}-${o}`}return e}(function(){if(document.getElementById("swal-anims"))return;const a=document.createElement("style");a.id="swal-anims",a.textContent=`
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
  `,document.head.appendChild(a)})();function ke(e){const a=e.extendedProps||{};if(Array.isArray(a.planillas_ids)&&a.planillas_ids.length)return a.planillas_ids;const t=(e.id||"").match(/planilla-(\d+)/);return t?[Number(t[1])]:[]}async function $e(e,a){var t,n;try{D()}catch{}if(!e)return Swal.fire("⚠️","ID de salida inválido.","warning");try{const o=await fetch(`${(n=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:n.informacionPaquetesSalida}?salida_id=${e}`,{headers:{Accept:"application/json"}});if(!o.ok)throw new Error("Error al cargar información de la salida");const{salida:s,paquetesAsignados:c,paquetesDisponibles:p,paquetesTodos:d,filtros:l}=await o.json();qe(s,c,p,d||[],l||{obras:[],planillas:[],obrasRelacionadas:[]},a)}catch(o){console.error(o),Swal.fire("❌","Error al cargar la información de la salida","error")}}function qe(e,a,t,n,o,s){window._gestionPaquetesData={salida:e,paquetesAsignados:a,paquetesDisponibles:t,paquetesTodos:n,filtros:o,mostrarTodos:!1};const c=Ce(e,a,t,o);Swal.fire({title:`📦 Gestionar Paquetes - Salida ${e.codigo_salida||e.id}`,html:c,width:Math.min(window.innerWidth*.95,1200),showConfirmButton:!0,showCancelButton:!0,confirmButtonText:"💾 Guardar Cambios",cancelButtonText:"Cancelar",focusConfirm:!1,customClass:{popup:"w-full max-w-screen-xl"},didOpen:()=>{pe(),Le(),Ie(),setTimeout(()=>{De()},100)},willClose:()=>{C.cleanup&&C.cleanup();const p=document.getElementById("modal-keyboard-indicator");p&&p.remove()},preConfirm:()=>Me()}).then(async p=>{p.isConfirmed&&p.value&&await ze(e.id,p.value,s)})}function Ce(e,a,t,n){var l,f;const o=a.reduce((b,h)=>b+(parseFloat(h.peso)||0),0);let s="";e.salida_clientes&&e.salida_clientes.length>0&&(s='<div class="col-span-2"><strong>Obras/Clientes:</strong><br>',e.salida_clientes.forEach(b=>{var i,m,y,S,x;const h=((i=b.obra)==null?void 0:i.obra)||"Obra desconocida",r=(m=b.obra)!=null&&m.cod_obra?`(${b.obra.cod_obra})`:"",u=((y=b.cliente)==null?void 0:y.empresa)||((x=(S=b.obra)==null?void 0:S.cliente)==null?void 0:x.empresa)||"";s+=`<span class="text-xs">• ${h} ${r}`,u&&(s+=` - ${u}`),s+="</span><br>"}),s+="</div>");const c=`
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><strong>Código:</strong> ${e.codigo_salida||"N/A"}</div>
                <div><strong>Código SAGE:</strong> ${e.codigo_sage||"Sin asignar"}</div>
                <div><strong>Fecha salida:</strong> ${new Date(e.fecha_salida).toLocaleString("es-ES")}</div>
                <div><strong>Estado:</strong> ${e.estado||"pendiente"}</div>
                <div><strong>Empresa transporte:</strong> ${((l=e.empresa_transporte)==null?void 0:l.nombre)||"Sin asignar"}</div>
                <div><strong>Camión:</strong> ${((f=e.camion)==null?void 0:f.modelo)||"Sin asignar"}</div>
                ${s}
            </div>
        </div>
    `,p=((n==null?void 0:n.obras)||[]).map(b=>`<option value="${b.id}">${b.cod_obra||""} - ${b.obra||"Sin nombre"}</option>`).join(""),d=((n==null?void 0:n.planillas)||[]).map(b=>`<option value="${b.id}" data-obra-id="${b.obra_id||""}">${b.codigo||"Sin código"}</option>`).join("");return`
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
                            <span class="text-xs bg-green-200 px-2 py-1 rounded" id="peso-asignados">${o.toFixed(2)} kg</span>
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
                        ${U(a)}
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
                        ${U(t)}
                    </div>
                </div>
            </div>
        </div>
    `}function U(e){return!e||e.length===0?'<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>':e.map(a=>{var t,n,o,s,c,p,d,l,f,b,h,r,u,i,m,y;return`
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${a.id}"
            data-peso="${a.peso||0}"
            data-obra-id="${((n=(t=a.planilla)==null?void 0:t.obra)==null?void 0:n.id)||""}"
            data-obra="${((s=(o=a.planilla)==null?void 0:o.obra)==null?void 0:s.obra)||""}"
            data-planilla-id="${((c=a.planilla)==null?void 0:c.id)||""}"
            data-planilla="${((p=a.planilla)==null?void 0:p.codigo)||""}"
            data-cliente="${((l=(d=a.planilla)==null?void 0:d.cliente)==null?void 0:l.empresa)||""}"
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
                <div>📄 ${((f=a.planilla)==null?void 0:f.codigo)||a.planilla_id}</div>
                <div>🏗️ ${((h=(b=a.planilla)==null?void 0:b.obra)==null?void 0:h.cod_obra)||""} - ${((u=(r=a.planilla)==null?void 0:r.obra)==null?void 0:u.obra)||"N/A"}</div>
                <div>👤 ${((m=(i=a.planilla)==null?void 0:i.cliente)==null?void 0:m.empresa)||"Sin cliente"}</div>
                ${(y=a.nave)!=null&&y.obra?`<div class="text-blue-600 font-medium">📍 ${a.nave.obra}</div>`:""}
            </div>
        </div>
    `}).join("")}async function Ae(e){var a;try{const t=document.querySelector(`[data-paquete-id="${e}"]`);let n=null;if(t&&t.dataset.paqueteJson)try{n=JSON.parse(t.dataset.paqueteJson.replace(/&#39;/g,"'"))}catch(d){console.warn("No se pudo parsear JSON del paquete",d)}if(!n){const d=await fetch(`/api/paquetes/${e}/elementos`);d.ok&&(n=await d.json())}if(!n){alert("No se pudo obtener información del paquete");return}const o=[];if(n.etiquetas&&n.etiquetas.length>0&&n.etiquetas.forEach(d=>{d.elementos&&d.elementos.length>0&&d.elementos.forEach(l=>{o.push({id:l.id,dimensiones:l.dimensiones,peso:l.peso,longitud:l.longitud,diametro:l.diametro})})}),o.length===0){alert("Este paquete no tiene elementos para mostrar");return}const s=o.map((d,l)=>`
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">Elemento #${d.id}</span>
                    <span class="text-xs text-gray-500">${l+1} de ${o.length}</span>
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
        `).join(""),c=document.getElementById("modal-elementos-paquete-overlay");c&&c.remove();const p=`
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
                                <strong>Planilla:</strong> ${((a=n.planilla)==null?void 0:a.codigo)||"N/A"}<br>
                                <strong>Peso total:</strong> ${parseFloat(n.peso||0).toFixed(2)} kg<br>
                                <strong>Total elementos:</strong> ${o.length}
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
        `;document.body.insertAdjacentHTML("beforeend",p),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&o.forEach(d=>{d.dimensiones&&window.dibujarFiguraElemento(`elemento-dibujo-${d.id}`,d.dimensiones,null)})},100)}catch(t){console.error("Error al ver elementos del paquete:",t),alert("Error al cargar los elementos del paquete")}}window.verElementosPaqueteSalida=Ae;function Le(e){const a=document.getElementById("filtro-obra-modal"),t=document.getElementById("filtro-planilla-modal"),n=document.getElementById("btn-limpiar-filtros-modal");a&&a.addEventListener("change",()=>{ae(),X()}),t&&t.addEventListener("change",()=>{X()}),n&&n.addEventListener("click",()=>{a&&(a.value=""),t&&(t.value=""),ae(),X()})}function ae(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData;if(!a||!t)return;const n=(e==null?void 0:e.value)||"",o=n?t.paquetesTodos:t.paquetesDisponibles,s=new Map;o.forEach(d=>{var l,f,b;if((l=d.planilla)!=null&&l.id){if(n&&String((f=d.planilla.obra)==null?void 0:f.id)!==n)return;s.has(d.planilla.id)||s.set(d.planilla.id,{id:d.planilla.id,codigo:d.planilla.codigo||"Sin código",obra_id:(b=d.planilla.obra)==null?void 0:b.id})}});const c=Array.from(s.values()).sort((d,l)=>(d.codigo||"").localeCompare(l.codigo||"")),p=a.value;a.innerHTML='<option value="">-- Todas las planillas --</option>',c.forEach(d=>{const l=document.createElement("option");l.value=d.id,l.textContent=d.codigo,a.appendChild(l)}),p&&s.has(parseInt(p))?a.value=p:a.value=""}function X(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData,n=(e==null?void 0:e.value)||"",o=(a==null?void 0:a.value)||"",s=document.querySelector('[data-zona="disponibles"]');if(!s||!t)return;const c=document.querySelector('[data-zona="asignados"]'),p=new Set;c&&c.querySelectorAll(".paquete-item-salida").forEach(f=>{p.add(parseInt(f.dataset.paqueteId))});let l=(n?t.paquetesTodos:t.paquetesDisponibles).filter(f=>{var b,h,r;return!(p.has(f.id)||n&&String((h=(b=f.planilla)==null?void 0:b.obra)==null?void 0:h.id)!==n||o&&String((r=f.planilla)==null?void 0:r.id)!==o)});s.innerHTML=U(l),pe(),l.length===0&&(s.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">No hay paquetes que coincidan con el filtro</div>')}let C={zonaActiva:"asignados",indiceFocused:-1,cleanup:null};function De(){C.cleanup&&C.cleanup(),C.zonaActiva="asignados",C.indiceFocused=0,P();function e(a){var b;if(!document.querySelector(".swal2-container"))return;const t=a.target.tagName.toLowerCase(),n=t==="select";if((t==="input"||t==="textarea")&&a.key!=="Escape")return;const s=document.querySelector('[data-zona="asignados"]'),c=document.querySelector('[data-zona="disponibles"]');if(!s||!c)return;const p=C.zonaActiva==="asignados"?s:c,d=Array.from(p.querySelectorAll('.paquete-item-salida:not([style*="display: none"])')),l=d.length;let f=!1;if(!n)switch(a.key){case"ArrowDown":l>0&&(C.indiceFocused=(C.indiceFocused+1)%l,P(),f=!0);break;case"ArrowUp":l>0&&(C.indiceFocused=C.indiceFocused<=0?l-1:C.indiceFocused-1,P(),f=!0);break;case"ArrowLeft":case"ArrowRight":C.zonaActiva=C.zonaActiva==="asignados"?"disponibles":"asignados",C.indiceFocused=0,P(),f=!0;break;case"Tab":a.preventDefault(),C.zonaActiva=C.zonaActiva==="asignados"?"disponibles":"asignados",C.indiceFocused=0,P(),f=!0;break;case"Enter":{if(l>0&&C.indiceFocused>=0){const h=d[C.indiceFocused];if(h){Te(h);const r=Array.from(p.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));C.indiceFocused>=r.length&&(C.indiceFocused=Math.max(0,r.length-1)),P(),f=!0}}break}case"Home":C.indiceFocused=0,P(),f=!0;break;case"End":C.indiceFocused=Math.max(0,l-1),P(),f=!0;break}if(f){a.preventDefault(),a.stopPropagation();return}switch(a.key){case"o":case"O":{const h=document.getElementById("filtro-obra-modal");h&&(h.focus(),f=!0);break}case"p":case"P":{const h=document.getElementById("filtro-planilla-modal");h&&(h.focus(),f=!0);break}case"l":case"L":{const h=document.getElementById("btn-limpiar-filtros-modal");h&&(h.click(),(b=document.activeElement)==null||b.blur(),P(),f=!0);break}case"/":case"f":case"F":{const h=document.getElementById("filtro-obra-modal");h&&(h.focus(),f=!0);break}case"Escape":n&&(document.activeElement.blur(),P(),f=!0);break;case"s":case"S":{if(a.ctrlKey||a.metaKey){const h=document.querySelector(".swal2-confirm");h&&(h.click(),f=!0)}break}}f&&(a.preventDefault(),a.stopPropagation())}document.addEventListener("keydown",e,!0),C.cleanup=()=>{document.removeEventListener("keydown",e,!0),ce()}}function P(){ce();const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;C.zonaActiva==="asignados"?(e.classList.add("zona-activa-keyboard"),a.classList.remove("zona-activa-keyboard")):(a.classList.add("zona-activa-keyboard"),e.classList.remove("zona-activa-keyboard"));const t=C.zonaActiva==="asignados"?e:a,n=Array.from(t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));if(n.length>0&&C.indiceFocused>=0){const o=Math.min(C.indiceFocused,n.length-1),s=n[o];s&&(s.classList.add("paquete-focused-keyboard"),s.scrollIntoView({behavior:"smooth",block:"nearest"}))}_e()}function ce(){document.querySelectorAll(".paquete-focused-keyboard").forEach(e=>{e.classList.remove("paquete-focused-keyboard")}),document.querySelectorAll(".zona-activa-keyboard").forEach(e=>{e.classList.remove("zona-activa-keyboard")})}function Te(e){const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!a||!t)return;const n=e.closest("[data-zona]"),o=n.dataset.zona==="asignados"?t:a,s=o.querySelector(".placeholder-sin-paquetes");if(s&&s.remove(),o.appendChild(e),n.querySelectorAll(".paquete-item-salida").length===0){const p=document.createElement("div");p.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",p.textContent="Sin paquetes",n.appendChild(p)}ue(e),Y()}function _e(){let e=document.getElementById("modal-keyboard-indicator");e||(e=document.createElement("div"),e.id="modal-keyboard-indicator",e.className="fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs",document.body.appendChild(e));const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]'),n=(a==null?void 0:a.querySelectorAll(".paquete-item-salida").length)||0,o=(t==null?void 0:t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0,s=C.zonaActiva==="asignados"?`📦 Asignados (${n})`:`📋 Disponibles (${o})`;e.innerHTML=`
        <div class="flex items-center gap-2 mb-2">
            <span class="${C.zonaActiva==="asignados"?"bg-green-500":"bg-gray-500"} text-white text-xs px-2 py-0.5 rounded">${s}</span>
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
    `,clearTimeout(e._checkTimeout),e._checkTimeout=setTimeout(()=>{document.querySelector(".swal2-container")||e.remove()},500)}function Ie(){if(document.getElementById("modal-keyboard-styles"))return;const e=document.createElement("style");e.id="modal-keyboard-styles",e.textContent=`
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
    `,document.head.appendChild(e)}function ue(e){e.addEventListener("dragstart",a=>{e.style.opacity="0.5",a.dataTransfer.setData("text/plain",e.dataset.paqueteId)}),e.addEventListener("dragend",a=>{e.style.opacity="1"})}function pe(){document.querySelectorAll(".paquete-item-salida").forEach(e=>{ue(e)}),document.querySelectorAll(".drop-zone").forEach(e=>{e.addEventListener("dragover",a=>{a.preventDefault();const t=e.dataset.zona;e.style.backgroundColor=t==="asignados"?"#d1fae5":"#e0f2fe"}),e.addEventListener("dragleave",a=>{e.style.backgroundColor=""}),e.addEventListener("drop",a=>{a.preventDefault(),e.style.backgroundColor="";const t=a.dataTransfer.getData("text/plain"),n=document.querySelector(`.paquete-item-salida[data-paquete-id="${t}"]`);if(n){const o=e.querySelector(".placeholder-sin-paquetes");o&&o.remove(),e.appendChild(n),Y()}})})}function Y(){const e=document.querySelector('[data-zona="asignados"]'),a=e==null?void 0:e.querySelectorAll(".paquete-item-salida");let t=0;a==null||a.forEach(o=>{const s=parseFloat(o.dataset.peso)||0;t+=s});const n=document.getElementById("peso-asignados");n&&(n.textContent=`${t.toFixed(2)} kg`)}function Pe(){const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;const t=e.querySelectorAll(".paquete-item-salida");if(t.length===0)return;t.forEach(o=>{a.appendChild(o)});const n=a.querySelector(".placeholder-sin-paquetes");n&&n.remove(),e.querySelectorAll(".paquete-item-salida").length===0&&(e.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),Y()}function Fe(){const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;const t=Array.from(a.querySelectorAll(".paquete-item-salida")).filter(s=>s.style.display!=="none");if(t.length===0)return;const n=e.querySelector(".placeholder-sin-paquetes");n&&n.remove(),t.forEach(s=>{e.appendChild(s)}),a.querySelectorAll(".paquete-item-salida").length===0&&(a.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),Y()}window.vaciarSalidaModal=Pe;window.volcarTodosASalidaModal=Fe;function Me(){const e=document.querySelector('[data-zona="asignados"]');return{paquetes_ids:Array.from((e==null?void 0:e.querySelectorAll(".paquete-item-salida"))||[]).map(t=>parseInt(t.dataset.paqueteId))}}async function ze(e,a,t){var n,o,s,c;try{const d=await(await fetch((o=(n=window.AppSalidas)==null?void 0:n.routes)==null?void 0:o.guardarPaquetesSalida,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf},body:JSON.stringify({salida_id:e,paquetes_ids:a.paquetes_ids})})).json();d.success?(await Swal.fire({icon:"success",title:"✅ Cambios Guardados",text:"Los paquetes de la salida se han actualizado correctamente.",timer:2e3}),t&&(t.refetchEvents(),(c=t.refetchResources)==null||c.call(t))):await Swal.fire("⚠️",d.message||"No se pudieron guardar los cambios","warning")}catch(p){console.error(p),Swal.fire("❌","Error al guardar los paquetes","error")}}async function Be(e,a,t){try{D()}catch{}window.Livewire.dispatch("abrirComentario",{salidaId:e}),window._calendarRef=t}function Ne(e){return e?typeof e=="string"?e.split(",").map(t=>t.trim()).filter(Boolean):Array.from(e).map(t=>typeof t=="object"&&(t==null?void 0:t.id)!=null?t.id:t).map(String).map(t=>t.trim()).filter(Boolean):[]}async function Oe(e){var s,c;const a=(c=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:c.informacionPlanillas;if(!a)throw new Error("Ruta 'informacionPlanillas' no configurada");const t=`${a}?ids=${encodeURIComponent(e.join(","))}`,n=await fetch(t,{headers:{Accept:"application/json"}});if(!n.ok){const p=await n.text().catch(()=>"");throw new Error(`GET ${t} -> ${n.status} ${p}`)}const o=await n.json();return Array.isArray(o==null?void 0:o.planillas)?o.planillas:[]}function me(e){if(!e)return!1;const t=new Date(e+"T00:00:00").getDay();return t===0||t===6}function je(e,a,t,n){const o=document.getElementById("modal-figura-elemento-overlay");o&&o.remove();const s=n.getBoundingClientRect(),c=320,p=240;let d=s.right+10;d+c>window.innerWidth&&(d=s.left-c-10);let l=s.top-p/2+s.height/2;l<10&&(l=10),l+p>window.innerHeight-10&&(l=window.innerHeight-p-10);const f=`
        <div id="modal-figura-elemento-overlay"
             class="fixed bg-white rounded-lg shadow-2xl border border-gray-300"
             style="z-index: 10001; left: ${d}px; top: ${l}px; width: ${c}px;"
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
    `;document.body.insertAdjacentHTML("beforeend",f),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&window.dibujarFiguraElemento(`figura-elemento-container-${e}`,t,null)},50)}function He(e){return`
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
          <tbody>${e.map((t,n)=>{var r,u,i;const o=((r=t.obra)==null?void 0:r.codigo)||"",s=((u=t.obra)==null?void 0:u.nombre)||"",c=t.seccion||"";t.descripcion;const p=t.codigo||`Planilla ${t.id}`,d=t.peso_total?parseFloat(t.peso_total).toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})+" kg":"",l=Ee(t.fecha_estimada_entrega),f=t.elementos&&t.elementos.length>0,b=((i=t.elementos)==null?void 0:i.length)||0;let h="";return f&&(h=t.elementos.map((m,y)=>{const S=m.fecha_entrega||"",x=m.peso?parseFloat(m.peso).toFixed(2):"-",k=m.codigo||"-",E=m.dimensiones&&m.dimensiones.trim()!=="",v=E?m.dimensiones.replace(/"/g,"&quot;").replace(/'/g,"&#39;"):"",q=k.replace(/"/g,"&quot;").replace(/'/g,"&#39;");return`
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
                                        data-elemento-codigo="${q}"
                                        data-dimensiones="${v}"
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
                        <td class="px-2 py-1 text-xs text-right text-gray-500">${x} kg</td>
                        <td class="px-2 py-1" colspan="2">
                            <input type="date" class="swal2-input !m-0 !w-auto !text-xs elemento-fecha"
                                   data-elemento-id="${m.id}"
                                   data-planilla-id="${t.id}"
                                   value="${S}">
                        </td>
                    </tr>`}).join("")),`
<tr class="planilla-row hover:bg-blue-50 bg-blue-100 border-t border-blue-200" data-planilla-id="${t.id}" style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${n*18}ms;">
  <td class="px-2 py-2 text-xs font-semibold text-blue-800" colspan="2">
    ${f?`<button type="button" class="toggle-elementos mr-1 text-blue-600 hover:text-blue-800" data-planilla-id="${t.id}">▶</button>`:""}
    📄 ${p}
    ${f?`<span class="ml-1 text-xs text-blue-500 font-normal">(${b} elem.)</span>`:""}
  </td>
  <td class="px-2 py-2 text-xs text-blue-700" colspan="2">
    <span class="font-medium">${o}</span> ${s}
  </td>
  <td class="px-2 py-2 text-xs text-blue-600">${c||"-"}</td>
  <td class="px-2 py-2 text-xs text-right font-semibold text-blue-800">${d}</td>
  <td class="px-2 py-2" colspan="2">
    <div class="flex items-center gap-1">
      <input type="date" class="swal2-input !m-0 !w-auto planilla-fecha !bg-blue-50 !border-blue-300" data-planilla-id="${t.id}" value="${l}">
      ${f?`<button type="button" class="aplicar-fecha-elementos text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded" data-planilla-id="${t.id}" title="Aplicar fecha a todos los elementos">↓</button>`:""}
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
    </div>`}function We(e){const a={};return document.querySelectorAll('input[type="date"][data-planilla-id]').forEach(n=>{const o=parseInt(n.dataset.planillaId),s=n.value,c=e.find(p=>p.id===o);s&&c&&c.peso_total&&(a[s]||(a[s]={peso:0,planillas:0,esFinDeSemana:me(s)}),a[s].peso+=parseFloat(c.peso_total),a[s].planillas+=1)}),a}function ne(e){const a=We(e),t=document.getElementById("resumen-contenido");if(!t)return;const n=Object.keys(a).sort();if(n.length===0){t.innerHTML='<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';return}const o=n.map(p=>{const d=a[p],l=new Date(p+"T00:00:00").toLocaleDateString("es-ES",{weekday:"short",day:"2-digit",month:"2-digit",year:"numeric"}),f=d.peso.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2}),b=d.esFinDeSemana?"bg-orange-100 border-orange-300 text-orange-800":"bg-green-100 border-green-300 text-green-800",h=d.esFinDeSemana?"🏖️":"📦";return`
            <div class="inline-block m-1 px-2 py-1 rounded border ${b}">
                <span class="font-medium">${h} ${l}</span>
                <br>
                <span class="text-xs">${f} kg (${d.planillas} planilla${d.planillas!==1?"s":""})</span>
            </div>
        `}).join(""),s=n.reduce((p,d)=>p+a[d].peso,0),c=n.reduce((p,d)=>p+a[d].planillas,0);t.innerHTML=`
        <div class="mb-2">${o}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${s.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})} kg 
            (${c} planilla${c!==1?"s":""})
        </div>
    `}async function Re(e){var n,o,s;const a=(o=(n=window.AppSalidas)==null?void 0:n.routes)==null?void 0:o.actualizarFechasPlanillas;if(!a)throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");const t=await fetch(a,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf,Accept:"application/json"},body:JSON.stringify({planillas:e})});if(!t.ok){const c=await t.text().catch(()=>"");throw new Error(`PUT ${a} -> ${t.status} ${c}`)}return t.json().catch(()=>({}))}async function Ge(e,a){var t,n;try{const o=Array.from(new Set(Ne(e))).map(Number).filter(Boolean);if(!o.length)return Swal.fire("⚠️","No hay planillas en la agrupación.","warning");const s=await Oe(o);if(!s.length)return Swal.fire("⚠️","No se han encontrado planillas.","warning");const p=`
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `+He(s),{isConfirmed:d}=await Swal.fire({title:"",html:p,width:Math.min(window.innerWidth*.98,1200),customClass:{popup:"w-full max-w-screen-xl"},showCancelButton:!0,confirmButtonText:"💾 Guardar",cancelButtonText:"Cancelar",focusConfirm:!1,showClass:{popup:"swal-fade-in-zoom"},hideClass:{popup:"swal-fade-out"},didOpen:r=>{var y,S,x,k,E,v,q;Ve(r),R("#swal-drag",!1),setTimeout(()=>{const g=Swal.getHtmlContainer().querySelector('input[type="date"]');g==null||g.focus({preventScroll:!0})},120),Swal.getHtmlContainer().querySelectorAll('input[type="date"]').forEach(g=>{g.addEventListener("change",function(){me(this.value)?this.classList.add("weekend-date"):this.classList.remove("weekend-date"),ne(s)})});const i=Swal.getHtmlContainer();i.querySelectorAll(".toggle-elementos").forEach(g=>{g.addEventListener("click",A=>{A.stopPropagation();const $=g.dataset.planillaId,L=i.querySelectorAll(`.elemento-planilla-${$}`),_=g.textContent==="▼";L.forEach(T=>{T.classList.toggle("hidden",_)}),g.textContent=_?"▶":"▼"})}),(y=i.querySelector("#expandir-todos"))==null||y.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(g=>g.classList.remove("hidden")),i.querySelectorAll(".toggle-elementos").forEach(g=>g.textContent="▼")}),(S=i.querySelector("#colapsar-todos"))==null||S.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(g=>g.classList.add("hidden")),i.querySelectorAll(".toggle-elementos").forEach(g=>g.textContent="▶")});function m(){const A=i.querySelectorAll(".elemento-checkbox:checked").length,$=i.querySelector("#barra-acciones-masivas"),L=i.querySelector("#contador-seleccionados");A>0?($==null||$.classList.remove("hidden"),L&&(L.textContent=A)):$==null||$.classList.add("hidden")}i.querySelectorAll(".elemento-checkbox").forEach(g=>{g.addEventListener("change",m)}),(x=i.querySelector("#seleccionar-todos-elementos"))==null||x.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(g=>g.classList.remove("hidden")),i.querySelectorAll(".toggle-elementos").forEach(g=>g.textContent="▼"),i.querySelectorAll(".elemento-checkbox").forEach(g=>{g.checked=!0}),m()}),(k=i.querySelector("#seleccionar-sin-fecha"))==null||k.addEventListener("click",()=>{i.querySelectorAll(".elemento-row").forEach(g=>g.classList.remove("hidden")),i.querySelectorAll(".toggle-elementos").forEach(g=>g.textContent="▼"),i.querySelectorAll(".elemento-checkbox").forEach(g=>{g.checked=!1}),i.querySelectorAll(".elemento-checkbox").forEach(g=>{const A=g.dataset.elementoId,$=i.querySelector(`.elemento-fecha[data-elemento-id="${A}"]`);$&&!$.value&&(g.checked=!0)}),m()}),(E=i.querySelector("#deseleccionar-todos"))==null||E.addEventListener("click",()=>{i.querySelectorAll(".elemento-checkbox").forEach(g=>{g.checked=!1}),m()}),(v=i.querySelector("#aplicar-fecha-masiva"))==null||v.addEventListener("click",()=>{var _;const g=(_=i.querySelector("#fecha-masiva"))==null?void 0:_.value;if(!g){alert("Por favor, selecciona una fecha para aplicar");return}i.querySelectorAll(".elemento-checkbox:checked").forEach(T=>{const I=T.dataset.elementoId,M=i.querySelector(`.elemento-fecha[data-elemento-id="${I}"]`);M&&(M.value=g,M.dispatchEvent(new Event("change")))});const $=i.querySelector("#aplicar-fecha-masiva"),L=$.textContent;$.textContent="✓ Aplicado",$.classList.add("bg-green-600"),setTimeout(()=>{$.textContent=L,$.classList.remove("bg-green-600")},1500)}),(q=i.querySelector("#limpiar-fecha-seleccionados"))==null||q.addEventListener("click",()=>{i.querySelectorAll(".elemento-checkbox:checked").forEach(A=>{const $=A.dataset.elementoId,L=i.querySelector(`.elemento-fecha[data-elemento-id="${$}"]`);L&&(L.value="",L.dispatchEvent(new Event("change")))})}),i.querySelectorAll(".aplicar-fecha-elementos").forEach(g=>{g.addEventListener("click",A=>{var _;A.stopPropagation();const $=g.dataset.planillaId,L=(_=i.querySelector(`.planilla-fecha[data-planilla-id="${$}"]`))==null?void 0:_.value;L&&i.querySelectorAll(`.elemento-fecha[data-planilla-id="${$}"]`).forEach(T=>{T.value=L,T.dispatchEvent(new Event("change"))})})}),i.querySelectorAll(".ver-figura-elemento").forEach(g=>{g.addEventListener("mouseenter",A=>{var T,I;const $=g.dataset.elementoId,L=((T=g.dataset.elementoCodigo)==null?void 0:T.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"",_=((I=g.dataset.dimensiones)==null?void 0:I.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"";typeof window.dibujarFiguraElemento=="function"&&je($,L,_,g)}),g.addEventListener("mouseleave",A=>{setTimeout(()=>{const $=document.getElementById("modal-figura-elemento-overlay");$&&!$.matches(":hover")&&$.remove()},100)}),g.addEventListener("click",A=>{A.preventDefault(),A.stopPropagation();const $=g.dataset.elementoId,L=i.querySelector(`.elemento-checkbox[data-elemento-id="${$}"]`);if(L){L.checked=!L.checked;const T=i.querySelectorAll(".elemento-checkbox:checked").length,I=i.querySelector("#barra-acciones-masivas"),M=i.querySelector("#contador-seleccionados");T>0?(I==null||I.classList.remove("hidden"),M&&(M.textContent=T)):I==null||I.classList.add("hidden")}})}),setTimeout(()=>{ne(s)},100)}});if(!d)return;const l=Swal.getHtmlContainer(),f=l.querySelectorAll(".planilla-fecha"),b=Array.from(f).map(r=>{const u=Number(r.getAttribute("data-planilla-id")),i=l.querySelectorAll(`.elemento-fecha[data-planilla-id="${u}"]`),m=Array.from(i).map(y=>({id:Number(y.getAttribute("data-elemento-id")),fecha_entrega:y.value||null}));return{id:u,fecha_estimada_entrega:r.value,elementos:m.length>0?m:void 0}}),h=await Re(b);await Swal.fire(h.success?"✅":"⚠️",h.message||(h.success?"Fechas actualizadas":"No se pudieron actualizar"),h.success?"success":"warning"),h.success&&a&&((t=a.refetchEvents)==null||t.call(a),(n=a.refetchResources)==null||n.call(a))}catch(o){console.error("[CambiarFechasEntrega] error:",o),Swal.fire("❌",(o==null?void 0:o.message)||"Ocurrió un error al actualizar las fechas.","error")}}function oe(e,a){e.el.addEventListener("mousedown",D),e.el.addEventListener("contextmenu",t=>{t.preventDefault(),t.stopPropagation();const n=e.event,o=n.extendedProps||{},s=o.tipo||"planilla";let c="";if(s==="salida"){if(o.clientes&&Array.isArray(o.clientes)&&o.clientes.length>0){const l=o.clientes.map(f=>f.nombre).filter(Boolean).join(", ");l&&(c+=`<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${l}</span>`)}o.obras&&Array.isArray(o.obras)&&o.obras.length>0&&(c+='<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ ',c+=o.obras.map(l=>{const f=l.codigo?`(${l.codigo})`:"";return`${l.nombre} ${f}`}).join(", "),c+="</span>")}const p=`
      <div style="padding:10px 12px; font-weight:600;">
        ${n.title??"Evento"}${c}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(n.start).toLocaleString()} — ${new Date(n.end).toLocaleString()}
        </span>
      </div>
    `;let d=[];if(s==="planilla"){const l=ke(n);d=[{label:"Gestionar Salidas y Paquetes",icon:"📦",onClick:()=>window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${l.join(",")}`},{label:"Cambiar fechas de entrega",icon:"🗓️",onClick:()=>Ge(l,a)}]}else if(s==="salida"){const l=o.salida_id||n.id;o.empresa_id,o.empresa,d=[{label:"Abrir salida",icon:"🧾",onClick:()=>window.open(`/salidas-ferralla/${l}`,"_blank")},{label:"Gestionar paquetes",icon:"📦",onClick:()=>$e(l,a)},{label:"Agregar comentario",icon:"✍️",onClick:()=>Be(l,o.comentario||"",a)}]}else d=[{label:"Abrir",icon:"🧾",onClick:()=>window.open(o.url||"#","_blank")}];Se(t.clientX,t.clientY,{headerHtml:p,items:d})})}function Ve(e){e.style.transform="none",e.style.position="fixed",e.style.margin="0";const a=e.offsetWidth,t=e.offsetHeight,n=Math.max(0,Math.round((window.innerWidth-a)/2)),o=Math.max(0,Math.round((window.innerHeight-t)/2));e.style.left=`${n}px`,e.style.top=`${o}px`}function R(e=".swal2-title",a=!1){const t=Swal.getPopup(),n=Swal.getHtmlContainer();let o=(e?(n==null?void 0:n.querySelector(e))||(t==null?void 0:t.querySelector(e)):null)||t;if(!t||!o)return;a&&R.__lastPos&&(t.style.left=R.__lastPos.left,t.style.top=R.__lastPos.top,t.style.transform="none"),o.style.cursor="move",o.style.touchAction="none";const s=u=>{var i;return((i=u.closest)==null?void 0:i.call(u,"input, textarea, select, button, a, label, [contenteditable]"))!=null};let c=!1,p=0,d=0,l=0,f=0;const b=u=>{if(!o.contains(u.target)||s(u.target))return;c=!0,document.body.style.userSelect="none";const i=t.getBoundingClientRect();t.style.left=`${i.left}px`,t.style.top=`${i.top}px`,t.style.transform="none",l=parseFloat(t.style.left||i.left),f=parseFloat(t.style.top||i.top),p=u.clientX,d=u.clientY,document.addEventListener("pointermove",h),document.addEventListener("pointerup",r,{once:!0})},h=u=>{if(!c)return;const i=u.clientX-p,m=u.clientY-d;let y=l+i,S=f+m;const x=t.offsetWidth,k=t.offsetHeight,E=-x+40,v=window.innerWidth-40,q=-k+40,g=window.innerHeight-40;y=Math.max(E,Math.min(v,y)),S=Math.max(q,Math.min(g,S)),t.style.left=`${y}px`,t.style.top=`${S}px`},r=()=>{c=!1,document.body.style.userSelect="",a&&(R.__lastPos={left:t.style.left,top:t.style.top}),document.removeEventListener("pointermove",h)};o.addEventListener("pointerdown",b)}document.addEventListener("DOMContentLoaded",function(){window.addEventListener("comentarioGuardado",e=>{const{salidaId:a,comentario:t}=e.detail,n=window._calendarRef;if(n){const o=n.getEventById(`salida-${a}`);o&&(o.setExtendedProp("comentario",t),o._def&&o._def.extendedProps&&(o._def.extendedProps.comentario=t)),typeof Swal<"u"&&Swal.fire({icon:"success",title:"Comentario guardado",text:"El comentario se ha guardado correctamente",timer:2e3,showConfirmButton:!1,toast:!0,position:"top-end"})}})});let w=null;function Ye(e,a){const t=()=>e&&e.offsetParent!==null&&e.clientWidth>0&&e.clientHeight>=0;if(t())return a();if("IntersectionObserver"in window){const o=new IntersectionObserver(s=>{s.some(p=>p.isIntersecting)&&(o.disconnect(),a())},{root:null,threshold:.01});o.observe(e);return}if("ResizeObserver"in window){const o=new ResizeObserver(()=>{t()&&(o.disconnect(),a())});o.observe(e);return}const n=setInterval(()=>{t()&&(clearInterval(n),a())},100)}function F(){w&&(requestAnimationFrame(()=>{try{w.updateSize()}catch{}}),setTimeout(()=>{try{w.updateSize()}catch{}},150))}function Ke(){let e=document.getElementById("transparent-drag-image");return e||(e=document.createElement("img"),e.id="transparent-drag-image",e.src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7",e.style.cssText="position: fixed; left: -9999px; top: -9999px; width: 1px; height: 1px; opacity: 0;",document.body.appendChild(e)),e}function Xe(e,a){var u,i;Z();const t=document.createElement("div");t.id="custom-drag-ghost",t.className="custom-drag-ghost";const n=e.extendedProps||{},o=n.tipo==="salida",s=o?"🚚":"📋",c=o?"Salida":"Planilla",p=n.cod_obra||"",d=n.nombre_obra||((u=e.title)==null?void 0:u.split(`
`)[0])||"",l=n.cliente||"",f=n.pesoTotal?Number(n.pesoTotal).toLocaleString("es-ES",{maximumFractionDigits:0}):"",b=n.longitudTotal?Number(n.longitudTotal).toLocaleString("es-ES",{maximumFractionDigits:0}):"",h=n.diametroMedio?Number(n.diametroMedio).toFixed(1):"",r=((i=a==null?void 0:a.style)==null?void 0:i.backgroundColor)||e.backgroundColor||"#6366f1";return t.innerHTML=`
        <div class="ghost-card" style="--ghost-color: ${r};">
            <!-- Tipo -->
            <div class="ghost-type-badge ${o?"badge-salida":"badge-planilla"}">
                <span>${s}</span>
                <span>${c}</span>
            </div>

            <!-- Info principal -->
            <div class="ghost-main">
                ${p?`<div class="ghost-code">${p}</div>`:""}
                ${d?`<div class="ghost-name">${d}</div>`:""}
                ${l?`<div class="ghost-client">👤 ${l}</div>`:""}
            </div>

            <!-- Métricas -->
            ${f||b||h?`
            <div class="ghost-metrics">
                ${f?`<span class="ghost-metric">📦 ${f} kg</span>`:""}
                ${b?`<span class="ghost-metric">📏 ${b} m</span>`:""}
                ${h?`<span class="ghost-metric">⌀ ${h} mm</span>`:""}
            </div>
            `:""}

            <!-- Destino del drop -->
            <div class="ghost-destination">
                <span class="ghost-dest-date">--</span>
            </div>
        </div>
    `,document.body.appendChild(t),t}function se(e,a,t,n){const o=document.getElementById("custom-drag-ghost");if(o){if(o.style.left=`${e+20}px`,o.style.top=`${a-20}px`,t){const s=o.querySelector(".ghost-dest-time");s&&(s.textContent=t)}if(n){const s=o.querySelector(".ghost-dest-date");if(s){const c=new Date(n+"T00:00:00"),p={weekday:"short",day:"numeric",month:"short"};s.textContent=c.toLocaleDateString("es-ES",p)}}}}function Z(){const e=document.getElementById("custom-drag-ghost");e&&e.remove()}function re(e,a){const t=a==null?void 0:a.querySelector(".fc-timegrid-slots");if(!t)return null;const n=t.getBoundingClientRect(),o=e-n.top+t.scrollTop,s=t.scrollHeight||n.height,c=5,p=20,d=p-c,l=o/s*d,f=c*60+l*60,b=Math.round(f/30)*30,h=Math.max(c,Math.min(p-1,Math.floor(b/60))),r=b%60;return`${String(h).padStart(2,"0")}:${String(r).padStart(2,"0")}`}function ie(e){const a=document.querySelectorAll(".fc-timegrid-slot, .fc-timegrid-col");e?a.forEach(t=>{t.classList.add("fc-drop-zone-highlight")}):a.forEach(t=>{t.classList.remove("fc-drop-zone-highlight")})}function Je(){if(!window.FullCalendar)return console.error("FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."),null;if(!document.getElementById("fc-mirror-hide-style-global")){const l=document.createElement("style");l.id="fc-mirror-hide-style-global",l.textContent=`
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
        `,document.head.appendChild(l)}w&&w.destroy();const e=["resourceTimeGridDay","resourceTimelineWeek","dayGridMonth"];let a=localStorage.getItem("ultimaVistaCalendario");e.includes(a)||(a="resourceTimeGridDay");const t=localStorage.getItem("fechaCalendario");let n=null;const o=document.getElementById("calendario");if(!o)return console.error("#calendario no encontrado"),null;function s(l){return w?w.getEvents().some(f=>{var r,u;const b=(f.startStr||((r=f.start)==null?void 0:r.toISOString())||"").split("T")[0];return(((u=f.extendedProps)==null?void 0:u.tipo)==="festivo"||typeof f.id=="string"&&f.id.startsWith("festivo-"))&&b===l}):!1}Ye(o,()=>{w=new FullCalendar.Calendar(o,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",navLinks:!0,navLinkDayClick:(r,u)=>{var S;const i=r.getDay(),m=i===0||i===6,y=(S=w==null?void 0:w.view)==null?void 0:S.type;if(m&&(y==="resourceTimelineWeek"||y==="dayGridMonth")){u.preventDefault();let x;y==="dayGridMonth"?x=i===6?"saturday":"sunday":x=r.toISOString().split("T")[0],window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(x)?window.expandedWeekendDays.delete(x):window.expandedWeekendDays.add(x),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),w.render(),setTimeout(()=>{var k;return(k=window.applyWeekendCollapse)==null?void 0:k.call(window)},50);return}w.changeView("resourceTimeGridDay",r)},initialView:a,initialDate:t?new Date(t):void 0,dayMaxEventRows:!1,dayMaxEvents:!1,slotMinTime:"05:00:00",slotMaxTime:"20:00:00",buttonText:{today:"Hoy",resourceTimeGridDay:"Día",resourceTimelineWeek:"Semana",dayGridMonth:"Mes"},progressiveEventRendering:!0,expandRows:!0,height:"auto",events:(r,u,i)=>{var y;const m=r.view&&r.view.type||((y=w==null?void 0:w.view)==null?void 0:y.type)||"resourceTimeGridDay";ve(m,r).then(u).catch(i)},resources:(r,u,i)=>{var y;const m=r.view&&r.view.type||((y=w==null?void 0:w.view)==null?void 0:y.type)||"resourceTimeGridDay";be(m,r).then(u).catch(i)},headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimeGridDay,resourceTimelineWeek,dayGridMonth"},eventOrderStrict:!0,eventOrder:(r,u)=>{var x,k;const i=((x=r.extendedProps)==null?void 0:x.tipo)==="resumen-dia",m=((k=u.extendedProps)==null?void 0:k.tipo)==="resumen-dia";if(i&&!m)return-1;if(!i&&m)return 1;const y=parseInt(String(r.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0,S=parseInt(String(u.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0;return y-S},datesSet:r=>{try{const u=Ue(r);localStorage.setItem("fechaCalendario",u),localStorage.setItem("ultimaVistaCalendario",r.view.type),p(),clearTimeout(n),n=setTimeout(async()=>{J(),w.refetchResources(),w.refetchEvents(),await we(r.view.type,{startStr:r.startStr,endStr:r.endStr}),F(),(r.view.type==="resourceTimelineWeek"||r.view.type==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)},0)}catch(u){console.error("Error en datesSet:",u)}},loading:r=>{if(!r&&w){const u=w.view.type;u==="resourceTimeGridDay"&&setTimeout(()=>d(),150),(u==="resourceTimelineWeek"||u==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)}},viewDidMount:r=>{p(),r.view.type==="resourceTimeGridDay"&&setTimeout(()=>d(),100),r.view.type==="dayGridMonth"&&setTimeout(()=>{document.querySelectorAll(".fc-daygrid-event-harness").forEach(u=>{u.querySelector(".evento-resumen-diario")||(u.style.setProperty("width","100%","important"),u.style.setProperty("max-width","100%","important"),u.style.setProperty("position","static","important"),u.style.setProperty("left","unset","important"),u.style.setProperty("right","unset","important"),u.style.setProperty("top","unset","important"),u.style.setProperty("inset","unset","important"),u.style.setProperty("margin","0 0 2px 0","important"))}),document.querySelectorAll(".fc-daygrid-event:not(.evento-resumen-diario)").forEach(u=>{u.style.setProperty("width","100%","important"),u.style.setProperty("max-width","100%","important"),u.style.setProperty("margin","0","important"),u.style.setProperty("position","static","important"),u.style.setProperty("left","unset","important"),u.style.setProperty("right","unset","important"),u.style.setProperty("inset","unset","important")})},50)},eventContent:r=>{var S;const u=r.event.backgroundColor||"#9CA3AF",i=r.event.extendedProps||{},m=(S=w==null?void 0:w.view)==null?void 0:S.type;if(i.tipo==="resumen-dia"){const x=Number(i.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),k=Number(i.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),E=i.diametroMedio?Number(i.diametroMedio).toFixed(1):null;if(m==="resourceTimelineWeek")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${x} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${k} m</div>
                                ${E?`<div class="text-yellow-800">⌀ ${E} mm</div>`:""}
                            </div>
                        `};if(m==="dayGridMonth")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${x} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${k} m</div>
                                ${E?`<div class="text-yellow-800">⌀ ${E} mm</div>`:""}
                            </div>
                        `}}let y=`
        <div style="background-color:${u}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${r.event.title}</div>
    `;if(i.tipo==="planilla"){const x=i.pesoTotal!=null?`📦 ${Number(i.pesoTotal).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2})} kg`:null,k=i.longitudTotal!=null?`📏 ${Number(i.longitudTotal).toLocaleString()} m`:null,E=i.diametroMedio!=null?`⌀ ${Number(i.diametroMedio).toFixed(2)} mm`:null,v=[x,k,E].filter(Boolean);v.length>0&&(y+=`<div class="text-sm text-black font-semibold">${v.join(" | ")}</div>`),i.tieneSalidas&&Array.isArray(i.salidas_codigos)&&i.salidas_codigos.length>0&&(y+=`
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${i.salidas_codigos.join(", ")}
                </span>
            </div>`)}return y+="</div>",{html:y}},eventDidMount:function(r){var y,S,x,k;const u=r.event.extendedProps||{};if(r.el.setAttribute("draggable","false"),r.el.ondragstart=E=>(E.preventDefault(),!1),u.tipo==="resumen-dia"){r.el.classList.add("evento-resumen-diario"),r.el.style.cursor="default";return}if(r.view.type==="dayGridMonth"){const E=r.el.closest(".fc-daygrid-event-harness");E&&E.classList.add("evento-fullwidth"),r.el.classList.add("evento-fullwidth-event")}const i=(((y=document.getElementById("filtro-obra"))==null?void 0:y.value)||"").trim().toLowerCase(),m=(((S=document.getElementById("filtro-nombre-obra"))==null?void 0:S.value)||"").trim().toLowerCase();if(i||m){let E=!1;if(u.tipo==="salida"&&u.obras&&Array.isArray(u.obras))E=u.obras.some(v=>{const q=(v.codigo||"").toString().toLowerCase(),g=(v.nombre||"").toString().toLowerCase();return i&&q.includes(i)||m&&g.includes(m)});else{const v=(((x=r.event.extendedProps)==null?void 0:x.cod_obra)||"").toString().toLowerCase(),q=(((k=r.event.extendedProps)==null?void 0:k.nombre_obra)||r.event.title||"").toString().toLowerCase();E=i&&v.includes(i)||m&&q.includes(m)}E&&r.el.classList.add("evento-filtrado")}typeof te=="function"&&te(r),typeof oe=="function"&&oe(r,w)},eventAllow:(r,u)=>{var m;const i=(m=u.extendedProps)==null?void 0:m.tipo;return!(i==="resumen-dia"||i==="festivo")},snapDuration:"00:30:00",eventDragStart:r=>{var E;window._isDragging=!0,window._draggedEvent=r.event,Xe(r.event,r.el),document.body.classList.add("fc-dragging-active");const u=Ke(),i=v=>{v.dataTransfer&&window._isDragging&&v.dataTransfer.setDragImage(u,0,0)};document.addEventListener("dragstart",i,!0),window._nativeDragStartHandler=i;const m=document.getElementById("calendario");((E=w==null?void 0:w.view)==null?void 0:E.type)==="resourceTimeGridDay"&&ie(!0);const y=(v,q)=>{const g=document.elementsFromPoint(v,q);for(const A of g){const $=A.closest(".fc-daygrid-day");if($)return $.getAttribute("data-date");const L=A.closest("[data-date]");if(L)return L.getAttribute("data-date")}return null};let S=!1;const x=v=>{!window._isDragging||S||(S=!0,requestAnimationFrame(()=>{if(S=!1,!window._isDragging)return;const q=re(v.clientY,m),g=y(v.clientX,v.clientY);se(v.clientX,v.clientY,q,g)}))};if(document.addEventListener("mousemove",x,{passive:!0}),window._dragMouseMoveHandler=x,r.jsEvent){const v=re(r.jsEvent.clientY,m),q=y(r.jsEvent.clientX,r.jsEvent.clientY);se(r.jsEvent.clientX,r.jsEvent.clientY,v,q)}window._dragOriginalStart=r.event.start,window._dragOriginalEnd=r.event.end,window._dragEventId=r.event.id;const k=v=>{if(window._isDragging){v.preventDefault(),v.stopPropagation(),v.stopImmediatePropagation(),window._cancelDrag=!0,Z();const q=new PointerEvent("pointerup",{bubbles:!0,cancelable:!0,clientX:v.clientX,clientY:v.clientY});document.dispatchEvent(q)}};document.addEventListener("contextmenu",k,{capture:!0}),window._dragContextMenuHandler=k},eventDragStop:r=>{window._isDragging=!1,window._draggedEvent=null,window._nativeDragStartHandler&&(document.removeEventListener("dragstart",window._nativeDragStartHandler,!0),window._nativeDragStartHandler=null),window._dragMouseMoveHandler&&(document.removeEventListener("mousemove",window._dragMouseMoveHandler),window._dragMouseMoveHandler=null),window._dragContextMenuHandler&&(document.removeEventListener("contextmenu",window._dragContextMenuHandler,{capture:!0}),window._dragContextMenuHandler=null),window._dragOriginalStart=null,window._dragOriginalEnd=null,window._dragEventId=null,Z(),document.body.classList.remove("fc-dragging-active"),ie(!1)},eventDrop:r=>{var x,k,E,v;if(window._cancelDrag){window._cancelDrag=!1,r.revert(),window._dragOriginalStart&&(r.event.setStart(window._dragOriginalStart),window._dragOriginalEnd&&r.event.setEnd(window._dragOriginalEnd));return}const u=r.event.extendedProps||{},i=r.event.id,m=(x=r.event.start)==null?void 0:x.toISOString(),y={fecha:m,tipo:u.tipo,planillas_ids:u.planillas_ids||[],elementos_ids:u.elementos_ids||[]},S=(((E=(k=window.AppSalidas)==null?void 0:k.routes)==null?void 0:E.updateItem)||"").replace("__ID__",i);Swal.fire({title:"Actualizando fecha...",html:"Verificando programación de fabricación",allowOutsideClick:!1,allowEscapeKey:!1,showConfirmButton:!1,didOpen:()=>{Swal.showLoading()}}),fetch(S,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(v=window.AppSalidas)==null?void 0:v.csrf},body:JSON.stringify(y)}).then(q=>{if(!q.ok)throw new Error("No se pudo actualizar la fecha.");return q.json()}).then(async q=>{Swal.close(),J(),w.refetchEvents(),w.refetchResources(),F(),q.alerta_retraso&&Swal.fire({icon:"warning",title:"⚠️ Fecha de entrega adelantada",html:`
                                    <div class="text-left">
                                        <p class="mb-2">${q.alerta_retraso.mensaje}</p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fin fabricación:</strong> ${q.alerta_retraso.fin_programado}</p>
                                            <p class="text-sm"><strong>Fecha entrega:</strong> ${q.alerta_retraso.fecha_entrega}</p>
                                        </div>
                                        <p class="mt-3 text-sm text-gray-600">Los elementos no estarán listos para la fecha indicada según la programación actual de máquinas.</p>
                                    </div>
                                `,showCancelButton:!0,confirmButtonText:"🚀 Adelantar fabricación",cancelButtonText:"Entendido",confirmButtonColor:"#10b981",cancelButtonColor:"#f59e0b"}).then(g=>{g.isConfirmed&&Ze(u.elementos_ids,m)})}).catch(q=>{Swal.close(),console.error("Error:",q),Swal.fire({icon:"error",title:"Error",text:"No se pudo actualizar la fecha.",timer:3e3}),r.revert()})},dateClick:r=>{s(r.dateStr)&&Swal.fire({icon:"info",title:"📅 Día festivo",text:"Los festivos se editan en la planificación de Trabajadores.",confirmButtonText:"Entendido"})},eventMinHeight:30,firstDay:1,slotLabelContent:r=>{var E,v;if(((E=w==null?void 0:w.view)==null?void 0:E.type)!=="resourceTimelineWeek")return null;const i=r.date;if(!i)return null;const m=i.getDay(),y=m===0||m===6,S=i.toISOString().split("T")[0],x={weekday:"short",day:"numeric",month:"short"},k=i.toLocaleDateString("es-ES",x);if(y){const g=!((v=window.expandedWeekendDays)==null?void 0:v.has(S)),A=g?"▶":"▼",$=g?i.toLocaleDateString("es-ES",{weekday:"short"}).substring(0,3):k;return{html:`<div class="weekend-header cursor-pointer select-none hover:bg-gray-200 px-1 rounded"
                                    data-date="${S}"
                                    data-collapsed="${g}"
                                    title="${g?"Clic para expandir":"Clic para colapsar"}">
                                <span class="collapse-icon text-xs mr-1">${A}</span>
                                <span class="weekend-label">${$}</span>
                               </div>`}}return{html:`<span>${k}</span>`}},views:{resourceTimelineWeek:{slotDuration:{days:1}},resourceTimeGridDay:{slotDuration:"01:00:00",slotLabelFormat:{hour:"2-digit",minute:"2-digit",hour12:!1},slotLabelInterval:"01:00:00",allDaySlot:!1}},editable:!0,eventDurationEditable:!1,eventStartEditable:!0,resourceAreaColumns:[{field:"cod_obra",headerContent:"Código"},{field:"title",headerContent:"Obra"},{field:"cliente",headerContent:"Cliente"}],resourceAreaHeaderContent:"Obras",resourceOrder:"orderIndex",resourceLabelContent:r=>({html:`<div class="text-xs font-semibold">
                        <div class="text-blue-600">${r.resource.extendedProps.cod_obra||""}</div>
                        <div class="text-gray-700 truncate">${r.resource.title||""}</div>
                        <div class="text-gray-500 text-[10px] truncate">${r.resource.extendedProps.cliente||""}</div>
                    </div>`}),windowResize:()=>F()}),w.render(),F();const l=localStorage.getItem("expandedWeekendDays");window.expandedWeekendDays=new Set(l?JSON.parse(l):[]),window.weekendDefaultCollapsed=!0;function f(r){const i=new Date(r+"T00:00:00").getDay();return i===0||i===6}function b(){var u,i,m;const r=(u=w==null?void 0:w.view)==null?void 0:u.type;if(r==="resourceTimelineWeek"&&(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(x=>{var E;const k=x.getAttribute("data-date");f(k)&&(((E=window.expandedWeekendDays)==null?void 0:E.has(k))?x.classList.remove("weekend-collapsed"):x.classList.add("weekend-collapsed"))}),document.querySelectorAll(".fc-timeline-lane td[data-date]").forEach(x=>{var E;const k=x.getAttribute("data-date");f(k)&&(((E=window.expandedWeekendDays)==null?void 0:E.has(k))?x.classList.remove("weekend-collapsed"):x.classList.add("weekend-collapsed"))})),r==="dayGridMonth"){const y=(i=window.expandedWeekendDays)==null?void 0:i.has("saturday"),S=(m=window.expandedWeekendDays)==null?void 0:m.has("sunday"),x=document.querySelectorAll(".fc-col-header-cell.fc-day-sat"),k=document.querySelectorAll(".fc-col-header-cell.fc-day-sun");x.forEach(v=>{y?v.classList.remove("weekend-day-collapsed"):v.classList.add("weekend-day-collapsed")}),k.forEach(v=>{S?v.classList.remove("weekend-day-collapsed"):v.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sat").forEach(v=>{y?v.classList.remove("weekend-day-collapsed"):v.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sun").forEach(v=>{S?v.classList.remove("weekend-day-collapsed"):v.classList.add("weekend-day-collapsed")});const E=document.querySelector(".fc-dayGridMonth-view table");if(E){let v=E.querySelector("colgroup");if(!v){v=document.createElement("colgroup");for(let g=0;g<7;g++)v.appendChild(document.createElement("col"));E.insertBefore(v,E.firstChild)}const q=v.querySelectorAll("col");q.length>=7&&(q[5].style.width=y?"":"40px",q[6].style.width=S?"":"40px")}}}function h(r){window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(r)?window.expandedWeekendDays.delete(r):window.expandedWeekendDays.add(r),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),b()}o.addEventListener("click",r=>{var m;const u=r.target.closest(".weekend-header");if(u){const y=u.getAttribute("data-date");if(y){r.preventDefault(),r.stopPropagation(),h(y);return}}if(((m=w==null?void 0:w.view)==null?void 0:m.type)==="dayGridMonth"){const y=r.target.closest(".fc-col-header-cell.fc-day-sat, .fc-col-header-cell.fc-day-sun");if(y){r.preventDefault(),r.stopPropagation();const k=y.classList.contains("fc-day-sat")?"saturday":"sunday";h(k);return}const S=r.target.closest(".fc-daygrid-day.fc-day-sat, .fc-daygrid-day.fc-day-sun");if(S&&!r.target.closest(".fc-event")){r.preventDefault(),r.stopPropagation();const k=S.classList.contains("fc-day-sat")?"saturday":"sunday";h(k);return}}},!0),setTimeout(()=>b(),100),window.applyWeekendCollapse=b,o.addEventListener("contextmenu",r=>{if(window._isDragging||window._cancelDrag)return;const u=r.target.closest(".fc-daygrid-day, .fc-timeline-slot, .fc-timegrid-slot, .fc-col-header-cell");if(u){let i=u.getAttribute("data-date");if(!i){const m=r.target.closest("[data-date]");m&&(i=m.getAttribute("data-date"))}if(i&&w){const m=w.view.type;(m==="resourceTimelineWeek"||m==="dayGridMonth")&&(r.preventDefault(),r.stopPropagation(),Swal.fire({title:"📅 Ir a día",text:`¿Quieres ver el día ${i}?`,icon:"question",showCancelButton:!0,confirmButtonText:"Sí, ir al día",cancelButtonText:"Cancelar"}).then(y=>{y.isConfirmed&&(w.changeView("resourceTimeGridDay",i),F())}))}}})}),window.addEventListener("shown.bs.tab",F),window.addEventListener("shown.bs.collapse",F),window.addEventListener("shown.bs.modal",F);function p(){document.querySelectorAll(".resumen-diario-custom").forEach(f=>f.remove())}function d(){if(!w||w.view.type!=="resourceTimeGridDay"){p();return}p();const l=w.getDate(),f=l.getFullYear(),b=String(l.getMonth()+1).padStart(2,"0"),h=String(l.getDate()).padStart(2,"0"),r=`${f}-${b}-${h}`,u=w.getEvents().find(i=>{var m,y;return((m=i.extendedProps)==null?void 0:m.tipo)==="resumen-dia"&&((y=i.extendedProps)==null?void 0:y.fecha)===r});if(u&&u.extendedProps){const i=Number(u.extendedProps.pesoTotal||0).toLocaleString(),m=Number(u.extendedProps.longitudTotal||0).toLocaleString(),y=u.extendedProps.diametroMedio?Number(u.extendedProps.diametroMedio).toFixed(2):null,S=document.createElement("div");S.className="resumen-diario-custom",S.innerHTML=`
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 Peso: ${i} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${m} m</div>
                        ${y?`<div class="text-yellow-800">⌀ Diámetro: ${y} mm</div>`:""}
                    </div>
                </div>
            `,o&&o.parentNode&&o.parentNode.insertBefore(S,o)}}return window.mostrarResumenDiario=d,window.limpiarResumenesCustom=p,w}function Ue(e){if(e.view.type==="dayGridMonth"){const a=new Date(e.start);return a.setDate(a.getDate()+15),a.toISOString().split("T")[0]}if(e.view.type==="resourceTimeGridWeek"||e.view.type==="resourceTimelineWeek"){const a=new Date(e.start),t=Math.floor((e.end-e.start)/(1e3*60*60*24)/2);return a.setDate(a.getDate()+t),a.toISOString().split("T")[0]}return e.startStr.split("T")[0]}function Ze(e,a){var o;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para adelantar"});return}Swal.fire({title:"Analizando opciones...",html:"Calculando la mejor posición para adelantar la fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const t=new AbortController,n=setTimeout(()=>t.abort(),6e4);fetch("/planificacion/simular-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(o=window.AppSalidas)==null?void 0:o.csrf},body:JSON.stringify({elementos_ids:e,fecha_entrega:a}),signal:t.signal}).then(s=>{if(clearTimeout(n),!s.ok)throw new Error("Error en la simulación");return s.json()}).then(s=>{if(!s.necesita_adelanto){const l=(s.mensaje||"Los elementos llegarán a tiempo.").replace(/\n/g,"<br>").replace(/•/g,'<span class="text-amber-600">•</span>');s.razones&&s.razones.length>0&&s.razones.some(h=>h.fin_minimo)?Swal.fire({icon:"warning",title:"No se puede entregar a tiempo",html:`
                            <div class="text-left text-sm mb-4">${l}</div>
                            <div class="text-left text-sm font-semibold text-amber-700 border-t pt-3">
                                ¿Deseas adelantar a primera posición de todas formas?
                            </div>
                        `,width:650,showCancelButton:!0,confirmButtonText:"Sí, adelantar a 1ª posición",cancelButtonText:"Cancelar",confirmButtonColor:"#f59e0b",cancelButtonColor:"#6b7280"}).then(h=>{if(h.isConfirmed){const r=[];s.razones.filter(u=>u.fin_minimo).forEach(u=>{u.planillas_ids&&u.planillas_ids.length>0?u.planillas_ids.forEach(i=>{r.push({planilla_id:i,maquina_id:u.maquina_id,posicion_nueva:1})}):u.planilla_id&&r.push({planilla_id:u.planilla_id,maquina_id:u.maquina_id,posicion_nueva:1})}),r.length>0?(console.log("Órdenes a adelantar:",r),le(r)):(console.warn("No se encontraron órdenes para adelantar",s.razones),Swal.fire({icon:"warning",title:"Sin órdenes",text:"No se encontraron órdenes para adelantar."}))}}):Swal.fire({icon:"info",title:"No es necesario adelantar",html:`<div class="text-left text-sm">${l}</div>`,width:600});return}let c="";s.ordenes_a_adelantar&&s.ordenes_a_adelantar.length>0&&(c=`
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
                `,s.ordenes_a_adelantar.forEach(l=>{c+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${l.planilla_codigo}</td>
                            <td class="px-2 py-1">${l.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${l.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${l.posicion_nueva}</td>
                        </tr>
                    `}),c+=`
                                </tbody>
                            </table>
                        </div>
                    </div>
                `);let p="";s.colaterales&&s.colaterales.length>0&&(p=`
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
                `,s.colaterales.forEach(l=>{p+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${l.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${l.obra}</td>
                            <td class="px-2 py-1">${l.fecha_entrega}</td>
                        </tr>
                    `}),p+=`
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-orange-600 mt-1">Estas planillas bajarán una posición en la cola de fabricación.</p>
                    </div>
                `);const d=s.fecha_entrega||"---";Swal.fire({icon:"question",title:"🚀 Adelantar fabricación",html:`
                    <div class="text-left">
                        <p class="mb-3">Para cumplir con la fecha de entrega <strong>${d}</strong>, se propone el siguiente cambio:</p>
                        ${c}
                        ${p}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el adelanto?</p>
                    </div>
                `,width:600,showCancelButton:!0,confirmButtonText:"✅ Ejecutar adelanto",cancelButtonText:"Cancelar",confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280"}).then(l=>{l.isConfirmed&&le(s.ordenes_a_adelantar)})}).catch(s=>{clearTimeout(n),console.error("Error en simulación:",s);const c=s.name==="AbortError";Swal.fire({icon:"error",title:c?"Tiempo agotado":"Error",text:c?"El cálculo está tardando demasiado. La operación fue cancelada.":"No se pudo simular el adelanto. "+s.message})})}function le(e){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay órdenes para adelantar"});return}Swal.fire({title:"Ejecutando adelanto...",html:"Actualizando posiciones en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const a=e.map(n=>({planilla_id:n.planilla_id,maquina_id:n.maquina_id,posicion_nueva:n.posicion_nueva}));console.log("Enviando órdenes al servidor:",JSON.stringify({ordenes:a},null,2)),fetch("/planificacion/ejecutar-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({ordenes:a})}).then(n=>{if(!n.ok)throw new Error("Error al ejecutar el adelanto");return n.json()}).then(n=>{if(console.log("Respuesta del servidor:",n),n.success){const o=n.resultados||[],s=o.filter(d=>d.success),c=o.filter(d=>!d.success);let p=n.mensaje||"Las posiciones han sido actualizadas.";s.length>0&&(p+=`<br><br><strong>${s.length} orden(es) movidas correctamente.</strong>`),c.length>0&&(p+=`<br><span class="text-amber-600">${c.length} orden(es) no pudieron moverse:</span>`,p+="<ul class='text-left text-sm mt-2'>",c.forEach(d=>{p+=`<li>• Planilla ${d.planilla_id}: ${d.mensaje}</li>`}),p+="</ul>"),Swal.fire({icon:s.length>0?"success":"warning",title:s.length>0?"¡Adelanto ejecutado!":"Problemas al adelantar",html:p,confirmButtonColor:"#10b981"}).then(()=>{w&&(J(),w.refetchEvents(),w.refetchResources())})}else Swal.fire({icon:"error",title:"Error",text:n.mensaje||"No se pudo ejecutar el adelanto."})}).catch(n=>{console.error("Error al ejecutar adelanto:",n),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el adelanto. "+n.message})})}function Qe(e,a={}){const{selector:t=null,once:n=!1}=a;let o=!1;const s=()=>{t&&!document.querySelector(t)||n&&o||(o=!0,e())};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",s):s(),document.addEventListener("livewire:navigated",s)}function et(e){document.addEventListener("livewire:navigating",e)}function tt(e){let t=new Date(e).toLocaleDateString("es-ES",{month:"long",year:"numeric"});return`(${t.charAt(0).toUpperCase()+t.slice(1)})`}function at(e){const a=new Date(e),t=a.getDay(),n=t===0?-6:1-t,o=new Date(a);o.setDate(a.getDate()+n);const s=new Date(o);s.setDate(o.getDate()+6);const c=new Intl.DateTimeFormat("es-ES",{day:"2-digit",month:"short"}),p=new Intl.DateTimeFormat("es-ES",{year:"numeric"});return`(${c.format(o)} – ${c.format(s)} ${p.format(s)})`}function nt(e){var s,c;const a=document.querySelector("#resumen-semanal-fecha"),t=document.querySelector("#resumen-mensual-fecha");a&&(a.textContent=at(e)),t&&(t.textContent=tt(e));const n=(c=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:c.totales;if(!n)return;const o=`${n}?fecha=${encodeURIComponent(e)}`;fetch(o).then(p=>p.json()).then(p=>{const d=p.semana||{},l=p.mes||{};document.querySelector("#resumen-semanal-peso").textContent=`📦 ${Number(d.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-semanal-longitud").textContent=`📏 ${Number(d.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-semanal-diametro").textContent=d.diametro!=null?`⌀ ${Number(d.diametro).toFixed(2)} mm`:"",document.querySelector("#resumen-mensual-peso").textContent=`📦 ${Number(l.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-mensual-longitud").textContent=`📏 ${Number(l.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-mensual-diametro").textContent=l.diametro!=null?`⌀ ${Number(l.diametro).toFixed(2)} mm`:""}).catch(p=>console.error("❌ Totales:",p))}let z;function ot(){var u,i;if(window.calendar)try{window.calendar.destroy()}catch(m){console.warn("Error al destruir calendario anterior:",m)}const e=Je();z=e,window.calendar=e,e.refetchResources(),e.refetchEvents(),(u=document.getElementById("ver-con-salidas"))==null||u.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()}),(i=document.getElementById("ver-todas"))==null||i.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()});const t=(localStorage.getItem("fechaCalendario")||new Date().toISOString()).split("T")[0];nt(t);const n=localStorage.getItem("soloSalidas")==="true",o=localStorage.getItem("soloPlanillas")==="true",s=document.getElementById("solo-salidas"),c=document.getElementById("solo-planillas");s&&(s.checked=n),c&&(c.checked=o);const p=document.getElementById("filtro-obra"),d=document.getElementById("filtro-nombre-obra"),l=document.getElementById("btn-reset-filtros"),f=document.getElementById("btn-limpiar-filtros");l==null||l.addEventListener("click",()=>{p&&(p.value=""),d&&(d.value=""),s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),c&&(c.checked=!1,localStorage.setItem("soloPlanillas","false")),r(),z.refetchEvents()});const h=((m,y=150)=>{let S;return(...x)=>{clearTimeout(S),S=setTimeout(()=>m(...x),y)}})(()=>{z.refetchEvents()},120);p==null||p.addEventListener("input",h),d==null||d.addEventListener("input",h);function r(){const m=s==null?void 0:s.closest(".checkbox-container"),y=c==null?void 0:c.closest(".checkbox-container");m==null||m.classList.remove("active-salidas"),y==null||y.classList.remove("active-planillas"),s!=null&&s.checked&&(m==null||m.classList.add("active-salidas")),c!=null&&c.checked&&(y==null||y.classList.add("active-planillas"))}s==null||s.addEventListener("change",m=>{m.target.checked&&c&&(c.checked=!1,localStorage.setItem("soloPlanillas","false")),localStorage.setItem("soloSalidas",m.target.checked.toString()),r(),z.refetchEvents()}),c==null||c.addEventListener("change",m=>{m.target.checked&&s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),localStorage.setItem("soloPlanillas",m.target.checked.toString()),r(),z.refetchEvents()}),r(),f==null||f.addEventListener("click",()=>{p&&(p.value=""),d&&(d.value=""),z.refetchEvents()})}let j=null,N=null,Q="days",O=-1,H=[];function st(){N&&N();const e=window.calendar;if(!e)return;j=e.getDate(),Q="days",O=-1,dt();function a(t){var c;const n=t.target.tagName.toLowerCase();if(n==="input"||n==="textarea"||t.target.isContentEditable||document.querySelector(".swal2-container"))return;const o=window.calendar;if(!o)return;let s=!1;switch(t.key){case"ArrowLeft":o.prev(),s=!0;break;case"ArrowRight":o.next(),s=!0;break;case"t":case"T":o.today(),s=!0;break;case"Escape":window.isFullScreen&&((c=window.toggleFullScreen)==null||c.call(window),s=!0);break}s&&(t.preventDefault(),t.stopPropagation())}document.addEventListener("keydown",a,!0),e.on("eventsSet",()=>{Q==="events"&&(rt(),it())}),N=()=>{document.removeEventListener("keydown",a,!0),ge(),fe()}}function rt(){const e=window.calendar;if(!e){H=[];return}H=e.getEvents().filter(a=>{var n;const t=(n=a.extendedProps)==null?void 0:n.tipo;return t!=="resumen-dia"&&t!=="festivo"}).sort((a,t)=>{const n=a.start||new Date(0),o=t.start||new Date(0);return n<o?-1:n>o?1:(a.title||"").localeCompare(t.title||"")})}function it(){var t;if(fe(),O<0||O>=H.length)return;const e=H[O];if(!e)return;const a=document.querySelector(`[data-event-id="${e.id}"]`)||document.querySelector(`.fc-event[data-event="${e.id}"]`);if(a)a.classList.add("keyboard-focused-event"),a.scrollIntoView({behavior:"smooth",block:"nearest"});else{const n=document.querySelectorAll(".fc-event");for(const o of n)if(o.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){o.classList.add("keyboard-focused-event"),o.scrollIntoView({behavior:"smooth",block:"nearest"});break}}e.start&&(j=new Date(e.start)),ye()}function fe(){document.querySelectorAll(".keyboard-focused-event").forEach(e=>{e.classList.remove("keyboard-focused-event")})}function lt(e){const a=e.getFullYear(),t=String(e.getMonth()+1).padStart(2,"0"),n=String(e.getDate()).padStart(2,"0");return`${a}-${t}-${n}`}function dt(){if(ge(),!j)return;const e=lt(j),a=window.calendar;if(!a)return;const t=a.view.type;let n=null;t==="dayGridMonth"?n=document.querySelector(`.fc-daygrid-day[data-date="${e}"]`):t==="resourceTimelineWeek"?(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(s=>{s.dataset.date&&s.dataset.date.startsWith(e)&&(n=s)}),n||(n=document.querySelector(`.fc-timeline-slot-lane[data-date^="${e}"]`))):t==="resourceTimeGridDay"&&(n=document.querySelector(".fc-col-header-cell")),n&&(n.classList.add("keyboard-focused-day"),n.scrollIntoView({behavior:"smooth",block:"nearest",inline:"nearest"})),ye()}function ge(){document.querySelectorAll(".keyboard-focused-day").forEach(e=>{e.classList.remove("keyboard-focused-day")})}function ye(){let e=document.getElementById("keyboard-nav-indicator");if(e||(e=document.createElement("div"),e.id="keyboard-nav-indicator",e.className="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm",document.body.appendChild(e)),Q==="events"){const a=H[O],t=(a==null?void 0:a.title)||"Sin evento",n=`${O+1}/${H.length}`;e.innerHTML=`
            <div class="flex items-center gap-2">
                <span class="bg-green-500 text-white text-xs px-2 py-0.5 rounded">EVENTOS</span>
                <span class="font-medium truncate max-w-[200px]">${t}</span>
                <span class="text-gray-400">${n}</span>
            </div>
            <div class="text-xs text-gray-400 mt-1 flex gap-3">
                <span>↑↓ Navegar</span>
                <span>Enter Abrir</span>
                <span>E Menú</span>
                <span>I Info</span>
                <span>Tab/Esc Días</span>
            </div>
        `}else{const a=j?j.toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short",year:"numeric"}):"";e.innerHTML=`
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
        `}clearTimeout(e._hideTimeout),e.style.display="block",e._hideTimeout=setTimeout(()=>{e.style.display="none"},4e3)}function ct(){if(document.getElementById("keyboard-nav-styles"))return;const e=document.createElement("style");e.id="keyboard-nav-styles",e.textContent=`
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
    `,document.head.appendChild(e)}Qe(()=>{ot(),ct(),setTimeout(()=>{st()},500)},{selector:'#calendario[data-calendar-type="salidas"]'});et(()=>{if(N&&(N(),N=null),window.calendar)try{window.calendar.destroy(),window.calendar=null}catch(a){console.warn("Error al limpiar calendario de salidas:",a)}const e=document.getElementById("keyboard-nav-indicator");e&&e.remove()});
