<?php
/**
 * Script para importar planillas con información de ensamblaje desde FerraWin
 * Uso: php import_ensamblaje_ferrawin.php [cantidad]
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Planilla;
use App\Models\PlanillaEntidad;
use App\Models\Elemento;

// Configuración FerraWin
$ferrawinConfig = [
    'host' => '192.168.0.7',
    'port' => '1433',
    'database' => 'FERRAWIN',
    'username' => 'sa',
    'password' => 'Ferrawin73',
];

$cantidad = (int)($argv[1] ?? 5);

echo "=== Importando {$cantidad} planillas con ensamblaje desde FerraWin ===\n\n";

try {
    // Conectar a FerraWin (TrustServerCertificate para evitar error SSL)
    $dsn = "sqlsrv:Server={$ferrawinConfig['host']},{$ferrawinConfig['port']};Database={$ferrawinConfig['database']};TrustServerCertificate=yes";
    $pdo = new PDO($dsn, $ferrawinConfig['username'], $ferrawinConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);
    echo "[OK] Conectado a FerraWin\n\n";

    // Buscar planillas que tengan datos en PROD_DETO (ensamblajes)
    $sql = "
        SELECT DISTINCT TOP ({$cantidad})
            oh.ZCONTA + '-' + oh.ZCODIGO as codigo,
            oh.ZNOMBRE as descripcion,
            oh.ZFECHA as fecha,
            p.ZCODCLI as codigo_cliente,
            p.ZCLIENTE as nombre_cliente,
            p.ZCODIGO as codigo_obra,
            p.ZNOMBRE as nombre_obra
        FROM ORD_HEAD oh
        INNER JOIN PROD_DETO pd ON oh.ZCONTA = pd.ZCONTA AND oh.ZCODIGO = pd.ZCODPLA
        LEFT JOIN PROJECT p ON oh.ZCODOBRA = p.ZCODIGO
        WHERE oh.ZFECHA >= DATEADD(year, -1, GETDATE())
        ORDER BY oh.ZFECHA DESC
    ";

    $stmt = $pdo->query($sql);
    $planillas = $stmt->fetchAll();

    if (empty($planillas)) {
        echo "[!] No se encontraron planillas con datos de ensamblaje\n";
        exit(1);
    }

    echo "Encontradas " . count($planillas) . " planillas con ensamblaje:\n";
    foreach ($planillas as $p) {
        echo "  - {$p->codigo}: {$p->descripcion}\n";
    }
    echo "\n";

    $importadas = 0;

    foreach ($planillas as $planillaData) {
        echo "Procesando {$planillaData->codigo}...\n";

        [$zconta, $zcodigo] = explode('-', $planillaData->codigo, 2);

        // Obtener entidades con composición (ORD_DET + PROD_DETI)
        $sqlEntidades = "
            SELECT
                od.ZCODLIN,
                od.ZORDEN,
                od.ZMARCA as marca,
                od.ZSITUACION as situacion,
                od.ZCANTIDAD as cantidad,
                od.ZMEMBERS as miembros,
                od.ZDIAMETRO as diametro_principal,
                od.ZCODMODELO as modelo,
                od.ZTIPO as tipo,
                di.ZCOTAS as cotas,
                di.ZLONGITUD as longitud_ensamblaje
            FROM ORD_DET od
            LEFT JOIN PROD_DETI di ON od.ZCONTA = di.ZCONTA
                AND od.ZCODIGO = di.ZCODPLA
                AND od.ZCODLIN = di.ZCODLIN
            WHERE od.ZCONTA = :zconta AND od.ZCODIGO = :zcodigo
            ORDER BY od.ZCODLIN
        ";

        $stmtEnt = $pdo->prepare($sqlEntidades);
        $stmtEnt->execute(['zconta' => $zconta, 'zcodigo' => $zcodigo]);
        $entidades = $stmtEnt->fetchAll();

        echo "  Entidades encontradas: " . count($entidades) . "\n";

        // Crear o actualizar planilla en local
        $planilla = Planilla::updateOrCreate(
            ['codigo' => $planillaData->codigo],
            [
                'descripcion' => trim($planillaData->descripcion ?? ''),
                'seccion' => 'FERRAWIN',
                'peso_total' => 0,
                'revisada' => false,
            ]
        );

        foreach ($entidades as $entidad) {
            // Obtener elementos (barras/estribos) de esta entidad
            $sqlElementos = "
                SELECT
                    ob.ZELEMENTO as elemento,
                    ob.ZCANTIDAD as cantidad,
                    ob.ZDIAMETRO as diametro,
                    ob.ZLONGTESTD as longitud,
                    ob.ZNUMBEND as dobleces,
                    ob.ZFIGURA as dimensiones,
                    ob.ZPESOTESTD as peso,
                    ob.ZSTRBENT as tipo_forma,
                    ob.ZCODMODELO as figura
                FROM ORD_BAR ob
                WHERE ob.ZCONTA = :zconta
                  AND ob.ZCODIGO = :zcodigo
                  AND ob.ZCODLIN = :zcodlin
                ORDER BY ob.ZELEMENTO
            ";

            $stmtElem = $pdo->prepare($sqlElementos);
            $stmtElem->execute([
                'zconta' => $zconta,
                'zcodigo' => $zcodigo,
                'zcodlin' => $entidad->ZCODLIN,
            ]);
            $elementos = $stmtElem->fetchAll();

            // Clasificar elementos
            $barras = [];
            $estribos = [];
            $pesoTotal = 0;
            $longitudMaxima = 0;

            foreach ($elementos as $elem) {
                $dobleces = (int)($elem->dobleces ?? 0);
                $tipoForma = trim($elem->tipo_forma ?? '');
                $longitud = (float)($elem->longitud ?? 0);
                $dimensionesRaw = trim($elem->dimensiones ?? '');

                $elementoFormateado = [
                    'elemento' => trim($elem->elemento ?? ''),
                    'cantidad' => (int)($elem->cantidad ?? 0),
                    'diametro' => (int)($elem->diametro ?? 0),
                    'longitud' => $longitud,
                    'peso' => (float)($elem->peso ?? 0),
                    'figura' => trim($elem->figura ?? ''),
                ];

                $pesoTotal += (float)($elem->peso ?? 0);

                if ($dobleces > 0 || $tipoForma === 'Doblado') {
                    $elementoFormateado['dobleces'] = $dobleces;
                    $elementoFormateado['dimensiones'] = $dimensionesRaw;
                    if (!empty($dimensionesRaw)) {
                        $elementoFormateado['secuencia_doblado'] = parsearSecuenciaDoblado($dimensionesRaw);
                    }
                    $estribos[] = $elementoFormateado;
                } else {
                    if (!empty($dimensionesRaw)) {
                        $elementoFormateado['dimensiones'] = $dimensionesRaw;
                        $elementoFormateado['secuencia_doblado'] = parsearSecuenciaDoblado($dimensionesRaw);
                    }
                    $barras[] = $elementoFormateado;
                    if ($longitud > $longitudMaxima) {
                        $longitudMaxima = $longitud;
                    }
                }
            }

            $longitudEnsamblaje = (float)($entidad->longitud_ensamblaje ?? 0);
            if ($longitudEnsamblaje <= 0) {
                $longitudEnsamblaje = $longitudMaxima;
            }

            $composicion = [
                'barras' => $barras,
                'estribos' => $estribos,
            ];

            // Crear o actualizar entidad
            PlanillaEntidad::updateOrCreate(
                [
                    'planilla_id' => $planilla->id,
                    'linea' => trim($entidad->ZCODLIN ?? ''),
                ],
                [
                    'marca' => trim($entidad->marca ?? ''),
                    'situacion' => trim($entidad->situacion ?? ''),
                    'cantidad' => (int)($entidad->cantidad ?? 1),
                    'miembros' => (int)($entidad->miembros ?? 1),
                    'modelo' => trim($entidad->modelo ?? ''),
                    'cotas' => trim($entidad->cotas ?? ''),
                    'composicion' => $composicion,
                    'longitud_ensamblaje' => $longitudEnsamblaje,
                    'peso_total' => round($pesoTotal, 2),
                ]
            );
        }

        // También importar los elementos individuales (ORD_BAR)
        $sqlBarras = "
            SELECT
                ob.ZELEMENTO as elemento,
                ob.ZCODLIN as fila,
                od.ZSITUACION as descripcion_fila,
                ob.ZMARCA as marca,
                ob.ZDIAMETRO as diametro,
                ob.ZCODMODELO as figura,
                ob.ZLONGTESTD as longitud,
                ob.ZNUMBEND as dobles_barra,
                ob.ZCANTIDAD as barras,
                ob.ZPESOTESTD as peso,
                ob.ZFIGURA as dimensiones,
                COALESCE(pd.ZETIQUETA, '') as etiqueta
            FROM ORD_BAR ob
            LEFT JOIN ORD_DET od ON ob.ZCONTA = od.ZCONTA AND ob.ZCODIGO = od.ZCODIGO
                AND ob.ZORDEN = od.ZORDEN AND ob.ZCODLIN = od.ZCODLIN
            LEFT JOIN PROD_DETO pd ON ob.ZCONTA = pd.ZCONTA AND ob.ZCODIGO = pd.ZCODPLA
                AND ob.ZCODLIN = pd.ZCODLIN AND ob.ZELEMENTO = pd.ZELEMENTO
            WHERE ob.ZCONTA = :zconta AND ob.ZCODIGO = :zcodigo
            ORDER BY ob.ZCODLIN, ob.ZELEMENTO
        ";

        $stmtBarras = $pdo->prepare($sqlBarras);
        $stmtBarras->execute(['zconta' => $zconta, 'zcodigo' => $zcodigo]);
        $barrasData = $stmtBarras->fetchAll();

        $elementosCreados = 0;
        foreach ($barrasData as $barra) {
            Elemento::updateOrCreate(
                [
                    'planilla_id' => $planilla->id,
                    'fila' => trim($barra->fila ?? ''),
                    'marca' => trim($barra->marca ?? ''),
                ],
                [
                    'descripcion_fila' => trim($barra->descripcion_fila ?? ''),
                    'diametro' => (int)($barra->diametro ?? 0),
                    'figura' => trim($barra->figura ?? ''),
                    'longitud' => (float)($barra->longitud ?? 0),
                    'dobles_barra' => (int)($barra->dobles_barra ?? 0),
                    'barras' => (int)($barra->barras ?? 0),
                    'peso' => (float)($barra->peso ?? 0),
                    'dimensiones' => trim($barra->dimensiones ?? ''),
                    'etiqueta' => trim($barra->etiqueta ?? ''),
                ]
            );
            $elementosCreados++;
        }

        echo "  Entidades importadas: " . count($entidades) . "\n";
        echo "  Elementos importados: {$elementosCreados}\n";
        $importadas++;
    }

    echo "\n=== Importación completada ===\n";
    echo "Planillas importadas: {$importadas}\n";

} catch (PDOException $e) {
    echo "[ERROR] Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Parsea la secuencia de doblado de ZFIGURA.
 */
function parsearSecuenciaDoblado(string $figura): array
{
    if (empty($figura)) {
        return [];
    }

    $partes = preg_split('/\t/', $figura);
    $segmentos = [];

    foreach ($partes as $parte) {
        $parte = trim($parte);
        if (empty($parte)) {
            continue;
        }

        if (preg_match('/^(-?\d+\.?\d*)d$/', $parte, $matches)) {
            $segmentos[] = [
                'tipo' => 'doblez',
                'angulo' => (float)$matches[1],
            ];
        } elseif (preg_match('/^(\d+\.?\d*)r$/', $parte, $matches)) {
            $segmentos[] = [
                'tipo' => 'radio',
                'valor' => (float)$matches[1],
            ];
        } elseif (is_numeric($parte)) {
            $segmentos[] = [
                'tipo' => 'longitud',
                'valor' => (float)$parte,
            ];
        }
    }

    return $segmentos;
}
