
<div>
<div class="mx-auto mt-12 max-w-2xl px-6">
    <span class="eyebrow">What people are saying</span>
    <h3 class="mb-6 text-2xl font-bold text-gray-900">Reviews</h3>

    <div class="space-y-4">
        @forelse($indoors->comments as $comment)
            <blockquote class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="text-sm leading-relaxed text-gray-700">&ldquo;{{ $comment->comment }}&rdquo;</p>
                <div class="mt-4 flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm text-brand-700">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <cite class="text-xs font-medium not-italic text-gray-500">User</cite>
                </div>
            </blockquote>
        @empty
            <p class="text-gray-500">No comments yet! Be the first to comment.</p>
        @endforelse
    </div>
</div>

    <div class="mx-auto mb-16 mt-8 max-w-2xl px-6">
        @auth
            <form action="{{ url('comments') }}" method="post" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                @csrf

                @if(session('message'))
                    <h4 class="mb-3 text-sm font-medium text-red-600">{{ session('message') }}</h4>
                @endif
                <input type="hidden" name="post_id" value="{{ $indoors->id }}">

                <h4 class="mb-3 text-sm font-semibold text-gray-800">Add a comment</h4>
                <textarea name="comments" class="field-input w-full" placeholder="Share your experience…" required></textarea>

                <div class="mt-3 flex items-center justify-between gap-4">
                    <p class="flex items-center gap-1.5 text-xs text-gray-500">
                        <i class="fa-solid fa-circle-info"></i> Be respectful and constructive.
                    </p>
                    <button type="submit" class="btn btn-primary btn-sm">Post Comment</button>
                </div>
            </form>
        @else
            <p class="text-center text-gray-500">Please <a href="{{ route('login') }}" class="font-semibold text-brand-600 underline">log in</a> to comment.</p>
        @endauth
    </div>
</div>
