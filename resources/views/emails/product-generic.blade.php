<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $product['name'] ?? 'Producto Tami' }}</title>
</head>
<body style="margin:0; padding:0; font-family:Arial,sans-serif; background:#f5f5f5;">
    <div style="max-width:600px; margin:20px auto; background:#ffffff; border-radius:8px; overflow:hidden; text-align:center; position:relative;">

        <img src="{{ $product['main_image'] ?? asset('email/default-product.webp') }}"
            alt="{{ $product['name'] ?? 'Producto Tami' }}" width="100%" style="display:block;">

        <h1 style="margin:20px 0 10px 0; color:#0d9488; font-size:2em;">{{ $product['name'] ?? 'Producto Tami' }}</h1>
        <p style="font-size:1.1em; color:#333; margin-bottom:10px;">{{ $product['model'] ?? '' }}</p>

        <div style="display:flex; justify-content:center; gap:20px; margin:20px 0; flex-wrap:wrap;">
            @if(!empty($product['features']))
                @foreach($product['features'] as $feature)
                    <div style="background:#0d9488; color:#fff; border-radius:12px; padding:10px 18px; min-width:120px; font-size:0.95em;">
                        {{ $feature }}
                    </div>
                @endforeach
            @endif
        </div>

        
        <h2 style="color:#0d9488; margin:20px 0 10px 0; font-size:1.2em;">{{ $product['description'] ?? '' }}</h2>
        <ul style="text-align:left; max-width:400px; margin:0 auto 20px auto; color:#333; font-size:1em;">
            @if(!empty($product['specs']))
                @foreach($product['specs'] as $spec)
                    <li style="margin-bottom:6px;">{{ $spec }}</li>
                @endforeach
            @endif
        </ul>

        @if(!empty($product['secondary_image']))
            <img src="{{ $product['secondary_image'] }}" alt="Imagen secundaria" width="60%" style="margin:20px auto; display:block;">
        @endif

        <div style="background:#0d9488; padding:16px 0; margin-top:20px;">
            <a href="https://www.facebook.com/tamiperu01" target="_blank" style="margin:0 10px; text-decoration:none;">
                <img src="https://cdn-icons-png.flaticon.com/128/14776/14776542.png" width="28" alt="Facebook" style="vertical-align:middle; border:none; display:inline-block;">
            </a>
            <a href="https://www.instagram.com/tami2_02590/?igsh=MWZoYnZjM3FxYXN5cQ%3D%3D" target="_blank" style="margin:0 10px; text-decoration:none;">
                <img src="https://cdn-icons-png.flaticon.com/128/15455/15455779.png" width="28" alt="Instagram" style="vertical-align:middle; border:none; display:inline-block;">
            </a>
            <a href="https://x.com/Tami_Maquinaria" target="_blank" style="margin:0 10px; text-decoration:none;">
                <img src="https://cdn-icons-png.flaticon.com/128/14417/14417709.png" width="28" alt="Twitter" style="vertical-align:middle; border:none; display:inline-block;">
            </a>
        </div>

        @if(!empty($product['video_url']))
        <div style="margin:30px 0;">
            <a href="{{ $product['video_url'] }}" target="_blank" style="display:inline-block; background:#D93535; color:#fff; padding:14px 32px; text-decoration:none; border-radius:14px; font-weight:bold; font-size:1.1em; border:2px solid #880000;">
                VER VIDEO AQUÍ
            </a>
        </div>
        @endif
    </div>
</body>
</html>
