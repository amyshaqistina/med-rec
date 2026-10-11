<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MedRec SASMEC · Medication Reconciliation</title>
    <meta name="description" content="Medication reconciliation system for clinical staff at Sultan Ahmad Shah Medical Centre @IIUM.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        .font-display { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; letter-spacing: -0.025em; }
    </style>
</head>

<body class="min-h-screen bg-[#F4F8F7] text-[#12263A] antialiased">

    {{-- ===== Header ===== --}}
    <header class="border-b border-[#D5E2DF] bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-4">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#10916F]" aria-hidden="true">
                    <svg viewBox="0 0 24 24" class="h-7 w-7">
                        <circle cx="12" cy="12" r="10" fill="#FFFFFF"/>
                        <path d="M12 7v10M7 12h10" stroke="#10916F" stroke-width="2.6" stroke-linecap="round" fill="none"/>
                    </svg>
                </span>
                <span class="text-lg font-semibold">MedRec SASMEC</span>
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="rounded-lg bg-[#10916F] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0C7A5C] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#10916F]">
                    Open dashboard
                </a>
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="rounded-lg bg-[#10916F] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0C7A5C] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#10916F]">
                        Sign in
                    </a>
                @endif
            @endauth
        </div>
    </header>

    <main>

        {{-- ===== Hero ===== --}}
        <section class="mx-auto grid max-w-6xl items-center gap-12 px-5 py-14 lg:grid-cols-[1fr_1.05fr] lg:py-20">
            <div>
                <h1 class="font-display text-4xl font-bold leading-[1.08] sm:text-5xl">
                    Every patient leaves with the right medicines.
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-[#3F5664]">
                    MedRec helps doctors, pharmacists and nurses at Sultan Ahmad Shah Medical Centre compare what a patient takes at home with what is ordered in hospital, and settle every difference before it reaches the patient.
                </p>

                <div class="mt-8">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-block rounded-lg bg-[#10916F] px-6 py-3 font-semibold text-white hover:bg-[#0C7A5C] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#10916F]">
                            Open dashboard
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="inline-block rounded-lg bg-[#10916F] px-6 py-3 font-semibold text-white hover:bg-[#0C7A5C] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#10916F]">
                                Sign in to MedRec
                            </a>
                        @endif
                        <p class="mt-4 text-sm text-[#4B6370]">For authorised SASMEC staff only. Accounts are created by your administrator.</p>
                    @endauth
                </div>
            </div>

            {{-- Medicine illustration: bottle, blister pack, capsules and tablets --}}
            <div class="flex justify-center" aria-hidden="true">
                <svg viewBox="0 0 520 450" class="h-auto w-full max-w-md lg:max-w-lg">
                    <circle cx="260" cy="225" r="200" fill="#DCEFEA"/>
                    <circle cx="260" cy="225" r="140" fill="#C6E5DC"/>

                    {{-- Medicine bottle --}}
                    <rect x="138" y="62" width="94" height="38" rx="9" fill="#12263A"/>
                    <rect x="146" y="98" width="78" height="14" fill="#0B5851"/>
                    <rect x="112" y="110" width="146" height="222" rx="24" fill="#10916F"/>
                    <rect x="124" y="122" width="9" height="198" rx="4.5" fill="#FFFFFF" opacity="0.25"/>
                    <rect x="126" y="168" width="118" height="124" rx="12" fill="#FFFFFF"/>
                    <circle cx="185" cy="208" r="20" fill="#10916F"/>
                    <path d="M185 196v24M173 208h24" stroke="#FFFFFF" stroke-width="5" stroke-linecap="round" fill="none"/>
                    <rect x="146" y="244" width="78" height="8" rx="4" fill="#C6E5DC"/>
                    <rect x="154" y="262" width="62" height="8" rx="4" fill="#C6E5DC"/>

                    {{-- Blister pack --}}
                    <g transform="rotate(8 385 250)">
                        <rect x="296" y="150" width="176" height="212" rx="18" fill="#FFFFFF" stroke="#B9CFCA" stroke-width="2"/>
                        <g fill="#F4F8F7" stroke="#B9CFCA" stroke-width="2">
                            <circle cx="342" cy="196" r="17"/><circle cx="426" cy="196" r="17"/>
                            <circle cx="342" cy="244" r="17"/><circle cx="426" cy="244" r="17"/>
                            <circle cx="342" cy="292" r="17"/><circle cx="426" cy="292" r="17"/>
                            <circle cx="342" cy="340" r="17"/><circle cx="426" cy="340" r="17"/>
                        </g>
                        <circle cx="342" cy="196" r="10" fill="#10916F"/>
                        <circle cx="426" cy="196" r="10" fill="#12263A"/>
                        <circle cx="342" cy="244" r="10" fill="#8FD0BD"/>
                        <circle cx="426" cy="292" r="10" fill="#10916F"/>
                        <circle cx="342" cy="340" r="10" fill="#12263A"/>
                    </g>

                    {{-- Loose capsule and tablets --}}
                    <g transform="rotate(-20 215 398)">
                        <rect x="165" y="381" width="100" height="36" rx="18" fill="#FFFFFF" stroke="#10916F" stroke-width="3"/>
                        <path d="M215 381 H247 a18 18 0 0 1 0 36 H215 Z" fill="#10916F"/>
                    </g>
                    <circle cx="316" cy="402" r="19" fill="#FFFFFF" stroke="#12263A" stroke-width="3"/>
                    <path d="M300 402h32" stroke="#12263A" stroke-width="3" stroke-linecap="round"/>
                    <circle cx="82" cy="372" r="12" fill="#10916F"/>
                    <circle cx="448" cy="112" r="9" fill="#12263A"/>
                    <circle cx="70" cy="140" r="7" fill="#8FD0BD"/>
                </svg>
            </div>
        </section>

        {{-- ===== Where it is used ===== --}}
        <section class="border-y border-[#D5E2DF] bg-white">
            <div class="mx-auto max-w-6xl px-5 py-14">
                <h2 class="font-display max-w-2xl text-3xl font-bold leading-tight">Used wherever care changes hands</h2>
                <p class="mt-3 max-w-2xl leading-relaxed text-[#3F5664]">
                    Medicine errors cluster at transitions. MedRec follows the patient through each one.
                </p>

                <div class="mt-10 grid gap-8 md:grid-cols-3">
                    <div class="border-t-2 border-[#10916F] pt-4">
                        <h3 class="font-display text-xl font-semibold">On admission</h3>
                        <p class="mt-2 leading-relaxed text-[#3F5664]">
                            Record the medicines the patient takes at home, then check them against the first set of hospital orders.
                        </p>
                    </div>
                    <div class="border-t-2 border-[#10916F] pt-4">
                        <h3 class="font-display text-xl font-semibold">On transfer</h3>
                        <p class="mt-2 leading-relaxed text-[#3F5664]">
                            When a patient moves between wards, the receiving team sees what changed and why.
                        </p>
                    </div>
                    <div class="border-t-2 border-[#10916F] pt-4">
                        <h3 class="font-display text-xl font-semibold">On discharge</h3>
                        <p class="mt-2 leading-relaxed text-[#3F5664]">
                            Produce one clear list of what to start, stop and continue at home.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== What staff can do ===== --}}
        <section class="mx-auto max-w-6xl px-5 py-14">
            <h2 class="font-display max-w-2xl text-3xl font-bold leading-tight">Built for the people on the ward</h2>

            <dl class="mt-10 grid gap-x-12 gap-y-8 md:grid-cols-2">
                <div>
                    <dt class="font-display text-lg font-semibold">Home and hospital lists side by side</dt>
                    <dd class="mt-1 leading-relaxed text-[#3F5664]">Differences in drug, dose or frequency stand out, so nothing is missed on a busy round.</dd>
                </div>
                <div>
                    <dt class="font-display text-lg font-semibold">A decision for every medicine</dt>
                    <dd class="mt-1 leading-relaxed text-[#3F5664]">Continue, hold or stop, recorded against the patient with the name of the person who decided.</dd>
                </div>
                <div>
                    <dt class="font-display text-lg font-semibold">Works with your patient records</dt>
                    <dd class="mt-1 leading-relaxed text-[#3F5664]">Reconciliation sits beside each patient's ward, details and lab results.</dd>
                </div>
                <div>
                    <dt class="font-display text-lg font-semibold">Access for authorised staff</dt>
                    <dd class="mt-1 leading-relaxed text-[#3F5664]">Sign in with your staff account. Patient information is not visible to anyone else.</dd>
                </div>
            </dl>
        </section>

        {{-- ===== Closing call to action ===== --}}
        <section class="bg-[#12263A]">
            <div class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-6 px-5 py-12 md:flex-row md:items-center">
                <div>
                    <h2 class="font-display text-2xl font-bold text-white sm:text-3xl">Ready to reconcile a patient's medicines?</h2>
                    <p class="mt-2 text-[#B7C9D3]">Sign in with your SASMEC staff account.</p>
                </div>

                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-white px-6 py-3 font-semibold text-[#12263A] hover:bg-[#E6EFED] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        Open dashboard
                    </a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="rounded-lg bg-white px-6 py-3 font-semibold text-[#12263A] hover:bg-[#E6EFED] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                            Sign in to MedRec
                        </a>
                    @endif
                @endauth
            </div>
        </section>
    </main>

    {{-- ===== Footer ===== --}}
    <footer class="bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-5 py-6 text-sm text-[#4B6370] sm:flex-row sm:justify-between">
            <p>Sultan Ahmad Shah Medical Centre @IIUM, Kuantan, Pahang</p>
            <p>&copy; {{ date('Y') }} MedRec SASMEC</p>
        </div>
    </footer>
</body>
</html>
