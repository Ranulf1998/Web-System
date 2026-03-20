<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop approved</title>
</head>
<body>
    <p>Hello {{ data_get($tenant->settings, 'onboarding.owner.name', 'there') }},</p>
    <p>Your shop <strong>{{ $tenant->name }}</strong> has been approved.</p>
    <p>You can now log in here: <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
    <p>Login email: {{ $ownerEmail }}</p>
    <p>Temporary password: {{ $generatedPassword }}</p>
    <p>Thank you,<br>BrewCloud Team</p>
</body>
</html>
