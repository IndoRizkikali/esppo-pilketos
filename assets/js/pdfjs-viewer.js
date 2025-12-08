/**
 * e-SPPO PDF.js Viewer Helper
 * Menginisialisasi PDF.js sebagai viewer utama dengan fallback ke iframe browser
 */

const ESPPOPDFViewer = {
    pdfjsLib: null,
    currentPdf: null,
    currentPage: 1,
    totalPages: 0,
    scale: 1.0,
    container: null,
    canvas: null,
    ctx: null,
    rendering: false,
    pendingPage: null,

    /**
     * Inisialisasi viewer
     * @param {string} containerId - ID container untuk viewer
     * @param {string} pdfUrl - URL file PDF
     * @param {object} options - Opsi tambahan
     */
    init: async function(containerId, pdfUrl, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Container tidak ditemukan:', containerId);
            return false;
        }

        // Cek apakah PDF.js tersedia
        if (typeof pdfjsLib === 'undefined') {
            console.warn('PDF.js tidak tersedia, menggunakan fallback iframe');
            this.showFallback(pdfUrl, options);
            return false;
        }

        this.pdfjsLib = pdfjsLib;
        
        // Set worker path
        const workerSrc = options.workerSrc || '../../vendor/clean-composer-packages/pdf-js/build/pdf.worker.mjs';
        this.pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

        try {
            // Render container
            this.renderViewerUI(options);
            
            // Load PDF
            const loadingTask = this.pdfjsLib.getDocument(pdfUrl);
            this.currentPdf = await loadingTask.promise;
            this.totalPages = this.currentPdf.numPages;
            
            // Update info halaman
            this.updatePageInfo();
            
            // Render halaman pertama
            await this.renderPage(1);
            
            return true;
        } catch (error) {
            console.error('Gagal memuat PDF dengan PDF.js:', error);
            this.showFallback(pdfUrl, options);
            return false;
        }
    },

    /**
     * Render UI viewer
     */
    renderViewerUI: function(options) {
        const height = options.height || 800;
        
        this.container.innerHTML = `
            <div class="pdfjs-viewer-wrapper" style="background: #525659; border-radius: 4px; overflow: hidden;">
                <div class="pdfjs-toolbar" style="background: #323639; padding: 8px 12px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div class="pdfjs-nav-buttons" style="display: flex; gap: 5px;">
                        <button type="button" class="btn btn-sm btn-secondary pdfjs-prev" title="Halaman Sebelumnya">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary pdfjs-next" title="Halaman Berikutnya">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="pdfjs-page-info" style="color: #fff; font-size: 14px;">
                        <span class="pdfjs-current-page">1</span> / <span class="pdfjs-total-pages">1</span>
                    </div>
                    <div class="pdfjs-zoom-controls" style="display: flex; gap: 5px; margin-left: auto;">
                        <button type="button" class="btn btn-sm btn-secondary pdfjs-zoom-out" title="Perkecil">
                            <i class="fas fa-search-minus"></i>
                        </button>
                        <span class="pdfjs-zoom-level" style="color: #fff; min-width: 50px; text-align: center; line-height: 31px;">100%</span>
                        <button type="button" class="btn btn-sm btn-secondary pdfjs-zoom-in" title="Perbesar">
                            <i class="fas fa-search-plus"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary pdfjs-fit-width" title="Sesuaikan Lebar">
                            <i class="fas fa-arrows-alt-h"></i>
                        </button>
                    </div>
                </div>
                <div class="pdfjs-canvas-container" style="height: ${height}px; overflow: auto; text-align: center; padding: 10px;">
                    <canvas class="pdfjs-canvas" style="box-shadow: 0 2px 10px rgba(0,0,0,0.3);"></canvas>
                </div>
            </div>
        `;

        this.canvas = this.container.querySelector('.pdfjs-canvas');
        this.ctx = this.canvas.getContext('2d');

        // Event listeners
        this.container.querySelector('.pdfjs-prev').addEventListener('click', () => this.prevPage());
        this.container.querySelector('.pdfjs-next').addEventListener('click', () => this.nextPage());
        this.container.querySelector('.pdfjs-zoom-in').addEventListener('click', () => this.zoomIn());
        this.container.querySelector('.pdfjs-zoom-out').addEventListener('click', () => this.zoomOut());
        this.container.querySelector('.pdfjs-fit-width').addEventListener('click', () => this.fitWidth());

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            if (e.key === 'ArrowLeft') this.prevPage();
            if (e.key === 'ArrowRight') this.nextPage();
        });
    },

    /**
     * Render halaman PDF
     */
    renderPage: async function(pageNum) {
        if (this.rendering) {
            this.pendingPage = pageNum;
            return;
        }

        this.rendering = true;
        this.currentPage = pageNum;

        try {
            const page = await this.currentPdf.getPage(pageNum);
            const viewport = page.getViewport({ scale: this.scale });

            this.canvas.height = viewport.height;
            this.canvas.width = viewport.width;

            const renderContext = {
                canvasContext: this.ctx,
                viewport: viewport
            };

            await page.render(renderContext).promise;
            this.updatePageInfo();
        } catch (error) {
            console.error('Gagal render halaman:', error);
        }

        this.rendering = false;

        if (this.pendingPage !== null) {
            const pending = this.pendingPage;
            this.pendingPage = null;
            this.renderPage(pending);
        }
    },

    /**
     * Update info halaman
     */
    updatePageInfo: function() {
        this.container.querySelector('.pdfjs-current-page').textContent = this.currentPage;
        this.container.querySelector('.pdfjs-total-pages').textContent = this.totalPages;
        this.container.querySelector('.pdfjs-zoom-level').textContent = Math.round(this.scale * 100) + '%';
        
        // Enable/disable nav buttons
        this.container.querySelector('.pdfjs-prev').disabled = this.currentPage <= 1;
        this.container.querySelector('.pdfjs-next').disabled = this.currentPage >= this.totalPages;
    },

    prevPage: function() {
        if (this.currentPage > 1) {
            this.renderPage(this.currentPage - 1);
        }
    },

    nextPage: function() {
        if (this.currentPage < this.totalPages) {
            this.renderPage(this.currentPage + 1);
        }
    },

    zoomIn: function() {
        this.scale = Math.min(this.scale + 0.25, 3.0);
        this.renderPage(this.currentPage);
    },

    zoomOut: function() {
        this.scale = Math.max(this.scale - 0.25, 0.5);
        this.renderPage(this.currentPage);
    },

    fitWidth: function() {
        if (!this.currentPdf) return;
        
        this.currentPdf.getPage(this.currentPage).then((page) => {
            const containerWidth = this.container.querySelector('.pdfjs-canvas-container').clientWidth - 40;
            const viewport = page.getViewport({ scale: 1.0 });
            this.scale = containerWidth / viewport.width;
            this.renderPage(this.currentPage);
        });
    },

    /**
     * Tampilkan fallback iframe
     */
    showFallback: function(pdfUrl, options) {
        const height = options.height || 800;
        this.container.innerHTML = `
            <div class="alert alert-warning mb-2">
                <i class="fas fa-exclamation-triangle"></i> 
                PDF.js tidak tersedia. Menggunakan viewer bawaan browser.
            </div>
            <iframe src="${pdfUrl}" height="${height}" width="100%" style="border:none;" allowfullscreen></iframe>
        `;
    }
};
