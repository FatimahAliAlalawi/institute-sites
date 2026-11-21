<div class="faq">
    @foreach ($faqs as $faq)
        <details @if ($loop->first) open @endif>
            <summary>{{ $faq->question }}</summary>
            <p class="prose">{{ $faq->answer }}</p>
        </details>
    @endforeach
</div>
