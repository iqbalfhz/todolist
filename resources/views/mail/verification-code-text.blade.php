{!! $greeting !!}

{!! $intro !!}

{!! $code !!}

{!! $validity !!}

{!! $outro !!}

{!! $closing !!}
{!! $signature !!}

---
{!! $automated !!}
@if ($supportEmail)
{!! __('mail.help', ['email' => $supportEmail]) !!}
@endif
