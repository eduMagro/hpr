async function ie(e,a){var t,o,n,s;try{const l=(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.planificacion;if(!l)return[];const d=new URLSearchParams({tipo:"events",viewType:e||"",start:a.startStr||"",end:a.endStr||"",t:Date.now()}),m=await fetch(`${l}?${d.toString()}`);if(!m.ok)return console.error("Error eventos",m.status),[];const r=await m.json();let i=Array.isArray(r)?r:Array.isArray(r==null?void 0:r.events)?r.events:[];const c=((n=document.getElementById("solo-salidas"))==null?void 0:n.checked)||!1,u=((s=document.getElementById("solo-planillas"))==null?void 0:s.checked)||!1,b=i.filter(f=>{var p;return((p=f.extendedProps)==null?void 0:p.tipo)==="resumen-dia"}),y=i.filter(f=>{var p;return((p=f.extendedProps)==null?void 0:p.tipo)!=="resumen-dia"});let g=y;return c&&!u?g=y.filter(f=>{var h;return((h=f.extendedProps)==null?void 0:h.tipo)==="salida"}):u&&!c&&(g=y.filter(f=>{var h;const p=(h=f.extendedProps)==null?void 0:h.tipo;return p==="planilla"||p==="festivo"})),[...g,...b]}catch(l){return console.error("fetch eventos falló:",l),[]}}async function le(e,a){var l,d;const t=(d=(l=window.AppSalidas)==null?void 0:l.routes)==null?void 0:d.planificacion;if(!t)return[];const o=new URLSearchParams({tipo:"resources",viewType:e,start:a.startStr||"",end:a.endStr||""}),n=await fetch(`${t}?${o.toString()}`,{method:"GET"});if(!n.ok)throw new Error("Error cargando recursos");const s=await n.json();return Array.isArray(s)?s:Array.isArray(s==null?void 0:s.resources)?s.resources:[]}function G(e,a){const t=e.event.extendedProps||{};if(t.tipo!=="festivo"){if(t.tipo==="planilla"){const o=`
      ✅ Fabricados: ${R(t.fabricadosKg)} kg<br>
      🔄 Fabricando: ${R(t.fabricandoKg)} kg<br>
      ⏳ Pendientes: ${R(t.pendientesKg)} kg
    `;tippy(e.el,{content:o,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}t.tipo==="salida"&&t.comentario&&t.comentario.trim()&&tippy(e.el,{content:t.comentario,allowHTML:!0,theme:"light-border",placement:"top",animation:"shift-away",arrow:!0})}}function R(e){return e!=null?Number(e).toLocaleString():0}let N=null;function k(){N&&(N.remove(),N=null,document.removeEventListener("click",k),document.removeEventListener("contextmenu",k,!0),document.removeEventListener("scroll",k,!0),window.removeEventListener("resize",k),window.removeEventListener("keydown",K))}function K(e){e.key==="Escape"&&k()}function de(e,a,t){k();const o=document.createElement("div");o.className="fc-contextmenu",Object.assign(o.style,{position:"fixed",top:a+"px",left:e+"px",zIndex:99999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",fontFamily:"system-ui, -apple-system, Segoe UI, Roboto, sans-serif"}),o.innerHTML=t,document.body.appendChild(o),N=o;const n=o.getBoundingClientRect(),s=Math.max(0,n.right-window.innerWidth+8),l=Math.max(0,n.bottom-window.innerHeight+8);return(s||l)&&(o.style.left=Math.max(8,e-s)+"px",o.style.top=Math.max(8,a-l)+"px"),setTimeout(()=>{document.addEventListener("click",k),document.addEventListener("contextmenu",k,!0),document.addEventListener("scroll",k,!0),window.addEventListener("resize",k),window.addEventListener("keydown",K)},0),o}function ce(e,a,{headerHtml:t="",items:o=[]}={}){const n=`
    <div class="ctx-menu-container">
      ${t?`<div class="ctx-menu-header">${t}</div>`:""}
      ${o.map((l,d)=>`
        <button type="button"
          class="ctx-menu-item${l.danger?" ctx-menu-danger":""}"
          data-idx="${d}">
          ${l.icon?`<span class="ctx-menu-icon">${l.icon}</span>`:""}
          <span class="ctx-menu-label">${l.label}</span>
        </button>`).join("")}
    </div>
  `,s=de(e,a,n);return s.querySelectorAll(".ctx-menu-item").forEach(l=>{l.addEventListener("click",async d=>{var i;d.preventDefault(),d.stopPropagation();const m=Number(l.dataset.idx),r=(i=o[m])==null?void 0:i.onClick;k();try{await(r==null?void 0:r())}catch(c){console.error(c)}})}),s}function ue(e){if(!e||typeof e!="string")return"";const a=e.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s|T|$)/);if(a){const t=a[1],o=a[2].padStart(2,"0"),n=a[3].padStart(2,"0");return`${t}-${o}-${n}`}return e}(function(){if(document.getElementById("swal-anims"))return;const a=document.createElement("style");a.id="swal-anims",a.textContent=`
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
  `,document.head.appendChild(a)})();function me(e){const a=e.extendedProps||{};if(Array.isArray(a.planillas_ids)&&a.planillas_ids.length)return a.planillas_ids;const t=(e.id||"").match(/planilla-(\d+)/);return t?[Number(t[1])]:[]}async function U(e,a){var t,o;try{k()}catch{}if(!e)return Swal.fire("⚠️","ID de salida inválido.","warning");try{const n=await fetch(`${(o=(t=window.AppSalidas)==null?void 0:t.routes)==null?void 0:o.informacionPaquetesSalida}?salida_id=${e}`,{headers:{Accept:"application/json"}});if(!n.ok)throw new Error("Error al cargar información de la salida");const{salida:s,paquetesAsignados:l,paquetesDisponibles:d,paquetesTodos:m,filtros:r}=await n.json();pe(s,l,d,m||[],r||{obras:[],planillas:[],obrasRelacionadas:[]},a)}catch(n){console.error(n),Swal.fire("❌","Error al cargar la información de la salida","error")}}function pe(e,a,t,o,n,s){window._gestionPaquetesData={salida:e,paquetesAsignados:a,paquetesDisponibles:t,paquetesTodos:o,filtros:n,mostrarTodos:!1};const l=fe(e,a,t,n);Swal.fire({title:`📦 Gestionar Paquetes - Salida ${e.codigo_salida||e.id}`,html:l,width:Math.min(window.innerWidth*.95,1200),showConfirmButton:!0,showCancelButton:!0,confirmButtonText:"💾 Guardar Cambios",cancelButtonText:"Cancelar",focusConfirm:!1,customClass:{popup:"w-full max-w-screen-xl"},didOpen:()=>{Z(),ye(),we(),setTimeout(()=>{be()},100)},willClose:()=>{w.cleanup&&w.cleanup();const d=document.getElementById("modal-keyboard-indicator");d&&d.remove()},preConfirm:()=>xe()}).then(async d=>{d.isConfirmed&&d.value&&await Se(e.id,d.value,s)})}function fe(e,a,t,o){var r,i;const n=a.reduce((c,u)=>c+(parseFloat(u.peso)||0),0);let s="";e.salida_clientes&&e.salida_clientes.length>0&&(s='<div class="col-span-2"><strong>Obras/Clientes:</strong><br>',e.salida_clientes.forEach(c=>{var g,f,p,h,E;const u=((g=c.obra)==null?void 0:g.obra)||"Obra desconocida",b=(f=c.obra)!=null&&f.cod_obra?`(${c.obra.cod_obra})`:"",y=((p=c.cliente)==null?void 0:p.empresa)||((E=(h=c.obra)==null?void 0:h.cliente)==null?void 0:E.empresa)||"";s+=`<span class="text-xs">• ${u} ${b}`,y&&(s+=` - ${y}`),s+="</span><br>"}),s+="</div>");const l=`
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><strong>Código:</strong> ${e.codigo_salida||"N/A"}</div>
                <div><strong>Código SAGE:</strong> ${e.codigo_sage||"Sin asignar"}</div>
                <div><strong>Fecha salida:</strong> ${new Date(e.fecha_salida).toLocaleString("es-ES")}</div>
                <div><strong>Estado:</strong> ${e.estado||"pendiente"}</div>
                <div><strong>Empresa transporte:</strong> ${((r=e.empresa_transporte)==null?void 0:r.nombre)||"Sin asignar"}</div>
                <div><strong>Camión:</strong> ${((i=e.camion)==null?void 0:i.modelo)||"Sin asignar"}</div>
                ${s}
            </div>
        </div>
    `,d=((o==null?void 0:o.obras)||[]).map(c=>`<option value="${c.id}">${c.cod_obra||""} - ${c.obra||"Sin nombre"}</option>`).join(""),m=((o==null?void 0:o.planillas)||[]).map(c=>`<option value="${c.id}" data-obra-id="${c.obra_id||""}">${c.codigo||"Sin código"}</option>`).join("");return`
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
                        ${j(a)}
                    </div>
                </div>

                <!-- Paquetes disponibles -->
                <div class="bg-gray-50 border-2 border-gray-300 rounded-lg p-3">
                    <div class="font-semibold text-gray-900 mb-2 flex items-center justify-between">
                        <span>📋 Paquetes Disponibles</span>
                        <button type="button" id="btn-toggle-todos-modal"
                                class="text-xs px-3 py-1.5 rounded-md transition-colors shadow-sm font-medium bg-blue-500 hover:bg-blue-600 text-white">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Incluir otros paquetes
                            </span>
                        </button>
                    </div>

                    <!-- Info del modo actual -->
                    <div id="info-modo-paquetes" class="bg-blue-50 border border-blue-200 rounded-md px-3 py-2 mb-3">
                        <p class="text-xs text-blue-800">
                            <strong>📋 Mostrando:</strong> Solo paquetes de las obras de esta salida
                        </p>
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
                                    ${m}
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
                        ${j(t)}
                    </div>
                </div>
            </div>
        </div>
    `}function j(e){return!e||e.length===0?'<div class="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes">Sin paquetes</div>':e.map(a=>{var t,o,n,s,l,d,m,r,i,c,u,b,y,g,f,p;return`
        <div
            class="paquete-item-salida bg-white border border-gray-300 rounded p-2 mb-2 cursor-move hover:shadow-md transition-shadow"
            draggable="true"
            data-paquete-id="${a.id}"
            data-peso="${a.peso||0}"
            data-obra-id="${((o=(t=a.planilla)==null?void 0:t.obra)==null?void 0:o.id)||""}"
            data-obra="${((s=(n=a.planilla)==null?void 0:n.obra)==null?void 0:s.obra)||""}"
            data-planilla-id="${((l=a.planilla)==null?void 0:l.id)||""}"
            data-planilla="${((d=a.planilla)==null?void 0:d.codigo)||""}"
            data-cliente="${((r=(m=a.planilla)==null?void 0:m.cliente)==null?void 0:r.empresa)||""}"
        >
            <div class="flex items-center justify-between text-xs">
                <span class="font-medium">📦 ${a.codigo||"Paquete #"+a.id}</span>
                <span class="text-gray-600">${parseFloat(a.peso||0).toFixed(2)} kg</span>
            </div>
            <div class="text-xs text-gray-500 mt-1">
                <div>📄 ${((i=a.planilla)==null?void 0:i.codigo)||a.planilla_id}</div>
                <div>🏗️ ${((u=(c=a.planilla)==null?void 0:c.obra)==null?void 0:u.cod_obra)||""} - ${((y=(b=a.planilla)==null?void 0:b.obra)==null?void 0:y.obra)||"N/A"}</div>
                <div>👤 ${((f=(g=a.planilla)==null?void 0:g.cliente)==null?void 0:f.empresa)||"Sin cliente"}</div>
                ${(p=a.nave)!=null&&p.obra?`<div class="text-blue-600 font-medium">📍 ${a.nave.obra}</div>`:""}
            </div>
        </div>
    `}).join("")}function ye(e){const a=document.getElementById("btn-toggle-todos-modal"),t=document.getElementById("filtro-obra-modal"),o=document.getElementById("filtro-planilla-modal"),n=document.getElementById("btn-limpiar-filtros-modal");a&&a.addEventListener("click",()=>{const s=window._gestionPaquetesData;if(!s)return;s.mostrarTodos=!s.mostrarTodos,s.mostrarTodos?(a.classList.remove("bg-blue-500","hover:bg-blue-600"),a.classList.add("bg-orange-500","hover:bg-orange-600"),a.innerHTML=`
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Solo esta salida
                    </span>
                `):(a.classList.remove("bg-orange-500","hover:bg-orange-600"),a.classList.add("bg-blue-500","hover:bg-blue-600"),a.innerHTML=`
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Incluir otros paquetes
                    </span>
                `);const l=document.getElementById("info-modo-paquetes");if(l){const d=s.mostrarTodos?s.paquetesTodos.length:s.paquetesDisponibles.length;l.innerHTML=`
                    <p class="text-xs text-blue-800">
                        <strong>${s.mostrarTodos?"🌐":"📋"} Mostrando:</strong>
                        ${s.mostrarTodos?"Todos los paquetes disponibles":"Solo paquetes de las obras de esta salida"}
                        (${d} paquetes)
                    </p>
                `}t&&(t.value=""),o&&(o.value=""),ge()}),t&&t.addEventListener("change",()=>{B()}),o&&o.addEventListener("change",()=>{B()}),n&&n.addEventListener("click",()=>{t&&(t.value=""),o&&(o.value=""),B()})}function ge(){const e=window._gestionPaquetesData;if(!e)return;const a=document.querySelector('[data-zona="disponibles"]');if(!a)return;const t=e.mostrarTodos?e.paquetesTodos:e.paquetesDisponibles,o=document.querySelector('[data-zona="asignados"]'),n=new Set;o&&o.querySelectorAll(".paquete-item-salida").forEach(l=>{n.add(parseInt(l.dataset.paqueteId))});const s=t.filter(l=>!n.has(l.id));a.innerHTML=j(s),Z(),B()}function B(){const e=document.getElementById("filtro-obra-modal"),a=document.getElementById("filtro-planilla-modal"),t=(e==null?void 0:e.value)||"",o=(a==null?void 0:a.value)||"",n=document.querySelector('[data-zona="disponibles"]');if(!n)return;const s=n.querySelectorAll(".paquete-item-salida");let l=0;s.forEach(m=>{let r=!0;t&&m.dataset.obraId!==t&&(r=!1),o&&m.dataset.planillaId!==o&&(r=!1),m.style.display=r?"":"none",r&&l++});let d=n.querySelector(".placeholder-sin-paquetes");l===0?(d||(d=document.createElement("div"),d.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",d.textContent="No hay paquetes que coincidan con el filtro",n.appendChild(d)),d.style.display=""):d&&(d.style.display="none")}let w={zonaActiva:"asignados",indiceFocused:-1,cleanup:null};function be(){w.cleanup&&w.cleanup(),w.zonaActiva="asignados",w.indiceFocused=0,$();function e(a){var c;if(!document.querySelector(".swal2-container"))return;const t=a.target.tagName.toLowerCase(),o=t==="select";if((t==="input"||t==="textarea")&&a.key!=="Escape")return;const s=document.querySelector('[data-zona="asignados"]'),l=document.querySelector('[data-zona="disponibles"]');if(!s||!l)return;const d=w.zonaActiva==="asignados"?s:l,m=Array.from(d.querySelectorAll('.paquete-item-salida:not([style*="display: none"])')),r=m.length;let i=!1;if(!o)switch(a.key){case"ArrowDown":r>0&&(w.indiceFocused=(w.indiceFocused+1)%r,$(),i=!0);break;case"ArrowUp":r>0&&(w.indiceFocused=w.indiceFocused<=0?r-1:w.indiceFocused-1,$(),i=!0);break;case"ArrowLeft":case"ArrowRight":w.zonaActiva=w.zonaActiva==="asignados"?"disponibles":"asignados",w.indiceFocused=0,$(),i=!0;break;case"Tab":a.preventDefault(),w.zonaActiva=w.zonaActiva==="asignados"?"disponibles":"asignados",w.indiceFocused=0,$(),i=!0;break;case"Enter":{if(r>0&&w.indiceFocused>=0){const u=m[w.indiceFocused];if(u){ve(u);const b=Array.from(d.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));w.indiceFocused>=b.length&&(w.indiceFocused=Math.max(0,b.length-1)),$(),i=!0}}break}case"Home":w.indiceFocused=0,$(),i=!0;break;case"End":w.indiceFocused=Math.max(0,r-1),$(),i=!0;break}if(i){a.preventDefault(),a.stopPropagation();return}switch(a.key){case"o":case"O":{const u=document.getElementById("filtro-obra-modal");u&&(u.focus(),i=!0);break}case"p":case"P":{const u=document.getElementById("filtro-planilla-modal");u&&(u.focus(),i=!0);break}case"l":case"L":{const u=document.getElementById("btn-limpiar-filtros-modal");u&&(u.click(),(c=document.activeElement)==null||c.blur(),$(),i=!0);break}case"+":case"t":case"T":{const u=document.getElementById("btn-toggle-todos-modal");u&&(u.click(),i=!0);break}case"/":case"f":case"F":{const u=document.getElementById("filtro-obra-modal");u&&(u.focus(),i=!0);break}case"Escape":o&&(document.activeElement.blur(),$(),i=!0);break;case"s":case"S":{if(a.ctrlKey||a.metaKey){const u=document.querySelector(".swal2-confirm");u&&(u.click(),i=!0)}break}}i&&(a.preventDefault(),a.stopPropagation())}document.addEventListener("keydown",e,!0),w.cleanup=()=>{document.removeEventListener("keydown",e,!0),Y()}}function $(){Y();const e=document.querySelector('[data-zona="asignados"]'),a=document.querySelector('[data-zona="disponibles"]');if(!e||!a)return;w.zonaActiva==="asignados"?(e.classList.add("zona-activa-keyboard"),a.classList.remove("zona-activa-keyboard")):(a.classList.add("zona-activa-keyboard"),e.classList.remove("zona-activa-keyboard"));const t=w.zonaActiva==="asignados"?e:a,o=Array.from(t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])'));if(o.length>0&&w.indiceFocused>=0){const n=Math.min(w.indiceFocused,o.length-1),s=o[n];s&&(s.classList.add("paquete-focused-keyboard"),s.scrollIntoView({behavior:"smooth",block:"nearest"}))}he()}function Y(){document.querySelectorAll(".paquete-focused-keyboard").forEach(e=>{e.classList.remove("paquete-focused-keyboard")}),document.querySelectorAll(".zona-activa-keyboard").forEach(e=>{e.classList.remove("zona-activa-keyboard")})}function ve(e){const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]');if(!a||!t)return;const o=e.closest("[data-zona]"),n=o.dataset.zona==="asignados"?t:a,s=n.querySelector(".placeholder-sin-paquetes");if(s&&s.remove(),n.appendChild(e),o.querySelectorAll(".paquete-item-salida").length===0){const d=document.createElement("div");d.className="text-gray-400 text-sm text-center py-4 placeholder-sin-paquetes",d.textContent="Sin paquetes",o.appendChild(d)}X(e),J()}function he(){let e=document.getElementById("modal-keyboard-indicator");e||(e=document.createElement("div"),e.id="modal-keyboard-indicator",e.className="fixed bottom-20 right-4 bg-gray-900 text-white px-3 py-2 rounded-lg shadow-lg z-[10000] text-xs max-w-xs",document.body.appendChild(e));const a=document.querySelector('[data-zona="asignados"]'),t=document.querySelector('[data-zona="disponibles"]'),o=(a==null?void 0:a.querySelectorAll(".paquete-item-salida").length)||0,n=(t==null?void 0:t.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0,s=w.zonaActiva==="asignados"?`📦 Asignados (${o})`:`📋 Disponibles (${n})`;e.innerHTML=`
        <div class="flex items-center gap-2 mb-2">
            <span class="${w.zonaActiva==="asignados"?"bg-green-500":"bg-gray-500"} text-white text-xs px-2 py-0.5 rounded">${s}</span>
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
    `,clearTimeout(e._checkTimeout),e._checkTimeout=setTimeout(()=>{document.querySelector(".swal2-container")||e.remove()},500)}function we(){if(document.getElementById("modal-keyboard-styles"))return;const e=document.createElement("style");e.id="modal-keyboard-styles",e.textContent=`
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
    `,document.head.appendChild(e)}function X(e){e.addEventListener("dragstart",a=>{e.style.opacity="0.5",a.dataTransfer.setData("text/plain",e.dataset.paqueteId)}),e.addEventListener("dragend",a=>{e.style.opacity="1"})}function Z(){document.querySelectorAll(".paquete-item-salida").forEach(e=>{X(e)}),document.querySelectorAll(".drop-zone").forEach(e=>{e.addEventListener("dragover",a=>{a.preventDefault();const t=e.dataset.zona;e.style.backgroundColor=t==="asignados"?"#d1fae5":"#e0f2fe"}),e.addEventListener("dragleave",a=>{e.style.backgroundColor=""}),e.addEventListener("drop",a=>{a.preventDefault(),e.style.backgroundColor="";const t=a.dataTransfer.getData("text/plain"),o=document.querySelector(`.paquete-item-salida[data-paquete-id="${t}"]`);if(o){const n=e.querySelector(".placeholder-sin-paquetes");n&&n.remove(),e.appendChild(o),J()}})})}function J(){const e=document.querySelector('[data-zona="asignados"]'),a=e==null?void 0:e.querySelectorAll(".paquete-item-salida");let t=0;a==null||a.forEach(s=>{const l=parseFloat(s.dataset.peso)||0;t+=l});const o=document.getElementById("peso-asignados");o&&(o.textContent=`${t.toFixed(2)} kg`);const n=window._gestionPaquetesData;if(n){const s=document.getElementById("info-modo-paquetes");if(s){const l=document.querySelector('[data-zona="disponibles"]'),d=(l==null?void 0:l.querySelectorAll('.paquete-item-salida:not([style*="display: none"])').length)||0;s.innerHTML=`
                <p class="text-xs text-blue-800">
                    <strong>${n.mostrarTodos?"🌐":"📋"} Mostrando:</strong>
                    ${n.mostrarTodos?"Todos los paquetes disponibles":"Solo paquetes de las obras de esta salida"}
                    (${d} paquetes)
                </p>
            `}}}function xe(){const e=document.querySelector('[data-zona="asignados"]');return{paquetes_ids:Array.from((e==null?void 0:e.querySelectorAll(".paquete-item-salida"))||[]).map(t=>parseInt(t.dataset.paqueteId))}}async function Se(e,a,t){var o,n,s,l;try{const m=await(await fetch((n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.guardarPaquetesSalida,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf},body:JSON.stringify({salida_id:e,paquetes_ids:a.paquetes_ids})})).json();m.success?(await Swal.fire({icon:"success",title:"✅ Cambios Guardados",text:"Los paquetes de la salida se han actualizado correctamente.",timer:2e3}),t&&(t.refetchEvents(),(l=t.refetchResources)==null||l.call(t))):await Swal.fire("⚠️",m.message||"No se pudieron guardar los cambios","warning")}catch(d){console.error(d),Swal.fire("❌","Error al guardar los paquetes","error")}}async function Ee(e,a,t){try{k()}catch{}window.Livewire.dispatch("abrirComentario",{salidaId:e}),window._calendarRef=t}function ke(e){return e?typeof e=="string"?e.split(",").map(t=>t.trim()).filter(Boolean):Array.from(e).map(t=>typeof t=="object"&&(t==null?void 0:t.id)!=null?t.id:t).map(String).map(t=>t.trim()).filter(Boolean):[]}async function Te(e){var s,l;const a=(l=(s=window.AppSalidas)==null?void 0:s.routes)==null?void 0:l.informacionPlanillas;if(!a)throw new Error("Ruta 'informacionPlanillas' no configurada");const t=`${a}?ids=${encodeURIComponent(e.join(","))}`,o=await fetch(t,{headers:{Accept:"application/json"}});if(!o.ok){const d=await o.text().catch(()=>"");throw new Error(`GET ${t} -> ${o.status} ${d}`)}const n=await o.json();return Array.isArray(n==null?void 0:n.planillas)?n.planillas:[]}function Q(e){if(!e)return!1;const t=new Date(e+"T00:00:00").getDay();return t===0||t===6}function $e(e){return`
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

          <tbody>${e.map((t,o)=>{var c,u;const n=((c=t.obra)==null?void 0:c.codigo)||"",s=((u=t.obra)==null?void 0:u.nombre)||"",l=t.seccion||"",d=t.descripcion||"",m=t.codigo||`Planilla ${t.id}`,r=t.peso_total?parseFloat(t.peso_total).toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})+" kg":"",i=ue(t.fecha_estimada_entrega);return`
<tr style="opacity:0; transform:translateY(4px); animation: swalRowIn .22s ease-out forwards; animation-delay:${o*18}ms;">
  <td class="px-2 py-1 text-xs">${t.id}</td>
  <td class="px-2 py-1 text-xs">${n}</td>
  <td class="px-2 py-1 text-xs">${s}</td>
  <td class="px-2 py-1 text-xs">${l}</td>
  <td class="px-2 py-1 text-xs">${d}</td>
  <td class="px-2 py-1 text-xs">${m}</td>
  <td class="px-2 py-1 text-xs text-right font-medium">${r}</td>
  <td class="px-2 py-1">
    <input type="date" class="swal2-input !m-0 !w-auto" data-planilla-id="${t.id}" value="${i}">
  </td>
</tr>`}).join("")}</tbody>
        </table>
      </div>
    </div>`}function De(e){const a={};return document.querySelectorAll('input[type="date"][data-planilla-id]').forEach(o=>{const n=parseInt(o.dataset.planillaId),s=o.value,l=e.find(d=>d.id===n);s&&l&&l.peso_total&&(a[s]||(a[s]={peso:0,planillas:0,esFinDeSemana:Q(s)}),a[s].peso+=parseFloat(l.peso_total),a[s].planillas+=1)}),a}function H(e){const a=De(e),t=document.getElementById("resumen-contenido");if(!t)return;const o=Object.keys(a).sort();if(o.length===0){t.innerHTML='<span class="text-gray-500">Selecciona fechas para ver el resumen...</span>';return}const n=o.map(d=>{const m=a[d],r=new Date(d+"T00:00:00").toLocaleDateString("es-ES",{weekday:"short",day:"2-digit",month:"2-digit",year:"numeric"}),i=m.peso.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2}),c=m.esFinDeSemana?"bg-orange-100 border-orange-300 text-orange-800":"bg-green-100 border-green-300 text-green-800",u=m.esFinDeSemana?"🏖️":"📦";return`
            <div class="inline-block m-1 px-2 py-1 rounded border ${c}">
                <span class="font-medium">${u} ${r}</span>
                <br>
                <span class="text-xs">${i} kg (${m.planillas} planilla${m.planillas!==1?"s":""})</span>
            </div>
        `}).join(""),s=o.reduce((d,m)=>d+a[m].peso,0),l=o.reduce((d,m)=>d+a[m].planillas,0);t.innerHTML=`
        <div class="mb-2">${n}</div>
        <div class="text-sm font-medium text-blue-900 pt-2 border-t border-blue-200">
            📊 Total: ${s.toLocaleString("es-ES",{minimumFractionDigits:2,maximumFractionDigits:2})} kg 
            (${l} planilla${l!==1?"s":""})
        </div>
    `}async function Le(e){var o,n,s;const a=(n=(o=window.AppSalidas)==null?void 0:o.routes)==null?void 0:n.actualizarFechasPlanillas;if(!a)throw new Error("Ruta 'actualizarFechasPlanillas' no configurada");const t=await fetch(a,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(s=window.AppSalidas)==null?void 0:s.csrf,Accept:"application/json"},body:JSON.stringify({planillas:e})});if(!t.ok){const l=await t.text().catch(()=>"");throw new Error(`PUT ${a} -> ${t.status} ${l}`)}return t.json().catch(()=>({}))}async function qe(e,a){var t,o;try{const n=Array.from(new Set(ke(e))).map(Number).filter(Boolean);if(!n.length)return Swal.fire("⚠️","No hay planillas en la agrupación.","warning");const s=await Te(n);if(!s.length)return Swal.fire("⚠️","No se han encontrado planillas.","warning");const d=`
      <div id="swal-drag" style="display:flex;align-items:center;gap:.5rem;cursor:move;user-select:none;touch-action:none;padding:6px 0;">
        <span>🗓️ Cambiar fechas de entrega</span>
        <span style="margin-left:auto;font-size:12px;opacity:.7;">(arrástrame)</span>
      </div>
    `+$e(s),{isConfirmed:m}=await Swal.fire({title:"",html:d,width:Math.min(window.innerWidth*.98,1200),customClass:{popup:"w-full max-w-screen-xl"},showCancelButton:!0,confirmButtonText:"💾 Guardar",cancelButtonText:"Cancelar",focusConfirm:!1,showClass:{popup:"swal-fade-in-zoom"},hideClass:{popup:"swal-fade-out"},didOpen:u=>{Ce(u),F("#swal-drag",!1),setTimeout(()=>{const y=Swal.getHtmlContainer().querySelector('input[type="date"]');y==null||y.focus({preventScroll:!0})},120),Swal.getHtmlContainer().querySelectorAll('input[type="date"]').forEach(y=>{y.addEventListener("change",function(){Q(this.value)?this.classList.add("weekend-date"):this.classList.remove("weekend-date"),H(s)})}),setTimeout(()=>{H(s)},100)}});if(!m)return;const r=Swal.getHtmlContainer().querySelectorAll("input[data-planilla-id]"),i=Array.from(r).map(u=>({id:Number(u.getAttribute("data-planilla-id")),fecha_estimada_entrega:u.value})),c=await Le(i);await Swal.fire(c.success?"✅":"⚠️",c.message||(c.success?"Fechas actualizadas":"No se pudieron actualizar"),c.success?"success":"warning"),c.success&&a&&((t=a.refetchEvents)==null||t.call(a),(o=a.refetchResources)==null||o.call(a))}catch(n){console.error("[CambiarFechasEntrega] error:",n),Swal.fire("❌",(n==null?void 0:n.message)||"Ocurrió un error al actualizar las fechas.","error")}}function W(e,a){e.el.addEventListener("mousedown",k),e.el.addEventListener("contextmenu",t=>{t.preventDefault(),t.stopPropagation();const o=e.event,n=o.extendedProps||{},s=n.tipo||"planilla";let l="";if(s==="salida"){if(n.clientes&&Array.isArray(n.clientes)&&n.clientes.length>0){const r=n.clientes.map(i=>i.nombre).filter(Boolean).join(", ");r&&(l+=`<br><span style="font-weight:400;color:#4b5563;font-size:11px">👤 ${r}</span>`)}n.obras&&Array.isArray(n.obras)&&n.obras.length>0&&(l+='<br><span style="font-weight:400;color:#4b5563;font-size:11px">🏗️ ',l+=n.obras.map(r=>{const i=r.codigo?`(${r.codigo})`:"";return`${r.nombre} ${i}`}).join(", "),l+="</span>")}const d=`
      <div style="padding:10px 12px; font-weight:600;">
        ${o.title??"Evento"}${l}<br>
        <span style="font-weight:400;color:#6b7280;font-size:12px">
          ${new Date(o.start).toLocaleString()} — ${new Date(o.end).toLocaleString()}
        </span>
      </div>
    `;let m=[];if(s==="planilla"){const r=me(o);m=[{label:"Gestionar Salidas y Paquetes",icon:"📦",onClick:()=>window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${r.join(",")}`},{label:"Cambiar fechas de entrega",icon:"🗓️",onClick:()=>qe(r,a)}]}else if(s==="salida"){const r=n.salida_id||o.id;n.empresa_id,n.empresa,m=[{label:"Abrir salida",icon:"🧾",onClick:()=>window.open(`/salidas-ferralla/${r}`,"_blank")},{label:"Gestionar paquetes",icon:"📦",onClick:()=>U(r,a)},{label:"Agregar comentario",icon:"✍️",onClick:()=>Ee(r,n.comentario||"",a)}]}else m=[{label:"Abrir",icon:"🧾",onClick:()=>window.open(n.url||"#","_blank")}];ce(t.clientX,t.clientY,{headerHtml:d,items:m})})}function Ce(e){e.style.transform="none",e.style.position="fixed",e.style.margin="0";const a=e.offsetWidth,t=e.offsetHeight,o=Math.max(0,Math.round((window.innerWidth-a)/2)),n=Math.max(0,Math.round((window.innerHeight-t)/2));e.style.left=`${o}px`,e.style.top=`${n}px`}function F(e=".swal2-title",a=!1){const t=Swal.getPopup(),o=Swal.getHtmlContainer();let n=(e?(o==null?void 0:o.querySelector(e))||(t==null?void 0:t.querySelector(e)):null)||t;if(!t||!n)return;a&&F.__lastPos&&(t.style.left=F.__lastPos.left,t.style.top=F.__lastPos.top,t.style.transform="none"),n.style.cursor="move",n.style.touchAction="none";const s=y=>{var g;return((g=y.closest)==null?void 0:g.call(y,"input, textarea, select, button, a, label, [contenteditable]"))!=null};let l=!1,d=0,m=0,r=0,i=0;const c=y=>{if(!n.contains(y.target)||s(y.target))return;l=!0,document.body.style.userSelect="none";const g=t.getBoundingClientRect();t.style.left=`${g.left}px`,t.style.top=`${g.top}px`,t.style.transform="none",r=parseFloat(t.style.left||g.left),i=parseFloat(t.style.top||g.top),d=y.clientX,m=y.clientY,document.addEventListener("pointermove",u),document.addEventListener("pointerup",b,{once:!0})},u=y=>{if(!l)return;const g=y.clientX-d,f=y.clientY-m;let p=r+g,h=i+f;const E=t.offsetWidth,A=t.offsetHeight,oe=-E+40,ne=window.innerWidth-40,se=-A+40,re=window.innerHeight-40;p=Math.max(oe,Math.min(ne,p)),h=Math.max(se,Math.min(re,h)),t.style.left=`${p}px`,t.style.top=`${h}px`},b=()=>{l=!1,document.body.style.userSelect="",a&&(F.__lastPos={left:t.style.left,top:t.style.top}),document.removeEventListener("pointermove",u)};n.addEventListener("pointerdown",c)}document.addEventListener("DOMContentLoaded",function(){window.addEventListener("comentarioGuardado",e=>{const{salidaId:a,comentario:t}=e.detail,o=window._calendarRef;if(o){const n=o.getEventById(`salida-${a}`);n&&(n.setExtendedProp("comentario",t),n._def&&n._def.extendedProps&&(n._def.extendedProps.comentario=t)),typeof Swal<"u"&&Swal.fire({icon:"success",title:"Comentario guardado",text:"El comentario se ha guardado correctamente",timer:2e3,showConfirmButton:!1,toast:!0,position:"top-end"})}})});function V(e){var l,d;if(!e)return;const a=new Date(e),t={year:"numeric",month:"long"};let o=a.toLocaleDateString("es-ES",t);o=o.charAt(0).toUpperCase()+o.slice(1);const n=document.querySelector("#resumen-mensual-fecha");n&&(n.textContent=`(${o})`);const s=(d=(l=window.AppSalidas)==null?void 0:l.routes)==null?void 0:d.totales;s&&fetch(`${s}?fecha=${encodeURIComponent(e)}`).then(m=>m.json()).then(m=>{const r=m.semana||{};q("#resumen-semanal-peso",`📦 ${_(r.peso)} kg`),q("#resumen-semanal-longitud",`📏 ${_(r.longitud)} m`),q("#resumen-semanal-diametro",r.diametro!=null&&!isNaN(r.diametro)?`⌀ ${Number(r.diametro).toFixed(2)} mm`:"");const i=m.mes||{};q("#resumen-mensual-peso",`📦 ${_(i.peso)} kg`),q("#resumen-mensual-longitud",`📏 ${_(i.longitud)} m`),q("#resumen-mensual-diametro",i.diametro!=null&&!isNaN(i.diametro)?`⌀ ${Number(i.diametro).toFixed(2)} mm`:"")}).catch(m=>console.error("❌ Error al actualizar los totales:",m))}function _(e){return e!=null?Number(e).toLocaleString():"0"}function q(e,a){const t=document.querySelector(e);t&&(t.textContent=a)}let v=null;function Pe(e,a){const t=()=>e&&e.offsetParent!==null&&e.clientWidth>0&&e.clientHeight>=0;if(t())return a();if("IntersectionObserver"in window){const n=new IntersectionObserver(s=>{s.some(d=>d.isIntersecting)&&(n.disconnect(),a())},{root:null,threshold:.01});n.observe(e);return}if("ResizeObserver"in window){const n=new ResizeObserver(()=>{t()&&(n.disconnect(),a())});n.observe(e);return}const o=setInterval(()=>{t()&&(clearInterval(o),a())},100)}function L(){v&&(requestAnimationFrame(()=>{try{v.updateSize()}catch{}}),setTimeout(()=>{try{v.updateSize()}catch{}},150))}function Ie(){if(!window.FullCalendar)return console.error("FullCalendar (global) no está cargado. Asegúrate de tener los <script> CDN en el Blade."),null;v&&v.destroy();const e=["resourceTimeGridDay","resourceTimelineWeek","dayGridMonth"];let a=localStorage.getItem("ultimaVistaCalendario");e.includes(a)||(a="resourceTimeGridDay");const t=localStorage.getItem("fechaCalendario");let o=null;const n=document.getElementById("calendario");if(!n)return console.error("#calendario no encontrado"),null;function s(r){return v?v.getEvents().some(i=>{var b,y;const c=(i.startStr||((b=i.start)==null?void 0:b.toISOString())||"").split("T")[0];return(((y=i.extendedProps)==null?void 0:y.tipo)==="festivo"||typeof i.id=="string"&&i.id.startsWith("festivo-"))&&c===r}):!1}Pe(n,()=>{v=new FullCalendar.Calendar(n,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",navLinks:!0,initialView:a,initialDate:t?new Date(t):void 0,dayMaxEventRows:!1,dayMaxEvents:!1,slotMinTime:"05:00:00",slotMaxTime:"20:00:00",buttonText:{today:"Hoy",resourceTimeGridDay:"Día",resourceTimelineWeek:"Semana",dayGridMonth:"Mes"},progressiveEventRendering:!0,expandRows:!0,height:"auto",events:(r,i,c)=>{var b;const u=r.view&&r.view.type||((b=v==null?void 0:v.view)==null?void 0:b.type)||"resourceTimeGridDay";ie(u,r).then(i).catch(c)},resources:(r,i,c)=>{var b;const u=r.view&&r.view.type||((b=v==null?void 0:v.view)==null?void 0:b.type)||"resourceTimeGridDay";le(u,r).then(i).catch(c)},headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimeGridDay,resourceTimelineWeek,dayGridMonth"},eventOrderStrict:!0,eventOrder:(r,i)=>{var g,f;const c=((g=r.extendedProps)==null?void 0:g.tipo)==="resumen-dia",u=((f=i.extendedProps)==null?void 0:f.tipo)==="resumen-dia";if(c&&!u)return-1;if(!c&&u)return 1;const b=parseInt(String(r.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0,y=parseInt(String(i.extendedProps.cod_obra??"").replace(/\D/g,""),10)||0;return b-y},datesSet:r=>{try{const i=Ae(r);localStorage.setItem("fechaCalendario",i),localStorage.setItem("ultimaVistaCalendario",r.view.type),d(),setTimeout(()=>V(i),0),clearTimeout(o),o=setTimeout(()=>{v.refetchResources(),v.refetchEvents(),L()},0)}catch(i){console.error("Error en datesSet:",i)}},loading:r=>{!r&&v&&v.view.type==="resourceTimeGridDay"&&setTimeout(()=>m(),150)},viewDidMount:r=>{d(),r.view.type==="resourceTimeGridDay"&&setTimeout(()=>m(),100),r.view.type==="dayGridMonth"&&setTimeout(()=>{document.querySelectorAll(".fc-daygrid-event-harness").forEach(i=>{i.querySelector(".evento-resumen-diario")||(i.style.setProperty("width","100%","important"),i.style.setProperty("max-width","100%","important"),i.style.setProperty("position","static","important"),i.style.setProperty("left","unset","important"),i.style.setProperty("right","unset","important"),i.style.setProperty("top","unset","important"),i.style.setProperty("inset","unset","important"),i.style.setProperty("margin","0 0 2px 0","important"))}),document.querySelectorAll(".fc-daygrid-event:not(.evento-resumen-diario)").forEach(i=>{i.style.setProperty("width","100%","important"),i.style.setProperty("max-width","100%","important"),i.style.setProperty("margin","0","important"),i.style.setProperty("position","static","important"),i.style.setProperty("left","unset","important"),i.style.setProperty("right","unset","important"),i.style.setProperty("inset","unset","important")})},50)},eventContent:r=>{var y;const i=r.event.backgroundColor||"#9CA3AF",c=r.event.extendedProps||{},u=(y=v==null?void 0:v.view)==null?void 0:y.type;if(c.tipo==="resumen-dia"){const g=Number(c.pesoTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),f=Number(c.longitudTotal||0).toLocaleString(void 0,{minimumFractionDigits:0,maximumFractionDigits:0}),p=c.diametroMedio?Number(c.diametroMedio).toFixed(1):null;if(u==="resourceTimelineWeek")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight w-full">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${g} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${f} m</div>
                                ${p?`<div class="text-yellow-800">⌀ ${p} mm</div>`:""}
                            </div>
                        `};if(u==="dayGridMonth")return{html:`
                            <div class="bg-yellow-100 border border-yellow-400 rounded px-2 py-1 text-[10px] leading-tight">
                                <div class="font-semibold text-yellow-900 mb-0.5">📦 ${g} kg</div>
                                <div class="text-yellow-800 mb-0.5">📏 ${f} m</div>
                                ${p?`<div class="text-yellow-800">⌀ ${p} mm</div>`:""}
                            </div>
                        `}}let b=`
        <div style="background-color:${i}; color:#000;" class="rounded p-3 text-sm leading-snug font-medium space-y-1">
            <div class="text-sm text-black font-semibold mb-1">${r.event.title}</div>
    `;if(c.tipo==="planilla"){const g=c.pesoTotal!=null?`📦 ${Number(c.pesoTotal).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2})} kg`:null,f=c.longitudTotal!=null?`📏 ${Number(c.longitudTotal).toLocaleString()} m`:null,p=c.diametroMedio!=null?`⌀ ${Number(c.diametroMedio).toFixed(2)} mm`:null,h=[g,f,p].filter(Boolean);h.length>0&&(b+=`<div class="text-sm text-black font-semibold">${h.join(" | ")}</div>`),c.tieneSalidas&&Array.isArray(c.salidas_codigos)&&c.salidas_codigos.length>0&&(b+=`
            <div class="mt-2">
                <span class="text-black bg-yellow-400 rounded px-2 py-1 inline-block text-xs font-semibold">
                    Salidas: ${c.salidas_codigos.join(", ")}
                </span>
            </div>`)}return b+="</div>",{html:b}},eventDidMount:function(r){var b,y,g,f;const i=r.event.extendedProps||{};if(i.tipo==="resumen-dia"){r.el.classList.add("evento-resumen-diario"),r.el.style.cursor="default";return}if(r.view.type==="dayGridMonth"){const p=r.el.closest(".fc-daygrid-event-harness");p&&(p.style.setProperty("width","100%","important"),p.style.setProperty("max-width","100%","important"),p.style.setProperty("position","static","important"),p.style.setProperty("left","unset","important"),p.style.setProperty("right","unset","important"),p.style.setProperty("top","unset","important"),p.style.setProperty("inset","unset","important"),p.style.setProperty("margin","0 0 2px 0","important")),r.el.style.setProperty("width","100%","important"),r.el.style.setProperty("max-width","100%","important"),r.el.style.setProperty("margin","0","important"),r.el.style.setProperty("position","static","important"),r.el.style.setProperty("left","unset","important"),r.el.style.setProperty("right","unset","important"),r.el.style.setProperty("inset","unset","important")}const c=(((b=document.getElementById("filtro-obra"))==null?void 0:b.value)||"").trim().toLowerCase(),u=(((y=document.getElementById("filtro-nombre-obra"))==null?void 0:y.value)||"").trim().toLowerCase();if(c||u){let p=!1;if(i.tipo==="salida"&&i.obras&&Array.isArray(i.obras))p=i.obras.some(h=>{const E=(h.codigo||"").toString().toLowerCase(),A=(h.nombre||"").toString().toLowerCase();return c&&E.includes(c)||u&&A.includes(u)});else{const h=(((g=r.event.extendedProps)==null?void 0:g.cod_obra)||"").toString().toLowerCase(),E=(((f=r.event.extendedProps)==null?void 0:f.nombre_obra)||r.event.title||"").toString().toLowerCase();p=c&&h.includes(c)||u&&E.includes(u)}p&&r.el.classList.add("evento-filtrado")}typeof G=="function"&&G(r),typeof W=="function"&&W(r,v)},eventAllow:(r,i)=>{var u;const c=(u=i.extendedProps)==null?void 0:u.tipo;return!(c==="resumen-dia"||c==="festivo")},eventDrop:r=>{var g,f,p,h;const i=r.event.extendedProps||{},c=r.event.id,b={fecha:(g=r.event.start)==null?void 0:g.toISOString(),tipo:i.tipo,planillas_ids:i.planillas_ids||[]},y=(((p=(f=window.AppSalidas)==null?void 0:f.routes)==null?void 0:p.updateItem)||"").replace("__ID__",c);fetch(y,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":(h=window.AppSalidas)==null?void 0:h.csrf},body:JSON.stringify(b)}).then(E=>{if(!E.ok)throw new Error("No se pudo actualizar la fecha.");return E.json()}).then(()=>{v.refetchEvents(),v.refetchResources();const A=r.event.start.toISOString().split("T")[0];V(A),L()}).catch(E=>{console.error("Error:",E),r.revert()})},dateClick:r=>{const i=v.view.type;if(s(r.dateStr)){Swal.fire({icon:"info",title:"📅 Día festivo",text:"Los festivos se editan en la planificación de Trabajadores.",confirmButtonText:"Entendido"});return}(i==="resourceTimelineWeek"||i==="dayGridMonth")&&Swal.fire({title:"📅 Cambiar a vista diaria",text:`¿Quieres ver el día ${r.dateStr}?`,icon:"question",showCancelButton:!0,confirmButtonText:"Sí, ver día",cancelButtonText:"No"}).then(c=>{c.isConfirmed&&(v.changeView("resourceTimeGridDay",r.dateStr),L())})},eventMinHeight:30,firstDay:1,views:{resourceTimelineWeek:{slotDuration:{days:1},slotLabelFormat:[{weekday:"short",day:"numeric",month:"short"}]},resourceTimeGridDay:{slotDuration:"01:00:00",slotLabelFormat:{hour:"2-digit",minute:"2-digit",hour12:!1},slotLabelInterval:"01:00:00",allDaySlot:!1}},slotLabelContent:r=>{var c;const i=(c=v==null?void 0:v.view)==null?void 0:c.type;return i==="resourceTimeGridDay"?{html:`<div class="text-sm font-medium text-gray-700 py-1">${r.date.toLocaleTimeString("es-ES",{hour:"2-digit",minute:"2-digit",hour12:!1})}</div>`}:i==="resourceTimelineWeek"?{html:`<div class="text-center font-bold text-sm py-2">${new Date(r.date).toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short"})}</div>`}:null},dayHeaderContent:r=>({html:`<div class="text-center font-bold text-base py-2">${new Date(r.date).toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short"})}</div>`}),editable:!0,resourceAreaColumns:[{field:"cod_obra",headerContent:"Código"},{field:"title",headerContent:"Obra"},{field:"cliente",headerContent:"Cliente"}],resourceAreaHeaderContent:"Obras",resourceOrder:"orderIndex",resourceLabelContent:r=>({html:`<div class="text-xs font-semibold">
                        <div class="text-blue-600">${r.resource.extendedProps.cod_obra||""}</div>
                        <div class="text-gray-700 truncate">${r.resource.title||""}</div>
                        <div class="text-gray-500 text-[10px] truncate">${r.resource.extendedProps.cliente||""}</div>
                    </div>`}),windowResize:()=>L()}),v.render(),L()}),window.addEventListener("shown.bs.tab",L),window.addEventListener("shown.bs.collapse",L),window.addEventListener("shown.bs.modal",L);function d(){document.querySelectorAll(".resumen-diario-custom").forEach(i=>i.remove())}function m(){if(!v||v.view.type!=="resourceTimeGridDay"){d();return}d();const r=v.getDate(),i=r.getFullYear(),c=String(r.getMonth()+1).padStart(2,"0"),u=String(r.getDate()).padStart(2,"0"),b=`${i}-${c}-${u}`,y=v.getEvents().find(g=>{var f,p;return((f=g.extendedProps)==null?void 0:f.tipo)==="resumen-dia"&&((p=g.extendedProps)==null?void 0:p.fecha)===b});if(y&&y.extendedProps){const g=Number(y.extendedProps.pesoTotal||0).toLocaleString(),f=Number(y.extendedProps.longitudTotal||0).toLocaleString(),p=y.extendedProps.diametroMedio?Number(y.extendedProps.diametroMedio).toFixed(2):null,h=document.createElement("div");h.className="resumen-diario-custom",h.innerHTML=`
                <div class="bg-yellow-100 border-2 border-yellow-400 rounded-lg px-6 py-4 mb-4 shadow-sm">
                    <div class="flex items-center justify-center gap-8 text-base font-semibold">
                        <div class="text-yellow-900">📦 Peso: ${g} kg</div>
                        <div class="text-yellow-800">📏 Longitud: ${f} m</div>
                        ${p?`<div class="text-yellow-800">⌀ Diámetro: ${p} mm</div>`:""}
                    </div>
                </div>
            `,n&&n.parentNode&&n.parentNode.insertBefore(h,n)}}return window.mostrarResumenDiario=m,window.limpiarResumenesCustom=d,v}function Ae(e){if(e.view.type==="dayGridMonth"){const a=new Date(e.start);return a.setDate(a.getDate()+15),a.toISOString().split("T")[0]}if(e.view.type==="resourceTimeGridWeek"||e.view.type==="resourceTimelineWeek"){const a=new Date(e.start),t=Math.floor((e.end-e.start)/(1e3*60*60*24)/2);return a.setDate(a.getDate()+t),a.toISOString().split("T")[0]}return e.startStr.split("T")[0]}function Fe(e,a={}){const{selector:t=null,once:o=!1}=a;let n=!1;const s=()=>{t&&!document.querySelector(t)||o&&n||(n=!0,e())};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",s):s(),document.addEventListener("livewire:navigated",s)}function ze(e){document.addEventListener("livewire:navigating",e)}function Me(e){let t=new Date(e).toLocaleDateString("es-ES",{month:"long",year:"numeric"});return`(${t.charAt(0).toUpperCase()+t.slice(1)})`}function _e(e){const a=new Date(e),t=a.getDay(),o=t===0?-6:1-t,n=new Date(a);n.setDate(a.getDate()+o);const s=new Date(n);s.setDate(n.getDate()+6);const l=new Intl.DateTimeFormat("es-ES",{day:"2-digit",month:"short"}),d=new Intl.DateTimeFormat("es-ES",{year:"numeric"});return`(${l.format(n)} – ${l.format(s)} ${d.format(s)})`}function Ne(e){const a=document.querySelector("#resumen-semanal-fecha"),t=document.querySelector("#resumen-mensual-fecha");a&&(a.textContent=_e(e)),t&&(t.textContent=Me(e));const o=`${window.AppSalidas.routes.totales}?fecha=${encodeURIComponent(e)}`;fetch(o).then(n=>n.json()).then(n=>{const s=n.semana||{},l=n.mes||{};document.querySelector("#resumen-semanal-peso").textContent=`📦 ${Number(s.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-semanal-longitud").textContent=`📏 ${Number(s.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-semanal-diametro").textContent=s.diametro!=null?`⌀ ${Number(s.diametro).toFixed(2)} mm`:"",document.querySelector("#resumen-mensual-peso").textContent=`📦 ${Number(l.peso||0).toLocaleString()} kg`,document.querySelector("#resumen-mensual-longitud").textContent=`📏 ${Number(l.longitud||0).toLocaleString()} m`,document.querySelector("#resumen-mensual-diametro").textContent=l.diametro!=null?`⌀ ${Number(l.diametro).toFixed(2)} mm`:""}).catch(n=>console.error("❌ Totales:",n))}let C;function Be(){var y,g;if(window.calendar)try{window.calendar.destroy()}catch(f){console.warn("Error al destruir calendario anterior:",f)}const e=Ie();C=e,window.calendar=e,e.refetchResources(),e.refetchEvents(),(y=document.getElementById("ver-con-salidas"))==null||y.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()}),(g=document.getElementById("ver-todas"))==null||g.addEventListener("click",()=>{e.refetchResources(),e.refetchEvents()});const t=(localStorage.getItem("fechaCalendario")||new Date().toISOString()).split("T")[0];Ne(t);const o=localStorage.getItem("soloSalidas")==="true",n=localStorage.getItem("soloPlanillas")==="true",s=document.getElementById("solo-salidas"),l=document.getElementById("solo-planillas");s&&(s.checked=o),l&&(l.checked=n);const d=document.getElementById("filtro-obra"),m=document.getElementById("filtro-nombre-obra"),r=document.getElementById("btn-reset-filtros"),i=document.getElementById("btn-limpiar-filtros");r==null||r.addEventListener("click",()=>{d&&(d.value=""),m&&(m.value=""),s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),l&&(l.checked=!1,localStorage.setItem("soloPlanillas","false")),b(),C.refetchEvents()});const u=((f,p=150)=>{let h;return(...E)=>{clearTimeout(h),h=setTimeout(()=>f(...E),p)}})(()=>{C.refetchEvents()},120);d==null||d.addEventListener("input",u),m==null||m.addEventListener("input",u);function b(){const f=s==null?void 0:s.closest(".checkbox-container"),p=l==null?void 0:l.closest(".checkbox-container");f==null||f.classList.remove("active-salidas"),p==null||p.classList.remove("active-planillas"),s!=null&&s.checked&&(f==null||f.classList.add("active-salidas")),l!=null&&l.checked&&(p==null||p.classList.add("active-planillas"))}s==null||s.addEventListener("change",f=>{f.target.checked&&l&&(l.checked=!1,localStorage.setItem("soloPlanillas","false")),localStorage.setItem("soloSalidas",f.target.checked.toString()),b(),C.refetchEvents()}),l==null||l.addEventListener("change",f=>{f.target.checked&&s&&(s.checked=!1,localStorage.setItem("soloSalidas","false")),localStorage.setItem("soloPlanillas",f.target.checked.toString()),b(),C.refetchEvents()}),b(),i==null||i.addEventListener("click",()=>{d&&(d.value=""),m&&(m.value=""),C.refetchEvents()})}let T=null,I=null,D="days",x=-1,S=[];function Oe(){I&&I();const e=window.calendar;if(!e)return;T=e.getDate(),D="days",x=-1,z();function a(t){const o=t.target.tagName.toLowerCase();if(o==="input"||o==="textarea"||t.target.isContentEditable||document.querySelector(".swal2-container")||!window.calendar||!T)return;let s=!1;if(t.key==="Tab"&&!t.ctrlKey&&!t.metaKey){t.preventDefault(),Re();return}if(t.key==="Escape"&&D==="events"){t.preventDefault(),D="days",x=-1,O(),z(),M();return}D==="events"?s=je(t):s=Ge(t),s&&(t.preventDefault(),t.stopPropagation())}document.addEventListener("keydown",a,!0),e.on("eventsSet",()=>{D==="events"&&(ee(),P())}),I=()=>{document.removeEventListener("keydown",a,!0),ae(),O()}}function Re(){D==="days"?(D="events",ee(),S.length>0?(x=0,P()):(D="days",Ke())):(D="days",x=-1,O(),z()),M()}function ee(){const e=window.calendar;if(!e){S=[];return}S=e.getEvents().filter(a=>{var o;const t=(o=a.extendedProps)==null?void 0:o.tipo;return t!=="resumen-dia"&&t!=="festivo"}).sort((a,t)=>{const o=a.start||new Date(0),n=t.start||new Date(0);return o<n?-1:o>n?1:(a.title||"").localeCompare(t.title||"")})}function je(e){if(S.length===0)return!1;let a=!1;switch(e.key){case"ArrowDown":case"ArrowRight":x=(x+1)%S.length,P(),a=!0;break;case"ArrowUp":case"ArrowLeft":x=x<=0?S.length-1:x-1,P(),a=!0;break;case"Home":x=0,P(),a=!0;break;case"End":x=S.length-1,P(),a=!0;break;case"Enter":He(),a=!0;break;case"e":case"E":We(),a=!0;break;case"i":case"I":Ve(),a=!0;break}return a}function Ge(e){const a=window.calendar,t=new Date(T);let o=!1;switch(e.key){case"ArrowLeft":t.setDate(t.getDate()-1),o=!0;break;case"ArrowRight":t.setDate(t.getDate()+1),o=!0;break;case"ArrowUp":t.setDate(t.getDate()-7),o=!0;break;case"ArrowDown":t.setDate(t.getDate()+7),o=!0;break;case"Home":t.setDate(1),o=!0;break;case"End":t.setMonth(t.getMonth()+1),t.setDate(0),o=!0;break;case"PageUp":t.setMonth(t.getMonth()-1),o=!0;break;case"PageDown":t.setMonth(t.getMonth()+1),o=!0;break;case"Enter":const n=te(T),s=a.view.type;s==="dayGridMonth"||s==="resourceTimelineWeek"?a.changeView("resourceTimeGridDay",n):a.gotoDate(T),o=!0;break;case"t":case"T":!e.ctrlKey&&!e.metaKey&&(T=new Date,a.today(),z(),o=!0);break}if(o&&e.key!=="Enter"&&e.key!=="t"&&e.key!=="T"){T=t;const n=a.view;(t<n.currentStart||t>=n.currentEnd)&&a.gotoDate(t),z()}return o}function P(){var t;if(O(),x<0||x>=S.length)return;const e=S[x];if(!e)return;const a=document.querySelector(`[data-event-id="${e.id}"]`)||document.querySelector(`.fc-event[data-event="${e.id}"]`);if(a)a.classList.add("keyboard-focused-event"),a.scrollIntoView({behavior:"smooth",block:"nearest"});else{const o=document.querySelectorAll(".fc-event");for(const n of o)if(n.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){n.classList.add("keyboard-focused-event"),n.scrollIntoView({behavior:"smooth",block:"nearest"});break}}e.start&&(T=new Date(e.start)),M()}function O(){document.querySelectorAll(".keyboard-focused-event").forEach(e=>{e.classList.remove("keyboard-focused-event")})}function He(){if(x<0||x>=S.length)return;const e=S[x];if(!e)return;const a=e.extendedProps||{},t=window.calendar;if(a.tipo==="salida"){const o=a.salida_id||e.id;U(o,t)}else if(a.tipo==="planilla"){const o=a.planillas_ids||[];o.length>0&&(window.location.href=`/salidas-ferralla/gestionar-salidas?planillas=${o.join(",")}`)}}function We(){var t;if(x<0||x>=S.length)return;const e=S[x];if(!e)return;const a=document.querySelectorAll(".fc-event");for(const o of a)if(o.classList.contains("keyboard-focused-event")||o.textContent.includes((t=e.title)==null?void 0:t.substring(0,20))){const n=o.getBoundingClientRect(),s=new MouseEvent("contextmenu",{bubbles:!0,cancelable:!0,clientX:n.left+n.width/2,clientY:n.top+n.height/2});o.dispatchEvent(s);break}}function Ve(){if(x<0||x>=S.length)return;const e=S[x];if(!e)return;const a=e.extendedProps||{};let t=`<strong>${e.title}</strong><br><br>`;a.tipo==="salida"?(t+="<b>Tipo:</b> Salida<br>",a.obras&&a.obras.length>0&&(t+=`<b>Obras:</b> ${a.obras.map(o=>o.nombre).join(", ")}<br>`)):a.tipo==="planilla"&&(t+="<b>Tipo:</b> Planilla<br>",a.cod_obra&&(t+=`<b>Código:</b> ${a.cod_obra}<br>`),a.pesoTotal&&(t+=`<b>Peso:</b> ${Number(a.pesoTotal).toLocaleString()} kg<br>`),a.longitudTotal&&(t+=`<b>Longitud:</b> ${Number(a.longitudTotal).toLocaleString()} m<br>`)),e.start&&(t+=`<b>Fecha:</b> ${e.start.toLocaleDateString("es-ES",{weekday:"long",day:"numeric",month:"long",year:"numeric"})}<br>`),Swal.fire({title:"Información del evento",html:t,icon:"info",confirmButtonText:"Cerrar"})}function Ke(){const e=document.getElementById("keyboard-nav-indicator");if(e){const a=document.getElementById("keyboard-nav-date");a&&(a.innerHTML='<span class="text-yellow-400">No hay eventos visibles</span>'),clearTimeout(e._hideTimeout),e.style.display="flex",e._hideTimeout=setTimeout(()=>{M()},2e3)}}function te(e){const a=e.getFullYear(),t=String(e.getMonth()+1).padStart(2,"0"),o=String(e.getDate()).padStart(2,"0");return`${a}-${t}-${o}`}function z(){if(ae(),!T)return;const e=te(T),a=window.calendar;if(!a)return;const t=a.view.type;let o=null;t==="dayGridMonth"?o=document.querySelector(`.fc-daygrid-day[data-date="${e}"]`):t==="resourceTimelineWeek"?(document.querySelectorAll(".fc-timeline-slot[data-date]").forEach(s=>{s.dataset.date&&s.dataset.date.startsWith(e)&&(o=s)}),o||(o=document.querySelector(`.fc-timeline-slot-lane[data-date^="${e}"]`))):t==="resourceTimeGridDay"&&(o=document.querySelector(".fc-col-header-cell")),o&&(o.classList.add("keyboard-focused-day"),o.scrollIntoView({behavior:"smooth",block:"nearest",inline:"nearest"})),M()}function ae(){document.querySelectorAll(".keyboard-focused-day").forEach(e=>{e.classList.remove("keyboard-focused-day")})}function M(){let e=document.getElementById("keyboard-nav-indicator");if(e||(e=document.createElement("div"),e.id="keyboard-nav-indicator",e.className="fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-2 rounded-lg shadow-lg z-50 text-sm",document.body.appendChild(e)),D==="events"){const a=S[x],t=(a==null?void 0:a.title)||"Sin evento",o=`${x+1}/${S.length}`;e.innerHTML=`
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
        `}else{const a=T?T.toLocaleDateString("es-ES",{weekday:"short",day:"numeric",month:"short",year:"numeric"}):"";e.innerHTML=`
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
        `}clearTimeout(e._hideTimeout),e.style.display="block",e._hideTimeout=setTimeout(()=>{e.style.display="none"},4e3)}function Ue(){if(document.getElementById("keyboard-nav-styles"))return;const e=document.createElement("style");e.id="keyboard-nav-styles",e.textContent=`
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
    `,document.head.appendChild(e)}Fe(()=>{Be(),Ue(),setTimeout(()=>{Oe()},500)},{selector:'#calendario[data-calendar-type="salidas"]'});ze(()=>{if(I&&(I(),I=null),window.calendar)try{window.calendar.destroy(),window.calendar=null}catch(a){console.warn("Error al limpiar calendario de salidas:",a)}const e=document.getElementById("keyboard-nav-indicator");e&&e.remove()});
