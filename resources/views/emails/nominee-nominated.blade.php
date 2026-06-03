<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>You have been nominated</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2933; line-height: 1.6;">
    <p>Hello {{ $nomination->nominee->full_name }},</p>

    <p>
        You have been nominated for the Extraordinary African awards in the
        {{ $nomination->category->name }} category.
    </p>

    <p>
        <strong>Nomination reason:</strong><br>
        {{ $nomination->nomination_reason }}
    </p>

    <p>
        Our team will review the nomination and contact you if any further information is needed.
    </p>

    <p>Thank you,<br>Extraordinary African Team</p>
</body>
</html>
