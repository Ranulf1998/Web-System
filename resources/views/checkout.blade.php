<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <h1>Checkout</h1>
    <button id="checkout-button">Pay $20.00</button>

    <script>
        const stripe = Stripe('{{ config('services.stripe.key') }}');
        document.getElementById('checkout-button').addEventListener('click', function() {
            fetch('/create-checkout-session', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(session => stripe.redirectToCheckout({ sessionId: session.id }))
            .then((result) => {
                if (result && result.error) {
                    console.error('Stripe redirect error:', result.error.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>