<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'slug' => 'essential',
                'name' => 'Essential Compliance',
                'tagline' => 'Everything you need to meet baseline compliance requirements.',
                'monthly_price' => 99.00,
                'annual_price' => 999.00,
                'billing_type' => 'annual',
                'description' => 'Core compliance documentation for small healthcare practices getting started with regulatory requirements.',
                'features' => [
                    'Employee Handbook (Basic)',
                    'OSHA Safety Plan',
                    'Fillable intake questionnaire',
                    'Password-protected PDF delivery',
                    'Email support',
                ],
                'included_document_types' => [
                    'employee_handbook_basic',
                    'osha_safety_plan',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'professional',
                'name' => 'Professional Compliance',
                'tagline' => 'Comprehensive compliance for growing practices.',
                'monthly_price' => 129.00,
                'annual_price' => 1299.00,
                'billing_type' => 'annual',
                'description' => 'Everything in Essential plus a full employee handbook and HR policy manual for practices ready to scale.',
                'features' => [
                    'Employee Handbook (Full)',
                    'OSHA Safety Plan',
                    'HR Policy Manual',
                    'Fillable intake questionnaire',
                    'Password-protected PDF + Word delivery',
                    'Priority email support',
                ],
                'included_document_types' => [
                    'employee_handbook_full',
                    'osha_safety_plan',
                    'hr_policy_manual',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'advanced',
                'name' => 'Advanced Compliance',
                'tagline' => 'Full HIPAA and per-location OSHA coverage.',
                'monthly_price' => 169.00,
                'annual_price' => 1699.00,
                'billing_type' => 'annual',
                'description' => 'Everything in Professional plus HIPAA privacy policy and per-location OSHA reports for multi-site practices.',
                'features' => [
                    'Employee Handbook (Full)',
                    'OSHA Safety Plan',
                    'HR Policy Manual',
                    'HIPAA Privacy Policy',
                    'OSHA Location Report (per location)',
                    'Password-protected PDF + Word delivery',
                    'Dedicated compliance advisor',
                ],
                'included_document_types' => [
                    'employee_handbook_full',
                    'osha_safety_plan',
                    'hr_policy_manual',
                    'hipaa_privacy_policy',
                    'osha_location_report',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => 'complete',
                'name' => 'Complete Compliance',
                'tagline' => 'Fully custom compliance suite — contact us for a quote.',
                'monthly_price' => null,
                'annual_price' => null,
                'billing_type' => 'custom',
                'description' => 'Everything in Advanced plus a fully custom compliance document tailored to your practice. Pricing based on scope.',
                'features' => [
                    'Everything in Advanced',
                    'Revenue Cycle & Billing Compliance Manual',
                    'Compliance & Ethics Manual',
                    'Emergency Operations Plan',
                    'HIPAA Business Associate Manual',
                    'HIPAA Security Manual',
                    'Custom Compliance Document',
                    'White-glove onboarding',
                    'Quarterly compliance review',
                    'Direct phone & email support',
                ],
                'included_document_types' => [
                    'employee_handbook_full',
                    'osha_safety_plan',
                    'hr_policy_manual',
                    'hipaa_privacy_policy',
                    'osha_location_report',
                    'custom_compliance_document',
                    'revenue_cycle_billing_manual',
                    'compliance_ethics_manual',
                    'emergency_operations_plan',
                    'hipaa_business_associate_manual',
                    'hipaa_security_manual',
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($packages as $data) {
            Package::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
