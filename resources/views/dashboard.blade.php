@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-4 rounded-lg bg-white p-6 shadow">
        <h1 class="text-xl font-semibold">Welcome, {{ $user->name }}</h1>
        <p class="text-sm text-gray-600">You are logged in as <strong>{{ ucfirst($user->role->value) }}</strong> ({{ $user->email }}).</p>

        @if ($user->isStudent())
            <ul class="list-inside list-disc text-sm">
                <li><a class="text-blue-600 hover:underline" href="{{ route('sections.index') }}">Register for sections</a></li>
                <li><a class="text-blue-600 hover:underline" href="{{ route('enrollments.index') }}">My enrollments (KRS)</a></li>
                <li><a class="text-blue-600 hover:underline" href="{{ route('grades.index') }}">My grades (KHS)</a></li>
            </ul>
        @elseif ($user->isLecturer())
            <p class="text-sm">You teach {{ $user->taughtSections()->count() }} section(s).</p>
            <ul class="list-inside list-disc text-sm">
                <li><a class="text-blue-600 hover:underline" href="{{ route('teaching.index') }}">My sections and grading</a></li>
            </ul>
        @endif
    </div>
@endsection
