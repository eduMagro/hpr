async function ce(e,a){var t,o,n,r;try{const d=(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.planificacion;if(!d)return[];const m=new URLSearchParams({tipo:"events",viewType:e||"",start:a.startStr||"",end:a.endStr||"",t:Date.now()}),c=await fetch(`${d}?${m.toString()}`);if(!c.ok)return console.error("Error eventos",c.status),[];const p=await c.json();let y=Array.isArray(p)?p:Array.isArray(p==null?void 0:p.events)?p.events:[];const v=((n=document.getElementById("solo-salidas"))==null?void 0:n.checked)||!1,b=((r=document.getElementById("solo-planillas"))==null?void 0:r.checked)||!1,s=y.filter(f=>{var u;return((u=f.extendedProps)==null?void 0:u.tipo)==="resumen-dia"}),i=y.filter(f=>{var u;return((u=f.extendedProps)==null?void 0:u.tipo)!=="resumen-dia"});let l=i;return v&&!b?l=i.filter(f=>{var g;return((g=f.extendedProps)==null?void 0:g.tipo)==="salida"}):b&&!v&&(l=i.filter(f=>{var g;const u=(g=f.extendedProps)==null?void 0:g.tipo;return u==="planilla"||u==="festivo"})),[...l,...s]}catch(d){return console.error("fetch eventos falló:",d),[]}}async function ue(e,a){var d,m;const t=(m=(d=window.AppSalidas)==null?void 0:d.routes)==null?void 0:m.planificacion;if(!t)return[];const o=new URLSearchParams({tipo:"resources",viewType:e,start:a.startStr||"",end:a.endStr||""}),n=await fetch(`${t}?${o.toString()}`,{method:"GET"});if(!n.ok)throw new Error("Error cargando recursos");const r=await n.json();return Array.isArray(r)?r:Array.isArray(r==null?void 0:r.resources)?r.resources:[]}function K(e,a){const t=e.event.extendedProps||{};if(t.tipo!=="festivo"){if(t.tipo==="planilla"){const o=`
      ✅ Fabricados: ${R(t.fabricadosKg)} kg<br>
      🔄 Fabricando: ${R(t.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${R(t.pendientesKg)} kg
    `;tippy(e.el,{content:o,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}t.tipo==="salida"&&t.comentario&&t.comentario.trim()&&tippy(e.el,{content:t.comentario,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}}function R(e){return e!=null?Number(e).toLocaleString():0}let j=null;function q(){j&&(j.remove(),j=null,document.removeEventListener("click",q),document.removeEventListener("contextmenu",q,!0),document.removeEventListener("scroll",q,!0),window.removeEventListener("resize",q),window.removeEventListener("keydown",Z))}function Z(e){e.key==="Escape"&&q()}function pe(e,a,t){q();const o=document.createElement("div");o.className="fc-contextmenu",Object.assign(o.style,{position:"fixed",top:a+"px",left:e+"px",zIndex:99999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",fontFamily:"system-ui, -apple-system, Segoe UI, Roboto, sans-serif"}),o.innerHTML=t,document.body.appendChild(o),j=o;const n=o.getBoundingClientRect(),r=Math.max(0,n.right-window.innerWidth+8),d=Math.max(0,n.bottom-window.innerHeight+8);return(r||d)&&(o.style.left=Math.max(8,e-r)+"px",o.style.top=Math.max(8,a-d)+"px"),setTimeout(()=>{document.addEventListener("click",q),document.addEventListener("contextmenu",q,!0),document.addEventListener("scroll",q,!0),window.addEventListener("resize",q),window.addEventListener("keydown",Z)},0),o}function me(e,a,{headerHtml:t="",items:o=[]}={}){const n=`
    <div class="ctx-menu-container">
      ${t?`<div class="ctx-menu-header">${t}</div>`:""}
      ${o.map((d,m)=>`
        <button type="button"
          class="ctx-menu-item${d.danger?" ctx-menu-danger":""}"
          data-idx="${m}">
          ${d.icon?`<span class="ctx-menu-icon">${d.icon}</span>`:""}
          <span class="ctx-menu-label">${d.label}</span>
        </button>`).join("")}
    </div>
  `,r=pe(e,a,n);return r.querySelectorAll(".ctx-menu-item").forEach(d=>{d.addEventListener("click",async m=>{var y;m.preventDefault(),m.stopPropagation();const c=Number(d.dataset.idx),p=(y=o[c])==null?void 0:y.onClick;q();try{await(p==null?void 0:p())}catch(v){console.error(v)}})}),r}function fe(e){if(!e||typeof e!="string")return"";const a=e.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);if(a){const t=a[1],o=a[2].padStart(2,"0"),n=a[3].padStart(2,"0");return`${t}-${o}-${n}`}return e}(function(){if(document.getElementById("swal-anims"))return;const a=document.createElement("style");a.id="swal-anims",a.textContent=`
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
  `,document.head.appendChild(a)})();function ye(e){const a=e.extendedProps||{};if(Array.isArray(a.planillas_ids)&&a.planillas_ids.length)return a.planillas_ids;const t=(e.id||"").match(/planilla-(\d+)/);return t?[Number(t[1])]:[]}async function Q(e,a){var t,o;try{q()}catch{}if(!e)return Swal.fire("⚠️","ID de salida inválido.","warning");try{const n=await fetch(`${(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.informacionPaquetesSalida}?salida_id=${e}`,{headers:{Accept:"application/json"}});if(!n.ok)throw new Error("Error al cargar información de la salida");const{salida:r,paquetesAsignados:d,paquetesDisponibles:m,paquetesTodos:c,filtros:p}=await n.json();ge(r,d,m,c||[],p||{obras:[],planillas:[],obrasRelacionadas:[]},a)}catch(n){console.error(n),Swal.fire("❌","Error al cargar la información de la salida","error")}}function ge(e,a,t,o,n,r){window._gestionPaquetesData={salida:e,paquetesAsignados:a,paquetesDisponibles:t,paquetesTodos:o,filtros:n,mostrarTodos:!1};const d=be(e,a,t,n);Swal.fire({title:`📦 Gestionar Paquetes - Salida ${e.codigo_salida||e.id}`,html:d,width:Math.min(window.innerWidth*.95,1200),showConfirmButton:!0,showCancelButton:!0,confirmButtonText:"💾 Guardar Cambios",cancelButtonText:"Cancelar",focusConfirm:!1,customClass:{popup:"w-full max-w-screen-xl"},didOpen:()=>{ae(),he(),ke(),setTimeout(()=>{we()},100)},willClose:()=>{E.cleanup&&E.cleanup();const m=document.getElementById("modal-keyboard-indicator");m&&m.remove()},preConfirm:()=>Ee()}).then(async m=>{m.isConfirmed&&m.value&&await $e(e.id,m.value,r)})}function be(e,a,t,o){var p,y;const n=a.reduce((v,b)=>v+(parseFloat(b.peso)||0),0);let r="";e.salida_clientes&&e.salida_clientes.length>0&&(r='<div class="col-span-2"><strong>Obras/Clientes:</strong><br>',e.salida_clientes.forEach(v=>{var l,f,u,g,w;const b=((l=v.obra)==null?void 0:l.obra)||"Obra desconocida",s=(f=v.obra)!=null&&f.cod_obra?`(${v.obra.cod_obra})`:"",i=((u=v.cliente)==null?void 0:u.empresa)||((w=(g=v.obra)==null?void 0:g.cliente)==null?void 0:w.empresa)||"";r+=`<span class="text-xs">• ${b} ${s}`,i&&(r+=` - ${i}`),r+="</span><br>"}),r+="</div>");const d=`
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><strong>Código:</strong> ${e.codigo_salida||"N/A"}</div>
                <div><strong>Código SAGE:</strong> ${e.codigo_sage||"Sin asignar"}</div>
                <div><strong>Fecha salida:</strong> ${new Date(e.fecha_salida).toLocaleString("es-ES")}</div>
                <div><strong>Estado:</strong> ${e.estado||"pendiente"}</div>
                <div><strong>Empresa transporte:</strong> ${((p=e.empresa_transporte)==null?void 0:p.nombre)||"Sin asignar"}</div>
                <div><strong>Camión:</strong> ${((y=e.camion)==null?void 0:y.modelo)||"Sin asignar"}</div>
                ${r}
            </div>
        </div>
    `,m=((o==null?void 0:o.obras)||[]).map(v=>`<option value="${v.id}">${v.cod_obra||""} - ${v.obra||"Sin nombre"}</option>`).join(""),c=((o==null?void 0:o.planillas)||[]).map(v=>`<option value="${v.id}" data-obra-id="${v.obra_id||""}">${v.codigo||"Sin código"}</option>`).join("");return`
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
                        ${V(a)}
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
                                    ${m}
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
                        ${V(t)}
                    </div>
                </div>
            </div>
        </div>
    `}function V(e){return!e||e.length===0?'<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>':e.map(a=>{var t,o,n,r,d,m,c,p,y,v,b,s,i,l,f,u;return`
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${a.id}"
            data-peso="${a.peso||0}"
            data-obra-id="${((o=(t=a.planilla)==null?void 0:t.obra)==null?void 0:o.id)||""}"
            data-obra="${((r=(n=a.planilla)==null?void 0:n.obra)==null?void 0:r.obra)||""}"
            data-planilla-id="${((d=a.planilla)==null?void 0:d.id)||""}"
            data-planilla="${((m=a.planilla)==null?void 0:m.codigo)||""}"
            data-cliente="${((p=(c=a.planilla)==null?void 0:c.cliente)==null?void 0:p.empresa)||""}"
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
                <div>📄 ${((y=a.planilla)==null?void 0:y.codigo)||a.planilla_id}</div>
                <div>🏗️ ${((b=(v=a.planilla)==null?void 0:v.obra)==null?void 0:b.cod_obra)||""} - ${((i=(s=a.planilla)==null?void 0:s.obra)==null?void 0:i.obra)||"N/A"}</div>
                <div>👤 ${((f=(l=a.planilla)==null?void 0:l.cliente)==null?void 0:f.empresa)||"Sin cliente"}</div>
                ${(u=a.nave)!=null&&u.obra?`<div class="text-blue-600 font-medium">📍 ${a.nave.obra}</div>`:""}
            </div>
        </div>
    `}).join("")}async function ve(e){var a;try{const t=document.querySelector(`[data-paquete-id="${e}"]`);let o=null;if(t&&t.dataset.paqueteJson)try{o=JSON.parse(t.dataset.paqueteJson.replace(/&#39;/g,"'"))}catch(c){console.warn("No se pudo parsear JSON del paquete",c)}if(!o){const c=await fetch(`/api/paquetes/${e}/elementos`);c.ok&&(o=await c.json())}if(!o){alert("No se pudo obtener información del paquete");return}const n=[];if(o.etiquetas&&o.etiquetas.length>0&&o.etiquetas.forEach(c=>{c.elementos&&c.elementos.length>0&&c.elementos.forEach(p=>{n.push({id:p.id,dimensiones:p.dimensiones,peso:p.peso,longitud:p.longitud,diametro:p.diametro})})}),n.length===0){alert("Este paquete no tiene elementos para mostrar");return}const r=n.map((c,p)=>`
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">Elemento #${c.id}</span>
                    <span class="text-xs text-gray-500">${p+1} de ${n.length}</span>
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
        `).join(""),d=document.getElementById("modal-elementos-paquete-overlay");d&&d.remove();const m=`
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
        `;document.body.insertAdjacentHTML("beforeend",m),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&n.forEach(c=>{c.dimensiones&&window.dibujarFiguraElemento(`elemento-dibujo-${c.id}`,c.dimensiones,null)})},100)}catch(t){console.error("Error al ver elementos del paquete:",t),alert("Error al cargar los elementos del paquete")}}window.verElementosPaqueteSalida=ve;function he(e){const a=document.getElementById("filtro-obra-modal"),t=document.getElementById("filtro-planilla-modal"),o=document.getElementById("btn-limpiar-filtros-modal");a&&a.addEventListener("change",()=>{U(),H()}),t&&t.addEventListener("change",()=>{H()}),o&&o.addEventListener("click",()=>{a&&(a.value=""),t&&(t.value=""),U(),H()})}function U(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData;if(!a||!t)return;const o=(e==null?void 0:e.value)||"",n=o?t.paquetesTodos:t.paquetesDisponibles,r=new Map;n.forEach(c=>{var p,y,v;if((p=c.planilla)!=null&&p.id){if(o&&String((y=c.planilla.obra)==null?void 0:y.id)!==o)return;r.has(c.planilla.id)||r.set(c.planilla.id,{id:c.planilla.id,codigo:c.planilla.codigo||"Sin código",obra_id:(v=c.planilla.obra)==null?void 0:v.id})}});const d=Array.from(r.values()).sort((c,p)=>(c.codigo||"").localeCompare(p.codigo||"")),m=a.value;a.innerHTML='<option value="">-- Todas las planillas --</option>',d.forEach(c=>{const p=document.createElement("option");p.value=c.id,p.textContent=c.codigo,a.appendChild(p)}),m&&r.has(parseInt(m))?a.value=m:a.value=""}function H(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData,o=(e==null?void 0:e.value)||"",n=(a==null?void 0:a.value)||"",r=document.querySelector('[data-zona="disponibles"]');if(!r||!t)return;const d=document.querySelector('[data-zona="asignados"]'),m=new Set;d&&d.querySelectorAll(".paquete-item-salida").forEach(y=>{m.add(parseInt(y.dataset.paqueteId))});let p=(o?t.paquetesTodos:t.paquetesDisponibles).filter(y=>{var v,b,s;return!(m.has(y.id)||o&&String((b=(v=y.planilla)==null?void 0:v.obra)==null?void 0:b.id)!==o||n&&String((s=y.planilla)==null?void 0:s.id)!==n)});r.innerHTML=V(p),ae(),p.length===0&&(r.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">No hay paquetes que coincidan con el filtro</div>')}let E={zonaActiva:"asignados",indiceFocused:-1,cleanup:null};function we(){E.cleanup&&E.cleanup(),E.zonaActiva="asignados",E.indiceFocused=0,P();function e(a){var v;if(!document.querySelector(".swal2-container"))return;const t=a.target.tagName.toLowerCase(),o=t==="select";if((t==="input"||t==="textarea")&&a.key!=="Escape")return;const r=document.querySelector('[data-zona="asignados"]'),d=document.querySelector('[data-zona="disponibles"]');if(!r||!d)return;const m=E.zonaActiva==="asignados"?r:d,c=Array.from(m.querySelectorAll('.paquete-item-salida:not([style*="display: none"])')),p=c.length;let y=!1;if(!o)switch(a.key){case"ArrowDown":p>0&&(E.indiceFocused=(E.indiceFocused+1)%p,P(),y=!0);break;case"ArrowUp":p>0&&(E.indiceFocused=E.indiceFocused<=0?p-1:E.indiceFocused-1,P(),y=!0);break;case"ArrowLeft":case"ArrowRight":E.zonaActiva=E.zonaActiva==="asignados"?"disponibles":"asignados",E.indiceFocused=0,P(),y=!0;break;case"Tab":a.preventDefault(),E.zonaActiva=E.zonaActiva==="asignados"?"disponibles":"asignados",E.indiceFocused=0,P(),y=!0;break;case"Enter":{if(p>0&&E.indiceFocused>=0){const b=c[E.indiceFocused];if(b){xe(b);const s=Array.from(m.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));E.indiceFocused>=s.length&&(E.indiceFocused=Math.max(0,s.length-1)),P(),y=!0}}break}case"Home":E.indiceFocused=0,P(),y=!0;break;case"End":E.indiceFocused=Math.max(0,p-1),P(),y=!0;break}if(y){a.preventDefault(),a.stopPropagation();return}switch(a.key){case"o":case"O":{const b=document.getElementById("filtro-obra-modal");b&&(b.focus(),y=!0);break}case"p":case"P":{const b=document.getElementById("filtro-planilla-modal");b&&(b.focus(),y=!0);break}case"l":case"L":{const b=document.getElementById("btn-limpiar-filtros-modal");b&&(b.click(),(v=document.activeElement)==null||v.blur(),P(),y=!0);break}case"/":case"f":case"F":{const b=document.getElementById("filtro-obra-modal");b&&(b.focus(),y=!0);break}case"Escape":o&&(document.activeElement.blur(),P(),y=!0);break;case"s":case"S":{if(a.ctrlKey||a.metaKey){const b=document.querySelector(".swal2-confirm");b&&(b.click(),y=!0)}break}}y&&(a.preventDefault(),a.stopPropagation())}document.addEventListener("keydown",e,!0),E.cleanup=()=>{document.removeEventListener("keydown",e,!0),ee()}}function P(){ee();const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;E.zonaActiva==="asignados"?(e.classList.add("zona-activa-keyboard"),a.classList.remove("zona-activa-keyboard")):(a.classList.add("zona-activa-keyboard"),e.classList.remove("zona-activa-keyboard"));const t=E.zonaActiva==="asignados"?e:a,o=Array.from(t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));if(o.length>0&&E.indiceFocused>=0){const n=Math.min(E.indiceFocused,o.length-1),r=o[n];r&&(r.classList.add("paquete-focused-keyboard"),r.scrollIntoView({behavior:"smooth",block:"nearest"}))}Se()}function ee(){document.querySelectorAll(".paquete-focused-keyboard").forEach(e=>{e.classList.remove("paquete-focused-keyboard")}),document.querySelectorAll(".zona-activa-keyboard").forEach(e=>{e.classList.remove("zona-activa-keyboard")})}function xe(e){const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!a||!t)return;const o=e.closest("[data-zona]"),n=o.dataset.zona==="asignados"?t:a,r=n.querySelector(".placeholder-sin-paquetes");if(r&&r.remove(),n.appendChild(e),o.querySelectorAll(".paquete-item-salida").length===0){const m=document.createElement("div");m.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",m.textContent="Sin paquetes",o.appendChild(m)}te(e),oe()}function Se(){let e=document.getElementById("modal-keyboard-indicator");e||(e=document.createElement("div"),e.id="modal-keyboard-indicator",e.className="fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs",document.body.appendChild(e));const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]'),o=(a==null?void 0:a.querySelectorAll(".paquete-item-salida").length)||0,n=(t==null?void 0:t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0,r=E.zonaActiva==="asignados"?`📦 Asignados (${o})`:`📋 Disponibles (${n})`;e.innerHTML=`
        <div class="flex items-center gap-2 mb-2">
            <span class="${E.zonaActiva==="asignados"?"bg-green-500":"bg-gray-500"} text-white text-xs px-2 py-0.5 rounded">${r}</span>
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
    `,clearTimeout(e._checkTimeout),e._checkTimeout=setTimeout(()=>{document.querySelector(".swal2-container")||e.remove()},500)}function ke(){if(document.getElementById("modal-keyboard-styles"))return;const e=document.createElement("style");e.id="modal-keyboard-styles",e.textContent=`
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
    `,document.head.appendChild(e)}function te(e){e.addEventListener("dragstart",a=>{e.style.opacity="0.5",a.dataTransfer.setData("text/plain",e.dataset.paqueteId)}),e.addEventListener("dragend",a=>{e.style.opacity="1"})}function ae(){document.querySelectorAll(".paquete-item-salida").forEach(e=>{te(e)}),document.querySelectorAll(".drop-zone").forEach(e=>{e.addEventListener("dragover",a=>{a.preventDefault();const t=e.dataset.zona;e.style.backgroundColor=t==="asignados"?"#d1fae5":"#e0f2fe"}),e.addEventListener("dragleave",a=>{e.style.backgroundColor=""}),e.addEventListener("drop",a=>{a.preventDefault(),e.style.backgroundColor="";const t=a.dataTransfer.getData("text/plain"),o=document.querySelector(`.paquete-item-salida[data-paquete-id="${t}"]`);if(o){const n=e.querySelector(".placeholder-sin-paquetes");n&&n.remove(),e.appendChild(o),oe()}})})}function oe(){const e=document.querySelector('[data-zona="asignados"]'),a=e==null?void 0:e.querySelectorAll(".paquete-item-salida");let t=0;a==null||a.forEach(n=>{const r=parseFloat(n.dataset.peso)||0;t+=r});const o=document.getElementById("peso-asignados");o&&(o.textContent=`${t.toFixed(2)} kg`)}function Ee(){const e=document.querySelector('[data-zona="asignados"]');return{paquetes_ids:Array.from((e==null?void 0:e.querySelectorAll(".paquete-item-salida"))||[]).map(t=>parseInt(t.dataset.paqueteId))}}async function $e(e,a,t){var o,n,r,d;try{const c=await(await fetch((n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.guardarPaquetesSalida,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(r=window.AppSalidas)==null?void 0:r.csrf},body:JSON.stringify({salida_id:e,paquetes_ids:a.paquetes_ids})})).json();c.success?(await Swal.fire({icon:"success",title:"✅ Cambios Guardados",text:"Los paquetes de la salida se han actualizado correctamente.",timer:2e3}),t&&(t.refetchEvents(),(d=t.refetchResources)==null||d.call(t))):await Swal.fire("⚠️",c.message||"No se pudieron guardar los cambios","warning")}catch(m){console.error(m),Swal.fire("❌","Error al guardar los paquetes","error")}}async function De(e,a,t){try{q()}catch{}window.Livewire.dispatch("abrirComentario",{salidaId:e}),window._calendarRef=t}function Te(e){return e?typeof e=="string"?e.split(",").map(t=>t.trim()).filter(Boolean):Array.from(e).map(t=>typeof t=="object"&&(t==null?void 0:t.id)!=null?t.id:t).map(String).map(t=>t.trim()).filter(Boolean):[]}async function Ce(e){var r,d;const a=(d=(r=window.AppSalidas)==null?void 0:r.routes)==null?void 0:d.informacionPlanillas;if(!a)throw new Error("Ruta 'informacionPlanillas' no configurada");const t=`${a}?ids=${encodeURIComponent(e.join(","))}`,o=await fetch(t,{headers:{Accept:"application/json"}});if(!o.ok){const m=await o.text().catch(()=>"");throw new Error(`GET ${t} -> ${o.status} ${m}`)}const n=await o.json();return Array.isArray(n==null?void 0:n.planillas)?n.planillas:[]}function ne(e){if(!e)return!1;const t=new Date(e+"T00:00:00").getDay();return t===0||t===6}function qe(e){return`
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
          <tbody>${e.map((t,o)=>{var i,l,f;const n=((i=t.obra)==null?void 0:i.codigo)||"",r=((l=t.obra)==null?void 0:l.nombre)||"",d=t.seccion||"",m=t.descripcion||"",c=t.codigo||`Planilla ${t.id}`,p=t.peso_total?parseFloat(t.peso_total).toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})+" kg":"",y=fe(t.fecha_estimada_entrega),v=t.elementos&&t.elementos.length>0,b=((f=t.elementos)==null?void 0:f.length)||0;let s="";return v&&(s=t.elementos.map((u,g)=>{const w=u.fecha_entrega||"",k=u.peso?parseFloat(u.peso).toFixed(2):"-";return`
                    <tr class="elemento-row elemento-planilla-${t.id} bg-gray-50 hidden">
                        <td class="px-2 py-1 text-xs text-gray-400 pl-8">↳ ${u.id}</td>
                        <td class="px-2 py-1 text-xs text-gray-500" colspan="2">Marca: ${u.marca||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">Ø${u.diametro||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${u.longitud||"-"} mm</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${u.barras||"-"} uds</td>
                        <td class="px-2 py-1 text-xs text-right text-gray-500">${k} kg</td>
                        <td class="px-2 py-1">
                            <input type="date" class="swal2-input !m-0 !w-auto !text-xs elemento-fecha"
                                   data-elemento-id="${u.id}"
                                   data-planilla-id="${t.id}"
                                   value="${w}">
                        </td>
                    </tr>`}).join("")),`
<tr class="planilla-row hover:bg-blue-50 cursor-pointer" data-planilla-id="${t.id}" style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${o*18}ms;">
  <td class="px-2 py-1 text-xs">
    ${v?`<button type="button" class="toggle-elementos mr-1 text-blue-500 hover:text-blue-700" data-planilla-id="${t.id}">▶</button>`:""}
    ${t.id}
  </td>
  <td class="px-2 py-1 text-xs">${n}</td>
  <td class="px-2 py-1 text-xs">${r}</td>
  <td class="px-2 py-1 text-xs">${d}</td>
  <td class="px-2 py-1 text-xs">${m}</td>
  <td class="px-2 py-1 text-xs">
    ${c}
    ${v?`<span class="ml-1 text-xs text-gray-400">(${b} elem.)</span>`:""}
  </td>
  <td class="px-2 py-1 text-xs text-right font-medium">${p}</td>
  <td class="px-2 py-1">
    <div class="flex items-center gap-1">
      <input type="date" class="swal2-input !m-0 !w-auto planilla-fecha" data-planilla-id="${t.id}" value="${y}">
      ${v?`<button type="button" class="aplicar-fecha-elementos text-xs bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded" data-planilla-id="${t.id}" title="Aplicar fecha a todos los elementos">↓</button>`:""}
    </div>
  </td>
</tr>
${s}`}).join("")}</tbody>
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
    </div>`}function Le(e){const a={};return document.querySelectorAll('input[type="date"][data-planilla-id]').forEach(o=>{const n=parseInt(o.dataset.planillaId),r=o.value,d=e.find(m=>m.id===n);r&&d&&d.peso_total&&(a[r]||(a[r]={peso:0,planillas:0,esFinDeSemana:ne(r)}),a[r].peso+=parseFloat(d.peso_total),a[r].planillas+=1)}),a}function Y(e){const a=Le(e),t=document.getElementById("resumen-contenido");if(!t)return;const o=Object.keys(a).sort();if(o.length===0){t.innerHTML='<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';return}const n=o.map(m=>{const c=a[m],p=new Date(m+"T00:00:00").toLocaleDateString("es-ES",{weekday:"short",day:"2-digit",month:"2-digit",year:"numeric"}),y=c.peso.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2}),v=c.esFinDeSemana?"bg-orange-100 border-orange-300 text-orange-800":"bg-green-100 border-green-300 text-green-800",b=c.esFinDeSemana?"🏖️":"📦";return`
            <div class="inline-block m-1 px-2 py-1 rounded border ${v}">
                <span class="font-medium">${b} ${p}</span>
                <br>
                <span class="text-xs">${y} kg (${c.planillas} planilla${c.planillas!==1?"s":""})</span>
            </div>
        `}).join(""),r=o.reduce((m,c)=>m+a[c].peso,0),d=o.reduce((m,c)=>m+a[c].planillas,0);t.innerHTML=`
        <div class="mb-2">${n}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${r.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})} kg 
            (${d} planilla${d!==1?"s":""})
        </div>
    `}async function Pe(e){var o,n,r;const a=(n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.actualizarFechasPlanillas;if(!a)throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");const t=await fetch(a,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(r=window.AppSalidas)==null?void 0:r.csrf,Accept:"application/json"},body:JSON.stringify({planillas:e})});if(!t.ok){const d=await t.text().catch(()=>"");throw new Error(`PUT ${a} -> ${t.status} ${d}`)}return t.json().catch(()=>({}))}async function Ae(e,a){var t,o;try{const n=Array.from(new Set(Te(e))).map(Number).filter(Boolean);if(!n.length)return Swal.fire("⚠️","No hay planillas en la agrupación.","warning");const r=await Ce(n);if(!r.length)return Swal.fire("⚠️","No se han encontrado planillas.","warning");const m=`
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `+qe(r),{isConfirmed:c}=await Swal.fire({title:"",html:m,width:Math.min(window.innerWidth*.98,1200),customClass:{popup:"w-full max-w-screen-xl"},showCancelButton:!0,confirmButtonText:"💾 Guardar",cancelButtonText:"Cancelar",focusConfirm:!1,showClass:{popup:"swal-fade-in-zoom"},hideClass:{popup:"swal-fade-out"},didOpen:s=>{var f,u;Ie(s),N("#swal-drag",!1),setTimeout(()=>{const g=Swal.getHtmlContainer().querySelector('input[type="date"]');g==null||g.focus({preventScroll:!0})},120),Swal.getHtmlContainer().querySelectorAll('input[type="date"]').forEach(g=>{g.addEventListener("change",function(){ne(this.value)?this.classList.add("weekend-date"):this.classList.remove("weekend-date"),Y(r)})});const l=Swal.getHtmlContainer();l.querySelectorAll(".toggle-elementos").forEach(g=>{g.addEventListener("click",w=>{w.stopPropagation();const k=g.dataset.planillaId,S=l.querySelectorAll(`.elemento-planilla-${k}`),h=g.textContent==="▼";S.forEach(D=>{D.classList.toggle("hidden",h)}),g.textContent=h?"▶":"▼"})}),(f=l.querySelector("#expandir-todos"))==null||f.addEventListener("click",()=>{l.querySelectorAll(".elemento-row").forEach(g=>g.classList.remove("hidden")),l.querySelectorAll(".toggle-elementos").forEach(g=>g.textContent="▼")}),(u=l.querySelector("#colapsar-todos"))==null||u.addEventListener("click",()=>{l.querySelectorAll(".elemento-row").forEach(g=>g.classList.add("hidden")),l.querySelectorAll(".toggle-elementos").forEach(g=>g.textContent="▶")}),l.querySelectorAll(".aplicar-fecha-elementos").forEach(g=>{g.addEventListener("click",w=>{var h;w.stopPropagation();const k=g.dataset.planillaId,S=(h=l.querySelector(`.planilla-fecha[data-planilla-id="${k}"]`))==null?void 0:h.value;S&&l.querySelectorAll(`.elemento-fecha[data-planilla-id="${k}"]`).forEach(D=>{D.value=S,D.dispatchEvent(new Event("change"))})})}),setTimeout(()=>{Y(r)},100)}});if(!c)return;const p=Swal.getHtmlContainer(),y=p.querySelectorAll(".planilla-fecha"),v=Array.from(y).map(s=>{const i=Number(s.getAttribute("data-planilla-id")),l=p.querySelectorAll(`.elemento-fecha[data-planilla-id="${i}"]`),f=Array.from(l).map(u=>({id:Number(u.getAttribute("data-elemento-id")),fecha_entrega:u.value||null}));return{id:i,fecha_estimada_entrega:s.value,elementos:f.length>0?f:void 0}}),b=await Pe(v);await Swal.fire(b.success?"✅":"⚠️",b.message||(b.success?"Fechas actualizadas":"No se pudieron actualizar"),b.success?"success":"warning"),b.success&&a&&((t=a.refetchEvents)==null||t.call(a),(o=a.refetchResources)==null||o.call(a))}catch(n){console.error("[CambiarFechasEntrega] error:",n),Swal.fire("❌",(n==null?void 0:n.message)||"Ocurrió un error al actualizar las fechas.","error")}}function J(e,a){e.el.addEventListener("mousedown",q),e.el.addEventListener("contextmenu",t=>{t.preventDefault(),t.stopPropagation();const o=e.event,n=o.extendedProps||{},r=n.tipo||"planilla";let d="";if(r==="salida"){if(n.clientes&&Array.isArray(n.clientes)&&n.clientes.length>0){const p=n.clientes.map(y=>y.nombre).filter(Boolean).join(", ");p&&(d+=`<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${p}</span>`)}n.obras&&Array.isArray(n.obras)&&n.obras.length>0&&(d+='<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ ',d+=n.obras.map(p=>{const y=p.codigo?`(${p.codigo})`:"";return`${p.nombre} ${y}`}).join(", "),d+="</span>")}const m=`
      <div style="padding:10px 12px; font-weight:600;">
        ${o.title??"Evento"}${d}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(o.start).toLocaleString()} — ${new Date(o.end).toLocaleString()}
        </span>
      </div>
    `;let c=[];if(r==="planilla"){const p=ye(o);c=[{label:"Gestionar Salidas y Paquetes",icon:"📦",onClick:()=>window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${p.join(",")}`},{label:"Cambiar fechas de entrega",icon:"🗓️",onClick:()=>Ae(p,a)}]}else if(r==="salida"){const p=n.salida_id||o.id;n.empresa_id,n.empresa,c=[{label:"Abrir salida",icon:"🧾",onClick:()=>window.open(`/salidas-ferralla/${p}`,"_blank")},{label:"Gestionar paquetes",icon:"📦",onClick:()=>Q(p,a)},{label:"Agregar comentario",icon:"✍️",onClick:()=>De(p,n.comentario||"",a)}]}else c=[{label:"Abrir",icon:"🧾",onClick:()=>window.open(n.url||"#","_blank")}];me(t.clientX,t.clientY,{headerHtml:m,items:c})})}function Ie(e){e.style.transform="none",e.style.position="fixed",e.style.margin="0";const a=e.offsetWidth,t=e.offsetHeight,o=Math.max(0,Math.round((window.innerWidth-a)/2)),n=Math.max(0,Math.round((window.innerHeight-t)/2));e.style.left=`${o}px`,e.style.top=`${n}px`}function N(e=".swal2-title",a=!1){const t=Swal.getPopup(),o=Swal.getHtmlContainer();let n=(e?(o==null?void 0:o.querySelector(e))||(t==null?void 0:t.querySelector(e)):null)||t;if(!t||!n)return;a&&N.__lastPos&&(t.style.left=N.__lastPos.left,t.style.top=N.__lastPos.top,t.style.transform="none"),n.style.cursor="move",n.style.touchAction="none";const r=i=>{var l;return((l=i.closest)==null?void 0:l.call(i,"input, textarea, select, button, a, label, [contenteditable]"))!=null};let d=!1,m=0,c=0,p=0,y=0;const v=i=>{if(!n.contains(i.target)||r(i.target))return;d=!0,document.body.style.userSelect="none";const l=t.getBoundingClientRect();t.style.left=`${l.left}px`,t.style.top=`${l.top}px`,t.style.transform="none",p=parseFloat(t.style.left||l.left),y=parseFloat(t.style.top||l.top),m=i.clientX,c=i.clientY,document.addEventListener("pointermove",b),document.addEventListener("pointerup",s,{once:!0})},b=i=>{if(!d)return;const l=i.clientX-m,f=i.clientY-c;let u=p+l,g=y+f;const w=t.offsetWidth,k=t.offsetHeight,S=-w+40,h=window.innerWidth-40,D=-k+40,T=window.innerHeight-40;u=Math.max(S,Math.min(h,u)),g=Math.max(D,Math.min(T,g)),t.style.left=`${u}px`,t.style.top=`${g}px`},s=()=>{d=!1,document.body.style.userSelect="",a&&(N.__lastPos={left:t.style.left,top:t.style.top}),document.removeEventListener("pointermove",b)};n.addEventListener("pointerdown",v)}document.addEventListener("DOMContentLoaded",function(){window.addEventListener("comentarioGuardado",e=>{const{salidaId:a,comentario:t}=e.detail,o=window._calendarRef;if(o){const n=o.getEventById(`salida-${a}`);n&&(n.setExtendedProp("comentario",t),n._def&&n._def.extendedProps&&(n._def.extendedProps.comentario=t)),typeof Swal<"u"&&Swal.fire({icon:"success",title:"Comentario guardado",text:"El comentario se ha guardado correctamente",timer:2e3,showConfirmButton:!1,toast:!0,position:"top-end"})}})});function X(e){var d,m;if(!e)return;const a=new Date(e),t={year:"numeric",month:"long"};let o=a.toLocaleDateString("es-ES",t);o=o.charAt(0).toUpperCase()+o.slice(1);const n=document.querySelector("#resumen-mensual-fecha");n&&(n.textContent=`(${o})`);const r=(m=(d=window.AppSalidas)==null?void 0:d.routes)==null?void 0:m.totales;r&&fetch(`${r}?fecha=${encodeURIComponent(e)}`).then(c=>c.json()).then(c=>{const p=c.semana||{};F("#resumen-semanal-peso",`📦 ${B(p.peso)} kg`),F("#resumen-semanal-longitud",`📏 ${B(p.longitud)} m`),F("#resumen-semanal-diametro",p.diametro!=null&&!isNaN(p.diametro)?`⌀ ${Number(p.diametro).toFixed(2)} mm`:"");const y=c.mes||{};F("#resumen-mensual-peso",`📦 ${B(y.peso)} kg`),F("#resumen-mensual-longitud",`📏 ${B(y.longitud)} m`),F("#resumen-mensual-diametro",y.diametro!=null&&!isNaN(y.diametro)?`⌀ ${Number(y.diametro).toFixed(2)} mm`:"")}).catch(c=>console.error("❌ Error al actualizar los totales:",c))}function B(e){return e!=null?Number(e).toLocaleString():"0"}function F(e,a){const t=document.querySelector(e);t&&(t.textContent=a)}let x=null;function Fe(e,a){const t=()=>e&&e.offsetParent!==null&&e.clientWidth>0&&e.clientHeight>=0;if(t())return a();if("IntersectionObserver"in window){const n=new IntersectionObserver(r=>{r.some(m=>m.isIntersecting)&&(n.disconnect(),a())},{root:null,threshold:.01});n.observe(e);return}if("ResizeObserver"in window){const n=new ResizeObserver(()=>{t()&&(n.disconnect(),a())});n.observe(e);return}const o=setInterval(()=>{t()&&(clearInterval(o),a())},100)}function I(){x&&(requestAnimationFrame(()=>{try{x.updateSize()}catch{}}),setTimeout(()=>{try{x.updateSize()}catch{}},150))}function Me(){if(!window.FullCalendar)return console.error("FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."),null;x&&x.destroy();const e=["resourceTimeGridDay","resourceTimelineWeek","dayGridMonth"];let a=localStorage.getItem("ultimaVistaCalendario");e.includes(a)||(a="resourceTimeGridDay");const t=localStorage.getItem("fechaCalendario");let o=null;const n=document.getElementById("calendario");if(!n)return console.error("#calendario no encontrado"),null;function r(p){return x?x.getEvents().some(y=>{var s,i;const v=(y.startStr||((s=y.start)==null?void 0:s.toISOString())||"").split("T")[0];return(((i=y.extendedProps)==null?void 0:i.tipo)==="festivo"||typeof y.id=="string"&&y.id.startsWith("festivo-"))&&v===p}):!1}Fe(n,()=>{x=new FullCalendar.Calendar(n,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",navLinks:!0,navLinkDayClick:(s,i)=>{var g;const l=s.getDay(),f=l===0||l===6,u=(g=x==null?void 0:x.view)==null?void 0:g.type;if(f&&(u==="resourceTimelineWeek"||u==="dayGridMonth")){i.preventDefault();let w;u==="dayGridMonth"?w=l===6?"saturday":"sunday":w=s.toISOString().split("T")[0],window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(w)?window.expandedWeekendDays.delete(w):window.expandedWeekendDays.add(w),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),x.render(),setTimeout(()=>{var k;return(k=window.applyWeekendCollapse)==null?void 0:k.call(window)},50);return}x.changeView("resourceTimeGridDay",s)},initialView:a,initialDate:t?new Date(t):void 0,dayMaxEventRows:!1,dayMaxEvents:!1,slotMinTime:"05:00:00",slotMaxTime:"20:00:00",buttonText:{today:"Hoy",resourceTimeGridDay:"Día",resourceTimelineWeek:"Semana",dayGridMonth:"Mes"},progressiveEventRendering:!0,expandRows:!0,height:"auto",events:(s,i,l)=>{var u;const f=s.view&&s.view.type||((u=x==null?void 0:x.view)==null?void 0:u.type)||"resourceTimeGridDay";ce(f,s).then(i).catch(l)},resources:(s,i,l)=>{var u;const f=s.view&&s.view.type||((u=x==null?void 0:x.view)==null?void 0:u.type)||"resourceTimeGridDay";ue(f,s).then(i).catch(l)},headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimeGridDay,resourceTimelineWeek,dayGridMonth"},eventOrderStrict:!0,eventOrder:(s,i)=>{var w,k;const l=((w=s.extendedProps)==null?void 0:w.tipo)==="resumen-dia",f=((k=i.extendedProps)==null?void 0:k.tipo)==="resumen-dia";if(l&&!f)return-1;if(!l&&f)return 1;const u=parseInt(String(s.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0,g=parseInt(String(i.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0;return u-g},datesSet:s=>{try{const i=ze(s);localStorage.setItem("fechaCalendario",i),localStorage.setItem("ultimaVistaCalendario",s.view.type),m(),setTimeout(()=>X(i),0),clearTimeout(o),o=setTimeout(()=>{x.refetchResources(),x.refetchEvents(),I(),(s.view.type==="resourceTimelineWeek"||s.view.type==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)},0)}catch(i){console.error("Error en datesSet:",i)}},loading:s=>{if(!s&&x){const i=x.view.type;i==="resourceTimeGridDay"&&setTimeout(()=>c(),150),(i==="resourceTimelineWeek"||i==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)}},viewDidMount:s=>{m(),s.view.type==="resourceTimeGridDay"&&setTimeout(()=>c(),100),s.view.type==="dayGridMonth"&&setTimeout(()=>{document.querySelectorAll(".fc-daygrid-event-harness").forEach(i=>{i.querySelector(".evento-resumen-diario")||(i.style.setProperty("width","100%","important"),i.style.setProperty("max-width","100%","important"),i.style.setProperty("position","static","important"),i.style.setProperty("left","unset","important"),i.style.setProperty("right","unset","important"),i.style.setProperty("top","unset","important"),i.style.setProperty("inset","unset","important"),i.style.setProperty("margin","0 0 2px 0","important"))}),document.querySelectorAll(".fc-daygrid-event:not(.evento-resumen-diario)").forEach(i=>{i.style.setProperty("width","100%","important"),i.style.setProperty("max-width","100%","important"),i.style.setProperty("margin","0","important"),i.style.setProperty("position","static","important"),i.style.setProperty("left","unset","important"),i.style.setProperty("right","unset","important"),i.style.setProperty("inset","unset","important")})},50)},eventContent:s=>{var g;const i=s.event.backgroundColor||"#9CA3AF",l=s.event.extendedProps||{},f=(g=x==null?void 0:x.view)==null?void 0:g.type;if(l.tipo==="resumen-dia"){const w=Number(l.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),k=Number(l.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),S=l.diametroMedio?Number(l.diametroMedio).toFixed(1):null;if(f==="resourceTimelineWeek")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${w} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${k} m</div>
                                ${S?`<div class="text-yellow-800">⌀ ${S} mm</div>`:""}
                            </div>
                        `};if(f==="dayGridMonth")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${w} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${k} m</div>
                                ${S?`<div class="text-yellow-800">⌀ ${S} mm</div>`:""}
                            </div>
                        `}}let u=`
        <div style="background-color:${i}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${s.event.title}</div>
    `;if(l.tipo==="planilla"){const w=l.pesoTotal!=null?`📦 ${Number(l.pesoTotal).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2})} kg`:null,k=l.longitudTotal!=null?`📏 ${Number(l.longitudTotal).toLocaleString()} m`:null,S=l.diametroMedio!=null?`⌀ ${Number(l.diametroMedio).toFixed(2)} mm`:null,h=[w,k,S].filter(Boolean);h.length>0&&(u+=`<div class="text-sm text-black font-semibold">${h.join(" | ")}</div>`),l.tieneSalidas&&Array.isArray(l.salidas_codigos)&&l.salidas_codigos.length>0&&(u+=`
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${l.salidas_codigos.join(", ")}
                </span>
            </div>`)}return u+="</div>",{html:u}},eventDidMount:function(s){var u,g,w,k;const i=s.event.extendedProps||{};if(i.tipo==="resumen-dia"){s.el.classList.add("evento-resumen-diario"),s.el.style.cursor="default";return}if(s.view.type==="dayGridMonth"){const S=s.el.closest(".fc-daygrid-event-harness");S&&(S.style.setProperty("width","100%","important"),S.style.setProperty("max-width","100%","important"),S.style.setProperty("min-width","100%","important"),S.style.setProperty("position","static","important"),S.style.setProperty("left","unset","important"),S.style.setProperty("right","unset","important"),S.style.setProperty("top","unset","important"),S.style.setProperty("inset","unset","important"),S.style.setProperty("margin","0 0 2px 0","important"),S.style.setProperty("display","block","important")),s.el.style.setProperty("width","100%","important"),s.el.style.setProperty("max-width","100%","important"),s.el.style.setProperty("min-width","100%","important"),s.el.style.setProperty("margin","0","important"),s.el.style.setProperty("position","static","important"),s.el.style.setProperty("left","unset","important"),s.el.style.setProperty("right","unset","important"),s.el.style.setProperty("inset","unset","important"),s.el.style.setProperty("display","block","important"),s.el.querySelectorAll("*").forEach(h=>{h.style.setProperty("width","100%","important"),h.style.setProperty("max-width","100%","important")})}const l=(((u=document.getElementById("filtro-obra"))==null?void 0:u.value)||"").trim().toLowerCase(),f=(((g=document.getElementById("filtro-nombre-obra"))==null?void 0:g.value)||"").trim().toLowerCase();if(l||f){let S=!1;if(i.tipo==="salida"&&i.obras&&Array.isArray(i.obras))S=i.obras.some(h=>{const D=(h.codigo||"").toString().toLowerCase(),T=(h.nombre||"").toString().toLowerCase();return l&&D.includes(l)||f&&T.includes(f)});else{const h=(((w=s.event.extendedProps)==null?void 0:w.cod_obra)||"").toString().toLowerCase(),D=(((k=s.event.extendedProps)==null?void 0:k.nombre_obra)||s.event.title||"").toString().toLowerCase();S=l&&h.includes(l)||f&&D.includes(f)}if(S){s.el.classList.add("evento-filtrado");const h="#1f2937",D="#111827";s.el.style.setProperty("background-color",h,"important"),s.el.style.setProperty("background",h,"important"),s.el.style.setProperty("border-color",D,"important"),s.el.style.setProperty("color","white","important"),s.el.querySelectorAll("*").forEach(T=>{T.style.setProperty("background-color",h,"important"),T.style.setProperty("background",h,"important"),T.style.setProperty("color","white","important")})}}typeof K=="function"&&K(s),typeof J=="function"&&J(s,x)},eventAllow:(s,i)=>{var f;const l=(f=i.extendedProps)==null?void 0:f.tipo;return!(l==="resumen-dia"||l==="festivo")},eventDragStart:()=>{const s=()=>{document.querySelectorAll(".fc-event-dragging").forEach(i=>{i.style.width="150px",i.style.maxWidth="150px",i.style.minWidth="150px",i.style.height="80px",i.style.maxHeight="80px",i.style.overflow="hidden"}),window._isDragging&&requestAnimationFrame(s)};window._isDragging=!0,requestAnimationFrame(s)},eventDragStop:()=>{window._isDragging=!1},eventDrop:s=>{var w,k,S,h;const i=s.event.extendedProps||{},l=s.event.id,u={fecha:(w=s.event.start)==null?void 0:w.toISOString(),tipo:i.tipo,planillas_ids:i.planillas_ids||[]},g=(((S=(k=window.AppSalidas)==null?void 0:k.routes)==null?void 0:S.updateItem)||"").replace("__ID__",l);fetch(g,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(h=window.AppSalidas)==null?void 0:h.csrf},body:JSON.stringify(u)}).then(D=>{if(!D.ok)throw new Error("No se pudo actualizar la fecha.");return D.json()}).then(()=>{x.refetchEvents(),x.refetchResources();const T=s.event.start.toISOString().split("T")[0];X(T),I()}).catch(D=>{console.error("Error:",D),s.revert()})},dateClick:s=>{r(s.dateStr)&&Swal.fire({icon:"info",title:"📅 Día festivo",text:"Los festivos se editan en la planificación de Trabajadores.",confirmButtonText:"Entendido"})},eventMinHeight:30,firstDay:1,slotLabelContent:s=>{var S,h;if(((S=x==null?void 0:x.view)==null?void 0:S.type)!=="resourceTimelineWeek")return null;const l=s.date;if(!l)return null;const f=l.getDay(),u=f===0||f===6,g=l.toISOString().split("T")[0],w={weekday:"short",day:"numeric",month:"short"},k=l.toLocaleDateString("es-ES",w);if(u){const T=!((h=window.expandedWeekendDays)==null?void 0:h.has(g)),le=T?"▶":"▼",de=T?l.toLocaleDateString("es-ES",{weekday:"short"}).substring(0,3):k;return{html:`<div class="weekend-header cursor-pointer select-none hover:bg-gray-200 px-1 rounded"
                                    data-date="${g}"
                                    data-collapsed="${T}"
                                    title="${T?"Clic para expandir":"Clic para colapsar"}">
                                <span class="collapse-icon text-xs mr-1">${le}</span>
                                <span class="weekend-label">${de}</span>
                               </div>`}}return{html:`<span>${k}</span>`}},views:{resourceTimelineWeek:{slotDuration:{days:1}},resourceTimeGridDay:{slotDuration:"01:00:00",slotLabelFormat:{hour:"2-digit",minute:"2-digit",hour12:!1},slotLabelInterval:"01:00:00",allDaySlot:!1}},editable:!0,eventDurationEditable:!1,eventStartEditable:!0,resourceAreaColumns:[{field:"cod_obra",headerContent:"Código"},{field:"title",headerContent:"Obra"},{field:"cliente",headerContent:"Cliente"}],resourceAreaHeaderContent:"Obras",resourceOrder:"orderIndex",resourceLabelContent:s=>({html:`<div class="text-xs font-semibold">
                        <div class="text-blue-600">${s.resource.extendedProps.cod_obra||""}</div>
                        <div class="text-gray-700 truncate">${s.resource.title||""}</div>
                        <div class="text-gray-500 text-[10px] truncate">${s.resource.extendedProps.cliente||""}</div>
                    </div>`}),windowResize:()=>I()}),x.render(),I();const p=localStorage.getItem("expandedWeekendDays");window.expandedWeekendDays=new Set(p?JSON.parse(p):[]),window.weekendDefaultCollapsed=!0;function y(s){const l=new Date(s+"T00:00:00").getDay();return l===0||l===6}function v(){var i,l,f;const s=(i=x==null?void 0:x.view)==null?void 0:i.type;if(s==="resourceTimelineWeek"&&(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(w=>{var S;const k=w.getAttribute("data-date");y(k)&&(((S=window.expandedWeekendDays)==null?void 0:S.has(k))?w.classList.remove("weekend-collapsed"):w.classList.add("weekend-collapsed"))}),document.querySelectorAll(".fc-timeline-lane td[data-date]").forEach(w=>{var S;const k=w.getAttribute("data-date");y(k)&&(((S=window.expandedWeekendDays)==null?void 0:S.has(k))?w.classList.remove("weekend-collapsed"):w.classList.add("weekend-collapsed"))})),s==="dayGridMonth"){const u=(l=window.expandedWeekendDays)==null?void 0:l.has("saturday"),g=(f=window.expandedWeekendDays)==null?void 0:f.has("sunday");console.log("applyWeekendCollapse - satExpanded:",u,"sunExpanded:",g);const w=document.querySelectorAll(".fc-col-header-cell.fc-day-sat"),k=document.querySelectorAll(".fc-col-header-cell.fc-day-sun");console.log("Headers encontrados - sat:",w.length,"sun:",k.length),w.forEach(h=>{u?h.classList.remove("weekend-day-collapsed"):h.classList.add("weekend-day-collapsed"),console.log("Header sat después:",h.classList.contains("weekend-day-collapsed"))}),k.forEach(h=>{g?h.classList.remove("weekend-day-collapsed"):h.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sat").forEach(h=>{u?h.classList.remove("weekend-day-collapsed"):h.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sun").forEach(h=>{g?h.classList.remove("weekend-day-collapsed"):h.classList.add("weekend-day-collapsed")});const S=document.querySelector(".fc-dayGridMonth-view table");if(S){let h=S.querySelector("colgroup");if(!h){h=document.createElement("colgroup");for(let T=0;T<7;T++)h.appendChild(document.createElement("col"));S.insertBefore(h,S.firstChild)}const D=h.querySelectorAll("col");D.length>=7&&(D[5].style.width=u?"":"40px",D[6].style.width=g?"":"40px")}}}function b(s){console.log("toggleWeekendCollapse llamado con key:",s),console.log("expandedWeekendDays antes:",[...window.expandedWeekendDays||[]]),window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(s)?(window.expandedWeekendDays.delete(s),console.log("Colapsando:",s)):(window.expandedWeekendDays.add(s),console.log("Expandiendo:",s)),console.log("expandedWeekendDays después:",[...window.expandedWeekendDays]),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),v()}n.addEventListener("click",s=>{var f;console.log("Click detectado en:",s.target);const i=s.target.closest(".weekend-header");if(i){const u=i.getAttribute("data-date");if(console.log("Click en weekend-header, dateStr:",u),u){s.preventDefault(),s.stopPropagation(),b(u);return}}const l=(f=x==null?void 0:x.view)==null?void 0:f.type;if(console.log("Vista actual:",l),l==="dayGridMonth"){const u=s.target.closest(".fc-col-header-cell.fc-day-sat, .fc-col-header-cell.fc-day-sun");if(console.log("Header cell encontrado:",u),u){s.preventDefault(),s.stopPropagation();const k=u.classList.contains("fc-day-sat")?"saturday":"sunday";console.log("Toggling:",k),b(k);return}const g=s.target.closest(".fc-daygrid-day.fc-day-sat, .fc-daygrid-day.fc-day-sun");if(console.log("Day cell encontrado:",g),g&&!s.target.closest(".fc-event")){s.preventDefault(),s.stopPropagation();const k=g.classList.contains("fc-day-sat")?"saturday":"sunday";console.log("Toggling day:",k),b(k);return}}},!0),setTimeout(()=>v(),100),window.applyWeekendCollapse=v,n.addEventListener("contextmenu",s=>{const i=s.target.closest(".fc-daygrid-day, .fc-timeline-slot, .fc-timegrid-slot, .fc-col-header-cell");if(i){let l=i.getAttribute("data-date");if(!l){const f=s.target.closest("[data-date]");f&&(l=f.getAttribute("data-date"))}if(l&&x){const f=x.view.type;(f==="resourceTimelineWeek"||f==="dayGridMonth")&&(s.preventDefault(),s.stopPropagation(),Swal.fire({title:"📅 Ir a día",text:`¿Quieres ver el día ${l}?`,icon:"question",showCancelButton:!0,confirmButtonText:"Sí, ir al día",cancelButtonText:"Cancelar"}).then(u=>{u.isConfirmed&&(x.changeView("resourceTimeGridDay",l),I())}))}}})}),window.addEventListener("shown.bs.tab",I),window.addEventListener("shown.bs.collapse",I),window.addEventListener("shown.bs.modal",I);function m(){document.querySelectorAll(".resumen-diario-custom").forEach(y=>y.remove())}function c(){if(!x||x.view.type!=="resourceTimeGridDay"){m();return}m();const p=x.getDate(),y=p.getFullYear(),v=String(p.getMonth()+1).padStart(2,"0"),b=String(p.getDate()).padStart(2,"0"),s=`${y}-${v}-${b}`,i=x.getEvents().find(l=>{var f,u;return((f=l.extendedProps)==null?void 0:f.tipo)==="resumen-dia"&&((u=l.extendedProps)==null?void 0:u.fecha)===s});if(i&&i.extendedProps){const l=Number(i.extendedProps.pesoTotal||0).toLocaleString(),f=Number(i.extendedProps.longitudTotal||0).toLocaleString(),u=i.extendedProps.diametroMedio?Number(i.extendedProps.diametroMedio).toFixed(2):null,g=document.createElement("div");g.className="resumen-diario-custom",g.innerHTML=`
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 Peso: ${l} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${f} m</div>
                        ${u?`<div class="text-yellow-800">⌀ Diámetro: ${u} mm</div>`:""}
                    </div>
                </div>
            `,n&&n.parentNode&&n.parentNode.insertBefore(g,n)}}return window.mostrarResumenDiario=c,window.limpiarResumenesCustom=m,x}function ze(e){if(e.view.type==="dayGridMonth"){const a=new Date(e.start);return a.setDate(a.getDate()+15),a.toISOString().split("T")[0]}if(e.view.type==="resourceTimeGridWeek"||e.view.type==="resourceTimelineWeek"){const a=new Date(e.start),t=Math.floor((e.end-e.start)/(1e3*60*60*24)/2);return a.setDate(a.getDate()+t),a.toISOString().split("T")[0]}return e.startStr.split("T")[0]}function _e(e,a={}){const{selector:t=null,once:o=!1}=a;let n=!1;const r=()=>{t&&!document.querySelector(t)||o&&n||(n=!0,e())};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",r):r(),document.addEventListener("livewire:navigated",r)}function Ne(e){document.addEventListener("livewire:navigating",e)}function We(e){let t=new Date(e).toLocaleDateString("es-ES",{month:"long",year:"numeric"});return`(${t.charAt(0).toUpperCase()+t.slice(1)})`}function Oe(e){const a=new Date(e),t=a.getDay(),o=t===0?-6:1-t,n=new Date(a);n.setDate(a.getDate()+o);const r=new Date(n);r.setDate(n.getDate()+6);const d=new Intl.DateTimeFormat("es-ES",{day:"2-digit",month:"short"}),m=new Intl.DateTimeFormat("es-ES",{year:"numeric"});return`(${d.format(n)} – ${d.format(r)} ${m.format(r)})`}function Be(e){const a=document.querySelector("#resumen-semanal-fecha"),t=document.querySelector("#resumen-mensual-fecha");a&&(a.textContent=Oe(e)),t&&(t.textContent=We(e));const o=`${window.AppSalidas.routes.totales}?fecha=${encodeURIComponent(e)}`;fetch(o).then(n=>n.json()).then(n=>{const r=n.semana||{},d=n.mes||{};document.querySelector("#resumen-semanal-peso").textContent=`📦 ${Number(r.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-semanal-longitud").textContent=`📏 ${Number(r.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-semanal-diametro").textContent=r.diametro!=null?`⌀ ${Number(r.diametro).toFixed(2)} mm`:"",document.querySelector("#resumen-mensual-peso").textContent=`📦 ${Number(d.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-mensual-longitud").textContent=`📏 ${Number(d.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-mensual-diametro").textContent=d.diametro!=null?`⌀ ${Number(d.diametro).toFixed(2)} mm`:""}).catch(n=>console.error("❌ Totales:",n))}let M;function je(){var i,l;if(window.calendar)try{window.calendar.destroy()}catch(f){console.warn("Error al destruir calendario anterior:",f)}const e=Me();M=e,window.calendar=e,e.refetchResources(),e.refetchEvents(),(i=document.getElementById("ver-con-salidas"))==null||i.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()}),(l=document.getElementById("ver-todas"))==null||l.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()});const t=(localStorage.getItem("fechaCalendario")||new Date().toISOString()).split("T")[0];Be(t);const o=localStorage.getItem("soloSalidas")==="true",n=localStorage.getItem("soloPlanillas")==="true",r=document.getElementById("solo-salidas"),d=document.getElementById("solo-planillas");r&&(r.checked=o),d&&(d.checked=n);const m=document.getElementById("filtro-obra"),c=document.getElementById("filtro-nombre-obra"),p=document.getElementById("btn-reset-filtros"),y=document.getElementById("btn-limpiar-filtros");p==null||p.addEventListener("click",()=>{m&&(m.value=""),c&&(c.value=""),r&&(r.checked=!1,localStorage.setItem("soloSalidas","false")),d&&(d.checked=!1,localStorage.setItem("soloPlanillas","false")),s(),M.refetchEvents()});const b=((f,u=150)=>{let g;return(...w)=>{clearTimeout(g),g=setTimeout(()=>f(...w),u)}})(()=>{M.refetchEvents()},120);m==null||m.addEventListener("input",b),c==null||c.addEventListener("input",b);function s(){const f=r==null?void 0:r.closest(".checkbox-container"),u=d==null?void 0:d.closest(".checkbox-container");f==null||f.classList.remove("active-salidas"),u==null||u.classList.remove("active-planillas"),r!=null&&r.checked&&(f==null||f.classList.add("active-salidas")),d!=null&&d.checked&&(u==null||u.classList.add("active-planillas"))}r==null||r.addEventListener("change",f=>{f.target.checked&&d&&(d.checked=!1,localStorage.setItem("soloPlanillas","false")),localStorage.setItem("soloSalidas",f.target.checked.toString()),s(),M.refetchEvents()}),d==null||d.addEventListener("change",f=>{f.target.checked&&r&&(r.checked=!1,localStorage.setItem("soloSalidas","false")),localStorage.setItem("soloPlanillas",f.target.checked.toString()),s(),M.refetchEvents()}),s(),y==null||y.addEventListener("click",()=>{m&&(m.value=""),c&&(c.value=""),M.refetchEvents()})}let L=null,_=null,A="days",$=-1,C=[];function Ge(){_&&_();const e=window.calendar;if(!e)return;L=e.getDate(),A="days",$=-1,W();function a(t){const o=t.target.tagName.toLowerCase();if(o==="input"||o==="textarea"||t.target.isContentEditable||document.querySelector(".swal2-container")||!window.calendar||!L)return;let r=!1;if(t.key==="Tab"&&!t.ctrlKey&&!t.metaKey){t.preventDefault(),Re();return}if(t.key==="Escape"&&A==="events"){t.preventDefault(),A="days",$=-1,G(),W(),O();return}A==="events"?r=He(t):r=Ve(t),r&&(t.preventDefault(),t.stopPropagation())}document.addEventListener("keydown",a,!0),e.on("eventsSet",()=>{A==="events"&&(se(),z())}),_=()=>{document.removeEventListener("keydown",a,!0),ie(),G()}}function Re(){A==="days"?(A="events",se(),C.length>0?($=0,z()):(A="days",Je())):(A="days",$=-1,G(),W()),O()}function se(){const e=window.calendar;if(!e){C=[];return}C=e.getEvents().filter(a=>{var o;const t=(o=a.extendedProps)==null?void 0:o.tipo;return t!=="resumen-dia"&&t!=="festivo"}).sort((a,t)=>{const o=a.start||new Date(0),n=t.start||new Date(0);return o<n?-1:o>n?1:(a.title||"").localeCompare(t.title||"")})}function He(e){if(C.length===0)return!1;let a=!1;switch(e.key){case"ArrowDown":case"ArrowRight":$=($+1)%C.length,z(),a=!0;break;case"ArrowUp":case"ArrowLeft":$=$<=0?C.length-1:$-1,z(),a=!0;break;case"Home":$=0,z(),a=!0;break;case"End":$=C.length-1,z(),a=!0;break;case"Enter":Ke(),a=!0;break;case"e":case"E":Ue(),a=!0;break;case"i":case"I":Ye(),a=!0;break}return a}function Ve(e){const a=window.calendar,t=new Date(L);let o=!1;switch(e.key){case"ArrowLeft":t.setDate(t.getDate()-1),o=!0;break;case"ArrowRight":t.setDate(t.getDate()+1),o=!0;break;case"ArrowUp":t.setDate(t.getDate()-7),o=!0;break;case"ArrowDown":t.setDate(t.getDate()+7),o=!0;break;case"Home":t.setDate(1),o=!0;break;case"End":t.setMonth(t.getMonth()+1),t.setDate(0),o=!0;break;case"PageUp":t.setMonth(t.getMonth()-1),o=!0;break;case"PageDown":t.setMonth(t.getMonth()+1),o=!0;break;case"Enter":const n=re(L),r=a.view.type;r==="dayGridMonth"||r==="resourceTimelineWeek"?a.changeView("resourceTimeGridDay",n):a.gotoDate(L),o=!0;break;case"t":case"T":!e.ctrlKey&&!e.metaKey&&(L=new Date,a.today(),W(),o=!0);break}if(o&&e.key!=="Enter"&&e.key!=="t"&&e.key!=="T"){L=t;const n=a.view;(t<n.currentStart||t>=n.currentEnd)&&a.gotoDate(t),W()}return o}function z(){var t;if(G(),$<0||$>=C.length)return;const e=C[$];if(!e)return;const a=document.querySelector(`[data-event-id="${e.id}"]`)||document.querySelector(`.fc-event[data-event="${e.id}"]`);if(a)a.classList.add("keyboard-focused-event"),a.scrollIntoView({behavior:"smooth",block:"nearest"});else{const o=document.querySelectorAll(".fc-event");for(const n of o)if(n.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){n.classList.add("keyboard-focused-event"),n.scrollIntoView({behavior:"smooth",block:"nearest"});break}}e.start&&(L=new Date(e.start)),O()}function G(){document.querySelectorAll(".keyboard-focused-event").forEach(e=>{e.classList.remove("keyboard-focused-event")})}function Ke(){if($<0||$>=C.length)return;const e=C[$];if(!e)return;const a=e.extendedProps||{},t=window.calendar;if(a.tipo==="salida"){const o=a.salida_id||e.id;Q(o,t)}else if(a.tipo==="planilla"){const o=a.planillas_ids||[];o.length>0&&(window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${o.join(",")}`)}}function Ue(){var t;if($<0||$>=C.length)return;const e=C[$];if(!e)return;const a=document.querySelectorAll(".fc-event");for(const o of a)if(o.classList.contains("keyboard-focused-event")||o.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){const n=o.getBoundingClientRect(),r=new MouseEvent("contextmenu",{bubbles:!0,cancelable:!0,clientX:n.left+n.width/2,clientY:n.top+n.height/2});o.dispatchEvent(r);break}}function Ye(){if($<0||$>=C.length)return;const e=C[$];if(!e)return;const a=e.extendedProps||{};let t=`<strong>${e.title}</strong><br><br>`;a.tipo==="salida"?(t+="<b>Tipo:</b> Salida<br>",a.obras&&a.obras.length>0&&(t+=`<b>Obras:</b> ${a.obras.map(o=>o.nombre).join(", ")}<br>`)):a.tipo==="planilla"&&(t+="<b>Tipo:</b> Planilla<br>",a.cod_obra&&(t+=`<b>Código:</b> ${a.cod_obra}<br>`),a.pesoTotal&&(t+=`<b>Peso:</b> ${Number(a.pesoTotal).toLocaleString()} kg<br>`),a.longitudTotal&&(t+=`<b>Longitud:</b> ${Number(a.longitudTotal).toLocaleString()} m<br>`)),e.start&&(t+=`<b>Fecha:</b> ${e.start.toLocaleDateString("es-ES",{weekday:"long",day:"numeric",month:"long",year:"numeric"})}<br>`),Swal.fire({title:"Información del evento",html:t,icon:"info",confirmButtonText:"Cerrar"})}function Je(){const e=document.getElementById("keyboard-nav-indicator");if(e){const a=document.getElementById("keyboard-nav-date");a&&(a.innerHTML='<span class="text-yellow-400">No hay eventos visibles</span>'),clearTimeout(e._hideTimeout),e.style.display="flex",e._hideTimeout=setTimeout(()=>{O()},2e3)}}function re(e){const a=e.getFullYear(),t=String(e.getMonth()+1).padStart(2,"0"),o=String(e.getDate()).padStart(2,"0");return`${a}-${t}-${o}`}function W(){if(ie(),!L)return;const e=re(L),a=window.calendar;if(!a)return;const t=a.view.type;let o=null;t==="dayGridMonth"?o=document.querySelector(`.fc-daygrid-day[data-date="${e}"]`):t==="resourceTimelineWeek"?(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(r=>{r.dataset.date&&r.dataset.date.startsWith(e)&&(o=r)}),o||(o=document.querySelector(`.fc-timeline-slot-lane[data-date^="${e}"]`))):t==="resourceTimeGridDay"&&(o=document.querySelector(".fc-col-header-cell")),o&&(o.classList.add("keyboard-focused-day"),o.scrollIntoView({behavior:"smooth",block:"nearest",inline:"nearest"})),O()}function ie(){document.querySelectorAll(".keyboard-focused-day").forEach(e=>{e.classList.remove("keyboard-focused-day")})}function O(){let e=document.getElementById("keyboard-nav-indicator");if(e||(e=document.createElement("div"),e.id="keyboard-nav-indicator",e.className="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm",document.body.appendChild(e)),A==="events"){const a=C[$],t=(a==null?void 0:a.title)||"Sin evento",o=`${$+1}/${C.length}`;e.innerHTML=`
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
        `}clearTimeout(e._hideTimeout),e.style.display="block",e._hideTimeout=setTimeout(()=>{e.style.display="none"},4e3)}function Xe(){if(document.getElementById("keyboard-nav-styles"))return;const e=document.createElement("style");e.id="keyboard-nav-styles",e.textContent=`
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
    `,document.head.appendChild(e)}_e(()=>{je(),Xe(),setTimeout(()=>{Ge()},500)},{selector:'#calendario[data-calendar-type="salidas"]'});Ne(()=>{if(_&&(_(),_=null),window.calendar)try{window.calendar.destroy(),window.calendar=null}catch(a){console.warn("Error al limpiar calendario de salidas:",a)}const e=document.getElementById("keyboard-nav-indicator");e&&e.remove()});
