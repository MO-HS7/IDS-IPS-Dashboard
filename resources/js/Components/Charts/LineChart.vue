<script setup>
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale);

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
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
