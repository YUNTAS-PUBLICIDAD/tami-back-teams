<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro Exitoso - Tami</title>
</head>
<body>
    <main>
        <h1>{{ $data['name'] ? "¡Bienvenido, " . $data['name'] . "!" : "¡Bienvenido!" }}</h1>
        
    <div style="margin-bottom: 20px; line-height: 1.6; color: #374151;">
        {!! $data['message'] ?? 'Gracias por registrarte en Tami. Estamos encantados de tenerte con nosotros.' !!}
    </div>

        @if(!empty($data['image_path']) && file_exists($data['image_path']))
            <div style="margin-top: 20px; text-align: center;">
                <img src="{{ $message->embed($data['image_path']) }}" alt="Imagen de bienvenida" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            </div>
        @elseif(!empty($data['image_url']))
            <div style="margin-top: 20px; text-align: center;">
                <img src="{{ $data['image_url'] }}" alt="Imagen de bienvenida" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            </div>
        @endif

        @php
            $btnText = $data['email_btn_text'] ?? '¡REGISTRARME!';
            $btnLink = $data['email_btn_link'] ?? '#';
            $btnBgColor = $data['email_btn_bg_color'] ?? '#00AFA0';
            $btnTextColor = $data['email_btn_text_color'] ?? '#FFFFFF';
        @endphp
        
        @if($btnText && $btnLink && $btnLink !== '#')
            <div style="margin-top: 30px; text-align: center;">
                <a href="{{ $btnLink }}" style="display: inline-block; padding: 12px 30px; background-color: {{ $btnBgColor }}; color: {{ $btnTextColor }}; text-decoration: none; border-radius: 6px; font-weight: bold; font-family: sans-serif; font-size: 16px;">
                    {{ $btnText }}
                </a>
            </div>
        @endif
        <div style="display:none; white-space:nowrap; font:15px courier; line-height:0;">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>

    </main>
    <footer style="margin-top: 40px; border-top: 1px solid #e5e7eb; padding-top: 20px; color: #6b7280; font-size: 0.875rem;">
        <p>Si tienes alguna pregunta, no dudes en <a href="mailto:yuntasproducciones@gmail.com" style="color: #14b8a6; text-decoration: none;">contactarnos</a>.</p>
        <p>&copy; {{ date('Y') }} Tami - Todos los derechos reservados.</p>
    </footer>

</body>
</html>
