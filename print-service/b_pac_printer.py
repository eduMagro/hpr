"""
Módulo de integración con Brother b-PAC SDK
============================================
Usa COM para controlar P-Touch Editor y generar etiquetas QR
"""

import win32com.client
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
            return {
                'success': True,
                'message': 'Conexión con Brother b-PAC SDK exitosa'
            }
        except Exception as e:
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

            # Crear nueva etiqueta (tamaño 29mm x 90mm - ajusta según tu impresora)
            logger.info("Creando nueva etiqueta...")

            # Usar plantilla en blanco de 29x90mm (tamaño común para P-Touch)
            # Si tienes una plantilla personalizada, cambia esta línea
            if not self.bpac.Open(""):  # "" = etiqueta en blanco
                raise Exception("No se pudo crear una nueva etiqueta")

            # Configurar tamaño de etiqueta (en milímetros)
            # Ajusta estos valores según tu modelo de impresora
            self.bpac.SetMediaById(self.bpac.GetMediaId(), True)

            cantidad_impresa = 0

            for item in codigos:
                codigo = item.get('codigo', '')
                if not codigo:
                    logger.warning("Código vacío encontrado, omitiendo...")
                    continue

                logger.info(f"Procesando código: {codigo}")

                try:
                    # Limpiar objetos anteriores si existen
                    self._limpiar_etiqueta()

                    # Agregar objeto QR
                    qr_obj = self._agregar_qr_code(codigo)
                    if qr_obj:
                        logger.info(f"QR generado para: {codigo}")

                    # Agregar texto del código debajo del QR
                    texto_obj = self._agregar_texto_codigo(codigo)
                    if texto_obj:
                        logger.info(f"Texto agregado para: {codigo}")

                    # Imprimir la etiqueta
                    if self.bpac.DoPrint(1, 0):  # Imprimir 1 copia, sin opciones
                        logger.info(f"Etiqueta impresa: {codigo}")
                        cantidad_impresa += 1
                    else:
                        logger.error(f"Error imprimiendo etiqueta: {codigo}")

                except Exception as e:
                    logger.error(f"Error procesando código {codigo}: {str(e)}")
                    continue

            # Cerrar documento
            self.bpac.Close()

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
                except:
                    pass
            return {
                'success': False,
                'error': str(e)
            }

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
