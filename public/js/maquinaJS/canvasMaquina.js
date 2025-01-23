document.addEventListener("DOMContentLoaded", function () {
    const etiquetasConElementos = window.etiquetasConElementos || [];

    etiquetasConElementos.forEach((etiqueta, etiquetaIndex) => {
        etiqueta.elementos.forEach((elemento, elementoIndex) => {
            const canvasId = `canvas-${elemento.id}`;
            const canvas = document.getElementById(canvasId);

            if (canvas && elemento.dimensiones) {
                const instrucciones = generarInstrucciones(
                    elemento.dimensiones
                );

                // Obtener el número de iteración del elemento dentro de su etiqueta
                const loopNumber = `${etiquetaIndex + 1}.${elementoIndex + 1}`;

                ajustarCanvasAlFigura(canvas, instrucciones, loopNumber); // Ajustar tamaño del canvas a la figura
                dibujarFigura(canvas, instrucciones); // Dibujar la figura específica
            }
        });
    });
});

function ajustarCanvasAlFigura(canvas, instrucciones, loopNumber) {
    let x = 0,
        y = 0;
    let angle = 0;
    const points = [
        {
            x,
            y,
        },
    ];

    // Calcular puntos de la figura considerando seno y coseno
    instrucciones.forEach((inst, index) => {
        console.log(
            `-----------------------  Elemento ${loopNumber}, Instrucción ${
                index + 1
            } -----------------`
        );

        // Incrementar el ángulo acumulado en radianes
        if (inst.angulo !== 0) {
            angle += inst.angulo * (Math.PI / 180); // Convertir grados a radianes
            console.log(`Ángulo acumulado (radianes): ${angle}`);
        }

        // Calcular los desplazamientos en X e Y basados en el ángulo actual
        if (inst.longitud !== 0) {
            const deltaX = inst.longitud * Math.cos(angle);
            const deltaY = inst.longitud * Math.sin(angle);
            x += deltaX; // Sumar desplazamiento en X
            y += deltaY; // Sumar desplazamiento en Y
            points.push({
                x,
                y,
            });

            console.log(`Desplazamiento (longitud): ${inst.longitud}`);
            console.log(`Delta X: ${deltaX}`);
            console.log(`Delta Y: ${deltaY}`);
            console.log(`Nueva posición: X=${x}, Y=${y}`);
        }
    });

    // Determinar límites de la figura
    const minX = Math.min(...points.map((p) => p.x));
    const maxX = Math.max(...points.map((p) => p.x));
    const minY = Math.min(...points.map((p) => p.y));
    const maxY = Math.max(...points.map((p) => p.y));

    const figureWidth = maxX - minX;
    const figureHeight = maxY - minY;

    // Configurar dimensiones del canvas
    const margin = 20; // Margen adicional mínimo
    const canvasWidth = Math.max(figureWidth + margin * 2, canvas.clientWidth);
    const canvasHeight = Math.max(
        figureHeight + margin * 2,
        canvas.clientHeight
    );

    canvas.width = canvasWidth;
    canvas.height = canvasHeight;

    // Calcular márgenes para centrar la figura
    const margenLateral = (canvas.width - figureWidth) / 2;
    const margenVertical = (canvas.height - figureHeight) / 2;

    // Guardar desplazamiento para centrar la figura
    canvas.startX = margenLateral - minX;
    canvas.startY = margenVertical - minY;

    // Logs para depuración

    console.log(` - Canvas dimensions: ${canvasWidth}x${canvasHeight}`);
    console.log(`Margins: X=${margenLateral}, Y=${margenVertical}`);
    console.log(`Figure Width: ${figureWidth}, Height: ${figureHeight}`);
    console.log(`Min/Max X: ${minX}, ${maxX} | Min/Max Y: ${minY}, ${maxY}`);
}

function generarInstrucciones(dimensiones) {
    if (!dimensiones || typeof dimensiones !== "string") {
        console.warn(
            "Error: dimensiones no definidas o no es una cadena válida."
        );
        return [];
    }
    const valores = dimensiones.split("\t");
    let longitudes = valores.map((valor) =>
        valor.includes("d") ? 0 : parseFloat(valor)
    );

    const instrucciones = [];
    valores.forEach((valor, index) => {
        if (valor.includes("d")) {
            const angulo = parseFloat(valor.replace("d", ""));
            instrucciones.push({
                longitud: 0,
                angulo,
            });
        } else {
            const longitud = longitudes[index];
            instrucciones.push({
                longitud,
                angulo: 0,
            });
        }
    });

    return instrucciones;
}

function dibujarFigura(canvas, instrucciones) {
    const ctx = canvas.getContext("2d");
    if (!ctx) {
        console.error("Error: No se pudo obtener el contexto 2D del canvas");
        return;
    }

    let x = canvas.startX || 0;
    let y = canvas.startY || 0;
    let angle = 0;

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.beginPath();
    ctx.moveTo(x, y);

    instrucciones.forEach((inst) => {
        if (inst.longitud !== 0) {
            x += inst.longitud * Math.cos(angle);
            y += inst.longitud * Math.sin(angle);
            ctx.lineTo(x, y);
        }
        if (inst.angulo !== 0) {
            angle += inst.angulo * (Math.PI / 180);
        }
    });

    ctx.strokeStyle = "rgba(0, 0, 0, 0.5)";
    ctx.stroke();
}
