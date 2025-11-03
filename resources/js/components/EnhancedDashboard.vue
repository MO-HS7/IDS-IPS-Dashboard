<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div class="flex items-center">
            <ShieldIcon class="h-8 w-8 text-indigo-600 mr-3" />
            <h1 class="text-2xl font-bold text-gray-900">
              Enhanced IDS-IPS Dashboard
            </h1>
            <span class="ml-3 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
              ML-Enhanced
            </span>
          </div>
          <div class="flex items-center space-x-4">
            <button
              @click="refreshData"
              class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <ArrowPathIcon class="h-4 w-4 mr-2" />
              Refresh
            </button>
            <div class="flex items-center space-x-2">
              <div class="h-2 w-2 rounded-full" :class="connectionStatus.color"></div>
              <span class="text-sm text-gray-600">{{ connectionStatus.text }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- System Status Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatusCard
          title="Active Threats"
          :value="dashboardData.activeThreats"
          :trend="dashboardData.threatsTrend"
          icon="exclamation-triangle"
          color="red"
          @click="navigateToAlerts"
        />
        <StatusCard
          title="ML Accuracy"
          :value="`${dashboardData.mlAccuracy}%`"
          :trend="dashboardData.accuracyTrend"
          icon="chart-bar"
          color="green"
          @click="navigateToAnalytics"
        />
        <StatusCard
          title="Sessions Running"
          :value="dashboardData.activeSessions"
          icon="play-circle"
          color="blue"
          @click="navigateToMonitoring"
        />
        <StatusCard
          title="Detection Rate"
          :value="`${dashboardData.detectionRate}%`"
          :trend="dashboardData.detectionTrend"
          icon="bolt"
          color="purple"
        />
      </div>

      <!-- Dual Verification Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- ML Detection Panel -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 flex items-center">
              <CpuChipIcon class="h-5 w-5 text-indigo-600 mr-2" />
              ML Predictions
            </h2>
            <div class="flex items-center space-x-2">
              <span class="text-sm text-gray-500">Random Forest</span>
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Active
              </span>
            </div>
          </div>
          
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Model Confidence</span>
              <span class="font-medium">{{ mlData.confidence }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${mlData.confidence}%` }"
              ></div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Last Prediction:</span>
                <span class="font-medium ml-1">{{ mlData.lastPrediction || 'None' }}</span>
              </div>
              <div>
                <span class="text-gray-600">Features Analyzed:</span>
                <span class="font-medium ml-1">{{ mlData.featuresCount }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Snort Validation Panel -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 flex items-center">
              <ShieldCheckIcon class="h-5 w-5 text-green-600 mr-2" />
              Snort Validation
            </h2>
            <div class="flex items-center space-x-2">
              <span class="text-sm text-gray-500">{{ snortData.rulesCount }} Rules</span>
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Active
              </span>
            </div>
          </div>
          
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Validation Status</span>
              <span class="font-medium" :class="snortData.validationStatus === 'Valid' ? 'text-green-600' : 'text-yellow-600'">
                {{ snortData.validationStatus || 'Validating...' }}
              </span>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-600">Rules Matched:</span>
                <span class="font-medium ml-1">{{ snortData.rulesMatched }}</span>
              </div>
              <div>
                <span class="text-gray-600">Last Update:</span>
                <span class="font-medium ml-1">{{ snortData.lastUpdate || 'Never' }}</span>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <div class="h-2 w-2 rounded-full bg-green-500"></div>
              <span class="text-sm text-gray-600">Real-time signature-based detection</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Real-time Threats & Monitoring -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Live Threats -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-medium text-gray-900 flex items-center">
                <ExclamationTriangleIcon class="h-5 w-5 text-red-500 mr-2" />
                Real-time Threats
              </h2>
              <button 
                @click="toggleLiveMonitoring"
                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                :class="isLiveMonitoring ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'"
              >
                <div class="h-2 w-2 rounded-full mr-2" :class="isLiveMonitoring ? 'bg-red-500 animate-pulse' : 'bg-gray-400'"></div>
                {{ isLiveMonitoring ? 'Live' : 'Paused' }}
              </button>
            </div>
          </div>
          
          <div class="p-6">
            <div class="space-y-4 max-h-96 overflow-y-auto">
              <div
                v-for="threat in liveThreats"
                :key="threat.id"
                class="flex items-center justify-between p-4 border border-red-200 rounded-lg bg-red-50"
              >
                <div class="flex items-center space-x-4">
                  <div class="flex-shrink-0">
                    <ExclamationTriangleIcon class="h-6 w-6 text-red-500" />
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">{{ threat.type }}</p>
                    <p class="text-sm text-gray-600">{{ threat.source }} → {{ threat.destination }}</p>
                    <p class="text-xs text-gray-500">{{ threat.timestamp }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                        :class="getThreatSeverityClass(threat.severity)">
                    {{ threat.severity }}
                  </span>
                  <p class="text-sm text-gray-600 mt-1">{{ threat.confidence }}%</p>
                </div>
              </div>
              
              <div v-if="liveThreats.length === 0" class="text-center py-8 text-gray-500">
                <ShieldCheckIcon class="h-12 w-12 mx-auto mb-4 text-gray-300" />
                <p>No threats detected</p>
              </div>
            </div>
          </div>
        </div>

        <!-- System Health -->
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900 flex items-center">
              <HeartIcon class="h-5 w-5 text-green-500 mr-2" />
              System Health
            </h2>
          </div>
          
          <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">CPU Usage</span>
              <div class="flex items-center space-x-2">
                <div class="w-16 bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-blue-600 h-2 rounded-full"
                    :style="{ width: `${healthData.cpu}%` }"
                  ></div>
                </div>
                <span class="text-sm font-medium">{{ healthData.cpu }}%</span>
              </div>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Memory Usage</span>
              <div class="flex items-center space-x-2">
                <div class="w-16 bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-green-600 h-2 rounded-full"
                    :style="{ width: `${healthData.memory}%` }"
                  ></div>
                </div>
                <span class="text-sm font-medium">{{ healthData.memory }}%</span>
              </div>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Network I/O</span>
              <div class="flex items-center space-x-2">
                <div class="w-16 bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-purple-600 h-2 rounded-full"
                    :style="{ width: `${healthData.network}%` }"
                  ></div>
                </div>
                <span class="text-sm font-medium">{{ healthData.network }}%</span>
              </div>
            </div>
            
            <div class="pt-4 border-t border-gray-200">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Uptime</span>
                <span class="font-medium">{{ healthData.uptime }}</span>
              </div>
              <div class="flex items-center justify-between text-sm mt-2">
                <span class="text-gray-600">Packets Processed</span>
                <span class="font-medium">{{ healthData.packetsProcessed.toLocaleString() }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <button
            @click="startMonitoring"
            class="flex items-center justify-center px-4 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <PlayIcon class="h-4 w-4 mr-2" />
            Start Monitoring
          </button>
          
          <button
            @click="uploadPCAP"
            class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <DocumentArrowUpIcon class="h-4 w-4 mr-2" />
            Upload PCAP
          </button>
          
          <button
            @click="viewReports"
            class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <DocumentChartBarIcon class="h-4 w-4 mr-2" />
            View Reports
          </button>
          
          <button
            @click="manageRules"
            class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <Cog6ToothIcon class="h-4 w-4 mr-2" />
            Manage Rules
          </button>
        </div>
      </div>
    </div>

    <!-- Start Monitoring Modal -->
    <Modal :show="showMonitoringModal" @close="showMonitoringModal = false">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Start Enhanced Monitoring</h3>
        <form @submit.prevent="submitMonitoringForm">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Network Interface</label>
              <select 
                v-model="monitoringForm.interface" 
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                required
              >
                <option value="">Select interface</option>
                <option v-for="interface in availableInterfaces" :key="interface.name" :value="interface.name">
                  {{ interface.name }} - {{ interface.description }}
                </option>
              </select>
            </div>
            
            <div class="flex items-center space-x-4">
              <label class="flex items-center">
                <input 
                  v-model="monitoringForm.enableSnort" 
                  type="checkbox" 
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <span class="ml-2 text-sm text-gray-700">Enable Snort Validation</span>
              </label>
            </div>
            
            <div class="flex items-center space-x-4">
              <label class="flex items-center">
                <input 
                  v-model="monitoringForm.enableML" 
                  type="checkbox" 
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <span class="ml-2 text-sm text-gray-700">Enable ML Prediction</span>
              </label>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button
              type="button"
              @click="showMonitoringModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Start Monitoring
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { 
  ShieldIcon,
  ArrowPathIcon,
  CpuChipIcon,
  ShieldCheckIcon,
  ExclamationTriangleIcon,
  HeartIcon,
  PlayIcon,
  DocumentArrowUpIcon,
  DocumentChartBarIcon,
  Cog6ToothIcon
} from '@heroicons/vue/24/outline'

// State
const router = useRouter()
const showMonitoringModal = ref(false)
const isLiveMonitoring = ref(false)
const connectionStatus = ref({ color: 'bg-green-500', text: 'Connected' })

const dashboardData = ref({
  activeThreats: 3,
  threatsTrend: '+12%',
  mlAccuracy: 98.5,
  accuracyTrend: '+0.2%',
  activeSessions: 2,
  detectionRate: 94.2,
  detectionTrend: '+1.1%'
})

const mlData = ref({
  confidence: 87.5,
  lastPrediction: 'AnomalousTraffic',
  featuresCount: 42
})

const snortData = ref({
  validationStatus: 'Valid',
  rulesCount: 1523,
  rulesMatched: 0,
  lastUpdate: '2 min ago'
})

const healthData = ref({
  cpu: 35,
  memory: 68,
  network: 42,
  uptime: '2d 14h 32m',
  packetsProcessed: 1456789
})

const monitoringForm = ref({
  interface: '',
  enableSnort: true,
  enableML: true
})

const availableInterfaces = ref([
  { name: 'eth0', description: 'Ethernet connection' },
  { name: 'wlan0', description: 'WiFi interface' },
  { name: 'lo', description: 'Loopback interface' }
])

const liveThreats = ref([
  {
    id: 1,
    type: 'Port Scan',
    source: '192.168.1.100',
    destination: '192.168.1.1',
    severity: 'High',
    confidence: 89.5,
    timestamp: '2 min ago'
  },
  {
    id: 2,
    type: 'DoS Attempt',
    source: '10.0.0.15',
    destination: '192.168.1.50',
    severity: 'Critical',
    confidence: 94.2,
    timestamp: '5 min ago'
  }
])

// WebSocket connection
let ws = null
let refreshInterval = null

// Methods
const refreshData = () => {
  // Simulate data refresh
  dashboardData.value = {
    ...dashboardData.value,
    activeThreats: Math.floor(Math.random() * 10) + 1
  }
}

const toggleLiveMonitoring = () => {
  isLiveMonitoring.value = !isLiveMonitoring.value
  if (isLiveMonitoring.value) {
    startWebSocketConnection()
  } else {
    stopWebSocketConnection()
  }
}

const startMonitoring = () => {
  showMonitoringModal.value = true
}

const submitMonitoringForm = async () => {
  try {
    const response = await fetch('/api/v1/enhanced-ml/monitoring/start', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({
        interface: monitoringForm.value.interface,
        enable_snort: monitoringForm.value.enableSnort,
        enable_ml: monitoringForm.value.enableML
      })
    })

    const result = await response.json()
    
    if (result.success) {
      showMonitoringModal.value = false
      isLiveMonitoring.value = true
      // Show success notification
    }
  } catch (error) {
    console.error('Failed to start monitoring:', error)
  }
}

const uploadPCAP = () => {
  router.push('/pcap-upload')
}

const viewReports = () => {
  router.push('/reports')
}

const manageRules = () => {
  router.push('/rules')
}

const getThreatSeverityClass = (severity) => {
  const classes = {
    'Critical': 'bg-red-100 text-red-800',
    'High': 'bg-orange-100 text-orange-800',
    'Medium': 'bg-yellow-100 text-yellow-800',
    'Low': 'bg-green-100 text-green-800'
  }
  return classes[severity] || 'bg-gray-100 text-gray-800'
}

const navigateToAlerts = () => {
  router.push('/alerts')
}

const navigateToMonitoring = () => {
  router.push('/monitoring')
}

const navigateToAnalytics = () => {
  router.push('/analytics')
}

// WebSocket methods
const startWebSocketConnection = () => {
  if (ws) return
  
  ws = new WebSocket(`ws://${window.location.host}/ws/threats`)
  
  ws.onopen = () => {
    connectionStatus.value = { color: 'bg-green-500', text: 'Live Connected' }
  }
  
  ws.onmessage = (event) => {
    const data = JSON.parse(event.data)
    if (data.type === 'threat_detected') {
      liveThreats.value.unshift({
        id: Date.now(),
        type: data.attack_type,
        source: data.source_ip,
        destination: data.destination_ip,
        severity: data.severity,
        confidence: data.confidence,
        timestamp: 'Just now'
      })
      
      // Keep only last 20 threats
      if (liveThreats.value.length > 20) {
        liveThreats.value = liveThreats.value.slice(0, 20)
      }
    }
  }
  
  ws.onclose = () => {
    connectionStatus.value = { color: 'bg-yellow-500', text: 'Disconnected' }
    ws = null
  }
}

const stopWebSocketConnection = () => {
  if (ws) {
    ws.close()
    ws = null
    connectionStatus.value = { color: 'bg-gray-500', text: 'Connected' }
  }
}

// Lifecycle
onMounted(() => {
  // Start data refresh interval
  refreshInterval = setInterval(() => {
    refreshData()
    // Simulate real-time updates
    healthData.value = {
      ...healthData.value,
      cpu: Math.floor(Math.random() * 50) + 25,
      memory: Math.floor(Math.random() * 30) + 60,
      network: Math.floor(Math.random() * 20) + 30,
      packetsProcessed: healthData.value.packetsProcessed + Math.floor(Math.random() * 1000) + 100
    }
  }, 5000)
})

onUnmounted(() => {
  stopWebSocketConnection()
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>

<!-- StatusCard Component -->
<script>
export default {
  name: 'StatusCard',
  props: {
    title: String,
    value: [String, Number],
    trend: String,
    icon: String,
    color: String
  },
  emits: ['click'],
  methods: {
    getIconClass() {
      const classes = {
        red: 'text-red-600',
        green: 'text-green-600',
        blue: 'text-blue-600',
        purple: 'text-purple-600'
      }
      return classes[this.color] || 'text-gray-600'
    }
  }
}
</script>

<style scoped>
/* Custom animations */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>