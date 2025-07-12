<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Dashboard Portal</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .icon-bounce {
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
        <h1 class="text-5xl font-bold text-white mb-4">
            <i class="fas fa-tachometer-alt mr-4 icon-bounce"></i>
            Dashboard Portal
        </h1>
        <p class="text-xl text-blue-100">Select your role to access your dashboard</p>
    </div>

    <!-- Role Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 max-w-6xl mx-auto">
        <!-- Admin Card -->
        <div class="card-hover bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-red-500 to-pink-500 p-6 text-center">
                <i class="fas fa-user-shield text-4xl text-white mb-4"></i>
                <h3 class="text-2xl font-bold text-white">Admin</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">Full system access and management capabilities</p>
                <ul class="text-sm text-gray-500 mb-6 space-y-2">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>User Management</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>System Configuration</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Analytics & Reports</li>
                </ul>
                <a href="{{ url('/admin') }}" class="block w-full bg-gradient-to-r from-red-500 to-pink-500 text-white text-center py-3 px-6 rounded-lg font-semibold hover:from-red-600 hover:to-pink-600 transition-all duration-300">
                    Access Admin Panel
                </a>
            </div>
        </div>

        <!-- Reviewer Card -->
        <div class="card-hover bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 p-6 text-center">
                <i class="fas fa-user-edit text-4xl text-white mb-4"></i>
                <h3 class="text-2xl font-bold text-white">Reviewer</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">Review and evaluate submissions and content</p>
                <ul class="text-sm text-gray-500 mb-6 space-y-2">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Content Review</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Approval Workflow</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Quality Assessment</li>
                </ul>
                <a href="{{ url('/reviewer') }}" class="block w-full bg-gradient-to-r from-blue-500 to-cyan-500 text-white text-center py-3 px-6 rounded-lg font-semibold hover:from-blue-600 hover:to-cyan-600 transition-all duration-300">
                    Access Reviewer Panel
                </a>
            </div>
        </div>

        <!-- Observer Card -->
        <div class="card-hover bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-teal-500 p-6 text-center">
                <i class="fas fa-eye text-4xl text-white mb-4"></i>
                <h3 class="text-2xl font-bold text-white">Observer</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">Monitor and view system activities and data</p>
                <ul class="text-sm text-gray-500 mb-6 space-y-2">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Real-time Monitoring</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Data Visualization</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Activity Tracking</li>
                </ul>
                <a href="{{ url('/observer') }}" class="block w-full bg-gradient-to-r from-green-500 to-teal-500 text-white text-center py-3 px-6 rounded-lg font-semibold hover:from-green-600 hover:to-teal-600 transition-all duration-300">
                    Access Observer Panel
                </a>
            </div>
        </div>

        <!-- Student Card -->
        <div class="card-hover bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-indigo-500 p-6 text-center">
                <i class="fas fa-user-graduate text-4xl text-white mb-4"></i>
                <h3 class="text-2xl font-bold text-white">Student</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-6">Access learning materials and track progress</p>
                <ul class="text-sm text-gray-500 mb-6 space-y-2">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Course Access</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Assignment Submission</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Progress Tracking</li>
                </ul>
                <a href="{{ url('/student') }}" class="block w-full bg-gradient-to-r from-purple-500 to-indigo-500 text-white text-center py-3 px-6 rounded-lg font-semibold hover:from-purple-600 hover:to-indigo-600 transition-all duration-300">
                    Access Student Panel
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
