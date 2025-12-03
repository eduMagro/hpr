async function ce(e,a){var t,o,n,s;try{const d=(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.planificacion;if(!d)return[];const u=new URLSearchParams({tipo:"events",viewType:e||"",start:a.startStr||"",end:a.endStr||"",t:Date.now()}),c=await fetch(`${d}?${u.toString()}`);if(!c.ok)return console.error("Error eventos",c.status),[];const m=await c.json();let g=Array.isArray(m)?m:Array.isArray(m==null?void 0:m.events)?m.events:[];const S=((n=document.getElementById("solo-salidas"))==null?void 0:n.checked)||!1,h=((s=document.getElementById("solo-planillas"))==null?void 0:s.checked)||!1,r=g.filter(p=>{var f;return((f=p.extendedProps)==null?void 0:f.tipo)==="resumen-dia"}),i=g.filter(p=>{var f;return((f=p.extendedProps)==null?void 0:f.tipo)!=="resumen-dia"});let l=i;return S&&!h?l=i.filter(p=>{var y;return((y=p.extendedProps)==null?void 0:y.tipo)==="salida"}):h&&!S&&(l=i.filter(p=>{var y;const f=(y=p.extendedProps)==null?void 0:y.tipo;return f==="planilla"||f==="festivo"})),[...l,...r]}catch(d){return console.error("fetch eventos falló:",d),[]}}async function ue(e,a){var d,u;const t=(u=(d=window.AppSalidas)==null?void 0:d.routes)==null?void 0:u.planificacion;if(!t)return[];const o=new URLSearchParams({tipo:"resources",viewType:e,start:a.startStr||"",end:a.endStr||""}),n=await fetch(`${t}?${o.toString()}`,{method:"GET"});if(!n.ok)throw new Error("Error cargando recursos");const s=await n.json();return Array.isArray(s)?s:Array.isArray(s==null?void 0:s.resources)?s.resources:[]}function Y(e,a){const t=e.event.extendedProps||{};if(t.tipo!=="festivo"){if(t.tipo==="planilla"){const o=`
      ✅ Fabricados: ${V(t.fabricadosKg)} kg<br>
      🔄 Fabricando: ${V(t.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${V(t.pendientesKg)} kg
    `;tippy(e.el,{content:o,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}t.tipo==="salida"&&t.comentario&&t.comentario.trim()&&tippy(e.el,{content:t.comentario,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}}function V(e){return e!=null?Number(e).toLocaleString():0}let W=null;function q(){W&&(W.remove(),W=null,document.removeEventListener("click",q),document.removeEventListener("contextmenu",q,!0),document.removeEventListener("scroll",q,!0),window.removeEventListener("resize",q),window.removeEventListener("keydown",ee))}function ee(e){e.key==="Escape"&&q()}function pe(e,a,t){q();const o=document.createElement("div");o.className="fc-contextmenu",Object.assign(o.style,{position:"fixed",top:a+"px",left:e+"px",zIndex:99999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",fontFamily:"system-ui, -apple-system, Segoe UI, Roboto, sans-serif"}),o.innerHTML=t,document.body.appendChild(o),W=o;const n=o.getBoundingClientRect(),s=Math.max(0,n.right-window.innerWidth+8),d=Math.max(0,n.bottom-window.innerHeight+8);return(s||d)&&(o.style.left=Math.max(8,e-s)+"px",o.style.top=Math.max(8,a-d)+"px"),setTimeout(()=>{document.addEventListener("click",q),document.addEventListener("contextmenu",q,!0),document.addEventListener("scroll",q,!0),window.addEventListener("resize",q),window.addEventListener("keydown",ee)},0),o}function me(e,a,{headerHtml:t="",items:o=[]}={}){const n=`
    <div class="ctx-menu-container">
      ${t?`<div class="ctx-menu-header">${t}</div>`:""}
      ${o.map((d,u)=>`
        <button type="button"
          class="ctx-menu-item${d.danger?" ctx-menu-danger":""}"
          data-idx="${u}">
          ${d.icon?`<span class="ctx-menu-icon">${d.icon}</span>`:""}
          <span class="ctx-menu-label">${d.label}</span>
        </button>`).join("")}
    </div>
  `,s=pe(e,a,n);return s.querySelectorAll(".ctx-menu-item").forEach(d=>{d.addEventListener("click",async u=>{var g;u.preventDefault(),u.stopPropagation();const c=Number(d.dataset.idx),m=(g=o[c])==null?void 0:g.onClick;q();try{await(m==null?void 0:m())}catch(S){console.error(S)}})}),s}function fe(e){if(!e||typeof e!="string")return"";const a=e.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);if(a){const t=a[1],o=a[2].padStart(2,"0"),n=a[3].padStart(2,"0");return`${t}-${o}-${n}`}return e}(function(){if(document.getElementById("swal-anims"))return;const a=document.createElement("style");a.id="swal-anims",a.textContent=`
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
  `,document.head.appendChild(a)})();function ge(e){const a=e.extendedProps||{};if(Array.isArray(a.planillas_ids)&&a.planillas_ids.length)return a.planillas_ids;const t=(e.id||"").match(/planilla-(\d+)/);return t?[Number(t[1])]:[]}async function te(e,a){var t,o;try{q()}catch{}if(!e)return Swal.fire("⚠️","ID de salida inválido.","warning");try{const n=await fetch(`${(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.informacionPaquetesSalida}?salida_id=${e}`,{headers:{Accept:"application/json"}});if(!n.ok)throw new Error("Error al cargar información de la salida");const{salida:s,paquetesAsignados:d,paquetesDisponibles:u,paquetesTodos:c,filtros:m}=await n.json();ye(s,d,u,c||[],m||{obras:[],planillas:[],obrasRelacionadas:[]},a)}catch(n){console.error(n),Swal.fire("❌","Error al cargar la información de la salida","error")}}function ye(e,a,t,o,n,s){window._gestionPaquetesData={salida:e,paquetesAsignados:a,paquetesDisponibles:t,paquetesTodos:o,filtros:n,mostrarTodos:!1};const d=be(e,a,t,n);Swal.fire({title:`📦 Gestionar Paquetes - Salida ${e.codigo_salida||e.id}`,html:d,width:Math.min(window.innerWidth*.95,1200),showConfirmButton:!0,showCancelButton:!0,confirmButtonText:"💾 Guardar Cambios",cancelButtonText:"Cancelar",focusConfirm:!1,customClass:{popup:"w-full max-w-screen-xl"},didOpen:()=>{ne(),ve(),Ee(),setTimeout(()=>{we()},100)},willClose:()=>{k.cleanup&&k.cleanup();const u=document.getElementById("modal-keyboard-indicator");u&&u.remove()},preConfirm:()=>ke()}).then(async u=>{u.isConfirmed&&u.value&&await $e(e.id,u.value,s)})}function be(e,a,t,o){var m,g;const n=a.reduce((S,h)=>S+(parseFloat(h.peso)||0),0);let s="";e.salida_clientes&&e.salida_clientes.length>0&&(s='<div class="col-span-2"><strong>Obras/Clientes:</strong><br>',e.salida_clientes.forEach(S=>{var l,p,f,y,w;const h=((l=S.obra)==null?void 0:l.obra)||"Obra desconocida",r=(p=S.obra)!=null&&p.cod_obra?`(${S.obra.cod_obra})`:"",i=((f=S.cliente)==null?void 0:f.empresa)||((w=(y=S.obra)==null?void 0:y.cliente)==null?void 0:w.empresa)||"";s+=`<span class="text-xs">• ${h} ${r}`,i&&(s+=` - ${i}`),s+="</span><br>"}),s+="</div>");const d=`
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
    `,u=((o==null?void 0:o.obras)||[]).map(S=>`<option value="${S.id}">${S.cod_obra||""} - ${S.obra||"Sin nombre"}</option>`).join(""),c=((o==null?void 0:o.planillas)||[]).map(S=>`<option value="${S.id}" data-obra-id="${S.obra_id||""}">${S.codigo||"Sin código"}</option>`).join("");return`
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
                        <span class="text-xs bg-green-200 px-2 py-1 rounded" id="peso-asignados">${n.toFixed(2)} kg</span>
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
                                    ${u}
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
                        ${U(t)}
                    </div>
                </div>
            </div>
        </div>
    `}function U(e){return!e||e.length===0?'<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>':e.map(a=>{var t,o,n,s,d,u,c,m,g,S,h,r,i,l,p,f;return`
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${a.id}"
            data-peso="${a.peso||0}"
            data-obra-id="${((o=(t=a.planilla)==null?void 0:t.obra)==null?void 0:o.id)||""}"
            data-obra="${((s=(n=a.planilla)==null?void 0:n.obra)==null?void 0:s.obra)||""}"
            data-planilla-id="${((d=a.planilla)==null?void 0:d.id)||""}"
            data-planilla="${((u=a.planilla)==null?void 0:u.codigo)||""}"
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
                <div>🏗️ ${((h=(S=a.planilla)==null?void 0:S.obra)==null?void 0:h.cod_obra)||""} - ${((i=(r=a.planilla)==null?void 0:r.obra)==null?void 0:i.obra)||"N/A"}</div>
                <div>👤 ${((p=(l=a.planilla)==null?void 0:l.cliente)==null?void 0:p.empresa)||"Sin cliente"}</div>
                ${(f=a.nave)!=null&&f.obra?`<div class="text-blue-600 font-medium">📍 ${a.nave.obra}</div>`:""}
            </div>
        </div>
    `}).join("")}async function he(e){var a;try{const t=document.querySelector(`[data-paquete-id="${e}"]`);let o=null;if(t&&t.dataset.paqueteJson)try{o=JSON.parse(t.dataset.paqueteJson.replace(/&#39;/g,"'"))}catch(c){console.warn("No se pudo parsear JSON del paquete",c)}if(!o){const c=await fetch(`/api/paquetes/${e}/elementos`);c.ok&&(o=await c.json())}if(!o){alert("No se pudo obtener información del paquete");return}const n=[];if(o.etiquetas&&o.etiquetas.length>0&&o.etiquetas.forEach(c=>{c.elementos&&c.elementos.length>0&&c.elementos.forEach(m=>{n.push({id:m.id,dimensiones:m.dimensiones,peso:m.peso,longitud:m.longitud,diametro:m.diametro})})}),n.length===0){alert("Este paquete no tiene elementos para mostrar");return}const s=n.map((c,m)=>`
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
        `).join(""),d=document.getElementById("modal-elementos-paquete-overlay");d&&d.remove();const u=`
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
        `;document.body.insertAdjacentHTML("beforeend",u),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&n.forEach(c=>{c.dimensiones&&window.dibujarFiguraElemento(`elemento-dibujo-${c.id}`,c.dimensiones,null)})},100)}catch(t){console.error("Error al ver elementos del paquete:",t),alert("Error al cargar los elementos del paquete")}}window.verElementosPaqueteSalida=he;function ve(e){const a=document.getElementById("filtro-obra-modal"),t=document.getElementById("filtro-planilla-modal"),o=document.getElementById("btn-limpiar-filtros-modal");a&&a.addEventListener("change",()=>{J(),K()}),t&&t.addEventListener("change",()=>{K()}),o&&o.addEventListener("click",()=>{a&&(a.value=""),t&&(t.value=""),J(),K()})}function J(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData;if(!a||!t)return;const o=(e==null?void 0:e.value)||"",n=o?t.paquetesTodos:t.paquetesDisponibles,s=new Map;n.forEach(c=>{var m,g,S;if((m=c.planilla)!=null&&m.id){if(o&&String((g=c.planilla.obra)==null?void 0:g.id)!==o)return;s.has(c.planilla.id)||s.set(c.planilla.id,{id:c.planilla.id,codigo:c.planilla.codigo||"Sin código",obra_id:(S=c.planilla.obra)==null?void 0:S.id})}});const d=Array.from(s.values()).sort((c,m)=>(c.codigo||"").localeCompare(m.codigo||"")),u=a.value;a.innerHTML='<option value="">-- Todas las planillas --</option>',d.forEach(c=>{const m=document.createElement("option");m.value=c.id,m.textContent=c.codigo,a.appendChild(m)}),u&&s.has(parseInt(u))?a.value=u:a.value=""}function K(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData,o=(e==null?void 0:e.value)||"",n=(a==null?void 0:a.value)||"",s=document.querySelector('[data-zona="disponibles"]');if(!s||!t)return;const d=document.querySelector('[data-zona="asignados"]'),u=new Set;d&&d.querySelectorAll(".paquete-item-salida").forEach(g=>{u.add(parseInt(g.dataset.paqueteId))});let m=(o?t.paquetesTodos:t.paquetesDisponibles).filter(g=>{var S,h,r;return!(u.has(g.id)||o&&String((h=(S=g.planilla)==null?void 0:S.obra)==null?void 0:h.id)!==o||n&&String((r=g.planilla)==null?void 0:r.id)!==n)});s.innerHTML=U(m),ne(),m.length===0&&(s.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">No hay paquetes que coincidan con el filtro</div>')}let k={zonaActiva:"asignados",indiceFocused:-1,cleanup:null};function we(){k.cleanup&&k.cleanup(),k.zonaActiva="asignados",k.indiceFocused=0,A();function e(a){var S;if(!document.querySelector(".swal2-container"))return;const t=a.target.tagName.toLowerCase(),o=t==="select";if((t==="input"||t==="textarea")&&a.key!=="Escape")return;const s=document.querySelector('[data-zona="asignados"]'),d=document.querySelector('[data-zona="disponibles"]');if(!s||!d)return;const u=k.zonaActiva==="asignados"?s:d,c=Array.from(u.querySelectorAll('.paquete-item-salida:not([style*="display: none"])')),m=c.length;let g=!1;if(!o)switch(a.key){case"ArrowDown":m>0&&(k.indiceFocused=(k.indiceFocused+1)%m,A(),g=!0);break;case"ArrowUp":m>0&&(k.indiceFocused=k.indiceFocused<=0?m-1:k.indiceFocused-1,A(),g=!0);break;case"ArrowLeft":case"ArrowRight":k.zonaActiva=k.zonaActiva==="asignados"?"disponibles":"asignados",k.indiceFocused=0,A(),g=!0;break;case"Tab":a.preventDefault(),k.zonaActiva=k.zonaActiva==="asignados"?"disponibles":"asignados",k.indiceFocused=0,A(),g=!0;break;case"Enter":{if(m>0&&k.indiceFocused>=0){const h=c[k.indiceFocused];if(h){xe(h);const r=Array.from(u.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));k.indiceFocused>=r.length&&(k.indiceFocused=Math.max(0,r.length-1)),A(),g=!0}}break}case"Home":k.indiceFocused=0,A(),g=!0;break;case"End":k.indiceFocused=Math.max(0,m-1),A(),g=!0;break}if(g){a.preventDefault(),a.stopPropagation();return}switch(a.key){case"o":case"O":{const h=document.getElementById("filtro-obra-modal");h&&(h.focus(),g=!0);break}case"p":case"P":{const h=document.getElementById("filtro-planilla-modal");h&&(h.focus(),g=!0);break}case"l":case"L":{const h=document.getElementById("btn-limpiar-filtros-modal");h&&(h.click(),(S=document.activeElement)==null||S.blur(),A(),g=!0);break}case"/":case"f":case"F":{const h=document.getElementById("filtro-obra-modal");h&&(h.focus(),g=!0);break}case"Escape":o&&(document.activeElement.blur(),A(),g=!0);break;case"s":case"S":{if(a.ctrlKey||a.metaKey){const h=document.querySelector(".swal2-confirm");h&&(h.click(),g=!0)}break}}g&&(a.preventDefault(),a.stopPropagation())}document.addEventListener("keydown",e,!0),k.cleanup=()=>{document.removeEventListener("keydown",e,!0),ae()}}function A(){ae();const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;k.zonaActiva==="asignados"?(e.classList.add("zona-activa-keyboard"),a.classList.remove("zona-activa-keyboard")):(a.classList.add("zona-activa-keyboard"),e.classList.remove("zona-activa-keyboard"));const t=k.zonaActiva==="asignados"?e:a,o=Array.from(t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));if(o.length>0&&k.indiceFocused>=0){const n=Math.min(k.indiceFocused,o.length-1),s=o[n];s&&(s.classList.add("paquete-focused-keyboard"),s.scrollIntoView({behavior:"smooth",block:"nearest"}))}Se()}function ae(){document.querySelectorAll(".paquete-focused-keyboard").forEach(e=>{e.classList.remove("paquete-focused-keyboard")}),document.querySelectorAll(".zona-activa-keyboard").forEach(e=>{e.classList.remove("zona-activa-keyboard")})}function xe(e){const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!a||!t)return;const o=e.closest("[data-zona]"),n=o.dataset.zona==="asignados"?t:a,s=n.querySelector(".placeholder-sin-paquetes");if(s&&s.remove(),n.appendChild(e),o.querySelectorAll(".paquete-item-salida").length===0){const u=document.createElement("div");u.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",u.textContent="Sin paquetes",o.appendChild(u)}oe(e),se()}function Se(){let e=document.getElementById("modal-keyboard-indicator");e||(e=document.createElement("div"),e.id="modal-keyboard-indicator",e.className="fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs",document.body.appendChild(e));const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]'),o=(a==null?void 0:a.querySelectorAll(".paquete-item-salida").length)||0,n=(t==null?void 0:t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0,s=k.zonaActiva==="asignados"?`📦 Asignados (${o})`:`📋 Disponibles (${n})`;e.innerHTML=`
        <div class="flex items-center gap-2 mb-2">
            <span class="${k.zonaActiva==="asignados"?"bg-green-500":"bg-gray-500"} text-white text-xs px-2 py-0.5 rounded">${s}</span>
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
    `,clearTimeout(e._checkTimeout),e._checkTimeout=setTimeout(()=>{document.querySelector(".swal2-container")||e.remove()},500)}function Ee(){if(document.getElementById("modal-keyboard-styles"))return;const e=document.createElement("style");e.id="modal-keyboard-styles",e.textContent=`
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
    `,document.head.appendChild(e)}function oe(e){e.addEventListener("dragstart",a=>{e.style.opacity="0.5",a.dataTransfer.setData("text/plain",e.dataset.paqueteId)}),e.addEventListener("dragend",a=>{e.style.opacity="1"})}function ne(){document.querySelectorAll(".paquete-item-salida").forEach(e=>{oe(e)}),document.querySelectorAll(".drop-zone").forEach(e=>{e.addEventListener("dragover",a=>{a.preventDefault();const t=e.dataset.zona;e.style.backgroundColor=t==="asignados"?"#d1fae5":"#e0f2fe"}),e.addEventListener("dragleave",a=>{e.style.backgroundColor=""}),e.addEventListener("drop",a=>{a.preventDefault(),e.style.backgroundColor="";const t=a.dataTransfer.getData("text/plain"),o=document.querySelector(`.paquete-item-salida[data-paquete-id="${t}"]`);if(o){const n=e.querySelector(".placeholder-sin-paquetes");n&&n.remove(),e.appendChild(o),se()}})})}function se(){const e=document.querySelector('[data-zona="asignados"]'),a=e==null?void 0:e.querySelectorAll(".paquete-item-salida");let t=0;a==null||a.forEach(n=>{const s=parseFloat(n.dataset.peso)||0;t+=s});const o=document.getElementById("peso-asignados");o&&(o.textContent=`${t.toFixed(2)} kg`)}function ke(){const e=document.querySelector('[data-zona="asignados"]');return{paquetes_ids:Array.from((e==null?void 0:e.querySelectorAll(".paquete-item-salida"))||[]).map(t=>parseInt(t.dataset.paqueteId))}}async function $e(e,a,t){var o,n,s,d;try{const c=await(await fetch((n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.guardarPaquetesSalida,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf},body:JSON.stringify({salida_id:e,paquetes_ids:a.paquetes_ids})})).json();c.success?(await Swal.fire({icon:"success",title:"✅ Cambios Guardados",text:"Los paquetes de la salida se han actualizado correctamente.",timer:2e3}),t&&(t.refetchEvents(),(d=t.refetchResources)==null||d.call(t))):await Swal.fire("⚠️",c.message||"No se pudieron guardar los cambios","warning")}catch(u){console.error(u),Swal.fire("❌","Error al guardar los paquetes","error")}}async function De(e,a,t){try{q()}catch{}window.Livewire.dispatch("abrirComentario",{salidaId:e}),window._calendarRef=t}function Te(e){return e?typeof e=="string"?e.split(",").map(t=>t.trim()).filter(Boolean):Array.from(e).map(t=>typeof t=="object"&&(t==null?void 0:t.id)!=null?t.id:t).map(String).map(t=>t.trim()).filter(Boolean):[]}async function Ce(e){var s,d;const a=(d=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:d.informacionPlanillas;if(!a)throw new Error("Ruta 'informacionPlanillas' no configurada");const t=`${a}?ids=${encodeURIComponent(e.join(","))}`,o=await fetch(t,{headers:{Accept:"application/json"}});if(!o.ok){const u=await o.text().catch(()=>"");throw new Error(`GET ${t} -> ${o.status} ${u}`)}const n=await o.json();return Array.isArray(n==null?void 0:n.planillas)?n.planillas:[]}function re(e){if(!e)return!1;const t=new Date(e+"T00:00:00").getDay();return t===0||t===6}function qe(e,a,t){const o=document.getElementById("modal-figura-elemento-overlay");o&&o.remove();const n=t.getBoundingClientRect(),s=320,d=200;let u=n.right+10;u+s>window.innerWidth&&(u=n.left-s-10);let c=n.top-d/2+n.height/2;c<10&&(c=10),c+d>window.innerHeight-10&&(c=window.innerHeight-d-10);const m=`
        <div id="modal-figura-elemento-overlay"
             class="fixed bg-white rounded-lg shadow-2xl border border-gray-300"
             style="z-index: 10001; left: ${u}px; top: ${c}px; width: ${s}px;"
             onmouseleave="this.remove()">
            <div class="flex items-center justify-between px-3 py-2 border-b bg-gray-100 rounded-t-lg">
                <h3 class="text-xs font-semibold text-gray-700">Elemento #${e}</h3>
            </div>
            <div class="p-2">
                <div id="figura-elemento-container-${e}" class="w-full h-40 bg-gray-50 rounded"></div>
            </div>
        </div>
    `;document.body.insertAdjacentHTML("beforeend",m),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&window.dibujarFiguraElemento(`figura-elemento-container-${e}`,a,null)},50)}function Le(e){return`
    <div class="text-left">
      <div class="text-sm text-gray-600 mb-2">
        Edita la <strong>fecha estimada de entrega</strong> de planillas y elementos.
        <span class="text-blue-600">▶</span> = expandir elementos
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
          <tbody>${e.map((t,o)=>{var r,i,l;const n=((r=t.obra)==null?void 0:r.codigo)||"",s=((i=t.obra)==null?void 0:i.nombre)||"",d=t.seccion||"";t.descripcion;const u=t.codigo||`Planilla ${t.id}`,c=t.peso_total?parseFloat(t.peso_total).toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})+" kg":"",m=fe(t.fecha_estimada_entrega),g=t.elementos&&t.elementos.length>0,S=((l=t.elementos)==null?void 0:l.length)||0;let h="";return g&&(h=t.elementos.map((p,f)=>{const y=p.fecha_entrega||"",w=p.peso?parseFloat(p.peso).toFixed(2):"-",E=p.codigo||"-",v=p.dimensiones&&p.dimensiones.trim()!=="",b=v?p.dimensiones.replace(/"/g,"&quot;").replace(/'/g,"&#39;"):"";return`
                    <tr class="elemento-row elemento-planilla-${t.id} bg-gray-50 hidden">
                        <td class="px-2 py-1 text-xs text-gray-400 pl-8">
                            <div class="flex items-center gap-1">
                                <span>↳</span>
                                <span class="font-medium text-gray-600">${E}</span>
                                ${v?`
                                <button type="button"
                                        class="ver-figura-elemento text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded p-0.5 transition-colors"
                                        data-elemento-id="${p.id}"
                                        data-dimensiones="${b}"
                                        title="Ver figura del elemento">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                `:""}
                            </div>
                        </td>
                        <td class="px-2 py-1 text-xs text-gray-500">${p.marca||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">Ø${p.diametro||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${p.longitud||"-"} mm</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${p.barras||"-"} uds</td>
                        <td class="px-2 py-1 text-xs text-right text-gray-500">${w} kg</td>
                        <td class="px-2 py-1" colspan="2">
                            <input type="date" class="swal2-input !m-0 !w-auto !text-xs elemento-fecha"
                                   data-elemento-id="${p.id}"
                                   data-planilla-id="${t.id}"
                                   value="${y}">
                        </td>
                    </tr>`}).join("")),`
<tr class="planilla-row hover:bg-blue-50 bg-blue-100 border-t border-blue-200" data-planilla-id="${t.id}" style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${o*18}ms;">
  <td class="px-2 py-2 text-xs font-semibold text-blue-800" colspan="2">
    ${g?`<button type="button" class="toggle-elementos mr-1 text-blue-600 hover:text-blue-800" data-planilla-id="${t.id}">▶</button>`:""}
    📄 ${u}
    ${g?`<span class="ml-1 text-xs text-blue-500 font-normal">(${S} elem.)</span>`:""}
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

      <div class="mt-2 flex gap-2">
        <button type="button" id="expandir-todos" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded">
          📂 Expandir todos
        </button>
        <button type="button" id="colapsar-todos" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-1 rounded">
          📁 Colapsar todos
        </button>
      </div>
    </div>`}function Ae(e){const a={};return document.querySelectorAll('input[type="date"][data-planilla-id]').forEach(o=>{const n=parseInt(o.dataset.planillaId),s=o.value,d=e.find(u=>u.id===n);s&&d&&d.peso_total&&(a[s]||(a[s]={peso:0,planillas:0,esFinDeSemana:re(s)}),a[s].peso+=parseFloat(d.peso_total),a[s].planillas+=1)}),a}function X(e){const a=Ae(e),t=document.getElementById("resumen-contenido");if(!t)return;const o=Object.keys(a).sort();if(o.length===0){t.innerHTML='<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';return}const n=o.map(u=>{const c=a[u],m=new Date(u+"T00:00:00").toLocaleDateString("es-ES",{weekday:"short",day:"2-digit",month:"2-digit",year:"numeric"}),g=c.peso.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2}),S=c.esFinDeSemana?"bg-orange-100 border-orange-300 text-orange-800":"bg-green-100 border-green-300 text-green-800",h=c.esFinDeSemana?"🏖️":"📦";return`
            <div class="inline-block m-1 px-2 py-1 rounded border ${S}">
                <span class="font-medium">${h} ${m}</span>
                <br>
                <span class="text-xs">${g} kg (${c.planillas} planilla${c.planillas!==1?"s":""})</span>
            </div>
        `}).join(""),s=o.reduce((u,c)=>u+a[c].peso,0),d=o.reduce((u,c)=>u+a[c].planillas,0);t.innerHTML=`
        <div class="mb-2">${n}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${s.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})} kg 
            (${d} planilla${d!==1?"s":""})
        </div>
    `}async function Pe(e){var o,n,s;const a=(n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.actualizarFechasPlanillas;if(!a)throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");const t=await fetch(a,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf,Accept:"application/json"},body:JSON.stringify({planillas:e})});if(!t.ok){const d=await t.text().catch(()=>"");throw new Error(`PUT ${a} -> ${t.status} ${d}`)}return t.json().catch(()=>({}))}async function Ie(e,a){var t,o;try{const n=Array.from(new Set(Te(e))).map(Number).filter(Boolean);if(!n.length)return Swal.fire("⚠️","No hay planillas en la agrupación.","warning");const s=await Ce(n);if(!s.length)return Swal.fire("⚠️","No se han encontrado planillas.","warning");const u=`
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `+Le(s),{isConfirmed:c}=await Swal.fire({title:"",html:u,width:Math.min(window.innerWidth*.98,1200),customClass:{popup:"w-full max-w-screen-xl"},showCancelButton:!0,confirmButtonText:"💾 Guardar",cancelButtonText:"Cancelar",focusConfirm:!1,showClass:{popup:"swal-fade-in-zoom"},hideClass:{popup:"swal-fade-out"},didOpen:r=>{var p,f;Fe(r),N("#swal-drag",!1),setTimeout(()=>{const y=Swal.getHtmlContainer().querySelector('input[type="date"]');y==null||y.focus({preventScroll:!0})},120),Swal.getHtmlContainer().querySelectorAll('input[type="date"]').forEach(y=>{y.addEventListener("change",function(){re(this.value)?this.classList.add("weekend-date"):this.classList.remove("weekend-date"),X(s)})});const l=Swal.getHtmlContainer();l.querySelectorAll(".toggle-elementos").forEach(y=>{y.addEventListener("click",w=>{w.stopPropagation();const E=y.dataset.planillaId,v=l.querySelectorAll(`.elemento-planilla-${E}`),b=y.textContent==="▼";v.forEach($=>{$.classList.toggle("hidden",b)}),y.textContent=b?"▶":"▼"})}),(p=l.querySelector("#expandir-todos"))==null||p.addEventListener("click",()=>{l.querySelectorAll(".elemento-row").forEach(y=>y.classList.remove("hidden")),l.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▼")}),(f=l.querySelector("#colapsar-todos"))==null||f.addEventListener("click",()=>{l.querySelectorAll(".elemento-row").forEach(y=>y.classList.add("hidden")),l.querySelectorAll(".toggle-elementos").forEach(y=>y.textContent="▶")}),l.querySelectorAll(".aplicar-fecha-elementos").forEach(y=>{y.addEventListener("click",w=>{var b;w.stopPropagation();const E=y.dataset.planillaId,v=(b=l.querySelector(`.planilla-fecha[data-planilla-id="${E}"]`))==null?void 0:b.value;v&&l.querySelectorAll(`.elemento-fecha[data-planilla-id="${E}"]`).forEach($=>{$.value=v,$.dispatchEvent(new Event("change"))})})}),l.querySelectorAll(".ver-figura-elemento").forEach(y=>{y.addEventListener("mouseenter",w=>{var b;const E=y.dataset.elementoId,v=((b=y.dataset.dimensiones)==null?void 0:b.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"";v&&typeof window.dibujarFiguraElemento=="function"&&qe(E,v,y)}),y.addEventListener("mouseleave",w=>{setTimeout(()=>{const E=document.getElementById("modal-figura-elemento-overlay");E&&!E.matches(":hover")&&E.remove()},100)})}),setTimeout(()=>{X(s)},100)}});if(!c)return;const m=Swal.getHtmlContainer(),g=m.querySelectorAll(".planilla-fecha"),S=Array.from(g).map(r=>{const i=Number(r.getAttribute("data-planilla-id")),l=m.querySelectorAll(`.elemento-fecha[data-planilla-id="${i}"]`),p=Array.from(l).map(f=>({id:Number(f.getAttribute("data-elemento-id")),fecha_entrega:f.value||null}));return{id:i,fecha_estimada_entrega:r.value,elementos:p.length>0?p:void 0}}),h=await Pe(S);await Swal.fire(h.success?"✅":"⚠️",h.message||(h.success?"Fechas actualizadas":"No se pudieron actualizar"),h.success?"success":"warning"),h.success&&a&&((t=a.refetchEvents)==null||t.call(a),(o=a.refetchResources)==null||o.call(a))}catch(n){console.error("[CambiarFechasEntrega] error:",n),Swal.fire("❌",(n==null?void 0:n.message)||"Ocurrió un error al actualizar las fechas.","error")}}function Z(e,a){e.el.addEventListener("mousedown",q),e.el.addEventListener("contextmenu",t=>{t.preventDefault(),t.stopPropagation();const o=e.event,n=o.extendedProps||{},s=n.tipo||"planilla";let d="";if(s==="salida"){if(n.clientes&&Array.isArray(n.clientes)&&n.clientes.length>0){const m=n.clientes.map(g=>g.nombre).filter(Boolean).join(", ");m&&(d+=`<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${m}</span>`)}n.obras&&Array.isArray(n.obras)&&n.obras.length>0&&(d+='<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ ',d+=n.obras.map(m=>{const g=m.codigo?`(${m.codigo})`:"";return`${m.nombre} ${g}`}).join(", "),d+="</span>")}const u=`
      <div style="padding:10px 12px; font-weight:600;">
        ${o.title??"Evento"}${d}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(o.start).toLocaleString()} — ${new Date(o.end).toLocaleString()}
        </span>
      </div>
    `;let c=[];if(s==="planilla"){const m=ge(o);c=[{label:"Gestionar Salidas y Paquetes",icon:"📦",onClick:()=>window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${m.join(",")}`},{label:"Cambiar fechas de entrega",icon:"🗓️",onClick:()=>Ie(m,a)}]}else if(s==="salida"){const m=n.salida_id||o.id;n.empresa_id,n.empresa,c=[{label:"Abrir salida",icon:"🧾",onClick:()=>window.open(`/salidas-ferralla/${m}`,"_blank")},{label:"Gestionar paquetes",icon:"📦",onClick:()=>te(m,a)},{label:"Agregar comentario",icon:"✍️",onClick:()=>De(m,n.comentario||"",a)}]}else c=[{label:"Abrir",icon:"🧾",onClick:()=>window.open(n.url||"#","_blank")}];me(t.clientX,t.clientY,{headerHtml:u,items:c})})}function Fe(e){e.style.transform="none",e.style.position="fixed",e.style.margin="0";const a=e.offsetWidth,t=e.offsetHeight,o=Math.max(0,Math.round((window.innerWidth-a)/2)),n=Math.max(0,Math.round((window.innerHeight-t)/2));e.style.left=`${o}px`,e.style.top=`${n}px`}function N(e=".swal2-title",a=!1){const t=Swal.getPopup(),o=Swal.getHtmlContainer();let n=(e?(o==null?void 0:o.querySelector(e))||(t==null?void 0:t.querySelector(e)):null)||t;if(!t||!n)return;a&&N.__lastPos&&(t.style.left=N.__lastPos.left,t.style.top=N.__lastPos.top,t.style.transform="none"),n.style.cursor="move",n.style.touchAction="none";const s=i=>{var l;return((l=i.closest)==null?void 0:l.call(i,"input, textarea, select, button, a, label, [contenteditable]"))!=null};let d=!1,u=0,c=0,m=0,g=0;const S=i=>{if(!n.contains(i.target)||s(i.target))return;d=!0,document.body.style.userSelect="none";const l=t.getBoundingClientRect();t.style.left=`${l.left}px`,t.style.top=`${l.top}px`,t.style.transform="none",m=parseFloat(t.style.left||l.left),g=parseFloat(t.style.top||l.top),u=i.clientX,c=i.clientY,document.addEventListener("pointermove",h),document.addEventListener("pointerup",r,{once:!0})},h=i=>{if(!d)return;const l=i.clientX-u,p=i.clientY-c;let f=m+l,y=g+p;const w=t.offsetWidth,E=t.offsetHeight,v=-w+40,b=window.innerWidth-40,$=-E+40,C=window.innerHeight-40;f=Math.max(v,Math.min(b,f)),y=Math.max($,Math.min(C,y)),t.style.left=`${f}px`,t.style.top=`${y}px`},r=()=>{d=!1,document.body.style.userSelect="",a&&(N.__lastPos={left:t.style.left,top:t.style.top}),document.removeEventListener("pointermove",h)};n.addEventListener("pointerdown",S)}document.addEventListener("DOMContentLoaded",function(){window.addEventListener("comentarioGuardado",e=>{const{salidaId:a,comentario:t}=e.detail,o=window._calendarRef;if(o){const n=o.getEventById(`salida-${a}`);n&&(n.setExtendedProp("comentario",t),n._def&&n._def.extendedProps&&(n._def.extendedProps.comentario=t)),typeof Swal<"u"&&Swal.fire({icon:"success",title:"Comentario guardado",text:"El comentario se ha guardado correctamente",timer:2e3,showConfirmButton:!1,toast:!0,position:"top-end"})}})});function Q(e){var d,u;if(!e)return;const a=new Date(e),t={year:"numeric",month:"long"};let o=a.toLocaleDateString("es-ES",t);o=o.charAt(0).toUpperCase()+o.slice(1);const n=document.querySelector("#resumen-mensual-fecha");n&&(n.textContent=`(${o})`);const s=(u=(d=window.AppSalidas)==null?void 0:d.routes)==null?void 0:u.totales;s&&fetch(`${s}?fecha=${encodeURIComponent(e)}`).then(c=>c.json()).then(c=>{const m=c.semana||{};F("#resumen-semanal-peso",`📦 ${O(m.peso)} kg`),F("#resumen-semanal-longitud",`📏 ${O(m.longitud)} m`),F("#resumen-semanal-diametro",m.diametro!=null&&!isNaN(m.diametro)?`⌀ ${Number(m.diametro).toFixed(2)} mm`:"");const g=c.mes||{};F("#resumen-mensual-peso",`📦 ${O(g.peso)} kg`),F("#resumen-mensual-longitud",`📏 ${O(g.longitud)} m`),F("#resumen-mensual-diametro",g.diametro!=null&&!isNaN(g.diametro)?`⌀ ${Number(g.diametro).toFixed(2)} mm`:"")}).catch(c=>console.error("❌ Error al actualizar los totales:",c))}function O(e){return e!=null?Number(e).toLocaleString():"0"}function F(e,a){const t=document.querySelector(e);t&&(t.textContent=a)}let x=null;function _e(e,a){const t=()=>e&&e.offsetParent!==null&&e.clientWidth>0&&e.clientHeight>=0;if(t())return a();if("IntersectionObserver"in window){const n=new IntersectionObserver(s=>{s.some(u=>u.isIntersecting)&&(n.disconnect(),a())},{root:null,threshold:.01});n.observe(e);return}if("ResizeObserver"in window){const n=new ResizeObserver(()=>{t()&&(n.disconnect(),a())});n.observe(e);return}const o=setInterval(()=>{t()&&(clearInterval(o),a())},100)}function I(){x&&(requestAnimationFrame(()=>{try{x.updateSize()}catch{}}),setTimeout(()=>{try{x.updateSize()}catch{}},150))}function Me(){if(!window.FullCalendar)return console.error("FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."),null;x&&x.destroy();const e=["resourceTimeGridDay","resourceTimelineWeek","dayGridMonth"];let a=localStorage.getItem("ultimaVistaCalendario");e.includes(a)||(a="resourceTimeGridDay");const t=localStorage.getItem("fechaCalendario");let o=null;const n=document.getElementById("calendario");if(!n)return console.error("#calendario no encontrado"),null;function s(m){return x?x.getEvents().some(g=>{var r,i;const S=(g.startStr||((r=g.start)==null?void 0:r.toISOString())||"").split("T")[0];return(((i=g.extendedProps)==null?void 0:i.tipo)==="festivo"||typeof g.id=="string"&&g.id.startsWith("festivo-"))&&S===m}):!1}_e(n,()=>{x=new FullCalendar.Calendar(n,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",navLinks:!0,navLinkDayClick:(r,i)=>{var y;const l=r.getDay(),p=l===0||l===6,f=(y=x==null?void 0:x.view)==null?void 0:y.type;if(p&&(f==="resourceTimelineWeek"||f==="dayGridMonth")){i.preventDefault();let w;f==="dayGridMonth"?w=l===6?"saturday":"sunday":w=r.toISOString().split("T")[0],window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(w)?window.expandedWeekendDays.delete(w):window.expandedWeekendDays.add(w),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),x.render(),setTimeout(()=>{var E;return(E=window.applyWeekendCollapse)==null?void 0:E.call(window)},50);return}x.changeView("resourceTimeGridDay",r)},initialView:a,initialDate:t?new Date(t):void 0,dayMaxEventRows:!1,dayMaxEvents:!1,slotMinTime:"05:00:00",slotMaxTime:"20:00:00",buttonText:{today:"Hoy",resourceTimeGridDay:"Día",resourceTimelineWeek:"Semana",dayGridMonth:"Mes"},progressiveEventRendering:!0,expandRows:!0,height:"auto",events:(r,i,l)=>{var f;const p=r.view&&r.view.type||((f=x==null?void 0:x.view)==null?void 0:f.type)||"resourceTimeGridDay";ce(p,r).then(i).catch(l)},resources:(r,i,l)=>{var f;const p=r.view&&r.view.type||((f=x==null?void 0:x.view)==null?void 0:f.type)||"resourceTimeGridDay";ue(p,r).then(i).catch(l)},headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimeGridDay,resourceTimelineWeek,dayGridMonth"},eventOrderStrict:!0,eventOrder:(r,i)=>{var w,E;const l=((w=r.extendedProps)==null?void 0:w.tipo)==="resumen-dia",p=((E=i.extendedProps)==null?void 0:E.tipo)==="resumen-dia";if(l&&!p)return-1;if(!l&&p)return 1;const f=parseInt(String(r.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0,y=parseInt(String(i.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0;return f-y},datesSet:r=>{try{const i=ze(r);localStorage.setItem("fechaCalendario",i),localStorage.setItem("ultimaVistaCalendario",r.view.type),u(),setTimeout(()=>Q(i),0),clearTimeout(o),o=setTimeout(()=>{x.refetchResources(),x.refetchEvents(),I(),(r.view.type==="resourceTimelineWeek"||r.view.type==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)},0)}catch(i){console.error("Error en datesSet:",i)}},loading:r=>{if(!r&&x){const i=x.view.type;i==="resourceTimeGridDay"&&setTimeout(()=>c(),150),(i==="resourceTimelineWeek"||i==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)}},viewDidMount:r=>{u(),r.view.type==="resourceTimeGridDay"&&setTimeout(()=>c(),100),r.view.type==="dayGridMonth"&&setTimeout(()=>{document.querySelectorAll(".fc-daygrid-event-harness").forEach(i=>{i.querySelector(".evento-resumen-diario")||(i.style.setProperty("width","100%","important"),i.style.setProperty("max-width","100%","important"),i.style.setProperty("position","static","important"),i.style.setProperty("left","unset","important"),i.style.setProperty("right","unset","important"),i.style.setProperty("top","unset","important"),i.style.setProperty("inset","unset","important"),i.style.setProperty("margin","0 0 2px 0","important"))}),document.querySelectorAll(".fc-daygrid-event:not(.evento-resumen-diario)").forEach(i=>{i.style.setProperty("width","100%","important"),i.style.setProperty("max-width","100%","important"),i.style.setProperty("margin","0","important"),i.style.setProperty("position","static","important"),i.style.setProperty("left","unset","important"),i.style.setProperty("right","unset","important"),i.style.setProperty("inset","unset","important")})},50)},eventContent:r=>{var y;const i=r.event.backgroundColor||"#9CA3AF",l=r.event.extendedProps||{},p=(y=x==null?void 0:x.view)==null?void 0:y.type;if(l.tipo==="resumen-dia"){const w=Number(l.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),E=Number(l.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),v=l.diametroMedio?Number(l.diametroMedio).toFixed(1):null;if(p==="resourceTimelineWeek")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${w} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${E} m</div>
                                ${v?`<div class="text-yellow-800">⌀ ${v} mm</div>`:""}
                            </div>
                        `};if(p==="dayGridMonth")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${w} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${E} m</div>
                                ${v?`<div class="text-yellow-800">⌀ ${v} mm</div>`:""}
                            </div>
                        `}}let f=`
        <div style="background-color:${i}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${r.event.title}</div>
    `;if(l.tipo==="planilla"){const w=l.pesoTotal!=null?`📦 ${Number(l.pesoTotal).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2})} kg`:null,E=l.longitudTotal!=null?`📏 ${Number(l.longitudTotal).toLocaleString()} m`:null,v=l.diametroMedio!=null?`⌀ ${Number(l.diametroMedio).toFixed(2)} mm`:null,b=[w,E,v].filter(Boolean);b.length>0&&(f+=`<div class="text-sm text-black font-semibold">${b.join(" | ")}</div>`),l.tieneSalidas&&Array.isArray(l.salidas_codigos)&&l.salidas_codigos.length>0&&(f+=`
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${l.salidas_codigos.join(", ")}
                </span>
            </div>`)}return f+="</div>",{html:f}},eventDidMount:function(r){var f,y,w,E;const i=r.event.extendedProps||{};if(i.tipo==="resumen-dia"){r.el.classList.add("evento-resumen-diario"),r.el.style.cursor="default";return}if(r.view.type==="dayGridMonth"){const v=r.el.closest(".fc-daygrid-event-harness");v&&(v.style.setProperty("width","100%","important"),v.style.setProperty("max-width","100%","important"),v.style.setProperty("min-width","100%","important"),v.style.setProperty("position","static","important"),v.style.setProperty("left","unset","important"),v.style.setProperty("right","unset","important"),v.style.setProperty("top","unset","important"),v.style.setProperty("inset","unset","important"),v.style.setProperty("margin","0 0 2px 0","important"),v.style.setProperty("display","block","important")),r.el.style.setProperty("width","100%","important"),r.el.style.setProperty("max-width","100%","important"),r.el.style.setProperty("min-width","100%","important"),r.el.style.setProperty("margin","0","important"),r.el.style.setProperty("position","static","important"),r.el.style.setProperty("left","unset","important"),r.el.style.setProperty("right","unset","important"),r.el.style.setProperty("inset","unset","important"),r.el.style.setProperty("display","block","important"),r.el.querySelectorAll("*").forEach(b=>{b.style.setProperty("width","100%","important"),b.style.setProperty("max-width","100%","important")})}const l=(((f=document.getElementById("filtro-obra"))==null?void 0:f.value)||"").trim().toLowerCase(),p=(((y=document.getElementById("filtro-nombre-obra"))==null?void 0:y.value)||"").trim().toLowerCase();if(l||p){let v=!1;if(i.tipo==="salida"&&i.obras&&Array.isArray(i.obras))v=i.obras.some(b=>{const $=(b.codigo||"").toString().toLowerCase(),C=(b.nombre||"").toString().toLowerCase();return l&&$.includes(l)||p&&C.includes(p)});else{const b=(((w=r.event.extendedProps)==null?void 0:w.cod_obra)||"").toString().toLowerCase(),$=(((E=r.event.extendedProps)==null?void 0:E.nombre_obra)||r.event.title||"").toString().toLowerCase();v=l&&b.includes(l)||p&&$.includes(p)}if(v){r.el.classList.add("evento-filtrado");const b="#1f2937",$="#111827";r.el.style.setProperty("background-color",b,"important"),r.el.style.setProperty("background",b,"important"),r.el.style.setProperty("border-color",$,"important"),r.el.style.setProperty("color","white","important"),r.el.querySelectorAll("*").forEach(C=>{C.style.setProperty("background-color",b,"important"),C.style.setProperty("background",b,"important"),C.style.setProperty("color","white","important")})}}typeof Y=="function"&&Y(r),typeof Z=="function"&&Z(r,x)},eventAllow:(r,i)=>{var p;const l=(p=i.extendedProps)==null?void 0:p.tipo;return!(l==="resumen-dia"||l==="festivo")},eventDragStart:()=>{const r=()=>{document.querySelectorAll(".fc-event-dragging").forEach(i=>{i.style.width="150px",i.style.maxWidth="150px",i.style.minWidth="150px",i.style.height="80px",i.style.maxHeight="80px",i.style.overflow="hidden"}),window._isDragging&&requestAnimationFrame(r)};window._isDragging=!0,requestAnimationFrame(r)},eventDragStop:()=>{window._isDragging=!1},eventDrop:r=>{var w,E,v,b;const i=r.event.extendedProps||{},l=r.event.id,p=(w=r.event.start)==null?void 0:w.toISOString(),f={fecha:p,tipo:i.tipo,planillas_ids:i.planillas_ids||[],elementos_ids:i.elementos_ids||[]},y=(((v=(E=window.AppSalidas)==null?void 0:E.routes)==null?void 0:v.updateItem)||"").replace("__ID__",l);fetch(y,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(b=window.AppSalidas)==null?void 0:b.csrf},body:JSON.stringify(f)}).then($=>{if(!$.ok)throw new Error("No se pudo actualizar la fecha.");return $.json()}).then($=>{x.refetchEvents(),x.refetchResources();const H=r.event.start.toISOString().split("T")[0];Q(H),I(),$.alerta_retraso&&Swal.fire({icon:"warning",title:"⚠️ Fecha de entrega adelantada",html:`
                                    <div class="text-left">
                                        <p class="mb-2">${$.alerta_retraso.mensaje}</p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fin fabricación:</strong> ${$.alerta_retraso.fin_programado}</p>
                                            <p class="text-sm"><strong>Fecha entrega:</strong> ${$.alerta_retraso.fecha_entrega}</p>
                                        </div>
                                        <p class="mt-3 text-sm text-gray-600">Los elementos no estarán listos para la fecha indicada según la programación actual de máquinas.</p>
                                    </div>
                                `,showCancelButton:!0,confirmButtonText:"🚀 Adelantar fabricación",cancelButtonText:"Entendido",confirmButtonColor:"#10b981",cancelButtonColor:"#f59e0b"}).then(G=>{G.isConfirmed&&Ne(i.elementos_ids,p)})}).catch($=>{console.error("Error:",$),r.revert()})},dateClick:r=>{s(r.dateStr)&&Swal.fire({icon:"info",title:"📅 Día festivo",text:"Los festivos se editan en la planificación de Trabajadores.",confirmButtonText:"Entendido"})},eventMinHeight:30,firstDay:1,slotLabelContent:r=>{var v,b;if(((v=x==null?void 0:x.view)==null?void 0:v.type)!=="resourceTimelineWeek")return null;const l=r.date;if(!l)return null;const p=l.getDay(),f=p===0||p===6,y=l.toISOString().split("T")[0],w={weekday:"short",day:"numeric",month:"short"},E=l.toLocaleDateString("es-ES",w);if(f){const C=!((b=window.expandedWeekendDays)==null?void 0:b.has(y)),H=C?"▶":"▼",G=C?l.toLocaleDateString("es-ES",{weekday:"short"}).substring(0,3):E;return{html:`<div class="weekend-header cursor-pointer select-none hover:bg-gray-200 px-1 rounded"
                                    data-date="${y}"
                                    data-collapsed="${C}"
                                    title="${C?"Clic para expandir":"Clic para colapsar"}">
                                <span class="collapse-icon text-xs mr-1">${H}</span>
                                <span class="weekend-label">${G}</span>
                               </div>`}}return{html:`<span>${E}</span>`}},views:{resourceTimelineWeek:{slotDuration:{days:1}},resourceTimeGridDay:{slotDuration:"01:00:00",slotLabelFormat:{hour:"2-digit",minute:"2-digit",hour12:!1},slotLabelInterval:"01:00:00",allDaySlot:!1}},editable:!0,eventDurationEditable:!1,eventStartEditable:!0,resourceAreaColumns:[{field:"cod_obra",headerContent:"Código"},{field:"title",headerContent:"Obra"},{field:"cliente",headerContent:"Cliente"}],resourceAreaHeaderContent:"Obras",resourceOrder:"orderIndex",resourceLabelContent:r=>({html:`<div class="text-xs font-semibold">
                        <div class="text-blue-600">${r.resource.extendedProps.cod_obra||""}</div>
                        <div class="text-gray-700 truncate">${r.resource.title||""}</div>
                        <div class="text-gray-500 text-[10px] truncate">${r.resource.extendedProps.cliente||""}</div>
                    </div>`}),windowResize:()=>I()}),x.render(),I();const m=localStorage.getItem("expandedWeekendDays");window.expandedWeekendDays=new Set(m?JSON.parse(m):[]),window.weekendDefaultCollapsed=!0;function g(r){const l=new Date(r+"T00:00:00").getDay();return l===0||l===6}function S(){var i,l,p;const r=(i=x==null?void 0:x.view)==null?void 0:i.type;if(r==="resourceTimelineWeek"&&(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(w=>{var v;const E=w.getAttribute("data-date");g(E)&&(((v=window.expandedWeekendDays)==null?void 0:v.has(E))?w.classList.remove("weekend-collapsed"):w.classList.add("weekend-collapsed"))}),document.querySelectorAll(".fc-timeline-lane td[data-date]").forEach(w=>{var v;const E=w.getAttribute("data-date");g(E)&&(((v=window.expandedWeekendDays)==null?void 0:v.has(E))?w.classList.remove("weekend-collapsed"):w.classList.add("weekend-collapsed"))})),r==="dayGridMonth"){const f=(l=window.expandedWeekendDays)==null?void 0:l.has("saturday"),y=(p=window.expandedWeekendDays)==null?void 0:p.has("sunday");console.log("applyWeekendCollapse - satExpanded:",f,"sunExpanded:",y);const w=document.querySelectorAll(".fc-col-header-cell.fc-day-sat"),E=document.querySelectorAll(".fc-col-header-cell.fc-day-sun");console.log("Headers encontrados - sat:",w.length,"sun:",E.length),w.forEach(b=>{f?b.classList.remove("weekend-day-collapsed"):b.classList.add("weekend-day-collapsed"),console.log("Header sat después:",b.classList.contains("weekend-day-collapsed"))}),E.forEach(b=>{y?b.classList.remove("weekend-day-collapsed"):b.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sat").forEach(b=>{f?b.classList.remove("weekend-day-collapsed"):b.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sun").forEach(b=>{y?b.classList.remove("weekend-day-collapsed"):b.classList.add("weekend-day-collapsed")});const v=document.querySelector(".fc-dayGridMonth-view table");if(v){let b=v.querySelector("colgroup");if(!b){b=document.createElement("colgroup");for(let C=0;C<7;C++)b.appendChild(document.createElement("col"));v.insertBefore(b,v.firstChild)}const $=b.querySelectorAll("col");$.length>=7&&($[5].style.width=f?"":"40px",$[6].style.width=y?"":"40px")}}}function h(r){console.log("toggleWeekendCollapse llamado con key:",r),console.log("expandedWeekendDays antes:",[...window.expandedWeekendDays||[]]),window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(r)?(window.expandedWeekendDays.delete(r),console.log("Colapsando:",r)):(window.expandedWeekendDays.add(r),console.log("Expandiendo:",r)),console.log("expandedWeekendDays después:",[...window.expandedWeekendDays]),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),S()}n.addEventListener("click",r=>{var p;console.log("Click detectado en:",r.target);const i=r.target.closest(".weekend-header");if(i){const f=i.getAttribute("data-date");if(console.log("Click en weekend-header, dateStr:",f),f){r.preventDefault(),r.stopPropagation(),h(f);return}}const l=(p=x==null?void 0:x.view)==null?void 0:p.type;if(console.log("Vista actual:",l),l==="dayGridMonth"){const f=r.target.closest(".fc-col-header-cell.fc-day-sat, .fc-col-header-cell.fc-day-sun");if(console.log("Header cell encontrado:",f),f){r.preventDefault(),r.stopPropagation();const E=f.classList.contains("fc-day-sat")?"saturday":"sunday";console.log("Toggling:",E),h(E);return}const y=r.target.closest(".fc-daygrid-day.fc-day-sat, .fc-daygrid-day.fc-day-sun");if(console.log("Day cell encontrado:",y),y&&!r.target.closest(".fc-event")){r.preventDefault(),r.stopPropagation();const E=y.classList.contains("fc-day-sat")?"saturday":"sunday";console.log("Toggling day:",E),h(E);return}}},!0),setTimeout(()=>S(),100),window.applyWeekendCollapse=S,n.addEventListener("contextmenu",r=>{const i=r.target.closest(".fc-daygrid-day, .fc-timeline-slot, .fc-timegrid-slot, .fc-col-header-cell");if(i){let l=i.getAttribute("data-date");if(!l){const p=r.target.closest("[data-date]");p&&(l=p.getAttribute("data-date"))}if(l&&x){const p=x.view.type;(p==="resourceTimelineWeek"||p==="dayGridMonth")&&(r.preventDefault(),r.stopPropagation(),Swal.fire({title:"📅 Ir a día",text:`¿Quieres ver el día ${l}?`,icon:"question",showCancelButton:!0,confirmButtonText:"Sí, ir al día",cancelButtonText:"Cancelar"}).then(f=>{f.isConfirmed&&(x.changeView("resourceTimeGridDay",l),I())}))}}})}),window.addEventListener("shown.bs.tab",I),window.addEventListener("shown.bs.collapse",I),window.addEventListener("shown.bs.modal",I);function u(){document.querySelectorAll(".resumen-diario-custom").forEach(g=>g.remove())}function c(){if(!x||x.view.type!=="resourceTimeGridDay"){u();return}u();const m=x.getDate(),g=m.getFullYear(),S=String(m.getMonth()+1).padStart(2,"0"),h=String(m.getDate()).padStart(2,"0"),r=`${g}-${S}-${h}`,i=x.getEvents().find(l=>{var p,f;return((p=l.extendedProps)==null?void 0:p.tipo)==="resumen-dia"&&((f=l.extendedProps)==null?void 0:f.fecha)===r});if(i&&i.extendedProps){const l=Number(i.extendedProps.pesoTotal||0).toLocaleString(),p=Number(i.extendedProps.longitudTotal||0).toLocaleString(),f=i.extendedProps.diametroMedio?Number(i.extendedProps.diametroMedio).toFixed(2):null,y=document.createElement("div");y.className="resumen-diario-custom",y.innerHTML=`
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 Peso: ${l} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${p} m</div>
                        ${f?`<div class="text-yellow-800">⌀ Diámetro: ${f} mm</div>`:""}
                    </div>
                </div>
            `,n&&n.parentNode&&n.parentNode.insertBefore(y,n)}}return window.mostrarResumenDiario=c,window.limpiarResumenesCustom=u,x}function ze(e){if(e.view.type==="dayGridMonth"){const a=new Date(e.start);return a.setDate(a.getDate()+15),a.toISOString().split("T")[0]}if(e.view.type==="resourceTimeGridWeek"||e.view.type==="resourceTimelineWeek"){const a=new Date(e.start),t=Math.floor((e.end-e.start)/(1e3*60*60*24)/2);return a.setDate(a.getDate()+t),a.toISOString().split("T")[0]}return e.startStr.split("T")[0]}function Ne(e,a){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para adelantar"});return}Swal.fire({title:"Analizando opciones...",html:"Calculando la mejor posición para adelantar la fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}}),fetch("/planificacion/simular-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({elementos_ids:e,fecha_entrega:a})}).then(o=>{if(!o.ok)throw new Error("Error en la simulación");return o.json()}).then(o=>{if(!o.necesita_adelanto){Swal.fire({icon:"info",title:"No es necesario adelantar",text:o.mensaje||"Los elementos llegarán a tiempo."});return}let n="";o.ordenes_a_adelantar&&o.ordenes_a_adelantar.length>0&&(n=`
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
                `,o.ordenes_a_adelantar.forEach(u=>{n+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${u.planilla_codigo}</td>
                            <td class="px-2 py-1">${u.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${u.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${u.posicion_nueva}</td>
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
                `,o.colaterales.forEach(u=>{s+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${u.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${u.obra}</td>
                            <td class="px-2 py-1">${u.fecha_entrega}</td>
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
                `,width:600,showCancelButton:!0,confirmButtonText:"✅ Ejecutar adelanto",cancelButtonText:"Cancelar",confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280"}).then(u=>{u.isConfirmed&&Be(o.ordenes_a_adelantar)})}).catch(o=>{console.error("Error en simulación:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo simular el adelanto. "+o.message})})}function Be(e){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay órdenes para adelantar"});return}Swal.fire({title:"Ejecutando adelanto...",html:"Actualizando posiciones en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const a=e.map(o=>({planilla_id:o.planilla_id,maquina_id:o.maquina_id,posicion_nueva:o.posicion_nueva}));fetch("/planificacion/ejecutar-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({ordenes:a})}).then(o=>{if(!o.ok)throw new Error("Error al ejecutar el adelanto");return o.json()}).then(o=>{o.success?Swal.fire({icon:"success",title:"¡Adelanto ejecutado!",text:o.mensaje||"Las posiciones han sido actualizadas correctamente.",confirmButtonColor:"#10b981"}).then(()=>{x&&(x.refetchEvents(),x.refetchResources())}):Swal.fire({icon:"error",title:"Error",text:o.mensaje||"No se pudo ejecutar el adelanto."})}).catch(o=>{console.error("Error al ejecutar adelanto:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el adelanto. "+o.message})})}function je(e,a={}){const{selector:t=null,once:o=!1}=a;let n=!1;const s=()=>{t&&!document.querySelector(t)||o&&n||(n=!0,e())};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",s):s(),document.addEventListener("livewire:navigated",s)}function Oe(e){document.addEventListener("livewire:navigating",e)}function We(e){let t=new Date(e).toLocaleDateString("es-ES",{month:"long",year:"numeric"});return`(${t.charAt(0).toUpperCase()+t.slice(1)})`}function Re(e){const a=new Date(e),t=a.getDay(),o=t===0?-6:1-t,n=new Date(a);n.setDate(a.getDate()+o);const s=new Date(n);s.setDate(n.getDate()+6);const d=new Intl.DateTimeFormat("es-ES",{day:"2-digit",month:"short"}),u=new Intl.DateTimeFormat("es-ES",{year:"numeric"});return`(${d.format(n)} – ${d.format(s)} ${u.format(s)})`}function He(e){const a=document.querySelector("#resumen-semanal-fecha"),t=document.querySelector("#resumen-mensual-fecha");a&&(a.textContent=Re(e)),t&&(t.textContent=We(e));const o=`${window.AppSalidas.routes.totales}?fecha=${encodeURIComponent(e)}`;fetch(o).then(n=>n.json()).then(n=>{const s=n.semana||{},d=n.mes||{};document.querySelector("#resumen-semanal-peso").textContent=`📦 ${Number(s.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-semanal-longitud").textContent=`📏 ${Number(s.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-semanal-diametro").textContent=s.diametro!=null?`⌀ ${Number(s.diametro).toFixed(2)} mm`:"",document.querySelector("#resumen-mensual-peso").textContent=`📦 ${Number(d.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-mensual-longitud").textContent=`📏 ${Number(d.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-mensual-diametro").textContent=d.diametro!=null?`⌀ ${Number(d.diametro).toFixed(2)} mm`:""}).catch(n=>console.error("❌ Totales:",n))}let _;function Ge(){var i,l;if(window.calendar)try{window.calendar.destroy()}catch(p){console.warn("Error al destruir calendario anterior:",p)}const e=Me();_=e,window.calendar=e,e.refetchResources(),e.refetchEvents(),(i=document.getElementById("ver-con-salidas"))==null||i.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()}),(l=document.getElementById("ver-todas"))==null||l.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()});const t=(localStorage.getItem("fechaCalendario")||new Date().toISOString()).split("T")[0];He(t);const o=localStorage.getItem("soloSalidas")==="true",n=localStorage.getItem("soloPlanillas")==="true",s=document.getElementById("solo-salidas"),d=document.getElementById("solo-planillas");s&&(s.checked=o),d&&(d.checked=n);const u=document.getElementById("filtro-obra"),c=document.getElementById("filtro-nombre-obra"),m=document.getElementById("btn-reset-filtros"),g=document.getElementById("btn-limpiar-filtros");m==null||m.addEventListener("click",()=>{u&&(u.value=""),c&&(c.value=""),s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),d&&(d.checked=!1,localStorage.setItem("soloPlanillas","false")),r(),_.refetchEvents()});const h=((p,f=150)=>{let y;return(...w)=>{clearTimeout(y),y=setTimeout(()=>p(...w),f)}})(()=>{_.refetchEvents()},120);u==null||u.addEventListener("input",h),c==null||c.addEventListener("input",h);function r(){const p=s==null?void 0:s.closest(".checkbox-container"),f=d==null?void 0:d.closest(".checkbox-container");p==null||p.classList.remove("active-salidas"),f==null||f.classList.remove("active-planillas"),s!=null&&s.checked&&(p==null||p.classList.add("active-salidas")),d!=null&&d.checked&&(f==null||f.classList.add("active-planillas"))}s==null||s.addEventListener("change",p=>{p.target.checked&&d&&(d.checked=!1,localStorage.setItem("soloPlanillas","false")),localStorage.setItem("soloSalidas",p.target.checked.toString()),r(),_.refetchEvents()}),d==null||d.addEventListener("change",p=>{p.target.checked&&s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),localStorage.setItem("soloPlanillas",p.target.checked.toString()),r(),_.refetchEvents()}),r(),g==null||g.addEventListener("click",()=>{u&&(u.value=""),c&&(c.value=""),_.refetchEvents()})}let L=null,z=null,P="days",D=-1,T=[];function Ve(){z&&z();const e=window.calendar;if(!e)return;L=e.getDate(),P="days",D=-1,B();function a(t){const o=t.target.tagName.toLowerCase();if(o==="input"||o==="textarea"||t.target.isContentEditable||document.querySelector(".swal2-container")||!window.calendar||!L)return;let s=!1;if(t.key==="Tab"&&!t.ctrlKey&&!t.metaKey){t.preventDefault(),Ke();return}if(t.key==="Escape"&&P==="events"){t.preventDefault(),P="days",D=-1,R(),B(),j();return}P==="events"?s=Ue(t):s=Ye(t),s&&(t.preventDefault(),t.stopPropagation())}document.addEventListener("keydown",a,!0),e.on("eventsSet",()=>{P==="events"&&(ie(),M())}),z=()=>{document.removeEventListener("keydown",a,!0),de(),R()}}function Ke(){P==="days"?(P="events",ie(),T.length>0?(D=0,M()):(P="days",Qe())):(P="days",D=-1,R(),B()),j()}function ie(){const e=window.calendar;if(!e){T=[];return}T=e.getEvents().filter(a=>{var o;const t=(o=a.extendedProps)==null?void 0:o.tipo;return t!=="resumen-dia"&&t!=="festivo"}).sort((a,t)=>{const o=a.start||new Date(0),n=t.start||new Date(0);return o<n?-1:o>n?1:(a.title||"").localeCompare(t.title||"")})}function Ue(e){if(T.length===0)return!1;let a=!1;switch(e.key){case"ArrowDown":case"ArrowRight":D=(D+1)%T.length,M(),a=!0;break;case"ArrowUp":case"ArrowLeft":D=D<=0?T.length-1:D-1,M(),a=!0;break;case"Home":D=0,M(),a=!0;break;case"End":D=T.length-1,M(),a=!0;break;case"Enter":Je(),a=!0;break;case"e":case"E":Xe(),a=!0;break;case"i":case"I":Ze(),a=!0;break}return a}function Ye(e){const a=window.calendar,t=new Date(L);let o=!1;switch(e.key){case"ArrowLeft":t.setDate(t.getDate()-1),o=!0;break;case"ArrowRight":t.setDate(t.getDate()+1),o=!0;break;case"ArrowUp":t.setDate(t.getDate()-7),o=!0;break;case"ArrowDown":t.setDate(t.getDate()+7),o=!0;break;case"Home":t.setDate(1),o=!0;break;case"End":t.setMonth(t.getMonth()+1),t.setDate(0),o=!0;break;case"PageUp":t.setMonth(t.getMonth()-1),o=!0;break;case"PageDown":t.setMonth(t.getMonth()+1),o=!0;break;case"Enter":const n=le(L),s=a.view.type;s==="dayGridMonth"||s==="resourceTimelineWeek"?a.changeView("resourceTimeGridDay",n):a.gotoDate(L),o=!0;break;case"t":case"T":!e.ctrlKey&&!e.metaKey&&(L=new Date,a.today(),B(),o=!0);break}if(o&&e.key!=="Enter"&&e.key!=="t"&&e.key!=="T"){L=t;const n=a.view;(t<n.currentStart||t>=n.currentEnd)&&a.gotoDate(t),B()}return o}function M(){var t;if(R(),D<0||D>=T.length)return;const e=T[D];if(!e)return;const a=document.querySelector(`[data-event-id="${e.id}"]`)||document.querySelector(`.fc-event[data-event="${e.id}"]`);if(a)a.classList.add("keyboard-focused-event"),a.scrollIntoView({behavior:"smooth",block:"nearest"});else{const o=document.querySelectorAll(".fc-event");for(const n of o)if(n.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){n.classList.add("keyboard-focused-event"),n.scrollIntoView({behavior:"smooth",block:"nearest"});break}}e.start&&(L=new Date(e.start)),j()}function R(){document.querySelectorAll(".keyboard-focused-event").forEach(e=>{e.classList.remove("keyboard-focused-event")})}function Je(){if(D<0||D>=T.length)return;const e=T[D];if(!e)return;const a=e.extendedProps||{},t=window.calendar;if(a.tipo==="salida"){const o=a.salida_id||e.id;te(o,t)}else if(a.tipo==="planilla"){const o=a.planillas_ids||[];o.length>0&&(window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${o.join(",")}`)}}function Xe(){var t;if(D<0||D>=T.length)return;const e=T[D];if(!e)return;const a=document.querySelectorAll(".fc-event");for(const o of a)if(o.classList.contains("keyboard-focused-event")||o.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){const n=o.getBoundingClientRect(),s=new MouseEvent("contextmenu",{bubbles:!0,cancelable:!0,clientX:n.left+n.width/2,clientY:n.top+n.height/2});o.dispatchEvent(s);break}}function Ze(){if(D<0||D>=T.length)return;const e=T[D];if(!e)return;const a=e.extendedProps||{};let t=`<strong>${e.title}</strong><br><br>`;a.tipo==="salida"?(t+="<b>Tipo:</b> Salida<br>",a.obras&&a.obras.length>0&&(t+=`<b>Obras:</b> ${a.obras.map(o=>o.nombre).join(", ")}<br>`)):a.tipo==="planilla"&&(t+="<b>Tipo:</b> Planilla<br>",a.cod_obra&&(t+=`<b>Código:</b> ${a.cod_obra}<br>`),a.pesoTotal&&(t+=`<b>Peso:</b> ${Number(a.pesoTotal).toLocaleString()} kg<br>`),a.longitudTotal&&(t+=`<b>Longitud:</b> ${Number(a.longitudTotal).toLocaleString()} m<br>`)),e.start&&(t+=`<b>Fecha:</b> ${e.start.toLocaleDateString("es-ES",{weekday:"long",day:"numeric",month:"long",year:"numeric"})}<br>`),Swal.fire({title:"Información del evento",html:t,icon:"info",confirmButtonText:"Cerrar"})}function Qe(){const e=document.getElementById("keyboard-nav-indicator");if(e){const a=document.getElementById("keyboard-nav-date");a&&(a.innerHTML='<span class="text-yellow-400">No hay eventos visibles</span>'),clearTimeout(e._hideTimeout),e.style.display="flex",e._hideTimeout=setTimeout(()=>{j()},2e3)}}function le(e){const a=e.getFullYear(),t=String(e.getMonth()+1).padStart(2,"0"),o=String(e.getDate()).padStart(2,"0");return`${a}-${t}-${o}`}function B(){if(de(),!L)return;const e=le(L),a=window.calendar;if(!a)return;const t=a.view.type;let o=null;t==="dayGridMonth"?o=document.querySelector(`.fc-daygrid-day[data-date="${e}"]`):t==="resourceTimelineWeek"?(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(s=>{s.dataset.date&&s.dataset.date.startsWith(e)&&(o=s)}),o||(o=document.querySelector(`.fc-timeline-slot-lane[data-date^="${e}"]`))):t==="resourceTimeGridDay"&&(o=document.querySelector(".fc-col-header-cell")),o&&(o.classList.add("keyboard-focused-day"),o.scrollIntoView({behavior:"smooth",block:"nearest",inline:"nearest"})),j()}function de(){document.querySelectorAll(".keyboard-focused-day").forEach(e=>{e.classList.remove("keyboard-focused-day")})}function j(){let e=document.getElementById("keyboard-nav-indicator");if(e||(e=document.createElement("div"),e.id="keyboard-nav-indicator",e.className="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm",document.body.appendChild(e)),P==="events"){const a=T[D],t=(a==null?void 0:a.title)||"Sin evento",o=`${D+1}/${T.length}`;e.innerHTML=`
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
        `}else{const a=L?L.toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short",year:"numeric"}):"";e.innerHTML=`
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
        `}clearTimeout(e._hideTimeout),e.style.display="block",e._hideTimeout=setTimeout(()=>{e.style.display="none"},4e3)}function et(){if(document.getElementById("keyboard-nav-styles"))return;const e=document.createElement("style");e.id="keyboard-nav-styles",e.textContent=`
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
    `,document.head.appendChild(e)}je(()=>{Ge(),et(),setTimeout(()=>{Ve()},500)},{selector:'#calendario[data-calendar-type="salidas"]'});Oe(()=>{if(z&&(z(),z=null),window.calendar)try{window.calendar.destroy(),window.calendar=null}catch(a){console.warn("Error al limpiar calendario de salidas:",a)}const e=document.getElementById("keyboard-nav-indicator");e&&e.remove()});
