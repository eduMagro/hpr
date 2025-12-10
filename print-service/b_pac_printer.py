"""
Módulo de integración con Brother b-PAC SDK
============================================
Usa COM para controlar P-Touch Editor y generar etiquetas QR
"""

import win32com.client
import pythoncom
import logging
import os
import winreg
from typing import List, Dict

logger = logging.getLogger(__name__)
logger.setLevel(logging.DEBUG)


def debug_step(msg: str):
    logger.info(f"[DEBUG b-PAC] {msg}")


def check_bpac_installed() -> bool:
    """Verifica si b-PAC SDK está instalado comprobando el registro de Windows"""
    debug_step("Iniciando check_bpac_installed()")

    try:
        key_paths = [
            r"SOFTWARE\Classes\bpac.Document",
            r"SOFTWARE\WOW6432Node\Classes\bpac.Document",
        ]

        for key_path in key_paths:
            debug_step(f"Probando clave de registro HKLM\\{key_path}")
            try:
                key = winreg.OpenKey(winreg.HKEY_LOCAL_MACHINE, key_path)
                winreg.CloseKey(key)
                debug_step(f"Encontrada clave en HKLM\\{key_path}")
                return True
            except FileNotFoundError:
                debug_step(f"No existe HKLM\\{key_path}")

        debug_step("Probando clave de registro HKCR\\bpac.Document")
        try:
            key = winreg.OpenKey(winreg.HKEY_CLASSES_ROOT, "bpac.Document")
            winreg.CloseKey(key)
            debug_step("Encontrada clave en HKCR\\bpac.Document")
            return True
        except FileNotFoundError:
            debug_step("No existe HKCR\\bpac.Document")

        debug_step("b-PAC NO encontrado en el registro")
        return False

    except Exception as e:
        logger.warning(f"Error verificando b-PAC en registro: {e}")
        return False


class BrotherPrinter:
    """Clase para controlar Brother P-Touch usando b-PAC SDK"""

    def __init__(self):
        self.bpac = None
        self.doc = None

    def _initialize_bpac(self):
        """Inicializa el objeto COM de b-PAC"""
        try:
            debug_step("_initialize_bpac() llamado")

            if self.bpac is None:
                debug_step("self.bpac es None, iniciando proceso de inicialización")

                debug_step("Verificando si b-PAC está instalado en el registro...")
                if not check_bpac_installed():
                    raise Exception(
                        "Brother b-PAC SDK no está instalado. "
                        "Descárgalo desde: https://support.brother.com/g/s/es/dev/en/bpac/download/index.html"
                    )

                debug_step("b-PAC detectado en el registro")

                try:
                    pythoncom.CoInitialize()
                except Exception:
                    pass

                debug_step("Creando objeto COM con Dispatch('bpac.Document')...")
                self.bpac = win32com.client.Dispatch("bpac.Document")
                debug_step("Objeto bpac.Document creado correctamente")

                logger.info("b-PAC SDK inicializado correctamente")

            return True

        except Exception as e:
            logger.error(f"Error inicializando b-PAC SDK: {str(e)}")
            raise

    def test_connection(self) -> Dict:
        """Prueba la conexión con b-PAC SDK"""
        debug_step("test_connection() llamado")

        try:
            self._initialize_bpac()
            debug_step("Inicialización b-PAC OK, liberando COM")

            self.bpac = None
            try:
                pythoncom.CoUninitialize()
            except Exception:
                pass

            return {
                "success": True,
                "message": "Conexión con Brother b-PAC SDK exitosa",
            }

        except Exception as e:
            try:
                pythoncom.CoUninitialize()
            except Exception:
                pass

            return {
                "success": False,
                "error": str(e),
            }

    def print_qr_labels(self, codigos: List[Dict]) -> Dict:
        """Imprime etiquetas QR para una lista de códigos"""
        try:
            self._initialize_bpac()

            template_path = self._get_template_path()
            logger.info(f"Usando plantilla: {template_path}")

            if not self.bpac.Open(template_path):
                raise Exception(f"No se pudo abrir la plantilla: {template_path}")

            printer_name = self.bpac.GetPrinterName()
            logger.info(f"Impresora: {printer_name}")

            cantidad_impresa = 0

            for item in codigos:
                codigo = item.get("codigo", "")
                if not codigo:
                    continue

                obj = self.bpac.GetObject("objText")
                if obj:
                    obj.Text = codigo

                barcode_obj = self.bpac.GetObject("objBarcode")
                if barcode_obj:
                    barcode_obj.Text = codigo

                if self.bpac.StartPrint("", 0):
                    if self.bpac.PrintOut(1, 0):
                        self.bpac.EndPrint()
                        cantidad_impresa += 1
                    else:
                        self.bpac.EndPrint()

            self.bpac.Close()
            self.bpac = None

            try:
                pythoncom.CoUninitialize()
            except Exception:
                pass

            return {
                "success": True,
                "cantidad": cantidad_impresa,
                "total_solicitado": len(codigos),
                "message": f"Se imprimieron {cantidad_impresa} de {len(codigos)} etiquetas",
            }

        except Exception as e:
            if self.bpac:
                try:
                    self.bpac.Close()
                except Exception:
                    pass

            try:
                pythoncom.CoUninitialize()
            except Exception:
                pass

            return {
                "success": False,
                "error": str(e),
            }

    def _get_template_path(self) -> str:
        service_dir = os.path.dirname(os.path.abspath(__file__))
        local_template = os.path.join(service_dir, "etiqueta_qr.lbx")

        if os.path.exists(local_template):
            return local_template

        ptouch_templates = [
            r"C:\Program Files (x86)\Brother\P-touch Editor 6\Templates",
            r"C:\Program Files\Brother\P-touch Editor 6\Templates",
            r"C:\Program Files (x86)\Brother\P-touch Editor 5.4\Templates",
        ]

        for template_dir in ptouch_templates:
            if os.path.exists(template_dir):
                for f in os.listdir(template_dir):
                    if f.lower().endswith(".lbx"):
                        return os.path.join(template_dir, f)

        raise Exception(
            "No se encontró ninguna plantilla .lbx. "
            "Crea 'etiqueta_qr.lbx' con objText y objBarcode."
        )
