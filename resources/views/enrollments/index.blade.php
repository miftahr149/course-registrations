@extends('layouts.app')

@section('title', 'My KRS')

@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
        <div>
            <h1 class="text-xl font-semibold">My enrollments (KRS)</h1>
            <p class="text-sm text-gray-600">{{ $semester?->name ?? 'No semester is open for registration.' }}</p>
        </div>
        <p class="text-sm">Credits taken: <strong>{{ $creditsTaken }} / {{ $creditLimit }} SKS</strong></p>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-3 py-2">Course</th>
                    <th class="px-3 py-2">SKS</th>
                    <th class="px-3 py-2">Section</th>
                    <th class="px-3 py-2">Lecturer</th>
                    <th class="px-3 py-2">Schedule</th>
                    <th class="px-3 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($enrollments as $enrollment)
                    <tr class="border-t border-gray-100 align-top">
                        <td class="px-3 py-2"><span class="font-medium">{{ $enrollment->section->course->code }}</span> {{ $enrollment->section->course->name }}</td>
                        <td class="px-3 py-2">{{ $enrollment->section->course->credits }}</td>
                        <td class="px-3 py-2">{{ $enrollment->section->section_code }}</td>
                        <td class="px-3 py-2">{{ $enrollment->section->lecturer->name }}</td>
                        <td class="px-3 py-2">
                            @foreach ($enrollment->section->schedules as $schedule)
                                <div>{{ $schedule->label() }}</div>
                            @endforeach
                        </td>
                        <td class="px-3 py-2 text-right">
                            <form method="POST" action="{{ route('enrollments.destroy', $enrollment) }}" onsubmit="return confirm('Drop this section?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded bg-red-600 px-3 py-1 text-white hover:bg-red-700">Drop</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">
                            You are not enrolled in any section yet. <a class="text-blue-600 hover:underline" href="{{ route('sections.index') }}">Browse sections</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
