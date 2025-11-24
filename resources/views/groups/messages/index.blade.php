@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto mt-10">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold text-blue-600 mb-6">Group Chat: {{ $group->name }}</h1>
        <div class="mb-6 max-h-96 overflow-y-auto bg-gray-50 rounded-lg p-4 border border-gray-200">
            <ul class="space-y-4">
                @foreach($messages as $message)
                    <li class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-200 rounded-full flex items-center justify-center font-bold text-blue-700">
                            {{ strtoupper(substr($message->user->username, 0, 2)) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <div class="text-sm text-gray-700 font-semibold">{{ $message->user->username }}</div>
                                @if(auth()->id() === $message->user_id || $group->isAdmin(auth()->id()) || (auth()->user()->role ?? '') === 'moderator')
                                    <form method="POST" action="{{ route('groups.messages.destroy', [$group->id, $message->id]) }}" onsubmit="return confirm('Delete this message?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-2 p-1 rounded-full hover:bg-red-50 text-gray-400 hover:text-red-500 transition duration-150 ease-in-out" title="Delete message">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <div class="bg-blue-100 text-gray-800 rounded-lg px-4 py-2 inline-block shadow-sm">{{ $message->content }}</div>
                            <div class="text-xs text-gray-400 mt-1">{{ $message->created_at->diffForHumans() }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
        
        <!-- WhatsApp-style message input -->
        <form method="POST" action="{{ route('groups.messages.store', $group->id) }}" class="flex items-center gap-2 bg-white p-2 rounded-full border border-gray-300 shadow-sm">
            @csrf
            <textarea name="content" class="flex-1 bg-gray-50 rounded-full px-4 py-2 focus:outline-none resize-none text-gray-800 placeholder-gray-500 border-0" rows="1" required placeholder="Type a message" style="min-height: 40px; max-height: 120px;"></textarea>
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white rounded-full p-3 shadow-md transition duration-150 ease-in-out flex items-center justify-center" title="Send">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </button>
        </form>
    </div>
</div>
@endsection