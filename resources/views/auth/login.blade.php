@extends('layouts.app')

@section('title', 'Log in')

@section('content')
<div class="mx-auto max-w-md pt-8">
    <h1 class="mb-6 text-center text-2xl font-semibold">Course Registration</h1>

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4 rounded-lg bg-white p-6 shadow">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                class="mt-1 w-full rounded border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>

        <button type="submit" class="w-full rounded bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700">
            Log in
        </button>
    </form>

    @if ($demoAccounts)
        <section class="mt-6 rounded-lg border border-dashed border-gray-300 bg-white p-4 text-sm">
            <h2 class="mb-2 font-semibold">Demo accounts</h2>
            <p class="mb-2 text-gray-600">Run <code>php artisan migrate:fresh --seed</code>. Every password is <code>{{ $demoPassword }}</code>.</p>
            <table class="w-full text-left">
                <thead>
                    <tr class="text-gray-500"><th class="py-1">Role</th><th>Name</th><th>Email</th></tr>
                </thead>
                <tbody>
                    @foreach ($demoAccounts as $account)
                        <tr class="border-t border-gray-100">
                            <td class="py-1">{{ $account['role'] }}</td>
                            <td>{{ $account['name'] }}</td>
                            <td><code>{{ $account['email'] }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</div>
@endsection
