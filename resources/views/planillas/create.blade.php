<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Importar Planillas') }}
        </h2>
    </x-slot>

    <!-- Mostrar errores de validación -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Mostrar mensajes de éxito o error -->
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

    <div class="container mx-auto px-4 py-6">
        <!-- Formulario de importación -->
        <div class="mb-4">
            <form method="post" action="{{ route('planillas.import') }}" enctype="multipart/form-data" class="bg-light border p-4 rounded">
                @csrf
                <div class="form-group mb-3">
                    <label for="file" class="form-label fw-bold">
                        Seleccionar archivo para importar
                    </label>
                    <input type="file" name="file" id="file" class="form-control">
                </div>
            
                <div class="d-flex justify-content-end">
                    <input type="submit" class="btn btn-primary" name="proceso" value="IMPORTAR">
                </div>
            </form>
        </form>
        </div>

        <!-- Información adicional -->
        <div class="mt-4">
            <h3 class="font-semibold text-lg">Instrucciones:</h3>
            <ul class="list-disc pl-5">
                <li>Asegúrese de que el archivo esté en formato Excel (.xlsx).</li>
                <li>El archivo debe contener las columnas requeridas para importar las planillas.</li>
                <li>Después de importar, el peso total se calculará automáticamente.</li>
            </ul>
        </div>
    </div>
</x-app-layout>