// A4 page width in CSS pixels (210 mm at 96 dpi).
const PAGE_WIDTH = 794;

/**
 * Scales a full-size A4 iframe render down to its container width.
 */
export default () => ({
    scale: 0.3,

    init() {
        const resize = () => {
            this.scale = this.$el.clientWidth / PAGE_WIDTH;
        };

        resize();
        new ResizeObserver(resize).observe(this.$el);
    },
});
