<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ringkaskeun - AI Summarizer</title>

    {{-- Fonts & Icons --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet"/>
    
    {{-- Konfigurasi Tailwind (termasuk 'glow') --}}
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#3B82F6",
                        "background-light": "#F9FAFB",
                        "background-dark": "#0D1117",
                        "surface-dark": "#161B22",
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.5rem",
                    },
                    boxShadow: {
                        'glow': '0 0 15px 5px rgba(59, 130, 246, 0.2)',
                    },
                    animation: {
                        'pulse-glow': 'pulse-glow 3s infinite ease-in-out',
                    },
                    keyframes: {
                        'pulse-glow': {
                            '0%, 100%': { boxShadow: '0 0 15px 5px rgba(59, 130, 246, 0.15)' },
                            '50%': { boxShadow: '0 0 25px 8px rgba(59, 130, 246, 0.3)' },
                        }
                    }
                },
            },
        };
    </script>
    <style>
        .material-icons-outlined {
            font-size: 20px;
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-gray-800 dark:text-gray-200 antialiased">
<div class="flex h-screen">

    {{-- Sidebar --}}
    <aside id="sidebar" class="w-64 flex-shrink-0 bg-white dark:bg-surface-dark p-6 flex flex-col border-r border-gray-200 dark:border-gray-800 absolute lg:relative z-30 -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out h-screen overflow-y-auto">
        <div>
            {{-- Header Sidebar (dengan tombol close) --}}
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center gap-3">
                    <span class="material-icons-outlined text-3xl text-primary bg-blue-100 dark:bg-blue-900/50 p-2 rounded-lg">menu_book</span>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Ringkaskeun</h1>
                </div>
                <button id="sidebar-close-btn" class="lg:hidden p-1 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <span class="material-icons-outlined">close</span>
                </button>
            </div>
            
            {{-- Navigasi Link Halaman --}}
            <nav class="space-y-2">
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->is('chat') ? 'bg-blue-100 dark:bg-primary/20 text-primary font-semibold' : 'text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-100 dark:hover:bg-gray-800' }}" href="{{ url('/chat') }}">
                    <span class="material-icons-outlined">upload_file</span>
                    <span>Upload Document</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->is('recent-projects') ? 'bg-blue-100 dark:bg-primary/20 text-primary font-semibold' : 'text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-100 dark:hover:bg-gray-800' }}" href="{{ route('projects.history') }}">
                    <span class="material-icons-outlined">history</span>
                    <span>Recent Projects</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->is('flashcards*') ? 'bg-blue-100 dark:bg-primary/20 text-primary font-semibold' : 'text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-100 dark:hover:bg-gray-800' }}" href="{{ route('flashcards.index') }}">
                    <span class="material-icons-outlined">style</span>
                    <span>Flashcards</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->is('qna*') ? 'bg-blue-100 dark:bg-primary/20 text-primary font-semibold' : 'text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-100 dark:hover:bg-gray-800' }}" href="{{ route('qna.index') }}">
                    <span class="material-icons-outlined">quiz</span>
                    <span>Q&A Document</span>
                </a>
            </nav>
        </div>

        {{-- Bagian Bawah Sidebar (Dark Mode & Auth) --}}
        <div class="space-y-4">
            {{-- Dark Mode Toggle --}}
            <div class="flex items-center justify-between p-2 rounded-lg">
                <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400 font-medium">
                    <span class="material-icons-outlined">dark_mode</span>
                    <span>Dark Mode</span>
                </div>
                <button id="darkModeToggle" aria-checked="false" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 bg-gray-200" role="switch" type="button">
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out translate-x-0"></span>
                </button>
            </div>

            {{-- **LOGIKA LOGIN/LOGOUT (dari Breeze)** --}}
            @auth
                {{-- Tampil jika pengguna SUDAH LOGIN --}}
                <div class="px-4 py-2.5 rounded-lg text-gray-500 dark:text-gray-400 font-medium">
                    <div class="font-semibold text-gray-800 dark:text-white">{{ Auth::user()->name }}</div>
                    <div class="text-sm">{{ Auth::user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); this.closest('form').submit();"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium">
                        <span class="material-icons-outlined">logout</span>
                        <span>Logout</span>
                    </a>
                </form>
            @else
                {{-- Tampil jika pengguna BELUM LOGIN (Guest) --}}
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg bg-primary/10 text-primary font-semibold" href="{{ route('login') }}">
                    <span class="material-icons-outlined">login</span>
                    <span>Login</span>
                </a>
                <a class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-500 dark:text-gray-400 font-medium hover:bg-gray-100 dark:hover:bg-gray-800" href="{{ route('register') }}">
                    <span class="material-icons-outlined">person_add</span>
                    <span>Register</span>
                </a>
            @endauth
        </div>
    </aside>

    {{-- Overlay (untuk mobile) --}}
    <div id="sidebar-overlay" class="lg:hidden fixed inset-0 z-20 bg-black/50 opacity-0 invisible transition-opacity duration-300 ease-in-out"></div>

    {{-- Konten Utama --}}
    <main class="flex-1 overflow-y-auto lg:ml-64 p-6">
        <header class="flex justify-between items-center mb-8">
            {{-- Tombol Hamburger (untuk mobile) --}}
            <button id="hamburger-btn" class="lg:hidden p-2 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                <span class="material-icons-outlined">menu</span>
            </button>
            <img alt="User avatar" class="w-10 h-10 rounded-full lg:ml-auto" src="https://i.pravatar.cc/40"/>
        </header>
        
        @yield('content') {{-- Ini adalah tempat konten halaman (chat.blade.php, dll) akan dimasukkan --}}
    </main>
</div>

{{-- Skrip untuk Dark Mode Toggle --}}
<script>
    const darkModeToggle = document.getElementById('darkModeToggle');
    const html = document.documentElement;

    function setToggleState(isDark) {
        if (isDark) {
            html.classList.add('dark');
            darkModeToggle.setAttribute('aria-checked', 'true');
            darkModeToggle.classList.add('dark:bg-primary');
            darkModeToggle.querySelector('span').classList.add('dark:translate-x-5');
        } else {
            html.classList.remove('dark');
            darkModeToggle.setAttribute('aria-checked', 'false');
            darkModeToggle.classList.remove('dark:bg-primary');
            darkModeToggle.querySelector('span').classList.remove('dark:translate-x-5');
        }
    }

    const prefersDark = localStorage.getItem('darkMode') === 'true' || 
                        (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
    setToggleState(prefersDark);

    darkModeToggle.addEventListener('click', () => {
        const isDark = html.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark);
        setToggleState(isDark);
    });
</script>

{{-- Skrip untuk Navigasi Responsive --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const closeBtn = document.getElementById('sidebar-close-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        const openSidebar = () => {
            if (sidebar) sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.remove('invisible', 'opacity-0');
        };

        const closeSidebar = () => {
            if (sidebar) sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('invisible', 'opacity-0');
        };

        if (hamburgerBtn) hamburgerBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
    });
</script>

@yield('scripts') {{-- Tempat untuk script khusus halaman --}}

</body>
</html>