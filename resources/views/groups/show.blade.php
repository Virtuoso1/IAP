@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto mt-8">
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif
    <div class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-blue-600 mb-2">{{ $group->name }}</h1>
        <p class="text-gray-600 mb-6">{{ $group->description }}</p>

        <h2 class="text-xl font-semibold text-blue-500 mb-2">Group Settings</h2>
        <div class="flex items-center gap-6 mb-4">
            <div class="flex flex-col gap-4">
                <form method="POST" action="{{ route('groups.update', $group->id) }}" class="flex items-center">
                    @csrf
                    @method('PUT')
                    <button type="submit" title="Edit Group" class="p-3 bg-gray-200 hover:bg-blue-500 text-blue-600 hover:text-white rounded-full shadow transition flex items-center justify-center mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.293-6.293a1 1 0 011.414 0l1.586 1.586a1 1 0 010 1.414L11 15H9v-2z" /></svg>
                    </button>
                    <span class="text-base text-gray-700">Edit Group</span>
                </form>
                <form method="POST" action="{{ route('groups.destroy', $group->id) }}" onsubmit="return confirm('Are you sure you want to delete this group?');" class="flex items-center">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Delete Group" class="p-3 bg-gray-200 hover:bg-red-500 text-red-600 hover:text-white rounded-full shadow transition flex items-center justify-center mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" /></svg>
                    </button>
                    <span class="text-base text-gray-700">Delete Group</span>
                </form>
            </div>
            <div class="flex-1">
                <form method="POST" action="{{ route('groups.update', $group->id) }}" class="flex flex-col">
                    @csrf
                    @method('PUT')
                    <label class="block mb-1">Name:</label>
                    <input type="text" name="name" value="{{ $group->name }}" class="form-control mb-2 border rounded px-3 py-2" required>
                    <label class="block mb-1">Description:</label>
                    <textarea name="description" class="form-control mb-2 border rounded px-3 py-2" required>{{ $group->description }}</textarea>
                </form>
            </div>
        </div>

        <h2 class="text-xl font-semibold text-blue-500 mt-6 mb-2">Members</h2>
        <ul class="divide-y divide-gray-200">
            @foreach($group->members as $user)
                <li class="py-2 flex items-center justify-between">
                    <span>{{ $user->username }} <span class="text-gray-500">({{ $user->pivot->role }})</span></span>
                    <form method="POST" action="{{ route('groups.users.destroy', [$group->id, $user->id]) }}" style="display:inline;" onsubmit="return confirm('Remove this member?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">Remove</button>
                    </form>
                </li>
            @endforeach
        </ul>

        @if(!$group->members->contains(auth()->id()))
            <form method="POST" action="{{ route('groups.users.store', $group->id) }}" class="mt-4">
                @csrf
                <input type="hidden" name="username" value="{{ auth()->user()->username }}">
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Join Group</button>
            </form>
        @endif

        <h2 class="text-xl font-semibold text-blue-500 mt-6 mb-2">Messages</h2>
        <ul class="divide-y divide-gray-200 mb-4">
            @foreach($group->messages as $message)
                <li class="py-2 flex items-center justify-between">
                    <div>
                        <strong class="text-blue-700">{{ $message->user->username }}:</strong> {{ $message->content }}
                    </div>
                    @if($message->user_id === auth()->id())
                        <div class="flex gap-2">
                            <a href="{{ route('groups.messages.edit', [$group->id, $message->id]) }}" class="bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1 rounded text-xs">Edit</a>
                            <form method="POST" action="{{ route('groups.messages.destroy', [$group->id, $message->id]) }}" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">Delete</button>
                            </form>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
        <form method="POST" action="{{ route('groups.messages.store', $group->id) }}">
            @csrf
            <textarea name="content" class="form-control border rounded px-3 py-2" rows="3" required></textarea>
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded mt-2">Send Message</button>
        </form>
    </div>
</div>
@endsection