<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-8">
            {{-- Header --}}
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Create Account</h1>
                <p class="text-gray-600 mt-2">Set up your WhatsApp Bot Store</p>
            </div>

            {{-- Form --}}
            <form wire:submit="register" class="space-y-6">
                {{-- Name --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Your Name</label>
                    <input
                        wire:model="name"
                        type="text"
                        id="name"
                        class="mt-1 block w-full rounded-lg border-gray-300 border px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="John Doe"
                    />
                    @error('name') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input
                        wire:model="email"
                        type="email"
                        id="email"
                        class="mt-1 block w-full rounded-lg border-gray-300 border px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="you@example.com"
                    />
                    @error('email') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Store Name --}}
                <div>
                    <label for="store_name" class="block text-sm font-medium text-gray-700">Store Name</label>
                    <input
                        wire:model="store_name"
                        type="text"
                        id="store_name"
                        class="mt-1 block w-full rounded-lg border-gray-300 border px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="My Awesome Store"
                    />
                    @error('store_name') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input
                        wire:model="password"
                        type="password"
                        id="password"
                        class="mt-1 block w-full rounded-lg border-gray-300 border px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="••••••••"
                    />
                    @error('password') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Password Confirmation --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input
                        wire:model="password_confirmation"
                        type="password"
                        id="password_confirmation"
                        class="mt-1 block w-full rounded-lg border-gray-300 border px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="••••••••"
                    />
                </div>

                {{-- Terms --}}
                <div class="flex items-center">
                    <input
                        wire:model="agreeToTerms"
                        type="checkbox"
                        id="agreeToTerms"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                    />
                    <label for="agreeToTerms" class="ml-2 block text-sm text-gray-600">
                        I agree to the Terms of Service
                    </label>
                    @error('agreeToTerms') <span class="text-red-500 text-sm ml-2">{{ $message }}</span> @enderror
                </div>

                {{-- Error Message --}}
                @if ($errors->has('general'))
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-700 text-sm">{{ $errors->first('general') }}</p>
                    </div>
                @endif

                {{-- Submit --}}
                <button
                    type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition"
                >
                    Create Account & Store
                </button>
            </form>

            {{-- Login Link --}}
            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm">
                    Already have an account?
                    <a href="{{ route('login') }}" class="text-indigo-600 hover:underline font-medium">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>
