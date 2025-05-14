<template>
    <div class="overflow-auto border border-gray-300">
        <div v-for="y in largo" :key="y" class="flex">
            <div
                v-for="x in ancho"
                :key="x"
                @click="toggleCell(x, y)"
                :class="[
                    'w-6 h-6 border border-gray-200',
                    isSelected(x, y) ? 'bg-green-500' : 'bg-white',
                ]"
                class="cursor-pointer"
                title="({{ x }}, {{ y }})"
            ></div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";

const ancho = 50;
const largo = 100; // puedes paginar para ver los 300

const seleccionadas = ref([]);

function toggleCell(x, y) {
    const key = `${x},${y}`;
    const index = seleccionadas.value.indexOf(key);

    if (index === -1) {
        seleccionadas.value.push(key);
    } else {
        seleccionadas.value.splice(index, 1);
    }
}

function isSelected(x, y) {
    return seleccionadas.value.includes(`${x},${y}`);
}
</script>
