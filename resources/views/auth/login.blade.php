<x-guest-layout>

    <div class="flex flex-col items-center justify-center min-h-screen">
        <div class="font-bold text-6xl text-center">
            F.F
        </div>

        {{-- Pesan Error/Status --}}
        <x-validation-errors class="mb-4" />
        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <h2 class="text-xl font-semibold mb-4 text-center mt-20">Pilih Pengguna untuk Login</h2>

        <div class="container mx-auto px-4 flex justify-center">
            <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 w-full max-w-5xl">
                @foreach ($users as $user)
                    <div class="bg-white p-4 rounded-lg shadow-md cursor-pointer user-card login-button flex flex-col items-center space-y-2"
                        data-user-id="{{ $user->id }}" data-user-email="{{ $user->email }}">
                        <!-- ICON USER -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.121 17.804A9 9 0 1112 21a9 9 0 01-6.879-3.196zM12 14a4 4 0 100-8 4 4 0 000 8z" />
                        </svg>

                        <div class="font-bold text-lg">{{ $user->name }}</div>
                        <div class="text-sm text-gray-600">{{ $user->email }}</div>
                    </div>
                @endforeach
            </div>
        </div>


        {{-- Modal/Pop-up untuk Input Password --}}
        <div id="passwordModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity duration-300 opacity-0 pointer-events-none">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Login untuk <span id="modalUserName"></span></h3>
                <form id="loginForm" method="POST" action="{{ route('login') }}">
                    @csrf
                    <input type="hidden" name="email" id="modalUserEmail">
                    <div>
                        <x-label for="password_modal" value="{{ __('Password') }}" />
                        <x-input id="password_modal" class="block mt-1 w-full" type="password" name="password" required
                            autocomplete="current-password" />
                    </div>
                    <div class="flex items-center justify-end mt-4">
                        <x-button type="submit" class="ms-4">
                            {{ __('Log in') }}
                        </x-button>
                        <button type="button" id="closeModal"
                            class="ms-2 px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Batal</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- JavaScript untuk Mengelola Modal dan Login --}}
        @push('scripts')
            {{-- Asumsi Anda menggunakan stack 'scripts' di layout Anda --}}
            <script>
                document.querySelectorAll('.login-button').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.closest('.user-card').dataset.userId;
                        const userEmail = this.closest('.user-card').dataset.userEmail;
                        const userName = this.closest('.user-card').querySelector('.font-bold').innerText;

                        document.getElementById('modalUserName').innerText = userName;
                        document.getElementById('modalUserEmail').value = userEmail;

                        // tampilkan modal dengan animasi
                        const modal = document.getElementById('passwordModal');
                        modal.classList.remove('opacity-0', 'pointer-events-none');
                    });
                });

                document.getElementById('closeModal').addEventListener('click', function() {
                    const modal = document.getElementById('passwordModal');
                    modal.classList.add('opacity-0', 'pointer-events-none');
                    document.getElementById('password_modal').value = '';
                });

                // tutup modal jika klik di luar konten
                document.getElementById('passwordModal').addEventListener('click', function(event) {
                    if (event.target === this) {
                        this.classList.add('opacity-0', 'pointer-events-none');
                        document.getElementById('password_modal').value = '';
                    }
                });
            </script>
        @endpush

</x-guest-layout>
