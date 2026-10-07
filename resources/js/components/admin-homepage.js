/**
 * BYC GROWTH 2.0 — Admin Homepage Management Component
 * Controls slideshow preview, cropping studio, drag-and-drop reordering, and AJAX deletions.
 */

export function initAdminHomepage() {
    const modal = document.getElementById('modal-add-slide');
    if (modal) {
        const openBtns = document.querySelectorAll('.btn-trigger-add-slide');
        const closeBtns = modal.querySelectorAll('.btn-close-modal');
        const form = document.getElementById('form-add-slide');
        const fileInput = document.getElementById('add-slide-image');
        const dropzone = document.getElementById('crop-dropzone');
        const fileInfo = document.getElementById('crop-file-info');
        const fileNameEl = document.getElementById('crop-file-name');
        const fileSizeEl = document.getElementById('crop-file-size');
        const btnChangePhoto = document.getElementById('btn-change-photo');
        const studio = document.getElementById('crop-studio');
        const viewport = document.getElementById('crop-viewport');
        const cropImg = document.getElementById('crop-image-element');
        const btnSubmit = document.getElementById('btn-submit-add-slide');

        const zoomRange = document.getElementById('crop-zoom-range');
        const zoomLabel = document.getElementById('crop-zoom-label');
        const btnReset = document.getElementById('btn-reset-crop');

        // Cropping State
        let originalFile = null;
        let imgNaturalW = 0;
        let imgNaturalH = 0;
        let currentZoom = 1.0;
        let currentOffsetX = 0;
        let currentOffsetY = 0;
        const viewportW = 320;
        const viewportH = 240;
        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let startOffsetX = 0;
        let startOffsetY = 0;

        function openModal() {
            modal.style.display = 'grid';
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            resetCropper();
        }

        function resetCropper() {
            originalFile = null;
            if (fileInput) fileInput.value = '';
            if (dropzone) dropzone.style.display = 'block';
            if (fileInfo) fileInfo.style.display = 'none';
            if (studio) studio.style.display = 'none';
            if (cropImg) cropImg.src = '';
            currentZoom = 1.0;
            if (zoomRange) zoomRange.value = '1';
            if (zoomLabel) zoomLabel.textContent = 'Zoom: 1.0x';
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Upload Photo';
            }
        }

        openBtns.forEach(btn => btn.addEventListener('click', openModal));
        closeBtns.forEach(btn => btn.addEventListener('click', closeModal));

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display !== 'none') closeModal();
        });

        if (dropzone && fileInput) {
            dropzone.addEventListener('click', () => fileInput.click());
            if (btnChangePhoto) btnChangePhoto.addEventListener('click', () => fileInput.click());

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('dragover');
                });
            });

            dropzone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files && files.length > 0) {
                    handleFileSelect(files[0]);
                }
            });

            fileInput.addEventListener('change', () => {
                if (fileInput.files && fileInput.files.length > 0) {
                    handleFileSelect(fileInput.files[0]);
                }
            });
        }

        function handleFileSelect(file) {
            if (!file || !file.type.startsWith('image/')) {
                alert('Please select a valid image file (JPEG, PNG, WEBP, or GIF).');
                return;
            }

            originalFile = file;
            if (fileNameEl) fileNameEl.textContent = file.name;
            if (fileSizeEl) fileSizeEl.textContent = `(${ (file.size / 1024).toFixed(1) } KB)`;

            const reader = new FileReader();
            reader.onload = (e) => {
                if (cropImg) {
                    cropImg.onload = () => {
                        imgNaturalW = cropImg.naturalWidth;
                        imgNaturalH = cropImg.naturalHeight;
                        currentZoom = 1.0;
                        if (zoomRange) zoomRange.value = '1';
                        if (zoomLabel) zoomLabel.textContent = 'Zoom: 1.0x';

                        if (dropzone) dropzone.style.display = 'none';
                        if (fileInfo) fileInfo.style.display = 'flex';
                        if (studio) studio.style.display = 'block';

                        alignPosition('center');
                    };
                    cropImg.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        }

        function getDimensions() {
            const baseScale = Math.max(viewportW / imgNaturalW, viewportH / imgNaturalH);
            const scale = baseScale * currentZoom;
            const displayW = imgNaturalW * scale;
            const displayH = imgNaturalH * scale;

            const minOffsetX = viewportW - displayW;
            const maxOffsetX = 0;
            const minOffsetY = viewportH - displayH;
            const maxOffsetY = 0;

            return { scale, displayW, displayH, minOffsetX, maxOffsetX, minOffsetY, maxOffsetY };
        }

        function clampOffsets() {
            const { minOffsetX, maxOffsetX, minOffsetY, maxOffsetY } = getDimensions();
            currentOffsetX = Math.min(maxOffsetX, Math.max(minOffsetX, currentOffsetX));
            currentOffsetY = Math.min(maxOffsetY, Math.max(minOffsetY, currentOffsetY));
        }

        function updateImageStyle() {
            if (!cropImg) return;
            const { displayW, displayH } = getDimensions();
            cropImg.style.width = `${displayW}px`;
            cropImg.style.height = `${displayH}px`;
            cropImg.style.left = `${currentOffsetX}px`;
            cropImg.style.top = `${currentOffsetY}px`;
        }

        function alignPosition(align) {
            const { displayW, displayH, minOffsetX, minOffsetY } = getDimensions();
            if (align === 'center') {
                currentOffsetX = (viewportW - displayW) / 2;
                currentOffsetY = (viewportH - displayH) / 2;
            } else if (align === 'top') {
                currentOffsetY = 0;
            } else if (align === 'bottom') {
                currentOffsetY = minOffsetY;
            } else if (align === 'left') {
                currentOffsetX = 0;
            } else if (align === 'right') {
                currentOffsetX = minOffsetX;
            }
            clampOffsets();
            updateImageStyle();
        }

        modal.querySelectorAll('.crop-preset-btn[data-align]').forEach(btn => {
            btn.addEventListener('click', () => {
                alignPosition(btn.getAttribute('data-align'));
            });
        });

        if (zoomRange) {
            zoomRange.addEventListener('input', () => {
                currentZoom = parseFloat(zoomRange.value);
                if (zoomLabel) zoomLabel.textContent = `Zoom: ${currentZoom.toFixed(1)}x`;
                clampOffsets();
                updateImageStyle();
            });
        }

        if (btnReset) {
            btnReset.addEventListener('click', () => {
                currentZoom = 1.0;
                if (zoomRange) zoomRange.value = '1';
                if (zoomLabel) zoomLabel.textContent = 'Zoom: 1.0x';
                alignPosition('center');
            });
        }

        if (viewport) {
            viewport.addEventListener('pointerdown', (e) => {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                startOffsetX = currentOffsetX;
                startOffsetY = currentOffsetY;
                viewport.setPointerCapture(e.pointerId);
            });

            viewport.addEventListener('pointermove', (e) => {
                if (!isDragging) return;
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                currentOffsetX = startOffsetX + dx;
                currentOffsetY = startOffsetY + dy;
                clampOffsets();
                updateImageStyle();
            });

            function endDrag(e) {
                if (!isDragging) return;
                isDragging = false;
                try { viewport.releasePointerCapture(e.pointerId); } catch (_) {}
            }

            viewport.addEventListener('pointerup', endDrag);
            viewport.addEventListener('pointercancel', endDrag);
        }

        function getCroppedBlob() {
            return new Promise((resolve, reject) => {
                try {
                    const { scale } = getDimensions();
                    const cropX = -currentOffsetX / scale;
                    const cropY = -currentOffsetY / scale;
                    const cropW = viewportW / scale;
                    const cropH = viewportH / scale;

                    const targetW = Math.min(1600, Math.max(800, Math.round(cropW)));
                    const targetH = Math.round(targetW * 3 / 4);
                    const canvas = document.createElement('canvas');
                    canvas.width = targetW;
                    canvas.height = targetH;
                    const ctx = canvas.getContext('2d');

                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(
                        cropImg,
                        cropX, cropY, cropW, cropH,
                        0, 0, targetW, targetH
                    );

                    const mimeType = (originalFile && originalFile.type) ? originalFile.type : 'image/jpeg';
                    canvas.toBlob((blob) => {
                        if (blob) resolve(blob);
                        else reject(new Error('Canvas export failed'));
                    }, mimeType.includes('png') ? 'image/png' : 'image/jpeg', 0.92);
                } catch (err) {
                    reject(err);
                }
            });
        }

        if (form) {
            form.addEventListener('submit', async (e) => {
                if (!originalFile) return;

                e.preventDefault();
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = 'Uploading...';
                }

                try {
                    const blob = await getCroppedBlob();
                    const croppedFile = new File([blob], originalFile.name, {
                        type: blob.type || originalFile.type,
                        lastModified: Date.now()
                    });

                    if (window.DataTransfer && fileInput) {
                        const dt = new DataTransfer();
                        dt.items.add(croppedFile);
                        fileInput.files = dt.files;
                        form.submit();
                    } else {
                        const formData = new FormData(form);
                        formData.set('image', croppedFile, originalFile.name);
                        await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        window.location.reload();
                    }
                } catch (err) {
                    console.warn('Fallback submit due to:', err);
                    form.submit();
                }
            });
        }
    }

    // ==========================================
    // Edit Slideshow Photo Position Studio
    // ==========================================
    const editModal = document.getElementById('modal-edit-slide');
    if (editModal) {
        const editForm = document.getElementById('form-edit-slide');
        const editTitleInput = document.getElementById('edit-slide-title');
        const editFilenameDisplay = document.getElementById('edit-slide-filename-display');
        const editViewport = document.getElementById('edit-crop-viewport');
        const editCropImg = document.getElementById('edit-crop-image-element');
        const editLoadingOverlay = document.getElementById('edit-loading-overlay');
        const editZoomRange = document.getElementById('edit-crop-zoom-range');
        const editZoomLabel = document.getElementById('edit-crop-zoom-label');
        const btnSubmitEdit = document.getElementById('btn-submit-edit-slide');
        const closeEditBtns = editModal.querySelectorAll('.btn-close-modal');

        let editBlob = null;
        let editFileName = 'slide_photo.jpg';
        let editNaturalW = 0;
        let editNaturalH = 0;
        let editCurrentZoom = 1.0;
        let editOffsetX = 0;
        let editOffsetY = 0;
        const editViewportW = 320;
        const editViewportH = 240;
        let editIsDragging = false;
        let editStartX = 0;
        let editStartY = 0;
        let editStartOffsetX = 0;
        let editStartOffsetY = 0;

        function openEditModal() {
            editModal.style.display = 'grid';
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            editModal.style.display = 'none';
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            if (editCropImg) editCropImg.src = '';
            editBlob = null;
        }

        closeEditBtns.forEach(btn => btn.addEventListener('click', closeEditModal));

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && editModal.style.display !== 'none') {
                closeEditModal();
            }
        });

        function getEditDimensions() {
            const baseScale = Math.max(editViewportW / editNaturalW, editViewportH / editNaturalH);
            const scale = baseScale * editCurrentZoom;
            const displayW = editNaturalW * scale;
            const displayH = editNaturalH * scale;

            const minOffsetX = editViewportW - displayW;
            const maxOffsetX = 0;
            const minOffsetY = editViewportH - displayH;
            const maxOffsetY = 0;

            return { scale, displayW, displayH, minOffsetX, maxOffsetX, minOffsetY, maxOffsetY };
        }

        function clampEditOffsets() {
            const { minOffsetX, maxOffsetX, minOffsetY, maxOffsetY } = getEditDimensions();
            editOffsetX = Math.min(maxOffsetX, Math.max(minOffsetX, editOffsetX));
            editOffsetY = Math.min(maxOffsetY, Math.max(minOffsetY, editOffsetY));
        }

        function updateEditImageStyle() {
            if (!editCropImg) return;
            const { displayW, displayH } = getEditDimensions();
            editCropImg.style.width = `${displayW}px`;
            editCropImg.style.height = `${displayH}px`;
            editCropImg.style.left = `${editOffsetX}px`;
            editCropImg.style.top = `${editOffsetY}px`;
        }

        function alignEditPosition(align) {
            const { displayW, displayH, minOffsetX, minOffsetY } = getEditDimensions();
            if (align === 'center') {
                editOffsetX = (editViewportW - displayW) / 2;
                editOffsetY = (editViewportH - displayH) / 2;
            } else if (align === 'top') {
                editOffsetY = 0;
            } else if (align === 'bottom') {
                editOffsetY = minOffsetY;
            } else if (align === 'left') {
                editOffsetX = 0;
            } else if (align === 'right') {
                editOffsetX = minOffsetX;
            }
            clampEditOffsets();
            updateEditImageStyle();
        }

        function applyZoom(newZoom) {
            editCurrentZoom = Math.max(1.0, Math.min(3.0, Math.round(newZoom * 100) / 100));
            if (editZoomRange) editZoomRange.value = editCurrentZoom;
            if (editZoomLabel) editZoomLabel.textContent = `Zoom: ${editCurrentZoom.toFixed(1)}x`;
            clampEditOffsets();
            updateEditImageStyle();
        }

        if (editZoomRange) {
            editZoomRange.addEventListener('input', () => {
                applyZoom(parseFloat(editZoomRange.value));
            });
        }

        if (editViewport) {
            editViewport.addEventListener('wheel', (e) => {
                e.preventDefault();
                const delta = e.deltaY < 0 ? 0.1 : -0.1;
                applyZoom(editCurrentZoom + delta);
            }, { passive: false });

            editViewport.addEventListener('pointerdown', (e) => {
                if (!editNaturalW) return;
                editIsDragging = true;
                editStartX = e.clientX;
                editStartY = e.clientY;
                editStartOffsetX = editOffsetX;
                editStartOffsetY = editOffsetY;
                editViewport.setPointerCapture(e.pointerId);
            });

            editViewport.addEventListener('pointermove', (e) => {
                if (!editIsDragging) return;
                const dx = e.clientX - editStartX;
                const dy = e.clientY - editStartY;
                editOffsetX = editStartOffsetX + dx;
                editOffsetY = editStartOffsetY + dy;
                clampEditOffsets();
                updateEditImageStyle();
            });

            function endEditDrag(e) {
                if (!editIsDragging) return;
                editIsDragging = false;
                try { editViewport.releasePointerCapture(e.pointerId); } catch (_) {}
            }
            editViewport.addEventListener('pointerup', endEditDrag);
            editViewport.addEventListener('pointercancel', endEditDrag);
        }

        document.querySelectorAll('.btn-edit-slide').forEach(btn => {
            btn.addEventListener('click', () => {
                const actionUrl = btn.getAttribute('data-action');
                const imgUrl = btn.getAttribute('data-image');
                const title = btn.getAttribute('data-title') || '';
                const filename = btn.getAttribute('data-filename') || 'slide-photo.jpg';
                const slideId = btn.getAttribute('data-id');

                if (editForm) {
                    editForm.action = actionUrl;
                    editForm.setAttribute('data-editing-slide-id', slideId);
                }
                if (editTitleInput) editTitleInput.value = title;
                if (editFilenameDisplay) editFilenameDisplay.textContent = filename;
                editFileName = filename;
                if (btnSubmitEdit) {
                    btnSubmitEdit.disabled = false;
                    btnSubmitEdit.textContent = 'Save Changes';
                }

                openEditModal();

                if (editLoadingOverlay) editLoadingOverlay.style.display = 'flex';
                editCurrentZoom = 1.0;
                if (editZoomRange) editZoomRange.value = '1';
                if (editZoomLabel) editZoomLabel.textContent = 'Zoom: 1.0x';

                if (imgUrl && imgUrl.startsWith('data:')) {
                    editCropImg.onload = () => {
                        if (editLoadingOverlay) editLoadingOverlay.style.display = 'none';
                        editNaturalW = editCropImg.naturalWidth;
                        editNaturalH = editCropImg.naturalHeight;
                        alignEditPosition('center');
                    };
                    editCropImg.src = imgUrl;
                } else {
                    fetch(imgUrl)
                        .then(res => {
                            if (!res.ok) throw new Error('Network error loading image');
                            return res.blob();
                        })
                        .then(blob => {
                            editBlob = blob;
                            const objUrl = URL.createObjectURL(blob);
                            editCropImg.onload = () => {
                                if (editLoadingOverlay) editLoadingOverlay.style.display = 'none';
                                editNaturalW = editCropImg.naturalWidth;
                                editNaturalH = editCropImg.naturalHeight;
                                alignEditPosition('center');
                            };
                            editCropImg.src = objUrl;
                        })
                        .catch(err => {
                            console.warn('Direct image loading fallback:', err);
                            if (editLoadingOverlay) editLoadingOverlay.style.display = 'none';
                            editCropImg.crossOrigin = 'anonymous';
                            editCropImg.onload = () => {
                                editNaturalW = editCropImg.naturalWidth;
                                editNaturalH = editCropImg.naturalHeight;
                                alignEditPosition('center');
                            };
                            editCropImg.src = imgUrl;
                        });
                }
            });
        });

        function getEditCroppedBlob() {
            return new Promise((resolve, reject) => {
                try {
                    const { scale } = getEditDimensions();
                    const cropX = -editOffsetX / scale;
                    const cropY = -editOffsetY / scale;
                    const cropW = editViewportW / scale;
                    const cropH = editViewportH / scale;

                    const targetW = Math.min(1600, Math.max(800, Math.round(cropW)));
                    const targetH = Math.round(targetW * (editViewportH / editViewportW));
                    const canvas = document.createElement('canvas');
                    canvas.width = targetW;
                    canvas.height = targetH;
                    const ctx = canvas.getContext('2d');

                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(
                        editCropImg,
                        cropX, cropY, cropW, cropH,
                        0, 0, targetW, targetH
                    );

                    const mimeType = (editBlob && editBlob.type) ? editBlob.type : 'image/jpeg';
                    canvas.toBlob((blob) => {
                        if (blob) resolve(blob);
                        else reject(new Error('Canvas export failed'));
                    }, mimeType.includes('png') ? 'image/png' : 'image/jpeg', 0.92);
                } catch (err) {
                    reject(err);
                }
            });
        }

        if (editForm) {
            editForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!editNaturalW) return;

                if (btnSubmitEdit) {
                    btnSubmitEdit.disabled = true;
                    btnSubmitEdit.textContent = 'Saving...';
                }

                try {
                    const blob = await getEditCroppedBlob();
                    const mimeType = (blob && blob.type) ? blob.type : 'image/jpeg';
                    const ext = mimeType.includes('png') ? '.png' : (mimeType.includes('webp') ? '.webp' : '.jpg');
                    const baseName = editFileName.replace(/\.[^/.]+$/, "");
                    const fileName = baseName + ext;

                    const croppedFile = new File([blob], fileName, {
                        type: mimeType,
                        lastModified: Date.now()
                    });

                    const formData = new FormData(editForm);
                    formData.set('image', croppedFile, fileName);

                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                                 document.querySelector('input[name="_token"]')?.value;

                    const response = await fetch(editForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json().catch(() => ({}));

                    if (response.ok && data.success) {
                        const slideId = editForm.getAttribute('data-editing-slide-id');
                        const targetRow = document.querySelector(`tr[data-slide-id="${slideId}"]`);
                        if (targetRow) {
                            const thumbImg = targetRow.querySelector('img');
                            if (thumbImg) thumbImg.src = data.image_url;
                            const editBtn = targetRow.querySelector('.btn-edit-slide');
                            if (editBtn) editBtn.setAttribute('data-image', data.image_url);
                        }

                        const heroSlide = document.querySelector(`.hero-slide[data-slide-id="${slideId}"]`);
                        if (heroSlide) {
                            heroSlide.src = data.image_url;
                        }

                        closeEditModal();
                        if (window.showAdminToast) {
                            window.showAdminToast('Photo position updated successfully.', 'success');
                        }
                    } else {
                        alert(data.error || 'Failed to update photo position.');
                    }
                } catch (err) {
                    console.error('Save failed:', err);
                    alert('Could not save adjusted position: ' + err.message);
                } finally {
                    if (btnSubmitEdit) {
                        btnSubmitEdit.disabled = false;
                        btnSubmitEdit.textContent = 'Save Changes';
                    }
                }
            });
        }
    }

    // ==========================================
    // Drag-and-Drop Sequence & AJAX Reorder Controls
    // ==========================================
    const tbody = document.getElementById('slideshow-list-tbody');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                      document.querySelector('input[name="_token"]')?.value;

    function reindexTableRows() {
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('.draggable-slide-row'));
        rows.forEach((r, idx) => {
            const badge = r.querySelector('.slide-order-badge');
            if (badge) badge.textContent = `#${idx + 1}`;
        });
    }

    function syncHeroSlideshowOrder(order) {
        const heroTrack = document.querySelector('.hero-slides-track');
        if (!heroTrack) return;

        order.forEach((id, idx) => {
            const img = heroTrack.querySelector(`img[data-slide-id="${id}"]`);
            if (img) {
                img.dataset.slideIndex = idx;
                heroTrack.appendChild(img);
            }
        });

        const heroImgs = heroTrack.querySelectorAll('.hero-slide');
        if (heroImgs.length > 0 && !Array.from(heroImgs).some(img => img.classList.contains('active'))) {
            heroImgs[0].classList.add('active');
        }
    }

    async function saveSlideOrder() {
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('.draggable-slide-row'));
        const order = rows.map(r => parseInt(r.getAttribute('data-slide-id'), 10)).filter(Boolean);

        if (order.length === 0) return;

        syncHeroSlideshowOrder(order);

        const reorderUrl = tbody.dataset.reorderUrl || '/admin/homepage/slides/reorder';

        try {
            const response = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ order })
            });

            const data = await response.json().catch(() => ({}));
            if (response.ok && data.success) {
                if (window.showAdminToast) {
                    window.showAdminToast(data.message || 'Sequence updated successfully.', 'success');
                }
            } else {
                if (window.showAdminToast) {
                    window.showAdminToast(data.error || data.message || 'Failed to update slideshow order.', 'error');
                }
            }
        } catch (err) {
            console.error('Save order failed:', err);
            if (window.showAdminToast) {
                window.showAdminToast('Failed to update sequence. Please check connection.', 'error');
            }
        }
    }

    if (tbody) {
        let draggedRow = null;

        tbody.addEventListener('dragstart', (e) => {
            const row = e.target.closest('.draggable-slide-row');
            if (!row) return;
            draggedRow = row;
            row.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', row.getAttribute('data-slide-id'));
        });

        tbody.addEventListener('dragend', () => {
            if (draggedRow) {
                draggedRow.classList.remove('is-dragging');
            }
            tbody.querySelectorAll('.draggable-slide-row').forEach(r => {
                r.classList.remove('drag-over-above', 'drag-over-below');
            });
            draggedRow = null;
        });

        tbody.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const row = e.target.closest('.draggable-slide-row');
            if (!row || !draggedRow || row === draggedRow) return;

            const rect = row.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;

            if (e.clientY < midpoint) {
                row.classList.add('drag-over-above');
                row.classList.remove('drag-over-below');
            } else {
                row.classList.add('drag-over-below');
                row.classList.remove('drag-over-above');
            }
        });

        tbody.addEventListener('dragleave', (e) => {
            const row = e.target.closest('.draggable-slide-row');
            if (!row) return;
            const rect = row.getBoundingClientRect();
            if (e.clientY < rect.top || e.clientY > rect.bottom || e.clientX < rect.left || e.clientX > rect.right) {
                row.classList.remove('drag-over-above', 'drag-over-below');
            }
        });

        tbody.addEventListener('drop', (e) => {
            e.preventDefault();
            const targetRow = e.target.closest('.draggable-slide-row');
            if (!targetRow || !draggedRow || targetRow === draggedRow) return;

            const isAbove = targetRow.classList.contains('drag-over-above');
            targetRow.classList.remove('drag-over-above', 'drag-over-below');

            if (isAbove) {
                tbody.insertBefore(draggedRow, targetRow);
            } else {
                tbody.insertBefore(draggedRow, targetRow.nextSibling);
            }

            reindexTableRows();
            saveSlideOrder();
        });
    }

    // Delete Slide (Zero Refresh AJAX with in-place removal)
    document.querySelectorAll('.btn-delete-slide').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const row = btn.closest('tr');
            const actionUrl = btn.getAttribute('data-action');
            const title = btn.getAttribute('data-confirm-title') || 'Delete Slideshow Photo?';
            const message = btn.getAttribute('data-confirm-message') || 'This photo will be permanently removed.';
            const confirmBtn = btn.getAttribute('data-confirm-btn') || 'Delete Photo';

            if (window.openAdminConfirm) {
                window.openAdminConfirm({
                    title,
                    message,
                    actionUrl,
                    method: 'DELETE',
                    confirmText: confirmBtn,
                    buttonClass: 'button-danger',
                    onConfirm: async () => {
                        try {
                            const res = await fetch(actionUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ _method: 'DELETE' })
                            });

                            const data = await res.json();
                            if (res.ok && data.success) {
                                row.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'scale(0.95)';
                                setTimeout(() => {
                                    const slideId = row.getAttribute('data-slide-id');
                                    row.remove();
                                    const heroSlide = document.querySelector(`.hero-slide[data-slide-id="${slideId}"]`);
                                    if (heroSlide) heroSlide.remove();
                                    reindexTableRows();
                                    if (window.showAdminToast) {
                                        window.showAdminToast('Slideshow photo deleted.', 'success');
                                    }
                                }, 250);
                            } else {
                                if (window.showAdminToast) {
                                    window.showAdminToast(data.error || 'Failed to delete photo.', 'error');
                                }
                            }
                        } catch (err) {
                            console.error('Delete error:', err);
                            if (window.showAdminToast) {
                                window.showAdminToast('Error deleting photo. Please try again.', 'error');
                            }
                        }
                    }
                });
            }
        });
    });
}
