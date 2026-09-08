<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Industry sectors, each mapped to the bearing families typically specified in
 * that sector's rotating equipment.
 *
 * The mapping is to FAMILIES, not to individual catalogue records. The
 * imported data tags every product with the same two industry ids, which is a
 * seeding artefact rather than curation, so presenting per-industry product
 * lists from it would be presenting noise as knowledge. Family-level guidance
 * is defensible and still useful to an engineer narrowing a selection.
 */
final class Industries
{
    /** @var array<string,array<string,mixed>> */
    private const MAP = [
        'steel' => [
            'labelFa' => 'فولاد و ذوب',
            'labelEn' => 'Steel & Metals',
            'noteFa' => 'نورد گرم و سرد، جرثقیل پاتیل و فن‌های دمنده؛ بار سنگین همراه با دمای بالا.',
            'noteEn' => 'Hot and cold rolling, ladle cranes and blower fans — heavy load combined with high temperature.',
            'families' => ['spherical-roller', 'cylindrical-roller', 'tapered-roller', 'bearing-housing'],
        ],
        'mining' => [
            'labelFa' => 'معدن و فرآوری مواد',
            'labelEn' => 'Mining & Mineral Processing',
            'noteFa' => 'سرندهای لرزان، سنگ‌شکن و نوار نقاله؛ بار ضربه‌ای و آلودگی شدید.',
            'noteEn' => 'Vibrating screens, crushers and conveyors — shock loading and heavy contamination.',
            'families' => ['spherical-roller', 'toroidal-roller', 'bearing-unit', 'oil-seal'],
        ],
        'cement' => [
            'labelFa' => 'سیمان و آهک',
            'labelEn' => 'Cement & Lime',
            'noteFa' => 'کوره دوار، آسیاب گلوله‌ای و الواتور؛ دمای بالا و گرد و غبار ساینده.',
            'noteEn' => 'Rotary kilns, ball mills and elevators — elevated temperature and abrasive dust.',
            'families' => ['spherical-roller', 'deep-groove-ball', 'bearing-housing', 'lubricant'],
        ],
        'oil-gas' => [
            'labelFa' => 'نفت و گاز',
            'labelEn' => 'Oil & Gas',
            'noteFa' => 'پمپ‌های فرآیندی، کمپرسور و درایو؛ کارکرد پیوسته و الزامات قابلیت اطمینان.',
            'noteEn' => 'Process pumps, compressors and drives — continuous duty and strict reliability requirements.',
            'families' => ['angular-contact-ball', 'cylindrical-roller', 'deep-groove-ball'],
        ],
        'petrochemical' => [
            'labelFa' => 'پتروشیمی',
            'labelEn' => 'Petrochemical',
            'noteFa' => 'اکسترودر، میکسر و پمپ سیالات خورنده؛ نیاز به آب‌بندی مقاوم شیمیایی.',
            'noteEn' => 'Extruders, mixers and corrosive-fluid pumps — chemically resistant sealing required.',
            'families' => ['cylindrical-roller', 'oil-seal', 'angular-contact-ball'],
        ],
        'power' => [
            'labelFa' => 'نیروگاه و تولید برق',
            'labelEn' => 'Power Generation',
            'noteFa' => 'توربین، ژنراتور و فن‌های خنک‌کننده؛ دور بالا و پایداری حرارتی.',
            'noteEn' => 'Turbines, generators and cooling fans — high speed and thermal stability.',
            'families' => ['deep-groove-ball', 'angular-contact-ball', 'cylindrical-roller'],
        ],
        'pumps' => [
            'labelFa' => 'پمپ‌های صنعتی',
            'labelEn' => 'Industrial Pumps',
            'noteFa' => 'پمپ گریز از مرکز و دنده‌ای؛ ترکیب بار شعاعی و محوری.',
            'noteEn' => 'Centrifugal and gear pumps — combined radial and axial loading.',
            'families' => ['deep-groove-ball', 'angular-contact-ball', 'oil-seal'],
        ],
        'gearboxes' => [
            'labelFa' => 'گیربکس و انتقال قدرت',
            'labelEn' => 'Gearboxes & Power Transmission',
            'noteFa' => 'گیربکس صنعتی و کاهنده‌ها؛ بار محوری ناشی از دنده مارپیچ.',
            'noteEn' => 'Industrial gearboxes and reducers — axial reaction from helical gearing.',
            'families' => ['tapered-roller', 'cylindrical-roller', 'deep-groove-ball'],
        ],
        'heavy-machinery' => [
            'labelFa' => 'ماشین‌آلات سنگین',
            'labelEn' => 'Heavy Machinery',
            'noteFa' => 'جرثقیل، بیل مکانیکی و تجهیزات راهسازی؛ بار متغیر و ضربه.',
            'noteEn' => 'Cranes, excavators and earthmoving equipment — variable load and impact.',
            'families' => ['spherical-roller', 'tapered-roller', 'needle-roller'],
        ],
        'automotive' => [
            'labelFa' => 'خودرو و ناوگان سنگین',
            'labelEn' => 'Automotive & Heavy Fleet',
            'noteFa' => 'توپی چرخ، گیربکس و دیفرانسیل؛ فضای محدود و عمر تعریف‌شده.',
            'noteEn' => 'Wheel hubs, gearboxes and differentials — constrained space and defined service life.',
            'families' => ['tapered-roller', 'deep-groove-ball', 'needle-roller'],
        ],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::MAP);
    }

    /** @return array<string,mixed> */
    public static function get(string $id): array
    {
        return self::MAP[$id] ?? [];
    }

    public static function label(string $id, string $language): string
    {
        $meta = self::get($id);

        return (string) ($meta[$language === 'fa' ? 'labelFa' : 'labelEn'] ?? $id);
    }

    public static function note(string $id, string $language): string
    {
        $meta = self::get($id);

        return (string) ($meta[$language === 'fa' ? 'noteFa' : 'noteEn'] ?? '');
    }

    /** @return list<string> */
    public static function families(string $id): array
    {
        $meta = self::get($id);
        $families = $meta['families'] ?? [];

        return is_array($families) ? array_values(array_filter($families, 'is_string')) : [];
    }

    /** @return list<array{id:string,label:string,note:string,families:list<string>}> */
    public static function all(string $language): array
    {
        $out = [];
        foreach (self::keys() as $id) {
            $out[] = [
                'id' => $id,
                'label' => self::label($id, $language),
                'note' => self::note($id, $language),
                'families' => self::families($id),
            ];
        }

        return $out;
    }
}
