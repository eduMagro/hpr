<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Máquinas') }}
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

    <div class="container mt-5">
        <div class="row justify-content-center mb-4">
            <div class="col-md-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="text-uppercase">Crear Máquina</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('maquinas.store') }}" method="POST">
                            @csrf
                        
                            <!-- Código de la máquina -->
                            <div class="form-group mb-4">
                                <label for="codigo" class="form-label fw-bold text-uppercase">Código de la Máquina *</label>
                                <input type="text" id="codigo" name="codigo" class="form-control form-control-lg"
                                    placeholder="Introduce el código de la máquina" required>
                            </div>
                        
                            <!-- Nombre de la máquina -->
                            <div class="form-group mb-4">
                                <label for="nombre" class="form-label fw-bold text-uppercase">Nombre de la Máquina *</label>
                                <input type="text" id="nombre" name="nombre" class="form-control form-control-lg"
                                    placeholder="Introduce el nombre de la máquina" required>
                            </div> 
                            
                            <!-- Diámetro mínimo -->
                            <div class="form-group mb-4">
                                <label for="diametro_min" class="form-label fw-bold text-uppercase">Diámetro Mínimo *</label>
                                <select id="diametro_min" name="diametro_min" class="form-control form-control-lg" required>
                                    <option value="" disabled selected>Selecciona un diámetro mínimo</option>
                                    <option value="8">8</option>
                                    <option value="10">10</option>
                                    <option value="12">12</option>
                                    <option value="16">16</option>
                                    <option value="20">20</option>
                                    <option value="25">25</option>
                                    <option value="32">32</option>
                                </select>
                            </div>
                        
                            <!-- Diámetro máximo -->
                            <div class="form-group mb-4">
                                <label for="diametro_max" class="form-label fw-bold text-uppercase">Diámetro Máximo *</label>
                                <select id="diametro_max" name="diametro_max" class="form-control form-control-lg" required>
                                    <option value="" disabled selected>Selecciona un diámetro máximo</option>
                                    <option value="8">8</option>
                                    <option value="10">10</option>
                                    <option value="12">12</option>
                                    <option value="16">16</option>
                                    <option value="20">20</option>
                                    <option value="25">25</option>
                                    <option value="32">32</option>
                                </select>
                            </div>
                        
                            <!-- Peso mínimo -->
                            <div class="form-group mb-4">
                                <label for="peso_min" class="form-label fw-bold text-uppercase">Peso Mínimo *</label>
                                <select id="peso_min" name="peso_min" class="form-control form-control-lg">
                                    <option value="" disabled selected>Selecciona un peso mínimo</option>
                                    <option value="3000">3000 kg</option>
                                    <option value="5000">5000 kg</option>
                                    <option value="barras">Barras</option>
                                </select>
                            </div>
                        
                            <!-- Peso máximo -->
                            <div class="form-group mb-4">
                                <label for="peso_max" class="form-label fw-bold text-uppercase">Peso Máximo *</label>
                                <select id="peso_max" name="peso_max" class="form-control form-control-lg">
                                    <option value="" disabled selected>Selecciona un peso máximo</option>
                                    <option value="3000">3000 kg</option>
                                    <option value="5000">5000 kg</option>
                                    <option value="barras">Barras</option>
                                </select>
                            </div>
                        
                            <!-- Botón para enviar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">Registrar Máquina</button>
                            </div>
                        </form>
                        
                    </div>
                    <div class="card-footer text-center text-muted">
                        <small>Todos los campos con * son obligatorios.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    
</x-app-layout>
