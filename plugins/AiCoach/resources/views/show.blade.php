<x-layouts.app title="AI Coach Conversation">
    <main class="mx-auto max-w-5xl px-6 py-10">
        <div class="flex flex-col gap-4 border-b border-white/10 pb-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-zinc-400">AI Coach</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $conversation->title }}</h1>
            </div>
            <a href="{{ route('plugins.ai-coach.index') }}" class="rounded-xl border border-white/10 px-4 py-2 text-center text-sm text-zinc-200">All conversations</a>
        </div>

        <section class="mt-8 space-y-4">
            @forelse ($messages as $message)
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                    <p class="text-xs uppercase tracking-[0.18em] text-zinc-500">{{ $message->role }}</p>
                    @if ($message->role === 'assistant' && is_array($message->structured))
                        <div class="mt-4 space-y-5 text-sm text-zinc-300">
                            <div>
                                <h2 class="text-base font-semibold text-zinc-100">Direct answer</h2>
                                <p class="mt-2">{{ $message->structured['direct_answer'] ?? $message->content }}</p>
                            </div>
                            @foreach ([
                                'personalized_observations' => 'Personalized observations',
                                'evidence_used' => 'Evidence used',
                                'recommended_next_steps' => 'Recommended next steps',
                                'what_to_watch' => 'What to watch',
                                'reflection_prompts' => 'Reflection prompts',
                            ] as $key => $label)
                                @if (! empty($message->structured[$key]))
                                    <div>
                                        <h2 class="text-base font-semibold text-zinc-100">{{ $label }}</h2>
                                        <ul class="mt-2 list-inside list-disc space-y-1">
                                            @foreach ($message->structured[$key] as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endforeach
                            @if (! empty($message->structured['coach_note']))
                                <p class="rounded-xl border border-white/10 bg-zinc-950/50 p-4 text-zinc-200">{{ $message->structured['coach_note'] }}</p>
                            @endif
                        </div>
                    @else
                        <p class="mt-3 whitespace-pre-line text-sm text-zinc-200">{{ $message->content }}</p>
                    @endif
                    <p class="mt-4 text-xs text-zinc-500">{{ \Illuminate\Support\Carbon::parse($message->created_at)->format('Y-m-d H:i') }}</p>
                </article>
            @empty
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 text-sm text-zinc-400">
                    Ask your first question to begin.
                </article>
            @endforelse
        </section>

        <section class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <form method="POST" action="{{ route('plugins.ai-coach.messages.store', $conversation->id) }}" class="space-y-4">
                @csrf
                <textarea name="question" rows="4" required minlength="8" maxlength="1000" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-zinc-100 outline-none focus:border-white/30" placeholder="Ask a follow-up about your scores, goals, habits, lessons, or patterns.">{{ $draftQuestion }}</textarea>
                @error('question')
                    <p class="text-sm text-red-300">{{ $message }}</p>
                @enderror
                <button class="rounded-xl bg-white px-4 py-3 text-sm font-semibold text-zinc-950">Ask coach</button>
            </form>
        </section>
    </main>
</x-layouts.app>
