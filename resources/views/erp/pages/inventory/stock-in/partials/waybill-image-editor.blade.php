<div class="input-group">
    <input type="file" class="form-control" id="waybill_image" name="waybill_image" accept="image/*"
        @if ($capture ?? false) capture="environment" @endif>
</div>

<div id="waybill-image-editor" class="mt-3" style="display: none;">
    <div class="border rounded-3 bg-light p-2 text-center">
        <canvas id="waybill-preview-canvas" class="mw-100 rounded-2"
            style="max-height: 420px; object-fit: contain;"></canvas>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
        <button type="button" class="btn btn-sm btn-light-brand" id="rotate-waybill-left">
            <i class="feather-rotate-ccw me-1"></i> Putar Kiri
        </button>
        <button type="button" class="btn btn-sm btn-light-brand" id="rotate-waybill-right">
            <i class="feather-rotate-cw me-1"></i> Putar Kanan
        </button>
        <small class="text-muted" id="waybill-rotation-status">Posisi asli</small>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            const input = document.getElementById('waybill_image');
            const canvas = document.getElementById('waybill-preview-canvas');
            const context = canvas.getContext('2d');
            const editor = document.getElementById('waybill-image-editor');
            const status = document.getElementById('waybill-rotation-status');
            const rotateButtons = document.querySelectorAll('#rotate-waybill-left, #rotate-waybill-right');
            let sourceImage = null;
            let sourceFile = null;
            let rotation = 0;
            let processing = false;

            function setProcessing(value) {
                processing = value;
                rotateButtons.forEach(button => button.disabled = value);
                document.querySelectorAll('[type="submit"][form="stockInForm"], #stockInForm [type="submit"]')
                    .forEach(button => button.disabled = value);
                status.textContent = value ? 'Memproses gambar...' :
                    (rotation === 0 ? 'Posisi asli' : `Diputar ${rotation}°`);
            }

            function rotatedFileName(file) {
                const baseName = file.name.replace(/\.[^/.]+$/, '');
                return `${baseName}_rotated.jpg`;
            }

            function replaceUploadWithCanvas(quality = 0.9) {
                canvas.toBlob(function(blob) {
                    if (!blob) {
                        setProcessing(false);
                        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gambar tidak dapat diputar.' });
                        return;
                    }

                    // Production membatasi upload 2 MB. Turunkan kualitas bertahap
                    // supaya hasil rotasi tetap lolos validasi server.
                    if (blob.size > 1.9 * 1024 * 1024 && quality > 0.55) {
                        replaceUploadWithCanvas(quality - 0.1);
                        return;
                    }

                    const rotatedFile = new File([blob], rotatedFileName(sourceFile), {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });
                    const transfer = new DataTransfer();
                    transfer.items.add(rotatedFile);
                    input.files = transfer.files;
                    setProcessing(false);
                }, 'image/jpeg', quality);
            }

            function renderImage(updateUpload = false) {
                if (!sourceImage || processing) return;

                const normalizedRotation = ((rotation % 360) + 360) % 360;
                const swapsSides = normalizedRotation === 90 || normalizedRotation === 270;
                const maxDimension = 2000;
                const scale = Math.min(1, maxDimension / Math.max(sourceImage.naturalWidth, sourceImage.naturalHeight));
                const imageWidth = Math.round(sourceImage.naturalWidth * scale);
                const imageHeight = Math.round(sourceImage.naturalHeight * scale);

                canvas.width = swapsSides ? imageHeight : imageWidth;
                canvas.height = swapsSides ? imageWidth : imageHeight;
                context.clearRect(0, 0, canvas.width, canvas.height);
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.save();
                context.translate(canvas.width / 2, canvas.height / 2);
                context.rotate(normalizedRotation * Math.PI / 180);
                context.drawImage(sourceImage, -imageWidth / 2, -imageHeight / 2, imageWidth, imageHeight);
                context.restore();

                status.textContent = normalizedRotation === 0 ? 'Posisi asli' : `Diputar ${normalizedRotation}°`;

                if (!updateUpload) return;

                setProcessing(true);
                replaceUploadWithCanvas();
            }

            input.addEventListener('change', function() {
                const file = input.files[0];
                if (!file || !file.type.startsWith('image/')) {
                    editor.style.display = 'none';
                    sourceImage = null;
                    sourceFile = null;
                    return;
                }

                sourceFile = file;
                rotation = 0;
                const imageUrl = URL.createObjectURL(file);
                const image = new Image();
                image.onload = function() {
                    URL.revokeObjectURL(imageUrl);
                    sourceImage = image;
                    editor.style.display = 'block';
                    renderImage(false);
                };
                image.onerror = function() {
                    URL.revokeObjectURL(imageUrl);
                    editor.style.display = 'none';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'File gambar tidak dapat dibaca.' });
                };
                image.src = imageUrl;
            });

            document.getElementById('rotate-waybill-left').addEventListener('click', function() {
                if (processing) return;
                rotation = (rotation + 270) % 360;
                renderImage(true);
            });

            document.getElementById('rotate-waybill-right').addEventListener('click', function() {
                if (processing) return;
                rotation = (rotation + 90) % 360;
                renderImage(true);
            });
        });
    </script>
@endpush
