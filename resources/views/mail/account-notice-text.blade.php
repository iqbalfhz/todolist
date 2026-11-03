{!! $greeting !!}

{!! $body !!}
@if ($warningKey)

{!! __($warningKey, ['support' => (string) $supportEmail]) !!}
@endif

{!! $closing !!}
{!! $signature !!}

---
{!! $automated !!}
@if ($supportEmail)
{!! __('mail.help', ['email' => $supportEmail]) !!}
@endif
