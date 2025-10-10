<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product['name'] ?? 'Producto Tami' }}</title>
</head>
<body style="margin:0; padding:0; font-family:Arial,sans-serif; background:#f5f5f5;">
    <div style="max-width:600px; margin:20px auto; background:#ffffff; overflow:hidden; text-align:center;">
        
        <!-- Imagen completa del email (diseño personalizado del producto) -->
        <img src="{{ $product['main_image'] ?? asset('email/default-product.webp') }}"
            alt="{{ $product['name'] ?? 'Producto Tami' }}" 
            style="width:100%; max-width:600px; display:block; height:auto;">

        <!-- Botón de video (si existe) -->
        @if(!empty($product['video_url']))
        <div style="padding:30px 0 40px 0; background:#ffffff;">
            <a href="{{ $product['video_url'] }}" target="_blank" 
               style="display:inline-block; background:#DC2626; color:#fff; padding:14px 40px; text-decoration:none; border-radius:25px; font-weight:bold; font-size:14px; text-transform:uppercase; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                VER VIDEO AQUÍ
            </a>
        </div>
        @endif

    </div>
</body>
</html>
