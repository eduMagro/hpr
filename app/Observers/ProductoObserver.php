<?php

namespace App\Observers;

use App\Models\Producto;
use Illuminate\Support\Facades\Log;

class ProductoObserver
{
    /**
     * Handle the Producto "created" event.
     * Recalcula cantidad_recepcionada al crear un producto.
     */
    public function created(Producto $producto): void
    {
        $this->recalcularLineaPedido($producto);
    }

    /**
     * Handle the Producto "updated" event.
     * Recalcula cantidad_recepcionada si cambió el peso_inicial.
     */
    public function updated(Producto $producto): void
    {
        // Solo recalcular si cambió el peso_inicial
        if ($producto->wasChanged('peso_inicial')) {
            $this->recalcularLineaPedido($producto);
        }
    }

    /**
     * Handle the Producto "deleted" event.
     * Recalcula cantidad_recepcionada al eliminar un producto.
     */
    public function deleted(Producto $producto): void
    {
        $this->recalcularLineaPedido($producto);
    }

    /**
     * Handle the Producto "restored" event.
     * Recalcula cantidad_recepcionada al restaurar un producto desde la papelera.
     */
    public function restored(Producto $producto): void
    {
        $this->recalcularLineaPedido($producto);
    }

    /**
     * Recalcula la cantidad recepcionada de la línea de pedido asociada.
     */
    private function recalcularLineaPedido(Producto $producto): void
    {
        // Obtener la entrada del producto
        $entrada = $producto->entrada;

        if (!$entrada) {
            return;
        }

        // Obtener la línea de pedido de la entrada
        $pedidoProducto = $entrada->pedidoProducto;

        if (!$pedidoProducto) {
            return;
        }

        // Recalcular
        $pedidoProducto->recalcularCantidadRecepcionada();

        Log::info("Recalculada cantidad_recepcionada para línea de pedido #{$pedidoProducto->id}: {$pedidoProducto->cantidad_recepcionada} kg");
    }
}
