<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const stats = ref([
    { value: '99.7%', label: 'Detection Accuracy' },
    { value: '24/7', label: 'Real-time Monitoring' },
    { value: '<1s', label: 'Response Time' },
    { value: '100K+', label: 'Threats Blocked' }
]);

const features = ref([
    {
        icon: '🧠',
        title: 'AI-Powered Detection',
        description: 'Advanced machine learning algorithms detect threats with precision using Random Forest, Neural Networks, and SVM models.'
    },
    {
        icon: '⚡',
        title: 'Real-time Alerts',
        description: 'Instant notifications when suspicious activity is detected with severity-based categorization and confidence scoring.'
    },
    {
        icon: '📊',
        title: 'Advanced Analytics',
        description: 'Comprehensive dashboards with detailed security insights, threat statistics, and historical trend analysis.'
    },
    {
        icon: '🛡️',
        title: 'Multi-layer Protection',
        description: 'Defense against DDoS, SQL Injection, XSS, Port Scans, Brute Force attacks, and Malware detection.'
    },
    {
        icon: '📁',
        title: 'File Processing',
        description: 'Support for CSV and PCAP network log files with automated validation and real-time processing status.'
    },
    {
        icon: '👥',
        title: 'Role Management',
        description: 'Role-based access control with Admin, Analyst, and Viewer permissions for secure collaboration.'
    }
]);

const technologies = ref([
    { name: 'Laravel 12', category: 'Backend', description: 'Modern PHP framework with elegant syntax' },
    { name: 'Vue.js 3', category: 'Frontend', description: 'Progressive JavaScript framework with Composition API' },
    { name: 'Python ML', category: 'AI/ML', description: 'Scikit-learn, Pandas, NumPy for machine learning' },
    { name: 'Inertia.js', category: 'Integration', description: 'Modern monolith for seamless SPA experience' },
    { name: 'Tailwind CSS', category: 'Styling', description: 'Utility-first CSS framework for responsive design' },
    { name: 'MySQL', category: 'Database', description: 'Reliable relational database with optimized indexing' }
]);

onMounted(() => {
    // Add scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fadeIn');
            }
        });
    });

    document.querySelectorAll('.fade-in-section').forEach(el => {
        observer.observe(el);
    });
});
</script>

<template>
    <Head title="AI-IDS - Intelligent Intrusion Detection System" />
    
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">

                    <!-- Logo -->
                    <div class="flex items-center">
                       <Link href="/" class="flex-shrink-0 flex items-center">
                           <img src="/images/logo.png" alt="AI-IDS Logo" 
                               class="w-16 h-16 object-contain mr-3" />
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                 AI-IDS
                         </span>
                       </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-8">
                            <a href="#features" class="text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium transition-colors">
                                Features
                            </a>
                            <a href="#about" class="text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium transition-colors">
                                About
                            </a>
                            <a href="#technology" class="text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium transition-colors">
                                Technology
                            </a>
                        </div>
                    </div>

                    <!-- Auth Links -->
                    <div class="flex items-center space-x-4">
                        <template v-if="canLogin">
                            <Link href="/login" 
                                  class="text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium transition-colors">
                                Sign In
                            </Link>
                            <Link v-if="canRegister" 
                                  href="/register" 
                                  class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                                Get Started
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative bg-white dark:bg-gray-900 overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-gradient-to-r from-primary-50 to-cyan-50 dark:from-gray-800 dark:to-gray-900 opacity-90"></div>
                <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-primary-100 to-transparent dark:from-gray-700 dark:to-transparent"></div>
            </div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="py-16 md:py-24 lg:py-32">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                        <!-- Hero Content -->
                        <div class="fade-in-section">
                            <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 mb-6">
                                <span class="w-2 h-2 bg-primary-500 rounded-full mr-2"></span>
                                AI-Powered Security Solution
                            </div>
                            
                            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-gray-900 dark:text-white leading-tight mb-6">
                                Intelligent
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-cyan-600">
                                    Network Security
                                </span>
                                Made Simple
                            </h1>
                            
                            <p class="text-lg text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
                                Protect your network with advanced AI algorithms that detect and respond to threats in real-time. 
                                Experience next-generation intrusion detection with unmatched accuracy and automated response capabilities.
                            </p>
                            
                            <div class="flex flex-col sm:flex-row gap-4 mb-12">
                                <Link href="/register" 
                                      class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-4 rounded-lg font-semibold text-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-1">
                                    Start Free Trial
                                </Link>
                                <button class="border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-8 py-4 rounded-lg font-semibold text-lg hover:border-primary-500 dark:hover:border-primary-400 transition-colors">
                                    Watch Demo
                                </button>
                            </div>
                            
                            <!-- Stats -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                                <div v-for="stat in stats" :key="stat.label" class="text-center">
                                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ stat.label }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Hero Illustration -->
                        <div class="fade-in-section relative">
                            <div class="relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                                <div class="mb-4 flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Security Dashboard</h3>
                                    <span class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></span>
                                </div>
                                
                                <!-- Mock Dashboard -->
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border-l-4 border-red-500">
                                        <div>
                                            <div class="font-medium text-red-800 dark:text-red-200">Critical Alert</div>
                                            <div class="text-sm text-red-600 dark:text-red-300">DDoS Attack Detected</div>
                                        </div>
                                        <div class="text-red-600 dark:text-red-400">🚨</div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border-l-4 border-yellow-500">
                                        <div>
                                            <div class="font-medium text-yellow-800 dark:text-yellow-200">High Alert</div>
                                            <div class="text-sm text-yellow-600 dark:text-yellow-300">SQL Injection Attempt</div>
                                        </div>
                                        <div class="text-yellow-600 dark:text-yellow-400">⚠️</div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border-l-4 border-green-500">
                                        <div>
                                            <div class="font-medium text-green-800 dark:text-green-200">System Status</div>
                                            <div class="text-sm text-green-600 dark:text-green-300">All Systems Operational</div>
                                        </div>
                                        <div class="text-green-600 dark:text-green-400">✅</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Background decoration -->
                            <div class="absolute top-4 -right-4 w-32 h-32 bg-primary-200 dark:bg-primary-800 rounded-full opacity-20"></div>
                            <div class="absolute -bottom-6 left-4 w-20 h-20 bg-cyan-200 dark:bg-cyan-800 rounded-full opacity-30"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Project Section -->
        <section id="about" class="py-20 bg-white dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16 fade-in-section">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        About AI-IDS Project
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                        A comprehensive Laravel-based Intrusion Detection System that leverages advanced machine learning 
                        algorithms to detect and analyze network security threats in real-time.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-16">
                    <!-- Project Description -->
                    <div class="fade-in-section">
                        <div class="space-y-6">
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Advanced AI Technology</h3>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Utilizing cutting-edge machine learning algorithms including Random Forest, Neural Networks, 
                                        Support Vector Machines, and Deep Learning models to provide unparalleled threat detection accuracy.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Real-time Processing</h3>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Process network logs and detect threats in real-time with automated alert generation, 
                                        severity classification, and instant notification capabilities for immediate response.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Enterprise Architecture</h3>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Built with modern web technologies and scalable architecture designed for enterprise-level 
                                        security operations with role-based access control and comprehensive audit logging.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Technical Features -->
                    <div class="fade-in-section">
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-8">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Key Capabilities</h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                        <span class="font-medium text-gray-900 dark:text-white">Network Log Analysis</span>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">CSV & PCAP Support</span>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        <span class="font-medium text-gray-900 dark:text-white">Multi-Model Detection</span>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">5+ ML Algorithms</span>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                        <span class="font-medium text-gray-900 dark:text-white">Threat Classification</span>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">8+ Attack Types</span>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                        <span class="font-medium text-gray-900 dark:text-white">User Management</span>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Role-Based Access</span>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                        <span class="font-medium text-gray-900 dark:text-white">Real-time Alerts</span>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Instant Notifications</span>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-indigo-500 rounded-full"></div>
                                        <span class="font-medium text-gray-900 dark:text-white">Analytics Dashboard</span>
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Interactive Charts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Project Goals -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 fade-in-section">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Enhanced Security</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Provide organizations with advanced threat detection capabilities to protect against modern cyber attacks.
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">User-Friendly Interface</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Deliver an intuitive web interface that makes complex security operations accessible to security professionals.
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">AI Innovation</h3>
                        <p class="text-gray-600 dark:text-gray-300">
                            Demonstrate the practical application of machine learning in cybersecurity with cutting-edge algorithms.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Technology Stack Section -->
        <section id="technology" class="py-20 bg-gray-50 dark:bg-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16 fade-in-section">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        Technology Stack
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                        Built with modern technologies and frameworks to ensure scalability, performance, and maintainability.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="tech in technologies" :key="tech.name" 
                         class="fade-in-section bg-white dark:bg-gray-700 rounded-xl p-6 shadow-sm hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ tech.name }}</h3>
                            <span class="px-3 py-1 text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 rounded-full">
                                {{ tech.category }}
                            </span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-300">{{ tech.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 bg-white dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16 fade-in-section">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        Powerful Features for Complete Protection
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                        Our AI-powered intrusion detection system provides comprehensive security monitoring 
                        with advanced features designed for modern threats.
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="feature in features" :key="feature.title" 
                         class="fade-in-section bg-gray-50 dark:bg-gray-800 rounded-xl p-6 hover:bg-gray-100 dark:hover:bg-gray-750 transition-colors duration-200">
                        <div class="text-3xl mb-4">{{ feature.icon }}</div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ feature.title }}</h3>
                        <p class="text-gray-600 dark:text-gray-300">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 bg-gradient-to-r from-primary-600 to-cyan-600">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8 fade-in-section">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    Ready to Secure Your Network?
                </h2>
                <p class="text-xl text-primary-100 mb-8">
                    Join organizations protecting their infrastructure with AI-IDS advanced threat detection.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link href="/register" 
                          class="bg-white text-primary-600 hover:bg-gray-50 px-8 py-4 rounded-lg font-bold text-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-1">
                        Get Started Today
                    </Link>
                    <Link href="/login" 
                          class="border-2 border-white text-white hover:bg-white hover:text-primary-600 px-8 py-4 rounded-lg font-bold text-lg transition-all duration-200">
                        Sign In
                    </Link>
                </div>
            </div>
        </section>

<!-- Footer -->
<footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Brand -->
            <div class="md:col-span-2">
                <div class="flex items-center mb-4">
                    <!-- Logo as image -->
                    <img src="/images/logo.png" 
                         alt="AI-IDS Logo" 
                         class="w-16 h-16 object-contain mr-3" />
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                        AI-IDS
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Advanced AI-powered Intrusion Detection System designed to protect networks from modern cyber threats 
                    with real-time monitoring and intelligent threat analysis.
                </p>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    © {{ new Date().getFullYear() }} AI-IDS. All rights reserved.
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                    Quick Links
                </h3>
                <ul class="space-y-2">
                    <li><a href="#features" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Features</a></li>
                    <li><a href="#about" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">About</a></li>
                    <li><a href="#technology" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Technology</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                    Get Started
                </h3>
                <ul class="space-y-2">
                    <li><Link href="/register" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Sign Up</Link></li>
                    <li><Link href="/login" class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Sign In</Link></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

    </div>
</template>

<style scoped>
.animate-fadeIn {
    animation: fadeIn 0.8s ease-out forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-section {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.8s ease-out;
}
</style>
