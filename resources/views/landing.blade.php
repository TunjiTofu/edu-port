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
        .timeline-item {
            position: relative;
            padding-left: 30px;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 8px;
            width: 12px;
            height: 12px;
            background: #059669;
            border-radius: 50%;
            border: 3px solid white;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: 15px;
            top: 20px;
            width: 2px;
            height: calc(100% + 10px);
            background: linear-gradient(to bottom, #059669, transparent);
        }
        .timeline-item:last-child::after {
            display: none;
        }
        .share-option {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 1px solid #cbd5e1;
            transition: all 0.3s ease;
        }
        .share-option:hover {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            transform: translateY(-2px);
        }
        .section-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto mb-16">
        <!-- Reviewer Card -->
        <div class="card-hover bg-white rounded-2xl shadow-xl overflow-hidden transform-gpu">
            <div class="bg-gradient-to-r from-blue-800 to-blue-600 p-10 text-center">
                <i class="fas fa-user-edit text-6xl text-yellow-300 mb-6"></i>
                <h3 class="text-3xl font-bold text-white">Intending MG</h3>
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

    <!-- Information Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-6xl mx-auto mb-16">
        <!-- Share Section -->
        <div class="section-card rounded-2xl shadow-xl p-8">
            <div class="flex items-center mb-6">
                <i class="fas fa-share-alt text-4xl text-blue-600 mr-4"></i>
                <h2 class="text-3xl font-bold text-gray-800">Share What You Are Learning!</h2>
            </div>
            <p class="text-gray-600 mb-6 text-lg">Include evidence (picture, written summary, link, etc.) in your portfolio. These can be done in various forms such as:</p>

            <div class="space-y-4">
                <div class="share-option rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-pen-fancy text-blue-600 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-gray-800">Writing a Reflection</h4>
                            <p class="text-gray-600 text-sm">Write about a specific lesson or teaching that stood out to you.</p>
                        </div>
                    </div>
                </div>

                <div class="share-option rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-users text-emerald-600 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-gray-800">Group Discussion</h4>
                            <p class="text-gray-600 text-sm">Share what you've learned in a group setting (e.g., youth group, small group).</p>
                        </div>
                    </div>
                </div>

                <div class="share-option rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-chalkboard-teacher text-purple-600 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-gray-800">Create a Presentation</h4>
                            <p class="text-gray-600 text-sm">Share knowledge through a creative format (e.g., PowerPoint or video).</p>
                        </div>
                    </div>
                </div>

                <div class="share-option rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-video text-red-600 mr-3"></i>
                        <div>
                            <h4 class="font-semibold text-gray-800">Record & Share</h4>
                            <p class="text-gray-600 text-sm">Record a video or podcast summarizing three ideas you learned and post it online.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Section -->
        <div class="section-card rounded-2xl shadow-xl p-8">
            <div class="flex items-center mb-6">
                <i class="fas fa-calendar-alt text-4xl text-emerald-600 mr-4"></i>
                <h2 class="text-3xl font-bold text-gray-800">2025 MG Training Timeline</h2>
            </div>

            <div class="timeline-container">
                <div class="timeline-item">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Registration of Candidates</h4>
                        <p class="text-blue-600 font-medium">April 05, 2025</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="bg-green-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Training of Trainers and Trainees</h4>
                        <p class="text-emerald-600 font-medium">April 27, 2025</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">First Submission of Portfolio</h4>
                        <p class="text-yellow-600 font-medium">June 29, 2025</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="bg-orange-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Second/Final Submission of Portfolio</h4>
                        <p class="text-orange-600 font-medium">August 30, 2025</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="bg-purple-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Review/Assessment of Portfolio</h4>
                        <p class="text-purple-600 font-medium">September 01 - 30, 2025</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="bg-pink-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Short-listing of Qualified Candidates</h4>
                        <p class="text-pink-600 font-medium">October 04, 2025</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="bg-indigo-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Interview of Candidates</h4>
                        <p class="text-indigo-600 font-medium">October 11 – 18, 2025</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="bg-red-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Release of List of Successful Candidates</h4>
                        <p class="text-red-600 font-medium">October 25, 2025</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="bg-teal-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Grooming of Successful Candidates</h4>
                        <p class="text-teal-600 font-medium">October 26 – November 15, 2025</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="bg-gradient-to-r from-green-100 to-blue-100 rounded-lg p-4 border-2 border-green-300">
                        <h4 class="font-bold text-gray-800">Investiture Ceremony</h4>
                        <p class="text-green-700 font-bold">November 22, 2025</p>
                    </div>
                </div>
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

        // Add smooth scrolling animation for timeline items
        const timelineItems = document.querySelectorAll('.timeline-item');
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateX(0)';
                }
            });
        }, observerOptions);

        timelineItems.forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            item.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(item);
        });
    });
</script>
</body>
</html>
