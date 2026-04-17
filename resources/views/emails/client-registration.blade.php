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

    </main>
    <footer style="margin-top: 40px; border-top: 1px solid #e5e7eb; padding-top: 20px; color: #6b7280; font-size: 0.875rem;">
        <p>Si tienes alguna pregunta, no dudes en <a href="mailto:yuntasproducciones@gmail.com" style="color: #14b8a6; text-decoration: none;">contactarnos</a>.</p>
        <p>&copy; {{ date('Y') }} Tami - Todos los derechos reservados.</p>
    </footer>

</body>
</html>
