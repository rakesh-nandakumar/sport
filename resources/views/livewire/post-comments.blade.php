
{{--<div class="bg-gray-100 p-6">--}}
{{--    <h2 class="text-lg font-bold mb-4">Comments</h2>--}}


{{--    <div class="flex flex-col space-y-4">--}}




{{--        @forelse($this->comments as $comment)--}}

{{--            <div class="bg-white p-4 rounded-lg shadow-md">--}}
{{--                <h3 class="text-lg font-bold">John Doe</h3>--}}
{{--                <p class="text-gray-700 text-sm mb-2">Posted on April 17, 2023</p>--}}
{{--                <p class="text-gray-700">This is a sample comment. Lorem ipsum dolor sit amet, consectetur adipiscing elit,--}}
{{--                    sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.--}}
{{--                </p>--}}
{{--            </div>--}}

{{--        @empty--}}
{{--            <p class="text-gray-700">No comments yet</p>--}}




{{--        @endforelse--}}
{{--        <div class="bg-white p-4 rounded-lg shadow-md">--}}
{{--            <h3 class="text-lg font-bold">John Doe</h3>--}}
{{--            <p class="text-gray-700 text-sm mb-2">Posted on April 17, 2023</p>--}}
{{--            <p class="text-gray-700">This is a sample comment. Lorem ipsum dolor sit amet, consectetur adipiscing elit,--}}
{{--                sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.--}}
{{--            </p>--}}
{{--        </div>--}}


{{--            @auth--}}
{{--                <form wire:submit.prevent="postComment" class="bg-white p-4 rounded-lg shadow-md">--}}
{{--                    <h3 class="text-lg font-bold mb-2">Add a comment</h3>--}}


{{--                    @error('comment') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror--}}

{{--                    <div class="mb-4">--}}
{{--                        <label class="block text-gray-700 font-bold mb-2" for="comment">--}}
{{--                            Comment--}}
{{--                        </label>--}}
{{--                        <textarea wire:model="comment"--}}
{{--                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"--}}
{{--                                  id="comment" rows="3" placeholder="Enter your comment"></textarea>--}}
{{--                    </div>--}}
{{--                    <button type="submit"--}}
{{--                            class="bg-cyan-500 hover:bg-cyan-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">--}}
{{--                        Submit--}}
{{--                    </button>--}}
{{--                </form>--}}

{{--                <button wire:click="work">work</button>--}}
{{--            @else--}}


{{--                <p>Login to post comments</p>--}}
{{--            @endauth--}}
{{--        </form>--}}
{{--    </div>--}}
{{--</div>--}}




<div class="pt-10 mt-10 border-t border-gray-100 comments-box">
    <h2 class="mb-5 text-2xl font-semibold text-gray-900">Discussions</h2>
    @auth
        <textarea wire:model="comment"
                  class="w-full p-4 text-sm text-gray-700 border-gray-200 rounded-lg bg-gray-50 focus:outline-none placeholder:text-gray-400"
                  cols="30" rows="7"></textarea>
        <button wire:click="postComment"
                class="inline-flex items-center justify-center h-10 px-4 mt-3 font-medium tracking-wide text-white transition duration-200 bg-gray-900 rounded-lg hover:bg-gray-800 focus:shadow-outline focus:outline-none">
            Post Comment
        </button>
    @else
        <a wire:navigate class="py-1 text-yellow-500 underline" href="{{ route('login') }}"> Login to Post Comments</a>
    @endauth
    <div class="px-3 py-2 mt-5 user-comments">
        @forelse($this->comments as $comment)
            <div class="comment [&:not(:last-child)]:border-b border-gray-100 py-5">
                <div class="flex items-center mb-4 text-sm user-meta">
{{--                    <x-posts.author :author="$comment->user" size="sm" />--}}
                    <span class="text-gray-500">. {{ $comment->created_at->diffForHumans() }}</span>
                </div>
                <div class="text-sm text-justify text-gray-700">
                    {{ $comment->comment }}
                </div>
            </div>
        @empty
            <div class="text-center text-gray-500">
                <span> No Comments Posted</span>
            </div>
        @endforelse
    </div>
    <div class="my-2">
{{--        {{ $this->comments->links() }}--}}
    </div>
</div>
