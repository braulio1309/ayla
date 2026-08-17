<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo turno asignado | Ayla</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4efe7; font-family: Arial, Helvetica, sans-serif; color: #3f3a36;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4efe7; padding: 32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 620px; background-color: #ffffff; border: 1px solid #e7ddd0;">
                    <tr>
                        <td style="background-color: #e9d9c7; border-bottom: 5px solid #c99191; padding: 28px 32px;">
                            <div style="font-size: 34px; line-height: 1; font-weight: 700; letter-spacing: 1px; color: #403936;">ayla</div>
                            <div style="margin-top: 7px; font-size: 11px; letter-spacing: 1.5px; color: #756a64;">CENTRO MEDICO - BELLEZA &amp; SPA</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px;">
                            <div style="display: inline-block; padding: 7px 12px; background-color: #c99191; color: #ffffff; font-size: 12px; font-weight: 700;">NUEVO TURNO</div>
                            <h1 style="margin: 18px 0 8px; font-size: 25px; line-height: 1.25; color: #403936;">Hola, {{ $especialista->name }}</h1>
                            <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #756a64;">Se ha agendado un nuevo turno a tu nombre. Estos son los detalles:</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e7ddd0; border-left: 4px solid #c99191;">
                                <tr>
                                    <td style="padding: 18px 20px; background-color: #fbf8f4;">
                                        <div style="margin-bottom: 12px; font-size: 12px; color: #8a7d75;">PACIENTE</div>
                                        <div style="font-size: 17px; font-weight: 700; color: #403936;">{{ $paciente->nombre }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0 20px 18px; background-color: #fbf8f4;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td width="50%" style="padding-top: 14px; vertical-align: top;">
                                                    <div style="font-size: 12px; color: #8a7d75;">FECHA</div>
                                                    <div style="margin-top: 5px; font-size: 15px; font-weight: 700; color: #403936;">{{ $cita->fecha->format('d/m/Y') }}</div>
                                                </td>
                                                <td width="50%" style="padding-top: 14px; vertical-align: top;">
                                                    <div style="font-size: 12px; color: #8a7d75;">HORARIO</div>
                                                    <div style="margin-top: 5px; font-size: 15px; font-weight: 700; color: #403936;">{{ \Carbon\Carbon::parse($cita->hora_inicio)->format('h:i A') }} - {{ \Carbon\Carbon::parse($cita->hora_fin)->format('h:i A') }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 20px; border-top: 1px solid #e7ddd0; background-color: #ffffff;">
                                        <div style="font-size: 12px; color: #8a7d75;">SERVICIO(S)</div>
                                        <div style="margin-top: 5px; font-size: 15px; color: #403936;">{{ $servicios->pluck('nombre')->join(', ') ?: 'Atención General' }}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 20px; border-top: 1px solid #e7ddd0; background-color: #ffffff;">
                                        <div style="font-size: 12px; color: #8a7d75;">MONTO DEL TURNO</div>
                                        <div style="margin-top: 5px; font-size: 18px; font-weight: 700; color: #5b8c5a;">${{ number_format((float) $cita->monto_total, 2) }}</div>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 26px 0 0; font-size: 13px; line-height: 1.6; color: #756a64;">Puedes consultar y actualizar el estado de este turno desde tu panel de Ayla.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 32px; background-color: #403936; text-align: center;">
                            <div style="font-size: 13px; color: #e9d9c7;">Ayla Centro Médico - Belleza &amp; Spa</div>
                            <div style="margin-top: 5px; font-size: 11px; color: #cfc4bb;">Este correo fue generado automáticamente.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
