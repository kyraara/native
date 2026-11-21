import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';
import { publicOrderForm, publicTracking, publicCatalog, publicNavigation, publicMarquee } from './public.js';
import { adminWorkspace } from './admin.js';
import { publicHero } from './hero.js';
import { cuyAiChat } from './chat.js';
import { serviceFormBuilder } from './service-builder.js';
import { adminOrderForm } from './admin-order-form.js';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.plugin(intersect);
Alpine.data('publicOrderForm', publicOrderForm);
Alpine.data('publicTracking', publicTracking);
Alpine.data('publicCatalog', publicCatalog);
Alpine.data('publicNavigation', publicNavigation);
Alpine.data('adminWorkspace', adminWorkspace);
Alpine.data('publicHero', publicHero);
Alpine.data('publicMarquee', publicMarquee);
Alpine.data('cuyAiChat', cuyAiChat);
Alpine.data('serviceFormBuilder', serviceFormBuilder);
Alpine.data('adminOrderForm', adminOrderForm);

Alpine.start();
