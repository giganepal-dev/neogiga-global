# Multi-Language Plan

Supported architecture languages: English, Hindi, Nepali, Bangla, Burmese, Arabic, French, German, Spanish, Portuguese, Japanese, Chinese, Korean.

## Tables Needed

- `product_translations`
- `brand_translations`
- `manufacturer_translations`
- `seller_store_translations`
- `lms_course_translations`
- `project_translations`
- `article_translations`
- `faq_translations`

## Rule

Never duplicate products for language. Use translation tables keyed by canonical entity ID and locale.

