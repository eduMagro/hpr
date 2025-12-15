{{--
    Componente compartido: JavaScript del Ghost de Paquete
    Usado en: localizaciones/index y mapa-simple (modal mover paquete)

    IMPORTANTE: Si modificas este código, se actualiza en TODOS los lugares donde se usa.
--}}
@once
<script>
/**
 * Ghost de Paquete - Sistema unificado para posicionar paquetes en el mapa
 */
window.GhostPaquete = (function() {
    'use strict';

    /**
     * Crea una instancia del sistema Ghost
     * @param {Object} config - Configuración
     */
    function GhostPaquete(config) {
        this.grid = config.grid;
        this.viewCols = config.viewCols;
        this.viewRows = config.viewRows;
        this.W = config.W;
        this.H = config.H;
        this.isVertical = config.isVertical;
        this.naveId = config.naveId;
        this.rutaGuardar = config.rutaGuardar || '/localizaciones/paquete';
        this.getCeldaPx = config.getCeldaPx;
        this.onSuccess = config.onSuccess || function() { location.reload(); };
        this.onCancel = config.onCancel || function() {};
        this.confirmBeforeSave = config.confirmBeforeSave !== false;

        // Estado interno
        this.ghost = null;
        this.ghostActions = null;
        this.paqueteMeta = null;
        this.gWidthCells = 1;
        this.gHeightCells = 2;
        this.gX = 1;
        this.gY = 1;

        // Bind methods
        this._onKeyDown = this._onKeyDown.bind(this);
        this._onResize = this._onResize.bind(this);
    }

    // Mapeo de coordenadas vista -> real
    GhostPaquete.prototype.mapViewToReal = function(xv, yv) {
        if (this.isVertical) {
            return { x: xv, y: (this.H - yv + 1) };
        }
        return { x: yv, y: xv };
    };

    // Crear elementos del ghost
    GhostPaquete.prototype._ensureGhost = function() {
        if (this.ghost) return;

        var self = this;

        this.ghost = document.createElement('div');
        this.ghost.id = 'paquete-ghost';
        this.ghost.innerHTML = '<div class="ghost-label"></div>';
        this.grid.appendChild(this.ghost);

        this.ghostActions = document.createElement('div');
        this.ghostActions.id = 'ghost-actions';
        this.ghostActions.innerHTML =
            '<button class="ghost-btn cancel" title="Cancelar (Esc)" aria-label="Cancelar">' +
                '<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                    '<path d="M6 6l12 12M18 6L6 18" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>' +
                '</svg>' +
            '</button>' +
            '<button class="ghost-btn rotate" title="Voltear (R)" aria-label="Voltear">' +
                '<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                    '<path d="M12 4v4l3-2-3-2zM4 12a8 8 0 1 1 8 8" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
                '</svg>' +
            '</button>' +
            '<button class="ghost-btn confirm" title="Asignar aquí (Enter)" aria-label="Asignar">' +
                '<svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                    '<circle cx="12" cy="12" r="9" stroke="#22c55e" stroke-width="2"/>' +
                    '<path d="M8 12l3 3 5-6" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
                '</svg>' +
            '</button>';
        this.grid.appendChild(this.ghostActions);

        // Listeners de botones
        this.ghostActions.querySelector('.cancel').addEventListener('click', function() {
            self.cancelar();
        });
        this.ghostActions.querySelector('.confirm').addEventListener('click', function() {
            self.confirmar();
        });
        this.ghostActions.querySelector('.rotate').addEventListener('click', function() {
            self.rotar();
        });

        // Drag
        this._enableDrag();

        // Keyboard shortcuts
        window.addEventListener('keydown', this._onKeyDown);

        // Resize handler
        window.addEventListener('resize', this._onResize, { passive: true });
    };

    // Layout del ghost
    GhostPaquete.prototype._layoutGhost = function() {
        if (!this.ghost) return;
        var celdaPx = this.getCeldaPx();

        // Mantener dentro de vista
        this.gX = Math.max(1, Math.min(this.viewCols - this.gWidthCells + 1, this.gX));
        this.gY = Math.max(1, Math.min(this.viewRows - this.gHeightCells + 1, this.gY));

        this.ghost.style.left = ((this.gX - 1) * celdaPx) + 'px';
        this.ghost.style.top = ((this.gY - 1) * celdaPx) + 'px';
        this.ghost.style.width = (this.gWidthCells * celdaPx) + 'px';
        this.ghost.style.height = (this.gHeightCells * celdaPx) + 'px';

        var label = this.ghost.querySelector('.ghost-label');
        if (label && this.paqueteMeta) {
            label.textContent = this.paqueteMeta.codigo + ' · ' +
                this.paqueteMeta.longitud.toFixed(2) + ' m · ' +
                this.gWidthCells + '×' + this.gHeightCells + ' celdas';
        }

        if (this.ghostActions) {
            this.ghostActions.style.left = this.ghost.style.left;
            this.ghostActions.style.top = this.ghost.style.top;
            this.ghostActions.style.display = 'flex';
        }
    };

    // Centrar ghost en la vista
    GhostPaquete.prototype._centerGhost = function() {
        this.gX = Math.floor((this.viewCols - this.gWidthCells) / 2) + 1;
        this.gY = Math.floor((this.viewRows - this.gHeightCells) / 2) + 1;
        this._layoutGhost();
    };

    // Calcular tamaño del ghost desde datos del paquete
    GhostPaquete.prototype._setGhostSizeFromPaquete = function(tamano) {
        var CELDA_M = 0.5;
        var anchoCells = Math.max(1, Math.round((tamano.ancho || 1) / CELDA_M));
        var largoCells = Math.max(1, Math.ceil((tamano.longitud || 0) / CELDA_M));
        this.gWidthCells = largoCells;
        this.gHeightCells = anchoCells;
    };

    // Habilitar drag
    GhostPaquete.prototype._enableDrag = function() {
        if (!this.ghost) return;
        var self = this;
        var dragging = false;
        var startMouseX = 0, startMouseY = 0;
        var startGX = 0, startGY = 0;

        function onDown(e) {
            dragging = true;
            self.ghost.classList.add('dragging');
            startMouseX = (e.touches ? e.touches[0].clientX : e.clientX);
            startMouseY = (e.touches ? e.touches[0].clientY : e.clientY);
            startGX = self.gX;
            startGY = self.gY;
            e.preventDefault();
            e.stopPropagation();
            // Marcar globalmente que hay un ghost arrastrándose
            window.__ghostDragging__ = true;
        }

        function onMove(e) {
            if (!dragging) return;
            var celdaPx = self.getCeldaPx();
            var mx = (e.touches ? e.touches[0].clientX : e.clientX);
            var my = (e.touches ? e.touches[0].clientY : e.clientY);
            var dx = mx - startMouseX;
            var dy = my - startMouseY;
            var dCol = Math.round(dx / celdaPx);
            var dRow = Math.round(dy / celdaPx);
            self.gX = startGX + dCol;
            self.gY = startGY + dRow;
            self._layoutGhost();
            e.preventDefault();
            e.stopPropagation();
        }

        function onUp(e) {
            dragging = false;
            window.__ghostDragging__ = false;
            if (self.ghost) self.ghost.classList.remove('dragging');
        }

        this.ghost.addEventListener('mousedown', onDown);
        this.ghost.addEventListener('touchstart', onDown, { passive: false });
        window.addEventListener('mousemove', onMove, { passive: false });
        window.addEventListener('touchmove', onMove, { passive: false });
        window.addEventListener('mouseup', onUp, { passive: true });
        window.addEventListener('touchend', onUp, { passive: true });
    };

    // Keyboard handler
    GhostPaquete.prototype._onKeyDown = function(e) {
        if (!this.ghost) return;
        if (e.key === 'Escape') {
            this.cancelar();
        } else if (e.key.toLowerCase() === 'r') {
            this.rotar();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            this.confirmar();
        }
    };

    // Resize handler
    GhostPaquete.prototype._onResize = function() {
        var self = this;
        if (!this.ghost) return;
        requestAnimationFrame(function() {
            self._layoutGhost();
        });
    };

    // === MÉTODOS PÚBLICOS ===

    /**
     * Crear ghost para un paquete
     */
    GhostPaquete.prototype.crear = function(paqueteData) {
        this.paqueteMeta = {
            codigo: paqueteData.codigo,
            paquete_id: paqueteData.paquete_id,
            longitud: Number(paqueteData.longitud || 0),
            ancho: Number(paqueteData.ancho || 1)
        };

        this._ensureGhost();
        this._setGhostSizeFromPaquete({
            ancho: this.paqueteMeta.ancho,
            longitud: this.paqueteMeta.longitud
        });
        this._centerGhost();
    };

    /**
     * Rotar el ghost 90 grados
     */
    GhostPaquete.prototype.rotar = function() {
        if (!this.ghost) return;

        var cx = this.gX + (this.gWidthCells - 1) / 2;
        var cy = this.gY + (this.gHeightCells - 1) / 2;

        var newW = this.gHeightCells;
        var newH = this.gWidthCells;

        var newGX = Math.round(cx - (newW - 1) / 2);
        var newGY = Math.round(cy - (newH - 1) / 2);

        newGX = Math.max(1, Math.min(this.viewCols - newW + 1, newGX));
        newGY = Math.max(1, Math.min(this.viewRows - newH + 1, newGY));

        this.gWidthCells = newW;
        this.gHeightCells = newH;
        this.gX = newGX;
        this.gY = newGY;

        this._layoutGhost();
    };

    /**
     * Cancelar y eliminar el ghost
     */
    GhostPaquete.prototype.cancelar = function() {
        window.removeEventListener('keydown', this._onKeyDown);
        window.removeEventListener('resize', this._onResize);

        if (this.ghost) {
            this.ghost.remove();
            this.ghost = null;
        }
        if (this.ghostActions) {
            this.ghostActions.remove();
            this.ghostActions = null;
        }
        this.paqueteMeta = null;

        this.onCancel();
    };

    /**
     * Confirmar y guardar la posición
     */
    GhostPaquete.prototype.confirmar = async function() {
        if (!this.paqueteMeta) return;

        var self = this;

        // Coordenadas vista → reales
        var x1v = this.gX, y1v = this.gY;
        var x2v = this.gX + this.gWidthCells - 1;
        var y2v = this.gY + this.gHeightCells - 1;

        var p1 = this.mapViewToReal(x1v, y1v);
        var p2 = this.mapViewToReal(x2v, y2v);

        var x1r = Math.min(p1.x, p2.x);
        var y1r = Math.min(p1.y, p2.y);
        var x2r = Math.max(p1.x, p2.x);
        var y2r = Math.max(p1.y, p2.y);

        // Validar límites
        if (x1r < 1 || y1r < 1 || x2r > this.W || y2r > this.H) {
            alert('Fuera de los límites de la nave.');
            return;
        }

        // Confirmación opcional
        if (this.confirmBeforeSave) {
            var msg = 'Asignar paquete ' + this.paqueteMeta.codigo +
                      ' en (' + x1r + ',' + y1r + ')–(' + x2r + ',' + y2r + ')?';
            if (!confirm(msg)) return;
        }

        // Mostrar loading en botón
        var btnConfirm = this.ghostActions ? this.ghostActions.querySelector('.confirm') : null;
        if (btnConfirm) {
            btnConfirm.disabled = true;
            btnConfirm.innerHTML = '<svg class="icon" style="animation:spin 1s linear infinite" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="4" fill="none"/></svg>';
        }

        var payload = {
            nave_id: this.naveId,
            paquete_id: this.paqueteMeta.paquete_id,
            x1: x1r,
            y1: y1r,
            x2: x2r,
            y2: y2r
        };

        console.log('[GhostPaquete] Guardando ubicación:', payload);
        console.log('[GhostPaquete] Ruta:', this.rutaGuardar);

        try {
            var resp = await fetch(this.rutaGuardar, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(payload)
            });

            var responseText = await resp.text();
            console.log('[GhostPaquete] Respuesta HTTP', resp.status, ':', responseText);

            if (!resp.ok) {
                throw new Error(responseText || 'HTTP ' + resp.status);
            }

            // Éxito
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Ubicación asignada!',
                    text: 'Paquete ' + self.paqueteMeta.codigo + ' ubicado correctamente',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }

            self.cancelar();
            self.onSuccess();

        } catch (err) {
            console.error('[GhostPaquete] Error al guardar ubicación:', err);
            console.error('[GhostPaquete] Payload enviado:', payload);

            var errorMsg = 'No se pudo guardar la localización del paquete.';
            if (err.message) {
                // Intentar parsear si es JSON
                try {
                    var errJson = JSON.parse(err.message);
                    if (errJson.message) errorMsg += '\n' + errJson.message;
                    if (errJson.errors) {
                        Object.keys(errJson.errors).forEach(function(key) {
                            errorMsg += '\n- ' + key + ': ' + errJson.errors[key].join(', ');
                        });
                    }
                } catch(e) {
                    errorMsg += '\n' + err.message;
                }
            }
            alert(errorMsg);

            if (btnConfirm) {
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = '<svg class="icon" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="#22c55e" stroke-width="2"/><path d="M8 12l3 3 5-6" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            }
        }
    };

    /**
     * Verifica si hay un ghost activo
     */
    GhostPaquete.prototype.isActive = function() {
        return this.ghost !== null;
    };

    return GhostPaquete;
})();
</script>
@endonce
