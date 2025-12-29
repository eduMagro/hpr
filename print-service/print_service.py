#!/usr/bin/env python3
"""
Servicio Local de Impresión P-Touch
====================================
Servicio Flask que recibe peticiones en localhost:8765
para probar conexión e imprimir etiquetas usando Brother b-PAC.
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import logging
import sys
import subprocess
import tempfile
import os

from b_pac_printer import BrotherPrinter

# ------------------------------------------------------
# CONFIGURACIÓN DE LOGS
# ------------------------------------------------------

logging.basicConfig(
    level=logging.DEBUG,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
    handlers=[
        logging.FileHandler("print_service.log", encoding="utf-8"),
        logging.StreamHandler(sys.stdout),
    ],
)

logger = logging.getLogger(__name__)

# ------------------------------------------------------
# APP FLASK
# ------------------------------------------------------

app = Flask(__name__)
CORS(app)

# ------------------------------------------------------
# HEALTH CHECK
# ------------------------------------------------------

@app.route("/", methods=["GET"])
def index():
    """Endpoint de estado"""
    return jsonify({
        "status": "running",
        "service": "Brother P-Touch Print Service",
        "version": "1.0.0"
    })

# ------------------------------------------------------
# TEST DE BROTHER EN SUBPROCESO (ANTI-BLOQUEOS COM)
# ------------------------------------------------------

@app.route("/test", methods=["GET"])
def test_printer():
    """
    Prueba la conexión con Brother b-PAC en un PROCESO AISLADO
    para evitar bloqueos de COM con Flask.
    """
    try:
        logger.info("========= [/test] =========")
        logger.info("Lanzando prueba Brother en proceso externo...")

        test_code = r"""
import win32com.client
import pythoncom

print("Inicializando COM...")
pythoncom.CoInitialize()

print("Creando objeto bpac.Document...")
bpac = win32com.client.Dispatch("bpac.Document")

print("OBJETO CREADO CORRECTAMENTE")
"""

        with tempfile.NamedTemporaryFile(delete=False, suffix=".py", mode="w", encoding="utf-8") as f:
            f.write(test_code)
            test_file = f.name

        result = subprocess.run(
            [sys.executable, test_file],
            capture_output=True,
            text=True,
            timeout=20
        )

        os.unlink(test_file)

        salida = result.stdout.strip()
        error = result.stderr.strip()

        logger.info(f"Salida test Brother:\n{salida}")
        if error:
            logger.error(f"Error test Brother:\n{error}")

        if "OBJETO CREADO CORRECTAMENTE" in salida:
            return jsonify({
                "success": True,
                "message": "Conexión con Brother b-PAC SDK exitosa",
                "output": salida
            })

        return jsonify({
            "success": False,
            "error": "b-PAC no respondió correctamente",
            "stdout": salida,
            "stderr": error
        }), 500

    except subprocess.TimeoutExpired:
        return jsonify({
            "success": False,
            "error": "Timeout: Brother COM no respondió en tiempo"
        }), 504

    except Exception as e:
        logger.exception("Error en /test")
        return jsonify({
            "success": False,
            "error": str(e)
        }), 500

# ------------------------------------------------------
# IMPRESIÓN REAL
# ------------------------------------------------------

@app.route("/print", methods=["POST", "OPTIONS"])
def print_labels():
    """
    Endpoint para imprimir etiquetas QR

    Espera JSON:
    {
        "codigos": [
            {"codigo": "MP250101"},
            {"codigo": "MP250102"}
        ]
    }
    """
    if request.method == "OPTIONS":
        return "", 204

    try:
        data = request.get_json()

        if not data or "codigos" not in data:
            return jsonify({
                "success": False,
                "error": "Formato inválido. Se espera: {'codigos': [...]}"
            }), 400

        codigos = data["codigos"]

        if not isinstance(codigos, list) or len(codigos) == 0:
            return jsonify({
                "success": False,
                "error": "La lista de códigos está vacía o no es válida"
            }), 400

        logger.info(f"Solicitud de impresión para {len(codigos)} etiquetas")

        printer = BrotherPrinter()
        resultado = printer.print_qr_labels(codigos)

        if resultado.get("success"):
            logger.info(f"Impresión completada: {resultado.get('cantidad')} etiquetas")
            return jsonify(resultado)

        logger.error(f"Error en impresión: {resultado.get('error')}")
        return jsonify(resultado), 500

    except Exception as e:
        logger.exception("Error procesando solicitud /print")
        return jsonify({
            "success": False,
            "error": f"Error interno del servidor: {str(e)}"
        }), 500

# ------------------------------------------------------
# ARRANQUE DEL SERVIDOR (MONOHILO PARA MAYOR ESTABILIDAD)
# ------------------------------------------------------

if __name__ == "__main__":
    logger.info("=" * 60)
    logger.info("Iniciando Servicio de Impresión P-Touch")
    logger.info("Escuchando en http://localhost:8765")
    logger.info("Presiona Ctrl+C para detener el servicio")
    logger.info("=" * 60)

    app.run(
        host="127.0.0.1",
        port=8765,
        debug=False,
        threaded=False   # 👈 CRÍTICO PARA EVITAR BLOQUEOS DE COM
    )
