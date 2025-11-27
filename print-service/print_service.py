#!/usr/bin/env python3
"""
Servicio Local de Impresión P-Touch
====================================
Este servicio escucha en localhost:8765 y recibe peticiones
para imprimir etiquetas QR con códigos de productos usando Brother b-PAC SDK.
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import logging
import sys
from b_pac_printer import BrotherPrinter

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('print_service.log'),
        logging.StreamHandler(sys.stdout)
    ]
)

logger = logging.getLogger(__name__)

# Crear aplicación Flask
app = Flask(__name__)
CORS(app)  # Permitir peticiones desde el navegador

# Instancia del printer
printer = BrotherPrinter()


@app.route('/')
def index():
    """Endpoint de health check"""
    return jsonify({
        'status': 'running',
        'service': 'Brother P-Touch Print Service',
        'version': '1.0.0'
    })


@app.route('/print', methods=['POST', 'OPTIONS'])
def print_labels():
    """
    Endpoint para imprimir etiquetas QR

    Espera JSON con formato:
    {
        "codigos": [
            {"codigo": "MP250101"},
            {"codigo": "MP250102"},
            ...
        ]
    }
    """
    if request.method == 'OPTIONS':
        # Manejar preflight CORS
        return '', 204

    try:
        data = request.get_json()

        if not data or 'codigos' not in data:
            return jsonify({
                'success': False,
                'error': 'Formato de datos inválido. Se espera: {"codigos": [...]}'
            }), 400

        codigos = data['codigos']

        if not isinstance(codigos, list) or len(codigos) == 0:
            return jsonify({
                'success': False,
                'error': 'La lista de códigos está vacía o no es válida'
            }), 400

        logger.info(f"Recibida solicitud de impresión para {len(codigos)} etiquetas")

        # Imprimir etiquetas
        resultado = printer.print_qr_labels(codigos)

        if resultado['success']:
            logger.info(f"Impresión completada: {resultado['cantidad']} etiquetas")
            return jsonify(resultado)
        else:
            logger.error(f"Error en impresión: {resultado['error']}")
            return jsonify(resultado), 500

    except Exception as e:
        logger.error(f"Error procesando solicitud: {str(e)}", exc_info=True)
        return jsonify({
            'success': False,
            'error': f'Error interno del servidor: {str(e)}'
        }), 500


@app.route('/test', methods=['GET'])
def test_printer():
    """Endpoint para probar la conexión con la impresora"""
    try:
        resultado = printer.test_connection()
        return jsonify(resultado)
    except Exception as e:
        logger.error(f"Error en test de impresora: {str(e)}", exc_info=True)
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500


if __name__ == '__main__':
    logger.info("=" * 60)
    logger.info("Iniciando Servicio de Impresión P-Touch")
    logger.info("Escuchando en http://localhost:8765")
    logger.info("Presiona Ctrl+C para detener el servicio")
    logger.info("=" * 60)

    # Iniciar servidor Flask
    app.run(
        host='127.0.0.1',
        port=8765,
        debug=False,
        threaded=True
    )
