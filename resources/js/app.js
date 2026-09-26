import './bootstrap';

// Livewire is bundled manually (layout uses @livewireScriptConfig) so Alpine
// components can be registered before it starts. Livewire already ships the
// Alpine collapse, sort, intersect, anchor and mask plugins.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import exporter from './components/exporter';
import resumeThumbnail from './components/resume-thumbnail';
import previewFrame from './components/preview-frame';
import { markNativeShell, registerServiceWorker } from './native';

markNativeShell();
registerServiceWorker();

Alpine.data('exporter', exporter);
Alpine.data('resumeThumbnail', resumeThumbnail);
Alpine.data('previewFrame', previewFrame);

Livewire.start();
