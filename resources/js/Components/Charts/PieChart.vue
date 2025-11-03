<script setup>
import { Pie } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale);

const props = defineProps({
    chartData: {
        type: Object,
        required: true,
    },
    chartOptions: {
        type: Object,
        default: () => ({ responsive: true, maintainAspectRatio: false }),
    },
    height: {
        type: Number,
        default: 256,
    },
});
</script>

<template>
    <div :style="{ position: 'relative', height: `${props.height}px`, width: '100%' }">
        <Pie :data="chartData" :options="chartOptions" />
    </div>
</template>
