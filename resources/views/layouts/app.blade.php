<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Briefly - AI Summarizer</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              colors: {
                primary: "#4285F4",
                "background-light": "#F8F9FA",
                "background-dark": "#1A1D28", // <-- Warna biru sangat gelap
                "surface-light": "#FFFFFF",
                "surface-dark": "#2E3856",   // <-- Warna kartu yang lebih terang
                "text-light-primary": "#202124",
                "text-light-secondary": "#5F6368",
                "text-dark-primary": "#FFFFFF",   // <-- Teks utama menjadi putih
                "text-dark-secondary": "#A6B2D9", // <-- Teks sekunder menjadi abu-abu kebiruan
                "border-light": "#E0E0E0",
                "border-dark": "#434C70",    // <-- Border yang disesuaikan
            },
              fontFamily: { display: ["Poppins", "sans-serif"] },
              borderRadius: { DEFAULT: "12px" },
            },
          },
        };
    </script>
    <style>.material-icons { font-size: 24px; }</style>
</head>
<body class="font-[Poppins] bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
    <div class="flex h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-white dark:bg-gray-800 flex flex-col p-4 border-r dark:border-gray-700">
            <div class="flex items-center mb-10">
                <div class="bg-blue-600 p-2 rounded-lg mr-3"><span class="material-icons text-white">auto_stories</span></div>
                {{-- Perubahan: Tambah warna teks untuk light/dark mode --}}
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Briefly</h1>
            </div>
            <nav class="flex-grow">
                <ul>
                    {{-- Perubahan: Tambah warna teks untuk light/dark mode pada link --}}
                    <li class="mb-2">
                        <a class="flex items-center p-3 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-white" href="{{ url('/chat') }}">
                            <span class="material-icons mr-4">upload_file</span><span>Upload Document</span>
                        </a>
                    </li>
                    <li class="mb-2">
                        <a class="flex items-center p-3 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-white" href="{{ route('projects.history') }}">
                            <span class="material-icons mr-4">history</span><span>Recent Projects</span>
                        </a>
                    </li>
                     <li class="mb-2">
                        <a class="flex items-center p-3 rounded-lg text-gray-500 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-white" href="{{ route('flashcards.index') }}">
                            <span class="material-icons mr-4">style</span><span>Flashcards</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mt-auto">
                <div class="flex items-center justify-between p-3 rounded-lg mb-4">
                    <div class="flex items-center">
                        <span class="material-icons mr-4 text-gray-500 dark:text-gray-300">dark_mode</span>
                        <span class="text-gray-500 dark:text-gray-300">Dark Mode</span>
                    </div>
                    <button class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors bg-gray-200 dark:bg-gray-700" id="darkModeToggle"><span class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform"></span></button>
                </div>
                <button class="w-full flex items-center p-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
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
</body>
</html>