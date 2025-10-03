# Configuración de Zoho Mail OAuth para Adjuntos

## Problema
El webhook de Zoho Mail no envía los adjuntos directamente. Para recibirlos, debemos usar la API REST de Zoho Mail con autenticación OAuth 2.0.

## Solución Implementada
Se ha actualizado el sistema para descargar adjuntos automáticamente usando la API REST de Zoho Mail cuando el webhook no los incluye.

## Pasos para Configurar OAuth

### 1. Configurar Client ID y Client Secret en Zoho Developer Console

1. Ve a [Zoho Developer Console](https://api-console.zoho.com/)
2. Crea un nuevo "Server-based Application"
3. Configura los siguientes valores:
   - **Client Name**: TARdesk Mail Integration
   - **Homepage URL**: `http://tu-dominio.com` (o `http://localhost` para desarrollo)
   - **Authorized Redirect URIs**: `http://tu-dominio.com/oauth/zoho/callback`
     - Para desarrollo local: `http://localhost/oauth/zoho/callback`
4. Copia el **Client ID** y **Client Secret**

### 2. Agregar Credenciales al archivo .env

Abre tu archivo `.env` y agrega/actualiza las siguientes variables:

```env
ZOHO_MAIL_CLIENT_ID=tu_client_id_aqui
ZOHO_MAIL_CLIENT_SECRET=tu_client_secret_aqui
ZOHO_MAIL_REFRESH_TOKEN=
ZOHO_MAIL_ACCESS_TOKEN=
ZOHO_MAIL_API_BASE=https://mail.zoho.com/api
ZOHO_MAIL_ACCOUNT_ID=
ZOHO_MAIL_WEBHOOK_SECRET=tu_webhook_secret_opcional
```

### 3. Obtener Refresh Token y Access Token

#### Opción A: Usando la Aplicación (Recomendado)

1. Asegúrate de que tu aplicación esté corriendo:
   ```bash
   php artisan serve
   ```

2. En tu navegador, visita:
   ```
   http://localhost:8000/oauth/zoho/authorize
   ```

3. Serás redirigido a Zoho para autorizar la aplicación
4. Acepta los permisos solicitados
5. Serás redirigido de vuelta a tu aplicación con los tokens
6. Copia el **refresh_token** y **access_token** del JSON mostrado
7. Actualiza tu archivo `.env`:
   ```env
   ZOHO_MAIL_REFRESH_TOKEN=el_refresh_token_obtenido
   ZOHO_MAIL_ACCESS_TOKEN=el_access_token_obtenido
   ```

#### Opción B: Manualmente con cURL

1. Abre en tu navegador (reemplaza CLIENT_ID y REDIRECT_URI):
   ```
   https://accounts.zoho.com/oauth/v2/auth?scope=ZohoMail.messages.ALL,ZohoMail.accounts.READ&client_id=CLIENT_ID&response_type=code&access_type=offline&redirect_uri=REDIRECT_URI&prompt=consent
   ```

2. Autoriza y copia el `code` de la URL de redirección

3. Ejecuta este comando (reemplaza los valores):
   ```bash
   curl -X POST https://accounts.zoho.com/oauth/v2/token \
     -d "grant_type=authorization_code" \
     -d "client_id=TU_CLIENT_ID" \
     -d "client_secret=TU_CLIENT_SECRET" \
     -d "redirect_uri=TU_REDIRECT_URI" \
     -d "code=EL_CODE_OBTENIDO"
   ```

4. Copia el `refresh_token` del JSON de respuesta

### 4. Obtener Account ID (Opcional pero Recomendado)

Para mejorar el rendimiento, puedes obtener tu Account ID:

```bash
curl -H "Authorization: Zoho-oauthtoken TU_ACCESS_TOKEN" \
  "https://mail.zoho.com/api/accounts"
```

Copia el `accountId` del JSON de respuesta y agrégalo a tu `.env`:
```env
ZOHO_MAIL_ACCOUNT_ID=el_account_id_obtenido
```

### 5. Reiniciar la Aplicación

```bash
php artisan config:clear
php artisan cache:clear
```

## URLs de Configuración en Zoho Developer Console

- **Homepage URL**: `http://tu-dominio.com` o `http://localhost`
- **Authorized Redirect URI**: `http://tu-dominio.com/oauth/zoho/callback`

## Scopes Requeridos

La aplicación necesita los siguientes scopes de OAuth:
- `ZohoMail.messages.ALL` - Para leer mensajes y adjuntos
- `ZohoMail.accounts.READ` - Para obtener información de la cuenta

## Verificación

Para verificar que todo está funcionando:

1. Envía un email con adjuntos a la dirección configurada en el webhook
2. Revisa los logs en `storage/logs/laravel.log`
3. Busca mensajes como:
   - `"Fetching attachment info"`
   - `"Attachment downloaded successfully"`
   - `"Adjunto guardado en storage"`
4. Los adjuntos deberían aparecer en `storage/app/public/ticket_attachments/`

## Solución de Problemas

### Error: "INVALID_OAUTHTOKEN"
- El access token expiró (duran 1 hora)
- La aplicación automáticamente usa el refresh token para obtener uno nuevo
- Verifica que `ZOHO_MAIL_REFRESH_TOKEN` esté configurado correctamente

### Error: "URL_RULE_NOT_CONFIGURED"
- La URL de redirección no está configurada en Zoho Developer Console
- Verifica que la Redirect URI en Zoho coincida exactamente con la de tu aplicación

### No se descargan adjuntos
- Verifica los logs con: `tail -f storage/logs/laravel.log`
- Asegúrate de que `ZOHO_MAIL_CLIENT_ID`, `ZOHO_MAIL_CLIENT_SECRET` y `ZOHO_MAIL_REFRESH_TOKEN` estén configurados
- Verifica que el scope incluya `ZohoMail.messages.ALL`

### Adjuntos no aparecen en el ticket
- Verifica que existe el directorio `storage/app/public/ticket_attachments/`
- Crea el enlace simbólico si no existe: `php artisan storage:link`
- Verifica permisos del directorio: `chmod -R 775 storage/`

## Arquitectura de la Solución

1. **Webhook recibe email** → No incluye adjuntos directamente
2. **Sistema detecta message_id** → Del payload del webhook
3. **Obtiene access token** → Usa refresh_token para obtener access_token válido
4. **Consulta API de Zoho** → `/api/accounts/{accountId}/messages/{messageId}/attachmentinfo`
5. **Descarga cada adjunto** → Usando el `attachmentPath` de cada adjunto
6. **Guarda en storage** → `storage/app/public/ticket_attachments/`
7. **Asocia al ticket** → En la columna `metadata` o `attachments` según el caso

## Endpoints Utilizados

- `GET /api/accounts` - Obtener accountId
- `GET /api/accounts/{accountId}/messages/{messageId}/attachmentinfo` - Info de adjuntos
- `GET /api/accounts/{accountId}/{attachmentPath}` - Descargar adjunto

## Refresh Token

El refresh token **NO EXPIRA** y se usa para obtener nuevos access tokens cada hora automáticamente. Guárdalo de forma segura y no lo compartas.

