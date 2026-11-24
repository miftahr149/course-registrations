@extends('layouts.app')

@section('title', $section->course->code.' '.$section->section_code)

@section('content')
    <a href="{{ route('teaching.index') }}" class="text-sm text-blue-600 hover:underline">&larr; My sections</a>

    <div class="mb-4 mt-2">
        <h1 class="text-xl font-semibold">{{ $section->course->code }} {{ $section->course->name }}, section {{ $section->section_code }}</h1>
        <p class="text-sm text-gray-600">
            {{ $section->semester->name }} · {{ $section->course->credits }} SKS ·
            {{ $section->schedules->map->label()->join('; ') }}
        </p>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="px-3 py-2">NIM</th>
                    <th class="px-3 py-2">Student</th>
                    <th class="px-3 py-2">Grade</th>
                    <th class="px-3 py-2">Score (0-100)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($enrollments as $enrollment)
                    <tr class="border-t border-gray-100">
                        <td class="px-3 py-2">{{ $enrollment->student->nim }}</td>
                        <td class="px-3 py-2">{{ $enrollment->student->name }}</td>
                        <td class="px-3 py-2 font-medium">
                            {{ $enrollment->grade ? $enrollment->grade->letter.' ('.$enrollment->grade->grade_point.')' : 'Not graded' }}
                        </td>
                        <td class="px-3 py-2">
                            <form method="POST" action="{{ route('grades.update', $enrollment) }}" class="flex gap-2">
                                @csrf
                                @method('PUT')
                                <input type="number" name="score" min="0" max="100" step="0.01" required
                                    value="{{ $enrollment->grade?->score }}" aria-label="Score for {{ $enrollment->student->name }}"
                                    class="w-24 rounded border border-gray-300 px-2 py-1">
                                <button type="submit" class="rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">No students are enrolled.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
