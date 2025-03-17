@extends('layouts.app')

@section('content')
    <h1>Papelera de reciclaje</h1>

    <h2>Paquetes eliminados</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Acciones</th>
        </tr>
        @foreach ($paquetes as $paquete)
            <tr>
                <td>{{ $paquete->id }}</td>
                <td>{{ $paquete->nombre }}</td>
                <td>
                    <form action="{{ route('papelera.restore', ['model' => 'paquetes', 'id' => $paquete->id]) }}"
                        method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit">Restaurar</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>

    <h2>Camiones eliminados</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Placa</th>
            <th>Acciones</th>
        </tr>
        @foreach ($camiones as $camion)
            <tr>
                <td>{{ $camion->id }}</td>
                <td>{{ $camion->placa }}</td>
                <td>
                    <form action="{{ route('papelera.restore', ['model' => 'camiones', 'id' => $camion->id]) }}"
                        method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit">Restaurar</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>

    <h2>Empresas de transporte eliminadas</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Acciones</th>
        </tr>
        @foreach ($empresas as $empresa)
            <tr>
                <td>{{ $empresa->id }}</td>
                <td>{{ $empresa->nombre }}</td>
                <td>
                    <form action="{{ route('papelera.restore', ['model' => 'empresas_transporte', 'id' => $empresa->id]) }}"
                        method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit">Restaurar</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </table>
@endsection
