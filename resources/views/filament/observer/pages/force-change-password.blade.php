<!-- resources/views/filament/student/pages/force-change-password.blade.php -->
<x-filament-panels::page>
    <div class="min-h-[60vh] flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center mb-4 shadow-lg">
                    <x-heroicon-o-shield-check class="w-8 h-8 text-white" />
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $this->getHeading() ?: 'Secure Your Account' }}
                </h1>
            </div>

            @if(!auth()->user()->password_updated_at)
                <!-- Enhanced Warning Alert -->
                <div class="mb-6 relative overflow-hidden rounded-xl border border-warning-200 dark:border-warning-800 bg-gradient-to-r from-warning-50 to-orange-50 dark:from-warning-900/20 dark:to-orange-900/20 shadow-sm">
                    <div class="absolute inset-0 bg-gradient-to-r from-warning-500/5 to-orange-500/5"></div>
                    <div class="relative p-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-warning-100 dark:bg-warning-900/50 flex items-center justify-center">
                                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-600 dark:text-warning-400" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold text-warning-800 dark:text-warning-200 mb-1">
                                    Password Change Required
                                </h3>
                                <p class="text-sm text-warning-700 dark:text-warning-300 leading-relaxed">
                                    Change your default password to secure your account.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-warning-400 to-orange-400"></div>
                </div>
            @endif

            <!-- Enhanced Form Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6">
                    <form wire:submit="updatePassword" class="space-y-6">
                        {{ $this->form }}

                        <!-- Enhanced Submit Button -->
                        <div class="pt-4">
                            <x-filament::button
                                type="submit"
                                size="lg"
                                class="w-full justify-center !bg-gradient-to-r !from-blue-600 !to-purple-600 hover:!from-blue-700 hover:!to-purple-700 !text-white font-semibold !py-4 !px-6 !rounded-xl transition-all duration-300 transform hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-blue-300 focus:ring-opacity-50 disabled:opacity-50 disabled:cursor-not-allowed border-0"
                            >
                                Update Password
                            </x-filament::button>
                        </div>
                    </form>
                </div>

                <!-- Security Tips Footer -->
                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-400">
                        <x-heroicon-o-information-circle class="w-4 h-4 text-primary-500" />
                        <span>Use a combination of letters, numbers, and special characters</span>
                    </div>
                </div>
            </div>

            <!-- Additional Security Info -->
            <div class="mt-6 text-center">
                <div class="inline-flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-4 py-2 rounded-full border border-gray-200 dark:border-gray-700">
                    <x-heroicon-o-shield-check class="w-4 h-4 text-green-500" />
                    <span>Your data is protected with enterprise-grade security</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Styles -->
    <style>
        /* Smooth animations for form interactions */
        .fi-form-component-ctn {
            transition: all 0.2s ease-in-out;
        }

        .fi-form-component-ctn:hover {
            transform: translateY(-1px);
        }

        /* Enhanced focus states */
        .fi-input:focus {
            box-shadow: 0 0 0 3px rgba(var(--primary-500), 0.1);
            border-color: rgb(var(--primary-500));
        }

        /* Loading state animation */
        @keyframes pulse-ring {
            0% {
                transform: scale(0.33);
            }
            40%, 50% {
                opacity: 1;
            }
            100% {
                opacity: 0;
                transform: scale(1.2);
            }
        }

        /* Gradient background animation */
        @keyframes gradient-shift {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }

        .animate-gradient {
            background-size: 200% 200%;
            animation: gradient-shift 6s ease infinite;
        }

        /* Enhanced button hover effects */
        .fi-btn:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Custom scrollbar for better aesthetics */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgb(var(--gray-100));
        }

        ::-webkit-scrollbar-thumb {
            background: rgb(var(--primary-400));
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgb(var(--primary-500));
        }

        /* Dark mode enhancements */
        .dark ::-webkit-scrollbar-track {
            background: rgb(var(--gray-800));
        }

        /* Responsive design improvements */
        @media (max-width: 640px) {
            .min-h-[60vh] {
            min-height: 50vh;
        }
        }

        /* Form field enhancements */
        .fi-fo-field-wrp {
            position: relative;
        }

        .fi-fo-field-wrp::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(var(--primary-500), 0.3), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .fi-fo-field-wrp:focus-within::before {
            opacity: 1;
        }
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('redirect-after-delay', (event) => {
                // Show notification for 2 seconds, then redirect
                setTimeout(() => {
                    window.location.href = event.url;
                }, 2000);
            });
        });
    </script>

</x-filament-panels::page>
