@extends('layouts.app')

@section('title', 'Sections')

@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
        <div>
            <h1 class="text-xl font-semibold">Open sections</h1>
            <p class="text-sm text-gray-600">{{ $semester?->name ?? 'No semester is open for registration.' }}</p>
        </div>
        @if ($creditLimit !== null)
            <p class="text-sm">Credits taken: <strong>{{ $creditsTaken }} / {{ $creditLimit }} SKS</strong></p>
        @endif
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
                    <th class="px-3 py-2">Seats</th>
                    @if ($creditLimit !== null)
                        <th class="px-3 py-2"></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($sections as $section)
                    <tr class="border-t border-gray-100 align-top">
                        <td class="px-3 py-2">
                            <span class="font-medium">{{ $section->course->code }}</span> {{ $section->course->name }}
                            @if ($section->course->prerequisite)
                                <div class="text-xs text-gray-500">Requires {{ $section->course->prerequisite->code }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $section->course->credits }}</td>
                        <td class="px-3 py-2">{{ $section->section_code }}</td>
                        <td class="px-3 py-2">{{ $section->lecturer->name }}</td>
                        <td class="px-3 py-2">
                            @foreach ($section->schedules as $schedule)
                                <div>{{ $schedule->label() }}</div>
                            @endforeach
                        </td>
                        <td class="px-3 py-2 {{ $section->enrolled_count >= $section->capacity ? 'text-red-600' : '' }}">
                            {{ $section->enrolled_count }} / {{ $section->capacity }}
                        </td>
                        @if ($creditLimit !== null)
                            <td class="px-3 py-2 text-right">
                                @if (in_array($section->id, $enrolledSectionIds, true))
                                    <span class="rounded bg-green-100 px-2 py-1 text-xs text-green-800">Enrolled</span>
                                @else
                                    <form method="POST" action="{{ route('enrollments.store', $section) }}">
                                        @csrf
                                        <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700">Enroll</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">No sections are open.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
