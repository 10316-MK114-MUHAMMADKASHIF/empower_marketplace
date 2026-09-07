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
                    'Compliance & Ethics Program',
                    'HIPAA Policies',
                    'Training Platform',
                    'Employee Manual Review',
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
                    'Everything in Essential',
                    'Exclusions Screening',
                    'Compliance Hotline',
                    'Manuals & Manual Updates',
                    'Safety Review',
                    'Quarterly Compliance Meeting',
                    'Employee Manual Updates',
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
                    'Everything in Essential & Professional',
                    'Coding & Documentation Mini Audit (10 encounters/provider)',
                    'Security Risk Assessment (SRA)',
                    'Creation & Oversight of Compliance Department',
                    'Monthly Compliance Meeting',
                    'Employee Manual Creation',
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
                    'Everything in Essential, Professional & Advanced',
                    'Empower, by CareCloud operates as your fully operational compliance department',
                    'End-to-end ownership & oversight of your compliance program',
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
