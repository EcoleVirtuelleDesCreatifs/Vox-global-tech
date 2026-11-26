<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light">
<title>{{ $newsletter->subject }}</title>
</head>
<body style="margin:0;padding:0;background:#F6F5F2;font-family:'Outfit','Helvetica Neue',Arial,sans-serif;color:#111214;">
<div style="display:none;max-height:0;overflow:hidden;">{{ \Illuminate\Support\Str::limit($newsletter->intro, 120) }}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F6F5F2;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#FFFFFF;border:1px solid #DAD8D2;border-radius:16px;">
<tr><td style="padding:28px 32px 8px;border-bottom:1px solid #EDECE8;">
  <a href="{{ $track(route('home')) }}" style="text-decoration:none;color:#111214;font-size:24px;font-weight:600;letter-spacing:.06em;">VOX<span style="color:#D3141B;">●</span>TECH <span style="font-size:12px;letter-spacing:.4em;color:#5B5B61;">GLOBAL</span></a>
  <p style="margin:6px 0 16px;font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#5B5B61;">Newsletter VOX · {{ fr_date($newsletter->sent_at ?? now()) }}</p>
</td></tr>
<tr><td style="padding:24px 32px 8px;">
  <h1 style="margin:0 0 12px;font-size:26px;line-height:1.25;font-weight:400;">{{ $newsletter->subject }}</h1>
  @if ($newsletter->intro)<p style="margin:0 0 8px;font-size:16px;line-height:1.6;color:#2b2c30;">{!! nl2br(e($newsletter->intro)) !!}</p>@endif
</td></tr>
@foreach ($articles as $a)
<tr><td style="padding:16px 32px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #EDECE8;"><tr><td style="padding-top:16px;">
  <p style="margin:0 0 6px;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#B5121B;font-weight:600;">{{ $a->label() }} · {{ $a->rubrique?->name }}</p>
  <a href="{{ $track($a->url().'?utm_source=newsletter&utm_medium=email&utm_campaign=nl-'.$newsletter->id) }}" style="text-decoration:none;color:#111214;font-size:19px;line-height:1.35;font-weight:600;">{{ $a->title }}</a>
  <p style="margin:8px 0 10px;font-size:15px;line-height:1.55;color:#5B5B61;">{{ $a->chapo }}</p>
  <a href="{{ $track($a->url().'?utm_source=newsletter&utm_medium=email&utm_campaign=nl-'.$newsletter->id) }}" style="display:inline-block;background:#111214;color:#FFFFFF;text-decoration:none;padding:9px 18px;border-radius:999px;font-size:14px;">Lire l’article →</a>
  </td></tr></table>
</td></tr>
@endforeach
@if ($sponsor)
<tr><td style="padding:16px 32px;">
  <a href="{{ $track(route('campaign.click', $sponsor)) }}" style="display:block;text-decoration:none;background:#EDECE8;border-radius:12px;padding:16px 18px;color:#111214;">
    <span style="font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:#5B5B61;">Partenaire · {{ $sponsor->advertiser }}</span><br>
    <strong style="font-size:16px;">{{ $sponsor->title }}</strong> <span style="color:#B5121B;">{{ $sponsor->cta }} →</span>
  </a>
</td></tr>
@endif
<tr><td style="padding:24px 32px 28px;border-top:1px solid #EDECE8;">
  <p style="margin:0;font-size:12px;line-height:1.6;color:#5B5B61;">Vous recevez cet e-mail car vous êtes abonné·e à la Newsletter VOX de {{ setting('name') }}.<br>
  <a href="{{ $unsubscribe }}" style="color:#5B5B61;">Se désabonner en un clic</a> · <a href="{{ $track(route('confidentialite')) }}" style="color:#5B5B61;">Confidentialité</a></p>
</td></tr>
</table>
</td></tr></table>
@if ($pixel)<img src="{{ $pixel }}" width="1" height="1" alt="" style="display:block;border:0;">@endif
</body>
</html>
