/**
 * BYC GROWTH — Client-Side Adaptive Image Compression Utilities
 * Keeps uploaded/cropped photos comfortably below strict hosting upload limits (prevents HTTP 413)
 * while maintaining high visual sharpness for portraits and raw backups.
 */

export function canvasToDataUrlWithBudget(canvas, maxBytes = 180 * 1024, initialQuality = 0.85) {
    return new Promise((resolve, reject) => {
        try {
            // Composite onto clean white background before JPEG export so PNG alpha renders cleanly
            const outCanvas = document.createElement('canvas');
            outCanvas.width = canvas.width;
            outCanvas.height = canvas.height;
            const outCtx = outCanvas.getContext('2d');
            outCtx.fillStyle = '#ffffff';
            outCtx.fillRect(0, 0, outCanvas.width, outCanvas.height);
            outCtx.drawImage(canvas, 0, 0);

            const qualities = [initialQuality, 0.76, 0.68, 0.58, 0.48];
            let safetyCount = 0;

            function attempt(stepIdx, currentCanvas) {
                safetyCount++;
                const quality = qualities[stepIdx] ?? 0.48;

                currentCanvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error('Canvas export failed'));
                        return;
                    }

                    const atMinScale = currentCanvas.width <= 400 && currentCanvas.height <= 400;
                    if (blob.size <= maxBytes || safetyCount >= 12 || (stepIdx >= qualities.length - 1 && atMinScale)) {
                        const reader = new FileReader();
                        reader.onload = () => resolve({ dataUrl: reader.result, blob });
                        reader.onerror = reject;
                        reader.readAsDataURL(blob);
                        return;
                    }

                    if (stepIdx < qualities.length - 1) {
                        attempt(stepIdx + 1, currentCanvas);
                    } else {
                        const nextW = Math.max(400, Math.round(currentCanvas.width * 0.75));
                        const nextH = Math.max(400, Math.round(currentCanvas.height * 0.75));
                        const scaled = document.createElement('canvas');
                        scaled.width = nextW;
                        scaled.height = nextH;
                        const sCtx = scaled.getContext('2d');
                        sCtx.imageSmoothingEnabled = true;
                        sCtx.imageSmoothingQuality = 'high';
                        sCtx.drawImage(currentCanvas, 0, 0, nextW, nextH);
                        attempt(1, scaled);
                    }
                }, 'image/jpeg', quality);
            }

            attempt(0, outCanvas);
        } catch (err) {
            reject(err);
        }
    });
}

export function compressImageToDataUrl(imgElement, maxDimension = 1200, maxBytes = 240 * 1024) {
    return new Promise((resolve, reject) => {
        try {
            const natW = imgElement.naturalWidth || imgElement.width;
            const natH = imgElement.naturalHeight || imgElement.height;
            if (!natW || !natH) {
                reject(new Error('Invalid image dimensions'));
                return;
            }

            const ratio = Math.min(1, maxDimension / Math.max(natW, natH));
            const targetW = Math.max(1, Math.round(natW * ratio));
            const targetH = Math.max(1, Math.round(natH * ratio));

            const canvas = document.createElement('canvas');
            canvas.width = targetW;
            canvas.height = targetH;
            const ctx = canvas.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(imgElement, 0, 0, targetW, targetH);

            canvasToDataUrlWithBudget(canvas, maxBytes, 0.84)
                .then((res) => resolve(res.dataUrl))
                .catch(reject);
        } catch (err) {
            reject(err);
        }
    });
}

export function registerImageCompressHelpers() {
    window.bycCanvasToDataUrl = canvasToDataUrlWithBudget;
    window.bycCompressRawImage = compressImageToDataUrl;
}
