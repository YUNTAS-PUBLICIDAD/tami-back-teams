<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Cliente;
use Illuminate\Support\Str;
use App\Services\ChatbotService;
use App\Traits\ApiResponseTrait; 

class ChatbotController extends Controller
{
    use ApiResponseTrait; 

    // Inyectamos el nuevo servicio mediante el constructor
    public function __construct(
        private ChatbotService $chatbotService
    ) {}
    
    private $numeroWhatsapp = "51978883199";

    private $respuestasSaludo = [
        '¡Hola! Qué gusto saludarte, soy el asistente de Tami 🤖. ¿Qué tipo de producto estás buscando hoy?',
        '¡Hola, bienvenido a Tami! 👋 ¿En qué te puedo ayudar o qué producto buscas?',
        '¡Hola hola! Soy el asistente virtual de Tami. Cuéntame, ¿qué estás buscando?',
        '¡Hola! Es un placer atenderte. 😊 ¿Buscabas información sobre algún artículo en especial?',
        '¡Buenas! Soy tu asesor virtual en Tami. Escribe el nombre del producto que buscas y te ayudaré al instante.',
        '¡Hey! 😁 Bienvenido a Tami. Estoy aquí para ayudarte a ubicar lo que necesitas, ¿qué tienes en mente?',
        '¡Saludos! Soy el bot amigable de Tami 🚀. Déjame saber si buscas algo específico en nuestro catálogo.',
        '¡Hola y bienvenido! Estamos felices de recibirte. ¿Podrías indicarme qué andas buscando hoy?',
        '¡Buen día! ☀️ Desde Tami te damos la bienvenida. Cuéntame en qué estás interesado y te mostraré opciones.',
        '¡Hola, hola! Qué bueno verte por aquí. Escribe alguna palabra clave del producto y veamos si lo tenemos.'
    ];

    private $respuestasPrecio = [
        '💳 Nuestros precios varían según el modelo y stock actual. Para darte una cotización exacta al instante, escríbenos a nuestro WhatsApp:',
        '💰 Para brindarte toda la información de costos y métodos de pago de forma personalizada, hemos habilitado nuestro WhatsApp:',
        'Te comento que manejamos diferentes precios según el producto. ¿Me escribes a WhatsApp para cotizarte sin compromiso?',
        '¡Claro! Los precios dependen del detalle de tu pedido. Un asesor te dará la lista completa de precios por WhatsApp aquí:',
        'Para asegurarnos de darte el precio actualizado y descuentos disponibles, te redirigiré con un experto a WhatsApp:',
        'Dado que los costos fluctúan según promociones vigentes 📉, uno de nuestros expertos te brindará la cotización ideal en WhatsApp:',
        'Para darte un excelente precio oficial (y a lo mejor con algún dscto. 😁), solo indícanoslo vía mensaje por WhatsApp:',
        '💵 La información de montos se maneja directamente con nuestros ejecutivos comerciales. Te dejamos la línea oficial de atención acá abajo:',
        'Revisemos primero tu ubicación y cantidad. A partir de allí podremos armarte el presupuesto por nuestra línea de WhatsApp segura 🔒:',
        'Cada artículo presenta variables técnicas que inciden en su valor comercial. Por ello preferimos atender precio netamente por WhatsApp comercial aquí:'
    ];

    private $respuestasUbicacion = [
        '📍 Realizamos envíos a todo el Perú de forma rápida y segura. Para coordinar una entrega o recojo, contacta a un asesor:',
        '🚚 Nuestras entregas son 100% seguras y llegamos a nivel nacional. Coordina la tuya rápidamente por nuestro WhatsApp:',
        'Para detalles sobre nuestra dirección, horarios físicos o programación de envíos, por favor conversa con nuestro equipo:',
        '¡Llegamos a donde tú estés! 📦 Coordina el lugar de entrega de tus productos directamente con ventas aquí:',
        'Te podemos enviar el pedido a domicilio o puedes pasar a recogerlo. Escríbele a nuestro asesor para acordar todo:',
        '🌍 Actualmente trabajamos envíos garantizados. Si tienes interés en retirar tu propio paquete o envío a provincia, escríbenos primero para darte detalles:',
        'Las zonas de despacho, direcciones presenciales y costos de ruta los validamos rápidamente si pinchas debajo y te comunicas vía WA.',
        '🗺️ Si buscas retirar el pedido hoy o programar ruta para esta semana, ponte en contacto ahora mismo con nuestra área de entregas.',
        'A nosotros también nos emociona que llegue rápido. 🏃‍♂️ ¡Habla ya con logística en WhatsApp presionando en este cuadro!',
        'Consulta direcciones de las oficinas o plazos de entrega que te correspondan simplemente enviándonos la información en nuestro canal de WhatApp oficial 💬:'
    ];

    private $respuestasDefecto = [
        'Actualmente solo puedo ayudarte a buscar artículos de nuestro catálogo. Para consultas de precios, stock u otras dudas, nuestro equipo humano te atenderá encantado. 👇',
        'Ups, no entendí muy bien 😅. Solo puedo buscar nombres de productos. Si necesitas algo más complejo, te invito a nuestro WhatsApp:',
        'Lo siento, mi base de datos solo contiene nuestro catálogo. Si tienes preguntas técnicas o deseas saber el precio, conversemos en WhatsApp:',
        'Aún sigo aprendiendo 🤖. Para no darte información errónea, es mejor que hables directamente con nuestro equipo de soporte:',
        'No encontré lo que buscabas o no entendí la pregunta. ¿Te gustaría que un asesor nuestro de Tami te atienda en persona?',
        'Hmm... Creo que tus palabras están fuera de mis actuales directrices. Por precaución prefiero dejarte comunicarte aquí 📞',
        'Solo soy capaz de hacer búsquedas básicas. Para una charla sobre cosas complejas u otras solicitudes me apoyo en humanos. Entra aquí abajo 😉',
        '😅 Creo que eso está un poco difícil de contestar para mí. Te enlazo para un contacto directo y oficial. 👉',
        'Sinceramente, no lograré responder tu consulta. Disculpa. Para apoyarte me gustaría pasarte con mis diseñadores o el gerente responsable de área. 🙋‍♂️',
        'Actualmente no dispongo de ese dato de ventas. ¡Lo bueno es que el botón inferior sí te lo da! 🔴 Entra e informa tu cuestionamiento directo al humano:'
    ];

    private $respuestasPago = [
        '💳 Aceptamos Yape, Plin, transferencias bancarias y todas las tarjetas. Para coordinar tu pago y confirmar tu pedido, haz clic aquí:',
        '🏧 Tenemos pasarela de pago para tarjetas y también opciones de depósito (Yape/Plin/Transferencia). Escríbenos para enviarte los números de cuenta:',
        '¡Facilitamos tu compra! Puedes pagar con las principales tarjetas o apps del Banco. Un asesor te guiará con el pago por WhatsApp:',
        'Para brindarte los números de cuenta oficiales o el link de pago seguro de Tami, por favor contacta a ventas:',
        'Recibimos pagos de todos los bancos. 💵 Por temas de seguridad, los números de Yape y cuentas solo te los entregará nuestro asesor si le escribes:',
        'No te preocupes por cómo pagar, ¡tenemos todas las alternativas en el Checkout y por depósitos directos! Consulta tus dudas dándole click acá abajo.',
        'Trabajamos con casi cada billetera móvil y banco local nacional para pago. Los asesores pueden certificar la compra de frente y brindarte las cuentas de Tami 💰.',
        'Para confirmar el uso de abonos contra entrega u otra modalidad inusual, pregúntale ahora mismo de frente a ventas (haz clic).',
        'Nos aseguramos en que las trasferencias y pagos vía Niubiz o Mercado Pago sean las más sólidas. Para pedir su voucher dirígete acá 👇⬇️',
        'Todos los pagos efectuados con la billetera digital o por ventanilla aplican rápido; por tanto la comunicación con Ventas debe hacerse por WA ahora 🕒.'
    ];

    private $respuestasGarantia = [
        '🛡️ Todos nuestros productos originales cuentan con garantía contra defectos de fábrica. Para tramitar algún inconveniente o reclamo, habla directo con nosotros:',
        '¡Tu compra está segura! Si has tenido algún problema con un pedido o tienes dudas sobre la política de devoluciones, nuestro equipo te ayudará en WhatsApp:',
        'Para reportar una falla, ejercer garantía o recibir soporte post-venta personalizado, nuestra área encargada te atiende aquí:',
        'Nos tomamos muy en serio la calidad de Tami. Si requieres ayuda técnica o devolver un producto, haz clic abajo para conversar con soporte:',
        '¿Dudas sobre garantías? Tranquilo/a. Escribe a nuestro canal oficial adjuntando tu número de pedido o boleta/factura:',
        'Si requieres reclamar por una descompostura interna de fábrica o daños logísticos, un asesor se encarga de orientar ese caso de forma prioritaria.',
        '¡En Tami la Post Venta es primordial! Si cuentas con algún percance por favor accede sin demoras al panel de atención por chat.',
        'Ofrecemos soporte integral sobre compras realizadas y cubrimos imperfectos si proceden según evaluación de técnicos 👨‍🔧. Inicia aquí.',
        '¿Vino con detalles extraños o es una desconfiguración? Para tramitar devolución 💡 envía fotografías para iniciar el proceso de inmediato.',
        'Lamento muchísimo oír lo de las averías eventuales en tu adquisición. Ayúdanos con evidencia dirigiéndote al grupo asignado cliqueando abajo:'
    ];

    private $respuestasHorario = [
        '🕒 Nuestro horario de atención por WhatsApp y redes es de Lunes a Sábado, en horario comercial. ¡Pero puedes dejarnos tu mensaje ahora mismo!',
        'Trabajamos arduamente durante la semana. Si nos escribes ahora al WhatsApp, te responderemos en el menor tiempo posible ⏳:',
        'Aunque estemos fuera de horario, nuestro buzón de WhatsApp siempre recibe solicitudes y respondemos apenas estemos en línea. ¡Escríbenos!',
        'Para conocer los horarios exactos de tienda física o si deseas que te contactemos en la mañana, déjanos tu interés por aquí:',
        '¡Claro! Atendemos de corrido en días de semana. Puedes confirmar si estamos abiertos y activos justo ahora mandándonos un mensaje:',
        'Por lo normal, nuestro equipo descansa los domingos, pero los mensajes pueden gestionarse el lunes temprano. 📲 Déjelo cargando ya. ',
        'Normalmente laboramos turnos diurnos. Si tienes alguna prisa para una recolección escribe pidiendo las agendas reales vigentes 😉',
        'Estamos enfocados en brindar respuestas durante el horario hábil; ¡siempre un representante lo revisará! Intenta el siguiente contacto interno 🧑‍💻',
        'Las líneas de WhatsApp Oficial de Empresa usualmente están de sol a sol ☀️ pero puedes dejarnos tu texto e inquietud sin compromisos de horario en el enlace.',
        '¡Nuestros relojes no paran para el negocio! Pese a esto el horario presencial final lo dará ventas si los requieres clicando el link 🕓.'
    ];

    private $respuestasAgradecimiento = [
        '¡De nada! Ha sido un placer. Recuerda que ante cualquier duda adicional siempre estaremos para ti. 🌟',
        '¡A ti por preferirnos! Si más adelante decides hacer una compra o consultar otra cosa, por aquí andaré. Que tengas un súper día. ✨',
        '¡Fue un gusto! Cuídate mucho. Aquí te dejamos el botón de WhatsApp por si nos necesitas luego.',
        '¡No hay problema! Siempre es bueno ayudar. Que todo vaya excelente con tus proyectos de Tami. 👋',
        '¡Un placer servirte! Nos vemos pronto. ¡Cualquier otra consulta técnica o comercial, aquí estaremos!',
        '¡Todo bien, suerte en tus labores y vuelve cuando desees investigar sobre otras promociones!. 😊👍',
        'Gracias por tu comunicación. Quedo en stand-by 🤖 para el futuro, deseándote buen día.',
        'Perfecto. Para ser un Bot, amé charlar contigo jaja 😄. Hasta pronto.',
        '¡Ok, listo! Yo seguiré online aquí en Tami por si tuvieras interés luego en consultar qué catálogo manejamos.',
        'Bien, comprendido. Te veo lueguito para explorar artículos de Tami 💼.'
    ];

    private $respuestasMaquinaria = [
        "🔥 En Maquinaria industrial somos líderes. Contamos con:\n• Selladoras de bolsas\n• Compresoras de aire\n• Cortadoras de materiales\n• Codificadoras\n¿Ventas te puede cotizar alguna mañana?",
        "Nuestras máquinas industriales son el motor de muchos emprendimientos. Tenemos selladoras, compresoras y más. ¿Buscas algún modelo?",
        "Para producción industrial contamos con un catálogo completo de maquinaria certificada. 🏗️ ¿Deseas ver selladoras o compresoras?",
        "Si buscas potencia para tu fábrica, nuestras compresoras y selladoras industriales son ideales. Consulta stock aquí:",
        "Mejora tu línea de producción con maquinaria Tami. Ofrecemos equipos industriales de alto rendimiento. ¡Habla con un asesor!"
    ];

    private $respuestasNegocio = [
        "🚀 ¡Impulsamos tu emprendimiento! En Tami te ayudamos con:\n• Maquinaria Industrial (producción)\n• Decoración Comercial (imagen de local)\n¿En qué área necesitas apoyo hoy?",
        "Para un negocio exitoso necesitas buena producción y una imagen impecable. 🏪 ¿Buscas maquinaria o decoración para tu local?",
        "En Tami somos aliados de los emprendedores. Te ofrecemos desde equipos industriales hasta paneles decorativos. ¿Cuál te interesa?",
        "Si estás iniciando una empresa, tenemos todo en maquinaria y acabados decorativos. 📈 ¡Escríbenos para enviarte el catálogo!",
        "Nuestro enfoque comercial es ayudarte a crecer. Contamos con soluciones industriales y de decoración empresarial. ¿Qué buscas?"
    ];

    private $respuestasHumano = [
        '¡Claro! Te paso con un asesor para que te ayude personalmente 😊.',
        'Sin problemas, un experto de Tami te atenderá en breve por WhatsApp.',
        'Entendido. Para una atención personalizada, haz clic en el siguiente enlace:',
    ];

    public function responder(Request $request)
    {
        $mensaje = $request->input('mensaje', '');
        $context = $request->input('context');

        if ($context && isset($context['paso'])) {
            $paso = $context['paso'];

            switch ($paso) {
                case 'neg_lista': return $this->pasoNegLista($mensaje);
                case 'maq_lista': return $this->pasoMaqLista($mensaje);
                case 'maq_uso': return $this->pasoMaqUso($mensaje, $context);
                case 'maq_ciudad': return $this->pasoMaqCiudad($mensaje, $context);
                case 'maq_datos_contacto': return $this->pasoMaqDatosContacto($mensaje, $context);
                case 'deco_lista': return $this->pasoDecoLista($mensaje, $context);
                case 'asesor_tipo_negocio': return $this->pasoAsesorTipoNegocio($mensaje);
                case 'asesor_producto': return $this->pasoAsesorProducto($mensaje, $context);
                case 'asesor_ciudad': return $this->pasoAsesorCiudad($mensaje, $context);
            }
        }

        return $this->manejarChatbotLibre($request);
    }

    private function iniciarFlujoNegocio(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'tipo'      => 'opciones',
            'respuesta' => 'Perfecto 👍 Estos son nuestros productos disponibles:',
            'opciones'  => $this->obtenerProductosPorSeccion('Negocio'),
            'context'   => ['paso' => 'neg_lista'],
        ]);
    }

    private function pasoNegLista(string $mensaje): \Illuminate\Http\JsonResponse
    {
        $producto = Producto::where('seccion', 'Negocio')
            ->where('nombre', 'LIKE', '%' . $mensaje . '%')
            ->first();

        if (!$producto) {
            return response()->json([
                'tipo'      => 'opciones',
                'respuesta' => 'No encontré ese producto 🤔. Por favor elige una de las opciones:',
                'opciones'  => $this->obtenerProductosPorSeccion('Negocio'),
                'context'   => ['paso' => 'neg_lista'],
            ]);
        }

        $imagen      = $producto->imagenes()->first();
        $descripcion = $producto->descripcion
            ? Str::limit($producto->descripcion, 200)
            : 'Producto de alta calidad ideal para tu negocio.';

        return response()->json([
            'tipo'      => 'opciones',
            'respuesta' => "¡Excelente elección! 👍\n\n{$descripcion}\n\n✔ Ideal para uso comercial\n✔ Alta calidad\n✔ Envíos a todo el Perú\n\nTe puedo enviar el precio actualizado y más detalles.\n\n👉 ¿Es para uso personal o negocio?",
            'producto'  => [
                'nombre' => $producto->nombre,
                'imagen' => $imagen ? $imagen->url_imagen : null,
            ],
            'opciones'  => [
                ['label' => '🏠 Uso personal', 'valor' => 'personal'],
                ['label' => '🏢 Negocio',       'valor' => 'negocio_uso'],
            ],
            'context'   => [
                'paso'     => 'maq_uso',
                'flujo'    => 'negocio',
                'producto' => $producto->nombre,
            ],
        ]);
    }


    // FLUJO MAQUINARIA

    private function iniciarFlujoMaquinaria(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'tipo'      => 'opciones',
            'respuesta' => 'Perfecto 👍 Estas son nuestras máquinas disponibles:',
            'opciones'  => $this->obtenerProductosPorSeccion('Maquinaria'),
            'context'   => ['paso' => 'maq_lista'],
        ]);
    }

    private function pasoMaqLista(string $mensaje): \Illuminate\Http\JsonResponse
    {
        $producto = Producto::where('seccion', 'Maquinaria')
            ->where('nombre', 'LIKE', '%' . $mensaje . '%')
            ->first();
        if (!$producto) {
            return response()->json([
                'tipo'      => 'opciones',
                'respuesta' => 'No encontré esa máquina 🤔. Por favor elige una de las opciones:',
                'opciones'  => $this->obtenerProductosPorSeccion('Maquinaria'),
                'context'   => ['paso' => 'maq_lista'],
            ]);
        }

        $imagen      = $producto->imagenes()->first();
        $descripcion = $producto->descripcion
            ? Str::limit($producto->descripcion, 200)
            : 'Máquina de alta calidad para uso comercial.';

        return response()->json([
            'tipo'      => 'opciones',
            'respuesta' => "Excelente elección 👍\n\n{$descripcion}\n\n✔ Ideal para producción continua\n✔ Fácil operación\n✔ Uso comercial\n\nTe puedo enviar el precio actualizado y más detalles técnicos.\n\n👉 ¿Es para uso personal o negocio?",
            'producto'  => [
                'nombre' => $producto->nombre,
                'imagen' => $imagen ? $imagen->url_imagen : null,
            ],
            'opciones'  => [
                ['label' => '🏠 Uso personal', 'valor' => 'personal'],
                ['label' => '🏢 Negocio',       'valor' => 'negocio'],
            ],
            'context'   => [
                'paso'     => 'maq_uso',
                'flujo'    => 'maquinaria',
                'producto' => $producto->nombre,
            ],
        ]);
    }

    private function pasoMaqUso(string $mensaje, array $context): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'tipo'      => 'texto',
            'respuesta' => "Perfecto 👌\n¿En qué ciudad te encuentras? (para cotizar envío también)",
            'context'   => array_merge($context, [
                'paso' => 'maq_ciudad',
                'uso'  => $mensaje,
            ]),
        ]);
    }

    private function pasoMaqCiudad(string $mensaje, array $context): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'tipo'      => 'texto',
            'respuesta' => "Genial 👍 Te envío la información completa con precio y envío.\n👉 ¿Me compartes tu nombre y número de WhatsApp?",
            'context'   => array_merge($context, [
                'paso'   => 'maq_datos_contacto',
                'ciudad' => $mensaje,
            ]),
        ]);
    }

    private function pasoMaqDatosContacto(string $mensaje, array $context): \Illuminate\Http\JsonResponse
    {
        $this->guardarLeadCliente($mensaje, $context);

        $producto = $context['producto'] ?? 'el producto';
        $ciudad   = $context['ciudad']   ?? '';
        $textoWa  = urlencode("Hola, soy {$mensaje}. Estoy interesado en: {$producto}. Ciudad: {$ciudad}.");

        return response()->json([
            'tipo'          => 'fin_flujo',
            'respuesta'     => "¡Gracias! 🙌\n\nPara recibir tu cotización completa, haz clic en el botón verde y un asesor te atenderá por WhatsApp 👇",
            'link_whatsapp' => "https://wa.me/{$this->numeroWhatsapp}?text={$textoWa}",
            'context'       => null,
        ]);
    }


    // FLUJO DECORACIÓN

    private function iniciarFlujoDecoracion(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'tipo'      => 'opciones',
            'respuesta' => "¡Me encanta esa elección! ✨ La decoración realmente cambia todo el ambiente de un negocio 😍\n\nTenemos opciones súper bonitas y modernas 💡\n\nMira, te dejo aquí lo que puedes elegir:",
            'opciones'  => $this->obtenerProductosPorSeccion('Decoración'),
            'context'   => ['paso' => 'deco_lista'],
        ]);
    }

    private function pasoDecoLista(string $mensaje, array $context): \Illuminate\Http\JsonResponse
    {
        $producto = Producto::where('seccion', 'Decoración')
            ->where('nombre', 'LIKE', '%' . $mensaje . '%')
            ->first();

        $detallesFijos = [
            'silla_cubo'        => ['nombre' => 'Silla cuadrada o de cubo',  'desc' => "✔️ Diseño moderno y resistente\n✔️ Ideal para locales comerciales\n✔️ Disponible en varios colores"],
            'mesa_led'          => ['nombre' => 'Mesa LED bar alta',          'desc' => "✔️ Batería recargable (10–12 horas)\n✔️ Cambia de colores\n✔️ Resistente al agua"],
            'silla_led'         => ['nombre' => 'Silla LED bar alta',         'desc' => "✔️ Batería recargable (10–12 horas)\n✔️ Cambia de colores\n✔️ Resistente al agua"],
            'mesa_led_cuadrada' => ['nombre' => 'Mesa LED bar alta cuadrada', 'desc' => "✔️ Batería recargable (10–12 horas)\n✔️ Cambia de colores\n✔️ Resistente al agua"],
        ];

        if (!$producto && !isset($detallesFijos[$mensaje])) {
            return response()->json([
                'tipo'      => 'opciones',
                'respuesta' => 'Por favor elige una de las opciones disponibles 😊',
                'opciones'  => $this->obtenerProductosPorSeccion('Decoración'),
                'context'   => ['paso' => 'deco_lista'],
            ]);
        }

        $nombre      = $producto ? $producto->nombre : ($detallesFijos[$mensaje]['nombre'] ?? $mensaje);
        $descripcion = $producto
            ? Str::limit($producto->descripcion ?? '', 200)
            : ($detallesFijos[$mensaje]['desc'] ?? 'Producto decorativo de alta calidad.');
        $imagen      = $producto ? $producto->imagenes()->first() : null;

        $context['producto'] = $nombre;

        return $this->pasoDecoPrecio($mensaje, $context);
    }

    private function pasoDecoPrecio(string $mensaje, array $context): \Illuminate\Http\JsonResponse
    {
        $producto = $context['producto'] ?? 'este producto';
        $textoWa  = urlencode("Hola, quisiera el precio y más detalles de: {$producto}");

        return response()->json([
            'tipo'          => 'fin_flujo',
            'respuesta'     => "¡Perfecto! 🙌 Haz clic en el botón verde para recibir el precio y todos los detalles de *{$producto}* por WhatsApp 👇",
            'link_whatsapp' => "https://wa.me/{$this->numeroWhatsapp}?text={$textoWa}",
            'context'       => null,
        ]);
    }


    // FLUJO ASESOR

    private function iniciarFlujoAsesor(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'tipo'      => 'opciones',
            'respuesta' => "¡Perfecto! 🙌 Te ayudaré con una atención más personalizada.\n\nPara orientarte mejor, cuéntame 👇\n¿Qué tipo de negocio tienes?",
            'opciones'  => [
                ['label' => '🧋 Bebidas (jugos, bubble tea, café)',   'valor' => 'bebidas'],
                ['label' => '🍔 Comida (snacks, fast food, postres)', 'valor' => 'comida'],
                ['label' => '🛍️ Retail / tienda',                    'valor' => 'retail'],
                ['label' => '🎁 Regalos / personalizados',            'valor' => 'regalos'],
                ['label' => '🏭 Producción / industria',              'valor' => 'industria'],
                ['label' => '🚀 Emprendimiento en inicio',            'valor' => 'emprendimiento'],
                ['label' => '✏️ Otro',                                'valor' => 'otro'],
            ],
            'context'   => ['paso' => 'asesor_tipo_negocio'],
        ]);
    }

    private function pasoAsesorTipoNegocio(string $mensaje): \Illuminate\Http\JsonResponse
    {
        $labels = [
            'bebidas'        => 'Bebidas',
            'comida'         => 'Comida',
            'retail'         => 'Retail / tienda',
            'regalos'        => 'Regalos / personalizados',
            'industria'      => 'Producción / industria',
            'emprendimiento' => 'Emprendimiento',
            'otro'           => 'tu negocio',
        ];

        $tipo = $labels[$mensaje] ?? $mensaje;

        return response()->json([
            'tipo'      => 'texto',
            'respuesta' => "¡Buenísimo! 🔥 El rubro de {$tipo} tiene mucha demanda.\n\nAhora dime, ¿qué tipo de maquinaria o producto te interesa?",
            'context'   => [
                'paso'         => 'asesor_producto',
                'flujo'        => 'asesor',
                'tipo_negocio' => $tipo,
            ],
        ]);
    }

    private function pasoAsesorProducto(string $mensaje, array $context): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'tipo'      => 'texto',
            'respuesta' => "¡Excelente elección! 🔥 Por último, ¿en qué ciudad te encuentras?",
            'context'   => array_merge($context, [
                'paso'     => 'asesor_ciudad',
                'producto' => $mensaje,
            ]),
        ]);
    }

    private function pasoAsesorCiudad(string $mensaje, array $context): \Illuminate\Http\JsonResponse
    {
        $tipo     = $context['tipo_negocio'] ?? '';
        $producto = $context['producto']     ?? '';
        $textoWa  = urlencode("Hola, tengo un negocio de {$tipo} en {$mensaje} y me interesa: {$producto}. ¿Me pueden asesorar?");

        return response()->json([
            'tipo'          => 'fin_flujo',
            'respuesta'     => "¡Gracias por la información! 😊\n\nHaz clic en el botón verde para conectarte con un asesor especializado en {$tipo} 👇",
            'link_whatsapp' => "https://wa.me/{$this->numeroWhatsapp}?text={$textoWa}",
            'context'       => null,
        ]);
    }



    // HELPERS


    private function obtenerProductosPorSeccion(string $seccion, int $limite = 8): array
    {
        $productos = Producto::where('seccion', $seccion)
            ->orderBy('id', 'desc')
            ->take($limite)
            ->get();

        if ($productos->isEmpty()) {
            return [['label' => '👨‍💼 Hablar con un asesor', 'valor' => 'asesor']];
        }

        return $productos->map(fn($p) => [
            'label' => $p->nombre,
            'valor' => $p->nombre,
        ])->toArray();
    }

    private function guardarLeadCliente(string $datos, array $context): void
    {
        try {
            $partes  = explode(',', $datos);
            $nombre  = trim($partes[0] ?? '');
            $celular = trim(preg_replace('/[^0-9]/', '', $partes[1] ?? ''));

            if ($nombre) {
                Cliente::updateOrCreate(
                    ['celular' => $celular ?: null],
                    [
                        'nombre'   => $nombre,
                        'celular'  => $celular ?: null,
                        'ciudad'   => $context['ciudad']   ?? null,
                        'producto' => $context['producto'] ?? null,
                        'fuente'   => 'chatbot',
                    ]
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error al guardar lead desde chatbot: " . $e->getMessage());
        }
    }

    private function respuestaBienvenida(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'tipo'      => 'texto',
            'respuesta' => $this->respuestasSaludo[array_rand($this->respuestasSaludo)],
        ]);
    }

    // CHATBOT LIBRE
    private function contienePalabraClave(string $mensaje, array $palabrasClave): bool
    {
        $mensajeLimpio   = strtolower(trim($mensaje));
        $palabrasMensaje = explode(' ', $mensajeLimpio);

        foreach ($palabrasClave as $palabra) {
            if (str_contains($mensajeLimpio, $palabra)) return true;

            foreach ($palabrasMensaje as $pm) {
                if (abs(strlen($palabra) - strlen($pm)) > 2) continue;
                $distancia = levenshtein($palabra, $pm);
                if (strlen($palabra) <= 4 && $distancia === 0) return true;
                elseif (strlen($palabra) <= 6 && $distancia <= 1) return true;
                elseif (strlen($palabra) > 6 && $distancia <= 2) return true;
            }
        }
        return false;
    }

    private function manejarChatbotLibre(Request $request): \Illuminate\Http\JsonResponse
    {
        $mensajeUsuario = strtolower($request->input('mensaje', ''));
        $numeroWhatsapp = $this->numeroWhatsapp;

        // Puerta de entrada al flujo guiado desde texto libre
        if ($this->contienePalabraClave($mensajeUsuario, ['maquinaria', 'maquina', 'selladora', 'embalaje', 'codificadora']))
            return $this->iniciarFlujoMaquinaria();
        if ($this->contienePalabraClave($mensajeUsuario, ['decoracion', 'decorar', 'silla led', 'mesa led', 'panel']))
            return $this->iniciarFlujoDecoracion();
        if ($this->contienePalabraClave($mensajeUsuario, ['negocio', 'emprendimiento', 'empresa', 'productos']))
            return $this->iniciarFlujoNegocio();
        if ($this->contienePalabraClave($mensajeUsuario, ['asesor', 'humano', 'persona', 'vendedor']))
            return $this->iniciarFlujoAsesor();
        if ($this->contienePalabraClave($mensajeUsuario, ['hola', 'ola', 'buenas', 'buenos dias', 'buen dia', 'holi']))
            return $this->respuestaBienvenida();

        $palabrasIgnoradas    = ['de','con','para','hola','ola','buenas','tienen','stock','del','quiero','busco','algo','mi','negocio','cuanto','cuesta','precio','que','como','cuando','donde','por','favor','necesito','saber','sobre','el','la','los','las','un','una','sin'];
        $palabrasBusqueda     = array_diff(explode(' ', $mensajeUsuario), $palabrasIgnoradas);

        $horarioClave = ['hora', 'horario', 'abren', 'cierran', 'domingo', 'atienden'];
        if ($this->contienePalabraClave($mensajeUsuario, $horarioClave)) {
            return response()->json(['tipo' => 'texto','respuesta' => $this->respuestasHorario[array_rand($this->respuestasHorario)],'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20cu%C3%A1les%20son%20sus%20horarios."]);
        }
        
        $humanoClave = ['humano', 'asesor', 'persona', 'alguien', 'ejecutivo', 'vendedor', 'operador'];
        if ($this->contienePalabraClave($mensajeUsuario, $humanoClave)) {
            return response()->json(['tipo' => 'texto','respuesta' => $this->respuestasHumano[array_rand($this->respuestasHumano)],'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20necesito%20un%20asesor."]);
        }

        // --- 3️⃣ RECOMENDACIONES / DESTACADOS ---
        $recomendacionesClave = ['recomienda', 'recomiendas', 'recomendacion', 'destacados', 'destacado', 'populares', 'mas vendidos', 'mejores'];
        if ($this->contienePalabraClave($mensajeUsuario, $recomendacionesClave)) {
            $productosDestacados = Producto::orderBy('id', 'desc')->take(2)->get();
            if ($productosDestacados->isNotEmpty()) {
                $items = [];
                foreach($productosDestacados as $p) {
                    $imagen = $p->imagenes()->first();
                    $urlImagen = $imagen ? $imagen->url_imagen : null;
                    $textoWa = urlencode("Hola Tami, me interesa consultar sobre este producto destacado: " . $p->nombre);
                    
                    $items[] = [
                        'nombre' => $p->nombre,
                        'descripcion' => Str::limit($p->descripcion, 100), 
                        'imagen' => $urlImagen,
                        'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text={$textoWa}"
                    ];
                }
                return response()->json([
                    'tipo' => 'producto',
                    'respuesta' => '🌟 ¡Excelente elección! Aquí te muestro nuestros productos más destacados del momento:',
                    'productos' => $items,
                    'producto' => $items[0],
                    'link_whatsapp' => $items[0]['link_whatsapp']
                ]);
            }
        }

        // --- 5.5️⃣ CATÁLOGO / QUÉ VENDEN ---
        $catalogoClave = ['catalogo', 'productos', 'venden', 'vendes', 'ofrecen', 'rubros', 'categorias', 'vender', 'si', 'claro', 'muestrame', 'enviame', 'mandame', 'verlos', 'modelos', 'foto', 'fotos', 'video'];
        if ($this->contienePalabraClave($mensajeUsuario, $catalogoClave)) {
            $respuestasCatalogo = [
                "En Tami somos expertos en 🏭 Maquinaria Industrial y ✨ Decoración Comercial. Pide el catálogo completo aquí:",
                "¡Excelente! 🚀 Tenemos un catálogo PDF con todos los modelos instalados de maquinaria y paneles. Escríbenos:",
                "¡Perfecto! Un asesor te enviará los modelos y el catálogo digital por WhatsApp ahora mismo. Entra aquí:",
                "¡Claro que sí! Contamos con un folleto interactivo de nuestras líneas de negocio. Pídelo aquí:",
                "¡Sin problemas! El catálogo oficial de Tami con precios y stock lo manejamos vía WhatsApp en este enlace:"
            ];
            return response()->json([
                'tipo' => 'texto',
                'respuesta' => $respuestasCatalogo[array_rand($respuestasCatalogo)],
                'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20quisiera%20el%20cat%C3%A1logo%20y%20ver%20los%20modelos."
            ]);
        }

        // --- 6️⃣ DESPEDIDAS ---
        $despedidaClave = ['gracias', 'ok', 'vale', 'listo', 'perfecto', 'chau', 'chao', 'adios', 'hasta luego'];
        if ($this->contienePalabraClave($mensajeUsuario, $despedidaClave)) {
            return response()->json(['tipo' => 'texto','respuesta' => $this->respuestasAgradecimiento[array_rand($this->respuestasAgradecimiento)]]);
        }

        // --- 7️⃣ SALUDO SIMPLE (Si no hubo producto ni intención, recién saludamos) ---
        $saludosClave = ['hola', 'ola', 'buenas', 'buenos dias', 'buen dia', 'buenas tardes', 'buenas noches', 'holi'];
        if ($this->contienePalabraClave($mensajeUsuario, $saludosClave)) {
            // Respuestas alineadas al prompt: Directas y Breves.
            $respuestasSaludoSimple = [
                '¡Hola! 👋 ¿Qué producto o maquinaria estás buscando hoy?',
                '¡Hola! Bienvenido a Tami 😊. ¿Buscas artículos para hogar o para tu negocio?',
                '¡Hola, qué tal! Escribe el nombre o categoría del producto que te interese.',
                '¡Saludos! 🚀 ¿En qué te puedo ayudar o qué producto buscas?',
                '¡Buen día! ☀️ Desde Tami te damos la bienvenida. ¿Qué categoría te interesa ver hoy?'
            ];
            return response()->json([
                'tipo' => 'texto',
                'respuesta' => $respuestasSaludoSimple[array_rand($respuestasSaludoSimple)]
            ]);
        }

        // --- 8️⃣ RESPUESTA POR DEFECTO (No entiendo) ---
        return response()->json([
            'tipo' => 'texto',
            'respuesta' => $this->respuestasDefecto[array_rand($this->respuestasDefecto)],
            'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20necesito%20ayuda%20para%20encontrar%20algo."
        ]);
    }
    /**
     * Endpoint POST para actualizar el ícono del asistente.
     */
    public function updateIcon(Request $request)
    {
        // 1. Validamos que realmente suban un archivo, que sea imagen y no pese demasiado
        $request->validate([
            'chatbot_icon' => 'required|image|mimes:jpeg,png,jpg,svg,webp|max:2048',
        ]);

        try {
            if ($request->hasFile('chatbot_icon')) {
                $archivo = $request->file('chatbot_icon');

                // 2. Delegamos toda la carga al nuevo servicio
                $urlPublica = $this->chatbotService->updateIconoChatbot($archivo);

                // 3. Respuesta exitosa usando tus funciones nativas del proyecto
                return $this->successMessage(
                    'Ícono del chatbot actualizado exitosamente',
                    200 // O HttpStatusCode::OK->value si usas la estructura de ProductoController
                );
            }

            return response()->json(['message' => 'No se recibió ningún archivo de imagen.'], 400);

        } catch (\Exception $e) {
            // Reutilizamos tu manejador de excepciones configurado en el trait del proyecto
            return $this->handleException($e, 'actualizar el ícono del chatbot', true);
        }
    }

    public function getIcon()
    {
        $config = \App\Models\ChatbotConfig::first();
    
        return response()->json([
            'url_icono' => $config?->url_icono
                ? asset($config->url_icono)
                : null
        ]);
    }
}
