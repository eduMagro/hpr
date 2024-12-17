<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Entradas de Material') }}
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
     <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <div class="container mx-auto px-4 py-6">

        <form class="needs-validation" novalidate>
            <div class="mb-3">
              <label for="nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="nombre" required>
              <div class="invalid-feedback">
                Por favor, ingresa tu nombre.
              </div>
            </div>
            
            <div class="mb-3">
              <label for="email" class="form-label">Correo Electrónico</label>
              <input type="email" class="form-control" id="email" required>
              <div class="invalid-feedback">
                Por favor, ingresa un correo electrónico válido.
              </div>
            </div>
            
            <div class="mb-3">
              <label for="password" class="form-label">Contraseña</label>
              <input type="password" class="form-control" id="password" required minlength="6">
              <div class="invalid-feedback">
                La contraseña debe tener al menos 6 caracteres.
              </div>
            </div>
      
            <div class="mb-3">
              <label for="confirmarPassword" class="form-label">Confirmar Contraseña</label>
              <input type="password" class="form-control" id="confirmarPassword" required>
              <div class="invalid-feedback">
                Las contraseñas no coinciden.
              </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Registrar</button>
          </form>
        </div>
      
        <!-- Bootstrap JS y dependencias -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <script>
          document.addEventListener('DOMContentLoaded', function () {
            var forms = document.querySelectorAll('.needs-validation');
      
            Array.prototype.slice.call(forms)
              .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                  var password = document.getElementById('password');
                  var confirmarPassword = document.getElementById('confirmarPassword');
      
                  if (password.value !== confirmarPassword.value) {
                    confirmarPassword.setCustomValidity("Las contraseñas no coinciden.");
                  } else {
                    confirmarPassword.setCustomValidity("");
                  }
      
                  if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                  }
      
                  form.classList.add('was-validated');
                }, false);
              });
          });
        </script>
</x-app-layout>
