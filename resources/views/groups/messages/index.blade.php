@extends('layouts.app')
@section('content')
<div class="container">
    <h1>Messages for {{ $group->name }}</h1>
    <ul>
        @foreach($messages as $message)
            <li><strong>{{ $message->user->username }}:</strong> {{ $message->content }}</li>
        @endforeach
    </ul>
    <form method="POST" action="{{ route('groups.messages.store', $group->id) }}">
        @csrf
        <textarea name="content" class="form-control" rows="3" required></textarea>
        <button type="submit" class="btn btn-success mt-2">Send Message</button>
    </form>
</div>
@endsection
