@extends('layouts.app')

@section('title', 'My sections')

@section('content')
    <h1 class="mb-4 text-xl font-semibold">My sections</h1>

    @forelse ($sectionsBySemester as $semesterName => $sections)
        <h2 class="mb-2 mt-6 font-semibold">{{ $semesterName }}</h2>
        <div class="overflow-x-auto rounded-lg bg-white shadow">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-3 py-2">Course</th>
                        <th class="px-3 py-2">Section</th>
                        <th class="px-3 py-2">Schedule</th>
                        <th class="px-3 py-2">Students</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sections as $section)
                        <tr class="border-t border-gray-100 align-top">
                            <td class="px-3 py-2"><span class="font-medium">{{ $section->course->code }}</span> {{ $section->course->name }}</td>
                            <td class="px-3 py-2">{{ $section->section_code }}</td>
                            <td class="px-3 py-2">
                                @foreach ($section->schedules as $schedule)
                                    <div>{{ $schedule->label() }}</div>
                                @endforeach
                            </td>
                            <td class="px-3 py-2">{{ $section->enrolled_count }} / {{ $section->capacity }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('teaching.show', $section) }}" class="rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700">Students &amp; grades</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow">You don't teach any section.</p>
    @endforelse
@endsection
