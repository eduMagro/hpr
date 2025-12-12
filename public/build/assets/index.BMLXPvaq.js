const L=()=>window.AppPlanif,z=()=>L().csrf,k=()=>L().routes,X=()=>{var n;const e=L()||{};let r=e.turnos;return Array.isArray(r)||(r=((n=e.turnosConfig)==null?void 0:n.turnos)||[]),{maquinas:e.maquinas||[],eventos:e.eventos||[],cargaTrabajo:e.cargaTrabajo||{},turnos:r}};let D=null;function E(){D&&(D.remove(),D=null,document.removeEventListener("click",E),document.removeEventListener("scroll",E,!0),window.removeEventListener("resize",E))}function O(e,r,n){E();const a=document.createElement("div");a.className="fc-contextmenu",Object.assign(a.style,{position:"fixed",zIndex:9999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",visibility:"hidden"}),a.innerHTML=n,document.body.appendChild(a);const i=a.getBoundingClientRect(),l=window.innerHeight,u=window.innerWidth;let c=r;r+i.height>l&&(c=r-i.height,c<0&&(c=Math.max(0,l-i.height)));let p=e;return e+i.width>u&&(p=e-i.width,p<0&&(p=Math.max(0,u-i.width))),Object.assign(a.style,{top:c+"px",left:p+"px",visibility:"visible"}),D=a,setTimeout(()=>{document.addEventListener("click",E),document.addEventListener("scroll",E,!0),window.addEventListener("resize",E)},0),a}function W(e,r,{headerHtml:n="",items:a=[]}){const i=`
    <div class="ctx-menu-container">
      ${n?`<div class="ctx-menu-header">${n}</div>`:""}
      ${a.map((u,c)=>u.type==="separator"?'<div class="ctx-menu-separator" style="height:1px; background:#e5e7eb; margin:4px 0;"></div>':`
        <button class="ctx-menu-item${u.danger?" ctx-menu-danger":""}${u.disabled?" ctx-menu-disabled":""}" data-idx="${c}" ${u.disabled?"disabled":""}>
          ${u.icon?`<span class="ctx-menu-icon">${u.icon}</span>`:""}
          <span class="ctx-menu-label">${u.label}</span>
        </button>
      `).join("")}
    </div>
  `,l=O(e,r,i);return l.querySelectorAll(".ctx-menu-item").forEach(u=>{const c=parseInt(u.dataset.idx,10),p=a[c];u.addEventListener("click",async v=>{if(v.preventDefault(),v.stopPropagation(),console.log("[baseMenu] Click en item:",p.label,"disabled:",p.disabled),p.disabled){console.log("[baseMenu] Item deshabilitado, ignorando");return}const d=p.onClick;console.log("[baseMenu] Ejecutando acción..."),E();try{await(d==null?void 0:d()),console.log("[baseMenu] Acción completada")}catch(s){console.error("[baseMenu] Error en acción:",s)}})}),l}async function A(e,r={}){const n={headers:{Accept:"application/json","X-CSRF-TOKEN":z()},...r};n.body&&typeof n.body!="string"&&(n.headers["Content-Type"]="application/json",n.body=JSON.stringify(n.body));const a=await fetch(e,n);let i=null;try{i=await a.json()}catch{}if(!a.ok)throw new Error((i==null?void 0:i.message)||`HTTP ${a.status}`);return i}async function K(e){const r=await Swal.fire({title:"Nuevo festivo",input:"text",inputLabel:"Título del festivo",inputValue:"Festivo",showCancelButton:!0,confirmButtonText:"Crear",cancelButtonText:"Cancelar",inputValidator:a=>!a||!a.trim()?"Pon un título":void 0});return r.isConfirmed?(await A(k().festivo.store,{method:"POST",body:{fecha:e,titulo:r.value.trim()}})).festivo:null}function Y(e){var a,i,l;if(!e)return null;const[r]=e.split(":").map(Number),n=((i=(a=window.AppPlanif)==null?void 0:a.turnosConfig)==null?void 0:i.turnos)||((l=window.AppPlanif)==null?void 0:l.turnos)||[];for(const u of n){if(!u.hora_inicio||!u.hora_fin)continue;const[c]=u.hora_inicio.split(":").map(Number),[p]=u.hora_fin.split(":").map(Number),v=p<c;let d=!1;if(v?d=r>=c||r<p:d=r>=c&&r<p,d)return u.nombre}return r>=0&&r<8?"noche":r>=8&&r<16?"mañana":"tarde"}const H=[{bg:"bg-emerald-50",border:"border-emerald-200",text:"text-emerald-700",badge:"bg-emerald-100 text-emerald-800"},{bg:"bg-blue-50",border:"border-blue-200",text:"text-blue-700",badge:"bg-blue-100 text-blue-800"},{bg:"bg-amber-50",border:"border-amber-200",text:"text-amber-700",badge:"bg-amber-100 text-amber-800"},{bg:"bg-purple-50",border:"border-purple-200",text:"text-purple-700",badge:"bg-purple-100 text-purple-800"},{bg:"bg-rose-50",border:"border-rose-200",text:"text-rose-700",badge:"bg-rose-100 text-rose-800"},{bg:"bg-cyan-50",border:"border-cyan-200",text:"text-cyan-700",badge:"bg-cyan-100 text-cyan-800"}];function J(e,r,n,a){const i=H[a%H.length],l=`swal-trabajador-${n}-${r}`,u=e.operarios.length;if(u===0)return"";const c=e.operarios.map(p=>`<option value="${p.id}">${p.name} ${p.primer_apellido||""} ${p.segundo_apellido||""}</option>`).join("");return`
        <div class="mb-3 ${i.bg} ${i.border} border rounded-lg p-2">
            <label class="block text-xs font-semibold ${i.text} mb-1">
                ${e.empresa_nombre}
                <span class="${i.badge} text-xs px-1.5 py-0.5 rounded-full ml-1">${u}</span>
            </label>
            <select id="${l}" data-tipo="${n}" class="swal2-input w-full select-operario text-sm py-1" style="margin: 0;">
                <option value="" selected>Seleccionar...</option>
                ${c}
            </select>
        </div>
    `}function M(e,r,n,a){if(!e||e.length===0)return`
            <div class="mb-4">
                <p class="text-sm font-semibold text-gray-500 mb-2">${n}</p>
                <p class="text-xs text-gray-400 italic">No hay operarios</p>
            </div>
        `;const i=e.reduce((u,c)=>u+c.operarios.length,0),l=e.map((u,c)=>J(u,c,r,c)).join("");return`
        <div class="mb-4">
            <p class="text-sm font-semibold text-gray-700 mb-2">
                ${n} <span class="${a}">(${i})</span>
            </p>
            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                ${l}
            </div>
        </div>
    `}async function G(e,r,n,a=null){const i=Y(a);console.log("[generarTurnos] horaISO:",a,"turnoDetectado:",i);let l={sin_turno_por_empresa:[],de_maquina_por_empresa:[],todos_por_empresa:[],todos:[]};try{const s=await fetch(`/api/usuarios/operarios-agrupados?fecha=${e}&maquina_id=${r}`,{headers:{"X-CSRF-TOKEN":window.AppPlanif.csrf,Accept:"application/json"}});s.ok?(l=await s.json(),console.log("[generarTurnos] Datos recibidos:",l)):console.error("Error al obtener operarios:",s.status)}catch(s){console.error("Error al obtener operarios:",s)}const u=i?`<span class="text-green-600 font-semibold">${i.charAt(0).toUpperCase()+i.slice(1)}</span>`:'<span class="text-gray-400">No detectado</span>',c=M(l.sin_turno_por_empresa,"sin_turno","Sin turno asignado este día","text-green-600"),p=M(l.de_maquina_por_empresa,"de_maquina",`Asignados a ${n}`,"text-blue-600"),v=M(l.todos_por_empresa,"todos","Todos los operarios","text-gray-600"),{value:d}=await Swal.fire({title:"Generar Turnos",html:`
            <div class="text-left space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Fecha inicio:</strong> ${e}<br>
                        <strong>Máquina:</strong> ${n}<br>
                        <strong>Turno detectado:</strong> ${u}
                    </p>
                </div>

                <!-- Tabs para las secciones -->
                <div class="border-b border-gray-200 mb-3">
                    <nav class="flex space-x-1" aria-label="Tabs">
                        <button type="button" data-tab="sin_turno" class="tab-btn px-2 py-2 text-xs font-medium rounded-t-lg border-b-2 border-green-500 text-green-600 bg-green-50">
                            Sin turno hoy
                        </button>
                        <button type="button" data-tab="de_maquina" class="tab-btn px-2 py-2 text-xs font-medium rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            De ${n}
                        </button>
                        <button type="button" data-tab="todos" class="tab-btn px-2 py-2 text-xs font-medium rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            Todos
                        </button>
                    </nav>
                </div>

                <!-- Contenido de tabs -->
                <div id="tab-content-sin_turno" class="tab-content">
                    ${c}
                </div>
                <div id="tab-content-de_maquina" class="tab-content hidden">
                    ${p}
                </div>
                <div id="tab-content-todos" class="tab-content hidden">
                    ${v}
                </div>

                <input type="hidden" id="swal-trabajador" value="" />
                <input type="hidden" id="swal-turno-detectado" value="${i||""}" />

                <!-- Trabajador seleccionado -->
                <div id="trabajador-seleccionado" class="hidden bg-green-50 border border-green-200 rounded-lg p-2 mb-3">
                    <p class="text-sm text-green-800">
                        <strong>Seleccionado:</strong> <span id="nombre-seleccionado"></span>
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Alcance de generación <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-alcance" class="swal2-input w-full">
                        <option value="un_dia">Solo este día (${e})</option>
                        <option value="dos_semanas">Hasta el viernes de la semana siguiente</option>
                        <option value="resto_año">Desde ${e} hasta fin de año</option>
                    </select>
                </div>

                <div class="mb-4" id="turno-inicio-container" style="display: ${i?"none":"block"};">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Turno inicial (para diurnos) <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-turno-inicio" class="swal2-input w-full">
                        <option value="mañana" ${i==="mañana"?"selected":""}>Mañana</option>
                        <option value="tarde" ${i==="tarde"?"selected":""}>Tarde</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Los turnos alternarán cada viernes</p>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-xs text-yellow-800">
                        <strong>Nota:</strong> Se saltarán automáticamente sábados, domingos, festivos y días con vacaciones.
                    </p>
                </div>
            </div>
        `,focusConfirm:!1,showCancelButton:!0,confirmButtonText:"Generar Turnos",cancelButtonText:"Cancelar",confirmButtonColor:"#3b82f6",cancelButtonColor:"#6b7280",width:"650px",didOpen:()=>{const s=document.getElementById("swal-trabajador"),x=document.getElementById("turno-inicio-container"),o=document.getElementById("trabajador-seleccionado"),t=document.getElementById("nombre-seleccionado"),m=l.todos||[];function b(g,h){s.value=g,document.querySelectorAll(".select-operario").forEach(y=>{y!==h&&(y.value="")});const f=m.find(y=>y.id===parseInt(g));f&&(t.textContent=`${f.name} ${f.primer_apellido||""} ${f.segundo_apellido||""} (${f.empresa_nombre||"Sin empresa"})`,o.classList.remove("hidden"),f.turno==="diurno"?x.style.display="block":x.style.display="none")}document.querySelectorAll(".select-operario").forEach(g=>{g.addEventListener("change",h=>{h.target.value&&b(h.target.value,h.target)})}),document.querySelectorAll(".tab-btn").forEach(g=>{g.addEventListener("click",h=>{const f=h.target.dataset.tab;document.querySelectorAll(".tab-btn").forEach(y=>{y.classList.remove("border-green-500","text-green-600","bg-green-50"),y.classList.add("border-transparent","text-gray-500")}),h.target.classList.remove("border-transparent","text-gray-500"),h.target.classList.add("border-green-500","text-green-600","bg-green-50"),document.querySelectorAll(".tab-content").forEach(y=>{y.classList.add("hidden")}),document.getElementById(`tab-content-${f}`).classList.remove("hidden")})})},preConfirm:()=>{const s=document.getElementById("swal-trabajador").value,x=document.getElementById("swal-alcance").value,o=document.getElementById("swal-turno-inicio").value,t=document.getElementById("swal-turno-detectado").value;return s?{trabajador_id:s,alcance:x,turno_inicio:o,turno_detectado:t||null}:(Swal.showValidationMessage("Debes seleccionar un trabajador"),!1)}});if(!d)return null;try{const x=await fetch("/profile/generar-turnos-calendario",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":window.AppPlanif.csrf,Accept:"application/json"},body:JSON.stringify({user_id:d.trabajador_id,maquina_id:r,fecha_inicio:e,alcance:d.alcance,turno_inicio:d.turno_inicio,turno_detectado:d.turno_detectado})}),o=await x.json();if(console.log("[generarTurnos] Respuesta backend:",o),o.eventos&&o.eventos.length>0&&console.log("[generarTurnos] Primer evento:",o.eventos[0]),!x.ok)throw new Error(o.message||`Error HTTP ${x.status}`);return{...o,eventos:o.eventos||[]}}catch(s){return console.error("Error al generar turnos:",s),await Swal.fire({icon:"error",title:"Error",text:s.message||"No se pudieron generar los turnos",confirmButtonText:"Aceptar"}),null}}async function U({fechaISO:e,maquinaId:r,maquinaNombre:n,calendar:a}){const i=r&&r!=="null",{value:l}=await Swal.fire({title:"Propagar Asignaciones",html:`
            <div class="text-left space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Fecha origen:</strong> ${e}<br>
                        ${i?`<strong>Máquina:</strong> ${n}`:'<strong>Máquina:</strong> <span class="text-gray-500">Ninguna seleccionada</span>'}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        ¿Qué quieres propagar?
                    </label>

                    <div class="space-y-2">
                        ${i?`
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="tipo" value="maquina" class="mt-1 mr-3" checked>
                            <div>
                                <span class="font-medium text-gray-900">Solo ${n}</span>
                                <p class="text-xs text-gray-500 mt-1">
                                    Propaga únicamente las asignaciones de esta máquina al resto de días.
                                </p>
                            </div>
                        </label>
                        `:""}

                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="tipo" value="todas" class="mt-1 mr-3" ${i?"":"checked"}>
                            <div>
                                <span class="font-medium text-gray-900">TODAS las máquinas</span>
                                <p class="text-xs text-gray-500 mt-1">
                                    Propaga las asignaciones de todas las máquinas del día ${e} a los días siguientes.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        ¿Hasta cuándo propagar?
                    </label>

                    <div class="space-y-2">
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="alcance" value="semana_actual" class="mt-1 mr-3" checked>
                            <div>
                                <span class="font-medium text-gray-900">Resto de esta semana</span>
                                <p class="text-xs text-gray-500 mt-1">
                                    Desde ${e} hasta el viernes de esta semana.
                                </p>
                            </div>
                        </label>

                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="alcance" value="dos_semanas" class="mt-1 mr-3">
                            <div>
                                <span class="font-medium text-gray-900">Esta semana + la siguiente</span>
                                <p class="text-xs text-gray-500 mt-1">
                                    Desde ${e} hasta el viernes de la próxima semana (2 semanas en total).
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <p class="text-xs text-amber-800">
                        <strong>Importante:</strong><br>
                        - Se saltarán automáticamente sábados, domingos, festivos y días con vacaciones.<br>
                        - Si un trabajador ya tiene asignación en un día destino, se <strong>actualizará</strong> con los datos del día origen.
                    </p>
                </div>
            </div>
        `,focusConfirm:!1,showCancelButton:!0,confirmButtonText:"Propagar",cancelButtonText:"Cancelar",confirmButtonColor:"#10b981",width:"550px",preConfirm:()=>{var v,d;const c=(v=document.querySelector('input[name="tipo"]:checked'))==null?void 0:v.value,p=(d=document.querySelector('input[name="alcance"]:checked'))==null?void 0:d.value;return!c||!p?(Swal.showValidationMessage("Selecciona todas las opciones"),!1):{tipo:c,alcance:p}}});if(!l)return null;const u=l.tipo==="todas"?null:r;try{const c=await fetch("/asignaciones-turno/propagar-dia",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":z(),Accept:"application/json"},body:JSON.stringify({fecha_origen:e,alcance:l.alcance,maquina_id:u})}),p=await c.json();if(!c.ok)throw new Error(p.message||`Error HTTP ${c.status}`);p.eventos_eliminados&&p.eventos_eliminados.length>0&&p.eventos_eliminados.forEach(s=>{const x=a.getEventById(s);x&&x.remove()}),p.eventos&&p.eventos.length>0&&p.eventos.forEach(s=>{const x=a.getEventById(s.id);x&&x.remove(),a.addEvent({id:s.id,title:s.title,start:s.start,end:s.end,resourceId:s.resourceId,backgroundColor:s.backgroundColor,borderColor:s.borderColor,textColor:s.textColor||"#000000",extendedProps:s.extendedProps||{}})});const v=l.tipo==="todas"?"todas las máquinas":n,d=l.alcance==="semana_actual"?"esta semana":"2 semanas";return await Swal.fire({icon:"success",title:"Propagación completada",html:`
                <div class="text-left">
                    <p class="mb-2">Se propagaron <b>${p.copiadas||0}</b> asignaciones a <b>${p.dias_procesados||0}</b> días.</p>
                    ${p.eliminadas>0?`<p class="mb-2 text-amber-600">Se quitaron <b>${p.eliminadas}</b> trabajadores de máquinas (modo espejo).</p>`:""}
                    <p class="text-sm text-gray-500">
                        Origen: ${e}<br>
                        Máquinas: ${v}<br>
                        Alcance: ${d}
                    </p>
                </div>
            `,timer:3500,showConfirmButton:!1}),p}catch(c){return console.error("Error al propagar día:",c),await Swal.fire({icon:"error",title:"Error",text:c.message||"No se pudieron propagar las asignaciones",confirmButtonText:"Aceptar"}),null}}function Q(e,r,{fechaISO:n,resourceId:a,horaISO:i},l,u){var v;const c=a?((v=u.find(d=>String(d.id)===String(a)))==null?void 0:v.title)||`Máquina ${a}`:"Seleccione una máquina",p=[{icon:"📅",label:"Crear festivo este día",onClick:async()=>{const d=await K(n);if(!d)return;const s=new Date(d.fecha+"T00:00:00"),x=new Date(s);x.setDate(x.getDate()+1);const o=(u||[]).map(t=>t.id);l.addEvent({id:"festivo-"+d.id,title:d.titulo,start:s.toISOString(),end:x.toISOString(),allDay:!0,resourceIds:o,backgroundColor:"#ff0000",borderColor:"#b91c1c",textColor:"#ffffff",editable:!0,classNames:["evento-festivo"],extendedProps:{es_festivo:!0,festivo_id:d.id,entrada:null,salida:null}}),Swal.fire({icon:"success",title:"Festivo creado",timer:1200,showConfirmButton:!1})}},{type:"separator"},{icon:"📤",label:`Propagar asignaciones de ${n}...`,onClick:()=>U({fechaISO:n,maquinaId:a||null,maquinaNombre:c,calendar:l})},{type:"separator"},{icon:"🔧",label:a?`Generar turnos para ${c}`:"Generar turnos (seleccione una máquina)",disabled:!a,onClick:async()=>{var s,x;if(console.log("[menu] Click en generar turnos, resourceId:",a),!a){console.log("[menu] No hay resourceId, mostrando advertencia"),Swal.fire({icon:"warning",title:"Máquina no seleccionada",text:"Haz clic derecho sobre una máquina específica para generar turnos."});return}console.log("[menu] Llamando a generarTurnosDialog...");const d=await G(n,a,c,i);if(console.log("[menu] Resultado del diálogo:",d),d&&d.eventos){console.log("[menu] Procesando eventos:",d.eventos.length);const o=(x=(s=d.eventos[0])==null?void 0:s.extendedProps)==null?void 0:x.user_id;if(o){const t=l.getEvents(),m=d.eventos.map(b=>{var g;return(g=b.start)==null?void 0:g.slice(0,10)});t.forEach(b=>{var f,y,w,_;const g=(f=b.extendedProps)==null?void 0:f.user_id,h=((y=b.startStr)==null?void 0:y.slice(0,10))||((w=b.start)==null?void 0:w.toISOString().slice(0,10));g===o&&m.includes(h)&&!((_=b.extendedProps)!=null&&_.es_festivo)&&(console.log("[menu] Eliminando evento antiguo:",b.id),b.remove())})}d.eventos.forEach(t=>{console.log("[menu] Añadiendo evento:",{id:t.id,start:t.start,end:t.end,resourceId:t.resourceId}),l.addEvent({id:t.id,title:t.title,start:t.start,end:t.end,resourceId:t.resourceId,backgroundColor:t.backgroundColor,borderColor:t.borderColor,textColor:t.textColor||"#000000",extendedProps:t.extendedProps||{}})}),console.log("[menu] Eventos actualizados correctamente")}}}];W(e,r,{headerHtml:`<div>Acciones para <b>${n}</b></div>`,items:p})}function Z(e,r,{event:n,titulo:a}){O(e,r,`
    <div style="padding:10px 12px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
      ${a}
    </div>
    <button id="ctx-eliminar-festivo" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;">
      🗑️ Eliminar festivo
    </button>
  `).querySelector("#ctx-eliminar-festivo").addEventListener("click",async()=>{if(E(),!await Swal.fire({icon:"warning",title:"Eliminar festivo",html:`<div>¿Seguro que quieres eliminar <b>${a}</b>?</div>`,showCancelButton:!0,confirmButtonText:"Eliminar",cancelButtonText:"Cancelar"}).then(c=>c.isConfirmed))return;const u=n.extendedProps.festivo_id;await A(k().festivo.delete.replace("__ID__",u),{method:"DELETE"}),n.remove(),Swal.fire({icon:"success",title:"Festivo eliminado",timer:1200,showConfirmButton:!1})})}async function ee(e){const r=e.extendedProps||{},n=r.entrada||"",a=r.salida||"",i=await Swal.fire({title:"Editar fichaje",html:`
      <div class="flex flex-col gap-3">
        <label class="text-left text-sm">Entrada</label>
        <input id="entradaHora" type="time" class="swal2-input" value="${n}">
        <label class="text-left text-sm">Salida</label>
        <input id="salidaHora" type="time" class="swal2-input" value="${a}">
      </div>`,showCancelButton:!0,confirmButtonText:"Guardar",cancelButtonText:"Cancelar",preConfirm:()=>{const u=document.getElementById("entradaHora").value,c=document.getElementById("salidaHora").value;return!u&&!c?(Swal.showValidationMessage("Debes indicar al menos una hora"),!1):{entrada:u,salida:c}}});if(!i.isConfirmed)return;const l=e.id.toString().replace(/^turno-/,"");await A(k().asignacion.updateHoras.replace("__ID__",l),{method:"POST",body:i.value}),e.setExtendedProp("entrada",i.value.entrada),e.setExtendedProp("salida",i.value.salida),Swal.fire({icon:"success",title:"Horas actualizadas",timer:1500,showConfirmButton:!1})}function te(e,r,n){var c,p;const a=n.title||"Operario",i=((c=n.extendedProps)==null?void 0:c.categoria_nombre)??"",l=((p=n.extendedProps)==null?void 0:p.especialidad_nombre)??"Sin especialidad",u=O(e,r,`
    <div style="padding:10px 12px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
      ${a} <div style="font-size:12px">${i} · ${l}</div>
    </div>
    <button id="ctx-editar-fichajes" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;">
      ✏️ Editar fichajes
    </button>
    <button id="ctx-eliminar-registro" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;color:#b91c1c;">
      🗑️ Eliminar registro
    </button>
  `);u.querySelector("#ctx-editar-fichajes").addEventListener("click",async()=>{E(),await ee(n)}),u.querySelector("#ctx-eliminar-registro").addEventListener("click",async()=>{var x;if(E(),!await Swal.fire({icon:"warning",title:"Eliminar registro",html:`<div>¿Seguro que quieres eliminar este evento/asignación?</div>
                   <div class="text-xs text-gray-500 mt-1">Esta acción no se puede deshacer.</div>`,confirmButtonText:"Eliminar",cancelButtonText:"Cancelar",showCancelButton:!0,confirmButtonColor:"#b91c1c"}).then(o=>o.isConfirmed))return;const d="/asignaciones-turnos/destroy",s={_method:"DELETE",fecha_inicio:n.startStr,fecha_fin:n.endStr??n.startStr,tipo:"eliminarTurnoEstado",user_id:(x=n.extendedProps)==null?void 0:x.user_id};console.log("[workerMenu] Eliminando turno, payload:",s),console.log("[workerMenu] Event extendedProps:",n.extendedProps);try{await A(d,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(s)}),n.remove(),Swal.fire({icon:"success",title:"Registro eliminado",timer:1300,showConfirmButton:!1})}catch(o){console.error("Error al eliminar el turno:",o),Swal.fire({icon:"error",title:"Error al eliminar",text:o.message||"No se pudo eliminar el turno."})}})}function oe(e,r){const n=[];if(!e||typeof e!="object")return n;const a=(r||[]).filter(l=>l.hora_inicio&&l.hora_fin);if(a.length===0)return n;const i={};a.forEach(l=>{const u=l.nombre.toLowerCase(),[c]=l.hora_inicio.split(":").map(Number),[p]=l.hora_fin.split(":").map(Number),v=p<c;let d,s;v?(d="00:00:00",s="00:30:00"):c<14?(d="08:00:00",s="08:30:00"):(d="16:00:00",s="16:30:00"),i[u]={esNocturno:v,slotInicio:d,slotFin:s,color:l.color}});for(const[l,u]of Object.entries(e))if(!(!u||typeof u!="object")){for(const[c,p]of Object.entries(u))if(p)for(const[v,d]of Object.entries(i)){const s=p[v]||0;s<=0||n.push({id:`carga-${l}-${c}-${v}`,title:`${s}h`,start:`${c}T${d.slotInicio}`,end:`${c}T${d.slotFin}`,resourceId:l,backgroundColor:R(s),borderColor:R(s),textColor:"#000",editable:!1,classNames:["evento-carga"],extendedProps:{es_carga:!0,turno:v,horas:s}})}}return n}function R(e){return e>6?"#fca5a5":e>3?"#fcd34d":"#86efac"}function ne(e){const r={slotMinTime:"00:00:00",slotMaxTime:"24:00:00",slotDuration:"08:00:00",turnos:[]};if(!e||e.length===0)return r;const n=e.filter(i=>i.hora_inicio&&i.hora_fin);return n.length===0?r:{slotMinTime:"00:00:00",slotMaxTime:"24:00:00",slotDuration:"08:00:00",turnos:[...n].sort((i,l)=>{const[u]=i.hora_inicio.split(":").map(Number),[c]=i.hora_fin.split(":").map(Number),[p]=l.hora_inicio.split(":").map(Number),[v]=l.hora_fin.split(":").map(Number),d=c<u,s=v<p;return d&&!s?-1:!d&&s?1:u-p})}}function ae(e,r,n){const a=localStorage.getItem(e);return r.includes(a)?a:n}let P={};async function re(e,r){try{const a=await(await fetch(`/asignaciones-turno/ocupacion-cruzada?start=${e}&end=${r}&calendario=produccion`)).json();a.success&&(P=a.ocupados||{},console.log("[cal] Trabajadores en obras externas:",P))}catch(n){console.error("[cal] Error verificando ocupación en obras:",n)}}function se(e){return P[e]!==void 0}function ie(e){var r;return((r=P[e])==null?void 0:r.total_dias)||0}function le(e){const{maquinas:r,eventos:n,cargaTrabajo:a,turnos:i}=X(),l=ne(i),u=oe(a,i),c=[...n,...u],p="vistaObras",v="fechaObras";let d=!1;const s=new FullCalendar.Calendar(e,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",initialView:ae(p,["resourceTimelineDay","resourceTimelineWeek"],"resourceTimelineWeek"),initialDate:localStorage.getItem(v)||void 0,selectable:!0,unselectAuto:!0,async datesSet(o){localStorage.setItem("vistaObras",o.view.type),localStorage.setItem("fechaObras",o.startStr);const t=o.view.type==="resourceTimelineDay";if(e.classList.toggle("vista-dia",t),e.classList.toggle("vista-semana",!t),t){const h=e.querySelector(".fc-toolbar-title");if(h){const f=o.start,y={weekday:"long",day:"numeric",month:"long",year:"numeric"};h.textContent=f.toLocaleDateString("es-ES",y)}}const m=document.getElementById("btnRepetirSemana");m&&(o.view.type==="resourceTimelineWeek"?(m.classList.remove("hidden"),m.dataset.fecha=o.startStr):m.classList.add("hidden"));const b=o.startStr.slice(0,10),g=o.endStr.slice(0,10);await re(b,g)},displayEventEnd:!0,eventMinHeight:30,firstDay:1,height:"auto",headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimelineDay,resourceTimelineWeek"},buttonText:{today:"Hoy",week:"Semana",day:"Día"},slotLabelDidMount(o){if(o.view.type==="resourceTimelineDay"&&l.turnos){const m=o.date.getHours();let b=0;m>=8&&m<16?b=1:m>=16&&(b=2);const g=l.turnos[b];g&&(o.el.style.backgroundColor=g.color||"#e5e7eb")}},slotLabelContent(o){if(o.view.type==="resourceTimelineDay"){const m=o.date.getHours();if(m===0)return{html:"<b>Noche</b>"};if(m===8)return{html:"<b>Mañana</b>"};if(m===16)return{html:"<b>Tarde</b>"}}return null},views:{resourceTimelineDay:{slotMinTime:l.slotMinTime,slotMaxTime:l.slotMaxTime,slotDuration:l.slotDuration,titleFormat:{weekday:"long",day:"numeric",month:"long",year:"numeric"}},resourceTimelineWeek:{slotDuration:{days:1},slotLabelFormat:{weekday:"long"}}},editable:!0,resources:r,resourceOrder:"orden",resourceAreaWidth:"100px",resourceLabelDidMount(o){const t=o.resource.extendedProps.backgroundColor;t&&(o.el.style.backgroundColor=t,o.el.style.color="#fff")},filterResourcesWithEvents:!1,events:c,resourceAreaColumns:[{field:"title",headerContent:"Máquinas"}],eventDragStart:o=>{d=!0;const t=o.el;t._tippy&&(t._tippy.hide(),t._tippy.disable()),document.querySelectorAll(".fc-event").forEach(m=>{m._tippy&&m._tippy.disable()})},eventDragStop:o=>{d=!1,setTimeout(()=>{document.querySelectorAll(".fc-event").forEach(b=>{b._tippy&&b._tippy.enable()});const t=o.el,m=o.event.extendedProps||{};if(!t._tippy&&m.foto&&!m.es_festivo){const b=`<img src="${m.foto}" class="w-18 h-18 rounded-full object-cover ring-2 ring-blue-400 shadow-lg">`;tippy(t,{content:b,allowHTML:!0,placement:"top",theme:"transparent-avatar",interactive:!1,arrow:!1,delay:[100,0],offset:[0,10],onShow(){if(d)return!1}})}},100)},eventDrop:async o=>{var b,g;const t=o.event,m=t.extendedProps||{};try{if(m.es_festivo){const S=t.startStr.slice(0,10);await fetch(k().festivo.update.replace("__ID__",m.festivo_id),{method:"PUT",headers:{"X-CSRF-TOKEN":window.AppPlanif.csrf,Accept:"application/json","Content-Type":"application/json"},body:JSON.stringify({fecha:S})}).then($=>{if(!$.ok)throw new Error(`HTTP ${$.status}`)});return}const h=t.id.replace(/^turno-/,""),f=(b=t.getResources())==null?void 0:b[0],y=f?parseInt(f.id,10):null,w=(g=t.start)==null?void 0:g.toISOString(),_=new Date(w).getHours();let T=null;for(const S of l.turnos){const[$]=S.hora_inicio.split(":").map(Number),[I]=S.hora_fin.split(":").map(Number),V=I<$;let q=!1;if(V?q=_>=$||_<I:q=_>=$&&_<I,q){T=S.id;break}}if(T||(T=_>=6&&_<14?1:_>=14&&_<22?2:3),!y||!w)throw new Error("Datos incompletos");const B=k().asignacion.updatePuesto.replace("__ID__",h),j=await fetch(B,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":window.AppPlanif.csrf},body:JSON.stringify({maquina_id:y,start:w,turno_id:T})}),C=await j.json();if(!j.ok)throw new Error((C==null?void 0:C.message)||`HTTP ${j.status}`);C.color&&(t.setProp("backgroundColor",C.color),t.setProp("borderColor",C.color)),typeof C.nuevo_obra_id<"u"&&t.setExtendedProp("obra_id",C.nuevo_obra_id),t.setExtendedProp("turno_id",T);const F=l.turnos.find(S=>S.id===T);F&&t.setExtendedProp("turno_nombre",F.nombre),s.refetchEvents()}catch(h){console.error(h),Swal.fire("Error",h.message||"Ocurrió un error inesperado.","error"),o.revert()}},eventDidMount(o){const t=o.event,m=t.extendedProps||{};if(m.es_carga){o.el.title=`${m.horas}h de trabajo - Turno ${m.turno}`;return}if(o.el._tippy&&o.el._tippy.destroy(),m.foto&&!m.es_festivo){const b=`<img src="${m.foto}" class="w-18 h-18 rounded-full object-cover ring-2 ring-blue-400 shadow-lg">`,g=tippy(o.el,{content:b,allowHTML:!0,placement:"top",theme:"transparent-avatar",interactive:!1,arrow:!1,delay:[100,0],offset:[0,10],onShow(){if(d)return!1}});d&&g&&g.disable()}o.el.addEventListener("contextmenu",b=>{b.preventDefault(),b.stopPropagation(),m.es_festivo?Z(b.clientX,b.clientY,{event:t,titulo:t.title}):te(b.clientX,b.clientY,t)})},eventClick(o){const m=o.event.extendedProps||{};if(m.es_festivo)return;const b=m.user_id;if(b){const g=k().userShow.replace(":id",b);window.location.href=g}},eventContent(o){const t=o.event.extendedProps;if(t!=null&&t.es_carga)return{html:`<div class="carga-content">${t.horas}h</div>`};if(t!=null&&t.es_festivo)return{html:`<div class="px-2 py-1 text-xs font-semibold" style="color:#fff">${o.event.title}</div>`};const m=t.entrada&&t.salida?`${t.entrada} / ${t.salida}`:t.entrada||t.salida||"-- / --",b=t.turno_nombre?t.turno_nombre.charAt(0).toUpperCase()+t.turno_nombre.slice(1):"",g=t.user_id,h=se(g),f=ie(g),y=h?`<span class="ml-1 px-1 py-0.5 bg-green-500 text-white text-[8px] rounded font-bold" title="${f} día(s) en obra">🏗️${f}</span>`:"";return{html:`
          <div class="px-2 py-1 text-xs font-semibold flex items-center ${h?"tiene-obra-externa":""}">
            <div class="flex flex-col">
              <span>${o.event.title} <span class="text-[10px] font-medium opacity-70">[${b}]</span>${y}</span>
              <span class="text-[10px] font-normal opacity-80">(${t.categoria_nombre??""} 🛠 ${t.especialidad_nombre??"Sin especialidad"})</span>
            </div>
            <div class="ml-auto text-right">
              <span class="text-[10px] font-normal opacity-80">${m}</span>
            </div>
          </div>`}}});s.render(),console.log("[cal] Configurando event listener para contextmenu...");const x=s.el;return console.log("[cal] Elemento raíz del calendario:",x),console.log("[cal] Agregando event listener de contextmenu al calendario"),x.addEventListener("contextmenu",o=>{if(console.log("[cal] ¡Contextmenu disparado!",o.target),o.target.closest(".fc-event")){console.log("[cal] Es un evento, ignorando");return}o.preventDefault();let t=null,m=null,b=null;const g=o.target.closest("[data-date]");if(g){const f=g.getAttribute("data-date")||"";f.includes("T")?(m=f.slice(0,10),b=f.slice(11,16)):m=f.slice(0,10)}if(!b&&s.view.type==="resourceTimelineDay"){const f=s;if(f.getDate(),typeof f.el.getBoundingClientRect=="function"){f.el.getBoundingClientRect();const y=f.el.querySelector(".fc-timeline-body");if(y){const w=y.getBoundingClientRect(),_=o.clientX-w.left,T=w.width,B=_/T*24,j=Math.floor(B);b=String(j).padStart(2,"0")+":00",console.log("[cal] Hora calculada por posición X:",b)}}}if(!m){console.log("[cal] No se pudo determinar la fecha");return}console.log("[cal] Fecha encontrada:",m,"Hora:",b),console.log("[cal] Elemento clickeado:",o.target),console.log("[cal] Elemento con data-date:",g);const h=x.querySelectorAll(".fc-timeline-lane[data-resource-id]");if(console.log("[cal] Filas de recursos encontradas:",h.length),h.length>0){const f=o.clientY;console.log("[cal] Posición Y del click:",f);for(const y of h){const w=y.getBoundingClientRect();if(console.log("[cal] Examinando lane con resource-id:",y.dataset.resourceId,"top:",w.top,"bottom:",w.bottom),f>=w.top&&f<=w.bottom){t=y.dataset.resourceId,console.log("[cal] ¡ResourceId encontrado por posición Y!:",t);break}}}console.log("[cal] ResourceId final detectado:",t,"Fecha:",m,"Hora:",b),Q(o.clientX,o.clientY,{fechaISO:m,resourceId:t,horaISO:b},s,r)}),console.log("[cal] Event listener de contextmenu agregado correctamente"),s}function N(){const e=document.getElementById("calendario");if(!e||e.getAttribute("data-calendar-type")!=="trabajadores"||!window.AppPlanif)return;if(window.calendarTrabajadores){try{window.calendarTrabajadores.destroy()}catch{}window.calendarTrabajadores=null}const r=le(e);window.calendarTrabajadores=r}function ce(){if(window.calendarTrabajadores){try{window.calendarTrabajadores.destroy()}catch{}window.calendarTrabajadores=null}}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",N):N();document.addEventListener("livewire:navigated",N);document.addEventListener("livewire:navigating",ce);
