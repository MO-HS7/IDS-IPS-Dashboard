<script setup>
import { ref, watch, onMounted } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    packets: {
        type: Array,
        default: () => []
    },
    isMonitoring: {
        type: Boolean,
        default: false
    }
});

const maxDataPoints = 60; // Show last 60 seconds
const chartData = ref({
    labels: [],
    datasets: [
        {
            label: 'Packets/sec',
            data: [],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4,
        },
        {
            label: 'Threats/sec',
            data: [],
            borderColor: 'rgb(239, 68, 68)',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: true,
            tension: 0.4,
        }
    ]
});

const chartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: {
            position: 'top',
            labels: {
                color: '#9ca3af',
            }
        },
        title: {
            display: true,
            text: 'Real-time Traffic Flow',
            color: '#9ca3af',
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
        }
    },
    scales: {
        x: {
            display: true,
            title: {
                display: true,
                text: 'Time',
                color: '#9ca3af',
            },
            ticks: {
                color: '#9ca3af',
            },
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            }
        },
        y: {
            display: true,
            title: {
                display: true,
                text: 'Count',
                color: '#9ca3af',
            },
            ticks: {
                color: '#9ca3af',
            },
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            beginAtZero: true,
        }
    }
});

const isInitialized = ref(false);

const updateChart = () => {
    // Group packets by second
    const grouped = {};
    
    props.packets.forEach(packet => {
        if (!packet.time) return;
        
        // Extract seconds from time (format: HH:MM:SS.mmm)
        const timeKey = packet.time.split('.')[0]; // Get HH:MM:SS
        
        if (!grouped[timeKey]) {
            grouped[timeKey] = { total: 0, threats: 0 };
        }
        
        grouped[timeKey].total++;
        if (packet.is_threat) {
            grouped[timeKey].threats++;
        }
    });
    
    const times = Object.keys(grouped).sort().slice(-maxDataPoints);
    
    // Update chart data without triggering watchers
    chartData.value.labels = times;
    chartData.value.datasets[0].data = times.map(time => grouped[time].total);
    chartData.value.datasets[1].data = times.map(time => grouped[time].threats);
};

// Update chart when packets array length changes (but not during initialization)
watch(() => props.packets.length, () => {
    if (isInitialized.value && props.isMonitoring && props.packets.length > 0) {
        updateChart();
    }
});

// Initialize chart
onMounted(() => {
    // Initialize with empty data points
    const now = new Date();
    const initialLabels = [];
    const initialData0 = [];
    const initialData1 = [];
    
    for (let i = maxDataPoints - 1; i >= 0; i--) {
        const time = new Date(now - i * 1000);
        initialLabels.push(time.toLocaleTimeString());
        initialData0.push(0);
        initialData1.push(0);
    }
    
    // Set data once instead of pushing in loop
    chartData.value.labels = initialLabels;
    chartData.value.datasets[0].data = initialData0;
    chartData.value.datasets[1].data = initialData1;
    
    // Mark as initialized so watcher can start working
    isInitialized.value = true;
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <div class="h-64">
                <Line
                    :data="chartData"
                    :options="chartOptions"
                />
            </div>
        </div>
    </div>
</template>
