<x-app-layout>
    <x-slot name="header">
        <div class="dashboard-header">
            <div>
                <h2 class="dashboard-title">Updates</h2>
                <p class="dashboard-subtitle">Choose a BrewCloud version to view or download.</p>
            </div>
            <a href="{{ route('tenant.dashboard') }}" class="modal-close">Back to Dashboard</a>
        </div>
    </x-slot>

    @php
        $currentVersion = $versionInfo['current_version'] ?? config('app.version', 'dev');
        $latestVersion = $versionInfo['latest_version'] ?? null;
        $updateAvailable = (bool) ($versionInfo['update_available'] ?? false);
        $releases = is_array($releases ?? null) ? $releases : [];
        $selectedRelease = collect($releases)->first(function (array $release) use ($latestVersion) {
            return $latestVersion !== null && strcasecmp((string) ($release['tag_name'] ?? ''), (string) $latestVersion) === 0;
        }) ?? ($releases[0] ?? null);
        $otaInfo = is_array($otaInfo ?? null) ? $otaInfo : [];
    @endphp

    <div class="dashboard-shell py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div data-auto-hide-notice class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 transition-all duration-700 ease-out">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-[0_20px_60px_-30px_rgba(15,23,42,0.25)] backdrop-blur-xl">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                            GitHub Version Card
                        </div>
                        <h3 class="mt-4 text-2xl font-semibold tracking-tight text-zinc-950">BrewCloud Release Updates</h3>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-600">
                            Current version: <span class="font-semibold text-zinc-900">{{ $currentVersion }}</span>
                            @if ($latestVersion)
                                <span class="mx-2 text-zinc-300">|</span>
                                Latest version: <span class="font-semibold text-zinc-900">{{ $latestVersion }}</span>
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($updateAvailable)
                            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Update available</span>
                        @else
                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Up to date</span>
                        @endif
                        @if (!empty($versionInfo['latest_version']))
                            <form method="POST" action="{{ route('tenant.updates.apply') }}">
                                @csrf
                                <input type="hidden" name="release_tag" value="{{ $versionInfo['latest_version'] }}">
                                <input type="hidden" name="release_name" value="{{ $versionInfo['latest_version'] }}">
                                <input type="hidden" name="release_url" value="{{ $versionInfo['latest_url'] ?? '' }}">
                                <button
                                    type="submit"
                                    class="rounded-full bg-[color:var(--brand-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-95"
                                >
                                    Download latest
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_1.1fr]">
                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h4 class="text-lg font-semibold text-zinc-950">Select a version</h4>
                            <p class="mt-1 text-sm text-zinc-600">Only the latest published release can be applied.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ count($releases) }} releases</span>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div>
                            <label for="release-selector" class="mb-2 block text-sm font-medium text-slate-700">Available Releases</label>
                            <select id="release-selector" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                                @forelse ($releases as $release)
                                    @php
                                        $isLatestRelease = $latestVersion !== null && strcasecmp((string) ($release['tag_name'] ?? ''), (string) $latestVersion) === 0;
                                    @endphp
                                    <option
                                        value="{{ $loop->index }}"
                                        data-tag="{{ $release['tag_name'] }}"
                                        data-name="{{ $release['name'] }}"
                                        data-release-url="{{ $release['html_url'] }}"
                                        data-published-at="{{ $release['published_at'] }}"
                                        @selected($loop->first)
                                    >
                                        {{ $release['tag_name'] }}{{ !empty($release['prerelease']) ? ' (pre-release)' : '' }}{{ $isLatestRelease ? ' (latest)' : '' }}
                                    </option>
                                @empty
                                    <option value="">No releases found</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Selected Version</div>
                            <div class="mt-1 text-xl font-semibold text-slate-950" data-release-name>{{ $selectedRelease['name'] ?? 'No release selected' }}</div>
                            <div class="mt-1 text-sm text-slate-600">
                                Tag: <span class="font-medium text-slate-900" data-release-tag>{{ $selectedRelease['tag_name'] ?? '—' }}</span>
                            </div>
                            <div class="mt-1 text-sm text-slate-600">
                                Published: <span class="font-medium text-slate-900" data-release-published>{{ !empty($selectedRelease['published_at']) ? \Illuminate\Support\Carbon::parse($selectedRelease['published_at'])->format('M j, Y g:i A') : '—' }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <form method="POST" action="{{ route('tenant.updates.apply') }}" data-apply-selected-form>
                                    @csrf
                                    <input type="hidden" name="release_tag" data-apply-release-tag value="{{ $selectedRelease['tag_name'] ?? '' }}">
                                    <input type="hidden" name="release_name" data-apply-release-name value="{{ $selectedRelease['name'] ?? '' }}">
                                    <input type="hidden" name="release_url" data-apply-release-url value="{{ $selectedRelease['html_url'] ?? '' }}">
                                    <button
                                        type="submit"
                                        data-download-selected
                                        class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-500 {{ empty($selectedRelease['tag_name']) ? 'pointer-events-none opacity-50' : '' }}"
                                    >
                                        Download selected update
                                    </button>
                                </form>
                                <a
                                    href="{{ $selectedRelease['html_url'] ?? '#' }}"
                                    target="_blank"
                                    rel="noreferrer"
                                    data-view-selected
                                    class="rounded-full border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 {{ empty($selectedRelease['html_url']) ? 'pointer-events-none opacity-50' : '' }}"
                                >
                                    View release
                                </a>
                            </div>
                            <div class="download-progress-bar hidden h-2 w-full overflow-hidden rounded-full bg-slate-200">
                                <div class="progress-bar-fill h-full w-0 bg-emerald-500 transition-all duration-300 ease-out"></div>
                            </div>
                            <p class="download-status-text hidden text-xs text-slate-600"></p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm">
                    <h4 class="text-lg font-semibold text-zinc-950">Release cards</h4>
                    <p class="mt-1 text-sm text-zinc-600">Choose any published version you want.</p>

                    <div class="mt-5 grid gap-4">
                        @forelse ($releases as $release)
                            @php
                                $isLatestRelease = $latestVersion !== null && strcasecmp((string) ($release['tag_name'] ?? ''), (string) $latestVersion) === 0;
                            @endphp
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h5 class="text-base font-semibold text-slate-950">{{ $release['tag_name'] }}</h5>
                                            @if ($isLatestRelease)
                                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-800">Latest</span>
                                            @endif
                                            @if (!empty($release['prerelease']))
                                                <span class="rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800">Pre-release</span>
                                            @endif
                                            @if (!empty($release['draft']))
                                                <span class="rounded-full bg-rose-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-800">Draft</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm text-slate-600">{{ $release['name'] ?: $release['tag_name'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Published {{ !empty($release['published_at']) ? \Illuminate\Support\Carbon::parse($release['published_at'])->diffForHumans() : 'N/A' }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <form method="POST" action="{{ route('tenant.updates.apply') }}">
                                            @csrf
                                            <input type="hidden" name="release_tag" value="{{ $release['tag_name'] }}">
                                            <input type="hidden" name="release_name" value="{{ $release['name'] ?: $release['tag_name'] }}">
                                            <input type="hidden" name="release_url" value="{{ $release['html_url'] ?? '' }}">
                                            <button type="submit" class="rounded-full bg-[color:var(--brand-primary)] px-3 py-2 text-xs font-medium text-white shadow-sm transition hover:opacity-95 {{ $isLatestRelease ? '' : 'pointer-events-none opacity-50' }}">
                                                Download
                                            </button>
                                        </form>
                                        @if (!empty($release['html_url']))
                                            <a href="{{ $release['html_url'] }}" target="_blank" rel="noreferrer" class="rounded-full border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:bg-white">
                                                View
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                                No releases are available from GitHub yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (count($releases))
        <script>
            (function () {
                const autoHideNotices = document.querySelectorAll('[data-auto-hide-notice]');
                const selector = document.getElementById('release-selector');
                const downloadButton = document.querySelector('[data-download-selected]');
                const viewButton = document.querySelector('[data-view-selected]');
                const releaseName = document.querySelector('[data-release-name]');
                const releaseTag = document.querySelector('[data-release-tag]');
                const releasePublished = document.querySelector('[data-release-published]');
                const applyTagInput = document.querySelector('[data-apply-release-tag]');
                const applyNameInput = document.querySelector('[data-apply-release-name]');
                const applyUrlInput = document.querySelector('[data-apply-release-url]');

                // Progress bar elements
                const progressBarContainer = document.querySelector('.download-progress-bar');
                const progressBarFill = document.querySelector('.progress-bar-fill');
                const statusText = document.querySelector('.download-status-text');
                let progressInterval = null;

                const showProgressBar = () => {
                    if (progressBarContainer) {
                        progressBarContainer.classList.remove('hidden');
                        progressBarFill.style.width = '10%';
                        if (statusText) {
                            statusText.classList.remove('hidden');
                            statusText.textContent = 'Downloading update...';
                        }
                    }
                };

                const animateProgress = () => {
                    if (progressInterval) clearInterval(progressInterval);
                    
                    let progress = 10;
                    progressInterval = setInterval(() => {
                        progress += Math.random() * 30;
                        if (progress > 90) progress = 90;
                        if (progressBarFill) progressBarFill.style.width = progress + '%';
                    }, 500);
                };

                const completeProgress = () => {
                    if (progressInterval) clearInterval(progressInterval);
                    if (progressBarFill) progressBarFill.style.width = '100%';
                    if (statusText) statusText.textContent = 'Update downloaded successfully!';
                };

                const hideProgressBar = (delay = 3000) => {
                    setTimeout(() => {
                        if (progressBarContainer) {
                            progressBarContainer.classList.add('hidden');
                            progressBarFill.style.width = '0%';
                        }
                        if (statusText) statusText.classList.add('hidden');
                    }, delay);
                };

                // Add progress bar listener to main download form
                const initMainFormProgress = () => {
                    const mainForm = document.querySelector('[data-apply-selected-form]');
                    if (mainForm) {
                        mainForm.addEventListener('submit', (e) => {
                            showProgressBar();
                            animateProgress();
                            
                            // Disable download button while downloading
                            if (downloadButton) {
                                downloadButton.disabled = true;
                                downloadButton.classList.add('opacity-50', 'cursor-not-allowed');
                            }
                        });
                    }
                };

                // Add progress bar listener to release card download buttons
                const initCardButtonsProgress = () => {
                    const cardButtons = document.querySelectorAll('.rounded-2xl.bg-slate-50 form[action="{{ route('tenant.updates.apply') }}"] button[type="submit"]');
                    cardButtons.forEach((btn) => {
                        btn.addEventListener('click', (e) => {
                            // Show minimal progress feedback
                            btn.disabled = true;
                            btn.classList.add('opacity-50');
                            const originalText = btn.textContent;
                            btn.textContent = 'Downloading...';
                        });
                    });
                };

                if (!selector || !downloadButton || !viewButton) {
                    autoHideNotices.forEach((notice) => {
                        window.setTimeout(() => {
                            notice.classList.add('opacity-0', '-translate-y-1');
                            notice.classList.add('pointer-events-none');

                            window.setTimeout(() => {
                            notice.remove();
                            }, 700);
                        }, 30000);
                    });

                    initMainFormProgress();
                    initCardButtonsProgress();
                    return;
                }

                autoHideNotices.forEach((notice) => {
                    window.setTimeout(() => {
                        notice.classList.add('opacity-0', '-translate-y-1');
                        notice.classList.add('pointer-events-none');

                        window.setTimeout(() => {
                        notice.remove();
                        }, 700);
                    }, 30000);
                });

                const updateSelection = () => {
                    const selected = selector.options[selector.selectedIndex];
                    if (!selected) {
                        return;
                    }

                    const tag = selected.getAttribute('data-tag') || '—';
                    const name = selected.getAttribute('data-name') || tag;
                    const releaseUrl = selected.getAttribute('data-release-url') || '#';
                    const publishedAt = selected.getAttribute('data-published-at') || '';

                    if (releaseName) releaseName.textContent = name;
                    if (releaseTag) releaseTag.textContent = tag;
                    if (releasePublished) {
                        releasePublished.textContent = publishedAt ? new Date(publishedAt).toLocaleString() : '—';
                    }

                    if (applyTagInput) applyTagInput.value = tag === '—' ? '' : tag;
                    if (applyNameInput) applyNameInput.value = name === '—' ? '' : name;
                    if (applyUrlInput) applyUrlInput.value = releaseUrl === '#' ? '' : releaseUrl;
                    viewButton.href = releaseUrl;
                    downloadButton.classList.toggle('pointer-events-none', tag === '—');
                    downloadButton.classList.toggle('opacity-50', tag === '—');
                    viewButton.classList.toggle('pointer-events-none', releaseUrl === '#');
                    viewButton.classList.toggle('opacity-50', releaseUrl === '#');
                };

                selector.addEventListener('change', updateSelection);
                updateSelection();
                
                // Initialize progress listeners
                initMainFormProgress();
                initCardButtonsProgress();
            })();
        </script>
    @endif
</x-app-layout>
