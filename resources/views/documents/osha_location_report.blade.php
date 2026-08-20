@include('documents._header')

@php
    $loc = $oshaLocation;
@endphp

<div class="section">
    <h2>Location Information</h2>
    <table>
        <tr><td class="label">Practice Name</td><td>{{ $practice?->name ?? 'N/A' }}</td></tr>
        <tr><td class="label">Location Name</td><td>{{ $loc?->name ?? 'Primary Location' }}</td></tr>
        <tr><td class="label">Address</td><td>{{ $loc?->address ?: 'N/A' }}</td></tr>
        <tr><td class="label">OSHA Officer</td><td>{{ $loc?->osha_officer ?? 'N/A' }}</td></tr>
        <tr><td class="label">Safety Coordinator</td><td>{{ $loc?->safety_coordinator ?? 'N/A' }}</td></tr>
        @if($loc?->employees_per_year)
            <tr><td class="label">Employees per Year</td><td>{{ $loc->employees_per_year }}</td></tr>
        @endif
    </table>
</div>

<div class="section">
    <h2>1. Facility Profile</h2>
    <table>
        <tr><th>Characteristic</th><th>Status</th></tr>
        <tr><td>Uses Hazardous Drugs</td><td>{{ $loc?->uses_hazardous_drugs ? 'YES — Hazardous Drug Program Required' : 'No' }}</td></tr>
        <tr><td>Has Operating Rooms</td><td>{{ $loc?->has_operating_rooms ? 'YES — Surgical Safety Protocols Apply' : 'No' }}</td></tr>
        <tr><td>Offers Hepatitis B Vaccination</td><td>{{ $loc?->offers_hep_b_vaccination ? 'Yes' : 'No' }}</td></tr>
        <tr><td>Offers TB Screening</td><td>{{ $loc?->offers_tb_screening ? 'Yes' : 'No' }}</td></tr>
    </table>
</div>

<div class="section">
    <h2>2. Housekeeping and Cleaning Services</h2>
    <table>
        <tr><td class="label">Cleaning Provider</td><td>{{ $loc?->cleaning_provider ?? 'In-house' }}</td></tr>
        <tr><td class="label">Cleaning Frequency</td><td>{{ $loc?->cleaning_frequency ?? 'N/A' }}</td></tr>
    </table>
    <p>All cleaning staff must be trained in healthcare facility cleaning standards, including handling of regulated medical waste and proper use of disinfectants. Cleaning logs must be maintained and available for inspection.</p>
</div>

<div class="section">
    <h2>3. Medical Waste Management</h2>
    <table>
        <tr><td class="label">Waste Hauler</td><td>{{ $loc?->waste_hauler ?? 'N/A' }}</td></tr>
    </table>
    <p>Regulated medical waste (sharps, biohazardous materials, pharmaceutical waste) must be segregated, labeled, and disposed of in accordance with applicable state and federal regulations. Manifests must be retained for three (3) years.</p>

    @if($loc?->uses_hazardous_drugs)
        <h3>Hazardous Drug Waste</h3>
        <p>This location handles hazardous drugs. Hazardous pharmaceutical waste must be disposed of through a licensed hazardous waste contractor. All staff handling hazardous drugs must receive specialized training per USP 800 standards.</p>
    @endif
</div>

<div class="section">
    <h2>4. Required Location-Specific Safety Programs</h2>
    <table>
        <tr><th>Program</th><th>Required</th><th>Responsible Party</th></tr>
        <tr><td>Bloodborne Pathogen Exposure Control Plan</td><td>Yes</td><td>OSHA Officer</td></tr>
        <tr><td>Hazard Communication Program</td><td>Yes</td><td>Safety Coordinator</td></tr>
        <tr><td>Emergency Action Plan</td><td>Yes</td><td>Safety Coordinator</td></tr>
        @if($loc?->has_operating_rooms)
            <tr><td>Surgical Safety Checklist</td><td>Yes</td><td>OR Director</td></tr>
            <tr><td>Anesthesia Safety Protocols</td><td>Yes</td><td>Anesthesiologist</td></tr>
        @endif
        @if($loc?->uses_hazardous_drugs)
            <tr><td>USP 800 Hazardous Drug Program</td><td>Yes</td><td>Pharmacy/Clinical Lead</td></tr>
        @endif
    </table>
</div>

<div class="section">
    <h2>5. Employee Health Programs</h2>
    @if($loc?->offers_hep_b_vaccination)
        <p><strong>Hepatitis B Vaccination:</strong> This location offers Hepatitis B vaccination to all occupationally exposed employees at no cost. Employees who decline must sign a declination form.</p>
    @endif
    @if($loc?->offers_tb_screening)
        <p><strong>TB Screening:</strong> Annual TB screening is available and required for all clinical staff. Results must be documented in the employee health record.</p>
    @endif
</div>

<div class="section">
    <h2>6. Location-Specific Emergency Procedures</h2>
    <p>Emergency evacuation routes and assembly points are posted throughout this location. Fire extinguishers are inspected monthly and annually certified. AED devices are inspected per manufacturer specifications. Emergency drills are conducted at least annually.</p>
</div>

<div style="margin-top: 30px;">
    <div class="signature-line"></div>
    <p class="signature-label">OSHA Officer: {{ $loc?->osha_officer ?? '___________________________' }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date</p>
</div>

<div class="footer">
    {{ $practice?->name ?? '' }} &bull; OSHA Location Report: {{ $loc?->name ?? 'Primary' }} &bull; Generated {{ $generatedAt->format('m/d/Y') }} &bull; CONFIDENTIAL
</div>
