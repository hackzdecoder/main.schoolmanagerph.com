<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .content {
            padding: 30px;
        }

        .success-badge {
            background: #10b981;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }

        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #eee;
        }

        .button-link {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }

        .button:hover {
            background: #5a67d8;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏫 School Manager PH</h1>
        </div>

        <div class="content">
            <div style="text-align: center;">
                <span class="success-badge">✓ TEST EMAIL</span>
            </div>

            <h2 style="text-align: center; color: #333;">Hello!</h2>

            <p style="font-size: 16px;">This is a test email from your School Manager application.</p>

            <div class="info-box">
                <p style="margin: 0;"><strong>✅ Email Configuration Status:</strong></p>
                <p style="margin: 10px 0 0 0;">If you're reading this, your mail configuration is working correctly!</p>
            </div>

            <p><strong>📋 Email Details:</strong></p>
            <ul style="list-style-type: none; padding: 0;">
                <li style="margin-bottom: 10px;">📅 <strong>Date & Time:</strong> {{ now()->format('F j, Y, g:i a') }}
                </li>
                <li style="margin-bottom: 10px;">📧 <strong>From:</strong> {{ config('mail.from.address') }}</li>
                <li style="margin-bottom: 10px;">🖥️ <strong>Environment:</strong> {{ app()->environment() }}</li>
                <li style="margin-bottom: 10px;">🔧 <strong>Mailer:</strong> {{ config('mail.default') }}</li>
            </ul>

            <div style="text-align: center;">
                <a href="{{ config('app.url') }}" class="button-link">Visit School Manager</a>
            </div>

            <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

            <p style="font-style: italic; color: #666; text-align: center;">
                "This is an automated test message from your School Manager PH application."
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} School Manager PH. All rights reserved.</p>
            <p style="margin-top: 5px; font-size: 11px;">This is a test email, no action is required.</p>
        </div>
    </div>
</body>

</html>