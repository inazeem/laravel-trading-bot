<x-admin-layout>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-green-600 to-emerald-600 rounded-2xl shadow-xl">
            <div class="px-8 py-8 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">Roles Management</h1>
                        <p class="text-green-100">Define and manage user roles with specific permissions</p>
                    </div>
                    @can('create roles')
                    <a href="{{ route('admin.roles.create') }}" class="bg-white/20 hover:bg-white/30 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add New Role
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Roles</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $roles->total() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Permissions</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $roles->sum('permissions_count') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $roles->sum('users_count') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles Grid -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">All Roles</h2>
                    <div class="flex items-center space-x-2">
                        <div class="relative">
                            <input type="text" placeholder="Search roles..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($roles as $role)
                    <div class="bg-gray-50 rounded-xl p-6 hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 border border-gray-200">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold text-lg">{{ substr($role->name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $role->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $role->permissions->count() }} permissions</p>
                                </div>
                            </div>
                            <div class="relative">
                                <button class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <!-- Permissions -->
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Permissions</p>
                                <div class="flex flex-wrap gap-1">
                                    @forelse($role->permissions->take(3) as $permission)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $permission->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-500">No permissions</span>
                                    @endforelse
                                    @if($role->permissions->count() > 3)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            +{{ $role->permissions->count() - 3 }} more
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Users Count -->
                            <div class="flex items-center text-xs text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                {{ $role->users_count ?? 0 }} users assigned
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center space-x-2 pt-3 border-t border-gray-200">
                                @can('edit roles')
                                <a href="{{ route('admin.roles.edit', $role) }}" class="flex-1 bg-green-500 hover:bg-green-600 text-white text-sm font-medium py-2 px-3 rounded-lg transition-colors duration-200 text-center">
                                    Edit
                                </a>
                                @endcan
                                @can('delete roles')
                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="flex-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white text-sm font-medium py-2 px-3 rounded-lg transition-colors duration-200" onclick="return confirm('Are you sure you want to delete this role?')">
                                        Delete
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($roles->hasPages())
                <div class="mt-8">
                    {{ $roles->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
