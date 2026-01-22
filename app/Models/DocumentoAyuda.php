<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class DocumentoAyuda extends Model
{
    protected $table = 'documentos_ayuda';

    protected $fillable = [
        'categoria',
        'titulo',
        'contenido',
        'embedding',
        'tags',
        'keywords',
        'activo',
        'orden',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'embedding' => 'array',
        'tags' => 'array',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // RELACIONES
    // ─────────────────────────────────────────────────────────────

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─────────────────────────────────────────────────────────────
    // SCOPES
    // ─────────────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeConEmbedding($query)
    {
        return $query->whereNotNull('embedding');
    }

    // ─────────────────────────────────────────────────────────────
    // BÚSQUEDA VECTORIAL (Similitud Coseno)
    // ─────────────────────────────────────────────────────────────

    /**
     * Busca documentos similares usando similitud coseno
     *
     * @param array $queryEmbedding El embedding de la pregunta del usuario
     * @param int $limit Número máximo de documentos a retornar
     * @param float $minSimilarity Similitud mínima (0-1) para incluir un documento
     * @return Collection Documentos ordenados por similitud descendente
     */
    public static function buscarSimilares(array $queryEmbedding, int $limit = 3, float $minSimilarity = 0.3): Collection
    {
        // Obtener todos los documentos activos con embedding
        $documentos = static::activos()
            ->conEmbedding()
            ->get();

        // Calcular similitud para cada documento
        $resultados = $documentos->map(function ($doc) use ($queryEmbedding) {
            $similitud = static::similitudCoseno($queryEmbedding, $doc->embedding);
            $doc->similitud = $similitud;
            return $doc;
        });

        // Filtrar por similitud mínima, ordenar y limitar
        return $resultados
            ->filter(fn($doc) => $doc->similitud >= $minSimilarity)
            ->sortByDesc('similitud')
            ->take($limit)
            ->values();
    }

    /**
     * Calcula la similitud coseno entre dos vectores
     * Retorna un valor entre -1 y 1 (1 = idénticos, 0 = sin relación, -1 = opuestos)
     */
    public static function similitudCoseno(array $vectorA, array $vectorB): float
    {
        if (count($vectorA) !== count($vectorB) || empty($vectorA)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitudA = 0.0;
        $magnitudB = 0.0;

        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $magnitudA += $vectorA[$i] * $vectorA[$i];
            $magnitudB += $vectorB[$i] * $vectorB[$i];
        }

        $magnitudA = sqrt($magnitudA);
        $magnitudB = sqrt($magnitudB);

        if ($magnitudA == 0 || $magnitudB == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudA * $magnitudB);
    }

    // ─────────────────────────────────────────────────────────────
    // BÚSQUEDA FULLTEXT (Fallback sin embeddings)
    // ─────────────────────────────────────────────────────────────

    /**
     * Búsqueda fulltext en MySQL como fallback
     */
    public function scopeBuscarTexto($query, string $texto)
    {
        return $query->whereRaw(
            "MATCH(titulo, contenido, keywords) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$texto]
        );
    }

    /**
     * Búsqueda por palabras clave (fallback más simple)
     */
    public static function buscarPorKeywords(string $pregunta, int $limit = 3): Collection
    {
        $palabras = preg_split('/\s+/', mb_strtolower($pregunta));

        return static::activos()
            ->where(function ($query) use ($palabras) {
                foreach ($palabras as $palabra) {
                    if (strlen($palabra) > 2) {
                        $query->orWhere('keywords', 'LIKE', "%{$palabra}%")
                              ->orWhere('contenido', 'LIKE', "%{$palabra}%")
                              ->orWhere('titulo', 'LIKE', "%{$palabra}%");
                    }
                }
            })
            ->orderBy('orden')
            ->limit($limit)
            ->get();
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    /**
     * Obtiene las categorías disponibles
     */
    public static function categorias(): Collection
    {
        return static::activos()
            ->select('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');
    }

    /**
     * Formatea el contenido para mostrar en el contexto del LLM
     */
    public function formatearParaContexto(): string
    {
        return "[{$this->categoria}] {$this->titulo}\n{$this->contenido}";
    }
}
