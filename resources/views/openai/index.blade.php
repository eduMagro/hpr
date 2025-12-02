<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escaneo de Albaranes con IA - OpenAI GPT-4</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5rem;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .upload-section {
            background: #f8f9fa;
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .upload-section:hover {
            border-color: #764ba2;
            background: #f0f1ff;
        }

        .upload-section.dragover {
            background: #e8eaff;
            border-color: #764ba2;
            transform: scale(1.02);
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .file-input-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .file-list {
            margin-top: 20px;
            text-align: left;
        }

        .file-item {
            background: white;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .file-item-name {
            color: #333;
            font-weight: 500;
        }

        .file-item-size {
            color: #999;
            font-size: 0.9rem;
        }

        .btn-process {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 15px 50px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-process:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
        }

        .btn-process:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .results-section {
            margin-top: 40px;
        }

        .result-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .result-header {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .result-image {
            flex: 0 0 300px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .result-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .result-content {
            flex: 1;
            min-width: 300px;
        }

        .result-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .result-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .meta-badge {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .result-text {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #10b981;
            white-space: pre-wrap;
            line-height: 1.8;
            max-height: 600px;
            overflow-y: auto;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #c33;
            margin-top: 10px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .info-box {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box p {
            margin: 5px 0;
            color: #065f46;
        }

        .info-box strong {
            color: #047857;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            h1 {
                font-size: 2rem;
            }

            .result-header {
                flex-direction: column;
            }

            .result-image {
                flex: 0 0 auto;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="text-align: center;">
            <span class="badge">Powered by OpenAI GPT-4 Vision</span>
        </div>

        <h1>Escaneo Inteligente de Albaranes</h1>
        <p class="subtitle">Extracción de texto con IA de última generación</p>

        <div class="info-box">
            <p><strong>Ventajas de usar GPT-4 Vision:</strong></p>
            <p>• Reconocimiento de texto con IA avanzada</p>
            <p>• Texto estructurado y organizado automáticamente</p>
            <p>• Comprensión del contexto del documento</p>
            <p>• Manejo de texto manuscrito y en cualquier idioma</p>
        </div>

        <form action="{{ route('openai.procesar') }}" method="POST" enctype="multipart/form-data" id="ocrForm">
            @csrf
            <div class="upload-section" id="dropZone">
                <div class="file-input-wrapper">
                    <input type="file"
                           name="imagenes[]"
                           id="imagenes"
                           accept="image/*"
                           multiple
                           onchange="handleFileSelect(event)">
                    <label for="imagenes" class="file-input-label">
                        Seleccionar Imágenes
                    </label>
                </div>
                <p style="margin-top: 15px; color: #666;">o arrastra archivos aquí</p>
                <div id="fileList" class="file-list"></div>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="btn-process" id="processBtn" disabled>
                    Procesar con IA
                </button>
            </div>
        </form>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 15px; color: #667eea; font-weight: 600;">Procesando imágenes con IA...</p>
            <p style="color: #999; font-size: 0.9rem;">Esto puede tardar 10-30 segundos por imagen</p>
        </div>

        @if(isset($resultados) && count($resultados) > 0)
        <div class="results-section">
            <h2 style="color: #333; margin-bottom: 20px;">Resultados del Escaneo</h2>
            @foreach($resultados as $resultado)
            <div class="result-card">
                <div class="result-header">
                    @if($resultado['ruta'])
                    <div class="result-image">
                        <img src="{{ $resultado['ruta'] }}" alt="{{ $resultado['nombre_archivo'] }}">
                    </div>
                    @endif
                    <div class="result-content">
                        <div class="result-title">{{ $resultado['nombre_archivo'] }}</div>
                        @if(isset($resultado['tokens_usados']))
                        <div class="result-meta">
                            <span class="meta-badge">Tokens usados: {{ $resultado['tokens_usados'] }}</span>
                        </div>
                        @endif
                        @if($resultado['error'])
                            <div class="error-message">{{ $resultado['error'] }}</div>
                        @else
                            <div class="result-text">{{ $resultado['texto'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <script>
        let selectedFiles = [];

        function handleFileSelect(event) {
            const files = Array.from(event.target.files);
            selectedFiles = files;
            displayFileList();
        }

        function displayFileList() {
            const fileList = document.getElementById('fileList');
            const processBtn = document.getElementById('processBtn');

            if (selectedFiles.length === 0) {
                fileList.innerHTML = '';
                processBtn.disabled = true;
                return;
            }

            processBtn.disabled = false;

            let html = '<h3 style="margin-bottom: 10px; color: #333;">Archivos seleccionados:</h3>';
            selectedFiles.forEach((file, index) => {
                const sizeKB = (file.size / 1024).toFixed(2);
                html += `
                    <div class="file-item">
                        <span class="file-item-name">${file.name}</span>
                        <span class="file-item-size">${sizeKB} KB</span>
                    </div>
                `;
            });
            fileList.innerHTML = html;
        }

        // Drag and drop functionality
        const dropZone = document.getElementById('dropZone');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('dragover');
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('imagenes').files = files;
            selectedFiles = Array.from(files);
            displayFileList();
        }, false);

        // Show loading on form submit
        document.getElementById('ocrForm').addEventListener('submit', () => {
            document.getElementById('loading').classList.add('active');
            document.getElementById('processBtn').disabled = true;
        });
    </script>
</body>
</html>
