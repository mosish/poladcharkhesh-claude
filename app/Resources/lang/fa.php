<?php

declare(strict_types=1);

/**
 * Persian interface strings.
 *
 * Editorial copy (hero, about, industries and so on) is CMS-managed and lives
 * in the data store. This file holds only the fixed interface chrome: labels,
 * actions and engineering terminology.
 *
 * The Persian here is written as Persian, not translated from the English
 * file. Where a term has an established usage in the Iranian bearing trade,
 * that usage wins over a literal rendering.
 */

return [
    'meta' => [
        'skipToContent' => 'رفتن به محتوای اصلی',
    ],

    'nav' => [
        'home' => 'صفحهٔ نخست',
        'about' => 'دربارهٔ ما',
        'catalog' => 'کاتالوگ فنی',
        'tools' => 'ابزار مهندسی',
        'industries' => 'صنایع',
        'whyUs' => 'چرا ما',
        'support' => 'پشتیبانی فنی',
        'contact' => 'تماس و استعلام',
        'menu' => 'فهرست',
        'closeMenu' => 'بستن فهرست',
        'openMenu' => 'باز کردن فهرست',
        'admin' => 'ورود مدیریت',
    ],

    'action' => [
        'browseCatalog' => 'مشاهدهٔ کاتالوگ فنی',
        'contactEngineering' => 'گفت‌وگو با واحد فنی',
        'viewAll' => 'مشاهدهٔ همه',
        'viewDetails' => 'مشخصات کامل',
        'enquire' => 'استعلام فنی',
        'call' => 'تماس تلفنی',
        'whatsapp' => 'واتس‌اپ',
        'datasheet' => 'دیتاشیت فنی',
        'backToTop' => 'بازگشت به بالا',
        'search' => 'جست‌وجو',
        'switchLanguage' => 'English',
        'openTools' => 'ورود به ابزار مهندسی',
        'exploreFamily' => 'مشاهدهٔ این خانواده',
    ],

    'hero' => [
        'examplesLabel' => 'نمونهٔ جست‌وجو',
        'searchLabel' => 'جست‌وجوی شمارهٔ فنی، ابعاد یا کاربرد',
        'noResults' => 'موردی با این مشخصات در کاتالوگ یافت نشد. برای استعلام قطعات خارج از کاتالوگ تماس بگیرید.',
    ],

    'facts' => [
        'referencesLabel' => 'شمارهٔ فنی مستندشده در کاتالوگ',
        'familiesLabel' => 'خانوادهٔ فنی بلبرینگ و رولبرینگ',
        'sourcedLabel' => 'مشخصات مستند به کاتالوگ سازندگان',
        'hoursLabel' => 'ساعت پاسخ‌گویی کارشناسی',
    ],

    'instrument' => [
        'title' => 'شبیه‌ساز فنی بلبرینگ',
        'modeAssembly' => 'مجموعه',
        'modeCutaway' => 'برش',
        'modeDimensions' => 'ابعاد',
        'modeThermal' => 'حرارتی',
        'speedLabel' => 'سرعت دورانی',
        'tempLabel' => 'دمای تخمینی',
        'boreLabel' => 'قطر داخلی',
        'odLabel' => 'قطر خارجی',
        'widthLabel' => 'پهنا',
        'cageLabel' => 'قفسه',
        'ballsLabel' => 'ساچمه',
        'noCanvas' => 'نمایش گرافیکی در این مرورگر پشتیبانی نمی‌شود؛ مشخصات فنی در جدول زیر در دسترس است.',
        'thermalDisclaimer' => 'تخمین مهندسی — عدد راهنما، نه دمای تضمین‌شده.',
        'thermalNote' => 'دمای واقعی کارکرد به بار وارده، نوع و مقدار روان‌کار، دمای محیط، طرح بلبرینگ، تلرانس نشست روی شفت و پوسته، شرایط تهویه و نوع قفسه بستگی دارد. برای انتخاب دقیق با واحد فنی تماس بگیرید.',
        'rotationHint' => 'برای تغییر سرعت، نوار زیر را جابه‌جا کنید.',
    ],

    'spec' => [
        'bore' => 'قطر داخلی',
        'outerDiameter' => 'قطر خارجی',
        'width' => 'پهنا',
        'dimensions' => 'ابعاد اصلی',
        'weight' => 'وزن',
        'dynamicLoad' => 'ظرفیت بار دینامیکی',
        'staticLoad' => 'ظرفیت بار استاتیکی',
        'speedGrease' => 'حد سرعت (گریس)',
        'speedOil' => 'حد سرعت (روغن)',
        'referenceSpeed' => 'سرعت مرجع حرارتی',
        'cage' => 'جنس قفسه',
        'sealing' => 'آب‌بندی',
        'clearance' => 'لقی داخلی',
        'family' => 'خانوادهٔ فنی',
        'brands' => 'برندهای قابل تأمین',
        'notSpecified' => 'اعلام‌نشده',
        'unitMm' => 'میلی‌متر',
        'unitKn' => 'کیلونیوتن',
        'unitRpm' => 'دور/دقیقه',
        'unitKg' => 'کیلوگرم',
    ],

    'product' => [
        'inStock' => 'موجود در انبار',
        'enquireStock' => 'استعلام موجودی',
        'sourced' => 'مستند به کاتالوگ سازنده',
        'sourcedShort' => 'مستند',
        'unverified' => 'در انتظار تأیید مرجع',
        'dimensionsShort' => 'd × D × B',
    ],

    'catalog' => [
        'familiesTitle' => 'خانواده‌های فنی',
        'countSuffix' => 'شمارهٔ فنی',
        'featuredTitle' => 'نمونه‌هایی از کاتالوگ',
    ],

    'contact' => [
        'phone' => 'تلفن دفتر مرکزی',
        'mobile' => 'همراه و واتس‌اپ',
        'address' => 'نشانی',
        'hours' => 'ساعات کاری',
        'email' => 'رایانامه',
        'mapLink' => 'مشاهده روی نقشه',
    ],

    'footer' => [
        'navigation' => 'بخش‌های سایت',
        'families' => 'خانواده‌های فنی',
        'contact' => 'تماس',
        'rights' => 'همهٔ حقوق محفوظ است.',
    ],

    'state' => [
        'loading' => 'در حال بارگذاری…',
        'empty' => 'موردی برای نمایش وجود ندارد.',
        'error' => 'دریافت اطلاعات ممکن نشد.',
        'retry' => 'تلاش دوباره',
        'unavailableTitle' => 'اطلاعات در دسترس نیست',
        'unavailableBody' => 'در حال حاضر امکان خواندن پایگاه اطلاعات فنی وجود ندارد. لطفاً چند لحظه بعد دوباره تلاش کنید یا مستقیم تماس بگیرید.',
        'notFoundTitle' => 'صفحه یافت نشد',
        'notFoundBody' => 'نشانی درخواست‌شده در دسترس نیست. می‌توانید از کاتالوگ فنی شروع کنید یا شمارهٔ فنی مورد نظر را جست‌وجو کنید.',
        'serverErrorTitle' => 'خطای غیرمنتظره',
        'serverErrorBody' => 'مشکلی در پردازش درخواست پیش آمد. اگر ادامه داشت، لطفاً اطلاع دهید.',
    ],
];
