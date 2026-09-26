import { openDownload } from '../native';

/**
 * Queues a PDF/DOCX export, polls its status and starts the download.
 * Works the same whether the queue runs asynchronously or synchronously.
 */
export default (storeUrl, format) => ({
    busy: false,
    error: null,

    async run() {
        if (this.busy) return;

        this.busy = true;
        this.error = null;

        try {
            const { data } = await window.axios.post(storeUrl, { format });
            const download = await this.waitFor(data.status_url);

            openDownload(download);
        } catch (error) {
            const response = error.response?.data;
            this.error = response?.message ?? error.message;

            if (response?.upgrade_url) {
                this.$dispatch('upgrade-required', response);
            }
        } finally {
            this.busy = false;
        }
    },

    async waitFor(statusUrl) {
        const started = Date.now();

        while (Date.now() - started < 120000) {
            const { data } = await window.axios.get(statusUrl);

            if (data.status === 'completed') return data.download_url;
            if (data.status === 'failed') throw new Error(data.error);

            await new Promise((resolve) => setTimeout(resolve, 1200));
        }

        throw new Error(document.documentElement.lang === 'ar' ? 'استغرق التصدير وقتاً أطول من المتوقع.' : 'The export is taking longer than expected.');
    },
});
