<x-guest-layout>
    <style>
        :root {
            --primary: 224, 60%, 38%;
            --muted: 220, 10%, 50%;
            --bg: 220, 20%, 97%;
            --card-shadow: 0 1px 3px 0 hsla(224, 30%, 18%, 0.04), 0 4px 16px -2px hsla(224, 30%, 18%, 0.06);
            --card-hover-shadow: 0 4px 12px -2px hsla(224, 30%, 18%, 0.08), 0 8px 32px -4px hsla(224, 30%, 18%, 0.1);
        }

        body {
            background-color: hsl(var(--bg));
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .logo-text {
            font-size: 3rem;
            font-weight: 900;
            letter-spacing: -0.025em;
            text-align: center;
            background: linear-gradient(135deg, hsl(224, 60%, 38%), hsl(260, 50%, 50%));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            text-align: center;
            font-size: 1.125rem;
            font-weight: 500;
            color: hsl(var(--muted));
            margin-bottom: 2.5rem;
        }

        .users-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            max-width: 48rem;
            width: 100%;
        }

        @media (max-width: 640px) {
            .users-grid {
                grid-template-columns: 1fr;
            }
        }

        .user-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            padding: 2rem;
            background: #fff;
            border: 1px solid hsl(220, 16%, 90%);
            border-radius: 0.75rem;
            box-shadow: var(--card-shadow);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .user-card:hover {
            border-color: hsla(224, 60%, 38%, 0.2);
            box-shadow: var(--card-hover-shadow);
        }

        .user-avatar {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 9999px;
            background: hsl(220, 14%, 92%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            font-weight: 600;
            color: hsl(var(--muted));
            transition: all 0.3s ease;
        }

        .user-card:hover .user-avatar {
            background: hsla(224, 60%, 38%, 0.1);
            color: hsl(var(--primary));
        }

        .user-name {
            font-size: 1rem;
            font-weight: 600;
            color: hsl(224, 30%, 18%);
            margin: 0;
        }

        .user-email {
            font-size: 0.875rem;
            color: hsl(var(--muted));
            margin: 0.25rem 0 0;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            transition: opacity 0.2s ease;
        }

        .modal-overlay.opacity-0 {
            opacity: 0;
            pointer-events: none;
        }

        .modal-content {
            background: #fff;
            border-radius: 0.75rem;
            padding: 2rem;
            width: 100%;
            max-width: 24rem;
            box-shadow: var(--card-hover-shadow);
            transform: scale(1);
            transition: transform 0.2s ease;
        }

        .modal-overlay.opacity-0 .modal-content {
            transform: scale(0.95);
        }

        .modal-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 2rem;
        }

        .modal-avatar {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 9999px;
            background: hsla(224, 60%, 38%, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            font-weight: 600;
            color: hsl(var(--primary));
            margin-bottom: 0.75rem;
        }

        .modal-name {
            font-size: 1rem;
            font-weight: 600;
            color: hsl(224, 30%, 18%);
            margin: 0;
        }

        .modal-email {
            font-size: 0.875rem;
            color: hsl(var(--muted));
            margin: 0;
        }

        .password-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }

        .password-input {
            width: 100%;
            height: 2.75rem;
            padding: 0 2.5rem 0 0.75rem;
            border: 1px solid hsl(220, 16%, 90%);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .password-input:focus {
            border-color: hsl(var(--primary));
            box-shadow: 0 0 0 2px hsla(224, 60%, 38%, 0.15);
        }

        .toggle-password {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: hsl(var(--muted));
            cursor: pointer;
            padding: 0;
            display: flex;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: hsl(224, 30%, 18%);
        }

        .btn-login {
            width: 100%;
            height: 2.75rem;
            background: hsl(var(--primary));
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: opacity 0.2s;
        }

        .btn-login:hover {
            opacity: 0.9;
        }

        .btn-login:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-cancel {
            width: 100%;
            height: 2.75rem;
            background: transparent;
            color: hsl(var(--muted));
            border: 1px solid hsl(220, 16%, 90%);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: hsl(220, 14%, 92%);
        }

        /* Status/Error Messages */
        .status-message {
            text-align: center;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background: hsla(224, 60%, 38%, 0.08);
            color: hsl(var(--primary));
            max-width: 48rem;
            width: 100%;
        }
    </style>

    <div class="login-container">
        <div style="width:100%;max-width:48rem;">
            <h1 class="logo-text">F.F</h1>

            {{-- Pesan Error/Status --}}
            @session('status')
                <div class="status-message">{{ $value }}</div>
            @endsession

            <p class="subtitle">Pilih Pengguna untuk Login</p>

            <div class="users-grid">
                @foreach ($users as $user)
                    <div class="user-card login-button" data-user-id="{{ $user->id }}"
                        data-user-email="{{ $user->email }}"
                        data-user-initials="{{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->name)[1] ?? '', 0, 1)) }}">
                        <div class="user-avatar">
                            {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->name)[1] ?? '', 0, 1)) }}
                        </div>
                        <div>
                            <p class="user-name">{{ $user->name }}</p>
                            <p class="user-email">{{ $user->email }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Modal/Pop-up untuk Input Password --}}
        <div id="passwordModal" class="modal-overlay opacity-0">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-avatar" id="modalInitials"></div>
                    <p class="modal-name" id="modalUserName"></p>
                    <p class="modal-email" id="modalUserEmailDisplay"></p>
                </div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <input type="hidden" name="email" id="modalUserEmail">

                    <div class="password-wrapper">
                        <input type="password" name="password" id="password_modal" class="password-input"
                            placeholder="Masukkan password" required>
                        <button type="button" class="toggle-password" id="togglePassword">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <svg id="eyeOffIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" style="display:none">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                <path
                                    d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                <line x1="2" x2="22" y1="2" y2="22" />
                            </svg>
                        </button>
                    </div>

                    <button type="submit" class="btn-login">
                        Masuk
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="m12 5 7 7-7 7" />
                        </svg>
                    </button>
                    <button type="button" class="btn-cancel" id="closeModal">Batal</button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('.login-button').forEach(button => {
                button.addEventListener('click', function() {
                    const userEmail = this.dataset.userEmail;
                    const userName = this.querySelector('.user-name').innerText;
                    const initials = this.dataset.userInitials;

                    document.getElementById('modalUserName').innerText = userName;
                    document.getElementById('modalUserEmailDisplay').innerText = userEmail;
                    document.getElementById('modalUserEmail').value = userEmail;
                    document.getElementById('modalInitials').innerText = initials;

                    const modal = document.getElementById('passwordModal');
                    modal.classList.remove('opacity-0');
                    document.getElementById('password_modal').focus();
                });
            });

            document.getElementById('closeModal').addEventListener('click', function() {
                const modal = document.getElementById('passwordModal');
                modal.classList.add('opacity-0');
                document.getElementById('password_modal').value = '';
            });

            document.getElementById('passwordModal').addEventListener('click', function(event) {
                if (event.target === this) {
                    this.classList.add('opacity-0');
                    document.getElementById('password_modal').value = '';
                }
            });

            document.getElementById('togglePassword').addEventListener('click', function() {
                const input = document.getElementById('password_modal');
                const eyeIcon = document.getElementById('eyeIcon');
                const eyeOffIcon = document.getElementById('eyeOffIcon');
                if (input.type === 'password') {
                    input.type = 'text';
                    eyeIcon.style.display = 'none';
                    eyeOffIcon.style.display = 'block';
                } else {
                    input.type = 'password';
                    eyeIcon.style.display = 'block';
                    eyeOffIcon.style.display = 'none';
                }
            });
        </script>
    @endpush
</x-guest-layout>
