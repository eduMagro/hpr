<x-app-layout>
    <x-slot name="title">Mi Perfil</x-slot>

    <div class="container mx-auto px-4 py-6">


        <x-ficha-trabajador :user="$user" :resumen="$resumen" />

    </div>

</x-app-layout>
