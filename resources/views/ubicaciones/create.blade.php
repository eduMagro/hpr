<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Ubicaciones') }}
        </h2>
    </x-slot>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="container mt-5">
        <h2 class="text-center mb-4">Crear Ubicación</h2>
        <form action="{{ route('ubicaciones.store') }}" method="POST">
            @csrf
            <!-- almacen -->
            <div class="mb-3">
                <label for="almacen" class="form-label">Almacén</label>
                <select id="almacen" name="almacen" class="form-select">
                    <option value="0A">0A</option>
                    <option value="0B">0B</option>
                    <option value="0C">0C</option>
                </select>
            </div>

            <!-- Sector -->
            <div class="mb-3">
                <label for="sector" class="form-label">Sector</label>
                <select id="sector" name="sector" class="form-select">
                    <?php for ($i = 1; $i <= 20; $i++): ?>
                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Ubicación -->
            <div class="mb-3">
                <label for="ubicacion" class="form-label">Ubicación</label>
                <select id="ubicacion" name="ubicacion" class="form-select">
                    <?php for ($i = 1; $i <= 100; $i++): ?>
                        <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Botón de Enviar -->
            <div class="d-flex justify-content-center">
                <button type="submit" class="btn btn-primary">Crear Ubicación</button>
            </div>
        </form>
    </div>

   
</x-app-layout>
