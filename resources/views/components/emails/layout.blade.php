@props([
    'title' => 'ScreenSense Update',
    'previewText' => '',
])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        a { text-decoration: none; }
        .external-link:hover { text-decoration: underline; }
    </style>
</head>
<body class="bg-zinc-50 py-12 px-4 min-h-screen flex justify-center text-zinc-900">
    @if($previewText)
    <!-- Preview text for email clients -->
    <div style="display:none;font-size:1px;color:#333333;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
        {{ $previewText }}
    </div>
    @endif

    <!-- Email Container -->
    <div class="w-full max-w-[600px] bg-white rounded-2xl shadow-sm border border-zinc-200/60 overflow-hidden mx-auto">

        <x-emails.header />

        <!-- Content Body -->
        <div class="px-8 pb-8">
            {{ $slot }}

            <!-- Divider -->
            <div class="h-px w-full bg-gradient-to-r from-transparent via-zinc-200 to-transparent my-8"></div>

            <!-- Signature -->
            <p class="text-sm text-zinc-500 font-normal">
                Best,<br>
                <span class="text-zinc-900 font-medium">The ScreenSense Team</span>
            </p>
        </div>

    </div>

</body>
</html>
