@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-10">
    <h2 class="text-2xl font-bold mb-6">Dashboard</h2>

    @if(auth()->user()->student_type === 'overwhelmed')
        <p class="text-blue-600 mb-4">Here are students who can help you:</p>
        @foreach($helpers as $student)
            <div class="p-4 mb-2 bg-green-100 rounded">
                {{ $student->name }} - <a href="#" class="text-blue-700 underline">Connect</a>
            </div>
        @endforeach
    @elseif(auth()->user()->student_type === 'helper')
        <p class="text-purple-600 mb-4">Here are students looking for help:</p>
        @foreach($overwhelmed as $student)
            <div class="p-4 mb-2 bg-red-100 rounded">
                {{ $student->name }} - <a href="#" class="text-blue-700 underline">Connect</a>
            </div>
        @endforeach
    @else
        <p>Welcome to SafeSpace!</p>
    @endif
</div>
@endsection
