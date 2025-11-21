@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto mt-8">
    <div class="bg-white rounded-lg shadow-md p-8">
        <h1 class="text-3xl font-bold text-blue-600 mb-2">{{ $group->name }}</h1>
        <p class="text-gray-600 mb-6">{{ $group->description }}</p>

        <h2 class="text-xl font-semibold text-blue-500 mb-2">Group Settings</h2>
        <form method="POST" action="{{ route('groups.update', $group->id) }}" class="mb-4">
            @csrf
            @method('PUT')
            <label class="block mb-1">Name:</label>
            <input type="text" name="name" value="{{ $group->name }}" class="form-control mb-2 border rounded px-3 py-2" required>
            <label class="block mb-1">Description:</label>
            <textarea name="description" class="form-control mb-2 border rounded px-3 py-2" required>{{ $group->description }}</textarea>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Update Group</button>
        </form>
        <form method="POST" action="{{ route('groups.destroy', $group->id) }}" onsubmit="return confirm('Are you sure you want to delete this group?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Delete Group</button>
        </form>

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

        <form method="POST" action="{{ route('groups.users.store', $group->id) }}" class="mt-4">
            @csrf
            <label class="block mb-1">Add Member by Username:</label>
            <input type="text" name="username" class="form-control mb-2 border rounded px-3 py-2" required>
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Add Member</button>
        </form>

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