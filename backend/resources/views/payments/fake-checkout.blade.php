<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('payments.fake.title') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f4f4f5; color: #18181b; font-family: system-ui, "Segoe UI", Tahoma, sans-serif; }
        main { width: min(420px, calc(100% - 32px)); background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 10px 30px rgb(0 0 0 / .08); }
        .notice { background: #fef3c7; color: #92400e; border-radius: 8px; padding: 10px 12px; font-size: 14px; }
        h1 { font-size: 20px; margin: 20px 0 4px; }
        .amount { font-size: 32px; font-weight: 700; margin: 12px 0 24px; }
        .muted { color: #71717a; font-size: 13px; word-break: break-all; }
        button { width: 100%; padding: 12px; border-radius: 8px; border: 0; font-size: 16px; cursor: pointer; margin-top: 10px; }
        .pay { background: #18181b; color: #fff; }
        .fail { background: #fff; color: #b91c1c; border: 1px solid #fecaca; }
        button:disabled { opacity: .6; cursor: wait; }
    </style>
</head>
<body>
<main>
    <p class="notice">{{ __('payments.fake.notice') }}</p>
    <h1 dir="auto">{{ $payment->course->title }}</h1>
    <p class="muted">{{ $payment->student->email }} · {{ $payment->reference }}</p>
    <p class="amount">{{ number_format((float) $payment->amount, 2) }} {{ __('payments.currency') }}</p>

    @if ($open)
        <button class="pay" data-result="pay">{{ __('payments.fake.pay', ['amount' => number_format((float) $payment->amount, 2).' '.__('payments.currency')]) }}</button>
        <button class="fail" data-result="fail">{{ __('payments.fake.fail') }}</button>
    @else
        <p>{{ $payment->status->label() }}</p>
        <p><a href="{{ $resultUrl }}">{{ $resultUrl }}</a></p>
    @endif
</main>

<script>
    const callbacks = @json(['pay' => $pay, 'fail' => $fail]);

    document.querySelectorAll('button[data-result]').forEach((button) => {
        button.addEventListener('click', async () => {
            document.querySelectorAll('button').forEach((b) => (b.disabled = true));
            button.textContent = @json(__('payments.fake.sending'));
            const { payload, hmac } = callbacks[button.dataset.result];

            // The same request Paymob's servers would send.
            await fetch(@json($callbackUrl) + '?hmac=' + hmac, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            window.location.href = @json($resultUrl);
        });
    });
</script>
</body>
</html>
