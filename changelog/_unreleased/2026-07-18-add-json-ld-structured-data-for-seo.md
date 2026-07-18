---
title: Improve catalogue data for SEO, structured data and analytics
issue: NEXT-00000
author: Jason Woods
author_email: jjwoods@gmail.com
author_github: jjwoods17071
---

# Core
* Added console command `product:catalogue:audit` reporting products missing GTIN/EAN, brand, meta description or a cover image, so catalogue data gaps that hurt SEO and product feeds can be fixed at the source.

# Storefront
* Added `storefront/layout/structured-data.html.twig` emitting sitewide `Organization` and `WebSite` (sitelinks search box) JSON-LD.
* Added `storefront/page/product-detail/structured-data.html.twig` emitting consolidated `Product` and `BreadcrumbList` JSON-LD (name, sku, gtin13, mpn, brand, image, description, offers with price/currency/availability).
* Added block `layout_head_json_ld` in `storefront/layout/meta.html.twig` so page types and plugins can extend the emitted JSON-LD.
* Changed `component/product/card/box-standard.html.twig` to enrich the Google Analytics `data-product-information` payload with `item_id`, `item_brand` and `item_category`.
* Changed the Google Analytics `view_item` event to include `item_brand` and `item_category`.
* Changed the storefront `robots.txt` to disallow the internal `/search` and `/suggest` routes to preserve crawl budget.
