<x-admin-layout>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Edit Permission</h1>
                <a href="{{ route('admin.permissions.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Permissions
                </a>
            </div>

            <form action="{{ route('admin.permissions.update', $permission) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Permission Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $permission->name) }}" required
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="e.g., create users">
                    <p class="mt-1 text-sm text-gray-500">Use lowercase with spaces or underscores (e.g., "create users" or "create_users")</p>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Update Permission
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
