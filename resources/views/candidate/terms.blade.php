<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions — MG Portfolio</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">

{{-- Sticky header --}}
<header class="sticky top-0 z-10 bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🎓</span>
            <span class="font-bold text-gray-900 text-sm sm:text-base">MG Portfolio</span>
        </div>
        <a href="{{ route('candidate.register') }}"
           class="text-sm text-green-600 font-medium hover:underline flex items-center gap-1">
            ← Back to Registration
        </a>
    </div>
</header>

<main class="max-w-3xl mx-auto px-4 py-10 sm:py-14">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-10">

        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Terms &amp; Conditions</h1>
        <p class="text-sm text-gray-400 mb-8">Last updated: {{ now()->format('F j, Y') }} &nbsp;·&nbsp; MG Portfolio Program</p>

        <div class="prose prose-sm sm:prose max-w-none text-gray-700 space-y-8">

            {{-- 1 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">1. Introduction &amp; Acceptance</h2>
                <p>
                    Welcome to the MG Portfolio Candidate Portal ("the Portal"), a digital platform managed for the purpose of training, assessment, and certification of Intending Ministers General (MGs). By registering for and using this Portal, you ("Candidate") agree to be bound by these Terms &amp; Conditions in their entirety.
                </p>
                <p class="mt-2">
                    If you do not agree to these Terms, you must not register or use the Portal. These Terms are binding from the moment you submit your registration and may be updated periodically. Continued use of the Portal after any update constitutes acceptance of the revised Terms.
                </p>
            </section>

            {{-- 2 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">2. Eligibility</h2>
                <p>To register as a Candidate, you must:</p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Be a member in good standing of a registered church within an accredited district.</li>
                    <li>Have received a formal nomination or approval from your district leadership or pastor.</li>
                    <li>Provide accurate, current, and complete information during registration.</li>
                    <li>Be at least 18 years of age.</li>
                </ul>
                <p class="mt-2">
                    The program administrators reserve the right to suspend or revoke access for any Candidate who does not meet eligibility requirements or who provides false information.
                </p>
            </section>

            {{-- 3 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">3. Account Security &amp; Responsibilities</h2>
                <p>You are responsible for:</p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Maintaining the confidentiality of your login credentials.</li>
                    <li>All activity that occurs under your account.</li>
                    <li>Immediately notifying an administrator if you suspect unauthorised access to your account.</li>
                    <li>Logging out after each session, particularly on shared devices.</li>
                </ul>
                <p class="mt-2">
                    Sharing your login credentials with any other person is strictly prohibited and may result in immediate account suspension.
                </p>
            </section>

            {{-- 4 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">4. Academic Integrity &amp; Submission Policy</h2>
                <p>
                    All assignments and portfolio submissions must represent your own original work. You must not:
                </p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Plagiarise or reproduce the work of another person without proper attribution.</li>
                    <li>Submit work generated entirely by artificial intelligence tools as your own.</li>
                    <li>Assist another Candidate in submitting work that is not their own.</li>
                    <li>Tamper with or attempt to alter submitted documents after submission.</li>
                </ul>
                <p class="mt-2">
                    Submissions are subject to similarity checks. Violations of academic integrity may result in a mark of zero, suspension from the program, or permanent disqualification. All decisions regarding academic integrity violations are at the discretion of the program administrators.
                </p>
            </section>

            {{-- 5 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">5. Personal Data &amp; Privacy</h2>
                <p>
                    By registering, you consent to the collection and processing of your personal information, including your name, email address, phone number, church and district affiliation, and passport photograph, for the purposes of:
                </p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Administering your participation in the training program.</li>
                    <li>Communicating with you regarding your progress and assessments.</li>
                    <li>Verifying your identity and eligibility.</li>
                    <li>Generating records for certification and church administration.</li>
                </ul>
                <p class="mt-2">
                    Your data is stored securely and will not be sold or shared with unaffiliated third parties without your consent, except where required by law or by church governance structures.
                </p>
                <p class="mt-2">
                    Your passport photograph is collected for identification purposes only. It will be stored securely and displayed solely to authorised administrators and reviewers within the Portal.
                </p>
            </section>

            {{-- 6 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">6. Use of the Platform</h2>
                <p>You agree to use the Portal only for lawful purposes and in accordance with these Terms. You must not:</p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Upload malicious files, viruses, or any code designed to disrupt the Portal.</li>
                    <li>Attempt to gain unauthorised access to any area of the Portal or its underlying systems.</li>
                    <li>Use the Portal to distribute spam, unsolicited communications, or offensive content.</li>
                    <li>Scrape, harvest, or extract data from the Portal without authorisation.</li>
                    <li>Impersonate any person or misrepresent your affiliation with any church or district.</li>
                </ul>
            </section>

            {{-- 7 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">7. Assessment &amp; Results</h2>
                <p>
                    All assessments are conducted by appointed Reviewers. Results are published at the discretion of the program administrators. By participating, you acknowledge that:
                </p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Assessment decisions by Reviewers are final unless formally appealed through the appropriate channel.</li>
                    <li>You may request a review modification through the Portal, which is subject to administrator approval.</li>
                    <li>The passing score for each program is set by the administrators and is subject to change.</li>
                    <li>Results will only be visible to you after the administrator publishes them.</li>
                </ul>
            </section>

            {{-- 8 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">8. Conduct &amp; Community Standards</h2>
                <p>
                    Candidates are expected to conduct themselves with respect and integrity at all times on the Portal. Any harassment, abuse, or disrespectful communication directed at administrators, reviewers, or other candidates will result in immediate suspension.
                </p>
            </section>

            {{-- 9 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">9. Termination of Access</h2>
                <p>
                    The program administrators reserve the right to suspend or permanently terminate your access to the Portal at any time, with or without notice, for reasons including but not limited to:
                </p>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    <li>Breach of these Terms &amp; Conditions.</li>
                    <li>Academic integrity violations.</li>
                    <li>Withdrawal from the program by you or your sponsoring church.</li>
                    <li>Failure to complete profile requirements within a reasonable time.</li>
                </ul>
            </section>

            {{-- 10 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">10. Disclaimer of Warranties</h2>
                <p>
                    The Portal is provided on an "as is" and "as available" basis. While every effort is made to ensure uptime and data integrity, the program administrators make no warranties, express or implied, regarding the uninterrupted availability, accuracy, or fitness for a particular purpose of the Portal.
                </p>
            </section>

            {{-- 11 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">11. Limitation of Liability</h2>
                <p>
                    To the fullest extent permitted by applicable law, the program administrators shall not be liable for any indirect, incidental, special, or consequential damages arising out of or in connection with your use of the Portal, including but not limited to loss of data or delays in assessment.
                </p>
            </section>

            {{-- 12 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">12. Changes to These Terms</h2>
                <p>
                    These Terms may be updated at any time. Registered Candidates will be notified of material changes via the email address on their account. Continued use of the Portal after such notification constitutes acceptance of the updated Terms.
                </p>
            </section>

            {{-- 13 --}}
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">13. Contact</h2>
                <p>
                    For questions about these Terms or the MG Portfolio program, please contact your district administrator or the program helpdesk via your registered email address.
                </p>
            </section>

        </div>

        {{-- Accept CTA --}}
        <div class="mt-10 pt-6 border-t border-gray-100 text-center">
            <p class="text-sm text-gray-500 mb-4">
                By completing your registration, you confirm that you have read, understood, and agreed to these Terms &amp; Conditions.
            </p>
            <a href="{{ route('candidate.register') }}"
               class="inline-block bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-8 rounded-xl text-sm transition shadow-md">
                ← Return to Registration
            </a>
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        © {{ date('Y') }} MG Portfolio. All rights reserved.
    </p>
</main>

</body>
</html>
