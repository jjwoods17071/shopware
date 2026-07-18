---
title: Add JSON-LD structured data for products and the storefront
issue: NEXT-00000
author: Jason Woods
author_email: jjwoods@gmail.com
author_github: jjwoods17071
---

# Storefront
* Added `storefront/layout/structured-data.html.twig` emitting sitewide `Organization` and `WebSite` (sitelinks search box) JSON-LD.
* Added `storefront/page/product-detail/structured-data.html.twig` emitting a consolidated `Product` JSON-LD block (name, sku, gtin13, mpn, brand, image, description, offers with price/currency/availability).
* Added block `layout_head_json_ld` in `storefront/layout/meta.html.twig` so page types and plugins can extend the emitted JSON-LD.
