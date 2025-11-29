async function le(e,a){var t,o,n,r;try{const l=(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.planificacion;if(!l)return[];const u=new URLSearchParams({tipo:"events",viewType:e||"",start:a.startStr||"",end:a.endStr||"",t:Date.now()}),d=await fetch(`${l}?${u.toString()}`);if(!d.ok)return console.error("Error eventos",d.status),[];const s=await d.json();let i=Array.isArray(s)?s:Array.isArray(s==null?void 0:s.events)?s.events:[];const c=((n=document.getElementById("solo-salidas"))==null?void 0:n.checked)||!1,m=((r=document.getElementById("solo-planillas"))==null?void 0:r.checked)||!1,y=i.filter(f=>{var p;return((p=f.extendedProps)==null?void 0:p.tipo)==="resumen-dia"}),g=i.filter(f=>{var p;return((p=f.extendedProps)==null?void 0:p.tipo)!=="resumen-dia"});let b=g;return c&&!m?b=g.filter(f=>{var v;return((v=f.extendedProps)==null?void 0:v.tipo)==="salida"}):m&&!c&&(b=g.filter(f=>{var v;const p=(v=f.extendedProps)==null?void 0:v.tipo;return p==="planilla"||p==="festivo"})),[...b,...y]}catch(l){return console.error("fetch eventos falló:",l),[]}}async function de(e,a){var l,u;const t=(u=(l=window.AppSalidas)==null?void 0:l.routes)==null?void 0:u.planificacion;if(!t)return[];const o=new URLSearchParams({tipo:"resources",viewType:e,start:a.startStr||"",end:a.endStr||""}),n=await fetch(`${t}?${o.toString()}`,{method:"GET"});if(!n.ok)throw new Error("Error cargando recursos");const r=await n.json();return Array.isArray(r)?r:Array.isArray(r==null?void 0:r.resources)?r.resources:[]}function G(e,a){const t=e.event.extendedProps||{};if(t.tipo!=="festivo"){if(t.tipo==="planilla"){const o=`
      ✅ Fabricados: ${O(t.fabricadosKg)} kg<br>
      🔄 Fabricando: ${O(t.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${O(t.pendientesKg)} kg
    `;tippy(e.el,{content:o,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}t.tipo==="salida"&&t.comentario&&t.comentario.trim()&&tippy(e.el,{content:t.comentario,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}}function O(e){return e!=null?Number(e).toLocaleString():0}let N=null;function k(){N&&(N.remove(),N=null,document.removeEventListener("click",k),document.removeEventListener("contextmenu",k,!0),document.removeEventListener("scroll",k,!0),window.removeEventListener("resize",k),window.removeEventListener("keydown",U))}function U(e){e.key==="Escape"&&k()}function ce(e,a,t){k();const o=document.createElement("div");o.className="fc-contextmenu",Object.assign(o.style,{position:"fixed",top:a+"px",left:e+"px",zIndex:99999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",fontFamily:"system-ui, -apple-system, Segoe UI, Roboto, sans-serif"}),o.innerHTML=t,document.body.appendChild(o),N=o;const n=o.getBoundingClientRect(),r=Math.max(0,n.right-window.innerWidth+8),l=Math.max(0,n.bottom-window.innerHeight+8);return(r||l)&&(o.style.left=Math.max(8,e-r)+"px",o.style.top=Math.max(8,a-l)+"px"),setTimeout(()=>{document.addEventListener("click",k),document.addEventListener("contextmenu",k,!0),document.addEventListener("scroll",k,!0),window.addEventListener("resize",k),window.addEventListener("keydown",U)},0),o}function ue(e,a,{headerHtml:t="",items:o=[]}={}){const n=`
    <div class="ctx-menu-container">
      ${t?`<div class="ctx-menu-header">${t}</div>`:""}
      ${o.map((l,u)=>`
        <button type="button"
          class="ctx-menu-item${l.danger?" ctx-menu-danger":""}"
          data-idx="${u}">
          ${l.icon?`<span class="ctx-menu-icon">${l.icon}</span>`:""}
          <span class="ctx-menu-label">${l.label}</span>
        </button>`).join("")}
    </div>
  `,r=ce(e,a,n);return r.querySelectorAll(".ctx-menu-item").forEach(l=>{l.addEventListener("click",async u=>{var i;u.preventDefault(),u.stopPropagation();const d=Number(l.dataset.idx),s=(i=o[d])==null?void 0:i.onClick;k();try{await(s==null?void 0:s())}catch(c){console.error(c)}})}),r}function me(e){if(!e||typeof e!="string")return"";const a=e.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);if(a){const t=a[1],o=a[2].padStart(2,"0"),n=a[3].padStart(2,"0");return`${t}-${o}-${n}`}return e}(function(){if(document.getElementById("swal-anims"))return;const a=document.createElement("style");a.id="swal-anims",a.textContent=`
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
  `,document.head.appendChild(a)})();function pe(e){const a=e.extendedProps||{};if(Array.isArray(a.planillas_ids)&&a.planillas_ids.length)return a.planillas_ids;const t=(e.id||"").match(/planilla-(\d+)/);return t?[Number(t[1])]:[]}async function Y(e,a){var t,o;try{k()}catch{}if(!e)return Swal.fire("⚠️","ID de salida inválido.","warning");try{const n=await fetch(`${(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.informacionPaquetesSalida}?salida_id=${e}`,{headers:{Accept:"application/json"}});if(!n.ok)throw new Error("Error al cargar información de la salida");const{salida:r,paquetesAsignados:l,paquetesDisponibles:u,paquetesTodos:d,filtros:s}=await n.json();fe(r,l,u,d||[],s||{obras:[],planillas:[],obrasRelacionadas:[]},a)}catch(n){console.error(n),Swal.fire("❌","Error al cargar la información de la salida","error")}}function fe(e,a,t,o,n,r){window._gestionPaquetesData={salida:e,paquetesAsignados:a,paquetesDisponibles:t,paquetesTodos:o,filtros:n,mostrarTodos:!1};const l=ge(e,a,t,n);Swal.fire({title:`📦 Gestionar Paquetes - Salida ${e.codigo_salida||e.id}`,html:l,width:Math.min(window.innerWidth*.95,1200),showConfirmButton:!0,showCancelButton:!0,confirmButtonText:"💾 Guardar Cambios",cancelButtonText:"Cancelar",focusConfirm:!1,customClass:{popup:"w-full max-w-screen-xl"},didOpen:()=>{Z(),be(),xe(),setTimeout(()=>{ve()},100)},willClose:()=>{w.cleanup&&w.cleanup();const u=document.getElementById("modal-keyboard-indicator");u&&u.remove()},preConfirm:()=>Se()}).then(async u=>{u.isConfirmed&&u.value&&await Ee(e.id,u.value,r)})}function ge(e,a,t,o){var s,i;const n=a.reduce((c,m)=>c+(parseFloat(m.peso)||0),0);let r="";e.salida_clientes&&e.salida_clientes.length>0&&(r='<div class="col-span-2"><strong>Obras/Clientes:</strong><br>',e.salida_clientes.forEach(c=>{var b,f,p,v,S;const m=((b=c.obra)==null?void 0:b.obra)||"Obra desconocida",y=(f=c.obra)!=null&&f.cod_obra?`(${c.obra.cod_obra})`:"",g=((p=c.cliente)==null?void 0:p.empresa)||((S=(v=c.obra)==null?void 0:v.cliente)==null?void 0:S.empresa)||"";r+=`<span class="text-xs">• ${m} ${y}`,g&&(r+=` - ${g}`),r+="</span><br>"}),r+="</div>");const l=`
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><strong>Código:</strong> ${e.codigo_salida||"N/A"}</div>
                <div><strong>Código SAGE:</strong> ${e.codigo_sage||"Sin asignar"}</div>
                <div><strong>Fecha salida:</strong> ${new Date(e.fecha_salida).toLocaleString("es-ES")}</div>
                <div><strong>Estado:</strong> ${e.estado||"pendiente"}</div>
                <div><strong>Empresa transporte:</strong> ${((s=e.empresa_transporte)==null?void 0:s.nombre)||"Sin asignar"}</div>
                <div><strong>Camión:</strong> ${((i=e.camion)==null?void 0:i.modelo)||"Sin asignar"}</div>
                ${r}
            </div>
        </div>
    `,u=((o==null?void 0:o.obras)||[]).map(c=>`<option value="${c.id}">${c.cod_obra||""} - ${c.obra||"Sin nombre"}</option>`).join(""),d=((o==null?void 0:o.planillas)||[]).map(c=>`<option value="${c.id}" data-obra-id="${c.obra_id||""}">${c.codigo||"Sin código"}</option>`).join("");return`
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
                        <span class="text-xs bg-green-200 px-2 py-1 rounded" id="peso-asignados">${n.toFixed(2)} kg</span>
                    </div>
                    <div
                        class="paquetes-zona-salida drop-zone overflow-y-auto"
                        data-zona="asignados"
                        style="min-height: 350px; max-height: 450px; border: 2px dashed #10b981; border-radius: 8px; padding: 8px;"
                    >
                        ${R(a)}
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
                                    ${u}
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
                        <button type="button" id="btn-limpiar-filtros-modal"
                                class="w-full text-xs px-2 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                            🔄 Limpiar Filtros
                        </button>
                    </div>

                    <div
                        class="paquetes-zona-salida drop-zone overflow-y-auto"
                        data-zona="disponibles"
                        style="min-height: 250px; max-height: 350px; border: 2px dashed #6b7280; border-radius: 8px; padding: 8px;"
                    >
                        ${R(t)}
                    </div>
                </div>
            </div>
        </div>
    `}function R(e){return!e||e.length===0?'<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>':e.map(a=>{var t,o,n,r,l,u,d,s,i,c,m,y,g,b,f,p;return`
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${a.id}"
            data-peso="${a.peso||0}"
            data-obra-id="${((o=(t=a.planilla)==null?void 0:t.obra)==null?void 0:o.id)||""}"
            data-obra="${((r=(n=a.planilla)==null?void 0:n.obra)==null?void 0:r.obra)||""}"
            data-planilla-id="${((l=a.planilla)==null?void 0:l.id)||""}"
            data-planilla="${((u=a.planilla)==null?void 0:u.codigo)||""}"
            data-cliente="${((s=(d=a.planilla)==null?void 0:d.cliente)==null?void 0:s.empresa)||""}"
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
                <div>📄 ${((i=a.planilla)==null?void 0:i.codigo)||a.planilla_id}</div>
                <div>🏗️ ${((m=(c=a.planilla)==null?void 0:c.obra)==null?void 0:m.cod_obra)||""} - ${((g=(y=a.planilla)==null?void 0:y.obra)==null?void 0:g.obra)||"N/A"}</div>
                <div>👤 ${((f=(b=a.planilla)==null?void 0:b.cliente)==null?void 0:f.empresa)||"Sin cliente"}</div>
                ${(p=a.nave)!=null&&p.obra?`<div class="text-blue-600 font-medium">📍 ${a.nave.obra}</div>`:""}
            </div>
        </div>
    `}).join("")}async function ye(e){var a;try{const t=document.querySelector(`[data-paquete-id="${e}"]`);let o=null;if(t&&t.dataset.paqueteJson)try{o=JSON.parse(t.dataset.paqueteJson.replace(/&#39;/g,"'"))}catch(d){console.warn("No se pudo parsear JSON del paquete",d)}if(!o){const d=await fetch(`/api/paquetes/${e}/elementos`);d.ok&&(o=await d.json())}if(!o){alert("No se pudo obtener información del paquete");return}const n=[];if(o.etiquetas&&o.etiquetas.length>0&&o.etiquetas.forEach(d=>{d.elementos&&d.elementos.length>0&&d.elementos.forEach(s=>{n.push({id:s.id,dimensiones:s.dimensiones,peso:s.peso,longitud:s.longitud,diametro:s.diametro})})}),n.length===0){alert("Este paquete no tiene elementos para mostrar");return}const r=n.map((d,s)=>`
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">Elemento #${d.id}</span>
                    <span class="text-xs text-gray-500">${s+1} de ${n.length}</span>
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
        `).join(""),l=document.getElementById("modal-elementos-paquete-overlay");l&&l.remove();const u=`
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
                        ${r}
                    </div>
                    <div class="p-4 border-t bg-gray-50 rounded-b-lg">
                        <button onclick="document.getElementById('modal-elementos-paquete-overlay').remove()"
                                class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        `;document.body.insertAdjacentHTML("beforeend",u),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&n.forEach(d=>{d.dimensiones&&window.dibujarFiguraElemento(`elemento-dibujo-${d.id}`,d.dimensiones,null)})},100)}catch(t){console.error("Error al ver elementos del paquete:",t),alert("Error al cargar los elementos del paquete")}}window.verElementosPaqueteSalida=ye;function be(e){const a=document.getElementById("filtro-obra-modal"),t=document.getElementById("filtro-planilla-modal"),o=document.getElementById("btn-limpiar-filtros-modal");a&&a.addEventListener("change",()=>{H(),j()}),t&&t.addEventListener("change",()=>{j()}),o&&o.addEventListener("click",()=>{a&&(a.value=""),t&&(t.value=""),H(),j()})}function H(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData;if(!a||!t)return;const o=(e==null?void 0:e.value)||"",n=o?t.paquetesTodos:t.paquetesDisponibles,r=new Map;n.forEach(d=>{var s,i,c;if((s=d.planilla)!=null&&s.id){if(o&&String((i=d.planilla.obra)==null?void 0:i.id)!==o)return;r.has(d.planilla.id)||r.set(d.planilla.id,{id:d.planilla.id,codigo:d.planilla.codigo||"Sin código",obra_id:(c=d.planilla.obra)==null?void 0:c.id})}});const l=Array.from(r.values()).sort((d,s)=>(d.codigo||"").localeCompare(s.codigo||"")),u=a.value;a.innerHTML='<option value="">-- Todas las planillas --</option>',l.forEach(d=>{const s=document.createElement("option");s.value=d.id,s.textContent=d.codigo,a.appendChild(s)}),u&&r.has(parseInt(u))?a.value=u:a.value=""}function j(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData,o=(e==null?void 0:e.value)||"",n=(a==null?void 0:a.value)||"",r=document.querySelector('[data-zona="disponibles"]');if(!r||!t)return;const l=document.querySelector('[data-zona="asignados"]'),u=new Set;l&&l.querySelectorAll(".paquete-item-salida").forEach(i=>{u.add(parseInt(i.dataset.paqueteId))});let s=(o?t.paquetesTodos:t.paquetesDisponibles).filter(i=>{var c,m,y;return!(u.has(i.id)||o&&String((m=(c=i.planilla)==null?void 0:c.obra)==null?void 0:m.id)!==o||n&&String((y=i.planilla)==null?void 0:y.id)!==n)});r.innerHTML=R(s),Z(),s.length===0&&(r.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">No hay paquetes que coincidan con el filtro</div>')}let w={zonaActiva:"asignados",indiceFocused:-1,cleanup:null};function ve(){w.cleanup&&w.cleanup(),w.zonaActiva="asignados",w.indiceFocused=0,T();function e(a){var c;if(!document.querySelector(".swal2-container"))return;const t=a.target.tagName.toLowerCase(),o=t==="select";if((t==="input"||t==="textarea")&&a.key!=="Escape")return;const r=document.querySelector('[data-zona="asignados"]'),l=document.querySelector('[data-zona="disponibles"]');if(!r||!l)return;const u=w.zonaActiva==="asignados"?r:l,d=Array.from(u.querySelectorAll('.paquete-item-salida:not([style*="display: none"])')),s=d.length;let i=!1;if(!o)switch(a.key){case"ArrowDown":s>0&&(w.indiceFocused=(w.indiceFocused+1)%s,T(),i=!0);break;case"ArrowUp":s>0&&(w.indiceFocused=w.indiceFocused<=0?s-1:w.indiceFocused-1,T(),i=!0);break;case"ArrowLeft":case"ArrowRight":w.zonaActiva=w.zonaActiva==="asignados"?"disponibles":"asignados",w.indiceFocused=0,T(),i=!0;break;case"Tab":a.preventDefault(),w.zonaActiva=w.zonaActiva==="asignados"?"disponibles":"asignados",w.indiceFocused=0,T(),i=!0;break;case"Enter":{if(s>0&&w.indiceFocused>=0){const m=d[w.indiceFocused];if(m){he(m);const y=Array.from(u.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));w.indiceFocused>=y.length&&(w.indiceFocused=Math.max(0,y.length-1)),T(),i=!0}}break}case"Home":w.indiceFocused=0,T(),i=!0;break;case"End":w.indiceFocused=Math.max(0,s-1),T(),i=!0;break}if(i){a.preventDefault(),a.stopPropagation();return}switch(a.key){case"o":case"O":{const m=document.getElementById("filtro-obra-modal");m&&(m.focus(),i=!0);break}case"p":case"P":{const m=document.getElementById("filtro-planilla-modal");m&&(m.focus(),i=!0);break}case"l":case"L":{const m=document.getElementById("btn-limpiar-filtros-modal");m&&(m.click(),(c=document.activeElement)==null||c.blur(),T(),i=!0);break}case"/":case"f":case"F":{const m=document.getElementById("filtro-obra-modal");m&&(m.focus(),i=!0);break}case"Escape":o&&(document.activeElement.blur(),T(),i=!0);break;case"s":case"S":{if(a.ctrlKey||a.metaKey){const m=document.querySelector(".swal2-confirm");m&&(m.click(),i=!0)}break}}i&&(a.preventDefault(),a.stopPropagation())}document.addEventListener("keydown",e,!0),w.cleanup=()=>{document.removeEventListener("keydown",e,!0),X()}}function T(){X();const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;w.zonaActiva==="asignados"?(e.classList.add("zona-activa-keyboard"),a.classList.remove("zona-activa-keyboard")):(a.classList.add("zona-activa-keyboard"),e.classList.remove("zona-activa-keyboard"));const t=w.zonaActiva==="asignados"?e:a,o=Array.from(t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));if(o.length>0&&w.indiceFocused>=0){const n=Math.min(w.indiceFocused,o.length-1),r=o[n];r&&(r.classList.add("paquete-focused-keyboard"),r.scrollIntoView({behavior:"smooth",block:"nearest"}))}we()}function X(){document.querySelectorAll(".paquete-focused-keyboard").forEach(e=>{e.classList.remove("paquete-focused-keyboard")}),document.querySelectorAll(".zona-activa-keyboard").forEach(e=>{e.classList.remove("zona-activa-keyboard")})}function he(e){const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!a||!t)return;const o=e.closest("[data-zona]"),n=o.dataset.zona==="asignados"?t:a,r=n.querySelector(".placeholder-sin-paquetes");if(r&&r.remove(),n.appendChild(e),o.querySelectorAll(".paquete-item-salida").length===0){const u=document.createElement("div");u.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",u.textContent="Sin paquetes",o.appendChild(u)}J(e),Q()}function we(){let e=document.getElementById("modal-keyboard-indicator");e||(e=document.createElement("div"),e.id="modal-keyboard-indicator",e.className="fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs",document.body.appendChild(e));const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]'),o=(a==null?void 0:a.querySelectorAll(".paquete-item-salida").length)||0,n=(t==null?void 0:t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0,r=w.zonaActiva==="asignados"?`📦 Asignados (${o})`:`📋 Disponibles (${n})`;e.innerHTML=`
        <div class="flex items-center gap-2 mb-2">
            <span class="${w.zonaActiva==="asignados"?"bg-green-500":"bg-gray-500"} text-white text-xs px-2 py-0.5 rounded">${r}</span>
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
    `,clearTimeout(e._checkTimeout),e._checkTimeout=setTimeout(()=>{document.querySelector(".swal2-container")||e.remove()},500)}function xe(){if(document.getElementById("modal-keyboard-styles"))return;const e=document.createElement("style");e.id="modal-keyboard-styles",e.textContent=`
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
    `,document.head.appendChild(e)}function J(e){e.addEventListener("dragstart",a=>{e.style.opacity="0.5",a.dataTransfer.setData("text/plain",e.dataset.paqueteId)}),e.addEventListener("dragend",a=>{e.style.opacity="1"})}function Z(){document.querySelectorAll(".paquete-item-salida").forEach(e=>{J(e)}),document.querySelectorAll(".drop-zone").forEach(e=>{e.addEventListener("dragover",a=>{a.preventDefault();const t=e.dataset.zona;e.style.backgroundColor=t==="asignados"?"#d1fae5":"#e0f2fe"}),e.addEventListener("dragleave",a=>{e.style.backgroundColor=""}),e.addEventListener("drop",a=>{a.preventDefault(),e.style.backgroundColor="";const t=a.dataTransfer.getData("text/plain"),o=document.querySelector(`.paquete-item-salida[data-paquete-id="${t}"]`);if(o){const n=e.querySelector(".placeholder-sin-paquetes");n&&n.remove(),e.appendChild(o),Q()}})})}function Q(){const e=document.querySelector('[data-zona="asignados"]'),a=e==null?void 0:e.querySelectorAll(".paquete-item-salida");let t=0;a==null||a.forEach(n=>{const r=parseFloat(n.dataset.peso)||0;t+=r});const o=document.getElementById("peso-asignados");o&&(o.textContent=`${t.toFixed(2)} kg`)}function Se(){const e=document.querySelector('[data-zona="asignados"]');return{paquetes_ids:Array.from((e==null?void 0:e.querySelectorAll(".paquete-item-salida"))||[]).map(t=>parseInt(t.dataset.paqueteId))}}async function Ee(e,a,t){var o,n,r,l;try{const d=await(await fetch((n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.guardarPaquetesSalida,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(r=window.AppSalidas)==null?void 0:r.csrf},body:JSON.stringify({salida_id:e,paquetes_ids:a.paquetes_ids})})).json();d.success?(await Swal.fire({icon:"success",title:"✅ Cambios Guardados",text:"Los paquetes de la salida se han actualizado correctamente.",timer:2e3}),t&&(t.refetchEvents(),(l=t.refetchResources)==null||l.call(t))):await Swal.fire("⚠️",d.message||"No se pudieron guardar los cambios","warning")}catch(u){console.error(u),Swal.fire("❌","Error al guardar los paquetes","error")}}async function ke(e,a,t){try{k()}catch{}window.Livewire.dispatch("abrirComentario",{salidaId:e}),window._calendarRef=t}function $e(e){return e?typeof e=="string"?e.split(",").map(t=>t.trim()).filter(Boolean):Array.from(e).map(t=>typeof t=="object"&&(t==null?void 0:t.id)!=null?t.id:t).map(String).map(t=>t.trim()).filter(Boolean):[]}async function Te(e){var r,l;const a=(l=(r=window.AppSalidas)==null?void 0:r.routes)==null?void 0:l.informacionPlanillas;if(!a)throw new Error("Ruta 'informacionPlanillas' no configurada");const t=`${a}?ids=${encodeURIComponent(e.join(","))}`,o=await fetch(t,{headers:{Accept:"application/json"}});if(!o.ok){const u=await o.text().catch(()=>"");throw new Error(`GET ${t} -> ${o.status} ${u}`)}const n=await o.json();return Array.isArray(n==null?void 0:n.planillas)?n.planillas:[]}function ee(e){if(!e)return!1;const t=new Date(e+"T00:00:00").getDay();return t===0||t===6}function De(e){return`
    <div class="text-left">
      <div class="text-sm text-gray-600 mb-2">
        Edita la <strong>fecha estimada de entrega</strong> y guarda.
      </div>
      
      <!-- Sumatorio dinámico por fechas -->
      <div id="sumatorio-fechas" class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="text-sm font-medium text-blue-800 mb-2">📊 Resumen por fecha:</div>
        <div id="resumen-contenido" class="text-xs text-blue-700">
          Cambia las fechas para ver el resumen...
        </div>
      </div>
      
      <div class="overflow-auto" style="max-height:45vh;border:1px solid #e5e7eb;border-radius:6px;">
        <table class="min-w-full text-sm">
        <thead class="sticky top-0 bg-white">
  <tr>
    <th class="px-2 py-1 text-left">ID</th>
    <th class="px-2 py-1 text-left">Cod. Obra</th>
    <th class="px-2 py-1 text-left">Obra</th>
    <th class="px-2 py-1 text-left">Sección</th>
    <th class="px-2 py-1 text-left">Descripción</th>
    <th class="px-2 py-1 text-left">Planilla</th>
    <th class="px-2 py-1 text-left">Peso Total</th>
    <th class="px-2 py-1 text-left">Fecha Entrega</th>
  </tr>
</thead>

          <tbody>${e.map((t,o)=>{var c,m;const n=((c=t.obra)==null?void 0:c.codigo)||"",r=((m=t.obra)==null?void 0:m.nombre)||"",l=t.seccion||"",u=t.descripcion||"",d=t.codigo||`Planilla ${t.id}`,s=t.peso_total?parseFloat(t.peso_total).toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})+" kg":"",i=me(t.fecha_estimada_entrega);return`
<tr style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${o*18}ms;">
  <td class="px-2 py-1 text-xs">${t.id}</td>
  <td class="px-2 py-1 text-xs">${n}</td>
  <td class="px-2 py-1 text-xs">${r}</td>
  <td class="px-2 py-1 text-xs">${l}</td>
  <td class="px-2 py-1 text-xs">${u}</td>
  <td class="px-2 py-1 text-xs">${d}</td>
  <td class="px-2 py-1 text-xs text-right font-medium">${s}</td>
  <td class="px-2 py-1">
    <input type="date" class="swal2-input !m-0 !w-auto" data-planilla-id="${t.id}" value="${i}">
  </td>
</tr>`}).join("")}</tbody>
        </table>
      </div>
    </div>`}function Pe(e){const a={};return document.querySelectorAll('input[type="date"][data-planilla-id]').forEach(o=>{const n=parseInt(o.dataset.planillaId),r=o.value,l=e.find(u=>u.id===n);r&&l&&l.peso_total&&(a[r]||(a[r]={peso:0,planillas:0,esFinDeSemana:ee(r)}),a[r].peso+=parseFloat(l.peso_total),a[r].planillas+=1)}),a}function W(e){const a=Pe(e),t=document.getElementById("resumen-contenido");if(!t)return;const o=Object.keys(a).sort();if(o.length===0){t.innerHTML='<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';return}const n=o.map(u=>{const d=a[u],s=new Date(u+"T00:00:00").toLocaleDateString("es-ES",{weekday:"short",day:"2-digit",month:"2-digit",year:"numeric"}),i=d.peso.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2}),c=d.esFinDeSemana?"bg-orange-100 border-orange-300 text-orange-800":"bg-green-100 border-green-300 text-green-800",m=d.esFinDeSemana?"🏖️":"📦";return`
            <div class="inline-block m-1 px-2 py-1 rounded border ${c}">
                <span class="font-medium">${m} ${s}</span>
                <br>
                <span class="text-xs">${i} kg (${d.planillas} planilla${d.planillas!==1?"s":""})</span>
            </div>
        `}).join(""),r=o.reduce((u,d)=>u+a[d].peso,0),l=o.reduce((u,d)=>u+a[d].planillas,0);t.innerHTML=`
        <div class="mb-2">${n}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${r.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})} kg 
            (${l} planilla${l!==1?"s":""})
        </div>
    `}async function Le(e){var o,n,r;const a=(n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.actualizarFechasPlanillas;if(!a)throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");const t=await fetch(a,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(r=window.AppSalidas)==null?void 0:r.csrf,Accept:"application/json"},body:JSON.stringify({planillas:e})});if(!t.ok){const l=await t.text().catch(()=>"");throw new Error(`PUT ${a} -> ${t.status} ${l}`)}return t.json().catch(()=>({}))}async function qe(e,a){var t,o;try{const n=Array.from(new Set($e(e))).map(Number).filter(Boolean);if(!n.length)return Swal.fire("⚠️","No hay planillas en la agrupación.","warning");const r=await Te(n);if(!r.length)return Swal.fire("⚠️","No se han encontrado planillas.","warning");const u=`
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `+De(r),{isConfirmed:d}=await Swal.fire({title:"",html:u,width:Math.min(window.innerWidth*.98,1200),customClass:{popup:"w-full max-w-screen-xl"},showCancelButton:!0,confirmButtonText:"💾 Guardar",cancelButtonText:"Cancelar",focusConfirm:!1,showClass:{popup:"swal-fade-in-zoom"},hideClass:{popup:"swal-fade-out"},didOpen:m=>{Ce(m),F("#swal-drag",!1),setTimeout(()=>{const g=Swal.getHtmlContainer().querySelector('input[type="date"]');g==null||g.focus({preventScroll:!0})},120),Swal.getHtmlContainer().querySelectorAll('input[type="date"]').forEach(g=>{g.addEventListener("change",function(){ee(this.value)?this.classList.add("weekend-date"):this.classList.remove("weekend-date"),W(r)})}),setTimeout(()=>{W(r)},100)}});if(!d)return;const s=Swal.getHtmlContainer().querySelectorAll("input[data-planilla-id]"),i=Array.from(s).map(m=>({id:Number(m.getAttribute("data-planilla-id")),fecha_estimada_entrega:m.value})),c=await Le(i);await Swal.fire(c.success?"✅":"⚠️",c.message||(c.success?"Fechas actualizadas":"No se pudieron actualizar"),c.success?"success":"warning"),c.success&&a&&((t=a.refetchEvents)==null||t.call(a),(o=a.refetchResources)==null||o.call(a))}catch(n){console.error("[CambiarFechasEntrega] error:",n),Swal.fire("❌",(n==null?void 0:n.message)||"Ocurrió un error al actualizar las fechas.","error")}}function V(e,a){e.el.addEventListener("mousedown",k),e.el.addEventListener("contextmenu",t=>{t.preventDefault(),t.stopPropagation();const o=e.event,n=o.extendedProps||{},r=n.tipo||"planilla";let l="";if(r==="salida"){if(n.clientes&&Array.isArray(n.clientes)&&n.clientes.length>0){const s=n.clientes.map(i=>i.nombre).filter(Boolean).join(", ");s&&(l+=`<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${s}</span>`)}n.obras&&Array.isArray(n.obras)&&n.obras.length>0&&(l+='<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ ',l+=n.obras.map(s=>{const i=s.codigo?`(${s.codigo})`:"";return`${s.nombre} ${i}`}).join(", "),l+="</span>")}const u=`
      <div style="padding:10px 12px; font-weight:600;">
        ${o.title??"Evento"}${l}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(o.start).toLocaleString()} — ${new Date(o.end).toLocaleString()}
        </span>
      </div>
    `;let d=[];if(r==="planilla"){const s=pe(o);d=[{label:"Gestionar Salidas y Paquetes",icon:"📦",onClick:()=>window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${s.join(",")}`},{label:"Cambiar fechas de entrega",icon:"🗓️",onClick:()=>qe(s,a)}]}else if(r==="salida"){const s=n.salida_id||o.id;n.empresa_id,n.empresa,d=[{label:"Abrir salida",icon:"🧾",onClick:()=>window.open(`/salidas-ferralla/${s}`,"_blank")},{label:"Gestionar paquetes",icon:"📦",onClick:()=>Y(s,a)},{label:"Agregar comentario",icon:"✍️",onClick:()=>ke(s,n.comentario||"",a)}]}else d=[{label:"Abrir",icon:"🧾",onClick:()=>window.open(n.url||"#","_blank")}];ue(t.clientX,t.clientY,{headerHtml:u,items:d})})}function Ce(e){e.style.transform="none",e.style.position="fixed",e.style.margin="0";const a=e.offsetWidth,t=e.offsetHeight,o=Math.max(0,Math.round((window.innerWidth-a)/2)),n=Math.max(0,Math.round((window.innerHeight-t)/2));e.style.left=`${o}px`,e.style.top=`${n}px`}function F(e=".swal2-title",a=!1){const t=Swal.getPopup(),o=Swal.getHtmlContainer();let n=(e?(o==null?void 0:o.querySelector(e))||(t==null?void 0:t.querySelector(e)):null)||t;if(!t||!n)return;a&&F.__lastPos&&(t.style.left=F.__lastPos.left,t.style.top=F.__lastPos.top,t.style.transform="none"),n.style.cursor="move",n.style.touchAction="none";const r=g=>{var b;return((b=g.closest)==null?void 0:b.call(g,"input, textarea, select, button, a, label, [contenteditable]"))!=null};let l=!1,u=0,d=0,s=0,i=0;const c=g=>{if(!n.contains(g.target)||r(g.target))return;l=!0,document.body.style.userSelect="none";const b=t.getBoundingClientRect();t.style.left=`${b.left}px`,t.style.top=`${b.top}px`,t.style.transform="none",s=parseFloat(t.style.left||b.left),i=parseFloat(t.style.top||b.top),u=g.clientX,d=g.clientY,document.addEventListener("pointermove",m),document.addEventListener("pointerup",y,{once:!0})},m=g=>{if(!l)return;const b=g.clientX-u,f=g.clientY-d;let p=s+b,v=i+f;const S=t.offsetWidth,P=t.offsetHeight,ne=-S+40,se=window.innerWidth-40,re=-P+40,ie=window.innerHeight-40;p=Math.max(ne,Math.min(se,p)),v=Math.max(re,Math.min(ie,v)),t.style.left=`${p}px`,t.style.top=`${v}px`},y=()=>{l=!1,document.body.style.userSelect="",a&&(F.__lastPos={left:t.style.left,top:t.style.top}),document.removeEventListener("pointermove",m)};n.addEventListener("pointerdown",c)}document.addEventListener("DOMContentLoaded",function(){window.addEventListener("comentarioGuardado",e=>{const{salidaId:a,comentario:t}=e.detail,o=window._calendarRef;if(o){const n=o.getEventById(`salida-${a}`);n&&(n.setExtendedProp("comentario",t),n._def&&n._def.extendedProps&&(n._def.extendedProps.comentario=t)),typeof Swal<"u"&&Swal.fire({icon:"success",title:"Comentario guardado",text:"El comentario se ha guardado correctamente",timer:2e3,showConfirmButton:!1,toast:!0,position:"top-end"})}})});function K(e){var l,u;if(!e)return;const a=new Date(e),t={year:"numeric",month:"long"};let o=a.toLocaleDateString("es-ES",t);o=o.charAt(0).toUpperCase()+o.slice(1);const n=document.querySelector("#resumen-mensual-fecha");n&&(n.textContent=`(${o})`);const r=(u=(l=window.AppSalidas)==null?void 0:l.routes)==null?void 0:u.totales;r&&fetch(`${r}?fecha=${encodeURIComponent(e)}`).then(d=>d.json()).then(d=>{const s=d.semana||{};q("#resumen-semanal-peso",`📦 ${_(s.peso)} kg`),q("#resumen-semanal-longitud",`📏 ${_(s.longitud)} m`),q("#resumen-semanal-diametro",s.diametro!=null&&!isNaN(s.diametro)?`⌀ ${Number(s.diametro).toFixed(2)} mm`:"");const i=d.mes||{};q("#resumen-mensual-peso",`📦 ${_(i.peso)} kg`),q("#resumen-mensual-longitud",`📏 ${_(i.longitud)} m`),q("#resumen-mensual-diametro",i.diametro!=null&&!isNaN(i.diametro)?`⌀ ${Number(i.diametro).toFixed(2)} mm`:"")}).catch(d=>console.error("❌ Error al actualizar los totales:",d))}function _(e){return e!=null?Number(e).toLocaleString():"0"}function q(e,a){const t=document.querySelector(e);t&&(t.textContent=a)}let h=null;function Ae(e,a){const t=()=>e&&e.offsetParent!==null&&e.clientWidth>0&&e.clientHeight>=0;if(t())return a();if("IntersectionObserver"in window){const n=new IntersectionObserver(r=>{r.some(u=>u.isIntersecting)&&(n.disconnect(),a())},{root:null,threshold:.01});n.observe(e);return}if("ResizeObserver"in window){const n=new ResizeObserver(()=>{t()&&(n.disconnect(),a())});n.observe(e);return}const o=setInterval(()=>{t()&&(clearInterval(o),a())},100)}function L(){h&&(requestAnimationFrame(()=>{try{h.updateSize()}catch{}}),setTimeout(()=>{try{h.updateSize()}catch{}},150))}function Ie(){if(!window.FullCalendar)return console.error("FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."),null;h&&h.destroy();const e=["resourceTimeGridDay","resourceTimelineWeek","dayGridMonth"];let a=localStorage.getItem("ultimaVistaCalendario");e.includes(a)||(a="resourceTimeGridDay");const t=localStorage.getItem("fechaCalendario");let o=null;const n=document.getElementById("calendario");if(!n)return console.error("#calendario no encontrado"),null;function r(s){return h?h.getEvents().some(i=>{var y,g;const c=(i.startStr||((y=i.start)==null?void 0:y.toISOString())||"").split("T")[0];return(((g=i.extendedProps)==null?void 0:g.tipo)==="festivo"||typeof i.id=="string"&&i.id.startsWith("festivo-"))&&c===s}):!1}Ae(n,()=>{h=new FullCalendar.Calendar(n,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",navLinks:!0,initialView:a,initialDate:t?new Date(t):void 0,dayMaxEventRows:!1,dayMaxEvents:!1,slotMinTime:"05:00:00",slotMaxTime:"20:00:00",buttonText:{today:"Hoy",resourceTimeGridDay:"Día",resourceTimelineWeek:"Semana",dayGridMonth:"Mes"},progressiveEventRendering:!0,expandRows:!0,height:"auto",events:(s,i,c)=>{var y;const m=s.view&&s.view.type||((y=h==null?void 0:h.view)==null?void 0:y.type)||"resourceTimeGridDay";le(m,s).then(i).catch(c)},resources:(s,i,c)=>{var y;const m=s.view&&s.view.type||((y=h==null?void 0:h.view)==null?void 0:y.type)||"resourceTimeGridDay";de(m,s).then(i).catch(c)},headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimeGridDay,resourceTimelineWeek,dayGridMonth"},eventOrderStrict:!0,eventOrder:(s,i)=>{var b,f;const c=((b=s.extendedProps)==null?void 0:b.tipo)==="resumen-dia",m=((f=i.extendedProps)==null?void 0:f.tipo)==="resumen-dia";if(c&&!m)return-1;if(!c&&m)return 1;const y=parseInt(String(s.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0,g=parseInt(String(i.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0;return y-g},datesSet:s=>{try{const i=Fe(s);localStorage.setItem("fechaCalendario",i),localStorage.setItem("ultimaVistaCalendario",s.view.type),u(),setTimeout(()=>K(i),0),clearTimeout(o),o=setTimeout(()=>{h.refetchResources(),h.refetchEvents(),L()},0)}catch(i){console.error("Error en datesSet:",i)}},loading:s=>{!s&&h&&h.view.type==="resourceTimeGridDay"&&setTimeout(()=>d(),150)},viewDidMount:s=>{u(),s.view.type==="resourceTimeGridDay"&&setTimeout(()=>d(),100),s.view.type==="dayGridMonth"&&setTimeout(()=>{document.querySelectorAll(".fc-daygrid-event-harness").forEach(i=>{i.querySelector(".evento-resumen-diario")||(i.style.setProperty("width","100%","important"),i.style.setProperty("max-width","100%","important"),i.style.setProperty("position","static","important"),i.style.setProperty("left","unset","important"),i.style.setProperty("right","unset","important"),i.style.setProperty("top","unset","important"),i.style.setProperty("inset","unset","important"),i.style.setProperty("margin","0 0 2px 0","important"))}),document.querySelectorAll(".fc-daygrid-event:not(.evento-resumen-diario)").forEach(i=>{i.style.setProperty("width","100%","important"),i.style.setProperty("max-width","100%","important"),i.style.setProperty("margin","0","important"),i.style.setProperty("position","static","important"),i.style.setProperty("left","unset","important"),i.style.setProperty("right","unset","important"),i.style.setProperty("inset","unset","important")})},50)},eventContent:s=>{var g;const i=s.event.backgroundColor||"#9CA3AF",c=s.event.extendedProps||{},m=(g=h==null?void 0:h.view)==null?void 0:g.type;if(c.tipo==="resumen-dia"){const b=Number(c.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),f=Number(c.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),p=c.diametroMedio?Number(c.diametroMedio).toFixed(1):null;if(m==="resourceTimelineWeek")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${b} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${f} m</div>
                                ${p?`<div class="text-yellow-800">⌀ ${p} mm</div>`:""}
                            </div>
                        `};if(m==="dayGridMonth")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${b} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${f} m</div>
                                ${p?`<div class="text-yellow-800">⌀ ${p} mm</div>`:""}
                            </div>
                        `}}let y=`
        <div style="background-color:${i}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${s.event.title}</div>
    `;if(c.tipo==="planilla"){const b=c.pesoTotal!=null?`📦 ${Number(c.pesoTotal).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2})} kg`:null,f=c.longitudTotal!=null?`📏 ${Number(c.longitudTotal).toLocaleString()} m`:null,p=c.diametroMedio!=null?`⌀ ${Number(c.diametroMedio).toFixed(2)} mm`:null,v=[b,f,p].filter(Boolean);v.length>0&&(y+=`<div class="text-sm text-black font-semibold">${v.join(" | ")}</div>`),c.tieneSalidas&&Array.isArray(c.salidas_codigos)&&c.salidas_codigos.length>0&&(y+=`
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${c.salidas_codigos.join(", ")}
                </span>
            </div>`)}return y+="</div>",{html:y}},eventDidMount:function(s){var y,g,b,f;const i=s.event.extendedProps||{};if(i.tipo==="resumen-dia"){s.el.classList.add("evento-resumen-diario"),s.el.style.cursor="default";return}if(s.view.type==="dayGridMonth"){const p=s.el.closest(".fc-daygrid-event-harness");p&&(p.style.setProperty("width","100%","important"),p.style.setProperty("max-width","100%","important"),p.style.setProperty("min-width","100%","important"),p.style.setProperty("position","static","important"),p.style.setProperty("left","unset","important"),p.style.setProperty("right","unset","important"),p.style.setProperty("top","unset","important"),p.style.setProperty("inset","unset","important"),p.style.setProperty("margin","0 0 2px 0","important"),p.style.setProperty("display","block","important")),s.el.style.setProperty("width","100%","important"),s.el.style.setProperty("max-width","100%","important"),s.el.style.setProperty("min-width","100%","important"),s.el.style.setProperty("margin","0","important"),s.el.style.setProperty("position","static","important"),s.el.style.setProperty("left","unset","important"),s.el.style.setProperty("right","unset","important"),s.el.style.setProperty("inset","unset","important"),s.el.style.setProperty("display","block","important"),s.el.querySelectorAll("*").forEach(v=>{v.style.setProperty("width","100%","important"),v.style.setProperty("max-width","100%","important")})}const c=(((y=document.getElementById("filtro-obra"))==null?void 0:y.value)||"").trim().toLowerCase(),m=(((g=document.getElementById("filtro-nombre-obra"))==null?void 0:g.value)||"").trim().toLowerCase();if(c||m){let p=!1;if(i.tipo==="salida"&&i.obras&&Array.isArray(i.obras))p=i.obras.some(v=>{const S=(v.codigo||"").toString().toLowerCase(),P=(v.nombre||"").toString().toLowerCase();return c&&S.includes(c)||m&&P.includes(m)});else{const v=(((b=s.event.extendedProps)==null?void 0:b.cod_obra)||"").toString().toLowerCase(),S=(((f=s.event.extendedProps)==null?void 0:f.nombre_obra)||s.event.title||"").toString().toLowerCase();p=c&&v.includes(c)||m&&S.includes(m)}if(p){s.el.classList.add("evento-filtrado");const v="#1f2937",S="#111827";s.el.style.setProperty("background-color",v,"important"),s.el.style.setProperty("background",v,"important"),s.el.style.setProperty("border-color",S,"important"),s.el.style.setProperty("color","white","important"),s.el.querySelectorAll("*").forEach(P=>{P.style.setProperty("background-color",v,"important"),P.style.setProperty("background",v,"important"),P.style.setProperty("color","white","important")})}}typeof G=="function"&&G(s),typeof V=="function"&&V(s,h)},eventAllow:(s,i)=>{var m;const c=(m=i.extendedProps)==null?void 0:m.tipo;return!(c==="resumen-dia"||c==="festivo")},eventDrop:s=>{var b,f,p,v;const i=s.event.extendedProps||{},c=s.event.id,y={fecha:(b=s.event.start)==null?void 0:b.toISOString(),tipo:i.tipo,planillas_ids:i.planillas_ids||[]},g=(((p=(f=window.AppSalidas)==null?void 0:f.routes)==null?void 0:p.updateItem)||"").replace("__ID__",c);fetch(g,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(v=window.AppSalidas)==null?void 0:v.csrf},body:JSON.stringify(y)}).then(S=>{if(!S.ok)throw new Error("No se pudo actualizar la fecha.");return S.json()}).then(()=>{h.refetchEvents(),h.refetchResources();const P=s.event.start.toISOString().split("T")[0];K(P),L()}).catch(S=>{console.error("Error:",S),s.revert()})},dateClick:s=>{r(s.dateStr)&&Swal.fire({icon:"info",title:"📅 Día festivo",text:"Los festivos se editan en la planificación de Trabajadores.",confirmButtonText:"Entendido"})},eventMinHeight:30,firstDay:1,views:{resourceTimelineWeek:{slotDuration:{days:1},slotLabelFormat:[{weekday:"short",day:"numeric",month:"short"}]},resourceTimeGridDay:{slotDuration:"01:00:00",slotLabelFormat:{hour:"2-digit",minute:"2-digit",hour12:!1},slotLabelInterval:"01:00:00",allDaySlot:!1}},slotLabelContent:s=>{var c;const i=(c=h==null?void 0:h.view)==null?void 0:c.type;return i==="resourceTimeGridDay"?{html:`<div class="text-sm font-medium text-gray-700 py-1">${s.date.toLocaleTimeString("es-ES",{hour:"2-digit",minute:"2-digit",hour12:!1})}</div>`}:i==="resourceTimelineWeek"?{html:`<div class="text-center font-bold text-sm py-2">${new Date(s.date).toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short"})}</div>`}:null},dayHeaderContent:s=>({html:`<div class="text-center font-bold text-base py-2">${new Date(s.date).toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short"})}</div>`}),editable:!0,resourceAreaColumns:[{field:"cod_obra",headerContent:"Código"},{field:"title",headerContent:"Obra"},{field:"cliente",headerContent:"Cliente"}],resourceAreaHeaderContent:"Obras",resourceOrder:"orderIndex",resourceLabelContent:s=>({html:`<div class="text-xs font-semibold">
                        <div class="text-blue-600">${s.resource.extendedProps.cod_obra||""}</div>
                        <div class="text-gray-700 truncate">${s.resource.title||""}</div>
                        <div class="text-gray-500 text-[10px] truncate">${s.resource.extendedProps.cliente||""}</div>
                    </div>`}),windowResize:()=>L()}),h.render(),L(),n.addEventListener("contextmenu",s=>{const i=s.target.closest(".fc-daygrid-day, .fc-timeline-slot, .fc-timegrid-slot, .fc-col-header-cell");if(i){let c=i.getAttribute("data-date");if(!c){const m=s.target.closest("[data-date]");m&&(c=m.getAttribute("data-date"))}if(c&&h){const m=h.view.type;(m==="resourceTimelineWeek"||m==="dayGridMonth")&&(s.preventDefault(),s.stopPropagation(),Swal.fire({title:"📅 Ir a día",text:`¿Quieres ver el día ${c}?`,icon:"question",showCancelButton:!0,confirmButtonText:"Sí, ir al día",cancelButtonText:"Cancelar"}).then(y=>{y.isConfirmed&&(h.changeView("resourceTimeGridDay",c),L())}))}}})}),window.addEventListener("shown.bs.tab",L),window.addEventListener("shown.bs.collapse",L),window.addEventListener("shown.bs.modal",L);function u(){document.querySelectorAll(".resumen-diario-custom").forEach(i=>i.remove())}function d(){if(!h||h.view.type!=="resourceTimeGridDay"){u();return}u();const s=h.getDate(),i=s.getFullYear(),c=String(s.getMonth()+1).padStart(2,"0"),m=String(s.getDate()).padStart(2,"0"),y=`${i}-${c}-${m}`,g=h.getEvents().find(b=>{var f,p;return((f=b.extendedProps)==null?void 0:f.tipo)==="resumen-dia"&&((p=b.extendedProps)==null?void 0:p.fecha)===y});if(g&&g.extendedProps){const b=Number(g.extendedProps.pesoTotal||0).toLocaleString(),f=Number(g.extendedProps.longitudTotal||0).toLocaleString(),p=g.extendedProps.diametroMedio?Number(g.extendedProps.diametroMedio).toFixed(2):null,v=document.createElement("div");v.className="resumen-diario-custom",v.innerHTML=`
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 Peso: ${b} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${f} m</div>
                        ${p?`<div class="text-yellow-800">⌀ Diámetro: ${p} mm</div>`:""}
                    </div>
                </div>
            `,n&&n.parentNode&&n.parentNode.insertBefore(v,n)}}return window.mostrarResumenDiario=d,window.limpiarResumenesCustom=u,h}function Fe(e){if(e.view.type==="dayGridMonth"){const a=new Date(e.start);return a.setDate(a.getDate()+15),a.toISOString().split("T")[0]}if(e.view.type==="resourceTimeGridWeek"||e.view.type==="resourceTimelineWeek"){const a=new Date(e.start),t=Math.floor((e.end-e.start)/(1e3*60*60*24)/2);return a.setDate(a.getDate()+t),a.toISOString().split("T")[0]}return e.startStr.split("T")[0]}function ze(e,a={}){const{selector:t=null,once:o=!1}=a;let n=!1;const r=()=>{t&&!document.querySelector(t)||o&&n||(n=!0,e())};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",r):r(),document.addEventListener("livewire:navigated",r)}function Me(e){document.addEventListener("livewire:navigating",e)}function _e(e){let t=new Date(e).toLocaleDateString("es-ES",{month:"long",year:"numeric"});return`(${t.charAt(0).toUpperCase()+t.slice(1)})`}function Ne(e){const a=new Date(e),t=a.getDay(),o=t===0?-6:1-t,n=new Date(a);n.setDate(a.getDate()+o);const r=new Date(n);r.setDate(n.getDate()+6);const l=new Intl.DateTimeFormat("es-ES",{day:"2-digit",month:"short"}),u=new Intl.DateTimeFormat("es-ES",{year:"numeric"});return`(${l.format(n)} – ${l.format(r)} ${u.format(r)})`}function Be(e){const a=document.querySelector("#resumen-semanal-fecha"),t=document.querySelector("#resumen-mensual-fecha");a&&(a.textContent=Ne(e)),t&&(t.textContent=_e(e));const o=`${window.AppSalidas.routes.totales}?fecha=${encodeURIComponent(e)}`;fetch(o).then(n=>n.json()).then(n=>{const r=n.semana||{},l=n.mes||{};document.querySelector("#resumen-semanal-peso").textContent=`📦 ${Number(r.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-semanal-longitud").textContent=`📏 ${Number(r.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-semanal-diametro").textContent=r.diametro!=null?`⌀ ${Number(r.diametro).toFixed(2)} mm`:"",document.querySelector("#resumen-mensual-peso").textContent=`📦 ${Number(l.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-mensual-longitud").textContent=`📏 ${Number(l.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-mensual-diametro").textContent=l.diametro!=null?`⌀ ${Number(l.diametro).toFixed(2)} mm`:""}).catch(n=>console.error("❌ Totales:",n))}let C;function Oe(){var g,b;if(window.calendar)try{window.calendar.destroy()}catch(f){console.warn("Error al destruir calendario anterior:",f)}const e=Ie();C=e,window.calendar=e,e.refetchResources(),e.refetchEvents(),(g=document.getElementById("ver-con-salidas"))==null||g.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()}),(b=document.getElementById("ver-todas"))==null||b.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()});const t=(localStorage.getItem("fechaCalendario")||new Date().toISOString()).split("T")[0];Be(t);const o=localStorage.getItem("soloSalidas")==="true",n=localStorage.getItem("soloPlanillas")==="true",r=document.getElementById("solo-salidas"),l=document.getElementById("solo-planillas");r&&(r.checked=o),l&&(l.checked=n);const u=document.getElementById("filtro-obra"),d=document.getElementById("filtro-nombre-obra"),s=document.getElementById("btn-reset-filtros"),i=document.getElementById("btn-limpiar-filtros");s==null||s.addEventListener("click",()=>{u&&(u.value=""),d&&(d.value=""),r&&(r.checked=!1,localStorage.setItem("soloSalidas","false")),l&&(l.checked=!1,localStorage.setItem("soloPlanillas","false")),y(),C.refetchEvents()});const m=((f,p=150)=>{let v;return(...S)=>{clearTimeout(v),v=setTimeout(()=>f(...S),p)}})(()=>{C.refetchEvents()},120);u==null||u.addEventListener("input",m),d==null||d.addEventListener("input",m);function y(){const f=r==null?void 0:r.closest(".checkbox-container"),p=l==null?void 0:l.closest(".checkbox-container");f==null||f.classList.remove("active-salidas"),p==null||p.classList.remove("active-planillas"),r!=null&&r.checked&&(f==null||f.classList.add("active-salidas")),l!=null&&l.checked&&(p==null||p.classList.add("active-planillas"))}r==null||r.addEventListener("change",f=>{f.target.checked&&l&&(l.checked=!1,localStorage.setItem("soloPlanillas","false")),localStorage.setItem("soloSalidas",f.target.checked.toString()),y(),C.refetchEvents()}),l==null||l.addEventListener("change",f=>{f.target.checked&&r&&(r.checked=!1,localStorage.setItem("soloSalidas","false")),localStorage.setItem("soloPlanillas",f.target.checked.toString()),y(),C.refetchEvents()}),y(),i==null||i.addEventListener("click",()=>{u&&(u.value=""),d&&(d.value=""),C.refetchEvents()})}let $=null,I=null,D="days",x=-1,E=[];function je(){I&&I();const e=window.calendar;if(!e)return;$=e.getDate(),D="days",x=-1,z();function a(t){const o=t.target.tagName.toLowerCase();if(o==="input"||o==="textarea"||t.target.isContentEditable||document.querySelector(".swal2-container")||!window.calendar||!$)return;let r=!1;if(t.key==="Tab"&&!t.ctrlKey&&!t.metaKey){t.preventDefault(),Re();return}if(t.key==="Escape"&&D==="events"){t.preventDefault(),D="days",x=-1,B(),z(),M();return}D==="events"?r=Ge(t):r=He(t),r&&(t.preventDefault(),t.stopPropagation())}document.addEventListener("keydown",a,!0),e.on("eventsSet",()=>{D==="events"&&(te(),A())}),I=()=>{document.removeEventListener("keydown",a,!0),oe(),B()}}function Re(){D==="days"?(D="events",te(),E.length>0?(x=0,A()):(D="days",Ue())):(D="days",x=-1,B(),z()),M()}function te(){const e=window.calendar;if(!e){E=[];return}E=e.getEvents().filter(a=>{var o;const t=(o=a.extendedProps)==null?void 0:o.tipo;return t!=="resumen-dia"&&t!=="festivo"}).sort((a,t)=>{const o=a.start||new Date(0),n=t.start||new Date(0);return o<n?-1:o>n?1:(a.title||"").localeCompare(t.title||"")})}function Ge(e){if(E.length===0)return!1;let a=!1;switch(e.key){case"ArrowDown":case"ArrowRight":x=(x+1)%E.length,A(),a=!0;break;case"ArrowUp":case"ArrowLeft":x=x<=0?E.length-1:x-1,A(),a=!0;break;case"Home":x=0,A(),a=!0;break;case"End":x=E.length-1,A(),a=!0;break;case"Enter":We(),a=!0;break;case"e":case"E":Ve(),a=!0;break;case"i":case"I":Ke(),a=!0;break}return a}function He(e){const a=window.calendar,t=new Date($);let o=!1;switch(e.key){case"ArrowLeft":t.setDate(t.getDate()-1),o=!0;break;case"ArrowRight":t.setDate(t.getDate()+1),o=!0;break;case"ArrowUp":t.setDate(t.getDate()-7),o=!0;break;case"ArrowDown":t.setDate(t.getDate()+7),o=!0;break;case"Home":t.setDate(1),o=!0;break;case"End":t.setMonth(t.getMonth()+1),t.setDate(0),o=!0;break;case"PageUp":t.setMonth(t.getMonth()-1),o=!0;break;case"PageDown":t.setMonth(t.getMonth()+1),o=!0;break;case"Enter":const n=ae($),r=a.view.type;r==="dayGridMonth"||r==="resourceTimelineWeek"?a.changeView("resourceTimeGridDay",n):a.gotoDate($),o=!0;break;case"t":case"T":!e.ctrlKey&&!e.metaKey&&($=new Date,a.today(),z(),o=!0);break}if(o&&e.key!=="Enter"&&e.key!=="t"&&e.key!=="T"){$=t;const n=a.view;(t<n.currentStart||t>=n.currentEnd)&&a.gotoDate(t),z()}return o}function A(){var t;if(B(),x<0||x>=E.length)return;const e=E[x];if(!e)return;const a=document.querySelector(`[data-event-id="${e.id}"]`)||document.querySelector(`.fc-event[data-event="${e.id}"]`);if(a)a.classList.add("keyboard-focused-event"),a.scrollIntoView({behavior:"smooth",block:"nearest"});else{const o=document.querySelectorAll(".fc-event");for(const n of o)if(n.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){n.classList.add("keyboard-focused-event"),n.scrollIntoView({behavior:"smooth",block:"nearest"});break}}e.start&&($=new Date(e.start)),M()}function B(){document.querySelectorAll(".keyboard-focused-event").forEach(e=>{e.classList.remove("keyboard-focused-event")})}function We(){if(x<0||x>=E.length)return;const e=E[x];if(!e)return;const a=e.extendedProps||{},t=window.calendar;if(a.tipo==="salida"){const o=a.salida_id||e.id;Y(o,t)}else if(a.tipo==="planilla"){const o=a.planillas_ids||[];o.length>0&&(window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${o.join(",")}`)}}function Ve(){var t;if(x<0||x>=E.length)return;const e=E[x];if(!e)return;const a=document.querySelectorAll(".fc-event");for(const o of a)if(o.classList.contains("keyboard-focused-event")||o.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){const n=o.getBoundingClientRect(),r=new MouseEvent("contextmenu",{bubbles:!0,cancelable:!0,clientX:n.left+n.width/2,clientY:n.top+n.height/2});o.dispatchEvent(r);break}}function Ke(){if(x<0||x>=E.length)return;const e=E[x];if(!e)return;const a=e.extendedProps||{};let t=`<strong>${e.title}</strong><br><br>`;a.tipo==="salida"?(t+="<b>Tipo:</b> Salida<br>",a.obras&&a.obras.length>0&&(t+=`<b>Obras:</b> ${a.obras.map(o=>o.nombre).join(", ")}<br>`)):a.tipo==="planilla"&&(t+="<b>Tipo:</b> Planilla<br>",a.cod_obra&&(t+=`<b>Código:</b> ${a.cod_obra}<br>`),a.pesoTotal&&(t+=`<b>Peso:</b> ${Number(a.pesoTotal).toLocaleString()} kg<br>`),a.longitudTotal&&(t+=`<b>Longitud:</b> ${Number(a.longitudTotal).toLocaleString()} m<br>`)),e.start&&(t+=`<b>Fecha:</b> ${e.start.toLocaleDateString("es-ES",{weekday:"long",day:"numeric",month:"long",year:"numeric"})}<br>`),Swal.fire({title:"Información del evento",html:t,icon:"info",confirmButtonText:"Cerrar"})}function Ue(){const e=document.getElementById("keyboard-nav-indicator");if(e){const a=document.getElementById("keyboard-nav-date");a&&(a.innerHTML='<span class="text-yellow-400">No hay eventos visibles</span>'),clearTimeout(e._hideTimeout),e.style.display="flex",e._hideTimeout=setTimeout(()=>{M()},2e3)}}function ae(e){const a=e.getFullYear(),t=String(e.getMonth()+1).padStart(2,"0"),o=String(e.getDate()).padStart(2,"0");return`${a}-${t}-${o}`}function z(){if(oe(),!$)return;const e=ae($),a=window.calendar;if(!a)return;const t=a.view.type;let o=null;t==="dayGridMonth"?o=document.querySelector(`.fc-daygrid-day[data-date="${e}"]`):t==="resourceTimelineWeek"?(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(r=>{r.dataset.date&&r.dataset.date.startsWith(e)&&(o=r)}),o||(o=document.querySelector(`.fc-timeline-slot-lane[data-date^="${e}"]`))):t==="resourceTimeGridDay"&&(o=document.querySelector(".fc-col-header-cell")),o&&(o.classList.add("keyboard-focused-day"),o.scrollIntoView({behavior:"smooth",block:"nearest",inline:"nearest"})),M()}function oe(){document.querySelectorAll(".keyboard-focused-day").forEach(e=>{e.classList.remove("keyboard-focused-day")})}function M(){let e=document.getElementById("keyboard-nav-indicator");if(e||(e=document.createElement("div"),e.id="keyboard-nav-indicator",e.className="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm",document.body.appendChild(e)),D==="events"){const a=E[x],t=(a==null?void 0:a.title)||"Sin evento",o=`${x+1}/${E.length}`;e.innerHTML=`
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
        `}else{const a=$?$.toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short",year:"numeric"}):"";e.innerHTML=`
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
        `}clearTimeout(e._hideTimeout),e.style.display="block",e._hideTimeout=setTimeout(()=>{e.style.display="none"},4e3)}function Ye(){if(document.getElementById("keyboard-nav-styles"))return;const e=document.createElement("style");e.id="keyboard-nav-styles",e.textContent=`
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
    `,document.head.appendChild(e)}ze(()=>{Oe(),Ye(),setTimeout(()=>{je()},500)},{selector:'#calendario[data-calendar-type="salidas"]'});Me(()=>{if(I&&(I(),I=null),window.calendar)try{window.calendar.destroy(),window.calendar=null}catch(a){console.warn("Error al limpiar calendario de salidas:",a)}const e=document.getElementById("keyboard-nav-indicator");e&&e.remove()});
