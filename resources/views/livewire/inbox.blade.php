<div x-data="{ mobileShowDetail: false }">
    {{-- Page Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Inbox</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $unreadCount > 0 ? $unreadCount . ' pesan belum dibaca' : 'Semua pesan sudah dibaca' }}
            </p>
        </div>
        @if($unreadCount > 0)
            <button wire:click="markAllAsRead" class="btn-ghost text-sm inline-flex items-center gap-2 self-start">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Tandai Semua Dibaca
            </button>
        @endif
    </div>

    @if($emails->isEmpty())
        {{-- Empty State --}}
        <div class="glass-card p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 01-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 001.183 1.981l6.478 3.488m8.839 2.51l-4.66-2.51m0 0l-1.023-.55a2.25 2.25 0 00-2.134 0l-1.022.55m0 0l-4.661 2.51m16.5 1.615a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V8.844a2.25 2.25 0 011.183-1.981l7.5-4.039a2.25 2.25 0 012.134 0l7.5 4.039a2.25 2.25 0 011.183 1.98V19.5z" />
                </svg>
            </div>
            <h3 class="font-semibold text-slate-900 mb-1">Inbox Kosong</h3>
            <p class="text-sm text-slate-500">Belum ada email yang dikirim ke akun Anda.</p>
        </div>
    @else
        {{-- Split View Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-0">

            {{-- Email List (Left Panel) --}}
            <div class="lg:col-span-5 xl:col-span-4"
                 :class="{ 'hidden lg:block': mobileShowDetail }">
                <div class="glass-card overflow-hidden lg:rounded-r-none lg:border-r-0">
                    <div class="divide-y divide-slate-100 max-h-[calc(100vh-220px)] overflow-y-auto">
                        @foreach($emails as $email)
                            <button
                                wire:click="selectEmail({{ $email->id }})"
                                @click="mobileShowDetail = true"
                                class="w-full text-left px-4 py-3.5 transition-all duration-150 hover:bg-slate-50 focus:outline-none focus:bg-slate-50 group
                                    {{ $selectedEmail && $selectedEmail->id === $email->id ? 'bg-blue-50/70 border-l-[3px] border-l-blue-500' : 'border-l-[3px] border-l-transparent' }}"
                                id="inbox-email-{{ $email->id }}">

                                <div class="flex items-start gap-3">
                                    {{-- Unread Indicator --}}
                                    <div class="mt-1.5 shrink-0">
                                        @if(!$email->isRead())
                                            <span class="block w-2.5 h-2.5 rounded-full bg-blue-500 shadow-sm shadow-blue-200"></span>
                                        @else
                                            <span class="block w-2.5 h-2.5 rounded-full bg-transparent"></span>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        {{-- Subject --}}
                                        <p class="text-sm truncate {{ !$email->isRead() ? 'font-semibold text-slate-900' : 'font-medium text-slate-600' }}">
                                            {{ $email->subject }}
                                        </p>

                                        {{-- Preview (stripped HTML) --}}
                                        <p class="text-xs text-slate-400 truncate mt-0.5">
                                            {{ Str::limit(strip_tags($email->body), 80) }}
                                        </p>

                                        {{-- Date --}}
                                        <p class="text-xs text-slate-400 mt-1">
                                            {{ $email->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Email Detail (Right Panel) --}}
            <div class="lg:col-span-7 xl:col-span-8"
                 :class="{ 'hidden lg:block': !mobileShowDetail && !{{ $selectedEmail ? 'true' : 'false' }} }">

                @if($selectedEmail)
                    <div class="glass-card overflow-hidden lg:rounded-l-none">
                        {{-- Detail Header --}}
                        <div class="px-5 py-4 border-b border-slate-100">
                            <div class="flex items-center gap-3 mb-3">
                                {{-- Mobile Back Button --}}
                                <button @click="mobileShowDetail = false" class="lg:hidden p-1 -ml-1 rounded-lg hover:bg-slate-100 transition-colors">
                                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                                    </svg>
                                </button>

                                <div class="flex-1 min-w-0">
                                    <h2 class="text-lg font-semibold text-slate-900 leading-tight">
                                        {{ $selectedEmail->subject }}
                                    </h2>
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5">
                                        <span class="text-xs text-slate-400">
                                            Ke: {{ $selectedEmail->recipient_email }}
                                        </span>
                                        <span class="text-xs text-slate-300">•</span>
                                        <span class="text-xs text-slate-400">
                                            {{ $selectedEmail->created_at->format('d M Y, H:i') }}
                                        </span>
                                        @if($selectedEmail->mailable_class)
                                            <span class="text-xs text-slate-300">•</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500">
                                                {{ class_basename($selectedEmail->mailable_class) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Email Body --}}
                        <div class="p-5 max-h-[calc(100vh-320px)] overflow-y-auto">
                            <div class="email-body-content bg-white rounded-xl border border-slate-100 p-5 shadow-sm">
                                <iframe
                                    srcdoc="{!! e($selectedEmail->body) !!}"
                                    class="w-full border-0 rounded-lg"
                                    style="min-height: 400px;"
                                    onload="this.style.height = this.contentDocument.documentElement.scrollHeight + 'px'"
                                    sandbox="allow-same-origin"
                                    title="Email Content"
                                    id="inbox-email-body">
                                </iframe>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- No Email Selected State --}}
                    <div class="glass-card lg:rounded-l-none h-full min-h-[400px] flex items-center justify-center">
                        <div class="text-center p-8">
                            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-slate-700 mb-1">Pilih Pesan</h3>
                            <p class="text-sm text-slate-400">Klik salah satu email untuk melihat detailnya.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
