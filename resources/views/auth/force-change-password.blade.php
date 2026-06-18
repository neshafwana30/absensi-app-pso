<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f5f6fa;">

    <div style="max-width: 420px; margin: 90px auto; background: white; padding: 28px; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">

        <h2 style="margin-bottom: 10px;">Ganti Password</h2>

        <p style="color: #555; line-height: 1.5;">
            Untuk keamanan akun, silakan ganti password terlebih dahulu sebelum mengakses aplikasi.
        </p>

        @if ($errors->any())
            <div style="background: #ffecec; color: #c0392b; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="/force-change-password">
            @csrf

            <div style="margin-bottom: 14px;">
                <label>Password Baru</label>
                <input 
                    type="password" 
                    name="password" 
                    required
                    style="width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #ccc; border-radius: 8px;"
                >
            </div>

            <div style="margin-bottom: 18px;">
                <label>Konfirmasi Password Baru</label>
                <input 
                    type="password" 
                    name="password_confirmation" 
                    required
                    style="width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #ccc; border-radius: 8px;"
                >
            </div>

            <button 
                type="submit"
                style="width: 100%; padding: 11px; border: none; border-radius: 8px; background: #2f80ed; color: white; font-weight: bold;"
            >
                Simpan Password
            </button>
        </form>

    </div>

</body>
</html>
