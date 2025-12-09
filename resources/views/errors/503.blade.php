<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizando...</title>
    <meta http-equiv="refresh" content="10">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #111827 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .container {
            text-align: center;
            padding: 2rem;
            max-width: 500px;
        }

        .icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #60a5fa;
        }

        p {
            font-size: 1.1rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .loader {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .loader span {
            width: 12px;
            height: 12px;
            background: #3b82f6;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .loader span:nth-child(1) { animation-delay: -0.32s; }
        .loader span:nth-child(2) { animation-delay: -0.16s; }
        .loader span:nth-child(3) { animation-delay: 0s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        .timer {
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #9ca3af;
        }

        .refresh-note {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <h1>Estamos actualizando</h1>
        <p>
            Estamos subiendo mejoras al sistema.<br>
            Solo serán unos segundos, por favor espera.
        </p>

        <div class="loader">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="timer">
            La página se recargará automáticamente...
        </div>

        <div class="refresh-note">
            Si no se recarga, pulsa F5 o actualiza manualmente.
        </div>
    </div>
</body>
</html>
