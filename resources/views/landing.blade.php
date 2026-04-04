<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AllLeads CRM - The single-tenant CRM for web development agencies. Import leads, generate AI cold emails, and close deals faster.">
    <meta name="theme-color" content="#1e5a96">
    <title>AllLeads CRM - AI-Powered Lead Management for Agencies</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <style>
        * { font-family: 'Inter', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        .animate-float-delayed {
            animation: float 6s ease-in-out infinite;
            animation-delay: 2s;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .grid-pattern {
            background-image: radial-gradient(circle at 1px 1px, rgba(30, 64, 175, 0.08) 1px, transparent 0);
            background-size: 32px 32px;
        }
        .feature-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .feature-card:hover {
            transform: translateY(-8px);
        }
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }
        .feature-icon {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(30, 64, 175, 0.4);
        }
        .btn-secondary {
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.1);
        }
        .tech-badge {
            transition: all 0.3s ease;
        }
        .tech-badge:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(30, 64, 175, 0.3);
        }
        .nav-link {
            position: relative;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #1e40af;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .glow {
            box-shadow: 0 0 60px -15px rgba(59, 130, 246, 0.4);
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 0.9; }
        }
        .animate-pulse-slow {
            animation: pulse-slow 4s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased overflow-x-hidden">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-white/80 backdrop-blur-xl border-b border-slate-200/50" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center shadow-lg shadow-blue-600/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold bg-gradient-to-r from-blue-700 to-blue-500 bg-clip-text text-transparent">AllLeads</span>
                </div>
                <div class="hidden md:flex items-center gap-10">
                    <a href="#features" class="nav-link text-slate-600 hover:text-slate-900 font-medium transition">Features</a>
                    <a href="#technology" class="nav-link text-slate-600 hover:text-slate-900 font-medium transition">Technology</a>
                    <a href="#pricing" class="nav-link text-slate-600 hover:text-slate-900 font-medium transition">Pricing</a>
                </div>
                <a href="{{ route('filament.admin.auth.login') }}" class="px-6 py-3 bg-slate-900 text-white rounded-full font-semibold hover:bg-slate-800 transition shadow-lg shadow-slate-900/20 btn-secondary">
                    Sign In
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-20">
        <!-- Background Effects -->
        <div class="absolute inset-0 grid-pattern opacity-50"></div>
        <div class="absolute top-40 left-[10%] w-96 h-96 bg-blue-400/20 rounded-full blur-[100px] animate-float animate-pulse-slow"></div>
        <div class="absolute bottom-40 right-[10%] w-[500px] h-[500px] bg-indigo-400/15 rounded-full blur-[120px] animate-float-delayed animate-pulse-slow"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-blue-200/10 rounded-full blur-[150px]"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center max-w-5xl mx-auto">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-100 mb-10 glow">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="text-sm font-semibold text-blue-700">Now with AI Website Analysis</span>
                </div>
                
                <!-- Headline -->
                <h1 class="text-6xl sm:text-7xl lg:text-8xl font-black text-slate-900 mb-8 leading-[0.95] tracking-tight">
                    Land More<br>
                    <span class="gradient-text">Clients</span>
                </h1>
                
                <!-- Subheadline -->
                <p class="text-xl sm:text-2xl text-slate-600 mb-12 max-w-3xl mx-auto leading-relaxed font-medium">
                    The single-tenant CRM built exclusively for web development agencies. Import leads, analyze websites with AI, and close deals faster.
                </p>
                
                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-20">
                    <a href="{{ route('filament.admin.auth.login') }}" class="px-10 py-5 btn-primary text-white rounded-full font-bold text-lg shadow-2xl">
                        Start Free — No Credit Card
                    </a>
                    <a href="#features" class="px-10 py-5 bg-white text-slate-700 border-2 border-slate-200 rounded-full font-bold text-lg btn-secondary">
                        Explore Features
                    </a>
                </div>

                <!-- Stats -->
                <div class="glass-card rounded-3xl p-10 shadow-2xl max-w-3xl mx-auto">
                    <div class="grid grid-cols-3 gap-8">
                        <div class="text-center">
                            <div class="text-5xl font-black text-slate-900 mb-2">100%</div>
                            <div class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Free Forever</div>
                        </div>
                        <div class="text-center border-x border-slate-200">
                            <div class="text-5xl font-black text-slate-900 mb-2">∞</div>
                            <div class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Unlimited</div>
                        </div>
                        <div class="text-center">
                            <div class="text-5xl font-black text-slate-900 mb-2">60s</div>
                            <div class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Setup</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-32 bg-white relative">
        <div class="absolute inset-0 bg-gradient-to-b from-slate-50/50 to-white/0 pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-24">
                <span class="inline-block px-5 py-2 rounded-full bg-blue-100 text-blue-700 text-sm font-bold uppercase tracking-wider mb-8">Features</span>
                <h2 class="text-5xl sm:text-6xl lg:text-7xl font-black text-slate-900 mb-8 leading-tight">
                    Everything You Need<br><span class="gradient-text">To Close More Deals</span>
                </h2>
                <p class="text-xl text-slate-600 max-w-3xl mx-auto leading-relaxed">Built for agencies. Powered by cutting-edge AI. Zero learning curve.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card group p-10 rounded-3xl bg-slate-50 border border-slate-200 hover:border-blue-300 hover:shadow-2xl hover:shadow-blue-900/5">
                    <div class="feature-icon w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-blue-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Smart Lead Import</h3>
                    <p class="text-slate-600 mb-6 leading-relaxed text-lg">Drag-and-drop CSV/JSON files with automatic duplicate detection and intelligent field mapping. Queued processing with per-batch undo.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-4 py-1.5 bg-blue-100 text-blue-700 text-sm font-semibold rounded-full">Batch Processing</span>
                        <span class="px-4 py-1.5 bg-blue-100 text-blue-700 text-sm font-semibold rounded-full">Deduplication</span>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card group p-10 rounded-3xl bg-slate-50 border border-slate-200 hover:border-amber-300 hover:shadow-2xl hover:shadow-amber-900/5">
                    <div class="feature-icon w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-orange-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">AI Prospect Detection</h3>
                    <p class="text-slate-600 mb-6 leading-relaxed text-lg">Instantly identify high-potential leads with no website. Built-in smart filters surface your hottest prospects automatically.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-4 py-1.5 bg-orange-100 text-orange-700 text-sm font-semibold rounded-full">Smart Filters</span>
                        <span class="px-4 py-1.5 bg-orange-100 text-orange-700 text-sm font-semibold rounded-full">Auto-Scoring</span>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card group p-10 rounded-3xl bg-slate-50 border border-slate-200 hover:border-emerald-300 hover:shadow-2xl hover:shadow-emerald-900/5">
                    <div class="feature-icon w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-emerald-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">AI Email Generation</h3>
                    <p class="text-slate-600 mb-6 leading-relaxed text-lg">Bulk-generate personalized cold emails using OpenRouter, Groq, or Gemini. Customizable tone, length, and personalization.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-4 py-1.5 bg-emerald-100 text-emerald-700 text-sm font-semibold rounded-full">Free AI Models</span>
                        <span class="px-4 py-1.5 bg-emerald-100 text-emerald-700 text-sm font-semibold rounded-full">Auto-Retry</span>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card group p-10 rounded-3xl bg-slate-50 border border-slate-200 hover:border-purple-300 hover:shadow-2xl hover:shadow-purple-900/5">
                    <div class="feature-icon w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-500 rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-purple-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Website Intelligence</h3>
                    <p class="text-slate-600 mb-6 leading-relaxed text-lg">Automatically analyze target websites to extract tech stacks, team information, and pricing data for smarter pitches.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-4 py-1.5 bg-purple-100 text-purple-700 text-sm font-semibold rounded-full">Tech Detection</span>
                        <span class="px-4 py-1.5 bg-purple-100 text-purple-700 text-sm font-semibold rounded-full">Fit Scoring</span>
                    </div>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card group p-10 rounded-3xl bg-slate-50 border border-slate-200 hover:border-rose-300 hover:shadow-2xl hover:shadow-rose-900/5">
                    <div class="feature-icon w-16 h-16 bg-gradient-to-br from-rose-500 to-pink-500 rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-rose-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Conversation Hub</h3>
                    <p class="text-slate-600 mb-6 leading-relaxed text-lg">Unified Gmail-style conversation view with Brevo integration. Automatic inbound webhook handling for seamless replies.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-4 py-1.5 bg-rose-100 text-rose-700 text-sm font-semibold rounded-full">Threaded View</span>
                        <span class="px-4 py-1.5 bg-rose-100 text-rose-700 text-sm font-semibold rounded-full">Webhooks</span>
                    </div>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card group p-10 rounded-3xl bg-slate-50 border border-slate-200 hover:border-cyan-300 hover:shadow-2xl hover:shadow-cyan-900/5">
                    <div class="feature-icon w-16 h-16 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-2xl flex items-center justify-center mb-8 shadow-xl shadow-cyan-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Analytics Dashboard</h3>
                    <p class="text-slate-600 mb-6 leading-relaxed text-lg">Real-time stats, conversion funnels, activity feeds, and email analytics. Beautiful Filament-powered interface.</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-4 py-1.5 bg-cyan-100 text-cyan-700 text-sm font-semibold rounded-full">Live Stats</span>
                        <span class="px-4 py-1.5 bg-cyan-100 text-cyan-700 text-sm font-semibold rounded-full">Funnel Charts</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Technology Stack Section -->
    <section id="technology" class="py-32 bg-slate-900 relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(59,130,246,0.1),transparent_70%)]"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-20">
                <span class="inline-block px-5 py-2 rounded-full bg-blue-900/50 text-blue-300 text-sm font-bold uppercase tracking-wider mb-8 border border-blue-700/50">Technology</span>
                <h2 class="text-5xl sm:text-6xl lg:text-7xl font-black text-white mb-8 leading-tight">
                    Built on<br><span class="gradient-text">Modern Tech</span>
                </h2>
                <p class="text-xl text-slate-400 max-w-3xl mx-auto leading-relaxed">Enterprise-grade infrastructure. Zero vendor lock-in.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="tech-badge p-8 rounded-2xl bg-slate-800/50 border border-slate-700 hover:border-blue-500/50 text-center">
                    <div class="text-3xl font-black text-white mb-2">Laravel 13</div>
                    <div class="text-slate-400 font-medium">PHP Framework</div>
                </div>
                <div class="tech-badge p-8 rounded-2xl bg-slate-800/50 border border-slate-700 hover:border-blue-500/50 text-center">
                    <div class="text-3xl font-black text-white mb-2">Filament 5</div>
                    <div class="text-slate-400 font-medium">Admin Panel</div>
                </div>
                <div class="tech-badge p-8 rounded-2xl bg-slate-800/50 border border-slate-700 hover:border-blue-500/50 text-center">
                    <div class="text-3xl font-black text-white mb-2">Livewire 4</div>
                    <div class="text-slate-400 font-medium">Reactive UI</div>
                </div>
                <div class="tech-badge p-8 rounded-2xl bg-slate-800/50 border border-slate-700 hover:border-blue-500/50 text-center">
                    <div class="text-3xl font-black text-white mb-2">Tailwind 4</div>
                    <div class="text-slate-400 font-medium">Styling</div>
                </div>
                <div class="tech-badge p-8 rounded-2xl bg-slate-800/50 border border-slate-700 hover:border-blue-500/50 text-center">
                    <div class="text-3xl font-black text-white mb-2">MySQL 8</div>
                    <div class="text-slate-400 font-medium">Database</div>
                </div>
                <div class="tech-badge p-8 rounded-2xl bg-slate-800/50 border border-slate-700 hover:border-blue-500/50 text-center">
                    <div class="text-3xl font-black text-white mb-2">Docker</div>
                    <div class="text-slate-400 font-medium">Containerized</div>
                </div>
                <div class="tech-badge p-8 rounded-2xl bg-slate-800/50 border border-slate-700 hover:border-blue-500/50 text-center">
                    <div class="text-3xl font-black text-white mb-2">Kubernetes</div>
                    <div class="text-slate-400 font-medium">Orchestration</div>
                </div>
                <div class="tech-badge p-8 rounded-2xl bg-slate-800/50 border border-slate-700 hover:border-blue-500/50 text-center">
                    <div class="text-3xl font-black text-white mb-2">PWA</div>
                    <div class="text-slate-400 font-medium">Mobile-Ready</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why AllLeads Section -->
    <section class="py-32 bg-white relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-24">
                <span class="inline-block px-5 py-2 rounded-full bg-blue-100 text-blue-700 text-sm font-bold uppercase tracking-wider mb-8">Why Choose Us</span>
                <h2 class="text-5xl sm:text-6xl lg:text-7xl font-black text-slate-900 leading-tight">
                    Why Agencies<br><span class="gradient-text">Choose AllLeads</span>
                </h2>
            </div>

            <div class="space-y-32">
                <!-- Feature 1 -->
                <div class="flex flex-col lg:flex-row gap-16 items-center">
                    <div class="lg:w-1/2">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-100 text-blue-700 text-sm font-bold mb-6">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                            Single-Tenant
                        </div>
                        <h3 class="text-4xl font-black text-slate-900 mb-6">Your Data.<br>Your Server.</h3>
                        <p class="text-xl text-slate-600 mb-8 leading-relaxed">Complete privacy and control. No shared infrastructure, no surprise outages. Run on your own infrastructure with full data ownership.</p>
                        <ul class="space-y-4 text-lg text-slate-700">
                            <li class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Run on your own infrastructure
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Full data ownership
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Custom domain support
                            </li>
                        </ul>
                    </div>
                    <div class="lg:w-1/2">
                        <div class="aspect-square rounded-3xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center shadow-2xl shadow-blue-900/10">
                            <svg class="w-48 h-48 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="flex flex-col lg:flex-row-reverse gap-16 items-center">
                    <div class="lg:w-1/2">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-100 text-amber-700 text-sm font-bold mb-6">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                            AI Scaling
                        </div>
                        <h3 class="text-4xl font-black text-slate-900 mb-6">AI That Scales<br>Freely</h3>
                        <p class="text-xl text-slate-600 mb-8 leading-relaxed">No per-email costs. Use free-tier OpenRouter, Groq, or Google Gemini. Intelligent fallback keeps you moving even under rate limits.</p>
                        <ul class="space-y-4 text-lg text-slate-700">
                            <li class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Zero per-message fees
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Auto-fallback providers
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Scale from 10 to 10,000 emails
                            </li>
                        </ul>
                    </div>
                    <div class="lg:w-1/2">
                        <div class="aspect-square rounded-3xl bg-gradient-to-br from-amber-100 to-amber-50 flex items-center justify-center shadow-2xl shadow-amber-900/10">
                            <svg class="w-48 h-48 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="flex flex-col lg:flex-row gap-16 items-center">
                    <div class="lg:w-1/2">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-100 text-emerald-700 text-sm font-bold mb-6">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Permissions
                        </div>
                        <h3 class="text-4xl font-black text-slate-900 mb-6">Role-Based<br>Access Control</h3>
                        <p class="text-xl text-slate-600 mb-8 leading-relaxed">Powered by Spatie Laravel Permission. Give agents access to their own leads and emails. Keep admins in charge with full audit trails.</p>
                        <ul class="space-y-4 text-lg text-slate-700">
                            <li class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Admin & Agent roles
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Lead ownership controls
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Audit trail built-in
                            </li>
                        </ul>
                    </div>
                    <div class="lg:w-1/2">
                        <div class="aspect-square rounded-3xl bg-gradient-to-br from-emerald-100 to-emerald-50 flex items-center justify-center shadow-2xl shadow-emerald-900/10">
                            <svg class="w-48 h-48 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-32 bg-slate-50 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-20">
                <span class="inline-block px-5 py-2 rounded-full bg-blue-100 text-blue-700 text-sm font-bold uppercase tracking-wider mb-8">Pricing</span>
                <h2 class="text-5xl sm:text-6xl lg:text-7xl font-black text-slate-900 mb-8 leading-tight">
                    Simple,<br><span class="gradient-text">Transparent Pricing</span>
                </h2>
                <p class="text-xl text-slate-600 max-w-3xl mx-auto">Start free. Scale as you grow.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Free Plan -->
                <div class="p-10 rounded-3xl bg-white border border-slate-200 flex flex-col hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-slate-900 mb-2">Free Forever</h3>
                        <p class="text-slate-500">Perfect for solo agencies</p>
                    </div>
                    <div class="text-6xl font-black text-slate-900 mb-8">$0<span class="text-2xl font-medium text-slate-400">/mo</span></div>
                    <ul class="space-y-4 mb-10 flex-grow">
                        <li class="flex items-center gap-3 text-slate-700">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            1 user
                        </li>
                        <li class="flex items-center gap-3 text-slate-700">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Unlimited leads & emails
                        </li>
                        <li class="flex items-center gap-3 text-slate-700">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            AI generation
                        </li>
                        <li class="flex items-center gap-3 text-slate-700">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Email integration
                        </li>
                    </ul>
                    <button class="w-full py-4 rounded-full bg-slate-100 text-slate-900 font-bold hover:bg-slate-200 transition text-lg">Start Free</button>
                </div>

                <!-- Professional Plan -->
                <div class="p-10 rounded-3xl bg-blue-600 text-white border border-blue-500 flex flex-col relative shadow-2xl shadow-blue-900/30 hover:-translate-y-2 transition-all duration-300">
                    <div class="absolute -top-5 left-1/2 -translate-x-1/2 bg-white text-blue-600 px-6 py-2 rounded-full text-sm font-bold shadow-lg">Most Popular</div>
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold mb-2">Professional</h3>
                        <p class="text-blue-100">For growing agencies</p>
                    </div>
                    <div class="text-6xl font-black mb-8">$99<span class="text-2xl font-medium text-blue-200">/mo</span></div>
                    <ul class="space-y-4 mb-10 flex-grow">
                        <li class="flex items-center gap-3 text-white">
                            <svg class="w-5 h-5 text-blue-200 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Up to 3 users
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <svg class="w-5 h-5 text-blue-200 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Everything in Free
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <svg class="w-5 h-5 text-blue-200 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Priority support
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <svg class="w-5 h-5 text-blue-200 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Custom webhooks
                        </li>
                    </ul>
                    <button class="w-full py-4 rounded-full bg-white text-blue-600 font-bold hover:bg-blue-50 transition text-lg shadow-lg">Get Started</button>
                </div>

                <!-- Enterprise Plan -->
                <div class="p-10 rounded-3xl bg-white border border-slate-200 flex flex-col hover:shadow-2xl transition-all duration-300 hover:-translate-y-2">
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-slate-900 mb-2">Enterprise</h3>
                        <p class="text-slate-500">For agencies at scale</p>
                    </div>
                    <div class="text-6xl font-black text-slate-900 mb-8">Custom</div>
                    <ul class="space-y-4 mb-10 flex-grow">
                        <li class="flex items-center gap-3 text-slate-700">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Unlimited users
                        </li>
                        <li class="flex items-center gap-3 text-slate-700">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Everything in Professional
                        </li>
                        <li class="flex items-center gap-3 text-slate-700">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Dedicated support
                        </li>
                        <li class="flex items-center gap-3 text-slate-700">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            SLA guaranteed
                        </li>
                    </ul>
                    <button class="w-full py-4 rounded-full border-2 border-slate-300 text-slate-700 font-bold hover:border-slate-400 hover:bg-slate-50 transition text-lg">Contact Sales</button>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-32 bg-blue-600 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.1),transparent_70%)]"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
            <h2 class="text-5xl sm:text-6xl lg:text-7xl font-black mb-8 leading-tight">
                Ready to Scale Your<br>Lead Generation?
            </h2>
            <p class="text-2xl text-blue-100 mb-12 max-w-3xl mx-auto leading-relaxed">Join agencies that are closing more deals with AI-powered lead intelligence.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('filament.admin.auth.login') }}" class="px-10 py-5 bg-white text-blue-600 rounded-full font-bold text-lg hover:bg-blue-50 transition shadow-2xl">
                    Get Started Free
                </a>
                <a href="https://github.com/ash-rain/allleads" target="_blank" class="px-10 py-5 border-2 border-white text-white rounded-full font-bold text-lg hover:bg-white/10 transition">
                    View on GitHub
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 text-slate-400 py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-white">AllLeads</span>
                    </div>
                    <p class="text-slate-400 leading-relaxed">AI-powered CRM for web development agencies. Built with Laravel, Filament & Tailwind.</p>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-6 text-lg">Product</h4>
                    <ul class="space-y-4 text-slate-400">
                        <li><a href="#features" class="hover:text-white transition">Features</a></li>
                        <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
                        <li><a href="{{ route('filament.admin.auth.login') }}" class="hover:text-white transition">Sign In</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-6 text-lg">Developers</h4>
                    <ul class="space-y-4 text-slate-400">
                        <li><a href="https://github.com/ash-rain/allleads" target="_blank" class="hover:text-white transition">GitHub</a></li>
                        <li><a href="#technology" class="hover:text-white transition">Technology</a></li>
                        <li><a href="#" class="hover:text-white transition">Documentation</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-6 text-lg">Legal</h4>
                    <ul class="space-y-4 text-slate-400">
                        <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-white transition">License</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-10 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-slate-500">&copy; 2026 AllLeads. MIT Licensed.</p>
                <p class="text-slate-500">Engineered for agencies. Built with love.</p>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('shadow-lg');
            } else {
                navbar.classList.remove('shadow-lg');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>