<?php

namespace App\Http\Controllers;

use App\Models\Epi;
use App\Models\EpiUsuario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EpisController extends Controller
{
    public function index(Request $request)
    {
        return view('epis.index');
    }

    public function apiUsers(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('primer_apellido', 'like', "%{$q}%")
                        ->orWhere('segundo_apellido', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('dni', 'like', "%{$q}%")
                        ->orWhere('movil_personal', 'like', "%{$q}%");
                });
            })
            ->withSum(
                ['episAsignaciones as epis_en_posesion' => function ($query) {
                    $query->whereNull('devuelto_en');
                }],
                'cantidad'
            )
            ->orderByDesc('epis_en_posesion')
            ->orderBy('primer_apellido')
            ->orderBy('segundo_apellido')
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'primer_apellido' => $user->primer_apellido,
                    'segundo_apellido' => $user->segundo_apellido,
                    'nombre_completo' => $user->nombre_completo,
                    'dni' => $user->dni,
                    'email' => $user->email,
                    'movil_personal' => $user->movil_personal,
                    'ruta_imagen' => $user->ruta_imagen,
                    'epis_en_posesion' => (int) ($user->epis_en_posesion ?? 0),
                    'tiene_epis' => ((int) ($user->epis_en_posesion ?? 0)) > 0,
                ];
            })
            ->values();

        $topUser = $users->firstWhere('tiene_epis', true);

        return response()->json([
            'users' => $users,
            'stats' => [
                'usuarios_con_epis' => $users->where('tiene_epis', true)->count(),
                'top' => $topUser
                    ? ['user' => $topUser, 'cantidad' => (int) ($topUser['epis_en_posesion'] ?? 0)]
                    : null,
            ],
        ]);
    }

    public function apiEpis()
    {
        $epis = Epi::query()
            ->orderBy('nombre')
            ->get()
            ->map(function (Epi $epi) {
                return [
                    'id' => $epi->id,
                    'codigo' => $epi->codigo,
                    'nombre' => $epi->nombre,
                    'categoria' => $epi->categoria,
                    'descripcion' => $epi->descripcion,
                    'imagen_path' => $epi->imagen_path,
                    'imagen_url' => $epi->imagen_path ? asset('storage/' . $epi->imagen_path) : null,
                    'activo' => (bool) $epi->activo,
                ];
            })
            ->values();

        return response()->json(['epis' => $epis]);
    }

    public function apiUserAsignaciones(User $user)
    {
        $enPosesion = EpiUsuario::query()
            ->where('user_id', $user->id)
            ->whereNull('devuelto_en')
            ->with('epi')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (EpiUsuario $asignacion) {
                return [
                    'id' => $asignacion->id,
                    'user_id' => $asignacion->user_id,
                    'epi_id' => $asignacion->epi_id,
                    'cantidad' => (int) $asignacion->cantidad,
                    'entregado_en' => optional($asignacion->entregado_en)->toIso8601String(),
                    'devuelto_en' => optional($asignacion->devuelto_en)->toIso8601String(),
                    'notas' => $asignacion->notas,
                    'epi' => $asignacion->epi ? [
                        'id' => $asignacion->epi->id,
                        'codigo' => $asignacion->epi->codigo,
                        'nombre' => $asignacion->epi->nombre,
                        'categoria' => $asignacion->epi->categoria,
                        'descripcion' => $asignacion->epi->descripcion,
                        'imagen_path' => $asignacion->epi->imagen_path,
                        'imagen_url' => $asignacion->epi->imagen_path ? asset('storage/' . $asignacion->epi->imagen_path) : null,
                        'activo' => (bool) $asignacion->epi->activo,
                    ] : null,
                ];
            })
            ->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nombre_completo' => $user->nombre_completo,
                'dni' => $user->dni,
                'email' => $user->email,
                'movil_personal' => $user->movil_personal,
                'ruta_imagen' => $user->ruta_imagen,
            ],
            'en_posesion' => $enPosesion,
            'total_en_posesion' => (int) $enPosesion->sum('cantidad'),
        ]);
    }

    public function storeEpi(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:255', 'unique:epis,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'imagen' => ['nullable', 'image', 'max:2048'],
        ]);

        $epi = new Epi();
        $epi->fill($data);
        $epi->activo = (bool) ($data['activo'] ?? true);

        if ($request->hasFile('imagen')) {
            $epi->imagen_path = $request->file('imagen')->store('epis', 'public');
        }

        $epi->save();

        return response()->json([
            'ok' => true,
            'epi' => [
                'id' => $epi->id,
                'codigo' => $epi->codigo,
                'nombre' => $epi->nombre,
                'categoria' => $epi->categoria,
                'descripcion' => $epi->descripcion,
                'imagen_path' => $epi->imagen_path,
                'imagen_url' => $epi->imagen_path ? asset('storage/' . $epi->imagen_path) : null,
                'activo' => (bool) $epi->activo,
            ],
        ]);
    }

    public function updateEpi(Request $request, Epi $epi)
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:255', Rule::unique('epis', 'codigo')->ignore($epi->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
            'imagen' => ['nullable', 'image', 'max:2048'],
        ]);

        $epi->fill($data);
        $epi->activo = (bool) ($data['activo'] ?? false);

        if ($request->hasFile('imagen')) {
            if ($epi->imagen_path) {
                Storage::disk('public')->delete($epi->imagen_path);
            }
            $epi->imagen_path = $request->file('imagen')->store('epis', 'public');
        }

        $epi->save();

        return response()->json([
            'ok' => true,
            'epi' => [
                'id' => $epi->id,
                'codigo' => $epi->codigo,
                'nombre' => $epi->nombre,
                'categoria' => $epi->categoria,
                'descripcion' => $epi->descripcion,
                'imagen_path' => $epi->imagen_path,
                'imagen_url' => $epi->imagen_path ? asset('storage/' . $epi->imagen_path) : null,
                'activo' => (bool) $epi->activo,
            ],
        ]);
    }

    public function destroyEpi(Epi $epi)
    {
        if ($epi->asignaciones()->exists()) {
            $epi->activo = false;
            $epi->save();

            return response()->json([
                'ok' => true,
                'action' => 'deactivated',
                'epi' => [
                    'id' => $epi->id,
                    'activo' => (bool) $epi->activo,
                ],
            ]);
        }

        if ($epi->imagen_path) {
            Storage::disk('public')->delete($epi->imagen_path);
        }

        $epi->delete();

        return response()->json(['ok' => true, 'action' => 'deleted']);
    }

    public function asignarAUsuario(Request $request, User $user)
    {
        $data = $request->validate([
            'epi_id' => ['required', 'integer', 'exists:epis,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:1000'],
            'notas' => ['nullable', 'string'],
        ]);

        EpiUsuario::create([
            'user_id' => $user->id,
            'epi_id' => $data['epi_id'],
            'cantidad' => $data['cantidad'],
            'entregado_en' => now(),
            'notas' => $data['notas'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function devolverAsignacion(User $user, EpiUsuario $asignacion)
    {
        if ((int) $asignacion->user_id !== (int) $user->id) {
            abort(404);
        }

        if ($asignacion->devuelto_en) {
            return response()->json(['ok' => true, 'already_returned' => true]);
        }

        $asignacion->devuelto_en = now();
        $asignacion->save();

        return response()->json(['ok' => true]);
    }
}
