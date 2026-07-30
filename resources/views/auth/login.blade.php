<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Grosir Felicia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ["Inter", "ui-sans-serif", "system-ui", "sans-serif"],
                    },
                    colors: {
                        ink: {
                            50: "#f7f7f7",
                            100: "#efefef",
                            200: "#dcdcdc",
                            300: "#bdbdbd",
                            400: "#989898",
                            500: "#7c7c7c",
                            600: "#656565",
                            700: "#525252",
                            800: "#3f3f3f",
                            900: "#171717",
                        },
                    },
                    boxShadow: {
                        soft: "0 8px 30px rgba(0, 0, 0, 0.08)",
                    },
                },
            },
        };
    </script>
    <style>
        .user-card {
            opacity: 0;
            transform: translateY(10px) scale(0.98);
            animation: cardEnter 420ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
            will-change: transform, opacity;
        }

        .user-card:active {
            transform: scale(0.98);
        }

        #password-modal {
            opacity: 0;
            transition: opacity 220ms ease;
        }

        #password-modal .modal-panel {
            opacity: 0;
            transform: translateY(12px) scale(0.98);
            transition: transform 260ms cubic-bezier(0.22, 1, 0.36, 1), opacity 220ms ease;
            will-change: transform, opacity;
        }

        #password-modal.modal-open {
            opacity: 1;
        }

        #password-modal.modal-open .modal-panel {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        #password-modal.modal-closing {
            opacity: 0;
        }

        #password-modal.modal-closing .modal-panel {
            opacity: 0;
            transform: translateY(8px) scale(0.98);
        }

        @keyframes cardEnter {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .user-card,
            #password-modal,
            #password-modal .modal-panel {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-ink-50 text-ink-900 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-8 sm:px-6 lg:px-8">
        <header class="mb-8 flex flex-col gap-3">
            <p class="text-sm font-medium uppercase tracking-[0.2em] text-ink-500">Grosir Felicia</p>
            <h1 class="text-2xl font-semibold sm:text-3xl">Pilih Profil untuk Masuk</h1>
            <p class="max-w-2xl text-sm text-ink-600 sm:text-base">
                Klik profil Anda, masukkan password, lalu lanjut ke halaman kasir.
            </p>
        </header>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <section id="user-grid" class="grid grid-cols-1 gap-4 pb-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <!-- User cards injected by JavaScript -->
        </section>
    </main>

    <div id="password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/45 p-4">
        <div class="modal-panel w-full max-w-md rounded-2xl border border-ink-200 bg-white p-5 shadow-soft sm:p-6">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Konfirmasi Password</h2>
                    <p id="modal-user-email" class="text-sm text-ink-500"></p>
                </div>
                <button id="close-modal-button" type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-xl border border-ink-200 text-ink-600 transition hover:bg-ink-100"
                    aria-label="Tutup modal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            </div>

            <div class="mb-3 flex items-center gap-3 rounded-xl bg-ink-100 p-3">
                <div id="modal-avatar"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ink-900 text-sm font-semibold text-white">
                </div>
                <div>
                    <p id="modal-user-name" class="font-medium"></p>
                    <p class="text-xs text-ink-500">Silakan masukkan password akun Anda.</p>
                </div>
            </div>

            <form id="password-form" class="space-y-3">
                <label for="password-input" class="block text-sm font-medium">Password</label>
                <div class="relative">
                    <input id="password-input" name="password" type="password" required autocomplete="current-password"
                        class="w-full rounded-xl border border-ink-300 bg-white px-4 py-2.5 pr-12 text-sm outline-none transition focus:border-ink-900"
                        placeholder="Masukkan password">
                    <button id="toggle-password-button" type="button"
                        class="absolute inset-y-0 right-2 my-auto flex h-8 w-8 items-center justify-center rounded-lg text-ink-600 transition hover:bg-ink-100"
                        aria-label="Tampilkan atau sembunyikan password">
                        <svg id="eye-open-icon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg id="eye-off-icon" xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.585 10.587A2 2 0 0012 14a2 2 0 001.413-3.415" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.878 5.092A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.051 10.051 0 01-4.133 5.411M6.102 6.1A9.958 9.958 0 002.458 12c1.274 4.057 5.065 7 9.542 7a9.95 9.95 0 005.205-1.462" />
                        </svg>
                    </button>
                </div>

                <p id="error-message"
                    class="hidden rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    Password yang Anda masukkan salah!
                </p>

                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button id="cancel-login-button" type="button"
                        class="rounded-xl border border-ink-300 px-4 py-2.5 text-sm font-medium transition hover:bg-ink-100">
                        Batal
                    </button>
                    <button type="submit"
                        class="rounded-xl bg-ink-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-black">
                        Login
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const users = @json(\App\Models\User::select('id', 'name', 'email')->get());

        const userGrid = document.getElementById("user-grid");
        const modal = document.getElementById("password-modal");
        const closeModalButton = document.getElementById("close-modal-button");
        const cancelLoginButton = document.getElementById("cancel-login-button");
        const passwordForm = document.getElementById("password-form");
        const passwordInput = document.getElementById("password-input");
        const togglePasswordButton = document.getElementById("toggle-password-button");
        const errorMessage = document.getElementById("error-message");
        const modalUserName = document.getElementById("modal-user-name");
        const modalUserEmail = document.getElementById("modal-user-email");
        const modalAvatar = document.getElementById("modal-avatar");
        const eyeOpenIcon = document.getElementById("eye-open-icon");
        const eyeOffIcon = document.getElementById("eye-off-icon");
        const MODAL_TRANSITION_MS = 240;

        let selectedUser = null;

        const createInitials = (fullName) => {
            const words = fullName.trim().split(/\s+/);
            if (words.length === 1) return words[0].slice(0, 2).toUpperCase();
            return `${words[0][0]}${words[words.length - 1][0]}`.toUpperCase();
        };

        const renderUserCards = () => {
            userGrid.innerHTML = users.map((user) => `
        <button
            type="button"
            data-user-id="${user.id}"
            class="user-card group rounded-2xl border border-ink-200 bg-white p-4 text-center shadow-sm transition duration-300 hover:-translate-y-1 hover:scale-[1.01] hover:shadow-soft focus:outline-none focus:ring-2 focus:ring-ink-300"
            style="animation-delay: ${user.id * 45}ms"
        >
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-ink-900 text-sm font-semibold text-white">
                ${createInitials(user.name)}
            </div>

            <p class="text-base font-semibold">
                ${user.name}
            </p>

            <p class="mt-1 text-sm text-ink-500 break-all">
                ${user.email}
            </p>
        </button>
      `).join("");
        };

        const showModal = (user) => {
            selectedUser = user;
            modalUserName.textContent = user.name;
            modalUserEmail.textContent = user.email;
            modalAvatar.textContent = createInitials(user.name);
            passwordInput.value = "";
            errorMessage.classList.add("hidden");
            passwordInput.type = "password";
            eyeOpenIcon.classList.remove("hidden");
            eyeOffIcon.classList.add("hidden");
            modal.classList.remove("hidden");
            modal.classList.add("flex");
            requestAnimationFrame(() => {
                modal.classList.remove("modal-closing");
                modal.classList.add("modal-open");
                passwordInput.focus();
            });
        };

        const hideModal = () => {
            if (modal.classList.contains("hidden")) return;
            modal.classList.remove("modal-open");
            modal.classList.add("modal-closing");
            setTimeout(() => {
                modal.classList.add("hidden");
                modal.classList.remove("flex");
                modal.classList.remove("modal-closing");
            }, MODAL_TRANSITION_MS);
            passwordInput.value = "";
            errorMessage.classList.add("hidden");
            selectedUser = null;
        };

        userGrid.addEventListener("click", (event) => {
            const button = event.target.closest("button[data-user-id]");
            if (!button) return;
            const userId = Number(button.dataset.userId);
            const user = users.find((item) => item.id === userId);
            if (!user) return;
            showModal(user);
        });

        closeModalButton.addEventListener("click", hideModal);
        cancelLoginButton.addEventListener("click", hideModal);

        modal.addEventListener("click", (event) => {
            if (event.target === modal) hideModal();
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && !modal.classList.contains("hidden")) {
                hideModal();
            }
        });

        togglePasswordButton.addEventListener("click", () => {
            const isHidden = passwordInput.type === "password";
            passwordInput.type = isHidden ? "text" : "password";
            eyeOpenIcon.classList.toggle("hidden", !isHidden);
            eyeOffIcon.classList.toggle("hidden", isHidden);
            passwordInput.focus();
        });

        passwordForm.addEventListener(
            "submit",
            (event) => {

                event.preventDefault();

                if (!selectedUser) {
                    return;
                }

                document
                    .getElementById("login-email")
                    .value = selectedUser.email;

                document
                    .getElementById("login-password")
                    .value = passwordInput.value;

                document
                    .getElementById("jetstream-login-form")
                    .submit();
            }
        );

        renderUserCards();
    </script>

    <form id="jetstream-login-form" method="POST" action="{{ route('login') }}" class="hidden">
        @csrf

        <input type="email" name="email" id="login-email">

        <input type="password" name="password" id="login-password">
    </form>
</body>

</html>
