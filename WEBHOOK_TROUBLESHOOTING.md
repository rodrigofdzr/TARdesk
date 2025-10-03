# 🔧 Solución: Webhook de Zoho Mail Fallando

## ❌ Problema
Zoho está mostrando: "Please review your webhook endpoint to ensure it is available and functioning correctly. The Outgoing webhook TARdesk will be disabled if consecutive delivery failure is observed."

## ✅ Solución Implementada

He actualizado el webhook controller para:
1. ✅ Responder correctamente a las verificaciones de Zoho (HTTP 200)
2. ✅ Mapear correctamente los campos del payload que Zoho envía
3. ✅ Agregar mejor logging para debugging
4. ✅ Crear endpoint de health check

## 🚀 Pasos para Verificar y Solucionar

### 1. Verifica que tu servidor esté accesible

Prueba el endpoint de health check:

```bash
curl http://tu-servidor.com/webhooks/zoho-mail/health
```

Deberías ver:
```json
{
  "status": "ok",
  "service": "TARdesk Zoho Mail Webhook",
  "timestamp": "2025-10-03T...",
  "endpoint": "http://tu-servidor.com/webhooks/zoho-mail",
  "ready": true
}
```

### 2. Prueba el webhook directamente

```bash
curl -X POST http://tu-servidor.com/webhooks/zoho-mail \
  -H "Content-Type: application/json" \
  -d '{
    "from_email": "test@example.com",
    "to_email": "desarrollo@tarmexico.com",
    "subject": "Test webhook",
    "body_html": "<p>Test message</p>",
    "message_id": "test123"
  }'
```

Deberías recibir:
```json
{"ok":true,"ticket_id":XX}
```

O si el email es filtrado:
```json
{"ok":false}
```

### 3. Verifica los logs

```bash
tail -f storage/logs/laravel.log
```

Busca mensajes como:
- ✅ `"Zoho webhook payload mapped"`
- ✅ `"Procesando email entrante"`
- ⚠️ `"Zoho webhook mapping: missing critical fields"` (si hay problemas)

### 4. Re-configurar el Webhook en Zoho Mail

1. Ve a **Zoho Mail** > **Settings** > **Webhooks**
2. Edita o elimina el webhook existente "TARdesk"
3. Crea uno nuevo con:
   - **Name**: TARdesk
   - **URL**: `http://tu-servidor.com/webhooks/zoho-mail`
   - **Method**: POST
   - **Content Type**: application/json
   - **Events**: Incoming Mail / Mail Received

4. Zoho enviará una petición de verificación (con body vacío)
5. Tu webhook responderá HTTP 200 OK automáticamente
6. El webhook quedará habilitado ✅

### 5. Verificar que el webhook está activo

Después de configurar:
1. Envía un email de prueba a la dirección configurada
2. Verifica los logs: `tail -f storage/logs/laravel.log`
3. Deberías ver el payload completo guardado en `storage/logs/email_payload_*.json`

## 🔍 Debugging

### Si el webhook sigue fallando:

**Problema: Servidor no accesible desde internet**
```bash
# Verifica que tu servidor esté escuchando
curl http://tu-servidor.com/webhooks/zoho-mail/health

# Si falla, verifica:
# 1. Que tu servidor esté corriendo
# 2. Firewall permite conexiones en puerto 80/443
# 3. DNS apunta correctamente a tu servidor
```

**Problema: HTTPS requerido**
```bash
# Zoho puede requerir HTTPS en producción
# Instala certificado SSL con Let's Encrypt:
sudo certbot --nginx -d tu-servidor.com
```

**Problema: Timeout del webhook**
```bash
# Verifica que el procesamiento sea rápido
# Los logs mostrarán el tiempo de procesamiento
# Considera usar queue para procesamiento asíncrono
```

### Verificar payload real de Zoho

El sistema guarda automáticamente cada payload en:
```
storage/logs/email_payload_YYYYMMDD_HHMMSS.json
```

Revisa este archivo para ver exactamente qué está enviando Zoho.

## 📊 Campos que Zoho Mail Webhook Envía

Según el payload que observamos, Zoho envía:
```json
{
  "from_email": "remitente@example.com",
  "to_email": "destinatario@example.com",
  "subject": "Asunto del email",
  "body_html": "HTML del mensaje",
  "message_id": "ID único del mensaje",
  "sender_name": "Nombre del remitente",
  "received_time": timestamp
}
```

El webhook ahora mapea correctamente todos estos campos.

## ⚠️ Importante: Adjuntos

El webhook de Zoho **NO incluye adjuntos directamente**. Para descargar adjuntos:

1. El webhook envía el `message_id`
2. El sistema usa ese `message_id` para descargar adjuntos vía API REST
3. Esto requiere que configures OAuth (ver `GUIA_RAPIDA_ZOHO.md`)

## 🔐 Webhook Secret (Opcional pero Recomendado)

Para mayor seguridad, configura un webhook secret:

1. En Zoho Mail webhook settings, agrega un "Secret"
2. Agrégalo a tu `.env`:
   ```env
   ZOHO_MAIL_WEBHOOK_SECRET=tu_secret_aqui
   ```
3. El webhook verificará la firma HMAC-SHA256

## 📋 Checklist de Verificación

- [ ] El servidor es accesible públicamente
- [ ] `/webhooks/zoho-mail/health` responde HTTP 200
- [ ] El webhook está configurado en Zoho Mail
- [ ] Los logs muestran "Zoho webhook payload mapped"
- [ ] Se crean archivos email_payload_*.json
- [ ] Los tickets se crean correctamente
- [ ] (Opcional) OAuth configurado para adjuntos
- [ ] (Opcional) Webhook secret configurado

## 🆘 Si Nada Funciona

1. **Verifica conectividad básica:**
   ```bash
   curl -v http://tu-servidor.com/webhooks/zoho-mail/health
   ```

2. **Prueba con ngrok (solo para testing):**
   ```bash
   # Útil para desarrollo local
   ngrok http 8000
   # Usa la URL de ngrok en Zoho webhook
   ```

3. **Revisa los logs de Laravel:**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

4. **Verifica los logs del servidor web:**
   ```bash
   # Nginx
   tail -f /var/log/nginx/error.log
   
   # Apache
   tail -f /var/log/apache2/error.log
   ```

## 🎯 Resumen

El webhook ahora:
- ✅ Responde correctamente a verificaciones de Zoho
- ✅ Mapea correctamente los campos del payload
- ✅ Guarda payloads para debugging
- ✅ Tiene endpoint de health check
- ✅ Maneja errores apropiadamente
- ✅ Descarga adjuntos vía API REST (cuando OAuth está configurado)

El problema debería estar resuelto. Si Zoho sigue mostrando el error, es probable que sea un problema de conectividad de red o firewall.

