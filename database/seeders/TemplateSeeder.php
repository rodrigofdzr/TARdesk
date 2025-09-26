<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first(); // Get the first user to assign as creator

        $templates = [
            [
                'name' => 'Bienvenida - Nuevo Ticket',
                'subject' => 'Confirmación de Ticket #{{ticket_number}} - {{ticket_subject}}',
                'content' => '<p>Estimado/a {{customer_name}},</p>
                <p>Hemos recibido su solicitud y le hemos asignado el número de ticket <strong>{{ticket_number}}</strong>.</p>
                <p><strong>Asunto:</strong> {{ticket_subject}}</p>
                <p>Nuestro equipo de soporte está trabajando en su consulta y le responderemos lo antes posible. El tiempo estimado de respuesta es de 24 horas en días laborables.</p>
                <p>Si tiene alguna pregunta adicional, no dude en responder a este email mencionando el número de ticket.</p>
                <p>Saludos cordiales,<br>{{agent_name}}<br>Equipo de Soporte</p>',
                'type' => 'ticket_creation',
                'category' => 'Soporte Técnico',
                'variables' => ['customer_name', 'ticket_number', 'ticket_subject', 'agent_name'],
                'is_active' => true,
                'is_default' => true,
                'created_by' => $user?->id,
                'description' => 'Plantilla automática enviada cuando se crea un nuevo ticket'
            ],
            [
                'name' => 'Solicitud de Información Adicional',
                'subject' => 'Re: Ticket #{{ticket_number}} - Información Adicional Requerida',
                'content' => '<p>Hola {{customer_name}},</p>
                <p>Gracias por contactarnos. Para poder ayudarle de la mejor manera con el ticket <strong>{{ticket_number}}</strong>, necesitamos información adicional:</p>
                <ul>
                    <li>Descripción detallada del problema</li>
                    <li>Pasos que realizó antes de que ocurriera el issue</li>
                    <li>Capturas de pantalla si es posible</li>
                    <li>Sistema operativo y navegador que está utilizando</li>
                </ul>
                <p>Una vez que recibamos esta información, podremos proceder con la resolución de su consulta.</p>
                <p>Gracias por su paciencia.</p>
                <p>Atentamente,<br>{{agent_name}}</p>',
                'type' => 'response',
                'category' => 'Soporte Técnico',
                'variables' => ['customer_name', 'ticket_number', 'agent_name'],
                'is_active' => true,
                'is_default' => false,
                'created_by' => $user?->id,
                'description' => 'Para solicitar información adicional al cliente'
            ],
            [
                'name' => 'Cierre de Ticket - Resuelto',
                'subject' => 'Ticket #{{ticket_number}} Resuelto',
                'content' => '<p>Estimado/a {{customer_name}},</p>
                <p>Nos complace informarle que el ticket <strong>{{ticket_number}}</strong> ha sido resuelto exitosamente.</p>
                <p><strong>Resumen de la solución:</strong></p>
                <p>Hemos implementado las medidas necesarias para resolver su consulta. Si el problema persiste o tiene alguna pregunta adicional, no dude en contactarnos.</p>
                <p>Si está satisfecho con la resolución, le agradeceríamos que califique nuestro servicio.</p>
                <p>Gracias por confiar en nuestro equipo de soporte.</p>
                <p>Saludos cordiales,<br>{{agent_name}}<br>Equipo de Soporte</p>',
                'type' => 'closing',
                'category' => 'Soporte Técnico',
                'variables' => ['customer_name', 'ticket_number', 'agent_name'],
                'is_active' => true,
                'is_default' => true,
                'created_by' => $user?->id,
                'description' => 'Plantilla para cerrar tickets resueltos'
            ],
            [
                'name' => 'Escalación a Nivel Superior',
                'subject' => 'Ticket #{{ticket_number}} - Escalado a Soporte Especializado',
                'content' => '<p>Hola {{customer_name}},</p>
                <p>Hemos revisado su consulta en el ticket <strong>{{ticket_number}}</strong> y hemos determinado que requiere atención especializada.</p>
                <p>Su caso ha sido escalado a nuestro equipo de soporte de nivel superior, quienes cuentan con la experiencia técnica necesaria para resolver su problema de manera efectiva.</p>
                <p>Un especialista se comunicará con usted dentro de las próximas 4 horas durante horario laboral.</p>
                <p>Agradecemos su paciencia mientras trabajamos en la resolución de su consulta.</p>
                <p>Cordialmente,<br>{{agent_name}}</p>',
                'type' => 'escalation',
                'category' => 'Soporte Técnico',
                'variables' => ['customer_name', 'ticket_number', 'agent_name'],
                'is_active' => true,
                'is_default' => true,
                'created_by' => $user?->id,
                'description' => 'Para tickets que requieren escalación a nivel superior'
            ],
            [
                'name' => 'Consulta de Ventas - Primera Respuesta',
                'subject' => 'Re: {{ticket_subject}}',
                'content' => '<p>Estimado/a {{customer_name}},</p>
                <p>Gracias por su interés en nuestros productos y servicios.</p>
                <p>Hemos recibido su consulta sobre <strong>{{ticket_subject}}</strong> y estaremos encantados de ayudarle.</p>
                <p>Nuestro equipo comercial revisará su solicitud y le proporcionará información detallada junto con una cotización personalizada en un plazo máximo de 24 horas.</p>
                <p>Mientras tanto, le invitamos a visitar nuestro sitio web para conocer más sobre nuestras soluciones.</p>
                <p>Si tiene alguna pregunta urgente, no dude en contactarnos al teléfono de ventas.</p>
                <p>Saludos comerciales,<br>{{agent_name}}<br>Equipo de Ventas</p>',
                'type' => 'response',
                'category' => 'Ventas',
                'variables' => ['customer_name', 'ticket_subject', 'agent_name'],
                'is_active' => true,
                'is_default' => false,
                'created_by' => $user?->id,
                'description' => 'Respuesta inicial para consultas comerciales'
            ],
            [
                'name' => 'Seguimiento de Satisfacción',
                'subject' => 'Su opinión es importante - Ticket #{{ticket_number}}',
                'content' => '<p>Hola {{customer_name}},</p>
                <p>Esperamos que se encuentre bien. Hace algunos días resolvimos su ticket <strong>{{ticket_number}}</strong> y quisiéramos conocer su experiencia.</p>
                <p>¿Está satisfecho con la solución proporcionada? ¿Hay algo en lo que podríamos mejorar?</p>
                <p>Su retroalimentación es muy valiosa para nosotros y nos ayuda a brindar un mejor servicio.</p>
                <p>Si tiene algún comentario o sugerencia, puede responder a este email o completar nuestra breve encuesta de satisfacción.</p>
                <p>Gracias por confiar en nosotros.</p>
                <p>Saludos cordiales,<br>{{agent_name}}<br>Equipo de Atención al Cliente</p>',
                'type' => 'custom',
                'category' => 'Atención al Cliente',
                'variables' => ['customer_name', 'ticket_number', 'agent_name'],
                'is_active' => true,
                'is_default' => false,
                'created_by' => $user?->id,
                'description' => 'Para hacer seguimiento de satisfacción post-resolución'
            ],
        ];

        foreach ($templates as $templateData) {
            Template::create($templateData);
        }
    }
}
