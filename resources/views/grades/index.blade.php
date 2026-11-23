@extends('layouts.app')

@section('title', 'My KHS')

@section('content')
    <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
        <h1 class="text-xl font-semibold">My grades (KHS)</h1>
        <p class="text-sm">
            GPA: <strong>{{ $gpa === null ? '-' : number_format($gpa, 2) }}</strong>
            · Credit limit next registration: <strong>{{ $creditLimit }} SKS</strong>
        </p>
    </div>

    @forelse ($gradesBySemester as $semesterName => $grades)
        <h2 class="mb-2 mt-6 font-semibold">{{ $semesterName }}</h2>
        <div class="overflow-x-auto rounded-lg bg-white shadow">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-3 py-2">Course</th>
                        <th class="px-3 py-2">SKS</th>
                        <th class="px-3 py-2">Score</th>
                        <th class="px-3 py-2">Letter</th>
                        <th class="px-3 py-2">Point</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grades as $grade)
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2"><span class="font-medium">{{ $grade->enrollment->section->course->code }}</span> {{ $grade->enrollment->section->course->name }}</td>
                            <td class="px-3 py-2">{{ $grade->enrollment->section->course->credits }}</td>
                            <td class="px-3 py-2">{{ $grade->score }}</td>
                            <td class="px-3 py-2 font-medium">{{ $grade->letter }}</td>
                            <td class="px-3 py-2">{{ $grade->grade_point }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="rounded-lg bg-white p-6 text-center text-sm text-gray-500 shadow">No grades yet.</p>
    @endforelse
@endsection
