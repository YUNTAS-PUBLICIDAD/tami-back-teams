<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Cliente;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    private string $numeroWhatsapp = "51978883199";

    public function responder(Request $request)
    {
        $context = $request->input('context', null);

        if ($context && isset($context['paso'])) {
            return $this->manejarFlujoGuiado($request, $context);
        }

        return $this->manejarChatbotLibre($request);
    }

    private function manejarFlujoGuiado(Request $request, array $context): \Illuminate\Http\JsonResponse
    {
        $paso    = $context['paso'];
        $mensaje = trim($request->input('mensaje', ''));

        return match($paso) {
            'menu_principal'        => $this->pasoMenuPrincipal($mensaje),

            // Maquinaria
            'maq_lista'             => $this->pasoMaqLista($mensaje),
            'maq_uso'               => $this->pasoMaqUso($mensaje, $context),
            'maq_ciudad'            => $this->pasoMaqCiudad($mensaje, $context),
            'maq_datos_contacto'    => $this->pasoMaqDatosContacto($mensaje, $context),

            // Decoración
            'deco_lista'            => $this->pasoDecoLista($mensaje),
            'deco_detalle'          => $this->pasoDecoDetalle($mensaje, $context),
            'deco_precio'           => $this->pasoDecoPrecio($mensaje, $context),

            // Negocio — 
            'neg_lista'             => $this->pasoNegLista($mensaje),

            // Asesor
            'asesor_tipo_negocio'   => $this->pasoAsesorTipoNegocio($mensaje),
            'asesor_producto'       => $this->pasoAsesorProducto($mensaje, $context),
            'asesor_ciudad'         => $this->pasoAsesorCiudad($mensaje, $context),

            default                 => $this->respuestaBienvenida(),
        };
    }


    // BIENVENIDA

    private function respuestaBienvenida(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'tipo'      => 'opciones',
            'respuesta' => "¡Hola! 👋 Soy la Asistente Tami, estoy aquí para ayudarte a encontrar la maquinaria o productos ideales para tu negocio.\n\n¿Qué te gustaría hacer?",
            'opciones'  => [
                ['label' => '🚀 Negocio',              'valor' => 'negocio'],
                ['label' => '⚙️ Maquinaria',            'valor' => 'maquinaria'],
                ['label' => '✨ Decoración',             'valor' => 'decoracion'],
                ['label' => '👨‍💼 Hablar con asesor',    'valor' => 'asesor'],
            ],
            'context'   => ['paso' => 'menu_principal'],
        ]);
    }

    private function pasoMenuPrincipal(string $mensaje): \Illuminate\Http\JsonResponse
    {
        return match(strtolower($mensaje)) {
            'maquinaria'  => $this->iniciarFlujoMaquinaria(),
            'decoracion'  => $this->iniciarFlujoDecoracion(),
            'negocio'     => $this->iniciarFlujoNegocio(),
            'asesor'      => $this->iniciarFlujoAsesor(),
            default       => $this->respuestaBienvenida(),
        };
    }

    // FLUJO NEGOCIO


    private function iniciarFlujoNegocio(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'tipo'      => 'opciones',
            'respuesta' => 'Perfecto 👍 Estos son nuestros productos disponibles:',
            'opciones'  => $this->obtenerTodosLosProductos(),
            'context'   => ['paso' => 'neg_lista'],
        ]);
    }

    private function obtenerTodosLosProductos(): array
    {
        
        $productos = Producto::orderBy('id', 'desc')->take(8)->get();

        if ($productos->isEmpty()) {
            return [
                ['label' => '⚙️ Maquinaria industrial',   'valor' => 'maquinaria industrial'],
                ['label' => '🥤 Selladoras',               'valor' => 'selladora'],
                ['label' => '✨ Decoración LED',            'valor' => 'decoracion led'],
            ];
        }

        return $productos->map(fn($p) => [
            'label' => $p->nombre,
            'valor' => $p->nombre,
        ])->toArray();
    }

    private function pasoNegLista(string $mensaje): \Illuminate\Http\JsonResponse
    {
        // Busca el producto seleccionado en la BD
        $producto = Producto::where('nombre', 'LIKE', '%' . $mensaje . '%')->first();

        if (!$producto) {
            return response()->json([
                'tipo'      => 'opciones',
                'respuesta' => 'No encontré ese producto 🤔. Por favor elige una de las opciones:',
                'opciones'  => $this->obtenerTodosLosProductos(),
                'context'   => ['paso' => 'neg_lista'],
            ]);
        }

        $imagen = $producto->imagenes()->first();

        // Construye descripción dinámica con los datos reales del producto
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
            'opciones'  => $this->obtenerMaquinas(),
            'context'   => ['paso' => 'maq_lista'],
        ]);
    }

    private function pasoMaqLista(string $mensaje): \Illuminate\Http\JsonResponse
    {
        $producto = Producto::where('nombre', 'LIKE', '%' . $mensaje . '%')->first();

        if (!$producto) {
            return response()->json([
                'tipo'      => 'opciones',
                'respuesta' => 'No encontré esa máquina 🤔. Por favor elige una de las opciones:',
                'opciones'  => $this->obtenerMaquinas(),
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
            'opciones'  => $this->obtenerDecoracion(),
            'context'   => ['paso' => 'deco_lista'],
        ]);
    }

    private function obtenerDecoracion(): array
    {
        $productos = Producto::where('seccion', 'LIKE', '%deco%')
            ->orWhere('seccion', 'LIKE', '%led%')
            ->orWhere('nombre', 'LIKE', '%silla%')
            ->orWhere('nombre', 'LIKE', '%mesa%')
            ->orWhere('nombre', 'LIKE', '%led%')
            ->take(6)
            ->get();

        if ($productos->isEmpty()) {
            return [
                ['label' => '🪑 Sillas cuadradas o de cubo',   'valor' => 'silla_cubo'],
                ['label' => '💡 Mesa LED bar alta',             'valor' => 'mesa_led'],
                ['label' => '🪑 Silla LED bar alta',            'valor' => 'silla_led'],
                ['label' => '💡 Mesa LED bar alta cuadrada',    'valor' => 'mesa_led_cuadrada'],
            ];
        }

        return $productos->map(fn($p) => [
            'label' => $p->nombre,
            'valor' => $p->nombre,
        ])->toArray();
    }

    private function pasoDecoLista(string $mensaje): \Illuminate\Http\JsonResponse
    {
        // Primero intenta encontrar en BD
        $producto = Producto::where('nombre', 'LIKE', '%' . $mensaje . '%')->first();

        // Fallback para opciones fijas
        $detallesFijos = [
            'silla_cubo'        => ['nombre' => 'Silla cuadrada o de cubo',  'desc' => "✔️ Diseño moderno y resistente\n✔️ Ideal para locales comerciales\n✔️ Disponible en varios colores"],
            'mesa_led'          => ['nombre' => 'Mesa LED bar alta',          'desc' => "✔️ Batería recargable (10–12 horas)\n✔️ Cambia de colores\n✔️ Resistente al agua"],
            'silla_led'         => ['nombre' => 'Silla LED bar alta',         'desc' => "✔️ Batería recargable (10–12 horas)\n✔️ Cambia de colores\n✔️ Resistente al agua"],
            'mesa_led_cuadrada' => ['nombre' => 'Mesa LED bar alta cuadrada', 'desc' => "✔️ Batería recargable (10–12 horas)\n✔️ Cambia de colores\n✔️ Resistente al agua"],
        ];

        $nombre      = $producto ? $producto->nombre : ($detallesFijos[$mensaje]['nombre'] ?? $mensaje);
        $descripcion = $producto
            ? Str::limit($producto->descripcion ?? '', 200)
            : ($detallesFijos[$mensaje]['desc'] ?? 'Producto decorativo de alta calidad.');
        $imagen      = $producto ? $producto->imagenes()->first() : null;

        if (!$producto && !isset($detallesFijos[$mensaje])) {
            return response()->json([
                'tipo'      => 'opciones',
                'respuesta' => 'Por favor elige una de las opciones disponibles 😊',
                'opciones'  => $this->obtenerDecoracion(),
                'context'   => ['paso' => 'deco_lista'],
            ]);
        }

        return response()->json([
            'tipo'      => 'opciones',
            'respuesta' => "¡Buena elección! 😍 *{$nombre}* le da un toque increíble a tu espacio ✨\n\n{$descripcion}\n\nCon gusto te paso el precio y más detalles 😉",
            'producto'  => [
                'nombre' => $nombre,
                'imagen' => $imagen ? $imagen->url_imagen : null,
            ],
            'opciones'  => [
                ['label' => '💰 Quiero precio y más detalles', 'valor' => 'quiero_precio'],
                ['label' => '🔙 Ver otros productos',          'valor' => 'volver'],
            ],
            'context'   => [
                'paso'     => 'deco_detalle',
                'flujo'    => 'decoracion',
                'producto' => $nombre,
            ],
        ]);
    }

    private function pasoDecoDetalle(string $mensaje, array $context): \Illuminate\Http\JsonResponse
    {
        if ($mensaje === 'volver') {
            return $this->iniciarFlujoDecoracion();
        }
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
                ['label' => '🧋 Bebidas (jugos, bubble tea, café)',  'valor' => 'bebidas'],
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


    private function obtenerMaquinas(): array
    {
        $productos = Producto::where('seccion', 'LIKE', '%maquin%')
            ->orWhere('nombre', 'LIKE', '%selladora%')
            ->orWhere('nombre', 'LIKE', '%embalaje%')
            ->orWhere('nombre', 'LIKE', '%codificadora%')
            ->take(6)
            ->get();

        if ($productos->isEmpty()) {
            return [
                ['label' => '📦 Máquina de embalaje de té',        'valor' => 'embalaje de té'],
                ['label' => '🥤 Selladora de vasos manual',         'valor' => 'selladora de vasos'],
                ['label' => '💧 Selladora de bolsas para líquidos', 'valor' => 'selladora de bolsas'],
            ];
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
            // Loguear el error para revisión, pero no interrumpir el flujo del chatbot
            \Illuminate\Support\Facades\Log::error("Error al guardar lead desde chatbot: " . $e->getMessage());
        }
    }

    // CHATBOT LIBRE


    private function contienePalabraClave($mensaje, $palabrasClave): bool
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

        // Búsqueda de producto en BD
        $palabrasIgnoradas    = ['de','con','para','hola','ola','buenas','tienen','stock','del','quiero','busco','algo','mi','negocio','cuanto','cuesta','precio','que','como','cuando','donde','por','favor','necesito','saber','sobre','el','la','los','las','un','una','sin'];
        $palabrasBusqueda     = array_diff(explode(' ', $mensajeUsuario), $palabrasIgnoradas);
        $producto             = null;
        $productosEncontrados = collect();

        if (!empty($palabrasBusqueda)) {
            $query        = Producto::query();
            $huboTerminos = false;

            foreach ($palabrasBusqueda as $palabra) {
                $palabra = trim($palabra);
                if (strlen($palabra) >= 3) {
                    $huboTerminos = true;
                    $sinAcentos   = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $palabra);
                    $query->orWhere('nombre', 'LIKE', "%{$palabra}%")
                          ->orWhere('seccion', 'LIKE', "%{$palabra}%")
                          ->orWhere('nombre', 'LIKE', "%{$sinAcentos}%");
                    if (str_ends_with($palabra, 's')) {
                        $singular = substr($palabra, 0, -1);
                        if (strlen($singular) >= 3) $query->orWhere('nombre', 'LIKE', "%{$singular}%");
                    }
                }
            }

            if ($huboTerminos) {
                $productosEncontrados = $query->orderByRaw(
                    "CASE WHEN nombre LIKE ? THEN 1 WHEN nombre LIKE ? THEN 2 ELSE 3 END",
                    ["{$mensajeUsuario}%", "%{$mensajeUsuario}%"]
                )->take(2)->get();

                if ($productosEncontrados->isNotEmpty()) $producto = $productosEncontrados->first();
            }
        }

        if ($producto) {
            $items = $productosEncontrados->map(function ($p) use ($numeroWhatsapp) {
                $imagen  = $p->imagenes()->first();
                $textoWa = urlencode("Hola, me interesa consultar disponibilidad de: {$p->nombre}");
                return [
                    'nombre'        => $p->nombre,
                    'descripcion'   => Str::limit($p->descripcion, 100),
                    'imagen'        => $imagen ? $imagen->url_imagen : null,
                    'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text={$textoWa}",
                ];
            })->toArray();

            $frases = ['✅ Encontré estos artículos en nuestro catálogo:', '¡Claro! Contamos con estas opciones:'];
            return response()->json([
                'tipo'          => 'producto',
                'respuesta'     => $frases[array_rand($frases)],
                'productos'     => $items,
                'producto'      => $items[0],
                'link_whatsapp' => $items[0]['link_whatsapp'],
            ]);
        }

        // Intenciones específicas
        $preciosClave   = ['precio','presio','costo','cuanto','cuesta','valor','cotizar'];
        $ubicacionClave = ['ubicacion','direccion','tienda','local','envio','delivery'];
        $pagoClave      = ['pago','pagar','yape','plin','tarjeta','transferencia'];
        $garantiaClave  = ['garantia','cambio','devolucion','soporte','falla','reclamo'];
        $horarioClave   = ['hora','horario','abren','cierran','atienden'];
        $catalogoClave  = ['catalogo','venden','ofrecen','categorias','modelos','foto'];
        $despedidaClave = ['gracias','ok','vale','listo','perfecto','chau','adios'];

        $respuestasPrecio    = ['💳 Los precios varían según modelo y stock. Para una cotización exacta, escríbenos:', '💰 Un asesor te dará la cotización personalizada por WhatsApp:'];
        $respuestasUbicacion = ['📍 Realizamos envíos a todo el Perú. Coordina tu envío aquí:', '🚚 Llegamos a nivel nacional. Escríbenos:'];
        $respuestasPago      = ['💳 Aceptamos Yape, Plin, transferencias y tarjetas:', '🏧 Todas las opciones de pago disponibles. Escríbenos:'];
        $respuestasGarantia  = ['🛡️ Todos nuestros productos tienen garantía. Para reclamos:', '¡Tu compra está segura! Para soporte, escríbenos:'];
        $respuestasHorario   = ['🕒 Atendemos Lunes a Sábado en horario comercial. ¡Escríbenos!', 'Puedes dejarnos tu mensaje en cualquier momento:'];
        $respuestasAgradec   = ['¡De nada! Ha sido un placer. 🌟', '¡A ti por preferirnos! Que tengas un súper día. ✨'];

        if ($this->contienePalabraClave($mensajeUsuario, $preciosClave))
            return response()->json(['tipo'=>'texto','respuesta'=>$respuestasPrecio[array_rand($respuestasPrecio)],'link_whatsapp'=>"https://wa.me/{$numeroWhatsapp}?text=Hola,%20quisiera%20consultar%20el%20precio."]);
        if ($this->contienePalabraClave($mensajeUsuario, $ubicacionClave))
            return response()->json(['tipo'=>'texto','respuesta'=>$respuestasUbicacion[array_rand($respuestasUbicacion)],'link_whatsapp'=>"https://wa.me/{$numeroWhatsapp}?text=Hola,%20quiero%20info%20de%20envios."]);
        if ($this->contienePalabraClave($mensajeUsuario, $pagoClave))
            return response()->json(['tipo'=>'texto','respuesta'=>$respuestasPago[array_rand($respuestasPago)],'link_whatsapp'=>"https://wa.me/{$numeroWhatsapp}?text=Hola,%20sobre%20metodos%20de%20pago."]);
        if ($this->contienePalabraClave($mensajeUsuario, $garantiaClave))
            return response()->json(['tipo'=>'texto','respuesta'=>$respuestasGarantia[array_rand($respuestasGarantia)],'link_whatsapp'=>"https://wa.me/{$numeroWhatsapp}?text=Hola,%20necesito%20soporte."]);
        if ($this->contienePalabraClave($mensajeUsuario, $horarioClave))
            return response()->json(['tipo'=>'texto','respuesta'=>$respuestasHorario[array_rand($respuestasHorario)],'link_whatsapp'=>"https://wa.me/{$numeroWhatsapp}?text=Hola,%20cuales%20son%20sus%20horarios."]);
        if ($this->contienePalabraClave($mensajeUsuario, $catalogoClave))
            return response()->json(['tipo'=>'texto','respuesta'=>'¡Un asesor te enviará el catálogo completo!','link_whatsapp'=>"https://wa.me/{$numeroWhatsapp}?text=Hola,%20quisiera%20el%20catalogo."]);
        if ($this->contienePalabraClave($mensajeUsuario, $despedidaClave))
            return response()->json(['tipo'=>'texto','respuesta'=>$respuestasAgradec[array_rand($respuestasAgradec)]]);

        // Default
        $defecto = ['No entendí bien 😅. ¿Me indicas el producto o categoría? O escríbenos directamente:', 'Puedo ayudarte mejor si me dices qué producto buscas. O contáctanos:'];
        return response()->json([
            'tipo'          => 'texto',
            'respuesta'     => $defecto[array_rand($defecto)],
            'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20necesito%20ayuda.",
        ]);
    }
}