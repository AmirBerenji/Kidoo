<div class="container">

    <h1>Child Tag Alert 🔔</h1>

    <p>
        We detected a scan of your child's tag.
        Here are the details:
    </p>

    <table style="width:100%;border-collapse:collapse;margin:20px 0;">
        <tr>
            <td style="padding:10px;background:#f5f5f5;font-weight:bold;">
                Child Name
            </td>
            <td style="padding:10px;">
                {{ $childName }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px;background:#f5f5f5;font-weight:bold;">
                Latitude
            </td>
            <td style="padding:10px;">
                {{ $lat }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px;background:#f5f5f5;font-weight:bold;">
                Longitude
            </td>
            <td style="padding:10px;">
                {{ $lng }}
            </td>
        </tr>
    </table>

    <p>
        <a href="https://maps.google.com/?q={{ $lat }},{{ $lng }}"
           style="background:#4f46e5;color:#fff;padding:10px 20px;
                  border-radius:6px;text-decoration:none;">
            View on Google Maps
        </a>
    </p>

    <p style="color:#888;font-size:13px;margin-top:30px;">
        Scanned at: {{ $scannedAt }}
    </p>

    <p>
        Best regards,<br>
        {{ config('app.name') }}
    </p>

</div>
