<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Dashboard Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #059669 50%, #1e40af 100%);
        }
        .logo-bounce {
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
    </style>
</head>
<body class="min-h-screen gradient-bg">
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-5xl font-bold text-white mb-4 flex items-center justify-center">
            <img src="{{ asset('images/logo.png') }}" alt="Dashboard Logo" class="w-20 h-20 mr-4 logo-bounce">
            Ogun Conference MG Portfolio Portal
        </h1>
        <p class="text-xl text-blue-100">Select your role to access your dashboard</p>
    </div>

    <!-- Role Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
        <!-- Reviewer Card -->
        <div class="card-hover bg-white rounded-2xl shadow-xl overflow-hidden transform-gpu">
            <div class="bg-gradient-to-r from-blue-800 to-blue-600 p-10 text-center">
                <i class="fas fa-user-edit text-6xl text-yellow-300 mb-6"></i>
                <h3 class="text-3xl font-bold text-white">Intending MGs</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6 text-lg">Access learning materials and track progress</p>
                <a href="{{ url('/student') }}" class="block w-full bg-gradient-to-r from-blue-800 to-blue-600 text-white text-center py-5 px-8 rounded-lg font-semibold text-lg hover:from-blue-900 hover:to-blue-700 transition-all duration-300">
                    Access Intending MG Panel
                </a>
            </div>
        </div>

        <!-- Observer Card -->
        <div class="card-hover bg-white rounded-2xl shadow-xl overflow-hidden transform-gpu">
            <div class="bg-gradient-to-r from-emerald-600 to-green-600 p-10 text-center">
                <i class="fas fa-eye text-6xl text-yellow-300 mb-6"></i>
                <h3 class="text-3xl font-bold text-white">Reviewer</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6 text-lg">Review and evaluate submissions and content</p>
                <a href="{{ url('/reviewer') }}" class="block w-full bg-gradient-to-r from-emerald-600 to-green-600 text-white text-center py-5 px-8 rounded-lg font-semibold text-lg hover:from-emerald-700 hover:to-green-700 transition-all duration-300">
                    Access Reviewer Panel
                </a>
            </div>
        </div>

        <!-- Student Card -->
        <div class="card-hover bg-white rounded-2xl shadow-xl overflow-hidden transform-gpu">
            <div class="bg-gradient-to-r from-blue-700 to-emerald-600 p-10 text-center">
                <i class="fas fa-user-graduate text-6xl text-yellow-300 mb-6"></i>
                <h3 class="text-3xl font-bold text-white">Observer</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6 text-lg">Monitor and view system activities and data</p>
                <a href="{{ url('/observer') }}" class="block w-full bg-gradient-to-r from-blue-700 to-emerald-600 text-white text-center py-5 px-4 rounded-lg font-semibold text-lg hover:from-blue-800 hover:to-emerald-700 transition-all duration-300">
                    Access Observer Panel
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-12">
        <p class="text-blue-100 text-sm">© {{ date('Y') }} {{ config('app.name') }}. Secure access to your personalized dashboard.</p>
    </div>
</div>

<script>
    // Add some interactive effects
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.card-hover');

        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    });
</script>
</body>
</html>
