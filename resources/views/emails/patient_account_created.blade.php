<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Patient Account Created</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 0;">
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td align="center" style="padding: 40px 0;">
            <table border="0" cellpadding="0" cellspacing="0" width="600"
                   style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

                <!-- Header -->
                <tr>
                    <td align="center" bgcolor="#2563eb" style="padding: 20px 30px;">
                        <h1 style="margin: 0; font-size: 22px; font-weight: bold; color: #ffffff;">
                            Welcome to Our System
                        </h1>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding: 30px; color: #333333;">
                        <p style="font-size: 16px; margin-bottom: 20px;">
                            Your patient account has been created successfully 🎉
                        </p>

                        <table width="100%" cellpadding="8" cellspacing="0"
                               style="border: 1px solid #e5e7eb; border-radius: 8px; background-color: #f9fafb; margin-bottom: 20px;">
                            <tr>
                                <td style="font-weight: bold; width: 120px;">Email:</td>
                                <td>{{ $email }}</td>
                            </tr>
                            <tr>
                                <td style="font-weight: bold;">Password:</td>
                                <td>{{ $password }}</td>
                            </tr>
                        </table>

                        <p style="font-size: 14px; color: #6b7280;">
                            Please login using the credentials above and
                            <strong>change your password</strong> after first login for security.
                        </p>

                        <!-- Button -->
                        <p style="text-align: center; margin-top: 30px;">
                            <a href="{{ url('/login') }}"
                               style="display: inline-block; background-color: #2563eb; color: #ffffff;
                                          padding: 12px 24px; font-size: 16px; font-weight: bold;
                                          border-radius: 6px; text-decoration: none;">
                                Login Now
                            </a>
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center" style="background-color: #f3f4f6; padding: 15px; font-size: 12px; color: #6b7280;">
                        &copy; {{ date('Y') }} Our System. All rights reserved.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
