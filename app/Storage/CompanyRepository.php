<?php

declare(strict_types=1);

namespace App\Storage;

/**
 * The authoritative company record.
 *
 * Every phone number, address and opening hour on the site and in the
 * structured data resolves through here. Nothing is hardcoded in a template.
 */
final class CompanyRepository extends DocumentRepository
{
    protected function collection(): string
    {
        return 'company';
    }

    /** @return array<string,mixed> */
    protected function defaults(): array
    {
        return [
            'nameFa' => '', 'nameEn' => '',
            'legalNameFa' => '', 'legalNameEn' => '',
            'sloganFa' => '', 'sloganEn' => '',
            'website' => '', 'email' => '',
            'primaryPhone' => '', 'primaryPhoneDisplayFa' => '', 'primaryPhoneDisplayEn' => '', 'primaryPhoneTel' => '',
            'landlinePhone' => '', 'landlinePhoneDisplayFa' => '', 'landlinePhoneDisplayEn' => '', 'landlinePhoneTel' => '',
            'whatsappNumber' => '', 'whatsappUrl' => '',
            'addressFa' => '', 'addressEn' => '',
            'cityFa' => '', 'cityEn' => '',
            'workingHoursFa' => '', 'workingHoursEn' => '',
            'workingHoursShortFa' => '', 'workingHoursShortEn' => '',
            'maps' => ['google' => '', 'neshan' => '', 'balad' => ''],
        ];
    }

    /**
     * Build a WhatsApp link, optionally pre-filled with a product enquiry.
     *
     * @param array{code?:string,name?:string,dimensions?:string,brands?:list<string>} $product
     */
    public function whatsappUrl(string $language = 'fa', array $product = []): string
    {
        $base = $this->value('whatsappUrl');
        if ($base === '') {
            return '';
        }
        if ($product === []) {
            return $base;
        }

        $isFa = $language === 'fa';
        $lines = [];

        if ($isFa) {
            $lines[] = 'با سلام، استعلام مشخصات و موجودی قطعه زیر را دارم:';
            if (($product['code'] ?? '') !== '') {
                $lines[] = 'کد کالا: ' . $product['code'];
            }
            if (($product['name'] ?? '') !== '') {
                $lines[] = 'نام: ' . $product['name'];
            }
            if (($product['dimensions'] ?? '') !== '') {
                $lines[] = 'ابعاد: ' . $product['dimensions'];
            }
            if (($product['brands'] ?? []) !== []) {
                $lines[] = 'برند مورد نظر: ' . implode(' / ', $product['brands']);
            }
            $lines[] = 'ممنون می‌شوم راهنمایی بفرمایید.';
        } else {
            $lines[] = 'Hello, I would like to enquire about the following item:';
            if (($product['code'] ?? '') !== '') {
                $lines[] = 'Part code: ' . $product['code'];
            }
            if (($product['name'] ?? '') !== '') {
                $lines[] = 'Name: ' . $product['name'];
            }
            if (($product['dimensions'] ?? '') !== '') {
                $lines[] = 'Dimensions: ' . $product['dimensions'];
            }
            if (($product['brands'] ?? []) !== []) {
                $lines[] = 'Preferred brands: ' . implode(' / ', $product['brands']);
            }
            $lines[] = 'Could you advise on specification and availability?';
        }

        return $base . '?text=' . rawurlencode(implode("\n", $lines));
    }

    public function name(string $language): string
    {
        return $this->value($language === 'fa' ? 'nameFa' : 'nameEn');
    }

    public function legalName(string $language): string
    {
        return $this->value($language === 'fa' ? 'legalNameFa' : 'legalNameEn');
    }

    public function slogan(string $language): string
    {
        return $this->value($language === 'fa' ? 'sloganFa' : 'sloganEn');
    }

    public function address(string $language): string
    {
        return $this->value($language === 'fa' ? 'addressFa' : 'addressEn');
    }

    public function workingHours(string $language): string
    {
        return $this->value($language === 'fa' ? 'workingHoursFa' : 'workingHoursEn');
    }

    public function phoneDisplay(string $language, bool $landline = false): string
    {
        $prefix = $landline ? 'landlinePhoneDisplay' : 'primaryPhoneDisplay';

        return $this->value($prefix . ($language === 'fa' ? 'Fa' : 'En'));
    }
}
