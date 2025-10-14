<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Briefly - AI Summarizer</title>
    
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    
    {{-- Tailwind CSS & Config --}}
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              colors: {
                primary: "#4285F4",
                "background-light": "#F8F9FA",
                "background-dark": "#1A1D28",
                "surface-light": "#FFFFFF",
                "surface-dark": "#2E3856",
                "text-light-primary": "#202124",
                "text-light-secondary": "#5F6368",
                "text-dark-primary": "#FFFFFF",
                "text-dark-secondary": "#A6B2D9",
                "border-light": "#E0E0E0",
                "border-dark": "#434C70",
              },
              fontFamily: {
                display: ["Poppins", "sans-serif"], // Membuat kelas 'font-display'
              },
              borderRadius: { DEFAULT: "12px" },
            },
          },
        };
    </script>
    <style>
        .material-icons { font-size: 24px; }
        .line-clamp-4 {
           overflow: hidden; display: -webkit-box;
           -webkit-box-orient: vertical; -webkit-line-clamp: 4;
        }
    </style>
</head>
{{-- Menggunakan font dan warna kustom dari config --}}
<body class="font-display bg-background-light dark:bg-background-dark text-text-light-primary dark:text-dark-primary">
    <div class="flex h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-surface-light dark:bg-surface-dark flex flex-col p-4 border-r border-border-light dark:border-border-dark">
            <div class="flex items-center mb-10">
                <div class="bg-primary p-2 rounded-lg mr-3"><span class="material-icons text-white">auto_stories</span></div>
                <h1 class="text-2xl font-bold text-text-light-primary dark:text-dark-primary">Briefly</h1>
            </div>
            <nav class="flex-grow">
                <ul>
                    {{-- Navigasi menggunakan warna kustom --}}
                    <li class="mb-2">
                        <a class="flex items-center p-3 rounded-lg {{ request()->is('chat') ? 'bg-primary/10 text-primary' : 'text-text-light-secondary dark:text-dark-secondary' }} hover:bg-primary/10 hover:text-primary" href="{{ url('/chat') }}">
                            <span class="material-icons mr-4">upload_file</span><span>Upload Document</span>
                        </a>
                    </li>
                    <li class="mb-2">
                         <a class="flex items-center p-3 rounded-lg {{ request()->is('recent-projects') ? 'bg-primary/10 text-primary' : 'text-text-light-secondary dark:text-dark-secondary' }} hover:bg-primary/10 hover:text-primary" href="{{ route('projects.history') }}">
                            <span class="material-icons mr-4">history</span><span>Recent Projects</span>
                        </a>
                    </li>
                     <li class="mb-2">
                        <a class="flex items-center p-3 rounded-lg {{ request()->is('flashcards*') ? 'bg-primary/10 text-primary' : 'text-text-light-secondary dark:text-dark-secondary' }} hover:bg-primary/10 hover:text-primary" href="{{ route('flashcards.index') }}">
                            <span class="material-icons mr-4">style</span><span>Flashcards</span>
                        </a>
                    </li>
                    <li class="mb-2">
                        <a class="flex items-center p-3 rounded-lg {{ request()->is('qna*') ? 'bg-primary/10 text-primary' : 'text-text-light-secondary dark:text-dark-secondary' }} hover:bg-primary/10 hover:text-primary" href="{{ route('qna.index') }}">
                            <span class="material-icons mr-4">quiz</span><span>Q&A Document</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mt-auto">
                {{-- Toggle menggunakan warna kustom --}}
                <div class="flex items-center justify-between p-3 rounded-lg mb-4">
                    <div class="flex items-center">
                        <span class="material-icons mr-4 text-text-light-secondary dark:text-dark-secondary">dark_mode</span>
                        <span class="text-text-light-secondary dark:text-dark-secondary">Dark Mode</span>
                    </div>
                    <button class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors bg-gray-200 dark:bg-gray-700" id="darkModeToggle"><span class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform"></span></button>
                </div>
                <button class="w-full flex items-center p-3 rounded-lg bg-gray-100 dark:bg-surface-dark text-text-light-secondary dark:text-dark-secondary hover:bg-gray-200">
                    <span class="material-icons mr-4">logout</span><span>Logout</span>
                </button>
            </div>
        </aside>

        {{-- Main Content Area --}}
        <main class="flex-1 p-8 overflow-y-auto">
            <header class="flex justify-end items-center mb-8">
                <img alt="User avatar" class="w-10 h-10 rounded-full" src="https://i.pravatar.cc/40"/>
            </header>
            @yield('content')
        </main>
    </div>

    {{-- Dark Mode Script --}}
    <script>
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        if (localStorage.getItem('darkMode') === 'true') { html.classList.add('dark'); }
        darkModeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            if (html.classList.contains('dark')) { localStorage.setItem('darkMode', 'true'); } 
            else { localStorage.setItem('darkMode', 'false'); }
        });
    </script>
    @yield('scripts')
</body>
</html>