let z=new Map,H=new Map;function xe(e,a){return`${e}|${a.startStr}|${a.endStr}`}function Se(e){return Date.now()-e.timestamp>6e4}function Ee(){if(z.size>8){const e=Array.from(z.entries()).sort((t,o)=>t[1].timestamp-o[1].timestamp);e.slice(0,e.length-8).forEach(([t])=>z.delete(t))}}async function ae(e,a){var r,d;const t=(d=(r=window.AppSalidas)==null?void 0:r.routes)==null?void 0:d.planificacion;if(!t)return{events:[],resources:[],totales:null};const o=xe(e,a),n=z.get(o);if(n&&!Se(n))return n.data;if(H.has(o))return H.get(o);const i=(async()=>{try{const l=new URLSearchParams({tipo:"all",viewType:e||"",start:a.startStr||"",end:a.endStr||""}),u=await fetch(`${t}?${l.toString()}`);if(!u.ok)throw new Error(`HTTP ${u.status}`);const p=await u.json(),h={events:p.events||[],resources:p.resources||[],totales:p.totales||null};return z.set(o,{data:h,timestamp:Date.now()}),Ee(),h}catch(l){return console.error("fetch all data falló:",l),{events:[],resources:[],totales:null}}finally{H.delete(o)}})();return H.set(o,i),i}function F(){z.clear(),H.clear()}function ke(e){var r,d;const a=((r=document.getElementById("solo-salidas"))==null?void 0:r.checked)||!1,t=((d=document.getElementById("solo-planillas"))==null?void 0:d.checked)||!1,o=e.filter(l=>{var u;return((u=l.extendedProps)==null?void 0:u.tipo)==="resumen-dia"}),n=e.filter(l=>{var u;return((u=l.extendedProps)==null?void 0:u.tipo)!=="resumen-dia"});let i=n;return a&&!t?i=n.filter(l=>{var u;return((u=l.extendedProps)==null?void 0:u.tipo)==="salida"}):t&&!a&&(i=n.filter(l=>{var p;const u=(p=l.extendedProps)==null?void 0:p.tipo;return u==="planilla"||u==="festivo"})),[...i,...o]}async function $e(e,a){const t=await ae(e,a);return ke(t.events)}async function Ce(e,a){return(await ae(e,a)).resources}async function qe(e,a){const t=await ae(e,a);if(!t.totales)return;const{semana:o,mes:n}=t.totales,i=c=>c!=null?Number(c).toLocaleString():"0",r=document.querySelector("#resumen-semanal-peso"),d=document.querySelector("#resumen-semanal-longitud"),l=document.querySelector("#resumen-semanal-diametro"),u=document.querySelector("#resumen-semanal-fecha");r&&(r.textContent=`${i(o==null?void 0:o.peso)} kg`),d&&(d.textContent=`${i(o==null?void 0:o.longitud)} m`),l&&(l.textContent=o!=null&&o.diametro?`⌀ ${Number(o.diametro).toFixed(2)} mm`:""),u&&(o!=null&&o.nombre)&&(u.textContent=`(${o.nombre})`);const p=document.querySelector("#resumen-mensual-peso"),h=document.querySelector("#resumen-mensual-longitud"),b=document.querySelector("#resumen-mensual-diametro"),s=document.querySelector("#resumen-mensual-fecha");p&&(p.textContent=`${i(n==null?void 0:n.peso)} kg`),h&&(h.textContent=`${i(n==null?void 0:n.longitud)} m`),b&&(b.textContent=n!=null&&n.diametro?`⌀ ${Number(n.diametro).toFixed(2)} mm`:""),s&&(n!=null&&n.nombre)&&(s.textContent=`(${n.nombre})`)}function oe(e,a){const t=e.event.extendedProps||{};if(t.tipo!=="festivo"){if(t.tipo==="planilla"){const o=`
      ✅ Fabricados: ${U(t.fabricadosKg)} kg<br>
      🔄 Fabricando: ${U(t.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${U(t.pendientesKg)} kg
    `;tippy(e.el,{content:o,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}t.tipo==="salida"&&t.comentario&&t.comentario.trim()&&tippy(e.el,{content:t.comentario,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}}function U(e){return e!=null?Number(e).toLocaleString():0}let G=null;function A(){G&&(G.remove(),G=null,document.removeEventListener("click",A),document.removeEventListener("contextmenu",A,!0),document.removeEventListener("scroll",A,!0),window.removeEventListener("resize",A),window.removeEventListener("keydown",ue))}function ue(e){e.key==="Escape"&&A()}function Le(e,a,t){A();const o=document.createElement("div");o.className="fc-contextmenu",Object.assign(o.style,{position:"fixed",top:a+"px",left:e+"px",zIndex:99999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",fontFamily:"system-ui, -apple-system, Segoe UI, Roboto, sans-serif"}),o.innerHTML=t,document.body.appendChild(o),G=o;const n=o.getBoundingClientRect(),i=Math.max(0,n.right-window.innerWidth+8),r=Math.max(0,n.bottom-window.innerHeight+8);return(i||r)&&(o.style.left=Math.max(8,e-i)+"px",o.style.top=Math.max(8,a-r)+"px"),setTimeout(()=>{document.addEventListener("click",A),document.addEventListener("contextmenu",A,!0),document.addEventListener("scroll",A,!0),window.addEventListener("resize",A),window.addEventListener("keydown",ue)},0),o}function _e(e,a,{headerHtml:t="",items:o=[]}={}){const n=`
    <div class="ctx-menu-container">
      ${t?`<div class="ctx-menu-header">${t}</div>`:""}
      ${o.map((r,d)=>`
        <button type="button"
          class="ctx-menu-item${r.danger?" ctx-menu-danger":""}"
          data-idx="${d}">
          ${r.icon?`<span class="ctx-menu-icon">${r.icon}</span>`:""}
          <span class="ctx-menu-label">${r.label}</span>
        </button>`).join("")}
    </div>
  `,i=Le(e,a,n);return i.querySelectorAll(".ctx-menu-item").forEach(r=>{r.addEventListener("click",async d=>{var p;d.preventDefault(),d.stopPropagation();const l=Number(r.dataset.idx),u=(p=o[l])==null?void 0:p.onClick;A();try{await(u==null?void 0:u())}catch(h){console.error(h)}})}),i}function Te(e){if(!e||typeof e!="string")return"";const a=e.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);if(a){const t=a[1],o=a[2].padStart(2,"0"),n=a[3].padStart(2,"0");return`${t}-${o}-${n}`}return e}(function(){if(document.getElementById("swal-anims"))return;const a=document.createElement("style");a.id="swal-anims",a.textContent=`
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
  `,document.head.appendChild(a)})();function Ae(e){const a=e.extendedProps||{};if(Array.isArray(a.planillas_ids)&&a.planillas_ids.length)return a.planillas_ids;const t=(e.id||"").match(/planilla-(\d+)/);return t?[Number(t[1])]:[]}async function De(e,a){var t,o;try{A()}catch{}if(!e)return Swal.fire("⚠️","ID de salida inválido.","warning");try{const n=await fetch(`${(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.informacionPaquetesSalida}?salida_id=${e}`,{headers:{Accept:"application/json"}});if(!n.ok)throw new Error("Error al cargar información de la salida");const{salida:i,paquetesAsignados:r,paquetesDisponibles:d,paquetesTodos:l,filtros:u}=await n.json();Pe(i,r,d,l||[],u||{obras:[],planillas:[],obrasRelacionadas:[]},a)}catch(n){console.error(n),Swal.fire("❌","Error al cargar la información de la salida","error")}}function Pe(e,a,t,o,n,i){window._gestionPaquetesData={salida:e,paquetesAsignados:a,paquetesDisponibles:t,paquetesTodos:o,filtros:n,mostrarTodos:!1};const r=Ie(e,a,t,n);Swal.fire({title:`📦 Gestionar Paquetes - Salida ${e.codigo_salida||e.id}`,html:r,width:Math.min(window.innerWidth*.95,1200),showConfirmButton:!0,showCancelButton:!0,confirmButtonText:"💾 Guardar Cambios",cancelButtonText:"Cancelar",focusConfirm:!1,customClass:{popup:"w-full max-w-screen-xl"},didOpen:()=>{fe(),Me(),je(),setTimeout(()=>{ze()},100)},willClose:()=>{q.cleanup&&q.cleanup();const d=document.getElementById("modal-keyboard-indicator");d&&d.remove()},preConfirm:()=>He()}).then(async d=>{d.isConfirmed&&d.value&&await We(e.id,d.value,i)})}function Ie(e,a,t,o){var u,p;const n=a.reduce((h,b)=>h+(parseFloat(b.peso)||0),0);let i="";e.salida_clientes&&e.salida_clientes.length>0&&(i='<div class="col-span-2"><strong>Obras/Clientes:</strong><br>',e.salida_clientes.forEach(h=>{var f,g,v,k,y;const b=((f=h.obra)==null?void 0:f.obra)||"Obra desconocida",s=(g=h.obra)!=null&&g.cod_obra?`(${h.obra.cod_obra})`:"",c=((v=h.cliente)==null?void 0:v.empresa)||((y=(k=h.obra)==null?void 0:k.cliente)==null?void 0:y.empresa)||"";i+=`<span class="text-xs">• ${b} ${s}`,c&&(i+=` - ${c}`),i+="</span><br>"}),i+="</div>");const r=`
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><strong>Código:</strong> ${e.codigo_salida||"N/A"}</div>
                <div><strong>Código SAGE:</strong> ${e.codigo_sage||"Sin asignar"}</div>
                <div><strong>Fecha salida:</strong> ${new Date(e.fecha_salida).toLocaleString("es-ES")}</div>
                <div><strong>Estado:</strong> ${e.estado||"pendiente"}</div>
                <div><strong>Empresa transporte:</strong> ${((u=e.empresa_transporte)==null?void 0:u.nombre)||"Sin asignar"}</div>
                <div><strong>Camión:</strong> ${((p=e.camion)==null?void 0:p.modelo)||"Sin asignar"}</div>
                ${i}
            </div>
        </div>
    `,d=((o==null?void 0:o.obras)||[]).map(h=>`<option value="${h.id}">${h.cod_obra||""} - ${h.obra||"Sin nombre"}</option>`).join(""),l=((o==null?void 0:o.planillas)||[]).map(h=>`<option value="${h.id}" data-obra-id="${h.obra_id||""}">${h.codigo||"Sin código"}</option>`).join("");return`
        <div class="text-left">
            ${r}

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
                        ${Q(a)}
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
                                    ${l}
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
                        ${Q(t)}
                    </div>
                </div>
            </div>
        </div>
    `}function Q(e){return!e||e.length===0?'<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>':e.map(a=>{var t,o,n,i,r,d,l,u,p,h,b,s,c,f,g,v;return`
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${a.id}"
            data-peso="${a.peso||0}"
            data-obra-id="${((o=(t=a.planilla)==null?void 0:t.obra)==null?void 0:o.id)||""}"
            data-obra="${((i=(n=a.planilla)==null?void 0:n.obra)==null?void 0:i.obra)||""}"
            data-planilla-id="${((r=a.planilla)==null?void 0:r.id)||""}"
            data-planilla="${((d=a.planilla)==null?void 0:d.codigo)||""}"
            data-cliente="${((u=(l=a.planilla)==null?void 0:l.cliente)==null?void 0:u.empresa)||""}"
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
                <div>📄 ${((p=a.planilla)==null?void 0:p.codigo)||a.planilla_id}</div>
                <div>🏗️ ${((b=(h=a.planilla)==null?void 0:h.obra)==null?void 0:b.cod_obra)||""} - ${((c=(s=a.planilla)==null?void 0:s.obra)==null?void 0:c.obra)||"N/A"}</div>
                <div>👤 ${((g=(f=a.planilla)==null?void 0:f.cliente)==null?void 0:g.empresa)||"Sin cliente"}</div>
                ${(v=a.nave)!=null&&v.obra?`<div class="text-blue-600 font-medium">📍 ${a.nave.obra}</div>`:""}
            </div>
        </div>
    `}).join("")}async function Fe(e){var a;try{const t=document.querySelector(`[data-paquete-id="${e}"]`);let o=null;if(t&&t.dataset.paqueteJson)try{o=JSON.parse(t.dataset.paqueteJson.replace(/&#39;/g,"'"))}catch(l){console.warn("No se pudo parsear JSON del paquete",l)}if(!o){const l=await fetch(`/api/paquetes/${e}/elementos`);l.ok&&(o=await l.json())}if(!o){alert("No se pudo obtener información del paquete");return}const n=[];if(o.etiquetas&&o.etiquetas.length>0&&o.etiquetas.forEach(l=>{l.elementos&&l.elementos.length>0&&l.elementos.forEach(u=>{n.push({id:u.id,dimensiones:u.dimensiones,peso:u.peso,longitud:u.longitud,diametro:u.diametro})})}),n.length===0){alert("Este paquete no tiene elementos para mostrar");return}const i=n.map((l,u)=>`
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-2">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-gray-700">Elemento #${l.id}</span>
                    <span class="text-xs text-gray-500">${u+1} de ${n.length}</span>
                </div>
                <div class="mt-2 text-sm text-gray-600 grid grid-cols-2 gap-2">
                    ${l.diametro?`<div><strong>Ø:</strong> ${l.diametro} mm</div>`:""}
                    ${l.longitud?`<div><strong>Long:</strong> ${l.longitud} mm</div>`:""}
                    ${l.peso?`<div><strong>Peso:</strong> ${parseFloat(l.peso).toFixed(2)} kg</div>`:""}
                </div>
                ${l.dimensiones?`
                    <div class="mt-2 p-2 bg-white border rounded">
                        <div id="elemento-dibujo-${l.id}" class="w-full h-32"></div>
                    </div>
                `:""}
            </div>
        `).join(""),r=document.getElementById("modal-elementos-paquete-overlay");r&&r.remove();const d=`
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
                        ${i}
                    </div>
                    <div class="p-4 border-t bg-gray-50 rounded-b-lg">
                        <button onclick="document.getElementById('modal-elementos-paquete-overlay').remove()"
                                class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        `;document.body.insertAdjacentHTML("beforeend",d),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&n.forEach(l=>{l.dimensiones&&window.dibujarFiguraElemento(`elemento-dibujo-${l.id}`,l.dimensiones,null)})},100)}catch(t){console.error("Error al ver elementos del paquete:",t),alert("Error al cargar los elementos del paquete")}}window.verElementosPaqueteSalida=Fe;function Me(e){const a=document.getElementById("filtro-obra-modal"),t=document.getElementById("filtro-planilla-modal"),o=document.getElementById("btn-limpiar-filtros-modal");a&&a.addEventListener("change",()=>{ne(),Z()}),t&&t.addEventListener("change",()=>{Z()}),o&&o.addEventListener("click",()=>{a&&(a.value=""),t&&(t.value=""),ne(),Z()})}function ne(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData;if(!a||!t)return;const o=(e==null?void 0:e.value)||"",n=o?t.paquetesTodos:t.paquetesDisponibles,i=new Map;n.forEach(l=>{var u,p,h;if((u=l.planilla)!=null&&u.id){if(o&&String((p=l.planilla.obra)==null?void 0:p.id)!==o)return;i.has(l.planilla.id)||i.set(l.planilla.id,{id:l.planilla.id,codigo:l.planilla.codigo||"Sin código",obra_id:(h=l.planilla.obra)==null?void 0:h.id})}});const r=Array.from(i.values()).sort((l,u)=>(l.codigo||"").localeCompare(u.codigo||"")),d=a.value;a.innerHTML='<option value="">-- Todas las planillas --</option>',r.forEach(l=>{const u=document.createElement("option");u.value=l.id,u.textContent=l.codigo,a.appendChild(u)}),d&&i.has(parseInt(d))?a.value=d:a.value=""}function Z(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=window._gestionPaquetesData,o=(e==null?void 0:e.value)||"",n=(a==null?void 0:a.value)||"",i=document.querySelector('[data-zona="disponibles"]');if(!i||!t)return;const r=document.querySelector('[data-zona="asignados"]'),d=new Set;r&&r.querySelectorAll(".paquete-item-salida").forEach(p=>{d.add(parseInt(p.dataset.paqueteId))});let u=(o?t.paquetesTodos:t.paquetesDisponibles).filter(p=>{var h,b,s;return!(d.has(p.id)||o&&String((b=(h=p.planilla)==null?void 0:h.obra)==null?void 0:b.id)!==o||n&&String((s=p.planilla)==null?void 0:s.id)!==n)});i.innerHTML=Q(u),fe(),u.length===0&&(i.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">No hay paquetes que coincidan con el filtro</div>')}let q={zonaActiva:"asignados",indiceFocused:-1,cleanup:null};function ze(){q.cleanup&&q.cleanup(),q.zonaActiva="asignados",q.indiceFocused=0,D();function e(a){var h;if(!document.querySelector(".swal2-container"))return;const t=a.target.tagName.toLowerCase(),o=t==="select";if((t==="input"||t==="textarea")&&a.key!=="Escape")return;const i=document.querySelector('[data-zona="asignados"]'),r=document.querySelector('[data-zona="disponibles"]');if(!i||!r)return;const d=q.zonaActiva==="asignados"?i:r,l=Array.from(d.querySelectorAll('.paquete-item-salida:not([style*="display: none"])')),u=l.length;let p=!1;if(!o)switch(a.key){case"ArrowDown":u>0&&(q.indiceFocused=(q.indiceFocused+1)%u,D(),p=!0);break;case"ArrowUp":u>0&&(q.indiceFocused=q.indiceFocused<=0?u-1:q.indiceFocused-1,D(),p=!0);break;case"ArrowLeft":case"ArrowRight":q.zonaActiva=q.zonaActiva==="asignados"?"disponibles":"asignados",q.indiceFocused=0,D(),p=!0;break;case"Tab":a.preventDefault(),q.zonaActiva=q.zonaActiva==="asignados"?"disponibles":"asignados",q.indiceFocused=0,D(),p=!0;break;case"Enter":{if(u>0&&q.indiceFocused>=0){const b=l[q.indiceFocused];if(b){Be(b);const s=Array.from(d.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));q.indiceFocused>=s.length&&(q.indiceFocused=Math.max(0,s.length-1)),D(),p=!0}}break}case"Home":q.indiceFocused=0,D(),p=!0;break;case"End":q.indiceFocused=Math.max(0,u-1),D(),p=!0;break}if(p){a.preventDefault(),a.stopPropagation();return}switch(a.key){case"o":case"O":{const b=document.getElementById("filtro-obra-modal");b&&(b.focus(),p=!0);break}case"p":case"P":{const b=document.getElementById("filtro-planilla-modal");b&&(b.focus(),p=!0);break}case"l":case"L":{const b=document.getElementById("btn-limpiar-filtros-modal");b&&(b.click(),(h=document.activeElement)==null||h.blur(),D(),p=!0);break}case"/":case"f":case"F":{const b=document.getElementById("filtro-obra-modal");b&&(b.focus(),p=!0);break}case"Escape":o&&(document.activeElement.blur(),D(),p=!0);break;case"s":case"S":{if(a.ctrlKey||a.metaKey){const b=document.querySelector(".swal2-confirm");b&&(b.click(),p=!0)}break}}p&&(a.preventDefault(),a.stopPropagation())}document.addEventListener("keydown",e,!0),q.cleanup=()=>{document.removeEventListener("keydown",e,!0),pe()}}function D(){pe();const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;q.zonaActiva==="asignados"?(e.classList.add("zona-activa-keyboard"),a.classList.remove("zona-activa-keyboard")):(a.classList.add("zona-activa-keyboard"),e.classList.remove("zona-activa-keyboard"));const t=q.zonaActiva==="asignados"?e:a,o=Array.from(t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));if(o.length>0&&q.indiceFocused>=0){const n=Math.min(q.indiceFocused,o.length-1),i=o[n];i&&(i.classList.add("paquete-focused-keyboard"),i.scrollIntoView({behavior:"smooth",block:"nearest"}))}Oe()}function pe(){document.querySelectorAll(".paquete-focused-keyboard").forEach(e=>{e.classList.remove("paquete-focused-keyboard")}),document.querySelectorAll(".zona-activa-keyboard").forEach(e=>{e.classList.remove("zona-activa-keyboard")})}function Be(e){const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!a||!t)return;const o=e.closest("[data-zona]"),n=o.dataset.zona==="asignados"?t:a,i=n.querySelector(".placeholder-sin-paquetes");if(i&&i.remove(),n.appendChild(e),o.querySelectorAll(".paquete-item-salida").length===0){const d=document.createElement("div");d.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",d.textContent="Sin paquetes",o.appendChild(d)}me(e),V()}function Oe(){let e=document.getElementById("modal-keyboard-indicator");e||(e=document.createElement("div"),e.id="modal-keyboard-indicator",e.className="fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs",document.body.appendChild(e));const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]'),o=(a==null?void 0:a.querySelectorAll(".paquete-item-salida").length)||0,n=(t==null?void 0:t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0,i=q.zonaActiva==="asignados"?`📦 Asignados (${o})`:`📋 Disponibles (${n})`;e.innerHTML=`
        <div class="flex items-center gap-2 mb-2">
            <span class="${q.zonaActiva==="asignados"?"bg-green-500":"bg-gray-500"} text-white text-xs px-2 py-0.5 rounded">${i}</span>
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
    `,clearTimeout(e._checkTimeout),e._checkTimeout=setTimeout(()=>{document.querySelector(".swal2-container")||e.remove()},500)}function je(){if(document.getElementById("modal-keyboard-styles"))return;const e=document.createElement("style");e.id="modal-keyboard-styles",e.textContent=`
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
    `,document.head.appendChild(e)}function me(e){e.addEventListener("dragstart",a=>{e.style.opacity="0.5",a.dataTransfer.setData("text/plain",e.dataset.paqueteId)}),e.addEventListener("dragend",a=>{e.style.opacity="1"})}function fe(){document.querySelectorAll(".paquete-item-salida").forEach(e=>{me(e)}),document.querySelectorAll(".drop-zone").forEach(e=>{e.addEventListener("dragover",a=>{a.preventDefault();const t=e.dataset.zona;e.style.backgroundColor=t==="asignados"?"#d1fae5":"#e0f2fe"}),e.addEventListener("dragleave",a=>{e.style.backgroundColor=""}),e.addEventListener("drop",a=>{a.preventDefault(),e.style.backgroundColor="";const t=a.dataTransfer.getData("text/plain"),o=document.querySelector(`.paquete-item-salida[data-paquete-id="${t}"]`);if(o){const n=e.querySelector(".placeholder-sin-paquetes");n&&n.remove(),e.appendChild(o),V()}})})}function V(){const e=document.querySelector('[data-zona="asignados"]'),a=e==null?void 0:e.querySelectorAll(".paquete-item-salida");let t=0;a==null||a.forEach(n=>{const i=parseFloat(n.dataset.peso)||0;t+=i});const o=document.getElementById("peso-asignados");o&&(o.textContent=`${t.toFixed(2)} kg`)}function Ne(){const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;const t=e.querySelectorAll(".paquete-item-salida");if(t.length===0)return;t.forEach(n=>{a.appendChild(n)});const o=a.querySelector(".placeholder-sin-paquetes");o&&o.remove(),e.querySelectorAll(".paquete-item-salida").length===0&&(e.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),V()}function Re(){const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;const t=Array.from(a.querySelectorAll(".paquete-item-salida")).filter(i=>i.style.display!=="none");if(t.length===0)return;const o=e.querySelector(".placeholder-sin-paquetes");o&&o.remove(),t.forEach(i=>{e.appendChild(i)}),a.querySelectorAll(".paquete-item-salida").length===0&&(a.innerHTML='<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>'),V()}window.vaciarSalidaModal=Ne;window.volcarTodosASalidaModal=Re;function He(){const e=document.querySelector('[data-zona="asignados"]');return{paquetes_ids:Array.from((e==null?void 0:e.querySelectorAll(".paquete-item-salida"))||[]).map(t=>parseInt(t.dataset.paqueteId))}}async function We(e,a,t){var o,n,i,r;try{const l=await(await fetch((n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.guardarPaquetesSalida,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(i=window.AppSalidas)==null?void 0:i.csrf},body:JSON.stringify({salida_id:e,paquetes_ids:a.paquetes_ids})})).json();l.success?(await Swal.fire({icon:"success",title:"Cambios Guardados",text:"Los paquetes de la salida se han actualizado correctamente.",timer:2e3}),t&&(t.refetchEvents(),(r=t.refetchResources)==null||r.call(t))):await Swal.fire("⚠️",l.message||"No se pudieron guardar los cambios","warning")}catch(d){console.error(d),Swal.fire("❌","Error al guardar los paquetes","error")}}async function Ge(e,a,t){try{A()}catch{}window.Livewire.dispatch("abrirComentario",{salidaId:e}),window._calendarRef=t}function Ve(e){return e?typeof e=="string"?e.split(",").map(t=>t.trim()).filter(Boolean):Array.from(e).map(t=>typeof t=="object"&&(t==null?void 0:t.id)!=null?t.id:t).map(String).map(t=>t.trim()).filter(Boolean):[]}async function Xe(e){var i,r;const a=(r=(i=window.AppSalidas)==null?void 0:i.routes)==null?void 0:r.informacionPlanillas;if(!a)throw new Error("Ruta 'informacionPlanillas' no configurada");const t=`${a}?ids=${encodeURIComponent(e.join(","))}`,o=await fetch(t,{headers:{Accept:"application/json"}});if(!o.ok){const d=await o.text().catch(()=>"");throw new Error(`GET ${t} -> ${o.status} ${d}`)}const n=await o.json();return Array.isArray(n==null?void 0:n.planillas)?n.planillas:[]}function ge(e){if(!e)return!1;const t=new Date(e+"T00:00:00").getDay();return t===0||t===6}function Ye(e,a,t,o){const n=document.getElementById("modal-figura-elemento-overlay");n&&n.remove();const i=o.getBoundingClientRect(),r=320,d=240;let l=i.right+10;l+r>window.innerWidth&&(l=i.left-r-10);let u=i.top-d/2+i.height/2;u<10&&(u=10),u+d>window.innerHeight-10&&(u=window.innerHeight-d-10);const p=`
        <div id="modal-figura-elemento-overlay"
             class="fixed bg-white rounded-lg shadow-2xl border border-gray-300"
             style="z-index: 10001; left: ${l}px; top: ${u}px; width: ${r}px;"
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
    `;document.body.insertAdjacentHTML("beforeend",p),setTimeout(()=>{typeof window.dibujarFiguraElemento=="function"&&window.dibujarFiguraElemento(`figura-elemento-container-${e}`,t,null)},50)}function Ke(e){return`
    <div class="text-left">
      <div class="text-sm text-gray-600 mb-2">
        Edita la <strong>fecha estimada de entrega</strong> de planillas y elementos.
        Clic en <span class="text-blue-600 font-medium">fila azul</span> = expandir, clic en <span class="text-gray-600 font-medium">fila gris</span> = seleccionar
      </div>

      <!-- Sumatorio dinámico por fechas con barra de acciones integrada -->
      <div id="sumatorio-fechas" class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <div class="text-sm font-medium text-blue-800 mb-2">📊 Resumen por fecha:</div>
            <div id="resumen-contenido" class="text-xs text-blue-700">
              Cambia las fechas para ver el resumen...
            </div>
          </div>
          <!-- Barra de acciones masivas para elementos -->
          <div id="barra-acciones-masivas" class="hidden flex-shrink-0 p-2 bg-purple-50 border border-purple-200 rounded-lg">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-xs font-medium text-purple-800 whitespace-nowrap">
                <span id="contador-seleccionados">0</span> sel.
              </span>
              <input type="date" id="fecha-masiva" class="swal2-input !m-0 !w-auto !text-xs !py-1 !px-2 !bg-white !border-purple-300">
              <button type="button" id="aplicar-fecha-masiva" class="text-xs bg-purple-600 hover:bg-purple-700 text-white px-2 py-1 rounded font-medium transition-colors whitespace-nowrap">
                Aplicar
              </button>
              <button type="button" id="limpiar-fecha-seleccionados" class="text-xs bg-gray-500 hover:bg-gray-600 text-white px-2 py-1 rounded whitespace-nowrap" title="Quitar fecha de los seleccionados">
                Limpiar
              </button>
              <button type="button" id="deseleccionar-todos" class="text-xs bg-gray-400 hover:bg-gray-500 text-white px-2 py-1 rounded whitespace-nowrap">
                ✕
              </button>
            </div>
          </div>
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
          <tbody>${e.map((t,o)=>{var s,c,f;const n=((s=t.obra)==null?void 0:s.codigo)||"",i=((c=t.obra)==null?void 0:c.nombre)||"",r=t.seccion||"";t.descripcion;const d=t.codigo||`Planilla ${t.id}`,l=t.peso_total?parseFloat(t.peso_total).toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})+" kg":"",u=Te(t.fecha_estimada_entrega),p=t.elementos&&t.elementos.length>0,h=((f=t.elementos)==null?void 0:f.length)||0;let b="";return p&&(b=t.elementos.map((g,v)=>{const k=g.fecha_entrega||"",y=g.peso?parseFloat(g.peso).toFixed(2):"-",E=g.codigo||"-",$=g.dimensiones&&g.dimensiones.trim()!=="",m=$?g.dimensiones.replace(/"/g,"&quot;").replace(/'/g,"&#39;"):"",w=E.replace(/"/g,"&quot;").replace(/'/g,"&#39;");return`
                    <tr class="elemento-row elemento-planilla-${t.id} bg-gray-50 hidden cursor-pointer hover:bg-purple-50" data-elemento-id="${g.id}" data-planilla-id="${t.id}">
                        <td class="px-2 py-1 text-xs text-gray-400 pl-4">
                            <div class="flex items-center gap-1">
                                <input type="checkbox" class="elemento-checkbox rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-3.5 w-3.5 pointer-events-none"
                                       data-elemento-id="${g.id}"
                                       data-planilla-id="${t.id}">
                                <span>↳</span>
                                <span class="font-medium text-gray-600">${E}</span>
                                ${$?`
                                <button type="button"
                                        class="ver-figura-elemento text-blue-500 hover:text-blue-700 hover:bg-blue-100 rounded p-0.5 transition-colors"
                                        data-elemento-id="${g.id}"
                                        data-elemento-codigo="${w}"
                                        data-dimensiones="${m}"
                                        title="Ver figura">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                                `:""}
                            </div>
                        </td>
                        <td class="px-2 py-1 text-xs text-gray-500">${g.marca||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">Ø${g.diametro||"-"}</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${g.longitud||"-"} mm</td>
                        <td class="px-2 py-1 text-xs text-gray-500">${g.barras||"-"} uds</td>
                        <td class="px-2 py-1 text-xs text-right text-gray-500">${y} kg</td>
                        <td class="px-2 py-1" colspan="2">
                            <input type="date" class="swal2-input !m-0 !w-auto !text-xs elemento-fecha"
                                   data-elemento-id="${g.id}"
                                   data-planilla-id="${t.id}"
                                   value="${k}">
                        </td>
                    </tr>`}).join("")),`
<tr class="planilla-row hover:bg-blue-50 bg-blue-100 border-t border-blue-200 ${p?"cursor-pointer":""}" data-planilla-id="${t.id}" data-tiene-elementos="${p}" style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${o*18}ms;">
  <td class="px-2 py-2 text-xs font-semibold text-blue-800" colspan="2">
    ${p?`<span class="toggle-elementos mr-1 text-blue-600" data-planilla-id="${t.id}">▶</span>`:""}
    📄 ${d}
    ${p?`<span class="ml-1 text-xs text-blue-500 font-normal">(${h} elem.)</span>`:""}
  </td>
  <td class="px-2 py-2 text-xs text-blue-700" colspan="2">
    <span class="font-medium">${n}</span> ${i}
  </td>
  <td class="px-2 py-2 text-xs text-blue-600">${r||"-"}</td>
  <td class="px-2 py-2 text-xs text-right font-semibold text-blue-800">${l}</td>
  <td class="px-2 py-2" colspan="2">
    <div class="flex items-center gap-1">
      <input type="date" class="swal2-input !m-0 !w-auto planilla-fecha !bg-blue-50 !border-blue-300" data-planilla-id="${t.id}" value="${u}">
      ${p?`<button type="button" class="aplicar-fecha-elementos text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded" data-planilla-id="${t.id}" title="Aplicar fecha a todos los elementos">↓</button>`:""}
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
    </div>`}function Je(e){const a={};return document.querySelectorAll('input[type="date"][data-planilla-id]').forEach(o=>{const n=parseInt(o.dataset.planillaId),i=o.value,r=e.find(d=>d.id===n);i&&r&&r.peso_total&&(a[i]||(a[i]={peso:0,planillas:0,esFinDeSemana:ge(i)}),a[i].peso+=parseFloat(r.peso_total),a[i].planillas+=1)}),a}function se(e){const a=Je(e),t=document.getElementById("resumen-contenido");if(!t)return;const o=Object.keys(a).sort();if(o.length===0){t.innerHTML='<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';return}const n=o.map(d=>{const l=a[d],u=new Date(d+"T00:00:00").toLocaleDateString("es-ES",{weekday:"short",day:"2-digit",month:"2-digit",year:"numeric"}),p=l.peso.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2}),h=l.esFinDeSemana?"bg-orange-100 border-orange-300 text-orange-800":"bg-green-100 border-green-300 text-green-800",b=l.esFinDeSemana?"🏖️":"📦";return`
            <div class="inline-block m-1 px-2 py-1 rounded border ${h}">
                <span class="font-medium">${b} ${u}</span>
                <br>
                <span class="text-xs">${p} kg (${l.planillas} planilla${l.planillas!==1?"s":""})</span>
            </div>
        `}).join(""),i=o.reduce((d,l)=>d+a[l].peso,0),r=o.reduce((d,l)=>d+a[l].planillas,0);t.innerHTML=`
        <div class="mb-2">${n}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${i.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})} kg 
            (${r} planilla${r!==1?"s":""})
        </div>
    `}function Ue(e,a){F();const t=new Set(a.eventos_eliminar||[]);(a.eventos_nuevos||[]).forEach(o=>t.add(o.id)),t.forEach(o=>{const n=e.getEventById(o);n&&n.remove()}),a.eventos_nuevos&&Array.isArray(a.eventos_nuevos)&&a.eventos_nuevos.forEach(o=>{const n=e.getEventById(o.id);n&&n.remove(),e.addEvent(o)}),a.resumenes_dias&&typeof window.actualizarResumenesDias=="function"&&window.actualizarResumenesDias(a.resumenes_dias),e.render()}async function Ze(e){var o,n,i;const a=(n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.actualizarFechasPlanillas;if(!a)throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");const t=await fetch(a,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(i=window.AppSalidas)==null?void 0:i.csrf,Accept:"application/json"},body:JSON.stringify({planillas:e})});if(!t.ok){const r=await t.text().catch(()=>"");throw new Error(`PUT ${a} -> ${t.status} ${r}`)}return t.json().catch(()=>({}))}async function Qe(e,a){try{const t=Array.from(new Set(Ve(e))).map(Number).filter(Boolean);if(!t.length)return Swal.fire("⚠️","No hay planillas en la agrupación.","warning");const o=await Xe(t);if(!o.length)return Swal.fire("⚠️","No se han encontrado planillas.","warning");const i=`
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `+Ke(o),{isConfirmed:r}=await Swal.fire({title:"",html:i,width:Math.min(window.innerWidth*.98,1200),customClass:{popup:"w-full max-w-screen-xl"},showCancelButton:!0,confirmButtonText:"💾 Guardar",cancelButtonText:"Cancelar",focusConfirm:!1,showClass:{popup:"swal-fade-in-zoom"},hideClass:{popup:"swal-fade-out"},didOpen:h=>{var f,g,v,k,y,E,$;et(h),W("#swal-drag",!1),setTimeout(()=>{const m=Swal.getHtmlContainer().querySelector('input[type="date"]');m==null||m.focus({preventScroll:!0})},120),Swal.getHtmlContainer().querySelectorAll('input[type="date"]').forEach(m=>{m.addEventListener("change",function(){ge(this.value)?this.classList.add("weekend-date"):this.classList.remove("weekend-date"),se(o)})});const s=Swal.getHtmlContainer();s.querySelectorAll(".planilla-row").forEach(m=>{m.addEventListener("click",w=>{if(w.target.closest("input, button"))return;const S=m.dataset.planillaId;if(!(m.dataset.tieneElementos==="true"))return;const L=s.querySelectorAll(`.elemento-planilla-${S}`),_=m.querySelector(".toggle-elementos"),T=(_==null?void 0:_.textContent)==="▼";L.forEach(P=>{P.classList.toggle("hidden",T)}),_&&(_.textContent=T?"▶":"▼")})}),s.querySelectorAll(".elemento-row").forEach(m=>{m.addEventListener("click",w=>{if(w.target.closest("input, button, .ver-figura-elemento"))return;const S=m.querySelector(".elemento-checkbox");S&&(S.checked=!S.checked,c())})}),(f=s.querySelector("#expandir-todos"))==null||f.addEventListener("click",()=>{s.querySelectorAll(".elemento-row").forEach(m=>m.classList.remove("hidden")),s.querySelectorAll(".toggle-elementos").forEach(m=>m.textContent="▼")}),(g=s.querySelector("#colapsar-todos"))==null||g.addEventListener("click",()=>{s.querySelectorAll(".elemento-row").forEach(m=>m.classList.add("hidden")),s.querySelectorAll(".toggle-elementos").forEach(m=>m.textContent="▶")});function c(){const w=s.querySelectorAll(".elemento-checkbox:checked").length,S=s.querySelector("#barra-acciones-masivas"),C=s.querySelector("#contador-seleccionados");w>0?(S==null||S.classList.remove("hidden"),C&&(C.textContent=w)):S==null||S.classList.add("hidden")}s.querySelectorAll(".elemento-checkbox").forEach(m=>{m.addEventListener("change",c)}),(v=s.querySelector("#seleccionar-todos-elementos"))==null||v.addEventListener("click",()=>{s.querySelectorAll(".elemento-row").forEach(m=>m.classList.remove("hidden")),s.querySelectorAll(".toggle-elementos").forEach(m=>m.textContent="▼"),s.querySelectorAll(".elemento-checkbox").forEach(m=>{m.checked=!0}),c()}),(k=s.querySelector("#seleccionar-sin-fecha"))==null||k.addEventListener("click",()=>{s.querySelectorAll(".elemento-row").forEach(m=>m.classList.remove("hidden")),s.querySelectorAll(".toggle-elementos").forEach(m=>m.textContent="▼"),s.querySelectorAll(".elemento-checkbox").forEach(m=>{m.checked=!1}),s.querySelectorAll(".elemento-checkbox").forEach(m=>{const w=m.dataset.elementoId,S=s.querySelector(`.elemento-fecha[data-elemento-id="${w}"]`);S&&!S.value&&(m.checked=!0)}),c()}),(y=s.querySelector("#deseleccionar-todos"))==null||y.addEventListener("click",()=>{s.querySelectorAll(".elemento-checkbox").forEach(m=>{m.checked=!1}),c()}),(E=s.querySelector("#aplicar-fecha-masiva"))==null||E.addEventListener("click",()=>{var L;const m=(L=s.querySelector("#fecha-masiva"))==null?void 0:L.value;if(!m){alert("Por favor, selecciona una fecha para aplicar");return}s.querySelectorAll(".elemento-checkbox:checked").forEach(_=>{const T=_.dataset.elementoId,P=s.querySelector(`.elemento-fecha[data-elemento-id="${T}"]`);P&&(P.value=m,P.dispatchEvent(new Event("change")))});const S=s.querySelector("#aplicar-fecha-masiva"),C=S.textContent;S.textContent="✓ Aplicado",S.classList.add("bg-green-600"),setTimeout(()=>{S.textContent=C,S.classList.remove("bg-green-600")},1500)}),($=s.querySelector("#limpiar-fecha-seleccionados"))==null||$.addEventListener("click",()=>{s.querySelectorAll(".elemento-checkbox:checked").forEach(w=>{const S=w.dataset.elementoId,C=s.querySelector(`.elemento-fecha[data-elemento-id="${S}"]`);C&&(C.value="",C.dispatchEvent(new Event("change")))})}),s.querySelectorAll(".aplicar-fecha-elementos").forEach(m=>{m.addEventListener("click",w=>{var L;w.stopPropagation();const S=m.dataset.planillaId,C=(L=s.querySelector(`.planilla-fecha[data-planilla-id="${S}"]`))==null?void 0:L.value;C&&s.querySelectorAll(`.elemento-fecha[data-planilla-id="${S}"]`).forEach(_=>{_.value=C,_.dispatchEvent(new Event("change"))})})}),s.querySelectorAll(".ver-figura-elemento").forEach(m=>{m.addEventListener("mouseenter",w=>{var _,T;const S=m.dataset.elementoId,C=((_=m.dataset.elementoCodigo)==null?void 0:_.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"",L=((T=m.dataset.dimensiones)==null?void 0:T.replace(/&quot;/g,'"').replace(/&#39;/g,"'"))||"";typeof window.dibujarFiguraElemento=="function"&&Ye(S,C,L,m)}),m.addEventListener("mouseleave",w=>{setTimeout(()=>{const S=document.getElementById("modal-figura-elemento-overlay");S&&!S.matches(":hover")&&S.remove()},100)}),m.addEventListener("click",w=>{w.stopPropagation()})}),setTimeout(()=>{se(o)},100)}});if(!r)return;const d=Swal.getHtmlContainer(),l=d.querySelectorAll(".planilla-fecha"),u=Array.from(l).map(h=>{const b=Number(h.getAttribute("data-planilla-id")),s=d.querySelectorAll(`.elemento-fecha[data-planilla-id="${b}"]`),c=Array.from(s).map(f=>({id:Number(f.getAttribute("data-elemento-id")),fecha_entrega:f.value||null}));return{id:b,fecha_estimada_entrega:h.value,elementos:c.length>0?c:void 0}}),p=await Ze(u);await Swal.fire({title:p.success?"Guardado":"Atención",text:p.message||(p.success?"Fechas actualizadas":"No se pudieron actualizar"),icon:p.success?"success":"warning",timer:p.success?1500:void 0,showConfirmButton:!p.success}),p.success&&a&&Ue(a,p)}catch(t){console.error("[CambiarFechasEntrega] error:",t),Swal.fire("❌",(t==null?void 0:t.message)||"Ocurrió un error al actualizar las fechas.","error")}}function ie(e,a){e.el.addEventListener("mousedown",A),e.el.addEventListener("contextmenu",t=>{t.preventDefault(),t.stopPropagation();const o=e.event,n=o.extendedProps||{},i=n.tipo||"planilla";let r="";if(i==="salida"){if(n.clientes&&Array.isArray(n.clientes)&&n.clientes.length>0){const u=n.clientes.map(p=>p.nombre).filter(Boolean).join(", ");u&&(r+=`<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${u}</span>`)}n.obras&&Array.isArray(n.obras)&&n.obras.length>0&&(r+='<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ ',r+=n.obras.map(u=>{const p=u.codigo?`(${u.codigo})`:"";return`${u.nombre} ${p}`}).join(", "),r+="</span>")}const d=`
      <div style="padding:10px 12px; font-weight:600;">
        ${o.title??"Evento"}${r}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(o.start).toLocaleString()} — ${new Date(o.end).toLocaleString()}
        </span>
      </div>
    `;let l=[];if(i==="planilla"){const u=Ae(o);l=[{label:"Gestionar Salidas y Paquetes",icon:"📦",onClick:()=>window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${u.join(",")}`},{label:"Cambiar fechas de entrega",icon:"🗓️",onClick:()=>Qe(u,a)}]}else if(i==="salida"){const u=n.salida_id||o.id;n.empresa_id,n.empresa,l=[{label:"Abrir salida",icon:"🧾",onClick:()=>window.open(`/salidas-ferralla/${u}`,"_blank")},{label:"Gestionar paquetes",icon:"📦",onClick:()=>De(u,a)},{label:"Agregar comentario",icon:"✍️",onClick:()=>Ge(u,n.comentario||"",a)}]}else l=[{label:"Abrir",icon:"🧾",onClick:()=>window.open(n.url||"#","_blank")}];_e(t.clientX,t.clientY,{headerHtml:d,items:l})})}function et(e){e.style.transform="none",e.style.position="fixed",e.style.margin="0";const a=e.offsetWidth,t=e.offsetHeight,o=Math.max(0,Math.round((window.innerWidth-a)/2)),n=Math.max(0,Math.round((window.innerHeight-t)/2));e.style.left=`${o}px`,e.style.top=`${n}px`}function W(e=".swal2-title",a=!1){const t=Swal.getPopup(),o=Swal.getHtmlContainer();let n=(e?(o==null?void 0:o.querySelector(e))||(t==null?void 0:t.querySelector(e)):null)||t;if(!t||!n)return;a&&W.__lastPos&&(t.style.left=W.__lastPos.left,t.style.top=W.__lastPos.top,t.style.transform="none"),n.style.cursor="move",n.style.touchAction="none";const i=c=>{var f;return((f=c.closest)==null?void 0:f.call(c,"input, textarea, select, button, a, label, [contenteditable]"))!=null};let r=!1,d=0,l=0,u=0,p=0;const h=c=>{if(!n.contains(c.target)||i(c.target))return;r=!0,document.body.style.userSelect="none";const f=t.getBoundingClientRect();t.style.left=`${f.left}px`,t.style.top=`${f.top}px`,t.style.transform="none",u=parseFloat(t.style.left||f.left),p=parseFloat(t.style.top||f.top),d=c.clientX,l=c.clientY,document.addEventListener("pointermove",b),document.addEventListener("pointerup",s,{once:!0})},b=c=>{if(!r)return;const f=c.clientX-d,g=c.clientY-l;let v=u+f,k=p+g;const y=t.offsetWidth,E=t.offsetHeight,$=-y+40,m=window.innerWidth-40,w=-E+40,S=window.innerHeight-40;v=Math.max($,Math.min(m,v)),k=Math.max(w,Math.min(S,k)),t.style.left=`${v}px`,t.style.top=`${k}px`},s=()=>{r=!1,document.body.style.userSelect="",a&&(W.__lastPos={left:t.style.left,top:t.style.top}),document.removeEventListener("pointermove",b)};n.addEventListener("pointerdown",h)}document.addEventListener("DOMContentLoaded",function(){window.addEventListener("comentarioGuardado",e=>{const{salidaId:a,comentario:t}=e.detail,o=window._calendarRef;if(o){const n=o.getEventById(`salida-${a}`);n&&(n.setExtendedProp("comentario",t),n._def&&n._def.extendedProps&&(n._def.extendedProps.comentario=t)),typeof Swal<"u"&&Swal.fire({icon:"success",title:"Comentario guardado",text:"El comentario se ha guardado correctamente",timer:2e3,showConfirmButton:!1,toast:!0,position:"top-end"})}})});let x=null;function tt(e){setTimeout(()=>{const a=document.querySelector(".fc-resource-timeline .fc-datagrid");if(!a||a.querySelector(".fc-resource-area-resizer"))return;const t=document.createElement("div");t.className="fc-resource-area-resizer",t.title="Arrastrar para redimensionar",a.appendChild(t);let o=!1,n=0,i=0;const r=localStorage.getItem("fc-resource-area-width");r&&(a.style.width=r,e.updateSize()),t.addEventListener("mousedown",d=>{o=!0,n=d.clientX,i=a.offsetWidth,t.classList.add("dragging"),document.body.classList.add("resizing-resource-area"),d.preventDefault()}),document.addEventListener("mousemove",d=>{if(!o)return;const l=d.clientX-n,u=Math.max(100,Math.min(500,i+l));a.style.width=u+"px"}),document.addEventListener("mouseup",()=>{o&&(o=!1,t.classList.remove("dragging"),document.body.classList.remove("resizing-resource-area"),localStorage.setItem("fc-resource-area-width",a.style.width),e.updateSize())})},100)}function at(e,a){const t=()=>e&&e.offsetParent!==null&&e.clientWidth>0&&e.clientHeight>=0;if(t())return a();if("IntersectionObserver"in window){const n=new IntersectionObserver(i=>{i.some(d=>d.isIntersecting)&&(n.disconnect(),a())},{root:null,threshold:.01});n.observe(e);return}if("ResizeObserver"in window){const n=new ResizeObserver(()=>{t()&&(n.disconnect(),a())});n.observe(e);return}const o=setInterval(()=>{t()&&(clearInterval(o),a())},100)}function I(){x&&(requestAnimationFrame(()=>{try{x.updateSize()}catch{}}),setTimeout(()=>{try{x.updateSize()}catch{}},150))}function ot(){let e=document.getElementById("transparent-drag-image");return e||(e=document.createElement("img"),e.id="transparent-drag-image",e.src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7",e.style.cssText="position: fixed; left: -9999px; top: -9999px; width: 1px; height: 1px; opacity: 0;",document.body.appendChild(e)),e}function nt(e,a){var c,f;ee();const t=document.createElement("div");t.id="custom-drag-ghost",t.className="custom-drag-ghost";const o=e.extendedProps||{},n=o.tipo==="salida",i=n?"🚚":"📋",r=n?"Salida":"Planilla",d=o.cod_obra||"",l=o.nombre_obra||((c=e.title)==null?void 0:c.split(`
`)[0])||"",u=o.cliente||"",p=o.pesoTotal?Number(o.pesoTotal).toLocaleString("es-ES",{maximumFractionDigits:0}):"",h=o.longitudTotal?Number(o.longitudTotal).toLocaleString("es-ES",{maximumFractionDigits:0}):"",b=o.diametroMedio?Number(o.diametroMedio).toFixed(1):"",s=((f=a==null?void 0:a.style)==null?void 0:f.backgroundColor)||e.backgroundColor||"#6366f1";return t.innerHTML=`
        <div class="ghost-card" style="--ghost-color: ${s};">
            <!-- Tipo -->
            <div class="ghost-type-badge ${n?"badge-salida":"badge-planilla"}">
                <span>${i}</span>
                <span>${r}</span>
            </div>

            <!-- Info principal -->
            <div class="ghost-main">
                ${d?`<div class="ghost-code">${d}</div>`:""}
                ${l?`<div class="ghost-name">${l}</div>`:""}
                ${u?`<div class="ghost-client">👤 ${u}</div>`:""}
            </div>

            <!-- Métricas -->
            ${p||h||b?`
            <div class="ghost-metrics">
                ${p?`<span class="ghost-metric">📦 ${p} kg</span>`:""}
                ${h?`<span class="ghost-metric">📏 ${h} m</span>`:""}
                ${b?`<span class="ghost-metric">⌀ ${b} mm</span>`:""}
            </div>
            `:""}

            <!-- Destino del drop -->
            <div class="ghost-destination">
                <span class="ghost-dest-date">--</span>
            </div>
        </div>
    `,document.body.appendChild(t),t}function re(e,a,t,o){const n=document.getElementById("custom-drag-ghost");if(n){if(n.style.left=`${e+20}px`,n.style.top=`${a-20}px`,t){const i=n.querySelector(".ghost-dest-time");i&&(i.textContent=t)}if(o){const i=n.querySelector(".ghost-dest-date");if(i){const r=new Date(o+"T00:00:00"),d={weekday:"short",day:"numeric",month:"short"};i.textContent=r.toLocaleDateString("es-ES",d)}}}}function ee(){const e=document.getElementById("custom-drag-ghost");e&&e.remove()}function le(e,a){const t=a==null?void 0:a.querySelector(".fc-timegrid-slots");if(!t)return null;const o=t.getBoundingClientRect(),n=e-o.top+t.scrollTop,i=t.scrollHeight||o.height,r=5,d=20,l=d-r,u=n/i*l,p=r*60+u*60,h=Math.round(p/30)*30,b=Math.max(r,Math.min(d-1,Math.floor(h/60))),s=h%60;return`${String(b).padStart(2,"0")}:${String(s).padStart(2,"0")}`}function de(e){const a=document.querySelectorAll(".fc-timegrid-slot, .fc-timegrid-col");e?a.forEach(t=>{t.classList.add("fc-drop-zone-highlight")}):a.forEach(t=>{t.classList.remove("fc-drop-zone-highlight")})}function st(){if(!window.FullCalendar)return console.error("FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."),null;if(!document.getElementById("fc-mirror-hide-style-global")){const u=document.createElement("style");u.id="fc-mirror-hide-style-global",u.textContent=`
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
        `,document.head.appendChild(u)}x&&x.destroy();const e=["resourceTimeGridDay","resourceTimelineWeek","dayGridMonth"];let a=localStorage.getItem("ultimaVistaCalendario");e.includes(a)||(a="resourceTimeGridDay");const t=localStorage.getItem("fechaCalendario");let o=null;const n=document.getElementById("calendario");if(!n)return console.error("#calendario no encontrado"),null;function i(u){return x?x.getEvents().some(p=>{var s,c;const h=(p.startStr||((s=p.start)==null?void 0:s.toISOString())||"").split("T")[0];return(((c=p.extendedProps)==null?void 0:c.tipo)==="festivo"||typeof p.id=="string"&&p.id.startsWith("festivo-"))&&h===u}):!1}at(n,()=>{x=new FullCalendar.Calendar(n,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",navLinks:!0,navLinkDayClick:(s,c)=>{var k;const f=s.getDay(),g=f===0||f===6,v=(k=x==null?void 0:x.view)==null?void 0:k.type;if(g&&(v==="resourceTimelineWeek"||v==="dayGridMonth")){c.preventDefault();let y;v==="dayGridMonth"?y=f===6?"saturday":"sunday":y=s.toISOString().split("T")[0],window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(y)?window.expandedWeekendDays.delete(y):window.expandedWeekendDays.add(y),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),x.render(),setTimeout(()=>{var E;return(E=window.applyWeekendCollapse)==null?void 0:E.call(window)},50);return}x.changeView("resourceTimeGridDay",s)},initialView:a,initialDate:t?new Date(t):void 0,dayMaxEventRows:!1,dayMaxEvents:!1,slotMinTime:"05:00:00",slotMaxTime:"20:00:00",buttonText:{today:"Hoy",resourceTimeGridDay:"Día",resourceTimelineWeek:"Semana",dayGridMonth:"Mes"},progressiveEventRendering:!0,expandRows:!0,height:"auto",events:(s,c,f)=>{var v;const g=s.view&&s.view.type||((v=x==null?void 0:x.view)==null?void 0:v.type)||"resourceTimeGridDay";$e(g,s).then(c).catch(f)},resources:(s,c,f)=>{var v;const g=s.view&&s.view.type||((v=x==null?void 0:x.view)==null?void 0:v.type)||"resourceTimeGridDay";Ce(g,s).then(c).catch(f)},headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimeGridDay,resourceTimelineWeek,dayGridMonth"},eventOrderStrict:!0,eventOrder:(s,c)=>{var y,E;const f=((y=s.extendedProps)==null?void 0:y.tipo)==="resumen-dia",g=((E=c.extendedProps)==null?void 0:E.tipo)==="resumen-dia";if(f&&!g)return-1;if(!f&&g)return 1;const v=parseInt(String(s.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0,k=parseInt(String(c.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0;return v-k},datesSet:s=>{try{const c=it(s);localStorage.setItem("fechaCalendario",c),localStorage.setItem("ultimaVistaCalendario",s.view.type),d(),clearTimeout(o),o=setTimeout(async()=>{F(),x.refetchResources(),x.refetchEvents(),await qe(s.view.type,{startStr:s.startStr,endStr:s.endStr}),I(),(s.view.type==="resourceTimelineWeek"||s.view.type==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)},0)}catch(c){console.error("Error en datesSet:",c)}},loading:s=>{const c=document.getElementById("calendario-loading"),f=document.getElementById("loading-text");if(c&&(s?(c.classList.remove("hidden"),f&&(f.textContent="Cargando eventos...")):c.classList.add("hidden")),!s&&x){const g=x.view.type;g==="resourceTimeGridDay"&&setTimeout(()=>l(),150),(g==="resourceTimelineWeek"||g==="dayGridMonth")&&window.applyWeekendCollapse&&setTimeout(()=>window.applyWeekendCollapse(),150)}},viewDidMount:s=>{d(),s.view.type==="resourceTimeGridDay"&&setTimeout(()=>l(),100),s.view.type==="dayGridMonth"&&setTimeout(()=>{document.querySelectorAll(".fc-daygrid-event-harness").forEach(c=>{c.querySelector(".evento-resumen-diario")||(c.style.setProperty("width","100%","important"),c.style.setProperty("max-width","100%","important"),c.style.setProperty("position","static","important"),c.style.setProperty("left","unset","important"),c.style.setProperty("right","unset","important"),c.style.setProperty("top","unset","important"),c.style.setProperty("inset","unset","important"),c.style.setProperty("margin","0 0 2px 0","important"))}),document.querySelectorAll(".fc-daygrid-event:not(.evento-resumen-diario)").forEach(c=>{c.style.setProperty("width","100%","important"),c.style.setProperty("max-width","100%","important"),c.style.setProperty("margin","0","important"),c.style.setProperty("position","static","important"),c.style.setProperty("left","unset","important"),c.style.setProperty("right","unset","important"),c.style.setProperty("inset","unset","important")})},50)},eventContent:s=>{var k;const c=s.event.backgroundColor||"#9CA3AF",f=s.event.extendedProps||{},g=(k=x==null?void 0:x.view)==null?void 0:k.type;if(f.tipo==="resumen-dia"){const y=Number(f.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),E=Number(f.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),$=f.diametroMedio?Number(f.diametroMedio).toFixed(1):null;if(g==="resourceTimelineWeek")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${y} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${E} m</div>
                                ${$?`<div class="text-yellow-800">⌀ ${$} mm</div>`:""}
                            </div>
                        `};if(g==="dayGridMonth")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${y} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${E} m</div>
                                ${$?`<div class="text-yellow-800">⌀ ${$} mm</div>`:""}
                            </div>
                        `}}let v=`
        <div style="background-color:${c}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${s.event.title}</div>
    `;if(f.tipo==="planilla"){const y=f.pesoTotal!=null?`📦 ${Number(f.pesoTotal).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2})} kg`:null,E=f.longitudTotal!=null?`📏 ${Number(f.longitudTotal).toLocaleString()} m`:null,$=f.diametroMedio!=null?`⌀ ${Number(f.diametroMedio).toFixed(2)} mm`:null,m=[y,E,$].filter(Boolean);m.length>0&&(v+=`<div class="text-sm text-black font-semibold">${m.join(" | ")}</div>`),f.tieneSalidas&&Array.isArray(f.salidas_codigos)&&f.salidas_codigos.length>0&&(v+=`
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${f.salidas_codigos.join(", ")}
                </span>
            </div>`)}return v+="</div>",{html:v}},eventDidMount:function(s){var E,$,m,w,S;const c=s.event.extendedProps||{};if(s.el.setAttribute("draggable","false"),s.el.ondragstart=C=>(C.preventDefault(),!1),c.tipo==="resumen-dia"){s.el.classList.add("evento-resumen-diario"),s.el.style.cursor="default";return}if(s.view.type==="dayGridMonth"){const C=s.el.closest(".fc-daygrid-event-harness");C&&C.classList.add("evento-fullwidth"),s.el.classList.add("evento-fullwidth-event")}const f=(((E=document.getElementById("filtro-obra"))==null?void 0:E.value)||"").trim().toLowerCase(),g=((($=document.getElementById("filtro-nombre-obra"))==null?void 0:$.value)||"").trim().toLowerCase(),v=(((m=document.getElementById("filtro-cod-cliente"))==null?void 0:m.value)||"").trim().toLowerCase(),k=(((w=document.getElementById("filtro-cliente"))==null?void 0:w.value)||"").trim().toLowerCase(),y=(((S=document.getElementById("filtro-cod-planilla"))==null?void 0:S.value)||"").trim().toLowerCase();if(f||g||v||k||y){let C=!1;if(c.tipo==="salida"&&c.obras&&Array.isArray(c.obras)){if(C=c.obras.some(L=>{const _=(L.codigo||"").toString().toLowerCase(),T=(L.nombre||"").toString().toLowerCase(),P=(L.cod_cliente||"").toString().toLowerCase(),X=(L.cliente||"").toString().toLowerCase(),Y=!f||_.includes(f),K=!g||T.includes(g),J=!v||P.includes(v),R=!k||X.includes(k);return Y&&K&&J&&R}),y&&c.planillas_codigos&&Array.isArray(c.planillas_codigos)){const L=c.planillas_codigos.some(_=>(_||"").toString().toLowerCase().includes(y));C=C&&L}}else{const L=(c.cod_obra||"").toString().toLowerCase(),_=(c.nombre_obra||s.event.title||"").toString().toLowerCase(),T=(c.cod_cliente||"").toString().toLowerCase(),P=(c.cliente||"").toString().toLowerCase(),X=!f||L.includes(f),Y=!g||_.includes(g),K=!v||T.includes(v),J=!k||P.includes(k);let R=!0;y&&(c.planillas_codigos&&Array.isArray(c.planillas_codigos)?R=c.planillas_codigos.some(we=>(we||"").toString().toLowerCase().includes(y)):R=(s.event.title||"").toLowerCase().includes(y)),C=X&&Y&&K&&J&&R}C?(s.el.classList.add("evento-filtrado"),s.el.classList.remove("evento-atenuado")):(s.el.classList.add("evento-atenuado"),s.el.classList.remove("evento-filtrado"))}else s.el.classList.remove("evento-filtrado"),s.el.classList.remove("evento-atenuado");typeof oe=="function"&&oe(s),typeof ie=="function"&&ie(s,x)},eventAllow:(s,c)=>{var g;const f=(g=c.extendedProps)==null?void 0:g.tipo;return!(f==="resumen-dia"||f==="festivo")},snapDuration:"00:30:00",eventDragStart:s=>{var $;window._isDragging=!0,window._draggedEvent=s.event,nt(s.event,s.el),document.body.classList.add("fc-dragging-active");const c=ot(),f=m=>{m.dataTransfer&&window._isDragging&&m.dataTransfer.setDragImage(c,0,0)};document.addEventListener("dragstart",f,!0),window._nativeDragStartHandler=f;const g=document.getElementById("calendario");(($=x==null?void 0:x.view)==null?void 0:$.type)==="resourceTimeGridDay"&&de(!0);const v=(m,w)=>{const S=document.elementsFromPoint(m,w);for(const C of S){const L=C.closest(".fc-daygrid-day");if(L)return L.getAttribute("data-date");const _=C.closest("[data-date]");if(_)return _.getAttribute("data-date")}return null};let k=!1;const y=m=>{!window._isDragging||k||(k=!0,requestAnimationFrame(()=>{if(k=!1,!window._isDragging)return;const w=le(m.clientY,g),S=v(m.clientX,m.clientY);re(m.clientX,m.clientY,w,S)}))};if(document.addEventListener("mousemove",y,{passive:!0}),window._dragMouseMoveHandler=y,s.jsEvent){const m=le(s.jsEvent.clientY,g),w=v(s.jsEvent.clientX,s.jsEvent.clientY);re(s.jsEvent.clientX,s.jsEvent.clientY,m,w)}window._dragOriginalStart=s.event.start,window._dragOriginalEnd=s.event.end,window._dragEventId=s.event.id;const E=m=>{if(window._isDragging){m.preventDefault(),m.stopPropagation(),m.stopImmediatePropagation(),window._cancelDrag=!0,ee();const w=new PointerEvent("pointerup",{bubbles:!0,cancelable:!0,clientX:m.clientX,clientY:m.clientY});document.dispatchEvent(w)}};document.addEventListener("contextmenu",E,{capture:!0}),window._dragContextMenuHandler=E},eventDragStop:s=>{window._isDragging=!1,window._draggedEvent=null,window._nativeDragStartHandler&&(document.removeEventListener("dragstart",window._nativeDragStartHandler,!0),window._nativeDragStartHandler=null),window._dragMouseMoveHandler&&(document.removeEventListener("mousemove",window._dragMouseMoveHandler),window._dragMouseMoveHandler=null),window._dragContextMenuHandler&&(document.removeEventListener("contextmenu",window._dragContextMenuHandler,{capture:!0}),window._dragContextMenuHandler=null),window._dragOriginalStart=null,window._dragOriginalEnd=null,window._dragEventId=null,ee(),document.body.classList.remove("fc-dragging-active"),de(!1)},eventDrop:s=>{var y,E,$,m;if(window._cancelDrag){window._cancelDrag=!1,s.revert(),window._dragOriginalStart&&(s.event.setStart(window._dragOriginalStart),window._dragOriginalEnd&&s.event.setEnd(window._dragOriginalEnd));return}const c=s.event.extendedProps||{},f=s.event.id,g=(y=s.event.start)==null?void 0:y.toISOString(),v={fecha:g,tipo:c.tipo,planillas_ids:c.planillas_ids||[],elementos_ids:c.elementos_ids||[],verificar_fabricacion:!0},k=((($=(E=window.AppSalidas)==null?void 0:E.routes)==null?void 0:$.updateItem)||"").replace("__ID__",f);Swal.fire({title:"Actualizando fecha...",allowOutsideClick:!1,allowEscapeKey:!1,showConfirmButton:!1,didOpen:()=>{Swal.showLoading()}}),fetch(k,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(m=window.AppSalidas)==null?void 0:m.csrf},body:JSON.stringify(v)}).then(w=>{if(!w.ok)throw new Error("No se pudo actualizar la fecha.");return w.json()}).then(async w=>{if(Swal.close(),F(),I(),w.resumenes_dias&&ye(w.resumenes_dias),w.alerta_retraso){const S=w.alerta_retraso.es_elementos_con_fecha_propia||!1,C=S?"elementos":"planilla";Swal.fire({icon:"warning",title:"⚠️ Fecha de entrega adelantada",html:`
                                    <div class="text-left">
                                        <p class="mb-2">${w.alerta_retraso.mensaje}</p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fin fabricación:</strong> ${w.alerta_retraso.fin_programado}</p>
                                            <p class="text-sm"><strong>Fecha entrega:</strong> ${w.alerta_retraso.fecha_entrega}</p>
                                        </div>
                                        <p class="mt-3 text-sm text-gray-600">Los ${C==="elementos"?"elementos":"elementos de la planilla"} no estarán listos para la fecha indicada.</p>
                                    </div>
                                `,showCancelButton:!0,confirmButtonText:"🚀 Adelantar fabricación",cancelButtonText:"Entendido",confirmButtonColor:"#10b981",cancelButtonColor:"#f59e0b"}).then(L=>{L.isConfirmed&&rt(w.alerta_retraso.elementos_ids||c.elementos_ids,g,S)})}if(w.opcion_posponer){const S=w.opcion_posponer.es_elementos_con_fecha_propia||!1,C=w.opcion_posponer.ordenes_afectadas||[],L=S?"Elementos con fecha propia":"Planilla";let _="";C.length>0&&(_=`
                                    <div class="max-h-40 overflow-y-auto mt-3">
                                        <table class="w-full text-sm border">
                                            <thead class="bg-blue-100">
                                                <tr>
                                                    <th class="px-2 py-1 text-left">Planilla</th>
                                                    <th class="px-2 py-1 text-left">Máquina</th>
                                                    <th class="px-2 py-1 text-center">Posición</th>
                                                    ${S?'<th class="px-2 py-1 text-center">Elementos</th>':""}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${C.map(T=>`
                                                    <tr class="border-t">
                                                        <td class="px-2 py-1">${T.planilla_codigo}</td>
                                                        <td class="px-2 py-1">${T.maquina_nombre}</td>
                                                        <td class="px-2 py-1 text-center">${T.posicion_actual} / ${T.total_posiciones}</td>
                                                        ${S?`<td class="px-2 py-1 text-center">${T.elementos_count||"-"}</td>`:""}
                                                    </tr>
                                                `).join("")}
                                            </tbody>
                                        </table>
                                    </div>
                                `),Swal.fire({icon:"question",title:`📅 ${L} - Fecha pospuesta`,html:`
                                    <div class="text-left">
                                        <p class="mb-2">${w.opcion_posponer.mensaje}</p>
                                        <div class="bg-blue-50 border border-blue-200 rounded p-3 mt-3">
                                            <p class="text-sm"><strong>Fecha anterior:</strong> ${w.opcion_posponer.fecha_anterior}</p>
                                            <p class="text-sm"><strong>Nueva fecha:</strong> ${w.opcion_posponer.fecha_nueva}</p>
                                        </div>
                                        ${_}
                                        <p class="mt-3 text-sm text-gray-600">Al retrasar la fabricación, otras planillas más urgentes podrán avanzar en la cola.</p>
                                    </div>
                                `,showCancelButton:!0,confirmButtonText:"⏬ Retrasar fabricación",cancelButtonText:"No, mantener posición",confirmButtonColor:"#3b82f6",cancelButtonColor:"#6b7280"}).then(T=>{T.isConfirmed&&dt(w.opcion_posponer.elementos_ids,S,g)})}}).catch(w=>{Swal.close(),console.error("Error:",w),Swal.fire({icon:"error",title:"Error",text:"No se pudo actualizar la fecha.",timer:3e3}),s.revert()})},dateClick:s=>{i(s.dateStr)&&Swal.fire({icon:"info",title:"📅 Día festivo",text:"Los festivos se editan en la planificación de Trabajadores.",confirmButtonText:"Entendido"})},eventMinHeight:30,firstDay:1,slotLabelContent:s=>{var $,m;if((($=x==null?void 0:x.view)==null?void 0:$.type)!=="resourceTimelineWeek")return null;const f=s.date;if(!f)return null;const g=f.getDay(),v=g===0||g===6,k=f.toISOString().split("T")[0],y={weekday:"short",day:"numeric",month:"short"},E=f.toLocaleDateString("es-ES",y);if(v){const S=!((m=window.expandedWeekendDays)==null?void 0:m.has(k)),C=S?"▶":"▼",L=S?f.toLocaleDateString("es-ES",{weekday:"short"}).substring(0,3):E;return{html:`<div class="weekend-header cursor-pointer select-none hover:bg-gray-200 px-1 rounded"
                                    data-date="${k}"
                                    data-collapsed="${S}"
                                    title="${S?"Clic para expandir":"Clic para colapsar"}">
                                <span class="collapse-icon text-xs mr-1">${C}</span>
                                <span class="weekend-label">${L}</span>
                               </div>`}}return{html:`<span>${E}</span>`}},views:{resourceTimelineWeek:{slotDuration:{days:1}},resourceTimeGridDay:{slotDuration:"01:00:00",slotLabelFormat:{hour:"2-digit",minute:"2-digit",hour12:!1},slotLabelInterval:"01:00:00",allDaySlot:!1}},editable:!0,eventDurationEditable:!1,eventStartEditable:!0,resourceAreaWidth:"15%",resourceAreaHeaderContent:"Obras",resourceOrder:"orderIndex",resourceLabelContent:s=>({html:`<div class="text-xs font-semibold">
                        <div class="text-blue-600">${s.resource.extendedProps.cod_obra||""}</div>
                        <div class="text-gray-700 truncate">${s.resource.title||""}</div>
                        <div class="text-gray-500 text-[10px] truncate">${s.resource.extendedProps.cliente||""}</div>
                    </div>`}),windowResize:()=>I()}),x.render(),I(),tt(x),setTimeout(()=>{const s=document.getElementById("calendario-loading");s&&!s.classList.contains("opacity-0")&&(s.classList.add("opacity-0","pointer-events-none"),s.classList.remove("opacity-100"))},500);const u=localStorage.getItem("expandedWeekendDays");window.expandedWeekendDays=new Set(u?JSON.parse(u):[]),window.weekendDefaultCollapsed=!0;function p(s){const f=new Date(s+"T00:00:00").getDay();return f===0||f===6}function h(){var c,f,g;const s=(c=x==null?void 0:x.view)==null?void 0:c.type;if(s==="resourceTimelineWeek"&&(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(y=>{var $;const E=y.getAttribute("data-date");p(E)&&((($=window.expandedWeekendDays)==null?void 0:$.has(E))?y.classList.remove("weekend-collapsed"):y.classList.add("weekend-collapsed"))}),document.querySelectorAll(".fc-timeline-lane td[data-date]").forEach(y=>{var $;const E=y.getAttribute("data-date");p(E)&&((($=window.expandedWeekendDays)==null?void 0:$.has(E))?y.classList.remove("weekend-collapsed"):y.classList.add("weekend-collapsed"))})),s==="dayGridMonth"){const v=(f=window.expandedWeekendDays)==null?void 0:f.has("saturday"),k=(g=window.expandedWeekendDays)==null?void 0:g.has("sunday"),y=document.querySelectorAll(".fc-col-header-cell.fc-day-sat"),E=document.querySelectorAll(".fc-col-header-cell.fc-day-sun");y.forEach(m=>{v?m.classList.remove("weekend-day-collapsed"):m.classList.add("weekend-day-collapsed")}),E.forEach(m=>{k?m.classList.remove("weekend-day-collapsed"):m.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sat").forEach(m=>{v?m.classList.remove("weekend-day-collapsed"):m.classList.add("weekend-day-collapsed")}),document.querySelectorAll(".fc-daygrid-day.fc-day-sun").forEach(m=>{k?m.classList.remove("weekend-day-collapsed"):m.classList.add("weekend-day-collapsed")});const $=document.querySelector(".fc-dayGridMonth-view table");if($){let m=$.querySelector("colgroup");if(!m){m=document.createElement("colgroup");for(let S=0;S<7;S++)m.appendChild(document.createElement("col"));$.insertBefore(m,$.firstChild)}const w=m.querySelectorAll("col");w.length>=7&&(w[5].style.width=v?"":"40px",w[6].style.width=k?"":"40px")}}}function b(s){window.expandedWeekendDays||(window.expandedWeekendDays=new Set),window.expandedWeekendDays.has(s)?window.expandedWeekendDays.delete(s):window.expandedWeekendDays.add(s),localStorage.setItem("expandedWeekendDays",JSON.stringify([...window.expandedWeekendDays])),h()}n.addEventListener("click",s=>{var g;const c=s.target.closest(".weekend-header");if(c){const v=c.getAttribute("data-date");if(v){s.preventDefault(),s.stopPropagation(),b(v);return}}if(((g=x==null?void 0:x.view)==null?void 0:g.type)==="dayGridMonth"){const v=s.target.closest(".fc-col-header-cell.fc-day-sat, .fc-col-header-cell.fc-day-sun");if(v){s.preventDefault(),s.stopPropagation();const E=v.classList.contains("fc-day-sat")?"saturday":"sunday";b(E);return}const k=s.target.closest(".fc-daygrid-day.fc-day-sat, .fc-daygrid-day.fc-day-sun");if(k&&!s.target.closest(".fc-event")){s.preventDefault(),s.stopPropagation();const E=k.classList.contains("fc-day-sat")?"saturday":"sunday";b(E);return}}},!0),setTimeout(()=>h(),100),window.applyWeekendCollapse=h,n.addEventListener("contextmenu",s=>{if(window._isDragging||window._cancelDrag)return;const c=s.target.closest(".fc-daygrid-day, .fc-timeline-slot, .fc-timegrid-slot, .fc-col-header-cell");if(c){let f=c.getAttribute("data-date");if(!f){const g=s.target.closest("[data-date]");g&&(f=g.getAttribute("data-date"))}if(f&&x){const g=x.view.type;(g==="resourceTimelineWeek"||g==="dayGridMonth")&&(s.preventDefault(),s.stopPropagation(),Swal.fire({title:"📅 Ir a día",text:`¿Quieres ver el día ${f}?`,icon:"question",showCancelButton:!0,confirmButtonText:"Sí, ir al día",cancelButtonText:"Cancelar"}).then(v=>{v.isConfirmed&&(x.changeView("resourceTimeGridDay",f),I())}))}}})}),window.addEventListener("shown.bs.tab",I),window.addEventListener("shown.bs.collapse",I),window.addEventListener("shown.bs.modal",I);function d(){document.querySelectorAll(".resumen-diario-custom").forEach(p=>p.remove())}function l(){if(!x||x.view.type!=="resourceTimeGridDay"){d();return}d();const u=x.getDate(),p=u.getFullYear(),h=String(u.getMonth()+1).padStart(2,"0"),b=String(u.getDate()).padStart(2,"0"),s=`${p}-${h}-${b}`,c=x.getEvents().find(f=>{var g,v;return((g=f.extendedProps)==null?void 0:g.tipo)==="resumen-dia"&&((v=f.extendedProps)==null?void 0:v.fecha)===s});if(c&&c.extendedProps){const f=Number(c.extendedProps.pesoTotal||0).toLocaleString(),g=Number(c.extendedProps.longitudTotal||0).toLocaleString(),v=c.extendedProps.diametroMedio?Number(c.extendedProps.diametroMedio).toFixed(2):null,k=document.createElement("div");k.className="resumen-diario-custom",k.innerHTML=`
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 Peso: ${f} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${g} m</div>
                        ${v?`<div class="text-yellow-800">⌀ Diámetro: ${v} mm</div>`:""}
                    </div>
                </div>
            `,n&&n.parentNode&&n.parentNode.insertBefore(k,n)}}return window.mostrarResumenDiario=l,window.limpiarResumenesCustom=d,x}function ye(e){var a;!window.calendar||!e||(Object.entries(e).forEach(([t,o])=>{const n=window.calendar.getEvents().find(i=>{var r,d;return((r=i.extendedProps)==null?void 0:r.tipo)==="resumen-dia"&&((d=i.extendedProps)==null?void 0:d.fecha)===t});if(n){n.setExtendedProp("pesoTotal",o.pesoTotal),n.setExtendedProp("longitudTotal",o.longitudTotal),n.setExtendedProp("diametroMedio",o.diametroMedio);const i=Number(o.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),r=Number(o.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0});n.setProp("title",`📊 ${i} kg · ${r} m`)}else if(o.pesoTotal>0||o.longitudTotal>0){const i=Number(o.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),r=Number(o.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0});window.calendar.addEvent({title:`📊 ${i} kg · ${r} m`,start:t,allDay:!0,backgroundColor:"#fef3c7",borderColor:"#fbbf24",textColor:"#92400e",classNames:["evento-resumen-diario"],editable:!1,extendedProps:{tipo:"resumen-dia",pesoTotal:o.pesoTotal,longitudTotal:o.longitudTotal,diametroMedio:o.diametroMedio,fecha:t}})}n&&o.pesoTotal===0&&o.longitudTotal===0&&n.remove()}),window.calendar.view.type==="resourceTimeGridDay"&&window.mostrarResumenDiario&&((a=window.limpiarResumenesCustom)==null||a.call(window),window.mostrarResumenDiario()))}window.actualizarResumenesDias=ye;function it(e){if(e.view.type==="dayGridMonth"){const a=new Date(e.start);return a.setDate(a.getDate()+15),a.toISOString().split("T")[0]}if(e.view.type==="resourceTimeGridWeek"||e.view.type==="resourceTimelineWeek"){const a=new Date(e.start),t=Math.floor((e.end-e.start)/(1e3*60*60*24)/2);return a.setDate(a.getDate()+t),a.toISOString().split("T")[0]}return e.startStr.split("T")[0]}function rt(e,a,t=!1){var i;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para adelantar"});return}if(t){lt(e,a);return}Swal.fire({title:"Analizando opciones...",html:"Calculando la mejor posición para adelantar la fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const o=new AbortController,n=setTimeout(()=>o.abort(),6e4);fetch("/planificacion/simular-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(i=window.AppSalidas)==null?void 0:i.csrf},body:JSON.stringify({elementos_ids:e,fecha_entrega:a}),signal:o.signal}).then(r=>{if(clearTimeout(n),!r.ok)throw new Error("Error en la simulación");return r.json()}).then(r=>{if(!r.necesita_adelanto){const p=(r.mensaje||"Los elementos llegarán a tiempo.").replace(/\n/g,"<br>").replace(/•/g,'<span class="text-amber-600">•</span>');r.razones&&r.razones.length>0&&r.razones.some(s=>s.fin_minimo)?Swal.fire({icon:"warning",title:"No se puede entregar a tiempo",html:`
                            <div class="text-left text-sm mb-4">${p}</div>
                            <div class="text-left text-sm font-semibold text-amber-700 border-t pt-3">
                                ¿Deseas adelantar a primera posición de todas formas?
                            </div>
                        `,width:650,showCancelButton:!0,confirmButtonText:"Sí, adelantar a 1ª posición",cancelButtonText:"Cancelar",confirmButtonColor:"#f59e0b",cancelButtonColor:"#6b7280"}).then(s=>{if(s.isConfirmed){const c=[];r.razones.filter(f=>f.fin_minimo).forEach(f=>{f.planillas_ids&&f.planillas_ids.length>0?f.planillas_ids.forEach(g=>{c.push({planilla_id:g,maquina_id:f.maquina_id,posicion_nueva:1})}):f.planilla_id&&c.push({planilla_id:f.planilla_id,maquina_id:f.maquina_id,posicion_nueva:1})}),c.length>0?(console.log("Órdenes a adelantar:",c),ce(c)):(console.warn("No se encontraron órdenes para adelantar",r.razones),Swal.fire({icon:"warning",title:"Sin órdenes",text:"No se encontraron órdenes para adelantar."}))}}):Swal.fire({icon:"info",title:"No es necesario adelantar",html:`<div class="text-left text-sm">${p}</div>`,width:600});return}let d="";r.ordenes_a_adelantar&&r.ordenes_a_adelantar.length>0&&(d=`
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
                `,r.ordenes_a_adelantar.forEach(p=>{d+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${p.planilla_codigo}</td>
                            <td class="px-2 py-1">${p.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${p.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${p.posicion_nueva}</td>
                        </tr>
                    `}),d+=`
                                </tbody>
                            </table>
                        </div>
                    </div>
                `);let l="";r.colaterales&&r.colaterales.length>0&&(l=`
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
                `,r.colaterales.forEach(p=>{l+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${p.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${p.obra}</td>
                            <td class="px-2 py-1">${p.fecha_entrega}</td>
                        </tr>
                    `}),l+=`
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-orange-600 mt-1">Estas planillas bajarán una posición en la cola de fabricación.</p>
                    </div>
                `);const u=r.fecha_entrega||"---";Swal.fire({icon:"question",title:"🚀 Adelantar fabricación",html:`
                    <div class="text-left">
                        <p class="mb-3">Para cumplir con la fecha de entrega <strong>${u}</strong>, se propone el siguiente cambio:</p>
                        ${d}
                        ${l}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el adelanto?</p>
                    </div>
                `,width:600,showCancelButton:!0,confirmButtonText:"✅ Ejecutar adelanto",cancelButtonText:"Cancelar",confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280"}).then(p=>{p.isConfirmed&&ce(r.ordenes_a_adelantar)})}).catch(r=>{clearTimeout(n),console.error("Error en simulación:",r);const d=r.name==="AbortError";Swal.fire({icon:"error",title:d?"Tiempo agotado":"Error",text:d?"El cálculo está tardando demasiado. La operación fue cancelada.":"No se pudo simular el adelanto. "+r.message})})}function ce(e){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay órdenes para adelantar"});return}Swal.fire({title:"Ejecutando adelanto...",html:"Actualizando posiciones en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const a=e.map(o=>({planilla_id:o.planilla_id,maquina_id:o.maquina_id,posicion_nueva:o.posicion_nueva}));console.log("Enviando órdenes al servidor:",JSON.stringify({ordenes:a},null,2)),fetch("/planificacion/ejecutar-adelanto",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({ordenes:a})}).then(o=>{if(!o.ok)throw new Error("Error al ejecutar el adelanto");return o.json()}).then(o=>{if(console.log("Respuesta del servidor:",o),o.success){const n=o.resultados||[],i=n.filter(l=>l.success),r=n.filter(l=>!l.success);let d=o.mensaje||"Las posiciones han sido actualizadas.";i.length>0&&(d+=`<br><br><strong>${i.length} orden(es) movidas correctamente.</strong>`),r.length>0&&(d+=`<br><span class="text-amber-600">${r.length} orden(es) no pudieron moverse:</span>`,d+="<ul class='text-left text-sm mt-2'>",r.forEach(l=>{d+=`<li>• Planilla ${l.planilla_id}: ${l.mensaje}</li>`}),d+="</ul>"),Swal.fire({icon:i.length>0?"success":"warning",title:i.length>0?"¡Adelanto ejecutado!":"Problemas al adelantar",html:d,confirmButtonColor:"#10b981"}).then(()=>{x&&(F(),x.refetchEvents(),x.refetchResources())})}else Swal.fire({icon:"error",title:"Error",text:o.mensaje||"No se pudo ejecutar el adelanto."})}).catch(o=>{console.error("Error al ejecutar adelanto:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el adelanto. "+o.message})})}function lt(e,a){var t;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para adelantar"});return}Swal.fire({title:"Ejecutando adelanto...",html:"Separando elementos y actualizando posiciones en la cola",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}}),fetch("/planificacion/ejecutar-adelanto-elementos",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(t=window.AppSalidas)==null?void 0:t.csrf},body:JSON.stringify({elementos_ids:e,nueva_fecha_entrega:a})}).then(o=>{if(!o.ok)throw new Error("Error al ejecutar el adelanto");return o.json()}).then(o=>{if(o.success){const n=o.resultados||[],i=n.filter(l=>l.success),r=n.filter(l=>!l.success);let d=o.mensaje||"Las posiciones han sido actualizadas.";i.length>0&&(d+=`<br><br><strong>${i.length} orden(es) de elementos adelantadas.</strong>`),r.length>0&&(d+=`<br><span class="text-amber-600">${r.length} orden(es) no pudieron moverse.</span>`),Swal.fire({icon:i.length>0?"success":"warning",title:i.length>0?"¡Adelanto ejecutado!":"Problemas al adelantar",html:d,confirmButtonColor:"#10b981"}).then(()=>{x&&(F(),x.refetchEvents(),x.refetchResources())})}else Swal.fire({icon:"error",title:"Error",text:o.mensaje||"No se pudo ejecutar el adelanto."})}).catch(o=>{console.error("Error al ejecutar adelanto de elementos:",o),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el adelanto. "+o.message})})}function dt(e,a=!1,t=null){var o;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para retrasar"});return}Swal.fire({title:"Analizando...",html:"Calculando el impacto del retraso en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}}),fetch("/planificacion/simular-retraso",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(o=window.AppSalidas)==null?void 0:o.csrf},body:JSON.stringify({elementos_ids:e,es_elementos_con_fecha_propia:a})}).then(n=>{if(!n.ok)throw new Error("Error en la simulación");return n.json()}).then(n=>{if(!n.puede_retrasar){Swal.fire({icon:"info",title:"No se puede retrasar",text:n.mensaje||"Las planillas ya están al final de la cola."});return}let i="";n.ordenes_a_retrasar&&n.ordenes_a_retrasar.length>0&&(i=`
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
                `,n.ordenes_a_retrasar.forEach(l=>{i+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${l.planilla_codigo}</td>
                            <td class="px-2 py-1">${l.maquina_nombre}</td>
                            <td class="px-2 py-1 text-center">${l.posicion_actual}</td>
                            <td class="px-2 py-1 text-center font-bold text-blue-600">${l.posicion_nueva}</td>
                        </tr>
                    `}),i+=`
                                </tbody>
                            </table>
                        </div>
                    </div>
                `);let r="";n.beneficiados&&n.beneficiados.length>0&&(r=`
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
                `,n.beneficiados.slice(0,10).forEach(l=>{r+=`
                        <tr class="border-t">
                            <td class="px-2 py-1">${l.planilla_codigo}</td>
                            <td class="px-2 py-1 truncate" style="max-width:150px">${l.obra}</td>
                            <td class="px-2 py-1 text-center font-bold text-green-600">${l.posicion_nueva}</td>
                        </tr>
                    `}),r+=`
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-green-600 mt-1">Estas planillas subirán una posición en la cola.</p>
                    </div>
                `);const d=n.es_elementos_con_fecha_propia?"⏬ Retrasar fabricación (Elementos)":"⏬ Retrasar fabricación";Swal.fire({icon:"question",title:d,html:`
                    <div class="text-left">
                        <p class="mb-3">${n.mensaje}</p>
                        ${i}
                        ${r}
                        <p class="text-sm text-gray-600 mt-3">¿Deseas ejecutar el retraso?</p>
                    </div>
                `,width:600,showCancelButton:!0,confirmButtonText:"✅ Ejecutar retraso",cancelButtonText:"Cancelar",confirmButtonColor:"#3b82f6",cancelButtonColor:"#6b7280"}).then(l=>{l.isConfirmed&&ct(e,a,t)})}).catch(n=>{console.error("Error en simulación de retraso:",n),Swal.fire({icon:"error",title:"Error",text:"No se pudo simular el retraso. "+n.message})})}function ct(e,a=!1,t=null){var n;if(!e||e.length===0){Swal.fire({icon:"error",title:"Error",text:"No hay elementos para retrasar"});return}Swal.fire({title:"Ejecutando retraso...",html:"Actualizando posiciones en la cola de fabricación",allowOutsideClick:!1,didOpen:()=>{Swal.showLoading()}});const o={elementos_ids:e,es_elementos_con_fecha_propia:a};a&&t&&(o.nueva_fecha_entrega=t),fetch("/planificacion/ejecutar-retraso",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(n=window.AppSalidas)==null?void 0:n.csrf},body:JSON.stringify(o)}).then(i=>{if(!i.ok)throw new Error("Error al ejecutar el retraso");return i.json()}).then(i=>{if(i.success){const r=i.resultados||[],d=r.filter(p=>p.success),l=r.filter(p=>!p.success);let u=i.mensaje||"Las posiciones han sido actualizadas.";d.length>0&&(u+=`<br><br><strong>${d.length} planilla(s) movidas al final de la cola.</strong>`),l.length>0&&(u+=`<br><span class="text-amber-600">${l.length} orden(es) no pudieron moverse:</span>`,u+="<ul class='text-left text-sm mt-2'>",l.forEach(p=>{u+=`<li>• Planilla ${p.planilla_id}: ${p.mensaje}</li>`}),u+="</ul>"),Swal.fire({icon:d.length>0?"success":"warning",title:d.length>0?"¡Retraso ejecutado!":"Problemas al retrasar",html:u,confirmButtonColor:"#3b82f6"}).then(()=>{x&&(F(),x.refetchEvents(),x.refetchResources())})}else Swal.fire({icon:"error",title:"Error",text:i.mensaje||"No se pudo ejecutar el retraso."})}).catch(i=>{console.error("Error al ejecutar retraso:",i),Swal.fire({icon:"error",title:"Error",text:"No se pudo ejecutar el retraso. "+i.message})})}function ut(e,a={}){const{selector:t=null,once:o=!1}=a;let n=!1;const i=()=>{t&&!document.querySelector(t)||o&&n||(n=!0,e())};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",i):i(),document.addEventListener("livewire:navigated",i)}function pt(e){document.addEventListener("livewire:navigating",e)}function mt(e){let t=new Date(e).toLocaleDateString("es-ES",{month:"long",year:"numeric"});return`(${t.charAt(0).toUpperCase()+t.slice(1)})`}function ft(e){const a=new Date(e),t=a.getDay(),o=t===0?-6:1-t,n=new Date(a);n.setDate(a.getDate()+o);const i=new Date(n);i.setDate(n.getDate()+6);const r=new Intl.DateTimeFormat("es-ES",{day:"2-digit",month:"short"}),d=new Intl.DateTimeFormat("es-ES",{year:"numeric"});return`(${r.format(n)} – ${r.format(i)} ${d.format(i)})`}function gt(e){const a=document.querySelector("#resumen-semanal-fecha"),t=document.querySelector("#resumen-mensual-fecha");a&&(a.textContent=ft(e)),t&&(t.textContent=mt(e))}let M;function yt(){var v,k;if(window.calendar)try{window.calendar.destroy()}catch(y){console.warn("Error al destruir calendario anterior:",y)}const e=st();M=e,window.calendar=e,e.refetchResources(),e.refetchEvents(),(v=document.getElementById("ver-con-salidas"))==null||v.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()}),(k=document.getElementById("ver-todas"))==null||k.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()});const t=(localStorage.getItem("fechaCalendario")||new Date().toISOString()).split("T")[0];gt(t);const o=localStorage.getItem("soloSalidas")==="true",n=localStorage.getItem("soloPlanillas")==="true",i=document.getElementById("solo-salidas"),r=document.getElementById("solo-planillas");i&&(i.checked=o),r&&(r.checked=n);const d=document.getElementById("filtro-obra"),l=document.getElementById("filtro-nombre-obra"),u=document.getElementById("filtro-cod-cliente"),p=document.getElementById("filtro-cliente"),h=document.getElementById("filtro-cod-planilla"),b=document.getElementById("btn-reset-filtros"),s=document.getElementById("btn-limpiar-filtros");b==null||b.addEventListener("click",()=>{d&&(d.value=""),l&&(l.value=""),u&&(u.value=""),p&&(p.value=""),h&&(h.value=""),i&&(i.checked=!1,localStorage.setItem("soloSalidas","false")),r&&(r.checked=!1,localStorage.setItem("soloPlanillas","false")),g(),document.querySelectorAll(".fc-event.evento-filtrado, .fc-event.evento-atenuado").forEach(y=>{y.classList.remove("evento-filtrado","evento-atenuado")}),F(),M.refetchEvents()});const f=((y,E=150)=>{let $;return(...m)=>{clearTimeout($),$=setTimeout(()=>y(...m),E)}})(()=>{M.refetchEvents()},120);d==null||d.addEventListener("input",f),l==null||l.addEventListener("input",f),u==null||u.addEventListener("input",f),p==null||p.addEventListener("input",f),h==null||h.addEventListener("input",f);function g(){const y=i==null?void 0:i.closest(".checkbox-container"),E=r==null?void 0:r.closest(".checkbox-container");y==null||y.classList.remove("active-salidas"),E==null||E.classList.remove("active-planillas"),i!=null&&i.checked&&(y==null||y.classList.add("active-salidas")),r!=null&&r.checked&&(E==null||E.classList.add("active-planillas"))}i==null||i.addEventListener("change",y=>{y.target.checked&&r&&(r.checked=!1,localStorage.setItem("soloPlanillas","false")),localStorage.setItem("soloSalidas",y.target.checked.toString()),g(),M.refetchEvents()}),r==null||r.addEventListener("change",y=>{y.target.checked&&i&&(i.checked=!1,localStorage.setItem("soloSalidas","false")),localStorage.setItem("soloPlanillas",y.target.checked.toString()),g(),M.refetchEvents()}),g(),s==null||s.addEventListener("click",()=>{d&&(d.value=""),l&&(l.value=""),u&&(u.value=""),p&&(p.value=""),h&&(h.value=""),document.querySelectorAll(".fc-event.evento-filtrado, .fc-event.evento-atenuado").forEach(y=>{y.classList.remove("evento-filtrado","evento-atenuado")}),F(),M.refetchEvents()})}let j=null,B=null,te="days",O=-1,N=[];function ht(){B&&B();const e=window.calendar;if(!e)return;j=e.getDate(),te="days",O=-1,xt();function a(t){var r;const o=t.target.tagName.toLowerCase();if(o==="input"||o==="textarea"||t.target.isContentEditable||document.querySelector(".swal2-container"))return;const n=window.calendar;if(!n)return;let i=!1;switch(t.key){case"ArrowLeft":n.prev(),i=!0;break;case"ArrowRight":n.next(),i=!0;break;case"t":case"T":n.today(),i=!0;break;case"Escape":window.isFullScreen&&((r=window.toggleFullScreen)==null||r.call(window),i=!0);break}i&&(t.preventDefault(),t.stopPropagation())}document.addEventListener("keydown",a,!0),e.on("eventsSet",()=>{te==="events"&&(vt(),bt())}),B=()=>{document.removeEventListener("keydown",a,!0),ve(),he()}}function vt(){const e=window.calendar;if(!e){N=[];return}N=e.getEvents().filter(a=>{var o;const t=(o=a.extendedProps)==null?void 0:o.tipo;return t!=="resumen-dia"&&t!=="festivo"}).sort((a,t)=>{const o=a.start||new Date(0),n=t.start||new Date(0);return o<n?-1:o>n?1:(a.title||"").localeCompare(t.title||"")})}function bt(){var t;if(he(),O<0||O>=N.length)return;const e=N[O];if(!e)return;const a=document.querySelector(`[data-event-id="${e.id}"]`)||document.querySelector(`.fc-event[data-event="${e.id}"]`);if(a)a.classList.add("keyboard-focused-event"),a.scrollIntoView({behavior:"smooth",block:"nearest"});else{const o=document.querySelectorAll(".fc-event");for(const n of o)if(n.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){n.classList.add("keyboard-focused-event"),n.scrollIntoView({behavior:"smooth",block:"nearest"});break}}e.start&&(j=new Date(e.start)),be()}function he(){document.querySelectorAll(".keyboard-focused-event").forEach(e=>{e.classList.remove("keyboard-focused-event")})}function wt(e){const a=e.getFullYear(),t=String(e.getMonth()+1).padStart(2,"0"),o=String(e.getDate()).padStart(2,"0");return`${a}-${t}-${o}`}function xt(){if(ve(),!j)return;const e=wt(j),a=window.calendar;if(!a)return;const t=a.view.type;let o=null;t==="dayGridMonth"?o=document.querySelector(`.fc-daygrid-day[data-date="${e}"]`):t==="resourceTimelineWeek"?(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(i=>{i.dataset.date&&i.dataset.date.startsWith(e)&&(o=i)}),o||(o=document.querySelector(`.fc-timeline-slot-lane[data-date^="${e}"]`))):t==="resourceTimeGridDay"&&(o=document.querySelector(".fc-col-header-cell")),o&&(o.classList.add("keyboard-focused-day"),o.scrollIntoView({behavior:"smooth",block:"nearest",inline:"nearest"})),be()}function ve(){document.querySelectorAll(".keyboard-focused-day").forEach(e=>{e.classList.remove("keyboard-focused-day")})}function be(){let e=document.getElementById("keyboard-nav-indicator");if(e||(e=document.createElement("div"),e.id="keyboard-nav-indicator",e.className="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm",document.body.appendChild(e)),te==="events"){const a=N[O],t=(a==null?void 0:a.title)||"Sin evento",o=`${O+1}/${N.length}`;e.innerHTML=`
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
        `}clearTimeout(e._hideTimeout),e.style.display="block",e._hideTimeout=setTimeout(()=>{e.style.display="none"},4e3)}function St(){if(document.getElementById("keyboard-nav-styles"))return;const e=document.createElement("style");e.id="keyboard-nav-styles",e.textContent=`
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
    `,document.head.appendChild(e)}ut(()=>{yt(),St(),setTimeout(()=>{ht()},500)},{selector:'#calendario[data-calendar-type="salidas"]'});pt(()=>{if(B&&(B(),B=null),window.calendar)try{window.calendar.destroy(),window.calendar=null}catch(a){console.warn("Error al limpiar calendario de salidas:",a)}const e=document.getElementById("keyboard-nav-indicator");e&&e.remove()});
