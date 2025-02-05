document.addEventListener("DOMContentLoaded", () => {
    // Altura fija para el canvas (ajústala según necesites)
    const canvasHeight = 400;
    // Márgenes internos para delimitar el área de dibujo
    const marginX = 50; // margen horizontal
    const marginY = 25; // margen vertical
  
    const elementos = @json($elementosAgrupadosScript);
    console.log("Elementos agrupados:", elementos);
  
    elementos.forEach((grupo) => {
      console.log("Procesando etiqueta:", grupo.etiqueta);
      const canvas = document.getElementById(`canvas-etiqueta-${grupo.etiqueta?.id}`);
      if (!canvas) {
        console.warn(`Canvas no encontrado para etiqueta ID: ${grupo.etiqueta?.id}`);
        return;
      }
      const parent = canvas.parentElement;
      // Ajusta el ancho del canvas al ancho del div padre
      const canvasWidth = parent.clientWidth;
      canvas.width  = canvasWidth;
      canvas.height = canvasHeight;
  
      const ctx = canvas.getContext("2d");
      ctx.clearRect(0, 0, canvas.width, canvas.height);
  
      // Distribuir horizontalmente cada figura en un “slot”
      const numElementos = grupo.elementos.length;
      const availableSlotWidth = (canvasWidth - 2 * marginX) / numElementos;
      const availableHeight    = canvasHeight - 2 * marginY;
  
      grupo.elementos.forEach((elemento, index) => {
        console.log(`Dibujando elemento #${index + 1}:`, elemento);
  
        // Extraer longitudes y ángulos del string (por ejemplo: "400" o "15 90d 85 ..." )
        const dimensionesStr = elemento.dimensiones || "";
        const { longitudes, angulos } = extraerDimensiones(dimensionesStr);
  
        // Calcular el centro del slot asignado a este elemento
        const centerX = marginX + availableSlotWidth * (index + 0.5);
        const centerY = marginY + availableHeight / 2;
  
        if (longitudes.length === 1) {
          // CASO: ÚNICA DIMENSIÓN (ahora se muestran acotaciones)
          const length = longitudes[0];
          // Se usa el valor absoluto para escalar
          const scale = availableHeight / Math.abs(length);
          const lineLength = Math.abs(length) * scale;
  
          // Dibujar la línea vertical (en azul)
          ctx.strokeStyle = "blue";
          ctx.lineWidth   = 2;
          ctx.beginPath();
          // La línea se dibuja centrada en el slot verticalmente
          ctx.moveTo(centerX, centerY - lineLength / 2);
          ctx.lineTo(centerX, centerY + lineLength / 2);
          ctx.stroke();
  
          // Dibujar acotación para el segmento
          // Definir los puntos inicial y final de la línea (en coordenadas del canvas)
          const pt1 = { x: centerX, y: centerY - lineLength / 2 };
          const pt2 = { x: centerX, y: centerY + lineLength / 2 };
          // Calcular el punto medio
          const midX = (pt1.x + pt2.x) / 2;
          const midY = (pt1.y + pt2.y) / 2;
          // Calcular el ángulo del segmento (para línea vertical, es PI/2)
          const angle = Math.atan2(pt2.y - pt1.y, pt2.x - pt1.x); // ≈ PI/2
          // Desplazamiento perpendicular de 10 píxeles para ubicar la etiqueta
          const offset = 10;
          const offsetX = offset * Math.cos(angle - Math.PI / 2);
          const offsetY = offset * Math.sin(angle - Math.PI / 2);
  
          // Dibujar la línea de acotación (en rojo)
          ctx.strokeStyle = "red";
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(pt1.x, pt1.y);
          ctx.lineTo(pt2.x, pt2.y);
          ctx.stroke();
  
          // Dibujar la etiqueta con la longitud
          ctx.font = "12px Arial";
          ctx.fillStyle = "red";
          ctx.fillText(length.toString(), midX + offsetX, midY + offsetY);
  
          // Dibujar el ID del elemento
          ctx.font = "14px Arial";
          ctx.fillStyle = "black";
          ctx.fillText(`#${elemento.id}`, centerX - 10, centerY - lineLength / 2 - 10);
  
        } else {
          // CASO: FIGURA COMPUESTA (varias dimensiones) → se muestran acotaciones
  
          // 1. Calcular el bounding box de la figura en coordenadas locales (sistema "natural", iniciando en 0,0)
          const { minX, maxX, minY, maxY } = calcularBoundingBox(longitudes, angulos);
          const figWidth  = maxX - minX;
          const figHeight = maxY - minY;
  
          // 2. Si la parte más larga es el ancho, se rota la figura 90° para que la dimensión mayor quede en el eje Y.
          const rotate = (figWidth > figHeight);
          // Las dimensiones "efectivas" serán las originales o intercambiadas en caso de rotación
          const effectiveWidth  = rotate ? figHeight : figWidth;
          const effectiveHeight = rotate ? figWidth  : figHeight;
          // Centro de la figura en sus coordenadas locales
          const figCenterX = (minX + maxX) / 2;
          const figCenterY = (minY + maxY) / 2;
  
          // 3. Calcular la escala para que la figura se ajuste en el slot asignado
          const scale = Math.min( availableSlotWidth / effectiveWidth, availableHeight / effectiveHeight );
          console.log(`Elemento ID ${elemento.id}: figWidth=${figWidth}, figHeight=${figHeight}, rotate=${rotate}, scale=${scale}`);
  
          // 4. Aplicar transformaciones: trasladar al centro del slot, rotar (si es necesario), escalar y centrar la figura
          ctx.save();
            ctx.translate(centerX, centerY);
            if (rotate) {
              ctx.rotate(Math.PI / 2);
            }
            ctx.scale(scale, scale);
            ctx.translate(-figCenterX, -figCenterY);
  
            // Para mantener el grosor constante, compensamos la escala en el lineWidth:
            ctx.strokeStyle = "blue";
            ctx.lineWidth   = 2 / scale; // (2/scale)*scale = 2 píxeles efectivos
            ctx.lineCap     = 'round';
            ctx.lineJoin    = 'round';
  
            // Dibujar la figura en su sistema de coordenadas natural
            dibujarFiguraPath(ctx, longitudes, angulos);
          ctx.restore();
  
          // 5. Calcular y dibujar las acotaciones de cada segmento
  
          // Primero, computamos la lista de puntos en coordenadas locales de la figura.
          const pointsLocal = computePoints(longitudes, angulos);
          // Transformamos cada punto a coordenadas del canvas usando la misma transformación:
          const pointsCanvas = pointsLocal.map(pt =>
            transformPoint(pt.x, pt.y, centerX, centerY, scale, rotate, figCenterX, figCenterY)
          );
  
          // Para cada segmento, dibujamos una línea de acotación (en rojo) y la etiqueta con la longitud
          for (let i = 0; i < pointsCanvas.length - 1; i++) {
            const pt1 = pointsCanvas[i];
            const pt2 = pointsCanvas[i + 1];
            // Calcular el punto medio del segmento
            const midX = (pt1.x + pt2.x) / 2;
            const midY = (pt1.y + pt2.y) / 2;
            // Calcular el ángulo del segmento en las coordenadas locales
            const dxLocal = pointsLocal[i + 1].x - pointsLocal[i].x;
            const dyLocal = pointsLocal[i + 1].y - pointsLocal[i].y;
            const angleLocal = Math.atan2(dyLocal, dxLocal);
            // Ajustamos el ángulo para las coordenadas del canvas (si se aplicó rotación)
            const angleCanvas = angleLocal + (rotate ? Math.PI / 2 : 0);
            // Desplazamiento perpendicular de 10 píxeles para ubicar la etiqueta
            const offset = 10;
            const offsetX = offset * Math.cos(angleCanvas - Math.PI / 2);
            const offsetY = offset * Math.sin(angleCanvas - Math.PI / 2);
  
            // Dibujar la línea de acotación en color rojo y grosor 1
            ctx.strokeStyle = "red";
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(pt1.x, pt1.y);
            ctx.lineTo(pt2.x, pt2.y);
            ctx.stroke();
  
            // Dibujar el texto con la longitud del segmento (usando el valor original)
            ctx.font = "12px Arial";
            ctx.fillStyle = "red";
            const text = longitudes[i].toString();
            ctx.fillText(text, midX + offsetX, midY + offsetY);
          }
  
          // Dibujar el ID del elemento en el centro del slot
          ctx.font = "14px Arial";
          ctx.fillStyle = "black";
          ctx.fillText(`#${elemento.id}`, centerX - 10, centerY - 10);
        }
      });
  
      console.log(`Finalizado dibujo para etiqueta ID: ${grupo.etiqueta?.id}`);
    });
  
    /* Función que calcula el bounding box de la figura en coordenadas locales.
       Se recorre cada segmento acumulando posiciones (origen en 0,0). */
    function calcularBoundingBox(longitudes, angulos) {
      let currentX = 0, currentY = 0, currentAngle = 0;
      let minX = 0, maxX = 0, minY = 0, maxY = 0;
      longitudes.forEach((longitud, i) => {
        const angleIncrement = angulos[i] || 0;
        const newX = currentX + longitud * Math.cos(currentAngle * Math.PI / 180);
        const newY = currentY + longitud * Math.sin(currentAngle * Math.PI / 180);
        minX = Math.min(minX, newX);
        maxX = Math.max(maxX, newX);
        minY = Math.min(minY, newY);
        maxY = Math.max(maxY, newY);
        currentX = newX;
        currentY = newY;
        currentAngle += angleIncrement;
      });
      return { minX, maxX, minY, maxY };
    }
  
    /* Función que dibuja la figura (el path) en el canvas usando el sistema de coordenadas natural.
       Se asume que ya se aplicaron las transformaciones (traslación, rotación, escala). */
    function dibujarFiguraPath(ctx, longitudes, angulos) {
      ctx.beginPath();
      let currentX = 0, currentY = 0, currentAngle = 0;
      ctx.moveTo(currentX, currentY);
      longitudes.forEach((longitud, i) => {
        const angleIncrement = angulos[i] || 0;
        const newX = currentX + longitud * Math.cos(currentAngle * Math.PI / 180);
        const newY = currentY + longitud * Math.sin(currentAngle * Math.PI / 180);
        ctx.lineTo(newX, newY);
        currentX = newX;
        currentY = newY;
        currentAngle += angleIncrement;
      });
      ctx.stroke();
    }
  
    /* Función que extrae longitudes y ángulos a partir de un string de dimensiones.
       Se asume que las longitudes vienen sin sufijo y que los ángulos llevan la "d" (ej.: "15 90d 85 ..."). */
    function extraerDimensiones(dimensiones) {
      const longitudes = [];
      const angulos     = [];
      const tokens = dimensiones.split(/\s+/).filter(token => token.length > 0);
      tokens.forEach(token => {
        if (token.includes('d')) {
          const num = parseFloat(token.replace('d', ''));
          angulos.push(isNaN(num) ? 0 : num);
        } else {
          const num = parseFloat(token);
          longitudes.push(isNaN(num) ? 50 : num);
        }
      });
      return { longitudes, angulos };
    }
  
    /* Función que computa la lista de puntos (en coordenadas locales) de la figura.
       Comienza en (0,0) y acumula la posición de cada segmento. */
    function computePoints(longitudes, angulos) {
      const points = [];
      let cx = 0, cy = 0, ca = 0;
      points.push({ x: cx, y: cy });
      for (let i = 0; i < longitudes.length; i++) {
        const newX = cx + longitudes[i] * Math.cos(ca * Math.PI / 180);
        const newY = cy + longitudes[i] * Math.sin(ca * Math.PI / 180);
        points.push({ x: newX, y: newY });
        cx = newX;
        cy = newY;
        ca += angulos[i] || 0;
      }
      return points;
    }
  
    /* Función que transforma un punto de coordenadas locales a coordenadas del canvas,
       usando la misma transformación aplicada (traslación, escala y rotación). */
    function transformPoint(x, y, centerX, centerY, scale, rotate, figCenterX, figCenterY) {
      // Trasladar el punto para centrar la figura en sus coordenadas locales
      let dx = x - figCenterX;
      let dy = y - figCenterY;
      // Si se aplicó rotación (90°), rotar el punto
      if (rotate) {
        const tx = -dy;
        const ty = dx;
        dx = tx;
        dy = ty;
      }
      // Aplicar la escala y trasladar al centro del slot en el canvas
      return { x: centerX + scale * dx, y: centerY + scale * dy };
    }
  });