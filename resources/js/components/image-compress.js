/**
 * BYC GROWTH — Client-Side Adaptive Image Compression Utilities
 * Keeps uploaded/cropped photos comfortably below strict hosting upload limits (prevents HTTP 413)
 * while maintaining high visual sharpness across Members, Activities, Games, Homepage, and Cash.
 */

function toJpgFilename(name) {
    const clean = (name || 'image.jpg').replace(/\.[^/.]+$/, '');
    return `${clean}.jpg`;
}

export function compressCanvasToBlob(canvas, maxBytes = 200 * 1024, initialQuality = 0.85, minDimension = 400) {
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

                    const atMinScale = currentCanvas.width <= minDimension && currentCanvas.height <= minDimension;
                    if (blob.size <= maxBytes || safetyCount >= 12 || (stepIdx >= qualities.length - 1 && atMinScale)) {
                        resolve(blob);
                        return;
                    }

                    if (stepIdx < qualities.length - 1) {
                        attempt(stepIdx + 1, currentCanvas);
                    } else {
                        const nextW = Math.max(minDimension, Math.round(currentCanvas.width * 0.75));
                        const nextH = Math.max(minDimension, Math.round(currentCanvas.height * 0.75));
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

export function canvasToDataUrlWithBudget(canvas, maxBytes = 180 * 1024, initialQuality = 0.85) {
    return compressCanvasToBlob(canvas, maxBytes, initialQuality).then((blob) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve({ dataUrl: reader.result, blob });
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
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

export function compressImageFile(file, maxDimension = 1200, maxBytes = 280 * 1024) {
    return new Promise((resolve) => {
        if (!file || !(file instanceof File)) {
            resolve(file);
            return;
        }

        const isGif = file.type === 'image/gif' || /\.gif$/i.test(file.name);
        if (isGif && file.size <= 500 * 1024) {
            resolve(file);
            return;
        }

        const isRaster = file.type.startsWith('image/') || /\.(jpe?g|png|webp|bmp)$/i.test(file.name);
        if (!isRaster) {
            resolve(file);
            return;
        }

        if (file.size <= Math.min(maxBytes, 150 * 1024) && /\.(jpe?g)$/i.test(file.name)) {
            resolve(file);
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        const img = new Image();

        img.onload = async () => {
            try {
                const natW = img.naturalWidth || img.width;
                const natH = img.naturalHeight || img.height;
                if (!natW || !natH) {
                    URL.revokeObjectURL(objectUrl);
                    resolve(file);
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
                ctx.drawImage(img, 0, 0, targetW, targetH);

                const blob = await compressCanvasToBlob(canvas, maxBytes, 0.85, 480);
                URL.revokeObjectURL(objectUrl);

                if (blob && blob.size < file.size) {
                    resolve(new File([blob], toJpgFilename(file.name), {
                        type: 'image/jpeg',
                        lastModified: Date.now(),
                    }));
                } else {
                    resolve(file);
                }
            } catch (_) {
                URL.revokeObjectURL(objectUrl);
                resolve(file);
            }
        };

        img.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(file);
        };

        img.src = objectUrl;
    });
}

export function initAutoFileCompression() {
    if (window.__bycAutoFileCompressionBound) return;
    window.__bycAutoFileCompressionBound = true;

    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form[data-byc-auto-compressed="true"]').forEach((f) => {
            delete f.dataset.bycAutoCompressed;
        });
    });

    window.addEventListener('submit', async (e) => {
        if (e.defaultPrevented) return;
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.bycAutoCompressed === 'true') return;

        const enctype = (form.getAttribute('enctype') || '').toLowerCase();
        if (enctype !== 'multipart/form-data') return;

        // Skip forms managed by dedicated custom croppers
        if (form.querySelector('input[name="photo_base64"], input[name="image_base64"]') ||
            ['form-admin-guess-round', 'form-guess-round', 'form-add-slide', 'form-edit-slide', 'form-edit-member-page', 'form-create-member', 'form-profile'].includes(form.id)) {
            return;
        }

        const fileInputs = Array.from(form.querySelectorAll('input[type="file"]'))
            .filter((inp) => !inp.disabled && inp.files && inp.files.length > 0);
        if (fileInputs.length === 0) return;

        const allFiles = [];
        let totalBytes = 0;
        fileInputs.forEach((inp) => {
            Array.from(inp.files).forEach((f) => {
                allFiles.push(f);
                totalBytes += f.size || 0;
            });
        });

        // Fast-path: small payloads (<= 420 KB total) proceed natively with zero delay
        if (totalBytes <= 420 * 1024) return;

        e.preventDefault();
        const submitBtn = e.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            if (submitBtn.tagName === 'BUTTON') {
                submitBtn.textContent = allFiles.length > 1 ? 'Optimizing photos...' : 'Optimizing photo...';
            }
        }

        try {
            const totalBudget = 720 * 1024;
            const perFileBudget = Math.max(80 * 1024, Math.min(320 * 1024, Math.floor(totalBudget / allFiles.length)));
            const maxDim = allFiles.length > 1 ? 1100 : 1200;

            if (window.DataTransfer) {
                for (const inp of fileInputs) {
                    const dt = new DataTransfer();
                    for (const file of Array.from(inp.files)) {
                        const outFile = await compressImageFile(file, maxDim, perFileBudget);
                        dt.items.add(outFile);
                    }
                    inp.files = dt.files;
                }
            }
        } catch (err) {
            console.warn('Auto file compression fallback:', err);
        } finally {
            form.dataset.bycAutoCompressed = 'true';
            if (e.submitter && e.submitter.name) {
                const hiddenSub = document.createElement('input');
                hiddenSub.type = 'hidden';
                hiddenSub.name = e.submitter.name;
                hiddenSub.value = e.submitter.value || '1';
                form.appendChild(hiddenSub);
            }
            if (submitBtn && submitBtn.tagName === 'BUTTON') {
                submitBtn.textContent = 'Uploading...';
            }
            HTMLFormElement.prototype.submit.call(form);
        }
    });
}

export function registerImageCompressHelpers() {
    window.bycCanvasToDataUrl = canvasToDataUrlWithBudget;
    window.bycCompressRawImage = compressImageToDataUrl;
    window.bycCompressCanvasToBlob = compressCanvasToBlob;
    window.bycCompressImageFile = compressImageFile;
}
