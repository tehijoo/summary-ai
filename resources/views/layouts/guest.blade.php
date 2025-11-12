<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    {{-- Menggunakan Head yang sama dengan app.blade.php --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet"/>
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
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <a href="/" class="flex items-center gap-3">
                <span class="material-icons-outlined text-3xl text-primary bg-blue-100 dark:bg-blue-900/50 p-2 rounded-lg">menu_book</span>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ringkaskeun</h1>
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white dark:bg-surface-dark shadow-md overflow-hidden sm:rounded-lg border border-gray-200 dark:border-gray-800">
            {{ $slot }}
        </div>
    </div>
    
    {{-- Skrip Dark Mode (agar halaman login juga mengikuti) --}}
    <script>
        const html = document.documentElement;
        const prefersDark = localStorage.getItem('darkMode') === 'true' || 
                            (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
        if (prefersDark) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
    </script>
</body>
</html>