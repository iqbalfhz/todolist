<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<title>{{ $subjectLine }}</title>
</head>
<body style="margin:0; padding:0; background-color:#FCF3F7;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FCF3F7;">
<tr>
<td align="center" style="padding:32px 16px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:480px; background-color:#FFFFFF; border:1px solid #EFD9E2; border-radius:16px;">
<tr>
<td style="background-color:#CB2F69; border-radius:15px 15px 0 0; padding:24px 32px; font-family:Arial, Helvetica, sans-serif; font-size:22px; line-height:28px; font-weight:bold; color:#FFFFFF;">{{ $signature }}</td>
</tr>
<tr>
<td style="padding:32px; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:24px; color:#2B1A22;">
<p style="margin:0 0 16px;">{{ $greeting }}</p>
<p style="margin:0 0 24px;">{{ $intro }}</p>
<p style="margin:0 0 24px; text-align:center;"><span style="display:inline-block; padding:16px 28px; background-color:#F9E4EC; border-radius:12px; font-family:'Courier New', Courier, monospace; font-size:32px; line-height:40px; font-weight:bold; letter-spacing:8px; color:#CB2F69;">{{ $code }}</span></p>
<p style="margin:0 0 16px;">{{ $validity }}</p>
<p style="margin:0 0 24px;">{{ $outro }}</p>
<p style="margin:0;">{{ $closing }}<br>{{ $signature }}</p>
</td>
</tr>
<tr>
<td style="padding:20px 32px; border-top:1px solid #EFD9E2; font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:18px; color:#6E5A63;">
{{ $automated }}
@if ($supportEmail)
{!! __('mail.help', ['email' => '<a href="mailto:'.e($supportEmail).'" style="color:#B42A5E;">'.e($supportEmail).'</a>']) !!}
@endif
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
