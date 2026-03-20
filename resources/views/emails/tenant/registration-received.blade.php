<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration in progress</title>
</head>
<body>
    <p>Hello {{ data_get($tenant->settings, 'onboarding.owner.name', 'there') }},</p>
    <p>Your registration for <strong>{{ $tenant->name }}</strong> is in progress.</p>
    <p>Our super admin team will review your request. We will email your login link and credentials once approved.</p>
    <p>Thank you,<br>BrewCloud Team</p>
</body>
</html>
