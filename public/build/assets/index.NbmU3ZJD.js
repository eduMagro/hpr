const N=()=>window.AppPlanif,F=()=>N().csrf,P=()=>N().routes,Y=()=>{var o;const e=N()||{};let a=e.turnos;return Array.isArray(a)||(a=((o=e.turnosConfig)==null?void 0:o.turnos)||[]),{maquinas:e.maquinas||[],eventos:e.eventos||[],cargaTrabajo:e.cargaTrabajo||{},turnos:a}};let A=null;function T(){A&&(A.remove(),A=null,document.removeEventListener("click",T),document.removeEventListener("scroll",T,!0),window.removeEventListener("resize",T))}function H(e,a,o){T();const r=document.createElement("div");r.className="fc-contextmenu",Object.assign(r.style,{position:"fixed",zIndex:9999,minWidth:"240px",background:"#fff",border:"1px solid #e5e7eb",boxShadow:"0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -2px rgba(0,0,0,.05)",borderRadius:"8px",overflow:"hidden",visibility:"hidden"}),r.innerHTML=o,document.body.appendChild(r);const s=r.getBoundingClientRect(),i=window.innerHeight,u=window.innerWidth;let p=a;a+s.height>i&&(p=a-s.height,p<0&&(p=Math.max(0,i-s.height)));let d=e;return e+s.width>u&&(d=e-s.width,d<0&&(d=Math.max(0,u-s.width))),Object.assign(r.style,{top:p+"px",left:d+"px",visibility:"visible"}),A=r,setTimeout(()=>{document.addEventListener("click",T),document.addEventListener("scroll",T,!0),window.addEventListener("resize",T)},0),r}function J(e,a,{headerHtml:o="",items:r=[]}){const s=`
    <div class="ctx-menu-container">
      ${o?`<div class="ctx-menu-header">${o}</div>`:""}
      ${r.map((u,p)=>u.type==="separator"?'<div class="ctx-menu-separator" style="height:1px; background:#e5e7eb; margin:4px 0;"></div>':`
        <button class="ctx-menu-item${u.danger?" ctx-menu-danger":""}${u.disabled?" ctx-menu-disabled":""}" data-idx="${p}" ${u.disabled?"disabled":""}>
          ${u.icon?`<span class="ctx-menu-icon">${u.icon}</span>`:""}
          <span class="ctx-menu-label">${u.label}</span>
        </button>
      `).join("")}
    </div>
  `,i=H(e,a,s);return i.querySelectorAll(".ctx-menu-item").forEach(u=>{const p=parseInt(u.dataset.idx,10),d=r[p];u.addEventListener("click",async x=>{if(x.preventDefault(),x.stopPropagation(),console.log("[baseMenu] Click en item:",d.label,"disabled:",d.disabled),d.disabled){console.log("[baseMenu] Item deshabilitado, ignorando");return}const l=d.onClick;console.log("[baseMenu] Ejecutando acción..."),T();try{await(l==null?void 0:l()),console.log("[baseMenu] Acción completada")}catch(m){console.error("[baseMenu] Error en acción:",m)}})}),i}async function L(e,a={}){const o={headers:{Accept:"application/json","X-CSRF-TOKEN":F()},...a};o.body&&typeof o.body!="string"&&(o.headers["Content-Type"]="application/json",o.body=JSON.stringify(o.body));const r=await fetch(e,o);let s=null;try{s=await r.json()}catch{}if(!r.ok)throw new Error((s==null?void 0:s.message)||`HTTP ${r.status}`);return s}async function G(e){const a=await Swal.fire({title:"Nuevo festivo",input:"text",inputLabel:"Título del festivo",inputValue:"Festivo",showCancelButton:!0,confirmButtonText:"Crear",cancelButtonText:"Cancelar",inputValidator:r=>!r||!r.trim()?"Pon un título":void 0});return a.isConfirmed?(await L(P().festivo.store,{method:"POST",body:{fecha:e,titulo:a.value.trim()}})).festivo:null}async function U(e,a,o,r){try{const s=await fetch("/asignaciones-turno/verificar-conflictos",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":F(),Accept:"application/json"},body:JSON.stringify({user_id:e,fecha_inicio:a,fecha_fin:o||a,destino:r})});if(!s.ok)return console.error("Error al verificar conflictos:",s.status),!0;const i=await s.json();if(!i.tiene_conflictos)return!0;const u=r==="taller",p=u?"⚠️ Este trabajador tiene días en obra":"⚠️ Este trabajador tiene días en taller",d=u?i.dias_en_obra:i.dias_en_taller,x=u?"obra externa":"taller/producción",l=d.length>0?d.join(", "):"varios días";return(await Swal.fire({icon:"warning",title:p,html:`
                <div class="text-left">
                    <p class="mb-3">Este trabajador ya tiene asignaciones en <strong>${x}</strong> esta semana:</p>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-3">
                        <p class="font-semibold text-amber-800">
                            ${u?"🏗️":"🏭"} ${l}
                        </p>
                    </div>
                    <p class="text-sm text-gray-600">¿Deseas continuar con la asignación de todos modos?</p>
                </div>
            `,showCancelButton:!0,confirmButtonText:"Sí, continuar",cancelButtonText:"Cancelar",confirmButtonColor:"#f59e0b",cancelButtonColor:"#6b7280"})).isConfirmed}catch(s){return console.error("Error al verificar conflictos:",s),!0}}function Q(e){var r,s,i;if(!e)return null;const[a]=e.split(":").map(Number),o=((s=(r=window.AppPlanif)==null?void 0:r.turnosConfig)==null?void 0:s.turnos)||((i=window.AppPlanif)==null?void 0:i.turnos)||[];for(const u of o){if(!u.hora_inicio||!u.hora_fin)continue;const[p]=u.hora_inicio.split(":").map(Number),[d]=u.hora_fin.split(":").map(Number),x=d<p;let l=!1;if(x?l=a>=p||a<d:l=a>=p&&a<d,l)return u.nombre}return a>=0&&a<8?"noche":a>=8&&a<16?"mañana":"tarde"}const V=[{bg:"bg-emerald-50",border:"border-emerald-200",text:"text-emerald-700",badge:"bg-emerald-100 text-emerald-800"},{bg:"bg-blue-50",border:"border-blue-200",text:"text-blue-700",badge:"bg-blue-100 text-blue-800"},{bg:"bg-amber-50",border:"border-amber-200",text:"text-amber-700",badge:"bg-amber-100 text-amber-800"},{bg:"bg-purple-50",border:"border-purple-200",text:"text-purple-700",badge:"bg-purple-100 text-purple-800"},{bg:"bg-rose-50",border:"border-rose-200",text:"text-rose-700",badge:"bg-rose-100 text-rose-800"},{bg:"bg-cyan-50",border:"border-cyan-200",text:"text-cyan-700",badge:"bg-cyan-100 text-cyan-800"}];function W(e){return!e||e.length===0?"":e.map(a=>{const o=a.split("-");return o.length===3?new Date(o[0],o[1]-1,o[2]).toLocaleDateString("es-ES",{weekday:"short",day:"numeric"}):a}).join(", ")}function Z(e,a,o,r){const s=V[r%V.length],i=`swal-trabajador-${o}-${a}`,u=e.operarios.length;if(u===0)return"";const p=e.operarios.map(d=>{const x=d.dias_en_obra||0,l=d.dias_en_obra_lista||[],m=W(l),_=x>0?` 🏗️${x}`:"",f=x>0?` (En obra: ${m})`:"";return`<option value="${d.id}" title="${d.name} ${d.primer_apellido||""} ${d.segundo_apellido||""}${f}">${d.name} ${d.primer_apellido||""} ${d.segundo_apellido||""}${_}</option>`}).join("");return`
        <div class="mb-3 ${s.bg} ${s.border} border rounded-lg p-2">
            <label class="block text-xs font-semibold ${s.text} mb-1">
                ${e.empresa_nombre}
                <span class="${s.badge} text-xs px-1.5 py-0.5 rounded-full ml-1">${u}</span>
            </label>
            <select id="${i}" data-tipo="${o}" class="swal2-input w-full select-operario text-sm py-1" style="margin: 0;">
                <option value="" selected>Seleccionar...</option>
                ${p}
            </select>
        </div>
    `}function I(e,a,o,r){if(!e||e.length===0)return`
            <div class="mb-4">
                <p class="text-sm font-semibold text-gray-500 mb-2">${o}</p>
                <p class="text-xs text-gray-400 italic">No hay operarios</p>
            </div>
        `;const s=e.reduce((u,p)=>u+p.operarios.length,0),i=e.map((u,p)=>Z(u,p,a,p)).join("");return`
        <div class="mb-4">
            <p class="text-sm font-semibold text-gray-700 mb-2">
                ${o} <span class="${r}">(${s})</span>
            </p>
            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                ${i}
            </div>
        </div>
    `}async function ee(e,a,o,r=null){const s=Q(r);console.log("[generarTurnos] horaISO:",r,"turnoDetectado:",s);let i={sin_turno_por_empresa:[],de_maquina_por_empresa:[],todos_por_empresa:[],todos:[]};try{const f=await fetch(`/api/usuarios/operarios-agrupados?fecha=${e}&maquina_id=${a}`,{headers:{"X-CSRF-TOKEN":window.AppPlanif.csrf,Accept:"application/json"}});f.ok?(i=await f.json(),console.log("[generarTurnos] Datos recibidos:",i)):console.error("Error al obtener operarios:",f.status)}catch(f){console.error("Error al obtener operarios:",f)}const u=s?`<span class="text-green-600 font-semibold">${s.charAt(0).toUpperCase()+s.slice(1)}</span>`:'<span class="text-gray-400">No detectado</span>',p=I(i.sin_turno_por_empresa,"sin_turno","Sin turno asignado este día","text-green-600"),d=I(i.de_maquina_por_empresa,"de_maquina",`Asignados a ${o}`,"text-blue-600"),x=I(i.todos_por_empresa,"todos","Todos los operarios","text-gray-600"),{value:l}=await Swal.fire({title:"Generar Turnos",html:`
            <div class="text-left space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Fecha inicio:</strong> ${e}<br>
                        <strong>Máquina:</strong> ${o}<br>
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
                            De ${o}
                        </button>
                        <button type="button" data-tab="todos" class="tab-btn px-2 py-2 text-xs font-medium rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            Todos
                        </button>
                    </nav>
                </div>

                <!-- Contenido de tabs -->
                <div id="tab-content-sin_turno" class="tab-content">
                    ${p}
                </div>
                <div id="tab-content-de_maquina" class="tab-content hidden">
                    ${d}
                </div>
                <div id="tab-content-todos" class="tab-content hidden">
                    ${x}
                </div>

                <input type="hidden" id="swal-trabajador" value="" />
                <input type="hidden" id="swal-turno-detectado" value="${s||""}" />

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

                <div class="mb-4" id="turno-inicio-container" style="display: ${s?"none":"block"};">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Turno inicial (para diurnos) <span class="text-red-500">*</span>
                    </label>
                    <select id="swal-turno-inicio" class="swal2-input w-full">
                        <option value="mañana" ${s==="mañana"?"selected":""}>Mañana</option>
                        <option value="tarde" ${s==="tarde"?"selected":""}>Tarde</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Los turnos alternarán cada viernes</p>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-xs text-yellow-800">
                        <strong>Nota:</strong> Se saltarán automáticamente sábados, domingos, festivos y días con vacaciones.
                    </p>
                </div>
            </div>
        `,focusConfirm:!1,showCancelButton:!0,confirmButtonText:"Generar Turnos",cancelButtonText:"Cancelar",confirmButtonColor:"#3b82f6",cancelButtonColor:"#6b7280",width:"650px",didOpen:()=>{const f=document.getElementById("swal-trabajador"),t=document.getElementById("turno-inicio-container"),n=document.getElementById("trabajador-seleccionado"),c=document.getElementById("nombre-seleccionado"),b=i.todos||[];function h(w,g){f.value=w,document.querySelectorAll(".select-operario").forEach(v=>{v!==g&&(v.value="")});const y=b.find(v=>v.id===parseInt(w));if(y){const v=y.dias_en_obra||0,E=y.dias_en_obra_lista||[],C=W(E),k=v>0?` 🏗️ ${v} día(s) en obra: ${C}`:"";c.innerHTML=`${y.name} ${y.primer_apellido||""} ${y.segundo_apellido||""} (${y.empresa_nombre||"Sin empresa"})${k?`<br><span class="text-orange-600 text-xs">${k}</span>`:""}`,n.classList.remove("hidden"),y.turno==="diurno"?t.style.display="block":t.style.display="none"}}document.querySelectorAll(".select-operario").forEach(w=>{w.addEventListener("change",g=>{g.target.value&&h(g.target.value,g.target)})}),document.querySelectorAll(".tab-btn").forEach(w=>{w.addEventListener("click",g=>{const y=g.target.dataset.tab;document.querySelectorAll(".tab-btn").forEach(v=>{v.classList.remove("border-green-500","text-green-600","bg-green-50"),v.classList.add("border-transparent","text-gray-500")}),g.target.classList.remove("border-transparent","text-gray-500"),g.target.classList.add("border-green-500","text-green-600","bg-green-50"),document.querySelectorAll(".tab-content").forEach(v=>{v.classList.add("hidden")}),document.getElementById(`tab-content-${y}`).classList.remove("hidden")})})},preConfirm:()=>{const f=document.getElementById("swal-trabajador").value,t=document.getElementById("swal-alcance").value,n=document.getElementById("swal-turno-inicio").value,c=document.getElementById("swal-turno-detectado").value;return f?{trabajador_id:f,alcance:t,turno_inicio:n,turno_detectado:c||null}:(Swal.showValidationMessage("Debes seleccionar un trabajador"),!1)}});if(!l)return null;let m=e;if(l.alcance==="dos_semanas"){const f=new Date(e),t=(5-f.getDay()+7)%7+7;f.setDate(f.getDate()+t),m=f.toISOString().slice(0,10)}else l.alcance==="resto_año"&&(m=`${new Date(e).getFullYear()}-12-31`);if(!await U(l.trabajador_id,e,m,"taller"))return null;try{const t=await fetch("/profile/generar-turnos-calendario",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":window.AppPlanif.csrf,Accept:"application/json"},body:JSON.stringify({user_id:l.trabajador_id,maquina_id:a,fecha_inicio:e,alcance:l.alcance,turno_inicio:l.turno_inicio,turno_detectado:l.turno_detectado})}),n=await t.json();if(console.log("[generarTurnos] Respuesta backend:",n),n.eventos&&n.eventos.length>0&&console.log("[generarTurnos] Primer evento:",n.eventos[0]),!t.ok)throw new Error(n.message||`Error HTTP ${t.status}`);return{...n,eventos:n.eventos||[]}}catch(f){return console.error("Error al generar turnos:",f),await Swal.fire({icon:"error",title:"Error",text:f.message||"No se pudieron generar los turnos",confirmButtonText:"Aceptar"}),null}}async function te({fechaISO:e,maquinaId:a,maquinaNombre:o,calendar:r}){const s=a&&a!=="null",{value:i}=await Swal.fire({title:"Propagar Asignaciones",html:`
            <div class="text-left space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Fecha origen:</strong> ${e}<br>
                        ${s?`<strong>Máquina:</strong> ${o}`:'<strong>Máquina:</strong> <span class="text-gray-500">Ninguna seleccionada</span>'}
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        ¿Qué quieres propagar?
                    </label>

                    <div class="space-y-2">
                        ${s?`
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="tipo" value="maquina" class="mt-1 mr-3" checked>
                            <div>
                                <span class="font-medium text-gray-900">Solo ${o}</span>
                                <p class="text-xs text-gray-500 mt-1">
                                    Propaga únicamente las asignaciones de esta máquina al resto de días.
                                </p>
                            </div>
                        </label>
                        `:""}

                        <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="radio" name="tipo" value="todas" class="mt-1 mr-3" ${s?"":"checked"}>
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
        `,focusConfirm:!1,showCancelButton:!0,confirmButtonText:"Propagar",cancelButtonText:"Cancelar",confirmButtonColor:"#10b981",width:"550px",preConfirm:()=>{var x,l;const p=(x=document.querySelector('input[name="tipo"]:checked'))==null?void 0:x.value,d=(l=document.querySelector('input[name="alcance"]:checked'))==null?void 0:l.value;return!p||!d?(Swal.showValidationMessage("Selecciona todas las opciones"),!1):{tipo:p,alcance:d}}});if(!i)return null;const u=i.tipo==="todas"?null:a;try{const p=await fetch("/asignaciones-turno/propagar-dia",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":F(),Accept:"application/json"},body:JSON.stringify({fecha_origen:e,alcance:i.alcance,maquina_id:u})}),d=await p.json();if(!p.ok)throw new Error(d.message||`Error HTTP ${p.status}`);d.eventos_eliminados&&d.eventos_eliminados.length>0&&d.eventos_eliminados.forEach(m=>{const _=r.getEventById(m);_&&_.remove()}),d.eventos&&d.eventos.length>0&&d.eventos.forEach(m=>{const _=r.getEventById(m.id);_&&_.remove(),r.addEvent({id:m.id,title:m.title,start:m.start,end:m.end,resourceId:m.resourceId,backgroundColor:m.backgroundColor,borderColor:m.borderColor,textColor:m.textColor||"#000000",extendedProps:m.extendedProps||{}})});const x=i.tipo==="todas"?"todas las máquinas":o,l=i.alcance==="semana_actual"?"esta semana":"2 semanas";return await Swal.fire({icon:"success",title:"Propagación completada",html:`
                <div class="text-left">
                    <p class="mb-2">Se propagaron <b>${d.copiadas||0}</b> asignaciones a <b>${d.dias_procesados||0}</b> días.</p>
                    ${d.eliminadas>0?`<p class="mb-2 text-amber-600">Se quitaron <b>${d.eliminadas}</b> trabajadores de máquinas (modo espejo).</p>`:""}
                    <p class="text-sm text-gray-500">
                        Origen: ${e}<br>
                        Máquinas: ${x}<br>
                        Alcance: ${l}
                    </p>
                </div>
            `,timer:3500,showConfirmButton:!1}),d}catch(p){return console.error("Error al propagar día:",p),await Swal.fire({icon:"error",title:"Error",text:p.message||"No se pudieron propagar las asignaciones",confirmButtonText:"Aceptar"}),null}}function oe(e,a,{fechaISO:o,resourceId:r,horaISO:s},i,u){var x;const p=r?((x=u.find(l=>String(l.id)===String(r)))==null?void 0:x.title)||`Máquina ${r}`:"Seleccione una máquina",d=[{icon:"📅",label:"Crear festivo este día",onClick:async()=>{const l=await G(o);if(!l)return;const m=new Date(l.fecha+"T00:00:00"),_=new Date(m);_.setDate(_.getDate()+1);const f=(u||[]).map(t=>t.id);i.addEvent({id:"festivo-"+l.id,title:l.titulo,start:m.toISOString(),end:_.toISOString(),allDay:!0,resourceIds:f,backgroundColor:"#ff0000",borderColor:"#b91c1c",textColor:"#ffffff",editable:!0,classNames:["evento-festivo"],extendedProps:{es_festivo:!0,festivo_id:l.id,entrada:null,salida:null}}),Swal.fire({icon:"success",title:"Festivo creado",timer:1200,showConfirmButton:!1})}},{type:"separator"},{icon:"📤",label:`Propagar asignaciones de ${o}...`,onClick:()=>te({fechaISO:o,maquinaId:r||null,maquinaNombre:p,calendar:i})},{type:"separator"},{icon:"🔧",label:r?`Generar turnos para ${p}`:"Generar turnos (seleccione una máquina)",disabled:!r,onClick:async()=>{var m,_;if(console.log("[menu] Click en generar turnos, resourceId:",r),!r){console.log("[menu] No hay resourceId, mostrando advertencia"),Swal.fire({icon:"warning",title:"Máquina no seleccionada",text:"Haz clic derecho sobre una máquina específica para generar turnos."});return}console.log("[menu] Llamando a generarTurnosDialog...");const l=await ee(o,r,p,s);if(console.log("[menu] Resultado del diálogo:",l),l&&l.eventos){console.log("[menu] Procesando eventos:",l.eventos.length);const f=(_=(m=l.eventos[0])==null?void 0:m.extendedProps)==null?void 0:_.user_id;if(f){const t=i.getEvents(),n=l.eventos.map(c=>{var b;return(b=c.start)==null?void 0:b.slice(0,10)});t.forEach(c=>{var w,g,y,v;const b=(w=c.extendedProps)==null?void 0:w.user_id,h=((g=c.startStr)==null?void 0:g.slice(0,10))||((y=c.start)==null?void 0:y.toISOString().slice(0,10));b===f&&n.includes(h)&&!((v=c.extendedProps)!=null&&v.es_festivo)&&(console.log("[menu] Eliminando evento antiguo:",c.id),c.remove())})}l.eventos.forEach(t=>{console.log("[menu] Añadiendo evento:",{id:t.id,start:t.start,end:t.end,resourceId:t.resourceId}),i.addEvent({id:t.id,title:t.title,start:t.start,end:t.end,resourceId:t.resourceId,backgroundColor:t.backgroundColor,borderColor:t.borderColor,textColor:t.textColor||"#000000",extendedProps:t.extendedProps||{}})}),console.log("[menu] Eventos actualizados correctamente")}}}];J(e,a,{headerHtml:`<div>Acciones para <b>${o}</b></div>`,items:d})}function ne(e,a,{event:o,titulo:r}){H(e,a,`
    <div style="padding:10px 12px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
      ${r}
    </div>
    <button id="ctx-eliminar-festivo" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;">
      🗑️ Eliminar festivo
    </button>
  `).querySelector("#ctx-eliminar-festivo").addEventListener("click",async()=>{if(T(),!await Swal.fire({icon:"warning",title:"Eliminar festivo",html:`<div>¿Seguro que quieres eliminar <b>${r}</b>?</div>`,showCancelButton:!0,confirmButtonText:"Eliminar",cancelButtonText:"Cancelar"}).then(p=>p.isConfirmed))return;const u=o.extendedProps.festivo_id;await L(P().festivo.delete.replace("__ID__",u),{method:"DELETE"}),o.remove(),Swal.fire({icon:"success",title:"Festivo eliminado",timer:1200,showConfirmButton:!1})})}async function ae(e){const a=e.extendedProps||{},o=a.entrada||"",r=a.salida||"",s=await Swal.fire({title:"Editar fichaje",html:`
      <div class="flex flex-col gap-3">
        <label class="text-left text-sm">Entrada</label>
        <input id="entradaHora" type="time" class="swal2-input" value="${o}">
        <label class="text-left text-sm">Salida</label>
        <input id="salidaHora" type="time" class="swal2-input" value="${r}">
      </div>`,showCancelButton:!0,confirmButtonText:"Guardar",cancelButtonText:"Cancelar",preConfirm:()=>{const u=document.getElementById("entradaHora").value,p=document.getElementById("salidaHora").value;return!u&&!p?(Swal.showValidationMessage("Debes indicar al menos una hora"),!1):{entrada:u,salida:p}}});if(!s.isConfirmed)return;const i=e.id.toString().replace(/^turno-/,"");await L(P().asignacion.updateHoras.replace("__ID__",i),{method:"POST",body:s.value}),e.setExtendedProp("entrada",s.value.entrada),e.setExtendedProp("salida",s.value.salida),Swal.fire({icon:"success",title:"Horas actualizadas",timer:1500,showConfirmButton:!1})}function re(e,a,o){var p,d;const r=o.title||"Operario",s=((p=o.extendedProps)==null?void 0:p.categoria_nombre)??"",i=((d=o.extendedProps)==null?void 0:d.especialidad_nombre)??"Sin especialidad",u=H(e,a,`
    <div style="padding:10px 12px; font-size:13px; color:#6b7280; border-bottom:1px solid #f3f4f6;">
      ${r} <div style="font-size:12px">${s} · ${i}</div>
    </div>
    <button id="ctx-editar-fichajes" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;">
      ✏️ Editar fichajes
    </button>
    <button id="ctx-eliminar-registro" style="display:block;width:100%;text-align:left;padding:10px 12px;font-size:14px;background:#fff;border:none;cursor:pointer;color:#b91c1c;">
      🗑️ Eliminar registro
    </button>
  `);u.querySelector("#ctx-editar-fichajes").addEventListener("click",async()=>{T(),await ae(o)}),u.querySelector("#ctx-eliminar-registro").addEventListener("click",async()=>{var _;if(T(),!await Swal.fire({icon:"warning",title:"Eliminar registro",html:`<div>¿Seguro que quieres eliminar este evento/asignación?</div>
                   <div class="text-xs text-gray-500 mt-1">Esta acción no se puede deshacer.</div>`,confirmButtonText:"Eliminar",cancelButtonText:"Cancelar",showCancelButton:!0,confirmButtonColor:"#b91c1c"}).then(f=>f.isConfirmed))return;const l="/asignaciones-turnos/destroy",m={_method:"DELETE",fecha_inicio:o.startStr,fecha_fin:o.endStr??o.startStr,tipo:"eliminarTurnoEstado",user_id:(_=o.extendedProps)==null?void 0:_.user_id};console.log("[workerMenu] Eliminando turno, payload:",m),console.log("[workerMenu] Event extendedProps:",o.extendedProps);try{await L(l,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(m)}),o.remove(),Swal.fire({icon:"success",title:"Registro eliminado",timer:1300,showConfirmButton:!1})}catch(f){console.error("Error al eliminar el turno:",f),Swal.fire({icon:"error",title:"Error al eliminar",text:f.message||"No se pudo eliminar el turno."})}})}function se(e,a){const o=[];if(!e||typeof e!="object")return o;const r=(a||[]).filter(i=>i.hora_inicio&&i.hora_fin);if(r.length===0)return o;const s={};r.forEach(i=>{const u=i.nombre.toLowerCase(),[p]=i.hora_inicio.split(":").map(Number),[d]=i.hora_fin.split(":").map(Number),x=d<p;let l,m;x?(l="00:00:00",m="00:30:00"):p<14?(l="08:00:00",m="08:30:00"):(l="16:00:00",m="16:30:00"),s[u]={esNocturno:x,slotInicio:l,slotFin:m,color:i.color}});for(const[i,u]of Object.entries(e))if(!(!u||typeof u!="object")){for(const[p,d]of Object.entries(u))if(d)for(const[x,l]of Object.entries(s)){const m=d[x]||0;m<=0||o.push({id:`carga-${i}-${p}-${x}`,title:`${m}h`,start:`${p}T${l.slotInicio}`,end:`${p}T${l.slotFin}`,resourceId:i,backgroundColor:X(m),borderColor:X(m),textColor:"#000",editable:!1,classNames:["evento-carga"],extendedProps:{es_carga:!0,turno:x,horas:m}})}}return o}function X(e){return e>6?"#fca5a5":e>3?"#fcd34d":"#86efac"}function ie(e){const a={slotMinTime:"00:00:00",slotMaxTime:"24:00:00",slotDuration:"08:00:00",turnos:[]};if(!e||e.length===0)return a;const o=e.filter(s=>s.hora_inicio&&s.hora_fin);return o.length===0?a:{slotMinTime:"00:00:00",slotMaxTime:"24:00:00",slotDuration:"08:00:00",turnos:[...o].sort((s,i)=>{const[u]=s.hora_inicio.split(":").map(Number),[p]=s.hora_fin.split(":").map(Number),[d]=i.hora_inicio.split(":").map(Number),[x]=i.hora_fin.split(":").map(Number),l=p<u,m=x<d;return l&&!m?-1:!l&&m?1:u-d})}}function le(e,a,o){const r=localStorage.getItem(e);return a.includes(r)?r:o}let B={};async function ce(e,a){try{const r=await(await fetch(`/asignaciones-turno/ocupacion-cruzada?start=${e}&end=${a}&calendario=produccion`)).json();r.success&&(B=r.ocupados||{},console.log("[cal] Trabajadores en obras externas:",B))}catch(o){console.error("[cal] Error verificando ocupación en obras:",o)}}function de(e){return B[e]!==void 0}function ue(e){var a;return((a=B[e])==null?void 0:a.total_dias)||0}function pe(e){var r;const a=((r=B[e])==null?void 0:r.dias)||[];return(Array.isArray(a)?a:Object.values(a)).map(s=>{const i=s.split("-");return i.length===3?new Date(i[0],i[1]-1,i[2]).toLocaleDateString("es-ES",{weekday:"short",day:"numeric"}):s}).join(", ")}function me(e){const{maquinas:a,eventos:o,cargaTrabajo:r,turnos:s}=Y(),i=ie(s),u=se(r,s),p=[...o,...u],d="vistaObras",x="fechaObras";let l=!1;const m=new FullCalendar.Calendar(e,{schedulerLicenseKey:"CC-Attribution-NonCommercial-NoDerivatives",locale:"es",initialView:le(d,["resourceTimelineDay","resourceTimelineWeek"],"resourceTimelineWeek"),initialDate:localStorage.getItem(x)||void 0,selectable:!0,unselectAuto:!0,async datesSet(t){localStorage.setItem("vistaObras",t.view.type),localStorage.setItem("fechaObras",t.startStr);const n=t.view.type==="resourceTimelineDay";if(e.classList.toggle("vista-dia",n),e.classList.toggle("vista-semana",!n),n){const w=e.querySelector(".fc-toolbar-title");if(w){const g=t.start,y={weekday:"long",day:"numeric",month:"long",year:"numeric"};w.textContent=g.toLocaleDateString("es-ES",y)}}const c=document.getElementById("btnRepetirSemana");c&&(t.view.type==="resourceTimelineWeek"?(c.classList.remove("hidden"),c.dataset.fecha=t.startStr):c.classList.add("hidden"));const b=t.startStr.slice(0,10),h=t.endStr.slice(0,10);await ce(b,h)},displayEventEnd:!0,eventMinHeight:30,firstDay:1,height:"auto",headerToolbar:{left:"prev,next today",center:"title",right:"resourceTimelineDay,resourceTimelineWeek"},buttonText:{today:"Hoy",week:"Semana",day:"Día"},slotLabelDidMount(t){if(t.view.type==="resourceTimelineDay"&&i.turnos){const c=t.date.getHours();let b=0;c>=8&&c<16?b=1:c>=16&&(b=2);const h=i.turnos[b];h&&(t.el.style.backgroundColor=h.color||"#e5e7eb")}},slotLabelContent(t){if(t.view.type==="resourceTimelineDay"){const c=t.date.getHours();if(c===0)return{html:"<b>Noche</b>"};if(c===8)return{html:"<b>Mañana</b>"};if(c===16)return{html:"<b>Tarde</b>"}}return null},views:{resourceTimelineDay:{slotMinTime:i.slotMinTime,slotMaxTime:i.slotMaxTime,slotDuration:i.slotDuration,titleFormat:{weekday:"long",day:"numeric",month:"long",year:"numeric"}},resourceTimelineWeek:{slotDuration:{days:1},slotLabelFormat:{weekday:"long"}}},editable:!0,resources:a,resourceOrder:"orden",resourceAreaWidth:"100px",resourceLabelDidMount(t){const n=t.resource.extendedProps.backgroundColor;n&&(t.el.style.backgroundColor=n,t.el.style.color="#fff")},filterResourcesWithEvents:!1,events:p,resourceAreaColumns:[{field:"title",headerContent:"Máquinas"}],eventDragStart:t=>{l=!0;const n=t.el;n._tippy&&(n._tippy.hide(),n._tippy.disable()),document.querySelectorAll(".fc-event").forEach(c=>{c._tippy&&c._tippy.disable()})},eventDragStop:t=>{l=!1,setTimeout(()=>{document.querySelectorAll(".fc-event").forEach(b=>{b._tippy&&b._tippy.enable()});const n=t.el,c=t.event.extendedProps||{};if(!n._tippy&&c.foto&&!c.es_festivo){const b=`<img src="${c.foto}" class="w-18 h-18 rounded-full object-cover ring-2 ring-blue-400 shadow-lg">`;tippy(n,{content:b,allowHTML:!0,placement:"top",theme:"transparent-avatar",interactive:!1,arrow:!1,delay:[100,0],offset:[0,10],onShow(){if(l)return!1}})}},100)},eventDrop:async t=>{var b,h;const n=t.event,c=n.extendedProps||{};try{if(c.es_festivo){const j=n.startStr.slice(0,10);await fetch(P().festivo.update.replace("__ID__",c.festivo_id),{method:"PUT",headers:{"X-CSRF-TOKEN":window.AppPlanif.csrf,Accept:"application/json","Content-Type":"application/json"},body:JSON.stringify({fecha:j})}).then(D=>{if(!D.ok)throw new Error(`HTTP ${D.status}`)});return}const w=n.id.replace(/^turno-/,""),g=(b=n.getResources())==null?void 0:b[0],y=g?parseInt(g.id,10):null,v=(h=n.start)==null?void 0:h.toISOString(),E=new Date(v).getHours();let C=null;for(const j of i.turnos){const[D]=j.hora_inicio.split(":").map(Number),[O]=j.hora_fin.split(":").map(Number),K=O<D;let q=!1;if(K?q=E>=D||E<O:q=E>=D&&E<O,q){C=j.id;break}}if(C||(C=E>=6&&E<14?1:E>=14&&E<22?2:3),!y||!v)throw new Error("Datos incompletos");const k=P().asignacion.updatePuesto.replace("__ID__",w),S=await fetch(k,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":window.AppPlanif.csrf},body:JSON.stringify({maquina_id:y,start:v,turno_id:C})});if(S.status===419)throw new Error("Tu sesión ha expirado. Por favor recarga la página (F5).");const R=S.headers.get("content-type");if(!R||!R.includes("application/json"))throw new Error("Error de servidor. Recarga la página e intenta de nuevo.");const $=await S.json();if(!S.ok)throw new Error(($==null?void 0:$.message)||`HTTP ${S.status}`);$.color&&(n.setProp("backgroundColor",$.color),n.setProp("borderColor",$.color)),typeof $.nuevo_obra_id<"u"&&n.setExtendedProp("obra_id",$.nuevo_obra_id),n.setExtendedProp("turno_id",C);const z=i.turnos.find(j=>j.id===C);z&&n.setExtendedProp("turno_nombre",z.nombre),m.refetchEvents()}catch(w){console.error(w),Swal.fire("Error",w.message||"Ocurrió un error inesperado.","error"),t.revert()}},eventDidMount(t){const n=t.event,c=n.extendedProps||{};if(c.es_carga){t.el.title=`${c.horas}h de trabajo - Turno ${c.turno}`;return}if(t.el._tippy&&t.el._tippy.destroy(),c.foto&&!c.es_festivo){const b=`<img src="${c.foto}" class="w-18 h-18 rounded-full object-cover ring-2 ring-blue-400 shadow-lg">`,h=tippy(t.el,{content:b,allowHTML:!0,placement:"top",theme:"transparent-avatar",interactive:!1,arrow:!1,delay:[100,0],offset:[0,10],onShow(){if(l)return!1}});l&&h&&h.disable()}t.el.addEventListener("contextmenu",b=>{b.preventDefault(),b.stopPropagation(),c.es_festivo?ne(b.clientX,b.clientY,{event:n,titulo:n.title}):re(b.clientX,b.clientY,n)})},eventClick(t){const c=t.event.extendedProps||{};if(c.es_festivo)return;const b=c.user_id;if(b){const h=P().userShow.replace(":id",b);window.location.href=h}},eventContent(t){const n=t.event.extendedProps;if(n!=null&&n.es_carga)return{html:`<div class="carga-content">${n.horas}h</div>`};if(n!=null&&n.es_festivo)return{html:`<div class="px-2 py-1 text-xs font-semibold" style="color:#fff">${t.event.title}</div>`};const c=n.entrada&&n.salida?`${n.entrada} / ${n.salida}`:n.entrada||n.salida||"-- / --",b=n.turno_nombre?n.turno_nombre.charAt(0).toUpperCase()+n.turno_nombre.slice(1):"",h=n.user_id,w=de(h),g=ue(h),y=pe(h),v=w?`<span class="ml-1 px-1 py-0.5 bg-green-500 text-white text-[8px] rounded font-bold" title="En obra: ${y}">🏗️${g}</span>`:"";return{html:`
          <div class="px-2 py-1 text-xs font-semibold flex items-center ${w?"tiene-obra-externa":""}">
            <div class="flex flex-col">
              <span>${t.event.title} <span class="text-[10px] font-medium opacity-70">[${b}]</span>${v}</span>
              <span class="text-[10px] font-normal opacity-80">(${n.categoria_nombre??""} 🛠 ${n.especialidad_nombre??"Sin especialidad"})</span>
            </div>
            <div class="ml-auto text-right">
              <span class="text-[10px] font-normal opacity-80">${c}</span>
            </div>
          </div>`}}});m.render();const _=document.getElementById("filtro-eventos");_&&_.addEventListener("input",function(){const t=this.value.toLowerCase().trim();m.getEvents().forEach(c=>{const b=c.extendedProps||{};if(b.es_carga||b.es_festivo)return;const h=(c.title||"").toLowerCase(),w=(b.categoria_nombre||"").toLowerCase(),g=(b.especialidad_nombre||"").toLowerCase(),y=!t||h.includes(t)||w.includes(t)||g.includes(t),v=document.querySelector(`[data-event-id="${c.id}"]`);v&&(v.style.display=y?"":"none"),c.setProp("display",y?"auto":"none")})}),console.log("[cal] Configurando event listener para contextmenu...");const f=m.el;return console.log("[cal] Elemento raíz del calendario:",f),console.log("[cal] Agregando event listener de contextmenu al calendario"),f.addEventListener("contextmenu",t=>{if(console.log("[cal] ¡Contextmenu disparado!",t.target),t.target.closest(".fc-event")){console.log("[cal] Es un evento, ignorando");return}t.preventDefault();let n=null,c=null,b=null;const h=t.target.closest("[data-date]");if(h){const g=h.getAttribute("data-date")||"";g.includes("T")?(c=g.slice(0,10),b=g.slice(11,16)):c=g.slice(0,10)}if(!b&&m.view.type==="resourceTimelineDay"){const g=m;if(g.getDate(),typeof g.el.getBoundingClientRect=="function"){g.el.getBoundingClientRect();const y=g.el.querySelector(".fc-timeline-body");if(y){const v=y.getBoundingClientRect(),E=t.clientX-v.left,C=v.width,k=E/C*24,S=Math.floor(k);b=String(S).padStart(2,"0")+":00",console.log("[cal] Hora calculada por posición X:",b)}}}if(!c){console.log("[cal] No se pudo determinar la fecha");return}console.log("[cal] Fecha encontrada:",c,"Hora:",b),console.log("[cal] Elemento clickeado:",t.target),console.log("[cal] Elemento con data-date:",h);const w=f.querySelectorAll(".fc-timeline-lane[data-resource-id]");if(console.log("[cal] Filas de recursos encontradas:",w.length),w.length>0){const g=t.clientY;console.log("[cal] Posición Y del click:",g);for(const y of w){const v=y.getBoundingClientRect();if(console.log("[cal] Examinando lane con resource-id:",y.dataset.resourceId,"top:",v.top,"bottom:",v.bottom),g>=v.top&&g<=v.bottom){n=y.dataset.resourceId,console.log("[cal] ¡ResourceId encontrado por posición Y!:",n);break}}}console.log("[cal] ResourceId final detectado:",n,"Fecha:",c,"Hora:",b),oe(t.clientX,t.clientY,{fechaISO:c,resourceId:n,horaISO:b},m,a)}),console.log("[cal] Event listener de contextmenu agregado correctamente"),m}function M(){const e=document.getElementById("calendario");if(!e||e.getAttribute("data-calendar-type")!=="trabajadores"||!window.AppPlanif)return;if(window.calendarTrabajadores){try{window.calendarTrabajadores.destroy()}catch{}window.calendarTrabajadores=null}const a=me(e);window.calendarTrabajadores=a}function be(){if(window.calendarTrabajadores){try{window.calendarTrabajadores.destroy()}catch{}window.calendarTrabajadores=null}}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",M):M();document.addEventListener("livewire:navigated",M);document.addEventListener("livewire:navigating",be);
