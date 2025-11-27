"""
Módulo de integración con Brother b-PAC SDK
============================================
Usa COM para controlar P-Touch Editor y generar etiquetas QR
"""

import win32com.client
import pythoncom
import logging
import os
from typing import List, Dict

logger = logging.getLogger(__name__)


class BrotherPrinter:
    """Clase para controlar Brother P-Touch usando b-PAC SDK"""

    def __init__(self):
        """Inicializa la conexión con b-PAC SDK"""
        self.bpac = None
        self.doc = None

    def _initialize_bpac(self):
        """Inicializa el objeto COM de b-PAC"""
        try:
            if self.bpac is None:
                logger.info("Inicializando Brother b-PAC SDK...")
                # Inicializar COM en este thread (necesario para Flask multi-threaded)
                pythoncom.CoInitialize()
                logger.info("COM inicializado, creando objeto bpac.Document...")
                self.bpac = win32com.client.Dispatch("bpac.Document")
                logger.info("b-PAC SDK inicializado correctamente")
            return True
        except Exception as e:
            logger.error(f"Error inicializando b-PAC SDK: {str(e)}")
            raise Exception(
                f"No se pudo inicializar Brother b-PAC SDK. "
                f"Asegúrate de que esté instalado correctamente. Error: {str(e)}"
            )

    def test_connection(self) -> Dict:
        """Prueba la conexión con b-PAC SDK"""
        try:
            self._initialize_bpac()
            # Liberar recursos después de probar
            self.bpac = None
            pythoncom.CoUninitialize()
            return {
                'success': True,
                'message': 'Conexión con Brother b-PAC SDK exitosa'
            }
        except Exception as e:
            try:
                pythoncom.CoUninitialize()
            except:
                pass
            return {
                'success': False,
                'error': str(e)
            }

    def print_qr_labels(self, codigos: List[Dict]) -> Dict:
        """
        Imprime etiquetas QR para una lista de códigos

        Args:
            codigos: Lista de diccionarios con formato [{"codigo": "MP250101"}, ...]

        Returns:
            Dict con resultado de la operación
        """
        try:
            self._initialize_bpac()

            # Buscar plantilla en la carpeta del servicio o usar plantilla del sistema
            template_path = self._get_template_path()
            logger.info(f"Usando plantilla: {template_path}")

            # Abrir plantilla
            if not self.bpac.Open(template_path):
                raise Exception(f"No se pudo abrir la plantilla: {template_path}")

            logger.info("Plantilla abierta correctamente")

            # Obtener nombre de la impresora
            printer_name = self.bpac.GetPrinterName()
            logger.info(f"Impresora: {printer_name}")

            cantidad_impresa = 0

            for item in codigos:
                codigo = item.get('codigo', '')
                if not codigo:
                    logger.warning("Código vacío encontrado, omitiendo...")
                    continue

                logger.info(f"Procesando código: {codigo}")

                try:
                    # Establecer el texto del objeto (buscar objeto de texto en la plantilla)
                    obj = self.bpac.GetObject("objText")
                    if obj:
                        obj.Text = codigo

                    # También intentar con el objeto de código de barras/QR
                    barcode_obj = self.bpac.GetObject("objBarcode")
                    if barcode_obj:
                        barcode_obj.Text = codigo

                    # Imprimir la etiqueta
                    if self.bpac.StartPrint("", 0):  # Usar impresora predeterminada
                        if self.bpac.PrintOut(1, 0):  # 1 copia
                            self.bpac.EndPrint()
                            logger.info(f"Etiqueta impresa: {codigo}")
                            cantidad_impresa += 1
                        else:
                            self.bpac.EndPrint()
                            logger.error(f"Error en PrintOut para: {codigo}")
                    else:
                        logger.error(f"Error en StartPrint para: {codigo}")

                except Exception as e:
                    logger.error(f"Error procesando código {codigo}: {str(e)}")
                    continue

            # Cerrar documento
            self.bpac.Close()
            self.bpac = None
            # Liberar recursos COM
            pythoncom.CoUninitialize()

            return {
                'success': True,
                'cantidad': cantidad_impresa,
                'total_solicitado': len(codigos),
                'message': f'Se imprimieron {cantidad_impresa} de {len(codigos)} etiquetas'
            }

        except Exception as e:
            logger.error(f"Error en print_qr_labels: {str(e)}", exc_info=True)
            if self.bpac:
                try:
                    self.bpac.Close()
                    self.bpac = None
                    pythoncom.CoUninitialize()
                except:
                    pass
            return {
                'success': False,
                'error': str(e)
            }

    def _get_template_path(self) -> str:
        """Obtiene la ruta de la plantilla a usar"""
        # Buscar plantilla en la carpeta del servicio
        service_dir = os.path.dirname(os.path.abspath(__file__))
        local_template = os.path.join(service_dir, "etiqueta_qr.lbx")

        if os.path.exists(local_template):
            return local_template

        # Buscar en carpeta de plantillas de P-Touch Editor
        ptouch_templates = [
            r"C:\Program Files (x86)\Brother\P-touch Editor 6\Templates",
            r"C:\Program Files\Brother\P-touch Editor 6\Templates",
            r"C:\Program Files (x86)\Brother\P-touch Editor 5.4\Templates",
        ]

        for template_dir in ptouch_templates:
            if os.path.exists(template_dir):
                # Buscar cualquier plantilla .lbx
                for f in os.listdir(template_dir):
                    if f.endswith('.lbx'):
                        return os.path.join(template_dir, f)

        # Si no hay plantilla, usar string vacío (intentará crear una nueva)
        raise Exception(
            "No se encontró plantilla .lbx. Por favor crea una plantilla llamada 'etiqueta_qr.lbx' "
            f"en la carpeta {service_dir} usando P-Touch Editor con un objeto de texto llamado 'objText' "
            "y un objeto de código QR llamado 'objBarcode'."
        )

    def _limpiar_etiqueta(self):
        """Elimina todos los objetos de la etiqueta actual"""
        try:
            count = self.bpac.GetObjectCount()
            for i in range(count - 1, -1, -1):
                obj_name = self.bpac.GetObjectName(i)
                self.bpac.DeleteObject(obj_name)
        except Exception as e:
            logger.warning(f"Error limpiando etiqueta: {str(e)}")

    def _agregar_qr_code(self, codigo: str):
        """Agrega un código QR a la etiqueta"""
        try:
            # Crear objeto QR
            qr_name = "QRCode"

            # Agregar objeto de barcode (QR es tipo de barcode en b-PAC)
            # Parámetros: nombre, tipo (92 = QR Code), posición X, Y, ancho, alto
            obj = self.bpac.GetObject(qr_name)

            if obj is None:
                # Si no existe, crearlo
                # Tipo 92 = QR Code en b-PAC
                # Posición centrada: X=10mm, Y=5mm, Tamaño=25x25mm
                self.bpac.AddBarcode(
                    qr_name,  # Nombre del objeto
                    92,       # Tipo: QR Code
                    10.0,     # Posición X (mm)
                    5.0,      # Posición Y (mm)
                    25.0,     # Ancho (mm)
                    25.0      # Alto (mm)
                )
                obj = self.bpac.GetObject(qr_name)

            if obj:
                # Establecer los datos del QR
                obj.Text = codigo
                return obj

            return None

        except Exception as e:
            logger.error(f"Error agregando QR: {str(e)}")
            return None

    def _agregar_texto_codigo(self, codigo: str):
        """Agrega el texto del código debajo del QR"""
        try:
            # Crear objeto de texto
            texto_name = "TextoCodigo"

            obj = self.bpac.GetObject(texto_name)

            if obj is None:
                # Si no existe, crearlo
                # Posición debajo del QR: X=10mm, Y=32mm
                self.bpac.AddText(
                    texto_name,   # Nombre del objeto
                    10.0,         # Posición X (mm)
                    32.0,         # Posición Y (mm)
                    70.0,         # Ancho (mm)
                    8.0           # Alto (mm)
                )
                obj = self.bpac.GetObject(texto_name)

            if obj:
                # Establecer el texto
                obj.Text = codigo

                # Configurar formato del texto
                obj.HorizontalAlignment = 1  # 1 = Centrado
                obj.FontName = "Arial"
                obj.FontSize = 8
                obj.FontBold = True

                return obj

            return None

        except Exception as e:
            logger.error(f"Error agregando texto: {str(e)}")
            return None
