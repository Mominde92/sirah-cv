// Preview document width in CSS pixels: A4 sheet (210 mm at 96 dpi) + canvas padding.
const DOC_WIDTH = 794 + 48;
const MIN_HEIGHT = 1123 + 48;

/**
 * Live preview for the editor. New HTML is written into the iframe document
 * instead of reloading it, so updates do not flash; the iframe grows to the
 * document height and is scaled to fit the available width.
 */
export default (initialHtml) => ({
    html: initialHtml,
    scale: 1,
    zoom: 'fit',
    height: MIN_HEIGHT,

    init() {
        this.$nextTick(() => {
            this.write(this.html);
            this.fit();
        });

        new ResizeObserver(() => this.fit()).observe(this.$refs.viewport);
        this.$watch('html', (html) => this.write(html));
        this.$watch('zoom', () => this.fit());
    },

    get docWidth() {
        return DOC_WIDTH;
    },

    fit() {
        const available = this.$refs.viewport.clientWidth - 24;
        const fitScale = Math.max(0.3, Math.min(1.2, available / DOC_WIDTH));

        this.scale = this.zoom === 'fit' ? fitScale : Number(this.zoom);
    },

    write(html) {
        const frame = this.$refs.frame;
        const doc = frame.contentDocument;

        if (!doc) return;

        doc.open();
        doc.write(html);
        doc.close();

        const measure = () => {
            this.height = Math.max(MIN_HEIGHT, doc.documentElement.scrollHeight);
        };

        measure();
        doc.fonts?.ready.then(measure);
    },
});
