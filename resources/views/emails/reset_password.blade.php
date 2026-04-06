<!-- resources/views/emails/reset_password.blade.php -->

<p>Hi {{ $name }},</p>
<p>Your password reset code is:</p>
<h2>{{ $code }}</h2>
<p>This code expires in <strong>15 minutes</strong>.</p>
<p>If you did not request this, please ignore this email.</p>
