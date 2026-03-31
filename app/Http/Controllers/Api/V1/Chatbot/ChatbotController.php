<?php

namespace App\Http\Controllers\Api\V1\Chatbot;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    /**
     * Función ayudante para tolerar ciertos errores (typos).
     * Devuelve true si encuentra alguna de las palabras clave en el mensaje.
     */
    private function contienePalabraClave($mensaje, $palabrasClave)
    {
        $mensajeLimpio = strtolower(trim($mensaje));
        $palabrasMensaje = explode(' ', $mensajeLimpio);
        
        foreach ($palabrasClave as $palabra) {
            // Chequeo exacto y subcadena
            if (str_contains($mensajeLimpio, $palabra)) {
                return true;
            }
            
            // Distancia de Levenshtein ajustada a longitud para evitar falsos positivos
            foreach ($palabrasMensaje as $pm) {
                // Descartamos palabras muy diferentes en tamaño rápidamente
                if (abs(strlen($palabra) - strlen($pm)) > 2) continue;

                $distancia = levenshtein($palabra, $pm);

                if (strlen($palabra) <= 4) {
                    if ($distancia === 0) return true;
                } elseif (strlen($palabra) <= 6) {
                    if ($distancia <= 1) return true; // Permite 1 error (ej: presio vs precio)
                } else {
                    if ($distancia <= 2) return true; // Permite 2 errores (ej: uvicacion vs ubicacion)
                }
            }
        }
        return false;
    }

    public function responder(Request $request)
    {
        // 1. Obtenemos el mensaje del usuario y lo pasamos a minúsculas
        $mensajeUsuario = strtolower($request->input('mensaje', ''));
        
        // El número de WhatsApp al que redirigiremos
        $numeroWhatsapp = "51978883199"; 

        // === LISTA DE RESPUESTAS DINÁMICAS (Para que no parezca bot) ===
        $respuestasSaludo = [
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

        $respuestasPrecio = [
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

        $respuestasUbicacion = [
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

        $respuestasHumano = [
            '👨‍💻 ¡Por supuesto! Uno de nuestros expertos humanos está listo para atenderte en WhatsApp ahora mismo. Haz clic aquí:',
            '¡Entendido! Te voy a transferir con uno de nuestros asesores de atención al cliente para que te ayude personalmente:',
            'Claro que sí, un humano te atenderá encantado. Solo toca el botón de aquí abajo para ir a WhatsApp:',
            'A veces hablar con alguien es mejor. 🧑‍💼 Nuestro equipo está en línea y esperando tu mensaje:',
            '¡Ya mismo! Te pongo en contacto directo con uno de nuestros ejecutivos de ventas por WhatsApp:',
            '¡Con gusto! Estoy trasladando este caso. Solo necesitas dar un clic abajo para continuar tu llamada o chat de asistencia con alguien de Tami.',
            'Entiendo perfecto, hay consultas que ameritan intervención de una persona. Entra a nuestra línea directa para conectarte al instante 🚀',
            'Derivando con especialista... ⚙️ Entra al siguiente número de atención al cliente, un humano estará tomando asiento esperándote:',
            'Tienes razón, conversemos frente a frente en este canal de WhatsApp. 👇 Allí uno de los chicos despeja todos tus cuestionamientos.',
            '¡Genial! Alguien capacitado podrá resolver esta observación mejor que un bot 🤖. Anda para allá 👇 y te brindaran los detalles.'
        ];

        $respuestasDefecto = [
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

        // === NUEVOS ESCENARIOS ===
        $respuestasPago = [
            '💳 Aceptamos Yape, Plin, transferencias bancarias y todas las tarjetas. Para coordinar tu pago y confirmar tu pedido, haz clic aquí:',
            '🏧 Tenemos pasarela de pago para tarjetas y también opciones de depósito (Yape/Plin/Transferencia). Escríbenos para enviarte los números de cuenta:',
            '¡Facilitamos tu compra! Puedes pagar con las principales tarjetas o apps del Banco. Un asesor te guiará con el pago por WhatsApp:',
            'Para brindarte los números de cuenta oficiales o el link de pago seguro de Tami, por favor contacta a ventas:',
            'Recibimos pagos de todos los bancos. 💵 Por temas de seguridad, los números de Yape y cuentas solo te los entregará nuestro asesor si le escribes:',
            'No te preocupes por cómo pagar, ¡tenemos todas las alternativas en el Checkout y por depósitos directos! Consulta tus dudas dándole click acá abajo.',
            'Trabajamos con casi cada billetera móvil y banco local nacional para pago. Los asesores pueden certificar la compra de frente y brindarte las cuentas de Tami 💰.',
            'Para confirmar el uso de abonos contra entrega u otra modalidad inusual, pregúntale ahora mismo a ventas (haz clic).',
            'Nos aseguramos en que las trasferencias y pagos vía Niubiz o Mercado Pago sean las más sólidas. Para pedir su voucher dirígete acá 👇⬇️',
            'Todos los pagos efectuados con la billetera digital o por ventanilla aplican rápido; por tanto la comunicación con Ventas debe hacerse por WA ahora 🕒.'
        ];

        $respuestasGarantia = [
            '🛡️ Todos nuestros productos originales cuentan con garantía contra defectos de fábrica. Para tramitar algún inconveniente o reclamo, habla directo con nosotros:',
            '¡Tu compra está segura! Si has tenido algún problema con un pedido o tienes dudas sobre la política de devoluciones, nuestro equipo te ayudará en WhatsApp:',
            'Para reportar una falla, ejercer garantía o recibir soporte post-venta personalizado, nuestra área encargada te atiende aquí:',
            'Nos tomamos muy en serio la calidad de Tami. Si requieres ayuda técnica o devolver un producto, haz clic abajo para conversar con soporte:',
            '¿Dudas sobre garantías? Tranquilo/a. Escribe a nuestro canal oficial adjuntando tu número de pedido o boleta/factura:',
            'Si requieres reclamar por una descompostura interna de fábrica o daños logísticos, un asesor se encarga de orientar ese caso de forma prioritaria.',
            '¡En Tami la Post Venta es primordial! Si cuentas con algún percance por favor accede sin demoras al panel de atención por chat.',
            'Ofrecemos soporte integral sobre compras realizadas y cubrimos imperfectos si proceden según evaluación de técnicos 👨‍🔧. Inicia aquí.',
            '¿Vino con detalles extraños o es una desconfiguración? Para tramitar devolución 💡 envía fotografías para iniciar el proceso de inmediato.',
            'Lamento muchísimo oír lo de las averías eventuales en tu adquisición. Ayudanos con evidencia dirigiéndote al grupo asignado cliqueando abajo:'
        ];

        $respuestasHorario = [
            '🕒 Nuestro horario de atención por WhatsApp y redes es de Lunes a Sábado, en horario comercial. ¡Pero puedes dejarnos tu mensaje ahora mismo!',
            'Trabajamos arduamente durante la semana. Si nos escribes ahora al WhatsApp, te responderemos en el menor tiempo posible ⏳:',
            'Aunque estemos fuera de horario, nuestro buzón de WhatsApp siempre recibe solicitudes y respondemos apenas estemos en línea. ¡Escríbenos!',
            'Para conocer los horarios exactos de tienda física o si deseas que te contactemos en la mañana, déjanos tu interés por aquí:',
            '¡Claro! Atendemos de corrido en días de semana. Puedes confirmar si estamos abiertos y activos justo ahora mandándonos un mensaje:',
            'Por lo normal, nuestro equipo descansa los domingos, pero los mensajes pueden gestionarse el lunes temprano. 📲 Déjelo cargando ya. ',
            'Normalmente laboramos turnos diurnos. Si tienes alguna prisa para una recolección escribe pidiendo las agendas reales vigentes 😉',
            'Estamos enfocados en brindar respuestas durante el horario hábil; ¡siempre un representante lo revisará! Intenta el siguiente contacto interno 🧑‍💻',
            'Las líneas de WhatsApp Oficial de Empresa usualmente están de sol a sol ☀️ pero puedes dejarnos tu texto e inquietud sin compromisos de horario en el enlace.',
            'Nuestros relojes no paran para el negocio! Pese a esto el horario presencial final lo dará ventas si los requieres clicando el link 🕓.'
        ];

        $respuestasAgradecimiento = [
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

        $respuestasMaquinaria = [
            "🔥 En Maquinaria industrial somos líderes. Contamos con:\n• Selladoras de bolsas\n• Compresoras de aire\n• Cortadoras de materiales\n• Codificadoras\n¿Ventas te puede cotizar alguna mañana?",
            "Nuestras máquinas industriales son el motor de muchos emprendimientos. Tenemos selladoras, compresoras y más. ¿Buscas algún modelo?",
            "Para producción industrial contamos con un catálogo completo de maquinaria certificada. 🏗️ ¿Deseas ver selladoras o compresoras?",
            "Si buscas potencia para tu fábrica, nuestras compresoras y selladoras industriales son ideales. Consulta stock aquí:",
            "Mejora tu línea de producción con maquinaria Tami. Ofrecemos equipos industriales de alto rendimiento. ¡Habla con un asesor!"
        ];

        $respuestasNegocio = [
            "🚀 ¡Impulsamos tu emprendimiento! En Tami te ayudamos con:\n• Maquinaria Industrial (producción)\n• Decoración Comercial (imagen de local)\n¿En qué área necesitas apoyo hoy?",
            "Para un negocio exitoso necesitas buena producción y una imagen impecable. 🏪 ¿Buscas maquinaria o decoración para tu local?",
            "En Tami somos aliados de los emprendedores. Te ofrecemos desde equipos industriales hasta paneles decorativos. ¿Cuál te interesa?",
            "Si estás iniciando una empresa, tenemos todo en maquinaria y acabados decorativos. 📈 ¡Escríbenos para enviarte el catálogo!",
            "Nuestro enfoque comercial es ayudarte a crecer. Contamos con soluciones industriales y de decoración empresarial. ¿Qué buscas?"
        ];

        $respuestasHogar = [
            "✨ Para decoración de locales y negocios contamos con:\n• Paneles decorativos (WPC, PVC)\n• Iluminación LED\n• Adornos modernos\n¿Deseas ver modelos para tu negocio?",
            "Nuestra línea de decoración comercial resalta la imagen de cualquier local. 🏪 Tenemos paneles y acabados increíbles. Escríbenos:",
            "¡Dale vida a tus espacios! Contamos con iluminación y recubrimientos decorativos de larga duración. ✨ Consulta aquí:",
            "Si buscas paneles decorativos o iluminación moderna para tu negocio, estás en el lugar correcto. Mira las opciones arriba 👆",
            "Transforma la estética de tu local con la decoración de Tami. Pide el folleto de paneles y adornos a un asesor:"
        ];

        // --- 1️⃣ BÚSQUEDA DE PRODUCTO O STOCK (Prioridad Máxima) ---
        // Extraemos palabras clave y omitimos conectores
        $palabrasIgnoradas = ['de', 'con', 'para', 'hola', 'ola', 'buenas', 'tienen', 'stock', 'del', 'quiero', 'busco', 'algo', 'mi', 'negocio', 'cuanto', 'cuesta', 'precio', 'que', 'como', 'cuando', 'donde', 'por', 'favor', 'necesito', 'saber', 'sobre', 'el', 'la', 'los', 'las', 'un', 'una', 'sin'];
        $palabrasOriginales = explode(' ', $mensajeUsuario);
        $palabrasBusqueda = array_diff($palabrasOriginales, $palabrasIgnoradas);

        $producto = null;
        if (!empty($palabrasBusqueda)) {
            $query = Producto::query();
            $huboTerminos = false;

            foreach($palabrasBusqueda as $palabra) {
                $palabra = trim($palabra);
                if (strlen($palabra) >= 3) {
                    $huboTerminos = true;

                    // Normalización básica de acentos para la consulta (MySQL suele ignorar acentos en LIKE con collation por defecto, pero por seguridad)
                    $sinAcentos = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $palabra);

                    $query->orWhere('nombre', 'LIKE', '%' . $palabra . '%')
                          ->orWhere('seccion', 'LIKE', '%' . $palabra . '%')
                          ->orWhere('nombre', 'LIKE', '%' . $sinAcentos . '%');
                    
                    // Manejo de plurales y fragmentos (ej: selladoras -> selladora)
                    if (str_ends_with($palabra, 's')) {
                        $singular = substr($palabra, 0, -1);
                        if (strlen($singular) >= 3) {
                            $query->orWhere('nombre', 'LIKE', '%' . $singular . '%');
                        }
                    }
                }
            }

            if ($huboTerminos) {
                // Ordenar por relevancia (si el nombre contiene la palabra al inicio, es mejor) e intentar traer hasta 2 productos para no saturar
                $productosEncontrados = $query->orderByRaw("CASE 
                                    WHEN nombre LIKE ? THEN 1 
                                    WHEN nombre LIKE ? THEN 2 
                                    ELSE 3 END", [$mensajeUsuario.'%', '%'.$mensajeUsuario.'%'])
                                  ->take(2)
                                  ->get();
                
                if ($productosEncontrados->isNotEmpty()) {
                    $producto = $productosEncontrados->first(); // Para compatibilidad si el front aún usa 'producto'
                }
            }
        }

        // SI NO ENCONTRAMOS PRODUCTO EXACTO, PERO MENCIONÓ MAQUINARIA/DECORACIÓN
        // Intentamos traer un producto "ejemplo" de esa categoría para mostrar imagen
        if (!$producto) {
            $esMaquinaria = $this->contienePalabraClave($mensajeUsuario, ['maquina', 'industrial', 'compresora', 'selladora', 'codificadora', 'cortadora']);
            $esDecoracion = $this->contienePalabraClave($mensajeUsuario, ['decorar', 'decoracion', 'paneles', 'iluminacion', 'adorno', 'luces', 'luces led', 'mesas led', 'mesa led']);

            if ($esMaquinaria) {
                $productosEncontrados = Producto::where('seccion', 'LIKE', '%maquina%')
                                    ->orWhere('nombre', 'LIKE', '%selladora%')
                                    ->orWhere('nombre', 'LIKE', '%compresora%')
                                    ->take(2)
                                    ->get();
            } elseif ($esDecoracion) {
                $productosEncontrados = Producto::where('seccion', 'LIKE', '%decoracion%')
                                    ->orWhere('seccion', 'LIKE', '%panel%')
                                    ->orWhere('nombre', 'LIKE', '%panel%')
                                    ->orWhere('nombre', 'LIKE', '%luces%')
                                    ->orWhere('nombre', 'LIKE', '%mesa%')
                                    ->take(2)
                                    ->get();
            }

            if (isset($productosEncontrados) && $productosEncontrados->isNotEmpty()) {
                $producto = $productosEncontrados->first();
            }
        }

        // SI ENCONTRAMOS UN PRODUCTO (O UN EJEMPLO DE LA CATEGORÍA), RESPONDEMOS CON TARJETA VISUAL
        if ($producto) {
            $items = [];
            foreach($productosEncontrados as $p) {
                $imagen = $p->imagenes()->first();
                $urlImagen = $imagen ? $imagen->url_imagen : null;
                $textoWa = urlencode("Hola Tami, me interesa consultar disponibilidad de: " . $p->nombre);
                
                $items[] = [
                    'nombre' => $p->nombre,
                    'descripcion' => Str::limit($p->descripcion, 100), 
                    'imagen' => $urlImagen,
                    'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text={$textoWa}"
                ];
            }
            
            $respuestasEncontroProducto = [
                '✅ Sí tenemos disponibilidad de artículos en esa categoría. Mira estos modelos:',
                '¡Bingo! 🎉 Encontré estos artículos en nuestro catálogo que coinciden con tu interés:',
                '¡Claro! Contamos con stock de opciones similares. Dale un vistazo:',
                'Aquí tienes lo que solicitaste. Te dejo la información a continuación, revísalo aquí:',
                '¡Buenas noticias! Te conseguí información rápida. Revisa sus características 👍:'
            ];

            return response()->json([
                'tipo' => 'producto',
                'respuesta' => $respuestasEncontroProducto[array_rand($respuestasEncontroProducto)],
                'productos' => $items, // Nueva clave plural
                'producto' => $items[0], // Mantener singular para compatibilidad vieja
                'link_whatsapp' => $items[0]['link_whatsapp']
            ]);
        }

        // --- 2️⃣ INTENCIONES ESPECÍFICAS DE CATEGORÍA ---

        // A. DECORACIÓN (Prioridad por ser actividad específica)
        $hogarClave = ['decorar', 'decoracion', 'hogar', 'casa', 'adorno', 'paneles', 'iluminacion', 'interior'];
        if ($this->contienePalabraClave($mensajeUsuario, $hogarClave)) {
            return response()->json([
                'tipo' => 'texto',
                'respuesta' => $respuestasHogar[array_rand($respuestasHogar)],
                'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20busco%20opciones%20de%20decoraci%C3%B3n%20para%20mi%20negocio."
            ]);
        }

        // B. MAQUINARIA (Equipos industriales)
        $maquinariaClave = ['maquinaria', 'maquina', 'industrial', 'compresora', 'selladora', 'codificadora', 'cortadora', 'tira'];
        if ($this->contienePalabraClave($mensajeUsuario, $maquinariaClave)) {
            return response()->json([
                'tipo' => 'texto',
                'respuesta' => $respuestasMaquinaria[array_rand($respuestasMaquinaria)],
                'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20busco%20maquinaria%20industrial%20para%20mi%20negocio."
            ]);
        }

        // C. NEGOCIO / EMPRENDIMIENTO (General - Si no dijo decorar o maquina)
        $negocioClave = ['negocio', 'emprendimiento', 'empresa', 'taller', 'fabrica', 'produccion', 'negosio'];
        if ($this->contienePalabraClave($mensajeUsuario, $negocioClave)) {
            return response()->json([
                'tipo' => 'texto',
                'respuesta' => $respuestasNegocio[array_rand($respuestasNegocio)],
                'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20quisiera%20asesor%C3%ADa%20para%20mi%20emprendimiento."
            ]);
        }

        // --- 4️⃣ PRECIO / COTIZACIONES ---
        $preciosClave = ['precio', 'presio', 'prexi', 'costo', 'kosto', 'cuanto', 'kuanto', 'cuesta', 'valor', 'cotizar', 'cotizacion'];
        if ($this->contienePalabraClave($mensajeUsuario, $preciosClave)) {
            return response()->json([
                'tipo' => 'texto',
                'respuesta' => $respuestasPrecio[array_rand($respuestasPrecio)],
                'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20quisiera%20consultar%20el%20precio%20de%20unos%20art%C3%ADculos."
            ]);
        }

        // --- 5️⃣ INFORMACIÓN GENERAL (UBICACIÓN, PAGOS, GARANTÍA, HORARIO) ---
        $ubicacionClave = ['ubicacion', 'uvicacion', 'direccion', 'tienda', 'local', 'donde estan', 'envio', 'delivery'];
        if ($this->contienePalabraClave($mensajeUsuario, $ubicacionClave)) {
            return response()->json(['tipo' => 'texto','respuesta' => $respuestasUbicacion[array_rand($respuestasUbicacion)],'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20quiero%20info%20de%20env%C3%ADos."]);
        }
        
        $pagoClave = ['pago', 'pagar', 'yape', 'plin', 'tarjeta', 'transferencia', 'cuenta', 'contraentrega', 'efectivo'];
        if ($this->contienePalabraClave($mensajeUsuario, $pagoClave)) {
            return response()->json(['tipo' => 'texto','respuesta' => $respuestasPago[array_rand($respuestasPago)],'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20sobre%20m%C3%A9todos%20de%20pago."]);
        }

        $garantiaClave = ['garantia', 'cambio', 'devolucion', 'soporte', 'falla', 'reclamo', 'defectuoso'];
        if ($this->contienePalabraClave($mensajeUsuario, $garantiaClave)) {
            return response()->json(['tipo' => 'texto','respuesta' => $respuestasGarantia[array_rand($respuestasGarantia)],'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20necesito%20soporte%20logistico."]);
        }

        $horarioClave = ['hora', 'horario', 'abren', 'cierran', 'domingo', 'atienden'];
        if ($this->contienePalabraClave($mensajeUsuario, $horarioClave)) {
            return response()->json(['tipo' => 'texto','respuesta' => $respuestasHorario[array_rand($respuestasHorario)],'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20cu%C3%A1les%20son%20sus%20horarios."]);
        }
        
        $humanoClave = ['humano', 'asesor', 'persona', 'alguien', 'ejecutivo', 'vendedor', 'operador'];
        if ($this->contienePalabraClave($mensajeUsuario, $humanoClave)) {
            return response()->json(['tipo' => 'texto','respuesta' => $respuestasHumano[array_rand($respuestasHumano)],'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20necesito%20un%20asesor."]);
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
            return response()->json(['tipo' => 'texto','respuesta' => $respuestasAgradecimiento[array_rand($respuestasAgradecimiento)]]);
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
        $respuestasDefectoNuevas = [
            'Aún estoy aprendiendo 🤖. ¿Podrías indicarme el producto o categoría que buscas? O habla con un asesor aquí:',
            'No reconocí el término 😅. Te invito a conversar directamente con un humano en nuestro WhatsApp:',
            'Mi base de datos solo detecta categorías específicas como "panel" o "impresora". Conversemos en WhatsApp:',
            '¿Buscas algo muy específico? 🤔 Mejor te redirijo de inmediato con nuestro equipo de soporte humano:',
            'Para no darte información errónea sobre temas puntuales, es mejor que hables con ventas presionando aquí:'
        ];
        return response()->json([
            'tipo' => 'texto',
            'respuesta' => $respuestasDefectoNuevas[array_rand($respuestasDefectoNuevas)],
            'link_whatsapp' => "https://wa.me/{$numeroWhatsapp}?text=Hola,%20necesito%20ayuda%20para%20encontrar%20algo."
        ]);
    }
}
