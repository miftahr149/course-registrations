<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title') - {{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
        @auth
            <nav class="border-b border-gray-200 bg-white">
                <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 text-sm">
                    <a href="{{ route('dashboard') }}" class="font-semibold">Course Registration</a>

                    @if (auth()->user()->isStudent())
                        <a href="{{ route('sections.index') }}" class="hover:text-blue-600">Register</a>
                        <a href="{{ route('enrollments.index') }}" class="hover:text-blue-600">My KRS</a>
                        <a href="{{ route('grades.index') }}" class="hover:text-blue-600">My KHS</a>
                    @elseif (auth()->user()->isLecturer())
                        <a href="{{ route('teaching.index') }}" class="hover:text-blue-600">My sections</a>
                        <a href="{{ route('sections.index') }}" class="hover:text-blue-600">All sections</a>
                    @else
                        <a href="{{ route('sections.index') }}" class="hover:text-blue-600">All sections</a>
                    @endif

                    <span class="ml-auto text-gray-500">{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role->value) }})</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-500 hover:text-gray-900">Log out</button>
                    </form>
                </div>
            </nav>
        @endauth

        <main class="mx-auto max-w-5xl px-4 py-8">
            @auth
                @if (session('status'))
                    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">{{ $errors->first() }}</div>
                @endif
            @endauth

            @yield('content')
        </main>
    </body>
</html>
