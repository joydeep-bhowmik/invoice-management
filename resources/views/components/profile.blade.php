@props(['user'])
<flux:avatar size="sm" :src="$user->avatar_url"
    :initials="collect(preg_split('/\s+/', trim($user->name)))
                    ->filter()
                    ->when(fn ($c) => $c->count() > 1,
                        fn ($c) => collect([$c->first(), $c->last()]),
                        fn ($c) => collect([$c->first()])
                    )
                    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                    ->implode('')"
    class="bg-blue-100 text-blue-800" />
