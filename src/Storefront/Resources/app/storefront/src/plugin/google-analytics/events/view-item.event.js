import AnalyticsEvent from 'src/plugin/google-analytics/analytics-event';

export default class ViewItemEvent extends AnalyticsEvent
{
    supports(controllerName, actionName) {
        return controllerName === 'product' && actionName === 'index';
    }

    execute() {
        if (!this.active) {
            return;
        }

        const productItemElement = document.querySelector('[itemtype="https://schema.org/Product"]');
        if (!productItemElement) {
            console.warn('[Google Analytics Plugin] Product itemtype ([itemtype="https://schema.org/Product"]) could not be found in document.');

            return;
        }

        const productIdElement = productItemElement.querySelector('meta[itemprop="productID"]');
        const productNameElement = productItemElement.querySelector('[itemprop="name"]');
        if (!productIdElement || !productNameElement) {
            console.warn('[Google Analytics Plugin] Product ID (meta[itemprop="productID"]) or product name ([itemprop="name"]) could not be found within product scope.');

            return;
        }

        const productId = productIdElement.content;
        const productName = productNameElement.textContent.trim();
        if (!productId || !productName) {
            console.warn('[Google Analytics Plugin] Product ID or product name is empty, do not track page view.');

            return;
        }

        const item = {
            'id': productId,
            'name': productName,
        };

        // Enrich with brand (schema.org/Brand microdata) when available
        const brandElement = productItemElement.querySelector('[itemprop="brand"] [itemprop="name"]');
        if (brandElement && brandElement.content) {
            item.item_brand = brandElement.content;
        }

        // Enrich with the deepest breadcrumb category as item_category when available
        const breadcrumbNameElements = document.querySelectorAll('.breadcrumb-item [itemprop="name"]');
        if (breadcrumbNameElements.length) {
            const category = breadcrumbNameElements[breadcrumbNameElements.length - 1].textContent.trim();
            if (category) {
                item.item_category = category;
            }
        }

        gtag('event', 'view_item', {
            'items': [item],
        });
    }
}
